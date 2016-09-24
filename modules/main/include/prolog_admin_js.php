<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// define("START_EXEC_PROLOG_AFTER_1", microtime());
$GLOBALS["BX_STATE"] = "PA";

if(!$GLOBALS['USER']->IsAuthorized())
{
	die();
}

// define("START_EXEC_PROLOG_AFTER_2", microtime());
$GLOBALS["BX_STATE"] = "WA";
?>