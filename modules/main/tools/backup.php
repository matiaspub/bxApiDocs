<?php
if (ini_get('short_open_tag') == 0)
	die("Error: short_open_tag parameter must be turned on in php.ini\n");
?><?
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
// define('START_TIME', time());
// define('CLI', php_sapi_name() == 'cli');
@// define('LANGUAGE_ID', 'en');
@// define('NOT_CHECK_PERMISSIONS', true);
$NS = array(); // NewState

if (CLI && defined('BX_CRONTAB')) // start from cron_events.php
{
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/dump.php');

	if (IntOption('dump_auto_enable') != 1)
		return;

	$l = COption::GetOptionInt('main', 'last_backup_start_time', 0);
	if (time() - $l < IntOption('dump_auto_interval') * 86400)
		return;

	$min_left = IntOption('dump_auto_time') - date('H')*60 - date("i");
	if ($min_left > 0 || $min_left < -60)
		return;

	// define('LOCK_FILE', $_SERVER['DOCUMENT_ROOT'].'/bitrix/backup/auto_lock');

	if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/backup'))
		mkdir($_SERVER['DOCUMENT_ROOT'].'/bitrix/backup');

	if (file_exists(LOCK_FILE))
	{
		if (!($time = file_get_contents(LOCK_FILE)))
			RaiseErrorAndDie('Can\'t read file: '.LOCK_FILE, 1);

		if ($time + 86400 > time())
		{
			return;
		}
		else
		{
			ShowBackupStatus('Warning! Last backup has failed');
			CEventLog::Add(array(
				"SEVERITY" => "WARNING",
				"AUDIT_TYPE_ID" => "BACKUP_ERROR",
				"MODULE_ID" => "main",
				"ITEM_ID" => LOCK_FILE,
				"DESCRIPTION" => GetMessage('AUTO_LOCK_EXISTS_ERR', array('#DATETIME#' => ConvertTimeStamp($time))),
			));
			unlink(LOCK_FILE) || RaiseErrorAndDie('Can\'t delete file: '.LOCK_FILE, 2);
		}
	}

	if (!file_put_contents(LOCK_FILE, time()))
		RaiseErrorAndDie('Can\'t create file: '.LOCK_FILE, 3);

	COption::SetOptionInt('main', 'last_backup_start_time', time());
}
else
{
	// define('NO_AGENT_CHECK', true);
	// define("STATISTIC_SKIP_ACTIVITY_CHECK", true);
	$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../');
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/dump.php');
}
if (!defined('DOCUMENT_ROOT'))
	;// define('DOCUMENT_ROOT', rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']),'/'));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/backup.php");

if (!CLI) // hit from bitrixcloud service
{
	if ((!$backup_secret_key =  CPasswordStorage::Get('backup_secret_key')) || $backup_secret_key != $_REQUEST['secret_key'])
	{
		RaiseErrorAndDie('Secret key is incorrect', 10);
	}
	elseif ($_REQUEST['check_auth'])
	{
		echo 'SUCCESS';
		exit(0);
	}

	if (IntOption('dump_auto_enable') != 2)
		RaiseErrorAndDie('Backup is disabled', 4);

	session_write_close();
	session_id(md5($backup_secret_key));
	session_start();
	$NS =& $_SESSION['BX_DUMP_STATE'];
}

if (!file_exists(DOCUMENT_ROOT.'/bitrix/backup'))
	mkdir(DOCUMENT_ROOT.'/bitrix/backup');

if (!file_exists(DOCUMENT_ROOT."/bitrix/backup/index.php"))
{
	$f = fopen(DOCUMENT_ROOT."/bitrix/backup/index.php","w");
	fwrite($f,"<head><meta http-equiv=\"REFRESH\" content=\"0;URL=/bitrix/admin/index.php\"></head>");
	fclose($f);
}

while(ob_end_flush());

@set_time_limit(0);

if (function_exists('mb_internal_encoding'))
	mb_internal_encoding('ISO-8859-1');

