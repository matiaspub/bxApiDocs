<?php
namespace Bitrix\Security\Mfa;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\TimeSigner;
use Bitrix\Main\Security\Random;
use Bitrix\Security\Codec\Base32;


Loc::loadMessages(__FILE__);

class Otp
{
	const TYPE_HOTP = 'hotp';
	const TYPE_TOTP = 'totp';
	const TYPE_DEFAULT = self::TYPE_HOTP;
	const SECRET_LENGTH = 20; // Must be power of 5 for "nicely" App Secret view

	const SKIP_COOKIE = 'OTPH';

	const REJECTED_KEY = 'OTP_REJECT_REASON';
	const REJECT_BY_CODE = 'code';
	const REJECT_BY_MANDATORY = 'mandatory';

	const TAGGED_CACHE_TEMPLATE = 'USER_OTP_%d';

	protected static $availableTypes = array(self::TYPE_HOTP, self::TYPE_TOTP);
	protected static $typeMap = array(
		self::TYPE_HOTP => '\Bitrix\Security\Mfa\HotpAlgorithm',
		self::TYPE_TOTP => '\Bitrix\Security\Mfa\TotpAlgorithm',
	);
	protected $algorithmClass = null;
	protected $regenerated = false;
	/* @var \Bitrix\Main\Context $context */
	protected $context = null;

	protected $userId = null;
	protected $userLogin = null;
	protected $userGroupPolicy = array();
	protected $active = null;
	protected $secret = null;
	protected $issuer = null;
	protected $label = null;
	protected $params = null;
	protected $attempts = null;
	protected $type = null;
	/** @var Type\DateTime */
	protected $initialDate = null;
	protected $skipMandatory = null;
	/** @var Type\DateTime */
	protected $deactivateUntil = null;

	/**
	 * @param string|null $algorithm Class of needed OtpAlgorithm.
	 */
	public function __construct($algorithm = null)
	{
		if ($algorithm === null)
		{
			$this->setType(static::getDefaultType());
		}
		else
		{
			$this->algorithmClass = $algorithm;
		}
	}

	/**
	 * Return new instance for user provided by user ID
	 *
	 * @param int $userId User ID.
	 * @throws ArgumentOutOfRangeException
	 * @throws ArgumentTypeException
	 * @return static New instance, if user does not use OTP - returning NullObject (see Otp::isActivated).
	 */
	public static function getByUser($userId)
	{
		$userId = (int) $userId;

		if ($userId <= 0)
			throw new ArgumentTypeException('userId', 'positive integer');

		$userInfo = UserTable::getList(array(
			'filter' => array('=USER_ID' => $userId),
			'select' => array('ACTIVE', 'USER_ID', 'SECRET', 'PARAMS', 'TYPE', 'ATTEMPTS', 'INITIAL_DATE', 'SKIP_MANDATORY', 'DEACTIVATE_UNTIL')
		));

		$userInfo = $userInfo->fetch();

		if (!$userInfo)
		{
			// OTP not available for this user
			$instance = new static;
			$instance->setUserId($userId);
			$instance->setActive(false);
		}
		else
		{
			$type = $userInfo['TYPE']?: self::TYPE_DEFAULT;
			$userInfo['SECRET'] = pack('H*', $userInfo['SECRET']);
			$userInfo['ACTIVE'] = $userInfo['ACTIVE'] === 'Y';
			$userInfo['SKIP_MANDATORY'] = $userInfo['SKIP_MANDATORY'] === 'Y';

			$instance = static::getByType($type);
			$instance->setUserInfo($userInfo);
		}

		return $instance;
	}

	/**
	 * Return new instance with needed OtpAlgorithm type
	 *
	 * @param string $type Type of OtpAlgorithm (see getAvailableTypes).
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @return static New instance
	 */
	public static function getByType($type)
	{
		if (!in_array($type, static::$availableTypes))
			throw new ArgumentOutOfRangeException('type', static::$availableTypes);

		$algo = static::$typeMap[$type];
		$instance = new static($algo);
		$instance->setType($type);
		return $instance;
	}

	/**
	 * Set new type of OtpAlgorithm
	 *
	 * @param string $type Type of OtpAlgorithm (see getAvailableTypes).
	 * @throws ArgumentOutOfRangeException
	 * @return $this
	 */
	public function setType($type)
	{
		if (!in_array($type, static::$availableTypes))
			throw new ArgumentOutOfRangeException('type', static::$availableTypes);

		$this->algorithmClass = static::$typeMap[$type];
		$this->type = $type;

		return $this;
	}

