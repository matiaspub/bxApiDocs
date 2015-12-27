<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage sale
* @copyright 2001-2014 Bitrix
*/

namespace Bitrix\Sale\Location;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Config;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Sale\Location\Util\Assert;
use Bitrix\Sale\Location\DB\BlockInserter;

Loc::loadMessages(__FILE__);

abstract class Connector extends Entity\DataManager
{
	const LINK_ID = 								0x01;
	const LINK_CODE = 								0x02;

	const DB_LOCATION_FLAG = 						'L';
	const DB_GROUP_FLAG = 							'G';

	const LSTAT_IN_NOT_CONNECTED_BRANCH = 			0x01;
	const LSTAT_IS_CONNECTOR = 						0x02;
	const LSTAT_ABOVE_CONNECTOR = 					0x03;
	const LSTAT_BELOW_CONNECTOR =					0x04;

	#####################################
	#### Entity settings
	#####################################

	/** 
	* Method returns name of table column that provides linking between location table and entity table. For example, for delivery it should return 'DELIVERY_ID'
	* Limitation: mechanism does not support compound primary keys
	*
	* @return string field name
	*/
	abstract public function getLinkField();

	/** Method should return a name of the "target" entity plus namespace but without trailing 'Table'. For example, for delivery this should be 'Bitrix\Sale\Delivery\Delivery'
	*
	* @return string table name
	*/
	abstract public function getTargetEntityName();

	/**
	* Returns a name of type filed. By default 'LOCATION_TYPE' is used for the most of. Does not make any sense if getUseGroups() returns false
	* 
	* @return string
	*/
	public static function getTypeField()
	{
		return 'LOCATION_TYPE';
	}

	/**
	* Returns a name of the filed that keep location id or code. By default 'LOCATION_ID' is used for the most. For newly-created entites this should be LOCATION_CODE (string)
	* 
	* @return string
	*/
	public static function getLocationLinkField()
	{
		return 'LOCATION_ID';
	}

	/**
	* Returns a name of the primary field of the "target" entity. By default 'ID' is used for the most. See target entity class for sure.
	* 
	* @return string
	*/
	public static function getTargetEntityPrimaryField()
	{
		return 'ID';
	}

	/**
	* Show whether or not entity use linking through groups. E.g. delivery would return true, 
	* but location groups (which can be treated as entity too) - always return false (groups cannot be connected with groups)
	* 
	* @return boolean
	*/
	public static function getUseGroups()
	{
		return true;
	}

	/**
	* Show whether or not entity use link tracking. Link tracking allows keeping information about link existence in option strings.
	* So, this is some kind of cacheing mechanism. It can be usefull, but may exceed option string length when there are lots of entity records exist.
	* 
	* @return boolean
	*/
	public static function getUseLinkTracking()
	{
		return false;
	}

	/**
	* Return the connection type for the current entity. Return value that is equal to self::LINK_ID stands for ID, otherwise - for CODE link type
	* 
	* @return integer
	*/
	public static function getConnectType()
	{
		$map = static::getMap();
		$locLinkFld = static::getLocationLinkField();

		if(isset($map[$locLinkFld]))
			return $map[$locLinkFld]['data_type'] == 'integer' ? self::LINK_ID : self::LINK_CODE;

		Assert::announceNotImplemented(Loc::getMessage('SALE_LOCATION_CONNECTOR_ENTITY_LINK_FLD_NOT_FOUND_EXCEPTION'));
	}

	/**
	* Just a shortcut to link type identification
	* 
	* @return boolean
	*/
	public static function getUseCodes()
	{
		return static::getConnectType() == static::LINK_CODE;
	}

	#####################################
	#### CRUD wrappers
	#####################################

	/**
	* Adds a new connection between location and entity
	* 
	* @param mixed[] $data data to add (See getMap() of a certain implementation for $data key details)
	* 
	* @return Bitrix\Main\Entity\AddResult
	*/
	public static function add($data = array())
	{
		$res = parent::add($data);
		if($res->isSuccess())
			static::setLinkUsage($data[static::getLinkField()], $data[static::getTypeField()], true);

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
		static::onAfterModifiy();

		return $res;
	}

	/**
	* Updates a connection between location and entity
	* 
	* @param mixed $primary relation primary key value
	* @param mixed[] $data data to update with (See getMap() of a certain implementation for $data key details)
	* 
	* @return Bitrix\Main\Entity\UpdateResult
	*/
	public static function update($primary, $data = array())
	{
		$linkFld = static::getLinkField();

		if($primary && isset($data[$linkFld])) // it will break below, at parent::delete()
			$link = static::getByPrimary($primary)->fetch();

		$res = parent::update($primary, $data);
		if($res->isSuccess() && isset($data[$linkFld]) && $data[$linkFld] != $link[$linkFld]) // if switched from one entity to another
		{
			static::resetLinkUsage($link[static::getLinkField()]); // for donor entity we need to recalc link existence
			static::setLinkUsage($data[$linkFld], $data[static::getTypeField()], true); // we know there ARE links for retsepient entity
		}

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
		static::onAfterModifiy();

		return $res;
	}

	/**
	* Removes a connection between location and entity
	* 
	* @param mixed $primary relation primary key value
	* 
	* @return Bitrix\Main\Entity\DeleteResult
	*/
	public static function delete($primary)
	{
		if($primary) // it will break below, at parent::delete()
			$link = static::getByPrimary($primary)->fetch();

		$res = parent::delete($primary);
		if($res->isSuccess())
			static::resetLinkUsage($link[static::getLinkField()]);

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
		static::onAfterModifiy();

		return $res;
	}

	public static function onAfterModifiy()
	{
	}

