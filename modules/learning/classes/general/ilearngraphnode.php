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
interface ILearnGraphNode
{
	/**
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Insert() in main
	 * module (version info:
	 *    define("SM_VERSION","11.0.12");
	 *    define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param array of pairs field => value for new GraphNode. Allowed fields are:
	 * ACTIVE, true by default, available values are: true/false
	 * NAME, mustn't be omitted
	 * CODE, NULL by default
	 * PREVIEW_PICTURE, NULL by default, available value is array ('name' => ..., 
	 *     'size' => ..., 'tmp_name' => ..., 'type' => ..., 'del' => ...)
	 * PREVIEW_TEXT, NULL by default
	 * PREVIEW_TEXT_TYPE, 'text' by default, available values are: 'text', 'html'
	 * DETAIL_PICTURE, NULL by default, available value is array ('name' => ..., 
	 *     'size' => ..., 'tmp_name' => ..., 'type' => ..., 'del' => ...)
	 * DETAIL_TEXT, NULL by default
	 * DETAIL_TEXT_TYPE, 'text' by default, available values are: 'text', 'html', 'file' (filename in LAUNCH)
	 * LAUNCH, NULL by default
	 *
	 * @throws LearnException with errcodes:
	 *         - LearnException::EXC_ERR_GN_CREATE,
	 *         - LearnException::EXC_ERR_GN_CHECK_PARAMS,
	 *         - LearnException::EXC_ERR_GN_FILE_UPLOAD
	 * Also can throws other exceptions or exceptions' codes
	 *
	 * @return integer id of created graph node
	 */
	public static function Create ($arFields);

	/**
	 * WARNING: this method terminates (by die()/exit()) current execution flow
	 * when SQL server error occured. It's due to bug in CDatabase::Update() in main
	 * module (version info:
	 *    // define("SM_VERSION","11.0.12");
	 *    // define("SM_VERSION_DATE","2012-02-21 17:00:00"); // YYYY-MM-DD HH:MI:SS
	 * )
	 *
	 * @param integer id of node to be updated
	 * @param array of pairs field => value for new GraphNode
	 *
	 * @throws LearnException with errcodes:
	 *         - LearnException::EXC_ERR_GN_UPDATE,
	 *         - LearnException::EXC_ERR_GN_CHECK_PARAMS,
	 *         - LearnException::EXC_ERR_GN_FILE_UPLOAD
	 * Also can throws other exceptions or exceptions' codes
	 */
	public static function Update ($id, $arFields);

	/**
	 * @param integer id of node to be getted
	 *
	 * @throws LearnException with errcode LearnException::EXC_ERR_GN_GETBYID.
	 *         Messages can be: 'EA_PARAMS', 'EA_ACCESS_DENIED',
	 *         'EA_SQLERROR', 'EA_NOT_EXISTS'.
	 *
	 * @return array of properties for node with $id
	 */
	public static function GetByID ($id);

	/**
	 * @param integer id of node to be removed
	 *
	 * @throws LearnException with errcode LearnException::EXC_ERR_GN_REMOVE,
	 *         also errmsg === 'EA_NOT_EXISTS' if there is wasn't node with this id.
	 */
	public static function Remove ($id);
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
abstract class CLearnGraphNode implements ILearnGraphNode
{
	// Rights allowed for different fields (stored as bitmask). MUST be integers >= zero
	const SQL_NONE   = 0;
	const SQL_SELECT = 1;
	const SQL_INSERT = 2;
	const SQL_UPDATE = 4;

	public static function Remove($id)
	{
		global $DB;

		if ( ! is_numeric($id) )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_GN_REMOVE);

		$lessonData = self::GetByID($id);
		if ( ! array_key_exists('NAME', $lessonData) )
			throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_GN_REMOVE);

		// Remove pictures
		if ( array_key_exists('PREVIEW_PICTURE', $lessonData) && ($lessonData['PREVIEW_PICTURE'] > 0) )
			CFile::Delete($lessonData['PREVIEW_PICTURE']);

		if ( array_key_exists('DETAIL_PICTURE', $lessonData) && ($lessonData['DETAIL_PICTURE'] > 0) )
			CFile::Delete($lessonData['DETAIL_PICTURE']);

		// Remove SCORM data
		if ( array_key_exists('SCORM', $lessonData) && ($lessonData['SCORM'] === 'Y') )
			DeleteDirFilesEx("/".(COption::GetOptionString("main", "upload_dir", "upload"))."/learning/scorm/" . $id);

