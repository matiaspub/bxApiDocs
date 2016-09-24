<?php

/**
 * WARNING: nobody shouldn't rely on this code,
 * because it's FOR INTERNAL USE ONLY. Any declared
 * function can be removed or changed in future versions.
 * This code can be changed without any notifications.
 * DON'T USE it nowhere.
 *
 * @deprecated
 *
 * @access private
 */
class CLearnHelper
{
	const GRAPH_STATUS_NOT_SET          = '1';		// status wasn't set yet (we must determine, is our tables updated to graph or not)
	const GRAPH_STATUS_LEGACY           = '2';
	const GRAPH_STATUS_UPDATED_TO_GRAPH = '3';
	const GRAPH_STATUS_UNDEFINED        = '4';

	const MODULE_ID     = 'learning';
	const OPTION_ID     = '~CLearnHelper::isUpdatedToGraph();';
	const DEFAULT_VALUE = self::GRAPH_STATUS_NOT_SET;		// be default
	const SITE_ID       = '';		// request shared options for all sites

	const ACCESS_READ   = 0x001;
	const ACCESS_MODIFY = 0x003;	// includes ACCESS_READ

	/**
	 * Don't relay on this function, it can be removed without any notifications.
	 * @deprecated
	 */
	public static function PatchLessonContentLinks($strContent, $contextCourseId = false)
	{
		static $arCourseLinksPatterns = array(
			'?COURSE_ID={SELF}"',
			'?COURSE_ID={SELF}\'',
			'?COURSE_ID={SELF}&',
			'&COURSE_ID={SELF}"',
			'&COURSE_ID={SELF}\'',
			'&COURSE_ID={SELF}&'
		);

		$argsCheck = is_string($strContent)
			&& ($contextCourseId !== false)
			&& ($contextCourseId > 0);

		if ( ! $argsCheck )
			return ($strContent);

		$arCourseResolvedLinks = str_replace(
			'{SELF}', 
			(string) ((int) $contextCourseId),
			$arCourseLinksPatterns
		);

		$rc = str_replace(
			$arCourseLinksPatterns, 
			$arCourseResolvedLinks, 
			$strContent
		);

		return ($rc);
	}


