<?php

if (!CModule::IncludeModule('rest'))
	return;

class CMailRestService extends IRestService
{

	public static function OnRestServiceBuildDescription()
	{
		return array(
			'mailservice' => array(
				'mailservice.fields' => array('CMailRestService', 'mailserviceFields'),
				'mailservice.list'   => array('CMailRestService', 'mailserviceList'),
				'mailservice.get'    => array('CMailRestService', 'mailserviceGet'),
				'mailservice.add'    => array('CMailRestService', 'mailserviceAdd'),
				'mailservice.update' => array('CMailRestService', 'mailserviceUpdate'),
				'mailservice.delete' => array('CMailRestService', 'mailserviceDelete'),
			),
		);
	}

	public static function mailserviceFields($arParams)
	{
		IncludeModuleLangFile(__FILE__);

		return array(
			'ID'         => 'ID',
			'SITE_ID'    => GetMessage('MAIL_MAILSERVICE_SITE_ID'),
			'ACTIVE'     => GetMessage('MAIL_MAILSERVICE_ACTIVE'),
			'NAME'       => GetMessage('MAIL_MAILSERVICE_NAME'),
			'SERVER'     => GetMessage('MAIL_MAILSERVICE_SERVER'),
			'PORT'       => GetMessage('MAIL_MAILSERVICE_PORT'),
			'ENCRYPTION' => GetMessage('MAIL_MAILSERVICE_ENCRYPTION'),
			'LINK'       => GetMessage('MAIL_MAILSERVICE_LINK'),
			'ICON'       => GetMessage('MAIL_MAILSERVICE_ICON'),
			'SORT'       => GetMessage('MAIL_MAILSERVICE_SORT'),
		);
	}

	public static function mailserviceList($arParams)
	{
		IncludeModuleLangFile(__FILE__);

		$result = Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array('ACTIVE' => 'Y', '=SITE_ID' => SITE_ID),
			'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC')
		));

		$data = array();
		while ($row = $result->fetch())
		{
			$icon = CFile::GetFileArray($row['ICON']);
			$row['ICON'] = $icon['SRC'];

			$data[] = $row;
		}

		if (empty($data))
			throw new Exception(GetMessage('MAIL_MAILSERVICE_LIST_EMPTY'));

		return $data;
	}

	public static function mailserviceGet($arParams)
	{
		IncludeModuleLangFile(__FILE__);

		if (empty($arParams['ID']))
			throw new Exception(GetMessage('MAIL_MAILSERVICE_EMPTY_ID'));

		$result = Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array('=ID' => $arParams['ID'], '=SITE_ID' => SITE_ID)
		));

		if ($data = $result->fetch())
		{
			$icon = CFile::GetFileArray($data['ICON']);
			$data['ICON'] = $icon['SRC'];
		}

		if (empty($data))
			throw new Exception(GetMessage('MAIL_MAILSERVICE_EMPTY'));

		return $data;
	}

	public static function mailserviceAdd($arParams)
	{
		global $USER;

		if (!$USER->CanDoOperation('bitrix24_config'))
			throw new Exception(GetMessage('ACCESS_DENIED'));

		$arFields = array(
			'SITE_ID'    => SITE_ID,
			'ACTIVE'     => $arParams['ACTIVE'] ?: 'Y',
			'NAME'       => $arParams['NAME'],
			'SERVER'     => $arParams['SERVER'],
			'PORT'       => $arParams['PORT'],
			'ENCRYPTION' => $arParams['ENCRYPTION'],
			'LINK'       => $arParams['LINK'],
			'ICON'       => CRestUtil::saveFile($arParams['ICON']) ?: $arParams['ICON'],
			'SORT'       => $arParams['SORT'] ?: 100
		);

		$result = Bitrix\Mail\MailServicesTable::add($arFields);

		if (!$result->isSuccess())
			throw new Exception(join('; ', $result->getErrorMessages()));

		return $result->getId();
	}

	public static function mailserviceUpdate($arParams)
	{
		global $USER;

		IncludeModuleLangFile(__FILE__);

		if (!$USER->CanDoOperation('bitrix24_config'))
			throw new Exception(GetMessage('ACCESS_DENIED'));

		if (empty($arParams['ID']))
			throw new Exception(GetMessage('MAIL_MAILSERVICE_EMPTY_ID'));

		$result = Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array('=ID' => $arParams['ID'], '=SITE_ID' => SITE_ID)
		));

		if (!$result->fetch())
			throw new Exception(GetMessage('MAIL_MAILSERVICE_EMPTY'));

		$arFields = array(
			'ACTIVE'     => $arParams['ACTIVE'],
			'NAME'       => $arParams['NAME'],
			'SERVER'     => $arParams['SERVER'],
			'PORT'       => $arParams['PORT'],
			'ENCRYPTION' => $arParams['ENCRYPTION'],
			'LINK'       => $arParams['LINK'],
			'ICON'       => CRestUtil::saveFile($arParams['ICON']) ?: $arParams['ICON'],
			'SORT'       => $arParams['SORT']
		);

		foreach ($arFields as $name => $value)
		{
			if (empty($value))
				unset($arFields[$name]);
		}

		$result = Bitrix\Mail\MailServicesTable::update($arParams['ID'], $arFields);

		if (!$result->isSuccess())
			throw new Exception(join('; ', $result->getErrorMessages()));

		return true;
	}

	public static function mailserviceDelete($arParams)
	{
		global $USER;

		IncludeModuleLangFile(__FILE__);

		if (!$USER->CanDoOperation('bitrix24_config'))
			throw new Exception(GetMessage('ACCESS_DENIED'));

		if (empty($arParams['ID']))
			throw new Exception(GetMessage('MAIL_MAILSERVICE_EMPTY_ID'));

		$result = Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array('=ID' => $arParams['ID'], '=SITE_ID' => SITE_ID)
		));

		if (!$result->fetch())
			throw new Exception(GetMessage('MAIL_MAILSERVICE_EMPTY'));

		$result = Bitrix\Mail\MailServicesTable::delete($arParams['ID']);

		if (!$result->isSuccess())
			throw new Exception(join('; ', $result->getErrorMessages()));

		return true;
	}

}
