<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/classes/general/question.php");

class CVoteQuestion extends CAllVoteQuestion
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CVoteQuestion<br>File: ".__FILE__;
	}
}
?>