		// Remove graph node
		$rc = $DB->Query (
			"DELETE FROM b_learn_lesson
			WHERE ID = '" . ($id + 0) . "'",
			true	// ignore errors
			);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GN_REMOVE);

		if ($rc->AffectedRowsCount() == 0)
			throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_GN_REMOVE);
	}

	public static function GetByID($id)
	{
		global $DB;

		static $cacheFieldsToSelect = null;

		if ( ! (is_numeric ($id) && is_int ($id + 0)) )
			throw new LearnException ('EA_PARAMS', LearnException::EXC_ERR_GN_GETBYID);

		// Prepare list of DB fields to be selected
		if ($cacheFieldsToSelect === null)
		{
			$arReversedFieldsMap = self::_GetReversedFieldsMap();

			$arFieldsToSelect = array();
			foreach ($arReversedFieldsMap as $fieldNameInDB => $value)
			{
				if ($value['access'] & self::SQL_SELECT)
				{
					if ( ($fieldNameInDB === 'TIMESTAMP_X') || ($fieldNameInDB === 'DATE_CREATE') )
						$arFieldsToSelect[] = $DB->DateToCharFunction($fieldNameInDB) . ' AS ' . $fieldNameInDB;
					else
						$arFieldsToSelect[] = $fieldNameInDB;
				}
			}

			$cacheFieldsToSelect = implode (',', $arFieldsToSelect);

			if ( ! (strlen($cacheFieldsToSelect) > 0) )
				$cacheFieldsToSelect = false;
		}

		if ($cacheFieldsToSelect === false)
			throw new LearnException ('EA_ACCESS_DENIED', LearnException::EXC_ERR_GN_GETBYID);

		// Get graph node data
		$rc = $DB->Query (
			"SELECT " . $cacheFieldsToSelect . "
			FROM b_learn_lesson
			WHERE ID='" . (int) ($id + 0) . "'",
			true	// ignore errors
			);

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', LearnException::EXC_ERR_GN_GETBYID);

		if ( ! (($arData = $rc->Fetch()) && is_array($arData)) )
			throw new LearnException ('EA_NOT_EXISTS', LearnException::EXC_ERR_GN_GETBYID);

		return ($arData);
	}

	public static function Create($arInFields)
	{
		$lastID = self::_InsertOrUpdate ($arInFields, 'insert');

		return ($lastID);
	}

	public static function Update($id, $arInFields)
	{
		self::_InsertOrUpdate ($arInFields, 'update', $id);
	}

	protected static function _InsertOrUpdate ($arInFields, $mode, $id = false)
	{
		global $DB, $USER, $DBType;

		$createdBy = 1;
		if (is_object($USER) && method_exists($USER, 'getId'))
			$createdBy = (int) $USER->getId();

		$dbtype = strtolower($DBType);

		switch ($mode)
		{
			case 'update':
				$accessLevel  = self::SQL_UPDATE;
				$throwErrCode = LearnException::EXC_ERR_GN_UPDATE;
				$isInsert     = false;
				$isForUpdate  = true;

				if ( ! is_numeric ($id) )
					throw new LearnException ('EA_PARAMS: $id', $throwErrCode);
			break;

			case 'insert':
				$accessLevel  = self::SQL_INSERT;
				$throwErrCode = LearnException::EXC_ERR_GN_CREATE;
				$isInsert     = true;
				$isForUpdate  = false;
			break;

			default:
				throw new LearnException ('EA_LOGIC',
					LearnException::EXC_ERR_ALL_LOGIC);
			break;
		}

		// Mapping of fields' names in db and in function arguments
		$arFieldsMap = self::_GetFieldsMap();

		// Check params for access_level (throws LearnException on failure). After - canonize it
		$arFields = self::_CheckAndCanonizeFields ($arFieldsMap, $arInFields, $accessLevel, $isForUpdate);

		// Prepares array of fields with values for query to DB. Also, uploads/removes files, if there are.
		if ($isForUpdate)
			$arFieldsToDb = self::_PrepareDataForQuery ($arFieldsMap, $arFields, $id);
		else
			$arFieldsToDb = self::_PrepareDataForQuery ($arFieldsMap, $arFields, false);

		$newLessonId = null;

		if ($isInsert)
		{
			$arInsert = $DB->PrepareInsert('b_learn_lesson', $arFieldsToDb);

			if ($dbtype === 'oracle')
			{
				$newLessonId = intval($DB->NextID('sq_b_learn_lesson'));

				$strSql =
					"INSERT INTO b_learn_lesson 
						(ID, " . $arInsert[0] . ", 
						TIMESTAMP_X, DATE_CREATE, CREATED_BY) " .
					"VALUES 
						(" . $newLessonId . ", " . $arInsert[1] . ", " 
						. $DB->GetNowFunction() . ", " . $DB->GetNowFunction() . ", " . $createdBy . ")";

				$arBinds = array();

				if (array_key_exists('PREVIEW_TEXT', $arFieldsToDb))
					$arBinds['PREVIEW_TEXT'] = $arFieldsToDb['PREVIEW_TEXT'];

				if (array_key_exists('DETAIL_TEXT', $arFieldsToDb))
					$arBinds['DETAIL_TEXT'] = $arFieldsToDb['DETAIL_TEXT'];

				if (array_key_exists('KEYWORDS', $arFieldsToDb))
					$arBinds['KEYWORDS'] = $arFieldsToDb['KEYWORDS'];

				$rc = $DB->QueryBind($strSql, $arBinds, true);
			}
			elseif (($dbtype === 'mssql') || ($dbtype === 'mysql'))
			{
				$strSql =
					"INSERT INTO b_learn_lesson 
						(" . $arInsert[0] . ", 
						TIMESTAMP_X, DATE_CREATE, CREATED_BY) " .
					"VALUES 
						(" . $arInsert[1] . ", " 
						. $DB->GetNowFunction() . ", " . $DB->GetNowFunction() . ", " . $createdBy . ")";

				$rc = $DB->Query($strSql, true);

				$newLessonId = intval($DB->LastID());
			}
		}
		else	// update
		{
			$strUpdate = $DB->PrepareUpdate('b_learn_lesson', $arFieldsToDb);

			if ($strUpdate !== '')
				$strUpdate .= ', ';

			$strSql = "UPDATE b_learn_lesson SET $strUpdate 
					TIMESTAMP_X = " . $DB->GetNowFunction()
				. " WHERE ID = " . ($id + 0);

			if ($dbtype === 'oracle')
			{
				$arBinds = array();

				if (array_key_exists('PREVIEW_TEXT', $arFieldsToDb))
					$arBinds['PREVIEW_TEXT'] = $arFieldsToDb['PREVIEW_TEXT'];

				if (array_key_exists('DETAIL_TEXT', $arFieldsToDb))
					$arBinds['DETAIL_TEXT'] = $arFieldsToDb['DETAIL_TEXT'];

				if (array_key_exists('KEYWORDS', $arFieldsToDb))
					$arBinds['KEYWORDS'] = $arFieldsToDb['KEYWORDS'];

				$rc = $DB->QueryBind($strSql, $arBinds);
			}
			elseif (($dbtype === 'mssql') || ($dbtype === 'mysql'))
			{
				$rc = $DB->Query($strSql, $bIgnoreErrors = true);
			}

			// TIMESTAMP_X - date when data last changed, so update it
			$arFieldsToDb['TIMESTAMP_X'] = $DB->GetNowFunction();
		}

		if ($rc === false)
			throw new LearnException ('EA_SQLERROR', $throwErrCode);

		if ($isInsert)
			return ($newLessonId);	// id of created node
	}

	/**
	 * Prepares array of fields with values for query to DB.
	 * Also, uploads/removes files, if there are.
	 * @throws LearnException on error. Errcodes are: LearnException::EXC_ERR_GN_CHECK_PARAMS,
	 *         LearnException::EXC_ERR_GN_FILE_UPLOAD
	 */
	protected static function _PrepareDataForQuery ($arFieldsMap, $arFields, $lessonId)
	{
		global $DB;

		$arLessonData = false;

		// if data prepartation for update - cache data about lesson to be updated
		if ($lessonId !== false)
		{
			// if lesson data not cached - get it
			if ($arLessonData === false)
				$arLessonData = self::GetByID ($lessonId);
		}

		$arFieldsToDb = array ();

		foreach ($arFields as $field => $value)
		{
			$fieldNameInDB = $arFieldsMap[$field]['field'];

			if ( ($field === 'PREVIEW_PICTURE')
				|| ($field === 'DETAIL_PICTURE')
				|| ($fieldNameInDB === 'PREVIEW_PICTURE')
				|| ($fieldNameInDB === 'DETAIL_PICTURE')
			)
			{
				$error = CFile::CheckImageFile($value);
				if (strlen($error) > 0)
				{
					throw new LearnException (
						'EA_PARAMS: ' . $error, 
						LearnException::EXC_ERR_GN_CHECK_PARAMS);
				}

				// if data prepartation for update - gets prev pictures names
				if ($lessonId !== false)
				{
					if ( ! array_key_exists($field, $arLessonData) )
						throw new LearnException ('EA_LOGIC', LearnException::EXC_ERR_ALL_LOGIC);

					$arFields[$field]['old_file'] = $arLessonData[$field];
					$value = $arFields[$field];
				}

				// throws LearnException on error, returns FALSE if id of image not updated
				$fileId = self::_UploadFile ($fieldNameInDB, $value);
				if ($fileId === false)
					continue;	// id of image not updated

				// replace value for current field to fileId
				$value = $arFields[$field] = $fileId;
			}

			if ($value === NULL)
				$arFieldsToDb[$fieldNameInDB] = false;
			else
				$arFieldsToDb[$fieldNameInDB] = $value;
		}

		return ($arFieldsToDb);
	}

	/**
	 * @return integer id of file in table b_file
	 */
	protected static function _UploadFile ($fieldNameInDB, $arData)
	{
		if ( ! is_array($arData) )
		{
			throw new LearnException ('EA_PARAMS: ' . var_export ($arData, true),
				LearnException::EXC_ERR_GN_CHECK_PARAMS);
		}

		// Check for fields needed by CFile::SaveForDB
		$fieldsMustBe = array ('name', 'size', 'tmp_name', 'type', 'del', 'MODULE_ID');
		if (count(array_diff($fieldsMustBe, array_keys($arData))) !== 0)
		{
			throw new LearnException ('EA_PARAMS: some fields not found',
				LearnException::EXC_ERR_GN_CHECK_PARAMS);
		}

		if ($arData['del'] !== 'Y')
			$arData['del'] = '';		// we can't use N' due to bug in CFile::SaveToDB();

		$arFileData = array($fieldNameInDB => $arData);
		
		$rc = CFile::SaveForDB($arFileData, $fieldNameInDB, 
				'learning');	// learning - is folder in /upload

		// This is workaround caused by bug in CFile::SaveToDB();
		if (($rc === false) && ($arData['name'] == '') && ($arData['del'] !== 'Y'))
		{
			// We are not deleting file and not uploading new, so return FALSE, what means no image's ID updates occured
			return (false);
		}

		if ( ($rc === false)
			|| ( ! isset($arFileData[$fieldNameInDB]) )
			|| ( ($arData['del'] !== 'Y') && ($arFileData[$fieldNameInDB] === false) )
		)
		{
			throw new LearnException ('EA_OTHER: file uploading error: ' . var_export ($rc, true) 
				. '; ' . var_export ($arFileData, true) . '; ' . var_export ($arData, true),
				LearnException::EXC_ERR_GN_FILE_UPLOAD);
		}

		// If file removed - return NULL
		if ($arFileData[$fieldNameInDB] === false)
			$fileId = NULL;
		else
			$fileId = intval ($arFileData[$fieldNameInDB]);

		return ($fileId);
	}

	/**
	 * @throws LearnException with errcode LearnException::EXC_ERR_GN_CHECK_PARAMS
	 */
	protected static function _CheckAndCanonizeFields ($arFieldsMap, $arFields, $access_level, $forUpdate = false)
	{
		if ( ! (is_int($access_level) && ($access_level >= 0)) )
		{
			throw new LearnException ('EA_LOGIC: wrong access level',
				LearnException::EXC_ERR_GN_CHECK_PARAMS);
		}

		// Check params
		$arFieldsNames = array_keys($arFields);
		foreach ($arFieldsNames as $fieldName)
		{
			// Skip checking user fields
			if (substr($fieldName, 0, 3) === 'UF_')
				continue;

			// Is field exists in DB?
			if ( ! array_key_exists ($fieldName, $arFieldsMap) )
			{
				throw new LearnException ('EA_PARAMS: ' . $fieldName,
					LearnException::EXC_ERR_GN_CHECK_PARAMS);
			}

			// Is access_level allowed by logic?
			if (($arFieldsMap[$fieldName]['access'] & $access_level) !== $access_level)
			{
				throw new LearnException ('EA_LOGIC: ACCESS TO FIELD "' . $fieldName . '" logically prohibited.',
					LearnException::EXC_ERR_GN_CHECK_PARAMS);
			}
		}

		// PREVIEW_TEXT_TYPE
		if ( ( ! $forUpdate ) && ( ! array_key_exists('PREVIEW_TEXT_TYPE', $arFields) ) )
			$arFields['PREVIEW_TEXT_TYPE'] = 'text';	// by default, for backward compatibility

		if ( ( ! $forUpdate ) || array_key_exists('PREVIEW_TEXT_TYPE', $arFields) )
		{
			if ( ! in_array ($arFields['PREVIEW_TEXT_TYPE'], array('text', 'html'), true) )
				throw new LearnException ('EA_PARAMS: PREVIEW_TEXT_TYPE', LearnException::EXC_ERR_GN_CHECK_PARAMS);
		}

		// DETAIL_TEXT_TYPE
		if ( ( ! $forUpdate ) && ( ! array_key_exists('DETAIL_TEXT_TYPE', $arFields) ) )
			$arFields['DETAIL_TEXT_TYPE'] = 'text';		// by default, for backward compatibility

		if ( ( ! $forUpdate ) || array_key_exists('DETAIL_TEXT_TYPE', $arFields) )
		{
			if ( ! in_array ($arFields['DETAIL_TEXT_TYPE'], array('text', 'html', 'file'), true) )
				throw new LearnException ('EA_PARAMS: DETAIL_TEXT_TYPE', LearnException::EXC_ERR_GN_CHECK_PARAMS);
		}

		// KEYWORDS
		if ( ! $forUpdate )
		{
			if (
				( ! array_key_exists('KEYWORDS', $arFields) )
				|| ($arFields['KEYWORDS'] === NULL)
			)
			{
				$arFields['KEYWORDS'] = '';
			}
		}
		else	// for update
		{
			if (
				array_key_exists('KEYWORDS', $arFields) 
				&& ($arFields['KEYWORDS'] === NULL)
			)
			{
				$arFields['KEYWORDS'] = '';
			}
		}

		// ACTIVE
		if (array_key_exists('ACTIVE', $arFields))
		{
			// canonize
			if ( in_array($arFields['ACTIVE'], array(true, false), true) )
			{
				if ($arFields['ACTIVE'])
					$arFields['ACTIVE'] = 'Y';
				else
					$arFields['ACTIVE'] = 'N';
			}
		}
		else
		{
			if ( ! $forUpdate )
				$arFields['ACTIVE'] = 'Y';	// by default, for backward compatibility
		}

		// ACTIVE - check admitted region
		if ( ( ! $forUpdate ) || array_key_exists('ACTIVE', $arFields) )
		{
			if ( ! in_array($arFields['ACTIVE'], array('Y', 'N'), true) )
			{
				throw new LearnException ('EA_PARAMS: ACTIVE is out of range',
					LearnException::EXC_ERR_GN_CHECK_PARAMS);
			}
		}

		// PREVIEW_PICTURE
		if (array_key_exists('PREVIEW_PICTURE', $arFields))
		{
			// remove this field, if nothing to do
			if (!is_array($arFields['PREVIEW_PICTURE']))
			{
				unset($arFields['PREVIEW_PICTURE']);
			}
			else if (
				(!array_key_exists('name', $arFields['PREVIEW_PICTURE']) || strlen($arFields['PREVIEW_PICTURE']['name']) == 0)
				&&
				(!array_key_exists('del', $arFields['PREVIEW_PICTURE']) || strlen($arFields['PREVIEW_PICTURE']['del']) == 0)
				&&
				(!isset($arFields['PREVIEW_PICTURE']['description']) || strlen($arFields['PREVIEW_PICTURE']['description']) == 0)
			)
			{
				unset($arFields['PREVIEW_PICTURE']);
			}
			else
			{
				// check structure
				$check = array_key_exists('name', $arFields['PREVIEW_PICTURE'])
					&& array_key_exists('size', $arFields['PREVIEW_PICTURE'])
					&& array_key_exists('tmp_name', $arFields['PREVIEW_PICTURE'])
					&& array_key_exists('type', $arFields['PREVIEW_PICTURE'])
					&& ( ( ! array_key_exists('del', $arFields['PREVIEW_PICTURE']) )
						|| in_array($arFields['PREVIEW_PICTURE']['del'], array('Y', 'N', NULL), true)
						);

				if ( ! $check )
				{
					throw new LearnException ('EA_PARAMS: <pre>' . var_export($arFields['PREVIEW_PICTURE'], true) 
						. '</pre>', LearnException::EXC_ERR_GN_CHECK_PARAMS);
				}

				$arFields['PREVIEW_PICTURE']['MODULE_ID'] = CLearnHelper::MODULE_ID;	// learning

				if ($arFields['PREVIEW_PICTURE']['del'] === NULL)
					$arFields['PREVIEW_PICTURE']['del'] = 'N';
			}
		}

		// DETAIL_PICTURE
		if (array_key_exists('DETAIL_PICTURE', $arFields))
		{
			// remove this field, if nothing to do
			if (!is_array($arFields['DETAIL_PICTURE']))
			{
				unset($arFields['DETAIL_PICTURE']);
			}
			elseif (
				(!array_key_exists('name', $arFields['DETAIL_PICTURE']) || strlen($arFields['DETAIL_PICTURE']['name']) == 0)
				&&
				(!array_key_exists('del', $arFields['DETAIL_PICTURE']) || strlen($arFields['DETAIL_PICTURE']['del']) == 0)
				&&
				(!isset($arFields['DETAIL_PICTURE']['description']) || strlen($arFields['DETAIL_PICTURE']['description']) == 0)
			)
			{
				unset($arFields['DETAIL_PICTURE']);
			}
			else
			{
				// check structure
				$check = array_key_exists('name', $arFields['DETAIL_PICTURE'])
					&& array_key_exists('size', $arFields['DETAIL_PICTURE'])
					&& array_key_exists('tmp_name', $arFields['DETAIL_PICTURE'])
					&& array_key_exists('type', $arFields['DETAIL_PICTURE'])
					&& ( ( ! array_key_exists('del', $arFields['DETAIL_PICTURE']) )
						|| in_array($arFields['DETAIL_PICTURE']['del'], array('Y', 'N', NULL), true)
						);

				if ( ! $check )
				{
					throw new LearnException ('EA_PARAMS: <pre>' . var_export($arFields['DETAIL_PICTURE'], true) 
						. '</pre>', LearnException::EXC_ERR_GN_CHECK_PARAMS);
				}

				$arFields['DETAIL_PICTURE']['MODULE_ID'] = CLearnHelper::MODULE_ID;	// learning

				if ($arFields['DETAIL_PICTURE']['del'] === NULL)
					$arFields['DETAIL_PICTURE']['del'] = 'N';
			}
		}

		return ($arFields);
	}

	protected static function _GetFieldsMap()
	{
		static $arFieldsMap = null;

		if ($arFieldsMap === null)
		{
			$arFieldsMap = array(
				'ID'                => array (
										'field'  => 'ID',
										'access' => self::SQL_SELECT),
				'TIMESTAMP_X'       => array (
										'field'  => 'TIMESTAMP_X',
										'access' => self::SQL_SELECT),
				'DATE_CREATE'       => array (
										'field'  => 'DATE_CREATE',
										'access' => self::SQL_SELECT),
				'CREATED_BY'        => array (
										'field'  => 'CREATED_BY',
										'access' => self::SQL_SELECT),
				'ACTIVE'            => array (
										'field'  => 'ACTIVE',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'NAME'              => array (
										'field'  => 'NAME',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'CODE'              => array (
										'field'  => 'CODE',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'KEYWORDS'          => array (
										'field'  => 'KEYWORDS',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'PREVIEW_PICTURE'   => array (
										'field'  => 'PREVIEW_PICTURE',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'PREVIEW_TEXT'      => array (
										'field'  => 'PREVIEW_TEXT',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'PREVIEW_TEXT_TYPE' => array (
										'field'  => 'PREVIEW_TEXT_TYPE',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'DETAIL_PICTURE'    => array (
										'field'  => 'DETAIL_PICTURE',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'DETAIL_TEXT'       => array (
										'field'  => 'DETAIL_TEXT',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'DETAIL_TEXT_TYPE'  => array (
										'field'  => 'DETAIL_TEXT_TYPE',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE),
				'LAUNCH'            => array (
										'field'  => 'LAUNCH',
										'access' => self::SQL_SELECT + self::SQL_INSERT + self::SQL_UPDATE)
				);
		}

		return ($arFieldsMap);
	}

	protected static function _GetReversedFieldsMap()
	{
		static $cache = false;

		if ($cache === false)
		{
			$fieldsMap = self::_GetFieldsMap();

			foreach ($fieldsMap as $propertyName => $arData)
			{
				$fieldNameInDB = $arData['field'];
				$cache[$fieldNameInDB] = array ('propertyName' => $propertyName, 'access' => $arData['access']);
			}
		}

		return ($cache);
	}
}
