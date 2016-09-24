<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/classes/general/user.php");

class CVoteUser extends CAllVoteUser
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CVoteUser<br>File: ".__FILE__;
	}
}
?>