	/**
	 * Return used OtpAlgorithm type
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Return instance of used OtpAlgorithm
	 *
	 * @return OtpAlgorithm
	 */
	public function getAlgorithm()
	{
		/** @var OtpAlgorithm $algorithm */
		$algorithm = new $this->algorithmClass;
		return $algorithm->setSecret($this->getSecret());
	}

	/**
	 * Return Provision URI according to KeyUriFormat
	 *
	 * @link https://code.google.com/p/google-authenticator/wiki/KeyUriFormat
	 * @param array $opts Additional URI parameters, e.g. ['image' => 'http://example.com/my_logo.png'] .
	 * @return string
	 */
	public function getProvisioningUri(array $opts = array())
	{
		$issuer = $this->getIssuer();
		$opts += array('issuer' => $issuer);

		return $this
			->getAlgorithm()
			->generateUri(
				$this->getLabel($issuer),
				$opts
			);
	}

	/**
	 * Reinitialize OTP (generate new secret, set default algo, etc), must be called before connect new device
	 *
	 * @param null $newSecret Using custom secret.
	 * @return $this
	 */
	public function regenerate($newSecret = null)
	{
		if (!$newSecret)
		{
			$newSecret = Random::getBytes(static::SECRET_LENGTH);
		}

		$this->regenerated = true;
		return $this
			->setType(static::getDefaultType())
			->setAttempts(0)
			->setSkipMandatory(false)
			->setInitialDate(new Type\DateTime)
			->setDeactivateUntil(null)
			->setParams(null)
			->setSecret($newSecret)
			->setActive(true)
		;
	}

	/**
	 * Verify provided input
	 *
	 * @param string $input Input received from user.
	 * @param bool $updateParams Update or not user parameters in DB (e.g. counter for HotpAlgorithm).
	 * @return bool True if input is valid.
	 */
	public function verify($input, $updateParams = true)
	{
		list($result, $newParams) = $this->getAlgorithm()->verify($input, $this->getParams());

		if (
			$updateParams
			&& $newParams !== null
			&& $this->isActivated()
		)
		{
			$this
				->setParams($newParams)
				->save()
			;
		}
		return $result;
	}

	/**
	 * Check is verifying attempts reached according to group security policy
	 * May be used for show Captcha or what ever you want
	 *
	 * @return bool
	 */
	public function isAttemptsReached()
	{
		$attempts  = $this->getAttempts();
		$maxAttempts = $this->getMaxLoginAttempts();
		return (bool) (
			$maxAttempts > 0
			&& $attempts >= $maxAttempts
		);
	}

	/**
	 * Return synchronized user params for provided inputs
	 *
	 * @param string $inputA First code.
	 * @param string $inputB Second code.
	 * @return string
	 */
	public function getSyncParameters($inputA, $inputB)
	{
		return $this->getAlgorithm()->getSyncParameters((string) $inputA, (string) $inputB);
	}

	/**
	 * Synchronize user params for provided inputs
	 * Must be called after regenerate and before save!
	 * If something went wrong - throw OtpException with valid description in message
	 *
	 * @param string $inputA First code.
	 * @param string|null $inputB Second code.
	 * @throws OtpException
	 * @return $this
	 */
	public function syncParameters($inputA, $inputB = null)
	{
		if (!$inputA)
			throw new OtpException(Loc::getMessage('SECURITY_OTP_ERROR_PASS1_EMPTY'));
		elseif (!preg_match('/^\d{6}$/D', $inputA))
			throw new OtpException(getMessage('SECURITY_OTP_ERROR_PASS1_INVALID'));

		if ($this->getAlgorithm()->isTwoCodeRequired())
		{
			if (!$inputB)
				throw new OtpException(Loc::getMessage('SECURITY_OTP_ERROR_PASS2_EMPTY'));
			elseif (!preg_match('/^\d{6}$/D', $inputB))
				throw new OtpException(Loc::getMessage('SECURITY_OTP_ERROR_PASS2_INVALID'));
		}

		try
		{
			$params = $this->getSyncParameters($inputA, $inputB);
		}
		catch (OtpException $e)
		{
			throw new OtpException(Loc::getMessage('SECURITY_OTP_ERROR_SYNC_ERROR'));
		}

		$this->setParams($params);

		return $this;
	}

