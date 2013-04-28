<?
function stemming_init($sLang="ru")
{
	static $arStemFunc = false;

	//Init all languages
	if($arStemFunc === false)
	{
		$arStemFunc = array();
		$rsLanguages = CLanguage::GetList(($b=""), ($o=""));
		while($arLanguage = $rsLanguages->Fetch())
			stemming_init($arLanguage["LID"]);
	}

	//Check if language was not used
	if($sLang !== false && !isset($arStemFunc[$sLang]))
	{
		$stemming_function_suf = $sLang;

		if(!function_exists("stemming_".$sLang))
		{
			$strFileName=$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".$sLang."/search/stemming.php";
			if(file_exists($strFileName))
				@include($strFileName);
			if(!function_exists("stemming_".$sLang))
			{
				$strFileName=$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/tools/".$sLang."/stemming.php";
				if(file_exists($strFileName))
					@include($strFileName);
				if(!function_exists("stemming_".$sLang))
				{
					$stemming_function_suf = "default";
				}
			}
		}

		$stemming_stop_function = "stemming_stop_".$sLang;
		if(!function_exists($stemming_stop_function))
			$stemming_stop_function = "stemming_stop_default";

		$stemming_upper_function = "stemming_upper_".$sLang;
		if(!function_exists($stemming_upper_function))
			$stemming_upper_function = "stemming_upper_default";

		$letters = stemming_letter_default();
		$stemming_letter_function = "stemming_letter_".$sLang;
		if(function_exists($stemming_letter_function))
			$letters .= $stemming_letter_function();
		$letters .= COption::GetOptionString("search", "letters");

		if(function_exists($stemming_letter_function))
			$abc = $stemming_letter_function();
		else
			$abc = "";

		if(strlen($abc) <= 0)
			$abc = stemming_letter_default();

		$arStemFunc[$sLang] = array(
			"stem" => "stemming_".$stemming_function_suf,
			"stop" => $stemming_stop_function,
			"upper" => $stemming_upper_function,
			"letters" => $letters,
			"pcre_letters" => str_replace(
				array("\\"  , "-"  , "^"  , "]"  , "/"),
				array("\\\\", "\\-", "\\^", "\\]", "\\/"),
				$letters
			),
			"abc" => $abc,
			"pcre_abc" => str_replace(
				array("\\"  , "-"  , "^"  , "]"  , "/"),
				array("\\\\", "\\-", "\\^", "\\]", "\\/"),
				$abc
			),
		);
	}

	if($sLang === false)
		return $arStemFunc;
	else
		return $arStemFunc[$sLang];
}

function stemming_upper($sText, $sLang="ru")
{
	$arStemFunc = stemming_init($sLang);
	$upper_function = $arStemFunc["upper"];
	return $upper_function($sText);
}

function stemming_split($sText, $sLang="ru")
{
	$arStemFunc = stemming_init($sLang);

	$words = array();

	$tok = " ";
	$sText = preg_replace("/[^".$arStemFunc["pcre_letters"]."]/".BX_UTF_PCRE_MODIFIER, $tok, ToUpper($sText));

	$word = strtok($sText, $tok);
	while($word !== false)
	{
		$word = substr($word, 0, 100);

		if(!isset($words[$word]))
			$words[$word] = strpos($sText, $word);

		$word = strtok($tok);
	}

	return $words;
}


/**
 * <p>Функция производит морфологический анализ текста и возвращает массив неизменных основ слов.</p> <p>Сначала ищется и подключается анализатор для заданного языка (параметр sLang):</p> <ul> <li>Выполняется попытка подключения файла $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/".$sLang."/search/stemming.php" и если в нем определена функция "stemming_"$sLang, то на этом поиск завершается.</li> <li>Иначе выполняется попытка подключить анализатор из набора поставляемых с модулем поиска.</li> <li>Если и эта попытка провалилась, то будет использован анализатор по умолчанию <a href="http://dev.1c-bitrix.ruapi_help/search/functions/stemming_default.php">Stemming_default</a>.</li> </ul> <p>Также ожидается, что в одном из файлов определена функция поиска стоп слов - "stemming_stop_".$sLang. Если это не так, то для определения списка стоп слов будет использована функция по умолчанию <a href="http://dev.1c-bitrix.ruapi_help/search/functions/stemming_stop_default.php">Stemming_stop_default</a>.</p> <p>Далее текст приводится к верхнему регистру и разбивается на отдельные слова. Разбивка происходит путем удаления всех символов не входящих в алфавит языка. Алфавит задается следующим образом:</p> <ul> <li>В расчет принимаются все символы возвращаемые функцией <a href="http://dev.1c-bitrix.ruapi_help/search/functions/stemming_letter_default.php">Stemming_letter_default</a>;</li> <li>К ним добавляются символы возвращаемые функцией "stemming_letter_".$sLang (если таковая определена);</li> <li>А также символы входящие в соответствующую настройку модуля поиска.</li> </ul> <p>Для каждого слова с помощью найденной ранее функции определяется неизменная основа. После этого, если слово не является стоп словом оно заносится в массив который будет возвращен в качестве результата.</p>
 *
 *
 *
 *
 * @param string $sText  Содержит текст для морфологического анализа.
 *
 *
 *
 * @param string $sLang = "ru" Идентификатор языка текста.
 *
 *
 *
 * @return array <p>Функция возвращает ассоциативный массив ключами которого
 * являются неизменные основы слов, а значениями частота их
 * появления.</p>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * $ar = stemming("Зебры любят зебр, а не слонов!", "ru");
 * print_r($ar);
 * 
 * //Будет напечатано:
 * //<
 * array(
 * 	"ЗЕБР" =&gt; 2
 * 	"ЛЮБ" =&gt; 1
 * 	"СЛОН" =&gt; 1
 * )
 * >//
 * ?&gt;
 * </pre>
 *
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/search/functions/stemming_default.php">Stemming_default</a></li>
 * <li><a href="http://dev.1c-bitrix.ruapi_help/search/functions/stemming_stop_default.php">Stemming_stop_default</a></li>
 * <li><a
 * href="http://dev.1c-bitrix.ruapi_help/search/functions/stemming_letter_default.php">Stemming_letter_default</a></li>
 * </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/functions/stemming.php
 * @author Bitrix
 */
