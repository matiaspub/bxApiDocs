<?
IncludeModuleLangFile(__FILE__);

global $BX_MAIL_ERRORs, $B_MAIL_MAX_ALLOWED;
$BX_MAIL_ERRORs = Array();
$B_MAIL_MAX_ALLOWED = false;

class CMail
{
	const ERR_DEFAULT = 1;
	const ERR_DB      = 2;

	const ERR_API_DEFAULT            = 101;
	const ERR_API_DENIED             = 102;
	const ERR_API_DOMAINLIST_EMPTY   = 103;
	const ERR_API_NAME_OCCUPIED      = 104;
	const ERR_API_USER_NOTFOUND      = 105;
	const ERR_API_EMPTY_DOMAIN       = 106;
	const ERR_API_EMPTY_NAME         = 107;
	const ERR_API_EMPTY_PASSWORD     = 108;
	const ERR_API_SHORT_PASSWORD     = 109;
	const ERR_API_BAD_NAME           = 110;
	const ERR_API_BAD_PASSWORD       = 111;
	const ERR_API_PASSWORD_LIKELOGIN = 112;
	const ERR_API_LONG_NAME          = 113;
	const ERR_API_LONG_PASSWORD      = 114;
	const ERR_API_OP_DENIED          = 115;

	const ERR_API_DOMAIN_OCCUPIED    = 201;
	const ERR_API_BAD_DOMAIN         = 202;
	const ERR_API_PROHIBITED_DOMAIN  = 203;

	const F_DOMAIN_LOGO = 1;
	const F_DOMAIN_REG  = 2;

	public static function getErrorMessage($code)
	{
		switch ($code)
		{
			case self::ERR_DB:
				return GetMessage('MAIL_ERR_DB');
			case self::ERR_API_DEFAULT:
				return GetMessage('MAIL_ERR_API_DEFAULT');
			case self::ERR_API_DENIED:
				return GetMessage('MAIL_ERR_API_DENIED');
			case self::ERR_API_NAME_OCCUPIED:
				return GetMessage('MAIL_ERR_API_NAME_OCCUPIED');
			case self::ERR_API_USER_NOTFOUND:
				return GetMessage('MAIL_ERR_API_USER_NOTFOUND');
			case self::ERR_API_EMPTY_DOMAIN:
				return GetMessage('MAIL_ERR_API_EMPTY_DOMAIN');
			case self::ERR_API_EMPTY_NAME:
				return GetMessage('MAIL_ERR_API_EMPTY_NAME');
			case self::ERR_API_EMPTY_PASSWORD:
				return GetMessage('MAIL_ERR_API_EMPTY_PASSWORD');
			case self::ERR_API_SHORT_PASSWORD:
				return GetMessage('MAIL_ERR_API_SHORT_PASSWORD');
			case self::ERR_API_BAD_NAME:
				return GetMessage('MAIL_ERR_API_BAD_NAME');
			case self::ERR_API_BAD_PASSWORD:
				return GetMessage('MAIL_ERR_API_BAD_PASSWORD');
			case self::ERR_API_PASSWORD_LIKELOGIN:
				return GetMessage('MAIL_ERR_API_PASSWORD_LIKELOGIN');
			case self::ERR_API_LONG_NAME:
				return GetMessage('MAIL_ERR_API_LONG_NAME');
			case self::ERR_API_LONG_PASSWORD:
				return GetMessage('MAIL_ERR_API_LONG_PASSWORD');
			case self::ERR_API_OP_DENIED:
				return GetMessage('MAIL_ERR_API_OP_DENIED');
			case self::ERR_API_DOMAIN_OCCUPIED:
				return GetMessage('MAIL_ERR_API_DOMAIN_OCCUPIED');
			case self::ERR_API_BAD_DOMAIN:
				return GetMessage('MAIL_ERR_API_BAD_DOMAIN');
			case self::ERR_API_PROHIBITED_DOMAIN:
				return GetMessage('MAIL_ERR_API_PROHIBITED_DOMAIN');
			default:
				return GetMessage('MAIL_ERR_DEFAULT');
		}
	}

	public static function onUserUpdate($arFields)
	{
		if ($arFields['RESULT'] && isset($arFields['ACTIVE']) && $arFields['ACTIVE'] == 'N')
		{
			$selectResult = CMailbox::getList(array(), array('USER_ID' => intval($arFields['ID']), 'ACTIVE' => 'Y'));
			while ($mailbox = $selectResult->fetch())
				CMailbox::update($mailbox['ID'], array('ACTIVE' => 'N'));
		}
	}

	public static function onUserDelete($id)
	{
		$selectResult = CMailbox::getList(array(), array('USER_ID' => intval($id)));
		while ($mailbox = $selectResult->fetch())
			CMailbox::delete($mailbox['ID']);
	}

}

class CMailError
{
	public static function ResetErrors()
	{
		global $BX_MAIL_ERRORs;
		$BX_MAIL_ERRORs = Array();
	}

	public static function SetError($ID, $TITLE="", $DESC="")
	{
		global $BX_MAIL_ERRORs;
		$BX_MAIL_ERRORs[] = array("ID"=>$ID, "TITLE"=>$TITLE, "DESCRIPTION"=>$DESC);
		return false;
	}

	public static function GetLastError($type=false)
	{
		global $BX_MAIL_ERRORs;
		if($type===false)
			return $BX_MAIL_ERRORs[count($BX_MAIL_ERRORs)-1];
		return $BX_MAIL_ERRORs[count($BX_MAIL_ERRORs)-1][$type];
	}

	public static function GetErrors()
	{
		global $BX_MAIL_ERRORs;
		return $BX_MAIL_ERRORs;
	}

	public static function GetErrorsText($delim="<br>")
	{
		global $BX_MAIL_ERRORs;
		$str = "";
		foreach($BX_MAIL_ERRORs as $err)
		{
			if ($str!="")
				$str .= $delim;
			$str.=$err["TITLE"];
		}
		return $str;
	}

	public static function ErrCount()
	{
		global $BX_MAIL_ERRORs;
		if(!is_array($BX_MAIL_ERRORs))
			return 0;
		return count($BX_MAIL_ERRORs);
	}
}


class _CMailBoxDBRes  extends CDBResult
{
	public static function _CMailBoxDBRes($res)
	{
		parent::CDBResult($res);
	}

	public static function Fetch()
	{
		if($res = parent::Fetch())
		{
			$res["PASSWORD"] = CMailUtil::Decrypt($res["PASSWORD"]);
		}
		return $res;
	}
}
///////////////////////////////////////////////////////////////////////////////////
// class CMailBox
///////////////////////////////////////////////////////////////////////////////////
class CAllMailBox
{
	var $pop3_conn = false;
	var $mess_count = 0;
	var $mess_size = 0;
	var $resp = true;
	var $last_result = true;
	var $response = "";
	var $response_body = "";
	public $mailbox_id = 0;
	public $new_mess_count = 0;
	public $deleted_mess_count = 0;


	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB;
		$strSql =
				"SELECT MB.*, C.CHARSET as LANG_CHARSET, ".
				"	".$DB->DateToCharFunction("MB.TIMESTAMP_X")."	as TIMESTAMP_X ".
				"FROM b_mail_mailbox MB, b_lang L, b_culture C ".
				"WHERE MB.LID=L.LID AND C.ID=L.CULTURE_ID";

		if(!is_array($arFilter))
			$arFilter = Array();
		$arSqlSearch = Array();
		$filter_keys = array_keys($arFilter);
		for($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			if (strlen($val)<=0) continue;
			$key = strtoupper($filter_keys[$i]);

			$strNegative = false;
			if (substr($key, 0, 1) == '!')
			{
				$key = substr($key, 1);
				$strNegative = 'Y';
			}

			$strExact = false;
			if (substr($key, 0, 1) == '=')
			{
				$key = substr($key, 1);
				$strExact = 'Y';
			}

			switch ($key)
			{
				case 'ID':
				case 'PORT':
				case 'DELETE_MESSAGES':
				case 'ACTIVE':
				case 'USE_MD5':
				case 'RELAY':
				case 'AUTH_RELAY':
					$arSqlSearch[] = GetFilterQuery('MB.'.$key, ($strNegative == 'Y' ? '~' : '').$val, 'N');
					break;
				case 'LID':
				case 'LOGIN':
				case 'SERVER':
				case 'NAME':
				case 'DESCRIPTION':
				case 'DOMAINS':
				case 'SERVER_TYPE':
					$arSqlSearch[] = GetFilterQuery('MB.'.$key, ($strNegative == 'Y' ? '~' : '').$val, $strExact == 'Y' ? 'N' : 'Y');
					break;
				case 'SERVICE_ID':
				case 'USER_ID':
					$arSqlSearch[] = 'MB.' . $key . ($strNegative == 'Y' ? ' != ' : ' = ') . intval($val);
					break;
			}
		}

		$is_filtered = false;
		$strSqlSearch = "";
		for($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
			{
				$is_filtered = true;
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
			}
		}

		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc".(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"");

