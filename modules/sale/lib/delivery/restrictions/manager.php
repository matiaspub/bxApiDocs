<?
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Sale\Internals\ServiceRestrictionTable;

class Manager extends \Bitrix\Sale\Services\Base\RestrictionManager
{
	protected static $classNames = null;

	/**
	 * @return int
	 */
	protected static function getServiceType()
	{
		return self::SERVICE_TYPE_SHIPMENT;
	}

	/**
	 * @return array
	 */
	public static function getBuildInRestrictions()
	{
		return  array(
			'\Bitrix\Sale\Delivery\Restrictions\BySite' => 'lib/delivery/restrictions/bysite.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByPrice' => 'lib/delivery/restrictions/byprice.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByWeight' => 'lib/delivery/restrictions/byweight.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByMaxSize' => 'lib/delivery/restrictions/bymaxsize.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByLocation' => 'lib/delivery/restrictions/bylocation.php',
			'\Bitrix\Sale\Delivery\Restrictions\PersonType' => 'lib/delivery/restrictions/bypersontype.php', //will be moved
			'\Bitrix\Sale\Delivery\Restrictions\ByPaySystem' => 'lib/delivery/restrictions/bypaysystem.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByPersonType' => 'lib/delivery/restrictions/bypersontype.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByDimensions' => 'lib/delivery/restrictions/bydimensions.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByPublicMode' => 'lib/delivery/restrictions/bypublicmode.php',
			'\Bitrix\Sale\Delivery\Restrictions\ByProductCategory' => 'lib/delivery/restrictions/byproductcategory.php'
		);
	}

	/**
	 * @return string
	 */
	public static function getEventName()
	{
		return 'onSaleDeliveryRestrictionsClassNamesBuildList';
	}

	/**
	 * @param int $deliveryId
	 * @param string $className
	 */
	public static function deleteByDeliveryIdClassName($deliveryId, $className)
	{
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();
		$strSql = "DELETE FROM ".ServiceRestrictionTable::getTableName().
			" WHERE SERVICE_ID=".$sqlHelper->forSql($deliveryId).
			" AND SERVICE_TYPE=".$sqlHelper->forSql(Manager::SERVICE_TYPE_SHIPMENT).
			" AND CLASS_NAME='".$sqlHelper->forSql($className)."'";

		$con->queryExecute($strSql);
	}

	/**
	 * Returns services wich have restrictions, but successfully pass checks.
	 * @param \Bitrix\Sale\Shipment|null $shipment
	 * @param int $restrictionMode RestrictionManager::MODE_CLIENT | RestrictionManager::MODE_MANAGER
	 * @return int[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getRestrictedIds(\Bitrix\Sale\Shipment $shipment = null, $restrictionMode)
	{
		$result = array();

		static $dataPrepared = false;
		static $idsGrouppedByRestrictions = array();	//performance
		static $supportGroupFiltering = array();		//

		if($dataPrepared === false)
		{
			self::init();

			$dbRes = ServiceRestrictionTable::getList(array(
				'runtime' => array(
					new \Bitrix\Main\Entity\ReferenceField(
						'DELIVERY_SERVICE',
						'\Bitrix\Sale\Delivery\Services\Table',
						array(
							'=this.SERVICE_ID' => 'ref.ID',
							'=this.SERVICE_TYPE' => array('?', self::SERVICE_TYPE_SHIPMENT)
						),
						array('join_type' => 'inner')
					)
				),
				'filter' => array(
					'DELIVERY_SERVICE.ACTIVE' => 'Y'
				),
				'order' => array('SORT' =>'ASC')
			));

			$data = array();
			$checkedGroupFiltering = array();

			while($rstr = $dbRes->fetch())
			{
				if(!isset($data[$rstr["SERVICE_ID"]]))
					$data[$rstr["SERVICE_ID"]] = array();

				$data[$rstr["SERVICE_ID"]][$rstr['ID']] = $rstr;

				if(!in_array($rstr['CLASS_NAME'], $checkedGroupFiltering))
				{
					$checkedGroupFiltering[] = $rstr['CLASS_NAME'];

					if(method_exists($rstr['CLASS_NAME'], 'filterServicesArray'))
						$supportGroupFiltering[] = $rstr['CLASS_NAME'];
				}

				if(in_array($rstr['CLASS_NAME'], $supportGroupFiltering))
				{
					if(!is_array($idsGrouppedByRestrictions[$rstr['CLASS_NAME']]))
						$idsGrouppedByRestrictions[$rstr['CLASS_NAME']] = array();

					if(!in_array($rstr["SERVICE_ID"], $idsGrouppedByRestrictions[$rstr['CLASS_NAME']]))
						$idsGrouppedByRestrictions[$rstr['CLASS_NAME']][$rstr["SERVICE_ID"]] = $rstr;
				}
			}

			self::prepareData(array_keys($data), $data);
			$dataPrepared = true;
		}
		else
		{
			$data = self::getCache(0, self::getServiceType());
		}

		$filterResult = array();

		foreach($supportGroupFiltering as $rstrClass)
		{
			$passedServicesIds = $rstrClass::filterServicesArray($shipment, $idsGrouppedByRestrictions[$rstrClass]);
			$notPassed = array_diff_key($idsGrouppedByRestrictions[$rstrClass], array_flip($passedServicesIds));

			if($restrictionMode == self::MODE_MANAGER)
				foreach($notPassed as $srvId => $rstr)
					$filterResult[$srvId] = $rstrClass::getSeverity($restrictionMode);

			$data = array_diff_key($data, $notPassed);
		}

		foreach($data as $serviceId => $serviceRestrictions)
		{
			$srvRes = self::SEVERITY_NONE;

			if($shipment != null)
			{
				foreach($serviceRestrictions as $restrictionId => $rstr)
				{
					if(in_array($rstr['CLASS_NAME'], $supportGroupFiltering))
						continue;

					if(!self::isClassValid($rstr['CLASS_NAME']))
						continue;

					if(!$rstr['PARAMS'])
						$rstr['PARAMS'] = array();

					$res = $rstr['CLASS_NAME']::checkByEntity(
						$shipment,
						$rstr['PARAMS'],
						$restrictionMode,
						$serviceId
					);

					if($res == self::SEVERITY_STRICT)
						continue 2;

					if($res == self::SEVERITY_SOFT && $restrictionMode == self::MODE_CLIENT)
						continue 2;

					if($res == self::SEVERITY_SOFT && $srvRes == self::SEVERITY_NONE)
						$srvRes = self::SEVERITY_SOFT;
				}
			}

			$result[$serviceId] = $srvRes;
		}

		return $filterResult + $result;
	}

	protected function isClassSupportGroupFiltering($className)
	{
		if(!self::isClassValid($className))
			return false;

		return method_exists($className, 'filterServicesArray');
	}

	protected static function isClassValid($className)
	{
		if(empty($className))
			return false;

		if(!class_exists($className))
			return false;

		if(!is_subclass_of($className, 'Bitrix\Sale\Delivery\Restrictions\Base'))
			return false;

		return true;
	}
}