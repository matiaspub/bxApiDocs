<?php
/*
 * SQL Helper
 */
class CSqlHelper
{
	public static function GetCount($tableName, $tableAlias, &$arFields, &$arFilter)
	{
		$tableName = strval($tableName);
		if($tableName === '')
		{
			return false;
		}

		global $DB;
		$isOracle = strtoupper($DB->type) === 'ORACLE';

		$sql = $isOracle
			? "SELECT COUNT(1) AS QTY FROM {$tableName}"
			: "SELECT COUNT(*) AS QTY FROM {$tableName}";

		if(is_array($arFilter) && !empty($arFilter))
		{
			if(!is_array($arFields))
			{
				return false;
			}

			$arJoins = array();
			$condition = self::PrepareWhere($arFields, $arFilter, $arJoins);
			if($condition !== '')
			{
				$tableAlias = strval($tableAlias);
				if($tableAlias !== '')
				{
					//ORA-00933 overwise
					$sql .= $isOracle ? " {$tableAlias}" : " AS {$tableAlias}";
				}

				$sql .= " WHERE {$condition}";
			}
		}

		$dbResult = $DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		$arResult = $dbResult ? $dbResult->Fetch() : null;
		return $arResult !== null && isset($arResult['QTY']) ? intval($arResult['QTY']) : 0;
	}

	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="?")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		elseif (substr($key, 0, 1)=="=")
		{
			$key = substr($key, 1);
			$strOperation = "=";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	private static function AddToSelect(&$fieldKey, &$arField, &$arOrder, &$strSqlSelect)
	{
		global $DB;

		if (strlen($strSqlSelect) > 0)
			$strSqlSelect .= ", ";

		// ORACLE AND MSSQL require datetime/date field in select list if it present in order list
		if ($arField["TYPE"] == "datetime")
		{
			if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($fieldKey, $arOrder)))
				$strSqlSelect .= $arField["FIELD"]." as ".$fieldKey."_X1, ";

			$strSqlSelect .= $DB->DateToCharFunction($arField["FIELD"], "FULL")." as ".$fieldKey;
		}
		elseif ($arField["TYPE"] == "date")
		{
			if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($fieldKey, $arOrder)))
				$strSqlSelect .= $arField["FIELD"]." as ".$fieldKey."_X1, ";

			$strSqlSelect .= $DB->DateToCharFunction($arField["FIELD"], "SHORT")." as ".$fieldKey;
		}
		else
			$strSqlSelect .= $arField["FIELD"]." as ".$fieldKey;
	}

	private static function AddToFrom(&$arField, &$arJoined, &$strSqlFrom)
	{
		if (isset($arField["FROM"])
			&& strlen($arField["FROM"]) > 0
			&& !in_array($arField["FROM"], $arJoined))
		{
			if (strlen($strSqlFrom) > 0)
				$strSqlFrom .= " ";
			$strSqlFrom .= $arField["FROM"];
			$arJoined[] = $arField["FROM"];
		}
	}

	private static function PrepareDefaultFields(&$arFields, &$arOrder, &$arJoined, &$strSqlSelect, &$strSqlFrom)
	{
		$arFieldsKeys = array_keys($arFields);
		$qty = count($arFieldsKeys);
		for ($i = 0; $i < $qty; $i++)
		{
			if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
				&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
			{
				continue;
			}

			if (isset($arFields[$arFieldsKeys[$i]]["DEFAULT"])
				&& $arFields[$arFieldsKeys[$i]]["DEFAULT"] == "N")
			{
				continue;
			}

			self::AddToSelect($arFieldsKeys[$i], $arFields[$arFieldsKeys[$i]], $arOrder, $strSqlSelect);
			self::AddToFrom($arFields[$arFieldsKeys[$i]], $arJoined, $strSqlFrom);
		}
	}

	public static function PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $arOptions = array())
	{
		global $DB;

		$strSqlSelect = '';
		$strSqlFrom = '';
		$strSqlGroupBy = '';

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields) <= 0)
			{
				self::PrepareDefaultFields($arFields, $arOrder, $arAlreadyJoined, $strSqlSelect, $strSqlFrom);
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					if($val === '*')
					{
						self::PrepareDefaultFields($arFields, $arOrder, $arAlreadyJoined, $strSqlSelect, $strSqlFrom);
					}

					$val = strtoupper($val);
					$key = strtoupper($key);

					if (!array_key_exists($val, $arFields))
					{
						continue;
					}

					if (in_array($key, $arGroupByFunct))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
					}
					else
					{
						self::AddToSelect($val, $arFields[$val], $arOrder, $strSqlSelect);
					}
					self::AddToFrom($arFields[$val], $arAlreadyJoined, $strSqlFrom);
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arJoins = array();
		$strSqlWhere = self::PrepareWhere($arFields, $arFilter, $arJoins);

		foreach($arJoins as $join)
		{
			if($join !== '' && !in_array($join, $arAlreadyJoined))
			{
				if (strlen($strSqlFrom) > 0)
				{
					$strSqlFrom .= ' ';
				}

				$strSqlFrom .= $join;
				$arAlreadyJoined[] = $join;
			}
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = array();
		$dbType = strtoupper($DB->type);
		$nullsLast = is_array($arOptions) && isset($arOptions['NULLS_LAST']) ? (bool)$arOptions['NULLS_LAST'] : false;
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";

			if (array_key_exists($by, $arFields))
			{
				if(!$nullsLast)
				{
					if($dbType !== "ORACLE")
					{
						$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order;
					}
					else
					{
						if($order === 'ASC')
							$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order." NULLS FIRST";
						else
							$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order." NULLS LAST";
					}
				}
				else
				{
					if($dbType === "MYSQL")
					{
						if($order === 'ASC')
							$arSqlOrder[] = '(CASE WHEN ISNULL('.$arFields[$by]["FIELD"].') THEN 1 ELSE 0 END) '.$order.', '.$arFields[$by]["FIELD"]." ".$order;
						else
							$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order;
					}
					elseif($dbType === "MSSQL")
					{
						if($order === 'ASC')
							$arSqlOrder[] = '(CASE WHEN '.$arFields[$by]["FIELD"].' IS NULL THEN 1 ELSE 0 END) '.$order.', '.$arFields[$by]["FIELD"]." ".$order;
						else
							$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order;

					}
					elseif($dbType === "ORACLE")
					{
						if($order === 'DESC')
							$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order." NULLS LAST";
						else
							$arSqlOrder[] = $arFields[$by]["FIELD"]." ".$order;
					}
				}

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = '';
		DelDuplicateSort($arSqlOrder);
		$sqlOrderQty = count($arSqlOrder);
		for ($i = 0; $i < $sqlOrderQty; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}

	private static function PrepareWhere(&$arFields, &$arFilter, &$arJoins)
	{
		global $DB;
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$keyQty = count($filter_keys);
		for ($i = 0; $i < $keyQty; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);

			$key = $filter_keys[$i];

			if(strpos($key, '__INNER_FILTER') === 0)
			{
				$arSqlSearch[] = '('.self::PrepareWhere($arFields, $vals, $arJoins).')';
				continue;
			}

			$key_res = self::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];


			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();

				if (count($vals) > 0)
				{
					if ($strOperation == "IN")
					{
						if (isset($arFields[$key]["WHERE"]))
						{
							$arSqlSearch_tmp1 = call_user_func_array(
								$arFields[$key]["WHERE"],
								array($vals, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
							);
							if ($arSqlSearch_tmp1 !== false)
								$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
						else
						{
							if ($arFields[$key]["TYPE"] == "int")
							{
								array_walk($vals, create_function("&\$item", "\$item=IntVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." IN (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "double")
							{
								array_walk($vals, create_function("&\$item", "\$item=DoubleVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->ForSql(\$item).\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "datetime")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"FULL\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "date")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"SHORT\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
						}
					}
					else
					{
						$valQty = count($vals);
						for ($j = 0; $j < $valQty; $j++)
						{
							$val = $vals[$j];

							if (isset($arFields[$key]["WHERE"]))
							{
								$arSqlSearch_tmp1 = call_user_func_array(
									$arFields[$key]["WHERE"],
									array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
								);
								if ($arSqlSearch_tmp1 !== false)
									$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
							}
							else
							{
								$fieldType = $arFields[$key]["TYPE"];
								$fieldName = $arFields[$key]["FIELD"];
								if ($strOperation === "QUERY" && $fieldType !== "string" && $fieldType !== "char")
								{
									// Ignore QUERY operation for not character types - QUERY is supported only for character types.
									$strOperation = '=';
								}

								if ($strOperation === "LIKE" && ($fieldType === "int" || $fieldType === "double"))
								{
									// Ignore LIKE operation for numeric types.
									$strOperation = '=';
								}

								if ($fieldType === "int")
								{
									if ((intval($val) === 0) && (strpos($strOperation, "=") !== false))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
									{
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".intval($val)." )";
									}
								}
								elseif ($fieldType === "double")
								{
									$val = str_replace(",", ".", $val);

									if ((doubleval($val) === 0) && (strpos($strOperation, "=") !== false))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
								}
								elseif ($fieldType === "string" || $fieldType === "char")
								{
									if ($strOperation === "QUERY")
									{
										$arSqlSearch_tmp[] = GetFilterQuery($fieldName, $val, "Y");
									}
									else
									{
										if ((strlen($val) === 0) && (strpos($strOperation, "=") !== false))
											$arSqlSearch_tmp[] = "(".$fieldName." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($fieldName)." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$fieldName." ".$strOperation." '".$DB->ForSql($val)."' )";
										else
										{
											if($strOperation === "LIKE")
											{
												if(is_array($val))
													$arSqlSearch_tmp[] = "(".$fieldName." LIKE '%".implode("%' ESCAPE '!' OR ".$fieldName." LIKE '%", self::ForLike($val))."%' ESCAPE '!')";
												elseif(strlen($val)<=0)
													$arSqlSearch_tmp[] = $fieldName;
												else
													$arSqlSearch_tmp[] = $fieldName." LIKE '%".self::ForLike($val)."%' ESCAPE '!'";

											}
											else
												$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$fieldName." IS NULL OR NOT " : "")."(".$fieldName." ".$strOperation." '".$DB->ForSql($val)."' )";
										}
									}
								}
								elseif ($fieldType === "datetime")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
								}
								elseif ($fieldType === "date")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
								}
							}
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arJoins))
				{
					$arJoins[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				$sqlSearchQty = count($arSqlSearch_tmp);
				for ($j = 0; $j < $sqlSearchQty; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= self::AddBrackets($arSqlSearch_tmp[$j]);
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					}
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					}
				}

				if ($strSqlSearch_tmp != "")
				{
					$arSqlSearch[] = $strSqlSearch_tmp;
				}
			}
		}

		$logic = 'AND';
		if(isset($arFilter['LOGIC']) && $arFilter['LOGIC'] !== '')
		{
			$logic = strtoupper($arFilter['LOGIC']);
			if($logic !== 'AND' && $logic !== 'OR')
			{
				$logic = 'AND';
			}
		}

		$strSqlWhere = '';
		$logic = " $logic ";
		$sqlSearchQty = count($arSqlSearch);
		for ($i = 0; $i < $sqlSearchQty; $i++)
		{
			$searchItem = $arSqlSearch[$i];

			if($searchItem === '')
			{
				continue;
			}

			if ($strSqlWhere !== '')
				$strSqlWhere .= $logic;

			$strSqlWhere .= "($searchItem)";
		}

		return $strSqlWhere;
	}

	private static function AddBrackets($str)
	{
		return preg_match('/^\(.*\)$/s', $str) > 0 ? $str : "($str)";
	}

	public static function GetRowCount(&$arSql, $tableName, $tableAlias = '', $dbType = '')
	{
		global $DB;

		$tableName = strval($tableName);
		$tableAlias = strval($tableAlias);
		$dbType = strval($dbType);
		if(!isset($dbType[0]))
		{
			$dbType = 'MYSQL';
		}

		$dbType = strtoupper($dbType);

		$query = 'SELECT COUNT(\'x\') as CNT FROM '.$tableName;

		if($tableAlias !== '')
		{
			$query .= ' '.$tableAlias;
		}

		if (isset($arSql['FROM'][0]))
		{
			$query .= ' '.$arSql['FROM'];
		}

		if (isset($arSql['WHERE'][0]))
		{
			$query .= ' WHERE '.$arSql['WHERE'];
		}

		if (isset($arSql['GROUPBY'][0]))
		{
			$query .= ' GROUP BY '.$arSql['GROUPBY'];
		}

		$rs = $DB->Query($query, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		//MYSQL, MSSQL, ORACLE
		$result = 0;
		while($ary = $rs->Fetch())
		{
			$result += intval($ary['CNT']);
		}

		return $result;
	}

	public static function PrepareSelectTop(&$sql, $top, $dbType)
	{
		$dbType = strval($dbType);
		if(!isset($dbType[0]))
		{
			$dbType = 'MYSQL';
		}

		$dbType = strtoupper($dbType);

		if($dbType === 'MYSQL')
		{
			$sql .= ' LIMIT '.$top;
		}
		elseif($dbType === 'MSSQL')
		{
			if(substr($sql, 0, 7) === 'SELECT ')
			{
				$sql = 'SELECT TOP '.$top.substr($sql, 6);
			}
		}
		elseif($dbType === 'ORACLE')
		{
			$sql = 'SELECT * FROM ('.$sql.') WHERE ROWNUM <= '.$top;
		}
	}

	private static function ForLike($str)
	{
		global $DB;
		static $search  = array( "!",  "_",  "%");
		static $replace = array("!!", "!_", "!%");
		return str_replace($search, $replace, $DB->ForSQL($str));
	}
}
