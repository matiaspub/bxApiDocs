<?php

namespace Bitrix\Bizproc\BaseType;

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class InternalSelect
 * @package Bitrix\Iblock\BizprocType
 */
class InternalSelect extends Select
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::INTERNALSELECT;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $callbackFunctionName Client callback function name.
	 * @param mixed $value Field value.
	 * @return string
	 */
	public static function renderControlOptions(FieldType $fieldType, $callbackFunctionName, $value)
	{
		$result = '';
		$selectedField = $fieldType->getOptions();

		$fields = self::getDocumentSelectFields($fieldType, true);
		if (!empty($fields))
		{
			$result .= '<select onchange="'.htmlspecialcharsbx($callbackFunctionName).'(this.options[this.selectedIndex].value)">';

			$fieldsNames = array_keys($fields);
			if (!in_array($selectedField, $fieldsNames))
				$selectedField = isset($fieldsNames[0]) ? $fieldsNames[0] : '';

			foreach ($fields as $name => $field)
			{
				$result .= '<option value="'.htmlspecialcharsbx($name).'"'.(($selectedField == $name) ? " selected" : "").'>'
					.htmlspecialcharsbx($field["Name"]).'</option>';
			}
			$result .= '</select>';
		}
		$result .= '<!--__defaultOptionsValue:'.$selectedField.'--><!--__modifyOptionsPromt:'.Loc::getMessage('BPDT_INTERNALSELECT_OPT_LABEL').'-->';
		$fieldType->setOptions($selectedField);

		return $result;
	}

	/**
	 * @param FieldType $fieldType
	 * @return array
	 */
	protected static function getFieldOptions(FieldType $fieldType)
	{
		$optionsValue = $fieldType->getOptions();

		$fields = self::getDocumentSelectFields($fieldType);
		$options = array();

		if (isset($fields[$optionsValue]['Options']))
		{
			$options = $fields[$optionsValue]['Options'];
		}

		return static::normalizeOptions($options);
	}

	/**
	 * @param FieldType $fieldType
	 * @param bool $ignoreAliases
	 * @return array
	 */
	private static function getDocumentSelectFields(FieldType $fieldType, $ignoreAliases = false)
	{
		$runtime = \CBPRuntime::getRuntime();
		$runtime->startRuntime();
		$documentService = $runtime->getService("DocumentService");

		$result = array();
		$fields = $documentService->getDocumentFields($fieldType->getDocumentType());
		foreach ($fields as $key => $field)
		{
			if ($field['Type'] == 'select' && substr($key, -10) != '_PRINTABLE')
			{
				$result[$key] = $field;
				if (isset($field['Alias']) && !$ignoreAliases)
					$result[$field['Alias']] = $field;
			}
		}
		return $result;
	}
}