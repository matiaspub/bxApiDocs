<?php
global $STEMMING_EN_STEP2A;
$STEMMING_EN_STEP2A = array("TIONAL"=>"TION", "ENCI"=>"ENCE", "ANCI"=>"ANCE", "ABLI"=>"ABLE", "ENTLI"=>"ENT", "IZER"=>"IZE", "IZATION"=>"IZE", "ATIONAL"=>"ATE", "ATION"=>"ATE", "ATOR"=>"ATE", "ALISM"=>"AL", "ALITI"=>"AL", "ALLI"=>"AL", "FULNESS"=>"FUL", "OUSLI"=>"OUS", "OUSNESS"=>"OUS", "IVENESS"=>"IVE", "IVITI"=>"IVE", "BILITI"=>"BLE", "BLI"=>"BLE", "FULLI"=>"FUL", "LESSLI"=>"LESS");
global $STEMMING_EN_STEP2;
$STEMMING_EN_STEP2 = "/(".implode("|", array_keys($STEMMING_EN_STEP2A))."|OGI|LI)$/";
global $STEMMING_EN_STEP3A;
$STEMMING_EN_STEP3A = array("TIONAL"=>"TION", "ATIONAL"=>"ATE", "ALIZE"=>"AL", "ICATE"=>"IC", "ICITI"=>"IC", "ICAL"=>"IC", "FUL"=>"", "NESS"=>"");
global $STEMMING_EN_STEP3;
$STEMMING_EN_STEP3 = "/(".implode("|", array_keys($STEMMING_EN_STEP3A))."|ATIVE)$/";
global $STEMMING_EN_STEP4A;
$STEMMING_EN_STEP4A = array("AL", "ANCE", "ENCE", "ER", "IC", "ABLE", "IBLE", "ANT", "EMENT", "MENT","ENT", "ISM", "ATE", "ITI", "OUS", "IVE", "IZE");
global $STEMMING_EN_STEP4;
$STEMMING_EN_STEP4 = "/(".implode("|", $STEMMING_EN_STEP4A)."|ION)$/";
global $STEMMING_EN_EX1;
$STEMMING_EN_EX1 = array (
	"SKIS"=>"SKI"
	,"SKIES"=>"SKY"
	,"DYING"=>"DIE"
	,"LYING"=>"LIE"
	,"TYING"=>"TIE"
	,"IDLY"=>"IDL"
	,"GENTLY"=>"GENTL"
	,"UGLY"=>"UGLI"
	,"EARLY"=>"EARLI"
	,"ONLY"=>"ONLI"
	,"SINGLY"=>"SINGL"
	,"SKY"=>"SKY"
	,"NEWS"=>"NEWS"
	,"HOWE"=>"HOWE"
	,"ATLAS"=>"ATLAS"
	,"COSMOS"=>"COSMOS"
	,"BIAS"=>"BIAS"
	,"ANDES"=>"ANDES"
);
global $STEMMING_EN_EX2;
$STEMMING_EN_EX2 = array(
	"INNING" => 1,
	"OUTING" => 1,
	"CANNING" => 1,
	"HERRING" => 1,
	"EARRING" => 1,
	"PROCEED" => 1,
	"EXCEED" => 1,
	"SUCCEED" => 1,
);
function stemming_letter_en()
{
	return "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
}

function stemming_stop_en($sWord)
{
	if(strlen($sWord) < 2)
		return false;
	static $stop_list = false;
	if(!$stop_list)
	{
		$stop_list = array (
			"QUOTE"=>0,"HTTP"=>0,"WWW"=>0,"RU"=>0,"IMG"=>0,"GIF"=>0,"A"=>0,"THE"=>0,"IS"=>0,
			"ARE"=>0,"OFF"=>0,"ON"=>0,"AND"=>0,"IN"=>0,"FOR"=>0,"OF"=>0,"BY"=>0,"WITH"=>0,
			"BE"=>0,"WAS"=>0,"IT"=>0,
		);
		if(defined("STEMMING_STOP_EN"))
		{
			foreach(explode(",", STEMMING_STOP_EN) as $word)
			{
				$word = trim($word);
				if(strlen($word)>0)
					$stop_list[$word]=0;
			}
		}
	}
	return !array_key_exists($sWord, $stop_list);
}

