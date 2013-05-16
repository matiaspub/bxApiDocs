<?

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/index.php
 * @author Bitrix
 */
class CAllSaleLocation
{
	
	/**
	 * <p>Функция возвращает языконезависимые параметры страны с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код страны.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив с ключами:</p><table class="tnormal"
	 * width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * страны.</td> </tr> <tr> <td>NAME</td> <td>Языконезависимое название страны.</td>
	 * </tr> <tr> <td>SHORT_NAME</td> <td>Языконезависимое короткое название страны.</td>
	 * </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getcountrybyid.bc803b85.php
	 * @author Bitrix
	 */
	public static function GetCountryByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_location_country ".
			"WHERE ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	 * <p>Функция возвращает языкозависимые параметры страны по ее коду ID и языку strLang</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код страны.
	 *
	 *
	 *
	 * @param string $strLang = LANGUAGE_ID Язык.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив с ключами:</p><table class="tnormal"
	 * width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * записи.</td> </tr> <tr> <td>CITY_ID</td> <td>Код страны.</td> </tr> <tr> <td>LID</td> <td>Язык.</td>
	 * </tr> <tr> <td>NAME</td> <td>Языкозависимое название страны.</td> </tr> <tr>
	 * <td>SHORT_NAME</td> <td>Языкозависимое короткое название страны.</td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getcountrylangbyid.aef8761b.php
	 * @author Bitrix
	 */
	public static function GetCountryLangByID($ID, $strLang = LANGUAGE_ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strLang = Trim($strLang);

		$strSql =
			"SELECT * ".
			"FROM b_sale_location_country_lang ".
			"WHERE COUNTRY_ID = ".$ID." ".
			"	AND LID = '".$DB->ForSql($strLang, 2)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	 * <p>Функция возвращает языконезависимые параметры города с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код города.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив с ключами:</p><table class="tnormal"
	 * width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * города.</td> </tr> <tr> <td>NAME</td> <td>Языконезависимое название города.</td>
	 * </tr> <tr> <td>SHORT_NAME</td> <td>Языконезависимое короткое название города.</td>
	 * </tr> </table><p>  </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getcitybyid.fb724f2b.php
	 * @author Bitrix
	 */
	public static function GetCityByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_location_city ".
			"WHERE ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	 * <p>Функция возвращает языкозависимые параметры города по его коду ID и языку strLang </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код города.
	 *
	 *
	 *
	 * @param string $strLang = LANGUAGE_ID Язык. По умолчанию равен текущему языку.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив с ключами:</p><table class="tnormal"
	 * width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * записи.</td> </tr> <tr> <td>CITY_ID</td> <td>Код города.</td> </tr> <tr> <td>LID</td> <td>Язык.</td>
	 * </tr> <tr> <td>NAME</td> <td>Языкозависимое название города.</td> </tr> <tr>
	 * <td>SHORT_NAME</td> <td>Языкозависимое короткое название города.</td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getcitylangbyid.f2bc091a.php
	 * @author Bitrix
	 */
	public static function GetCityLangByID($ID, $strLang = LANGUAGE_ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strLang = Trim($strLang);

		$strSql =
			"SELECT * ".
			"FROM b_sale_location_city_lang ".
			"WHERE CITY_ID = ".$ID." ".
			"	AND LID = '".$DB->ForSql($strLang, 2)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}


	/**
	* The function returns the languages parameters for region
	* @param int $ID region code
	* @param string $strLang the current language
	* @return array $res region parameters
	*/
	public static function GetRegionLangByID($ID, $strLang = LANGUAGE_ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strLang = Trim($strLang);

		$strSql =
			"SELECT * ".
			"FROM b_sale_location_region_lang ".
			"WHERE REGION_ID = ".$ID." ".
			" AND LID = '".$DB->ForSql($strLang, 2)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	/**
	* The function returns parameters for region
	* @param int $ID region code
	* @return array $res region parameters
	*/
	public static function GetRegionByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_location_region ".
			"WHERE ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}


	// COUNTRY
	public static function CountryCheckFields($ACTION, &$arFields)
	{
		global $DB;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"])<=0) return false;

		/*
		$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ((is_set($arFields[$arLang["LID"]], "NAME") || $ACTION=="ADD") && strlen($arFields[$arLang["LID"]]["NAME"])<=0) return false;
		}
		*/

		return True;
	}

	
	/**
	 * <p>Функция изменяет параметры страны с кодом ID на новые параметры из массива arFields </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код страны.
	 *
	 *
	 *
	 * @param array $arFields  Массив с параметрами страны должен содержать ключи: <ul> <li> <b>NAME</b> -
	 * название страны (не зависящее от языка);</li> <li> <b>SHORT_NAME</b> -
	 * сокращенное название страны - абревиатура (не зависящее от
	 * языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является код языка, а
	 * значением ассоциативный массив вида <pre class="syntax">array("LID" =&gt; "код
	 * языка", "NAME" =&gt; "название страны на этом языке", "SHORT_NAME" =&gt;
	 * "сокращенное название страны (аббревиатура) на этом языке")</pre> Эта
	 * пара ключ-значение должна присутствовать для каждого языка
	 * системы.</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращается код измененной страны или <i>false</i> у случае
	 * ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatecountry.d8fa5b90.php
	 * @author Bitrix
	 */
	public static function UpdateCountry($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);

		if ($ID <= 0 || !CSaleLocation::CountryCheckFields("UPDATE", $arFields))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeCountryUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_location_country", $arFields);
		$strSql = "UPDATE b_sale_location_country SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ($arCntLang = CSaleLocation::GetCountryLangByID($ID, $arLang["LID"]))
			{
				$strUpdate = $DB->PrepareUpdate("b_sale_location_country_lang", $arFields[$arLang["LID"]]);
				$strSql = "UPDATE b_sale_location_country_lang SET ".$strUpdate." WHERE ID = ".$arCntLang["ID"]."";
			}
			else
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_country_lang", $arFields[$arLang["LID"]]);
				$strSql =
					"INSERT INTO b_sale_location_country_lang(COUNTRY_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
			}
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$events = GetModuleEvents("sale", "OnCountryUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	
	/**
	 * <p>Функция удаляет страну с кодом ID. Связаные с этой страной местоположения не изменяются. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код страны.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает <i>true</i> в случае успешного удаления
	 * местоположения и <i>false</i> - в противном случае.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (!CSaleLocation::DeleteCountry(12))
	 *    echo "Ошибка удаления страны";<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deletecountry.e37a14ed.php
	 * @author Bitrix
	 */
	public static function DeleteCountry($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$db_events = GetModuleEvents("sale", "OnBeforeCountryDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$DB->Query("DELETE FROM b_sale_location_country_lang WHERE COUNTRY_ID = ".$ID."", true);
		$bDelete = $DB->Query("DELETE FROM b_sale_location_country WHERE ID = ".$ID."", true);

		$events = GetModuleEvents("sale", "OnCountryDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));


		return $bDelete;
	}

	// CITY
	public static function CityCheckFields($ACTION, &$arFields)
	{
		global $DB;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"])<=0) return false;

		/*
		$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ((is_set($arFields[$arLang["LID"]], "NAME") || $ACTION=="ADD") && strlen($arFields[$arLang["LID"]]["NAME"])<=0) return false;
		}
		*/

		return True;
	}

	// REGION
	public static function RegionCheckFields($ACTION, &$arFields)
	{
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"])<=0) return false;

		return True;
	}

	
	/**
	 * <p>Функция изменяет параметры города с кодом ID на значения из массива arFields </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код города.
	 *
	 *
	 *
	 * @param array $arFields  Массив с параметрами города должен содержать ключи: <ul> <li> <b>NAME</b> -
	 * название города (не зависящее от языка);</li> <li> <b>SHORT_NAME</b> -
	 * сокращенное название города - абревиатура (не зависящее от
	 * языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является код языка, а
	 * значением ассоциативный массив вида <pre class="syntax"> array("LID" =&gt; "код
	 * языка", "NAME" =&gt; "название города на этом языке", "SHORT_NAME" =&gt;
	 * "сокращенное название города (аббревиатура) на этом языке")</pre> Эта
	 * пара ключ-значение должна присутствовать для каждого языка
	 * системы.</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращается код измененного города или <i>false</i> у случае
	 * ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatecity.3fe4165d.php
	 * @author Bitrix
	 */
	public static function UpdateCity($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);

		if ($ID <= 0 || !CSaleLocation::CityCheckFields("UPDATE", $arFields))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeCityUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_location_city", $arFields);
		$strSql = "UPDATE b_sale_location_city SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ($arCntLang = CSaleLocation::GetCityLangByID($ID, $arLang["LID"]))
			{
				$strUpdate = $DB->PrepareUpdate("b_sale_location_city_lang", $arFields[$arLang["LID"]]);
				$strSql = "UPDATE b_sale_location_city_lang SET ".$strUpdate." WHERE ID = ".$arCntLang["ID"]."";
			}
			else
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_city_lang", $arFields[$arLang["LID"]]);
				$strSql =
					"INSERT INTO b_sale_location_city_lang(CITY_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
			}
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$events = GetModuleEvents("sale", "OnCityUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	/**
	* The function modifies the parameters of the region
	*
	* @param int $ID region code
	* @param array $arFields array with parameters region
	* @return int $ID code region
	*/
	public static function UpdateRegion($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);

		if ($ID <= 0 || !CSaleLocation::RegionCheckFields("UPDATE", $arFields))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeRegionUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_location_region", $arFields);
		$strSql = "UPDATE b_sale_location_region SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"), array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ($arCntLang = CSaleLocation::GetRegionLangByID($ID, $arLang["LID"]))
			{
				$strUpdate = $DB->PrepareUpdate("b_sale_location_region_lang", $arFields[$arLang["LID"]]);
				print_r($arFields);die();
				$strSql = "UPDATE b_sale_location_region_lang SET ".$strUpdate." WHERE ID = ".$arCntLang["ID"]."";
			}
			else
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_region_lang", $arFields[$arLang["LID"]]);
				$strSql =
					"INSERT INTO b_sale_location_region_lang(REGION_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
			}
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$events = GetModuleEvents("sale", "OnRegionUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	/**
	* The function delete region
	*
	* @param int $ID region code
	* @return true false
	*/
	public static function DeleteRegion($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$db_events = GetModuleEvents("sale", "OnBeforeRegionDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$bDelete = false;
		$DB->Query("DELETE FROM b_sale_location_region_lang WHERE REGION_ID = ".$ID."", true);
		$bDelete = $DB->Query("DELETE FROM b_sale_location_region WHERE ID = ".$ID."", true);

		$events = GetModuleEvents("sale", "OnRegionDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		return $bDelete;
	}

	
	/**
	 * <p>Функция удаляет город с кодом ID. Местоположение, с которым связан этот город, не изменяется.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код города.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает <i>true</i> в случае успешного удаления
	 * местоположения и <i>false</i> - в противном случае.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (!CSaleLocation::DeleteCity(12))
	 *    echo "Ошибка удаления города";<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deletecity.339c5a43.php
	 * @author Bitrix
	 */
	public static function DeleteCity($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$db_events = GetModuleEvents("sale", "OnBeforeCityDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$DB->Query("DELETE FROM b_sale_location_city_lang WHERE CITY_ID = ".$ID."", true);
		$bDelete = $DB->Query("DELETE FROM b_sale_location_city WHERE ID = ".$ID."", true);

		$events = GetModuleEvents("sale", "OnCityDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		return $bDelete;
	}

	// LOCATION
	public static function LocationCheckFields($ACTION, &$arFields)
	{
		global $DB;

		if ((is_set($arFields, "SORT") || $ACTION=="ADD") && IntVal($arFields["SORT"])<=0) $arFields["SORT"] = 100;
		if (is_set($arFields, "COUNTRY_ID")) $arFields["COUNTRY_ID"] = IntVal($arFields["COUNTRY_ID"]);
		if (is_set($arFields, "CITY_ID")) $arFields["CITY_ID"] = IntVal($arFields["CITY_ID"]);

		return True;
	}

	
	/**
	 * <p>Функция обновляет параметры местоположения с кодом ID в соответствии с параметрами из массива arFields.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код местоположения.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров местоположения с ключами: <ul> <li>
	 * <b>SORT</b> - индекс сортировки; </li> <li> <b>COUNTRY_ID</b> - код страны;</li> <li>
	 * <b>CITY_ID</b> - код города (если такой город уже есть, иначе код должен
	 * быть нулем, и должен быть заполнен ключ CITY).</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращается код измененного местоположения или <i>false</i> у
	 * случае ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__updatelocation.3c5a6205.php
	 * @author Bitrix
	 */
	public static function UpdateLocation($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);

		if ($ID <= 0 || !CSaleLocation::LocationCheckFields("UPDATE", $arFields))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeLocationUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_location", $arFields);
		$strSql = "UPDATE b_sale_location SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$events = GetModuleEvents("sale", "OnLocationUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}


	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;

		if (is_set($arFields, "CHANGE_COUNTRY") && $arFields["CHANGE_COUNTRY"]!="Y")
			$arFields["CHANGE_COUNTRY"] = "N";
		if (is_set($arFields, "WITHOUT_CITY") && $arFields["WITHOUT_CITY"]!="Y")
			$arFields["WITHOUT_CITY"] = "N";

		if (is_set($arFields, "COUNTRY_ID"))
			$arFields["COUNTRY_ID"] = trim($arFields["COUNTRY_ID"]);
		//	$arFields["COUNTRY_ID"] = IntVal($arFields["COUNTRY_ID"]);

		if (is_set($arFields, "CHANGE_COUNTRY") && $arFields["CHANGE_COUNTRY"]=="Y"
			&& (!is_set($arFields, "COUNTRY_ID") || $arFields["COUNTRY_ID"]<=0))
			return false;

		return True;
	}

	
	/**
	 * <p>Функция добавляет новое местоположение включая страну и город местоположения, если нужно.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив параметров местоположения с ключами: <ul> <li>
	 * <b>SORT</b> - индекс сортировки; </li> <li> <b>COUNTRY_ID</b> - код страны (если такая
	 * страна уже есть, иначе код должен быть нулем, и должен быть
	 * заполнен ключ COUNTRY);</li> <li> <b>COUNTRY</b> - массив с параметрами страны
	 * (если страна уже есть и установлен ключ COUNTRY_ID, то этот ключ
	 * заполнять не нужно); </li> <li> <b>WITHOUT_CITY</b> - флаг (Y/N), означающий, что
	 * это местоположение без города (только страна) (если значением с
	 * этим ключом является N, то необходимо заполнить ключ CITY);</li> <li>
	 * <b>CITY</b> - массив с параметрами города (если установлен флаг WITHOUT_CITY
	 * в значение Y, то этот ключ заполнять не нужно);</li> </ul> Массив с
	 * параметрами страны должен содержать ключи: <ul> <li> <b>NAME</b> - название
	 * страны (не зависящее от языка);</li> <li> <b>SHORT_NAME</b> - сокращенное
	 * название страны - абревиатура (не зависящее от языка);</li> <li>
	 * <b>&lt;код языка&gt;</b> - ключом является код языка, а значением
	 * ассоциативный массив вида: <pre class="syntax">array("LID" =&gt; "код языка", "NAME"
	 * =&gt; "название страны на этом языке", "SHORT_NAME" =&gt; "сокращенное
	 * название страны (аббревиатура) на этом языке")</pre> Эта пара
	 * ключ-значение должна присутствовать для каждого языка системы.
	 * </li> </ul> Массив с параметрами города должен содержать ключи: <ul> <li>
	 * <b>NAME</b> - название города (не зависящее от языка);</li> <li> <b>SHORT_NAME</b> -
	 * сокращенное название города - абревиатура (не зависящее от
	 * языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является код языка, а
	 * значением ассоциативный массив вида: <pre class="syntax">array("LID" =&gt; "код
	 * языка", "NAME" =&gt; "название города на этом языке", "SHORT_NAME" =&gt;
	 * "сокращенное название города (аббревиатура) на этом языке")</pre> Эта
	 * пара ключ-значение должна присутствовать для каждого языка
	 * системы. </li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращается код добавленного местоположения или <i>false</i> у
	 * случае ошибки.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = array(
	 *    "SORT" =&gt; 100,
	 *    "COUNTRY_ID" =&gt; 0,
	 *    "WITHOUT_CITY" =&gt; "N"
	 * );
	 * 
	 * $arCountry = array(
	 *    "NAME" =&gt; "Russian Federation",
	 *    "SHORT_NAME" =&gt; "Russia",
	 *    "ru" =&gt; array(
	 *       "LID" =&gt; "ru",
	 *       "NAME" =&gt; "Российская федерация",
	 *       "SHORT_NAME" =&gt; "Россия"
	 *       ),
	 *    "en" =&gt; array(
	 *       "LID" =&gt; "en",
	 *       "NAME" =&gt; "Russian Federation",
	 *       "SHORT_NAME" =&gt; "Russia"
	 *       )
	 * );
	 * 
	 * $arFields["COUNTRY"] = $arCountry;
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
	 * $ID = CSaleLocation::Add($arFields);
	 * if (IntVal($ID)&lt;=0)
	 *    echo "Ошибка добавления местоположения";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__add.92483b06.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleLocation::CheckFields("ADD", $arFields))
			return false;

		if ((!is_set($arFields, "COUNTRY_ID") || IntVal($arFields["COUNTRY_ID"])<=0) && strlen($arFields["COUNTRY_ID"]) > 0)
		{
			$arFields["COUNTRY_ID"] = CSaleLocation::AddCountry($arFields["COUNTRY"]);
			if (IntVal($arFields["COUNTRY_ID"])<=0) return false;

			if ($arFields["WITHOUT_CITY"]!="Y" && strlen($arFields["REGION_ID"]) <= 0)
			{
				UnSet($arFields["CITY_ID"]);
				CSaleLocation::AddLocation($arFields);
			}
		}

		if ($arFields["REGION_ID"] <= 0 && $arFields["REGION_ID"] != "")
		{
			$arFields["REGION_ID"] = CSaleLocation::AddRegion($arFields["REGION"]);
			if (IntVal($arFields["REGION_ID"])<=0) return false;

			if ($arFields["WITHOUT_CITY"] != "Y")
			{
				//$arFieldsTmp = $arFields;
				UnSet($arFields["CITY_ID"]);
				CSaleLocation::AddLocation($arFields);
			}
		}
		elseif ($arFields["REGION_ID"] == '')
		{
			UnSet($arFields["REGION_ID"]);
		}

		if ($arFields["WITHOUT_CITY"]!="Y")
		{
			if (IntVal($arFields["REGION_ID"]) > 0)
				$arFields["CITY"]["REGION_ID"] = $arFields["REGION_ID"];
			$arFields["CITY_ID"] = CSaleLocation::AddCity($arFields["CITY"]);
			if (IntVal($arFields["CITY_ID"])<=0) return false;
		}
		else
		{
			UnSet($arFields["CITY_ID"]);
		}

		$ID = CSaleLocation::AddLocation($arFields);

		return $ID;
	}

	
	/**
	 * <p>Функция обновляет параметры местоположения с кодом ID в соответствии с параметрами из массива arFields. Обновляются так же страна и город этого местоположения. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код местоположения.
	 *
	 *
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
	 * заполнять не нужно);</li> </ul> Массив с параметрами страны должен
	 * содержать ключи: <ul> <li> <b>NAME</b> - название страны (не зависящее от
	 * языка);</li> <li> <b>SHORT_NAME</b> - сокращенное название страны - абревиатура
	 * (не зависящее от языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является
	 * код языка, а значением ассоциативный массив вида <pre class="syntax">
	 * array("LID" =&gt; "код языка", "NAME" =&gt; "название страны на этом языке",
	 * "SHORT_NAME" =&gt; "сокращенное название страны (аббревиатура) на этом
	 * языке")</pre> Эта пара ключ-значение должна присутствовать для
	 * каждого языка системы. </li> </ul> Массив с параметрами города должен
	 * содержать ключи: <ul> <li> <b>NAME</b> - название города (не зависящее от
	 * языка);</li> <li> <b>SHORT_NAME</b> - сокращенное название города - абревиатура
	 * (не зависящее от языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является
	 * код языка, а значением ассоциативный массив вида <pre class="syntax">
	 * array("LID" =&gt; "код языка", "NAME" =&gt; "название города на этом языке",
	 * "SHORT_NAME" =&gt; "сокращенное название города (аббревиатура) на этом
	 * языке")</pre> Эта пара ключ-значение должна присутствовать для
	 * каждого языка системы.</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращается код измененного местоположения или <i>false</i> у
	 * случае ошибки.</p><a name="examples"></a>
	 *
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
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__update.a6601f1c.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CSaleLocation::CheckFields("UPDATE", $arFields)) return false;

		if (!($arLocRes = CSaleLocation::GetByID($ID, LANGUAGE_ID))) return false;

		if ((!is_set($arFields, "COUNTRY_ID") || IntVal($arFields["COUNTRY_ID"])<=0) && $arFields["COUNTRY_ID"] != "")
		{
			$arFields["COUNTRY_ID"] = CSaleLocation::AddCountry($arFields["COUNTRY"]);
			if (IntVal($arFields["COUNTRY_ID"])<=0) return false;

			UnSet($arFields["CITY_ID"]);
			UnSet($arFields["REGION_ID"]);
			CSaleLocation::AddLocation($arFields);
		}
		elseif ($arFields["CHANGE_COUNTRY"]=="Y" || $arFields["COUNTRY_ID"] == "")
		{
			CSaleLocation::UpdateCountry($arFields["COUNTRY_ID"], $arFields["COUNTRY"]);
		}

		//city
		if ($arFields["WITHOUT_CITY"]!="Y")
		{
			if (IntVal($arLocRes["CITY_ID"])>0)
			{
				CSaleLocation::UpdateCity(IntVal($arLocRes["CITY_ID"]), $arFields["CITY"]);
			}
			else
			{
				$arFields["CITY_ID"] = CSaleLocation::AddCity($arFields["CITY"]);
				if (IntVal($arFields["CITY_ID"])<=0) return false;
			}
		}
		else
		{
			CSaleLocation::DeleteCity($arLocRes["CITY_ID"]);
			$arFields["CITY_ID"] = false;
		}

		//region
		if (IntVal($arFields["REGION_ID"])>0)
		{
			CSaleLocation::UpdateRegion(IntVal($arLocRes["REGION_ID"]), $arFields["REGION"]);
		}
		elseif ($arFields["REGION_ID"] == 0 && $arFields["REGION_ID"] != '')
		{
			$db_res = CSaleLocation::GetRegionList(array("ID" => "DESC"), array("NAME" => $arFields["REGION"][SITE_ID]["NAME"]));
			$arRegion = $db_res->Fetch();

			if (count($arRegion) > 1)
				$arFields["REGION_ID"] = $arRegion["ID"];
			else
			{
				$arFields["REGION_ID"] = CSaleLocation::AddRegion($arFields["REGION"]);
				if (IntVal($arFields["REGION_ID"])<=0)
					return false;

				$arFieldsTmp = $arFields;
				UnSet($arFieldsTmp["CITY_ID"]);
				CSaleLocation::AddLocation($arFieldsTmp);
			}
		}
		elseif ($arFields["REGION_ID"] == '')
		{
			//CSaleLocation::DeleteRegion($arLocRes["REGION_ID"]);
			$arFields["REGION_ID"] = 0;
		}
		else
		{
			UnSet($arFields["REGION_ID"]);
		}

		CSaleLocation::UpdateLocation($ID, $arFields);

		return $ID;
	}

	
	/**
	 * <p>Функция удаляет местоположение с кодом ID. Функция так же удаляет город этого местоположения, страну этого местоположения (если она не входит больше ни в одно другое местоположение), а так же связи этого местоположения с группами местоположений и службами доставки. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код местоположения.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает <i>true</i> в случае успешного удаления
	 * местоположения и <i>false</i> - в противном случае.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (!CSaleLocation::Delete(12))
	 *    echo "Ошибка удаления местоположения";<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__delete.008e0aa2.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		if (!($arLocRes = CSaleLocation::GetByID($ID, LANGUAGE_ID)))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforeLocationDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		if (IntVal($arLocRes["CITY_ID"]) > 0)
			CSaleLocation::DeleteCity($arLocRes["CITY_ID"]);

		$bDelCountry = True;
		$db_res = CSaleLocation::GetList(
				array("SORT" => "ASC"),
				array("COUNTRY_ID" => $arLocRes["COUNTRY_ID"], "!ID"=>$ID),
				LANGUAGE_ID
			);
		if ($db_res->Fetch())
			$bDelCountry = false;

		if ($bDelCountry && IntVal($arLocRes["COUNTRY_ID"]) > 0)
			CSaleLocation::DeleteCountry($arLocRes["COUNTRY_ID"]);

		$bDelRegion = True;
		$db_res = CSaleLocation::GetList(
				array("SORT" => "ASC"),
				array("REGION_ID" => $arLocRes["REGION_ID"], "!ID"=>$ID),
				LANGUAGE_ID
			);
		if ($db_res->Fetch())
			$bDelRegion = false;

		if ($bDelRegion && IntVal($arLocRes["REGION_ID"]) > 0)
			CSaleLocation::DeleteRegion($arLocRes["REGION_ID"]);

		$DB->Query("DELETE FROM b_sale_location2location_group WHERE LOCATION_ID = ".$ID."", true);
		$DB->Query("DELETE FROM b_sale_delivery2location WHERE LOCATION_ID = ".$ID." AND LOCATION_TYPE = 'L'", true);
		$DB->Query("DELETE FROM b_sale_location_zip WHERE LOCATION_ID = ".$ID."", true);
		$bDelete = $DB->Query("DELETE FROM b_sale_location WHERE ID = ".$ID."", true);

		$events = GetModuleEvents("sale", "OnLocationDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		return $bDelete;
	}

	public static function OnLangDelete($strLang)
	{
		global $DB;
		$DB->Query("DELETE FROM b_sale_location_city_lang WHERE LID = '".$DB->ForSql($strLang)."'", true);
		$DB->Query("DELETE FROM b_sale_location_country_lang WHERE LID = '".$DB->ForSql($strLang)."'", true);
		return True;
	}

	
	/**
	 * <p>Функция удаляет все местоположения из базы.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__deleteall.1cda6559.php
	 * @author Bitrix
	 */
	public static function DeleteAll()
	{
		global $DB;

		$db_events = GetModuleEvents("sale", "OnBeforeLocationDeleteAll");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent)===false)
				return false;

		$DB->Query("DELETE FROM b_sale_location2location_group");
		$DB->Query("DELETE FROM b_sale_location_group_lang");
		$DB->Query("DELETE FROM b_sale_location_group");

		$DB->Query("DELETE FROM b_sale_delivery2location");
		$DB->Query("DELETE FROM b_sale_location");

		$DB->Query("DELETE FROM b_sale_location_city_lang");
		$DB->Query("DELETE FROM b_sale_location_city");

		$DB->Query("DELETE FROM b_sale_location_country_lang");
		$DB->Query("DELETE FROM b_sale_location_country");

		$DB->Query("DELETE FROM b_sale_location_region_lang");
		$DB->Query("DELETE FROM b_sale_location_region");

		$DB->Query("DELETE FROM b_sale_location_zip");

		$events = GetModuleEvents("sale", "OnLocationDeleteAll");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent);

	}

	public static function GetLocationZIP($location)
	{
		global $DB;

		return $DB->Query("SELECT ZIP FROM b_sale_location_zip WHERE LOCATION_ID='".$DB->ForSql($location)."'");
	}

	public static function GetByZIP($zip)
	{
		global $DB;

		$dbRes = $DB->Query('SELECT LOCATION_ID FROM b_sale_location_zip WHERE ZIP=\''.$DB->ForSql($zip).'\'');
		if ($arRes = $dbRes->Fetch())
			return CSaleLocation::GetByID($arRes['LOCATION_ID']);
		else
			return false;
	}

	public static function ClearLocationZIP($location)
	{
		global $DB;

		$query = "DELETE FROM b_sale_location_zip WHERE LOCATION_ID='".$DB->ForSql($location)."'";
		$DB->Query($query);

		return;
	}

	public static function ClearAllLocationZIP()
	{
		global $DB;
		$DB->Query("DELETE FROM b_sale_location_zip");
	}

	public static function AddLocationZIP($location, $ZIP, $bSync = false)
	{
		global $DB;

		$arInsert = array(
			"LOCATION_ID" => intval($location),
			"ZIP" => intval($ZIP),
		);

		if ($bSync)
		{
			$cnt = $DB->Update(
				'b_sale_location_zip',
				$arInsert,
				"WHERE LOCATION_ID='".$arInsert["LOCATION_ID"]."' AND ZIP='".$arInsert["ZIP"]."'"
			);

			if ($cnt <= 0)
			{
				$bSync = false;
			}
		}

		if (!$bSync)
		{
			$DB->Insert('b_sale_location_zip', $arInsert);
		}

		return;
	}

	public static function SetLocationZIP($location, $arZipList)
	{
		global $DB;

		if (is_array($arZipList))
		{
			CSaleLocation::ClearLocationZIP($location);

			$arInsert = array(
				"LOCATION_ID" => "'".$DB->ForSql($location)."'",
				"ZIP" => '',
			);

			foreach ($arZipList as $ZIP)
			{
				if (strlen($ZIP) > 0)
				{
					$arInsert["ZIP"] = "'".$DB->ForSql($ZIP)."'";
					$DB->Insert('b_sale_location_zip', $arInsert);
				}
			}
		}

		return;
	}

	function _GetZIPImportStats()
	{
		global $DB;

		$query = "SELECT COUNT(*) AS CNT, COUNT(DISTINCT LOCATION_ID) AS CITY_CNT FROM b_sale_location_zip";
		$rsStats = $DB->Query($query);
		$arStat = $rsStats->Fetch();

		return $arStat;
	}

	function _GetCityImport($arCityName, $country_id = false)
	{
		global $DB;

		$arQueryFields = array('LCL.NAME', 'LCL.SHORT_NAME');

		$arWhere = array();
		foreach ($arCityName as $city_name)
		{
			$city_name = $DB->ForSql($city_name);
			foreach ($arQueryFields as $field)
			{
				if (strlen($field) > 0)
					$arWhere[] = $field."='".$city_name."'";
			}
		}

		if (count($arWhere) <= 0) return false;
		$strWhere = implode(' OR ', $arWhere);

		if ($country_id)
		{
			$strWhere = 'L.COUNTRY_ID=\''.intval($country_id).'\' AND ('.$strWhere.')';
		}

		$query = "
SELECT L.ID, L.CITY_ID
FROM b_sale_location L
LEFT JOIN b_sale_location_city_lang LCL ON L.CITY_ID=LCL.CITY_ID
WHERE ".$strWhere;

		$dbList = $DB->Query($query);

		if ($arCity = $dbList->Fetch())
			return $arCity;
		else
			return false;
	}
}
?>