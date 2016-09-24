<?php

namespace Bitrix\Sale\Internals\Input;

use Bitrix\Main\Event;
use	Bitrix\Main\EventManager,
	Bitrix\Main\SystemException,
	Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventResult;
use Bitrix\Sale\ResultError;

Loc::loadMessages(__FILE__);

// TODO integrate with input.js on adding multiple item

class Manager
{
	static function initJs()
	{
		static $done = false;

		if (! $done)
		{
			$done = true;

			\CJSCore::RegisterExt('input', array(
				'js'   => '/bitrix/js/sale/input.js',
				'lang' => '/bitrix/modules/sale/lang/'.LANGUAGE_ID.'/lib/internals/input.php',
			));
			\CJSCore::Init(array('input'));

			print('<div style="display:none">');
			$GLOBALS['APPLICATION']->IncludeComponent("bitrix:sale.location.selector.".\Bitrix\Sale\Location\Admin\LocationHelper::getWidgetAppearance(), "", array(
				"ID" => '',
				"CODE" => '',
				"INPUT_NAME" => 'SALE_LOCATION_SELECTOR_RESOURCES',
				"PROVIDE_LINK_BY" => 'code',

				"FILTER_BY_SITE" => 'Y',

				"SHOW_DEFAULT_LOCATIONS" => 'Y',
				"SEARCH_BY_PRIMARY" => 'Y',

				"JS_CONTROL_GLOBAL_ID" => 'SALE_LOCATION_SELECTOR_RESOURCES',
				//"INITIALIZE_BY_GLOBAL_EVENT" => 'sale-event-never-happen',
				"USE_JS_SPAWN" => 'Y'
			),
				false,
				array('HIDE_ICONS' => 'Y')
			);
			print('</div>');
		}
	}

	protected static $types = array();

	/** Return html representation of value.
	 * @param array $input - input settings values
	 * @param mixed $value - value to render
	 * @return string - html
	 * @throws SystemException
	 */
	static function getViewHtml(array $input, $value = null)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $input, $value);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/** Return html input control for value.
	 * @param string $name - name for input control (eg: 'email', 'person[1][name]')
	 * @param array $input - input settings values
	 * @param mixed $value - value to render
	 * @return string - html
	 * @throws SystemException
	 */
	static function getEditHtml($name, array $input, $value = null)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $name, $input, $value);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param null $value
	 * @return mixed
	 * @throws SystemException
	 */
	static function getFilterEditHtml($name, array $input, $value = null)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $name, $input, $value);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/** Get user input validation errors.
	 * @param array $input - input settings values
	 * @param string|array|null $value - value to validate
	 * @return array - empty array on success OR array ([error code] => error message) on failure
	 * @throws \Bitrix\Main\SystemException
	 */
	static function getError(array $input, $value)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $input, $value);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/**
	 * @param array $input
	 * @param $value
	 *
	 * @return mixed
	 * @throws SystemException
	 */
	static function getRequiredError(array $input, $value)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
		{
			return call_user_func(array($type['CLASS'], __FUNCTION__), $input, $value);
		}
		else
		{
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
		}
	}

	/** Get normalized user input value (for example to save to database).
	 * Before saving to database check value for errors with Manager::getError!
	 * @param array $input - input settings values
	 * @param string|array|null $value - value to normalize
	 * @return mixed result
	 * @throws \Bitrix\Main\SystemException
	 */
	static function getValue(array $input, $value)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $input, $value);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/** Get multiple value.
	 * @param array $input
	 * @param mixed $value
	 * @return array - if value is single, wrap it in array (except null, which gets empty array)
	 * @throws \Bitrix\Main\SystemException
	 */
	static function asMultiple(array $input, $value)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $value);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/** Get settings inputs for user control.
	 * @param array $input - input settings values
	 * @param string|null $reload - javascript form reload action
	 * @return array - ([setting name] => input array)
	 * @throws \Bitrix\Main\SystemException
	 */
	static function getSettings(array $input, $reload = null)
	{
		if (! static::$initialized)
			static::initialize();

		if ($type = static::$types[$input['TYPE']])
			return call_user_func(array($type['CLASS'], __FUNCTION__), $input, $reload);
		else
			throw new SystemException('invalid input type in '.print_r($input, true), 0, __FILE__, __LINE__);
	}

	/** Get settings, common to all input types.
	 * @param array $input - input settings values
	 * @param string|null $reload - javascript form reload action
	 * @return array - ([setting name] => input array)
	 */
	static function getCommonSettings(array $input, $reload = null)
	{
		if (! static::$initialized)
			static::initialize();

		$typeOptions = array();

		foreach (static::$types as $k => $v)
			$typeOptions[$k] = $v['NAME']." [$k]";

		return array(
			'TYPE'     => array('TYPE' => 'ENUM', 'LABEL' => Loc::getMessage('INPUT_TYPE'), 'OPTIONS' => $typeOptions, 'REQUIRED' => 'Y', 'ONCHANGE' => $reload),
			'REQUIRED' => array('TYPE' => 'Y/N' , 'LABEL' => Loc::getMessage('INPUT_REQUIRED')),
			'MULTIPLE' => array('TYPE' => 'Y/N' , 'LABEL' => Loc::getMessage('INPUT_MULTIPLE'), 'ONCLICK' => $reload),
			'VALUE'    => array('LABEL' => Loc::getMessage('INPUT_VALUE'), 'REQUIRED' => 'N') + $input,
		);
	}

	/** Get all registered types.
	 * @return array - ([TYPE NAME] => array('CLASS', 'NAME', ...))
	 */
	static function getTypes()
	{
		if (! static::$initialized)
			static::initialize();

		return static::$types;
	}

	/** Register new type.
	 * @param string $name - type name
	 * @param array $type - type parameters
	 *      'CLASS' => __NAMESPACE__.'ClassName'
	 *      'NAME' => Loc::getMessage('CLASS_LOCALIZED_NAME')
	 * @return void
	 * @throws SystemException
	 */
	static function register($name, array $type)
	{
		if (static::$types[$name])
			throw new SystemException('duplicate type '.$name, 0, __FILE__, __LINE__);

		if (! class_exists($type['CLASS']))
			throw new SystemException('undefined CLASS in '.print_r($type, true), 0, __FILE__, __LINE__);

		if (! is_subclass_of($type['CLASS'], __NAMESPACE__.'\Base'))
			throw new SystemException($type['CLASS'].' does not implement Input\Base', 0, __FILE__, __LINE__);

		static::$types[$name] = $type;
	}

	protected static $initialized;

	protected function initialize()
	{
		static::$initialized = true;

		/** @var Event $event */
		$event = new Event('sale', 'registerInputTypes', static::$types);
		$event->send();

		if ($event->getResults())
		{
			foreach($event->getResults() as $eventResult)
			{
				if ($eventResult->getType() != EventResult::SUCCESS)
					continue;

				if ($params = $eventResult->getParameters())
				{
					if(!empty($params) && is_array($params))
					{
						static::$types = array_merge(static::$types, $params);
					}
				}
			}
		}
	}
}

