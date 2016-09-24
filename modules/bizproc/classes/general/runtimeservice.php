<?
abstract class CBPRuntimeService
{
	protected $runtime;

	public function SetRuntime(CBPRuntime $runtime)
	{
		$this->runtime = $runtime;
	}

	public function Start(CBPRuntime $runtime = null)
	{
		if ($runtime != null)
			$this->SetRuntime($runtime);
	}

	static public function Stop()
	{
		
	}
}
?>