<?
/**
 * Идентификатор текущего сайта.
 */
define('SITE_ID', $site_id);

/**
 * Поле "Папка сайта" в настройках сайта. Как правило используется в случае организации многосайтовости <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=286" target="_blank">на одном домене</a>.
 */
define('SITE_DIR', $arLang["DIR"]);

/**
 * Поле "URL сервера" в настройках текущего сайта.
 */
define('SITE_SERVER_NAME', $arLang["SERVER_NAME"]);

/**
 * URL от корня сайта до папки текущего шаблона.
 */
define('SITE_TEMPLATE_PATH', BX_PERSONAL_ROOT.'/templates/'.SITE_TEMPLATE_ID);

/**
 * Поле "Кодировка" в настройках текущего сайта.
 */
define('SITE_CHARSET', $arLang["CHARSET"]);

/**
 * Для публичной части, 		в данной константе 		хранится формат даты из настроек текущего сайта. 		Для административной 		части - формат даты текущего языка.
 */
define('FORMAT_DATE', $arLang["FORMAT_DATE"]);

/**
 * Для публичной части, в данной константе хранится формат времени из настроек текущего сайта.	Для административной части - формат времени текущего языка.
 */
define('FORMAT_DATETIME', $arLang["FORMAT_DATETIME"]);

/**
 * Если это публичная часть, то в данной константе храниться поле "Язык" из настроек текущего сайта, если административная часть, то в данной константе храниться идентификатор текущего языка.
 */
define('LANGUAGE_ID', 'en');

/**
 * В данной константе содержится значение кодировки, указанной в секции 	<i>Параметры</i> формы настроек текущего сайта.
 */
define('LANG_CHARSET', $arLang["CHARSET"]);

/**
 * Идентификатор текущего шаблона сайта.
 */
define('SITE_TEMPLATE_ID', $siteTemplateId);

/**
 * Содержит время начала работы страницы в формате возвращаемом функцией <a href="/api_help/main/functions/date/getmicrotime.php">getmicrotime</a>.
 */
define('START_EXEC_TIME', microtime(true));

/**
 * Если подключена служебная часть пролога, то данная константа будет инициализирована значением "true". Как правило эту константу используют во включаемых файлах в целях безопасности, когда необходимо убедиться, что пролог подключен и все необходимые права проверены.
 */
define('B_PROLOG_INCLUDED', true);

/**
 * Текущая версия главного модуля.
 */
define('SM_VERSION', "12.5.3");

/**
 * Дата выпуска текущей версии главного модуля.
 */
define('SM_VERSION_DATE', "2013-04-17 08:09:00");

/**
 * Если необходимо подключать пролог административной части, то значение данной константы - "true".
 */
define('ADMIN_SECTION', true);

/**
 * <p>Данную константу необходимо инициализировать до пролога в файлах-обработчиках 404 ошибки (страница не найдена). Подобные файлы-обработчики задаются в настройках веб-сервера.</p>
 *        
 *         <p>Инициализация этой константы позволяет в стандартных компонентах авторизации, регистрации, высылки забытого пароля, смены пароля поменять страницу на которую будет осуществляться сабмит соответствующей формы. Этой страницей по умолчанию является - текущая страница, если же константа инициализирована, то это будет - <b>/SITE_DIR/auth.php</b>.</p>
 *        
 *         <p>Необходимость инициализации этой константы связана с тем, что на несуществующие страницы отослать данные методом POST нельзя, а именно с этим методом и работают вышеперечисленные компоненты. Поэтому если файл текущей страницы физически не существует на сервере, то без этой константы компоненты работать не будут.</p>
 *        	Пример: 
 *         <pre>define("AUTH_404", "Y");</pre>
 */
define('AUTH_404', "Y");

/**
 * Данная константа используется как правило в административных скриптах, для хранения имени файла контекстно-зависимой помощи, в случае если это имя отличается от имени данного скрипта. Ссылка на контекстно-зависимую помощь выводится в виде иконки на административной панели. 	 
 *         <br>
 *        Пример: 
 *         <pre>define("HELP_FILE",
 *        "my_admin_script.php");</pre>
 */
