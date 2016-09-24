<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Range extends Base
{
	/** @var integer */
	protected $min;

	/** @var integer */
	protected $max;

	/** @var boolean */
	protected $equality;

	/** @var string */
	protected $errorPhraseMinCode = 'MAIN_ENTITY_VALIDATOR_RANGE_MIN';
	protected $errorPhraseMin;

	/** @var string */
	protected $errorPhraseMaxCode = 'MAIN_ENTITY_VALIDATOR_RANGE_MAX';
	protected $errorPhraseMax;

	/**
	 * @param int   $min
	 * @param null  $max
	 * @param bool  $equality  Check "or equal" to the edges of the range
	 * @param array $errorPhrase
	 *
	 * @throws ArgumentTypeException
	 */
	public function __construct($min = 0, $max = null, $equality = false, $errorPhrase = array('MIN' => null, 'MAX' => null))
	{
		if ($min !== null)
		{
			if (!is_numeric($min))
			{
				throw new ArgumentTypeException('min', 'numeric');
			}

			$this->min = $min;
		}

		if ($max !== null)
		{
			if (!is_numeric($max))
			{
				throw new ArgumentTypeException('max', 'numeric');
			}

			$this->max = $max;
		}

		$this->equality = (bool) $equality;

		if (!empty($errorPhrase['MIN']))
		{
			$this->errorPhraseMin = $errorPhrase['MIN'];
		}

		if (!empty($errorPhrase['MAX']))
		{
			$this->errorPhraseMax = $errorPhrase['MAX'];
		}

		parent::__construct();
	}


	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		if ($this->min !== null)
		{
			if ((!$this->equality && $value < $this->min) || ($this->equality && $value <= $this->min))
			{
				$mess = ($this->errorPhraseMin !== null? $this->errorPhraseMin : Loc::getMessage($this->errorPhraseMinCode));
				return $this->getErrorMessage($value, $field, $mess, array("#MIN#" => $this->min));
			}
		}

		if ($this->max !== null)
		{
			if ((!$this->equality && $value > $this->max) || ($this->equality && $value >= $this->max))
			{
				$mess = ($this->errorPhraseMax !== null? $this->errorPhraseMax : Loc::getMessage($this->errorPhraseMaxCode));
				return $this->getErrorMessage($value, $field, $mess, array("#MAX#" => $this->max));
			}
		}

		return true;
	}
}