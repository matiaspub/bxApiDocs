<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Length extends Base
{
	/** @var integer */
	protected $min;

	/** @var integer */
	protected $max;

	/** @var string */
	protected $errorPhraseMinCode = 'MAIN_ENTITY_VALIDATOR_LENGTH_MIN';
	protected $errorPhraseMin;

	/** @var string */
	protected $errorPhraseMaxCode = 'MAIN_ENTITY_VALIDATOR_LENGTH_MAX';
	protected $errorPhraseMax;

	/**
	 * @param int|null  $min
	 * @param int|null  $max
	 * @param array $errorPhrase
	 *
	 * @throws ArgumentTypeException
	 */
	public function __construct($min = 1, $max = null, $errorPhrase = array('MIN' => null, 'MAX' => null))
	{
		if ($min !== null)
		{
			if (!is_int($min))
			{
				throw new ArgumentTypeException('min', 'integer');
			}

			$this->min = $min;
		}

		if ($max !== null)
		{
			if (!is_int($max))
			{
				throw new ArgumentTypeException('max', 'integer');
			}

			$this->max = $max;
		}

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

	/**
	 * Checks minimum and/or maximum length (as string) of the value.
	 * Returns true if check was successful or string with error text otherwise.
	 *
	 * @param mixed $value Value to check.
	 * @param array $primary Has no use in this function.
	 * @param array $row  Has no use in this function.
	 * @param Entity\Field $field Field metadata.
	 * @return boolean|string
	 */
	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		if ($this->min !== null)
		{
			if (strlen($value) < $this->min)
			{
				$mess = ($this->errorPhraseMin !== null? $this->errorPhraseMin : Loc::getMessage($this->errorPhraseMinCode));
				return $this->getErrorMessage($value, $field, $mess, array("#MIN_LENGTH#" => $this->min));
			}
		}

		if ($this->max !== null)
		{
			if (strlen($value) > $this->max)
			{
				$mess = ($this->errorPhraseMax !== null? $this->errorPhraseMax : Loc::getMessage($this->errorPhraseMaxCode));
				return $this->getErrorMessage($value, $field, $mess, array("#MAX_LENGTH#" => $this->max));
			}
		}

		return true;
	}

	/**
	 * Returns minimum allowed length.
	 * null if not set.
	 *
	 * @return integer|null
	 */
	public function getMin()
	{
		return $this->min;
	}

	/**
	 * Returns maximum allowed length.
	 * null if not set.
	 *
	 * @return integer|null
	 */
	public function getMax()
	{
		return $this->max;
	}
}
