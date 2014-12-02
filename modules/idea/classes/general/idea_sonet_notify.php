<?
//Include Lang
IncludeModuleLangFile(__FILE__);

//System, not for use
Class CIdeaManagmentSonetNotify
{
	private $Notify = NULL;
	private static $Enable = true;

	public function __construct($parent)
	{
		$this->Notify = $parent;
	}

	public function IsAvailable()
	{
		return CModule::IncludeModule('socialnetwork') && CModule::IncludeModule('blog') && NULL!=$this->Notify && self::$Enable;
	}

	/*
	 * Not for USE Can be changed
	 */
	public static function AddLogEvent(&$arFields)
	{
		$arFields["idea"]= array(
			'ENTITIES' => array(
				SONET_SUBSCRIBE_ENTITY_USER => array(
					'TITLE' => GetMessage("IDEA_SONET_NOTIFY_TITLE"),
					'TITLE_SETTINGS' => GetMessage('IDEA_SONET_GROUP_SETTINGS'),
					'TITLE_SETTINGS_1' => GetMessage('IDEA_SONET_GROUP_SETTINGS_1'),
					'TITLE_SETTINGS_2' => GetMessage('IDEA_SONET_GROUP_SETTINGS_2')
				),
			),
			'CLASS_FORMAT'   => __CLASS__,
			'METHOD_FORMAT'   => 'FormatMessage',
			'FULL_SET' => array('idea', 'idea_comment'),
			'COMMENT_EVENT' => array(
				'EVENT_ID' => 'idea_comment',
				'CLASS_FORMAT' => __CLASS__,
				'METHOD_FORMAT' => 'FormatComment',
				'ADD_CALLBACK'  =>  array(__CLASS__, 'CallBack_AddComment'),
				'UPDATE_CALLBACK'  =>  array(__CLASS__, 'CallBack_UpdateComment'),
				'DELETE_CALLBACK'  =>  array(__CLASS__, 'CallBack_DeleteComment'),
			)
		);
	}

	/*
	 * Not for USE Can be changed
	 */
	public static function CallBack_AddComment($arFields)
	{
		if(!CModule::IncludeModule('blog'))
			return false;

		$arResult = array();

		$arLog = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array("TMP_ID" => $arFields["LOG_ID"]),
				false,
				false,
				array("ID", "SOURCE_ID", "SITE_ID", "RATING_ENTITY_ID")
		)->Fetch();

		if($arLog)
		{
			$arIdeaPost = CBlogPost::GetById($arLog["SOURCE_ID"]);
			if($arIdeaPost)
			{
				$UserIP = CBlogUser::GetUserIP();
				$arBlogCommentFields = array(
					"BLOG_ID" => $arIdeaPost["BLOG_ID"],
					"POST_ID" => $arIdeaPost["ID"],
					"AUTHOR_ID" => $arFields["USER_ID"],
					"POST_TEXT" => $arFields["TEXT_MESSAGE"],
					"DATE_CREATE" => ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL"),
					"PARENT_ID" => false,
					"AUTHOR_IP" => $UserIP[0],
					"AUTHOR_IP1" => $UserIP[1],
				);

				if (
					isset($arFields["UF_SONET_COM_DOC"])
					&& is_array($arFields["UF_SONET_COM_DOC"])
				)
				{
					$arBlogCommentFields["UF_BLOG_COMMENT_FILE"] = $arFields["UF_SONET_COM_DOC"];
				}

				$IdeaCommentId = CBlogComment::Add($arBlogCommentFields);

				$arResult = array(
					"SOURCE_ID" => $IdeaCommentId,
				);
				if($arLog["RATING_ENTITY_ID"]>0)
				{
					$arResult["RATING_TYPE_ID"] = "BLOG_COMMENT";
					$arResult["RATING_ENTITY_ID"] = $IdeaCommentId;
				}

				if(intval($IdeaCommentId)==0)
				{
					global $APPLICATION;
					if($ex = $APPLICATION->GetException())
						$arResult["ERROR"] = $ex->GetString();
				}
				else
				{
					//clear cache on succcess
					BXClearCache(True, "/".SITE_ID."/idea/".$arIdeaPost["BLOG_ID"]."/first_page/");
					BXClearCache(True, "/".SITE_ID."/idea/".$arIdeaPost["BLOG_ID"]."/pages/");
					BXClearCache(True, "/".SITE_ID."/idea/".$arIdeaPost["BLOG_ID"]."/comment/".$arIdeaPost["ID"]."/");
					BXClearCache(True, "/".SITE_ID."/idea/".$arIdeaPost["BLOG_ID"]."/post/".$arIdeaPost["ID"]."/");
				}
			}
		}

		return $arResult;
	}

	public static function CallBack_UpdateComment($arFields)
	{
		if(!CModule::IncludeModule('blog'))
		{
			return false;
		}

		if (
			!isset($arFields["SOURCE_ID"])
			|| intval($arFields["SOURCE_ID"]) <= 0
		)
		{
			return false;
		}

		$messageId = intval($arFields["SOURCE_ID"]);

		$ufFileID = array();
		$ufDocID = array();

		if ($arBlogComment = CBlogComment::GetByID($messageId))
		{
			$arBlogCommentFields = array(
				"POST_TEXT" => $arFields["TEXT_MESSAGE"]
			);

			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
			if (is_array($arTmp))
			{
				if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
				{
					$arBlogCommentFields["UF_BLOG_COMMENT_FILE"] = $arTmp["UF_SONET_COM_DOC"];
				}
			}

			if ($messageId = CBlogComment::Update($messageId, $arBlogCommentFields))
			{
				$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("BLOG_COMMENT", "UF_BLOG_COMMENT_FILE", $messageId, LANGUAGE_ID);
				$sNote = GetMessage("IDEA_SONET_UPDATE_COMMENT_SOURCE_SUCCESS");

				$cache = new CPHPCache;
				$cache->CleanDir(SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/comment/".$arBlogComment["POST_ID"]."/");

				BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/first_page/");
				BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/pages/");
				BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/comment/".$arBlogComment["POST_ID"]."/");
				BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/post/".$arBlogComment["POST_ID"]."/");
			}
			else
			{
				if ($ex = $GLOBALS["APPLICATION"]->GetException())
				{
					$sError = $ex->GetString();
				}
				else
				{
					$sError = GetMessage("IDEA_SONET_UPDATE_COMMENT_SOURCE_ERROR");
				}
			}
		}
		else
		{
			$sError = GetMessage("IDEA_SONET_UPDATE_COMMENT_SOURCE_ERROR");
		}

		return array(
			"ERROR" => $sError,
			"NOTES" => $sNote,
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID
			)
		);
	}

	public static function CallBack_DeleteComment($arFields)
	{
		if (!CModule::IncludeModule("blog"))
		{
			return false;
		}

		if (
			!isset($arFields["SOURCE_ID"])
			|| intval($arFields["SOURCE_ID"]) <= 0
		)
		{
			return false;
		}

		$messageId = intval($arFields["SOURCE_ID"]);

		if (
			($arBlogComment = CBlogComment::GetByID($messageId))
			&& CBlogComment::Delete($messageId)
		)
		{
			$strOKMessage = GetMessage("IDEA_SONET_DELETE_COMMENT_SOURCE_SUCCESS");

			$cache = new CPHPCache;
			$cache->CleanDir(SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/comment/".$arBlogComment["POST_ID"]."/");

			BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/first_page/");
			BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/pages/");
			BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/comment/".$arBlogComment["POST_ID"]."/");
			BXClearCache(True, "/".SITE_ID."/idea/".$arBlogComment["BLOG_ID"]."/post/".$arBlogComment["POST_ID"]."/");
		}
		else
		{
			$strErrorMessage = GetMessage("IDEA_SONET_DELETE_COMMENT_SOURCE_ERROR");
		}

		return array(
			"ERROR" => $strErrorMessage,
			"NOTES" => $strOKMessage
		);
	}	

	/*
	 * Not for USE Can be changed
	 * Alias
	 */
	public static function FormatComment($arFields, $arParams, $bMail = false, $arLog = array())
	{
		return CSocNetLogTools::FormatComment_Blog($arFields, $arParams, $bMail, $arLog);
	}

	/*
	 * Not for USE Can be changed
	 * Alias
	 */
	public static function FormatMessage($arFields, $arParams, $bMail = false)
	{
		$arResult = CSocNetLogTools::FormatEvent_Blog($arFields, $arParams, $bMail);
		$arResult["EVENT_FORMATTED"]["TITLE_24"] = GetMessage("IDEA_SONET_NOTIFY_TITLE_24");
		return $arResult;
	}

	/*
	 * Not for USE Can be changed
	 */
	private function AddMessage()
	{
		global $DB;
		$arNotification = $this->Notify->getNotification();

		$arNotify = Array(
			"EVENT_ID" => "idea",
			"=LOG_DATE" => $DB->CurrentTimeFunction(),
			"URL" => $arNotification["PATH"],
			"TITLE" => $arNotification["TITLE"],
			"TITLE_24" => $arNotification["TITLE_24"],
			"MESSAGE" => $arNotification["DETAIL_TEXT"],
			"CALLBACK_FUNC" => false,
			"SOURCE_ID" => $arNotification["ID"],
			"SITE_ID" => SITE_ID,
			"ENABLE_COMMENTS" => "Y",
			"ENTITY_TYPE" => SONET_ENTITY_USER,
			"ENTITY_ID" => $arNotification["AUTHOR_ID"],
			"USER_ID" => $arNotification["AUTHOR_ID"],
			"MODULE_ID" => 'idea',
		);

		//Use rating
		if($arNotification["SHOW_RATING"] == "Y")
		{
			$arNotify["RATING_ENTITY_ID"] = $arNotification["ID"];
			$arNotify["RATING_TYPE_ID"] = "BLOG_POST";
		}

		if($arNotification["ACTION"] == "ADD")
		{
			$LogID = CSocNetLog::Add($arNotify, false);
			if (intval($LogID) > 0)
			{
				CSocNetLog::Update($LogID, array("TMP_ID" => $LogID));
				CSocNetLogRights::Add($LogID, array("G2")); //G2 - everyone
			}
		}
		elseif($arNotification["ACTION"] == "UPDATE")
		{
			$arLog = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"ENTITY_TYPE" => SONET_ENTITY_USER,
					"EVENT_ID" => "idea",
					"SOURCE_ID" => $arNotification["ID"]
				),
				false,
				false,
				array("ID")
			)->Fetch();
			if($arLog)
			{
				$LogID = $arLog["ID"];
				CSocNetLog::Update($LogID, $arNotify);
			}
		}

		return $LogID>0;
	}

	/*
	 * Not for USE Can be changed
	 */
	private function AddComment()
	{
		global $DB;
		$arNotification = $this->Notify->getNotification();

		$arLog = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"ENTITY_TYPE" => SONET_ENTITY_USER,
				"EVENT_ID" => "idea",
				"SOURCE_ID" => $arNotification["POST_ID"]),
			false,
			false,
			array("ID", "RATING_ENTITY_ID")
		)->Fetch();

		if($arLog)
		{
			$arNotify = Array(
				"EVENT_ID" => "idea_comment",
				"URL" => $arNotification["PATH"],
				"MESSAGE" => $arNotification["POST_TEXT"],
				"SOURCE_ID" => $arNotification["ID"],
				"ENTITY_TYPE" => SONET_ENTITY_USER,
				"ENTITY_ID" => $arNotification["AUTHOR_ID"],
				"USER_ID" => $arNotification["AUTHOR_ID"],
				"MODULE_ID" => 'idea',
				"LOG_ID" => $arLog["ID"],
			);

			if (isset($arNotification["LOG_DATE"]))
			{
				$arNotify["LOG_DATE"] = $arNotification["LOG_DATE"];
			}
			else
			{
				$arNotify["=LOG_DATE"] = $DB->CurrentTimeFunction();
			}

			if($arLog["RATING_ENTITY_ID"]>0)
			{
				$arNotify["RATING_ENTITY_ID"] = $arNotification["ID"];
				$arNotify["RATING_TYPE_ID"] = "BLOG_COMMENT";
			}

			if($arNotification["ACTION"] == "ADD")
			{
				$LogCommentID = CSocNetLogComments::Add($arNotify, false, false);
				CSocNetLog::CounterIncrement($LogCommentID, false, false, "LC");
			}
			elseif($arNotification["ACTION"] == "UPDATE")
			{
				$arLogComment = CSocNetLogComments::GetList(
					array("ID" => "DESC"),
					array(
						"ENTITY_TYPE" => SONET_ENTITY_USER,
						"EVENT_ID" => "idea_comment",
						"SOURCE_ID" => $arNotification["ID"]),
					false,
					false,
					array("ID")
				)->Fetch();

				if($arLogComment)
				{
					unset($arNotify["USER_ID"]);
					$LogCommentID = CSocNetLogComments::Update($arLogComment["ID"], $arNotify);
				}
			}
		}

		return $LogCommentID>0;
	}

	public function Send()
	{
		if(!$this->IsAvailable())
			return false;

		$arNotification = $this->Notify->getNotification();
		if($arNotification["TYPE"] == 'IDEA')
			return $this->AddMessage();
		elseif($arNotification["TYPE"] == 'IDEA_COMMENT')
			return $this->AddComment();

		return false;
	}

	public function HideMessage()
	{
		if(!$this->IsAvailable())
			return false;

		$arNotification = $this->Notify->getNotification();

		$oLog = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID" => 'idea',
				"SOURCE_ID" => $arNotification["ID"]
			),
			false,
			false,
			array("ID", "USER_ID")
		);
		while ($arLog = $oLog->Fetch())
		{
			CSocNetLogRights::DeleteByLogID($arLog["ID"]);
			CSocNetLogRights::Add($arLog["ID"], array("SA", "U".$arLog["USER_ID"]));
		}

		return false;
	}

	public function ShowMessage()
	{
		if(!$this->IsAvailable())
			return false;

		$arNotification = $this->Notify->getNotification();

		$oLog = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"EVENT_ID" => 'idea',
				"SOURCE_ID" => $arNotification["ID"]
			),
			false,
			false,
			array("ID")
		);
		while ($arLog = $oLog->Fetch())
		{
			CSocNetLogRights::DeleteByLogID($arLog["ID"]);
			CSocNetLogRights::Add($arLog["ID"], array("G2"));
		}

		return false;
	}

	/*
	 * Not for USE Can be changed
	 */
	private function RemoveComment($CommentId = false)
	{
		$arNotification = $this->Notify->getNotification();
		$oLogComment = CSocNetLogComments::GetList(
			array("ID" => "DESC"),
			array(
				"ENTITY_TYPE" => SONET_ENTITY_USER,
				"EVENT_ID" => 'idea_comment',
				"SOURCE_ID" => $CommentId?$CommentId:$arNotification["ID"]
			),
			false,
			false,
			array("ID")
		);
		while($arLogComment = $oLogComment->Fetch())
			CSocNetLogComments::Delete($arLogComment["ID"]);
	}
	/*
	 * Not for USE Can be changed
	 */
	private function RemoveMessage($MessageId = false)
	{
		$arNotification = $this->Notify->getNotification();

		//Remove comments
		$oComment = CBlogComment::GetList(
			array(),
			array(
				"POST_ID" => $MessageId?$MessageId:$arNotification["ID"],
			),
			false,
			false,
			array("ID")
		);
		while ($arComment = $oComment->Fetch())
			$this->RemoveComment($arComment["ID"]);

		//Remove message
		$oLogMessage = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array(
				"ENTITY_TYPE" => SONET_ENTITY_USER,
				"EVENT_ID" => 'idea',
				"SOURCE_ID" => $MessageId?$MessageId:$arNotification["ID"]
			),
			false,
			false,
			array("ID")
		);
		while($arLogMessage = $oLogMessage->Fetch())
			CSocNetLog::Delete($arLogMessage["ID"]);
	}

	public function Remove()
	{
		if(!$this->IsAvailable())
			return false;

		$arNotification = $this->Notify->getNotification();
		if($arNotification["TYPE"] == 'IDEA')
			return $this->RemoveMessage();
		elseif($arNotification["TYPE"] == 'IDEA_COMMENT')
			return $this->RemoveComment();

		return false;
	}

	static public function Disable()
	{
		self::$Enable = false;
	}

	static public function Enable()
	{
		self::$Enable = true;
	}
}
?>