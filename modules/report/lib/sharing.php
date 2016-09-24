<?php
namespace Bitrix\Report;

use Bitrix\Report\Internals\SharingTable;
use Bitrix\Main\Loader;
use CExtranet;
use CPHPCache;
use CSocNetLogDestination;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

class Sharing
{
	const ERROR_SHARING_ADD = 'REPORT_SHARING_ADD_12000';
	const ERROR_SHARING_UPDATE = 'REPORT_SHARING_UPDATE_12001';
	const ERROR_SHARING_DELETE = 'REPORT_SHARING_DELETE_12002';

	const CODE_USER         = 'U';
	const CODE_GROUP        = 'G';
	const CODE_SOCNET_GROUP = 'SG';
	const CODE_DEPARTMENT   = 'DR';

	const TYPE_USER         = 'users';
	const TYPE_GROUP        = 'groups';
	const TYPE_SOCNET_GROUP = 'sonetgroups';
	const TYPE_DEPARTMENT   = 'department';

	/** @var  ErrorCollection */
	protected $errorCollection;

	protected $reportId;

	public function __construct($reportId)
	{
		$this->reportId = intval($reportId);
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * The method changes the share.
	 * @param $listNewEntity - List new reports.
	 */
	
	/**
	* <p>Нестатический метод для работы с общим доступом отчёта. Добавление/изменение/удаление общего доступа.</p>
	*
	*
	* @param mixed $listNewEntity  Список новых отчетов.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/changesharing.php
	* @author Bitrix
	*/
	public function changeSharing($listNewEntity)
	{
		$listSharingRow = $this->getByReportId();
		if(!empty($listNewEntity) && is_array($listNewEntity))
		{
			$newEntityData = array();
			foreach($listNewEntity as $entityId => $entityData)
			{
				switch($entityData['right'])
				{
					case RightsManager::ACCESS_READ:
					case RightsManager::ACCESS_EDIT:
						$newEntityData[$entityId]['ENTITY'] = $entityId;
						$newEntityData[$entityId]['RIGHTS'] = $entityData['right'];
						$newEntityData[$entityId]['REPORT_ID'] = $this->reportId;
						break;
				}
			}

			$overwriteSharing = $deleteSharing = $addSharing = array();
			foreach($listSharingRow as $sharingRow)
			{
				if(array_key_exists($sharingRow['ENTITY'], $newEntityData))
				{
					if($newEntityData[$sharingRow['ENTITY']]['RIGHTS'] != $sharingRow['RIGHTS'])
					{
						$overwriteSharing[$sharingRow['ENTITY']] = $newEntityData[$sharingRow['ENTITY']];
						$overwriteSharing[$sharingRow['ENTITY']]['ID'] = $sharingRow['ID'];
					}
					elseif($newEntityData[$sharingRow['ENTITY']]['RIGHTS'] == $sharingRow['RIGHTS'])
					{
						unset($newEntityData[$sharingRow['ENTITY']]);
					}
				}
				else
				{
					$deleteSharing[$sharingRow['ENTITY']] = $sharingRow;
				}
			}
			$addSharing = array_diff_key($newEntityData, $overwriteSharing);
			if($addSharing)
				$this->addToManyEntity($addSharing);
			if($overwriteSharing)
				$this->updateManyEntity($overwriteSharing);
			if($deleteSharing)
				$this->deleteManyEntity($deleteSharing);
		}
		else
		{
			$this->deleteManyEntity($listSharingRow);
		}

		$cache = new CPHPCache;
		$cache->cleanDir('/report/sharing/'.$this->reportId);
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Get an array shared entity.
	 * @param $reportId.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	
	/**
	* <p>Статический метод возвращает массив данных привязанных сущностей отчёта.</p>
	*
	*
	* @param mixed $reportId  Идентификатор отчета.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/getentityofsharing.php
	* @author Bitrix
	*/
	public static function getEntityOfSharing($reportId)
	{
		$reportId = intval($reportId);
		$entitySharing = array();

		$cacheTime = defined('BX_COMP_MANAGED_CACHE') ? 3153600 : 3600*4;
		$cacheId = 'report-sharing-'.$reportId;
		$cacheDir = '/report/sharing/'.$reportId;
		$cache = new CPHPCache;
		if($cache->initCache($cacheTime, $cacheId, $cacheDir))
		{
			$entitySharing = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$result = SharingTable::getList(array(
				'select' => array('ID', 'ENTITY', 'RIGHTS'),
				'filter' => array('=REPORT_ID' => $reportId),
			));
			while($data = $result->fetch())
			{
				$entitySharing[] = array(
					'ENTITY' => $data['ENTITY'],
					'RIGHTS' => $data['RIGHTS'],
				);
			}
			$cache->endDataCache($entitySharing);
		}

		return $entitySharing;
	}

	/**
	 * Get an array user data for sharing.
	 * @param $userId
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает пользовательские данные для работы с общим доступом.</p>
	*
	*
	* @param mixed $userId  Идентификатор пользователя.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/getuserdata.php
	* @author Bitrix
	*/
	public static function getUserData($userId)
	{
		$userData = array();

		$userId = intval($userId);
		$users = \CUser::getList($by='id', $order='asc',
			array('ID' => $userId),
			array('FIELDS' => array('ID', 'PERSONAL_PHOTO', 'NAME', 'LAST_NAME'))
		);
		$user = $users->fetch();
		if ($user)
		{
			$avatar = self::getImage($user['PERSONAL_PHOTO']);
			$userData['name'] = \CUser::formatName(\CSite::getNameFormat(false), $user, false);
			$userData['avatar'] = $avatar ? $avatar['src'] : '/bitrix/js/report/css/images/default_avatar.png';
			$userData['link'] = self::getUserUrl($userId);
			$userData['access'] = RightsManager::ACCESS_READ;
		}

		return $userData;
	}

	/**
	 * Parses entity code to get entity type and entity id.
	 * Ex. SG444 = array('SG', 444)
	 * @param $entity
	 * @return array|null
	 */
	
	/**
	* <p>Статический метод разбирает код сущности, чтобы получить её код и идентификатор. Например, <code>SG444 = array('SG', 444)</code>.</p>
	*
	*
	* @param mixed $entity  Сущность.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/parseentityvalue.php
	* @author Bitrix
	*/
	public static function parseEntityValue($entity)
	{
		preg_match(
			'%(' . self::CODE_USER . '|' . self::CODE_SOCNET_GROUP . '|' .
			self::CODE_DEPARTMENT . '){1,2}([0-9]+)%u',
			$entity,
			$m
		);
		list(, $code, $id) = $m;
		if($code === null || $id === null)
		{
			return null;
		}
		switch($code)
		{
			case self::CODE_USER:
				return array(self::TYPE_USER, $id);
			case self::CODE_GROUP:
				return array(self::TYPE_GROUP, $id);
			case self::CODE_SOCNET_GROUP:
				return array(self::TYPE_SOCNET_GROUP, $id);
			case self::CODE_DEPARTMENT:
				return array(self::TYPE_DEPARTMENT, $id);
		}
		return null;
	}

	/**
	 * Get an array of desired size depending on the type of entity.
	 * @param $type
	 * @param $id
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	
	/**
	* <p>Статический метод получает массив необходимого формата, в зависимости от типа сущности.</p>
	*
	*
	* @param mixed $type  Тип сущности.
	*
	* @param $typ $id  Идентификатор.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/gettypedata.php
	* @author Bitrix
	*/
	public static function getTypeData($type, $id)
	{
		$typeData = array();

		if($type == self::TYPE_USER)
		{
			$typeData = self::getUserData($id);
		}
		elseif($type == self::TYPE_GROUP)
		{

		}
		elseif($type == self::TYPE_SOCNET_GROUP)
		{
			if(Loader::includeModule('socialnetwork'))
			{
				$query = \CSocNetGroup::getList(array(), array('ID' => $id),
					false, false, array('IMAGE_ID', 'NAME'));
				while($group = $query->fetch())
				{
					$typeData['name'] = $group['NAME'];
					$avatar = self::getImage($group['IMAGE_ID']);
					$typeData['avatar'] = $avatar?$avatar['src']:'/bitrix/js/report/css/images/default_groupe.png';
				}
			}
		}
		elseif($type == self::TYPE_DEPARTMENT)
		{
			if(Loader::includeModule('socialnetwork'))
			{
				$structure = \CSocNetLogDestination::getStucture();
				if(!empty($structure['department']['DR' . $id]))
				{
					$typeData['name'] = $structure['department']['DR' . $id]['name'];
					$typeData['avatar'] = '/bitrix/js/report/css/images/default_groupe.png';
				}
			}
		}

		return $typeData;
	}

	/**
	 * Get socnet destination.
	 * @param $userId
	 * @param array $selected
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	
	/**
	* <p>Статический метод возвращает список сущностей для общего доступа.</p>
	*
	*
	* @param mixed $userId  Идентификатор пользователя.
	*
	* @param array $selected = array() Массив со списком идентификаторов групп, отделов и
	* пользователей.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/getsocnetdestination.php
	* @author Bitrix
	*/
	public static function getSocNetDestination($userId, $selected = array())
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			return array();
		}

		global $CACHE_MANAGER;

		if (!is_array($selected))
		{
			$selected = array();
		}

		if (method_exists('CSocNetLogDestination','getDestinationSort'))
		{
			$destination = array(
				'LAST' => array()
			);

			$lastDestination = CSocNetLogDestination::getDestinationSort(array(
				"DEST_CONTEXT" => "REPORT_SHARE"
			));

			CSocNetLogDestination::fillLastDestination($lastDestination, $destination['LAST']);
		}
		else
		{
			$destination = array(
				'LAST' => array(
					'SONETGROUPS' => CSocNetLogDestination::getLastSocnetGroup(),
					'DEPARTMENT' => CSocNetLogDestination::getLastDepartment(),
					'USERS' => CSocNetLogDestination::getLastUser()
				)
			);
		}

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = 'dest_group_'.$userId;
		$cacheDir = '/report/dest/'.$userId;

		$cache = new CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$destination['SONETGROUPS'] = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$destination['SONETGROUPS'] = CSocNetLogDestination::getSocnetGroup(
				array('GROUP_CLOSED' => 'N', 'features' => array("files", array("view"))));
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cacheDir);
				foreach($destination['SONETGROUPS'] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
				$CACHE_MANAGER->registerTag("sonet_user2group_U".$userId);
				$CACHE_MANAGER->endTagCache();
			}
			$cache->endDataCache($destination['SONETGROUPS']);
		}

		$destUser = array();
		$destination['SELECTED'] = array();
		foreach ($selected as $ind => $code)
		{
			if (substr($code, 0 , 2) == 'DR')
			{
				$destination['SELECTED'][$code] = "department";
			}
			elseif (substr($code, 0 , 2) == 'UA')
			{
				$destination['SELECTED'][$code] = "groups";
			}
			elseif (substr($code, 0 , 2) == 'SG')
			{
				$destination['SELECTED'][$code] = "sonetgroups";
			}
			elseif (substr($code, 0 , 1) == 'U')
			{
				$destination['SELECTED'][$code] = "users";
				$destUser[] = str_replace('U', '', $code);
			}
		}

		// intranet structure
		$structure = CSocNetLogDestination::getStucture();
		$destination['DEPARTMENT'] = $structure['department'];
		$destination['DEPARTMENT_RELATION'] = $structure['department_relation'];
		$destination['DEPARTMENT_RELATION_HEAD'] = $structure['department_relation_head'];

		if (Loader::includeModule('extranet') && !CExtranet::isIntranetUser())
		{
			$destination['EXTRANET_USER'] = 'Y';
			$destination['USERS'] = CSocNetLogDestination::getExtranetUser();
		}
		else
		{
			if(is_array($destination['LAST']['USERS']))
			{
				foreach ($destination['LAST']['USERS'] as $value)
					$destUser[] = str_replace('U', '', $value);
			}

			$destination['EXTRANET_USER'] = 'N';
			$destination['USERS'] = CSocNetLogDestination::getUsers(array('id' => $destUser));
		}

		return $destination;
	}

	/**
	 * Removes all related entities by removing report.
	 * @param $reportId
	 * @throws \Exception
	 */
	
	/**
	* <p>Обработчик события для удаления всех привязанных сущностей к отчёту. Метод статический.</p>
	*
	*
	* @param mixed $reportId  Идентификатор отчета.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/sharing/onreportdelete.php
	* @author Bitrix
	*/
	public static function onReportDelete($reportId)
	{
		$reportId = intval($reportId);
		$result = SharingTable::getList(array(
			'select' => array('ID'),
			'filter'=>array('=REPORT_ID' => $reportId),
		));
		while($data = $result->fetch())
			SharingTable::delete($data['ID']);
	}

	protected function getByReportId()
	{
		$listSharingRow = array();
		$result = SharingTable::getList(array(
			'filter'=>array('=REPORT_ID' => $this->reportId),
		));
		while($data = $result->fetch())
			$listSharingRow[] = $data;

		return $listSharingRow;
	}

	protected function addToManyEntity(array $listData)
	{
		foreach($listData as $data)
		{
			$fields = array(
				'REPORT_ID' => $data['REPORT_ID'],
				'ENTITY' => $data['ENTITY'],
				'RIGHTS' => $data['RIGHTS'],
			);
			$result = SharingTable::add($fields);
			if(!$result->isSuccess())
			{
				foreach($result->getErrorMessages() as $errorMessage)
					$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_SHARING_ADD)));
			}
		}
	}

	protected function updateManyEntity(array $listData)
	{
		foreach($listData as $data)
		{
			$fields = array(
				'RIGHTS' => $data['RIGHTS']
			);
			$result = SharingTable::update($data['ID'], $fields);
			if(!$result->isSuccess())
			{
				foreach($result->getErrorMessages() as $errorMessage)
					$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_SHARING_UPDATE)));
			}
		}
	}

	protected function deleteManyEntity(array $listData)
	{
		foreach($listData as $data)
		{
			$result = SharingTable::delete($data['ID']);
			if(!$result->isSuccess())
			{
				foreach($result->getErrorMessages() as $errorMessage)
					$this->errorCollection->add(array(new Error($errorMessage, self::ERROR_SHARING_DELETE)));
			}
		}
	}

	protected static function getUserUrl($userId)
	{
		$userPage = \COption::getOptionString('socialnetwork', 'user_page', false, SITE_ID);
		if(!$userPage)
		{
			$userPage = SITE_DIR.'company/personal/';
		}
		return $userPage.'user/'.$userId.'/';
	}

	protected static function getImage($imageId)
	{
		return \CFile::resizeImageGet($imageId,
			array('width' => 21, 'height' => 21), \BX_RESIZE_IMAGE_EXACT, false);
	}
}