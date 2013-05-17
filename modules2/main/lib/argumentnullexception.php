<?php
namespace Bitrix\Main;

//use \Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

/**
 * Exception is thrown when "empty" value is passed to a function that does not accept it as a valid argument.
 */
class ArgumentNullException
	extends ArgumentException
{
	static public function __construct($parameter, \Exception $previous = null)
	{
		/*$message = Loc::getMessage(
			"argument_null_exception_message",
			array("#PARAMETER#" => $parameter)
		);*/
		$message = sprintf("Argument '%s' is null or empty", $parameter);
		parent::__construct($message, $parameter, $previous);
	}
}
