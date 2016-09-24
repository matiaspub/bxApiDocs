<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Util;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Assert
{
	// checkers

	/**
	* Method checks if the given argument is an integer value, or can be casted to it
	*
	* @param mixed $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer checked and casted value
	*/
	public final static function expectInteger($arg, $argName = '', $customMsg = '')
	{
		$argInt = intval($arg);
		if($arg != $argInt)
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_INTEGER_EXPECTED', $argName, $customMsg));

		return $argInt;
	}

	/**
	* Method checks if the given argument is a positive integer value, or can be casted to it
	*
	* @param mixed $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer checked and casted value
	*/
	public final static function expectIntegerPositive($arg, $argName = '', $customMsg = '')
	{
		$argInt = intval($arg);
		if($arg != $argInt || $argInt <= 0)
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_INTEGER_NOTNULL_EXPECTED', $argName, $customMsg));

		return $argInt;
	}

	/**
	* Method checks if the given argument is a non-negative integer value, or can be casted to it
	*
	* @param mixed $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer checked and casted value
	*/
	public final static function expectIntegerNonNegative($arg, $argName = '', $customMsg = '')
	{
		$argInt = intval($arg);
		if($arg != $argInt || $argInt < 0)
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_INTEGER_NONNEGATIVE_EXPECTED', $argName, $customMsg));

		return $argInt;
	}

	/**
	* Method checks if the given argument is a non-zero-length string value, or can be casted to it
	*
	* @param mixed $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return string checked and casted value
	*/
	public final static function expectStringNotNull($arg, $argName = '', $customMsg = '')
	{
		if(!strlen($arg))
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_STRING_NOTNULL_EXPECTED', $argName, $customMsg));

		return (string) $arg;
	}

	/**
	* Method checks if the given argument is an array
	*
	* @param mixed[] $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return mixed[] value being checked
	*/
	public final static function expectArray($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg))
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ARRAY_EXPECTED', $argName, $customMsg));

		return $arg;
	}

	/**
	* Method checks if the given argument is a non-empty array
	*
	* @param mixed[] $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return mixed[] value being checked
	*/
	public final static function expectNotEmptyArray($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg) || empty($arg))
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ARRAY_NOT_EMPTY_EXPECTED', $argName, $customMsg));

		return $arg;
	}

	/**
	* Method checks if the given argument is a non-empty array of unique positive integers (or somehow can be casted to it)
	*
	* @param mixed[] $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return integer[] checked and casted value
	*/
	public final static function expectArrayOfUniqueIntegerNotNull($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg))
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ARRAY_EXPECTED', $argName, $customMsg));

		$arg = array_unique(array_values($arg));

		foreach($arg as $k => $v)
		{
			$vInt = intval($v);
			if($v != $vInt || $vInt == 0)
				throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ARRAY_OF_INTEGER_NOT_NULL_EXPECTED', $argName, $customMsg));

			$arg[$k] = $vInt; // it can be casted to integer
		}

		return $arg;
	}

	/**
	* Method checks if the given argument is a non-empty array of unique non-zero-length strings (or somehow can be casted to it)
	*
	* @param mixed[] $arg argument to check
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return string[] checked and casted value
	*/
	public final static function expectArrayOfUniqueStringNotNull($arg, $argName = '', $customMsg = '')
	{
		if(!is_array($arg))
			throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ARRAY_EXPECTED', $argName, $customMsg));

		$arg = array_unique(array_values($arg));

		foreach($arg as $k => $v)
		{
			$v = (string) $v;
			if(!strlen($v))
				throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ARRAY_OF_STRING_NOT_NULL_EXPECTED', $argName, $customMsg));

			$arg[$k] = $v;
		}

		return $arg;
	}

	/**
	* Method checks if the given argument belongs to a set of elements
	*
	* @param mixed[] $arg argument to check
	* @param mixed[] $enum enumeration to check argument belong to
	* @param string $argName argument name to figure in a error message
	* @param string $customMsg custom message to be shown instead of a standard one
	*
	* @throws \Bitrix\Main\ArgumentException
	*
	* @return mixed[] checked and casted value
	*/
	public final static function expectEnumerationMember($arg, $enum = array(), $argName = '', $customMsg = '')
	{
		if(!strlen($arg))
			throw new Main\ArgumentException(Loc::getMessage('SALE_LOCATION_ASSERT_EMPTY_ARGUMENT'));

		if(!is_array($enum) || empty($enum))
			throw new Main\ArgumentException(Loc::getMessage('SALE_LOCATION_ASSERT_EMPTY_ENUMERATION'));

		// we cannot use in_array() here, kz we need for real data type
		foreach($enum as $variant)
		{
			if($variant == $arg)
				return $variant;
		}

		throw new Main\ArgumentException(self::formMessage('SALE_LOCATION_ASSERT_ITEM_NOT_IN_ENUMERATION', $argName, $customMsg));
	}

	// casters

	public static function castTrimLC($value)
	{
		return ToLower(trim($value));
	}

	// announcers

	/**
	* Method announces that method or action is not implemented
	*
	* @param string $msg message to be shown
	*
	* @throws \Bitrix\Main\NotImplementedException
	*/
	public final static function announceNotImplemented($msg = '')
	{
		throw new Main\NotImplementedException($msg);
	}

	/**
	* Method announces that method or action is not supported
	*
	* @param string $msg message to be shown
	*
	* @throws \Bitrix\Main\NotImplementedException
	*/
	public final static function announceNotSupported($msg = '')
	{
		throw new Main\NotSupportedException($msg);
	}

	private final static function formMessage($msgCode, $argName = '', $customMsg = '')
	{
		if(strlen($customMsg))
			return str_replace('#ARG_NAME#', $argName, $customMsg);

		return Loc::getMessage($msgCode, array('#ARG_NAME#' => strlen($argName) ? ' "'.$argName.'" ' : ' '));
	}
}