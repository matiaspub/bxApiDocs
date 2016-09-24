<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/archive.php");

class CArchiver implements IBXArchive
{
	public $_strArchiveName = "";
	public $_bCompress = false;
	public $_strSeparator = " ";
	public $_dFile = 0;
	public $_arErrors = array();
	public $start_time = 0;
	public $max_exec_time = 0;
	public $file_pos = 0;
	public $stepped = false;

	private $add_path = "";
	private $remove_path = "";

	private $startFile = "";
	private $lastFile = "";
	private $step_time = 30;
	private $tempres = false;
	private $ReplaceExistentFiles = false;
	private $CheckBXPermissions = false;

	private static $bMbstring = false;

	public function __construct($strArchiveName, $bCompress = false, $start_time = -1, $max_exec_time = -1, $pos = 0, $stepped = false)
	{
		$this->io = CBXVirtualIo::GetInstance();

		$this->max_exec_time = $max_exec_time;
		$this->start_time = $start_time;
		$this->file_pos = $pos;
		$this->_bCompress = $bCompress;
		$this->stepped = $stepped;
		self::$bMbstring = extension_loaded("mbstring");

		if (!$bCompress)
		{
			if (@file_exists($this->io->GetPhysicalName($strArchiveName)))
			{
				if ($fp = @fopen($this->io->GetPhysicalName($strArchiveName), "rb"))
				{
					$data = fread($fp, 2);
					fclose($fp);
					if ($data == "\37\213")
					{
						$this->_bCompress = True;
					}
				}
			}
			else
			{
				if (substr($strArchiveName, -2) == 'gz')
				{
					$this->_bCompress = True;
				}
			}
		}
		else
		{
			$this->_bCompress = True;
		}

		$this->_strArchiveName = $strArchiveName;
		$this->_arErrors = array();
	}

	/**
	* Packs files and folders into archive
	* @param array $arFileList containing files and folders to be packed into archive
	* @param string $startFile - if specified then all files before it won't be packed during the traversing of $arFileList. Can be used for multi-step archivation
	* @return mixed false or 0 if error, 1 if success, 2 if the next step should be performed. Errors can be seen using GetErrors() method
	*/
	public function Pack($arFileList, $startFile = "")
	{

		$this->_arErrors = array();
		$this->startFile = $this->io->GetPhysicalName($startFile);

		$bNewArchive = True;
		if (file_exists($this->io->GetPhysicalName($this->_strArchiveName))
			&& is_file($this->io->GetPhysicalName($this->_strArchiveName)) && ($startFile != ""))
		{
			$bNewArchive = False;
		}

		if ($bNewArchive)
		{
			if (!$this->_openWrite())
				return false;
		}
		else
		{
			if (!$this->_openAppendFast())
				return false;
		}

		$res = false;
		$arFileList = &$this->_parseFileParams($arFileList);

		$arConvertedFileList = array();
		foreach ($arFileList as $fullpath)
			$arConvertedFileList[] = $this->io->GetPhysicalName($fullpath);

		unset($this->tempres);

		if (is_array($arFileList) && count($arFileList)>0)
			$res = $this->_processFiles($arConvertedFileList, $this->add_path, $this->remove_path, $this->startFile);

		if ($res !== false && $res !== "continue")
		{
			$this->_writeFooter();
		}

		$this->_close();

		if ($bNewArchive && ($res === false))
		{
			$this->_cleanFile();
		}

		//if packing is not completed, remember last file
		if ($res === 'continue')
		{
		// if (array_pop($arFileList) != $this->lastFile)
			$this->startFile = $this->io->GetLogicalName(array_pop($this->lastFile));
		}

		if ($res === false)
		{
			return IBXArchive::StatusError;

		}
		elseif ($res == true && $this->startFile == "")
		{
			return IBXArchive::StatusSuccess;

		}
		elseif ($res == true && $this->startFile != "")
		{
			return IBXArchive::StatusContinue;
			//call Pack() with $this->getStartFile() next time to continue
		}
		return null;
	}

	/**
	* Unpacks archive into specified folder
	* @param string $strPath - path to the directory to unpack archive to
	* @return mixed false if error, array if success. Errors can be seen using GetErrors() method
	*/
	public function Unpack($strPath)
	{
		$this->_arErrors = array();

		$v_list_detail = array();
		$arFileList = array();

		$strExtrType = "complete";

		@set_time_limit(0);
		if ($v_result = $this->_openRead())
		{
			$v_result = $this->_extractList($strPath, $v_list_detail, $strExtrType, $arFileList, '');
			$this->_close();
		}

		return $v_result;
	}

	/**
	* Called from the archive object it returns the name of the file for the next step during multi-step archivation. Call if Pack method returned 2
	* @return string path to file
	*/
	public function GetStartFile()
	{
		return $this->startFile;
	}

	private function _haveTime()
	{
		return microtime(true) - START_EXEC_TIME < $this->step_time;
	}

