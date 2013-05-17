<?
class CCSVData
{
	var $sFileName;			// full file name
	var $sContent;			// file contents
	var $iFileLength;		// file length
	var $iCurPos = 0;		// current file position Fetch
	var $cFieldsType = "R";		// fields type: R - with delimiter, F - fixed width
	var $cDelimiter = ";";		// field delimiter
	var $arWidthMap = array();	// array of delimiters positions in fixed width case
	var $bFirstHeader = false;	// 1 row - columns titles

	var $__file = null;
	var $__buffer = "";
	var $__buffer_pos = 0;
	var $__buffer_size = 0;
	var $__hasBOM = false;

	public static function CCSVData($fields_type = "R", $first_header = false)
	{
		$this->SetFieldsType($fields_type);
		$this->SetFirstHeader($first_header);
	}

	public static function LoadFile($filename)
	{
		$this->sFileName = $filename;
		$this->__file = fopen($this->sFileName, "rb");
		$this->iFileLength = filesize($this->sFileName);
		$this->CheckUTF8BOM();
		$this->SetPos(0);
	}

	public static function CheckUTF8BOM()
	{
		//check UTF-8 Byte-Order Mark
		fseek($this->__file, 0);
		$sBOM = fread($this->__file, 3);
		if($sBOM == "\xEF\xBB\xBF")
			$this->__hasBOM = true;
	}

	public static function SetFieldsType($fields_type = "R")
	{
		$this->cFieldsType = ($fields_type=="F") ? "F" : "R";
	}

	public static function SetDelimiter($delimiter = ";")
	{
		$this->cDelimiter = (strlen($delimiter)>1) ? substr($delimiter, 0, 1) : $delimiter;
	}

	public static function SetFirstHeader($first_header = false)
	{
		$this->bFirstHeader = $first_header;
	}

	public static function GetFirstHeader()
	{
		return $this->bFirstHeader;
	}

	public static function SetWidthMap($arMap)
	{
		$this->arWidthMap = array();
		for ($i = 0; $i < count($arMap); $i++)
		{
			$this->arWidthMap[$i] = IntVal($arMap[$i]);
		}
	}

	public static function FetchDelimiter()
	{
		$bInString = false;
		$str = "";
		$res_r = Array();
		while ($this->iCurPos < $this->iFileLength)
		{
			$ch = $this->__buffer[$this->__buffer_pos];
			if ($ch == "\r" || $ch == "\n")
			{
				if (!$bInString)
				{
					while ($this->iCurPos < $this->iFileLength)
					{
						$this->IncCurPos();
						$ch = $this->__buffer[$this->__buffer_pos];
						if ($ch != "\r" && $ch != "\n") break;
					}
					if ($this->bFirstHeader)
					{
						$this->bFirstHeader = False;
						$res_r = array();
						$str = "";
						continue;
					}
					else
					{
						$res_r[] = $str;
						return $res_r;
					}
				}
			}
			elseif ($ch == "\"")
			{
				if (!$bInString)
				{
					$bInString = true;
					$this->IncCurPos();
					continue;
				}
				else
				{
					$this->IncCurPos();
					if($this->__buffer[$this->__buffer_pos]!="\"")
					{
						$bInString = false;
						continue;
					}
				}
			}
			elseif ($ch == $this->cDelimiter)
			{
				if (!$bInString)
				{
					$res_r[] = $str;
					$str = "";
					$this->IncCurPos();
					continue;
				}
			}

			//$this->IncCurPos();
			//inline "call"
			$this->iCurPos++;
			$this->__buffer_pos++;
			if($this->__buffer_pos >= $this->__buffer_size)
			{
				if(feof($this->__file))
					$this->__buffer = "";
				else
					$this->__buffer = fread($this->__file, 1024*1024);
				$this->__buffer_size = function_exists("mb_strlen")? mb_strlen($this->__buffer, 'latin1'): strlen($this->__buffer);
				$this->__buffer_pos = 0;
			}

			$str .= $ch;
		}

		if (strlen($str))
			$res_r[] = $str;

		if(empty($res_r))
			return false;
		else
			return $res_r;
	}