function stemming_upper_en($sText)
{
	return ToUpper($sText);
}

function stemming_en($word)
{
	global $STEMMING_EN_STEP2A;
	global $STEMMING_EN_STEP2;
	global $STEMMING_EN_STEP3A;
	global $STEMMING_EN_STEP3;
	global $STEMMING_EN_STEP4A;
	global $STEMMING_EN_STEP4;
	global $STEMMING_EN_EX1;
	global $STEMMING_EN_EX2;
	//If the word has two letters or less, leave it as it is.
	$word_len = strlen($word);
	if($word_len<=2)
		return $word;
	if(array_key_exists($word, $STEMMING_EN_EX1))
		return $STEMMING_EN_EX1[$word];
	//Set initial y, or y after a vowel, to Y, and then establish the regions R1 and R2. (See  note on vowel marking.)
	$vowels = "AEIOUY";
	$word=preg_replace("/^Y/","y",$word);
	$word=preg_replace("/([$vowels])(Y)/","\\1y",$word);
	//In any word, R1 is the region after the first non-vowel following a vowel, or the end of the word if it contains no such a non-vowel.
	$R1=0;
	while( ($R1<$word_len) && (strpos($vowels, substr($word, $R1, 1))===false))
		$R1++;
	while( ($R1<$word_len) && (strpos($vowels, substr($word, $R1, 1))!==false))
		$R1++;
	if($R1<$word_len)
		$R1++;
	if(preg_match("/^COMMUN/", $word))
		$R1 = 6;
	if(preg_match("/^GENER/", $word))
		$R1 = 5;

	$R2=$R1;
	while( ($R2<$word_len) && (strpos($vowels, substr($word, $R2, 1))===false))
		$R2++;
	while( ($R2<$word_len) && (strpos($vowels, substr($word, $R2, 1))!==false))
		$R2++;
	if($R2<$word_len)
		$R2++;
	//Step 1a:
	//	Search for the longest among the following suffixes, and perform the action indicated.
	$found=array();
	if(preg_match("/(SSES|IED|IES|US|SS|S)$/", $word, $found))
		switch ($found[0]) {
			//sses - replace by ss
			case "SSES":
				$word = substr($word, 0, $word_len-4)."SS";
				break;
			//ied+   ies* - replace by i if preceded by more than one letter, otherwise by ie  (so ties -> tie, cries -> cri)
			case "IED":
			case "IES":
				if(strlen($word)>4)
					$word = substr($word, 0, $word_len-3)."I";
				else
					$word = substr($word, 0, $word_len-3)."IE";
				break;
			//s  delete if the preceding word part contains a vowel not immediately before the s (so gas and this retain the s, gaps and kiwis lose it)
			case "S":
				if(preg_match("/([$vowels].*.)(S)$/", $word))
					$word = substr($word, 0, $word_len-1);
				break;
			//us+   ss - do nothing
		}

	if(array_key_exists($word, $STEMMING_EN_EX2))
		return $word;
	//Step 1b:
	//	Search for the longest among the following suffixes, and perform the action indicated.
	//eed   eedly+ - replace by ee if in R1
	if(preg_match("/(EEDLY|INGLY|EDLY|EED|ING|ED)$/", $word, $found))
		switch($found[0]) {
			case "EEDLY":
			case "EED":
				if(preg_match("/".$found[0]."$/", substr($word, $R1)))
					$word = substr($word, 0, strlen($word)-strlen($found[0]))."EE";
				break;
			default:
				//delete if the preceding word part contains a vowel, and then
				if(($step1b=preg_replace("/([$vowels].*)(ED|EDLY|ING|INGLY)$/", "\\1", $word))!=$word)
				{
					//if the word ends at, bl or iz add e (so luxuriat -> luxuriate), or
					if(($step1b1=preg_replace("/(AT|BL|IZ)$/", "\\1E", $step1b))==$step1b)
						//if the word ends with a double remove the last letter (so hopp -> hop), or
						if(preg_match("/(BB|DD|FF|GG|MM|NN|PP|RR|TT)$/", $step1b))
							$step1b1=substr($step1b, 0, strlen($step1b)-1);
						else
						{
					//if the word is short, add e (so hop -> hope)
					//A word is called short if it consists of a short syllable preceded by zero or more consonants.
					//Define a short syllable in a word as either (a) a vowel followed by a non-vowel other than w, x or Y and preceded by a non-vowel, or * (b) a vowel at the beginning of the word followed by a non-vowel.
							if(preg_match("/^[^$vowels]+[$vowels][^WXy$vowels]$/", $step1b)
								|| preg_match("/^[$vowels][^$vowels]$/", $step1b))
								$step1b1 = $step1b."E";
						}
					$step1b = $step1b1;
				}
				$word = $step1b;
		}
	//Step 1c: *
	//	replace suffix y or Y by i if preceded by a non-vowel which is not the first letter of the word (so cry -> cri, by -> by, say -> say)
	$word=preg_replace("/^(.+[^$vowels])([yY])$/",  "\\1I", $word);
	//Step 2:
	//	Search for the longest among the following suffixes, and, if found and in R1, perform the action indicated.
	if(preg_match($STEMMING_EN_STEP2, $word, $found) && preg_match("/".$found[0]."$/", substr($word, $R1)))
		switch ($found[0]) {
			case "OGI":
				if(preg_match("/LOGI$/", $word))
					$word = substr($word, 0, strlen($word)-3)."OG";
				break;
			case "LI":
				if(preg_match("/[CDEGHKMNRT]LI$/", $word))
					$word = substr($word, 0, strlen($word)-2);
				break;
			default:
				$word = substr($word, 0, strlen($word)-strlen($found[0])).$STEMMING_EN_STEP2A[$found[0]];
		}
	//Step 3:
	//	Search for the longest among the following suffixes, and, if found and in R1, perform the action indicated.
	if(preg_match($STEMMING_EN_STEP3, $word, $found) && preg_match("/".$found[0]."$/", substr($word, $R1)))
		switch($found[0]) {
			case "ATIVE":
				if(preg_match("/ATIVE$/", substr($word, $R2)))
					$word = substr($word, 0, strlen($word)-5);
				break;
			default:
				$word = substr($word, 0, strlen($word)-strlen($found[0])).$STEMMING_EN_STEP3A[$found[0]];
		}
	//Step 4:
	//	Search for the longest among the following suffixes, and, if found and in R2, perform the action indicated.
	if(preg_match($STEMMING_EN_STEP4, $word, $found) && preg_match("/".$found[0]."$/", substr($word, $R2)))
		switch($found[0]) {
			case "ION":
				if(preg_match("/[ST]ION$/", $word))
					$word = substr($word, 0, strlen($word)-strlen($found[0]));
				break;
			default:
				$word = substr($word, 0, strlen($word)-strlen($found[0]));
		}
	//Step 5:
	if(preg_match("/E$/", substr($word, $R2))
		|| (preg_match("/E$/", substr($word, $R1))
			&& !
			//Define a short syllable in a word as either (a) a vowel followed by a non-vowel other than w, x or Y and preceded by a non-vowel, or * (b) a vowel at the beginning of the word followed by a non-vowel.
					(preg_match("/[^$vowels][$vowels][^WXy$vowels].$/", $word)
						|| preg_match("/^[$vowels][^$vowels].$/", $word))
			))
		$word = substr($word, 0, strlen($word)-1);
	elseif(preg_match("/L$/", substr($word, $R2)) && preg_match("/LL$/", $word))
		$word = substr($word, 0, strlen($word)-1);

	return str_replace("y", "Y", $word);
}
?>
