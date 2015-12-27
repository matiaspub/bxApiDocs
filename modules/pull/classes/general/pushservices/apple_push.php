<?php

class CAppleMessage extends CPushMessage
{
	const PAYLOAD_MAXIMUM_SIZE = 2048;
	const APPLE_RESERVED_NAMESPACE = 'aps';

	protected $_bAutoAdjustLongPayload = true;

	public function __construct($sDeviceToken = null)
	{
		if (isset($sDeviceToken))
		{
			$this->addRecipient($sDeviceToken);
		}
	}

	public function setAutoAdjustLongPayload($bAutoAdjust)
	{
		$this->_bAutoAdjustLongPayload = (boolean)$bAutoAdjust;
	}

	public function getAutoAdjustLongPayload()
	{
		return $this->_bAutoAdjustLongPayload;
	}

	protected function _getPayload()
	{
		$aPayload[self::APPLE_RESERVED_NAMESPACE] = array();

		if (isset($this->text))
		{
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['alert'] = (string)$this->text;
		}

		if (isset($this->category))
		{
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['category'] = (string)$this->category;
		}

		if (isset($this->badge) && $this->badge >= 0)
		{
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['badge'] = (int)$this->badge;
		}
		if (isset($this->sound) && strlen($this->sound) > 0)
		{
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['sound'] = (string)$this->sound;
		}

		if (is_array($this->customProperties))
		{
			foreach ($this->customProperties as $sPropertyName => $mPropertyValue)
			{
				$aPayload[$sPropertyName] = $mPropertyValue;
			}
		}

		return $aPayload;
	}

	public function getPayload()
	{
		$sJSONPayload = str_replace(
			'"' . self::APPLE_RESERVED_NAMESPACE . '":[]',
			'"' . self::APPLE_RESERVED_NAMESPACE . '":{}',
			CPushManager::_MakeJson($this->_getPayload(), "", false)
		);
		$nJSONPayloadLen = CUtil::BinStrlen($sJSONPayload);

		if ($nJSONPayloadLen > self::PAYLOAD_MAXIMUM_SIZE)
		{
			if ($this->_bAutoAdjustLongPayload)
			{
				$nMaxTextLen = $nTextLen = CUtil::BinStrlen($this->text) - ($nJSONPayloadLen - self::PAYLOAD_MAXIMUM_SIZE);
				if ($nMaxTextLen > 0)
				{
					while (CUtil::BinStrlen($this->text = CUtil::BinSubstr($this->text, 0, --$nTextLen)) > $nMaxTextLen) ;

					return $this->getPayload();
				}
				else
				{
					throw new Exception(
						"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
							self::PAYLOAD_MAXIMUM_SIZE . " bytes. The message text can not be auto-adjusted."
					);
				}
			}
			else
			{
				throw new Exception(
					"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
						self::PAYLOAD_MAXIMUM_SIZE . " bytes"
				);
			}
		}

		return $sJSONPayload;
	}

	public function getBatch()
	{
		$arTokens = $this->getRecipients();
		$sPayload = $this->getPayload();
		$nPayloadLength = CUtil::BinStrlen($sPayload);
		$totalBatch = "";
		for ($i = 0; $i < count($arTokens); $i++)
		{
			$sDeviceToken = $arTokens[$i];
			$nTokenLength = strlen($sDeviceToken);

			$sRet = pack('CNNnH*', 1, $this->getCustomIdentifier(), $this->getExpiry() > 0 ? time() + $this->getExpiry() : 0, 32, $sDeviceToken);
			$sRet .= pack('n', $nPayloadLength);
			$sRet .= $sPayload;
			if (strlen($totalBatch) > 0)
				$totalBatch .= ";";
			$totalBatch .= base64_encode($sRet);
		}

		return $totalBatch;
	}
}

class CApplePush extends CPushService
{
	/**
	 * Gets the batch for Apple push notification service
	 *
	 * @param array $messageData
	 *
	 * @return bool|string
	 */
	public function getBatch($messageData = Array())
	{
		$arGroupedMessages = self::getGroupedByServiceMode($messageData);
		if (is_array($arGroupedMessages) && count($arGroupedMessages) <= 0)
			return false;

		$batch = $this->getProductionBatch($arGroupedMessages["PRODUCTION"]);
		$batch .= $this->getSandboxBatch($arGroupedMessages["SANDBOX"]);

		if (strlen($batch) == 0)
			return $batch;

		return $batch;
	}

	/**
	 * Returns message instance
	 *
	 * @param $token
	 *
	 * @return CAppleMessage
	 */
	public static function getMessageInstance($token)
	{
		return new CAppleMessage($token);
	}

	/**
	 * Gets batch  with ;1; modifier only for sandbox server
	 *
	 * @param $appMessages
	 *
	 * @return string
	 */
	public function getSandboxBatch($appMessages)
	{
		return $this->getBatchWithModifier($appMessages, ";1;");
	}

	/**
	 * Gets batch  with ;2; modifier only for production server
	 *
	 * @param $appMessages
	 *
	 * @return string
	 */
	public function getProductionBatch($appMessages)
	{
		return $this->getBatchWithModifier($appMessages, ";2;");
	}


}
