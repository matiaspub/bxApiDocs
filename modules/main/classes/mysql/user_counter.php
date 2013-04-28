<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/user_counter.php");

class CUserCounter extends CAllUserCounter
{
	
	/**
	 * <p>Функция позволяет задать для счётчика произвольное число.</p>
	 *
	 *
	 *
	 *
	 * @param user_i $d  11.5.2
	 *
	 *
	 *
	 * @param cod $e  11.5.2
	 *
	 *
	 *
	 * @param valu $e  11.5.2
	 *
	 *
	 *
	 * @param site_i $d = SITE_ID 11.5.2
	 *
	 *
	 *
	 * @param ta $g = "" 11.5.6
	 *
	 *
	 *
	 * @return mixed <p>Возвращает <i>true</i>, если действие успешно, <i>false</i> - если нет.</p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * CUserCounter::Set($USER-&gt;GetID(), 'code2', 100500);
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/set.php
	 * @author Bitrix
	 */
	public static function Set($user_id, $code, $value, $site_id = SITE_ID, $tag = '')
	{
		global $DB, $CACHE_MANAGER;

		$value = intval($value);
		$user_id = intval($user_id);
		if ($user_id <= 0 || strlen($code) <= 0)
			return false;

		$rs = $DB->Query("
			SELECT CNT FROM b_user_counter
			WHERE USER_ID = ".$user_id."
			AND SITE_ID = '".$DB->ForSQL($site_id)."'
			AND CODE = '".$DB->ForSQL($code)."'
		");

		if ($rs->Fetch())
		{
			$ssql = "";
			if ($tag != "")
				$ssql = ", TAG = '".$DB->ForSQL($tag)."'";

			$DB->Query("
				UPDATE b_user_counter SET
				CNT = ".$value." ".$ssql."
				WHERE USER_ID = ".$user_id."
				AND SITE_ID = '".$DB->ForSQL($site_id)."'
				AND CODE = '".$DB->ForSQL($code)."'
			");
		}
		else
		{
			$DB->Query("
				INSERT INTO b_user_counter
				(CNT, USER_ID, SITE_ID, CODE, TAG)
				VALUES
				(".$value.", ".$user_id.", '".$DB->ForSQL($site_id)."', '".$DB->ForSQL($code)."', '".$DB->ForSQL($tag)."')
			", true);
		}

		if (self::$counters && self::$counters[$user_id])
		{
			if ($site_id == '**')
			{
				foreach(self::$counters[$user_id] as $key => $tmp)
				{
					self::$counters[$user_id][$key][$code] = $value;
				}
			}
			else
			{
				if (!isset(self::$counters[$user_id][$site_id]))
					self::$counters[$user_id][$site_id] = array();

				self::$counters[$user_id][$site_id][$code] = $value;
			}
		}

		$CACHE_MANAGER->Clean("user_counter".$user_id, "user_counter");

		return true;
	}

	
	/**
	 * <p>Функция позволяет увеличить счётчик пользователя на 1.</p>
	 *
	 *
	 *
	 *
	 * @param user_i $d  Идентификатор пользователя
	 *
	 *
	 *
	 * @param cod $e  Код счётчика
	 *
	 *
	 *
	 * @param site_i $d = SITE_ID Идентификатор сайта, необязательный параметр. По умолчанию
	 * подставляется текущий сайт.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает <i>true</i>, если действие успешно, <i>false</i> - если нет.</p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * CUserCounter::Increment($USER-&gt;GetID(), 'code1');
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/increment.php
	 * @author Bitrix
	 */
	public static function Increment($user_id, $code, $site_id = SITE_ID)
	{
		global $DB, $CACHE_MANAGER;

		$user_id = intval($user_id);
		if ($user_id <= 0 || strlen($code) <= 0)
			return false;

		$strSQL = "
			INSERT INTO b_user_counter (USER_ID, CNT, SITE_ID, CODE)
			VALUES (".$user_id.", 1, '".$DB->ForSQL($site_id)."', '".$DB->ForSQL($code)."')
			ON DUPLICATE KEY UPDATE CNT = CNT + 1";
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (self::$counters && self::$counters[$user_id])
		{
			if ($site_id == '**')
			{
				foreach(self::$counters[$user_id] as $key => $tmp)
				{
					if (isset(self::$counters[$user_id][$key][$code]))
						self::$counters[$user_id][$key][$code]++;
					else
						self::$counters[$user_id][$key][$code] = 1;
				}
			}
			else
			{
				if (!isset(self::$counters[$user_id][$site_id]))
					self::$counters[$user_id][$site_id] = array();

				if (isset(self::$counters[$user_id][$site_id][$code]))
					self::$counters[$user_id][$site_id][$code]++;
				else
					self::$counters[$user_id][$site_id][$code] = 1;
			}
		}

		$CACHE_MANAGER->Clean("user_counter".$user_id, "user_counter");

		return true;
	}

	
	/**
	 * <p>Функция осуществляет уменьшение счетчика на единицу.</p>
	 *
	 *
	 *
	 *
	 * @param user_i $d  Описание параметра
	 *
	 *
	 *
	 * @param cod $e  Описание необязательного параметра
	 *
	 *
	 *
	 * @param site_i $d = SITE_ID Необязательный. По умолчанию равен SITE_ID.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает <i>true</i>, если действие успешно, <i>false</i> - если нет.</p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * CUserCounter::Decrement($USER-&gt;GetID(), 'code1');
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/decremen.php
	 * @author Bitrix
	 */
	public static function Decrement($user_id, $code, $site_id = SITE_ID)
	{
		global $DB, $CACHE_MANAGER;

		$user_id = intval($user_id);
		if ($user_id <= 0 || strlen($code) <= 0)
			return false;

		$strSQL = "
			INSERT INTO b_user_counter (USER_ID, CNT, SITE_ID, CODE)
			VALUES (".$user_id.", -1, '".$DB->ForSQL($site_id)."', '".$DB->ForSQL($code)."')
			ON DUPLICATE KEY UPDATE CNT = CNT - 1";
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (self::$counters && self::$counters[$user_id])
		{
			if ($site_id == '**')
			{
				foreach(self::$counters[$user_id] as $key => $tmp)
				{
					if (isset(self::$counters[$user_id][$key][$code]))
						self::$counters[$user_id][$key][$code]--;
					else
						self::$counters[$user_id][$key][$code] = -1;
				}
			}
			else
			{
				if (!isset(self::$counters[$user_id][$site_id]))
					self::$counters[$user_id][$site_id] = array();

				if (isset(self::$counters[$user_id][$site_id][$code]))
					self::$counters[$user_id][$site_id][$code]--;
				else
					self::$counters[$user_id][$site_id][$code] = -1;
			}
		}

		$CACHE_MANAGER->Clean("user_counter".$user_id, "user_counter");

		return true;
	}

	public static function IncrementWithSelect($sub_select)
	{
		global $DB, $CACHE_MANAGER;

		if (strlen($sub_select) > 0)
		{
			$strSQL = "
				INSERT INTO b_user_counter (USER_ID, CNT, SITE_ID, CODE) (".$sub_select.")
				ON DUPLICATE KEY UPDATE CNT = CNT + 1
			";
			$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			self::$counters = false;
			$CACHE_MANAGER->CleanDir("user_counter");
		}
	}

	
	/**
	 * <p>Функция обнуляет данные счётчика.</p> <p><b>Примечание</b>: возможное примечание.</p>
	 *
	 *
	 *
	 *
	 * @param user_i $d  Идентификатор пользователя
	 *
	 *
	 *
	 * @param cod $e  Код счётчика
	 *
	 *
	 *
	 * @param site_i $d = SITE_ID Идентификатор сайта, необязательный параметр. По умолчанию
	 * подставляется текущий сайт.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает <i>true</i>, если действие успешно, <i>false</i> - если нет.</p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * CUserCounter::Clear($USER-&gt;GetID(), "code3");
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cusercounter/clear.php
	 * @author Bitrix
	 */
	public static function Clear($user_id, $code, $site_id = SITE_ID)
	{
		global $DB, $CACHE_MANAGER;

		$user_id = intval($user_id);
		if ($user_id <= 0 || strlen($code) <= 0)
			return false;

		if (!is_array($site_id))
			$site_id = array($site_id);

		$strSQL = "
			INSERT INTO b_user_counter (USER_ID, SITE_ID, CODE, CNT, LAST_DATE) VALUES ";

		foreach ($site_id as $i => $site_id_tmp)
		{
			if ($i > 0)
				$strSQL .= ",";
			$strSQL .= " (".$user_id.", '".$DB->ForSQL($site_id_tmp)."', '".$DB->ForSQL($code)."', 0, ".$DB->CurrentTimeFunction().") ";
		}

		$strSQL .= " ON DUPLICATE KEY UPDATE CNT = 0, LAST_DATE = ".$DB->CurrentTimeFunction();

		$res = $DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (self::$counters && self::$counters[$user_id])
		{
			foreach ($site_id as $site_id_tmp)
			{
				if ($site_id_tmp == '**')
				{
					foreach(self::$counters[$user_id] as $key => $tmp)
						self::$counters[$user_id][$key][$code] = 0;
					break;
				}
				else
				{
					if (!isset(self::$counters[$user_id][$site_id_tmp]))
						self::$counters[$user_id][$site_id_tmp] = array();

					self::$counters[$user_id][$site_id_tmp][$code] = 0;
				}
			}
		}

		$CACHE_MANAGER->Clean("user_counter".$user_id, "user_counter");

		return true;
	}

	protected static function dbIF($condition, $yes, $no)
	{
		return "if(".$condition.", ".$yes.", ".$no.")";
	}

	// legacy function
	public static function ClearByUser($user_id, $site_id = SITE_ID, $code = "**")
	{
		return self::Clear($user_id, $code, $site_id);
	}
}
?>