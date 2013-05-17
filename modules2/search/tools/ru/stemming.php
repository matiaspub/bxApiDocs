<?php
global $STEMMING_RU_VOWELS;
$STEMMING_RU_VOWELS = "АЕИОУЫЭЮЯ";
global $STEMMING_RU_PERFECTIVE_GERUND;
$STEMMING_RU_PERFECTIVE_GERUND = "/(ЫВШИСЬ|ИВШИСЬ|ЯВШИСЬ|АВШИСЬ|ЫВШИ|ИВШИ|ЯВШИ|АВШИ|ЫВ|ИВ|ЯВ|АВ)$/".BX_UTF_PCRE_MODIFIER;

$STEMMING_RU_ADJECTIVE=array("ЕЕ"=>2, "ИЕ"=>2, "ЫЕ"=>2, "ОЕ"=>2, "ИМИ"=>3, "ЫМИ"=>3, "ЕЙ"=>2, "ИЙ"=>2, "ЫЙ"=>2, "ОЙ"=>2, "ЕМ"=>2, "ИМ"=>2, "ЫМ"=>2, "ОМ"=>2, "ЕГО"=>2, "ОГО"=>3, "ЕМУ"=>3, "ОМУ"=>3, "ИХ"=>2, "ЫХ"=>2, "УЮ"=>2, "ЮЮ"=>2, "АЯ"=>2, "ЯЯ"=>2, "ОЮ"=>2, "ЕЮ"=>2);
$STEMMING_RU_PARTICIPLE_GR1=array("ЕМ"=>2, "НН"=>2, "ВШ"=>2, "ЮЩ"=>2, "Щ"=>1);
$STEMMING_RU_PARTICIPLE_GR2=array("ИВШ"=>3, "ЫВШ"=>3, "УЮЩ"=>3);
$STEMMING_RU_ADJECTIVAL_GR1=array();
$STEMMING_RU_ADJECTIVAL_GR2=array();
foreach($STEMMING_RU_ADJECTIVE as $i => $il)
{
	foreach($STEMMING_RU_PARTICIPLE_GR1 as $j => $jl) $STEMMING_RU_ADJECTIVAL_GR1[$j.$i]=$jl+$il;
	foreach($STEMMING_RU_PARTICIPLE_GR2 as $j => $jl) $STEMMING_RU_ADJECTIVAL_GR2[$j.$i]=$jl+$il;
}
global $STEMMING_RU_ADJECTIVAL1;
arsort($STEMMING_RU_ADJECTIVAL_GR1);
$STEMMING_RU_ADJECTIVAL1="/([АЯ])(".implode("|", array_keys($STEMMING_RU_ADJECTIVAL_GR1)).")$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_ADJECTIVAL2;
foreach($STEMMING_RU_ADJECTIVE as $i => $il)
	$STEMMING_RU_ADJECTIVAL_GR2[$i]=$il;
