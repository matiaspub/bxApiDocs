<?
IncludeModuleLangFile(__FILE__);
// define("POSTING_TEMPLATE_DIR", substr(BX_PERSONAL_ROOT, 1)."/php_interface/subscribe/templates");


/**
 * <b>CPostingTemplate</b> - класс для работы с шаблонами генерации выпусков подписки. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostingtemplate/index.php
 * @author Bitrix
 */
class CPostingTemplate
{
	var $LAST_ERROR="";
	//Get list
	
	/**
	* <p>Метод возвращает список шаблонов.</p>
	*
	*
	* @return array <p>Возвращается массив относительных путей к подкаталогам
	* каталога <code>/bitrix/php_interface/subscribe/templates</code>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //get template directories
	* $arTemplates = <b>CPostingTemplate::GetList</b>();
	* foreach($arTemplates as $template_dir):
	* ?&gt;
	*     &lt;p&gt;&lt;?echo $template_dir?&gt;&lt;/p&gt;
	* &lt;?
	* endforeach;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostingtemplate/cpostingtemplate.getlist.php
	* @author Bitrix
	*/
	public static function GetList()
	{
		$arTemplates = array();
		$dir = $_SERVER["DOCUMENT_ROOT"]."/".POSTING_TEMPLATE_DIR;
		if(is_dir($dir) && ($dh = opendir($dir)))
		{
			while (($file = readdir($dh)) !== false)
				if(is_dir($dir."/".$file) && $file!="." && $file!="..")
					$arTemplates[]=POSTING_TEMPLATE_DIR."/".$file;
			closedir($dh);
		}
		return $arTemplates;
	}

	
	/**
	* <p>Метод возвращает шаблон по его идентификатору (относительному пути).</p>
	*
	*
	* @param string $path = "" Относительный путь к каталогу шаблона.
	*
	* @return array <p>Возвращается массив формируемый в файле description.php дополненный
	* элементом "path" равным относительному пути к каталогу шаблона. </p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arTemplate =
	* 	Array(
	* 	"NAME"=&gt;"Дайджест новостей",
	* 	"DESCRIPTION"=&gt;"Шаблон генерации дайджеста новостей."
	* 	);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostingtemplate/cpostingtemplate.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($path="")
	{
		global $MESS;
		if(!CPostingTemplate::IsExists($path))
			return false;
		$arTemplate = array();
		$strFileName= $_SERVER["DOCUMENT_ROOT"]."/".$path."/lang/".LANGUAGE_ID."/description.php";
		if(file_exists($strFileName)) include($strFileName);
		$strFileName= $_SERVER["DOCUMENT_ROOT"]."/".$path."/description.php";
		if(file_exists($strFileName)) include($strFileName);
		$arTemplate["PATH"] = $path;
		return $arTemplate;
	}

	
	/**
	* <p>Метод проверяет существование каталога шаблона.</p>
	*
	*
	* @param string $path = "" Относительный путь к каталогу шаблона.
	*
	* @return bool <p>true, если каталог шаблона существует, и false в противном случае. </p>
	* <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if(!CPostingTemplate::IsExists("bitrix/php_interface/subscribe/templates/news"))
	* 	echo "Указанный шаблон не существует.";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostingtemplate/cpostingtemplate.isexists.php
	* @author Bitrix
	*/
	public static function IsExists($path="")
	{
		if(substr($path, 0, strlen(POSTING_TEMPLATE_DIR)+1) !== POSTING_TEMPLATE_DIR."/")
			return false;

		$template = substr($path, strlen(POSTING_TEMPLATE_DIR)+1);
		if(
			strpos($template, "\0") !== false
			|| strpos($template, "\\") !== false
			|| strpos($template, "/") !== false
			|| strpos($template, "..") !== false
		)
		{
			return false;
		}

		return is_dir($_SERVER["DOCUMENT_ROOT"]."/".$path);
	}

	
	/**
	* <p>Метод выбирает шаблон для генерации выпуска рассылки в соответствии с расписанием.</p> <p>Сначала делается выборка всех рассылок отмеченных как активные и автоматические. Затем для каждой из них выполняется проверка на необходимость генерации выпуска. Как только найдена такая рассылка для нее вызывается метод CPostingTemplate::AddPosting и на этом функция Execute завершает свою работу.</p> <p>Этот метод предназначен для вызова из сценария cron'а или агента.</p>
	*
	*
	* @return string <p>Если была найдена хотя бы одна активная и автоматическая
	* рассылка, то возвращается строка для вызова из агента, иначе
	* возвращается пустая строка.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* #!/usr/bin/php
	* &lt;?php
	* //Здесь необходимо указать ваш DOCUMENT_ROOT!
	* $_SERVER["DOCUMENT_ROOT"] = "/opt/www/html";
	* $DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
	* define("NO_KEEP_STATISTIC", true);
	* define("NOT_CHECK_PERMISSIONS", true);
	* set_time_limit(0);
	* define("LANG", "ru");
	* require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	* if (CModule::IncludeModule("subscribe"))
	*     <b>CPostingTemplate::Execute</b>();
	* require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostingtemplate/cpostingtemplate.execute.php
	* @author Bitrix
	*/
	public static function Execute()
	{
		global $DB;

		$rubrics = CRubric::GetList(array(), array("ACTIVE"=>"Y", "AUTO"=>"Y"));
		$current_time = time();
		$time_of_exec = false;
		$result = "";
		while(($arRubric=$rubrics->Fetch()) && $time_of_exec===false)
		{
			if ($arRubric["LAST_EXECUTED"] == '')
				continue;

			$last_executed = MakeTimeStamp(ConvertDateTime($arRubric["LAST_EXECUTED"], "DD.MM.YYYY HH:MI:SS"), "DD.MM.YYYY HH:MI:SS");

			if ($last_executed <= 0)
				continue;

			//parse schedule
			$arDoM = CPostingTemplate::ParseDaysOfMonth($arRubric["DAYS_OF_MONTH"]);
			$arDoW = CPostingTemplate::ParseDaysOfWeek($arRubric["DAYS_OF_WEEK"]);
			$arToD = CPostingTemplate::ParseTimesOfDay($arRubric["TIMES_OF_DAY"]);
			if($arToD)
				sort($arToD, SORT_NUMERIC);
			//sdate = truncate(last_execute)
			$arSDate = localtime($last_executed);
			$sdate = mktime(0, 0, 0, $arSDate[4]+1, $arSDate[3], $arSDate[5]+1900);
			while($sdate < $current_time && $time_of_exec===false)
			{
				$arSDate = localtime($sdate);
				if($arSDate[6]==0) $arSDate[6]=7;
				//determine if date is good for execution
				if($arDoM)
				{
					$flag = array_search($arSDate[3], $arDoM);
					if($arDoW)
						$flag = array_search($arSDate[6], $arDoW);
				}
				elseif($arDoW)
					$flag = array_search($arSDate[6], $arDoW);
				else
					$flag=false;

				if($flag!==false && $arToD)
					foreach($arToD as $intToD)
					{
						if($sdate+$intToD >  $last_executed && $sdate+$intToD <= $current_time)
						{
							$time_of_exec = $sdate+$intToD;
							break;
						}
					}
				$sdate = mktime(0, 0, 0, date("m",$sdate), date("d",$sdate)+1, date("Y",$sdate));//next day
			}
			if($time_of_exec!==false)
			{
				$arRubric["START_TIME"] = ConvertTimeStamp($last_executed, "FULL");
				$arRubric["END_TIME"] = ConvertTimeStamp($time_of_exec, "FULL");
				$arRubric["SITE_ID"] = $arRubric["LID"];
				CPostingTemplate::AddPosting($arRubric);
			}
			$result = "CPostingTemplate::Execute();";
		}
		return $result;
	}

	
	/**
	* <p>Метод генерации выпуска на основании шаблона.</p> <p>Сначала ищется и подключается языковой файл шаблона. Поиск осуществляется по пути &lt;шаблон&gt;&gt;/lang/&lt;идентификатор языка сайта к которому привязана рассылка&gt;/template.php. Затем исполняется (подключается файл &lt;шаблон&gt;&gt;/lang/template.php) шаблон. Весь вывод шаблона становится телом письма, а массив возвращаемый из него становится полями выпуска.</p> <p>Если шаблон вернул не массив, а false, то выпуск не будет создан. При этом отметка времени о формировании будет сделана. <br></p> <p> </p> <p>Если в этом массиве есть элемент FILES, к выпуску добавляются вложения. Элементами этого массива должны быть массивы формата: <br></p> <pre>Array(<br> "name" =&gt; "название файла",<br> "size" =&gt; "размер",<br> "tmp_name" =&gt; "временный путь на сервере",<br> "type" =&gt; "тип загружаемого файла");</pre> Массив такого вида может быть сформирован с помощью функии <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/makefilearray.php">CFile::MakeFileArray</a>. <p></p> <p>А если в этом массиве есть элемент DO_NOT_SEND и его значение равно "Y", то выпуск не будет отправлен. Может быть использовано для отладки генерации или премодерации автоматических выпусков.</p>
	*
	*
	* @param array $arRubric  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">полей объекта
	* "Рассылка"</a>. и дополнительными полями: <br> SITE_ID - идентификатор
	* сайта рассылки; <br> START_TIME - время предыдущего запуска шаблона в
	* формате "FULL" текущего сайта; <br> END_TIME - время текущего запуска
	* шаблона в формате "FULL" текущего сайта.
	*
	* @return void <p>Нет.</p></bo<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $rubrics = CRubric::GetList(array(), array("ID"=&gt;$ID));<br>if($arRubric=$rubrics-&gt;Fetch())<br>{<br>    $arRubric["START_TIME"] = $START_TIME;<br>    $arRubric["END_TIME"] = $END_TIME;<br>    $arRubric["SITE_ID"] = $arRubric["LID"];<br>    CPostingTemplate::AddPosting($arRubric);<br>}<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostingtemplate/cpostingtemplate.addposting.php
	* @author Bitrix
	*/
	public static function AddPosting($arRubric)
	{
		global $DB, $USER, $MESS;
		if(!is_object($USER)) $USER = new CUser;
		//Include language file for template.php
		$rsSite = CSite::GetByID($arRubric["SITE_ID"]);
		$arSite = $rsSite->Fetch();
		$rsLang = CLanguage::GetByID($arSite["LANGUAGE_ID"]);
		$arLang = $rsLang->Fetch();

		$arFields=false;
		if(CPostingTemplate::IsExists($arRubric["TEMPLATE"]))
		{
			$strFileName= $_SERVER["DOCUMENT_ROOT"]."/".$arRubric["TEMPLATE"]."/lang/".$arSite["LANGUAGE_ID"]."/template.php";
			if(file_exists($strFileName))
				include($strFileName);
			//Execute template
			$strFileName= $_SERVER["DOCUMENT_ROOT"]."/".$arRubric["TEMPLATE"]."/template.php";
			if(file_exists($strFileName))
			{
				ob_start();
				$arFields = @include($strFileName);
				$strBody = ob_get_contents();
				ob_end_clean();
			}
		}
		$ID = false;
		//If there was an array returned then add posting
		if(is_array($arFields))
		{
			$arFields["BODY"] = $strBody;
			$cPosting=new CPosting;
			$arFields["AUTO_SEND_TIME"]=$arRubric["END_TIME"];
			$arFields["RUB_ID"]=array($arRubric["ID"]);
			$arFields["MSG_CHARSET"] = $arLang["CHARSET"];
			$ID = $cPosting->Add($arFields);
			if($ID)
			{
				if(array_key_exists("FILES", $arFields))
				{
					foreach($arFields["FILES"] as $arFile)
						$cPosting->SaveFile($ID, $arFile);
				}
				if(!array_key_exists("DO_NOT_SEND", $arFields) || $arFields["DO_NOT_SEND"]!="Y")
				{
					$cPosting->ChangeStatus($ID, "P");
					if(COption::GetOptionString("subscribe", "subscribe_auto_method")!=="cron")
						CAgent::AddAgent("CPosting::AutoSend(".$ID.",true,\"".$arRubric["LID"]."\");", "subscribe", "N", 0, $arRubric["END_TIME"], "Y", $arRubric["END_TIME"]);
				}
			}
		}
		//Update last execution time mark
		$strSql = "UPDATE b_list_rubric SET LAST_EXECUTED=".$DB->CharToDateFunction($arRubric["END_TIME"])." WHERE ID=".intval($arRubric["ID"]);
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $ID;
	}

