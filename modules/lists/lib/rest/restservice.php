<?php
namespace Bitrix\Lists\Rest;

use \Bitrix\Main\Loader;
use \Bitrix\Rest\RestException;
use \Bitrix\Rest\AccessException;

Loader::includeModule('rest');

class RestService extends \IRestService
{
	const SCOPE = 'lists';
	const ENTITY_LISTS_CODE_PREFIX = 'REST';

	const ERROR_REQUIRED_PARAMETERS_MISSING = 'ERROR_REQUIRED_PARAMETERS_MISSING';
	const ERROR_IBLOCK_ALREADY_EXISTS = 'ERROR_IBLOCK_ALREADY_EXISTS';
	const ERROR_SAVE_IBLOCK = 'ERROR_SAVE_IBLOCK';
	const ERROR_IBLOCK_NOT_FOUND = 'ERROR_IBLOCK_NOT_FOUND';
	const ERROR_SAVE_FIELD = 'ERROR_SAVE_FIELD';
	const ERROR_PROPERTY_ALREADY_EXISTS = 'ERROR_PROPERTY_ALREADY_EXISTS';
	const ERROR_SAVE_ELEMENT = 'ERROR_SAVE_ELEMENT';
	const ERROR_DELETE_ELEMENT = 'ERROR_DELETE_ELEMENT';
	const ERROR_BIZPROC = 'ERROR_BIZPROC';

