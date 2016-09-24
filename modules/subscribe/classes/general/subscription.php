<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CSubscriptionGeneral</b> - класс для работы с подписками на рассылки.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/index.php
 * @author Bitrix
 */
class CSubscriptionGeneral
{
	var $LAST_ERROR="";
	var $LAST_MESSAGE="";

	
	/**
	* <p>Метод выбирает список подписок (подписчиков) по фильтру. Метод статический.</p>   <p>Если параметр aFilter не задан, то сортировки будет выполнена в порядке убывания поля ID.</p>   <p> </p>
	*
	*
	* @param array $aSort = Array() Массив, содержащий признак сортировки в виде наборов "название
	* поля"=&gt;"направление".          <br><br>        	Название поля может
	* принимать значение:          <br><ul> <li> <b>ID</b> - идентификатор
	* подписки;</li>          	            <li> <b>DATE_INSERT</b> - дата добавления;</li>          	   
	*         <li> <b>DATE_UPDATE</b> - дата изменения;</li>          	            <li> <b>DATE_CONFIRM</b> -
	* дата подтверждения;</li>          	            <li> <b>ACT</b> - флаг активности;</li>   
	*       	            <li> <b>CONF</b> - флаг подтверждения;</li>          	            <li> <b>EMAIL</b>
	* - адрес подписки;</li>          	            <li> <b>FMT</b> - формат подписки;</li>         
	* 	            <li> <b>USER</b> - идентификатор пользователя.</li>          	            <li>
	* <b>CONFIRM_CODE</b> - код подтверждения подписки.</li>          	           
	* <li>Направление сортировки может принимать значение:</li>          	         
	*   <li> <b>ASC</b> - по возрастанию;</li>          	            <li> <b>DESC</b> - по
	* убыванию.</li>          </ul>        	Например: <code>array("EMAIL"=&gt;"ASC", "ACT"=&gt;"DESC")</code>
	*
	* @param array $arFilter = Array() Массив, содержащий фильтр в виде наборов "название
	* поля"=&gt;"значение фильтра".          <br><br>        	Название поля может
	* принимать значение:          <br><ul> <li> <sup>1</sup><b>ID</b> - идентификатор
	* подписки;</li>          	            <li> <sup>1</sup><b>EMAIL</b> - адрес;</li>          	            <li>
	* <b>UPDATE_1</b> - дата модификации (начало периода);</li>          	            <li>
	* <b>UPDATE_2</b> - дата модификации (конец периода);</li>          	            <li>
	* <b>INSERT_1</b> - дата добавления (начало периода);</li>          	            <li>
	* <b>INSERT_2</b> - дата добавления (конец периода);</li>          	            <li>
	* <sup>2</sup><b>USER</b> - пользователь;</li>          	            <li> <sup>1</sup><b>USER_ID</b> -
	* идентификатор пользователя;</li>          	            <li> <b>ANONYMOUS</b> - флаг
	* анонимности подписки;</li>          	            <li> <b>CONFIRMED</b> - флаг
	* подтверждения подписки;</li>          	            <li> <b>ACTIVE</b> - флаг
	* активности подписки;</li>          	            <li> <b>FORMAT</b> - формат
	* подписки;</li>          	            <li> <b>RUBRIC</b> - рубрики подписки в виде
	* массива идентификаторов или строки, сформированной по правилам
	* логики фильтра.</li>          </ul>        	Например: <code>array("CONFIRMED"=&gt;"Y",
	* "ACTIVE"=&gt;"Y")</code>  <br><sup>1</sup> - допускаются <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложные условия</a>         
	* <br><sup>2</sup> - допускаются <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложные условия</a> по полям
	* пользователя ID, LOGIN, NAME, LAST_NAME
	*
	* @param array $arNavStartParams = false Параметры для постраничной навигации и ограничения количества
	* выводимых элементов. массив вида "Название
	* параметра"=&gt;"Значение", где название параметра          <br><br>       
	* "bShowAll" - разрешить вывести все элементы при постраничной
	* навигации          <br>        "iNumPage" - номер страницы при постраничной
	* навигации          <br>        "nPageSize" - количество элементов на странице
	* при постраничной навигации          <br><br>        Необязательное. По
	* умолчанию false - не ограничивать выборку.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongeneralfields.php">поля
	* объекта "Подписка"</a>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //в какие рубрики отправлять<br>$aPostRub = array();<br>$post_rub = CPosting::GetRubricList($post_arr["ID"]);<br>while($post_rub_arr = $post_rub-&gt;Fetch())<br>    $aPostRub[] = $post_rub_arr["ID"];<br><br>//активные и подтвержденные адреса, подписанные на рубрики<br>$subscr = <b>CSubscription::GetList</b>(<br>    array("ID"=&gt;"ASC"),<br>    array("RUBRIC"=&gt;$aPostRub, "CONFIRMED"=&gt;"Y", "ACTIVE"=&gt;"Y",<br>        "FORMAT"=&gt;$post_arr["SUBSCR_FORMAT"], "EMAIL"=&gt;$post_arr["EMAIL_FILTER"])<br>);<br>while(($subscr_arr = $subscr-&gt;Fetch()))<br>    $aEmail[] = $subscr_arr["EMAIL"];<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongetlist.php
	* @author Bitrix
	*/
	public static function GetList($aSort=Array(), $arFilter=Array(), $arNavStartParams=false)
	{
		global $DB;
		$arSqlSearch = Array();
		$from1 = "";
		if(is_array($arFilter))
		{
			foreach($arFilter as $key => $val)
			{
				if(!is_array($val))
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				switch(strtoupper($key))
				{
				case "ID":
					$arSqlSearch[] = GetFilterQuery("S.ID",$val,"N");
					break;
				case "EMAIL":
					$arSqlSearch[] = GetFilterQuery("S.EMAIL",$val,"Y",array("@", ".", "_"));
					break;
				case "UPDATE_1":
					$arSqlSearch[] = "S.DATE_UPDATE>=".$DB->CharToDateFunction($val);
					break;
				case "UPDATE_2":
					$arSqlSearch[] = "S.DATE_UPDATE<=".$DB->CharToDateFunction($val." 23:59:59");
					break;
				case "INSERT_1":
					$arSqlSearch[] = "S.DATE_INSERT>=".$DB->CharToDateFunction($val);
					break;
				case "INSERT_2":
					$arSqlSearch[] = "S.DATE_INSERT<=".$DB->CharToDateFunction($val." 23:59:59");
					break;
				case "USER":
					$arSqlSearch[] = GetFilterQuery("U.ID, U.LOGIN, U.NAME, U.LAST_NAME",$val);
					break;
				case "USER_ID":
					$arSqlSearch[] = GetFilterQuery("S.USER_ID",$val,"N");
					break;
				case "ANONYMOUS":
					$arSqlSearch[] = ($val=="Y") ? "(S.USER_ID = 0 or S.USER_ID is null)" : "(S.USER_ID > 0 and S.USER_ID is not null)";
					break;
				case "CONFIRMED":
					$arSqlSearch[] = ($val=="Y") ? "S.CONFIRMED='Y'" : "S.CONFIRMED='N'";
					break;
				case "ACTIVE":
					$arSqlSearch[] = ($val=="Y") ? "S.ACTIVE='Y'" : "S.ACTIVE='N'";
					break;
				case "FORMAT":
					$arSqlSearch[] = ($val=="text") ? "S.FORMAT='text'" : "S.FORMAT='html'";
					break;
				case "RUBRIC":
				case "RUBRIC_MULTI":
				case "DISTRIBUTION":
					if(is_array($val))
						$val = implode(" | ",$val);
					if(strlen($val)>0)
					{
						$from1 = " INNER JOIN b_subscription_rubric SR ON (SR.SUBSCRIPTION_ID=S.ID) ";
						$arSqlSearch[] = GetFilterQuery("SR.LIST_RUBRIC_ID", $val, "N");
					}
					else
					{
						$arSqlSearch[] = " 1=0 ";
					}
					break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$arOrder = array();
		foreach($aSort as $by => $ord)
		{
			$by = strtoupper($by);
			$ord = (strtoupper($ord) <> "ASC"? "DESC": "ASC");
			switch($by)
			{
				case "ID":		$arOrder[$by] = "S.ID ".$ord; break;
				case "DATE_INSERT":	$arOrder[$by] = "S.DATE_INSERT ".$ord; break;
				case "DATE_UPDATE":	$arOrder[$by] = "S.DATE_UPDATE ".$ord; break;
				case "DATE_CONFIRM":	$arOrder[$by] = "S.DATE_CONFIRM ".$ord; break;
				case "ACT":		$arOrder[$by] = "S.ACTIVE ".$ord; break;
				case "CONF":		$arOrder[$by] = "S.CONFIRMED ".$ord; break;
				case "EMAIL":		$arOrder[$by] = "S.EMAIL ".$ord; break;
				case "FMT":		$arOrder[$by] = "S.FORMAT ".$ord; break;
				case "USER":		$arOrder[$by] = "S.USER_ID ".$ord; break;
				case "CONFIRM_CODE":	$arOrder[$by] = "S.CONFIRM_CODE ".$ord; break;
			}
		}
		if(count($arOrder) <= 0)
			$arOrder["ID"] = "S.ID DESC";

		if(is_array($arNavStartParams))
		{

			$strSql = "
				SELECT count(".($from1 <> ""? "DISTINCT S.ID": "'x'").") as C
				FROM
					b_subscription S
					LEFT JOIN b_user U ON (S.USER_ID=U.ID)
					$from1
				WHERE
				".$strSqlSearch;

			$res_cnt = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$res_cnt = $res_cnt->Fetch();
			$cnt = $res_cnt["C"];

			$strSql = "
				SELECT
					S.ID, S.USER_ID, S.ACTIVE, S.EMAIL, S.FORMAT, S.CONFIRM_CODE, S.CONFIRMED,
					".$DB->DateToCharFunction("S.DATE_UPDATE")." DATE_UPDATE,
					".$DB->DateToCharFunction("S.DATE_INSERT")." DATE_INSERT,
					".$DB->DateToCharFunction("S.DATE_CONFIRM")." DATE_CONFIRM,
					U.LOGIN USER_LOGIN,
					U.NAME USER_NAME,
					U.LAST_NAME USER_LAST_NAME
				FROM
					b_subscription S
					LEFT JOIN b_user U ON (S.USER_ID=U.ID)
					$from1
				WHERE
				$strSqlSearch
				".($from1 <> ""?
					"GROUP BY S.ID, S.USER_ID, S.ACTIVE, S.EMAIL, S.FORMAT, S.CONFIRM_CODE, S.CONFIRMED, S.DATE_CONFIRM, S.DATE_UPDATE, S.DATE_INSERT, U.LOGIN, U.NAME, U.LAST_NAME":
					""
				)."
				ORDER BY ".implode(", ", $arOrder);

			$res = new CDBResult();
			$res->NavQuery($strSql, $cnt, $arNavStartParams);
			$res->is_filtered = (IsFiltered($strSqlSearch));

			return $res;
		}
		else
		{
			$strSql = "
				SELECT
					S.ID, S.USER_ID, S.ACTIVE, S.EMAIL, S.FORMAT, S.CONFIRM_CODE, S.CONFIRMED,
					".$DB->DateToCharFunction("S.DATE_UPDATE")." DATE_UPDATE,
					".$DB->DateToCharFunction("S.DATE_INSERT")." DATE_INSERT,
					".$DB->DateToCharFunction("S.DATE_CONFIRM")." DATE_CONFIRM,
					U.LOGIN USER_LOGIN,
					U.NAME USER_NAME,
					U.LAST_NAME USER_LAST_NAME
				FROM
					b_subscription S
					LEFT JOIN b_user U ON (S.USER_ID=U.ID)
					$from1
				WHERE
				$strSqlSearch
				".($from1 <> ""?
					"GROUP BY S.ID, S.USER_ID, S.ACTIVE, S.EMAIL, S.FORMAT, S.CONFIRM_CODE, S.CONFIRMED, S.DATE_CONFIRM, S.DATE_UPDATE, S.DATE_INSERT, U.LOGIN, U.NAME, U.LAST_NAME":
					""
				)."
				ORDER BY ".implode(", ", $arOrder);

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$res->is_filtered = (IsFiltered($strSqlSearch));

			return $res;
		}
	}

	//list of subscribed categories
	
	/**
	* <p>Метод возвращает выборку рассылок, на которые подписан данный адрес. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны некоторые <a
	* href="../crubric/crubric.fields.php">поля объекта "Рассылка"</a>. А именно: </p><ul> <li>ID -
	* Идентификатор рассылки;</li>     <li>NAME - Название рассылки;</li>     <li>SORT -
	* Сортировка в списке;</li>     <li>LID - Идентификатор сайта;</li>     <li>ACTIVE -
	* Признак активности рассылки (Y/N).</li>   <li>VISIBLE - Выводить в списке
	* публичных рассылок (Y/N).     <br> </li>  </ul><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //array of subscribed categories<br>function GetRubricArray($ID)<br>{<br>    $aSubscrRub = array();<br>    if($ID&gt;0)<br>    {<br>        $subscr_rub = <b>CSubscription::GetRubricList</b>($ID);<br>        while($subscr_rub_arr = $subscr_rub-&gt;Fetch())<br>            $aSubscrRub[] = $subscr_rub_arr["ID"];<br>    }<br>    return $aSubscrRub;<br>}<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongetrubriclist.php
	* @author Bitrix
	*/
	public static function GetRubricList($ID)
	{
		global $DB;
		$ID = intval($ID);
		$strSql =
			"SELECT R.ID, R.NAME, R.SORT, R.LID, R.ACTIVE, R.VISIBLE ".
			"FROM b_list_rubric R, b_subscription_rubric SR ".
			"WHERE R.ID=SR.LIST_RUBRIC_ID AND SR.SUBSCRIPTION_ID='".$ID."' ".
			"ORDER BY R.LID, R.SORT, R.NAME ";
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	//array of subscribed categories
	
	/**
	* <p>Метод возвращает массив идентификаторов рассылок, на которые подписан данный адрес. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @return array <p>Массив целых чисел, идентификаторы рассылок.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* //shows all subscription categories and checks subscribed one
	* $aSubscrRub = <b>CSubscription::GetRubricArray</b>($ID);
	* $rub = CRubric::GetList(
	*     array("LID"=&gt;"ASC", "SORT"=&gt;"ASC", "NAME"=&gt;"ASC"),
	*     array("ACTIVE"=&gt;"Y", "LID"=&gt;LANG)
	* );
	* while($rub-&gt;ExtractFields("r_")):
	*     $bChecked = in_array($r_ID, $aSubscrRub);
	* ?&gt;
	* &lt;input type="checkbox"
	*        name="RUB_ID[]"
	*        value="&lt;?echo $r_ID?&gt;"&lt;?if($bChecked) echo " checked"?&gt;&gt;
	* &lt;?echo $r_NAME?&gt;&lt;br&gt;
	* &lt;?
	* endwhile;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongetrubricarray.php
	* @author Bitrix
	*/
	public static function GetRubricArray($ID)
	{
		$ID = intval($ID);
		$aSubscrRub = array();
		if($ID>0)
		{
			$subscr_rub = CSubscription::GetRubricList($ID);
			while($subscr_rub_arr = $subscr_rub->Fetch())
				$aSubscrRub[] = $subscr_rub_arr["ID"];
		}
		return $aSubscrRub;
	}

	//subscription of current user from cookies
	
	/**
	* <p>Метод возвращает массив полей подписки текущего пользователя.  Подписка определяется по Email, сохраненному в куках посетителя,  либо по Email авторизованного пользователя.  Метод статический.</p>
	*
	*
	* @return array <p>Возвращается массив <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongeneralfields.php">полей
	* объекта "Подписка"</a>. Если подписка посетителя не найдена, то
	* возвращается массив array("ID"=&gt;0, "EMAIL"=&gt;"").</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //get current user subscription from cookies
	* $aSubscr = <b>CSubscription::GetUserSubscription</b>();
	* 
	* //get user's newsletter categories
	* $aSubscrRub = CSubscription::GetRubricArray(intval($aSubscr["ID"]));
	* 
	* //show subscription form
	* //.....
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongetusersubscription.php
	* @author Bitrix
	*/
	public static function GetUserSubscription()
	{
		global $USER;
		$email_cookie = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_SUBSCR_EMAIL";

		$subscr_EMAIL = (strlen($_COOKIE[$email_cookie]) > 0? $_COOKIE[$email_cookie] : $USER->GetParam("EMAIL"));
		if($subscr_EMAIL <> "")
		{
			$subscr = CSubscription::GetByEmail($subscr_EMAIL, intval($USER->GetID()));
			if(($subscr_arr = $subscr->Fetch()))
				return $subscr_arr;
		}
		return array("ID"=>0, "EMAIL"=>"");
	}

	//get by ID
	
	/**
	* <p>Метод выбирает подписку по ее идентификатору. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongeneralfields.php">поля
	* объекта "Подписка"</a>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //new or existing subscription?
	* //ID==0 indicates new subscription
	* if(strlen($sf_EMAIL) &gt; 0 || $ID &gt; 0)
	* {
	*     if($ID &gt; 0)
	*         $subscription = <b>CSubscription::GetByID</b>($ID);
	*     else
	*         $subscription = CSubscription::GetByEmail($sf_EMAIL);
	* 
	*     if($subscription-&gt;ExtractFields("str_"))
	*         $ID = (integer)$str_ID;
	*     else
	*         $ID=0;
	* }
	* else
	*     $ID = 0;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongetbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql =
			"SELECT S.*, ".
			"	".$DB->DateToCharFunction("S.DATE_UPDATE", "FULL")." AS DATE_UPDATE, ".
			"	".$DB->DateToCharFunction("S.DATE_INSERT", "FULL")." AS DATE_INSERT, ".
			"	".$DB->DateToCharFunction("S.DATE_CONFIRM", "FULL")." AS DATE_CONFIRM ".
			"FROM b_subscription S ".
			"WHERE S.ID='".$ID."' ";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	// deletion
	
	/**
	* <p>Метод удаляет подписку. Метод статический.</p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и  ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @return mixed <p>В случае успешного удаления возвращается результат типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. В противном
	* случае возвращается false.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* if (($res = <b>CSubscription::Delete</b>($ID)) &amp;&amp;
	*      $res-&gt;AffectedRowsCount() &lt; 1 ||
	*      $res == false)
	*     echo "Error deleting subscription.";
	* else
	*     echo "Subscription deleted.";
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiondelete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);

		foreach (GetModuleEvents("subscribe", "OnBeforeSubscriptionDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
			{
				return false;
			}
		}

		$DB->StartTransaction();

		$res = $DB->Query("DELETE FROM b_subscription_rubric WHERE SUBSCRIPTION_ID='".$ID."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res)
		{
			$res = $DB->Query("DELETE FROM b_subscription WHERE ID='".$ID."' ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if ($res)
		{
			foreach (GetModuleEvents("subscribe", "OnAfterSubscriptionDelete", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}

			$DB->Commit();
			return $res;
		}

		$DB->Rollback();
		return false;
	}

	//check fields before writing
	public function CheckFields(&$arFields, $ID, $SITE_ID=SITE_ID)
	{
		global $DB, $APPLICATION;

		$APPLICATION->ResetException();
		$this->LAST_ERROR = "";
		$aMsg = array();
		if($ID > 0)
		{
			$arFields["ID"] = $ID;
		}

		if ($ID > 0)
			$db_events = GetModuleEvents("subscribe", "OnStartSubscriptionUpdate", true);
		else
			$db_events = GetModuleEvents("subscribe", "OnStartSubscriptionAdd", true);

		foreach($db_events as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields, $SITE_ID)) === false)
			{
				break;
			}
		}


		if(is_set($arFields, "EMAIL"))
		{
			if(strlen($arFields["EMAIL"]) == 0 || !check_email($arFields["EMAIL"]))
				$aMsg[] = array("id"=>"EMAIL", "text"=>GetMessage("class_subscr_addr"));
			else
			{
				$res = $this->GetByEmail($arFields["EMAIL"], intval($arFields["USER_ID"]));
				$ar = $res->Fetch();
				if($ar && ($ar["ID"] <> intval($ID)))
					$aMsg[] = array("id"=>"EMAIL", "text"=>GetMessage("class_subscr_addr2"));
			}
		}

		if(is_set($arFields, "USER_ID"))
		{
			if(intval($arFields["USER_ID"]) > 0)
			{
				$res = $DB->Query("SELECT 'x' FROM b_user WHERE ID = ".intval($arFields["USER_ID"]), false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if(!$res->Fetch())
					$aMsg[] = array("id"=>"USER_ID", "text"=>GetMessage("class_subscr_user"));
			}
		}

		if ($ID > 0)
			$db_events = GetModuleEvents("subscribe", "OnBeforeSubscriptionUpdate", true);
		else
			$db_events = GetModuleEvents("subscribe", "OnBeforeSubscriptionAdd", true);

		foreach($db_events as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields, $SITE_ID)) === false)
			{
				$err = $APPLICATION->GetException();
				if ($err)
				{
					$aMsg[] = array("id"=>"", "text"=>$err->GetString());
					$APPLICATION->ResetException();
				}
				else
				{
					$aMsg[] = array("id"=>"", "text"=>"Unknown error.");
				}
				break;
			}
		}

		if($ID > 0)
		{
			unset($arFields["ID"]);
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}

		return true;
	}

	//link with categories
	public static function UpdateRubrics($ID, $aRubric, $SITE_ID=false)
	{
		global $DB;
		$ID = intval($ID);

		if($SITE_ID!==false)
		{
			$strSql = "
			SELECT sr.SUBSCRIPTION_ID,sr.LIST_RUBRIC_ID
			FROM
				b_subscription_rubric sr
				INNER JOIN b_list_rubric lr ON lr.ID=sr.LIST_RUBRIC_ID
			WHERE
				sr.SUBSCRIPTION_ID='".$ID."'
				AND lr.LID='".$DB->ForSql($SITE_ID)."'
			";
			$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar=$rs->Fetch())
			{
				$strSql = "
				DELETE FROM b_subscription_rubric
				WHERE SUBSCRIPTION_ID=".intval($ar["SUBSCRIPTION_ID"])."
				AND LIST_RUBRIC_ID=".intval($ar["LIST_RUBRIC_ID"])."
				";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			$sID = "0";
			if(is_array($aRubric))
				foreach($aRubric as $rub)
					$sID .= ",".intval($rub);

			$strSql = "
				INSERT INTO b_subscription_rubric (SUBSCRIPTION_ID, LIST_RUBRIC_ID)
				SELECT '".$ID."', ID
				FROM b_list_rubric
				WHERE ID IN (".$sID.")
				AND LID='".$DB->ForSql($SITE_ID)."'
			";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$DB->Query("DELETE FROM b_subscription_rubric WHERE SUBSCRIPTION_ID='".$ID."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$sID = "0";
			if(is_array($aRubric))
				foreach($aRubric as $rub)
					$sID .= ",".intval($rub);

			$strSql = "
				INSERT INTO b_subscription_rubric (SUBSCRIPTION_ID, LIST_RUBRIC_ID)
				SELECT '".$ID."', ID
				FROM b_list_rubric
				WHERE ID IN (".$sID.")
			";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	//adding
	
	/**
	* <p>Метод добавляет подписку на рассылки и отправляет подписчику письмо с кодом подтверждения подписки (если не указано не отправлять). Метод нестатический.</p>
	*
	*
	* @param array $arFields  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongeneralfields.php">полей
	* объекта "Подписка"</a>. 	Дополнительно могут быть указаны поля:        
	* <br><ul> <li> <b>RUB_ID</b> - массив идентификаторов рассылок, на которые
	* подписывается адрес;</li>          	           <li> <b>SEND_CONFIRM</b> - отправлять ли
	* письмо с кодом подтверждения подписчику (Y/N).</li>          	           <li>
	* <b>ALL_SITES</b> - отписать подписчика от рассылок всех сайтов или только
	* от заданного SITE_ID (Y/N).</li>                    <li> <b>USER_ID</b> - необязательный.
	* Идентификатор зарегистрированного пользователя.</li>          </ul>
	*
	* @param string $SITE_ID = SITE_ID Идентификатор сайта, по которому определяется шаблон письма о
	* подтверждении подписки. 	По умолчанию параметр принимает
	* значение текущего сайта.
	*
	* @return int <p>В случае успешного добавления возвращается ID подписки. В
	* противном случает возвращается false, и переменная класса LAST_ERROR
	* содержит сообщение об ошибке (так же возбуждается исключение <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a>).</p><a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //there must be at least one newsletter category
	* if(!is_array($RUB_ID) || count($RUB_ID) == 0)
	*     $strWarning .= "There must be at least one category."."&lt;br&gt;";
	* 
	* if($strWarning == "")
	* {
	*     $arFields = Array(
	*         "USER_ID" =&gt; ($USER-&gt;IsAuthorized()? $USER-&gt;GetID():false),
	*         "FORMAT" =&gt; ($FORMAT &lt;&gt; "html"? "text":"html"),
	*         "EMAIL" =&gt; $EMAIL,
	*         "ACTIVE" =&gt; "Y",
	*         "RUB_ID" =&gt; $RUB_ID
	*     );
	*     $subscr = new CSubscription;
	* 
	*     //can add without authorization
	*     $ID = <b>$subscr-&gt;Add</b>($arFields);
	*     if($ID&gt;0)
	*         CSubscription::Authorize($ID);
	*     else
	*         $strWarning .= "Error adding subscription: ".$subscr-&gt;LAST_ERROR."&lt;br&gt;";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptionadd.php
	* @author Bitrix
	*/
	public function Add($arFields, $SITE_ID=SITE_ID)
	{
		global $DB;

		if(!$this->CheckFields($arFields, 0, $SITE_ID))
			return false;

		if(array_key_exists("USER_ID", $arFields) && (intval($arFields["USER_ID"]) <= 0))
			$arFields["USER_ID"] = false;

		$arFields["CONFIRM_CODE"] = randString(8);
		$arFields["~DATE_INSERT"]  = $DB->CurrentTimeFunction();
		$arFields["~DATE_CONFIRM"] = $DB->CurrentTimeFunction();

		$ID = $DB->Add("b_subscription", $arFields);

		if($ID > 0)
		{
			if(is_set($arFields,"ALL_SITES") && $arFields["ALL_SITES"]=="Y")
				$this->UpdateRubrics($ID, $arFields["RUB_ID"]);
			else
				$this->UpdateRubrics($ID, $arFields["RUB_ID"], $SITE_ID);

			if($arFields["SEND_CONFIRM"] <> "N")
				$this->ConfirmEvent($ID, $SITE_ID);
		}

		return $ID;
	}

	//Updating record
	
	/**
	* <p>Метод изменяет данные подписки. Если изменяется адрес подписки, то метод снимает подтверждение с подписки и  генерирует событие для отправки письма с кодом подтверждения подписки (если это явно не запрещено).  Если подписка не подтверждена, а массив полей включает в себя правильный CONFIRM_CODE, то подписка  подтверждается. Метод нестатический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @param array $arFields  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptiongeneralfields.php">полей
	* объекта "Подписка"</a>. 	Дополнительно могут быть указаны поля:<br>
	* 	   RUB_ID - массив идентификаторов рассылок, на которые
	* подписывается адрес;<br> 	   SEND_CONFIRM - отправлять ли письмо с кодом
	* подтверждения подписчику при изменениии адреса (Y/N).
	*
	* @param string $SITE_ID = SITE_ID Идентификатор сайта, по которому определяется шаблон письма о
	* подтверждении подписки. По умолчанию параметр принимает
	* значение текущего сайта. Обязателен при использовании RUB_ID. Если
	* ALL_SITES указан в массиве <b>$arFields</b>, то SITE_ID можно не указывать
	*
	* @return mixed <p>В случае успешного изменения возвращается true. В противном
	* случает возвращается false,  и переменная класса LAST_ERROR содержит
	* сообщение об ошибке (так же возбуждается исключение <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a>).  <br><br> 
	* При успешном результате переменная класса LAST_MESSAGE содержит
	* строку-код информационного сообщения.  Возможные значения:<br> 
	*    "CONF" - подписка подтверждена;<br>     "SENT" - сгенерировано событие
	* для отправки письма с кодом подтверждения.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $subscr = new CSubscription;
	* //confirmation code from letter or confirmation form
	* if($CONFIRM_CODE &lt;&gt; "" &amp;&amp; $ID &gt; 0 &amp;&amp; empty($action))
	* {
	*     if($str_CONFIRMED &lt;&gt; "Y")
	*     {
	*         //subscribtion confirmation
	*         if(<b>$subscr-&gt;Update</b>($ID, array("CONFIRM_CODE"=&gt;$CONFIRM_CODE)))
	*             $str_CONFIRMED = "Y";
	*         $strWarning .= $subscr-&gt;LAST_ERROR;
	*         $iMsg = $subscr-&gt;LAST_MESSAGE;
	*     }
	* }
	* 
	* ///<************************
	* //form actions processing
	* ///<************************
	* if($ID &gt; 0)
	* {
	*     if($action == "unsubscribe" &amp;&amp; CSubscription::IsAuthorized($ID))
	*     {
	*         //unsubscription
	*         if(<b>$subscr-&gt;Update</b>($ID, array("ACTIVE"=&gt;"N")))
	*         {
	*             $str_ACTIVE = "N";
	*             $iMsg = "UNSUBSCR";
	*         }
	*     }
	*     if($action == "activate" &amp;&amp; CSubscription::IsAuthorized($ID))
	*     {
	*         //activation
	*         if(<b>$subscr-&gt;Update</b>($ID, array("ACTIVE"=&gt;"Y")))
	*         {
	*             $str_ACTIVE = "Y";
	*             $iMsg = "ACTIVE";
	*         }
	*     }
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptionupdate.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields, $SITE_ID=SITE_ID)
	{
		global $DB;
		$ID = intval($ID);
		$this->LAST_MESSAGE = "";

		if(!$this->CheckFields($arFields, $ID, $SITE_ID))
			return false;

		if(array_key_exists("USER_ID", $arFields) && (intval($arFields["USER_ID"]) <= 0))
			$arFields["USER_ID"] = false;

		//Check whether email changed. If changed, we must to generate new confirm code.
		$strSql =
			"SELECT EMAIL, CONFIRM_CODE, CONFIRMED FROM b_subscription ".
			"WHERE ID='".$ID."' ";
		$db_check = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if(!($db_check_arr = $db_check->Fetch()))
		{
			$this->LAST_ERROR = GetMessage("class_subscr_perm")."<br>";
			return false;
		}

		$CONFIRM_CODE = $arFields["CONFIRM_CODE"];
		unset($arFields["CONFIRM_CODE"]);
		if(!is_set($arFields, "EMAIL") || strtoupper($db_check_arr["EMAIL"]) == strtoupper($arFields["EMAIL"]))
		{
			//the same email - check confirm code
			if(strlen($CONFIRM_CODE)>0 && $db_check_arr["CONFIRMED"] <> "Y")
			{
				if($CONFIRM_CODE == $db_check_arr["CONFIRM_CODE"])
				{
					//let's confirm the subscription
					$arFields["CONFIRMED"] = "Y";
					$this->LAST_MESSAGE = "CONF";
				}
				else
				{
					$this->LAST_ERROR = GetMessage("class_subscr_conf")."<br>";
					return false;
				}
			}
		}
		else
		{
			//new email - new confirm code
			$arFields["CONFIRM_CODE"] = randString(8);
			if($arFields["CONFIRMED"]<>"Y")
				$arFields["CONFIRMED"] = "N";
		}

		$strUpdate = $DB->PrepareUpdate("b_subscription", $arFields);
		if (strlen($strUpdate)>0)
		{
			$strSql =
				"UPDATE b_subscription SET ".
				$strUpdate.", ".
				"	DATE_UPDATE=".$DB->GetNowFunction()." ".
				(strlen($arFields["CONFIRM_CODE"])>0? ",".
				"	DATE_CONFIRM=".$DB->GetNowFunction()." "
				:"").
				"WHERE ID=".$ID;
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}

		//subscription categories
		if(is_set($arFields, "RUB_ID"))
		{
			if(is_set($arFields,"ALL_SITES") && $arFields["ALL_SITES"]=="Y")
				$this->UpdateRubrics($ID, $arFields["RUB_ID"]);
			else
				$this->UpdateRubrics($ID, $arFields["RUB_ID"], $SITE_ID);
		}
		//send confirmation code if needed
		if($arFields["SEND_CONFIRM"] <> "N" && strlen($arFields["CONFIRM_CODE"])>0)
		{
			$this->ConfirmEvent($ID, $SITE_ID);
			$this->LAST_MESSAGE = "SENT";
		}

		return true;
	}

	//message with subscription confirmation
	
	/**
	* <p>Метод добавляет событие SUBSCRIBE_CONFIRM для отправки подписчику письма с кодом подтверждения подписки.  Письмо формируется по шаблону типа "SUBSCRIBE_CONFIRM - Подтверждение подписки". Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @param string $SITE_ID = SITE_ID Идентификатор сайта, по которому определяется шаблон письма о
	* подтверждении подписки. 	По умолчанию параметр принимает
	* значение текущего сайта.
	*
	* @return bool <p>При успешном добавлении события возвращается true, иначе
	* false.</p><p>Так же false возвращается если подписка уже подтверждена
	* (CONFIRMED равно Y).</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* if($ID &gt; 0)
	* {
	*     //confirmation code request
	*     if($action == "sendcode")
	*     {
	*         if(<b>CSubscription::ConfirmEvent</b>($ID))
	*             echo "Subscription confirmation was sent successfully";
	*     }
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptionconfirmevent.php
	* @author Bitrix
	*/
	public static function ConfirmEvent($ID, $SITE_ID=SITE_ID)
	{
		static $SITE_DIR_CACHE = array();
		CTimeZone::Disable();
		$subscr = CSubscription::GetByID($ID);
		CTimeZone::Enable();
		if($subscr_arr = $subscr->Fetch())
		{
			if(!array_key_exists($SITE_ID, $SITE_DIR_CACHE))
			{
				$db_lang = CLang::GetByID($SITE_ID);
				if($ar_lang = $db_lang->Fetch())
					$SITE_DIR_CACHE[$SITE_ID] = $ar_lang["DIR"];
				else
					$SITE_DIR_CACHE[$SITE_ID] = LANG_DIR;
			}

			$subscr_arr["USER_NAME"] = "";
			$subscr_arr["USER_LAST_NAME"] = "";
			if(intval($subscr_arr["USER_ID"]) > 0)
			{
				$rsUser = CUser::GetByID($subscr_arr["USER_ID"]);
				if($arUser = $rsUser->Fetch())
				{
					$subscr_arr["USER_NAME"] = $arUser["NAME"];
					$subscr_arr["USER_LAST_NAME"] = $arUser["LAST_NAME"];
				}
			}

			$arFields = Array(
				"ID" => $subscr_arr["ID"],
				"EMAIL" => $subscr_arr["EMAIL"],
				"CONFIRM_CODE" => $subscr_arr["CONFIRM_CODE"],
				"USER_NAME" => $subscr_arr["USER_NAME"]." ".$subscr_arr["USER_LAST_NAME"],
				"DATE_SUBSCR" => ($subscr_arr["DATE_UPDATE"] <> ""? $subscr_arr["DATE_UPDATE"]: $subscr_arr["DATE_INSERT"]),
				"SUBSCR_SECTION" => str_replace(
					array("#SITE_DIR#", "#LANG_DIR#"),
					array($SITE_DIR_CACHE[$SITE_ID], $SITE_DIR_CACHE[$SITE_ID]),
					COption::GetOptionString("subscribe", "subscribe_section")
				),
			);
			CEvent::Send("SUBSCRIBE_CONFIRM", $SITE_ID, $arFields);
			return true;
		}
		return false;
	}

	//checks and set user authorization
	
	/**
	* <p>Авторизует посетителя для доступа к редактированию подписки.  Признак успешной авторизации сохраняется в PHP-сессии ($_SESSION["SESS_SUBSCR_AUTH"][$ID]). </p> <p>Если подписка анонимная, то авторизация проверяется по  коду подтверждения подписки. Если подписка принадлежит зарегистрированному пользователю, то доступ предоставляется  только авторизованному пользователю-владельцу подписки.  Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @param string $CONFIRM_CODE = false Пароль для авторизации доступа, должен совпадать с кодом
	* подтверждения подписки, 	сгенерированным при добавлении или
	* изменении адреса подписки. 	Если параметр принимает значение false,
	* то производится безусловная авторизация.
	*
	* @return bool <p>При успешной авторизации доступа к подписке возвращается true,
	* иначе false.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //try to authorize subscription by CONFIRM_CODE or user password AUTH_PASS
	* if($ID &gt; 0 &amp;&amp; !CSubscription::IsAuthorized($ID))
	* {
	*     if($str_USER_ID &gt; 0 &amp;&amp; !empty($AUTH_PASS))
	*     {
	*         //trying to login user
	*         $usr = CUser::GetByID($str_USER_ID);
	*         if(($usr_arr = $usr-&gt;Fetch()))
	*         {
	*             $res = $USER-&gt;Login($usr_arr["LOGIN"], $AUTH_PASS);
	*             if($res["TYPE"] == "ERROR")
	*                 $strWarning .= $res["MESSAGE"];
	*         }
	*     }
	*     <b>CSubscription::Authorize</b>($ID, (empty($AUTH_PASS)? $CONFIRM_CODE:$AUTH_PASS));
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptionauthorize.php
	* @author Bitrix
	*/
	public static function Authorize($ID, $CONFIRM_CODE=false)
	{
		global $USER;

		$subscr = CSubscription::GetByID($ID);
		if(($subscr_arr = $subscr->Fetch()))
		{
			//unconditional auth
			if($CONFIRM_CODE===false)
			{
				$_SESSION["SESS_SUBSCR_AUTH"][$ID] = "YES";
				return true;
			}
			//anonymous subscription
			if($subscr_arr["CONFIRM_CODE"] == $CONFIRM_CODE)
			{
				$_SESSION["SESS_SUBSCR_AUTH"][$ID] = "YES";
				return true;
			}
			//user account subscription
			if(intval($subscr_arr["USER_ID"]) > 0)
			{
				if($USER->IsAuthorized())
				{
					//user is already authorized
					if($USER->GetID()==$subscr_arr["USER_ID"])
					{
						$_SESSION["SESS_SUBSCR_AUTH"][$ID] = "YES";
						return true;
					}
				}
			}
		}
		$_SESSION["SESS_SUBSCR_AUTH"][$ID] = "NO";
		return false;
	}

	//retuns user's subscription authorization
	
	/**
	* <p>Метод проверяет, авторизован ли текущий посетитель для доступа к информации о подписке. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор подписки.
	*
	* @return bool <p>Если посетитель авторизован для доступа к данной подписке, то
	* возвращается true, иначе false.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //check whether already authorized to show password field
	* $bShowPass = true;
	* $aSubscr = CSubscription::GetUserSubscription();
	* if($aSubscr["ID"] &gt; 0)
	* {
	*     //try to authorize user account's subscription
	*     if($aSubscr["USER_ID"]&gt;0 &amp;&amp; !<b>CSubscription::IsAuthorized</b>($aSubscr["ID"]))
	*         CSubscription::Authorize($aSubscr["ID"], "");
	*     //check authorization
	*     if(<b>CSubscription::IsAuthorized</b>($aSubscr["ID"]))
	*         $bShowPass = false;
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/csubscriptiongeneral/csubscriptionisauthorized.php
	* @author Bitrix
	*/
	public static function IsAuthorized($ID)
	{
		return ($_SESSION["SESS_SUBSCR_AUTH"][$ID] == "YES");
	}

	//*****************************
	// Events
	//*****************************

	//user deletion event
	public static function OnUserDelete($user_id)
	{
		//clear user subscriptions on user deletion
		global $DB;
		$user_id = intval($user_id);

		$strSql = "SELECT ID FROM b_subscription WHERE USER_ID = ".$user_id;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arIn = array();
		while($res_arr = $res->Fetch())
			$arIn[] = intval($res_arr["ID"]);

		if(count($arIn) > 0)
		{
			$sIn = implode(",",$arIn);
			if(
				$DB->Query("DELETE FROM b_subscription_rubric WHERE SUBSCRIPTION_ID IN (".$sIn.")", false, "File: ".__FILE__."<br>Line: ".__LINE__) &&
				$DB->Query("DELETE FROM b_subscription WHERE ID IN (".$sIn.")", false, "File: ".__FILE__."<br>Line: ".__LINE__)
			)
				return true;
			else
				return false;
		}
		else
		{
			return true;
		}
	}

	//user logout event
	public static function OnUserLogout($user_id)
	{
		//let's reset subscriptions authorization on user logout
		global $DB;
		$user_id = intval($user_id);
		if($user_id>0)
		{
			$strSql = "SELECT ID FROM b_subscription WHERE USER_ID=".$user_id;
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($res_arr = $res->Fetch())
				$_SESSION["SESS_SUBSCR_AUTH"][$res_arr["ID"]] = "NO";
		}
		return true;
	}

	//*****************************
	// Agents
	//*****************************

	//delete unconfirmed subscriptions
	public static function CleanUp()
	{
		//must be inherited
		return "CSubscription::CleanUp();";
	}
}
