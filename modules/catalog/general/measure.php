<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
/**
 * Class CCatalogMeasureAll
 */
class CCatalogMeasureAll
{
	const DEFAULT_MEASURE_CODE = 796;

	protected static $defaultMeasure = null;
	/**
	 * @param string $action
	 * @param array $arFields
	 * @param int $id
	 * @return bool
	 */
	protected static function checkFields($action, &$arFields, $id = 0)
	{
		global $APPLICATION;
		$action = strtoupper($action);
		if ($action != 'ADD' && $action != 'UPDATE')
			return false;
		$id = (int)$id;
		if ($action == 'UPDATE' && $id <= 0)
			return false;

		if (array_key_exists('CODE', $arFields))
		{
			$code = trim($arFields['CODE']);
			if ($code === '')
			{
				$APPLICATION->ThrowException(Loc::getMessage('CAT_MEASURE_ERR_CODE_IS_ABSENT'));
				return false;
			}
			elseif(preg_match('/^[0-9]+$/', $code) !== 1)
			{
				$APPLICATION->ThrowException(Loc::getMessage('CAT_MEASURE_ERR_CODE_IS_BAD'));
				return false;
			}
			else
			{
				$arFields['CODE'] = (int)$code;
			}
		}

		$cnt = 0;
		switch ($action)
		{
			case 'ADD':
				if (!isset($arFields['CODE']))
					return false;
				$cnt = CCatalogMeasure::getList(array(), array("CODE" => $arFields['CODE']), array());
				break;
			case 'UPDATE':
				if (isset($arFields['CODE']))
					$cnt = CCatalogMeasure::getList(array(), array("CODE" => $arFields['CODE'], '!ID' => $id), array(), false, array('ID'));
				break;
		}
		if ($cnt > 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage('CAT_MEASURE_ERR_CODE_ALREADY_EXISTS'));
			return false;
		}

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
		global $DB;

		$id = (int)$id;
		if ($id <= 0)
			return false;
		if (!static::checkFields('UPDATE', $arFields, $id))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_measure", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_measure SET ".$strUpdate." WHERE ID = ".$id;
			if (!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}
		return $id;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function delete($id)
	{
		global $DB;
		$id = (int)$id;
		if ($id <= 0)
			return false;

		$DB->Query("DELETE FROM b_catalog_measure WHERE ID = ".$id);

		return true;
	}

	public static function getDefaultMeasure($getStub = false, $getExt = false)
	{
		$getStub = ($getStub === true);
		$getExt = ($getExt === true);

		if (self::$defaultMeasure === null)
		{
			$measureRes = CCatalogMeasure::getList(
				array(),
				array('IS_DEFAULT' => 'Y'),
				false,
				false,
				array()
			);
			if ($measure = $measureRes->GetNext(true, $getExt))
			{
				$measure['ID'] = (int)$measure['ID'];
				$measure['CODE'] = (int)$measure['CODE'];
				self::$defaultMeasure = $measure;
			}
		}
		if (self::$defaultMeasure === null)
		{
			$measureRes = CCatalogMeasure::getList(
				array(),
				array('CODE' => self::DEFAULT_MEASURE_CODE),
				false,
				false,
				array()
			);
			if ($measure = $measureRes->GetNext(true, $getExt))
			{
				$measure['ID'] = (int)$measure['ID'];
				$measure['CODE'] = (int)$measure['CODE'];
				self::$defaultMeasure = $measure;
			}
		}
		if (self::$defaultMeasure === null)
		{
			if ($getStub)
			{
				$defaultMeasureDescription = CCatalogMeasureClassifier::getMeasureInfoByCode(self::DEFAULT_MEASURE_CODE);
				if ($defaultMeasureDescription !== null)
				{
					self::$defaultMeasure = array(
						'ID' => 0,
						'CODE' => self::DEFAULT_MEASURE_CODE,
						'MEASURE_TITLE' => htmlspecialcharsEx($defaultMeasureDescription['MEASURE_TITLE']),
						'SYMBOL_RUS' => htmlspecialcharsEx($defaultMeasureDescription['SYMBOL_RUS']),
						'SYMBOL_INTL' => htmlspecialcharsEx($defaultMeasureDescription['SYMBOL_INTL']),
						'SYMBOL_LETTER_INTL' => htmlspecialcharsEx($defaultMeasureDescription['SYMBOL_LETTER_INTL']),
						'IS_DEFAULT' => 'Y'
					);
					if ($getExt)
					{
						self::$defaultMeasure['~ID'] = '0';
						self::$defaultMeasure['~CODE'] = (string)self::DEFAULT_MEASURE_CODE;
						self::$defaultMeasure['~MEASURE_TITLE'] = self::$defaultMeasure['MEASURE_TITLE'];
						self::$defaultMeasure['~SYMBOL_RUS'] = self::$defaultMeasure['SYMBOL_RUS'];
						self::$defaultMeasure['~SYMBOL_INTL'] = self::$defaultMeasure['SYMBOL_INTL'];
						self::$defaultMeasure['~SYMBOL_LETTER_INTL'] = self::$defaultMeasure['SYMBOL_LETTER_INTL'];
						self::$defaultMeasure['~IS_DEFAULT'] = 'Y';
					}
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
	static public function __construct($res)
	{
		parent::__construct($res);
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