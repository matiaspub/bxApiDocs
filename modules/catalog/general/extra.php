<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/index.php
 * @author Bitrix
 */
class CAllExtra
{
	protected static $arExtraCache = array();

	public static function ClearCache()
	{
		self::$arExtraCache = array();
	}

	
	/**
	* <p>Метод возвращает параметры наценки по ее коду ID. Если указанной наценки не найдено, вернет <i>false</i>. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код наценки.
	*
	* @return array <p>Возвращается ассоциативный массив с ключами </p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* наценки.</td> </tr> <tr> <td>NAME</td> <td>Название наценки.</td> </tr> <tr> <td>PERCENTAGE</td>
	* <td>Процент наценки.</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__getbyid.949068d9.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		if (isset(self::$arExtraCache[$ID]))
		{
			return self::$arExtraCache[$ID];
		}
		else
		{
			$strSql = "SELECT ID, NAME, PERCENTAGE FROM b_catalog_extra WHERE ID = ".$ID;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($res = $db_res->Fetch())
			{
				return $res;
			}
		}
		return false;
	}

	
	/**
	* <p>Метод возвращает HTML код для отображения выпадающего списка (select) наценок. Метод кеширует список наценок, поэтому ее повторный вызов на одной странице не приводит к дополнительным запросам базы данных. Метод динамичный.</p>
	*
	*
	* @param string $sFieldName  Название выпадающего списка (атрибут name)
	*
	* @param string $sValue  Начальное значение. </h
	*
	* @param string $sDefaultValue = "" Название особого значения, относящееся к пустому значению
	* наценки (например, "Все" или "Нет")
	*
	* @param string $JavaChangeFunc = "" JavaScript обработчик события OnChange списка. Если указана пустая строка,
	* то событие OnChange не обрабатывается.
	*
	* @param string $sAdditionalParams = "" Дополнительные атрибуты тега &lt;select&gt;
	*
	* @return string <p>Возвращает HTML код для вывода выпадающего списка наценок </p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* echo CExtra::SelectBox("CAT_EXTRA",
	*                        2,
	*                        "Не установлено",
	*                        "ChangeExtra()",
	*                        "class='typeselect'");
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__selectbox.81640baf.php
	* @author Bitrix
	*/
	public static function SelectBox($sFieldName, $sValue, $sDefaultValue = "", $JavaChangeFunc = "", $sAdditionalParams = "")
	{
		if (empty(self::$arExtraCache))
		{
			$rsExtras = CExtra::GetList(
				array("NAME" => "ASC")
			);
			while ($arExtra = $rsExtras->Fetch())
			{
				$arExtra['ID'] = intval($arExtra['ID']);
				self::$arExtraCache[$arExtra['ID']] = $arExtra;
				if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
				{
					global $MAIN_EXTRA_LIST_CACHE;
					$MAIN_EXTRA_LIST_CACHE = self::$arExtraCache;
				}
			}
		}
		$s = '<select name="'.$sFieldName.'"';
		if (!empty($JavaChangeFunc))
			$s .= ' onchange="'.$JavaChangeFunc.'"';
		if (!empty($sAdditionalParams))
			$s .= ' '.$sAdditionalParams.' ';
		$s .= '>';
		$sValue = intval($sValue);
		$boolFound = isset(self::$arExtraCache[$sValue]);
		if (!empty($sDefaultValue))
			$s .= '<option value="0"'.($boolFound ? '' : ' selected').'>'.htmlspecialcharsex($sDefaultValue).'</option>';

		foreach (self::$arExtraCache as &$arExtra)
		{
			$s .= '<option value="'.$arExtra['ID'].'"'.($arExtra['ID'] == $sValue ? ' selected' : '').'>'.htmlspecialcharsex($arExtra['NAME']).' ('.htmlspecialcharsex($arExtra['PERCENTAGE']).'%)</option>';
		}
		if (isset($arExtra))
			unset($arExtra);
		return $s.'</select>';
	}

	
	/**
	* <p>Метод обновляет параметры наценки с кодом ID на значения из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код.</b
	*
	* @param array $arFields  Ассоциативный массив новых параметров наценки с ключами: <ul> <li>NAME
	* - название наценки;</li> <li>PERCENTAGE - процент наценки (может быть как
	* положительным, так и отрицательным);</li> <li>RECALCULATE - если имеет
	* значение Y, то будут автоматически пересчитаны все цены, которые
	* заданы этой наценкой</li> </ul>
	*
	* @return bool <p>Возвращает значение <i>true</i> в случае успешного сохранения
	* наценки и <i>false</i> - в противном случае </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__update.8ab660d7.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;
		if (!CExtra::CheckFields('UPDATE', $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_extra", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_extra SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if (isset($arFields['RECALCULATE']) && 'Y' == $arFields['RECALCULATE'])
			{
				CPrice::ReCalculate('EXTRA', $ID, $arFields['PERCENTAGE']);
			}
			CExtra::ClearCache();
		}
		return true;
	}

	
	/**
	* <p>Удаляет запись наценки из базы. Цены, которые были заданы в виде наценки от базовой цены, становятся заданными абсолютным значением. Сама величина цены не меняется. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код наценки.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__delete.ca4c66fe.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;
		$DB->Query("UPDATE b_catalog_price SET EXTRA_ID = NULL WHERE EXTRA_ID = ".$ID);
		CExtra::ClearCache();
		return $DB->Query("DELETE FROM b_catalog_extra WHERE ID = ".$ID, true);
	}

	
	/**
	* <p>Метод служит для проверки параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__add.937250e4.php">CExtra::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__update.8ab660d7.php">CExtra::Update</a>. Метод динамичный.</p>
	*
	*
	* @param string $strAction  Указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__add.937250e4.php">CExtra::Add</a>;</li> <li>
	* <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__update.8ab660d7.php">CExtra::Update</a>.</li>
	* </ul>
	*
	* @param array &$arFields  Ассоциативный массив параметров наценки. Допустимые ключи: <ul> <li>
	* <b>NAME</b> - название наценки;</li> <li> <b>PERCENTAGE</b> - процент наценки (может
	* быть как положительным, так и отрицательным).</li> </ul>
	*
	* @param int $ID  Код наценки.
	*
	* @return bool <p> В случае корректности переданных параметров возвращает true,
	* иначе - false. Если метод вернул false, с помощью $APPLICATION-&gt;GetException() можно
	* получить текст ошибок.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__add.937250e4.php">CExtra::Add</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__update.8ab660d7.php">CExtra::Update</a></li> </ul>
	* </ht<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/checkfields.php
	* @author Bitrix
	*/
	public static function CheckFields($strAction, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$strAction = strtoupper($strAction);

		$ID = intval($ID);
		if ('UPDATE' == $strAction && 0 >= $ID)
		{
			$arMsg[] = array('id' => 'ID', 'text' => Loc::getMessage('CAT_EXTRA_ERR_UPDATE_NOT_ID'));
			$boolResult = false;
		}
		if (array_key_exists('ID', $arFields))
		{
			unset($arFields['ID']);
		}

		if ('ADD' == $strAction)
		{
			if (!array_key_exists('NAME', $arFields))
			{
				$arMsg[] = array('id' => 'NAME', 'text' => Loc::getMessage('CAT_EXTRA_ERROR_NONAME'));
				$boolResult = false;
			}
			if (!array_key_exists('PERCENTAGE', $arFields))
			{
				$arMsg[] = array('id' => 'PERCENTAGE', 'text' => Loc::getMessage('CAT_EXTRA_ERROR_NOPERCENTAGE'));
				$boolResult = false;
			}
		}

		if ($boolResult)
		{
			if (array_key_exists('NAME', $arFields))
			{
				$arFields["NAME"] = trim($arFields["NAME"]);
				if ('' == $arFields["NAME"])
				{
					$arMsg[] = array('id' => 'NAME', 'text' => Loc::getMessage('CAT_EXTRA_ERROR_NONAME'));
					$boolResult = false;
				}
			}
			if (array_key_exists('PERCENTAGE', $arFields))
			{
				$arFields["PERCENTAGE"] = trim($arFields["PERCENTAGE"]);
				if ('' == $arFields["PERCENTAGE"])
				{
					$arMsg[] = array('id' => 'PERCENTAGE', 'text' => Loc::getMessage('CAT_EXTRA_ERROR_NOPERCENTAGE'));
					$boolResult = false;
				}
				else
				{
					$arFields["PERCENTAGE"] = doubleval($arFields["PERCENTAGE"]);
				}
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

/*
* @deprecated deprecated since catalog 12.5.6
*/
	public static function PrepareInsert(&$arFields, &$intID)
	{
		return false;
	}
}
?>