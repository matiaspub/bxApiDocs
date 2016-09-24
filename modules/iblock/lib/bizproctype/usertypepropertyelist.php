<?php

namespace Bitrix\Iblock\BizprocType;

use Bitrix\Bizproc\BaseType;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

Loc::loadMessages(__FILE__);

class UserTypePropertyElist extends UserTypeProperty
{
	private static $controlIsRendered = false;

	/**
	 * @param FieldType $fieldType
	 * @param string $callbackFunctionName
	 * @param mixed $value
	 * @return string
	 */
	public static function renderControlOptions(FieldType $fieldType, $callbackFunctionName, $value)
	{
		if (is_array($value))
		{
			reset($value);
			$valueTmp = (int) current($value);
		}
		else
		{
			$valueTmp = (int) $value;
		}

		$iblockId = 0;
		if ($valueTmp > 0)
		{
			$elementIterator = \CIBlockElement::getList(array(), array('ID' => $valueTmp), false, false, array('ID', 'IBLOCK_ID'));
			if ($element = $elementIterator->fetch())
				$iblockId = $element['IBLOCK_ID'];
		}
		if ($iblockId <= 0 && (int) $fieldType->getOptions() > 0)
			$iblockId = (int) $fieldType->getOptions();

		$defaultIBlockId = 0;

		$result = '<select id="WFSFormOptionsX" onchange="'.htmlspecialcharsbx($callbackFunctionName).'(this.options[this.selectedIndex].value)">';
		$iblockTypeIterator = \CIBlockParameters::getIBlockTypes();
		foreach ($iblockTypeIterator as $iblockTypeId => $iblockTypeName)
		{
			$result .= '<optgroup label="'.htmlspecialcharsbx($iblockTypeName).'">';

			$iblockIterator = \CIBlock::getList(array('SORT' => 'ASC'), array('TYPE' => $iblockTypeId, 'ACTIVE' => 'Y'));
			while ($iblock = $iblockIterator->fetch())
			{
				$result .= '<option value="'.$iblock['ID'].'"'.(($iblock['ID'] == $iblockId) ? ' selected' : '').'>'
					.htmlspecialcharsbx($iblock['NAME']).'</option>';
				if (($defaultIBlockId <= 0) || ($iblock['ID'] == $iblockId))
					$defaultIBlockId = $iblock['ID'];
			}

			$result .= '</optgroup>';
		}
		$result .= '</select><!--__defaultOptionsValue:'.$defaultIBlockId.'--><!--__modifyOptionsPromt:'.Loc::getMessage('UTP_ELIST_DOCUMENT_MOPROMT').'-->';
		$fieldType->setOptions($defaultIBlockId);

		return $result;
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		static::initControlHelpers();
		return parent::renderControlSingle($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlMultiple(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		static::initControlHelpers();
		return parent::renderControlMultiple($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	private function initControlHelpers()
	{
		if (!static::$controlIsRendered)
		{
			Asset::getInstance()->addJs('/bitrix/js/iblock/iblock_edit.js');
			static::$controlIsRendered = true;
		}
	}
}