abstract class Base
{
	const MULTITAG = 'div';

	/** Check if value is multiple.
	 * @param $value
	 * @return bool
	 */
	static function isMultiple($value)
	{
		return is_array($value);
	}

	/** Get single value.
	 * @param $value
	 * @return mixed - if value is multiple, get first meaningful value (which is not null)
	 */
	static function asSingle($value)
	{
		if (static::isMultiple($value))
		{
			$v = null;

			foreach ($value as $v)
				if ($v) // !== null) TODO maybe??
					break;

			return $v;
		}
		else
		{
			return $value;
		}
	}

	static function asMultiple($value)
	{
		if (static::isMultiple($value))
		{
			return array_filter($value); // TODO maybe??
		}
		else
		{
			return $value === null ? array() : array($value);
		}
	}

	public static function getViewHtml(array $input, $value = null)
	{
		if ($value === null)
			$value = $input['VALUE'];

		if ($input['MULTIPLE'] == 'Y')
		{
			$tag = isset($input['MULTITAG']) ? htmlspecialcharsbx($input['MULTITAG']) : static::MULTITAG;
			list ($startTag, $endTag) = $tag ? array("<$tag>", "</$tag>") : array('', '');

			$html = '';

			foreach (static::asMultiple($value) as $value)
				$html .= $startTag.static::getViewHtmlSingle($input, $value).$endTag;

			return $html;
		}
		else
		{
			return static::getViewHtmlSingle($input, static::asSingle($value));
		}
	}

	public static function getViewHtmlSingle(array $input, $value)
	{
		$output = $valueText = htmlspecialcharsbx($value);
		if ($input['IS_EMAIL'] == 'Y')
		{
			$output = '<a href="mailto:'.$valueText.'">'.$valueText.'</a>';
		}

		return $output;
	}

