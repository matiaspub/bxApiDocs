<?
use \Bitrix\Security\Mfa\Otp;
use Bitrix\Security\Mfa\OtpException;

/**
 * @deprecated use \Bitrix\Security\Mfa\Otp
 */
class CSecurityUser
{
	const BX_SECURITY_SYNC_WINDOW = 15000;

	/** @var \Bitrix\Security\Mfa\Otp[]*/
	protected static $cacheOtp = array();

	/**
	 * @param int $userId
	 * @return Otp
	 */
	public static function getCachedOtp($userId)
	{
		if (!isset(static::$cacheOtp[$userId]))
		{
			static::$cacheOtp[$userId] = Otp::getByUser($userId);
		}

		return static::$cacheOtp[$userId];
	}
	/**
	 * @param array $arParams
	 * @return bool
	 */
	public static function onBeforeUserLogin(&$arParams)
	{
		//compatibility with old forms
		if (
			$arParams['PASSWORD_ORIGINAL'] === 'Y'
			&& preg_match('/(\d{6})$/D', $arParams["PASSWORD"], $arMatch)
		)
		{
			$arParams['OTP'] = $arMatch[1];
		}
		
		return true;
	}

	/**
	 * @param $arFields
	 * @return bool
	 */
	public static function update($arFields)
	{
		global $USER;
		$userId = intval($arFields['USER_ID']);
		$result = null;

		if (!$userId)
			return true;

		$otp = Otp::getByUser($userId);
		$canAdminOtp =
			!Otp::isMandatoryUsing() && $userId == $USER->GetID()
			|| $USER->CanDoOperation('security_edit_user_otp')
		;

		try
		{
			if (
				$arFields['ACTIVE'] !== 'Y'
				&& $otp->isActivated()
			)
			{
				if ($canAdminOtp)
				{
					$otp->deactivate();
					return true;
				}
				return false;
			}

			if (
				$arFields['DEACTIVATE_UNTIL'] > 0
				&& $otp->isActivated()
			)
			{
				if ($canAdminOtp)
				{
					$otp->deactivate((int) $arFields['DEACTIVATE_UNTIL']);
					return true;
				}
				return false;
			}

			$secret = substr(trim($arFields['SECRET']), 0, 64);
			if (!$secret)
			{
				if ($canAdminOtp)
				{
					$otp->delete();
					return true;
				}
				return false;
			}

			if ($otp->getHexSecret() != $secret)
			{
				// We want to connect new device
				$binarySecret = pack('H*', $secret);
				$otp->regenerate($binarySecret);
			}
			if ($arFields['TYPE'])
			{
				$otp->setType($arFields['TYPE']);
			}

			$sync1 = trim($arFields['SYNC1']);
			$sync2 = trim($arFields['SYNC2']);

			if ($sync1 || $sync2)
			{
				$otp->syncParameters($sync1, $sync2);
			}

			$otp
				->setActive(true)
				->save();
		}
		catch (OtpException $e)
		{
			/** @global CMain $APPLICATION */
			global $APPLICATION;
			$ex = array();
			$ex[] = array(
				'id' => 'security_otp',
				'text' => $e->getMessage()
			);

			$APPLICATION->ThrowException(
				new CAdminException($ex)
			);
			return false;
		}

		return true;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public static function onUserDelete($userId)
	{
		\Bitrix\Security\Mfa\UserTable::delete($userId);
		return true;
	}

	/**
	 * @return bool
	 */
	public static function isActive()
	{
		$bActive = false;
		foreach(GetModuleEvents("main", "OnBeforeUserLogin", true) as $event)
		{
			if(
				$event["TO_MODULE_ID"] == "security"
				&& $event["TO_CLASS"] == "CSecurityUser"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	/**
	 * @param bool $pActive
	 */
	public static function setActive($pActive = false)
	{
		$otpRecheckAgent = 'Bitrix\Security\Mfa\OtpEvents::onRecheckDeactivate();';
		if($pActive)
		{
			if(!CSecurityUser::isActive())
			{
				RegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin", "100");
				RegisterModuleDependences("main", "OnAfterUserLogout", "security", "CSecurityUser", "OnAfterUserLogout", "100");
				CAgent::RemoveAgent($otpRecheckAgent, "security");
				CAgent::Add(array(
					"NAME" => $otpRecheckAgent,
					"MODULE_ID" => "security",
					"ACTIVE" => "Y",
					"AGENT_INTERVAL" => 3600,
					"IS_PERIOD" => "N"
				));
				$f = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings.php", "w");
				fwrite($f, "<?include(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/modules/security/options_user_settings_1.php\");?>");
				fclose($f);
				COption::SetOptionString('security', 'otp_enabled', 'Y');
			}
		}
		else
		{
			if(CSecurityUser::isActive())
			{
				UnRegisterModuleDependences("main", "OnBeforeUserLogin", "security", "CSecurityUser", "OnBeforeUserLogin");
				UnRegisterModuleDependences("main", "OnAfterUserLogout", "security", "CSecurityUser", "OnAfterUserLogout");
				CAgent::RemoveAgent($otpRecheckAgent, "security");
				unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/security/options_user_settings.php");
				COption::SetOptionString('security', 'otp_enabled', 'N');
			}
		}
	}

	public static function OnAfterUserLogout()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$APPLICATION->set_cookie(Otp::SKIP_COOKIE, '', false, '/', false, false, true, false, true);

		// Clear deferred params
		Otp::setDeferredParams(null);
	}

	public static function IsOtpMandatory()
	{
		$isOtpMandatory = Otp::isMandatoryUsing();
		return ($isOtpMandatory ? true : false);
	}

	public static function IsUserOtpActive($userId)
	{
		if (!intval($userId))
			return false;

		$otp = static::getCachedOtp($userId);
		return ($otp->isActivated() ? true : false);
	}

	public static function IsUserSkipMandatoryRights($userId)
	{
		if (!intval($userId))
			return false;

		if (!static::IsOtpMandatory())
			return true;

		$otp = static::getCachedOtp($userId);
		return $otp->canSkipMandatoryByRights();
	}

	public static function IsUserOtpExist($userId)
	{
		if (!intval($userId))
			return false;

		$otp = static::getCachedOtp($userId);
		return ($otp->isInitialized() ? true : false);
	}

	public static function DeactivateUserOtp($userId, $days = 0)
	{
		/** @global CUser $USER */
		global $USER;

		if (!intval($userId))
			return false;

		$isOtpMandatory = self::IsOtpMandatory();

		if (
			!$isOtpMandatory && $userId == $USER->GetID()
			|| $USER->CanDoOperation('security_edit_user_otp')
		)
		{
			$otp = static::getCachedOtp($userId);
			try
			{
				$otp->deactivate($days);
				return true;
			}
			catch (OtpException $e)
			{
				return false;
			}

		}

		return false;
	}

	public static function DeferUserOtp($userId, $days = 0)
	{
		/** @global CUser $USER */
		global $USER;

		if (!intval($userId))
			return false;

		$isOtpMandatory = self::IsOtpMandatory();

		if (
			$isOtpMandatory
			&& $USER->CanDoOperation('security_edit_user_otp')
		)
		{
			$otp = static::getCachedOtp($userId);
			try
			{
				$otp->defer($days);
				return true;
			}
			catch (OtpException $e)
			{
				return false;
			}

		}

		return false;
	}

	public static function ActivateUserOtp($userId)
	{
		/** @global CUser $USER */
		global $USER;

		if (!intval($userId))
			return false;

		if (
			$userId == $USER->GetID()
			|| $USER->CanDoOperation('security_edit_user_otp')
		)
		{
			$otp = static::getCachedOtp($userId);
			try
			{
				$otp->activate();
				return true;
			}
			catch (OtpException $e)
			{
				return false;
			}
		}

		return false;
	}

	public static function GetDeactivateUntil($userId)
	{
		/** @global CUser $USER */
		global $USER;

		if (!intval($userId))
			return false;

		if (
			$userId == $USER->GetID()
			|| $USER->CanDoOperation('security_edit_user_otp')
		)
		{
			$otp = static::getCachedOtp($userId);
			return	$otp->getDeactivateUntil();
		}

		return false;
	}

	public static function GetInitialDate($userId)
	{
		/** @global CUser $USER */
		global $USER;

		if (!intval($userId))
			return false;

		$otp = static::getCachedOtp($userId);
		if ($otp->isActivated())
		{
			$datetime = $otp->getInitialDate();
			return $datetime;
		}

		return false;
	}
}