	/**
	 * Save all OTP data to DB
	 *
	 * @throws OtpException
	 * @return bool
	 */
	public function save()
	{
		$fields = array(
			'ACTIVE' => $this->isActivated()? 'Y': 'N',
			'TYPE' => $this->getType(),
			'ATTEMPTS' => $this->getAttempts(),
			'SECRET' => $this->getHexSecret(),
			'INITIAL_DATE' => $this->getInitialDate()?: new Type\DateTime,
			'PARAMS' => $this->getParams(),
			'SKIP_MANDATORY' => $this->isMandatorySkipped()? 'Y': 'N',
			'DEACTIVATE_UNTIL' => $this->getDeactivateUntil()
		);

		if ($this->regenerated)
		{
			if (!$this->isInitialized())
				throw new OtpException('Missing OTP params, forgot to call syncParameters?');

			// Clear recovery codes when we connect new device
			RecoveryCodesTable::clearByUser($this->getUserId());
		}


		if ($this->isDbRecordExists())
		{
			$result = UserTable::update($this->getUserId(), $fields);
		}
		else
		{
			$fields += array(
				'USER_ID' => $this->getUserId(),
			);
			$result = UserTable::add($fields);
		}

		$this->clearGlobalCache();

		return $result->isSuccess();
	}

	/**
	 * Delete OTP record from DB
	 *
	 * @return $this
	 */
	public function delete()
	{
		UserTable::delete($this->getUserId());

		return $this;
	}

	/**
	 * Activate user OTP
	 * OTP must be initialized (have secret, params, etc) before activate
	 *
	 * @return $this
	 * @throws OtpException
	 */
	public function activate()
	{
		if (!$this->isInitialized())
			throw new OtpException('OTP not initialized, if your activate it - user can\'t login anymore. Do you forgot to call regenerate?');

		$this
			->setActive(true)
			->setDeactivateUntil(null)
			->save();

		return $this;
	}

	/**
	 * Deactivate user OTP for a needed number of days or forever
	 *
	 * @param int $days Days. 0 means "forever".
	 * @return $this
	 * @throws OtpException
	 */
	public function deactivate($days = 0)
	{
		if (!$this->isActivated())
			throw new OtpException('Otp not activated. Do your mean deffer?');

		$this->setActive(false);
		$this->setSkipMandatory(true);

		if ($days <= 0)
		{
			$this->setDeactivateUntil(null);
		}
		else
		{
			$deactivateDate = Type\DateTime::createFromTimestamp(time() + $days * 86400);
			$this->setDeactivateUntil($deactivateDate);
		}

		$this->save();

		return $this;
	}

	/**
	 * Defer  mandatory user OTP using for a needed number of days or forever
	 *
	 * @param int $days Days. 0 means "forever".
	 * @return $this
	 * @throws OtpException
	 */
	public function defer($days = 0)
	{
		if ($this->isActivated())
			throw new OtpException('Otp already activated. Do your mean deactivate?');

		$this->setSkipMandatory(true);
		if ($days <= 0)
		{
			$this->setDeactivateUntil(null);
		}
		else
		{
			$deactivateDate = Type\DateTime::createFromTimestamp(time() + $days * 86400);
			$this->setDeactivateUntil($deactivateDate);
		}

		$this->save();

		return $this;
	}

	/**
	 * Set new user information
	 * Mostly used for initialization from DB
	 * Now support:
	 *  - ACTIVE: bool, activating state (see setActive)
	 *  - USER_ID: integer, User ID (see setUserId)
	 *  - ATTEMPTS: integer, Attempts counter (see setAttempts)
	 *  - SECRET: binary, User secret (see setSecret)
	 *  - PARAMS: string, User params (see setParams and getSyncParameters)
	 *  - INITIAL_DATE: Type\Date, OTP initial date (see setInitialDate)
	 *
	 * @param array $userInfo See above.
	 * @return $this
	 */
	public function setUserInfo(array $userInfo)
	{
		$this->setActive($userInfo['ACTIVE']);
		$this->setUserId($userInfo['USER_ID']);
		$this->setAttempts($userInfo['ATTEMPTS']);
		$this->setSecret($userInfo['SECRET']);
		$this->setParams($userInfo['PARAMS']);
		$this->setSkipMandatory($userInfo['SKIP_MANDATORY']);

		// Old users haven't INITIAL_DATE and DEACTIVATE_UNTIL
		// ToDo: maybe it's not the best approach, think about it later
		if ($userInfo['INITIAL_DATE'])
			$this->setInitialDate($userInfo['INITIAL_DATE']);

		if ($userInfo['DEACTIVATE_UNTIL'])
			$this->setDeactivateUntil($userInfo['DEACTIVATE_UNTIL']);

		return $this;
	}

