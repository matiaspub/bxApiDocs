<?
class CSaleHelper
{
	public static function IsAssociativeArray($ar)
	{
		if (count($ar) <= 0)
			return false;

		$fl = false;

		$arKeys = array_keys($ar);
		$ind = -1;
		foreach ($arKeys as $key)
		{
			$ind++;
			if ($key."!" !== $ind."!" && "".$key !== "n".$ind)
			{
				$fl = true;
				break;
			}
		}

		return $fl;
	}

	/**
	* Writes to /bitrix/modules/sale.log
	*
	* @param string $text message to write
	* @param array $arVars array (varname => value) to print out variables
	* @param string $code log record tag
	*/
	public static function WriteToLog($text, $arVars = array(), $code = "")
	{
		$filename = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale.log";

		if ($f = fopen($filename, "a"))
		{
			fwrite($f, date("Y-m-d H:i:s")." - ".$code." - ".$text."\n");

			if (is_array($arVars))
			{
				foreach ($arVars as $varName => $varData)
				{
					fwrite($f, $varName.": ");
					fwrite($f, print_r($varData, true));
					fwrite($f, "\n");
				}
			}

			fwrite($f, "\n");
			fclose($f);
		}
	}
}
