<?php

/**
 * WARNING: nobody shouldn't rely on this code,
 * because it's FOR INTERNAL USE ONLY. Any declared
 * function can be removed or changed in future versions.
 * This code can be changed without any notifications.
 * DON'T USE it nowhere.
 *
 * @access private
 */
interface ILearnGraphRelation
{
	/**
	 * Link two nodes from $parentNodeId to $childNodeId
	 *
	 * @param int $parentNodeId
	 * @param int $childNodeId
	 * @param array of properties of the link. Currently available properties:
	 *    - 'SORT', 32-bit integer
	 *
	 * @throws Exception LearnException with code error LearnException::EXC_ERR_GR_LINK
	 */
	public static function Link ($parentNodeId, $childNodeId, $arProperties);

	/**
	 * Remove relation from $parentNodeId to $childNodeId
	 *
	 * @param int $parentNodeId
	 * @param int $childNodeId
	 *
	 * @throws Exception LearnException with code error LearnException::EXC_ERR_GR_UNLINK
	 *         if relation isn't exists => message of exception === 'EA_NOT_EXISTS'
	 */
	public static function Unlink ($parentNodeId, $childNodeId);

	/**
	 * Set property for relation from $parentNodeId to $childNodeId.
	 * Currently available properties:
	 *    - 'SORT', 32-bit integer
	 *
	 * Warning: this method DON'T checks for existence of relation
	 * (due to CDatabase transactions problems), and it will not give any error
	 * or exception in that case.
	 *
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Update() in main
	 * module (version info:
	 *    // define("SM_VERSION","11.0.12");
	 *    // define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param int $parentNodeId
	 * @param int $childNodeId
	 * @param string $propertyName ('SORT')
	 * @param mixed $value (for 'SORT' - integer)
	 *
	 * @throws Exception LearnException with code error LearnException::EXC_ERR_GR_SET_PROPERTY
	 */
	public static function SetProperty ($parentNodeId, $childNodeId, $propertyName, $value);

	/**
	 * Get property for relation from $parentNodeId to $childNodeId.
	 *
	 * @param int $parentNodeId
	 * @param int $childNodeId
	 * @param string $propertyName ('SORT')
	 *
	 * @throws Exception LearnException with code error LearnException::EXC_ERR_GR_GET_PROPERTY
	 *         if relation isn't exists => message of exception === 'EA_NOT_EXISTS'
	 *
	 * @return mixed value of property (for 'SORT' - integer)
	 */
	public static function GetProperty ($parentNodeId, $childNodeId, $propertyName);

	/**
	 * Lists immediate neighbours.
	 *
	 * @param integer id of node
	 *
	 * @return array of immediate neighbours (empty array if there is no neighbours)
	 *
	 * @example
	 * <?php
	 * $arNeighbours = ThisClass::ListImmediateNeighbours (1);
	 * var_dump ($arNeighbours);
	 * ?>
	 *
	 * output:
	 * array(2) {
	 *   [0]=>
	 *   array(4) {
	 *     ["SOURCE_NODE"]=>
	 *     int(1)
	 *     ["TARGET_NODE"]=>
	 *     int(2)
	 *     ["SORT"]=>
	 *     int(500)
	 *   }
	 *   [1]=>
	 *   array(4) {
	 *     ["SOURCE_NODE"]=>
	 *     int(4)
	 *     ["TARGET_NODE"]=>
	 *     int(1)
	 *     ["SORT"]=>
	 *     int(500)
	 *   }
	 * }
	 *
	 */
	public static function ListImmediateNeighbours ($nodeId);

	/**
	 * Lists immediate parents
	 *
	 * @param integer id of node
	 *
	 * @return array of immediate parents (empty array if there is no parents)
	 *
	 * @see example for ListImmediateNeighbours()
	 */
	public static function ListImmediateParents ($nodeId);

	/**
	 * Lists immediate childs
	 *
	 * @param integer id of node
	 *
	 * @return array of immediate childs (empty array if there is no childs)
	 *
	 * @see example for ListImmediateNeighbours()
	 */
	public static function ListImmediateChilds ($nodeId);
}

/**
 * WARNING: nobody shouldn't rely on this code,
 * because it's FOR INTERNAL USE ONLY. Any declared
 * function can be removed or changed in future versions.
 * This code can be changed without any notifications.
 * DON'T USE it nowhere.
 *
 * @access private
 */