$bGzip = function_exists('gzcompress');
$bMcrypt = function_exists('mcrypt_encrypt');
$bBitrixCloud = $bMcrypt && CModule::IncludeModule('bitrixcloud') && CModule::IncludeModule('clouds');

$arParams = array(
	'disk_space' => COption::GetOptionInt('main','disk_space', 0),

	'dump_archive_size_limit' => IntOption('dump_archive_size_limit'),
	'dump_use_compression' => $bGzip && IntOption('dump_use_compression'),
	'dump_integrity_check' => IntOption('dump_integrity_check'),
	
	'dump_delete_old' => IntOption('dump_delete_old'),
	'dump_old_time' => IntOption('dump_old_time'),
	'dump_old_cnt' => IntOption('dump_old_cnt'),
	'dump_old_size' => IntOption('dump_old_size'),
);

$arExpertBackupDefaultParams = array(
	'dump_base' => IntOption('dump_base', 1),
	'dump_base_skip_stat' => IntOption('dump_base_skip_stat', 0),
	'dump_base_skip_search' => IntOption('dump_base_skip_search', 0),
	'dump_base_skip_log' => IntOption('dump_base_skip_log', 0),

	'dump_file_public' => IntOption('dump_file_public', 1),
	'dump_file_kernel' => IntOption('dump_file_kernel', 1),
	'dump_do_clouds' => IntOption('dump_do_clouds', 1),
	'skip_mask' => IntOption('skip_mask', 0),
	'skip_mask_array' => is_array($ar = unserialize(COption::GetOptionString("main","skip_mask_array_auto"))) ? $ar : array(),
	'dump_max_file_size' => IntOption('dump_max_file_size', 0),
	'skip_symlinks' => IntOption('skip_symlinks', 0),
);

if (!is_array($arExpertBackupParams))
	$arExpertBackupParams = array();
	
$arParams = array_merge($arExpertBackupDefaultParams, $arExpertBackupParams, $arParams);
$skip_mask_array = $arParams['skip_mask_array'];

if (strtolower($DB->type) != 'mysql')
	$arParams['dump_base'] = 0;

if (!$NS['step'])
{
	$NS = array('step' => 1);
	$NS['dump_encrypt_key'] = CPasswordStorage::Get('dump_temporary_cache');
	$dump_bucket_id = IntOption('dump_bucket_id');
	if ($dump_bucket_id == -1)
	{
		if (!$bBitrixCloud || !$NS['dump_encrypt_key'])
		{
			$dump_bucket_id = 0;
			ShowBackupStatus('BitrixCloud is not available');
		}
	}
	$NS['BUCKET_ID'] = $dump_bucket_id;

	if ($dump_bucket_id == -1)
		$arc_name = DOCUMENT_ROOT.BX_ROOT."/backup/".date('Ymd_His_').rand(11111111,99999999);
	elseif(($arc_name = $argv[1]) && !is_dir($arc_name))
		$arc_name =  str_replace(array('.tar','.gz','.enc'),'',$arc_name);
	else
	{
		$prefix = str_replace('/', '', COption::GetOptionString("main", "server_name", ""));
		$arc_name = CBackup::GetArcName(preg_match('#^[a-z0-9\.\-]+$#i', $prefix) ? substr($prefix, 0, 20).'_' : '');
	}

	$NS['arc_name'] = $arc_name.($NS['dump_encrypt_key'] ? ".enc" : ".tar").($arParams['dump_use_compression'] ? ".gz" : '');
	$NS['dump_name'] = $arc_name.'.sql';

	ShowBackupStatus('Backup started to file: '.$NS['arc_name']);
}

$after_file = str_replace('.sql','_after_connect.sql',$NS['dump_name']);

