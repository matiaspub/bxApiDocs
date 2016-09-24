<?php

use Bitrix\Main\Entity;

class CReport
{
	public static $iBlockCompareVariations = array(
		'EQUAL' => '=',
		'GREATER_OR_EQUAL' => '>=',
		'GREATER' => '>',
		'LESS' => '<',
		'LESS_OR_EQUAL' => '<=',
		'NOT_EQUAL' => '!',
		'START_WITH' => '>%',
		'CONTAINS' => '%',
		'NOT_CONTAINS' => '!%'
	);

	public static function Add($settings)
	{
		global $DB, $USER;

		$name = $settings['title'];
		$description = $settings['description'];
		$owner = $settings['owner'];
		unset($settings['title']);
		unset($settings['description']);
		unset($settings['owner']);


		$fields = array(
			'TITLE' => $name,
			'DESCRIPTION' => $description,
			'OWNER_ID' => $owner,
			'CREATED_DATE' => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()+CTimeZone::GetOffset()),
			'CREATED_BY' => $USER->GetID()
		);

		if (isset($settings['mark_default']))
		{
			$fields['MARK_DEFAULT'] = $settings['mark_default'];
			unset($settings['mark_default']);
		}

		$fields['SETTINGS'] = serialize($settings);

		// pre-events
		foreach (GetModuleEvents("report", "OnBeforeReportAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$fields)) === false)
			{
				return false;
			}
		}

		// save data
		$ID = $DB->Add("b_report", $fields, array("SETTINGS", "DESCRIPTION"), "report");

		// clear view params
		self::clearViewParams($ID);

		// post-events
		foreach (GetModuleEvents("report", "OnReportAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, &$fields));
		}

		return $ID;
	}

	public static function Update($ID, $settings)
	{
		global $DB;

		$name = $settings['title'];
		$description = $settings['description'];
		unset($settings['title']);
		unset($settings['description']);
		unset($settings['owner']);

		$settings = serialize($settings);

		$fields = array(
			'TITLE' => $name,
			'DESCRIPTION' => $description,
			'SETTINGS' => $settings,
			'MARK_DEFAULT' => false
		);

		// pre-events
		foreach (GetModuleEvents("report", "OnBeforeReportUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$fields)) === false)
			{
				return false;
			}
		}

		// save data
		$strUpdate = $DB->PrepareUpdate("b_report", $fields, "report");
		$strSql = "UPDATE b_report SET ".$strUpdate." WHERE ID='".$DB->ForSQL($ID)."'";

		$result = $DB->QueryBind(
			$strSql, array('SETTINGS' => $settings, 'DESCRIPTION' => $description),false,
			"File: ".__FILE__."<br>Line: ".__LINE__
		);

		// post-events
		if ($result)
		{
			foreach (GetModuleEvents("report", "OnReportUpdate", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID, &$fields));
			}
		}

		// clear view params
		self::clearViewParams($ID);

		return $result;
	}

	public static function Delete($ID)
	{
		global $DB;

		$strSql = "DELETE FROM b_report WHERE ID = ".intval($ID);

		// pre-events
		foreach (GetModuleEvents("report", "OnBeforeReportDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
			{
				return false;
			}
		}

		// save data
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		// post-events
		foreach (GetModuleEvents("report", "OnReportDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		// clear view params
		self::clearViewParams($ID);

		return true;
	}

	public static function GetList($owner = '')
	{
		global $USER;

		return Bitrix\Report\ReportTable::getList(array(
			'select' => array('ID', 'TITLE', 'DESCRIPTION','CREATED_DATE'),
			'filter' => array('=CREATED_BY' => $USER->GetID(), '=OWNER_ID' => $owner)
		));
	}

	public static function setViewParams($id, $templateName, $strParams)
	{
		global $USER;

		$result = false;
		if (empty($templateName)) $templateName = '.default';
		if (
			get_class($USER) === 'CUser'
			&& $id !== null && intval($id) >= 0
			&& !empty($templateName)
			&& !empty($strParams)
		)
		{
			$user_id = $USER->GetId();
			if ($user_id != null)
			{
				$result = CUserOptions::SetOption(
					'report', 'view_params_'.$id.'_'.$templateName, $_SERVER['QUERY_STRING'], false, $user_id
				);
			}
		}

		return $result;
	}

	public static function getViewParams($id, $templateName)
	{
		global $USER;

		$result = '';
		if (empty($templateName)) $templateName = '.default';
		if (get_class($USER) === 'CUser' && $id !== null && intval($id) >= 0 && !empty($templateName))
		{
			$user_id = $USER->GetId();
			if ($user_id != null)
			{
				$result = CUserOptions::GetOption(
					'report', 'view_params_'.$id.'_'.$templateName, false, $user_id
				);
			}

		}

		return $result;
	}

	public static function clearViewParams($id)
	{
		global $USER;

		if (get_class($USER) === 'CUser' && $id !== null && intval($id) >= 0)
		{
			$user_id = $USER->GetId();
			if ($user_id != null)
			{
				$dbRes = CUserOptions::GetList(
					array("ID" => "ASC"),
					array('USER_ID' => $user_id, 'CATEGORY' => 'report', 'NAME_MASK' => 'view_params_'.$id.'_')
				);
				if (is_object($dbRes))
				{
					while ($row = $dbRes->fetch())
					{
						if (strpos($row['NAME'], 'view_params_'.$id.'_') === 0)
							CUserOptions::DeleteOption('report', $row['NAME'], false, $user_id);
					}
				}
			}
		}
	}


	public static function GetCountInt($owner = '')
	{
		global $DB, $USER;

		$strSql = "SELECT COUNT(ID) AS CNT FROM b_report WHERE CREATED_BY='".$DB->ForSql($USER->GetID())."' AND OWNER_ID='".$DB->ForSql($owner)."'";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$row = $res->Fetch();

		return (int) $row['CNT'];
	}

	public static function generateChains($strChains, $initEntity, $initKey)
	{
		$chains = array();

		foreach ($strChains as $k => $v)
		{
			if (is_array($v))
			{
				// catalog here
				$key = empty($initKey) ? $k : $initKey . '.' .$k;

				try
				{
					$chain = Entity\QueryChain::getChainByDefinition($initEntity, $key);
					$lastElem = $chain->getLastElement();

					if ($lastElem->getValue() instanceof Entity\ReferenceField || is_array($lastElem->getValue()))
					{
						// reference to another entity
						$v = self::generateChains($v, $initEntity, $key);
						$v['__CHAIN__'] = $chain;
					}
					else
					{
						throw new \Bitrix\Main\SystemException('', 100);
					}
				}
				catch (Exception $e)
				{
					// try to recognize virtual category
					if ($e->getCode() == 100)
					{
						// `getChainByDefinition` field not found, there is  virtual category
						$v = self::generateChains($v, $initEntity, $initKey);
					}
					else
					{
						throw $e;
					}
				}
			}
			else
			{
				// normal field
				// collect chain path FLD1.FLD2.FLD3
				$key = empty($initKey) ? '' : $initKey . '.';

				$key = $key.$v;
				$v = Entity\QueryChain::getChainByDefinition($initEntity, $key);
			}

			// replace key
			$chains[$key] = $v;
		}

		return $chains;
	}


	public static function generateColumnTree($chains, $initEntity, $helper_class, $level = 0)
	{
		$tree = array();

		foreach ($chains as $k => $v)
		{
			if ($k == '__CHAIN__')
			{
				continue;
			}

			if ($v instanceof Entity\QueryChain)
			{
				// there is single element (chain)
				$chain = $v;
				$treeElem = $chain->getLastElement()->getValue();
				$branch = null;
			}
			else
			{
				// there is sub tree
				if (!empty($v['__CHAIN__']))
				{
					// real sub-tree
					$chain = $v['__CHAIN__'];
					$treeElem = $v['__CHAIN__']->getLastElement()->getValue();

					if (is_array($treeElem))
					{
						$treeElem = $treeElem[1];
					}
				}
				else
				{
					// virtual category
					$chain = null;
					$treeElem = null;
				}

				$branch = self::generateColumnTree($v, $initEntity, $helper_class, $level+1);
			}

			$tree[] = array(
				'fieldName' => $k,
				'humanTitle' => '', // reserved
				'fullHumanTitle' => '', // reserved
				'field' => $treeElem,
				'chain' => $chain,
				'branch' => $branch
			);
		}

		if ($level == 0)
		{
			self::attachLangToColumnTree($tree, $initEntity, $helper_class);
		}

		return $tree;
	}

	protected static function attachLangToColumnTree(&$tree, $initEntity, $helper_class, $preTitle = array())
	{
		$arUFInfo = call_user_func(array($helper_class, 'getUFInfo'));

		foreach($tree as &$treeElem)
		{
			$ownerId = call_user_func(array($helper_class, 'getOwnerId'));

			$humanTitle = '';

			if (!empty($treeElem['field']))
			{
				// detect UF
				$arUF = call_user_func(array($helper_class, 'detectUserField'), $treeElem['field']);
				if ($arUF['isUF'])
				{
					$treeElem['isUF'] = true;
					$treeElem['ufInfo'] = $arUF['ufInfo'];
					$humanTitle = $arUF['ufInfo']['LIST_COLUMN_LABEL'];
				}
				unset($arUF);

				// first: report-defined lang
				$rElementTitle = 'REPORT_'.$ownerId.'_'.$treeElem['fieldName'];

				// second: entity-defined lang
				$eElementTitle = $treeElem['field']->getLangCode();

				//$elementTitle = HasMessage($rElementTitle) ? $rElementTitle : $eElementTitle;
				$elementMessage = GetMessage($rElementTitle);
				$elementTitle = (!empty($elementMessage)) ? $rElementTitle : $eElementTitle;
			}
			else
			{
				// virtual field - subtree head
				$elementName = $treeElem['fieldName'];
				$elementTitle = 'REPORT_'.$ownerId.'_COLUMN_TREE_'.$elementName;
			}

			if (!isset($treeElem['isUF']) || !$treeElem['isUF'])
			{
				// PRCNT hack should not be here
				if (substr($elementTitle, -12) == '_PRCNT_FIELD')
				{
					$humanTitle = GetMessage(substr($elementTitle, 0, -12).'_FIELD');
				}
				else
				{
					$humanTitle = GetMessage($elementTitle);
				}
			}

			if (empty($humanTitle))
			{
				$humanTitle = $treeElem['fieldName'];
			}

			if (substr($elementTitle, -12) == '_PRCNT_FIELD')
			{
				$humanTitle .= ' (%)';
			}

			if (empty($treeElem['branch']))
			{
				$fullHumanTitle = $humanTitle;

				if (!empty($preTitle))
				{
					$fullHumanTitle = join(': ', $preTitle) . ': ' . $fullHumanTitle;
				}

				$treeElem['humanTitle'] = $humanTitle;
				$treeElem['fullHumanTitle'] = $fullHumanTitle;
			}
			else
			{
				$treeElem['humanTitle'] = $humanTitle;
				$treeElem['fullHumanTitle'] = $humanTitle;

				$sendPreTitle = array($humanTitle);

				self::attachLangToColumnTree($treeElem['branch'], $initEntity, $helper_class, $sendPreTitle);
			}
		}
	}

	/** @deprecated 14.5.5 Method moved into a helper class */
	public static function fillFilterReferenceColumns(&$filters, &$fieldList, $helperClass)
	{
		foreach ($filters as &$filter)
		{
			foreach ($filter as &$fElem)
			{
				if (is_array($fElem) && $fElem['type'] == 'field')
				{
					$field = $fieldList[$fElem['name']];

					if ($field instanceof Entity\ReferenceField)
					{
						call_user_func_array(
							array($helperClass, 'fillFilterReferenceColumn'),
							array(&$fElem, &$field)
						);
					}
				}
			}
		}
	}

	/**
	 * @param                     $elem
	 * @param                     $select
	 * @param                     $is_init_entity_aggregated
	 * @param                     $fList
	 * @param Entity\QueryChain[] $fChainList
	 * @param                     $helper_class
	 * @param Entity\Base         $entity
	 *
	 * @return array
	 */
	public static function prepareSelectViewElement($elem, $select, $is_init_entity_aggregated, $fList, $fChainList, $helper_class, Entity\Base $entity)
	{
		$result = null;
		$alias = null;

		if (empty($elem['aggr']) && !strlen($elem['prcnt']))
		{
			$result = $elem['name'];
		}
		else
		{
			$expression = '';

			/** @var Entity\Field $field */
			$field = $fList[$elem['name']];
			$chain = $fChainList[$elem['name']];
			$alias = $chain->getAlias();

			$dataType = call_user_func(array($helper_class, 'getFieldDataType'), $field);

			if (!empty($elem['aggr']))
			{
				$alias = $elem['aggr'] . '_' . $alias;

				if ($dataType == 'boolean')
				{
					// sum int for boolean
					global $DB;

					/** @var Entity\BooleanField $field */
					$trueValue = $field->normalizeValue(true);
					$localDef = 'CASE WHEN %s = \''.$DB->ForSql($trueValue).'\' THEN 1 ELSE 0 END';
				}
				else
				{
					$localDef = '%s';
				}

				if ($elem['aggr'] == 'COUNT_DISTINCT')
				{
					$dataType = 'integer';
					$expression = array(
						'COUNT(DISTINCT '.$localDef.')', $elem['name']
					);
				}
				else
				{
					if ($dataType == 'boolean')
					{
						$dataType = 'integer';
					}

					if ($elem['aggr'] == 'GROUP_CONCAT')
					{
						$expression = array(
							$localDef, $elem['name']
						);
					}
					else
					{
						$expression = array(
							$elem['aggr'].'('.$localDef.')', $elem['name']
						);
					}
				}

				// pack 1:N aggregations into subquery
				if ($chain->hasBackReference() && $elem['aggr'] != 'GROUP_CONCAT')
				{
					$confirm = call_user_func_array(
						array($helper_class, 'confirmSelectBackReferenceRewrite'),
						array(&$elem, $chain)
					);

					if ($confirm)
					{
						$filter = array();
						foreach ($entity->GetPrimaryArray() as $primary)
						{
							$filter['='.$primary] = new CSQLWhereExpression('?#', ToLower($entity->getCode()).'.'.$primary);
						}

						$query = new Entity\Query($entity);
						$query->addSelect(new Entity\ExpressionField('X', $expression[0], $elem['name']));
						$query->setFilter($filter);
						$query->setTableAliasPostfix('_sub');

						$expression = array('('.$query->getQuery().')');

						// double aggregation if init entity aggregated
						if ($is_init_entity_aggregated)
						{
							if ($elem['aggr'] == 'COUNT_DISTINCT')
							{
								$expression[0] = 'SUM('.$expression[0].')';
							}
							else
							{
								$expression[0] = $elem['aggr'].'('.$expression[0].')';
							}
						}
					} // confirmed
				}
			}

			if (strlen($elem['prcnt']))
			{
				$alias = $alias . '_PRCNT';
				$dataType = 'integer';

				if ($elem['prcnt'] == 'self_column')
				{
					if (empty($expression))
					{
						$expression = array('%s', $elem['name']);
					}
				}
				else
				{
					if (empty($expression))
					{
						$localDef = '%s';
						$localMembers = array($elem['name']);
					}
					else
					{
						$localDef = $expression[0];
						$localMembers = array_slice($expression, 1);
					}

					list($remoteAlias, $remoteSelect) = self::prepareSelectViewElement($select[$elem['prcnt']], $select, $is_init_entity_aggregated, $fList, $fChainList, $helper_class, $entity);

					if (is_array($remoteSelect) && !empty($remoteSelect['expression']))
					{
						// remote field is expression
						$remoteDef = $remoteSelect['expression'][0];
						$remoteMembers = array_slice($remoteSelect['expression'], 1);

						$alias = $alias . '_FROM_' . $remoteAlias;
					}
					else
					{
						// remote field is usual field
						$remoteDef = '%s';
						$remoteMembers = array($remoteSelect);

						$remoteAlias = Entity\QueryChain::getAliasByDefinition($entity, $remoteSelect);
						$alias = $alias . '_FROM_' . $remoteAlias;
					}

					$exprDef = '('.$localDef.') / ('.$remoteDef.') * 100';

					$expression = array_merge(array($exprDef), $localMembers, $remoteMembers);

					// 'ROUND(STATUS / ID * 100)'
					// 'ROUND( (EX1(F1, F2)) / (EX2(F3, F1)) * 100)',
					// F1, F2, F3, F1
				}
			}

			$result = array(
				'data_type' => $dataType,
				'expression' => $expression
			);
		}

		return array($alias, $result);
	}

	public static function getFullColumnTitle($view, $viewColumns, $fullHumanTitles)
	{
		$title = $fullHumanTitles[$view['fieldName']];

		if (!empty($view['aggr']))
		{
			$title .= ' ('.GetMessage('REPORT_SELECT_CALC_VAR_'.$view['aggr']).')';
		}

		if (strlen($view['prcnt']))
		{
			if ($view['prcnt'] == 'self_column')
			{
				$title .= ' (%)';
			}
			else
			{
				$byTitle = self::getFullColumnTitle($viewColumns[$view['prcnt']], $viewColumns, $fullHumanTitles);
				$title .= ' ('.GetMessage('REPORT_PRCNT_FROM_TITLE').' '.$byTitle.')';
			}
		}

		return $title;
	}

	public static function isColumnPercentable($view, $helperClassName)
	{
		/*
		1. any integer
		2. any float
		3. boolean with aggr
		4. any with COUNT_DISTINCT aggr
		*/

		$dataType = call_user_func(array($helperClassName, 'getFieldDataType'), $view['field']);

		/** @var Entity\Field[] $view */
		if (($dataType === 'integer' || $dataType === 'float')
			&& (!$view['isUF'] || $view['ufInfo']['MULTIPLE'] !== 'Y'))
		{
			return true;
		}
		elseif ($dataType === 'boolean' && $view['aggr'] === 'SUM'
			&& (!$view['isUF'] || $view['ufInfo']['MULTIPLE'] !== 'Y'))
		{
			return true;
		}
		elseif ($view['aggr'] === 'COUNT_DISTINCT')
		{
			return true;
		}

		return false;
	}

	public static function isColumnTotalCountable($view, $helperClassName)
	{
		/** @var Entity\Field[] $view */
		$dataType = call_user_func(array($helperClassName, 'getFieldDataType'), $view['field']);

		if (($dataType === 'integer' || $dataType === 'float')
			&& empty($view['aggr'])
			&& (!$view['isUF'] || $view['ufInfo']['MULTIPLE'] !== 'Y'))
		{
			return true;
		}
		elseif ($view['aggr'] == 'SUM' || $view['aggr'] == 'COUNT_DISTINCT')
		{
			return true;
		}

		return false;
	}

	public static function appendHrefSelectElements(&$elem, $fList, $entity, $helper_class, &$select, &$runtime)
	{
		// default href assign
		if (empty($elem['href']))
		{
			$href = call_user_func(array($helper_class, 'getDefaultElemHref'), $elem, $fList);

			if (!empty($href))
			{
				$elem['href'] = $href;
			}
		}

		// user defined or default href
		if (!empty($elem['href']))
		{
			$matches = array();
			preg_match_all('/#([a-zA-Z0-9_\.:\\\\]+)#/', $elem['href']['pattern'], $matches);

			if (!empty($matches[1]))
			{
				foreach ($matches[1] as $match)
				{
					// by default get definition from href
					$fieldDefinition = $match;
					$fieldAggr = null;

					// try to find extended info about href element
					if (!empty($elem['href']['elements'][$fieldDefinition]))
					{
						$fieldDefinition = $elem['href']['elements'][$match]['name'];

						if (!empty($elem['href']['elements'][$match]['aggr']))
						{
							$fieldAggr = $elem['href']['elements'][$match]['aggr'];
						}
						else
						{
							// normalize
							$elem['href']['elements'][$match]['aggr'] = null;
						}
					}
					else
					{
						// normalize
						$elem['href']['elements'][$fieldDefinition] = array(
							'name' => $fieldDefinition,
							'aggr' => null
						);
					}

					$fieldAlias = Entity\QueryChain::getAliasByDefinition($entity, $fieldDefinition);

					// add to select
					if (empty($fieldAggr) && !in_array($fieldDefinition, $select, true))
					{
						$select[$fieldAlias] = $fieldDefinition;
					}
					elseif (!empty($fieldAggr))
					{
						$fieldAlias = $fieldAggr.'_'.$fieldAlias;

						// add if not exists
						if (!array_key_exists($fieldAlias, $select))
						{
							// get field object
							$chain = Entity\QueryChain::getChainByDefinition($entity, $fieldDefinition);
							$field = $chain->getLastElement()->getValue();

							// add to select
							if ($fieldAggr == 'COUNT_DISTINCT')
							{
								$runtime[$fieldAlias] = array(
									'data_type' => 'integer',  // until we don't have group_concat
									'expression' => array(
										'COUNT(DISTINCT %s)', $fieldDefinition
									)
								);
							}
							else
							{
								$runtime[$fieldAlias] = array(
									'data_type' => call_user_func(array($helper_class, 'getFieldDataType') ,$field),
									'expression' => array(
										$fieldAggr.'(%s)', $fieldDefinition
									)
								);
							}

							$select[] = $fieldAlias;
						}
					}
				} // href pattern and elements saved
			}
		}
	}


	public static function generateValueUrl($elem, $dataRow, $entity)
	{
		// create url
		$urlParams = array();

		foreach ($elem['href']['elements'] as $hrefElem)
		{
			$alias = Entity\QueryChain::getAliasByDefinition($entity, $hrefElem['name']);

			if (!empty($hrefElem['aggr']))
			{
				$alias = $hrefElem['aggr'].'_'.$alias;
			}

			$urlParams[$hrefElem['name']] = $dataRow[$alias];
		}

		return CComponentEngine::MakePathFromTemplate($elem['href']['pattern'], $urlParams);
	}


	public static function rewriteUserShortName(&$select, &$runtime, $format, $entity, $grc =false)
	{
		foreach ($select as $k => $def)
		{
			if (
				(is_string($def) && (substr($def, -11) == '.SHORT_NAME' || $def === 'SHORT_NAME'))
				|| (is_array($def) && count($def['expression']) === 2 && substr($def['expression'][1], -11) == '.SHORT_NAME')
			)
			{
				$definition = is_string($def) ? $def : $def['expression'][1];
				$pre = substr($definition, 0, -11);
				$_alias = Entity\QueryChain::getAliasByDefinition($entity, $definition);

				$expression = self::getFormattedNameExpr($format, $pre);

				// show login if names is null
				global $DB;
				$nNameElements = count($expression) - 1;
				if ($nNameElements < 1)
				{
					$expression = array(
						$DB->IsNull('%s', '\' \''),
						(empty($pre) ? '' : $pre.'.').'LOGIN'
					);
				}
				else
				{
					$arConcatNameElements = array($DB->IsNull('%s', '\' \''));
					$n = $nNameElements;
					while (--$n > 0)
						$arConcatNameElements[] = $DB->IsNull('%s', '\' \'');
					$strConcatNameElements = call_user_func_array(array($DB, 'concat'), $arConcatNameElements);
					$expression[0] = 'CASE WHEN '.$DB->Length('LTRIM(RTRIM('.$strConcatNameElements.'))').'>0 THEN '.$expression[0].' ELSE %s END';
					for ($i = 1; $i <= $nNameElements; $i++)
						$expression[] = $expression[$i];
					$expression[] = (empty($pre) ? '' : $pre.'.').'LOGIN';
				}

				// modify select
				unset($select[$k]);

				if (is_string($def))
				{
					$runtime[$_alias] = array(
						'data_type' => 'string',
						'expression' => $expression
					);
				}
				else
				{
					// add aggr
					if (substr($def['expression'][0], 0, 14) == 'COUNT(DISTINCT')
					{
						$_alias = 'COUNT_DISTINCT_'.$_alias;
					}
					elseif ($grc)
					{
						$_alias = 'GROUP_CONCAT_'.$_alias;
					}

					$expression[0] = str_replace('%s', $expression[0], $def['expression'][0]);

					$runtime[$_alias] = array(
						'data_type' => 'integer',
						'expression' => $expression
					);
				}

				$select[] = $_alias;
			}
		}
	}

	public static function getUniqueFieldsByTree($tree)
	{
		$list = array();

		foreach ($tree as $treeElem)
		{
			$fieldDefinition = $treeElem['fieldName'];
			$field = $treeElem['field'];
			$branch = $treeElem['branch'];

			$list[$fieldDefinition] = $field;

			if (!empty($branch))
			{
				$list = array_merge($list, self::getUniqueFieldsByTree($branch));
			}
		}

		return $list;
	}

	public static function isValidFilterCompareVariation($fDefinition, $fType, $variation, $variations)
	{
		if (array_key_exists($fDefinition, $variations))
		{
			$vars = $variations[$fDefinition];
		}
		else
		{
			$vars = $variations[$fType];
		}

		return in_array($variation, $vars, true);
	}

	public static function addFreshDefaultReports($vReports, $ownerId)
	{
		foreach ($vReports as &$dReport)
		{
			$dReport['settings']['mark_default'] = $dReport['mark_default'];
			$dReport['settings']['title'] = $dReport['title'];
			$dReport['settings']['description'] = $dReport['description'];
			$dReport['settings']['owner'] = $ownerId;

			self::Add($dReport['settings']);
		}
		unset($dReport);
	}

	public static function sqlizeFilter($filter)
	{
		$newFilter = array();

		foreach ($filter as $fId => $filterInfo)
		{
			$iFilterItems = array();

			foreach ($filterInfo as $key => $subFilter)
			{
				// collect only fields and subfilters
				if ($key === 'LOGIC')
				{
					continue;
				}

				if ($subFilter['type'] == 'field')
				{

					$compare = self::$iBlockCompareVariations[$subFilter['compare']];
					$name = $subFilter['name'];
					$value = $subFilter['value'];
					if ($compare === '>%')
					{
						$compare = '';
						$value = $value.'%';
					}
					$iFilterItems[] = array(
						$compare.$name => $value
					);
				}
				else if ($subFilter['type'] == 'filter')
				{
					// hold link to another filter
					$iFilterItems[] = 'FILTER_'.$subFilter['name'];
				}
			}

			if (!empty($iFilterItems))
			{
				$iFilterItems['LOGIC'] = $filterInfo['LOGIC'];
				$newFilter[$fId] = $iFilterItems;
			}
		}

		return $newFilter;
	}

	public static function makeSingleFilter($filter)
	{
		$filter = self::sqlizeFilter($filter);

		// the 0s element should be in the end
		$filter = array_reverse($filter, true);

		foreach ($filter as &$filterInfo)
		{
			foreach ($filterInfo as $key => $subFilter)
			{
				if ($key !== 'LOGIC' && is_string($subFilter))
				{
					$sfId = substr($subFilter, 7);

					if (array_key_exists($sfId, $filter))
					{
						$filterInfo[$key] = &$filter[$sfId];
					}
				}
			}
		}

		return array_key_exists(0, $filter) ?  $filter[0] : array();
	}

	public static function collectFullHumanTitles($tree)
	{
		$fullHumanTitles = array();

		foreach ($tree as $treeElem)
		{
			//$fullHumanTitles[$treeElem['fieldName']] = $treeElem['fullHumanTitle'];
			$fullHumanTitle = $treeElem['fullHumanTitle'];
			if (substr($treeElem['fieldName'], -11) == '.SHORT_NAME')    // hack for ticket 0037576
			{
				$pos = strrpos($fullHumanTitle, ':');
				if ($pos !== false)
				{
					$fullHumanTitle = substr($fullHumanTitle, 0, $pos);
				}
			}
			$fullHumanTitles[$treeElem['fieldName']] = $fullHumanTitle;
			unset($fullHumanTitle);

			if (!empty($treeElem['branch']))
			{
				$fullHumanTitles = array_merge($fullHumanTitles, self::collectFullHumanTitles($treeElem['branch']));
			}
		}

		return $fullHumanTitles;
	}

	public static function getFormattedNameExpr($format, $defPrefix)
	{
		global $DB;

		$values = array(
			'#NAME#' => array($DB->IsNull('%s', '\' \''), 'NAME'),
			'#NAME_SHORT#' => array($DB->Concat("UPPER(".$DB->Substr($DB->IsNull('%s', '\' \''), 1, 1).")", "'.'"), 'NAME'),
			'#SECOND_NAME#' => array($DB->IsNull('%s', '\' \''), 'SECOND_NAME'),
			'#SECOND_NAME_SHORT#' => array($DB->Concat("UPPER(".$DB->Substr($DB->IsNull('%s', '\' \''), 1, 1).")", "'.'"), 'SECOND_NAME'),
			'#LAST_NAME#' => array($DB->IsNull('%s', '\' \''), 'LAST_NAME'),
			'#LAST_NAME_SHORT#' => array($DB->Concat("UPPER(".$DB->Substr($DB->IsNull('%s', '\' \''), 1, 1).")", "'.'"), 'LAST_NAME')
		);

		if (empty($format))
		{
			$format = '#LAST_NAME# #NAME_SHORT#';
		}

		$sql_fields = array(null);

		$matches = preg_split(
			'/('.join('|', array_keys($values)).')/',
			str_replace('%', '%%', $format),
			-1, PREG_SPLIT_DELIM_CAPTURE
		);

		$expression = array();

		foreach ($matches as $match)
		{
			if (array_key_exists($match, $values))
			{
				$expression[] = $values[$match][0];
				$sql_fields[] = (empty($defPrefix) ? '' : $defPrefix.'.').$values[$match][1];
			}
			elseif ($match !== '')
			{
				$expression[] = "'".$match."'";
			}
		}

		$expression = call_user_func_array(array($DB, 'Concat'), $expression);

		$sql_fields[0] = $expression;

		return $sql_fields;
	}

}

class BXUserException extends \Bitrix\Main\SystemException
{}
class BXFormException extends \Bitrix\Main\SystemException
{}
