<?php
	class CLearnInstall201203ConvertDBTimeOut extends Exception
	{
	}


	class CLearnInstall201203ConvertDBException extends Exception
	{
	}


	class CLearnInstall201203ConvertDB
	{
		const MODULE_ID = 'learning';
		const OPTION_ID = '~LearnInstall201203ConvertDB::_IsAlreadyConverted';		// don't change this constant, NEVER!
		const STATUS_INSTALL_COMPLETE    = '1';
		const STATUS_INSTALL_NEVER_START = '2';
		const STATUS_INSTALL_INCOMPLETE  = '3';

		const JOURNAL_STATUS_UNPROCESSED          = -1;
		const JOURNAL_STATUS_COURSE_LINKED        = 1;
		const JOURNAL_STATUS_CHAPTER_COPIED       = 2;
		const JOURNAL_STATUS_LESSON_EDGES_CREATED = 3;

		public static $items_processed = 0;


		public static function run()
		{
			global $DB;
			$msg = $step = false;
			$errorMessage = '';

			// If data tables are not installed - nothing to do
			if ( ! $DB->TableExists('b_learn_lesson') )
				return ($errorMessage);

			try
			{
				if ( ! self::IsNewRightsModelInitialized($step, $msg) )
				{
					self::InitializeNewRightsModel();
					$step = false;
					if ( ! self::IsNewRightsModelInitialized($step, $msg) )
					{
						$errorMessage .= 'FAILED on step ' . $step . '; msg = ' . $msg . '.';
						return ($errorMessage);			// FATAL
					}
				}

				self::StartTransaction();
				self::ReCreateTriggersForMSSQL();
				self::Commit();

				self::StartTransaction();
				self::ConvertDB($errorMessage);
				self::Commit();
			}
			catch (CLearnInstall201203ConvertDBException $e)
			{
				self::Rollback();
				$errorMessage .= "Cautch exception at line: " . $e->getLine()
					. "; with message: " . $e->getMessage();
			}
			catch (CLearnInstall201203ConvertDBTimeOut $e)
			{
				self::Commit();
				/*
				$errorMessage .= "Timeout occured at line: " . $e->getLine() 
					. ". Convertation is incomplete and should be executed another time.";
				*/
				$errorMessage .= '';
			}
			catch (Exception $e)
			{
				self::Rollback();
				$errorMessage .= "Cautch general exception at line: " . $e->getLine() 
					. "; with message: " . $e->getMessage();
			}

			return ($errorMessage);
		}


		protected static function StartTransaction()
		{
			global $DB, $DBType;

			$dbtype = strtolower($DBType);

			if ($dbtype == 'mssql')
				return;

			$DB->StartTransaction();
		}


		protected static function Rollback()
		{
			global $DB, $DBType;

			$dbtype = strtolower($DBType);

			if ($dbtype == 'mssql')
				return;

			$DB->Rollback();
		}


		protected static function Commit()
		{
			global $DB, $DBType;

			$dbtype = strtolower($DBType);

			if ($dbtype == 'mssql')
				return;

			$DB->Commit();
		}


		protected static function ReCreateTriggersForMSSQL()
		{
			global $DB, $DBType;

			$dbtype = strtolower($DBType);

			if ($dbtype != 'mssql')
				return;

			$arTriggers = array(
				'B_LEARN_COURSE_UPDATE'        => 'B_LEARN_COURSE',
				'B_LEARN_CHAPTER_UPDATE'       => 'B_LEARN_CHAPTER',
				'B_LEARN_LESSON_UPDATE'        => 'B_LEARN_LESSON',
				'B_LEARN_QUESTION_UPDATE'      => 'B_LEARN_QUESTION',
				'B_LEARN_TEST_UPDATE'          => 'B_LEARN_TEST',
				'B_LEARN_CERTIFICATION_UPDATE' => 'B_LEARN_CERTIFICATION'
				);

			foreach ($arTriggers as $triggerName => $tableName)
			{
				$DB->Query('DROP TRIGGER ' . $triggerName, true);

				$DB->Query('
					CREATE TRIGGER ' . $triggerName . ' ON ' . $tableName . ' FOR UPDATE AS
					BEGIN
						SET NOCOUNT ON;
						IF (NOT UPDATE(TIMESTAMP_X))
						BEGIN
							UPDATE ' . $tableName . ' SET
								TIMESTAMP_X = GETDATE()
							FROM
								' . $tableName . ' U,
								INSERTED I,
								DELETED D
							WHERE
								U.ID = I.ID 
								AND U.ID = D.ID;
						END
					END
					', true);
			}
		}


		protected static function _RightsModelGetTasksWithOperations()
		{
			$arTasksOperations = array(
				'learning_lesson_access_denied'            => array(),
				'learning_lesson_access_read'              => array(
					'lesson_read'
					),
				'learning_lesson_access_manage_basic'      => array(
					'lesson_read', 
					'lesson_create',
					'lesson_write', 
					'lesson_remove'
					),
				'learning_lesson_access_linkage_as_child'  => array(
					'lesson_read', 
					'lesson_link_to_parents', 
					'lesson_unlink_from_parents'
					),
				'learning_lesson_access_linkage_as_parent' => array(
					'lesson_read', 
					'lesson_link_descendants',
					'lesson_unlink_descendants'
					),
				'learning_lesson_access_linkage_any'       => array(
					'lesson_read', 
					'lesson_link_to_parents', 
					'lesson_unlink_from_parents',
					'lesson_link_descendants',
					'lesson_unlink_descendants'
					),
				'learning_lesson_access_manage_as_child'   => array(
					'lesson_read', 
					'lesson_create',
					'lesson_write', 
					'lesson_remove',
					'lesson_link_to_parents', 
					'lesson_unlink_from_parents'
					),
				'learning_lesson_access_manage_as_parent'  => array(
					'lesson_read', 
					'lesson_create',
					'lesson_write', 
					'lesson_remove',
					'lesson_link_descendants',
					'lesson_unlink_descendants'
					),
				'learning_lesson_access_manage_dual'       => array(
					'lesson_read', 
					'lesson_create',
					'lesson_write', 
					'lesson_remove',
					'lesson_link_to_parents', 
					'lesson_unlink_from_parents',
					'lesson_link_descendants',
					'lesson_unlink_descendants'
					),
				'learning_lesson_access_manage_full'       => array(
					'lesson_read', 
					'lesson_create',
					'lesson_write', 
					'lesson_remove',
					'lesson_link_to_parents', 
					'lesson_unlink_from_parents',
					'lesson_link_descendants',
					'lesson_unlink_descendants',
					'lesson_manage_rights'
					)			
				);

			return ($arTasksOperations);
		}


		protected static function _RightsModelGetAllOperations()
		{
			$arAllOperations = array(
				'lesson_read',
				'lesson_create',
				'lesson_write',
				'lesson_remove',
				'lesson_link_to_parents',
				'lesson_unlink_from_parents',
				'lesson_link_descendants',
				'lesson_unlink_descendants',
				'lesson_manage_rights'
				);

			return ($arAllOperations);
		}


		/**
		 * @return array of operations with IDs
		 */
		protected static function _CheckOperationsInDB()
		{
			global $DB;

			$arAllOperations = self::_RightsModelGetAllOperations();

			$rc = $DB->Query ("SELECT ID, NAME, BINDING FROM b_operation WHERE MODULE_ID = 'learning'", true);

			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			$arOperationsInDB = array();
			while ($arOperation = $rc->Fetch())
			{
				if (substr($arOperation['NAME'], 0, 7) === 'lesson_')
					$binding = 'lesson';
				else
					$binding = 'module';

				if ($arOperation['BINDING'] !== $binding)
					throw new Exception();

				$arOperationsInDB[$arOperation['NAME']] = $arOperation['ID'];
			}

			if (count($arOperationsInDB) !== count($arAllOperations))
				throw new Exception();	// not all operations in DB

			foreach ($arAllOperations as $operationName)
			{
				if ( ! isset($arOperationsInDB[$operationName]) )
					throw new Exception();	// not all operations in DB
			}

			return ($arOperationsInDB);
		}


		protected static function _CheckTasksInDB($arTasksOperations)
		{
			global $DB;

			$rc = $DB->Query ("SELECT ID, NAME, BINDING FROM b_task WHERE MODULE_ID = 'learning'", true);

			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			$arTasksInDB = array();
			while ($arTask = $rc->Fetch())
			{
				if (substr($arTask['NAME'], 0, 16) === 'learning_lesson_')
					$binding = 'lesson';
				else
					$binding = 'module';

				if ($arTask['BINDING'] !== $binding)
					throw new Exception();

				$arTasksInDB[$arTask['NAME']] = $arTask['ID'];
			}

			if (count($arTasksInDB) !== count($arTasksOperations))
			{
				throw new Exception('count($arTasksInDB) = ' 
					. count($arTasksInDB) 
					. '; count($arTasksOperations) = ' 
					. count($arTasksOperations));	// not all tasks in DB
			}

			foreach (array_keys($arTasksOperations) as $taskName)
			{
				if ( ! isset($arTasksInDB[$taskName]) )
					throw new Exception();	// not all tasks in DB
			}

			return ($arTasksInDB);
		}


		protected static function _CheckTasksOperationsRelations($arOperationsInDB, $arTasksInDB, $arTasksOperations)
		{
			global $DB;

			foreach ($arTasksInDB as $taskName => $taskId)
			{
				if ( ! isset($arTasksOperations[$taskName]) )
					throw new Exception();

				$arCurTaskOperations = $arTasksOperations[$taskName];
				$arCurTaskOperationsIDs = array();
				foreach ($arCurTaskOperations as $operationName)
				{
					if ( ! isset($arOperationsInDB[$operationName]) )
						throw new Exception();

					$operationId = $arOperationsInDB[$operationName];

					$arCurTaskOperationsIDs[$operationId] = 'operation';
				}

				// Get list of task's operations reltaions
				$rc = $DB->Query ("SELECT OPERATION_ID FROM b_task_operation WHERE TASK_ID = " . ($taskId + 0), true);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');



				while ($arRelation = $rc->Fetch())
				{
					if ( ! isset($arCurTaskOperationsIDs[$arRelation['OPERATION_ID']]) )
						throw new Exception();

					unset ($arCurTaskOperationsIDs[$arRelation['OPERATION_ID']]);
				}

				if (count($arCurTaskOperationsIDs) > 0)
					throw new Exception();
			}
		}


		protected static function IsNewRightsModelInitialized(&$step, &$msg)
		{
			try
			{
				$arTasksOperations = self::_RightsModelGetTasksWithOperations();
				// Compare list of operations in DB
				$arOperationsInDB = self::_CheckOperationsInDB();

				// Compare list of tasks in DB
				$arTasksInDB = self::_CheckTasksInDB($arTasksOperations);

				// Compare relations between tasks and operations
				self::_CheckTasksOperationsRelations($arOperationsInDB, $arTasksInDB, $arTasksOperations);

				return (true);	// new rights model correctly initialized
			}
			catch (Exception $e)
			{
				$step = $e->getLine();
				$msg = $e->getMessage();
				return (false);	// new rights model not initialized
			}
		}


		protected static function _RightsModelCreateOperations()
		{
			global $DB;

			$arAllOperations = self::_RightsModelGetAllOperations();

			$arOperationsInDB = array();

			foreach ($arAllOperations as $operationName)
			{
				if (substr($operationName, 0, 7) === 'lesson_')
					$binding = 'lesson';
				else
					$binding = 'module';

				$arFields = array(
					'NAME'        => "'" . $DB->ForSql($operationName) . "'",
					'MODULE_ID'   => "'learning'",
					'DESCRIPTION' => 'NULL',
					'BINDING'     => "'" . $binding . "'"
					);

				$id = $DB->Insert(
						'b_operation',
						$arFields,
						"",		// $error_position
						false,	// $debug
						"",		// $exist_id
						false	// don't ignore errors, due to the bug in Database::Insert (it don't checks Query return status)
					);

				if ($id === false)
					throw new Exception();

				$arOperationsInDB[$operationName] = $id;
			}

			return ($arOperationsInDB);
		}


		protected static function _RightsModelCreateTasksAndRelation($arOperationsInDB)
		{
			global $DB, $APPLICATION;

			$arOld2NewRightsMatrix = array(
				'D' => 'learning_lesson_access_read',
				'W' => 'learning_lesson_access_manage_full'
				);

			$module_id = 'learning';

			$arDefaultRights = array (
				'learning_lesson_access_read'        => array(),
				'learning_lesson_access_manage_dual' => array('CR'),	// Author
				'learning_lesson_access_manage_full' => array('G1')		// Admins
				);

			$rc = CGroup::GetList($v1 = 'sort', $v2 = 'asc', array('ACTIVE' => 'Y'));
			while($zr = $rc->Fetch())
			{
				$group_id = $zr['ID'];
				$oldSymbol = $APPLICATION->GetGroupRight(
					$module_id, 
					array($group_id), 
					$use_default_level = "N", 
					$max_right_for_super_admin = "N", 
					$site_id = false);

				if (isset($arOld2NewRightsMatrix[$oldSymbol]))
				{
					$newSymbol = $arOld2NewRightsMatrix[$oldSymbol];
					if (isset($arDefaultRights[$newSymbol]))
						$arDefaultRights[$newSymbol][] = 'G' . $group_id;
				}
			}

			$arTasksOperations = self::_RightsModelGetTasksWithOperations();

			foreach ($arTasksOperations as $taskName => $arOperationsForTask)
			{
				if (substr($taskName, 0, 16) === 'learning_lesson_')
					$binding = 'lesson';
				else
					$binding = 'module';

				$arFields = array(
					'NAME'        => "'" . $DB->ForSql($taskName) . "'",
					'LETTER'      => 'NULL',
					'MODULE_ID'   => "'learning'",
					'SYS'         => "'Y'",
					'DESCRIPTION' => 'NULL',
					'BINDING'     => "'" . $binding . "'"
					);

				$taskId = $DB->Insert(
					'b_task',
					$arFields,
					"",		// $error_position
					false,	// $debug
					"",		// $exist_id
					false	// don't ignore errors, due to the bug in Database::Insert (it don't checks Query return status)
					);

				if ($taskId === false)
					throw new Exception();

				// Create relation for every operation per task
				foreach ($arOperationsForTask as $operationName)
				{
					if ( ! isset($arOperationsInDB[$operationName]) )
						throw new Exception();

					$operationId = (int) $arOperationsInDB[$operationName];

					$rc = $DB->Query(
						"INSERT INTO b_task_operation (TASK_ID, OPERATION_ID) 
						VALUES (" . (int) $taskId . ", " . (int) $operationId . ")", true);

					if ($rc === false)
						throw new Exception();
				}

				// Add default rights for this task, if it exists
				if ( array_key_exists($taskName, $arDefaultRights) )
				{
					$arDefaultRights[$taskName] = array_unique($arDefaultRights[$taskName]);
					foreach ($arDefaultRights[$taskName] as $subject_id)
					{
						$DB->Query("DELETE FROM b_learn_rights_all 
							WHERE SUBJECT_ID = '" . $DB->ForSQL($subject_id) . "'");

						$rc = $DB->Query (
							"INSERT INTO b_learn_rights_all (SUBJECT_ID, TASK_ID) 
							VALUES ('" . $DB->ForSQL($subject_id) . "', " . (int) $taskId . ")",
							true);

						if ($rc === false)
							throw new Exception();
					}
				}
			}
		}


		protected static function _RightsModelPurge()
		{
			global $DB;

			$arQueries = array(
				"DELETE FROM b_task_operation 
				WHERE TASK_ID IN (SELECT ID FROM b_task WHERE MODULE_ID = 'learning')
					OR OPERATION_ID IN (SELECT ID FROM b_operation WHERE MODULE_ID = 'learning')",

				"DELETE FROM b_operation
				WHERE MODULE_ID = 'learning'",

				"DELETE FROM b_task
				WHERE MODULE_ID = 'learning'"
				);

			foreach ($arQueries as $key => $query)
			{
				$rc = $DB->Query($query, true);		// ignore_errors = true
				if ($rc === false)
					throw new Exception ('EA_SQLERROR in query #' . $key);
			}
		}


		protected static function InitializeNewRightsModel()
		{
			global $DB;

			// Clean up learning module operations and tasks (if exists)
			self::_RightsModelPurge();

			if ( ! $DB->TableExists('b_learn_rights_all') )
				self::_CreateTblRightsAll();

			$arOperationsInDB = self::_RightsModelCreateOperations();

			self::_RightsModelCreateTasksAndRelation($arOperationsInDB);
		}


		protected static function _CreateTblRightsAll ()
		{
			global $DB, $DBType;

			if ( ! $DB->TableExists('b_learn_rights_all') )
			{
				$dbtype = strtolower($DBType);

				// Prepare sql code for adding fields
				if ($dbtype === 'mysql')
				{
					$sql_tbl_b_learn_rights_all = "
						CREATE TABLE b_learn_rights_all (
						SUBJECT_ID VARCHAR( 100 ) NOT NULL ,
						TASK_ID INT NOT NULL ,
						PRIMARY KEY ( SUBJECT_ID )
						)";
				}
				elseif ($dbtype === 'mssql')
				{
					$sql_tbl_b_learn_rights_all = "
					CREATE TABLE B_LEARN_RIGHTS_ALL
					(
						SUBJECT_ID VARCHAR(100) NOT NULL,
						TASK_ID INT NOT NULL,
						CONSTRAINT PK_B_LEARN_RIGHTS_ALL PRIMARY KEY( SUBJECT_ID)
					)";
				}
				elseif ($dbtype === 'oracle')
				{
					$sql_tbl_b_learn_rights_all = "
					CREATE TABLE b_learn_rights_all
					(
						SUBJECT_ID VARCHAR2(100 CHAR) NOT NULL,
						TASK_ID NUMBER(11) NOT NULL,
						PRIMARY KEY(SUBJECT_ID)
					)";
				}
				else
				{
					throw new CLearnInstall201203ConvertDBException('SQL code not ready for: ' . $DBType . ' in line #' . __LINE__);
				}

				$rc = $DB->Query($sql_tbl_b_learn_rights_all);
				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException(__LINE__ . '/tbl: sql_tbl_b_learn_rights_all');
			}
		}


		/**
		 * @return int items processed
		 */
		public static function ConvertDB(&$errorMessage)
		{
			global $DB;

			self::$items_processed = 0;

			// Check, was DB already converted?
			if (self::_IsAlreadyConverted() === true)
				return (true);

			// Mark that db convert process started
			$rc = COption::SetOptionString(self::MODULE_ID, self::OPTION_ID, self::STATUS_INSTALL_INCOMPLETE);
			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('SetOptionString() failed!');

			// Create fields `CODE`, `WAS_CHAPTER_ID` in `b_learn_lesson` (if they doesn't exists yet) 
			// and `JOURNAL_STATUS` in `b_learn_chapter`
			// and `LINKED_LESSON_ID` in `b_learn_course`
			self::_CreateFieldsInTbls();

			/**
			 * Our plan:
			 * 1) Create lesson for every course and links them 
			 *    (`b_learn_course`.`LINKED_LESSON_ID` = id_of_new_lesson).
			 *    Than update `b_learn_course`.`JOURNAL_STATUS` to self::JOURNAL_STATUS_COURSE_LINKED
			 * 
			 * 2) Copy all chapters to lessons table and than update 
			 *    `b_learn_chapter`.`JOURNAL_STATUS` = self::JOURNAL_STATUS_CHAPTER_COPIED
			 * 
			 * 3) Build all edges between lessons. Firstly, build edges for simple lessons 
			 *    (not that was a chapter or course), and than for lessons-chapters.
			 */

			// Process courses
			self::_processCourses();

			// Process chapters
			self::$items_processed += self::_processChapters();

			// Creates table for edges, if it doesn't exists yet.
			self::_CreateEdgesTbl();

			// Build edges for lessons and chapters (`WAS_COURSE_ID` === NULL)
			self::_buildEdges($errorMessage);

			// Convert old permissions to new
			self::ConvertPermissions();

			// Add new path: COURSE_ID=#COURSE_ID#
			// ?LESSON_PATH=#LESSON_PATH#
			self::AddPath();

			// Remove b_learn_course_permission, if exists
			self::_RemoveOrphanedTables();

			// Mark that db convert process complete
			$rc = COption::SetOptionString(self::MODULE_ID, self::OPTION_ID, self::STATUS_INSTALL_COMPLETE);
			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('SetOptionString() failed!');
		}


		protected static function ConvertPermissions()
		{
			global $DB;

			$arTaskIdByOldSymbol = array();
			$arTasks = array(
				'R' => 'learning_lesson_access_read', 
				'W' => 'learning_lesson_access_manage_basic', 
				'X' => 'learning_lesson_access_manage_full');

			foreach ($arTasks as $oldSymbol => $taskName)
			{
				$rc = $DB->Query (
					"SELECT ID 
					FROM b_task 
					WHERE NAME = '" . $taskName . "'",
					true);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				$row = $rc->Fetch();

				if ( ! isset($row['ID']) )
					throw new CLearnInstall201203ConvertDBException('EA_LOGIC');

				$arTaskIdByOldSymbol[$oldSymbol] = (int) $row['ID'];
			}



			$sql = 
			"SELECT TLL.ID, TLCP.PERMISSION, TLCP.USER_GROUP_ID 
			FROM b_learn_lesson TLL
			INNER JOIN b_learn_course_permission TLCP
				ON TLL.COURSE_ID = TLCP.COURSE_ID
			WHERE TLL.COURSE_ID > 0
			AND TLCP.PERMISSION != 'D'

			UNION 

			SELECT TLL.ID, TLCP.PERMISSION, TLCP.USER_GROUP_ID
			FROM b_learn_lesson TLL
			INNER JOIN b_learn_course_permission TLCP
				ON TLL.WAS_COURSE_ID = TLCP.COURSE_ID
			WHERE TLL.COURSE_ID = 0
			AND TLL.WAS_COURSE_ID > 0
			AND TLCP.PERMISSION != 'D'
			";

			$res = $DB->Query($sql, true);

			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			while ($row = $res->Fetch())
			{
				$lessonId      = $row['ID'];
				$permission    = $row['PERMISSION'];
				$user_group_id = $row['USER_GROUP_ID'];

				$group = 'G' . $user_group_id;

				// Determine task id
				if ( ! in_array($permission, array('R', 'W', 'X'), true) )
					continue;		// skip elements with D

				$task_id = $arTaskIdByOldSymbol[$permission];

				$rc = $DB->Query (
					"DELETE FROM b_learn_rights 
					WHERE LESSON_ID = " . (int) $lessonId . "
						AND SUBJECT_ID = '" . $DB->ForSql($group) . "'",
					true);
				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');
					

				$rc = $DB->Query (
					"INSERT INTO b_learn_rights (LESSON_ID, SUBJECT_ID, TASK_ID) 
					VALUES (" . (int) $lessonId . ", '" . $DB->ForSql($group) . "', '" . $task_id . "')", true);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');
			}

		}


		protected static function AddPath()
		{
			global $DB;

			$res = $DB->Query(
				"SELECT DISTINCT SITE_ID 
				FROM b_learn_site_path 
				WHERE 1=1", true);

			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			$arSitesIds = array();
			while ($row = $res->Fetch())
				$arSitesIds[] = $row['SITE_ID'];

			foreach ($arSitesIds as $k => $siteId)
			{
				$res = $DB->Query (
					"DELETE FROM b_learn_site_path 
					WHERE SITE_ID = '" . $DB->ForSql($siteId) . "' 
						AND TYPE = 'U'", true);

				if ($res === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				$res = $DB->Query (
					"SELECT TSP.PATH
					FROM b_learn_site_path TSP
					WHERE TYPE = 'C' AND SITE_ID = '" . $DB->ForSql($siteId) . "'", 
					true);
				
				if ($res === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				$row = $res->Fetch();
				if (isset($row['PATH']))
				{
					$path = str_replace('COURSE_ID=#COURSE_ID#', 'LESSON_PATH=#LESSON_PATH#', $row['PATH']);
					$path = str_replace('&INDEX=Y', '', $path);
				}
				else
					$path = '/services/learning/course.php?LESSON_PATH=#LESSON_PATH#';

				$DB->Insert(
					'b_learn_site_path',
					array(
						'SITE_ID' => "'" . $DB->ForSql($siteId) . "'",
						'PATH'    => "'" . $DB->ForSql($path) . "'",
						'TYPE'    => "'U'"
						)
					);
			}
		}

		/**
		 * @return int items processed
		 */
		public static function _buildEdges(&$errorMessage)
		{
			global $DB;

			// For lessons, on which b_learn_course.LINKED_LESSON_ID linked we don't need edges, because they are tops nodes now.
			$res = $DB->Query (
				"SELECT ID, COURSE_ID, CHAPTER_ID, ACTIVE, SORT, WAS_CHAPTER_ID, 
					WAS_PARENT_CHAPTER_ID, WAS_PARENT_COURSE_ID
				FROM b_learn_lesson 
				WHERE JOURNAL_STATUS != " . self::JOURNAL_STATUS_LESSON_EDGES_CREATED . "
					AND WAS_COURSE_ID IS NULL", 
				$ignore_errors = true);
			
			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			while ($arLesson = $res->Fetch())
			{
				$sort = $arLesson['SORT'];

				$childNodeId = $arLesson['ID'];

				// Determine, who is immediate parent of lesson - chapter or course
				if ($arLesson['WAS_CHAPTER_ID'] === NULL)	// current node wasn't a chapter
				{
					if ($arLesson['CHAPTER_ID'] !== NULL)
					{
						// intermediate parent is chapter, get it id in new data model
						$parentNodeId = self::_GetChapterIdInNewDataModel ($arLesson['CHAPTER_ID']);
					}
					elseif ($arLesson['COURSE_ID'] !== NULL)
					{
						// intermediate parent is course, get it id in new data model
						$parentNodeId = self::_GetCourseIdInNewDataModel ($arLesson['COURSE_ID']);
					}
					else
					{
						// No parent? It's very strange for old data model, but it's OK for new data model.
						// So, nothing to do here.
						$parentNodeId = NULL;
					}
				}
				else	// current node was a chapter
				{
					if ($arLesson['WAS_PARENT_CHAPTER_ID'] !== NULL)
					{
						// intermediate parent is chapter, get it id in new data model
						$parentNodeId = self::_GetChapterIdInNewDataModel ($arLesson['WAS_PARENT_CHAPTER_ID']);
					}
					elseif ($arLesson['WAS_PARENT_COURSE_ID'] !== NULL)
					{
						// intermediate parent is course, get it id in new data model
						$parentNodeId = self::_GetCourseIdInNewDataModel ($arLesson['WAS_PARENT_COURSE_ID']);
					}
					else
					{
						// No parent? It's very strange for old data model, but it's OK for new data model.
						// So, nothing to do here.
						$parentNodeId = NULL;
					}
				}			

				if ($parentNodeId === NULL)
					; //	nothing to do
				elseif ($parentNodeId === -1)
				{
					/**
					 * An error occured (chapter or course not found in new data model)
					 * In old data model, this shouldn't be. 
					 * So, it's may be:
					 * 1) our bug during importing chapter/courses to lessons
					 * 2) data inconsistency in target database
					 * 3) third-party UPDATE/INSERT/DELETE was processed on lessons/chapters/courses during convertation
					 * In any case, this situation is not good (except, probabaly, case #2).
					 * But, best we can do is continue convertation.
					 * Because, if we restart convertation, it can be infinitly looped.
					 * 
					 * So, nothing to do here.
					 */
					$errorMessage .= "Problem occured with CHAPTER_ID = " . $arLesson['CHAPTER_ID'] 
					. "; COURSE_ID = " . $arLesson['COURSE_ID'] . "<br>\n";
				}
				elseif ($parentNodeId <= 0)
				{
					// This is invalid value
					throw new CLearnInstall201203ConvertDBException('EA_OTHER: invalid parentNodeId for lesson_id = ' . $arLesson['ID']);
				}
				else
				{
					// All is OK, so create edge for nodes
					self::_CreateEdgeForNodes ($parentNodeId, $childNodeId, $sort);
				}

				// Mark lesson as processed
				self::_MarkLessonAsProcessed ($arLesson['ID']);

				++self::$items_processed;

				// This function throws exception CLearnInstall201203ConvertDBTimeOut, if it's low time left.
				self::avoidTimeout();
			}
		}

		public static function _MarkLessonAsProcessed ($lessonId)
		{
			global $DB;

			// Mark this course as processed
			$rc = $DB->Update('b_learn_lesson', 
				array ('JOURNAL_STATUS' => self::JOURNAL_STATUS_LESSON_EDGES_CREATED), 
				"WHERE ID = '" . ($lessonId + 0) . "'",
					$error_position = "",
					$debug = false,
					$ignore_errors = true
				);

			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

		}

		public static function _CreateEdgeForNodes ($parentNodeId, $childNodeId, $sort)
		{
			global $DB;

			$parentNodeId += 0;
			$childNodeId  += 0;

			// Firstly, remove such edge, if exists
			$rc = $DB->Query (
				"DELETE FROM b_learn_lesson_edges 
				WHERE SOURCE_NODE = '" . $parentNodeId . "'
					AND TARGET_NODE = '" . $childNodeId . "'", 
				$ignore_errors = true);

			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			// Now, create edge
			$rc = $DB->Query (
				"INSERT INTO b_learn_lesson_edges (SOURCE_NODE, TARGET_NODE, SORT)
				VALUES ('" . $parentNodeId . "', '" . $childNodeId . "', '" . $sort . "')", 
				$ignore_errors = true);

			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');
		}

		/**
		 * @param int chapter_id in old data model (in table b_learn_chapter)
		 * @return int chapter_id in new data model (in table b_learn_lesson) OR -1 on error
		 */
		public static function _GetChapterIdInNewDataModel ($b_learn_chapter_ID)
		{
			global $DB;

			$res = $DB->Query (
				"SELECT ID FROM b_learn_lesson 
				WHERE WAS_CHAPTER_ID = '" . ($b_learn_chapter_ID + 0) . "'", 
				$ignore_errors = true);
			
			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			if ($arLesson = $res->Fetch())
				return ($arLesson['ID'] + 0);
			else
				return (-1);
		}

		/**
		 * @param int course_id in old data model (in table b_learn_course)
		 * @return int course_id in new data model (in table b_learn_lesson) OR -1 on error
		 */
		public static function _GetCourseIdInNewDataModel ($b_learn_course_ID)
		{
			global $DB;

			$res = $DB->Query (
				"SELECT ID FROM b_learn_lesson 
				WHERE WAS_COURSE_ID = '" . ($b_learn_course_ID + 0) . "'", 
				$ignore_errors = true);
			
			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			if ($arLesson = $res->Fetch())
				return ($arLesson['ID'] + 0);
			else
				return (-1);
		}

		/**
		 * @return int items processed
		 */
		public static function _processCourses()
		{
			global $DB;

			$res = $DB->Query ("SELECT * FROM b_learn_course WHERE JOURNAL_STATUS != " . self::JOURNAL_STATUS_COURSE_LINKED, 
				$ignore_errors = true);
			
			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			while ($arCourses = $res->Fetch())
			{
				$arFields = array(
					'ACTIVE'                => $arCourses['ACTIVE'],
					'NAME'                  => ($arCourses['NAME'] === NULL) ? false : $arCourses['NAME'],
					'CODE'                  => ($arCourses['CODE'] === NULL) ? false : $arCourses['CODE'],
					'SORT'                  => $arCourses['SORT'],
					'PREVIEW_PICTURE'       => ($arCourses['PREVIEW_PICTURE'] === NULL) ? false : $arCourses['PREVIEW_PICTURE'],
					'DETAIL_PICTURE'        => ($arCourses['PREVIEW_PICTURE'] === NULL) ? false : $arCourses['PREVIEW_PICTURE'],
					'PREVIEW_TEXT_TYPE'     => $arCourses['PREVIEW_TEXT_TYPE'],
					'DETAIL_TEXT_TYPE'      => $arCourses['DESCRIPTION_TYPE'],
					'LAUNCH'                => '',
					'JOURNAL_STATUS'        => self::JOURNAL_STATUS_UNPROCESSED,
					'WAS_CHAPTER_ID'        => false,
					'WAS_PARENT_CHAPTER_ID' => false,
					'WAS_PARENT_COURSE_ID'  => false,
					'WAS_COURSE_ID'         => $arCourses['ID'],
					'PREVIEW_TEXT'          => $arCourses['PREVIEW_TEXT'],
					'DETAIL_TEXT'           => $arCourses['DESCRIPTION']
					);

				// Creates new lesson (unprocessed duplicates will be removed first)
				$id_of_new_lesson = self::_UnrepeatableCreateLesson ($arFields);

				// Link course to this lesson
				$rc = $DB->Update ('b_learn_course', 
					array ('LINKED_LESSON_ID' => $id_of_new_lesson), 
					"WHERE ID = '" . ($arCourses['ID'] + 0) . "'",
					$error_position = "",
					$debug = false,
					$ignore_errors = true
				);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				// Mark this course as processed
				$rc = $DB->Update('b_learn_course', 
					array ('JOURNAL_STATUS' => self::JOURNAL_STATUS_COURSE_LINKED), 
					"WHERE ID = '" . ($arCourses['ID'] + 0) . "'",
					$error_position = "",
					$debug = false,
					$ignore_errors = true
				);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				++self::$items_processed;

				// This function throws exception CLearnInstall201203ConvertDBTimeOut, if it's low time left.
				self::avoidTimeout();
			}
		}

		/**
		 * This function throws exception CLearnInstall201203ConvertDBTimeOut, if it's low time left.
		 */
		public static function avoidTimeout()
		{
			static $started_at = false;
			static $time_limit = false;

			if ($started_at === false)
			{
				$started_at = microtime (true);

				$rc = ini_get('max_execution_time');
				if (($rc === false) || ($rc === '') || ($rc < 0))
				{
					// We fail to determine max_execution_time, try to set it
					set_time_limit (25);

					// Ensure, that max_execution_time was set
					$rc = ini_get('max_execution_time');
				}

				if (($rc === false) || ($rc === '') || ($rc < 0))
				{
					/**
					 * Hmmm... WTF?!
					 * Lets think, that our limit is 25 seconds.
					 * If it actually less, there is nothing wrong, 
					 * because current algorithm should be firm to breaks in any place.
					 */
					$time_limit = 25;
				}
				elseif ($rc == 0)
				{
					/**
					 * We have unlimited time, but some troubles can occur in IIS,
					 * so limit time to 20 seconds.
					 */
					$time_limit = 20;
				}
				else
				{
					$time_limit = min(25, ($rc + 0));
				}
			}

			$time_executed = microtime(true) - $started_at;
			$time_left = $time_limit - $time_executed;

			if ($time_left < 4)
				throw new CLearnInstall201203ConvertDBTimeOut();
		}


		public static function _processChapters()
		{
			global $DB;

			$res = $DB->Query ("SELECT * FROM b_learn_chapter WHERE JOURNAL_STATUS != " . self::JOURNAL_STATUS_CHAPTER_COPIED, 
				$ignore_errors = true);
			
			if ($res === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			while ($arChapter = $res->Fetch())
			{
				$arFields = array (
					'ACTIVE'                => $arChapter['ACTIVE'],
					'NAME'                  => ($arChapter['NAME'] === NULL) ? false : $arChapter['NAME'],
					'CODE'                  => ($arChapter['CODE'] === NULL) ? false : $arChapter['CODE'],
					'SORT'                  => (string) (1000000 + (int) $arChapter['SORT']),
					'PREVIEW_PICTURE'       => ($arChapter['PREVIEW_PICTURE'] === NULL) ? false : $arChapter['PREVIEW_PICTURE'],
					'PREVIEW_TEXT'          => $arChapter['PREVIEW_TEXT'],
					'PREVIEW_TEXT_TYPE'     => $arChapter['PREVIEW_TEXT_TYPE'],
					'DETAIL_PICTURE'        => ($arChapter['DETAIL_PICTURE'] === NULL) ? false : $arChapter['DETAIL_PICTURE'],
					'DETAIL_TEXT'           => $arChapter['DETAIL_TEXT'],
					'DETAIL_TEXT_TYPE'      => $arChapter['DETAIL_TEXT_TYPE'],
					'LAUNCH'                => '',
					'JOURNAL_STATUS'        => self::JOURNAL_STATUS_UNPROCESSED,
					'WAS_CHAPTER_ID'        => ($arChapter['ID']),
					'WAS_PARENT_CHAPTER_ID' => ($arChapter['CHAPTER_ID'] === NULL) ? false : $arChapter['CHAPTER_ID'],
					'WAS_PARENT_COURSE_ID'  => ($arChapter['COURSE_ID'] === NULL) ? false : $arChapter['COURSE_ID'],
					'WAS_COURSE_ID'         => false
					);

				// Creates new lesson (unprocessed duplicates will be removed first)
				$id_of_new_lesson = self::_UnrepeatableCreateLesson ($arFields);

				// Now we must replace QUESTIONS_FROM_ID (where now is $arChapter['ID'])
				// in b_learn_test for QUESTIONS_FROM=='H'. Replace them to QUESTIONS_FROM=''

				// UPDATE b_learn_test SET QUESTIONS_FROM = 'R', QUESTIONS_FROM_ID = 150 WHERE QUESTIONS_FROM = 'H' AND QUESTIONS_FROM_ID = '1'
				$rc = $DB->Query(
					"UPDATE b_learn_test 
					SET TIMESTAMP_X = " . $DB->GetNowFunction()
					. ", QUESTIONS_FROM = 'R', 
					QUESTIONS_FROM_ID = " . ($id_of_new_lesson + 0)
					. " WHERE QUESTIONS_FROM = 'H' 
						AND QUESTIONS_FROM_ID = '" . ($arChapter['ID'] + 0) . "'",
					$ignore_errors = true
				);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				// Mark this chapter as processed
				$rc = $DB->Update('b_learn_chapter', 
					array ('JOURNAL_STATUS' => self::JOURNAL_STATUS_CHAPTER_COPIED), 
					"WHERE ID = '" . ($arChapter['ID'] + 0) . "'",
					$error_position = "",
					$debug = false,
					$ignore_errors = true
				);

				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				++self::$items_processed;

				// This function throws exception CLearnInstall201203ConvertDBTimeOut, if it's low time left.
				self::avoidTimeout();
			}
		}

		/**
		 * Inserts new lesson to `b_learn_lesson`. Before insert, drop
		 * exists lessons with such `WAS_CHAPTER_ID` (if not NULL)
		 * or with such `WAS_COURSE_ID` (if not NULL)
		 */
		public static function _UnrepeatableCreateLesson ($arFields)
		{
			global $DB, $DBType;

			$dbtype = strtolower($DBType);

			if ( ! is_array($arFields) )
				throw new CLearnInstall201203ConvertDBException('EA_PARAMS');

			if ( ! isset($arFields['COURSE_ID']) )
				$arFields['COURSE_ID'] = 0;

			// Determine, from what source import doing (Chapter or Course)
			if (array_key_exists('WAS_CHAPTER_ID', $arFields) 
				&& ($arFields['WAS_CHAPTER_ID'] !== false)
			)
			{
				// new lesson will be created from chapter
				$sqlWhere = "WAS_CHAPTER_ID = '" . ($arFields['WAS_CHAPTER_ID'] + 0) . "'";
			}
			elseif (array_key_exists('WAS_COURSE_ID', $arFields) 
				&& ($arFields['WAS_COURSE_ID'] !== false)
			)
			{
				// new lesson will be created from chapter
				$sqlWhere = "WAS_COURSE_ID = '" . ($arFields['WAS_COURSE_ID'] + 0) . "'";
			}
			else
			{
				throw new CLearnInstall201203ConvertDBException('EA_PARAMS');
			}

			// Firstly, remove such imported lesson, if exists
			$rc = $DB->Query ("DELETE FROM b_learn_lesson WHERE " . $sqlWhere, $ignore_errors = true);
			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

			$arInsert = $DB->PrepareInsert('b_learn_lesson', $arFields);

			if ($dbtype === 'oracle')
			{
				$newLessonId = intval($DB->NextID('sq_b_learn_lesson'));

				$strSql =
					"INSERT INTO b_learn_lesson 
						(ID, " . $arInsert[0] . ", 
						TIMESTAMP_X, DATE_CREATE, CREATED_BY) " .
					"VALUES 
						(" . $newLessonId . ", " . $arInsert[1] . ", " 
						. $DB->GetNowFunction() . ", " . $DB->GetNowFunction() . ", 1)";

				$arBinds = array();

				if (array_key_exists('PREVIEW_TEXT', $arFields))
					$arBinds['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
				
				if (array_key_exists('DETAIL_TEXT', $arFields))
					$arBinds['DETAIL_TEXT'] = $arFields['DETAIL_TEXT'];

				$rc = $DB->QueryBind($strSql, $arBinds);
				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');
			}
			elseif (($dbtype === 'mssql') || ($dbtype === 'mysql'))
			{
				$strSql =
					"INSERT INTO b_learn_lesson 
						(" . $arInsert[0] . ", 
						TIMESTAMP_X, DATE_CREATE, CREATED_BY) " .
					"VALUES 
						(" . $arInsert[1] . ", " 
						. $DB->GetNowFunction() . ", " . $DB->GetNowFunction() . ", 1)";

				$rc = $DB->Query($strSql, true);
				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException('EA_SQLERROR');

				$newLessonId = intval($DB->LastID());
			}

			return ($newLessonId);
		}

		/**
		 * We are converted if option with name self::OPTION_ID is set to self::STATUS_INSTALL_COMPLETE
		 * 
		 * !!! But, if:
		 * 1) this option is set to self::STATUS_INSTALL_NEVER_START 
		 * AND
		 * 2) there is tables b_learn_lesson_edges exists & b_learn_rights_all 
		 * and b_learn_course_permission doesn't exists
		 * it means that options is incorrectly set (or was reseted by somebody else), so we returns that DB is already converted
		 * 
		 */
		public static function _IsAlreadyConverted()
		{
			$rc = (string) COption::GetOptionString(self::MODULE_ID, self::OPTION_ID, self::STATUS_INSTALL_NEVER_START, $site = '');

			if ($rc === self::STATUS_INSTALL_NEVER_START)
			{
				global $DB;

				if ($DB->TableExists('b_learn_lesson_edges')
					&& $DB->TableExists('b_learn_rights_all')
					&& ( ! $DB->TableExists('b_learn_course_permission') )
				)
				{
					// Mark that db convert process complete
					$rc = COption::SetOptionString(self::MODULE_ID, self::OPTION_ID, self::STATUS_INSTALL_COMPLETE);
					if ($rc === false)
						throw new CLearnInstall201203ConvertDBException('SetOptionString() failed!');

					return (true);
				}
				else
					return (false);
			}
			elseif ($rc === self::STATUS_INSTALL_COMPLETE)
				return (true);
			elseif ($rc === self::STATUS_INSTALL_INCOMPLETE)
				return (false);
			else
				self::_GiveUp(__LINE__);
		}


		protected static function _RemoveOrphanedTables()
		{
			global $DB, $DBType;

			if ( ! $DB->TableExists('b_learn_course_permission') )
				return;

			$rc = $DB->Query ("DROP TABLE b_learn_course_permission", true);
			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('Can\'t DROP `b_learn_course_permission` under database engine: ' . $DBType);
		}

		public static function _CreateEdgesTbl()
		{
			global $DB, $DBType;

			if ($DB->TableExists('b_learn_lesson_edges'))
				return;

			switch (strtolower($DBType))
			{
				case 'mysql':
					$sql 
					= "CREATE TABLE b_learn_lesson_edges (
						SOURCE_NODE INT NOT NULL ,
						TARGET_NODE INT NOT NULL ,
						SORT INT NOT NULL DEFAULT '500',
						PRIMARY KEY ( SOURCE_NODE , TARGET_NODE )
					)";
				break;

				case 'mssql':
					$sql 
					= "CREATE TABLE B_LEARN_LESSON_EDGES (
						SOURCE_NODE INT NOT NULL ,
						TARGET_NODE INT NOT NULL ,
						SORT INT NOT NULL DEFAULT '500',
						CONSTRAINT PK_B_LEARN_LESSON_EDGES PRIMARY KEY (SOURCE_NODE, TARGET_NODE)
					)
					
					";
				break;

				case 'oracle':
					$sql
					= "CREATE TABLE b_learn_lesson_edges
					(
						SOURCE_NODE NUMBER(11) NOT NULL,
						TARGET_NODE NUMBER(11) NOT NULL,
						SORT NUMBER(11) DEFAULT 500 NOT NULL,
						PRIMARY KEY(SOURCE_NODE, TARGET_NODE)
					)
					
					";
				break;

				default:
					throw new CLearnInstall201203ConvertDBException('Unsupported database engine: ' . $DBType);
				break;
			}

			$rc = $DB->Query ($sql, $ignore_errors = true);
			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException('Can\'t create `b_learn_lesson_edges` under database engine: ' . $DBType);
		}

		public static function _CreateFieldsInTbls()
		{
			global $DB, $DBType;

			$arTableFields = array(
				'b_learn_lesson'  => $DB->GetTableFieldsList ('b_learn_lesson'),
				'b_learn_chapter' => $DB->GetTableFieldsList ('b_learn_chapter'),
				'b_learn_course'  => $DB->GetTableFieldsList ('b_learn_course')
				);

			$sql_add = array();
			$dbtype = strtolower($DBType);
			$other_sql_skip_errors = array();
			$other_sql = array();

			// Prepare sql code for adding fields
			if ($dbtype === 'mysql')
			{
				$sql_add['b_learn_lesson'] = array (
					'KEYWORDS'              => "ALTER TABLE b_learn_lesson ADD KEYWORDS TEXT NOT NULL",
					'CODE'                  => "ALTER TABLE b_learn_lesson ADD CODE VARCHAR( 50 ) NULL DEFAULT NULL",
					'WAS_CHAPTER_ID'        => "ALTER TABLE b_learn_lesson ADD WAS_CHAPTER_ID INT NULL DEFAULT NULL",
					'WAS_PARENT_CHAPTER_ID' => "ALTER TABLE b_learn_lesson ADD WAS_PARENT_CHAPTER_ID INT NULL DEFAULT NULL",
					'WAS_PARENT_COURSE_ID'  => "ALTER TABLE b_learn_lesson ADD WAS_PARENT_COURSE_ID INT NULL DEFAULT NULL",
					'WAS_COURSE_ID'         => "ALTER TABLE b_learn_lesson ADD WAS_COURSE_ID INT NULL DEFAULT NULL",
					'JOURNAL_STATUS'        => "ALTER TABLE b_learn_lesson ADD JOURNAL_STATUS INT NOT NULL DEFAULT '0'"
					);
				
				$sql_add['b_learn_chapter'] = array (
					'JOURNAL_STATUS' => "ALTER TABLE b_learn_chapter ADD JOURNAL_STATUS INT NOT NULL DEFAULT '0'"
					);
				
				$sql_add['b_learn_course'] = array (
					'LINKED_LESSON_ID' => "ALTER TABLE b_learn_course ADD LINKED_LESSON_ID INT NULL DEFAULT NULL",
					'JOURNAL_STATUS'   => "ALTER TABLE b_learn_course ADD JOURNAL_STATUS INT NOT NULL DEFAULT '0'"
					);

				$sql_tbl_b_learn_rights = "
					CREATE TABLE b_learn_rights (
					LESSON_ID INT UNSIGNED NOT NULL ,
					SUBJECT_ID VARCHAR( 100 ) NOT NULL ,
					TASK_ID INT NOT NULL ,
					PRIMARY KEY ( LESSON_ID , SUBJECT_ID )
					)";

				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_lesson DROP FOREIGN KEY FK_B_LEARN_LESSON1';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_lesson DROP FOREIGN KEY FK_B_LEARN_LESSON2';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_chapter DROP FOREIGN KEY FK_B_LEARN_CHAPTER1';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_chapter DROP FOREIGN KEY FK_B_LEARN_CHAPTER2';

				$other_sql_skip_errors[] = "ALTER TABLE b_learn_course ALTER COLUMN NAME SET DEFAULT 'name'";
				$other_sql_skip_errors[] = "ALTER TABLE b_learn_lesson ALTER COLUMN NAME SET DEFAULT 'name'";
				$other_sql_skip_errors[] = "ALTER TABLE b_learn_lesson ALTER COLUMN COURSE_ID SET DEFAULT '0'";

				$other_sql_skip_errors[] = "
					CREATE TABLE b_learn_publish_prohibition
					(
						COURSE_LESSON_ID INT UNSIGNED NOT NULL ,
						PROHIBITED_LESSON_ID INT UNSIGNED NOT NULL ,
						PRIMARY KEY ( COURSE_LESSON_ID , PROHIBITED_LESSON_ID )
					)";

				$other_sql_skip_errors[] = "
					CREATE TABLE b_learn_exceptions_log (
						DATE_REGISTERED datetime NOT NULL,
						CODE int(11) NOT NULL,
						MESSAGE text NOT NULL,
						FFILE text NOT NULL,
						LINE int(11) NOT NULL,
						BACKTRACE text NOT NULL
					)";
			}
			elseif ($dbtype === 'mssql')
			{
				$sql_add['b_learn_lesson'] = array (
					'KEYWORDS'              => "ALTER TABLE b_learn_lesson ADD KEYWORDS TEXT NOT NULL DEFAULT ''",
					'CODE'                  => "ALTER TABLE b_learn_lesson ADD CODE VARCHAR( 50 ) NULL DEFAULT NULL",
					'WAS_CHAPTER_ID'        => "ALTER TABLE b_learn_lesson ADD WAS_CHAPTER_ID INT NULL DEFAULT NULL",
					'WAS_PARENT_CHAPTER_ID' => "ALTER TABLE b_learn_lesson ADD WAS_PARENT_CHAPTER_ID INT NULL DEFAULT NULL",
					'WAS_PARENT_COURSE_ID'  => "ALTER TABLE b_learn_lesson ADD WAS_PARENT_COURSE_ID INT NULL DEFAULT NULL",
					'WAS_COURSE_ID'         => "ALTER TABLE b_learn_lesson ADD WAS_COURSE_ID INT NULL DEFAULT NULL",
					'JOURNAL_STATUS'        => "ALTER TABLE b_learn_lesson ADD JOURNAL_STATUS INT NOT NULL DEFAULT '0'"
					);
				
				$sql_add['b_learn_chapter'] = array (
					'JOURNAL_STATUS' => "ALTER TABLE b_learn_chapter ADD JOURNAL_STATUS INT NOT NULL DEFAULT '0'"
					);
				
				$sql_add['b_learn_course'] = array (
					'LINKED_LESSON_ID' => "ALTER TABLE b_learn_course ADD LINKED_LESSON_ID INT NULL DEFAULT NULL",
					'JOURNAL_STATUS'   => "ALTER TABLE b_learn_course ADD JOURNAL_STATUS INT NOT NULL DEFAULT '0'"
					);

				$sql_tbl_b_learn_rights = "
				CREATE TABLE B_LEARN_RIGHTS
				(
					LESSON_ID INT NOT NULL ,
					SUBJECT_ID VARCHAR(100) NOT NULL,
					TASK_ID INT NOT NULL,
					CONSTRAINT PK_B_LEARN_RIGHTS PRIMARY KEY(LESSON_ID, SUBJECT_ID)
				)";

				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_lesson DROP CONSTRAINT FK_B_LEARN_LESSON1';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_lesson DROP CONSTRAINT FK_B_LEARN_LESSON2';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_chapter DROP CONSTRAINT FK_B_LEARN_CHAPTER1';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_chapter DROP CONSTRAINT FK_B_LEARN_CHAPTER2';

				$other_sql_skip_errors[] = "ALTER TABLE b_learn_course ADD CONSTRAINT DF_b_learn_course_name DEFAULT 'name' FOR NAME";
				$other_sql_skip_errors[] = "ALTER TABLE b_learn_lesson ADD CONSTRAINT DF_b_learn_lesson_name DEFAULT 'name' FOR NAME";
				$other_sql_skip_errors[] = "ALTER TABLE b_learn_lesson ADD CONSTRAINT DF_b_learn_lesson_course_id DEFAULT 0 FOR COURSE_ID";

				$other_sql_skip_errors[] = "
					CREATE TABLE B_LEARN_PUBLISH_PROHIBITION
					(
						COURSE_LESSON_ID INT NOT NULL ,
						PROHIBITED_LESSON_ID INT NOT NULL,
						CONSTRAINT PK_B_LEARN_PUBLISH_PROHIBITION PRIMARY KEY(COURSE_LESSON_ID, PROHIBITED_LESSON_ID)
					)";

				$other_sql_skip_errors[] = "
					CREATE TABLE B_LEARN_EXCEPTIONS_LOG (
						DATE_REGISTERED DATETIME NOT NULL DEFAULT GETDATE(),
						CODE INT NOT NULL,
						MESSAGE TEXT NOT NULL,
						FFILE TEXT NOT NULL,
						LINE INT NOT NULL,
						BACKTRACE TEXT NOT NULL
					)";

				/*

				declare @default sysname, @sql nvarchar(max)

				SELECT @default = name 
				FROM sys.default_constraints 
				WHERE parent_object_id = object_id('b_learn_lesson')
				AND type = 'D'
				AND parent_column_id = (
					select column_id 
					from sys.columns 
					where object_id = object_id('b_learn_lesson')
					and name = 'COURSE_ID'
				)

				-- create alter table command as string and run it
				set @sql = N'ALTER TABLE b_learn_lesson DROP CONSTRAINT ' + @default
				exec sp_executesql @sql

				-- now we can alter column
				ALTER TABLE [TABLE_NAME]
				ALTER COLUMN COLUMN_NAME datetime -- here you can have any datatype you want of course

				-- last step, we need to recreate constraint
				-- DEFAULT getdate() is just for example, you can have any constraint you need of course
				ALTER TABLE [TABLE_NAME]
				ADD CONSTRAINT [YOUR_CONSTRAINT_NAME] DEFAULT getdate() For COLUMN_NAME 
				*/
			}
			elseif ($dbtype === 'oracle')
			{
				$sql_add['b_learn_lesson'] = array (
					'KEYWORDS'              => "ALTER TABLE b_learn_lesson ADD KEYWORDS CLOB DEFAULT ' '",
					'CODE'                  => "ALTER TABLE b_learn_lesson ADD CODE VARCHAR( 50 CHAR ) NULL",
					'WAS_CHAPTER_ID'        => "ALTER TABLE b_learn_lesson ADD WAS_CHAPTER_ID NUMBER(11) NULL",
					'WAS_PARENT_CHAPTER_ID' => "ALTER TABLE b_learn_lesson ADD WAS_PARENT_CHAPTER_ID NUMBER(11) NULL",
					'WAS_PARENT_COURSE_ID'  => "ALTER TABLE b_learn_lesson ADD WAS_PARENT_COURSE_ID NUMBER(11) NULL",
					'WAS_COURSE_ID'         => "ALTER TABLE b_learn_lesson ADD WAS_COURSE_ID NUMBER(11) NULL",
					'JOURNAL_STATUS'        => "ALTER TABLE b_learn_lesson ADD JOURNAL_STATUS NUMBER(11) DEFAULT '0' NOT NULL"
					);
				
				$sql_add['b_learn_chapter'] = array (
					'JOURNAL_STATUS' => "ALTER TABLE b_learn_chapter ADD JOURNAL_STATUS NUMBER(11) DEFAULT '0' NOT NULL"
					);
				
				$sql_add['b_learn_course'] = array (
					'LINKED_LESSON_ID' => "ALTER TABLE b_learn_course ADD LINKED_LESSON_ID NUMBER(11) NULL",
					'JOURNAL_STATUS'   => "ALTER TABLE b_learn_course ADD JOURNAL_STATUS NUMBER(11) DEFAULT '0' NOT NULL"
					);

				$sql_tbl_b_learn_rights = "
				CREATE TABLE b_learn_rights
				(
					LESSON_ID NUMBER(11) NOT NULL ,
					SUBJECT_ID VARCHAR2(100 CHAR) NOT NULL,
					TASK_ID NUMBER(11) NOT NULL,
					PRIMARY KEY(LESSON_ID, SUBJECT_ID)
				)";

				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_lesson DROP CONSTRAINT FK_B_LEARN_LESSON1';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_lesson DROP CONSTRAINT FK_B_LEARN_LESSON2';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_chapter DROP CONSTRAINT FK_B_LEARN_CHAPTER1';
				$other_sql_skip_errors[] = 'ALTER TABLE b_learn_chapter DROP CONSTRAINT FK_B_LEARN_CHAPTER2';

				$other_sql_skip_errors[] = "ALTER TABLE b_learn_course MODIFY NAME DEFAULT 'name'";
				$other_sql_skip_errors[] = "ALTER TABLE b_learn_lesson MODIFY NAME DEFAULT 'name'";
				$other_sql_skip_errors[] = "ALTER TABLE b_learn_lesson MODIFY COURSE_ID DEFAULT '0'";

				$other_sql_skip_errors[] = "
				CREATE TABLE b_learn_exceptions_log (
					DATE_REGISTERED DATE DEFAULT SYSDATE NOT NULL,
					CODE NUMBER(11) NOT NULL,
					MESSAGE CLOB NOT NULL,
					FFILE CLOB NOT NULL,
					LINE NUMBER(11) NOT NULL,
					BACKTRACE CLOB NOT NULL
				)";

				$other_sql_skip_errors[] = "
				CREATE TABLE b_learn_publish_prohibition
				(
					COURSE_LESSON_ID NUMBER(11) NOT NULL ,
					PROHIBITED_LESSON_ID NUMBER(11) NOT NULL ,
					PRIMARY KEY ( COURSE_LESSON_ID , PROHIBITED_LESSON_ID )
				)";
			}
			else
			{
				throw new CLearnInstall201203ConvertDBException('SQL code not ready for: ' . $DBType . ' in line #' . __LINE__);
			}

			if ( ! $DB->TableExists('b_learn_rights'))
			{
				$rc = $DB->Query($sql_tbl_b_learn_rights);
				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException(__LINE__ . '/tbl: sql_tbl_b_learn_rights');
			}

			foreach ($sql_add as $tableName => $sql_for_table)
			{
				// Add every field (if not exists yet) to table $tableName
				foreach ($sql_for_table as $fieldName => $sql)
				{
					if ( ! in_array($fieldName, $arTableFields[$tableName], true) )
					{
						$rc = $DB->Query($sql, $ignore_erros = true);

						if ($rc === false)
							throw new CLearnInstall201203ConvertDBException(__LINE__ . '/tbl:' . strlen($fieldName));
					}
				}
				/*

				!!! This does not work correctly (table's fields cache in Database class issue?)
				// Now, ensure, that fields was added really
				$arTableFields_after = $DB->GetTableFieldsList ($tableName);
				foreach ($sql_for_table as $fieldName => $sql)
				{
					if ( ! in_array($fieldName, $arTableFields_after, true) )
						self::_GiveUp(__LINE__ . '/tbl:' . strlen($fieldName));
				}
				*/
			}

			foreach ($other_sql_skip_errors as $sql)
				$rc = $DB->Query($sql, $ignore_erros = true);

			foreach ($other_sql as $sql)
			{
				$rc = $DB->Query($sql, $ignore_erros = true);
				if ($rc === false)
					throw new CLearnInstall201203ConvertDBException(__LINE__ . '/sql:' . htmlspecialcharsbx($sql));
			}

			// Drop cache
			$rc = $DB->DDL("SELECT * FROM b_learn_lesson WHERE 1=1", true);
			if ($rc === false)
				throw new CLearnInstall201203ConvertDBException(__LINE__ . ', on DDL\'s cache drop');
		}

		/*
		public static function _RemoveFieldsFromLesson()
		{
			//		ORACLE:
			//		ALTER TABLE `b_learn_lesson` DROP COLUMN `JOURNAL_ID` ???

			global $DB, $DBType;

			$arTableFields = $DB->GetTableFieldsList (`b_learn_lesson`);

			// Prepare sql code for removing fields
			$sql_add = array();
			if ($DBType === 'mysql')
			{
				$sql_add['JOURNAL_ID'] = "ALTER TABLE `b_learn_lesson` DROP `JOURNAL_ID`";
			}
			else
			{
				// TODO: do sql code for MSSQL and Oracle

				self::_GiveUp ('SQL code not ready for: ' . $DBType);
			}

			// Remove every field (if exists) from table b_learn_lesson
			foreach ($sql_add as $fieldName => $sql)
			{
				if ( in_array($fieldName, $arTableFields, true) )
				{
					$rc = $DB->Query($sql, $ignore_erros = true);

					if ($rc === false)
						self::_GiveUp(__LINE__);
				}
			}

			// Don't ensure, that fields was really removed, because it's not critical for us
		}
		*/

		public static function _GiveUp($msg = false)
		{
			if ($msg !== false)
				throw new Exception ('FATAL: ' . $msg);
			else
				throw new Exception ('Shit happens.');
		}
	}

