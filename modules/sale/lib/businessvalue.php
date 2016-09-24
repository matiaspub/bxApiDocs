<?php

namespace Bitrix\Sale;

use Bitrix\Main\Type\Date;
use Bitrix\Sale\Internals\BusinessValueTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class BusinessValue
{
	const ENTITY_DOMAIN     = 'E';
	const INDIVIDUAL_DOMAIN = 'I';

	private static $redefinedFields = array();
	private static $consumers = array();
	private static $consumerCodePersonMapping = array();

	/** @deprecated */
	public static function getValueFromProvider(IBusinessValueProvider $provider = null, $codeKey, $consumerKey)
	{
		return self::get($codeKey, $consumerKey, $provider);
	}

	/**
	 * @param array $fields
	 */
	public static function redefineProviderField(array $fields)
	{
		self::$redefinedFields = array_merge_recursive(self::$redefinedFields, $fields);
	}

	/** Get business value.
	 * @param string $codeKey
	 * @param string|null $consumerKey
	 * @param IBusinessValueProvider|string|integer|null $personTypeId
	 * @param mixed $providerInstance
	 * @return mixed
	 */
	
	/**
	* <p>Метод возвращает значение бизнес-смысла. Метод статический.</p>
	*
	*
	* @param string $codeKey  Код параметра.
	*
	* @param string $string  Код сущности, для которой получаем значение.
	*
	* @param null $consumerKey = null Тип плательщика.
	*
	* @param mixed $Bitrix  Объект, реализующий интерфейс <code>IBusinessValueProvider</code>.
	*
	* @param Bitri $Sale  
	*
	* @param Sal $IBusinessValueProvider  
	*
	* @param IBusinessValueProvide $string  
	*
	* @param strin $integer  
	*
	* @param null $personTypeId = null 
	*
	* @param mixed $providerInstance = null 
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/businessvalue/get.php
	* @author Bitrix
	*/
	public static function get($codeKey, $consumerKey = null, $personTypeId = null, $providerInstance = null)
	{
		$value = null;

		if ($personTypeId instanceof IBusinessValueProvider)
		{
			$provider = $personTypeId;
			$personTypeId = $provider->getPersonTypeId();
			$mapping = self::getMapping($codeKey, $consumerKey, $personTypeId, array('GET_VALUE' => array('PROPERTY' => 'BY_ID')));
			$providerInstance = $provider->getBusinessValueProviderInstance($mapping);
		}
		else
		{
			if ($personTypeId <= 0)
				$personTypeId = null;
			$mapping = self::getMapping($codeKey, $consumerKey, $personTypeId, array('GET_VALUE' => array('PROPERTY' => 'BY_ID')));
		}

		if (isset(self::$redefinedFields[$mapping['PROVIDER_KEY']][$mapping['PROVIDER_VALUE']]))
		{
			return self::$redefinedFields[$mapping['PROVIDER_KEY']][$mapping['PROVIDER_VALUE']];
		}

		if ($mapping['PROVIDER_KEY'] && $mapping['PROVIDER_VALUE'])
		{
			switch ($mapping['PROVIDER_KEY'])
			{
				case 'VALUE':
				case 'INPUT':
					$value = $mapping['PROVIDER_VALUE'];
					break;

				default:
					if (($providers = self::getProviders())
						&& ($provider = $providers[$mapping['PROVIDER_KEY']])
						&& is_array($provider)
						&& is_callable($provider['GET_INSTANCE_VALUE'])
						&& ($v = call_user_func($provider['GET_INSTANCE_VALUE'], $providerInstance, $mapping['PROVIDER_VALUE'], $personTypeId)))
					{
						$value = $v;
					}
			}
		}

		return $value;
	}

	/** Check if mapping has been set.
	 * @param string $codeKey
	 * @param string|null $consumerKey
	 * @param string|integer|null $personTypeId
	 * @return bool
	 */
	
	/**
	* <p>Метод, проверяющий задано ли для поля соответствие (карта). Метод статический.</p>
	*
	*
	* @param string $codeKey  Код поля.
	*
	* @param string $string  Код сущности, для которой получаем значение.
	*
	* @param null $consumerKey = null Тип плательщика.
	*
	* @param mixed $string  
	*
	* @param strin $integer  
	*
	* @param null $personTypeId = null 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/businessvalue/issetmapping.php
	* @author Bitrix
	*/
	public static function isSetMapping($codeKey, $consumerKey = null, $personTypeId = null)
	{
		$codeKey = ToUpper($codeKey);
		return isset(self::$consumerCodePersonMapping[$consumerKey][$codeKey][$personTypeId]);
	}

	const MATCH_ALL     = -1;
	const MATCH_EXACT   =  1;
	const MATCH_COMMON  =  2;
	const MATCH_DEFAULT =  4;

	/** Get mapping.
	 * @param string $codeKey
	 * @param string|null $consumerKey
	 * @param string|integer|null $personTypeId
	 * @param array $options
	 * @return array with PROVIDER_KEY and PROVIDER_VALUE if mapping was found, or empty array
	 */
	
	/**
	* <p>Метод возвращает карту настройки (карту) для поля сущности. Метод статический.</p>
	*
	*
	* @param string $codeKey  Код поля.
	*
	* @param string $string  Код сущности, для которой получаем значение.
	*
	* @param null $consumerKey = null Тип плательщика.
	*
	* @param mixed $string  Массив опций.
	*
	* @param strin $integer  
	*
	* @param null $personTypeId = null 
	*
	* @param array $options = array() 
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/businessvalue/getmapping.php
	* @author Bitrix
	*/
	public static function getMapping($codeKey, $consumerKey = null, $personTypeId = null, array $options = array())
	{
		$mapping = array();
		$codeKeyUp = ToUpper($codeKey);

		$match = is_int($options['MATCH']) ? $options['MATCH'] : self::MATCH_ALL;

		$consumerCodePersonMapping = is_array($options['consumerCodePersonMapping']) // internal, do not use!
			? $options['consumerCodePersonMapping']
			: self::getConsumerCodePersonMapping();

		if ($match & self::MATCH_EXACT && isset($consumerCodePersonMapping[$consumerKey][$codeKeyUp][$personTypeId]))
		{
			$mapping = $consumerCodePersonMapping[$consumerKey][$codeKeyUp][$personTypeId];
		}
		else
		{
			if ($match & self::MATCH_COMMON)
			{
				if ($personTypeId && isset($consumerCodePersonMapping[$consumerKey][$codeKeyUp]['']))
					$mapping = $consumerCodePersonMapping[$consumerKey][$codeKeyUp][''];
				elseif ($consumerKey && isset($consumerCodePersonMapping[''][$codeKeyUp][$personTypeId]))
					$mapping = $consumerCodePersonMapping[''][$codeKeyUp][$personTypeId];
				elseif ($consumerKey && $personTypeId && isset($consumerCodePersonMapping[''][$codeKeyUp]['']))
					$mapping = $consumerCodePersonMapping[''][$codeKeyUp][''];
			}

			if (! $mapping && $match & self::MATCH_DEFAULT && ($consumers = self::getConsumers()))
			{
				if (is_array($consumers[$consumerKey]['CODES'][$codeKey]['DEFAULT']))
				{
					$mapping = $consumers[$consumerKey]['CODES'][$codeKey]['DEFAULT'];
				}
//				elseif ($consumerKey && is_array($consumers['']['CODES'][$codeKey]['DEFAULT']))
//				{
//					$mapping = $consumers['']['CODES'][$codeKey]['DEFAULT'];
//				}
			}
		}

		// TODO universal approach maybe later
		if ($mapping
			&& $mapping['PROVIDER_KEY'] == 'PROPERTY'
			&& $mapping['PROVIDER_VALUE']
			&& ($providers = BusinessValue::getProviders())
			&& is_callable($providers['PROPERTY']['GET_VALUE']))
		{
 			$mapping['PROVIDER_VALUE'] = call_user_func($providers['PROPERTY']['GET_VALUE']
				, $mapping['PROVIDER_VALUE']
				, $personTypeId
				, isset($options['GET_VALUE']['PROPERTY']) ? $options['GET_VALUE']['PROPERTY'] : null
			);
		}

		return $mapping;
	}

	/** Set mapping.
	 * @param string $codeKey
	 * @param string|null $consumerKey
	 * @param string|integer|null $personTypeId
	 * @param array $mapping
	 * @param bool $withCommon internal, do not use!
	 * @return \Bitrix\Main\Entity\Result
	 * @throws \Exception
	 */
	
	/**
	* <p>Метод, устанавливающий карту для поля (кода) сущности. Метод статический.</p>
	*
	*
	* @param string $codeKey  Код поля.
	*
	* @param string $string  Код сущности, для которой получаем значение.
	*
	* @param null $consumerKey  Тип плательщика.
	*
	* @param null $string  Соответствие (карта).
	*
	* @param strin $integer  Флаг, определяющий, использовать ли соответствие как базовое,
	* если для этого поля оно еще не определено. Только для внутреннего
	* использования.
	*
	* @param null $personTypeId  
	*
	* @param array $mapping  
	*
	* @param boolean $withCommon = false 
	*
	* @return \Bitrix\Main\Entity\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/businessvalue/setmapping.php
	* @author Bitrix
	*/
	public static function setMapping($codeKey, $consumerKey, $personTypeId, array $mapping, $withCommon = false)
	{
		$codeKey = ToUpper($codeKey);
		if (! $consumerKey || $consumerKey === BusinessValueTable::COMMON_CONSUMER_KEY)
			$consumerKey = null;

		if (! $personTypeId || $personTypeId === BusinessValueTable::COMMON_PERSON_TYPE_ID)
			$personTypeId = null;

		if (!array_key_exists('PROVIDER_KEY', $mapping) || !array_key_exists('PROVIDER_VALUE', $mapping))
			$mapping = array();

		$primary = array(
			'CODE_KEY'       => $codeKey,
			// TODO remove save_data_modification hack
			'CONSUMER_KEY'   => $consumerKey ?: BusinessValueTable::COMMON_CONSUMER_KEY,
			'PERSON_TYPE_ID' => $personTypeId ?: BusinessValueTable::COMMON_PERSON_TYPE_ID,
		);

		$consumerCodePersonMapping = self::getConsumerCodePersonMapping();

		if (isset($consumerCodePersonMapping[$consumerKey][$codeKey][$personTypeId]))
		{
			if ($mapping)
			{
				$savedMapping = $consumerCodePersonMapping[$consumerKey][$codeKey][$personTypeId];

				if (! ($mapping['PROVIDER_KEY'] == $savedMapping['PROVIDER_KEY']
					&& $mapping['PROVIDER_VALUE'] == $savedMapping['PROVIDER_VALUE']))
					$result = BusinessValueTable::update($primary, $mapping);
			}
			else
			{
				$result = BusinessValueTable::delete($primary);
			}
		}
		elseif ($mapping)
		{
			if ($withCommon
				&& $consumerKey
				&& !isset($consumerCodePersonMapping[''][$codeKey][''])
			)
			{
				$consumerKey = null;
				$personTypeId = null;
				$primary['CONSUMER_KEY'] = BusinessValueTable::COMMON_CONSUMER_KEY;
				$primary['PERSON_TYPE_ID'] = BusinessValueTable::COMMON_PERSON_TYPE_ID;
			}

			$result = BusinessValueTable::add($primary + $mapping);
		}

		if (isset($result))
		{
			if ($result->isSuccess())
			{
				if ($mapping)
					self::$consumerCodePersonMapping[$consumerKey][$codeKey][$personTypeId] = $mapping;
				else
					unset(self::$consumerCodePersonMapping[$consumerKey][$codeKey][$personTypeId]);
			}
		}
		else
		{
			$result = new \Bitrix\Main\Entity\Result;
		}

		return $result;
	}

	/** @internal do not use! */
	public static function getConsumerCodePersonMapping()
	{
		if (! self::$consumerCodePersonMapping)
		{
			$result = BusinessValueTable::getList(array(
				'select' => array('CODE_KEY', 'CONSUMER_KEY', 'PERSON_TYPE_ID', 'PROVIDER_KEY', 'PROVIDER_VALUE'),
				// !!! if filter is set, than setMapping must evaluate personTypeId's for codes, otherwise it will throw duplicate error!!!
				//'filter' => array('=PERSON_TYPE_ID' => array_merge(array(BusinessValueTable::COMMON_PERSON_TYPE_ID), array_keys(self::getPersonTypes())),
			));

			while ($row = $result->fetch())
			{
				$row['CODE_KEY'] = ToUpper($row['CODE_KEY']);
				// TODO delete mappings for non existent code:consumer:person from db
				// TODO optimize memory usage $consumerCodePersonMapping >> $personConsumerCodeMapping
				self::$consumerCodePersonMapping[$row['CONSUMER_KEY']][$row['CODE_KEY']][$row['PERSON_TYPE_ID']] = array(
					'PROVIDER_KEY'   => $row['PROVIDER_KEY'],
					'PROVIDER_VALUE' => $row['PROVIDER_VALUE'],
				);
			}
		}

		return self::$consumerCodePersonMapping;
	}

	/**
	 * Get all registered providers.
	 * @return array
	 * @throws SystemException
	 */
	
	/**
	* <p>Метод возвращает список всех зарегистрированных провайдеров. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/businessvalue/getproviders.php
	* @author Bitrix
	*/
	public static function getProviders()
	{
		$providers = BusinessValueHandlers::getProviders();

		foreach (EventManager::getInstance()->findEventHandlers('sale', 'OnGetBusinessValueProviders') as $handler)
		{
			$result = ExecuteModuleEventEx($handler);

			if (is_array($result))
			{
				foreach ($result as $providerKey => $provider)
				{
					if ($providerKey && is_array($provider))
					{
						if (isset($providers[$providerKey]))
							throw new SystemException('duplicate provider key '.$providerKey);

						$providers[$providerKey] = $provider;
					}
				}
			}
		}

		self::sortArray($providers);
		return $providers;
	}

	private static $commonConsumer = array();

	/** Get all registered consumers.
	 * @return array
	 * @throws SystemException
	 */
	
	/**
	* <p>Метод возвращает все зарегистрированные сущности. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/businessvalue/getconsumers.php
	* @author Bitrix
	*/
	public static function getConsumers()
	{
		if (! self::$consumers)
		{
			self::$commonConsumer = array(
				'NAME'  => Loc::getMessage('BIZVAL_CONSUMER_COMMON'),
				'SORT'  => 0,
				'CODES' => array(),
			);

			self::$consumers[''] =& self::$commonConsumer;

			foreach (EventManager::getInstance()->findEventHandlers('sale', 'OnGetBusinessValueConsumers') as $handler)
			{
//				// TODO remove
//				if (! ($handler['TO_NAME'] == 'Bitrix\Sale\BusinessValueHandlers::getConsumers'
////					|| $handler['TO_NAME'] == 'Bitrix\Sale\BusinessValueConsumer1C::getConsumers'
//				))
//					continue;

				$result = &ExecuteModuleEventEx($handler); // by ref! // TODO modern api

				if (is_array($result))
				{
					foreach ($result as $consumerKey => &$consumer) // by ref!
					{
						if ($consumerKey && is_array($consumer))
						{
							self::addConsumerByRef($consumerKey, $consumer);
						}
					}
				}
			}
			self::sortArray(self::$commonConsumer['CODES']);

			self::sortArray(self::$consumers);

//			if (self::$commonConsumer['CODES'] &&
//				(self::$commonConsumer['CODES'] = array_filter(
//					self::$commonConsumer['CODES'],
//					function ($i) {return count($i['CONSUMERS']) > 1 && (! is_array($i['DOMAINS']) || $i['DOMAINS']);}
//				)))
//			{}
		}

		return self::$consumers;
	}

	private static function addConsumerByRef($consumerKey, array &$consumer) // by ref!
	{
		if (isset(self::$consumers[$consumerKey]) || $consumerKey == BusinessValueTable::COMMON_CONSUMER_KEY)
			throw new SystemException('duplicate consumer key '.$consumerKey);

//		// TODO remove hack to paysytem
//		if (substr($consumerKey, 0, 10) == 'PAYSYSTEM_')
//			$consumer['PROVIDERS'] = array('VALUE', 'COMPANY', 'ORDER', 'USER', 'PROPERTY', 'PAYMENT');

		if (is_array($consumer['CODES']))
		{
			$codes =& self::$commonConsumer['CODES'];

			foreach ($consumer['CODES'] as $codeKey => $code)
			{
				if ($codeKey && is_array($code) && ! $code['NOT_COMMON'])
				{
					if ($c =& $codes[$codeKey])
					{
						if (! $c['GROUP'] && $code['GROUP'])
							$c['GROUP'] = $code['GROUP'];

						if (! isset($c['SORT']) && isset($code['SORT']))
							$c['SORT'] = $code['SORT'];

						if (! is_array($c['DEFAULT']) && is_array($code['DEFAULT']))
							$c['DEFAULT'] = $code['DEFAULT'];

						if (is_array($code['DOMAINS']))
						{
							$c['DOMAINS'] = is_array($c['DOMAINS'])
								? array_intersect($c['DOMAINS'], $code['DOMAINS'])
								: $code['DOMAINS'];
						}

						if (! is_array($code['PROVIDERS']))
							$code['PROVIDERS'] = $consumer['PROVIDERS'];

						if (is_array($code['PROVIDERS']))
						{
							$c['PROVIDERS'] = is_array($c['PROVIDERS'])
								? array_intersect($c['PROVIDERS'], $code['PROVIDERS'])
								: $code['PROVIDERS'];
						}

						$c['NAMES'][$code['NAME'] ?: $codeKey] = 1;

//						if (! $c['NAME'] && $code['NAME'])
//							$c['NAME'] = $code['NAME'];
					}
					else
					{
						$c = $code;
						$c['PROVIDERS'] = $code['PROVIDERS'] ?: $consumer['PROVIDERS'];
						$c['NAMES'][$code['NAME'] ?: $codeKey] = 1;
					}

					$c['CONSUMERS'] []= $consumer['NAME'] ?: $consumerKey;
				}
			}

			self::sortArray($consumer['CODES']);
		}

		self::$consumers[$consumerKey] = & $consumer; // by ref!
	}

	public static function addConsumer($consumerKey, array $consumer)
	{
		if (! $consumerKey)
			throw new SystemException('Empty consumer key!');

		if (! is_array($consumer))
			throw new SystemException('Invalid consumer!');

		self::getConsumers(); // preload consumers
		self::addConsumerByRef($consumerKey, $consumer);
	}

	public static function changeConsumer($consumerKey, array $consumer)
	{
		if (! $consumerKey)
			throw new SystemException('Empty consumer key!');

		self::getConsumers(); // preload consumers

		if (! isset(self::$consumers[$consumerKey]))
			throw new SystemException('There is no consumer "'.$consumerKey.'" to change!');

		self::$consumers[$consumerKey] = $consumer;
	}

	public static function changeConsumerKey($fromConsumerKey, $toConsumerKey)
	{
		if (! ($fromConsumerKey && $toConsumerKey))
			throw new SystemException('Empty consumer key!');

		if ($fromConsumerKey == $toConsumerKey)
			return;

		self::getConsumers(); // preload consumers

		if (! isset(self::$consumers[$fromConsumerKey]))
			throw new SystemException('There is no consumer "'.$fromConsumerKey.'" to change key!');

		if (isset(self::$consumers[$toConsumerKey]))
			throw new SystemException('Cannot change to consumer key "'.$toConsumerKey.'", as it already exists!');

		// TODO change common consumer code['CONSUMERS']
		self::$consumers[$toConsumerKey] = & self::$consumers[$fromConsumerKey]; // by ref!
		unset(self::$consumers[$fromConsumerKey]);
	}

	public static function getGroups()
	{
		static $groups = array();

		if (! $groups)
		{
			foreach (EventManager::getInstance()->findEventHandlers('sale', 'OnGetBusinessValueGroups') as $handler)
			{
				$result = ExecuteModuleEventEx($handler); // TODO modern api

				if (is_array($result))
				{
					foreach ($result as $groupKey => $group)
					{
						if ($groupKey && is_array($group) && ! $groups[$groupKey])
						{
							$groups[$groupKey] = $group;
						}
					}
				}
			}

			self::sortArray($groups);
		}

		return $groups;
	}

	/** @internal */
	private static function sortArray(array &$data)
	{
		@uasort( // @ because https://bugs.php.net/bug.php?id=50688
			$data,
			function ($aa, $bb) // $aa & $bb - because php before 5.4 passes by reference
			{
				$a = is_array($aa) ? (int) $aa['SORT'] : 0;
				$b = is_array($bb) ? (int) $bb['SORT'] : 0;
				return $a < $b ? -1 : ($a > $b ? 1 : 0);
			}
		);
	}

	/** @internal */
	public static function getPersonTypes($all = false, array $resetAllPersonTypes = null)
	{
		static $allPersonTypes = array(), $personTypes = array();

		if ($resetAllPersonTypes !== null)
		{
			$allPersonTypes = $resetAllPersonTypes;
			$personTypes = array_filter($allPersonTypes, function ($i) {return $i['DOMAIN'];});
		}
		elseif (! $allPersonTypes)
		{
			// TODO check what to do with ACTIVE
			$result = Internals\PersonTypeTable::getList(array(
				'select'  => array('ID', 'NAME', 'LID', 'ACTIVE', 'DOMAIN' => 'BIZVAL.DOMAIN'),
				'order'   => array('LID', 'SORT', 'NAME'),
				'runtime' => array(
					new \Bitrix\Main\Entity\ReferenceField(
						'BIZVAL',
						'Bitrix\Sale\Internals\BusinessValuePersonDomainTable',
						array('=this.ID' => 'ref.PERSON_TYPE_ID'),
						array('join_type' => 'LEFT')
					),
				),
			));

			while ($row = $result->fetch())
			{
				$row['TITLE'] = $row['NAME'].' ('.$row['LID'].')';

				$rowId = $row['ID'];

				$allPersonTypes[$rowId] = $row;

				if ($row['DOMAIN'])
					$personTypes[$rowId] = $row;
			}
		}

		return $all ? $allPersonTypes : $personTypes;
	}

	// DEPRECATED API //////////////////////////////////////////////////////////////////////////////////////////////////

	/** @deprecated */
	public static function install($parentName, $translationSource, $data)
	{
	}

	/** @deprecated */
	const COMMON_PERSON_ID  = 0; // person type id
	/** @deprecated */
	const COMMON_DOMAIN     = 'C';
}

