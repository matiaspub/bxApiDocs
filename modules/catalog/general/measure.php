<?

/**
 * Class CCatalogMeasureAll
 */
class CCatalogMeasureAll
{
	const DEFAULT_MEASURE_CODE = 796;
	protected static $defaultMeasure = null;
	/**
	 * @param $action
	 * @param $arFields
	 * @return bool
	 */
	protected static function checkFields($action, &$arFields)
	{
		if((is_set($arFields, "IS_DEFAULT")) && (($arFields["IS_DEFAULT"]) == 'Y'))
		{
			$dbMeasure = CCatalogMeasure::getList(array(), array("IS_DEFAULT" => 'Y'), false, false, array('ID'));
			while($arMeasure = $dbMeasure->Fetch())
			{
				if(!self::update($arMeasure["ID"], array("IS_DEFAULT" => 'N')))
					return false;
			}
		}
		return true;
	}

	/**
	 * @param $id
	 * @param $arFields
	 * @return bool|int
	 */
	public static function update($id, $arFields)
	{
		$id = intval($id);
		if($id < 0 || !self::checkFields('UPDATE', $arFields))
			return false;
		global $DB;
		$strUpdate = $DB->PrepareUpdate("b_catalog_measure", $arFields);
		$strSql = "UPDATE b_catalog_measure SET ".$strUpdate." WHERE ID = ".$id;
		if(!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;
		return $id;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function delete($id)
	{
		global $DB;
		$id = intval($id);
		if($id > 0)
		{
			if($DB->Query("DELETE FROM b_catalog_measure WHERE ID = ".$id, true))
				return true;
		}
		return false;
	}

	public static function getDefaultMeasure($getStub = false, $getExt = false)
	{
		if (null === self::$defaultMeasure)
		{
			$measureRes = CCatalogMeasure::getList(
				array(),
				array('IS_DEFAULT' => 'Y'),
				false,
				false,
				array()
			);
			if ($measure = $measureRes->GetNext())
			{
				$measure['ID'] = intval($measure['ID']);
				$measure['CODE'] = intval($measure['CODE']);
				self::$defaultMeasure = $measure;
			}
		}
		if (null === self::$defaultMeasure)
		{
			$measureRes = CCatalogMeasure::getList(
				array(),
				array('CODE' => self::DEFAULT_MEASURE_CODE),
				false,
				false,
				array()
			);
			if ($measure = $measureRes->GetNext())
			{
				$measure['ID'] = intval($measure['ID']);
				$measure['CODE'] = intval($measure['CODE']);
				self::$defaultMeasure = $measure;
			}
		}
		if (null === self::$defaultMeasure)
		{
			if ($getStub)
			{
				$defaultMeasureDescription = CCatalogMeasureClassifier::getMeasureInfoByCode(self::DEFAULT_MEASURE_CODE);
				if (null !== $defaultMeasureDescription)
				self::$defaultMeasure = array(
					'ID' => 0,
					'CODE' => self::DEFAULT_MEASURE_CODE,
					'MEASURE_TITLE' => $defaultMeasureDescription['MEASURE_TITLE'],
					'SYMBOL_RUS' => $defaultMeasureDescription['SYMBOL_RUS'],
					'SYMBOL_INTL' => $defaultMeasureDescription['SYMBOL_INTL'],
					'SYMBOL_LETTER_INTL' => $defaultMeasureDescription['SYMBOL_LETTER_INTL']
				);
				if ($getExt)
				{
					self::$defaultMeasure['~MEASURE_TITLE'] = self::$defaultMeasure['MEASURE_TITLE'];
					self::$defaultMeasure['~SYMBOL_RUS'] = self::$defaultMeasure['SYMBOL_RUS'];
					self::$defaultMeasure['~SYMBOL_INTL'] = self::$defaultMeasure['SYMBOL_INTL'];
					self::$defaultMeasure['~SYMBOL_LETTER_INTL'] = self::$defaultMeasure['SYMBOL_LETTER_INTL'];
				}
			}
		}
		return self::$defaultMeasure;
	}
}

/**
 * Class CCatalogMeasureResult
 */
class CCatalogMeasureResult extends CDBResult
{
	/**
	 * @param $res
	 */
	public static function CCatalogMeasureResult($res)
	{
		parent::CDBResult($res);
	}

	/**
	 * @return array
	 */
	public static function Fetch()
	{
		$res = parent::Fetch();
		if (!empty($res))
		{
			if (array_key_exists('MEASURE_TITLE', $res) && $res["MEASURE_TITLE"] == '')
			{
				$tmpTitle = CCatalogMeasureClassifier::getMeasureTitle($res["CODE"], 'MEASURE_TITLE');
				$res["MEASURE_TITLE"] = ($tmpTitle == '') ? $res["SYMBOL_INTL"] : $tmpTitle;
			}
			if (array_key_exists('SYMBOL_RUS', $res) && $res["SYMBOL_RUS"] == '')
			{
				$tmpSymbol = CCatalogMeasureClassifier::getMeasureTitle($res["CODE"], 'SYMBOL_RUS');
				$res["SYMBOL_RUS"] = ($tmpSymbol == '') ? $res["SYMBOL_INTL"] : $tmpSymbol;
			}
		}
		return $res;
	}
}