	public static function getEditHtml($name, array $input, $value = null)
	{
		$name = htmlspecialcharsbx($name);

		if ($value === null)
			$value = $input['VALUE'];

		if ($input['HIDDEN'] == 'Y')
			return static::getHiddenRecursive($name
				, $input['MULTIPLE'] == 'Y' ? static::asMultiple($value) : static::asSingle($value)
				, static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>''), false));

		if ($input['MULTIPLE'] == 'Y')
		{
			$tag = isset($input['MULTITAG']) ? htmlspecialcharsbx($input['MULTITAG']) : static::MULTITAG;
			list ($startTag, $endTag) = $tag ? array("<$tag>", "</$tag>") : array('', '');

			$html = '';

			$index = -1;

			foreach (static::asMultiple($value) as $value)
			{
				$namix = $name.'['.(++$index).']';
				$html .= $startTag
					.static::getEditHtmlSingle($namix, $input, $value)
					.static::getEditHtmlSingleDelete($namix, $input)
					.$endTag;
			}

			$replace = '##INPUT##NAME##';

			if ($input['DISABLED'] !== 'Y') // TODO
				$html .= static::getEditHtmlInsert($tag, $replace, $name
					, static::getEditHtmlSingle($replace, $input, null).static::getEditHtmlSingleDelete($replace, $input)
					, static::getEditHtmlSingleAfterInsert());

			return $html;
		}
		else
		{
			return static::getEditHtmlSingle($name, $input, static::asSingle($value));
		}
	}

	/** @return string */
	public static function getEditHtmlSingle($name, array $input, $value)
	{
		throw new SystemException("you must implement [getEditHtmlSingle] or override [getEditHtml] in yor class", 0, __FILE__, __LINE__);
	}

	public static function getEditHtmlSingleDelete($name, array $input)
	{
		return '<label> '.Loc::getMessage('INPUT_DELETE').' <input type="checkbox" onclick="'

			."this.parentNode.previousSibling.disabled = this.checked;"

			.'"> </label>';
	}

	public static function getEditHtmlInsert($tag, $replace, $name, $sample, $after)
	{
		$name = \CUtil::JSEscape($name);
		$sample = \CUtil::JSEscape(htmlspecialcharsbx($sample));

		return '<input type="button" value="'.Loc::getMessage('INPUT_ADD').'" onclick="'

			."var parent = this.parentNode;"
			."var container = document.createElement('$tag');"
			."container.innerHTML = '$sample'.replace(/$replace/g, '{$name}['+parent.childNodes.length+']');"
			."parent.insertBefore(container, this);"

			.$after.'">';
	}

	public static function getEditHtmlSingleAfterInsert()
	{
		return "container.firstChild.focus();";
	}

	public static function getError(array $input, $value)
	{
		$errors = array();
		if ($value === null)
			$value = $input['VALUE'];

		if ($input['MULTIPLE'] == 'Y')
		{

			$index = -1;

			foreach (static::asMultiple($value) as $value)
			{
				if (($value !== '' && $value !== null) && ($error = static::getErrorSingle($input, $value)))
				{
					$errors[++$index] = $error;
				}
			}
		}
		else
		{
			$value = static::asSingle($value);

			if ($value !== '' && $value !== null)
			{
				return static::getErrorSingle($input, $value);
			}
		}

		return $errors;
	}

	/**
	 * @param array $input
	 * @param $value
	 *
	 * @return bool
	 */
	public static function getRequiredError(array $input, $value)
	{
		$errors = array();

		if ($value === null)
			$value = $input['VALUE'];

		if ($input['MULTIPLE'] == 'Y')
		{
			$index = -1;
			foreach (static::asMultiple($value) as $value)
			{
				if ($value === '' || $value === null)
				{
					if ($input['REQUIRED'] == 'Y')
						$errors[++$index] = array('REQUIRED' => Loc::getMessage('INPUT_REQUIRED_ERROR'));
				}
			}
		}
		else
		{
			$value = static::asSingle($value);

			if ($value === '' || $value === null)
			{
				return ($input['REQUIRED'] == 'Y')
					? array('REQUIRED' => Loc::getMessage('INPUT_REQUIRED_ERROR'))
					: array();
			}
		}

		return $errors;
	}

	/**
	 * @param array $input
	 * @param $value
	 *
	 * @throws SystemException
	 */
	public static function getErrorSingle(array $input, $value)
	{
		throw new SystemException("you must implement [getErrorSingle] or override [getError] in yor class", 0, __FILE__, __LINE__);
	}

	public static function getValue(array $input, $value)
	{
		if ($input['DISABLED'] == 'Y')
			return null; // TODO maybe??

		if ($value === null)
			$value = $input['VALUE'];

		if ($input['MULTIPLE'] == 'Y')
		{
			$values = array();

			foreach (static::asMultiple($value) as $value)
			{
				$value = static::getValueSingle($input, $value);
				if ($value !== null)
					$values []= $value;
			}

			return $values ? $values : null;
		}
		else
		{
			return static::getValueSingle($input, static::asSingle($value));
		}
	}

	public static function getValueSingle(array $input, $value)
	{
		return $value;
	}

	public static function getSettings(array $input, $reload)
	{
		return array(); // no settings
	}

	// utils

	protected static function getHiddenRecursive($name, $value, $attributes)
	{
		if (is_array($value))
		{
			$html = '';

			foreach ($value as $k => $v)
				$html .= self::getHiddenRecursive($name.'['.htmlspecialcharsbx($k).']', $v, $attributes);

			return $html;
		}
		else
		{
			return '<input type="hidden" name="'.$name.'" value="'.htmlspecialcharsbx($value).'"'.$attributes.'>';
		}
	}

	/** @deprecated */
	protected static function extractAttributes(array $input, array $boolean, array $other, $withGlobal = true)
	{
		$string = '';

		// add boolean attributes with predefined values or no value

		unset($boolean['REQUIRED']); // TODO remove with HTML5

		static $globalBoolean = array('CONTENTEDITABLE'=>'', 'DRAGGABLE'=>'true', 'SPELLCHECK'=>'', 'TRANSLATE'=>'yes');

		if ($withGlobal)
			$boolean = $globalBoolean + $boolean;

		foreach (array_intersect_key($input, $boolean) as $k => $v)
			if ($v === 'Y' || $v === true)
				$string .= ' '.strtolower($k).($boolean[$k] ? '="'.$boolean[$k].'"' : '');

		// add event attributes with values
		if ($withGlobal)
		{
			static $globalEvents = array(
				'ONABORT'=>1, 'ONBLUR'=>1, 'ONCANPLAY'=>1, 'ONCANPLAYTHROUGH'=>1, 'ONCHANGE'=>1, 'ONCLICK'=>1,
				'ONCONTEXTMENU'=>1, 'ONDBLCLICK'=>1, 'ONDRAG'=>1, 'ONDRAGEND'=>1, 'ONDRAGENTER'=>1, 'ONDRAGLEAVE'=>1,
				'ONDRAGOVER'=>1, 'ONDRAGSTART'=>1, 'ONDROP'=>1, 'ONDURATIONCHANGE'=>1, 'ONEMPTIED'=>1, 'ONENDED'=>1,
				'ONERROR'=>1, 'ONFOCUS'=>1, 'ONINPUT'=>1, 'ONINVALID'=>1, 'ONKEYDOWN'=>1, 'ONKEYPRESS'=>1, 'ONKEYUP'=>1,
				'ONLOAD'=>1, 'ONLOADEDDATA'=>1, 'ONLOADEDMETADATA'=>1, 'ONLOADSTART'=>1, 'ONMOUSEDOWN'=>1, 'ONMOUSEMOVE'=>1,
				'ONMOUSEOUT'=>1, 'ONMOUSEOVER'=>1, 'ONMOUSEUP'=>1, 'ONMOUSEWHEEL'=>1, 'ONPAUSE'=>1, 'ONPLAY'=>1,
				'ONPLAYING'=>1, 'ONPROGRESS'=>1, 'ONRATECHANGE'=>1, 'ONREADYSTATECHANGE'=>1, 'ONRESET'=>1, 'ONSCROLL'=>1,
				'ONSEEKED'=>1, 'ONSEEKING'=>1, 'ONSELECT'=>1, 'ONSHOW'=>1, 'ONSTALLED'=>1, 'ONSUBMIT'=>1, 'ONSUSPEND'=>1,
				'ONTIMEUPDATE'=>1, 'ONVOLUMECHANGE'=>1, 'ONWAITING'=>1,
			);

			$events = array_intersect_key($input, $globalEvents);
			$other = array_diff_key($other, $events);

			foreach ($events as $k => $v)
				if ($v)
					$string .= ' '.strtolower($k).'="'.$v.'"';
		}

		// add other attributes with values

		static $globalOther = array(
			'ACCESSKEY'=>1, 'CLASS'=>1, 'CONTEXTMENU'=>1, 'DIR'=>1, 'DROPZONE'=>1, 'LANG'=>1, 'STYLE'=>1, 'TABINDEX'=>1,
			'TITLE'=>1, 'ID' => 1,
			'XML:LANG'=>1, 'XML:SPACE'=>1, 'XML:BASE'=>1
		);

		if ($withGlobal)
			$other += $globalOther;

		foreach (array_intersect_key($input, $other) as $k => $v)
			if ($v)
				$string .= ' '.strtolower($k).'="'.htmlspecialcharsbx($v).'"';

		// add data attributes
		if ($withGlobal && is_array($input['DATA']))
		{
			foreach ($input['DATA'] as $k => $v)
				$string .= ' data-'.htmlspecialcharsbx($k).'="'.htmlspecialcharsbx($v).'"';
		}

		return $string;
	}
}

