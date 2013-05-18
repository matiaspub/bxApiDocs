<?php
global $STEMMING_RU_VOWELS;
$STEMMING_RU_VOWELS = "РђР•Р?РћРЈР«Р­Р®РЇ";
global $STEMMING_RU_PERFECTIVE_GERUND;
$STEMMING_RU_PERFECTIVE_GERUND = "/(Р«Р’РЁР?РЎР¬|Р?Р’РЁР?РЎР¬|РЇР’РЁР?РЎР¬|РђР’РЁР?РЎР¬|Р«Р’РЁР?|Р?Р’РЁР?|РЇР’РЁР?|РђР’РЁР?|Р«Р’|Р?Р’|РЇР’|РђР’)$/".BX_UTF_PCRE_MODIFIER;

$STEMMING_RU_ADJECTIVE=array("Р•Р•"=>2, "Р?Р•"=>2, "Р«Р•"=>2, "РћР•"=>2, "Р?РњР?"=>3, "Р«РњР?"=>3, "Р•Р™"=>2, "Р?Р™"=>2, "Р«Р™"=>2, "РћР™"=>2, "Р•Рњ"=>2, "Р?Рњ"=>2, "Р«Рњ"=>2, "РћРњ"=>2, "Р•Р“Рћ"=>2, "РћР“Рћ"=>3, "Р•РњРЈ"=>3, "РћРњРЈ"=>3, "Р?РҐ"=>2, "Р«РҐ"=>2, "РЈР®"=>2, "Р®Р®"=>2, "РђРЇ"=>2, "РЇРЇ"=>2, "РћР®"=>2, "Р•Р®"=>2);
$STEMMING_RU_PARTICIPLE_GR1=array("Р•Рњ"=>2, "РќРќ"=>2, "Р’РЁ"=>2, "Р®Р©"=>2, "Р©"=>1);
$STEMMING_RU_PARTICIPLE_GR2=array("Р?Р’РЁ"=>3, "Р«Р’РЁ"=>3, "РЈР®Р©"=>3);
$STEMMING_RU_ADJECTIVAL_GR1=array();
$STEMMING_RU_ADJECTIVAL_GR2=array();
foreach($STEMMING_RU_ADJECTIVE as $i => $il)
{
	foreach($STEMMING_RU_PARTICIPLE_GR1 as $j => $jl) $STEMMING_RU_ADJECTIVAL_GR1[$j.$i]=$jl+$il;
	foreach($STEMMING_RU_PARTICIPLE_GR2 as $j => $jl) $STEMMING_RU_ADJECTIVAL_GR2[$j.$i]=$jl+$il;
}
global $STEMMING_RU_ADJECTIVAL1;
arsort($STEMMING_RU_ADJECTIVAL_GR1);
$STEMMING_RU_ADJECTIVAL1="/([РђРЇ])(".implode("|", array_keys($STEMMING_RU_ADJECTIVAL_GR1)).")$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_ADJECTIVAL2;
foreach($STEMMING_RU_ADJECTIVE as $i => $il)
	$STEMMING_RU_ADJECTIVAL_GR2[$i]=$il;
arsort($STEMMING_RU_ADJECTIVAL_GR2);
$STEMMING_RU_ADJECTIVAL2="/(".implode("|", array_keys($STEMMING_RU_ADJECTIVAL_GR2)).")$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_VERB1;
$STEMMING_RU_VERB1="/([РђРЇ])(РќРќРћ|Р•РўР•|Р™РўР•|Р•РЁР¬|Р›Рђ|РќРђ|Р›Р?|Р•Рњ|Р›Рћ|РќРћ|Р•Рў|Р®Рў|РќР«|РўР¬|Р™|Р›|Рќ)$/".BX_UTF_PCRE_MODIFIER;

