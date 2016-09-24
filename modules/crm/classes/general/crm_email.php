<?php
use Bitrix\Crm\Integration\StorageManager;
if(!IsModuleInstalled('bitrix24'))
{
	IncludeModuleLangFile(__FILE__);
}
else
{
	// HACK: try to take site language instead of user language
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite->Fetch();
	IncludeModuleLangFile(__FILE__, isset($arSite['LANGUAGE_ID']) ? $arSite['LANGUAGE_ID'] : false);
}

class CCrmEMail
{
	public static function OnGetFilterList()
	{
		return array(
			'ID'					=>	'crm',
			'NAME'					=>	GetMessage('CRM_ADD_MESSAGE'),
			'ACTION_INTERFACE'		=>	$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/mail/action.php',
			'PREPARE_RESULT_FUNC'	=>	Array('CCrmEMail', 'PrepareVars'),
			'CONDITION_FUNC'		=>	Array('CCrmEMail', 'EmailMessageCheck'),
			'ACTION_FUNC'			=>	Array('CCrmEMail', 'EmailMessageAdd')
		);
	}
	private static function FindUserIDByEmail($email)
	{
		$email = trim(strval($email));
		if($email === '')
		{
			return 0;
		}

		$dbUsers = CUser::GetList(
			($by='ID'),
			($order='ASC'),
			array('=EMAIL' => $email),
			array(
				'FIELDS' => array('ID'),
				'NAV_PARAMS' => array('nTopCount' => 1)
			)
		);

		$arUser = $dbUsers ? $dbUsers->Fetch() : null;
		return $arUser ? intval($arUser['ID']) : 0;
	}
	private static function PrepareEntityKey($entityTypeID, $entityID)
	{
		return "{$entityTypeID}-{$entityID}";
	}
	private static function CreateBinding($entityTypeID, $entityID)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		return array(
			'ID' => $entityID,
			'TYPE_ID' => $entityTypeID,
			'TYPE_NAME' => CCrmOwnerType::ResolveName($entityTypeID)
		);
	}
	private static function CreateComm($entityTypeID, $entityID, $value)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);
		$value = strval($value);

		return array(
			'ENTITY_ID' => $entityID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'VALUE' => $value,
			'TYPE' => 'EMAIL'
		);
	}
	private static function ExtractCommsFromEmails($emails, $arIgnored = array())
	{
		if(!is_array($emails))
		{
			$emails = array($emails);
		}

		if(count($emails) === 0)
		{
			return array();
		}

		$arFilter = array();
		foreach ($emails as $email)
		{
			//Process valid emails only
			if(!($email !== '' && CCrmMailHelper::IsEmail($email)))
			{
				continue;
			}

			if(in_array($email, $arIgnored, true))
			{
				continue;
			}

			$arFilter[] = array('=VALUE' => $email);
		}

		if(empty($arFilter))
		{
			return array();
		}

		$dbFieldMulti = CCrmFieldMulti::GetList(
			array(),
			array(
				'ENTITY_ID' => 'LEAD|CONTACT|COMPANY',
				'TYPE_ID' => 'EMAIL',
				'FILTER' => $arFilter
			)
		);

		$result = array();
		while($arFieldMulti = $dbFieldMulti->Fetch())
		{
			$entityTypeID = CCrmOwnerType::ResolveID($arFieldMulti['ENTITY_ID']);
			$entityID = intval($arFieldMulti['ELEMENT_ID']);
			$result[] = self::CreateComm($entityTypeID, $entityID, $arFieldMulti['VALUE']);
		}
		return $result;
	}
	private static function ConvertCommsToBindings(&$arCommData)
	{
		$result = array();
		foreach($arCommData as &$arComm)
		{
			$entityTypeID = $arComm['ENTITY_TYPE_ID'];
			$entityID = $arComm['ENTITY_ID'];
			// Key to avoid dublicated entities
			$key = self::PrepareEntityKey($entityTypeID, $entityID);
			if(isset($result[$key]))
			{
				continue;
			}
			$result[$key] = self::CreateBinding($entityTypeID, $entityID);
		}
		unset($arComm);

		return $result;
	}
	private static function ExtractEmailsFromBody($body)
	{
		$body = strval($body);

		$out = array();
		if (!preg_match_all('/\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $body, $out))
		{
			return array();
		}

		$result = array();
		foreach($out[0] as $email)
		{
			$email = strtolower($email);
			if (!in_array($email, $result, true))
			{
				$result[] = $email;
			}
		}

		return $result;
	}
	private static function GetResponsibleID(&$entityFields)
	{
		$result = isset($entityFields['ASSIGNED_BY_ID']) ? intval($entityFields['ASSIGNED_BY_ID']) : 0;
		if($result <= 0)
		{
			$result = isset($entityFields['CREATED_BY_ID']) ? intval($entityFields['CREATED_BY_ID']) : 0;
		}
		return $result;
	}
	private static function GetEntity($entityTypeID, $entityID, $select = array())
	{

		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		$dbRes = null;
		if($entityTypeID === CCrmOwnerType::Company)
		{
			$dbRes = CCrmCompany::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			$dbRes = CCrmContact::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Lead)
		{
			$dbRes = CCrmLead::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			$dbRes = CCrmDeal::GetListEx(
				array(),
				array(
					'ID' => $entityID,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 1),
				$select
			);
		}

		return $dbRes ? $dbRes->Fetch() : null;
	}
	private static function IsEntityExists($entityTypeID, $entityID)
	{
		$arFields = self::GetEntity(
			$entityTypeID,
			$entityID,
			array('ID')
		);

		return is_array($arFields);
	}
	private static function ResolveResponsibleID($entityTypeID, $entityID)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		$arFields = self::GetEntity(
			$entityTypeID,
			$entityID,
			array('ASSIGNED_BY_ID', 'CREATED_BY_ID')
		);

		return $arFields ? self::GetResponsibleID($arFields) : 0;
	}
	private static function TryImportVCard(&$fileData)
	{
		$CCrmVCard = new CCrmVCard();
		$arContact = $CCrmVCard->ReadCard(false, $fileData);

		if (empty($arContact['NAME']) && empty($arContact['LAST_NAME']))
		{
			return false;
		}

		$arFilter = array();
		if (!empty($arContact['NAME']))
		{
			$arFilter['NAME'] = $arContact['NAME'];
		}
		if (!empty($arContact['LAST_NAME']))
		{
			$arFilter['LAST_NAME'] = $arContact['LAST_NAME'];
		}
		if (!empty($arContact['SECOND_NAME']))
		{
			$arFilter['SECOND_NAME'] = $arContact['SECOND_NAME'];
		}

		$arFilter['CHECK_PERMISSIONS'] = 'N';

		$dbContact = CCrmContact::GetListEx(array(), $arFilter, false, false, array('ID'));
		if ($dbContact->Fetch())
		{
			return false;
		}

		$arContact['SOURCE_ID'] = 'EMAIL';
		$arContact['TYPE_ID'] = 'SHARE';
		if (!empty($arContact['COMPANY_TITLE']))
		{
			$dbCompany = CCrmCompany::GetListEx(
				array(),
				array(
					'TITLE' => $arContact['COMPANY_TITLE'],
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				false,
				array('ID')
			);

			if (($arCompany = $dbCompany->Fetch()) !== false)
			{
				$arContact['COMPANY_ID'] = $arCompany['ID'];
			}
			else
			{
				if(!empty($arContact['COMMENTS']))
				{
					$arContact['COMMENTS'] .= PHP_EOL;
				}
				$arContact['COMMENTS'] .=
					GetMessage('CRM_MAIL_COMPANY_NAME', array('%TITLE%' => $arContact['COMPANY_TITLE']));
			}
		}

		$CCrmContact = new CCrmContact(false);
		$CCrmContact->Add(
			$arContact,
			true,
			array('DISABLE_USER_FIELD_CHECK' => true)
		);

		return true;
	}
	protected static function ExtractPostingID(&$arMessageFields)
	{
		$header = isset($arMessageFields['HEADER']) ? $arMessageFields['HEADER'] : '';
		$match = array();
		return preg_match('/^X-Bitrix-Posting:\s*(?P<id>[0-9]+)\s*$/im', $header, $match) === 1
			? (isset($match['id']) ? intval($match['id']) : 0)
			: 0;
	}
	public static function EmailMessageAdd($arMessageFields, $ACTION_VARS)
	{
		if(!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$date = isset($arMessageFields['FIELD_DATE']) ? $arMessageFields['FIELD_DATE'] : '';
		$maxAgeDays = intval(COption::GetOptionString('crm', 'email_max_age', 7));
		$maxAge = $maxAgeDays > 0 ? ($maxAgeDays * 86400) : 0;
		if($maxAge > 0 && $date !== '')
		{
			$now = time() + CTimeZone::GetOffset();
			$timestamp = MakeTimeStamp($date, FORMAT_DATETIME);
			if( ($now - $timestamp) > $maxAge)
			{
				//Time threshold is exceeded
				return false;
			}
		}

		$crmEmail = strtolower(trim(COption::GetOptionString('crm', 'mail', '')));

		$msgID = isset($arMessageFields['ID']) ? intval($arMessageFields['ID']) : 0;
		$mailboxID = isset($arMessageFields['MAILBOX_ID']) ? intval($arMessageFields['MAILBOX_ID']) : 0;
		$from = isset($arMessageFields['FIELD_FROM']) ? $arMessageFields['FIELD_FROM'] : '';
		$replyTo = isset($arMessageFields['FIELD_REPLY_TO']) ? $arMessageFields['FIELD_REPLY_TO'] : '';
		if($replyTo !== '')
		{
			// Ignore FROM if REPLY_TO EXISTS
			$from = $replyTo;
		}
		$addresserInfo = CCrmMailHelper::ParseEmail($from);
		if($crmEmail !== '' && strcasecmp($addresserInfo['EMAIL'], $crmEmail) === 0)
		{
			// Ignore emails from ourselves
			return false;
		}

		$to = isset($arMessageFields['FIELD_TO']) ? $arMessageFields['FIELD_TO'] : '';
		$cc = isset($arMessageFields['FIELD_CC']) ? $arMessageFields['FIELD_CC'] : '';
		$bcc = isset($arMessageFields['FIELD_BCC']) ? $arMessageFields['FIELD_BCC'] : '';

		$addresseeEmails = array_unique(
			array_merge(
				$to !== '' ? CMailUtil::ExtractAllMailAddresses($to) : array(),
				$cc !== '' ? CMailUtil::ExtractAllMailAddresses($cc) : array(),
				$bcc !== '' ? CMailUtil::ExtractAllMailAddresses($bcc) : array()),
			SORT_STRING
		);

		if($mailboxID > 0)
		{
			$dbMailbox = CMailBox::GetById($mailboxID);
			$arMailbox = $dbMailbox->Fetch();

			// POP3 mailboxes are ignored - they bound to single email
			if ($arMailbox
				&& $arMailbox['SERVER_TYPE'] === 'smtp'
				&& (empty($crmEmail) || !in_array($crmEmail, $addresseeEmails, true)))
			{
				return false;
			}
		}

		$subject = isset($arMessageFields['SUBJECT']) ? $arMessageFields['SUBJECT'] : '';
		$body = isset($arMessageFields['BODY']) ? $arMessageFields['BODY'] : '';
		$arBodyEmails = null;

		$userID = 0;
		$parentID = 0;
		$ownerTypeID = CCrmOwnerType::Undefined;
		$ownerID = 0;

		$addresserID = self::FindUserIDByEmail($addresserInfo['EMAIL']);

		$arCommEmails = $addresserID <= 0
			? array($addresserInfo['EMAIL'])
			: ($crmEmail !== ''
				? array_diff($addresseeEmails, array($crmEmail))
				: $addresseeEmails);

		$targInfo = CCrmActivity::ParseUrn(
			CCrmActivity::ExtractUrnFromMessage(
				$arMessageFields,
				CCrmEMailCodeAllocation::GetCurrent()
			)
		);
		$targActivity = $targInfo['ID'] > 0 ? CCrmActivity::GetByID($targInfo['ID'], false) : null;

		// Check URN
		if(!$targActivity
			&& (!isset($targActivity['URN']) || strtoupper($targActivity['URN']) !== strtoupper($targInfo['URN'])))
		{
			$targActivity = null;
		}

		if($targActivity)
		{
			$postingID = self::ExtractPostingID($arMessageFields);
			if($postingID > 0 && isset($targActivity['ASSOCIATED_ENTITY_ID']) && intval($targActivity['ASSOCIATED_ENTITY_ID']) === $postingID)
			{
				// Ignore - it is our message.
				return false;
			}

			$parentID = $targActivity['ID'];
			$subject = CCrmActivity::ClearUrn($subject);

			if($addresserID > 0)
			{
				$userID = $addresserID;
			}
			elseif(isset($targActivity['RESPONSIBLE_ID']))
			{
				$userID = $targActivity['RESPONSIBLE_ID'];
			}

			if(isset($targActivity['OWNER_TYPE_ID']))
			{
				$ownerTypeID = intval($targActivity['OWNER_TYPE_ID']);
			}

			if(isset($targActivity['OWNER_ID']))
			{
				$ownerID = intval($targActivity['OWNER_ID']);
			}

			$arCommData = self::ExtractCommsFromEmails($arCommEmails);

			if($ownerTypeID > 0 && $ownerID > 0)
			{
				if(empty($arCommData))
				{
					if($addresserID > 0)
					{
						foreach($addresseeEmails as $email)
						{
							if($email === $crmEmail)
							{
								continue;
							}

							$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $email));
						}
					}
					else
					{
						$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $addresserInfo['EMAIL']));
					}
				}
				elseif($ownerTypeID !== CCrmOwnerType::Deal)
				{
					//Check if owner in communications. Otherwise clear owner.
					//There is only one exception for DEAL - it entity has no communications
					$isOwnerInComms = false;
					foreach($arCommData as &$arCommItem)
					{
						$commEntityTypeID = isset($arCommItem['ENTITY_TYPE_ID']) ? $arCommItem['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
						$commEntityID = isset($arCommItem['ENTITY_ID']) ? $arCommItem['ENTITY_ID'] : 0;

						if($commEntityTypeID === $ownerTypeID && $commEntityID === $ownerID)
						{
							$isOwnerInComms = true;
							break;
						}
					}
					unset($arCommItem);

					if(!$isOwnerInComms)
					{
						$ownerTypeID = CCrmOwnerType::Undefined;
						$ownerID = 0;
					}
				}
			}
		}
		else
		{
			if($addresserID > 0)
			{
				//It is email from registred user
				$userID = $addresserID;

				if(empty($arCommEmails))
				{
					$arBodyEmails = self::ExtractEmailsFromBody($body);
					//Clear system user emails
					if(!empty($arBodyEmails))
					{
						foreach($arBodyEmails as $email)
						{
							if(self::FindUserIDByEmail($email) <= 0)
							{
								$arCommEmails[] = $email;
							}
						}
					}
				}

				// Try to resolve communications
				$arCommData = self::ExtractCommsFromEmails($arCommEmails);
			}
			else
			{
				//It is email from unknown user

				//Try to resolve bindings from addresser
				$arCommData = self::ExtractCommsFromEmails($arCommEmails);
				if(!empty($arCommData))
				{
					// Try to resolve responsible user
					foreach($arCommData as &$arComm)
					{
						$userID = self::ResolveResponsibleID(
							$arComm['ENTITY_TYPE_ID'],
							$arComm['ENTITY_ID']
						);

						if($userID > 0)
						{
							break;
						}
					}
					unset($arComm);
				}
			}

			// Try to resolve owner by old-style method-->
			$arACTION_VARS = explode('&', $ACTION_VARS);
			for ($i=0, $ic=count($arACTION_VARS); $i < $ic ; $i++)
			{
				$v = $arACTION_VARS[$i];
				if($pos = strpos($v, '='))
				{
					$name = substr($v, 0, $pos);
					${$name} = urldecode(substr($v, $pos+1));
				}
			}

			$arTypeNames = CCrmOwnerType::GetNames(
				array(
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company
				)
			);
			foreach ($arTypeNames as $typeName)
			{
				$regexVar = 'W_CRM_ENTITY_REGEXP_'.$typeName;

				if(empty($$regexVar))
				{
					continue;
				}

				$match = array();
				if (preg_match('/'.$$regexVar.'/i'.BX_UTF_PCRE_MODIFIER, $subject, $match) === 1)
				{
					$ownerID = intval($match[1]);
					$ownerTypeID = CCrmOwnerType::ResolveID($typeName);

					break;
				}
			}
			// <-- Try to resolve owner by old-style method

			// Filter communications by owner
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				if(!empty($arCommData))
				{
					foreach($arCommData as $commKey => $arComm)
					{
						if($arComm['ENTITY_TYPE_ID'] === $ownerTypeID && $arComm['ENTITY_ID'] === $ownerID)
						{
							continue;
						}

						unset($arCommData[$commKey]);
					}

					$arCommData = array_values($arCommData);
				}

				if(empty($arCommData))
				{
					if($addresserID > 0)
					{
						foreach($addresseeEmails as $email)
						{
							if($email === $crmEmail)
							{
								continue;
							}

							$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $email));
						}
					}
					else
					{
						$arCommData = array(self::CreateComm($ownerTypeID, $ownerID, $addresserInfo['EMAIL']));
					}
				}
			}
		}

		$arBindingData = self::ConvertCommsToBindings($arCommData);

		// Check bindings for converted leads -->
		// Not Existed entities are ignored. Converted leads are ignored if their associated entities (contacts, companies, deals) are contained in bindings.
		$arCorrectedBindingData = array();
		$arConvertedLeadData = array();
		foreach($arBindingData as $bindingKey => &$arBinding)
		{
			if($arBinding['TYPE_ID'] !== CCrmOwnerType::Lead)
			{
				if(self::IsEntityExists($arBinding['TYPE_ID'], $arBinding['ID']))
				{
					$arCorrectedBindingData[$bindingKey] = $arBinding;
				}
				continue;
			}

			$arFields = self::GetEntity(
				CCrmOwnerType::Lead,
				$arBinding['ID'],
				array('STATUS_ID')
			);

			if(!is_array($arFields))
			{
				continue;
			}

			if(isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] === 'CONVERTED')
			{
				$arConvertedLeadData[$bindingKey] = $arBinding;
			}
			else
			{
				$arCorrectedBindingData[$bindingKey] = $arBinding;
			}
		}
		unset($arBinding);

		foreach($arConvertedLeadData as &$arConvertedLead)
		{
			$leadID = $arConvertedLead['ID'];
			$exists = false;

			$dbRes = CCrmCompany::GetListEx(
				array(),
				array('LEAD_ID' => $leadID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			if($dbRes)
			{
				while($arRes = $dbRes->Fetch())
				{
					if(isset($arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Company, $arRes['ID'])]))
					{
						$exists = true;
						break;
					}
				}
			}

			if($exists)
			{
				continue;
			}

			$dbRes = CCrmContact::GetListEx(
				array(),
				array('LEAD_ID' => $leadID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			if($dbRes)
			{
				while($arRes = $dbRes->Fetch())
				{
					if(isset($arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Contact, $arRes['ID'])]))
					{
						$exists = true;
						break;
					}
				}
			}

			if($exists)
			{
				continue;
			}

			$dbRes = CCrmDeal::GetListEx(
				array(),
				array('LEAD_ID' => $leadID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID')
			);

			if($dbRes)
			{
				while($arRes = $dbRes->Fetch())
				{
					if(isset($arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Deal, $arRes['ID'])]))
					{
						$exists = true;
						break;
					}
				}
			}

			if($exists)
			{
				continue;
			}

			$arCorrectedBindingData[self::PrepareEntityKey(CCrmOwnerType::Lead, $leadID)] = $arConvertedLead;
		}
		unset($arConvertedLead);

		$arBindingData = $arCorrectedBindingData;
		// <-- Check bindings for converted leads

		// If no bindings are found then create new lead from this message
		// Skip lead creation if email list is empty. Otherwise we will create lead with no email-addresses. It is absolutely useless.
		$emailQty = count($arCommEmails);
		if(empty($arBindingData) && $emailQty > 0)
		{
			if(strtoupper(COption::GetOptionString('crm', 'email_create_lead_for_new_addresser', 'Y')) !== 'Y')
			{
				// Creation of new lead is not allowed
				return true;
			}

			//"Lead from forwarded email..." or "Lead from email..."
			$title = GetMessage(
				$addresserID > 0
					? 'CRM_MAIL_LEAD_FROM_USER_EMAIL_TITLE'
					: 'CRM_MAIL_LEAD_FROM_EMAIL_TITLE',
				array('%SENDER%' => $addresserInfo['ORIGINAL'])
			);

			$comment = '';
			if($body !== '')
			{
				// Remove extra new lines (fix for #31807)
				$comment = preg_replace("/(\r\n|\n|\r)+/", '<br/>', $body);
			}
			if($comment === '')
			{
				$comment = $subject;
			}

			$name = '';
			if($addresserID <= 0)
			{
				$name = $addresserInfo['NAME'];
			}
			else
			{
				//Try get name from body
				for($i = 0; $i < $emailQty; $i++)
				{
					$email = $arCommEmails[$i];
					$match = array();
					if(preg_match('/"([^"]+)"\s*<'.$email.'>/i'.BX_UTF_PCRE_MODIFIER, $body, $match) === 1 && count($match) > 1)
					{
						$name = $match[1];
						break;
					}

					if(preg_match('/"([^"]+)"\s*[\s*mailto\:\s*'.$email.']/i'.BX_UTF_PCRE_MODIFIER, $body, $match) === 1 && count($match) > 1)
					{
						$name = $match[1];
						break;
					}
				}

				if($name === '')
				{
					$name = $arCommEmails[0];
				}
			}


			$arLeadFields = array(
				'TITLE' =>  $title,
				'NAME' => $name,
				'STATUS_ID' => 'NEW',
				'COMMENTS' => $comment,
				'SOURCE_ID' => 'EMAIL',
				'SOURCE_DESCRIPTION' => GetMessage('CRM_MAIL_LEAD_FROM_EMAIL_SOURCE', array('%SENDER%' => $addresserInfo['ORIGINAL'])),
				'OPENED' => 'Y',
				'FM' => array(
					'EMAIL' => array()
				)
			);

			$responsibleID = intval(COption::GetOptionString('crm', 'email_lead_responsible_id', 0));
			if($responsibleID > 0)
			{
				$arLeadFields['CREATED_BY_ID'] = $arLeadFields['MODIFY_BY_ID'] = $arLeadFields['ASSIGNED_BY_ID'] = $responsibleID;

				if($userID === 0)
				{
					$userID = $responsibleID;
				}
			}

			for($i = 0; $i < $emailQty; $i++)
			{
				$arLeadFields['FM']['EMAIL']['n'.($i + 1)] =
				array(
					'VALUE_TYPE' => 'WORK',
					'VALUE' => $arCommEmails[$i]
				);
			}

			$leadEntity = new CCrmLead(false);
			$leadID = $leadEntity->Add(
				$arLeadFields,
				true,
				array(
					'DISABLE_USER_FIELD_CHECK' => true,
					'CURRENT_USER' => $responsibleID
				)
			);
			// TODO: log error
			if($leadID > 0)
			{
				$arBizProcErrors = array();
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$leadID,
					CCrmBizProcEventType::Create,
					$arBizProcErrors
				);

				$arCommData = array();
				for($i = 0; $i < $emailQty; $i++)
				{
					$arCommData[] = self::CreateComm(
						CCrmOwnerType::Lead,
						$leadID,
						$arCommEmails[$i]
					);
				}

				$arBindingData = array(
					self::PrepareEntityKey(CCrmOwnerType::Lead, $leadID) =>
					self::CreateBinding(CCrmOwnerType::Lead, $leadID)
				);
			}
		}

		// Terminate processing if no bindings are found.
		if(empty($arBindingData))
		{
			// Try to export vcf-files before exit if email from registered user
			if($addresserID > 0)
			{
				$dbAttachment = CMailAttachment::GetList(array(), array('MESSAGE_ID' => $msgID));
				while ($arAttachment = $dbAttachment->Fetch())
				{
					if(GetFileExtension(strtolower($arAttachment['FILE_NAME'])) === 'vcf')
					{
						self::TryImportVCard($arAttachment['FILE_DATA']);
					}
				}
			}
			return false;
		}

		// If owner info not defined set it by default
		if($ownerID <= 0 || $ownerTypeID <= 0)
		{
			if(count($arBindingData) > 1)
			{
				// Search owner in specified order: Contact, Company, Lead.
				$arTypeIDs = array(
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company,
					CCrmOwnerType::Lead
				);

				foreach($arTypeIDs as $typeID)
				{
					foreach($arBindingData as &$arBinding)
					{
						if($arBinding['TYPE_ID'] === $typeID)
						{
							$ownerTypeID = $typeID;
							$ownerID = $arBinding['ID'];
							break;
						}
					}
					unset($arBinding);

					if($ownerID > 0 && $ownerTypeID > 0)
					{
						break;
					}
				}
			}

			if($ownerID <= 0 || $ownerTypeID <= 0)
			{
				$arBinding = array_shift(array_values($arBindingData));
				$ownerTypeID = $arBinding['TYPE_ID'];
				$ownerID = $arBinding['ID'];
			}
		}

		// Precessing of attachments -->
		$attachmentMaxSizeMb = intval(COption::GetOptionString('crm', 'email_attachment_max_size', 16));
		$attachmentMaxSize = $attachmentMaxSizeMb > 0 ? ($attachmentMaxSizeMb * 1048576) : 0;

		$arFilesData = array();
		$dbAttachment = CMailAttachment::GetList(array(), array('MESSAGE_ID' => $msgID));
		$arBannedAttachments = array();
		while ($arAttachment = $dbAttachment->Fetch())
		{
			if ($arAttachment['FILE_NAME'] === '1.tmp')
			{
				// HACK: For bug in module 'Mail'
				continue;
			}
			elseif (GetFileExtension(strtolower($arAttachment['FILE_NAME'])) === 'vcf')
			{
				self::TryImportVCard($arAttachment['FILE_DATA']);
			}

			$fileSize = isset($arAttachment['FILE_SIZE']) ? intval($arAttachment['FILE_SIZE']) : 0;
			if($fileSize <= 0)
			{
				//Skip zero lenth files
				continue;
			}

			if($attachmentMaxSize > 0 && $fileSize > $attachmentMaxSize)
			{
				//File size limit  is exceeded
				$arBannedAttachments[] = array(
					'name' => $arAttachment['FILE_NAME'],
					'size' => $fileSize
				);
				continue;
			}

			$arFilesData[] = array(
				'name' => $arAttachment['FILE_NAME'],
				'type' => $arAttachment['CONTENT_TYPE'],
				'content' => $arAttachment['FILE_DATA'],
				//'size' => $arAttachment['FILE_SIZE'], // HACK: Must be commented if use CFile:SaveForDB
				'MODULE_ID' => 'crm'
			);
		}
		//<-- Precessing of attachments

		// Remove extra new lines (fix for #31807)
		$body = preg_replace("/(\r\n|\n|\r)+/", PHP_EOL, $body);

		$sanitizer = new CBXSanitizer();
		$sanitizer->ApplyDoubleEncode(false);
		$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
		$sanitizedBody = $sanitizer->SanitizeHtml($body);

		// Creating of new event -->
		$arEventBindings = array();
		foreach($arBindingData as &$arBinding)
		{
			$arEventBindings[] = array(
				'ENTITY_TYPE' => $arBinding['TYPE_NAME'],
				'ENTITY_ID' => $arBinding['ID']
			);
		}
		unset($arBinding);

		$eventText  = '';
		$eventText .= '<b>'.GetMessage('CRM_EMAIL_SUBJECT').'</b>: '.$subject.PHP_EOL;
		$eventText .= '<b>'.GetMessage('CRM_EMAIL_FROM').'</b>: '.$addresserInfo['EMAIL'].PHP_EOL;
		$eventText .= '<b>'.GetMessage('CRM_EMAIL_TO').'</b>: '.implode($addresseeEmails, '; ').PHP_EOL;
		if(!empty($arBannedAttachments))
		{
			$eventText .= '<b>'.GetMessage('CRM_EMAIL_BANNENED_ATTACHMENTS', array('%MAX_SIZE%' => $attachmentMaxSizeMb)).'</b>: ';
			foreach($arBannedAttachments as &$attachmentInfo)
			{
				$eventText .= GetMessage(
					'CRM_EMAIL_BANNENED_ATTACHMENT_INFO',
					array(
						'%NAME%' => $attachmentInfo['name'],
						'%SIZE%' => round($attachmentInfo['size'] / 1048576, 1)
					)
				);
			}
			unset($attachmentInfo);
			$eventText .= PHP_EOL;
		}
		$eventText .= $sanitizedBody;

		$CCrmEvent = new CCrmEvent();
		$CCrmEvent->Add(
			array(
				'USER_ID' => $userID,
				'ENTITY' => array_values($arEventBindings),
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName($ownerTypeID),
				'ENTITY_ID' => $ownerID,
				'EVENT_NAME' => GetMessage('CRM_EMAIL_GET_EMAIL'),
				'EVENT_TYPE' => 2,
				'EVENT_TEXT_1' => $eventText,
				'FILES' => $arFilesData,
			),
			false
		);
		// <-- Creating of new event

		// Creating new activity -->
		$siteID = '';
		$dbSites = CSite::GetList($by = 'sort', $order = 'desc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$defaultSite = is_object($dbSites) ? $dbSites->Fetch() : null;
		if(is_array($defaultSite))
		{
			$siteID = $defaultSite['LID'];
		}
		if($siteID === '')
		{
			$siteID = 's1';
		}

		$storageTypeID =  CCrmActivity::GetDefaultStorageTypeID();
		$arElementIDs = array();
		foreach($arFilesData as $fileData)
		{
			$fileID = CFile::SaveFile($fileData, 'crm');
			if($fileID > 0)
			{
				$elementID = StorageManager::saveEmailAttachment(CFile::GetFileArray($fileID), $storageTypeID, $siteID);
				if(is_int($elementID) && $elementID > 0)
				{
					$arElementIDs[] = $elementID;
				}
			}
		}

		$descr = preg_replace("/(\r\n|\n|\r)+/", '<br/>', $sanitizedBody);
		$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
		$arActivityFields = array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'TYPE_ID' =>  CCrmActivityType::Email,
			'ASSOCIATED_ENTITY_ID' => 0,
			'PARENT_ID' => $parentID,
			'SUBJECT' => $subject,
			'START_TIME' => $now,
			'END_TIME' => $now,
			'COMPLETED' => 'N', // Incomming emails must be marked as 'Not Completed'.
			'AUTHOR_ID' => $userID,
			'RESPONSIBLE_ID' => $userID,
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $descr,
			'DESCRIPTION_TYPE' => CCrmContentType::Html,
			'DIRECTION' => CCrmActivityDirection::Incoming,
			'LOCATION' => '',
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'STORAGE_TYPE_ID' => $storageTypeID,
			'STORAGE_ELEMENT_IDS' => $arElementIDs
		);

		$arActivityFields['BINDINGS'] = array();
		foreach($arBindingData as &$arBinding)
		{
			$entityTypeID = $arBinding['TYPE_ID'];
			$entityID = $arBinding['ID'];

			if($entityTypeID <= 0 || $entityID <= 0)
			{
				continue;
			}

			$arActivityFields['BINDINGS'][] =
				array(
					'OWNER_TYPE_ID' => $entityTypeID,
					'OWNER_ID' => $entityID
				);
		}
		unset($arBinding);

		$activityID = CCrmActivity::Add($arActivityFields, false, false, array('REGISTER_SONET_EVENT' => true));
		if($activityID > 0 && !empty($arCommData))
		{
			CCrmActivity::SaveCommunications(
				$activityID,
				$arCommData,
				$arActivityFields,
				false,
				false
			);
			$arActivityFields['COMMUNICATIONS'] = $arCommData;
		}

		//Notity responsible user
		if($userID > 0)
		{
			CCrmActivity::Notify($arActivityFields, CCrmNotifierSchemeType::IncomingEmail);
		}
		// <-- Creating new activity
		return true;
	}
	public static function EmailMessageCheck($arFields, $ACTION_VARS)
	{
		$arACTION_VARS = explode('&', $ACTION_VARS);
		for ($i=0, $ic=count($arACTION_VARS); $i < $ic ; $i++)
		{
			$v = $arACTION_VARS[$i];
			if($pos = strpos($v, '='))
			{
				$name = substr($v, 0, $pos);
				${$name} = urldecode(substr($v, $pos+1));
			}
		}
		return true;
	}
	public static function PrepareVars()
	{
		$str = 'W_CRM_ENTITY_REGEXP_LEAD='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_LEAD']).
			'&W_CRM_ENTITY_REGEXP_CONTACT='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_CONTACT']).
			'&W_CRM_ENTITY_REGEXP_COMPANY='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_COMPANY']).
			'&W_CRM_ENTITY_REGEXP_DEAL='.urlencode($_REQUEST['W_CRM_ENTITY_REGEXP_DEAL']);
		return $str;
	}
	public static function BeforeSendMail($arMessageFields)
	{
		// ADD ADDITIONAL HEADERS
		$postingID = self::ExtractPostingID($arMessageFields);
		if($postingID <= 0)
		{
			return $arMessageFields;
		}

		$dbActivity = CAllCrmActivity::GetList(
			array(),
			array(
				'=TYPE_ID' => CCrmActivityType::Email,
				'=ASSOCIATED_ENTITY_ID' => $postingID,
				'CHECK_PERMISSIONS'=>'N'
			),
			false,
			false,
			array('SETTINGS'),
			array()
		);

		$arActivity = $dbActivity ? $dbActivity->Fetch() : null;

		if(!$arActivity)
		{
			return $arMessageFields;
		}

		$settings = isset($arActivity['SETTINGS']) && is_array($arActivity['SETTINGS']) ? $arActivity['SETTINGS'] : array();
		$messageHeaders = isset($settings['MESSAGE_HEADERS']) ? $settings['MESSAGE_HEADERS'] : array();
		if(empty($messageHeaders))
		{
			return $arMessageFields;
		}

		$header = isset($arMessageFields['HEADER']) ? $arMessageFields['HEADER'] : '';
		$eol = CEvent::GetMailEOL();
		foreach($messageHeaders as $headerName => &$headerValue)
		{
			if(strlen($header) > 0)
			{
				$header .= $eol;
			}

			$header .= $headerName.': '.$headerValue;
		}
		unset($headerValue);
		$arMessageFields['HEADER'] = $header;

		return $arMessageFields;
	}

	public static function GetEOL()
	{
		return CEvent::GetMailEOL();
	}
}

class CCrmEMailCodeAllocation
{
	const None = 0;
	const Subject = 1;
	const Body = 2;
	private static $ALL_DESCRIPTIONS = null;
	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Body => GetMessage('CRM_EMAIL_CODE_ALLOCATION_BODY'),
				self::Subject => GetMessage('CRM_EMAIL_CODE_ALLOCATION_SUBJECT'),
				self::None => GetMessage('CRM_EMAIL_CODE_ALLOCATION_NONE')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}
	public static function PrepareListItems()
	{
		return CCrmEnumeration::PrepareListItems(self::GetAllDescriptions());
	}
	public static function IsDefined($value)
	{
		$value = intval($value);
		return $value >= self::None && $value <= self::Body;
	}
	public static function SetCurrent($value)
	{
		if(!self::IsDefined($value))
		{
			$value = self::Body;
		}

		COption::SetOptionString('crm', 'email_service_code_allocation', $value);
	}
	public static function GetCurrent()
	{
		$value = intval(COption::GetOptionString('crm', 'email_service_code_allocation', self::Body));
		return self::IsDefined($value) ? $value : self::Body;
	}
}
?>
