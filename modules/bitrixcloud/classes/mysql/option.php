<?
class CBitrixCloudOption extends CAllBitrixCloudOption
{
	/**
	 *
	 * @param string $name
	 * @return void
	 *
	 */
	static public function __construct($name)
	{
		parent::__construct($name);
	}
	/**
	 * Fabric method
	 *
	 * @param string $name
	 * @return CBitrixCloudOption
	 *
	 */
	public static function getOption($name)
	{
		$ob = new CBitrixCloudOption($name);
		return $ob;
	}
	/**
	 * @return bool
	 *
	 */
	public static function lock()
	{
		global $DB;
		$db_lock = $DB->Query("SELECT GET_LOCK('".CMain::GetServerUniqID()."_cdn', 0) as L");
		$ar_lock = $db_lock->Fetch();
		if (intval($ar_lock["L"]) == 0)
			return false;
		else 
			return true;
	}
	/**
	 * @return void
	 *
	 */
	public static function unlock()
	{
		global $DB;
		$DB->Query("SELECT RELEASE_LOCK('".CMain::GetServerUniqID()."_cdn') as L");
	}
}
?>