<?

class CIMMessageParam
{
	public static function Set($messageId, $params = Array())
	{
		global $DB;
		$messageId = intval($messageId);

		if(!(is_array($params) || is_null($params)) || $messageId <= 0)
			return false;

		if (is_null($params) || count($params) <= 0)
		{
			$DB->Query("
				DELETE FROM b_im_message_param
				WHERE
				MESSAGE_ID = ".$messageId."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);

			return true;
		}
		$default = self::GetDefault();

		$arToDelete = array();
		foreach ($params as $key => $val)
		{
			if (isset($default[$key]) && $default[$key] == $val)
			{
				$sqlName = "'".$DB->ForSQL($key, 100)."'";
				$arToDelete[$sqlName] = "
					DELETE FROM b_im_message_param
					WHERE
					MESSAGE_ID = ".$messageId."
					AND PARAM_NAME = ".$sqlName."
				";
			}
		}

		$arToInsert = array();
		foreach($params as $k1 => $v1)
		{
			$name = trim($k1);
			if(strlen($name))
			{
				$sqlName = "'".$DB->ForSQL($name, 100)."'";

				if(!is_array($v1))
					$v1 = array($v1);

				if (empty($v1))
				{
					$arToDelete[$sqlName] = "
						DELETE FROM b_im_message_param
						WHERE
						MESSAGE_ID = ".$messageId."
						AND PARAM_NAME = ".$sqlName."
					";
				}
				else
				{
					foreach($v1 as $v2)
					{
						$value = trim($v2);
						if(strlen($value))
						{
							$sqlValue = "'".$DB->ForSQL($value, 100)."'";
							$key = md5($sqlName).md5($sqlValue);

							$arToInsert[$key] = "
								INSERT INTO b_im_message_param
								(MESSAGE_ID, PARAM_NAME, PARAM_VALUE)
								VALUES
								(".$messageId.", ".$sqlName.", ".$sqlValue.")
							";
						}
					}
				}
			}
		}

		if(!empty($arToInsert))
		{
			$rs = $DB->Query("
				SELECT PARAM_NAME, PARAM_VALUE
				FROM b_im_message_param
				WHERE MESSAGE_ID = ".$messageId."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $rs->Fetch())
			{
				$sqlName = "'".$DB->ForSQL($ar["PARAM_NAME"], 100)."'";
				$sqlValue = "'".$DB->ForSQL($ar["PARAM_VALUE"], 100)."'";
				$key = md5($sqlName).md5($sqlValue);

				if(array_key_exists($key, $arToInsert))
				{
					unset($arToInsert[$key]);
				}
				else if (isset($params[$ar["PARAM_NAME"]]))
				{
					$DB->Query($s = "
						DELETE FROM b_im_message_param
						WHERE
						MESSAGE_ID = ".$messageId."
						AND PARAM_NAME = ".$sqlName."
						AND PARAM_VALUE = ".$sqlValue."
					", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}
		}
		foreach($arToInsert as $sql)
			$DB->Query($sql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach($arToDelete as $sql)
			$DB->Query($sql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function Get($messageId, $params = false)
	{
		global $DB;

		$arResult = array();
		if (is_array($messageId))
		{
			if (!empty($messageId))
			{
				foreach ($messageId as $key => $value)
				{
					$messageId[$key] = intval($value);
					$arResult[$messageId[$key]] = Array();
				}
			}
			else
			{
				return $arResult;
			}
		}
		else
		{
			$messageId = intval($messageId);
			$arResult[$messageId] = Array();
			if ($messageId <= 0)
			{
				return false;
			}
		}

		if (is_array($messageId))
			$whereMessageId = "MESSAGE_ID IN (".implode(',',$messageId).")";
		else
			$whereMessageId = "MESSAGE_ID = ".$messageId;

		$rs = $DB->Query("
			SELECT MESSAGE_ID, PARAM_NAME, PARAM_VALUE
			FROM b_im_message_param
			WHERE ".$whereMessageId."
			".($params && strlen($params) > 0 ? " AND PARAM_NAME = '".$DB->ForSQL($params)."'" : "")."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			if (!isset($arResult[$ar["MESSAGE_ID"]][$ar["PARAM_NAME"]]))
			{
				$arResult[$ar["MESSAGE_ID"]][$ar["PARAM_NAME"]] = array();
			}
			$arResult[$ar["MESSAGE_ID"]][$ar["PARAM_NAME"]][] = $ar["PARAM_VALUE"];
		}
		if (is_array($messageId))
		{
			foreach ($messageId as $key)
			{
				$arResult[$key] = self::PrepareValues($arResult[$key]);
			}
			return $arResult;
		}
		else
		{
			return self::PrepareValues($arResult[$messageId]);
		}
	}

	public static function PrepareValues($value)
	{
		$arValues = Array();

		$arDefault = self::GetDefault();
		foreach($arDefault as $key => $default)
		{
			if (in_array($key, Array('IS_DELETED', 'IS_EDITED')))
			{
				$arValues[$key] = in_array($value[$key][0], Array('Y', 'N'))? $value[$key][0]: $default;
			}
			else if ($key == 'FILE_ID' || $key == 'LIKE')
			{
				if (is_array($value[$key]) && !empty($value[$key]))
				{
					foreach ($value[$key] as $k => $v)
					{
						$arValues[$key][$k] = intval($v);
					}
				}
				else if (!is_array($value[$key]) && intval($value[$key]) > 0)
				{
					$arValues[$key] = intval($value[$key]);
				}
				else
				{
					$arValues[$key] = $default;
				}
			}
			else
			{
				$arValues[$key] = $default;
			}
		}

		return $arValues;
	}

	public static function GetDefault()
	{
		$arDefault = Array(
			'LIKE' => Array(),
			'FILE_ID' => Array(),
			'IS_DELETED' => 'N',
			'IS_EDITED' => 'N',
		);

		return $arDefault;
	}
}
?>