/** @deprecated */
class BusinessValueHandlers
{
	public static function getProviders()
	{
		return array(
			'VALUE' => array(
				'NAME'      => Loc::getMessage('BIZVAL_PROVIDER_VALUE'),
				'SORT'      => 100,
			),
			'COMPANY' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_COMPANY'),
				'SORT'   => 600,
				'FIELDS' => call_user_func(function ()
				{
					$data = array(
						'ID'          => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_ID')),
						'NAME'        => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_NAME'       )),
						'LOCATION_ID' => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_LOCATION'   )),
						'CODE'        => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_CODE'       )),
						'XML_ID'      => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_XML_ID'     )),
						'ACTIVE'      => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_ACTIVE'     )),
						'DATE_CREATE' => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_DATE_CREATE')),
						'DATE_MODIFY' => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_DATE_MODIFY')),
						'CREATED_BY'  => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_CREATED_BY' )),
						'MODIFIED_BY' => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_MODIFIED_BY')),
						'ADDRESS'     => array('GROUP' => 'ENTITY', 'NAME' => Loc::getMessage('BIZVAL_CODE_ADDRESS'    )),
					);

					global $USER_FIELD_MANAGER;
					$result = $USER_FIELD_MANAGER->GetUserFields(Internals\CompanyTable::getUfId(), null, LANGUAGE_ID);

					foreach ($result as $name => $row)
						$data[$name] = array('NAME' => $row['EDIT_FORM_LABEL'] ?: $name, 'GROUP' => 'UF');

					return $data;
				}),
				'FIELDS_GROUPS' => array(
					'ENTITY' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CODE_COMPANY_ENTITY')),
					'UF'     => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CODE_COMPANY_UF'    )),
				),
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					$value = null;

					if ($providerInstance === null)
						return $value;

					if (substr($providerValue, 0, 3) == 'UF_')
					{
						global $USER_FIELD_MANAGER;
						$value = $USER_FIELD_MANAGER->GetUserFieldValue(Internals\CompanyTable::getUfId(), $providerValue, $providerInstance) ?: null;
					}
					elseif ($company = Internals\CompanyTable::getById($providerInstance)->fetch())
					{
						$value = $company[$providerValue];
					}

					return $value;
				},
			),
			'ORDER' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_ORDER'),
				'SORT'   => 200,
				'FIELDS' => array(
					'ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_ID')),
					'LID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_SITE_ID')),
					'ACCOUNT_NUMBER' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_ACCOUNT_NUMBER')),
					'TRACKING_NUMBER' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_TRACKING_NUMBER')),
					'DATE_INSERT' => array('NAME' => Loc::getMessage('BIZVAL_CODE_DATE_CREATE')),
					'DATE_INSERT_DATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_DATE_CREATE_DATE')),
					'DATE_UPDATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_DATE_MODIFY')),
					'PERSON_TYPE_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PERSON_TYPE_ID')),
					'USER_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_USER_ID')),
					'PAYED' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAID')),
					'DATE_PAY_BEFORE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PAY_BEFORE')),
					'SHOULD_PAY' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PRICE')),
					'CURRENCY' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_CURRENCY')),
					'PRICE_DELIVERY' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PRICE_DELIV')),
					'DISCOUNT_VALUE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_DESCOUNT')),
					'PAY_SYSTEM_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_PAY_SYSTEM_ID')),
					'DELIVERY_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_DELIVERY_ID')),
					'TAX_VALUE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_TAX')),
					'COMMENTS' => array('NAME' => Loc::getMessage('BIZVAL_CODE_ORDER_COMMENTS')),
				),
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					$value = null;

					if ($providerInstance instanceof Order)
					{
						if ($providerValue == 'DATE_INSERT_DATE')
							$value = new Date($providerInstance->getField('DATE_INSERT'));
						else if ($providerValue == 'DATE_BILL_DATE') // for crm compatibility
							$value = new Date($providerInstance->getField('DATE_BILL'));
						else
							$value = $providerInstance->getField($providerValue);
					}

					return $value;
				},
			),
			'USER' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_USER'),
				'SORT'   => 110,
				'FIELDS' => array(
					'ID'                  => array('NAME' => Loc::getMessage('BIZVAL_CODE_USER_ID'), 'GROUP' => 'CLIENT'),
					'LOGIN'               => array('NAME' => Loc::getMessage('BIZVAL_CODE_LOGIN'), 'GROUP' => 'CLIENT'),
					'NAME'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_FIRST_NAME'), 'GROUP' => 'CLIENT'),
					'SECOND_NAME'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SECOND_NAME'), 'GROUP' => 'CLIENT'),
					'LAST_NAME'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_LAST_NAME'), 'GROUP' => 'CLIENT'),
					'EMAIL'               => array('NAME' => Loc::getMessage('BIZVAL_CODE_EMAIL'), 'GROUP' => 'CLIENT'),
					'LID'                 => array('NAME' => Loc::getMessage('BIZVAL_CODE_SITE_ID'), 'GROUP' => 'CLIENT'),
					'PERSONAL_PROFESSION' => array('NAME' => Loc::getMessage('BIZVAL_CODE_JOB_PROFESSION'), 'GROUP' => 'CLIENT'),
					'PERSONAL_WWW'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_WEBSITE'), 'GROUP' => 'CLIENT'),
					'PERSONAL_ICQ'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_ICQ'), 'GROUP' => 'CLIENT'),
					'PERSONAL_GENDER'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_GENDER'), 'GROUP' => 'CLIENT'),
					'PERSONAL_FAX'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_FAX'), 'GROUP' => 'CLIENT'),
					'PERSONAL_MOBILE'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_PHONE'), 'GROUP' => 'CLIENT'),
					'PERSONAL_STREET'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_ADDRESS'), 'GROUP' => 'CLIENT'),
					'PERSONAL_MAILBOX'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_POSTAL_ADDRESS'), 'GROUP' => 'CLIENT'),
					'PERSONAL_CITY'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_CITY'), 'GROUP' => 'CLIENT'),
					'PERSONAL_STATE'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_REGION'), 'GROUP' => 'CLIENT'),
					'PERSONAL_ZIP'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_ZIP'), 'GROUP' => 'CLIENT'),
					'PERSONAL_COUNTRY'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_COUNTRY'), 'GROUP' => 'CLIENT'),
					'WORK_COMPANY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_NAME'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_DEPARTMENT'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_JOB_DEPARTMENT'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_POSITION'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_JOB_POSITION'  ), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_WWW'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_WEBSITE'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_PHONE'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_PHONE'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_FAX'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_FAX'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_STREET'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_ADDRESS'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_MAILBOX'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_POSTAL_ADDRESS'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_CITY'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_CITY'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_STATE'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_REGION'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_ZIP'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_ZIP'), 'GROUP' => 'CLIENT_COMPANY'),
					'WORK_COUNTRY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_COUNTRY'), 'GROUP' => 'CLIENT_COMPANY'),
				),
				'FIELDS_GROUPS' => array(
					'CLIENT' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CODE_CLIENT'        )),
					'CLIENT_COMPANY' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CODE_CLIENT_COMPANY')),
				),
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					$value = null;

					global $USER;

					if ($user = $USER->GetByID($providerInstance)->Fetch())
					{
						$value = $user[$providerValue];
					}

					return $value;
				},
			),
			'PAYMENT' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_PAYMENT'),
				'SORT'   => 400,
				'FIELDS' => array(
					'ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_ID')),
					'ACCOUNT_NUMBER' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_ACCOUNT_NUMBER')),
					'PAID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAID')),
					'DATE_PAID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_PAID')),
					'PAY_SYSTEM_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_SYSTEM_ID')),
					'PAY_VOUCHER_NUM' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_VOUCHER_NUM')),
					'PAY_VOUCHER_DATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_VOUCHER_DATE')),
					'DATE_PAY_BEFORE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_PAY_BEFORE')),
					'DATE_BILL' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_BILL')),
					'DATE_BILL_DATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_DATE_BILL_DATE')),
					'XML_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_XML_ID')),
					'SUM' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_SUM')),
					'CURRENCY' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_CURRENCY')),
					'PAY_SYSTEM_NAME' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_SYSTEM_NAME')),
					'COMPANY_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_COMPANY_ID')),
					'PAY_RETURN_NUM' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_RETURN_NUM')),
					'PAY_RETURN_DATE' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_RETURN_DATE')),
					'PAY_RETURN_COMMENT' => array('NAME' => Loc::getMessage('BIZVAL_CODE_PAYMENT_PAY_RETURN_COMMENT')),
				),
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					$value = null;

					if ($providerInstance instanceof Payment)
					{
						if ($providerValue == 'DATE_BILL_DATE')
							$value = new Date($providerInstance->getField('DATE_BILL'));
						else
							$value = $providerInstance->getField($providerValue);
					}
					return $value;
				},
			),
			'SHIPMENT' => array(
				'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_SHIPMENT'),
				'SORT'   => 500,
				'FIELDS' => array(
					'STATUS_ID'             => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_STATUS_ID')),
					'PRICE_DELIVERY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_PRICE_DELIVERY')),
					'ALLOW_DELIVERY'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_ALLOW_DELIVERY')),
					'DATE_ALLOW_DELIVERY'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_ALLOW_DELIVERY')),
					'EMP_ALLOW_DELIVERY_ID' => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_ALLOW_DELIVERY_ID')),
					'DEDUCTED'              => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DEDUCTED')),
					'DATE_DEDUCTED'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_DEDUCTED')),
					'EMP_DEDUCTED_ID'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_DEDUCTED_ID')),
					'REASON_UNDO_DEDUCTED'  => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_REASON_UNDO_DEDUCTED')),
					'DELIVERY_ID'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_ID')),
					'DELIVERY_DOC_NUM'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_DOC_NUM')),
					'DELIVERY_DOC_DATE'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_DOC_DATE')),
					'TRACKING_NUMBER'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_TRACKING_NUMBER')),
					'XML_ID'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_XML_ID')),
					'PARAMETERS'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_PARAMETERS')),
					'DELIVERY_NAME'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DELIVERY_NAME')),
					'COMPANY_ID'            => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_COMPANY_ID')),
					'MARKED'                => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_MARKED')),
					'DATE_MARKED'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_MARKED')),
					'EMP_MARKED_ID'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_MARKED_ID')),
					'REASON_MARKED'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_REASON_MARKED')),
					'CANCELED'              => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_CANCELED')),
					'DATE_CANCELED'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_DATE_CANCELED')),
					'EMP_CANCELED_ID'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_SHIPMENT_EMP_CANCELED_ID')),
				),
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					$value = null;

					if ($providerInstance instanceof Shipment)
						$value = $providerInstance->getField($providerValue);

					return $value;
				},
			),
			'PROPERTY' => call_user_func(
				function ()
				{
					$fields = call_user_func(
						function ()
						{
							$fields = array();

							$result = Internals\OrderPropsTable::getList(array(
								'select' => array('ID', 'NAME', 'PERSON_TYPE_ID', 'TYPE', 'CODE'),
								'filter' => array('=PERSON_TYPE_ID' => array_keys(BusinessValue::getPersonTypes())),
								'order'  => array('PERSON_TYPE_ID', 'SORT'),
							));

							while ($row = $result->fetch())
							{
								$id    = $row['ID'];
								$name  = $row['NAME'];
								$field = array(
									'NAME' => $name,
									'CODE' => $row['CODE'],
									'GROUP' => $row['PERSON_TYPE_ID'],
									'PERSON_TYPE_ID' => $row['PERSON_TYPE_ID']
								);

								$fields[$id] = $field;

								if ($row['TYPE'] == 'LOCATION')
								{
									$field['NAME'] = $name.' ('.Loc::getMessage('BIZVAL_CODE_COUNTRY').')';
									$fields[$id.'_COUNTRY'] = $field;

									$field['NAME'] = $name.' ('.Loc::getMessage('BIZVAL_CODE_REGION').')';
									$fields[$id.'_REGION'] = $field;

									$field['NAME'] = $name.' ('.Loc::getMessage('BIZVAL_CODE_CITY').')';
									$fields[$id.'_CITY'] = $field;
								}
							}

							return $fields;
						}
					);

					$parseId = function ($propertyId)
					{
						$propertyCode = null;
						$locationField = null;

						if (! is_numeric($propertyId)) // TODO is_int
						{
							if (($a = explode('_', $propertyId, 2))
								&& is_numeric($a[0])
								&& in_array($a[1], array('CITY', 'COUNTRY', 'REGION'), true))
							{
								$propertyId = $a[0];
								$locationField = $a[1];
							}
							else
							{
								$propertyCode = $propertyId;
								$propertyId = null;
							}
						}

						return array($propertyCode, $propertyId, $locationField);
					};

					return array(
						'NAME'   => Loc::getMessage('BIZVAL_PROVIDER_PROPERTY'),
						'SORT'   => 300,
						'FIELDS' => $fields,
						'FIELDS_GROUPS' => array_map(function ($i) {return array('NAME' => $i['TITLE']);}, BusinessValue::getPersonTypes()),
						'GET_VALUE' => function ($providerValue, $personTypeId, $options) use ($parseId, $fields)
						{
							list ($propertyCode, $propertyId, $locationField) = call_user_func($parseId, $providerValue);

							if ($propertyCode)
							{
								if ($options == 'BY_ID')
								{
									foreach ($fields as $propertyId => $property)
									{
										if ($property['PERSON_TYPE_ID'] == $personTypeId && $property['CODE'] == $propertyCode)
										{
											$providerValue = $propertyId;
											break;
										}
									}
								}
							}
							else
							{
								if ($options != 'BY_ID' && ($property = $fields[$propertyId]))
								{
									$providerValue = $property['CODE'];
								}
							}

							return $providerValue;
						},
						'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId) use ($parseId)
						{
							$value = null;

							if ($providerInstance instanceof Order)
							{
								list ($propertyCode, $propertyId, $locationField) = call_user_func($parseId, $providerValue);

								/** @var PropertyValue $property */
								foreach ($providerInstance->getPropertyCollection() as $property)
								{
									if ($property->getPersonTypeId() == $personTypeId)
									{
										if ($propertyCode)
										{
											if ($property->getField('CODE') == $propertyCode)
											{
												$value = $property->getValue();
												break;
											}
										}
										elseif ($property->getPropertyId() == $propertyId)
										{
											if (($value = $property->getValue())
												&& ($propertyField = $property->getProperty())
											)
											{
												if($propertyField['TYPE'] == "LOCATION")
												{
													$limit = -1;
													$filter['=CODE'] = $value;
													$filter['=PARENTS.NAME.LANGUAGE_ID'] = LANGUAGE_ID;
													if(is_set($locationField))
													{
														$filter['=PARENTS.TYPE.CODE'] = $locationField;
														$limit = 1;
													}
													$row = \Bitrix\Sale\Location\LocationTable::getList(array(
															'select' => array('LOCATION_NAME' => 'PARENTS.NAME.NAME'),
															'filter' => $filter,
															'limit' => $limit,
													));
													$locations  = array();
													while($location = $row->fetch())
														$locations[] = $location['LOCATION_NAME'];

													$value = count($locations)>0? implode('-',$locations):$value;
												}
												elseif($propertyField['TYPE'] == "ENUM")
												{
													$value = $propertyField['OPTIONS'][$property->getValue()];
												}
											}
											break;
										}
									}
								}
							}

							return $value;
						},
					);
				}
			),
		);
	}

	public static function getConsumers()
	{
		return array(
			/*'DEF' => array(
				'SORT' => 100,
				'CODES' => array(
//					'BASIC_INDIVIDUAL' => array('GROUP' => 'BASIC', 'SORT' => 10, 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
//					'BASIC_ENTITY'     => array('GROUP' => 'BASIC', 'SORT' => 20, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
					'DEFA' => array('GROUP' => 'BASIC', 'SORT' => 5),
					'DEF0' => array('GROUP' => 'BASIC', 'SORT' => 10, 'DEFAULT' => array('PROVIDER_KEY' => 'VALUE'   , 'PROVIDER_VALUE' => 'default')),
					'DEF1' => array('GROUP' => 'BASIC', 'SORT' => 20, 'DEFAULT' => array('PROVIDER_KEY' => 'PROPERTY', 'PROVIDER_VALUE' => '3'      )),
					'DEF2' => array('GROUP' => 'BASIC', 'SORT' => 30, 'DEFAULT' => array('PROVIDER_KEY' => 'PROPERTY', 'PROVIDER_VALUE' => 'EMAIL'  )),
					'DEFF' => array('GROUP' => 'BASIC', 'SORT' => 50, 'INPUT'   => array('TYPE' => 'FILE')),
				),
			),
			'OTHER' => array(
				'SORT' => 200,
				'CODES' => array(
					'OTHER'       => array(
						'SORT' => 10,
						'DESCRIPTION' => 'If some value has a type of, say, IO String, that means that it\'s an I/O action that, when performed, will go out into the real world and get some string for us, which it will yield as a result.',
						//'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN),
					),
					'OTHER_INPUT' => array(
						'SORT' => 20,
						//'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN),
						//'DEFAULT' => array('PROVIDER_KEY' => 'INPUT', 'PROVIDER_VALUE' => '33'),
						'INPUT' => array('TYPE' => 'ENUM', 'OPTIONS' => array('a' => 'AAA', 'b' => 'BBB', 'c' => 'CCC', 'd' => 'DDD', 'e' => 'EEE'),
					)),
					'OTR_YN' => array('SORT' => 40, 'INPUT' => array('TYPE' => 'Y/N'))
				),
				'PROVIDERS' => array('VALUE', 'COMPANY', 'PROPERTY'),
			),/*
			'BOTH' => array(
				'SORT' => 300,
				'CODES' => array(
					'BASIC_COMMON' => array(),
					'OTHER'        => array(),//'DEFAULT' => array('PROVIDER_KEY' => 'VALUE', 'PROVIDER_VALUE' => 'def123')),
					'OTHER_INPUT'  => array(),
					'BASIC_ENTITY' => array('DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
					'BOTH_ENTITY'  => array('DOMAINS' => array(BusinessValue::ENTITY_DOMAIN), 'INPUT' => array(
						'TYPE' => 'ENUM',
						'OPTIONS' => array(
							'one',
							'two',
							'three',
						),
					)),
				),
			),*/
		);
	}

	public static function getGroups()
	{
		return array(
			'CONSUMER_PAYSYS'       => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CONSUMER_PAYSYS'      ), 'SORT' => 100),
			'CODE_PAYSYS'           => array('NAME' => Loc::getMessage('BIZVAL_GROUP_CONSUMER_PAYSYS'      ), 'SORT' => 100),

			'BUYER_PERSON'          => array('NAME' => Loc::getMessage('BIZVAL_GROUP_BUYER_PERSON'         ), 'SORT' => 300),
			'BUYER_PERSON_COMPANY'  => array('NAME' => Loc::getMessage('BIZVAL_GROUP_BUYER_PERSON_COMPANY' ), 'SORT' => 305),
			'BUYER_COMPANY'         => array('NAME' => Loc::getMessage('BIZVAL_GROUP_BUYER_COMPANY'        ), 'SORT' => 310),
			'1C_REKV'               => array('NAME' => Loc::getMessage('BIZVAL_GROUP_1C_REKV'              ), 'SORT' => 320),

			'SELLER_PERSON'         => array('NAME' => Loc::getMessage('BIZVAL_GROUP_SELLER_PERSON'        ), 'SORT' => 400),
			'SELLER_PERSON_COMPANY' => array('NAME' => Loc::getMessage('BIZVAL_GROUP_SELLER_PERSON_COMPANY'), 'SORT' => 405),
			'SELLER_COMPANY'        => array('NAME' => Loc::getMessage('BIZVAL_GROUP_SELLER_COMPANY'       ), 'SORT' => 410),
		);
	}
}

