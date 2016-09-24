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
	 * @param integer $iblockId Identifier of the iblock of element.
	 * @param integer $elementId Identifier of the element.
	 */
	public static function __construct($iblockId, $elementId)
	{
		$entity = new ElementValues($iblockId, $elementId);
		parent::__construct($entity);
	}
}