<?
class CPerfQueryJoin
{
	var $left_table = "";
	var $left_column = "";
	var $left_const = "";
	var $right_table = "";
	var $right_column = "";
	var $right_const = "";

	function _parse($sql, &$table, &$column, &$const)
	{
		if(preg_match("/^([a-zA-Z0-9_]+)\\.(.+)\$/", $sql, $match))
		{
			$table = $match[1];
			$column = $match[2];
			$const = "";
		}
		else
		{
			$table = "";
			$column = "";
			$const = $sql;
		}
	}

	public static function parse_left($sql)
	{
		$this->_parse($sql, $this->left_table, $this->left_column, $this->left_const);
	}

	public static function parse_right($sql)
	{
		$this->_parse($sql, $this->right_table, $this->right_column, $this->right_const);
	}
}

class CPerfQueryWhere
{
	var $table_aliases_regex = "";
	var $equation_regex = "";
	var $sql = "";
	var $simplified_sql = "";
	var $joins = array();

	function __construct($table_aliases_regex)
	{
		$this->table_aliases_regex = $table_aliases_regex;
		$this->equation_regex = "(?:".$this->table_aliases_regex."\\.[a-zA-Z0-9_]+|[0-9]+|'[^']*') (?:=|<|>|> =|< =|IS) (?:".$this->table_aliases_regex."\\.[a-zA-Z0-9_]+|[0-9]+|'[^']*'|NULL)";
	}

	public static function parse($sql)
	{
		//Transform and simplify sql
		//
		//Remove balanced braces around equals
		$sql = $this->_remove_braces(CPerfQuery::removeSpaces($sql));

		//Replace "expr1 = <const1> or expr1 = <const2> or expr1 = <const3> ..."
		//with "expr1 in (<const1>, ...)"
		$new_sql = preg_replace_callback("/\\( (".$this->equation_regex."(?: OR ".$this->equation_regex.")+) \\)/i", array($this, "_or2in"), CPerfQuery::removeSpaces($sql));
		if($new_sql !== null)
			$sql = CPerfQuery::removeSpaces($new_sql);

		//Replace IN with no more than 5 values to equal
		$sql = preg_replace("/ IN[ ]*\\([ ]*([0-9]+|'[^']*')([ ]*,[ ]*([0-9]+|'[^']*')[ ]*){0,5}[ ]*\\)/i", " = \\1 ", $sql);
//echo "<pre>_replace_in:",htmlspecialcharsbx($sql),"</pre>";

		//Remove complex inner syntax
		while(preg_match("/\\([^()]*\\)/", $sql))
			$sql = preg_replace("/\\([^()]*\\)/", "", $sql);

//echo "<pre>simplified_sql:",htmlspecialcharsbx($sql),"</pre>";
		$this->simplified_sql = $sql;

		foreach(preg_split("/ and /i", $sql) as $str)
		{
			if(preg_match("/(".$this->table_aliases_regex."\\.[a-zA-Z0-9_]+) = (".$this->table_aliases_regex."\\.[a-zA-Z0-9_]+)/", $str, $match))
			{
				$join = new CPerfQueryJoin;
				$join->parse_left($match[1]);
				$join->parse_right($match[2]);
				$this->joins[] = $join;
			}
			elseif(preg_match("/(".$this->table_aliases_regex."\\.[a-zA-Z0-9_]+) = ([0-9]+|'.+')/", $str, $match))
			{
				$join = new CPerfQueryJoin;
				$join->parse_left($match[1]);
				$join->parse_right($match[2]);
				$this->joins[] = $join;
			}
		}

		return !empty($this->joins);
	}

	//Remove balanced braces around equals
	function _remove_braces($sql)
	{
		while(true)
		{
			$new_sql = preg_replace("/\\([ ]*(".$this->equation_regex."(?: AND ".$this->equation_regex.")*)[ ]*\\)/i", "\\1", $sql);
			if($new_sql === null)
				break;

			if($new_sql === $sql)
			{
				$new_sql = preg_replace("/\\( \\( (".$this->equation_regex."(?: OR ".$this->equation_regex.")*) \\) \\)/i", "( \\1 )", trim($sql));
				if($new_sql === null)
					break;

				if($new_sql === $sql)
					break;
			}
//echo "<pre>_remove_braces:",htmlspecialcharsbx($new_sql),"</pre>";
			$sql = trim($new_sql);
		}
		return $sql;
	}

