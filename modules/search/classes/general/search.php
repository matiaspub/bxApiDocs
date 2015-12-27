<?
IncludeModuleLangFile(__FILE__);

if(!defined("START_EXEC_TIME"))
	// define("START_EXEC_TIME", getmicrotime());


/**
 * Класс для индексирования сайта и осуществления поиска по индексу 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/index.php
 * @author Bitrix
 */
class CAllSearch extends CDBResult
{
	var $Query; //Query parset
	var $Statistic; //Search statistic
	var $strQueryText = false; //q
	var $strTagsText = false; //tags
	var $strSqlWhere = ""; //additional sql filter
	var $strTags = ""; //string of tags in double quotes separated by commas
	var $errorno = 0;
	var $error = false;
	var $arParams = array();
	var $url_add_params = array(); //additional url params (OnSearch event)
	var $tf_hwm = 0;
	var $tf_hwm_site_id = "";
	var $_opt_ERROR_ON_EMPTY_STEM = false;
	var $_opt_NO_WORD_LOGIC = false;
	var $bUseRatingSort = false;

	public function __construct($strQuery=false, $SITE_ID=false, $MODULE_ID=false, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $aSort=array(), $aParamsEx=array(), $bTagsCloud = false)
	{
		return $this->CSearch($strQuery, $SITE_ID, $MODULE_ID, $ITEM_ID, $PARAM1, $PARAM2, $aSort, $aParamsEx, $bTagsCloud);
	}

	public function CSearch($strQuery=false, $LID=false, $MODULE_ID=false, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $aSort=array(), $aParamsEx=array(), $bTagsCloud = false)
	{
		if($strQuery===false)
			return $this;

		$arParams["QUERY"] = $strQuery;
		$arParams["SITE_ID"] = $LID;
		$arParams["MODULE_ID"] = $MODULE_ID;
		$arParams["ITEM_ID"] = $ITEM_ID;
		$arParams["PARAM1"] = $PARAM1;
		$arParams["PARAM2"] = $PARAM2;

		$this->Search($arParams, $aSort, $aParamsEx, $bTagsCloud);
	}
	//combination ($MODULE_ID, $PARAM1, $PARAM2, $PARAM3) is used to narrow search
	//returns recordset with search results
	
	/**
	* <p>Этот метод возвращает записи индекса, которые удовлетворяют заданной строке запроса и указанным параметрам, а так же доступны на просмотр для текущего посетителя (в соответствии с его уровнем доступа). Метод динамичный.</p> <p>Перед выполнением поисковых запросов вызывается событие <a href="http://dev.1c-bitrix.ru/api_help/search/events/onsearch.php">OnSearch</a>.</p>
	*
	*
	* @param array $arParams  Массив, содержащий условия поиска в виде наборов "название
	* поля"=&gt;"значение". <br><br> Название поля может принимать значение:
	* <ul> <li> <b>QUERY</b> - Строка запроса. Обязательный элемент. Должна быть
	* сформирована в соответствии с правилами языка запросов.</li> <li>
	* <b>TAGS</b> - Список тегов. В результате поиска будут возвращены все
	* элементы имеющие данные теги.</li> <li> <b>SITE_ID</b> - Сайт, в информации
	* которого производится поиск. Не обязательный параметр, по
	* умолчанию равен текущему.</li> <li> <b>MODULE_ID</b> - Код модуля, данными
	* которого ограничивается поиск. Если этот параметр равен false, то
	* поиск производится по всем модулям. Не обязательный параметр, по
	* умолчанию равен false.</li> <li> <b>ITEM_ID</b> - Код проиндексированного
	* элемента. Используется для ограничения области поиска по коду
	* элемента. Если параметр имеет значение false, то ограничение не
	* производится. Не обязательный параметр, по умолчанию равен false.</li>
	* <li> <b>PARAM1</b> - Первый параметр элемента или массив первых
	* параметров. Используется для ограничения области поиска по
	* произвольному параметру. Если параметр имеет значение false, то
	* ограничение не производится. Не обязательный параметр, по
	* умолчанию равен false.</li> <li> <b>PARAM2</b> - Второй параметр элемента или
	* массив вторых параметров. Используется для ограничения области
	* поиска по произвольному параметру. Если параметр имеет значение
	* false, то ограничение не производится. Не обязательный параметр, по
	* умолчанию равен false.</li> <li> <b>MODULE_ID</b> - идентификатор модуля или
	* массив идентификаторов;</li> <li> <b>ITEM_ID</b> - идентификатор элемента
	* или массив;</li> <li> <b>URL</b> - маска адреса относительно корня сайта, по
	* которому доступен данный элемент или массив масок адресов;</li> <li>
	* <b>DATE_CHANGE</b> - время изменения элемента в формате сайта (включает
	* время);</li> <li> <b>CHECK_DATES</b> - если задан и равен Y, то найдены будут
	* только активные элементы;</li> </ul>
	*
	* @param array $aSort = array() Массив, содержащий признак сортировки в виде наборов "название
	* поля"=&gt;"направление". <br><br> Название поля может принимать
	* значение: <ul> <li> <b>ID</b> - идентификатор в поисковом индексе;</li> <li>
	* <b>MODULE_ID</b> - идентификатор модуля;</li> <li> <b>ITEM_ID</b> - идентификатор
	* элемента поискового индекса (например для форума это
	* идентификатор сообщения);</li> <li> <b>TITLE</b> - заголовок;</li> <li> <b>PARAM1</b> -
	* Первый параметр элемента или массив первых параметров.
	* Используется для ограничения области поиска по произвольному
	* параметру. Если параметр имеет значение false, то ограничение не
	* производится. Не обязательный параметр, по умолчанию равен false.</li>
	* <li> <b>PARAM2</b> - Второй параметр элемента или массив вторых
	* параметров. Используется для ограничения области поиска по
	* произвольному параметру. Если параметр имеет значение false, то
	* ограничение не производится. Не обязательный параметр, по
	* умолчанию равен false.</li> <li> <b>DATE_FROM</b> - дата начала активности
	* элемента;</li> <li> <b>DATE_TO</b> - дата окончания активности элемента;</li>
	* <li> <b>RANK</b> - вычисленное значение релевантности;</li> <li> <b>TITLE_RANK</b> -
	* количество вхождений подстрок запроса в заголовок. Позволяет
	* повысить значимость заголовка в результатах поиска;</li> <li>
	* <b>CUSTOM_RANK</b> - заданное значение релевантности;</li> <li> <b>DATE_CHANGE</b> -
	* время изменения элемента;</li> </ul> Направление сортировки может
	* принимать значение: <ul> <li> <b>ASC</b> - по возрастанию;</li> <li> <b>DESC</b> - по
	* убыванию.</li> </ul> Пример (именно это значение считается по
	* умолчанию): <pre class="syntax"><code>array("CUSTOM_RANK"=&gt;"DESC", "RANK"=&gt;"DESC",
	* "DATE_CHANGE"=&gt;"DESC")</code></pre> В случае когда параметр bTagsCloud равен true,
	* допустимыми полями являются: <ul> <li> <b>DATE_CHANGE</b> - время изменения
	* элемента;</li> <li> <b>NAME</b> - значение тег;</li> <li> <b>CNT</b> - частота тега;</li>
	* </ul>
	*
	* @param array $aParamsEx = array() Массив массивов, содержащий дополнительные условия поиска в виде
	* наборов "название поля"=&gt;"значение". Эти условия будут объединены
	* по условию ИЛИ. Смотри описание параметра arParams. <br><br> Так же может
	* содержать элемент: <ul> <li> <b>STEMMING</b> - Если равно true, то при поиске
	* будет учитываться морфология. Если false, то не будет. Если не
	* задано, то будет использоваться настройка модуля;</li> </ul> Пример
	* сложного условия: <pre class="syntax"> array(<br> array(<br> "MODULE_ID" =&gt; "forum",<br> "PARAM1"
	* =&gt; array(1, 2),<br> ),<br> array(<br> "MODULE_ID" =&gt; "iblock",<br> "PARAM2" =&gt; 3,<br> ),<br> )</pre> В
	* данном случае поиск будет осуществляться в форумах с
	* идентификаторами 1 и 2, а также в инфоблоке с идентификатором 3.
	*
	* @param bool $bTagsCloud = false Признак построения облака тегов. Если задан и равен true, то будет
	* построено облако тегов.
	*
	* @return void <p>Набор записей, каждая из которых представляет собой массив
	* следующей структуры:</p> <pre class="syntax">Array(<br> [ID] =&gt; Код индекса<br>
	* [DATE_CHANGE] =&gt; Дата изменения проиндексированного элемента<br> [MODULE_ID]
	* =&gt; Модуль, которому принадлежит проиндексированный элемент<br>
	* [ITEM_ID] =&gt; Код проиндексированного элемента<br> [LID] =&gt; Языковое
	* зеркало (язык) сайта<br> [URL] =&gt; Адрес относительно корня сайта, по
	* которому доступен данный элемент (с заменой специальных символов
	* &lt;, &gt;, " на &amp;lt;, &amp;gt;, &amp;quot;)<br> [TITLE] =&gt; Заголовок
	* проиндексированного элемента (с заменой спец. символов)<br> [BODY] =&gt;
	* Текст проиндексированного элемента (с заменой спец. символов)<br>
	* [SEARCHABLE_CONTENT] =&gt; Индекс элемента, по которому идет поиск (с заменой
	* спец. символов)<br> [PARAM1] =&gt; Первый параметр элемента<br> [PARAM2] =&gt;
	* Второй параметр элемента<br> [~URL] =&gt; Адрес относительно корня
	* сайта, по которому доступен данный элемент<br> [~TITLE] =&gt; Заголовок
	* проиндексированного элемента<br> [~BODY] =&gt; Текст
	* проиндексированного элемента<br> [~SEARCHABLE_CONTENT] =&gt; Индекс элемента,
	* по которому идет поиск<br> [TITLE_FORMATED] =&gt; Заголовок
	* проиндексированного элемента с выделением искомых слов<br>
	* [BODY_FORMATED] =&gt; Часть текста проиндексированного элемента с
	* выделением искомых слов<br> [URL_WO_PARAMS] =&gt; Адрес элемента без
	* параметров добавленных в обработчиках события OnSearch.<br>)<br></pre>
	* <p>Если задано построение облака тегов, то:</p> <pre class="syntax">Array(<br> [NAME]
	* =&gt; тег<br> [CNT] =&gt; частота<br> [DATE_CHANGE] =&gt; Максимальная дата изменения
	* элемента имеющего данный тег<br> [FULL_DATE_CHANGE] =&gt; Дата изменения
	* элемента включая время.<br>)<br></pre> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* Расширение фильтров
	* </htm
	* <li>MODULE_ID</li>
	*  
	*   <li>ITEM_ID</li>
	*  
	*   <li> PARAM1</li>
	*  
	*   <li> PARAM2</li>
	*  
	*   <li>SITE_ID</li>
	*  
	*   <li>DATE_CHANGE</li>
	*  
	*   <li>DATE_FROM</li>
	*  
	*   <li>DATE_TO</li>
	*  
	*   <li>SITE_URL</li>
	*   //Поиск только среди элементов инфоблоков за исключением разделов и инфоблоков с ID 3, 5 и 7 и сообщений форума не старше одних суток. <br> array( 
	*    "LOGIC" =&gt; "OR", 
	*    array( 
	*      "=MODULE_ID" =&gt; "iblock", 
	*      "!ITEM_ID" =&gt; "S%", 
	*      "!=PARAM2" =&gt; array(3,5,7), 
	*    ), 
	*    array( 
	*      "=MODULE_ID" =&gt; "forum", 
	*      "&gt;=DATE_CHANGE" =&gt; ConvertTimeStamp(time()-24*60*60, "FULL"), 
	*    ) 
	*  ) 
	* 
	* &lt;?<br>$q = "жареная рыба или селедка";<br>$module_id = "forum";<br>$obSearch = new CSearch;<br>$obSearch-&gt;Search(array(<br>	"QUERY" =&gt; $q,<br>	"SITE_ID" =&gt; LANG,<br>	"MODULE_ID" =&gt; $module_id,<br>));<br>if ($obSearch-&gt;errorno!=0):<br>	?&gt;<br>	&lt;font class="text"&gt;В поисковой фразе обнаружена ошибка:&lt;/font&gt;<br>	&lt;?echo ShowError($obSearch-&gt;error);?&gt;<br>	&lt;font class="text"&gt;Исправьте поисковую фразу и повторите поиск.&lt;/font&gt;<br>	&lt;?<br>else:<br>	while($arResult = $obSearch-&gt;GetNext())<br>	{?&gt;<br>		&lt;a href="&lt;?echo $arResult["URL"]?&gt;"&gt;&lt;?echo $arResult["TITLE_FORMATED"]?&gt;&lt;/a&gt;<br>		&lt;?echo $arResult["BODY_FORMATED"]?&gt;<br>	&lt;hr size="1" color="#DFDFDF"&gt;<br>	&lt;?}<br>endif;<br>?&gt;<br>
	* 
	* 
	* 
	*    $obSearch = new CSearch;
	*    $obSearch-&gt;Search(array(
	*       'QUERY' =&gt; $_REQUEST['q'],
	*       'SITE_ID' =&gt; LANG,
	*       'MODULE_ID' =&gt; 'iblock',
	*    ));
	*    $obSearch-&gt;NavStart();
	*    while ($arSearch = $obSearch-&gt;Fetch()) {
	*       echo($arSearch);
	*    }
	* Примечание: такой вызов NavStart() породит дополнительный PAGEN_. Если он мешает, то можно заменить NavStart на такую конструкцию:
	* 
	* 
	* $obSearch-&gt;Statistic = new CSearchStatistic($obSearch-&gt;strQueryText, $obSearch-&gt;strTagsText);
	* $obSearch-&gt;Statistic-&gt;PhraseStat($obSearch-&gt;NavRecordCount, $obSearch-&gt;NavPageNomer);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/search.php
	* @author Bitrix
	*/
	public function Search($arParams, $aSort=array(), $aParamsEx=array(), $bTagsCloud = false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(!is_array($arParams))
			$arParams = array("QUERY"=>$arParams);

		if(!is_set($arParams, "SITE_ID") && is_set($arParams, "LID"))
		{
			$arParams["SITE_ID"] = $arParams["LID"];
			unset($arParams["LID"]);
		}

		if(array_key_exists("TAGS", $arParams))
		{
			$this->strTagsText = $arParams["TAGS"];
			$arTags = explode(",", $arParams["TAGS"]);
			foreach($arTags as $i => $strTag)
			{
				$strTag = trim($strTag);
				if(strlen($strTag))
					$arTags[$i] = str_replace("\"", "\\\"", $strTag);
				else
					unset($arTags[$i]);
			}

			if(count($arTags))
				$arParams["TAGS"] = '"'.implode('","', $arTags).'"';
			else
				unset($arParams["TAGS"]);
		}

		$this->strQueryText = $strQuery = trim($arParams["QUERY"]);
		$this->strTags = $strTags  = $arParams["TAGS"];

		if((strlen($strQuery) <= 0) && (strlen($strTags) > 0))
		{
			$strQuery = $strTags;
			$bTagsSearch = true;
		}
		else
		{
			if(strlen($strTags))
				$strQuery .= " ".$strTags;
			$strQuery = preg_replace_callback("/&#(\\d+);/", array($this, "chr"), $strQuery);
			$bTagsSearch = false;
		}

		$result = CSearchFullText::GetInstance()->search($arParams, $aSort, $aParamsEx, $bTagsCloud);
		if (is_array($result))
		{
			$this->error = CSearchFullText::GetInstance()->getErrorText();
			$this->errorno = CSearchFullText::GetInstance()->getErrorNumber();
			$this->formatter = CSearchFullText::GetInstance()->getRowFormatter();
			if ($this->errorno > 0)
				return;
		}
		else
		{
			if(!array_key_exists("STEMMING", $aParamsEx))
				$aParamsEx["STEMMING"] = COption::GetOptionString("search", "use_stemming", "N")=="Y";

			$this->Query = new CSearchQuery("and", "yes", 0, $arParams["SITE_ID"]);
			if($this->_opt_NO_WORD_LOGIC)
				$this->Query->no_bool_lang = true;

			$query = $this->Query->GetQueryString((BX_SEARCH_VERSION > 1? "sct": "sc").".SEARCHABLE_CONTENT", $strQuery, $bTagsSearch, $aParamsEx["STEMMING"], $this->_opt_ERROR_ON_EMPTY_STEM);
			if(!$query || strlen(trim($query))<=0)
			{
				if($bTagsCloud)
				{
					$query = "1=1";
				}
				else
				{
					$this->error = $this->Query->error;
					$this->errorno = $this->Query->errorno;
					return;
				}
			}

			if(strlen($query)>2000)
			{
				$this->error = GetMessage("SEARCH_ERROR4");
				$this->errorno = 4;
				return;
			}
		}

		foreach(GetModuleEvents("search", "OnSearch", true) as $arEvent)
		{
			$r = "";
			if($bTagsSearch)
			{
				if(strlen($strTags))
					$r = ExecuteModuleEventEx($arEvent, array("tags:".$strTags));
			}
			else
			{
				$r = ExecuteModuleEventEx($arEvent, array($strQuery));
			}
			if($r <> "")
				$this->url_add_params[] = $r;
		}

		if (is_array($result))
		{
			$r = new CDBResult;
			$r->InitFromArray($result);
		}
		elseif(
			BX_SEARCH_VERSION > 1
			&& count($this->Query->m_stemmed_words_id)
			&& array_sum($this->Query->m_stemmed_words_id) === 0
		)
		{
			$r = new CDBResult;
			$r->InitFromArray(array());
		}
		else
		{
			$this->strSqlWhere = "";
			$bIncSites = false;

			$arSqlWhere = array();
			if(is_array($aParamsEx) && !empty($aParamsEx))
			{
				foreach($aParamsEx as $aParamEx)
				{
					$strSqlWhere = CSearch::__PrepareFilter($aParamEx, $bIncSites);
					if($strSqlWhere != "")
						$arSqlWhere[] = $strSqlWhere;
				}
			}
			if (!empty($arSqlWhere))
			{
				$arSqlWhere = array(
					"\n\t\t\t\t(".implode(")\n\t\t\t\t\tOR(",$arSqlWhere)."\n\t\t\t\t)",
				);
			}

			$strSqlWhere = CSearch::__PrepareFilter($arParams, $bIncSites);
			if($strSqlWhere != "")
				array_unshift($arSqlWhere, $strSqlWhere);

			$strSqlOrder = $this->__PrepareSort($aSort, "sc.", $bTagsCloud);

			if(!array_key_exists("USE_TF_FILTER", $aParamsEx))
				$aParamsEx["USE_TF_FILTER"] = COption::GetOptionString("search", "use_tf_cache") == "Y";

			$bStem = !$bTagsSearch && count($this->Query->m_stemmed_words)>0;
			//calculate freq of the word on the whole site_id
			if($bStem && count($this->Query->m_stemmed_words))
			{
				$arStat = $this->GetFreqStatistics($this->Query->m_lang, $this->Query->m_stemmed_words, $arParams["SITE_ID"]);
				$this->tf_hwm_site_id = (strlen($arParams["SITE_ID"]) > 0? $arParams["SITE_ID"]: "");

				//we'll make filter by it's contrast
				if(!$bTagsCloud && $aParamsEx["USE_TF_FILTER"])
				{
					$hwm = false;
					foreach($this->Query->m_stemmed_words as $i => $stem)
					{
						if(!array_key_exists($stem, $arStat))
						{
							$hwm = 0;
							break;
						}
						elseif($hwm === false)
						{
							$hwm = $arStat[$stem]["TF"];
						}
						elseif($hwm > $arStat[$stem]["TF"])
						{
							$hwm = $arStat[$stem]["TF"];
						}
					}

					if($hwm > 0)
					{
						$arSqlWhere[] = "st.TF >= ".number_format($hwm, 2, ".", "");
						$this->tf_hwm = $hwm;
					}
				}
			}

			if(!empty($arSqlWhere))
			{
				$this->strSqlWhere = "\n\t\t\t\tAND (\n\t\t\t\t\t(".implode(")\n\t\t\t\t\tAND(",$arSqlWhere).")\n\t\t\t\t)";
			}

			if($bTagsCloud)
				$strSql = $this->tagsMakeSQL($query, $this->strSqlWhere, $strSqlOrder, $bIncSites, $bStem, $aParamsEx["LIMIT"]);
			else
				$strSql = $this->MakeSQL($query, $this->strSqlWhere, $strSqlOrder, $bIncSites, $bStem);

			$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		parent::CDBResult($r);
	}

	public function SetOptions($arOptions)
	{
		if(array_key_exists("ERROR_ON_EMPTY_STEM", $arOptions))
			$this->_opt_ERROR_ON_EMPTY_STEM = $arOptions["ERROR_ON_EMPTY_STEM"] === true;

		if(array_key_exists("NO_WORD_LOGIC", $arOptions))
			$this->_opt_NO_WORD_LOGIC = $arOptions["NO_WORD_LOGIC"] === true;
	}

	public function GetFilterMD5()
	{
		$perm = CSearch::CheckPermissions("sc.ID");
		$sql = preg_replace("/(DATE_FROM|DATE_TO|DATE_CHANGE)(\\s+IS\\s+NOT\\s+NULL|\\s+IS\\s+NULL|\\s*[<>!=]+\\s*'.*?')/im", "", $this->strSqlWhere);
		return md5($perm.$sql.$this->strTags);
	}

	public static function chr($a)
	{
		return chr($a[1]);
	}

	public static function GetFreqStatistics($lang_id, $arStem, $site_id="")
	{
		$DB = CDatabase::GetModuleConnection('search');
		$sql_site_id  = $DB->ForSQL($site_id);
		$sql_lang_id  = $DB->ForSQL($lang_id);
		$sql_stem = array();
		foreach($arStem as $stem)
			$sql_stem[] = $DB->ForSQL($stem);

		$limit = COption::GetOptionInt("search", "max_result_size");
		if($limit < 1)
			$limit = 500;

		$arResult = array();
		foreach($arStem as $stem)
			$arResult[$stem] = array(
				"STEM" => false,
				"FREQ" => 0,
				"TF" => 0,
				"STEM_COUNT" => 0,
				"TF_SUM" => 0,
			);

		if(BX_SEARCH_VERSION > 1)
			$strSql = "
				SELECT s.ID, s.STEM, FREQ, TF
				FROM b_search_content_freq f
				inner join b_search_stem s on s.ID = f.STEM
				WHERE LANGUAGE_ID = '".$sql_lang_id."'
				AND s.STEM in ('".implode("','", $sql_stem)."')
				AND ".(strlen($site_id) > 0? "SITE_ID = '".$sql_site_id."'": "SITE_ID IS NULL")."
				ORDER BY STEM
			";
		else
			$strSql = "
				SELECT STEM ID,STEM, FREQ, TF
				FROM b_search_content_freq
				WHERE LANGUAGE_ID = '".$sql_lang_id."'
				AND STEM in ('".implode("','", $sql_stem)."')
				AND ".(strlen($site_id) > 0? "SITE_ID = '".$sql_site_id."'": "SITE_ID IS NULL")."
				ORDER BY STEM
			";

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			if(strlen($ar["TF"]) > 0)
				$arResult[$ar["STEM"]] = $ar;
		}

		$arMissed = array();
		foreach($arResult as $stem => $ar)
			if(!$ar["STEM"])
				$arMissed[] = $DB->ForSQL($stem);

		if(count($arMissed) > 0)
		{
			if(BX_SEARCH_VERSION > 1)
				$strSql = "
					SELECT s.ID, s.STEM, floor(st.TF/100) BUCKET, sum(st.TF/10000) TF_SUM, count(*) STEM_COUNT
					FROM
						b_search_content_stem st
						inner join b_search_stem s on s.ID = st.STEM
						".(strlen($site_id) > 0? "INNER JOIN b_search_content_site scsite ON scsite.SEARCH_CONTENT_ID = st.SEARCH_CONTENT_ID AND scsite.SITE_ID = '".$sql_site_id."'": "")."
					WHERE st.LANGUAGE_ID = '".$sql_lang_id."'
					AND s.STEM in ('".implode("','", $arMissed)."')
					GROUP BY s.ID, s.STEM, floor(st.TF/100)
					ORDER BY s.ID, s.STEM, floor(st.TF/100) DESC
				";
			else
				$strSql = "
					SELECT st.STEM ID, st.STEM, floor(st.TF*100) BUCKET, sum(st.TF) TF_SUM, count(*) STEM_COUNT
					FROM
						b_search_content_stem st
						".(strlen($site_id) > 0? "INNER JOIN b_search_content_site scsite ON scsite.SEARCH_CONTENT_ID = st.SEARCH_CONTENT_ID AND scsite.SITE_ID = '".$sql_site_id."'": "")."
					WHERE st.LANGUAGE_ID = '".$sql_lang_id."'
					AND st.STEM in ('".implode("','", $arMissed)."')
					GROUP BY st.STEM, floor(st.TF*100)
					ORDER BY st.STEM, floor(st.TF*100) DESC
				";


			$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $rs->Fetch())
			{
				$stem = $ar["STEM"];
				if($arResult[$stem]["STEM_COUNT"] < $limit)
					$arResult[$stem]["TF"] = $ar["BUCKET"]/100.0;
				$arResult[$stem]["STEM_COUNT"] += $ar["STEM_COUNT"];
				$arResult[$stem]["TF_SUM"] += $ar["TF_SUM"];
				$arResult[$stem]["DO_INSERT"] = true;
				$arResult[$stem]["ID"] = $ar["ID"];
			}
		}