	private function _processFiles($arFileList, $strAddPath, $strRemovePath, $startFile = "")
	{

		$arHeaders = array();
		$strAddPath = str_replace("\\", "/", $strAddPath);
		$strRemovePath = str_replace("\\", "/", $strRemovePath);

		if (!$this->_dFile)
		{
			$this->_arErrors[] = array("ERR_DFILE", GetMessage("MAIN_ARCHIVE_ERR_DFILE"));
			return false;
		}

		if (!is_array($arFileList) || count($arFileList)<=0)
			return true;

		$j = -1;

		if (!isset($this->tempres))
			$this->tempres = "started";

		//files and directory scan
		while ($j++ < count($arFileList) && ($this->tempres === "started"))
		{
			$strFilename = $arFileList[$j];

			if ($this->_normalizePath($strFilename) == $this->_normalizePath($this->_strArchiveName))
				continue;

			if (strlen($strFilename)<=0)
				continue;

			if (!file_exists($strFilename))
			{
				$this->_arErrors[] = array("NO_FILE", str_replace("#FILE_NAME#", removeDocRoot($strFilename) , GetMessage("MAIN_ARCHIVE_NO_FILE")));
				continue;
			}
			//is file
			if (!@is_dir($strFilename))
			{
				$strFilename = str_replace("//", "/", $strFilename);

				//jumping to startFile, if it's specified
				if (strlen($this->startFile) != 0)
				{
					if ($strFilename != $this->startFile)
						//don't pack - jump to the next file
						continue;
					else
					{
						//if startFile is found, continue to pack files and folders without startFile, starting from next
						unset($this->startFile);
						continue;
					}
				}

				//check product permissions
				if ($this->CheckBXPermissions)
				{
					if (!CBXArchive::HasAccess($strFilename, true))
						continue;
				}

				if ($this->_haveTime())
				{
					if (!$this->_addFile($strFilename, $arHeaders, $this->add_path, $this->remove_path))
					{
						//$arErrors is filled in the _addFile method
						$this->tempres = false;
					}
					else
					{
						//remember last file
						$this->lastFile[] = $strFilename;
					}
				}
				else
				{
					$this->tempres = "continue";
					return $this->tempres;
				}
			}
			//if directory
			else
			{
				if (!($handle = opendir($strFilename)))
				{
					$this->_arErrors[] = array("NO_READ", str_replace("#DIR_NAME#", $strFilename, GetMessage("MAIN_ARCHIVE_ERR_DFILE")));
					continue;
				}

				if ($this->CheckBXPermissions)
				{
					if (!CBXArchive::HasAccess($strFilename, false))
						continue;
				}

				while (false !== ($dir = readdir($handle)))
				{
					if ($dir!="." && $dir!="..")
					{
						$arFileList_tmp = array();
						if ($strFilename != ".")
							$arFileList_tmp[] = $strFilename.'/'.$dir;
						else
							$arFileList_tmp[] = $dir;

						$this->_processFiles($arFileList_tmp, $strAddPath, $strRemovePath, $this->startFile);
					}
				}

				unset($arFileList_tmp);
				unset($dir);
				unset($handle);
			}
		}

		return $this->tempres;
	}

	/**
	* Lets the user define packing/unpacking options
	* @param array $arOptions an array with the options' names and their values
	* @return nothing
	*/
	public function SetOptions($arOptions)
	{
		if (array_key_exists("COMPRESS", $arOptions))
			$this->_bCompress = $arOptions["COMPRESS"] === true;

		//this is for old 'block' step-by-step writing in the addFile method
		if (array_key_exists("STEPPED", $arOptions))
			$this->stepped = $arOptions["STEPPED"] === true;

		if (array_key_exists("START_TIME", $arOptions))
			$this->start_time = floatval($arOptions["START_TIME"]);

		if (array_key_exists("MAX_EXEC_TIME", $arOptions))
			$this->max_exec_time = intval($arOptions["MAX_EXEC_TIME"]);

		if (array_key_exists("FILE_POS", $arOptions))
			$this->file_pos = intval($arOptions["FILE_POS"]);
		//end

		if (array_key_exists("ADD_PATH", $arOptions))
			$this->add_path = $this->io->GetPhysicalName(str_replace("\\", "/", strval($arOptions["ADD_PATH"])));

		if (array_key_exists("REMOVE_PATH", $arOptions))
			$this->remove_path = $this->io->GetPhysicalName(str_replace("\\", "/", strval($arOptions["REMOVE_PATH"])));

		//this is for 'file' step-by-step writing - used in the Pack method
		if (array_key_exists("STEP_TIME", $arOptions))
			$this->step_time = floatval($arOptions["STEP_TIME"]);

		if (array_key_exists("UNPACK_REPLACE", $arOptions))
			$this->ReplaceExistentFiles = $arOptions["UNPACK_REPLACE"] === true;

		if (array_key_exists("CHECK_PERMISSIONS", $arOptions))
			$this->CheckBXPermissions = $arOptions["CHECK_PERMISSIONS"] === true;
	}

	/**
	* Returns an array of packing/unpacking options and their current values
	* @return array
	*/
	public function GetOptions()
	{
		$arOptions = array(
			"COMPRESS"			=> $this->_bCompress,
			"STEPPED"			=> $this->stepped,
			"START_TIME" 		=> $this->start_time,
			"MAX_EXEC_TIME" 	=> $this->max_exec_time,
			"FILE_POS" 			=> $this->file_pos,
			"ADD_PATH"			=> $this->add_path,
			"REMOVE_PATH"		=> $this->remove_path,
			"STEP_TIME"			=> $this->step_time,
			"UNPACK_REPLACE"	=> $this->ReplaceExistentFiles,
			"CHECK_PERMISSIONS"	=> $this->CheckBXPermissions
			);

		return $arOptions;
	}

