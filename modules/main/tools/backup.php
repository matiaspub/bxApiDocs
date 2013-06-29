<?php
if (php_sapi_name() != 'cli')
	die("Must be started from command line\n");
if (ini_get('short_open_tag') == 0)
	die("Error: short_open_tag parameter must be turned on in php.ini\n");
?><?
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// define('START_TIME', time());
// define('LANGUAGE_ID', 'en');
// define('NOT_CHECK_PERMISSIONS', true);

if (defined('BX_CRONTAB')) // start from cron_events.php
{
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/dump.php');

	if (!IntOption('dump_auto_enable'))
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
			RaiseErrorAndDie('Can\'t read file: '.LOCK_FILE);

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
			unlink(LOCK_FILE) || RaiseErrorAndDie('Can\'t delete file: '.LOCK_FILE);
		}
	}

	if (!file_put_contents(LOCK_FILE, time()))
		RaiseErrorAndDie('Can\'t create file: '.LOCK_FILE);

	COption::SetOptionInt('main', 'last_backup_start_time', time());
}
else
{
	$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__).'/../../../../');
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/dump.php');
}
if (!defined('DOCUMENT_ROOT'))
	// define('DOCUMENT_ROOT', rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']),'/'));

if (!file_exists(DOCUMENT_ROOT.'/bitrix/backup'))
	mkdir(DOCUMENT_ROOT.'/bitrix/backup');

if (!file_exists(DOCUMENT_ROOT."/bitrix/backup/index.php"))
{
	$f = fopen(DOCUMENT_ROOT."/bitrix/backup/index.php","w");
	fwrite($f,"<head><meta http-equiv=\"REFRESH\" content=\"0;URL=/bitrix/admin/index.php\"></head>");
	fclose($f);
}

while(ob_end_flush());
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/backup.php");

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
	'dump_base' => 1,
	'dump_base_skip_stat' => 0,
	'dump_base_skip_search' => 0,
	'dump_base_skip_log' => 0,

	'dump_file_public' => 1,
	'dump_file_kernel' => 1,
	'dump_do_clouds' => 1,
	'skip_mask' => 0,
	'skip_mask_array' => array(),
	'dump_max_file_size' => 0,
	'skip_symlinks' => 0,
);

if (!is_array($arExpertBackupParams))
	$arExpertBackupParams = array();
	
$arParams = array_merge($arExpertBackupDefaultParams, $arExpertBackupParams, $arParams);
$skip_mask_array = $arParams['skip_mask_array'];

$arParams['dump_encrypt_key'] = CPasswordStorage::Get('dump_temporary_cache');
$dump_bucket_id = IntOption('dump_bucket_id');
if ($dump_bucket_id == -1)
{
	if (!$bBitrixCloud || !$arParams['dump_encrypt_key'])
	{
		$dump_bucket_id = 0;
		ShowBackupStatus('BitrixCloud is not available');
	}
}
$arParams['dump_bucket_id'] = $dump_bucket_id;
$NS = array('BUCKET_ID' => $dump_bucket_id);

if ($dump_bucket_id == -1)
	$arc_name = DOCUMENT_ROOT.BX_ROOT."/backup/".date('Ymd_His_').rand(11111111,99999999);
elseif(($arc_name = $argv[1]) && !is_dir($arc_name))
	$arc_name =  str_replace(array('.tar','.gz','.enc'),'',$arc_name);
else
	$arc_name = CBackup::GetArcName();

$NS['arc_name'] = $arc_name.($arParams['dump_encrypt_key'] ? ".enc" : ".tar").($arParams['dump_use_compression'] ? ".gz" : '');
$NS['dump_name'] = $arc_name.'.sql';

$after_file = str_replace('.sql','_after_connect.sql',$NS['dump_name']);
ShowBackupStatus('Backup started to file: '.$NS['arc_name']);

