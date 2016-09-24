<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/archive.php");

class CZip implements IBXArchive
{
	public $zipname = '';
	public $zipfile = 0;
	private $arErrors = array();
	private $fileSystemEncoding = "";
	private $startFile;
	private $arHeaders;

	//should be changed via SetOptions
	private $compress = true;
	private $remove_path = "";
	private $add_path = "";
	private $replaceExistentFiles = false;
	private $checkBXPermissions = false;

	const ReadBlockSize = 2048;

	private static $bMbstring = false;

	public function __construct($pzipname)
	{
		$this->io = CBXVirtualIo::GetInstance();
		$this->zipname = $this->_convertWinPath($pzipname, false);
		$this->step_time = 30;
		$this->arPackedFiles = array();
		$this->arPackedFilesData = array();
		$this->_errorReset();
		$this->fileSystemEncoding = $this->_getfileSystemEncoding();
		self::$bMbstring = extension_loaded("mbstring");

		return;
	}

	/**
	* Packs files and folders into archive
	* @param array $arFileList containing files and folders to be packed into archive
	* @param string $startFile - if specified then all files before it won't be packed during the traversing of $arFileList. Can be used for multi-step archivation
	* @return mixed 0 or false if error, 1 if success, 2 if the next step should be performed. Errors can be seen using GetErrors() method
	*/
	public function Pack($arFileList, $startFile = "")
	{
		$this->_errorReset();
		$this->startFile = $this->io->GetPhysicalName($startFile);
		$this->arPackedFiles = array();
		$this->arHeaders = array();
		$arCentralDirInfo = array();
		$zipfile_tmp = $zipname_tmp = '';

		$isNewArchive = true;
		if($startFile != "" && is_file($this->io->GetPhysicalName($this->zipname)))
			$isNewArchive = false;

		if ($isNewArchive)
		{
			if (!$this->_openFile("wb"))
				return false;
		}
		else
		{
			if (!$this->_openFile("rb"))
				return false;

			// read the central directory
			if (($res = $this->_readEndCentralDir($arCentralDirInfo)) != 1)
			{
				$this->_closeFile();
				return $res;
			}

			@rewind($this->zipfile);

			//creating tmp file
			$zipname_tmp = GetDirPath($this->zipname).uniqid('ziparc').'.tmp';
			if (($zipfile_tmp = @fopen($this->io->GetPhysicalName($zipname_tmp), 'wb')) == 0)
			{
				$this->_closeFile();
				$this->_errorLog("ERR_READ_TMP", str_replace("#FILE_NAME#", removeDocRoot($zipname_tmp), GetMessage("MAIN_ZIP_ERR_READ_TMP")));
				return $this->arErrors;
			}

			//copy files from the archive to the tmp file
			$size = $arCentralDirInfo['offset'];

			while ($size != 0)
			{
				$length = ($size < self::ReadBlockSize ? $size : self::ReadBlockSize);
				$buffer = fread($this->zipfile, $length);
				@fwrite($zipfile_tmp, $buffer, $length);
				$size -= $length;
			}

			//swapping file handle to use methods on the temporary file, not the real archive
			$tmp_id = $this->zipfile;
			$this->zipfile = $zipfile_tmp;
			$zipfile_tmp = $tmp_id;
		}

		//starting measuring start time from here (only packing time)
		// define("ZIP_START_TIME", microtime(true));
		unset($this->tempres);

		$arFileList = &$this->_parseFileParams($arFileList);

		$arConvertedFileList = array();
		foreach ($arFileList as $fullpath)
			$arConvertedFileList[] = $this->io->GetPhysicalName($fullpath);

		$packRes = null;
		if (is_array($arFileList) && count($arFileList)>0)
			$packRes = $this->_processFiles($arConvertedFileList, $this->add_path, $this->remove_path);

		if ($isNewArchive)
		{
			//writing Central Directory
			//save central directory offset
			$offset = @ftell($this->zipfile);

			//make central dir files header
			for ($i = 0, $counter = 0; $i<sizeof($this->arPackedFiles); $i++)
			{
				//write file header
				if ($this->arHeaders[$i]['status'] == 'ok')
				{
					if (($res = $this->_writeCentralFileHeader($this->arHeaders[$i])) != 1)
					{
						return $res;
					}
					$counter++;
				}

				$this->_convertHeader2FileInfo($this->arHeaders[$i], $this->arPackedFilesData[$i]);
			}

			$zip_comment = '';
			//calculate the size of the central header
			$size = @ftell($this->zipfile)-$offset;
			//make central dir footer
			if (($res = $this->_writeCentralHeader($counter, $size, $offset, $zip_comment)) != 1)
			{
				unset($this->arHeaders);
				return $res;
			}

		}
		else
		{
			//save the offset of the central dir
			$offset = @ftell($this->zipfile);

			//copy file headers block from the old archive
			$size = $arCentralDirInfo['size'];
			while ($size != 0)
			{
				$length = ($size < self::ReadBlockSize ? $size : self::ReadBlockSize);
				$buffer = @fread($zipfile_tmp, $length);
				@fwrite($this->zipfile, $buffer, $length);
				$size -= $length;
			}

			//add central dir files header
			for ($i = 0, $counter = 0; $i<sizeof($this->arHeaders); $i++)
			{
				//create the file header
				if ($this->arHeaders[$i]['status'] == 'ok')
				{
					if (($res = $this->_writeCentralFileHeader($this->arHeaders[$i]))!=1)
					{
						fclose($zipfile_tmp);
						$this->_closeFile();
						@unlink($this->io->GetPhysicalName($zipname_tmp));

						return $res;
					}
					$counter++;
				}
				//convert header to the usable format
				$this->_convertHeader2FileInfo($this->arHeaders[$i], $this->arPackedFilesData[$i]);
			}

			$zip_comment = '';

			//find the central header size
			$size = @ftell($this->zipfile)-$offset;

			//make central directory footer
			if (($res = $this->_writeCentralHeader($counter + $arCentralDirInfo['entries'], $size, $offset, $zip_comment)) != 1)
			{
				//clear file list
				unset($this->arHeaders);
				return $res;
			}

			//changing file handler back
			$tmp_id = $this->zipfile;
			$this->zipfile = $zipfile_tmp;
			$zipfile_tmp = $tmp_id;

			$this->_closeFile();
			@fclose($zipfile_tmp);
			// @unlink($this->zipname);

			//probably test the result @rename($zipname_tmp, $this->zipname);
			$this->_renameTmpFile($zipname_tmp, $this->zipname);
		}

		if ($isNewArchive && ($res === false))
			$this->_cleanFile();
		else
			$this->_closeFile();

		//if packing is not completed, remember last file
		if ($packRes === 'continue')
		{
			$this->startFile = $this->io->GetLogicalName(array_pop($this->arPackedFiles));
		}

		if ($packRes === false)
		{
			return IBXArchive::StatusError;
		}
		elseif ($packRes == true && $this->startFile == "")
		{
			return IBXArchive::StatusSuccess;
		}
		elseif ($packRes == true && $this->startFile != "")
		{
			//call Pack() with $this->GetStartFile() next time to continue
			return IBXArchive::StatusContinue;
		}
		return null;
	}

	private function _haveTime()
	{
		return microtime(true) - ZIP_START_TIME < $this->step_time;
	}

