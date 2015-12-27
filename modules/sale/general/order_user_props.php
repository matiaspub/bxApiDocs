<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/index.php
 * @author Bitrix
 */
class CAllSaleOrderUserProps
{
	/*
	public static function TranslateLocationPropertyValues($personTypeId, &$orderProps)
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			// location ID to CODE
			$dbOrderProps = CSaleOrderProps::GetList(
				array("SORT" => "ASC"),
				array(
					'PERSON_TYPE_ID' => $personTypeId
				),
				false,
				false,
				array("ID", "NAME", "TYPE", "IS_LOCATION", "IS_LOCATION4TAX", "IS_PROFILE_NAME", "IS_PAYER", "IS_EMAIL", "REQUIED", "SORT", "IS_ZIP", "CODE", "MULTIPLE")
			);
			while($item = $dbOrderProps->fetch())
			{
				if($item['TYPE'] == 'LOCATION' && strlen($orderProps[$item['ID']]))
					$orderProps[$item['ID']] = CSaleLocation::getLocationCodeByID($orderProps[$item['ID']]);
			}
		}
	}
	*/

	static function DoSaveUserProfile($userId, $profileId, $profileName, $personTypeId, $orderProps, &$arErrors)
	{
		$profileId = intval($profileId);

		$arIDs = array();
		if ($profileId > 0)
		{
			$dbProfile = CSaleOrderUserProps::GetList(
				array(),
				array("ID" => $profileId),
				false,
				false,
				array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID")
			);
			$arProfile = $dbProfile->Fetch();
			if (!$arProfile)
			{
				$arErrors[] = array("CODE" => "PROFILE_NOT_FOUND", "TEXT" => GetMessage('SKGOUP_PROFILE_NOT_FOUND'));
				return false;
			}
			if ($arProfile["USER_ID"] != $userId || $arProfile["PERSON_TYPE_ID"] != $personTypeId)
			{
				$arErrors[] = array("CODE" => "PARAM", "TEXT" => GetMessage('SKGOUP_PARRAMS_ERROR'));
				return false;
			}

			//if (strlen($profileName) > 0 && $profileName != $arProfile["NAME"])
			if (strlen($profileName) > 0)
			{
				$arFields = array("NAME" => $profileName, "USER_ID" => $userId);
				CSaleOrderUserProps::Update($profileId, $arFields);
			}

			$dbUserPropsValues = CSaleOrderUserPropsValue::GetList(
				array(),
				array("USER_PROPS_ID" => $profileId),
				false,
				false,
				array("ID", "ORDER_PROPS_ID")
			);
			while ($arUserPropsValue = $dbUserPropsValues->Fetch())
				$arIDs[$arUserPropsValue["ORDER_PROPS_ID"]] = $arUserPropsValue["ID"];
		}

		if (!is_array($orderProps))
		{
			$dbOrderPropsValues = CSaleOrderPropsValue::GetList(
				array(),
				array("ORDER_ID" => intval($orderProps)),
				false,
				false,
				array("ORDER_PROPS_ID", "VALUE")
			);
			$orderProps = array();
			while ($arOrderPropsValue = $dbOrderPropsValues->Fetch())
				$orderProps[$arOrderPropsValue["ORDER_PROPS_ID"]] = $arOrderPropsValue["VALUE"];
		}
		/*
		TRANSLATION_CUT_OFF
		else
		{
			// map location ID to CODE, if taken from parameters
			static::TranslateLocationPropertyValues($personTypeId, $orderProps);
		}
		*/

		$dbOrderProperties = CSaleOrderProps::GetList(
			array(),
			array("PERSON_TYPE_ID" => $personTypeId, "ACTIVE" => "Y", "UTIL" => "N", "USER_PROPS" => "Y"),
			false,
			false,
			array("ID", "TYPE", "NAME", "CODE")
		);
		while ($arOrderProperty = $dbOrderProperties->Fetch())
		{
			$curVal = $orderProps[$arOrderProperty["ID"]];
			if (($arOrderProperty["TYPE"] == "MULTISELECT") && is_array($curVal))
				$curVal = implode(",", $curVal);

			if (strlen($curVal) > 0)
			{
				if ($profileId <= 0)
				{
					if (strlen($profileName) <= 0)
						$profileName = GetMessage("SOA_PROFILE")." ".Date("Y-m-d");

					$arFields = array(
						"NAME" => $profileName,
						"USER_ID" => $userId,
						"PERSON_TYPE_ID" => $personTypeId
					);
					$profileId = CSaleOrderUserProps::Add($arFields);
				}

				if (array_key_exists($arOrderProperty["ID"], $arIDs))
				{
					$arFields = Array(
						"NAME" => $arOrderProperty["NAME"],
						"VALUE" => $curVal
					);
					CSaleOrderUserPropsValue::Update($arIDs[$arOrderProperty["ID"]], $arFields);
					unset($arIDs[$arOrderProperty["ID"]]);
				}
				else
				{
					$arFields = array(
						"USER_PROPS_ID" => $profileId,
						"ORDER_PROPS_ID" => $arOrderProperty["ID"],
						"NAME" => $arOrderProperty["NAME"],
						"VALUE" => $curVal
					);
					CSaleOrderUserPropsValue::Add($arFields);
				}
			}
		}

		foreach ($arIDs as $id)
			CSaleOrderUserPropsValue::Delete($id);
	}

