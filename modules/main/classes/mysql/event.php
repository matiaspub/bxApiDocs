<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/event.php");


/**
 * <b>CEvent</b> - класс для работы с почтовыми событиями.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cevent/index.php
 * @author Bitrix
 */
class CEvent extends CAllEvent
{

}

///////////////////////////////////////////////////////////////////
// Class of mail templates
///////////////////////////////////////////////////////////////////


/**
 * <b>CEventMessage</b> - класс предназначеный для работы с почтовыми шаблонами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/index.php
 * @author Bitrix
 */
class CEventMessage extends CAllEventMessage
{

}
?>