	/**
	* Establishes several connections between entity and locations. Could be used to add and delete selected relations.
	* 
	* @param mixed $entityPrimary primary key for an entity
	* @param integer[][]|string[][] $links array of locations and groups to link an entity with
	* 
	* 	Argument format:
	* 	array(
	* 		'L' => array(), // an array of IDs or CODEs of locations
	* 		'G' => array() // an array of IDs or CODEs of location groups
	* 	)
	* 
	* @throws Bitrix\Main\ArgumentNullException
	* 
	* @return boolean
	*/
	public static function updateMultipleForOwner($entityPrimary, $links = array(), $behaviour = array('REMOVE_ABSENT' => true))
	{
		$entityPrimary = 	Assert::expectStringNotNull($entityPrimary, '$entityPrimary');
		$links = 			static::checkUpdateLinks($links);

		$updateLocations = 	is_array($links[self::DB_LOCATION_FLAG]);
		$updateGroups = 	is_array($links[self::DB_GROUP_FLAG]) && static::getUseGroups();

		// get existed relations
		$existed = 			static::getLinkedLocations($entityPrimary);

		if($updateLocations)
			static::updateMultipleLinkType($entityPrimary, $links[self::DB_LOCATION_FLAG], $existed[self::DB_LOCATION_FLAG], self::DB_LOCATION_FLAG, $behaviour['REMOVE_ABSENT']);

		if($updateGroups)
			static::updateMultipleLinkType($entityPrimary, $links[self::DB_GROUP_FLAG], $existed[self::DB_GROUP_FLAG], self::DB_GROUP_FLAG, $behaviour['REMOVE_ABSENT']);

		static::resetLinkUsage($entityPrimary);

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
		static::onAfterModifiy();

		return true;
	}

	// removes all links with a given entity
	public static function deleteAllForOwner($entityPrimary, $behaviour = array('BATCH_MODE' => false))
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');

		if($behaviour['BATCH_MODE'])
		{
			// low-level drop
			$dbConnection = Main\HttpApplication::getConnection();
			$dbConnection->query('delete from '.static::getTableName().' where '.static::getLinkField().' = \''.$dbConnection->getSqlHelper()->forSql($entityPrimary).'\'');
		}
		else
		{
			// get existed relations
			$existed = static::getLinkedLocations($entityPrimary);

			static::updateMultipleLinkType($entityPrimary, array(), $existed[self::DB_LOCATION_FLAG], self::DB_LOCATION_FLAG, true);

			if(static::getUseGroups())
				static::updateMultipleLinkType($entityPrimary, array(), $existed[self::DB_GROUP_FLAG], self::DB_GROUP_FLAG, true);
		}

