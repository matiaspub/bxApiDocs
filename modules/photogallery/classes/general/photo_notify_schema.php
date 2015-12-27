<?
IncludeModuleLangFile(__FILE__);

class CPhotogalleryNotifySchema
{
	static public function __construct()
	{
	}

	public static function OnGetNotifySchema()
	{
		return array(
			"photogallery" => array(
				"comment" => Array(
					"NAME" => GetMessage("PHOTO_NS_COMMENT"),
				)
			)
		);
	}
}

?>