	function _or2in($or_match)
	{
		$sql = $or_match[0];
		if(preg_match_all("/(".$this->table_aliases_regex."\\.[a-zA-Z0-9_]+|[0-9]+|'[^']*') (?:=) ([0-9]+|'[^']*')/", $or_match[1], $match))
		{
			if(count(array_unique($match[1])) == 1)
				$sql = $match[1][0]." IN ( ".implode(", ", $match[2])." )";
		}
//echo "<pre>_or2in:",htmlspecialcharsbx($sql),"</pre>";
		return $sql;
	}
}

class CPerfQueryTable
{
	var $sql = "";
	var $name = "";
	var $alias = "";
	var $join = "";

	public static function parse($sql)
	{
		$sql = CPerfQuery::removeSpaces($sql);

		if(preg_match("/^([a-z0-9_]+) ([a-z0-9_]+) on (.+)\$/i", $sql, $match))
		{
			$this->name = $match[1];
			$this->alias = $match[2];
			$this->join = $match[3];
		}
		if(preg_match("/^([a-zA-Z0-9_]+) ([a-zA-Z0-9_]+)(\$| )/", $sql, $match))
		{
			$this->name = $match[1];
			$this->alias = $match[2];
		}
		elseif(preg_match("/^([a-zA-Z0-9_]+)\$/", $sql, $match))
		{
			$this->name = $match[1];
			$this->alias = $this->name;
		}
		else
		{
			return false;
		}

		$this->sql = $sql;
		return true;
	}
}

class CPerfQueryFrom
{
	var $sql = "";
	var $tables = array();
	var $joins = array();

	public static function parse($sql)
	{
		$sql = CPerfQuery::removeSpaces($sql);

		if(preg_match("/^select(.*) from (.*?) (where|group|having|order)/is", $sql, $match))
			$this->sql = $match[2];
		elseif(preg_match("/^select(.*) from (.*?)\$/is", $sql, $match))
			$this->sql = $match[2];
		else
			$this->sql = "";

		if($this->sql)
		{
			$arJoinTables = preg_split("/(,|inner\\s+join|left\\s+join)(?= [a-z0-9_]+)/is", $this->sql);
			foreach($arJoinTables as $str)
			{
				$table = new CPerfQueryTable;
				if($table->parse($str))
				{
					$this->tables[] = $table;
				}
			}

			if(count($this->tables) <= 0)
				return false;

			$tables_regex = "(?:".implode("|", $this->getTableAliases()).")";
			foreach($this->tables as $table)
			{
				$where = new CPerfQueryWhere($tables_regex);
				if($where->parse($table->join))
				{
					$this->joins = array_merge($this->joins, $where->joins);
				}
			}

		}

		return !empty($this->tables);
	}

	public static function getTableAliases()
	{
		$res = array();
		foreach($this->tables as $table)
			$res[] = $table->alias;
		return $res;
	}
}

class CPerfQuery
{
	var $sql = "";
	var $type = "unknown";
	var $subqueries  = array();
	var $from = null;
	var $where = null;

	public static function transform2select($sql)
	{
		if(preg_match("#^\\s*insert\\s+into\\s+(.+?)(\\(|)\\s*(\\s*select.*)\\s*\\2\\s*(\$|ON\\s+DUPLICATE\\s+KEY\\s+UPDATE)#is", $sql, $match))
			$result = $match[3];
		elseif(preg_match("#^\\s*DELETE\\s+#i", $sql))
			$result = preg_replace("#^\s*(DELETE.*?FROM)#is", "select * from", $sql);
		elseif(preg_match("#^\\s*SELECT\\s+#i", $sql))
			$result = $sql;
		else
			$result = "";

		return $result;
	}

	public static function removeSpaces($str)
	{
		return trim(preg_replace("/[ \t\n\r]+/", " ", $str), " \t\n\r");
	}

	public static function parse($sql)
	{
		$this->sql = preg_replace("/([()=])/", " \\1 ", $sql);
		$this->sql = CPerfQuery::removeSpaces($this->sql);

		//if(preg_match("/^(select|update|delete|insert) /i", $this->sql, $match))
		if(preg_match("/^(select) /i", $this->sql, $match))
			$this->type = strtolower($match[1]);
		else
			$this->type = "unknown";

		if($this->type == "select")
		{
			//0 TODO replace literals whith placeholders
			//1 remove subqueries from sql
			if(!$this->parse_subqueries())
				return false;
			//2 parse from
			$this->from = new CPerfQueryFrom;
			if(!$this->from->parse($this->sql))
				return false;

			$tables_regex = "(?:".implode("|", $this->from->getTableAliases()).")";
			$this->where = new CPerfQueryWhere($tables_regex);
			if(preg_match("/ where (.+?)(\$| group | having | order )/i", $this->sql, $match))
				$this->where->parse($match[1]);

			return true;
		}
		else
		{
			return false;
		}
	}

