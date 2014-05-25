<?
IncludeModuleLangFile(__FILE__);

class CBPArgumentException
	extends Exception
{
    private $paramName = "";

	public function __construct($message, $paramName = "")
	{
		parent::__construct($message, 10001);
		$this->paramName = $paramName;
	}

	public function GetParamName()
	{
		return $this->paramName;
	}
}

class CBPArgumentNullException
	extends CBPArgumentException
{
	public function __construct($paramName, $message = "")
	{
		if (strlen($message) <= 0)
			$message = str_replace("#PARAM#", htmlspecialcharsbx($paramName), GetMessage("BPCGERR_NULL_ARG"));

		parent::__construct($message, $paramName);

		$this->code = "10002";
	}
}

class CBPArgumentOutOfRangeException
	extends CBPArgumentException
{
	private $actualValue = null;

	public function __construct($paramName, $actualValue = null, $message = "")
	{
		if (strlen($message) <= 0)
		{
			if ($actualValue === null)
				$message = str_replace("#PARAM#", htmlspecialcharsbx($paramName), GetMessage("BPCGERR_INVALID_ARG"));
			else
				$message = str_replace(array("#PARAM#", "#VALUE#"), array(htmlspecialcharsbx($paramName), htmlspecialcharsbx($actualValue)), GetMessage("BPCGERR_INVALID_ARG1"));
		}

		parent::__construct($message, $paramName);

		$this->code = "10003";
		$this->actualValue = $actualValue;
	}

	public function GetActualValue()
	{
		return $this->actualValue;
	}
}

class CBPArgumentTypeException
	extends CBPArgumentException
{
	private $correctType = null;

	public function __construct($paramName, $correctType = null, $message = "")
	{
		if (strlen($message) <= 0)
		{
			if ($correctType === null)
				$message = str_replace("#PARAM#", htmlspecialcharsbx($paramName), GetMessage("BPCGERR_INVALID_TYPE"));
			else
				$message = str_replace(array("#PARAM#", "#VALUE#"), array(htmlspecialcharsbx($paramName), htmlspecialcharsbx($correctType)), GetMessage("BPCGERR_INVALID_TYPE1"));
		}

		parent::__construct($message, $paramName);

		$this->code = "10005";
		$this->correctType = $correctType;
	}

	public function GetCorrectType()
	{
		return $this->correctType;
	}
}

class CBPInvalidOperationException
	extends Exception
{
	static public function __construct($message = "")
	{
		parent::__construct($message, 10006);
	}
}

class CBPNotSupportedException
	extends Exception
{
	public function __construct($message = "")
	{
		parent::__construct($message, 10004);
	}
}

//class CBPStandartException
//	extends Exception
//{
//	public function __construct($message, $errorLevel = 0, $errorFile = '', $errorLine = 0)
//	{
//		parent::__construct($message, $errorLevel);
//		$this->file = $errorFile;
//		$this->line = $errorLine;
//	}
//}

//set_error_handler(
//	create_function('$c, $m, $f, $l', 'if ($c === E_NOTICE) {echo "This is notice: ".$m;} else {throw new CBPStandartException($m, $c, $f, $l);}'),
//	E_ALL
//);
?>