<?
IncludeModuleLangFile(__FILE__);
class CForumDBTools
{
	/*
	* RegisterModuleDependences('forum', 'CollectDBUpdater', 'forum', '', '');
	*/

	static function GetDBUpdaters()
	{
		$rsHandlers = GetModuleEvents("forum", "CollectDBUpdater");
		while($arHandler = $rsHandlers->Fetch())
		{
			ExecuteModuleEventEx($arHandler, array());
		}
	}

	static function Unregister($caller)
	{
		if (is_array($caller) && isset($caller['module']) && isset($caller['class']) && isset($caller['method']))
		{
			UnRegisterModuleDependences(
				'forum',
				'CollectDBUpdater',
				$caller['module'],
				$caller['class'],
				$caller['method']
			);
		}
	}

	static function ShowOffer($TRIGGER, $message)
	{
?>
		<div style='background-color:#ffffcc; border: 1px solid #ff8888; padding: 10px; margin: 5px 0; font-size:80%; '>
			<div style='float: right; width: 150px; margin-top:-5px;'>
				<form action="<?=POST_FORM_ACTION_URI?>" method="POST">
					<input type='hidden' name='<?=htmlspecialcharsbx($TRIGGER)?>' value='Y' />
						<?=bitrix_sessid_post()?>
					<input type='submit' value='<?=GetMessage('F_DB_GO')?>' />
				</form>
			</div>
			<?=htmlspecialcharsEx($message)?>
		</div>
<?
	}

	static function ShowMessage($message, $error = false)
	{
		$background = ($error ? '#ffcccc' : '#ccffcc');
?>
		<div style='background-color:<?=$background?>; border: 1px solid #ff8888; padding: 10px; margin: 5px 0; font-size:80%;'>
			<?=htmlspecialcharsEx($message)?>
		</div>
<?
	}

	static function Alter($arParams)
	{
		global $APPLICATION, $DB;
		$TRIGGER = $arParams['TRIGGER'];

		if (in_array($DB->type, array('MSSQL', 'ORACLE'))) // uppercase
		{
			$arUpperCaseFields = array('TABLE', 'FIELDS', 'INDEX', 'COMMAND');
			foreach($arUpperCaseFields as $fn)
			{
				if (isset($arParams[$fn]))
				{
					if (!is_array($arParams[$fn]))
						$arParams[$fn] = strtoupper($arParams[$fn]);
					else
						$arParams[$fn] = array_map('strtoupper', $arParams[$fn]);
				}
			}
		}

		$arMsgParams = array();
		foreach($arParams as $paramName => $paramValue)
		{
			if (is_array($paramValue))
			{
				$arMsgParams["#$paramName#"] = htmlspecialcharsbx(implode(', ', $paramValue));
			}
			else
			{
				$arMsgParams["#$paramName#"] = htmlspecialcharsbx($paramValue);
			}
		}

		$msg_success = GetMessage('F_DB_'.$arParams['mode'].'_SUCCESS', $arMsgParams);
		$msg_fail = GetMessage('F_DB_'.$arParams['mode'].'_FAIL', $arMsgParams);
		$msg_offer = GetMessage('F_DB_'.$arParams['mode'].'_OFFER', $arMsgParams);

		if (
				(
					($arParams['mode'] == 'DROPINDEX') &&
					$DB->TableExists($arParams['TABLE']) &&
					($DB->GetIndexName($arParams['TABLE'], $arParams['FIELDS'], true) !== '')
				) || (
					($arParams['mode'] == 'CREATEINDEX') &&
					$DB->TableExists($arParams['TABLE']) &&
					($DB->GetIndexName($arParams['TABLE'], $arParams['FIELDS'], true) === '')
				)
		)
		{
			if (isset($_REQUEST[$TRIGGER]) && check_bitrix_sessid())
			{
				if ($arParams['mode'] == 'DROPINDEX')
				{
					$indexName = $DB->GetIndexName($arParams['TABLE'], $arParams['FIELDS'], true);
					$arParams['COMMAND'] = str_replace($arParams['INDEX'], $indexName, $arParams['COMMAND']);
					if ($DB->type == 'ORACLE')
					{
						$arCommand = array_filter(explode(' ',$arParams['COMMAND']));
						$arOraCommand = array_slice($arCommand, 0, 3);
						$arParams['COMMAND'] = implode(' ', $arOraCommand);
					}
				}
				// semafor ?
				$result = $DB->Query($arParams['COMMAND'], true);
				if ($result !== false)
				{
					self::Unregister($arParams['caller']);
					self::ShowMessage($msg_success);
				}
				else
				{
					self::Unregister($arParams['caller']);
					self::ShowMessage($msg_fail, true);
				}
			}
			else
			{
				self::ShowOffer($TRIGGER, $msg_offer);
			}
		}
		else
		{
			// local setup differs from expected
			self::Unregister($arParams['caller']);
		}
		return;
	}

