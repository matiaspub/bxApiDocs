<?
function Number2Word_Rus($source, $IS_MONEY = "Y", $currency = "")
{
	$result = "";

	if (strlen($currency) <= 0 || $currency == "RUR")
		$currency = "RUB";

	$arNumericLang = array(
		"RUB" => array(
			"1c" => "СЃС‚Рѕ ",
			"2c" => "РґРІРµСЃС‚Рё ",
			"3c" => "С‚СЂРёСЃС‚Р° ",
			"4c" => "С‡РµС‚С‹СЂРµСЃС‚Р° ",
			"5c" => "РїСЏС‚СЊСЃРѕС‚ ",
			"6c" => "С€РµСЃС‚СЊСЃРѕС‚ ",
			"7c" => "СЃРµРјСЊСЃРѕС‚ ",
			"8c" => "РІРѕСЃРµРјСЊСЃРѕС‚ ",
			"9c" => "РґРµРІСЏС‚СЊСЃРѕС‚ ",
			"1d0e" => "РґРµСЃСЏС‚СЊ ",
			"1d1e" => "РѕРґРёРЅРЅР°РґС†Р°С‚СЊ ",
			"1d2e" => "РґРІРµРЅР°РґС†Р°С‚СЊ ",
			"1d3e" => "С‚СЂРёРЅР°РґС†Р°С‚СЊ ",
			"1d4e" => "С‡РµС‚С‹СЂРЅР°РґС†Р°С‚СЊ ",
			"1d5e" => "РїСЏС‚РЅР°РґС†Р°С‚СЊ ",
			"1d6e" => "С€РµСЃС‚РЅР°РґС†Р°С‚СЊ ",
			"1d7e" => "СЃРµРјРЅР°РґС†Р°С‚СЊ ",
			"1d8e" => "РІРѕСЃРµРјРЅР°РґС†Р°С‚СЊ ",
			"1d9e" => "РґРµРІСЏС‚РЅР°РґС†Р°С‚СЊ ",
			"2d" => "РґРІР°РґС†Р°С‚СЊ ",
			"3d" => "С‚СЂРёРґС†Р°С‚СЊ ",
			"4d" => "СЃРѕСЂРѕРє ",
			"5d" => "РїСЏС‚СЊРґРµСЃСЏС‚ ",
			"6d" => "С€РµСЃС‚СЊРґРµСЃСЏС‚ ",
			"7d" => "СЃРµРјСЊРґРµСЃСЏС‚ ",
			"8d" => "РІРѕСЃРµРјСЊРґРµСЃСЏС‚ ",
			"9d" => "РґРµРІСЏРЅРѕСЃС‚Рѕ ",
			"5e" => "РїСЏС‚СЊ ",
			"6e" => "С€РµСЃС‚СЊ ",
			"7e" => "СЃРµРјСЊ ",
			"8e" => "РІРѕСЃРµРјСЊ ",
			"9e" => "РґРµРІСЏС‚СЊ ",
			"1et" => "РѕРґРЅР° С‚С‹СЃСЏС‡Р° ",
			"2et" => "РґРІРµ С‚С‹СЃСЏС‡Рё ",
			"3et" => "С‚СЂРё С‚С‹СЃСЏС‡Рё ",
			"4et" => "С‡РµС‚С‹СЂРµ С‚С‹СЃСЏС‡Рё ",
			"1em" => "РѕРґРёРЅ РјРёР»Р»РёРѕРЅ ",
			"2em" => "РґРІР° РјРёР»Р»РёРѕРЅР° ",
			"3em" => "С‚СЂРё РјРёР»Р»РёРѕРЅР° ",
			"4em" => "С‡РµС‚С‹СЂРµ РјРёР»Р»РёРѕРЅР° ",
			"1eb" => "РѕРґРёРЅ РјРёР»Р»РёР°СЂРґ ",
			"2eb" => "РґРІР° РјРёР»Р»РёР°СЂРґР° ",
			"3eb" => "С‚СЂРё РјРёР»Р»РёР°СЂРґР° ",
			"4eb" => "С‡РµС‚С‹СЂРµ РјРёР»Р»РёР°СЂРґР° ",
			"1e." => "РѕРґРёРЅ СЂСѓР±Р»СЊ ",
			"2e." => "РґРІР° СЂСѓР±Р»СЏ ",
			"3e." => "С‚СЂРё СЂСѓР±Р»СЏ ",
			"4e." => "С‡РµС‚С‹СЂРµ СЂСѓР±Р»СЏ ",
			"1e" => "РѕРґРёРЅ ",
			"2e" => "РґРІР° ",
			"3e" => "С‚СЂРё ",
			"4e" => "С‡РµС‚С‹СЂРµ ",
			"11k" => "11 РєРѕРїРµРµРє",
			"12k" => "12 РєРѕРїРµРµРє",
			"13k" => "13 РєРѕРїРµРµРє",
			"14k" => "14 РєРѕРїРµРµРє",
			"1k" => "1 РєРѕРїРµР№РєР°",
			"2k" => "2 РєРѕРїРµР№РєРё",
			"3k" => "3 РєРѕРїРµР№РєРё",
			"4k" => "4 РєРѕРїРµР№РєРё",
			"." => "СЂСѓР±Р»РµР№ ",
			"t" => "С‚С‹СЃСЏС‡ ",
			"m" => "РјРёР»Р»РёРѕРЅРѕРІ ",
			"b" => "РјРёР»Р»РёР°СЂРґРѕРІ ",
			"k" => " РєРѕРїРµРµРє",
		),
		"UAH" => array(
			"1c" => "СЃС‚Рѕ ",
			"2c" => "РґРІС–СЃС‚С– ",
			"3c" => "С‚СЂРёСЃС‚Р° ",
			"4c" => "С‡РѕС‚РёСЂРёСЃС‚Р° ",
			"5c" => "Рї'СЏС‚СЃРѕС‚ ",
			"6c" => "С€С–СЃС‚СЃРѕС‚ ",
			"7c" => "СЃС–РјСЃРѕС‚ ",
			"8c" => "РІС–СЃС–РјСЃРѕС‚ ",
			"9c" => "РґРµРІ'СЏС‚СЊСЃРѕС‚ ",
			"1d0e" => "РґРµСЃСЏС‚СЊ ",
			"1d1e" => "РѕРґРёРЅР°РґС†СЏС‚СЊ ",
			"1d2e" => "РґРІР°РЅР°РґС†СЏС‚СЊ ",
			"1d3e" => "С‚СЂРёРЅР°РґС†СЏС‚СЊ ",
			"1d4e" => "С‡РѕС‚РёСЂРЅР°РґС†СЏС‚СЊ ",
			"1d5e" => "Рї'СЏС‚РЅР°РґС†СЏС‚СЊ ",
			"1d6e" => "С€С–СЃС‚РЅР°РґС†СЏС‚СЊ ",
			"1d7e" => "СЃС–РјРЅР°РґС†СЏС‚СЊ ",
			"1d8e" => "РІС–СЃС–РјРЅР°РґС†СЏС‚СЊ ",
			"1d9e" => "РґРµРІ'СЏС‚РЅР°РґС†СЏС‚СЊ ",
			"2d" => "РґРІР°РґС†СЏС‚СЊ ",
			"3d" => "С‚СЂРёРґС†СЏС‚СЊ ",
			"4d" => "СЃРѕСЂРѕРє ",
			"5d" => "Рї'СЏС‚РґРµСЃСЏС‚ ",
			"6d" => "С€С–СЃС‚РґРµСЃСЏС‚ ",
			"7d" => "СЃС–РјРґРµСЃСЏС‚ ",
			"8d" => "РІС–СЃС–РјРґРµСЃСЏС‚ ",
			"9d" => "РґРµРІ'СЏРЅРѕСЃС‚Рѕ ",
			"5e" => "Рї'СЏС‚СЊ ",
			"6e" => "С€С–СЃС‚СЊ ",
			"7e" => "СЃС–Рј ",
			"8e" => "РІС–СЃС–Рј ",
			"9e" => "РґРµРІ'СЏС‚СЊ ",
			"1e." => "РѕРґРёРЅ РіСЂРёРІРЅСЏ ",
			"2e." => "РґРІР° РіСЂРёРІРЅС– ",
			"3e." => "С‚СЂРё РіСЂРёРІРЅС– ",
			"4e." => "С‡РѕС‚РёСЂРё РіСЂРёРІРЅС– ",
			"1e" => "РѕРґРёРЅ ",
			"2e" => "РґРІР° ",
			"3e" => "С‚СЂРё ",
			"4e" => "С‡РѕС‚РёСЂРё ",
			"1et" => "РѕРґРЅР° С‚РёСЃСЏС‡Р° ",
			"2et" => "РґРІС– С‚РёСЃСЏС‡С– ",
			"3et" => "С‚СЂРё С‚РёСЃСЏС‡С– ",
			"4et" => "С‡РѕС‚РёСЂРё С‚РёСЃСЏС‡С– ",
			"1em" => "РѕРґРёРЅ РјС–Р»СЊР№РѕРЅ ",
			"2em" => "РґРІР° РјС–Р»СЊР№РѕРЅР° ",
			"3em" => "С‚СЂРё РјС–Р»СЊР№РѕРЅР° ",
			"4em" => "С‡РѕС‚РёСЂРё РјС–Р»СЊР№РѕРЅР° ",
			"1eb" => "РѕРґРёРЅ РјС–Р»СЊСЏСЂРґ ",
			"2eb" => "РґРІР° РјС–Р»СЊСЏСЂРґР° ",
			"3eb" => "С‚СЂРё РјС–Р»СЊСЏСЂРґР° ",
			"4eb" => "С‡РѕС‚РёСЂРё РјС–Р»СЊСЏСЂРґР° ",
			"11k" => "11 РєРѕРїС–Р№РѕРє",
			"12k" => "12 РєРѕРїС–Р№РѕРє",
			"13k" => "13 РєРѕРїС–Р№РѕРє",
			"14k" => "14 РєРѕРїС–Р№РѕРє",
			"1k" => "1 РєРѕРїС–Р№РєР°",
			"2k" => "2 РєРѕРїС–Р№РєРё",
			"3k" => "3 РєРѕРїС–Р№РєРё",
			"4k" => "4 РєРѕРїС–Р№РєРё",
			"." => "РіСЂРёРІРµРЅСЊ ",
			"t" => "С‚РёСЃСЏС‡ ",
			"m" => "РјС–Р»СЊР№РѕРЅС–РІ ",
			"b" => "РјС–Р»СЊСЏСЂРґС–РІ ",
			"k" => " РєРѕРїС–Р№РѕРє",
		)
	);


	// k - РєРѕРїРµР№РєРё
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
		// t - С‚С‹СЃСЏС‡Рё; m - РјРёР»РёРѕРЅС‹; b - РјРёР»Р»РёР°СЂРґС‹;
		// e - РµРґРёРЅРёС†С‹; d - РґРµСЃСЏС‚РєРё; c - СЃРѕС‚РЅРё;
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
		$result = "РЅРѕР»СЊ ".$result;

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
