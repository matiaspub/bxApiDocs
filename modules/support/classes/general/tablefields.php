<?php
class CSupportTableFields
{
	const VT_NUMBER		= "number";					
	const VT_STRING		= "string";
	const VT_Y_N		= "Y_N";
	const VT_Y_N_NULL	= "Y_N_NULL";
	const VT_DATE		= "date";
	const VT_DATE_TIME	= "datetime";

	
	const JS_HREF		= 1;
	const JS_HREF_ALERT	= 2;
	const JS_IN_QUOTES	= 3;
	const JS_EVENT		= 4;
	const ATTRIBUTE		= 5;
	const ATTRIBUTE_EX	= 6;
	const HREF_LOCATION	= 7;
	const ID			= 8;
		
	const NOT_NULL		= 1;
	const NOT_DEFAULT	= 2;
	const ONLY_CHANGED	= 3;
	const MORE0			= 4;
	const NOT_EMTY_STR	= 5;
	
	const ALL			= null;
	
	const C_Array		= 1;
	const C_Table		= 2;
	
	protected $_arFieldsTypes = array(); /*
		array(
			"aaa" => array(
				"TYPE" => self::VT_STRING, 
				"DEF_VAL" => null, 
				"MAX_STR_LEN" => 255, 
				"AUTO_CALCULATED" => true, 
				"LIST" => array("q1","q2") 
			) 
		)*/
	protected $_arFields = array(); //array(0 => array("aaa" => null))
	protected $_arModifiedFields = array(); //array(0 => array("aaa", "bbb"))
	protected $_classType = self::C_Array;
	protected $_currentRow = 0;
	protected $_resetNextF = false;
	protected $_sortFields = array();
	
	static function Convert($type, $value, $op)
	{
		$maxStrLen = (array_key_exists("MAX_STR_LEN", $op) ? $op["MAX_STR_LEN"] : 0);
		$list = (array_key_exists("LIST", $op) ? $op["LIST"] : null);
		$defVal = (array_key_exists("DEF_VAL", $op) ? $op["DEF_VAL"] : null);
		$res = null;
		switch($type)
		{
			case self::VT_NUMBER:		
				$res = (intval($value) == floatval($value)) ? intval($value) : floatval($value);
				break;
			case self::VT_STRING:
				$res = ($maxStrLen == 0 ? $value : substr($value, 0, $maxStrLen)) ;
				break;
			case self::VT_Y_N_NULL:
				$res = ($value == "Y") ? "Y" : ($value === null ? null : "N");
				break;
			case self::VT_Y_N:
				$res = ($value == "Y") ? "Y" : "N";
				break;
			case self::VT_DATE:
				$res = (is_int($value) || $value===null) ? $value : MakeTimeStamp($value , FORMAT_DATE);
				$res = (is_int($res) || $res===null) ? $res : null;
				break;
			case self::VT_DATE_TIME:
				$res = (is_int($value) || $value===null) ? $value : MakeTimeStamp($value , FORMAT_DATETIME);
				$res = (is_int($res) || $res===null) ? $res : null;
				break;
		}
		if($list != null) $res = (in_array($res, $list) ? $res : $defVal);
				
		return $res;
	}
	
	static function ConvertForSQL($type, $value)
	{
		global $DB;
		$sf = "FULL";
		if($value === null) return "null";
		if($value === 0) return "0";
		switch($type)
		{
			case self::VT_NUMBER:		return (is_float($value)) ? floatval($value) : intval($value);
			
			case self::VT_Y_N:
			case self::VT_Y_N_NULL:
			case self::VT_STRING:		return "'" . $DB->ForSql($value) . "'";
			
			case self::VT_DATE:			$sf = "SHORT";
			case self::VT_DATE_TIME:	return (is_int($value) ? $DB->CharToDateFunction(GetTime($value, $sf)) : $value);
		}
	}
	
	static function ConvertForHTML($type, $place, $value, $op)
	{
		switch($type)
		{
			case self::VT_DATE:			return	GetTime($value, "SHORT");
			case self::VT_DATE_TIME:	return	GetTime($value, "FULL");
			case self::VT_STRING:		break;
			default:					return	$value;
		}
		
		$WHITE_LIST = (array_key_exists("WHITE_LIST", $op) ? $op["WHITE_LIST"] : array());
		$DEF_VAL = (array_key_exists("DEF_VAL", $op) ? $op["DEF_VAL"] : null);
		switch($place)
		{	
			case self::JS_HREF:			return urlencode(urlencode($value));
			case self::JS_HREF_ALERT:	return urlencode(CUtil::addslashes($value));
			case self::JS_IN_QUOTES:	return CUtil::JSEscape($value);
			case self::JS_EVENT:		return CUtil::addslashes(htmlspecialcharsbx($value));
			case self::ATTRIBUTE:		return htmlspecialcharsbx($value);
			case self::ATTRIBUTE_EX:	return htmlspecialcharsEx($value);
			case self::ID:				return preg_replace("/[^a-zA-Z0-9_]/", "",  $value);
			case self::HREF_LOCATION:
				$res = null;
				foreach($WHITE_LIST as $key => $value) if(substr($value, 0, strlen($value)) == $value) $res = $value;
				if($res == null)  $res = "/" . $value;
				return CUtil::addslashes(htmlspecialcharsbx($res));
		}
		return $DEF_VAL;
	}
	
