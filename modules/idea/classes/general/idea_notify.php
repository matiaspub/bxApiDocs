<?
Class CIdeaManagmentNotify
{
	private $arNotification = array();

	const EMAIL = 'CIdeaManagmentEmailNotify';
	const SONET = 'CIdeaManagmentSonetNotify';

	public function __construct($arNotification = array())
	{
		$this->SetNotification($arNotification);
	}

	public function SetNotification($arNotification = array())
	{
		$this->arNotification = $arNotification;
		return $this;
	}

	public function GetNotification()
	{
		return $this->arNotification;
	}

	static public function GetEmailNotify()
	{
		$Activity = self::EMAIL;
		return new $Activity($this);
	}

	static public function GetSonetNotify()
	{
		$Activity = self::SONET;
		return new $Activity($this);
	}

	static public function GetNotify($CustomNotifyClassName)
	{
		if(class_exists($CustomNotifyClassName))
			return new $CustomNotifyClassName($this);

		return false;
	}
}
?>