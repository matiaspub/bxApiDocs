<?php
namespace Bitrix\Lists;

use Bitrix\Main;

Main\Loader::includeModule("iblock");
Main\Loader::includeModule("bizproc");

/**
 * Class Importer
 * @package Bitrix\Lists
 *
 *
 * 	$APPLICATION->RestartBuffer();
 *	if (есть права)
 *	{
 *      $id = iblock ID
 *		$datum = \Bitrix\Lists\Importer::export($id);
 *
 *		header("HTTP/1.1 200 OK");
 *		header("Content-Type: application/force-download; name=\"bp-".$id.".prc\"");
 *		header("Content-Transfer-Encoding: binary");
 *		header("Content-Length: ".(Main\Text\String::getBinaryLength($datum)));
 *		header("Content-Disposition: attachment; filename=\"bp-".$id.".prc\"");
 *		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
 *		header("Expires: 0");
 *		header("Pragma: public");
 *
 *		echo $datum;
 *	}
 *	die();
 *
 *
 *
 * 	if (is_uploaded_file($_FILES['import_file']['tmp_name']))
 *	{
 *		$f = fopen($_FILES['import_file']['tmp_name'], "rb");
 *		$datum = fread($f, filesize($_FILES['import_file']['tmp_name']));
 *		fclose($f);
 *
 *      \Bitrix\Lists\Importer::import("iblock type", $datum);
 *  }
 *
 */
class Importer
{
	const DIRECTION_EXPORT = 0;
	const DIRECTION_IMPORT = 1;

 	/**
	 * @param int $iblockId This variable is the id iblock.
	 * @return string
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
public static 	public static function export($iblockId)
	{
		$iblockId = intval($iblockId);
		if ($iblockId <= 0)
			throw new Main\ArgumentNullException("iblockId");

		$db = \CIBlock::GetList(Array(), Array("ID" => $iblockId, "CHECK_PERMISSIONS" => "N"));
		$iblock = $db->Fetch();
		if (!$iblock)
			throw new Main\ArgumentOutOfRangeException("iblockId");

		if(!$iblock["CODE"])
			throw new Main\ArgumentException("Parameter 'CODE' is required.", "matches");

		$list = new \CList($iblockId);
		$fields = $list->getFields();
		foreach($fields as $fieldId => $field)
		{
			if ($field["TYPE"] == "NAME")
			{
				$iblock["~NAME_FIELD"] = array(
					"NAME" => $field["NAME"],
					"SETTINGS" => $field["SETTINGS"],
					"DEFAULT_VALUE" => $field["DEFAULT_VALUE"],
					"SORT" => $field["SORT"],
				);
				break;
			}
		}

		$iblockUtf8 = Main\Text\Encoding::convertEncodingArray($iblock, LANG_CHARSET, "UTF-8");
		$iblockUtf8 = serialize($iblockUtf8);
		$iblockUtf8Length = Main\Text\String::getBinaryLength($iblockUtf8);
		$datum = str_pad($iblockUtf8Length, 10, "0", STR_PAD_LEFT).$iblockUtf8;

		if (intval($iblock["PICTURE"]) > 0)
		{
			$picture = \CFile::MakeFileArray($iblock["PICTURE"]);
			if (isset($picture["tmp_name"]) && !empty($picture["tmp_name"]))
			{
				$f = fopen($picture["tmp_name"], "rb");
				$pictureData = fread($f, filesize($picture["tmp_name"]));
				fclose($f);

				$pictureTypeLength = Main\Text\String::getBinaryLength($picture["type"]);
				$pictureLength = Main\Text\String::getBinaryLength($pictureData);
				$datum .= "P".str_pad($pictureTypeLength, 10, "0", STR_PAD_LEFT).$picture["type"].str_pad($pictureLength, 10, "0", STR_PAD_LEFT).$pictureData;
			}
		}

		$documentType = self::getDocumentType($iblock["IBLOCK_TYPE_ID"], $iblockId);

		$templatesList = \CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType),
			false,
			false,
			array("ID", "AUTO_EXECUTE", "NAME", "DESCRIPTION", "SYSTEM_CODE")
		);
		while ($templatesListItem = $templatesList->Fetch())
		{
			$bpDescrUtf8 = Main\Text\Encoding::convertEncodingArray($templatesListItem, LANG_CHARSET, "UTF-8");
			$bpDescrUtf8 = serialize($bpDescrUtf8);
			$bpDescrUtf8Length = Main\Text\String::getBinaryLength($bpDescrUtf8);
			$datum .= "B".str_pad($bpDescrUtf8Length, 10, "0", STR_PAD_LEFT).$bpDescrUtf8;

			$bp = \CBPWorkflowTemplateLoader::ExportTemplate($templatesListItem["ID"], false);
			$bpLength = Main\Text\String::getBinaryLength($bp);
			$datum .= str_pad($bpLength, 10, "0", STR_PAD_LEFT).$bp;
		}

		if (function_exists("gzcompress"))
			$datum = "compressed".gzcompress($datum, 9);

		return $datum;
	}

	/**
	 * @param string $filePath This variable is the path to the file to get the data.
	 * @return array
	 */
public static 	public static function getDataProcess($filePath)
	{
		$f = fopen($filePath, "rb");
		$datum = fread($f, filesize($filePath));
		fclose($f);

		if (substr($datum, 0, 10) === "compressed")
			$datum = gzuncompress(Main\Text\String::getBinarySubstring($datum, 10));

		$len = intval(Main\Text\String::getBinarySubstring($datum, 0, 10));
		$dataSerialized = Main\Text\String::getBinarySubstring($datum, 10, $len);

		$data = CheckSerializedData($dataSerialized) ? unserialize($dataSerialized) : array();
		$data = Main\Text\Encoding::convertEncodingArray($data, "UTF-8", LANG_CHARSET);

		return $data;
	}

