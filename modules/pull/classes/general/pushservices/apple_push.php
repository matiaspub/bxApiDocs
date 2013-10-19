<?php

class CAppleMessage
{
	const PAYLOAD_MAXIMUM_SIZE = 256; /**< @type integer The maximum size allowed for a notification payload. */
	const APPLE_RESERVED_NAMESPACE = 'aps'; /**< @type string The Apple-reserved aps namespace. */

	protected $_bAutoAdjustLongPayload = true; /**< @type boolean If the JSON payload is longer than maximum allowed size, shorts message text. */

	protected $_aDeviceTokens = array(); /**< @type array Recipients device tokens. */

	protected $_sText; /**< @type string Alert message to display to the user. */
	protected $_nBadge; /**< @type integer Number to badge the application icon with. */
	protected $_sSound; /**< @type string Sound to play. */

	protected $_aCustomProperties; /**< @type mixed Custom properties container. */

	protected $_nExpiryValue = 604800; /**< @type integer That message will expire in 604800 seconds (86400 * 7, 7 days) if not successful delivered. */

	protected $_mCustomIdentifier; /**< @type mixed Custom message identifier. */

	public function __construct($sDeviceToken = null)
	{
		if (isset($sDeviceToken)) {
			$this->addRecipient($sDeviceToken);
		}
	}

	public function addRecipient($sDeviceToken)
	{
		if (!preg_match('~^[a-f0-9]{64}$~i', $sDeviceToken)) {
			throw new Exception(
				"Invalid device token '{$sDeviceToken}'"
			);
		}
		$this->_aDeviceTokens[] = $sDeviceToken;
	}

	public function getRecipient($nRecipient = 0)
	{
		if (!isset($this->_aDeviceTokens[$nRecipient])) {
			throw new Exception(
				"No recipient at index '{$nRecipient}'"
			);
		}
		return $this->_aDeviceTokens[$nRecipient];
	}

	public function getRecipientsNumber()
	{
		return count($this->_aDeviceTokens);
	}

	public function getRecipients()
	{
		return $this->_aDeviceTokens;
	}

	public function setText($sText)
	{
		$this->_sText = $sText;
	}

	public function getText()
	{
		return $this->_sText;
	}

	public function setBadge($nBadge)
	{
		if (!is_int($nBadge)) {
			throw new Exception(
				"Invalid badge number '{$nBadge}'"
			);
		}
		$this->_nBadge = $nBadge;
	}

	public function getBadge()
	{
		return $this->_nBadge;
	}

	public function setSound($sSound = 'default')
	{
		$this->_sSound = $sSound;
	}

	public function getSound()
	{
		return $this->_sSound;
	}

	public function setCustomProperty($sName, $mValue)
	{
		if ($sName == self::APPLE_RESERVED_NAMESPACE) {
			throw new Exception(
				"Property name '" . self::APPLE_RESERVED_NAMESPACE . "' can not be used for custom property."
			);
		}
		$this->_aCustomProperties[trim($sName)] = $mValue;
	}

	public function getCustomPropertyName()
	{
		if (!is_array($this->_aCustomProperties)) {
			return;
		}
		$aKeys = array_keys($this->_aCustomProperties);
		return $aKeys[0];
	}

	public function getCustomPropertyValue()
	{
		if (!is_array($this->_aCustomProperties)) {
			return;
		}
		$aKeys = array_keys($this->_aCustomProperties);
		return $this->_aCustomProperties[$aKeys[0]];
	}

	public function getCustomPropertyNames()
	{
		if (!is_array($this->_aCustomProperties)) {
			return array();
		}
		return array_keys($this->_aCustomProperties);
	}

	public function getCustomProperty($sName)
	{
		if (!array_key_exists($sName, $this->_aCustomProperties)) {
			throw new Exception(
				"No property exists with the specified name '{$sName}'."
			);
		}
		return $this->_aCustomProperties[$sName];
	}

	public function setAutoAdjustLongPayload($bAutoAdjust)
	{
		$this->_bAutoAdjustLongPayload = (boolean)$bAutoAdjust;
	}

