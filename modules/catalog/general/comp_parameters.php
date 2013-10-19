<?

/**
 * Данный класс используется в файле <b>.parameters.php</b> компонентов модуля <b>Торговый каталог</b>.
 *
 *
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
	 * <p>Метод возвращает массив полей каталога, по которым можно сортировать.</p>
	 *
	 *
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
			'CATALOG_AVAILABLE' => GetMessage('IBLOCK_SORT_FIELDS_CATALOG_AVAILABLE')
		);
	}
}
?>