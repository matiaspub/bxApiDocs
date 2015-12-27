<?php

namespace Bitrix\Main\UI;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Security\Sign\Signer;
use \Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\UI\Uploader\Status;
use \Bitrix\Main\UI\Uploader\Uploader;
use \Bitrix\Main\UI\Uploader\Error;

Loc::loadMessages(__FILE__);
class FileInputReceiver
{
	protected $signature;
	protected $status = array();
	protected $id = "unknown";
	protected $uploader;

	const STATUS_SUCCESS      = 'success';
	const STATUS_DENIED       = 'denied';
	const STATUS_ERROR        = 'error';
	const STATUS_NEED_AUTH    = 'need_auth';
	const STATUS_INVALID_SIGN = 'invalid_sign';

	public function setSignature($signature)
	{
		$this->signature = $signature;
		return $this;
	}

	protected function getAgent()
	{
		if (!($this->uploader instanceof Uploader))
		{
			$this->uploader = new Uploader(array(), "get");
			$this->uploader->setHandler("onFileIsUploaded", array($this, "handleFile"));
		}
		return $this->uploader;
	}

	protected function check()
	{
		global $USER;
		$this->status = new Status();
		if(!$USER->IsAuthorized())
		{
			$this->status = new Error(self::STATUS_DENIED, Loc::getMessage("BXU_AccessDenied_Authorize"));
		}
		else
		{
			try
			{
				$sign = new Signer;
				$params = unserialize(base64_decode($sign->unsign($this->signature, "fileinput")));
				$this->id = $params["id"];
				$this->getAgent()->setParams($params);
			}
			catch (BadSignatureException $e)
			{
				$this->status = new Error(self::STATUS_INVALID_SIGN, Loc::getMessage("BXU_AccessDenied_SignBetter"));
			}
		}
		return !($this->status instanceof Error);
	}

	public static function sign($params = array())
	{
		$sign = new Signer();
		return $sign->sign(base64_encode(serialize($params)), "fileinput");
	}

