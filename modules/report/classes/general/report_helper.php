<?php

use Bitrix\Main\Entity;

abstract class CReportHelper
{
	const UF_DATETIME_SHORT_POSTFIX = '_DTSHORT';

	protected static $arUFId = null;
	protected static $ufInfo = null;
	protected static $ufEnumerations = null;

	abstract public static function getEntityName();

	abstract public static function getOwnerId();

	abstract public static function getColumnList();

	abstract public static function getDefaultColumns();

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
				case 'enumeration':
					$dataType = 'enum';
					break;
				case 'boolean':
					$dataType = 'boolean';
					break;
			}
		}

		return $dataType;
	}

	public static function getUserFieldEnumerationValue($ufId, $ufName, $valueKey)
	{
		$value = '';
		
		if (is_array(self::$ufEnumerations) && isset(self::$ufEnumerations[$ufId][$ufName][$valueKey]['VALUE']))
			$value = self::$ufEnumerations[$ufId][$ufName][$valueKey]['VALUE'];

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
				$fieldType = $treeElem['field'] ? self::getFieldDataType($treeElem['field']) : null;
			}

			if (empty($branch))
			{
				// single field
				// replace by static:: when php 5.3 available
				$htmlElem = self::buildSelectTreePopupElelemnt(
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

				$scalarTypes = array('integer', 'float', 'string', 'boolean', 'datetime', 'enum');
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
					// replace by static:: when php 5.3 available
					$html .= self::buildSelectTreePopupElelemnt(GetMessage('REPORT_CHOOSE').'...', $treeElem['humanTitle'], $fieldDefinition, $fieldType);
				}

				// replace by static:: when php 5.3 available
				$html .= self::buildHTMLSelectTreePopup($branch, $withReferencesChoose, $level+1);

				$html .= '</div>';
			}
		}

		return $html;
	}

	public static function buildSelectTreePopupElelemnt($humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType, $ufInfo = array())
	{
		// replace by static:: when php 5.3 available
		$grcFields = self::getGrcColumns();

		$isUF = false;
		$ufId = $ufName = '';
		if (is_array($ufInfo) && isset($ufInfo['ENTITY_ID']) && isset($ufInfo['FIELD_NAME']))
		{
			$ufId = $ufInfo['ENTITY_ID'];
			$ufName = $ufInfo['FIELD_NAME'];
			$isUF = true;
		}

		$htmlCheckbox = sprintf(
			'<input type="checkbox" name="%s" title="%s" fieldType="%s" isGrc="%s" isUF="%s"%s class="reports-add-popup-checkbox" />',
			htmlspecialcharsbx($fieldDefinition), htmlspecialcharsbx($fullHumanTitle), htmlspecialcharsbx($fieldType),
			(int) in_array($fieldDefinition, $grcFields), (int)($isUF === true),
			($isUF ? 'ufId="'.htmlspecialcharsbx($ufId).'"' : '').($isUF ? 'ufName="'.htmlspecialcharsbx($ufName).'"' : '')
		);

		$htmlElem = sprintf('<div class="reports-add-popup-item">
			<span class="reports-add-pop-left-bord"></span><span
			class="reports-add-popup-checkbox-block">
				%s
			</span><span class="reports-add-popup-it-text%s">%s</span>
		</div>', $htmlCheckbox, $isUF ? ' uf' : '', $humanTitle);

		return $htmlElem;
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
					$username = CUser::FormatName(CSite::GetNameFormat(false), $user, true);
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

	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options)
	{
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

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total)
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
			$v = self::getUserFieldEnumerationValue($ufInfo['ENTITY_ID'], $ufInfo['FIELD_NAME'], $v);
		}
		elseif ($dataType == 'datetime' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt'])
		)
		{
			$v = ($v instanceof \Bitrix\Main\Type\DateTime || $v instanceof \Bitrix\Main\Type\Date) ? ConvertTimeStamp($v->getTimestamp(), 'SHORT') : '';
		}
		elseif ($dataType == 'float' && !empty($v) && !$isUF)
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

	public static function formatResultsTotal(&$total, &$columnInfo)
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

	abstract public static function getPeriodFilter($date_from, $date_to);

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
}

