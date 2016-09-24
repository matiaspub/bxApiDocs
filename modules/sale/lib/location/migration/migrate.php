<?php

namespace Bitrix\Sale\Location\Migration;

use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Tax\Rate;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;

use Bitrix\Sale\Location;
use Bitrix\Sale\Location\DB\Helper;
use Bitrix\Sale\Location\DB\BlockInserter;

include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/update_class.php");

Loc::loadMessages(__FILE__);

class CUpdaterLocationPro extends \CUpdater implements \Serializable
{
	// old tables that will be reused
	const TABLE_LOCATION = 'b_sale_location';
	const TABLE_LOCATION_GROUP = 'b_sale_location_group';
	const TABLE_LOCATION2GROUP = 'b_sale_location2location_group';
	const TABLE_DELIVERY2LOCATION = 'b_sale_delivery2location';
	const TABLE_TAX2LOCATION = 'b_sale_tax2location';
	const TABLE_LOCATION_GROUP_NAME = 'b_sale_location_group_lang';

	// new tables to be created
	const TABLE_LOCATION_NAME = 'b_sale_loc_name';
	const TABLE_LOCATION_EXTERNAL = 'b_sale_loc_ext';
	const TABLE_LOCATION_EXTERNAL_SERVICE = 'b_sale_loc_ext_srv';
	const TABLE_LOCATION_TYPE = 'b_sale_loc_type';
	const TABLE_LOCATION_TYPE_NAME = 'b_sale_loc_type_name';
	const TABLE_LOCATION2SITE = 'b_sale_loc_2site'; // ?

	// obsolete tables to get data from
	const TABLE_LOCATION_ZIP = 'b_sale_location_zip';
	const TABLE_LOCATION_COUNTRY_NAME = 'b_sale_location_country_lang';
	const TABLE_LOCATION_REGION_NAME = 'b_sale_location_region_lang';
	const TABLE_LOCATION_CITY_NAME = 'b_sale_location_city_lang';

	// temporal tables
	const TABLE_TEMP_TREE = 'b_sale_location_temp_tree';
	const TABLE_LEGACY_RELATIONS = 'b_sale_loc_legacy';

	const MODULE_ID = 'sale';

	protected $data = array();

	// to CUpdater ?
	const DB_TYPE_MYSQL = 'MySQL';
	const DB_TYPE_MSSQL = 'MSSQL';
	const DB_TYPE_ORACLE = 'Oracle';

	const DB_TYPE_MYSQL_LC = 'mysql';
	const DB_TYPE_MSSQL_LC = 'mssql';
	const DB_TYPE_ORACLE_LC = 'oracle';

	public function __construct()
	{
		global $DBType;
		$this->Init($curPath = "", $DBType, $updaterName = "", $curDir = "", self::MODULE_ID, "DB");
	}

	public function serialize()
	{
		return serialize($this->data);
	}

	public function unserialize($data)
	{
		global $DBType;
		$this->Init($curPath = "", $DBType, $updaterName = "", $curDir = "", self::MODULE_ID, "DB");
		$this->data = unserialize($data);
	}

	public static function updateDBSchemaRestoreLegacyIndexes()
	{
		$dbConnection = \Bitrix\Main\HttpApplication::getConnection();

		$agentName = '\Bitrix\Sale\Location\Migration\CUpdaterLocationPro::updateDBSchemaRestoreLegacyIndexes();';

		if(!Helper::checkIndexNameExists('IX_B_SALE_LOC_EXT_LID_SID', 'b_sale_loc_ext'))
		{
			$dbConnection->query('create index IX_B_SALE_LOC_EXT_LID_SID on b_sale_loc_ext (LOCATION_ID, SERVICE_ID)');
			return $agentName;
		}

		if(!Helper::checkIndexNameExists('IXS_LOCATION_COUNTRY_ID', 'b_sale_location'))
		{
			$dbConnection->query('create index IXS_LOCATION_COUNTRY_ID on b_sale_location (COUNTRY_ID)');
			return $agentName;
		}

		if(!Helper::checkIndexNameExists('IXS_LOCATION_REGION_ID', 'b_sale_location'))
		{
			$dbConnection->query('create index IXS_LOCATION_REGION_ID on b_sale_location (REGION_ID)');
			return $agentName;
		}

		if(!Helper::checkIndexNameExists('IXS_LOCATION_CITY_ID', 'b_sale_location'))
		{
			$dbConnection->query('create index IXS_LOCATION_CITY_ID on b_sale_location (CITY_ID)');
		}

		return false;
	}