if ($NS['step'] <= 2)
{
	// dump database
	if ($arParams['dump_base'])
	{
		if ($NS['step'] == 1)
		{
			ShowBackupStatus('Dumping database');
			if (!CBackup::MakeDump($NS['dump_name'], $NS['dump_state']))
				RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS'), 100, $NS['dump_name']);

			if (!$NS['dump_state']['end'])
				CheckPoint();

			$rs = $DB->Query('SHOW VARIABLES LIKE "character_set_results"');
			if (($f = $rs->Fetch()) && array_key_exists ('Value', $f))
				file_put_contents($after_file, "SET NAMES '".$f['Value']."';\n");

			$rs = $DB->Query('SHOW VARIABLES LIKE "collation_database"');
			if (($f = $rs->Fetch()) && array_key_exists ('Value', $f))
				file_put_contents($after_file, "ALTER DATABASE `<DATABASE>` COLLATE ".$f['Value'].";\n",8);
			
			$NS['step'] = 2;
		}

		ShowBackupStatus('Archiving database dump');
		$tar = new CTar;
		$tar->EncryptKey = $NS['dump_encrypt_key'];
		$tar->ArchiveSizeLimit = $arParams['dump_archive_size_limit'];
		$tar->gzip = $arParams['dump_use_compression'];
		$tar->path = DOCUMENT_ROOT;
		$tar->ReadBlockCurrent = intval($NS['ReadBlockCurrent']);
		$tar->ReadFileSize = intval($NS['ReadFileSize']);

		if (!$tar->openWrite($NS["arc_name"]))
			RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS'), 200, $NS['arc_name']);

		if (!$tar->ReadBlockCurrent && file_exists($f = DOCUMENT_ROOT.BX_ROOT.'/.config.php'))
			$tar->addFile($f);

		$Block = $tar->Block;
		while(haveTime() && ($r = $tar->addFile($NS['dump_name'])) && $tar->ReadBlockCurrent > 0);
		$NS["data_size"] += 512 * ($tar->Block - $Block);
		
		if ($r === false)
			RaiseErrorAndDie(implode('<br>',$tar->err), 210, $NS['arc_name']);

		$NS["ReadBlockCurrent"] = $tar->ReadBlockCurrent;
		$NS["ReadFileSize"] = $tar->ReadFileSize;
		
		if (!haveTime())
		{
			$tar->close();
			CheckPoint();
		}

		$tar->addFile($after_file);
		unlink($NS["dump_name"]) && (!file_exists($after_file) || unlink($after_file));

		$NS['arc_size'] = 0;
		$name = $NS["arc_name"];
		while(file_exists($name))
		{
			$size = filesize($name);
			$NS['arc_size'] += $size;
			if ($arParams["disk_space"] > 0)
				CDiskQuota::updateDiskQuota("file", $size, "add");
			$name = CTar::getNextName($name);
		}
		$tar->close();
	}
	$NS['step'] = 3;
}

if ($NS['step'] == 3)
{
	// Download cloud files
	if ($arParams['dump_do_clouds'] && ($arDumpClouds = CBackup::GetBucketList()))
	{
		ShowBackupStatus('Downloading cloud files');
		foreach($arDumpClouds as $arBucket)
		{
			$id = $arBucket['ID'];
			if ($NS['bucket_finished_'.$id])
				continue;

			$obCloud = new CloudDownload($arBucket['ID']);
			$obCloud->last_bucket_path = $NS['last_bucket_path'];
			if ($res = $obCloud->Scan(''))
			{
				$NS['bucket_finished_'.$id] = true;
			}
			else
			{
				$NS['last_bucket_path'] = $obCloud->path;
				break;
			}
		}

		CheckPoint();
	}
	$NS['step'] = 4;
}

$DB->Disconnect();

