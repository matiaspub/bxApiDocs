<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Unique extends Base
{
	/**
	 * @var string
	 */
	protected $errorPhraseCode = 'MAIN_ENTITY_VALIDATOR_UNIQUE';

	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		$entity = $field->getEntity();
		$primaryNames = $entity->getPrimaryArray();

		$query = new Entity\Query($entity);
		$query->setSelect($primaryNames);
		$query->setFilter(array('='.$field->getName() => $value));
		$query->setLimit(2);
		$result = $query->exec();

		while ($existing = $result->fetch())
		{
			// check primary
			foreach ($existing as $k => $v)
			{
				if (!isset($primary[$k]) || $primary[$k] != $existing[$k])
				{
					return $this->getErrorMessage($value, $field);
				}
			}
		}

		return true;
	}
}