		foreach($arResult as $stem => $ar)
		{
			if($ar["DO_INSERT"])
			{
				$FREQ = intval(defined("search_range_by_sum_tf")? $ar["TF_SUM"]: $ar["STEM_COUNT"]);
				$strSql = "
					UPDATE b_search_content_freq
					SET FREQ=".$FREQ.", TF=".number_format($ar["TF"], 2, ".", "")."
					WHERE LANGUAGE_ID='".$sql_lang_id."'
					AND ".(strlen($site_id) > 0? "SITE_ID = '".$sql_site_id."'": "SITE_ID IS NULL")."
					AND STEM='".$DB->ForSQL($ar["ID"])."'
				";
				$rsUpdate = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if($rsUpdate->AffectedRowsCount() <= 0)
				{
					$strSql = "
						INSERT INTO b_search_content_freq
						(STEM, LANGUAGE_ID, SITE_ID, FREQ, TF)
						VALUES
						('".$DB->ForSQL($ar["ID"])."', '".$sql_lang_id."', ".(strlen($site_id) > 0? "'".$sql_site_id."'": "NULL").", ".$FREQ.", ".number_format($ar["TF"], 2, ".", "").")
					";
					$rsInsert = $DB->Query($strSql, true);
				}
			}
		}

		return $arResult;
	}

	public function Repl($strCond, $strType, $strWh)
	{
		$l=strlen($strCond);

		if($this->Query->bStemming)
		{
			$arStemInfo = stemming_init($this->Query->m_lang);
			$pcreLettersClass = "[".$arStemInfo["pcre_letters"]."]";
			$strWhUpp = stemming_upper($strWh, $this->Query->m_lang);
		}
		else
		{
			$strWhUpp=ToUpper($strWh);
		}

		$strCondUpp=ToUpper($strCond);

		$pos = 0;
		do
		{
			$pos = strpos($strWhUpp, $strCondUpp, $pos);

			//Check if we are in the middle of the numeric entity
			while(
				$pos !== false &&
				preg_match("/^[0-9]+;/", substr($strWh, $pos)) &&
				preg_match("/^[0-9]+#&/", strrev(substr($strWh, 0, $pos+strlen($strCond))))
			)
			{
				$pos = strpos($strWhUpp, $strCondUpp, $pos+1);
			}

			if($pos === false) break;

			if($strType=="STEM")
			{
				$lw = strlen($strWhUpp);
				for ($s = $pos; $s >= 0; $s--)
				{
					if (!preg_match("/$pcreLettersClass/".BX_UTF_PCRE_MODIFIER, substr($strWhUpp, $s, 1)))
						break;
				}
				$s++;
				for ($e = $pos; $e < $lw; $e++)
				{
					if (!preg_match("/$pcreLettersClass/".BX_UTF_PCRE_MODIFIER, substr($strWhUpp, $e, 1)))
						break;
				}
				$e--;
				$a = stemming(substr($strWhUpp,$s,$e-$s+1), $this->Query->m_lang, true);
				foreach($a as $stem => $cnt)
				{
					if($stem == $strCondUpp)
					{
						$strWh = substr($strWh, 0, $pos)."%^%".substr($strWh, $pos, $e-$pos+1)."%/^%".substr($strWh,$e+1);
						$strWhUpp = substr($strWhUpp, 0, $pos)."%^%".str_repeat(" ", $e-$pos+1)."%/^%".substr($strWhUpp,$e+1);
						$pos += 7+$e-$pos+1;
					}
				}
			}
			else
			{
				$strWh = substr($strWh, 0, $pos)."%^%".substr($strWh, $pos, $l)."%/^%".substr($strWh,$pos+$l);
				$strWhUpp = substr($strWhUpp, 0, $pos)."%^%".str_repeat(" ", $l)."%/^%".substr($strWhUpp,$pos+$l);
				$pos += 7+$l;
			}
			$pos += 1;
		} while ($pos < strlen($strWhUpp));

		return $strWh;
	}

	public function PrepareSearchResult($str)
	{
		//$words - contains what we will highlight
		$words = array();
		foreach ($this->Query->m_words as $v)
		{
			$v = ToUpper($v);
			$words[$v] = "KAV";
			if(strpos($v, "\"")!==false)
				$words[str_replace("\"", "&QUOT;", $v)] = "KAV";
		}

		foreach ($this->Query->m_stemmed_words as $v)
			$words[ToUpper($v)]="STEM";

		//Prepare upper case version of the string
		if ($this->Query->bStemming)
		{
			//And add missing stemming words
			$arStemInfo = stemming_init($this->Query->m_lang);
			$a = stemming($this->Query->m_query, $this->Query->m_lang, true);
			foreach ($a as $stem => $cnt)
			{
				if (!preg_match("/cut[56]/i", $stem))
					$words[$stem] = "STEM";
			}
			$pcreLettersClass = "[".$arStemInfo["pcre_letters"]."]";
			$strUpp = stemming_upper($str, $this->Query->m_lang);
		}
		else
		{
			$strUpp = ToUpper($str);
			$pcreLettersClass = "";
		}

		$wordsCount = count($words);

		//We'll use regexp to find positions of the words in the text
		$pregMask = "";
		foreach ($words as $search => $type)
		{
			if ($type == "STEM")
				$pregMask = "(?<!".$pcreLettersClass.")".preg_quote($search, "/").$pcreLettersClass."*|".$pregMask;
			else
				$pregMask = $pregMask."|".preg_quote($search, "/");
		}
		$pregMask = trim($pregMask, "|");

		$arPos = array(); //This will contain positions of the first occurrence
		$arPosW = array(); //This is "running" words array
		$arPosP = array(); //and their positions
		$arPosLast = false; //Best found combination of the positions
		$matches = array();
		if (preg_match_all("/(".$pregMask.")/i".BX_UTF_PCRE_MODIFIER, $strUpp, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE))
		{
			foreach ($matches as $oneCase)
			{
				$search = null;
				if (isset($words[$oneCase[0][0]]))
				{
					$search = $oneCase[0][0];
				}
				else
				{
					$a = stemming($oneCase[0][0], $this->Query->m_lang, true);
					foreach ($a as $stem => $cnt)
					{
						if (isset($words[$stem]))
						{
							$search = $stem;
							break;
						}
					}
				}

				if (isset($search))
				{
					$p = $oneCase[0][1];
					if (!isset($arPos[$search]))
						$arPos[$search] = $p;
					//Add to the tail of the running window
					$arPosP[] = $p;
					$arPosW[] = $search;
					$cc = count($arPosW);
					if ($cc >= $wordsCount)
					{
						//This cuts the tail of the running window
						while ($cc > $wordsCount)
						{
							array_shift($arPosW);
							array_shift($arPosP);
							$cc--;
						}
						//Check if all the words present in the current window
						if (count(array_unique($arPosW)) == $wordsCount)
						{
							//And check if positions is the best
							if (
								!$arPosLast
								|| (
									(max($arPosP) - min($arPosP)) < (max($arPosLast) - min($arPosLast))
								))
								$arPosLast = $arPosP;
						}
					}
				}
			}
		}

		if ($arPosLast)
			$arPos = $arPosLast;

		//Nothing found just cut some text
		if (empty($arPos))
		{
			$str_len = strlen($str);
			$pos_end = 500;
			while (($pos_end < $str_len) && (strpos(" ,.\n\r", substr($str, $pos_end, 1)) === false))
				$pos_end++;
			return substr($str, 0, $pos_end).($pos_end < $str_len? "...": "");
		}

		sort($arPos);

		$str_len = CUtil::BinStrlen($str);
		$delta = 250/count($arPos);
		$arOtr = array();
		//Have to do it two times because Positions eat each other
		for ($i = 0; $i < 2; $i++)
		{
			$arOtr = array();
			$last_pos = -1;
			foreach ($arPos as $pos_mid)
			{
				//Find where sentence begins
				$pos_beg = $pos_mid - $delta;
				if($pos_beg <= 0)
					$pos_beg = 0;
				while(($pos_beg > 0) && (strpos(" ,.!?\n\r", CUtil::BinSubstr($str, $pos_beg, 1)) === false))
					$pos_beg--;

				//Find where sentence ends
				$pos_end = $pos_mid + $delta;
				if($pos_end > $str_len)
					$pos_end = $str_len;
				while(($pos_end < $str_len) && (strpos(" ,.!?\n\r", CUtil::BinSubstr($str, $pos_end, 1)) === false))
					$pos_end++;

				if($pos_beg <= $last_pos)
					$arOtr[count($arOtr)-1][1] = $pos_end;
				else
					$arOtr[] = array($pos_beg, $pos_end);

				$last_pos = $pos_end;
			}
			//Adjust length of the text
			$delta = 250/count($arOtr);
		}

		$str_result = "";
		foreach ($arOtr as $borders)
		{
			$str_result .= ($borders[0]<=0? "": " ...")
				.CUtil::BinSubstr($str, $borders[0], $borders[1] - $borders[0] + 1)
				.($borders[1] >= $str_len? "": "... ")
			;
		}

		foreach ($words as $search => $type)
			$str_result = $this->repl($search, $type, $str_result);

		$str_result = str_replace("%/^%", "</b>", str_replace("%^%","<b>", $str_result));

		return $str_result;
	}

	public function NavStart($nPageSize=0, $bShowAll=true, $iNumPage=false)
	{
		parent::NavStart($nPageSize, $bShowAll, $iNumPage);
		if(COption::GetOptionString("search", "stat_phrase") == "Y")
		{
			$this->Statistic = new CSearchStatistic($this->strQueryText, $this->strTagsText);
			$this->Statistic->PhraseStat($this->NavRecordCount, $this->NavPageNomer);
			if($this->Statistic->phrase_id)
				$this->url_add_params[] = "sphrase_id=".$this->Statistic->phrase_id;
		}
	}

	
	/**
	* <p>Возвращает следующий найденный элемент. Метод динамичный.</p> <p>Если поле результата URL начинается с символа "=", то вызываются обработчики события <a href="http://dev.1c-bitrix.ru/api_help/search/events/onsearchgeturl.php">OnSearchGetURL</a>.</p>
	*
	*
	* @return array <p>Смотри описание возвращаемого значения метода <a
	* href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/search.php">CSearch::Search</a>. Если
	* достигнут конец выборки, возвращается false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/fetch.php
	* @author Bitrix
	*/
	function Fetch()
	{
		static $arSite = array();

		$r = parent::Fetch();

		if ($r && $this->formatter)
		{
			$r = $this->formatter->format($r);
			if (!$r)
				return $this->Fetch();
		}

		if ($r)
		{
			$site_id = $r["SITE_ID"];
			if(!isset($arSite[$site_id]))
			{
				$b = "sort";
				$o = "asc";
				$rsSite = CSite::GetList($b, $o, array("ID"=>$site_id));
				$arSite[$site_id] = $rsSite->Fetch();
			}
			$r["DIR"] = $arSite[$site_id]["DIR"];
			$r["SERVER_NAME"] = $arSite[$site_id]["SERVER_NAME"];

			if(strlen($r["SITE_URL"])>0)
				$r["URL"] = $r["SITE_URL"];

			if(substr($r["URL"], 0, 1)=="=")
			{
				foreach (GetModuleEvents("search", "OnSearchGetURL", true) as $arEvent)
					$r["URL"] = ExecuteModuleEventEx($arEvent, array($r));
			}

			$r["URL"] = str_replace(
				array("#LANG#", "#SITE_DIR#", "#SERVER_NAME#"),
				array($r["DIR"], $r["DIR"], $r["SERVER_NAME"]),
				$r["URL"]
			);
			$r["URL"] = preg_replace("'(?<!:)/+'s", "/", $r["URL"]);
			$r["URL_WO_PARAMS"] = $r["URL"];

			$w = $this->Query->m_words;
			if(count($this->url_add_params))
			{
				$p1 = strpos($r["URL"], "?");
				if($p1 === false)
					$ch = "?";
				else
					$ch = "&";

				$p2 = strpos($r["URL"], "#", $p1);
				if($p2===false)
				{
					$r["URL"] = $r["URL"].$ch.implode("&", $this->url_add_params);
				}
				else
				{
					$r["URL"] = substr($r["URL"], 0, $p2).$ch.implode("&", $this->url_add_params).substr($r["URL"], $p2);
				}
			}

			if (!array_key_exists("TITLE_FORMATED", $r) && array_key_exists("TITLE", $r))
			{
				$r["TITLE_FORMATED"] = $this->PrepareSearchResult(htmlspecialcharsex($r["TITLE"]));
				$r["TITLE_FORMATED_TYPE"] = "html";
				$r["TAGS_FORMATED"] = tags_prepare($r["TAGS"], SITE_ID);
				$r["BODY_FORMATED"] = $this->PrepareSearchResult(htmlspecialcharsex($r["BODY"]));
				$r["BODY_FORMATED_TYPE"] = "html";
			}
		}

		return $r;
	}

	function CheckPath($path)
	{
		static $SEARCH_MASKS_CACHE = false;

		if(!is_array($SEARCH_MASKS_CACHE))
		{
			$arSearch = array("\\", ".",  "?", "*",   "'");
			$arReplace = array("/",  "\\.", ".", ".*?", "\\'");

			$arInc = array();
			$inc = str_replace(
				$arSearch,
				$arReplace,
				COption::GetOptionString("search", "include_mask")
			);
			$arIncTmp = explode(";", $inc);
			foreach($arIncTmp as $mask)
			{
				$mask = trim($mask);
				if(strlen($mask))
					$arInc[] = "'^".$mask."$'";
			}

			$arFullExc = array();
			$arExc = array();
			$exc = str_replace(
				$arSearch,
				$arReplace,
				COption::GetOptionString("search", "exclude_mask")
			);
			$arExcTmp = explode(";", $exc);
			foreach($arExcTmp as $mask)
			{
				$mask = trim($mask);
				if(strlen($mask))
				{
					if(preg_match("#^/[a-z0-9_.\\\\]+/#i", $mask))
						$arFullExc[] = "'^".$mask."$'".BX_UTF_PCRE_MODIFIER;
					else
						$arExc[] = "'^".$mask."$'".BX_UTF_PCRE_MODIFIER;
				}
			}

			$SEARCH_MASKS_CACHE = Array(
				"full_exc" => $arFullExc,
				"exc"=>$arExc,
				"inc"=>$arInc
			);
		}

		$file = end(explode('/', $path)); //basename
		if(strncmp($file, ".", 1)==0)
			return 0;

		foreach($SEARCH_MASKS_CACHE["full_exc"] as $mask)
			if(preg_match($mask, $path))
				return false;

		foreach($SEARCH_MASKS_CACHE["exc"] as $mask)
			if(preg_match($mask, $path))
				return 0;

		foreach($SEARCH_MASKS_CACHE["inc"] as $mask)
			if(preg_match($mask, $path))
				return true;

		return 0;
	}

	function GetGroupCached()
	{
		static $SEARCH_CACHED_GROUPS = false;

		if(!is_array($SEARCH_CACHED_GROUPS))
		{
			$SEARCH_CACHED_GROUPS = Array();
			$db_groups = CGroup::GetList($order="ID", $by="ASC");
			while($g = $db_groups->Fetch())
			{
				$group_id = intval($g["ID"]);
				if($group_id > 1)
					$SEARCH_CACHED_GROUPS[$group_id]=$group_id;
			}
		}

		return $SEARCH_CACHED_GROUPS;
	}

	public static function QueryMnogoSearch(&$xml)
	{
		$SITE = COption::GetOptionString("search", "mnogosearch_url", "www.mnogosearch.org");
		$PATH = COption::GetOptionString("search", "mnogosearch_path", "");
		$PORT = COption::GetOptionString("search", "mnogosearch_port", "80");

		$QUERY_STR = 'document='.urlencode($xml);

		$strRequest = "POST ".$PATH." HTTP/1.0\r\n";
		$strRequest.= "User-Agent: BitrixSM\r\n";
		$strRequest.= "Accept: */*\r\n";
		$strRequest.= "Host: $SITE\r\n";
		$strRequest.= "Accept-Language: en\r\n";
		$strRequest.= "Content-type: application/x-www-form-urlencoded\r\n";
		$strRequest.= "Content-length: ".strlen($QUERY_STR)."\r\n";
		$strRequest.= "\r\n";
		$strRequest.= $QUERY_STR;
		$strRequest.= "\r\n";

		$arAll = "";
		$errno = 0;
		$errstr = "";

		$FP = fsockopen($SITE, $PORT, $errno, $errstr, 120);
		if ($FP)
		{
			fputs($FP, $strRequest);

			while (($line = fgets($FP, 4096)) && $line!="\r\n");
			while ($line = fread($FP, 4096))
				$arAll .= $line;
			fclose($FP);
		}

		return $arAll;
	}

	//////////////////////////////////
	//reindex the whole server content
	//$bFull = true - no not check change_date. all index tables will be truncated
	//       = false - add new ones. update changed and delete deleted.
	
	/**
	* <p>Метод пошаговой переиндексации. Для того, чтобы модуль был переиндексирован, он должен предоставить соответствующий метод, который должен быть зарегистрирован в системе событий как обработчик события <a href="http://dev.1c-bitrix.ru/api_help/search/events/onreindex.php">OnReIndex</a> модуля "search". Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание:</b> метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</div>
	*
	*
	* @param bool $bFull = false Если равен true, то на первом шаге поисковый индекс будет полностью
	* очищен. По умолчанию равен false, что приводит к перестройке индекса
	* только тех элементов, содержимое которых изменилось.
	*
	* @param int $max_execution_time = 0 Если задан и больше нуля, то как только время выполнения метода
	* превысит значение этого параметра выполнение метода завершится.
	* Данные для начала следующего шага будут возвращены как результат
	* работы метода.
	*
	* @param array $NS = Array() Хранит состояние процесса индексации (прогресс) на начало шага.
	*
	* @param bool $clear_suggest = false Удаляет историю/статистику подсказок для строки поиска.
	* Необязательный параметр, по умолчанию принимает значение false.
	*
	* @return mixed <p>Метод возвращает массив, если требуется вызвать метод еще раз.
	* Или число проиндексированных элементов, если переиндексация
	* завершена.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>//Этот пример не является примером пошаговой индексации.<br>//Для этого надо вызывать метод ReIndexAll только один раз за запуск скрипта.<br>//А промежуточное состояние (NS) можно сохранять например в файле.<br><br>$NS = false;<br>$NS = CSearch::ReIndexAll(false, 60, $NS);<br>while(is_array($NS))<br>	$NS = CSearch::ReIndexAll(false, 60, $NS);<br>echo $NS;<br><br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/events/onreindex.php">OnReIndex</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexall.php
	* @author Bitrix
	*/
	public static function ReIndexAll($bFull = false, $max_execution_time = 0, $NS = Array(), $clear_suggest = false)
	{
		global $APPLICATION;
		$DB = CDatabase::GetModuleConnection('search');

		@set_time_limit(0);
		if(!is_array($NS))
			$NS = Array();
		if($max_execution_time<=0)
		{
			$NS_OLD=$NS;
			$NS=Array("CLEAR"=>"N", "MODULE"=>"", "ID"=>"", "SESS_ID"=>md5(uniqid("")));
			if($NS_OLD["SITE_ID"]!="") $NS["SITE_ID"]=$NS_OLD["SITE_ID"];
			if($NS_OLD["MODULE_ID"]!="") $NS["MODULE_ID"]=$NS_OLD["MODULE_ID"];
		}
		$NS["CNT"] = IntVal($NS["CNT"]);
		if(!$bFull && strlen($NS["SESS_ID"])!=32)
			$NS["SESS_ID"] = md5(uniqid(""));

		$p1 = getmicrotime();

		$DB->StartTransaction();
		CSearch::ReindexLock();

		if($NS["CLEAR"] != "Y")
		{
			if($bFull)
			{
				foreach(GetModuleEvents("search", "OnBeforeFullReindexClear", true) as $arEvent)
					ExecuteModuleEventEx($arEvent);

				CSearchTags::CleanCache();
				$DB->Query("TRUNCATE TABLE b_search_content_param", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_content_site", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_content_right", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_content_title", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_tags", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_content_freq", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_content", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_suggest", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_user_right", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				CSearchFullText::getInstance()->truncate();
				COption::SetOptionString("search", "full_reindex_required", "N");
			}
			elseif($clear_suggest)
			{
				$DB->Query("TRUNCATE TABLE b_search_suggest", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_user_right", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("TRUNCATE TABLE b_search_content_freq", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}


		$NS["CLEAR"] = "Y";

		clearstatcache();

		if(
			($NS["MODULE"]=="" || $NS["MODULE"]=="main") &&
			($NS["MODULE_ID"]=="" || $NS["MODULE_ID"]=="main")
		)
		{
			$arLangDirs = Array();
			$arFilter = Array("ACTIVE"=>"Y");
			if($NS["SITE_ID"]!="")
				$arFilter["ID"]=$NS["SITE_ID"];
			$r = CSite::GetList($by="sort", $order="asc", $arFilter);
			while($arR = $r->Fetch())
			{
				$path = rtrim($arR["DIR"], "/");
				$arLangDirs[$arR["ABS_DOC_ROOT"]."/".$path."/"] = $arR;
			}

			//get rid of duplicates
			$dub = Array();
			foreach($arLangDirs as $path=>$arR)
			{
				foreach($arLangDirs as $path2=>$arR2)
				{
					if($path==$path2) continue;
					if(substr($path, 0, strlen($path2)) == $path2)
						$dub[] = $path;
				}
			}

			foreach($dub as $p)
				unset($arLangDirs[$p]);

			foreach($arLangDirs as $arR)
			{
				$site = $arR["ID"];
				$path = rtrim($arR["DIR"], "/");
				$site_path = $site."|".$path."/";

				if(
					$max_execution_time > 0
					&& $NS["MODULE"] == "main"
					&& substr($NS["ID"]."/", 0, strlen($site_path)) != $site_path
				)
					continue;

				//for every folder
				CSearch::RecurseIndex(Array($site, $path), $max_execution_time, $NS);
				if(
					$max_execution_time > 0
					&& strlen($NS["MODULE"]) > 0
				)
				{
					$DB->Commit();
					return $NS;
				}
			}
		}

		$p1 = getmicrotime();

		//for every who wants to reindex
		$oCallBack = new CSearchCallback;
		$oCallBack->max_execution_time = $max_execution_time;
		foreach(GetModuleEvents("search", "OnReindex", true) as $arEvent)
		{
			if($NS["MODULE_ID"]!="" && $NS["MODULE_ID"]!=$arEvent["TO_MODULE_ID"]) continue;
			if($max_execution_time>0 && strlen($NS["MODULE"])>0 && $NS["MODULE"]!= "main" && $NS["MODULE"]!=$arEvent["TO_MODULE_ID"]) continue;
			//here we get recordset
			$oCallBack->MODULE = $arEvent["TO_MODULE_ID"];
			$oCallBack->CNT = &$NS["CNT"];
			$oCallBack->SESS_ID = $NS["SESS_ID"];
			$r = &$oCallBack;
			$arResult = ExecuteModuleEventEx($arEvent, array($NS, $r, "Index"));
			if(is_array($arResult)) //old way
			{
				foreach($arResult as $arFields)
				{
					$ID = $arFields["ID"];
					if(strlen($ID) > 0)
					{
						unset($arFields["ID"]);
						$NS["CNT"]++;
						CSearch::Index($arEvent["TO_MODULE_ID"], $ID, $arFields, false, $NS["SESS_ID"]);
					}
				}
			}
			else  //new method
			{
				if($max_execution_time>0 && $arResult!==false && strlen(".".$arResult)>1)
				{
					$DB->Commit();
					return Array(
						"MODULE"=>$arEvent["TO_MODULE_ID"],
						"CNT"=>$oCallBack->CNT,
						"ID"=>$arResult,
						"CLEAR"=>$NS["CLEAR"],
						"SESS_ID"=>$NS["SESS_ID"],
						"SITE_ID"=>$NS["SITE_ID"],
						"MODULE_ID"=>$NS["MODULE_ID"],
					);
				}
			}
			$NS["MODULE"] = "";
		}

		if(!$bFull)
		{
			CSearch::DeleteOld($NS["SESS_ID"], $NS["MODULE_ID"], $NS["SITE_ID"]);
		}

		$DB->Commit();

		return $NS["CNT"];
	}

	
	/**
	* <p>Метод инициирует переиндексацию указанного модуля. Для того, чтобы модуль был переиндексирован, он должен предоставить соответствующий метод, который должен быть зарегистрирован в системе событий как обработчик события <a href="http://dev.1c-bitrix.ru/api_help/search/events/onreindex.php">OnReIndex</a> модуля "search". Метод динамичный.</p> <p>С помощью этого метода невозможно переиндексировать данные модуля "main". Т.к. индексация файлов выполняется не через обработчики событий, а непосредственно модулем поиска. Но можно воспользоваться методом <a href="http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexall.php">CSearch::ReIndexAll</a>, передав на первом шаге $NS = array("MODULE_ID"=&gt;"main").</p> <p></p> <div class="note"> <b>Примечание:</b> метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</div>
	*
	*
	* @param string $MODULE_ID  Код модуля, переиндексация которого требуется.
	*
	* @param bool $bFull = false Производить ли полную переиндексацию с очисткой старых
	* поисковых индексов. Не обязательный параметр, по умолчанию равен
	* false.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* OnReindex.<br><br>// регистрируем обработчик события "OnReindex" модуля "search"<br>RegisterModuleDependences("search", "OnReindex", "my_module", "CMyModule", "OnReindex");<br><br>// создаем в модуле my_module в классе CMyModule метод OnReindex<br>function OnReindex()<br>{<br>	global $DB;<br>	$arResult = array();<br><br>	$strSql =<br>		"SELECT FT.ID, FT.TITLE, FT.MESSAGE, ".<br>		"  DATE_FORMAT(FT.POST_DATE, '%d.%m.%Y %H:%i:%s') as POST_DATE, FT.LID ".<br>		"FROM b_my_table FT ";<br><br>	$db_res = $DB-&gt;Query($strSql);<br>	while ($res = $db_res-&gt;Fetch())<br>	{<br>		$arResult[] = array(<br>			"ID" =&gt; $res["ID"],<br>			"LID" =&gt; $res["LID"],<br>			"DATE_CHANGE" =&gt; $res["POST_DATE"],<br>			"URL" =&gt; "/my_module/index.php?ID=".$res["ID"],<br>			"PERMISSIONS" =&gt; array(2),<br>			"TITLE" =&gt; $res["TITLE"],<br>			"BODY" =&gt; $res["POST_MESSAGE"]<br>		);<br>	}<br>	return $arResult;<br>}<br><br>// вызываем переиндексацию модуля<br>CSearch::ReIndexModule("my_module");<br><br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/events/onreindex.php">OnReIndex</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/reindexmodule.php
	* @author Bitrix
	*/
	public static function ReindexModule($MODULE_ID, $bFull=false)
	{
		global $APPLICATION;
		$DB = CDatabase::GetModuleConnection('search');

		if($bFull)
			CSearch::DeleteForReindex($MODULE_ID);

		$NS=Array("CLEAR"=>"N", "MODULE"=>"", "ID"=>"", "SESS_ID"=>md5(uniqid("")));
		//for every who wants to be reindexed
		foreach(GetModuleEvents("search", "OnReindex", true) as $arEvent)
		{
			if($arEvent["TO_MODULE_ID"]!=$MODULE_ID) continue;

			$oCallBack = new CSearchCallback;
			$oCallBack->MODULE = $arEvent["TO_MODULE_ID"];
			$oCallBack->CNT = &$NS["CNT"];
			$oCallBack->SESS_ID = $NS["SESS_ID"];
			$r = &$oCallBack;

			$arResult = ExecuteModuleEventEx($arEvent, array($NS, $r, "Index"));
			if(is_array($arResult)) //old way
			{
				foreach($arResult as $arFields)
				{
					$ID = $arFields["ID"];
					if(strlen($ID) > 0)
					{
						unset($arFields["ID"]);
						$NS["CNT"]++;
						CSearch::Index($arEvent["TO_MODULE_ID"], $ID, $arFields, false, $NS["SESS_ID"]);
					}
				}
			}
			else  //new way
			{
				return Array("MODULE"=>$arEvent["TO_MODULE_ID"], "CNT"=>$oCallBack->CNT, "ID"=>$arResult, "CLEAR"=>$NS["CLEAR"], "SESS_ID"=>$NS["SESS_ID"]);
			}
		}

		if(!$bFull)
			CSearch::DeleteOld($NS["SESS_ID"], $MODULE_ID, $NS["SITE_ID"]);
	}
	//index one item (forum message, news, etc.)
	//combination of ($MODULE_ID, $ITEM_ID) is used to determine the documents
	
	/**
	* <p>Метод переиндексирует какую-то одиночную позицию (сообщение на форуме, новость и т.п.), причем комбинация (MODULE_ID, ITEM_ID) используется для определения переиндексируемого документа. Метод динамичный.</p> <p>Вначале индексации вызывается событие <a href="http://dev.1c-bitrix.ru/api_help/search/events/beforeindex.php">BeforeIndex</a>. Затем вычисляется пользовательский вес позиции. И производится собственно переиндексация.</p>
	*
	*
	* @param string $MODULE_ID  Код модуля, которому принадлежит индексируемый элемент.
	*
	* @param string $ITEM_ID  Код индексируемого элемента.
	*
	* @param array $arFields  Массив свойств индексируемого элемента.
	*
	* @param bool $bOverWrite = false Перетирать индекс поиска элемента если элемент уже
	* проиндексирован. Не обязательный параметр, по умолчанию равен
	* false.
	*
	* @param string $SEARCH_SESS_ID = '' Служебный параметр, используется при пошаговой переиндексации.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>CSearch::Index(<br>	"iblock",<br>	$ID,<br>	Array(<br>		"DATE_CHANGE"=&gt;$arIBlockElement["DATE_CHANGE"],<br>		"TITLE"=&gt;$arIBlockElement["NAME"],<br>		"SITE_ID"=&gt;$arSites,<br>		"PARAM1"=&gt;$arIBlockElement["IBLOCK_TYPE_ID"],<br>		"PARAM2"=&gt;$IBLOCK_ID,<br>		"PERMISSIONS"=&gt;$arGroups,<br>		"URL"=&gt;str_replace(&amp;quot#ID#", $arIBlockElement["ID"], $DETAIL_PAGE_URL),<br>		"BODY"=&gt;$arIBlockElement["DETAIL_TEXT"],<br>		"TAGS"=&gt;$arIBlockElement["TAGS"]<br>	),<br>	$bOverWrite<br>);<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/indexs.php
	* @author Bitrix
	*/
	public static function Index($MODULE_ID, $ITEM_ID, $arFields, $bOverWrite=false, $SEARCH_SESS_ID="")
	{
		$DB = CDatabase::GetModuleConnection('search');

		$arFields["MODULE_ID"] = $MODULE_ID;
		$arFields["ITEM_ID"] = $ITEM_ID;
		foreach(GetModuleEvents("search", "BeforeIndex", true) as $arEvent)
		{
			$arEventResult = ExecuteModuleEventEx($arEvent, array($arFields));
			if(is_array($arEventResult))
				$arFields = $arEventResult;
		}
		unset($arFields["MODULE_ID"]);
		unset($arFields["ITEM_ID"]);

		$bTitle = array_key_exists("TITLE", $arFields);
		if($bTitle)
			$arFields["TITLE"] = trim($arFields["TITLE"]);
		$bBody = array_key_exists("BODY", $arFields);
		if($bBody)
			$arFields["BODY"] = trim($arFields["BODY"]);
		$bTags = array_key_exists("TAGS", $arFields);
		if($bTags)
			$arFields["TAGS"] = trim($arFields["TAGS"]);

		if(!array_key_exists("SITE_ID", $arFields) && array_key_exists("LID", $arFields))
			$arFields["SITE_ID"] = $arFields["LID"];

		if(array_key_exists("SITE_ID", $arFields))
		{
			if(!is_array($arFields["SITE_ID"]))
			{
				$arFields["SITE_ID"] = Array($arFields["SITE_ID"]=>"");
			}
			else
			{
				$bNotAssoc = true;
				$i = 0;
				foreach($arFields["SITE_ID"] as $k=>$val)
				{
					if("".$k!="".$i)
					{
						$bNotAssoc=false;
						break;
					}
					$i++;
				}
				if($bNotAssoc)
				{
					$x = $arFields["SITE_ID"];
					$arFields["SITE_ID"] = Array();
					foreach($x as $val)
						$arFields["SITE_ID"][$val] = "";
				}
			}

			if(count($arFields["SITE_ID"])<=0)
				return 0;

			reset($arFields["SITE_ID"]);
			list($arFields["LID"], $url) = each($arFields["SITE_ID"]);

			$arSites = array();
			foreach($arFields["SITE_ID"] as $site => $url)
			{
				$arSites[] = $DB->ForSQL($site, 2);
			}

			$strSql = "
				SELECT CR.RANK
				FROM b_search_custom_rank CR
				WHERE CR.SITE_ID in ('".implode("', '", $arSites)."')
				AND CR.MODULE_ID='".$DB->ForSQL($MODULE_ID)."'
				".(is_set($arFields, "PARAM1")?"AND (CR.PARAM1 IS NULL OR CR.PARAM1='' OR CR.PARAM1='".$DB->ForSQL($arFields["PARAM1"])."')":"")."
				".(is_set($arFields, "PARAM2")?"AND (CR.PARAM2 IS NULL OR CR.PARAM2='' OR CR.PARAM2='".$DB->ForSQL($arFields["PARAM2"])."')":"")."
				".($ITEM_ID<>""?"AND (CR.ITEM_ID IS NULL OR CR.ITEM_ID='' OR CR.ITEM_ID='".$DB->ForSQL($ITEM_ID)."')":"")."
				ORDER BY
					PARAM1 DESC, PARAM2 DESC, ITEM_ID DESC
			";
			$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arFields["CUSTOM_RANK_SQL"]=$strSql;
			if($arResult = $r->Fetch())
				$arFields["CUSTOM_RANK"]=$arResult["RANK"];
		}

		$arGroups = array();
		if(is_set($arFields, "PERMISSIONS"))
		{
			foreach($arFields["PERMISSIONS"] as $group_id)
			{
				if(is_numeric($group_id))
					$arGroups[$group_id] = "G".intval($group_id);
				else
					$arGroups[$group_id] = $group_id;
			}
		}

		$strSqlSelect = "";
		if($bBody) $strSqlSelect .= ",BODY";
		if($bTitle) $strSqlSelect .= ",TITLE";
		if($bTags) $strSqlSelect .= ",TAGS";

		$strSql =
			"SELECT ID, MODULE_ID, ITEM_ID, ".$DB->DateToCharFunction("DATE_CHANGE")." as DATE_CHANGE
			".$strSqlSelect."
			FROM b_search_content
			WHERE MODULE_ID = '".$DB->ForSQL($MODULE_ID)."'
				AND ITEM_ID = '".$DB->ForSQL($ITEM_ID)."' ";

		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($arResult = $r->Fetch())
		{
			$ID = $arResult["ID"];

			if($bTitle && $bBody && strlen($arFields["BODY"])<=0 && strlen($arFields["TITLE"])<=0)
			{
				foreach(GetModuleEvents("search", "OnBeforeIndexDelete", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ID));

				CSearchTags::CleanCache("", $ID);
				CSearch::CleanFreqCache($ID);
				$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				CSearchFullText::getInstance()->deleteById($ID);

				return 0;
			}

			if(is_set($arFields, "PARAMS"))
				CAllSearch::SetContentItemParams($ID, $arFields["PARAMS"]);

			if(count($arGroups) > 0)
				CAllSearch::SetContentItemGroups($ID, $arGroups);

			if(is_set($arFields, "SITE_ID"))
			{
				CSearch::UpdateSite($ID, $arFields["SITE_ID"]);
			}

			if(array_key_exists("LAST_MODIFIED", $arFields))
				$arFields["~DATE_CHANGE"] = $arFields["DATE_CHANGE"] = $DATE_CHANGE = $arFields["LAST_MODIFIED"];
			elseif(array_key_exists("DATE_CHANGE", $arFields))
				$arFields["~DATE_CHANGE"] = $arFields["DATE_CHANGE"] = $DATE_CHANGE = $DB->FormatDate($arFields["DATE_CHANGE"], "DD.MM.YYYY HH:MI:SS", CLang::GetDateFormat());
			else
				$DATE_CHANGE = '';

			if(!$bOverWrite && $DATE_CHANGE == $arResult["DATE_CHANGE"])
			{
				if(strlen($SEARCH_SESS_ID)>0)
					$DB->Query("UPDATE b_search_content SET UPD='".$DB->ForSql($SEARCH_SESS_ID)."' WHERE ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				//$DB->Commit();
				return $ID;
			}

			unset($arFields["MODULE_ID"]);
			unset($arFields["ITEM_ID"]);

			if($bBody || $bTitle || $bTags)
			{

				if(array_key_exists("INDEX_TITLE", $arFields) && $arFields["INDEX_TITLE"] === false)
				{
					$content = "";
				}
				else
				{
					if($bTitle)
						$content = $arFields["TITLE"]."\r\n";
					else
						$content = $arResult["TITLE"]."\r\n";
				}

				if($bBody)
					$content .= $arFields["BODY"]."\r\n";
				else
					$content .= $arResult["BODY"]."\r\n";

				if($bTags)
					$content .= $arFields["TAGS"];
				else
					$content .= $arResult["TAGS"];

				$content = preg_replace_callback("/&#(\\d+);/", array("CSearch", "chr"), $content);
				$arFields["SEARCHABLE_CONTENT"] = CSearch::KillEntities(ToUpper($content));
			}

			if(strlen($SEARCH_SESS_ID)>0)
				$arFields["UPD"] = $SEARCH_SESS_ID;

			if(array_key_exists("TITLE", $arFields))
			{
				$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if(
					!array_key_exists("INDEX_TITLE", $arFields)
					|| $arFields["INDEX_TITLE"] !== false
				)
					CSearch::IndexTitle($arFields["SITE_ID"], $ID, $arFields["TITLE"]);
			}

			if($bTags && ($arResult["TAGS"] != $arFields["TAGS"]))
			{
				CSearchTags::CleanCache("", $ID);
				$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				CSearch::TagsIndex($arFields["SITE_ID"], $ID, $arFields["TAGS"]);
			}

			foreach(GetModuleEvents("search", "OnBeforeIndexUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			CSearch::Update($ID, $arFields);
			$arFields["MODULE_ID"] = $arResult['MODULE_ID'];
			$arFields["ITEM_ID"] = $arResult['ITEM_ID'];
			CSearchFullText::getInstance()->replace($ID, $arFields);
		}
		else
		{
			if($bTitle && $bBody && strlen($arFields["BODY"])<=0 && strlen($arFields["TITLE"])<=0)
			{
				//$DB->Commit();
				return 0;
			}

			$arFields["MODULE_ID"] = $MODULE_ID;
			$arFields["ITEM_ID"] = $ITEM_ID;

			if(array_key_exists("INDEX_TITLE", $arFields) && $arFields["INDEX_TITLE"] === false)
				$content = $arFields["BODY"]."\r\n".$arFields["TAGS"];
			else
				$content = $arFields["TITLE"]."\r\n".$arFields["BODY"]."\r\n".$arFields["TAGS"];

			$content = preg_replace_callback("/&#(\\d+);/", array("CSearch", "chr"), $content);
			$arFields["SEARCHABLE_CONTENT"] = CSearch::KillEntities(ToUpper($content));

			if($SEARCH_SESS_ID!="")
				$arFields["UPD"] = $SEARCH_SESS_ID;

			$ID = CSearch::Add($arFields);
			//We failed to add this record to the search index
			if ($ID === false)
			{
				//Check if item was added
				$strSql = "SELECT ID FROM b_search_content WHERE MODULE_ID = '".$DB->ForSQL($MODULE_ID)."' AND ITEM_ID = '".$DB->ForSQL($ITEM_ID)."' ";
				$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$ar = $rs->Fetch();
				if($ar)
					return $ar["ID"];
				else
					return $ID;
			}
			CSearchFullText::getInstance()->replace($ID, $arFields);

			foreach(GetModuleEvents("search", "OnAfterIndexAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			if(is_set($arFields, "PARAMS"))
				CAllSearch::SetContentItemParams($ID, $arFields["PARAMS"]);

			CAllSearch::SetContentItemGroups($ID, $arGroups);

			CSearch::UpdateSite($ID, $arFields["SITE_ID"]);

			if(
				!array_key_exists("INDEX_TITLE", $arFields)
				|| $arFields["INDEX_TITLE"] !== false
			)
				CSearch::IndexTitle($arFields["SITE_ID"], $ID, $arFields["TITLE"]);

			CSearch::TagsIndex($arFields["SITE_ID"], $ID, $arFields["TAGS"]);
		}
		//$DB->Commit();

		return $ID;
	}

	function KillEntities($str)
	{
		static $arAllEntities = array(
			'UMLYA' => ARRAY(
				'&IQUEST;','&AGRAVE;','&AACUTE;','&ACIRC;','&ATILDE;',
				'&AUML;','&ARING;','&AELIG;','&CCEDIL;','&EGRAVE;',
				'&EACUTE;','&ECIRC;','&EUML;','&IGRAVE;','&IACUTE;',
				'&ICIRC;','&IUML;','&ETH;','&NTILDE;','&OGRAVE;',
				'&OACUTE;','&OCIRC;','&OTILDE;','&OUML;','&TIMES;',
				'&OSLASH;','&UGRAVE;','&UACUTE;','&UCIRC;','&UUML;',
				'&YACUTE;','&THORN;','&SZLIG;','&AGRAVE;','&AACUTE;',
				'&ACIRC;','&ATILDE;','&AUML;','&ARING;','&AELIG;',
				'&CCEDIL;','&EGRAVE;','&EACUTE;','&ECIRC;','&EUML;',
				'&IGRAVE;','&IACUTE;','&ICIRC;','&IUML;','&ETH;',
				'&NTILDE;','&OGRAVE;','&OACUTE;','&OCIRC;','&OTILDE;',
				'&OUML;','&DIVIDE;','&OSLASH;','&UGRAVE;','&UACUTE;',
				'&UCIRC;','&UUML;','&YACUTE;','&THORN;','&YUML;',
				'&OELIG;','&OELIG;','&SCARON;','&SCARON;','&YUML;',
			),
			'GREEK' => ARRAY(
				'&ALPHA;','&BETA;','&GAMMA;','&DELTA;','&EPSILON;',
				'&ZETA;','&ETA;','&THETA;','&IOTA;','&KAPPA;',
				'&LAMBDA;','&MU;','&NU;','&XI;','&OMICRON;',
				'&PI;','&RHO;','&SIGMA;','&TAU;','&UPSILON;',
				'&PHI;','&CHI;','&PSI;','&OMEGA;','&ALPHA;',
				'&BETA;','&GAMMA;','&DELTA;','&EPSILON;','&ZETA;',
				'&ETA;','&THETA;','&IOTA;','&KAPPA;','&LAMBDA;',
				'&MU;','&NU;','&XI;','&OMICRON;','&PI;',
				'&RHO;','&SIGMAF;','&SIGMA;','&TAU;','&UPSILON;',
				'&PHI;','&CHI;','&PSI;','&OMEGA;','&THETASYM;',
				'&UPSIH;','&PIV;',
			),
			'OTHER' => ARRAY(
				'&IEXCL;','&CENT;','&POUND;','&CURREN;','&YEN;',
				'&BRVBAR;','&SECT;','&UML;','&COPY;','&ORDF;',
				'&LAQUO;','&NOT;','&REG;','&MACR;','&DEG;',
				'&PLUSMN;','&SUP2;','&SUP3;','&ACUTE;','&MICRO;',
				'&PARA;','&MIDDOT;','&CEDIL;','&SUP1;','&ORDM;',
				'&RAQUO;','&FRAC14;','&FRAC12;','&FRAC34;','&CIRC;',
				'&TILDE;','&ENSP;','&EMSP;','&THINSP;','&ZWNJ;',
				'&ZWJ;','&LRM;','&RLM;','&NDASH;','&MDASH;',
				'&LSQUO;','&RSQUO;','&SBQUO;','&LDQUO;','&RDQUO;',
				'&BDQUO;','&DAGGER;','&DAGGER;','&PERMIL;','&LSAQUO;',
				'&RSAQUO;','&EURO;','&BULL;','&HELLIP;','&PRIME;',
				'&PRIME;','&OLINE;','&FRASL;','&WEIERP;','&IMAGE;',
				'&REAL;','&TRADE;','&ALEFSYM;','&LARR;','&UARR;',
				'&RARR;','&DARR;','&HARR;','&CRARR;','&LARR;',
				'&UARR;','&RARR;','&DARR;','&HARR;','&FORALL;',
				'&PART;','&EXIST;','&EMPTY;','&NABLA;','&ISIN;',
				'&NOTIN;','&NI;','&PROD;','&SUM;','&MINUS;',
				'&LOWAST;','&RADIC;','&PROP;','&INFIN;','&ANG;',
				'&AND;','&OR;','&CAP;','&CUP;','&INT;',
				'&THERE4;','&SIM;','&CONG;','&ASYMP;','&NE;',
				'&EQUIV;','&LE;','&GE;','&SUB;','&SUP;',
				'&NSUB;','&SUBE;','&SUPE;','&OPLUS;','&OTIMES;',
				'&PERP;','&SDOT;','&LCEIL;','&RCEIL;','&LFLOOR;',
				'&RFLOOR;','&LANG;','&RANG;','&LOZ;','&SPADES;',
				'&CLUBS;','&HEARTS;','&DIAMS;',
			),
		);
		foreach($arAllEntities as $key => $entities)
			$str = str_replace($entities, "", $str);
		return $str;
	}

	public static function ReindexFile($path, $SEARCH_SESS_ID="")
	{
		global $APPLICATION;
		$io = CBXVirtualIo::GetInstance();
		$DB = CDatabase::GetModuleConnection('search');

		if(!is_array($path))
			return 0;

		$file_doc_root = CSite::GetSiteDocRoot($path[0]);
		$file_rel_path = $path[1];
		$file_abs_path = preg_replace("#[\\\\\\/]+#", "/", $file_doc_root."/".$file_rel_path);
		$f = $io->GetFile($file_abs_path);

		if(!$f->IsExists() || !$f->IsReadable())
			return 0;

		if(!CSearch::CheckPath($file_rel_path))
			return 0;

		$max_file_size = COption::GetOptionInt("search", "max_file_size", 0);
		if(
			$max_file_size > 0
			&& $f->GetFileSize() > ($max_file_size*1024)
		)
			return 0;

		$file_site = "";
		$rsSites = CSite::GetList($by = "lendir", $order = "desc");
		while($arSite = $rsSites->Fetch())
		{
			$site_path = preg_replace("#[\\\\\\/]+#", "/", $arSite["ABS_DOC_ROOT"]."/".$arSite["DIR"]."/");
			if(strpos($file_abs_path, $site_path) === 0)
			{
				$file_site = $arSite["ID"];
				break;
			}
		}

		if($file_site == "")
			return 0;

		$item_id = $file_site."|".$file_rel_path;
		if (strlen($item_id) > 255)
			return 0;

		if(strlen($SEARCH_SESS_ID) > 0)
		{
			$DATE_CHANGE = $DB->CharToDateFunction(
				FormatDate(
					$DB->DateFormatToPHP(CLang::GetDateFormat("FULL")), $f->GetModificationTime() + CTimeZone::GetOffset()
				)
			);
			$strSql = "
				SELECT ID
				FROM b_search_content
				WHERE MODULE_ID = 'main'
					AND ITEM_ID = '".$DB->ForSQL($item_id)."'
					AND DATE_CHANGE = ".$DATE_CHANGE."
			";

			$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($arR = $r->Fetch())
			{
				$strSql = "UPDATE b_search_content SET UPD='".$DB->ForSQL($SEARCH_SESS_ID)."' WHERE ID = ".$arR["ID"];
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				return $arR["ID"];
			}
		}

		$arrFile = false;
		foreach(GetModuleEvents("search", "OnSearchGetFileContent", true) as $arEvent)
		{
			if($arrFile = ExecuteModuleEventEx($arEvent, array($file_abs_path, $SEARCH_SESS_ID)))
				break;
		}
		if(!is_array($arrFile))
		{
			$sFile = $APPLICATION->GetFileContent($file_abs_path);
			$sHeadEndPos = strpos($sFile, "</head>");
			if($sHeadEndPos===false)
				$sHeadEndPos = strpos($sFile, "</HEAD>");
			if($sHeadEndPos!==false)
			{
				//html header detected try to get document charset
				$arMetaMatch = array();
				if(preg_match("/<(meta)\\s+([^>]*)(content)\\s*=\\s*(['\"]).*?(charset)\\s*=\\s*(.*?)(\\4)/is", substr($sFile, 0, $sHeadEndPos), $arMetaMatch))
				{
					$doc_charset = $arMetaMatch[6];
					if(defined("BX_UTF"))
					{
						if(strtoupper($doc_charset) != "UTF-8")
							$sFile = $APPLICATION->ConvertCharset($sFile, $doc_charset, "UTF-8");
					}
				}
			}
			$arrFile = ParseFileContent($sFile);
		}

		$title = CSearch::KillTags(trim($arrFile["TITLE"]));

		if(strlen($title) <= 0)
			return 0;

		//strip out all the tags
		$filesrc = CSearch::KillTags($arrFile["CONTENT"]);

		$arGroups = CSearch::GetGroupCached();
		$arGPerm = Array();
		foreach($arGroups as $group_id)
		{
			$p = $APPLICATION->GetFileAccessPermission(Array($file_site, $file_rel_path), Array($group_id));
			if($p >= "R")
			{
				$arGPerm[] = $group_id;
				if($group_id==2) break;
			}
		}

		$tags = COption::GetOptionString("search", "page_tag_property");

		//save to database
		$ID = CSearch::Index("main", $item_id,
			Array(
				"SITE_ID" => $file_site,
				"DATE_CHANGE" => date("d.m.Y H:i:s", $f->GetModificationTime()+1),
				"PARAM1" => "",
				"PARAM2" => "",
				"URL" => $file_rel_path,
				"PERMISSIONS" => $arGPerm,
				"TITLE" => $title,
				"BODY" => $filesrc,
				"TAGS" => array_key_exists($tags, $arrFile["PROPERTIES"])? $arrFile["PROPERTIES"][$tags]: "",
			), false, $SEARCH_SESS_ID
		);

		return $ID;
	}

	public static function RecurseIndex($path=Array(), $max_execution_time = 0, &$NS)
	{
		global $APPLICATION;

		if(!is_array($path))
			return 0;

		$site = $path[0];
		$path = $path[1];

		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		$abs_path = $DOC_ROOT.$path;

		$io = CBXVirtualIo::GetInstance();

		if(!$io->DirectoryExists($abs_path))
			return 0;

		$f = $io->GetFile($abs_path);
		if(!$f->IsReadable())
			return 0;

		$d = $io->GetDirectory($abs_path);
		foreach($d->GetChildren() as $dir_entry)
		{
			$path_file = $path."/".$dir_entry->GetName();

			if($dir_entry->IsDirectory())
			{
				if($path_file == "/bitrix")
					continue;

				//this is not first step and we had stopped here, so go on to reindex
				if(
					$max_execution_time <= 0
					|| strlen($NS["MODULE"]) <= 0
					|| (
						$NS["MODULE"]=="main"
						&& substr($NS["ID"]."/", 0, strlen($site."|".$path_file."/")) == $site."|".$path_file."/"
					)
				)
				{
					if(CSearch::CheckPath($path_file."/") !== false)
					{
						if(CSearch::RecurseIndex(Array($site, $path_file), $max_execution_time, $NS)===false)
							return false;
					}
				}
				else //all done
				{
					continue;
				}
			}
			else
			{
				//not the first step and we found last file from previous one
				if(
					$max_execution_time > 0
					&& strlen($NS["MODULE"]) > 0
					&& $NS["MODULE"]=="main"
					&& $NS["ID"] == $site."|".$path_file
					)
				{
					$NS["MODULE"] = "";
				}
				elseif(strlen($NS["MODULE"]) <= 0)
				{
					$ID = CSearch::ReindexFile(Array($site, $path_file), $NS["SESS_ID"]);
					if(IntVal($ID)>0)
					{
						$NS["CNT"] = IntVal($NS["CNT"]) + 1;
					}

					if(
						$max_execution_time > 0
						&& (getmicrotime() - START_EXEC_TIME > $max_execution_time)
					)
					{
						$NS["MODULE"] = "main";
						$NS["ID"] = $site."|".$path_file;
						return false;
					}
				}
			}
		}

		return true;
	}

	public static function RemovePHP($str)
	{
		$res = "";
		$a = preg_split('/(<'.'\\?|\\?'.'>|\\/\\'.'*|\\'.'*'.'\\/|\\/\\/|\'|"|\\n)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
		$c = count($a);
		$i = 0;
		$bPHP = false;
		while($i < $c)
		{
			if($a[$i] == '\'' && $bPHP)
			{
				while((++$i) < $c)
				{
					if($a[$i] === '\'')
					{
						$m = array();
						if(preg_match('/(\\\\+)$/', $a[$i-1], $m))
						{
							if((strlen($m[1]) % 2) == 0) //non even slashes
								break;
						}
						else
						{
							break;
						}
					}
				}
			}
			elseif($a[$i] == '"' && $bPHP)
			{
				while((++$i) < $c)
				{
					if($a[$i] === '"')
					{
						if(preg_match('/(\\\\+)$/', $a[$i-1], $m))
						{
							if((strlen($m[1]) % 2) == 0) //non even slashes
								break;
						}
						else
							break;
					}
				}
			}
			elseif($a[$i] == '//' && $bPHP)
			{
				//single line comment
				while((++$i) < $c)
				{
					if($a[$i] === "\n" || $a[$i] === '?>')
						break;
				}
				continue;
			}
			elseif($a[$i] === '/*' && $bPHP)
			{
				while((++$i) < $c)
				{
					if($a[$i] === '*/')
						break;
				}
				continue;
			}
			elseif($a[$i] === '<?' && !$bPHP) //start of php
			{
				$bPHP = true;
				$i++;
				continue;
			}
			elseif($a[$i] === '?>' && $bPHP) //end of php
			{
				$bPHP = false;
				$i++;
				continue;
			}

			if(!$bPHP)
				$res .= $a[$i];

			$i++;
		}

		return $res;
	}

	public static function KillTags($str)
	{
		$str = CSearch::RemovePHP($str);

		static $search = array (
			"'<!--.*?-->'si",  // Strip out javascript
			"'<script[^>]*?>.*?</script>'si",  // Strip out javascript
			"'<style[^>]*?>.*?</style>'si",  // Strip out styles
			"'<select[^>]*?>.*?</select>'si",  // Strip out <select></select>
			"'<head[^>]*?>.*?</head>'si",  // Strip out <head></head>
			"'<tr[^>]*?>'",
			"'<[^>]*?>'",
			"'([\\r\\n])[\\s]+'",  // Strip out white space
			"'&(quot|#34);'i",  // Replace html entities
			"'&(amp|#38);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&(nbsp|#160);'i",
			"'[ ]+ '",
		);

		static $replace = array (
			"",
			"",
			"",
			"",
			"",
			"\r\n",
			"\r\n",
			"\\1",
			"\"",
			"&",
			"<",
			">",
			" ",
			" ",
		);

		$str = preg_replace ($search, $replace, $str);

		return $str;
	}

	public static function OnChangeFile($path, $site)
	{
		CSearch::ReindexFile(Array($site, $path));
	}

	public static function OnGroupDelete($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$DB->Query("
			DELETE FROM b_search_content_right
			WHERE GROUP_CODE = 'G".IntVal($ID)."'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function __PrepareFilter($arFilter, &$bIncSites, $strSearchContentAlias="sc.")
	{
		$DB = CDatabase::GetModuleConnection('search');
		$arSql = array();
		$arNewFilter = array();
		static $arFilterEvents = false;

		if(!is_array($arFilter))
			$arFilter = array();

		foreach($arFilter as $field=>$val)
		{
			$field = strtoupper($field);
			if(
				is_array($val)
				&& count($val) == 1
				&& $field !== "URL"
				&& $field !== "PARAMS"
			)
				$val = $val[0];
			switch($field)
			{
			case "=MODULE_ID":
				if($val !== false && $val !== "no")
					$arNewFilter[$field] = $val;
				break;
			case "MODULE_ID":
				if($val !== false && $val !== "no")
					$arNewFilter["=".$field] = $val;
				break;
			case "ITEM_ID":
			case "PARAM1":
			case "PARAM2":
				if($val !== false)
					$arNewFilter["=".$field] = $val;
				break;
			case "CHECK_DATES":
				if($val == "Y")
				{
					$time = ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL");
					$arNewFilter[] = array(
						"LOGIC" => "AND",
						array(
							"LOGIC" => "OR",
							"=DATE_FROM" => false,
							"<=DATE_FROM" => $time,
						),
						array(
							"LOGIC" => "OR",
							"=DATE_TO" => false,
							">=DATE_TO" => $time,
						),
					);
				}
				break;
			case "DATE_CHANGE":
				if(strlen($val) > 0)
					$arNewFilter[">=".$field] = $val;
				break;
			case "SITE_ID":
				if($val !== false)
					$arNewFilter["=".$field] = $val;
				break;
			default:
				if(!is_array($arFilterEvents))
				{
					$arFilterEvents = array();
					foreach(GetModuleEvents("search", "OnSearchPrepareFilter", true) as $arEvent)
						$arFilterEvents[] = $arEvent;
				}
				//Try to get someone to make the filter sql
				$sql = "";
				foreach($arFilterEvents as $arEvent)
				{
					$sql = ExecuteModuleEventEx($arEvent, array($strSearchContentAlias, $field, $val));
					if(strlen($sql))
					{
						$arSql[] = "(".$sql.")";
						break;
					}
				}

				if(!$sql)
					$arNewFilter[$field] = $val;
			}
		}

		$strSearchContentAlias = rtrim($strSearchContentAlias, ".");
		$obWhereHelp = new CSearchSQLHelper($strSearchContentAlias);
		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"MODULE_ID" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".MODULE_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ITEM_ID" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".ITEM_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"PARAM1" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".PARAM1",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"PARAM2" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".PARAM2",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"DATE_FROM" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".DATE_FROM",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "datetime",
				"JOIN" => false,
			),
			"DATE_TO" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".DATE_TO",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "datetime",
				"JOIN" => false,
			),
			"DATE_CHANGE" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".DATE_CHANGE",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "datetime",
				"JOIN" => false,
			),
			"SITE_ID" => array(
				"TABLE_ALIAS" => "scsite",
				"FIELD_NAME" => "scsite.SITE_ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "string",
				"JOIN" => true,
			),
			"SITE_URL" => array(
				"TABLE_ALIAS" => "scsite",
				"FIELD_NAME" => "scsite.URL",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "string",
				"JOIN" => true,
			),
			"URL" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".URL",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "callback",
				"CALLBACK" => array($obWhereHelp, "_CallbackURL"),
				"JOIN" => true,
			),
			"PARAMS" => array(
				"TABLE_ALIAS" => $strSearchContentAlias,
				"FIELD_NAME" => $strSearchContentAlias.".ID",
				"MULTIPLE" => "N",
				"FIELD_TYPE" => "callback",
				"CALLBACK" => array($obWhereHelp, "_CallbackPARAMS"),
				"JOIN" => false,
			),
		));

		$strWhere = $obQueryWhere->GetQuery($arNewFilter);

		if(count($arSql) > 0)
		{
			if($strWhere)
				$strWhere .= "\nAND (".implode(" AND ", $arSql).")";
			else
				$strWhere = implode("\nAND ", $arSql);
		}

		$bIncSites = $bIncSites || strlen($obQueryWhere->GetJoins()) > 0;
		return $strWhere;
	}

	public function __PrepareSort($aSort=array(), $strSearchContentAlias="sc.", $bTagsCloud = false)
	{
		$arOrder = array();
		if(!is_array($aSort))
			$aSort=array($aSort => "ASC");

		if($bTagsCloud)
		{
			foreach($aSort as $key => $ord)
			{
				$ord = strtoupper($ord) <> "ASC"? "DESC": "ASC";
				$key = strtoupper($key);
				switch($key)
				{
					case "DATE_CHANGE":
						$arOrder[] = "DC_TMP ".$ord;
						break;
					case "NAME":
					case "CNT":
						$arOrder[] = $key." ".$ord;
						break;
				}
			}
			if(count($arOrder) == 0)
			{
				$arOrder[]= "NAME ASC";
			}
		}
		else
		{
			$this->flagsUseRatingSort = 0;
			foreach($aSort as $key => $ord)
			{
				$ord = strtoupper($ord) <> "ASC"? "DESC": "ASC";
				$key = strtoupper($key);
				switch($key)
				{
					case "DATE_CHANGE":
						if(!($this->flagsUseRatingSort & 0x01))
							$this->flagsUseRatingSort = 0x02;
						$arOrder[]=$strSearchContentAlias.$key." ".$ord;
						break;
					case "RANK":
						if(!($this->flagsUseRatingSort & 0x02))
							$this->flagsUseRatingSort = 0x01;
						$arOrder[]=$key." ".$ord;
						break;
					case "TITLE_RANK":
					case "CUSTOM_RANK":
						$arOrder[]=$key." ".$ord;
						break;
					case "ID":
					case "MODULE_ID":
					case "ITEM_ID":
					case "TITLE":
					case "PARAM1":
					case "PARAM2":
					case "UPD":
					case "DATE_FROM":
					case "DATE_TO":
					case "URL":
						if(!($this->flagsUseRatingSort & 0x01))
							$this->flagsUseRatingSort = 0x02;
						$arOrder[]=$key." ".$ord;
						break;
				}
			}

			if(count($arOrder) == 0)
			{
				$arOrder[]= "CUSTOM_RANK DESC";
				$arOrder[]= "RANK DESC";
				$arOrder[]= $strSearchContentAlias."DATE_CHANGE DESC";
				$this->flagsUseRatingSort = 0x01;
			}
		}

		return " ORDER BY ".implode(", ",$arOrder);
	}

	public static function Add($arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(array_key_exists("~DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["~DATE_CHANGE"];
			unset($arFields["~DATE_CHANGE"]);
		}
		elseif(array_key_exists("LAST_MODIFIED", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["LAST_MODIFIED"];
			unset($arFields["LAST_MODIFIED"]);
		}
		elseif(array_key_exists("DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $DB->FormatDate($arFields["DATE_CHANGE"], "DD.MM.YYYY HH:MI:SS", CLang::GetDateFormat());
		}

		if(BX_SEARCH_VERSION > 1)
			return $DB->Add("b_search_content", $arFields, array("BODY", "TAGS"), true);
		else
			return $DB->Add("b_search_content", $arFields, array("BODY", "TAGS", "SEARCHABLE_CONTENT"), true);
	}

	public static function OnChangeFilePermissions($path, $permission = array(), $old_permission = array(), $arGroups = false)
	{

		global $APPLICATION;
		$DB = CDatabase::GetModuleConnection('search');

		$site = false;
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		$path = rtrim($path, "/");

		if(!is_array($arGroups))
		{
			$arGroups = CSearch::GetGroupCached();
			//Check if anonymous permission was changed
			if(!array_key_exists(2, $permission) && array_key_exists("*", $permission))
				$permission[2] = $permission["*"];
			if(!is_array($old_permission))
				$old_permission = array();
			if(!array_key_exists(2, $old_permission) && array_key_exists("*", $old_permission))
				$old_permission[2] = $old_permission["*"];
			//And if not when will do nothing
			if(
				(array_key_exists(2, $permission)
				&& $permission[2] >= "R")
				&& array_key_exists(2, $old_permission)
				&& $old_permission[2] >= "R"
			)
			{
				return;
			}
		}

		if(file_exists($DOC_ROOT.$path))
		{
			@set_time_limit(300);
			if(is_dir($DOC_ROOT.$path))
			{
				$handle = @opendir($DOC_ROOT.$path);
				while(false !== ($file = @readdir($handle)))
				{
					if($file == "." || $file == "..")
						continue;

					$full_file = $path."/".$file;
					if($full_file == "/bitrix")
						continue;

					if(is_dir($DOC_ROOT.$full_file) || CSearch::CheckPath($full_file))
						CSearch::OnChangeFilePermissions(array($site, $full_file), array(), array(), $arGroups);
				}
			}
			else//if(is_dir($DOC_ROOT.$path))
			{
				$rs = $DB->Query("
					SELECT SC.ID
					FROM b_search_content SC
					WHERE MODULE_ID='main'
					AND ITEM_ID='".$DB->ForSql($site."|".$path)."'
				", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if($ar = $rs->Fetch())
				{
					$arNewGroups = array();
					foreach($arGroups as $group_id)
					{
						$p = $APPLICATION->GetFileAccessPermission(array($site, $path), array($group_id));
						if($p >= "R")
						{
							$arNewGroups[$group_id] = 'G'.$group_id;
							if($group_id == 2)
								break;
						}
					}
					CAllSearch::SetContentItemGroups($ar["ID"], $arNewGroups);
				}
			} //if(is_dir($DOC_ROOT.$path))
		}//if(file_exists($DOC_ROOT.$path))
	}

	public static function SetContentItemGroups($index_id, $arGroups)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$index_id = intval($index_id);

		$arToInsert = array();
		foreach($arGroups as $group_code)
			if(strlen($group_code))
				$arToInsert[$group_code] = $group_code;

		//Read database
		$rs = $DB->Query("
			SELECT * FROM b_search_content_right
			WHERE SEARCH_CONTENT_ID = ".$index_id."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			$group_code = $ar["GROUP_CODE"];
			if(isset($arToInsert[$group_code]))
				unset($arToInsert[$group_code]); //This already in DB
			else
				$DB->Query("
					DELETE FROM b_search_content_right
					WHERE
					SEARCH_CONTENT_ID = ".$index_id."
					AND GROUP_CODE = '".$DB->ForSQL($group_code)."'
				", false, "File: ".__FILE__."<br>Line: ".__LINE__); //And this should be deleted
		}

		foreach($arToInsert as $group_code)
		{
			$DB->Query("
				INSERT INTO b_search_content_right
				(SEARCH_CONTENT_ID, GROUP_CODE)
				VALUES
				(".$index_id.", '".$DB->ForSQL($group_code, 100)."')
			", true, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function CheckPermissions($FIELD = "sc.ID")
	{
		global $USER;

		$arResult = array();

		if($USER->IsAdmin())
		{
			$arResult[] = "1=1";
		}
		else
		{
			if($USER->GetID() > 0)
			{
				CSearchUser::CheckCurrentUserGroups();
				$arResult[] = "
					EXISTS (
						SELECT 1
						FROM b_search_content_right scg
						WHERE ".$FIELD." = scg.SEARCH_CONTENT_ID
						AND scg.GROUP_CODE IN (
							SELECT GROUP_CODE FROM b_search_user_right
							WHERE USER_ID = ".$USER->GetID()."
						)
					)";
			}
			else
			{
				$arResult[] = "
					EXISTS (
						SELECT 1
						FROM b_search_content_right scg
						WHERE ".$FIELD." = scg.SEARCH_CONTENT_ID
						AND scg.GROUP_CODE = 'G2'
					)";
			}
		}
		return "((".implode(") OR (", $arResult)."))";
	}

	public static function SetContentItemParams($index_id, $arParams)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$index_id = intval($index_id);

		$arToInsert = array();

		if(is_array($arParams))
		{
			foreach($arParams as $k1 => $v1)
			{
				$name = trim($k1);
				if(strlen($name))
				{
					$sql_name = "'".$DB->ForSQL($name, 100)."'";

					if(!is_array($v1))
						$v1 = array($v1);

					foreach($v1 as $v2)
					{
						$value = trim($v2);
						if(strlen($value))
						{
							$sql_value = "'".$DB->ForSQL($value, 100)."'";
							$key = md5($sql_name).md5($sql_value);

							$arToInsert[$key] = "
								INSERT INTO b_search_content_param
								(SEARCH_CONTENT_ID, PARAM_NAME, PARAM_VALUE)
								VALUES
								(".$index_id.", ".$sql_name.", ".$sql_value.")
							";
						}
					}
				}
			}
		}

		if(empty($arToInsert))
		{
			$DB->Query("
				DELETE FROM b_search_content_param
				WHERE
				SEARCH_CONTENT_ID = ".$index_id."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$rs = $DB->Query("
				SELECT PARAM_NAME, PARAM_VALUE
				FROM b_search_content_param
				WHERE SEARCH_CONTENT_ID = ".$index_id."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $rs->Fetch())
			{
				$sql_name = "'".$DB->ForSQL($ar["PARAM_NAME"], 100)."'";
				$sql_value = "'".$DB->ForSQL($ar["PARAM_VALUE"], 100)."'";
				$key = md5($sql_name).md5($sql_value);

				if(array_key_exists($key, $arToInsert))
				{
					unset($arToInsert[$key]);
				}
				else
				{
					$DB->Query($s = "
						DELETE FROM b_search_content_param
						WHERE
						SEARCH_CONTENT_ID = ".$index_id."
						AND PARAM_NAME = ".$sql_name."
						AND PARAM_VALUE = ".$sql_value."
					", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}
		}

		foreach($arToInsert as $sql)
			$DB->Query($sql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetContentItemParams($index_id, $param_name = false)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$index_id = intval($index_id);

		if ($index_id <= 0)
		{
			return false;
		}

		$arResult = array();

		$rs = $DB->Query("
			SELECT PARAM_NAME, PARAM_VALUE
			FROM b_search_content_param
			WHERE SEARCH_CONTENT_ID = ".$index_id."
			".($param_name && strlen($param_name) > 0 ? " AND PARAM_NAME = '".$DB->ForSQL($param_name)."'" : "")."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			if (!isset($ar["PARAM_NAME"], $arResult))
			{
				$arResult[$ar["PARAM_NAME"]] = array();
			}
			$arResult[$ar["PARAM_NAME"]][] = $ar["PARAM_VALUE"];
		}

		return $arResult;
	}

	public static function stddev($arValues)
	{
		$mean = array_sum($arValues)/count($arValues);
		$variance = 0.0;
		foreach($arValues as $v)
			$variance += pow($v - $mean, 2);
		return sqrt($variance / count($arValues));
	}

	public function normdev($words_count)
	{
		$a = array();
		while($words_count > 0)
			$a[] = $words_count--;
		return $this->stddev($a);
	}

	public static function DeleteOld($SESS_ID, $MODULE_ID="", $SITE_ID="")
	{
		$DB = CDatabase::GetModuleConnection('search');

		$strFilter = "";
		if($MODULE_ID!="")
			$strFilter.=" AND MODULE_ID = '".$DB->ForSql($MODULE_ID)."' ";

		$strJoin = "";
		if($SITE_ID!="")
		{
			$strFilter.=" AND scsite.SITE_ID = '".$DB->ForSql($SITE_ID)."' ";
			$strJoin.=" INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID ";
		}

		if(!is_array($SESS_ID))
			$SESS_ID = array($SESS_ID);

		foreach ($SESS_ID as $key => $value)
			$SESS_ID[$key] = $DB->ForSql($value);

		$strSql = "
			SELECT ID
			FROM b_search_content sc
			".$strJoin."
			WHERE (UPD not in ('".implode("', '", $SESS_ID)."') OR UPD IS NULL)
			".$strFilter."
		";

		$arEvents = GetModuleEvents("search", "OnBeforeIndexDelete", true);

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			foreach($arEvents as $arEvent)
				ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ar["ID"]));

			$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			CSearchFullText::getInstance()->deleteById($ar["ID"]);
		}

		CSearchTags::CleanCache();
	}

	public static function DeleteForReindex($MODULE_ID)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$MODULE_ID = $DB->ForSql($MODULE_ID);
		$strSql = "SELECT ID FROM b_search_content WHERE MODULE_ID = '".$MODULE_ID."'";

		$arEvents = GetModuleEvents("search", "OnBeforeIndexDelete", true);

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			foreach($arEvents as $arEvent)
				ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ar["ID"]));

			$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			CSearchFullText::getInstance()->deleteById($ar["ID"]);
		}

		CSearchTags::CleanCache();
	}

	
	/**
	* <p>Метод удаляет из индекса поиска указанную информацию. Метод динамичный.</p>
	*
	*
	* @param string $MODULE_ID  Код модуля, индексацию информации которого необходимо удалить .
	*
	* @param string $ITEM_ID = false Код элемента, индексацию информации которого необходимо удалить.
	* Если этот параметр равен false, то ограничение по коду элемента не
	* устанавливается (удаляется индексация информации с любыми
	* кодами). Не обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	* проиндексированных элементов, индексацию информации которых
	* необходимо удалить. Если этот параметр равен false, то ограничение
	* по первому параметру элемента не устанавливается. Не
	* обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	* проиндексированных элементов, индексацию информации которых
	* необходимо удалить. Если этот параметр равен false, то ограничение
	* по второму параметру элемента не устанавливается. Не
	* обязательный параметр, по умолчанию равен false.
	*
	* @param string $SITE_ID = false Сайт проиндексированого элемента, ограничивающий набор
	* элементов, индексацию информации которых необходимо удалить.
	* Если этот параметр равен false, то ограничение по сайту элемента не
	* устанавливается. Необязательный параметр, по умолчанию равен
	* false.<br><br> До версии 4.0.6 параметр назывался LID.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $path = "/ru/my_files/file.php";
	* if (unlink($_SERVER["DOCUMENT_ROOT"].$path))
	* {
	* 	if (CModule::IncludeModule("search"))
	* 		CSearch::DeleteIndex("main", $path);
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/deleteindex.php
	* @author Bitrix
	*/
	public static function DeleteIndex($MODULE_ID, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$bIncSites = false;

		if($PARAM1 !== false && $PARAM2 !== false)
		{
			$strSqlWhere = CSearch::__PrepareFilter(array(
				"MODULE_ID" => $MODULE_ID,
				"ITEM_ID" => $ITEM_ID,
				array(
					"=PARAM1" => $PARAM1,
					"PARAM2" => $PARAM2,
				),
				"SITE_ID" => $SITE_ID,
			), $bIncSites);
		}
		else
		{
			$strSqlWhere = CSearch::__PrepareFilter(array(
				"MODULE_ID" => $MODULE_ID,
				"ITEM_ID" => $ITEM_ID,
				"PARAM1" => $PARAM1,
				"PARAM2" => $PARAM2,
				"SITE_ID" => $SITE_ID,
			), $bIncSites);
		}

		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
				".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID" :"")."
			WHERE
			".$strSqlWhere."
		";

		$arEvents = GetModuleEvents("search", "OnBeforeIndexDelete", true);

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			foreach($arEvents as $arEvent)
				ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ar["ID"]));

			$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			CSearchFullText::getInstance()->deleteById($ar["ID"]);
		}

		CSearchTags::CleanCache();
	}

	public static function Update($ID, $arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$bUpdate = false;

		if(array_key_exists("~DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["~DATE_CHANGE"];
			unset($arFields["~DATE_CHANGE"]);
		}
		elseif(array_key_exists("LAST_MODIFIED", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["LAST_MODIFIED"];
			unset($arFields["LAST_MODIFIED"]);
		}
		elseif(array_key_exists("DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $DB->FormatDate($arFields["DATE_CHANGE"], "DD.MM.YYYY HH:MI:SS", CLang::GetDateFormat());
		}

		if(BX_SEARCH_VERSION > 1)
			unset($arFields["SEARCHABLE_CONTENT"]);

		if (array_key_exists("SITE_ID", $arFields))
		{
			CSearch::UpdateSite($ID, $arFields["SITE_ID"]);
			$bUpdate = true;
		}

		if (array_key_exists("PERMISSIONS", $arFields))
		{
			$arNewGroups = array();
			foreach($arFields["PERMISSIONS"] as $group_id)
			{
				if(is_numeric($group_id))
					$arNewGroups[$group_id] = "G".intval($group_id);
				else
					$arNewGroups[$group_id] = $group_id;
			}
			CSearch::SetContentItemGroups($ID, $arNewGroups);
			$bUpdate = true;
		}

		if(array_key_exists("PARAMS", $arFields))
		{
			CSearch::SetContentItemParams($ID, $arFields["PARAMS"]);
			$bUpdate = true;
		}

		$strUpdate = $DB->PrepareUpdate("b_search_content", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$arBinds=Array();
			if(is_set($arFields, "BODY"))
				$arBinds["BODY"] = $arFields["BODY"];
			if(is_set($arFields, "SEARCHABLE_CONTENT"))
				$arBinds["SEARCHABLE_CONTENT"] = $arFields["SEARCHABLE_CONTENT"];
			if(is_set($arFields, "TAGS"))
				$arBinds["TAGS"] = $arFields["TAGS"];
			$DB->QueryBind("UPDATE b_search_content SET ".$strUpdate." WHERE ID=".intval($ID), $arBinds);
			$bUpdate = true;
		}

		if ($bUpdate)
			CSearchFullText::getInstance()->update($ID, $arFields);
	}

	public static function UpdateSite($ID, $arSITE_ID)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);
		if (!is_array($arSITE_ID))
		{
			$DB->Query("
				DELETE FROM b_search_content_site
				WHERE SEARCH_CONTENT_ID = ".$ID."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$rsSite = $DB->Query("
				SELECT SITE_ID, URL
				FROM b_search_content_site
				WHERE SEARCH_CONTENT_ID = ".$ID."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($arSite = $rsSite->Fetch())
			{
				if(!array_key_exists($arSite["SITE_ID"], $arSITE_ID))
				{
					$DB->Query("
						DELETE FROM b_search_content_site
						WHERE SEARCH_CONTENT_ID = ".$ID."
						AND SITE_ID = '".$DB->ForSql($arSite["SITE_ID"])."'
					", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
				else
				{
					if($arSite["URL"] !== $arSITE_ID[$arSite["SITE_ID"]])
					{
						$DB->Query("
							UPDATE b_search_content_site
							SET URL = '".$DB->ForSql($arSITE_ID[$arSite["SITE_ID"]], 2000)."'
							WHERE SEARCH_CONTENT_ID = ".$ID."
							AND SITE_ID = '".$DB->ForSql($arSite["SITE_ID"])."'
						", false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
					unset($arSITE_ID[$arSite["SITE_ID"]]);
				}
			}

			foreach($arSITE_ID as $site => $url)
			{
				$DB->Query("
					INSERT INTO b_search_content_site(SEARCH_CONTENT_ID, SITE_ID, URL)
					VALUES(".$ID.", '".$DB->ForSql($site, 2)."', '".$DB->ForSql($url, 2000)."')
				", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
	}

	
	/**
	* <p>Метод изменяет проиндексированную информацию данного модуля. Метод динамичный.</p>
	*
	*
	* @param string $MODULE_ID  Код модуля, проиндексированную информацию которого необходимо
	* поменять.
	*
	* @param array $arFields  Массив новых значений для поискового индекса. Массив должен
	* иметь следующую структуру: <ul> <li> <b>"DATE_CHANGE"</b> =&gt; дата изменения
	* индексируемого элемента,</li> <li> <b>"URL"</b> =&gt; адрес индексируемого
	* элемента,</li> <li> <b>"TITLE"</b> =&gt; заголовок индексируемого элемента,</li>
	* <li> <b>"BODY"</b> =&gt; индексируемый текст,</li> <li> <b>"SEARCHABLE_CONTENT"</b> =&gt;
	* индекс,</li> <li> <b>"PARAM1"</b> =&gt; первый параметр,</li> <li> <b>"PARAM2"</b> =&gt;
	* второй параметр,</li> <li> <b>"PERMISSIONS"</b> =&gt; массив кодов групп, члены
	* которых имеют право на чтение индексируемого элемента,</li> <li>
	* <b>"SITE_ID"</b> =&gt; ассоциативный массив привязки к сайтам. Ключи -
	* идентификаторы сайтов, а их значения пути к элементу. <br> </li> </ul>
	* Атрибуты, значения которых не изменились, можно опустить.
	*
	* @param string $ITEM_ID = false Код элемента, индекс которого необходимо изменить. Если этот
	* параметр равен false, то ограничение по коду элемента не
	* устанавливается (изменяются индексы элементов с любыми кодами).
	* Не обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	* проиндексированных элементов, индексы которых необходимо
	* поменять. Если этот параметр равен false, то ограничение по первому
	* параметру элемента не устанавливается. Не обязательный параметр,
	* по умолчанию равен false.
	*
	* @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	* проиндексированных элементов, индексы которых необходимо
	* поменять. Если этот параметр равен false, то ограничение по второму
	* параметру элемента не устанавливается. Не обязательный параметр,
	* по умолчанию равен false.
	*
	* @param string $SITE_ID = false Сайт проиндексированого элемента, ограничивающий набор
	* элементов, индекс которых необходимо поменять. Если этот
	* параметр равен false, то ограничение по сайту элемента не
	* устанавливается. Необязательный параметр, по умолчанию равен false.
	* <br><br> До версии 4.0.6 параметр назывался LID.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$new_title = "Новый заголовок темы";<br>$fid = 5;    // Код форума<br>$tid = 128;    // Код темы<br>CSearch::ChangeIndex("forum",<br>	array(<br>		"TITLE" =&gt; $new_title<br>	),<br>	false, $fid, $tid);<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/changeindex.php
	* @author Bitrix
	*/
	public static function ChangeIndex($MODULE_ID, $arFields, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$bIncSites = false;

		$strSqlWhere = CSearch::__PrepareFilter(array(
			"MODULE_ID" => $MODULE_ID,
			"ITEM_ID" => $ITEM_ID,
			"PARAM1" => $PARAM1,
			"PARAM2" => $PARAM2,
			"SITE_ID" => $SITE_ID,
		), $bIncSites);
		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
			".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID": "")."
			".(strlen($strSqlWhere)>0? "WHERE ".$strSqlWhere: "")."
		";
		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			CSearch::Update($ar["ID"], $arFields);
		}
	}

	
	/**
	* <p>Метод изменяет привязку проиндексированной информации к сайтам. Метод динамичный.</p>
	*
	*
	* @param string $MODULE_ID  Код модуля, права на доступ к информации которого необходимо
	* поменять.
	*
	* @param array $arSite  Ассоциативный массив привязки к сайтам.Ключи - идентификаторы
	* сайтов, а их значения пути к проиндексированной информации. <br>
	*
	* @param string $ITEM_ID = false Код элемента, права на доступ к которому необходимо поменять.
	* Если этот параметр равен false, то ограничение по коду элемента не
	* устанавливается (изменяются права на доступ к элементу с любыми
	* кодами). Не обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	* проиндексированных элементов, права на доступ к которым
	* необходимо поменять. Если этот параметр равен false, то ограничение
	* по первому параметру элемента не устанавливается. Не
	* обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	* проиндексированных элементов, права на доступ к которым
	* необходимо поменять. Если этот параметр равен false, то ограничение
	* по второму параметру элемента не устанавливается. Не
	* обязательный параметр, по умолчанию равен false.
	*
	* @param string $SITE_ID = false Сайт проиндексированного элемента, ограничивающий набор
	* элементов, права на доступ к которым необходимо поменять. Если
	* этот параметр равен false, то ограничение по сайту элемента не
	* устанавливается. Необязательный параметр, по умолчанию равен false.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$IBLOCK_ID = 5;<br>CSearch::ChangeSite("iblock", array("s1" =&gt; "=/new/localation/#ID#/"), false, $IBLOCK_ID);<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/changesite.php
	* @author Bitrix
	*/
	public static function ChangeSite($MODULE_ID, $arSite, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$bIncSites = false;

		$strSqlWhere = CSearch::__PrepareFilter(array(
			"MODULE_ID" => $MODULE_ID,
			"ITEM_ID" => $ITEM_ID,
			"PARAM1" => $PARAM1,
			"PARAM2" => $PARAM2,
			"SITE_ID" => $SITE_ID,
		), $bIncSites);

		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
			".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID": "")."
			WHERE
			".$strSqlWhere."
		";

		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arR = $r->Fetch())
		{
			CSearch::Update($arR["ID"], array("SITE_ID" => $arSite));
		}
	}

	
	/**
	* <p>Метод изменяет права на доступ к проиндексированной информации данного модуля. Метод динамичный.</p>
	*
	*
	* @param string $MODULE_ID  Код модуля, права на доступ к информации которого необходимо
	* поменять.
	*
	* @param array $arGroups  Массив кодов групп, которые имеют право на чтение
	* проиндексированной информации.
	*
	* @param string $ITEM_ID = false Код элемента, права на доступ к которому необходимо поменять.
	* Если этот параметр равен false, то ограничение по коду элемента не
	* устанавливается (изменяются права на доступ к элементу с любыми
	* кодами). Не обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	* проиндексированных элементов, права на доступ к которым
	* необходимо поменять. Если этот параметр равен false, то ограничение
	* по первому параметру элемента не устанавливается. Не
	* обязательный параметр, по умолчанию равен false.
	*
	* @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	* проиндексированных элементов, права на доступ к которым
	* необходимо поменять. Если этот параметр равен false, то ограничение
	* по второму параметру элемента не устанавливается. Не
	* обязательный параметр, по умолчанию равен false.
	*
	* @param string $SITE_ID = false Сайт проиндексированного элемента, ограничивающий набор
	* элементов, права на доступ к которым необходимо поменять. Если
	* этот параметр равен false, то ограничение по сайту элемента не
	* устанавливается. Необязательный параметр, по умолчанию равен
	* false.<br><br> До версии 4.0.6 параметр назывался LID.
	*
	* @param string $PARAMS = false Необязательный параметр, по умолчанию равен false.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $Forum_ID = 5;
	* $arGroups = CForum::GetAccessPermissions($Forum_ID);
	* $arGPerm = Array();
	* for ($i=0; $i &lt; count($arGroups); $i++)
	* {
	* 	if ($arGroups[$i][1]&gt;="E")
	* 	{
	* 		$arGPerm[] = $arGroups[$i][0];
	* 		if($arGroups[$i][0]==2)
	* 			break;
	* 	}
	* }
	* CSearch::ChangePermission("forum", $arGPerm, false, $Forum_ID);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/changepermission.php
	* @author Bitrix
	*/
	public static function ChangePermission($MODULE_ID, $arGroups, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false, $PARAMS=false)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$bIncSites = false;

		$strSqlWhere = CSearch::__PrepareFilter(array(
			"MODULE_ID"=>$MODULE_ID,
			"ITEM_ID"=>$ITEM_ID,
			"PARAM1"=>$PARAM1,
			"PARAM2"=>$PARAM2,
			"SITE_ID"=>$SITE_ID,
			"PARAMS"=>$PARAMS,
		), $bIncSites);

		if($strSqlWhere)
		{
			$strSqlJoin1 = "INNER JOIN b_search_content sc ON sc.ID = b_search_content_right.SEARCH_CONTENT_ID";
			$match = array();
			//Copy first exists into inner join in hopeless try to defeat MySQL optimizer
			if(preg_match('#^\\s*EXISTS (\\(SELECT \\* FROM b_search_content_param WHERE SEARCH_CONTENT_ID = sc.ID AND PARAM_NAME = \'[^\']+\' AND PARAM_VALUE  = \'[^\']+\'\\))#', $strSqlWhere, $match))
			{
				$subTable = str_replace("SEARCH_CONTENT_ID = sc.ID AND", "", $match[1]);
				$strSqlJoin2 = "INNER JOIN ".$subTable." p1 ON p1.SEARCH_CONTENT_ID = sc.ID";
			}
			else
			{
				$strSqlJoin2 = "";
			}
		}
		else
		{
			$strSqlJoin1 = "";
			$strSqlJoin2 = "";
		}

		$rs = $DB->Query("
			SELECT sc.ID
			FROM b_search_content sc
			".$strSqlJoin2."
			".($strSqlWhere?
				"WHERE ".$strSqlWhere:
				""
			)."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arR = $rs->fetch())
		{
			CSearch::Update($arR["ID"], array("PERMISSIONS" => $arGroups));
		}
	}
}

class CSearchSQLHelper
{
	var $bIncSites = false;
	var $strSearchContentAlias = "";

	public function __construct($strSearchContentAlias)
	{
		$this->strSearchContentAlias = $strSearchContentAlias;
	}

	public function _CallbackURL($field_name, $operation, $field_value)
	{
		global $DB;

		if(is_array($field_value))
			$sql_values = array_map(array($DB, "ForSQL"), array_filter($field_value));
		elseif($field_value !== false)
			$sql_values = array($DB->ForSQL($field_value));
		else
			$sql_values = array();

		$strSql = "";
		if(!empty($sql_values))
		{
			switch($operation)
			{
				case "I":
				case "E":
				case "S":
				case "M":
					foreach($sql_values as $url_i)
					{
						$arSQL[] = $this->strSearchContentAlias.".URL LIKE '".$url_i."'";
						$arSQL[] = "scsite.URL LIKE '".$url_i."'";
					}
					$strSql = "(".implode(") OR (", $arSQL).")";
					$this->bIncSites = true;
					break;
				case "NI":
				case "N":
				case "NS":
				case "NM":
					$arSQL = array();
					foreach($sql_values as $url_i)
					{
						$arSQL[] = $this->strSearchContentAlias.".URL NOT LIKE '".$url_i."'";
						$arSQL[] = "scsite.URL NOT LIKE '".$url_i."'";
					}
					$strSql = "(".implode(") AND (", $arSQL).")";
					$this->bIncSites = true;
					break;
				default:
					break;
			}
		}

		if($strSql)
			return "(".$strSql.")";
		else
			return "";
	}

	public static function _CallbackPARAMS($field_name, $operation, $field_value)
	{
		global $DB;

		$arSql = array();
		if(is_array($field_value))
		{
			foreach($field_value as $key => $val)
			{
				if(is_array($val))
				{
					foreach($val as $i=>$val2)
						$val[$i] = $DB->ForSQL($val2);
					$where = " in ('".implode("', '", $val)."')";
				}
				else
				{
					$where = " = '".$DB->ForSQL($val)."'";
				}
				$arSql[] = "EXISTS (SELECT * FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$field_name." AND PARAM_NAME = '".$DB->ForSQL($key)."' AND PARAM_VALUE ".$where.")";
			}
		}

		switch($operation)
		{
			case "I":
			case "E":
			case "S":
			case "M":
				if(count($arSql))
					return implode(" AND ", $arSql);
		}
	}
}

class CAllSearchQuery
{
	var $m_query;
	var $m_words;
	var $m_stemmed_words;
	var $m_stemmed_words_id;
	var $m_fields;
	var $m_kav;
	var $default_query_type;
	var $rus_bool_lang;
	var $no_bool_lang;
	var $m_casematch;
	var $error = "";
	var $errorno = 0;
	var $bTagsSearch = false;
	var $m_tags_words;
	var $bStemming = false;
	var $bText = false;

	public function __construct($default_query_type = "and", $rus_bool_lang = "yes", $m_casematch = 0, $site_id = "")
	{
		return $this->CSearchQuery($default_query_type, $rus_bool_lang, $m_casematch, $site_id);
	}

	public function CSearchQuery($default_query_type = "and", $rus_bool_lang = "yes", $m_casematch = 0, $site_id = "")
	{
		$this->m_query  = "";
		$this->m_stemmed_words = array();
		$this->m_tags_words = array();
		$this->m_fields = "";
		$this->default_query_type = $default_query_type;
		$this->rus_bool_lang = $rus_bool_lang;
		$this->m_casematch = $m_casematch;
		$this->m_kav = array();
		$this->error = "";

		$db_site_tmp = CSite::GetByID($site_id);
		if ($ar_site_tmp = $db_site_tmp->Fetch())
			$this->m_lang=$ar_site_tmp["LANGUAGE_ID"];
		else
			$this->m_lang="en";
	}

	public function GetQueryString($fields, $query, $bTagsSearch = false, $bUseStemming = true, $bErrorOnEmptyStem = false)
	{
		$this->m_words = Array();
		$this->m_fields = explode(",", $fields);

		$this->bTagsSearch = $bTagsSearch;
		//In case there is no masks used we'll keep list
		//of all tags in this memeber
		//to perform optimization
		$this->m_tags_words = array();

		$this->m_query = $query = $this->CutKav($query);

		//Assume query does not have any word which can be stemmed
		$this->bStemming = false;
		if(!$this->bTagsSearch && $bUseStemming && COption::GetOptionString("search", "use_stemming")=="Y")
		{
			//In case when at least one word found: $this->bStemming = true
			$stem_query = $this->StemQuery($query, $this->m_lang);
			if($this->bStemming === true || $bErrorOnEmptyStem)
				$query = $stem_query;
		}
		$query = $this->ParseQ($query);

		if($query == "( )" || strlen($query)<=0)
		{
			$this->error=GetMessage("SEARCH_ERROR3");
			$this->errorno=3;
			return false;
		}

		$query = $this->PrepareQuery($query);

		return $query;
	}

	public function CutKav($query)
	{
		$arQuotes = array();
		if(preg_match_all("/([\"'])(.*?)(?<!\\\\)(\\1)/s", $query, $arQuotes))
		{
			foreach($arQuotes[2] as $i => $quoted)
			{
				$quoted = trim($quoted);
				if(strlen($quoted))
				{
					$repl = $i."cut5";
					$this->m_kav[$repl] = str_replace("\\\"", "\"", $quoted);
					$query = str_replace($arQuotes[0][$i], " ".$repl." ", $query);
				}
				else
				{
					$query = str_replace($arQuotes[0][$i], " ", $query);
				}

				if($i > 100) break;
			}
		}
		return $query;
	}

	public function ParseQ($q)
	{
		$q = trim($q);
		if(strlen($q) <= 0)
			return '';

		$q = $this->ParseStr($q);

		$q = str_replace(
			array("&"   , "|"   , "~"  , "("  , ")"),
			array(" && ", " || ", " ! ", " ( ", " ) "),
			$q
		);
		$q = "( $q )";
		$q = preg_replace("/\\s+/".BX_UTF_PCRE_MODIFIER, " ", $q);

		return $q;
	}

	public function ParseStr($qwe)
	{
		//Take alphabet into account
		$arStemInfo = stemming_init($this->m_lang);
		$letters = $arStemInfo["pcre_letters"]."|+&~()";

		//Erase delimiters from the query
		$qwe = trim(preg_replace("/[^".$letters."]+/".BX_UTF_PCRE_MODIFIER, " ", $qwe));

		// query language normalizer
		if(!$this->no_bool_lang)
		{
			$qwe=preg_replace("/(\\s+|^|[|&~])or(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1|\\2", $qwe);
			$qwe=preg_replace("/(\\s+|^|[|&~])and(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1&\\2", $qwe);
			$qwe=preg_replace("/(\\s+|^|[|&~])not(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1~\\2", $qwe);
			$qwe=preg_replace("/(\\s+|^|[|&~])without(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1~\\2", $qwe);

			if($this->rus_bool_lang == 'yes')
			{
				$qwe=preg_replace("/(\\s+|^|[|&~])".GetMessage("SEARCH_TERM_OR")."(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1|\\2", $qwe);
				$qwe=preg_replace("/(\\s+|^|[|&~])".GetMessage("SEARCH_TERM_AND")."(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1&\\2", $qwe);
				$qwe=preg_replace("/(\\s+|^|[|&~])".GetMessage("SEARCH_TERM_NOT_1")."(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1~\\2", $qwe);
				$qwe=preg_replace("/(\\s+|^|[|&~])".GetMessage("SEARCH_TERM_NOT_2")."(\\s+|\$|[|&~])/is".BX_UTF_PCRE_MODIFIER, "\\1~\\2", $qwe);
			}
		}

		$qwe=preg_replace("/(\\s*\\|+\\s*)/is".BX_UTF_PCRE_MODIFIER, "|", $qwe);
		$qwe=preg_replace("/(\\s*\\++\\s*|\\s*\\&\\s*)/is".BX_UTF_PCRE_MODIFIER, "&", $qwe);
		$qwe=preg_replace("/(\\s*\\~+\\s*)/is".BX_UTF_PCRE_MODIFIER, "~", $qwe);

		$qwe=preg_replace("/\s*([()])\s*/s".BX_UTF_PCRE_MODIFIER,"\\1",$qwe);

		// default query type is and
		if(strtolower($this->default_query_type) == 'or')
			$default_op = "|";
		else
			$default_op = "&";

		$qwe=preg_replace("/(\s+|\&\|+|\|\&+)/s".BX_UTF_PCRE_MODIFIER, $default_op, $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\|+/", "|", $qwe);
		$qwe=preg_replace("/&+/", "&", $qwe);
		$qwe=preg_replace("/~+/", "~", $qwe);
		$qwe=preg_replace("/\|\&\|/", "&", $qwe);
		$qwe=preg_replace("/[\|\&\~]+$/", "", $qwe);
		$qwe=preg_replace("/^[\|\&]+/", "", $qwe);

		// transform "w1 ~w2" -> "w1 default_op ~ w2"
		// ") ~w" -> ") default_op ~w"
		// "w ~ (" -> "w default_op ~("
		// ") w" -> ") default_op w"
		// "w (" -> "w default_op ("
		// ")(" -> ") default_op ("

		$qwe=preg_replace("/([^\&\~\|\(\)]+)~([^\&\~\|\(\)]+)/s".BX_UTF_PCRE_MODIFIER,"\\1".$default_op."~\\2", $qwe);
		$qwe=preg_replace("/\)~{1,}/s".BX_UTF_PCRE_MODIFIER,")".$default_op."~", $qwe);
		$qwe=preg_replace("/~{1,}\(/s".BX_UTF_PCRE_MODIFIER, ($default_op=="|"? "~|(": "&~("), $qwe);
		$qwe=preg_replace("/\)([^\&\~\|\(\)]+)/s".BX_UTF_PCRE_MODIFIER, ")".$default_op."\\1", $qwe);
		$qwe=preg_replace("/([^\&\~\|\(\)]+)\(/s".BX_UTF_PCRE_MODIFIER, "\\1".$default_op."(", $qwe);
		$qwe=preg_replace("/\) *\(/s".BX_UTF_PCRE_MODIFIER, ")".$default_op."(", $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\|+/", "|", $qwe);
		$qwe=preg_replace("/&+/", "&", $qwe);

		// remove errornous format of query - ie: '(&', '&)', '(|', '|)', '~&', '~|', '~)'
		$qwe=preg_replace("/\(\&{1,}/s", "(", $qwe);
		$qwe=preg_replace("/\&{1,}\)/s", ")", $qwe);
		$qwe=preg_replace("/\~{1,}\)/s", ")", $qwe);
		$qwe=preg_replace("/\(\|{1,}/s", "(", $qwe);
		$qwe=preg_replace("/\|{1,}\)/s", ")", $qwe);
		$qwe=preg_replace("/\~{1,}\&{1,}/s", "&", $qwe);
		$qwe=preg_replace("/\~{1,}\|{1,}/s", "|", $qwe);

		$qwe=preg_replace("/\(\)/s", "", $qwe);
		$qwe=preg_replace("/^[\|\&]{1,}/s", "", $qwe);
		$qwe=preg_replace("/[\|\&\~]{1,}$/s", "", $qwe);
		$qwe=preg_replace("/\|\&/s", "&", $qwe);
		$qwe=preg_replace("/\&\|/s", "|", $qwe);

		// remove unnesessary boolean operators one more time
		$qwe=preg_replace("/\|+/", "|", $qwe);
		$qwe=preg_replace("/&+/", "&", $qwe);

		return $qwe;
	}

	function StemWord($w)
	{
		static $preg_ru = false;
		if (is_array($w))
			$w = $w[0];
		$wu = ToUpper($w);

		if(!$this->no_bool_lang)
		{
			if(preg_match("/^(OR|AND|NOT|WITHOUT)$/", $wu))
			{
				return $w;
			}
			elseif($this->rus_bool_lang == 'yes')
			{
				if($preg_ru === false)
					$preg_ru = "/^(".ToUpper(GetMessage("SEARCH_TERM_OR")."|".GetMessage("SEARCH_TERM_AND")."|".GetMessage("SEARCH_TERM_NOT_1")."|".GetMessage("SEARCH_TERM_NOT_2")).")$/".BX_UTF_PCRE_MODIFIER;
				if(preg_match($preg_ru, $wu))
					return $w;
			}
		}

		if(preg_match("/cut[56]/i", $w))
			return $w;
		$arrStem = array_keys(stemming($w, $this->m_lang));
		if(count($arrStem) < 1)
			return " ";
		else
		{
			$this->bStemming = true;
			return $arrStem[0];
		}
	}

	public static function StemQuery($q, $lang="en")
	{
		$arStemInfo = stemming_init($lang);
		return preg_replace_callback("/([".$arStemInfo["pcre_letters"]."]+)/".BX_UTF_PCRE_MODIFIER, array($this, "StemWord"), $q);
	}

	public function PrepareQuery($q)
	{
		$state = 0;
		$qu = array();
		$n = 0;
		$this->error = "";

		$t = strtok($q," ");
		while (($t!="") && ($this->error==""))
		{
			if ($state == 0)
			{
				if (($t=="||") || ($t=="&&") || ($t==")"))
				{
					$this->error = GetMessage("SEARCH_ERROR2")." ".$t;
					$this->errorno = 2;
				}
				elseif ($t=="!")
				{
					$state = 0;
					$qu[] = " NOT ";
				}
				elseif ($t=="(")
				{
					$n++;
					$state = 0;
					$qu[] = "(";
				}
				else
				{
					$state = 1;
					$where = $this->BuildWhereClause($t);
					$c = count($qu);
					if (
						$where === "1=1"
						&& (
							($c > 0 && $qu[$c-1] === " OR ")
							|| ($c > 1 && $qu[$c-1] === "(" && $qu[$c-2] === " OR ")
						)
					)
					{
						$where = "1<>1";
					}
					$qu[] = " ".$where." ";
				}
			}
			elseif ($state == 1)
			{
				if (($t=="||") || ($t=="&&"))
				{
					$state = 0;
					if ($t=='||')
						$qu[] = " OR ";
					else
						$qu[] = " AND ";
				}
				elseif ($t==")")
				{
					$n--;
					$state = 1;
					$qu[] = ")";
				}
				else
				{
					$this->error = GetMessage("SEARCH_ERROR2")." ".$t;
					$this->errorno = 2;
				}
			}
			else
			{

				break;
			}
			$t = strtok(" ");
		}

		if (($this->error=="") && ($n != 0))
		{
			$this->error = GetMessage("SEARCH_ERROR1");
			$this->errorno = 1;
		}

		if ($this->error != "")
		{
			return 0;
		}

		return implode($qu);
	}
}

class CSearchCallback
{
	var $MODULE="";
	var $max_execution_time=0;
	var $CNT=0;
	var $SESS_ID = "";
	public function Index($arFields)
	{
		$ID = $arFields["ID"];
		if($ID=="")
			return true;
		unset($arFields["ID"]);
		CSearch::Index($this->MODULE, $ID, $arFields, false, $this->SESS_ID);
		$this->CNT = $this->CNT+1;
		if($this->max_execution_time>0 && getmicrotime() - START_EXEC_TIME > $this->max_execution_time)
			return false;
		else
			return true;
	}
}
?>