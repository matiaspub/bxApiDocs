<?php
if (!CModule::IncludeModule('iblock'))
{
	return false;
}

IncludeModuleLangFile(__FILE__);

class CCrmProductSection
{
	const NO_ERRORS = 0;
	const ERR_SECTION_NAME_EMPTY = 1;
	const ERR_IBLOCK_SECTION_ADD = 2;
	const ERR_SECTION_NOT_FOUND = 3;
	const ERR_IBLOCK_SECTION_UPDATE = 4;
	const ERR_SECTION_INCLUDES_USED_PRODUCTS = 5;
	const ERR_IBLOCK_SECTION_DELETE = 6;

	protected static $FIELD_INFOS = null;
	protected static $LAST_ERROR = '';
	protected static $LAST_ERROR_CODE = self::NO_ERRORS;
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'CATALOG_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'SECTION_ID' => array(
					'TYPE' => 'integer'
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				)
			);
		}

		return self::$FIELD_INFOS;
	}
	// CRUD -->
	public static function Add(&$arFields)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return false;
		}

		if(!(isset($arFields['NAME']) && is_string($arFields['NAME']) && $arFields['NAME'] !== ''))
		{
			self::$LAST_ERROR_CODE = self::ERR_SECTION_NAME_EMPTY;
			self::$LAST_ERROR = GetMessage('CRM_PRODUCT_SECTION_ERR_SECTION_NAME_EMPTY');
			return false;
		}

		$catalogID = isset($arFields['CATALOG_ID']) ? intval($arFields['CATALOG_ID']) : 0;
		if(!($catalogID > 0 && CCrmCatalog::Exists($catalogID)))
		{
			$catalogID = CCrmCatalog::EnsureDefaultExists();
		}
		$arFields['CATALOG_ID'] = $catalogID;

		$sectionFields = CCrmProductSectionDbResult::MapKeys($arFields);
		$sectionFields['CHECK_PERMISSIONS'] = 'N';

		$section = new CIBlockSection();
		$result = $section->Add($sectionFields);

		if(!(is_int($result) && $result > 0))
		{
			self::$LAST_ERROR_CODE = self::ERR_IBLOCK_SECTION_ADD;
			self::$LAST_ERROR = $section->LAST_ERROR;
		}

		return $result;
	}
	public static function GetByID($ID)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return false;
		}

		$section = new CIBlockSection();
		$dbResult = $section->GetList(
			array(),
			array(
				'ID' => $ID,
				'GLOBAL_ACTIVE' => 'Y',
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			array('ID', 'NAME', 'IBLOCK_ID', 'IBLOCK_SECTION_ID'),
			false
		);

		$result = $dbResult ? $dbResult->Fetch() : null;

		if(!is_array($result))
		{
			return null;
		}

		$result = CCrmProductSectionDbResult::UnMapKeys($result);

		$catalogID = isset($result['CATALOG_ID']) ? intval($result['CATALOG_ID']) : 0;
		return ($catalogID > 0 && CCrmCatalog::Exists($catalogID)) ? $result : null;
	}
	public static function Update($ID, &$arFields)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return false;
		}


		$section = new CIBlockSection();
		$dbResult = $section->GetList(
			array(),
			array(
				'ID' => $ID,
				'GLOBAL_ACTIVE' => 'Y',
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			array('IBLOCK_ID'),
			false
		);

		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!(is_array($currentFields) && CCrmCatalog::Exists(intval($currentFields['IBLOCK_ID']))))
		{
			self::$LAST_ERROR_CODE = self::ERR_SECTION_NOT_FOUND;
			self::$LAST_ERROR = GetMessage('CRM_PRODUCT_SECTION_ERR_SECTION_NOT_FOUND');
			return false;
		}

		$sectionFields = CCrmProductSectionDbResult::MapKeys($arFields);
		$sectionFields['CHECK_PERMISSIONS'] = 'N';

		$result = $section->Update($ID, $sectionFields);

		if($result === false)
		{
			self::$LAST_ERROR_CODE = self::ERR_IBLOCK_SECTION_UPDATE;
			self::$LAST_ERROR = $section->LAST_ERROR;
		}

		return $result;
	}
	public static function Delete($ID)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return false;
		}

		$section = new CIBlockSection();
		$dbResult = $section->GetList(
			array(),
			array(
				'ID' => $ID,
				'GLOBAL_ACTIVE' => 'Y',
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			array('IBLOCK_ID'),
			false
		);

		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!(is_array($currentFields) && CCrmCatalog::Exists(intval($currentFields['IBLOCK_ID']))))
		{
			self::$LAST_ERROR_CODE = self::ERR_SECTION_NOT_FOUND;
			self::$LAST_ERROR = GetMessage('CRM_PRODUCT_SECTION_ERR_NOT_FOUND');
			return false;
		}

		if (self::isIncludesUsedProducts($currentFields['IBLOCK_ID'], $ID))
		{
			// Section includes used products
			self::$LAST_ERROR_CODE = self::ERR_SECTION_INCLUDES_USED_PRODUCTS;
			self::$LAST_ERROR = GetMessage('CRM_PRODUCT_SECTION_ERR_SECTION_INCLUDES_USED_PRODUCTS');
			return false;
		}

		$result = $section->Delete($ID, false);
		if($result === false)
		{
			self::$LAST_ERROR_CODE = self::ERR_IBLOCK_SECTION_DELETE;
			self::$LAST_ERROR = $section->LAST_ERROR;
		}
		return $result !== false;
	}
	// <-- CRUD
	// Contract -->
	public static function GetList($arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavStartParams = false)
	{
		if (!CModule::IncludeModule('iblock'))
		{
			return false;
		}

		if(!is_array($arFilter))
		{
			$arFilter = array();
		}

		$catalogID = isset($arFilter['CATALOG_ID']) ? intval($arFilter['CATALOG_ID']) : 0;
		if(!($catalogID > 0 && CCrmCatalog::Exists($catalogID)))
		{
			$catalogID = CCrmCatalog::EnsureDefaultExists();
		}
		$arFilter['CATALOG_ID'] = $catalogID;

		$section = new CIBlockSection();

		return new CCrmProductSectionDbResult(
			$section->GetList(
				CCrmProductSectionDbResult::MapKeys($arOrder),
				CCrmProductSectionDbResult::MapKeys($arFilter),
				false,
				CCrmProductSectionDbResult::MapValues($arSelectFields),
				$arNavStartParams
			)
		);
	}
	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}
	public static function GetLastErrorCode()
	{
		return self::$LAST_ERROR_CODE;
	}
	//<-- Contract
	public static function isIncludesUsedProducts($iblockId, $sectionId)
	{
		$iblockId = intval($iblockId);
		$sectionId = intval($sectionId);
		if ($iblockId <= 0 || $sectionId <= 0)
			return false;

		$result = false;
		$stepSize = 500;

		$element = new CIBlockElement();
		$rs = $element->GetList(
			array('SORT' => 'ASC'),
			array(
				'IBLOCK_ID' => $iblockId,
				'SECTION_ID' => $sectionId,
				'INCLUDE_SUBSECTIONS' => 'Y',
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID')
		);
		if ($rs)
		{
			$i = 0;
			$arProductId = array();
			while ($row = $rs->Fetch())
			{
				$i++;
				$arProductId[] = $row['ID'];
				if ($i === $stepSize)
				{
					$rowsCount = CCrmProductRow::GetList(array(), array('PRODUCT_ID' => $arProductId), array(), false, array());
					if($rowsCount > 0 || CCrmInvoice::HasProductRows($arProductId))
						$result = true;
					$i = 0;
					$arProductId = array();
				}
				if ($result)
					break;
			}
			if (!$result && count($arProductId) > 0)
			{
				$rowsCount = CCrmProductRow::GetList(array(), array('PRODUCT_ID' => $arProductId), array(), false, array());
				if($rowsCount > 0 || CCrmInvoice::HasProductRows($arProductId))
					$result = true;
			}
		}

		return $result;
	}
}