if ($NS['step'] == 4)
{
	// Tar files
	if ($arParams['dump_file_public'] || $arParams['dump_file_kernel'])
	{
		ShowBackupStatus('Archiving files');
		$DOCUMENT_ROOT_SITE = DOCUMENT_ROOT;
		if (!defined('DOCUMENT_ROOT_SITE'))
			;// define('DOCUMENT_ROOT_SITE', $DOCUMENT_ROOT_SITE);

		$tar = new CTar;
		$tar->EncryptKey = $NS['dump_encrypt_key'];
		$tar->ArchiveSizeLimit = $arParams['dump_archive_size_limit'];
		$tar->gzip = $arParams['dump_use_compression'];
		$tar->path = DOCUMENT_ROOT_SITE;
		$tar->ReadBlockCurrent = intval($NS['ReadBlockCurrent']);
		$tar->ReadFileSize = intval($NS['ReadFileSize']);

		if (!$tar->openWrite($NS["arc_name"]))
			RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS'), 400, $NS['arc_name']);

		$Block = $tar->Block;
		$DirScan = new CDirRealScan;
		$DirScan->startPath = $NS['startPath'];

		$r = $DirScan->Scan(DOCUMENT_ROOT_SITE);
		$NS["data_size"] += 512 * ($tar->Block - $Block);
		$tar->close();

		if ($r === false)
			RaiseErrorAndDie(implode('<br>',array_merge($tar->err,$DirScan->err)), 410);

		$NS["ReadBlockCurrent"] = $tar->ReadBlockCurrent;
		$NS["ReadFileSize"] = $tar->ReadFileSize;
		$NS["startPath"] = $DirScan->nextPath;
		$NS["cnt"] += $DirScan->FileCount;

		CheckPoint();

		$NS['arc_size'] = 0;
		$name = $NS["arc_name"];
		while(file_exists($name))
		{
			$size = filesize($name);
			$NS['arc_size'] += $size;
			if ($arParams["disk_space"] > 0)
				CDiskQuota::updateDiskQuota("file", $size, "add");
			$name = CTar::getNextName($name);
		}
		DeleteDirFilesEx(BX_ROOT.'/backup/clouds');
	}
	$NS['step'] = 5;
}

if ($NS['step'] == 5)
{
	// Integrity check
	if ($arParams['dump_integrity_check'])
	{
		ShowBackupStatus('Checking archive integrity');
		$tar = new CTarCheck;
		$tar->EncryptKey = $NS['dump_encrypt_key'];

		if (!$tar->openRead($NS["arc_name"]))
			RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS_READ').'<br>'.implode('<br>',$tar->err), 510, $NS['arc_name']);
		else
		{
			if(($Block = intval($NS['Block'])) && !$tar->SkipTo($Block))
				RaiseErrorAndDie(implode('<br>',$tar->err), 520);
			while(($r = $tar->extractFile()) && haveTime());
			$NS["Block"] = $tar->Block;
			if ($r === false)
				RaiseErrorAndDie(implode('<br>',$tar->err), 530, $NS['arc_name']);
		}
		$tar->close();

		CheckPoint();
	}
	$NS['step'] = 6;
}

$DB->DoConnect();