	public static function FetchWidth()
	{
		$str = "";
		$ind = 1;
		$jnd = 0;
		$res_r = Array();

		while ($this->iCurPos < $this->iFileLength)
		{
			$ch = $this->__buffer[$this->__buffer_pos];
			if ($ch == "\r" || $ch == "\n")
			{
				while ($this->iCurPos < $this->iFileLength)
				{
					$this->IncCurPos();
					$ch = $this->__buffer[$this->__buffer_pos];
					if ($ch != "\r" && $ch != "\n") break;
				}
				if ($this->bFirstHeader)
				{
					$this->bFirstHeader = False;
					$res_r = array();
					$ind = 1;
					$str = "";
					continue;
				}
				else
				{
					$res_r[] = $str;
					return $res_r;
				}
			}
			elseif ($ind == $this->arWidthMap[$jnd])
			{
				$res_r[] = $str.$ch;
				$str = "";
				$this->IncCurPos();
				$ind++;
				$jnd++;
				continue;
			}

			//$this->IncCurPos();
			//inline "call"
			$this->iCurPos++;
			$this->__buffer_pos++;
			if($this->__buffer_pos >= $this->__buffer_size)
			{
				if(feof($this->__file))
					$this->__buffer = "";
				else
					$this->__buffer = fread($this->__file, 1024*1024);
				$this->__buffer_size = function_exists("mb_strlen")? mb_strlen($this->__buffer, 'latin1'): strlen($this->__buffer);
				$this->__buffer_pos = 0;
			}

			$ind++;
			$str .= $ch;
		}

		if (strlen($str)>0)
			$res_r[] = $str;

		if(empty($res_r))
			return false;
		else
			return $res_r;
	}

	public static function Fetch()
	{
		if ($this->cFieldsType=="R")
		{
			if (strlen($this->cDelimiter)<=0) return false;
			return $this->FetchDelimiter();
		}
		else
		{
			if (count($this->arWidthMap)<=0) return false;
			return $this->FetchWidth();
		}
	}

	public static function IncCurPos()
	{
		$this->iCurPos++;
		$this->__buffer_pos++;
		if($this->__buffer_pos >= $this->__buffer_size)
		{
			if(feof($this->__file))
				$this->__buffer = "";
			else
				$this->__buffer = fread($this->__file, 1024*1024);
			$this->__buffer_size = function_exists("mb_strlen")? mb_strlen($this->__buffer, 'latin1'): strlen($this->__buffer);
			$this->__buffer_pos = 0;
		}
	}

	public static function MoveFirst()
	{
		$this->SetPos(0);
	}

	public static function GetPos()
	{
		return $this->iCurPos;
	}

	public static function SetPos($iCurPos = 0)
	{
		$iCurPos = IntVal($iCurPos);
		if ($iCurPos<=$this->iFileLength)
			$this->iCurPos = IntVal($iCurPos);
		else
			$this->iCurPos = $this->iFileLength;

		$pos = $this->iCurPos;
		if($this->__hasBOM)
			$pos += 3;
		fseek($this->__file, $pos);

		if(feof($this->__file))
			$this->__buffer = "";
		else
			$this->__buffer = fread($this->__file, 1024*1024);
		$this->__buffer_size = function_exists("mb_strlen")? mb_strlen($this->__buffer, 'latin1'): strlen($this->__buffer);
		$this->__buffer_pos = 0;
	}

	public static function SaveFile($filename, $arFields)
	{
		$this->sFileName = $filename;

		if ($this->cFieldsType=="R")
		{
			if (strlen($this->cDelimiter)<=0) return false;

			$this->sContent = "";
			for ($i = 0; $i < count($arFields); $i++)
			{
				if ($i>0) $this->sContent .= $this->cDelimiter;
				$pos1 = strpos($arFields[$i], $this->cDelimiter);
				$pos2 = strpos($arFields[$i], "\"");
				$pos3 = strpos($arFields[$i], "\n");
				$pos4 = strpos($arFields[$i], "\r");
				if ($pos1 !== false || $pos2 !== false || $pos3 !== false || $pos4 !== false)
				{
					$this->sContent .= "\"";
					$this->sContent .= str_replace("\"", "\"\"", $arFields[$i]);
					$this->sContent .= "\"";
				}
				else
				{
					$this->sContent .= $arFields[$i];
				}
			}
			if (strlen($this->sContent)>0)
			{
				$this->sContent .= "\n";
				$file_id = fopen($this->sFileName, "ab");
				fwrite($file_id, $this->sContent);
				fclose($file_id);
			}
		}
	}
}
?>