<?
IncludeModuleLangFile(__FILE__);

class CSupportSuperCoupon
{
	public static function Generate($arParams = array())
	{
		global $DB, $USER, $APPLICATION;
		if (!is_array($arParams))
		{
			$arParams = array();
		}

		if(array_key_exists('KEY_FORMAT', $arParams) && strlen($arParams['KEY_FORMAT']) > 0)
		{
			$couponFormat = $arParams['KEY_FORMAT'];
		}
		else 
		{ 
			$couponFormat = COption::GetOptionString('support', 'SUPERTICKET_COUPON_FORMAT');
		}
		
		$count = array_key_exists('COUNT_TICKETS', $arParams) ? intval($arParams['COUNT_TICKETS']) : 5;
		if ($count <= 0)
		{
			$count = 5;
		}
		
		$slaID = array_key_exists('SLA_ID', $arParams) ? $arParams['SLA_ID'] : COption::GetOptionString("support", 'SUPERTICKET_DEFAULT_SLA');
		$slaID = intval($slaID);
		if ($slaID <= 0)
		{
			$slaID = false;
		}
		
		$coupon = false;
		$DB->StartTransaction();
		for ($i = 0; $i < 100; ++$i)
		{
			$coupon = preg_replace_callback('|#|'.BX_UTF_PCRE_MODIFIER, array('CSupportSuperCoupon', '_getrandsymbol'), $couponFormat);
			$rs = CSupportSuperCoupon::GetList(false, array('COUPON' => $coupon));
			if ($rs->Fetch())
			{
				$coupon = false;
			}
			else 
			{
				break;
			}
		}
		
		if ($coupon !== false)
		{
			$arFields = array(
				'COUPON' => $coupon,
				'COUNT_TICKETS' => $count,
				'SLA_ID' => $slaID,
				'ACTIVE_FROM' => $arParams['ACTIVE_FROM'],
				'ACTIVE_TO' => $arParams['ACTIVE_TO'],
				'ACTIVE' => $arParams['ACTIVE'],
			);

			$ID = CSupportSuperCoupon::Add($arFields);
			if ($ID === false)
			{	
				$DB->Rollback();
				return $ID;
			}

		}
		else 
		{
			$DB->Rollback();
			$APPLICATION->ThrowException(GetMessage('SUP_ST_ERROR_NO_NEW_COUPON'));
		}
		$DB->Commit();
		
		return $coupon;
	}
	
	public static function Add($arFields)
	{
		global $DB, $USER;
		
		if(!CSupportSuperCoupon::__CheckFields($arFields))
			return false;
		
		$arFields['~TIMESTAMP_X'] = $DB->GetNowFunction();
		$arFields['~DATE_CREATE'] = $DB->GetNowFunction();
		
		if(isset($USER) && is_object($USER))
		{
			$arFields['CREATED_USER_ID'] = intval($USER->GetID());
		}
		
		if (array_key_exists('TIMESTAMP_X', $arFields))
		{
			unset($arFields['TIMESTAMP_X']);
		}
		
		if (array_key_exists('DATE_CREATE', $arFields))
		{
			unset($arFields['DATE_CREATE']);
		}
		
		return $DB->Add('b_ticket_supercoupons', $arFields);
	}
	
	public static function Update($ID, $arFields)
	{
		global $DB, $APPLICATION, $USER;
		
		$ID = intval($ID);
		if(!CSupportSuperCoupon::__CheckFields($arFields))
			return false;
		
		$arFields['~TIMESTAMP_X'] = $DB->GetNowFunction();
		if(isset($USER) && is_object($USER))
		{
			$arFields['UPDATED_USER_ID'] = $USER->GetID();
		}
		
		$strUpdate = $DB->PrepareUpdate('b_ticket_supercoupons', $arFields);
		if (strlen($strUpdate) > 0)
		{
			$strSql = "UPDATE b_ticket_supercoupons SET $strUpdate WHERE ID=$ID";
			$q = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$rows = intval($q->AffectedRowsCount());
		}
		else 
		{
			$APPLICATION->ThrowException(GetMessage('SUP_ST_ERROR_NO_UPDATE_DATA'));
			return false;
		}
		
		if ($rows <= 0)
		{
			$APPLICATION->ThrowException(GetMessage('SUP_ST_ERROR_NO_UPDATES_ROWS'));
			return false;
		}
		
		return true;
	}
	