// dump database
if ($arParams['dump_base'])
{
	ShowBackupStatus('Dumping database');
	if (!CBackup::MakeDump($NS['dump_name'], $arState = ''))
		RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS', $NS['dump_name']));

	$rs = $DB->Query('SHOW VARIABLES LIKE "character_set_results"');
	if (($f = $rs->Fetch()) && array_key_exists ('Value', $f))
		file_put_contents($after_file, "SET NAMES '".$f['Value']."';\n");

	$rs = $DB->Query('SHOW VARIABLES LIKE "collation_database"');
	if (($f = $rs->Fetch()) && array_key_exists ('Value', $f))
		file_put_contents($after_file, "ALTER DATABASE `<DATABASE>` COLLATE ".$f['Value'].";\n",8);

	ShowBackupStatus('Archiving database dump');
	$tar = new CTar;
	$tar->EncryptKey = $arParams['dump_encrypt_key'];
	$tar->ArchiveSizeLimit = $arParams['dump_archive_size_limit'];
	$tar->gzip = $arParams['dump_use_compression'];
	$tar->path = DOCUMENT_ROOT;

	if (!$tar->openWrite($NS["arc_name"]))
		RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS'), $NS['arc_name']);

	if (!$tar->ReadBlockCurrent && file_exists($f = DOCUMENT_ROOT.BX_ROOT.'/.config.php'))
		$tar->addFile($f);

	$Block = $tar->Block;
	while(($r = $tar->addFile($NS['dump_name'])) && $tar->ReadBlockCurrent > 0);
	$NS["data_size"] += 512 * ($tar->Block - $Block);
	
	if ($r === false)
		RaiseErrorAndDie(implode('<br>',$tar->err), $NS['arc_name']);

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

// Download cloud files
if ($arDumpClouds = CBackup::GetBucketList())
{
	ShowBackupStatus('Downloading cloud files');
	foreach($arDumpClouds as $arBucket)
	{
		$obCloud = new CloudDownload($arBucket['ID']);
		$res = $obCloud->Scan('');
	}
}

$DB->Disconnect();

// Tar files
if ($arParams['dump_file_public'] || $arParams['dump_file_kernel'])
{
	ShowBackupStatus('Archiving files');
	$DOCUMENT_ROOT_SITE = DOCUMENT_ROOT;
	if (!defined('DOCUMENT_ROOT_SITE'))
		// define('DOCUMENT_ROOT_SITE', $DOCUMENT_ROOT_SITE);

	$tar = new CTar;
	$tar->EncryptKey = $arParams['dump_encrypt_key'];
	$tar->ArchiveSizeLimit = $arParams['dump_archive_size_limit'];
	$tar->gzip = $arParams['dump_use_compression'];
	$tar->path = DOCUMENT_ROOT_SITE;

	if (!$tar->openWrite($NS["arc_name"]))
		RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS'), $NS['arc_name']);

	$Block = $tar->Block;
	$DirScan = new CDirRealScan;

	$r = $DirScan->Scan(DOCUMENT_ROOT_SITE);
	$NS["data_size"] += 512 * ($tar->Block - $Block);
	$tar->close();

	if ($r === false)
		RaiseErrorAndDie(implode('<br>',array_merge($tar->err,$DirScan->err)));

	$NS["cnt"] += $DirScan->FileCount;

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

// Integrity check
if ($arParams['dump_integrity_check'])
{
	ShowBackupStatus('Checking archive integrity');
	$tar = new CTarCheck;
	$tar->EncryptKey = $arParams['dump_encrypt_key'];

	if (!$tar->openRead($NS["arc_name"]))
		RaiseErrorAndDie(GetMessage('DUMP_NO_PERMS_READ').'<br>'.implode('<br>',$tar->err), $NS['arc_name']);
	else
	{
		while($r = $tar->extractFile());
		if ($r === false)
			RaiseErrorAndDie(implode('<br>',$tar->err), $NS['arc_name']);
	}
	$tar->close();
}

$DB->DoConnect();

// Send to the cloud
if ($arParams['dump_bucket_id'])
{
	ShowBackupStatus('Sending backup to the cloud');
	if (!CModule::IncludeModule('clouds'))
		RaiseErrorAndDie(GetMessage("MAIN_DUMP_NO_CLOUDS_MODULE"), $NS['arc_name']);

	while(true)
	{
		$file_size = filesize($NS["arc_name"]);
		$file_name = $NS['BUCKET_ID'] == -1 ? basename($NS['arc_name']) : substr($NS['arc_name'],strlen(DOCUMENT_ROOT));
		$obUpload = new CCloudStorageUpload($file_name);

		if ($NS['BUCKET_ID'] == -1)
		{
			if (!$bBitrixCloud)
				RaiseErrorAndDie(getMessage('DUMP_BXCLOUD_NA'), $NS['arc_name']);
			try {
				$backup = CBitrixCloudBackup::getInstance();
				$q = $backup->getQuota();
				if ($NS['arc_size'] > $q)
					RaiseErrorAndDie(GetMessage('DUMP_ERR_BIG_BACKUP', array('#ARC_SIZE#' => $NS['arc_size'], '#QUOTA#' => $q)), $NS['arc_name']);

				$obBucket = $backup->getBucketToWriteFile(CTar::getCheckword($arParams['dump_encrypt_key']), basename($NS['arc_name']));
			}
			catch (Exception $e) {
				RaiseErrorAndDie($e->getMessage(),$NS['arc_name']);
			}

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
				RaiseErrorAndDie($strError,$NS['arc_name']);
			}

			if (is_object($obBucket))
				$obBucket->unsetCheckWordHeader();
		}

		if (!$fp = fopen($NS['arc_name'],'rb'))
			RaiseErrorAndDie(GetMessage("MAIN_DUMP_ERR_OPEN_FILE").' '.$NS['arc_name'],$NS['arc_name']);

		while($obUpload->getPos() < $file_size)
		{
			fseek($fp, $obUpload->getPos());
			$part = fread($fp, $obUpload->getPartSize());
			$fails = 0;
			$res = false;
			while($obUpload->hasRetries())
			{
				if($res = $obUpload->Next($part, $obBucket))
					break;
				elseif (++$fails >= 10)
					RaiseErrorAndDie('Internal Error: could not init upload for '.$fails.' times', $NS['arc_name']);
			}

			if (!$res)
			{
				$obUpload->Delete();
				RaiseErrorAndDie(GetMessage("MAIN_DUMP_ERR_FILE_SEND").' '.basename($NS['arc_name']), $NS['arc_name']);
			}
		}
		fclose($fp);

		if($obUpload->Finish($obBucket))
		{
			if ($NS['BUCKET_ID'] != -1)
			{
				$oBucket = new CCloudStorageBucket($NS['BUCKET_ID']);
				$oBucket->IncFileCounter($file_size);
			}

			if (file_exists($arc_name = CTar::getNextName($NS['arc_name'])))
			{
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
			RaiseErrorAndDie(GetMessage("MAIN_DUMP_ERR_FILE_SEND").basename($NS['arc_name']),$NS['arc_name']);
		}
	}
}

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
			RaiseErrorAndDie('Could not delete file: '.$f, $NS['arc_name']);
	}
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
if (defined('LOCK_FILE'))
	unlink(LOCK_FILE) || RaiseErrorAndDie('Can\'t delete file: '.LOCK_FILE);
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
	static $time;
	if (!$time)
		$time = microtime(1);
	echo round(microtime(1)-$time, 2).' sec	'.$str."\n";
}

function haveTime()
{
	return true;
}

function RaiseErrorAndDie($strError, $ITEM_ID = '')
{
	global $DB;
	echo 'Error: '.str_replace('<br>',"\n",$strError)."\n";
	if (is_object($DB))
	{
		$DB->DoConnect();

		CEventLog::Add(array(
			"SEVERITY" => "WARNING",
			"AUDIT_TYPE_ID" => "BACKUP_ERROR",
			"MODULE_ID" => "main",
			"ITEM_ID" => $ITEM_ID,
			"DESCRIPTION" => $strError,
		));
	}
	die();
}
