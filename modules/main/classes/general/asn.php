<?
class CASNReader
{
	protected $value;

	public function __construct($value='')
	{
		$this->value = $value;
	}

	public function Read(&$buf)
	{
		self::ReadByte($buf);
		$size = self::ReadByte($buf);
	
		if($size > 127) 
		{
			$sizeLen = $size - 0x80;
			$size = self::ToInt(self::ReadBytes($buf, $sizeLen));
		}
	
		$this->value = self::ReadBytes($buf, $size);
	}

	protected static function ReadBytes(&$buf, $len)
	{
		$res = CUtil::BinSubstr($buf, 0, $len);
		$buf = CUtil::BinSubstr($buf, $len);
		
		return $res;
	}
    
    protected static function ReadByte(&$buf)
	{
		return ord(self::ReadBytes($buf, 1));
	}

	protected static function ToInt($bin)
	{
		$result = 0;
		$len = CUtil::BinStrlen($bin);
		for($i=0; $i<$len; $i++) 
		{
			$byte = self::ReadByte($bin);
			$result += $byte << (($len-$i-1)*8);
		}
		return $result;
	}

	public function GetValue()    
	{
		$result = $this->value;
		if(ord($result{0}) == 0x00)
			$result = CUtil::BinSubstr($result, 1);
		return $result;
	}

	public function GetSequence()
	{
		$arResult = array();
		$val = $this->value;
		while($val <> '')
		{
			$sequence = new CASNReader();
			$sequence->Read($val);
			$arResult[] = $sequence;
		}  
		return $arResult;
	}
}
?>
