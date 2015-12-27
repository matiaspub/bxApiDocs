<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CRubric</b> - класс для работы с рубриками подписки. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/index.php
 * @author Bitrix
 */
class CRubric
{
	var $LAST_ERROR="";

	//Get list
	
	/**
	* <p>Метод возвращает список рассылок по фильтру.</p>
	*
	*
	* @param array $arrayaSort = Array() Массив, содержащий признак сортировки в виде наборов "название
	* поля"=&gt;"направление". <br><br> Название поля может принимать
	* значение:<br><ul> <li> <b>NAME</b> - название рассылки;</li> <li> <b>LID</b> -
	* идентификатор сайта;</li> <li> <b>ACT</b> - активность рассылки;</li> <li>
	* <b>SORT</b> - сортировка в списке;</li> <li> <b>ID</b> - идентификатор
	* рассылки;</li> <li> <b>AUTO</b> - флаг генерации;</li> <li> <b>VISIBLE</b> - публичность
	* рассылки;</li> <li> <b>LAST_EXECUTED</b> - время последней генерации.</li> </ul>
	* Направление сортировки может принимать значение: <ul> <li> <b>ASC</b> - по
	* возрастанию;</li> <li> <b>DESC</b> - по убыванию.</li> </ul> Пример: <pre
	* class="syntax"><code>array("LID"=&gt;"ASC", "NAME"=&gt;"DESC")</code></pre>
	*
	* @param array $arrayaFilter = Array() Массив, содержащий фильтр в виде наборов "название
	* поля"=&gt;"значение фильтра". <br><br> Название поля может принимать
	* значение:<br><ul> <li> <b>LID</b> - идентификатор сайта;</li> <li> <b>ACTIVE</b> -
	* активность рассылки;</li> <li> <b>AUTO</b> - флаг генерации;</li> <li> <b>VISIBLE</b> -
	* публичность рассылки;</li> <li> <b>ID</b> - идентификатор рассылки.</li> <li>
	* <b>NAME</b> - название рассылки.</li> </ul> Пример: <pre
	* class="syntax"><code>array("LID"=&gt;SITE_ID, "ACTIVE"=&gt;"Y")</code></pre>
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">поля объекта
	* "Рассылка"</a>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //get site's newsletter categories
	* $rub = <b>CRubric::GetList</b>(array("SORT"=&gt;"ASC", "NAME"=&gt;"ASC"), array("ACTIVE"=&gt;"Y", "LID"=&gt;LANG));
	* while($rub-&gt;ExtractFields("r_")):
	* ?&gt;
	*     &lt;input type="checkbox" name="sf_RUB_ID[]" value="&lt;?echo $r_ID?&gt;"&gt;&lt;?echo $r_NAME?&gt;&lt;br&gt;
	* &lt;?
	* endwhile;
	* ?&gt;
	* 
	* &lt;?
	* // Вывод рубрик можно производить таким способом
	* $arOrder = Array("SORT"=&gt;"ASC", "NAME"=&gt;"ASC"); 
	* $arFilter = Array("ACTIVE"=&gt;"Y", "LID"=&gt;LANG); 
	* $rsRubric = CRubric::GetList($arOrder, $arFilter); 
	* $arRubrics = array(); 
	* while($arRubric = $rsRubric-&gt;GetNext()) 
	* { 
	*  $arResult["RUBRIC_LIST"][] = $arRubric; 
	* } 
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.getlist.php
	* @author Bitrix
	*/
	public static function GetList($aSort=array(), $aFilter=array())
	{
		global $DB;

		$arFilter = array();
		foreach($aFilter as $key=>$val)
		{
			if(strlen($val)<=0)
				continue;

			$key = strtoupper($key);
			switch($key)
			{
				case "ID":
				case "ACTIVE":
				case "VISIBLE":
				case "LID":
				case "AUTO":
				case "CODE":
					$arFilter[] = "R.".$key." = '".$DB->ForSql($val)."'";
					break;
				case "NAME":
					$arFilter[] = "R.NAME like '%".$DB->ForSql($val)."%'";
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"? "DESC": "ASC");
			$key = strtoupper($key);

			switch($key)
			{
				case "ID":
				case "NAME":
				case "SORT":
				case "LAST_EXECUTED":
				case "VISIBLE":
				case "LID":
				case "AUTO":
				case "CODE":
					$arOrder[] = "R.".$key." ".$ord;
					break;
				case "ACT":
					$arOrder[] = "R.ACTIVE ".$ord;
					break;
			}
		}
		if(count($arOrder) == 0)
			$arOrder[] = "R.ID DESC";
		$sOrder = "\nORDER BY ".implode(", ",$arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				R.ID
				,R.NAME
				,R.CODE
				,R.SORT
				,R.LID
				,R.ACTIVE
				,R.DESCRIPTION
				,R.AUTO
				,R.VISIBLE
				,".$DB->DateToCharFunction("R.LAST_EXECUTED", "FULL")." AS LAST_EXECUTED
				,R.FROM_FIELD
				,R.DAYS_OF_MONTH
				,R.DAYS_OF_WEEK
				,R.TIMES_OF_DAY
				,R.TEMPLATE
			FROM
				b_list_rubric R
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	//Get by ID
	
	/**
	* <p>Метод выбирает одну рассылку по ее идентификатору.</p>
	*
	*
	* @param int $ID  Идентификатор рассылки.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">поля объекта
	* "Рассылка"</a>.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* if($ID&gt;0)
	* {
	*     $rubric = <b>CRubric::GetByID</b>($ID);
	*     if($rubric-&gt;ExtractFields("str_"))
	*         echo $str_NAME;
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT
				R.*
				,".$DB->DateToCharFunction("R.LAST_EXECUTED", "FULL")." AS LAST_EXECUTED
			FROM b_list_rubric R
			WHERE R.ID = ".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	//Count of subscribers
	
	/**
	* <p>Метод возвращает количество подписчиков на указанную рассылку. Учитываются как подтвержденные, так и неподтвержденные подписчики.</p>
	*
	*
	* @param int $ID  Идентификатор рассылки.
	*
	* @return int <p>целое число, равное количеству подписчиков рассылки. Если
	* подписка с заданным идентификатором не существует, то
	* возвращается 0.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* //get site's newsletter categories
	* $rub = CRubric::GetList(array("SORT"=&gt;"ASC", "NAME"=&gt;"ASC"), array("ACTIVE"=&gt;"Y", "LID"=&gt;LANG));
	* while($rub-&gt;ExtractFields("r_")):
	* ?&gt;
	*     &lt;input type="checkbox" name="sf_RUB_ID[]" value="&lt;?echo $r_ID?&gt;"&gt;
	*     &lt;?echo $r_NAME?&gt; (&lt;?echo <b>CRubric::GetSubscriptionCount</b>($r_ID);?&gt;)&lt;br&gt;
	* &lt;?
	* endwhile;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.getsubscriptioncount.php
	* @author Bitrix
	*/
	public static function GetSubscriptionCount($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT COUNT('x') AS CNT
			FROM b_subscription_rubric SR
			WHERE SR.LIST_RUBRIC_ID = ".$ID."
		";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res_arr = $res->Fetch())
			return intval($res_arr["CNT"]);
		else
			return 0;
	}


	// delete
	
	/**
	* <p>Метод удаляет рассылку.</p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	*
	*
	* @param int $ID  Идентификатор рассылки.
	*
	* @return mixed <p>В случае успешного удаления возвращается результат типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. В противном
	* случает возвращается false.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* if (($res = <b>CRubric::Delete</b>($ID)) &amp;&amp;
	*      $res-&gt;AffectedRowsCount() &lt; 1 ||
	*      $res == false)
	*     echo "Error";
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);

		$DB->StartTransaction();

		$res = $DB->Query("DELETE FROM b_subscription_rubric WHERE LIST_RUBRIC_ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res)
			$res = $DB->Query("DELETE FROM b_posting_rubric WHERE LIST_RUBRIC_ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res)
			$res = $DB->Query("DELETE FROM b_list_rubric WHERE ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$DB->Commit();
		else
			$DB->Rollback();

		return $res;
	}

	public static function OnBeforeLangDelete($lang)
	{
		global $DB, $APPLICATION;
		$rs = $DB->Query("SELECT count(*) C FROM b_list_rubric WHERE LID='".$DB->ForSql($lang, 2)."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] > 0)
		{
			$APPLICATION->ThrowException(GetMessage("class_rub_err_exists", array("#COUNT#"=>$ar["C"])));
			return false;
		}
		else
		{
			return true;
		}
	}

	//check fields before writing
	public function CheckFields($arFields)
	{
		global $DB;
		$this->LAST_ERROR = "";
		$aMsg = array();

		if(strlen($arFields["NAME"]) == 0)
			$aMsg[] = array("id"=>"NAME", "text"=>GetMessage("class_rub_err_name"));
		if(strlen($arFields["LID"]) > 0)
		{
			$r = CLang::GetByID($arFields["LID"]);
			if(!$r->Fetch())
				$aMsg[] = array("id"=>"LID", "text"=>GetMessage("class_rub_err_lang"));
		}
		else
			$aMsg[] = array("id"=>"LID", "text"=>GetMessage("class_rub_err_lang2"));
		if(strlen($arFields["DAYS_OF_MONTH"]) > 0)
		{
			$arDoM = explode(",", $arFields["DAYS_OF_MONTH"]);
			$arFound = array();
			foreach($arDoM as $strDoM)
			{
				if(preg_match("/^(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 31)
					{
						$aMsg[] = array("id"=>"DAYS_OF_MONTH", "text"=>GetMessage("class_rub_err_dom"));
						break;
					}
				}
				elseif(preg_match("/^(\d{1,2})-(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 31 || intval($arFound[2]) < 1 || intval($arFound[2]) > 31 || intval($arFound[1]) >= intval($arFound[2]))
					{
						$aMsg[] = array("id"=>"DAYS_OF_MONTH", "text"=>GetMessage("class_rub_err_dom"));
						break;
					}
				}
				else
				{
					$aMsg[] = array("id"=>"DAYS_OF_MONTH", "text"=>GetMessage("class_rub_err_dom2"));
					break;
				}
			}
		}
		if(strlen($arFields["DAYS_OF_WEEK"]) > 0)
		{
			$arDoW = explode(",", $arFields["DAYS_OF_WEEK"]);
			$arFound = array();
			foreach($arDoW as $strDoW)
			{
				if(preg_match("/^(\d)$/", trim($strDoW), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 7)
					{
						$aMsg[] = array("id"=>"DAYS_OF_WEEK", "text"=>GetMessage("class_rub_err_dow"));
						break;
					}
				}
				else
				{
					$aMsg[] = array("id"=>"DAYS_OF_WEEK", "text"=>GetMessage("class_rub_err_dow2"));
					break;
				}
			}
		}
		if(strlen($arFields["TIMES_OF_DAY"]) > 0)
		{
			$arToD = explode(",", $arFields["TIMES_OF_DAY"]);
			$arFound = array();
			foreach($arToD as $strToD)
			{
				if(preg_match("/^(\d{1,2}):(\d{1,2})$/", trim($strToD), $arFound))
				{
					if(intval($arFound[1]) > 23 || intval($arFound[2]) > 59)
					{
						$aMsg[] = array("id"=>"TIMES_OF_DAY", "text"=>GetMessage("class_rub_err_tod"));
						break;
					}
				}
				else
				{
					$aMsg[] = array("id"=>"TIMES_OF_DAY", "text"=>GetMessage("class_rub_err_tod2"));
					break;
				}
			}
		}
		if(strlen($arFields["TEMPLATE"])>0 && !CPostingTemplate::IsExists($arFields["TEMPLATE"]))
			$aMsg[] = array("id"=>"TEMPLATE", "text"=>GetMessage("class_rub_err_wrong_templ"));
		if($arFields["AUTO"]=="Y")
		{
			if((strlen($arFields["FROM_FIELD"]) < 3) || !check_email($arFields["FROM_FIELD"]))
				$aMsg[] = array("id"=>"FROM_FIELD", "text"=>GetMessage("class_rub_err_email"));
			if(strlen($arFields["DAYS_OF_MONTH"])+strlen($arFields["DAYS_OF_WEEK"]) <= 0)
				$aMsg[] = array("id"=>"DAYS_OF_MONTH", "text"=>GetMessage("class_rub_err_days_missing"));
			if(strlen($arFields["TIMES_OF_DAY"]) <= 0)
				$aMsg[] = array("id"=>"TIMES_OF_DAY", "text"=>GetMessage("class_rub_err_times_missing"));
			if(strlen($arFields["TEMPLATE"]) <= 0)
				$aMsg[] = array("id"=>"TEMPLATE", "text"=>GetMessage("class_rub_err_templ_missing"));
			if(is_set($arFields, "FROM_FIELD") && strlen($arFields["FROM_FIELD"])<=0)
				$aMsg[] = array("id"=>"FROM_FIELD", "text"=>GetMessage("class_rub_err_from"));
			if(strlen($arFields["LAST_EXECUTED"])<=0)
				$aMsg[] = array("id"=>"LAST_EXECUTED", "text"=>GetMessage("class_rub_err_le_missing"));
			elseif(is_set($arFields, "LAST_EXECUTED") && $arFields["LAST_EXECUTED"]!==false && $DB->IsDate($arFields["LAST_EXECUTED"], false, false, "FULL")!==true)
				$aMsg[] = array("id"=>"LAST_EXECUTED", "text"=>GetMessage("class_rub_err_le_wrong"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}
		return true;
	}

	//add
	
	/**
	* <p>Метод добавляет рассылку. При этом если рассылка активна и является автоматической, то в случае соответствующей настроки модуля (метод генерации не cron) добляется агент для генерации выпусков.</p>
	*
	*
	* @param array $arFields  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">полей объекта
	* "Рассылка"</a>.
	*
	* @return int <p>В случае успешного добавления возвращается ID рассылки. В
	* противном случает возвращается false, и переменная класса LAST_ERROR
	* содержит сообщение об ошибке.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $rubric = new CRubric;
	* $arFields = Array(
	*     "ACTIVE" =&gt; ($ACTIVE &lt;&gt; "Y"? "N":"Y"),
	*     "NAME" =&gt; $NAME,
	*     "SORT" =&gt; $SORT,
	*     "DESCRIPTION" =&gt; $DESCRIPTION,
	*     "LID" =&gt; $LID
	* );
	* $ID = <b>$rubric-&gt;Add</b>($arFields);
	* if($ID == false)
	*     echo $rubric-&gt;LAST_ERROR;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields($arFields))
			return false;

		$ID = $DB->Add("b_list_rubric", $arFields);

		if($ID>0 && $arFields["ACTIVE"]=="Y" && $arFields["AUTO"]=="Y" && COption::GetOptionString("subscribe", "subscribe_template_method")!=="cron")
				CAgent::AddAgent("CPostingTemplate::Execute();", "subscribe", "N", COption::GetOptionString("subscribe", "subscribe_template_interval"));
		return $ID;
	}

	//update
	
	/**
	* <p>Метод модифицирует рассылку.</p>
	*
	*
	* @param int $ID  Идентификатор рассылки.
	*
	* @param array $arFields  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">полей объекта
	* "Рассылка"</a>.
	*
	* @return bool <p>В случае успешного изменения возвращается true. В противном
	* случает возвращается false, и переменная класса LAST_ERROR содержит
	* сообщение об ошибке.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $rubric = new CRubric;
	* $arFields = Array(
	*     "ACTIVE" =&gt; ($ACTIVE &lt;&gt; "Y"? "N":"Y"),
	*     "NAME" =&gt; $NAME,
	*     "SORT" =&gt; $SORT,
	*     "DESCRIPTION" =&gt; $DESCRIPTION,
	*     "LID" =&gt; $LID
	* );
	* if($ID&gt;0)
	* {
	*     if(!$rubric-&gt;Update($ID, $arFields))
	*         echo $rubric-&gt;LAST_ERROR;
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if(!$this->CheckFields($arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_list_rubric", $arFields);
		if($strUpdate!="")
		{
			$strSql = "UPDATE b_list_rubric SET ".$strUpdate." WHERE ID=".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($ID>0 && $arFields["ACTIVE"]=="Y" && $arFields["AUTO"]=="Y" && COption::GetOptionString("subscribe", "subscribe_template_method")!=="cron")
					CAgent::AddAgent("CPostingTemplate::Execute();", "subscribe", "N", COption::GetOptionString("subscribe", "subscribe_template_interval"));
		}
		return true;
	}
}
?>