	public static function DoLoadProfiles($userId, $personTypeId = null)
	{
		$userId = intval($userId);

		if ($userId <= 0)
			return null;

		$arResult = array();
		$arFilter = array("USER_ID" => $userId);

		if ($personTypeId != null)
			$arFilter["PERSON_TYPE_ID"] = $personTypeId;

		$dbProfile = CSaleOrderUserProps::GetList(
			array("DATE_UPDATE" => "DESC", "NAME" => "ASC"),
			$arFilter,
			false,
			false,
			array("ID", "NAME", "PERSON_TYPE_ID", "DATE_UPDATE")
		);

		while ($arProfile = $dbProfile->GetNext())
		{
			if (!array_key_exists($arProfile["PERSON_TYPE_ID"], $arResult))
				$arResult[$arProfile["PERSON_TYPE_ID"]] = array();

			$arResult[$arProfile["PERSON_TYPE_ID"]][$arProfile["ID"]] = array("NAME" => $arProfile["NAME"], "VALUES" => array(), "VALUES_ORIG" => array());

			$dbProps = CSaleOrderUserPropsValue::GetList(
				array(),
				array("USER_PROPS_ID" => $arProfile["ID"]),
				false,
				false,
				array("ORDER_PROPS_ID", "NAME", "VALUE", "VALUE_ORIG")
			);
			while ($arProps = $dbProps->GetNext())
			{
				$arResult[$arProfile["PERSON_TYPE_ID"]][$arProfile["ID"]]["VALUES"][$arProps["ORDER_PROPS_ID"]] = $arProps["VALUE"];

				if(isset($arProps["VALUE_ORIG"]))
					$arResult[$arProfile["PERSON_TYPE_ID"]][$arProfile["ID"]]["VALUES_ORIG"][$arProps["ORDER_PROPS_ID"]] = $arProps["VALUE_ORIG"];
			}
		}

		if (count($arResult) > 0)
		{
			if ($personTypeId != null)
				$arResult = $arResult[$personTypeId];
		}

		return $arResult;
	}

	
	/**
	* <p>Метод возвращает параметры профиля покупателя с кодом ID. Метод динамичный. </p>
	*
	*
	* @param int $ID  Код профиля покупателя.
	*
	* @return array <p>Возвращается ассоциативный массив параметров профиля
	* покупателя с ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код профиля покупателя.</td> </tr> <tr>
	* <td>NAME</td> <td>Название профиля.</td> </tr> <tr> <td>USER_ID</td> <td>Код пользователя,
	* которому принадлежит профиль.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	* плательщика.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата последнего изменения
	* профиля.</td> </tr> </table> <p>  </p<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if ($ar = CSaleOrderUserProps::GetByID(12))
	* {
	*    echo "&lt;pre&gt;";
	*    print_r($ar);
	*    echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/csaleorderuserprops__getbyid.2fb0237e.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_user_props ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $USER;

		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOUP_EMPTY_PERS_TYPE"), "ERROR_NO_PERSON_TYPE_ID");
			return false;
		}

		if (false && !$USER->IsAuthorized())
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOUP_UNAUTH"), "ERROR_NO_AUTH");
			return false;
		}

		if (!is_set($arFields, "USER_ID"))
			$arFields["USER_ID"] = IntVal($USER->GetID());

		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && IntVal($arFields["USER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOUP_NO_USER_ID"), "ERROR_NO_PERSON_TYPE_ID");
			return false;
		}

		return True;
	}

	
	/**
	* <p>Метод обновляет параметры профиля покупателя с кодом ID на значения из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код профиля покупателя.
	*
	* @param array $arFields  Ассоциативный массив новых параметров профиля. Ключами являются
	* названия параметров, а значениями - соответствующие
	* значения.<br><br> Допустимые ключи:<ul> <li> <b>NAME</b> - название профиля
	* покупателя;</li> <li> <b>USER_ID</b> - код пользователя, которому
	* принадлежит профиль;</li> <li> <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li>
	* <b>DATE_UPDATE</b> - дата последнего изменения.</li> </ul>
	*
	* @return int <p>Возвращается код измененного профиля или <i>false</i> в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Изменим название профиля покупателя
	* $arFields = array(
	*    "NAME" =&gt; "Профиль 24"
	* );
	* if (!CSaleOrderUserProps::Update(258, $arFields))
	*    echo "Ошибка изменения профиля покупателя";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/csaleorderuserprops__update.4b826079.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if (!CSaleOrderUserProps::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_user_props", $arFields);

		$strSql =
			"UPDATE b_sale_user_props SET ".
			"	".$strUpdate.", ".
			"	DATE_UPDATE = ".$DB->GetNowFunction()." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	
	/**
	* <p>Метод удаляет все пустые профили из базы (т.е. профили, в которых нет свойств). Метод динамичный.</p> <br><br>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/csaleorderuserprops__clearempty.7bfe4eef.php
	* @author Bitrix
	*/
	public static function ClearEmpty()
	{
		global $DB;
		$strSql = 
			"SELECT UP.ID ".
			"FROM b_sale_user_props UP ".
			"	LEFT JOIN b_sale_user_props_value UPV ON (UP.ID = UPV.USER_PROPS_ID) ".
			"WHERE UPV.ID IS NULL ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($res = $db_res->Fetch())
		{
			$DB->Query("DELETE FROM b_sale_user_props WHERE ID = ".$res["ID"]."");
		}
	}

	
	/**
	* <p>Метод удаляет профиль покупателя с кодом ID. Вместе с профилем удаляются все его свойства. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код профиля покупателя.
	*
	* @return bool <p>Возвращается объект <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> в случае
	* успешного удаления и <i>false</i> - в противном случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* CSaleOrderUserProps::Delete(12);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderuserprops/csaleorderuserprops__delete.118435cf.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$DB->Query("DELETE FROM b_sale_user_props_value WHERE USER_PROPS_ID = ".$ID."", true);
		return $DB->Query("DELETE FROM b_sale_user_props WHERE ID = ".$ID."", true);
	}

	public static function OnUserDelete($ID)
	{
		$ID = IntVal($ID);
		$db_res = CSaleOrderUserProps::GetList(($b="ID"), ($o="ASC"), Array("USER_ID"=>$ID));
		while ($ar_res = $db_res->Fetch())
		{
			CSaleOrderUserProps::Delete(IntVal($ar_res["ID"]));
		}
		return True;
	}
}
?>