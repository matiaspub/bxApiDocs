<?
IncludeModuleLangFile(__FILE__);

function tags_prepare($sText, $site_id = false)
{
	static $arEvents = false;
	if($arEvents === false)
	{
		$arEvents = array();
		$rsEvents = GetModuleEvents("search", "OnSearchGetTag");
		while($arEvent = $rsEvents->Fetch())
			$arEvents[] = $arEvent;
	}
	$arResult = array();
	$arTags = explode(",", $sText);
	foreach($arTags as $tag)
	{
		$tag = trim($tag);
		if(strlen($tag))
		{
			foreach($arEvents as $arEvent)
				$tag = ExecuteModuleEventEx($arEvent, array($tag));

			if(strlen($tag))
				$arResult[$tag] = $tag;
		}
	}
	return $arResult;
}

function TagsShowScript()
{
	CJSCore::Init('search_tags');
}


/**
 * <p>Функция возвращает код html для ввода тегов с поддержкой автодополнения.</p>
 *
 *
 *
 *
 * @param string $sName  Имя элемента управления html.
 *
 *
 *
 * @param string $sValue = '' Начальное значение элемента управления. Необязательный
 * параметр. По умолчанию - пустая строка.
 *
 *
 *
 * @param array $arSites = array() Массив идентификаторов сайтов для которых будет строиться
 * облако тегов. Необязательный параметр. По умолчанию берется
 * текущий сайт.
 *
 *
 *
 * @param string $sHTML = '' Произвольный html код, который будет вставлен в элемент управления.
 * Необязательный параметр. По умолчанию - пустая строка.
 *
 *
 *
 * @param string $sId = '' Идентификатор элемента управления (id). Необязательный параметр.
 * По умолчанию идентификатор будет сгенерирован автоматически.
 *
 *
 *
 * @return string <p>Функция возвращает html код элемента управления.</p><a name="examples"></a>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * echo InputTags("TAGS", $arElement["TAGS"]);
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/functions/inputtags.php
 * @author Bitrix
 */
function InputTags($sName="", $sValue="", $arSites=array(), $sHTML="", $sId="")
{
	if(!$sId)
		$sId = GenerateUniqId($sName);
	TagsShowScript();
	$order = class_exists("cuseroptions")? CUserOptions::GetOption("search_tags", "order", "CNT"): "CNT";
	return '<input name="'.htmlspecialcharsbx($sName).'" id="'.htmlspecialcharsbx($sId).'" type="text" autocomplete="off" value="'.htmlspecialcharsex($sValue).'" onfocus="'.htmlspecialcharsbx('window.oObject[this.id] = new JsTc(this, '.CUtil::PhpToJSObject($arSites).');').'" '.$sHTML.'/><input type="checkbox" id="ck_'.$sId.'" name="ck_'.htmlspecialcharsbx($sName).'" '.($order=="NAME"? "checked": "").' title="'.GetMessage("SEARCH_TAGS_SORTING_TIP").'">';
}
?>