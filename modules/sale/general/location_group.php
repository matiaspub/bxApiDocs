<?
/**
 * The content of this file was marked as deprecated.
 * It will be removed from future releases. Do not rely on this code.
 *
 * @access private
 */

use Bitrix\Main\DB;
use Bitrix\Main\Entity;
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
class CAllSaleLocationGroup
{
	const SELF_ENTITY_NAME = 		'Bitrix\Sale\Location\Group';
	const CONN_ENTITY_NAME = 		'Bitrix\Sale\Location\GroupLocation';
	const LOCATION_ENTITY_NAME = 	'Bitrix\Sale\Location\Location';
	const NAME_ENTITY_NAME = 		'Bitrix\Sale\Location\Name\Group';

	
	/**
	* <p>Метод возвращает набор местоположений, связанных с группами местоположений, удовлетворяющих фильтру arFilter. Метод динамичный.</p>
	*
	*
	* @param array $arrayarFilter = Array() Фильтр представляет собой ассоциативный массив, в котором
	* ключами являются названия параметров записи, а значениями -
	* условия на значения<br><br> Допустимые ключи: <ul> <li> <b>LOCATION_ID</b> - код
	* местоположения;</li> <li> <b>LOCATION_GROUP_ID</b> - код группы
	* местоположений.</li> </ul>
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы с ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>LOCATION_ID</td> <td>Код местоположения.</td> </tr> <tr>
	* <td>LOCATION_GROUP_ID</td> <td>Код группы местоположений.</td> </tr> </table> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем все местоположения группы 2
	* $db_res = CSaleLocationGroup::GetLocationList(array("LOCATION_GROUP_ID"=&gt;2));
	* while ($ar_res = $db_res-&gt;Fetch())
	* {
	*    echo $ar_res["LOCATION_ID"].", ";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__getlocationlist.56a02620.php
	* @author Bitrix
	*/
	public static function GetLocationList($arFilter=Array())
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			try
			{
				$query = new Entity\Query(self::CONN_ENTITY_NAME);

				$fieldMap = array(
					'D_SPIKE' => 'D_SPIKE',
					'LLOCATION_ID' => 'C.ID',
					'LOCATION_GROUP_ID' => 'LOCATION_GROUP_ID'
				);
				$fieldProxy = array(
					'LLOCATION_ID' => 'LOCATION_ID'
				);
				
				$query->registerRuntimeField(
					'D_SPIKE',
					array(
						'data_type' => 'integer',
						'expression' => array(
							'distinct %s',
							'LOCATION_GROUP_ID'
						)
					)
				);

				$query->registerRuntimeField(
					'L',
					array(
						'data_type' => self::LOCATION_ENTITY_NAME,
						'reference' => array(
							'=this.LOCATION_ID' => 'ref.ID',
						),
						'join_type' => 'inner'
					)
				);

				$query->registerRuntimeField(
					'C',
					array(
						'data_type' => self::LOCATION_ENTITY_NAME,
						'reference' => array(
							'LOGIC' => 'OR',
							array(
								'>=ref.LEFT_MARGIN' => 'this.L.LEFT_MARGIN',
								'<=ref.RIGHT_MARGIN' => 'this.L.RIGHT_MARGIN'
							),
							array(
								'=ref.ID' => 'this.L.ID'
							)
						),
						'join_type' => 'inner'
					)
				);

				// select
				$selectFields = CSaleLocation::processSelectForGetList(array('*'), $fieldMap);

				// filter
				list($filterFields, $filterClean) = CSaleLocation::processFilterForGetList($arFilter, $fieldMap, $fieldProxy);

				$query->setSelect($selectFields);
				$query->setFilter($filterFields);

				$res = $query->exec();
				$res->addReplacedAliases(array(
					'LLOCATION_ID' => 'LOCATION_ID'
				));

				return $res;
			}
			catch(Exception $e)
			{
				return new DB\ArrayResult(array());
			}
		}
		else
		{

			global $DB;
			$arSqlSearch = Array();

			if(!is_array($arFilter))
				$filter_keys = Array();
			else
				$filter_keys = array_keys($arFilter);

			$countFieldKey = count($filter_keys);
			for($i=0; $i < $countFieldKey; $i++)
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
				case "LOCATION_ID":
					$arSqlSearch[] = "LOCATION_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					break;
				case "LOCATION_GROUP_ID":
					$arSqlSearch[] = "LOCATION_GROUP_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
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

			$strSql =
				"SELECT LOCATION_ID, LOCATION_GROUP_ID ".
				"FROM b_sale_location2location_group ".
				"WHERE 1 = 1 ".
				"	".$strSqlSearch." ";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			return $res;

		}
	}

	
	/**
	* <p>Метод возвращает языкозависимые параметры группы местоположений с кодом ID для языка с кодом strLang. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код группы местоположений.
	*
	* @param string $strLang = LANGUAGE_ID Код языка.
	*
	* @return array <p>Возвращается ассоциативный массив с ключами:</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* записи.</td> </tr> <tr> <td>LOCATION_GROUP_ID</td> <td>Код группы местоположений.</td>
	* </tr> <tr> <td>NAME</td> <td>Название группы.</td> </tr> <tr> <td>LID</td> <td>Язык
	* названия.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$arGroupLang = CSaleLocationGroup::GetGroupLangByID(2, "en");
	* echo $arGroupLang["NAME"];
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__getgrouplangbyid.6c40615e.php
	* @author Bitrix
	*/
	public static function GetGroupLangByID($ID, $strLang = LANGUAGE_ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT ID, LOCATION_GROUP_ID, LID, NAME ".
			"FROM b_sale_location_group_lang ".
			"WHERE LOCATION_GROUP_ID = ".$ID." ".
			"	AND LID = '".$DB->ForSql($strLang, 2)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;

		if (is_set($arFields, "SORT") && IntVal($arFields["SORT"])<=0)
			$arFields["SORT"] = 100;

		if (is_set($arFields, "LOCATION_ID") && (!is_array($arFields["LOCATION_ID"]) || count($arFields["LOCATION_ID"])<=0))
			return false;

		if (is_set($arFields, "LANG"))
		{
			$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
			while ($arLang = $db_lang->Fetch())
			{
				$bFound = False;
				$coountarFieldLang = count($arFields["LANG"]);
				for ($i = 0; $i < $coountarFieldLang; $i++)
				{
					if ($arFields["LANG"][$i]["LID"]==$arLang["LID"] && strlen($arFields["LANG"][$i]["NAME"])>0)
					{
						$bFound = True;
					}
				}
				if (!$bFound)
					return false;
			}
		}

		return True;
	}

	
	/**
	* <p>Метод обновляет параметры местоположения с кодом ID в соответствии с параметрами из массива arFields. Обновляются также страна и город этого местоположения. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код местоположения. </h
	*
	* @param array $arFields  Ассоциативный массив параметров местоположения с ключами: <ul> <li>
	* <b>SORT</b> - индекс сортировки; </li> <li> <b>COUNTRY_ID</b> - код страны (если такая
	* страна уже есть, иначе код должен быть нулем, и должен быть
	* заполнен ключ COUNTRY);</li> <li> <b>COUNTRY</b> - массив с параметрами страны
	* (должен быть заполнен, если не установлен ключ COUNTRY_ID или если ключ
	* CHANGE_COUNTRY установлен в значение Y); </li> <li> <b>CHANGE_COUNTRY</b> - флаг (Y/N),
	* изменять ли параметры страны (долны быть установлены ключи COUNTRY_ID
	* и COUNTRY); </li> <li> <b>WITHOUT_CITY</b> - флаг (Y/N), означающий, что это
	* местоположение без города (только страна) (если значением с этим
	* ключем является N, то необходимо заполнить ключ CITY);</li> <li> <b>CITY_ID</b> -
	* код города (если такой город уже есть, иначе код должен быть нулем,
	* и должен быть заполнен ключ CITY);</li> <li> <b>CITY</b> - массив с параметрами
	* города (если установлен флаг WITHOUT_CITY в значение Y, то этот ключ
	* заполнять не нужно);</li> <li> <b>LOCATION_ID</b> - массив кодов
	* местоположений, которые привязаны к данной группе
	* местоположений.</li> </ul> Массив с параметрами страны должен
	* содержать ключи: <ul> <li> <b>NAME</b> - название страны (не зависящее от
	* языка);</li> <li> <b>SHORT_NAME</b> - сокращенное название страны - абревиатура
	* (не зависящее от языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является
	* код языка, а значением ассоциативный массив вида <pre class="syntax">
	* array("LID" =&gt; "код языка", "NAME" =&gt; "название страны на этом языке",
	* "SHORT_NAME" =&gt; "сокращенное название страны (аббревиатура) на этом
	* языке")</pre> Эта пара ключ-значение должна присутствовать для
	* каждого языка системы. </li> </ul> Массив с параметрами города должен
	* содержать ключи: <ul> <li> <b>NAME</b> - название города (не зависящее от
	* языка);</li> <li> <b>SHORT_NAME</b> - сокращенное название города -
	* аббревиатура (не зависящее от языка);</li> <li> <b>&lt;код языка&gt;</b> -
	* ключем является код языка, а значением ассоциативный массив вида
	* <pre class="syntax"> array("LID" =&gt; "код языка", "NAME" =&gt; "название города на этом
	* языке", "SHORT_NAME" =&gt; "сокращенное название города (аббревиатура) на
	* этом языке")</pre> Эта пара ключ-значение должна присутствовать для
	* каждого языка системы.</li> </ul>
	*
	* @return int <p>Возвращается код измененного местоположения или <i>false</i> у
	* случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "SORT" =&gt; 100,
	*    "COUNTRY_ID" =&gt; 8,
	*    "WITHOUT_CITY" =&gt; "N"
	* );
	*  
	* $arCity = array(
	*    "NAME" =&gt; "Kaliningrad",
	*    "SHORT_NAME" =&gt; "Kaliningrad",
	*    "ru" =&gt; array(
	*       "LID" =&gt; "ru",
	*       "NAME" =&gt; "Калининград",
	*       "SHORT_NAME" =&gt; "Калининград"
	*       ),
	*    "en" =&gt; array(
	*       "LID" =&gt; "en",
	*       "NAME" =&gt; "Kaliningrad",
	*       "SHORT_NAME" =&gt; "Kaliningrad"
	*       )
	* );
	*  
	* $arFields["CITY"] = $arCity;
	*  
	* if (!CSaleLocation::Update(6, $arFields))
	*    echo "Ошибка изменения местоположения";
	* ?&gt;
	* 
	* 
	* 
	*  function CreateCityRussia($arFields, $regionName, &amp;$strError) {
	*    // Ищем группу местоположений, к которой необходимо привязать местоположение
	*    $locGroupID = 0;
	*    $resLocationGroup = CSaleLocationGroup::GetList(array("ID"=&gt;"ASC"), array());
	*    while($arLocationGroup = $resLocationGroup-&gt;Fetch()) {
	*      if(mb_strtolower($arLocationGroup["NAME"], "Windows-1251") == mb_strtolower($regionName, "Windows-1251")) $locGroupID = $arLocationGroup["ID"];
	*    }
	*    
	*    // Если группа найдена, определяем список привязанных к ней местоположений и добавляем к списку новое
	*    if($locGroupID) {
	*      // Создаем новый город в стране Россия и новое местоположение
	*      $arCoutry = CSaleLocation::GetList(array(), array("LID" =&gt; LANGUAGE_ID, "CITY_NAME" =&gt; false, "COUNTRY_NAME" =&gt; "Россия"))-&gt;Fetch();
	*      $cityId = CSaleLocation::AddCity($arFields);
	*      $ID = CSaleLocation::AddLocation(array("COUNTRY_ID" =&gt; $arCoutry['COUNTRY_ID'], "CITY_ID" =&gt; $cityId));
	*      
	*      // Формируем новый список местоположений группы
	*      $resLocGroup = CSaleLocationGroup::GetLocationList(array("LOCATION_GROUP_ID" =&gt; $locGroupID));
	*      $locList = array();
	*      while($arLocGroup = $resLocGroup-&gt;Fetch()) {
	*        $locList[] = $arLocGroup["LOCATION_ID"];
	*      }
	*      $locList[] = $ID;
	*      
	*      // Записываем новый список местоположений группы
	*      $arFields = CSaleLocationGroup::GetByID($locGroupID);
	*      $arFields["LOCATION_ID"] = $locList;
	*      if (!CSaleLocationGroup::Update($locGroupID, $arFields))
	*         $strError = "Ошибка добавления местоположения к группе местоположений";
	*      return $ID;
	*    } else {
	*      // В противном случае записываем сообщение об ошибке
	*      $strError = "Ошибка создания местоположения: Не найдена группа местоположений $regionName";
	*      return false; 
	*    }
	*     }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__update.c02c467b.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if (!CSaleLocationGroup::CheckFields("UPDATE", $arFields))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeLocationGroupUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		$events = GetModuleEvents("sale", "OnLocationGroupUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		$strUpdate = $DB->PrepareUpdate("b_sale_location_group", $arFields);
		$strSql = "UPDATE b_sale_location_group SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (is_set($arFields, "LANG"))
		{
			$DB->Query("DELETE FROM b_sale_location_group_lang WHERE LOCATION_GROUP_ID = ".$ID."");

			$countFieldLang = count($arFields["LANG"]);
			for ($i = 0; $i < $countFieldLang; $i++)
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_group_lang", $arFields["LANG"][$i]);
				$strSql =
					"INSERT INTO b_sale_location_group_lang(LOCATION_GROUP_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		if(is_set($arFields, "LOCATION_ID"))
		{
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
				$DB->Query("DELETE FROM b_sale_location2location_group WHERE LOCATION_GROUP_ID = ".$ID."");

				$countArFieldLoc = count($arFields["LOCATION_ID"]);
				for ($i = 0; $i < $countArFieldLoc; $i++)
				{
					$strSql =
						"INSERT INTO b_sale_location2location_group(LOCATION_ID, LOCATION_GROUP_ID) ".
						"VALUES(".$arFields["LOCATION_ID"][$i].", ".$ID.")";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}
		}

		return $ID;
	}

	
	/**
	* <p>Метод удаляет группу местоположений с кодом ID. Местоположения, входящие в эту группу, не изменяются. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код группы местоположений.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!CSaleLocationGroup::Delete(2))
	*    echo "Ошибка удаления группы местоположений";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocationgroup/csalelocationgroup__delete.d96420be.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$db_events = GetModuleEvents("sale", "OnBeforeLocationGroupDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$events = GetModuleEvents("sale", "OnLocationGroupDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		$DB->Query("DELETE FROM b_sale_delivery2location WHERE LOCATION_ID = ".$ID." AND LOCATION_TYPE = 'G'", true);
		// tax rates drop ?
		$DB->Query("DELETE FROM b_sale_location2location_group WHERE LOCATION_GROUP_ID = ".$ID."", true);
		$DB->Query("DELETE FROM b_sale_location_group_lang WHERE LOCATION_GROUP_ID = ".$ID."", true);

		return $DB->Query("DELETE FROM b_sale_location_group WHERE ID = ".$ID."", true);
	}

	public static function OnLangDelete($strLang)
	{
		global $DB;
		$DB->Query("DELETE FROM b_sale_location_group_lang WHERE LID = '".$DB->ForSql($strLang)."'", true);
		return True;
	}
}
?>