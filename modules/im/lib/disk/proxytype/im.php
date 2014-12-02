<?php

namespace Bitrix\Im\Disk\ProxyType;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\ProxyType;
Loc::loadMessages(__FILE__);

class Im extends ProxyType\Base
{

	/**
	 * @param $user
	 * @return SecurityContext
	 */
	static public function getSecurityContextByUser($user)
	{
		return new DiskSecurityContext($user);
	}

	/**
	 * @inheritdoc
	 */
	static public function getStorageBaseUrl()
	{
		return '/';
	}

	/**
	 * @inheritdoc
	 */
	static public function getEntityUrl()
	{
		return '/';
	}

	/**
	 * @inheritdoc
	 */
	static public function getEntityTitle()
	{
		return Loc::getMessage('IM_DISK_STORAGE_TITLE');
	}

	/**
	 * @inheritdoc
	 */
	static public function getEntityImageSrc($width, $height)
	{
		return '/bitrix/js/im/images/blank.gif';
	}

	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		return $this->getEntityTitle();
	}
}