/**
 * String
 */
class StringInput extends Base // String reserved in php 7
{
	public static function getEditHtmlSingle($name, array $input, $value)
	{
		if ($input['MULTILINE'] == 'Y')
		{
			$attributes = static::extractAttributes($input,
				array('DISABLED'=>'', 'READONLY'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>''),
				array('FORM'=>1, 'MAXLENGTH'=>1, 'PLACEHOLDER'=>1, 'DIRNAME'=>1, 'ROWS'=>1, 'COLS'=>1, 'WRAP'=>1));

			return '<textarea name="'.$name.'"'.$attributes.'>'.htmlspecialcharsbx($value).'</textarea>';
		}
		else
		{
			$attributes = static::extractAttributes($input,
				array('DISABLED'=>'', 'READONLY'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>'', 'AUTOCOMPLETE'=>'on'),
				array('FORM'=>1, 'MAXLENGTH'=>1, 'PLACEHOLDER'=>1, 'DIRNAME'=>1, 'SIZE'=>1, 'LIST'=>1, 'PATTERN'=>1));

			return '<input type="text" name="'.$name.'" value="'.htmlspecialcharsbx($value).'"'.$attributes.'>';
		}
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		return static::getEditHtmlSingle($name, $input, $value);
	}

	public static function getErrorSingle(array $input, $value)
	{
		$errors = array();

		$value = trim($value);

		if ($input['MINLENGTH'] && strlen($value) < $input['MINLENGTH'])
			$errors['MINLENGTH'] = Loc::getMessage('INPUT_STRING_MINLENGTH_ERROR', array("#NUM#" => $input['MINLENGTH']));

		if ($input['MAXLENGTH'] && strlen($value) > $input['MAXLENGTH'])
			$errors['MAXLENGTH'] = Loc::getMessage('INPUT_STRING_MAXLENGTH_ERROR', array("#NUM#" => $input['MAXLENGTH']));

		if ($input['PATTERN'] && !preg_match($input['PATTERN'], $value))
			$errors['PATTERN'] = Loc::getMessage('INPUT_STRING_PATTERN_ERROR');

		return $errors;
	}

	static function getSettings(array $input, $reload)
	{
		$settings = array(
			'MINLENGTH' => array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_STRING_MINLENGTH'), 'MIN' => 0, 'STEP' => 1),
			'MAXLENGTH' => array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_STRING_MAXLENGTH'), 'MIN' => 0, 'STEP' => 1),
			'PATTERN'   => array('TYPE' => 'STRING', 'LABEL' => Loc::getMessage('INPUT_STRING_PATTERN'  )),
			'MULTILINE' => array('TYPE' => 'Y/N'   , 'LABEL' => Loc::getMessage('INPUT_STRING_MULTILINE'), 'ONCLICK' => $reload),
		);

		if ($input['MULTILINE'] == 'Y')
		{
			$settings['COLS'] = array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_STRING_SIZE'), 'MIN' => 0, 'STEP' => 1);
			$settings['ROWS'] = array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_STRING_ROWS'), 'MIN' => 0, 'STEP' => 1);
		}
		else
		{
			$settings['SIZE'] = array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_STRING_SIZE'), 'MIN' => 0, 'STEP' => 1);
		}

		return $settings;
	}

	/**
	 * @param $value
	 *
	 * @return bool
	 */
	public static function isDeletedSingle($value)
	{
		return is_array($value) && $value['DELETE'];
	}

}

Manager::register('STRING', array(
	'CLASS' => __NAMESPACE__.'\StringInput',
	'NAME' => Loc::getMessage('INPUT_STRING'),
));

/**
 * Number
 */
class Number extends Base
{
	public static function getEditHtmlSingle($name, array $input, $value)
	{
		// TODO HTML5 from IE10: remove SIZE; Add MIN, MAX, STEP; Change type="number"

		$size = 5;

		if (($s = strlen(strval($input['MIN']))) && $s > $size)
			$size = $s;

		if (($s = strlen(strval($input['MAX']))) && $s > $size)
			$size = $s;

		if (($s = strlen(strval($input['STEP']))) && $s > $size)
			$size = $s;

		$input['SIZE'] = $size;

		$attributes = static::extractAttributes($input,
			array('DISABLED'=>'', 'READONLY'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>'', 'AUTOCOMPLETE'=>'on'),
			array('FORM'=>1, 'LIST'=>1, 'PLACEHOLDER'=>1, 'SIZE'=>1));

		return '<input type="text" name="'.$name.'" value="'.htmlspecialcharsbx($value).'"'.$attributes.'>';
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		return static::getEditHtmlSingle($name, $input, $value);
	}

	public static function getErrorSingle(array $input, $value)
	{
		$errors = array();

		if (is_numeric($value))
		{
			$value = (double) $value;

			if (!empty($input['MIN']) && $value < $input['MIN'])
				$errors['MIN'] = Loc::getMessage('INPUT_NUMBER_MIN_ERROR', array("#NUM#" => $input['MIN']));

			if (!empty($input['MAX']) && $value > $input['MAX'])
				$errors['MAX'] = Loc::getMessage('INPUT_NUMBER_MAX_ERROR', array("#NUM#" => $input['MAX']));

			if ($input['STEP'])
			{
				$step = (double) $input['STEP'];

				$value = (double) abs($value - ($input['MIN'] ? $input['MIN'] : 0.0));

				if (! ($value / pow(2.0, 53) > $step))
				{
					$remainder = (double) abs($value - $step * round($value / $step));
					$acceptableError = (double) ($step / pow(2.0, 24));

					if ($acceptableError < $remainder && ($step - $acceptableError) > $remainder)
						$errors['STEP'] = Loc::getMessage('INPUT_NUMBER_STEP_ERROR', array("#NUM#" => $input['STEP']));
				}
			}
		}
		else
		{
			$errors['NUMERIC'] = Loc::getMessage('INPUT_NUMBER_NUMERIC_ERROR');
		}

		return $errors;
	}

