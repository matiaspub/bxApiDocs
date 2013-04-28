<?
IncludeModuleLangFile(__FILE__);

class CAllCatalogVat
{
	public static function CheckFields($ACTION, &$arFields)
	{
		global $APPLICATION;

		if ($ACTION == 'INSERT')
		{
			unset($arFields['ID']);

			$arFields = array(
				"ACTIVE" => $arFields['ACTIVE'] == 'Y' ? 'Y' : 'N',
				"C_SORT" => intval($arFields['C_SORT']) > 0 ? intval($arFields['C_SORT']) : 100,
				'NAME' => trim($arFields['NAME']),
				'RATE' => floatval($arFields['RATE'])
			);
		}
		else
		{

			$arResultFields = array();

			$arResultFields['ID'] = intval($arFields['ID']);

			if (is_set($arFields, 'ACTIVE'))
				$arResultFields['ACTIVE'] = $arFields['ACTIVE'] == 'Y' ? 'Y' : 'N';

			if (is_set($arFields, 'C_SORT'))
				$arResultFields['C_SORT'] = intval($arFields['C_SORT']) > 0 ? intval($arFields['C_SORT']) : 100;

			if (is_set($arFields, 'NAME'))
				$arResultFields['NAME'] = trim($arFields['NAME']);

			if (is_set($arFields, 'RATE'))
				$arResultFields['RATE'] = floatval($arFields['RATE']);

			$arFields = $arResultFields;
		}

		$arErrors = array();

		if (is_set($arFields, 'NAME') && strlen($arFields['NAME']) <= 0)
			$arErrors[] = GetMessage('CVAT_ERROR_BAD_NAME');

		if (is_set($arFields, 'RATE') && ($arFields['RATE'] < 0 || $arFields['RATE'] > 100))
			$arErrors[] = GetMessage('CVAT_ERROR_BAD_RATE');

		if (count($arErrors) > 0)
		{
			//$GLOBALS['APPLICATION']->ThrowException(implode('<br />', $arErrors));
			$APPLICATION->ThrowException(implode('<br />', $arErrors));
			return false;
		}

		return true;
	}

	public static function GetByID($ID)
	{
		return CCatalogVat::GetList(array(), array('ID' => $ID));
	}

	public static function GetList($arOrder = array('CSORT' => 'ASC'), $arFilter = array(), $arFields = array())
	{
		global $DB;

		$arSqlSearch = Array();
		$strSqlSearch = "";

		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0, $cnt = count($filter_keys); $i < $cnt; $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				$key = $filter_keys[$i];
				switch(strtoupper($filter_keys[$i]))
				{
					case "ID":
						$arSqlSearch[] = 'ID='.intval($val);
					break;
					case "ACTIVE":
						$arSqlSearch[] = GetFilterQuery("ACTIVE", $val);
					break;
					case "NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("NAME", $val, $match);
					break;
					case "RATE":
						$arSqlSearch[] = 'RATE=\''.doubleval($val).'\''; //GetFilterQuery('RATE', $val);
					break;
				}
			}
		}

		$sOrder = "";
		$sort_keys = array_keys($arOrder);
		for ($i=0, $intCount = count($sort_keys); $i < $intCount; $i++)
		{
			$ord = (strtoupper($arOrder[$sort_keys[$i]]) <> "ASC"? "DESC":"ASC");
			switch (strtoupper($sort_keys[$i]))
			{
				case "ID": $sOrder .= ", ID ".$ord; break;
				case "C_SORT": $sOrder .= ", C_SORT ".$ord; break;
				case "ACTIVE": $sOrder .= ", ACTIVE ".$ord; break;
				case "NAME": $sOrder .= ", NAME ".$ord; break;
				case "RATE": $sOrder .= ", RATE ".$ord; break;
			}
		}
		if (strlen($sOrder)<=0)
		{
			$sOrder = "C_SORT ASC";
		}
		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$arDefaultFields = array('ID', 'TIMESTAMP_X', 'ACTIVE', 'C_SORT', 'NAME', 'RATE');
		if (!is_array($arFields) || count($arFields) <= 0)
			$arQueryFields = $arDefaultFields;
		else
		{
			$arQueryFields = array();
			foreach ($arFields as $fld)
			{
				if (in_array($fld, $arDefaultFields))
					$arQueryFields[] = $fld;
			}
			if (count($arQueryFields) <= 0)
				$arQueryFields = $arDefaultFields;
		}

		$strSqlFields = implode(', ', $arQueryFields);

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
SELECT
".$strSqlFields."
FROM b_catalog_vat
WHERE
".$strSqlSearch."
".$strSqlOrder."
";
		//echo $strSql;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->is_filtered = (IsFiltered($strSqlSearch));

		return $res;
	}

	public static function Set($arFields)
	{
		global $DB;
		global $APPLICATION;

		$ACTION = empty($arFields['ID']) ? 'INSERT' : 'UPDATE';

		if (CCatalogVat::CheckFields($ACTION, $arFields))
		{
			//echo '<pre>'; print_r($arFields); echo '</pre>';

			$ID = 0;
			if ($ACTION == 'UPDATE')
			{
				$ID = intval($arFields['ID']);
			}
			else
			{
				if (array_key_exists('ID',$arFields))
					unset($arFields['ID']);
			}

			foreach ($arFields as $key => $value)
				$arFields[$key] = "'".$DB->ForSql($arFields[$key])."'";

			if ($ACTION == 'INSERT')
			{
				$res = $DB->Insert('b_catalog_vat', $arFields, $err_mess.__LINE__);
				$ID = $res;
			}
			else
			{
				unset($arFields['ID']);
				$res = $DB->Update('b_catalog_vat', $arFields, "WHERE ID=".$ID, $err_mess.__LINE__);
			}

			if ($res)
				return $ID;
			else
			{
				//$GLOBALS['APPLICATION']->ThrowException(GetMessage('CVAT_ERROR_SET'));
				$APPLICATION->ThrowException(GetMessage('CVAT_ERROR_SET'));
				return false;
			}
		}
		else
			return false;
	}

	public static function Delete($ID)
	{
		global $DB;
		$DB->Query("DELETE FROM b_catalog_vat WHERE ID='".intval($ID)."'");
		return true;
	}

	public static function GetByProductID($PRODUCT_ID)
	{

	}
}
?>