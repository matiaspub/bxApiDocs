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
		// invalid symbols: "%*/:<>?\| and \x00-\x1F\x7F and \x80-\xFF
		return parent::Output(
			preg_replace('/[\x00-\x1F\x22\x25\x2A\x2F\x3A\x3C\x3E\x3F\x5C\x7C\x7F-\xFF]+/', '', $name),
			$dest
		);
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
		$string = html_entity_decode($string, ENT_NOQUOTES, 'UTF-8');

		return $string;
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

	static public function GetImageSize($file)
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
			$arFile = CFile::GetImageSize($file, true);

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
			$path = $_SERVER['DOCUMENT_ROOT'] . $file;
		}

		return $path;
	}

	public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
	{
		try
		{
			$path = $this->GetImagePath($file);

			return $this->generator->Image($path, $x, $y, $w, $h, $type, $link);
		}
		catch (Exception $e)
		{
		}
	}

}
