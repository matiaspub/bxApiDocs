<?php
namespace Bitrix\Currency\Helpers\Admin;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Currency;

Loc::loadMessages(__FILE__);
/**
 * Class RoundEdit
 * Provides various useful methods for admin pages.
 *
 * @package Bitrix\Currency\Helper\Admin
 */
class RoundEdit
{
	/**
	 * Return default round values for admin forms.
	 *
	 * @param bool $dropdownList		Return list for usage in admin pages.
	 * @return array
	 */
	public static function getPresetRoundValues($dropdownList = false)
	{
		$result = array(
			0.0001,
			0.001,
			0.005,
			0.01,
			0.02,
			0.05,
			0.1,
			0.2,
			0.5,
			1,
			2,
			5,
			10,
			20,
			50,
			100,
			200,
			500,
			1000,
			5000
		);
		if (!$dropdownList)
			return $result;
		$list = array();
		foreach ($result as $value)
		{
			$value = (string)$value;
			$list[$value] = $value;
		}
		return $list;
	}

	/**
	 * Prepare admin form data.
	 *
	 * @param array &$fields		Fields for update/add.
	 * @return void
	 */
	public static function prepareFields(array &$fields)
	{
		if (isset($fields['ROUND_TYPE']))
			$fields['ROUND_TYPE'] = (int)$fields['ROUND_TYPE'];
	}
}