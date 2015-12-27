<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog;
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
			'CATALOG_AVAILABLE' => Loc::getMessage('IBLOCK_SORT_FIELDS_CATALOG_AVAILABLE')
		);
	}

	public static function getPriceTypesList($useId = false)
	{
		$useId = ($useId === true);
		$result = array();
		$priceTypeIterator = Catalog\GroupTable::getList(array(
			'select' => array('ID', 'NAME', 'NAME_LANG' => 'CURRENT_LANG.NAME'),
			'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
		));
		while ($priceType = $priceTypeIterator->fetch())
		{
			$priceType['NAME_LANG'] = (string)$priceType['NAME_LANG'];
			$priceCode = ($useId ? $priceType['ID'] : $priceType['NAME']);
			$priceTitle = '['.$priceType['ID'].'] ['.$priceType['NAME'].']'.($priceType['NAME_LANG'] != '' ? ' '.$priceType['NAME_LANG'] : '');
			$result[$priceCode] = $priceTitle;
		}
		unset($priceTitle, $priceCode, $priceType, $priceTypeIterator);
		return $result;
	}
}