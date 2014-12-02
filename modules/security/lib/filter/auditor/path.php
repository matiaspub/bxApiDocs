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
 * Path security auditor
 * Searching "path traversal" like strings, for example: /foo/../bar/
 *
 * @package Bitrix\Security\Filter\Auditor
 * @since 14.0.0
 */
class Path
	extends Base
{
	protected $name = 'PHP';

	protected function getFilters()
	{
		$filters = array();
		$filters['#([\\\/]\.)(\.[\\\/])#is'] = $this->getSplittingString(2);
		if(
			(!defined('PHP_OS'))
			|| (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		)
		{
			$slashes = '\\\/\x20\x22\x3c\x3e\x5c';
		}
		else
		{
			$slashes = '\\\/';
		}

		$filters['#(?:\.['.$slashes.']+){30,}#'] = ' X ';

		$result = array(
			'search' => array_keys($filters),
			'replace' => $filters
			);
		return $result;
	}

}