global $STEMMING_RU_VERB2;
$STEMMING_RU_VERB2="/(Р•Р™РўР•|РЈР™РўР•|Р?Р›Рђ|Р«Р›Рђ|Р•РќРђ|Р?РўР•|Р?Р›Р?|Р«Р›Р?|Р?Р›Рћ|Р«Р›Рћ|Р•РќРћ|РЈР•Рў|РЈР®Рў|Р•РќР«|Р?РўР¬|Р«РўР¬|Р?РЁР¬|Р•Р™|РЈР™|Р?Р›|Р«Р›|Р?Рњ|Р«Рњ|Р•Рќ|РЇРў|Р?Рў|Р«Рў|РЈР®|Р®)$/".BX_UTF_PCRE_MODIFIER;
global $STEMMING_RU_NOUN;
$STEMMING_RU_NOUN="/(Р?РЇРњР?|Р?РЇРҐ|Р?Р•Рњ|Р?РЇРњ|РђРњР?|РЇРњР?|Р¬РЇ|Р?РЇ|Р¬Р®|Р?Р®|РЇРҐ|РђРҐ|РћРњ|РђРњ|Р•Рњ|РЇРњ|Р?Р™|РћР™|Р•Р™|Р?Р•Р™|Р?Р?|Р•Р?|Р¬Р•|Р?Р•|РћР’|Р•Р’|Р®|Р¬|Р«|РЈ|Рћ|Р™|Р?|Р•|РЇ|Рђ)$/".BX_UTF_PCRE_MODIFIER;
function stemming_letter_ru()
{
	return "С‘Р№С†СѓРєРµРЅРіС€С‰Р·С…СЉС„С‹РІР°РїСЂРѕР»РґР¶СЌСЏС‡СЃРјРёС‚СЊР±СЋРЃР™Р¦РЈРљР•РќР“РЁР©Р—РҐРЄР¤Р«Р’РђРџР РћР›Р”Р–Р­РЇР§РЎРњР?РўР¬Р‘Р®";
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
			"QUOTE"=>0,"HTTP"=>0,"WWW"=>0,"RU"=>0,"IMG"=>0,"GIF"=>0,"Р‘Р•Р—"=>0,"Р‘Р«"=>0,"Р‘Р«Р›"=>0,
			"Р‘Р«Рў"=>0,"Р’РђРњ"=>0,"Р’РђРЁ"=>0,"Р’Рћ"=>0,"Р’РћРў"=>0,"Р’РЎР•"=>0,"Р’Р«"=>0,"Р“Р”Р•"=>0,"Р”Рђ"=>0,
			"Р”РђР–"=>0,"Р”Р›РЇ"=>0,"Р”Рћ"=>0,"Р•Р“"=>0,"Р•РЎР›"=>0,"Р•РЎРў"=>0,"Р•Р©"=>0,"Р–Р•"=>0,"Р—Рђ"=>0,
			"Р?Р—"=>0,"Р?Р›Р?"=>0,"Р?Рњ"=>0,"Р?РҐ"=>0,"РљРђРљ"=>0,"РљРћР“Р”"=>0,"РљРўРћ"=>0,"Р›Р?"=>0,"Р›Р?Р‘"=>0,
			"РњР•Рќ"=>0,"РњРќР•"=>0,"РњРћ"=>0,"РњР«"=>0,"РќРђ"=>0,"РќРђР”"=>0,"РќР•"=>0,"РќР•Рў"=>0,"РќР?"=>0,
			"РќРћ"=>0,"РќРЈ"=>0,"РћР‘"=>0,"РћРќ"=>0,"РћРў"=>0,"РћР§Р•Рќ"=>0,"РџРћ"=>0,"РџРћР”"=>0,"РџР Р?"=>0,
			"РџР Рћ"=>0,"РЎРђРњ"=>0,"РЎР•Р‘"=>0,"РЎР’Рћ"=>0,"РўРђРљ"=>0,"РўРђРњ"=>0,"РўР•Р‘"=>0,"РўРћ"=>0,"РўРћР–"=>0,
			"РўРћР›Р¬Рљ"=>0,"РўРЈРў"=>0,"РўР«"=>0,"РЈР–"=>0,"РҐРћРў"=>0,"Р§Р•Р“"=>0,"Р§Р•Рњ"=>0,"Р§РўРћ"=>0,"Р§РўРћР‘"=>0,
			"Р­Рў"=>0,"Р­РўРћРў"=>0,
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
	return str_replace(array("РЃ"), array("Р•"), ToUpper($sText, "ru"));
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
	//There is a 33rd letter, С‘ (?), but it is rarely used, and we assume it is mapped into Рµ (e).
	$word=str_replace("РЃ", "Р•", $word);
	//Exceptions
	static $STEMMING_RU_EX = array(
		"Р‘Р•Р—Р•"=>true,
		"Р‘Р«Р›Р¬"=>true,
		"РњР•РќР®"=>true,
		"Р“Р РђРќРђРў"=>true,
		"Р“Р РђРќР?Рў"=>true,
		"РўР•Р РњР?РќРђР›"=>true,
		"Р?Р›Р?"=>true,
		"Р РЈРљРђР’"=>true,
		"РџР Р?Р•Рњ"=>true,
	);
	if(isset($STEMMING_RU_EX[$word]))
		return $word;

	//HERE IS AN ATTEMPT TO STEM RUSSIAN SECOND NAMES BEGINS
	//http://www.gramma.ru/SPR/?id=2.8
	if($flags & 1)
	{
		if(preg_match("/(РћР’|Р•Р’)$/", $word))
		{
			return array(
				stemming_ru($word."Рђ"),
				stemming_ru($word),
			);
		}
		if(preg_match("/(РћР’|Р•Р’)(Рђ|РЈ|Р«Рњ|Р•)$/", $word, $found))
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
			case "РђР’":
			case "РђР’РЁР?":
			case "РђР’РЁР?РЎР¬":
			case "РЇР’":
			case "РЇР’РЁР?":
			case "РЇР’РЁР?РЎР¬":
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
		$rv = preg_replace("/(РЎРЇ|РЎР¬)$/".BX_UTF_PCRE_MODIFIER, "", $rv);
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

	//Step 2: If the word ends with Рё (i), remove it.
	if(substr($rv, -1) == "Р?")
		$rv = substr($rv, 0, -1);
	//Step 3: Search for a DERIVATIONAL ending in R2 (i.e. the entire ending must lie in R2), and if one is found, remove it.
	//R1 is the region after the first non-vowel following a vowel, or the end of the word if there is no such non-vowel.
	if(preg_match("/(РћРЎРўР¬|РћРЎРў)$/".BX_UTF_PCRE_MODIFIER, $rv))
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
		//"РћРЎРўР¬", "РћРЎРў"
		if((substr($rv, -4) == "РћРЎРўР¬") && ($rv_len >= ($R2+4)))
			$rv = substr($rv, 0, $rv_len - 4);
		elseif((substr($rv, -3) == "РћРЎРў") && ($rv_len >= ($R2+3)))
			$rv = substr($rv, 0, $rv_len - 3);
	}
	//Step 4: (1) Undouble РЅ (n), or, (2) if the word ends with a SUPERLATIVE ending, remove it and undouble РЅ (n), or (3) if the word ends СЊ (') (soft sign) remove it.
	$rv = preg_replace("/(Р•Р™РЁР•|Р•Р™РЁ)$/".BX_UTF_PCRE_MODIFIER, "", $rv);
	$r = preg_replace("/РќРќ$/".BX_UTF_PCRE_MODIFIER, "Рќ", $rv);
	if($r == $rv)
		$rv = preg_replace("/Р¬$/".BX_UTF_PCRE_MODIFIER, "", $rv);
	else
		$rv = $r;

	return $word.$rv;
}
?>
