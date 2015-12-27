<?php

namespace Bitrix\Sale;

use Bitrix\Sale\Internals\BusinessValueTable;
use \Bitrix\Main\EventManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BusinessValue
{
	const PERSON_TYPE_COMMON     = 'C';
	const PERSON_TYPE_ENTITY     = 'E';
	const PERSON_TYPE_INDIVIDUAL = 'I';

	public static function getBestMapping($codeName, $consumerName = null, $personTypeId = null)
	{
		if (! ($codeName && is_string($codeName)))
			throw new ArgumentTypeException('codeName', 'string>""');

		if (! ($consumerName === null || (is_string($consumerName) && $consumerName != '')))
			throw new ArgumentTypeException('consumerName', 'string>""|null');

		if (! ($personTypeId === null || (is_numeric($personTypeId) && $personTypeId > 0)))
			throw new ArgumentTypeException('personTypeId', 'int>0|null');

		$personTypeId = (int) $personTypeId;

		$result = BusinessValueTable::getList(array(
			'select' => array('ID', 'CODE_NAME', 'CONSUMER_NAME', 'PERSON_TYPE_ID', 'PROVIDER_NAME', 'PROVIDER_FIELD'),
			'filter' => array(
				'=CODE_NAME'      => $codeName,
				'=CONSUMER_NAME'  => $consumerName ? array($consumerName, null) : null,
				'=PERSON_TYPE_ID' => $personTypeId ? array($personTypeId, null) : null,
			),
			'limit' => $consumerName && $personTypeId ? 4 : ($consumerName || $personTypeId ? 2 : 1),
		));

		$bestRow    = null;
		$bestWeight = -1;

		while ($row = $result->fetch())
		{
			$weight = 0; // TODO check weight logic!

			if ($row['CONSUMER_NAME'] === $consumerName)
				$weight += 10;
			elseif ($row['CONSUMER_NAME'] === null)
				$weight += 1;

			if ($row['PERSON_TYPE_ID'] === $personTypeId)
				$weight += 10;
			elseif ($row['PERSON_TYPE_ID'] === null)
				$weight += 1;

			if ($weight > $bestWeight)
			{
				$bestRow    = $row;
				$bestWeight = $weight;
			}
		}

		return $bestRow;
	}

	public static function getValueFromMapping($mapping)
	{
		$value = null;

		if (is_array($mapping)
			&& isset($mapping['PROVIDER_NAME'])
			&& ($providers = self::getProviders())
			&& isset($providers[$mapping['PROVIDER_NAME']])
			&& ($provider = $providers[$mapping['PROVIDER_NAME']])
			&& isset($provider['GET_VALUE'])
			&& is_callable($provider['GET_VALUE']))
		{
			$value = call_user_func($provider['GET_VALUE'], $mapping);
		}

		return $value;
	}

	/** @internal */
	public static function getProviders()
	{
		static $data;

		if (! $data)
		{
			$data = array(
				'VALUE' => array(
					'NAME'      => Loc::getMessage('BIZVAL_PROVIDER_VALUE'),
					'SORT'      => 100,
					'GET_VALUE' => function (array $mapping)
					{
						return $mapping['PROVIDER_FIELD'] ?: null;
					},
				),
				'COMPANY' => array(
					'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_COMPANY'),
					'SORT'   => 600,
					'FIELDS' => call_user_func(function ()
					{
						$data = array(
							'ID'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_ID'         ), 'GROUP' => 'ENTITY'),
							'NAME'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_NAME'       ), 'GROUP' => 'ENTITY'),
							'LOCATION_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_LOCATION'   ), 'GROUP' => 'ENTITY'),
							'CODE'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_CODE'       ), 'GROUP' => 'ENTITY'),
							'XML_ID'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_XML_ID'     ), 'GROUP' => 'ENTITY'),
							'ACTIVE'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_ACTIVE'     ), 'GROUP' => 'ENTITY'),
							'DATE_CREATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_DATE_CREATE'), 'GROUP' => 'ENTITY'),
							'DATE_MODIFY' => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_DATE_MODIFY'), 'GROUP' => 'ENTITY'),
							'CREATED_BY'  => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_CREATED_BY' ), 'GROUP' => 'ENTITY'),
							'MODIFIED_BY' => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_MODIFIED_BY'), 'GROUP' => 'ENTITY'),
						);

						global $USER_FIELD_MANAGER;
						$result = $USER_FIELD_MANAGER->GetUserFields(Internals\CompanyTable::getUfId(), null, LANGUAGE_ID);

						foreach ($result as $name => $row)
							$data[$name] = array('NAME' => $row['EDIT_FORM_LABEL'] ?: $name, 'GROUP' => 'UF');

						return $data;
					}),
					'FIELDS_GROUPS' => array(
						'ENTITY' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_COMPANY_ENTITY')),
						'UF'     => array('NAME' => Loc::getMessage('BIZVAL_GROUP_COMPANY_UF'    )),
					),
				),

			);

			self::addEventData('OnGetBusinessValueProviders', $data);
		}

		return $data;
	}

	/** @internal */
	public static function getConsumers()
	{
		static $data;

		if (! $data)
		{
			$data = array(
				'1C' => array(
					'NAME' => Loc::getMessage('BIZVAL_CONSUMER_1C'),
					'SORT' => 400,
					'CODES' => array( // TODO remove '1C_CODES' after migration from 1C business-values
						'CLIENT_NAME'          => array('1C_CODE' => 'FULL_NAME'     , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_NAME'         ), 'SORT' =>  100, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_LAST_NAME'     => array('1C_CODE' => 'SURNAME'       , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_LAST_NAME'    ), 'SORT' =>  200, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_FIRST_NAME'    => array('1C_CODE' => 'NAME'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_FIRST_NAME'   ), 'SORT' =>  300, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_SECOND_NAME'   => array('1C_CODE' => 'SECOND_NAME'   , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_SECOND_NAME'  ), 'SORT' =>  400, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_AGENT_NAME'    => array('1C_CODE' => 'AGENT_NAME'    , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_AGENT_NAME'   ), 'SORT' =>  500, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_CONTACT_NAME'  => array('1C_CODE' => 'CONTACT_PERSON', 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_CONTACT_NAME' ), 'SORT' =>  600, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_BIRTHDAY'      => array('1C_CODE' => 'BIRTHDAY'      , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_BIRTHDAY'     ), 'SORT' =>  700, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_SEX'           => array('1C_CODE' => 'MALE'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_SEX'          ), 'SORT' =>  800, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_INN'           => array('1C_CODE' => 'INN'           , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_INN'          ), 'SORT' =>  900, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_KPP'           => array('1C_CODE' => 'KPP'           , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_KPP'          ), 'SORT' => 1000, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_ADDRESS'       => array('1C_CODE' => 'ADDRESS_FULL'  , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_ADDRESS'      ), 'SORT' => 1100, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_ZIP'           => array('1C_CODE' => 'INDEX'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_ZIP'          ), 'SORT' => 1200, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_COUNTRY'       => array('1C_CODE' => 'COUNTRY'       , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COUNTRY'      ), 'SORT' => 1300, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_REGION'        => array('1C_CODE' => 'REGION'        , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_REGION'       ), 'SORT' => 1400, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_STATE'         => array('1C_CODE' => 'STATE'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_STATE'        ), 'SORT' => 1500, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_TOWN'          => array('1C_CODE' => 'TOWN'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_TOWN'         ), 'SORT' => 1600, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_CITY'          => array('1C_CODE' => 'CITY'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_CITY'         ), 'SORT' => 1700, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_STREET'        => array('1C_CODE' => 'STREET'        , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_STREET'       ), 'SORT' => 1800, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_HOUSING'       => array('1C_CODE' => 'BUILDING'      , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_HOUSING'      ), 'SORT' => 1900, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_BUILDING'      => array('1C_CODE' => 'HOUSE'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_BUILDING'     ), 'SORT' => 2000, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_APARTMENT'     => array('1C_CODE' => 'FLAT'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_APARTMENT'    ), 'SORT' => 2100, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_PHONE'         => array('1C_CODE' => 'PHONE'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_PHONE'        ), 'SORT' => 2200, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'CLIENT_EMAIL'         => array('1C_CODE' => 'EMAIL'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_EMAIL'        ), 'SORT' => 2300, 'GROUP' => 'CLIENT' , 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_INDIVIDUAL),
						'COMPANY_AGENT_NAME'   => array('1C_CODE' => 'AGENT_NAME'    , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_AGENT_NAME'  ), 'SORT' =>  100, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_NAME'         => array('1C_CODE' => 'FULL_NAME'     , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_NAME'        ), 'SORT' =>  200, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_ADDRESS'      => array('1C_CODE' => 'ADDRESS_FULL'  , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_ADDRESS'     ), 'SORT' =>  300, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_ZIP'          => array('1C_CODE' => 'INDEX'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_ZIP'         ), 'SORT' =>  400, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_COUNTRY'      => array('1C_CODE' => 'COUNTRY'       , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_COUNTRY'     ), 'SORT' =>  500, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_REGION'       => array('1C_CODE' => 'REGION'        , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_REGION'      ), 'SORT' =>  600, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_STATE'        => array('1C_CODE' => 'STATE'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_STATE'       ), 'SORT' =>  700, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_TOWN'         => array('1C_CODE' => 'TOWN'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_TOWN'        ), 'SORT' =>  800, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_CITY'         => array('1C_CODE' => 'CITY'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_CITY'        ), 'SORT' =>  900, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_STREET'       => array('1C_CODE' => 'STREET'        , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_STREET'      ), 'SORT' => 1000, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_HOUSING'      => array('1C_CODE' => 'BUILDING'      , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_HOUSING'     ), 'SORT' => 1100, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_BUILDING'     => array('1C_CODE' => 'HOUSE'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_BUILDING'    ), 'SORT' => 1200, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_APARTMENT'    => array('1C_CODE' => 'FLAT'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_APARTMENT'   ), 'SORT' => 1300, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_INN'          => array('1C_CODE' => 'INN'           , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_INN'         ), 'SORT' => 1400, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_KPP'          => array('1C_CODE' => 'KPP'           , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_KPP'         ), 'SORT' => 1500, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_EGRPO'        => array('1C_CODE' => 'EGRPO'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_EGRPO'       ), 'SORT' => 1600, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_OKVED'        => array('1C_CODE' => 'OKVED'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKVED'       ), 'SORT' => 1700, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_OKDP'         => array('1C_CODE' => 'OKDP'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKDP'        ), 'SORT' => 1800, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_OKOPF'        => array('1C_CODE' => 'OKOPF'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKOPF'       ), 'SORT' => 1900, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_OKFC'         => array('1C_CODE' => 'OKFC'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKFC'        ), 'SORT' => 2000, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_OKPO'         => array('1C_CODE' => 'OKPO'          , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKPO'        ), 'SORT' => 2100, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_BANK_ACCOUNT' => array('1C_CODE' => 'ACCOUNT_NUMBER', 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_BANK_ACCOUNT'), 'SORT' => 2200, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_PHONE'        => array('1C_CODE' => 'PHONE'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_PHONE'       ), 'SORT' => 2300, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_EMAIL'        => array('1C_CODE' => 'EMAIL'         , 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_EMAIL'       ), 'SORT' => 2400, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
						'COMPANY_CONTACT_NAME' => array('1C_CODE' => 'CONTACT_PERSON', 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_CONTACT_NAME'), 'SORT' => 2500, 'GROUP' => 'COMPANY', 'PERSON_TYPE' => BusinessValue::PERSON_TYPE_ENTITY    ),
					),
					'CODES_GROUPS' => array(
						'COMPANY' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_COMPANY'), 'SORT' => 100),
						'CLIENT'  => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CLIENT' ), 'SORT' => 200),
					),
				),
			);

			self::addEventData('OnGetBusinessValueConsumers', $data, function (&$row)
			{
				// TODO validate codes array('NAME', 'SORT', 'GROUP', 'PROVIDER', 'PERSON_TYPE') maybe??
				BusinessValue::sortArray($row['CODES']);
			});
		}

		return $data;
	}

	/** @internal */
	public static function getCodes()
	{
		static $data;

		if (! $data)
		{
			$data = array();
		}

		return $data;
	}

	private static function addEventData($eventName, array &$data, callable $eachRow = null)
	{
		foreach (EventManager::getInstance()->findEventHandlers('sale', $eventName) as $handler)
		{
			$rows = ExecuteModuleEventEx($handler);

			if (! is_array($rows))
				throw new SystemException('Not array returned from: '.print_r($handler, true));

			foreach ($rows as $name => $row)
			{
				if (! is_array($row))
					throw new SystemException('Not array in: '.$eventName.'()['.$name.'] => '.print_r($handler, true));

				if (isset($data[$name]))
					throw new SystemException('Duplicate in: '.$eventName.'()['.$name.'] => '.print_r($handler, true));

				if ($eachRow)
					$eachRow($row);

				$data[$name] = $row;
			}
		}

		self::sortArray($data);
	}

	/** @internal */
	public static function sortArray(array &$data)
	{
		uasort($data, function ($a, $b)
		{
			$a = $a['SORT'] ?: PHP_INT_MAX;
			$b = $b['SORT'] ?: PHP_INT_MAX;
			return $a < $b ? -1 : ($a > $b ? 1 : 0);
		});
	}

	public static function getActivePersonTypeIds()
	{
		// TODO
		return array();
	}

	// Case 2. Common interface
	public static function getValueFromProvider(IBusinessValueProvider $provider, $codeName, $consumerName)
	{
		return $provider->getBusinessValue(self::getBestMapping($codeName, $consumerName, $provider->getPersonTypeId()));
	}
}

interface IBusinessValueProvider
{
	public function getPersonTypeId();
	static public function getBusinessValue($mapping);
}

// Case 3. Consumer

class Yandex3xConsumer
{
	/** @var IBusinessValueProvider $provider */
	protected $provider;

	public function __construct(IBusinessValueProvider $provider)
	{
		$this->provider = $provider;
	}

	public function getBusinessValue($codeName)
	{
		return BusinessValue::getValueFromProvider($this->provider, $codeName, 'PAYSYSTEM_YANDEX3X');
	}

	public static function getBusinessValueConsumers()
	{
		return array(
			'PAYSYSTEM_YANDEX3X' => array(
				'NAME' => Loc::getMessage('BIZVAL_CONSUMER_PAYSYSTEM_YANDEX3X'),
				'SORT' => 500,
				'CODES' => array(
					// $ShopID = CSalePaySystemAction::GetParamValue("SHOP_ID");
					'SHOP_ID' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_SHOP_ID'),
						'SORT'        => 100,
						'GROUP'       => 'SHOP',
						//'PROVIDER'    => 'PAYMENT',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
					// $scid = CSalePaySystemAction::GetParamValue("SCID");
					'SCID' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_SCID'),
						'SORT'        => 200,
						//'GROUP'       => 'SHOP',
						//'PROVIDER'    => 'PAYMENT',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
					// $orderNumber = CSalePaySystemAction::GetParamValue("ORDER_PAYMENT_ID");
					'ORDER_PAYMENT_ID' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_ORDER_PAYMENT_ID'),
						'SORT'        => 300,
						'GROUP'       => 'PAYMENT',
						'PROVIDER'    => 'PAYMENT',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
					// $Sum = CSalePaySystemAction::GetParamValue("SHOULD_PAY");
					'ORDER_PRICE' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_ORDER_PAYMENT_ID'),
						'SORT'        => 400,
						'GROUP'       => 'ORDER',
						'PROVIDER'    => 'ORDER',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
					// $orderDate = CSalePaySystemAction::GetParamValue("ORDER_DATE");
					'ORDER_DATE' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_ORDER_DATE'),
						'SORT'        => 500,
						'GROUP'       => 'ORDER',
						'PROVIDER'    => 'ORDER',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
					// $customerNumber = CSalePaySystemAction::GetParamValue("ORDER_ID");
					'ORDER_ID' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_ORDER_ID'),
						'SORT'        => 600,
						'GROUP'       => 'ORDER',
						'PROVIDER'    => 'ORDER',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
					// $paymentType = CSalePaySystemAction::GetParamValue("PAYMENT_VALUE");
					'ORDER_PAYMENT_PRICE' => array(
						'NAME'        => Loc::getMessage('BIZVAL_CODE_ORDER_ID'),
						'SORT'        => 700,
						'GROUP'       => 'PAYMENT',
						'PROVIDER'    => 'PAYMENT',
						'PERSON_TYPE' => BusinessValue::PERSON_TYPE_COMMON,
					),
				),
				'CODES_GROUPS' => array(
					'SHOP' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_SHOP'), 'SORT' => 500),
				),
			),
		);
	}
}

// Case 1. Inject into entity

class OOrder extends Order
{
	public function getBusinessValue($mapping)
	{
		$value = null;

		if (is_array($mapping) && isset($mapping['PROVIDER_NAME'], $mapping['PROVIDER_FIELD']))
		{
			switch ($mapping['PROVIDER_NAME'])
			{
				case 'ORDER':

					$value = $this->getField($mapping['PROVIDER_FIELD']);

					break;

				case 'PROPERTY':

					/** @var PropertyValue $property */
					foreach ($this->getPropertyCollection() as $property)
					{
						if ($property->getField('ORDER_PROPS_ID') == $mapping['PROVIDER_FIELD'])
						{
							$value = $property->getValue() ?: null;
							break;
						}
					}

					break;

				case 'USER':

					global $USER;

					if (($userId = $this->getField('USER_ID')) && ($user = $USER->GetByID($userId)->Fetch()))
						$value = $user[$mapping['PROVIDER_FIELD']];

					break;

				// TODO case 'PAYMENT' & 'SHIPMENT': aggregate fields maybe?? What about COMPANY??

				default:

					$value = BusinessValue::getValueFromMapping($mapping);
			}
		}

		return $value;
	}

	public static function getBusinessValueProviders()
	{
		return array(
			'ORDER' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_ORDER'),
				'SORT'   => 200,
				'FIELDS' => array(
					'ID'               => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_ID'           )),
					'ACCOUNT_NUMBER'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_NUMBER'       )),
					'DATE_INSERT'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_DATETIME'     )),
					'DATE_INSERT_DATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_DATE'         )),
					'DATE_PAY_BEFORE'  => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PAY_BEFORE'   )),
					'SHOULD_PAY'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PRICE'        )),
					'CURRENCY'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_CURRENCY'     )),
					'PRICE'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_SUM'          )),
					'LID'              => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_SITE_ID'      )),
					'PRICE_DELIVERY'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PRICE_DELIV'  )),
					'DISCOUNT_VALUE'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_DESCOUNT'     )),
					'USER_ID'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_USER_ID'      )),
					'PAY_SYSTEM_ID'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PAY_SYSTEM_ID')),
					'DELIVERY_ID'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_DELIVERY_ID'  )),
					'TAX_VALUE'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_TAX'          )),
				),
			),
			// TODO not here maybe later??
			'USER' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_USER'),
				'SORT'   => 100,
				'FIELDS' => array(
					'ID'                  => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_USER_ID'               ), 'GROUP' => 'CLIENT'        ),
					'LOGIN'               => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_USER_LOGIN'            ), 'GROUP' => 'CLIENT'        ),
					'NAME'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_FIRST_NAME'            ), 'GROUP' => 'CLIENT'        ),
					'SECOND_NAME'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_SECOND_NAME'           ), 'GROUP' => 'CLIENT'        ),
					'LAST_NAME'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_LAST_NAME'             ), 'GROUP' => 'CLIENT'        ),
					'EMAIL'               => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_EMAIL'                 ), 'GROUP' => 'CLIENT'        ),
					'LID'                 => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_USER_SITE_ID'          ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_PROFESSION' => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_PROFESSION'            ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_WWW'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_WEBSITE'               ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_ICQ'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_ICQ'                   ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_GENDER'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_SEX'                   ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_FAX'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_FAX'                   ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_MOBILE'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_PHONE'                 ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_STREET'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_ADDRESS'               ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_MAILBOX'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_POSTAL_ADDRESS'        ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_CITY'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_CITY'                  ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_STATE'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_REGION'                ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_ZIP'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_ZIP'                   ), 'GROUP' => 'CLIENT'        ),
					'PERSONAL_COUNTRY'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COUNTRY'               ), 'GROUP' => 'CLIENT'        ),
					'WORK_COMPANY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_NAME'          ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_DEPARTMENT'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_DEPARTMENT'    ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_POSITION'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_POSITION'      ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_WWW'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_WEBSITE'       ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_PHONE'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_PHONE'         ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_FAX'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_FAX'           ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_STREET'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_ADDRESS'       ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_MAILBOX'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_POSTAL_ADDRESS'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_CITY'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_CITY'          ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_STATE'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_REGION'        ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_ZIP'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_ZIP'           ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_COUNTRY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_CLIENT_COMPANY_COUNTRY'       ), 'GROUP' => 'CLIENT_COMPANY'),
				),
				'FIELDS_GROUPS' => array(
					'CLIENT'         => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CLIENT'        ), 'SORT' => 100),
					'CLIENT_COMPANY' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CLIENT_COMPANY'), 'SORT' => 200),
				),
			),
			'PROPERTY' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_PROPERTY'),
				'SORT'   => 300,
				'FIELDS' => call_user_func(function ()
				{
					$data = array();

					$result = Internals\OrderPropsTable::getList(array(
						'select' => array('ID', 'NAME', 'PERSON_TYPE_ID'),
						'filter' => array('=PERSON_TYPE_ID' => array_keys(BusinessValue::getActivePersonTypeIds())),
						'order'  => array('PERSON_TYPE_ID', 'SORT'),
					));

					while ($row = $result->fetch())
						$data[$row['ID']] = array('NAME' => $row['NAME'], 'GROUP' => $row['PERSON_TYPE_ID']);

					return $data;
				}),
				'FIELDS_GROUPS' => call_user_func(function ()
				{
					$data = array();

					foreach (BusinessValue::getActivePersonTypeIds() as $personTypeId => $row)
						$data[$personTypeId] = array('NAME' => $row['NAME']);

					return $data;
				}),
			),
		);
	}
}

class PPayment extends Payment
{
	public function getBusinessValue($mapping)
	{
		$value = null;

		if (is_array($mapping) && isset($mapping['PROVIDER_NAME'], $mapping['PROVIDER_FIELD']))
		{
			switch ($mapping['PROVIDER_NAME'])
			{
				case 'PAYMENT':

					$value = $this->getField($mapping['PROVIDER_FIELD']);

					break;

				case 'COMPANY':

					if ($companyId = $this->getField('COMPANY_ID'))
					{
						if (substr($mapping['PROVIDER_FIELD'], 0, 3) == 'UF_')
						{
							global $USER_FIELD_MANAGER;
							$value = $USER_FIELD_MANAGER->GetUserFieldValue(Internals\CompanyTable::getUfId(), $mapping['PROVIDER_FIELD'], $companyId) ?: null;
						}
						elseif ($company = Internals\CompanyTable::getById($companyId)->fetch())
						{
							$value = $company[$mapping['PROVIDER_FIELD']];
						}
					}

					break;

				default:

					/** @var PaymentCollection $collection */
					$value = ($collection = $this->getCollection()) && ($order = $collection->getOrder())
						? $order->getBusinessValue($mapping)
						: BusinessValue::getValueFromMapping($mapping);
			}
		}

		return $value;
	}

	public static function getBusinessValueProviders()
	{
		return array(
			'PAYMENT' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_PAYMENT'),
				'SORT'   => 400,
				'FIELDS' => array(
					'ID'                    => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_ID'                   )),
					'PAID'                  => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAID'                 )),
					'DATE_PAID'             => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_PAID'            )),
					'EMP_PAID_ID'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_EMP_PAID_ID'          )),
					'PAY_SYSTEM_ID'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_SYSTEM_ID'        )),
					'PS_STATUS'             => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_STATUS'            )),
					'PS_STATUS_CODE'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_STATUS_CODE'       )),
					'PS_STATUS_DESCRIPTION' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_STATUS_DESCRIPTION')),
					'PS_STATUS_MESSAGE'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_STATUS_MESSAGE'    )),
					'PS_SUM'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_SUM'               )),
					'PS_CURRENCY'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_CURRENCY'          )),
					'PS_RESPONSE_DATE'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PS_RESPONSE_DATE'     )),
					'PAY_VOUCHER_NUM'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_VOUCHER_NUM'      )),
					'PAY_VOUCHER_DATE'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_VOUCHER_DATE'     )),
					'DATE_PAY_BEFORE'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_PAY_BEFORE'      )),
					'DATE_BILL'             => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_BILL'            )),
					'XML_ID'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_XML_ID'               )),
					'SUM'                   => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_SUM'                  )),
					'CURRENCY'              => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_CURRENCY'             )),
					'PAY_SYSTEM_NAME'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_SYSTEM_NAME'      )),
					'COMPANY_ID'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_COMPANY_ID'           )),
					'PAY_RETURN_NUM'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_RETURN_NUM'       )),
					'PAY_RETURN_DATE'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_RETURN_DATE'      )),
					'PAY_RETURN_COMMENT'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_RETURN_COMMENT'   )),
					'RESPONSIBLE_ID'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_RESPONSIBLE_ID'       )),
					'EMP_RESPONSIBLE_ID'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_EMP_RESPONSIBLE_ID'   )),
					'DATE_RESPONSIBLE_ID'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_RESPONSIBLE_ID'  )),
					'COMPANY_BY'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_COMPANY_BY'           )),
				),
			),
		);
	}
}

class SShipment extends Shipment
{
	public function getBusinessValue($mapping)
	{
		$value = null;

		if (is_array($mapping) && isset($mapping['PROVIDER_NAME'], $mapping['PROVIDER_FIELD']))
		{
			switch ($mapping['PROVIDER_NAME'])
			{
				case 'SHIPMENT':

					$value = $this->getField($mapping['PROVIDER_FIELD']);

					break;

				case 'COMPANY':

					if ($companyId = $this->getField('COMPANY_ID'))
					{
						if (substr($mapping['PROVIDER_FIELD'], 0, 3) == 'UF_')
						{
							global $USER_FIELD_MANAGER;
							$value = $USER_FIELD_MANAGER->GetUserFieldValue(Internals\CompanyTable::getUfId(), $mapping['PROVIDER_FIELD'], $companyId) ?: null;
						}
						elseif ($company = Internals\CompanyTable::getById($companyId)->fetch())
						{
							$value = $company[$mapping['PROVIDER_FIELD']];
						}
					}

					break;

				default:

					/** @var ShipmentCollection $collection */
					$value = ($collection = $this->getCollection()) && ($order = $collection->getOrder())
						? $order->getBusinessValue($mapping)
						: BusinessValue::getValueFromMapping($mapping);
			}
		}

		return $value;
	}

	public static function getBusinessValueProviders()
	{
		return array(
			'SHIPMENT' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_SHIPMENT'),
				'SORT'   => 500,
				'FIELDS' => array(
					'STATUS_ID'             => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_STATUS_ID'            )),
					'PRICE_DELIVERY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_PRICE_DELIVERY'       )),
					'ALLOW_DELIVERY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_ALLOW_DELIVERY'       )),
					'DATE_ALLOW_DELIVERY'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_ALLOW_DELIVERY'  )),
					'EMP_ALLOW_DELIVERY_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_ALLOW_DELIVERY_ID')),
					'DEDUCTED'              => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DEDUCTED'             )),
					'DATE_DEDUCTED'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_DEDUCTED'        )),
					'EMP_DEDUCTED_ID'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_DEDUCTED_ID'      )),
					'REASON_UNDO_DEDUCTED'  => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_REASON_UNDO_DEDUCTED' )),
					'DELIVERY_ID'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_ID'          )),
					'DELIVERY_DOC_NUM'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_DOC_NUM'     )),
					'DELIVERY_DOC_DATE'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_DOC_DATE'    )),
					'TRACKING_NUMBER'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_TRACKING_NUMBER'      )),
					'XML_ID'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_XML_ID'               )),
					'PARAMETERS'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_PARAMETERS'           )),
					'DELIVERY_NAME'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_NAME'        )),
					'COMPANY_ID'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_COMPANY_ID'           )),
					'MARKED'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_MARKED'               )),
					'DATE_MARKED'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_MARKED'          )),
					'EMP_MARKED_ID'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_MARKED_ID'        )),
					'REASON_MARKED'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_REASON_MARKED'        )),
					'CANCELED'              => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_CANCELED'             )),
					'DATE_CANCELED'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_CANCELED'        )),
					'EMP_CANCELED_ID'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_CANCELED_ID'      )),
				),
			),
		);
	}
}
