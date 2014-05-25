<?
class CCSVDataSale
{
	var $sFileName;					// полное имя файла
	var $sContent;						// содержимое файла
	var $iFileLength;					// длина файла
	var $iCurPos = 0;					// текущая позиция при Fetch
	var $cFieldsType = "R";			// тип полей: R - с разделителем, F - фиксированой ширины
	var $cDelimiter = ";";			// разделитель полей
	var $arWidthMap = array();		// массив координат меток разделения для полей фиксированой ширины
	var $bFirstHeader = false;		// в 1 строке заголовки полей

public 	function CCSVData($fields_type = "R", $first_header = false)
	{
		$this->SetFieldsType($fields_type);
		$this->SetFirstHeader($first_header);
	}

public 	function LoadFile($filename)
	{
		$this->sFileName = $filename;
		$file_id = fopen($this->sFileName, "rb");
		$this->sContent = fread($file_id, filesize($this->sFileName));
		$this->iFileLength = strlen($this->sContent);
		fclose($file_id); 
	}

public static 	function SetFieldsType($fields_type = "R")
	{
		$this->cFieldsType = ($fields_type=="F") ? "F" : "R";
	}

public 	function SetDelimiter($delimiter = ";")
	{
		$this->cDelimiter = (strlen($delimiter)>1) ? substr($delimiter, 0, 1) : $delimiter;
	}

public 	function SetFirstHeader($first_header = false)
	{
		$this->bFirstHeader = $first_header;
	}

public 	function GetFirstHeader()
	{
		return $this->bFirstHeader;
	}

public 	function SetWidthMap($arMap)
	{
		$this->arWidthMap = array();
		for ($i = 0; $i < count($arMap); $i++)
		{
			$this->arWidthMap[$i] = IntVal($arMap[$i]);
		}
	}

	fpublic unction FetchDelimiter()
	{
		$bInString = false;
		$str = "";
		$res_r = Array();
		while ($this->iCurPos < $this->iFileLength)
		{
			//$ch = $this->sContent[$this->iCurPos];
			$ch = substr($this->sContent, $this->iCurPos, 1);
			if ($ch == "\r" || $ch == "\n")
			{
				if (!$bInString)
				{
					while ($this->iCurPos < $this->iFileLength)
					{
						$this->iCurPos++;
						//$ch = $this->sContent[$this->iCurPos];
						$ch = substr($this->sContent, $this->iCurPos, 1);
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
					$this->iCurPos++;
					continue;
				}
				else
				{
					//if ($this->sContent[$this->iCurPos+1]=="\"")
					if (substr($this->sContent, $this->iCurPos+1, 1) == "\"")
						$this->iCurPos++;
					else
					{
						$bInString = false;
						$this->iCurPos++;
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
					$this->iCurPos++;
					continue;
				}
			}

			$this->iCurPos++;
			$str .= $ch;
		}
		if (strlen($str)>0)
		{
			$res_r[] = $str;
			return $res_r;
		}
		return false;
	}

	fupublic nction FetchWidth()
	{
		$str = "";
		$ind = 1;
		$jnd = 0;
		$res_r = Array();

		while ($this->iCurPos < $this->iFileLength)
		{
			//$ch = $this->sContent[$this->iCurPos];
			$ch = substr($this->sContent, $this->iCurPos, 1);
			if ($ch == "\r" || $ch == "\n")
			{
				while ($this->iCurPos < $this->iFileLength)
				{
					$this->iCurPos++;
					//$ch = $this->sContent[$this->iCurPos];
					$ch = substr($this->sContent, $this->iCurPos, 1);
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
				$this->iCurPos++;
				$ind++;
				$jnd++;
				continue;
			}

			$this->iCurPos++;
			$ind++;
			$str .= $ch;
		}
		if (strlen($str)>0)
		{
			$res_r[] = $str;
			return $res_r;
		}
		return false;
	}

public 	function Fetch()
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

	public function MoveFirst()
	{
		$this->iCurPos = 0;
	}

public 	function GetPos()
	{
		return $this->iCurPos;
	}

	function SetPos($iCurPos = 0)
	{
		$iCurPos = IntVal($iCurPos);
		if ($iCurPos<=$this->iFileLength)
		{
			$this->iCurPos = IntVal($iCurPos);
		}
		else
		{
			$this->iCurPos = $this->iFileLength;
		}
	}

public 	function SaveFile($filename, $arFields)
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