	public static function getSettings(array $input, $reload)
	{
		return array(
			'MIN'  => array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_NUMBER_MIN' )),
			'MAX'  => array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_NUMBER_MAX' )),
			'STEP' => array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_NUMBER_STEP')),
		);
	}
}

Manager::register('NUMBER', array(
	'CLASS' => __NAMESPACE__.'\Number',
	'NAME' => Loc::getMessage('INPUT_NUMBER'),
));

/**
 * Either Y or N
 */
class EitherYN extends Base
{
	public static function getViewHtmlSingle(array $input, $value)
	{
		return $value == 'Y' ? Loc::getMessage('INPUT_EITHERYN_Y') : Loc::getMessage('INPUT_EITHERYN_N');
	}

	public static function getEditHtmlSingle($name, array $input, $value)
	{
		$hiddenAttributes = static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>1), false);

		$checkboxAttributes = static::extractAttributes($input, array('DISABLED'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>''), array('FORM'=>1));

		return '<input type="hidden" name="'.$name.'" value="N"'.$hiddenAttributes.'>'
			.'<input type="checkbox" name="'.$name.'" value="Y"'.($value == 'Y' ? ' checked' : '').$checkboxAttributes.'>';
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		$hiddenAttributes = static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>1), false);

		$checkboxAttributes = static::extractAttributes($input, array('DISABLED'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>''), array('FORM'=>1));

		return '<select name="'.$name.'" '.$hiddenAttributes.'>
					<option value="">'.Loc::getMessage('INPUT_EITHERYN_ALL').'</option>
					<option value="Y"'.($value=="Y" ? " selected" : '').' '.$checkboxAttributes.'>'.Loc::getMessage('INPUT_EITHERYN_Y').'</option>
					<option value="N"'.($value=="N" ? " selected" : '').' '.$checkboxAttributes.'>'.Loc::getMessage('INPUT_EITHERYN_N').'</option>
				</select>';
	}

	public static function getErrorSingle(array $input, $value)
	{
		if ($input['REQUIRED'] == 'Y' && ($value === '' || $value === null))
			return array('REQUIRED' => Loc::getMessage('INPUT_REQUIRED_ERROR'));

		return ($value == 'N' || $value == 'Y')
			? array()
			: array('INVALID' => Loc::getMessage('INPUT_INVALID_ERROR'));
	}

	public static function getValueSingle(array $input, $value)
	{
		return $value == 'Y' ? 'Y' : 'N';
	}
}

Manager::register('Y/N', array(
	'CLASS' => __NAMESPACE__.'\EitherYN',
	'NAME'  => Loc::getMessage('INPUT_EITHERYN'),
));

/**
 * Enumeration
 */
class Enum extends Base
{
	private static function flatten(array $array)
	{
		$result = array();

		foreach ($array as $key => $value)
		{
			if (is_array($value))
				$result = array_merge($result, $value);
			else
				$result[$key] = $value;
		}

		return $result;
	}

	public static function getViewHtmlSingle(array $input, $value) // TODO optimize to getViewHtml
	{
		$options = $input['OPTIONS'];

		if (is_array($options))
		{
			$options = self::flatten($options);

			if ($v = $options[$value])
				$value = $v;
		}

		return htmlspecialcharsbx($value);
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		return static::getEditHtml($name, $input, $value);
	}

	public static function getEditHtml($name, array $input, $value = null)
	{
		$options = $input['OPTIONS'];

		if (! is_array($options))
			return Loc::getMessage('INPUT_ENUM_OPTIONS_ERROR');

		$multiple = $input['MULTIPLE'] == 'Y';

		$name = htmlspecialcharsbx($name);

		if ($value === null && isset($input['VALUE']))
			$value = $input['VALUE'];

		if ($input['HIDDEN'] == 'Y')
			return static::getHiddenRecursive($name
				, $multiple ? static::asMultiple($value) : static::asSingle($value)
				, static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>1), false));

		if ($value === null)
			$value = array();
		else
			$value = $multiple ? array_flip(static::asMultiple($value)) : array(static::asSingle($value) => true);

		$html = '';

		if ($input['MULTIELEMENT'] == 'Y')
		{
			$tag = isset($input['MULTITAG']) ? htmlspecialcharsbx($input['MULTITAG']) : static::MULTITAG;
			list ($startTag, $endTag) = $tag ? array("<$tag>", "</$tag>") : array('', '');

			$attributes = static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>1), false);

			$type = 'radio';

			if ($multiple)
			{
				$type = 'checkbox';
				$name .= '[]';
			}

			$html .= self::getEditOptionsHtml($options, $value, ' checked',
				'<fieldset><legend>{GROUP}</legend>{OPTIONS}</fieldset>',
				$startTag.'<label><input type="'.$type.'" name="'.$name.'" value="{VALUE}"{SELECTED}'.$attributes.'> {TEXT} </label>'.$endTag
			);
		}
		else // select
		{
			$attributes = static::extractAttributes($input, array('DISABLED'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>''), array('FORM'=>1, 'SIZE'=>1));

			$html .= '<select'.$attributes.' name="'.$name.($multiple ? '[]" multiple>' : '">');

			$html .= self::getEditOptionsHtml($options, $value, ' selected',
				'<optgroup label="{GROUP}">{OPTIONS}</optgroup>',
				'<option value="{VALUE}"{SELECTED}>{TEXT}</option>'
			);

			$html .= '</select>';
		}

		return $html;
	}

	private static function getEditOptionsHtml(array $options, array $selected, $selector, $group, $option)
	{
		$result = '';

		foreach ($options as $key => $value)
		{
			$result .= is_array($value)
				? str_replace(
					array('{GROUP}', '{OPTIONS}'),
					array(
						htmlspecialcharsbx($key),
						self::getEditOptionsHtml($value, $selected, $selector, $group, $option),
					),
					$group
				)
				: str_replace(
					array('{VALUE}', '{SELECTED}', '{TEXT}'),
					array(
						htmlspecialcharsbx($key),
						isset($selected[$key]) ? $selector : '',
						htmlspecialcharsbx($value) ?: htmlspecialcharsbx($key),
					),
					$option
				);
		}

		return $result;
	}

	public static function getErrorSingle(array $input, $value)  // TODO optimize to getError
	{
		$options = $input['OPTIONS'];

		if (is_array($options))
		{
			$options = self::flatten($options);

			return isset($options[$value])
				? array()
				: array('INVALID' => Loc::getMessage('INPUT_INVALID_ERROR'));
		}
		else
		{
			return array('OPTIONS' => Loc::getMessage('INPUT_ENUM_OPTIONS_ERROR'));
		}
	}

	static function getSettings(array $input, $reload)
	{
		$settings = array(
			// TODO maybe??? 'OPTIONS' => array('TYPE' => 'TUPLE'),
			'MULTIELEMENT' => array('TYPE' => 'Y/N', 'LABEL' => Loc::getMessage('INPUT_ENUM_MULTIELEMENT'), 'ONCLICK' => $reload),
		);

		if ($input['MULTIELEMENT'] != 'Y')
			$settings['SIZE'] = array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_ENUM_SIZE'), 'MIN' => 0, 'STEP' => 1);

		return $settings;
	}
}

