<?php
namespace Bitrix\Report;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\UserToGroupTable;
use CPHPCache;

class RightsManager
{
	const ACCESS_READ = 'access_read';
	const ACCESS_EDIT = 'access_edit';
	const ACCESS_FULL = 'access_full';

	protected $userId;

	public function __construct($userId)
	{
		$this->userId = intval($userId);
	}

	/**
	 * Checks the right to read.
	 * @param $reportId
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет наличие прав на чтение.</p>
	*
	*
	* @param mixed $reportId  Идентификатор отчета.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/rightsmanager/canread.php
	* @author Bitrix
	*/
	public function canRead($reportId)
	{
		if(!$this->isOwner($reportId))
		{
			$listSharing = Sharing::getEntityOfSharing($reportId);
			$listEntity = $this->getGroupsAndDepartments();
			foreach($listSharing as $sharingRow)
			{
				if(in_array($sharingRow['ENTITY'], $listEntity))
				{
					if($this->compareAccess($sharingRow['RIGHTS'], self::ACCESS_READ) >= 0)
						return true;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * Checks the right to edit.
	 * @param $reportId
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет наличие прав на редактирование.</p>
	*
	*
	* @param mixed $reportId  Идентификатор отчета.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/rightsmanager/canedit.php
	* @author Bitrix
	*/
	public function canEdit($reportId)
	{
		if(!$this->isOwner($reportId))
		{
			$listSharing = Sharing::getEntityOfSharing($reportId);
			$listEntity = $this->getGroupsAndDepartments();
			foreach($listSharing as $sharingRow)
			{
				if(in_array($sharingRow['ENTITY'], $listEntity))
				{
					if($this->compareAccess($sharingRow['RIGHTS'], self::ACCESS_EDIT) >= 0)
						return true;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * Checks the right to delete.
	 * @param $reportId
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет наличие прав на удаление.</p>
	*
	*
	* @param mixed $reportId  Идентификатор отчета.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/rightsmanager/candelete.php
	* @author Bitrix
	*/
	public function canDelete($reportId)
	{
		return $this->isOwner($reportId);
	}

	/**
	 * Checks the right to share.
	 * @param $reportId
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет наличие прав на возможность дать общий доступ.</p>
	*
	*
	* @param mixed $reportId  Идентификатор отчета.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/rightsmanager/canshare.php
	* @author Bitrix
	*/
	public function canShare($reportId)
	{
		return $this->isOwner($reportId);
	}

	/**
	 * Returns an array of user groups and departments.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	
	/**
	* <p>Нестатический метод возвращает список сущностей (групп и отделов) пользователя в виде индексированного массива.</p> <p>Без параметров</p>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/rightsmanager/getgroupsanddepartments.php
	* @author Bitrix
	*/
	public function getGroupsAndDepartments()
	{
		$cacheTime = defined('BX_COMP_MANAGED_CACHE') ? 3153600 : 3600*4;
		$cacheId = 'report-rights-'.$this->userId;
		$cacheDir = '/report/rights/'.$this->userId;
		$cache = new CPHPCache;
		if($cache->initCache($cacheTime, $cacheId, $cacheDir))
		{
			$listEntity = $cache->getVars();
		}
		else
		{
			global $CACHE_MANAGER;
			$cache->startDataCache();
			$listEntity = array(Sharing::CODE_USER.$this->userId);
			$userObject = \CUser::getByID($this->userId);
			if($userData = $userObject->fetch())
			{
				if(Loader::includeModule('socialnetwork'))
				{
					$queryObject = UserToGroupTable::getList(array(
						'select' => array('GROUP_ID'),
						'filter' => array(
							'USER_ID' => $userData['ID'],
							'ROLE' => array(
								UserToGroupTable::ROLE_USER,
								UserToGroupTable::ROLE_MODERATOR,
								UserToGroupTable::ROLE_OWNER
							)
						)
					));
					while ($groupData = $queryObject->fetch())
						$listEntity[] = Sharing::CODE_SOCNET_GROUP.$groupData['GROUP_ID'];
				}

				if(!empty($userData['UF_DEPARTMENT']))
				{
					foreach($userData['UF_DEPARTMENT'] as $departmentId)
						$listEntity[] = Sharing::CODE_DEPARTMENT.$departmentId;
				}
			}
			$CACHE_MANAGER->startTagCache($cacheDir);
			$CACHE_MANAGER->registerTag("sonet_user2group_U".$this->userId);
			$CACHE_MANAGER->registerTag("USER_CARD_".intval($this->userId/TAGGED_user_card_size));
			$CACHE_MANAGER->endTagCache();
			$cache->endDataCache($listEntity);
		}

		return $listEntity;
	}

	private function isOwner($reportId)
	{
		$reportId = intval($reportId);

		$hasReport = ReportTable::getCount(
			array('=ID' => $reportId, '=CREATED_BY' => $this->userId));

		if($hasReport)
			return true;
		else
			return false;
	}

	/**
	 * @param $access1
	 * @param $access2
	 * @return int Returns < 0 if $access1 is less than $access2; > 0
	 *    if $access1 is greater than $access2, and 0 if they are equal.
	 * @internal
	 */
	private function compareAccess($access1, $access2)
	{
		switch($access1)
		{
			case 'access_read':
				self::ACCESS_READ;
				$access1Pos = 2;
				break;
			case 'access_edit':
				self::ACCESS_EDIT;
				$access1Pos = 3;
				break;
			case 'access_full':
				self::ACCESS_FULL;
				$access1Pos = 4;
				break;
			default:
				$access1Pos = -1;
		}
		switch($access2)
		{
			case 'access_read':
				self::ACCESS_READ;
				$access2Pos = 2;
				break;
			case 'access_edit':
				self::ACCESS_EDIT;
				$access2Pos = 3;
				break;
			case 'access_full':
				self::ACCESS_FULL;
				$access2Pos = 4;
				break;
			default:
				$access2Pos = -1;
		}

		if($access1Pos == $access2Pos)
			return 0;

		return $access1Pos > $access2Pos? 1 : -1;
	}
}