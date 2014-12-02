<?php

abstract class CPushService
{
	protected $allowEmptyMessage = true;
	const DEFAULT_EXPIRY = 14400;

	protected function getBatchWithModifier($appMessages = Array(), $modifier = "")
	{
		global $APPLICATION;
		$batch = "";
		if(!is_array($appMessages) || count($appMessages) <= 0)
			return $batch;
		foreach ($appMessages as $appID => $arMessages)
		{
			$appModifier = ";tkey=" . $appID . ";";
			foreach ($arMessages as $token => $messages)
			{
				if (!count($messages))
					continue;
				$mess = 0;
				$messCount = count($messages);
				while ($mess < $messCount)
				{
					/**
					 * @var CPushMessage $message;
					 */

					if (!$this->allowEmptyMessage && strlen(trim($messages[$mess]["MESSAGE"])) <= 0)
					{
						$mess++;
						continue;
					}

					$message = static::getMessageInstance($token);
					$id = rand(1, 10000);
					$message->setCustomIdentifier($id);
					if ("UTF-8" != toupper(SITE_CHARSET))
						$text = $APPLICATION->ConvertCharset($messages[$mess]["MESSAGE"], SITE_CHARSET, "utf-8");
					else
						$text = $messages[$mess]["MESSAGE"];
					$message->setText($text);
					$message->setTitle($messages[$mess]["TITLE"]);
					if (strlen($text) > 0)
					{
						if(strlen($messages[$mess]["SOUND"])>0)
							$message->setSound($messages[$mess]["SOUND"]);
					}
					else
					{
						$message->setSound('');
					}

					if ($messages[$mess]["PARAMS"])
					{
						$params = $messages[$mess]["PARAMS"];
						if (is_array($messages[$mess]["PARAMS"]))
							$params = json_encode($messages[$mess]["PARAMS"]);
						$message->setCustomProperty('params', $params);
					}

					$message->setCustomProperty('target', md5($messages[$mess]["USER_ID"] . CMain::GetServerUniqID()));

					if(array_key_exists("EXPIRY", $messages[$mess]))
					{
						$expiry = $messages[$mess]["EXPIRY"];
						if ($expiry === 0 || $expiry === "0")
						{
							$message->setExpiry(0);
						}
						else
						{
							$message->setExpiry((intval($expiry)>0)
													? intval($expiry)
													:self::DEFAULT_EXPIRY
							);
						}
					}

					$badge = intval($messages[$mess]["BADGE"]);
					if (array_key_exists("BADGE", $messages[$mess]) && $badge >= 0)
						$message->setBadge($badge);

					if (strlen($batch) > 0)
						$batch .= ";";

					$batch .= $message->getBatch();
					$mess++;
				}
			}
			$batch = $appModifier . $batch;
		}

		if (strlen($batch) == 0)
			return $batch;

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
	abstract function getBatch();

}