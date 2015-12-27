<?
/* @var $APPLICATION CMain */
/* @var $GLOBALS['APPLICATION'] CMain */
/* @var $GLOBALS["APPLICATION"] CMain */
$APPLICATION = $GLOBALS['APPLICATION'] = $GLOBALS["APPLICATION"] = new CMain();

/* @var $USER CUser */
/* @var $GLOBALS['USER'] CUser */
/* @var $GLOBALS["USER"] CUser */
$USER = $GLOBALS['USER'] = $GLOBALS["USER"] = new CUser();

/* @var $USER_FIELD_MANAGER CUserTypeManager */
/* @var $GLOBALS['USER_FIELD_MANAGER'] CUserTypeManager */
/* @var $GLOBALS["USER_FIELD_MANAGER"] CUserTypeManager */
$USER_FIELD_MANAGER = $GLOBALS['USER_FIELD_MANAGER'] = $GLOBALS["USER_FIELD_MANAGER"] = new CUserTypeManager;

/* @var $CACHE_MANAGER CCacheManager */
/* @var $GLOBALS['CACHE_MANAGER'] CCacheManager */
/* @var $GLOBALS["CACHE_MANAGER"] CCacheManager */
$CACHE_MANAGER = $GLOBALS['CACHE_MANAGER'] = $GLOBALS["CACHE_MANAGER"] = new CCacheManager;

/* @var $DB CDatabase */
/* @var $GLOBALS['DB'] CDatabase */
/* @var $GLOBALS["DB"] CDatabase */
$DB = $GLOBALS['DB'] = $GLOBALS["DB"] = new CDatabase();
?>