			switch(strtoupper($by))
			{
			case "TIMESTAMP_X":
			case "LID":
			case "ACTIVE":
			case "NAME":
			case "SERVER":
			case "PORT":
			case "LOGIN":
			case "USE_MD5":
			case "DELETE_MESSAGES":
			case "RELAY":
			case "AUTH_RELAY":
			case "SERVER_TYPE":
			case "PERIOD_CHECK":
				$arSqlOrder[] = " MB.".$by." ".$order." ";
				break;
			default:
				$arSqlOrder[] = " MB.ID ".$order." ";
			}
		}

		$strSqlOrder = "";
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);

		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlSearch.$strSqlOrder;

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res = new _CMailBoxDBRes($res);
		$res->is_filtered = $is_filtered;
		return $res;
	}

	public static function GetByID($ID)
	{
		return CMailBox::GetList(Array(), Array("ID"=>$ID));
	}

	public static function CheckMail($mailbox_id = false)
	{
		global $DB;
		$mbx = Array();
		if($mailbox_id===false)
		{
			$strSql =
					"SELECT MB.ID ".
					"FROM b_mail_mailbox MB ".
					"WHERE ACTIVE='Y' ";

			$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $dbr->Fetch())
				$mbx[] = $ar["ID"];
		}
		else
		{
			$mbx[] = $mailbox_id;
		}

		$bNoErrors = true;
		foreach($mbx as $mailboxId)
		{
			$mb = new CMailbox();
			if(!$mb->Connect($mailboxId))
			{
				$bNoErrors = false;
				CMailError::SetError("ERR_CHECK_MAIL", GetMessage("MAIL_CL_ERR_CHECK_MAIL")." (mailbox id: ".$mailboxId.").", "");
			}
		}

		return $bNoErrors;
	}

	public static function CheckMailAgent($ID)
	{
		global $DB, $USER;
		$bUserCreated = false;
		if (!isset($USER) || !is_object($USER))
		{
			$USER = new CUser();
			$bUserCreated = true;
		}
		$ID = IntVal($ID);
		$strSql =
				"SELECT MB.ID, MB.PERIOD_CHECK ".
				"FROM b_mail_mailbox MB ".
				"WHERE ACTIVE='Y' ".
				"	AND ID=".$ID.
				"	AND USER_ID = 0";

		$strReturn = '';
		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($ar = $dbr->Fetch())
		{
			$mb = new CMailbox();
			$mb->Connect($ID);
			if(intval($ar["PERIOD_CHECK"])>0)
				$strReturn = "CMailbox::CheckMailAgent(".$ID.");";
		}
		if ($bUserCreated)
		{
			unset($USER);
		}
		return $strReturn;
	}

	public static function CheckFields($arFields, $ID=false)
	{
		global $APPLICATION;
		$arMsg = array();

		if (is_set($arFields, 'NAME') && strlen($arFields['NAME']) < 1)
		{
			CMailError::SetError('B_MAIL_ERR_NAME', GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_NAME').'"');
			$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_NAME').'"');
		}

		if (in_array(strtolower($arFields['SERVER_TYPE']), array('pop3', 'imap', 'controller', 'domain', 'crdomain')) && is_set($arFields, 'LOGIN') && strlen($arFields['LOGIN']) < 1)
		{
			CMailError::SetError('B_MAIL_ERR_LOGIN', GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_LOGIN').'"');
			$arMsg[] = array('id' => 'LOGIN', 'text' => GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_LOGIN').'"');
		}

		if (in_array(strtolower($arFields['SERVER_TYPE']), array('pop3', 'imap')) && is_set($arFields, 'PASSWORD') && strlen($arFields['PASSWORD']) < 1)
		{
			CMailError::SetError('B_MAIL_ERR_PASSWORD', GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_PASSWORD').'"');
			$arMsg[] = array('id' => 'PASSWORD', 'text' => GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_PASSWORD').'"');
		}

		if (strtolower($arFields['SERVER_TYPE']) == 'imap' && is_set($arFields, 'LINK') && strlen($arFields['LINK']) < 1)
		{
			CMailError::SetError('B_MAIL_ERR_LINK', GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_LINK').'"');
			$arMsg[] = array('id' => 'LINK', 'text' => GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_LINK').'"');
		}

		if (in_array(strtolower($arFields['SERVER_TYPE']), array('imap', 'controller', 'domain', 'crdomain')) && is_set($arFields, 'USER_ID') && strlen($arFields['USER_ID']) < 1)
		{
			CMailError::SetError('B_MAIL_ERR_USER_ID', GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_USER_ID').'"');
			$arMsg[] = array('id' => 'USER_ID', 'text' => GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_USER_ID').'"');
		}

		if (in_array(strtolower($arFields['SERVER_TYPE']), array('pop3', 'smtp', 'imap')) && is_set($arFields, 'SERVER') && strlen($arFields['SERVER']) < 1)
		{
			CMailError::SetError('B_MAIL_ERR_SERVER_NAME', GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_SERVER').'"');
			$arMsg[] = array('id' => 'SERVER', 'text' => GetMessage('MAIL_CL_ERR_NAME').' "'.GetMessage('MAIL_CL_SERVER').'"');
		}
		elseif (strtolower($arFields['SERVER_TYPE']) == 'smtp')
		{
			$dbres = CMailBox::GetList(array(), array('ACTIVE' => 'Y', 'SERVER_TYPE' => 'smtp', 'SERVER' => $arFields['SERVER'], 'PORT' => $arFields['PORT']));
			while($arres = $dbres->Fetch())
			{
				if ($ID === false || $arres['ID'] != $ID)
				{
					CMailError::SetError('B_MAIL_ERR_SERVER_NAME',  GetMessage('B_MAIL_ERR_SN').' "'.GetMessage('MAIL_CL_SERVER').'"');
					$arMsg[] = array('id' => 'SERVER', 'text' => GetMessage('B_MAIL_ERR_SN').' "'.GetMessage('MAIL_CL_SERVER').'"');
					break;
				}
			}
		}

		if (is_set($arFields, 'LID'))
		{
			$r = CLang::GetByID($arFields['LID']);
			if (!$r->Fetch())
			{
				CMailError::SetError('B_MAIL_ERR_BAD_LANG', GetMessage('MAIL_CL_ERR_BAD_LANG'));
				$arMsg[] = array('id' => 'LID', 'text' => GetMessage('MAIL_CL_ERR_BAD_LANG'));
			}
		}
		elseif ($ID === false)
		{
			CMailError::SetError('B_MAIL_ERR_BAD_LANG_NA', GetMessage('MAIL_CL_ERR_BAD_LANG_NX'));
			$arMsg[] = array('id' => 'LID', 'text' => GetMessage('MAIL_CL_ERR_BAD_LANG_NX'));
		}

		if ($arFields['USER_ID'])
		{
			if (is_set($arFields, 'SERVICE_ID'))
			{
				if (!empty($arFields['LID']) || $ID)
				{
					$LID_tmp = $arFields['LID'];
					if (empty($arFields['LID']))
					{
						$arMb_tmp = CMailBox::GetList(array(), array('ID' => $ID))->fetch();
						$LID_tmp = $arMb_tmp['LID'];
					}
					$result = Bitrix\Mail\MailServicesTable::getList(array(
						'filter' => array('=SITE_ID' => $LID_tmp, '=ID' => $arFields['SERVICE_ID'])
					));
					if (!$result->fetch())
					{
						CMailError::SetError('B_MAIL_ERR_BAD_SERVICE_ID', GetMessage('MAIL_CL_ERR_BAD_SERVICE_ID'));
						$arMsg[] = array('id' => 'SERVICE_ID', 'text' => GetMessage('MAIL_CL_ERR_BAD_SERVICE_ID'));
					}
				}
			}
			else if ($ID === false)
			{
				CMailError::SetError('B_MAIL_ERR_BAD_SERVICE_ID_NA', GetMessage('MAIL_CL_ERR_BAD_SERVICE_ID_NX'));
				$arMsg[] = array('id' => 'SERVICE_ID', 'text' => GetMessage('MAIL_CL_ERR_BAD_SERVICE_ID_NX'));
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function Add($arFields)
	{
		global $DB;
		CMailError::ResetErrors();

		if($arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if($arFields["DELETE_MESSAGES"]!="Y")
			$arFields["DELETE_MESSAGES"]="N";

		if($arFields["USE_MD5"]!="Y")
			$arFields["USE_MD5"]="N";

		if ($arFields['USE_TLS'] != 'Y' && $arFields['USE_TLS'] != 'S')
			$arFields["USE_TLS"]="N";

		if (!in_array($arFields["SERVER_TYPE"], array("pop3", "smtp", "imap", "controller", "domain", "crdomain")))
			$arFields["SERVER_TYPE"] = "pop3";

		if(!CMailBox::CheckFields($arFields))
			return false;

		if(is_set($arFields, "PASSWORD"))
			$arFields["PASSWORD"]=CMailUtil::Crypt($arFields["PASSWORD"]);

		if ($arFields['ACTIVE'] == 'Y' && $arFields['USER_ID'] != 0)
		{
			CUserCounter::Clear($arFields['USER_ID'], 'mail_unseen', $arFields['LID']);

			CUserOptions::SetOption('global', 'last_mail_check_'.$arFields['LID'], 0, false, $arFields['USER_ID']);
			CUserOptions::DeleteOption('global', 'last_mail_check_success_'.$arFields['LID'], false, $arFields['USER_ID']);
		}

		$ID = $DB->Add("b_mail_mailbox", $arFields);

		if(intval($arFields["PERIOD_CHECK"])>0 && $arFields["SERVER_TYPE"]=="pop3")
			CAgent::AddAgent("CMailbox::CheckMailAgent(".$ID.");", "mail", "N", intval($arFields["PERIOD_CHECK"])*60);

		CMailbox::SMTPReload();
		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);

		CMailError::ResetErrors();

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "DELETE_MESSAGES") && $arFields["DELETE_MESSAGES"]!="Y")
			$arFields["DELETE_MESSAGES"]="N";

		if(is_set($arFields, "USE_MD5") && $arFields["USE_MD5"]!="Y")
			$arFields["USE_MD5"]="N";

		if(is_set($arFields, 'USE_TLS') && $arFields['USE_TLS'] != 'Y' && $arFields['USE_TLS'] != 'S')
			$arFields["USE_TLS"]="N";

		if (is_set($arFields, "SERVER_TYPE") && !in_array($arFields["SERVER_TYPE"], array("pop3", "smtp", "imap", "controller", "domain", "crdomain")))
			$arFields["SERVER_TYPE"] = "pop3";

		if(!CMailBox::CheckFields($arFields, $ID))
			return false;

		if(is_set($arFields, "PASSWORD"))
			$arFields["PASSWORD"]=CMailUtil::Crypt($arFields["PASSWORD"]);

		$db_mbox = CMailbox::GetList(array('ID' => $ID));
		if (($mbox = $db_mbox->fetch()) !== false)
		{
			$userChanged = isset($arFields['USER_ID']) && $mbox['USER_ID'] != $arFields['USER_ID'];
			$siteChanged = isset($arFields['LID']) && $mbox['LID'] != $arFields['LID'];

			if ($userChanged || $siteChanged)
			{
				if ($mbox['ACTIVE'] == 'Y' && $mbox['USER_ID'] != 0)
					CUserOptions::DeleteOption('global', 'last_mail_check_'.$mbox['LID'], false, $mbox['USER_ID']);

				$newActive = isset($arFields['ACTIVE']) ? $arFields['ACTIVE'] : $mbox['ACTIVE'];
				if ($newActive == 'Y')
				{
					$newUserId = isset($arFields['USER_ID']) ? $arFields['USER_ID'] : $mbox['USER_ID'];
					$newSiteId = isset($arFields['LID']) ? $arFields['LID'] : $mbox['LID'];

					CUserOptions::SetOption('global', 'last_mail_check_'.$newSiteId, 0, false, $newUserId);
					CUserOptions::DeleteOption('global', 'last_mail_check_success_'.$newSiteId, false, $newUserId);
				}

				CUserOptions::DeleteOption('global', 'last_mail_check_success_'.$mbox['LID'], false, $mbox['USER_ID']);
			}

			if ($mbox['USER_ID'] != 0 || isset($arFields['USER_ID']) && $arFields['USER_ID'] != 0)
			{
				CUserCounter::Clear($mbox['USER_ID'], 'mail_unseen', $mbox['LID']);
				if ($siteChanged)
					CUserCounter::Clear($mbox['USER_ID'], 'mail_unseen', $arFields['LID']);

				if ($userChanged)
				{
					CUserCounter::Clear($arFields['USER_ID'], 'mail_unseen', $mbox['LID']);
					if (isset($arFields['LID']) && $mbox['LID'] != $arFields['LID'])
						CUserCounter::Clear($arFields['USER_ID'], 'mail_unseen', $arFields['LID']);
				}
			}
		}

		CAgent::RemoveAgent("CMailbox::CheckMailAgent(".$ID.");", "mail");
		if(intval($arFields["PERIOD_CHECK"])>0 && $arFields["SERVER_TYPE"]=="pop3")
			CAgent::AddAgent("CMailbox::CheckMailAgent(".$ID.");", "mail", "N", intval($arFields["PERIOD_CHECK"])*60);

		$strUpdate = $DB->PrepareUpdate("b_mail_mailbox", $arFields);

		$strSql =
			"UPDATE b_mail_mailbox SET ".
				$strUpdate." ".
			"WHERE ID=".$ID;

		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		CMailbox::SMTPReload();
		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$db_msg = CMailMessage::GetList(Array(), Array("MAILBOX_ID"=>$ID));
		while($msg = $db_msg->Fetch())
		{
			if(!CMailMessage::Delete($msg["ID"]))
				return false;
		}

		$db_flt = CMailFilter::GetList(Array(), Array("MAILBOX_ID"=>$ID));
		while($flt = $db_flt->Fetch())
		{
			if(!CMailFilter::Delete($flt["ID"]))
				return false;
		}

		$db_log = CMailLog::GetList(Array(), Array("MAILBOX_ID"=>$ID));
		while($log = $db_log->Fetch())
		{
			if(!CMailLog::Delete($log["ID"]))
				return false;
		}

		$db_mbox = CMailbox::GetList(array('ID' => $ID, 'ACTIVE' => 'Y', '!USER_ID' => 0));
		if ($mbox = $db_mbox->fetch())
		{
			CUserCounter::Clear($mbox['USER_ID'], 'mail_unseen', $mbox['LID']);

			CUserOptions::DeleteOption('global', 'last_mail_check_'.$mbox['LID'], false, $mbox['USER_ID']);
			CUserOptions::DeleteOption('global', 'last_mail_check_success_'.$mbox['LID'], false, $mbox['USER_ID']);
		}

		CAgent::RemoveAgent("CMailbox::CheckMailAgent(".$ID.");", "mail");

		$strSql = "DELETE FROM b_mail_message_uid WHERE MAILBOX_ID=".$ID;
		if(!$DB->Query($strSql, true))
			return false;

		CMailbox::SMTPReload();
		$strSql = "DELETE FROM b_mail_mailbox WHERE ID=".$ID;
		return $DB->Query($strSql, true);
	}

	public static function SMTPReload()
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->Read(3600000, $cache_id = "smtpd_reload");
		$CACHE_MANAGER->Set($cache_id, true);
	}

	public function SendCommand($command)
	{
		//SSRF "filter"
		$command = preg_replace("/[\\n\\r]/", "", $command);

		fputs($this->pop3_conn, $command."\r\n");

		if($this->mailbox_id>0)
		{
			CMailLog::AddMessage(
				Array(
					"MAILBOX_ID"=>$this->mailbox_id,
					"STATUS_GOOD"=>"Y",
					"MESSAGE"=>"> ".nl2br(preg_replace("'PASS .*'", "PASS ******", $command))
					)
				);
		}
		$this->resp = true;
	}

	public function GetResponse($bMultiline = false, $bSkipFirst = true)
	{
		if(!$this->resp) return false;
		$this->resp = false;

		socket_set_timeout($this->pop3_conn, 20);
		$res = rtrim(fgets($this->pop3_conn, 1024), "\r\n");
//		socket_set_blocking($this->pop3_conn, false);
//		socket_set_blocking($this->pop3_conn, true);

		$this->last_result = ($res[0]=="+");
		$this->response = $res;

		if($this->mailbox_id>0)
		{
			CMailLog::AddMessage(
				Array(
					"MAILBOX_ID"=>$this->mailbox_id,
					"STATUS_GOOD"=>($this->last_result?"Y":"N"),
					"MESSAGE"=>"< ".$res
					)
				);
		}

		if($bMultiline && $res[0]=="+")
		{
			if($bSkipFirst)
				$res = "";
			else
				$res .= "\r\n";

			$s = fgets($this->pop3_conn, 1024);
			while(strlen($s)>0 && $s!=".\r\n")
			{
				if(substr($s, 0, 2)=="..")
					$s = substr($s, 1);
				$res .= $s;
				$s = fgets($this->pop3_conn, 1024);
			}
		}
		$this->response_body = $res;
		return $this->last_result;
	}

	public function GetResponseBody()
	{
		return $this->response_body;
	}

	public function GetResponseString()
	{
		return $this->response_body;
	}

	static function GetPassword($p)
	{
	}

	public function Check($server, $port, $use_tls, $login, $passw)
	{
		if (($use_tls == 'Y' || $use_tls == 'S') && strpos($server, 'tls://') === false)
			$server = 'tls://' . $server;

		$skip_cert = $use_tls != 'Y' || PHP_VERSION_ID < 50600;

		$pop3_conn = &$this->pop3_conn;
		$pop3_conn = stream_socket_client(
			sprintf('%s:%s', $server, $port),
			$errno, $errstr,
			COption::getOptionInt('mail', 'connect_timeout', B_MAIL_TIMEOUT),
			STREAM_CLIENT_CONNECT,
			stream_context_create(array('ssl' => array('verify_peer' => !$skip_cert, 'verify_peer_name' => !$skip_cert)))
		);
		if(!$pop3_conn)
			return array(false, GetMessage("MAIL_CL_TIMEOUT")." $errstr ($errno)");

		$this->GetResponse();
		$greeting = $this->GetResponseString();

		$this->SendCommand("USER ".$login);
		if(!$this->GetResponse())
			return array(false, GetMessage("MAIL_CL_ERR_USER").' ('.$this->GetResponseString().')');
		$this->SendCommand("PASS ".$passw);
		if(!$this->GetResponse())
			return array(false, GetMessage("MAIL_CL_ERR_PASSWORD").' ('.$this->GetResponseString().')');

		$this->SendCommand("STAT");

		if(!$this->GetResponse())
			return array(false, GetMessage("MAIL_CL_ERR_STAT").' ('.$this->GetResponseString().')');

		$stat = trim($this->GetResponseBody());
		$arStat = explode(" ", $stat);
		return array(true, $arStat[1]);
	}

	public function Connect($mailbox_id)
	{
		global $DB;
		$mailbox_id = IntVal($mailbox_id);
		$strSql =
				"SELECT MB.*, C.CHARSET as LANG_CHARSET ".
				"FROM b_mail_mailbox MB, b_lang L, b_culture C ".
				"WHERE MB.LID=L.LID AND C.ID=L.CULTURE_ID ".
				"	AND MB.ID=".$mailbox_id;
		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$dbr = new _CMailBoxDBRes($dbr);
		if(!$arMAILBOX_PARAMS = $dbr->Fetch())
			return CMailError::SetError("ERR_MAILBOX_NOT_FOUND", GetMessage("MAIL_CL_ERR_MAILBOX_NOT_FOUND"), GetMessage("MAIL_CL_ERR_MAILBOX_NOT_FOUND"));

		if ($arMAILBOX_PARAMS['SYNC_LOCK'] > time()-600)
			return;

		$DB->query('UPDATE b_mail_mailbox SET SYNC_LOCK = '.time().' WHERE ID = '.$mailbox_id);

		$result = $this->_connect($mailbox_id, $arMAILBOX_PARAMS);

		$DB->query('UPDATE b_mail_mailbox SET SYNC_LOCK = 0 WHERE ID = '.$mailbox_id);

		return $result;
	}

	private function _connect($mailbox_id, $arMAILBOX_PARAMS)
	{
		global $DB;

		@set_time_limit(0);

		// https://support.google.com/mail/answer/47948
		if ($arMAILBOX_PARAMS["SERVER"] == 'pop.gmail.com')
			$arMAILBOX_PARAMS["LOGIN"] = 'recent:' . $arMAILBOX_PARAMS["LOGIN"];

		$server = $arMAILBOX_PARAMS["SERVER"];
		if (($arMAILBOX_PARAMS['USE_TLS'] == 'Y' || $arMAILBOX_PARAMS['USE_TLS'] == 'S') && strpos($server, 'tls://') === false)
			$server = 'tls://' . $server;

		$skip_cert = $arMAILBOX_PARAMS['USE_TLS'] != 'Y' || PHP_VERSION_ID < 50600;

		$pop3_conn = &$this->pop3_conn;
		$pop3_conn = stream_socket_client(
			sprintf('%s:%s', $server, $arMAILBOX_PARAMS["PORT"]),
			$errno, $errstr,
			COption::getOptionInt('mail', 'connect_timeout', B_MAIL_TIMEOUT),
			STREAM_CLIENT_CONNECT,
			stream_context_create(array('ssl' => array('verify_peer' => !$skip_cert, 'verify_peer_name' => !$skip_cert)))
		);

		CMailLog::AddMessage(
			Array(
				"MAILBOX_ID"=>$mailbox_id,
				"STATUS_GOOD"=>"Y",
				"MESSAGE"=>GetMessage("MAIL_CL_CONNECT_TO")." ".$arMAILBOX_PARAMS["SERVER"]
				)
			);

		if(!$pop3_conn || !is_resource($pop3_conn))
		{
			CMailLog::AddMessage(
				Array(
					"MAILBOX_ID"=>$mailbox_id,
					"STATUS_GOOD"=>"N",
					"MESSAGE"=>GetMessage("MAIL_CL_TIMEOUT")
					)
				);
			return CMailError::SetError("ERR_CONNECT_TIMEOUT", GetMessage("MAIL_CL_TIMEOUT"), "$errstr ($errno)");
		}

		$this->mailbox_id = $mailbox_id;
		if($arMAILBOX_PARAMS["CHARSET"]!='')
			$this->charset = $arMAILBOX_PARAMS["CHARSET"];
		else
			$this->charset = $arMAILBOX_PARAMS["LANG_CHARSET"];
		$this->use_md5 = $arMAILBOX_PARAMS["USE_MD5"];

		$session_id = md5(uniqid(""));
		$this->GetResponse();
		$greeting = $this->GetResponseString();

		if($this->use_md5=="Y" && preg_match("'(<.+>)'", $greeting, $reg))
		{
			$this->SendCommand("APOP ".$arMAILBOX_PARAMS["LOGIN"]." ".md5($reg[1].$arMAILBOX_PARAMS["PASSWORD"]));
			if(!$this->GetResponse())
				return CMailError::SetError("ERR_AFTER_USER", GetMessage("MAIL_CL_ERR_APOP"), $this->GetResponseString());
		}
		else
		{
			$this->SendCommand("USER ".$arMAILBOX_PARAMS["LOGIN"]);
			if(!$this->GetResponse())
				return CMailError::SetError("ERR_AFTER_USER", GetMessage("MAIL_CL_ERR_USER"), $this->GetResponseString());
			$this->SendCommand("PASS ".$arMAILBOX_PARAMS["PASSWORD"]);
			if(!$this->GetResponse())
				return CMailError::SetError("ERR_AFTER_PASS", GetMessage("MAIL_CL_ERR_PASSWORD"), $this->GetResponseString());
		}

		$this->SendCommand("STAT");
		if(!$this->GetResponse())
			return CMailError::SetError("ERR_AFTER_STAT", GetMessage("MAIL_CL_ERR_STAT"), $this->GetResponseString());

		$stat = trim($this->GetResponseBody());
		$arStat = explode(" ", $stat);
		$this->mess_count = $arStat[1];
		if($this->mess_count>0)
		{
			$this->mess_size = $arStat[2];
			$arLIST = array();

			if($arMAILBOX_PARAMS["MAX_MSG_SIZE"]>0)
			{
				$this->SendCommand("LIST");
				if(!$this->GetResponse(true))
					return CMailError::SetError("ERR_AFTER_LIST", "LIST command error", $this->GetResponseString());
				$list = $this->GetResponseBody();
				preg_match_all("'([0-9]+)[ ]+?(.+)'", $list, $arLIST_temp, PREG_SET_ORDER);

				for($i = 0, $n = count($arLIST_temp); $i < $n; $i++)
					$arLIST[IntVal($arLIST_temp[$i][1])] = IntVal($arLIST_temp[$i][2]);
			}

			$this->SendCommand("UIDL");
			if(!$this->GetResponse(true))
				return CMailError::SetError("ERR_AFTER_UIDL", GetMessage("MAIL_CL_ERR_UIDL"), $this->GetResponseString());

			$uidl = $this->GetResponseBody();
			preg_match_all("'([0-9]+)[ ]+?(.+)'", $uidl, $arUIDL_temp, PREG_SET_ORDER);

			$arUIDL = array();
			$cnt = count($arUIDL_temp);
			for ($i = 0; $i < $cnt; $i++)
				$arUIDL[md5($arUIDL_temp[$i][2])] = $arUIDL_temp[$i][1];

			$skipOldUIDL = $cnt < $this->mess_count;
			if ($skipOldUIDL)
			{
				AddMessage2Log(sprintf(
					"%s\n%s of %s",
					$this->response, $cnt, $this->mess_count
				), 'mail');
			}

			$arOldUIDL = array();
			if (count($arUIDL) > 0)
			{
				$strSql = 'SELECT ID FROM b_mail_message_uid WHERE MAILBOX_ID = ' . $mailbox_id;
				$db_res = $DB->query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
				while ($ar_res = $db_res->fetch())
				{
					if (isset($arUIDL[$ar_res['ID']]))
						unset($arUIDL[$ar_res['ID']]);
					else if (!$skipOldUIDL)
						$arOldUIDL[] = $ar_res['ID'];
				}
			}

			while (count($arOldUIDL) > 0)
			{
				$ids = "'" . join("','", array_splice($arOldUIDL, 0, 1000)) . "'";
				$strSql = 'DELETE FROM b_mail_message_uid WHERE MAILBOX_ID = ' . $mailbox_id . ' AND ID IN (' . $ids . ')';
				$DB->query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			}

			$this->new_mess_count = 0;
			$this->deleted_mess_count = 0;
			$session_id = md5(uniqid(""));

			foreach($arUIDL as $msguid=>$msgnum)
			{
				if($arMAILBOX_PARAMS["MAX_MSG_SIZE"]<=0 || $arLIST[$msgnum]<=$arMAILBOX_PARAMS["MAX_MSG_SIZE"])
					$this->GetMessage($mailbox_id, $msgnum, $msguid, $session_id);

				if($arMAILBOX_PARAMS["DELETE_MESSAGES"]=="Y")
				{
					$this->DeleteMessage($msgnum);
					$this->deleted_mess_count++;
				}

				$this->new_mess_count++;
				if($arMAILBOX_PARAMS["MAX_MSG_COUNT"]>0 && $arMAILBOX_PARAMS["MAX_MSG_COUNT"]<=$this->new_mess_count)
					break;
			}
		}

		$this->SendCommand("QUIT");
		if(!$this->GetResponse())
			return CMailError::SetError("ERR_AFTER_QUIT", GetMessage("MAIL_CL_ERR_DISCONNECT"), $this->GetResponseString());

		fclose($pop3_conn);
		return true;
	}

	public function GetMessage($mailbox_id, $msgnum, $msguid, $session_id)
	{
		global $DB;

		$this->SendCommand("RETR ".$msgnum);
		if(!$this->GetResponse(true))
			return CMailError::SetError("ERR_AFTER_RETR", GetMessage("MAIL_CL_ERR_RETR"), $this->GetResponseString());

		$message = $this->GetResponseBody();

		$strSql = "INSERT INTO b_mail_message_uid(ID, MAILBOX_ID, SESSION_ID, DATE_INSERT, MESSAGE_ID) VALUES('".$DB->ForSql($msguid)."', ".IntVal($mailbox_id).", '".$DB->ForSql($session_id)."', ".$DB->GetNowFunction().", 0)";
		$DB->Query($strSql);

		$message_id = CMailMessage::AddMessage($mailbox_id, $message, $this->charset);
		if($message_id>0)
		{
			$strSql = "UPDATE b_mail_message_uid SET MESSAGE_ID = " . intval($message_id) . " WHERE ID = '" . $DB->forSql($msguid) . "' AND MAILBOX_ID = " . intval($mailbox_id);
			$DB->Query($strSql);
		}
		return $message_id;
	} // function GetMessage(...

	/*********************************************************************
	*********************************************************************/
	public function DeleteMessage($msgnum)
	{
		$this->SendCommand("DELE ".$msgnum);
		if(!$this->GetResponse())
			return CMailError::SetError("ERR_AFTER_DELE", GetMessage("MAIL_CL_ERR_DELE"), $this->GetResponseString());
	}
}

///////////////////////////////////////////////////////////////////////////////////
// class CMailHeader
///////////////////////////////////////////////////////////////////////////////////
class CMailHeader
{
	var $arHeader = Array();
	var $arHeaderLines = Array();
	var $strHeader = "";
	var $bMultipart = false;
	var $content_type, $boundary, $charset, $filename, $MultipartType="mixed";
	public $content_id = '';

	public static function ConvertHeader($encoding, $type, $str, $charset)
	{
		if(strtoupper($type)=="B")
			$str = base64_decode($str);
		else
			$str = quoted_printable_decode(str_replace("_", " ", $str));

		$str = CMailUtil::ConvertCharset($str, $encoding, $charset);

		return $str;
	}

	public static function DecodeHeader($str, $charset_to, $charset_document)
	{
		while(preg_match('/(=\?[^?]+\?(Q|B)\?[^?]*\?=)(\s)+=\?/i', $str))
			$str = preg_replace('/(=\?[^?]+\?(Q|B)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $str);
		if(!preg_match("'=\?(.*)\?(B|Q)\?(.*)\?='i", $str))
		{
			if(strlen($charset_document)>0 && $charset_document!=$charset_to)
				$str = CMailUtil::ConvertCharset($str, $charset_document, $charset_to);
		}
		else
		{
			$str = preg_replace_callback(
				"'=\?(.*?)\?(B|Q)\?(.*?)\?='i",
				create_function('$m', "return CMailHeader::ConvertHeader(\$m[1], \$m[2], \$m[3], '".AddSlashes($charset_to)."');"),
				$str
			);
		}

		return $str;
	}

	public function Parse($message_header, $charset)
	{
		if(preg_match("'content-type:.*?charset=([^\r\n;]+)'is", $message_header, $res))
			$this->charset = strtolower(trim($res[1], ' "'));
		elseif($this->charset=='' && defined("BX_MAIL_DEFAULT_CHARSET"))
			$this->charset = BX_MAIL_DEFAULT_CHARSET;

		$ar_message_header_tmp = explode("\r\n", $message_header);

		$n = -1;
		$bConvertSubject = false;
		for($i = 0, $num = count($ar_message_header_tmp); $i < $num; $i++)
		{
			$line = $ar_message_header_tmp[$i];
			if(($line[0]==" " || $line[0]=="\t") && $n>=0)
			{
				$line = ltrim($line, " \t");
				$bAdd = true;
			}
			else
				$bAdd = false;

			$line = CMailHeader::DecodeHeader($line, $charset, $this->charset);

			if($bAdd)
				$this->arHeaderLines[$n] = $this->arHeaderLines[$n].$line;
			else
			{
				$n++;
				$this->arHeaderLines[] = $line;
			}
		}

		$this->arHeader = Array();
		for($i = 0, $num = count($this->arHeaderLines); $i < $num; $i++)
		{
			$p = strpos($this->arHeaderLines[$i], ":");
			if($p>0)
			{
				$header_name = strtoupper(trim(substr($this->arHeaderLines[$i], 0, $p)));
				$header_value = trim(substr($this->arHeaderLines[$i], $p+1));
				$this->arHeader[$header_name] = $header_value;
			}
		}

		$full_content_type = $this->arHeader["CONTENT-TYPE"];
		if(strlen($full_content_type)<=0)
			$full_content_type = "text/plain";

		if(!($p = strpos($full_content_type, ";")))
			$p = strlen($full_content_type);

		$this->content_type = trim(substr($full_content_type, 0, $p));
		if(strpos(strtolower($this->content_type), "multipart/") === 0)
		{
			$this->bMultipart = true;
			if (!preg_match("'boundary\s*=(.+);'i", $full_content_type, $res))
				preg_match("'boundary\s*=(.+)'i", $full_content_type, $res);

			$this->boundary = trim($res[1], '"');
			if($p = strpos($this->content_type, "/"))
				$this->MultipartType = substr($this->content_type, $p+1);
		}

		if($p < strlen($full_content_type))
		{
			$add = substr($full_content_type, $p+1);
			if(preg_match("'name=([^;]+)'i", $full_content_type, $res))
				$this->filename = trim($res[1], '"');
		}

		$cd = $this->arHeader["CONTENT-DISPOSITION"];
		if (strlen($cd) > 0)
		{
			if (preg_match("'filename=([^;]+)'i", $cd, $res))
			{
				$this->filename = trim($res[1], '"');
			}
			else if (preg_match("'filename\*=([^;]+)'i", $cd, $res))
			{
				list($fncharset, $fnstr) = preg_split("/'[^']*'/", trim($res[1], '"'));
				$this->filename = CMailUtil::ConvertCharset(rawurldecode($fnstr), $fncharset, $charset);
			}
		}

		if($this->arHeader["CONTENT-ID"]!='')
			$this->content_id = trim($this->arHeader["CONTENT-ID"], '"<>');

		$this->strHeader = implode("\r\n", $this->arHeaderLines);

		return true;
	}

	public function IsMultipart()
	{
		return $this->bMultipart;
	}

	public function MultipartType()
	{
		return strtolower($this->MultipartType);
	}

	public function GetBoundary()
	{
		return $this->boundary;
	}

	public function GetHeader($type)
	{
		return $this->arHeader[strtoupper($type)];
	}
}


///////////////////////////////////////////////////////////////////////////////////
// class CMailMessage
///////////////////////////////////////////////////////////////////////////////////
class CAllMailMessage
{
	public static function GetList($arOrder = Array(), $arFilter = Array(), $bCnt = false)
	{
		global $DB;
		if(strtoupper($DB->type)=="MYSQL")
			$sum = "IF(NEW_MESSAGE='Y', 1, 0)";
		else
			$sum = "case when NEW_MESSAGE='Y' then 1 else 0 end";

		$strSql =
				"SELECT ".
				($bCnt?
					"COUNT('x') as CNT, SUM(".$sum.") as CNT_NEW, COUNT('x')-SUM(".$sum.") as CNT_OLD "
				:
					"MS.*, MB.NAME as MAILBOX_NAME, MB.LID, ".
					"	".$DB->DateToCharFunction("MS.DATE_INSERT")."	as DATE_INSERT, ".
					"	".$DB->DateToCharFunction("MS.FIELD_DATE")."	as FIELD_DATE "
				).
				"FROM b_mail_message MS ".
				($bCnt? "":" INNER JOIN b_mail_mailbox MB ON MS.MAILBOX_ID=MB.ID ");

		$arSqlSearch = Array();
		$filter_keys = array_keys($arFilter);
		for($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$key = $filter_keys[$i];
			$val = $arFilter[$key];
			$res = CMailUtil::MkOperationFilter($key);
			$key = strtoupper($res["FIELD"]);
			$cOperationType = $res["OPERATION"];

			if($cOperationType == "?")
			{
				if (strlen($val)<=0) continue;
				switch($key)
				{
				case "ID":
				case "MAILBOX_ID":
				case "MSGUID":
					$arSqlSearch[] = GetFilterQuery("MS.".$key, $val, "N");
					break;
				case "FIELD_FROM":
				case "FIELD_TO":
				case "FIELD_CC":
				case "FIELD_BCC":
					$arSqlSearch[] = GetFilterQuery("MS.".$key, $val, "Y", Array("@", "_", ".", "-"));
					break;
				case "NEW_MESSAGE":
				case "SUBJECT":
				case "HEADER":
				case "MSG_ID":
				case "IN_REPLY_TO":
				case "BODY":
					$arSqlSearch[] = GetFilterQuery("MS.".$key, $val);
					break;
				case "SENDER":
					$arSqlSearch[] = GetFilterQuery("MS.FIELD_FROM", $val, "Y", array("@","_",".","-"));
					break;
				case "RECIPIENT":
					$arSqlSearch[] = GetFilterQuery("MS.FIELD_TO, MS.FIELD_CC, MS.FIELD_BCC", $val, "Y", array("@","_",".","-"));
					break;
				case "SPAM_RATING":
					CMailFilter::RecalcSpamRating();
					$arSqlSearch[] = GetFilterQuery("MS.SPAM_RATING", $val, "N");
					break;
				case "SPAM":
					$arSqlSearch[] = GetFilterQuery("MS.SPAM", $val, "Y", array("?"));
					break;
				case "ALL":
					$arSqlSearch[] = GetFilterQuery("MS.HEADER, MS.BODY", $val);
					break;
				}
			}
			else
			{
				switch($key)
				{
				case "SPAM":
				case "NEW_MESSAGE":
					$arSqlSearch[] = CMailUtil::FilterCreate("MS.".$key, $val, "string_equal", $cOperationType);
					break;
				case "ID":
				case "MAILBOX_ID":
					$arSqlSearch[] = CMailUtil::FilterCreate("MS.".$key, $val, "number", $cOperationType);
					break;
				case "SUBJECT":
				case "HEADER":
				case "BODY":
				case "MSGUID":
				case "FIELD_FROM":
				case "FIELD_TO":
				case "FIELD_CC":
				case "MSG_ID":
				case "IN_REPLY_TO":
				case "FIELD_BCC":
					$arSqlSearch[] = CMailUtil::FilterCreate("MS.".$key, $val, "string", $cOperationType);
					break;
				case "SPAM_RATING":
					$arSqlSearch[] = CMailUtil::FilterCreate("MS.".$key, $val, "number", $cOperationType);
					CMailFilter::RecalcSpamRating();
					break;
				/*
				case "TIMESTAMP_X":
					$arSqlSearch[] = CIBlock::FilterCreate("BE.TIMESTAMP_X", $val, "date", $cOperationType);
					break;
				*/
				}
			}
		}

		$is_filtered = false;
		$strSqlSearch = "";
		for($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
			{
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
				$is_filtered = true;
			}
		}
		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);

			if ($order!="asc")
				$order = "desc".(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"");

			if ($by == "field_date")		$arSqlOrder[] = " MS.FIELD_DATE ".$order." ";
			elseif ($by == "field_from")	$arSqlOrder[] = " MS.FIELD_FROM ".$order." ";
			elseif ($by == "field_reply_to")$arSqlOrder[] = " MS.FIELD_REPLY_TO ".$order." ";
			elseif ($by == "field_to")		$arSqlOrder[] = " MS.FIELD_TO ".$order." ";
			elseif ($by == "field_cc")		$arSqlOrder[] = " MS.FIELD_CC ".$order." ";
			elseif ($by == "field_bcc")		$arSqlOrder[] = " MS.FIELD_BCC ".$order." ";
			elseif ($by == "subject")		$arSqlOrder[] = " MS.SUBJECT ".$order." ";
			elseif ($by == "attachments")	$arSqlOrder[] = " MS.ATTACHMENTS ".$order." ";
			elseif ($by == "date_insert")	$arSqlOrder[] = " MS.DATE_INSERT ".$order." ";
			elseif ($by == "msguid")		$arSqlOrder[] = " MS.MSGUID ".$order." ";
			elseif ($by == "mailbox_id")	$arSqlOrder[] = " MS.MAILBOX_ID ".$order." ";
			elseif ($by == "new_message")	$arSqlOrder[] = " MS.NEW_MESSAGE ".$order." ";
			elseif ($by == "mailbox_name" && !$bCnt)	$arSqlOrder[] = " MB.NAME ".$order." ";
			elseif ($by == "spam_rating")	{$arSqlOrder[] = " MS.SPAM_RATING ".$order." "; CMailFilter::RecalcSpamRating();}
			else $arSqlOrder[] = " MS.ID ".$order." ";
		}

		$strSqlOrder = "";
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);

		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= " WHERE 1=1 ".$strSqlSearch.$strSqlOrder;

		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$dbr->is_filtered = $is_filtered;
		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CMailMessage::GetList(Array(), Array("=ID"=>$ID));
	}

	public static function GetSpamRating($msgid, $arRow=false)
	{
		global $DB;
		if(!is_array($arRow))
			$res = $DB->Query("SELECT SPAM_RATING, SPAM_LAST_RESULT, FOR_SPAM_TEST FROM b_mail_message WHERE ID=".Intval($msgid));
		else
			$ar = $arRow;

		if(is_array($arRow) || $ar = $res->Fetch())
		{
			if($ar["SPAM_LAST_RESULT"]=="Y")
				return $ar["SPAM_RATING"];
			$arSpam = CMailFilter::GetSpamRating($ar["FOR_SPAM_TEST"]);
			$num = Round($arSpam["RATING"], 4);
			$DB->Query("UPDATE b_mail_message SET SPAM_RATING=".$num.", SPAM_LAST_RESULT='Y', SPAM_WORDS='".$DB->ForSql($arSpam["WORDS"], 255)."' WHERE ID=".Intval($msgid));
			return $num;
		}
	}


	public static function ParseHeader($message_header, $charset)
	{
		$h = new CMailHeader();
		$h->Parse($message_header, $charset);
		return $h;
	}

	private static function decodeMessageBody($header, $body, $charset)
	{
		$encoding = strtolower($header->GetHeader('CONTENT-TRANSFER-ENCODING'));

		if ($encoding == 'base64')
			$body = base64_decode($body);
		elseif ($encoding == 'quoted-printable')
			$body = quoted_printable_decode($body);
		elseif ($encoding == 'x-uue')
			$body = CMailUtil::uue_decode($body);

		$content_type = strtolower($header->content_type);
		if (
			(
				strpos($content_type, 'plain') !== false
				|| strpos($content_type, 'html') !== false
				|| strpos($content_type, 'text') !== false
			)
			&& strpos($content_type, 'x-vcard') === false
			&& strpos($content_type, 'csv') === false
		)
		{
			$body = CMailUtil::ConvertCharset($body, $header->charset, $charset);
			if ($body === false)
			{
				AddMessage2Log("Failed to convert attachment body. content_type = ".$content_type);
			}
		}

		return array(
			'CONTENT-TYPE' => $content_type,
			'CONTENT-ID'   => $header->content_id,
			'BODY'         => $body,
			'FILENAME'     => $header->filename
		);
	}

	private static function parseMessage($message, $charset)
	{
		$headerP = strpos($message, "\r\n\r\n");

		$rawHeader = substr($message, 0, $headerP);
		$body      = substr($message, $headerP+4);

		$header = CMailMessage::ParseHeader($rawHeader, $charset);

		$htmlBody = '';
		$textBody = '';

		$parts = array();

		if ($header->IsMultipart())
		{
			$startB = "\r\n--" . $header->GetBoundary() . "\r\n";
			$endB   = "\r\n--" . $header->GetBoundary() . "--\r\n";

			$startP = strpos($message, $startB)+strlen($startB);
			$endP   = strpos($message, $endB);

			$data = substr($message, $startP, $endP-$startP);

			$isHtml = false;
			$rawParts = preg_split("/\r\n--".preg_quote($header->GetBoundary(), '/')."\r\n/s", $data);
			$tmpParts = array();
			foreach ($rawParts as $part)
			{
				if (substr($part, 0, 2) == "\r\n")
					$part = "\r\n" . $part;

				list(, $subHtml, $subText, $subParts) = CMailMessage::parseMessage($part, $charset);

				if ($subHtml)
					$isHtml = true;

				if ($subText)
					$tmpParts[] = array($subHtml, $subText);

				$parts = array_merge($parts, $subParts);
			}

			if (strtolower($header->MultipartType()) == 'alternative')
			{
				foreach ($tmpParts as $part)
				{
					if ($part[0])
					{
						if (!$textBody || $htmlBody && (strlen($htmlBody) < strlen($part[0])))
						{
							$htmlBody = $part[0];
							$textBody = $part[1];
						}
					}
					else
					{
						if (!$textBody || strlen($textBody) < strlen($part[1]))
						{
							$htmlBody = '';
							$textBody = $part[1];
						}
					}
				}
			}
			else
			{
				foreach ($tmpParts as $part)
				{
					if ($textBody)
						$textBody .= "\r\n\r\n";
					$textBody .= $part[1];

					if ($isHtml)
					{
						if ($htmlBody)
							$htmlBody .= "\r\n\r\n";

						$htmlBody .= $part[0] ?: $part[1];
					}
				}
			}
		}
		else
		{
			$bodyPart = CMailMessage::decodeMessageBody($header, $body, $charset);

			if (!$bodyPart['FILENAME'] && strpos(strtolower($bodyPart['CONTENT-TYPE']), 'text/') === 0)
			{
				if (strtolower($bodyPart['CONTENT-TYPE']) == 'text/html')
				{
					$htmlBody = $bodyPart['BODY'];
					$textBody = htmlToTxt($bodyPart['BODY']);
				}
				else
				{
					$textBody = $bodyPart['BODY'];
				}
			}
			else
			{
				$parts[] = $bodyPart;
			}
		}

		return array($header, $htmlBody, $textBody, $parts);
	}

	public static function AddMessage($mailbox_id, $message, $charset)
	{
		global $DB;

		list($obHeader, $message_body_html, $message_body, $arMessageParts) = CMailMessage::parseMessage($message, $charset);

		$arFields = array(
			"MAILBOX_ID" => $mailbox_id,
			"HEADER" => $obHeader->strHeader,
			"FIELD_DATE_ORIGINAL" => $obHeader->GetHeader("DATE"),
			"NEW_MESSAGE"	=> "Y",
			"FIELD_FROM" => $obHeader->GetHeader("FROM"),
			"FIELD_REPLY_TO" => $obHeader->GetHeader("REPLY-TO"),
			"FIELD_TO" => $obHeader->GetHeader("TO"),
			"FIELD_CC" => $obHeader->GetHeader("CC"),
			"FIELD_BCC" => ($obHeader->GetHeader('X-Original-Rcpt-to')!=''?$obHeader->GetHeader('X-Original-Rcpt-to').($obHeader->GetHeader("BCC")!=''?', ':''):'').$obHeader->GetHeader("BCC"),
			"MSG_ID" => trim($obHeader->GetHeader("MESSAGE-ID"), " <>"),
			"IN_REPLY_TO" => trim($obHeader->GetHeader("IN-REPLY-TO"), " <>"),
			"FIELD_PRIORITY" => IntVal($obHeader->GetHeader("X-PRIORITY")),
			"MESSAGE_SIZE" => strlen($message),
			"SUBJECT" => $obHeader->GetHeader("SUBJECT"),
			"BODY" => rtrim($message_body)
		);

		if(COption::GetOptionString("mail", "save_src", B_MAIL_SAVE_SRC)=="Y")
			$arFields["FULL_TEXT"] = $message;

		if($message_body_html!==false)
			$arFields["FOR_SPAM_TEST"] = $obHeader->strHeader." ".$message_body_html;
		else
			$arFields["FOR_SPAM_TEST"] = $obHeader->strHeader." ".$message_body;

		$arFields["SPAM"] = "?";
		if(COption::GetOptionString("mail", "spam_check", B_MAIL_CHECK_SPAM)=="Y")
		{
			$arSpam = CMailFilter::GetSpamRating($arFields["FOR_SPAM_TEST"]);
			$arFields["SPAM_RATING"] = $arSpam["RATING"];
			$arFields["SPAM_WORDS"] = $arSpam["WORDS"];
			$arFields["SPAM_LAST_RESULT"] = "Y";
		}

		$MESSAGE_ID = CMailMessage::Add($arFields);
		CMailLog::AddMessage(
			Array(
				"MAILBOX_ID"=>$mailbox_id,
				"MESSAGE_ID"=>$MESSAGE_ID,
				"STATUS_GOOD"=>"Y",
				"LOG_TYPE"=>"NEW_MESSAGE",
				"MESSAGE"=>$arFields["SUBJECT"]." (".$arFields["MESSAGE_SIZE"].") ".
					(COption::GetOptionString("mail", "spam_check", B_MAIL_CHECK_SPAM)=="Y"?
						"[".Round($arFields["SPAM_RATING"], 3)."]"
					:
						""
					)
				)
			);

		$atchCnt = 0;
		if(COption::GetOptionString("mail", "save_attachments", B_MAIL_SAVE_ATTACHMENTS)=="Y")
		{
			foreach($arMessageParts as $part)
			{
				$arField = Array(
						"MESSAGE_ID" => $MESSAGE_ID,
						"FILE_NAME" => $part["FILENAME"],
						"CONTENT_TYPE" => $part["CONTENT-TYPE"],
						"FILE_DATA" => $part["BODY"],
						"CONTENT_ID" => $part["CONTENT-ID"]
					);
				if (CMailMessage::AddAttachment($arField))
					$atchCnt++;
			} // foreach($arMessageParts as $part)
		}

		$arFields['ID'] = $MESSAGE_ID;
		$arFields['ATTACHMENTS'] = $atchCnt;
		if (is_set($arFields, 'FIELD_DATE_ORIGINAL') && !is_set($arFields, 'FIELD_DATE'))
		{
			$arFields['FIELD_DATE'] = $DB->formatDate(
				date('d.m.Y H:i:s', strtotime($arFields['FIELD_DATE_ORIGINAL']) + CTimeZone::getOffset()),
				'DD.MM.YYYY HH:MI:SS', CLang::GetDateFormat('FULL')
			);
		}

		CMailFilter::Filter($arFields, "R");

		return $MESSAGE_ID;
	}

	public static function Add($arFields)
	{
		global $DB;

		if(is_set($arFields, "NEW_MESSAGE") && $arFields["NEW_MESSAGE"]!="N")
			$arFields["NEW_MESSAGE"]="Y";

		if(is_set($arFields, "FULL_TEXT") && !is_set($arFields, "MESSAGE_SIZE"))
			$arFields["MESSAGE_SIZE"] = strlen($arFields["FULL_TEXT"]);

		if(!is_set($arFields, "DATE_INSERT"))
			$arFields["~DATE_INSERT"] = $DB->GetNowFunction();

		if(is_set($arFields, "FIELD_DATE_ORIGINAL") && !is_set($arFields, "FIELD_DATE"))
			$arFields["FIELD_DATE"] = $DB->FormatDate(date("d.m.Y H:i:s", strtotime($arFields["FIELD_DATE_ORIGINAL"])+CTimeZone::GetOffset()), "DD.MM.YYYY HH:MI:SS", CLang::GetDateFormat("FULL"));

		if (array_key_exists('SUBJECT', $arFields))
		{
			$arFields['SUBJECT'] = strval(substr($arFields['SUBJECT'], 0, 255));
		}

		$ID = $DB->Add("b_mail_message", $arFields, Array("FULL_TEXT", "HEADER", "BODY", "FOR_SPAM_TEST"));

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = Intval($ID);

		if(is_set($arFields, "FIELD_DATE_ORIGINAL") && !is_set($arFields, "FIELD_DATE"))
			$arFields["FIELD_DATE"] = $DB->FormatDate(date("d.m.Y H:i:s", strtotime($arFields["FIELD_DATE_ORIGINAL"])+CTimeZone::GetOffset()), "DD.MM.YYYY HH:MI:SS", CLang::GetDateFormat("FULL"));

		if (array_key_exists('SUBJECT', $arFields))
		{
			$arFields['SUBJECT'] = strval(substr($arFields['SUBJECT'], 0, 255));
		}

		$strUpdate = $DB->PrepareUpdate("b_mail_message", $arFields);
		$strSql = "UPDATE b_mail_message SET ".$strUpdate." WHERE ID=".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function Delete($id)
	{
		global $DB;
		$id = intval($id);

		$res = $DB->query('SELECT FILE_ID FROM b_mail_msg_attachment WHERE MESSAGE_ID = '.$id);
		while ($file = $res->fetch())
		{
			if ($file['FILE_ID'])
				CFile::delete($file['FILE_ID']);
		}

		$strSql = "DELETE FROM b_mail_msg_attachment WHERE MESSAGE_ID=".$id;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$strSql = "DELETE FROM b_mail_message WHERE ID=".$id;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function MarkAsSpam($ID, $bIsSPAM = true, $arRow = false)
	{
		global $DB;
		if(!is_array($arRow))
			$res = $DB->Query("SELECT SPAM, FOR_SPAM_TEST, MAILBOX_ID FROM b_mail_message WHERE ID=".Intval($ID));
		else
			$ar = $arRow;

		if(is_array($arRow) || $ar = $res->Fetch())
		{
			if($bIsSPAM)
			{
				if($ar["SPAM"]!="Y")
				{
					if($ar["SPAM"]=="N")
						CMailFilter::DeleteFromSpamBase($ar["FOR_SPAM_TEST"], false);
					CMailFilter::MarkAsSpam($ar["FOR_SPAM_TEST"], true);
					CMailMessage::Update($ID, Array("SPAM"=>"Y"));

					CMailLog::AddMessage(
						Array(
							"MAILBOX_ID"=>$ar["MAILBOX_ID"],
							"MESSAGE_ID"=>$ID,
							"LOG_TYPE"=>"SPAM"
							)
					);
				}
			}
			else
			{
				if($ar["SPAM"]!="N")
				{
					if($ar["SPAM"]=="Y")
						CMailFilter::DeleteFromSpamBase($ar["FOR_SPAM_TEST"], true);
					CMailFilter::MarkAsSpam($ar["FOR_SPAM_TEST"], false);
					CMailMessage::Update($ID, Array("SPAM"=>"N"));

					CMailLog::AddMessage(
						Array(
							"MAILBOX_ID"=>$ar["MAILBOX_ID"],
							"MESSAGE_ID"=>$ID,
							"LOG_TYPE"=>"NOTSPAM"
							)
					);
				}
			}
			$DB->Query("UPDATE b_mail_message SET SPAM_LAST_RESULT='N' WHERE ID=".IntVal($ID));
		}
	}

	public static function AddAttachment($arFields)
	{
		global $DB;

		$strSql = "SELECT ATTACHMENTS FROM b_mail_message WHERE ID=".IntVal($arFields["MESSAGE_ID"]);
		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!($dbr_arr = $dbr->Fetch()))
			return false;

		$n = IntVal($dbr_arr["ATTACHMENTS"])+1;
		if(strlen($arFields["FILE_NAME"])<=0)
		{
			$arFields["FILE_NAME"] = $n.".";
			if(strpos($arFields["CONTENT_TYPE"], "message/")===0)
				$arFields["FILE_NAME"] .= "msg";
			else
				$arFields["FILE_NAME"] .= "tmp";
		}

		if(is_set($arFields, "CONTENT_TYPE"))
			$arFields["CONTENT_TYPE"] = strtolower($arFields["CONTENT_TYPE"]);

		if(strpos($arFields["CONTENT_TYPE"], "image/")===0 && (!is_set($arFields, "IMAGE_WIDTH") || !is_set($arFields, "IMAGE_HEIGHT")) && is_set($arFields, "FILE_DATA"))
		{
			$filename = CTempFile::GetFileName(md5(uniqid("")).'.tmp');
			CheckDirPath($filename);
			if(file_put_contents($filename, $arFields["FILE_DATA"]) !== false)
			{
				$img_arr = CFile::GetImageSize($filename);
				$arFields["IMAGE_WIDTH"] = $img_arr? $img_arr[0]: 0;
				$arFields["IMAGE_HEIGHT"] = $img_arr? $img_arr[1]: 0;
			}
		}

		if(is_set($arFields, "FILE_DATA") && !is_set($arFields, "FILE_SIZE"))
			$arFields["FILE_SIZE"] = CUtil::BinStrlen($arFields["FILE_DATA"]);

		$file = array(
			'name'      => md5($arFields['FILE_NAME']),
			'size'      => $arFields['FILE_SIZE'],
			'type'      => $arFields['CONTENT_TYPE'],
			'content'   => $arFields['FILE_DATA'],
			'MODULE_ID' => 'mail'
		);

		if (!($file_id = CFile::saveFile($file, 'mail/attachment')))
			return false;

		unset($arFields['FILE_DATA']);
		$arFields['FILE_ID'] = $file_id;

		$ID = $DB->add('b_mail_msg_attachment', $arFields);

		if ($ID > 0)
		{
			$strSql = 'UPDATE b_mail_message SET ATTACHMENTS = ' . $n . ' WHERE ID = ' . intval($arFields['MESSAGE_ID']);
			$DB->query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		}

		return $ID;
	}
}

class CMailAttachment
{
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB;

		$strSql =
				"SELECT * ".
				"FROM b_mail_msg_attachment MA ";

		$arSqlSearch = Array();
		foreach ($arFilter as $key => $val)
		{
			$res = CMailUtil::MkOperationFilter($key);
			$key = strtoupper($res["FIELD"]);
			$cOperationType = $res["OPERATION"];

			if($cOperationType == "?")
			{
				if (strlen($val)<=0) continue;
				switch($key)
				{
				case "ID":
				case "MESSAGE_ID":
				case "FILE_SIZE":
				case "IMAGE_WIDTH":
				case "IMAGE_HEIGHT":
					$arSqlSearch[] = GetFilterQuery("MA.".$key, $val, "N");
					break;
				case "FILE_NAME":
				case "FILE_DATA":
					$arSqlSearch[] = GetFilterQuery("MA.".$key, $val);
					break;
				case "CONTENT_TYPE":
					$arSqlSearch[] = GetFilterQuery("MA.".$key, $val, "Y", array("/"));
					break;
				}
			}
			else
			{
				switch($key)
				{
				case "ID":
				case "MESSAGE_ID":
				case "FILE_SIZE":
				case "IMAGE_WIDTH":
				case "IMAGE_HEIGHT":
					$arSqlSearch[] = CMailUtil::FilterCreate("MA.".$key, $val, "number", $cOperationType);
					break;
				case "FILE_NAME":
				case "CONTENT_TYPE":
				case "FILE_DATA":
					$arSqlSearch[] = CMailUtil::FilterCreate("MA.".$key, $val, "string", $cOperationType);
					break;
				}
			}
		}

		$is_filtered = false;
		$strSqlSearch = "";
		for($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
			{
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
				$is_filtered = true;
			}
		}
		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);

			if ($order!="asc")
				$order = "desc".(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"");

			if ($by == "message_id")		$arSqlOrder[] = " MA.MESSAGE_ID ".$order." ";
			elseif ($by == "file_name")		$arSqlOrder[] = " MA.FILE_NAME ".$order." ";
			elseif ($by == "file_size")		$arSqlOrder[] = " MA.FILE_SIZE ".$order." ";
			elseif ($by == "content_type")	$arSqlOrder[] = " MA.CONTENT_TYPE ".$order." ";
			elseif ($by == "image_width")	$arSqlOrder[] = " MA.IMAGE_WIDTH ".$order." ";
			elseif ($by == "image_height")	$arSqlOrder[] = " MA.IMAGE_HEIGHT ".$order." ";
			else $arSqlOrder[] = " MA.ID ".$order." ";
		}

		$strSqlOrder = "";
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);

		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= " WHERE 1=1 ".$strSqlSearch.$strSqlOrder;
		//echo "<pre>".$strSql."</pre>";
		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$dbr->is_filtered = $is_filtered;
		return $dbr;
	}

	public static function GetByID($ID)
	{
		return CMailAttachment::GetList(Array(), Array("=ID"=>$ID));
	}

	public static function Delete($id)
	{
		global $DB;
		$id = IntVal($id);

		$res = $DB->query('SELECT FILE_ID FROM b_mail_msg_attachment WHERE MESSAGE_ID = '.$id);
		while ($file = $res->fetch())
		{
			if ($file['FILE_ID'])
				CFile::delete($file['FILE_ID']);
		}

		$strSql = "DELETE FROM b_mail_msg_attachment WHERE ID=".$id;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function getContents($attachment)
	{
		if (!is_array($attachment))
		{
			if ($res = CMailAttachment::getByID($attachment))
				$attachment = $res->fetch();
		}

		if (!is_array($attachment) || !isset($attachment['FILE_DATA']) && empty($attachment['FILE_ID']))
			return false;

		if ($attachment['FILE_ID'] > 0)
		{
			if ($file = CFile::makeFileArray($attachment['FILE_ID']))
				return file_get_contents($file['tmp_name']);
		}
		else
		{
			return $attachment['FILE_DATA'];
		}
	}
}

class CAllMailUtil
{
	public static function ConvertCharset($str, $from, $to)
	{
		$from = trim(strtolower($from));
		$to = trim(strtolower($to));

		if (($from == 'utf-8' || $to == 'utf-8') || defined('BX_UTF'))
		{
			$error = "";
			$result = CharsetConverter::ConvertCharset($str, $from, $to, $error, true);
			return $result;
		}


		if($from=='windows-1251' || $from=='cp1251')
			$from = 'w';
		elseif(strpos($from, 'koi8')===0)
			$from = 'k';
		elseif($from=='dos-866')
			$from = 'd';
		elseif($from=='iso-8859-5')
			$from = 'i';
		else
			$from = '';

		if($to=='windows-1251' || $to=='cp1251')
			$to = 'w';
		elseif(strpos($to, 'koi8')===0)
			$to = 'k';
		elseif($to=='dos-866')
			$to = 'd';
		elseif($to=='iso-8859-5')
			$to = 'i';
		else
			$to = '';

		if(strlen($from)>0 && strlen($to)>0)
		{
			$str = convert_cyr_string($str, $from, $to);
		}
		return $str;

	}

	public static function uue_decode($str)
	{
		preg_match("/begin [0-7]{3} .+?\r?\n(.+)?\r?\nend/i", $str, $reg);

		$str = $reg[1];
		$res = '';
		$str = preg_split("/\r?\n/", trim($str));
		$strlen = count($str);

		for ($i = 0; $i < $strlen; $i++)
		{
			$pos = 1;
			$d = 0;
			$len= (int)(((ord(substr($str[$i],0,1)) -32) - ' ') & 077);

			while (($d + 3 <= $len) AND ($pos + 4 <= strlen($str[$i])))
			{
				$c0 = (ord(substr($str[$i],$pos,1)) ^ 0x20);
				$c1 = (ord(substr($str[$i],$pos+1,1)) ^ 0x20);
				$c2 = (ord(substr($str[$i],$pos+2,1)) ^ 0x20);
				$c3 = (ord(substr($str[$i],$pos+3,1)) ^ 0x20);
				$res .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4)).
						chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2)).
						chr(((($c2 - ' ') & 077) << 6) |  (($c3 - ' ') & 077));

				$pos += 4;
				$d += 3;
			}

			if (($d + 2 <= $len) && ($pos + 3 <= strlen($str[$i])))
			{
				$c0 = (ord(substr($str[$i],$pos,1)) ^ 0x20);
				$c1 = (ord(substr($str[$i],$pos+1,1)) ^ 0x20);
				$c2 = (ord(substr($str[$i],$pos+2,1)) ^ 0x20);
				$res .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4)).
						chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));

				$pos += 3;
				$d += 2;
			}

			if (($d + 1 <= $len) && ($pos + 2 <= strlen($str[$i])))
			{
				$c0 = (ord(substr($str[$i],$pos,1)) ^ 0x20);
				$c1 = (ord(substr($str[$i],$pos+1,1)) ^ 0x20);
				$res .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
			}
		}

		return $res;
	}

	public static function MkOperationFilter($key)
	{
		if(substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$cOperationType = "N";
		}
		elseif(substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$cOperationType = "GE";
		}
		elseif(substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$cOperationType = "G";
		}
		elseif(substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$cOperationType = "LE";
		}
		elseif(substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$cOperationType = "L";
		}
		elseif(substr($key, 0, 1)=="=")
		{
			$key = substr($key, 1);
			$cOperationType = "E";
		}
		else
			$cOperationType = "?";

		return Array("FIELD"=>$key, "OPERATION"=>$cOperationType);
	}

	public static function FilterCreate($fname, $vals, $type, $cOperationType=false, $bSkipEmpty = true)
	{
		return CMailUtil::FilterCreateEx($fname, $vals, $type, $bFullJoin, $cOperationType, $bSkipEmpty);
	}

	public static function FilterCreateEx($fname, $vals, $type, &$bFullJoin, $cOperationType=false, $bSkipEmpty = true)
	{
		global $DB;
		if(!is_array($vals))
			$vals=Array($vals);

		if(count($vals)<1)
			return "";

		if(is_bool($cOperationType))
		{
			if($cOperationType===true)
				$cOperationType = "N";
			else
				$cOperationType = "E";
		}

		if($cOperationType=="G")
			$strOperation = ">";
		elseif($cOperationType=="GE")
			$strOperation = ">=";
		elseif($cOperationType=="LE")
			$strOperation = "<=";
		elseif($cOperationType=="L")
			$strOperation = "<";
		else
			$strOperation = "=";

		$bFullJoin = false;
		$bWasLeftJoin = false;

		$res = Array();
		for($i = 0, $n = count($vals); $i < $n; $i++)
		{
			$val = $vals[$i];
			if(!$bSkipEmpty || strlen($val)>0 || (is_bool($val) && $val===false))
			{
				switch ($type)
				{
				case "string_equal":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL OR ".$DB->Length($fname)."<=0)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".CIBlock::_Upper($fname).$strOperation.CIBlock::_Upper("'".$DB->ForSql($val)."'").")";
					break;
				case "string":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL OR ".$DB->Length($fname)."<=0)";
					else
						if($strOperation=="=")
							$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".(strtoupper($DB->type)=="ORACLE"?CIBlock::_Upper($fname)." LIKE ".CIBlock::_Upper("'".$DB->ForSqlLike($val)."'")." ESCAPE '\\'" : $fname." ".($strOperation=="="?"LIKE":$strOperation)." '".$DB->ForSqlLike($val)."'").")";
						else
							$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".(strtoupper($DB->type)=="ORACLE"?CIBlock::_Upper($fname)." ".$strOperation." ".CIBlock::_Upper("'".$DB->ForSql($val)."'")." " : $fname." ".$strOperation." '".$DB->ForSql($val)."'").")";
					break;
				case "date":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
					break;
				case "number":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." '".DoubleVal($val)."')";
					break;
				case "number_above":
					if(strlen($val)<=0)
						$res[] = ($cOperationType=="N"?"NOT":"")."(".$fname." IS NULL)";
					else
						$res[] = ($cOperationType=="N"?" ".$fname." IS NULL OR NOT ":"")."(".$fname." ".$strOperation." '".$DB->ForSql($val)."')";
					break;
				}

				// INNER JOIN on such conditions
				if(strlen($val)>0 && $cOperationType!="N")
					$bFullJoin = true;
				else
					$bWasLeftJoin = true;
			}
		}

		$strResult = "";
		for($i = 0, $n = count($res); $i < $n; $i++)
		{
			if($i>0)
				$strResult .= ($cOperationType=="N"?" AND ":" OR ");
			$strResult .= "(".$res[$i].")";
		}
		if($strResult!="")
			$strResult = "(".$strResult.")";

		if($bFullJoin && $bWasLeftJoin && $cOperationType!="N")
			$bFullJoin = false;

		return $strResult;
	}

	public static function ByteXOR($a,$b,$l)
	{
		$c="";
		for($i=0; $i<$l; $i++)
			$c .= $a{$i}^$b{$i};
		return($c);
	}

	public static function BinMD5($val)
	{
		return(pack("H*",md5($val)));
	}

	public static function Decrypt($str, $key=false)
	{
		$res = '';
		if($key===false)
			$key = COption::GetOptionString("main", "pwdhashadd", "");
		$key1 = CMailUtil::BinMD5($key);
		$str = base64_decode($str);
		while($str)
		{
			$m = CUtil::BinSubstr($str, 0, 16);
			$str = CUtil::BinSubstr($str, 16);
			$m = CMailUtil::ByteXOR($m, $key1, 16);
			$res .= $m;
			$key1 = CMailUtil::BinMD5($key.$key1.$m);
		}
		return $res;
	}

	public static function Crypt($str, $key=false)
	{
		$res = '';
		if($key===false)
			$key = COption::GetOptionString("main", "pwdhashadd", "");
		$key1 = CMailUtil::BinMD5($key);
		while($str)
		{
			$m = CUtil::BinSubstr($str, 0, 16);
			$str = CUtil::BinSubstr($str, 16);
			$res .= CMailUtil::ByteXOR($m, $key1, 16);
			$key1 = CMailUtil::BinMD5($key.$key1.$m);
		}
		return(base64_encode($res));
	}

	public static function ExtractAllMailAddresses($emails)
	{
		$result = array();
		$arEMails = explode(",", $emails);
		foreach($arEMails as $mail)
		{
			$result[] = CMailUtil::ExtractMailAddress($mail);
		}
		return $result;
	}


	public static function ExtractMailAddress($email)
	{
		$email = trim($email);
		if(($pos = strpos($email, "<"))!==false)
			$email = substr($email, $pos+1);
		if(($pos = strpos($email, ">"))!==false)
			$email = substr($email, 0, $pos);
		return strtolower($email);
	}

	public static function CheckImapMailbox($server, $port, $use_tls, $login, $password, &$error, $timeout = 1)
	{
		$host = ((is_string($use_tls) ? ($use_tls == 'Y' || $use_tls == 'S') : $use_tls) ? 'tls://' : '') . $server;

		$imap = new CMailImap();

		try
		{
			$imap->connect($host, $port, $timeout, $use_tls != 'Y');
			$imap->authenticate($login, $password);
			$unseen = $imap->getUnseen();
		}
		catch (Exception $e)
		{
			$unseen = -1;
			$error  = $e->getMessage();
		}

		return $unseen;
	}
}