	/**
	* Archives files and folders
	* @param array $vFileList containing files and folders to be packed into archive
	* @param string|bool $strAddPath - if specified contains path to add to each packed file/folder
	* @param string|bool $strRemovePath - if specified contains path to remove from each packed file/folder
	* @return mixed 0 or false if error, array with the list of packed files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function Add($vFileList, $strAddPath = false, $strRemovePath = false)
	{
		$this->_arErrors = array();

		$bNewArchive = True;
		if (file_exists($this->io->GetPhysicalName($this->_strArchiveName))
			&& is_file($this->io->GetPhysicalName($this->_strArchiveName)))
		{
			$bNewArchive = False;
		}

		if ($bNewArchive)
		{
			if (!$this->_openWrite())
				return false;
		}
		else
		{
			if (!$this->_openAppend())
				return false;
		}
		$res = false;
		$arFileList = &$this->_parseFileParams($vFileList);

		if (is_array($arFileList) && count($arFileList)>0)
			$res = $this->_addFileList($arFileList, $strAddPath, $strRemovePath);

		if ($res)
			$this->_writeFooter();

		$this->_close();

		if ($bNewArchive && !$res)
		{
			$this->_cleanFile();
		}

		return $res;
	}

	/**
	* Adds file into archive
	* @param string $strFilename full path to file
	* @param string $strAddPath - if specified contains path to add to each packed file/folder
	* @param string $strRemovePath - if specified contains path to remove from each packed file/folder
	* @return mixed 0 or false if error, array with the list of packed files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function addFile($strFilename, $strAddPath, $strRemovePath)
	{
		$arHeaders = array();
		$strAddPath = str_replace("\\", "/", $strAddPath);
		$strRemovePath = str_replace("\\", "/", $strRemovePath);

		if (!$this->_dFile)
		{
			$this->_arErrors[] = array("ERR_DFILE", GetMessage("MAIN_ARCHIVE_ERR_DFILE"));
			return false;
		}

		if (strlen($strFilename)<=0)
			return false;

		if (!file_exists($this->io->GetPhysicalName($strFilename)))
		{
			$this->_arErrors[] = array("NO_FILE", "File '".$strFilename."' does not exist");
			return false;
		}

		if (!$this->_addFile($strFilename, $arHeaders, $strAddPath, $strRemovePath))
			return false;

		return true;
	}

	/**
	* Adds string as a file into archive
	* @param string $strFilename full path to file
	* @param string $strFileContent - file content
	* @return mixed 0 or false if error, array with the list of packed files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function addString($strFilename, $strFileContent)
	{
		$this->_arErrors = array();

		if (!file_exists($this->io->GetPhysicalName($this->_strArchiveName))
			|| !is_file($this->io->GetPhysicalName($this->_strArchiveName)))
		{
			if (!$this->_openWrite())
				return false;
			$this->_close();
		}

		if (!$this->_openAppend())
			return false;

		$res = $this->_addString($strFilename, $strFileContent);

		$this->_writeFooter();
		$this->_close();
		return $res;
	}

	/**
	* Extract files from the archive
	* @param string $strPath path where to extract
	* @param array|bool $vFileList if specified - array of files to be extracted, else - all files will be extracted
	* @return mixed 0 or false if error, array with the list of unpacked files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function extractFiles($strPath, $vFileList = false)
	{
		$this->_arErrors = array();

		$v_list_detail = array();

		$strExtrType = "complete";
		$arFileList = 0;
		if ($vFileList !== false)
		{
			$arFileList = &$this->_parseFileParams($vFileList);
			$strExtrType = "partial";
		}

		if ($v_result = $this->_openRead())
		{
			$v_result = $this->_extractList($strPath, $v_list_detail, $strExtrType, $arFileList, '');
			$this->_close();
		}

		return $v_result;
	}

	/**
	* Extract content of the archive
	* @return mixed 0 or false if error, array with the list of unpacked files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function extractContent()
	{
		$this->_arErrors = array();

		$arRes = array();

		if ($this->_openRead())
		{
			if (!$this->_extractList('', $arRes, "list", '', ''))
			{
				unset($arRes);
				$arRes = false;
			}
			$this->_close();
		}

		return $arRes;
	}

	/**
	* Returns an array containing error codes and messages. Call this method after Pack or Unpack
	* @return array
	*/
	public function GetErrors()
	{
		return $this->_arErrors;
	}

	private function _addFileList($arFileList, $strAddPath, $strRemovePath)
	{

		$v_result = true;
		$arHeaders = array();
		$strAddPath = str_replace("\\", "/", $strAddPath);
		$strRemovePath = str_replace("\\", "/", $strRemovePath);

		if (!$this->_dFile)
		{
			$this->_arErrors[] = array("ERR_DFILE", GetMessage("MAIN_ARCHIVE_ERR_DFILE"));
			return false;
		}

		if (!is_array($arFileList) || count($arFileList)<=0)
			return true;

		$fileListCount = count($arFileList);
		for ($j = 0; ($j<$fileListCount) && ($v_result); $j++)
		{

			$strFilename = $arFileList[$j];

			if ($strFilename == $this->_strArchiveName)
				continue;

			if (strlen($strFilename)<=0)
				continue;

			if (!file_exists($this->io->GetPhysicalName($strFilename)))
			{
				$this->_arErrors[] = array("NO_FILE", "File '".$strFilename."' does not exist");
				continue;
			}

			if (!$this->_addFile($strFilename, $arHeaders, $strAddPath, $strRemovePath))
				return false;

			if (@is_dir($this->io->GetPhysicalName($strFilename)))
			{
				if (!($handle = opendir($this->io->GetPhysicalName($strFilename))))
				{
					$this->_arErrors[] = array("NO_READ", str_replace("#DIR_NAME#", $strFilename, GetMessage("MAIN_ARCHIVE_ERR_DFILE")));
					continue;
				}

				while (false !== ($dir = readdir($handle)))
				{
					if ($dir!="." && $dir!="..")
					{
						$arFileList_tmp = array();
						if ($strFilename != ".")
							$arFileList_tmp[] = $strFilename.'/'.$dir;
						else
							$arFileList_tmp[] = $dir;

						$v_result = $this->_addFileList($arFileList_tmp, $strAddPath, $strRemovePath);
					}
				}

				unset($arFileList_tmp);
				unset($dir);
				unset($handle);
			}
		}

		return $v_result;
	}

