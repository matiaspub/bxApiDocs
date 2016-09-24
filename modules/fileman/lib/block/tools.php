<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Fileman\Block;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Tools
{
	/**
	 * @return string
	 */
	public static function getControlInput()
	{
		return '<input type="text" data-bx-editor-tool-input="item" value="">';
	}

	/**
	 * @return string
	 */
	public static function getControlSelect(array $optionList, $haveDefault = true)
	{
		if($haveDefault)
		{
			$optionList = array_merge(array('' => Loc::getMessage('BLOCK_EDITOR_COMMON_DEFAULT')), $optionList);
		}

		$options = '';
		foreach($optionList as $value => $name)
		{
			$value = htmlspecialcharsbx($value);
			$name = htmlspecialcharsbx($name);
			$options .= '<option value="' . $value . '">' . $name . '</option>';
		}
		return '<select data-bx-editor-tool-input="item">' .$options . '</select>';
	}

	/**
	 * @return string
	 */
	public static function getControlColor()
	{
		static $includeColorPicker = false;
		if(!$includeColorPicker)
		{
			$GLOBALS['APPLICATION']->IncludeComponent('bitrix:main.colorpicker', '');
			$includeColorPicker = true;
		}

		return '<input type="text" data-bx-editor-tool-input="item" class="bx-editor-color-picker">' .
			'<span class="bx-editor-color-picker-view"></span>';
	}

	/**
	 * @return string
	 */
	public static function getControlPaddingBottoms()
	{
		$options = array(0, 5, 10, 15, 20, 25, 30, 40, 50, 60, 70, 80, 100, 120, 140, 160);

		$optionList = array();
		foreach($options as $v)
		{
			$v .= 'px';
			$optionList[$v] = $v;
		}

		return static::getControlSelect($optionList);
	}

	/**
	 * @return string
	 */
	public static function getControlBorderRadius()
	{
		$optionList = array(
			'' => Loc::getMessage('BLOCK_EDITOR_CTRL_BORDER_RADIUS_SQUARE'),
			'4px' => Loc::getMessage('BLOCK_EDITOR_CTRL_BORDER_RADIUS_ROUND1'),
			'7px' => Loc::getMessage('BLOCK_EDITOR_CTRL_BORDER_RADIUS_ROUND2'),
			'10px' => Loc::getMessage('BLOCK_EDITOR_CTRL_BORDER_RADIUS_ROUND3'),
			'15px' => Loc::getMessage('BLOCK_EDITOR_CTRL_BORDER_RADIUS_ROUND4'),
		);
		return static::getControlSelect($optionList, false);
	}

	/**
	 * @return string
	 */
	public static function getControlTextDecoration()
	{
		$optionList = array(
			'none' => Loc::getMessage('BLOCK_EDITOR_COMMON_NO'),
			'underline' => Loc::getMessage('BLOCK_EDITOR_CTRL_TEXT_DECORATION_UNDERLINE'),
			'overline' => Loc::getMessage('BLOCK_EDITOR_CTRL_TEXT_DECORATION_OVERLINE'),
			'line-through' => Loc::getMessage('BLOCK_EDITOR_CTRL_TEXT_DECORATION_THROUGH'),
		);
		return static::getControlSelect($optionList);
	}

	/**
	 * @return string
	 */
	public static function getControlTextAlign()
	{
		$optionList = array(
			'left' => Loc::getMessage('BLOCK_EDITOR_CTRL_TEXT_ALIGN_LEFT'),
			'center' => Loc::getMessage('BLOCK_EDITOR_CTRL_TEXT_ALIGN_CENTER'),
			'right' => Loc::getMessage('BLOCK_EDITOR_CTRL_TEXT_ALIGN_RIGHT'),
		);
		return static::getControlSelect($optionList, false);
	}

	/**
	 * @return string
	 */
	public static function getControlAlign()
	{
		$optionList = array(
			'left' => Loc::getMessage('BLOCK_EDITOR_CTRL_ALIGN_LEFT'),
			'top' => Loc::getMessage('BLOCK_EDITOR_CTRL_ALIGN_TOP'),
			'right' => Loc::getMessage('BLOCK_EDITOR_CTRL_ALIGN_RIGHT'),
			'bottom' => Loc::getMessage('BLOCK_EDITOR_CTRL_ALIGN_BOTTOM'),
		);
		return static::getControlSelect($optionList, false);
	}

	/**
	 * @return string
	 */
	public static function getControlFontFamily()
	{
		$optionList = array(
			'\'Times New Roman\', Times' => 'Times New Roman',
			'\'Courier New\'' => 'Courier New',
			'Arial, Helvetica' => 'Arial / Helvetica',
			'\'Arial Black\', Gadget' => 'Arial Black',
			'Tahoma, Geneva' => 'Tahoma / Geneva',
			'Verdana' => 'Verdana',
			'Georgia, serif' => 'Georgia',
			'monospace' => 'monospace',
		);
		return static::getControlSelect($optionList, false);
	}

	/**
	 * @return string
	 */
	public static function getControlLineHeight()
	{
		$options = array(5, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 35, 40, 45, 50);

		$optionList = array();
		foreach($options as $v)
		{
			$v .= 'px';
			$optionList[$v] = $v;
		}

		return static::getControlSelect($optionList);
	}

	/**
	 * @return string
	 */
	public static function getControlFontWeight()
	{
		$optionList = array(
			'normal' => Loc::getMessage('BLOCK_EDITOR_CTRL_FONT_WEIGHT_NORMAL'),
			'bold' => Loc::getMessage('BLOCK_EDITOR_CTRL_FONT_WEIGHT_BOLD'),
			'100' => '100',
			'200' => '200',
			'300' => '300',
			'400' => '400',
			'500' => '500',
			'600' => '600',
			'700' => '700',
			'800' => '800',
			'900' => '900',
		);
		return static::getControlSelect($optionList);
	}

	/**
	 * @return string
	 */
	public static function getControlFontSize()
	{
		$options = array(6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22, 24, 26, 28, 30, 35, 40, 45, 50);

		$optionList = array();
		foreach($options as $v)
		{
			$v .= 'px';
			$optionList[$v] = $v;
		}
		return static::getControlSelect($optionList);
	}
}