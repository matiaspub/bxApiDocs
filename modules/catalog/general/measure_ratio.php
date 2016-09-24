<?
use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class CCatalogMeasureRatioAll
{
	protected static $whiteList = array('ID', 'PRODUCT_ID', 'RATIO');

	protected static function checkFields($action, &$arFields)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$action = strtoupper($action);
		if ($action != 'UPDATE' && $action != 'ADD')
		{
			$APPLICATION->ThrowException(Loc::getMessage('CATALOG_MEASURE_RATIO_BAD_ACTION'));
			return false;
		}
		$clearFields = array();
		foreach (self::$whiteList as $field)
		{
			if ($field == 'ID')
				continue;
			if (isset($arFields[$field]))
				$clearFields[$field] = $arFields[$field];
		}
		unset($field);

		if ($action == 'ADD')
		{
			if (empty($clearFields))
			{
				$APPLICATION->ThrowException(Loc::getMessage('CATALOG_MEASURE_RATIO_EMPTY_CLEAR_FIELDS'));
				return false;
			}
			if (!isset($clearFields['PRODUCT_ID']))
			{
				$APPLICATION->ThrowException(Loc::getMessage('CATALOG_MEASURE_RATIO_PRODUCT_ID_IS_ABSENT'));
				return false;
			}
			if (!isset($clearFields['RATIO']))
				$clearFields['RATIO'] = 1;
		}
		if (isset($clearFields['PRODUCT_ID']))
		{
			$clearFields['PRODUCT_ID'] = (int)$clearFields['PRODUCT_ID'];
			if ($clearFields['PRODUCT_ID'] <= 0)
			{
				$APPLICATION->ThrowException(Loc::getMessage('CATALOG_MEASURE_RATIO_BAD_PRODUCT_ID'));
				return false;
			}
		}
		if (isset($clearFields['RATIO']))
		{
			if (is_string($clearFields['RATIO']))
				$clearFields['RATIO'] = str_replace(',', '.', $clearFields['RATIO']);
			$clearFields['RATIO'] = (float)$clearFields['RATIO'];
			if ($clearFields['RATIO'] <= CATALOG_VALUE_EPSILON)
				$clearFields['RATIO'] = 1;
		}
		$arFields = $clearFields;
		unset($clearFields);

		return true;
	}

	/**
	 * @deprecated deprecated since catalog 16.0.13
	 * @see \Bitrix\Catalog\MeasureRatioTable::add
	 * Attention! Method \Bitrix\Catalog\MeasureRatioTable::add very strict checks the input parameters.
	 *
	 * Add measure ratio for product.
	 *
	 * @param array $arFields		Measure ratio.
	 * @return bool|int
	 * @throws Exception
	 */
	public static function add($arFields)
	{
		if (!static::checkFields('ADD', $arFields))
			return false;

		$existRatio = Catalog\MeasureRatioTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=PRODUCT_ID' => $arFields['PRODUCT_ID'], '=RATIO' => $arFields['RATIO'])
		))->fetch();
		if (!empty($existRatio))
			return (int)$existRatio['ID'];

		$result = Catalog\MeasureRatioTable::add($arFields);
		if ($result->isSuccess())
			return (int)$result->getId();

		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$errorList = $result->getErrorMessages();
		if (!empty($errorList))
			$APPLICATION->ThrowException(implode(', ', $errorList));
		unset($errorList, $result);
		return false;
	}

	/**
	 * @deprecated deprecated since catalog 16.0.13
	 * @see \Bitrix\Catalog\MeasureRatioTable::update
	 * Attention! Method \Bitrix\Catalog\MeasureRatioTable::update very strict checks the input parameters.
	 *
	 * Update measure ratio for product by id.
	 *
	 * @param int $id				Measure ratio id.
	 * @param array $arFields		Measure ratio.
	 * @return bool|int
	 * @throws Exception
	 */
	public static function update($id, $arFields)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$id = (int)$id;
		if ($id <= 0 || !static::checkFields('UPDATE', $arFields))
			return false;
		if (empty($arFields))
			return $id;

		$existRatio = Catalog\MeasureRatioTable::getList(array(
			'select' => array('ID'),
			'filter' => array('!=ID' => $id, '=PRODUCT_ID' => $arFields['PRODUCT_ID'], '=RATIO' => $arFields['RATIO'])
		))->fetch();
		if (!empty($existRatio))
		{
			$APPLICATION->ThrowException(Loc::getMessage(
				'CATALOG_MEASURE_RATIO_RATIO_ALREADY_EXIST',
				array('#RATIO#' => $arFields['RATIO'])
			));
			return false;
		}

		$result = Catalog\MeasureRatioTable::update($id, $arFields);
		if ($result->isSuccess())
			return $id;

		$errorList = $result->getErrorMessages();
		if (!empty($errorList))
			$APPLICATION->ThrowException(implode(', ', $errorList));
		unset($errorList, $result);
		return false;
	}

	/**
	 * @deprecated deprecated since catalog 16.0.13
	 * @see \Bitrix\Catalog\MeasureRatioTable::delete
	 *
	 * Delete measure ratio by id.
	 *
	 * @param int $id		Measure ratio id.
	 * @return bool
	 * @throws Exception
	 */
	public static function delete($id)
	{
		$id = (int)$id;
		if ($id <= 0)
			return false;

		$result = Catalog\MeasureRatioTable::delete($id);
		if ($result->isSuccess())
			return true;

		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$errorList = $result->getErrorMessages();
		if (!empty($errorList))
			$APPLICATION->ThrowException(implode(', ', $errorList));
		unset($errorList, $result);
		return false;
	}
}