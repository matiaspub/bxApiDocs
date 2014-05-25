<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile(__FILE__);

class CBPTemplates_Vacation
{
	public static function GetName()
	{
		return GetMessage("BPT1_TTITLE");
	}

	public static function GetVariables()
	{
		$arBPTemplateVariables = array(
			'ParameterOpRead' => array(
				'Name' => GetMessage("BPT1_BT_PARAM_OP_READ"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => 'author'
			),
			'ParameterOpCreate' => array(
				'Name' => GetMessage("BPT1_BT_PARAM_OP_CREATE"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => 'author'
			),
			'ParameterOpAdmin' => array(
				'Name' => GetMessage("BPT1_BT_PARAM_OP_ADMIN"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => ''
			),
			'ParameterBoss' => array(
				'Name' => GetMessage("BPT1_BT_PARAM_BOSS"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => ''
			),
			'ParameterBookkeeper' => array(
				'Name' => GetMessage("BPT1_BT_PARAM_BOOK"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => true,
				'Multiple' => true,
				'Default' => ''
			),
		);

		return $arBPTemplateVariables;
	}

	public static function GetParameters()
	{
		$arBPTemplateParameters = array(
			'TargetUser' => array(
				'Name' => GetMessage("BPT1_BT_P_TARGET"),
				'Description' => '',
				'Type' => 'S:UserID',
				'Required' => false,
				'Multiple' => false,
				'Default' => ''
			),
			'date_start' => array(
				'Name' => GetMessage("BPT1_BT_T_DATE_START"),
				'Description' => '',
				'Type' => 'S:DateTime',
				'Required' => true,
				'Multiple' => false,
				'Default' => ''
			),
			'date_end' => array(
				'Name' => GetMessage("BPT1_BT_T_DATE_END"),
				'Description' => '',
				'Type' => 'S:DateTime',
				'Required' => true,
				'Multiple' => false,
				'Default' => ''
			),
		);

		return $arBPTemplateParameters;
	}

	public static function GetTemplate()
	{
		$arBPTemplate = array(
			array(
				'Type' => 'SequentialWorkflowActivity',
				'Name' => 'Template',
				'Properties' => array(
					'Title' => GetMessage("BPT1_BT_SWA"),
					'Permission' => array("read" => array('Variable', 'ParameterOpRead'), "create" => array('Variable', 'ParameterOpCreate'), "admin" => array('Variable', 'ParameterOpAdmin'))
				),
				'Children' => array(
					array(
						'Type' => 'SetFieldActivity',
						'Name' => 'A54792_44873_81417_17348',
						'Properties' => array(
							'FieldValue' => array(
								'ACTIVE_FROM' => '{=Template:date_start}',
								'ACTIVE_TO' => '{=Template:date_end}',
								'NAME' => '{=Template:TargetUser_printable}, {=Template:date_start} - {=Template:date_end}',
								'PROPERTY_approving' => 'x'
							),
							'Title' => GetMessage("BPT1_BT_SFA1_TITLE")
						)
					), 
					array(
						'Type' => 'SetStateTitleActivity',
						'Name' => 'A99154_51391_34111_46585',
						'Properties' => array(
							'TargetStateTitle' => GetMessage("BPT1_BT_STA1_STATE_TITLE"),
							'Title' => GetMessage("BPT1_BT_STA1_TITLE")
						)
					), 
					array(
						'Type' => 'WhileActivity',
						'Name' => 'A65993_8943_32801_73040',
						'Properties' => array(
							'Title' => GetMessage("BPT1_BT_CYCLE"),
							'fieldcondition' => array(array('PROPERTY_approving', '=', 'x'))
						),
						'Children' => array(
							array(
								'Type' => 'SequenceActivity',
								'Name' => 'A27555_16461_17196_39771',
								'Properties' => array('Title' => GetMessage("BPT1_BT_SA1_TITLE")),
								'Children' => array(
									array(
										'Type' => 'ApproveActivity',
										'Name' => 'A94751_67978_49922_99999',
										'Properties' => array(
											'ApproveType' => 'any',
											'OverdueDate' => '',
											'ApproveMinPercent' => '50',
											'ApproveWaitForAll' => 'N',
											'Name' => GetMessage("BPT1_BT_AA11_NAME"),
											'Description' => GetMessage("BPT1_BT_AA11_DESCR"),
											'Parameters' => '',
											'StatusMessage' => GetMessage("BPT1_BT_AA11_STATUS_MESSAGE"),
											'SetStatusMessage' => 'Y',
											'Users' => array('Variable', 'ParameterBoss'),
											'Title' => GetMessage("BPT1_BT_AA11_TITLE")
										),
										'Children' => array(
											array(
												'Type' => 'SequenceActivity',
												'Name' => 'A85668_52803_44143_49694',
												'Properties' => array('Title' => GetMessage("BPT1_BT_SA1_TITLE")),
												'Children' => array(
													array(
														'Type' => 'RequestInformationActivity',
														'Name' => 'A42698_12107_48239_41360',
														'Properties' => array(
															'OverdueDate' => '',
															'Name' => GetMessage("BPT1_BT_RIA11_NAME"),
															'Description' => GetMessage("BPT1_BT_RIA11_DESCR"),
															'Parameters' => '',
															'RequestedInformation' => array(
																array(
																	'Name' => 'need_additional_approve',
																	'Title' => GetMessage("BPT1_BT_RIA11_P1"),
																	'Type' => 'B',
																	'Default' => '',
																	'Required' => '0',
																	'Multiple' => '0'
																), 
																array(
																	'Name' => 'ParameterBoss',
																	'Title' => GetMessage("BPT1_BT_RIA11_P2"),
																	'Type' => 'S:UserID',
																	'Default' => '',
																	'Required' => '0',
																	'Multiple' => '0'
																)
															),
															'Users' => array('Variable', 'ParameterBoss'),
															'Title' => GetMessage("BPT1_BT_RIA11_TITLE")
														)
													), 
													array(
														'Type' => 'IfElseActivity',
														'Name' => 'A16288_6973_71334_75760',
														'Properties' => array('Title' => GetMessage("BPT1_BT_IF11_N")),
														'Children' => array(
															array(
																'Type' => 'IfElseBranchActivity',
																'Name' => 'A43136_44567_10680_30159',
																'Properties' => array(
																	'Title' => GetMessage("BPT1_BT_IEBA1_V1"),
																	'propertyvariablecondition' => array(array('need_additional_approve', '=', 'Y'))
																)
															),
															array(
																'Type' => 'IfElseBranchActivity',
																'Name' => 'A65726_71247_68427_60591',
																'Properties' => array('Title' => GetMessage("BPT1_BT_IEBA2_V2")),
																'Children' => array(
																	array(
																		'Type' => 'SetFieldActivity',
																		'Name' => 'A43342_8811_95090_90018',
																		'Properties' => array(
																			'FieldValue' => array(
																				'PROPERTY_approving' => 'y'
																			),
																			'Title' => GetMessage("BPT1_BT_SFA12_TITLE")
																		)
																	),
																	array(
																		'Type' => 'SetStateTitleActivity',
																		'Name' => 'A2560_50199_5564_95292',
																		'Properties' => array(
																			'TargetStateTitle' => GetMessage("BPT1_BT_SFTA12_ST"),
																			'Title' => GetMessage("BPT1_BT_SFTA12_T")
																		)
																	)
																)
															)
														)
													)
												)
											), 
											array(
												'Type' => 'SequenceActivity',
												'Name' => 'A40542_41453_94895_70387',
												'Properties' => array('Title' => GetMessage("BPT1_BT_SA1_TITLE")),
												'Children' => array(
													array(
														'Type' => 'SetFieldActivity',
														'Name' => 'A70022_19949_94473_76597',
														'Properties' => array(
															'FieldValue' => array(
																'PROPERTY_approving' => 'n'
															),
															'Title' => GetMessage("BPT1_BT_SFA12_TITLE")
														)
													), 
													array(
														'Type' => 'SetStateTitleActivity',
														'Name' => 'A80110_96659_73401_33711',
														'Properties' => array(
															'TargetStateTitle' => GetMessage("BPT1_BT_SSTA14_ST"),
															'Title' => GetMessage("BPT1_BT_SSTA14_T")
														)
													)
												)
											)
										)
									)
								)
							)
						)
					),

					array(
						'Type' => 'IfElseActivity',
						'Name' => 'A74964_46906_3754_79133',
						'Properties' => array('Title' => GetMessage("BPT1_BT_IF11_N")),
						'Children' => array(
							array(
								'Type' => 'IfElseBranchActivity',
								'Name' => 'A92164_76962_83081_44454',
								'Properties' => array(
									'Title' => GetMessage("BPT1_BT_IEBA15_V1"),
									'fieldcondition' => array(array('PROPERTY_approving', '=', 'y'))
								),
								'Children' => array(
									array(
										'Type' => 'SocNetMessageActivity',
										'Name' => 'A70194_97682_35832_41687',
										'Properties' => array(
											'MessageText' => GetMessage("BPT1_BT_SNMA16_TEXT"),
											'MessageUserFrom' => array("A94751_67978_49922_99999", "LastApprover"),
											'MessageUserTo' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT1_BT_SNMA16_TITLE")
										)
									),
									array(
										'Type' => 'ReviewActivity',
										'Name' => 'A41318_52246_80265_83609',
										'Properties' => array(
											'ApproveType' => 'any',
											'OverdueDate' => '',
											'Name' => GetMessage("BPT1_BT_RA17_NAME"),
											'Description' => GetMessage("BPT1_BT_RA17_DESCR"),
											'Parameters' => '',
											'StatusMessage' => GetMessage("BPT1_BT_RA17_STATUS_MESSAGE"),
											'SetStatusMessage' => 'Y',
											'TaskButtonMessage' => GetMessage("BPT1_BT_RA17_TBM"),
											'Users' => array("Variable", "ParameterBookkeeper"),
											'Title' => GetMessage("BPT1_BT_RA17_TITLE")
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
											'AbsenceType' => 'VACATION',
											'AbsenceUser' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT_BT_AA7_TITLE"),
										)
									), 
									array(
										'Type' => 'SetStateTitleActivity',
										'Name' => 'A80110_96659_73401_98765',
										'Properties' => array(
											'TargetStateTitle' => GetMessage("BPT1_BT_SSTA18_ST"),
											'Title' => GetMessage("BPT1_BT_SSTA18_T")
										)
									)
								)
							), 
							array(
								'Type' => 'IfElseBranchActivity',
								'Name' => 'A30959_26245_33197_97212',
								'Properties' => array('Title' => GetMessage("BPT1_BT_IEBA15_V2")),
								'Children' => array(
									array(
										'Type' => 'SocNetMessageActivity',
										'Name' => 'A61811_43013_42560_16921',
										'Properties' => array(
											'MessageText' => GetMessage("BPT1_BT_SNMA18_TEXT"),
											'MessageUserFrom' => array("A94751_67978_49922_99999", "LastApprover"),
											'MessageUserTo' => array('Template', 'TargetUser'),
											'Title' => GetMessage("BPT1_BT_SNMA18_TITLE")
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
				"name" => GetMessage("BPT1_BTF_P_APP"),
				"code" => "approving",
				"type" => "L",
				"multiple" => "N",
				"required" => "N",
				"options" => GetMessage("BPT1_BTF_P_APPS"),
			),
		);

		return $arDocumentFields;
	}
}

$bpTemplateObject = new CBPTemplates_Vacation();
?>