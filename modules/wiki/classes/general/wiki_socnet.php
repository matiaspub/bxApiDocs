<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CWikiSocnet</b> - Класс интеграции с модулем «Социальная сеть». 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisocnet/index.php
 * @author Bitrix
 */
class CWikiSocnet
{
	static public $bActive = false;

	static public $bInit = false;

	static public $iCatId = 0;
	static public $iCatLeftBorder = 0;
	static public $iCatRightBorder = 0;

	static public $iSocNetId = 0;

	
	/**
	* <p>Метод инициализирует интеграцию. Статичный метод.</p>
	*
	*
	* @param int $SOCNET_GROUP_ID  Идентификатор рабочей группы соц. сети
	*
	* @param int $IBLOCK_ID  Идентификатор инфо.блока
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Инициализируем интеграцию<br>$SOCNET_GROUP_ID = 14;<br>$IBLOCK_ID = 3;<br><br>if (!CWikiSocnet::Init($SOCNET_GROUP_ID, $IBLOCK_ID))<br>	echo 'Ошибка. Не удалось инициализировать интеграцию.';<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisocnet/Init.php
	* @author Bitrix
	*/
	static function Init($SOCNET_GROUP_ID, $IBLOCK_ID)
	{
		if (self::$bInit)
			return self::$bInit;

		if (!self::IsEnabledSocnet())
			return false;

		self::$iSocNetId = intVal($SOCNET_GROUP_ID);

		// detect work group
		$arFilter = Array();
		$arFilter['IBLOCK_ID'] = $IBLOCK_ID;
		$arFilter['SOCNET_GROUP_ID'] = self::$iSocNetId;
		$arFilter['CHECK_PERMISSIONS'] = 'N';
		$rsSection = CIBlockSection::GetList(Array($by=>$order), $arFilter, true);
		$obSection = $rsSection->GetNextElement();

		if ($obSection !== false)
		{
			$arResult = $obSection->GetFields();
			self::$iCatId = $arResult['ID'];
			self::$iCatLeftBorder = $arResult['LEFT_MARGIN'];
			self::$iCatRightBorder = $arResult['RIGHT_MARGIN'];
		}
		else
		{
			$arWorkGroup = CSocNetGroup::GetById(self::$iSocNetId);

			$arFields = Array(
				'ACTIVE' => 'Y',
				'IBLOCK_ID' => $IBLOCK_ID,
				'SOCNET_GROUP_ID' => self::$iSocNetId,
				'CHECK_PERMISSIONS' => 'N',
				'NAME' => $arWorkGroup['NAME']
			);
			$CIB_S = new CIBlockSection();
			self::$iCatId = $CIB_S->Add($arFields);
			if (self::$iCatId == false)
			{
				self::$bInit = false;
				return false;
			}
			$rsSection = CIBlockSection::GetList(Array($by=>$order), $arFilter, true);
			$obSection = $rsSection->GetNextElement();
			if ($obSection == false)
			{
				self::$bInit = false;
				return false;
			}
			$arResult = $obSection->GetFields();
			self::$iCatLeftBorder = $arResult['LEFT_MARGIN'];
			self::$iCatRightBorder = $arResult['RIGHT_MARGIN'];
		}

		self::$bInit = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, self::$iSocNetId, 'wiki');
		return self::$bInit;
	}

	
	/**
	* <p>Метод проверяет включена ли интеграция. Статичный метод.</p> <br><br>
	*
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisocnet/isenabledsocnet.php
	* @author Bitrix
	*/
	static function IsEnabledSocnet()
	{
		if (self::$bActive)
			return self::$bActive;

		$bActive = false;
		$rsEvents = GetModuleEvents('socialnetwork', 'OnFillSocNetFeaturesList');
		while($arEvent = $rsEvents->Fetch())
		{
			if($arEvent['TO_MODULE_ID'] == 'wiki'
				&& $arEvent['TO_CLASS'] == 'CWikiSocnet')
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	
	/**
	* <p>Метод проверяет находится ли модуль в режиме интеграции. Статичный метод.</p> <br><br>
	*
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisocnet/IsSocNet.php
	* @author Bitrix
	*/
	static function IsSocNet()
	{
		return self::$bInit;
	}

	
	/**
	* <p>Метод инициализирует интеграцию. Статичный метод.</p>
	*
	*
	* @param bool $bActive  true – включает интеграцию, false – отключает интеграцию
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisocnet/enablesocnet.php
	* @author Bitrix
	*/
	static function EnableSocnet($bActive = false)
	{
		if($bActive)
		{
			if(!self::IsEnabledSocnet())
			{
				RegisterModuleDependences('socialnetwork', 'OnFillSocNetFeaturesList', 'wiki', 'CWikiSocnet', 'OnFillSocNetFeaturesList');
				RegisterModuleDependences('socialnetwork', 'OnFillSocNetMenu', 'wiki', 'CWikiSocnet', 'OnFillSocNetMenu');
				RegisterModuleDependences('socialnetwork', 'OnParseSocNetComponentPath', 'wiki', 'CWikiSocnet', 'OnParseSocNetComponentPath');
				RegisterModuleDependences('socialnetwork', 'OnInitSocNetComponentVariables', 'wiki', 'CWikiSocnet', 'OnInitSocNetComponentVariables');
			}
		}
		else
		{
			if(self::IsEnabledSocnet())
			{
				UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetFeaturesList', 'wiki', 'CWikiSocnet', 'OnFillSocNetFeaturesList');
				UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetMenu', 'wiki', 'CWikiSocnet', 'OnFillSocNetMenu');
				UnRegisterModuleDependences('socialnetwork', 'OnParseSocNetComponentPath', 'wiki', 'CWikiSocnet', 'OnParseSocNetComponentPath');
				UnRegisterModuleDependences('socialnetwork', 'OnInitSocNetComponentVariables', 'wiki', 'CWikiSocnet', 'OnInitSocNetComponentVariables');
			}
		}
	}

	static function OnFillSocNetFeaturesList(&$arSocNetFeaturesSettings)
	{
		$arSocNetFeaturesSettings['wiki'] = array(
			'allowed' => array(SONET_ENTITY_GROUP),
			'title' => GetMessage('WIKI_SOCNET_TAB'),
			'operations' => array(
				'view' => array(SONET_ENTITY_GROUP => SONET_ROLES_USER),
				'write' => array(SONET_ENTITY_GROUP => SONET_ROLES_USER),
				'delete' => array(SONET_ENTITY_GROUP => SONET_ROLES_MODERATOR)
			),
			'operation_titles' => array(
				'view' => GetMessage('WIKI_PERM_READ'),
				'write' => GetMessage('WIKI_PERM_WRITE'),
				'delete' => GetMessage('WIKI_PERM_DELETE')
			),
			'minoperation' => array('view'),
			'subscribe_events' => array(
				'wiki' => array(
					'ENTITIES' => array(
						SONET_SUBSCRIBE_ENTITY_GROUP => array(
							'TITLE' => GetMessage('SOCNET_LOG_WIKI_GROUP'),
							'TITLE_SETTINGS' => GetMessage('SOCNET_LOG_WIKI_GROUP_SETTINGS'),
							'TITLE_SETTINGS_1' => GetMessage('SOCNET_LOG_WIKI_GROUP_SETTINGS_1'),
							'TITLE_SETTINGS_2' => GetMessage('SOCNET_LOG_WIKI_GROUP_SETTINGS_2')
						),
					),
					'OPERATION' => 'view',
					'CLASS_FORMAT' => 'CWikiSocnet',
					'METHOD_FORMAT' => 'FormatEvent_Wiki',
					'HAS_CB' => 'Y',
					'FULL_SET' => array("wiki", "wiki_del", "wiki_comment"),
					"COMMENT_EVENT" => array(
						"EVENT_ID" => "wiki_comment",
						"OPERATION" => "view",
						"OPERATION_ADD" => "view",
						"ADD_CALLBACK" => array("CWikiSocnet", "AddComment_Wiki"),
						"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
						"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Forum"),
						"CLASS_FORMAT" => "CWikiSocnet",
						"METHOD_FORMAT" => "FormatComment_Wiki"
					)
				),
				'wiki_del' => array(
					'ENTITIES' => array(
						SONET_SUBSCRIBE_ENTITY_GROUP => array(
							'TITLE' => GetMessage('SOCNET_LOG_WIKI_DEL_GROUP')
						)
					),
					'OPERATION' => 'view',
					'CLASS_FORMAT' => 'CWikiSocnet',
					'METHOD_FORMAT' => 'FormatEvent_Wiki',
					'HIDDEN' => true,
					'HAS_CB' => 'Y'
				)
			)
		);
	}

	static function OnFillSocNetMenu(&$arResult, $arParams = array())
	{
		$arResult['AllowSettings']['wiki'] = true;

		$arResult['CanView']['wiki'] = ((array_key_exists('ActiveFeatures', $arResult) ? array_key_exists('wiki', $arResult['ActiveFeatures']) : true) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS['USER']->GetID(), $arParams['ENTITY_TYPE'], $arParams['ENTITY_ID'], 'wiki', 'view', CSocNetUser::IsCurrentUserModuleAdmin()));
		$arResult['Title']['wiki'] = (array_key_exists('ActiveFeatures', $arResult) && array_key_exists('wiki', $arResult['ActiveFeatures']) && strlen($arResult['ActiveFeatures']['wiki']) > 0 ? $arResult['ActiveFeatures']['wiki'] : GetMessage('WIKI_SOCNET_TAB'));

		if (!array_key_exists('SEF_MODE', $arResult) || $arResult['SEF_MODE'] != 'N')
			$arResult['Urls']['wiki'] = $arResult['Urls']['view'].'wiki/';
		else
		{
			if (!array_key_exists('PAGE_VAR', $arResult))
				$arResult['PAGE_VAR'] = 'page';

			if (!array_key_exists('GROUP_VAR', $arResult))
				$arResult['GROUP_VAR'] = 'group_id';

			$arResult['Urls']['wiki'] = '?'.$arResult['PAGE_VAR'].'=group_wiki_index&'.$arResult['GROUP_VAR'].'='.$arResult['Group']['ID'];
		}
	}

	static function OnParseSocNetComponentPath(&$arUrlTemplates, &$arCustomPagesPath, $arParams)
	{
		if ($arParams['SEF_MODE'] == 'N')
		{
			$arMyUrlTemplates = array(
				'group_wiki_index' => 'page=group_wiki_index&group_id=#group_id#',
				'group_wiki_categories' => 'page=group_wiki_categories&group_id=#group_id#',
				'group_wiki_search' => 'page=group_wiki_search&group_id=#group_id#',
				'group_wiki_post' => 'page=group_wiki_post&group_id=#group_id#&title=#wiki_name#',
				'group_wiki_post_edit' => 'page=group_wiki_post_edit&group_id=#group_id#&title=#wiki_name#',
				'group_wiki_post_history' => 'page=group_wiki_post_history&group_id=#group_id#&title=#wiki_name#',
				'group_wiki_post_history_diff' => 'page=group_wiki_post_history_diff&group_id=#group_id#&title=#wiki_name#',
				'group_wiki_post_discussion' => 'page=group_wiki_post_discussion&group_id=#group_id#&title=#wiki_name#',
				'group_wiki_post_category' => 'page=group_wiki_post_category&group_id=#group_id#&title=#wiki_name#',
				'group_wiki_post_comment' => 'page=group_wiki_post_comment&#message_id=#message_id#'
			);
		}
		else
		{
			$arMyUrlTemplates = array(
				'group_wiki_index' => 'group/#group_id#/wiki/',
				'group_wiki_categories' => 'group/#group_id#/wiki/categories/',
				'group_wiki_search' => 'group/#group_id#/wiki/search/',
				'group_wiki_post' => 'group/#group_id#/wiki/#wiki_name#/',
				'group_wiki_post_edit' => 'group/#group_id#/wiki/#wiki_name#/edit/',
				'group_wiki_post_history' => 'group/#group_id#/wiki/#wiki_name#/history/',
				'group_wiki_post_history_diff' => 'group/#group_id#/wiki/#wiki_name#/history/diff/',
				'group_wiki_post_discussion' => 'group/#group_id#/wiki/#wiki_name#/discussion/',
				'group_wiki_post_category' => 'group/#group_id#/wiki/#wiki_name#/',
				'group_wiki_post_comment' => 'group/#group_id#/wiki/#wiki_name#/?MID=#message_id##message#message_id#'
			);
		}

		static $base_path = false;
		if(!$base_path)
		{
			if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/wiki/'.SITE_ID.'/group_index.php'))
				$base_path = '/bitrix/php_interface/wiki/'.SITE_ID.'/';
			elseif(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/wiki/group_index.php'))
				$base_path = '/bitrix/php_interface/wiki/';
			else
				$base_path = '/bitrix/modules/wiki/socnet/';
		}

		foreach($arMyUrlTemplates as $page => $url)
		{
			$arUrlTemplates[$page] = $url;
			$arCustomPagesPath[$page] = $base_path;
		}
	}

	static function OnInitSocNetComponentVariables(&$arVariableAliases, &$arCustomPagesPath)
	{
		$arVariableAliases['wiki_name'] = 'wiki_name';
		$arVariableAliases['title'] = 'title';
		$arVariableAliases['oper'] = 'oper';
		$arVariableAliases['message_id'] = 'message_id';
	}

	static function FormatEvent_Wiki($arFields, $arParams, $bMail = false)
	{
		$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/themes/.default/wiki_sonet_log.css');

		$arResult = array(
			'EVENT' => $arFields,
			'CREATED_BY' => array(),
			'ENTITY' => array(),
			'EVENT_FORMATTED' => array(),
			"CACHED_CSS_PATH" => array("/bitrix/themes/.default/wiki_sonet_log.css")
		);

		$arResult['CREATED_BY'] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);

		if (!$bMail)
			$arResult['AVATAR_SRC'] = CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams);

		if (
			$arFields['ENTITY_TYPE'] == SONET_SUBSCRIBE_ENTITY_GROUP
			&& intval($arFields['ENTITY_ID']) > 0
		)
		{
			if ($bMail)
			{
				$arResult['ENTITY']['FORMATTED'] = $arFields['GROUP_NAME'];
				$arResult['ENTITY']['TYPE_MAIL'] = GetMessage('WIKI_SOCNET_LOG_ENTITY_G');
			}
			else
			{
				$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();
				$url = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_GROUP'], array('group_id' => $arFields['ENTITY_ID']));
				$arResult['ENTITY']['FORMATTED']['TYPE_NAME'] = $arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_GROUP]['TITLE_ENTITY'];
				$arResult['ENTITY']['FORMATTED']['URL'] = $url;
				$arResult['ENTITY']['FORMATTED']['NAME'] = $arFields['GROUP_NAME'];
			}
		}

		if (
			!$bMail
			&& array_key_exists('URL', $arFields)
			&& strlen($arFields['URL']) > 0
		)
			$wiki_tmp = '<a href="'.$arFields['URL'].'">'.$arFields['TITLE'].'</a>';
		else
			$wiki_tmp = $arFields['TITLE'];

		if ($arFields['EVENT_ID'] == 'wiki')
		{
			$title_tmp = ($bMail ? GetMessage('WIKI_SOCNET_LOG_TITLE_MAIL') : GetMessage('WIKI_SOCNET_LOG_TITLE'));
			$title_tmp_24 = GetMessage("WIKI_SOCNET_LOG_TITLE_24");
		}
		elseif ($arFields['EVENT_ID'] == 'wiki_del')
		{
			$title_tmp = ($bMail ? GetMessage('WIKI_DEL_SOCNET_LOG_TITLE_MAIL') : GetMessage('WIKI_DEL_SOCNET_LOG_TITLE'));
			$title_tmp_24 = GetMessage("WIKI_DEL_SOCNET_LOG_TITLE_24");
		}

		$title = str_replace(
			array('#TITLE#', '#ENTITY#', '#CREATED_BY#'),
			array($wiki_tmp, $arResult['ENTITY']['FORMATTED'], ($bMail ? $arResult['CREATED_BY']['FORMATTED'] : '')),
			$title_tmp
		);

		$arResult['EVENT_FORMATTED'] = array(
			"TITLE" => $title,
			"TITLE_24" => $title_tmp_24,
			"TITLE_24_2" => $arFields["TITLE"],
			"MESSAGE" => $arFields['MESSAGE']
		);

		$arResult['HAS_COMMENTS'] = 'N';
		if (
			intval($arFields['SOURCE_ID']) > 0
			&& array_key_exists('PARAMS', $arFields)
			&& strlen($arFields['PARAMS']) > 0
		)
		{
			$arFieldsParams = explode('&', $arFields['PARAMS']);
			if (is_array($arFieldsParams) && count($arFieldsParams) > 0)
				foreach ($arFieldsParams as $tmp)
				{
					list($key, $value) = explode('=', $tmp);
					if ($key == 'forum_id')
					{
						$arResult['HAS_COMMENTS'] = 'Y';
						break;
					}
				}
		}

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);
			if (strlen($url) > 0)
				$arResult['EVENT_FORMATTED']['URL'] = $url;

			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "Y", "VIDEO" => "Y", "LOG_VIDEO" => "Y", "TABLE" => "Y");
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = $arFields["TEXT_MESSAGE"] ? $arFields["TEXT_MESSAGE"] : HTMLToTxt($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
		}
		else
		{
			$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);

			//$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "Y", "MULTIPLE_BR" => "Y", "VIDEO" => "Y", "LOG_VIDEO" => "N");
			//$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));

			if ($arParams["MOBILE"] != "Y")
			{
				$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/wiki.show/templates/.default/style.css');
				$arResult["CACHED_CSS_PATH"][] = "/bitrix/components/bitrix/wiki.show/templates/.default/style.css";

				if($arParams["NEW_TEMPLATE"] != "Y")
				{
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
						1000
					);
					$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
				}
			}

			if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
			{
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
					array(
						"STYLE" => "sonetgroups",
						"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
						"URL" => $arResult["ENTITY"]["FORMATTED"]["URL"],
						"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]))
					)
				);
			}
		}

		return $arResult;
	}

	static function FormatComment_Wiki($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
			"EVENT_FORMATTED" => array()
		);

		if ($bMail)
		{
			$arResult['CREATED_BY'] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult['ENTITY'] = CSocNetLogTools::FormatEvent_GetEntity($arLog, $arParams, $bMail);
		}

		if (
			!$bMail
			&& array_key_exists('URL', $arLog)
			&& strlen($arLog['URL']) > 0
		)
			$wiki_tmp = '<a href="'.$arLog['URL'].'">'.$arLog['TITLE'].'</a>';
		else
			$wiki_tmp = $arLog['TITLE'];

		$title = str_replace(
			array('#TITLE#', '#ENTITY#', '#CREATED_BY#'),
			array($wiki_tmp, $arResult['ENTITY']['FORMATTED'], ($bMail ? $arResult['CREATED_BY']['FORMATTED'] : '')),
			($bMail ? GetMessage('WIKI_SOCNET_LOG_COMMENT_TITLE_MAIL') : GetMessage('WIKI_SOCNET_LOG_COMMENT_TITLE'))
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? CSocNetTextParser::killAllTags($arFields['MESSAGE']) : $arFields['MESSAGE'])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if (strlen($url) > 0)
				$arResult['EVENT_FORMATTED']['URL'] = $url;
		}
		else
		{
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
				if (!$parserLog)
					$parserLog = new forumTextParser(LANGUAGE_ID);

				$arAllow = array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "VIDEO" => "Y",
					"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arFields["UF"],
					"USER" => "Y"
				);

				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
			}
			else
			{
				if (!$parserLog)
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);

				$arAllow = array(
					"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "Y",
					"MULTIPLE_BR" => "Y",
					"VIDEO" => "Y", "LOG_VIDEO" => "N"
				);

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				if (CModule::IncludeModule("forum"))
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow),
						500
					);
				else
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
						500
					);

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		return $arResult;
	}

	static function AddComment_Wiki($arFields)
	{
		if (!CModule::IncludeModule('iblock'))
			return false;

		if (!CModule::IncludeModule('socialnetwork'))
			return false;

		$ufFileID = array();
		$ufDocID = array();

		$dbResult = CSocNetLog::GetList(
			array('ID' => 'DESC'),
			array('TMP_ID' => $arFields['LOG_ID']),
			false,
			false,
			array('ID', 'SOURCE_ID', 'PARAMS', 'URL')
		);

		$bFound = false;
		if ($arLog = $dbResult->Fetch())
		{
			if (strlen($arLog['PARAMS']) > 0)
			{
				$arFieldsParams = explode('&', $arLog['PARAMS']);
				if (is_array($arFieldsParams) && count($arFieldsParams) > 0)
					foreach ($arFieldsParams as $tmp)
					{
						list($key, $value) = explode('=', $tmp);
						if ($key == 'forum_id')
						{
							$FORUM_ID = intval($value);
							break;
						}
					}
			}
			if ($FORUM_ID > 0 && intval($arLog['SOURCE_ID']) > 0)
				$bFound = true;
		}

		if ($bFound)
		{
			$arElement = false;

			$arFilter = array('ID' => $arLog['SOURCE_ID']);
			$arSelectedFields = array(
				'IBLOCK_ID', 'ID', 'NAME', 'TAGS', 'CODE', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL',
				'CREATED_BY', 'PREVIEW_PICTURE', 'PREVIEW_TEXT', 'PROPERTY_FORUM_TOPIC_ID', 'PROPERTY_FORUM_MESSAGE_CNT'
			);
			$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
			if ($db_res && $res = $db_res->GetNext())
			{
				$arElement = $res;
			}

			if ($arElement)
			{
				if (
					isset($arFields["ENTITY_TYPE"])
					&& isset($arFields["ENTITY_ID"])
				)
				{
					$arElement["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
					$arElement["ENTITY_ID"] = $arFields["ENTITY_ID"];
				}

				// check iblock properties
				CSocNetLogTools::AddComment_Review_CheckIBlock($arElement);

				$dbMessage = CForumMessage::GetList(
					array(),
					array('PARAM2' => $arElement['ID'])
				);

				if (!$arMessage = $dbMessage->Fetch())
				{
					// Add Topic and Root Message
					$TOPIC_ID = CSocNetLogTools::AddComment_Review_CreateRoot($arElement, $FORUM_ID, false);
					$bNewTopic = true;
				}
				else
					$TOPIC_ID = $arMessage['TOPIC_ID'];

				if(intval($TOPIC_ID) > 0)
				{
					// Add comment
					$messageID = false;
					$arFieldsMessage = array(
						'POST_MESSAGE' => $arFields['TEXT_MESSAGE'],
						'USE_SMILES' => 'Y',
						'PARAM2' => $arElement['ID'],
						'APPROVED' => 'Y'
					);

					$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
					if (is_array($arTmp))
					{
						if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
							$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
						elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
						{
							$arFieldsMessage["FILES"] = array();
							foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
								$arFieldsMessage["FILES"][] = array("FILE_ID" => $file_id);
						}
					}

					$messageID = ForumAddMessage('REPLY', $FORUM_ID, $TOPIC_ID, 0, $arFieldsMessage, $sError, $sNote);

					if (!$messageID)
						$strError = GetMessage('SONET_ADD_COMMENT_SOURCE_ERROR');
					else
					{
						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

						$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
					
						CSocNetLogTools::AddComment_Review_UpdateElement($arElement, $TOPIC_ID, $bNewTopic);

						$userID = $GLOBALS["USER"]->GetID();

						if (
							CModule::IncludeModule("im")
							&& intval($arElement["CREATED_BY"]) > 0
							&& $arElement["CREATED_BY"] != $userID
						)
						{
							$rsUnFollower = CSocNetLogFollow::GetList(
								array(
									"USER_ID" => $arElement["CREATED_BY"],
									"CODE" => "L".$arLog["ID"],
									"TYPE" => "N"
								),
								array("USER_ID")
							);

							$arUnFollower = $rsUnFollower->Fetch();
							if (!$arUnFollower)
							{
								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => $arElement["CREATED_BY"],
									"FROM_USER_ID" => $userID,
									"LOG_ID" => $arLog["ID"],
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "wiki",
									"NOTIFY_EVENT" => "comment",
								);

								$arParams["TITLE"] = str_replace(Array("\r\n", "\n"), " ", $arElement["NAME"]);
								$arParams["TITLE"] = TruncateText($arParams["TITLE"], 100);
								$arParams["TITLE_OUT"] = TruncateText($arParams["TITLE"], 255);

								$arTmp = CSocNetLogTools::ProcessPath(array("ELEMENT_URL" => $arLog["URL"]), $arElement["CREATED_BY"]);
								$serverName = $arTmp["SERVER_NAME"];
								$url = $arTmp["URLS"]["ELEMENT_URL"];

								$arMessageFields["NOTIFY_TAG"] = "WIKI|COMMENT|".$arElement['ID'];
								$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("WIKI_SONET_FROM_LOG_IM_COMMENT", Array(
									"#title#" => (
										strlen($url) > 0 
											? "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arParams["TITLE"])."</a>"
											: htmlspecialcharsbx($arParams["TITLE"])
									)
								));

								$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("WIKI_SONET_FROM_LOG_IM_COMMENT", Array(
									"#title#" => htmlspecialcharsbx($arParams["TITLE"])
								)).(strlen($url) > 0 
									? " (".$serverName.$url.")"
									: ""
								)."#BR##BR#".$arFields["TEXT_MESSAGE"];

								CIMNotify::Add($arMessageFields);
							}
						}
					}
				}
				else
					$strError = GetMessage('SONET_ADD_COMMENT_SOURCE_ERROR');
			}
			else
				$strError = GetMessage('SONET_ADD_COMMENT_SOURCE_ERROR');
		}
		else
			$strError = GetMessage('SONET_ADD_COMMENT_SOURCE_ERROR');

		return array(
			'SOURCE_ID' => $messageID,
			'RATING_TYPE_ID' => 'FORUM_POST',
			'RATING_ENTITY_ID' => $messageID,
			'ERROR' => $strError,
			'NOTES' => '',
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID
			)
		);
	}

	static function RecalcIBlockID($SocNetGroupID)
	{
		if(!CModule::IncludeModule('iblock'))
			return false;

		$arWikiIblockID = array();
		$iblock_id_tmp = COption::GetOptionString("wiki", "socnet_iblock_id", false, "");
		if (intval($iblock_id_tmp) > 0)
			$arWikiIblockID[] = $iblock_id_tmp;

		$rsSite = CSite::GetList($by="sort", $order="asc", array("ACTIVE"=>"Y"));
		while($arSite = $rsSite->Fetch())
		{
			$iblock_id_tmp = COption::GetOptionString("wiki", "socnet_iblock_id", false, $arSite["LID"]);
			if (intval($iblock_id_tmp) > 0)
				$arWikiIblockID[] = $iblock_id_tmp;
		}

		if (count($arWikiIblockID) > 0)
		{
			$rsWikiSection = CIBlockSection::GetList(
				array("timestamp_x"=>"desc"),
				array(
					"IBLOCK_ID" => array_unique($arWikiIblockID),
					"SOCNET_GROUP_ID" => $SocNetGroupID
				),
				false,
				array("IBLOCK_ID")
			);
			if ($arWikiSection = $rsWikiSection->Fetch())
				return $arWikiSection["IBLOCK_ID"];
		}

		return false;
	}

	static function PrepareTextForFeed($text)
	{
/*		$retText = preg_replace("/(<\s*\/(h(\d+)|li|ul)\s*>)\s*(<\s*br\s*\/*\s*>){0,1}(\s*(\r*\n)\s*){1,2}/ism", "$1##NN##", $text);
		$retText = preg_replace("/(<\s*(ul)\s*>)\s*(<\s*br\s*\/*\s*>){0,1}(\s*(\r*\n)\s*){1,2}/ism", "$1##NN##", $retText);
		$retText = preg_replace("/<\s*br\s*\/*\s*>\s*(\r*\n)/ismU", "##BR##", $retText);
		$retText = preg_replace("/(\r)*\n/ism", "<br />", $retText);
		$retText = preg_replace("/##NN##/ismU","\n", $retText);
		$retText = preg_replace("/##BR##/ismU","<br />\n", $retText);
*/
		$retText = "<div class='wiki_post_feed'>".$text."</div>";

		return $retText;
	}

	public static function __ProcessPath($arUrl, $user_id)
	{
		return CSocNetLogTools::ProcessPath($arUrl, $user_id);
	}
	
	public static function BeforeIndexSocNet($bxSocNetSearch, $arFields)
	{
		static $isSonetEnable = false;
		static $sonetForumId = false;
		
		if (!$isSonetEnable)
		{
			$isSonetEnable = COption::GetOptionString('wiki', 'socnet_enable');	
		}

		if (!$sonetForumId)
		{
			$sonetForumId = intval(COption::GetOptionString('wiki', 'socnet_forum_id'));
		}

		if(
			$arFields['ENTITY_TYPE_ID'] == 'FORUM_POST' 
			&& $isSonetEnable == 'Y'
			&& intval($arFields['PARAM1']) == $sonetForumId
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			if($bxSocNetSearch->_group_id)
			{
				$arFields = $bxSocNetSearch->BeforeIndexForum(
					$arFields,
					SONET_ENTITY_GROUP, 
					$bxSocNetSearch->_group_id,
					"wiki", 
					"view",
					$bxSocNetSearch->Url(
						str_replace(
							"#wiki_name#",
							urlencode($arFields["TITLE"]),
							$bxSocNetSearch->_params["PATH_TO_GROUP_WIKI_POST_COMMENT"]
						),
						array(
							"MID" => "#message_id#"
						), 
						"message#message_id#"
					)
				);
			}
		}

		return $arFields;
	}
}
?>