<?php
if(!CModule::IncludeModule('rest'))
{
	return;
}

use Bitrix\Crm\Integration\StorageFileType;
use Bitrix\Rest\RestException;
use Bitrix\Rest\UserFieldProxy;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\DiskManager;

final class CCrmRestService extends IRestService
{
	private static $METHOD_NAMES = array(
		'crm.status.fields',
		'crm.status.add',
		'crm.status.get',
		'crm.status.list',
		'crm.status.update',
		'crm.status.delete',
		'crm.status.entity.types',
		'crm.status.entity.items',

		'crm.enum.fields',
		'crm.enum.ownertype',
		'crm.enum.contenttype',
		'crm.enum.activitytype',
		'crm.enum.activitypriority',
		'crm.enum.activitydirection',
		'crm.enum.activitynotifytype',

		'crm.lead.fields',
		'crm.lead.add',
		'crm.lead.get',
		'crm.lead.list',
		'crm.lead.update',
		'crm.lead.delete',
		'crm.lead.productrows.set',
		'crm.lead.productrows.get',

		'crm.deal.fields',
		'crm.deal.add',
		'crm.deal.get',
		'crm.deal.list',
		'crm.deal.update',
		'crm.deal.delete',
		'crm.deal.productrows.set',
		'crm.deal.productrows.get',

		'crm.company.fields',
		'crm.company.add',
		'crm.company.get',
		'crm.company.list',
		'crm.company.update',
		'crm.company.delete',

		'crm.contact.fields',
		'crm.contact.add',
		'crm.contact.get',
		'crm.contact.list',
		'crm.contact.update',
		'crm.contact.delete',

		'crm.currency.fields',
		'crm.currency.add',
		'crm.currency.get',
		'crm.currency.list',
		'crm.currency.update',
		'crm.currency.delete',
		'crm.currency.localizations.get',
		'crm.currency.localizations.set',
		'crm.currency.localizations.delete',

		'crm.catalog.fields',
		//'crm.catalog.add',
		'crm.catalog.get',
		'crm.catalog.list',
		//'crm.catalog.update',
		//'crm.catalog.delete',

		'crm.product.fields',
		'crm.product.add',
		'crm.product.get',
		'crm.product.list',
		'crm.product.update',
		'crm.product.delete',

		'crm.productsection.fields',
		'crm.productsection.add',
		'crm.productsection.get',
		'crm.productsection.list',
		'crm.productsection.update',
		'crm.productsection.delete',

		'crm.productrow.fields',
		'crm.productrow.add',
		'crm.productrow.get',
		'crm.productrow.list',
		'crm.productrow.update',
		'crm.productrow.delete',

		'crm.activity.fields',
		'crm.activity.add',
		'crm.activity.get',
		'crm.activity.list',
		'crm.activity.update',
		'crm.activity.delete',
		'crm.activity.communication.fields',

		'crm.lead.userfield.add',
		'crm.lead.userfield.get',
		'crm.lead.userfield.list',
		'crm.lead.userfield.update',
		'crm.lead.userfield.delete',

		'crm.deal.userfield.add',
		'crm.deal.userfield.get',
		'crm.deal.userfield.list',
		'crm.deal.userfield.update',
		'crm.deal.userfield.delete',

		'crm.company.userfield.add',
		'crm.company.userfield.get',
		'crm.company.userfield.list',
		'crm.company.userfield.update',
		'crm.company.userfield.delete',

		'crm.contact.userfield.add',
		'crm.contact.userfield.get',
		'crm.contact.userfield.list',
		'crm.contact.userfield.update',
		'crm.contact.userfield.delete',

		'crm.userfield.fields',
		'crm.userfield.enumeration.fields',
		'crm.userfield.settings.fields',

		'crm.multifield.fields',
		'crm.duplicate.findbycomm',
		'crm.livefeedmessage.add'
	);
	const SCOPE_NAME = 'crm';
	private static $DESCRIPTION = null;
	private static $PROXIES = array();

	public static function onRestServiceBuildDescription()
	{
		if(!self::$DESCRIPTION)
		{
			$bindings = array();
			// There is one entry point
			$callback = array('CCrmRestService', 'onRestServiceMethod');
			foreach(self::$METHOD_NAMES as $name)
			{
				$bindings[$name] = $callback;
			}

			self::$DESCRIPTION = array('crm' => $bindings);
		}

		return self::$DESCRIPTION;
	}
	public static function onRestServiceMethod($arParams, $nav, $server)
	{
		if(!CCrmPerms::IsAccessEnabled())
		{
			throw new RestException('Access denied.');
		}

		$methodName = $server->getMethod();

		$parts = explode('.', $methodName);
		$partCount = count($parts);
		if($partCount < 3 || $parts[0] !== 'crm')
		{
			throw new RestException("Method '{$methodName}' is not supported in current context.");
		}

		$typeName = strtoupper($parts[1]);
		$proxy = null;

		if(isset(self::$PROXIES[$typeName]))
		{
			$proxy = self::$PROXIES[$typeName];
		}

		if(!$proxy)
		{
			if($typeName === 'ENUM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmEnumerationRestProxy();
			}
			elseif($typeName === 'MULTIFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmMultiFieldRestProxy();
			}
			elseif($typeName === 'CURRENCY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCurrencyRestProxy();
			}
			elseif($typeName === 'CATALOG')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCatalogRestProxy();
			}
			elseif($typeName === 'PRODUCT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRestProxy();
			}
			elseif($typeName === 'PRODUCTSECTION')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductSectionRestProxy();
			}
			elseif($typeName === 'PRODUCTROW')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRowRestProxy();
			}
			elseif($typeName === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmStatusRestProxy();
			}
			elseif($typeName === 'LEAD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLeadRestProxy();
			}
			elseif($typeName === 'DEAL')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDealRestProxy();
			}
			elseif($typeName === 'COMPANY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCompanyRestProxy();
			}
			elseif($typeName === 'CONTACT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmContactRestProxy();
			}
			elseif($typeName === 'ACTIVITY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmActivityRestProxy();
			}
			elseif($typeName === 'DUPLICATE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDuplicateRestProxy();
			}
			elseif($typeName === 'LIVEFEEDMESSAGE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLiveFeedMessageRestProxy();
			}
			elseif($typeName === 'USERFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmUserFieldRestProxy(CCrmOwnerType::Undefined);
			}
			else
			{
				throw new RestException("Could not find proxy for method '{$methodName}'.");
			}
			$proxy->setServer($server);
		}

		return $proxy->processMethodRequest(
			$parts[2],
			$partCount > 3 ? array_slice($parts, 3) : array(),
			$arParams,
			$nav,
			$server
		);
	}
	public static function getNavData($start)
	{
		return parent::getNavData($start);
	}
	public static function setNavData($result, $dbRes)
	{
		return parent::setNavData($result, $dbRes);
	}
}