	public function SortMethod($a, $b)
	{
		foreach($this->_sortFields as $k => $name)
		{
			if(array_key_exists($name, $this->_arFieldsTypes) && $a[$name] != $b[$name])
			{
				return ($a[$name] < $b[$name]) ? -1 : 1;
			}
		}
		return 0;
	}
	
	public function __construct($f, $arOrTable = self::C_Array)
	{
		$this->_arFieldsTypes = $f;
		$this->_classType = $arOrTable;
		$this->CleanVar();
	}
	
	public function checkRow($row)
	{
		if($this->_classType == self::C_Array || !array_key_exists($this->_currentRow, $this->_arFields)) $this->First();
		if($row != null && array_key_exists($row, $this->_arFields)) return  $row;
		return $this->_currentRow;
	}
		
	public function First()
	{
		$this->_currentRow = 0;
	}
	
	public function Last()
	{
		$this->_currentRow = (count($this->_arFields) - 1);
	}
	
	public function ResetNext()
	{
		$this->_resetNextF = true;
	}
		
	public function Next()
	{
		if($this->_resetNextF)
		{
			$this->_currentRow = -1;
			$this->_resetNextF = false;
		}
		if($this->_currentRow >= (count($this->_arFields) - 1)) 
		{
			return false;
		}
		$this->_currentRow++;
		return true;
	}
	
	public function Previous()
	{
		if($this->_currentRow <= 0) return false;
		$this->_currentRow--;
		return true;
	}
	
	public function Row($row)
	{
		$r = intval($row);
		if((count($this->table) - 1) < $r) return false;
		$this->_currentRow = $r;
		return true;
	}
	
	public function CleanVar($row = null, $removeExistingRows = false)
	{
		if($removeExistingRows) $this->RemoveExistingRows();
		$row = $this->checkRow($row);
		$this->_arFields[$row] = array();
		$this->_arModifiedFields[$row] = array();
		foreach($this->_arFieldsTypes as $key => $value) $this->Set($key, $value["DEF_VAL"], array(), $row, false);
	}
	
	public function RemoveExistingRows()
	{
		$this->_arFields = array();
		$this->_arModifiedFields = array();
		$this->First();
	}
		
	public function __set($name, $value)
	{
		$this->Set($name, $value);
	}
	
	public function AddRow()
	{
		$this->_arFields[] = array();
		$this->_arModifiedFields[] = array();
		$this->Last();
		$this->CleanVar();
	}
	
	/* заполнить поля из массива
		$sf = "Имя поля,Имя поля2,..."
		$sf = array("Имя поля", "Имя поля2",...) */
public 	public function SortRow($sf)
	{
		$this->_sortFields = CSupportTools::prepareParamArray($sf);
		$arr = $this->_arFields;
		uasort($arr, array($this, 'SortMethod'));
		$this->_arFields = $arr;
	}
		
	//$notNull = array(self::NOT_NULL, self::MORE0, self::NOT_EMTY_STR)
public 	public function Set($name, $value, $notNull = array(), $row = null, $isModified = true)
	{
		if(!array_key_exists($name, $this->_arFieldsTypes)) return;
		$row = $this->checkRow($row);
		$op = array();
		$ft = $this->_arFieldsTypes[$name];	
		if((in_array(self::NOT_NULL, $notNull) && $value === null) 
			|| (in_array(self::MORE0, $notNull) && $ft["TYPE"] == self::VT_NUMBER && intval($value) <= 0)
			|| (in_array(self::NOT_EMTY_STR, $notNull) && $value === "")
		) return;
		if(array_key_exists("MAX_STR_LEN", $ft)) $op["MAX_STR_LEN"] = $ft["MAX_STR_LEN"];
		if(array_key_exists("LIST", $ft))
		{
			$op["LIST"] = $ft["LIST"];
			$op["DEF_VAL"] = $ft["DEF_VAL"];
		}
		$this->_arFields[$row][$name] = self::Convert($ft["TYPE"], $value, $op);
		$this->_arModifiedFields[$row][$name] = $isModified;
	}
	
public 	public function SetCurrentTime($name, $row = null)
	{
		global $DB;
		$row = $this->checkRow($row);
		$arName = CSupportTools::prepareParamArray($name);
		foreach($arName as $key => $n)
		{
			if(!array_key_exists($n, $this->_arFieldsTypes)) return;
			if($this->_arFieldsTypes[$n]["TYPE"] == self::VT_DATE)
			{
				$this->_arFields[$row][$n] = time() + CTimeZone::GetOffset();
				$this->_arModifiedFields[$row][$n] = true;
			}
			elseif($this->_arFieldsTypes[$n]["TYPE"] == self::VT_DATE_TIME)
			{
				$this->_arFields[$row][$n] = time() + CTimeZone::GetOffset();
				$this->_arModifiedFields[$row][$n] = true;
			}
		}
	}
	