	public function getAutoAdjustLongPayload()
	{
		return $this->_bAutoAdjustLongPayload;
	}

	public function __toString()
	{
		try {
			$sJSONPayload = $this->getPayload();
		} catch (Exception $e) {
			$sJSONPayload = '';
		}
		return $sJSONPayload;
	}

	protected function _getPayload()
	{
		$aPayload[self::APPLE_RESERVED_NAMESPACE] = array();

		if (isset($this->_sText)) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['alert'] = (string)$this->_sText;
		}
		if (isset($this->_nBadge) && $this->_nBadge >= 0) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['badge'] = (int)$this->_nBadge;
		}
		if (isset($this->_sSound) && strlen($this->_sSound) > 0) {
			$aPayload[self::APPLE_RESERVED_NAMESPACE]['sound'] = (string)$this->_sSound;
		}

		if (is_array($this->_aCustomProperties)) {
			foreach($this->_aCustomProperties as $sPropertyName => $mPropertyValue) {
				$aPayload[$sPropertyName] = $mPropertyValue;
			}
		}

		return $aPayload;
	}

	public function getBatch()
	{
		$arTokens = $this->getRecipients();
		$sPayload = $this->getPayload();
		$nPayloadLength = CUtil::BinStrlen($sPayload);
		$totalBatch = "";
		for ($i=0; $i < count($arTokens); $i++)
		{
			$sDeviceToken = $arTokens[$i];
			$nTokenLength = strlen($sDeviceToken);

			$sRet  = pack('CNNnH*', 1, $this->getCustomIdentifier(), $this->getExpiry() > 0 ? time() + $this->getExpiry() : 0, 32, $sDeviceToken);
			$sRet .= pack('n', $nPayloadLength);
			$sRet .= $sPayload;
			if(strlen($totalBatch) >0)
				$totalBatch .=";";
			$totalBatch.=base64_encode($sRet);
		}

		return $totalBatch;
	}

	protected function _MakeJson($arData, $bWS, $bSkipTilda)
	{
		static $aSearch = array("\r", "\n");

		if(is_array($arData))
		{

			if($arData == array_values($arData))
			{

				foreach($arData as $key => $value)
				{
					if(is_array($value))
					{
						$arData[$key] = self::_MakeJson($value, $bWS, $bSkipTilda);
					}
					elseif(is_bool($value))
					{
						if($value === true)
							$arData[$key] = "true";
						else
							$arData[$key] = "false";
					}
					elseif(is_integer($value))
					{
						$arData[$key] = $value;
					}
					else
					{
						if(preg_match("#['\"\\n\\r<\\\\]#", $value))
							$arData[$key] = "\"".CUtil::JSEscape($value)."\"";
						else
							$arData[$key] = "\"".$value."\"";
					}
				}
				return '['.implode(',', $arData).']';
			}

			$sWS = ','.($bWS ? "\n" : '');
			$res = ($bWS ? "\n" : '').'{';
			$first = true;

			foreach($arData as $key => $value)
			{
				if ($bSkipTilda && substr($key, 0, 1) == '~')
					continue;

				if($first)
					$first = false;
				else
					$res .= $sWS;

				if(preg_match("#['\"\\n\\r<\\\\]#", $key))
					$res .= "\"".str_replace($aSearch, '', CUtil::addslashes($key))."\":";
				else
					$res .= "\"".$key."\":";

				if(is_array($value))
				{
					$res .= self::_MakeJson($value, $bWS, $bSkipTilda);
				}
				elseif(is_integer($value))
				{
					$res .= $value;
				}
				elseif(is_bool($value))
				{
					if($value === true)
						$res .= "true";
					else
						$res .= "false";
				}
				else
				{
					if(preg_match("#['\"\\n\\r<\\\\]#", $value))
						$res .= "\"".CUtil::JSEscape($value)."\"";
					else
						$res .= "\"".$value."\"";
				}
			}
			$res .= ($bWS ? "\n" : '').'}';

			return $res;
		}
		elseif(is_bool($arData))
		{
			if($arData === true)
				return 'true';
			else
				return 'false';
		}
		else
		{
			if(preg_match("#['\"\\n\\r<\\\\]#", $arData))
				return "\"".CUtil::JSEscape($arData)."'";
			else
				return "\"".$arData."\"";
		}
	}

	public function getPayload()
	{
		$sJSONPayload = str_replace(
			'"' . self::APPLE_RESERVED_NAMESPACE . '":[]',
			'"' . self::APPLE_RESERVED_NAMESPACE . '":{}',
			$this->_MakeJson($this->_getPayload(), "", false)
		);
		$nJSONPayloadLen = CUtil::BinStrlen($sJSONPayload);

		if ($nJSONPayloadLen > self::PAYLOAD_MAXIMUM_SIZE) {
			if ($this->_bAutoAdjustLongPayload) {
				$nMaxTextLen = $nTextLen = CUtil::BinStrlen($this->_sText) - ($nJSONPayloadLen - self::PAYLOAD_MAXIMUM_SIZE);
				if ($nMaxTextLen > 0) {
					while (CUtil::BinStrlen($this->_sText = CUtil::BinSubstr($this->_sText, 0, --$nTextLen)) > $nMaxTextLen);
						return $this->getPayload();
				} else {
					throw new Exception(
						"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
						self::PAYLOAD_MAXIMUM_SIZE . " bytes. The message text can not be auto-adjusted."
					);
				}
			} else {
				throw new Exception(
					"JSON Payload is too long: {$nJSONPayloadLen} bytes. Maximum size is " .
					self::PAYLOAD_MAXIMUM_SIZE . " bytes"
				);
			}
		}

		return $sJSONPayload;
	}

	public function setExpiry($nExpiryValue)
	{
		if (!is_int($nExpiryValue)) {
			throw new Exception(
				"Invalid seconds number '{$nExpiryValue}'"
			);
		}
		$this->_nExpiryValue = $nExpiryValue;
	}

	public function getExpiry()
	{
		return $this->_nExpiryValue;
	}

	public function setCustomIdentifier($mCustomIdentifier)
	{
		$this->_mCustomIdentifier = $mCustomIdentifier;
	}

	public function getCustomIdentifier()
	{
		return $this->_mCustomIdentifier;
	}
}

