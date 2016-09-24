<?

/**
 * <b>CIBlock</b> - класс для работы с информационными блоками
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/index.php
 * @author Bitrix
 */
class CIBlock extends CAllIBlock
{
	///////////////////////////////////////////////////////////////////
	// List of blocks
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Возвращает список информационных блоков по фильтру <i>arFilter</i> отсортированный в порядке <i>arOrder</i>. Нестатический метод.</p>
	*
	*
	* @param array $arOrder = Array("SORT"=>"ASC") Массив для сортировки результата. Содержит пары "<i>поле
	* сортировки</i>"=&gt;"<i>направление сортировки</i>". 		Поле для
	* сортировки может принимать значения: 		          <ul> <li> <b>id </b> - код
	* инфоблока;</li>          		            <li> <b>iblock_type </b> - тип инфоблоков;</li>          		
	*            <li> <b>name </b> - название инфоблока;</li>          		            <li> <b>active </b> -
	* активность;</li>                     <li> <b>code</b> - символьный код;</li>          		         
	*   <li> <b>sort </b> - индекс сортировки;</li>          		            <li> <b>element_cnt </b> -
	* количество элементов (только если <i>bIncCnt</i> = true);</li>          		            <li>
	* <b>timestamp_x </b> - дата последнего изменения.</li>          		</ul>
	*
	* @param array $arFilter = Array() Массив вида <i> array("фильтруемое поле"=&gt;"значение фильтра" [, ...])</i>.
	* 		Фильтруемое поле может принимать значения: 		          <ul> <li> <i>ACTIVE</i> -
	* фильтр по активности (Y|N);</li>          		            <li> <i>NAME</i> - по названию
	* (можно искать по шаблону [%_]);</li>          		            <li> <i>EXTERNAL_ID</i>, <i>XML_ID </i>
	* - по внешнему коду (можно искать по шаблону [%_]);</li>          		            <li>
	* <i>SITE_ID</i> - по сайту;</li>          		            <li> <i>TYPE</i> - по типу инфоблоков
	* (можно искать по шаблону [%_]);</li>          		            <li> <i>CODE</i> - по
	* символьному коду (можно искать по шаблону [%_]);</li>          		            <li>
	* <i>ID</i> - по коду;</li>                     <li> <i>VERSION</i> - по флагу хранения
	* значений свойств элементов инфоблока;</li>          		            <li>
	* <i>SOCNET_GROUP_ID</i> - по идентификатору группы социальной сети в которой
	* используется инфоблок;</li>           <li> <i>CNT_ACTIVE</i> - только если <i>bIncCnt</i>
	* = true. Если значение Y, то при подсчете элементов будут учитываться
	* только активные элементы, при любом другом значении все
	* элементы;</li>          		            <li> <i>CNT_ALL</i> - только если <i>bIncCnt</i> = true.
	* Если значение Y, то при подсчете элементов будут учитываться и те
	* элементы, которые ещё не были опубликованы. При любом другом
	* значении все элементы;</li>                     <li> <i>MIN_PERMISSION</i> - фильтр по
	* правам доступа, по умолчанию принимает <i>R</i> (уровень доступа
	* <i>Чтение</i>).</li>                     <li> <i>CHECK_PERMISSIONS</i> - если "N", то права на
	* доступ не проверяются.              <br><br>            Если проверка прав не
	* нужна, то для ускорения запроса следует указывать значение "N".
	* Кроме того, если не указать данный параметр в фильтре или при
	* создании инфоблока не изменить параметр по умолчанию "нет
	* доступа", то результат выдачи обычному пользователю будет
	* пустым.</li>          		</ul>        		Перед названием фильтруемого поля можно
	* указать тип фильтрации: 		          <ul> <li>"!" - не равно</li>          		           
	* <li>"&lt;" - меньше</li>          		            <li>"&lt;=" - меньше либо равно</li>          		   
	*         <li>"&gt;" - больше</li>          		            <li>"&gt;=" - больше либо равно</li>       
	*   		</ul>        		Все фильтруемые поля кроме (CHECK_PERMISSIONS, MIN_PERMISSION, CNT_ALL и
	* CNT_ACTIVE) могут содержать перед названием <a
	* href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2683" >тип проверки
	* фильтра</a>.          <br>        "<i>значения фильтра</i>" - одиночное значение
	* или массив.          <br><br>        Необязательное. По умолчанию записи не
	* фильтруются.
	*
	* @param bool $bIncCnt = false Возвращать ли количество элементов в информационном блоке в поле
	* <i>ELEMENT_CNT</i>. Необязательный параметр, по умолчанию равен false.
	*
	* @return CDBResult <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult.</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* <b>Примечание:</b> при копировании кода в свой проект рекомендуется убрать необязательный параметр bIncCnt (если он не используется), чтобы избежать проблем с производительностью.&lt;?<br>// выберем все активные информационные блоки для текущего сайта типа catalog<br>// у которых символьный код не my_products, со счетчиком активных элементов.<br>$res = CIBlock::GetList(<br>	Array(), <br>	Array(<br>		'TYPE'=&gt;'catalog', <br>		'SITE_ID'=&gt;SITE_ID, <br>		'ACTIVE'=&gt;'Y', <br>		"CNT_ACTIVE"=&gt;"Y", <br>		"!CODE"=&gt;'my_products'<br>	), true<br>);<br>while($ar_res = $res-&gt;Fetch())<br>{<br>	echo $ar_res['NAME'].': '.$ar_res['ELEMENT_CNT'];<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li>     <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fiblock">Поля CIBlock</a> </li>  </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder=Array("SORT"=>"ASC"), $arFilter=Array(), $bIncCnt = false)
	{
		global $DB, $USER;

		$strSqlSearch = "";
		$bAddSites = false;
		foreach($arFilter as $key => $val)
		{
			$res = CIBlock::MkOperationFilter($key);
			$key = strtoupper($res["FIELD"]);
			$cOperationType = $res["OPERATION"];

			switch($key)
			{
			case "ACTIVE":
				$sql = CIBlock::FilterCreate("B.ACTIVE", $val, "string_equal", $cOperationType);
				break;
			case "LID":
			case "SITE_ID":
				$sql = CIBlock::FilterCreate("BS.SITE_ID", $val, "string_equal", $cOperationType);
				if(strlen($sql))
					$bAddSites = true;
				break;
			case "NAME":
			case "CODE":
			case "XML_ID":
			case "PROPERTY_INDEX":
				$sql = CIBlock::FilterCreate("B.".$key, $val, "string", $cOperationType);
				break;
			case "EXTERNAL_ID":
				$sql = CIBlock::FilterCreate("B.XML_ID", $val, "string", $cOperationType);
				break;
			case "TYPE":
				$sql = CIBlock::FilterCreate("B.IBLOCK_TYPE_ID", $val, "string", $cOperationType);
				break;
			case "ID":
			case "VERSION":
			case "SOCNET_GROUP_ID":
				$sql = CIBlock::FilterCreate("B.".$key, $val, "number", $cOperationType);
				break;
			default:
				$sql = "";
				break;
			}

			if(strlen($sql))
				$strSqlSearch .= " AND  (".$sql.") ";
		}

		$bCheckPermissions =
			!array_key_exists("CHECK_PERMISSIONS", $arFilter)
			|| $arFilter["CHECK_PERMISSIONS"] !== "N"
			|| array_key_exists("OPERATION", $arFilter)
		;
		$bIsAdmin = is_object($USER) && $USER->IsAdmin();
		if($bCheckPermissions && !$bIsAdmin)
		{
			$min_permission = (strlen($arFilter["MIN_PERMISSION"])==1) ? $arFilter["MIN_PERMISSION"] : "R";
			if(is_object($USER))
			{
				$iUserID = intval($USER->GetID());
				$strGroups = $USER->GetGroups();
				$bAuthorized = $USER->IsAuthorized();
			}
			else
			{
				$iUserID = 0;
				$strGroups = "2";
				$bAuthorized = false;
			}

			$stdPermissions = "
				SELECT IBLOCK_ID
				FROM b_iblock_group IBG
				WHERE IBG.GROUP_ID IN (".$strGroups.")
				AND IBG.PERMISSION >= '".$min_permission."'
			";
			if(!defined("ADMIN_SECTION"))
				$stdPermissions .= "
					AND (IBG.PERMISSION='X' OR B.ACTIVE='Y')
				";

			if (strlen($arFilter["OPERATION"]) > 0)
				$operation  = "'".$DB->ForSql($arFilter["OPERATION"])."'";
			elseif($min_permission >= "X")
				$operation = "'iblock_edit'";
			elseif($min_permission >= "U")
				$operation = "'element_edit'";
			elseif($min_permission >= "S")
				$operation = "'iblock_admin_display'";
			else
				$operation = "'section_read', 'element_read', 'section_element_bind', 'section_section_bind'";

			if($operation)
			{
				$acc = new CAccess;
				$acc->UpdateCodes();

				$extPermissions = "
					SELECT IBLOCK_ID
					FROM b_iblock_right IBR
					INNER JOIN b_task_operation T ON T.TASK_ID = IBR.TASK_ID
					INNER JOIN b_operation O ON O.ID = T.OPERATION_ID
					".($iUserID > 0? "LEFT": "INNER")." JOIN b_user_access UA ON UA.ACCESS_CODE = IBR.GROUP_CODE AND UA.USER_ID = ".$iUserID."
					WHERE IBR.ENTITY_TYPE = 'iblock'
					AND O.NAME in (".$operation.")
					".($bAuthorized? "AND (UA.USER_ID IS NOT NULL OR IBR.GROUP_CODE = 'AU')": "")."
				";
				$sqlPermissions = "AND (
					B.ID IN ($stdPermissions)
					OR (B.RIGHTS_MODE = 'E' AND B.ID IN ($extPermissions))
				)";
			}
			else
			{
				$sqlPermissions = "AND (
					B.ID IN ($stdPermissions)
				)";
			}
		}
		else
		{
			$sqlPermissions = "";
		}

		if ($bAddSites)
			$sqlJoinSites = "LEFT JOIN b_iblock_site BS ON B.ID=BS.IBLOCK_ID
					LEFT JOIN b_lang L ON L.LID=BS.SITE_ID";
		else
			$sqlJoinSites = "INNER JOIN b_lang L ON L.LID=B.LID";

		if(!$bIncCnt)
		{
			$strSql = "
				SELECT DISTINCT
					B.*
					,B.XML_ID as EXTERNAL_ID
					,".$DB->DateToCharFunction("B.TIMESTAMP_X")." as TIMESTAMP_X
					,L.DIR as LANG_DIR
					,L.SERVER_NAME
				FROM
					b_iblock B
					".$sqlJoinSites."
				WHERE 1 = 1
					".$sqlPermissions."
					".$strSqlSearch."
			";
		}
		else
		{
			$strSql = "
				SELECT
					B.*
					,B.XML_ID as EXTERNAL_ID
					,".$DB->DateToCharFunction("B.TIMESTAMP_X")." as TIMESTAMP_X
					,L.DIR as LANG_DIR
					,L.SERVER_NAME
					,COUNT(DISTINCT BE.ID) as ELEMENT_CNT
				FROM
					b_iblock B
					".$sqlJoinSites."
					LEFT JOIN b_iblock_element BE ON (BE.IBLOCK_ID=B.ID
						AND (
							(BE.WF_STATUS_ID=1 AND BE.WF_PARENT_ELEMENT_ID IS NULL )
							".($arFilter["CNT_ALL"]=="Y"? " OR BE.WF_NEW='Y' ":"")."
						)
						".($arFilter["CNT_ACTIVE"]=="Y"?
						"AND BE.ACTIVE='Y'
						AND (BE.ACTIVE_TO >= ".$DB->CurrentDateFunction()." OR BE.ACTIVE_TO IS NULL)
						AND (BE.ACTIVE_FROM <= ".$DB->CurrentDateFunction()." OR BE.ACTIVE_FROM IS NULL)
						":
						"")."
					)
				WHERE 1 = 1
					".$sqlPermissions."
					".$strSqlSearch."
				GROUP BY B.ID
			";
		}

		$arSqlOrder = Array();
		if(is_array($arOrder))
		{
			foreach($arOrder as $by=>$order)
			{
				$by = strtolower($by);
				$order = strtolower($order);
				if ($order!="asc")
					$order = "desc";

				if ($by == "id") $arSqlOrder[$by] = " B.ID ".$order." ";
				elseif ($by == "lid") $arSqlOrder[$by] = " B.LID ".$order." ";
				elseif ($by == "iblock_type") $arSqlOrder[$by] = " B.IBLOCK_TYPE_ID ".$order." ";
				elseif ($by == "name") $arSqlOrder[$by] = " B.NAME ".$order." ";
				elseif ($by == "active") $arSqlOrder[$by] = " B.ACTIVE ".$order." ";
				elseif ($by == "sort") $arSqlOrder[$by] = " B.SORT ".$order." ";
				elseif ($by == "code") $arSqlOrder[$by] = " B.CODE ".$order." ";
				elseif ($bIncCnt && $by == "element_cnt") $arSqlOrder[$by] = " ELEMENT_CNT ".$order." ";
				else
				{
					$by = "timestamp_x";
					$arSqlOrder[$by] = " B.TIMESTAMP_X ".$order." ";
				}
			}
		}

		if(count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(",", $arSqlOrder);
		else
			$strSqlOrder = "";

		$res = $DB->Query($strSql.$strSqlOrder, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $res;
	}

	public static function _Upper($str)
	{
		return $str;
	}

	public static function _Add($ID)
	{
		global $DB;
		$err_mess = "FILE: ".__FILE__."<br>LINE: ";
		$ID = intval($ID);

		if(defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE) > 0)
		{
			$DB->Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", true);
		}
		$strSql = "
			CREATE TABLE IF NOT EXISTS b_iblock_element_prop_s".$ID." (
				IBLOCK_ELEMENT_ID 	int(11) not null REFERENCES b_iblock_element(ID),
				primary key (IBLOCK_ELEMENT_ID)
			)
		";
		$rs = $DB->DDL($strSql, false, $err_mess.__LINE__);
		$strSql = "
			CREATE TABLE IF NOT EXISTS b_iblock_element_prop_m".$ID." (
				ID			int(11) not null auto_increment,
				IBLOCK_ELEMENT_ID 	int(11) not null REFERENCES b_iblock_element(ID),
				IBLOCK_PROPERTY_ID	int(11) not null REFERENCES b_iblock_property(ID),
				VALUE			text	not null,
				VALUE_ENUM 		int(11),
				VALUE_NUM 		numeric(18,4),
				DESCRIPTION 		VARCHAR(255) NULL,
				PRIMARY KEY (ID),
				INDEX ix_iblock_elem_prop_m".$ID."_1(IBLOCK_ELEMENT_ID,IBLOCK_PROPERTY_ID),
				INDEX ix_iblock_elem_prop_m".$ID."_2(IBLOCK_PROPERTY_ID),
				INDEX ix_iblock_elem_prop_m".$ID."_3(VALUE_ENUM,IBLOCK_PROPERTY_ID)
			)
		";
		if($rs)
			$rs = $DB->DDL($strSql, false, $err_mess.__LINE__);
		return $rs;
	}

	public static function _Order($by, $order, $default_order, $nullable = true)
	{
		$o = parent::_Order($by, $order, $default_order, $nullable);
		//$o[0] - bNullsFirst
		//$o[1] - asc|desc
		if($o[0])
		{
			if($o[1] == "asc")
			{
				return $by." asc";
			}
			else
			{
				return "length(".$by.")>0 asc, ".$by." desc";
			}
		}
		else
		{
			if($o[1] == "asc")
			{
				return "length(".$by.")>0 desc, ".$by." asc";
			}
			else
			{
				return $by." desc";
			}
		}
	}

	public static function _NotEmpty($column)
	{
		return "if(".$column." is null, 0, 1)";
	}
}