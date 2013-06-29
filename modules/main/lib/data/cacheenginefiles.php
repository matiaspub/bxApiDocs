<?php
namespace Bitrix\Main\Data;

use \Bitrix\Main\IO;
use \Bitrix\Main\Server;

class CacheEngineFiles
	implements ICacheEngine
{
	private $TTL;

	//cache stats
	private $written = false;
	private $read = false;

	static public function isAvailable()
	{
		return true;
	}

	static public function clean($baseDir, $initDir = false, $filename = false)
	{
		if(strlen($filename))
		{
			$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
			$fn = IO\Path::combine($documentRoot, $baseDir, $initDir, $filename);
			$file = new IO\File($fn);

			//This checks for Zend Server CE in order to supress warnings
			if(function_exists('accelerator_reset'))
			{
				try
				{
					$file->markWritable();
					if($file->delete())
					{
						\Bitrix\Main\Application::resetAccelerator();
						return true;
					}
				}
				catch (\Exception $ex)
				{

				}
			}
			else
			{
				if($file->isExists())
				{
					$file->markWritable();
					if($file->delete())
					{
						\Bitrix\Main\Application::resetAccelerator();
						return true;
					}
				}
			}
			return false;
		}
		else
		{
			static $bAgentAdded = false;
			$bDelayedDelete = false;

			$source = IO\Path::combine($baseDir, $initDir);
			$sourceDir = new IO\Directory(IO\Path::convertRelativeToAbsolute($source));

			if($sourceDir->isExists())
			{
				$target = $source.".~";
				for($i = 0; $i < 9; $i++) //try to get new directory name no more than ten times
				{
					$suffix = rand(0, 999999);
					$targetDir = new IO\Directory(IO\Path::convertRelativeToAbsolute($target.$suffix));
					if(!$targetDir->isExists())
					{
						$con = \Bitrix\Main\Application::getDbConnection();
						$con->queryExecute(
							"INSERT INTO b_cache_tag (SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG)
							VALUES ('*', '*', '".$con->getSqlHelper()->forSql($target.$suffix)."', '*')");
						if($sourceDir->rename($targetDir->getPath()))
							$bDelayedDelete = true;
						break;
					}
				}
			}

			if($bDelayedDelete)
			{
				if(!$bAgentAdded)
				{
					$bAgentAdded = true;

					/* TODO: Agents */
					$rsAgents = \CAgent::getList(array("ID"=>"DESC"), array("NAME" => "CacheEngineFiles::delayedDelete(%"));
					if(!$rsAgents->fetch())
					{
						$res = \CAgent::addAgent(
							"CacheEngineFiles::delayedDelete();",
							"main", //module
							"Y", //period
							1 //interval
						);
					}
				}
			}
			else
			{
				$sourceDir->delete();
			}

			\Bitrix\Main\Application::resetAccelerator();
		}
	}

	private static function checkZeroDanger()
	{
		static $zeroDanger = null;
		if (is_null($zeroDanger))
			$zeroDanger = (version_compare(phpversion(), '5.4.0') >= 0) ? (ini_get('zend.multibyte') == '1') : (ini_get('detect_unicode') == '1');
		return $zeroDanger;
	}

	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
		$fn = IO\Path::combine($documentRoot, $baseDir, $initDir, $filename);
		$file = new IO\File($fn);

		if(!$file->isExists())
			return false;

		$ser_content = "";
		$dateexpire = 0;
		$datecreate = 0;
		$zeroDanger = false;

		$handle = null;
		if (is_array($arAllVars))
		{
			$INCLUDE_FROM_CACHE = 'Y';

			if (!@include(IO\Path::convertLogicalToPhysical($fn)))
				return false;

			if ($zeroDanger)
				$ser_content = str_replace("\x01\x01\01", "\x00\x2A\00", $ser_content);
		}
		else
		{
			if (!($file instanceof IO\IFileStream))
				return false;

			$handle = $file->open("r");
			if(!$handle)
				return false;

			$datecreate = fread($handle, 2);
			if($datecreate == "BX")
			{
				$datecreate = fread($handle, 12);
				$dateexpire = fread($handle, 12);
			}
			else
			{
				$datecreate .= fread($handle, 10);
			}
		}

		/* We suppress warning here in order not to break
		the compression under Zend Server */
		$this->read = $file->getFileSize();

		if(intval($datecreate) < (mktime() - $TTL))
			return false;

		if(is_array($arAllVars))
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
		$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
		$fn = IO\Path::combine($documentRoot, $baseDir, $initDir, $filename);
		$file = new IO\File($fn);

		$fnTmp = IO\Path::combine($documentRoot, $baseDir, $initDir, md5(mt_rand()).".tmp");
		$fileTmp = new IO\File($fnTmp);

		$dir = $file->getDirectory();
		if (!$dir->isExists())
			$dir->create();

		if(is_array($arAllVars))
		{
			$contents = "<?";
			$contents .= "\nif(\$INCLUDE_FROM_CACHE!='Y')return false;";
			$contents .= "\n\$datecreate = '".str_pad(mktime(), 12, "0", STR_PAD_LEFT)."';";
			$contents .= "\n\$dateexpire = '".str_pad(mktime() + IntVal($TTL), 12, "0", STR_PAD_LEFT)."';";
			$v = serialize($arAllVars);
			if (static::checkZeroDanger())
			{
				$v = str_replace("\x00\x2A\00", "\x01\x01\01", $v);
				$contents .= "\n\$zeroDanger = true;";
			}
			$contents .= "\n\$ser_content = '".str_replace("'", "\'", str_replace("\\", "\\\\", $v))."';";
			$contents .= "\nreturn true;";
			$contents .= "\n?>";
		}
		else
		{
			$contents = "BX".str_pad(mktime(), 12, "0", STR_PAD_LEFT).str_pad(mktime() + IntVal($this->TTL), 12, "0", STR_PAD_LEFT);
			$contents .= $arAllVars;
		}

		$this->written = $fileTmp->putContents($contents);

		$len = \Bitrix\Main\Text\String::strlenBytes($contents);

		//This checks for Zend Server CE in order to supress warnings
		if(function_exists('accelerator_reset'))
		{
			try
			{
				$file->delete();
			}
			catch (\Exception $ex)
			{

			}
		}
		elseif($file->isExists())
			$file->delete();

		if($this->written === $len)
			$fileTmp->rename($fn);

		//This checks for Zend Server CE in order to supress warnings
		if(function_exists('accelerator_reset'))
		{
			try
			{
				IO\File::deleteFile($fnTmp);
			}
			catch (\Exception $ex)
			{

			}
		}
		elseif(IO\File::isFileExists($fnTmp))
			IO\File::deleteFile($fnTmp);
	}

	static public function isCacheExpired($path)
	{
		$file = new IO\File($path);
		if(!$file->isExists())
			return true;

		if (!($file instanceof IO\IFileStream))
			return true;

		$dfile = $file->open("r");
		$str_tmp = fread($dfile, 150);
		fclose($dfile);

		if(
			preg_match("/dateexpire\s*=\s*'([\d]+)'/im", $str_tmp, $arTmp)
			|| preg_match("/^BX\\d{12}(\\d{12})/", $str_tmp, $arTmp)
			|| preg_match("/^(\\d{12})/", $str_tmp, $arTmp)
		)
		{
			if(strlen($arTmp[1]) <= 0 || doubleval($arTmp[1]) < mktime())
				return true;
		}

		return false;
	}

	protected function deleteOneDir($etime = 0)
	{
		$bDeleteFromQueue = false;

		$con = \Bitrix\Main\Application::getDbConnection();
		$rs = $con->query("SELECT * from b_cache_tag WHERE TAG='*'", 0, 1);
		if($ar = $rs->fetch())
		{
			$dirName = IO\Path::convertRelativeToAbsolute($ar["RELATIVE_PATH"]);
			$dir = new IO\Directory($dirName);
			if ($dir->isExists())
			{
				$arChildren = $dir->getChildren();
				$Counter = 0;
				foreach ($arChildren as $child)
				{
					$child->delete();
					$Counter++;
					if (time() > $etime)
						break;
				}

				if($Counter == 0)
				{
					$dir->delete();
					$bDeleteFromQueue = true;
				}
			}
			else
			{
				$bDeleteFromQueue = true;
			}

			if($bDeleteFromQueue)
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
		for($i = 0; $i < $count; $i++)
		{
			self::deleteOneDir($etime);
			if(time() > $etime)
				break;
		}

		$con = \Bitrix\Main\Application::getDbConnection();
		//try to adjust cache cleanup speed to cache cleanups
		$rs = $con->query("SELECT * from b_cache_tag WHERE TAG='**'");
		if($ar = $rs->fetch())
			$last_count = intval($ar["RELATIVE_PATH"]);
		else
			$last_count = 0;
		$bWasStatRecFound = is_array($ar);

		$this_count = $con->queryScalar("SELECT count(1) CNT from b_cache_tag WHERE TAG='*'");

		$delta = $this_count - $last_count;
		if($delta > 0)
			$count = intval($this_count/3600)+1; //Rest of the queue in an hour
		elseif($count < 1)
			$count = 1;

		if($bWasStatRecFound)
		{
			if($last_count != $this_count)
				$con->queryExecute("UPDATE b_cache_tag SET RELATIVE_PATH='".$this_count."' WHERE TAG='**'");
		}
		else
		{
			$con->queryExecute("INSERT INTO b_cache_tag (TAG, RELATIVE_PATH) VALUES ('**', '".$this_count."')");
		}

		if($this_count > 0)
			return "CacheEngineFiles::delayedDelete(".$count.");";
		else
			return "";
	}
}
