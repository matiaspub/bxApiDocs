<?

// define('FPDF_FONTPATH', $_SERVER["DOCUMENT_ROOT"]."/bitrix/fonts/");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/tfpdf/tfpdf.php");

class CSaleTfpdf extends tFPDF
{

	private $background;

	public function SetBackground($image, $bgHeight = 0, $bgWidth = 0, $style = 'none')
	{
		if (!in_array($style, array('none', 'tile', 'stretch')))
			$style = 'none';

		if ($image && $bgHeight && $bgWidth)
		{
			$this->background = array(
				'image'  => $image,
				'height' => $bgHeight,
				'width'  => $bgWidth,
				'style'  => $style
			);
		}
	}

	static public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
	{
		try
		{
			return parent::Image($file, $x, $y, $w, $h, $type, $link);
		}
		catch (Exception $e)
		{
		}
	}

	public function Header()
	{
		if (!empty($this->background))
		{
			switch ($this->background['style'])
			{
				case 'none':
					$this->Image($this->background['image'], 0, 0);
					break;
				case 'tile':
					$y = 0;
					while ($y <= $this->GetPageHeight())
					{
						$x = 0;
						while ($x <= $this->GetPageWidth())
						{
							$this->Image($this->background['image'], $x, $y);
							$x += $this->background['width'];
						}

						$y += $this->background['height'];
					}
					break;
				case 'stretch':
					$this->Image(
						$this->background['image'],
						0, 0,
						$this->GetPageWidth(), $this->GetPageHeight()
					);
					break;
			}
		}
	}

	public function GetPageWidth()
	{
		return $this->w;
	}

	public function GetPageHeight()
	{
		return $this->h;
	}

	static public function Output($name = '', $dest = '')
	{
		// invalid symbols: "%*:<>?| and \x00-\x1F\x7F and \x80-\xFF
		$name = preg_replace('/[\x00-\x1F\x22\x25\x2A\x3A\x3C\x3E\x3F\x7C\x7F-\xFF]+/', '', $name);
		$name = str_replace('\\', '/', $name);

		if (in_array($dest, array('I', 'D')))
			$name = basename($name);

		return parent::Output($name, $dest);
	}

	public function _parsebmp($file)
	{
		// Extract info from a BMP file (via PNG conversion)
		if(!function_exists('imagepng'))
			$this->Error('GD extension is required for BMP support');
		$im = CFile::ImageCreateFromBMP($file);
		if(!$im)
			$this->Error('Missing or incorrect image file: '.$file);
		imageinterlace($im,0);
		$f = @fopen('php://temp','rb+');
		if($f)
		{
			// Perform conversion in memory
			ob_start();
			imagepng($im);
			$data = ob_get_clean();
			imagedestroy($im);
			fwrite($f,$data);
			rewind($f);
			$info = $this->_parsepngstream($f,$file);
			fclose($f);
		}
		else
		{
			// Use temporary file
			$tmp = tempnam('.','gif');
			if(!$tmp)
				$this->Error('Unable to create a temporary file');
			if(!imagepng($im,$tmp))
				$this->Error('Error while saving to temporary file');
			imagedestroy($im);
			$info = $this->_parsepng($tmp);
			unlink($tmp);
		}
		return $info;
	}

}

class CSalePdf
{

	protected $generator;

	public static function isPdfAvailable()
	{
		if (!extension_loaded("mbstring"))
			return false;

		if (!file_exists(FPDF_FONTPATH.'/pt_serif-regular.ttf') || !file_exists(FPDF_FONTPATH.'/pt_serif-bold.ttf'))
			return false;

		return true;
	}

	public static function prepareToPdf($string)
	{
		$string = htmlspecialcharsback($string);
		$string = CharsetConverter::ConvertCharset($string, SITE_CHARSET, 'UTF-8');
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

		return $string;
	}

	public function splitString($text, $width)
	{
		if ($this->generator->GetStringWidth($text) <= $width)
		{
			return array($text, '');
		}
		else
		{
			$string = $text;
			while ($this->generator->GetStringWidth($string) > $width)
			{
				$l = floor(mb_strlen($string, 'UTF-8') * $width/$this->generator->GetStringWidth($string));
				$p = mb_strrpos($string, ' ', $l-mb_strlen($string, 'UTF-8'), 'UTF-8') ?: $l;

				$string = mb_substr($string, 0, $p, 'UTF-8');
			}

			return array(
				$string,
				mb_substr($text, $p+1, mb_strlen($text, 'UTF-8'), 'UTF-8')
			);
		}
	}

	public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
	{
		$this->generator = new CSaleTfpdf($orientation, $unit, $size);
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->generator, $name), $arguments);
	}

	public function SetBackground($image, $style)
	{
		list($bgHeight, $bgWidth) = $this->GetImageSize($image);

		$this->generator->SetBackground($this->GetImagePath($image), $bgHeight, $bgWidth, $style);
	}

	public function GetImageSize($file)
	{
		$height = 0;
		$width  = 0;

		if (intval($file) > 0)
		{
			$arFile = CFile::GetFileArray($file);

			if ($arFile)
			{
				$height = $arFile['HEIGHT'] * 0.75;
				$width  = $arFile['WIDTH'] * 0.75;
			}
		}
		else
		{
			$arFile = CFile::GetImageSize($this->GetImagePath($file), true);

			if ($arFile)
			{
				$height = $arFile[0] * 0.75;
				$width  = $arFile[1] * 0.75;
			}
		}

		return array(0 => $height, 1 => $width);
	}

	static public function GetImagePath($file)
	{
		$path = false;

		if (intval($file) > 0)
		{
			$arFile = CFile::MakeFileArray($file);

			if ($arFile)
				$path = $arFile['tmp_name'];
		}
		elseif ($file)
		{
			$path = strpos($file, $_SERVER['DOCUMENT_ROOT']) === 0
				? $file
				: $_SERVER['DOCUMENT_ROOT'] . $file;
		}

		return $path;
	}

	public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
	{
		return $this->generator->Image($this->GetImagePath($file), $x, $y, $w, $h, $type, $link);
	}

}
