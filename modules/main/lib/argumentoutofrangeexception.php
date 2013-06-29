<?php
namespace Bitrix\Main;

//use \Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

/**
 * Exception is thrown when the value of an argument is outside the allowable range of values.
 */
class ArgumentOutOfRangeException
	extends ArgumentException
{
	protected $lowerLimit;
	protected $upperLimit;

	/**
	 * Creates new exception object.
	 *
	 * @param string $parameter Argument that generates exception
	 * @param null $lowerLimit Either lower limit of the allowable range of values or an array of allowable values
	 * @param null $upperLimit Upper limit of the allowable values
	 * @param \Exception $previous
	 */
	public function __construct($parameter, $lowerLimit = null, $upperLimit = null, \Exception $previous = null)
	{
		if (is_array($lowerLimit))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: %s", $parameter, implode(", ", $lowerLimit));
		elseif (($lowerLimit !== null) && ($upperLimit !== null))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: from %s to %s", $parameter, $lowerLimit, $upperLimit);
		elseif (($lowerLimit === null) && ($upperLimit !== null))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: not greater than %s", $parameter, $upperLimit);
		elseif (($lowerLimit !== null) && ($upperLimit === null))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: not less than %s", $parameter, $lowerLimit);
		else
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values", $parameter);

		$this->lowerLimit = $lowerLimit;
		$this->upperLimit = $upperLimit;

		parent::__construct($message, $parameter, $previous);
	}

	public function getLowerLimitType()
	{
		return $this->lowerLimit;
	}

	public function getUpperType()
	{
		return $this->upperLimit;
	}
}
