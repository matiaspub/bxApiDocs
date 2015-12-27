<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

class Payment
	extends Internals\CollectableEntity
{
	/** @var  PaySystemService */
	protected $paySystem;

	protected $isInner = null;

	private static $innerPaySystemId = null;

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array(
			'PAID',
			'DATE_PAID',
			'EMP_PAID_ID',
			'PAY_SYSTEM_ID',
			'PS_STATUS',
			'PS_STATUS_CODE',
			'PS_STATUS_DESCRIPTION',
			'PS_STATUS_MESSAGE',
			'PS_SUM',
			'PS_CURRENCY',
			'PS_RESPONSE_DATE',
			'PAY_VOUCHER_NUM',
			'PAY_VOUCHER_DATE',
			'DATE_PAY_BEFORE',
			'DATE_BILL',
			'XML_ID',
			'SUM',
			'CURRENCY',
			'PAY_SYSTEM_NAME',
			'COMPANY_ID',
			'PAY_RETURN_NUM',
			'PAY_RETURN_DATE',
			'EMP_RETURN_ID',
			'PAY_RETURN_COMMENT',
			'RESPONSIBLE_ID',
			'EMP_RESPONSIBLE_ID',
			'DATE_RESPONSIBLE_ID',
			'COMPANY_BY',
			'IS_RETURN',
			'COMMENTS'
		);
	}

	/**
	 * @return array
	 */
	public static function getMeaningfulFields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public static function getAllFields()
	{
		static $fields = null;
		if ($fields == null)
			$fields = array_keys(Internals\PaymentTable::getMap());
		return $fields;
	}

	public static function create(PaymentCollection $collection, PaySystemService $paySystem = null)
	{
		$fields = array(
			'PAID' => 'N',
			'IS_RETURN' => 'N'
		);

		if ($paySystem != null)
		{
			$fields["PAY_SYSTEM_ID"] = $paySystem->getId();
			$fields["PAY_SYSTEM_NAME"] = $paySystem->getName();
		}

		$payment = new static();
		$payment->setFieldsNoDemand($fields);
		$payment->setCollection($collection);

		$payment->paySystem = $paySystem;

		return $payment;
	}

	public static function loadForOrder($id)
	{
		if (intval($id) <= 0)
			throw new Main\ArgumentNullException("id");

		$payments = array();

		$paymentDataList = Internals\PaymentTable::getList(
			array(
				'filter' => array('ORDER_ID' => $id)
			)
		);
		while ($paymentData = $paymentDataList->fetch())
			$payments[] = new static($paymentData);

		return $payments;
	}

	public function delete()
	{
		$result = new Result();
		if ($this->isPaid())
		{
			$result->addError(new ResultError(Loc::getMessage('SALE_PAYMENT_DELETE_EXIST_PAID'), 'SALE_PAYMENT_DELETE_EXIST_PAID'));
			return $result;
		}

		return parent::delete();
	}

	public function dump($i)
	{
		return str_repeat(' ', $i)."Payment: Id=".$this->getId().", PAID=".$this->getField('PAID').", SUM=".$this->getField('SUM')."\n";
	}

	/**
	 * @param string $name
	 * @param mixed $oldValue
	 * @param mixed $value
	 * @return Result
	 * @throws Main\NotSupportedException
	 */
	protected function onFieldModify($name, $oldValue, $value)
	{
		global $USER;

		if ($name == "PAID")
		{
			if ($oldValue != "Y")
			{
				$this->setField('DATE_PAID', new Main\Type\DateTime());
				$this->setField('EMP_PAID_ID', $USER->GetID());
			}
		}
		elseif ($name == "IS_RETURN")
		{
			if ($oldValue != "Y")
			{
//				$this->setField('PAY_RETURN_DATE', new Main\Type\DateTime());
				$this->setField('EMP_RETURN_ID', $USER->GetID());
			}
		}
		elseif($name == "SUM")
		{
			if($this->isPaid())
			{
				$result = new Result();

				$result->addError( new ResultError(Loc::getMessage('SALE_PAYMENT_NOT_ALLOWED_CHANGE_SUM'), 'SALE_PAYMENT_NOT_ALLOWED_CHANGE_SUM') );
				return $result;
			}
		}

		return parent::onFieldModify($name, $oldValue, $value);
	}

	/**
	 * @return Entity\AddResult|Entity\UpdateResult
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 * @throws \Exception
	 */
	public function save()
	{
		$id = $this->getId();
		$fields = $this->fields->getValues();

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();

			if (!empty($fields) && is_array($fields))
			{
				//$fields['DATE_UPDATE'] = new Main\Type\DateTime();

				$r = Internals\PaymentTable::update($id, $fields);
				if (!$r->isSuccess())
					return $r;
			}

			$result = new Entity\UpdateResult();
		}
		else
		{
			$fields['ORDER_ID'] = $this->getParentOrderId();

			/** @var PaymentCollection $paymentCollection */
			if (!$paymentCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
			}

			/** @var Order $order */
			if (!$order = $paymentCollection->getOrder())
			{
				throw new Main\ObjectNotFoundException('Entity "Order" not found');
			}

			if (!isset($fields['CURRENCY']) || strval($fields['CURRENCY']) == "" )
			{
				$fields['CURRENCY'] = $order->getCurrency();

			}
			//$fields['DATE_INSERT'] = new Main\Type\DateTime();

			$r = Internals\PaymentTable::add($fields);
			if (!$r->isSuccess())
				return $r;

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);

			$result = new Entity\AddResult();

			if ($order->getId() > 0)
			{
				OrderHistory::addAction(
					'PAYMENT',
					$order->getId(),
					'PAYMENT_ADDED',
					$id,
					$this
				);
			}
		}


		if ($result->isSuccess())
		{
			/** @var PaymentCollection $paymentCollection */
			if (!$paymentCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
			}

			/** @var Order $order */
			if (!$order = $paymentCollection->getOrder())
			{
				throw new Main\ObjectNotFoundException('Entity "Order" not found');
			}

			OrderHistory::collectEntityFields('PAYMENT', $order->getId(), $id);

			if ($eventName = static::getEntityEventName())
			{
				$oldEntityValues = $this->fields->getOriginalValues();

				if (!empty($oldEntityValues))
				{
					/** @var Main\Event $event */
					$event = new Main\Event('sale', 'On'.$eventName.'EntitySaved', array(
						'ENTITY' => $this,
						'VALUES' => $oldEntityValues,
					));
					$event->send();
				}
			}

		}

		$this->fields->clearChanged();

		return $result;
	}

	private function getParentOrderId()
	{
		/** @var PaymentCollection $collection */
		$collection = $this->getCollection();
		$order = $collection->getOrder();
		return $order->getId();
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->getField('ID');
	}


	/**
	 * @return float
	 */
	public function getSum()
	{
		return floatval($this->getField('SUM'));
	}


	/**
	 * @return float
	 */
	public function getSumPaid()
	{
		return $this->getField('PS_SUM');
	}

	/**
	 * @return bool
	 */
	public function isPaid()
	{
		return ($this->getField('PAID') == "Y");
	}

	/**
	 * @return bool
	 */
	public function isReturn()
	{
		return ($this->getField('IS_RETURN') == "Y");
	}

	/**
	 * @return int
	 */
	public function getOrderId()
	{
		return $this->getField('ORDER_ID');
	}

	/**
	 * @return int
	 */
	public function getPaymentSystemId()
	{
		return $this->getField('PAY_SYSTEM_ID');
	}

	/**
	 * @return string
	 */
	public function getPaymentSystemName()
	{
		return $this->getField('PAY_SYSTEM_NAME');
	}

	/**
	 * @param $value
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 * @throws \Exception
	 */
	public function setPaid($value)
	{
		$result = new Result();

		if ($value == "Y")
		{
			if ($this->isPaid())
				return new Result();

			if ($this->isReturn() && $this->isInner())
			{
				$r = $this->setField('IS_RETURN', 'N');
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}
			}
			else
			{
				if ($this->isInner())
				{
					/** @var PaymentCollection $paymentCollection */
					if (!$paymentCollection = $this->getCollection())
					{
						throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
					}

					/** @var Order $order */
					if (!$order = $paymentCollection->getOrder())
					{
						throw new Main\ObjectNotFoundException('Entity "Order" not found');
					}

					/** @var Result $r */
					$r = Internals\PaySystemInner::createOperation($order, $this, Internals\PaySystemInner::OPERATION_DEBIT);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
					}


				}

			}
		}
		elseif($value == "N")
		{
			if (!$this->isPaid())
				return new Result();

			if ($this->isInner())
			{
				/** @var PaymentCollection $paymentCollection */
				if (!$paymentCollection = $this->getCollection())
				{
					throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
				}

				/** @var Order $order */
				if (!$order = $paymentCollection->getOrder())
				{
					throw new Main\ObjectNotFoundException('Entity "Order" not found');
				}

				/** @var Result $r */
				$r = Internals\PaySystemInner::createOperation($order, $this, Internals\PaySystemInner::OPERATION_CREDIT);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}
			}
		}
		else
		{
			throw new Main\ArgumentOutOfRangeException('value');
		}

		/** @var Result $r */
		$r = $this->setField('PAID', $value);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param $value
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 * @throws \Exception
	 */
	public function setReturn($value)
	{
		$result = new Result();

		if ($value == "Y")
		{
			if ($this->isReturn())
				return new Result();

		}
		elseif($value == "N")
		{
			if (!$this->isReturn())
				return new Result();

		}
		else
		{
			throw new Main\ArgumentOutOfRangeException('value');
		}

		/** @var Result $r */
		$r = $this->setField('IS_RETURN', $value);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @return bool|null
	 * @throws Main\ObjectNotFoundException
	 */
	public function isInner()
	{

		if ($this->isInner !== null)
			return $this->isInner;


		/** @var PaymentCollection $paymentCollection */
		if (!$paymentCollection = $this->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
		}

		/** @var Order $order */
		if (!$order = $paymentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		if ( ($paySystemId = static::getInnerPaySystemId()) && intval($paySystemId) > 0)
		{
			$this->isInner = ($this->getPaymentSystemId() == $paySystemId);
		}

		return $this->isInner;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws \Exception
	 */
	public function setField($name, $value)
	{
		if ($name == "PAY_SYSTEM_ID")
		{
			if (intval($value) > 0 && !PaySystemService::isExist($value))
			{
				$result = new Result();
				$result->addError( new ResultError(Loc::getMessage('SALE_PAYMENT_WRONG_PAYMENT_SERVICE'), 'SALE_PAYMENT_WRONG_PAYMENT_SERVICE') );
				return $result;
			}

			/** @var PaymentCollection $paymentCollection */
			if (!$paymentCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
			}

			if (count($paymentCollection) == 1)
			{
				/** @var Order $order */
				if ($order = $paymentCollection->getOrder())
				{
					if (strval($order->getField('PAY_SYSTEM_ID')) == '')
					{
						$order->setFieldNoDemand('PAY_SYSTEM_ID', intval($value));
					}
				}
			}
		}


		return parent::setField($name, $value);
	}

	/**
	 * @param string $name
	 * @param null $oldValue
	 * @param null $value
	 * @throws Main\ObjectNotFoundException
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{
		if ($this->getId() > 0)
		{
			/** @var PaymentCollection $paymentCollection */
			if (!$paymentCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "PaymentCollection" not found');
			}

			/** @var Order $order */
			if (($order = $paymentCollection->getOrder()) && $order->getId() > 0)
			{
				OrderHistory::addField(
					'PAYMENT',
					$order->getId(),
					$name,
					$oldValue,
					$value,
					$this->getId(),
					$this
				);
			}
		}
	}

	/**
	 * @return int|null
	 */
	protected static function getInnerPaySystemId()
	{
		if (static::$innerPaySystemId === null)
		{
			static::$innerPaySystemId = Internals\PaySystemInner::getId();
		}

		return static::$innerPaySystemId;
	}
}