class CCrmRestHelper
{
	public static function resolveArrayParam(array &$arParams, $name, array $default = null)
	{
		// Check for upper case notation (FILTER, SORT, SELECT, etc)
		$upper = strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for lower case notation (filter, sort, select, etc)
		$lower = strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for capitalized notation (Filter, Sort, Select, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		// Check for hungary notation (arFilter, arSort, arSelect, etc)
		$hungary = "ar{$capitalized}";
		if(isset($arParams[$hungary]))
		{
			return $arParams[$hungary];
		}

		return $default;
	}
	public static function resolveParam(array &$arParams, $name, $default = null)
	{
		// Check for lower case notation (type, etc)
		$lower = strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case notation (TYPE, etc)
		$upper = strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for capitalized notation (Type, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		return $default;
	}
}

abstract class CCrmRestProxyBase
{
	private $currentUser = null;
	private $webdavSettings = null;
	private $webdavIBlock = null;
	private $server = null;
	private $sanitizer = null;
	private static $MULTIFIELD_TYPE_IDS = null;
	public function getFields()
	{
		$fildsInfo = $this->getFieldsInfo();
		return self::prepareFields($fildsInfo);
	}
	public function isValidID($ID)
	{
		return is_int($ID) && $ID > 0;
	}
	public function add(&$fields)
	{
		$this->internalizeFields($fields, $this->getFieldsInfo(), array());

		$errors = array();
		$result = $this->innerAdd($fields, $errors);
		if(!$this->isValidID($result))
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function get($ID)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}


		$errors = array();
		$result = $this->innerGet($ID, $errors);
		if(!is_array($result))
		{
			throw new RestException(implode("\n", $errors));
		}
		$this->externalizeFields($result, $this->getFieldsInfo());
		return $result;

	}
	public function getList($order, $filter, $select, $start)
	{
		$this->prepareListParams($order, $filter, $select);
		$navigation = CCrmRestService::getNavData($start);

		$enableMultiFields = false;
		$selectedFmTypeIDs = array();
		if(is_array($select) && !empty($select))
		{
			$supportedFmTypeIDs = $this->getSupportedMultiFieldTypeIDs();

			if(is_array($supportedFmTypeIDs) && !empty($supportedFmTypeIDs))
			{
				foreach($supportedFmTypeIDs as $fmTypeID)
				{
					if(in_array($fmTypeID, $select, true))
					{
						$selectedFmTypeIDs[] = $fmTypeID;
					}
				}
			}
			$enableMultiFields = !empty($selectedFmTypeIDs);
			if($enableMultiFields)
			{
				$identityFieldName = $this->getIdentityFieldName();
				if($identityFieldName === '')
				{
					throw new RestException('Could not find identity field name.');
				}

				if(!in_array($identityFieldName, $select, true))
				{
					$select[] = $identityFieldName;
				}
			}
		}

		$this->internalizeFilterFields($filter, $this->getFieldsInfo());

		$errors = array();
		$result = $this->innerGetList($order, $filter, $select, $navigation, $errors);
		if(!$result)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result instanceOf CDBResult
			? $this->prepareListFromDbResult($result, array('SELECTED_FM_TYPES' => $selectedFmTypeIDs))
			: $this->prepareListFromArray($result, array('SELECTED_FM_TYPES' => $selectedFmTypeIDs));
	}
	protected function prepareListFromDbResult(CDBResult $dbResult, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		$selectedFmTypeIDs = isset($options['SELECTED_FM_TYPES']) ? $options['SELECTED_FM_TYPES'] : array();
		if(empty($selectedFmTypeIDs))
		{
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$entityMap = array();
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	protected function prepareListFromArray(array $list, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		$selectedFmTypeIDs = isset($options['SELECTED_FM_TYPES']) ? $options['SELECTED_FM_TYPES'] : array();
		if(empty($selectedFmTypeIDs))
		{
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$entityMap = array();
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, array('offset' => 0, 'count' => count($result)));
	}
	public function update($ID, &$fields)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$this->internalizeFields(
			$fields,
			$this->getFieldsInfo(),
			array(
				'IGNORED_ATTRS' => array(
					CCrmFieldInfoAttr::Immutable,
					CCrmFieldInfoAttr::UserPKey
				)
			)
		);

		$errors = array();
		$result = $this->innerUpdate($ID, $fields, $errors);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function delete($ID)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$errors = array();
		$result = $this->innerDelete($ID, $errors);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	protected function prepareListParams(&$order, &$filter, &$select)
	{
	}
	protected function prepareListItemFields(&$fields)
	{
	}
	protected function getCurrentUser()
	{
		return $this->currentUser !== null
			? $this->currentUser
			: ($this->currentUser = CCrmSecurityHelper::GetCurrentUser());
	}
	protected function getCurrentUserID()
	{
		return $this->getCurrentUser()->GetID();
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer($server)
	{
		$this->server = $server;
	}
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Undefined;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$ownerTypeID = $this->getOwnerTypeID();

		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return $this->getFields();
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			return $this->add($fields);
		}
		elseif($name === 'GET')
		{
			return $this->get($this->resolveEntityID($arParams));
		}
		elseif($name === 'LIST')
		{
			$order = $this->resolveArrayParam($arParams, 'order');
			if(!is_array($order))
			{
				throw new RestException("Parameter 'order' must be array.");
			}

			$filter = $this->resolveArrayParam($arParams, 'filter');
			if(!is_array($filter))
			{
				throw new RestException("Parameter 'filter' must be array.");
			}
			$select = $this->resolveArrayParam($arParams, 'select');
			return $this->getList($order, $filter, $select, $nav);
		}
		elseif($name === 'UPDATE')
		{
			$ID = $this->resolveEntityID($arParams);
			$fields = $fields = $this->resolveArrayParam($arParams, 'fields');
			return $this->update($ID, $fields);
		}
		elseif($name === 'DELETE')
		{
			return $this->delete($this->resolveEntityID($arParams));
		}
		elseif($name === 'USERFIELD' && $ownerTypeID !== CCrmOwnerType::Undefined)
		{
			$ufProxy = new CCrmUserFieldRestProxy($ownerTypeID);

			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'ADD')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields', null);
				return $ufProxy->add(is_array($fields) ? $fields : $arParams);
			}
			elseif($nameSuffix === 'GET')
			{
				return $ufProxy->get($this->resolveParam($arParams, 'id', ''));
			}
			elseif($nameSuffix === 'LIST')
			{
				$order = $this->resolveArrayParam($arParams, 'order', array());
				if(!is_array($order))
				{
					throw new RestException("Parameter 'order' must be array.");
				}

				$filter = $this->resolveArrayParam($arParams, 'filter', array());
				if(!is_array($filter))
				{
					throw new RestException("Parameter 'filter' must be array.");
				}

				return $ufProxy->getList($order, $filter);
			}
			elseif($nameSuffix === 'UPDATE')
			{
				return $ufProxy->update(
					$this->resolveParam($arParams, 'id'),
					$this->resolveArrayParam($arParams, 'fields')
				);
			}
			elseif($nameSuffix === 'DELETE')
			{
				return $ufProxy->delete($this->resolveParam($arParams, 'id', ''));
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function resolveParam(&$arParams, $name)
	{
		return CCrmRestHelper::resolveParam($arParams, $name, '');
	}
	protected function resolveMultiPartParam(&$arParams, array $nameParts, $default = '')
	{
		if(empty($nameParts))
		{
			return $default;
		}

		$upperUnderscoreName = strtoupper(implode('_', $nameParts));
		if(isset($arParams[$upperUnderscoreName]))
		{
			return $arParams[$upperUnderscoreName];
		}

		$lowerUnderscoreName = strtolower($upperUnderscoreName);
		if(isset($arParams[$lowerUnderscoreName]))
		{
			return $arParams[$lowerUnderscoreName];
		}

		$hungaryName = '';
		foreach($nameParts as $namePart)
		{
			$hungaryName .= ucfirst($namePart);
		}

		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		$hungaryName = "ar{$hungaryName}";
		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		return $default;
	}
	protected function resolveArrayParam(&$arParams, $name, $default = array())
	{
		return CCrmRestHelper::resolveArrayParam($arParams, $name, $default);
	}
	protected function resolveEntityID(&$arParams)
	{
		return isset($arParams['ID'])
			? intval($arParams['ID'])
			: (isset($arParams['id']) ? intval($arParams['id']) : 0);
	}
	protected function resolveRelationID(&$arParams, $relationName)
	{
		$nameLowerCase = strtolower($relationName);
		// Check for camel case (entityId or entityID)
		$camel = "{$nameLowerCase}Id";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		$camel = "{$nameLowerCase}ID";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		// Check for lower case (entity_id)
		$lower = "{$nameLowerCase}_id";
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case (ENTITY_ID)
		$upper = strtoupper($lower);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		return '';
	}
	protected function checkEntityID($ID)
	{
		return is_int($ID) && $ID > 0;
	}
	protected static function prepareMultiFieldsInfo(&$fieldsInfo)
	{
		$typesID = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		foreach($typesID as $typeID)
		{
			$fieldsInfo[$typeID] = array(
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
	}
	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}
	protected static function prepareFields(&$fieldsInfo)
	{
		$result = array();

		foreach($fieldsInfo as $fieldID => &$fieldInfo)
		{
			$attrs = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : array();
			// Skip hidden fields
			if(in_array(CCrmFieldInfoAttr::Hidden, $attrs, true))
			{
				continue;
			}

			$fieldType = $fieldInfo['TYPE'];
			$field = array(
				'type' => $fieldType,
				'isRequired' => in_array(CCrmFieldInfoAttr::Required, $attrs, true),
				'isReadOnly' => in_array(CCrmFieldInfoAttr::ReadOnly, $attrs, true),
				'isImmutable' => in_array(CCrmFieldInfoAttr::Immutable, $attrs, true),
				'isMultiple' => in_array(CCrmFieldInfoAttr::Multiple, $attrs, true),
				'isDynamic' => in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true)
			);

			if(in_array(CCrmFieldInfoAttr::Deprecated, $attrs, true))
			{
				$field['isDeprecated'] = true;
			}

			if($fieldType === 'enumeration')
			{
				$field['items'] = isset($fieldInfo['ITEMS']) ? $fieldInfo['ITEMS'] : array();
			}
			elseif($fieldType === 'crm_status')
			{
				$field['statusType'] = isset($fieldInfo['CRM_STATUS_TYPE']) ? $fieldInfo['CRM_STATUS_TYPE'] : '';
			}

			if(isset($fieldInfo['LABELS']) && is_array($fieldInfo['LABELS']))
			{
				$labels = $fieldInfo['LABELS'];
				if(isset($labels['LIST']))
				{
					$field['listLabel'] = $labels['LIST'];
				}
				if(isset($labels['FORM']))
				{
					$field['formLabel'] = $labels['FORM'];
				}
				if(isset($labels['FILTER']))
				{
					$field['filterLabel'] = $labels['FILTER'];
				}
			}

			$result[$fieldID] = &$field;
			unset($field);
		}
		unset($fieldInfo);

		return $result;
	}
	protected function internalizeFields(&$fields, &$fieldsInfo, $options = array())
	{
		if(!is_array($fields))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$ignoredAttrs = isset($options['IGNORED_ATTRS']) ? $options['IGNORED_ATTRS'] : array();
		if(!in_array(CCrmFieldInfoAttr::Hidden, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::Hidden;
		}
		if(!in_array(CCrmFieldInfoAttr::ReadOnly, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::ReadOnly;
		}

		$arFMData = null;
		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);

			$ary = array_intersect($ignoredAttrs, $attrs);
			if(!empty($ary))
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'datetime')
			{
				$fields[$k] = CRestUtil::unConvertDateTime($v);
			}
			elseif($fieldType === 'file')
			{
				$this->tryInternalizeFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryInternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryInternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'crm_multifield')
			{
				$fmData = array();
				$fmTypeID = $k;
				//if(strlen($fmTypeID) > 3 && strpos($fmTypeID, 'FM_') === 0)
				//{
				//	$fmTypeID = substr($fmTypeID, 3);
				//}

				$fmNewItemQty = 0;
				foreach($v as &$fmItem)
				{
					$fmItemKey = isset($fmItem['ID']) ? $fmItem['ID'] : 'n'.(++$fmNewItemQty);
					$fmData[$fmItemKey] = array(
						'VALUE_TYPE' => isset($fmItem['VALUE_TYPE']) ? $fmItem['VALUE_TYPE'] : '',
						'VALUE' => isset($fmItem['VALUE']) ? $fmItem['VALUE'] : ''
					);
				}
				unset($fmItem);

				if($arFMData == null)
				{
					$arFMData = array();
				}

				$arFMData[$fmTypeID] = $fmData;
				unset($fmData, $fields[$k]);
			}
		}

		if(is_array($arFMData))
		{
			$fields['FM'] = $arFMData;
		}
	}
	protected function tryInternalizeFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeFile = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				$fileName = '';
				$fileContent = $fileData;
			}
			else
			{
				$fileDataLength = count($fileData);

				if($fileDataLength > 1)
				{
					$fileName = $fileData[0];
					$fileContent = $fileData[1];
				}
				elseif($fileDataLength === 1)
				{
					$fileName = '';
					$fileContent = $fileData[0];
				}
				else
				{
					$fileName = '';
					$fileContent = '';
				}
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				// Add/replace file
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileID > 0)
					{
						$fileInfo['old_id'] = $fileID;
					}

					//In this case 'del' flag does not make sense - old file will be replaced by new one.
					/*if($removeFile)
					{
						$fileInfo['del'] = true;
					}*/

					$result[] = &$fileInfo;
					unset($fileInfo);
				}
			}
			elseif($fileID > 0 && $removeFile)
			{
				// Remove file
				$result[] = array(
					'old_id' => $fileID,
					'del' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$elementID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$settings = $this->getWebDavSettings();
				$iblock = $this->prepareWebDavIBlock($settings);
				$fileName = $iblock->CorrectName($fileName);

				$filePath = $fileInfo['tmp_name'];
				$options = array(
					'new' => true,
					'dropped' => false,
					'arDocumentStates' => array(),
					'arUserGroups' => $iblock->USER['GROUPS'],
					'TMP_FILE' => $filePath,
					'FILE_NAME' => $fileName,
					'IBLOCK_ID' => $settings['IBLOCK_ID'],
					'IBLOCK_SECTION_ID' => $settings['IBLOCK_SECTION_ID'],
					'WF_STATUS_ID' => 1
				);
				$options['arUserGroups'][] = 'Author';

				global $DB;
				$DB->StartTransaction();
				if (!$iblock->put_commit($options))
				{
					$DB->Rollback();
					unlink($filePath);
					throw new RestException($iblock->LAST_ERROR);
				}
				$DB->Commit();
				unlink($filePath);

				if(!isset($options['ELEMENT_ID']))
				{
					throw new RestException('Could not save webdav element.');
				}

				$elementData = array(
					'ELEMENT_ID' => $options['ELEMENT_ID']
				);

				if($elementID > 0)
				{
					$elementData['OLD_ELEMENT_ID'] = $elementID;
				}

				$result[] = &$elementData;
				unset($elementData);
			}
			elseif($elementID > 0 && $removeElement)
			{
				$result[] = array(
					'OLD_ELEMENT_ID' => $elementID,
					'DELETE' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$folder = DiskManager::ensureFolderCreated(StorageFileType::Rest);
				if(!$folder)
				{
					unlink($fileInfo['tmp_name']);
					throw new RestException('Could not create disk folder for rest files.');
				}

				$file = $folder->uploadFile(
					$fileInfo,
					array('NAME' => $fileName, 'CREATED_BY' => $this->getCurrentUserID(), array(), true)
				);
				unlink($fileInfo['tmp_name']);

				if(!$file)
				{
					throw new RestException('Could not create disk file.');
				}

				$result[] = array('FILE_ID' => $file->getId());
			}
			elseif($fileID > 0 && $removeElement)
			{
				$result[] = array('OLD_FILE_ID' => $fileID, 'DELETE' => true);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		if(!is_array($fields))
		{
			return;
		}

		//Multi fields processing
		if(isset($fields['FM']))
		{
			foreach($fields['FM'] as $fmTypeID => &$fmItems)
			{
				foreach($fmItems as &$fmItem)
				{
					$fmItem['TYPE_ID'] = $fmTypeID;
					unset($fmItem['ENTITY_ID'], $fmItem['ELEMENT_ID']);
				}
				unset($fmItem);
				$fields[$fmTypeID] = $fmItems;
			}
			unset($fmItems);
			unset($fields['FM']);
		}

		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;

			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
			$isHidden = in_array(CCrmFieldInfoAttr::Hidden, $attrs, true);
			$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

			if($isHidden)
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'datetime')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDateTime($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDateTime($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryExternalizeFileField($fields, $k, $isMultiple, $isDynamic);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryExternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryExternalizeDiskFileField($fields, $k, $isMultiple);
			}
		}
	}
	protected function tryExternalizeFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$ownerTypeID = $this->getOwnerTypeID();
		$ownerID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			if($fileID <= 0)
			{
				unset($fields[$fieldName]);
				return false;
			}

			$fields[$fieldName] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
		}
		else
		{
			$result = array();
			$filesID = $fields[$fieldName];
			if(!is_array($filesID))
			{
				$filesID = array($filesID);
			}

			foreach($filesID as $fileID)
			{
				$fileID = intval($fileID);
				if($fileID > 0)
				{
					$result[] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if(!$multiple)
		{
			$elementID = intval($fields[$fieldName]);
			$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$elementsID = $fields[$fieldName];
		if(is_array($elementsID))
		{
			foreach($elementsID as $elementID)
			{
				$elementID = intval($elementID);
				$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			$info = DiskManager::getFileInfo($fileID, false);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$fileIDs = $fields[$fieldName];
		if(is_array($fileIDs))
		{
			foreach($fileIDs as $fileID)
			{
				$info = DiskManager::getFileInfo($fileID, false);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function internalizeFilterFields(&$filter, &$fieldsInfo)
	{
		if(!is_array($filter))
		{
			return;
		}

		foreach($filter as $k => $v)
		{
			$operationInfo =  CSqlUtil::GetFilterOperation($k);
			$fieldName = $operationInfo['FIELD'];

			$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
			if(!$info)
			{
				unset($filter[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'datetime')
			{
				$filter[$k] = CRestUtil::unConvertDateTime($v);
			}
		}

		CCrmEntityHelper::PrepareMultiFieldFilter($filter);
	}
	protected static function isAssociativeArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return true;
			}
		}
		return false;
	}
	protected static function isIndexedArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return false;
			}
		}
		return true;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		$errors[] = 'The operation "ADD" is not supported by this entity.';
		return false;
	}
	protected function innerGet($ID, &$errors)
	{
		$errors[] = 'The operation "GET" is not supported by this entity.';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$errors[] = 'The operation "LIST" is not supported by this entity.';
		return null;
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		$errors[] = 'The operation "UPDATE" is not supported by this entity.';
		return false;
	}
	protected function innerDelete($ID, &$errors)
	{
		$errors[] = 'The operation "DELETE" is not supported by this entity.';;
		return false;
	}
	protected function externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic = true)
	{
		$ownerTypeName = strtolower(CCrmOwnerType::ResolveName($ownerTypeID));
		if($ownerTypeName === '')
		{
			return '';
		}

		$handlerUrl = "/bitrix/components/bitrix/crm.{$ownerTypeName}.show/show_file.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->server ? $this->server->getAuth() : '',
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		return array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);
	}
	// WebDav -->
	protected function prepareWebDavIBlock($settings = null)
	{
		if($this->webdavIBlock !== null)
		{
			return $this->webdavIBlock;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		if(!is_array($settings) || empty($settings))
		{
			$settings = $this->getWebDavSettings();
		}

		$iblockID = isset($settings['IBLOCK_ID']) ? $settings['IBLOCK_ID'] : 0;
		if($iblockID <= 0)
		{
			throw new RestException('Could not find webdav iblock.');
		}

		$sectionId = isset($settings['IBLOCK_SECTION_ID']) ? $settings['IBLOCK_SECTION_ID'] : 0;
		if($sectionId <= 0)
		{
			throw new RestException('Could not find webdav section.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();
		$this->webdavIBlock = new CWebDavIblock(
			$iblockID,
			'',
			array(
				'ROOT_SECTION_ID' => $sectionId,
				'DOCUMENT_TYPE' => array('webdav', 'CIBlockDocumentWebdavSocnet', 'iblock_'.$sectionId.'_user_'.$user->GetID())
			)
		);

		return $this->webdavIBlock;
	}
	protected function getWebDavSettings()
	{
		if($this->webdavSettings !== null)
		{
			return $this->webdavSettings;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		$opt = COption::getOptionString('webdav', 'user_files', null);
		if($opt == null)
		{
			throw new RestException('Could not find webdav settings.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();

		$opt = unserialize($opt);
		$iblockID = intval($opt[CSite::GetDefSite()]['id']);
		$userSectionID = CWebDavIblock::getRootSectionIdForUser($iblockID, $user->GetID());
		if(!is_numeric($userSectionID) || $userSectionID <= 0)
		{
			throw new RestException('Could not find webdav section for user '.$user->GetLastName().'.');
		}

		return ($this->webdavSettings =
			array(
				'IBLOCK_ID' => $iblockID,
				'IBLOCK_SECTION_ID' => intval($userSectionID),
			)
		);
	}
	// <-- WebDav
	protected function getFieldsInfo()
	{
		throw new RestException('The method is not implemented.');
	}
	protected function sanitizeHtml($html)
	{
		$html = strval($html);
		if($html === '' || strpos($html, '<') === false)
		{
			return $html;
		}

		if($this->sanitizer === null)
		{
			$this->sanitizer = new CBXSanitizer();
			$this->sanitizer->ApplyDoubleEncode(false);
			$this->sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
		}

		return $this->sanitizer->SanitizeHtml($html);
	}
	protected function getIdentityFieldName()
	{
		return '';
	}
	protected function getIdentity(&$fields)
	{
		return 0;
	}
	protected static function getMultiFieldTypeIDs()
	{
		if(self::$MULTIFIELD_TYPE_IDS === null)
		{
			self::$MULTIFIELD_TYPE_IDS = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		}

		return self::$MULTIFIELD_TYPE_IDS;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return null;
	}
	protected function prepareListItemMultiFields(&$entityMap, $entityTypeID, $typeIDs)
	{
		$entityIDs = array_keys($entityMap);
		if(empty($entityIDs))
		{
			return;
		}

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetListEx(
			array(),
			array(
				'=ENTITY_ID' => $entityTypeName,
				'@ELEMENT_ID' => $entityIDs,
				'@TYPE_ID' => $typeIDs
			)
		);

		while($fm = $dbResult->Fetch())
		{
			$typeID = isset($fm['TYPE_ID']) ? $fm['TYPE_ID'] : '';
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			$entityID = isset($fm['ELEMENT_ID']) ? intval($fm['ELEMENT_ID']) : 0;
			if(!isset($entityMap[$entityID]))
			{
				continue;
			}

			$entity = &$entityMap[$entityID];
			if(!isset($entity['FM']))
			{
				$entity['FM'] = array();
			}

			if(!isset($entity['FM'][$typeID]))
			{
				$entity['FM'][$typeID] = array();
			}

			$entity['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
			unset($entity);
		}
	}
	protected function prepareMultiFieldData($entityTypeID, $entityID, &$entityFields, $typeIDs = null)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		if(!CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID
			)
		);

		if(!is_array($typeIDs) || empty($typeIDs))
		{
			$typeIDs = self::getMultiFieldTypeIDs();
		}

		$entityFields['FM'] = array();
		while($fm = $dbResult->Fetch())
		{
			$typeID = $fm['TYPE_ID'];
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			if(!isset($entityFields['FM'][$typeID]))
			{
				$entityFields['FM'][$typeID] = array();
			}

			$entityFields['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}
	}
}

class CCrmEnumerationRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
			);
		}
		return $this->FIELDS_INFO;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$descriptions = null;

		$name = strtoupper($name);
		if($name === 'OWNERTYPE')
		{
			$descriptions = CCrmOwnerType::GetDescriptions(
				array(
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company
				)
			);
		}
		elseif($name === 'CONTENTTYPE')
		{
			$descriptions = CCrmContentType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYTYPE')
		{
			$descriptions = CCrmActivityType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYPRIORITY')
		{
			$descriptions = CCrmActivityPriority::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYDIRECTION')
		{
			$descriptions = CCrmActivityDirection::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYNOTIFYTYPE')
		{
			$descriptions = CCrmActivityNotifyType::GetAllDescriptions();
		}

		if(!is_array($descriptions))
		{
			return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}

		$result = array();
		foreach($descriptions as $k => &$v)
		{
			$result[] = array('ID' => $k, 'NAME' => $v);
		}
		unset($v);
		return $result;
	}
}

class CCrmMultiFieldRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'TYPE_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'VALUE' => array('TYPE' => 'string'),
				'VALUE_TYPE' => array('TYPE' => 'string')
			);
		}
		return $this->FIELDS_INFO;
	}
}

class CCrmCatalogRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCatalog::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	/*
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$originatorID = isset($fields['ORIGINATOR_ID']) ? $fields['ORIGINATOR_ID'] : '';
		$originID = isset($fields['ORIGIN_ID']) ? $fields['ORIGIN_ID'] : '';
		$name = isset($fields['NAME']) ? $fields['NAME'] : '';

		$result = CCrmCatalog::CreateCatalog($originatorID, $name);
		if(!(is_int($result) && $result > 0))
		{
			$errors[] = CCrmCatalog::GetLastError();
			return $result;
		}

		if($originID !== '')
		{
			CCrmCatalog::Update($result, array('ORIGIN_ID' => $originID));
		}
		return $result;
	}
	*/
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCatalog::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Catalog is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCatalog::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	/*
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCatalog::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmCatalog::GetLastError();
		}
		return $result;
	}

	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmProduct::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCatalog::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmCatalog::GetLastError();
		}
		return $result;
	}
	*/
}

class CCrmProductRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProduct::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProduct::Add($fields);
		if(!is_int($result))
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$filter = array('ID' => $ID);
		$dbResult = CCrmProduct::GetList(array(), $filter, array('*'), array('nTopCount' => 1));
		if($dbResult)
		{
			return $dbResult->GetNext();
		}

		$errors[] = 'Product is not found.';
		return null;
	}
	public function getList($order, $filter, $select, $start)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		$navigation = CCrmRestService::getNavData($start);

		if(!is_array($order) || empty($order))
		{
			$order = array('sort' => 'asc');
		}

		if(!isset($navigation['bShowAll']))
		{
			$navigation['bShowAll'] = false;
		}

		$enableCatalogData = false;
		$catalogSelect = null;
		$priceSelect = null;
		$vatSelect = null;

		if(is_array($select))
		{
			if(!empty($select))
			{
				// Remove '*' for get rid of inefficient construction of price data
				foreach($select as $k => $v)
				{
					if($v === '*')
					{
						unset($select[$k]);
						break;
					}
				}
			}

			if(empty($select))
			{
				$priceSelect = array('PRICE', 'CURRENCY_ID');
				$vatSelect = array('VAT_ID', 'VAT_INCLUDED', 'MEASURE');
			}
			else
			{
				$priceSelect = array();
				$vatSelect = array();

				$select = CCrmProduct::DistributeProductSelect($select, $priceSelect, $vatSelect);
			}

			$catalogSelect = array_merge($priceSelect, $vatSelect);
			$enableCatalogData = !empty($catalogSelect);
		}

		$dbResult = CCrmProduct::GetList($order, $filter, $select, $navigation);
		if(!$enableCatalogData)
		{
			$result = array();
			$fieldsInfo = $this->getFieldsInfo();
			while($fields = $dbResult->Fetch())
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$itemMap = array();
			$itemIDs = array();
			while($fields = $dbResult->Fetch())
			{
				foreach ($catalogSelect as $fieldName)
				{
					$fields[$fieldName] = null;
				}

				$itemID = isset($fields['ID']) ? intval($fields['ID']) : 0;
				if($itemID > 0)
				{
					$itemIDs[] = $itemID;
					$itemMap[$itemID] = $fields;
				}

			}
			CCrmProduct::ObtainPricesVats($itemMap, $itemIDs, $priceSelect, $vatSelect, true);

			$result = array_values($itemMap);
			$fieldsInfo = $this->getFieldsInfo();
			foreach($result as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProduct::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmProduct::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProduct::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
}

class CCrmProductSectionRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductSection::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Add($fields);
		if(!(is_int($result) && $result > 0))
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Product section is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductSection::GetList($order, $filter, $select, $navigation);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmProduct::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
}

class CCrmProductRowRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductRow::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		$ownerType = isset($fields['OWNER_TYPE']) ? $fields['OWNER_TYPE'] : '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required.';
			}
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckCreatePermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Add($fields, true, true);
		if(!is_int($result))
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		$result = CCrmProductRow::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = "Product Row not found";
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($result['OWNER_TYPE']),
			$result['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$ownerID = isset($filter['OWNER_ID']) ? intval($filter['OWNER_ID']) : 0;
		$ownerType = isset($filter['OWNER_TYPE']) ? $filter['OWNER_TYPE'] : '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required in filer.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required in filer.';
			}
			return false;
		}

		if($ownerType === 'I')
		{
			//Crutch for Invoices
			if(!CCrmInvoice::CheckReadPermission($ownerID))
			{
				$errors[] = 'Access denied.';
				return false;
			}

			$result = array();
			$productRows = CCrmInvoice::GetProductRows($ownerID);
			foreach($productRows as $productRow)
			{
				$price = isset($productRow['PRICE']) ? $productRow['PRICE'] : 0.0;
				$discountSum = isset($productRow['DISCOUNT_PRICE']) ? $productRow['DISCOUNT_PRICE'] : 0.0;
				$taxRate = isset($productRow['VAT_RATE']) ? $productRow['VAT_RATE'] * 100 : 0.0;

				$exclusivePrice = CCrmProductRow::CalculateExclusivePrice($price, $taxRate);
				$discountRate = \Bitrix\Crm\Discount::calculateDiscountRate(($exclusivePrice + $discountSum), $exclusivePrice);

				$result[] = array(
					'ID' => $productRow['ID'],
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE' => 'I',
					'PRODUCT_ID' => isset($productRow['PRODUCT_ID']) ? $productRow['PRODUCT_ID'] : 0,
					'PRODUCT_NAME' => isset($productRow['PRODUCT_NAME']) ? $productRow['PRODUCT_NAME'] : '',
					'PRICE' => $price,
					'QUANTITY' => isset($productRow['QUANTITY']) ? $productRow['QUANTITY'] : 0,
					'DISCOUNT_TYPE_ID' => \Bitrix\Crm\Discount::MONETARY,
					'DISCOUNT_RATE' => $discountRate,
					'DISCOUNT_SUM' => $discountSum,
					'TAX_RATE' => $taxRate,
					'TAX_INCLUDED' => 'Y',
					'MEASURE_CODE' => isset($productRow['MEASURE_CODE']) ? $productRow['MEASURE_CODE'] : '',
					'MEASURE_NAME' => isset($productRow['MEASURE_NAME']) ? $productRow['MEASURE_NAME'] : '',
					'CUSTOMIZED' => isset($productRow['CUSTOM_PRICE']) ? $productRow['CUSTOM_PRICE'] : 'N',
				);
			}
			return $result;
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType),
			$ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductRow::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row not found";
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckUpdatePermission(
			CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']),
			$entity['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		// The fields OWNER_ID and OWNER_TYPE can not be changed.
		if(isset($fields['OWNER_ID']))
		{
			unset($fields['OWNER_ID']);
		}

		if(isset($fields['OWNER_TYPE']))
		{
			unset($fields['OWNER_TYPE']);
		}

		$result = CCrmProductRow::Update($ID, $fields, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row not found";
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckDeletePermission(
			CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']),
			$entity['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Delete($ID, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}

	public function prepareForSave(&$fields)
	{
		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldsInfo);
	}
}

class CCrmLeadRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Lead;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmLead(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmLead::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmLead::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmLead::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Add($fields, true, array());
		if(!is_int($result) || $result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmLead::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmLead::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmLead::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmLead::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmLead::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Update($ID, $fields, true, true, array());
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmLead::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID, array('CHECK_DEPENDENCIES' => true));
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmLead::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmLead::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmLead::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmLead::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
}

class CCrmDealRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Deal;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmDeal(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmDeal::GetFieldsInfo();
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmDeal::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmDeal::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Add($fields, true, array());
		if(!is_int($result) || $result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmDeal::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmDeal::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STAGE_ID']))
			{
				$order['STAGE_SORT'] = $order['STAGE_ID'];
				unset($order['STAGE_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STAGE_SORT'));
			}
		}

		return CCrmDeal::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmDeal::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Update($ID, $fields, true, true, array());
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmDeal::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmDeal::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmDeal::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmDeal::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmDeal::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmDeal::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
}

class CCrmCompanyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Company;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCompany::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmCompany::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmCompany(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmCompany::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Add($fields, true, array());
		if(!is_int($result) || $result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmCompany::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCompany::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmCompany::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Update($ID, $fields, true, true, array());
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmCompany::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
}

class CCrmContactRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Contact;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmContact::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmContact::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmContact(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmContact::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Add($fields, true, array());
		if(!is_int($result) || $result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmContact::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmContact::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmContact::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmContact::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmContact::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmContact::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$result = $entity->Update($ID, $fields, true, true, array());
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmContact::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
}

class CCrmCurrencyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCurrency::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	public function isValidID($ID)
	{
		return is_string($ID) && $ID !== '';
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmCurrency::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Add($fields);
		if($result === false)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCurrency::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::GetByID($ID);
		if(is_array($result))
		{
			return $result;
		}

		$errors[] = 'Not found';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCurrency::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCurrency::GetList($order);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmCurrency::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}

		return $result;
	}
	protected function resolveEntityID(&$arParams)
	{
		return isset($arParams['ID'])
			? strtoupper($arParams['ID'])
			: (isset($arParams['id']) ? strtoupper($arParams['id']) : '');
	}
	protected function checkEntityID($ID)
	{
		return is_string($ID) && $ID !== '';
	}

	public function getLocalizations($ID)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmCurrency::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::GetCurrencyLocalizations($ID);
	}
	public function setLocalizations($ID, $localizations)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($localizations) || empty($localizations))
		{
			return false;
		}

		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::SetCurrencyLocalizations($ID, $localizations);
	}
	public function deleteLocalizations($ID, $langs)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($langs) || empty($langs))
		{
			return false;
		}

		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::DeleteCurrencyLocalizations($ID, $langs);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'LOCALIZATIONS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return $this->getLocalizations($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$localizations = $this->resolveArrayParam($arParams, 'localizations');
				return $this->setLocalizations($ID, $localizations);
			}
			elseif($nameSuffix === 'DELETE')
			{
				$ID = $this->resolveEntityID($arParams);
				$lids = $this->resolveArrayParam($arParams, 'lids');
				return $this->deleteLocalizations($ID, $lids);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmStatusRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY_TYPES = null;

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatus::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors)
	{
		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($entityID === '' || $statusID === '')
		{
			if($entityID === '')
			{
				$errors[] = 'The field ENTITY_ID is required.';
			}

			if($statusID === '')
			{
				$errors[] = 'The field STATUS_ID is required.';
			}

			return false;
		}

		$entityTypes = self::prepareEntityTypes();
		if(!isset($entityTypes[$entityID]))
		{
			$errors[] = 'Specified entity type is not supported.';
			return false;
		}

		$fields['SYSTEM'] = 'N';
		$entity = new CCrmStatus($entityID);
		$result = $entity->Add($fields, true);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		if($dbResult)
		{
			return $dbResult->Fetch();
		}

		$errors[] = 'CRM Status is not found.';
		return null;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['sort'] = 'asc';
		}
		return CCrmStatus::GetList($order, $filter);
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'CRM Status is not found.';
			return false;
		}

		$entity = new CCrmStatus($currentFields['ENTITY_ID']);

		$result = $entity->Update($ID, $fields);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result !== false;

	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'CRM Status is not found.';
			return false;
		}

		$entity = new CCrmStatus($currentFields['ENTITY_ID']);
		$result = $entity->Delete($ID);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result !== false;
	}

	private static function prepareEntityTypes()
	{
		if(!self::$ENTITY_TYPES)
		{
			self::$ENTITY_TYPES = CCrmStatus::GetEntityTypes();
		}

		return self::$ENTITY_TYPES;
	}

	public function getEntityTypes()
	{
		return array_values(self::prepareEntityTypes());
	}

	public function getEntityItems($entityID)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		if($entityID === '')
		{
			throw new RestException('The parameter entityId is not defined or invalid.');
		}

		//return CCrmStatus::GetStatusList($entityID);
		$dbResult = CCrmStatus::GetList(array('sort' => 'asc'), array('ENTITY_ID' => strtoupper($entityID)));
		if(!$dbResult)
		{
			return array();
		}

		$result = array();
		while($fields = $dbResult->Fetch())
		{
			$result[] = array(
				'NAME' => $fields['NAME'],
				'SORT' => intval($fields['SORT']),
				'STATUS_ID' => $fields['STATUS_ID']
			);
		}

		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'ENTITY')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'TYPES')
			{
				return $this->getEntityTypes();
			}
			elseif($nameSuffix === 'ITEMS')
			{
				return $this->getEntityItems($this->resolveRelationID($arParams, 'entity'));
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmActivityRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $COMM_FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmActivity::GetFieldsInfo();
			$this->FIELDS_INFO['COMMUNICATIONS'] = array(
				'TYPE' => 'crm_activity_communication',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);

			$storageTypeID =  CCrmActivity::GetDefaultStorageTypeID();
			if($storageTypeID === StorageType::Disk)
			{
				$this->FIELDS_INFO['FILES'] = array(
					'TYPE' => 'diskfile',
					'ALIAS' => 'WEBDAV_ELEMENTS',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple),
				);
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'diskfile',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated, CCrmFieldInfoAttr::Multiple)
				);
			}
			else
			{
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'webdav',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				);
			}
			$this->FIELDS_INFO['BINDINGS'] = array(
				'TYPE' => 'crm_activity_binding',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple, CCrmFieldInfoAttr::ReadOnly)
			);
		}
		return $this->FIELDS_INFO;
	}
	protected function getCommunicationFieldsInfo()
	{
		if(!$this->COMM_FIELDS_INFO)
		{
			$this->COMM_FIELDS_INFO = CCrmActivity::GetCommunicationFieldsInfo();
		}
		return $this->COMM_FIELDS_INFO;
	}

	protected function prepareCommunications($ownerTypeID, $ownerID, $typeID, &$communications, &$bindings)
	{
		foreach($communications as $k => &$v)
		{
			$commEntityTypeID = $v['ENTITY_TYPE_ID'] ? intval($v['ENTITY_TYPE_ID']) : 0;
			$commEntityID = $v['ENTITY_ID'] ? intval($v['ENTITY_ID']) : 0;
			$commValue = $v['VALUE'] ? $v['VALUE'] : '';
			$commType = $v['TYPE'] ? $v['TYPE'] : '';

			if($commValue !== '' && ($commEntityTypeID <= 0 || $commEntityID <= 0))
			{
				// Push owner info into communication (if ommited)
				$commEntityTypeID = $v['ENTITY_TYPE_ID'] = $ownerTypeID;
				$commEntityID = $v['ENTITY_ID'] = $ownerID;
			}

			if($commEntityTypeID <= 0 || $commEntityID <= 0 || $commValue === '')
			{
				unset($communications[$k]);
				continue;
			}

			if($commType === '')
			{
				if($typeID === CCrmActivityType::Call)
				{
					$v['TYPE'] = 'PHONE';
				}
				elseif($typeID === CCrmActivityType::Email)
				{
					$v['TYPE'] = 'EMAIL';
				}
			}
			elseif(($typeID === CCrmActivityType::Call && $commType !== 'PHONE')
				|| ($typeID === CCrmActivityType::Email && $commType !== 'EMAIL'))
			{
				// Invalid communication type is specified
				unset($communications[$k]);
				continue;
			}

			$bindings["{$commEntityTypeID}_{$commEntityID}"] = array(
				'OWNER_TYPE_ID' => $commEntityTypeID,
				'OWNER_ID' => $commEntityID
			);
		}
		unset($v);
	}
	protected function innerAdd(&$fields, &$errors)
	{
		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;

		$bindings = array();
		if($ownerTypeID > 0 && $ownerID > 0)
		{
			$bindings["{$ownerTypeID}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		$responsibleID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
		if($responsibleID <= 0 && $ownerTypeID > 0 && $ownerID > 0)
		{
			$fields['RESPONSIBLE_ID'] = $responsibleID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID);
		}

		if($responsibleID <= 0)
		{
			$responsibleID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if($responsibleID <= 0)
		{
			$errors[] = 'The field RESPONSIBLE_ID is not defined or invalid.';
			return false;
		}

		$typeID = isset($fields['TYPE_ID']) ? intval($fields['TYPE_ID']) : CCrmActivityType::Undefined;
		if(!CCrmActivityType::IsDefined($typeID))
		{
			$errors[] = 'The field TYPE_ID is not defined or invalid.';
			return false;
		}

		if(!in_array($typeID, array(CCrmActivityType::Call, CCrmActivityType::Meeting, CCrmActivityType::Email), true))
		{
			$errors[] = 'The activity type "'.CCrmActivityType::ResolveDescription($typeID).' is not supported in current context".';
			return false;
		}

		$description = isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '';
		$descriptionType = isset($fields['DESCRIPTION_TYPE']) ? intval($fields['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;
		if($description !== '' && CCrmActivity::AddEmailSignature($description, $descriptionType))
		{
			$fields['DESCRIPTION'] = $description;
		}

		$direction = isset($fields['DIRECTION']) ? intval($fields['DIRECTION']) : CCrmActivityDirection::Undefined;
		$completed = isset($fields['COMPLETED']) && strtoupper($fields['COMPLETED']) === 'Y';
		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : array();

		$this->prepareCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

		if(empty($communications))
		{
			$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
			return false;
		}

		if(($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)
			&& count($communications) > 1)
		{
			$errors[] = 'The only one communication is allowed for activity of specified type.';
			return false;
		}

		if(empty($bindings))
		{
			$errors[] = 'Could not build binding. Please ensure that owner info and communications are defined correctly.';
			return false;
		}

		foreach($bindings as &$binding)
		{
			if(!CCrmActivity::CheckUpdatePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}
		unset($binding);

		$fields['BINDINGS'] = array_values($bindings);
		$fields['COMMUNICATIONS'] = $communications;
		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();

		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}
			}
			unset($element);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		if(!($ID = CCrmActivity::Add($fields)))
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
			return false;
		}

		CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);

		if($completed
			&& $typeID === CCrmActivityType::Email
			&& $direction === CCrmActivityDirection::Outgoing)
		{
			$sendErrors = array();
			if(!CCrmActivityEmailSender::TrySendEmail($ID, $fields, $sendErrors))
			{
				foreach($sendErrors as &$error)
				{
					$code = $error['CODE'];
					if($code === CCrmActivityEmailSender::ERR_CANT_LOAD_SUBSCRIBE)
					{
						$errors[] = 'Email send error. Failed to load module "subscribe".';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_DATA)
					{
						$errors[] = 'Email send error. Invalid data.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_EMAIL)
					{
						$errors[] = 'Email send error. Invalid email is specified.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_FROM)
					{
						$errors[] = 'Email send error. "From" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_TO)
					{
						$errors[] = 'Email send error. "To" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_ADD_POSTING)
					{
						$errors[] = 'Email send error. Failed to add posting.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_UPDATE_ACTIVITY)
					{
						$errors[] = 'Email send error. Failed to update activity.';
					}
					else
					{
						$errors[] = 'Email send error. Genaral error.';
					}
				}
				unset($error);
				return false;
			}
		}
		return $ID;
	}
	protected function innerGet($ID, &$errors)
	{
		// Permissions will be checked by default
		$dbResult = CCrmActivity::GetList(array(), array('ID' => $ID));
		if($dbResult)
		{
			return $dbResult->Fetch();
		}

		$errors[] = 'Activity is not found.';
		return null;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['START_TIME'] = 'ASC';
		}

		// Permissions will be checked by default
		return CCrmActivity::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		CCrmActivity::PrepareStorageElementIDs($currentFields);

		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		$typeID = intval($currentFields['TYPE_ID']);
		$currentOwnerID = intval($currentFields['OWNER_ID']);
		$currentOwnerTypeID = intval($currentFields['OWNER_TYPE_ID']);

		if(!CCrmActivity::CheckUpdatePermission($currentOwnerTypeID, $currentOwnerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		if($ownerID <= 0)
		{
			$ownerID = $currentOwnerID;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		if($ownerTypeID <= 0)
		{
			$ownerTypeID = $currentOwnerTypeID;
		}

		if(($ownerTypeID !== $currentOwnerTypeID || $ownerID !== $currentOwnerID)
			&& !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : null;

		if(is_array($communications))
		{
			$bindings = array();
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				$bindings["{$ownerTypeID}_{$ownerID}"] = array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID
				);
			}

			$this->prepareCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

			if(empty($communications))
			{
				$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
				return false;
			}

			$fields['BINDINGS'] = array_values($bindings);
			$fields['COMMUNICATIONS'] = $communications;
		}


		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();
		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			$prevStorageElementIDs = isset($currentFields['STORAGE_ELEMENT_IDS']) ? $currentFields['STORAGE_ELEMENT_IDS'] : array();
			$oldStorageElementIDs = array();
			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}

				$oldElementID = isset($element['OLD_ELEMENT_ID']) ? intval($element['OLD_ELEMENT_ID']) : 0;
				if($oldElementID > 0
					&& ($elementID > 0 || (isset($element['DELETE']) && $element['DELETE'] === true)))
				{
					if(in_array($oldElementID, $prevStorageElementIDs))
					{
						$oldStorageElementIDs[] = $oldElementID;
					}
				}
			}
			unset($element);
		}
		else if($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		$result = CCrmActivity::Update($ID, $fields, false, true, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}
		else
		{
			if(is_array($communications))
			{
				CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);
			}

			if(!empty($oldStorageElementIDs))
			{
				$webdavIBlock = $this->prepareWebDavIBlock();
				foreach($oldStorageElementIDs as $elementID)
				{
					$webdavIBlock->Delete(array('element_id' => $elementID));
				}
			}
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		if(!CCrmActivity::CheckDeletePermission(
			$currentFields['OWNER_TYPE_ID'], $currentFields['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmActivity::Delete($ID, false, true, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}

		return $result;
	}
	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		if(isset($fields['STORAGE_ELEMENT_IDS']))
		{
			CCrmActivity::PrepareStorageElementIDs($fields);
			$fields['WEBDAV_ELEMENTS'] = $fields['STORAGE_ELEMENT_IDS'];
			unset($fields['STORAGE_ELEMENT_IDS']);
		}
		parent::externalizeFields($fields, $fieldsInfo);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'COMMUNICATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$fieldsInfo = $this->getCommunicationFieldsInfo();
				return parent::prepareFields($fieldsInfo);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmDuplicateRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if(!CCrmLead::CheckReadPermission(0, $userPerms)
			&& !CCrmContact::CheckReadPermission(0, $userPerms)
			&& !CCrmCompany::CheckReadPermission(0, $userPerms))
		{
			throw new RestException('Access denied.');
		}

		if(strtoupper($name) === 'FINDBYCOMM')
		{
			$type = strtoupper($this->resolveParam($arParams, 'type'));
			if($type !== 'EMAIL' && $type !== 'PHONE')
			{
				if($type === '')
				{
					throw new RestException("Communication type is not defined.");
				}
				else
				{
					throw new RestException("Communication type '{$type}' is not supported in current context.");
				}
			}

			$values = $this->resolveArrayParam($arParams, 'values');
			if(!is_array($values) || count($values) === 0)
			{
				throw new RestException("Communication values is not defined.");
			}

			$entityTypeID = CCrmOwnerType::ResolveID(
				$this->resolveMultiPartParam($arParams, array('entity', 'type'))
			);

			if($entityTypeID === CCrmOwnerType::Deal)
			{
				throw new RestException("Deal is not supported in current context.");
			}

			$criterions = array();
			$dups = array();
			$qty = 0;
			foreach($values as $value)
			{
				if(!is_string($value) || $value === '')
				{
					continue;
				}

				$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($type, $value);
				$isExists = false;
				foreach($criterions as $curCriterion)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateCriterion $curCriterion */
					if($criterion->equals($curCriterion))
					{
						$isExists = true;
						break;
					}
				}

				if($isExists)
				{
					continue;
				}
				$criterions[] = $criterion;

				$duplicate = $criterion->find($entityTypeID, 20);
				if($duplicate !== null)
				{
					$dups[] = $duplicate;
				}

				$qty++;
				if($qty >= 20)
				{
					break;
				}
			}

			$entityByType = array();
			foreach($dups as $dup)
			{
				/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
				$entities = $dup->getEntities();
				if(!(is_array($entities) && !empty($entities)))
				{
					continue;
				}

				//Each entity type limited by 50 items
				foreach($entities as $entity)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
					$entityTypeID = $entity->getEntityTypeID();
					$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

					$entityID = $entity->getEntityID();

					if(!isset($entityByType[$entityTypeName]))
					{
						$entityByType[$entityTypeName] = array($entityID);
					}
					elseif(!in_array($entityID, $entityByType[$entityTypeName], true))
					{
						$entityByType[$entityTypeName][] = $entityID;
					}
				}
			}
			return $entityByType;
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmLiveFeedMessageRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$arComponentResult = array(
				'USER_ID' => $this->getCurrentUserID()
			);

			$arPOST = array(
				'ENABLE_POST_TITLE' => 'Y',
				'MESSAGE' => $fields['MESSAGE'],
				'SPERM' => $fields['SPERM']
			);

			if (
				isset($fields['POST_TITLE']) 
				&& strlen($fields['POST_TITLE']) > 0
			)
			{
				$arPOST['POST_TITLE'] = $fields['POST_TITLE'];
			}

			$entityTypeID = $fields['ENTITYTYPEID'];
			$entityID = $fields['ENTITYID'];

			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);			
			$userPerms = CCrmPerms::GetCurrentUserPermissions();

			if(
				$entityTypeName !== '' 
				&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $userPerms)
			)
			{
				throw new RestException('Access denied.');
			}

			$res = CCrmLiveFeedComponent::ProcessLogEventEditPOST($arPOST, $entityTypeID, $entityID, $arComponentResult);

			if(is_array($res))
			{
				throw new RestException(implode(", ", $res));
			}

			return $res;
		}

		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmUserFieldRestProxy extends UserFieldProxy
{
	private $ownerTypeID = CCrmOwnerType::Undefined;
	private $server = null;

	function __construct($ownerTypeID, \CUser $user = null)
	{
		$this->ownerTypeID = CCrmOwnerType::IsDefined($ownerTypeID) ? $ownerTypeID : CCrmOwnerType::Undefined;
		parent::__construct(CCrmOwnerType::ResolveUserFieldEntityID($this->ownerTypeID), $user);
		$this->setNamePrefix('crm');
	}
	public function getOwnerTypeID()
	{
		return $this->ownerTypeID;
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer($server)
	{
		$this->server = $server;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return self::getFields();
		}
		elseif($name === 'SETTINGS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$type = CCrmRestHelper::resolveParam($arParams, 'type', '');
				if($type === '')
				{
					throw new RestException("Parameter 'type' is not specified or empty.");
				}

				return self::getSettingsFields($type);
			}
		}
		elseif($name === 'ENUMERATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getEnumerationElementFields();
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function isAuthorizedUser()
	{
		if($this->isAuthorizedUser === null)
		{
			/**@var \CCrmPerms $userPermissions @**/
			$userPermissions = CCrmPerms::GetUserPermissions($this->user->GetID());
			$this->isAuthorizedUser = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		}
		return $this->isAuthorizedUser;
	}
	protected function checkCreatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkReadPermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkUpdatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkDeletePermission()
	{
		return $this->isAuthorizedUser();
	}
}
