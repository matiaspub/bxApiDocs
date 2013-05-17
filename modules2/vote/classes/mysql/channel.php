<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/classes/general/channel.php");

class CVoteChannel extends CAllVoteChannel
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CVoteChannel<br>File: ".__FILE__;
	}

	public static function GetDropDownList()
	{
		global $DB;
		$err_mess = (CVoteChannel::err_mess())."<br>Function: GetDropDownList<br>Line: ";
		$strSql = "
			SELECT
				ID as REFERENCE_ID,
				concat('[',ID,'] ',TITLE) as REFERENCE
			FROM b_vote_channel
			ORDER BY C_SORT
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}
}
?>