final class CLearnGraphRelation implements ILearnGraphRelation
{
	// For bitmask:
	const NBRS_IMDT_PARENTS = 0x1;		// immediate parent neighbours
	const NBRS_IMDT_CHILDS  = 0x2;		// immediate child neighbours

	private static $arNodesCache = array();
	private static $nodesCached = 0;


	public static function ListImmediateParents ($nodeId)
	{
		return (self::_ListImmediateNeighbours ($nodeId, self::NBRS_IMDT_PARENTS));
	}

	public static function ListImmediateChilds ($nodeId)
	{
		return (self::_ListImmediateNeighbours ($nodeId, self::NBRS_IMDT_CHILDS));
	}

	public static function ListImmediateNeighbours ($nodeId)
	{
		return (self::_ListImmediateNeighbours ($nodeId, self::NBRS_IMDT_PARENTS | self::NBRS_IMDT_CHILDS));
	}

	protected function _ListImmediateNeighbours ($nodeId, $bitmaskSearchMode)
	{
		global $DB;

		$arWhere = array();

		// List parents?
		if ($bitmaskSearchMode & self::NBRS_IMDT_PARENTS)
			$arWhere[] = "TARGET_NODE='" . (int) ($nodeId + 0) . "'";

		// List childs?
		if ($bitmaskSearchMode & self::NBRS_IMDT_CHILDS)
			$arWhere[] = "SOURCE_NODE='" . (int) ($nodeId + 0) . "'";

		// Prepare string for query
		$sqlWhere = implode (' OR ', $arWhere);

		if (strlen($sqlWhere) == 0)
		{
			throw new LearnException ('EA_PARAMS: nothing to search (check search mode bitmask);',
				LearnException::EXC_ERR_GR_GET_NEIGHBOURS | LearnException::EXC_ERR_ALL_LOGIC);
		}

		if ( ! array_key_exists($sqlWhere, self::$arNodesCache) )
		{
			// Get graph edge
			$rc = $DB->Query (
				"SELECT SOURCE_NODE, TARGET_NODE, SORT
				FROM b_learn_lesson_edges
				WHERE " . $sqlWhere,
				$ignore_errors = true);

			if ($rc === false)
				throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GR_GET_NEIGHBOURS);

			$result = array();

			// Postprocessing of result
			while ($arData = $rc->Fetch())
			{
				$result[] = array (
					'SOURCE_NODE'   => $arData['SOURCE_NODE'],
					'TARGET_NODE'   => $arData['TARGET_NODE'],
					'PARENT_LESSON' => $arData['SOURCE_NODE'],
					'CHILD_LESSON'  => $arData['TARGET_NODE'],
					'SORT'          => (int) $arData['SORT']
				);
			}

			// limit static cache size to 1024 nodes
			if (self::$nodesCached < 1024)
			{
				++self::$nodesCached;
				self::$arNodesCache[$sqlWhere] = $result;
			}
		}
		else
			$result = self::$arNodesCache[$sqlWhere];