	/**
	 * @param string $iblockType This variable is the id iblockType.
	 * @param string $datum This variable is the encrypted string.
	 * @param null $siteId This variable is the id current site.
	 * @throws Main\ArgumentNullException
	 */
public static 	public static function import($iblockType, $datum, $siteId = null)
	{
		if (empty($datum))
			throw new Main\ArgumentNullException("datum");

		if (substr($datum, 0, 10) === "compressed")
			$datum = gzuncompress(Main\Text\String::getBinarySubstring($datum, 10));

		$len = intval(Main\Text\String::getBinarySubstring($datum, 0, 10));
		$iblockSerialized = Main\Text\String::getBinarySubstring($datum, 10, $len);
		$datum = Main\Text\String::getBinarySubstring($datum, $len + 10);

		$marker = Main\Text\String::getBinarySubstring($datum, 0, 1);
		$picture = null;
		$pictureType = null;
		if ($marker == "P")
		{
			$len = intval(Main\Text\String::getBinarySubstring($datum, 1, 10));
			$pictureType = Main\Text\String::getBinarySubstring($datum, 11, $len);
			$datum = Main\Text\String::getBinarySubstring($datum, $len + 11);

			$len = intval(Main\Text\String::getBinarySubstring($datum, 0, 10));
			$picture = Main\Text\String::getBinarySubstring($datum, 10, $len);
			$datum = Main\Text\String::getBinarySubstring($datum, $len + 10);

			$marker = Main\Text\String::getBinarySubstring($datum, 0, 1);
		}

		$iblock = CheckSerializedData($iblockSerialized) ? unserialize($iblockSerialized) : array();
		$iblock = Main\Text\Encoding::convertEncodingArray($iblock, "UTF-8", LANG_CHARSET);
		$iblockId = static::createIBlock($iblockType, $iblock, $pictureType, $picture, $siteId);

		if ($iblockId > 0)
		{
			$documentType = self::getDocumentType($iblockType, $iblockId);

			while (!empty($datum))
			{
				if ($marker == "B")
				{
					$len = intval(Main\Text\String::getBinarySubstring($datum, 1, 10));
					$bpDescr = Main\Text\String::getBinarySubstring($datum, 11, $len);
					$datum = Main\Text\String::getBinarySubstring($datum, $len + 11);

					$bpDescr = CheckSerializedData($bpDescr) ? unserialize($bpDescr) : array();
					$bpDescr = Main\Text\Encoding::convertEncodingArray($bpDescr, "UTF-8", LANG_CHARSET);

					$len = intval(Main\Text\String::getBinarySubstring($datum, 0, 10));
					$bp = Main\Text\String::getBinarySubstring($datum, 10, $len);
					$datum = Main\Text\String::getBinarySubstring($datum, $len + 10);

					static::importTemplate($documentType, $bpDescr, $bp);
				}
				else
				{

				}

				if (empty($datum))
					break;

				$marker = Main\Text\String::getBinarySubstring($datum, 0, 1);
			}
		}
	}

