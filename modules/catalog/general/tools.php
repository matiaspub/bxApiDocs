<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCatalogTools
{
	public static function updateModuleTasksAgent()
	{
		if (!class_exists('catalog', false))
		{
			require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/catalog/install/index.php');
		}
		if (class_exists('catalog', false))
		{
			$moduleDescr = new catalog();
			$moduleDescr->InstallTasks();
		}
		return '';
	}
}
?>