	public static function UseCoupon($coupon)
	{
		global $DB, $USER;
		$ret = false;
		
		$arFields = false;
		$arLogFields = false;
		$arCoupon = false;

		$curr = ConvertTimeStamp();
		$arFilter = array(
			'LOGIC' => 'AND',
			
			'COUPON' => $coupon,
			'=ACTIVE' => 'Y',
			array(
				'LOGIC' => 'OR',
				
				'ACTIVE_FROM' => false,
				'<=ACTIVE_FROM' => $curr,
			),
			array(
				'LOGIC' => 'OR',
				
				'ACTIVE_TO' => false,
				'>=ACTIVE_TO' => $curr,
			)
		);
		
		
		$DB->StartTransaction();
		
		$rs = CSupportSuperCoupon::GetList(false, $arFilter);
		if ($arCoupon = $rs->Fetch())
		{
			$countTickets = intval($arCoupon['COUNT_TICKETS']);
			if ($countTickets > 0)
			{
				$countTickets--;
				$arFields = array(
					'COUNT_TICKETS' => $countTickets,
					'TIMESTAMP_X' => $DB->GetNowFunction(),
					//'UPDATED_USER_ID' => (isset($USER) && is_object($USER)) ? $USER->GetID() : false,
					'COUNT_USED' => 'COUNT_USED + 1'
				);
				if(isset($USER) && is_object($USER))
				{
					$arFields['UPDATED_USER_ID'] = $USER->GetID();
				}
				if ($aff_rows = $DB->Update('b_ticket_supercoupons', $arFields, 'WHERE ID=' . $arCoupon['ID']))
				{
					$ret = true;
				}
			}
			
			$arLogFields = array(
					'~TIMESTAMP_X' => $DB->GetNowFunction(),
					'COUPON_ID' => $arCoupon['ID'],
					'USER_ID' => ((isset($USER) && is_object($USER)) ? $USER->GetID() : false),
					'SUCCESS' => $ret ? 'Y' : 'N',
					'AFTER_COUNT' => $countTickets,
					'SESSION_ID' => (array_key_exists('SESS_SESSION_ID', $_SESSION) ? $_SESSION['SESS_SESSION_ID'] : false),
					'GUEST_ID' => (array_key_exists('SESS_GUEST_ID', $_SESSION) ? $_SESSION['SESS_GUEST_ID'] : false),
					'AFFECTED_ROWS' => $aff_rows,
					'COUPON' => $coupon,
				);
			
			$arInsert = $DB->PrepareInsert('b_ticket_supercoupons_log', $arLogFields);
			$strSql =
				"INSERT INTO b_ticket_supercoupons_log (".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, false, 'File: ' . __FILE__ . ' Line: ' . __LINE__);
			
			$arMail = array(
				'COUPON' => $coupon,
				'COUPON_ID' => $arCoupon['ID'],
				'DATE' => ConvertTimeStamp(false, 'FULL'),
				'USER_ID' => ((isset($USER) && is_object($USER)) ? $USER->GetID() : -1),
				'SESSION_ID' => (array_key_exists('SESS_SESSION_ID', $_SESSION) ? $_SESSION['SESS_SESSION_ID'] : -1),
				'GUEST_ID' => (array_key_exists('SESS_GUEST_ID', $_SESSION) ? $_SESSION['SESS_GUEST_ID'] : -1),
			);
			
			$rsEvents = GetModuleEvents('support', 'OnBeforeSendCouponEMail');
			while ($arEvent = $rsEvents->Fetch())
			{
				$arMail = ExecuteModuleEventEx($arEvent, array($arMail));
			}
			
			if ($arMail)
			{
				$e = new CEvent();
				$e->Send('TICKET_GENERATE_SUPERCOUPON', SITE_ID, $arMail);
			}
		}
		
