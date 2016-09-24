<?php

abstract class CPushService
{
	protected $allowEmptyMessage = true;
	const DEFAULT_EXPIRY = 14400;

	protected function getBatchWithModifier($appMessages = Array(), $modifier = "")
	{
		global $APPLICATION;
		$batch = "";
		if (!is_array($appMessages) || count($appMessages) <= 0)
		{
			return $batch;
		}
		foreach ($appMessages as $appID => $arMessages)
		{
			$appModifier = ";tkey=" . $appID . ";";
			foreach ($arMessages as $token => $messages)
			{
				if (!count($messages))
				{
					continue;
				}
				$mess = 0;
				$messCount = count($messages);
				while ($mess < $messCount)
				{
					/**
					 * @var CPushMessage $message ;
					 */

					$messageArray = $messages[$mess];
					if (!$this->allowEmptyMessage && strlen(trim($messageArray["MESSAGE"])) <= 0)
					{
						$mess++;
						continue;
					}

					$message = static::getMessageInstance($token);
					$id = rand(1, 10000);
					$message->setCustomIdentifier($id);
					if ("UTF-8" != toupper(SITE_CHARSET))
					{
						$text = $APPLICATION->ConvertCharset($messageArray["MESSAGE"], SITE_CHARSET, "utf-8");
					}
					else
					{
						$text = $messageArray["MESSAGE"];
					}
					$message->setSound('');
					$message->setText($text);
					$message->setTitle($messageArray["TITLE"]);
					if (strlen($text) > 0)
					{
						$message->setSound(
							(strlen($messageArray["SOUND"]) > 0)
								? $messageArray["SOUND"]
								: "default"
						);
					}

					if ($messages[$mess]["CATEGORY"])
					{
						$message->setCategory($messages[$mess]["CATEGORY"]);
					}

					if (array_key_exists("EXPIRY", $messageArray))
					{
						$expiry = intval($messageArray["EXPIRY"]);
						$message->setExpiry((intval($expiry) > 0)
							? intval($expiry)
							: self::DEFAULT_EXPIRY
						);
					}


					if ($messageArray["PARAMS"])
					{
						$message->setCustomProperty(
							'params',
							(is_array($messageArray["PARAMS"]))
								? json_encode($messageArray["PARAMS"])
								: $messageArray["PARAMS"]
						);
					}


					if ($messageArray["ADVANCED_PARAMS"] && is_array($messageArray["ADVANCED_PARAMS"]))
					{
//						$messageArray["ADVANCED_PARAMS"] = array_change_key_case($messageArray["ADVANCED_PARAMS"], CASE_LOWER);
						foreach ($messageArray["ADVANCED_PARAMS"] as $param => $value)
						{
							$message->setCustomProperty($param, $value);
						}
					}

					$message->setCustomProperty('target', md5($messages[$mess]["USER_ID"] . CMain::GetServerUniqID()));

					$badge = intval($messages[$mess]["BADGE"]);
					if (array_key_exists("BADGE", $messages[$mess]) && $badge >= 0)
					{
						$message->setBadge($badge);
					}


					if (strlen($batch) > 0)
					{
						$batch .= ";";
					}

					$messageBatch = $message->getBatch();
					if($messageBatch && strlen($messageBatch)>0)
					{
						$batch .= $messageBatch;
					}

					$mess++;
				}
			}
			$batch = $appModifier . $batch;
		}

		if (strlen($batch) == 0)
		{
			return $batch;
		}

		return $modifier . $batch;
	}

	protected static function getGroupedByServiceMode($arMessages)
	{
		$groupedMessages = array();
		foreach ($arMessages as $keyToken => $messTokenData)
		{
			$count = count($messTokenData["messages"]);
			for ($i = 0; $i < $count; $i++)
			{
				$mode = $arMessages[$keyToken]["mode"];
				$mess = $messTokenData["messages"][$i];
				$app_id = $mess["APP_ID"];
				$groupedMessages[$mode][$app_id][$keyToken][] = $mess;
			}
		}

		return $groupedMessages;
	}

	protected static function getGroupedByAppID($arMessages)
	{
		$groupedMessages = array();
		foreach ($arMessages as $keyToken => $messTokenData)
		{
			$count = count($messTokenData["messages"]);
			for ($i = 0; $i < $count; $i++)
			{
				$mode = $arMessages[$keyToken]["mode"];
				$mess = $messTokenData["messages"][$i];
				$app_id = $mess["APP_ID"];
				$groupedMessages[$app_id][$keyToken][] = $mess;
			}
		}

		return $groupedMessages;
	}

	abstract function getMessageInstance($token);

	abstract function getBatch($messages);

}