	// only for module_updater
	public static function updateDBSchemaRenameIndexes()
	{
		global $DB, $DBType;

		$updater = new \CUpdater();
		$updater->Init($curPath = "", $DBType, $updaterName = "", $curDir = "", "sale", "DB");

		$locationTableExists = 	$updater->TableExists("b_sale_location");

		if($locationTableExists) // module might not be installed, but tables may exist
		{
			// b_sale_location
			if(static::checkIndexExistsByName('IX_SALE_LOCATION_CODE', 'b_sale_location'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_CODE', 'b_sale_location');
				$DB->query('create unique index IX_B_SALE_LOC_CODE on b_sale_location (CODE)');
			}

			if(static::checkIndexExistsByName('IX_SALE_LOCATION_MARGINS', 'b_sale_location'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_MARGINS', 'b_sale_location');
				$DB->query('create index IX_B_SALE_LOC_MARGINS on b_sale_location (LEFT_MARGIN, RIGHT_MARGIN)');
			}

			if(static::checkIndexExistsByName('IX_SALE_LOCATION_MARGINS_REV', 'b_sale_location'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_MARGINS_REV', 'b_sale_location');
				$DB->query('create index IX_B_SALE_LOC_MARGINS_REV on b_sale_location (RIGHT_MARGIN, LEFT_MARGIN)');
			}

			if(static::checkIndexExistsByName('IX_SALE_LOCATION_PARENT', 'b_sale_location'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_PARENT', 'b_sale_location');
				$DB->query('create index IX_B_SALE_LOC_PARENT on b_sale_location (PARENT_ID)');
			}

			if(static::checkIndexExistsByName('IX_SALE_LOCATION_DL', 'b_sale_location'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_DL', 'b_sale_location');
				$DB->query('create index IX_B_SALE_LOC_DL on b_sale_location (DEPTH_LEVEL)');
			}

			if(static::checkIndexExistsByName('IX_SALE_LOCATION_TYPE', 'b_sale_location'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_TYPE', 'b_sale_location');
				$DB->query('create index IX_B_SALE_LOC_TYPE on b_sale_location (TYPE_ID)');
			}

			// b_sale_loc_name
			if(static::checkIndexExistsByName('IX_SALE_L_NAME_NAME_UPPER', 'b_sale_loc_name'))
			{
				static::dropIndexByName('IX_SALE_L_NAME_NAME_UPPER', 'b_sale_loc_name');
				$DB->query('create index IX_B_SALE_LOC_NAME_NAME_U on b_sale_loc_name (NAME_UPPER)');
			}

			if(static::checkIndexExistsByName('IX_SALE_L_NAME_LID_LID', 'b_sale_loc_name'))
			{
				static::dropIndexByName('IX_SALE_L_NAME_LID_LID', 'b_sale_loc_name');
				$DB->query('create index IX_B_SALE_LOC_NAME_LI_LI on b_sale_loc_name (LOCATION_ID, LANGUAGE_ID)');
			}

			// b_sale_loc_type_name
			if(static::checkIndexExistsByName('IX_SALE_L_TYPE_NAME_TID_LID', 'b_sale_loc_type_name'))
			{
				static::dropIndexByName('IX_SALE_L_TYPE_NAME_TID_LID', 'b_sale_loc_type_name');
				$DB->query('create index IX_B_SALE_LOC_TYPE_NAME_TI_LI on b_sale_loc_type_name (TYPE_ID, LANGUAGE_ID)');
			}

			// b_sale_location_group
			if(static::checkIndexExistsByName('IX_SALE_LOCATION_GROUP_CODE', 'b_sale_location_group'))
			{
				static::dropIndexByName('IX_SALE_LOCATION_GROUP_CODE', 'b_sale_location_group');
				$DB->query('create unique index IX_B_SALE_LOC_GROUP_CODE on b_sale_location_group (CODE)');
			}
		}
	}

	protected static function dropIndexByName($indexName, $tableName)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbConnType = $dbConnection->getType();

		if($dbConnType == self::DB_TYPE_MYSQL_LC)
			$dbConnection->query("alter table {$tableName} drop index {$indexName}");
		elseif($dbConnType == self::DB_TYPE_ORACLE_LC)
			$dbConnection->query("drop index {$indexName}");
		elseif($dbConnType == self::DB_TYPE_MSSQL_LC)
			$dbConnection->query("drop index {$indexName} on {$tableName}");

		return true;
	}

	protected static function checkIndexExistsByName($indexName, $tableName)
	{
		if(!strlen($indexName) || !strlen($tableName))
			return false;

		$dbConnection = Main\HttpApplication::getConnection();
		$dbConnType = $dbConnection->getType();

		if($dbConnType == self::DB_TYPE_MYSQL_LC)
			$res = $dbConnection->query("show index from ".$tableName);
		elseif($dbConnType == self::DB_TYPE_ORACLE_LC)
			$res = $dbConnection->query("SELECT INDEX_NAME as Key_name FROM USER_IND_COLUMNS WHERE TABLE_NAME = '".ToUpper($tableName)."'");
		elseif($dbConnType == self::DB_TYPE_MSSQL_LC)
		{
			$res = $dbConnection->query("SELECT si.name Key_name
				FROM sysindexkeys s
					INNER JOIN syscolumns c ON s.id = c.id AND s.colid = c.colid
					INNER JOIN sysobjects o ON s.id = o.Id AND o.xtype = 'U'
					LEFT JOIN sysindexes si ON si.indid = s.indid AND si.id = s.id
				WHERE o.name = '".ToUpper($tableName)."'");
		}

		while($item = $res->fetch())
		{
			if($item['Key_name'] == $indexName || $item['KEY_NAME'] == $indexName)
				return true;
		}

		return false;
	}

	// function stands for the corresponding block in module_updater.php
	public static function updateDBSchema()
	{
		global $DB, $DBType;

		$updater = new \CUpdater();
		$updater->Init($curPath = "", $DBType, $updaterName = "", $curDir = "", "sale", "DB");

		// table existence check
		$locationTableExists = 					$updater->TableExists("b_sale_location");

		if($locationTableExists) // module might not be installed, but tables may exist
		{
			$locationGroupTableExists = 			$updater->TableExists("b_sale_location_group");
			$locationGroupNameTableExists = 		$updater->TableExists("b_sale_location_group_lang");

			$locationNameTableExists = 				$updater->TableExists("b_sale_loc_name");
			$locationExternalServiceTableExists = 	$updater->TableExists("b_sale_loc_ext_srv");
			$locationExternalTableExists = 			$updater->TableExists("b_sale_loc_ext");
			$locationTypeTableExists = 				$updater->TableExists("b_sale_loc_type");
			$locationTypeNameTableExists = 			$updater->TableExists("b_sale_loc_type_name");
			$locationLoc2SiteTableExists = 			$updater->TableExists("b_sale_loc_2site");
			$locationDefaul2SiteTableExists = 		$updater->TableExists("b_sale_loc_def2site");

			$tax2LocationTableExists = 				$updater->TableExists("b_sale_tax2location");
			$delivery2LocationTableExists = 		$updater->TableExists("b_sale_delivery2location");

			// adding columns to B_SALE_LOCATION

			// if CODE not exists, add it
			if (!$DB->query("select CODE from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD CODE varchar(100) not null",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD CODE varchar(100) default '' NOT NULL", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD CODE VARCHAR2(100 CHAR) default '' NOT NULL", //OK // oracle allows to add not-null column only with default specified
				));
			}

			// if CODE exists, copy values from ID and add index
			if ($DB->query("select CODE from b_sale_location WHERE 1=0", true))
			{
				if (!$DB->IndexExists('b_sale_location', array('CODE')))
				{
					$DB->query("update b_sale_location set CODE = ID"); // OK: oracle, mssql
					$DB->query("CREATE UNIQUE INDEX IX_B_SALE_LOC_CODE ON b_sale_location (CODE)"); // OK: oracle, mssql
				}
			}

			// create LEFT_MARGIN
			if (!$DB->query("select LEFT_MARGIN from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD LEFT_MARGIN int",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD LEFT_MARGIN int", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD LEFT_MARGIN NUMBER(18)", // OK
				));
			}

			// create RIGHT_MARGIN
			if (!$DB->query("select RIGHT_MARGIN from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD RIGHT_MARGIN int",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD RIGHT_MARGIN int", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD RIGHT_MARGIN NUMBER(18)", // OK
				));
			}

			$lMarginExists = $DB->query("select LEFT_MARGIN from b_sale_location WHERE 1=0", true);
			$rMarginExists = $DB->query("select RIGHT_MARGIN from b_sale_location WHERE 1=0", true);

			// add indexes if margins exist, but indexes not
			if($lMarginExists && $rMarginExists)
			{
				if (!$DB->IndexExists('b_sale_location', array('LEFT_MARGIN', 'RIGHT_MARGIN')))
				{
					$DB->query("CREATE INDEX IX_B_SALE_LOC_MARGINS ON b_sale_location (LEFT_MARGIN, RIGHT_MARGIN)"); // OK: oracle, mssql
				}
				if (!$DB->IndexExists('b_sale_location', array('RIGHT_MARGIN', 'LEFT_MARGIN')))
				{
					$DB->query("CREATE INDEX IX_B_SALE_LOC_MARGINS_REV ON b_sale_location (RIGHT_MARGIN, LEFT_MARGIN)"); // OK: oracle, mssql
				}
			}

			// add PARENT_ID
			if (!$DB->query("select PARENT_ID from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD PARENT_ID int DEFAULT '0'",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD PARENT_ID int DEFAULT '0'", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD PARENT_ID NUMBER(18) DEFAULT '0'", // OK
				));
			}

			// add index, if not exist for PARENT_ID, that exists
			if ($DB->query("select PARENT_ID from b_sale_location WHERE 1=0", true) && !$DB->IndexExists('b_sale_location', array('PARENT_ID')))
			{
				$DB->query('CREATE INDEX IX_B_SALE_LOC_PARENT ON b_sale_location (PARENT_ID)'); // OK: oracle, mssql
			}

			// add DEPTH_LEVEL
			if (!$DB->query("select DEPTH_LEVEL from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD DEPTH_LEVEL int default '1'",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD DEPTH_LEVEL int DEFAULT '1'", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD DEPTH_LEVEL NUMBER(18) DEFAULT '1'", // OK
				));
			}

			// add index, if not exist for DEPTH_LEVEL, that exists
			if ($DB->query("select DEPTH_LEVEL from b_sale_location WHERE 1=0", true) && !$DB->IndexExists('b_sale_location', array('DEPTH_LEVEL')))
			{
				$DB->query("CREATE INDEX IX_B_SALE_LOC_DL ON b_sale_location (DEPTH_LEVEL)"); // OK: oracle, mssql
			}

			// add TYPE_ID
			if (!$DB->query("select TYPE_ID from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD TYPE_ID int",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD TYPE_ID int", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD TYPE_ID NUMBER(18)", // OK
				));
			}

			// add index, if not exist for TYPE_ID, that exists
			if ($DB->query("select TYPE_ID from b_sale_location WHERE 1=0", true) && !$DB->IndexExists('b_sale_location', array('TYPE_ID')))
			{
				$DB->query("CREATE INDEX IX_B_SALE_LOC_TYPE ON b_sale_location (TYPE_ID)"); // OK: oracle, mssql
			}

			// add LATITUDE
			if (!$DB->query("select LATITUDE from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD LATITUDE decimal(8,6)",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD LATITUDE decimal(8,6)", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD LATITUDE NUMBER(8,6)", // OK
				));
			}

			// add LONGITUDE
			if (!$DB->query("select LONGITUDE from b_sale_location WHERE 1=0", true))
			{
				$updater->query(array(
					"MySQL" => "ALTER TABLE b_sale_location ADD LONGITUDE decimal(9,6)",
					"MSSQL" => "ALTER TABLE B_SALE_LOCATION ADD LONGITUDE decimal(9,6)", // OK
					"Oracle" => "ALTER TABLE B_SALE_LOCATION ADD LONGITUDE NUMBER(9,6)", // OK
				));
			}

			// dropping not-nulls

			if($DBType == 'mysql')
				$DB->query("ALTER TABLE b_sale_location MODIFY COUNTRY_ID int NULL");

			if($DBType == 'mssql')
				$DB->query("ALTER TABLE B_SALE_LOCATION ALTER COLUMN COUNTRY_ID int NULL");

			if($DBType == 'oracle')
			{
				// dropping not-nulls

				if($DB->query("SELECT * FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = 'B_SALE_LOCATION' and COLUMN_NAME = 'COUNTRY_ID' and NULLABLE = 'N'")->fetch())
				{
					//if ($DB->IndexExists('b_sale_location', array('COUNTRY_ID')))
					//	$DB->query('drop index IXS_LOCATION_COUNTRY_ID');

					$DB->query("ALTER TABLE B_SALE_LOCATION MODIFY ( COUNTRY_ID NUMBER(18) NULL)");
				}
				//if (!$DB->IndexExists('b_sale_location', array('COUNTRY_ID')))
				//	$DB->query('CREATE INDEX IXS_LOCATION_COUNTRY_ID ON B_SALE_LOCATION(COUNTRY_ID)');

				// altering sequences for oracle

				// new sequence for b_sale_location
				if (($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_SALE_LOCATION'", true)->fetch())) // OK
				{
					$DB->query("RENAME SQ_SALE_LOCATION TO SQ_B_SALE_LOCATION"); // OK
					$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOCATION_INSERT
						BEFORE INSERT
						ON B_SALE_LOCATION
						FOR EACH ROW
						BEGIN
							IF :NEW.ID IS NULL THEN
								SELECT SQ_B_SALE_LOCATION.NEXTVAL INTO :NEW.ID FROM dual;
							END IF;
						END;"); // OK

				}

				// new sequence for b_sale_location_group
				if ($locationGroupTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOCATION_GROUP'", true)->fetch())) // OK
				{
					$DB->query("RENAME SQ_SALE_LOCATION_GROUP TO SQ_B_SALE_LOCATION_GROUP"); // OK
					$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOCATION_GROUP_INSERT
						BEFORE INSERT
						ON B_SALE_LOCATION_GROUP
						FOR EACH ROW
						BEGIN
							IF :NEW.ID IS NULL THEN
								SELECT SQ_B_SALE_LOCATION_GROUP.NEXTVAL INTO :NEW.ID FROM dual;
							END IF;
						END;"); // OK
				}

				// new sequence for b_sale_location_group_lang
				if ($locationGroupNameTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOCATION_GROUP_LANG'", true)->fetch())) // OK
				{
					$DB->query("RENAME SQ_SALE_LOCATION_GROUP_LANG TO SQ_B_SALE_LOCATION_GROUP_LANG"); // OK
					$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOCGR_LANG_INSERT
						BEFORE INSERT
						ON B_SALE_LOCATION_GROUP_LANG
						FOR EACH ROW
						BEGIN
							IF :NEW.ID IS NULL THEN
								SELECT SQ_B_SALE_LOCATION_GROUP_LANG.NEXTVAL INTO :NEW.ID FROM dual;
							END IF;
						END;"); // OK
				}
			}

			// adding columns to B_SALE_LOCATION_GROUP

			if($locationGroupTableExists)
			{
				if (!$DB->query("select CODE from b_sale_location_group WHERE 1=0", true))
				{
					$updater->query(array(
						"MySQL" => "ALTER TABLE b_sale_location_group ADD CODE varchar(100) NOT NULL",
						"MSSQL" => "ALTER TABLE B_SALE_LOCATION_GROUP ADD CODE varchar(100) default '' NOT NULL", // OK
						"Oracle" => "ALTER TABLE B_SALE_LOCATION_GROUP ADD CODE VARCHAR2(100 CHAR) default '' NOT NULL", //OK // oracle allows to add not-null column only with default specified
					));
				}

				// if CODE exists, copy values from ID and add index
				if ($DB->query("select CODE from b_sale_location_group WHERE 1=0", true))
				{
					if (!$DB->IndexExists('b_sale_location_group', array('CODE')))
					{
						$DB->query("update b_sale_location_group set CODE = ID"); // OK: oracle, mssql
						$DB->query("CREATE UNIQUE INDEX IX_B_SALE_LOC_GROUP_CODE ON b_sale_location_group (CODE)"); // OK: oracle, mssql
					}
				}

			}

			if (!$locationNameTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_name (
									ID int not null auto_increment,
									LANGUAGE_ID char(2) not null,
									LOCATION_ID int not null,
									NAME varchar(100) not null,
									NAME_UPPER varchar(100) not null,
									SHORT_NAME varchar(100),

									primary key (ID)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_NAME (
									ID int NOT NULL IDENTITY (1, 1),
									LANGUAGE_ID char(2) NOT NULL,
									LOCATION_ID int NOT NULL,
									NAME varchar(100) NOT NULL,
									NAME_UPPER varchar(100) NOT NULL,
									SHORT_NAME varchar(100)

									CONSTRAINT PK_B_SALE_LOC_NAME PRIMARY KEY (ID)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_NAME(
									ID NUMBER(18) NOT NULL,
									LANGUAGE_ID CHAR(2 CHAR) NOT NULL,
									LOCATION_ID NUMBER(18) NOT NULL,
									NAME VARCHAR2(100 CHAR) NOT NULL,
									NAME_UPPER VARCHAR2(100 CHAR) NOT NULL,
									SHORT_NAME VARCHAR2(100 CHAR),

									PRIMARY KEY (ID)
								)", // OK
				));

				$locationNameTableExists = true;
			}

			if($DBType == 'oracle' && $locationNameTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOC_NAME'", true)->fetch()))
			{
				$DB->query("CREATE SEQUENCE SQ_B_SALE_LOC_NAME INCREMENT BY 1 NOMAXVALUE NOCYCLE NOCACHE NOORDER"); // OK
				$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOC_NAME_INSERT
		BEFORE INSERT
		ON B_SALE_LOC_NAME
		FOR EACH ROW
		BEGIN
			IF :NEW.ID IS NULL THEN
				SELECT SQ_B_SALE_LOC_NAME.NEXTVAL INTO :NEW.ID FROM dual;
			END IF;
		END;"); // OK

			}

			if ($locationNameTableExists)
			{
				if (!$DB->IndexExists('b_sale_loc_name', array('NAME_UPPER')))
				{
					$DB->query("CREATE INDEX IX_B_SALE_LOC_NAME_NAME_U ON b_sale_loc_name (NAME_UPPER)"); // OK: oracle, mssql
				}

				if (!$DB->IndexExists('b_sale_loc_name', array('LOCATION_ID', 'LANGUAGE_ID')))
				{
					$DB->query("CREATE INDEX IX_B_SALE_LOC_NAME_LI_LI ON b_sale_loc_name (LOCATION_ID, LANGUAGE_ID)"); // OK: oracle, mssql
				}
			}

			if (!$locationExternalServiceTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_ext_srv(
									ID int not null auto_increment,
									CODE varchar(100) not null,

									primary key (ID)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_EXT_SRV(
									ID int NOT NULL IDENTITY (1, 1),
									CODE varchar(100) NOT NULL

									CONSTRAINT PK_B_SALE_LOC_EXT_SRV PRIMARY KEY (ID)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_EXT_SRV(
									ID NUMBER(18) NOT NULL,
									CODE VARCHAR2(100 CHAR) NOT NULL,

									PRIMARY KEY (ID)
								)", // OK
				));

				$locationExternalServiceTableExists = true;
			}

			if($DBType == 'oracle' && $locationExternalServiceTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOC_EXT_SRV'", true)->fetch()))
			{
				$DB->query("CREATE SEQUENCE SQ_B_SALE_LOC_EXT_SRV INCREMENT BY 1 NOMAXVALUE NOCYCLE NOCACHE NOORDER"); // OK
				$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOC_EXT_SRV_INSERT
		BEFORE INSERT
		ON B_SALE_LOC_EXT_SRV
		FOR EACH ROW
		BEGIN
			IF :NEW.ID IS NULL THEN
				SELECT SQ_B_SALE_LOC_EXT_SRV.NEXTVAL INTO :NEW.ID FROM dual;
			END IF;
		END;"); // OK

			}

			if (!$locationExternalTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_ext(
									ID int not null auto_increment,
									SERVICE_ID int not null,
									LOCATION_ID int not null,
									XML_ID varchar(100) not null,

									primary key (ID)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_EXT(
									ID int NOT NULL IDENTITY (1, 1),
									SERVICE_ID int NOT NULL,
									LOCATION_ID int NOT NULL,
									XML_ID varchar(100) NOT NULL

									CONSTRAINT PK_B_SALE_LOC_EXT PRIMARY KEY (ID)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_EXT(
									ID NUMBER(18) NOT NULL,
									SERVICE_ID NUMBER(18) NOT NULL,
									LOCATION_ID NUMBER(18) NOT NULL,
									XML_ID VARCHAR2(100 CHAR) NOT NULL,

									PRIMARY KEY (ID)
								)", // OK
				));

				$locationExternalTableExists = true;
			}

			if($DBType == 'oracle' && $locationExternalTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOC_EXT'", true)->fetch()))
			{
				$DB->query("CREATE SEQUENCE SQ_B_SALE_LOC_EXT INCREMENT BY 1 NOMAXVALUE NOCYCLE NOCACHE NOORDER"); // OK
				$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOC_EXT_INSERT
		BEFORE INSERT
		ON B_SALE_LOC_EXT
		FOR EACH ROW
		BEGIN
			IF :NEW.ID IS NULL THEN
				SELECT SQ_B_SALE_LOC_EXT.NEXTVAL INTO :NEW.ID FROM dual;
			END IF;
		END;");// OK
			}

			if ($locationExternalTableExists && !$DB->IndexExists('b_sale_loc_ext', array('LOCATION_ID', 'SERVICE_ID')))
			{
				$DB->query("CREATE INDEX IX_B_SALE_LOC_EXT_LID_SID ON b_sale_loc_ext (LOCATION_ID, SERVICE_ID)"); // OK: oracle, mssql
			}

			if (!$locationTypeTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_type(
									ID int not null auto_increment,
									CODE varchar(30) not null,
									SORT int default '100',

									primary key (ID)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_TYPE(
									ID int NOT NULL IDENTITY (1, 1),
									CODE varchar(30) NOT NULL,
									SORT int

									CONSTRAINT PK_B_SALE_LOC_TYPE PRIMARY KEY (ID)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_TYPE(
									ID NUMBER(18) NOT NULL,
									CODE VARCHAR2(30 CHAR) NOT NULL,
									SORT NUMBER(18) DEFAULT '100',

									PRIMARY KEY (ID)
								)", // OK
				));

				$updater->query(array(
					"MSSQL"  => "ALTER TABLE B_SALE_LOC_TYPE ADD CONSTRAINT DF_B_SALE_LOC_TYPE_SORT DEFAULT '100' FOR SORT", // OK
				));

				$locationTypeTableExists = true;
			}

			if($DBType == 'oracle' && $locationTypeTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOC_TYPE'", true)->fetch()))
			{
				$DB->query("CREATE SEQUENCE SQ_B_SALE_LOC_TYPE INCREMENT BY 1 NOMAXVALUE NOCYCLE NOCACHE NOORDER"); // OK
				$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOC_TYPE_INSERT
		BEFORE INSERT
		ON B_SALE_LOC_TYPE
		FOR EACH ROW
		BEGIN
			IF :NEW.ID IS NULL THEN
				SELECT SQ_B_SALE_LOC_TYPE.NEXTVAL INTO :NEW.ID FROM dual;
			END IF;
		END;"); // OK
			}

			if(!$locationTypeNameTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_type_name(
									ID int not null auto_increment,
									LANGUAGE_ID char(2) not null,
									NAME varchar(100) not null,
									TYPE_ID int not null,

									primary key (ID)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_TYPE_NAME(
									ID int NOT NULL IDENTITY (1, 1),
									LANGUAGE_ID char(2) NOT NULL,
									NAME varchar(100) NOT NULL,
									TYPE_ID int NOT NULL

									CONSTRAINT PK_B_SALE_LOC_TYPE_NAME PRIMARY KEY (ID)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_TYPE_NAME(
									ID NUMBER(18) NOT NULL,
									LANGUAGE_ID CHAR(2 CHAR) NOT NULL,
									NAME VARCHAR2(100 CHAR) NOT NULL,
									TYPE_ID NUMBER(18) NOT NULL,

									PRIMARY KEY (ID)
								)", // OK
				));

				$locationTypeNameTableExists = true;
			}

			if($DBType == 'oracle' && $locationTypeNameTableExists && !($DB->query("select * from USER_OBJECTS where OBJECT_TYPE = 'SEQUENCE' and OBJECT_NAME = 'SQ_B_SALE_LOC_TYPE_NAME'", true)->fetch()))
			{
				$DB->query("CREATE SEQUENCE SQ_B_SALE_LOC_TYPE_NAME INCREMENT BY 1 NOMAXVALUE NOCYCLE NOCACHE NOORDER"); // OK
				$DB->query("CREATE OR REPLACE TRIGGER B_SALE_LOC_TYPE_NAME_INSERT
		BEFORE INSERT
		ON B_SALE_LOC_TYPE_NAME
		FOR EACH ROW
		BEGIN
			IF :NEW.ID IS NULL THEN
				SELECT SQ_B_SALE_LOC_TYPE_NAME.NEXTVAL INTO :NEW.ID FROM dual;
			END IF;
		END;"); // OK
			}

			if ($locationTypeNameTableExists)
			{
				if (!$DB->IndexExists('b_sale_loc_type_name', array('TYPE_ID', 'LANGUAGE_ID')))
				{
					$DB->query('CREATE INDEX IX_B_SALE_LOC_TYPE_NAME_TI_LI ON b_sale_loc_type_name (TYPE_ID, LANGUAGE_ID)'); // OK: oracle, mssql
				}
			}

			if (!$locationLoc2SiteTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_2site(
									LOCATION_ID int not null,
									SITE_ID char(2) not null,
									LOCATION_TYPE char(1) not null default 'L',

									primary key (SITE_ID, LOCATION_ID, LOCATION_TYPE)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_2SITE(
									LOCATION_ID int NOT NULL,
									SITE_ID char(2) NOT NULL,
									LOCATION_TYPE char(1) NOT NULL

									CONSTRAINT PK_B_SALE_LOC_2SITE PRIMARY KEY (SITE_ID, LOCATION_ID, LOCATION_TYPE)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_2SITE(
									LOCATION_ID NUMBER(18) NOT NULL,
									SITE_ID CHAR(2 CHAR) NOT NULL,
									LOCATION_TYPE CHAR(1 CHAR) DEFAULT 'L' NOT NULL,

									PRIMARY KEY (SITE_ID, LOCATION_ID, LOCATION_TYPE)
								)", // OK
				));
				$updater->query(array(
					"MSSQL"  => "ALTER TABLE B_SALE_LOC_2SITE ADD CONSTRAINT DF_B_SALE_LOC_2SITE DEFAULT 'L' FOR LOCATION_TYPE", // OK
				));
			}

			if (!$locationDefaul2SiteTableExists)
			{
				$updater->query(array(
					"MySQL"  => "create table b_sale_loc_def2site(
									LOCATION_CODE varchar(100) not null,
									SITE_ID char(2) not null,
									SORT int default '100',

									primary key (LOCATION_CODE, SITE_ID)
								)",

					"MSSQL"  => "CREATE TABLE B_SALE_LOC_DEF2SITE(
									LOCATION_CODE varchar(100) NOT NULL,
									SITE_ID char(2) NOT NULL,
									SORT int

									CONSTRAINT PK_B_SALE_LOC_DEF2SITE PRIMARY KEY (LOCATION_CODE, SITE_ID)
								)", // OK

					"Oracle"  => "CREATE TABLE B_SALE_LOC_DEF2SITE(
									LOCATION_CODE VARCHAR2(100 CHAR) NOT NULL,
									SITE_ID CHAR(2 CHAR) NOT NULL,
									SORT NUMBER(18) DEFAULT '100',

									PRIMARY KEY (LOCATION_CODE, SITE_ID)
								)", // OK
				));
				$updater->query(array(
					"MSSQL"  => "ALTER TABLE B_SALE_LOC_DEF2SITE ADD CONSTRAINT DF_B_SALE_LOC_DEF2SITE_SORT DEFAULT '100' FOR SORT",
				));
			}

			// move tax and delivery to the new relation field: code

			if ($tax2LocationTableExists && $DB->query("select LOCATION_ID from b_sale_tax2location WHERE 1=0", true)) // OK: oracle, mssql
			{
				$DB->query('delete from b_sale_tax2location where LOCATION_ID is null'); // OK: oracle, mssql // useless records to be deleted

				if (!$DB->query("select LOCATION_CODE from b_sale_tax2location WHERE 1=0", true))
				{
					$updater->query(array(
						"MySQL" => "ALTER TABLE b_sale_tax2location ADD LOCATION_CODE varchar(100) NOT NULL",
						"MSSQL" => "ALTER TABLE B_SALE_TAX2LOCATION ADD LOCATION_CODE varchar(100) default '' NOT NULL",
						"Oracle" => "ALTER TABLE B_SALE_TAX2LOCATION ADD LOCATION_CODE VARCHAR2(100 CHAR) default '' NOT NULL", // OK // oracle allows to add not-null column only with default specified
					));
				}

				$DB->query('update b_sale_tax2location set LOCATION_CODE = LOCATION_ID'); // OK: oracle, mssql

				if($DBType == 'mssql')
					$DB->query('ALTER TABLE b_sale_tax2location DROP CONSTRAINT PK_B_SALE_TAX2LOCATION'); // OK
				else
					$DB->query('ALTER TABLE b_sale_tax2location DROP PRIMARY KEY'); // OK: oracle

				$DB->query('ALTER TABLE b_sale_tax2location DROP COLUMN LOCATION_ID'); // OK: oracle, mssql

				$DB->query('ALTER TABLE b_sale_tax2location ADD CONSTRAINT PK_B_SALE_TAX2LOCATION PRIMARY KEY (TAX_RATE_ID, LOCATION_CODE, LOCATION_TYPE)'); // OK: oracle, mssql
			}

			if ($delivery2LocationTableExists && $DB->query("select LOCATION_ID from b_sale_delivery2location WHERE 1=0", true)) // OK: oracle
			{
				$DB->query('delete from b_sale_delivery2location where LOCATION_ID is null'); // OK: oracle, mssql // useless records to be deleted

				if (!$DB->query("select LOCATION_CODE from b_sale_delivery2location WHERE 1=0", true))
				{
					$updater->query(array(
						"MySQL" => "ALTER TABLE b_sale_delivery2location ADD LOCATION_CODE varchar(100) NOT NULL",
						"MSSQL" => "ALTER TABLE B_SALE_DELIVERY2LOCATION ADD LOCATION_CODE varchar(100) default '' NOT NULL", // OK
						"Oracle" => "ALTER TABLE B_SALE_DELIVERY2LOCATION ADD LOCATION_CODE VARCHAR2(100 CHAR) default '' NOT NULL", // OK // oracle allows to add not-null column only with default specified
					));
				}

				$DB->query('update b_sale_delivery2location set LOCATION_CODE = LOCATION_ID'); // OK: oracle, mssql

				if($DBType == 'mssql')
					$DB->query('ALTER TABLE b_sale_delivery2location DROP CONSTRAINT PK_B_SALE_DELIVERY2LOCATION'); // OK
				else
					$DB->query('ALTER TABLE b_sale_delivery2location DROP PRIMARY KEY'); // OK: oracle

				$DB->query('ALTER TABLE b_sale_delivery2location DROP COLUMN LOCATION_ID'); // OK: oracle, mssql

				$DB->query('ALTER TABLE b_sale_delivery2location ADD CONSTRAINT PK_B_SALE_DELIVERY2LOCATION PRIMARY KEY (DELIVERY_ID, LOCATION_CODE, LOCATION_TYPE)'); // OK: oracle, mssql
			}

			if(\COption::GetOptionString('sale', 'sale_locationpro_migrated', '') != 'Y') // CSaleLocation::isLocationProMigrated()
			{
				\CAdminNotify::Add(
					array(
						"MESSAGE" => Loc::getMessage('SALE_LOCATION_MIGRATION_PLZ_MIGRATE_NOTIFIER', array(
							'#ANCHOR_MIGRATE#' => '<a href="/bitrix/admin/sale_location_migration.php">',
							'#ANCHOR_END#' => '</a>'
						)),
						"TAG" => "SALE_LOCATIONPRO_PLZ_MIGRATE",
						"MODULE_ID" => "SALE",
						"ENABLE_CLOSE" => "Y"
					)
				);
			}
		}
	}

	////////////////////////////////////////////////////////
	//// Migration-specific
	////////////////////////////////////////////////////////

	public function copyId2Code()
	{
		// in locations
		$this->Query(array(
			self::DB_TYPE_MYSQL => 'update '.self::TABLE_LOCATION.' set CODE = ID;',
			self::DB_TYPE_MSSQL => 'update '.strtoupper(self::TABLE_LOCATION).' set CODE = ID;',
			self::DB_TYPE_ORACLE => 'update '.strtoupper(self::TABLE_LOCATION).' set CODE = ID;'
		));

		// in groups
		$this->Query(array(
			self::DB_TYPE_MYSQL => 'update '.self::TABLE_LOCATION_GROUP.' set CODE = ID;',
			self::DB_TYPE_MSSQL => 'update '.strtoupper(self::TABLE_LOCATION_GROUP).' set CODE = ID;',
			self::DB_TYPE_ORACLE => 'update '.strtoupper(self::TABLE_LOCATION_GROUP).' set CODE = ID;'
		));
	}

	public function copyZipCodes()
	{
		global $DB;

		Helper::truncateTable(self::TABLE_LOCATION_EXTERNAL);

		$zipServiceId = false;
		$zip = Location\ExternalServiceTable::getList(array('filter' => array('=CODE' => 'ZIP')))->fetch();
		if(intval($zip['ID']))
			$zipServiceId = intval($zip['ID']);

		if($zipServiceId === false)
		{
			$res = Location\ExternalServiceTable::add(array('CODE' => 'ZIP'));
			if(!$res->isSuccess())
				throw new Main\SystemException('Cannot add external system: '.implode(', ', $res->getErrors()), 0, __FILE__, __LINE__);

			$zipServiceId = $res->getId();
		}

		if($this->TableExists(self::TABLE_LOCATION_ZIP))
		{
			$loc2External = new BlockInserter(array(
				'entityName' => '\Bitrix\Sale\Location\ExternalTable',
				'exactFields' => array('LOCATION_ID', 'XML_ID', 'SERVICE_ID'),
				'parameters' => array(
					//'autoIncrementFld' => 'ID',
					'mtu' => 9999
				)
			));

			$res = $DB->query('select * from '.self::TABLE_LOCATION_ZIP);
			while($item = $res->fetch())
			{
				$item['LOCATION_ID'] = trim($item['LOCATION_ID']);
				$item['ZIP'] = trim($item['ZIP']);

				if(strlen($item['LOCATION_ID']) && strlen($item['ZIP']))
				{
					$loc2External->insert(array(
						'LOCATION_ID' => $item['LOCATION_ID'],
						'XML_ID' => $item['ZIP'],
						'SERVICE_ID' => $zipServiceId
					));
				}
			}
			$loc2External->flush();
		}
	}

	private function convertEntityLocationLinks($entityName)
	{
		$class = 				$entityName.'Table';
		$typeField = 			$class::getTypeField();
		$locationLinkField = 	$class::getLocationLinkField();
		$linkField = 			$class::getLinkField();
		$useGroups = 			$class::getUseGroups();

		$res = $class::getList();
		$links = array();

		while($item = $res->fetch())
		{
			if($useGroups)
				$links[$item[$linkField]][$item[$typeField]][] = $item[$locationLinkField];
			else
				$links[$item[$linkField]][$class::DB_LOCATION_FLAG][] = $item[$locationLinkField];
		}

		foreach($links as $entityId => $rels)
		{
			$rels[$class::DB_LOCATION_FLAG] = $class::normalizeLocationList($rels[$class::DB_LOCATION_FLAG]);
			$class::resetMultipleForOwner($entityId, $rels);
		}
	}

	public function convertGroupLocationLinks()
	{
		$this->convertEntityLocationLinks('\Bitrix\Sale\Location\GroupLocation');
	}

	public function convertDeliveryLocationLinks()
	{
		$this->convertEntityLocationLinks('\Bitrix\Sale\Delivery\DeliveryLocation');
	}

	public function convertTaxRateLocationLinks()
	{
		$this->convertEntityLocationLinks('\Bitrix\Sale\Tax\RateLocation');
	}

	static public function convertSalesZones()
	{
		$siteList = \CSaleLocation::getSites();
		$siteList[] = ''; // 'empty site' too

		foreach($siteList as $siteId)
		{
			$countries = Sale\SalesZone::getCountriesIds($siteId);
			$regions = Sale\SalesZone::getRegionsIds($siteId);
			$cities = Sale\SalesZone::getCitiesIds($siteId);

			if(empty($countries) && empty($regions) && empty($cities))
				continue;

			Sale\SalesZone::saveSelectedTypes(array(
				'COUNTRY' => $countries,
				'REGION' => $regions,
				'CITY' => $cities
			), $siteId);
		}
	}

	static public function copyDefaultLocations()
	{
		$sRes = Main\SiteTable::getList();
		$sites = array();
		while($site = $sRes->fetch())
			$sites[] = $site['LID'];

		$existed = array();
		$res = Location\DefaultSiteTable::getList();
		while($item = $res->fetch())
			$existed[$item['SITE_ID']][$item['LOCATION_CODE']] = true;

		$res = \CSaleLocation::GetList(array(), array(
			'LID' => 'en',
			'LOC_DEFAULT' => 'Y'
		), false, false, array('ID'));

		while($item = $res->fetch())
		{
			foreach($sites as $site)
			{
				if(isset($existed[$site][$item['ID']]))
					continue;

				$opRes = Location\DefaultSiteTable::add(array(
					'SITE_ID' => $site,
					'LOCATION_CODE' => $item['ID']
				));
				if(!$opRes->isSuccess())
					throw new Main\SystemException('Cannot add default location');
			}
		}
	}

	public static function createBaseTypes()
	{
		$types = array(
			'COUNTRY' => array(
				'CODE' => 'COUNTRY',
				'SORT' => 100,
				'DISPLAY_SORT' => 700,
				'NAME' => array()
			),
			'REGION' => array(
				'CODE' => 'REGION',
				'SORT' => 300,
				'DISPLAY_SORT' => 500,
				'NAME' => array()
			),
			'CITY' => array(
				'CODE' => 'CITY',
				'SORT' => 600,
				'DISPLAY_SORT' => 100,
				'NAME' => array()
			),
		);

		$langs = array();
		$res = \Bitrix\Main\Localization\LanguageTable::getList();
		while($item = $res->Fetch())
		{
			$MESS = array();
			@include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/lang/'.$item['LID'].'/lib/location/migration/migrate.php');

			if(!empty($MESS))
			{
				$types['COUNTRY']['NAME'][$item['LID']]['NAME'] = $MESS['SALE_LOCATION_TYPE_COUNTRY'];
				$types['REGION']['NAME'][$item['LID']]['NAME'] = $MESS['SALE_LOCATION_TYPE_REGION'];
				$types['CITY']['NAME'][$item['LID']]['NAME'] = $MESS['SALE_LOCATION_TYPE_CITY'];
			}

			$langs[$item['LID']] = true;
		}

		$typeCode2Id = array();
		$res = Location\TypeTable::getList(array('select' => array('ID', 'CODE')));
		while($item = $res->Fetch())
			$typeCode2Id[$item['CODE']] = $item['ID'];

		foreach($types as $code => &$type)
		{
			foreach($langs as $lid => $f)
			{
				$type['NAME'][$lid] = \Bitrix\Sale\Location\Admin\NameHelper::getTranslatedName($type['NAME'], $lid);
			}

			if(!isset($typeCode2Id[$type['CODE']]))
			{
				$typeCode2Id[$type['CODE']] = Location\TypeTable::add($type);
			}
			else
			{
				// ensure it has all appropriate translations
				// we can not use ::updateMultipleForOwner() here, because user may rename types manually
				Location\Name\TypeTable::addAbsentForOwner($typeCode2Id[$type['CODE']], $type['NAME']);
			}
		}

		return $typeCode2Id;
	}

	public function createTypes()
	{
		$this->data['TYPE'] = self::createBaseTypes();
	}

	public function convertTree()
	{
		$res = Location\Name\LocationTable::getList(array('select' => array('ID'), 'limit' => 1))->fetch();
		if(!$res['ID']) // if we got smth in name table - this means we already have done this conversion in the past
		{
			$this->grabTree();
			$this->convertCountries();
			$this->convertRegions();
			$this->convertCities();

			$this->resort();

			$this->insertTreeInfo();
			$this->insertNames();
		}
	}

	static public function resetLegacyPath()
	{
		Helper::dropTable(self::TABLE_LEGACY_RELATIONS);

		$dbConnection = \Bitrix\Main\HttpApplication::getConnection();
		$dbConnection->query("create table ".self::TABLE_LEGACY_RELATIONS." (
			ID ".Helper::getSqlForDataType('int').",
			COUNTRY_ID ".Helper::getSqlForDataType('int').",
			REGION_ID ".Helper::getSqlForDataType('int').",
			CITY_ID ".Helper::getSqlForDataType('int')."
		)");

		$dbConnection->query("insert into ".self::TABLE_LEGACY_RELATIONS." (ID, COUNTRY_ID, REGION_ID, CITY_ID) select ID, COUNTRY_ID, REGION_ID, CITY_ID from b_sale_location");

		Location\LocationTable::resetLegacyPath();
	}

	static public function rollBack()
	{
		if(Helper::checkTableExists(self::TABLE_LEGACY_RELATIONS))
		{
			Helper::mergeTables(
				'b_sale_location', 
				self::TABLE_LEGACY_RELATIONS,
				array(
					'COUNTRY_ID' => 'COUNTRY_ID',
					'REGION_ID' => 'REGION_ID',
					'CITY_ID' => 'CITY_ID',
				),
				array('ID' => 'ID')
			);
		}

		Helper::truncateTable(self::TABLE_LOCATION_NAME);
		Helper::truncateTable(self::TABLE_LOCATION_EXTERNAL);

		\CSaleLocation::locationProSetRolledBack();
	}

	// in this function we track dependences between countries, regions and cities
	private function grabTree()
	{
		$this->data['LOC'] = array();

		$auxIndex = array(
			'COUNTRY' => array(),
			'REGION' => array(),
			'CITY' => array()
		);

		$this->data['LOC'] = array(
			'COUNTRY' => array(),
			'REGION' => array(),
			'CITY' => array()
		);

		// level 1: country
		$res = \CSaleLocation::GetList(array(), array(
			'!COUNTRY_ID' => false,
			'REGION_ID' => false,
			'CITY_ID' => false,
			'LID' => 'en'
		));

		while($item = $res->Fetch())
		{
			if(!isset($this->data['LOC']['COUNTRY'][$item['ID']]))
			{
				$this->data['LOC']['COUNTRY'][$item['ID']] = array(
					'SUBJ_ID' => $item['COUNTRY_ID'],
					'PARENT_ID' => false,
					'PARENT_TYPE' => false
				);
				$auxIndex['COUNTRY'][$item['COUNTRY_ID']] = $item['ID'];
			}
		}

		// level 2: country - region
		$res = \CSaleLocation::GetList(array(), array(
			//'!COUNTRY_ID' => false,
			'!REGION_ID' => false,
			'CITY_ID' => false,
			'LID' => 'en'
		));

		while($item = $res->Fetch())
		{
			if(!isset($this->data['LOC']['REGION'][$item['ID']]))
			{
				$this->data['LOC']['REGION'][$item['ID']] = array(
					'SUBJ_ID' => $item['REGION_ID'],
					'PARENT_ID' => $auxIndex['COUNTRY'][$item['COUNTRY_ID']],
					'PARENT_TYPE' => 'COUNTRY'
				);
				$auxIndex['REGION'][$item['REGION_ID']] = $item['ID'];
			}
		}

		// level 2: country - city
		$res = \CSaleLocation::GetList(array(), array(
			//'!COUNTRY_ID' => false,
			'REGION_ID' => false,
			'!CITY_ID' => false,
			'LID' => 'en'
		));

		while($item = $res->Fetch())
		{
			if(!isset($this->data['LOC']['CITY'][$item['ID']]))
				$this->data['LOC']['CITY'][$item['ID']] = array(
					'SUBJ_ID' => $item['CITY_ID'],
					'PARENT_ID' => $auxIndex['COUNTRY'][$item['COUNTRY_ID']],
					'PARENT_TYPE' => 'COUNTRY'
				);
		}

		// level 3: country - region - city
		$res = \CSaleLocation::GetList(array(), array(
			//'!COUNTRY_ID' => false,
			'!REGION_ID' => false,
			'!CITY_ID' => false,
			'LID' => 'en'
		));

		while($item = $res->Fetch())
		{
			if(!isset($this->data['LOC']['CITY'][$item['ID']]))
				$this->data['LOC']['CITY'][$item['ID']] = array(
					'SUBJ_ID' => $item['CITY_ID'],
					'PARENT_ID' => $auxIndex['REGION'][$item['REGION_ID']],
					'PARENT_TYPE' => 'REGION'
				);
		}

		// language list
		$a = false;
		$b = false;
		$lang = new \CLanguage();
		$res = $lang->GetList($a, $b);
		$this->data['LANG'] = array();
		while($item = $res->Fetch())
			$this->data['LANG'][] = $item['LID'];

		// type list
		$res = Location\TypeTable::getList();
		while($item = $res->Fetch())
			$this->data['TYPE'][$item['CODE']] = $item['ID'];
	}

	private function convertCountries()
	{
		global $DB;

		// fetch name referece, separated with lang
		$langIndex = array();
		$res = $DB->query('select * from '.self::TABLE_LOCATION_COUNTRY_NAME);
		while($item = $res->Fetch())
		{
			$langIndex[$item['COUNTRY_ID']][$item['LID']] = array(
				'NAME' => $item['NAME'],
				'SHORT_NAME' => $item['SHORT_NAME']
			);
		}

		if(is_array($this->data['LOC']['COUNTRY']))
		{
			foreach($this->data['LOC']['COUNTRY'] as $id => &$item)
			{
				$this->data['NAME'][$id] = $langIndex[$item['SUBJ_ID']];
				$this->data['TREE'][$id] = array(
					'PARENT_ID' => false,
					'TYPE_ID' => $this->data['TYPE']['COUNTRY'],
					'DEPTH_LEVEL' => 1
				);
			}
		}
		unset($this->data['LOC']['COUNTRY']);
	}

	private function convertRegions()
	{
		global $DB;

		// fetch name referece, separated with lang
		$langIndex = array();
		$res = $DB->query('select * from '.self::TABLE_LOCATION_REGION_NAME);
		while($item = $res->Fetch())
		{
			$langIndex[$item['REGION_ID']][$item['LID']] = array(
				'NAME' => $item['NAME'],
				'SHORT_NAME' => $item['SHORT_NAME']
			);
		}

		if(is_array($this->data['LOC']['REGION']))
		{
			foreach($this->data['LOC']['REGION'] as $id => &$item)
			{
				$this->data['NAME'][$id] = $langIndex[$item['SUBJ_ID']];
				$this->data['TREE'][$id] = array(
					'PARENT_ID' => $item['PARENT_ID'],
					'TYPE_ID' => $this->data['TYPE']['REGION'],
					'DEPTH_LEVEL' => 2
				);
			}
		}
		unset($this->data['LOC']['REGION']);
	}

	private function convertCities()
	{
		global $DB;

		// fetch name referece, separated with lang
		$langIndex = array();
		$res = $DB->query('select * from '.self::TABLE_LOCATION_CITY_NAME);
		while($item = $res->Fetch())
		{
			$langIndex[$item['CITY_ID']][$item['LID']] = array(
				'NAME' => $item['NAME'],
				'SHORT_NAME' => $item['SHORT_NAME']
			);
		}

		if(is_array($this->data['LOC']['CITY']))
		{
			foreach($this->data['LOC']['CITY'] as $id => &$item)
			{
				$this->data['NAME'][$id] = $langIndex[$item['SUBJ_ID']];
				$this->data['TREE'][$id] = array(
					'PARENT_ID' => $item['PARENT_ID'],
					'TYPE_ID' => $this->data['TYPE']['CITY'],
					'DEPTH_LEVEL' => $item['PARENT_TYPE'] == 'REGION' ? 3 : 2
				);
			}
		}
		unset($this->data['LOC']['CITY']);
	}

	private function resort()
	{
		$edges = array();
		$nodes = array();

		if(is_array($this->data['TREE']))
		{
			foreach($this->data['TREE'] as $id => $item)
			{
				$nodes[$id] = array();

				if(!intval($item['PARENT_ID']))
					$edges['ROOT'][] = $id;
				else
					$edges[$item['PARENT_ID']][] = $id;
			}
		}

		$this->walkTreeInDeep('ROOT', $edges, $nodes, 0);
	}

	private function walkTreeInDeep($nodeId, $edges, &$nodes, $margin, $depth = 0)
	{
		$lMargin = $margin;

		if(empty($edges[$nodeId]))
			$rMargin = $margin + 1;
		else
		{
			$offset = $margin + 1;
			foreach($edges[$nodeId] as $sNode)
				$offset = $this->walkTreeInDeep($sNode, $edges, $nodes, $offset, $depth + 1);

			$rMargin = $offset;
		}

		if($nodeId != 'ROOT')
		{
			// store margins
			$this->data['TREE'][$nodeId]['LEFT_MARGIN'] = $lMargin;
			$this->data['TREE'][$nodeId]['RIGHT_MARGIN'] = $rMargin;
			$this->data['TREE'][$nodeId]['DEPTH_LEVEL'] = $depth;
		}

		return $rMargin + 1;
	}

	private function insertTreeInfo()
	{
		// We make temporal table, place margins, parent and lang data into it, then perform an update of the old table from the temporal one.

		$this->createTemporalTable(
			self::TABLE_TEMP_TREE,
			array(
				'ID' => array(
					'TYPE' => array(
						self::DB_TYPE_MYSQL => 'int',
						self::DB_TYPE_MSSQL => 'int',
						self::DB_TYPE_ORACLE => 'NUMBER(18)',
					)
				),
				'PARENT_ID' => array(
					'TYPE' => array(
						self::DB_TYPE_MYSQL => 'int',
						self::DB_TYPE_MSSQL => 'int',
						self::DB_TYPE_ORACLE => 'NUMBER(18)',
					)
				),
				'TYPE_ID' => array(
					'TYPE' => array(
						self::DB_TYPE_MYSQL => 'int',
						self::DB_TYPE_MSSQL => 'int',
						self::DB_TYPE_ORACLE => 'NUMBER(18)',
					)
				),
				'DEPTH_LEVEL' => array(
					'TYPE' => array(
						self::DB_TYPE_MYSQL => 'int',
						self::DB_TYPE_MSSQL => 'int',
						self::DB_TYPE_ORACLE => 'NUMBER(18)',
					)
				),
				'LEFT_MARGIN' => array(
					'TYPE' => array(
						self::DB_TYPE_MYSQL => 'int',
						self::DB_TYPE_MSSQL => 'int',
						self::DB_TYPE_ORACLE => 'NUMBER(18)',
					)
				),
				'RIGHT_MARGIN' => array(
					'TYPE' => array(
						self::DB_TYPE_MYSQL => 'int',
						self::DB_TYPE_MSSQL => 'int',
						self::DB_TYPE_ORACLE => 'NUMBER(18)',
					)
				)
			)
		);

		$handle = new BlockInserter(array(
			'tableName' => self::TABLE_TEMP_TREE,
			'exactFields' => array(
				'ID' => array('data_type' => 'integer'),
				'PARENT_ID' => array('data_type' => 'integer'),
				'TYPE_ID' => array('data_type' => 'integer'),
				'DEPTH_LEVEL' => array('data_type' => 'integer'),
				'LEFT_MARGIN' => array('data_type' => 'integer'),
				'RIGHT_MARGIN' => array('data_type' => 'integer'),
			),
			'parameters' => array(
				'mtu' => 9999
			)
		));

		// fill temporal table
		if(is_array($this->data['TREE']))
		{
			foreach($this->data['TREE'] as $id => $node)
			{
				$handle->insert(array(
					'ID' => $id,
					'PARENT_ID' => $node['PARENT_ID'],
					'TYPE_ID' => $node['TYPE_ID'],
					'DEPTH_LEVEL' => $node['DEPTH_LEVEL'],
					'LEFT_MARGIN' => $node['LEFT_MARGIN'],
					'RIGHT_MARGIN' => $node['RIGHT_MARGIN'],
				));
			}
		}

		$handle->flush();

		// merge temp table with location table
		Location\LocationTable::mergeRelationsFromTemporalTable(self::TABLE_TEMP_TREE, array('TYPE_ID', 'PARENT_ID'));

		$this->dropTable(self::TABLE_TEMP_TREE);
	}

	private function insertNames()
	{
		$handle = new BlockInserter(array(
			'entityName' => '\Bitrix\Sale\Location\Name\LocationTable',
			'exactFields' => array('LOCATION_ID', 'LANGUAGE_ID', 'NAME', 'SHORT_NAME', 'NAME_UPPER'),
			'parameters' => array(
				//'autoIncrementFld' => 'ID',
				'mtu' => 9999
			)
		));

		if(is_array($this->data['NAME']) && !empty($this->data['NAME']))
		{
			foreach($this->data['NAME'] as $id => $nameLang)
			{
				if(is_array($nameLang))
				{
					foreach($nameLang as $lang => $name)
					{
						$handle->insert(array(
							'LOCATION_ID' => $id,
							'LANGUAGE_ID' => $lang,
							'NAME' => $name['NAME'],
							'NAME_UPPER' => ToUpper($name['NAME']),
							'SHORT_NAME' => $name['SHORT_NAME']
						));
					}
				}
			}
		}

		$handle->flush();
	}

	////////////////////////////////////////////////////////
	//// Common-specific logic => add to CUpdater ???
	////////////////////////////////////////////////////////

	protected function dropTable($tableName = '')
	{
		if(!strlen($tableName))
			return false;

		global $DB;

		if($this->TableExists($tableName))
			$DB->query('drop table '.$DB->ForSql($tableName));

		return true;
	}

	protected function createTemporalTable($tableName = '', $columns = array())
	{
		if(!strlen($tableName))
			return false;

		if($this->dropTable($tableName));

		return $this->createTable($tableName, $columns);
	}

	protected function createTable($tableName = '', $columns = array(), $constraints = array())
	{
		if(!strlen($tableName) || !is_array($columns) || empty($columns) || $this->TableExists($tableName))
			return false;

		global $DB;

		$tableName = $DB->ForSql($tableName);
		$tableNameUC = strtoupper($tableName);

		// queries that should be called after table creation
		$afterTableCreate = array();

		// column sqls separated by dbtype
		$columnsSql = array();
		foreach($columns as $colName => $colProps)
			if($col = self::prepareFieldSql($colProps, $afterTableCreate))
				$columnsSql[$colName] = $col;

		// constraint sqls separated by dbtype
		$constSql = self::prepareConstraintSql($constraints);

		$queries = array();

		if($sql = self::prepareCreateTable($tableName, $columnsSql, $constSql, self::DB_TYPE_MYSQL))
			$queries[self::DB_TYPE_MYSQL] = $sql;

		if($sql = self::prepareCreateTable($tableNameUC, $columnsSql, $constSql, self::DB_TYPE_MSSQL))
			$queries[self::DB_TYPE_MSSQL] = $sql;

		if($sql = self::prepareCreateTable($tableNameUC, $columnsSql, $constSql, self::DB_TYPE_ORACLE))
			$queries[self::DB_TYPE_ORACLE] = $sql;

		if(!empty($queries))
			$this->Query($queries);

		foreach($afterTableCreate as $dbType => $queries)
		{
			foreach($queries as $query)
			{
				$this->Query(array(
					$dbType => str_replace('%TABLE_NAME%', self::DB_TYPE_MYSQL == $dbType ? $tableName : $tableNameUC, $query)
				));
			}
		}

		return true;
	}

	protected function prepareCreateTable($tableName, $columnsSql, $constSql, $dbType)
	{
		$columnsSqlSpec = $this->prepareTableFields($columnsSql, $dbType);
		if(!empty($columnsSqlSpec))
			return 'create table '.$tableName.' ('.$columnsSqlSpec.(!empty($constSql[$dbType]) ? ', '.implode(', ', $constSql[$dbType]) : '').')';

		return false;
	}

	// might be some overhead
	protected function prepareConstraintSql($constraints)
	{
		global $DB;

		$cSql = array();
		foreach($constraints as $cCode => $cVal)
		{
			if($cCode == 'PRIMARY')
			{
				if(is_array($cVal) || !empty($cVal))
				{
					foreach($cVal as &$fld)
						$fld = $DB->ForSql($fld);

					$key = implode(', ', $cVal);
				}
				else
					$key = $DB->ForSql($cVal);

				$pk = 'PRIMARY KEY ('.$key.')';

				$cSql[self::DB_TYPE_MYSQL][] = $pk;
				$cSql[self::DB_TYPE_MSSQL][] = $pk;
				$cSql[self::DB_TYPE_ORACLE][] = $pk;
			}
		}

		return $cSql;
	}

	protected function prepareTableFields($columnsSql, $dbType)
	{
		$resSql = array();
		foreach($columnsSql as $colName => $sqls)
			if(isset($sqls[$dbType]))
				$resSql[] = $colName.' '.$sqls[$dbType];

		return implode(', ', $resSql);
	}

	protected function prepareFieldSql($field, &$afterCreate)
	{
		$prepared = array();

		global $DB;

		foreach($field['TYPE'] as $dbType => $fldType)
		{
			$prepared[$dbType] = $fldType;

			if($field['PRIMARY'])
				$prepared[$dbType] .= ' primary key';

			if($field['AUTO_INCREMENT'])
			{
				if($dbType == self::DB_TYPE_MYSQL)
					$prepared[$dbType] .= ' auto_increment';
				if($dbType == self::DB_TYPE_MSSQL)
					$prepared[$dbType] .= ' IDENTITY (1, 1)';
				if($dbType == self::DB_TYPE_ORACLE)
				{
					// create a sequence
					$afterCreate[self::DB_TYPE_ORACLE][] = 'CREATE SEQUENCE SQ_B_%TABLE_NAME%';

					// then create a trigger that uses the sequence
					$afterCreate[self::DB_TYPE_ORACLE][] = 'CREATE OR REPLACE TRIGGER %TABLE_NAME%_insert
						BEFORE INSERT
						ON %TABLE_NAME%
						FOR EACH ROW
						BEGIN
							IF :NEW.ID IS NULL THEN
								SELECT SQ_%TABLE_NAME%.NEXTVAL INTO :NEW.ID FROM dual;
							END IF;
						END;';
				}
			}

			if(isset($field['DEFAULT']))
				$prepared[$dbType] .= ' DEFAULT '.(empty($field['DEFAULT']) ? 'NULL' : "'".$DB->ForSql($field['DEFAULT'])."'");

			if(isset($field['NULL']))
				$prepared[$dbType] .= ' '.($field['NULL'] ? '' : 'NOT ').'NULL';

		}

		return $prepared;
	}

	public function TableExists($tableName)
	{
		if (!in_array("DATABASE", $this->callType))
			return False;

		$tableName = preg_replace("/[^A-Za-z0-9%_]+/i", "", $tableName);
		$tableName = Trim($tableName);

		if (strlen($tableName) <= 0)
			return False;

		global $DB;

		if($this->UsingMysql())
		{
			return $DB->query('select * from '.$DB->ForSql($tableName).' where 1=0', true);
		}
		else
		{
			$strSql = '';
			if($this->UsingOracle())
				$strSql = "SELECT TABLE_NAME FROM USER_TABLES WHERE TABLE_NAME LIKE UPPER('".strtoupper($DB->ForSql($tableName))."')";
			elseif($this->UsingMssql())
				$strSql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '".strtoupper($DB->ForSql($tableName))."'";

			return !!$DB->Query($strSql)->fetch();
		}
	}

	protected function UsingMysql()
	{
		return $this->dbType == 'MYSQL';
	}
	protected function UsingMssql()
	{
		return $this->dbType == 'MSSQL';
	}
	protected function UsingOracle()
	{
		return $this->dbType == 'ORACLE';
	}
}