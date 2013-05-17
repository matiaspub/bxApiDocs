<?
abstract class CBPRuntimeService
{
	protected $runtime;

	static public function SetRuntime(CBPRuntime $runtime)
	{
		$this->runtime = $runtime;
	}

	static public function Start(CBPRuntime $runtime = null)
	{
		if ($runtime != null)
			$this->SetRuntime($runtime);
	}

	static public function Stop()
	{
		
	}
}
?>