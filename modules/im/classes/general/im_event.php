<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Im as IM;

class CIMEvent
{
	public static function OnFileDelete($params)
	{
		$result = IM\Model\ChatTable::getList(Array(
			'select' => Array('ID', 'AUTHOR_ID'),
			'filter' => Array('=AVATAR' => $params['ID'])
		));
		while ($row = $result->fetch())
		{
			IM\Model\ChatTable::update($row['ID'], Array('AVATAR' => ''));

			$obCache = new CPHPCache();
			$arRel = CIMChat::GetRelationById($row['ID']);
			foreach ($arRel as $rel)
			{
				$obCache->CleanDir('/bx/imc/recent'.CIMMessenger::GetCachePath($rel['USER_ID']));
			}
		}
	}

	public static function OnBeforeUserSendPassword($params)
	{
		$bots = IM\Bot::getListCache();
		if (empty($bots))
			return true;

		if (isset($params['LOGIN']) && !empty($params['LOGIN']))
		{
			if (substr($params['LOGIN'], 0, strlen(IM\Bot::LOGIN_START)) == IM\Bot::LOGIN_START)
			{
				$orm = \Bitrix\Main\UserTable::getList(Array(
					'filter' => Array(
						'=LOGIN' => $params['LOGIN'],
						'=EXTERNAL_AUTH_ID' => IM\Bot::EXTERNAL_AUTH_ID
					)
				));
				if ($orm->fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_EVENT_ERROR_CHANGE_PASSWORD_FOR_BOT"), "ERROR_CHANGE_PASSWORD_FOR_BOT");
					return false;
				}
			}
		}

		if (isset($params['EMAIL']) && !empty($params['EMAIL']))
		{
			$orm = \Bitrix\Main\UserTable::getList(Array(
				'filter' => Array(
					'=EMAIL' => $params['EMAIL'],
					'=EXTERNAL_AUTH_ID' => IM\Bot::EXTERNAL_AUTH_ID
				)
			));
			if ($orm->fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_EVENT_ERROR_CHANGE_PASSWORD_FOR_BOT"), "ERROR_CHANGE_PASSWORD_FOR_BOT");
				return false;
			}
		}

		return true;
	}

