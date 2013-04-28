<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity;

interface INosqlPrimarySelector
{
	static public function getEntityByPrimary(\Bitrix\Main\Entity\Base $entity, $primary, $select);
}
