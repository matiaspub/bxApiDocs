<?php

class CSecurityFilterSqlAuditor extends CSecurityFilterBaseAuditor
{
	protected $name = "SQL";

	/**
	 * @return array
	 */
	protected function getFilters()
	{
		$sqlStart = "(?<![a-z0-9])";
		$sqlEnd = "(?![a-z0-9])";
		$sql_space = "(?:[\\x00-\\x20\(\)\'\"\`*@\+\-\.~\\\efd!\d]|(?:\\/\\*.*?\\*\\/)|(?:\\/\\*!\d*)|(?:\\*\\/)|(?:#.*[\\x00-\\x20]+))+";
		$sql_functions_space="[\\x00-\\x20]*";
		$sql_split_to_2 = $this->getSplittingString(2);
		$sql_split_to_3 = $this->getSplittingString(3);
		$sql_split_to_4 = $this->getSplittingString(4);


		global $DBType;
		$filters = array(
			"/{$sqlStart}(uni)(on{$sql_space}.+{$sql_space}sel)(ect){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(uni)(on{$sql_space}sel)(ect){$sqlEnd}/is" => $sql_split_to_3,

			"/{$sqlStart}(sel)(ect{$sql_space}.+{$sql_space}fr)(om){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(sel)(ect{$sql_space}fr)(om){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(fr)(om{$sql_space}.+{$sql_space}wh)(ere){$sqlEnd}/is" => $sql_split_to_3,

			"/{$sqlStart}(alt)(er)({$sql_space})(database|table|function|procedure|server|event|view|index){$sqlEnd}/is" => $sql_split_to_4,
			"/{$sqlStart}(cre)(ate)({$sql_space})(database|table|function|procedure|server|event|view|index){$sqlEnd}/is" => $sql_split_to_4,
			"/{$sqlStart}(dr)(op)({$sql_space})(database|table|function|procedure|server|event|view|index){$sqlEnd}/is" => $sql_split_to_4,

			"/{$sqlStart}(upd)(ate{$sql_space}.+{$sql_space}se)(t){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(ins)(ert{$sql_space}.+{$sql_space}val)(ue){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(ins)(ert{$sql_space}.+{$sql_space}se)(t){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(i)(nto{$sql_space}out)(file){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(i)(nto{$sql_space}dump)(file){$sqlEnd}/is" => $sql_split_to_3,

			"/{$sqlStart}(ins)(ert{$sql_space}.+{$sql_space}sele)(ct){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(ins)(ert{$sql_space}in)(to){$sqlEnd}/is" => $sql_split_to_3,
			"/{$sqlStart}(ins)(ert{$sql_space}.+{$sql_space}in)(to){$sqlEnd}/is" => $sql_split_to_3,

			"/{$sqlStart}(load_)(file{$sql_functions_space}\()/is" => $sql_split_to_2,

			"/{$sqlStart}(fr)(om{$sql_space}.+{$sql_space}lim)(it){$sqlEnd}/is" => $sql_split_to_3,
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
