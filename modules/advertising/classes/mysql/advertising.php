<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/advertising/classes/general/advertising.php");

/*****************************************************************
				Класс "Рекламный контракт"
*****************************************************************/


/**
 * Класс для работы с рекламными контрактами. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/advertising/classes/cadvcontract/index.php
 * @author Bitrix
 */
class CAdvContract extends CAdvContract_all
{
public static 	function err_mess()
	{
		$module_id = "advertising";
		return "<br>Module: ".$module_id."<br>Class: CAdvContract<br>File: ".__FILE__;
	}

	// получаем список контрактов

	/**
	* <p>Метод предназначен для получения списка контрактов.</p>
	*
	*
	* @param varchar &$by  Идентификатор, позволяющий задать имя поля для сортировки.
	* Допустимы следующие значения: <ul> <li>s_id - по ID </li> <li>s_lamp - по
	* индикатору контракта </li> <li>s_name - по заголовку контракта </li>
	* <li>s_description - по описанию контракта </li> <li>s_active - по флагу активности
	* </li> <li>s_weight - по весу </li> <li>s_sort - по сортировке </li> <li>s_date_modify - по дате
	* модификации </li> <li>s_modified_by - по ID пользователя, изменившего
	* контракт </li> <li>s_banner_count - по числу баннеров, приписанных к
	* контракту </li> <li>s_ctr - по отклику (CTR) контракта </li> <li>s_show_count - по
	* суммарному числу показов баннеров контракта </li> <li>s_max_show_count - по
	* максимальному суммарному числу всех показов баннеров контракта
	* </li> <li>s_click_count - по суммарному числу кликов на баннеры контракта </li>
	* <li>s_max_click_count - по максимальному суммарному числу кликов на баннеры
	* контракта</li> </ul>
	*
	* @param varchar &$order  Массив для фильтрации значений. Необязательный параметр. В
	* массиве допустимы следующие индексы: <ul> <li>ID - ID контракта
	* (допускается сложная логика) </li> <li>- "Y" - при фильтрации по ID
	* контракта будет искаться точное совпадение (по умолчанию); "N" - в
	* противном случае будет искаться вхождение </li> <li>LAMP - значение
	* индикатора: "red", "green" </li> <li>DATE_MODIFY_1 - левая часть интервала для даты
	* модификации контракта </li> <li>DATE_MODIFY_2 - правая часть интервала для
	* даты модификации контракта </li> <li>NAME - имя контракта (допускается
	* сложная логика) </li> <li>NAME_EXACT_MATCH - "Y" - при фильтрации по имени
	* контракта будет искаться точное совпадение; "N" - в противном
	* случае будет искаться вхождение (по умолчанию) </li> <li>DESCRIPTION -
	* описание контракта (допускается сложная логика) </li>
	* <li>DESCRIPTION_EXACT_MATCH - "Y" - при фильтрации по описанию контракта будет
	* искаться точное совпадение; "N" - в противном случае будет искаться
	* вхождение (по умолчанию) </li> <li>OWNER - ID, ФИО, логин владельца
	* контракта (допускается сложная логика) </li> <li>OWNER_EXACT_MATCH - "Y" - при
	* фильтрации по ID, ФИО, логину владельца контракта будет искаться
	* точное совпадение; "N" - в противном случае будет искаться
	* вхождение (по умолчанию) </li> <li>BANNER_COUNT_1 - левая часть интервала для
	* числа баннеров контракта </li> <li>BANNER_COUNT_2 - правая часть интервала
	* для числа баннеров контракта </li> <li>SHOW_COUNT_1 - левая часть интервала
	* для суммарного числа показов баннеров контракта </li> <li>SHOW_COUNT_2 -
	* правая часть интервала для суммарного числа показов баннеров
	* контракта </li> <li>CLICK_COUNT_1 - левая часть интервала для суммарного
	* числа кликов по баннерам контракта </li> <li>CLICK_COUNT_2 - правая часть
	* интервала для суммарного числа кликов по баннерам контракта </li>
	* <li>CTR_1 - левая часть интервала для CTR контракта </li> <li>CTR_2 - правая
	* часть интервала для CTR контракта </li> <li>ADMIN_COMMENTS - административный
	* комментарий к контракту (допускается сложная логика) </li>
	* <li>ADMIN_COMMENTS_EXACT_MATCH - "Y" - при фильтрации по административному
	* комментарию к контракту будет искаться точное совпадение; "N" - в
	* противном случае будет искаться вхождение (по умолчанию)</li> </ul>
	*
	* @param array $arFilter = array() Параметр проверяет уровень доступа к модулю Реклама
	* (администратор рекламы, рекламодатель и т.д.) Если параметр
	* определён как "N", то считается, что текущий пользователь обладает
	* административными правами доступа к модулю Реклама. Если
	* параметр пропущен либо равен "Y", то метод проверяет уровень
	* доступа к контракту, которому принадлежит баннер. Необязательный
	* параметр.
	*
	* @param boolean &$is_filtered  
	*
	* @param char(1) $CHECK_RIGHTS = "Y" 
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // фильтр
	* $FilterArr = Array(
	* 	"find_id",
	* 	"find_id_exact_match",
	* 	"find_date_modify_1", 
	* 	"find_date_modify_2", 
	* 	"find_name",
	* 	"find_name_exact_match",
	* 	"find_description", 
	* 	"find_description_exact_match",
	* 	"find_lamp", 
	* 	"find_owner",
	* 	"find_owner_exact_match",
	* 	"find_banner_count_1",
	* 	"find_banner_count_2",
	* 	"find_show_count_1",
	* 	"find_show_count_2",
	* 	"find_click_count_1",
	* 	"find_click_count_2",
	* 	"find_ctr_1",
	* 	"find_ctr_2",
	* 	"find_admin_comments",
	* 	"find_admin_comments_exact_match"
	* 	);
	* if (strlen($set_filter)&gt;0) InitFilterEx($FilterArr,"ADV_CONTRACT_LIST","set"); 
	* else InitFilterEx($FilterArr,"ADV_CONTRACT_LIST","get");
	* if (strlen($del_filter)&gt;0) DelFilterEx($FilterArr,"ADV_CONTRACT_LIST");
	* InitBVar($find_id_exact_match);
	* InitBVar($find_name_exact_match);
	* InitBVar($find_description_exact_match);
	* InitBVar($find_owner_exact_match);
	* InitBVar($find_admin_comments_exact_match);
	* $arFilter = Array(
	* 	"ID"							=&gt; $find_id,
	* 	"ID_EXACT_MATCH"				=&gt; $find_id_exact_match,
	* 	"DATE_MODIFY_1"				 =&gt; $find_date_modify_1, 
	* 	"DATE_MODIFY_2"				 =&gt; $find_date_modify_2, 
	* 	"NAME"						  =&gt; $find_name,
	* 	"NAME_EXACT_MATCH"			  =&gt; $find_name_exact_match,
	* 	"DESCRIPTION"				   =&gt; $find_description,
	* 	"DESCRIPTION_EXACT_MATCH"	   =&gt; $find_description_exact_match,
	* 	"LAMP"						  =&gt; $find_lamp,
	* 	"OWNER"						 =&gt; $find_owner,
	* 	"OWNER_EXACT_MATCH"			 =&gt; $find_owner_exact_match,
	* 	"BANNER_COUNT_1"				=&gt; $find_banner_count_1,
	* 	"BANNER_COUNT_2"				=&gt; $find_banner_count_2,
	* 	"SHOW_COUNT_1"				  =&gt; $find_show_count_1,
	* 	"SHOW_COUNT_2"				  =&gt; $find_show_count_2,
	* 	"CLICK_COUNT_1"				 =&gt; $find_click_count_1,
	* 	"CLICK_COUNT_2"				 =&gt; $find_click_count_2,
	* 	"CTR_1"						 =&gt; $find_ctr_1,
	* 	"CTR_2"						 =&gt; $find_ctr_2,
	* 	"ADMIN_COMMENTS"				=&gt; $find_admin_comments,
	* 	"ADMIN_COMMENTS_EXACT_MATCH"	=&gt; $find_admin_comments_exact_match
	* 	);
	* $rsAdvContract = <b>CAdvContract::GetList</b>($by, $order, $arFilter, $is_filtered, "N");
	* ?&gt;
	* 
	* &lt;?
	* Array
	* (
	* 	[LAMP] =&gt; green
	* 	[ID] =&gt; 3
	* 	[ACTIVE] =&gt; Y
	* 	[NAME] =&gt; заголовок контракта
	* 	[DESCRIPTION] =&gt; описание контракта
	* 	[ADMIN_COMMENTS] =&gt; административный комментарий
	* 	[WEIGHT] =&gt; 1000
	* 	[SORT] =&gt; 200
	* 	[MAX_SHOW_COUNT] =&gt; 1000
	* 	[SHOW_COUNT] =&gt; 312
	* 	[MAX_CLICK_COUNT] =&gt; 100
	* 	[CLICK_COUNT] =&gt; 64
	* 	[EMAIL_COUNT] =&gt; 0
	* 	[CREATED_BY] =&gt; 2
	* 	[MODIFIED_BY] =&gt; 2
	* 	[DEFAULT_STATUS_SID] =&gt; READY
	* 	[CTR] =&gt; 20.51
	* 	[DATE_SHOW_FROM] =&gt; 15.06.2004
	* 	[DATE_SHOW_TO] =&gt; 07.07.2009
	* 	[DATE_CREATE] =&gt; 07.06.2004 19:04:55
	* 	[DATE_MODIFY] =&gt; 24.06.2004 10:56:08
	* 	[BANNER_COUNT] =&gt; 12
	* )
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/advertising/classes/cadvcontract/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAdvContract::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $APPLICATION, $strError;
		if ($CHECK_RIGHTS=="Y")
		{
			$USER_ID = intval($USER->GetID());
			$isAdmin = CAdvContract::IsAdmin();
			$isDemo = CAdvContract::IsDemo();
			$isManager = CAdvContract::IsManager();
			$isAdvertiser = CAdvContract::IsAdvertiser();
		}
		else
		{
			if (is_object($USER)) $USER_ID = intval($USER->GetID()); else $USER_ID = 0;
			$isAdmin = true;
			$isDemo = true;
			$isManager = true;
			$isAdvertiser = true;
		}
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$lamp = "
			if ((
				(C.DATE_SHOW_FROM<=now() or C.DATE_SHOW_FROM is null or length(C.DATE_SHOW_FROM)<=0) and
				(C.DATE_SHOW_TO>=now() or C.DATE_SHOW_TO is null or length(C.DATE_SHOW_TO)<=0) and
				(ifnull(C.MAX_SHOW_COUNT,0)>ifnull(C.SHOW_COUNT,0) or ifnull(C.MAX_SHOW_COUNT,0)=0) and
				(ifnull(C.MAX_CLICK_COUNT,0)>ifnull(C.CLICK_COUNT,0) or ifnull(C.MAX_CLICK_COUNT,0)=0) and
				(ifnull(C.MAX_VISITOR_COUNT,0)>ifnull(C.VISITOR_COUNT,0) or ifnull(C.MAX_VISITOR_COUNT,0)=0) and
				(C.ACTIVE='Y')
				),
				'green',
				'red')
			";
		if (CAdvContract::CheckFilter($arFilter))
		{
			if (is_array($arFilter))
			{
				$filter_keys = array_keys($arFilter);
				for ($i=0, $n = count($filter_keys); $i < $n; $i++)
				{
					$key = $filter_keys[$i];
					$val = $arFilter[$filter_keys[$i]];
					if ($val == '' || "$val"=="NOT_REF") continue;
					if (is_array($val) && count($val)<=0) continue;
					$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
					$key = strtoupper($key);
					switch($key)
					{
						case "ID":
							$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
							$arSqlSearch[] = GetFilterQuery("C.ID",$val,$match);
							break;
						case "SITE":
							if (is_array($val)) $val = implode(" | ", $val);
							$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
							$arSqlSearch[] = GetFilterQuery("CS.SITE_ID", $val, $match);
							$left_join = "LEFT JOIN b_adv_contract_2_site CS ON (C.ID = CS.CONTRACT_ID)";
							break;
						case "DATE_MODIFY_1":
							$arSqlSearch[] = "C.DATE_MODIFY>=".$DB->CharToDateFunction($val, "SHORT");
							break;
						case "DATE_MODIFY_2":
							$arSqlSearch[] = "C.DATE_MODIFY<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
							break;
						case "NAME":
						case "DESCRIPTION":
						case "ADMIN_COMMENTS":
							$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
							$arSqlSearch[] = GetFilterQuery("C.".$key, $val, $match);
							break;
						case "LAMP":
							$arSqlSearch[] = " ".$lamp." = '".$DB->ForSQL($val)."'";
							break;
						case "OWNER":
							$from = "
								INNER JOIN b_user U ON (U.ID = CU.USER_ID)
								";
							$admin_from_1 = "
								INNER JOIN b_adv_contract_2_user CU ON (CU.CONTRACT_ID=C.ID)
								";
							$admin_from_2 = "
								INNER JOIN b_user U ON (U.ID = CU.USER_ID)
								";
							$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
							$arSqlSearch[] = GetFilterQuery("CU.USER_ID, U.LOGIN, U.NAME, U.LAST_NAME", $val, $match);
							break;
						case "BANNER_COUNT_1":
							$arSqlSearch_h[] = "BANNER_COUNT>='".intval($val)."'";
							break;
						case "BANNER_COUNT_2":
							$arSqlSearch_h[] = "BANNER_COUNT<='".intval($val)."'";
							break;
						case "SHOW_COUNT_1":
							$arSqlSearch[] = "C.SHOW_COUNT>='".intval($val)."'";
							break;
						case "SHOW_COUNT_2":
							$arSqlSearch[] = "C.SHOW_COUNT<='".intval($val)."'";
							break;
						case "EMAIL_COUNT_1":
							$arSqlSearch[] = "C.EMAIL_COUNT>='".intval($val)."'";
							break;
						case "EMAIL_COUNT_2":
							$arSqlSearch[] = "C.EMAIL_COUNT<='".intval($val)."'";
							break;
						case "VISITOR_COUNT_1":
							$arSqlSearch[] = "C.VISITOR_COUNT>='".intval($val)."'";
							break;
						case "VISITOR_COUNT_2":
							$arSqlSearch[] = "C.VISITOR_COUNT<='".intval($val)."'";
							break;
						case "CLICK_COUNT_1":
							$arSqlSearch[] = "C.CLICK_COUNT>='".intval($val)."'";
							break;
						case "CLICK_COUNT_2":
							$arSqlSearch[] = "C.CLICK_COUNT<='".intval($val)."'";
							break;
						case "CTR_1":
							$arSqlSearch[] = "if(C.SHOW_COUNT<=0,0,round((C.CLICK_COUNT*100)/C.SHOW_COUNT,2))>='".DoubleVal(str_replace(',', '.', $val))."'";
							break;
						case "CTR_2":
							$arSqlSearch[] = "if(C.SHOW_COUNT<=0,0,round((C.CLICK_COUNT*100)/C.SHOW_COUNT,2))<='".DoubleVal(str_replace(',', '.', $val))."'";
							break;
						case "USER_PERMISSIONS":
							$admin_from_1 = " INNER JOIN b_adv_contract_2_user CU ON (CU.CONTRACT_ID=C.ID) ";
							$arSqlSearch[] = GetFilterQuery("CU.PERMISSION", $val, "N");
							break;
					}
				}
			}
		}

		if ($by == "s_id")						$strSqlOrder = "ORDER BY C.ID";
		elseif ($by == "s_lamp")				$strSqlOrder = "ORDER BY LAMP";
		elseif ($by == "s_date_modify")			$strSqlOrder = "ORDER BY C.DATE_MODIFY";
		elseif ($by == "s_name")				$strSqlOrder = "ORDER BY C.NAME";
		elseif ($by == "s_description")			$strSqlOrder = "ORDER BY C.DESCRIPTION";
		elseif ($by == "s_modified_by")			$strSqlOrder = "ORDER BY C.MODIFIED_BY";
		elseif ($by == "s_active")				$strSqlOrder = "ORDER BY C.ACTIVE";
		elseif ($by == "s_weight")				$strSqlOrder = "ORDER BY C.WEIGHT";
		elseif ($by == "s_sort")				$strSqlOrder = "ORDER BY ifnull(C.SORT,0)";
		elseif ($by == "s_banner_count")		$strSqlOrder = "ORDER BY BANNER_COUNT";
		elseif ($by == "s_ctr")					$strSqlOrder = "ORDER BY CTR";
		elseif ($by == "s_show_count")			$strSqlOrder = "ORDER BY C.SHOW_COUNT";
		elseif ($by == "s_max_show_count")		$strSqlOrder = "ORDER BY ifnull(C.MAX_SHOW_COUNT,0)";
		elseif ($by == "s_click_count")			$strSqlOrder = "ORDER BY C.CLICK_COUNT";
		elseif ($by == "s_max_click_count")		$strSqlOrder = "ORDER BY ifnull(C.MAX_CLICK_COUNT,0)";
		elseif ($by == "s_visitor_count")		$strSqlOrder = "ORDER BY C.VISITOR_COUNT";
		elseif ($by == "s_max_visitor_count")	$strSqlOrder = "ORDER BY ifnull(C.MAX_VISITOR_COUNT,0)";
		else
		{
			$by = "s_sort";
			$strSqlOrder = "ORDER BY ifnull(C.SORT,0)";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSqlSearch_h = GetFilterSqlSearch($arSqlSearch_h);

		if ($isAdmin || $isDemo || $isManager)
		{
			$strSql = "
				SELECT
					$lamp LAMP,
					C.ID, C.ACTIVE, C.NAME, C.DESCRIPTION, C.ADMIN_COMMENTS, C.WEIGHT, C.SORT, C.MAX_SHOW_COUNT,	C.SHOW_COUNT, C.EMAIL_COUNT, C.CREATED_BY, C.MODIFIED_BY, C.MAX_CLICK_COUNT, C.CLICK_COUNT, C.DEFAULT_STATUS_SID, C.MAX_VISITOR_COUNT, C.VISITOR_COUNT, C.KEYWORDS,
					if(C.SHOW_COUNT<=0,0,round((C.CLICK_COUNT*100)/C.SHOW_COUNT,2))	CTR,
					".$DB->DateToCharFunction("C.DATE_SHOW_FROM")."		DATE_SHOW_FROM,
					".$DB->DateToCharFunction("C.DATE_SHOW_TO")."		DATE_SHOW_TO,
					".$DB->DateToCharFunction("C.DATE_CREATE")."				DATE_CREATE,
					".$DB->DateToCharFunction("C.DATE_MODIFY")."				DATE_MODIFY,
					count(distinct B.ID)								BANNER_COUNT
				FROM
					b_adv_contract C
				LEFT JOIN b_adv_banner B ON (B.CONTRACT_ID=C.ID)
				$left_join
				$admin_from_1
				$admin_from_2
				WHERE
				$strSqlSearch
				GROUP BY
					C.ID
				HAVING
				$strSqlSearch_h
				$strSqlOrder
				";
		}
		else
		{
			$strSql = "
				SELECT
					$lamp LAMP,
					C.ID, C.ACTIVE, C.NAME, C.DESCRIPTION, C.ADMIN_COMMENTS, C.WEIGHT, C.SORT, C.MAX_SHOW_COUNT, 	C.SHOW_COUNT, C.MAX_CLICK_COUNT, C.CLICK_COUNT, C.EMAIL_COUNT, C.CREATED_BY, C.MODIFIED_BY, C.DEFAULT_STATUS_SID, C.MAX_VISITOR_COUNT, C.VISITOR_COUNT, C.KEYWORDS,
					if(C.SHOW_COUNT<=0,0,round((C.CLICK_COUNT*100)/C.SHOW_COUNT,2))	CTR,
					".$DB->DateToCharFunction("C.DATE_SHOW_FROM")."		DATE_SHOW_FROM,
					".$DB->DateToCharFunction("C.DATE_SHOW_TO")."		DATE_SHOW_TO,
					".$DB->DateToCharFunction("C.DATE_CREATE")."				DATE_CREATE,
					".$DB->DateToCharFunction("C.DATE_MODIFY")."				DATE_MODIFY,
					count(distinct B.ID)										BANNER_COUNT
				FROM
					b_adv_contract C
				LEFT JOIN b_adv_banner B ON (B.CONTRACT_ID=C.ID)
				INNER JOIN b_adv_contract_2_user CU ON (CU.CONTRACT_ID=C.ID and CU.USER_ID=$USER_ID)
				$left_join
				$from
				WHERE
				$strSqlSearch
				GROUP BY
					C.ID
				HAVING
				$strSqlSearch_h
				$strSqlOrder
				";
		}
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
}

/*****************************************************************
				Класс "Рекламный баннер"
*****************************************************************/


/**
 * Класс для работы с рекламными баннерами. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/advertising/classes/cadvbanner/index.php
 * @author Bitrix
 */
class CAdvBanner extends CAdvBanner_all
{
public static 	function err_mess()
	{
		$module_id = "advertising";
		return "<br>Module: ".$module_id."<br>Class: CAdvBanner<br>File: ".__FILE__;
	}

public static 	function Update($arFields, $BANNER_ID)
	{
		$err_mess = (CAdvBanner::err_mess())."<br>Function: Update<br>Line: ";
		global $DB;
		$arrKeys = array_keys($arFields);
		if (in_array("CODE", $arrKeys))
		{
			$arFields["CODE"] = "'".$DB->ForSql($arFields["CODE"])."'";
		}
		$DB->Update("b_adv_banner",$arFields,"WHERE ID='".intval($BANNER_ID)."'",$err_mess.__LINE__);
	}

public static 	function getCTRSQL()
	{
		return 'IF (SUM(D.SHOW_COUNT) > 0, round((SUM(D.CLICK_COUNT)*100)/SUM(D.SHOW_COUNT),2), 0)	CTR';
	}

public static 	function Add($arFields)
	{
		$err_mess = (CAdvBanner::err_mess())."<br>Function: Add<br>Line: ";
		global $DB;
		$arrKeys = array_keys($arFields);
		if (in_array("CODE", $arrKeys))
		{
			$arFields["CODE"] = "'".$DB->ForSql($arFields["CODE"])."'";
		}
		$BANNER_ID = $DB->Insert("b_adv_banner",$arFields, $err_mess.__LINE__);
		return $BANNER_ID;
	}

	
	/**
	* <p>Метод предназначен для получения списка баннеров.</p>
	*
	*
	* @param varchar &$by  Идентификатор, позволяющий задать имя поля для сортировки.
	* Допустимы следующие значения: <ul> <li>s_id - по ID </li> <li>s_lamp - по
	* индикатору </li> <li>s_name - по имени баннера </li> <li>s_type_sid - по типу
	* баннера </li> <li>s_contract_id - по ID контракта </li> <li>s_group_sid - по имени группы
	* </li> <li>s_show_count - по количеству показов </li> <li>s_max_show_count - по
	* максимальному количеству показов </li> <li>s_date_last_show - по дате
	* последнего показа </li> <li>s_click_count - по количеству кликов на баннер
	* </li> <li>s_max_click_count - по максимальному количеству кликов </li>
	* <li>s_date_last_click - по дате последнего клика </li> <li>s_active - по флагу
	* активности </li> <li>s_weight - по весу баннера </li> <li>s_status_sid - по статусу
	* баннера </li> <li>s_date_show_from - по дате начала показов </li> <li>s_date_show_to - по
	* дате окончания показов </li> <li>s_ctr - по CTR баннера</li> </ul>
	*
	* @param varchar &$order  Порядок сортировки. Допустимы следующие значения: <ul> <li>desc - по
	* убыванию (значение по умолчанию) </li> <li>asc - по возрастанию</li> </ul>
	*
	* @param array $arFilter = array() Массив для фильтрации значений. Необязательный параметр. В
	* массиве допустимы следующие индексы: <ul> <li>ID - ID баннера
	* (допускается <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>)
	* </li> <li>ID_EXACT_MATCH - "Y" - при фильтрации по ID баннера будет искаться
	* точное совпадение (по умолчанию); "N" - в противном случае будет
	* искаться вхождение </li> <li>ACTIVE - активность, значения Y/N</li> <li>LAMP -
	* значение индикатора: "red", "green" </li> <li>LANG - двухсимвольный
	* идентификатор языковой части сайта в которой будет показываться
	* баннер </li> <li>SHOW_COUNT_1 - левая часть интервала для числа показов </li>
	* <li>SHOW_COUNT_2 - правая часть интервала для числа показов </li> <li>CLICK_COUNT_1 -
	* левая часть интервала для числа кликов </li> <li>CLICK_COUNT_2 - правая
	* часть интервала для числа кликов </li> <li>CTR_1 - левая часть интервала
	* для CTR баннера </li> <li>CTR_2 - правая часть интервала для CTR баннера </li>
	* <li>GROUP - имя группы баннера (допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>) </li> <li>GROUP_EXACT_MATCH
	* - "Y" - при фильтрации по имени группы будет искаться точное
	* совпадение; "N" - в противном случае будет искаться вхождение (по
	* умолчанию) </li> <li>STATUS_SID - символьный код статуса (допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>); символьный
	* код статуса может принимать следующие значения: <ul> <li>PUBLISHED -
	* баннер подтвержден и опубликован</li> <li>READY - баннер на
	* рассмотрении</li> <li>REJECTED - баннер отклонен</li> </ul> </li> <li>CONTRACT_ID - ID
	* контракта (допускается <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная
	* логика</a>) </li> <li>CONTRACT_ID_EXACT_MATCH - "Y" - при фильтрации по ID контракта
	* будет искаться точное совпадение (по умолчанию); "N" - в противном
	* случае будет искаться вхождение </li> <li>CONTRACT - ID, имя, описание
	* контракта (допускается <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная
	* логика</a>) </li> <li>CONTRACT_EXACT_MATCH - "Y" - при фильтрации по ID, имени,
	* описанию контракта будет искаться точное совпадение; "N" - в
	* противном случае будет искаться вхождение (по умолчанию) </li>
	* <li>TYPE_SID - символьный код типа баннера (допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>) </li>
	* <li>TYPE_SID_EXACT_MATCH - "Y" - при фильтрации по символьному коду типа
	* баннера будет искаться точное совпадение (по умолчанию); "N" - в
	* противном случае будет искаться вхождение </li> <li>NAME - имя баннера
	* (допускается <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>)
	* </li> <li>NAME_EXACT_MATCH - "Y" - при фильтрации по имени баннера будет
	* искаться точное совпадение; "N" - в противном случае будет искаться
	* вхождение (по умолчанию) </li> <li>CODE - код баннера (допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>) </li> <li>CODE_EXACT_MATCH
	* - "Y" - при фильтрации по коду баннера будет искаться точное
	* совпадение; "N" - в противном случае будет искаться вхождение (по
	* умолчанию) </li> <li>COMMENTS - комментарий к баннеру (допускается <a
	* href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>) </li>
	* <li>COMMENTS_EXACT_MATCH - "Y" - при фильтрации по комментарию к баннеру будет
	* искаться точное совпадение; "N" - в противном случае будет искаться
	* вхождение (по умолчанию) </li> <li>SITE - для фильтрации по сайтам.</li> </ul>
	*
	* @param boolean &$is_filtered  Переменная, возвращающая true в том случае, если список баннеров
	* отфильтрован по какому-либо критерию; либо false в противном случае.
	*
	* @param char(1) $CHECK_RIGHTS = "Y" Параметр проверяет уровень доступа к модулю Реклама
	* (администратор рекламы, рекламодатель и т.д.). Если параметр
	* определён как "N", то считается, что текущий пользователь обладает
	* административными правами доступа к модулю Реклама. Если
	* параметр пропущен либо равен "Y", то функция проверяет уровень
	* доступа к модулю. Необязательный параметр.
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FilterArr = Array(
	* 	"find_id",
	* 	"find_id_exact_match",
	* 	"find_lamp",
	* 	"find_lang",
	* 	"find_show_count_1",
	* 	"find_show_count_2",
	* 	"find_click_count_1",
	* 	"find_click_count_2",
	* 	"find_ctr_1",
	* 	"find_ctr_2",
	* 	"find_contract_id",
	* 	"find_contract",
	* 	"find_contract_exact_match",
	* 	"find_group",
	* 	"find_group_exact_match",
	* 	"find_status_sid",
	* 	"find_type_sid",
	* 	"find_type",
	* 	"find_type_exact_match",
	* 	"find_name",
	* 	"find_name_exact_match",
	* 	"find_code",
	* 	"find_code_exact_match",
	* 	"find_comments",
	* 	"find_comments_exact_match"
	* 	);
	* if (strlen($set_filter)&gt;0) InitFilterEx($FilterArr,"ADV_BANNER_LIST","set"); 
	* else InitFilterEx($FilterArr,"ADV_BANNER_LIST","get");
	* if (strlen($del_filter)&gt;0) DelFilterEx($FilterArr,"ADV_BANNER_LIST");
	* InitBVar($find_id_exact_match);
	* InitBVar($find_status_exact_match);
	* InitBVar($find_group_exact_match);
	* InitBVar($find_contract_exact_match);
	* InitBVar($find_type_exact_match);
	* InitBVar($find_name_exact_match);
	* InitBVar($find_code_exact_match);
	* InitBVar($find_comments_exact_match);
	* $arFilter = Array(
	* 	"ID"					=&gt; $find_id,
	* 	"ID_EXACT_MATCH"		=&gt; $find_id_exact_match,
	* 	"LAMP"				  =&gt; $find_lamp,
	* 	"LANG"				  =&gt; $find_lang,
	* 	"SHOW_COUNT_1"		  =&gt; $find_show_count_1,
	* 	"SHOW_COUNT_2"		  =&gt; $find_show_count_2,
	* 	"CLICK_COUNT_1"		 =&gt; $find_click_count_1,
	* 	"CLICK_COUNT_2"		 =&gt; $find_click_count_2,
	* 	"CTR_1"				 =&gt; $find_ctr_1,
	* 	"CTR_2"				 =&gt; $find_ctr_2,
	* 	"GROUP"				 =&gt; $find_group,
	* 	"GROUP_EXACT_MATCH"	 =&gt; $find_group_exact_match,
	* 	"STATUS_SID"			=&gt; $find_status_sid,
	* 	"CONTRACT_ID"		   =&gt; $find_contract_id,
	* 	"CONTRACT"			  =&gt; $find_contract,
	* 	"CONTRACT_EXACT_MATCH"  =&gt; $find_contract_exact_match,
	* 	"TYPE_SID"			  =&gt; $find_type_sid,
	* 	"TYPE"				  =&gt; $find_type,
	* 	"TYPE_EXACT_MATCH"	  =&gt; $find_type_exact_match,
	* 	"NAME"				  =&gt; $find_name,
	* 	"NAME_EXACT_MATCH"	  =&gt; $find_name_exact_match,
	* 	"CODE"				  =&gt; $find_code,
	* 	"CODE_EXACT_MATCH"	  =&gt; $find_code_exact_match,
	* 	"COMMENTS"			  =&gt; $find_comments,
	* 	"COMMENTS_EXACT_MATCH"  =&gt; $find_comments_exact_match
	* 	);
	* $rsBanners = <b>CAdvBanner::GetList</b>($by, $order, $arFilter, $is_filtered, "N");
	* $rsBanners-&gt;NavStart(20);
	* $rsBanners-&gt;NavPrint("Баннеры");
	* while($arBanner = $rsBanners-&gt;NavNext(true, "f_"))
	* {
	* 	echo "&lt;pre&gt;"; print_r($arBanner); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </bo
	* &lt;?
	* Array
	* (
	* 	[LAMP] =&gt; green
	* 	[ID] =&gt; 88
	* 	[CONTRACT_ID] =&gt; 1
	* 	[TYPE_SID] =&gt; TOP
	* 	[GROUP_SID] =&gt; 
	* 	[STATUS_SID] =&gt; PUBLISHED
	* 	[STATUS_COMMENTS] =&gt;
	* 	[NAME] =&gt;
	* 	[ACTIVE] =&gt; Y
	* 	[LID] =&gt; 
	* 	[WEIGHT] =&gt; 100
	* 	[MAX_SHOW_COUNT] =&gt; 300
	* 	[MAX_CLICK_COUNT] =&gt; 100
	* 	[SHOW_COUNT] =&gt; 102
	* 	[CLICK_COUNT] =&gt; 4
	* 	[IMAGE_ID] =&gt; 1032
	* 	[IMAGE_ALT] =&gt;
	* 	[URL] =&gt; http://www.1c-bitrix.ru?banner_param=<code>#EVENT_GID#</code>
	* 	[URL_TARGET] =&gt; _parent
	* 	[CODE] =&gt; &lt;TABLE class=smalltext cellSpacing=0 cellPadding=0 width=145&gt;
	* &lt;TR&gt;
	* &lt;TD&gt;&lt;/TD&gt;
	* &lt;TD&gt;
	* &lt;DIV align=center&gt;
	* &lt;A class=righthead href="/ru/partners/partnership.php"&gt;Партнёрская программа&lt;/A&gt;
	* &lt;/DIV&gt;
	* &lt;/TD&gt;&lt;/TR&gt;
	* &lt;TR&gt;
	* &lt;TD&gt;&lt;/TD&gt;
	* &lt;TD height=8&gt;&lt;/TD&gt;&lt;/TR&gt;
	* &lt;TR&gt;
	* &lt;TD width=5&gt;&lt;/TD&gt;
	* &lt;TD vAlign=bottom&gt;
	* &lt;DIV align=center&gt;
	* &lt;A href="/ru/partners/partnership.php"&gt;
	* &lt;IMG height=95 src="/images/advert/free.gif" width=100 border=0&gt;&lt;/A&gt;
	* &lt;/DIV&gt;&lt;/TD&gt;&lt;/TR&gt;
	* &lt;TR&gt;
	* &lt;TD&gt;&lt;/TD&gt;
	* &lt;TD height=5&gt;&lt;/TD&gt;&lt;/TR&gt;
	* &lt;TR&gt;
	* &lt;TD&gt;&lt;/TD&gt;
	* &lt;TD&gt;Разработчики сайтов и интеграторы получают:&lt;BR&gt;
	* &lt;B&gt;&lt;FONT class=smalltext&gt;
	* &lt;IMG height=8 src="/images/list_bullet.gif" width=8&gt;
	* &lt;/FONT&gt;&lt;/B&gt; 
	* &lt;FONT color=#ff5a31&gt;скидки до 50%&lt;/FONT&gt; &lt;BR&gt;
	* &lt;B&gt;&lt;FONT class=smalltext&gt;
	* &lt;IMG height=8 src="/images/list_bullet.gif" width=8&gt;
	* &lt;/FONT&gt;&lt;/B&gt; 
	* &lt;FONT color=#ff5a31&gt;бесплатную копиию&lt;/FONT&gt;
	* &lt;/TD&gt;&lt;/TR&gt;
	* &lt;TR&gt;
	* &lt;TD&gt;&lt;/TD&gt;
	* &lt;TD height=5&gt;&lt;/TD&gt;&lt;/TR&gt;
	* &lt;TR&gt;
	* &lt;TD&gt;&lt;/TD&gt;
	* &lt;TD&gt;
	* &lt;DIV align=right&gt;
	* &lt;A class=bottomlinks href="/ru/partners/partnership.php"&gt;Подробнее&lt;/A&gt;
	* &lt;IMG height=7 src="/images/main_button_more_3.gif" width=7&gt;
	* &lt;/DIV&gt;&lt;/TD&gt;&lt;/TR&gt;&lt;/TABLE&gt;
	* 	[CODE_TYPE] =&gt; html
	* 	[STAT_EVENT_1] =&gt; 
	* 	[STAT_EVENT_2] =&gt; 
	* 	[STAT_EVENT_3] =&gt; 
	* 	[FOR_NEW_GUEST] =&gt; 
	* 	[COMMENTS] =&gt; 
	* 	[CREATED_BY] =&gt; 2
	* 	[MODIFIED_BY] =&gt; 2
	* 	[CTR] =&gt; 3.92
	* 	[DATE_LAST_SHOW] =&gt; 24.06.2004 17:39:50
	* 	[DATE_LAST_CLICK] =&gt; 24.06.2004 14:47:53
	* 	[DATE_SHOW_FROM] =&gt; 10.06.2004
	* 	[DATE_SHOW_TO] =&gt; 07.07.2007
	* 	[DATE_CREATE] =&gt; 10.06.2004 11:25:59
	* 	[DATE_MODIFY] =&gt; 24.06.2004 14:33:56
	* 	[CONTRACT_NAME] =&gt; Default
	* 	[TYPE_NAME] =&gt; Top banner
	* )
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/advertising/classes/cadvbanner/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAdvBanner::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $APPLICATION;
		if ($CHECK_RIGHTS=="Y")
		{
			$USER_ID = intval($USER->GetID());
			$isAdmin = CAdvContract::IsAdmin();
			$isDemo = CAdvContract::IsDemo();
			$isManager = CAdvContract::IsManager();
			$isAdvertiser = CAdvContract::IsAdvertiser();
		}
		else
		{
			if (is_object($USER)) $USER_ID = intval($USER->GetID()); else $USER_ID = 0;
			$isAdmin = true;
			$isDemo = true;
			$isManager = true;
			$isAdvertiser = true;
		}
		$arSqlSearch = Array();
		$strSqlSearch = "";


		$DONT_USE_CONTRACT = COption::GetOptionString("advertising", "DONT_USE_CONTRACT", "N");

		if ($DONT_USE_CONTRACT == "Y")
		{
			$lamp = "
				if ((
					(B.DATE_SHOW_FROM<=now() or B.DATE_SHOW_FROM is null or length(B.DATE_SHOW_FROM)<=0) and
					(B.DATE_SHOW_TO>=now() or B.DATE_SHOW_TO is null or length(B.DATE_SHOW_TO)<=0) and
					(ifnull(B.MAX_SHOW_COUNT,0)>ifnull(B.SHOW_COUNT,0) or ifnull(B.MAX_SHOW_COUNT,0)=0) and
					(ifnull(B.MAX_CLICK_COUNT,0)>ifnull(B.CLICK_COUNT,0) or ifnull(B.MAX_CLICK_COUNT,0)=0) and
					(ifnull(B.MAX_VISITOR_COUNT,0)>ifnull(B.VISITOR_COUNT,0) or ifnull(B.MAX_VISITOR_COUNT,0)=0) and
					(B.ACTIVE='Y') and
					(B.STATUS_SID='PUBLISHED') and
					(T.ACTIVE='Y')
					),
					'green',
					'red')
				";
		}
		else
		{
			$lamp = "
				if ((
					(B.DATE_SHOW_FROM<=now() or B.DATE_SHOW_FROM is null or length(B.DATE_SHOW_FROM)<=0) and
					(B.DATE_SHOW_TO>=now() or B.DATE_SHOW_TO is null or length(B.DATE_SHOW_TO)<=0) and
					(ifnull(B.MAX_SHOW_COUNT,0)>ifnull(B.SHOW_COUNT,0) or ifnull(B.MAX_SHOW_COUNT,0)=0) and
					(ifnull(B.MAX_CLICK_COUNT,0)>ifnull(B.CLICK_COUNT,0) or ifnull(B.MAX_CLICK_COUNT,0)=0) and
					(ifnull(B.MAX_VISITOR_COUNT,0)>ifnull(B.VISITOR_COUNT,0) or ifnull(B.MAX_VISITOR_COUNT,0)=0) and
					(B.ACTIVE='Y') and
					(B.STATUS_SID='PUBLISHED') and
					(T.ACTIVE='Y') and
					(C.DATE_SHOW_FROM<=now() or C.DATE_SHOW_FROM is null or length(C.DATE_SHOW_FROM)<=0) and
					(C.DATE_SHOW_TO>=now() or C.DATE_SHOW_TO is null or length(C.DATE_SHOW_TO)<=0) and
					(ifnull(C.MAX_SHOW_COUNT,0)>ifnull(C.SHOW_COUNT,0) or ifnull(C.MAX_SHOW_COUNT,0)=0) and
					(ifnull(C.MAX_CLICK_COUNT,0)>ifnull(C.CLICK_COUNT,0) or ifnull(C.MAX_CLICK_COUNT,0)=0) and
					(ifnull(C.MAX_VISITOR_COUNT,0)>ifnull(C.VISITOR_COUNT,0) or ifnull(C.MAX_VISITOR_COUNT,0)=0) and
					(C.ACTIVE='Y')
					),
					'green',
					'red')
				";
		}

		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0, $n = count($filter_keys); $i < $n; $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
				if(is_array($val) && count($val)<=0)
					continue;
				if($val == '' || $val == "NOT_REF")
					continue;
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("B.ID",$val,$match);
						break;
					case "LAMP":
						$arSqlSearch[] = " ".$lamp." = '".$DB->ForSQL($val)."'";
						break;
					case "SITE":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("BS.SITE_ID", $val, $match);
						$left_join = "LEFT JOIN b_adv_banner_2_site BS ON (B.ID = BS.BANNER_ID)";
						break;
					case "DATE_MODIFY_1":
						$arSqlSearch[] = "B.DATE_MODIFY>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_MODIFY_2":
						$arSqlSearch[] = "B.DATE_MODIFY<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_CREATE_1":
						$arSqlSearch[] = "B.DATE_CREATE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_CREATE_2":
						$arSqlSearch[] = "B.DATE_CREATE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_SHOW_FROM_1":
						$arSqlSearch[] = "B.DATE_SHOW_FROM>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_SHOW_FROM_2":
						$arSqlSearch[] = "B.DATE_SHOW_FROM<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_SHOW_TO_1":
						$arSqlSearch[] = "B.DATE_SHOW_TO>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_SHOW_TO_2":
						$arSqlSearch[] = "B.DATE_SHOW_TO<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "ACTIVE":
					case "FIX_SHOW":
						$arSqlSearch[] = ($val=="Y") ? "B.".$key."='Y'" : "B.".$key."='N'";
						break;
					case "WEIGHT_1":
						$arSqlSearch[] = "ifnull(B.WEIGHT,0)>='".intval($val)."'";
						break;
					case "WEIGHT_2":
						$arSqlSearch[] = "ifnull(B.WEIGHT,0)<='".intval($val)."'";
						break;
					case "MAX_VISITOR_COUNT_1":
						$arSqlSearch[] = "ifnull(B.MAX_VISITOR_COUNT,0)>='".intval($val)."'";
						break;
					case "MAX_VISITOR_COUNT_2":
						$arSqlSearch[] = "ifnull(B.MAX_VISITOR_COUNT,0)<='".intval($val)."'";
						break;
					case "VISITOR_COUNT_1":
						$arSqlSearch[] = "ifnull(B.VISITOR_COUNT,0)>='".intval($val)."'";
						break;
					case "VISITOR_COUNT_2":
						$arSqlSearch[] = "ifnull(B.VISITOR_COUNT,0)<='".intval($val)."'";
						break;
					case "MAX_SHOW_COUNT_1":
						$arSqlSearch[] = "ifnull(B.MAX_SHOW_COUNT,0)>='".intval($val)."'";
						break;
					case "MAX_SHOW_COUNT_2":
						$arSqlSearch[] = "ifnull(B.MAX_SHOW_COUNT,0)<='".intval($val)."'";
						break;
					case "SHOW_COUNT_1":
						$arSqlSearch[] = "ifnull(B.SHOW_COUNT,0)>='".intval($val)."'";
						break;
					case "SHOW_COUNT_2":
						$arSqlSearch[] = "ifnull(B.SHOW_COUNT,0)<='".intval($val)."'";
						break;
					case "MAX_CLICK_COUNT_1":
						$arSqlSearch[] = "ifnull(B.MAX_CLICK_COUNT,0)>='".intval($val)."'";
						break;
					case "MAX_CLICK_COUNT_2":
						$arSqlSearch[] = "ifnull(B.MAX_CLICK_COUNT,0)<='".intval($val)."'";
						break;
					case "CLICK_COUNT_1":
						$arSqlSearch[] = "ifnull(B.CLICK_COUNT,0)>='".intval($val)."'";
						break;
					case "CLICK_COUNT_2":
						$arSqlSearch[] = "ifnull(B.CLICK_COUNT,0)<='".intval($val)."'";
						break;
					case "CTR_1":
						$arSqlSearch[] = "if(B.SHOW_COUNT<=0,0,round((B.CLICK_COUNT*100)/B.SHOW_COUNT,2))>='".DoubleVal(str_replace(',', '.', $val))."'";
						break;
					case "CTR_2":
						$arSqlSearch[] = "if(B.SHOW_COUNT<=0,0,round((B.CLICK_COUNT*100)/B.SHOW_COUNT,2))<='".DoubleVal(str_replace(',', '.', $val))."'";
						break;
					case "GROUP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("B.GROUP_SID", $val, $match);
						break;
					case "STATUS":
					case "STATUS_SID":
						if (is_array($val)) $val = implode(" | ",$val);
						$arSqlSearch[] = GetFilterQuery("B.STATUS_SID", $val, "N");
						break;
					case "CONTRACT_ID":
						if (is_array($val)) $val = implode(" | ",$val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("B.CONTRACT_ID", $val, $match);
						break;
					case "CONTRACT":
						if (is_array($val)) $val = implode(" | ",$val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("B.CONTRACT_ID, C.NAME, C.DESCRIPTION", $val, $match);
						break;
					case "TYPE_SID":
						if (is_array($val)) $val = implode(" | ",$val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("B.TYPE_SID", $val, $match);
						break;
					case "TYPE":
						if (is_array($val)) $val = implode(" | ",$val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("B.TYPE_SID, T.NAME, T.DESCRIPTION", $val, $match);
						break;
					case "SHOW_USER_GROUP":
						if($val=="Y")
							$arSqlSearch[] = "B.SHOW_USER_GROUP='Y'";
						else
							$arSqlSearch[] = "B.SHOW_USER_GROUP <> 'Y'";
						break;
					case "NAME":
					case "CODE":
					case "COMMENTS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("B.".$key, $val, $match);
						break;

					// совместимость со старой версией
					case "LANG":
					case "FIRST_SITE_ID":
						$arSqlSearch[] = GetFilterQuery("B.FIRST_SITE_ID",$val,"N");
						break;
				}
			}
		}

		if ($by == "s_id") $strSqlOrder = " ORDER BY B.ID ";
		elseif ($by == "s_lamp") $strSqlOrder = " ORDER BY LAMP ";
		elseif ($by == "s_name") $strSqlOrder = " ORDER BY B.NAME ";
		elseif ($by == "s_type_sid") $strSqlOrder = " ORDER BY B.TYPE_SID ";
		elseif ($by == "s_contract_id") $strSqlOrder = " ORDER BY B.CONTRACT_ID ";
		elseif ($by == "s_group_sid") $strSqlOrder = " ORDER BY B.GROUP_SID ";
		elseif ($by == "s_visitor_count") $strSqlOrder = " ORDER BY B.VISITOR_COUNT ";
		elseif ($by == "s_max_visitor_count") $strSqlOrder = " ORDER BY ifnull(B.MAX_VISITOR_COUNT,0) ";
		elseif ($by == "s_show_count") $strSqlOrder = " ORDER BY B.SHOW_COUNT ";
		elseif ($by == "s_max_show_count") $strSqlOrder = " ORDER BY ifnull(B.MAX_SHOW_COUNT,0) ";
		elseif ($by == "s_date_last_show") $strSqlOrder = " ORDER BY B.DATE_LAST_SHOW ";
		elseif ($by == "s_click_count") $strSqlOrder = " ORDER BY B.CLICK_COUNT ";
		elseif ($by == "s_max_click_count") $strSqlOrder = " ORDER BY ifnull(B.MAX_CLICK_COUNT,0) ";
		elseif ($by == "s_date_last_click") $strSqlOrder = " ORDER BY B.DATE_LAST_CLICK ";
		elseif ($by == "s_active") $strSqlOrder = " ORDER BY B.ACTIVE ";
		elseif ($by == "s_weight") $strSqlOrder = " ORDER BY B.WEIGHT ";
		elseif ($by == "s_status_sid") $strSqlOrder = " ORDER BY B.STATUS_SID ";
		elseif ($by == "s_date_show_from") $strSqlOrder = " ORDER BY B.DATE_SHOW_FROM ";
		elseif ($by == "s_date_show_to") $strSqlOrder = " ORDER BY B.DATE_SHOW_TO ";
		elseif ($by == "s_dropdown") $strSqlOrder = " ORDER BY B.CONTRACT_ID desc, B.ID ";
		elseif ($by == "s_ctr") $strSqlOrder = " ORDER BY CTR ";
		elseif ($by == "s_date_create") $strSqlOrder = " ORDER BY B.DATE_CREATE ";
		elseif ($by == "s_date_modify") $strSqlOrder = " ORDER BY B.DATE_MODIFY ";
		else
		{
			$strSqlOrder = " ORDER BY B.ID ";
			$by = "s_id";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order = "desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		if ($isAdmin || $isDemo || $isManager)
		{
			$strSql = "
				SELECT DISTINCT
					$lamp																LAMP,
					B.*,
					B.FIRST_SITE_ID,
					B.FIRST_SITE_ID														LID,
					if(B.SHOW_COUNT<=0,0,round((B.CLICK_COUNT*100)/B.SHOW_COUNT,2))		CTR,
					".$DB->DateToCharFunction("B.DATE_LAST_SHOW")."						DATE_LAST_SHOW,
					".$DB->DateToCharFunction("B.DATE_LAST_CLICK")."					DATE_LAST_CLICK,
					".$DB->DateToCharFunction("B.DATE_SHOW_FROM")."			DATE_SHOW_FROM,
					".$DB->DateToCharFunction("B.DATE_SHOW_TO")."				DATE_SHOW_TO,
					".$DB->DateToCharFunction("B.DATE_SHOW_FIRST")."			DATE_SHOW_FIRST,
					".$DB->DateToCharFunction("B.DATE_CREATE")."						DATE_CREATE,
					".$DB->DateToCharFunction("B.DATE_MODIFY")."						DATE_MODIFY,
					C.NAME																CONTRACT_NAME,
					T.NAME																TYPE_NAME
				FROM
					b_adv_banner B
				INNER JOIN b_adv_type T ON (T.SID = B.TYPE_SID)
				INNER JOIN b_adv_contract C ON (C.ID = B.CONTRACT_ID)
				$left_join
				WHERE
				$strSqlSearch
				$strSqlOrder
				";
		}
		else
		{
			$strSql = "
				SELECT DISTINCT
					$lamp																LAMP,
					B.*,
					B.FIRST_SITE_ID,
					B.FIRST_SITE_ID														LID,
					if(B.SHOW_COUNT<=0,0,round((B.CLICK_COUNT*100)/B.SHOW_COUNT,2))		CTR,
					".$DB->DateToCharFunction("B.DATE_LAST_SHOW")."						DATE_LAST_SHOW,
					".$DB->DateToCharFunction("B.DATE_LAST_CLICK")."					DATE_LAST_CLICK,
					".$DB->DateToCharFunction("B.DATE_SHOW_FROM")."			DATE_SHOW_FROM,
					".$DB->DateToCharFunction("B.DATE_SHOW_TO")."				DATE_SHOW_TO,
					".$DB->DateToCharFunction("B.DATE_SHOW_FIRST")."			DATE_SHOW_FIRST,
					".$DB->DateToCharFunction("B.DATE_CREATE")."						DATE_CREATE,
					".$DB->DateToCharFunction("B.DATE_MODIFY")."						DATE_MODIFY,
					C.NAME																CONTRACT_NAME,
					T.NAME																TYPE_NAME
				FROM
					b_adv_banner B
				INNER JOIN b_adv_type T ON (T.SID = B.TYPE_SID)
				INNER JOIN b_adv_contract C ON (C.ID = B.CONTRACT_ID)
				INNER JOIN b_adv_contract_2_user CU ON (CU.CONTRACT_ID=C.ID and CU.USER_ID=$USER_ID)
				$left_join
				WHERE
				$strSqlSearch
				$strSqlOrder
				";
		}
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	// фиксируем клик по изображению баннера

	/**
	* <p>Метод фиксирует клик по баннеру в базе данных.</p>
	*
	*
	* @param int $BANNER_ID  ID баннера.
	*
	* @param int $CONTRACT_ID = false ID контракта которому принадлежит баннер; если задано false - ID
	* контракта берется из параметров баннера. Необязательный
	* параметр.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/advertising/classes/cadvbanner/click.php
	* @author Bitrix
	*/
	public static 	function Click($BANNER_ID)
	{
		$err_mess = (CAdvBanner::err_mess())."<br>Function: Click<br>Line: ";
		global $DB;
		$BANNER_ID = intval($BANNER_ID);
		if ($BANNER_ID<=0) return false;

		$strSql = "
			SELECT
				B.FIX_CLICK,
				B.CONTRACT_ID
			FROM
				b_adv_banner B
			WHERE
				B.ID = $BANNER_ID
			";
		$rsBanner = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($arBanner = $rsBanner->Fetch())
		{
			if ($arBanner["FIX_CLICK"]=="Y")
			{

				/********************
					обновим баннер
				********************/

				// параметры баннера
				$arFields = Array(
						"CLICK_COUNT"		=> "CLICK_COUNT + 1",
						"DATE_LAST_CLICK"	=> $DB->GetNowFunction(),
						);
				$rows = $DB->Update("b_adv_banner",$arFields,"WHERE ID = $BANNER_ID",$err_mess.__LINE__);
				if (intval($rows)>0)
				{
					foreach (getModuleEvents('advertising', 'onBannerClick', true) as $arEvent)
						executeModuleEventEx($arEvent, array($BANNER_ID, $arFields));

					// счетчик по дням
					$strSql = "
						UPDATE b_adv_banner_2_day SET
							CLICK_COUNT = CLICK_COUNT + 1
						WHERE
							BANNER_ID = $BANNER_ID
						and	DATE_STAT = ".$DB->GetNowDate()."
						";
					$z = $DB->Query($strSql, false, $err_mess.__LINE__);
					$rows = $z->AffectedRowsCount();
					if (intval($rows)<=0)
					{
						$strSql = "
							SELECT
								'x'
							FROM
								b_adv_banner_2_day
							WHERE
								BANNER_ID = $BANNER_ID
							and	DATE_STAT = ".$DB->GetNowDate()."
							";
						$w = $DB->Query($strSql, false, $err_mess.__LINE__);
						if (!$wr=$w->Fetch())
						{
							$strSql = "
								INSERT INTO b_adv_banner_2_day (DATE_STAT, BANNER_ID, CLICK_COUNT) VALUES (
									".$DB->GetNowDate().",
									$BANNER_ID,
									1)
								";
							$DB->Query($strSql, true, $err_mess.__LINE__);
						}
					}
				}

				/*************************
					обновим контракт
				*************************/

				$DONT_USE_CONTRACT = COption::GetOptionString("advertising", "DONT_USE_CONTRACT", "N");

				$CONTRACT_ID = intval($arBanner["CONTRACT_ID"]);
				if ($CONTRACT_ID>0 && $DONT_USE_CONTRACT == "N")
				{
					$arFields = Array("CLICK_COUNT" => "CLICK_COUNT + 1");
					$DB->Update("b_adv_contract",$arFields,"WHERE ID = $CONTRACT_ID",$err_mess.__LINE__);
				}
			}
		}
	}

