<?
IncludeModuleLangFile(__FILE__);
$GLOBALS["BLOG_IMAGE"] = Array();

class CAllBlogImage
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB;
		
		if (is_set($arFields, "FILE_ID"))
		{
			if (is_array($arFields['FILE_ID']))
			{
				if (strlen($arFields["FILE_ID"]["name"]) <= 0 && strlen($arFields["FILE_ID"]["del"]) <= 0)
				{
					unset($arFields["FILE_ID"]);
				}

				$arFile = $arFields["FILE_ID"];
			}
			else
			{
				$arFields['FILE_ID'] = intval($arFields['FILE_ID']);
				if (
					($arFields['FILE_ID'] > 0) &&
					( $arFile = CFile::GetFileArray($arFields['FILE_ID']) )
				)
				{
					$res = CFile::CheckImageFile($arFile, 0, 0, 0);
					if (strlen($res) > 0)
					{
						$GLOBALS["APPLICATION"]->ThrowException($res, "ERROR_ATTACH_IMG");
						return false;
					}
				}
			}
					
			if($arFields["IMAGE_SIZE_CHECK"] != "N" &&IntVal($arFields["IMAGE_SIZE"]) > 0 && IntVal($arFields["IMAGE_SIZE"]) > COption::GetOptionString("blog", "image_max_size", 5000000))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("ERROR_ATTACH_IMG_SIZE", Array("#SIZE#" => DoubleVal(COption::GetOptionString("blog", "image_max_size", 5000000)/1000000))), "ERROR_ATTACH_IMG_SIZE");
				return false;
			}
			unset($arFields["IMAGE_SIZE_CHECK"]);
		}

		return True;
	}

	public static function ImageFixSize($aFile)
	{
		$file = $aFile['tmp_name'];
		preg_match("#/([a-z]+)#ies", $aFile['type'], $regs);
		$ext_tmp = $regs[1];

		$sizeX = COption::GetOptionString("blog", "image_max_width", 600);
		$sizeY = COption::GetOptionString("blog", "image_max_height", 600);

		switch ($ext_tmp)
		{
			case 'jpeg':
			case 'pjpeg':			
			case 'jpg':
				if(!function_exists("imageJPEG") || !function_exists("imagecreatefromjpeg"))
					return false;
			break;
			case 'gif':
				if(!function_exists("imageGIF") || !function_exists("imagecreatefromgif"))
					return false;
			break;
			case 'png':
				if(!function_exists("imagePNG") || !function_exists("imagecreatefrompng"))
					return false;
			break;
		}
	
		switch ($ext_tmp)
		{
			case 'jpeg':
			case 'pjpeg':
			case 'jpg':
				$imageInput = imagecreatefromjpeg($file);
				$ext_tmp = 'jpg';
			break;
			case 'gif':
				$imageInput = imagecreatefromgif($file);
			break;
			case 'png':
				$imageInput = imagecreatefrompng($file);
			break;
		}

		$imgX = imagesx($imageInput);
		$imgY = imagesy($imageInput);
		
		if ($imgX > $sizeX || $imgY > $sizeY)
		{
			$newX = $sizeX;
			$newY = $imgY * ($newX / $imgX);

			if ($newY > $sizeY)
			{
				$newY = $sizeY;
				$newX = $imgX * ($newY / $imgY);
			}
			
			if (function_exists("imagecreatetruecolor"))
				$imageOutput = ImageCreateTrueColor($newX, $newY);
			else
				$imageOutput = ImageCreate($newX, $newY);

			if(function_exists("imagecopyresampled"))
				imagecopyresampled($imageOutput, $imageInput, 0, 0, 0, 0, $newX, $newY, $imgX, $imgY);
			else
				imagecopyresized($imageOutput, $imageInput, 0, 0, 0, 0, $newX, $newY, $imgX, $imgY);

			switch ($ext_tmp)
			{
				case 'jpg':
					return (imageJPEG($imageOutput, $file));
				case 'gif':
					return (imageGIF($imageOutput, $file));
				case 'png':
					return (imagePNG($imageOutput, $file));
			}
		}
		return true;
	}
	
	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		unset($GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID]);
		if ($res = CBlogImage::GetByID($ID))
		{
			CFile::Delete($res['FILE_ID']);
			return $DB->Query("DELETE FROM b_blog_image WHERE ID = ".$ID, true);
		}
		return false;
	}

	//*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID]) && is_array($GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID]) && is_set($GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT G.* ".
				"FROM b_blog_image G ".
				"WHERE G.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["BLOG_IMAGE"]["BLOG_IMAGE_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

	public static function AddImageResizeHandler($arParams)
	{
		AddEventHandler('main',  "main.file.input.upload", array(__class__, 'ImageResizeHandler'));
		$bNull = null;
		self::ImageResizeHandler($bNull, $arParams);
	}

	static function ImageResizeHandler(&$arCustomFile, $arParams = null)
	{
		static $arResizeParams = array();

		if ($arParams !== null)
			$arResizeParams = $arParams;

		if ((!is_array($arCustomFile)) || !isset($arCustomFile['fileID']))
			return false;

		$fileID = $arCustomFile['fileID'];
		$arFile = CFile::GetFileArray($fileID);
		$arCustomFile['content_type'] = $arFile['CONTENT_TYPE'];
		if ($arFile && CFile::CheckImageFile($arFile) === null)
		{
			$aImgThumb = CFile::ResizeImageGet(
				$fileID,
				array("width" => 90, "height" => 90),
				BX_RESIZE_IMAGE_EXACT,
				true
			);
			$arCustomFile['img_thumb_src'] = $aImgThumb['src'];

			$aImgSource = CFile::ResizeImageGet(
				$fileID,
				array("width" => $arResizeParams["width"], "height" => $arResizeParams["height"]),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				true
			);
			$arCustomFile['img_source_src'] = $aImgSource['src'];
		}
	}
}
?>