define('HELP_FILE', "updates/index.php");

/**
 * Если инициализировать данную константу значением "true" до подключения пролога, то будет проведена <a href="/api_help/main/reference/cuser/isauthorized.php">проверка</a> на авторизованность пользователя. Если пользователь не авторизован, то ему будет <a href="/api_help/main/reference/cmain/authform.php">предложена форма авторизации</a>. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("NEED_AUTH", true);</pre>
 */
define('NEED_AUTH', true);

/**
 * Хранит E-Mail адрес (или группу адресов разделенных запятой), используемый функцией <a href="/api_help/main/functions/debug/senderror.php">SendError</a> для отправки сообщений об ошибках. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("ERROR_EMAIL", 
 *        "admin@site.ru, support@site.ru");</pre>
 */
define('ERROR_EMAIL', );

/**
 * Хранит абсолютный путь к log-файлу, используемого функцией <a href="/api_help/main/functions/debug/addmessage2log.php">AddMessage2Log</a> для записи ошибок или каких-либо сообщений. 	 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("LOG_FILENAME", 
 *        $_SERVER["DOCUMENT_ROOT"].
 *            "/log.txt");</pre>
 */
define('LOG_FILENAME', );

/**
 * Как правило данная константа используется в редакции "Веб-Аналитика". Если ее не инициализировать, то в публичной части будет отсылаться HTTP заголовок: 
 *         <br>
 *        	Content-Type: text/html; charset=<b>SITE_CHARSET</b> 
 *         <br><br>
 *        Пример: 
 *         <pre>define("STATISTIC_ONLY", true);</pre>
 */
define('STATISTIC_ONLY', true);

/**
 * Если инициализировать данную константу каким либо значением, то это запретит сбор статистики на данной странице. 	 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("NO_KEEP_STATISTIC", true);</pre>
 */
define('NO_KEEP_STATISTIC', true);

/**
 * Константа предназначена для отключения автоматического сбора статистики, 	реализованного как вызов функции <code><a href="/api_help/statistic/classes/cstatistics/keep.php">CStatistics::Keep</a></code> в качестве обработчика события <a href="/api_help/main/events/onbeforeprolog.php">OnBeforeProlog</a>. Константу необходимо инициализировать до подключения пролога. Затем, при необходимости, можно использовать "ручной" сбор статистики, вызвав функцию 	<code>CStatistics::Keep</code> (с первым параметром, равным true). 
 *         <br><br>
 *        Пример: 
 *         <pre>&lt;?
 * // отключим автоматический
 * // сбор статистики
 * define("STOP_STATISTICS", true);
 * require($_SERVER["DOCUMENT_ROOT"].
 *         "/bitrix/header.php");
 * 
 * // включим сбор статистики
 * CStatistics::Keep(true);
 * 
 * ...</pre>
 */
define('STOP_STATISTICS', true);

/**
 * Инициализация этой константы каким-либо значением 	приведет к запрету следующих действий модуля "Статистика", 	выполняемых ежедневно при помощи технологии <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436" target="_blank">агентов</a>: 
 *         <ul>
<li>перевод на новый день; </li>
 *          
 *           <li>очистка устаревших данных статистики; </li>
 *          
 *           <li>отсылка ежедневного статистического отчета. </li>
 *          </ul>
 *        Пример: 
 *         <pre>define("NO_AGENT_STATISTIC", true);</pre>
 */
define('NO_AGENT_STATISTIC', true);

/**
 * При установке в <b>true</b> отключает выполнение всех агентов 
 *         <p>Пример: </p>
 *        
 *         <pre>define("NO_AGENT_CHECK", true);</pre>
 */
define('NO_AGENT_CHECK', true);

/**
 * Если инициализировать данную константу значением "true" до подключения пролога, то это отключит проверку прав на доступ <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2819" target="_blank">к файлам и каталогам</a>. 	 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("NOT_CHECK_PERMISSIONS", true);</pre>
 */
