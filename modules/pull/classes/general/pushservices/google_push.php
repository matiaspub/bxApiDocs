<?

class CGoogleMessage
{
	protected $_aDeviceTokens = array();
	protected $_sText;
	protected $_nBadge;
	protected $_sSound;
	protected $_nExpiryValue = 7200;

	protected $_mCustomIdentifier;
	protected $_sTitle;
	public $_sound;
	public $_sParams = array();

	public function __construct($sDeviceToken = null)
	{
		if (isset($sDeviceToken)) {
			$this->addRecipient($sDeviceToken);
		}
	}

	public function addRecipient($sDeviceToken)
	{
		$this->_aDeviceTokens[] = $sDeviceToken;
	}

	public function setText($sText)
	{
		$this->_sText = $sText;
	}

	public function setTitle($sTitle)
	{
		$this->_sTitle = $sTitle;
	}

	public function setCustomProperty($string1, $params)
	{
		$this->_sParams[$string1] = $params;
	}

	public function setSound($sound = true)
	{
		$this->_sound = true;
	}

	public function setExpiry($nExpiryValue)
	{
		if (is_int($nExpiryValue))
			$this->_nExpiryValue = $nExpiryValue;
	}

	public function getBatch()
	{

		$data = array(
			"data" => array(
					'contentTitle' => $this->_sTitle,
					"contentText" => $this->_sText,
					"messageParams"=>$this->_sParams
				),
			"time_to_live" => $this->_nExpiryValue,
			"registration_ids" => $this->_aDeviceTokens
		);

		$data = CPushManager::_MakeJson($data,"",true);
		$batch = "Content-type: application/json\r\n";
		$batch.= "Content-length: " . CUtil::BinStrlen($data) . "\r\n";
		$batch.= "\r\n";
		$batch.= $data;

		return base64_encode($batch);
	}

}

class CGooglePush
{
	static public function GetBatch($arMessages = Array())
	{
		global $APPLICATION;
		if(is_array($arMessages) && count($arMessages)<=0)
		return false;
		$batch_modificator = ";3;";

		$batch = "";
		foreach($arMessages as $token=>$messages)
		{
			if(!count($messages))
				continue;
			$mess = 0;
			$messCount = count($messages);
			while($mess<$messCount)
			{
				if (strlen(trim($messages[$mess]["MESSAGE"])) <= 0)
				{
					$mess++;
					continue;
				}

				if ("UTF-8"!=toupper(SITE_CHARSET))
					$messages[$mess] = $APPLICATION->ConvertCharsetArray($messages[$mess], SITE_CHARSET, "utf-8");

				$text = $messages[$mess]["MESSAGE"];

				$message = new CGoogleMessage($token);
				$message->setText($text);
				if($messages[$mess]["TITLE"])
					$message->setTitle($messages[$mess]["TITLE"]);
				$message->setSound();
				$message->setExpiry(14400);

				if($messages[$mess]["PARAMS"])
				{
					$params = $messages[$mess]["PARAMS"];
					if(is_array($messages[$mess]["PARAMS"]))
						$params = json_encode($messages[$mess]["PARAMS"]);
					$message->setCustomProperty('params', $params);
				}

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
?>