		return ($result);
	}

	public static function Link ($parentNodeId, $childNodeId, $arProperties)
	{
		global $DB;

		// reset static cache
		self::$arNodesCache = array();
		self::$nodesCached = 0;

		$args_check = is_array($arProperties) 	// must be an array
			&& (count($arProperties) === 1)
			&& ($parentNodeId > 0)
			&& ($childNodeId > 0);

		// Only SORT allowed
		$args_check = $args_check && isset ($arProperties['SORT']);

		// check SORT admitted range: number
		if (isset($arProperties['SORT']))
			$args_check = $args_check && is_numeric ($arProperties['SORT']) && is_int ($arProperties['SORT'] + 0);
		else
			$args_check = false;

		if ( ! $args_check )
		{
			throw new LearnException (
				'EA_PARAMS: ' . $parentNodeId . ' / ' . $childNodeId . ' / ' . var_export($arProperties, true), 
				LearnException::EXC_ERR_GR_LINK);
		}

		// normalize & sanitize
		{
			$sort = (int) ($arProperties['SORT'] + 0);

			$parentNodeId += 0;
			$childNodeId  += 0;
		}

		// Create graph edge
		$rc = $DB->Query (
			"INSERT INTO b_learn_lesson_edges (SOURCE_NODE, TARGET_NODE, SORT)
			VALUES ('" . $parentNodeId . "', '" . $childNodeId . "', '" . $sort . "')",
			$ignore_errors = true);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GR_LINK);
	}

	public static function Unlink ($parentNodeId, $childNodeId)
	{
		global $DB;

		// reset static cache
		self::$arNodesCache = array();
		self::$nodesCached = 0;

		$args_check = ($parentNodeId > 0) && ($childNodeId > 0);

		if ( ! $args_check )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_GR_UNLINK);

		$parentNodeId += 0;
		$childNodeId  += 0;

		// Remove graph edge
		$rc = $DB->Query (
			"DELETE FROM b_learn_lesson_edges
			WHERE SOURCE_NODE = '" . $parentNodeId . "'
				AND TARGET_NODE = '" . $childNodeId . "'",
			$ignore_errors = true);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GR_UNLINK);

		if ($rc->AffectedRowsCount() == 0)
			throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_GR_UNLINK);
	}

	public static function SetProperty ($parentNodeId, $childNodeId, $propertyName, $value)
	{
		global $DB;

		// reset static cache
		self::$arNodesCache = array();
		self::$nodesCached = 0;

		$args_check = ($parentNodeId > 0) && ($childNodeId > 0)
			&& ( in_array ($propertyName, array('SORT'), true) );

		if ($propertyName === 'SORT')
		{
			// check SORT admitted range: number
			$args_check = $args_check && is_numeric ($value) && is_int ($value + 0);
		}

		if ( ! $args_check )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_GR_SET_PROPERTY);

		$parentNodeId += 0;
		$childNodeId  += 0;

		switch ($propertyName)
		{
			case 'SORT':
				$value = (int) ($value + 0);

				$arFields = array ('SORT' => "'" . $value . "'");
			break;

			default:
				throw new LearnException ('EA_PARAMS: unknown property name: '
					. $propertyName, LearnException::EXC_ERR_GR_SET_PROPERTY);
			break;
		}

		// Update graph edge
		$rc = $DB->Update ('b_learn_lesson_edges', $arFields,
			"WHERE SOURCE_NODE='" . $parentNodeId . "'
				AND TARGET_NODE='" . $childNodeId . "'", __LINE__, false,
				false);	// we must halt on errors due to bug in CDatabase::Update();

		/**
		 * This code will be useful after bug in CDatabase::Update() will be solved
		 * and $ignore_errors setted to true in Update() call above.
		 */
		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GR_SET_PROPERTY);

		/*
		This is not correctly, because there is can be update to value, which already set in db. And not affected rows will be.
		Consistent check of existence of relation needs transaction with one more sql-prerequest,
		so don't check it because of perfomance penalties and DB::transactions problems. :(
				if ($rc->AffectedRowsCount() == 0)
					throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_GR_SET_PROPERTY);
		*/
	}

	public static function GetProperty ($parentNodeId, $childNodeId, $propertyName)
	{
		global $DB;

		$args_check = ($parentNodeId > 0) && ($childNodeId > 0)
			&& ( in_array ($propertyName, array('SORT'), true) );

		if ( ! $args_check )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_GR_GET_PROPERTY);

		$parentNodeId += 0;
		$childNodeId  += 0;

		// Prepare DB field name
		switch ($propertyName)
		{
			case 'SORT':
				$field = 'SORT';
			break;

			default:
				throw new LearnException ('EA_PARAMS: unknown property name: '
					. $propertyName, LearnException::EXC_ERR_GR_GET_PROPERTY);
			break;
		}

		// Get graph edge
		$rc = $DB->Query (
			"SELECT " . $field . "
			FROM b_learn_lesson_edges
			WHERE SOURCE_NODE='" . $parentNodeId . "'
				AND TARGET_NODE='" . $childNodeId . "'",
			$ignore_errors = true);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GR_GET_PROPERTY);

		if ( ! (($arData = $rc->Fetch()) && isset($arData[$field])) )
			throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_GR_GET_PROPERTY);

		// Postprocessing of result
		switch ($propertyName)
		{
			case 'SORT':
				$rc = (int) $arData[$field];
			break;

			default:
				throw new LearnException ('EA_PARAMS: unknown property name: '
					. $propertyName, LearnException::EXC_ERR_GR_GET_PROPERTY);
			break;
		}

		return ($rc);
	}
}