define('NOT_CHECK_PERMISSIONS', true);

/**
 * Если на странице задана константа ONLY_EMAIL и email из настроек почтового шаблона с ее значением не совпадает, то письмо не отсылать. То есть отсылка письма будет происходить только в том случае если значение данной константы будет соответствовать адресу отправителя в настройках шаблона. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("ONLY_EMAIL", "admin@site.ru");</pre>
 */
define('ONLY_EMAIL', );

/**
 * Если данная константа инициализирована значением "true", то <a href="/api_help/main/reference/cagent/checkagents.php">функция проверки агентов на запуск</a> будет отбирать только те агенты для которых не критично количество их запусков (т.е. при <a href="/api_help/main/reference/cagent/addagent.php">добавлении</a> этого агента параметр <i>period</i>=N). Как правило данная константа используется для организации запуска агентов на cron'е. 	 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("BX_CRONTAB", true);</pre>
 */
define('BX_CRONTAB', true);

/**
 * Unix-права для вновь создаваемых файлов. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("BX_FILE_PERMISSIONS", 0755);</pre>
 */
define('BX_FILE_PERMISSIONS', 0600);

/**
 * Unix-права для вновь создаваемых каталогов. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("BX_DIR_PERMISSIONS", 0755);</pre>
 */
define('BX_DIR_PERMISSIONS', 0700);

/**
 * Инициализация данной константы значением "true" позволит отключить все модули системы за исключением главного и модуля "<a href="../../../../../fileman/help/ru/index.php.html">Управление структурой</a>". 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("SM_SAFE_MODE", true);</pre>
 */
define('SM_SAFE_MODE', );

/**
 * Данная константа используется в функции <a href="/api_help/main/functions/file/getdirindex.php">GetDirIndex</a> для определения индексного файла каталога. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("DIRECTORY_INDEX", "
 *     index.php 
 *     index.html 
 *     index.htm 
 *     index.phtml 
 *     default.html 
 *     index.php3
 * ");</pre>
 */
define('DIRECTORY_INDEX', );

/**
 * Значение данной константы содержит тип таблиц создаваемый в MySQL по умолчанию: "MyISAM" или "InnoDB". 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("MYSQL_TABLE_TYPE", "InnoDB");</pre>
 */
define('MYSQL_TABLE_TYPE', );

/**
 * Если данная константа инициализирована значением "true", то будет создаваться постоянное соединение с базой. 	 
 *         <br><br>
 *        Пример: 
 *         <pre>define("DBPersistent", true);</pre>
 */
define('DBPersistent', true);

/**
 * Может принимать значение true/false. Константа регулирует значение по умолчанию для параметра get_index_page функций GetPagePath(), CMain::GetCurPage(), CMain::GetCurPageParam(). Параметр get_index_page указывает, нужно ли для индексной страницы раздела возвращать путь, заканчивающийся на "index.php". Если значение параметра равно true, то возвращается путь с "index.php", иначе - путь, заканчивающийся на "/". Параметр имеет значение, <i>обратное</i> значению константы.
 */
define('BX_DISABLE_INDEX_PAGE', );

/**
 * Может принимать значение true/false. Если инициализировать данную константу каким либо значением,то она отключает/включает сбор бектрейсов при включенной отладке.
 */
define('BX_NO_SQL_BACKTRACE', );

/**
 * BX_ROOT
 */
define('BX_ROOT', "/bitrix");

/**
 * BX_PERSONAL_ROOT
 */
define('BX_PERSONAL_ROOT', BX_ROOT);

/**
 * HTML_PAGES_FILE
 */
define('HTML_PAGES_FILE', $PAGES_FILE);

/**
 * BX_MEMCACHE_CONNECTED
 */
define('BX_MEMCACHE_CONNECTED', true);

/**
 * PATH2CONVERT_TABLES
 */