call_user_func(
	function ()
	{
		$eventManager = EventManager::getInstance();
//		$eventManager->addEventHandler('sale', 'OnGetBusinessValueProviders', array(__NAMESPACE__.'\BusinessValueHandlers', 'getProviders'));
		$eventManager->addEventHandler('sale', 'OnGetBusinessValueConsumers', array(__NAMESPACE__.'\BusinessValueHandlers', 'getConsumers'));
		$eventManager->addEventHandler('sale', 'OnGetBusinessValueGroups'   , array(__NAMESPACE__.'\BusinessValueHandlers', 'getGroups'   ));

		$eventManager->addEventHandler('sale', 'OnGetBusinessValueConsumers', array(__NAMESPACE__.'\BusinessValueConsumer1C', 'getConsumers'));
	}
);

use \Bitrix\Sale\Helpers\Admin\BusinessValueControl;

/** @internal */
class BusinessValueConsumer1C
{
	const CONSUMER_KEY = '1C';

	private static $codes, $personMaxIndex;
	private static $systemProviderKeys = array('', 'VALUE', 'USER', 'ORDER', 'PROPERTY', 'COMPANY', 'PAYMENT', 'SHIPMENT');
	private static $rekvProviderKeys = array('VALUE', 'USER', 'ORDER', 'PROPERTY', 'COMPANY', 'PAYMENT', 'SHIPMENT');

