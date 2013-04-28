<?
/* @var $APPLICATION CMain */
/* @var $GLOBALS['APPLICATION'] CMain */
/* @var $GLOBALS["APPLICATION"] CMain */
if (! isset($APPLICATION)) {
	$APPLICATION = $GLOBALS['APPLICATION'] = $GLOBALS["APPLICATION"] = new CMain();
}

/* @var $USER CUser */
/* @var $GLOBALS['USER'] CUser */
/* @var $GLOBALS["USER"] CUser */
if (! isset($USER)) {
	$USER = $GLOBALS['USER'] = $GLOBALS["USER"] = new CUser();
}

/* @var $USER_FIELD_MANAGER CUserTypeManager */
/* @var $GLOBALS['USER_FIELD_MANAGER'] CUserTypeManager */
/* @var $GLOBALS["USER_FIELD_MANAGER"] CUserTypeManager */
if (! isset($USER_FIELD_MANAGER)) {
	/**
	 * Эта переменная содержит экземпляр класса через API которого
	 * и происходит работа с пользовательскими свойствами.
	 * @global CUserTypeManager $GLOBALS['USER_FIELD_MANAGER']
	 * @name $USER_FIELD_MANAGER
	 */
	$USER_FIELD_MANAGER = $GLOBALS['USER_FIELD_MANAGER'] = $GLOBALS["USER_FIELD_MANAGER"] = new CUserTypeManager;
}

/* @var $DB CDatabase */
$DB = new CDatabase();
?>