Manager::register('ENUM', array(
	'CLASS' => __NAMESPACE__.'\Enum',
	'NAME' => Loc::getMessage('INPUT_ENUM'),
));

/**
 * File
 * Must use: File::getPostWithFiles before using this type!
 */
class File extends Base
{
	/** Normalize $_FILES structure and join it with $_POST.
	 * Must be called before using File type!
	 *
	 * PHP default $_FILES structure:
	 *
	 * Array([name]     => Array([0] => photo.jpg     , [1] =>  )
	 *       [type]     => Array([0] => image/jpeg    , [1] =>  )
	 *       [tmp_name] => Array([0] => /tmp/dsk4le5se, [1] =>  )
	 *       [error]    => Array([0] => 0             , [1] => 4)
	 *       [size]     => Array([0] => 45673         , [1] => 0))
	 *
	 * Normalized files structure:
	 *
	 * Array(
	 *     [0] => Array(
	 *         [name]     => photo.jpg
	 *         [type]     => image/jpeg
	 *         [tmp_name] => /tmp/dsk4le5se
	 *         [error]    => 0
	 *         [size]     => 45673
	 *     )
	 *     [1] => Array(
	 *         [name]     =>
	 *         [type]     =>
	 *         [tmp_name] =>
	 *         [error]    => 4
	 *         [size]     =>
	 *     )
	 * )
	 *
	 * Example: <input type="file" name="PROFILE[5][PHOTOS][]"> - will be normalized as expected in post
	 *
	 * @param array $post  - $_POST
	 * @param array $files - $_FILES
	 * @return array       - post + fixed files
	 */
	static function getPostWithFiles(array $post, array $files)
	{
		foreach ($files as $key => $file)
		{
			if (! is_array($post[$key]))
				$post[$key] = array();

			foreach ($file as $property => $value)
			{
				if (is_array($value))
					self::getPostWithFilesRecursive($post[$key], $value, $property);
				else
					$post[$key][$property] = $value;
			}
		}

		return $post;
	}

	private static function getPostWithFilesRecursive(array &$root, array $values, $property)
	{
		foreach ($values as $key => $value)
		{
			if (! is_array($root[$key]))
				$root[$key] = array();

			if (is_array($value))
				self::getPostWithFilesRecursive($root[$key], $value, $property);
			else
				$root[$key][$property] = $value;
		}
	}

	/** @deprecated
	 * Load file array from database.
	 * @param $value
	 * @return array - file array
	 */
	static function loadInfo($value)
	{
		if (! $multiple = static::isMultiple($value))
			$value = array($value);

		foreach ($value as &$file)
			$file = self::loadInfoSingle($file);

		return $multiple ? $value : reset($value);
	}

	/** deprecated */
	static function loadInfoSingle($file)
	{
		if (is_array($file))
		{
			if ($file['SRC'])
				return $file; // already loaded

			$fileId = $file['ID'];
		}
		else
		{
			$fileId = $file;
		}

		if ($fileId && is_numeric($fileId) && ($row = \CFile::GetFileArray($fileId)))
		{
			$file = (is_array($file) ? $file : array('ID' => $fileId)) + $row;
		}

		return $file;
	}

	/** Check if file is marked for deletion.
	 * @param $value
	 * @return bool
	 */
	static function isDeletedSingle($value)
	{
		return is_array($value) && $value['DELETE'];
	}

	/** Check if file is uploaded.
	 * @param $value
	 * @return bool
	 */
	static function isUploadedSingle($value)
	{
		return is_array($value) && $value['error'] == UPLOAD_ERR_OK && is_uploaded_file($value['tmp_name']);
	}

	// input methods ///////////////////////////////////////////////////////////////////////////////////////////////////

	static function isMultiple($value)
	{
		return is_array($value) && ! isset($value['ID']);
	}

	public static function getViewHtmlSingle(array $input, $value)
	{
		if (! is_array($value))
			$value = array('ID' => $value);

		if ($src = $value['SRC'])
		{
			$attributes = ' href="'.htmlspecialcharsbx($src).'" title="'.htmlspecialcharsbx(Loc::getMessage('INPUT_FILE_DOWNLOAD')).'"';

			$content = \CFile::IsImage($src, $value['CONTENT_TYPE']) && $value['FILE_SIZE'] < 100000
				? '<img src="'.$src.'" border="0" alt="" style="max-height:100px; max-width:100px">'
				: $value['ORIGINAL_NAME'];
		}
		else
		{
			$attributes = '';
			$content = $value['ORIGINAL_NAME'];
		}

		if (! $content)
			$content = $value['FILE_NAME'];

		if (! $content)
			$content = $value['ID'];

		return "<a$attributes>$content</a>";
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		return static::getEditHtmlSingle($name, $input, $value);
	}