global $BX_MAIL_FILTER_CACHE, $BX_MAIL_SPAM_CNT;
$BX_MAIL_FILTER_CACHE = Array();
$BX_MAIL_SPAM_CNT = Array();

class CMailFilter
{
	public static function GetList($arOrder=Array(), $arFilter=Array(), $bCnt=false)
	{
		global $DB;
		$strSql =
				"SELECT ".
				($bCnt
				?
				"	COUNT('x') as CNT "
				:
				"	MF.*, MB.NAME as MAILBOX_NAME, MB.ID as MAILBOX_ID, MB.SERVER_TYPE as MAILBOX_TYPE, MB.DOMAINS as DOMAINS, ".
				"	".$DB->DateToCharFunction("MF.TIMESTAMP_X")."	as TIMESTAMP_X "
				).
				"	".
				"FROM b_mail_mailbox MB ".($arFilter["EMPTY"]=="Y"?"LEFT":"INNER")." JOIN b_mail_filter MF ON MB.ID=MF.MAILBOX_ID ";

		if(!is_array($arFilter))
			$arFilter = Array();
		$arSqlSearch = Array();
		$filter_keys = array_keys($arFilter);

		for($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			if (strlen($val)<=0) continue;
			$key = strtoupper($filter_keys[$i]);
			switch($key)
			{
			case "NAME":
			case "PHP_CONDITION":
			case "ACTION_PHP":
				$arSqlSearch[] = GetFilterQuery("MF.".$key, $val);
				break;
			case "SERVER_TYPE":
				$arSqlSearch[] = GetFilterQuery("MB.".$key, $val, "N");
				break;
			case "ID":
			case "ACTION_TYPE":
			case "MAILBOX_ID":
			case "PARENT_FILTER_ID":
			case "SORT":
			case "WHEN_MAIL_RECEIVED":
			case "WHEN_MANUALLY_RUN":
			case "ACTION_STOP_EXEC":
			case "ACTION_DELETE_MESSAGE":
			case "ACTION_READ":
			case "ACTIVE":
				$arSqlSearch[] = GetFilterQuery("MF.".$key, $val, "N");
				break;
			}
		}

		$is_filtered = false;
		$strSqlSearch = "";
		for($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
			{
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
				$is_filtered = true;
			}
		}

		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc".(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"");

			switch(strtoupper($by))
			{
			case "TIMESTAMP_X":
			case "MAILBOX_ID":
			case "ACTIVE":
			case "NAME":
			case "SORT":
			case "PARENT_FILTER_ID":
			case "WHEN_MAIL_RECEIVED":
			case "WHEN_MANUALLY_RUN":
			case "ACTION_STOP_EXEC":
			case "ACTION_DELETE_MESSAGE":
			case "ACTION_READ":
				$arSqlOrder[] = " MF.".$by." ".$order." ";
				break;
			case "MAILBOX_NAME":
				$arSqlOrder[] = " MB.NAME ".$order." ";
				$arSqlOrder[] = " MF.ID ".$order." ";
				break;
			default:
				$arSqlOrder[] = " MF.ID ".$order." ";
			}
		}

