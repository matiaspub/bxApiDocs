<?
/**
 * 
 * Класс-контейнер событий модуля <b>iblock</b>
 * 
 */
class _CEventsIblock {
	/**
	 * перед добавлением информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlock::CheckFields
	 */
	public static function OnBeforeIBlockAdd(){}

	/**
	 * после добавления информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">Add</a>
	 */
	public static function OnAfterIBlockAdd(){}

	/**
	 * перед изменением информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlock::CheckFields
	 */
	public static function OnBeforeIBlockUpdate(){}

	/**
	 * после изменения информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">Update</a>
	 */
	public static function OnAfterIBlockUpdate(){}

	/**
	 * перед удалением информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/delete.php">Delete</a>
	 */
	public static function OnBeforeIBlockDelete(){}

	/**
	 * при удалении информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/delete.php">Delete</a>
	 */
	public static function OnIBlockDelete(){}

	/**
	 * перед добавлением свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockProperty::CheckFields
	 */
	public static function OnBeforeIBlockPropertyAdd(){}

	/**
	 * после добавления свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/add.php">Add</a>
	 */
	public static function OnAfterIBlockPropertyAdd(){}

	/**
	 * перед изменением свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockProperty::CheckFields
	 */
	public static function OnBeforeIBlockPropertyUpdate(){}

	/**
	 * при удалении свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php">Delete</a>
	 */
	public static function OnIBlockPropertyDelete(){}

	/**
	 * после изменения свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/update.php">Update</a>
	 */
	public static function OnAfterIBlockPropertyUpdate(){}

	/**
	 * перед удалением свойства.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/delete.php">Delete</a>
	 */
	public static function OnBeforeIBlockPropertyDelete(){}

	/**
	 * при построении списка свойств.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/GetUserType.php">GetUserType</a>
	 */
	public static function OnIBlockPropertyBuildList(){}

	/**
	 * перед добавлением раздела.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockSection::CheckFields
	 */
	public static function OnBeforeIBlockSectionAdd(){}

	/**
	 * после добавления раздела.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/add.php">Add</a>
	 */
	public static function OnAfterIBlockSectionAdd(){}

	/**
	 * перед изменением раздела.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockSection::CheckFields
	 */
	public static function OnBeforeIBlockSectionUpdate(){}

	/**
	 * после изменения раздела.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/update.php">Update</a>
	 */
	public static function OnAfterIBlockSectionUpdate(){}

	/**
	 * перед удалением раздела.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php">Delete</a>
	 */
	public static function OnBeforeIBlockSectionDelete(){}

	/**
	 * после удаления раздела.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/delete.php">Delete</a>
	 */
	public static function OnAfterIBlockSectionDelete(){}

	/**
	 * перед добавлением элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockElement::CheckFields
	 */
	public static function OnBeforeIBlockElementAdd(){}

	/**
	 * в момент начала добавления элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockElement::CheckFields
	 */
	public static function OnStartIBlockElementAdd(){}

	/**
	 * после добавления элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/add.php">Add</a>
	 */
	public static function OnAfterIBlockElementAdd(){}

	/**
	 * перед изменением элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockElement::CheckFields
	 */
	public static function OnBeforeIBlockElementUpdate(){}

	/**
	 * в момент начала изменения элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockElement::CheckFields
	 */
	public static function OnStartIBlockElementUpdate(){}

	/**
	 * после изменения элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">Update</a>
	 */
	public static function OnAfterIBlockElementUpdate(){}

	/**
	 * перед удалением элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/delete.php">Delete</a>
	 */
	public static function OnBeforeIBlockElementDelete(){}

	/**
	 * после удаления элемента.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/delete.php">Delete</a>
	 */
	public static function OnAfterIBlockElementDelete(){}

	/**
	 * при удалении элемента информационного блока.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/delete.php">Delete</a>
	 */
	public static function OnIBlockElementDelete(){}

	/**
	 * перед внесением записи в лог.
	 * 
	 * <i>Вызывается в методе:</i>
	 * <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/update.php">Update</a>
	 */
	public static function OnBeforeEventLog(){}

	/**
	 * при поиске файла.
	 * 
	 * <i>Вызывается в методе:</i>
	 * CIBlockElement::__GetFileContent
	 */
	public static function OnSearchGetFileContent(){}

	/**
	 * при возвращении описания журналу событий
	 * 
	 * <i>Вызывается в методе:</i>
	 * CEventIBlock::GetAuditTypes
	 */
	public static function GetAuditTypesIblock(){}

	/**
	 * аналог <a href="/api_help/main/events/onadmincontextmenushow.php">OnAdminContextMenuShow</a> для списка SKU
	 * 
	 * <i>Вызывается в методе:</i>
	 * CAdminSubContextMenu::Show
	 */
	public static function OnAdminSubContextMenuShow(){}

	/**
	 * аналог <a href="/api_help/main/events/onadminlistdisplay.php">OnAdminListDisplay</a> для списка SKU
	 *         <br>

	 * 
	 * <i>Вызывается в методе:</i>
	 * CAdminSubList::Display
	 */
	public static function OnAdminSubListDisplay(){}


}
?>