	private function _addFile($strFilename, &$arHeaders, $strAddPath, $strRemovePath)
	{

		if (!$this->_dFile)
		{
			$this->_arErrors[] = array("ERR_DFILE", GetMessage("MAIN_ARCHIVE_ERR_DFILE"));
			return false;
		}

		if (strlen($strFilename)<=0)
		{
			$this->_arErrors[] = array("NO_FILENAME", GetMessage("MAIN_ARCHIVE_NO_FILENAME"));
			return false;
		}

		$strFilename = str_replace("\\", "/", $strFilename);
		$strFilename_stored = $strFilename;

		if (strcmp($strFilename, $strRemovePath) == 0)
			return true;

		if (strlen($strRemovePath)>0)
		{
			if (substr($strRemovePath, -1) != '/')
				$strRemovePath .= '/';

			if (substr($strFilename, 0, strlen($strRemovePath)) == $strRemovePath)
				$strFilename_stored = substr($strFilename, strlen($strRemovePath));
		}

		if (strlen($strAddPath)>0)
		{
			if (substr($strAddPath, -1) == '/')
				$strFilename_stored = $strAddPath.$strFilename_stored;
			else
				$strFilename_stored = $strAddPath.'/'.$strFilename_stored;
		}

		$strFilename_stored = $this->_normalizePath($strFilename_stored);

		if (is_file($strFilename))
		{
			if (($dfile = @fopen($strFilename, "rb")) == 0)
			{
				$this->_arErrors[] = array("ERR_OPEN", str_replace("#FILE_NAME#", removeDocRoot($strFilename), GetMessage("MAIN_ARCHIVE_ERR_OPEN")));
				return true;
			}

			$istime = ((getmicrotime() - $this->start_time) < round($this->max_exec_time)) || !$this->stepped;

			if ($istime)
			{

				if(($this->file_pos == 0))
				{
					if(!$this->_writeHeader($strFilename, $strFilename_stored))
						return false;
				}

				if(($this->file_pos > 0) && $this->stepped)
				{
					if ($this->_bCompress)
						@gzseek($dfile, $this->file_pos);
					else
						@fseek($dfile, $this->file_pos);
				}

				while ($istime && ($v_buffer = fread($dfile, 512)) != '')
				{
					$v_binary_data = pack("a512", "$v_buffer");
					$this->_writeBlock($v_binary_data);
					$istime = ((getmicrotime() - $this->start_time) < round($this->max_exec_time)) || !$this->stepped;
				}
			}

			if($istime && $this->stepped)
				$this->file_pos = 0;
			elseif($this->stepped)
			{
				if ($this->_bCompress)
				{
					$this->file_pos = @gztell($dfile);
				}
				else
					$this->file_pos = @ftell($dfile);
			}

			fclose($dfile);
		}
		elseif (!$this->_writeHeader($strFilename, $strFilename_stored))
			return false;

		return true;
	}

	/**
	* Returns the position of the file for the next step
	*/
	public function getFilePos()
	{
		return $this->file_pos;
	}

	private function _addString($strFilename, $strFileContent)
	{
		if (!$this->_dFile)
		{
			$this->_arErrors[] = array("ERR_DFILE", GetMessage("MAIN_ARCHIVE_ERR_DFILE"));
			return false;
		}

		if (strlen($strFilename)<=0)
		{
			$this->_arErrors[] = array("NO_FILENAME", GetMessage("MAIN_ARCHIVE_NO_FILENAME"));
			return false;
		}

		$strFilename = str_replace("\\", "/", $strFilename);

		if (!$this->_writeHeaderBlock($strFilename, (self::$bMbstring ? mb_strlen($strFileContent, "latin1") : strlen($strFileContent)), 0, 0, "", 0, 0))
			return false;

		$i = 0;
		while (($v_buffer = (self::$bMbstring ? mb_substr($strFileContent, (($i++)*512), 512, "latin1") : substr($strFileContent, (($i++)*512), 512))) != '')
		{
			$v_binary_data = pack("a512", $v_buffer);
			$this->_writeBlock($v_binary_data);
		}

		return true;
	}

