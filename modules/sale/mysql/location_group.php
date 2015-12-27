<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/location_group.php");

use Bitrix\Sale\Location;


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/index.php
 * @author Bitrix
 */
class CSaleLocationGroup extends CAllSaleLocationGroup
{
	
	/**
	* <p>Метод возвращает набор групп местоположений, удовлетворяющих фильтру arFilter. Группы отсортированы в соответствии с массивом arOrder. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = Array("NAME"=>"ASC") Ассоциативный массив для указания сортировки результирующего
	* набора групп. Каждая пара ключ-значение массива применяется
	* последовательно. Ключами являются названия полей для сортировки,
	* а значениями - направления сортировки.<br><br> Допустимые ключи: <ul>
	* <li> <b>ID</b> - код группы местоположений;</li> <li> <b>NAME</b> - название
	* группы;</li> <li> <b>SORT</b> - индекс сортировки.</li> </ul> Допустимые
	* значения: <ul> <li> <b>ASC</b> - по возрастанию;</li> <li> <b>DESC</b> - по
	* убыванию.</li> </ul>
	*
	* @param array $arFilter = Array() Фильтр представляет собой ассоциативный массив, в котором
	* ключами являются названия параметров группы, а значениями -
	* условия на значения<br><br> Допустимые ключи: <ul> <li> <b>ID</b> - код группы
	* местоположения.</li> </ul>
	*
	* @param string $strLang = LANGUAGE_ID Код языка для языкозависимых параметров. По умолчанию равен
	* текущему языку.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы с ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код группы местоположений.</td> </tr> <tr>
	* <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr> <td>NAME</td> <td>Название
	* группы.</td> </tr> <tr> <td>LID</td> <td>Язык названия.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;select name="LOCATION2" size="5" multiple&gt;
	*    &lt;?
	*    $db_vars = CSaleLocationGroup::GetList(Array("NAME"=&gt;"ASC"), array(), LANGUAGE_ID);
	*    while ($vars = $db_vars-&gt;Fetch()):
	*       ?&gt;
	*       &lt;option value="&lt;?= $vars["ID"]?&gt;"&gt;&lt;?= htmlspecialchars($vars["NAME"])?&gt;&lt;/option&gt;
	*       &lt;?
	*    endwhile;
	*    ?&gt;
	* &lt;/select&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__getlist.27441ea3.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("NAME"=>"ASC"), $arFilter=Array(), $strLang = LANGUAGE_ID)
	{
		global $DB;
		$arSqlSearch = Array();
		$arSqlSearchFrom = array();

		if(!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilterKey = count($filter_keys);
		for($i=0; $i < $countFilterKey; $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(ToUpper($key))
			{
			case "ID":
				$arSqlSearch[] = "LG.ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
				break;
			case "LOCATION":

				if(CSaleLocation::isLocationProMigrated())
				{
					try
					{
						$class = self::CONN_ENTITY_NAME.'Table';
						$arSqlSearch[] = "	LG.ID ".($bInvert ? 'not' : '')." in (".$class::getConnectedEntitiesQuery(IntVal($val), 'id', array('select' => array('ID'))).") ";
					}
					catch(Exception $e)
					{
					}
				}
				else
				{
					$arSqlSearch[] = "LG.ID = L2LG.LOCATION_GROUP_ID AND L2LG.LOCATION_GROUP_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					$arSqlSearchFrom[] = ", b_sale_location2location_group L2LG ";
				}

				break;
			}
		}

		$strSqlSearch = "";
		$countSqlSearch = count($arSqlSearch);
		for($i=0; $i < $countSqlSearch; $i++)
		{
			$strSqlSearch .= " AND ";
			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSqlSearchFrom = "";
		$countSqlSearchForm = count($arSqlSearchFrom);
		for($i=0; $i < $countSqlSearchForm; $i++)
		{
			$strSqlSearchFrom .= " ".$arSqlSearchFrom[$i]." ";
		}

		$strSql =
			"SELECT DISTINCT LG.ID, LG.SORT, LGL.NAME, LGL.LID ".
			"FROM (b_sale_location_group LG ".
			"	".$strSqlSearchFrom.") ".
			"	LEFT JOIN b_sale_location_group_lang LGL ON (LG.ID = LGL.LOCATION_GROUP_ID AND LGL.LID = '".$DB->ForSql($strLang, 2)."') ".
			"WHERE 1 = 1 ".
			"	".$strSqlSearch." ";

		$arSqlOrder = Array();
		foreach ($arOrder as $by=>$order)
		{
			$by = ToUpper($by);
			$order = ToUpper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "ID") $arSqlOrder[] = " LG.ID ".$order." ";
			elseif ($by == "NAME") $arSqlOrder[] = " LGL.NAME ".$order." ";
			else
			{
				$arSqlOrder[] = " LG.SORT ".$order." ";
				$by = "SORT";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i < $countSqlOrder; $i++)
		{
			if ($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ", ";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	
	/**
	* <p>Метод возвращает параметры группы местоположений с кодом ID. Языкозависимые параметры возвращаются для языка с кодом strLang. Метод динамичный. </p>
	*
	*
	* @param int $ID  Код группы местоположений.
	*
	* @param string $strLang = LANGUAGE_ID Код языка для языкозависимых параметров. По умолчанию равен
	* текущему языку.
	*
	* @return array <p>Возвращается ассоциативный массив с ключами:</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* группы местоположений.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td>
	* </tr> <tr> <td>NAME</td> <td>Название группы.</td> </tr> <tr> <td>LID</td> <td>Язык
	* названия.</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__getbyid.33dc4ad9.php
	* @author Bitrix
	*/
	public static function GetByID($ID, $strLang = LANGUAGE_ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT LG.ID, LG.SORT, LGL.NAME, LGL.LID ".
			"FROM b_sale_location_group LG ".
			"	LEFT JOIN b_sale_location_group_lang LGL ON (LG.ID = LGL.LOCATION_GROUP_ID AND LGL.LID = '".$DB->ForSql($strLang, 2)."') ".
			"WHERE LG.ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	* <p>Метод добавляет новую группу местоположений с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Массив значений параметров группы местоположений, ключами в
	* котором являются имена параметров.<br><br> Допустимые ключи: <ul> <li>
	* <b>SORT</b> - индекс сортировки;</li> <li> <b>LOCATION_ID</b> - массив кодов
	* местоположений, которые входят в эту группу;</li> <li> <b>LANG</b> - массив
	* языкозависимых параметров группы, каждый элемент которого имеет
	* вид <pre class="syntax"> array("LID"=&gt;"язык параметров", "NAME"=&gt;"Название
	* группы")</pre> </li> </ul>
	*
	* @return int <p>Возвращается код добавленной группы или <i>false</i> в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "SORT" =&gt; 150,
	*    "LOCATION_ID" =&gt; array(12, 34, 35, 36, 37),
	*    "LANG" =&gt; array(
	*       array("LID" =&gt; "ru", "NAME" =&gt; "Группа 1"),
	*       array("LID" =&gt; "en", "NAME" =&gt; "Group 1")
	*    )
	* );<br>
	* $ID = CSaleLocationGroup::Add($arFields);
	* if (IntVal($ID)&lt;=0)
	*    echo "Ошибка добавления группы";<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__add.3520254b.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleLocationGroup::CheckFields("ADD", $arFields))
			return false;