function stemming($sText, $sLang="ru", $bIgnoreStopWords = false, $bReturnPositions = false)
{
	static $STOP_CACHE=array();
	if(!isset($STOP_CACHE[$sLang]))
		$STOP_CACHE[$sLang] = array();
	$stop_cache = &$STOP_CACHE[$sLang];

	//Result
	$stems = array();

	//Get info about all languages
	$arStemInfo = stemming_init(false);
	//Add default functions if language was not defined
	if(!isset($arStemInfo[$sLang]))
		$arStemInfo[$sLang] = stemming_init($sLang);

	$stem_func = $arStemInfo[$sLang]["stem"];
	$pcre_abc = "/[^".$arStemInfo[$sLang]["pcre_abc"]."]+/".BX_UTF_PCRE_MODIFIER;

	//Delimiter of the words
	$tok = " ";

	if($bReturnPositions)
	{
		$sText = preg_replace("/[^".$arStemInfo[$sLang]["pcre_letters"].".!?]+/".BX_UTF_PCRE_MODIFIER, $tok, ToUpper($sText));
		$sText = preg_replace("/[!?]+/".BX_UTF_PCRE_MODIFIER, ".", $sText);
	}
	else
	{
		$sText = preg_replace("/[^".$arStemInfo[$sLang]["pcre_letters"]."]+/".BX_UTF_PCRE_MODIFIER, $tok, ToUpper($sText));
	}

	//Parse text
	$words = strtok($sText, $tok);
	$pos = 1;
	while($words !== false)
	{
		if($bReturnPositions)
			$words = explode(".", $words);
		else
			$words = array($words);

		foreach($words as $i => $word)
		{
			$word = substr($word, 0, 50);

			if($bReturnPositions)
			{
				if($i > 0)
					$pos += 5; //Sentence distance
				if(!strlen($word))
					continue;
			}

			//Try to stem starting with desired language
			//1 - stemming may return more than one word
			$stem = $stem_func($word, 1);
			$stop_lang = $sLang;

			//If word equals it's stemming
			//and has letters not from ABC
			if(
				!is_array($stem)
				&& $stem === $word
				&& preg_match($pcre_abc, $word)
			)
			{
				//Do the best to detect correct one
				$guess = stemming_detect($word, $arStemInfo, $sLang);
				if(strlen($guess[0]))
				{
					$stem = $guess[0];
					$stop_lang = $guess[1];
				}
			}

			if($bIgnoreStopWords)
			{
				if(is_array($stem))
				{
					foreach($stem as $st)
						$stems[$st] = isset($stems[$st])? $stems[$st] + $pos: $pos;
				}
				else
				{
					$stems[$stem] = isset($stems[$stem])? $stems[$stem] + $pos: $pos;
				}
			}
			else
			{
				$stop_func = $arStemInfo[$stop_lang]["stop"];
				if(is_array($stem))
				{
					foreach($stem as $st)
					{
						if(!isset($stop_cache[$st]))
							$stop_cache[$st] = $stop_func($st);

						if($stop_cache[$st])
							$stems[$st] = isset($stems[$st])? $stems[$st] + $pos: $pos;
					}
				}
				else
				{
					if(!isset($stop_cache[$stem]))
						$stop_cache[$stem] = $stop_func($stem);

					if($stop_cache[$stem])
						$stems[$stem] = isset($stems[$stem])? $stems[$stem] + $pos: $pos;
				}
			}

			if($bReturnPositions)
				$pos++;
		}
		//Next word
		$words = strtok($tok);
	}

	return $stems;
}

function stemming_detect($word, $arStemInfo, $skipLang)
{
	$stem = "";
	$lang = "";

	foreach($arStemInfo as $sGuessLang => $arInfo)
	{
		if($sGuessLang === $skipLang)
			continue;

		//Word has letters not from ABC, so skip to next language
		if(preg_match("/[^".$arInfo["pcre_abc"]."]+/".BX_UTF_PCRE_MODIFIER, $word))
			continue;

		$stem = $arInfo["stem"]($word);
		$lang = $sGuessLang;

		//It looks like stemming succseeded
		if($stem !== $word)
			break;

		//Check if stop function flag word as stop
		$stop_func = $arInfo["stop"];
		if(!$stop_func($stem))
			break;
	}

	//It' s the best we can do
	//return word and lang to use as stop
	return array($stem, $lang);
}

function stemming_upper_default($sText)
{
	return ToUpper($sText);
}

function stemming_default($sText)
{
	return $sText;
}
function stemming_stop_default($sWord)
{
	if(strlen($sWord) < 2)
		return false;
	else
		return true;
}
function stemming_letter_default()
{
	return "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789";
}
?>
