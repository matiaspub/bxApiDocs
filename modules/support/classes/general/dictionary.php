<?
IncludeModuleLangFile(__FILE__);


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
class CAllTicketDictionary
{
	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." <br>Class: CAllTicketDictionary<br>File: ".__FILE__;
	}

	public static function GetDefault($type, $siteID=SITE_ID)
	{
		if ($siteID=="all")
		{
			$siteID = "";
		}
		$arFilter = array("DEFAULT" => "Y", "TYPE" => $type, "SITE" => $siteID);
		$v2 = $v3 = null;
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		$ar = $rs->Fetch();
		return $ar["ID"];
	}

	public static function GetNextSort($typeID)
	{
		global $DB;
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetNextSort<br>Line: ";
		$strSql = "SELECT max(C_SORT) MAX_SORT FROM b_ticket_dictionary WHERE C_TYPE='".$DB->ForSql($typeID,5)."'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["MAX_SORT"])+100;
	}

	public static function GetDropDown($type="C", $siteID=false, $sla_id=false)
	{
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetDropDown<br>Line: ";
		global $DB;
		if ($siteID==false || $siteID=="all")
		{
			$siteID = "";
		}
		$arFilter = array("TYPE" => $type, "SITE" => $siteID);
		$v2 = $v3 = null;
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		
		$oldFunctionality = COption::GetOptionString( "support", "SUPPORT_OLD_FUNCTIONALITY", "Y" );
		if( intval( $sla_id ) <= 0 || $oldFunctionality != "Y" || ( $type != "C" && $type!="K" && $type!="M" ) ) return $rs;
		
		switch($type)
		{
			case "C": $strSql = "SELECT CATEGORY_ID as DID FROM b_ticket_sla_2_category WHERE SLA_ID=" . intval( $sla_id ); break;
			case "K": $strSql = "SELECT CRITICALITY_ID as DID FROM b_ticket_sla_2_criticality WHERE SLA_ID=" . intval( $sla_id ); break;
			case "M": $strSql = "SELECT MARK_ID as DID FROM b_ticket_sla_2_mark WHERE SLA_ID=" . intval( $sla_id ); break;
		}
		$r = $DB->Query( $strSql, false, $err_mess . __LINE__ );
		while( $a = $r->Fetch() ) $arDID[] = $a["DID"];
		$arRecords = array();
		while( $ar = $rs->Fetch() ) if( is_array( $arDID ) && ( in_array( $ar["ID"], $arDID ) || in_array( 0,$arDID ) ) ) $arRecords[] = $ar;
		
		$rs = new CDBResult;
		$rs->InitFromArray($arRecords);
		
		return $rs;
	}


	public static function GetDropDownArray($siteID = false, $SLA_ID = false, $arUnsetType = Array("F"))
	{
		//M, C, K, S, SR, D, F
		global $DB;
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetDropDownArray<br>Line: ";

		if ($siteID == false || $siteID == "all")
			$siteID = "";

		$arFilter = Array("SITE" => $siteID);

		$arReturn = Array();
		$v2 = $v3 = null;
		$rs = CTicketDictionary::GetList(($v1="s_dropdown"), $v2, $arFilter, $v3);
		while ($ar = $rs->Fetch())
		{
			if (in_array($ar["C_TYPE"], $arUnsetType))
				continue;

			$arReturn[$ar["C_TYPE"]][$ar["ID"]] = $ar;
		}
		
		$oldFunctionality = COption::GetOptionString( "support", "SUPPORT_OLD_FUNCTIONALITY", "Y" );
		if( intval($SLA_ID) > 0 && $oldFunctionality == "Y" )
		{
			$SLA_ID = intval($SLA_ID);

			$strSql = "SELECT 'M' as C_TYPE, SLA_ID, MARK_ID as DIC_ID FROM b_ticket_sla_2_mark WHERE SLA_ID = ".$SLA_ID."
						UNION ALL
						SELECT 'K' as C_TYPE, SLA_ID, CRITICALITY_ID as DIC_ID FROM b_ticket_sla_2_criticality WHERE SLA_ID = ".$SLA_ID."
						UNION ALL
						SELECT 'C' as C_TYPE, SLA_ID, CATEGORY_ID as DIC_ID FROM b_ticket_sla_2_category WHERE SLA_ID = ".$SLA_ID;

			$r = $DB->Query($strSql, false, $err_mess.__LINE__);

			$arUnset = Array();
			while ($ar = $r->Fetch())
			{
				if ($ar["DIC_ID"] == 0)
					continue;
				else
					$arUnset[$ar["C_TYPE"]][] = $ar["DIC_ID"];
			}

			if (!empty($arUnset) && !empty($arReturn))
			{
				foreach ($arReturn as $type => $arID)
				{
					if (!array_key_exists($type, $arUnset))
						continue;

					$arID = array_keys($arID);
					$arID = array_diff($arID, $arUnset[$type]);
					foreach ($arID as $val)
						unset($arReturn[$type][$val]);
				}
			}
		}
		
		return $arReturn;
	}

	// get array of languages related to contract
	public static function GetSiteArray($DICTIONARY_ID)
	{
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetSiteArray<br>Line: ";
		global $DB;
		$DICTIONARY_ID = intval($DICTIONARY_ID);
		if ($DICTIONARY_ID<=0) return false;
		$arrRes = array();
		$strSql = "
			SELECT
				DS.SITE_ID
			FROM
				b_ticket_dictionary_2_site DS
			WHERE
				DS.DICTIONARY_ID = $DICTIONARY_ID
			";

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($ar = $rs->Fetch()) $arrRes[] = $ar["SITE_ID"];
		return $arrRes;
	}

	public static function GetSiteArrayForAllDictionaries()
	{
		static $GetSiteArrayForAllDictCache;
		if(is_array($GetSiteArrayForAllDictCache))
		{
			return $GetSiteArrayForAllDictCache;
		}

		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetSiteArrayForAllDictionaries<br>Line: ";
		global $DB;
		$GetSiteArrayForAllDictCache = array();
		$strSql = "
			SELECT
				DS.SITE_ID,
				DS.DICTIONARY_ID
			FROM
				b_ticket_dictionary_2_site DS
			";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($ar = $rs->Fetch())
		{
			$GetSiteArrayForAllDictCache[$ar["DICTIONARY_ID"]][] = $ar["SITE_ID"];
		}
		return $GetSiteArrayForAllDictCache;
	}

	public static function GetTypeList()
	{
		$arr = array(
			"reference"=>array(
				GetMessage("SUP_CATEGORY"),
				GetMessage("SUP_CRITICALITY"),
				GetMessage("SUP_STATUS"),
				GetMessage("SUP_MARK"),
				GetMessage("SUP_FUA"),
				GetMessage("SUP_SOURCE"),
				GetMessage("SUP_DIFFICULTY")
				),
			"reference_id"=>array(
				"C",
				"K",
				"S",
				"M",
				"F",
				"SR",
				"D")
			);
		return $arr;
	}

	public static function GetTypeNameByID($id)
	{
		$arr = CTicketDictionary::GetTypeList();
		$KEY = array_search($id, $arr["reference_id"]);
		return $arr["reference"][$KEY];
	}

	
	/**
	* <p>Метод возвращает данные по одной записи справочника.</p>
	*
	*
	* @param int $ID  ID записи.</bo
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* Array
	* (
	*     [ID] =&gt; 3
	*     [LID] =&gt; ru
	*     [C_TYPE] =&gt; C
	*     [SID] =&gt; 
	*     [SET_AS_DEFAULT] =&gt; N
	*     [C_SORT] =&gt; 500
	*     [NAME] =&gt; Доставка программного продукта и обновлений
	*     [DESCR] =&gt; 
	*     [RESPONSIBLE_USER_ID] =&gt; 2
	*     [ <code>EVENT1</code>] =&gt; ticket
	*     [ <code>EVENT2</code>] =&gt; 
	*     [ <code>EVENT3</code>] =&gt; 
	* )
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticketdictionary/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($id)
	{
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetByID<br>Line: ";
		global $DB;
		$id = intval($id);
		if ($id<=0)
		{
			return;
		}
		$by = $order = $is_filtered = null;
		$res = CTicketDictionary::GetList($by, $order, array("ID" => $id), $is_filtered);
		return $res;
	}

	public static function GetBySID($sid, $type, $siteID=SITE_ID)
	{
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: GetBySID<br>Line: ";
		global $DB;
		$v1 = $v2 = $v3 = null;
		$rs = CTicketDictionary::GetList($v1, $v2, array("SITE_ID"=>$siteID, "TYPE"=>$type, "SID"=>$sid), $v3);
		return $rs;
	}

	public static function Delete($id, $CHECK_RIGHTS="Y")
	{
		$err_mess = (CAllTicketDictionary::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB, $APPLICATION;
		$id = intval($id);
		if ($id<=0)
		{
			return;
		}
		$bAdmin = "N";
		if ($CHECK_RIGHTS=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
		}
		if ($bAdmin=="Y")
		{
			$DB->Query("DELETE FROM b_ticket_dictionary WHERE ID='$id'", false, $err_mess.__LINE__);
			$DB->Query('DELETE FROM b_ticket_dictionary_2_site WHERE DICTIONARY_ID=' . $id, false, $err_mess.__LINE__);
		}
	}

	public static function CheckFields($arFields, $id = false)
	{
		$arMsg = Array();

		if ( $id ===false && !(array_key_exists('NAME', $arFields) && strlen($arFields['NAME']) > 0) )
		{
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("SUP_FORGOT_NAME"));
		}

		if ($id !== false)
		{
			$rs = CTicketDictionary::GetByID($id);
			if (!$rs->Fetch())
			{
				$arMsg[] = array("id"=>"ID", "text"=> GetMessage("SUP_UNKNOWN_ID", array('#ID#' => $id)));
			}
		}

		if ( array_key_exists('SID', $arFields) && preg_match("#[^A-Za-z_0-9]#", $arFields['SID']) )
		{
			$arMsg[] = array("id"=>"SID", "text"=> GetMessage("SUP_INCORRECT_SID"));
		}
		elseif (
				strlen($arFields['SID']) > 0 && array_key_exists('arrSITE', $arFields) &&
				is_array($arFields['arrSITE']) && count($arFields['arrSITE']) > 0
			)
		{
			$arFilter = array(
				"TYPE"	=> $arFields['C_TYPE'],
				"SID"	=> $arFields['SID'],
				"SITE"	=> $arFields['arrSITE'],
			);
			if (intval($id) > 0)
			{
				$arFilter['ID'] = '~'.intval($id);
			}

			$v1 = $v2 = $v3 = null;
			$z = CTicketDictionary::GetList($v1, $v2, $arFilter, $v3);
			if ($zr = $z->Fetch())
			{
				$arMsg[] = array(
							"id"=>"SID",
							"text"=> GetMessage(
									'SUP_SID_ALREADY_IN_USE',
									array(
										'#TYPE#' => CTicketDictionary::GetTypeNameByID($arFields['C_TYPE']),
										'#LANG#' => strlen($zr['LID']) > 0? $zr['LID']: $zr['SITE_ID'],
										'#RECORD_ID#' => $zr['ID'],
									)
							)
					);
			}
		}

		if (count($arMsg) > 0)
		{
			$e = new CAdminException($arMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function Add($arFields)
	{
		global $DB;
		$DB->StartTransaction();
		if (!CTicketDictionary::CheckFields($arFields))
		{
			$DB->Rollback();
			return false;
		}

		CTicketDictionary::__CleanDefault($arFields);

		$id = intval($DB->Add('b_ticket_dictionary', $arFields));
		if ($id > 0)
		{
			CTicketDictionary::__SetSites($id, $arFields);
			$DB->Commit();
			return $id;
		}

		$DB->Rollback();
		$GLOBALS['APPLICATION']->ThrowException(GetMessage('SUP_ERROR_ADD_DICTONARY'));
		return false;
	}

	public static function Update($id, $arFields)
	{
		global $DB;
		$DB->StartTransaction();
		$id = intval($id);
		if (!CTicketDictionary::CheckFields($arFields, $id))
		{
			$DB->Rollback();
			return false;
		}

		CTicketDictionary::__CleanDefault($arFields);

		$strUpdate = $DB->PrepareUpdate('b_ticket_dictionary', $arFields);
		$rs = $DB->Query('UPDATE b_ticket_dictionary SET ' . $strUpdate . ' WHERE ID=' . $id);
		if ($rs->AffectedRowsCount() > 0);
		{
			CTicketDictionary::__SetSites($id, $arFields);
			$DB->Commit();
			return true;
		}

		$DB->Rollback();
		$GLOBALS['APPLICATION']->ThrowException(GetMessage('SUP_ERROR_UPDATE_DICTONARY'));
		return false;
	}

	public static function __CleanDefault(&$arFields)
	{
		if (
				array_key_exists('SET_AS_DEFAULT', $arFields) && $arFields['SET_AS_DEFAULT'] == 'Y' &&
				array_key_exists('arrSITE', $arFields) && array_key_exists('C_TYPE',  $arFields)
			)
		{
			global $DB;
			$arFilter = array(
				'TYPE'	=> $arFields['C_TYPE'],
				'SITE'	=> $arFields['arrSITE']
				);
			$v1 = $v2 = $v3 = null;
			$z = CTicketDictionary::GetList($v1, $v2, $arFilter, $v3);
			while ($zr = $z->Fetch())
			{
				$DB->Update('b_ticket_dictionary', array('SET_AS_DEFAULT' => "'N'"), 'WHERE ID=' . $zr['ID'], '', false, false, false);
			}
		}
		elseif (array_key_exists('SET_AS_DEFAULT', $arFields))
		{
			$arFields['SET_AS_DEFAULT'] = 'N';
		}
	}

	public static function __SetSites($id, $arFields)
	{
		global $DB;
		if (!array_key_exists('arrSITE', $arFields))
		{
			return ;
		}
		$id = intval($id);
		$DB->Query('DELETE FROM b_ticket_dictionary_2_site WHERE DICTIONARY_ID=' . $id);
		if (is_array($arFields['arrSITE']) && count($arFields['arrSITE']) > 0)
		{
			foreach($arFields['arrSITE'] as $sid)
			{
				$strSql = "INSERT INTO b_ticket_dictionary_2_site (DICTIONARY_ID, SITE_ID) VALUES ($id, '".$DB->ForSql($sid, 2)."')";
				$DB->Query($strSql);
			}
		}
	}
}

?>