	public static function getEditHtmlSingle($name, array $input, $value)
	{
		if (! is_array($value))
		{
			$value = array('ID' => $value);
		}

		if ($value['DELETE'])
		{
			unset($value['ID']);
		}

		$input['ONCHANGE'] =
			"var anchor = this.previousSibling.previousSibling;".
			"if (anchor.firstChild) anchor.removeChild(anchor.firstChild);".
			"anchor.appendChild(document.createTextNode(this.value.split(/(\\\\|\\/)/g).pop()));".
			$input['ONCHANGE'];

		// TODO HTML5 add MULTIPLE
		$fileAttributes = static::extractAttributes($input,
			array('DISABLED'=>'', 'AUTOFOCUS'=>'', 'REQUIRED'=>''),
			array('FORM'=>1, 'ACCEPT'=>1));

		$otherAttributes = static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>1), false);

		return static::getViewHtmlSingle($input, $value)
			.'<input type="hidden" name="'.$name.'[ID]" value="'.htmlspecialcharsbx($value['ID']).'"'.$otherAttributes.'>'
			.'<input type="file" name="'.$name.'" style="position:absolute; visibility:hidden"'.$fileAttributes.'>'
			.'<input type="button" value="'.Loc::getMessage('INPUT_FILE_BROWSE').'" onclick="this.previousSibling.click()">'
			.(
				$input['NO_DELETE']
					? ''
					: '<label> '.Loc::getMessage('INPUT_DELETE').' <input type="checkbox" name="'.$name.'[DELETE]" onclick="'

						."var button = this.parentNode.previousSibling, file = button.previousSibling;"
						."button.disabled = file.disabled = this.checked;"

						.'"'.$otherAttributes.'> </label>'
			);
	}

	public static function getEditHtmlSingleDelete($name, array $input)
	{
		return '';
	}

	public static function getErrorSingle(array $input, $value)
	{
		if (is_array($value))
		{
			if ($value['DELETE'])
			{
				return $input['REQUIRED'] == 'Y'
					? array('REQUIRED' => Loc::getMessage('INPUT_REQUIRED_ERROR'))
					: array();
			}
			elseif (is_uploaded_file($value['tmp_name']))
			{
				$errors = array();

				if ($input['MAXSIZE'] && $value['size'] > $input['MAXSIZE'])
					$errors['MAXSIZE'] = Loc::getMessage('INPUT_FILE_MAXSIZE_ERROR');

				// TODO check: file name, mime type, extension
				//$info = pathinfo($value['name']);

				if ($error = \CFile::CheckFile($value, 0, false, $input['ACCEPT']))
					$errors['CFILE'] = $error;

				return $errors;
			}
			else
			{
				switch ($value['error'])
				{
					case UPLOAD_ERR_OK: return array();	//file uploaded successfully

					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE: return array('MAXSIZE' => Loc::getMessage('INPUT_FILE_MAXSIZE_ERROR'));

					case UPLOAD_ERR_PARTIAL: return array('PARTIAL' => Loc::getMessage('INPUT_FILE_PARTIAL_ERROR'));

					case UPLOAD_ERR_NO_FILE:

						return $input['REQUIRED'] == 'Y' && (! is_numeric($value['ID']) || $value['DELETE'])
							? array('REQUIRED' => Loc::getMessage('INPUT_REQUIRED_ERROR'))
							: array();

					// TODO case UPLOAD_ERR_NO_TMP_DIR  UPLOAD_ERR_CANT_WRITE  UPLOAD_ERR_EXTENSION
					default: return array('INVALID' => Loc::getMessage('INPUT_INVALID_ERROR'));
				}
			}
		}
		elseif (is_numeric($value))
		{
			// TODO check if file id exists maybe ???
			return array();
		}
		else
		{
			return array('INVALID' => Loc::getMessage('INPUT_INVALID_ERROR'));
		}
	}

	public static function getValueSingle(array $input, $value)
	{
		if (is_array($value))
		{
			if ($value['DELETE'])
				return null;

			$value = $value['ID'];
		}

		return is_numeric($value) ? $value : null;
	}

	public static function getSettings(array $input, $reload)
	{
		return array(
			'MAXSIZE' => array('TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('INPUT_FILE_MAXSIZE'), 'MIN' => 0, 'STEP' => 1),
			'ACCEPT'  => array('TYPE' => 'STRING', 'LABEL' => Loc::getMessage('INPUT_FILE_ACCEPT' ), 'PLACEHOLDER' => 'png, doc, zip'),
		);
	}
}

Manager::register('FILE', array(
	'CLASS' => __NAMESPACE__.'\File',
	'NAME' => Loc::getMessage('INPUT_FILE'),
));

/**
 * Date
 */
class Date extends Base
{
	public static function getEditHtmlSingle($name, array $input, $value)
	{
		$showTime = $input['TIME'] == 'Y';

		// TODO HTML5 input="date|datetime|datetime-local" & min & max & step(date:integer|datetime..:float)

		$textAttributes = static::extractAttributes($input,
			array('DISABLED'=>'', 'AUTOCOMPLETE'=>'on', 'AUTOFOCUS'=>'', 'READONLY'=>'',  'REQUIRED'=>''),
			array('FORM'=>1, 'LIST'=>1));

		$buttonAttributes = static::extractAttributes($input, array('DISABLED'=>''), array(), false);

		return '<input type="text" name="'.$name.'" size="'.($showTime ? 20 : 10).'" value="'.htmlspecialcharsbx($value).'"'.$textAttributes.'>'
			.'<input type="button" value="'.Loc::getMessage('INPUT_DATE_SELECT').'"'.$buttonAttributes.' onclick="'
			."BX.calendar({node:this, field:'$name', form:'', bTime:".($showTime ? 'true' : 'false').", bHideTime:false});"
			.'">';
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		return static::getEditHtmlSingle($name, $input, $value);
	}

