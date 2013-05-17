<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
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
	
	/**
	 * <p>Функция возвращает параметры наценки по ее коду ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код наценки.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив с ключами </p><table class="tnormal"
	 * width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * наценки.</td> </tr> <tr> <td>NAME</td> <td>Название наценки.</td> </tr> <tr> <td>PERCENTAGE</td>
	 * <td>Процент наценки.</td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__getbyid.949068d9.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;
		$strSql =
			"SELECT ID, NAME, PERCENTAGE ".
			"FROM b_catalog_extra ".
			"WHERE ID = ".intval($ID)." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;
		return false;
	}

	
	/**
	 * <p>Функция возвращает HTML код для отображения выпадающего списка (select) наценок. Функция кэширует список наценок, поэтому ее повторный вызов на одной странице не приводит к дополнительным запросам базы данных. </p>
	 *
	 *
	 *
	 *
	 * @param string $sFieldName  Название выпадающего списка (атрибут name)
	 *
	 *
	 *
	 * @param string $sValue  Начальное значение.
	 *
	 *
	 *
	 * @param string $sDefaultValue = "" Название особого значения, относящееся к пустому значению
	 * наценки (например, "Все" или "Нет")
	 *
	 *
	 *
	 * @param string $JavaChangeFunc = "" JavaScript обработчик события OnChange списка. Если указана пустая строка,
	 * то событие OnChange не обрабатывается.
	 *
	 *
	 *
	 * @param string $sAdditionalParams = "" Дополнительные атрибуты тега &lt;select&gt;
	 *
	 *
	 *
	 * @return string <p>Возвращает HTML код для вывода выпадающего списка наценок </p><a
	 * name="examples"></a>
	 *
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
		if (!isset($GLOBALS["MAIN_EXTRA_LIST_CACHE"]) || !is_array($GLOBALS["MAIN_EXTRA_LIST_CACHE"]) || count($GLOBALS["MAIN_EXTRA_LIST_CACHE"])<1)
		{
			unset($GLOBALS["MAIN_EXTRA_LIST_CACHE"]);

			$l = CExtra::GetList(array("NAME" => "ASC"));
			while ($l_res = $l->Fetch())
			{
				$GLOBALS["MAIN_EXTRA_LIST_CACHE"][] = $l_res;
			}
		}
		$s = '<select name="'.$sFieldName.'"';
		if (!empty($JavaChangeFunc))
			$s .= ' OnChange="'.$JavaChangeFunc.'"';
		if (!empty($sAdditionalParams))
			$s .= ' '.$sAdditionalParams.' ';
		$s .= '>'."\n";
		$found = false;

		$intCount = count($GLOBALS["MAIN_EXTRA_LIST_CACHE"]);
		for ($i=0; $i < $intCount; $i++)
		{
			$found = (intval($GLOBALS["MAIN_EXTRA_LIST_CACHE"][$i]["ID"]) == intval($sValue));
			$s1 .= '<option value="'.$GLOBALS["MAIN_EXTRA_LIST_CACHE"][$i]["ID"].'"'.($found ? ' selected':'').'>'.htmlspecialcharsbx($GLOBALS["MAIN_EXTRA_LIST_CACHE"][$i]["NAME"]).' ('.htmlspecialcharsbx($GLOBALS["MAIN_EXTRA_LIST_CACHE"][$i]["PERCENTAGE"]).'%)</option>'."\n";
		}
		if (!empty($sDefaultValue))
			$s .= "<option value='' ".($found ? "" : "selected").">".htmlspecialcharsbx($sDefaultValue)."</option>";
		return $s.$s1.'</select>';
	}

	
	/**
	 * <p>Функция обновляет параметры наценки с кодом ID на значения из массива arFields.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив новых параметров наценки с ключами: <ul> <li>NAME
	 * - название наценки;</li> <li>PERCENTAGE - процент наценки (может быть как
	 * положительным, так и отрицательным);</li> <li>RECALCULATE - если имеет
	 * значение Y, то будут автоматически пересчитаны все цены, которые
	 * заданы этой наценкой</li> </ul>
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение <i>true</i> в случае успешного сохранения
	 * наценки и <i>false</i> - в противном случае </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__update.8ab660d7.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if (!CExtra::CheckFields('UPDATE', $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_extra", $arFields);
		$strSql = "UPDATE b_catalog_extra SET ".$strUpdate." WHERE ID = '".intval($ID)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (!empty($arFields["RECALCULATE"]) && $arFields["RECALCULATE"]=="Y")
		{
			CPrice::ReCalculate("EXTRA", $ID, $arFields["PERCENTAGE"]);
		}

		unset($GLOBALS["MAIN_EXTRA_LIST_CACHE"]);
		return true;
	}

	
	/**
	 * <p>Удаляет запись наценки из базы. Цены, которые были заданы в виде наценки от базовой цены, становятся заданными абсолютным значением. Сама величина цены не меняется. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код наценки.
	 *
	 *
	 *
	 * @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	 * противном случае </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__delete.ca4c66fe.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		$DB->Query("UPDATE b_catalog_price SET EXTRA_ID = NULL WHERE EXTRA_ID = ".$ID." ");
		unset($GLOBALS["MAIN_EXTRA_LIST_CACHE"]);
		return $DB->Query("DELETE FROM b_catalog_extra WHERE ID = ".$ID." ", true);
	}

	public static function CheckFields($strAction, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		if ($strAction != 'ADD' && $strAction != 'UPDATE')
			$boolResult = false;

		$ID = intval($ID);
		if ($strAction == 'UPDATE' && $ID <= 0)
		{
			$arMsg[] = $arMsg[] = array('id' => 'ID', 'text' => GetMessage('CAT_EXTRA_ERR_UPDATE_NOT_ID'));
			$boolResult = false;
		}

		if ($boolResult)
		{
			if (isset($arFields['ID']))
			{
				if ($strAction == 'UPDATE')
				{
					unset($arFields['ID']);
				}
				else
				{
					$arFields['ID'] = intval($arFields['ID']);
					if ($arFields['ID'] <= 0)
					{
						unset($arFields['ID']);
					}
					else
					{
						$mxRes = CExtra::GetByID($arFields['ID']);
						if ($mxRes)
						{
							$arMsg[] = $arMsg[] = array('id' => 'ID', 'text' => GetMessage('CAT_EXTRA_ERR_ADD_EXISTS_ID'));
							$boolResult = false;
						}
					}
				}
			}
		}

		if ($boolResult)
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
			if (empty($arFields["NAME"]))
			{
				$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('CAT_EXTRA_ERROR_NONAME'));
				$boolResult = false;
			}
			if (empty($arFields["PERCENTAGE"]))
				$arFields["PERCENTAGE"] = 0;
			$arFields["PERCENTAGE"] = DoubleVal($arFields["PERCENTAGE"]);
		}

		if (!$boolResult)
		{
			if (!empty($arMsg))
			{
				$obError = new CAdminException($arMsg);
				$APPLICATION->ThrowException($obError);
			}
		}
		return $boolResult;
	}

	public static function PrepareInsert(&$arFields, &$intID)
	{
		global $APPLICATION;
		global $DB;

		$arMsg = array();
		$boolResult = true;

		$intID = '';
		$arFieldsList = $DB->GetTableFieldsList("b_catalog_extra");
		foreach ($arFields as $key => $value)
		{
			if (in_array($key,$arFieldsList))
			{
				if ($key == 'ID')
				{
					$intID = $value;
					unset($arFields[$key]);
				}
				else
				{
					$arFields[$key] = "'".$DB->ForSql($value)."'";
				}
			}
			else
			{
				unset($arFields[$key]);
			}
		}
		if (empty($arFields))
		{
			$arMsg[] = array('id' => 'ID', 'text' => GetMessage('CAT_EXTRA_ERR_ADD_FIELDS_EMPTY'));
			$boolResult = false;
		}

		if (!$boolResult)
		{
			if (!empty($arMsg))
			{
				$obError = new CAdminException($arMsg);
				$APPLICATION->ThrowException($obError);
			}
		}
		return $boolResult;
	}
}
?>