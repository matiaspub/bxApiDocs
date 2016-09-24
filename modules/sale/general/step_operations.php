<?
use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale;

if (!Loader::includeModule('catalog'))
	return;

Loc::loadMessages(__FILE__);

class CSaleBasketDiscountConvert extends CCatalogStepOperations
{
	const SESSION_PREFIX = 'BDC';

	protected $filter = array();
	protected $orderViewUrl = '';

	public function __construct($sessID, $maxExecutionTime, $maxOperationCounter)
	{
		$sessID = (string)$sessID;
		if ($sessID == '')
			$sessID = self::SESSION_PREFIX.time();

		if (Main\Config\Option::get('main', '~sale_converted_15', 'N') == 'Y')
			$this->orderViewUrl = '/bitrix/admin/sale_order_view.php?ID=#ID#&lang='.LANGUAGE_ID;
		else
			$this->orderViewUrl = '/bitrix/admin/sale_order_detail.php?ID=#ID#&lang='.LANGUAGE_ID;

		parent::__construct($sessID, $maxExecutionTime, $maxOperationCounter);
	}

	public function setFilter($filter)
	{
		if (empty($filter) || !is_array($filter))
			return;
		$this->filter = $filter;
	}

	public function runOperation()
	{
		$found = false;
		$filter = array('>ID' => $this->lastID, 'ORDER_DISCOUNT_DATA.ID' => null);
		if (!empty($this->filter))
		{
			foreach ($this->filter as $filterKey => $filterValue)
				$filter[$filterKey] = $filterValue;
			unset($filterKey, $filterValue);
		}
		$ordersIterator = Sale\Internals\OrderTable::getList(array(
			'select' => array('ID', 'CURRENCY', 'LID'),
			'filter' => $filter,
			'order' => array('ID' => 'ASC'),
			'limit' => $this->maxOperationCounter
		));
		while ($order = $ordersIterator->fetch())
		{
			$found = true;

			$migrateResult = Sale\OrderDiscountManager::migrateOrderDiscounts($order);
			$this->lastID = $order['ID'];
			$this->allOperationCounter++;
			if (!$migrateResult->isSuccess())
			{
				$this->errorCounter++;
				$this->stepErrors[] = Loc::getMessage(
					'SALE_BASKET_DISCOUNT_ORDER_ERROR_REPORT',
					array(
						'#URL#' => str_replace('#ID#', $order['ID'], $this->orderViewUrl),
						'#TITLE#' => $order['ID'],
						'#ERRORS#' => implode('; ', $migrateResult->getErrorMessages())
					)
				);
			}
			unset($migrateResult);

			if ($this->maxExecutionTime > 0 && (time() - $this->startOperationTime > $this->maxExecutionTime))
				break;
		}
		unset($order, $ordersIterator);
		if (!$found)
			$this->finishOperation = true;
	}