	private function _extractList($p_path, &$p_list_detail, $p_mode, $p_file_list, $p_remove_path)
	{
		$v_result = true;
		$v_nb = 0;
		$v_extract_all = true;
		$v_listing = false;

		$p_path = str_replace("\\", "/", $p_path);
		$p_path = $this->io->GetPhysicalName($p_path);

		if ($p_path == ''
			|| (substr($p_path, 0, 1) != '/'
				&& substr($p_path, 0, 3) != "../"
				&& !strpos($p_path, ':')))
		{
			$p_path = "./".$p_path;
		}

		$p_remove_path = str_replace("\\", "/", $p_remove_path);
		if (($p_remove_path != '') && (substr($p_remove_path, -1) != '/'))
			$p_remove_path .= '/';

		$p_remove_path_size = strlen($p_remove_path);

		switch ($p_mode)
		{
			case "complete" :
			$v_extract_all = TRUE;
			$v_listing = FALSE;
			break;
			case "partial" :
			$v_extract_all = FALSE;
			$v_listing = FALSE;
			break;
			case "list" :
			$v_extract_all = FALSE;
			$v_listing = TRUE;
			break;
			default :
			$this->_arErrors[] = array("ERR_PARAM", str_replace("#EXTRACT_MODE#", $p_mode, GetMessage("MAIN_ARCHIVE_ERR_PARAM")));
			return false;
		}

		clearstatcache();

		while(self::$bMbstring ? mb_strlen($v_binary_data = $this->_readBlock(), "latin1") : strlen($v_binary_data = $this->_readBlock()) != 0)
		{
			$v_extract_file = FALSE;
			$v_extraction_stopped = 0;

			if (!$this->_readHeader($v_binary_data, $v_header))
				return false;

			if ($v_header['filename'] == '')
				continue;

			// ----- Look for long filename
			if ($v_header['typeflag'] == 'L')
			{
				if (!$this->_readLongHeader($v_header))
					return false;
			}
			if ((!$v_extract_all) && (is_array($p_file_list)))
			{
				// ----- By default no unzip if the file is not found
				$v_extract_file = false;
				$l = count($p_file_list);
				for ($i = 0; $i < $l; $i++)
				{
					// ----- Look if it is a directory
					if (substr($p_file_list[$i], -1) == '/')
					{
						// ----- Look if the directory is in the filename path
						if ((strlen($v_header['filename']) > strlen($p_file_list[$i]))
							&& (substr($v_header['filename'], 0, strlen($p_file_list[$i])) == $p_file_list[$i]))
						{
							$v_extract_file = TRUE;
							break;
						}
					}
					elseif ($p_file_list[$i] == $v_header['filename'])
					{
						// ----- It is a file, so compare the file names
						$v_extract_file = TRUE;
						break;
					}
				}
			}
			else
			{
				$v_extract_file = TRUE;
			}

			// ----- Look if this file need to be extracted
			if (($v_extract_file) && (!$v_listing))
			{
				if (($p_remove_path != '')
					&& (substr($v_header['filename'], 0, $p_remove_path_size) == $p_remove_path))
				{
					$v_header['filename'] = substr($v_header['filename'], $p_remove_path_size);
				}
				if (($p_path != './') && ($p_path != '/'))
				{
					while (substr($p_path, -1) == '/')
						$p_path = substr($p_path, 0, strlen($p_path)-1);

					if (substr($v_header['filename'], 0, 1) == '/')
						$v_header['filename'] = $p_path.$v_header['filename'];
					else
						$v_header['filename'] = $p_path.'/'.$v_header['filename'];
				}
				if (file_exists($v_header['filename']))
				{
					if ((@is_dir($v_header['filename'])) && ($v_header['typeflag'] == ''))
					{
						$this->_arErrors[] = array("DIR_EXISTS", str_replace("#FILE_NAME#", removeDocRoot($this->io->GetLogicalName($v_header['filename'])), GetMessage("MAIN_ARCHIVE_DIR_EXISTS")));
						return false;
					}

					if ((is_file($v_header['filename'])) && ($v_header['typeflag'] == "5"))
					{
						$this->_arErrors[] = array("FILE_EXISTS", str_replace("#FILE_NAME#", removeDocRoot($this->io->GetLogicalName($v_header['filename'])), GetMessage("MAIN_ARCHIVE_FILE_EXISTS")));
						return false;
					}
					if (!is_writeable($v_header['filename']))
					{
						$this->_arErrors[] = array("FILE_PERMS", str_replace("#FILE_NAME#", removeDocRoot($this->io->GetLogicalName($v_header['filename'])), GetMessage("MAIN_ARCHIVE_FILE_PERMS")));
						return false;
					}
				}
				elseif (($v_result = $this->_dirCheck(($v_header['typeflag'] == "5" ? $v_header['filename'] : dirname($v_header['filename'])))) != 1)
				{
					$this->_arErrors[] = array("NO_DIR", str_replace("#FILE_NAME#", removeDocRoot($this->io->GetLogicalName($v_header['filename'])), GetMessage("MAIN_ARCHIVE_NO_DIR")));
					return false;
				}

				if ($v_extract_file)
				{
					$logicalFilename = $this->io->GetLogicalName($v_header['filename']);

					if ((HasScriptExtension($v_header['filename'])
						|| IsFileUnsafe($v_header['filename'])
						|| !$this->io->ValidatePathString($logicalFilename)
						|| !$this->io->ValidateFilenameString(GetFileName($logicalFilename)))
						&& $this->CheckBXPermissions == true)
					{
						$this->_jumpBlock(ceil(($v_header['size']/512)));
					}
					//should we overwrite existent files?
					elseif ((file_exists($v_header['filename']) && $this->ReplaceExistentFiles) || !(file_exists($v_header['filename'])))
					{
						if ($v_header['typeflag'] == "5")
						{
							if (!@file_exists($v_header['filename']))
							{
								if (!@mkdir($v_header['filename'], BX_DIR_PERMISSIONS))
								{
									$this->_arErrors[] = array("ERR_CREATE_DIR", str_replace("#DIR_NAME#", removeDocRoot($this->io->GetLogicalName($v_header['filename'])), GetMessage("MAIN_ARCHIVE_ERR_CREATE_DIR")));
									return false;
								}
							}
						}
						else
						{
							if (($v_dest_file = @fopen($v_header['filename'], "wb")) == 0)
							{
								$this->_arErrors[] = array("ERR_CREATE_FILE", str_replace("#FILE_NAME#", removeDocRoot($this->io->GetLogicalName($v_header['filename'])), GetMessage("MAIN_ARCHIVE_ERR_CREATE_FILE")));
								return false;
							}
							else
							{
									$n = floor($v_header['size']/512);
									for ($i = 0; $i < $n; $i++)
									{
										$v_content = $this->_readBlock();
										fwrite($v_dest_file, $v_content, 512);
									}
									if (($v_header['size'] % 512) != 0)
									{
										$v_content = $this->_readBlock();
										fwrite($v_dest_file, $v_content, ($v_header['size'] % 512));
									}

									@fclose($v_dest_file);

									@chmod($v_header['filename'], BX_FILE_PERMISSIONS);
									@touch($v_header['filename'], $v_header['mtime']);
							}

							clearstatcache();
							if (filesize($v_header['filename']) != $v_header['size'])
							{
								$this->_arErrors[] = array("ERR_SIZE_CHECK", str_replace(array("#FILE_NAME#","#SIZE#","#EXP_SIZE#"), array(removeDocRoot($v_header['size']),filesize($v_header['filename']),$v_header['size']), GetMessage("MAIN_ARCHIVE_ERR_SIZE_CHECK")));
								return false;
							}
						}
					}
					else
					{
						$this->_jumpBlock(ceil(($v_header['size']/512)));
					}
				}
				else
				{
					$this->_jumpBlock(ceil(($v_header['size']/512)));
				}
			}
			else
			{
				$this->_jumpBlock(ceil(($v_header['size']/512)));
			}

			if ($v_listing || $v_extract_file || $v_extraction_stopped)
			{
				if (($v_file_dir = dirname($v_header['filename'])) == $v_header['filename'])
					$v_file_dir = '';
				if ((substr($v_header['filename'], 0, 1) == '/') && ($v_file_dir == ''))
					$v_file_dir = '/';

				$p_list_detail[$v_nb++] = $v_header;
			}
		}

		return true;
	}

