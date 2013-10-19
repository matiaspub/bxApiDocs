<?php

class CSecurityFilterPathAuditor extends CSecurityFilterBaseAuditor
{
	protected $name = "PHP";

	/**
	 * @return array
	 */
	protected function getFilters()
	{
		$filters = array();
		$filters["/(\\.)(\\.[\\\\\/])/is"] = $this->getSplittingString(2); //directory up, ../
		if(
			(!defined("PHP_OS"))
			|| (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		)
			$filters["/[\\.\\/\\\\\\x20\\x22\\x3c\\x3e\\x5c]{30,}/"] = " X ";
		else
			$filters["/[\\.\\/\\\\]{30,}/"] = " X ";

		$result = array(
			"search" => array_keys($filters),
			"replace" => array_values($filters)
			);
		return $result;
	}

}