	public static function getAllCounter()
	{
		$countQuery = new Main\Entity\Query(Sale\Internals\OrderTable::getEntity());
		$countQuery->addSelect(new Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter(array('ORDER_DISCOUNT_DATA.ID' => null));
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		return (int)$totalCount['CNT'];
	}

	public static function getFilterCounter($filter)
	{
		if (!is_array($filter))
			$filter = array();
		if (empty($filter))
			return self::getAllCounter();
		$filter['ORDER_DISCOUNT_DATA.ID'] = null;
		$countQuery = new Main\Entity\Query(Sale\Internals\OrderTable::getEntity());
		$countQuery->addSelect(new Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter($filter);
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		return (int)$totalCount['CNT'];
	}

	public static function checkFilter(Main\HttpRequest $request)
	{
		$filter = array();

		if (!empty($request['filter']['filter_type']))
		{
			switch ($request['filter']['filter_type'])
			{
				case 'id':
					if (isset($request['filter']['order_id_from']) && (int)$request['filter']['order_id_from'] > 0)
						$filter['>=ID'] = (int)$request['filter']['order_id_from'];
					if (isset($request['filter']['order_id_to']) && (int)$request['filter']['order_id_to'] > 0)
						$filter['<=ID'] = (int)$request['filter']['order_id_to'];
					break;
				case 'date':
					CTimeZone::Disable();
					if (isset($request['filter']['order_date_from']) && is_string($request['filter']['order_date_from']))
					{
						$date = trim($request['filter']['order_date_from']);
						if ($date != '')
						{
							try
							{
								$filter['>=DATE_INSERT'] = Main\Type\DateTime::createFromUserTime($date);
							}
							catch (Main\ObjectException $e)
							{

							}
						}
						unset($date);
					}
					if (isset($request['filter']['order_date_to']) && is_string($request['filter']['order_date_to']))
					{
						$date = trim($request['filter']['order_date_to']);
						if ($date != '')
						{
							try
							{
								$filter['<=DATE_INSERT'] = Main\Type\DateTime::createFromUserTime($date);
							}
							catch (Main\ObjectException $e)
							{

							}
						}
						unset($date);
					}
					CTimeZone::Enable();
					break;
			}
		}

		return $filter;
	}
}

class CSaleDiscountReindex extends CCatalogStepOperations
{
	const SESSION_PREFIX = 'SDR';

	protected $discountEditUrl = '';

	public function __construct($sessID, $maxExecutionTime, $maxOperationCounter)
	{
		$sessID = (string)$sessID;
		if ($sessID == '')
			$sessID = self::SESSION_PREFIX.time();
		$this->discountEditUrl = '/bitrix/admin/sale_discount_edit.php?ID=#ID#&lang='.LANGUAGE_ID;
		parent::__construct($sessID, $maxExecutionTime, $maxOperationCounter);
	}

	public function runOperation()
	{
		global $APPLICATION;

		$found = false;
		$filter = array('>ID' => $this->lastID, '=VERSION' => Sale\Internals\DiscountTable::VERSION_15);

		$discountsIterator = Sale\Internals\DiscountTable::getList(array(
			'select' => array(
				'ID', 'MODIFIED_BY', 'TIMESTAMP_X', 'CONDITIONS_LIST', 'ACTIONS_LIST', 'NAME'
			),
			'filter' => $filter,
			'order' => array('ID' => 'ASC'),
			'limit' => $this->maxOperationCounter
		));
		while ($discount = $discountsIterator->fetch())
		{
			$found = true;
			$error = array();
			$rawFields = array(
				'ID' => $discount['ID'],
				'CONDITIONS' => $discount['CONDITIONS_LIST'],
				'ACTIONS' => $discount['ACTIONS_LIST']
			);
			if (\CSaleDiscount::checkFields('UPDATE', $rawFields))
			{
				$fields = array(
					'MODIFIED_BY' => $discount['MODIFIED_BY'],
					'TIMESTAMP_X' => $discount['TIMESTAMP_X'],
					'UNPACK' => $rawFields['UNPACK'],
					'APPLICATION' => $rawFields['APPLICATION']
				);
				if (isset($rawFields['EXECUTE_MODULE']))
					$fields['EXECUTE_MODULE'] = $rawFields['EXECUTE_MODULE'];
				$useCoupons = 'N';
				$coupon = Sale\Internals\DiscountCouponTable::getList(array(
					'select' => array('ID', 'DISCOUNT_ID'),
					'filter' => array('=DISCOUNT_ID' => $discount['ID']),
					'limit' => 1
				))->fetch();
				if (!empty($coupon))
					$useCoupons = 'Y';
				$fields['USE_COUPONS'] = $useCoupons;
				unset($coupon, $useCoupons);
				$updateResult = Sale\Internals\DiscountTable::update($discount['ID'], $fields);
				if ($updateResult->isSuccess())
				{
					if (isset($rawFields['ENTITIES']))
						Sale\Internals\DiscountEntitiesTable::updateByDiscount($discount['ID'], $rawFields['ENTITIES'], true);
					if (isset($rawFields['HANDLERS']['MODULES']))
						Sale\Internals\DiscountModuleTable::updateByDiscount($discount['ID'], $rawFields['HANDLERS']['MODULES'], true);
				}
				else
				{
					$error = $updateResult->getErrorMessages();
				}
			}
			else
			{
				if ($ex = $APPLICATION->GetException())
					$error[] = $ex->GetString();
				else
					$error[] = Loc::getMessage('SALE_DISCOUNT_REINDEX_UPDATE_UNKNOWN_ERROR');
			}
			$this->lastID = $discount['ID'];
			$this->allOperationCounter++;
			if (!empty($error))
			{
				$this->errorCounter++;
				$this->stepErrors[] = Loc::getMessage(
					'SALE_DISCOUNT_REINDEX_ORDER_ERROR_REPORT',
					array(
						'#URL#' => str_replace('#ID#', $discount['ID'], $this->discountEditUrl),
						'#TITLE#' => (trim((string)$discount['NAME']) != '' ? $discount['NAME'] : $discount['ID']),
						'#ERRORS#' => implode('; ', $error)
					)
				);
			}

			if ($this->maxExecutionTime > 0 && (time() - $this->startOperationTime > $this->maxExecutionTime))
				break;
		}
		unset($discount, $discountsIterator);

		if (!$found)
			$this->finishOperation = true;
	}

	public static function getAllCounter()
	{
		$countQuery = new Main\Entity\Query(Sale\Internals\DiscountTable::getEntity());
		$countQuery->addSelect(new Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter(array('=VERSION' => Sale\Internals\DiscountTable::VERSION_15));
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		return (int)$totalCount['CNT'];
	}
}

class CSaleDiscountConvertExt extends CCatalogStepOperations
{
	const SESSION_PREFIX = 'SDC15';

	protected $discountEditUrl = '';
	protected $deliveryCodes = array();
	protected $deliveryRuleId = 'CondSaleDelivery';

	public function __construct($sessID, $maxExecutionTime, $maxOperationCounter)
	{
		$sessID = (string)$sessID;
		if ($sessID == '')
			$sessID = self::SESSION_PREFIX.time();
		$this->discountEditUrl = '/bitrix/admin/sale_discount_edit.php?ID=#ID#&lang='.LANGUAGE_ID;
		parent::__construct($sessID, $maxExecutionTime, $maxOperationCounter);
	}

	public function runOperation()
	{
		global $APPLICATION;

		$this->getDeliveryCodes();

		$found = false;
		$filter = array('>ID' => $this->lastID, '=VERSION' => Sale\Internals\DiscountTable::VERSION_NEW);

		$discountsIterator = Sale\Internals\DiscountTable::getList(array(
			'select' => array(
					'ID', 'MODIFIED_BY', 'TIMESTAMP_X', 'CONDITIONS_LIST', 'ACTIONS_LIST', 'NAME'
			),
			'filter' => $filter,
			'order' => array('ID' => 'ASC'),
			'limit' => $this->maxOperationCounter
		));
		while ($discount = $discountsIterator->fetch())
		{
			$found = true;
			$error = array();
			$this->convertDelivery($discount['CONDITIONS_LIST']);
			$rawFields = array(
				'ID' => $discount['ID'],
				'CONDITIONS' => $discount['CONDITIONS_LIST'],
				'ACTIONS' => $discount['ACTIONS_LIST']
			);
			if (\CSaleDiscount::checkFields('UPDATE', $rawFields))
			{
				$fields = array(
					'MODIFIED_BY' => $discount['MODIFIED_BY'],
					'TIMESTAMP_X' => $discount['TIMESTAMP_X'],
					'UNPACK' => $rawFields['UNPACK'],
					'CONDITIONS' => $discount['CONDITIONS_LIST'],
					'VERSION' => Sale\Internals\DiscountTable::VERSION_15
				);
				if (isset($rawFields['EXECUTE_MODULE']))
					$fields['EXECUTE_MODULE'] = $rawFields['EXECUTE_MODULE'];
				$updateResult = Sale\Internals\DiscountTable::update($discount['ID'], $fields);
				if ($updateResult->isSuccess())
				{
					if (isset($rawFields['ENTITIES']))
						Sale\Internals\DiscountEntitiesTable::updateByDiscount($discount['ID'], $rawFields['ENTITIES'], true);
					if (isset($rawFields['HANDLERS']['MODULES']))
						Sale\Internals\DiscountModuleTable::updateByDiscount($discount['ID'], $rawFields['HANDLERS']['MODULES'], true);
				}
				else
				{
					$error = $updateResult->getErrorMessages();
				}
			}
			else
			{
				if ($ex = $APPLICATION->GetException())
					$error[] = $ex->GetString();
				else
					$error[] = Loc::getMessage('SALE_DISCOUNT_REINDEX_UPDATE_UNKNOWN_ERROR');
			}
			$this->lastID = $discount['ID'];
			$this->allOperationCounter++;
			if (!empty($error))
			{
				$this->errorCounter++;
				$this->stepErrors[] = Loc::getMessage(
					'SALE_DISCOUNT_REINDEX_ORDER_ERROR_REPORT',
					array(
						'#URL#' => str_replace('#ID#', $discount['ID'], $this->discountEditUrl),
						'#TITLE#' => (trim((string)$discount['NAME']) != '' ? $discount['NAME'] : $discount['ID']),
						'#ERRORS#' => implode('; ', $error)
					)
				);
			}

			if ($this->maxExecutionTime > 0 && (time() - $this->startOperationTime > $this->maxExecutionTime))
				break;
		}
		unset($discount, $discountsIterator);

		if (!$found)
			$this->finishOperation = true;
	}

	public static function getAllCounter()
	{
		$countQuery = new Main\Entity\Query(Sale\Internals\DiscountTable::getEntity());
		$countQuery->addSelect(new Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter(array(
			'=VERSION' => Sale\Internals\DiscountTable::VERSION_NEW,
			'=DISCOUNT_ENTITY.ENTITY' => 'ORDER',
			'=DISCOUNT_ENTITY.FIELD_ENTITY' => 'PAY_SYSTEM_ID'
		));
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		return (int)$totalCount['CNT'];
	}

	protected function getDeliveryCodes()
	{
		$this->deliveryCodes = array();
		$deliveryIterator = Sale\Delivery\Services\Table::getList(array(
			'select' => array('ID', 'CODE'),
		));
		while ($delivery = $deliveryIterator->fetch())
		{
			$delivery['CODE'] = (string)$delivery['CODE'];
			if ($delivery['CODE'] == '')
				continue;
			$this->deliveryCodes[$delivery['CODE']] = $delivery['ID'];
		}
		unset($delivery, $deliveryIterator);
	}

	protected function convertDelivery(&$condition)
	{
		if (empty($condition) || !is_array($condition))
			return;
		if (!isset($condition['CLASS_ID']))
			return;
		if ($condition['CLASS_ID'] == $this->deliveryRuleId)
		{
			if (empty($condition['DATA']) || !is_array($condition['DATA']))
				return;
			if (empty($condition['DATA']['value']))
				return;
			$value = $condition['DATA']['value'];
			if (!is_array($value))
				$value = array($value);
			$newValue = array();
			foreach ($value as &$item)
			{
				if (isset($this->deliveryCodes[$item]))
					$newValue[] = $this->deliveryCodes[$item];
			}
			unset($item);
			$condition['DATA']['value'] = $newValue;
			unset($newValue, $value);
		}
		elseif (!empty($condition['CHILDREN']) && is_array($condition['CHILDREN']))
		{
			foreach ($condition['CHILDREN'] as &$subCondition)
				$this->convertDelivery($subCondition);
			unset($subCondition);
		}
	}
}