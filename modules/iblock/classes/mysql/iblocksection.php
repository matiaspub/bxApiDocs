<?

/**
 * <b>CIBlockSection</b> - класс для работы с разделами (группами) информационных блоков.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/index.php
 * @author Bitrix
 */
class CIBlockSection extends CAllIBlockSection
{
	///////////////////////////////////////////////////////////////////
	// List of sections
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Возвращает список разделов, отсортированных в порядке<span class="syntax"><i> arOrder</i></span> по фильтру <i>arFilter</i>. Нестатический метод.</p>
	*
	*
	* @param array $arOrder = Array("SORT"=>"ASC") Массив для сортировки, имеющий вид <i>by1</i>=&gt;<i>order1</i>[,
	* <i>by2</i>=&gt;<i>order2</i> [, ..]], где <i> by1, ... </i> - поле сортировки, может
	* принимать значения:          <ul> <li> <b>id</b> - код группы;</li>          	            <li>
	* <b>section</b> - код родительской группы;</li>          	            <li> <b>name</b> -
	* название группы;</li>                     <li> <b>code</b> - символьный код
	* группы;</li>          	            <li> <b>active</b> - активности группы;</li>          	        
	*    <li> <b>left_margin</b> - левая граница;</li>          	            <li> <b>depth_level</b> -
	* глубина вложенности (начинается с 1);</li>          	            <li> <b>sort</b> -
	* индекс сортировки;</li>                     <li> <b>created</b> - по времени создания
	* группы;</li>                     <li> <b>created_by</b> - по идентификатору создателя
	* группы;</li>                     <li> <b>modified_by</b> - по идентификатору
	* пользователя изменившего группу;</li>          	            <li> <b>element_cnt</b> -
	* количество элементов в группе, работает только если <b>bIncCnt</b> =
	* true;</li>          	            <li> <b>timestamp_x</b> - по времени последнего
	* изменения.</li>          </ul> <i>order1, ... </i> - порядок сортировки, может
	* принимать значения:          <ul> <li> <b>asc</b> - по возрастанию;</li>          	       
	*     <li> <b>desc</b> - по убыванию.</li>          </ul> <br> Кроме того, сортировка
	* возможна и по пользовательским свойствам UF_XXX. <br><br> Значение по
	* умолчанию Array("SORT"=&gt;"ASC") означает, что результат выборки будет
	* отсортирован по возрастанию. Если задать пустой массив Array(), то
	* результат отсортирован не будет.
	*
	* @param array $arFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]).
	* 	<i>Фильтруемое поле</i> может принимать значения:          <ul> <li> <b>ACTIVE</b>
	* - фильтр по активности (Y|N);</li>          	            <li> <b>GLOBAL_ACTIVE</b> - фильтр по
	* активности, учитывая активность вышележащих разделов (Y|N);</li>         
	* 	            <li> <b>NAME</b> - по названию (можно искать по шаблону [%_]);</li>         
	* 	            <li> <b>CODE</b> - по символьному коду (по шаблону [%_]);</li>          	      
	*      <li> <b>XML_ID</b> или <b>EXTERNAL_ID</b> - по внешнему коду (по шаблону [%_]);</li>   
	*       	            <li> <b>SECTION_ID</b> - по коду раздела-родителя (если указать
	* false, то будут возвращены корневые разделы);</li>          	            <li>
	* <b>DEPTH_LEVEL</b> - по уровню вложенности (начинается с 1);</li>          	           
	* <li> <b>LEFT_BORDER</b>, <b> RIGHT_BORDER</b> - по левой и правой границе
	* (используется, когда необходимо выбрать некий диапазон разделов,
	* см. <a href="#ex4">пример №4</a>);</li>                     <li> <b>LEFT_MARGIN</b>, <b>RIGHT_MARGIN</b> -
	* по положению в дереве (используется, когда необходима выборка
	* дерева подразделов, см. <a href="#ex4">пример №4</a>);</li>          	            <li>
	* <b>ID</b> - по коду раздела;</li>          	            <li> <b>IBLOCK_ID</b> - по коду
	* родительского информационного блока;</li>          	            <li>
	* <b>IBLOCK_ACTIVE</b> - по активности родительского информационного
	* блока;</li>          	            <li> <b>IBLOCK_NAME</b> - по названию информационного
	* блока (по шаблону [%_]);</li>          	            <li> <b>IBLOCK_TYPE</b> - по типу
	* информационного блока (по шаблону [%_]);</li>          	            <li> <b>IBLOCK_CODE
	* </b><i> - </i>по символьному коду информационного блока (по шаблону
	* [%_]);</li>          	            <li> <b>IBLOCK_XML_ID</b> или <b>IBLOCK_EXTERNAL_ID</b> - по внешнему
	* коду информационного блока (по шаблону [%_]);</li>                     <li> <span
	* style="font-weight: bold; ">TIMESTAMP_X</span> - по времени последнего изменения;</li>      
	*               <li> <span style="font-weight: bold; ">DATE_CREATE</span> - по времени создания;</li>    
	*                 <li> <span style="font-weight: bold; ">MODIFIED_BY </span>- по коду пользователя
	* изменившему раздел;              <br> </li>                     <li> <span style="font-weight: bold;
	* ">CREATED_BY</span> - по содателю;              <br> </li>                     <li> <span style="font-weight:
	* bold; ">SOCNET_GROUP_ID</span> - по привязке к группе Социальной сети;</li>              
	*       <li> <b>MIN_PERMISSION</b> - фильтр по правам доступа, по умолчанию
	* принимает <i>R</i> (уровень доступа <i>Чтение</i>);</li>                     <li>
	* <b>CHECK_PERMISSIONS</b> - если установлено значение "N", то проверки прав не
	* происходит; </li>          	            <li> <b>PROPERTY </b><i> - </i>по значениям свойств
	* внутрилежащих элементов, PROPERTY - массив вида Array("код
	* свойства"=&gt;"значение", ...).</li>          </ul>        Все фильтруемые поля
	* могут содержать перед названием <a
	* href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2683" >тип проверки
	* фильтра</a>.          <br><br><b><i>Значения фильтра</i></b> одиночное значение
	* или массив.          <br><br><div class="note"> <b>Важно!</b> Чтобы фильтрация
	* выполнялась по пользовательским свойствам, необходимо
	* обязательно передавать в фильтр <i>IBLOCK_ID</i>. Само свойство надо
	* указывать в виде UF_... </div> <br>        Необязательное. По умолчанию
	* записи не фильтруются.
	*
	* @param bool $bIncCnt = false Возвращать ли поле <i>ELEMENT_CNT</i> - количество элементов в разделе.
	* При этом arFilter дополнительно обрабатывает следующие фильтруемые
	* поля:<b>            <br></b>          <ul> <li> <b>ELEMENT_SUBSECTIONS</b> - подсчитывать
	* элементы вложенных подразделов или нет (Y|N). По умолчанию Y;</li>         
	*            <li> <b>CNT_ALL</b> - подсчитывать еще неопубликованные элементы
	* (Y|N). По умолчанию N. Актуально при установленном модуле
	* документооборота;</li>                     <li> <b>CNT_ACTIVE</b> - при подсчете
	* учитывать активность элементов (Y|N). По умолчанию N. Учитывается
	* флаг активности элемента ACTIVE и даты начала и окончания
	* активности.              <br> </li>          </ul>        Необязательный параметр, по
	* умолчанию равен false.
	*
	* @param array $Select = Array() Массив для выборки. <ul> <li> <b>ID</b> - ID группы информационного
	* блока.</li>	 <li> <b>CODE</b> - Символьный идентификатор.</li>   <li> <b>EXTERNAL_ID или
	* XML_ID</b> - Внешний код.</li> <li> <b>IBLOCK_ID</b> - ID информационного блока. </li>
	* <li> <b>IBLOCK_SECTION_ID</b> - ID группы родителя, если не задан то группа
	* корневая. </li>  <li> <b>TIMESTAMP_X</b> - Дата последнего изменения параметров
	* группы. </li> <li> <b>SORT</b> - Порядок сортировки (среди групп внутри
	* одной группы-родителя).</li> <li> <b>NAME</b> - Наименование группы.</li>  <li>
	* <b>ACTIVE</b> - Флаг активности (Y|N)</li> <li> <b>GLOBAL_ACTIVE</b> - Флаг активности,
	* учитывая активность вышележащих (родительских) групп (Y|N).
	* Вычисляется автоматически (не может быть изменен вручную).</li> <li>
	* <b>PICTURE</b> - Код картинки в таблице файлов.</li>  <li> <b>DESCRIPTION</b> -
	* Описание группы. </li>  <li> <b>DESCRIPTION_TYPE</b> - Тип описания группы
	* (text/html).</li> <li> <b>LEFT_MARGIN</b> - Левая граница группы. Вычисляется
	* автоматически (не устанавливается вручную). </li>  <li> <b>RIGHT_MARGIN</b> -
	* Правая граница группы. Вычисляется автоматически (не
	* устанавливается вручную). </li> <li> <b>DEPTH_LEVEL</b> - Уровень вложенности
	* группы. Начинается с 1. Вычисляется автоматически (не
	* устанавливается вручную). </li> <li> <b>SEARCHABLE_CONTENT</b> Содержимое для
	* поиска при фильтрации групп. Вычисляется автоматически.
	* Складывается из полей <b>NAME</b> и <b>DESCRIPTION</b> (без html тэгов, если
	* <b>DESCRIPTION_TYPE</b> установлен в html).</li>  <li> <b>SECTION_PAGE_URL</b> - Шаблон URL-а к
	* странице для детального просмотра раздела. Определяется из
	* параметров информационного блока. Изменяется автоматически.</li>
	* <li> <b>MODIFIED_BY</b> - Код пользователя, в последний раз изменившего
	* элемент.</li> <li> <b>DATE_CREATE</b> - Дата создания элемента.</li> <li> <b>CREATED_BY</b> -
	* Код пользователя, создавшего элемент.</li>  <li> <b>DETAIL_PICTURE</b> - Код
	* картинки в таблице файлов для детального просмотра.</li>  </ul> Кроме
	* того, можно вывести пользовательские свойства, если задать их код
	* (см. примечание ниже).
	*
	* @param array $NavStartParams = false Массив для постраничной навигации. Может содержать следующие
	* ключи: 	 <ul> <li> <b>bShowAll</b> - разрешить вывести все элементы при
	* постраничной навигации;</li> <li> <b>iNumPage</b> - номер страницы при
	* постраничной навигации;</li>  <li> <b>nPageSize</b> - количество элементов на
	* странице при постраничной навигации;</li>  <li> <b>nTopCount</b> - ограничить
	* количество возвращаемых методом записей сверху значением этого
	* ключа (ключ доступен с версии <b>15.5.5 </b>).</li>   </ul>
	*
	* @return CIBlockResult <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a><p></p><div
	* class="note"> <b>Примечание №1:</b> для вывода пользовательских свойств
	* обязательно должен быть передан <i>IBLOCK_ID</i> и в arSelect код
	* необходимых свойств <i>UF_XXX</i>. Если необходимо вывести все
	* пользовательские свойства, то в arSelect необходимо передать
	* <i>UF_*</i>.</div><p></p><div class="note"> <b>Примечание №2:</b> поле для сортировки
	* <i>left_margin</i>, так называемая "сквозная" сортировка, высчитывается на
	* основании поля <i>sort</i>, уровня вложенности и сортировкой верхнего
	* уровня. Отличие полей <i>sort</i> и <i>left_margin</i> в том, что <i>sort</i>
	* указывается пользователем, для сортировки разделов между собой в
	* пределах одного раздела-родителя, а вычисляемое <i>left_margin</i>
	* предназначено для сортировки во всем информационном блоке.
	* </div><p></p><div class="note"> <b>Примечание №3:</b> нет возможности фильтровать
	* разделы в выборке  по количеству элементов.</div>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arIBTYPE = CIBlockType::GetByIDLang($type, LANGUAGE_ID);<br>if($arIBTYPE!==false)<br>{<br>  // выборка только активных разделов из инфоблока $IBLOCK_ID, в которых есть элементы <br>  // со значением свойства SRC, начинающееся с https://<br>  $arFilter = Array('IBLOCK_ID'=&gt;$IBLOCK_ID, 'GLOBAL_ACTIVE'=&gt;'Y', 'PROPERTY'=&gt;Array('SRC'=&gt;'https://%'));<br>  $db_list = CIBlockSection::GetList(Array($by=&gt;$order), $arFilter, true);<br>  $db_list-&gt;NavStart(20);<br>  echo $db_list-&gt;NavPrint($arIBTYPE["SECTION_NAME"]);<br>  while($ar_result = $db_list-&gt;GetNext())<br>  {<br>    echo $ar_result['ID'].' '.$ar_result['NAME'].': '.$ar_result['ELEMENT_CNT'].'&lt;br&gt;';<br>  }<br>  echo $db_list-&gt;NavPrint($arIBTYPE["SECTION_NAME"]);<br>}<br>?&gt;<br>
	* 
	* //пример выборки дерева подразделов для раздела 
	* $rsParentSection = CIBlockSection::GetByID(ID_необходимой_секции);
	* if ($arParentSection = $rsParentSection-&gt;GetNext())
	* {
	*    $arFilter = array('IBLOCK_ID' =&gt; $arParentSection['IBLOCK_ID'],'&gt;LEFT_MARGIN' =&gt; $arParentSection['LEFT_MARGIN'],'&lt;RIGHT_MARGIN' =&gt; $arParentSection['RIGHT_MARGIN'],'&gt;DEPTH_LEVEL' =&gt; $arParentSection['DEPTH_LEVEL']); // выберет потомков без учета активности
	*    $rsSect = CIBlockSection::GetList(array('left_margin' =&gt; 'asc'),$arFilter);
	*    while ($arSect = $rsSect-&gt;GetNext())
	*    {
	*        // получаем подразделы
	*    }
	* }
	* 
	* //в шаблоне меню, построенного по структуре инфоблока, менять ссылку на элемент, если заполнено пользовательское поле 
	* 
	* &lt;? 
	* //внутри цикла построения меню
	* $uf_iblock_id = 1; //ID инфоблока
	* $uf_name = Array("UF_PAGE_LINK"); //пользовательское поле UF_PAGE_LINK
	* 
	* preg_match('/\?ID=([0-9]+)\&amp;?/i', $arItem["LINK"], $matches); //SEF отключен, поэтому спокойно берем SECTION_ID из ссылки по шаблону ID=#SECTION_ID#
	* $uf_section_id = $matches[1];
	* if(CModule::IncludeModule("iblock")): //подключаем модуль инфоблок для работы с классом CIBlockSection
	*    $uf_arresult = CIBlockSection::GetList(Array("SORT"=&gt;"­­ASC"), Array("IBLOCK_ID" =&gt; $uf_iblock_id, "ID" =&gt; $uf_section_id), false, $uf_name);
	*    if($uf_value = $uf_arresult-&gt;GetNext()):
	*       if(strlen($uf_value["UF_PAGE_LINK"]) &gt; 0): //проверяем что поле заполнено
	*          $arItem["LINK"] = $uf_value["UF_PAGE_LINK"]; //подменяем ссылку и используем её в дальнейшем
	*       endif;
	*    endif;
	* endif;
	* ?&gt; 
	* 
	* //рассмотрим разницу использования фильтра по LEFT_MARGIN, RIGHT_MARGIN и LEFT_BORDER, RIGHT_BORDER
	* //допустим, что у некоторого раздела LEFT_MARGIN (значение в базе) = 10, RIGHT_MARGIN (значение в базе) = 40
	* 
	* //в первом примере кода будет выбран как сам раздел, так и все его подразделы,
	* //поскольку всегда LEFT_MARGIN раздела-потомка &gt; LEFT_MARGIN раздела-родителя
	* //и RIGHT_MARGIN раздела-потомка &lt; RIGHT_MARGIN раздела-родителя
	* 
	* $arFilter = array('IBLOCK_ID' =&gt; 10, 'LEFT_MARGIN' =&gt; 10, 'RIGHT_MARGIN' =&gt; 40);
	* $rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' =&gt; 'ASC'), $arFilter);
	* while ($arSection = $rsSections-&gt;Fetch())
	* {
	*     echo htmlspecialcharsbx($arSection['NAME']).' LEFT_MARGIN: '.$arSection['LEFT_MARGIN'].' RIGHT_MARGIN: '.$arSection['RIGHT_MARGIN'].'&amp;ltbr&gt;';
	* }
	* 
	* //во втором примере кода будет возвращена только одна запись - сам раздел
	* 
	* $arFilter = array('IBLOCK_ID' =&gt; 10, 'LEFT_BORDER' =&gt; 10, 'RIGHT_BORDER' =&gt; 40);
	* $rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' =&gt; 'ASC'), $arFilter);
	* while ($arSction = $rsSections-&gt;Fetch())
	* {
	*     echo htmlspecialcharsbx($arSection['NAME']).' LEFT_MARGIN: '.$arSection['LEFT_MARGIN'].' RIGHT_MARGIN: '.$arSection['RIGHT_MARGIN'].'&lt;br&gt;';
	* }
	* 
	* 		<!--
	* 		imgID = 'img-7308';
	* 		imgIDsection = 'img-7308-section';
	* 		document.getElementById(imgID).src = "/api_help/minus.gif";
	* 		document.getElementById(imgIDsection).src = "/api_help/section_open.gif";
	* 		//-->
	* 		
	* 		<!--
	* 		imgID = 'img-7310';
	* 		imgIDsection = 'img-7310-section';
	* 		document.getElementById(imgID).src = "/api_help/minus.gif";
	* 		document.getElementById(imgIDsection).src = "/api_help/section_open.gif";
	* 		//-->
	* 		
	* 		<!--
	* 		imgID = 'img-7316';
	* 		imgIDsection = 'img-7316-section';
	* 		document.getElementById(imgID).src = "/api_help/minus.gif";
	* 		document.getElementById(imgIDsection).src = "/api_help/section_open.gif";
	* 		//-->
	* 		
	* 		function MoveToPage(move_to)
	* 		{
	* 			if('next'==move_to)
	* 				window.location = '/api_help/iblock/classes/ciblocksection/getmixedlist.php';
	* 			else if('prev'==move_to)
	* 				window.location = '/api_help/iblock/classes/ciblocksection/getcount.php';
	* 		}
	* 		
	* 							<!--
	* 							document.getElementById('90688').style.fontWeight = 'bold';
	* 							//-->
	* 							
	* var smallEngLettersReg = new Array(/e'/g, /ch/g, /sh/g, /yo/g, /jo/g, /zh/g, /yu/g, /ju/g, /ya/g, /ja/g, /a/g, /b/g, /v/g, /g/g, /d/g, /e/g, /z/g, /i/g, /j/g, /k/g, /l/g, /m/g, /n/g, /o/g, /p/g, /r/g, /s/g, /t/g, /u/g, /f/g, /h/g, /c/g, /w/g, /~/g, /y/g, /'/g);
	* var smallRusLetters = new Array("э", "ч", "ш", "ё", "ё", "ж", "ю", "ю", "я", "я", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "щ", "ъ", "ы", "ь");
	* 
	* var capitEngLettersReg = new Array(
	* 	/Ch/g, /Sh/g, 
	* 	/Yo/g, /Zh/g, 
	* 	/Yu/g, /Ya/g, 
	* 	/E'/g, /CH/g, /SH/g, /YO/g, /JO/g, /ZH/g, /YU/g, /JU/g, /YA/g, /JA/g, /A/g, /B/g, /V/g, /G/g, /D/g, /E/g, /Z/g, /I/g, /J/g, /K/g, /L/g, /M/g, /N/g, /O/g, /P/g, /R/g, /S/g, /T/g, /U/g, /F/g, /H/g, /C/g, /W/g, /Y/g);
	* var capitRusLetters = new Array(
	* 	"Ч", "Ш",
	* 	"Ё", "Ж",
	* 	"Ю", "Я",
	* 	"Э", "Ч", "Ш", "Ё", "Ё", "Ж", "Ю", "Ю", "\Я", "\Я", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Щ", "Ы");
	* 
	* var smallRusLettersReg = new Array(/э/g, /ч/g, /ш/g, /ё/g, /ё/g,/ж/g, /ю/g, /ю/g, /я/g, /я/g, /а/g, /б/g, /в/g, /г/g, /д/g, /е/g, /з/g, /и/g, /й/g, /к/g, /л/g, /м/g, /н/g, /о/g, /п/g, /р/g, /с/g, /т/g, /у/g, /ф/g, /х/g, /ц/g, /щ/g, /ъ/g, /ы/g, /ь/g );
	* var smallEngLetters = new Array("e", "ch", "sh", "yo", "jo", "zh", "yu", "ju", "ya", "ja", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "w", "~", "y", "'");
	* 
	* var capitRusLettersReg = new Array(
	* 	/Ч(?=[^А-Я])/g, /Ш(?=[^А-Я])/g, 
	* 	/Ё(?=[^А-Я])/g, /Ж(?=[^А-Я])/g, 
	* 	/Ю(?=[^А-Я])/g, /Я(?=[^А-Я])/g, 
	* 	/Э/g, /Ч/g, /Ш/g, /Ё/g, /Ё/g, /Ж/g, /Ю/g, /Ю/g, /Я/g, /Я/g, /А/g, /Б/g, /В/g, /Г/g, /Д/g, /Е/g, /З/g, /И/g, /Й/g, /К/g, /Л/g, /М/g, /Н/g, /О/g, /П/g, /Р/g, /С/g, /Т/g, /У/g, /Ф/g, /Х/g, /Ц/g, /Щ/g, /Ъ/g, /Ы/g, /Ь/g);
	* var capitEngLetters = new Array(
	* 	"Ch", "Sh",
	* 	"Yo", "Zh",
	* 	"Yu", "Ya",
	* 	"E", "CH", "SH", "YO", "JO", "ZH", "YU", "JU", "YA", "JA", "A", "B", "V", "G", "D", "E", "Z", "I", "J", "K", "L", "M", "N", "O", "P", "R", "S", "T", "U", "F", "H", "C", "W", "~", "Y", "'");
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-315789-1474649860',
	* 			'FORUM_POST',
	* 			'315789',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-319631-1474650311',
	* 			'FORUM_POST',
	* 			'319631',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-320394-1474649488',
	* 			'FORUM_POST',
	* 			'320394',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-320627-1474649671',
	* 			'FORUM_POST',
	* 			'320627',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-331219-1474649576',
	* 			'FORUM_POST',
	* 			'331219',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-342695-1474650022',
	* 			'FORUM_POST',
	* 			'342695',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-351621-1474649667',
	* 			'FORUM_POST',
	* 			'351621',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-355817-1474650411',
	* 			'FORUM_POST',
	* 			'355817',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-388818-1474650047',
	* 			'FORUM_POST',
	* 			'388818',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* BX.ready(function() {
	* 		if (!window.RatingLike && top.RatingLike)
	* 			RatingLike = top.RatingLike;
	* 
	* 		if (typeof(RatingLike) == 'undefined')
	* 			return false;
	* 
	* 		RatingLike.Set(
	* 			'FORUM_POST-432155-1474650221',
	* 			'FORUM_POST',
	* 			'432155',
	* 			'N',
	* 			'',
	* 			{'LIKE_Y' : 'Больше не нравится', 'LIKE_N' : 'Мне нравится', 'LIKE_D' : 'Это нравится'},
	* 			'light',
	* 			''
	* 		);
	* 
	* 		if (typeof(RatingLikePullInit) == 'undefined')
	* 		{
	* 			RatingLikePullInit = true;
	* 			BX.addCustomEvent("onPullEvent-main", function(command,params) {
	* 				if (command == 'rating_vote')
	* 				{
	* 					RatingLike.LiveUpdate(params);
	* 				}
	* 			});
	* 		}
	* 
	* 
	* });
	* 
	* 			DocSwitchTab('DocGeneral')
	* 		
	* 		BX.ready(function(){
	* 			hljs.configure({tabReplace: '    '});
	* 			hljs.initHighlightingOnLoad();
	* 			var elements = BX.findChildren(document, {tag: 'pre'}, true);
	* 			if (elements != null)
	* 			{
	* 				for (var j = 0; j < elements.length; j++)
	* 				{
	* 					hljs.highlightBlock(elements[j]);
	* 				}
	* 			}		
	* 		});
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a> </li>    
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fsection">Поля раздела
	* информационного блока </a> </li>  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblocksection/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder=Array("SORT"=>"ASC"), $arFilter=Array(), $bIncCnt = false, $arSelect = array(), $arNavStartParams=false)
	{
		global $DB, $USER, $USER_FIELD_MANAGER;

		if (!is_array($arOrder))
			$arOrder = array();

		if(isset($arFilter["IBLOCK_ID"]) && $arFilter["IBLOCK_ID"] > 0)
		{
			$obUserFieldsSql = new CUserTypeSQL;
			$obUserFieldsSql->SetEntity("IBLOCK_".$arFilter["IBLOCK_ID"]."_SECTION", "BS.ID");
			$obUserFieldsSql->SetSelect($arSelect);
			$obUserFieldsSql->SetFilter($arFilter);
			$obUserFieldsSql->SetOrder($arOrder);
		}
		else
		{
			foreach($arFilter as $key => $val)
			{
				$res = CIBlock::MkOperationFilter($key);
				if(preg_match("/^UF_/", $res["FIELD"]))
				{
					trigger_error("arFilter parameter of the CIBlockSection::GetList contains user fields, but has no IBLOCK_ID field.", E_USER_WARNING);
					break;
				}
			}
		}

		$arJoinProps = array();
		$bJoinFlatProp = false;

		$arSqlSearch = CIBlockSection::GetFilter($arFilter);

		$bCheckPermissions = !array_key_exists("CHECK_PERMISSIONS", $arFilter) || $arFilter["CHECK_PERMISSIONS"]!=="N";
		$bIsAdmin = is_object($USER) && $USER->IsAdmin();
		if($bCheckPermissions && !$bIsAdmin)
			$arSqlSearch[] = CIBlockSection::_check_rights_sql($arFilter["MIN_PERMISSION"]);

		if(array_key_exists("PROPERTY", $arFilter))
		{
			$val = $arFilter["PROPERTY"];
			foreach($val as $propID=>$propVAL)
			{
				$res = CIBlock::MkOperationFilter($propID);
				$propID = $res["FIELD"];
				$cOperationType = $res["OPERATION"];
				if($db_prop = CIBlockProperty::GetPropertyArray($propID, CIBlock::_MergeIBArrays($arFilter["IBLOCK_ID"], $arFilter["IBLOCK_CODE"])))
				{

					$bSave = false;
					if(array_key_exists($db_prop["ID"], $arJoinProps))
						$iPropCnt = $arJoinProps[$db_prop["ID"]];
					elseif($db_prop["VERSION"]!=2 || $db_prop["MULTIPLE"]=="Y")
					{
						$bSave = true;
						$iPropCnt=count($arJoinProps);
					}

					if(!is_array($propVAL))
						$propVAL = Array($propVAL);

					if($db_prop["PROPERTY_TYPE"]=="N" || $db_prop["PROPERTY_TYPE"]=="G" || $db_prop["PROPERTY_TYPE"]=="E")
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "number", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE_NUM", $propVAL, "number", $cOperationType);
					}
					else
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "string", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE", $propVAL, "string", $cOperationType);
					}

					if(strlen($r)>0)
					{
						if($bSave)
						{
							$db_prop["iPropCnt"] = $iPropCnt;
							$arJoinProps[$db_prop["ID"]] = $db_prop;
						}
						$arSqlSearch[] = $r;
					}
				}
			}
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $r)
			if(strlen($r)>0)
				$strSqlSearch .= "\n\t\t\t\tAND  (".$r.") ";

		if(isset($obUserFieldsSql))
		{
			$r = $obUserFieldsSql->GetFilter();
			if(strlen($r)>0)
				$strSqlSearch .= "\n\t\t\t\tAND (".$r.") ";
		}

		$strProp1 = "";
		foreach($arJoinProps as $propID=>$db_prop)
		{
			if($db_prop["VERSION"]==2)
				$strTable = "b_iblock_element_prop_m".$db_prop["IBLOCK_ID"];
			else
				$strTable = "b_iblock_element_property";
			$i = $db_prop["iPropCnt"];
			$strProp1 .= "
				LEFT JOIN b_iblock_property FP".$i." ON FP".$i.".IBLOCK_ID=B.ID AND
				".((int)$propID>0?" FP".$i.".ID=".(int)$propID." ":" FP".$i.".CODE='".$DB->ForSQL($propID, 200)."' ")."
				LEFT JOIN ".$strTable." FPV".$i." ON FP".$i.".ID=FPV".$i.".IBLOCK_PROPERTY_ID AND FPV".$i.".IBLOCK_ELEMENT_ID=BE.ID ";
		}
		if($bJoinFlatProp)
			$strProp1 .= "
				LEFT JOIN b_iblock_element_prop_s".$bJoinFlatProp." FPS ON FPS.IBLOCK_ELEMENT_ID = BE.ID
			";

		$arFields = array(
			"ID" => "BS.ID",
			"CODE" => "BS.CODE",
			"XML_ID" => "BS.XML_ID",
			"EXTERNAL_ID" => "BS.XML_ID",
			"IBLOCK_ID" => "BS.IBLOCK_ID",
			"IBLOCK_SECTION_ID" => "BS.IBLOCK_SECTION_ID",
			"TIMESTAMP_X" =>  $DB->DateToCharFunction("BS.TIMESTAMP_X"),
			"TIMESTAMP_X_UNIX"=>'UNIX_TIMESTAMP(BS.TIMESTAMP_X)',
			"SORT" => "BS.SORT",
			"NAME" => "BS.NAME",
			"ACTIVE" => "BS.ACTIVE",
			"GLOBAL_ACTIVE" => "BS.GLOBAL_ACTIVE",
			"PICTURE" => "BS.PICTURE",
			"DESCRIPTION" => "BS.DESCRIPTION",
			"DESCRIPTION_TYPE" => "BS.DESCRIPTION_TYPE",
			"LEFT_MARGIN" => "BS.LEFT_MARGIN",
			"RIGHT_MARGIN" => "BS.RIGHT_MARGIN",
			"DEPTH_LEVEL" => "BS.DEPTH_LEVEL",
			"SEARCHABLE_CONTENT" => "BS.SEARCHABLE_CONTENT",
			"MODIFIED_BY" => "BS.MODIFIED_BY",
			"DATE_CREATE" =>  $DB->DateToCharFunction("BS.DATE_CREATE"),
			"DATE_CREATE_UNIX"=>'UNIX_TIMESTAMP(BS.DATE_CREATE)',
			"CREATED_BY" => "BS.CREATED_BY",
			"DETAIL_PICTURE" => "BS.DETAIL_PICTURE",
			"TMP_ID" => "BS.TMP_ID",

			"LIST_PAGE_URL" => "B.LIST_PAGE_URL",
			"SECTION_PAGE_URL" => "B.SECTION_PAGE_URL",
			"IBLOCK_TYPE_ID" => "B.IBLOCK_TYPE_ID",
			"IBLOCK_CODE" => "B.CODE",
			"IBLOCK_EXTERNAL_ID" => "B.XML_ID",
			"SOCNET_GROUP_ID" => "BS.SOCNET_GROUP_ID",
		);

		$arSqlSelect = array();
		foreach($arSelect as $field)
		{
			$field = strtoupper($field);
			if(array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field]." AS ".$field;
		}

		if(array_key_exists("DESCRIPTION", $arSqlSelect))
			$arSqlSelect["DESCRIPTION_TYPE"] = $arFields["DESCRIPTION_TYPE"]." AS DESCRIPTION_TYPE";

		if(array_key_exists("LIST_PAGE_URL", $arSqlSelect) || array_key_exists("SECTION_PAGE_URL", $arSqlSelect))
		{
			$arSqlSelect["ID"] = $arFields["ID"]." AS ID";
			$arSqlSelect["CODE"] = $arFields["CODE"]." AS CODE";
			$arSqlSelect["EXTERNAL_ID"] = $arFields["EXTERNAL_ID"]." AS EXTERNAL_ID";
			$arSqlSelect["IBLOCK_TYPE_ID"] = $arFields["IBLOCK_TYPE_ID"]." AS IBLOCK_TYPE_ID";
			$arSqlSelect["IBLOCK_ID"] = $arFields["IBLOCK_ID"]." AS IBLOCK_ID";
			$arSqlSelect["IBLOCK_CODE"] = $arFields["IBLOCK_CODE"]." AS IBLOCK_CODE";
			$arSqlSelect["IBLOCK_EXTERNAL_ID"] = $arFields["IBLOCK_EXTERNAL_ID"]." AS IBLOCK_EXTERNAL_ID";
			$arSqlSelect["GLOBAL_ACTIVE"] = $arFields["GLOBAL_ACTIVE"]." AS GLOBAL_ACTIVE";
			//$arr["LANG_DIR"],
		}

		$additionalSelect = array();
		$arSqlOrder = array();
		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			if(isset($arSqlOrder[$by]))
				continue;
			$order = strtolower($order);
			if($order!="asc")
				$order = "desc";

			switch ($by)
			{
				case "id":
					$arSqlOrder[$by] = " BS.ID ".$order." ";
					$additionalSelect["ID"] = $arFields["ID"]." AS ID";
					break;
				case "section":
					$arSqlOrder[$by] = " BS.IBLOCK_SECTION_ID ".$order." ";
					$additionalSelect["IBLOCK_SECTION_ID"] = $arFields["IBLOCK_SECTION_ID"]." AS IBLOCK_SECTION_ID";
					break;
				case "name":
					$arSqlOrder[$by] = " BS.NAME ".$order." ";
					$additionalSelect["NAME"] = $arFields["NAME"]." AS NAME";
					break;
				case "code":
					$arSqlOrder[$by] = " BS.CODE ".$order." ";
					$additionalSelect["CODE"] = $arFields["CODE"]." AS CODE";
					break;
				case "active":
					$arSqlOrder[$by] = " BS.ACTIVE ".$order." ";
					$additionalSelect["ACTIVE"] = $arFields["ACTIVE"]." AS ACTIVE";
					break;
				case "left_margin":
					$arSqlOrder[$by] = " BS.LEFT_MARGIN ".$order." ";
					$additionalSelect["LEFT_MARGIN"] = $arFields["LEFT_MARGIN"]." AS LEFT_MARGIN";
					break;
				case "depth_level":
					$arSqlOrder[$by] = " BS.DEPTH_LEVEL ".$order." ";
					$additionalSelect["DEPTH_LEVEL"] = $arFields["DEPTH_LEVEL"]." AS DEPTH_LEVEL";
					break;
				case "sort":
					$arSqlOrder[$by] = " BS.SORT ".$order." ";
					$additionalSelect["SORT"] = $arFields["SORT"]." AS SORT";
					break;
				case "created":
					$arSqlOrder[$by] = " BS.DATE_CREATE ".$order." ";
					$additionalSelect["DATE_CREATE"] = $arFields["DATE_CREATE"]." AS DATE_CREATE";
					break;
				case "created_by":
					$arSqlOrder[$by] = " BS.CREATED_BY ".$order." ";
					$additionalSelect["CREATED_BY"] = $arFields["CREATED_BY"]." AS CREATED_BY";
					break;
				case "modified_by":
					$arSqlOrder[$by] = " BS.MODIFIED_BY ".$order." ";
					$additionalSelect["MODIFIED_BY"] = $arFields["MODIFIED_BY"]." AS MODIFIED_BY";
					break;
				default:
					if ($bIncCnt && $by == "element_cnt")
					{
						$arSqlOrder[$by] = " ELEMENT_CNT ".$order." ";
					}
					elseif (isset($obUserFieldsSql) && $s = $obUserFieldsSql->GetOrder($by))
					{
						$arSqlOrder[$by] = " ".$s." ".$order." ";
					}
					else
					{
						$by = "timestamp_x";
						$arSqlOrder[$by] = " BS.TIMESTAMP_X ".$order." ";
						$additionalSelect["TIMESTAMP_X"] = $arFields["TIMESTAMP_X"]." AS TIMESTAMP_X";
					}
			}
		}
		if (!empty($additionalSelect) && !empty($arSqlSelect))
		{
			foreach ($additionalSelect as $key => $value)
				$arSqlSelect[$key] = $value;
		}

		if(count($arSqlSelect))
			$sSelect = implode(",\n", $arSqlSelect);
		else
			$sSelect = "
				BS.*,
				B.LIST_PAGE_URL,
				B.SECTION_PAGE_URL,
				B.IBLOCK_TYPE_ID,
				B.CODE as IBLOCK_CODE,
				B.XML_ID as IBLOCK_EXTERNAL_ID,
				BS.XML_ID as EXTERNAL_ID,
				".$DB->DateToCharFunction("BS.TIMESTAMP_X")." as TIMESTAMP_X,
				".$DB->DateToCharFunction("BS.DATE_CREATE")." as DATE_CREATE
			";

		if(!$bIncCnt)
		{
			$strSelect = $sSelect.(isset($obUserFieldsSql)? $obUserFieldsSql->GetSelect(): "");
			$strSql = "
				FROM b_iblock_section BS
					INNER JOIN b_iblock B ON BS.IBLOCK_ID = B.ID
					".(isset($obUserFieldsSql)? $obUserFieldsSql->GetJoin("BS.ID"): "")."
				".(strlen($strProp1)>0?
					"	INNER JOIN b_iblock_section BSTEMP ON BSTEMP.IBLOCK_ID = BS.IBLOCK_ID
						LEFT JOIN b_iblock_section_element BSE ON BSE.IBLOCK_SECTION_ID=BSTEMP.ID
						LEFT JOIN b_iblock_element BE ON (BSE.IBLOCK_ELEMENT_ID=BE.ID
							AND ((BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL )
							AND BE.IBLOCK_ID = BS.IBLOCK_ID
					".($arFilter["CNT_ALL"]=="Y"?" OR BE.WF_NEW='Y' ":"").")
					".($arFilter["CNT_ACTIVE"]=="Y"?
						" AND BE.ACTIVE='Y'
						AND (BE.ACTIVE_TO >= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_TO IS NULL)
						AND (BE.ACTIVE_FROM <= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_FROM IS NULL)"
					:"").")
						".$strProp1." "
				:"")."
				WHERE 1=1
				".(strlen($strProp1)>0?
					"	AND BSTEMP.LEFT_MARGIN >= BS.LEFT_MARGIN
						AND BSTEMP.RIGHT_MARGIN <= BS.RIGHT_MARGIN "
				:""
				)."
				".$strSqlSearch."
			";
			$strGroupBy = "";
		}
		else
		{
			$strSelect = $sSelect.",COUNT(DISTINCT BE.ID) as ELEMENT_CNT".(isset($obUserFieldsSql)? $obUserFieldsSql->GetSelect(): "");
			$strSql = "
				FROM b_iblock_section BS
					INNER JOIN b_iblock B ON BS.IBLOCK_ID = B.ID
					".(isset($obUserFieldsSql)? $obUserFieldsSql->GetJoin("BS.ID"): "")."
				".($arFilter["ELEMENT_SUBSECTIONS"]=="N"?
					"	LEFT JOIN b_iblock_section_element BSE ON BSE.IBLOCK_SECTION_ID=BS.ID "
				:
					"	INNER JOIN b_iblock_section BSTEMP ON BSTEMP.IBLOCK_ID = BS.IBLOCK_ID
						LEFT JOIN b_iblock_section_element BSE ON BSE.IBLOCK_SECTION_ID=BSTEMP.ID "
				)."
					LEFT JOIN b_iblock_element BE ON (BSE.IBLOCK_ELEMENT_ID=BE.ID
						AND ((BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL )
						AND BE.IBLOCK_ID = BS.IBLOCK_ID
				".($arFilter["CNT_ALL"]=="Y"?" OR BE.WF_NEW='Y' ":"").")
				".($arFilter["CNT_ACTIVE"]=="Y"?
					" AND BE.ACTIVE='Y'
					AND (BE.ACTIVE_TO >= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_TO IS NULL)
					AND (BE.ACTIVE_FROM <= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_FROM IS NULL)"
				:"").")
					".$strProp1."
				WHERE 1=1
				".($arFilter["ELEMENT_SUBSECTIONS"]=="N"
				?
					"	"
				:
					"	AND BSTEMP.IBLOCK_ID = BS.IBLOCK_ID
						AND BSTEMP.LEFT_MARGIN >= BS.LEFT_MARGIN
						AND BSTEMP.RIGHT_MARGIN <= BS.RIGHT_MARGIN
						".($arFilter["CNT_ACTIVE"]=="Y"? "AND BSTEMP.GLOBAL_ACTIVE = 'Y'": "")."
					"
				)."
				".$strSqlSearch."
			";
			$strGroupBy = "GROUP BY BS.ID, B.ID";
		}

		if(count($arSqlOrder) > 0)
			$strSqlOrder = "\n\t\t\t\tORDER BY ".implode(", ", $arSqlOrder);
		else
			$strSqlOrder = "";

		if(is_array($arNavStartParams))
		{
			$nTopCount = intval($arNavStartParams["nTopCount"]);
			if($nTopCount > 0)
			{
				$res = $DB->Query($DB->TopSql(
					"SELECT DISTINCT ".$strSelect.$strSql.$strGroupBy.$strSqlOrder,
					$nTopCount
				));
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(DISTINCT BS.ID) as C ".$strSql);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->NavQuery("SELECT DISTINCT ".$strSelect.$strSql.$strGroupBy.$strSqlOrder, $res_cnt["C"], $arNavStartParams);
			}
		}
		else
		{
			$res = $DB->Query("SELECT DISTINCT ".$strSelect.$strSql.$strGroupBy.$strSqlOrder, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		$res = new CIBlockResult($res);
		if(isset($arFilter["IBLOCK_ID"]) && $arFilter["IBLOCK_ID"] > 0)
		{
			$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("IBLOCK_".$arFilter["IBLOCK_ID"]."_SECTION"));
			$res->SetIBlockTag($arFilter["IBLOCK_ID"]);
		}

		return $res;
	}
	///////////////////////////////////////////////////////////////////
	// Update list of sections w/o any events
	///////////////////////////////////////////////////////////////////
	protected function UpdateList($arFields, $arFilter = array())
	{
		global $DB, $USER;

		$strUpdate = $DB->PrepareUpdate("b_iblock_section", $arFields, "iblock", false, "BS");
		if ($strUpdate == "")
			return false;

		if(isset($arFilter["IBLOCK_ID"]) && $arFilter["IBLOCK_ID"] > 0)
		{
			$obUserFieldsSql = new CUserTypeSQL;
			$obUserFieldsSql->SetEntity("IBLOCK_".$arFilter["IBLOCK_ID"]."_SECTION", "BS.ID");
			$obUserFieldsSql->SetFilter($arFilter);
		}
		else
		{
			foreach($arFilter as $key => $val)
			{
				$res = CIBlock::MkOperationFilter($key);
				if(preg_match("/^UF_/", $res["FIELD"]))
				{
					trigger_error("arFilter parameter of the CIBlockSection::GetList contains user fields, but has no IBLOCK_ID field.", E_USER_WARNING);
					break;
				}
			}
		}

		$arJoinProps = array();
		$bJoinFlatProp = false;

		$arSqlSearch = CIBlockSection::GetFilter($arFilter);

		$bCheckPermissions = !array_key_exists("CHECK_PERMISSIONS", $arFilter) || $arFilter["CHECK_PERMISSIONS"]!=="N";
		$bIsAdmin = is_object($USER) && $USER->IsAdmin();
		if($bCheckPermissions && !$bIsAdmin)
			$arSqlSearch[] = CIBlockSection::_check_rights_sql($arFilter["MIN_PERMISSION"]);

		if(array_key_exists("PROPERTY", $arFilter))
		{
			$val = $arFilter["PROPERTY"];
			foreach($val as $propID=>$propVAL)
			{
				$res = CIBlock::MkOperationFilter($propID);
				$propID = $res["FIELD"];
				$cOperationType = $res["OPERATION"];
				if($db_prop = CIBlockProperty::GetPropertyArray($propID, CIBlock::_MergeIBArrays($arFilter["IBLOCK_ID"], $arFilter["IBLOCK_CODE"])))
				{

					$bSave = false;
					if(array_key_exists($db_prop["ID"], $arJoinProps))
						$iPropCnt = $arJoinProps[$db_prop["ID"]];
					elseif($db_prop["VERSION"]!=2 || $db_prop["MULTIPLE"]=="Y")
					{
						$bSave = true;
						$iPropCnt=count($arJoinProps);
					}

					if(!is_array($propVAL))
						$propVAL = Array($propVAL);

					if($db_prop["PROPERTY_TYPE"]=="N" || $db_prop["PROPERTY_TYPE"]=="G" || $db_prop["PROPERTY_TYPE"]=="E")
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "number", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE_NUM", $propVAL, "number", $cOperationType);
					}
					else
					{
						if($db_prop["VERSION"]==2 && $db_prop["MULTIPLE"]=="N")
						{
							$r = CIBlock::FilterCreate("FPS.PROPERTY_".$db_prop["ORIG_ID"], $propVAL, "string", $cOperationType);
							$bJoinFlatProp = $db_prop["IBLOCK_ID"];
						}
						else
							$r = CIBlock::FilterCreate("FPV".$iPropCnt.".VALUE", $propVAL, "string", $cOperationType);
					}

					if(strlen($r)>0)
					{
						if($bSave)
						{
							$db_prop["iPropCnt"] = $iPropCnt;
							$arJoinProps[$db_prop["ID"]] = $db_prop;
						}
						$arSqlSearch[] = $r;
					}
				}
			}
		}

		$strSqlSearch = "";
		foreach($arSqlSearch as $r)
			if(strlen($r)>0)
				$strSqlSearch .= "\n\t\t\t\tAND  (".$r.") ";

		if(isset($obUserFieldsSql))
		{
			$r = $obUserFieldsSql->GetFilter();
			if(strlen($r)>0)
				$strSqlSearch .= "\n\t\t\t\tAND (".$r.") ";
		}

		$strProp1 = "";
		foreach($arJoinProps as $propID=>$db_prop)
		{
			if($db_prop["VERSION"]==2)
				$strTable = "b_iblock_element_prop_m".$db_prop["IBLOCK_ID"];
			else
				$strTable = "b_iblock_element_property";
			$i = $db_prop["iPropCnt"];
			$strProp1 .= "
				LEFT JOIN b_iblock_property FP".$i." ON FP".$i.".IBLOCK_ID=B.ID AND
				".((int)$propID>0?" FP".$i.".ID=".(int)$propID." ":" FP".$i.".CODE='".$DB->ForSQL($propID, 200)."' ")."
				LEFT JOIN ".$strTable." FPV".$i." ON FP".$i.".ID=FPV".$i.".IBLOCK_PROPERTY_ID AND FPV".$i.".IBLOCK_ELEMENT_ID=BE.ID ";
		}
		if($bJoinFlatProp)
			$strProp1 .= "
				LEFT JOIN b_iblock_element_prop_s".$bJoinFlatProp." FPS ON FPS.IBLOCK_ELEMENT_ID = BE.ID
			";

		$strSql = "
			UPDATE
			b_iblock_section BS
				INNER JOIN b_iblock B ON BS.IBLOCK_ID = B.ID
				".(isset($obUserFieldsSql)? $obUserFieldsSql->GetJoin("BS.ID"): "")."
			".(strlen($strProp1)>0?
				"	INNER JOIN b_iblock_section BSTEMP ON BSTEMP.IBLOCK_ID = BS.IBLOCK_ID
					LEFT JOIN b_iblock_section_element BSE ON BSE.IBLOCK_SECTION_ID=BSTEMP.ID
					LEFT JOIN b_iblock_element BE ON (BSE.IBLOCK_ELEMENT_ID=BE.ID
						AND ((BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL )
						AND BE.IBLOCK_ID = BS.IBLOCK_ID
				".($arFilter["CNT_ALL"]=="Y"?" OR BE.WF_NEW='Y' ":"").")
				".($arFilter["CNT_ACTIVE"]=="Y"?
					" AND BE.ACTIVE='Y'
					AND (BE.ACTIVE_TO >= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_TO IS NULL)
					AND (BE.ACTIVE_FROM <= ".$DB->CurrentTimeFunction()." OR BE.ACTIVE_FROM IS NULL)"
				:"").")
					".$strProp1." "
			:"")."
			SET ".$strUpdate."
			WHERE 1=1
			".(strlen($strProp1)>0?
				"	AND BSTEMP.LEFT_MARGIN >= BS.LEFT_MARGIN
					AND BSTEMP.RIGHT_MARGIN <= BS.RIGHT_MARGIN "
			:""
			)."
			".$strSqlSearch."
		";

		return $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}
}