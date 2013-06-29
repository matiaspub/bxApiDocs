<?
abstract class CBPActivityCondition
{
    public abstract function Evaluate(CBPActivity $ownerActivity);

	public static function CreateInstance($code, $data)
	{
		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		$classname = 'CBP'.$code;
		return new $classname($data);
	}

	public static function ValidateProperties($value = null, CBPWorkflowTemplateUser $user = null)
	{
		return array();
	}

	public static function CallStaticMethod($code, $method, $arParameters = array())
	{
		$runtime = CBPRuntime::GetRuntime();
		$runtime->IncludeActivityFile($code);

		$code = preg_replace("[^a-zA-Z0-9]", "", $code);
		$classname = 'CBP'.$code;

		return call_user_func_array(array($classname, $method), $arParameters);
	}
}
?>