arsort($STEMMING_RU_ADJECTIVAL_GR2);
$STEMMING_RU_ADJECTIVAL2="/(".implode("|", array_keys($STEMMING_RU_ADJECTIVAL_GR2)).")$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_VERB1;
$STEMMING_RU_VERB1="/([АЯ])(ННО|ЕТЕ|ЙТЕ|ЕШЬ|ЛА|НА|ЛИ|ЕМ|ЛО|НО|ЕТ|ЮТ|НЫ|ТЬ|Й|Л|Н)$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_VERB2;
$STEMMING_RU_VERB2="/(ЕЙТЕ|УЙТЕ|ИЛА|ЫЛА|ЕНА|ИТЕ|ИЛИ|ЫЛИ|ИЛО|ЫЛО|ЕНО|УЕТ|УЮТ|ЕНЫ|ИТЬ|ЫТЬ|ИШЬ|ЕЙ|УЙ|ИЛ|ЫЛ|ИМ|ЫМ|ЕН|ЯТ|ИТ|ЫТ|УЮ|Ю)$/".BX_UTF_PCRE_MODIFIER;
global $STEMMING_RU_NOUN;
$STEMMING_RU_NOUN="/(ИЯМИ|ИЯХ|ИЕМ|ИЯМ|АМИ|ЯМИ|ЬЯ|ИЯ|ЬЮ|ИЮ|ЯХ|АХ|ОМ|АМ|ЕМ|ЯМ|ИЙ|ОЙ|ЕЙ|ИЕЙ|ИИ|ЕИ|ЬЕ|ИЕ|ОВ|ЕВ|Ю|Ь|Ы|У|О|Й|И|Е|Я|А)$/".BX_UTF_PCRE_MODIFIER;
function stemming_letter_ru()
{
	return "ёйцукенгшщзхъфывапролджэячсмитьбюЁЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮ";
}
function stemming_ru_sort($a, $b)
{
	$al = strlen($a);
	$bl = strlen($b);
	if($al == $bl)
		return 0;
	elseif($al < $bl)
		return 1;
	else
		return -1;
}
function stemming_stop_ru($sWord)
{
	if(strlen($sWord) < 2)
		return false;
	static $stop_list = false;
	if(!$stop_list)
	{
		$stop_list = array (
			"QUOTE"=>0,"HTTP"=>0,"WWW"=>0,"RU"=>0,"IMG"=>0,"GIF"=>0,"БЕЗ"=>0,"БЫ"=>0,"БЫЛ"=>0,
			"БЫТ"=>0,"ВАМ"=>0,"ВАШ"=>0,"ВО"=>0,"ВОТ"=>0,"ВСЕ"=>0,"ВЫ"=>0,"ГДЕ"=>0,"ДА"=>0,
			"ДАЖ"=>0,"ДЛЯ"=>0,"ДО"=>0,"ЕГ"=>0,"ЕСЛ"=>0,"ЕСТ"=>0,"ЕЩ"=>0,"ЖЕ"=>0,"ЗА"=>0,
			"ИЗ"=>0,"ИЛИ"=>0,"ИМ"=>0,"ИХ"=>0,"КАК"=>0,"КОГД"=>0,"КТО"=>0,"ЛИ"=>0,"ЛИБ"=>0,
			"МЕН"=>0,"МНЕ"=>0,"МО"=>0,"МЫ"=>0,"НА"=>0,"НАД"=>0,"НЕ"=>0,"НЕТ"=>0,"НИ"=>0,
			"НО"=>0,"НУ"=>0,"ОБ"=>0,"ОН"=>0,"ОТ"=>0,"ОЧЕН"=>0,"ПО"=>0,"ПОД"=>0,"ПРИ"=>0,
			"ПРО"=>0,"САМ"=>0,"СЕБ"=>0,"СВО"=>0,"ТАК"=>0,"ТАМ"=>0,"ТЕБ"=>0,"ТО"=>0,"ТОЖ"=>0,
			"ТОЛЬК"=>0,"ТУТ"=>0,"ТЫ"=>0,"УЖ"=>0,"ХОТ"=>0,"ЧЕГ"=>0,"ЧЕМ"=>0,"ЧТО"=>0,"ЧТОБ"=>0,
			"ЭТ"=>0,"ЭТОТ"=>0,
		);
		if(defined("STEMMING_STOP_RU"))
		{
			foreach(explode(",", STEMMING_STOP_RU) as $word)
			{
				$word = trim($word);
				if(strlen($word)>0)
					$stop_list[$word]=0;
			}
		}
	}
	return !array_key_exists($sWord, $stop_list);
}

function stemming_upper_ru($sText)
{
	return str_replace(array("Ё"), array("Е"), ToUpper($sText, "ru"));
}