	private static function importTemplate($documentType, $bpDescr, $bp)
	{
		$id = 0;

		$db = \CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType, "SYSTEM_CODE" => $bpDescr["SYSTEM_CODE"]),
			false,
			false,
			array("ID", "IS_MODIFIED")
		);
		if ($res = $db->Fetch())
		{
			if ($res["IS_MODIFIED"] == "Y")
				return;

			$id = $res["ID"];
		}

		try
		{
			\CBPWorkflowTemplateLoader::ImportTemplate(
				$id,
				$documentType,
				$bpDescr["AUTO_EXECUTE"],
				$bpDescr["NAME"],
				$bpDescr["DESCRIPTION"],
				$bp,
				$bpDescr["SYSTEM_CODE"],
				true
			);
		}
		catch (\Exception $e)
		{
		}
	}

	private static function createIBlock($iblockType, $iblock, $pictureType, $picture, $siteId = null)
	{
		if (is_null($siteId))
			$siteId = \CSite::GetDefSite();

		$db = \CIBlock::GetList(
			array(),
			array("IBLOCK_TYPE_ID" => $iblockType, "CODE" => $iblock["CODE"], "CHECK_PERMISSIONS" => "N", "SITE_ID" => $siteId)
		);
		if ($res = $db->Fetch())
			return $res["ID"];

		$fields = array(
			"NAME" => $iblock["NAME"],
			"DESCRIPTION" => $iblock["DESCRIPTION"],
			"IBLOCK_TYPE_ID" => $iblockType,
			"SORT" => $iblock["SORT"],
			"CODE" => $iblock["CODE"],
			"WORKFLOW" => "N",
			"ELEMENTS_NAME" => $iblock["ELEMENTS_NAME"],
			"ELEMENT_NAME" => $iblock["ELEMENT_NAME"],
			"ELEMENT_ADD" => $iblock["ELEMENT_ADD"],
			"ELEMENT_EDIT" => $iblock["ELEMENT_EDIT"],
			"ELEMENT_DELETE" => $iblock["ELEMENT_DELETE"],
			"SECTIONS_NAME" => $iblock["SECTIONS_NAME"],
			"SECTION_NAME" => $iblock["SECTION_NAME"],
			"SECTION_ADD" => $iblock["SECTION_ADD"],
			"SECTION_EDIT" => $iblock["SECTION_EDIT"],
			"SECTION_DELETE" => $iblock["SECTION_DELETE"],
			"BIZPROC" => "Y",
			"SITE_ID" => array($siteId),
			"RIGHTS_MODE" => "E",
		);

		if ($iblock["SOCNET_GROUP_ID"])
			$fields["SOCNET_GROUP_ID"] = $iblock["SOCNET_GROUP_ID"];

		static $exts = array(
			"image/jpeg" => "jpg",
			"image/png" => "png",
			"image/gif" => "gif",
		);
		if (!empty($picture) && isset($exts[$pictureType]))
		{
			$fn = \CTempFile::GetFileName();
			Main\IO\Directory::createDirectory($fn);

			$fn .= md5(mt_rand()).".".$exts[$pictureType];

			$f = fopen($fn, "wb");
			fwrite($f, $picture);
			fclose($f);

			$fields["PICTURE"] = \CFile::MakeFileArray($fn/*, $pictureType*/);
		}

		$ob = new \CIBlock;
		$res = $ob->Add($fields);
		if ($res)
		{
			self::createIBlockRights($res);

			$list = new \CList($res);

			if (isset($iblock["~NAME_FIELD"]))
				$list->UpdateField("NAME", $iblock["~NAME_FIELD"]);

			$list->Save();

			\CLists::setLiveFeed(1, $res);

			return $res;
		}

		return 0;
	}

	protected static function getIBlockType()
	{
		$iblockType = Main\Config\Option::get("lists", "livefeed_iblock_type_id", "bitrix_processes");
		if (empty($iblockType))
			$iblockType = "bitrix_processes";

		return $iblockType;
	}

	protected static function getDocumentType($iblockType, $iblockId)
	{
		if ($iblockType == static::getIBlockType())
			$documentType = array('lists', 'BizprocDocument', 'iblock_'.$iblockId);
		else
			$documentType = array('iblock', 'CIBlockDocument', 'iblock_'.$iblockId);

		return $documentType;
	}

	/**
	 * @param int $iblockId This variable is the id iblock.
	 */
	private static function createIBlockRights($iblockId)
	{
		$rightObject = new \CIBlockRights($iblockId);
		$rights = $rightObject->getRights();
		$rightsList = $rightObject->getRightsList(false);

		$rightId = array_search('iblock_full', $rightsList);
		$rights['n0'] = array('GROUP_CODE' => "G1", 'TASK_ID' => $rightId);
		$rights['n1'] = array('GROUP_CODE' => "U1", 'TASK_ID' => $rightId);

		$rightId = array_search('iblock_element_add', $rightsList);
		$rights['n2'] = array('GROUP_CODE' => "G2", 'TASK_ID' => $rightId);

		$rightObject->setRights($rights);
	}

	const PATH = "/bitrix/modules/lists/install/bizproc/process/";
	const PATH_USER_PROCESSES = "/bitrix/lists/processes/";

	/**
	 * @param string $lang This variable is the value language.
	 * @param null $siteId This variable is the id current site.
	 * @throws Main\ArgumentNullException
	 * @throws Main\IO\FileNotFoundException
	 */
