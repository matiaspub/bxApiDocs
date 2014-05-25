<?

IncludeModuleLangFile(__FILE__);

class CUserTypeWiki extends CUserTypeString
{
    public static function GetUserTypeDescription()
	{
		return array(
			'USER_TYPE_ID' => 'wiki',
			'CLASS_NAME' => 'CUserTypeWiki',
			'DESCRIPTION' => 'USER_TYPE_WIKI_DESCRIPTION', //TODO: Lang file
			'BASE_TYPE' => 'string',
		);
	}

	public static function CheckPermission()
	{
		if (!CModule::IncludeModule('wiki') || !CWikiUtils::IsReadable())
			return false;

		return true;
	}
}
?>