		$strSqlOrder = "";
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);

		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= " WHERE 1=1 ".$strSqlSearch.$strSqlOrder;

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->is_filtered = $is_filtered;
		return $res;
	}

	public static function GetByID($ID)
	{
		global $DB;
		return CMailFilter::GetList(Array(), Array("ID"=>$ID));
	}

	public static function CheckPHP($code, $field_name)
	{
		return true; // not work - E_CODE_ERROR

		global $php_errormsg;
		ini_set("track_errors", "on");
		$php_errormsg_prev = $php_errormsg;
		ob_start();
		error_reporting(0);
		@eval($code);
		ob_end_clean();
		if($php_errormsg != "")
			CMailError::SetError("B_MAIL_ERR_PHP", GetMessage("MAIL_CL_ERR_IN_PHP").$field_name.". (".$php_errormsg.")");
		$php_errormsg = $php_errormsg_prev;
		ini_set("track_errors", $prev);
	}

	public static function CheckFields($arFields, $ID=false)
	{
		$err_cnt = CMailError::ErrCount();
		$arMsg = Array();

		if(is_set($arFields, "NAME") && strlen($arFields["NAME"])<1)
		{
			CMailError::SetError("B_MAIL_ERR_NAME", GetMessage("MAIL_CL_ERR_NAME")." \"".GetMessage("MAIL_CL_NAME")."\"");
			$arMsg[] = array("id"=>"NAME", "text"=> GetMessage("MAIL_CL_ERR_NAME")." \"".GetMessage("MAIL_CL_NAME")."\"");
		}

		if(is_set($arFields, "PHP_CONDITION") && strlen(trim($arFields["PHP_CONDITION"]))>0)
		{
			if (!CMailFilter::CheckPHP($arFields["PHP_CONDITION"], GetMessage("MAIL_CL_PHP_COND")))
				$arMsg[] = array("id"=>"PHP_CONDITION", "text"=> GetMessage("MAIL_CL_ERR_IN_PHP").GetMessage("MAIL_CL_PHP_COND"));
		}

		if(is_set($arFields, "ACTION_PHP") && strlen(trim($arFields["ACTION_PHP"]))>0)
		{
			if (!CMailFilter::CheckPHP($arFields["ACTION_PHP"], GetMessage("MAIL_CL_PHP_ACT")))
				$arMsg[] = array("id"=>"ACTION_PHP", "text"=> GetMessage("MAIL_CL_ERR_IN_PHP").GetMessage("MAIL_CL_PHP_ACT"));
		}

		if(is_set($arFields, "MAILBOX_ID"))
		{
			$r = CMailBox::GetByID($arFields["MAILBOX_ID"]);
			if(!$r->Fetch())
			{
				CMailError::SetError("B_MAIL_ERR_BAD_MAILBOX", GetMessage("MAIL_CL_ERR_WRONG_MAILBOX"));
				$arMsg[] = array("id"=>"MAILBOX_ID", "text"=> GetMessage("MAIL_CL_ERR_WRONG_MAILBOX"));
			}
		}
		elseif($ID===false)
		{
			CMailError::SetError("B_MAIL_ERR_BAD_MAILBOX_NA", GetMessage("MAIL_CL_ERR_MAILBOX_NA"));
			$arMsg[] = array("id"=>"MAILBOX_ID", "text"=> GetMessage("MAIL_CL_ERR_MAILBOX_NA"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}
		return true;

		//return ($err_cnt == CMailError::ErrCount());
	}

	public static function Add($arFields)
	{
		global $DB;

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";

		if(is_set($arFields, "ACTION_READ") && $arFields["ACTION_READ"]!="Y" && $arFields["ACTION_READ"]!="N")
			$arFields["ACTION_READ"] = "-";

		if(is_set($arFields, "ACTION_SPAM") && $arFields["ACTION_SPAM"]!="Y" && $arFields["ACTION_SPAM"]!="N")
			$arFields["ACTION_SPAM"] = "-";

		if(is_set($arFields, "ACTION_DELETE_MESSAGE") && $arFields["ACTION_DELETE_MESSAGE"]!="Y")
			$arFields["ACTION_DELETE_MESSAGE"] ="N";

		if(is_set($arFields, "ACTION_STOP_EXEC") && $arFields["ACTION_STOP_EXEC"]!="Y")
			$arFields["ACTION_STOP_EXEC"] = "N";

		if(!CMailFilter::CheckFields($arFields))
			return false;

		$ID = $DB->Add("b_mail_filter", $arFields, Array("PHP_CONDITION", "ACTION_PHP"));

		if(is_set($arFields, "CONDITIONS"))
			CMailFilterCondition::SetConditions($ID, $arFields["CONDITIONS"]);

		CMailbox::SMTPReload();

		return $ID;
	}


	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"]="N";
		if(is_set($arFields, "WHEN_MAIL_RECEIVED") && $arFields["WHEN_MAIL_RECEIVED"]!="Y")
			$arFields["WHEN_MAIL_RECEIVED"] = "N";
		if(is_set($arFields, "WHEN_MANUALLY_RUN") && $arFields["WHEN_MANUALLY_RUN"]!="Y")
			$arFields["WHEN_MANUALLY_RUN"] = "N";
		if(is_set($arFields, "ACTION_READ") && $arFields["ACTION_READ"]!="Y" && $arFields["ACTION_READ"]!="N")
			$arFields["ACTION_READ"] = "-";
		if(is_set($arFields, "ACTION_SPAM") && $arFields["ACTION_SPAM"]!="Y" && $arFields["ACTION_SPAM"]!="N")
			$arFields["ACTION_SPAM"] = "-";
		if(is_set($arFields, "ACTION_DELETE_MESSAGE") && $arFields["ACTION_DELETE_MESSAGE"]!="Y")
			$arFields["ACTION_DELETE_MESSAGE"] ="N";
		if(is_set($arFields, "ACTION_STOP_EXEC") && $arFields["ACTION_STOP_EXEC"]!="Y")
			$arFields["ACTION_STOP_EXEC"] = "N";

		if(!CMailFilter::CheckFields($arFields, $ID))
			return false;

		$arUpdateBinds = array();
		$strUpdate = $DB->PrepareUpdateBind("b_mail_filter", $arFields,"", false, $arUpdateBinds);

		$strSql =
			"UPDATE b_mail_filter SET ".
				$strUpdate." ".
			"WHERE ID=".$ID;

		$arBinds = array();
		foreach($arUpdateBinds as $field_id)
			$arBinds[$field_id] = $arFields[$field_id];

		$DB->QueryBind($strSql, $arBinds);

		if(is_set($arFields, "CONDITIONS"))
			CMailFilterCondition::SetConditions($ID, $arFields["CONDITIONS"]);

		CMailbox::SMTPReload();

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$dbr = CMailFilterCondition::GetList(Array(), Array("FILTER_ID"=>$ID));
		while($r = $dbr->Fetch())
		{
			if(!CMailFilterCondition::Delete($r["ID"]))
				return false;
		}

		$strSql = "DELETE FROM b_mail_filter WHERE ID=".$ID;
		CMailbox::SMTPReload();
		return $DB->Query($strSql, true);
	}

	public static function Filter($arFields, $event, $FILTER_ID=false, $PARENT_FILTER_ID = false)
	{
		global $BX_MAIL_FILTER_CACHE, $DB;
		$PARENT_FILTER_ID = IntVal($PARENT_FILTER_ID);
		$MAILBOX_ID = IntVal($arFields["MAILBOX_ID"]);
		$MESSAGE_ID = IntVal($arFields["ID"]);

		$cache_param = $MAILBOX_ID."|".$PARENT_FILTER_ID."|".$event."|".$FILTER_ID;

		if(is_set($BX_MAIL_FILTER_CACHE, $cache_param))
		{
			$arFilterCond = $BX_MAIL_FILTER_CACHE[$cache_param]["CONDITIONS"];
			$arFilter = $BX_MAIL_FILTER_CACHE[$cache_param]["FILTER"];
		}
		else
		{
			$strSqlAdd = "";
			if($event=="R")
				$strSqlAdd .= "	AND (WHEN_MAIL_RECEIVED='Y')";
			else
				$strSqlAdd .= "	AND (WHEN_MANUALLY_RUN='Y' ".(IntVal($FILTER_ID)>0?" AND f.ID='".IntVal($FILTER_ID)."'":"").")";

			$strSql =
				"SELECT f.*, c.*, f.ID, c.ID as CONDITION_ID
				FROM b_mail_filter f LEFT JOIN b_mail_filter_cond c ON f.ID = c.FILTER_ID
				WHERE (f.MAILBOX_ID = ".$MAILBOX_ID." OR MAILBOX_ID IS NULL)
					AND f.ACTIVE = 'Y'
					AND (f.PARENT_FILTER_ID = " . ($PARENT_FILTER_ID > 0 ? $PARENT_FILTER_ID : "'' OR f.PARENT_FILTER_ID IS NULL") . ")" .
					$strSqlAdd."
				ORDER BY f.SORT, f.ID";

			$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$arFilter = Array();
			$arFilterCond = Array();
			$prev_ID = 0;
			$arr_prev = false;
			$arConds = Array();
			while($arr = $dbr->Fetch())
			{
				$arFilter[$arr["ID"]] = $arr;
				if($arr["CONDITION_ID"]>0)
				{
					if(!is_array($arFilterCond[$arr["ID"]]))
						$arFilterCond[$arr["ID"]] = Array();
					$arFilterCond[$arr["ID"]][] = $arr;
				}
			}

			$BX_MAIL_FILTER_CACHE[$cache_param] = Array("FILTER"=>$arFilter, "CONDITIONS"=>$arFilterCond);
		}

		$arFieldsOriginal = $arFields;
		foreach($arFilter as $filter_id=>$arFilterParams)
		{
			$arFields = $arFieldsOriginal;
			$arFields["MAIL_FILTER"] = $arFilterParams;

			$arAllConditions = $arFilterCond[$filter_id];
			$bCondOK = true;
			if(!is_array($arAllConditions))
				$arAllConditions = Array();
			foreach($arAllConditions as $k => $arCondition)
			{
				$bCondOK = false;
				$type = $arCondition["TYPE"];
				switch($type)
				{
				case "ALL":case "RECIPIENT":case "SENDER":
					if($type=="ALL")
						$arFields[$type] = $arFields["HEADER"]."\r\n".$arFields["BODY"];
					elseif($type=="RECIPIENT")
						$arFields[$type] = $arFields["FIELD_CC"]."\r\n".$arFields["FIELD_TO"]."\r\n".$arFields["FIELD_BCC"];
					else
						$arFields[$type] = $arFields["FIELD_FROM"]."\r\n".$arFields["FIELD_REPLY_TO"];
				case "HEADER": case "FIELD_FROM": case "FIELD_REPLY_TO": case "FIELD_TO": case "FIELD_CC": case "SUBJECT": case "BODY":
					$arStrings = explode("\n", $arCondition["STRINGS"]);
					if($arCondition["COMPARE_TYPE"]=="NOT_EQUAL" || $arCondition["COMPARE_TYPE"]=="NOT_CONTAIN")
					{
						$bCondOK = true;
						for($i = 0, $n = count($arStrings); $i < $n; $i++)
						{
							$str = strtoupper(Trim($arStrings[$i], "\r"));
							switch($arCondition["COMPARE_TYPE"])
							{
							case "NOT_CONTAIN":
								if(strlen($str)>0 && strpos(strtoupper($arFields[$type]), $str)!==false)
									$bCondOK = false;
								break;
							case "NOT_EQUAL":
								if($str==strtoupper($arFields[$type]))
									$bCondOK = false;
								break;
							}

							if(!$bCondOK)
								break;
						}
					}
					else
					{
						for($i = 0, $n = count($arStrings); $i < $n; $i++)
						{
							$str = strtoupper(Trim($arStrings[$i], "\r"));
							switch($arCondition["COMPARE_TYPE"])
							{
							case "CONTAIN":
								if(strlen($str)>0 && strpos(strtoupper($arFields[$type]), $str)!==false)
									$bCondOK = true;
								break;
							case "EQUAL":
								if($str==strtoupper($arFields[$type]))
									$bCondOK = true;
								break;
							case "REGEXP":
								if(preg_match("'".str_replace("'", "\'", $str)."'i", $arFields[$type]))
									$bCondOK = true;
								break;
							}

							if($bCondOK)
								break;
						}
					}
					break;

				case "ATTACHMENT":
					$db_att = CMailAttachment::GetList(Array(), Array("MESSAGE_ID"=>$arFields["ID"]));
					$arStrings = explode("\n", $arCondition["STRINGS"]);
					if($arCondition["COMPARE_TYPE"]=="NOT_EQUAL" || $arCondition["COMPARE_TYPE"]=="NOT_CONTAIN")
					{
						$bCondOK = true;
						while($arr_att = $db_att->Fetch())
						{
							for($i = 0, $n = count($arStrings); $i < $n; $i++)
							{
								$str = strtoupper(Trim($arStrings[$i], "\r"));
								switch($arCondition["COMPARE_TYPE"])
								{
									case "NOT_CONTAIN":
										if(strlen($str)>0 && strpos(strtoupper($arr_att["FILE_NAME"]), $str)!==false)
											$bCondOK = false;
										break;
									case "NOT_EQUAL":
										if($str==strtoupper($arr_att["FILE_NAME"]))
											$bCondOK = false;
										break;
								}
							}
							if(!$bCondOK)
								break;
						}
					}
					else
					{
						while($arr_att = $db_att->Fetch())
						{
							for($i = 0, $n = count($arStrings); $i < $n; $i++)
							{
								$str = strtoupper(Trim($arStrings[$i], "\r"));
								switch($arCondition["COMPARE_TYPE"])
								{
								case "CONTAIN":
									if(strlen($str)>0 && strpos(strtoupper($arr_att["FILE_NAME"]), $str)!==false)
										$bCondOK = true;
									break;
								case "EQUAL":
									if($str==strtoupper($arr_att["FILE_NAME"]))
										$bCondOK = true;
									break;
								case "REGEXP":
									if(preg_match("'".str_replace("'", "\'", $str)."'i", $arr_att["FILE_NAME"]))
										$bCondOK = true;
									break;
								}
							}
							if($bCondOK)
								break;
						}
					}
					break;
				} //switch

				if(!$bCondOK)
					break;
			} //foreach($arAllConditions as $k => $arCondition)

			if(!$bCondOK)
				continue;

			if($arFilterParams["SPAM_RATING"]>0)
			{
				$arFields["SPAM_RATING"] = CMailMessage::GetSpamRating($arFields["ID"], $arFields);
				if($arFilterParams["SPAM_RATING_TYPE"]==">" && $arFields["SPAM_RATING"]<=$arFilterParams["SPAM_RATING"])
					continue;
				if($arFilterParams["SPAM_RATING_TYPE"]!=">" && $arFields["SPAM_RATING"]>=$arFilterParams["SPAM_RATING"])
					continue;
			}

			if($arFilterParams["MESSAGE_SIZE"]>0)
			{
				$MESSAGE_SIZE = $arFields["MESSAGE_SIZE"];
				if($arFilterParams["MESSAGE_SIZE_UNIT"]=="k")
					$MESSAGE_SIZE = IntVal($MESSAGE_SIZE/1024);
				elseif($arFilterParams["MESSAGE_SIZE_UNIT"]=="m")
					$MESSAGE_SIZE = IntVal($MESSAGE_SIZE/1024/1024);

				if($arFilterParams["MESSAGE_SIZE_TYPE"]==">" && $MESSAGE_SIZE<=$arFilterParams["MESSAGE_SIZE"])
					continue;
				if($arFilterParams["MESSAGE_SIZE_TYPE"]!=">" && $MESSAGE_SIZE>=$arFilterParams["MESSAGE_SIZE"])
					continue;
			}

			if(strlen($arFilterParams["PHP_CONDITION"])>0)
				if(!CMailFilter::DoPHPAction("php_cond_".$arFilterParams["ID"]."_", $arFilterParams["PHP_CONDITION"], $arFields))
					continue;

			$arModFilter = false;
			if($arFilterParams["ACTION_TYPE"]!="")
			{
				$res = CMailFilter::GetFilterList($arFilterParams["ACTION_TYPE"]);
				if($arModFilter = $res->Fetch())
				{
					if (
						(is_array($arModFilter["CONDITION_FUNC"]) && count($arModFilter["CONDITION_FUNC"]) > 0) ||
						strlen($arModFilter["CONDITION_FUNC"]) > 0
					)
						if(!call_user_func_array($arModFilter["CONDITION_FUNC"], Array(&$arFields, &$arFilterParams["ACTION_VARS"])))
							continue;
				}
			}
			CMailLog::AddMessage(
				Array(
					"MAILBOX_ID"=>$MAILBOX_ID,
					"MESSAGE_ID"=>$MESSAGE_ID,
					"FILTER_ID"=>$filter_id,
					"STATUS_GOOD"=>"Y",
					"LOG_TYPE"=>"FILTER_OK",
					"MESSAGE"=>$event,
					)
				);

			if($arModFilter)
				if (
						(is_array($arModFilter["ACTION_FUNC"]) && count($arModFilter["ACTION_FUNC"]) > 0) ||
						strlen($arModFilter["ACTION_FUNC"]) > 0
					)
					call_user_func_array($arModFilter["ACTION_FUNC"], array(&$arFields, &$arFilterParams["ACTION_VARS"]));


			if(strlen(Trim($arFilterParams["ACTION_PHP"]))>0)
			{
				$res = CMailFilter::DoPHPAction("php_act_".$arFilterParams["ID"]."_", $arFilterParams["ACTION_PHP"], $arFields);
				CMailLog::AddMessage(
					Array(
						"MAILBOX_ID"=>$MAILBOX_ID,
						"MESSAGE_ID"=>$MESSAGE_ID,
						"FILTER_ID"=>$filter_id,
						"LOG_TYPE"=>"DO_PHP",
						"MESSAGE"=>""
						)
					);
			}

			if($arFilterParams["ACTION_SPAM"]=="Y" && $arFields["SPAM"]!="Y")
			{
				if($arFields["SPAM"]=="N")
					CMailFilter::DeleteFromSpamBase($arFields["FOR_SPAM_TEST"], false);
				CMailFilter::MarkAsSpam($arFields["FOR_SPAM_TEST"], true);
				CMailMessage::Update($MESSAGE_ID, Array("SPAM"=>"Y"));
				CMailLog::AddMessage(
					Array(
						"MAILBOX_ID"=>$MAILBOX_ID,
						"MESSAGE_ID"=>$MESSAGE_ID,
						"FILTER_ID"=>$filter_id,
						"LOG_TYPE"=>"SPAM",
						"MESSAGE"=>""
						)
					);
				$arFields["SPAM"] = "Y";
			}
			elseif($arFilterParams["ACTION_SPAM"]=="N" && $arFields["SPAM"]!="N")
			{
				if($arFields["SPAM"]=="Y")
					CMailFilter::DeleteFromSpamBase($arFields["FOR_SPAM_TEST"], true);
				CMailFilter::MarkAsSpam($arFields["FOR_SPAM_TEST"], false);
				CMailMessage::Update($MESSAGE_ID, Array("SPAM"=>"N"));
				CMailLog::AddMessage(
					Array(
						"MAILBOX_ID"=>$MAILBOX_ID,
						"MESSAGE_ID"=>$MESSAGE_ID,
						"FILTER_ID"=>$filter_id,
						"LOG_TYPE"=>"NOTSPAM",
						"MESSAGE"=>""
						)
					);
				$arFields["SPAM"] = "N";
			}

			if($arFilterParams["ACTION_READ"]=="Y" && $arFields["NEW_MESSAGE"]=="Y")
			{
				$arFields["NEW_MESSAGE"] = "N";
				CMailMessage::Update($MESSAGE_ID, Array("NEW_MESSAGE"=>"N"));
			}
			elseif($arFilterParams["ACTION_READ"]=="N" && $arFields["NEW_MESSAGE"]!="Y")
			{
				$arFields["NEW_MESSAGE"] = "Y";
				CMailMessage::Update($MESSAGE_ID, Array("NEW_MESSAGE"=>"Y"));
			}

			if($arFilterParams["ACTION_DELETE_MESSAGE"]=="Y")
			{
				CMailLog::AddMessage(
					Array(
						"MAILBOX_ID"=>$MAILBOX_ID,
						"MESSAGE_ID"=>$MESSAGE_ID,
						"FILTER_ID"=>$filter_id,
						"STATUS_GOOD"=>"Y",
						"LOG_TYPE"=>"MESSAGE_DELETED",
						"MESSAGE"=>""
						)
					);
				CMailMessage::Delete($MESSAGE_ID);
			}

			if($arFilterParams["ACTION_STOP_EXEC"]=="Y")
			{
				CMailLog::AddMessage(
					Array(
						"MAILBOX_ID"=>$MAILBOX_ID,
						"MESSAGE_ID"=>$MESSAGE_ID,
						"FILTER_ID"=>$filter_id,
						"STATUS_GOOD"=>"Y",
						"LOG_TYPE"=>"FILTER_STOP",
						"MESSAGE"=>""
						)
					);
				return true;
			}
		}

		return true;
	}


	public static function FilterMessage($message_id, $event, $FILTER_ID=false)
	{
		$res = CMailMessage::GetByID($message_id);
		if($arFields = $res->Fetch())
			return CMailFilter::Filter($arFields, $event, $FILTER_ID);

		return false;
	}

	public static function RecalcSpamRating()
	{
		global $DB;
		$res = $DB->Query("SELECT ID, FOR_SPAM_TEST FROM b_mail_message WHERE SPAM_LAST_RESULT<>'N'");
		while($arr = $res->Fetch())
		{
			$arSpam = CMailFilter::GetSpamRating($arr["FOR_SPAM_TEST"]);
			$DB->Query("UPDATE b_mail_message SET SPAM_RATING=".Round($arSpam["RATING"], 4).", SPAM_LAST_RESULT='Y', SPAM_WORDS='".$DB->ForSql($arSpam["WORDS"], 255)."' WHERE ID=".$arr["ID"]);
		}
	}

	public static function GetSpamRating($message)
	{
		global $DB;

		$arWords = CMailFilter::getWords($message, 1000);

		if (empty($arWords))
			return 0;

		// for every word find Si
		$arWords = array_map("md5", $arWords);

		global $BX_MAIL_SPAM_CNT;
		if(!is_set($BX_MAIL_SPAM_CNT, "G"))
		{
			$strSql = "SELECT MAX(GOOD_CNT) as G, MAX(BAD_CNT) as B FROM b_mail_spam_weight";
			if($res = $DB->Query($strSql))
				$BX_MAIL_SPAM_CNT = $res->Fetch();

			if(intval($BX_MAIL_SPAM_CNT["G"])<=0)
				$BX_MAIL_SPAM_CNT["G"] = 1;

			if(intval($BX_MAIL_SPAM_CNT["B"])<=0)
				$BX_MAIL_SPAM_CNT["B"] = 1;
		}

		$CNT_WORDS = COption::GetOptionInt("mail", "spam_word_count", B_MAIL_WORD_CNT);
		$MIN_COUNT =  COption::GetOptionInt("mail", "spam_min_count", B_MAIL_MIN_CNT);
		// select $CNT_WORDS words with max |Si - 0.5|
		// if the word placed less then xxx (5) times, then ignore
		$strSql =
			"SELECT SW.*, ".
			"	(BAD_CNT/".$BX_MAIL_SPAM_CNT["B"].".0) / (2*GOOD_CNT/".$BX_MAIL_SPAM_CNT["G"].".0 + BAD_CNT/".$BX_MAIL_SPAM_CNT["B"].".0) as RATING, ".
			"	ABS((BAD_CNT/".$BX_MAIL_SPAM_CNT["B"].".0) / (2*GOOD_CNT/".$BX_MAIL_SPAM_CNT["G"].".0 + BAD_CNT/".$BX_MAIL_SPAM_CNT["B"].".0) - 0.5) as MOD_RATING ".
			"FROM b_mail_spam_weight SW ".
			"WHERE WORD_ID IN ('".implode("', '", $arWords)."') ".
			"	AND ABS((BAD_CNT/".$BX_MAIL_SPAM_CNT["B"].".0) / (2*GOOD_CNT/".$BX_MAIL_SPAM_CNT["G"].".0 + BAD_CNT/".$BX_MAIL_SPAM_CNT["B"].".0) - 0.5) > 0.1 ".
			"	AND TOTAL_CNT>".$MIN_COUNT." ".
			"ORDER BY MOD_RATING DESC ".
			(strtoupper($DB->type)=="MYSQL"?"LIMIT ".$CNT_WORDS : "");

		//echo htmlspecialcharsbx($strSql)."<br>";

		$a = 1;
		$b = 1;
		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arr = true;
		$words = "";

		for($i=0; $i<$CNT_WORDS; $i++)
		{
			if($arr && $arr = $dbr->Fetch())
			{
				//echo "<font size='-3'>".htmlspecialcharsbx($arr["WORD_REAL"])."=".$arr["RATING"]."<br></font> ";
				$words .= $arr["WORD_REAL"]." ".Round($arr["RATING"]*100, 4)." ".$arr["BAD_CNT"]." ".$arr["GOOD_CNT"]."\n";
				$a = $a * ($arr["RATING"]==0?0.00001:$arr["RATING"]);
				$b = $b * (1 - ($arr["RATING"]==1?0.9999:$arr["RATING"]));
			}
			else
			{
				//if there is no word then weight Si = 0.4
				$a = $a * 0.4;
				$b = $b * (1 - 0.4);
			}
		}
		// calculate Bayes for the whole message
		$rating = $a/($a+$b) * 100;

		return Array("RATING"=>$rating, "WORDS"=>$words);
	}

	public static function getWords($message, $max_words)
	{
		static $tok = null;
		if (!isset($tok))
		{
			$tok = "}{~";
			for($i = ord("\x01"); $i < ord("\x23"); $i++)
				$tok .= chr($i);
			for($i = ord("\x25"); $i < ord("\x3F"); $i++)
				$tok .= chr($i);
			for($i = ord("\x5B"); $i < ord("\x5E"); $i++)
				$tok .= chr($i);
		}

		$arWords = array();
		$word = strtok($message, $tok);
		while($word !== false)
		{
			$arWords[$word] = $word;
			if (count($arWords) >= $max_words)
				break;
			$word = strtok($tok);
		}
		return $arWords;
	}

	public static function DoPHPAction($id, $action, &$arMessageFields)
	{
		return eval($action);
	}

	public static function DeleteFromSpamBase($message, $bIsSPAM = true)
	{
		return CMailFilter::SpamAction($message, $bIsSPAM, true);
	}

	public static function MarkAsSpam($message, $bIsSPAM = true)
	{
		return CMailFilter::SpamAction($message, $bIsSPAM);
	}

	public static function SpamAction($message, $bIsSPAM, $bDelete = false)
	{
		global $DB;
		global $BX_MAIL_SPAM_CNT;

		if(!is_set($BX_MAIL_SPAM_CNT, "G"))
		{
			$strSql = "SELECT MAX(GOOD_CNT) as G, MAX(BAD_CNT) as B FROM b_mail_spam_weight";
			if($res = $DB->Query($strSql))
				$BX_MAIL_SPAM_CNT = $res->Fetch();

			if(intval($BX_MAIL_SPAM_CNT["G"])<=0)
				$BX_MAIL_SPAM_CNT["G"] = 1;

			if(intval($BX_MAIL_SPAM_CNT["B"])<=0)
				$BX_MAIL_SPAM_CNT["B"] = 1;
		}

		if($bDelete && $bIsSPAM)
			$BX_MAIL_SPAM_CNT["B"]--;
		elseif($bDelete && !$bIsSPAM)
			$BX_MAIL_SPAM_CNT["G"]--;
		elseif(!$bDelete && $bIsSPAM)
			$BX_MAIL_SPAM_CNT["B"]++;
		elseif(!$bDelete && !$bIsSPAM)
			$BX_MAIL_SPAM_CNT["G"]++;

		@set_time_limit(30);

		// split to words
		$arWords = CMailFilter::getWords($message, 1000);

		// for every word find Si
		$strWords = "''";
		foreach($arWords as $word)
		{
			$word_md5 = md5($word);

			// change weight
			$strSql =
				"INSERT INTO b_mail_spam_weight(WORD_ID, WORD_REAL, GOOD_CNT, BAD_CNT, TOTAL_CNT) ".
				"VALUES('".$word_md5."', '".$DB->ForSql($word, 40)."', ".($bIsSPAM?0:1).", ".($bIsSPAM?1:0).", 1)";

			if($bDelete || (!$DB->Query($strSql, true)))
			{
				if($bDelete)
				{
					$strSql =
						"UPDATE b_mail_spam_weight SET ".
						"	GOOD_CNT = GOOD_CNT - ".($bIsSPAM?0:1).", ".
						"	BAD_CNT = BAD_CNT - ".($bIsSPAM?1:0).", ".
						"	TOTAL_CNT = TOTAL_CNT - 1 ".
						"WHERE WORD_ID = '".$word_md5."' ".
						"	AND ".($bIsSPAM?"BAD_CNT>0":"GOOD_CNT>0");// AND WORD_REAL = '".$DB->ForSql($word, 40)."'";
				}
				else
				{
					$strSql =
						"UPDATE b_mail_spam_weight SET ".
						"	GOOD_CNT = GOOD_CNT + ".($bIsSPAM?0:1).", ".
						"	BAD_CNT = BAD_CNT + ".($bIsSPAM?1:0).", ".
						"	TOTAL_CNT = TOTAL_CNT + 1 ".
						"WHERE WORD_ID='".$word_md5."'";// AND WORD_REAL = '".$DB->ForSql($word, 40)."'";
				}

				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}


		if(COption::GetOptionString("mail", "reset_all_spam_result", "N") == "Y")
			$DB->Query("UPDATE b_mail_message SET SPAM_LAST_RESULT='N'");
	}


	function GetFilterList($id = "")
	{
		static $BX_MAIL_CUST_FILTER_LIST = false;
		if($BX_MAIL_CUST_FILTER_LIST === false)
		{
			$BX_MAIL_CUST_FILTER_LIST = array();
			foreach(GetModuleEvents("mail", "OnGetFilterList", true) as $arEvent)
			{
				$arResult = ExecuteModuleEventEx($arEvent);
				if(is_array($arResult))
					$BX_MAIL_CUST_FILTER_LIST[] = $arResult;
			}
		}

		if($id != "")
		{
			$allResultsTemp = array();
			foreach($BX_MAIL_CUST_FILTER_LIST as $arResult)
			{
				if($arResult["ID"] == $id)
				{
					$allResultsTemp[] = $arResult;
					break;
				}
			}
		}
		else
		{
			$allResultsTemp = $BX_MAIL_CUST_FILTER_LIST;
		}

		$db_res = new CDBResult;
		$db_res->InitFromArray($allResultsTemp);
		return $db_res;
	}
}

class CMailFilterCondition
{
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB;
		$strSql =
				"SELECT MFC.* ".
				"FROM b_mail_filter_cond MFC ";

		if(!is_array($arFilter))
			$arFilter = Array();
		$arSqlSearch = Array();
		$filter_keys = array_keys($arFilter);
		for($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			if (strlen($val)<=0) continue;
			$key = strtoupper($filter_keys[$i]);
			switch($key)
			{
			case "TYPE":
			case "STRINGS":
			case "COMPARE_TYPE":
				$arSqlSearch[] = GetFilterQuery("MFC.".$key, $val);
				break;
			case "ID":
			case "FILTER_ID":
				$arSqlSearch[] = GetFilterQuery("MFC.".$key, $val, "N");
				break;
			}
		}

		$strSqlSearch = "";
		for($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
		}

		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc".(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"");

			switch(strtoupper($by))
			{
			case "FILTER_ID":
			case "TYPE":
			case "STRINGS":
			case "COMPARE_TYPE":
				$arSqlOrder[] = " MFC.".$by." ".$order." ";
				break;
			default:
				$arSqlOrder[] = " MFC.ID ".$order." ";
			}
		}

		$strSqlOrder = "";
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);

		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= " WHERE 1=1 ".$strSqlSearch.$strSqlOrder;

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->is_filtered = (count($arSqlOrder)>0);
		return $res;
	}

	public static function GetByID($ID)
	{
		global $DB;
		return CMailFilterCondition::GetList(Array(), Array("ID"=>$ID));
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = Intval($ID);
		$strSql = "DELETE FROM b_mail_filter_cond WHERE ID=".$ID;
		return $DB->Query($strSql, true);
	}

	public static function SetConditions($FILTER_ID, $CONDITIONS, $bClearOther = true)
	{
		global $DB;

		$FILTER_ID = IntVal($FILTER_ID);

		$strSql=
			"SELECT ID ".
			"FROM b_mail_filter_cond ".
			"WHERE FILTER_ID=".$FILTER_ID;

		$dbr = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		while($dbr_arr = $dbr->Fetch())
		{
			if(is_set($CONDITIONS, $dbr_arr["ID"]) && is_array($CONDITIONS[$dbr_arr["ID"]]) && strlen($CONDITIONS[$dbr_arr["ID"]]["STRINGS"])>0)
			{
				$arFields = $CONDITIONS[$dbr_arr["ID"]];
				unset($arFields["ID"]);
				$arFields["FILTER_ID"] = $FILTER_ID;
				CMailFilterCondition::Update($dbr_arr["ID"], $arFields);
				unset($CONDITIONS[$dbr_arr["ID"]]);
			}
			elseif($bClearOther)
			{
				$DB->Query("DELETE FROM b_mail_filter_cond WHERE ID=".$dbr_arr["ID"]);
			}
		}

		foreach($CONDITIONS as $arFields)
		{
			if(is_array($arFields) && strlen($arFields["STRINGS"])>0)
			{
				$arFields["FILTER_ID"] = $FILTER_ID;
				unset($arFields["ID"]);
				CMailFilterCondition::Add($arFields);
			}
		}
	}

	public static function Add($arFields)
	{
		global $DB;

		if(is_set($arFields, "COMPARE_TYPE") && $arFields["COMPARE_TYPE"]!="EQUAL" && $arFields["COMPARE_TYPE"]!="NOT_EQUAL" && $arFields["COMPARE_TYPE"]!="NOT_CONTAIN" && $arFields["COMPARE_TYPE"]!="REGEXP")
			$arFields["COMPARE_TYPE"]="CONTAIN";

		$ID = $DB->Add("b_mail_filter_cond", $arFields);
		return $ID;
	}


	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if(is_set($arFields, "COMPARE_TYPE") && $arFields["COMPARE_TYPE"]!="EQUAL" && $arFields["COMPARE_TYPE"]!="NOT_EQUAL" && $arFields["COMPARE_TYPE"]!="NOT_CONTAIN" && $arFields["COMPARE_TYPE"]!="REGEXP")
			$arFields["COMPARE_TYPE"]="CONTAIN";


		$strUpdate = $DB->PrepareUpdate("b_mail_filter_cond", $arFields);

		$strSql =
			"UPDATE b_mail_filter_cond SET ".
				$strUpdate." ".
			"WHERE ID=".$ID;

		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}
}