	public static function getEditHtmlSingleDelete($name, array $input)
	{
		return '<label> '.Loc::getMessage('INPUT_DELETE').' <input type="checkbox" onclick="'

		."var disabled = this.checked;"
		."var button = this.parentNode.previousSibling;"
		."button.disabled = disabled;"
		."button.previousSibling.disabled = disabled;"

		.'"> </label>';
	}

	public static function getErrorSingle(array $input, $value)
	{
		return CheckDateTime($value, FORMAT_DATE)
			? array()
			: array('INVALID' => Loc::getMessage('INPUT_INVALID_ERROR'));
	}

	static function getSettings(array $input, $reload)
	{
		return array(
			'TIME' => array('TYPE' => 'Y/N', 'LABEL' => Loc::getMessage('INPUT_DATE_TIME'), 'ONCLICK' => $reload),
			// TODO min, max, step
		);
	}
}

Manager::register('DATE', array(
	'CLASS' => __NAMESPACE__.'\Date',
	'NAME' => Loc::getMessage('INPUT_DATE'),
));

/**
 * Location
 */
class Location extends Base
{
	public static function getViewHtmlSingle(array $input, $value)
	{
		if((string) $value == '')
			return '';

		try
		{
			$result = \Bitrix\Sale\Location\LocationTable::getPathToNodeByCode($value, array(
				'select' => array('CHAIN' => 'NAME.NAME'),
				'filter' => array('NAME.LANGUAGE_ID' => LANGUAGE_ID),
			));

			$path = array();

			while($row = $result->fetch())
				$path[] = $row['CHAIN'];

			return htmlspecialcharsbx(implode(', ', $path));
		}
		catch(\Bitrix\Main\SystemException $e)
		{
			return '';
		}
	}

	/**
	 * @param $name
	 * @param array $input
	 * @param $value
	 * @return string
	 */
	public static function getFilterEditHtml($name, array $input, $value)
	{
		return static::getEditHtml($name, $input, $value);
	}

	public static function getEditHtml($name, array $input, $value = null)
	{
		$name = htmlspecialcharsbx($name);

		if ($value === null)
			$value = $input['VALUE'];

		if ($input['HIDDEN'] == 'Y')
			return static::getHiddenRecursive($name
				, $input['MULTIPLE'] == 'Y' ? static::asMultiple($value) : static::asSingle($value)
				, static::extractAttributes($input, array('DISABLED'=>''), array('FORM'=>1), false));

		$html = '';

		$selector = md5("location input selector $name");
		$input["LOCATION_SELECTOR"] = $selector;

		if ($onChange = $input['ONCHANGE'])
		{
			$functionName = $selector.'OnLocationChange';
			$html .= "<script>function $functionName (this){ $onChange }</script>";
			$input['JS_CALLBACK'] = $functionName;
		}
		else
		{
			$input['JS_CALLBACK'] = null;
		}

		if ($input['MULTIPLE'] == 'Y')
		{
			$tag = isset($input['MULTITAG']) ? htmlspecialcharsbx($input['MULTITAG']) : static::MULTITAG;
			list ($startTag, $endTag) = $tag ? array("<$tag>", "</$tag>") : array('', '');

			$index = -1;

			foreach (static::asMultiple($value) as $value)
				$html .= $startTag
					.static::getEditHtmlSingle($name.'['.(++$index).']', $input, $value)
					.$endTag;

			$replace = '##INPUT##NAME##';

			if ($input['DISABLED'] !== 'Y') // TODO
				$html .= static::getEditHtmlInsert($tag, $replace, $name
					, static::getEditHtmlSingle($replace, $input, null)
					, "var location = BX.locationSelectors['$selector'].spawn(container, {selectedItem: false, useSpawn: false});"
					."location.clearSelected();"
					//."location.focus();" // TODO
				);
		}
		else
		{
			$html .= static::getEditHtmlSingle($name, $input, static::asSingle($value));
		}

		return $html;
	}

	public static function getEditHtmlSingle($name, array $input, $value)
	{
		$filterMode = isset($input['IS_FILTER_FIELD']) && $input['IS_FILTER_FIELD'] === true;
		$parameters = array(
			'CODE' => $value,
			'INPUT_NAME' => $name,
			'PROVIDE_LINK_BY' => 'code',
			'SELECT_WHEN_SINGLE' => 'N',
			'FILTER_BY_SITE' => 'N',
			'SHOW_DEFAULT_LOCATIONS' => 'N',
			'SEARCH_BY_PRIMARY' => 'N',
			'JS_CONTROL_GLOBAL_ID' => $input["LOCATION_SELECTOR"],
			'JS_CALLBACK' => $input['JS_CALLBACK']
		);

		ob_start();

		if($filterMode)
		{
			print('<div style="width: 100%; margin-left: 12px">');
			$parameters['INITIALIZE_BY_GLOBAL_EVENT'] = 'onAdminFilterInited'; // this allows js logic to be initialized after admin filter
			$parameters['GLOBAL_EVENT_SCOPE'] = 'window';
		}

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:sale.location.selector.'.($filterMode ? 'search' : \Bitrix\Sale\Location\Admin\Helper::getWidgetAppearance()),
			'',
			$parameters,
			false
		);

		if($filterMode)
		{
			print('</div>');
		}

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public static function getErrorSingle(array $input, $value)
	{
		return \Bitrix\Sale\Location\LocationTable::getByCode($value)->fetch()
			? array()
			: array('INVALID' => Loc::getMessage('INPUT_INVALID_ERROR'));
	}
}

Manager::register('LOCATION', array(
	'CLASS' => __NAMESPACE__.'\Location',
	'NAME' => Loc::getMessage('INPUT_LOCATION'),
));