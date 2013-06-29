<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/catalog_export.php");


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogexport/index.php
 * @author Bitrix
 */
class CCatalogExport extends CAllCatalogExport
{
	
	/**
	 * <p>Развернутое описание функции.</p> <p><b>Примечание</b>: в данном методе отключена возможность заносить значения в обход CheckFields, кроме одного исключения - </p> <pre class="syntax">"=LAST_USE" =&gt; $DB-&gt;GetNowF unction()</pre>.
	 *
	 *
	 *
	 *
	 * @return mixed <p>описание возвращаемого значения.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>Пару строчек примера<br>желательно имеющего не только академическую ценность<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogexport/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;
		global $USER;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				if ('=LAST_USE' == $key)
				{
					if ($value == $DB->GetNowFunction())
						$arFields1['LAST_USE'] = $DB->GetNowFunction();
				}
				unset($arFields[$key]);
			}
		}

		if (isset($USER) && $USER instanceof CUser && 'CUser' == get_class($USER))
		{
			if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
				$arFields["CREATED_BY"] = intval($USER->GetID());
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = intval($USER->GetID());
		}
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);

		$arFields1['TIMESTAMP_X'] = $DB->GetNowFunction();
		$arFields1['DATE_CREATE'] = $DB->GetNowFunction();

		$arFields["IS_EXPORT"] = "Y";

		if (!CCatalogExport::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_export", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0)
			{
				$arInsert[0] .= ", ";
				$arInsert[1] .= ", ";
			}
			$arInsert[0] .= $key;
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_catalog_export(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		return $ID;
	}

	
	/**
	 * <p>Развернутое описание функции.</p> <p><b>Примечание</b>: в данном методе отключена возможность заносить значения в обход CheckFields, кроме одного исключения - </p> <pre class="syntax">"=LAST_USE" =&gt; $DB-&gt;GetNowF unction()</pre>.
	 *
	 *
	 *
	 *
	 * @return mixed <p>описание возвращаемого значения.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>Пару строчек примера<br>желательно имеющего не только академическую ценность<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogexport/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		global $USER;

		$ID = intval($ID);

		$boolNoUpdate = false;
		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				if ('=LAST_USE' == $key)
				{
					if ($value == $DB->GetNowFunction())
					{
						$arFields1['LAST_USE'] = $DB->GetNowFunction();
						$boolNoUpdate = true;
					}
				}
				unset($arFields[$key]);
			}
		}

		if (array_key_exists('CREATED_BY',$arFields))
			unset($arFields['CREATED_BY']);
		if (array_key_exists('DATE_CREATE',$arFields))
			unset($arFields['DATE_CREATE']);
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);

		if (!$boolNoUpdate)
		{
			if (isset($USER) && $USER instanceof CUser && 'CUser' == get_class($USER))
			{
				if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
					$arFields["MODIFIED_BY"] = intval($USER->GetID());
			}
			$arFields1['TIMESTAMP_X'] = $DB->GetNowFunction();
		}
		else
		{
			if (array_key_exists('MODIFIED_BY',$arFields))
				unset($arFields['MODIFIED_BY']);
		}
		$arFields["IS_EXPORT"] = "Y";

		if (!CCatalogExport::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_export", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql =
			"UPDATE b_catalog_export SET ".$strUpdate." WHERE ID = ".$ID." AND IS_EXPORT = 'Y'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>