	private static $codeNameInput = array('TYPE' => 'STRING', 'SIZE' => 40, 'MAXLENGTH' => 50);

	public static function &getConsumers() // by ref!
	{
		static $consumers;
		if (! $consumers)
		{
			self::$codes = array(
				// !!! Make sure these codes are in sync with system codes in CSaleExport !!!
				'BUYER_PERSON_NAME'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_NAME'        ), 'SORT' =>  100, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_NAME_FIRST'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_FIRST_NAME'  ), 'SORT' =>  200, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_NAME_SECOND'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_SECOND_NAME' ), 'SORT' =>  300, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_NAME_LAST'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_LAST_NAME'   ), 'SORT' =>  400, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_NAME_AGENT'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_AGENT_NAME'  ), 'SORT' =>  500, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_NAME_CONTACT'  => array('NAME' => Loc::getMessage('BIZVAL_CODE_CONTACT_NAME'), 'SORT' =>  600, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_BIRTHDAY'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_BIRTHDAY'    ), 'SORT' =>  700, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_GENDER'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_GENDER'      ), 'SORT' =>  800, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_INN'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_INN'         ), 'SORT' =>  900, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_KPP'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_KPP'         ), 'SORT' => 1000, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_ADDRESS'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_ADDRESS'     ), 'SORT' => 1100, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_ZIP'           => array('NAME' => Loc::getMessage('BIZVAL_CODE_ZIP'         ), 'SORT' => 1200, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_COUNTRY'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_COUNTRY'     ), 'SORT' => 1300, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_REGION'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_REGION'      ), 'SORT' => 1400, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_STATE'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_DISTRICT'    ), 'SORT' => 1500, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_TOWN'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_LOCALITY'    ), 'SORT' => 1600, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_CITY'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_CITY'        ), 'SORT' => 1700, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_STREET'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_STREET'      ), 'SORT' => 1800, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_HOUSING'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_HOUSING'     ), 'SORT' => 1900, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_BUILDING'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_BUILDING'    ), 'SORT' => 2000, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_APARTMENT'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_APARTMENT'   ), 'SORT' => 2100, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_PHONE'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_PHONE'       ), 'SORT' => 2200, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_EMAIL'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_EMAIL'       ), 'SORT' => 2300, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_ADDRESS_FULL'=> array('NAME' => Loc::getMessage('BIZVAL_CODE_F_ADDRESS_FULL'), 'SORT' => 2400, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_INDEX'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_INDEX'	  ), 'SORT' => 2500, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_COUNTRY'	 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_COUNTRY'   ), 'SORT' => 2600, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_REGION'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_REGION'	  ), 'SORT' => 2700, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_STATE'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_STATE'	  ), 'SORT' => 2800, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_TOWN'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_TOWN'	  ), 'SORT' => 2900, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_CITY'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_CITY'	  ), 'SORT' => 3000, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_STREET'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_STREET'	  ), 'SORT' => 3100, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_BUILDING'	 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_BUILDING'  ), 'SORT' => 3200, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_HOUSE'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_HOUSE'	  ), 'SORT' => 3300, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_PERSON_F_FLAT'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_FLAT'	  ), 'SORT' => 3400, 'GROUP' => 'BUYER_PERSON' , 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BUYER_COMPANY_NAME'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_NAME'        ), 'SORT' =>  100, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_NAME_AGENT'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_AGENT_NAME'  ), 'SORT' =>  200, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_NAME_CONTACT' => array('NAME' => Loc::getMessage('BIZVAL_CODE_CONTACT_NAME'), 'SORT' =>  300, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_INN'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_INN'         ), 'SORT' =>  400, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_KPP'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_KPP'         ), 'SORT' =>  500, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_EGRPO'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_EGRPO'       ), 'SORT' =>  600, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_OKVED'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_OKVED'       ), 'SORT' =>  700, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_OKDP'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_OKDP'        ), 'SORT' =>  800, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_OKOPF'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_OKOPF'       ), 'SORT' =>  900, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_OKFC'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_OKFC'        ), 'SORT' => 1000, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_OKPO'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_OKPO'        ), 'SORT' => 1100, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_BANK_ACCOUNT' => array('NAME' => Loc::getMessage('BIZVAL_CODE_BANK_ACCOUNT'), 'SORT' => 1200, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_ADDRESS'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_ADDRESS'     ), 'SORT' => 1300, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_ZIP'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_ZIP'         ), 'SORT' => 1400, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_COUNTRY'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_COUNTRY'     ), 'SORT' => 1500, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_REGION'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_REGION'      ), 'SORT' => 1600, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_STATE'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_DISTRICT'    ), 'SORT' => 1700, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_TOWN'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_LOCALITY'    ), 'SORT' => 1800, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_CITY'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_CITY'        ), 'SORT' => 1900, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_STREET'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_STREET'      ), 'SORT' => 2000, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_HOUSING'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_HOUSING'     ), 'SORT' => 2100, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_BUILDING'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_BUILDING'    ), 'SORT' => 2200, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_APARTMENT'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_APARTMENT'   ), 'SORT' => 2300, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_PHONE'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_PHONE'       ), 'SORT' => 2400, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_EMAIL'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_EMAIL'       ), 'SORT' => 2500, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_ADDRESS_FULL'=> array('NAME' => Loc::getMessage('BIZVAL_CODE_F_ADDRESS_FULL'), 'SORT' => 2600, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_INDEX'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_INDEX'	  ), 'SORT' => 2700, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_COUNTRY'	 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_COUNTRY'	  ), 'SORT' => 2800, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_REGION'	 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_REGION'	  ), 'SORT' => 2900, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_STATE'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_STATE'	  ), 'SORT' => 3000, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_TOWN'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_TOWN'	  ), 'SORT' => 3100, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_CITY'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_CITY'	  ), 'SORT' => 3200, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_STREET'	 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_STREET'	  ), 'SORT' => 3300, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_BUILDING'	 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_BUILDING'  ), 'SORT' => 3400, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_HOUSE'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_HOUSE'	  ), 'SORT' => 3500, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),
				'BUYER_COMPANY_F_FLAT'		 => array('NAME' => Loc::getMessage('BIZVAL_CODE_F_FLAT'	  ), 'SORT' => 3600, 'GROUP' => 'BUYER_COMPANY', 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN    )),

			);

			self::$personMaxIndex = array();

			$result = Internals\BusinessValueCode1CTable::getList(array('order' => array('CODE_INDEX')));

			while ($row = $result->fetch())
			{
				$personTypeId = $row['PERSON_TYPE_ID'];
				$codeIndex = $row['CODE_INDEX'];

				if (self::$personMaxIndex[$personTypeId] < $codeIndex)
					self::$personMaxIndex[$personTypeId] = $codeIndex;

				self::$codes[self::getRekvCodeKey($personTypeId, $codeIndex)] = self::getRekvCode($personTypeId, $codeIndex, $row['NAME']);
			}

			self::$codes['1C_REKV_##PERSON_TYPE_ID##_##CODE_INDEX##'] = array(
				'SORT'       => 100000,
				'GROUP'      => '1C_REKV',
				'DOMAINS'    => array(BusinessValue::INDIVIDUAL_DOMAIN, BusinessValue::ENTITY_DOMAIN),
				'NOT_COMMON' => true,
			);

			$consumers = array(
				self::CONSUMER_KEY => array(
					'NAME'  => Loc::getMessage('BIZVAL_CONSUMER_1C'),
					'SORT'  => 400,
					'CODES' => &self::$codes, // by ref!
					'SKIP_NEW_CODE_SANITATION' => true,
					'SANITIZE_MAPPING'         => array(__CLASS__, 'sanitizeMapping'),
					'SET_MAPPING'              => array(__CLASS__, 'setMapping'),
					'RENDER_COLUMNS'           => array(__CLASS__, 'renderColumns'),
					'GET_JAVASCRIPT'           => array(__CLASS__, 'getJavaScript'),
				),
			);
		}

		return $consumers;
	}

	public static function getRekvCodeKey($personTypeId, $codeIndex)
	{
		return '1C_REKV_'.$personTypeId.'_'.$codeIndex;
	}

	private static function getRekvCode($personTypeId, $codeIndex, $name)
	{
		return array(
			'NAME'           => $name,
			'SORT'           => $codeIndex + 10000,
			'GROUP'          => '1C_REKV',
			'PERSON_TYPE_ID' => $personTypeId,
			'NOT_COMMON'     => true,
			'CODE_INDEX'     => $codeIndex, // Used internally!
		);
	}

	public static function sanitizeMapping($codeKey, $personTypeId, array &$mapping)
	{
		$error = array();

		if (! ($code = self::$codes[$codeKey]) || isset($code['CODE_INDEX']))
		{
			if ($e = Internals\Input\Manager::getError(self::$codeNameInput, $mapping['NAME']))
				$error['NAME'] = $e;
			else
				$mapping['NAME'] = Internals\Input\Manager::getValue(self::$codeNameInput, $mapping['NAME']);

			$providerKeys = self::$rekvProviderKeys;
		}
		else
		{
			$providerKeys = self::$systemProviderKeys;
		}

		$error += BusinessValueControl::sanitizeMapping($personTypeId, $mapping, $providerKeys);

		return $error;
	}

	public static function setMapping($codeKey, $personTypeId, array $mapping)
	{
		$systemCode = true;

		if (! ($code = & self::$codes[$codeKey]) || isset($code['CODE_INDEX']))
		{
			$systemCode = false;

			if ($code)
			{
				if ($mapping && $mapping['NAME'])
				{
					if ($mapping['NAME'] != $code['NAME'])
					{
						$result = Internals\BusinessValueCode1CTable::update(
							array(
								'PERSON_TYPE_ID' => $code['PERSON_TYPE_ID'],
								'CODE_INDEX'     => $code['CODE_INDEX'],
							),
							array(
								'NAME' => $mapping['NAME']
							)
						);

						if ($result->isSuccess())
							$code['NAME'] = $mapping['NAME'];
					}
				}
				else
				{
					$result = Internals\BusinessValueCode1CTable::delete(array(
						'PERSON_TYPE_ID' => $code['PERSON_TYPE_ID'],
						'CODE_INDEX'     => $code['CODE_INDEX'],
					));

					if ($result->isSuccess())
					{
						unset(self::$codes[$codeKey]);
						$mapping = array(); // delete
					}
				}
			}
			else
			{
				$codeIndex = isset(self::$personMaxIndex[$personTypeId]) ? self::$personMaxIndex[$personTypeId] + 1 : 0;

				if ($mapping && $mapping['NAME'] && $codeKey == self::getRekvCodeKey($personTypeId, $codeIndex))
				{
					$result = Internals\BusinessValueCode1CTable::add(array(
						'PERSON_TYPE_ID' => $personTypeId,
						'CODE_INDEX'     => $codeIndex,
						'NAME'           => $mapping['NAME'],
					));

					if ($result->isSuccess())
					{
						self::$personMaxIndex[$personTypeId] = $codeIndex;
						$code = self::getRekvCode($personTypeId, $codeIndex, $mapping['NAME']);

						// move adding button to the end
						$addCode = self::$codes['1C_REKV_##PERSON_TYPE_ID##_##CODE_INDEX##'];
						unset(self::$codes['1C_REKV_##PERSON_TYPE_ID##_##CODE_INDEX##']);
						self::$codes['1C_REKV_##PERSON_TYPE_ID##_##CODE_INDEX##'] = $addCode;
					}
				}
				else
				{
					return new \Bitrix\Main\Result();
				}
			}
		}

		$mapping = $mapping['PROVIDER_KEY'] 
				? array(
						'PROVIDER_KEY'   => $mapping['PROVIDER_KEY'  ],
						'PROVIDER_VALUE' => $mapping['PROVIDER_VALUE'],
				)
				: array();


		return isset($result) && ! $result->isSuccess()
			? $result
			: BusinessValue::setMapping($codeKey, self::CONSUMER_KEY, $personTypeId, $mapping, $systemCode);
	}

	public static function renderColumns($codeKey, $personTypeId, array $mappings, $inputNamePrefix)
	{
		$hideCode = false;

		if ($code = self::$codes[$codeKey])
		{
			$providerValueInput = BusinessValueControl::getValueInput($personTypeId);
			$commonProviderInput = null;
			$commonProviderValueInput = BusinessValueControl::getValueInput('');

			if ($codeKey == '1C_REKV_##PERSON_TYPE_ID##_##CODE_INDEX##') // new code
			{
				$inputNamePrefix = str_replace('##PERSON_TYPE_ID##', $personTypeId, $inputNamePrefix);

				ob_start();

				?>
				<td>
					<?=Internals\Input\Manager::getEditHtml($inputNamePrefix.'[NAME]', self::$codeNameInput)?>
				</td>
				<td>
					<?

					BusinessValueControl::renderMapping(
						array(),
						$inputNamePrefix,
						BusinessValueControl::getProviderInput($personTypeId, self::$rekvProviderKeys),
						$providerValueInput
					);

					?>
				</td>
				<?

				$newMappingHTML = ob_get_clean();

				?>
				<td>
					<a href="#" onclick="bizvalAdd1CRekv(this, '<?=$personTypeId?>', '<?=\CUtil::JSEscape(htmlspecialcharsbx($newMappingHTML))?>'); return false;">
						<?=Loc::getMessage('BIZVAL_CODE_1C_REKV_ADD')?>
					</a>
				</td>
				<td></td>
				<?
			}
			elseif (isset($code['CODE_INDEX'])) // rekv code
			{
				?>
				<td><?=Internals\Input\Manager::getEditHtml($inputNamePrefix.'[NAME]', self::$codeNameInput, $code['NAME'] ?: $codeKey)?></td>
				<td>
					<?

					if ($personTypeId)
						$commonProviderInput = BusinessValueControl::getProviderInput('', self::$rekvProviderKeys);

					BusinessValueControl::renderMapping(
						$mappings,
						$inputNamePrefix,
						BusinessValueControl::getProviderInput($personTypeId, self::$rekvProviderKeys),
						$providerValueInput,
						$commonProviderInput,
						$commonProviderValueInput
					);

					?>
				</td>
				<?
			}
			else // system code
			{
				?>
				<td><?=htmlspecialcharsbx($code['NAME'] ?: $codeKey)?></td>
				<td>
					<?

					if ($personTypeId)
						$commonProviderInput = BusinessValueControl::getProviderInput('', self::$systemProviderKeys);

					$hideCode = BusinessValueControl::renderMapping(
						$mappings,
						$inputNamePrefix,
						BusinessValueControl::getProviderInput($personTypeId, self::$systemProviderKeys),
						$providerValueInput,
						$commonProviderInput,
						$commonProviderValueInput
					);

//					BusinessValueControl::renderDeletor($inputNamePrefix, $mapping, $commonMapping, $defaultMapping);

					?>
				</td>
				<?
			}
		}
		else
		{
			?>
			<td><?=self::CONSUMER_KEY?></td>
			<td><?=$codeKey?></td>
			<?
		}

		return $hideCode;
	}

	public static function getJavaScript()
	{
		return '
			var bizvalAdd1CRekv = (function (personMaxIndex) {
				"use strict";
				return function(anchor, personTypeId, sample)
				{
					var myRow  = anchor.parentNode.parentNode,
						table  = myRow.parentNode,
						newRow = document.createElement("tr");

					if (typeof personMaxIndex[personTypeId] === "undefined")
						personMaxIndex[personTypeId] = 0;
					else
						personMaxIndex[personTypeId] ++;

					newRow.innerHTML = sample.replace(/##CODE_INDEX##/g, personMaxIndex[personTypeId]);

					table.insertBefore(newRow, myRow);
				};
			})('.(self::$personMaxIndex ? \CUtil::PhpToJSObject(self::$personMaxIndex) : '{}').');
		';
	}
}
