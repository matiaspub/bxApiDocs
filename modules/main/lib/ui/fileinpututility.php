<?php
namespace Bitrix\Main\UI;

class FileInputUtility
{
	protected static $instance = null;

	const SESSION_VAR_PREFIX = "MFI_UPLOADED_FILES_";
	const SESSION_LIST = "MFI_SESSIONS";
	const SESSION_TTL = 86400;

	public static function instance()
	{
		if (!isset(static::$instance))
			static::$instance = new static();

		return static::$instance;
	}

	static public function __construct()
	{
	}

	public function registerControl($controlId)
	{
		$CID = md5(randString(15));
		$this->initSession($CID, $controlId);
		return $CID;
	}

	static public function registerFile($CID, $fileId)
	{
		$_SESSION[self::SESSION_VAR_PREFIX.$CID][] = $fileId;
	}

	static public function unRegisterFile($CID, $fileId)
	{
		if (isset($_SESSION[self::SESSION_VAR_PREFIX.$CID]))
		{
			$key = array_search($fileId, $_SESSION[self::SESSION_VAR_PREFIX.$CID]);
			if($key !== false)
			{
				unset($_SESSION[self::SESSION_VAR_PREFIX.$CID][$key]);
				return true;
			}
		}
		return false;
	}

	public function checkFiles($controlId, $arFiles)
	{
		$arSessionFilesList = $this->getSessionControlFiles($controlId);

		if(is_array($arFiles))
		{
			foreach($arFiles as $key => $fileId)
			{
				if(!in_array($fileId, $arSessionFilesList))
				{
					unset($arFiles[$key]);
				}
			}

			$arFiles = array_values($arFiles);
		}

		return $arFiles;
	}

	static public function checkFile($CID, $fileId)
	{
		return isset($_SESSION[self::SESSION_VAR_PREFIX.$CID])
			&& in_array($fileId, $_SESSION[self::SESSION_VAR_PREFIX.$CID]);
	}

	protected function initSession($CID, $controlId)
	{
		$ts = time();

		if(!isset($_SESSION[self::SESSION_LIST][$controlId]))
		{
			$_SESSION[self::SESSION_LIST][$controlId] = array();
		}
		else
		{
			foreach($_SESSION[self::SESSION_LIST][$controlId] as $key => $arSession)
			{
				if($arSession["SESSID"] != bitrix_sessid()
					|| $ts-$arSession["TS"] > self::SESSION_TTL)
				{
					$c = $_SESSION[self::SESSION_LIST][$controlId][$key]["CID"];
					unset($_SESSION[self::SESSION_LIST][$controlId][$key]);
					unset($_SESSION[self::SESSION_VAR_PREFIX.$c]);
				}
			}
		}

		$_SESSION[self::SESSION_LIST][$controlId][] = array(
			"CID" => $CID,
			"TS" => $ts,
			"SESSID" => bitrix_sessid()
		);
		$_SESSION[self::SESSION_VAR_PREFIX.$CID] = array();
	}

	protected function getSessionControlFiles($controlId)
	{
		$res = array();

		if(isset($_SESSION[self::SESSION_LIST][$controlId]))
		{
			foreach($_SESSION[self::SESSION_LIST][$controlId] as $arSession)
			{
				if(isset($_SESSION[self::SESSION_VAR_PREFIX.$arSession['CID']]))
				{
					$res = array_merge($_SESSION[self::SESSION_VAR_PREFIX.$arSession['CID']]);
				}

			}
		}

		return $res;
	}
}