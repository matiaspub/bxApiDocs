<?php
namespace Bitrix\Sale\Helpers\Admin;

class FormRecovery
{
	public static function getDataFromForm(array $collectionAvailableFields, array $fieldsFromForm, array $specialFields)
	{
		$result = array();
		foreach ($fieldsFromForm as $fieldKey => $fieldValue)
			if (in_array($fieldKey, $collectionAvailableFields) && !in_array($fieldKey, $specialFields))
				$result[$fieldKey] = $fieldValue;

		return $result;
	}

}