		// make IX_B_SALE_LOC_GROUP_CODE feel happy
		$arFields['CODE'] = 'randstr'.rand(999, 999999);

		$db_events = GetModuleEvents("sale", "OnBeforeLocationGroupAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sale_location_group", $arFields);
		$strSql =
			"INSERT INTO b_sale_location_group(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";

		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		// make IX_B_SALE_LOC_CODE feel happy
		Location\GroupTable::update($ID, array('CODE' => $ID));

		$countFieldLang = count($arFields["LANG"]);
		for ($i = 0; $i < $countFieldLang; $i++)
		{
			$arInsert = $DB->PrepareInsert("b_sale_location_group_lang", $arFields["LANG"][$i]);
			$strSql =
				"INSERT INTO b_sale_location_group_lang(LOCATION_GROUP_ID, ".$arInsert[0].") ".
				"VALUES(".$ID.", ".$arInsert[1].")";

			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		if(CSaleLocation::isLocationProMigrated())
		{
			try
			{
				$entityClass = self::CONN_ENTITY_NAME.'Table';
				$entityClass::resetMultipleForOwner($ID, array(
					Location\Connector::DB_LOCATION_FLAG => $entityClass::normalizeLocationList($arFields["LOCATION_ID"])
				));
			}
			catch(Exception $e)
			{
			}
		}
		else
		{
			$strSqlHead ="INSERT INTO b_sale_location2location_group (LOCATION_ID, LOCATION_GROUP_ID) VALUES ";
			$strSqlHeadLength = strlen($strSqlHead);

			$res = $DB->Query('SHOW VARIABLES LIKE \'max_allowed_packet\'');
			$maxPack = $res->Fetch();

			if(isset($maxPack["Value"]))
				$max_allowed_packet = $maxPack["Value"]-$strSqlHeadLength-100;
			else
				$max_allowed_packet = 0;

			$tmpSql = '';
			$strSql = '';
			$countFieldLoc = count($arFields["LOCATION_ID"]);
			for ($i = 0; $i < $countFieldLoc; $i++)
			{
				$tmpSql ="(".$arFields["LOCATION_ID"][$i].", ".$ID.")";
				$strSqlLen = strlen($strSql);

				if($strSqlHeadLength + $strSqlLen + strlen($tmpSql) < $max_allowed_packet || $max_allowed_packet <= 0)
				{
					if($strSqlLen > 0)
						$strSql .=",";

					$strSql .= $tmpSql;
				}
				else
				{
					$DB->Query($strSqlHead.$strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$strSql = $tmpSql;
				}
			}

			if(strlen($strSql) > 0)
				$DB->Query($strSqlHead.$strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$events = GetModuleEvents("sale", "OnLocationGroupAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}
}
?>