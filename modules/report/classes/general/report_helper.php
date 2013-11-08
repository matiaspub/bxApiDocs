<?php

use Bitrix\Main\Entity;

abstract class CReportHelper
{
	abstract public static function getEntityName();

	abstract public static function getOwnerId();

	abstract public static function getColumnList();

	abstract public static function getDefaultColumns();

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
			'Bitrix\Main\User' => array(
				'EQUAL'
			),
			'Bitrix\Socialnetwork\Workgroup' => array(
				'EQUAL'
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

			$fieldType = $treeElem['field'] ? $treeElem['field']->GetDataType() : null;

			if (empty($branch))
			{
				// single field
				// replace by static:: when php 5.3 available
				$htmlElem = self::buildSelectTreePopupElelemnt($treeElem['humanTitle'], $treeElem['fullHumanTitle'], $fieldDefinition, $fieldType);

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

				$scalarTypes = array('integer', 'float', 'string', 'boolean', 'datetime');
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

	public static function buildSelectTreePopupElelemnt($humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType)
	{
		// replace by static:: when php 5.3 available
		$grcFields = self::getGrcColumns();

		$htmlCheckbox = sprintf(
			'<input type="checkbox" name="%s" title="%s" fieldType="%s" isGrc="%s" class="reports-add-popup-checkbox" />',
			htmlspecialcharsbx($fieldDefinition), htmlspecialcharsbx($fullHumanTitle), htmlspecialcharsbx($fieldType),
			(int) in_array($fieldDefinition, $grcFields)
		);

		$htmlElem = sprintf('<div class="reports-add-popup-item">
			<span class="reports-add-pop-left-bord"></span><span
			class="reports-add-popup-checkbox-block">
				%s
			</span><span class="reports-add-popup-it-text">%s</span>
		</div>', $htmlCheckbox, $humanTitle);

		return $htmlElem;
	}

	public static function fillFilterReferenceColumn(&$filterElement, Entity\ReferenceField $field)
	{
		if ($field->GetDataType() == 'Bitrix\Main\User')
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
		else if ($field->GetDataType() == 'Bitrix\Socialnetwork\Workgroup')
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
		$field = $cInfo['field'];
		$dataType = $field->GetDataType();

		if ($dataType == 'datetime' && !empty($v)
			&& (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			&& !strlen($cInfo['prcnt'])
		)
		{
			$v = ConvertTimeStamp(strtotime($v), 'SHORT');
		}
		elseif ($dataType == 'float' && !empty($v))
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
		elseif ($dataType == 'boolean' && empty($cInfo['aggr']) && strlen($v))
		{
			// get bool value "yes/no"
			$fValues = array_flip($field->GetValues());
			$fValue = (bool) $fValues[$v];

			$mess = 'REPORT_BOOLEAN_VALUE_' . ($fValue ? 'TRUE' : 'FALSE');
			$v = htmlspecialcharsbx(GetMessage($mess));
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
						$v = round($v / $total[$k] * 100);
					}
					else
					{
						$v = '--';
					}
				}
				else
				{
					$v = round($v);
				}

				$total[$k] = $v . '%';
			}
			elseif (substr($k, -6) == '_PRCNT' && !strlen($cInfo['prcnt']))
			{
				$total[$k] = round($v). '%';
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

