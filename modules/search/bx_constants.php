<?
/**
 * Кешировать в управляемом кеше выпадающий список тегов или нет. Если
 * 		равно false, то кеширование не производится. По умолчанию задается
 * 		значение 3600, что означает кеширование на 1 час.
 * 	</body>
 * </html>
 */
define('CACHED_b_search_tags', 3600);

/**
 * Максимально допустимая длина маски поиска тегов при которой будет
 * 		включено управляемое кеширование. По умолчанию равно 2, что означает
 * 		создание в пределе около 1000 файлов (все 2-х буквенные комбинации).
 * 	</body>
 * </html>
 */
define('CACHED_b_search_tags_len', 2);

/**
 * START_EXEC_TIME
 */
define('START_EXEC_TIME', getmicrotime());

/**
 * BX_SEARCH_VERSION
 */
define('BX_SEARCH_VERSION', 1);

/**
 * ADMIN_MODULE_NAME
 */
define('ADMIN_MODULE_NAME', "search");

/**
 * ADMIN_MODULE_ICON
 */
define('ADMIN_MODULE_ICON', "<img src=\"/bitrix/images/search/search.gif\" width=\"48\" height=\"48\" border=\"0\" alt=\"".GetMessage("SEARCH_PROLOG_ALT")."\" title=\"".GetMessage("SEARCH_PROLOG_ALT")."\">");


?>