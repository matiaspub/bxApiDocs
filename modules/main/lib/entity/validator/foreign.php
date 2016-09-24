<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2014 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Checks if field value exists in referenced entity
 *
 * example: new Foreign(GroupTable::getEntity()->getField('ID'));
 *
 * @package Bitrix\Main\Entity\Validator
 */
class Foreign extends Base
{
	protected $errorPhraseCode = 'MAIN_ENTITY_VALIDATOR_FOREIGN';
	protected $reference = null;
	protected $filter = null;
	const NOT_EXISTS = 'BX_FOREIGN_NOT_EXISTS';

	/**
	 * @param Entity\Field $reference
	 * @param array $filter
	 */
	public function __construct(Entity\Field $reference, array $filter = array())
	{
		$this->reference = $reference;
		$this->filter = $filter;
		parent::__construct();
	}

	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		$query = new Entity\Query($this->reference->getEntity());
		$query->setFilter(array('='.$this->reference->getName() => $value) + $this->filter);
		$query->setLimit(1);
		$result = $query->exec();

		if($result->fetch())
		{
			return true;
		}
		return 	new Entity\FieldError($field, $this->getErrorMessage($value, $field), self::NOT_EXISTS);
	}
}