	public static function ParseDaysOfMonth($strDaysOfMonth)
	{
		$arResult=array();
		if(strlen($strDaysOfMonth) > 0)
		{
			$arDoM = explode(",", $strDaysOfMonth);
			$arFound = array();
			foreach($arDoM as $strDoM)
			{
				if(preg_match("/^(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 31)
						return false;
					else
						$arResult[]=intval($arFound[1]);
				}
				elseif(preg_match("/^(\d{1,2})-(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 31 || intval($arFound[2]) < 1 || intval($arFound[2]) > 31 || intval($arFound[1]) >= intval($arFound[2]))
						return false;
					else
						for($i=intval($arFound[1]);$i<=intval($arFound[2]);$i++)
							$arResult[]=intval($i);
				}
				else
					return false;
			}
		}
		else
			return false;
		return $arResult;
	}

	public static function ParseDaysOfWeek($strDaysOfWeek)
	{
		if(strlen($strDaysOfWeek) <= 0)
			return false;

		$arResult = array();

		$arDoW = explode(",", $strDaysOfWeek);
		foreach($arDoW as $strDoW)
		{
			$arFound = array();
			if(
				preg_match("/^(\d)$/", trim($strDoW), $arFound)
				&& $arFound[1] >= 1
				&& $arFound[1] <= 7
			)
			{
				$arResult[]=intval($arFound[1]);
			}
			else
			{
				return false;
			}
		}

		return $arResult;
	}

	public static function ParseTimesOfDay($strTimesOfDay)
	{
		if(strlen($strTimesOfDay) <= 0)
			return false;

		$arResult = array();

		$arToD = explode(",", $strTimesOfDay);
		foreach($arToD as $strToD)
		{
			$arFound = array();
			if(
				preg_match("/^(\d{1,2}):(\d{1,2})$/", trim($strToD), $arFound)
				&& $arFound[1] <= 23
				&& $arFound[2] <= 59
			)
			{
				$arResult[]=intval($arFound[1])*3600+intval($arFound[2])*60;
			}
			else
			{
				return false;
			}
		}

		return $arResult;
	}
}
?>