	// формирует массив весов всех возможных баннеров для текущей страницы
public static 	function GetPageWeights_RS()
	{
		$err_mess = (CAdvBanner::err_mess())."<br>Function: GetPageWeights_RS<br>Line: ";
		global $APPLICATION, $DB, $USER;

		$stat_adv_id = intval($_SESSION["SESS_LAST_ADV_ID"]);
		$stat_country_id = trim($_SESSION["SESS_COUNTRY_ID"]);
		$stat_city_id = intval($_SESSION["SESS_CITY_ID"]);
		if($stat_city_id > 0 && CModule::IncludeModule('statistic'))
		{
			$rsCity = CCity::GetList(array(), array("=CITY_ID" => $stat_city_id));
			if($arCity = $rsCity->Fetch())
				$stat_region = $arCity["REGION_NAME"];
		}
		$new_guest = ($_SESSION["SESS_GUEST_NEW"]=="N") ? "N" : "Y";
		$url = CAdvBanner::GetCurUri();
		$arrTime = getdate();
		$weekday = strtoupper($arrTime["weekday"]);
		$hour = intval($arrTime["hours"]);
		$strUserGroups = $USER->GetUserGroupString();

		$DONT_USE_CONTRACT = COption::GetOptionString("advertising", "DONT_USE_CONTRACT", "N");

		if ($DONT_USE_CONTRACT == "N")
		{

			$strSql = "
				SELECT DISTINCT
					B.TYPE_SID,
					B.ID					BANNER_ID,
					B.WEIGHT				BANNER_WEIGHT,
					B.SHOWS_FOR_VISITOR,
					B.FIX_CLICK,
					B.FIX_SHOW,
					B.KEYWORDS				BANNER_KEYWORDS,
					".$DB->DateToCharFunction("B.DATE_SHOW_FIRST")."		DATE_SHOW_FIRST,
					".$DB->DateToCharFunction("B.DATE_SHOW_FROM")."			DATE_SHOW_FROM,
					".$DB->DateToCharFunction("B.DATE_SHOW_TO")."			DATE_SHOW_TO,
					B.FLYUNIFORM			FLYUNIFORM,
					B.MAX_SHOW_COUNT		MAX_SHOW_COUNT,
					B.SHOW_COUNT			SHOW_COUNT,
					C.ID					CONTRACT_ID,
					C.WEIGHT				CONTRACT_WEIGHT,
					C.KEYWORDS				CONTRACT_KEYWORDS
				FROM
					b_adv_type T

				INNER JOIN b_adv_banner B ON (
						B.ACTIVE='Y'
					and	B.TYPE_SID = T.SID
					and	B.STATUS_SID = 'PUBLISHED'
					and (B.FOR_NEW_GUEST is null or B.FOR_NEW_GUEST='$new_guest')
					and	(ifnull(B.MAX_SHOW_COUNT,0)>ifnull(B.SHOW_COUNT,0) or ifnull(B.MAX_SHOW_COUNT,0)=0)
					and (ifnull(B.MAX_CLICK_COUNT,0)>ifnull(B.CLICK_COUNT,0) or ifnull(B.MAX_CLICK_COUNT,0)=0)
					and (ifnull(B.MAX_VISITOR_COUNT,0)>ifnull(B.VISITOR_COUNT,0) or ifnull(B.MAX_VISITOR_COUNT,0)=0)
					and (B.DATE_SHOW_FROM<=now() or B.DATE_SHOW_FROM is null or length(B.DATE_SHOW_FROM)<=0)
					and (B.DATE_SHOW_TO>=now() or B.DATE_SHOW_TO is null or length(B.DATE_SHOW_TO)<=0))

				INNER JOIN b_adv_banner_2_site BS ON (
						BS.BANNER_ID = B.ID
					and BS.SITE_ID = '".SITE_ID."')

				INNER JOIN b_adv_contract C ON (
						C.ID = B.CONTRACT_ID
					and C.ACTIVE='Y'
					and	(ifnull(C.MAX_SHOW_COUNT,0)>ifnull(C.SHOW_COUNT,0) or ifnull(C.MAX_SHOW_COUNT,0)=0)
					and (ifnull(C.MAX_CLICK_COUNT,0)>ifnull(C.CLICK_COUNT,0) or ifnull(C.MAX_CLICK_COUNT,0)=0)
					and (ifnull(C.MAX_VISITOR_COUNT,0)>ifnull(C.VISITOR_COUNT,0) or ifnull(C.MAX_VISITOR_COUNT,0)=0)
					and (C.DATE_SHOW_FROM<=now() or C.DATE_SHOW_FROM is null or length(C.DATE_SHOW_FROM)<=0)
					and (C.DATE_SHOW_TO>=now() or C.DATE_SHOW_TO is null or length(C.DATE_SHOW_TO)<=0))

				INNER JOIN b_adv_contract_2_site CS ON (
						CS.CONTRACT_ID = B.CONTRACT_ID
					and CS.SITE_ID = '".SITE_ID."')

				INNER JOIN b_adv_contract_2_type CT ON (
						CT.CONTRACT_ID = C.ID
					and (CT.TYPE_SID = 'ALL' or CT.TYPE_SID = T.SID))

				INNER JOIN b_adv_banner_2_weekday BW ON (
						BW.BANNER_ID = B.ID
					and BW.C_WEEKDAY='".$DB->ForSql($weekday,10)."'
					and BW.C_HOUR = '$hour')

				INNER JOIN b_adv_contract_2_weekday CW ON (
						CW.CONTRACT_ID = C.ID
					and CW.C_WEEKDAY='".$DB->ForSql($weekday,10)."'
					and CW.C_HOUR = '$hour')

				LEFT JOIN b_adv_banner_2_group UG1 ON (
					(UG1.BANNER_ID = B.ID
					and UG1.GROUP_ID in (".$strUserGroups.") and UG1.GROUP_ID<>2)
				)

				LEFT JOIN b_adv_banner_2_page BP1 ON (
						BP1.BANNER_ID = B.ID
					and BP1.SHOW_ON_PAGE='Y')

				LEFT JOIN b_adv_banner_2_page BP2 ON (
						BP2.BANNER_ID = B.ID
					and BP2.SHOW_ON_PAGE='N'
					and '".$DB->ForSQL($url)."' like concat(BP2.PAGE, '%'))

				LEFT JOIN b_adv_contract_2_page	CP1 ON (
						CP1.CONTRACT_ID = C.ID
					and CP1.SHOW_ON_PAGE='Y')

				LEFT JOIN b_adv_contract_2_page CP2 ON (
						CP2.CONTRACT_ID = C.ID
					and CP2.SHOW_ON_PAGE='N'
					and '".$DB->ForSQL($url)."' like concat(CP2.PAGE, '%'))

				LEFT JOIN b_adv_banner_2_stat_adv	BA	ON BA.BANNER_ID = B.ID
				LEFT JOIN b_adv_banner_2_country BC ON BC.BANNER_ID = B.ID AND (
					(
						(B.STAT_TYPE is null OR length(B.STAT_TYPE)=0 OR B.STAT_TYPE='COUNTRY')
						AND BC.COUNTRY_ID='".$DB->ForSql($stat_country_id,2)."'
					) OR (
						B.STAT_TYPE='REGION'
						AND BC.COUNTRY_ID='".$DB->ForSql($stat_country_id,2)."'
						AND BC.REGION='".$DB->ForSql($stat_region)."'
					) OR (
						B.STAT_TYPE='CITY'
						AND BC.CITY_ID='".intval($stat_city_id)."'
					)
				)

				WHERE
					T.ACTIVE = 'Y'

				and (
					B.STAT_COUNT is null
					or B.STAT_COUNT = 0
					or BC.BANNER_ID is not null
				)
				and BP2.ID is null
				and CP2.ID is null
				and (BP1.ID is null or '".$DB->ForSQL($url)."' like concat(BP1.PAGE, '%'))
				and (CP1.ID is null or '".$DB->ForSQL($url)."' like concat(CP1.PAGE, '%'))
				and (BA.STAT_ADV_ID is null or BA.STAT_ADV_ID='".$stat_adv_id."')
				and (BC.COUNTRY_ID is null or BC.COUNTRY_ID='".$DB->ForSql($stat_country_id,2)."')

				and
				(
					(B.SHOW_USER_GROUP = 'Y' and UG1.GROUP_ID is not null)
					or
					(B.SHOW_USER_GROUP <> 'Y' and UG1.GROUP_ID is null)
				)

				ORDER BY B.TYPE_SID desc, C.ID desc
				";
		}
		else
		{
			$strSql = "
				SELECT DISTINCT
					B.TYPE_SID,
					B.ID					BANNER_ID,
					B.WEIGHT				BANNER_WEIGHT,
					B.SHOWS_FOR_VISITOR,
					B.FIX_CLICK,
					B.FIX_SHOW,
					B.KEYWORDS				BANNER_KEYWORDS
				FROM
					b_adv_type T

				INNER JOIN b_adv_banner B ON (
						B.ACTIVE='Y'
					and	B.TYPE_SID = T.SID
					and	B.STATUS_SID = 'PUBLISHED'
					and (B.FOR_NEW_GUEST is null or B.FOR_NEW_GUEST='$new_guest')
					and	(ifnull(B.MAX_SHOW_COUNT,0)>ifnull(B.SHOW_COUNT,0) or ifnull(B.MAX_SHOW_COUNT,0)=0)
					and (ifnull(B.MAX_CLICK_COUNT,0)>ifnull(B.CLICK_COUNT,0) or ifnull(B.MAX_CLICK_COUNT,0)=0)
					and (ifnull(B.MAX_VISITOR_COUNT,0)>ifnull(B.VISITOR_COUNT,0) or ifnull(B.MAX_VISITOR_COUNT,0)=0)
					and (B.DATE_SHOW_FROM<=now() or B.DATE_SHOW_FROM is null or length(B.DATE_SHOW_FROM)<=0)
					and (B.DATE_SHOW_TO>=now() or B.DATE_SHOW_TO is null or length(B.DATE_SHOW_TO)<=0))

				INNER JOIN b_adv_banner_2_site BS ON (
						BS.BANNER_ID = B.ID
					and BS.SITE_ID = '".SITE_ID."')

				INNER JOIN b_adv_banner_2_weekday BW ON (
						BW.BANNER_ID = B.ID
					and BW.C_WEEKDAY='".$DB->ForSql($weekday,10)."'
					and BW.C_HOUR = '$hour')

				LEFT JOIN b_adv_banner_2_group UG1 ON (
					(UG1.BANNER_ID = B.ID
					and UG1.GROUP_ID in (".$strUserGroups.") and UG1.GROUP_ID<>2)
				)

				LEFT JOIN b_adv_banner_2_page BP1 ON (
						BP1.BANNER_ID = B.ID
					and BP1.SHOW_ON_PAGE='Y')

				LEFT JOIN b_adv_banner_2_page BP2 ON (
						BP2.BANNER_ID = B.ID
					and BP2.SHOW_ON_PAGE='N'
					and '".$DB->ForSQL($url)."' like concat(BP2.PAGE, '%'))

				LEFT JOIN b_adv_banner_2_stat_adv	BA	ON BA.BANNER_ID = B.ID
				LEFT JOIN b_adv_banner_2_country BC ON BC.BANNER_ID = B.ID AND (
					(
						(B.STAT_TYPE is null OR length(B.STAT_TYPE)=0 OR B.STAT_TYPE='COUNTRY')
						AND BC.COUNTRY_ID='".$DB->ForSql($stat_country_id,2)."'
					) OR (
						B.STAT_TYPE='REGION'
						AND BC.COUNTRY_ID='".$DB->ForSql($stat_country_id,2)."'
						AND BC.REGION='".$DB->ForSql($stat_region)."'
					) OR (
						B.STAT_TYPE='CITY'
						AND BC.CITY_ID='".intval($stat_city_id)."'
					)
				)

				WHERE
					T.ACTIVE = 'Y'

				and (
					B.STAT_COUNT is null
					or B.STAT_COUNT = 0
					or BC.BANNER_ID is not null
				)
				and BP2.ID is null
				and (BP1.ID is null or '".$DB->ForSQL($url)."' like concat(BP1.PAGE, '%'))
				and (BA.STAT_ADV_ID is null or BA.STAT_ADV_ID='".$stat_adv_id."')
				and (BC.COUNTRY_ID is null or BC.COUNTRY_ID='".$DB->ForSql($stat_country_id,2)."')
				and
				(
					(B.SHOW_USER_GROUP = 'Y' and UG1.GROUP_ID is not null)
					or
					(B.SHOW_USER_GROUP <> 'Y' and UG1.GROUP_ID is null)
				)
				ORDER BY B.TYPE_SID desc";
		}
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $rs;
	}

