<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class IblockTemplates extends BaseTemplate
{
	/**
	 * @param integer $iblock_id Identifier of the iblock.
	 */
	function __construct($iblock_id)
	{
		$entity = new IblockValues($iblock_id);
		parent::__construct($entity);
	}
}