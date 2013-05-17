<?
IncludeModuleLangFile(__FILE__);

class CAllSupportUpdate
{

	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllSupportUpdate<br>File: ".__FILE__;
	}
	
	public static function GetUpdateVersion()
	{
		return 12000004;
	}
	
	public static function CurrentVersionLowerThanUpdateVersion()
	{
		$supUpdVer = intval(COption::GetOptionString("support", "SUPPORT_UPDATE_VERSION"));
		return ($supUpdVer < CSupportUpdate::GetUpdateVersion());
	}
	
	public static function ChangeCurrentVersion()
	{
		COption::SetOptionString("support", "SUPPORT_UPDATE_VERSION", CSupportUpdate::GetUpdateVersion());
	}
	
	public static function Update()
	{
		if(CSupportUpdate::CurrentVersionLowerThanUpdateVersion())
		{
			
			$dbType = CSupportUpdate::GetBD();
				
			$res = self::AlterTables($dbType);
			if(!$res) return false;
				
			$res = self::SeparateSLAandTimeTable($dbType);
			if(!$res) return false;
					
			CSupportTimetableCache::toCache();
					
			CTicketReminder::RecalculateSupportDeadline();
					
			CTicketReminder::StartAgent();
					
			CSupportUpdate::ChangeCurrentVersion();
			
			self::SetHotKeys();
			
		}
	}
		
	public static function AlterTables($dbType)
	{
		
		global $DB;
		$err_mess = (CAllSupportUpdate::err_mess())."<br>Function: AlterTables<br>Line: ";
		
		//add tables
		$addTables = array(
		
			"b_ticket_timetable" => array(
				"MySQL" =>	"
	CREATE TABLE b_ticket_timetable
	(
		ID INT(18) not null auto_increment,
		NAME varchar(255) not null,
		DESCRIPTION text,
		PRIMARY KEY (ID)
	)
	",
				"MSSQL" =>	"
	CREATE TABLE b_ticket_timetable
	(
		ID int NOT NULL IDENTITY (1, 1),
		NAME varchar(255) NOT NULL,
		DESCRIPTION TEXT NULL
	)
GO
	ALTER TABLE b_ticket_timetable ADD CONSTRAINT PK_b_ticket_timetable PRIMARY KEY (ID)
GO
	",
				"Oracle" =>	"
	CREATE TABLE b_ticket_timetable
	(
		ID NUMBER(18) NOT NULL,
		NAME VARCHAR2(255 CHAR) NULL,
		DESCRIPTION CLOB NULL,
		PRIMARY KEY (ID)
	)
	/
	
	CREATE SEQUENCE SQ_b_ticket_timetable START WITH 1 INCREMENT BY 1 NOMINVALUE NOMAXVALUE NOCYCLE NOCACHE NOORDER
	/
	"				
			),
			
			"b_ticket_holidays" => array(
				"MySQL" =>	"
	CREATE TABLE b_ticket_holidays
	(
		ID INT(18) not null auto_increment,
		NAME varchar(255) not null,
		DESCRIPTION text,
		OPEN_TIME  varchar(10) not null default 'HOLIDAY',
		DATE_FROM datetime not null,
		DATE_TILL datetime not null,
		PRIMARY KEY (ID)
	)
				",
				"MSSQL" =>	"
	CREATE TABLE b_ticket_holidays
	(
		ID int NOT NULL IDENTITY (1, 1),
		NAME varchar(255) NOT NULL,
		DESCRIPTION TEXT NULL,
		OPEN_TIME varchar(255) NOT NULL,
		DATE_FROM datetime NOT NULL,
		DATE_TILL datetime NOT NULL
	)
GO
	ALTER TABLE b_ticket_holidays ADD CONSTRAINT PK_b_ticket_holidays PRIMARY KEY (ID)
GO
	ALTER TABLE b_ticket_holidays ADD CONSTRAINT DF_b_ticket_holidays_OPEN_TIME DEFAULT 'HOLIDAY' FOR OPEN_TIME
GO
	",
				"Oracle" =>	"
	CREATE TABLE b_ticket_holidays
	(
		ID NUMBER(18) NOT NULL,
		NAME VARCHAR2(255 CHAR) NULL,
		DESCRIPTION CLOB NULL,
		OPEN_TIME VARCHAR2(10 CHAR) DEFAULT ('HOLIDAY') NOT NULL,
		DATE_FROM DATE NOT NULL,
		DATE_TILL DATE NOT NULL,
		PRIMARY KEY (ID)
	)
	/
	
	CREATE SEQUENCE SQ_b_ticket_holidays START WITH 1 INCREMENT BY 1 NOMINVALUE NOMAXVALUE NOCYCLE NOCACHE NOORDER
	/
	"
			),
			
			"b_ticket_sla_2_holidays" => array(
				"MySQL" =>	"
	CREATE TABLE b_ticket_sla_2_holidays
	(
		SLA_ID INT(18) not null,
		HOLIDAYS_ID INT(18) not null
	)
	",
				"MSSQL" =>	"
	CREATE TABLE b_ticket_sla_2_holidays
	(
		SLA_ID int NOT NULL,
		HOLIDAYS_ID int NOT NULL
	)
	",
				"Oracle" =>	"
	CREATE TABLE b_ticket_sla_2_holidays
	(
		SLA_ID NUMBER(18) NOT NULL,
		HOLIDAYS_ID NUMBER(18) NOT NULL
	)
	/
	"
			),
			
			"b_ticket_search" => array(
				"MySQL" =>	"
	CREATE TABLE b_ticket_search
	(
		MESSAGE_ID INT(18) not null,
		SEARCH_WORD varchar(70) not null
	);
	
	ALTER TABLE b_ticket_search ADD INDEX UX_b_ticket_search(SEARCH_WORD)
	",
				"MSSQL" =>	"
	CREATE TABLE b_ticket_search
	(
		MESSAGE_ID int NOT NULL,
		SEARCH_WORD varchar(70) NOT NULL
	)
GO
	CREATE INDEX UX_b_ticket_search ON b_ticket_search (SEARCH_WORD)
GO
	",
				"Oracle" =>	"
	CREATE TABLE b_ticket_search
	(
		MESSAGE_ID NUMBER(18) NOT NULL,
		SEARCH_WORD VARCHAR2(70 CHAR) NULL
	)
	/
	
	CREATE INDEX UX_b_ticket_search ON b_ticket_search(SEARCH_WORD)
	/
	"
			),
			
			"b_ticket_timetable_cache" => array(
				"MySQL" =>	"
	CREATE TABLE b_ticket_timetable_cache
	(
		ID INT(18) not null auto_increment,
		SLA_ID INT(18) not null,
		DATE_FROM datetime not null,
		DATE_TILL datetime not null,
		W_TIME INT(18) not null,
		W_TIME_INC INT(18) not null,
		PRIMARY KEY (ID)
	)
				",
				"MSSQL" =>	"
	CREATE TABLE b_ticket_timetable_cache
	(
		ID int NOT NULL IDENTITY (1, 1),
		SLA_ID int NOT NULL,
		DATE_FROM datetime NOT NULL,
		DATE_TILL datetime NOT NULL,
		W_TIME int NOT NULL,
		W_TIME_INC int NOT NULL
	)
GO
	ALTER TABLE b_ticket_timetable_cache ADD CONSTRAINT PK_b_ticket_timetable_cache PRIMARY KEY (ID)
GO
	",
				"Oracle" =>	"
	CREATE TABLE b_ticket_timetable_cache
	(
		ID NUMBER(18) NOT NULL,
		SLA_ID NUMBER(18) NOT NULL,
		DATE_FROM DATE NOT NULL,
		DATE_TILL DATE NOT NULL,
		W_TIME NUMBER(18) NOT NULL,
		W_TIME_INC NUMBER(18) NOT NULL,
		PRIMARY KEY (ID)
	)
	/
	
	CREATE SEQUENCE SQ_b_ticket_timetable_cache START WITH 1 INCREMENT BY 1 NOMINVALUE NOMAXVALUE NOCYCLE NOCACHE NOORDER
	/
	"
			),
			
			
		);
		
		
		//delete fields
		$deleteFields = array(
			"b_ticket" => array(
				"DATE_OF_FIRST_USER_MSG_AFTER_SUP_MSG",
				"ID_OF_FIRST_USER_MSG_AFTER_SUP_MSG",
				"DATE_FIRST_USER_M_AFTER_SUP_M",
				"ID_FIRST_USER_M_AFTER_SUP_M",
			),
		);
		
		
		//add fields
		$addFields = array(
			"b_ticket" => array(
				array("FIELD" => "SUPPORT_DEADLINE", "MySQL" => "datetime null", "MSSQL" => "datetime NULL", "Oracle" => "DATE NULL",),
				array("FIELD" => "SUPPORT_DEADLINE_NOTIFY", "MySQL" => "datetime null", "MSSQL" => "datetime NULL", "Oracle" => "DATE NULL",),
				array("FIELD" => "D_1_USER_M_AFTER_SUP_M", "MySQL" => "datetime null", "MSSQL" => "datetime NULL", "Oracle" => "DATE NULL",),
				array("FIELD" => "ID_1_USER_M_AFTER_SUP_M", "MySQL" => "int(18) null", "MSSQL" => "int NULL", "Oracle" => "NUMBER(18) NULL",),
			),
			
			"b_ticket_sla"=> array(
				array("FIELD" => "TIMETABLE_ID", "MySQL" => "int(18) null", "MSSQL" => "int NULL", "Oracle" => "NUMBER(18) NULL",),
			),
			
			"b_ticket_sla_shedule" => array(
				array("FIELD" => "TIMETABLE_ID", "MySQL" => "int(18) null", "MSSQL" => "int NULL", "Oracle" => "NUMBER(18) NULL",),
			),
			
			"b_ticket_user_ugroup" => array(
				array("FIELD" => "CAN_MAIL_UPDATE_GROUP_MESSAGES", "MySQL" => "char(1) NOT NULL default 'N'", "MSSQL" => "char(1) NOT NULL DEFAULT 'N'", "Oracle" => "CHAR(1 CHAR) DEFAULT 'N' NOT NULL",),	
			),
		);
		
		if($DB->TableExists("b_ticket"))
		{
			foreach($addTables as $table => $arr)
			{
				if(!$DB->TableExists($table))
				{
					$arQuery = $DB->ParseSQLBatch(str_replace("\r", "", $arr[$dbType]));
					foreach($arQuery as $i => $sql)
					{
						$res = $DB->Query($sql, true);
						if(!$res) return false;
					}					
				}
			}
		}
		
		foreach($deleteFields as $table => $arr)
		{
			if($DB->TableExists($table))
			{
				foreach($arr as $n => $FN)
				{
					if($DB->Query("select $FN from $table WHERE 1=0", true))
					{
						$res = $DB->Query("ALTER TABLE $table DROP $FN", true);
						if(!$res) return false;
					}					
				}
			}
		}
		
		foreach($addFields as $table => $arr)
		{
			if($DB->TableExists($table))
			{
				foreach($arr as $n => $arrF)
				{
					$FN = $arrF["FIELD"];
					$FT = $arrF[$dbType];
					if(!$DB->Query("select $FN from $table WHERE 1=0", true))
					{
						$res = $DB->Query("ALTER TABLE $table ADD $FN $FT", true);
						if(!$res) return false;
					}
				}
			}
		}
		return true;			
	}
	
	public static function SeparateSLAandTimeTable($dbType)
	{
		global $DB;
		$err_mess = (CAllSupportUpdate::err_mess())."<br>Function: SeparateSLAandTimeTable<br>Line: ";
		
		$strUsers = implode(",", CTicket::GetSupportTeamAndAdminUsers());
		$strSql0 = "
				b_ticket
					INNER JOIN (
						SELECT
							TM.TICKET_ID ID,
							MIN(TM.ID) M_ID
						FROM
							b_ticket_message TM
							INNER JOIN (
								SELECT
									T.ID ID,
									MAX(" . CTicket::isnull("TM.ID", "0") . ") M_ID
								FROM
									b_ticket T
									LEFT JOIN b_ticket_message TM
										ON T.ID = TM.TICKET_ID
											AND (TM.IS_LOG='N' OR TM.IS_LOG IS NULL OR " . $DB->Length("TM.IS_LOG") . " <= 0)
											AND TM.OWNER_USER_ID IN ($strUsers)
								WHERE
									T.DATE_CLOSE IS NULL
								GROUP BY
									T.ID
							) AS Q
								ON TM.TICKET_ID = Q.ID
									AND TM.ID > Q.M_ID
						GROUP BY
							TM.TICKET_ID
					) AS Q
						ON b_ticket.ID = Q.ID
					INNER JOIN b_ticket_message AS M
						ON Q.M_ID = M.ID
			";
		
		$updateQueries = array(
		
			"b_ticket_timetable,b_ticket_sla,b_ticket_sla_shedule" => array(
				0 => array(
					"MySQL" =>	"
	INSERT INTO b_ticket_timetable (NAME, DESCRIPTION)
		SELECT NAME, ID
		FROM b_ticket_sla
					",
					
					"MSSQL" =>	"
	INSERT INTO b_ticket_timetable (NAME, DESCRIPTION)
		SELECT NAME, CAST(CAST(ID AS varchar) AS text)
		FROM b_ticket_sla
					",
					
					"Oracle" =>	"
	INSERT INTO b_ticket_timetable (ID, NAME, DESCRIPTION)
		SELECT SQ_b_ticket_timetable.nextval, NAME, ID  
		FROM b_ticket_sla
					"
				),

				1 => array(
					"MySQL" =>	"
	UPDATE b_ticket_sla AS S
		INNER JOIN b_ticket_timetable AS T
			ON (S.ID = cast(T.DESCRIPTION as UNSIGNED))
				AND T.DESCRIPTION IS NOT NULL
				AND S.TIMETABLE_ID IS NULL
	SET S.TIMETABLE_ID = T.ID
					",
					
					"MSSQL" =>	"
	UPDATE b_ticket_sla
	SET b_ticket_sla.TIMETABLE_ID = T.ID
	FROM
		b_ticket_sla
		INNER JOIN b_ticket_timetable AS T
			ON (b_ticket_sla.ID = CAST(CAST(T.DESCRIPTION AS varchar) AS int))
				AND T.DESCRIPTION IS NOT NULL
				AND b_ticket_sla.TIMETABLE_ID IS NULL
					",
					
					"Oracle" =>	"
	UPDATE b_ticket_sla SET TIMETABLE_ID = (
		SELECT T.ID
		FROM b_ticket_timetable T
		WHERE
			b_ticket_sla.ID =  CAST(CAST(T.DESCRIPTION as VARCHAR2(18 CHAR)) as int)
			AND T.DESCRIPTION IS NOT NULL
		)
	WHERE
		TIMETABLE_ID IS NULL
					"
				),
				
				2 => array(
					"MySQL" =>	"
	UPDATE b_ticket_sla_shedule AS SS
		INNER JOIN b_ticket_timetable AS T
			ON (SS.SLA_ID = cast(T.DESCRIPTION as UNSIGNED)) 
				AND T.DESCRIPTION IS NOT NULL
	SET SS.TIMETABLE_ID = T.ID
					",
					
					"MSSQL" =>	"
	UPDATE b_ticket_sla_shedule SET TIMETABLE_ID = (
		SELECT T.ID
		FROM 
			b_ticket_timetable AS T
		WHERE
			b_ticket_sla_shedule.SLA_ID = CAST(CAST(T.DESCRIPTION AS varchar) AS int)
			AND T.DESCRIPTION IS NOT NULL
		)
					",
					
					"Oracle" =>	"
	UPDATE b_ticket_sla_shedule SET TIMETABLE_ID = (
		SELECT T.ID
		FROM 
			b_ticket_timetable T
		WHERE
			b_ticket_sla_shedule.SLA_ID = CAST(CAST(T.DESCRIPTION as VARCHAR2(18 CHAR)) as int)
			AND T.DESCRIPTION IS NOT NULL
		)
					"
				),
				
				3 => array(
					"MySQL" =>	"
	UPDATE b_ticket_timetable
	SET DESCRIPTION = NULL
					",
					
					"MSSQL" =>	"
	UPDATE b_ticket_timetable
	SET DESCRIPTION = NULL
					",
					
					"Oracle" =>	"
	UPDATE b_ticket_timetable
	SET DESCRIPTION = NULL
					",
				),
			),
			
			"b_ticket" => array(
				0 => array(
					"MySQL" =>	"
	UPDATE $strSql0
	SET
	b_ticket.D_1_USER_M_AFTER_SUP_M = M.DATE_CREATE,
	b_ticket.ID_1_USER_M_AFTER_SUP_M = M.ID,
	b_ticket.LAST_MESSAGE_BY_SUPPORT_TEAM = 'N'
					",
					
					"MSSQL" =>	"
	UPDATE b_ticket
	SET
	b_ticket.D_1_USER_M_AFTER_SUP_M = M.DATE_CREATE,
	b_ticket.ID_1_USER_M_AFTER_SUP_M = M.ID,
	b_ticket.LAST_MESSAGE_BY_SUPPORT_TEAM = 'N'
	FROM $strSql0
					",
					
					"Oracle" =>	"
	UPDATE b_ticket T0
	SET (D_1_USER_M_AFTER_SUP_M, ID_1_USER_M_AFTER_SUP_M, LAST_MESSAGE_BY_SUPPORT_TEAM) = (
		SELECT
			M.DATE_CREATE,
			M.ID,
			'N'
		FROM ".str_replace(" AS ", " ", $strSql0)."
		WHERE b_ticket.ID = T0.ID
	)
					",
				),
			),
		);
		
		foreach($updateQueries as $checkTables => $arT)
		{
			$arCT = explode(",", $checkTables);
			$skipU = false;
			foreach($arCT as $n => $t)
			{
				if(!$DB->TableExists($t))
				{
					$skipU = true;
				}
			}
			if(!$skipU)
			{
				foreach($arT as $n1 => $arQ)
				{
					$arQuery = $DB->ParseSQLBatch(str_replace("\r", "", $arQ[$dbType]));
					foreach($arQuery as $i => $sql)
					{
						$res = $DB->Query($sql, true);
						if(!$res) return false;
					}
					
				}
			}
		}
		
		return true;		
	}

	public static function SetHotKeys()
	{
		$arHK = array(
			"B" => "Alt+66",
			"I" => "Alt+73",
			"U" => "Alt+85",
			"QUOTE" => "Alt+81",
			"CODE" => "Alt+67",
			"TRANSLIT" => "Alt+84",
		);
				
		$hkc = new CHotKeysCode;
		foreach($arHK as $s => $hk)
		{
			$className = "TICKET_EDIT_$s";
			$arHKC = array (
				CLASS_NAME => $className,
				CODE => "var d=document.getElementById('$s'); if (d) d.click();",
				NAME => " ($id)",
				TITLE_OBJ => "TICKET_EDIT_" . $s . "_T",
				IS_CUSTOM => "1"
			);
			
			$objK = $hkc->GetList(array(), Array("CLASS_NAME"=>$className));
			if($arK = $objK->Fetch())
			{
				$hkc->Update($arK["ID"],$arHKC);
			}
			else
			{
				$id = $hkc->Add($arHKC);
				if($id > 0)
				{
					$result = CHotKeys::GetInstance()->AddDefaultKeyToAll($id, $hk);
				}
			}
		}	
	}
}

?>