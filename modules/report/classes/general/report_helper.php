<?php

use Bitrix\Main\Entity;

abstract class CReportHelper
{
	const UF_DATETIME_SHORT_POSTFIX = '_DTSHORT';
	const UF_TEXT_TRIM_POSTFIX = '_TRIMTX';

	protected static $userNameFormat = null;

	protected static $arUFId = null;
	protected static $ufInfo = null;
	protected static $ufEnumerations = null;
	protected static $ufFiles = array();
	protected static $ufEmployees = array();
	protected static $ufDiskFiles = array();
	protected static $ufCrmElements = array();
	protected static $ufCrmStatuses = array();
	protected static $ufIblockElements = array();
	protected static $ufIblockSections = array();

	public static function getEntityName()
	{
		throw new \Bitrix\Main\SystemException('Method "getEntityName" must be defined in child class.');
	}

	public static function getOwnerId()
	{
		throw new \Bitrix\Main\SystemException('Method "getOwnerId" must be defined in child class.');
	}

	public static function getColumnList()
	{
		throw new \Bitrix\Main\SystemException('Method "getColumnList" must be defined in child class.');
	}

	public static function getDefaultColumns()
	{
		throw new \Bitrix\Main\SystemException('Method "getDefaultColumns" must be defined in child class.');
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		throw new \Bitrix\Main\SystemException('Method "getPeriodFilter" must be defined in child class.');
	}

	protected static function prepareUFInfo()
	{
		if (!is_array(self::$arUFId) || count(self::$arUFId) <= 0 || is_array(self::$ufInfo))
			return;

		self::$arUFId = array();
		self::$ufInfo = array();
		self::$ufEnumerations = array();
	}

	public static function &getUFInfo()
	{
		static::prepareUFInfo();

		return self::$ufInfo;
	}

	public static function &getUFEnumerations()
	{
		static::prepareUFInfo();

		return self::$ufEnumerations;
	}

	public static function detectUserField($field)
	{
		static::prepareUFInfo();

		$arUF = array(
			'isUF' => false,
			'ufInfo' => null
		);

		if ($field instanceof \Bitrix\Main\Entity\ExpressionField && is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
		{
			$ufKey = $field->getName();
			$ufId = $field->getEntity()->getUFId();
			if (is_string($ufId) && !empty($ufId) && array_key_exists($ufId, self::$ufInfo)
				&& is_array(self::$ufInfo[$ufId])
				&& array_key_exists($ufKey, self::$ufInfo[$ufId]))
			{
				$arUF['isUF'] = true;
				$arUF['ufInfo'] = self::$ufInfo[$ufId][$ufKey];
			}
		}

		return $arUF;
	}

	public static function getUserFieldDataType($arUF)
	{
		$result = false;

		if (is_array($arUF) && isset($arUF['isUF']) && $arUF['isUF'] === true && isset($arUF['ufInfo'])
			&& is_array($arUF['ufInfo']) && isset($arUF['ufInfo']['USER_TYPE_ID']))
		{
			$result = $arUF['ufInfo']['USER_TYPE_ID'];
		}

		return $result;
	}

	public static function getFieldDataType($field)
	{
		static::prepareUFInfo();

		/** @var Entity\Field $field*/
		$dataType = $field->getDataType();

		// until the date type is not supported
		if ($dataType === 'date')
			$dataType = 'datetime';

		$ufInfo = null;
		if ($field instanceof Entity\ExpressionField && is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
		{
			$ufKey = $field->getName();
			$ufId = $field->getEntity()->getUFId();
			if (is_string($ufId) && !empty($ufId) && array_key_exists($ufId, self::$ufInfo)
				&& is_array(self::$ufInfo[$ufId])
				&& array_key_exists($ufKey, self::$ufInfo[$ufId]))
			{
				$ufInfo = self::$ufInfo[$ufId][$ufKey];
			}
			unset($ufKey);
		}

		if (is_array($ufInfo) && isset($ufInfo['USER_TYPE_ID']))
		{
			switch ($ufInfo['USER_TYPE_ID'])
			{
				case 'integer':
					$dataType = 'integer';
					break;
				case 'double':
					$dataType = 'float';
					break;
				case 'boolean':
					$dataType = 'boolean';
					break;
				case 'date':
					$dataType = 'datetime';
					break;
				case 'datetime':
					$dataType = 'datetime';
					break;
				case 'enumeration':
					$dataType = 'enum';
					break;
				case 'employee':
					$dataType = 'employee';
					break;
				case 'file':
					$dataType = 'file';
					break;
				case 'disk_file':
					$dataType = 'disk_file';
					break;
				case 'crm':
					$dataType = 'crm';
					break;
				case 'crm_status':
					$dataType = 'crm_status';
					break;
				case 'iblock_element':
					$dataType = 'iblock_element';
					break;
				case 'iblock_section':
					$dataType = 'iblock_section';
					break;
			}
		}

		return $dataType;
	}

	public static function getUserFieldEnumerationValue($valueKey, $ufInfo)
	{
		$value = '';
		$ufId = isset($ufInfo['ENTITY_ID']) ? strval($ufInfo['ENTITY_ID']) : '';
		$ufName = isset($ufInfo['FIELD_NAME']) ? strval($ufInfo['FIELD_NAME']) : '';

		if (!empty($ufId) && !empty($ufName))
		{
			if (is_array(self::$ufEnumerations) && isset(self::$ufEnumerations[$ufId][$ufName][$valueKey]['VALUE']))
				$value = self::$ufEnumerations[$ufId][$ufName][$valueKey]['VALUE'];
		}

		return $value;
	}

	public static function getUserFieldFileValue($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufFiles) && is_array(self::$ufFiles[$valueKey]))
			{
				$arFile = self::$ufFiles[$valueKey];
				/*
				 * save security
				 *
				$src = $arFile['SRC'];
				$file = new CFile();
				$value = '<a target="_blank" href="'.htmlspecialcharsbx($src).'" title="'.
					htmlspecialcharsbx($file->FormatSize($arFile['FILE_SIZE'])).'">'.
					htmlspecialcharsbx($arFile['FILE_NAME']).'</a>';
				*/
				$file = new CFile();
				$value = htmlspecialcharsbx($arFile['FILE_NAME'].' ('.$file->FormatSize($arFile['FILE_SIZE']).')');
			}
			else
			{
				$value = htmlspecialcharsbx(GetMessage('REPORT_FILE_NOT_FOUND'));
			}
		}