	protected static function resizePicture(&$f, $resize = array())
	{
		$file = $f["tmp_name"];
		$orig = ((file_exists($file) && is_file($file)) ? \CFile::GetImageSize($file, true) : false);

		if ($orig)
		{
			$resize = (is_array($resize) ? array_change_key_case($resize, CASE_LOWER) : array());
			$resize = array(
				"compression" => intval($resize["compression"]),
				"method" => ($resize["method"] == "resample" ? "resample" : "resize"),
				"width" => intval($resize["width"]),
				"height" => intval($resize["height"])
			);
			$size = array(
				"width" => $orig[0],
				"height" => $orig[1]
			);
			$orientation = 0;
			$image_type = $orig[2];

			if($image_type == IMAGETYPE_JPEG)
			{
				$exifData = \CFile::extractImageExif($file);
				if ($exifData  && isset($exifData['Orientation']))
				{
					$orientation = $exifData['Orientation'];
					if ($orientation >= 5 && $orientation <= 8)
					{
						$size["width"] = $orig[1];
						$size["height"] = $orig[0];
					}
				}
			}
			$need = false;
			$source = array();
			$destination = array();

			\CFile::scaleImage($size["width"], $size["height"], $resize, BX_RESIZE_IMAGE_PROPORTIONAL, $need, $source, $destination);

			$image = false;
			if ($need || $orientation > 1)
			{
				if($image_type == IMAGETYPE_JPEG)
				{
					$image = imagecreatefromjpeg($file);
					if ($image === false)
					{
						ini_set('gd.jpeg_ignore_warning', 1);
						$image = imagecreatefromjpeg($file);
					}

					if ($orientation > 1)
					{
						if ($orientation == 7 || $orientation == 8)
							$image = imagerotate($image, 90, null);
						elseif ($orientation == 3 || $orientation == 4)
							$image = imagerotate($image, 180, null);
						elseif ($orientation == 5 || $orientation == 6)
							$image = imagerotate($image, 270, null);

						if (
							$orientation == 2 || $orientation == 7
							|| $orientation == 4 || $orientation == 5
						)
						{
							\CFile::ImageFlipHorizontal($image);
						}
					}
				}
				elseif($image_type == IMAGETYPE_GIF)
					$image = imagecreatefromgif($file);
				elseif($image_type == IMAGETYPE_PNG)
					$image = imagecreatefrompng($file);
			}

			if ($image)
			{
				$image_p = imagecreatetruecolor($destination["width"], $destination["height"]);
				if($image_type == IMAGETYPE_JPEG)
				{
					if($resize["method"] === "resample")
						imagecopyresampled($image_p, $image, 0, 0, 0, 0, $destination["width"], $destination["height"], $source["width"], $source["height"]);
					else
						imagecopyresized($image_p, $image, 0, 0, 0, 0, $destination["width"], $destination["height"], $source["width"], $source["height"]);

					if($resize["compression"] > 0)
						imagejpeg($image_p, $file, $resize["compression"]);
					else
						imagejpeg($image_p, $file);
				}
				elseif($image_type == IMAGETYPE_GIF && function_exists("imagegif"))
				{
					imagetruecolortopalette($image_p, true, imagecolorstotal($image));
					imagepalettecopy($image_p, $image);

					//Save transparency for GIFs
					$transparentColor = imagecolortransparent($image);
					if($transparentColor >= 0 && $transparentColor < imagecolorstotal($image))
					{
						$transparentColor = imagecolortransparent($image_p, $transparentColor);
						imagefilledrectangle($image_p, 0, 0, $destination["width"], $destination["height"], $transparentColor);
					}

					if($resize["method"] === "resample")
						imagecopyresampled($image_p, $image, 0, 0, 0, 0, $destination["width"], $destination["height"], $source["width"], $source["height"]);
					else
						imagecopyresized($image_p, $image, 0, 0, 0, 0, $destination["width"], $destination["height"], $source["width"], $source["height"]);
					imagegif($image_p, $file);
				}
				else
				{
					//Save transparency for PNG
					$transparentColor = imagecolorallocatealpha($image_p, 0, 0, 0, 127);
					imagefilledrectangle($image_p, 0, 0, $destination["width"], $destination["height"], $transparentColor);
					$transparentColor = imagecolortransparent($image_p, $transparentColor);

					imagealphablending($image_p, false);
					if($resize["method"] === "resample")
						imagecopyresampled($image_p, $image, 0, 0, 0, 0, $destination["width"], $destination["height"], $source["width"], $source["height"]);
					else
						imagecopyresized($image_p, $image, 0, 0, 0, 0, $destination["width"], $destination["height"], $source["width"], $source["height"]);

					imagesavealpha($image_p, true);
					imagepng($image_p, $file);
				}
				imagedestroy($image_p);

				imagedestroy($image);

				return true;
			}
		}
		return false;
	}

	protected static function handleFileByPath($hash, &$file)
	{
		$key = "default";
		if (self::resizePicture($file["files"][$key], array("method" => "resample")))
		{
			clearstatcache();
			$file["files"][$key]["wasChangedOnServer"] = true;
			$file["files"][$key]["size"] = filesize($file["files"][$key]["tmp_name"]);
			$file["files"][$key]["sizeFormatted"] = \CFile::FormatSize($file["files"][$key]["size"]);
		}

		$docRoot = \CBXVirtualIo::GetInstance()->CombinePath($_SERVER["DOCUMENT_ROOT"]);
		$file["path"] = \CBXVirtualIo::GetInstance()->GetFile($file["files"][$key]["tmp_name"])->GetPathWithName();
		if (strpos($file["path"], $docRoot) === 0)
			$file["path"] = str_replace("//", "/", "/".substr($file["path"], strlen($docRoot)));

		$file["files"][$key]["url"] =
		$file["files"][$key]["tmp_url"] = \Bitrix\Main\IO\Path::convertPhysicalToUri($file["path"]);
		$file["type"] = $file["files"][$key]["type"];
		return true;
	}

	protected static function handleFileByHash($hash, &$file)
	{
		$file["uploadId"] = $hash;
		return true;
	}

	public function handleFile($hash, &$file)
	{
		if ($this->id == "path")
		{
			return self::handleFileByPath($hash, $file);
		}
		return self::handleFileByHash($hash, $file);
	}

	public function exec()
	{
		if (!$this->check())
			$this->getAgent()->showError($this->status);
		$this->getAgent()->checkPost();
	}
}
?>