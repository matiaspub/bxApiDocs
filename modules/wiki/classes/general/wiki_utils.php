<?php

IncludeModuleLangFile(__FILE__);


/**
 * <b>CWikiUtils</b> - Класс дополнительных функций. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/index.php
 * @author Bitrix
 */
class CWikiUtils
{
	static function getRightsLinks($arPage)
	{
		global $arParams, $APPLICATION;
		if (!is_array($arPage))
			$arPage = array($arPage);

		$arLinks = array();
		$arParams['ELEMENT_NAME'] = htmlspecialcharsback($arParams['ELEMENT_NAME']);
		$arParams['ELEMENT_NAME'] = urlencode($arParams['ELEMENT_NAME']);

		if (in_array('categories', $arPage))
			return array();

		if (in_array('article', $arPage) && !in_array('add', $arPage))
		{
			$arLinks['article'] = array(
				'NAME' => GetMessage('PAGE_ARTICLE'),
				'TITLE' => GetMessage('PAGE_ARTICLE_TITLE'),
				'CURRENT' => in_array('article', $arPage),
				'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_POST'],
					array(
						'wiki_name' => $arParams['ELEMENT_NAME'],
						'group_id' => CWikiSocnet::$iSocNetId
					)
				),
				'ID' => 'article',
				'TYPE' => 'page',
				'IS_RED' => in_array('add', $arPage) ? 'Y' : 'N'
			);
		}

		if (self::IsWriteable() &&
			((!in_array('history', $arPage) || in_array('history_diff', $arPage)) &&
			(!in_array('add', $arPage) && !in_array('edit', $arPage) && !in_array('delete', $arPage) && !in_array('rename', $arPage))))
		{
			if(IsModuleInstalled('bizproc'))
			{
				$arLinks['history'] = array(
					'NAME' => GetMessage('PAGE_HISTORY'),
					'TITLE' => GetMessage('PAGE_HISTORY_TITLE'),
					'CURRENT' => in_array('history', $arPage),
					'LINK' => CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_HISTORY'],
							array(
								'wiki_name' => $arParams['ELEMENT_NAME'],
								'group_id' => CWikiSocnet::$iSocNetId
							)
						),
						$arParams['IN_COMPLEX'] == 'Y' && $arParams['SEF_MODE'] == 'N' ? array($arParams['OPER_VAR'] => 'history') : array()
					),
					'ID' => 'history',
					'TYPE' => 'page',
					'IS_RED' => 'N'
				);
			}
		}

		if ($arParams['USE_REVIEW'] == 'Y')
		{
			$arLinks['discussion'] = array(
				'NAME' => GetMessage('PAGE_DISCUSSION'),
				'TITLE' => GetMessage('PAGE_DISCUSSION_TITLE'),
				'CURRENT' => in_array('discussion', $arPage),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DISCUSSION'],
						array(
							'wiki_name' => $arParams['ELEMENT_NAME'],
							'group_id' => CWikiSocnet::$iSocNetId
						)
					),
					$arParams['IN_COMPLEX'] == 'Y' && $arParams['SEF_MODE'] == 'N' ? array($arParams['OPER_VAR'] => 'discussion') : array()
				),
				'ID' => 'discussion',
				'TYPE' => 'page',
				'IS_RED' => 'N'
			);
		}

		if (self::IsWriteable() && (!in_array('history', $arPage) && !in_array('history_diff', $arPage)))
		{
			$arLinks['add'] = array(
				'NAME' => GetMessage('PAGE_ADD'),
				'TITLE' => GetMessage('PAGE_ADD_TITLE'),
				'CURRENT' => in_array('add', $arPage),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_POST_EDIT'],
						array(
							'wiki_name' => GetMessage('WIKI_NEW_PAGE_TITLE'),
							'group_id' => CWikiSocnet::$iSocNetId
						)
					),
					array($arParams['OPER_VAR'] => 'add')
				),
				'ID' => 'add',
				'TYPE' => 'edit',
				'IS_RED' => in_array('add', $arPage) ? 'Y' : 'N'
			);

			if (!in_array('add', $arPage))
			{
				$arLinks['edit'] = array(
					'NAME' => GetMessage('PAGE_EDIT'),
					'TITLE' => GetMessage('PAGE_EDIT_TITLE'),
					'CURRENT' => in_array('edit', $arPage),
					'LINK' => CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_POST_EDIT'],
							array(
								'wiki_name' => $arParams['ELEMENT_NAME'],
								'group_id' => CWikiSocnet::$iSocNetId
							)
						),
						$arParams['IN_COMPLEX'] == 'Y' && $arParams['SEF_MODE'] == 'N' ? array($arParams['OPER_VAR'] => 'edit') : array()
					),
					'ID' => 'edit',
					'TYPE' => 'edit',
					'IS_RED' => in_array('add', $arPage) ? 'Y' : 'N'
				);

				$url = $APPLICATION->GetPopupLink(
					array(
						'URL' => CHTTP::urlAddParams(
							CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_POST_EDIT'],
								array(
									'wiki_name' => urlencode($arParams['ELEMENT_NAME']),
									'group_id' => CWikiSocnet::$iSocNetId
								)
							),
							array($arParams['OPER_VAR'] => 'rename')
						),
						'PARAMS' => array(
							'width' => 400,
							'height' => 150,
							'resizable' => false
						)
					)
				);

				$arLinks['rename'] = array(
					'NAME' => GetMessage('WIKI_PAGE_RENAME'),
					'TITLE' => GetMessage('WIKI_PAGE_RENAME_TITLE'),
					'CURRENT' => in_array('rename', $arPage),
					'LINK' => 'javascript:'.$url,
					'ID' => 'rename',
					'TYPE' => 'page',
				);

				if (self::IsDeleteable())
				{
					$url = $APPLICATION->GetPopupLink(
						array(
							'URL' => CHTTP::urlAddParams(
								CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_POST_EDIT'],
									array(
										'wiki_name' => $arParams['ELEMENT_ID'],
										'group_id' => CWikiSocnet::$iSocNetId
									)
								),
								array($arParams['OPER_VAR'] => 'delete')
							),
							'PARAMS' => array(
								'width' => 400,
								'height' => 150,
								'resizable' => false
							)
						)
					);

					$arLinks['delete'] = array(
						'NAME' => GetMessage('PAGE_DELETE'),
						'TITLE' => GetMessage('PAGE_DELETE_TITLE'),
						'CURRENT' => in_array('delete', $arPage),
						'LINK' => 'javascript:'.$url,
						'ID' => 'delete',
						'TYPE' => 'edit',
						'IS_RED' => 'N'
					);
				}
			}


		/**	$arLinks['access'] = array(
				'NAME' => GetMessage('PAGE_ACCESS'),
				'TITLE' => GetMessage('PAGE_ACCESS_TITLE'),
				'CURRENT' => in_array('access', $arPage),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_POST_EDIT'],
						array(
							'wiki_name' => $arParams['ELEMENT_NAME'],
							'group_id' => CWikiSocnet::$iSocNetId
						)
					),
					array($arParams['OPER_VAR'] => 'access')
				),
				'ID' => 'access',
				'TYPE' => 'edit',
				'IS_RED' => 'N'
			); **/
		}

		return $arLinks;
	}

	
	/**
	* <p>Метод проверяет доступ пользователя на чтение. Статичный метод.</p>
	*
	*
	* @return bool 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsWriteable.php">CWikiUtils::isWriteable</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsDeleteable.php">CWikiUtils::isDeleteable</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsAllowHTML.php">CWikiUtils::isAllowHTML</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/CheckAccess.php">CWikiUtils::CheckAccess</a>
	* </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsReadable.php
	* @author Bitrix
	*/
	static function IsReadable()
	{
		return self::CheckAccess('view');
	}

	
	/**
	* <p>Метод проверяет доступ пользователя на запись. Статичный метод.</p>
	*
	*
	* @return bool 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsReadable.php">CWikiUtils::isReadable</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsDeleteable.php">CWikiUtils::isDeleteable</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsAllowHTML.php">CWikiUtils::isAllowHTML</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/CheckAccess.php">CWikiUtils::CheckAccess</a>
	* </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsWriteable.php
	* @author Bitrix
	*/
	static function IsWriteable()
	{
		return self::CheckAccess('write');
	}

	static function isAllowHTML()
	{
		if (COption::GetOptionString('wiki', 'allow_html', 'Y') == 'N')
			return false;

		if (!$GLOBALS['USER']->IsAuthorized())
			return false;

		return true;
	}

	
	/**
	* <p>Метод проверяет доступ пользователя на удаление. Статичный метод.</p>
	*
	*
	* @return bool 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsReadable.php">CWikiUtils::isReadable</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsWriteable.php">CWikiUtils::isWriteable</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsAllowHTML.php">CWikiUtils::isAllowHTML</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/CheckAccess.php">CWikiUtils::CheckAccess</a>
	* </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsDeleteable.php
	* @author Bitrix
	*/
	static function IsDeleteable()
	{
		return self::CheckAccess('delete');
	}

	
	/**
	* <p>Метод проверяет доступ пользователя. Статичный метод.</p>
	*
	*
	* @param string $access  Тип проверяемого доступа (view, write, delete)
	*
	* @return bool 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsReadable.php">CWikiUtils::isReadable</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsWriteable.php">CWikiUtils::isWriteable</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsDeleteable.php">CWikiUtils::IsDeleteable</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsAllowHTML.php">CWikiUtils::isAllowHTML</a>
	* </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/CheckAccess.php
	* @author Bitrix
	*/
	static function CheckAccess($access = 'view')
	{
		global $APPLICATION, $USER, $arParams;

		if ($USER->IsAdmin())
			return true;

		if (CWikiSocnet::IsSocNet())
		{
			$arSonetGroup = CSocNetGroup::GetByID(CWikiSocnet::$iSocNetId);
			if ($arSonetGroup && CSocNetUser::IsCurrentUserModuleAdmin($arSonetGroup['SITE_ID']))
				return true;

			if (!CSocNetFeaturesPerms::CanPerformOperation($USER->GetID(), SONET_ENTITY_GROUP, CWikiSocnet::$iSocNetId, 'wiki', $access))
				return false;

			return true;
		}
		else
		{
			$letter = 'R';
			$letterI = 'R';
			switch ($access)
			{
				case 'write': $letter = 'W'; $letterI = 'W'; break;
				case 'delete': $letter = 'Y'; $letterI = 'W'; break;
				case 'perm': $letter = 'Z'; $letterI = 'X'; break;
			}

			$wikiModulePermission = $APPLICATION->GetGroupRight('wiki');
			$iblockPermission = CIBlock::GetPermission($arParams['IBLOCK_ID']);
			return $wikiModulePermission >= $letter && $iblockPermission >= $letterI;
		}
	}

	
	/**
	* <p>Метод проверяет, является ли запрашиваемая страница сервисной. Статичный метод.</p>
	*
	*
	* @param string $NAME  Наименование страницы. До версии 10.0.0 назывался <b>ELEMENT_NAME</b>
	*
	* @param string &$SERVICE_NAME  Наименование сервисной страницы
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsCategoryPage.php">CWikiUtils::IsCategoryPage</a> </li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/CheckServicePage.php
	* @author Bitrix
	*/
	static function CheckServicePage($NAME, &$SERVICE_NAME)
	{
		$arStream = array('category', strtolower(GetMessage('CATEGORY_NAME')));
		$arSplit = explode(':', $NAME);

		if (count($arSplit) >= 2)
		{
			$SERVICE_PAGE = strtolower($arSplit[0]);
			if (in_array($SERVICE_PAGE, $arStream))
			{
				unset($arSplit[0]);
				$SERVICE_NAME =  implode(':', $arSplit);
				return $SERVICE_PAGE;
			}
			else
				return '';
		}
		else
			return '';
	}

	
	/**
	* <p>Метод проверяет, является ли запрашиваемая страница страницей категории. Статичный метод.</p>
	*
	*
	* @param string $NAME  Наименование страницы. До версии 10.0.0 назывался <b>ELEMENT_NAME</b>.
	*
	* @param string &$CATEGORY_NAME  Наименование категории
	*
	* @return bool 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/CheckServicePage.php">CWikiUtils::CheckServicePage</a>
	* </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiutils/IsCategoryPage.php
	* @author Bitrix
	*/
	static function IsCategoryPage($NAME, &$CATEGORY_NAME)
	{
		$sServiceName = self::CheckServicePage($NAME, $CATEGORY_NAME);
		return $sServiceName == 'category' || $sServiceName == strtolower(GetMessage('CATEGORY_NAME'));
	}

	static function OnBeforeIndex($arFields)
	{
		$arFields['NAME'] = preg_replace('/^category:/i'.BX_UTF_PCRE_MODIFIER, GetMessage('CATEGORY_NAME').':', $arFields['NAME']);
		$CWikiParser = new CWikiParser();
		$arFields['BODY'] = $CWikiParser->parseForSearch($arFields['BODY']);

		if(SITE_DIR != "/") //http://www.jabber.bx/view.php?id=29053
			if(preg_match("#^".SITE_DIR."#",$arFields['SITE_ID'][SITE_ID]) !== false)
				$arFields['SITE_ID'][SITE_ID] = preg_replace("#^".SITE_DIR."#","/",$arFields['SITE_ID'][SITE_ID]);

		return $arFields;
	}

	static function GetUserLogin($arUserData = array(), $nameTemplate = "")
	{
		global $USER;

		if (empty($nameTemplate))
			$nameTemplate = CSite::GetNameFormat(false);

		if (!empty($arUserData))
		{
			$userLogin = isset($arUserData['USER_LOGIN']) ? $arUserData['USER_LOGIN'] : $arUserData['LOGIN'];
			$userFName = isset($arUserData['USER_NAME']) ? $arUserData['USER_NAME'] : $arUserData['NAME'];
			$userLName = isset($arUserData['USER_LAST_NAME']) ? $arUserData['USER_LAST_NAME'] : $arUserData['LAST_NAME'];
			$userSName = isset($arUserData['USER_SECOND_NAME']) ? $arUserData['USER_SECOND_NAME'] : $arUserData['SECOND_NAME'];
		}
		else
		{
			$userLogin = $USER->GetLogin();
			$userFName = $USER->GetFirstName();
			$userLName = $USER->GetLastName();
			$userSName = $USER->GetSecondName();
		}

		$userLogin = CUser::FormatName($nameTemplate, array("NAME" => $userFName, "LAST_NAME" => $userLName, "SECOND_NAME" => $userSName, "LOGIN" => $userLogin ));

		return $userLogin;
	}

	static function htmlspecialcharsback($str, $end = true)
	{
		$str = urldecode($str);
		while(strpos($str, '&amp;') !== false)
			$str = self::htmlspecialchars_decode($str);
		if($end)
			$str = self::htmlspecialchars_decode($str);
		return  $str;
	}

	static function htmlspecialchars_decode($str)
	{
		static $search =  array("&lt;", "&gt;", "&quot;", "&apos;", "&#039;","&amp;");
		static $replace = array("<",    ">",    "\"", "'", "'","&");
		return str_replace($search, $replace, $str);
	}

	/**
	* Sets right search path for comments, likes etc.
	* http://jabber.bx/view.php?id=25340
	* @param int $forumID - forum's ID were comments saving (for example $arParams['FORUM_ID'])
	* @param str $rightPath - wich path must leads to the comment ( for example: "/comment/#MESSAGE_ID#/" )
	* @param str $urlRewriterPath - wich path leads to curent module (complex component) ( for example: "/services/wiki.php" )
	* @return bool true|false
	*/
	static function SetCommentPath($forumID, $rightPath, $urlRewriterPath)
	{
		if (!$forumID || !CModule::IncludeModule('forum') || !$rightPath || !$urlRewriterPath)
			return false;

		$arRewriter = CUrlRewriter::GetList(array("PATH"=>$urlRewriterPath));		//http://jabber.bx/view.php?id=25340

		if(!is_array($arRewriter) || empty($arRewriter))
			return false;

		$rewriteCondition = str_replace(array("#","^"),"",$arRewriter[0]["CONDITION"]);
		$rightCommentsPath = $rewriteCondition.$rightPath;

		$arActualCommentsPath = CWikiUtils::GetCommentPath($forumID);

		if(!is_array($arActualCommentsPath))
			return false;

		$arUpdateForum = array();

		foreach ($arActualCommentsPath as $site => $path)
			if($path!=$rightCommentsPath)
				$arUpdateForum["SITES"][$site] = $rightCommentsPath;

		if(!empty($arUpdateForum))
			CForumNew::Update($forumID, $arUpdateForum);

		return true;
	}

	/**
	* Gets right search path for comments,
	* http://jabber.bx/view.php?id=25340
	* @param int $forumID - forum's ID were comments saving (for example $arParams['FORUM_ID'])
	* @return array( $siteID => $forumURL )
	*/
	static function GetCommentPath($forumID)
	{
		$arForumPath=array();

		if (!$forumID || !CModule::IncludeModule('forum'))
			return false;

		$arSites = CForumNew::GetSites($forumID);

		if(!is_array($arSites))
			return false;

		foreach ($arSites as $siteID => $forumUrl)
				$arForumPath[$siteID] = $forumUrl;

		return $arForumPath;
	}

	static function UnlocalizeCategoryName($categoryName)
	{
		return preg_replace('/^'.GetMessage('CATEGORY_NAME').':/i'.BX_UTF_PCRE_MODIFIER, 'category:', $categoryName);
	}

	static function GetTagsAsLinks($arTags)
	{
		if(!is_array($arTags) || empty($arTags))
			return "";

		$strRet = "";
		$_i = 1;
		foreach ($arTags as $arTag)
		{
			if (isset($arTag['LINK'])):
				$strRet .= "<a title='".$arTag['NAME']."' href='".$arTag['LINK']."'>".$arTag['NAME']."</a>";
			else :
				$strRet .= $arTag['NAME'];
			endif;

			if ($_i < count($arTags))
				$strRet .= ' | ';
			$_i++;
		}

		return $strRet;
	}

	static function isCategoryVirtual($name)
	{
		$result = false;
		$sCatName = '';

		if(CWikiUtils::IsCategoryPage($name, $sCatName))
			$result = self::isVirtualCategoryExist($sCatName);

		return $result;
	}

	static function isVirtualCategoryExist($categoryName)
	{
		$result = false;

		if($categoryName == GetMessage("WIKI_CATEGORY_NOCAT") || $categoryName == GetMessage("WIKI_CATEGORY_ALL"))
		{
			$result = true;
		}
		else
		{
			$categories = new CWikiCategories;
			$rsHandlers = GetModuleEvents("wiki", "OnCategoryListCreate");

			while($arHandler = $rsHandlers->Fetch())
				ExecuteModuleEventEx($arHandler, array(&$categories, ''));

			$arCats = $categories->GetItems();

			foreach ($arCats as $category)
			{
				if($category["NAME"] == $categoryName)
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}
}

?>