public static 	public static function installProcesses($lang, $siteId = null)
	{
		if (empty($lang))
			throw new Main\ArgumentNullException("lang");

		if (! Main\Loader::includeModule("bizproc"))
			return;

		$iblockType = static::getIBlockType();

		$db = \CIBlockType::GetList(array(), array("=ID" => $iblockType));
		$res = $db->Fetch();
		if (!$res)
			static::createIBlockType();

		$dir = new Main\IO\Directory(Main\Loader::getDocumentRoot() . static::PATH . $lang . "/");
		if ($dir->isExists())
		{
			$children = $dir->getChildren();
			foreach ($children as $child)
			{
				/** @var Main\IO\File $child */
				if ($child->isFile() && ($child->getExtension() == "prc"))
				{
					static::import($iblockType, $child->getContents(), $siteId);
				}
			}
		}
	}

	/**
	 * @param string $path This variable is the path to the file for the installation process.
	 * @param null $siteId This variable is the id current site.
	 * @throws Main\ArgumentNullException
	 * @throws Main\IO\FileNotFoundException
	 */
public static 	public static function installProcess($path, $siteId = null)
	{
		if (empty($path))
			throw new Main\ArgumentNullException("path");

		if (!Main\Loader::includeModule("bizproc"))
			return;

		$path = Main\Loader::getDocumentRoot() . $path;
		$iblockType = static::getIBlockType();

		$db = \CIBlockType::GetList(array(), array("=ID" => $iblockType));
		$res = $db->Fetch();
		if (!$res)
			static::createIBlockType();

		$file = new Main\IO\File($path);
		if($file->isExists() && $file->getExtension() == "prc")
		{
			static::import($iblockType, $file->getContents(), $siteId);
		}
	}

	/**
	 * @param string $lang This variable is the value language.
	 * @param bool $systemProcesses Installing the system processes.
	 * @param string $path This variable is the path to the file to get the data.
	 * @param array $fileData Array for loading the data.
	 * @throws Main\ArgumentNullException
	 * @throws Main\IO\FileNotFoundException
	 */