	public static function onRestServiceBuildDescription()
	{
		return array(
			static::SCOPE => array(
				'lists.add' => array(__CLASS__, 'addLists'),
				'lists.get' => array(__CLASS__, 'getLists'),
				'lists.update' => array(__CLASS__, 'updateLists'),
				'lists.delete' => array(__CLASS__, 'deleteLists'),

				'lists.field.add' => array(__CLASS__, 'addField'),
				'lists.field.get' => array(__CLASS__, 'getFields'),
				'lists.field.update' => array(__CLASS__, 'updateField'),
				'lists.field.delete' => array(__CLASS__, 'deleteField'),
				'lists.field.type.get' => array(__CLASS__, 'getFieldTypes'),

				'lists.element.add' => array(__CLASS__, 'addElement'),
				'lists.element.get' => array(__CLASS__, 'getElement'),
				'lists.element.update' => array(__CLASS__, 'updateElement'),
				'lists.element.delete' => array(__CLASS__, 'deleteElement'),
			)
		);
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function addLists($params, $n, $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		if(empty($params['IBLOCK_TYPE_ID']) || empty($params['IBLOCK_CODE']) || empty($params['FIELDS']['NAME']))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);
		if(!empty($params['SOCNET_GROUP_ID']))
			$params['SOCNET_GROUP_ID'] = intval($params['SOCNET_GROUP_ID']);
		$params['IBLOCK_ID'] = false;

		self::checkIblockPermission($params);
		$listIblock = self::getIblocksData($params);

		if(empty($listIblock))
		{
			if(empty($params['RIGHTS']) || !is_array($params['RIGHTS']))
			{
				global $USER;
				$params['RIGHTS'] = array();
				$params['RIGHTS']['U'.$USER->getID()] = 'X';
			}

			$fields = self::prepareIblockFields($params);
			$fields['RIGHTS'] = self::prepareRights($params['RIGHTS']);

			if($params['SOCNET_GROUP_ID'])
				$fields['SOCNET_GROUP_ID'] = $params['SOCNET_GROUP_ID'];

			$iblockObject = new \CIBlock();
			$result = $iblockObject->add($fields);
			if($result)
			{
				if (!empty($fields['SOCNET_GROUP_ID']) && Loader::includeModule('socialnetwork'))
					\CSocNetGroup::setLastActivity($params['SOCNET_GROUP_ID']);

				return $result;
			}
			else
				throw new RestException($iblockObject->LAST_ERROR, self::ERROR_SAVE_IBLOCK);
		}
		else
		{
			throw new RestException('Iblock already exists', self::ERROR_IBLOCK_ALREADY_EXISTS);
		}
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function getLists($params, $n, $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		if(empty($params['IBLOCK_TYPE_ID']))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);
		if(!empty($params['SOCNET_GROUP_ID']))
			$params['SOCNET_GROUP_ID'] = intval($params['SOCNET_GROUP_ID']);
		if(empty($params['IBLOCK_ID']))
			$params['IBLOCK_ID'] = false;

		self::checkIblockPermission($params);
		$listIblock = self::getIblocksData($params);

		if(!empty($listIblock))
		{
			$listIblock['total'] = count($listIblock);
			return $listIblock;
		}
		else
		{
			return array();
		}
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function updateLists($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		$fields = self::prepareIblockFields($params);
		if(!empty($params['RIGHTS']) && is_array($params['RIGHTS']))
		{
			$iblockRights = new \CIBlockRights($params['IBLOCK_ID']);
			$rights = $iblockRights->getRights();
			foreach($rights as $rightId => $right)
				$fields['RIGHTS'][$rightId] = $right;
			$paramsRights = self::prepareRights($params['RIGHTS']);
			foreach($paramsRights as $id => $right)
				$fields['RIGHTS'][$id] = $right;
		}
		$iblockObject = new \CIBlock;
		if($iblockObject->update($params['IBLOCK_ID'], $fields))
			return true;
		else
			throw new RestException($iblockObject->LAST_ERROR, self::ERROR_SAVE_IBLOCK);
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function deleteLists($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		return \CIBlock::delete($params['IBLOCK_ID']);
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function addField($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		$object = new \CList($params['IBLOCK_ID']);
		$fields = self::prepareFields($params, $object);
		$result = $object->addField($fields);
		if($result)
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->clearByTag('lists_list_'.$params['IBLOCK_ID']);
			return $result;
		}
		else
			throw new RestException('Unknown error', self::ERROR_SAVE_FIELD);
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function getFields($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		$fields = array();
		if(!empty($params['FIELD_ID']))
		{
			$object = new \CListFieldList($params['IBLOCK_ID']);
			$fieldsObject = $object->getByID($params['FIELD_ID']);
			if($fieldsObject)
			{
				$fieldData = $fieldsObject->getArray();
				$fields = array($fieldData['TYPE'] => $fieldData);
			}
		}
		else
		{
			$object = new \CList($params['IBLOCK_ID']);
			$fields = $object->getFields();
		}

		foreach($fields as $fieldId => &$field)
		{
			if($field['TYPE'] == 'ACTIVE_FROM')
			{
				if($field['DEFAULT_VALUE'] === '=now')
					$field['DEFAULT_VALUE'] = ConvertTimeStamp(time()+\CTimeZone::getOffset(), 'FULL');
				elseif($field['DEFAULT_VALUE'] === '=today')
					$field['DEFAULT_VALUE'] = ConvertTimeStamp(time()+\CTimeZone::getOffset(), 'SHORT');
			}
			elseif($field['TYPE'] == 'L')
			{
				$option = array();
				$propertyEnum = \CIBlockProperty::getPropertyEnum($field['ID']);
				while($listEnum = $propertyEnum->fetch())
					$option[$listEnum['ID']] = $listEnum['VALUE'];
				$field['DISPLAY_VALUES_FORM'] = $option;
			}
			elseif($field['TYPE'] == 'G')
			{
				$option = array();
				$sections = \CIBlockSection::getTreeList(array('IBLOCK_ID' => $field['LINK_IBLOCK_ID']));
				while($section = $sections->getNext())
					$option[$section["ID"]] = str_repeat(" . ", $section["DEPTH_LEVEL"]).$section["~NAME"];
				$field['DISPLAY_VALUES_FORM'] = $option;
			}
			elseif(preg_match('/^(E|E:)/', $field['TYPE']))
			{
				$option = array();
				$elements = \CIBlockElement::getList(array('NAME'=>'ASC'),
					array('IBLOCK_ID' => $field['LINK_IBLOCK_ID']), false, false, array('ID', 'NAME'));
				while($element = $elements->fetch())
					$option[$element["ID"]] = $element["NAME"];
				$field['DISPLAY_VALUES_FORM'] = $option;
			}
			elseif($field['TYPE'] == 'N:Sequence')
			{
				$sequence = new \CIBlockSequence($field['IBLOCK_ID'], $field['ID']);
				$field['USER_TYPE_SETTINGS']['VALUE'] = $sequence->getNext();
			}
		}

		return $fields;
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function updateField($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		if(empty($params['FIELD_ID']))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);

		$object = new \CList($params['IBLOCK_ID']);
		$fields = self::prepareFields($params, $object, true);
		$result = $object->updateField($params['FIELD_ID'], $fields);
		if($result)
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->clearByTag('lists_list_'.$params['IBLOCK_ID']);
			return true;
		}
		else
			throw new RestException('Unknown error', self::ERROR_SAVE_FIELD);
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function deleteField($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		if(empty($params['FIELD_ID']))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);

		$object = new \CList($params['IBLOCK_ID']);
		$object->deleteField($params['FIELD_ID']);
		$object->save();

		global $CACHE_MANAGER;
		$CACHE_MANAGER->clearByTag('lists_list_'.$params['IBLOCK_ID']);

		return true;
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function getFieldTypes($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		self::checkIblockPermission($params);

		$fieldId = '';
		if(!empty($params['FIELD_ID']))
			$fieldId = $params['FIELD_ID'];

		$object = new \CList($params['IBLOCK_ID']);
		return $object->getAvailableTypes($fieldId);
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function addElement($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		if(empty($params['ELEMENT_CODE']))
			throw new RestException('Element code not found', self::ERROR_SAVE_ELEMENT);

		self::checkElementPermission($params);
		list($listElement, $elementSelect, $elementFields,
			$elementProperty, $queryObject) = self::getElementsData($params, $n);
		if(!empty($listElement))
			throw new RestException('Element already exists', self::ERROR_SAVE_ELEMENT);

		$object = new \CList($params['IBLOCK_ID']);
		if(!empty($params['LIST_ELEMENT_URL']))
		{
			$object->actualizeDocumentAdminPage(str_replace(
				array('#list_id#', '#group_id#'),
				array($params['IBLOCK_ID'], $params['SOCNET_GROUP_ID']),
				$params['LIST_ELEMENT_URL']
			));
		}

		$errors = '';
		list($element, $documentStates, $bizprocParameters) = self::prepareElementFields($params, $object, $errors);
		$params['ELEMENT_ID'] = false;
		if(empty($errors))
		{
			$elementObject = new \CIBlockElement;
			$params['ELEMENT_ID'] = $elementObject->add($element, false, true, true);
			if($params['ELEMENT_ID'])
			{
				$params['ELEMENT_NAME'] = $element['NAME'];
				if($params['ENABLED_BIZPROC'] && $params['TEMPLATES_ON_STARTUP'])
					self::startBizproc($params, $documentStates, $bizprocParameters, array(), $errors);
			}
			else
			{
				$errors = $elementObject->LAST_ERROR;
			}
		}

		if(!empty($errors))
			throw new RestException($errors, self::ERROR_SAVE_ELEMENT);
		else
			return $params['ELEMENT_ID'];
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function getElement($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);

		list($listElement, $elementSelect, $elementFields,
			$elementProperty, $queryObject) = self::getElementsData($params, $n);
		self::checkElementPermission($params);

		if(!empty($listElement))
			return self::setNavData(array_values($listElement), $queryObject);
		else
			return array();
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function updateElement($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		if(empty($params['ELEMENT_CODE']) && empty($params['ELEMENT_ID']))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);

		list($listElement, $elementSelect, $elementFields,
			$elementProperty, $queryObject) = self::getElementsData($params, $n);
		if(empty($listElement))
			throw new RestException('Element not found.', self::ERROR_SAVE_ELEMENT);
		self::checkElementPermission($params);

		$object = new \CList($params['IBLOCK_ID']);
		$errors = '';
		list($element, $documentStates, $bizprocParameters) = self::prepareElementFields($params, $object, $errors);
		if(empty($errors))
		{
			$elementObject = new \CIBlockElement;
			$params['ELEMENT_ID'] = $elementObject->update($element['ID'], $element, false, true, true);
			if($params['ELEMENT_ID'])
			{
				if($params['ENABLED_BIZPROC'] && $params['TEMPLATES_ON_STARTUP'])
				{
					$changedElementFields = \CLists::checkChangedFields(
						$params['IBLOCK_ID'], $params['ELEMENT_ID'], $elementSelect,
						$elementFields[$params['ELEMENT_ID']], $elementProperty);

					self::startBizproc($params, $documentStates, $bizprocParameters, $changedElementFields, $errors);
				}
			}
			else
			{
				if(!empty($elementObject->LAST_ERROR))
					$errors = $elementObject->LAST_ERROR;
				else
					$errors = 'Unknown error';
			}
		}

		if(!empty($errors))
			throw new RestException($errors, self::ERROR_SAVE_ELEMENT);
		else
			return true;
	}

	/**
	 * @param array $params The set of parameters.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function deleteElement($params, $n, $server)
	{
		$params = self::checkParamsIblock($params);
		$listIblock = self::getIblocksData($params);
		if(empty($listIblock))
			throw new RestException('Iblock not found', self::ERROR_IBLOCK_NOT_FOUND);
		if(empty($params['ELEMENT_CODE']) && empty($params['ELEMENT_ID']))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);
		list($listElement, $elementSelect, $elementFields,
			$elementProperty, $queryObject) = self::getElementsData($params, $n);
		if(empty($listElement))
			throw new RestException('Element not found.', self::ERROR_SAVE_ELEMENT);
		self::checkElementPermission($params);

		if(!$params['CAN_DELETE_ELEMENT'])
			throw new AccessException();

		$elementObject = new \CIBlockElement;
		global $DB, $APPLICATION;
		$DB->startTransaction();
		$APPLICATION->resetException();
		if(!$elementObject->delete(key($listElement)))
		{
			$DB->rollback();
			if($exception = $APPLICATION->getException())
				throw new RestException($exception->getString(), self::ERROR_DELETE_ELEMENT);
			else
				throw new RestException('Unknown error', self::ERROR_DELETE_ELEMENT);
		}
		else
		{
			$DB->commit();
			return true;
		}
	}

	/**
	 * @param array $params The set of parameters.
	 * @param \CList $object
	 * @param string $errors
	 * @return array
	 * @throws RestException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function prepareElementFields(array &$params, $object, &$errors)
	{
		if(empty($params['FIELDS']) || !is_array($params['FIELDS']))
			return array();

		$element = array(
			'IBLOCK_ID' => $params['IBLOCK_ID'],
			'CODE' => $params['ELEMENT_CODE'],
			'ID' => $params['ELEMENT_ID'],
			'PROPERTY_VALUES' => array()
		);

		$fields = $object->getFields();
		foreach($fields as $fieldId => $fieldData)
		{
			$fieldValue = $params['FIELDS'][$fieldId];
			if($object->is_field($fieldId))
			{
				$isField = true;
				if(is_array($fieldValue))
					$fieldValue = $fieldValue[0];
			}
			else
			{
				$isField = false;
				if(!is_array($fieldValue))
					$fieldValue = array($fieldValue);
			}

			if($isField)
			{
				if($fieldId == 'PREVIEW_PICTURE' || $fieldId == 'DETAIL_PICTURE')
				{
					$element[$fieldId] = \CRestUtil::saveFile($fieldValue);
					if(!empty($params['FIELDS'][$fieldId.'_DEL']))
						$element[$fieldId]['del'] = 'Y';
				}
				elseif($fieldId == 'PREVIEW_TEXT' || $fieldId == 'DETAIL_TEXT')
				{
					if(!empty($fieldData['SETTINGS']['USE_EDITOR']) && $fieldData['SETTINGS']['USE_EDITOR'] == 'Y')
						$element[$fieldId.'_TYPE'] = 'html';
					else
						$element[$fieldId.'_TYPE'] = 'text';
					$element[$fieldId] = $fieldValue;
				}
				else
				{
					$element[$fieldId] = $fieldValue;
				}
			}
			else
			{
				if($fieldData['TYPE'] == 'F')
				{
					if(!empty($params['FIELDS'][$fieldId.'_DEL']))
						$delete = $params['FIELDS'][$fieldId.'_DEL'];
					else
						$delete = array();

					foreach($fieldValue as $key => $value)
						$element['PROPERTY_VALUES'][$fieldData['ID']][$key]['VALUE'] = \CRestUtil::saveFile($value);

					foreach($delete as $fileId => $checked)
					{
						if(array_key_exists($fileId, $element['PROPERTY_VALUES'][$fieldData['ID']]))
							$element['PROPERTY_VALUES'][$fieldData['ID']][$fileId]['VALUE']['del'] = 'Y';
					}
				}
				elseif($fieldData['TYPE'] == 'N')
				{
					foreach($fieldValue as $key => $value)
					{
						$value = str_replace(' ', '', str_replace(',', '.', $value));
						if (!is_numeric($value))
							$errors .= 'Value of the "'.$fieldData['NAME'].'" field is not correct. ';
						$element['PROPERTY_VALUES'][$fieldData['ID']][$key]['VALUE'] = floatval($value);
					}
				}
				else
				{
					foreach($fieldValue as $key => $value)
					{
						if(is_array($value))
						{
							foreach($value as $k => $v)
								$element['PROPERTY_VALUES'][$fieldData['ID']][$k]['VALUE'] = $v;
						}
						else
							$element['PROPERTY_VALUES'][$fieldData['ID']][$key]['VALUE'] = $value;
					}
				}
			}
		}

		global $USER;
		$userId = $USER->getID();
		$element['MODIFIED_BY'] = $userId;
		unset($element['TIMESTAMP_X']);

		$params['ENABLED_BIZPROC'] = Loader::includeModule('bizproc') && ($params['BIZPROC'] === 'Y');
		$bizprocParameters = array();
		$documentStates = array();
		$params['TEMPLATES_ON_STARTUP'] = false;
		if($params['ENABLED_BIZPROC'])
		{
			$documentType = \BizProcDocument::generateDocumentComplexType($params['IBLOCK_TYPE_ID'], $params['IBLOCK_ID']);
			$documentStates = \CBPDocument::getDocumentStates($documentType, $params['ELEMENT_ID'] ?
				\BizProcDocument::getDocumentComplexId($params['IBLOCK_TYPE_ID'], $params['ELEMENT_ID']) : null);

			$currentUserGroups = $USER->getUserGroupArray();
			if(!$params['ELEMENT_ID'] || $element['CREATED_BY'] == $userId)
				$currentUserGroups[] = 'author';

			if($params['ELEMENT_ID'])
			{
				$canWrite = \CBPDocument::canUserOperateDocument(
					\CBPCanUserOperateOperation::WriteDocument, $userId,
					\BizProcDocument::getDocumentComplexId($params['IBLOCK_TYPE_ID'], $params['ELEMENT_ID']),
					array('AllUserGroups' => $currentUserGroups, 'DocumentStates' => $documentStates)
				);
			}
			else
			{
				$canWrite = \CBPDocument::canUserOperateDocumentType(
					\CBPCanUserOperateOperation::WriteDocument, $userId, $documentType,
					array('AllUserGroups' => $currentUserGroups, 'DocumentStates' => $documentStates)
				);
			}
			if(!$canWrite)
				throw new RestException('You do not have enough permissions to edit this record in its current state',
					self::ERROR_BIZPROC);

			$bizprocError = '';
			foreach ($documentStates as $documentState)
			{
				if(strlen($documentState['ID']) <= 0)
				{
					$params['TEMPLATES_ON_STARTUP'] = true;
					$bizprocErrors = array();
					$bizprocParameters[$documentState['TEMPLATE_ID']] = \CBPDocument::startWorkflowParametersValidate(
						$documentState['TEMPLATE_ID'],
						$documentState['TEMPLATE_PARAMETERS'],
						$documentType,
						$bizprocErrors
					);
					foreach($bizprocErrors as $message)
						$bizprocError .= $message['message'].' ';
				}
			}
			$templates = array_merge(
				\CBPWorkflowTemplateLoader::searchTemplatesByDocumentType($documentType, \CBPDocumentEventType::Create),
				\CBPWorkflowTemplateLoader::searchTemplatesByDocumentType($documentType, \CBPDocumentEventType::Edit)
			);
			foreach($templates as $template)
			{
				if(!\CBPWorkflowTemplateLoader::isConstantsTuned($template['ID']))
				{
					$bizprocError .= 'Workflow constants need to be configured. ';
					break;
				}
			}
			if(!empty($bizprocError))
				throw new RestException($bizprocError, self::ERROR_BIZPROC);
		}

		return array($element, $documentStates, $bizprocParameters);
	}

	private static function checkParamsIblock(array $params)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		if(empty($params['IBLOCK_TYPE_ID']) || (empty($params['IBLOCK_CODE']) && empty($params['IBLOCK_ID'])))
			throw new RestException('Required parameters are missing.', self::ERROR_REQUIRED_PARAMETERS_MISSING);
		if(!empty($params['SOCNET_GROUP_ID']))
			$params['SOCNET_GROUP_ID'] = intval($params['SOCNET_GROUP_ID']);
		if(empty($params['IBLOCK_ID']))
			$params['IBLOCK_ID'] = false;

		return $params;
	}

	/**
	 * @param array $params
	 * @param \CList $object
	 * @param bool $update
	 * @return array
	 * @throws RestException
	 */
	private static function prepareFields(array $params, $object, $update = false)
	{
		$fieldList = array('NAME', 'IS_REQUIRED', 'MULTIPLE', 'TYPE', 'SORT', 'DEFAULT_VALUE', 'LIST', 'USER_TYPE_SETTINGS',
			'LIST_TEXT_VALUES', 'LIST_DEF', 'CODE', 'SETTINGS', 'ROW_COUNT', 'COL_COUNT', 'LINK_IBLOCK_ID');
		$fields = array();
		if(!empty($params['FIELDS']) && is_array($params['FIELDS']))
		{
			foreach($params['FIELDS'] as $fieldId => $fieldValue)
			{
				$fieldId = strtoupper($fieldId);
				if(!in_array($fieldId, $fieldList))
					continue;
				if(is_array($fieldValue))
					$fieldValue = array_change_key_case($fieldValue, CASE_UPPER);

				$fields[$fieldId] = $fieldValue;
			}
		}

		$requiredFields = array('NAME', 'TYPE');
		foreach($requiredFields as $field)
		{
			if(empty($fields[$field]))
				throw new RestException('Please fill the required fields', self::ERROR_SAVE_FIELD);
		}

		if($update)
		{
			$objectFieldList = new \CListFieldList($params['IBLOCK_ID']);
			$fieldObject = $objectFieldList->getByID($params['FIELD_ID']);
			if($fieldObject)
			{
				$oldType = $fieldObject->GetTypeID();
				if($oldType != $fields['TYPE'])
					throw new RestException('Field type can not be changed', self::ERROR_SAVE_FIELD);
			}
		}

		if(!$update && !$object->is_field($fields['TYPE']))
		{
			if(!empty($fields['CODE']))
			{
				$property = self::getProperty($params['IBLOCK_ID'], $fields['CODE']);
				if(!empty($property) && is_array($property))
					throw new RestException('Property already exists', self::ERROR_PROPERTY_ALREADY_EXISTS);
			}
			else
			{
				throw new RestException('Please fill the code fields', self::ERROR_SAVE_FIELD);
			}
		}

		$error = '';
		if(isset($fields['SETTINGS']['ADD_READ_ONLY_FIELD']) && $fields['SETTINGS']['ADD_READ_ONLY_FIELD'] == 'Y')
		{
			switch($fields['TYPE'])
			{
				case 'SORT':
					if(strlen($fields['DEFAULT_VALUE']) <= 0)
						$error .= 'Default value is required. ';
					break;
				case 'L':
					if(is_array($fields['LIST_DEF']))
					{
						$listDefaultValue = current($fields['LIST_DEF']);
						if(empty($listDefaultValue))
							$error .= 'Default value is required. ';
					}
					break;
				case 'S:HTML':
					if(empty($fields['DEFAULT_VALUE']['TEXT']))
						$error .= 'Default value is required. ';
					break;
				default:
					if(empty($fields['DEFAULT_VALUE']))
						$error .= 'Default value is required. ';
			}
		}
		if(empty($fields['NAME']))
			$error .= 'Name is not specified. ';

		if($fields['TYPE'] == 'PREVIEW_PICTURE')
		{
			$fields['DEFAULT_VALUE']['METHOD'] = 'resample';
			$fields['DEFAULT_VALUE']['COMPRESSION'] = intval(\COption::getOptionString('main', 'image_resize_quality', '95'));
		}
		elseif($fields['TYPE'] == 'S:Date')
		{
			if(!empty($fields['DEFAULT_VALUE']) && !CheckDateTime($fields['DEFAULT_VALUE'], FORMAT_DATE))
			{
				$error .= 'The "Default value" field format is incorrect. ';
			}
		}
		elseif($fields['TYPE'] == 'S:DateTime')
		{
			if(!empty($fields['DEFAULT_VALUE']) && !CheckDateTime($fields['DEFAULT_VALUE']))
			{
				$error .= 'The "Default value" field format is incorrect. ';
			}
		}
		if(preg_match("/^(G|G:|E|E:)/", $fields["TYPE"]))
		{
			$fields['LINK_IBLOCK_ID'] = intval($fields['LINK_IBLOCK_ID']);
			$blocks = \CLists::getIBlocks($params['IBLOCK_TYPE_ID'], !$params['CAN_EDIT_IBLOCK'], $params['SOCNET_GROUP_ID']);

			if(substr($fields['TYPE'], 0, 1) == 'G')
				unset($blocks[$params['IBLOCK_ID']]);

			if(!array_key_exists($fields['LINK_IBLOCK_ID'], $blocks))
				$error .= 'Incorrect lists specified for the "Link to section" and "Link to element" properties. ';
		}
		if(!empty($error))
		{
			throw new RestException($error, self::ERROR_SAVE_FIELD);
		}

		if(!is_array($fields['LIST']))
		{
			$fields['LIST'] = array();
		}
		if(!empty($fields['LIST_TEXT_VALUES']))
		{
			$maxSort = 0;
			$listMap = array();
			foreach($fields['LIST'] as $key => $enum)
			{
				if($enum['SORT'] > $maxSort)
					$maxSort = intval($enum['SORT']);

				$listMap[trim($enum['VALUE'], " \t\n\r")] = $enum['ID'];
			}
			foreach(explode("\n", $fields['LIST_TEXT_VALUES']) as $valueLine)
			{
				$value = trim($valueLine, " \t\n\r");
				if(strlen($value) > 0 && !isset($listMap[$value]))
				{
					$maxSort += 10;
					$listMap[$value] = 'm'.$maxSort;
					$fields['LIST']['m'.$maxSort] = array(
						'SORT' => $maxSort,
						'VALUE' => $value,
					);
				}
			}
		}

		if(!empty($fields['LIST_DEF']) && is_array($fields['LIST_DEF']))
		{
			foreach($fields['LIST'] as $key => $enum)
			{
				$fields['LIST'][$key]['DEF'] = 'N';
			}
			foreach($fields['LIST_DEF'] as $def)
			{
				$def = intval($def);
				if($def > 0 && isset($fields['LIST'][$def]))
				{
					$fields['LIST'][$def]['DEF'] = 'Y';
				}
			}
		}

		return $fields;
	}

	private static function checkIblockPermission(&$params)
	{
		global $USER;
		$listPerm = \CListPermissions::checkAccess(
			$USER, $params['IBLOCK_TYPE_ID'], $params['IBLOCK_ID'], $params['SOCNET_GROUP_ID']);
		if($listPerm < 0)
		{
			throw new AccessException();
		}
		elseif($params['IBLOCK_ID'] && $listPerm < \CListPermissions::IS_ADMIN
			&& !\CIBlockRights::userHasRightTo($params['IBLOCK_ID'], $params['IBLOCK_ID'], 'iblock_edit')
			|| (!$params['IBLOCK_ID'] && $listPerm < \CListPermissions::IS_ADMIN)
		)
		{
			throw new AccessException();
		}

		$params['CAN_EDIT_IBLOCK'] = $listPerm >= \CListPermissions::IS_ADMIN
			|| ($params['IBLOCK_ID'] && \CIBlockRights::userHasRightTo(
					$params['IBLOCK_ID'], $params['IBLOCK_ID'], 'iblock_edit'));
		$params['CAN_ADMIN'] = $listPerm >= \CListPermissions::IS_ADMIN;

		return true;
	}

	private function checkElementPermission(array &$params)
	{
		global $USER;
		$listPerm = \CListPermissions::checkAccess(
			$USER, $params['IBLOCK_TYPE_ID'], $params['IBLOCK_ID'], $params['SOCNET_GROUP_ID']);
		if($listPerm < 0)
		{
			throw new AccessException();
		}
		elseif(($params['ELEMENT_ID'] && $listPerm < \CListPermissions::CAN_READ
			&& !\CIBlockElementRights::userHasRightTo($params['IBLOCK_ID'], $params['ELEMENT_ID'], 'element_read'))
			|| (!$params['ELEMENT_ID'] && $listPerm < \CListPermissions::CAN_READ
				&& !\CIBlockSectionRights::userHasRightTo($params['IBLOCK_ID'], 0, 'section_element_bind'))
			|| (!$params['ELEMENT_ID'] && $listPerm < \CListPermissions::CAN_READ
				&& !\CIBlockElementRights::userHasRightTo($params['IBLOCK_ID'], $params['IBLOCK_ID'], 'element_read'))
		)
		{
			throw new AccessException();
		}

		$socnetGroupClosed = false;
		if (intval($params['SOCNET_GROUP_ID']) && Loader::includeModule('socialnetwork'))
		{
			$socnetGroup = \CSocNetGroup::getByID(intval($params['SOCNET_GROUP_ID']));
			if (is_array($socnetGroup) && $socnetGroup['CLOSED'] == 'Y' && !\CSocNetUser::isCurrentUserModuleAdmin()
				&& ($socnetGroup['OWNER_ID'] != $USER->getID()
					|| \COption::getOptionString('socialnetwork', 'work_with_closed_groups', 'N') != 'Y')
			)
			{
				$socnetGroupClosed = true;
			}
		}
		$params['CAN_DELETE_ELEMENT'] = !$socnetGroupClosed && $params['ELEMENT_ID'] && ($listPerm >= \CListPermissions::CAN_WRITE
			|| \CIBlockElementRights::userHasRightTo($params['IBLOCK_ID'], $params['ELEMENT_ID'], 'element_delete'));
		$params['CAN_FULL_EDIT_ELEMENT'] = (!$socnetGroupClosed && $params['ELEMENT_ID'] && ($listPerm >= \CListPermissions::IS_ADMIN
				|| \CIBlockRights::userHasRightTo($params['IBLOCK_ID'], $params['IBLOCK_ID'], 'iblock_edit')));

		return true;
	}

	private static function getIblocksData(&$params)
	{
		$listIblock = array();

		$filter = array(
			'TYPE' => $params['IBLOCK_TYPE_ID'],
			'ID' => $params['IBLOCK_ID'] ? $params['IBLOCK_ID'] : '',
			'CODE' => $params['IBLOCK_CODE'] ? $params['IBLOCK_CODE'] : '',
			'ACTIVE' => 'Y',
			'CHECK_PERMISSIONS' => ($params['CAN_ADMIN'] || $params['SOCNET_GROUP_ID']) ? 'N' : 'Y',
		);
		if($params['SOCNET_GROUP_ID'])
			$filter['=SOCNET_GROUP_ID'] = $params['SOCNET_GROUP_ID'];
		else
			$filter['SITE_ID'] = SITE_ID;
		$queryObject = \CIBlock::getList(array(), $filter);
		while($result = $queryObject->fetch())
			$listIblock[] = $result;

		if((!empty($params['IBLOCK_CODE']) || !empty($params['IBLOCK_ID'])) && count($listIblock) == 1)
		{
			$iblock = current($listIblock);
			$params['IBLOCK_ID'] = $iblock['ID'];
			$params['BIZPROC'] = $iblock['BIZPROC'];
		}

		return $listIblock;
	}

	private static function getElementsData(&$params, $n)
	{
		$listElement = array();
		$object = new \CList($params['IBLOCK_ID']);
		$fields = $object->getFields();
		$elementSelect = array(
			'ID', 'IBLOCK_ID', 'NAME', 'IBLOCK_SECTION_ID', 'CREATED_BY', 'BP_PUBLISHED', 'CODE');
		$propertyFields = array();
		foreach($fields as $fieldId => $field)
		{
			if($object->is_field($fieldId))
				$elementSelect[] = $fieldId;
			else
				$propertyFields[] = $fieldId;
			if($fieldId == 'CREATED_BY')
				$elementSelect[] = 'CREATED_USER_NAME';
			if($fieldId == 'MODIFIED_BY')
				$elementSelect[] = 'USER_NAME';
		}

		$queryObject = \CIBlockElement::getList(array(), array(
			'IBLOCK_TYPE' => $params['IBLOCK_TYPE_ID'],
			'IBLOCK_ID' => $params['IBLOCK_ID'],
			'ID' => $params['ELEMENT_ID'] ? $params['ELEMENT_ID'] : '',
			'CODE' => $params['ELEMENT_CODE'] ? $params['ELEMENT_CODE'] : '',
			'SHOW_NEW' => ($params['CAN_FULL_EDIT_ELEMENT'] ? 'Y' : 'N')
		), false, self::getNavData($n), $elementSelect);
		$elementFields = array();
		$elementProperty = array();
		while($result = $queryObject->fetch())
		{
			$elementFields[$result['ID']] = $result;
			$listElement[$result['ID']] = $result;

			if(!empty($propertyFields))
			{
				$queryProperty = \CIBlockElement::getProperty(
					$params['IBLOCK_ID'],
					$result['ID'],
					array('sort'=>'asc', 'id'=>'asc', 'enum_sort'=>'asc', 'value_id'=>'asc'),
					array('ACTIVE'=>'Y', 'EMPTY'=>'N')
				);
				while($property = $queryProperty->fetch())
				{
					$propertyId = $property['ID'];
					$listElement[$result['ID']]['PROPERTY_'.$propertyId]
						[$property['PROPERTY_VALUE_ID']] = $property['VALUE'];

					if(!array_key_exists($propertyId, $elementProperty))
					{
						$elementProperty[$propertyId] = $property;
						unset($elementProperty[$propertyId]['DESCRIPTION']);
						unset($elementProperty[$propertyId]['VALUE_ENUM_ID']);
						unset($elementProperty[$propertyId]['VALUE_ENUM']);
						unset($elementProperty[$propertyId]['VALUE_XML_ID']);
						$elementProperty[$propertyId]['FULL_VALUES'] = array();
						$elementProperty[$propertyId]['VALUES_LIST'] = array();
					}

					$elementProperty[$propertyId]['FULL_VALUES'][$property['PROPERTY_VALUE_ID']] = array(
						'VALUE' => $property['VALUE'],
						'DESCRIPTION' => $property['DESCRIPTION'],
					);
					$elementProperty[$propertyId]['VALUES_LIST'][$property['PROPERTY_VALUE_ID']] = $property['VALUE'];
				}
			}
		}

		if((!empty($params['ELEMENT_CODE']) || !empty($params['ELEMENT_ID'])) && count($listElement) == 1)
			$params['ELEMENT_ID'] = key($listElement);

		return array($listElement, $elementSelect, $elementFields, $elementProperty, $queryObject);
	}

	private static function getProperty($iblockId, $code)
	{
		$queryObject = \CIBlockProperty::getList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $code));
		return $queryObject->fetch();
	}

	private static function prepareIblockFields(array $params)
	{
		$fields = array(
			'IBLOCK_TYPE_ID' => $params['IBLOCK_TYPE_ID'],
			'WORKFLOW' => 'N',
			'RIGHTS_MODE' => 'E',
			'SITE_ID' => \CSite::getDefSite(),
		);

		if($params['IBLOCK_CODE'])
			$fields['CODE'] = $params['IBLOCK_CODE'];

		$fieldList = array('NAME', 'ACTIVE', 'DESCRIPTION', 'SORT', 'BIZPROC', 'PICTURE');
		$messageList = array('ELEMENTS_NAME', 'ELEMENT_NAME', 'ELEMENT_ADD', 'ELEMENT_EDIT',
			'ELEMENT_DELETE', 'SECTIONS_NAME', 'SECTION_ADD', 'SECTION_EDIT', 'SECTION_DELETE');
		if(!empty($params['FIELDS']) && is_array($params['FIELDS']))
		{
			foreach($params['FIELDS'] as $fieldId => $fieldValue)
			{
				$fieldId = strtoupper($fieldId);
				if(!in_array($fieldId, $fieldList))
					continue;

				if($fieldId == 'PICTURE')
					$fieldValue = \CRestUtil::saveFile($fieldValue);
				$fields[$fieldId] = $fieldValue;
			}
		}
		if(!empty($params['MESSAGES']) && is_array($params['MESSAGES']))
		{
			foreach($params['MESSAGES'] as $messageId => $messageValue)
			{
				$messageId = strtoupper($messageId);
				if(!in_array($messageId, $messageList))
					continue;
				$fields[$messageId] = $messageValue;
			}
		}

		return $fields;
	}

	private static function prepareRights($rights)
	{
		$result = array();
		$count = 0;
		foreach($rights as $rightCode => $access)
		{
			$rightCode = strtoupper($rightCode);
			$access = strtoupper($access);
			$result['n'.($count++)] = array(
				'GROUP_CODE' => $rightCode,
				'TASK_ID' => \CIBlockRights::letterToTask($access),
				'DO_CLEAN' => 'N',
			);
		}
		return $result;
	}

	private static function startBizproc($params, $documentStates, $bizprocParameters, $changedElementFields, &$errors)
	{
		$bizprocWorkflowId = array();
		global $USER;
		$userId = $USER->getID();
		foreach($documentStates as $documentState)
		{
			if(strlen($documentState['ID']) <= 0)
			{
				$bizprocErrors = array();
				$bizprocWorkflowId[$documentState['TEMPLATE_ID']] = \CBPDocument::startWorkflow(
					$documentState['TEMPLATE_ID'],
					\BizProcDocument::getDocumentComplexId($params['IBLOCK_TYPE_ID'], $params['ELEMENT_ID']),
					array_merge($bizprocParameters[$documentState['TEMPLATE_ID']], array(
						\CBPDocument::PARAM_TAGRET_USER => 'user_'.intval($userId),
						\CBPDocument::PARAM_MODIFIED_DOCUMENT_FIELDS => $changedElementFields
					)),
					$bizprocErrors
				);
				foreach($bizprocErrors as $message)
					$errors .= $message['message'].' ';
			}
		}

		\CBPDocument::addDocumentToHistory(
			\BizProcDocument::getDocumentComplexId($params['IBLOCK_TYPE_ID'], $params['ELEMENT_ID']),
			$params['ELEMENT_NAME'], $userId);
	}
}