	public static function parse_subqueries()
	{
		$this->subqueries = array();

		$ar = preg_split("/(\\(\\s*select|\\(|\\))/is", $this->sql, -1, PREG_SPLIT_DELIM_CAPTURE);
		$subq = 0; $braces = 0;
		foreach($ar as $i => $str)
		{
			if($str == ")")
				$braces--;
			elseif(substr($str, 0, 1) == "(")
				$braces++;

			if($subq == 0)
			{
				if(preg_match("/^\\(\\s*select/is", $str))
				{
					$this->subqueries[] = substr($str, 1);
					$subq++;
					unset($ar[$i]);
				}
			}
			elseif($braces == 0)
			{
				$subq--;
				unset($ar[$i]);
			}
			else
			{
				$this->subqueries[count($this->subqueries)-1] .= $str;
				unset($ar[$i]);
			}
		}

		$this->sql = implode('', $ar);
		return true;
	}

	public static function table_joins($table_alias)
	{
		//Lookup table by its alias
		$suggest_table = null;
		foreach($this->from->tables as $table)
			if($table->alias === $table_alias)
				$suggest_table = $table;
		if(!isset($suggest_table))
			return array();

		$arTableJoins = array(
			"WHERE" => array()
		);
		//1 iteration gather inter tables joins
		foreach($this->from->joins as $join)
		{
			if($join->left_table === $table_alias && $join->right_table !== "")
			{
				if(!isset($arTableJoins[$join->right_table]))
					$arTableJoins[$join->right_table] = array();
				$arTableJoins[$join->right_table][] = $join->left_column;
			}
			elseif($join->right_table === $table_alias && $join->left_table !== "")
			{
				if(!isset($arTableJoins[$join->left_table]))
					$arTableJoins[$join->left_table] = array();
				$arTableJoins[$join->left_table][] = $join->right_column;
			}
		}
		//2 iteration gather inter tables joins from where
		foreach($this->where->joins as $join)
		{
			if($join->left_table === $table_alias && $join->right_table !== "")
			{
				if(!isset($arTableJoins[$join->right_table]))
					$arTableJoins[$join->right_table] = array();
				$arTableJoins[$join->right_table][] = $join->left_column;
			}
			elseif($join->right_table === $table_alias && $join->left_table !== "")
			{
				if(!isset($arTableJoins[$join->left_table]))
					$arTableJoins[$join->left_table] = array();
				$arTableJoins[$join->left_table][] = $join->right_column;
			}
		}
		//3 iteration add constant filters from joins
		foreach($this->from->joins as $join)
		{
			if($join->left_table === $table_alias && $join->right_table === "")
			{
				foreach($arTableJoins as $i => $arColumns)
					$arTableJoins[$i][] = $join->left_column;
			}
			elseif($join->right_table === $table_alias && $join->left_table === "")
			{
				foreach($arTableJoins as $i => $arColumns)
					$arTableJoins[$i][] = $join->right_column;
			}
		}
		//4 iteration add constant filters from where
		foreach($this->where->joins as $join)
		{
			if($join->left_table === $table_alias && $join->right_table === "")
			{
				foreach($arTableJoins as $i => $arColumns)
					$arTableJoins[$i][] = $join->left_column;
			}
			elseif($join->right_table === $table_alias && $join->left_table === "")
			{
				foreach($arTableJoins as $i => $arColumns)
					$arTableJoins[$i][] = $join->right_column;
			}
		}

		if(empty($arTableJoins["WHERE"]))
			unset($arTableJoins["WHERE"]);

		return $arTableJoins;
	}

	public static function suggest_index($table_alias)
	{
		global $DB;

		$suggest_table = null;
		foreach($this->from->tables as $table)
			if($table->alias === $table_alias)
				$suggest_table = $table;
		if(!isset($suggest_table))
			return false;

		$arTableJoins = $this->table_joins($table_alias);

		//Next read indexes already have
//echo "<pre>",htmlspecialcharsbx(print_r($arTableJoins,1)),"</pre>";
		$arSuggest = array();
		if(!empty($arTableJoins))
		{
			if(!$DB->TableExists($suggest_table->name))
				return false;

			$arIndexes = CPerfomanceTable::GetIndexes($suggest_table->name);
			foreach($arIndexes as $index_name => $arColumns)
				$arIndexes[$index_name] = implode(",", $arColumns);
//echo "<pre>",htmlspecialcharsbx(print_r($arIndexes,1)),"</pre>";
			//Test our suggestion against existing indexes
			foreach($arTableJoins as $i => $arColumns)
			{
				$index_found = "";
				$arColumns = $this->_adjust_columns($arColumns);
				//Take all possible combinations of columns
				$arCombosToTest = $this->array_power_set($arColumns);
//echo "<pre>",htmlspecialcharsbx(print_r($arCombosToTest,1)),"</pre>";
				foreach($arCombosToTest as $arComboColumns)
				{
					if(!empty($arComboColumns))
					{
						$index2test = implode(",", $arComboColumns);
						//Try to find out if index alredy exists
						foreach($arIndexes as $index_name => $index_columns)
						{
							if(substr($index_columns, 0, strlen($index2test)) === $index2test)
							{
								if(
									$index_found === ""
									|| count(explode(",", $index_found)) < count(explode(",", $index2test))
								)
									$index_found = $index2test;
							}
						}
					}
				}
				//
				if(!$index_found)
				{
					sort($arColumns);
					$arSuggest[] = $suggest_table->alias.":".$suggest_table->name.":".implode(",", $arColumns);
				}
			}
		}

		if(!empty($arSuggest))
		{
			return $arSuggest;
		}
		else
		{
			return false;
		}
	}