class CApplePush
{
	static public function GetBatch($arMessages = Array())
	{
		global $APPLICATION;
		if(is_array($arMessages) && count($arMessages)<=0)
			return false;
		$batch_modificator = ";2;";
		if(defined('PULL_PUSH_SANDBOX') && PULL_PUSH_SANDBOX)
			$batch_modificator = ";1;";

		$batch = "";
		foreach($arMessages as $token=>$messages)
		{
			if(!count($messages))
				continue;
			$mess = 0;
			$messCount = count($messages);
			while($mess<$messCount)
			{
				$message = new CAppleMessage($token);
				$id = rand(1,10000);
				$message->setCustomIdentifier($id);
				//$message->setAutoAdjustLongPayload(false);
				if ("UTF-8"!=toupper(SITE_CHARSET))
					$text = $APPLICATION->ConvertCharset($messages[$mess]["MESSAGE"], SITE_CHARSET, "utf-8");
				else
					$text = $messages[$mess]["MESSAGE"];
				$message->setText($text);
				if (strlen($text) > 0)
					$message->setSound();
				else
					$message->setSound('');

				if($messages[$mess]["PARAMS"])
				{
					$params = $messages[$mess]["PARAMS"];
					if(is_array($messages[$mess]["PARAMS"]))
						$params = json_encode($messages[$mess]["PARAMS"]);
					$message->setCustomProperty('params', $params);
				}
				$message->setExpiry(14400);
				$badge = intval($messages[$mess]["BADGE"]);
				if(array_key_exists("BADGE", $messages[$mess]) && $badge>=0)
					$message->setBadge($badge);

				if(strlen($batch) > 0)
					$batch.= ";";
				$batch.= $message->getBatch();
				$mess++;
			}
		}

		if(strlen($batch) == 0)
			return $batch;
		return  $batch_modificator.$batch;
	}
}
