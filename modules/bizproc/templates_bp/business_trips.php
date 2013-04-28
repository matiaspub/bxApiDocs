<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile(__FILE__);

class CBPTemplates_BusinessTrips
{
	public static function GetName()
	{
		return GetMessage("BPT_TTITLE");
	}

	public static function GetVariables()
	{
		$arBPTemplateVariables = array(
			'ParameterOpRead' => array(
				'Name' => GetMessage("BPT_BT_PARAM_OP_READ"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => 'author'
			),
			'ParameterOpCreate' => array(
				'Name' => GetMessage("BPT_BT_PARAM_OP_CREATE"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => 'author'
			),
			'ParameterOpAdmin' => array(
				'Name' => GetMessage("BPT_BT_PARAM_OP_ADMIN"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => ''
			),
			'ParameterBoss' => array(
				'Name' => GetMessage("BPT_BT_PARAM_BOSS"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => ''
			),
			'ParameterBookkeeper' => array(
				'Name' => GetMessage("BPT_BT_PARAM_BOOK"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => ''
			),
			'ParameterForm1' => array(
				'Name' => GetMessage("BPT_BT_PARAM_FORM1"),
				'Description' => '',
				'Type' => 'S',
				'Required' => true,
				'Multiple' => false,
				'Default' => '/upload/form1.doc'
			),
			'ParameterForm2' => array(
				'Name' => GetMessage("BPT_BT_PARAM_FORM2"),
				'Description' => '',
				'Type' => 'S',
				'Required' => true,
				'Multiple' => false,
				'Default' => '/upload/form2.doc'
			),
		);

		return $arBPTemplateVariables;
	}

	public static function GetParameters()
	{
		$arBPTemplateParameters = array(
			'TargetUser' => array(
				'Name' => GetMessage("BPT_BT_P_TARGET"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => false,
				'Multiple' => false,
				'Default' => ''
			),
			'purpose' => array(
				'Name' => GetMessage("BPT_BT_T_PURPOSE"),
				'Description' => '',
				'Type' => 'T',
				'Required' => true,
				'Multiple' => false,
				'Default' => ''
			),
			'COUNTRY' => array(
				'Name' => GetMessage("BPT_BT_T_COUNTRY"),
				'Description' => '',
				'Type' => 'L',
				'Required' => true,
				'Multiple' => false,
				'Default' => GetMessage("BPT_BT_T_COUNTRY_DEF"),
				'Options' => array()
			),
			'CITY' => array(
				'Name' => GetMessage("BPT_BT_T_CITY"),
				'Description' => '',
				'Type' => 'S',
				'Required' => true,
				'Multiple' => false,
				'Default' => ''
			),
			'date_start' => array(
				'Name' => GetMessage("BPT_BT_T_DATE_START"),
				'Description' => '',
				'Type' => 'S:DateTime',
				'Required' => true,
				'Multiple' => false,
				'Default' => ''
			),
			'date_end' => array(
				'Name' => GetMessage("BPT_BT_T_DATE_END"),
				'Description' => '',
				'Type' => 'S:DateTime',
				'Required' => true,
				'Multiple' => false,
				'Default' => ''
			),
			'expenditures' => array(
				'Name' => GetMessage("BPT_BT_T_EXP"),
				'Description' => '',
				'Type' => 'N',
				'Required' => false,
				'Multiple' => false,
				'Default' => ''
			),
			'tickets' => array(
				'Name' => GetMessage("BPT_BT_T_TICKETS"),
				'Description' => '',
				'Type' => 'F',
				'Required' => false,
				'Multiple' => true,
				'Default' => ''
			)
		);

		$ar = GetCountryArray();
		for ($i = 0, $cnt = count($ar["reference"]); $i < $cnt; $i++)
			$arBPTemplateParameters['COUNTRY']['Options'][$ar["reference"][$i]] = $ar["reference"][$i];

		return $arBPTemplateParameters;
	}

	public static function GetTemplate()
	{
		$arBPTemplate = array(
			array(
				'Type' => 'SequentialWorkflowActivity',
				'Name' => 'Template',
				'Properties' => array(
					'Title' => GetMessage("BPT_BT_SWA"),
					'Permission' => array("read" => array('Variable', 'ParameterOpRead'), "create" => array('Variable', 'ParameterOpCreate'), "admin" => array('Variable', 'ParameterOpAdmin'))
				),
				'Children' => array(
					array(
						'Type' => 'SetFieldActivity',
						'Name' => 'A5656_39486_90916_53735',
						'Properties' => array(
							'FieldValue' => array(
								'ACTIVE_FROM' => '{=Template:date_start}',
								'ACTIVE_TO' => '{=Template:date_end}',
								'NAME' => GetMessage("BPT_BT_SFA1_NAME"),
								'PREVIEW_TEXT' => '{=Template:purpose}',
								'PROPERTY_CITY' => '{=Template:CITY}',
								'PROPERTY_tickets' => '{=Template:tickets}',
								'PROPERTY_COUNTRY' => '{=Template:COUNTRY}'
							),
							'Title' => GetMessage("BPT_BT_SFA1_TITLE")
						)
					), 
					array(
						'Type' => 'SetStateTitleActivity',
						'Name' => 'A44511_70449_33378_74731',
						'Properties' => array(
							'TargetStateTitle' => GetMessage("BPT_BT_STA1_STATE_TITLE"),
							'Title' => GetMessage("BPT_BT_STA1_TITLE")
						)
					),
					array(
						'Type' => 'ApproveActivity',
						'Name' => 'A54165_38396_31015_81889',
						'Properties' => array(
							'ApproveType' => 'any',
							'OverdueDate' => '',
							'ApproveMinPercent' => '50',
							'ApproveWaitForAll' => 'N',
							'Name' => GetMessage("BPT_BT_AA1_NAME"),
							'Description' => GetMessage("BPT_BT_AA1_DESCR"),
							'Parameters' => '',
							'StatusMessage' => GetMessage("BPT_BT_AA1_STATUS_MESSAGE"),
							'SetStatusMessage' => 'Y',
							'Users' => array('Variable', 'ParameterBoss'),
							'Title' => GetMessage("BPT_BT_AA1_TITLE")
						),
						'Children' => array(
							array(
								'Type' => 'SequenceActivity',
								'Name' => 'A7049_25485_20198_22566',
								'Properties' => array(
									'Title' => GetMessage("BPT_BT_SA1_TITLE")
								),
								'Children' => array(
									array(
										'Type' => 'SetStateTitleActivity',
										'Name' => 'A49920_58866_40695_72906',
										'Properties' => array(
											'TargetStateTitle' => GetMessage("BPT_BT_SSTA2_STATE_TITLE"),
											'Title' => GetMessage("BPT_BT_SSTA2_TITLE")
										)
									), 
									array(
										'Type' => 'SocNetMessageActivity',
										'Name' => 'A20044_6088_63188_45862',
										'Properties' => array(
											'MessageText' => GetMessage("BPT_BT_SNMA1_TEXT"),
											'MessageUserFrom' => array('A54165_38396_31015_81889', 'LastApprover'),
											'MessageUserTo' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT_BT_SNMA1_TITLE")
										)
									), 
									array(
										'Type' => 'ReviewActivity',
										'Name' => 'A7642_71713_44727_60839',
										'Properties' => array(
											'ApproveType' => 'any',
											'OverdueDate' => '',
											'Name' => GetMessage("BPT_BT_RA1_NAME"),
											'Description' => GetMessage("BPT_BT_RA1_DESCR"),
											'Parameters' => '',
											'StatusMessage' => GetMessage("BPT_BT_RA1_STATUS_MESSAGE"),
											'SetStatusMessage' => 'Y',
											'TaskButtonMessage' => GetMessage("BPT_BT_RA1_TBM"),
											'Users' => array('Variable', 'ParameterBookkeeper'),
											'Title' => GetMessage("BPT_BT_RA1_TITLE")
										)
									), 
									array(
										'Type' => 'AbsenceActivity',
										'Name' => 'A49292_56042_93493_74019',
										'Properties' => array(
											'AbsenceName' => GetMessage("BPT_BT_AA7_NAME"),
											'AbsenceDesrc' => GetMessage("BPT_BT_AA7_DESCR"),
											'AbsenceFrom' => "{=Template:date_start}",
											'AbsenceTo' => "{=Template:date_end}",
											'AbsenceState' => GetMessage("BPT_BT_AA7_STATE"),
											'AbsenceFinishState' => GetMessage("BPT_BT_AA7_FSTATE"),
											'AbsenceType' => 'ASSIGNMENT',
											'AbsenceUser' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT_BT_AA7_TITLE"),
										)
									), 
									array(
										'Type' => 'ReviewActivity',
										'Name' => 'A53073_25727_90841_44084',
										'Properties' => array(
											'ApproveType' => 'any',
											'OverdueDate' => '',
											'Name' => GetMessage("BPT_BT_RA2_NAME"),
											'Description' => GetMessage("BPT_BT_RA2_DESCR"),
											'Parameters' => '',
											'StatusMessage' => GetMessage("BPT_BT_RA2_STATUS_MESSAGE"),
											'SetStatusMessage' => 'Y',
											'TaskButtonMessage' => GetMessage("BPT_BT_RA2_TBM"),
											'Users' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT_BT_RA2_TITLE1")
										)
									), 
									array(
										'Type' => 'RequestInformationActivity',
										'Name' => 'A20394_79186_50371_19561',
										'Properties' => array(
											'OverdueDate' => '',
											'Name' => GetMessage("BPT_BT_RIA1_NAME"),
											'Description' => GetMessage("BPT_BT_RIA1_DESCR"),
											'Parameters' => '',
											'RequestedInformation' => array(
												array(
													'Name' => 'date_end_real',
													'Title' => GetMessage("BPT_BT_RIA1_DATE_END_REAL"),
													'Type' => 'S:DateTime',
													'Default' => '',
													'Required' => '1',
													'Multiple' => '0'
												), 
												array(
													'Name' => 'report',
													'Title' => GetMessage("BPT_BT_RIA1_REPORT"),
													'Type' => 'T',
													'Default' => '',
													'Required' => '1',
													'Multiple' => '0'
												), 
												array(
													'Name' => 'expenditures_real',
													'Title' => GetMessage("BPT_BT_RIA1_EXP_REAL"),
													'Type' => 'T',
													'Default' => '',
													'Required' => '1',
													'Multiple' => '0'
												)
											),
											'Users' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT_BT_RIA1_TITLE")
										)
									), 
									array(
										'Type' => 'SetStateTitleActivity',
										'Name' => 'A28739_11998_86132_91273',
										'Properties' => array(
											'TargetStateTitle' => GetMessage("BPT_BT_SSTA3_STATE_TITLE"),
											'Title' => GetMessage("BPT_BT_SSTA3_TITLE")
										)
									), 
									array(
										'Type' => 'SetFieldActivity',
										'Name' => 'A38493_95930_44627_9607',
										'Properties' => array(
											'FieldValue' => array(
												'DETAIL_TEXT' => '{=Variable:report}',
												'PROPERTY_date_end_real' => '{=Variable:date_end_real}',
												'PROPERTY_expenditures_real' => '{=Variable:expenditures_real}'
											),
											'Title' => GetMessage("BPT_BT_SFA2_TITLE")
										)
									), 
									array(
										'Type' => 'ReviewActivity',
										'Name' => 'A63230_58757_46425_24958',
										'Properties' => array(
											'ApproveType' => 'any',
											'OverdueDate' => '',
											'Name' => GetMessage("BPT_BT_RA3_NAME"),
											'Description' => GetMessage("BPT_BT_RA3_DESCR"),
											'Parameters' => '',
											'StatusMessage' => GetMessage("BPT_BT_RA3_STATUS_MESSAGE"),
											'SetStatusMessage' => 'Y',
											'TaskButtonMessage' => GetMessage("BPT_BT_RA3_TBM"),
											'Users' => array('Variable', 'ParameterBoss'),
											'Title' => GetMessage("BPT_BT_RA2_TITLE")
										)
									),
									array(
										'Type' => 'ReviewActivity',
										'Name' => 'A93774_95633_29799_95943',
										'Properties' => array(
											'ApproveType' => 'any',
											'OverdueDate' => '',
											'Name' => GetMessage("BPT_BT_RA4_NAME"),
											'Description' => GetMessage("BPT_BT_RA4_DESCR"),
											'Parameters' => '',
											'StatusMessage' => GetMessage("BPT_BT_RA4_STATUS_MESSAGE"),
											'SetStatusMessage' => 'Y',
											'TaskButtonMessage' => GetMessage("BPT_BT_RA4_TMB"),
											'Users' => array('Variable', 'ParameterBookkeeper'),
											'Title' => GetMessage("BPT_BT_RA4_TITLE")
										)
									), 
									array(
										'Type' => 'SetStateTitleActivity',
										'Name' => 'A32350_8379_33931_16721',
										'Properties' => array(
											'TargetStateTitle' => GetMessage("BPT_BT_SSTA4_STATE_TITLE"),
											'Title' => GetMessage("BPT_BT_SSTA4_TITLE")
										)
									)
								)
							), 
							array(
								'Type' => 'SequenceActivity',
								'Name' => 'A47770_28716_89715_34547',
								'Properties' => array(
									'Title' => GetMessage("BPT_BT_SA3_TITLE")
								),
								'Children' => array(
									array(
										'Type' => 'SetStateTitleActivity',
										'Name' => 'A91143_32832_79230_7668',
										'Properties' => array(
											'TargetStateTitle' => GetMessage("BPT_BT_SSTA5_STATE_TITLE"),
											'Title' => GetMessage("BPT_BT_SSTA5_TITLE")
										)
									), 
									array(
										'Type' => 'SocNetMessageActivity',
										'Name' => 'A877_42848_71789_77065',
										'Properties' => array(
											'MessageText' => GetMessage("BPT_BT_SNMA2_TEXT"),
											'MessageUserFrom' => array('A54165_38396_31015_81889', 'LastApprover'),
											'MessageUserTo' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT_BT_SNMA2_TITLE")
										)
									)
								)
							)
						)
					)
				)
			)
		);

		return $arBPTemplate;
	}

	public static function GetDocumentFields()
	{
		$arDocumentFields = array(
			array(
				"name" => GetMessage("BPT_BT_DF_COUNTRY"),
				"code" => "COUNTRY",
				"type" => "L",
				"multiple" => "N",
				"required" => "N",
				"options" => "",
			),
			array(
				"name" => GetMessage("BPT_BT_DF_CITY"),
				"code" => "CITY",
				"type" => "S",
				"multiple" => "N",
				"required" => "N",
				"options" => "",
			),
			array(
				"name" => GetMessage("BPT_BT_DF_TICKETS"),
				"code" => "tickets",
				"type" => "F",
				"multiple" => "Y",
				"required" => "N",
				"options" => "",
			),
			array(
				"name" => GetMessage("BPT_BT_DF_DATE_END_REAL"),
				"code" => "date_end_real",
				"type" => "S:DateTime",
				"multiple" => "N",
				"required" => "N",
				"options" => "",
			),
			array(
				"name" => GetMessage("BPT_BT_DF_EXP_REAL"),
				"code" => "expenditures_real",
				"type" => "T",
				"multiple" => "N",
				"required" => "N",
				"options" => "",
			),
		);

		$ar = GetCountryArray();
		for ($i = 0, $cnt = count($ar["reference"]); $i < $cnt; $i++)
			$arDocumentFields[0]['options'] .= (($i > 0) ? "\n" : "").$ar["reference"][$i];

		return $arDocumentFields;
	}
}

$bpTemplateObject = new CBPTemplates_BusinessTrips();
?>