<?
// 2012-04-13 Checked/modified for compatibility with new data model
class CLTestMark
{
	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function CheckFields(&$arFields, $ID = false)
	{
		global $DB;
		$arMsg = Array();

		if ( (is_set($arFields, "MARK") || $ID === false) && strlen($arFields["MARK"]) <= 0)
			$arMsg[] = array("id"=>"MARK", "text"=> GetMessage("LEARNING_BAD_MARK"));


		if (
			($ID === false && !is_set($arFields, "TEST_ID"))
			||
			(is_set($arFields, "TEST_ID") && intval($arFields["TEST_ID"]) < 1)
			)
		{
			$arMsg[] = array("id"=>"TEST_ID", "text"=> GetMessage("LEARNING_BAD_TEST_ID"));
		}
		elseif (is_set($arFields, "TEST_ID"))
		{
			$res = CTest::GetByID($arFields["TEST_ID"]);
			if(!$arRes = $res->Fetch())
				$arMsg[] = array("id"=>"TEST_ID", "text"=> GetMessage("LEARNING_BAD_TEST_ID"));
		}

		if (!is_set($arFields, "SCORE") || intval($arFields["SCORE"]) > 100 || intval($arFields["SCORE"]) < 1)
		{
			$arMsg[] = array("id"=>"SCORE", "text"=> GetMessage("LEARNING_BAD_MARK_SCORE"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public function Add($arFields)
	{
		global $DB;

		if($this->CheckFields($arFields))
		{
			unset($arFields["ID"]);

			$ID = $DB->Add("b_learn_test_mark", $arFields, Array("DESCRIPTION"), "learning");

			return $ID;
		}

		return false;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;


		if ($this->CheckFields($arFields, $ID))
		{
			unset($arFields["ID"]);

			$arBinds=Array(
				"DESCRIPTION"=>$arFields["DESCRIPTION"]
			);

			$strUpdate = $DB->PrepareUpdate("b_learn_test_mark", $arFields, "learning");
			$strSql = "UPDATE b_learn_test_mark SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			return true;
		}
		return false;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID < 1) return false;

		$strSql = "DELETE FROM b_learn_test_mark WHERE ID = ".$ID;

		if (!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			return false;

		return true;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function GetByID($ID)
	{
		return CLTestMark::GetList($arOrder=Array(), $arFilter=Array("ID" => $ID));
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function GetByPercent($TEST_ID, $PERCENT)
	{
		global $DB;

		$PERCENT = intval($PERCENT);
		if ($PERCENT < 0 || $PERCENT > 100)
			return false;

		$TEST_ID = intval($TEST_ID);
		if ($TEST_ID <= 0)
			return false;

		$arFilter = array(
			">=SCORE" => $PERCENT,
			"TEST_ID" => $TEST_ID
		);

		$arOrder = array(
			"SCORE" => "ASC"
		);

		$rsMark = CLTestMark::GetList($arOrder, $arFilter);

		if ($arMark = $rsMark->GetNext())
			return $arMark["MARK"];
		else
			return false;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CLearnHelper::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case "ID":
				case "SCORE":
				case "TEST_ID":
					$arSqlSearch[] = CLearnHelper::FilterCreate("TM.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;
			}

		}

		return $arSqlSearch;
	}


	// 2012-04-13 Checked/modified for compatibility with new data model
	public static function GetList($arOrder=Array(), $arFilter=Array())
	{
		global $DB, $USER;

		$arSqlSearch = CLTestMark::GetFilter($arFilter);

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
			if(strlen($arSqlSearch[$i])>0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql =
		"SELECT TM.* ".
		"FROM b_learn_test_mark TM ".
		"WHERE 1=1 ".
		$strSqlSearch;

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach($arOrder as $by=>$order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order!="asc")
				$order = "desc";

			if ($by == "id") $arSqlOrder[] = " TM.ID ".$order." ";
			elseif ($by == "mark") $arSqlOrder[] = " TM.MARK ".$order." ";
			elseif ($by == "score") $arSqlOrder[] = " TM.SCORE ".$order." ";
			elseif ($by == "rand") $arSqlOrder[] = CTest::GetRandFunction();
			else
			{
				$arSqlOrder[] = " TM.ID ".$order." ";
				$by = "id";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i=0; $i<count($arSqlOrder); $i++)
		{
			if($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		//echo $strSql;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
}
