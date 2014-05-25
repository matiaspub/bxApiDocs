<?php

namespace Bitrix\Mail;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization;

Localization\Loc::loadMessages(__FILE__);

class MailServicesTable extends Entity\DataManager
{

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_mail_mailservices';
	}

	public static function checkFields(Entity\Result $result, $primary, array $data)
	{
		parent::checkFields($result, $primary, $data);

		if (isset($data['SITE_ID']))
		{
			$selectResult = \Bitrix\Main\SiteTable::getByPrimary($data['SITE_ID']);
			if (!$selectResult->fetch())
			{
				$field = static::getEntity()->getField('SITE_ID');
				$result->addError(new Entity\FieldError(
					$field,
					Localization\Loc::getMessage('MAIN_ENTITY_FIELD_INVALID', array('#FIELD_TITLE#' => $field->getTitle())),
					Entity\FieldError::INVALID_VALUE
				));
			}
		}

		if (!empty($data['ICON']))
		{
			if (!is_scalar($data['ICON']) || !preg_match('/[0-9]+/', $data['ICON']))
			{
				$field = static::getEntity()->getField('ICON');
				$result->addError(new Entity\FieldError(
					$field,
					Localization\Loc::getMessage('MAIN_ENTITY_FIELD_INVALID', array('#FIELD_TITLE#' => $field->getTitle())),
					Entity\FieldError::INVALID_VALUE
				));
			}
		}

		return $result;
	}

	public static function add(array $data)
	{
		if (isset($data['ICON']) && is_array($data['ICON']))
		{
			$iconError = $data['ICON']['name'] ? \CFile::CheckImageFile($data['ICON']) : null;
			if (is_null($iconError))
			{
				$data['ICON']['MODULE_ID'] = 'mail';

				\CFile::SaveForDB($data, 'ICON', 'mail/mailservices/icon');
			}
		}

		return parent::add($data);
	}

	public static function update($primary, array $data)
	{
		if (empty($data))
			return new Entity\UpdateResult();

		$serviceForUpdate = static::getByPrimary($primary)->fetch();
		if (!$serviceForUpdate)
		{
			$updateResult = new Entity\UpdateResult();
			$updateResult->addError(new Entity\EntityError(Localization\Loc::getMessage('mail_mailservice_not_found')));

			return $updateResult;
		}

		if (isset($data['ICON']) && is_array($data['ICON']))
		{
			$iconError = $data['ICON']['name'] ? \CFile::CheckImageFile($data['ICON']) : null;
			if (is_null($iconError))
			{
				$data['ICON']['MODULE_ID'] = 'mail';

				\CFile::SaveForDB($data, 'ICON', 'mail/mailservices/icon');
			}
		}

		$updateResult = parent::update($primary, $data);

		if ($updateResult->isSuccess())
		{
			$serviceId = is_array($primary) ? $primary['ID'] : $primary;

			$isSiteChanged = isset($data['SITE_ID']) && $data['SITE_ID'] != $serviceForUpdate['SITE_ID'];
			$isDeactivated = isset($data['ACTIVE']) && $data['ACTIVE'] == 'N' && $serviceForUpdate['ACTIVE'] == 'Y';
			if ($isSiteChanged || $isDeactivated)
			{
				$emptyService = static::getList(array(
					'filter' => array(
						'=SITE_ID'    => $serviceForUpdate['SITE_ID'],
						'ACTIVE'      => 'Y',
						'=SERVER'     => '',
						'=PORT'       => '',
						'=ENCRYPTION' => '',
						'=LINK'       => ''
					),
					'limit' => 1
				))->fetch();
			}

			if ($isSiteChanged || $isDeactivated && $emptyService)
			{
				$mbData = $emptyService
					? array('SERVICE_ID' => $emptyService['ID'], 'NAME' => $emptyService['NAME'])
					: array('ACTIVE' => 'N', 'SERVICE_ID' => 0);
			}
			else
			{
				$mbData = array();
				foreach ($data as $key => $value)
				{
					if (empty($value))
						continue;

					switch ($key)
					{
						case 'ACTIVE':
						case 'NAME':
						case 'SERVER':
						case 'PORT':
						case 'LINK':
							$mbData[$key] = $value;
							break;
						case 'ENCRYPTION':
							$mbData['USE_TLS'] = $value;
							break;
					}
				}
			}

			$selectResult = \CMailbox::GetList(array(), array('SERVICE_ID' => $serviceId));
			while ($mailbox = $selectResult->Fetch())
				\CMailbox::Update($mailbox['ID'], $mbData);
		}

		return $updateResult;
	}

	public static function getIconSrc($serviceName, $iconId = null)
	{
		if ($iconId)
		{
			$icon = \CFile::GetFileArray($iconId);

			return $icon['SRC'];
		}
		else
		{
			$icons = array(
				'gmail'       => '/bitrix/images/mail/mailservice-icon/post-gmail-icon.png',
				'icloud'      => '/bitrix/images/mail/mailservice-icon/post-icloud-icon.png',
				'outlook.com' => '/bitrix/images/mail/mailservice-icon/post-outlook-icon.png',
				'office365'   => '/bitrix/images/mail/mailservice-icon/post-office360-icon.png',
				'yahoo'       => '/bitrix/images/mail/mailservice-icon/post-yahoo-icon.png',
				'aol'         => '/bitrix/images/mail/mailservice-icon/post-aol-icon.png',
				'yandex'      => '/bitrix/images/mail/mailservice-icon/post-yandex-icon.png',
				'mail.ru'     => '/bitrix/images/mail/mailservice-icon/post-mail-icon.png',
				'ukr.net'     => '/bitrix/images/mail/mailservice-icon/post-ukrnet-icon.png',
				'exchange'    => '/bitrix/images/mail/mailservice-icon/post-exchange-icon.png',
				'other'       => '/bitrix/images/mail/mailservice-icon/post-imap-icon.png',
			);

			if ($icons[$serviceName])
				return $icons[$serviceName];
		}
	}

	public static function delete($primary)
	{
		$serviceForDelete = static::getByPrimary($primary)->fetch();
		if (!$serviceForDelete)
		{
			$deleteResult = new Entity\DeleteResult();
			$deleteResult->addError(new Entity\EntityError(Localization\Loc::getMessage('mail_mailservice_not_found')));

			return $deleteResult;
		}

		$deleteResult = parent::delete($primary);

		if ($deleteResult->isSuccess())
		{
			$emptyService = static::getList(array(
				'filter' => array(
					'=SITE_ID'    => $serviceForDelete['SITE_ID'],
					'ACTIVE'      => 'Y',
					'=SERVER'     => '',
					'=PORT'       => '',
					'=ENCRYPTION' => '',
					'=LINK'       => ''
				),
				'limit' => 1
			))->fetch();

			$serviceId = is_array($primary) ? $primary['ID'] : $primary;
			$mbData = $emptyService
				? array('SERVICE_ID' => $emptyService['ID'], 'NAME' => $emptyService['NAME'])
				: array('ACTIVE' => 'N', 'SERVICE_ID' => 0);

			$selectResult = \CMailbox::GetList(array(), array('SERVICE_ID' => $serviceId));
			while ($mailbox = $selectResult->Fetch())
				\CMailbox::Update($mailbox['ID'], $mbData);
		}

		return $deleteResult;
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_site_field'),
				'required'  => true
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_active_field'),
				'values'    => array('N', 'Y'),
				'required'  => true
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_sort_field'),
				'required'  => true
			),
			'NAME' => array(
				'data_type' => 'string',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_name_field'),
				'required'  => true
			),
			'SERVER' => array(
				'data_type' => 'string',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_server_field'),
			),
			'PORT' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_port_field'),
			),
			'ENCRYPTION' => array(
				'data_type' => 'boolean',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_encryption_field'),
				'values'    => array('N', 'Y'),
			),
			'LINK' => array(
				'data_type' => 'string',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_link_field'),
			),
			'ICON' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('mail_mailservice_entity_icon_field'),
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID'),
			),
		);
	}

}
