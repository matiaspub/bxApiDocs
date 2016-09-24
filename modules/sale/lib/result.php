<?php
namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Main\Error;

class Result extends Entity\Result
{
	/** @var  int */
	protected $id;

	static public function __construct()
	{
		parent::__construct();
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Returns id of added record
	 * @return int
	 */
	
	/**
	* <p>Возвращает идентификатор созданной записи. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/result/getid.php
	* @author Bitrix
	*/
	public function getId()
	{
		return $this->id;
	}

	static public function __destruct()
	{
		//just quietly die in contrast Entity\Result either checked errors or not.
	}

	public function addData(array $data)
	{
		if (is_array($this->data))
		{
			$this->data = $this->data + $data;
		}
		else
		{
			$this->data = $data;
		}
	}

	/**
	 * @param Error[] $errors
	 *
	 * @return null
	 */
	public function addWarnings(array $errors)
	{
		/** @var Error $error */
		foreach ($errors as $error)
		{
			$this->addError(ResultWarning::create($error));
		}
	}

	/**
	 * @param Error[] $errors
	 *
	 * @return null
	 */
	public function addNotices(array $errors)
	{
		/** @var Error $error */
		foreach ($errors as $error)
		{
			$this->addError(ResultNotice::create($error));
		}
	}

}

class ResultError
	extends Entity\EntityError
{
	/**
	 * @param Error $error
	 *
	 * @return static
	 */
	public static function create(Error $error)
	{
		return new static($error->getMessage(), $error->getCode());
	}
}

class ResultWarning
		extends ResultError
{

}

class ResultNotice
		extends ResultError
{

}
