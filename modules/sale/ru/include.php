<?
function Number2Word_Rus($source, $IS_MONEY = "Y", $currency = "")
{
	$result = "";

	if (strlen($currency) <= 0 || $currency == "RUR")
		$currency = "RUB";

	$arNumericLang = array(
		"RUB" => array(
			"1c" => "сто ",
			"2c" => "двести ",
			"3c" => "триста ",
			"4c" => "четыреста ",
			"5c" => "пятьсот ",
			"6c" => "шестьсот ",
			"7c" => "семьсот ",
			"8c" => "восемьсот ",
			"9c" => "девятьсот ",
			"1d0e" => "десять ",
			"1d1e" => "одиннадцать ",
			"1d2e" => "двенадцать ",
			"1d3e" => "тринадцать ",
			"1d4e" => "четырнадцать ",
			"1d5e" => "пятнадцать ",
			"1d6e" => "шестнадцать ",
			"1d7e" => "семнадцать ",
			"1d8e" => "восемнадцать ",
			"1d9e" => "девятнадцать ",
			"2d" => "двадцать ",
			"3d" => "тридцать ",
			"4d" => "сорок ",
			"5d" => "пятьдесят ",
			"6d" => "шестьдесят ",
			"7d" => "семьдесят ",
			"8d" => "восемьдесят ",
			"9d" => "девяносто ",
			"5e" => "пять ",
			"6e" => "шесть ",
			"7e" => "семь ",
			"8e" => "восемь ",
			"9e" => "девять ",
			"1et" => "одна тысяча ",
			"2et" => "две тысячи ",
			"3et" => "три тысячи ",
			"4et" => "четыре тысячи ",
			"1em" => "один миллион ",
			"2em" => "два миллиона ",
			"3em" => "три миллиона ",
			"4em" => "четыре миллиона ",
			"1eb" => "один миллиард ",
			"2eb" => "два миллиарда ",
			"3eb" => "три миллиарда ",
			"4eb" => "четыре миллиарда ",
			"1e." => "один рубль ",
			"2e." => "два рубля ",
			"3e." => "три рубля ",
			"4e." => "четыре рубля ",
			"1e" => "один ",
			"2e" => "два ",
			"3e" => "три ",
			"4e" => "четыре ",
			"11k" => "11 копеек",
			"12k" => "12 копеек",
			"13k" => "13 копеек",
			"14k" => "14 копеек",
			"1k" => "1 копейка",
			"2k" => "2 копейки",
			"3k" => "3 копейки",
			"4k" => "4 копейки",
			"." => "рублей ",
			"t" => "тысяч ",
			"m" => "миллионов ",
			"b" => "миллиардов ",
			"k" => " копеек",
		),
		"UAH" => array(
			"1c" => "сто ",
			"2c" => "двісті ",
			"3c" => "триста ",
			"4c" => "чотириста ",
			"5c" => "п'ятсот ",
			"6c" => "шістсот ",
			"7c" => "сімсот ",
			"8c" => "вісімсот ",
			"9c" => "дев'ятьсот ",
			"1d0e" => "десять ",
			"1d1e" => "одинадцять ",
			"1d2e" => "дванадцять ",
			"1d3e" => "тринадцять ",
			"1d4e" => "чотирнадцять ",
			"1d5e" => "п'ятнадцять ",
			"1d6e" => "шістнадцять ",
			"1d7e" => "сімнадцять ",
			"1d8e" => "вісімнадцять ",
			"1d9e" => "дев'ятнадцять ",
			"2d" => "двадцять ",
			"3d" => "тридцять ",
			"4d" => "сорок ",
			"5d" => "п'ятдесят ",
			"6d" => "шістдесят ",
			"7d" => "сімдесят ",
			"8d" => "вісімдесят ",
			"9d" => "дев'яносто ",
			"5e" => "п'ять ",
			"6e" => "шість ",
			"7e" => "сім ",
			"8e" => "вісім ",
			"9e" => "дев'ять ",
			"1e." => "один гривня ",
			"2e." => "два гривні ",
			"3e." => "три гривні ",
			"4e." => "чотири гривні ",
			"1e" => "один ",
			"2e" => "два ",
			"3e" => "три ",
			"4e" => "чотири ",
			"1et" => "одна тисяча ",
			"2et" => "дві тисячі ",
			"3et" => "три тисячі ",
			"4et" => "чотири тисячі ",
			"1em" => "один мільйон ",
			"2em" => "два мільйона ",
			"3em" => "три мільйона ",
			"4em" => "чотири мільйона ",
			"1eb" => "один мільярд ",
			"2eb" => "два мільярда ",
			"3eb" => "три мільярда ",
			"4eb" => "чотири мільярда ",
			"11k" => "11 копійок",
			"12k" => "12 копійок",
			"13k" => "13 копійок",
			"14k" => "14 копійок",
			"1k" => "1 копійка",
			"2k" => "2 копійки",
			"3k" => "3 копійки",
			"4k" => "4 копійки",
			"." => "гривень ",
			"t" => "тисяч ",
			"m" => "мільйонів ",
			"b" => "мільярдів ",
			"k" => " копійок",
		)
	);


	// k - копейки
	if ($IS_MONEY == "Y")
	{
		$source = DoubleVal($source);

		$dotpos = strpos($source, ".");
		if ($dotpos === false)
		{
			$ipart = $source;
			$fpart = "";
		}
		else
		{
			$ipart = substr($source, 0, $dotpos);
			$fpart = substr($source, $dotpos + 1);
		}

		$fpart = substr($fpart, 0, 2);
		while (strlen($fpart)<2) $fpart .= "0";
	}
	else
	{
		$source = IntVal($source);
		$ipart = $source;
		$fpart = "";
	}

	while ($ipart[0]=="0") $ipart = substr($ipart, 1);

	$ipart1 = StrRev($ipart);
	$ipart = "";
	$i = 0;
	while ($i<strlen($ipart1))
	{
		$ipart_tmp = $ipart1[$i];
		// t - тысячи; m - милионы; b - миллиарды;
		// e - единицы; d - десятки; c - сотни;
		if ($i % 3 == 0)
		{
			if ($i==0) $ipart_tmp .= "e";
			elseif ($i==3) $ipart_tmp .= "et";
			elseif ($i==6) $ipart_tmp .= "em";
			elseif ($i==9) $ipart_tmp .= "eb";
			else $ipart_tmp .= "x";
		}
		elseif ($i % 3 == 1) $ipart_tmp .= "d";
		elseif ($i % 3 == 2) $ipart_tmp .= "c";
		$ipart = $ipart_tmp.$ipart;
		$i++;
	}

	if ($IS_MONEY == "Y")
	{
		$result = $ipart.".".$fpart."k";
	}
	else
	{
		$result = $ipart;
	}

	if ($result[0] == ".")
		$result = "ноль ".$result;

	$result = str_replace("0c0d0et", "", $result);
	$result = str_replace("0c0d0em", "", $result);
	$result = str_replace("0c0d0eb", "", $result);

	$result = str_replace("0c", "", $result);
	$result = str_replace("1c", $arNumericLang[$currency]["1c"], $result);
	$result = str_replace("2c", $arNumericLang[$currency]["2c"], $result);
	$result = str_replace("3c", $arNumericLang[$currency]["3c"], $result);
	$result = str_replace("4c", $arNumericLang[$currency]["4c"], $result);
	$result = str_replace("5c", $arNumericLang[$currency]["5c"], $result);
	$result = str_replace("6c", $arNumericLang[$currency]["6c"], $result);
	$result = str_replace("7c", $arNumericLang[$currency]["7c"], $result);
	$result = str_replace("8c", $arNumericLang[$currency]["8c"], $result);
	$result = str_replace("9c", $arNumericLang[$currency]["9c"], $result);

	$result = str_replace("1d0e", $arNumericLang[$currency]["1d0e"], $result);
	$result = str_replace("1d1e", $arNumericLang[$currency]["1d1e"], $result);
	$result = str_replace("1d2e", $arNumericLang[$currency]["1d2e"], $result);
	$result = str_replace("1d3e", $arNumericLang[$currency]["1d3e"], $result);
	$result = str_replace("1d4e", $arNumericLang[$currency]["1d4e"], $result);
	$result = str_replace("1d5e", $arNumericLang[$currency]["1d5e"], $result);
	$result = str_replace("1d6e", $arNumericLang[$currency]["1d6e"], $result);
	$result = str_replace("1d7e", $arNumericLang[$currency]["1d7e"], $result);
	$result = str_replace("1d8e", $arNumericLang[$currency]["1d8e"], $result);
	$result = str_replace("1d9e", $arNumericLang[$currency]["1d9e"], $result);

	$result = str_replace("0d", "", $result);
	$result = str_replace("2d", $arNumericLang[$currency]["2d"], $result);
	$result = str_replace("3d", $arNumericLang[$currency]["3d"], $result);
	$result = str_replace("4d", $arNumericLang[$currency]["4d"], $result);
	$result = str_replace("5d", $arNumericLang[$currency]["5d"], $result);
	$result = str_replace("6d", $arNumericLang[$currency]["6d"], $result);
	$result = str_replace("7d", $arNumericLang[$currency]["7d"], $result);
	$result = str_replace("8d", $arNumericLang[$currency]["8d"], $result);
	$result = str_replace("9d", $arNumericLang[$currency]["9d"], $result);

	$result = str_replace("0e", "", $result);
	$result = str_replace("5e", $arNumericLang[$currency]["5e"], $result);
	$result = str_replace("6e", $arNumericLang[$currency]["6e"], $result);
	$result = str_replace("7e", $arNumericLang[$currency]["7e"], $result);
	$result = str_replace("8e", $arNumericLang[$currency]["8e"], $result);
	$result = str_replace("9e", $arNumericLang[$currency]["9e"], $result);

	$result = str_replace("1et", $arNumericLang[$currency]["1et"], $result);
	$result = str_replace("2et", $arNumericLang[$currency]["2et"], $result);
	$result = str_replace("3et", $arNumericLang[$currency]["3et"], $result);
	$result = str_replace("4et", $arNumericLang[$currency]["4et"], $result);
	$result = str_replace("1em", $arNumericLang[$currency]["1em"], $result);
	$result = str_replace("2em", $arNumericLang[$currency]["2em"], $result);
	$result = str_replace("3em", $arNumericLang[$currency]["3em"], $result);
	$result = str_replace("4em", $arNumericLang[$currency]["4em"], $result);
	$result = str_replace("1eb", $arNumericLang[$currency]["1eb"], $result);
	$result = str_replace("2eb", $arNumericLang[$currency]["2eb"], $result);
	$result = str_replace("3eb", $arNumericLang[$currency]["3eb"], $result);
	$result = str_replace("4eb", $arNumericLang[$currency]["4eb"], $result);


	if ($IS_MONEY == "Y")
	{
		$result = str_replace("1e.", $arNumericLang[$currency]["1e."], $result);
		$result = str_replace("2e.", $arNumericLang[$currency]["2e."], $result);
		$result = str_replace("3e.", $arNumericLang[$currency]["3e."], $result);
		$result = str_replace("4e.", $arNumericLang[$currency]["4e."], $result);
	}
	else
	{
		$result = str_replace("1e", $arNumericLang[$currency]["1e"], $result);
		$result = str_replace("2e", $arNumericLang[$currency]["2e"], $result);
		$result = str_replace("3e", $arNumericLang[$currency]["3e"], $result);
		$result = str_replace("4e", $arNumericLang[$currency]["4e"], $result);
	}

	if ($IS_MONEY == "Y")
	{
		$result = str_replace("11k", $arNumericLang[$currency]["11k"], $result);
		$result = str_replace("12k", $arNumericLang[$currency]["12k"], $result);
		$result = str_replace("13k", $arNumericLang[$currency]["13k"], $result);
		$result = str_replace("14k", $arNumericLang[$currency]["14k"], $result);
		$result = str_replace("1k", $arNumericLang[$currency]["1k"], $result);
		$result = str_replace("2k", $arNumericLang[$currency]["2k"], $result);
		$result = str_replace("3k", $arNumericLang[$currency]["3k"], $result);
		$result = str_replace("4k", $arNumericLang[$currency]["4k"], $result);
	}

	if ($IS_MONEY == "Y")
		$result = str_replace(".", $arNumericLang[$currency]["."], $result);

	$result = str_replace("t", $arNumericLang[$currency]["t"], $result);
	$result = str_replace("m", $arNumericLang[$currency]["m"], $result);
	$result = str_replace("b", $arNumericLang[$currency]["b"], $result);

	if ($IS_MONEY == "Y")
		$result = str_replace("k", $arNumericLang[$currency]["k"], $result);

	return (ToUpper(substr($result, 0, 1)) . substr($result, 1));
}
?>