define('PATH2CONVERT_TABLES', $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/cvtables/");

/**
 * PUBLIC_AJAX_MODE
 */
define('PUBLIC_AJAX_MODE', true);

/**
 * CRYPT_MODE_ECB
 */
define('CRYPT_MODE_ECB', 0);

/**
 * CRYPT_MODE_CBC
 */
define('CRYPT_MODE_CBC', 1);

/**
 * BX_B_FILE_DIALOG_SCRIPT_LOADED
 */
define('BX_B_FILE_DIALOG_SCRIPT_LOADED', true);

/**
 * BX_SPREAD_SITES
 */
define('BX_SPREAD_SITES', 2);

/**
 * BX_SPREAD_DOMAIN
 */
define('BX_SPREAD_DOMAIN', 4);

/**
 * BX_RESIZE_IMAGE_PROPORTIONAL_ALT
 */
define('BX_RESIZE_IMAGE_PROPORTIONAL_ALT', 0);

/**
 * BX_RESIZE_IMAGE_PROPORTIONAL
 */
define('BX_RESIZE_IMAGE_PROPORTIONAL', 1);

/**
 * BX_RESIZE_IMAGE_EXACT
 */
define('BX_RESIZE_IMAGE_EXACT', 2);

/**
 * BX_AUTH_FORM
 */
define('BX_AUTH_FORM', true);

/**
 * BX_BUFFER_USED
 */
define('BX_BUFFER_USED', true);

/**
 * BX_FORK_AGENTS_AND_EVENTS_FUNCTION_STARTED
 */
define('BX_FORK_AGENTS_AND_EVENTS_FUNCTION_STARTED', true);

/**
 * MODULE_NOT_FOUND
 */
define('MODULE_NOT_FOUND', 0);

/**
 * MODULE_INSTALLED
 */
define('MODULE_INSTALLED', 1);

/**
 * MODULE_DEMO
 */
define('MODULE_DEMO', 2);

/**
 * MODULE_DEMO_EXPIRED
 */
define('MODULE_DEMO_EXPIRED', 3);

/**
 * NO_BITRIX_AUTOLOAD
 */
define('NO_BITRIX_AUTOLOAD', true);

/**
 * US_SHARED_KERNEL_PATH
 */
define('US_SHARED_KERNEL_PATH', "/bitrix");

/**
 * DEFAULT_UPDATE_SERVER
 */
define('DEFAULT_UPDATE_SERVER', "mysql.smn");

/**
 * US_CALL_TYPE
 */
define('US_CALL_TYPE', "ALL");

/**
 * US_BASE_MODULE
 */
define('US_BASE_MODULE', "main");

/**
 * UPDATE_SYSTEM_VERSION
 */
define('UPDATE_SYSTEM_VERSION', "11.0.12");

/**
 * __CUpdateOutputScript
 */
define('__CUpdateOutputScript', true);

/**
 * T_INCLUDE_RESULT_MODIFIER
 */
define('T_INCLUDE_RESULT_MODIFIER', 10001);

/**
 * T_INCLUDE_COMPONENTTEMPLATE
 */
define('T_INCLUDE_COMPONENTTEMPLATE', 10002);

/**
 * T_INCLUDE_COMPONENT
 */
define('T_INCLUDE_COMPONENT', 10003);

/**
 * T_INCLUDE_END
 */
define('T_INCLUDE_END', 10004);

/**
 * BX_WIZARD_WELCOME_ID
 */
define('BX_WIZARD_WELCOME_ID', "__welcome");

/**
 * BX_WIZARD_LICENSE_ID
 */
define('BX_WIZARD_LICENSE_ID', "__license");

/**
 * BX_WIZARD_SELECT_SITE_ID
 */
define('BX_WIZARD_SELECT_SITE_ID', "__select_site");

/**
 * BX_WIZARD_SELECT_GROUP_ID
 */
define('BX_WIZARD_SELECT_GROUP_ID', "__select_group");

/**
 * BX_WIZARD_SELECT_TEMPLATE_ID
 */
define('BX_WIZARD_SELECT_TEMPLATE_ID', "__select_template");

/**
 * BX_WIZARD_SELECT_SERVICE_ID
 */
define('BX_WIZARD_SELECT_SERVICE_ID', "__select_service");

/**
 * BX_WIZARD_SELECT_STRUCTURE_ID
 */
define('BX_WIZARD_SELECT_STRUCTURE_ID', "__select_structure");

/**
 * BX_WIZARD_START_INSTALL_ID
 */
define('BX_WIZARD_START_INSTALL_ID', "__start_install");

/**
 * BX_WIZARD_INSTALL_SITE_ID
 */
define('BX_WIZARD_INSTALL_SITE_ID', "__install_site");

/**
 * BX_WIZARD_INSTALL_TEMPLATE_ID
 */
define('BX_WIZARD_INSTALL_TEMPLATE_ID', "__install_template");

/**
 * BX_WIZARD_INSTALL_SERVICE_ID
 */
define('BX_WIZARD_INSTALL_SERVICE_ID', "__install_service");

/**
 * BX_WIZARD_INSTALL_STRUCTURE_ID
 */
define('BX_WIZARD_INSTALL_STRUCTURE_ID', "__install_structure");

/**
 * BX_WIZARD_FINISH_ID
 */
define('BX_WIZARD_FINISH_ID', "__finish");

/**
 * BX_WIZARD_CANCEL_ID
 */
define('BX_WIZARD_CANCEL_ID', "__install_cancel");

/**
 * ZIP_START_TIME
 */
define('ZIP_START_TIME', microtime(true));

/**
 * START_EXEC_EPILOG_AFTER_1
 */
define('START_EXEC_EPILOG_AFTER_1', microtime());

/**
 * START_EXEC_EVENTS_1
 */
define('START_EXEC_EVENTS_1', microtime());

/**
 * START_EXEC_EVENTS_2
 */
define('START_EXEC_EVENTS_2', microtime());

/**
 * START_EXEC_EPILOG_BEFORE_1
 */
define('START_EXEC_EPILOG_BEFORE_1', microtime());

/**
 * ADMIN_AJAX_MODE
 */
define('ADMIN_AJAX_MODE', true);

/**
 * BITRIX_STATIC_PAGES
 */
define('BITRIX_STATIC_PAGES', true);

/**
 * START_EXEC_PROLOG_AFTER_1
 */
define('START_EXEC_PROLOG_AFTER_1', microtime());

/**
 * START_EXEC_PROLOG_AFTER_2
 */
define('START_EXEC_PROLOG_AFTER_2', microtime());

/**
 * ADMIN_SECTION_LOAD_AUTH
 */
define('ADMIN_SECTION_LOAD_AUTH', 1);

/**
 * START_EXEC_PROLOG_BEFORE_1
 */
define('START_EXEC_PROLOG_BEFORE_1', microtime());

/**
 * BX_PUBLIC_MODE
 */
define('BX_PUBLIC_MODE', true);

/**
 * BX_URLREWRITE
 */
define('BX_URLREWRITE', true);

/**
 * POST_FORM_ACTION_URI
 */
define('POST_FORM_ACTION_URI', htmlspecialcharsbx($_SERVER["REQUEST_URI"]));

/**
 * BX_CHECK_SHORT_URI
 */
define('BX_CHECK_SHORT_URI', true);

/**
 * ERROR_404
 */
define('ERROR_404', "Y");

/**
 * BX_ADMIN_SECTION_404
 */
define('BX_ADMIN_SECTION_404', 'Y');

/**
 * ADMIN_THEMES_PATH
 */
define('ADMIN_THEMES_PATH', "/bitrix/themes");

/**
 * DisableEventsCheck
 */
define('DisableEventsCheck', true);

/**
 * BX_SEARCH_ADMIN
 */
define('BX_SEARCH_ADMIN', true);

/**
 * ADMIN_THEME_ID
 */
define('ADMIN_THEME_ID', CAdminTheme::GetCurrentTheme());

/**
 * BX_COMP_MANAGED_CACHE
 */
define('BX_COMP_MANAGED_CACHE', true);

/**
 * BX_CRONTAB_SUPPORT
 */
define('BX_CRONTAB_SUPPORT', defined("BX_CRONTAB"));

/**
 * BX_STARTED
 */
define('BX_STARTED', true);

/**
 * START_EXEC_AGENTS_1
 */
define('START_EXEC_AGENTS_1', microtime());

/**
 * START_EXEC_AGENTS_2
 */
define('START_EXEC_AGENTS_2', microtime());

/**
 * LANG
 */
define('LANG', "ru");

/**
 * LANG_DIR
 */
define('LANG_DIR', $arLang["DIR"]);

/**
 * LANG_ADMIN_LID
 */
define('LANG_ADMIN_LID', $arLang["LANGUAGE_ID"]);

/**
 * LICENSE_KEY
 */
define('LICENSE_KEY', $dispatcher->getLicenseKey());

/**
 * BX_UTF_PCRE_MODIFIER
 */
define('BX_UTF_PCRE_MODIFIER', '');

/**
 * CACHED_b_lang
 */
define('CACHED_b_lang', 3600);

/**
 * CACHED_b_option
 */
define('CACHED_b_option', 3600);

/**
 * CACHED_b_lang_domain
 */
define('CACHED_b_lang_domain', 3600);

/**
 * CACHED_b_site_template
 */
define('CACHED_b_site_template', 3600);

/**
 * CACHED_b_event
 */
define('CACHED_b_event', 3600);

/**
 * CACHED_b_agent
 */
define('CACHED_b_agent', 3660);

/**
 * CACHED_menu
 */
define('CACHED_menu', 3600);

/**
 * CACHED_b_file
 */
define('CACHED_b_file', false);

/**
 * CACHED_b_file_bucket_size
 */
define('CACHED_b_file_bucket_size', 100);

/**
 * CACHED_b_user_field
 */
define('CACHED_b_user_field', 3600);

/**
 * CACHED_b_user_field_enum
 */
define('CACHED_b_user_field_enum', 3600);

/**
 * CACHED_b_task
 */
define('CACHED_b_task', 3600);

/**
 * CACHED_b_task_operation
 */
define('CACHED_b_task_operation', 3600);

/**
 * CACHED_b_rating
 */
define('CACHED_b_rating', 3600);

/**
 * ADMIN_MODULE_NAME
 */
define('ADMIN_MODULE_NAME', 'main');

/**
 * ADMIN_MODULE_ICON
 */
define('ADMIN_MODULE_ICON', '<a href="'.BX_ROOT.'/admin/index.php?lang='.LANG.'"><img src="'.BX_ROOT.'/images/main/user.gif" width="48" height="48" border="0" alt="'.GetMessage('MAIN_MODULE_TITLE').'" title="'.GetMessage('MAIN_MODULE_TITLE').'"></a>');

/**
 * BX_SKIP_SESSION_TERMINATE_TIME
 */
define('BX_SKIP_SESSION_TERMINATE_TIME', true);

/**
 * NOT_CHECK_FILE_PERMISSIONS
 */
define('NOT_CHECK_FILE_PERMISSIONS', true);

/**
 * START_TIME
 */
define('START_TIME', time());

/**
 * LOCK_FILE
 */
define('LOCK_FILE', $_SERVER['DOCUMENT_ROOT'].'/bitrix/backup/auto_lock');

/**
 * DOCUMENT_ROOT
 */
define('DOCUMENT_ROOT', rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']),'/'));

/**
 * DOCUMENT_ROOT_SITE
 */
define('DOCUMENT_ROOT_SITE', $DOCUMENT_ROOT_SITE);

/**
 * BX_PUBLIC_TOOLS
 */
define('BX_PUBLIC_TOOLS', true);

/**
 * BX_NO_ACCELERATOR_RESET
 */
define('BX_NO_ACCELERATOR_RESET', true);


?>