class CMailLog
{
	public static function AddMessage($arFields)
	{
		global $DB;

		if (COption::getOptionString('mail', 'disable_log', 'N') == 'Y')
			return;

		$arFields["~DATE_INSERT"] = $DB->GetNowFunction();
		if(array_key_exists('MESSAGE', $arFields))
			$arFields['MESSAGE'] = strval(substr($arFields['MESSAGE'], 0, 255));
		else
			$arFields['MESSAGE'] = '';

		return $DB->Add("b_mail_log", $arFields);
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		$strSql = "DELETE FROM b_mail_log WHERE ID=".$ID;
		return $DB->Query($strSql, true);
	}

	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB;
		$strSql =
				"SELECT ML.*, MB.NAME as MAILBOX_NAME, ".
				"	MF.NAME as FILTER_NAME, ".
				"	MM.SUBJECT as MESSAGE_SUBJECT, ".
				"	".$DB->DateToCharFunction("ML.DATE_INSERT")."	as DATE_INSERT ".
				"	".
				"FROM b_mail_log ML ".
				"	INNER JOIN b_mail_mailbox MB ON MB.ID=ML.MAILBOX_ID ".
				"	LEFT JOIN b_mail_filter MF ON MF.ID=ML.FILTER_ID ".
				"	LEFT JOIN b_mail_message MM ON MM.ID=ML.MESSAGE_ID ";

