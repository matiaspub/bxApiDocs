<?php

namespace Bitrix\Sale;

use	Bitrix\Sale\Internals\Input,
	Bitrix\Sale\Internals\BusinessValueTable,
	Bitrix\Sale\Internals\BusinessValueCodeTable,
	Bitrix\Sale\Internals\BusinessValueGroupTable,
	Bitrix\Sale\Internals\BusinessValueParentTable,
	Bitrix\Sale\Internals\BusinessValueCodeParentTable,
	Bitrix\Sale\Internals\BusinessValuePersonDomainTable,

	Bitrix\Sale\Order,
	Bitrix\Sale\Payment,
	Bitrix\Sale\Shipment,
	Bitrix\Sale\Internals\CompanyTable,

	Bitrix\Sale\Internals\OrderTable,
	Bitrix\Sale\Internals\PaymentTable,
	Bitrix\Sale\Internals\ShipmentTable,
	Bitrix\Sale\Internals\OrderPropsValueTable,
	Bitrix\Sale\Internals\PersonTypeTable,
	Bitrix\Main\Application,
	Bitrix\Main\SystemException,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class BusinessValueOld
{
	const COMMON_PERSON_ID  = 0; // person type id
	const COMMON_DOMAIN     = 'C';
	const INDIVIDUAL_DOMAIN = 'I';
	const ENTITY_DOMAIN     = 'E';

	static function getForPayment($payment, $codeName, $order = null)
	{
		static $cache = array();

		if (is_numeric($payment))
		{
			$payment = ($v = $cache[$payment]) ? $v : PaymentTable::getById($payment)->fetch();
		}
		elseif ($payment instanceof Payment)
		{
			if (($collection = $payment->getCollection()) && ($o = $collection->getOrder()))
				$order = $o;

			$payment = $payment->getFieldValues();
		}

		if (is_array($payment))
		{
			$cache[$payment['ID']] = $payment;

			return self::get($codeName, $order ? $order : $payment['ORDER_ID'], $payment, null);
		}
		else
		{
			return null;
		}
	}

	static function getForShipment($shipment, $codeName, $order = null)
	{
		static $cache = array();

		if (is_numeric($shipment))
		{
			$shipment = ($v = $cache[$shipment]) ? $v : ShipmentTable::getById($shipment)->fetch();
		}
		elseif ($shipment instanceof Shipment)
		{
			if (($collection = $shipment->getCollection()) && ($o = $collection->getOrder()))
				$order = $o;

			$shipment = $shipment->getFieldValues();
		}

		if (is_array($shipment))
		{
			$cache[$shipment['ID']] = $shipment;

			return self::get($codeName, ($order ? $order : $shipment['ORDER_ID']), null, $shipment);
		}
		else
		{
			return null;
		}
	}

	static function getForOrder($order, $codeName)
	{
		return self::get($codeName, $order, null, null);
	}

	static function getMap($codeName, $personTypeId = self::COMMON_PERSON_ID)
	{
		static $cache = array();

		if (! $cache)
		{
			$result = BusinessValueTable::getList(array('select' => array('NAME' => 'CODE.NAME', 'PERSON_TYPE_ID', 'ENTITY', 'ITEM')));

			while ($row = $result->fetch())
				$cache[$row['PERSON_TYPE_ID']][$row['NAME']] = array($row['ENTITY'], $row['ITEM']);
		}

		$map = null;

		if ($codes = $cache[$personTypeId])
			$map = $codes[$codeName];

		if (! $map && $personTypeId && ($codes = $cache[0]))
			$map = $codes[$codeName];

		if (! $map)
			$map = array(null, null);

		return $map;
	}

	private static function get($codeName, $order, $payment, $shipment)
	{
		static $cache = array();

		if (is_numeric($order))
			$order = ($v = $cache[$order]) ? $v : OrderTable::getById($order)->fetch();
		elseif ($order instanceof Order)
			$order = $order->getFieldValues();

		if (is_array($order))
			$cache[$order['ID']] = $order;
		else
			return null;

		$result = null;

		list ($entity, $item) = self::getMap($codeName, $order['PERSON_TYPE_ID']);

		switch ($entity)
		{
			case 'VALUE':

				$result = $item;

				break;

			case 'ORDER':

				$result = $order[$item];

				break;

			case 'PAYMENT':

				if ($payment)
					$result = $payment[$item];

				break;

			case 'SHIPMENT':

				if ($shipment)
					$result = $shipment[$item];

				break;

			case 'PROPERTY':

				$property = OrderPropsValueTable::getList(array(
					'select' => array('VALUE'),
					'filter' => array('ORDER_ID' => $order['ID'], 'ORDER_PROPS_ID' => $item),
					'limit'  => 1,
				))->fetch();

				if ($property)
					$result = $property['VALUE'];

				break;

			case 'COMPANY':

				$companyId = null;

				if ($payment)
					$companyId = $payment['COMPANY_ID'];
				elseif ($shipment)
					$companyId = $shipment['COMPANY_ID'];

				if ($companyId)
				{
					if (substr($item, 0, 3) == 'UF_')
					{
						global $USER_FIELD_MANAGER;

						if ($value = $USER_FIELD_MANAGER->GetUserFieldValue(CompanyTable::getUfId(), $item, $companyId))
							$result = $value;
					}
					else
					{
						if ($company = CompanyTable::getById($companyId)->fetch())
							$result = $company[$item];
					}
				}

				break;

			case 'USER':

				global $USER;

				if (($userId = $order['USER_ID']) && ($user = $USER->GetByID($userId)->Fetch()))
					$result = $user[$item];

				break;
		}

		return $result;
	}

	/**
	 * @param string $parentName - parent identifier
	 * @param string $translationSource
	 * @param array $data - GROUP => ()
	 * @return bool - true on success or false on failure
	 * @throws SystemException
	 */
	static function install($parentName, $translationSource, array $data)
	{
		$error = false;

		// load person types

		$persons = array(/* ID => NAME (LID) */);

		$result = PersonTypeTable::getList(array('select'  => array('ID')));

		while ($row = $result->fetch())
			$persons[$row['ID']] = true;

		// load person domains

		$personsDomains = array();
		$result = BusinessValuePersonDomainTable::getList(array('select' => array('PERSON_TYPE_ID', 'DOMAIN')));
		while ($row = $result->fetch())
			$personsDomains[$row['PERSON_TYPE_ID']] = $row['DOMAIN'];

		// install person domains

		if (is_array($data['PERSON_DOMAIN']))
		{
			foreach ($data['PERSON_DOMAIN'] as $personId => $domain)
			{
				if (! $persons[$personId])
					throw new SystemException("invalid person type `$personId`", 0, __FILE__, __LINE__);

				switch ($domain)
				{
					case self::COMMON_DOMAIN:
					case self::INDIVIDUAL_DOMAIN:
					case self::ENTITY_DOMAIN: break;
					default: throw new SystemException("invalid domain: $domain", 0, __FILE__, __LINE__);
				}

				if (! $personsDomains[$personId])
				{
					if (BusinessValuePersonDomainTable::add(array('PERSON_TYPE_ID' => $personId, 'DOMAIN' => $domain))->isSuccess())
					{
						$personsDomains[$personId] = $domain;
					}
					else
					{
						$error = true;
					}
				}
			}
		}

		$personsDomainsWithCommon = array(self::COMMON_PERSON_ID => self::COMMON_DOMAIN) + $personsDomains;

		// load groups

		$groups = array();
		$result = BusinessValueGroupTable::getList(array('select' => array('NAME', 'ID')));
		while ($row = $result->fetch())
			$groups[$row['NAME']] = $row['ID'];

		// install new groups

		if (is_array($data['GROUPS']))
		{
			foreach ($data['GROUPS'] as $groupName => $group)
			{
				if (! $groups[$groupName])
				{
					if (! is_string($groupName))
						throw new SystemException("invalid group name", 0, __FILE__, __LINE__);

					if (! is_array($group))
						throw new SystemException("invalid group", 0, __FILE__, __LINE__);

					$result = BusinessValueGroupTable::add(array(
						'NAME' => $groupName,
						'SORT' => $group['SORT']
					));

					if ($result->isSuccess())
						$groups[$groupName] = $result->getId();
					else
						$error = true;
				}
			}
		}

		// install codes

		if (is_array($data['CODES']))
		{
			// get parent id, install parent if not exists

			if ($row = BusinessValueParentTable::getList(array('select' => array('ID'), 'filter' => array('=NAME' => $parentName)))->fetch())
			{
				$parentId = $row['ID'];
			}
			else
			{
				$result = BusinessValueParentTable::add(array('NAME' => $parentName, 'LANG_SRC' => $translationSource));

				if ($result->isSuccess())
					$parentId = $result->getId();
				else
					return false;
			}

			// load codes

			$codes = array();
			$result = BusinessValueCodeTable::getList(array('select' => array('ID', 'NAME')));
			while ($row = $result->fetch())
				$codes[$row['NAME']] = $row['ID'];

			// load value maps

			$valueMaps = array();
			$result = BusinessValueTable::getList(array('select' => array('CODE_ID', 'PERSON_TYPE_ID')));
			while ($row = $result->fetch())
				$valueMaps[$row['CODE_ID']][$row['PERSON_TYPE_ID']] = true;

			// load parent codes

			$parentCodes = array();
			$result = BusinessValueCodeParentTable::getList(array('select' => array('CODE_ID'), 'filter' => array('=PARENT_ID' => $parentId)));
			while ($row = $result->fetch())
				$parentCodes[$row['CODE_ID']] = true;

			// install: groups, codes, parent codes

			foreach ($data['CODES'] as $codeName => $code)
			{
				if (! is_string($codeName))
					throw new SystemException("invalid code name", 0, __FILE__, __LINE__);

				if (! is_array($code))
					throw new SystemException("invalid code", 0, __FILE__, __LINE__);

				// get group id, install group if not exists

				$groupId = null;

				if (($groupName = $code['GROUP']) && ! ($groupId = $groups[$groupName]))
				{
					$result = BusinessValueGroupTable::add(array('NAME' => $groupName));

					if ($result->isSuccess())
						$groups[$groupName] = $groupId = $result->getId();
					else
						$error = true;
				}

				// get code domain

				switch ($domain = $code['DOMAIN'])
				{
					case self::COMMON_DOMAIN:
					case self::INDIVIDUAL_DOMAIN:
					case self::ENTITY_DOMAIN: break;
					default: throw new SystemException("invalid domain: $domain", 0, __FILE__, __LINE__);
				}

				// get code id, install code if not exists

				if (! $codeId = $codes[$codeName])
				{
					$result = BusinessValueCodeTable::add(array(
						'NAME'     => $codeName,
						'DOMAIN'   => $domain,
						'GROUP_ID' => $groupId,
						'SORT'     => $code['SORT'],
					));

					if ($result->isSuccess())
					{
						$codeId = $result->getId();
						$codes[$codeName] = $codeId;
					}
					else
					{
						$error = true;
						continue;
					}
				}

				// install value maps if not exist

				if (is_array($code['MAP']))
				{
					foreach ($code['MAP'] as $personId => $map)
					{
						if (! is_array($map) || count($map) != 2)
							throw new SystemException("invalid map: ".print_r($map, true), 0, __FILE__, __LINE__);

						if ($domain === self::COMMON_DOMAIN)
						{
							if (! $personsDomainsWithCommon[$personId])
								throw new SystemException("invalid person type id `$personId`", 0, __FILE__, __LINE__);
						}
						else
						{
							if ($domain !== $personsDomains[$personId])
								throw new SystemException("invalid person type id `$personId`", 0, __FILE__, __LINE__);
						}

						if (! isset($valueMaps[$codeId][$personId]))
						{
							if (BusinessValueTable::add(array(
								'PERSON_TYPE_ID' => $personId,
								'CODE_ID'        => $codeId,
								'ENTITY'         => $map[0],
								'ITEM'           => $map[1]
							))->isSuccess())
								$valueMaps[$codeId][$personId] = true;
							else
								$error = true;
						}
					}
				}

				// install code parent if not exists

				if (! $parentCodes[$codeId])
				{
					if (BusinessValueCodeParentTable::add(array('CODE_ID' => $codeId, 'PARENT_ID' => $parentId))->isSuccess())
						$parentCodes[$codeId] = true;
					else
						$error = true;
				}
			}
		}

		return ! $error;
	}



	static function uninstall($parentName)
	{
		// get parent id

		if ($row = BusinessValueParentTable::getList(array('select' => array('ID'), 'filter' => array('=NAME' => $parentName)))->fetch())
			$parentId = $row['ID'];
		else
			return true;

		// TODO...
	}

	static function import1CProfiles()
	{
		$codes = array(
			'CLIENT_NAME'         => array('FULL_NAME'     , 'GROUP' => 'CLIENT', 'SORT' =>  100, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_LAST_NAME'    => array('SURNAME'       , 'GROUP' => 'CLIENT', 'SORT' =>  200, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_FIRST_NAME'   => array('NAME'          , 'GROUP' => 'CLIENT', 'SORT' =>  300, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_SECOND_NAME'  => array('SECOND_NAME'   , 'GROUP' => 'CLIENT', 'SORT' =>  400, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_AGENT_NAME'   => array('AGENT_NAME'    , 'GROUP' => 'CLIENT', 'SORT' =>  500, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_CONTACT_NAME' => array('CONTACT_PERSON', 'GROUP' => 'CLIENT', 'SORT' =>  600, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_BIRTHDAY'     => array('BIRTHDAY'      , 'GROUP' => 'CLIENT', 'SORT' =>  700, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_SEX'          => array('MALE'          , 'GROUP' => 'CLIENT', 'SORT' =>  800, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_INN'          => array('INN'           , 'GROUP' => 'CLIENT', 'SORT' =>  900, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_KPP'          => array('KPP'           , 'GROUP' => 'CLIENT', 'SORT' => 1000, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_ADDRESS'      => array('ADDRESS_FULL'  , 'GROUP' => 'CLIENT', 'SORT' => 1100, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_ZIP'          => array('INDEX'         , 'GROUP' => 'CLIENT', 'SORT' => 1200, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_COUNTRY'      => array('COUNTRY'       , 'GROUP' => 'CLIENT', 'SORT' => 1300, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_REGION'       => array('REGION'        , 'GROUP' => 'CLIENT', 'SORT' => 1400, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_STATE'        => array('STATE'         , 'GROUP' => 'CLIENT', 'SORT' => 1500, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_TOWN'         => array('TOWN'          , 'GROUP' => 'CLIENT', 'SORT' => 1600, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_CITY'         => array('CITY'          , 'GROUP' => 'CLIENT', 'SORT' => 1700, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_STREET'       => array('STREET'        , 'GROUP' => 'CLIENT', 'SORT' => 1800, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_HOUSING'      => array('BUILDING'      , 'GROUP' => 'CLIENT', 'SORT' => 1900, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_BUILDING'     => array('HOUSE'         , 'GROUP' => 'CLIENT', 'SORT' => 2000, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_APARTMENT'    => array('FLAT'          , 'GROUP' => 'CLIENT', 'SORT' => 2100, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_PHONE'        => array('PHONE'         , 'GROUP' => 'CLIENT', 'SORT' => 2200, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'CLIENT_EMAIL'        => array('EMAIL'         , 'GROUP' => 'CLIENT', 'SORT' => 2300, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array()),
			'COMPANY_AGENT_NAME'   => array('AGENT_NAME'    , 'GROUP' => 'COMPANY', 'SORT' =>  100, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_NAME'         => array('FULL_NAME'     , 'GROUP' => 'COMPANY', 'SORT' =>  200, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_ADDRESS'      => array('ADDRESS_FULL'  , 'GROUP' => 'COMPANY', 'SORT' =>  300, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_ZIP'          => array('INDEX'         , 'GROUP' => 'COMPANY', 'SORT' =>  400, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_COUNTRY'      => array('COUNTRY'       , 'GROUP' => 'COMPANY', 'SORT' =>  500, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_REGION'       => array('REGION'        , 'GROUP' => 'COMPANY', 'SORT' =>  600, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_STATE'        => array('STATE'         , 'GROUP' => 'COMPANY', 'SORT' =>  700, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_TOWN'         => array('TOWN'          , 'GROUP' => 'COMPANY', 'SORT' =>  800, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_CITY'         => array('CITY'          , 'GROUP' => 'COMPANY', 'SORT' =>  900, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_STREET'       => array('STREET'        , 'GROUP' => 'COMPANY', 'SORT' => 1000, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_HOUSING'      => array('BUILDING'      , 'GROUP' => 'COMPANY', 'SORT' => 1100, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_BUILDING'     => array('HOUSE'         , 'GROUP' => 'COMPANY', 'SORT' => 1200, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_APARTMENT'    => array('FLAT'          , 'GROUP' => 'COMPANY', 'SORT' => 1300, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_INN'          => array('INN'           , 'GROUP' => 'COMPANY', 'SORT' => 1400, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_KPP'          => array('KPP'           , 'GROUP' => 'COMPANY', 'SORT' => 1500, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_EGRPO'        => array('EGRPO'         , 'GROUP' => 'COMPANY', 'SORT' => 1600, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_OKVED'        => array('OKVED'         , 'GROUP' => 'COMPANY', 'SORT' => 1700, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_OKDP'         => array('OKDP'          , 'GROUP' => 'COMPANY', 'SORT' => 1800, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_OKOPF'        => array('OKOPF'         , 'GROUP' => 'COMPANY', 'SORT' => 1900, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_OKFC'         => array('OKFC'          , 'GROUP' => 'COMPANY', 'SORT' => 2000, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_OKPO'         => array('OKPO'          , 'GROUP' => 'COMPANY', 'SORT' => 2100, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_BANK_ACCOUNT' => array('ACCOUNT_NUMBER', 'GROUP' => 'COMPANY', 'SORT' => 2200, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_PHONE'        => array('PHONE'         , 'GROUP' => 'COMPANY', 'SORT' => 2300, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_EMAIL'        => array('EMAIL'         , 'GROUP' => 'COMPANY', 'SORT' => 2400, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
			'COMPANY_CONTACT_NAME' => array('CONTACT_PERSON', 'GROUP' => 'COMPANY', 'SORT' => 2500, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array()),
		);

		$personsDomains = array();

		$result = Application::getConnection()->query('SELECT * FROM b_sale_export');

		while ($row = $result->fetch())
		{
			if (($import = unserialize($row['VARS'])) && is_array($import))
			{
				$personId = $row['PERSON_TYPE_ID'];
				$domain = $import['IS_FIZ'] === 'Y' ? BusinessValue::INDIVIDUAL_DOMAIN : BusinessValue::ENTITY_DOMAIN;

				$personsDomains[$personId] = $domain;

				foreach ($codes as &$code)
				{
					if ($code['DOMAIN'] == $domain && ($map = $import[$code[0]]) && is_array($map) && ($item = $map['VALUE']))
					{
						$entity = $map['TYPE'];

						switch ($entity)
						{
							case ''        : break;
							case 'USER'    : break;
							case 'ORDER'   : break;
							case 'PROPERTY':
								if (! is_numeric($item)) // TODO: 6_COUNTRY, 6_CITY, 18_COUNTRY, 18_CITY
									$entity = null;
								break;
							default: $entity = null;
						}

						if ($entity !== null)
							$code['MAP'][$personId] = array($entity, $item);
					}
				}

				unset($code);
			}
		}

		return BusinessValue::install('1C_INTEGRATION', '/bitrix/modules/sale/1c_integration_codes.php', array(
			'PERSON_DOMAIN' => $personsDomains,
			'GROUPS' => array(
				'COMPANY' => array('SORT' => 100),
				'CLIENT'  => array('SORT' => 200),
			),
			'CODES' => $codes,
		));
	}

	static function testInstall()
	{
		var_dump(\Bitrix\Sale\BusinessValue::install('BASIC', null, array(
			'PERSON_DOMAIN' => array(
				1 => BusinessValue::INDIVIDUAL_DOMAIN,
				2 => BusinessValue::ENTITY_DOMAIN,
			),
			'GROUPS' => array(
				'BASIC' => array('SORT' => 0),
			),
			'CODES' => array(
				'BASIC_COMMON'     => array('GROUP' => 'BASIC', 'SORT' => 0, 'DOMAIN' => BusinessValue::COMMON_DOMAIN    ),
				'BASIC_INDIVIDUAL' => array('GROUP' => 'BASIC', 'SORT' => 0, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN),
				'BASIC_ENTITY'     => array('GROUP' => 'BASIC', 'SORT' => 0, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN    ),
			),
		)));

		var_dump(\Bitrix\Sale\BusinessValue::install('OTHER', null, array(
			'PERSON_DOMAIN' => array(
				1 => BusinessValue::INDIVIDUAL_DOMAIN,
				2 => BusinessValue::ENTITY_DOMAIN,
			),
			'GROUPS' => array(
				'OTHER' => array('SORT' => 1),
			),
			'CODES' => array(
				'OTHER_COMMON' => array('GROUP' => 'OTHER', 'SORT' => 0, 'DOMAIN' => BusinessValue::COMMON_DOMAIN, 'MAP' => array(
					BusinessValue::COMMON_PERSON_ID => array('VALUE', 'other common value'),
					1                               => array('VALUE', 'other individual value'),
					2                               => array('VALUE', 'other entity value'),
				)),
				'OTHER_COMMON_ALL' => array('GROUP' => 'OTHER', 'SORT' => 0, 'DOMAIN' => BusinessValue::COMMON_DOMAIN, 'MAP' => array(
					BusinessValue::COMMON_PERSON_ID => array('VALUE', 'other common all'),
				)),
				'OTHER_INDIVIDUAL' => array('GROUP' => 'OTHER', 'SORT' => 0, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN, 'MAP' => array(
					1 => array('PROPERTY', 1),
				)),
				'OTHER_ENTITY'     => array('GROUP' => 'OTHER', 'SORT' => 0, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array(
					2 => array('PROPERTY', 8),
				)),
			),
		)));

		var_dump(\Bitrix\Sale\BusinessValue::install('COMMON', null, array(
			'PERSON_DOMAIN' => array(
				1 => BusinessValue::INDIVIDUAL_DOMAIN,
				2 => BusinessValue::ENTITY_DOMAIN,
			),
			'GROUPS' => array(
				'COMMON' => array('SORT' => 2),
			),
			'CODES' => array(
				'BASIC_COMMON'     => array('GROUP' => 'COMMON', 'SORT' => 0, 'DOMAIN' => BusinessValue::COMMON_DOMAIN),
				'OTHER_COMMON'     => array('GROUP' => 'COMMON', 'SORT' => 0, 'DOMAIN' => BusinessValue::COMMON_DOMAIN),
				'OTHER_COMMON_ALL' => array('GROUP' => 'COMMON', 'SORT' => 0, 'DOMAIN' => BusinessValue::COMMON_DOMAIN),
			),
		)));
	}

	static function testInstallPaysystems()
	{
		var_dump(BusinessValue::install('PAYSYSTEM_WEBMONEY', '/bitrix/modules/sale/lib/payment_system_webmoney.php', array(
			'GROUPS' => array(
				'ORDER'    => array('SORT' => 400),
				'WEBMONEY' => array('SORT' => 500),
			),
			'CODES' => array(
				'ORDER_NUMBER' => array('GROUP' => 'ORDER', 'SORT' => 100, 'MAP' => array('COMMON' => array('ORDER', 'ACCOUNT_NUMBER'))),
				'ORDER_SUM'    => array('GROUP' => 'ORDER', 'SORT' => 200, 'MAP' => array('COMMON' => array('ORDER', 'PRICE'))),
				'WEBMONEY_WALLET_NUMBER' => array('GROUP' => 'WEBMONEY', 'SORT' => 100),
			),
		)));

		var_dump(BusinessValue::install('PAYSYSTEM_SBERBANK', '/bitrix/modules/sale/lib/payment_system_sberbank.php', array(
			'GROUPS' => array(
				'COMPANY' => array('SORT' => 100),
				'CLIENT'  => array('SORT' => 200),
			),
			'CODES' => array(
				'COMPANY_INN'               => array('GROUP' => 'COMPANY', 'SORT' => 100, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array(2 => array('PROPERTY', 10))),
				'COMPANY_KPP'               => array('GROUP' => 'COMPANY', 'SORT' => 200, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN),
				'COMPANY_NAME'              => array('GROUP' => 'COMPANY', 'SORT' => 300, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN, 'MAP' => array(2 => array('COMPANY', 'NAME'))),
				'COMPANY_BANK_BIK'          => array('GROUP' => 'COMPANY', 'SORT' => 400, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN),
				'COMPANY_BANK_NAME'         => array('GROUP' => 'COMPANY', 'SORT' => 500, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN),
				'COMPANY_BANK_CITY'         => array('GROUP' => 'COMPANY', 'SORT' => 600, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN),
				'COMPANY_BANK_ACCOUNT'      => array('GROUP' => 'COMPANY', 'SORT' => 700, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN),
				'COMPANY_BANK_CORR_ACCOUNT' => array('GROUP' => 'COMPANY', 'SORT' => 800, 'DOMAIN' => BusinessValue::ENTITY_DOMAIN),
				'CLIENT_NAME' => array('GROUP' => 'CLIENT', 'SORT' =>  100, 'DOMAIN' => BusinessValue::INDIVIDUAL_DOMAIN),
			),
		)));
	}
}

/* Example codes for SELLER_* and BUYER_*

	'SELLER_COMPANY_NAME'                      => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_NAME_SHORT'                => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_NAME_CONTACT'              => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_DIRECTOR_NAME'             => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_DIRECTOR_POSITION'         => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_DIRECTOR_PHONE'            => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ACCOUNTANT_NAME'           => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ACCOUNTANT_POSITION'       => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ACCOUNTANT_PHONE'          => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_PHONE'                     => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_FAX'                       => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_EMAIL'                     => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_WEBSITE'                   => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_LOCATION'                  => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ADDRESS'                   => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ADDRESS_LEGAL'             => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ADDRESS_POSTAL'            => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_ZIP'                       => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_COUNTRY'                   => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_REGION'                    => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_STATE'                     => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_TOWN'                      => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_CITY'                      => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_STREET'                    => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_HOUSING'                   => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BUILDING'                  => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_APARTMENT'                 => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_INN'                       => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_KPP'                       => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BANK_BIK'                  => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BANK_NAME'                 => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BANK_CITY'                 => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BANK_ACCOUNT'              => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BANK_ACCOUNT_CORR'         => array('GROUP' => 'SELLER_COMPANY'),
	'SELLER_COMPANY_BANK_SWIFT'                => array('GROUP' => 'SELLER_COMPANY'),
	'BUYER_PERSON_NAME'                        => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_NAME_LAST'                   => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_NAME_FIRST'                  => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_NAME_SECOND'                 => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_NAME_CONTACT'                => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_NAME_RECIPIENT'              => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_PHONE'                       => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_FAX'                         => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_EMAIL'                       => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_WEBSITE'                     => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_LOCATION'                    => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_COUNTRY'                     => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_REGION'                      => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_CITY'                        => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_ZIP'                         => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_ADDRESS'                     => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_ADDRESS_POSTAL'              => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_INN'                         => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_KPP'                         => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BANK_BIK'                    => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BANK_NAME'                   => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BANK_CITY'                   => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BANK_ACCOUNT'                => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BANK_ACCOUNT_CORR'           => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BANK_SWIFT'                  => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_PROFESSION'                  => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_BIRTHDAY'                    => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_SEX'                         => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_ICQ'                         => array('GROUP' => 'BUYER_PERSON'),
	'BUYER_PERSON_COMPANY_DEPARTMENT'          => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_POSITION'            => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_NAME'                => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_NAME_SHORT'          => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_NAME_CONTACT'        => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_DIRECTOR_NAME'       => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_DIRECTOR_POSITION'   => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_DIRECTOR_PHONE'      => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ACCOUNTANT_NAME'     => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ACCOUNTANT_POSITION' => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ACCOUNTANT_PHONE'    => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_PHONE'               => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_FAX'                 => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_EMAIL'               => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_WEBSITE'             => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_LOCATION'            => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_COUNTRY'             => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_REGION'              => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_CITY'                => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ZIP'                 => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ADDRESS'             => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ADDRESS_LEGAL'       => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_ADDRESS_POSTAL'      => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_INN'                 => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_KPP'                 => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_BANK_BIK'            => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_BANK_NAME'           => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_BANK_CITY'           => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_BANK_ACCOUNT'        => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_BANK_ACCOUNT_CORR'   => array('GROUP' => 'BUYER_PERSON_COMPANY'),
	'BUYER_PERSON_COMPANY_BANK_SWIFT'          => array('GROUP' => 'BUYER_PERSON_COMPANY'),

	// TODO make better translation for ORDER
	'ORDER_ID'            => array('GROUP' => 'ORDER'),
	'ORDER_NUMBER'        => array('GROUP' => 'ORDER'),
	'ORDER_DATE_CREATE'   => array('GROUP' => 'ORDER'),
	'ORDER_DATE_MODIFY'   => array('GROUP' => 'ORDER'),
	'ORDER_PAY_BEFORE'    => array('GROUP' => 'ORDER'),
	'ORDER_PRICE'         => array('GROUP' => 'ORDER'),
	'ORDER_CURRENCY'      => array('GROUP' => 'ORDER'),
	'ORDER_SUM'           => array('GROUP' => 'ORDER'),
	'ORDER_SITE_ID'       => array('GROUP' => 'ORDER'),
	'ORDER_PRICE_DELIV'   => array('GROUP' => 'ORDER'),
	'ORDER_DESCOUNT'      => array('GROUP' => 'ORDER'),
	'ORDER_USER_ID'       => array('GROUP' => 'ORDER'),
	'ORDER_PAY_SYSTEM_ID' => array('GROUP' => 'ORDER'),
	'ORDER_DELIVERY_ID'   => array('GROUP' => 'ORDER'),
	'ORDER_TAX'           => array('GROUP' => 'ORDER'),

*/
