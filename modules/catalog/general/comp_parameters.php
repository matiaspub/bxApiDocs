<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Catalog;

Loc::loadMessages(__FILE__);


/**
 * Данный класс используется в файле <b>.parameters.php</b> компонентов модуля <b>Торговый каталог</b>.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogiblockparameters/index.php
 * @author Bitrix
 */
class CCatalogIBlockParameters
{
	
	/**
	* <p>Метод возвращает массив полей каталога, по которым можно сортировать. Метод статический.</p>
	*
	*
	* @return array <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogiblockparameters/getcatalogsortfields.php
	* @author Bitrix
	*/
	public static function GetCatalogSortFields()
	{
		return array(
			'CATALOG_AVAILABLE' => Loc::getMessage('IBLOCK_SORT_FIELDS_CATALOG_AVAILABLE_EXT')
		);
	}

	/**
	 * @deprected deprecated since catalog 16.5.2
	 * see \Bitrix\Catalog\Helpers\Admin\Tools::getPriceTypeList
	 *
	 * @param bool $useId
	 * @return array
	 */
	public static function getPriceTypesList($useId = false)
	{
		$useId = ($useId === true);
		return Catalog\Helpers\Admin\Tools::getPriceTypeList(!$useId);
	}
}