		static::setLinkUsage($entityPrimary, self::DB_LOCATION_FLAG, false);
		static::setLinkUsage($entityPrimary, self::DB_GROUP_FLAG, false);

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
		static::onAfterModifiy();
	}

	public static function deleteAll()
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbConnection->query('truncate table '.static::getTableName());

		static::deleteLinkUsageOption();

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
		static::onAfterModifiy();
	}

	/**
	 * Removes all links and creates new ones
	 * 
	 */
	public static function resetMultipleForOwner($entityPrimary, $links = array())
	{
		$entityPrimary = 	Assert::expectStringNotNull($entityPrimary, '$entityPrimary');
		$links = 			static::checkUpdateLinks($links);

		static::deleteAllForOwner($entityPrimary, array('BATCH_MODE' => true));

		$map = 				static::getMap();
		$linkFld = 			static::getLinkField();
		$locationLinkFld = 	static::getLocationLinkField();
		$typeFld = 			static::getTypeField();

		$fields = array(
				$linkFld => $map[$linkFld], // DELIVERY_ID, SITE_ID, smth else
				$locationLinkFld => $map[$locationLinkFld] // LOCATION_ID or LOCATION_CODE or smth else
		);
		if($useGroups = static::getUseGroups())
			$fields[$typeFld] = $map[$typeFld]; // LOCATION_TYPE: L or G

		$inserter = new BlockInserter(array(
			'tableName' => static::getTableName(),
			'exactFields' => $fields
		));

		$smthAdded = false;
		if(is_array($links[self::DB_LOCATION_FLAG]))
		{
			foreach($links[self::DB_LOCATION_FLAG] as $id)
			{
				$data = array(
					$linkFld => $entityPrimary,
					$locationLinkFld => $id,
				);
				if($useGroups)
					$data[$typeFld] = self::DB_LOCATION_FLAG;

				$inserter->insert($data);
				$smthAdded = true;
			}
		}
		static::setLinkUsage($entityPrimary, self::DB_LOCATION_FLAG, $smthAdded);

		$smthAdded = false;
		if(is_array($links[self::DB_GROUP_FLAG]) && $useGroups)
		{
			foreach($links[self::DB_GROUP_FLAG] as $id)
			{
				$data = array(
					$linkFld => $entityPrimary,
					$locationLinkFld => $id,
				);
				if($useGroups)
					$data[$typeFld] = self::DB_GROUP_FLAG;

				$inserter->insert($data);
				$smthAdded = true;
			}
		}
		static::setLinkUsage($entityPrimary, self::DB_GROUP_FLAG, $smthAdded);

		$inserter->flush();

		$GLOBALS['CACHE_MANAGER']->ClearByTag('sale-location-data');
	}

	private static function updateMultipleLinkType($entityPrimary, $links, $existed, $linkType = self::DB_LOCATION_FLAG, $doRemove = true)
	{
		$useGroups = static::getUseGroups();

		$smthAdded = false;
		foreach($links as $k => $loc)
		{
			if(!isset($existed[$loc]))
			{
				$data = array(
					static::getLinkField() => $entityPrimary,
					static::getLocationLinkField() => $loc
				);
				if($useGroups)
					$data[static::getTypeField()] = $linkType;

				$res = static::add($data);
				if(!$res->isSuccess())
					throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_CONNECTOR_ENTITY_CANNOT_ADD_EXCEPTION'));

				$smthAdded = true;
			}
			else
				unset($existed[$loc]);
		}

		if($doRemove && !empty($existed))
		{
			foreach($existed as $loc => $k)
			{
				$data = array(
					static::getLinkField() => $entityPrimary,
					static::getLocationLinkField() => $loc
				);
				if($useGroups)
					$data[static::getTypeField()] = $linkType;

				$res = static::delete($data);
				if(!$res->isSuccess())
					throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_CONNECTOR_ENTITY_CANNOT_DELETE_EXCEPTION'));
			}
		}
	}

	#####################################
	#### Getters
	#####################################

	/**
	* Returns a set of locations that connected with a given entity, directly and, optionally, through groups.
	*
	* @param mixed $entityPrimary primary key for an entity
	* @param mixed[] $parameters filter and select parameters in ORM getlist() style, functionality is enought for usage, but limited. See limitations in code comments.
	* @param string[] $behaviour a set of behaviour options:
	*	<ul>
	*		<li>GET_LINKED_THROUGH_GROUPS boolean (default true) - if set to true, locations will be searched also throurgh groups (if entity connector supports groups)</li>
	*	</ul>
	*
	* @throws ArgumentNullException
	* @throws NotImplementedException
	*
	* @return String an SQL-query string that, being called, returns a list of locations
	*/
	public static function getConnectedLocationsQuery($entityPrimary, $parameters = array(), $behaviour = array('GET_LINKED_THROUGH_GROUPS' => false))
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');

		if(!is_array($parameters))
			$parameters = array();
		if(!is_array($behaviour))
			$behaviour = array();
		if(!isset($behaviour['GET_LINKED_THROUGH_GROUPS']))
			$behaviour['GET_LINKED_THROUGH_GROUPS'] = false;

		$useGroups = GroupTable::checkGroupUsage() && static::getUseGroups(); // check if we have groups in project and entity uses groups
		$getLinkedThroughGroups = $behaviour['GET_LINKED_THROUGH_GROUPS'];
		if(!$useGroups)
			$getLinkedThroughGroups = false;

		$connType = static::getConnectType();

		// proxy select
		$select = array();
		if(!is_array($parameters['select']))
			$select = array('' => 'LOCATION');
		else
		{
			foreach($parameters['select'] as $k => $v)
			{
				if($v == '*')
					$select[''] = 'LOCATION';
				else
				{
					if(is_numeric($k) && strpos((string) $v, '.') === false) // is NOT a reference
					{
						$k = $v;
					}

					$select[$k] = 'LOCATION.'.$v;
				}
			}
		}

		// proxy filter
		$filter = array();
		if(is_array($parameters['filter']))
		{
			foreach($parameters['filter'] as $k => $v)
			{
				$filter['LOCATION.'.$k] = $v;
			}
		}

		// proxy order
		$order = array();
		if(is_array($parameters['order']))
		{
			//if($getLinkedThroughGroups)
				Assert::announceNotImplemented('Sorry, order-over-union clause is not implemented currently.');

			foreach($parameters['order'] as $k => $v)
				$order['LOCATION.'.$k] = $v;
		}

		if(is_array($parameters['runtime']))
			Assert::announceNotImplemented('Sorry, runtime clause is not implemented currently.');
		
		$sqls = array();

		if(static::checkLinkUsage($entityPrimary, self::DB_LOCATION_FLAG))
		{
			$strictFilter = array_merge($filter, array(
				'='.static::getLinkField() => $entityPrimary
			));

			if($useGroups)
				$strictFilter['='.static::getTypeField()] = self::DB_LOCATION_FLAG;

			$query = new Entity\Query(self::getEntity());
			$query
				->setSelect($select)
				->setFilter($strictFilter)
				->setOrder($order);

			$sqls[] = $query->getQuery();
		}

		if(static::checkLinkUsage($entityPrimary, self::DB_GROUP_FLAG) && $getLinkedThroughGroups)
		{
			$query = new Entity\Query(static::getEntity());

			if($connType == self::LINK_CODE) // entity connected by CODE
			{
				$query
					->registerRuntimeField(
						'G',
						array(
							'data_type' => '\Bitrix\Sale\Location\GroupTable',
							'reference' => array(
								'=this.'.static::getLocationLinkField() => 'ref.CODE',
								'=this.'.static::getTypeField() => array('?', self::DB_GROUP_FLAG)
							),
							'join_type' => 'inner'
						)
					)
					->registerRuntimeField(
						'LG',
						array(
							'data_type' => '\Bitrix\Sale\Location\GroupLocation',
							'reference' => array(
								'=this.G.ID' => 'ref.'.GroupLocationTable::getLinkField(),
							),
							'join_type' => 'inner'
						)
					)
					->registerRuntimeField(
						'LOCATION',
						array(
							'data_type' => '\Bitrix\Sale\Location\Location',
							'reference' => array(
								'=this.LG.LOCATION_ID' => 'ref.ID'
							),
							'join_type' => 'inner'
						)
					)
					->setSelect($select)
					->setFilter(array_merge($filter, array(
						'='.static::getLinkField() => $entityPrimary
					)))
					->setOrder($order);

				/*
				select LOCATION.*
					from b_sale_delivery2location DL
						inner join b_sale_location_group G on (G.CODE = DL.LOCATION_CODE and DL.LOCATION_TYPE = self::DB_GROUP_FLAG)
						inner join b_sale_location2location_group LG on (LG.LOCATION_GROUP_ID = G.ID)
						inner join b_sale_location LOCATION on (LG.LOCATION_ID = LOCATION.ID)
					where
						DL.DELIVERY_ID = 2;
				*/
			}
			else // entity connected by ID
			{
				$query
					->registerRuntimeField(
						'LG',
						array(
							'data_type' => '\Bitrix\Sale\Location\GroupLocation',
							'reference' => array(
								'=this.'.static::getLocationLinkField() => 'ref.'.GroupLocationTable::getLinkField(),
								'=this.'.static::getTypeField() => array('?', self::DB_GROUP_FLAG)
							),
							'join_type' => 'inner'
						)
					)
					->registerRuntimeField(
						'LOCATION',
						array(
							'data_type' => '\Bitrix\Sale\Location\Location',
							'reference' => array(
								'=this.LG.LOCATION_ID' => 'ref.ID'
							),
							'join_type' => 'inner'
						)
					)
					->setSelect($select)
					->setFilter(array_merge($filter, array(
						'='.static::getLinkField() => $entityPrimary
					)))
					->setOrder($order);

				/*
				select LOCATION.*
					from b_sale_delivery2location DL
						inner join b_sale_location2location_group LG on (LG.LOCATION_GROUP_ID = DL.LOCATION_ID and DL.LOCATION_TYPE = self::DB_GROUP_FLAG)
						inner join b_sale_location LOCATION on (LG.LOCATION_ID = LOCATION.ID)
					where
						DL.DELIVERY_ID = 2;
				*/
			}

			$sqls[] = $query->getQuery();
		}

		if(empty($sqls)) // entity is not connected, so it means "all connected"
			return false;

		return static::unionize($sqls);
	}

	/**
	 * More preferable alias for getConnectedLocationsQuery()
	 */
	public static function getConnectedLocationsSql($entityPrimary, $parameters = array(), $behaviour = array('GET_LINKED_THROUGH_GROUPS' => false))
	{
		return static::getConnectedLocationsQuery($entityPrimary, $parameters, $behaviour);
	}

	public static function getConnectedLocations($entityPrimary, $parameters = array(), $behaviour = array('GET_LINKED_THROUGH_GROUPS' => false))
	{
		$query = static::getConnectedLocationsQuery($entityPrimary, $parameters, $behaviour);

		if(!$query)
			return new DB\ArrayResult(array());

		return static::queryPage(
			$query,
			$parameters['limit'],
			$parameters['offset']);
	}

	/**
	* Returns a set of location groups that connected with a given entity.
	*
	* @param mixed $entityPrimary primary key for an entity
	* @param mixed[] $parameters for ORM getList()
	*
	* @throws ArgumentNullException
	* @throws NotImplementedException
	*
	* @return Bitrix\Main\DB\Result list of locations
	*/
	public static function getConnectedGroups($entityPrimary, $parameters = array())
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');

		if(!static::getUseGroups())
			Assert::announceNotSupported(Loc::getMessage('SALE_LOCATION_CONNECTOR_ENTITY_DOESNT_SUPPORT_GROUPS'));

		if(!is_array($parameters))
			$parameters = array();

		$parameters['runtime']['C'] = array(
			'data_type' => static::getEntity()->getFullName(),
			'reference' => array(
				'=ref.'.static::getLinkField() => array('?', $entityPrimary),
				'=ref.'.static::getTypeField() => array('?', 'G')
			),
			'join_type' => 'inner'
		);

		$parameters['runtime']['C']['reference']['=ref.'.static::getLocationLinkField()] = (static::getConnectType() == self::LINK_CODE ? 'this.CODE' : 'this.ID');

		return GroupTable::getList($parameters);
	}

	// returns list of connected entities for location with ID == $locationPrimary, with an optional filter applied 
	public static function getConnectedEntites($locationPrimary, $parameters = array())
	{
		return static::getConnectedEntitiesByCondition($locationPrimary, 'id', $parameters);
	}

	// returns list of connected entities for location with CODE == $locationPrimary, with an optional filter applied 
	public static function getConnectedEntitesByCode($locationPrimary, $parameters = array()) // getConnectedEntitiesByLocationCode
	{
		return static::getConnectedEntitiesByCondition($locationPrimary, 'code', $parameters);
	}

	// returns sql-select query that can be embeded to another sql query in 'ID in (select * from ...)' manner
	public static function getConnectedEntitiesQuery($locationPrimary, $linkType = 'id', $parameters = array()) // // getConnectedEntitiesSql
	{
		if($linkType == 'id')
			$locationPrimary = Assert::expectIntegerPositive($locationPrimary, '$locationPrimary');
		else
			$locationPrimary = Assert::expectStringNotNull($locationPrimary, '$locationPrimary');

		$useGroups = 			GroupTable::checkGroupUsage() && static::getUseGroups(); // check if we have groups in project and entity uses groups
		$useCodes = 			static::getUseCodes(); // this entity uses codes
		$groupUseCodes =		GroupLocationTable::getUseCodes(); // group entity uses codes
		
		$typeFld = 				static::getTypeField();/*LOCATION_TYPE*/
		$linkFld = 				static::getLinkField();/*DELIVERY_ID*/
		$locationLinkFld = 		static::getLocationLinkField();/*LOCATION_ID*/
		$targetPrimaryFld = 	static::getTargetEntityPrimaryField();/*ID*/
		$groupLocationLinkFld = GroupLocationTable::getLocationLinkField();/*LOCATION_ID*/
		$groupLinkFld = 		GroupLocationTable::getLinkField();/*LOCATION_GROUP_ID*/

		$seachById = $linkType == 'id';

		$dbConnection = Main\HttpApplication::getConnection();

		if(!is_array($parameters))
			$parameters = array();

		if(is_array($parameters['runtime']))
			Assert::announceNotImplemented('Sorry, runtime clause is not implemented currently.');

		$order = array();
		if(is_array($parameters['order']))
			Assert::announceNotImplemented('Sorry, order-over-union clause is not implemented currently.');

		$filter = array();
		if(is_array($parameters['filter']) && !empty($parameters['filter']))
			$filter = $parameters['filter'];

		$select = array('*');
		if(is_array($parameters['select']) && !empty($parameters['select']))
			$select = $parameters['select'];

		/*
		query example when working with delivery:

		select distinct D.* from b_sale_delivery D
			inner join b_sale_delivery2location DL on D.ID = DL.DELIVERY_ID and DL.LOCATION_TYPE = 'L'
			inner join b_sale_location L1 on L1.CODE = DL.LOCATION_ID
			inner join b_sale_location L2 on L2.ID(there will be CODE, if we search by code) = 65683 and L2.LEFT_MARGIN >= L1.LEFT_MARGIN and L2.RIGHT_MARGIN <= L1.RIGHT_MARGIN;
		*/

		$query = new Entity\Query(static::getTargetEntityName());

		$DLCondition = array(
			'=this.'.$targetPrimaryFld/*ID*/ => 'ref.'.$linkFld/*DELIVERY_ID*/
		);
		if($useGroups)
			$DLCondition['=ref.'.$typeFld/*LOCATION_TYPE*/] = array('?', self::DB_LOCATION_FLAG);

		$query
			->registerRuntimeField(
				'DL',
				array(
					'data_type' => get_called_class(),
					'reference' => $DLCondition,
					'join_type' => 'inner'
				)
			)
			->registerRuntimeField(
				'L1',
				array(
					'data_type' => '\Bitrix\Sale\Location\Location',
					'reference' => array(
						'=this.DL.'.$locationLinkFld/*LOCATION_ID*/ => 'ref.'.($useCodes ? 'CODE' : 'ID'),
					),
					'join_type' => 'inner'
				)
			)
			->registerRuntimeField(
				'L2',
				array(
					'data_type' => '\Bitrix\Sale\Location\Location',
					'reference' => array(
						'=ref.'.($seachById ? 'ID' : 'CODE') =>  array('?', $locationPrimary), // among parents we have element with ID or CODE is equal to $locationPrimary
						'>=ref.LEFT_MARGIN' => 'this.L1.LEFT_MARGIN', // and its left_margin
						'<=ref.RIGHT_MARGIN' => 'this.L1.RIGHT_MARGIN' // and right_margin fit our restrictions
					),
					'join_type' => 'inner'
				)
			)
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order);

		if(!$useGroups)
		{
			// emulate "select distinct"
			$query->setGroup($select);

			return $query->getQuery();
		}
		else
		{
			$sqls = array($query->getQuery());

			$query = new Entity\Query(static::getTargetEntityName());

			/*
			query example when working with delivery:

			select D.* from b_sale_delivery D
				inner join b_sale_delivery2location DL on D.ID = DL.DELIVERY_ID and DL.LOCATION_TYPE = 'G'
				inner join b_sale_location_group G on G.CODE = DL.LOCATION_ID (if this entity uses ID, skip this join)
				inner join b_sale_grouplocation GL on GL.LOCATION_GROUP_ID = G.ID (if this entity uses ID, there will be DL.LOCATION_ID)
				inner join b_sale_location L1 on L1.ID (there will be CODE, if grouplocation entity uses CODE) = GL.LOCATION_ID
				inner join b_sale_location L2 on L2.ID (there will be CODE, if we seach by code) = 65683 and L2.LEFT_MARGIN >= L1.LEFT_MARGIN and L2.RIGHT_MARGIN <= L1.RIGHT_MARGIN;
			*/

			$query
				->registerRuntimeField(
					'DL',
					array(
						'data_type' => get_called_class(),
						'reference' => array(
							'=this.'.$targetPrimaryFld/*ID*/ => 'ref.'.$linkFld/*DELIVERY_ID*/,
							'=ref.'.$typeFld/*LOCATION_TYPE*/ => array('?', self::DB_GROUP_FLAG)
						),
						'join_type' => 'inner'
					)
				);

			if($useCodes)
			{
				$query
					->registerRuntimeField(
						'G',
						array(
							'data_type' => '\Bitrix\Sale\Location\Group',
							'reference' => array(
								'=this.DL.'.$locationLinkFld/*LOCATION_ID*/ => 'ref.CODE', //$useCodes == true here, so always CODE
							),
							'join_type' => 'inner'
						)
					);
			}

			$query
				->registerRuntimeField(
					'GL',
					array(
						'data_type' => '\Bitrix\Sale\Location\GroupLocation',
						'reference' => array(
							($useCodes ? '=this.G.ID' : '=this.DL.'.$locationLinkFld/*LOCATION_ID*/) => 'ref.'.$groupLinkFld/*LOCATION_GROUP_ID*/
						),
						'join_type' => 'inner'
					)
				)
				->registerRuntimeField(
					'L1',
					array(
						'data_type' => '\Bitrix\Sale\Location\Location',
						'reference' => array(
							'=this.GL.'.$groupLocationLinkFld/*LOCATION_ID*/ => 'ref.'.($groupUseCodes ? 'CODE' : 'ID'),
						),
						'join_type' => 'inner'
					)
				)
				->registerRuntimeField(
					'L2',
					array(
						'data_type' => '\Bitrix\Sale\Location\Location',
						'reference' => array(
							'=ref.'.($seachById ? 'ID' : 'CODE') =>  array('?', $locationPrimary),
							'>=ref.LEFT_MARGIN' => 'this.L1.LEFT_MARGIN',
							'<=ref.RIGHT_MARGIN' => 'this.L1.RIGHT_MARGIN'
						),
						'join_type' => 'inner'
					)
				)
				->setSelect($select)
				->setFilter($filter)
				->setOrder($order);

			$sqls[] = $query->getQuery();

			return static::unionize($sqls);
		}
	}

	protected static function getConnectedEntitiesByCondition($locationPrimary, $linkType = 'id', $parameters = array())
	{
		$useGroups = GroupTable::checkGroupUsage() && static::getUseGroups(); // check if we have groups in project and entity uses groups

		$sql = static::getConnectedEntitiesQuery($locationPrimary, $linkType, $parameters);
		$res = static::queryPage($sql, $parameters['limit'], $parameters['offset']);

		return $res;
	}

	protected static function getLinkedLocations($entityPrimary)
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');

		$existed = array();
		$linkFld = static::getLocationLinkField();
		$typeFld = static::getTypeField();
		$res = static::getList(array('filter' => array(static::getLinkField() => $entityPrimary)));
		while($item = $res->fetch())
		{
			if(!in_array($item[static::getTypeField()], array(self::DB_GROUP_FLAG, self::DB_LOCATION_FLAG))) // strange record found. skip it
				continue;

			$existed[$item[static::getTypeField()]][$item[$linkFld]] = true;
		}

		return $existed;
	}

	/**
	 * Functions for massive check for link type
	 * 
	 * 
	 */
	public static function getLinkStatusForMultipleNodes($nodeInfo = array(), $entityPrimary, $connectors = false) // rename to: getConnectionStatusForMultipleNodes
	{
		$nodeInfo = Assert::expectArray($nodeInfo, '$nodeInfo');
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');

		$result = array();

		if(!static::checkLinkUsageAny($entityPrimary)) // if there are no links at all, connection virtually exists
		{
			foreach($nodeInfo as $node)
			{
				$result[$node['ID']] = self::LSTAT_BELOW_CONNECTOR;
			}

			return $result;
		}

		if(!is_array($connectors))
			$connectors = static::getConnectedLocationsInfo($entityPrimary);

		foreach($nodeInfo as $node)
		{
			$node = Assert::expectNotEmptyArray($node, '$nodeInfo[]');
			$node['ID'] = Assert::expectIntegerPositive($node['ID'], '$nodeInfo[][ID]');
			$node['LEFT_MARGIN'] = Assert::expectIntegerNonNegative($node['LEFT_MARGIN'], '$nodeInfo[][LEFT_MARGIN]');
			$node['RIGHT_MARGIN'] = Assert::expectIntegerPositive($node['RIGHT_MARGIN'], '$nodeInfo[][RIGHT_MARGIN]');

			$result[$node['ID']] = false;
			foreach($connectors as $connector)
			{
				if($connector['ID'] == $node['ID'])
				{
					$result[$node['ID']] = self::LSTAT_IS_CONNECTOR;
					break;
				}
				elseif($node['LEFT_MARGIN'] >= $connector['LEFT_MARGIN'] && $node['RIGHT_MARGIN'] <= $connector['RIGHT_MARGIN'])
				{
					$result[$node['ID']] = self::LSTAT_BELOW_CONNECTOR;
					break;
				}
				elseif($node['LEFT_MARGIN'] <= $connector['LEFT_MARGIN'] && $node['RIGHT_MARGIN'] >= $connector['RIGHT_MARGIN'])
				{
					$result[$node['ID']] = self::LSTAT_ABOVE_CONNECTOR;
					break;
				}
			}

			if(!$result[$node['ID']])
				$result[$node['ID']] = self::LSTAT_IN_NOT_CONNECTED_BRANCH;
		}

		return $result;
	}

	/**
	 * Check if location is connected with entity
	 * 
	 * @param $entityPrimary mixed Entity being checked
	 * @param $locationPrimary mixed Location being checked. Could be a value of ID or CODE depending on what connection type is selected (see below)
	 * @param $behaviour mixed[] A set of flags that modify function behaviour
	 * 		<li> LOCATION_LINK_TYPE string One of: ID, CODE, AUTO. 
	 * 			If ID, than match by ID is used (default, for compatibility), if CODE than match by CODE. 
	 * 			In case of AUTO the target field value depends on entity connect type.
	 * 
	 * @return boolean
	 */
	public static function checkConnectionExists($entityPrimary, $locationPrimary, array $behaviour = array('LOCATION_LINK_TYPE' => 'ID'))
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');
		$locationPrimary = Assert::expectStringNotNull($locationPrimary, '$locationPrimary');

		if(!isset($behaviour['LOCATION_LINK_TYPE']))
		{
			$behaviour['LOCATION_LINK_TYPE'] = 'ID';
		}
		else
		{
			$behaviour['LOCATION_LINK_TYPE'] = Assert::expectEnumerationMember($behaviour['LOCATION_LINK_TYPE'], array('AUTO', 'ID', 'CODE'), '$behaviour[LOCATION_LINK_TYPE]');
		}

		if(!static::checkLinkUsageAny($entityPrimary)) // if there are no links at all, connection virtually exists
		{
			return true;
		}

		if($behaviour['LOCATION_LINK_TYPE'] == 'AUTO')
		{
			$field = static::getUseCodes() ? 'CODE' : 'ID';
		}
		else
		{
			$field = $behaviour['LOCATION_LINK_TYPE'];
		}

		$node = LocationTable::getList(
			array(
				'filter' => array('='.$field => $locationPrimary),
				'select' => array('ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'),
				'limit' => 1
			)
		)->fetch();

		if(!intval($node['ID']))
		{
			throw new \Bitrix\Sale\Location\Tree\NodeNotFoundException(false, array('INFO' => array($field => $locationPrimary)));
		}

		$result = static::getLinkStatusForMultipleNodes(array($node), $entityPrimary);

		return $result[$node['ID']] == self::LSTAT_IS_CONNECTOR || $result[$node['ID']] == self::LSTAT_BELOW_CONNECTOR;
	}

	// a wrapper to getConnectedLocations which returns simple info about connected locations
	protected static function getConnectedLocationsInfo($entityPrimary)
	{
		$result = array();

		$res = static::getConnectedLocations($entityPrimary, array('select' => array(
				'ID' => 'ID',
				'LEFT_MARGIN' => 'LEFT_MARGIN',
				'RIGHT_MARGIN' => 'RIGHT_MARGIN',
				'LNAME' => 'NAME.NAME',
			),
			'filter' => array('NAME.LANGUAGE_ID' => LANGUAGE_ID)
		), array('GET_LINKED_THROUGH_GROUPS' => true));

		while($item = $res->fetch())
			$result[] = $item;

		return $result;
	}

	#####################################
	#### Link tracking
	#####################################

	public static function checkLinkUsageAny($entityPrimary)
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');

		return static::checkLinkUsage($entityPrimary, self::DB_LOCATION_FLAG) || static::checkLinkUsage($entityPrimary, self::DB_GROUP_FLAG);
	}

	/**
	* Check if there is a link between entity and locations, optionally of a certain type (default: locations)
	*
	* @param mixed $entityPrimary primary key for an entity
	* @param string $linkType link type (G or L (default))
	*
	* @throws ArgumentNullException
	*
	* @return boolean
	*/
	public static function checkLinkUsage($entityPrimary, $linkType = self::DB_LOCATION_FLAG)
	{
		$entityPrimary = Assert::expectStringNotNull($entityPrimary, '$entityPrimary');
		$linkType = Assert::expectEnumerationMember(
			$linkType,
			array(self::DB_LOCATION_FLAG, self::DB_GROUP_FLAG),
			'$linkType'
		);

		if(!static::getUseLinkTracking())
			return true; // force to true if link tracking is off

		$useGroups = static::getUseGroups();

		if(!$useGroups && $linkType == self::DB_GROUP_FLAG)
			return false; // we know we dont use groups

		$usageFlags = static::getLinkUsageOptionValue();

		if(isset($usageFlags[$entityPrimary][$linkType]))
			return $usageFlags[$entityPrimary][$linkType];

		$strictFilter = array(
			static::getLinkField() => $entityPrimary
		);
		if($useGroups)
			$strictFilter['LOCATION_TYPE'] = self::DB_LOCATION_FLAG;

		$usageFlags[$entityPrimary][self::DB_LOCATION_FLAG] = !!static::getList(array(
			'limit' => 1,
			'filter' => $strictFilter
		))->fetch();

		if($useGroups)
		{
			$usageFlags[$entityPrimary][self::DB_GROUP_FLAG] = !!static::getList(array(
				'limit' => 1,
				'filter' => array(
					static::getLinkField() => $entityPrimary,
					'LOCATION_TYPE' => self::DB_GROUP_FLAG
				)
			))->fetch();
		}

		static::setLinkUsageOptionValue($usageFlags);

		return $usageFlags[$entityPrimary];
	}

	/**
	* Function switches link usage to "on\off" position, for a certain entity and connection type (to location or to group)
	*
	*/
	private static function setLinkUsage($entityPrimary, $linkType = self::DB_LOCATION_FLAG, $way = true)
	{
		if(!static::getUseLinkTracking())
			return;

		if(!in_array($linkType, array(self::DB_LOCATION_FLAG, self::DB_GROUP_FLAG)))
			$linkType = self::DB_LOCATION_FLAG;

		$usageFlags = static::getLinkUsageOptionValue();
		$usageFlags[$entityPrimary][$linkType] = $way;

		static::setLinkUsageOptionValue($usageFlags);
	}

	/**
	* Function re-reads link existence and set options correctly
	*
	*/
	private static function resetLinkUsage($entityPrimary)
	{
		if(!static::getUseLinkTracking())
			return;

		$useGroups = static::getUseGroups();
		$typeFld = static::getTypeField();
		$groupBy = array();
		if($useGroups)
			$groupBy[] = $typeFld;

		$res = static::getList(array(
			'filter' => array(
				static::getLinkField() => $entityPrimary
			),
			'group' => $groupBy
		));

		$hasLocationLink = false;
		$hasGroupLink = false;
		while($item = $res->fetch())
		{
			if(isset($item[$typeFld]) && $item[$typeFld] == self::DB_GROUP_FLAG)
				$hasGroupLink = true;
			else
				$hasLocationLink = true;
		}

		static::setLinkUsage($entityPrimary, self::DB_LOCATION_FLAG, $hasLocationLink);
		static::setLinkUsage($entityPrimary, self::DB_GROUP_FLAG, $hasGroupLink);
	}

	/**
	* Function returns option value (or an empty array, if option is not set)
	*
	*/
	private static function getLinkUsageOptionValue()
	{
		$usageFlagsOpt = Config\Option::get("sale", static::getLinkOptionName());
		if(!strlen($usageFlagsOpt) || !is_array($usageFlags = unserialize($usageFlagsOpt)))
			$usageFlags = array();

		return $usageFlags;
	}

	/**
	* Function sets option value
	*
	*/
	private static function setLinkUsageOptionValue($usageFlags)
	{
		Config\Option::set("sale", static::getLinkOptionName(), serialize($usageFlags), '');
	}

	private static function deleteLinkUsageOption()
	{
		Config\Option::delete("sale", array('name' => static::getLinkOptionName()));
	}

	private static function getLinkOptionName()
	{
		return 'link_option_'.static::getTableName();
	}

	#####################################
	#### Util
	#####################################

	private static function checkUpdateLinks($links)
	{
		$useCodes = static::getUseCodes();
		$useGroups = static::getUseGroups();

		$locationArgName = 	'$links['.self::DB_LOCATION_FLAG.']';
		$groupArgName = 	'$links['.self::DB_GROUP_FLAG.']';

		if(is_array($links[self::DB_LOCATION_FLAG]))
		{
			if($useCodes)
				$links[self::DB_LOCATION_FLAG] = Assert::expectArrayOfUniqueStringNotNull($links[self::DB_LOCATION_FLAG], $locationArgName);
			else
				$links[self::DB_LOCATION_FLAG] = Assert::expectArrayOfUniqueIntegerNotNull($links[self::DB_LOCATION_FLAG], $locationArgName);
		}

		if(is_array($links[self::DB_GROUP_FLAG]))
		{
			if(!$useGroups)
				Assert::announceNotSupported(Loc::getMessage('SALE_LOCATION_CONNECTOR_ENTITY_DOESNT_SUPPORT_GROUPS'));

			if($useCodes)
				$links[self::DB_GROUP_FLAG] = Assert::expectArrayOfUniqueStringNotNull($links[self::DB_GROUP_FLAG], $groupArgName);
			else
				$links[self::DB_GROUP_FLAG] = Assert::expectArrayOfUniqueIntegerNotNull($links[self::DB_GROUP_FLAG], $groupArgName);
		}

		return $links;
	}

	/**
	* A special paginator for UNION-like queries
	*
	* @param string $sql SQL select query to execute, wrapped in union
	* @param integer $limit record limit to fetch (default: 0)
	* @param integer $offset offset to start fetching records from (default: 0)
	*
	* @return Bitrix\Main\DB\Result
	*/
	protected static function queryPage($sql, $limit = 0, $offset = 0)
	{
		$artificialNav = false;
		$limit = intval($limit);
		$offset = intval($offset);

		// todo: Luke, use Bitrix\Main\DB\Paginator here

		$dbConnection = Main\HttpApplication::getConnection();

		if($limit)
		{
			if($dbConnection->getType() == 'mysql')
			{
				$sql .= ' limit '.($offset ? $offset.', ' : '').$limit;
			}
			else
			{
				$artificialNav = true;
			}
		}

		$res = $dbConnection->query($sql);

		if($artificialNav)
		{
			$result = array();

			$i = -1;
			while($item = $res->fetch())
			{
				$i++;

				if($i < $offset)
					continue;

				if($i >= $offset + $limit)
					break;

				$result[] = $item;
			}

			return new DB\ArrayResult($result);
		}
		else
			return $res;
	}

	/**
	* Wraps a set of sqls to a union
	*
	* @param string[] $sqls a set of SQL select queries to wrap
	*
	* @return string
	*/
	public static function unionize($sqls)
	{
		if(!is_array($sqls))
			return $sqls;

		if(count($sqls) == 1)
			return array_shift($sqls);

		//foreach($sqls as &$sql)
		//	$sql = '('.$sql.')';

		unset($sql);

		return implode(' union ', $sqls);
	}

	// function accepts denormalized list of location IDs on input, then makes it normal and returns list of IDs or CODEs, depending on current entity settings
	public static function normalizeLocationList($denormalizedList = array())
	{
		if(empty($denormalizedList))
			return array();

		$useCodes = static::getUseCodes();

		$denormalizedList = array_flip($denormalizedList);

		$query = new Entity\Query('Bitrix\Sale\Location\Location');

		$query->setSelect(array(
			'PARENT_ID',
			'ID',
			'CODE',
			'LNAME' => 'NAME.NAME' // tmp
		));

		$query->setFilter(array('NAME.LANGUAGE_ID' => LANGUAGE_ID)); // tmp
		$query->setOrder(array('LEFT_MARGIN' => 'desc')); // important
		
		$res = $query->exec();

		// make table of children count for each node

		$relations = array();
		$selected = array();
		while($item = $res->fetch())
		{
			if(!isset($relations[$item['ID']]['name'])) // tmp
				$relations[$item['ID']]['name'] = $item['LNAME'];

			if(!isset($relations[$item['ID']]['total']))
				$relations[$item['ID']]['total'] = 0;

			if(!isset($relations[$item['ID']]['selected']))
				$relations[$item['ID']]['selected'] = 0;

			///
			if($item['PARENT_ID'] != 0)
			{
				if(!isset($relations[$item['PARENT_ID']]['total']))
					$relations[$item['PARENT_ID']]['total'] = 0;
				if(!isset($relations[$item['PARENT_ID']]['selected']))
					$relations[$item['PARENT_ID']]['selected'] = 0;
			}

			$relations[$item['PARENT_ID']]['total'] += (1 + $relations[$item['ID']]['total']);

			if(isset($denormalizedList[$item['ID']]))
			{
				$relations[$item['PARENT_ID']]['selected'] += (1 + $relations[$item['ID']]['selected']);
				$selected[$item['ID']] = $item;
			}
		}
		
		// now make up list of nodes to remove
		$removeItems = array();
		$removeChildrenOf = array();
		foreach($relations as $id => $rel)
		{
			if($rel['total'] > 0) // item has children
			{
				if($rel['total'] == $rel['selected']) // selected N of N of all children, remove all those children
				{
					if(isset($selected[$id])) // item should be selected itself to remove its children
						$removeChildrenOf[$id] = true;
				}
				elseif($rel['selected'] > 0) // selected M of N of children, where M > 0, so remove item itself
					$removeItems[$id] = true;
			}
		}

		$normalized = array();
		foreach($selected as $id => $item)
		{
			if(!($item['PARENT_ID'] && $removeChildrenOf[$item['PARENT_ID']]) && !$removeItems[$item['ID']])
				$normalized[] = $item[$useCodes ? 'CODE' : 'ID'];
		}

		return $normalized;
	}
}