	public static function OnAddRatingVote($id, $arParams)
	{
		$bSocialnetworkInstalled = CModule::IncludeModule("socialnetwork");

		if (
			$arParams['ENTITY_TYPE_ID'] == 'LISTS_NEW_ELEMENT'
			&& $bSocialnetworkInstalled
		) // BP
		{
			$rsLog = CSocNetLog::GetList(
				array(),
				array(
					"RATING_TYPE_ID" => $arParams['ENTITY_TYPE_ID'],
					"RATING_ENTITY_ID" =>  $arParams['ENTITY_ID']
				),
				false,
				false,
				array("ID", "USER_ID", "TITLE_TEMPLATE", "TITLE")
			);

			if ($arLog = $rsLog->Fetch())
			{
				if ($arLog['USER_ID'] != $arParams['USER_ID'])
				{
					$url = COption::GetOptionString("socialnetwork", "log_entry_page", $arSites[$user_site_id]["DIR"]."company/personal/log/#log_id#/", SITE_ID);
					$url = str_replace("#log_id#", $arLog["ID"], $url);

					$arParams['ENTITY_LINK'] = $url;
					$arParams['ENTITY_TITLE'] = htmlspecialcharsback($arLog["TITLE"]);

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => intval($arLog['USER_ID']),
						"FROM_USER_ID" => intval($arParams['USER_ID']),
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "main",
						"NOTIFY_EVENT" => "rating_vote",
						"NOTIFY_TAG" => "RATING|".($arParams['VALUE'] >= 0 ? "" : "DL|").$arParams['ENTITY_TYPE_ID']."|".$arParams['ENTITY_ID'],
						"NOTIFY_MESSAGE" => self::GetMessageRatingVote($arParams),
						"NOTIFY_MESSAGE_OUT" => self::GetMessageRatingVote($arParams, true)
					);

					CIMNotify::Add($arMessageFields);
				}
			}
		}
		elseif ($arParams['ENTITY_TYPE_ID'] == 'LOG_COMMENT') // no source
		{
			if ($arComment = CSocNetLogComments::GetByID($arParams['ENTITY_ID']))
			{
				preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/ies".BX_UTF_PCRE_MODIFIER, $arComment["TEXT_MESSAGE"], $arMention);
				if(!empty($arMention))
				{
					$arMentionedUserID = $arMention[1];
				}

				if ($arComment['USER_ID'] == $arParams['USER_ID'] && empty($arMentionedUserID))
				{
					return false;
				}

				$arEventTmp = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
				if (
					$arEventTmp
					&& array_key_exists("CLASS_FORMAT", $arEventTmp)
					&& array_key_exists("METHOD_FORMAT", $arEventTmp)
				)
				{
					$arComment["MESSAGE"] = preg_replace(
						array(
							'|\[DISK\sFILE\sID=[n]*\d+\]|',
							'|\[DOCUMENT\sID=[n]*\d+\]|'
						),
						'',
						$arComment["MESSAGE"]
					);

					$arComment["MESSAGE"] = preg_replace(
						'|\[QUOTE\](.+?)\[\/QUOTE\]|is'.BX_UTF_PCRE_MODIFIER,
						'&quot;\\1&quot;',
						$arComment["MESSAGE"]
					);

					$arFIELDS_FORMATTED = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arComment, array("IM" => "Y"));

					$CCTP = new CTextParser();
					$CCTP->MaxStringLen = 200;
					$CCTP->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

					$arComment["MESSAGE"] = $CCTP->convertText($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
				}

				$arComment["MESSAGE"] = preg_replace(
					array(
						'|\[DISK\sFILE\sID=[n]*\d+\]|',
						'|\[DOCUMENT\sID=[n]*\d+\]|'
					),
					'',
					$arComment["MESSAGE"]
				);

				$arParams["ENTITY_TITLE"] = strip_tags(str_replace(array("<br>","<br/>","<br />", "#BR#"), Array(" "," ", " ", " "), htmlspecialcharsback($arComment["MESSAGE"])));

				$bExtranetInstalled = CModule::IncludeModule("extranet");

				if ($bExtranetInstalled)
				{
					$arSites = array();
					$extranet_site_id = CExtranet::GetExtranetSiteID();
					$intranet_site_id = CSite::GetDefSite();
					$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));
					while($arSite = $dbSite->Fetch())
					{
						$arSites[$arSite["ID"]] = array(
							"DIR" => (strlen(trim($arSite["DIR"])) > 0 ? $arSite["DIR"] : "/"),
							"SERVER_NAME" => (strlen(trim($arSite["SERVER_NAME"])) > 0 ? $arSite["SERVER_NAME"] : COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]))
						);
					}
				}

				if ($arComment['USER_ID'] != $arParams['USER_ID'])
				{
					$followValue = "Y";

					if ($bSocialnetworkInstalled)
					{
						$followValue = CSocNetLogFollow::GetExactValueByRating(
							$arComment['USER_ID'],
							trim($arParams["ENTITY_TYPE_ID"]),
							intval($arParams["ENTITY_ID"])
						);
					}

					if ($followValue != "N")
					{
						$arParams['ENTITY_LINK'] = self::GetMessageRatingLogCommentURL(
							$arComment,
							intval($arComment['USER_ID']),
							$arSites,
							$intranet_site_id,
							$extranet_site_id
						);

						$arMessageFields = array(
							"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
							"TO_USER_ID" => intval($arComment['USER_ID']),
							"FROM_USER_ID" => intval($arParams['USER_ID']),
							"NOTIFY_TYPE" => IM_NOTIFY_FROM,
							"NOTIFY_MODULE" => "main",
							"NOTIFY_EVENT" => "rating_vote",
							"NOTIFY_TAG" => "RATING|".($arParams['VALUE'] >= 0?"":"DL|").$arParams['ENTITY_TYPE_ID']."|".$arParams['ENTITY_ID'],
							"NOTIFY_MESSAGE" => self::GetMessageRatingVote($arParams),
							"NOTIFY_MESSAGE_OUT" => self::GetMessageRatingVote($arParams, true)
						);

						CIMNotify::Add($arMessageFields);
					}
				}

				if (
					!empty($arMentionedUserID)
					&& is_array($arMentionedUserID)
				)
				{
					$arParams["MENTION"] = true; // for self::GetMessageRatingVote()

					foreach ($arMentionedUserID as $mentioned_user_id)
					{
						if (
							$mentioned_user_id != $arParams['USER_ID']
							&& CSocNetLogRights::CheckForUserOnly($arComment["LOG_ID"], $mentioned_user_id)
						)
						{
							$followValue = "Y";

							if ($bSocialnetworkInstalled)
							{
								$followValue = CSocNetLogFollow::GetExactValueByRating(
									intval($mentioned_user_id),
									trim($arParams["ENTITY_TYPE_ID"]),
									intval($arParams["ENTITY_ID"])
								);
							}

							if ($followValue != "N")
							{
								$arParams['ENTITY_LINK'] = self::GetMessageRatingLogCommentURL(
									$arComment,
									intval($mentioned_user_id),
									$arSites,
									$intranet_site_id,
									$extranet_site_id
								);

								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => intval($mentioned_user_id),
									"FROM_USER_ID" => intval($arParams['USER_ID']),
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "main",
									"NOTIFY_EVENT" => "rating_vote_mentioned",
									"NOTIFY_TAG" => "RATING_MENTION|".($arParams['VALUE'] >= 0?"":"DL|").$arParams['ENTITY_TYPE_ID']."|".$arParams['ENTITY_ID'],
									"NOTIFY_MESSAGE" => self::GetMessageRatingVote($arParams),
									"NOTIFY_MESSAGE_OUT" => self::GetMessageRatingVote($arParams, true)
								);

								CIMNotify::Add($arMessageFields);
							}
						}
					}
				}
			}
		}
		else // with source
		{
			if (
				false &&
				$arParams['OWNER_ID'] != $arParams['USER_ID']
				&& $bSocialnetworkInstalled
				&& $arParams['ENTITY_TYPE_ID'] == 'BLOG_COMMENT'
				&& CModule::IncludeModule('blog')
				&& ($arBlogComment = CBlogComment::GetByID($arParams['ENTITY_ID']))
			) // AUX
			{
				$handlerManager = new Bitrix\Socialnetwork\CommentAux\HandlerManager();
				/** @var bool|object $handler */
				if($handler = $handlerManager->getHandlerByPostText($arBlogComment["POST_TEXT"]))
				{
					$handler->setOptions(array(
						'im' => true
					));
					$handler->sendRatingNotification($arBlogComment, $arParams);
					return true;
				}
			}

			if (
				!CModule::IncludeModule("search")
				|| BX_SEARCH_VERSION <= 1
			)
			{
				return false;
			}

			$CSI = new CSearchItem;

			$arFSearch = Array('=ENTITY_TYPE_ID' => $arParams['ENTITY_TYPE_ID'], '=ENTITY_ID' => $arParams['ENTITY_ID']);
			if (
				defined("SITE_ID")
				&& strlen(SITE_ID) > 0
			)
			{
				$arFSearch["=SITE_ID"] = SITE_ID;
			}

			$res = $CSI->GetList(Array(), $arFSearch, Array('ID', 'URL', 'TITLE', 'BODY', 'PARAM1'));
			if ($arItem = $res->GetNext(true, false))
			{
				// notify mentioned users
				$arMentionedUserID = array();
				$arSearchItemParams = CSearch::GetContentItemParams($arItem['ID'], 'mentioned_user_id');
				if (
					is_array($arSearchItemParams)
					&& array_key_exists('mentioned_user_id', $arSearchItemParams)
					&& is_array($arSearchItemParams['mentioned_user_id'])
				)
				{
					$arMentionedUserID = $arSearchItemParams['mentioned_user_id'];
				}

				// send to author
				if (
					$arParams['OWNER_ID'] != $arParams['USER_ID']
					|| !empty($arMentionedUserID)
				)
				{
					$arParams["ENTITY_LINK"] = $arItem['URL'];
					$arParams["ENTITY_PARAM"] = $arItem['PARAM1'];
					$arParams["ENTITY_TITLE"] = trim(strip_tags(str_replace(array("\r\n","\n","\r"), ' ', $arItem['TITLE'])));
					$arParams["ENTITY_MESSAGE"] = trim(strip_tags(str_replace(array("\r\n","\n","\r"), ' ', $arItem['BODY'])));

					$arParams["ENTITY_BODY"] = preg_replace('/(.+?)(\r|\n)+.*/is'.BX_UTF_PCRE_MODIFIER,'\\1',$arItem['BODY']);

					if (
						(
							strlen($arParams["ENTITY_TITLE"]) > 0
							|| strlen($arParams["ENTITY_MESSAGE"]) > 0
						)
						&& strlen($arParams["ENTITY_LINK"]) > 0
					)
					{
						$originalLink = $arParams["ENTITY_LINK"];

						$bExtranetInstalled = CModule::IncludeModule("extranet");
						if ($bExtranetInstalled)
						{
							$arSites = array();
							$extranet_site_id = CExtranet::GetExtranetSiteID();
							$intranet_site_id = CSite::GetDefSite();
							$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));
							while($arSite = $dbSite->Fetch())
							{
								$arSites[$arSite["ID"]] = array(
									"DIR" => (strlen(trim($arSite["DIR"])) > 0 ? $arSite["DIR"] : "/"),
									"SERVER_NAME" => (strlen(trim($arSite["SERVER_NAME"])) > 0 ? $arSite["SERVER_NAME"] : COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]))
								);
							}
						}
						$bSentToOwner = false;
						if ($arParams['OWNER_ID'] != $arParams['USER_ID'])
						{
							$followValue = "Y";

							if ($bSocialnetworkInstalled)
							{
								$followValue = CSocNetLogFollow::GetExactValueByRating(
									intval($arParams['OWNER_ID']),
									trim($arParams["ENTITY_TYPE_ID"]),
									intval($arParams["ENTITY_ID"])
								);
							}

							if ($followValue != "N")
							{
								$arParams['ENTITY_LINK'] = self::GetMessageRatingEntityURL(
									$originalLink,
									intval($arParams['OWNER_ID']),
									$arSites,
									$intranet_site_id,
									$extranet_site_id
								);

								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => intval($arParams['OWNER_ID']),
									"FROM_USER_ID" => intval($arParams['USER_ID']),
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "main",
									"NOTIFY_EVENT" => "rating_vote",
									"NOTIFY_TAG" => "RATING|".($arParams['VALUE'] >= 0?"":"DL|").$arParams['ENTITY_TYPE_ID']."|".$arParams['ENTITY_ID'],
									"NOTIFY_MESSAGE" => self::GetMessageRatingVote($arParams),
									"NOTIFY_MESSAGE_OUT" => self::GetMessageRatingVote($arParams, true)
								);
								$bSentToOwner = CIMNotify::Add($arMessageFields);
							}
						}

						if (
							is_array($arMentionedUserID)
							&& $bSocialnetworkInstalled
						)
						{
							if (in_array($arParams['ENTITY_TYPE_ID'], array("BLOG_COMMENT", "FORUM_POST")))
							{
								$rsLogComment = CSocNetLogComments::GetList(
									array(),
									array(
										"RATING_TYPE_ID" => $arParams['ENTITY_TYPE_ID'],
										"RATING_ENTITY_ID" =>  $arParams['ENTITY_ID']
									),
									false,
									false,
									array("LOG_ID")
								);
								if ($arLogComment = $rsLogComment->Fetch())
								{
									$log_id = $arLogComment["LOG_ID"];
								}
							}
							elseif (in_array($arParams['ENTITY_TYPE_ID'], array("BLOG_POST")))
							{
								$rsLog = CSocNetLog::GetList(
									array(),
									array(
										"RATING_TYPE_ID" => $arParams['ENTITY_TYPE_ID'],
										"RATING_ENTITY_ID" =>  $arParams['ENTITY_ID']
									),
									false,
									false,
									array("ID")
								);
								if ($arLog = $rsLog->Fetch())
								{
									$log_id = $arLog["ID"];
								}
							}

							if (intval($log_id) > 0)
							{
								$arParams["MENTION"] = true; // for self::GetMessageRatingVote()

								foreach ($arMentionedUserID as $mentioned_user_id)
								{
									if (
										$mentioned_user_id != $arParams['USER_ID']
										&& ($mentioned_user_id != $arParams['OWNER_ID'] || !$bSentToOwner)
										&& CSocNetLogRights::CheckForUserOnly($log_id, $mentioned_user_id)
									)
									{
										$followValue = "Y";

										if ($bSocialnetworkInstalled)
										{
											$followValue = CSocNetLogFollow::GetExactValueByRating(
												intval($mentioned_user_id),
												trim($arParams["ENTITY_TYPE_ID"]),
												intval($arParams["ENTITY_ID"])
											);
										}

										if ($followValue != "N")
										{
											$arParams['ENTITY_LINK'] = self::GetMessageRatingEntityURL(
												$originalLink,
												intval($mentioned_user_id),
												$arSites,
												$intranet_site_id,
												$extranet_site_id
											);

											$arMessageFields = array(
												"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
												"TO_USER_ID" => intval($mentioned_user_id),
												"FROM_USER_ID" => intval($arParams['USER_ID']),
												"NOTIFY_TYPE" => IM_NOTIFY_FROM,
												"NOTIFY_MODULE" => "main",
												"NOTIFY_EVENT" => "rating_vote_mentioned",
												"NOTIFY_TAG" => "RATING_MENTION|".($arParams['VALUE'] >= 0?"":"DL|").$arParams['ENTITY_TYPE_ID']."|".$arParams['ENTITY_ID'],
												"NOTIFY_MESSAGE" => self::GetMessageRatingVote($arParams),
												"NOTIFY_MESSAGE_OUT" => self::GetMessageRatingVote($arParams, true)
											);

											CIMNotify::Add($arMessageFields);
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public static function OnCancelRatingVote($id, $arParams)
	{
		CIMNotify::DeleteByTag("RATING|".$arParams['ENTITY_TYPE_ID']."|".$arParams['ENTITY_ID'], $arParams['USER_ID']);
	}

	public static function GetMessageRatingVote($arParams, $bForMail = false)
	{
		$like = $arParams['VALUE'] >= 0? '_LIKE': '_DISLIKE';

		foreach(\Bitrix\Main\EventManager::getInstance()->findEventHandlers("im", "OnGetMessageRatingVote") as $event)
		{
			ExecuteModuleEventEx($event, array(&$arParams, &$bForMail));
		}

		if(isset($arParams['MESSAGE'])) // message was generated manually inside OnGetMessageRatingVote
		{
			return $arParams['MESSAGE'];
		}

		if (
			$arParams['ENTITY_TYPE_ID'] == 'FORUM_POST'
			|| $arParams['ENTITY_TYPE_ID'] == 'BLOG_COMMENT'
		)
		{
			$dot = strlen($arParams["ENTITY_MESSAGE"])>=200? '...': '';
			$arParams["ENTITY_MESSAGE"] = substr($arParams["ENTITY_MESSAGE"], 0, 199).$dot;
		}
		else
		{
			$dot = strlen($arParams["ENTITY_TITLE"])>=200? '...': '';
			$arParams["ENTITY_TITLE"] = substr($arParams["ENTITY_TITLE"], 0, 199).$dot;
		}

		if ($bForMail)
		{
			if ($arParams['ENTITY_TYPE_ID'] == 'BLOG_POST')
			{
				$message = str_replace('#LINK#', $arParams["ENTITY_TITLE"], GetMessage('IM_EVENT_RATING_BLOG_POST'.($arParams['MENTION'] ? '_MENTION' : '').$like).' ('.$arParams['ENTITY_LINK'].')');
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'BLOG_COMMENT')
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_MESSAGE"], '', ''), GetMessage('IM_EVENT_RATING_COMMENT'.($arParams['MENTION'] ? '_MENTION' : '').$like).' ('.$arParams['ENTITY_LINK'].')');
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'FORUM_TOPIC')
			{
				$message = str_replace('#LINK#', $arParams["ENTITY_TITLE"], GetMessage('IM_EVENT_RATING_FORUM_TOPIC'.$like).' ('.$arParams['ENTITY_LINK'].')');
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'FORUM_POST')
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_MESSAGE"], '', ''), GetMessage('IM_EVENT_RATING_COMMENT'.($arParams['MENTION'] ? '_MENTION' : '').$like).' ('.$arParams['ENTITY_LINK'].')');
			}
			elseif (
				$arParams['ENTITY_TYPE_ID'] == 'IBLOCK_ELEMENT'
				&& $arParams['ENTITY_PARAM'] == 'library'
			)
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_TITLE"], '', ''), GetMessage('IM_EVENT_RATING_FILE'.$like).' ('.$arParams['ENTITY_LINK'].')');
			}
			elseif (
				$arParams['ENTITY_TYPE_ID'] == 'IBLOCK_ELEMENT'
				&& $arParams['ENTITY_PARAM'] == 'photos'
			)
			{
				if (isset($arParams["ENTITY_BODY"]))
				{
					$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_BODY"], '', ''), GetMessage('IM_EVENT_RATING_PHOTO'.$like).' ('.$arParams['ENTITY_LINK'].')');
				}
				elseif (is_numeric($arParams["ENTITY_TITLE"]))
				{
					$message = str_replace(Array('#A_START#', '#A_END#'), Array('', ''), GetMessage('IM_EVENT_RATING_PHOTO1'.$like).' ('.$arParams['ENTITY_LINK'].')');
				}
				else
				{
					$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_TITLE"], '', ''), GetMessage('IM_EVENT_RATING_PHOTO'.$like).' ('.$arParams['ENTITY_LINK'].')');
				}
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'LOG_COMMENT')
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_TITLE"], '', ''), GetMessage('IM_EVENT_RATING_COMMENT'.($arParams['MENTION'] ? '_MENTION' : '').$like).' ('.$arParams['ENTITY_LINK'].')');
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'LISTS_NEW_ELEMENT')
			{
				$message = str_replace(
					array(
						'#TITLE#',
						'#A_START#',
						'#A_END#'
					),
					array(
						$arParams["ENTITY_TITLE"],
						'',
						''
					),
					GetMessage('IM_EVENT_RATING_LISTS_NEW_ELEMENT_LIKE'.$like)
				);
			}
			else
			{
				$message = str_replace('#LINK#', $arParams["ENTITY_TITLE"], GetMessage('IM_EVENT_RATING_ELSE'.$like).strlen($arParams['ENTITY_LINK'])>0?' ('.$arParams['ENTITY_LINK'].')': '');
			}
		}
		else
		{
			if ($arParams['ENTITY_TYPE_ID'] == 'BLOG_POST')
			{
				$message = str_replace('#LINK#', '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">'.$arParams["ENTITY_TITLE"].'</a>', GetMessage('IM_EVENT_RATING_BLOG_POST'.($arParams['MENTION'] ? '_MENTION' : '').$like));
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'BLOG_COMMENT')
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_MESSAGE"], '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_COMMENT'.($arParams['MENTION'] ? '_MENTION' : '').$like));
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'FORUM_TOPIC')
			{
				$message = str_replace('#LINK#', '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">'.$arParams["ENTITY_TITLE"].'</a>', GetMessage('IM_EVENT_RATING_FORUM_TOPIC'.$like));
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'FORUM_POST')
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_MESSAGE"], '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_COMMENT'.($arParams['MENTION'] ? '_MENTION' : '').$like));
			}
			elseif (
				$arParams['ENTITY_TYPE_ID'] == 'IBLOCK_ELEMENT'
				&& $arParams['ENTITY_PARAM'] == 'library'
			)
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_TITLE"], '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_FILE'.$like));
			}
			elseif (
				$arParams['ENTITY_TYPE_ID'] == 'IBLOCK_ELEMENT'
				&& $arParams['ENTITY_PARAM'] == 'photos'
			)
			{
				if (isset($arParams["ENTITY_BODY"]))
				{
					$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_BODY"], '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_PHOTO'.$like));
				}
				elseif (is_numeric($arParams["ENTITY_TITLE"]))
				{
					$message = str_replace(Array('#A_START#', '#A_END#'), Array('<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_PHOTO1'.$like));
				}
				else
				{
					$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_TITLE"], '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_PHOTO'.$like));
				}
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'LOG_COMMENT')
			{
				$message = str_replace(Array('#TITLE#', '#A_START#', '#A_END#'), Array($arParams["ENTITY_TITLE"], '<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">', '</a>'), GetMessage('IM_EVENT_RATING_COMMENT'.($arParams['MENTION'] ? '_MENTION' : '').$like));
			}
			elseif ($arParams['ENTITY_TYPE_ID'] == 'LISTS_NEW_ELEMENT')
			{
				$message = str_replace(
					array(
						'#TITLE#',
						'#A_START#',
						'#A_END#'
					),
					array(
						$arParams["ENTITY_TITLE"],
						'<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">',
						'</a>'
					),
					GetMessage('IM_EVENT_RATING_LISTS_NEW_ELEMENT'.$like)
				);
			}
			else
			{
				$message = str_replace('#LINK#', strlen($arParams['ENTITY_LINK'])>0?'<a href="'.$arParams['ENTITY_LINK'].'" class="bx-notifier-item-action">'.$arParams["ENTITY_TITLE"].'</a>': '<i>'.$arParams["ENTITY_TITLE"].'</i>', GetMessage('IM_EVENT_RATING_ELSE'.$like));
			}
		}
		return $message;
	}

	public static function GetMessageRatingEntityURL($url, $user_id = false, $arSites = false, $intranet_site_id = false, $extranet_site_id = false)
	{
		static $arSiteData = false;

		if (
			!$arSiteData
			&& IsModuleInstalled('intranet')
			&& CModule::IncludeModule('socialnetwork')
		)
		{
			$arSiteData = CSocNetLogTools::GetSiteData();
		}

		if (
			$arSiteData
			&& count($arSiteData) > 1
		)
		{
			foreach($arSiteData as $siteId => $arUrl)
			{
				$url = str_replace($arUrl["USER_PATH"], "#USER_PATH#", $url);
			}

			$arTmp = CSocNetLogTools::ProcessPath(
				array(
					"URL" => $url
				),
				$user_id
			);

			$url = $arTmp["URLS"]["URL"];
			$url = (
				strpos($url, "http://") === 0
				|| strpos($url, "https://") === 0
					? ""
					: (
						isset($arTmp["SERVER_NAME"])
						&& !empty($arTmp["SERVER_NAME"])
							? $arTmp["SERVER_NAME"]
							: ""
					)
			).$arTmp["URLS"]["URL"];
		}
		else
		{
			if (
				is_array($arSites)
				&& intval($user_id) > 0
				&& strlen($extranet_site_id) > 0
				&& strlen($intranet_site_id) > 0
			)
			{
				$bExtranetUser = false;
				if ($arSites[$extranet_site_id])
				{
					$bExtranetUser = true;
					$rsUser = CUser::GetByID(intval($user_id));
					if ($arUser = $rsUser->Fetch())
					{
						if (intval($arUser["UF_DEPARTMENT"][0]) > 0)
						{
							$bExtranetUser = false;
						}
					}
				}

				if ($bExtranetUser)
				{
					$link = $url;
					if (substr($link, 0, strlen($arSites[$extranet_site_id]['DIR'])) == $arSites[$extranet_site_id]['DIR'])
					{
						$link = substr($link, strlen($arSites[$extranet_site_id]['DIR']));
					}

					$SiteServerName = $arSites[$extranet_site_id]['SERVER_NAME'].$arSites[$extranet_site_id]['DIR'].ltrim($link, "/");
				}
				else
				{
					$link = $url;
					if (substr($link, 0, strlen($arSites[$intranet_site_id]['DIR'])) == $arSites[$intranet_site_id]['DIR'])
					{
						$link = substr($link, strlen($arSites[$intranet_site_id]['DIR']));
					}

					$SiteServerName = $arSites[$intranet_site_id]['SERVER_NAME'].$arSites[$intranet_site_id]['DIR'].ltrim($link, "/");
				}

				$url = (CMain::IsHTTPS() ? "https" : "http")."://".$SiteServerName;
			}
			else
			{
				$SiteServerName = (defined('SITE_SERVER_NAME') && strlen(SITE_SERVER_NAME) > 0 ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", $_SERVER['SERVER_NAME']));
				if (strlen($SiteServerName) > 0)
				{
					$url = (CMain::IsHTTPS() ? "https" : "http")."://".$SiteServerName.$url;
				}
			}
		}

		return $url;
	}

	private static function GetMessageRatingLogCommentURL($arComment, $user_id = false, $arSites = false, $intranet_site_id = false, $extranet_site_id = false)
	{
		$url = false;

		if (
			!is_array($arComment)
			|| !isset($arComment["ENTITY_TYPE"]) || strlen($arComment["ENTITY_TYPE"]) <= 0
			|| !isset($arComment["ID"]) || intval($arComment["ID"]) <= 0
			|| !isset($arComment["LOG_ID"]) || intval($arComment["LOG_ID"]) <= 0
		)
		{
			return false;
		}

		if (
			is_array($arSites)
			&& intval($user_id) > 0
			&& strlen($extranet_site_id) > 0
			&& strlen($intranet_site_id) > 0
		)
		{
			$bExtranetUser = false;
			if ($arSites[$extranet_site_id])
			{
				$bExtranetUser = true;
				$rsUser = CUser::GetByID($user_id);
				if ($arUser = $rsUser->Fetch())
				{
					if (intval($arUser["UF_DEPARTMENT"][0]) > 0)
					{
						$bExtranetUser = false;
					}
				}
			}

			$user_site_id = ($bExtranetUser ? $extranet_site_id : $intranet_site_id);

			$url = (in_array($arComment["ENTITY_TYPE"], array("CRMLEAD", "CRMCONTACT", "CRMCOMPANY", "CRMDEAL", "CRMACTIVITY")) ? $arSites[$user_site_id]["DIR"]."crm/stream?log_id=#log_id#" : COption::GetOptionString("socialnetwork", "log_entry_page", $arSites[$user_site_id]["DIR"]."company/personal/log/#log_id#/", $user_site_id));
			$url = str_replace("#log_id#", $arComment["LOG_ID"], $url);
			$url .= (strpos($url, "?") !== false ? "&" : "?")."commentId=".$arComment["ID"]."#com".$arComment["ID"];
			$url = (CMain::IsHTTPS() ? "https" : "http")."://".$arSites[$user_site_id]['SERVER_NAME'].$url;
		}
		else
		{
			$url = (in_array($arComment["ENTITY_TYPE"], array("CRMLEAD", "CRMCONTACT", "CRMCOMPANY", "CRMDEAL", "CRMACTIVITY")) ? SITE_DIR."crm/stream?log_id=#log_id#" : COption::GetOptionString("socialnetwork", "log_entry_page", SITE_DIR."company/personal/log/#log_id#/", SITE_ID));
			$url = str_replace("#log_id#", $arComment["LOG_ID"], $url);
			$url .= (strpos($url, "?") !== false ? "&" : "?")."commentId=".$arComment["ID"]."#com".$arComment["ID"];

			$SiteServerName = (defined('SITE_SERVER_NAME') && strlen(SITE_SERVER_NAME) > 0 ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", $_SERVER['SERVER_NAME']));
			if (strlen($SiteServerName) > 0)
			{
				$url = (CMain::IsHTTPS() ? "https" : "http")."://".$SiteServerName.$url;
			}
		}

		return $url;
	}

	public static function OnAfterUserAdd($arParams)
	{
		if($arParams["ID"] <= 0)
			return false;

		if ($arParams['ACTIVE'] == 'N')
			return false;

		if (!CIMContactList::IsExtranet($arParams))
		{
			$commonChatId = CIMChat::GetGeneralChatId();
			if ($commonChatId <= 0)
				return true;

			if (\Bitrix\Im\User::getInstance($arParams["ID"])->isBot())
				return true;

			if (!CIMChat::CanJoinGeneralChatId($arParams["ID"]))
				return true;

			$CIMChat = new CIMChat(0);
			$CIMChat->AddUser($commonChatId, Array($arParams["ID"]));
		}

		return true;
	}

	public static function OnAfterUserUpdate($arParams)
	{
		$commonChatId = CIMChat::GetGeneralChatId();
		if ($commonChatId > 0 && (isset($arParams['ACTIVE']) || isset($arParams['UF_DEPARTMENT'])))
		{
			if ($arParams['ACTIVE'] == 'N')
			{
				CIMMessage::SetReadMessageAll($arParams['ID']);

				if ($commonChatId && CIMChat::GetRelationById($commonChatId, $arParams["ID"]))
				{
					$CIMChat = new CIMChat($arParams["ID"]);
					$CIMChat->DeleteUser($commonChatId, $arParams["ID"]);
				}
			}
			else
			{
				$commonChatId = CIMChat::GetGeneralChatId();
				if ($commonChatId)
				{
					if (\Bitrix\Im\User::getInstance($arParams["ID"])->isBot())
						return true;

					$userInChat = CIMChat::GetRelationById($commonChatId, $arParams["ID"]);
					$userCanJoin = CIMChat::CanJoinGeneralChatId($arParams["ID"]);

					if ($userInChat && !$userCanJoin)
					{
						$CIMChat = new CIMChat($arParams["ID"]);
						$CIMChat->DeleteUser($commonChatId, $arParams["ID"]);
					}
					else if (!$userInChat && $userCanJoin)
					{
						$CIMChat = new CIMChat(0);
						$CIMChat->AddUser($commonChatId, Array($arParams["ID"]));
					}
				}
			}
		}
	}

	public static function OnUserDelete($ID)
	{
		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		global $DB;

		$arChat = Array();
		$strSQL = "
			SELECT R.CHAT_ID
			FROM b_im_chat C, b_im_relation R
			WHERE R.USER_ID = ".$ID." and R.MESSAGE_TYPE IN ('".IM_MESSAGE_PRIVATE."', '".IM_MESSAGE_SYSTEM."') and R.CHAT_ID = C.ID
		";
		$dbRes = $DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			$arChat[$arRes['CHAT_ID']] = $arRes['CHAT_ID'];

		if (count($arChat) > 0)
		{
			$strSQL = "DELETE FROM b_im_chat WHERE ID IN (".implode(',', $arChat).")";
			$DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		\Bitrix\Im\Bot::unRegister(Array('BOT_ID' => $ID));

		$strSQL = "DELETE FROM b_im_message WHERE AUTHOR_ID = ".$ID;
		$DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);

		$strSQL = "DELETE FROM b_im_relation WHERE USER_ID =".$ID;
		$DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);

		$strSQL = "DELETE FROM b_im_recent WHERE USER_ID = ".$ID;
		$DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);

		$strSQL = "DELETE FROM b_im_status WHERE USER_ID = ".$ID;
		$DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);

		$obCache = new CPHPCache();
		if (IsModuleInstalled('bitrix24'))
		{
			$relationList = IM\Model\RecentTable::getList(array(
				"select" => array("USER_ID"),
				"filter" => array(
					"=ITEM_TYPE" => 'P',
					"=ITEM_ID" => $ID,
				),
			));
			while ($relation = $relationList->fetch())
			{
				$obCache->CleanDir('/bx/imc/recent'.CIMMessenger::GetCachePath($relation['USER_ID']));
			}
		}
		else
		{
			$obCache->CleanDir('/bx/imc/recent');
		}

		$strSQL = "DELETE FROM b_im_recent WHERE ITEM_TYPE = 'P' and ITEM_ID = ".$ID;
		$DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function OnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "im",
			'USE' => Array("PUBLIC_SECTION")
		);
	}
}

class DesktopApplication extends Bitrix\Main\Authentication\Application
{
	protected $validUrls = array(
		"/desktop_app/",
		"/online/",
		"/bitrix/tools/disk/",
		"/bitrix/services/disk/index.php"
	);

	public static function OnApplicationsBuildList()
	{
		return array(
			"ID" => "desktop",
			"NAME" => GetMessage('DESKTOP_APPLICATION_NAME'),
			"DESCRIPTION" => GetMessage("DESKTOP_APPLICATION_DESC"),
			"SORT" => 80,
			"CLASS" => "DesktopApplication",
		);
	}
}
?>