	/**
	 * @return void
	 */
	public static function FireEvent ($eventName, $eventParams)
	{
		$events = GetModuleEvents('learning', $eventName);
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($eventParams));
	}


	/**
	 * This function builds subquery (for oracle) or comma-separated
	 * list of lessons IDs (for mysql/mssql) for SQL WHERE clause, 
	 * which selects/contains all child lessons (only childs, 
	 * without parent lesson). This functions prevents cycling.
	 * 
	 * Warning: currently
	 * 
	 * @example
	 * on oracle SQLClauseForAllSubLessons(13) returns subquery:
	 * SELECT b_learn_lesson_edges.TARGET_NODE
	 * FROM b_learn_lesson_edges
	 * START WITH b_learn_lesson_edges.SOURCE_NODE=13
	 * CONNECT BY NOCYCLE PRIOR b_learn_lesson_edges.TARGET_NODE = b_learn_lesson_edges.SOURCE_NODE
	 * 
	 * on mysql/mssql SQLClauseForAllSubLessons(13) returns list of child lessons:
	 * 14, 16, 120, 875, 476
	 * 
	 * Any of this strings can be used in WHERE IN(...our string...) clause.
	 * 
	 * Complete example:
	 * <?php
	 * $parentLessonId = 447;
	 * $clauseChilds = CLearnHelper::SQLClauseForAllSubLessons($parentLessonId);
	 * $strSql = "
	 * SELECT * 
	 * FROM b_learn_lesson 
	 * WHERE ID IN ($clauseChilds) OR (ID = $parentLessonId)";
	 * // Selects list of all childs with parentLessonId included.
	 * $CDBresult = $DB->Query ($strSql);
	 * 
	 * @param int parent lesson id
	 * @return string for using in WHERE IN() clause: sql subquery or 
	 * comma-separated list of lesson's ids.
	 */
	public static function SQLClauseForAllSubLessons ($parentLessonId)
	{
		global $DBType;

		if ( ! (
			is_numeric($parentLessonId) 
			&& is_int($parentLessonId + 0) 
			) 
		)
		{
			throw new LearnException (
				'$parentLessonId must be strictly castable to integer', 
				LearnException::EXC_ERR_ALL_PARAMS);
		}

		if ($DBType === 'oracle')
		{
			// This subquery gets ids of all childs lesson for given $parentLessonId
			$rc = "
				SELECT b_learn_lesson_edges.TARGET_NODE
				FROM b_learn_lesson_edges
				START WITH b_learn_lesson_edges.SOURCE_NODE=" . ($parentLessonId + 0) . "
				CONNECT BY NOCYCLE PRIOR b_learn_lesson_edges.TARGET_NODE = b_learn_lesson_edges.SOURCE_NODE";
		
			return ($rc);
		}
		elseif (($DBType === 'mysql') || ($DBType === 'mssql'))
		{
			// MySQL & MSSQL supports "WHERE IN(...)" clause for more than 10 000 elements

			$oTree = CLearnLesson::GetTree($parentLessonId, array('EDGE_SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
			$arChildLessonsIds = $oTree->GetLessonsIdListInTree();	// parent lesson id isn't included

			// We need escape data for SQL
			$arChildLessonsIdsEscaped = array_map('intval', $arChildLessonsIds);

			$sqlChildLessonsIdsList = implode (', ', $arChildLessonsIdsEscaped);
			
			// No childs => nothing must be selected
			if (strlen($sqlChildLessonsIdsList) == 0)
				$sqlChildLessonsIdsList = 'NULL';		// NULL != any value. NULL != NULL too.

			return ($sqlChildLessonsIdsList);
		}
	}


	/**
	 * Linked from CAllCourse::MkOperationFilter($key);
	 * This code writed not by me, but I rely on it in good state
	 */
	public static function MkOperationFilter($key)
	{
		if(substr($key, 0, 1)=="=") //Identical
		{
			$key = substr($key, 1);
			$cOperationType = "I";
		}
		elseif(substr($key, 0, 2)=="!=") //not Identical
		{
			$key = substr($key, 2);
			$cOperationType = "NI";
		}
		elseif(substr($key, 0, 1)=="%") //substring
		{
			$key = substr($key, 1);
			$cOperationType = "S";
		}
		elseif(substr($key, 0, 2)=="!%") //not substring
		{
			$key = substr($key, 2);
			$cOperationType = "NS";
		}
		elseif(substr($key, 0, 1)=="?") //logical
		{
			$key = substr($key, 1);
			$cOperationType = "?";
		}
		elseif(substr($key, 0, 2)=="><") //between
		{
			$key = substr($key, 2);
			$cOperationType = "B";
		}
		elseif(substr($key, 0, 3)=="!><") //not between
		{
			$key = substr($key, 3);
			$cOperationType = "NB";
		}
		elseif(substr($key, 0, 2)==">=") //greater or equal
		{
			$key = substr($key, 2);
			$cOperationType = "GE";
		}
		elseif(substr($key, 0, 1)==">")  //greater
		{
			$key = substr($key, 1);
			$cOperationType = "G";
		}
		elseif(substr($key, 0, 2)=="<=")  //less or equal
		{
			$key = substr($key, 2);
			$cOperationType = "LE";
		}
		elseif(substr($key, 0, 1)=="<")  //less
		{
			$key = substr($key, 1);
			$cOperationType = "L";
		}
		elseif(substr($key, 0, 1)=="!") // not field LIKE val
		{
			$key = substr($key, 1);
			$cOperationType = "N";
		}
		else
			$cOperationType = "E";	// field LIKE val

		return Array("FIELD"=>$key, "OPERATION"=>$cOperationType);
	}

	/**
	 * This code writed not by me, but I rely on it in good state
	 */
	public static function FilterCreate($fname, $vals, $type, &$bFullJoin, $cOperationType=false, $bSkipEmpty = true)
	{
		global $DB;
		if(!is_array($vals))
			$vals=Array($vals);

		if(count($vals)<1)
			return "";

		if(is_bool($cOperationType))
		{
			if($cOperationType===true)
				$cOperationType = "N";
			else
				$cOperationType = "E";
		}

		if($cOperationType=="G")
			$strOperation = ">";
		elseif($cOperationType=="GE")
			$strOperation = ">=";
		elseif($cOperationType=="LE")
			$strOperation = "<=";
		elseif($cOperationType=="L")
			$strOperation = "<";
		else
			$strOperation = "=";

		$bFullJoin = false;
		$bWasLeftJoin = false;

		$res = Array();
		for($i=0; $i<count($vals); $i++)
		{
			$val = $vals[$i];

			if(!$bSkipEmpty || strlen($val)>0 || (is_bool($val) && $val===false))
			{
				switch ($type)
				{
				case "string_equal":
					if(strlen($val)<=0)
						$res[] =
						($cOperationType=="N"?"NOT":"").
						"(".
						$fname." IS NULL OR ".$DB->Length($fname).
						"<=0)";
					else
						$res[] =
						"(".
						($cOperationType=="N"?" ".$fname." IS NULL OR NOT (":"").
						CCourse::_Upper($fname).$strOperation.CCourse::_Upper("'".$DB->ForSql($val)."'").
						($cOperationType=="N"?")":"").
						")";
					break;
				case "string":
					if($cOperationType=="?")
					{
						if(strlen($val)>0)
							$res[] = GetFilterQuery($fname, $val, "Y",array(),"N");
					}
					elseif(strlen($val)<=0)
					{
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL OR ".$DB->Length($fname)."<=0)";
					}
					else
					{
						if($strOperation=="=")
							$res[] =
							"(".
							($cOperationType=="N"?" ".$fname." IS NULL OR NOT (":"").
							(strtoupper($DB->type)=="ORACLE"?CCourse::_Upper($fname)." LIKE ".CCourse::_Upper("'".$DB->ForSqlLike($val)."'")." ESCAPE '\\'" : $fname." ".($strOperation=="="?"LIKE":$strOperation)." '".$DB->ForSqlLike($val)."'").
							($cOperationType=="N"?")":"").
							")";
						else
							$res[] =
							"(".
							($cOperationType=="N"?" ".$fname." IS NULL OR NOT (":"").
							(strtoupper($DB->type)=="ORACLE"?CCourse::_Upper($fname).
							" ".$strOperation." ".CCourse::_Upper("'".$DB->ForSql($val)."'")." " : $fname." ".$strOperation." '".$DB->ForSql($val)."'").
							($cOperationType=="N"?")":"").
							")";
					}
					break;
				case "date":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] =
						"(".
						($cOperationType=="N"?" ".$fname." IS NULL OR NOT (":"").
						$fname." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").
						($cOperationType=="N"?")":"").
						")";
					break;
				case "number":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] =
						"(".
						($cOperationType=="N"?" ".$fname." IS NULL OR NOT (":"").
						$fname." ".$strOperation." '".DoubleVal($val).
						($cOperationType=="N"?"')":"'").
						")";
					break;
				/*
				case "number_above":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." '".$DB->ForSql($val)."')";
					break;
				*/
				}

				// INNER JOIN in this case
				if(strlen($val)>0 && $cOperationType!="N")
					$bFullJoin = true;
				else
					$bWasLeftJoin = true;
			}
		}

		$strResult = "";
		for($i=0; $i<count($res); $i++)
		{
			if($i>0)
				$strResult .= ($cOperationType=="N"?" AND ":" OR ");
			$strResult .= $res[$i];
		}

		if (count($res) > 1)
			$strResult = "(".$strResult.")";


		if($bFullJoin && $bWasLeftJoin && $cOperationType!="N")
			$bFullJoin = false;

		return $strResult;
	}

	/**
	 * @return boolean true, if data tables updated to graph (new native mode)
	 *                 otherwise returns false (it means, we must work in legacy mode)
	 */
	public static function isUpdatedToGraph()
	{
		if (self::getUpdatedToGraphStatus() === self::GRAPH_STATUS_UPDATED_TO_GRAPH)
			return true;
		else
			return false;
	}

	/**
	 * @param string $status one of this values:
	 *    self::GRAPH_STATUS_LEGACY - if not updated to grpah yet (legacy mode)
	 *    self::GRAPH_STATUS_UPDATED_TO_GRAPH - if updated to graph,
	 *    self::GRAPH_STATUS_UNDEFINED - if status is undefined (update in progress or interrupted)
	 * @return boolean true if saved successfully, false otherwise.
	 */
	public static function setUpdatedToGraphStatus($status)
	{
		$description = '';

		$isSaved = COption::SetOptionString(self::MODULE_ID, self::OPTION_ID,
			$status, $description, self::SITE_ID);

		return ($isSaved);
	}

	/**
	 * @return string $status one of this values:
	 *    self::GRAPH_STATUS_LEGACY - if not updated to graph (it means, we must work in legacy mode)
	 *    self::GRAPH_STATUS_UPDATED_TO_GRAPH - if update to graph,
	 *    self::GRAPH_STATUS_UNDEFINED - if status is undefined (update in progress or interrupted)
	 */
	public static function getUpdatedToGraphStatus()
	{

		$rc = COption::GetOptionString(self::MODULE_ID, self::OPTION_ID, self::DEFAULT_VALUE, self::SITE_ID);

		// status wasn't set yet (we must determine, is our tables updated to graph or not)
		if ($rc === self::DEFAULT_VALUE)
		{
			// Set determined mode in global options
			self::setUpdatedToGraphStatus(self::GRAPH_STATUS_LEGACY);
		}

		$allowed_statuses = array (
			self::GRAPH_STATUS_LEGACY,
			self::GRAPH_STATUS_UPDATED_TO_GRAPH,
			self::GRAPH_STATUS_UNDEFINED
			);

		if ( ! in_array($rc, $allowed_statuses, true) )
		{
			AddMessage2Log('Invalid COption ~CLearnHelper::isUpdatedToGraph();: `'
				. $rc . '`;', 'learning');

			$rc = self::GRAPH_STATUS_UNDEFINED;
		}

		return ($rc);
	}


	public static function IsBaseFilenameSafe($filename)
	{

		$isUnSafe = IsFileUnsafe($filename) 
			|| HasScriptExtension($filename)
			|| ( ! (preg_match("#^[^\\\/:*?\"\'~%<>|]+$#is", $filename) > 0) );

		return ( ! $isUnSafe );
	}


	public static function CopyDirFiles($path_from, $path_to, $ReWrite = True, $Recursive = False)
	{
		if (strpos($path_to."/", $path_from."/")===0 || realpath($path_to) === realpath($path_from))
			return false;

		if (is_dir($path_from))
		{
			CheckDirPath($path_to."/");
		}
		elseif(is_file($path_from))
		{
			$p = bxstrrpos($path_to, "/");
			$path_to_dir = substr($path_to, 0, $p);
			CheckDirPath($path_to_dir."/");

			if (file_exists($path_to) && !$ReWrite)
				return False;

			@copy($path_from, $path_to);
			if(is_file($path_to))
				@chmod($path_to, BX_FILE_PERMISSIONS);

			return True;
		}
		else
		{
			return True;
		}

		if ($handle = @opendir($path_from))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..")
					continue;

				// skip files with non-safe names
				if ( ! CLearnHelper::IsBaseFilenameSafe($file) )
					continue;

				if (is_dir($path_from."/".$file) && $Recursive)
				{
					self::CopyDirFiles($path_from."/".$file, $path_to."/".$file, $ReWrite, $Recursive);
				}
				elseif (is_file($path_from."/".$file))
				{
					if (file_exists($path_to."/".$file) && !$ReWrite)
						continue;

					@copy($path_from."/".$file, $path_to."/".$file);
					@chmod($path_to."/".$file, BX_FILE_PERMISSIONS);
				}
			}
			@closedir($handle);

			return true;
		}

		return false;
	}
}