		return $value;
	}

	public static function getUserFieldFileValueForChart($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufFiles) && is_array(self::$ufFiles[$valueKey]))
			{
				$arFile = self::$ufFiles[$valueKey];
				$value = htmlspecialcharsbx($arFile['FILE_NAME']);
			}
			else
			{
				$value = htmlspecialcharsbx(GetMessage('REPORT_FILE_NOT_FOUND'));
			}
		}

		return $value;
	}

	public static function getUserFieldDiskFileValue($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufDiskFiles) && is_array(self::$ufDiskFiles[$valueKey]))
			{
				$arDiskFile = self::$ufDiskFiles[$valueKey];
				$src = isset($arDiskFile['DOWNLOAD_URL']) ? strval($arDiskFile['DOWNLOAD_URL']) : '';
				$file = new CFile();
				if (!empty($src))
				{
					$value = '<a target="_blank" href="'.htmlspecialcharsbx($src).'" title="'.
						htmlspecialcharsbx($file->FormatSize($arDiskFile['SIZE'])).'">'.
						htmlspecialcharsbx($arDiskFile['NAME']).'</a>';
				}
				else
				{
					$value = htmlspecialcharsbx($arDiskFile['NAME'].' ('.$file->FormatSize($arDiskFile['SIZE']).')');
				}
			}
			else
			{
				$value = htmlspecialcharsbx(GetMessage('REPORT_FILE_NOT_FOUND'));
			}
		}

		return $value;
	}

	public static function getUserFieldDiskFileValueForChart($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufDiskFiles) && is_array(self::$ufDiskFiles[$valueKey]))
			{
				$arFile = self::$ufDiskFiles[$valueKey];
				$value = htmlspecialcharsbx($arFile['NAME']);
			}
			else
			{
				$value = htmlspecialcharsbx(GetMessage('REPORT_FILE_NOT_FOUND'));
			}
		}

		return $value;
	}

	public static function getUserFieldEmployeeValue($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufEmployees) && is_array(self::$ufEmployees[$valueKey]))
			{
				$employeeName = CUser::FormatName(self::getUserNameFormat(), self::$ufEmployees[$valueKey], true);
				if (!empty($employeeName))
				{
					$employeeLink = str_replace(
						array('#ID#', '#USER_ID#'),
						urlencode($valueKey),
						COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/')
					);
					if (empty($employeeLink))
						$value = $employeeName;
					else
						$value = '<a href="'.$employeeLink.'">'.$employeeName.'</a>';
				}
			}
			else
			{
				$value = htmlspecialcharsbx(GetMessage('REPORT_USER_NOT_FOUND'));
			}
		}

		return $value;
	}

	public static function getUserFieldEmployeeValueForChart($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufEmployees) && is_array(self::$ufEmployees[$valueKey]))
			{
				$employeeName = CUser::FormatName(self::getUserNameFormat(), self::$ufEmployees[$valueKey], true);
				if (!empty($employeeName))
					$value = $employeeName;
			}
			else
			{
				$value = htmlspecialcharsbx(GetMessage('REPORT_USER_NOT_FOUND'));
			}
		}

		return $value;
	}

	public static function getUserFieldCrmValue($valueKey, $ufInfo)
	{
		$valueKey = trim(strval($valueKey));
		$value = '';

		if (strlen($valueKey) > 0)
		{
			$prefixByType = array(
				'lead' => 'L',
				'contact' => 'C',
				'company' => 'CO',
				'deal' => 'D',
				'quote' => 'Q'
			);
			$maxPrefixLength = 2;    // 'CO'
			$singleTypePrefix = '';
			if (is_array($ufInfo['SETTINGS']))
			{
				$supportedTypes = array();
				foreach ($ufInfo['SETTINGS'] as $type => $supported)
				{
					if ($supported === 'Y')
						$supportedTypes[$type] = true;
				}
				$supportedTypes = array_keys($supportedTypes);
				if (count($supportedTypes) === 1)
				{
					if (isset($prefixByType[strtolower($supportedTypes[0])]))
						$singleTypePrefix = $prefixByType[strtolower($supportedTypes[0])];
				}
				unset($supportedTypes, $type, $supported);
			}

			$prefix = '';
			if (($pos = strpos(substr($valueKey, 0, $maxPrefixLength + 1), '_')) !== false && $pos > 0)
				$prefix = substr($valueKey, 0, $pos);
			if (empty($prefix))
				$valueKey = $singleTypePrefix . '_' . $valueKey;
			unset($prefix, $pos);

			if (is_array(self::$ufCrmElements) && is_array(self::$ufCrmElements[$valueKey]))
			{
				$element = self::$ufCrmElements[$valueKey];
				$item = explode('_', $valueKey);
				$arEntityType = array_flip($prefixByType);
				if (strlen($item[0]) > 0 && strlen($item[1]) > 0)
				{
					$entityTitle = $entityLink = '';
					switch ($item[0])
					{
						case 'L':
						case 'CO':
						case 'D':
							$entityTitle = $element['TITLE'];
							break;
						case 'C':
							$entityTitle = CUser::FormatName(
								static::getUserNameFormat(),
								array(
									'LOGIN' => '',
									'NAME' => $element['NAME'],
									'SECOND_NAME' => $element['SECOND_NAME'],
									'LAST_NAME' => $element['LAST_NAME']
								),
								false,
								false
							);
							break;
					}
					if (isset($arEntityType[$item[0]]))
					{
						$entityLink = CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_'.$arEntityType[$item[0]].'_show'),
							array($arEntityType[$item[0]].'_id' => $element['ID'])
						);
					}
					if (strlen($entityTitle) > 0)
					{
						if (strlen($entityLink) > 0)
						{
							$value = '<a target="_blank" href="'.$entityLink.'">'.
								htmlspecialcharsbx($entityTitle).'</a>';
						}
						else
							$value = htmlspecialcharsbx($entityTitle);
					}
				}
			}
		}

		return $value;
	}

	public static function getUserFieldCrmValueForChart($valueKey, $ufInfo)
	{
		$valueKey = trim(strval($valueKey));
		$value = '';

		if (strlen($valueKey) > 0)
		{
			if (is_array(self::$ufCrmElements) && is_array(self::$ufCrmElements[$valueKey]))
			{
				$element = self::$ufCrmElements[$valueKey];
				$item = explode('_', $valueKey);
				if (strlen($item[0]) > 0 && strlen($item[1]) > 0)
				{
					switch ($item[0])
					{
						case 'L':
						case 'CO':
						case 'D':
							$value = $element['TITLE'];
							break;
						case 'C':
							$value = $element['FULL_NAME'];
							break;
						default:
							$value = strval($element);
					}
				}
			}
		}

		return htmlspecialcharsbx($value);
	}

	public static function getUserFieldCrmStatusValue($valueKey, $ufInfo)
	{
		$entityType = isset($ufInfo['SETTINGS']['ENTITY_TYPE']) ? strval($ufInfo['SETTINGS']['ENTITY_TYPE']) : '';
		$valueKey = trim(strval($valueKey));
		$value = '';

		if (!empty($entityType) && strlen($valueKey) > 0)
		{
			if (is_array(self::$ufCrmStatuses) && isset(self::$ufCrmStatuses[$entityType][$valueKey]))
				$value = htmlspecialcharsbx(self::$ufCrmStatuses[$entityType][$valueKey]);
		}

		return $value;
	}

	public static function getUserFieldIblockElementValue($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufIblockElements) && is_array(self::$ufIblockElements[$valueKey]))
			{
				$element = self::$ufIblockElements[$valueKey];
				$elementLink = '';
				$elementName = $element['~NAME'];
				if (!empty($element['~DETAIL_PAGE_URL']))
					$elementLink = $element['~DETAIL_PAGE_URL'];
				if (strlen($elementName) > 0)
				{
					if (strlen($elementLink) > 0)
					{
						$value = '<a target="_blank" href="'.$elementLink.'">'.
							htmlspecialcharsbx($elementName).'</a>';
					}
					else
						$value = htmlspecialcharsbx($elementName);
				}
			}
		}

		return $value;
	}

	public static function getUserFieldIblockElementValueForChart($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufIblockElements) && is_array(self::$ufIblockElements[$valueKey]))
			{
				$element = self::$ufIblockElements[$valueKey];
				$elementName = $element['~NAME'];
				if (strlen($elementName) > 0)
					$value = htmlspecialcharsbx($elementName);
			}
		}

		return $value;
	}

	public static function getUserFieldIblockSectionValue($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufIblockSections) && is_array(self::$ufIblockSections[$valueKey]))
			{
				$section = self::$ufIblockSections[$valueKey];
				$sectionLink = '';
				$sectionName = $section['~NAME'];
				if (!empty($section['~SECTION_PAGE_URL']))
					$sectionLink = $section['~SECTION_PAGE_URL'];
				if (strlen($sectionName) > 0)
				{
					if (strlen($sectionLink) > 0)
					{
						$value = '<a target="_blank" href="'.$sectionLink.'">'.
							htmlspecialcharsbx($sectionName).'</a>';
					}
					else
						$value = htmlspecialcharsbx($sectionName);
				}
			}
		}

		return $value;
	}

	public static function getUserFieldIblockSectionValueForChart($valueKey, $ufInfo)
	{
		$valueKey = intval($valueKey);
		$value = '';

		if ($valueKey > 0)
		{
			if (is_array(self::$ufIblockSections) && is_array(self::$ufIblockSections[$valueKey]))
			{
				$section = self::$ufIblockSections[$valueKey];
				$sectionName = $section['~NAME'];
				if (strlen($sectionName) > 0)
					$value = htmlspecialcharsbx($sectionName);
			}
		}

		return $value;
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		// do nothing here, could be overwritten in children
	}

	public static function getCustomColumnTypes()
	{
		return array();
	}

	public static function getGrcColumns()
	{
		return array();
	}

	public static function getCalcVariations()
	{
		return array(
			'integer' => array(
				'MIN',
				'AVG',
				'MAX',
				'SUM',
				'COUNT_DISTINCT'
			),
			'float' => array(
				'MIN',
				'AVG',
				'MAX',
				'SUM',
				'COUNT_DISTINCT'
			),
			'string' => array(
				'COUNT_DISTINCT'
			),
			'text' => array(
				'COUNT_DISTINCT'
			),
			'boolean' => array(
				'SUM'
			),
			'datetime' => array(
				'MIN',
				'MAX',
				'COUNT_DISTINCT'
			),
			'enum' => array(
				'COUNT_DISTINCT'
			),
			'file' => array(
				'COUNT_DISTINCT'
			),
			'disk_file' => array(
				'COUNT_DISTINCT'
			),
			'employee' => array(
				'COUNT_DISTINCT'
			),
			'crm' => array(
				'COUNT_DISTINCT'
			),
			'crm_status' => array(
				'COUNT_DISTINCT'
			),
			'iblock_element' => array(
				'COUNT_DISTINCT'
			),
			'iblock_section' => array(
				'COUNT_DISTINCT'
			)
		);
	}

	public static function getCompareVariations()
	{
		return array(
			'integer' => array(
				'EQUAL',
				'GREATER_OR_EQUAL',
				'GREATER',
				'LESS',
				'LESS_OR_EQUAL',
				'NOT_EQUAL'
			),
			'float' => array(
				'EQUAL',
				'GREATER_OR_EQUAL',
				'GREATER',
				'LESS',
				'LESS_OR_EQUAL',
				'NOT_EQUAL'
			),
			'string' => array(
				'EQUAL',
				'START_WITH',
				'CONTAINS',
				'NOT_CONTAINS',
				'NOT_EQUAL'
			),
			'text' => array(
				'EQUAL',
				'START_WITH',
				'CONTAINS',
				'NOT_CONTAINS',
				'NOT_EQUAL'
			),
			'boolean' => array(
				'EQUAL'
			),
			'datetime' => array(
				'EQUAL',
				'GREATER_OR_EQUAL',
				'GREATER',
				'LESS',
				'LESS_OR_EQUAL',
				'NOT_EQUAL'
			),
			'\Bitrix\Main\User' => array(
				'EQUAL'
			),
			'\Bitrix\Socialnetwork\Workgroup' => array(
				'EQUAL'
			),
			'enum' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'file' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'disk_file' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'employee' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'crm' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'crm_status' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'iblock_element' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'iblock_section' => array(
				'EQUAL',
				'NOT_EQUAL'
			)
		);
	}

	public static function buildHTMLSelectTreePopup($tree, $withReferencesChoose = false, $level = 0)
	{
		$html = '';

		$i = 0;

		foreach($tree as $treeElem)
		{
			$isLastElem = (++$i == count($tree));

			$fieldDefinition = $treeElem['fieldName'];
			$branch = $treeElem['branch'];

			$fieldType = null;
			$customColumnTypes = static::getCustomColumnTypes();
			if (array_key_exists($fieldDefinition, $customColumnTypes))
			{
				$fieldType = $customColumnTypes[$fieldDefinition];
			}
			else
			{
				$fieldType = $treeElem['field'] ? static::getFieldDataType($treeElem['field']) : null;
			}

			// file fields is not filtrable
			if ($withReferencesChoose && ($fieldType === 'file' || $fieldType === 'disk_file'))
				continue;

			if (empty($branch))
			{
				// single field
				$htmlElem = static::buildSelectTreePopupElelemnt(
					$treeElem['humanTitle'], $treeElem['fullHumanTitle'], $fieldDefinition, $fieldType,
					($treeElem['isUF'] === true && is_array($treeElem['ufInfo'])) ? $treeElem['ufInfo'] : array()
				);

				if ($isLastElem && $level > 0)
				{
					$htmlElem = str_replace(
						'<div class="reports-add-popup-item">',
						'<div class="reports-add-popup-item reports-add-popup-item-last">',
						$htmlElem
					);

				}

				$html .= $htmlElem;
			}
			else
			{
				// add branch

				$scalarTypes = array('integer', 'float', 'string', 'text', 'boolean', 'file', 'disk_file', 'datetime',
					'enum', 'employee', 'crm', 'crm_status', 'iblock_element', 'iblock_section');
				if ($withReferencesChoose &&
					(in_array($fieldType, $scalarTypes) || empty($fieldType))
				)
				{
					// ignore virtual branches (without references)
					continue;
				}

				$html .= sprintf('<div class="reports-add-popup-item reports-add-popup-it-node">
					<span class="reports-add-popup-arrow"></span><span
						class="reports-add-popup-it-text">%s</span>
				</div>', $treeElem['humanTitle']);

				$html .= '<div class="reports-add-popup-it-children">';

				// add self
				if ($withReferencesChoose)
				{
					$html .= static::buildSelectTreePopupElelemnt(GetMessage('REPORT_CHOOSE').'...', $treeElem['humanTitle'], $fieldDefinition, $fieldType);
				}

				$html .= static::buildHTMLSelectTreePopup($branch, $withReferencesChoose, $level+1);

				$html .= '</div>';
			}
		}

		return $html;
	}

	public static function buildSelectTreePopupElelemnt($humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType, $ufInfo = array())
	{
		// replace by static:: when php 5.3 available
		$grcFields = static::getGrcColumns();

		$isUF = false;
		$isMultiple = false;
		$ufId = $ufName = '';
		if (is_array($ufInfo) && isset($ufInfo['ENTITY_ID']) && isset($ufInfo['FIELD_NAME']))
		{
			$ufId = $ufInfo['ENTITY_ID'];
			$ufName = $ufInfo['FIELD_NAME'];
			if (isset($ufInfo['MULTIPLE']) && $ufInfo['MULTIPLE'] === 'Y')
				$isMultiple = true;
			$isUF = true;
		}

		$htmlCheckbox = sprintf(
			'<input type="checkbox" name="%s" title="%s" fieldType="%s" isGrc="%s" isUF="%s"%s class="reports-add-popup-checkbox" />',
			htmlspecialcharsbx($fieldDefinition), htmlspecialcharsbx($fullHumanTitle), htmlspecialcharsbx($fieldType),
			(int) in_array($fieldDefinition, $grcFields), (int)$isUF,
			($isUF ? 'ufId="'.htmlspecialcharsbx($ufId).'"' : '').($isUF ? 'isMultiple="'.(int)$isMultiple.'" ufName="'.htmlspecialcharsbx($ufName).'"' : '')
		);

		$htmlElem = sprintf('<div class="reports-add-popup-item">
			<span class="reports-add-pop-left-bord"></span><span
			class="reports-add-popup-checkbox-block">
				%s
			</span><span class="reports-add-popup-it-text%s">%s</span>
		</div>', $htmlCheckbox, $isUF ? ' uf' : '', $humanTitle);

		return $htmlElem;
	}

	public static function getCustomSelectFields($select, $fList)
	{
		return array();
	}

	public static function fillFilterReferenceColumns(&$filters, &$fieldList)
	{
		foreach ($filters as &$filter)
		{
			foreach ($filter as &$fElem)
			{
				if (is_array($fElem) && $fElem['type'] == 'field')
				{
					$field = $fieldList[$fElem['name']];

					if ($field instanceof Entity\ReferenceField)
						static::fillFilterReferenceColumn($fElem, $field);
				}
			}
		}
	}

	public static function fillFilterReferenceColumn(&$filterElement, Entity\ReferenceField $field)
	{
		if ($field->getRefEntityName() == '\Bitrix\Main\User')
		{
			// USER
			if ($filterElement['value'])
			{
				$res = CUser::GetByID($filterElement['value']);
				$user = $res->fetch();

				if ($user)
				{
					$username = CUser::FormatName(static::getUserNameFormat(), $user, true);
					$filterElement['value'] = array('id' => $user['ID'], 'name' => $username);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('REPORT_USER_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		else if ($field->getRefEntityName() == '\Bitrix\Socialnetwork\Workgroup')
		{
			// GROUP
			if ($filterElement['value'])
			{
				$group = CSocNetGroup::GetByID($filterElement['value']);

				if ($group)
				{
					$filterElement['value'] = array(array('id' => $group['ID'], 'title' => $group['NAME']));
				}
				else
				{
					$filterElement['value'] = array(array('id' => $filterElement['value'], 'title' => GetMessage('REPORT_PROJECT_NOT_FOUND')));
				}
			}
			else
			{
				$filterElement['value'] = array(array('id' => ''));
			}
		}
	}

	public static function fillFilterUFColumns(&$filters, &$fieldList)
	{
		foreach ($filters as &$filter)
		{
			foreach ($filter as &$fElem)
			{
				if (is_array($fElem) && $fElem['type'] == 'field')
				{
					$field = $fieldList[$fElem['name']];

					$arUF = static::detectUserField($field);
					if ($arUF['isUF'] && is_array($arUF['ufInfo']) && isset($arUF['ufInfo']['USER_TYPE_ID']))
						static::fillFilterUFColumn($fElem, $field, $arUF['ufInfo']);
				}
			}
		}
	}

	public static function fillFilterUFColumn(&$filterElement, $field, $ufInfo)
	{
		if ($ufInfo['USER_TYPE_ID'] === 'employee')
		{
			$value = intval($filterElement['value']);
			if ($value > 0)
			{
				$user = new CUser();
				$res = $user->GetByID($value);
				$arUser = $res->fetch();

				if ($arUser)
				{
					$userName = CUser::FormatName(self::getUserNameFormat(), $arUser, true);
					$filterElement['value'] = array('id' => $arUser['ID'], 'name' => $userName);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('REPORT_USER_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
	}

	public static function beforeFilterBackReferenceRewrite(&$filter, $viewColumns)
	{
	}

	public static function getEntityFilterPrimaryFieldName($fElem)
	{
		return 'ID';
	}

	public static function confirmFilterBackReferenceRewrite($fElem, $chain)
	{
		return true;
	}

	public static function confirmSelectBackReferenceRewrite($elem, $chain)
	{
		return true;
	}

	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
	}

	public static function rewriteResultRowValues(&$row, &$columnInfo)
	{
	}

	public static function collectUFValues($rows, $columnInfo, $total)
	{
		// uf columns
		$fileColumns = array();
		$diskFileColumns = array();
		$employeeColumns = array();
		$crmColumns = array();
		$crmStatusColumns = array();
		$iblockElementColumns = array();
		$iblockSectionColumns = array();
		if (is_array($columnInfo))
		{
			foreach ($columnInfo as $k => $cInfo)
			{
				if ($cInfo['isUF'] && is_array($cInfo['ufInfo']) && isset($cInfo['ufInfo']['USER_TYPE_ID']))
				{
					switch ($cInfo['ufInfo']['USER_TYPE_ID'])
					{
						case 'file':
							$fileColumns[$k] = true;
							break;
						case 'disk_file':
							$diskFileColumns[$k] = true;
							break;
						case 'employee':
							$employeeColumns[$k] = true;
							break;
						case 'crm':
							$crmColumns[$k] = true;
							break;
						case 'crm_status':
							$crmStatusColumns[$k] = true;
							break;
						case 'iblock_element':
							$iblockElementColumns[$k] = true;
							break;
						case 'iblock_section':
							$iblockSectionColumns[$k] = true;
							break;
					}
				}
			}
		}

		$arFileID = array();
		$arDiskFileID = array();
		$arEmployeeID = array();
		$arCrmID = array();
		$arCrmStatusID = array();
		$arCrmStatusEntityType = array();
		$arIblockElementID = array();
		$arIblockSectionID = array();
		if (count($fileColumns) > 0 || count($diskFileColumns) > 0 || count($employeeColumns) > 0
			|| count($crmColumns) > 0 || count($crmStatusColumns) > 0 || count($iblockElementColumns) > 0
			|| count($iblockSectionColumns) > 0)
		{
			foreach ($rows as $row)
			{
				foreach ($row as $k => $v)
				{
					// file
					if (isset($fileColumns[$k]))
					{
						if (is_array($v))
							foreach ($v as $subv)
							{
								$value = intval($subv);
								if ($value > 0)
									$arFileID[] = $value;
							}
						else
						{
							$value = intval($v);
							if ($value > 0)
								$arFileID[] = $value;
						}
					}

					// disk file
					if (isset($diskFileColumns[$k]))
					{
						if (is_array($v))
							foreach ($v as $subv)
							{
								$value = intval($subv);
								if ($value > 0)
									$arDiskFileID[] = $value;
							}
						else
						{
							$value = intval($v);
							if ($value > 0)
								$arDiskFileID[] = $value;
						}
					}

					// employee
					if (isset($employeeColumns[$k]))
					{
						if (is_array($v))
							foreach ($v as $subv)
							{
								$value = intval($subv);
								if ($value > 0)
									$arEmployeeID[] = $value;
							}
						else
						{
							$value = intval($v);
							if ($value > 0)
								$arEmployeeID[] = $value;
						}
					}
					
					// crm
					if (isset($crmColumns[$k]))
					{
						$prefixByType = array(
							'lead' => 'L',
							'contact' => 'C',
							'company' => 'CO',
							'deal' => 'D',
							'quote' => 'Q'
						);
						$maxPrefixLength = 2;    // 'CO'
						$singleTypePrefix = '';
						if (is_array($columnInfo[$k]['ufInfo']['SETTINGS']))
						{
							$supportedTypes = array();
							foreach ($columnInfo[$k]['ufInfo']['SETTINGS'] as $type => $supported)
							{
								if ($supported === 'Y')
									$supportedTypes[$type] = true;
							}
							$supportedTypes = array_keys($supportedTypes);
							if (count($supportedTypes) === 1)
							{
								if (isset($prefixByType[strtolower($supportedTypes[0])]))
									$singleTypePrefix = $prefixByType[strtolower($supportedTypes[0])];
							}
							unset($supportedTypes, $type, $supported);
						}

						if (is_array($v))
						{
							foreach ($v as $subv)
							{
								if (strlen($subv) > 0)
								{
									$prefix = '';
									if (($pos = strpos(substr($subv, 0, $maxPrefixLength + 1), '_')) !== false && $pos > 0)
										$prefix = substr($subv, 0, $pos);
									if (empty($prefix))
										$subv = $singleTypePrefix . '_' . $subv;
									unset($prefix, $pos);

									$value = explode('_', trim(strval($subv)));
									if (strlen($value[0]) > 0 && strlen($value[1]) > 0)
									{
										if (!is_array($arCrmID[$value[0]]))
											$arCrmID[$value[0]] = array();
										$arCrmID[$value[0]][] = $value[1];
									}
								}
							}
						}
						else
						{
							if (strlen($v) > 0)
							{
								$prefix = '';
								if (($pos = strpos(substr($v, 0, $maxPrefixLength + 1), '_')) !== false && $pos > 0)
									$prefix = substr($v, 0, $pos);
								if (empty($prefix))
									$v = $singleTypePrefix . '_' . $v;
								unset($prefix, $pos);

								$value = explode('_', trim(strval($v)));
								if (strlen($value[0]) > 0 && strlen($value[1]) > 0)
								{
									if (!is_array($arCrmID[$value[0]]))
										$arCrmID[$value[0]] = array();
									$arCrmID[$value[0]][] = $value[1];
								}
							}
						}

						unset($maxPrefixLength);
					}

					// crm_status
					if (isset($crmStatusColumns[$k]))
					{
						if (!isset($arCrmStatusEntityType[$k]))
						{
							if (isset($columnInfo[$k]['ufInfo']['SETTINGS']['ENTITY_TYPE']))
							{
								$arCrmStatusEntityType[$k] =
									strval($columnInfo[$k]['ufInfo']['SETTINGS']['ENTITY_TYPE']);
							}
						}
						if (!empty($arCrmStatusEntityType[$k]))
						{
							if (is_array($v))
								foreach ($v as $subv)
								{
									if (strlen($subv) > 0)
									{
										if (!is_array($arCrmStatusID[$arCrmStatusEntityType[$k]]))
											$arCrmStatusID[$arCrmStatusEntityType[$k]] = array();
										$arCrmStatusID[$arCrmStatusEntityType[$k]][] = $subv;
									}
								}
							else
							{
								if (strlen($v) > 0)
								{
									if (!is_array($arCrmStatusID[$arCrmStatusEntityType[$k]]))
										$arCrmStatusID[$arCrmStatusEntityType[$k]] = array();
									$arCrmStatusID[$arCrmStatusEntityType[$k]][] = $v;
								}
							}
						}
					}

					// iblock_element
					if (isset($iblockElementColumns[$k]))
					{
						if (is_array($v))
							foreach ($v as $subv)
							{
								$value = intval($subv);
								if ($value > 0)
									$arIblockElementID[] = $value;
							}
						else
						{
							$value = intval($v);
							if ($value > 0)
								$arIblockElementID[] = $value;
						}
					}

					// iblock_section
					if (isset($iblockSectionColumns[$k]))
					{
						if (is_array($v))
							foreach ($v as $subv)
							{
								$value = intval($subv);
								if ($value > 0)
									$arIblockSectionID[] = $value;
							}
						else
						{
							$value = intval($v);
							if ($value > 0)
								$arIblockSectionID[] = $value;
						}
					}
				}
			}
		}
		
		// collect files
		if (count($fileColumns) > 0)
		{
			if (count($arFileID) > 0)
				$arFileID = array_unique($arFileID);

			$i = 0;
			$cnt = 0;
			$stepCnt = 500;
			$nIDs = count($arFileID);
			$arID = array();
			$file = new CFile();
			foreach ($arFileID as $fileID)
			{
				$arID[$cnt++] = $fileID;
				$i++;

				if ($cnt === $stepCnt || $i === $nIDs)
				{
					$res = $file->GetList(array(), array('@ID' => implode(',', $arID)));
					if (is_object($res))
					{
						while ($arFile = $res->Fetch())
						{
							if($arFile)
							{
								if(array_key_exists("~src", $arFile))
								{
									if($arFile["~src"])
										$arFile["SRC"] = $arFile["~src"];
									else
										$arFile["SRC"] = $file->GetFileSRC($arFile, false, false);
								}
								else
								{
									$arFile["SRC"] = $file->GetFileSRC($arFile, false);
								}

								self::$ufFiles[intval($arFile['ID'])] = $arFile;
							}
						}
					}

					$cnt = 0;
					$arID = array();
				}
			}
		}

		// collect disk files
		if (count($diskFileColumns) > 0)
		{
			if (count($arDiskFileID) > 0)
				$arDiskFileID = array_unique($arDiskFileID);

			$i = 0;
			$cnt = 0;
			$stepCnt = 500;
			$nIDs = count($arDiskFileID);
			$arID = array();
			foreach ($arDiskFileID as $diskFileID)
			{
				$arID[$cnt++] = $diskFileID;
				$i++;

				if ($cnt === $stepCnt || $i === $nIDs)
				{
					$res = \Bitrix\Disk\AttachedObject::getList(array(
						'filter' => array('ID' => $arID),
						'select' => array(
							'ID', 'NAME' => 'OBJECT.NAME', 'SIZE' => 'OBJECT.SIZE'
						),
					));
					$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
					if (is_object($res))
					{
						while ($arDiskFile = $res->Fetch())
						{
							if($arDiskFile)
							{
								$arDiskFile['DOWNLOAD_URL'] = $urlManager->getUrlUfController(
									'download',
									array('attachedId' => $arDiskFile['ID'])
								);
								self::$ufDiskFiles[intval($arDiskFile['ID'])] = $arDiskFile;
							}
						}
					}

					$cnt = 0;
					$arID = array();
				}
			}
		}

		// collect employees
		if (count($employeeColumns) > 0)
		{
			if (count($arEmployeeID) > 0)
				$arEmployeeID = array_unique($arEmployeeID);

			$i = 0;
			$cnt = 0;
			$stepCnt = 500;
			$nIDs = count($arEmployeeID);
			$arID = array();
			foreach ($arEmployeeID as $employeeID)
			{
				$arID[$cnt++] = $employeeID;
				$i++;

				if ($cnt === $stepCnt || $i === $nIDs)
				{
					$res = \Bitrix\Main\UserTable::getList(
						array(
							'filter' => array('ID' => $arID),
							'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE')
						)
					);
					if (is_object($res))
					{
						while ($arUser = $res->fetch())
							self::$ufEmployees[intval($arUser['ID'])] = $arUser;
					}

					$cnt = 0;
					$arID = array();
				}
			}
		}

		// collect crm elements
		if (count($crmColumns) > 0 && CModule::IncludeModule('crm'))
		{
			foreach ($arCrmID as $typeIndex => $arSubID)
			{
				if (count($arSubID) > 0)
					$arCrmID[$typeIndex] = array_unique($arSubID);

				$i = 0;
				$cnt = 0;
				$stepCnt = 500;
				$nIDs = count($arSubID);
				$arID = array();
				foreach ($arSubID as $crmID)
				{
					$arID[$cnt++] = $crmID;
					$i++;

					if ($cnt === $stepCnt || $i === $nIDs)
					{
						$res = null;
						switch ($typeIndex)
						{
							case 'L':
								$res = CCrmLead::GetList(
									array('ID' => 'DESC'),
									array('ID' => $arID),
									array('ID', 'TITLE', 'FULL_NAME', 'STATUS_ID')
								);
								break;
							case 'C':
								$res = CCrmContact::GetList(
									array('ID' => 'DESC'),
									array('ID' => $arID),
									array(
										'ID', 'NAME', 'SECOND_NAME', 'LAST_NAME',
										'FULL_NAME', 'COMPANY_TITLE', 'PHOTO'
									)
								);
								break;
							case 'CO':
								$res = CCrmCompany::GetList(
									array('ID' => 'DESC'),
									array('ID' => $arID),
									array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
								);
								break;
							case 'D':
								$res = CCrmDeal::GetList(
									array('ID' => 'DESC'),
									array('ID' => $arID),
									array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
								);
								break;
						}
						if (is_object($res))
						{
							while ($arCrmElement = $res->Fetch())
								self::$ufCrmElements[$typeIndex.'_'.$arCrmElement['ID']] = $arCrmElement;
						}

						$cnt = 0;
						$arID = array();
					}
				}
			}
		}

		// collect crm statuses
		if (count($crmStatusColumns) > 0 && CModule::IncludeModule('crm'))
		{
			foreach ($arCrmStatusID as $entityType => $arSubID)
			{
				if (count($arSubID) > 0)
					$arCrmID[$entityType] = array_unique($arSubID);

				$res = null;
				$res = CCrmStatus::GetStatusList($entityType);
				if (is_array($res) && count($res) > 0)
				{
					foreach ($arSubID as $crmStatusID)
					{
						if (isset($res[$crmStatusID]))
						if (!isset(self::$ufCrmStatuses[$entityType]))
							self::$ufCrmStatuses[$entityType] = array();
						self::$ufCrmStatuses[$entityType][$crmStatusID] = $res[$crmStatusID];
					}
				}
			}
		}
		
		// collect iblock elements
		if (count($iblockElementColumns) > 0 && CModule::IncludeModule('iblock'))
		{
			if (count($arIblockElementID) > 0)
				$arIblockElementID = array_unique($arIblockElementID);

			$i = 0;
			$cnt = 0;
			$stepCnt = 500;
			$nIDs = count($arIblockElementID);
			$arID = array();
			foreach ($arIblockElementID as $iblockElementID)
			{
				$arID[$cnt++] = $iblockElementID;
				$i++;

				if ($cnt === $stepCnt || $i === $nIDs)
				{
					$res = CIBlockElement::GetList(array('SORT'=>'ASC'), array('=ID' => $arID));
					if (is_object($res))
					{
						while ($arIblockElement = $res->GetNext())
							self::$ufIblockElements[intval($arIblockElement['ID'])] = $arIblockElement;
					}

					$cnt = 0;
					$arID = array();
				}
			}
		}
		
		// collect iblock sections
		if (count($iblockSectionColumns) > 0 && CModule::IncludeModule('iblock'))
		{
			if (count($arIblockSectionID) > 0)
				$arIblockSectionID = array_unique($arIblockSectionID);

			$i = 0;
			$cnt = 0;
			$stepCnt = 500;
			$nIDs = count($arIblockSectionID);
			$arID = array();
			foreach ($arIblockSectionID as $iblockSectionID)
			{
				$arID[$cnt++] = $iblockSectionID;
				$i++;

				if ($cnt === $stepCnt || $i === $nIDs)
				{
					$res = CIBlockSection::GetList(
						array('left_margin' => 'asc'),
						array('ID' => $arID),
						false, array('ID', 'NAME', 'SECTION_PAGE_URL')
					);
					if (is_object($res))
					{
						while ($arIblockSection = $res->GetNext())
							self::$ufIblockSections[intval($arIblockSection['ID'])] = $arIblockSection;
					}

					$cnt = 0;
					$arID = array();
				}
			}
		}
	}

	public static function formatResults(&$rows, &$columnInfo, $total)
	{
		foreach ($rows as &$row)
		{
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = $columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as &$subv)
					{
						// replace by static:: when php 5.3 available
						self::formatResultValue($k, $subv, $row, $cInfo, $total);
					}
				}
				else
				{
					// replace by static:: when php 5.3 available
					self::formatResultValue($k, $v, $row, $cInfo, $total);
				}
			}
		}

		unset($row, $v, $subv);
	}

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		/** @var Entity\Field $field */
		$field = $cInfo['field'];

		$dataType = self::getFieldDataType($field);

		$isUF = false;
		$ufInfo = null;
		if (isset($cInfo['isUF']) && $cInfo['isUF'])
		{
			$isUF = true;
			$ufInfo = $cInfo['ufInfo'];
		}

		if ($isUF && $dataType === 'enum' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$v = static::getUserFieldEnumerationValue($v, $ufInfo);
		}
		elseif ($isUF && $dataType === 'file' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldFileValue($valueKey, $ufInfo);
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'string';
			$customChartValue['value'] = static::getUserFieldFileValueForChart($valueKey, $ufInfo);
		}
		elseif ($isUF && $dataType === 'disk_file' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldDiskFileValue($valueKey, $ufInfo);
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'string';
			$customChartValue['value'] = static::getUserFieldDiskFileValueForChart($valueKey, $ufInfo);
		}
		elseif ($isUF && $dataType === 'employee' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldEmployeeValue($valueKey, $ufInfo);
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'string';
			$customChartValue['value'] = static::getUserFieldEmployeeValueForChart($valueKey, $ufInfo);
		}
		elseif ($isUF && $dataType === 'crm' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldCrmValue($valueKey, $ufInfo);
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'string';
			$customChartValue['value'] = static::getUserFieldCrmValueForChart($valueKey, $ufInfo);
		}
		elseif ($isUF && $dataType === 'crm_status' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldCrmStatusValue($valueKey, $ufInfo);
		}
		elseif ($isUF && $dataType === 'iblock_element' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldIblockElementValue($valueKey, $ufInfo);
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'string';
			$customChartValue['value'] = static::getUserFieldIblockElementValueForChart($valueKey, $ufInfo);
		}
		elseif ($isUF && $dataType === 'iblock_section' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt']))
		{
			$valueKey = $v;
			$v = static::getUserFieldIblockSectionValue($valueKey, $ufInfo);
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'string';
			$customChartValue['value'] = static::getUserFieldIblockSectionValueForChart($valueKey, $ufInfo);
		}
		elseif ($dataType == 'datetime' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt'])
		)
		{
			$v = ($v instanceof \Bitrix\Main\Type\DateTime || $v instanceof \Bitrix\Main\Type\Date) ? ConvertTimeStamp($v->getTimestamp(), 'SHORT') : '';
		}
		elseif ($dataType == 'float' && !empty($v) && !$isUF && !strlen($cInfo['prcnt']))
		{
			$v = round($v, 1);
		}
		elseif (substr($k, -11) == '_SHORT_NAME' && (empty($cInfo['aggr']) || $cInfo['aggr'] == 'GROUP_CONCAT'))
		{
			$v = str_replace(
				array('#NOBR#', '#/NOBR#'),
				array('<NOBR>', '</NOBR>'),
				htmlspecialcharsbx($v));
		}
		elseif (substr($k, -6) == '_PRCNT' && !strlen($cInfo['prcnt']))
		{
			$v = round($v, 2). '%';
		}
		elseif ($dataType == 'boolean' && empty($cInfo['aggr']))
		{
			if ($isUF && empty($v))
				$v = 0;

			if (strlen($v))
			{
				// get bool value "yes/no"
				/** @var Entity\BooleanField $field */
				$boolValues = ($isUF ? array(0, 1) : $field->GetValues());
				$fValues = array_flip($boolValues);
				$fValue = (bool) $fValues[$v];

				$mess = 'REPORT_BOOLEAN_VALUE_' . ($fValue ? 'TRUE' : 'FALSE');
				$v = htmlspecialcharsbx(GetMessage($mess));
			}
		}
		elseif (strlen($cInfo['prcnt']))
		{
			if ($cInfo['prcnt'] == 'self_column')
			{
				if (array_key_exists('TOTAL_'.$k, $total) && $total['TOTAL_'.$k] > 0)
				{
					$v = round($v / $total['TOTAL_'.$k] * 100, 2);
				}
				else
				{
					$v = '--';
				}
			}
			else
			{
				$v = round($v, 2);
			}

			$v = $v . '%';
		}
		else
		{
			$v = htmlspecialcharsbx($v);
		}
	}

	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		foreach ($total as $k => $v)
		{
			// remove prefix TOTAL_
			$original_k = substr($k, 6);

			$cInfo = $columnInfo[$original_k];
			$field = $cInfo['field'];

			if ($field->getName() == 'ID' && empty($cInfo['aggr']) && !strlen($cInfo['prcnt']))
			{
				unset($total[$k]);
			}
			elseif (strlen($cInfo['prcnt']))
			{
				if ($cInfo['prcnt'] == 'self_column')
				{
					if (array_key_exists($k, $total) && $v > 0)
					{
						$v = round($v / $total[$k] * 100, 2);
					}
					else
					{
						$v = '--';
					}
				}
				else
				{
					$v = round($v, 2);
				}

				$total[$k] = $v . '%';
			}
			elseif (substr($k, -6) == '_PRCNT' && !strlen($cInfo['prcnt']))
			{
				$total[$k] = round($v, 2). '%';
			}
		}
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		return '';
	}

	public static function getDefaultReports()
	{
		return array();
	}

	public static function getFirstVersion()
	{
		// usually it's first version of default reports
		return '11.0.1';
	}

	public static function getCurrentVersion()
	{
		// usually it's version of helper's module
		return '11.0.1';
	}

	public static function setUserNameFormat($userNameFormat)
	{
		self::$userNameFormat = $userNameFormat;
	}

	public static function getUserNameFormat()
	{
		if (self::$userNameFormat === null)
		{
			$site = new CSite();
			self::$userNameFormat = $site->GetNameFormat(false);
		}

		return self::$userNameFormat;
	}

	public static function renderUserSearch($id, $searchInputId, $dataInputId, $componentName, $siteId = '', $nameFormat = '', $delay = 0)
	{
		$id = strval($id);
		$searchInputId = strval($searchInputId);
		$dataInputId = strval($dataInputId);
		$componentName = strval($componentName);

		$siteId = strval($siteId);
		if($siteId === '')
		{
			$siteId = SITE_ID;
		}

		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = CSite::getNameFormat(false);
		}

		$delay = intval($delay);
		if($delay < 0)
		{
			$delay = 0;
		}

		echo '<input type="text" id="', htmlspecialcharsbx($searchInputId) ,'" style="width:200px;">',
		'<input type="hidden" id="', htmlspecialcharsbx($dataInputId),'" name="',
			htmlspecialcharsbx($dataInputId),'" value="">';

		echo '<script type="text/javascript">',
		'BX.ready(function(){',
		'BX.ReportUserSearchPopup.deletePopup("', $id, '");',
		'BX.ReportUserSearchPopup.create("', $id, '", { searchInput: BX("',
			CUtil::jSEscape($searchInputId), '"), dataInput: BX("',
			CUtil::jSEscape($dataInputId),'"), componentName: "',
			CUtil::jSEscape($componentName),'", user: {} }, ', $delay,');',
		'});</script>';

		$GLOBALS['APPLICATION']->includeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputId,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteId,
				'NAME_TEMPLATE' => $nameFormat,
				'ON_CHANGE' => 'reports.onResponsiblesChange',
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
}

