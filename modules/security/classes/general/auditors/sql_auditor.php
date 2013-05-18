<?php

class CSecurityFilterSqlAuditor extends CSecurityFilterBaseAuditor
{
	protected $name = "SQL";

	function __construct($pChar = "")
	{
		$this->setSplittingChar($pChar);
	}

	/**
	 * @return array
	 */
	protected function getFilters()
	{
		$sql_space = "(?:[\\x00-\\x20\(\)\'\"\`*@\+\-\.~\\\efd!\d]|(?:\\/\\*.*?\\*\\/)|(?:\\/\\*!\d*)|(?:\\*\\/))+";
		$sql_functions_space="[\\x00-\\x20]*";
		$sql_split_to_2 = $this->getSplittingString(2);
		$sql_split_to_3 = $this->getSplittingString(3);
		$sql_split_to_4 = $this->getSplittingString(4);


		global $DBType;
		$filters = array(
			"/(uni)(on{$sql_space}.+{$sql_space}sel)(ect)/is" => $sql_split_to_3,
			"/(uni)(on{$sql_space}sel)(ect)/is" => $sql_split_to_3,

			"/(sel)(ect{$sql_space}.+{$sql_space}fr)(om)/is" => $sql_split_to_3,
			"/(sel)(ect{$sql_space}fr)(om)/is" => $sql_split_to_3,
			"/(fr)(om{$sql_space}.+{$sql_space}wh)(ere)/is" => $sql_split_to_3,

			"/(alt)(er)({$sql_space})(database|table|function|procedure|server|event|view|index)/is" => $sql_split_to_4,
			"/(cre)(ate)({$sql_space})(database|table|function|procedure|server|event|view|index)/is" => $sql_split_to_4,
			"/(dr)(op)({$sql_space})(database|table|function|procedure|server|event|view|index)/is" => $sql_split_to_4,

			"/(upd)(ate{$sql_space}.+{$sql_space}se)(t)/is" => $sql_split_to_3,
			"/(ins)(ert{$sql_space}.+{$sql_space}val)(ue)/is" => $sql_split_to_3,
			"/(ins)(ert{$sql_space}.+{$sql_space}se)(t)/is" => $sql_split_to_3,
			"/(i)(nto{$sql_space}out)(file)/is" => $sql_split_to_3,
			"/(i)(nto{$sql_space}dump)(file)/is" => $sql_split_to_3,

			"/(ins)(ert{$sql_space}.+{$sql_space}sele)(ct)/is" => $sql_split_to_3,
			"/(ins)(ert{$sql_space}in)(to)/is" => $sql_split_to_3,
			"/(ins)(ert{$sql_space}.+{$sql_space}in)(to)/is" => $sql_split_to_3,

			"/(load_)(file{$sql_functions_space}\()/is" => $sql_split_to_2,

			"/(fr)(om{$sql_space}.+{$sql_space}lim)(it)/is" => $sql_split_to_3,
		);

		$dbt = strtolower($DBType);
		if($dbt === 'mssql')
		{
			$filters += array(
				"/({$sql_space}[sx]p)(_\w+{$sql_functions_space}[\(\[])/" => $sql_split_to_2,
				"/(ex)(ec{$sql_functions_space}\()/is"=>$sql_split_to_2,
				"/(ex)(ecute{$sql_functions_space}\()/is"=>$sql_split_to_2,
				"/([\\x00-\\x20;]ex)(ec.+[sx]p)(_\w+)/is" => $sql_split_to_3,
			);
		}
		elseif($dbt === 'oracle')
		{
			$filters += array(
				"/(ex)(ecute{$sql_space}.+{$sql_space}imme)(diate)/is" => $sql_split_to_3,
				"/(ex)(ecute{$sql_space}imme)(diate)/is" => $sql_split_to_3,
			);
		}

		$result = array(
			"search" => array_keys($filters),
			"replace" => array_values($filters)
			);
		return $result;
	}
}
