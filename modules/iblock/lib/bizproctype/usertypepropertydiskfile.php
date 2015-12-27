<?php

namespace Bitrix\Iblock\BizprocType;

use Bitrix\Bizproc\BaseType\Base;
use Bitrix\Bizproc\FieldType;
use Bitrix\Disk\File;
use Bitrix\Main\Loader;

class UserTypePropertyDiskFile extends UserTypeProperty
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::INT;
	}

	public static function formatValueMultiple(FieldType $fieldType, $value, $format = 'printable')
	{
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $k => $v)
		{
			$value[$k] = static::formatValuePrintable($fieldType, $v);
		}

		return implode(static::getFormatSeparator($format), $value);
	}

	public static function formatValueSingle(FieldType $fieldType, $value, $format = 'printable')
	{
		return static::formatValueMultiple($fieldType, $value, $format);
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		if(!Loader::includeModule('disk'))
		{
			return '';
		}

		$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType('lists_workflow');
		$documentType = $fieldType->getDocumentType();
		$iblockId = str_replace('iblock_', '', $documentType[2]);

		$attachedModel = \Bitrix\Disk\AttachedObject::load(array(
			'OBJECT_ID' => $value,
			'=ENTITY_TYPE' => $connectorClass,
			'=ENTITY_ID' => $iblockId,
			'=MODULE_ID' => $moduleId
		));
		if(!$attachedModel)
		{
			return '';
		}

		global $USER;
		$userId = $USER->getID();
		if($userId)
		{
			if(!$attachedModel->canRead($userId))
			{
				return '';
			}
		}

		$file = $attachedModel->getFile();
		if(!$file)
		{
			return '';
		}

		$driver = \Bitrix\Disk\Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		return '[url='.$urlManager->getUrlUfController('download', array('attachedId' => $attachedModel->getId())
			).']'.htmlspecialcharsbx($file->getName()).'[/url]';
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class manager name.
	 * @return null|mixed
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		if (is_subclass_of($toTypeClass, '\Bitrix\Iblock\BizprocType\UserTypePropertyDiskFile'))
		{
			return $value;
		}

		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];

		$value = (int) $value;

		/** @var Base $toTypeClass */
		$type = $toTypeClass::getType();
		switch ($type)
		{
			case FieldType::FILE:
				$diskFile = File::getById($value);
				$value = $diskFile? $diskFile->getFileId() : null;
				break;
			default:
				$value = null;
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return static::renderControlMultiple($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlMultiple(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		if ($allowSelection)
		{
			$selectorValue = null;
			if(is_array($value))
			{
				$value = current($value);
			}
			if (\CBPActivity::isExpression($value))
			{
				$selectorValue = $value;
				$value = null;
			}
			return static::renderControlSelector($field, $selectorValue, true);
		}

		if ($renderMode & FieldType::RENDER_MODE_DESIGNER)
			return '';

		$userType = static::getUserType($fieldType);
		$documentType = $fieldType->getDocumentType();
		$iblockId = str_replace('iblock_', '', $documentType[2]);

		if (!empty($userType['GetPublicEditHTML']))
		{
			if (is_array($value) && isset($value['VALUE']))
				$value = $value['VALUE'];

			$fieldName = static::generateControlName($field);
			$renderResult = call_user_func_array(
				$userType['GetPublicEditHTML'],
				array(
					array(
						'IBLOCK_ID' => $iblockId,
						'IS_REQUIRED' => $fieldType->isRequired()? 'Y' : 'N',
						'PROPERTY_USER_TYPE' => $userType
					),
					array('VALUE' => $value),
					array(
						'FORM_NAME' => $field['Form'],
						'VALUE' => $fieldName,
						'DESCRIPTION' => '',
					),
					true
				)
			);
		}
		else
			$renderResult = static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);

		return $renderResult;
	}

	public static function extractValueSingle(FieldType $fieldType, array $field, array $request)
	{
		return static::extractValueMultiple($fieldType, $field, $request);
	}

	public static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		if(!Loader::includeModule('disk'))
		{
			return null;
		}

		$value = parent::extractValue($fieldType, $field, $request);
		if (is_array($value) && isset($value['VALUE']))
		{
			$value = $value['VALUE'];
		}

		if(!$value)
		{
			return null;
		}

		// Attach file disk
		$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType('lists_workflow');
		list($type, $realId) = \Bitrix\Disk\Uf\FileUserType::detectType($value);

		if($type != \Bitrix\Disk\Uf\FileUserType::TYPE_NEW_OBJECT)
		{
			return null;
		}

		$errorCollection = new \Bitrix\Disk\Internals\Error\ErrorCollection();
		$fileModel = \Bitrix\Disk\File::loadById($realId, array('STORAGE'));
		if(!$fileModel)
		{
			return null;
		}

		$documentType = $fieldType->getDocumentType();
		$iblockId = intval(substr($documentType[2], strlen("iblock_")));
		$attachedModel = \Bitrix\Disk\AttachedObject::load(array(
			'OBJECT_ID' => $fileModel->getId(),
			'=ENTITY_TYPE' => $connectorClass,
			'=ENTITY_ID' => $iblockId,
			'=MODULE_ID' => $moduleId
		));
		if($attachedModel)
		{
			return $fileModel->getId();
		}


		$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();

		if(!$fileModel->canRead($securityContext))
		{
			return null;
		}

		$canUpdate = $fileModel->canUpdate($securityContext);

		global $USER;

		$attachedModel = \Bitrix\Disk\AttachedObject::add(array(
			'MODULE_ID' => $moduleId,
			'OBJECT_ID' => $fileModel->getId(),
			'ENTITY_ID' => $iblockId,
			'ENTITY_TYPE' => $connectorClass,
			'IS_EDITABLE' => (int)$canUpdate,
			'ALLOW_EDIT' => (int) ($canUpdate && (int)\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPost('DISK_FILE_'.$iblockId.'_DISK_ATTACHED_OBJECT_ALLOW_EDIT')),
			'CREATED_BY' => $USER->getId(),
		), $errorCollection);
		if(!$attachedModel || $errorCollection->hasErrors())
		{
			return null;
		}

		return $fileModel->getId();
	}

	public static function clearValueSingle(FieldType $fieldType, $value)
	{
		static::clearValueMultiple($fieldType, $value);
	}

	public static function clearValueMultiple(FieldType $fieldType, $values)
	{
		if(!Loader::includeModule('disk'))
		{
			return;
		}

		if(!is_array($values))
		{
			$values = array($values);
		}

		$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType('lists_workflow');
		$documentType = $fieldType->getDocumentType();
		$iblockId = intval(substr($documentType[2], strlen("iblock_")));
		if(!$iblockId)
		{
			return;
		}

		foreach($values as $value)
		{
			$attachedModel = \Bitrix\Disk\AttachedObject::load(array(
				'OBJECT_ID' => $value,
				'=ENTITY_TYPE' => $connectorClass,
				'=ENTITY_ID' => $iblockId,
				'=MODULE_ID' => $moduleId
			));
			if(!$attachedModel)
			{
				continue;
			}

			if($userFieldManager->belongsToEntity($attachedModel, "lists_workflow", $iblockId))
				\Bitrix\Disk\AttachedObject::detachByFilter(array('ID' => $attachedModel->getId()));
		}
	}
}