	static function DropIndex_message_TA()
	{
		global $DB;

		$TRIGGER = 'drop_mTA';
		$caller = array('module'=> 'forum', 'class'=>__CLASS__, 'method'=>__FUNCTION__);

		if ($DB->type == 'MYSQL')
		{
			$arParams = array(
				'TRIGGER' => $TRIGGER, 
				'mode' => 'DROPINDEX',
				'caller' => $caller,
				'INDEX' => 'IX_FORUM_MESSAGE_TOPIC',
				'TABLE' => 'b_forum_message',
				'FIELDS' => array('TOPIC_ID', 'APPROVED'),
				'COMMAND' => "drop index IX_FORUM_MESSAGE_TOPIC on b_forum_message"
			);
			self::Alter($arParams);
		}
		else
		{
			self::Unregister($caller);
		}
	}

	static function CreateIndex_message_TAI()
	{
		global $DB;

		$caller = array('module'=> 'forum', 'class'=>__CLASS__, 'method'=>__FUNCTION__);
		$TRIGGER = 'create_mTAI';

		if ($DB->type == 'MYSQL')
		{
			$arParams = array(
				'TRIGGER' => $TRIGGER, 
				'mode' => 'CREATEINDEX',
				'caller' => $caller,
				'INDEX' => 'IX_FORUM_MESSAGE_TOPIC_AI',
				'TABLE' => 'b_forum_message',
				'FIELDS' => array('TOPIC_ID', 'APPROVED', 'ID'),
				'COMMAND' => "create index IX_FORUM_MESSAGE_TOPIC_AI on b_forum_message(TOPIC_ID, APPROVED, ID)"
			);
			self::Alter($arParams);
		}
		else
		{
			self::Unregister($caller);
		}
	}

	static function DropIndex_message_AAF()
	{
		global $DB;

		$TRIGGER = 'drop_mAAF';
		$caller = array('module'=> 'forum', 'class'=>__CLASS__, 'method'=>__FUNCTION__);

		if ($DB->type == 'MYSQL')
		{
			$arParams = array(
				'TRIGGER' => $TRIGGER,
				'mode' => 'DROPINDEX',
				'caller' => $caller,
				'INDEX' => 'IX_FORUM_MESSAGE_AUTHOR',
				'TABLE' => 'b_forum_message',
				'FIELDS' => array('AUTHOR_ID', 'APPROVED', 'FORUM_ID'),
				'COMMAND' => "drop index IX_FORUM_MESSAGE_AUTHOR on b_forum_message"
			);
			self::Alter($arParams);
		}
		else
		{
			self::Unregister($caller);
		}
	}

	static function CreateIndex_message_AAFI()
	{
		global $DB;

		$caller = array('module'=> 'forum', 'class'=>__CLASS__, 'method'=>__FUNCTION__);
		$TRIGGER = 'create_mAAFI';

		if ($DB->type == 'MYSQL')
		{
			$arParams = array(
				'TRIGGER' => $TRIGGER,
				'mode' => 'CREATEINDEX',
				'caller' => $caller,
				'INDEX' => 'IX_FORUM_MESSAGE_AUTHOR2',
				'TABLE' => 'b_forum_message',
				'FIELDS' => array('AUTHOR_ID', 'APPROVED', 'FORUM_ID', 'ID'),
				'COMMAND' => "create index IX_FORUM_MESSAGE_AUTHOR2 on b_forum_message(AUTHOR_ID, APPROVED, FORUM_ID, ID)"
			);
			self::Alter($arParams);
		}
		else
		{
			self::Unregister($caller);
		}
	}

	static function CreateIndex_message_ATI()
	{
		global $DB;

		$caller = array('module'=> 'forum', 'class'=>__CLASS__, 'method'=>__FUNCTION__);
		$TRIGGER = 'create_mATI';

		if (in_array($DB->type, array('MYSQL', 'MSSQL', 'ORACLE')))
		{
			$arParams = array(
				'TRIGGER' => $TRIGGER,
				'mode' => 'CREATEINDEX',
				'caller' => $caller,
				'INDEX' => 'IX_FORUM_MESSAGE_AUTH_TOPIC_ID',
				'TABLE' => 'b_forum_message',
				'FIELDS' => array('AUTHOR_ID', 'TOPIC_ID', 'ID'),
				'COMMAND' => "create index IX_FORUM_MESSAGE_AUTH_TOPIC_ID on b_forum_message(AUTHOR_ID, TOPIC_ID, ID)"
			);
			self::Alter($arParams);
		}
		else
		{
			self::Unregister($caller);
		}
	}

	static function CreateIndex_message_AFIAT()
	{
		global $DB;

		$caller = array('module'=> 'forum', 'class'=>__CLASS__, 'method'=>__FUNCTION__);
		$TRIGGER = 'create_mAFIAT';

		if (in_array($DB->type, array('MYSQL', 'MSSQL', 'ORACLE')))
		{
			$arParams = array(
				'TRIGGER' => $TRIGGER,
				'mode' => 'CREATEINDEX',
				'caller' => $caller,
				'INDEX' => 'IX_FORUM_MESSAGE_AUTH_FORUM_ID',
				'TABLE' => 'b_forum_message',
				'FIELDS' => array('AUTHOR_ID', 'FORUM_ID', 'ID', 'APPROVED', 'TOPIC_ID'),
				'COMMAND' => "create index IX_FORUM_MESSAGE_AUTH_FORUM_ID on b_forum_message(AUTHOR_ID, FORUM_ID, ID, APPROVED, TOPIC_ID)"
			);
			self::Alter($arParams);
		}
		else
		{
			self::Unregister($caller);
		}
	}
}
?>