	private function _writeHeader($strFilename, $strFilename_stored)
	{
		if (strlen($strFilename_stored)<=0)
			$strFilename_stored = $strFilename;

		$strFilename_ready = $this->_normalizePath($strFilename_stored);

		if ((self::$bMbstring ? mb_strlen($strFilename_ready, "latin1") : strlen($strFilename_ready)) > 99)
		{
			if (!$this->_writeLongHeader($strFilename_ready))
				return false;
		}

		$v_info = stat($strFilename);
		$v_uid = sprintf("%6s ", DecOct($v_info[4]));
		$v_gid = sprintf("%6s ", DecOct($v_info[5]));
		$v_perms = sprintf("%6s ", DecOct(fileperms($strFilename)));
		$v_mtime = sprintf("%11s", DecOct(filemtime($strFilename)));

		if (@is_dir($strFilename))
		{
			$v_typeflag = "5";
			$v_size = sprintf("%11s ", DecOct(0));
		}
		else
		{
			$v_typeflag = '';
			clearstatcache();
			$v_size = sprintf("%11s ", DecOct(filesize($strFilename)));
		}

		$v_linkname = '';
		$v_magic = '';
		$v_version = '';
		$v_uname = '';
		$v_gname = '';
		$v_devmajor = '';
		$v_devminor = '';
		$v_prefix = '';

		$v_binary_data_first = pack("a100a8a8a8a12A12", $strFilename_ready, $v_perms, $v_uid, $v_gid, $v_size, $v_mtime);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12", $v_typeflag, $v_linkname, $v_magic, $v_version, $v_uname, $v_gname, $v_devmajor, $v_devminor, $v_prefix, '');

		$v_checksum = 0;
		for ($i = 0; $i < 148; $i++)
			$v_checksum += ord((self::$bMbstring ? mb_substr($v_binary_data_first, $i, 1, "latin1") : substr($v_binary_data_first, $i, 1)));
		for ($i = 148; $i < 156; $i++)
			$v_checksum += ord(' ');
		for ($i = 156, $j = 0; $i < 512; $i++, $j++)
			$v_checksum += ord((self::$bMbstring ? mb_substr($v_binary_data_last, $j, 1, "latin1") : substr($v_binary_data_last, $j, 1)));

		$this->_writeBlock($v_binary_data_first, 148);

		$v_checksum = sprintf("%6s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		$this->_writeBlock($v_binary_data, 8);

		$this->_writeBlock($v_binary_data_last, 356);

		return true;
	}

	private function _writeLongHeader($strFilename)
	{
		$v_size = sprintf("%11s ", DecOct(strlen($strFilename)));
		$v_typeflag = 'L';
		$v_linkname = '';
		$v_magic = '';
		$v_version = '';
		$v_uname = '';
		$v_gname = '';
		$v_devmajor = '';
		$v_devminor = '';
		$v_prefix = '';
		$v_binary_data_first = pack("a100a8a8a8a12A12", '././@LongLink', 0, 0, 0, $v_size, 0);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12", $v_typeflag, $v_linkname, $v_magic, $v_version, $v_uname, $v_gname, $v_devmajor, $v_devminor, $v_prefix, '');

		$v_checksum = 0;
		for ($i = 0; $i < 148; $i++)
			$v_checksum += ord((self::$bMbstring ? mb_substr($v_binary_data_first, $i, 1, "latin1") : substr($v_binary_data_first, $i, 1)));

		for ($i = 148; $i < 156; $i++)
			$v_checksum += ord(' ');

		for ($i = 156, $j=0; $i < 512; $i++, $j++)
			$v_checksum += ord((self::$bMbstring ? mb_substr($v_binary_data_last, $j, 1, "latin1") : substr($v_binary_data_last, $j, 1)));

		$this->_writeBlock($v_binary_data_first, 148);

		$v_checksum = sprintf("%6s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		$this->_writeBlock($v_binary_data, 8);

		$this->_writeBlock($v_binary_data_last, 356);

		$p_filename = $this->_normalizePath($strFilename);

		$i = 0;
		while (($v_buffer = (self::$bMbstring ? mb_substr($p_filename, (($i++)*512), 512, "latin1") : substr($p_filename, (($i++)*512), 512))) != '')
		{
			$v_binary_data = pack("a512", "$v_buffer");
			$this->_writeBlock($v_binary_data);
		}
		return true;
	}

	private function _writeHeaderBlock($strFilename, $iSize, $p_mtime=0, $p_perms=0, $p_type='', $p_uid=0, $p_gid=0)
	{
		$strFilename = $this->_normalizePath($strFilename);

		if (strlen($strFilename) > 99)
		{
			if (!$this->_writeLongHeader($strFilename))
				return false;
		}

		if ($p_type == "5")
			$v_size = sprintf("%11s ", DecOct(0));
		else
			$v_size = sprintf("%11s ", DecOct($iSize));

		$v_uid = sprintf("%6s ", DecOct($p_uid));
		$v_gid = sprintf("%6s ", DecOct($p_gid));
		$v_perms = sprintf("%6s ", DecOct($p_perms));
		$v_mtime = sprintf("%11s", DecOct($p_mtime));
		$v_linkname = '';
		$v_magic = '';
		$v_version = '';
		$v_uname = '';
		$v_gname = '';
		$v_devmajor = '';
		$v_devminor = '';
		$v_prefix = '';

		$v_binary_data_first = pack("a100a8a8a8a12A12", $strFilename, $v_perms, $v_uid, $v_gid, $v_size, $v_mtime);
		$v_binary_data_last = pack("a1a100a6a2a32a32a8a8a155a12", $p_type, $v_linkname, $v_magic, $v_version, $v_uname, $v_gname, $v_devmajor, $v_devminor, $v_prefix, '');

		$v_checksum = 0;
		for ($i = 0; $i < 148; $i++)
			$v_checksum += ord((self::$bMbstring ? mb_substr($v_binary_data_first, $i, 1, "latin1") : substr($v_binary_data_first, $i, 1)));
		for ($i = 148; $i < 156; $i++)
			$v_checksum += ord(' ');
		for ($i = 156, $j = 0; $i < 512; $i++, $j++)
			$v_checksum += ord((self::$bMbstring ? mb_substr($v_binary_data_last, $j, 1, "latin1") : substr($v_binary_data_last, $j, 1)));

		$this->_writeBlock($v_binary_data_first, 148);

		$v_checksum = sprintf("%6s ", DecOct($v_checksum));
		$v_binary_data = pack("a8", $v_checksum);
		$this->_writeBlock($v_binary_data, 8);

		$this->_writeBlock($v_binary_data_last, 356);

		return true;
	}

	private function _readBlock()
	{
		$v_block = "";
		if (is_resource($this->_dFile))
		{
			if ($this->_bCompress)
				$v_block = @gzread($this->_dFile, 512);
			else
				$v_block = @fread($this->_dFile, 512);
		}
		return $v_block;
	}

	private function _readHeader($v_binary_data, &$v_header)
	{
		if ((self::$bMbstring ? mb_strlen($v_binary_data, "latin1") : strlen($v_binary_data)) == 0)
		{
			$v_header['filename'] = '';
			return true;
		}

		if ((self::$bMbstring ? mb_strlen($v_binary_data, "latin1") : strlen($v_binary_data)) != 512)
		{
			$v_header['filename'] = '';
			$this->_arErrors[] = array("INV_BLOCK_SIZE", str_replace("#BLOCK_SIZE#", self::$bMbstring ? mb_strlen($v_binary_data, "latin1") : strlen($v_binary_data), GetMessage("MAIN_ARCHIVE_INV_BLOCK_SIZE")));
			return false;
		}

		$v_checksum = 0;
		for ($i = 0; $i < 148; $i++)
			$v_checksum+=ord((self::$bMbstring ? mb_substr($v_binary_data, $i, 1, "latin1") : substr($v_binary_data, $i, 1)));
		for ($i = 148; $i < 156; $i++)
			$v_checksum += ord(' ');
		for ($i = 156; $i < 512; $i++)
			$v_checksum+=ord((self::$bMbstring ? mb_substr($v_binary_data, $i, 1, "latin1") : substr($v_binary_data, $i, 1)));

		//changed
		$v_data = unpack("a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1typeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a131prefix", $v_binary_data);

		$v_header['checksum'] = OctDec(trim($v_data['checksum']));
		if ($v_header['checksum'] != $v_checksum)
		{
			$v_header['filename'] = '';

			if (($v_checksum == 256) && ($v_header['checksum'] == 0))
				return true;

			$this->_arErrors[] = array("INV_BLOCK_CHECK", str_replace(array("#FILE_NAME#","#CHECKSUM#","#EXP_CHECKSUM#"), array(removeDocRoot($this->io->GetLogicalName($v_data['filename'])), $v_checksum, $v_header['checksum']), GetMessage("MAIN_ARCHIVE_INV_BLOCK_CHECK")));
			return false;
		}

		// ----- Extract the properties
		$v_header['filename'] = trim($v_data['prefix']."/".$v_data['filename']);
		$v_header['mode'] = OctDec(trim($v_data['mode']));
		$v_header['uid'] = OctDec(trim($v_data['uid']));
		$v_header['gid'] = OctDec(trim($v_data['gid']));
		$v_header['size'] = OctDec(trim($v_data['size']));
		$v_header['mtime'] = OctDec(trim($v_data['mtime']));
		if (($v_header['typeflag'] = $v_data['typeflag']) == "5")
			$v_header['size'] = 0;

		return true;
	}

	private function _readLongHeader(&$v_header)
	{
		$v_filename = '';

		$n = floor($v_header['size']/512);
		for ($i = 0; $i < $n; $i++)
		{
			$v_content = $this->_readBlock();
			$v_filename .= $v_content;
		}

		if (($tail = $v_header['size'] % 512) != 0)
		{
			$v_content = $this->_readBlock();
			$v_filename .= trim($v_content);
		}

		$v_binary_data = $this->_readBlock();

		if (!$this->_readHeader($v_binary_data, $v_header))
			return false;

		$v_header['filename'] = $v_filename;
		return true;
	}

	private function _jumpBlock($p_len = false)
	{
		if (is_resource($this->_dFile))
		{
			if ($p_len === false)
				$p_len = 1;

			if ($this->_bCompress)
				@gzseek($this->_dFile, @gztell($this->_dFile)+($p_len*512));
			else
				@fseek($this->_dFile, @ftell($this->_dFile)+($p_len*512));
		}
		return true;
	}

	private function &_parseFileParams(&$vFileList)
	{
		if (isset($vFileList) && is_array($vFileList))
			return $vFileList;

		if (isset($vFileList) && strlen($vFileList)>0)
		{
			if(strpos($vFileList, "\"")===0)
				return Array(Trim($vFileList, "\""));
			return explode($this->_strSeparator, $vFileList);
		}

		return array();
	}

	private function _openWrite()
	{
		$this->_checkDirPath($this->_strArchiveName);

		if ($this->_bCompress)
			$this->_dFile = @gzopen($this->io->GetPhysicalName($this->_strArchiveName), "wb9f");
		else
			$this->_dFile = @fopen($this->io->GetPhysicalName($this->_strArchiveName), "wb");

		if (!($this->_dFile))
		{
			$this->_arErrors[] = array("ERR_OPEN_WRITE", str_replace("#FILE_NAME#", removeDocRoot($this->_strArchiveName), GetMessage("MAIN_ARCHIVE_ERR_OPEN_WRITE")));
			return false;
		}
		return true;
	}

	private function _openAppendFast()
	{
		$this->_checkDirPath($this->_strArchiveName);

		if ($this->_bCompress)
			$this->_dFile = @gzopen($this->io->GetPhysicalName($this->_strArchiveName), "ab9f");
		else
			$this->_dFile = @fopen($this->io->GetPhysicalName($this->_strArchiveName), "ab");
		if (!($this->_dFile))
		{
			$this->_arErrors[] = array("ERR_OPEN_WRITE",str_replace("#FILE_NAME#", removeDocRoot($this->_strArchiveName), GetMessage("MAIN_ARCHIVE_ERR_OPEN_WRITE")));
			return false;
		}
		return true;
	}

	private function _openAppend()
	{
		if (filesize($this->io->GetPhysicalName($this->_strArchiveName)) == 0)
			return $this->_openWrite();

		$this->_checkDirPath($this->_strArchiveName);

		if ($this->_bCompress)
		{
			$this->_close();

			if (!@rename($this->io->GetPhysicalName($this->_strArchiveName), $this->io->GetPhysicalName($this->_strArchiveName.".tmp")))
			{
				$this->_arErrors[] = array("ERR_RENAME", str_replace(array("#FILE_NAME#","#FILE_NAME2#"), array(removeDocRoot($this->_strArchiveName), removeDocRoot($this->_strArchiveName.".tmp")), GetMessage("MAIN_ARCHIVE_ERR_RENAME")));
				return false;
			}

			$dTarArch_tmp = @gzopen($this->io->GetPhysicalName($this->_strArchiveName.".tmp"), "rb");
			if (!$dTarArch_tmp)
			{
				$this->_arErrors[] = array("ERR_OPEN", str_replace("#FILE_NAME#", removeDocRoot($this->_strArchiveName.".tmp"), GetMessage("MAIN_ARCHIVE_ERR_OPEN")));
				@rename($this->io->GetPhysicalName($this->_strArchiveName.".tmp"), $this->io->GetPhysicalName($this->_strArchiveName));
				return false;
			}

			if (!$this->_openWrite())
			{
				@rename($this->io->GetPhysicalName($this->_strArchiveName.".tmp"), $this->io->GetPhysicalName($this->_strArchiveName));
				return false;
			}

			$v_buffer = @gzread($dTarArch_tmp, 512);

			if (!@gzeof($dTarArch_tmp))
			{
				do
				{
					$v_binary_data = pack("a512", $v_buffer);
					$this->_writeBlock($v_binary_data);
					$v_buffer = @gzread($dTarArch_tmp, 512);
				}
				while (!@gzeof($dTarArch_tmp));
			}

			@gzclose($dTarArch_tmp);

			@unlink($this->io->GetPhysicalName($this->_strArchiveName.".tmp"));
		}
		else
		{
			if (!$this->_openReadWrite())
				return false;

			clearstatcache();
			$iSize = filesize($this->io->GetPhysicalName($this->_strArchiveName));
			fseek($this->_dFile, $iSize-512);
		}

		return true;
	}

	private function _openReadWrite()
	{
		if ($this->_bCompress)
			$this->_dFile = @gzopen($this->io->GetPhysicalName($this->_strArchiveName), "r+b");
		else
			$this->_dFile = @fopen($this->io->GetPhysicalName($this->_strArchiveName), "r+b");

		if (!$this->_dFile)
			return false;

		return true;
	}

	private function _openRead()
	{
		if ($this->_bCompress)
			$this->_dFile = @gzopen($this->io->GetPhysicalName($this->_strArchiveName), "rb");
		else
			$this->_dFile = @fopen($this->io->GetPhysicalName($this->_strArchiveName), "rb");

		if (!$this->_dFile)
		{
			$this->_arErrors[] = array("ERR_OPEN", str_replace("#FILE_NAME#", removeDocRoot($this->_strArchiveName), GetMessage("MAIN_ARCHIVE_ERR_OPEN")));

			return false;
		}

		return true;
	}

	private function _writeBlock($v_binary_data, $iLen = false)
	{
		if (is_resource($this->_dFile))
		{
			if ($iLen === false)
			{
				if ($this->_bCompress)
					@gzputs($this->_dFile, $v_binary_data);
				else
					@fputs($this->_dFile, $v_binary_data);
			}
			else
			{
				if ($this->_bCompress)
					@gzputs($this->_dFile, $v_binary_data, $iLen);
				else
					@fputs($this->_dFile, $v_binary_data, $iLen);
			}
		}
		return true;
	}

	private function _writeFooter()
	{
		if (is_resource($this->_dFile))
		{
			$v_binary_data = pack("a512", '');
			$this->_writeBlock($v_binary_data);
		}
		return true;
	}

	private function _cleanFile()
	{
		$this->_close();
		@unlink($this->io->GetPhysicalName($this->_strArchiveName));
		return true;
	}

	private function _close()
	{
		if (is_resource($this->_dFile))
		{
			if ($this->_bCompress)
				@gzclose($this->_dFile);
			else
				@fclose($this->_dFile);

			$this->_dFile = 0;
		}

		return true;
	}

	private function _normalizePath($strPath)
	{
		$strResult = "";
		if($strPath <> '')
		{
			$strPath = str_replace("\\", "/", $strPath);

			while(strpos($strPath, ".../") !== false)
				$strPath = str_replace(".../", "../", $strPath);

			$arPath = explode('/', $strPath);
			$nPath = count($arPath);
			for ($i = $nPath-1; $i >= 0; $i--)
			{
				if ($arPath[$i] == ".")
					;
				elseif ($arPath[$i] == "..")
					$i--;
				elseif (($arPath[$i] == '') && ($i != ($nPath-1)) && ($i != 0))
					;
				else
					$strResult = $arPath[$i].($i != ($nPath-1)? '/'.$strResult : '');
			}
		}
		return $strResult;
	}

	private function _checkDirPath($path, $bPermission=true)
	{
		$path = str_replace(array("\\", "//"), "/", $path);

		//remove file name
		if(substr($path, -1) != "/")
		{
			$p = strrpos($path, "/");
			$path = substr($path, 0, $p);
		}

		$path = rtrim($path, "/");

		if(!file_exists($this->io->GetPhysicalName($path)))
			return mkdir($this->io->GetPhysicalName($path), BX_DIR_PERMISSIONS, true);
		else
			return is_dir($this->io->GetPhysicalName($path));
	}

	private function _dirCheck($p_dir)
	{
		if ((@is_dir($p_dir)) || ($p_dir == ''))
			return true;

		$p_parent_dir = dirname($p_dir);

		if (($p_parent_dir != $p_dir) &&
			($p_parent_dir != '') &&
			(!$this->_dirCheck($p_parent_dir)))
			return false;

		if (!@mkdir($p_dir, BX_DIR_PERMISSIONS))
		{
			$this->_arErrors[] = array("CANT_CREATE_PATH", str_replace("#DIR_NAME#", $p_dir, GetMessage("MAIN_ARCHIVE_CANT_CREATE_PATH")));;
			return false;
		}

		return true;
	}

}
