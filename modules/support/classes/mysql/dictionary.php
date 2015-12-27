<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/classes/general/dictionary.php");


/**
 * <b>CTicketDictionary</b> - класс для работы со справочником обращений. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/support/classes/cticketdictionary/index.php
 * @author Bitrix
 */
class CTicketDictionary extends CAllTicketDictionary
{
	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." <br>Class: CTicketDictionary<br>File: ".__FILE__;
	}

	
	/**
	* <p>Метод предназначен для получения списка записей справочника.</p>
	*
	*
	* @param varchar &$by  Идентификатор позволяющий задать имя поля для сортировки.
	* Допустимы следующие значения: <ul> <li>s_id - по ID </li> <li>s_c_sort - по
	* указанному порядку сортировки </li> <li>s_sid - по символьному коду </li>
	* <li>s_lis - по языку </li> <li>s_name - по имени </li> <li>s_responsible - по ID
	* ответственного </li> <li>s_dropdown - по указанному порядку сортировки и
	* по имени </li> </ul>
	*
	* @param varchar &$order  Порядок сортировки. Допустимы следующие значения: <ul> <li>desc - по
	* убыванию (значение по умолчанию) </li> <li>asc - по возрастанию </li> </ul>
	*
	* @param array $arFilter = array() Массив для фильтрации значений. В массиве допустимы следующие
	* индексы: <ul> <li>ID - ID записи (допускается сложная логика) </li> <li>SID -
	* символьный код (допускается сложная логика) </li> <li>LID - ID сайта </li>
	* <li>TYPE - тип записи: "C" - категория, "K" - критичность, "S" - статус, "M" -
	* оценка ответов, "F" - часто используемые ответы, "SR" - источник </li>
	* <li>NAME - имя (допускается сложная логика) </li> <li>RESPONSIBLE_ID - ID
	* ответственного </li> <li>RESPONSIBLE - ответственный, поиск осуществляется
	* по ID пользователя, логину, имени, фамилии (допускается сложная
	* логика) </li> <li>DEFAULT - флаг "Выбирать по умолчанию"; "Y" - да, "N" - нет </li>
	* </ul> Необязательный параметр.
	*
	* @param boolean &$is_filtered  Переменная возвращающая true в том случае если список результатов
	* отфильтрован по какому либо критерию; либо false в противном случае.
	* С версии 12.0.0 изменен на <b>isFiltered</b>.
	*
	* @return record <p>Возвращается объект CDBResult.</p> </h<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $FilterArr = Array(
	* 	"find_id",
	* 	"find_lid",
	* 	"find_type",
	* 	"find_name",
	* 	"find_sid",
	* 	"find_responsible",
	* 	"find_responsible_id",
	* 	"find_default"
	* 	);
	* if (strlen($set_filter)&gt;0) InitFilterEx($FilterArr,"TICKET_DICTIONARY_LIST","set"); else InitFilterEx($FilterArr,"TICKET_DICTIONARY_LIST","get");
	* if (strlen($del_filter)&gt;0) DelFilterEx($FilterArr,"TICKET_DICTIONARY_LIST");
	* $arFilter = Array(
	* 	"ID"				=&gt; $find_id,
	* 	"LID"				=&gt; $find_lid,
	* 	"TYPE"				=&gt; $find_type,
	* 	"NAME"				=&gt; $find_name,
	* 	"SID"				=&gt; $find_sid,
	* 	"RESPONSIBLE_ID"	=&gt; $find_responsible_id,
	* 	"RESPONSIBLE"		=&gt; $find_responsible,
	* 	"DEFAULT"			=&gt; $find_default
	* 	);
	* $tdic = <b>CTicketDictionary::GetList</b>($by, $order, $arFilter, $is_filtered);
	* ?&gt;
	* 
	* 
	* //получим список статусов для текущего сайта $arFilter = Array(
	* 	"LID" =&gt; SITE_ID,
	* 	"TYPE" =&gt; "S",
	* );
	* 
	* //сортировка задется через переменные
	* $by = "s_c_sort";
	* $sort = "asc";
	* 
	* //отбор и вывод
	* $rsStatus = CTicketDictionary::GetList($by, $sort, $arFilter, $is_filtered); while($arRes = $rsStatus-&gt;GetNext()) {
	* 	echo $arRes["NAME"], "";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticketdictionary/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$isFiltered)
	{
		$err_mess = (CTicketDictionary::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$leftJoinSite = "";
		$leftJoinUser = "";
		if (is_array($arFilter))
		{
			$filterKeys = array_keys($arFilter);
			$filterKeysCount = count($filterKeys);
			for ($i=0; $i<$filterKeysCount; $i++)
			{
				$key = $filterKeys[$i];
				$val = $arFilter[$filterKeys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$match_value_set = (in_array($key."_EXACT_MATCH", $filterKeys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
					case "SID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.".$key, $val, $match);
						break;
					case "SITE":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DS.SITE_ID", $val, $match);
						$leftJoinSite .= "LEFT JOIN b_ticket_dictionary_2_site DS ON (D.ID = DS.DICTIONARY_ID)";
						$select_user = ", DS.SITE_ID ";
						break;
					case "TYPE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.C_TYPE", $val, $match);
						break;
					case "NAME":
					case "DESCR":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("D.".$key, $val, $match);
						break;
					case "RESPONSIBLE_ID":
						if (intval($val)>0) $arSqlSearch[] = "D.RESPONSIBLE_USER_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(D.RESPONSIBLE_USER_ID is null or D.RESPONSIBLE_USER_ID=0)";
						break;
					case "RESPONSIBLE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("D.RESPONSIBLE_USER_ID, U.LOGIN, U.LAST_NAME, U.NAME", $val, $match);
						$select_user = ",
							U.LOGIN														RESPONSIBLE_LOGIN,
							concat(ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,''))		RESPONSIBLE_NAME
							";
						$leftJoinUser = "LEFT JOIN b_user U ON (U.ID = D.RESPONSIBLE_USER_ID)";
						break;
					case "DEFAULT":
						$arSqlSearch[] = ($val=="Y") ? "D.SET_AS_DEFAULT='Y'" : "D.SET_AS_DEFAULT='N'";
						break;
					case "LID":
					case "FIRST_SITE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.FIRST_SITE_ID",$val,$match);
						break;
				}
			}
		}

		if ($by == "s_id")
		{
			$strSqlOrder = "D.ID";
		}
		elseif ($by == "s_c_sort")
		{
			$strSqlOrder = "D.C_SORT";
		}
		elseif ($by == "s_sid")
		{
			$strSqlOrder = "D.SID";
		}
		elseif ($by == "s_lid")
		{
			$strSqlOrder = "D.FIRST_SITE_ID";
		}
		elseif ($by == "s_name")
		{
			$strSqlOrder = "D.NAME";
		}
		elseif ($by == "s_responsible")
		{
			$strSqlOrder = "D.RESPONSIBLE_USER_ID";
		}
		elseif ($by == "s_dropdown")
		{
			$strSqlOrder = "D.C_SORT, D.ID, D.NAME";
		}
		else
		{
			$by = "s_c_sort";
			$strSqlOrder = "D.C_SORT";
		}
		if ($order!="desc")
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}
		else
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				D.*,
				D.FIRST_SITE_ID						LID,
				D.ID								REFERENCE_ID,
				D.NAME								REFERENCE
				$select_user
			FROM
				b_ticket_dictionary D
			$leftJoinUser
			$leftJoinSite
			WHERE
			$strSqlSearch
			GROUP BY
				D.ID
			ORDER BY
				case D.C_TYPE
					when 'C'	then '1'
					when 'F'	then '2'
					when 'S'	then '3'
					when 'M'	then '4'
					when 'K'	then '5'
					when 'SR'	then '6'
					when 'D'	then '7'
					else ''	end,
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$isFiltered = (IsFiltered($strSqlSearch));
		return $res;
	}
}

?>