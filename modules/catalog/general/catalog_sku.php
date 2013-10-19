<?
IncludeModuleLangFile(__FILE__);


/**
 * Это вспомогательный класс для получения информации об инфоблоках, свойствах и элементах инфоблоков, относящихся к SKU.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/index.php
 * @author Bitrix
 */
class CAllCatalogSKU
{
	static protected $arOfferCache = array();
	static protected $arProductCache = array();
	static protected $arPropertyCache = array();

	
	/**
	 * <p>Функция позволяет получить по ID торгового предложения ID товара.</p>
	 *
	 *
	 *
	 *
	 * @param $intOfferI $D  ID торгового предложения.
	 *
	 *
	 *
	 * @param $intIBlockI $D = 0 ID инфоблока торговых предложений. Необязательный параметр.
	 *
	 *
	 *
	 * @return mixed <ul> <li> <b>false</b> - в случае ошибки;</li> <li>в противном случае массив
	 * следующей структуры: <b>ID</b> (ID товара), <b>IBLOCK_ID</b> (ID инфоблока
	 * товаров).</li> </ul><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * $intElementID = 100; // ID предложения
	 * $mxResult = CCatalogSku::GetProductInfo(
	 * $intElementID
	 * );
	 * if (is_array($mxResult))
	 * {
	 * 	echo 'ID товара = '.$mxResult['ID'];
	 * }
	 * else
	 * {
	 * 	ShowError('Это не торговое предложение');
	 * }
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getproductinfo.php
	 * @author Bitrix
	 */
	static public function GetProductInfo($intOfferID, $intIBlockID = 0)
	{
		$intOfferID = intval($intOfferID);
		if (0 >= $intOfferID)
			return false;

		$intIBlockID = intval($intIBlockID);
		if (0 >= $intIBlockID)
		{
			$intIBlockID = intval(CIBlockElement::GetIBlockByID($intOfferID));
		}
		if (0 >= $intIBlockID)
			return false;

		if (!array_key_exists($intIBlockID, self::$arOfferCache))
		{
			$arSkuInfo = CCatalogSKU::GetInfoByOfferIBlock($intIBlockID);
		}
		else
		{
			$arSkuInfo = self::$arOfferCache[$intIBlockID];
		}
		if (empty($arSkuInfo) || empty($arSkuInfo['SKU_PROPERTY_ID']))
			return false;

		$rsItems = CIBlockElement::GetProperty($intIBlockID,$intOfferID,array(),array('ID' => $arSkuInfo['SKU_PROPERTY_ID']));
		if ($arItem = $rsItems->Fetch())
		{
			$arItem['VALUE'] = intval($arItem['VALUE']);
			if (0 < $arItem['VALUE'])
			{
				return array(
					'ID' => $arItem['VALUE'],
					'IBLOCK_ID' => $arSkuInfo['PRODUCT_IBLOCK_ID'],
				);
			}
		}
		return false;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @param $intIBlockI $D  ID инфоблока торговых предложений.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает информацию о том, является ли инфоблок инфоблоком
	 * торговых предложений:</p><ul> <li> <b>false</b> - не является;</li> <li>Если
	 * является, то возвращается массив следующего вида: <b>IBLOCK_ID</b> (ID
	 * инфоблока торговых предложений), <b>PRODUCT_IBLOCK_ID</b> (ID инфоблока
	 * товаров), <b>SKU_PROPERTY_ID</b> (ID свойства привязки торговых предложений к
	 * товарам).</li> </ul><p>Начиная с версии модуля <b>12.5.6</b>, возвращаемое
	 * значение метода кешируется в течение хита.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Проверяем, хранит ли инфоблок торговые предложения:
	 * $intIBlockID = 11;
	 * $mxResult = CCatalogSKU::GetInfoByOfferIBlock(
	 *  $intIBlockID
	 * );
	 * if (is_array($mxResult))
	 * {
	 * 	echo 'ID инфоблока товаров = '.$mxResult['PRODUCT_IBLOCK_ID'];
	 * }
	 * else
	 * {
	 * 	ShowError('Этот инфоблок не хранит торговых предложений');
	 * }
	 * Проверяем, является ли свойство привязкой торговых предложений к товару:
	 * $intPropertyID = 53;
	 * $mxResult = CCatalogSKU::GetInfoByLinkProperty(
	 *  $intPropertyID
	 * );
	 * if (is_array($mxResult))
	 * {
	 * 	echo 'Свойство связывает инфоблоки '.$mxResult['PRODUCT_IBLOCK_ID'].' и 	'.$mxResult['IBLOCK_ID'];
	 * }
	 * else
	 * {
	 * 	ShowError('Свойство не является привязкой торговых предложений');
	 * }
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getinfobyofferiblock.php
	 * @author Bitrix
	 */
	static public function GetInfoByOfferIBlock($intIBlockID)
	{
		$intIBlockID = intval($intIBlockID);
		if (0 >= $intIBlockID)
			return false;

		if (!array_key_exists($intIBlockID, self::$arOfferCache))
		{
			$rsOffers = CCatalog::GetList(
				array(),
				array('IBLOCK_ID' => $intIBlockID, '!PRODUCT_IBLOCK_ID' => 0),
				false,
				false,
				array('IBLOCK_ID','PRODUCT_IBLOCK_ID','SKU_PROPERTY_ID')
			);
			$arResult = $rsOffers->Fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = intval($arResult['IBLOCK_ID']);
				$arResult['PRODUCT_IBLOCK_ID'] = intval($arResult['PRODUCT_IBLOCK_ID']);
				$arResult['SKU_PROPERTY_ID'] = intval($arResult['SKU_PROPERTY_ID']);
			}
			self::$arOfferCache[$intIBlockID] = $arResult;
		}
		else
		{
			$arResult = self::$arOfferCache[$intIBlockID];
		}
		return $arResult;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @param $intIBlockI $D  ID инфоблока.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает информацию о том, является ли инфоблок инфоблоком
	 * товаров.</p><ul> <li> <b>false</b> - не является;</li> <li>Если является, то
	 * возвращается массив следующего вида: <b>IBLOCK_ID</b> (ID инфоблока
	 * торговых предложений), <b>PRODUCT_IBLOCK_ID</b> (ID инфоблока товаров),
	 * <b>SKU_PROPERTY_ID</b> (ID свойства привязки торговых предложений к
	 * товарам).</li> </ul><p>Начиная с версии модуля <b>12.5.6</b>, возвращаемое
	 * значение метода кешируется в течение хита.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * $intIBlockID = 7;
	 * $mxResult = CCatalogSKU::GetInfoByProductIBlock(
	 *  $intIBlockID
	 * );
	 * if (is_array($mxResult))
	 * {
	 * 	echo 'ID инфоблока торговых предложений = '.$mxResult['IBLOCK_ID'];
	 * }
	 * else
	 * {
	 * 	ShowError('У этого инфоблока нет SKU');
	 * }
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getinfobyproductiblock.php
	 * @author Bitrix
	 */
	static public function GetInfoByProductIBlock($intIBlockID)
	{
		$intIBlockID = intval($intIBlockID);
		if (0 >= $intIBlockID)
			return false;
		if (!array_key_exists($intIBlockID, self::$arProductCache))
		{
			$rsProducts = CCatalog::GetList(
				array(),
				array('PRODUCT_IBLOCK_ID' => $intIBlockID),
				false,
				false,
				array('IBLOCK_ID','PRODUCT_IBLOCK_ID','SKU_PROPERTY_ID')
			);
			$arResult = $rsProducts->Fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = intval($arResult['IBLOCK_ID']);
				$arResult['PRODUCT_IBLOCK_ID'] = intval($arResult['PRODUCT_IBLOCK_ID']);
				$arResult['SKU_PROPERTY_ID'] = intval($arResult['SKU_PROPERTY_ID']);
			}
			self::$arProductCache[$intIBlockID] = $arResult;
		}
		else
		{
			$arResult = self::$arProductCache[$intIBlockID];
		}
		return $arResult;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @param $intPropertyI $D  ID свойства инфоблока.
	 *
	 *
	 *
	 * @return mixed <p>Возвращает информацию о том, является ли свойство инфоблока
	 * свойством привязки торговых предложений к товарам:</p><ul> <li> <b>false</b>
	 * - не является;</li> <li>Если является, то возвращается массив
	 * следующего вида: <b>IBLOCK_ID</b> (ID инфоблока торговых предложений),
	 * <b>PRODUCT_IBLOCK_ID</b> (ID инфоблока товаров), <b>SKU_PROPERTY_ID</b> (ID свойства
	 * привязки торговых предложений к товарам).</li> </ul><p>Начиная с версии
	 * модуля <b>12.5.6</b>, возвращаемое значение метода кешируется в
	 * течение хита.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getinfobylinkproperty.php
	 * @author Bitrix
	 */
	static public function GetInfoByLinkProperty($intPropertyID)
	{
		$intPropertyID = intval($intPropertyID);
		if (0 >= $intPropertyID)
			return false;
		if (!array_key_exists($intPropertyID, self::$arPropertyCache))
		{
			$rsProducts = CCatalog::GetList(
				array(),
				array('SKU_PROPERTY_ID' => $intPropertyID),
				false,
				false,
				array('IBLOCK_ID','PRODUCT_IBLOCK_ID','SKU_PROPERTY_ID')
			);
			$arResult = $rsProducts->Fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = intval($arResult['IBLOCK_ID']);
				$arResult['PRODUCT_IBLOCK_ID'] = intval($arResult['PRODUCT_IBLOCK_ID']);
				$arResult['SKU_PROPERTY_ID'] = intval($arResult['SKU_PROPERTY_ID']);
			}
			self::$arPropertyCache[$intPropertyID] = $arResult;
		}
		else
		{
			$arResult = self::$arPropertyCache[$intPropertyID];
		}
		return $arResult;
	}

	
	/**
	 * <p>Метод определяет имеются ли у товара торговые предложения.</p>
	 *
	 *
	 *
	 *
	 * @param int $intProductID  ID товара.
	 *
	 *
	 *
	 * @param int $intIBlockID = 0 ID инфоблока товаров (может отсутствовать, в этом случае будет
	 * лишний запрос к базе) .
	 *
	 *
	 *
	 * @return boolean <p>Метод возвращает <i>true</i> в случае наличия торговых предложений и
	 * <i>false</i> в случае отсутствия или ошибки (некорректных
	 * параметров).</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/isexistoffers.php
	 * @author Bitrix
	 */
	static public function IsExistOffers($intProductID, $intIBlockID = 0)
	{
		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
			return false;

		$intIBlockID = intval($intIBlockID);
		if (0 >= $intIBlockID)
		{
			$intIBlockID = intval(CIBlockElement::GetIBlockByID($intProductID));
		}
		if (0 >= $intIBlockID)
			return false;

		if (!array_key_exists($intIBlockID, self::$arProductCache))
		{
			$arSkuInfo = CCatalogSKU::GetInfoByProductIBlock($intIBlockID);
		}
		else
		{
			$arSkuInfo = self::$arProductCache[$intIBlockID];
		}
		if (empty($arSkuInfo) || empty($arSkuInfo['SKU_PROPERTY_ID']))
			return false;

		$intCount = CIBlockElement::GetList(
			array(),
			array('IBLOCK_ID' => $arSkuInfo['IBLOCK_ID'], '=PROPERTY_'.$arSkuInfo['SKU_PROPERTY_ID'] => $intProductID),
			array()
		);
		return (0 < $intCount);
	}

	
	/**
	 * <p>Метод автоматически вызывается после изменения данных в <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/index.php">CCatalog</a> и сбрасывает кеш, созданный методами <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getinfobyofferiblock.php">CCatalogSKU::GetInfoByOfferIBlock</a>, <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getinfobyproductiblock.php">CCatalogSKU::GetInfoByProductIBlock</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/getinfobylinkproperty.php">CCatalogSKU::GetInfoByLinkProperty</a>.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/index.php">CCatalog</a></li> </ul><br><br>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/clearcache.php
	 * @author Bitrix
	 */
	public static function ClearCache()
	{
		self::$arOfferCache = array();
		self::$arProductCache = array();
		self::$arPropertyCache = array();
	}
}
?>