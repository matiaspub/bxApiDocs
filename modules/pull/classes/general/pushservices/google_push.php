<?

class CGoogleMessage extends CPushMessage
{
	public function __construct($sDeviceToken = null)
	{
		if (isset($sDeviceToken))
		{
			$this->addRecipient($sDeviceToken);
		}
	}

	/**
	 * Returns batch of the message
	 * @return string
	 */
	public function getBatch()
	{

		$data = array(
			"data" => array(
				'contentTitle' => $this->title,
				"contentText" => $this->text,
				"messageParams" => $this->customProperties,
				"category" => $this->getCategory()
			),
			"time_to_live" => $this->expiryValue,
			"registration_ids" => $this->deviceTokens
		);

		$data = CPushManager::_MakeJson($data, "", true);
		$batch = "Content-type: application/json\r\n";
		$batch .= "Content-length: " . CUtil::BinStrlen($data) . "\r\n";
		$batch .= "\r\n";
		$batch .= $data;

		return base64_encode($batch);
	}
}

class CGooglePush extends CPushService
{
	public function __construct()
	{
		$this->allowEmptyMessage = false;
	}

	/**
	 * Returns the final batch for the Android's push notification
	 *
	 * @param array $messageData
	 *
	 * @return bool|string
	 */
	public function getBatch($messageData = Array())
	{
		$arGroupedMessages = self::getGroupedByAppID($messageData);
		if (is_array($arGroupedMessages) && count($arGroupedMessages) <= 0)
		{
			return false;
		}

		$batch = $this->getBatchWithModifier($arGroupedMessages, ";3;");

		if (strlen($batch) == 0)
		{
			return $batch;
		}

		return $batch;
	}

	/**
	 * Gets message instance
	 * @param $token
	 *
	 * @return CGoogleMessage
	 */
	public static function getMessageInstance($token)
	{
		return new CGoogleMessage($token);
	}
}

?>