		if(!is_array($arFilter))
			$arFilter = Array();
		$arSqlSearch = Array();
		$filter_keys = array_keys($arFilter);
		for($i = 0, $n = count($filter_keys); $i < $n; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];
			if (strlen($val)<=0) continue;
			$key = strtoupper($filter_keys[$i]);
			switch($key)
			{
			case "ID":
			case "MAILBOX_ID":
			case "FILTER_ID":
			case "MESSAGE_ID":
			case "LOG_TYPE":
			case "STATUS_GOOD":
				$arSqlSearch[] = GetFilterQuery("ML.".$key, $val, "N");
				break;
			case "MESSAGE":
				$arSqlSearch[] = GetFilterQuery("ML.".$key, $val);
				break;
			case "FILTER_NAME":
				$arSqlSearch[] = GetFilterQuery("MF.NAME", $val);
				break;
			case "MAILBOX_NAME":
				$arSqlSearch[] = GetFilterQuery("MB.NAME", $val);
				break;
			case "MESSAGE_SUBJECT":
				$arSqlSearch[] = GetFilterQuery("MM.SUBJECT", $val);
				break;
			}
		}

		$is_filtered = false;
		$strSqlSearch = "";
		for($i = 0, $n = count($arSqlSearch); $i < $n; $i++)
		{
			if(strlen($arSqlSearch[$i])>0)
			{
				$strSqlSearch .= " AND  (".$arSqlSearch[$i].") ";
				$is_filtered = true;
			}
		}

		$arSqlOrder = Array();
		foreach($arOrder as $by=>$order)
		{
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc".(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":"");
			else
				$order = "asc".(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"");

			switch(strtoupper($by))
			{
			case "ID":
			case "MAILBOX_ID":
			case "FILTER_ID":
			case "MESSAGE_ID":
			case "DATE_INSERT":
			case "LOG_TYPE":
			case "STATUS_GOOD":
			case "MESSAGE":
				$arSqlOrder[] = " ML.".$by." ".$order." ";
			case "MESSAGE_SUBJECT":
				$arSqlOrder[] = " MM.SUBJECT ".$order." ";
			case "FILTER_NAME":
				$arSqlOrder[] = " MF.NAME ".$order." ";
			case "MAILBOX_NAME":
				$arSqlOrder[] = " MB.NAME ".$order." ";
			default:
				$arSqlOrder[] = " ML.ID ".$order." ";
			}
		}

		$strSqlOrder = "";
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);

		for ($i = 0, $n = count($arSqlOrder); $i < $n; $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= " WHERE 1=1 ".$strSqlSearch.$strSqlOrder;

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res = new _CMailLogDBRes($res);
		$res->is_filtered = $is_filtered;
		return $res;
	}

	public static function ConvertRow($arr_log)
	{
		switch($arr_log["LOG_TYPE"])
		{
		case "FILTER_OK":
			$arr_log["MESSAGE_TEXT"] = GetMessage("MAIL_CL_RULE_RUN")." \"[".$arr_log["FILTER_ID"]."] ".substr($arr_log["FILTER_NAME"], 0, 30).(strlen($arr_log["FILTER_NAME"])>30?"...":"")."\" ";
			if($arr_log["MESSAGE"]=="R")
				$arr_log["MESSAGE_TEXT"] .= GetMessage("MAIL_CL_WHEN_CONNECT");
			else
				$arr_log["MESSAGE_TEXT"] .= GetMessage("MAIL_CL_WHEN_MANUAL");
			break;
		case "NEW_MESSAGE":
			$arr_log["MESSAGE_TEXT"] = GetMessage("MAIL_CL_NEW_MESSAGE")." ".$arr_log["MESSAGE"];
			break;
		case "SPAM":
			if($arr_log["FILTER_ID"]>0)
				$arr_log["MESSAGE_TEXT"] = "&nbsp;&nbsp;".GetMessage("MAIL_CL_RULE_ACT_SPAM");
			else
				$arr_log["MESSAGE_TEXT"] = GetMessage("MAIL_CL_ACT_SPAM");
			break;
		case "NOTSPAM":
			if($arr_log["FILTER_ID"]>0)
				$arr_log["MESSAGE_TEXT"] = "&nbsp;&nbsp;".GetMessage("MAIL_CL_RULE_ACT_NOTSPAM");
			else
				$arr_log["MESSAGE_TEXT"] = GetMessage("MAIL_CL_ACT_NOTSPAM");
			break;
		case "DO_PHP":
			$arr_log["MESSAGE_TEXT"] = "&nbsp;&nbsp;".GetMessage("MAIL_CL_RULE_ACT_PHP");
			break;
		case "MESSAGE_DELETED":
			$arr_log["MESSAGE_TEXT"] = "&nbsp;&nbsp;".GetMessage("MAIL_CL_RULE_ACT_DEL");
			break;
		case "FILTER_STOP":
			$arr_log["MESSAGE_TEXT"] = "&nbsp;&nbsp;".GetMessage("MAIL_CL_RULE_ACT_CANC");
			break;
		default:
			$arr_log["MESSAGE_TEXT"] = $arr_log["MESSAGE"];
		}
		return $arr_log;
	}
}

class _CMailLogDBRes  extends CDBResult
{
	public static function _CMailLogDBRes($res)
	{
		parent::CDBResult($res);
	}

	public static function Fetch()
	{
		if($arr_log = parent::Fetch())
			return CMailLog::ConvertRow($arr_log);

		return false;
	}
}
?>