	private function _processFiles($arFileList, $addPath, $removePath)
	{
		$addPath = str_replace("\\", "/", $addPath);
		$removePath = str_replace("\\", "/", $removePath);

		if (!$this->zipfile)
		{
			$this->arErrors[] = array("ERR_DFILE", GetMessage("MAIN_ZIP_ERR_DFILE"));
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
			$filename = $arFileList[$j];

			if (strlen($filename)<=0)
				continue;

			if (!file_exists($filename))
			{
				$this->arErrors[] = array("ERR_NO_FILE", str_replace("#FILE_NAME#", $filename , GetMessage("MAIN_ZIP_ERR_NO_FILE")));
				continue;
			}

			//is file
			if (!@is_dir($filename))
			{
				$filename = str_replace("//", "/", $filename);

				//jumping to startFile, if it's specified
				if (strlen($this->startFile) != 0)
				{
					if ($filename != $this->startFile)
					{
						//don't pack - jump to the next file
						continue;
					}
					else
					{
						//if startFile is found, continue to pack files and folders without startFile, starting from next
						unset($this->startFile);
						continue;
					}
				}

				//check product permissions
				if ($this->checkBXPermissions)
				{
					if (!CBXArchive::HasAccess($filename, true))
						continue;
				}

				if ($this->_haveTime())
				{
					if (!$this->_addFile($filename, $arFileHeaders, $this->add_path, $this->remove_path, array(), $arP))
					{
						//$arErrors is filled in the _addFile method
						$this->tempres = false;
					}
					else
					{
						//remember last file
						$this->arPackedFiles[] = $filename;
						$this->arHeaders[] = $arFileHeaders;
					}
				}
				else
				{
					$this->tempres = 'continue';
					return $this->tempres;
				}
			}
			//if directory
			else
			{
				if (!($handle = opendir($filename)))
				{
					$this->arErrors[] = array("ERR_DIR_OPEN_FAIL", str_replace("#DIR_NAME#", $filename, GetMessage("MAIN_ZIP_ERR_DIR_OPEN_FAIL")));
					continue;
				}

				if ($this->checkBXPermissions)
				{
					if (!CBXArchive::HasAccess($filename, false))
						continue;
				}

				while (false !== ($dir = readdir($handle)))
				{
					if ($dir!= "." && $dir != "..")
					{
						$arFileList_tmp = array();
						if ($filename != ".")
							$arFileList_tmp[] = $filename.'/'.$dir;
						else
							$arFileList_tmp[] = $dir;

						$this->_processFiles($arFileList_tmp, $addPath, $removePath);
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
	* Called from the archive object it returns the name of the file for the next step during multi-step archivation. Call if Pack method returned 2
	* @return string path to file
	*/
	public function GetStartFile()
	{
		return $this->startFile;
	}

	/**
	* Unpacks archive into specified folder
	* @param string $strPath - path to the directory to unpack archive to
	* @return mixed 0 or false if error, 1 if success. Errors can be seen using GetErrors() method
	*/
	public function Unpack($strPath)
	{
		$this->SetOptions(array("ADD_PATH"=>$strPath));

		$arParams = array(
			"add_path"              => $this->add_path,
			"remove_path"           => $this->remove_path,
			"extract_as_string"     => false,
			"remove_all_path"       => false,
			"callback_pre_extract"  => "",
			"callback_post_extract" => "",
			"set_chmod"             => 0,
			"by_name"               => "",
			"by_index"              => "",
			"by_preg"               => ""
		);

		@set_time_limit(0);
		$result = $this->Extract($arParams);

		if ($result === 0)
		{
			return false;
		}
		//if there was no error, but we didn't extract any file ($this->replaceExistentFile = false)
		else if ($result == array())
		{
			return true;
		}
		else
		{
			return $result;
		}
	}

	/**
	* Lets the user define packing/unpacking options
	* @param array $arOptions an array with the options' names and their values
	* @return nothing
	*/
	public function SetOptions($arOptions)
	{
		if (array_key_exists("COMPRESS", $arOptions))
			$this->compress = $arOptions["COMPRESS"] === true;

		if (array_key_exists("ADD_PATH", $arOptions))
			$this->add_path = $this->io->GetPhysicalName(str_replace("\\", "/", strval($arOptions["ADD_PATH"])));

		if (array_key_exists("REMOVE_PATH", $arOptions))
			$this->remove_path = $this->io->GetPhysicalName(str_replace("\\", "/", strval($arOptions["REMOVE_PATH"])));

		if (array_key_exists("STEP_TIME", $arOptions))
			$this->step_time = floatval($arOptions["STEP_TIME"]);

		if (array_key_exists("UNPACK_REPLACE", $arOptions))
			$this->replaceExistentFiles = $arOptions["UNPACK_REPLACE"] === true;

		if (array_key_exists("CHECK_PERMISSIONS", $arOptions))
			$this->checkBXPermissions = $arOptions["CHECK_PERMISSIONS"] === true;
	}

	/**
	* Returns an array of packing/unpacking options and their current values
	* @return array
	*/
	public function GetOptions()
	{
		$arOptions = array(
			"COMPRESS"          => $this->compress,
			"ADD_PATH"          => $this->add_path,
			"REMOVE_PATH"       => $this->remove_path,
			"STEP_TIME"         => $this->step_time,
			"UNPACK_REPLACE"    => $this->replaceExistentFiles,
			"CHECK_PERMISSIONS" => $this->checkBXPermissions
			);

		return $arOptions;
	}

	/**
	* Returns an array containing error codes and messages. Call this method after Pack or Unpack
	* @return array
	*/
	public function GetErrors()
	{
		return $this->arErrors;
	}

	/**
	* Creates an archive
	* @param array $arFileList containing files and folders to be added to the archive
	* @param array|int $arParams an array of parameters
	* @return mixed 0 if error, array $arResultList with packed files if success
	*/
	public function Create($arFileList, $arParams = 0)
	{
		$this->_errorReset();

		if ($arParams === 0)
			$arParams = array();

		if ($this->_checkParams($arParams,
			array('no_compression' => false,
				'add_path' => "",
				'remove_path' => "",
				'remove_all_path' => false)) != 1)
		{
			return 0;
		}

		$arResultList = array();
		if (is_array($arFileList))
		{
			$res = $this->_createArchive($arFileList, $arResultList, $arParams);
		}
		else if (is_string($arFileList))
		{
			$arTmpList = explode(",", $arFileList);
			$res = $this->_createArchive($arTmpList, $arResultList, $arParams);
		}
		else
		{
			$this->_errorLog("ERR_PARAM", GetMessage("MAIN_ZIP_ERR_PARAM"));
			$res = "ERR_PARAM";
		}

		if ($res != 1)
		{
			return 0;
		}

		return $arResultList;
	}

	/**
	* Archives files and folders
	* @param array $arFileList containing files and folders to be packed into archive
	* @param array|int $arParams - if specified contains options to use for archivation
	* @return mixed 0 or false if error, array with the list of packed files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function Add($arFileList, $arParams = 0)
	{
		$this->_errorReset();

		if ($arParams === 0)
			$arParams = array();

		if ($this->_checkParams($arParams,
			array ('no_compression' => false,
					'add_path' => '',
					'remove_path' => '',
					'remove_all_path' => false,
					'callback_pre_add' => '',
					'callback_post_add' => '')) != 1)
		{
			return 0;
		}

		$arResultList = array();
		if (is_array($arFileList))
		{
			$res = $this->_addData($arFileList, $arResultList, $arParams);
		}
		else if (is_string($arFileList))
		{
			$arTmpList = explode(",", $arFileList);
			$res = $this->_addData($arTmpList, $arResultList, $arParams);
		}
		else
		{
			$this->_errorLog("ERR_PARAM_LIST", GetMessage("MAIN_ZIP_ERR_PARAM_LIST"));
			$res = "ERR_PARAM_LIST";
		}

		if ($res != 1)
		{
			return 0;
		}

		return $arResultList;
	}

	/**
	* Returns the list of files and folders in the archive
	* @return mixed 0 if error, array of results if success
	*/
	public function GetContent()
	{
		$this->_errorReset();

		if (!$this->_checkFormat())
			return(0);

		$arTmpList = array();
		if ($this->_getFileList($arTmpList) != 1)
		{
			unset($arTmpList);
			return(0);
		}

		return $arTmpList;
	}

	/**
	* Extracts archive content
	* @param array|int $arParams an array of parameters
	* @return mixed 0 or false if error, array of extracted files and folders if success. Errors can be seen using GetErrors() method
	*/
	public function Extract($arParams = 0)
	{
		$this->_errorReset();

		if (!$this->_checkFormat())
			return(0);

		if ($arParams === 0)
			$arParams = array();

		if ($this->_checkParams($arParams,
			array ('extract_as_string' => false,
					'add_path' => '',
					'remove_path' => '',
					'remove_all_path' => false,
					'callback_pre_extract' => '',
					'callback_post_extract' => '',
					'set_chmod' => 0,
					'by_name' => '',
					'by_index' => '',
					'by_preg' => '') ) != 1)
		{
			return 0;
		}

		$arTmpList = array();
		if ($this->_extractByRule($arTmpList, $arParams) != 1)
		{
			unset($arTmpList);
			return(0);
		}

		return $arTmpList;
	}

	/**
	* Deletes a file from the archive
	* @param array $arParams an rules defining which files should be deleted
	* @return mixed 0 if error, array $arResultList with deleted files if success
	*/
	public function Delete($arParams)
	{
		$this->_errorReset();

		if (!$this->_checkFormat())
			return(0);

		if ($this->_checkParams($arParams, array ('by_name' => '', 'by_index' => '', 'by_preg' => '') ) != 1)
			return 0;

		//at least one rule should be set
		if (($arParams['by_name'] == '') && ($arParams['by_index'] == '') && ($arParams['by_preg'] == ''))
		{
			$this->_errorLog("ERR_PARAM_RULE", GetMessage("MAIN_ZIP_ERR_PARAM_RULE"));
			return 0;
		}

		$arTmpList = array();
		if ($this->_deleteByRule($arTmpList, $arParams) != 1)
		{
			unset($arTmpList);
			return(0);
		}

		return $arTmpList;
	}

	/**
	* Returns archive properties
	* @return mixed 0 if error, array $arProperties if success
	*/
	public function GetProperties()
	{
		$this->_errorReset();

		if (!$this->_checkFormat())
			return(0);

		$arProperties            = array();
		$arProperties['comment'] = '';
		$arProperties['nb']      = 0;
		$arProperties['status']  = 'not_exist';

		if (@is_file($this->io->GetPhysicalName($this->zipname)))
		{
			if (($this->zipfile = @fopen($this->io->GetPhysicalName($this->zipname), 'rb')) == 0)
			{
				$this->_errorLog("ERR_READ", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_READ")));
				return 0;
			}

			//read central directory info
			$arCentralDirInfo = array();
			if (($res = $this->_readEndCentralDir($arCentralDirInfo)) != 1)
				return 0;

			$this->_closeFile();

			//set user attributes
			$arProperties['comment'] = $arCentralDirInfo['comment'];
			$arProperties['nb']      = $arCentralDirInfo['entries'];
			$arProperties['status']  = 'ok';
		}

		return $arProperties;
	}

	private function _checkFormat()
	{
		$res = true;

		$this->_errorReset();

		if (!is_file($this->io->GetPhysicalName($this->zipname)))
		{
			$this->_errorLog("ERR_MISSING_FILE", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_MISSING_FILE")));
			return(false);
		}

		if (!is_readable($this->io->GetPhysicalName($this->zipname)))
		{
			$this->_errorLog("ERR_READ", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_READ")));
			return(false);
		}

		//possible checks: magic code, central header, each file header
		return $res;
	}

	private function _createArchive($arFilesList, &$arResultList, &$arParams)
	{
		$addDir        = $arParams['add_path'];
		$removeDir     = $arParams['remove_path'];
		$removeAllDir = $arParams['remove_all_path'];

		if (($res = $this->_openFile('wb')) != 1)
			return $res;

		$res = $this->_addList($arFilesList, $arResultList, $addDir, $removeDir, $removeAllDir, $arParams);

		$this->_closeFile();

		return $res;
	}

	private function _addData($arFilesList, &$arResultList, &$arParams)
	{
		$addDir        = $arParams['add_path'];
		$removeDir     = $arParams['remove_path'];
		$removeAllDir = $arParams['remove_all_path'];

		if ((!is_file($this->io->GetPhysicalName($this->zipname))) || (filesize($this->io->GetPhysicalName($this->zipname)) == 0))
		{
			$res = $this->_createArchive($arFilesList, $arResultList, $arParams);
			return $res;
		}

		if (($res = $this->_openFile('rb')) != 1)
			return $res;

		$arCentralDirInfo = array();
		if (($res = $this->_readEndCentralDir($arCentralDirInfo)) != 1)
		{
			$this->_closeFile();
			return $res;
		}

		@rewind($this->zipfile);

		$zipname_tmp = GetDirPath($this->zipname).uniqid('ziparc').'.tmp';

		if (($zipfile_tmp = @fopen($this->io->GetPhysicalName($zipname_tmp), 'wb')) == 0)
		{
			$this->_closeFile();
			$this->_errorLog("ERR_READ_TMP", str_replace("#FILE_NAME#", removeDocRoot($zipname_tmp), GetMessage("MAIN_ZIP_ERR_READ_TMP")));
			return $this->arErrors;
		}

		//copy files from archive to the tmp file
		$size = $arCentralDirInfo['offset'];
		while ($size != 0)
		{
			$length = ($size < self::ReadBlockSize ? $size : self::ReadBlockSize);

			$buffer = fread($this->zipfile, $length);

			@fwrite($zipfile_tmp, $buffer, $length);
			$size -= $length;
		}

		//changing file handles to use methods on the temporary file, not the real archive
		$tmp_id = $this->zipfile;
		$this->zipfile = $zipfile_tmp;
		$zipfile_tmp = $tmp_id;

		$arHeaders = array();
		if (($res = $this->_addFileList($arFilesList, $arHeaders,
			$addDir, $removeDir,
			$removeAllDir, $arParams)) != 1)
		{
			fclose($zipfile_tmp);
			$this->_closeFile();
			@unlink($this->io->GetPhysicalName($zipname_tmp));

			return $res;
		}

		//save central dir offset
		$offset = @ftell($this->zipfile);

		//copy file headers block from the old archive
		$size = $arCentralDirInfo['size'];
		while ($size != 0)
		{
			$length = ($size < self::ReadBlockSize ? $size : self::ReadBlockSize);
			$buffer = @fread($zipfile_tmp, $length);
			@fwrite($this->zipfile, $buffer, $length);
			$size -= $length;
		}

		//write central dir files header
		for ($i = 0, $counter = 0; $i<sizeof($arHeaders); $i++)
		{
			//add the file header
			if ($arHeaders[$i]['status'] == 'ok')
			{
				if (($res = $this->_writeCentralFileHeader($arHeaders[$i]))!=1)
				{
					fclose($zipfile_tmp);
					$this->_closeFile();
					@unlink($this->io->GetPhysicalName($zipname_tmp));
					return $res;
				}
				$counter++;
			}

			$this->_convertHeader2FileInfo($arHeaders[$i], $arResultList[$i]);
		}

		$zip_comment = '';

		//size of the central header
		$size = @ftell($this->zipfile)-$offset;

		//make central dir footer
		if (($res = $this->_writeCentralHeader($counter +$arCentralDirInfo['entries'], $size, $offset, $zip_comment)) != 1)
		{
			//reset files list
			unset($arHeaders);
			return $res;
		}

		//change back file handler
		$tmp_id = $this->zipfile;
		$this->zipfile = $zipfile_tmp;
		$zipfile_tmp = $tmp_id;

		$this->_closeFile();
		@fclose($zipfile_tmp);
		@unlink($this->io->GetPhysicalName($this->zipname));
		//possibly test the result @rename($zipname_tmp, $this->zipname);
		$this->_renameTmpFile($zipname_tmp, $this->zipname);

		return $res;
	}

	private function _openFile($mode)
	{
		$res = 1;

		if ($this->zipfile != 0)
		{
			$this->_errorLog("ERR_OPEN", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_READ_OPEN")));
			return $this->arErrors;
		}

		$this->_checkDirPath($this->zipname);

		if (($this->zipfile = @fopen($this->io->GetPhysicalName($this->zipname), $mode)) == 0)
		{
			$this->_errorLog("ERR_READ_MODE", str_replace(array("#FILE_NAME#","#MODE#"), array(removeDocRoot($this->zipname), $mode), GetMessage("MAIN_ZIP_ERR_READ_MODE")));
			return $this->arErrors;
		}

		return $res;
	}

	private function _closeFile()
	{
		$res = 1;
		if ($this->zipfile != 0)
			@fclose($this->zipfile);
		$this->zipfile = 0;
		return $res;
	}

	private function _addList($arFilesList, &$arResultList, $addDir, $removeDir, $removeAllDir, &$arParams)
	{
		$arHeaders = array();
		if (($res = $this->_addFileList($arFilesList, $arHeaders, $addDir, $removeDir, $removeAllDir, $arParams)) != 1)
		{
			return $res;
		}

		//save the offset of the central dir
		$offset = @ftell($this->zipfile);

		//make central dir files header
		for ($i = 0, $counter = 0; $i<sizeof($arHeaders); $i++)
		{
			if ($arHeaders[$i]['status'] == 'ok')
			{
				if (($res = $this->_writeCentralFileHeader($arHeaders[$i])) != 1)
				{
					return $res;
				}
				$counter++;
			}
			$this->_convertHeader2FileInfo($arHeaders[$i], $arResultList[$i]);
		}

		$zip_comment = '';

		//the size of the central header
		$size = @ftell($this->zipfile)-$offset;

		//add central dir footer
		if (($res = $this->_writeCentralHeader($counter, $size, $offset, $zip_comment)) != 1)
		{
			unset($arHeaders);
			return $res;
		}
		return $res;
	}

	private function _addFileList($arFilesList, &$arResultList, $addDir, $removeDir, $removeAllDir, &$arParams)
	{
		$res = 1;
		$header = array();

		//save the current number of elements in the result list
		$count = sizeof($arResultList);
		$filesListCount = count($arFilesList);
		for ($j = 0; ($j<$filesListCount) && ($res == 1); $j++)
		{
			$filename = $this->_convertWinPath($arFilesList[$j], false);

			//if empty - skip
			if ($filename == "")
			{
				continue;
			}

			if (!file_exists($this->io->GetPhysicalName($filename)))
			{
				$this->_errorLog("ERR_MISSING_FILE", str_replace("#FILE_NAME#", removeDocRoot($filename), GetMessage("MAIN_ZIP_ERR_MISSING_FILE")));
				return $this->arErrors;
			}

			if ((is_file($this->io->GetPhysicalName($filename))) || ((is_dir($this->io->GetPhysicalName($filename))) && !$removeAllDir))
			{
				if (($res = $this->_addFile($filename, $header, $addDir, $removeDir, $removeAllDir, $arParams)) != 1)
				{
					return $res;
				}

				//save file info
				$arResultList[$count++] = $header;
			}

			if (is_dir($this->io->GetPhysicalName($filename)))
			{
				if ($filename != ".")
				{
					$path = $filename."/";
				}
				else
				{
					$path = "";
				}

				//read the folder for files and subfolders
				$hdir  = opendir($this->io->GetPhysicalName($filename));

				while ($hitem = readdir($hdir))
				{
					if($hitem == '.' || $hitem == '..')
					{
						continue;
					}

					if (is_file($this->io->GetPhysicalName($path.$hitem)))
					{
						if (($res = $this->_addFile($path.$hitem, $header, $addDir, $removeDir, $removeAllDir, $arParams)) != 1)
						{
							return $res;
						}
						//save file info
						$arResultList[$count++] = $header;
					}
					else
					{
						//should be ana array as a parameter
						$arTmpList[0] = $path.$hitem;

						$res = $this->_addFileList($arTmpList, $arResultList, $addDir, $removeDir, $removeAllDir, $arParams);
						$count = sizeof($arResultList);
					}
				}

				//unset variables for the recursive call
				unset($arTmpList);
				unset($hdir);
				unset($hitem);
			}
		}

		return $res;
	}

	private function _addFile($filename, &$arHeader, $addDir, $removeDir, $removeAllDir, &$arParams)
	{
		$res = 1;

		if ($filename == "")
		{
			$this->_errorLog("ERR_PARAM_LIST", GetMessage("MAIN_ZIP_ERR_PARAM_LIST"));
			return $this->arErrors;
		}

		//saved filename
		$storedFilename = $filename;

		//remove the path
		if ($removeAllDir)
		{
			$storedFilename = basename($filename);
		}
		else if ($removeDir != "")
		{

			if (substr($removeDir, -1) != '/')
			{
				$removeDir .= "/";
			}

			if ((substr($filename, 0, 2) == "./") || (substr($removeDir, 0, 2) == "./"))
			{
				if ((substr($filename, 0, 2) == "./") && (substr($removeDir, 0, 2) != "./"))
				{
					$removeDir = "./".$removeDir;
				}
				if ((substr($filename, 0, 2) != "./") && (substr($removeDir, 0, 2) == "./"))
				{
					$removeDir = substr($removeDir, 2);
				}
			}

			$incl = $this->_containsPath($removeDir, $filename);

			if ($incl > 0)
			{
				if ($incl == 2)
				{
					$storedFilename = "";
				}
				else
				{
					$storedFilename = substr($filename, strlen($removeDir));
				}
			}
		}

		if ($addDir != "")
		{
			if (substr($addDir, -1) == "/")
			{
				$storedFilename = $addDir.$storedFilename;
			}
			else
			{
				$storedFilename = $addDir."/".$storedFilename;
			}
		}

		//make the filename
		$storedFilename = $this->_reducePath($storedFilename);

		//save file properties
		clearstatcache();
		$arHeader['comment']           = '';
		$arHeader['comment_len']       = 0;
		$arHeader['compressed_size']   = 0;
		$arHeader['compression']       = 0;
		$arHeader['crc']               = 0;
		$arHeader['disk']              = 0;
		$arHeader['external']          = (is_file($filename) ? 0xFE49FFE0 : 0x41FF0010);
		$arHeader['extra']             = '';
		$arHeader['extra_len']         = 0;
		$arHeader['filename']          = \Bitrix\Main\Text\Encoding::convertEncoding($filename, $this->fileSystemEncoding, "cp866");
		$arHeader['filename_len']      = self::$bMbstring ? mb_strlen(\Bitrix\Main\Text\Encoding::convertEncoding($filename, $this->fileSystemEncoding, "cp866"), "latin1") : strlen(\Bitrix\Main\Text\Encoding::convertEncoding($filename, $this->fileSystemEncoding, "cp866"));
		$arHeader['flag']              = 0;
		$arHeader['index']             = -1;
		$arHeader['internal']          = 0;
		$arHeader['mtime']             = filemtime($filename);
		$arHeader['offset']            = 0;
		$arHeader['size']              = filesize($filename);
		$arHeader['status']            = 'ok';
		$arHeader['stored_filename']   = \Bitrix\Main\Text\Encoding::convertEncoding($storedFilename, $this->fileSystemEncoding, "cp866");
		$arHeader['version']           = 20;
		$arHeader['version_extracted'] = 10;

		//pre-add callback
		if ((isset($arParams['callback_pre_add'])) && ($arParams['callback_pre_add'] != ''))
		{
			//generate local information
			$arLocalHeader = array();
			$this->_convertHeader2FileInfo($arHeader, $arLocalHeader);

			//callback call
			eval('$res = '.$arParams['callback_pre_add'].'(\'callback_pre_add\', $arLocalHeader);');
			//if res == 0 change the file status
			if ($res == 0)
			{
				$arHeader['status'] = "skipped";
				$res = 1;
			}

			//update the info, only some fields can be modified
			if ($arHeader['stored_filename'] != $arLocalHeader['stored_filename'])
			{
				$arHeader['stored_filename'] = $this->_reducePath($arLocalHeader['stored_filename']);
			}
		}

		//if stored filename is empty - filter
		if ($arHeader['stored_filename'] == "")
		{
			$arHeader['status'] = "filtered";
		}

		//check path length
		if (strlen($arHeader['stored_filename']) > 0xFF)
		{
			$arHeader['status'] = 'filename_too_long';
		}

		//if no error
		if ($arHeader['status'] == 'ok')
		{
			if (is_file($filename))
			{
				//reading source
				if (($file = @fopen($filename, "rb")) == 0)
				{
					$this->_errorLog("ERR_READ", str_replace("#FILE_NAME#", removeDocRoot($filename), GetMessage("MAIN_ZIP_ERR_READ")));
					return $this->arErrors;
				}

				if ($arParams['no_compression'])
				{
					//reading file content
					$compressedContent = @fread($file, $arHeader['size']);
					//calculating crc
					$arHeader['crc'] = crc32($compressedContent);
				}
				else
				{
					//reading the file content
					$content = fread($file, $arHeader['size']);
					$arHeader['crc'] = crc32($content);
					//compress the file
					$compressedContent = gzdeflate($content);
				}

				//set header params
				$arHeader['compressed_size'] = self::$bMbstring ? mb_strlen($compressedContent, "latin1") : strlen($compressedContent);
				$arHeader['compression']     = 8;

				//generate header
				if (($res = $this->_writeFileHeader($arHeader)) != 1)
				{
					@fclose($file);
					return $res;
				}

				//writing the compressed content
				$binary_data = pack('a'.$arHeader['compressed_size'], $compressedContent);
				@fwrite($this->zipfile, $binary_data, $arHeader['compressed_size']);

				@fclose($file);
			}
			//if directory
			else
			{
				//set file properties
				$arHeader['filename'] .= '/';
				$arHeader['filename_len']++;
				$arHeader['size']     = 0;
				//folder value. to be checked
				$arHeader['external'] = 0x41FF0010;

				//generate header
				if (($res = $this->_writeFileHeader($arHeader)) != 1)
					return $res;
			}
		}

		//pre-add callack
		if ((isset($arParams['callback_post_add'])) && ($arParams['callback_post_add'] != ''))
		{
			//make local info
			$arLocalHeader = array();
			$this->_convertHeader2FileInfo($arHeader, $arLocalHeader);

			//callback call
			eval('$res = '.$arParams['callback_post_add'].'(\'callback_post_add\', $arLocalHeader);');

			if ($res == 0)
				$res = 1; //ignored
		}

		return $res;
	}

	private function _writeFileHeader(&$arHeader)
	{
		$res = 1;

		//to be checked: for(reset($arHeader); $key = key($arHeader); next($arHeader))

		//save offset position of the file
		$arHeader['offset'] = ftell($this->zipfile);

		//transform unix modification time to the dos mdate/mtime format
		$date  = getdate($arHeader['mtime']);
		$mtime = ($date['hours'] << 11) + ($date['minutes'] << 5) + $date['seconds'] / 2;
		$mdate = (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday'];

		// $arHeader["stored_filename"] = "12345678.gif";

		//pack data
		$binary_data = pack("VvvvvvVVVvv",
							0x04034b50,
							$arHeader['version'],
							$arHeader['flag'],
							$arHeader['compression'],
							$mtime,
							$mdate,
							$arHeader['crc'],
							$arHeader['compressed_size'],
							$arHeader['size'],
							self::$bMbstring ? mb_strlen($arHeader['stored_filename'], 'latin1') : strlen($arHeader['stored_filename']),
							$arHeader['extra_len']
							);

		//write first 148 bytes of the header in the archive
		fputs($this->zipfile, $binary_data, 30);

		//write the variable fields
		if (strlen($arHeader['stored_filename']) != 0)
			fputs($this->zipfile, $arHeader['stored_filename'], (self::$bMbstring ? mb_strlen($arHeader['stored_filename'], 'latin1') : strlen($arHeader['stored_filename'])));
		if ($arHeader['extra_len'] != 0)
			fputs($this->zipfile, $arHeader['extra'], $arHeader['extra_len']);

		return $res;
	}

	private function _writeCentralFileHeader(&$arHeader)
	{
		$res = 1;

		//to be checked: for(reset($arHeader); $key = key($arHeader); next($arHeader)) {}

		//convert unix mtime to dos mdate/mtime
		$date  = getdate($arHeader['mtime']);
		$mtime = ($date['hours'] << 11) + ($date['minutes'] << 5) + $date['seconds'] / 2;
		$mdate = (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday'];

		//pack data
		$binary_data = pack("VvvvvvvVVVvvvvvVV",
							0x02014b50,
							$arHeader['version'],
							$arHeader['version_extracted'],
							$arHeader['flag'],
							$arHeader['compression'],
							$mtime,
							$mdate,
							$arHeader['crc'],
							$arHeader['compressed_size'],
							$arHeader['size'],
							self::$bMbstring ? mb_strlen($arHeader['stored_filename'], 'latin1') : strlen($arHeader['stored_filename']),
							$arHeader['extra_len'],
							$arHeader['comment_len'],
							$arHeader['disk'],
							$arHeader['internal'],
							$arHeader['external'],
							$arHeader['offset']);

		//write 42 byt4es of the header in the zip file
		fputs($this->zipfile, $binary_data, 46);

		//variable fields
		if (strlen($arHeader['stored_filename']) != 0)
		{
			fputs($this->zipfile, $arHeader['stored_filename'], (self::$bMbstring ? mb_strlen($arHeader['stored_filename'], 'latin1') : strlen($arHeader['stored_filename'])));
		}
		if ($arHeader['extra_len'] != 0)
		{
			fputs($this->zipfile, $arHeader['extra'], $arHeader['extra_len']);
		}
		if ($arHeader['comment_len'] != 0)
		{
			fputs($this->zipfile, $arHeader['comment'], $arHeader['comment_len']);
		}

		return $res;
	}

	private function _writeCentralHeader($entriesNumber, $blockSize, $offset, $comment)
	{
		$res = 1;

		//packed data
		$binary_data = pack("VvvvvVVv", 0x06054b50, 0, 0, $entriesNumber, $entriesNumber, $blockSize, $offset, strlen($comment));

		//22 bytes of the header in the zip file
		fputs($this->zipfile, $binary_data, 22);

		//variable fields
		if (strlen($comment) != 0)
			fputs($this->zipfile, $comment, strlen($comment));

		return $res;
	}

	private function _getFileList(&$arFilesList)
	{
		if (($this->zipfile = @fopen($this->io->GetPhysicalName($this->zipname), 'rb')) == 0)
		{
			$this->_errorLog("ERR_READ", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_READ")));
			return $this->arErrors;
		}

		//get central directory information
		$arCentralDirInfo = array();
		if (($res = $this->_readEndCentralDir($arCentralDirInfo)) != 1)
			return $res;

		//go the the beginning of the central directory
		@rewind($this->zipfile);

		if (@fseek($this->zipfile, $arCentralDirInfo['offset']))
		{
			$this->_errorLog("ERR_INVALID_ARCHIVE_ZIP", GetMessage("MAIN_ZIP_ERR_INVALID_ARCHIVE_ZIP"));
			return $this->arErrors;
		}

		//read each entry
		for ($i = 0; $i<$arCentralDirInfo['entries']; $i++)
		{
			//read the file header
			if (($res = $this->_readCentralFileHeader($header)) != 1)
			{
				return $res;
			}
			$header['index'] = $i;

			//get only interesting attributes
			$this->_convertHeader2FileInfo($header, $arFilesList[$i]);
			unset($header);
		}

		$this->_closeFile();
		return $res;
	}

	private function _convertHeader2FileInfo($arHeader, &$arInfo)
	{
		$res = 1;
		$arInfo = array();

		//get necessary attributes
		$arInfo['filename']        = $arHeader['filename'];
		$arInfo['stored_filename'] = $arHeader['stored_filename'];
		$arInfo['size']            = $arHeader['size'];
		$arInfo['compressed_size'] = $arHeader['compressed_size'];
		$arInfo['mtime']           = $arHeader['mtime'];
		$arInfo['comment']         = $arHeader['comment'];
		$arInfo['folder']          = (($arHeader['external']&0x00000010)==0x00000010);
		$arInfo['index']           = $arHeader['index'];
		$arInfo['status']          = $arHeader['status'];

		return $res;
	}

	private function _extractByRule(&$arFileList, &$arParams)
	{
		$path           = $arParams['add_path'];
		$removePath     = $arParams['remove_path'];
		$removeAllPath  = $arParams['remove_all_path'];

		//path checking
		if (($path == "") || ((substr($path, 0, 1) != "/") && (substr($path, 0, 3) != "../") && (substr($path,1,2)!=":/")))
		{
			$path = "./".$path;
		}

		//reduce the path last (and duplicated) '/'
		if (($path != "./") && ($path != "/"))
		{
			// checking path end '/'
			while (substr($path, -1) == "/")
			{
				$path = substr($path, 0, strlen($path)-1);
			}
		}

		//path should end with the /
		if (($removePath != "") && (substr($removePath, -1) != '/'))
		{
			$removePath .= '/';
		}

		if (($res = $this->_openFile('rb')) != 1)
			return $res;

		//reading central directory informations
		$arCentralDirInfo = array();
		if (($res = $this->_readEndCentralDir($arCentralDirInfo)) != 1)
		{
			$this->_closeFile();
			return $res;
		}

		//starting from the beginning of the central directory
		$entryPos = $arCentralDirInfo['offset'];

		//reading each entry
		$j_start = 0;

		for ($i = 0, $extractedCounter = 0; $i<$arCentralDirInfo['entries']; $i++)
		{
			//reading next central directory record
			@rewind($this->zipfile);
			if (@fseek($this->zipfile, $entryPos))
			{
				$this->_closeFile();
				$this->_errorLog("ERR_INVALID_ARCHIVE_ZIP", GetMessage("MAIN_ZIP_ERR_MISSING_FILE"));
				return $this->arErrors;
			}

			//reading the file header
			$header = array();
			if (($res = $this->_readCentralFileHeader($header)) != 1)
			{
				$this->_closeFile();
				return $res;
			}

			//saving the index
			$header['index'] = $i;

			//saving the file pos
			$entryPos = ftell($this->zipfile);

			$extract = false;

			//look for the specific extract rules
			if ((isset($arParams['by_name'])) && ($arParams['by_name'] != 0))
			{
				//is filename in the list
				for ($j = 0; ($j<sizeof($arParams['by_name'])) && (!$extract); $j++)
				{
					//is directory
					if (substr($arParams['by_name'][$j], -1) == "/")
					{
						//is dir in the filename path
						if ((strlen($header['stored_filename']) > strlen($arParams['by_name'][$j]))
							&& (substr($header['stored_filename'], 0, strlen($arParams['by_name'][$j])) == $arParams['by_name'][$j]))
						{
							$extract = true;
						}
					}
					else if ($header['stored_filename'] == $arParams['by_name'][$j])
					{
						$extract = true;
					}
				}
			}
			else if ((isset($arParams['by_preg'])) && ($arParams['by_preg'] != ""))
			{
				//extract by preg rule
				if (preg_match($arParams['by_preg'], $header['stored_filename']))
				{
					$extract = true;
				}
			}
			else if ((isset($arParams['by_index'])) && ($arParams['by_index'] != 0))
			{
				//extract by index rule (if index is in the list)
				for ($j = $j_start; ($j<sizeof($arParams['by_index'])) && (!$extract); $j++)
				{
					if (($i>=$arParams['by_index'][$j]['start']) && ($i<=$arParams['by_index'][$j]['end']))
					{
						$extract = true;
					}

					if ($i>=$arParams['by_index'][$j]['end'])
					{
						$j_start = $j+1;
					}

					if ($arParams['by_index'][$j]['start']>$i)
					{
						break;
					}
				}
			}
			else
			{
				$extract = true;
			}

			// extract file
			if ($extract)
			{
				@rewind($this->zipfile);
				if (@fseek($this->zipfile, $header['offset']))
				{

					$this->_closeFile();
					$this->_errorLog("ERR_INVALID_ARCHIVE_ZIP", GetMessage("MAIN_ZIP_ERR_INVALID_ARCHIVE_ZIP"));
					return $this->arErrors;
				}
				//extract as a string
				if ($arParams['extract_as_string'])
				{
					//extract the file
					if (($res = $this->_extractFileAsString($header, $string)) != 1)
					{
						$this->_closeFile();
						return $res;
					}

					//get attributes
					if (($res = $this->_convertHeader2FileInfo($header, $arFileList[$extractedCounter])) != 1)
					{
						$this->_closeFile();
						return $res;
					}

					//set file content
					$arFileList[$extractedCounter]['content'] = $string;

					//next extracted file
					$extractedCounter++;
				}
				else
				{
					if (($res = $this->_extractFile($header, $path, $removePath, $removeAllPath, $arParams)) != 1)
					{
						$this->_closeFile();
						return $res;
					}

					//get attributes
					if (($res = $this->_convertHeader2FileInfo($header, $arFileList[$extractedCounter++])) != 1)
					{
						$this->_closeFile();
						return $res;
					}
				}
			}
		}
		$this->_closeFile();
		return $res;
	}

	private function _extractFile(&$arEntry, $path, $removePath, $removeAllPath, &$arParams)
	{
		if (($res = $this->_readFileHeader($header)) != 1)
			return $res;
		//to be checked: file header should be coherent with $arEntry info

		$arEntry["filename"] = \Bitrix\Main\Text\Encoding::convertEncoding($arEntry["filename"], "cp866", $this->fileSystemEncoding);
		$arEntry["stored_filename"] = \Bitrix\Main\Text\Encoding::convertEncoding($arEntry["stored_filename"], "cp866", $this->fileSystemEncoding);

		//protecting against ../ etc in file path
		//only absolute path should be in the $arEntry
		$arEntry['filename'] = _normalizePath($arEntry['filename']);
		$arEntry['stored_filename'] = _normalizePath($arEntry['stored_filename']);

		if ($removeAllPath == true)
		{
			$arEntry['filename'] = basename($arEntry['filename']);
		}
		else if ($removePath != "")
		{
			if ($this->_containsPath($removePath, $arEntry['filename']) == 2)
			{
				//change file status
				$arEntry['status'] = "filtered";
				return $res;
			}

			$removePath_size = strlen($removePath);
			if (substr($arEntry['filename'], 0, $removePath_size) == $removePath)
			{
				//remove path
				$arEntry['filename'] = substr($arEntry['filename'], $removePath_size);
			}
		}

		//making absolute path to the extracted file out of filename stored in the zip header and passed extracting path
		if ($path != '')
			$arEntry['filename'] = $path."/".$arEntry['filename'];

		//pre-extract callback
		if ((isset($arParams['callback_pre_extract']))
			&& ($arParams['callback_pre_extract'] != ''))
		{
			//generate local info
			$arLocalHeader = array();
			$this->_convertHeader2FileInfo($arEntry, $arLocalHeader);

			//callback call
			eval('$res = '.$arParams['callback_pre_extract'].'(\'callback_pre_extract\', $arLocalHeader);');

			//change file status
			if ($res == 0)
			{
				$arEntry['status'] = "skipped";
				$res = 1;
			}

			//update the info, only some fields can be modified
			$arEntry['filename'] = $arLocalHeader['filename'];
		}

		//check if extraction should be done
		if ($arEntry['status'] == 'ok')
		{
			$logicalFilename = $this->io->GetLogicalName($arEntry['filename']);
			if
				(((HasScriptExtension($arEntry['filename']))
				|| IsFileUnsafe($arEntry['filename'])
				|| !$this->io->ValidatePathString($logicalFilename)
				|| !$this->io->ValidateFilenameString(GetFileName($logicalFilename)))
				&& $this->checkBXPermissions == true)
			{
					$arEntry['status'] = "no_permissions";
			}
			else
			{
				//if the file exists, change status
				if (file_exists($arEntry['filename']))
				{
					if (is_dir($arEntry['filename']))
					{
						$arEntry['status'] = "already_a_directory";
					}
					else if (!is_writeable($arEntry['filename']))
					{
						$arEntry['status'] = "write_protected";
					}
					else if ((filemtime($arEntry['filename']) > $arEntry['mtime']) && (!$this->replaceExistentFiles))
					{
						$arEntry['status'] = "newer_exist";
					}
				}
				else
				{
					//check the directory availability and create it if necessary
					if ((($arEntry['external']&0x00000010)==0x00000010) || (substr($arEntry['filename'], -1) == '/'))
					{
						$checkDir = $arEntry['filename'];
					}
					else if (!strstr($arEntry['filename'], "/"))
					{
						$checkDir = "";
					}
					else
					{
						$checkDir = dirname($arEntry['filename']);
					}

					if (($res = $this->_checkDir($checkDir, (($arEntry['external']&0x00000010)==0x00000010))) != 1)
					{
						//change file status
						$arEntry['status'] = "path_creation_fail";

						//return $res;
						$res = 1;
					}
				}
			}
		}

		//check if extraction should be done
		if ($arEntry['status'] == 'ok')
		{
			//if not a folder - extract
			if (!(($arEntry['external']&0x00000010)==0x00000010))
			{
				//if zip file with 0 compression
				if (($arEntry['compression'] == 0) && ($arEntry['compressed_size'] == $arEntry['size']))
				{
					if (($destFile = @fopen($arEntry['filename'], 'wb')) == 0)
					{
						$arEntry['status'] = "write_error";
						return $res;
					}

					//reading the fileby by self::ReadBlockSize octets blocks
					$size = $arEntry['compressed_size'];
					while ($size != 0)
					{
						$length = ($size < self::ReadBlockSize ? $size : self::ReadBlockSize);
						$buffer = fread($this->zipfile, $length);
						$binary_data = pack('a'.$length, $buffer);
						@fwrite($destFile, $binary_data, $length);
						$size -= $length;
					}

					//close the destination file
					fclose($destFile);

					//changing file modification time
					touch($arEntry['filename'], $arEntry['mtime']);
				}
				else
				{
					if (($destFile = @fopen($arEntry['filename'], 'wb')) == 0)
					{
						//change file status
						$arEntry['status'] = "write_error";
						return $res;
					}

					//read the compressed file in a buffer (one shot)
					$buffer = @fread($this->zipfile, $arEntry['compressed_size']);

					//decompress the file
					$fileContent = gzinflate($buffer);
					unset($buffer);

					//write uncompressed data
					@fwrite($destFile, $fileContent, $arEntry['size']);
					unset($fileContent);
					@fclose($destFile);
					touch($arEntry['filename'], $arEntry['mtime']);
				}

				if ((isset($arParams['set_chmod'])) && ($arParams['set_chmod'] != 0))
				{
					chmod($arEntry['filename'], $arParams['set_chmod']);
				}
			}
		}

		//post-extract callback
		if ((isset($arParams['callback_post_extract'])) && ($arParams['callback_post_extract'] != ''))
		{
			//make local info
			$arLocalHeader = array();
			$this->_convertHeader2FileInfo($arEntry, $arLocalHeader);

			//callback call
			eval('$res = '.$arParams['callback_post_extract'].'(\'callback_post_extract\', $arLocalHeader);');
		}
		return $res;
	}

	private function _extractFileAsString(&$arEntry, &$string)
	{
		//reading file header
		$header = array();
		if (($res = $this->_readFileHeader($header)) != 1)
			return $res;

		//to be checked: file header should be coherent with the $arEntry info

		//extract if not a folder
		if (!(($arEntry['external']&0x00000010)==0x00000010))
		{
			//if not compressed
			if ($arEntry['compressed_size'] == $arEntry['size'])
			{
				$string = fread($this->zipfile, $arEntry['compressed_size']);
			}
			else
			{
				$data = fread($this->zipfile, $arEntry['compressed_size']);
				$string = gzinflate($data);
			}
		}
		else
		{
			$this->_errorLog("ERR_EXTRACT", GetMessage("MAIN_ZIP_ERR_EXTRACT"));
			return $this->arErrors;
		}

		return $res;
	}

	private function _readFileHeader(&$arHeader)
	{
		$res = 1;

		//read 4 bytes signature
		$binary_data = @fread($this->zipfile, 4);

		$data = unpack('Vid', $binary_data);

		//check signature
		if ($data['id'] != 0x04034b50)
		{
			$this->_errorLog("ERR_BAD_FORMAT", GetMessage("MAIN_ZIP_ERR_STRUCT"));
			return $this->arErrors;
		}

		//reading first 42 bytes of the header
		$binary_data = fread($this->zipfile, 26);

		//look for invalid block size
		if ((self::$bMbstring? mb_strlen($binary_data, "latin1") : strlen($binary_data)) != 26)
		{
			$arHeader['filename'] = "";
			$arHeader['status']   = "invalid_header";

			$this->_errorLog("ERR_BAD_BLOCK_SIZE", str_replace("#BLOCK_SIZE#", $binary_data, GetMessage("MAIN_ZIP_ERR_BLOCK_SIZE")));
			return $this->arErrors;
		}

		//extract values
		$data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $binary_data);

		$arHeader['filename'] = fread($this->zipfile, $data['filename_len']);

		//extra fields
		if ($data['extra_len'] != 0)
			$arHeader['extra'] = fread($this->zipfile, $data['extra_len']);
		else
			$arHeader['extra'] = '';

		//extract properties
		$arHeader['compression']     = $data['compression'];
		$arHeader['size']            = $data['size'];
		$arHeader['compressed_size'] = $data['compressed_size'];
		$arHeader['crc']             = $data['crc'];
		$arHeader['flag']            = $data['flag'];

		//save date in unix format
		$arHeader['mdate'] = $data['mdate'];
		$arHeader['mtime'] = $data['mtime'];

		if ($arHeader['mdate'] && $arHeader['mtime'])
		{
			//extract time
			$hour    = ($arHeader['mtime'] & 0xF800) >> 11;
			$min  = ($arHeader['mtime'] & 0x07E0) >> 5;
			$sec = ($arHeader['mtime'] & 0x001F)*2;

			//...and date
			$year  = (($arHeader['mdate'] & 0xFE00) >> 9) + 1980;
			$month = ($arHeader['mdate'] & 0x01E0) >> 5;
			$day   = $arHeader['mdate'] & 0x001F;

			//unix date format
			$arHeader['mtime'] = mktime($hour, $min, $sec, $month, $day, $year);

		}
		else
		{
			$arHeader['mtime'] = time();
		}

		//to be checked: for(reset($data); $key = key($data); next($data)) { }
		$arHeader['stored_filename'] = $arHeader['filename'];
		$arHeader['status'] = "ok";

		return $res;
	}

	private function _readCentralFileHeader(&$arHeader)
	{
		$res = 1;

		//reading 4 bytes signature
		$binary_data = @fread($this->zipfile, 4);

		$data = unpack('Vid', $binary_data);

		//checking signature
		if ($data['id'] != 0x02014b50)
		{
			$this->_errorLog("ERR_BAD_FORMAT", GetMessage("MAIN_ZIP_ERR_STRUCT"));
			return $this->arErrors;
		}

		//reading first header 42 bytes
		$binary_data = fread($this->zipfile, 42);

		//if block size is not valid
		if ((self::$bMbstring? mb_strlen($binary_data, "latin1") : strlen($binary_data)) != 42)
		{
			$arHeader['filename'] = "";
			$arHeader['status']   = "invalid_header";

			$this->_errorLog("ERR_BAD_BLOCK_SIZE", str_replace("#SIZE#", $binary_data, GetMessage("MAIN_ZIP_ERR_BLOCK_SIZE")));
			return $this->arErrors;
		}

		//extract values
		$arHeader = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $binary_data);

		//getting filename
		if ($arHeader['filename_len'] != 0)
			$arHeader['filename'] = fread($this->zipfile, $arHeader['filename_len']);
		else
			$arHeader['filename'] = '';

		//getting extra
		if ($arHeader['extra_len'] != 0)
			$arHeader['extra'] = fread($this->zipfile, $arHeader['extra_len']);
		else
			$arHeader['extra'] = '';

		//getting comments
		if ($arHeader['comment_len'] != 0)
			$arHeader['comment'] = fread($this->zipfile, $arHeader['comment_len']);
		else
			$arHeader['comment'] = '';

		//extracting properties

		//saving date in unix format
		if ($arHeader['mdate'] && $arHeader['mtime'])
		{
			//extracting time
			$hour    = ($arHeader['mtime'] & 0xF800) >> 11;
			$min  = ($arHeader['mtime'] & 0x07E0) >> 5;
			$sec = ($arHeader['mtime'] & 0x001F)*2;

			//...and date
			$year  = (($arHeader['mdate'] & 0xFE00) >> 9) + 1980;
			$month = ($arHeader['mdate'] & 0x01E0) >> 5;
			$day   = $arHeader['mdate'] & 0x001F;

			//in unix date format
			$arHeader['mtime'] = mktime($hour, $min, $sec, $month, $day, $year);
		}
		else
		{
			$arHeader['mtime'] = time();
		}

		//set stored filename
		$arHeader['stored_filename'] = $arHeader['filename'];

		//default status is 'ok'
		$arHeader['status'] = 'ok';

		//is directory?
		if (substr($arHeader['filename'], -1) == '/')
		{
			$arHeader['external'] = 0x41FF0010;
		}

		return $res;
	}

	private function _readEndCentralDir(&$arCentralDir)
	{
		$res = 1;

		//going to the end of the file
		$size = filesize($this->io->GetPhysicalName($this->zipname));
		@fseek($this->zipfile, $size);

		if (@ftell($this->zipfile) != $size)
		{
			$this->_errorLog("ERR_ARC_END", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_ARC_END")));
			return $this->arErrors;
		}

		//if archive is without comments (usually), the end of central dir is at 22 bytes of the file end
		$isFound = 0;
		$pos = 0;

		if ($size > 26)
		{
			@fseek($this->zipfile, $size-22);

			if (($pos = @ftell($this->zipfile)) != ($size-22))
			{
				$this->_errorLog("ERR_ARC_MID", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_ARC_MID")));
				return $this->arErrors;
			}

			//read 4 bytes
			$binary_data = @fread($this->zipfile, 4);
			$data = unpack('Vid', $binary_data);

			//signature check
			if ($data['id'] == 0x06054b50)
				$isFound = 1;

			$pos = ftell($this->zipfile);
		}

		//going back to the max possible size of the Central Dir End Record
		if (!$isFound)
		{
			$maxSize = 65557; // 0xFFFF + 22;

			if ($maxSize > $size)
				$maxSize = $size;

			@fseek($this->zipfile, $size - $maxSize);

			if (@ftell($this->zipfile) != ($size - $maxSize))
			{
				$this->_errorLog("ERR_ARC_MID", str_replace("#FILE_NAME#", removeDocRoot($this->zipname), GetMessage("MAIN_ZIP_ERR_ARC_MID")));
				return $this->arErrors;
			}

			//reading byte per byte to find the signature
			$pos   = ftell($this->zipfile);
			$bytes = 0x00000000;
			while ($pos < $size)
			{
				//reading 1 byte
				$byte = @fread($this->zipfile, 1);

				//adding the byte
				$bytes = ($bytes << 8) | ord($byte);

				//compare bytes
				if ($bytes == 0x504b0506)
				{
					$pos++;
					break;
				}

				$pos++;
			}

			//if end of the central dir is not found
			if ($pos == $size)
			{
				$this->_errorLog("ERR_ARC_MID_END", GetMessage("MAIN_ZIP_ERR_ARC_MID_END"));
				return $this->arErrors;
			}
		}

		//reading first 18 bytes of the header
		$binary_data = fread($this->zipfile, 18);

		//if block size is not valid
		if ((self::$bMbstring? mb_strlen($binary_data, "latin1") : strlen($binary_data)) != 18)
		{
			$this->_errorLog("ERR_ARC_END_SIZE", str_replace("#SIZE#", strlen($binary_data), GetMessage("MAIN_ZIP_ERR_ARC_END_SIZE")));
			return $this->arErrors;
		}

		//extracting values
		$data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $binary_data);

		//checking global size
		if (($pos + $data['comment_size'] + 18) != $size)
		{
			$this->_errorLog("ERR_SIGNATURE", GetMessage("MAIN_ZIP_ERR_SIGNATURE"));
			return $this->arErrors;
		}

		//reading comments
		if ($data['comment_size'] != 0)
			$arCentralDir['comment'] = fread($this->zipfile, $data['comment_size']);
		else
			$arCentralDir['comment'] = '';

		$arCentralDir['entries']      = $data['entries'];
		$arCentralDir['disk_entries'] = $data['disk_entries'];
		$arCentralDir['offset']       = $data['offset'];
		$arCentralDir['size']         = $data['size'];
		$arCentralDir['disk']         = $data['disk'];
		$arCentralDir['disk_start']   = $data['disk_start'];

		return $res;
	}

	private function _deleteByRule(&$arResultList, &$arParams)
	{
		$arCentralDirInfo = array();
		$arHeaders = array();

		if (($res = $this->_openFile('rb')) != 1)
			return $res;

		if (($res = $this->_readEndCentralDir($arCentralDirInfo)) != 1)
		{
			$this->_closeFile();
			return $res;
		}

		//scanning all the files, starting at the beginning of Central Dir
		$entryPos = $arCentralDirInfo['offset'];
		@rewind($this->zipfile);

		if (@fseek($this->zipfile, $entryPos))
		{
			//clean file
			$this->_closeFile();
			$this->_errorLog("ERR_INVALID_ARCHIVE_ZIP", GetMessage("MAIN_ZIP_ERR_INVALID_ARCHIVE_ZIP"));
			return $this->arErrors;
		}


		$j_start = 0;

		//reading each entry
		for ($i = 0, $extractedCounter = 0; $i<$arCentralDirInfo['entries']; $i++)
		{
			//reading file header
			$arHeaders[$extractedCounter] = array();

			$res = $this->_readCentralFileHeader($arHeaders[$extractedCounter]);
			if ($res != 1)
			{
				$this->_closeFile();
				return $res;
			}

			//saving index
			$arHeaders[$extractedCounter]['index'] = $i;

			//check specific extract rules
			$isFound = false;

			//name rule
			if ((isset($arParams['by_name'])) && ($arParams['by_name'] != 0))
			{
				//if the filename is in the list
				for ($j = 0; ($j<sizeof($arParams['by_name'])) && (!$isFound); $j++)
				{
					if (substr($arParams['by_name'][$j], -1) == "/")
					{
						//if the directory is in the filename path
						if (   (strlen($arHeaders[$extractedCounter]['stored_filename']) > strlen($arParams['by_name'][$j]))
							&& (substr($arHeaders[$extractedCounter]['stored_filename'], 0, strlen($arParams['by_name'][$j])) == $arParams['by_name'][$j]))
						{
							$isFound = true;
						}
						elseif ((($arHeaders[$extractedCounter]['external']&0x00000010)==0x00000010) /* Indicates a folder */
							&& ($arHeaders[$extractedCounter]['stored_filename'].'/' == $arParams['by_name'][$j]))
						{
							$isFound = true;
						}
					}
					elseif ($arHeaders[$extractedCounter]['stored_filename'] == $arParams['by_name'][$j])
					{
						//check filename
						$isFound = true;
					}
				}
			}
			else if ((isset($arParams['by_preg'])) && ($arParams['by_preg'] != ""))
			{
				if (preg_match($arParams['by_preg'], $arHeaders[$extractedCounter]['stored_filename']))
				{
					$isFound = true;
				}
			}
			else if ((isset($arParams['by_index'])) && ($arParams['by_index'] != 0))
			{
				//index rule: if index is in the list
				for ($j = $j_start; ($j<sizeof($arParams['by_index'])) && (!$isFound); $j++)
				{
					if (($i>=$arParams['by_index'][$j]['start'])
						&& ($i<=$arParams['by_index'][$j]['end']))
					{
						$isFound = true;
					}
					if ($i>=$arParams['by_index'][$j]['end'])
					{
						$j_start = $j+1;
					}
					if ($arParams['by_index'][$j]['start']>$i)
					{
						break;
					}
				}
			}

			//delete?
			if ($isFound)
				unset($arHeaders[$extractedCounter]);
			else
				$extractedCounter++;
		}

		//if something should be deleted
		if ($extractedCounter > 0)
		{
			//create tmp file
			$zipname_tmp = GetDirPath($this->zipname).uniqid('ziparc').'.tmp';
			//create tmp zip archive
			$tmpzip = new CZip($zipname_tmp);

			if (($res = $tmpzip->_openFile('wb')) != 1)
			{
				$this->_closeFile();
				return $res;
			}

			//check which file should be kept
			for ($i = 0; $i<sizeof($arHeaders); $i++)
			{
				//calculate the position of the header
				@rewind($this->zipfile);
				if (@fseek($this->zipfile,  $arHeaders[$i]['offset']))
				{
					$this->_closeFile();
					$tmpzip->_closeFile();
					@unlink($this->io->GetPhysicalName($zipname_tmp));
					$this->_errorLog("ERR_INVALID_ARCHIVE_ZIP", GetMessage("MAIN_ZIP_ERR_INVALID_ARCHIVE_ZIP"));

					return $this->arErrors;
				}

				if (($res = $this->_readFileHeader($arHeaders[$i])) != 1)
				{
					$this->_closeFile();
					$tmpzip->_closeFile();
					@unlink($this->io->GetPhysicalName($zipname_tmp));

					return $res;
				}

				//writing file header
				$res = $tmpzip->_writeFileHeader($arHeaders[$i]);
				if ($res != 1)
				{
					$this->_closeFile();
					$tmpzip->_closeFile();
					@unlink($this->io->GetPhysicalName($zipname_tmp));

					return $res;
				}

				//reading/writing data block
				$res = $this->_copyBlocks($this->zipfile, $tmpzip->zipfile, $arHeaders[$i]['compressed_size']);
				if ($res != 1)
				{
					$this->_closeFile();
					$tmpzip->_closeFile();
					@unlink($this->io->GetPhysicalName($zipname_tmp));

					return $res;
				}
			}

			//save central dir offset
			$offset = @ftell($tmpzip->zipfile);

			//re-write central dir files header
			for ($i = 0; $i<sizeof($arHeaders); $i++)
			{
				$res = $tmpzip->_writeCentralFileHeader($arHeaders[$i]);
				if ($res != 1)
				{
					$tmpzip->_closeFile();
					$this->_closeFile();
					@unlink($this->io->GetPhysicalName($zipname_tmp));

					return $res;
				}

				//convert header to the 'usable' format
				$tmpzip->_convertHeader2FileInfo($arHeaders[$i], $arResultList[$i]);
			}

			$zip_comment = '';
			$size = @ftell($tmpzip->zipfile)-$offset;

			$res = $tmpzip->_writeCentralHeader(sizeof($arHeaders), $size, $offset, $zip_comment);
			if ($res != 1)
			{
				unset($arHeaders);
				$tmpzip->_closeFile();
				$this->_closeFile();
				@unlink($this->io->GetPhysicalName($zipname_tmp));

				return $res;
			}

			$tmpzip->_closeFile();
			$this->_closeFile();

			//deleting zip file (result should be checked)
			@unlink($this->io->GetPhysicalName($this->zipname));

			//result should be checked
			$this->_renameTmpFile($zipname_tmp, $this->zipname);

			unset($tmpzip);
		}

		return $res;
	}

	private function _checkDir($dir, $isDir = false)
	{
		$res = 1;

		//remove '/' at the end
		if (($isDir) && (substr($dir, -1)=='/'))
			$dir = substr($dir, 0, strlen($dir)-1);

		//check if dir is available
		if ((is_dir($dir)) || ($dir == ""))
			return 1;

		//get parent directory
		$parentDir = dirname($dir);

		if ($parentDir != $dir)
		{
			//find the parent dir
			if ($parentDir != "")
			{
				if (($res = $this->_checkDir($parentDir)) != 1)
				{
					return $res;
				}
			}
		}

		//creating a directory
		if (!@mkdir($dir, 0777))
		{
			$this->_errorLog("ERR_DIR_CREATE_FAIL", str_replace("#DIR_NAME#", $dir, GetMessage("MAIN_ZIP_ERR_DIR_CREATE_FAIL")));
			return $this->arErrors;
		}

		return $res;
	}

	private function _checkParams(&$arParams, $arDefaultValues)
	{
		if (!is_array($arParams))
		{
			$this->_errorLog("ERR_PARAM", GetMessage("MAIN_ZIP_ERR_PARAM"));
			return $this->arErrors;
		}

		//all params should be valid
		for (reset($arParams); list($key) = each($arParams); )
		{
			if (!isset($arDefaultValues[$key]))
			{
				$this->_errorLog("ERR_PARAM_KEY", str_replace("#KEY#", $key, GetMessage("MAIN_ZIP_ERR_PARAM_KEY")));
				return $this->arErrors;
			}
		}

		//set default values
		for (reset($arDefaultValues); list($key) = each($arDefaultValues); )
		{
			if (!isset($arParams[$key]))
			{
				$arParams[$key] = $arDefaultValues[$key];
			}
		}

		//check specific parameters
		$arCallbacks = array('callback_pre_add','callback_post_add', 'callback_pre_extract','callback_post_extract');

		for ($i = 0; $i<sizeof($arCallbacks); $i++)
		{
			$key = $arCallbacks[$i];

			if ((isset($arParams[$key])) && ($arParams[$key] != ''))
			{
				if (!function_exists($arParams[$key]))
				{
					$this->_errorLog("ERR_PARAM_CALLBACK", str_replace(array("#CALLBACK#", "#PARAM_NAME#"), array($arParams[$key], $key), GetMessage("MAIN_ZIP_ERR_PARAM_CALLBACK")));
					return $this->arErrors;
				}
			}
		}

		return(1);
	}

	private function _errorLog($errorName, $errorString = '')
	{
		$this->arErrors[] = "[".$errorName."] ".$errorString;
	}

	private function _errorReset()
	{
		$this->arErrors = array();
	}

	private function _reducePath($dir)
	{
		$res = "";

		if ($dir != "")
		{
			//get directory names
			$arTmpList = explode("/", $dir);

			//check from last to first
			for ($i = sizeof($arTmpList) - 1; $i >= 0; $i--)
			{
				//is current path
				if ($arTmpList[$i] == ".")
				{
					//just ignore. the first $i should be = 0, but no check is done
				}
				else if ($arTmpList[$i] == "..")
				{
					//ignore this and ignore the $i-1
					$i--;
				}
				else if (($arTmpList[$i] == "") && ($i!=(sizeof($arTmpList)-1)) && ($i!=0))
				{
					//ignore only the double '//' in path, but not the first and last '/'
				}
				else
				{
					$res = $arTmpList[$i].($i != (sizeof($arTmpList)-1) ? "/".$res : "");
				}
			}
		}
		return $res;
	}

	private function _containsPath($dir, $path)
	{
		$res = 1;

		//explode dir and path by directory separator
		$arTmpDirList  = explode("/", $dir);
		$arTmpPathList = explode("/", $path);

		$arTmpDirListSize  = sizeof($arTmpDirList);
		$arTmpPathListSize = sizeof($arTmpPathList);

		//check dir paths
		$i = 0;
		$j = 0;

		while (($i < $arTmpDirListSize) && ($j < $arTmpPathListSize) && ($res))
		{
			//check if is empty
			if ($arTmpDirList[$i] == '')
			{
				$i++;
				continue;
			}

			if ($arTmpPathList[$j] == '')
			{
				$j++;
				continue;
			}

			//compare items
			if (($arTmpDirList[$i] != $arTmpPathList[$j]) && ($arTmpDirList[$i] != '') && ( $arTmpPathList[$j] != ''))
			{
				$res = 0;
			}

			$i++;
			$j++;
		}

		//check if the same
		if ($res)
		{
			//skip empty items
			while (($j < $arTmpPathListSize) && ($arTmpPathList[$j] == ''))
			{
				$j++;
			}

			while (($i < $arTmpDirListSize) && ($arTmpDirList[$i] == ''))
			{
				$i++;
			}

			if (($i >= $arTmpDirListSize) && ($j >= $arTmpPathListSize))
			{
				//exactly the same
				$res = 2;
			}
			else if ($i < $arTmpDirListSize)
			{
				//path is shorter than the dir
				$res = 0;
			}
		}

		return $res;
	}

	private function _copyBlocks($source, $dest, $blockSize, $mode = 0)
	{
		$res = 1;

		if ($mode == 0)
		{
			while ($blockSize != 0)
			{
				$length = ($blockSize < self::ReadBlockSize ? $blockSize : self::ReadBlockSize);
				$buffer = @fread($source, $length);
				@fwrite($dest, $buffer, $length);
				$blockSize -= $length;
			}
		}
		else if ($mode == 1)
		{
			while ($blockSize != 0)
			{
					$length = ($blockSize < self::ReadBlockSize ? $blockSize : self::ReadBlockSize);
					$buffer = @gzread($source, $length);
					@fwrite($dest, $buffer, $length);
					$blockSize -= $length;
			}
		}
		else if ($mode == 2)
		{
			while ($blockSize != 0)
			{
				$length = ($blockSize < self::ReadBlockSize ? $blockSize : self::ReadBlockSize);
				$buffer = @fread($source, $length);
				@gzwrite($dest, $buffer, $length);
				$blockSize -= $length;
			}
		}
		else if ($mode == 3)
		{
			while ($blockSize != 0)
			{
				$length = ($blockSize < self::ReadBlockSize ? $blockSize : self::ReadBlockSize);
				$buffer = @gzread($source, $length);
				@gzwrite($dest, $buffer, $length);
				$blockSize -= $length;
			}
		}

		return $res;
	}

	private function _renameTmpFile($source, $dest)
	{
		$res = 1;

		if (!@rename($this->io->GetPhysicalName($source), $this->io->GetPhysicalName($dest)))
		{
			if (!@copy($this->io->GetPhysicalName($source), $this->io->GetPhysicalName($dest)))
			{
				$res = 0;
			}
			else if (!@unlink($this->io->GetPhysicalName($source)))
			{
				$res = 0;
			}
		}

		return $res;
	}

	private function _convertWinPath($path, $removeDiskLetter = true)
	{
		if (stristr(php_uname(), 'windows'))
		{
			//disk letter?
			if (($removeDiskLetter) && (($position = strpos($path, ':')) != false))
			{
				$path = substr($path, $position + 1);
			}

			//change windows directory separator
			if ((strpos($path, '\\') > 0) || (substr($path, 0, 1) == '\\'))
			{
				$path = strtr($path, '\\', '/');
			}
		}

		return $path;
	}

	private function &_parseFileParams(&$arFileList)
	{
		if (isset($arFileList) && is_array($arFileList))
			return $arFileList;

		if (isset($arFileList) && strlen($arFileList)>0)
		{
			if(strpos($arFileList, "\"")===0)
				return array(trim($arFileList, "\""));
			return explode(" ", $arFileList);
		}

		return array();
	}

	private function _cleanFile()
	{
		$this->_closeFile();
		@unlink($this->io->GetPhysicalName($this->zipname));
		return true;
	}

	private function _checkDirPath($path)
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

	private function _getfileSystemEncoding()
	{
		$fileSystemEncoding = strtolower(defined("BX_FILE_SYSTEM_ENCODING") ? BX_FILE_SYSTEM_ENCODING : "");

		if (empty($fileSystemEncoding))
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN")
				$fileSystemEncoding =  "windows-1251";
			else
				$fileSystemEncoding = "utf-8";
		}

		return $fileSystemEncoding;
	}
}
