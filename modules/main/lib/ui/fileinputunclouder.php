<?php
namespace Bitrix\Main\UI;
use \Bitrix\Main\Security\Sign\Signer;
use \Bitrix\Main\Security\Sign\BadSignatureException;

class FileInputUnclouder
{
	protected $id;
	protected $signature;
	protected $file;
	protected static $salt = "fileinput";

	public static function getSrc($file = array())
	{
		$src = $file['SRC'];
		if ($file['HANDLER_ID'] > 0)
		{
			$src = "/".\COption::GetOptionString("main", "upload_dir", "upload")."/".$file["SUBDIR"]."/".$file["FILE_NAME"];
			$path = $_SERVER["DOCUMENT_ROOT"].$src;
			if (!(is_file($path) && file_exists($path)))
			{
				$sign = new Signer();
				$s = $sign->sign($file["ID"], self::$salt);
				$src = \COption::GetOptionString("main.fileinput", "entryPointUrl", "/bitrix/tools/upload.php")."?".
					http_build_query(array(
					"action" => "uncloud",
					"file" => $file["ID"],
					"signature" => $s
				));
			}
		}
		else
		{
			$src = \Bitrix\Main\IO\Path::convertLogicalToUri($src);
		}
		return $src;
	}

	public static function getSrcWithResize($file = array(), $size = array())
	{
		$file1 = \CFile::ResizeImageGet($file["ID"], $size, BX_RESIZE_IMAGE_PROPORTIONAL, false);
		$src = $file1['src'];
		if ($file['HANDLER_ID'] > 0)
		{
			$src = "/".\COption::GetOptionString("main", "upload_dir", "upload")."/".$file["SUBDIR"]."/".$file["FILE_NAME"];
			$path = $_SERVER["DOCUMENT_ROOT"].$src;
			if (!(is_file($path) && file_exists($path)))
			{
				$sign = new Signer();
				$s = $sign->sign($file["ID"] . "x" . $size["width"]. "x" . $size["height"], self::$salt);
				$src = \COption::GetOptionString("main.fileinput", "entryPointUrl", "/bitrix/tools/upload.php")."?".
					http_build_query(array(
					"action" => "uncloud",
					"mode" => "resize",
					"file" => $file["ID"],
					"width" => $size["width"],
					"height" => $size["height"],
					"signature" => $s
				));
			}
		}
		else
		{
			$src = \Bitrix\Main\IO\Path::convertLogicalToUri($src);
		}
		return $src;
	}


	public function setValue($id)
	{
		$this->id = (int) $id;
		return $this;
	}

	public function setSignature($signature)
	{
		$this->signature = $signature;
		return $this;
	}

	protected function check($params = array())
	{
		$sign = new Signer;

		$str = (string) $sign->unsign($this->signature, self::$salt);
		$str2 = (string) $this->id;

		if (is_array($params) && array_key_exists("width", $params) && $params["width"] > 0 && array_key_exists("height", $params) && $params["height"] > 0)
		{
			$str2 = $this->id . "x" . $params["width"] . "x" . $params["height"];
		}
		return ($str == $str2);
	}

	public function exec($mode = "basic", $params = array())
	{
		$res = $this->check($params);
		if ($this->check($params))
		{
			$this->file = \CFile::getByID($this->id)->fetch();
			if ($mode == "resize" && ($file = \CFile::ResizeImageGet($this->id, $params, BX_RESIZE_IMAGE_PROPORTIONAL, true)) && $file)
			{
				$this->file["SRC"] = $file["src"];
				$this->file["WIDTH"] = $file["width"];
				$this->file["HEIGHT"] = $file["height"];
				$this->file["FILE_SIZE"] = $file["size"];
			}
			\CFile::ViewByUser($this->file, array("force_download" => false, 'cache_time' => 0));
		}
	}
}