if ($NS['step'] == 6)
{
	// Send to the cloud
	if ($NS['BUCKET_ID'])
	{
		ShowBackupStatus('Sending backup to the cloud');
		if (!CModule::IncludeModule('clouds'))
			RaiseErrorAndDie(GetMessage("MAIN_DUMP_NO_CLOUDS_MODULE"), 600, $NS['arc_name']);

		while(haveTime())
		{
			$file_size = filesize($NS["arc_name"]);
			$file_name = $NS['BUCKET_ID'] == -1 ? basename($NS['arc_name']) : substr($NS['arc_name'],strlen(DOCUMENT_ROOT));
			$obUpload = new CCloudStorageUpload($file_name);

			if ($NS['BUCKET_ID'] == -1)
			{
				if (!$bBitrixCloud)
					RaiseErrorAndDie(getMessage('DUMP_BXCLOUD_NA'), 610);

				$obBucket = null;
				if (!$NS['obBucket'])
				{
					try
					{
						$backup = CBitrixCloudBackup::getInstance();
						$q = $backup->getQuota();
						if ($NS['arc_size'] > $q)
							RaiseErrorAndDie(GetMessage('DUMP_ERR_BIG_BACKUP', array('#ARC_SIZE#' => $NS['arc_size'], '#QUOTA#' => $q)), 620);

						$obBucket = $backup->getBucketToWriteFile(CTar::getCheckword($NS['dump_encrypt_key']), basename($NS['arc_name']));
						$NS['obBucket'] = serialize($obBucket);
					}
					catch (Exception $e)
					{
						unset($NS['obBucket']);
						RaiseErrorAndDie($e->getMessage(), 630);
					}
				}
				else
					$obBucket = unserialize($NS['obBucket']);

				$obBucket->Init();
				$obBucket->GetService()->setPublic(false);

				$bucket_id = $obBucket;
			}
			else
			{
				$obBucket = null;
				$bucket_id = $NS['BUCKET_ID'];
			}

			if (!$obUpload->isStarted())
			{
				if (is_object($obBucket))
					$obBucket->setCheckWordHeader();

				if (!$obUpload->Start($bucket_id, $file_size))
				{
					if ($e = $APPLICATION->GetException())
						$strError = $e->GetString();
					else
						$strError = GetMessage('MAIN_DUMP_INT_CLOUD_ERR');
					RaiseErrorAndDie($strError,640,$NS['arc_name']);
				}

				if (is_object($obBucket))
					$obBucket->unsetCheckWordHeader();
			}

			if (!$fp = fopen($NS['arc_name'],'rb'))
				RaiseErrorAndDie(GetMessage("MAIN_DUMP_ERR_OPEN_FILE").' '.$NS['arc_name'], 650, $NS['arc_name']);
			
			fseek($fp, $obUpload->getPos());
			while($obUpload->getPos() < $file_size && haveTime())
			{
				$part = fread($fp, $obUpload->getPartSize());
				$fails = 0;
				$res = false;
				while($obUpload->hasRetries())
				{
					if($res = $obUpload->Next($part, $obBucket))
						break;
					elseif (++$fails >= 10)
						RaiseErrorAndDie('Internal Error: could not init upload for '.$fails.' times', 660, $NS['arc_name']);
				}

				if (!$res)
				{
					$obUpload->Delete();
					RaiseErrorAndDie(GetMessage("MAIN_DUMP_ERR_FILE_SEND").' '.basename($NS['arc_name']), 670, $NS['arc_name']);
				}
			}
			fclose($fp);

			CheckPoint();

			if($obUpload->Finish($obBucket))
			{
				if ($NS['BUCKET_ID'] != -1)
				{
					$oBucket = new CCloudStorageBucket($NS['BUCKET_ID']);
					$oBucket->IncFileCounter($file_size);
				}

				if (file_exists($arc_name = CTar::getNextName($NS['arc_name'])))
				{
					unset($NS['obBucket']);
					$NS['arc_name'] = $arc_name;
				}
				else
				{
					CBitrixCloudBackup::clearOptions();
					
					if ($arParams['dump_delete_old'] == 1)
					{
						$name = CTar::getFirstName($NS['arc_name']);
						while(file_exists($name))
						{
							$size = filesize($name);
							if (unlink($name) && IntOption("disk_space") > 0)
								CDiskQuota::updateDiskQuota("file",$size , "del");
							$name = CTar::getNextName($name);
						}
					}
					break;
				}
			}
			else
			{
				$obUpload->Delete();
				RaiseErrorAndDie(GetMessage("MAIN_DUMP_ERR_FILE_SEND").basename($NS['arc_name']), 680, $NS['arc_name']);
			}
		}
		CheckPoint();
	}
	$NS['step'] = 7;
}

