<?
/**
 * DLSERVER
 */
define('DLSERVER', $arParams['DLSERVER']);

/**
 * DLPORT
 */
define('DLPORT', $arParams['DLPORT']);

/**
 * DLPATH
 */
define('DLPATH', $arParams['DLPATH']);

/**
 * DLMETHOD
 */
define('DLMETHOD', $arParams['DLMETHOD']);

/**
 * DLZIPFILE
 */
define('DLZIPFILE', $arParams["DLZIPFILE"]);

/**
 * ZIP_STEP_LENGTH
 */
define('ZIP_STEP_LENGTH', $step_length);

/**
 * LOC_STEP_LENGTH
 */
define('LOC_STEP_LENGTH', $step_length);

/**
 * COLUMNS_COUNT_FOR_SIMPLE_TEMPLATE
 */
define('COLUMNS_COUNT_FOR_SIMPLE_TEMPLATE', 3);

/**
 * PATH_TO_MOBILE_REPORTS
 */
define('PATH_TO_MOBILE_REPORTS', '/bitrix/admin/mobile/sale_reports_view.php');

/**
 * FPDF_FONTPATH
 */
define('FPDF_FONTPATH', $_SERVER["DOCUMENT_ROOT"]."/bitrix/fonts/");

/**
 * tFPDF_VERSION
 */
define('tFPDF_VERSION', '1.24');

/**
 * _TTF_MAC_HEADER
 */
define('_TTF_MAC_HEADER', false);

/**
 * GF_WORDS
 */
define('GF_WORDS', (1 << 0));

/**
 * GF_SCALE
 */
define('GF_SCALE', (1 << 3));

/**
 * GF_MORE
 */
define('GF_MORE', (1 << 5));

/**
 * GF_XYSCALE
 */
define('GF_XYSCALE', (1 << 6));

/**
 * GF_TWOBYTWO
 */
define('GF_TWOBYTWO', (1 << 7));

/**
 * SALE_TIME_LOCK_USER
 */
define('SALE_TIME_LOCK_USER', 600);

/**
 * SALE_DEBUG
 */
define('SALE_DEBUG', false);

/**
 * SALE_PROC_REC_NUM
 */
define('SALE_PROC_REC_NUM', 3);

/**
 * SALE_PROC_REC_ATTEMPTS
 */
define('SALE_PROC_REC_ATTEMPTS', 3);

/**
 * SALE_PROC_REC_TIME
 */
define('SALE_PROC_REC_TIME', 43200);

/**
 * SALE_PROC_REC_FREQUENCY
 */
define('SALE_PROC_REC_FREQUENCY', 7200);

/**
 * SALE_REPORT_OWNER_ID
 */
define('SALE_REPORT_OWNER_ID', 'sale');

/**
 * CACHED_b_sale_order
 */
define('CACHED_b_sale_order', 3600*24);

/**
 * SALE_VALUE_PRECISION
 */
define('SALE_VALUE_PRECISION', 2);

/**
 * SALE_WEIGHT_PRECISION
 */
define('SALE_WEIGHT_PRECISION', 3);

/**
 * BX_SALE_MENU_CATALOG_CLEAR
 */
define('BX_SALE_MENU_CATALOG_CLEAR', 'Y');

/**
 * ADMIN_MODULE_NAME
 */
define('ADMIN_MODULE_NAME', "sale");

/**
 * ADMIN_MODULE_ICON
 */
define('ADMIN_MODULE_ICON', "<a href=\"/bitrix/admin/sale_order.php?lang=".LANG."\"><img src=\"/bitrix/images/sale/sale.gif\" width=\"48\" height=\"48\" border=\"0\" alt=\"".GetMessage("SALE_ICON_TITLE")."\" title=\"".GetMessage("SALE_ICON_TITLE")."\"></a>");


?>