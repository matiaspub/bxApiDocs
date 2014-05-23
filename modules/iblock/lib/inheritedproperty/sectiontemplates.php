<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class SectionTemplates extends BaseTemplate
{
	/**
	 * @param integer $iblock_id Identifier of the iblock of the section.
	 * @param integer $section_id Identifier of the section.
	 */
	function __construct($iblock_id, $section_id)
	{
		$entity = new SectionValues($iblock_id, $section_id);
		parent::__construct($entity);
	}
}