	/* заполнить поля из массива
		$fields = CSupportTableFields::ALL
		$fields = "Имя поля,Имя поля2,..."
		$fields = array("Имя поля", "Имя поля2",...)
		$fields = array("Имя поля" => "Имя поля в массиве", "Имя поля2" => "Имя поля в массиве2",...)
		$notNull = array(self::NOT_EMTY_STR, self::MORE0, self::NOT_EMTY_STR) */
public 	public function FromArray($arr, $fields = self::ALL, $notNull = array(), $row = null) //setFromArr
	{
		if(!is_array($arr)) return;
		$row = $this->checkRow($row);
		$fieldsArr = CSupportTools::prepareParamArray($fields, array_keys($arr));
		foreach($fieldsArr as $key => $name) 
		{
			$nameF = is_int($key) ? $name : $key;
			if(array_key_exists($name, $arr)) $this->Set($nameF, $arr[$name], $notNull, $row);
		}
	}
	
	/* заполнить поля из массива
		$fields = CSupportTableFields::ALL
		$fields = "Имя поля,Имя поля2,..."
		$fields = array("Имя поля", "Имя поля2",...)
		$fields = array("Имя поля" => "Имя поля в массиве", "Имя поля2" => "Имя поля в массиве2",...)
		$notNull = array(self::NOT_EMTY_STR, self::MORE0, self::NOT_EMTY_STR) */
public 	public function FromTable($table, $fields = self::ALL, $notNull = array(), $removeExistingRows = false) //setFromTable
	{
		if($removeExistingRows)
		{
			$this->RemoveExistingRows();
		}
		if(!is_array($table))
		{
			return;
		}
		foreach($table as $key => $arr) 
		{
			$this->AddRow();
			$this->FromArray($arr, $fields, $notNull); 
		}
	}
	
public static 	public function __get($name)
	{
		return $this->Get($name);
	}
	
public 	public function Get($name, $row = null)
	{
		if(!array_key_exists($name, $this->_arFieldsTypes)) return null;
		$row = $this->checkRow($row);
		return $this->_arFields[$row][$name];
	}
	
	/*Выгрузить перечисленные поля в массив
		$notNull = array(self::NOT_NULL, self::NOT_DEFAULT, self::ONLY_CHANGED)
		$fields = CSupportTableFields::ALL
		$fields = "Имя поля,Имя поля2,..."
		$fields = array("Имя поля", "Имя поля2",...)
		$fields = array("Имя поля" => "Имя поля в массиве", "Имя поля2" => "Имя поля в массиве2",...)*/
public 	public function ToArray($fields = self::ALL, $notNull = array(), $forSQL = false, $row = null)  //getArr
	{
		$row = $this->checkRow($row);
		$res = array();		
		$arFields = CSupportTools::prepareParamArray($fields, array_keys($this->_arFields[$row]));
		foreach($arFields as $key => $name)
		{
			$fName = is_int($key) ? $name : $key;
			if(!array_key_exists($fName, $this->_arFieldsTypes)) continue;
			$v = $this->_arFields[$row][$fName];
			$ft = $this->_arFieldsTypes[$fName];
			if(in_array(self::ONLY_CHANGED, $notNull) && (!isset($this->_arModifiedFields[$row][$fName]) || $this->_arModifiedFields[$row][$fName] != true))
			{
				continue;
			}
			elseif(in_array(self::NOT_NULL, $notNull) && $v === null)
			{
				continue;
			}
			elseif(in_array(self::NOT_DEFAULT, $notNull) && $v === $ft["DEF_VAL"])
			{
				continue;
			}
			if($forSQL)
			{
				if(array_key_exists("AUTO_CALCULATED", $ft)) continue;
				$res[$name] = self::ConvertForSQL($ft["TYPE"], $v);
			}
			else $res[$name] = $v;
		}
		return $res;
	}
		
	pubpublic lic function GetFieldForOutput($name, $place, $whiteList = array("http", "ftp", "/"), $row = null)
	{
		$row = $this->checkRow($row);
		if(!array_key_exists($name, $this->_arFieldsTypes))
		{
			return null;
		}
		$ft = $this->_arFieldsTypes[$name];
		$value = $this->_arFields[$row][$name];
		$op = array(
				"WHITE_LIST" =>	$whiteList,
				"DEF_VAL" =>	$ft["DEF_VAL"]
		);
		return self::ConvertForHTML($ft["TYPE"], $place, $value, $op);
	}
	
public static 	public function GetColumn($name)
	{
		$res = array();
		if(!array_key_exists($name, $this->_arFieldsTypes))
		{
			return false;
		}
		foreach($this->_arFields as $nom => $row) $res[$nom] = $row[$name];
		return $res;
	}
	
}	
?>