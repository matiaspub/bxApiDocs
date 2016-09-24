<?
namespace Bitrix\Sale\Helpers\Admin;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\Provider;

/**
 * Class Product
 * @package Bitrix\Sale\Helpers\Admin
 */
class Product
{
	private $groupByIblock = array();
	private $columnsList = null;
	private $parentsIds = array();
	private $measuresIds = null;
	private $siteId = null;
	private $storesCount = null;
	/** @var $productProvider \IBXSaleProductProvider */
	private $provider = null;
	private $productsIds = array();
	private $tmpId = '';

	private $iblockData = null;
	private $catalogData = null;

	private $resultData = array();

	/**
	 * @param array $productsIds
	 * @param $siteId
	 * @param array $columnsList
	 * @param string $tmpId
	 * @return array
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getData(array $productsIds, $siteId, array $columnsList = array(), $tmpId = "")
	{
		if(empty($productsIds))
			return array();

		if(!\Bitrix\Main\Loader::includeModule('iblock'))
			return array();

		if(!\Bitrix\Main\Loader::includeModule('catalog'))
			return array();

		$product = new self($productsIds, $siteId, $columnsList, $tmpId);
		$product->fillIblockData();

		if(count($product->resultData) <= 0)
			return array();

		$product->fillCatalogData();
		$product->completeResultData();
		$result = $product->getResultData();
		return $result;
	}

	/**
	 * @param array $productsData
	 * @param string $siteId
	 * @param int $userId
	 * @return array
	 * @throws \Bitrix\Main\NotSupportedException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function getProviderData(array $productsData, $siteId, $userId)
	{
		if(empty($productsData))
			return array();

		if(strlen($siteId) <= 0)
			return array();

		if(strlen($userId) <= 0)
			return array();

		$order = Order::create($siteId);
		$order->setFieldNoDemand("USER_ID", $userId);
		$basket = \Bitrix\Sale\Basket::create($siteId);
		$order->setBasket($basket);
		$fUserId = Fuser::getIdByUserId($userId);
		$basket->setFUserId($fUserId);

		foreach($productsData as $productFields)
		{
			$item = $basket->createItem($productFields["MODULE"], $productFields["OFFER_ID"]);
			$item->setField('QUANTITY', $productFields['QUANTITY']);
			$item->setField("NAME", $productFields["NAME"]);

			if(isset($productFields["PRODUCT_PROVIDER_CLASS"]) && strlen($productFields["PRODUCT_PROVIDER_CLASS"]) > 0)
				$item->setField("PRODUCT_PROVIDER_CLASS", trim($productFields["PRODUCT_PROVIDER_CLASS"]));
		}

		return Provider::getProductData($basket, array("PRICE", "AVAILABLE_QUANTITY"));
	}

	private function __construct(array $productsIds, $siteId, array $columnsList = array(), $tmpId = "")
	{
		$this->columnsList = $columnsList;
		$this->productsIds = $productsIds;
		$this->siteId = $siteId;
		$this->tmpId = $tmpId;

		$this->provider = \CSaleBasket::GetProductProvider(
			array(
				"MODULE" => 'catalog',
				"PRODUCT_PROVIDER_CLASS" => 'CCatalogProductProvider'
			)
		);
	}

	private function getResultData()
	{
		if($this->resultData === null)
			throw new ArgumentNullException('this->resultData must be set earlier!');

		return $this->resultData;
	}

	private function fillCatalogData()
	{
		$this->catalogData = array();
		$this->measuresIds = array();

		if(empty($this->iblockData))
			return;

		$setIds = array();

		$res = \CCatalogProduct::getList(
			array(),
			array('ID' => array_keys($this->iblockData)),
			false,
			false,
			array('ID', 'QUANTITY', 'WEIGHT', 'MEASURE', 'TYPE', 'BARCODE_MULTI', 'WIDTH', 'LENGTH', 'HEIGHT')
		);

		while($row = $res->Fetch())
		{
			$this->catalogData[$row['ID']] = $row;
			$this->measuresIds[] = $row['MEASURE'];

			if($row['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_SET)
				$setIds[] = $row['ID'];

			if(isset($this->resultData[$row['ID']]))
			{
				$this->resultData[$row['ID']]['DIMENSIONS'] = serialize(
					array(
						"WIDTH" => $row["WIDTH"],
						"HEIGHT" => $row["HEIGHT"],
						"LENGTH" => $row["LENGTH"]
					)
				);

				$this->resultData[$row['ID']]['AVAILABLE'] = floatval($row["QUANTITY"]);
				$this->resultData[$row['ID']]['WEIGHT'] = $row["WEIGHT"];
				$this->resultData[$row['ID']]['BARCODE_MULTI'] = $row["BARCODE_MULTI"];
				$this->resultData[$row['ID']]["SET_ITEMS"] = array();
				$this->resultData[$row['ID']]["IS_SET_ITEM"] = "N";
				$this->resultData[$row['ID']]["IS_SET_PARENT"] = "N"; //empty($arSetInfo) ? "N" : "Y";
				$arSetItemParams["OLD_PARENT_ID"] = $id."_tmp".$this->tmpId;
			}
		}

		if(!empty($setIds))
			$this->fillSetInfo($setIds);
	}

	private function fillSetInfo($setIds)
	{
		if(!$this->provider)
			return;

		if(!method_exists($this->provider, 'GetSetItems'))
			return;

		$provider = $this->provider;
		$childrenParent = array();
		$itemsIds = array();
		$items = array();

		if ($this->tmpId == "")
			$this->tmpId = randString(7);

		foreach($setIds as $id)
		{
			if ($this->catalogData[$id]["TYPE"] != \CCatalogProduct::TYPE_SET)
				continue;

			$arSets = $provider::GetSetItems($id, \CSaleBasket::TYPE_SET);

			if (empty($arSets))
				continue;

			foreach ($arSets as $arSetData)
			{
				foreach ($arSetData["ITEMS"] as $setItem)
				{
					$arSetItemParams = array();
					$arSetItemParams["PARENT_OFFER_ID"] = $id;
					$arSetItemParams["OFFER_ID"] = $setItem["PRODUCT_ID"];
					$arSetItemParams["NAME"] = $setItem["NAME"];
					$arSetItemParams["MODULE"] = $setItem["MODULE"];
					$arSetItemParams["PRODUCT_PROVIDER_CLASS"] = $setItem["PRODUCT_PROVIDER_CLASS"];
					$arSetItemParams["BARCODE_MULTI"] = $setItem["BARCODE_MULTI"];
					$arSetItemParams["PRODUCT_TYPE"] = $setItem["TYPE"];
					$arSetItemParams["WEIGHT"] = $setItem["WEIGHT"];
					$arSetItemParams["SET_ITEMS"] = "";
					$arSetItemParams["OLD_PARENT_ID"] = $id."_tmp".$this->tmpId;
					$arSetItemParams["IS_SET_ITEM"] = "Y";
					$arSetItemParams["IS_SET_PARENT"] = "N";
					$arSetItemParams["PROVIDER_DATA"] = serialize($setItem);
					$items[$id][$setItem["PRODUCT_ID"]] = $arSetItemParams;

					if(!in_array($setItem["PRODUCT_ID"], $itemsIds))
						$itemsIds[] = $setItem["PRODUCT_ID"];

					if(!is_array($childrenParent[$setItem["PRODUCT_ID"]]))
						$childrenParent[$setItem["PRODUCT_ID"]] = array();

					$childrenParent[$setItem["PRODUCT_ID"]][] = $id;
				}
			}

			$tmpData = self::getData($itemsIds, $this->siteId, $this->columnsList, $this->tmpId);

			foreach($childrenParent as $childId => $childData)
			{
				if(!is_array($childData))
					continue;

				if(empty($tmpData[$childId]))
					continue;

				foreach($childData as $productId)
				{
					if(empty($items[$productId][$childId]))
						continue;

					$this->resultData[$productId]['SET_ITEMS'][] = array_merge($tmpData[$childId], $items[$productId][$childId]);
					$this->resultData[$productId]["IS_SET_PARENT"] = empty($this->resultData[$productId]["SET_ITEMS"]) ? 'N' : 'Y';
					$this->resultData[$productId]["OLD_PARENT_ID"] = empty($this->resultData[$productId]["SET_ITEMS"]) ? '' : $productId."_tmp".$this->tmpId;
					$this->resultData[$productId]["PRODUCT_TYPE"] = empty($this->resultData[$productId]["SET_ITEMS"]) ? "" : \CSaleBasket::TYPE_SET;
				}
			}
		}
	}

	private function fillIblockData()
	{
		$select = array_merge(
			array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID", "DETAIL_PICTURE", "PREVIEW_PICTURE", "XML_ID", "IBLOCK_EXTERNAL_ID"),
			$this->columnsList
		);

		foreach($this->productsIds as $id)
		{
			$this->resultData[$id] = array();
			$info = \CCatalogSku::GetProductInfo($id);

			if(!empty($info))
			{
				$this->resultData[$id]["OFFERS_IBLOCK_ID"] = $info["OFFER_IBLOCK_ID"];
				$this->resultData[$id]["IBLOCK_ID"] = $info["IBLOCK_ID"];
				$this->resultData[$id]["PRODUCT_ID"] = $info["ID"];
				$this->parentsIds[] = $info["ID"];

				if(!isset($this->groupByIblock[$info['OFFER_IBLOCK_ID']]))
					$this->groupByIblock[$info['OFFER_IBLOCK_ID']] = array();

				$this->groupByIblock[$info['OFFER_IBLOCK_ID']][] = $id;

				if(!isset($this->groupByIblock[$info['IBLOCK_ID']]))
					$this->groupByIblock[$info['IBLOCK_ID']] = array();

				$this->groupByIblock[$info['IBLOCK_ID']][] = $info["ID"];
			}
			else
			{
				if(intval($this->resultData[$id]["IBLOCK_ID"]) > 0)
				{
					if(!isset($this->groupByIblock[$this->resultData[$id]["IBLOCK_ID"]]))
						$this->groupByIblock[$this->resultData[$id]["IBLOCK_ID"]] = array();

					$this->groupByIblock[$this->resultData[$id]["IBLOCK_ID"]][] = $id;
				}

				$this->resultData[$id]["PRODUCT_ID"] = $id;
				$this->resultData[$id]["OFFERS_IBLOCK_ID"] = 0;
			}
		}

		$ppData = getProductProps(array_merge($this->productsIds, $this->parentsIds), $select);

		foreach($ppData as $id => $fields)
		{
			if(empty($ppData[$id]))
				continue;

			$this->iblockData[$id] = $ppData[$id];
			$this->iblockData[$id]["PRODUCT_PROPS_VALUES"] = $this->createProductPropsValues($id);

			if(!in_array($id, $this->productsIds))
				continue;

			$this->resultData[$id] = array_merge(
				$this->resultData[$id],
				array_intersect_key(
					$this->iblockData[$id],
					array_flip(
						$this->columnsList
			)));

			$this->resultData[$id]["OFFER_ID"] = $id;
			$this->resultData[$id]["NAME"] = $fields["NAME"];
			$this->resultData[$id]["PRODUCT_XML_ID"] = $fields["XML_ID"];
			$this->resultData[$id]["CATALOG_XML_ID"] = $fields["~IBLOCK_EXTERNAL_ID"];
			$this->resultData[$id]["PRODUCT_PROPS_VALUES"] = $this->iblockData[$id]["PRODUCT_PROPS_VALUES"];
			$this->resultData[$id]['EDIT_PAGE_URL'] = $this->createEditPageUrl($fields);
		}

		return;
	}

	private static function isOffer($productData)
	{
		return intval($productData['PRODUCT_ID']) != intval($productData['OFFER_ID']);
	}

	private function completeResultData()
	{
		if(empty($this->resultData))
			return;

		foreach($this->resultData as $productId => $productData)
		{
			$this->resultData[$productId]['PROPERTIES'] = array();
			$this->resultData[$productId]['PICTURE_URL'] = $this->createImageUrl($productId);
			$this->resultData[$productId]['MODULE'] = "catalog";
			$this->resultData[$productId]["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";
			$this->resultData[$productId]["STORES"] = $this->getStoresData($productId);

			if($this->isOffer($productData) && !empty($this->iblockData[$productData['PRODUCT_ID']]))
			{
				$parentData = $this->iblockData[$productData['PRODUCT_ID']];

				if(is_array($parentData['PRODUCT_PROPS_VALUES']))
				{
					foreach($parentData['PRODUCT_PROPS_VALUES'] as $fieldId => $fieldValue)
					{
						if(!isset($productData['PRODUCT_PROPS_VALUES'][$fieldId])
						|| (isset($productData['PRODUCT_PROPS_VALUES'][$fieldId]) && is_null($productData['PRODUCT_PROPS_VALUES'][$fieldId]))
						)
						{
							$this->resultData[$productId]["PRODUCT_PROPS_VALUES"][$fieldId] = $fieldValue;
						}
					}
				}

				if(strpos($this->iblockData[$productId]["XML_ID"], '#') === false)
				{
					$parentXmlId = strval($parentData['XML_ID']);
					$this->resultData[$productId]['PRODUCT_XML_ID'] = $parentXmlId.'#'.$this->iblockData[$productId]['XML_ID'];
				}
			}

			foreach($this->resultData[$productId]['PRODUCT_PROPS_VALUES'] as $fieldId => $fieldValue)
			{
				if(is_null($fieldValue))
				{
					$this->resultData[$productId]['PRODUCT_PROPS_VALUES'][$fieldId] = '&nbsp';
				}
			}
		}

		$this->fillPropsData();
		$this->fillMeasures();
		$this->fillMeasuresRatio();
	}

	private function fillPropsData()
	{
		if(empty($this->resultData))
			return;

		foreach($this->groupByIblock as $iblockId => $elIds)
		{
			\CIBlockElement::GetPropertyValuesArray(
				$this->resultData,
				$iblockId,
				array(
					'ID' => $elIds,
					'IBLOCK_ID' => $iblockId
			));
		}

		foreach($this->resultData as $elId => $elData)
		{
			if(isset($elData['PROPERTIES']))
			{
				$props = $this->formatProps($elData['PROPERTIES']);
				unset($this->resultData[$elId]['PROPERTIES']);

				if(strlen($elData["CATALOG_XML_ID"]) > 0)
				{
					$props[] = array(
						"ID" => 0,
						"NAME" => "Catalog XML_ID",
						"CODE" => "CATALOG.XML_ID",
						"VALUE" => $elData['CATALOG_XML_ID']
					);
				}

				if(strlen($elData["PRODUCT_XML_ID"]) > 0)
				{
					$props[] = array(
						"ID" => 0,
						"NAME" => "Product XML_ID",
						"CODE" => "PRODUCT.XML_ID",
						"VALUE" => $elData["PRODUCT_XML_ID"]
					);
				}

				if(empty($props))
					continue;

				$this->resultData[$elId]['PROPS'] = $props;
			}
		}
	}

	//creators
	private static function formatProps(array $properties)
	{
		if(empty($properties))
			return array();

		$result = array();

		foreach ($properties as $prop)
		{
			if ($prop['XML_ID'] == 'CML2_LINK' || $prop['PROPERTY_TYPE'] == 'F')
				continue;

			if(is_array($prop["VALUE"]) && empty($prop["VALUE"]))
				continue;

			if(!is_array($prop["VALUE"]) && strlen($prop["VALUE"]) <= 0)
				continue;

			$displayProperty = \CIBlockFormatProperties::GetDisplayValue(array(), $prop, '');

			$mxValues = '';

			if ('E' == $prop['PROPERTY_TYPE'])
			{
				if (!empty($displayProperty['LINK_ELEMENT_VALUE']))
				{
					$mxValues = array();

					foreach ($displayProperty['LINK_ELEMENT_VALUE'] as $arTempo)
						$mxValues[] = $arTempo['NAME'].' ['.$arTempo['ID'].']';
				}
			}
			elseif ('G' == $prop['PROPERTY_TYPE'])
			{
				if (!empty($displayProperty['LINK_SECTION_VALUE']))
				{
					$mxValues = array();

					foreach ($displayProperty['LINK_SECTION_VALUE'] as $arTempo)
						$mxValues[] = $arTempo['NAME'].' ['.$arTempo['ID'].']';
				}
			}
			if (empty($mxValues))
			{
				$mxValues = $displayProperty["DISPLAY_VALUE"];
			}

			$result[] = array(
				'ID' => $prop["ID"],
				'CODE' => htmlspecialcharsback($prop['CODE']),
				'NAME' => htmlspecialcharsback($prop["NAME"]),
				'VALUE' => htmlspecialcharsback(strip_tags(is_array($mxValues) ? implode("/ ", $mxValues) : $mxValues))
			);
		}

		return $result;
	}

	private function createProductPropsValues($productId)
	{
		if(intval($productId) <= 0)
			return array();

		$result = array();
		$fields = $this->iblockData[$productId];

		foreach ($fields as $fieldId => $fieldValue)
		{
			if (strncmp($fieldId, 'PROPERTY_', 9) == 0 && substr($fieldId, -6) == "_VALUE")
			{
				$propertyInfo = $this->getPropertyInfo(str_replace("_VALUE", "", $fieldId));
				$code = strlen($propertyInfo['CODE']) > 0 ? $propertyInfo['CODE'] : $propertyInfo['ID'];
				$keyResult = 'PROPERTY_'.$code.'_VALUE';
				$result[$keyResult] = self::getIblockPropInfo($fieldValue, $propertyInfo, array("WIDTH" => 90, "HEIGHT" => 90));
			}
		}

		return $result;
	}


	private function preparePropertyInfo()
	{
		$result = array();
		$codes = array();

		foreach($this->columnsList as $column)
		{
			if(strncmp($column, 'PROPERTY_', 9) != 0)
				continue;

			$propertyCode = substr($column, 9);

			if ($propertyCode == '')
				continue;

			$codes[] = $propertyCode;
		}

		$dbRes = PropertyTable::getList(array(
			'filter' => array(
				'LOGIC' => 'OR',
				"=CODE" => $codes,
				"=ID" => $codes
			)
		));

		while($propData = $dbRes->fetch())
		{
			$code = strlen($propData['CODE']) > 0 ? $propData['CODE'] : $propData['ID'];
			$result['PROPERTY_'.strtoupper($code)] = $propData;
		}

		return $result;
	}

	private function getPropertyInfo($fieldId)
	{
		static $propsInfo = null;

		if($propsInfo === null)
			$propsInfo = $this->preparePropertyInfo();

		return isset($propsInfo[$fieldId]) ? $propsInfo[$fieldId] : array();
	}

	private function createImageUrl($productId)
	{
		$imgCode = '';
		$imgUrl = '';

		$productData = $this->iblockData[$productId];

		if($productData["PREVIEW_PICTURE"] > 0)
			$imgCode = $productData["PREVIEW_PICTURE"];
		elseif($productData["DETAIL_PICTURE"] > 0)
			$imgCode = $productData["DETAIL_PICTURE"];

		if($imgCode == "" && $this->isOffer($this->resultData[$productId]))
		{
			if(!empty($this->iblockData[$this->resultData[$productId]['PRODUCT_ID']]))
			{
				$parentData = $this->iblockData[$this->resultData[$productId]['PRODUCT_ID']];

				if ($parentData["PREVIEW_PICTURE"] > 0)
					$imgCode = $parentData["PREVIEW_PICTURE"];
				elseif ($parentData["DETAIL_PICTURE"] > 0)
					$imgCode = $parentData["DETAIL_PICTURE"];
			}
		}

		if ($imgCode > 0)
		{
			$arFile = \CFile::GetFileArray($imgCode);
			$arImgProduct = \CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);

			if (is_array($arImgProduct))
				$imgUrl = $arImgProduct["src"];
		}

		return $imgUrl;
	}


	private static function createEditPageUrl(array $productData)
	{

		if(intval($productData['IBLOCK_ID']) <= 0 || intval($productData['ID']) <= 0)
			return '';

		$result = \CIBlock::GetAdminElementEditLink(
			intval($productData['IBLOCK_ID']),
			intval($productData['ID']),
			array(
				"find_section_section" => (intval($productData['IBLOCK_SECTION_ID']) > 0 ? intval($productData['IBLOCK_SECTION_ID']) : null),
				'WF' => 'Y'
		));

		return $result;
	}

	private function fillMeasures()
	{
		$measures = array();
		$defaultMeasure = \CCatalogMeasure::getDefaultMeasure(true, true);
		$defaultMeasureText = ($defaultMeasure["SYMBOL_RUS"] != '' ? $defaultMeasure["SYMBOL_RUS"] : $defaultMeasure["SYMBOL_INTL"]);
		$defaultMeasureCode = 0;
		$settedIds = array();

		$dbRes = \CCatalogMeasure::GetList(
			array(),
			array("ID" => $this->measuresIds),
			false,
			false,
			array("ID", "CODE", "SYMBOL_RUS", "SYMBOL_INTL")
		);

		while ($measure = $dbRes->Fetch())
			$measures[$measure['ID']] = $measure;

		foreach($this->catalogData as $productId => $productFields)
		{
			if(!isset($this->resultData[$productId]))
				continue;

			$this->resultData[$productId]["MEASURE_TEXT"] = $defaultMeasureText;
			$this->resultData[$productId]["MEASURE_CODE"] = $defaultMeasureCode;

			if (empty($measures[$productFields['MEASURE']]) || !is_array($measures[$productFields['MEASURE']]))
				continue;

			$measure = $measures[$productFields['MEASURE']];

			$this->resultData[$productId]["MEASURE_TEXT"] = ($measure["SYMBOL_RUS"] != '' ? $measure["SYMBOL_RUS"] : $measure["SYMBOL_INTL"]);
			$this->resultData[$productId]["MEASURE_CODE"] = $measure["CODE"] != '' ? $measure["CODE"] : $defaultMeasureText;

			$settedIds[] = $productId;
		}

		$needToSet = array_diff_key($this->resultData, array_flip($settedIds));

		foreach($needToSet as $productId => $fields)
		{
			if(!isset($fields['MEASURE_CODE']))
				$this->resultData[$productId]['MEASURE_CODE'] = $defaultMeasureCode;

			if(!empty($fields['MEASURE_TEXT']))
				$this->resultData[$productId]["MEASURE_TEXT"] = $defaultMeasureText;
		}
	}

	private function fillMeasuresRatio()
	{
		$dbRes = \Bitrix\Catalog\MeasureRatioTable::getList(array(
			'filter' => array("PRODUCT_ID" => array_keys($this->resultData))
		));

		while($ratio = $dbRes->Fetch())
		{
			if(!isset($this->resultData[$ratio['PRODUCT_ID']]))
				continue;

			$this->resultData[$ratio['PRODUCT_ID']]['MEASURE_RATIO'] = $ratio["RATIO"];
		}

		foreach($this->resultData as $productId => $fields)
		{
			if(!isset($fields['MEASURE_RATIO']))
				$this->resultData[$productId]['MEASURE_RATIO'] = 1;
		}
	}

	private  function getStoresData($productId)
	{
		$result = array();

		if(!$this->provider)
			return array();

		$productProvider = $this->provider;

		if($this->storesCount === null)
			$this->storesCount = $productProvider::GetStoresCount(array("SITE_ID" => $this->siteId));

		if(intval($this->storesCount <= 0))
			return array();

		$stores = $productProvider::GetProductStores(array("PRODUCT_ID" => $productId, "SITE_ID" => $this->siteId));

		if($stores)
			$result = $stores;

		return $result;
	}

	private static function getIblockPropInfo($value, $propData, $arSize = array("WIDTH" => 90, "HEIGHT" => 90), $orderId = 0)
	{
		$res = "";

		if ($propData["MULTIPLE"] == "Y")
		{
			$arVal = array();
			if (!is_array($value))
			{
				if (strpos($value, ",") !== false)
					$arVal = explode(",", $value);
				else
					$arVal[] = $value;
			}
			else
				$arVal = $value;

			if (count($arVal) > 0)
			{
				foreach ($arVal as $key => $val)
				{
					if ($propData["PROPERTY_TYPE"] == "F")
					{
						if (strlen($res) > 0)
							$res .= "<br/> ".self::showImageOrDownloadLink(trim($val), $orderId, $arSize);
						else
							$res = self::showImageOrDownloadLink(trim($val), $orderId, $arSize);
					}
					else
					{
						if (strlen($res) > 0)
							$res .= ", ".$val;
						else
							$res = $val;
					}
				}
			}
		}
		else
		{
			if ($propData["PROPERTY_TYPE"] == "F")
				$res = self::showImageOrDownloadLink($value, $orderId, $arSize);
			else
				$res = $value;
		}

		if (strlen($res) == 0)
			$res = null;

		return $res;
	}

	private static function showImageOrDownloadLink($fileId, $orderId = 0, $arSize = array("WIDTH" => 90, "HEIGHT" => 90))
	{
		$resultHTML = "";
		$arFile = \CFile::GetFileArray($fileId);

		if ($arFile)
		{
			$is_image = \CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]);
			if ($is_image)
				$resultHTML = \CFile::ShowImage($arFile["ID"], $arSize["WIDTH"], $arSize["HEIGHT"], "border=0", "", true);
			else
				$resultHTML = "<a href=\"sale_order_detail.php?ID=".$orderId."&download=Y&file_id=".$arFile["ID"]."&".bitrix_sessid_get()."\">".$arFile["ORIGINAL_NAME"]."</a>";
		}

		return $resultHTML;
	}
}