public static 	public static function loadDataProcesses($lang, $systemProcesses = true, &$fileData, $path = null)
	{
		if (empty($lang))
			throw new Main\ArgumentNullException("lang");

		if(!empty($path))
		{
			$path = $path."/";
		}
		else
		{
			if($systemProcesses)
			{
				$path = Main\Loader::getDocumentRoot() . static::PATH . $lang . "/";
			}
			else
			{
				$path = Main\Loader::getDocumentRoot() . static::PATH_USER_PROCESSES . $lang . "/";
			}
		}

		$dir = new Main\IO\Directory($path);
		if ($dir->isExists())
		{
			$children = $dir->getChildren();
			foreach ($children as $key => $child)
			{
				/** @var Main\IO\File $child */
				if ($child->isFile() && ($child->getExtension() == "prc"))
				{
					$data = self::getDataProcess($path.$child->getName());
					$fileData[$data['CODE']]['FILE_NAME'] = $child->getName();
					$fileData[$data['CODE']]['FILE_PATH'] = str_replace(Main\Loader::getDocumentRoot(), '', $child->getPath());
					$fileData[$data['CODE']]['NAME'] = $data['NAME'];
					$fileData[$data['CODE']]['DESCRIPTION'] = $data['DESCRIPTION'];
					$fileData[$data['CODE']]['CODE'] = $data['CODE'];
					$fileData[$data['CODE']]['IBLOCK_TYPE_ID'] = $data['IBLOCK_TYPE_ID'];
					$fileData[$data['CODE']]['DIRECTORY_NAME'] = $child->getDirectory()->getName();
				}
				elseif($child->isDirectory())
				{
					self::loadDataProcesses($lang, $systemProcesses, $fileData, $child->getPath());
				}
			}
		}
	}

	protected static function createIBlockType()
	{
		$iblockType = array(
			'ID' => 'bitrix_processes',
			'SECTIONS' => 'Y',
			'SORT' => 500,
			'LANG' => array(),
		);

		$by = "lid";
		$order = "asc";
		$langList = \CLanguage::GetList($by, $order, array("ACTIVE" => "Y"));
		while ($lang = $langList->Fetch())
			$iblockType['LANG'][$lang['LID']]['NAME'] = "Processes";

		$iblockTypeList = \CIBlockType::GetList(array(), array('=ID' => $iblockType['ID']));
		$res = $iblockTypeList->fetch();
		if (!$res)
		{
			$iblockTypeObject = new \CIBlockType;
			$iblockTypeObject->add($iblockType);

			$con = Main\Application::getConnection();
			$con->queryExecute("
				insert into b_lists_permission (IBLOCK_TYPE_ID, GROUP_ID)
				select 'bitrix_processes', p.GROUP_ID
				from
					b_lists_permission p
					left join b_lists_permission p2 on p2.GROUP_ID = p.GROUP_ID and p2.IBLOCK_TYPE_ID = 'bitrix_processes'
				where
					p.IBLOCK_TYPE_ID = 'lists'
					and p2.IBLOCK_TYPE_ID is null
			");

			global $CACHE_MANAGER;
			$CACHE_MANAGER->Clean("b_lists_permission");
		}

		Main\Config\Option::set("lists", "livefeed_iblock_type_id", "bitrix_processes");
	}

	/**
	 * @param string $lang This variable is the value language.
	 * @return string
	 * @throws Main\ArgumentNullException
	 */
public static 	public static function onAgent($lang)
	{
		self::installProcesses($lang);
		return "";
	}

public static 	public static function migrateList($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new Main\ArgumentNullException("id");

		$db = \CIBlock::GetList(
			array(),
			array("ID" => $id, "IBLOCK_TYPE_ID" => "lists", "CHECK_PERMISSIONS" => "N")
		);
		$iblock = $db->Fetch();
		if (!$iblock)
			throw new Main\ArgumentOutOfRangeException("id");

		$iblockType = static::getIBlockType();

		$ob = new \CIBlock;
		$res = $ob->Update($id, array("IBLOCK_TYPE_ID" => $iblockType));
		if ($res)
		{
			\CLists::setLiveFeed(1, $id);
		}

		\CBPDocument::MigrateDocumentType(
			array("iblock", "CIBlockDocument", "iblock_".$id),
			array("lists", "BizprocDocument", "iblock_".$id)
		);
	}
}