	/**
	 * Set new OTP initialization date
	 *
	 * @param Type\DateTime $date Initialization date.
	 * @return $this
	 */
	protected function setInitialDate(Type\DateTime $date)
	{
		$this->initialDate = $date;

		return $this;
	}

	/**
	 * Returns OTP initialization date
	 *
	 * @return Type\DateTime
	 */
	public function getInitialDate()
	{
		return $this->initialDate;
	}

	/**
	 * Set datetime when user OTP must activated back
	 *
	 * @param Type\DateTime|null $date Datetime. "null" means never.
	 * @return $this
	 */
	protected function setDeactivateUntil($date)
	{
		$this->deactivateUntil = $date;

		return $this;
	}

	/**
	 * @return Type\DateTime
	 */
	public function getDeactivateUntil()
	{
		return $this->deactivateUntil;
	}

	/**
	 * Set if user allowed to bypass OTP mandatory using while authorization
	 *
	 * @param bool $isSkipped Allowed or not.
	 * @return $this
	 */
	protected function setSkipMandatory($isSkipped = true)
	{
		$this->skipMandatory = $isSkipped;

		return $this;
	}

	/**
	 * Returns true if user can skip mandatory using
	 *
	 * @return bool
	 */
	public function isMandatorySkipped()
	{
		return $this->skipMandatory;
	}

	/**
	 * Returns Unix timestamp of OTP initialization date
	 *
	 * @return int
	 */
	protected function getInitialTimestamp()
	{
		$initialDate = $this->getInitialDate();
		if (!$initialDate)
			return 0;

		return $initialDate->getTimestamp();
	}

	/**
	 * Set new User ID
	 *
	 * @param int $userId User ID.
	 * @return $this
	 */
	protected function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * Return used User ID
	 *
	 * @return int
	 */
	public function getUserId()
	{
		return (int) $this->userId;
	}

	/**
	 * Set new activating state
	 *
	 * @param bool $isActive Otp is activated or not.
	 * @return $this
	 */
	public function setActive($isActive)
	{
		$this->active = $isActive;

		return $this;
	}

	/**
	 * Return is OTP activated or not
	 *
	 * @return bool
	 */
	public function isActivated()
	{
		return (bool) $this->active;
	}

	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		if ($this->isActivated())
		{
			// Without "hacks" OTP can't be activated without initialization
			return true;
		}