function stemming_ru($word, $flags = 0)
{
	global $STEMMING_RU_VOWELS;
	global $STEMMING_RU_PERFECTIVE_GERUND;
	global $STEMMING_RU_ADJECTIVAL1;
	global $STEMMING_RU_ADJECTIVAL2;
	global $STEMMING_RU_VERB1;
	global $STEMMING_RU_VERB2;
	global $STEMMING_RU_NOUN;
	//There is a 33rd letter, ё (?), but it is rarely used, and we assume it is mapped into е (e).
	$word=str_replace("Ё", "Е", $word);
	//Exceptions
	static $STEMMING_RU_EX = array(
		"БЕЗЕ"=>true,
		"БЫЛЬ"=>true,
		"МЕНЮ"=>true,
		"ГРАНАТ"=>true,
		"ГРАНИТ"=>true,
		"ТЕРМИНАЛ"=>true,
		"ИЛИ"=>true,
		"РУКАВ"=>true,
		"ПРИЕМ"=>true,
	);
	if(isset($STEMMING_RU_EX[$word]))
		return $word;

	//HERE IS AN ATTEMPT TO STEM RUSSIAN SECOND NAMES BEGINS
	//http://www.gramma.ru/SPR/?id=2.8
	if($flags & 1)
	{
		if(preg_match("/(ОВ|ЕВ)$/", $word))
		{
			return array(
				stemming_ru($word."А"),
				stemming_ru($word),
			);
		}
		if(preg_match("/(ОВ|ЕВ)(А|У|ЫМ|Е)$/", $word, $found))
		{
			return array(
				stemming_ru($word),
				stemming_ru(substr($word, 0, -strlen($found[2]))),
			);
		}
	}
	//HERE IS AN ATTEMPT TO STEM RUSSIAN SECOND NAMES ENDS

	//In any word, RV is the region after the first vowel, or the end of the word if it contains no vowel.
	//All tests take place in the the RV part of the word.
	$found=array();
	if(preg_match("/^(.*?[$STEMMING_RU_VOWELS])(.+)$/".BX_UTF_PCRE_MODIFIER, $word, $found))
	{
		$rv = $found[2];
		$word = $found[1];
	}
	else
	{
		return $word;
	}

	//Do each of steps 1, 2, 3 and 4.
	//Step 1: Search for a PERFECTIVE GERUND ending. If one is found remove it, and that is then the end of step 1.


	if(preg_match($STEMMING_RU_PERFECTIVE_GERUND, $rv, $found))
	{
		switch($found[0]) {
			case "АВ":
			case "АВШИ":
			case "АВШИСЬ":
			case "ЯВ":
			case "ЯВШИ":
			case "ЯВШИСЬ":
				$rv = substr($rv, 0, 1-strlen($found[0]));
				break;
			default:
				$rv = substr($rv, 0, -strlen($found[0]));
		}
	}
	//Otherwise try and remove a REFLEXIVE ending, and then search in turn for
	// (1) an ADJECTIVE,
	// (2) a VERB or (3)
	// a NOUN ending.
	// As soon as one of the endings (1) to (3) is found remove it, and terminate step 1.
	else
	{
		$rv = preg_replace("/(СЯ|СЬ)$/".BX_UTF_PCRE_MODIFIER, "", $rv);
		//ADJECTIVAL
		if(preg_match($STEMMING_RU_ADJECTIVAL1, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[2]));
		elseif(preg_match($STEMMING_RU_ADJECTIVAL2, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[0]));
		elseif(preg_match($STEMMING_RU_VERB1, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[2]));
		elseif(preg_match($STEMMING_RU_VERB2, $rv, $found))
			$rv = substr($rv, 0, -strlen($found[0]));
		else
			$rv = preg_replace($STEMMING_RU_NOUN, "", $rv);
	}

	//Step 2: If the word ends with и (i), remove it.
	if(substr($rv, -1) == "И")
		$rv = substr($rv, 0, -1);
	//Step 3: Search for a DERIVATIONAL ending in R2 (i.e. the entire ending must lie in R2), and if one is found, remove it.
	//R1 is the region after the first non-vowel following a vowel, or the end of the word if there is no such non-vowel.
	if(preg_match("/(ОСТЬ|ОСТ)$/".BX_UTF_PCRE_MODIFIER, $rv))
	{
		$R1=0;
		$rv_len = strlen($rv);
		while( ($R1<$rv_len) && (strpos($STEMMING_RU_VOWELS, substr($rv,$R1,1))!==false) )
			$R1++;
		if($R1 < $rv_len)
			$R1++;
		//R2 is the region after the first non-vowel following a vowel in R1, or the end of the word if there is no such non-vowel.
		$R2 = $R1;
		while( ($R2<$rv_len) && (strpos($STEMMING_RU_VOWELS, substr($rv,$R2,1))===false) )
			$R2++;
		while( ($R2<$rv_len) && (strpos($STEMMING_RU_VOWELS, substr($rv,$R2,1))!==false) )
			$R2++;
		if($R2 < $rv_len)
			$R2++;
		//"ОСТЬ", "ОСТ"
		if((substr($rv, -4) == "ОСТЬ") && ($rv_len >= ($R2+4)))
			$rv = substr($rv, 0, $rv_len - 4);
		elseif((substr($rv, -3) == "ОСТ") && ($rv_len >= ($R2+3)))
			$rv = substr($rv, 0, $rv_len - 3);
	}
	//Step 4: (1) Undouble н (n), or, (2) if the word ends with a SUPERLATIVE ending, remove it and undouble н (n), or (3) if the word ends ь (') (soft sign) remove it.
	$rv = preg_replace("/(ЕЙШЕ|ЕЙШ)$/".BX_UTF_PCRE_MODIFIER, "", $rv);
	$r = preg_replace("/НН$/".BX_UTF_PCRE_MODIFIER, "Н", $rv);
	if($r == $rv)
		$rv = preg_replace("/Ь$/".BX_UTF_PCRE_MODIFIER, "", $rv);
	else
		$rv = $r;

	return $word.$rv;
}
?>
