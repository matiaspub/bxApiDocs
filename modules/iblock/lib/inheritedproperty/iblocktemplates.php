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
	 * @param integer $iblockId Identifier of the iblock.
	 */
	public static function __construct($iblockId)
	{
		$entity = new IblockValues($iblockId);
		parent::__construct($entity);
	}
}