class CCrmProductSectionDbResult extends CDBResult
{
	protected static $FIELD_MAP = array(
		'ID' => 'ID',
		'CATALOG_ID' => 'IBLOCK_ID',
		'SECTION_ID' => 'IBLOCK_SECTION_ID',
		'NAME' => 'NAME'
	);
	function Fetch()
	{
		if ($result = parent::Fetch())
		{
			$result = self::UnMapKeys($result);
		}
		return $result;
	}
	private static function isIndexedArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return false;
			}
		}
		return true;
	}
	public static function MapKeys($src)
	{
		if(!is_array($src))
		{
			return $src;
		}

		$map = self::$FIELD_MAP;
		$result = array();
		foreach($src as $k => $v)
		{
			if(isset($map[$k]))
			{
				$result[$map[$k]] = $v;
			}
		}
		return $result;
	}
	public static function MapValues($src)
	{
		if(!is_array($src))
		{
			return $src;
		}

		$map = self::$FIELD_MAP;
		$result = array();
		if(self::isIndexedArray($src))
		{
			$c = count($src);
			for($i = 0; $i < $c; $i++)
			{
				$v = $src[$i];
				if(isset($map[$v]))
				{
					$result[] = $map[$v];
				}

			}
		}
		else
		{
			foreach($src as $k => $v)
			{
				if(isset($map[$v]))
				{
					$result[$k] = $map[$v];
				}
			}
		}
		return $result;
	}
	public static function UnMapKeys($src)
	{
		if(!is_array($src))
		{
			return $src;
		}

		$map = array_flip(self::$FIELD_MAP);
		$result = array();
		foreach($src as $k => $v)
		{
			if(isset($map[$k]))
			{
				$result[$map[$k]] = $v;
			}
		}
		return $result;
	}
	public static function UnMapValues($src)
	{
		if(!is_array($src))
		{
			return $src;
		}

		$map = array_flip(self::$FIELD_MAP);
		$result = array();
		if(self::isIndexedArray($src))
		{
			$c = count($src);
			for($i = 0; $i < $c; $i++)
			{
				$v = $src[$i];
				if(isset($map[$v]))
				{
					$result[] = $map[$v];
				}

			}
		}
		else
		{
			foreach($src as $k => $v)
			{
				if(isset($map[$v]))
				{
					$result[$k] = $map[$v];
				}
			}
		}
		return $result;
	}
}
