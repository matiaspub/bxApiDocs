<?php
/**
 * Bitrix Security Module
 * @package Bitrix
 * @subpackage Security
 * @copyright 2001-2013 Bitrix
 * @since File available since 14.0.0
 */
namespace Bitrix\Security\Filter\Auditor;

/**
 * Sql security auditor
 * Searching SQLi like strings, for example: union select money,1,2,3 from big_guy;
 *
 * @package Bitrix\Security\Filter\Auditor
 * @since 14.0.0
 */
class Sql
	extends Base
{
	protected $name = 'SQL';

	protected function getFilters()
	{
		$sqlStart = '(?:(?<![a-z0-9_-])|\/\*M?!\d+?)\K';
		$sqlEnd = '(?![a-z_])';
		$sqlSpace = "(?:[\\x00-\\x20\(\)\'\"\`*@\+\-\.~\\\ed!\d{}]|(?:\\/\\*.*?\\*\\/)|(?:\\/\\*M?!\d*)|(?:\\*\\/)|(?:#[^\\n]*[\\n]+))+";
		$sqlExpEnd = "[\\x00-\\x20\(\)\'\"\`*@\+\-\.~\\\ed!\d{}\\/]";
		$sqlFunctionsSpace="[\\x00-\\x20]*";
		$sqlSplitTo2 = $this->getSplittingString(2);
		$sqlSplitTo3 = $this->getSplittingString(3);
		$sqlSplitTo4 = $this->getSplittingString(4);


		global $DBType;
		$filters = array(
			"/{$sqlStart}(uni)(on{$sqlSpace}.+{$sqlExpEnd}sel)(ect){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(uni)(on{$sqlSpace}sel)(ect){$sqlEnd}/is" => $sqlSplitTo3,

			"/{$sqlStart}(sel)(ect{$sqlSpace}.+{$sqlExpEnd}fr)(om){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(sel)(ect{$sqlSpace}fr)(om){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(fr)(om{$sqlSpace}.+{$sqlExpEnd}wh)(ere){$sqlEnd}/is" => $sqlSplitTo3,

			"/{$sqlStart}(alt)(er)({$sqlSpace})(database|table|function|procedure|server|event|view|index){$sqlEnd}/is" => $sqlSplitTo4,
			"/{$sqlStart}(cre)(ate)({$sqlSpace})(database|table|function|procedure|server|event|view|index){$sqlEnd}/is" => $sqlSplitTo4,
			"/{$sqlStart}(dr)(op)({$sqlSpace})(database|table|function|procedure|server|event|view|index){$sqlEnd}/is" => $sqlSplitTo4,

			"/{$sqlStart}(upd)(ate{$sqlSpace}.+{$sqlExpEnd}se)(t){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(ins)(ert{$sqlSpace}.+{$sqlExpEnd}val)(ue){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(ins)(ert{$sqlSpace}.+{$sqlExpEnd}se)(t){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(i)(nto{$sqlSpace}out)(file){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(i)(nto{$sqlSpace}dump)(file){$sqlEnd}/is" => $sqlSplitTo3,

			"/{$sqlStart}(ins)(ert{$sqlSpace}.+{$sqlSpace}sele)(ct){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(ins)(ert{$sqlSpace}in)(to){$sqlEnd}/is" => $sqlSplitTo3,
			"/{$sqlStart}(ins)(ert{$sqlSpace}.+{$sqlSpace}in)(to){$sqlEnd}/is" => $sqlSplitTo3,

			"/{$sqlStart}(load_)(file{$sqlFunctionsSpace}\()/is" => $sqlSplitTo2,

			"/{$sqlStart}(fr)(om{$sqlSpace}.+{$sqlExpEnd}lim)(it){$sqlEnd}/is" => $sqlSplitTo3,
		);

		$dbt = strtolower($DBType);
		if ($dbt === 'mssql')
		{
			$filters += array(
				"/({$sqlSpace}[sx]p)(_\w+{$sqlFunctionsSpace}[\(\[])/" => $sqlSplitTo2,
				"/(ex)(ec{$sqlFunctionsSpace}\()/is"=>$sqlSplitTo2,
				"/(ex)(ecute{$sqlFunctionsSpace}\()/is"=>$sqlSplitTo2,
				"/([\\x00-\\x20;]ex)(ec.+[sx]p)(_\w+)/is" => $sqlSplitTo3,
			);
		}
		elseif ($dbt === 'oracle')
		{
			$filters += array(
				"/(ex)(ecute{$sqlSpace}.+{$sqlExpEnd}imme)(diate)/is" => $sqlSplitTo3,
				"/(ex)(ecute{$sqlSpace}imme)(diate)/is" => $sqlSplitTo3,
			);
		}

		$result = array(
			'search' => array_keys($filters),
			'replace' => $filters
			);
		return $result;
	}
}
