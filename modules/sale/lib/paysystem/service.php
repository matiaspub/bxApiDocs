<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Internals\CompanyTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Result;
use Bitrix\Main\IO;
use Bitrix\Sale\ResultError;

Loc::loadMessages(__FILE__);

class Service
{
	const EVENT_ON_BEFORE_PAYMENT_PAID = 'OnSalePsServiceProcessRequestBeforePaid';

	/** @var ServiceHandler|IHold|IRefund|IPrePayable|ICheckable|IPayable $handler */	
	private $handler = null;

	/**
	 * @var array
	 */
	private $fields = array();

	/** @var bool  */
	protected $isClone = false;

	/**
	 * @param $fields
	 */
	public function __construct($fields)
	{
		$handlerType = '';
		$className = '';

		$name = Manager::getFolderFromClassName($fields['ACTION_FILE']);

		foreach (Manager::getHandlerDirectories() as $type => $path)
		{
			if (IO\File::isFileExists($_SERVER['DOCUMENT_ROOT'].$path.$name.'/handler.php'))
			{
				$className = Manager::getClassNameFromPath($fields['ACTION_FILE']);
				if (!class_exists($className))
					require_once($_SERVER['DOCUMENT_ROOT'].$path.$name.'/handler.php');

				if (class_exists($className))
				{
					$handlerType = $type;
					break;
				}

				$className = '';
			}
		}

		if ($className === '')
		{
			$className = '\Bitrix\Sale\PaySystem\CompatibilityHandler';
			$handlerType = $fields['ACTION_FILE'];
		}

		$this->fields = $fields;
		$this->handler = new $className($handlerType, $this);
	}

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @param int $mode
	 * @return ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null, $mode = BaseServiceHandler::STREAM)
	{
		$this->handler->setInitiateMode($mode);
		$initResult = $this->handler->initiatePay($payment, $request);

		$psData = $initResult->getPsData();
		if ($psData)
		{
			$setResult = $payment->setFields($psData);
			if ($setResult->isSuccess())
			{
				/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
				$paymentCollection = $payment->getCollection();
				if ($paymentCollection)
				{
					$order = $paymentCollection->getOrder();
					if ($order)
					{
						$saveResult = $order->save();
						if (!$saveResult->isSuccess())
							$initResult->addErrors($saveResult->getErrors());
					}
				}
			}
			else
			{
				$initResult->addErrors($setResult->getErrors());
			}
		}

		return $initResult;
	}

	/**
	 * @return bool
	 */
	public function isRefundable()
	{
		if ($this->handler instanceof IRefundExtended)
			return $this->handler->isRefundableExtended();

		return $this->handler instanceof IRefund;
	}

	/**
	 * @param Payment $payment
	 * @param int $refundableSum
	 * @return ServiceResult|Result
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function refund(Payment $payment, $refundableSum = 0)
	{
		if ($this->isRefundable())
		{
			$result = new Result();

			if (!$payment->isPaid())
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_PS_SERVICE_PAYMENT_NOT_PAID')));
				return $result;
			}

			if ($refundableSum == 0)
				$refundableSum = $payment->getSum();

			/** @var ServiceResult $result */
			$result = $this->handler->refund($payment, $refundableSum);

			return $result;
		}

		throw new SystemException();
	}

	/**
	 * @param Request $request
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function processRequest(Request $request)
	{
		$processResult = new Result();

		$paymentId = $this->handler->getPaymentIdFromRequest($request);

		if (empty($paymentId))
		{
			$errorMessage = str_replace('#PAYMENT_ID#', $paymentId, Loc::getMessage('SALE_PS_SERVICE_PAYMENT_ERROR'));
			$processResult->addError(new Error($errorMessage));
			ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => $errorMessage
			));
			return $processResult;
		}

		list($orderId, $paymentId) = Manager::getIdsByPayment($paymentId);

		if (!$orderId)
		{
			$errorMessage = str_replace('#ORDER_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_ORDER_ERROR'));
			$processResult->addError(new Error($errorMessage));
			ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => $errorMessage
			));
			return $processResult;
		}

		/** @var \Bitrix\Sale\Order $order */
		$order = Order::load($orderId);

		if (!$order)
		{
			$errorMessage = str_replace('#ORDER_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_ORDER_ERROR'));
			$processResult->addError(new Error($errorMessage));
			ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => $errorMessage
			));
			return $processResult;
		}

		if ($order->isCanceled())
		{
			$errorMessage = str_replace('#ORDER_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_ORDER_CANCELED'));
			$processResult->addError(new Error($errorMessage));
			ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => $errorMessage
			));
			return $processResult;
		}
		/** @var \Bitrix\Sale\PaymentCollection $collection */
		$collection = $order->getPaymentCollection();

		/** @var \Bitrix\Sale\Payment $payment */
		$payment = $collection->getItemById($paymentId);

		if (!$payment)
		{
			$errorMessage = str_replace('#PAYMENT_ID#', $orderId, Loc::getMessage('SALE_PS_SERVICE_PAYMENT_ERROR'));
			$processResult->addError(new Error($errorMessage));
			ErrorLog::add(array(
				'ACTION' => 'processRequest',
				'MESSAGE' => $errorMessage
			));
			return $processResult;
		}

		if (ErrorLog::DEBUG_MODE)
		{
			ErrorLog::add(array(
				'ACTION' => 'RESPONSE',
				'MESSAGE' => print_r($request->toArray(), 1)
			));
		}

		/** @var \Bitrix\Sale\PaySystem\ServiceResult $serviceResult */
		$serviceResult = $this->handler->processRequest($payment, $request);

		if ($serviceResult->isSuccess())
		{
			$status = null;
			$operationType = $serviceResult->getOperationType();

			if ($operationType == ServiceResult::MONEY_COMING)
				$status = 'Y';
			else if ($operationType == ServiceResult::MONEY_LEAVING)
				$status = 'N';

			if ($status !== null)
			{
				$event = new Event('sale', self::EVENT_ON_BEFORE_PAYMENT_PAID,
					array(
						'payment' => $payment,
						'status' => $status,
						'pay_system_id' => $this->getField('ID')
					)
				);
				$event->send();

				$paidResult = $payment->setPaid($status);
				if (!$paidResult->isSuccess())
				{
					ErrorLog::add(array(
						'ACTION' => 'PAYMENT SET PAID',
						'MESSAGE' => join(' ', $paidResult->getErrorMessages())
					));
					$serviceResult->setResultApplied(false);
				}
			}

			$psData = $serviceResult->getPsData();
			if ($psData)
			{
				$res = $payment->setFields($psData);

				if (!$res->isSuccess())
				{
					ErrorLog::add(array(
						'ACTION' => 'PAYMENT SET DATA',
						'MESSAGE' => join(' ', $res->getErrorMessages())
					));
					$serviceResult->setResultApplied(false);
				}
			}

			$saveResult = $order->save();

			if (!$saveResult->isSuccess())
			{
				ErrorLog::add(array(
					'ACTION' => 'ORDER SAVE',
					'MESSAGE' => join(' ', $saveResult->getErrorMessages())
				));
				$serviceResult->setResultApplied(false);
			}
		}
		else
		{
			$serviceResult->setResultApplied(false);
			$processResult->addErrors($serviceResult->getErrors());
		}

		$this->handler->sendResponse($serviceResult, $request);

		return $processResult;
	}

	/**
	 * @return string
	 */
	public function getConsumerName()
	{
		return 'PAYSYSTEM_'.$this->fields['ID'];
	}

	/**
	 * @return array
	 */
	public function getHandlerDescription()
	{
		return $this->handler->getDescription();
	}

	/**
	 * @return bool
	 */
	public function isBlockable()
	{
		return $this->handler instanceof IHold;
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 * @throws SystemException
	 */
	public function cancel(Payment $payment)
	{
		if ($this->isBlockable())
			return $this->handler->cancel($payment);

		throw new SystemException();
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 * @throws SystemException
	 */
	public function confirm(Payment $payment)
	{
		if ($this->isBlockable())
			return  $this->handler->confirm($payment);

		throw new SystemException();
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getField($name)
	{
		return $this->fields[$name];
	}

	/**
	 * @return array
	 */
	public function getCurrency()
	{
		return $this->handler->getCurrencyList();
	}

	/**
	 * @return bool
	 */
	public function isCash()
	{
		return $this->fields['IS_CASH'] == 'Y';
	}

	/**
	 * @param Payment $payment
	 * @return ServiceResult
	 */
	public function creditNoDemand(Payment $payment)
	{
		return $this->handler->creditNoDemand($payment);
	}

	/**
	 * @param Payment $payment
	 * @return ServiceResult
	 */
	public function debitNoDemand(Payment $payment)
	{
		return $this->handler->debitNoDemand($payment);
	}

	/**
	 * @return bool
	 */
	public function isPayable()
	{
		if ($this->handler instanceof IPayable)
			return true;

		if (method_exists($this->handler, 'isPayableCompatibility'))
			return $this->handler->isPayableCompatibility();

		return false;
	}

	/**
	 * @return bool
	 */
	public function isAffordPdf()
	{
		return $this->handler->isAffordPdf();
	}

	/**
	 * @param Payment $payment
	 * @return mixed
	 */
	public function getPaymentPrice(Payment $payment)
	{
		if ($this->isPayable())
			return $this->handler->getPrice($payment);

		return 0;
	}

	/**
	 * @param array $params
	 */
	public function setTemplateParams(array $params)
	{
		$this->handler->setExtraParams($params);
	}

	/**
	 * @param Payment|null $payment
	 * @param $templateName
	 */
	public function showTemplate(Payment $payment = null, $templateName)
	{
		$this->handler->showTemplate($payment, $templateName);
	}

	/**
	 * @return bool
	 */
	public function isPrePayable()
	{
		return $this->handler instanceof IPrePayable;
	}

	/**
	 * @param Payment|null $payment
	 * @param Request $request
	 * @throws NotSupportedException
	 */
	public function initPrePayment(Payment $payment = null, Request $request)
	{
		if ($this->isPrePayable())
			return $this->handler->initPrePayment($payment, $request);

		throw new NotSupportedException;
	}

	/**
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function getPrePaymentProps()
	{
		if ($this->isPrePayable())
			return $this->handler->getProps();

		throw new NotSupportedException;
	}

	/**
	 * @param array $orderData
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function basketButtonAction(array $orderData = array())
	{
		if ($this->isPrePayable())
			return $this->handler->basketButtonAction($orderData = array());

		throw new NotSupportedException;
	}

	/**
	 * @param array $orderData
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function setOrderDataForPrePayment($orderData = array())
	{
		if ($this->isPrePayable())
			return $this->handler->setOrderConfig($orderData);

		throw new NotSupportedException;
	}

	/**
	 * @param $orderData
	 * @return mixed
	 * @throws NotSupportedException
	 */
	public function payOrderByPrePayment($orderData)
	{
		if ($this->isPrePayable())
			return $this->handler->payOrder($orderData);

		throw new NotSupportedException;
	}

	/**
	 * @return array
	 */
	public function getFieldsValues()
	{
		return $this->fields;
	}

	/**
	 * @return bool
	 */
	public function isAllowEditPayment()
	{
		return $this->fields['ALLOW_EDIT_PAYMENT'] == 'Y';
	}

	/**
	 * @return bool
	 */
	public function isCheckable()
	{
		if ($this->handler instanceof ICheckable)
			return true;

		if (method_exists($this->handler, 'isCheckableCompatibility'))
			return $this->handler->isCheckableCompatibility();

		return true;
	}

	/**
	 * @param Payment $payment
	 * @return \Bitrix\Main\Entity\AddResult|\Bitrix\Main\Entity\UpdateResult|ServiceResult|Result|mixed
	 * @throws NotSupportedException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function check(Payment $payment)
	{
		if ($this->isCheckable())
		{
			/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
			$paymentCollection = $payment->getCollection();

			/** @var \Bitrix\Sale\Order $order */
			$order = $paymentCollection->getOrder();

			if (!$order->isCanceled())
			{
				/** @var ServiceResult $result */
				$result = $this->handler->check($payment);
				if ($result instanceof ServiceResult && $result->isSuccess())
				{
					$psData = $result->getPsData();
					if ($psData)
					{
						$res = $payment->setFields($psData);
						if (!$res->isSuccess())
							return $res;

						if ($result->getOperationType() == ServiceResult::MONEY_COMING)
						{
							$res = $payment->setPaid('Y');
							if (!$res->isSuccess())
								return $res;
						}

						$res = $order->save();
						if (!$res->isSuccess())
							return $res;
					}
				}
			}
			else
			{
				$result = new ServiceResult();
				$result->addError(new EntityError(Loc::getMessage('SALE_PS_SERVICE_ORDER_CANCELED', array('#ORDER_ID#' => $order->getId()))));
			}

			return $result;
		}

		throw new NotSupportedException;
	}

	/**
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return Service
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$paySystemServiceClone = clone $this;
		$paySystemServiceClone->isClone = true;

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $paySystemServiceClone;
		}

		if ($handler = $this->handler)
		{
			if (!$cloneEntity->contains($handler))
			{
				$cloneEntity[$handler] = $handler->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($handler))
			{
				$paySystemServiceClone->handler = $cloneEntity[$handler];
			}
		}

		return $paySystemServiceClone;
	}

	/**
	 * @return bool
	 */
	public function isClone()
	{
		return $this->isClone;
	}

	/**
	 * @return bool
	 */
	public function isCustom()
	{
		return in_array($this->handler->getHandlerType(), array('CUSTOM', 'USER'));
	}

	/**
	 * @param Payment $payment
	 * @return array
	 */
	public function getParamsBusValue(Payment $payment)
	{
		return $this->handler->getParamsBusValue($payment);
	}

	/**
	 * @return bool
	 */
	public function isRequested()
	{
		return $this->handler instanceof IRequested;
	}

	/**
	 * @param string $requestId
	 * @return array
	 */
	public function checkMovementListStatus($requestId)
	{
		if ($this->isRequested())
			return $this->handler->getMovementListStatus($requestId);

		return array();
	}

	/**
	 * @param string $requestId
	 * @return bool
	 */
	public function getMovementList($requestId)
	{
		if ($this->isRequested())
			return $this->handler->getMovementList($requestId);

		return false;
	}

	/**
	 * @return ServiceResult
	 */
	public function processAccountMovementList()
	{
		$serviceResult = new ServiceResult();

		if ($this->isRequested())
		{
			$requestId = $this->handler->createMovementListRequest();
			if ($requestId !== false)
			{
				$result = $this->handler->getMovementListStatus($requestId);
				if (!$result)
				{
					$serviceResult->addError(new Error(Loc::getMessage('SALE_PS_SERVICE_STATUS_ERROR')));
					return $serviceResult;
				}

				if ($result['status'] == true)
				{
					$movementList = $this->handler->getMovementList($requestId);
					return $this->applyAccountMovementList($movementList);
				}
				else
				{
					\CAgent::Add(array(
						'NAME' => '\Bitrix\Sale\PaySystem\Manager::getMovementListStatus('.$this->getField('ID').',\''.$requestId.'\');',
						'MODULE_ID' => 'sale',
						'ACTIVE' => 'Y',
						'NEXT_EXEC' => date('d.m.Y H:i:s', strtotime($result['estimatedTime'])),
						'AGENT_INTERVAL' => 60,
						'IS_PERIOD' => 'Y'
					));
				}
			}
		}

		return $serviceResult;
	}

	/**
	 * @param $movementList
	 * @return ServiceResult
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function applyAccountMovementList($movementList)
	{
		$serviceResult = new ServiceResult();

		if ($this->isRequested())
		{
			foreach ($movementList as $item)
			{

				if (strlen($item['PAYMENT_ID']) > 0)
					list($orderId, $paymentId) = Manager::getIdsByPayment($item['PAYMENT_ID']);
				else
					list($orderId, $paymentId) = $this->findEntityIds($item);

				if ($orderId > 0)
				{
					$order = Order::load($orderId);
					if ($order)
					{
						$paymentCollection = $order->getPaymentCollection();
						if ($paymentCollection && $paymentId > 0)
						{
							/** @var \Bitrix\Sale\Payment $payment */
							$payment = $paymentCollection->getItemById($paymentId);
							if ($payment)
							{
								$result = $payment->setPaid('Y');
								if ($result->isSuccess())
									$result = $order->save();

								if (!$result->isSuccess())
									$serviceResult->addErrors($result->getErrors());
							}
						}
					}
				}
			}
		}

		return $serviceResult;
	}

	private function findEntityIds($item)
	{
		$orderId = 0;
		$paymentId = 0;
		$personTypeList = Manager::getPersonTypeIdList($this->getField('ID'));

		$map = BusinessValue::getMapping('BUYER_PERSON_COMPANY_INN', $this->getConsumerName(), array_shift($personTypeList));
		if ($map)
		{
			$filter = array();
			$runtimeFields = array();

			$type = $map['PROVIDER_KEY'];
			$value = $map['PROVIDER_VALUE'];

			if ($type == 'PROPERTY')
			{
				$runtimeFields['PROP'] = array(
					'data_type' => 'Bitrix\Sale\Internals\OrderPropsValueTable',
					'reference' => array('ref.ORDER_ID' => 'this.ORDER_ID'),
					'join_type' => 'inner'
				);

				$filter = array('PAID' => 'N', 'PROP.CODE' => $value, 'PROP.VALUE' => $item['CONTRACTOR_INN']);
			}
			elseif ($type == 'REQUISITE')
			{
				if (!Loader::includeModule('crm'))
					return array($orderId, $paymentId);

				$orderIds = array();

				$requisite = new EntityRequisite();
				$res = $requisite->getList(
					array(
						'select' => array('ID'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => array(\CCrmOwnerType::Company, \CCrmOwnerType::Contact),
							'=RQ_INN' => $item['CONTRACTOR_INN']
						)
					)
				);

				$rqIds = array();
				while ($row = $res->fetch())
					$rqIds[] = $row['ID'];

				if ($rqIds)
				{
					$res = EntityLink::getList(
						array(
							'select' => array('ENTITY_ID'),
							'filter' => array('=ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice, '=REQUISITE_ID' => $rqIds)
						)
					);

					while ($row = $res->fetch())
						$orderIds[] = $row['ENTITY_ID'];
				}

				if ($orderIds)
					$filter = array('ID' => $orderIds, 'PAID' => 'N');
			}

			if ($filter)
			{
				$dbRes = Payment::getList(array('select' => array('ID', 'ORDER_ID', 'SUM', 'CURRENCY'), 'filter' => $filter, 'runtime' => $runtimeFields));
				while ($data = $dbRes->fetch())
				{
					if (PriceMaths::roundByFormatCurrency($data['SUM'], $data['CURRENCY']) == PriceMaths::roundByFormatCurrency($item['SUM'], $data['CURRENCY']))
					{
						list($orderId, $paymentId) = array($data['ORDER_ID'], $data['ID']);
						break;
					}
				}
			}
		}

		return array($orderId, $paymentId);
	}
}