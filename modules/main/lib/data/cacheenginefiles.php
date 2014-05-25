<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;
use Bitrix\Main\IO;

class CacheEngineFiles
	implements ICacheEngine, ICacheEngineStat
{
	private $TTL;

	//cache stats
	private $written = false;
	private $read = false;
	private $path = '';

	public function getReadBytes()
	{
		return $this->read;
	}

	public function getWrittenBytes()
	{
		return $this->written;
	}

	/**
	 * @return string
	 */
	public function getCachePath()
	{
		return $this->path;
	}

	static public function isAvailable()
	{
		return true;
	}

	private static function unlink($fileName)
	{
		//This checks for Zend Server CE in order to suppress warnings
		if (function_exists('accelerator_reset'))
		{
			@chmod($fileName, BX_FILE_PERMISSIONS);
			if (@unlink($fileName))
				return true;
		}
		else
		{
			if (file_exists($fileName))
			{
				@chmod($fileName, BX_FILE_PERMISSIONS);
				if (unlink($fileName))
					return true;
			}
		}
		return false;
	}

	private static function addAgent()
	{
		global $APPLICATION;

		static $bAgentAdded = false;
		if (!$bAgentAdded)
		{
			$bAgentAdded = true;
			$rsAgents = \CAgent::GetList(array("ID"=>"DESC"), array("NAME" => "\\Bitrix\\Main\\Data\\CacheEngineFiles::delayedDelete(%"));
			if (!$rsAgents->Fetch())
			{
				$res = \CAgent::AddAgent(
					"\\Bitrix\\Main\\Data\\CacheEngineFiles::delayedDelete();",
					"main", //module
					"Y", //period
					1 //interval
				);

				if (!$res)
					$APPLICATION->ResetException();
			}
		}
	}

	private static function randomizeFile($fileName)
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		for ($i = 0; $i < 99; $i++)
		{
			$suffix = rand(0, 999999);
			if (!file_exists($documentRoot.$fileName.$suffix))
				return $fileName.$suffix;
		}
		return "";
	}

	static public function clean($baseDir, $initDir = false, $filename = false)
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		if (($filename !== false) && ($filename !== ""))
		{
			$result = static::unlink($documentRoot.$baseDir.$initDir.$filename);
			Main\Application::resetAccelerator();
			return $result;
		}
		else
		{
			$initDir = trim($initDir, "/");
			if ($initDir == "")
			{
				$sourceDir = $documentRoot."/".trim($baseDir, "/");
				if (file_exists($sourceDir) && is_dir($sourceDir))
				{
					$dh = opendir($sourceDir);
					if (is_resource($dh))
					{
						while ($entry = readdir($dh))
						{
							if (preg_match("/^(\\.|\\.\\.|.*\\.~\\d+)\$/", $entry))
								continue;

							if (is_dir($sourceDir."/".$entry))
								static::clean($baseDir, $entry);
							elseif (is_file($sourceDir."/".$entry))
								static::unlink($sourceDir."/".$entry);
						}
					}
				}
			}
			else
			{
				$source = "/".trim($baseDir, "/")."/".$initDir;
				$source = rtrim($source, "/");
				$bDelayedDelete = false;

				if (!preg_match("/^(\\.|\\.\\.|.*\\.~\\d+)\$/", $source) && file_exists($documentRoot.$source))
				{
					$target = static::randomizeFile($source.".~");
					if ($target != '')
					{
						$con = Main\Application::getConnection();
						$con->queryExecute("INSERT INTO b_cache_tag (SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG) VALUES ('*', '*', '".$con->getSqlHelper()->forSql($target)."', '*')");
						if (@rename($documentRoot.$source, $documentRoot.$target))
							$bDelayedDelete = true;
					}
				}

				if ($bDelayedDelete)
					static::addAgent();
				else
					DeleteDirFilesEx($baseDir.$initDir);

				Main\Application::resetAccelerator();
			}
		}
	}

	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		$fn = $documentRoot."/".ltrim($baseDir.$initDir, "/").$filename;

		if (!file_exists($fn))
			return false;

		$ser_content = "";
		$dateexpire = 0;
		$datecreate = 0;
		$zeroDanger = false;

		$handle = null;
		if (is_array($arAllVars))
		{
			$INCLUDE_FROM_CACHE = 'Y';

			if (!@include($fn))
				return false;
		}
		else
		{
			$handle = fopen($fn, "rb");
			if (!$handle)
				return false;

			$datecreate = fread($handle, 2);
			if ($datecreate == "BX")
			{
				$datecreate = fread($handle, 12);
				$dateexpire = fread($handle, 12);
			}
			else
			{
				$datecreate .= fread($handle, 10);
			}
		}

		/* We suppress warning here in order not to break the compression under Zend Server */
		$this->read = @filesize($fn);
		$this->path = $fn;

		if (intval($datecreate) < (mktime() - $TTL))
			return false;

		if (is_array($arAllVars))
		{
			$arAllVars = unserialize($ser_content);
		}
		else
		{
			$arAllVars = fread($handle, $this->read);
			fclose($handle);
		}

		return true;
	}

	public function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$documentRoot = Main\Loader::getDocumentRoot();
		$folder = $documentRoot."/".ltrim($baseDir.$initDir, "/");
		$fn = $folder.$filename;
		$fnTmp = $folder.md5(mt_rand()).".tmp";

		if (!CheckDirPath($fn))
			return;

		if ($handle = fopen($fnTmp, "wb+"))
		{
			if (is_array($arAllVars))
			{
				$contents = "<?";
				$contents .= "\nif(\$INCLUDE_FROM_CACHE!='Y')return false;";
				$contents .= "\n\$datecreate = '".str_pad(mktime(), 12, "0", STR_PAD_LEFT)."';";
				$contents .= "\n\$dateexpire = '".str_pad(mktime() + intval($TTL), 12, "0", STR_PAD_LEFT)."';";
				$contents .= "\n\$ser_content = '".str_replace("'", "\'", str_replace("\\", "\\\\", serialize($arAllVars)))."';";
				$contents .= "\nreturn true;";
				$contents .= "\n?>";
			}
			else
			{
				$contents = "BX".str_pad(mktime(), 12, "0", STR_PAD_LEFT).str_pad(mktime() + intval($this->TTL), 12, "0", STR_PAD_LEFT);
				$contents .= $arAllVars;
			}

			$this->written = fwrite($handle, $contents);
			$this->path = $fn;
			$len = Main\Text\String::getBinaryLength($contents);

			fclose($handle);

			static::unlink($fn);

			if ($this->written === $len)
				rename($fnTmp, $fn);

			static::unlink($fnTmp);
		}
	}

	static public function isCacheExpired($path)
	{
		if(!file_exists($path))
			return true;

		$dateexpire = 0;

		$INCLUDE_FROM_CACHE='Y';

		$dfile = fopen($path, "rb");
		$str_tmp = fread($dfile, 150);
		fclose($dfile);

		if(
			preg_match("/dateexpire\s*=\s*'([\d]+)'/im", $str_tmp, $arTmp)
			|| preg_match("/^BX\\d{12}(\\d{12})/", $str_tmp, $arTmp)
			|| preg_match("/^(\\d{12})/", $str_tmp, $arTmp)
		)
		{
			if (strlen($arTmp[1]) <= 0 || doubleval($arTmp[1]) < mktime())
				return true;
		}

		return false;
	}

	protected function deleteOneDir($etime = 0)
	{
		$bDeleteFromQueue = false;

		$con = Main\Application::getConnection();
		$rs = $con->query("SELECT SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG from b_cache_tag WHERE TAG='*'", 0, 1);
		if ($ar = $rs->fetch())
		{
			$dirName = Main\Loader::getDocumentRoot().$ar["RELATIVE_PATH"];
			if ($ar["RELATIVE_PATH"] != '' && file_exists($dirName))
			{
				$dh = opendir($dirName);
				if (is_resource($dh))
				{
					$counter = 0;
					while (($file = readdir($dh)) !== false)
					{
						if ($file != "." && $file != "..")
						{
							DeleteDirFilesEx($ar["RELATIVE_PATH"]."/".$file);
							$counter++;
							if (time() > $etime)
								break;
						}
					}
					closedir($dh);

					if ($counter == 0)
					{
						rmdir($dirName);
						$bDeleteFromQueue = true;
					}
				}
			}
			else
			{
				$bDeleteFromQueue = true;
			}

			if ($bDeleteFromQueue)
			{
				$con->queryExecute(
					"DELETE FROM b_cache_tag
					WHERE SITE_ID = '".$con->getSqlHelper()->forSql($ar["SITE_ID"])."'
					AND CACHE_SALT = '".$con->getSqlHelper()->forSql($ar["CACHE_SALT"])."'
					AND RELATIVE_PATH = '".$con->getSqlHelper()->forSql($ar["RELATIVE_PATH"])."'");
			}
		}
	}

	public static function delayedDelete($count = 1, $level = 1)
	{
		$etime = time()+2;
		for ($i = 0; $i < $count; $i++)
		{
			static::deleteOneDir($etime);
			if (time() > $etime)
				break;
		}

		$con = Main\Application::getConnection();
		//try to adjust cache cleanup speed to cache cleanups
		$rs = $con->query("SELECT SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG from b_cache_tag WHERE TAG='**'");
		if ($ar = $rs->fetch())
		{
			$last_count = intval($ar["RELATIVE_PATH"]);
			if (preg_match("/:(\\d+)$/", $ar["RELATIVE_PATH"], $m))
				$last_time = intval($m[1]);
			else
				$last_time = 0;
		}
		else
		{
			$last_count = 0;
			$last_time = 0;
		}
		$bWasStatRecFound = is_array($ar);

		$this_count = $con->queryScalar("SELECT count(1) CNT from b_cache_tag WHERE TAG='*'");

		$delta = $this_count - $last_count;
		if ($delta > 0)
		{
			if($last_time > 0)
				$time_step = time() - $last_time;
			if ($time_step <= 0)
				$time_step = 1;
			$count = intval($this_count * $time_step / 3600) + 1; //Rest of the queue in an hour
		}
		elseif ($count < 1)
		{
			$count = 1;
		}

		if ($bWasStatRecFound)
		{
			if ($last_count != $this_count)
				$con->queryExecute("UPDATE b_cache_tag SET RELATIVE_PATH='".$this_count.":".time()."' WHERE TAG='**'");
		}
		else
		{
			$con->queryExecute("INSERT INTO b_cache_tag (TAG, RELATIVE_PATH) VALUES ('**', '".$this_count.":".time()."')");
		}

		if($this_count > 0)
			return "\\Bitrix\\Main\\Data\\CacheEngineFiles::delayedDelete(".$count.");";
		else
			return "";
	}
}