		// ToDo: maybe better add new property with column?
		return (bool) $this->getSecret();
	}

	/**
	 * Set new verifying attempts count
	 *
	 * @param int $attemptsCount Attempts count.
	 * @return $this
	 */
	protected function setAttempts($attemptsCount)
	{
		$this->attempts = $attemptsCount;

		return $this;
	}

	/**
	 * Return verifying attempts count
	 *
	 * @return int
	 */
	public function getAttempts()
	{
		return (int) $this->attempts;
	}

	/**
	 * Set new user params (e.g. counter for HotpAlgorithm)
	 *
	 * @see getSyncParameters
	 * @param string $params User params.
	 * @return $this
	 */
	protected function setParams($params)
	{
		$this->params = $params;

		return $this;
	}

	/**
	 * Return user params (e.g. counter for HotpAlgorithm)
	 *
	 * @return string
	 */
	public function getParams()
	{
		return (string) $this->params;
	}

	/**
	 * Return binary secret
	 *
	 * @return string
	 */
	public function getSecret()
	{
		return $this->secret;
	}

	/**
	 * Return hex-encoded secret
	 *
	 * @return string
	 */
	public function getHexSecret()
	{
		$secret = $this->getSecret();

		return bin2hex($secret);
	}

	/**
	 * Return mobile application secret, using for manual device initialization
	 *
	 * @return string
	 */
	public function getAppSecret()
	{
		$secret = $this->getSecret();
		$secret = Base32::encode($secret);

		return rtrim($secret, '=');
	}

	/**
	 * Set new secret
	 *
	 * @param string $secret Binary secret.
	 * @return $this
	 */
	public function setSecret($secret)
	{
		$this->secret = $secret;
		return $this;
	}

	/**
	 * Set new secret in hex-encoded representation
	 *
	 * @param string $hexValue Hex-encoded secret.
	 * @return $this
	 */
	public function setHexSecret($hexValue)
	{
		$secret = pack('H*', $hexValue);

		return $this->setSecret($secret);
	}

	/**
	 * Set new mobile application secret
	 *
	 * @param string $value Secret.
	 * @return $this
	 */
	public function setAppSecret($value)
	{
		$secret = Base32::decode($value);

		return $this->setSecret($secret);
	}

	/**
	 * Return issuer.
	 * If custom issuer not available - return default (see getDefaultIssuer).
	 *
	 * @return string
	 */
	public function getIssuer()
	{
		if ($this->issuer === null)
			$this->issuer = $this->getDefaultIssuer();

		return $this->issuer;
	}

	/**
	 * Set custom issuer
	 *
	 * @param string $issuer Issuer.
	 * @return $this
	 */
	public function setIssuer($issuer)
	{
		$this->issuer = $issuer;
		return $this;
	}

	/**
	 * Return label for issuer (if provided)
	 * If custom label not available - generate default (see generateLabel)
	 *
	 * @param string|null $issuer Issuer.
	 * @return string
	 */
	public function getLabel($issuer = null)
	{
		if ($this->label === null)
			$this->label = $this->generateLabel($issuer);

		return $this->label;
	}

	/**
	 * Set custom label
	 *
	 * @param string $label Label.
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * Returns context of the current request.
	 *
	 * @return \Bitrix\Main\Context
	 */
	public function getContext()
	{
		if ($this->context === null)
			$this->context = Application::getInstance()->getContext();

		return $this->context;
	}

	/**
	 * Set context of the current request.
	 *
	 * @param \Bitrix\Main\Context $context Application context.
	 * @return \Bitrix\Main\Context
	 */
	public function setContext(\Bitrix\Main\Context $context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * Set custom user login
	 *
	 * @param string $login Login.
	 * @return $this
	 */
	public function setUserLogin($login)
	{
		$this->userLogin = $login;

		return $this;
	}

	/**
	 * Return user login
	 * If custom login not available it will be fetched from DB
	 *
	 * @return string
	 */
	public function getUserLogin()
	{
		if ($this->userLogin === null && $this->userId)
		{
			$this->userLogin = \Bitrix\Main\UserTable::query()
				->addFilter('=ID', $this->getUserId())
				->addSelect('LOGIN')
				->exec()
				->fetch();

			$this->userLogin = $this->userLogin['LOGIN'];
		}

		return $this->userLogin;
	}

	/**
	 * Return default issuer
	 *
	 * @return string
	 */
	protected function getDefaultIssuer()
	{
		$host = Option::get('main', 'server_name');
		if($host)
		{
			return preg_replace('#:\d+$#D', '', $host);
		}
		else
		{
			return Option::get('security', 'otp_issuer', 'Bitrix');
		}
	}

	/**
	 * Generate label, based on current host, user login and issuer (if provided)
	 *
	 * @param string|null $issuer Issuer.
	 * @return string
	 */
	protected function generateLabel($issuer = null)
	{
		if ($issuer)
			return sprintf('%s:%s', $issuer, $this->getUserLogin());
		else
			return $this->getUserLogin();
	}

	/**
	 * Return maximum verifying attempts, based on security group policy
	 *
	 * @return int
	 */
	protected function getMaxLoginAttempts()
	{
		if (!$this->isActivated())
			return 0;

		return (int) $this->getPolicy('LOGIN_ATTEMPTS');
	}

	/**
	 * Return how long (in sec)remember value are valid
	 *
	 * @return int
	 */
	protected function getRememberLifetime()
	{
		if (!$this->isActivated())
			return 0;

		return ((int) $this->getPolicy('STORE_TIMEOUT')) * 60;
	}

	/**
	 * Return IP mask for checks remember value
	 *
	 * @return string
	 */
	protected function getRememberIpMask()
	{
		if (!$this->isActivated())
			return '255.255.255.255';

		return $this->getPolicy('STORE_IP_MASK');
	}

	/**
	 * Check if current user can skip OTP mandatory using.
	 * It can skip if:
	 *  - Otp already activated
	 *  - User never login before
	 *  - User not included to mandatory rights
	 *  - The current date is included in the window initialization
	 *
	 * @return bool
	 */
	public function canSkipMandatory()
	{
		$result = $this->isMandatorySkipped();

		if (!$result)
		{
			// Check mandatory rights
			$result = $this->canSkipMandatoryByRights();
		}

		return $result;
	}

	/**
	 * Check if current user not included to mandatory rights
	 *
	 * @return bool
	 */
	public function canSkipMandatoryByRights()
	{
		$targetRights = static::getMandatoryRights();
		$userRights = \CAccess::getUserCodesArray($this->getUserId());
		$existedRights = array_intersect($targetRights, $userRights);
		$result = empty($existedRights);

		return $result;
	}

	/**
	 * Check if user have valid cookie for skip OTP checking ("Remember OTP on this computer")
	 *
	 * @return bool
	 */
	protected function canSkipByCookie()
	{
		if (Option::get('security', 'otp_allow_remember') !== 'Y')
			return false;

		$signedValue = $this->getContext()->getRequest()->getCookie(static::SKIP_COOKIE);

		if (!$signedValue || !is_string($signedValue))
			return false;

		try
		{
			$signer = new TimeSigner();
			$value = $signer
				->setKey($this->getSecret())
				->unsign($signedValue, 'MFA_SAVE');
		}
		catch (BadSignatureException $e)
		{
			return false;
		}

		return ($value === $this->getSkipCookieValue());
	}

	/**
	 * Generate skip value for save in cookies
	 * Currently based on client IP and mask (see getRememberIpMask)
	 *
	 * @return string
	 */
	protected function getSkipCookieValue()
	{
		// ToDo: must be tied to the ID of "computer" when it will appear in the main module
		$rememberMask = $this->getRememberIpMask();
		$userIp = $this->getContext()->getRequest()->getRemoteAddress();
		return md5(ip2long($rememberMask) & ip2long($userIp));
	}

	/**
	 * Store new value for skip OTP checking ("Remember OTP on this computer") in cookies
	 *
	 * @return $this
	 */
	protected function setSkipCookie()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$signer = new TimeSigner();
		$rememberLifetime = $this->getRememberLifetime();
		$rememberLifetime += time();
		$rememberValue = $this->getSkipCookieValue();

		$signedValue = $signer
			->setKey($this->getSecret())
			->sign($rememberValue, $rememberLifetime, 'MFA_SAVE');

		$isSecure = (
			Option::get('main', 'use_secure_password_cookies', 'N') === 'Y'
			&& $this->getContext()->getRequest()->isHttps()
		);

		$APPLICATION->set_cookie(
			static::SKIP_COOKIE, // $name
			$signedValue,        // $value
			$rememberLifetime,   // $time = false
			'/',                 // $folder = "/"
			false,               // $domain = false
			$isSecure,           // $secure = false
			true,                // $spread = true
			false,               // $name_prefix = false
			true                 // $httpOnly = false
		);

		return $this;
	}

	/**
	 * Check if OTP record exists in DB
	 *
	 * @return bool
	 */
	protected function isDbRecordExists()
	{
		return UserTable::getRowById($this->getUserId()) !== null;
	}

	/**
	 * Return needed group security policy
	 *
	 * @param string $name Name of policy.
	 * @return null
	 */
	protected function getPolicy($name)
	{
		if (!$this->userGroupPolicy)
			$this->userGroupPolicy = \CUser::getGroupPolicy($this->getUserId());

		if (isset($this->userGroupPolicy[$name]))
			return $this->userGroupPolicy[$name];
		else
			return null;
	}

	/**
	 * Clear cache for this OTP in global scope
	 *
	 * @return $this
	 */
	protected function clearGlobalCache()
	{
		Application::getInstance()->getTaggedCache()->clearByTag(
			sprintf(static::TAGGED_CACHE_TEMPLATE, (int) ($this->getUserId() / 100))
		);
		return $this;
	}

	/**
	 * Most complex method, can check everything:-)
	 * ToDo: describe after refactoring
	 *
	 * @param array $params
	 * @throws ArgumentTypeException
	 * @return bool
	 */
	public static function verifyUser(array $params)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if (!static::isOtpEnabled()) // OTP disabled in settings
			return true;

		$isSuccess = false;

		// ToDo: review and refactoring needed
		$otp = static::getByUser($params['USER_ID']);

		if (!$otp->isActivated())
		{
			// User does not use OTP
			$isSuccess = true;

			if (
				static::isMandatoryUsing()
				&& !$otp->canSkipMandatory()
			)
			{
				// Grace full period ends. We must reject authorization and defer reject reason
				if (!$otp->isDbRecordExists() && static::getSkipMandatoryDays())
				{
					// If mandatory enabled and user never use OTP - let's deffer initialization
					$otp->defer(static::getSkipMandatoryDays());

					// We forgive the user for the first time
					static::setDeferredParams(null);
					return true;
				}

				// Save a flag which indicates that a OTP is required, but user doesn't use it :-(
				$params[static::REJECTED_KEY] = static::REJECT_BY_MANDATORY;
				static::setDeferredParams($params);
				return false;
			}
		}

		if (!$isSuccess)
		{
			// User skip OTP on this browser by cookie
			$isSuccess = $otp->canSkipByCookie();
		}

		if (!$isSuccess)
		{
			$isCaptchaChecked = (
				!$otp->isAttemptsReached()
				|| $APPLICATION->captchaCheckCode($params['CAPTCHA_WORD'], $params['CAPTCHA_SID'])
			);
			$isRememberNeeded = (
				$params['OTP_REMEMBER']
				&& Option::get('security', 'otp_allow_remember') === 'Y'
			);

			if (!$isCaptchaChecked && !$_SESSION['BX_LOGIN_NEED_CAPTCHA'])
			{
				// Backward compatibility with old login page
				$_SESSION['BX_LOGIN_NEED_CAPTCHA'] = true;
			}

			$isOtpPassword = (bool) preg_match('/^\d{6}$/D', $params['OTP']);
			$isRecoveryCode = (
				static::isRecoveryCodesEnabled()
				&& (bool) preg_match(RecoveryCodesTable::CODE_PATTERN, $params['OTP'])
			);

			if ($isCaptchaChecked && ($isOtpPassword || $isRecoveryCode))
			{
				if ($isOtpPassword)
					$isSuccess = $otp->verify($params['OTP'], true);
				elseif ($isRecoveryCode)
					$isSuccess = RecoveryCodesTable::useCode($otp->getUserId(), $params['OTP']);
				else
					$isSuccess = false;

				if (!$isSuccess)
				{
					$otp
						->setAttempts($otp->getAttempts() + 1)
						->save();
				}
				else
				{
					if ($otp->getAttempts() > 0)
					{
						// Clear OTP input attempts
						$otp
							->setAttempts(0)
							->save();
					}

					if ($isRememberNeeded && $isOtpPassword)
					{
						// If user provide otp password (not recovery codes)
						// Sets cookie for bypass OTP checking
						$otp->setSkipCookie();
					}
				}
			}
		}


		if ($isSuccess)
		{
			static::setDeferredParams(null);
		}
		else
		{
			// Save a flag which indicates that a form for OTP is required
			$params[static::REJECTED_KEY] = static::REJECT_BY_CODE;
			static::setDeferredParams($params);
		}

		return $isSuccess;
	}

	/**
	 * Returns true if user must provide password from device
	 *
	 * @return bool
	 */
	public static function isOtpRequired()
	{
		return static::getDeferredParams() !== null;
	}

	/**
	 * Returns true if user doesn't use OTP, but it required and grace full period ends
	 *
	 * @return bool
	 */
	public static function isOtpRequiredByMandatory()
	{
		$params = static::getDeferredParams();
		if (
			!$params
			|| !isset($params[static::REJECTED_KEY])
		)
		{
			return false;
		}

		return $params[static::REJECTED_KEY] === static::REJECT_BY_MANDATORY;
	}

	/**
	 * Return if user must provide captcha code before checking OTP password
	 *
	 * @return bool
	 */
	public static function isCaptchaRequired()
	{
		$params = static::getDeferredParams();

		if (!$params || !isset($params['USER_ID']))
			return false;

		$otp = static::getByUser($params['USER_ID']);
		return $otp && $otp->isAttemptsReached();
	}

	/**
	 * Return deferred params (see verifyUser)
	 *
	 * @return array|null
	 */
	public static function getDeferredParams()
	{
		if (isset($_SESSION['BX_SECURITY_OTP']) && is_array($_SESSION['BX_SECURITY_OTP']))
		{
			return $_SESSION['BX_SECURITY_OTP'];
		}

		return null;
	}

	/**
	 * Set or delete deferred params (see verifyUser)
	 *
	 * @param array|null $params Params, null means deleting params from storage.
	 * @return void
	 */
	public static function setDeferredParams($params)
	{
		if ($params === null)
		{
			unset($_SESSION['BX_SECURITY_OTP']);
		}
		else
		{
			// Probably we does not need save password in deferred params
			// Or need? I don't know right now...
			if (isset($params['PASSWORD']))
				unset($params['PASSWORD']);

			$_SESSION['BX_SECURITY_OTP'] = $params;
		}
	}

	/**
	 * Set initialization window (in days) for mandatory using checking
	 *
	 * @param int $days Days of initialization window. "0" means immediately (on next user authorization).
	 * @return void
	 */
	public static function setSkipMandatoryDays($days = 2)
	{
		Option::set('security', 'otp_mandatory_skip_days', (int) $days, null);
	}

	/**
	 * Return initialization window (in days) for mandatory using checking
	 *
	 * @return int
	 */
	public static function getSkipMandatoryDays()
	{
		return (int) Option::get('security', 'otp_mandatory_skip_days');
	}

	/**
	 * Activate or deactivate mandatory OTP using
	 *
	 * @param bool $isMandatory Active or not.
	 * @return void
	 */
	public static function setMandatoryUsing($isMandatory = true)
	{
		Option::set('security', 'otp_mandatory_using', $isMandatory? 'Y': 'N', null);
	}

	/**
	 * Return is mandatory OTP using activated
	 *
	 * @return bool
	 */
	public static function isMandatoryUsing()
	{
		return (bool) (Option::get('security', 'otp_mandatory_using') === 'Y');
	}

	/**
	 * Set user rights who must use OTP in mandatory way
	 *
	 * @param array $rights Needed rights. E.g. ['G1'] for administrators.
	 * @return void
	 */
	public static function setMandatoryRights(array $rights)
	{
		Option::set('security', 'otp_mandatory_rights', serialize($rights), null);
	}

	/**
	 * Return user rights who must use OTP in mandatory way
	 *
	 * @return array
	 */
	public static function getMandatoryRights()
	{
		$targetRights = Option::get('security', 'otp_mandatory_rights');
		$targetRights = unserialize($targetRights);
		if (!is_array($targetRights))
			$targetRights = array();

		return $targetRights;
	}

	/**
	 * Set default OtpAlgorithm type
	 *
	 * @param string $value OtpAlgorithm type (see getAvailableTypes).
	 * @throws ArgumentOutOfRangeException
	 * @return void
	 */
	public static function setDefaultType($value)
	{
		if (!in_array($value, static::$availableTypes))
			throw new ArgumentOutOfRangeException('value', static::$availableTypes);

		Option::set('security', 'otp_default_algo', $value, null);
	}


	/**
	 * Return default OtpAlgorithm type
	 *
	 * @return string
	 */
	public static function getDefaultType()
	{
		return Option::get('security', 'otp_default_algo');
	}

	/**
	 * Return available OtpAlgorithm types
	 *
	 * @return array
	 */
	public static function getAvailableTypes()
	{
		return static::$availableTypes;
	}

	/**
	 * Return available OtpAlgorithm types description
	 *
	 * @return array
	 */
	public static function getTypesDescription()
	{
		$result = array();
		foreach(static::getAvailableTypes() as $type)
		{
			$result[$type] = call_user_func(array(static::$typeMap[$type], 'getDescription'));
		}

		return $result;
	}

	/**
	 * Returns if OTP enabled
	 *
	 * @return bool
	 */
	public static function isOtpEnabled()
	{
		return (bool) (Option::get('security', 'otp_enabled') === 'Y');
	}

	/**
	 * Returns if "Recovery codes" are enabled
	 *
	 * @return bool
	 */
	public static function isRecoveryCodesEnabled()
	{
		return (bool) (Option::get('security', 'otp_allow_recovery_codes') === 'Y');
	}
}
