<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

use Bitrix\Iblock\Template\Entity\Element;

class ElementTemplates extends BaseTemplate
{
	/**
	 * @param integer $iblock_id Identifier of the iblock of element.
	 * @param integer $element_id Identifier of the element.
	 */
	function __construct($iblock_id, $element_id)
	{
		$entity = new ElementValues($iblock_id, $element_id);
		parent::__construct($entity);
	}
}