	public static function array_power_set($array)
	{
		$results = array(array());
		foreach($array as $element)
			foreach($results as $combination)
				array_push($results, array_merge(array($element), $combination));
		return $results;
	}

	function _adjust_columns($arColumns)
	{
		$arColumns = array_unique($arColumns);
		while(strlen(implode(",", $arColumns)) > 250)
		{
			//TODO: add brains here
			//1 exclude blobs and clobs
			//2 etc.
			array_pop($arColumns);
		}
		return $arColumns;
	}

	public static function has_where($table_alias = false)
	{
		if($table_alias === false)
			return !empty($this->where->joins);

		foreach($this->where->joins as $join)
		{
			if($join->left_table === $table_alias)
			{
				return true;
			}
			elseif($join->right_table === $table_alias)
			{
				return true;
			}
		}

		return false;
	}

	public static function find_value($table_name, $column_name)
	{
		//Lookup table by its name
		foreach($this->from->tables as $table)
		{
			if($table->name === $table_name)
			{
				$table_alias = $table->alias;

				foreach($this->where->joins as $join)
				{
					if(
						$join->left_table === $table_alias
						&& $join->left_column === $column_name
						&& $join->right_const !== ""
					)
					{
						return $join->right_const;
					}
					elseif(
						$join->right_table === $table_alias
						&& $join->right_column === $column_name
						&& $join->left_const !== ""
					)
					{
						return $join->left_const;
					}
				}

				foreach($this->from->joins as $join)
				{
					if(
						$join->left_table === $table_alias
						&& $join->left_column === $column_name
						&& $join->right_const !== ""
					)
					{
						return $join->right_const;
					}
					elseif(
						$join->right_table === $table_alias
						&& $join->right_column === $column_name
						&& $join->left_const !== ""
					)
					{
						return $join->left_const;
					}
				}
			}
		}

		return "";
	}

	public static function find_join($table_name, $column_name)
	{
		//Lookup table by its name
		$suggest_table = null;
		foreach($this->from->tables as $table)
			if($table->name === $table_name)
				$suggest_table = $table;

		if(!isset($suggest_table))
			return "";
		$table_alias = $suggest_table->alias;

		foreach($this->where->joins as $join)
		{
			if(
				$join->left_table === $table_alias
				&& $join->left_column === $column_name
				&& $join->right_table !== ""
			)
			{
				return $join->right_table.".".$join->right_column;
			}
			elseif(
				$join->right_table === $table_alias
				&& $join->right_column === $column_name
				&& $join->left_table !== ""
			)
			{
				return $join->left_table.".".$join->left_column;
			}
		}

		foreach($this->from->joins as $join)
		{
			if(
				$join->left_table === $table_alias
				&& $join->left_column === $column_name
				&& $join->right_table !== ""
			)
			{
				return $join->right_table.".".$join->right_column;
			}
			elseif(
				$join->right_table === $table_alias
				&& $join->right_column === $column_name
				&& $join->left_table !== ""
			)
			{
				return $join->left_table.".".$join->left_column;
			}
		}

		return "";
	}

	public static function remove_literals($sql)
	{
			return preg_replace('/(
				"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"                           # match double quoted string
				|
				\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'                       # match single quoted string
				|
				(?s:\\/\\*.*?\\*\\/)                                     # multiline comments
				|
				\\/\\/.*?\\n                                             # singleline comments
				|
				(?<![A-Za-z_])[0-9.]+(?![A-Za-z_])                       # an number
				|
				(?i:\\sIN\\s*\\(\\s*[0-9.]+(?:\\s*,\\s*[0-9.])*\\s*\\))  # in (1, 2, 3)
			)/x', '', $sql);
	}
}

?>