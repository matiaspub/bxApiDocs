<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
global $REL_FPATH;
$REL_FPATH = COption::GetOptionString("forum", "REL_FPATH", "");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/forum.php");

class CForum extends CAllForum
{

}
?>