		$DB->Commit();
		
		$rsEvents = GetModuleEvents('support', 'OnAfterUseCoupon');
		while ($arEvent = $rsEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($coupon, $arCoupon, $arFields, $arLogFields));
		}
		
		return $ret;
	}
	
	public static function GetList($arOrder = array(), $arFilter = array())
	{
		global $DB;
		$arFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'COUPON' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.COUPON',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'COUNT_TICKETS' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.COUNT_TICKETS',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'TIMESTAMP_X' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.TIMESTAMP_X',
				'FIELD_TYPE' => 'datetime', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'DATE_CREATE' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.DATE_CREATE',
				'FIELD_TYPE' => 'datetime', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'CREATED_USER_ID' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.CREATED_USER_ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'UPDATED_USER_ID' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.UPDATED_USER_ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'ACTIVE' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.ACTIVE',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'ACTIVE_FROM' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.ACTIVE_FROM',
				'FIELD_TYPE' => 'date', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'ACTIVE_TO' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.ACTIVE_TO',
				'FIELD_TYPE' => 'date', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'COUNT_USED' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.COUNT_USED',
				'FIELD_TYPE' => 'date', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'SLA_ID' => array(
				'TABLE_ALIAS' => 'S',
				'FIELD_NAME' => 'S.ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'SLA_NAME' => array(
				'TABLE_ALIAS' => 'S',
				'FIELD_NAME' => 'S.NAME',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
		);		
		
		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arFields);
		
		$where = $obQueryWhere->GetQuery($arFilter);
		
		$order = '';
		if (is_array($arOrder))
		{
			foreach ($arOrder as $k => $v)
			{
				if (array_key_exists($k, $arFields))
				{
					$v = strtoupper($v);
					if($v != 'DESC')
					{
						$v  ='ASC';
					}
					if (strlen($order) > 0)
					{
						$order .= ', ';
					}
					$order .= $arFields[$k]['FIELD_NAME'] . ' ' . $v;
				}		
			}
		}

		$strQuery = 'SELECT C.ID, C.COUPON, C.COUNT_TICKETS, C.CREATED_USER_ID, C.UPDATED_USER_ID, C.ACTIVE ACTIVE, C.COUNT_USED COUNT_USED,
		'.$DB->DateToCharFunction('C.TIMESTAMP_X').' TIMESTAMP_X,
		'.$DB->DateToCharFunction('C.DATE_CREATE').' DATE_CREATE,
		'.$DB->DateToCharFunction('C.ACTIVE_FROM', 'SHORT').' ACTIVE_FROM,
		'.$DB->DateToCharFunction('C.ACTIVE_TO', 'SHORT').' ACTIVE_TO,
		UCR.LOGIN CREATED_LOGIN, UCR.NAME CREATED_FIRST_NAME, UCR.LAST_NAME CREATED_LAST_NAME,
		UUP.LOGIN UPDATED_LOGIN, UUP.NAME UPDATED_FIRST_NAME, UUP.LAST_NAME UPDATED_LAST_NAME,
		S.ID SLA_ID, S.NAME SLA_NAME
		FROM b_ticket_supercoupons C
		LEFT JOIN b_user UCR ON (C.CREATED_USER_ID IS NOT NULL AND C.CREATED_USER_ID = UCR.ID)
		LEFT JOIN b_user UUP ON (C.CREATED_USER_ID IS NOT NULL AND C.UPDATED_USER_ID = UUP.ID)
		LEFT JOIN b_ticket_sla S ON (C.SLA_ID IS NOT NULL AND C.SLA_ID = S.ID)
		';
		
		if (strlen($where) > 0)
		{
			$strQuery .= ' WHERE ' . $where;
		}
		
		if (strlen($order) > 0)
		{
			$strQuery .= ' ORDER BY ' . $order;
		}
		
		return $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
	
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		if ($ID > 0)
		{
			$DB->Query('DELETE FROM b_ticket_supercoupons WHERE ID=' . $ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return true;
	}
	
	public static function GetLogList($arOrder = array(), $arFilter = array())
	{
		global $DB;
		$arFields = array(
			'COUPON_ID' => array(
				'TABLE_ALIAS' => 'C',
				'FIELD_NAME' => 'C.ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			
			
			'COUPON' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COUPON',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'SUCCESS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.SUCCESS',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'USER_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.USER_ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'TIMESTAMP_X' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TIMESTAMP_X',
				'FIELD_TYPE' => 'datetime', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
		);
		
		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arFields);
		
		$where = $obQueryWhere->GetQuery($arFilter);
		
		$order = '';
		if (is_array($arOrder))
		{
			foreach ($arOrder as $k => $v)
			{
				if (array_key_exists($k, $arFields))
				{
					$v = strtoupper($v);
					if($v != 'DESC')
					{
						$v  ='ASC';
					}
					if (strlen($order) > 0)
					{
						$order .= ', ';
					}
					$order .= $arFields[$k]['FIELD_NAME'] . ' ' . $v;
				}		
			}
		}
		
		$strQuery = "SELECT  C.ID COUPON_ID, L.COUPON COUPON,
		L.USER_ID USER_ID, L.SUCCESS SUCCESS, L.AFTER_COUNT AFTER_COUNT,
		L.SESSION_ID SESSION_ID, L.GUEST_ID GUEST_ID,
		U.LOGIN LOGIN, U.NAME FIRST_NAME, U.LAST_NAME LAST_NAME,
		".$DB->DateToCharFunction('L.TIMESTAMP_X')." TIMESTAMP_X
		FROM b_ticket_supercoupons_log L
		LEFT JOIN b_ticket_supercoupons C ON (L.COUPON_ID = C.ID)
		LEFT JOIN b_user U ON (L.USER_ID IS NOT NULL AND L.USER_ID = U.ID)";
		
		if (strlen($where) > 0)
		{
			$strQuery .= ' WHERE ' . $where;
		}
		
		if (strlen($order) > 0)
		{
			$strQuery .= ' ORDER BY ' . $order;
		}
		
		return $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		
	}
	
	public static function _getrandsymbol($x)
	{
		return ToUpper(randString(1));
	}
	
	public static function __CheckFields($arFields)
	{
		$aMsg = array();
		
		if (is_set($arFields, "ACTIVE_FROM") && CheckDateTime($arFields['ACTIVE_FROM'], 'DD.MM.YYYY') && 
			is_set($arFields, "ACTIVE_TO") && CheckDateTime($arFields['ACTIVE_TO'], 'DD.MM.YYYY'))
		{
			$dateElementsFrom = explode(".", $arFields["ACTIVE_FROM"]);
			$_activeFrom = mktime(0,0,0, $dateElementsFrom[1], $dateElementsFrom[0], $dateElementsFrom[2]);

			$dateElementsTo = explode(".", $arFields["ACTIVE_TO"]);
			$_activeTo = mktime(0,0,0, $dateElementsTo[1], $dateElementsTo[0], $dateElementsTo[2]);
			if ($_activeTo <= $_activeFrom)	
				$aMsg[] = array("id"=>"ACTIVE_TO", "text"=>GetMessage("SUP_ST_ERR_DATE_INTERVAL"));
		}	
			
		if(is_set($arFields, "ACTIVE") && !in_array($arFields['ACTIVE'], Array('Y','N')))
		{
			$aMsg[] = array("id"=>"ACTIVE", "text"=>GetMessage("SUP_ST_ERR_ACTIVE"));
		}
				
		if(is_set($arFields, "SLA_ID") && IntVal($arFields['SLA_ID']) == 0)
		{
			$aMsg[] = array("id"=>"SLA_ID", "text"=>GetMessage("SUP_ST_ERR_SLA_ID"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}
}
?>