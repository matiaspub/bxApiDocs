<?php
namespace Bitrix\Main;

use \Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

/**
 * Exception is thrown when the type of an argument is not accepted by function.
 */
class ArgumentTypeException
	extends ArgumentException
{
	protected $requiredType;

	/**
	 * Creates new exception object
	 *
	 * @param string $parameter Argument that generates exception
	 * @param string $requiredType Required type
	 * @param \Exception $previous
	 */
	public function __construct($parameter, $requiredType = "", \Exception $previous = null)
	{
		if (!empty($requiredType))
			$message = sprintf("The value of an argument '%s' must be of type %s", $parameter, $requiredType);
		else
			$message = sprintf("The value of an argument '%s' has an invalid type", $parameter);

		$this->requiredType = $requiredType;

		parent::__construct($message, $parameter, $previous);
	}

	public function getRequiredType()
	{
		return $this->requiredType;
	}
}