if ($NS['step'] == 7)
{
	// Delete old backups
	if ($arParams['dump_delete_old'] > 1)
	{
		ShowBackupStatus('Deleting old backups');
		$arFiles = array();

		$TotalSize = $NS['arc_size'];

		if (is_dir($p = DOCUMENT_ROOT.BX_ROOT.'/backup'))
		{
			if ($dir = opendir($p))
			{
				$arc_name = CTar::getFirstName(basename($NS['arc_name']));
				while(($item = readdir($dir)) !== false)
				{
					$f = $p.'/'.$item;
					if (!is_file($f))
						continue;

					if (!preg_match('#\.(sql|tar|gz|enc|[0-9]+)$#', $item))
						continue;

					$name = CTar::getFirstName($item);
					if ($name == $arc_name)
						continue;

					$s = filesize($f);
					$m = filemtime($f);

					$arFiles[$name] = $m;
					$TotalSize += $s;
				}
				closedir($dir);
			}
		}
		asort($arFiles);
		$cnt = count($arFiles) + 1;

		foreach($arFiles as $name => $m)
		{
			switch ($arParams['dump_delete_old'])
			{
				case 2: // time
					if ($m >= time() - 86400 * $arParams['dump_old_time'])
						break 2;
				break;
				case 4: // cnt
					if ($cnt <= $arParams['dump_old_cnt'])
						break 2;
				break;
				case 8: // size
					if ($TotalSize / 1024 / 1024 / 1024 <= $arParams['dump_old_size'])
						break 2;
				break;
				default:
				break;
			}

			$cnt--;
			$f = $p.'/'.$name;
	//		echo "delete ".$f."\n";

			$bDel = false;
			while(file_exists($f))
			{
				$size = filesize($f);
				$TotalSize -= $size;
				if (($bDel = unlink($f)) && $arParams["disk_space"] > 0)
					CDiskQuota::updateDiskQuota("file", $size , "del");
				$f = CTar::getNextName($f);
			}
			if (!$bDel)
				RaiseErrorAndDie('Could not delete file: '.$f, 700, $NS['arc_name']);
		}
	}
	$NS['step'] = 8;
}


$info = "Finished.\n\nData size: ".round($NS['data_size']/1024/1024, 2)." M\nArchive size: ".round($NS['arc_size']/1024/1024, 2)." M\nTime: ".(time() - START_TIME)." sec\n";
ShowBackupStatus($info);
CEventLog::Add(array(
	"SEVERITY" => "WARNING",
	"AUDIT_TYPE_ID" => "BACKUP_SUCCESS",
	"MODULE_ID" => "main",
	"ITEM_ID" => $NS['arc_name'],
	"DESCRIPTION" => $info,
));
$NS = array();
if (defined('LOCK_FILE'))
	unlink(LOCK_FILE) || RaiseErrorAndDie('Can\'t delete file: '.LOCK_FILE, 1000);
if (!CLI)
	echo 'FINISH';
COption::SetOptionInt('main', 'last_backup_end_time', time());
##########################################
########################### Functions ####
function IntOption($name, $def = 0)
{
	global $arParams;
	if (isset($arParams[$name]))
		return $arParams[$name];

	static $CACHE;
	$name .= '_auto';

	if (!$CACHE[$name])
		$CACHE[$name] = COption::GetOptionInt("main", $name, $def);
	return $CACHE[$name];
}

function ShowBackupStatus($str)
{
	if (!CLI && !$_REQUEST['show_status'])
		return;
	static $time, $NS;
	if (!$time)
		$time = microtime(1) + $NS['WORK_TIME'];
	echo round(microtime(1)-$time, 2).' sec	'.$str."\n";
}

function haveTime()
{
	if (!CLI && time() - START_TIME > 30)
		return false;
	return true;
}

function RaiseErrorAndDie($strError, $errCode = 0, $ITEM_ID = '')
{
	global $DB;
	if (CLI)
		echo 'Error ['.$errCode.']: '.str_replace('<br>',"\n",$strError)."\n";
	else
	{
		echo "ERROR_".$errCode."\n".htmlspecialcharsbx($strError)."\n";
	}

	if (is_object($DB))
	{
		$DB->DoConnect();

		CEventLog::Add(array(
			"SEVERITY" => "WARNING",
			"AUDIT_TYPE_ID" => "BACKUP_ERROR",
			"MODULE_ID" => "main",
			"ITEM_ID" => $ITEM_ID,
			"DESCRIPTION" => "[".$errCode."] ".$strError,
		));
	}
	die();
}

function CheckPoint()
{
	if (haveTime())
		return;
	
	global $NS;
	$NS['WORK_TIME'] = microtime(1) - START_TIME;
	session_write_close();
	echo "NEXT";
	exit(0);
}