	// периодически вызываемая функция очищающая устаревшие данные по динамике баннера по дням
public static 	function CleanUpDynamics()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = (CAdvBanner::err_mess())."<br>Function: CleanUpDynamics<br>Line: ";
		global $DB;
		$DAYS = intval(COption::GetOptionString("advertising", "BANNER_DAYS"));
		$strSql = "DELETE FROM b_adv_banner_2_day WHERE to_days(now())-to_days(DATE_STAT)>=$DAYS";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		$strSql = "OPTIMIZE TABLE b_adv_banner_2_day";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		return "CAdvBanner::CleanUpDynamics();";
	}

public static 	function GetDynamicList_SQL($strSqlSearch)
	{
		global $DB;
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")."		DATE_STAT,
				DAYOFMONTH(D.DATE_STAT)									DAY,
				MONTH(D.DATE_STAT)										MONTH,
				YEAR(D.DATE_STAT)										YEAR,
				D.SHOW_COUNT,
				D.CLICK_COUNT,
				D.VISITOR_COUNT,
				D.BANNER_ID,
				B.CONTRACT_ID,
				B.GROUP_SID,
				C.NAME													CONTRACT_NAME,
				C.SORT													CONTRACT_SORT,
				B.NAME													BANNER_NAME,
				B.TYPE_SID												BANNER_TYPE_SID
			FROM
				b_adv_banner_2_day D
			INNER JOIN b_adv_banner B ON (D.BANNER_ID = B.ID)
			INNER JOIN b_adv_contract C ON (B.CONTRACT_ID = C.ID)
			WHERE
			$strSqlSearch
			ORDER BY
				D.DATE_STAT, B.CONTRACT_ID, B.GROUP_SID, D.BANNER_ID
			";
		return $strSql;
	}
}

/*****************************************************************
					Класс "Тип баннера"
*****************************************************************/


/**
 * Класс для работы с типами баннеров. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/advertising/classes/cadvtype/index.php
 * @author Bitrix
 */
class CAdvType extends CAdvType_all
{
public static 	function err_mess()
	{
		$module_id = "advertising";
		return "<br>Module: ".$module_id."<br>Class: CAdvType<br>File: ".__FILE__;
	}
}
