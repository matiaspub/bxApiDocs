<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * The class is used when reading csv files doing location import.
 * 
 * @access private
 */

namespace Bitrix\Sale\Location\Util;

use Bitrix\Main;

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/csv_data.php");

final class CSVReader extends \CCSVData
{
	const FILE_ENCODING = 'UTF-8';

	private $header = 		array();
	private $useHeader = 	false;
	private $legacy = 		false;

	private $convertCharset = true;
	private $callbacks = 	array();

	public function __construct($fields_type = "R", $convertCharset = true)
	{
		parent::__construct($fields_type = "R", false);
		$this->convertCharset = $convertCharset;
	}

	public function LoadFile($filename, $firstHeader = true)
	{
		parent::LoadFile($filename);

		$this->SetFieldsType("R");
		if($firstHeader)
			$this->SetFirstHeader();
		$this->SetDelimiter(";");
	}

	public function SetFirstHeader($first_header = false)
	{
		$this->useHeader = true;
		$this->header = $this->ReadHeader();
	}

	public function ReadHeader()
	{
		if(!$this->useHeader || !$this->__file)
			return false;

		if($this->cFieldsType == 'F')
			return false; // sorry, not implemented for that

		$fPos = ftell($this->__file);
		fseek($this->__file, $this->__hasBOM ? 3 : 0);

		$h = fgets($this->__file);

		fseek($this->__file, $fPos);

		return explode($this->cDelimiter, $h);
	}

	public function FetchAssoc()
	{
		if(!($line = $this->Fetch()))
			return false;

		if(!$this->useHeader || $this->legacy)
			return $line;

		$header = $this->header;

		$result = array();
		$colCount = count($line);
		$langFields = array();
		for($k = 0; $k < $colCount; $k++)
		{
			$fld = trim(array_shift($header));

			if(!$fld) // column grid appeared shorter than data field
				break;

			$resLine = array();
			$prev =& $resLine;
			$subFields = explode('.', $fld);

			foreach($subFields as $subfld)
			{
				$subfld = trim($subfld);

				$prev[$subfld] = array();
				$prev =& $prev[$subfld];
			}

			$prev = trim($line[$k]);

			// keep for charset conversion
			if(strpos($fld, 'NAME') !== false)
				$langFields[] = &$prev;

			$result = array_merge_recursive($result, $resLine);
		}

		if(is_callable($this->callbacks['AFTER_ASSOC_LINE_READ']))
		{
			call_user_func_array($this->callbacks['AFTER_ASSOC_LINE_READ'], array(&$result));
		}

		// character conversion
		if($this->convertCharset && self::FILE_ENCODING != SITE_CHARSET)
		{
			foreach($langFields as &$value)
			{
				$value = \CharsetConverter::ConvertCharset($value, self::FILE_ENCODING, SITE_CHARSET);
			}
		}

		return $result;
	}

	// this function should not be here
	public function CheckFileIsLegacy()
	{
		return $this->legacy;
	}

	public function ReadBlockLowLevel(&$bytesRead = false, $lineLimit = false)
	{
		if(trim($this->header[0]) == 'en' && !isset($this->header[1]))
		{
			$this->legacy = true;
			$this->SetDelimiter(",");
		}

		if($bytesRead !== false)
			$this->SetPos($bytesRead);

		$result = array();
		$i = -1;
		while ($line = $this->FetchAssoc())
		{
			$i++;

			if($lineLimit !== false && $lineLimit + 1 == $i)
				break;

			if(!$i && !$bytesRead)
			{
				continue; // header, skip
			}

			$result[] = $line;

			if($bytesRead !== false)
				$bytesRead = $this->GetPos();
		}

		return $result;
	}

	public function ReadBlock($file, &$bytesRead = false, $lineLimit = false)
	{
		if(strpos($file, $_SERVER['DOCUMENT_ROOT']) != 0) // not found or somwhere else
			$file = $_SERVER['DOCUMENT_ROOT'].$file;

		if(!file_exists($file) || !is_readable($file))
			throw new Main\SystemException('Cannot open file '.$file.' for reading');

		$this->LoadFile($file);

		return $this->ReadBlockLowLevel($bytesRead, $lineLimit);
	}

	public function GetFileSize()
	{
		return $this->iFileLength;
	}

	public function GetHeaderAssoc()
	{
		return $this->GetAssocLineByHeader($this->header, $this->header);
	}

	public function SetCharsetConvert($switch)
	{
		$this->convertCharset = !!$switch;
	}

	public function AddEventCallback($event, $callback)
	{
		if((string) $event != '' && is_callable($callback))
			$this->callbacks[$event] = $callback;
	}

	private function GetAssocLineByHeader($line, $header)
	{
		$result = array();
		$lineLen = count($line);
		for($k = 0; $k < $lineLen; $k++)
		{
			$fld = array_shift($header);

			if(!$fld) // column grid appeared shorter than data field
				break;

			$resLine = array();
			$prev =& $resLine;
			$subFields = explode('.', $fld);

			foreach($subFields as $subfld)
			{
				$subfld = trim($subfld);

				$prev[$subfld] = array();
				$prev =& $prev[$subfld];
			}

			$prev = trim($line[$k]);

			$result = array_merge_recursive($result, $resLine);
		}

		return $result;
	}
}