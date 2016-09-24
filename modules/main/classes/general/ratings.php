<?

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/ratings.php");


/**
 * <b>CRatings</b> - класс для работы с рейтингами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/index.php
 * @author Bitrix
 */
class CAllRatings
{
	// get specified rating record
	
	/**
	* <p>Метод возвращает параметры рейтинга с идентификатором id в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>.  Нестатический метод.</p>
	*
	*
	* @param int $intID  Идентификатор рейтинга
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $ID = 4;
	* $rsRating = CRatings::GetByID($ID);
	* $arRating = $rsRating-&gt;Fetch();
	* echo "&lt;pre&gt;"; print_r($arRating); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li>  
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getlist.php">CRatings::GetList</a></li>   <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getarraybyid.php">CRatings::GetArrayByID</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: GetByID<br>Line: ";

		if($ID<=0)
			return false;

		return ($DB->Query("
			SELECT
				R.*,
				".$DB->DateToCharFunction("R.CREATED")." as CREATED,
				".$DB->DateToCharFunction("R.LAST_MODIFIED")." as LAST_MODIFIED,
				".$DB->DateToCharFunction("R.LAST_CALCULATED")." as	LAST_CALCULATED
			FROM
				b_rating R
			WHERE
				ID=".$ID,
			false, $err_mess.__LINE__));
	}

	
	/**
	* <p>Метод возвращает параметры рейтинга с идентификатором id в виде массива. Нестатический метод.</p>
	*
	*
	* @param int $intID  Идентификатор рейтинга
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $ID = 4;
	* $arRating = CRatings::GetArrayByID($ID);
	* echo "&lt;pre&gt;"; print_r($arRating); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getlist.php">CRatings::GetList</a> </li>  
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getbyid.php">CRatings::GetByID</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getarraybyid.php
	* @author Bitrix
	*/
	public static function GetArrayByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: GetArrayByID<br>Line: ";
		$strID = "b".$ID;
		if(CACHED_b_rating===false)
		{
			$res = $DB->Query("
				SELECT
					R.*,
					".$DB->DateToCharFunction("R.CREATED")." as CREATED,
					".$DB->DateToCharFunction("R.LAST_MODIFIED")." as LAST_MODIFIED,
					".$DB->DateToCharFunction("R.LAST_CALCULATED")." as	LAST_CALCULATED
				FROM
					b_rating R
				WHERE
					ID=".$ID,
			false, $err_mess.__LINE__);
			$arResult = $res->Fetch();
		}
		else
		{
			global $stackCacheManager;
			$stackCacheManager->SetLength("b_rating", 100);
			$stackCacheManager->SetTTL("b_rating", CACHED_b_rating);
			if($stackCacheManager->Exist("b_rating", $strID))
				$arResult = $stackCacheManager->Get("b_rating", $strID);
			else
			{
				$res = $DB->Query("
					SELECT
						R.*,
						".$DB->DateToCharFunction("R.CREATED")." as CREATED,
						".$DB->DateToCharFunction("R.LAST_MODIFIED")." as LAST_MODIFIED,
						".$DB->DateToCharFunction("R.LAST_CALCULATED")." as	LAST_CALCULATED
					FROM
						b_rating R
					WHERE
						ID=".$ID,
				false, $err_mess.__LINE__);
				$arResult = $res->Fetch();
				if($arResult)
					$stackCacheManager->Set("b_rating", $strID, $arResult);
			}
		}

		return $arResult;
	}

	// get rating record list
	
	/**
	* <p>Метод возвращает список рейтингов в виде объекта класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. Cтатический метод.</p>
	*
	*
	* @param array $arSort  Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки. Допустимые ключи: ID, NAME,
	* CREATED, LAST_MODIFIED, LAST_CALCILATED, ACTIVE, STATUS, CALCULATED, CALCULATION_METHOD, ENTITY_ID.
	*
	* @param array $arFilter  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения. Допустимые
	* поля: ID, ACTIVE, CALCULATED, NAME, ENTITY_ID
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arRatings = array(); 
	* $rsRatings = CRatings::GetList( 
	*    array("ID" =&gt; "DESC"), 
	*    array("ACTIVE" =&gt; "Y")
	* ); 
	* while ($arRatingsTmp = $rsRatings-&gt;GetNext())
	*    $arRatings[$arRatingsTmp["ID"]] = $arRatingsTmp; 
	* echo '&lt;pre&gt;'.print_r($arRatings, true).'&lt;/pre&gt;';
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li>  
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getbyid.php">CRatings::GetByID</a> </li>   <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getarraybyid.php">CRatings::GetArrayByID</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arSort=array(), $arFilter=Array())
	{
		global $DB;

		$arSqlSearch = Array();
		$strSqlSearch = "";
		$err_mess = (CRatings::err_mess())."<br>Function: GetList<br>Line: ";

		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val)<=0 || $val=="NOT_REF")
					continue;
				switch(strtoupper($key))
				{
					case "ID":
						$arSqlSearch[] = GetFilterQuery("R.ID",$val,"N");
					break;
					case "ACTIVE":
						if (in_array($val, Array('Y','N')))
							$arSqlSearch[] = "R.ACTIVE = '".$val."'";
					break;
					case "AUTHORITY":
						if (in_array($val, Array('Y','N')))
							$arSqlSearch[] = "R.AUTHORITY = '".$val."'";
					break;
					case "POSITION":
						if (in_array($val, Array('Y','N')))
							$arSqlSearch[] = "R.POSITION = '".$val."'";
					break;
					case "CALCULATED":
						if (in_array($val, Array('Y','N','C')))
							$arSqlSearch[] = "R.CALCULATED = '".$val."'";
					break;
					case "NAME":
						$arSqlSearch[] = GetFilterQuery("R.NAME", $val);
					break;
					case "ENTITY_ID":
						$arSqlSearch[] = GetFilterQuery("R.ENTITY_ID", $val);
					break;
				}
			}
		}

		$sOrder = "";
		foreach($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"? "DESC":"ASC");
			switch (strtoupper($key))
			{
				case "ID":		$sOrder .= ", R.ID ".$ord; break;
				case "NAME":	$sOrder .= ", R.NAME ".$ord; break;
				case "CREATED":	$sOrder .= ", R.CREATED ".$ord; break;
				case "LAST_MODIFIED":	$sOrder .= ", R.LAST_MODIFIED ".$ord; break;
				case "LAST_CALCULATED":	$sOrder .= ", R.LAST_CALCULATED ".$ord; break;
				case "ACTIVE":	$sOrder .= ", R.ACTIVE ".$ord; break;
				case "AUTHORITY":$sOrder .= ", R.AUTHORITY ".$ord; break;
				case "POSITION":$sOrder .= ", R.POSITION ".$ord; break;
				case "STATUS":	$sOrder .= ", R.CALCULATED ".$ord; break;
				case "CALCULATED":	$sOrder .= ", R.CALCULATED ".$ord; break;
				case "CALCULATION_METHOD":	$sOrder .= ", R.CALCULATION_METHOD ".$ord; break;
				case "ENTITY_ID":	$sOrder .= ", R.ENTITY_ID ".$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = "R.ID DESC";

		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				R.ID, R.NAME, R.ACTIVE, R.CALCULATED, R.AUTHORITY, R.POSITION, R.ENTITY_ID, R.CALCULATION_METHOD,
				".$DB->DateToCharFunction("R.CREATED")." CREATED,
				".$DB->DateToCharFunction("R.LAST_MODIFIED")." LAST_MODIFIED,
				".$DB->DateToCharFunction("R.LAST_CALCULATED")." LAST_CALCULATED
			FROM
				b_rating R
			WHERE
			".$strSqlSearch."
			".$strSqlOrder;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	public static function GetRatingValueInfo($ratingId)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: GetRatingValueInfo<br>Line: ";
		$ratingId = intval($ratingId);

		$strSql = "
			SELECT
				MAX(CURRENT_VALUE) as MAX,
				MIN(CURRENT_VALUE) as MIN,
				AVG(CURRENT_VALUE) as AVG,
				COUNT(*) as CNT
			FROM b_rating_results
			WHERE RATING_ID = ".$ratingId;
		return $DB->Query($strSql, false, $err_mess.__LINE__);
	}

	//Addition rating
	
	/**
	* <p>Метод добавляет новый рейтинг. Нестатический метод.</p>
	*
	*
	* @param array $fields  Массив значений параметров. В качестве ключей данного массива
	* допустимо использовать: <ul> <li> <b>NAME</b> – название рейтинга</li>     <li>
	* <b>ACTIVE</b> – активность рейтинга (Y|N)</li>     <li> <b>ENTITY_ID</b> – оцениваемый
	* объект (например: USER, BLOG, и т.д.)</li>     <li> <b>CALCULATION_METHOD</b> – тип
	* подсчета рейтинга (SUM -суммирование критериев, AVG – средние
	* значение критериев)</li>     <li> <b>CONFIGS</b> – массив, описывающий
	* критерии рейтингования.</li> </ul> Все поля являются обязательными.
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // создадим рейтинг с критериями рейтингования от модуля форум и блог
	* // учитывать будем только рейтинговые голосования, активность учитывать не будем
	* $arConfig = array();
	* $arConfig['FORUM'] = array(
	* 	'VOTE' =&gt; array(
	* 		'TOPIC' =&gt; array(
	* 			'ACTIVE' =&gt; 'Y',
	* 			'COEFFICIENT' =&gt; '1',
	* 		),
	* 		'POST' =&gt; array(
	* 			'ACTIVE' =&gt; 'Y',
	* 			'COEFFICIENT' =&gt; '1',
	* 		),
	* 	),
	* 	'RATING' =&gt; array(
	* 		'ACTIVITY' =&gt; array(
	* 			'ACTIVE' =&gt; 'N',
	* 			'TODAY_TOPIC_COEF' =&gt; '20',
	* 			'WEEK_TOPIC_COEF' =&gt; '10',
	* 			'MONTH_TOPIC_COEF' =&gt; '5',
	* 			'TODAY_POST_COEF' =&gt; '0.4',
	* 			'WEEK_POST_COEF' =&gt; '0.2',
	* 			'MONTH_POST_COEF' =&gt; '0.1',
	* 		),
	* 	),
	* );
	* $arConfig['BLOG'] = array(
	* 'VOTE' =&gt; array(
	* 		'POST' =&gt; array(
	* 			'ACTIVE' =&gt; 'Y',
	* 			'COEFFICIENT' =&gt; '1',
	* 		),
	* 		'COMMENT' =&gt; array(
	* 			'ACTIVE' =&gt; 'Y',
	* 			'COEFFICIENT' =&gt; '1',
	* 		),
	* 	),
	* 	'RATING' =&gt; array(
	* 		'ACTIVITY' =&gt; array(
	* 			'ACTIVE' =&gt; 'N',
	* 			'TODAY_POST_COEF' =&gt; '20',
	* 			'WEEK_POST_COEF' =&gt; '10',
	* 			'MONTH_POST_COEF' =&gt; '5',
	* 			'TODAY_COMMENT_COEF' =&gt; '0.4',
	* 			'WEEK_COMMENT_COEF' =&gt; '0.2',
	* 			'MONTH_COMMENT_COEF' =&gt; '0.1',
	* 		),
	* 	),
	* );
	* 
	* $arAddRating = array(
	* 	'ACTIVE' =&gt; 'Y',
	* 	'NAME' =&gt; 'Рейтинг созданный через API',
	* 	'ENTITY_ID' =&gt; 'USER',
	* 	'CALCULATION_METHOD' =&gt; 'SUM',
	* 	'CONFIGS' =&gt; $arConfig
	* );
	* $ratingId = CRatings::Add($arAddRating);
	* if (intval($ratingId) &gt; 0)
	*     echo "Рейтинг успешно добавлен.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li>Метод <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/update.php">CRating::Update</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB, $stackCacheManager;

		$err_mess = (CRatings::err_mess())."<br>Function: Add<br>Line: ";

		// check only general field
		if(!CRatings::__CheckFields($arFields))
			return false;

		$arFields_i = Array(
			"ACTIVE"				=> $arFields["ACTIVE"] == 'Y' ? 'Y' : 'N',
			"POSITION"				=> $arFields["POSITION"] == 'Y' ? 'Y' : 'N',
			"AUTHORITY"				=> $arFields["AUTHORITY"] == 'Y' ? 'Y' : 'N',
			"NAME"					=> $arFields["NAME"],
			"ENTITY_ID"		 		=> $arFields["ENTITY_ID"],
			"CALCULATION_METHOD"	=> $arFields["CALCULATION_METHOD"],
			"~CREATED"				=> $DB->GetNowFunction(),
			"~LAST_MODIFIED"		=> $DB->GetNowFunction(),
		);
		$ID = $DB->Add("b_rating", $arFields_i);

		// queries modules and give them to inspect the field settings
		foreach(GetModuleEvents("main", "OnAfterAddRating", true) as $arEvent)
			$arFields = ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		CRatings::__AddComponents($ID, $arFields);

		$arFields_u = Array(
			"CONFIGS" => "'".$DB->ForSQL(serialize($arFields["CONFIGS"]))."'",
		);

		$DB->Update("b_rating", $arFields_u, "WHERE ID = ".$ID);

		if ($arFields['AUTHORITY'] == 'Y')
			CRatings::SetAuthorityRating($ID);

		CAgent::AddAgent("CRatings::Calculate($ID);", "main", "N", 3600, "", "Y", "");

		$stackCacheManager->Clear("b_rating");

		return $ID;
	}

	//Update rating
	
	/**
	* <p>Метод изменяет параметры рейтинга с идентификатором <i>id</i>. Нестатический метод.</p>
	*
	*
	* @param int $intid  Идентификатор рейтинга.
	*
	* @param array $fields  Массив значений параметров. В качестве ключей данного массива
	* допустимо использовать: <ul> <li> <b>NAME</b> – название рейтинга</li>     <li>
	* <b>ACTIVE</b> – активность рейтинга (Y|N)</li>     <li> <b>ENTITY_ID</b> – оцениваемый
	* объект (например: USER, BLOG, и т.д.)</li>      <li> <b>CALCULATION_METHOD</b> – тип
	* подсчета рейтинга (SUM-суммирование критериев, AVG – средние
	* значение критериев)</li>      <li> <b>CONFIGS</b> – массив, описывающий
	* критерии рейтингования</li>       <li> <b>NEW_CALC</b>  –  обнуляет предыдущее
	* значение рейтинга</li> </ul>
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // изменим название рейтинга и сделаем его не активным
	* $arUpdateRating = array(
	* 	'ACTIVE' =&gt; 'N',
	* 	'NAME' =&gt; 'Рейтинг, измененный через API',
	* );
	* $result = CRatings::Update(1, $arUpdateRating);
	* if ($result)
	*     echo "Рейтинг успешно обновлен.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/add.php">CRating::Add</a></li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB, $stackCacheManager;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: Update<br>Line: ";

		// check only general field
		if(!CRatings::__CheckFields($arFields))
			return false;

		$arFields_u = Array(
			"ACTIVE"				=> $arFields['ACTIVE'] == 'Y' ? 'Y' : 'N',
			"NAME"					=> $arFields["NAME"],
			"ENTITY_ID"		 		=> $arFields["ENTITY_ID"],
			"CALCULATION_METHOD"	=> $arFields["CALCULATION_METHOD"],
			"~LAST_MODIFIED"		=> $DB->GetNowFunction(),
		);
		$strUpdate = $DB->PrepareUpdate("b_rating", $arFields_u);
		if(!$DB->Query("UPDATE b_rating SET ".$strUpdate." WHERE ID=".$ID, false, $err_mess.__LINE__))
			return false;

		if (!isset($arFields["CONFIGS"]))
		{
			$stackCacheManager->Clear("b_rating");
			return true;
		}
		// queries modules and give them to inspect the field settings
		foreach(GetModuleEvents("main", "OnAfterUpdateRating", true) as $arEvent)
			$arFields = ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		CRatings::__UpdateComponents($ID, $arFields);

		$arFields_u = Array(
			"POSITION" => "'".($arFields['POSITION'] == 'Y' ? 'Y' : 'N')."'",
			"AUTHORITY" => "'".($arFields['AUTHORITY'] == 'Y' ? 'Y' : 'N')."'",
			"CONFIGS"  => "'".$DB->ForSQL(serialize($arFields["CONFIGS"]))."'",
		);
		$DB->Update("b_rating", $arFields_u, "WHERE ID = ".$ID);

		if ($arFields['AUTHORITY'] == 'Y')
			CRatings::SetAuthorityRating($ID);

		if ($arFields['NEW_CALC'] == 'Y')
			$DB->Query("UPDATE b_rating_results SET PREVIOUS_VALUE = 0 WHERE RATING_ID=".$ID." and ENTITY_TYPE_ID='".$DB->ForSql($arFields["ENTITY_ID"])."'", false, $err_mess.__LINE__);

		$strSql = "SELECT COMPLEX_NAME FROM b_rating_component WHERE RATING_ID = $ID and ACTIVE = 'N'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$arrRatingComponentId = array();
		while($arRes = $res->Fetch())
			$arrRatingComponentId[] = $arRes['COMPLEX_NAME'];

		if (!empty($arrRatingComponentId))
			$DB->Query("DELETE FROM b_rating_component_results WHERE RATING_ID = $ID AND COMPLEX_NAME IN ('".implode("','", $arrRatingComponentId)."')", false, $err_mess.__LINE__);

		CRatings::Calculate($ID, true);

		CAgent::RemoveAgent("CRatings::Calculate($ID);", "main");
		$AID = CAgent::AddAgent("CRatings::Calculate($ID);", "main", "N", 3600, "", "Y", "");

		$stackCacheManager->Clear("b_rating");

		return true;
	}

	// delete rating
	
	/**
	* <p>Метод удаляет рейтинг с идентификатором ID. Нестатический метод.</p>
	*
	*
	* @param int $intid  Идентификатор рейтинга
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // удаление рейтинга
	* $ID = 1;
	* $result = CRatings::Delete($ID);
	* if ($result)
	*     echo "Рейтинг успешно удален.";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/Delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB, $stackCacheManager;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: Delete<br>Line: ";

		foreach(GetModuleEvents("main", "OnBeforeDeleteRating", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		$DB->Query("DELETE FROM b_rating WHERE ID=$ID", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_rating_user WHERE RATING_ID=$ID", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_rating_component WHERE RATING_ID=$ID", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_rating_component_results WHERE RATING_ID=$ID", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_rating_results WHERE RATING_ID=$ID", false, $err_mess.__LINE__);

		CAgent::RemoveAgent("CRatings::Calculate($ID);", "main");

		$stackCacheManager->Clear("b_rating");

		return true;
	}

	// start calculation rating-component
	
	/**
	* <p>Метод подсчитывает результаты рейтинга с идентификатором <i>id</i>. Нестатический метод.</p>
	*
	*
	* @param int $intid  Идентификатор рейтинга
	*
	* @param bool $forceRecalc  Принудительный пересчет. Если данный параметр установлен в
	* <b>true</b>, то критерии рейтинга будут пересчитаны без учета времени
	* следующего запуска.
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // принудительно пересчитаем все критерии рейтинга
	* $ID = 4;
	* $bforceRecalc = true;
	* CRatings::Calculate($ID, $bforceRecalc);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/calculate.php
	* @author Bitrix
	*/
	public static function Calculate($ID, $bForceRecalc = false)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: Calculate<br>Line: ";

		$strSql = "SELECT
				RC.*,
				".$DB->DateToCharFunction("RC.LAST_MODIFIED")."	LAST_MODIFIED,
				".$DB->DateToCharFunction("RC.LAST_CALCULATED")." LAST_CALCULATED,
				".$DB->DateToCharFunction("RC.NEXT_CALCULATION")." NEXT_CALCULATION
			FROM
				b_rating_component RC
			WHERE
				RATING_ID = $ID
				and ACTIVE = 'Y' ".($bForceRecalc ? '' : 'AND NEXT_CALCULATION <= '.$DB->GetNowFunction());
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		while($arRes = $res->Fetch())
		{
			if(CModule::IncludeModule(strtolower($arRes['MODULE_ID']))) {
				$arRes['CONFIG'] = unserialize($arRes['CONFIG']);
				// If the type is automatic calculation of parameters * global vote weight
				$sRatingWeightType = COption::GetOptionString("main", "rating_weight_type", "auto");
				if ($sRatingWeightType == 'auto') {
					$voteWeight = COption::GetOptionString("main", "rating_vote_weight", 1);
					$arRes['CONFIG']['COEFFICIENT'] = $arRes['CONFIG']['COEFFICIENT']*$voteWeight;
				}
				if (strlen($arRes['EXCEPTION_METHOD']) > 0)
				{
					if (method_exists($arRes['CLASS'], $arRes['EXCEPTION_METHOD']))
					{
						$exceptionText = call_user_func(array($arRes['CLASS'], $arRes['EXCEPTION_METHOD']));
						if ($exceptionText === false)
							if (method_exists($arRes['CLASS'],  $arRes['CALC_METHOD']))
								$result = call_user_func(array($arRes['CLASS'], $arRes['CALC_METHOD']), $arRes);
					}
				}
				else
				{
					if (method_exists($arRes['CLASS'],  $arRes['CALC_METHOD']))
						$result = call_user_func(array($arRes['CLASS'], $arRes['CALC_METHOD']), $arRes);
				}
			}
		}

		CRatings::BuildRating($ID);

		return "CRatings::Calculate($ID);";
	}

	// queries modules and get all the available objects
	
	/**
	* <p>Метод возвращает все доступные объекты рейтинговая. Нестатический метод.</p>
	*
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $arObjects = CRatings:: GetRatingObjects();
	* echo "&lt;pre&gt;"; print_r($arObjects); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getratingobjects.php
	* @author Bitrix
	*/
	public static function GetRatingObjects()
	{
		$arObjects = array();

		foreach(GetModuleEvents("main", "OnGetRatingsObjects", true) as $arEvent)
		{
			$arConfig = ExecuteModuleEventEx($arEvent);
			foreach ($arConfig as $OBJ_TYPE)
				if (!in_array($OBJ_TYPE, $arObjects))
					$arObjects[] = $OBJ_TYPE;
		}
		return $arObjects;
	}

	// queries modules and get all the available entity types
	public static function GetRatingEntityTypes($objectType = null)
	{
		$arEntityTypes = array();

		foreach(GetModuleEvents("main", "OnGetRatingsConfigs", true) as $arEvent)
		{
			$arConfig = ExecuteModuleEventEx($arEvent);
			if (is_null($objectType))
			{
				foreach ($arConfig as $OBJ_TYPE => $OBJ_VALUE)
					foreach ($OBJ_VALUE['VOTE'] as $VOTE_VALUE)
					{
						$EntityTypeId = $VOTE_VALUE['MODULE_ID'].'_'.$VOTE_VALUE['ID'];
						if (!in_array($arEntityTypes[$OBJ_TYPE], $EntityTypeId))
							$arEntityTypes[$OBJ_TYPE][] = $EntityTypeId;
					}
			}
			else
			{
				foreach ($arConfig[$objectType]['VOTE'] as $VOTE_VALUE)
				{
					$EntityTypeId = $VOTE_VALUE['MODULE_ID'].'_'.$VOTE_VALUE['ID'];
					$arEntityTypes[$EntityTypeId] = $EntityTypeId;
				}
			}
		}

		return $arEntityTypes;
	}

	// queries modules and assemble an array of settings
	
	/**
	* <p>Метод возвращает все доступные критерии рейтингования и их настройки для указанного оцениваемого объекта. Нестатический метод.</p>
	*
	*
	* @param int $objectType  Имя оцениваемого объекта.
	*
	* @param bool $withRatingType  Учитывать тип рейтингования.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $objectType = 'USER';
	* $arRatinConfigs = CRatings::GetRatingConfigs($objectType);
	* echo "&lt;pre&gt;"; print_r($arRatinConfigs); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getratingconfigs.php
	* @author Bitrix
	*/
	public static function GetRatingConfigs($objectType = null, $withRatingType = true)
	{
		$arConfigs = array();

		foreach(GetModuleEvents("main", "OnGetRatingsConfigs", true) as $arEvent)
		{
			$arConfig = ExecuteModuleEventEx($arEvent);
			if (is_null($objectType))
			{
				foreach ($arConfig["COMPONENT"] as $OBJ_TYPE => $TYPE_VALUE)
				{
					foreach ($TYPE_VALUE as $RAT_TYPE => $RAT_VALUE)
					{
						foreach ($RAT_VALUE as $VALUE)
						{
							if ($withRatingType)
								$arConfigs[$OBJ_TYPE][$arConfig['MODULE_ID']][$RAT_TYPE][$arConfig['MODULE_ID']."_".$RAT_TYPE."_".$VALUE['ID']] = $VALUE;
							else
								$arConfigs[$OBJ_TYPE][$arConfig['MODULE_ID']][$arConfig['MODULE_ID']."_".$RAT_TYPE."_".$VALUE['ID']] = $VALUE;
						}
					}
				}
			}
			else
			{
				foreach ($arConfig["COMPONENT"][$objectType] as $RAT_TYPE => $RAT_VALUE)
				{
					$arConfigs[$arConfig['MODULE_ID']]['MODULE_ID'] = $arConfig['MODULE_ID'];
					$arConfigs[$arConfig['MODULE_ID']]['MODULE_NAME'] = $arConfig['MODULE_NAME'];
					foreach ($RAT_VALUE as $VALUE)
						if ($withRatingType)
							$arConfigs[$arConfig['MODULE_ID']][$RAT_TYPE][$arConfig['MODULE_ID']."_".$RAT_TYPE."_".$VALUE['ID']] = $VALUE;
						else
							$arConfigs[$arConfig['MODULE_ID']][$arConfig['MODULE_ID']."_".$RAT_TYPE."_".$VALUE['ID']] = $VALUE;
				}
			}
		}

		return $arConfigs;
	}

	
	/**
	* <p>Метод возвращает результаты рейтингового голосования для сущности с именем <i>entityTypeId</i> и идентификатором <i>entityId</i>. Нестатический метод.</p>
	*
	*
	* @param int $entityTypeId  Название сущности голосования. Например: FORUM_TOPIC для тем форума,
	* FORUM_POST для сообщений форума, BLOG_POST для постов в блоге, BLOG_COMMENT для
	* комментариев в блоге. Сам список кодов не регламентирован, можно
	* использовать любой удобный.
	*
	* @param mixed $entityId  Идентификатор сущности голосования (можно выбрать несколько
	* результатов, указав в этом параметре массив идентификаторов)
	*
	* @param int $user_Id  Идентификатор пользователя (если не указывать, подставляется
	* текущий пользователь)
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $entityTypeId = 'FORUM_TOPIC';
	* $entityId = 38;
	* $arVoteResult = CRatings::GetRatingVoteResult($entityTypeId, $entityId);
	* if(!empty($arVoteResult))
	*     echo '&lt;pre&gt;'.print_r($arVoteResult, true).'&lt;/pre&gt;';
	* else
	*     echo "Форум: за выбранную тему ещё не голосовали"; 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getratingresult.php">CRatings::GetRatingResult</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getratingvoteresult.php
	* @author Bitrix
	*/
	public static function GetRatingVoteResult($entityTypeId, $entityId, $user_id = false)
	{
		global $DB, $CACHE_MANAGER;
		$err_mess = (CRatings::err_mess())."<br>Function: GetRatingVoteResult<br>Line: ";

		$arResult = array();
		$user_id = intval($user_id);

		if ($user_id == 0)
			$user_id = $GLOBALS["USER"]->GetID();

		$bReturnEntityArray = true;
		if (is_array($entityId))
		{
			foreach ($entityId as $currentEntityId)
				$arResult[$currentEntityId] = self::GetRatingVoteResultCache($entityTypeId, $currentEntityId, $user_id);
		}
		else
		{
			$arResult = self::GetRatingVoteResultCache($entityTypeId, $entityId, $user_id);
		}

		return $arResult;
	}

	public static function GetRatingVoteResultCache($entityTypeId, $entityId, $user_id = false)
	{
		global $DB, $CACHE_MANAGER;
		$err_mess = (CRatings::err_mess())."<br>Function: GetRatingVoteResultCache<br>Line: ";

		$arResult = array();
		$entityId = intval($entityId);
		$user_id = intval($user_id);

		if (strlen($entityTypeId) <= 0 || $entityId <= 0)
			return $arResult;

		if ($user_id == 0)
			$user_id = $GLOBALS["USER"]->GetID();

		$bucket_size = intval(CACHED_b_rating_bucket_size);
		if($bucket_size <= 0)
			$bucket_size = 100;

		$bucket = intval($entityId/$bucket_size);
		if($CACHE_MANAGER->Read(CACHED_b_rating_vote, $cache_id="b_rvg_".$entityTypeId.$bucket, "b_rating_voting"))
		{
			$arResult = $CACHE_MANAGER->Get($cache_id);
		}
		else
		{
			$sql_str = "SELECT
							RVG.ID,
							RVG.ENTITY_ID,
							RVG.TOTAL_VALUE,
							RVG.TOTAL_VOTES,
							RVG.TOTAL_POSITIVE_VOTES,
							RVG.TOTAL_NEGATIVE_VOTES
						FROM
							b_rating_voting RVG
						WHERE
							RVG.ENTITY_TYPE_ID = '".$DB->ForSql($entityTypeId)."'
						and RVG.ENTITY_ID between ".($bucket*$bucket_size)." AND ".(($bucket+1)*$bucket_size-1)."
						and RVG.ACTIVE = 'Y'";
			$res = $DB->Query($sql_str, false, $err_mess.__LINE__);
			while($row = $res->Fetch())
			{
				$arResult[$row['ENTITY_ID']] = array(
					'USER_VOTE' => 0,
					'USER_HAS_VOTED' => 'N',
					'USER_VOTE_LIST' => Array(),
					'TOTAL_VALUE' => $row['TOTAL_VALUE'],
					'TOTAL_VOTES' => intval($row['TOTAL_VOTES']),
					'TOTAL_POSITIVE_VOTES' => intval($row['TOTAL_POSITIVE_VOTES']),
					'TOTAL_NEGATIVE_VOTES' => intval($row['TOTAL_NEGATIVE_VOTES']),
				);
			}

			$sql = "SELECT RVG.ENTITY_ID, RVG.USER_ID, RVG.VALUE
					FROM b_rating_vote RVG
					WHERE RVG.ENTITY_TYPE_ID = '".$DB->ForSql($entityTypeId)."'
					and RVG.ENTITY_ID between ".($bucket*$bucket_size)." AND ".(($bucket+1)*$bucket_size-1);

			$res = $DB->Query($sql, false, $err_mess.__LINE__);
			while($row = $res->Fetch())
				$arResult[$row['ENTITY_ID']]['USER_VOTE_LIST'][$row['USER_ID']] = $row['VALUE'];

			$CACHE_MANAGER->Set($cache_id, $arResult);
		}

		if (isset($arResult[$entityId]['USER_VOTE_LIST'][$user_id]))
		{
			$arResult[$entityId]['USER_VOTE'] = $arResult[$entityId]['USER_VOTE_LIST'][$user_id];
			$arResult[$entityId]['USER_HAS_VOTED'] = 'Y';
		}

		return isset($arResult[$entityId])? $arResult[$entityId]: Array();
	}

	
	/**
	* <p>Метод возвращает результаты рейтинга для оцениваемой сущности с идентификатором <i>entityId</i>. Нестатический метод.</p>
	*
	*
	* @param int $intId  идентификатор рейтинга
	*
	* @param mixed $entityId  идентификатор оцениваемого объекта (можно выбрать несколько
	* результатов, указав в этом параметре массив идентификаторов)
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $ratingId = 4;
	* $entityId = 1;
	* $arRatingResult = CRatings::GetRatingResult($ratingId, $entityId);
	* if(!empty($arRatingResult ))
	*     echo '&lt;pre&gt;'.print_r($arRatingResult , true).'&lt;/pre&gt;';
	* else
	*     echo "Указанный идентификатор не участвует в рейтинге";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getratingvoteresult.php">CRatings::GetRatingVoteResult</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/getratingresult.php
	* @author Bitrix
	*/
	public static function GetRatingResult($ID, $entityId)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: GetRatingResult<br>Line: ";
		$ID = IntVal($ID);

		static $cacheRatingResult = array();
		if(!array_key_exists($ID, $cacheRatingResult))
			$cacheRatingResult[$ID] = array();

		$arResult = array();
		$arToSelect = array();
		if(is_array($entityId))
		{
			foreach($entityId as $value)
			{
				$value = intval($value);
				if($value > 0)
				{
					if(array_key_exists($value, $cacheRatingResult[$ID]))
						$arResult[$value] = $cacheRatingResult[$ID][$value];
					else
					{
						$arResult[$value] = $cacheRatingResult[$ID][$value] = array();
						$arToSelect[$value] = $value;
					}
				}
			}
		}
		else
		{
			$value = intval($entityId);
			if($value > 0)
			{
				if(isset($cacheRatingResult[$ID][$value]))
					$arResult[$value] = $cacheRatingResult[$ID][$value];
				else
				{
					$arResult[$value] = $cacheRatingResult[$ID][$value] = array();
					$arToSelect[$value] = $value;
				}
			}
		}

		if(!empty($arToSelect))
		{
			$strSql  = "
				SELECT ENTITY_TYPE_ID, ENTITY_ID, PREVIOUS_VALUE, CURRENT_VALUE, PREVIOUS_POSITION, CURRENT_POSITION
				FROM b_rating_results
				WHERE RATING_ID = '".$ID."'  AND ENTITY_ID IN (".implode(',', $arToSelect).")
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			while($arRes = $res->Fetch())
			{

				$arRes['PROGRESS_VALUE'] = $arRes['CURRENT_VALUE'] - $arRes['PREVIOUS_VALUE'];
				$arRes['PROGRESS_VALUE'] = round($arRes['PROGRESS_VALUE'], 2);
				$arRes['PROGRESS_VALUE'] = $arRes['PROGRESS_VALUE'] > 0? "+".$arRes['PROGRESS_VALUE']: $arRes['PROGRESS_VALUE'];
				$arRes['ROUND_CURRENT_VALUE'] = round($arRes['CURRENT_VALUE']) == 0? 0: round($arRes['CURRENT_VALUE']);
				$arRes['ROUND_PREVIOUS_VALUE'] = round($arRes['PREVIOUS_VALUE']) == 0? 0: round($arRes['CURRENT_VALUE']);
				$arRes['CURRENT_POSITION'] = $arRes['CURRENT_POSITION'] > 0? $arRes['CURRENT_POSITION'] : GetMessage('RATING_NO_POSITION');
				if ($arRes['PREVIOUS_POSITION']>0)
				{
					$arRes['PROGRESS_POSITION'] = $arRes['PREVIOUS_POSITION'] - $arRes['CURRENT_POSITION'];
					$arRes['PROGRESS_POSITION'] = $arRes['PROGRESS_POSITION'] > 0? "+".$arRes['PROGRESS_POSITION']: $arRes['PROGRESS_POSITION'];
				}
				else
				{
					$arRes['PREVIOUS_POSITION'] = 0;
					$arRes['PROGRESS_POSITION'] = 0;
				}

				$arResult[$arRes["ENTITY_ID"]] = $cacheRatingResult[$ID][$arRes["ENTITY_ID"]] = $arRes;
			}
		}
		if(!is_array($entityId) && !empty($arResult))
			$arResult = array_pop($arResult);

		return $arResult;
	}


	
	/**
	* <p>Метод добавляет голос к рейтинговому голосованию.  Нестатический метод.</p>
	*
	*
	* @param array $Param  Массив значений параметров. В качестве ключей данного массива
	* допустимо использовать: <ul> <li> <b>ENTITY_TYPE_ID</b> – объект голосования.
	* Например: FORUM_TOPIC для тем форума, FORUM_POST для сообщений форума, BLOG_POST
	* для постов в блоге, BLOG_COMMENT для комментариев в блоге. Сам список
	* кодов не регламентирован, можно использовать любой удобный.</li>    
	* <li> <b>ENTITY_ID</b> – идентификатор объекта голосования</li>     <li> <b>VALUE</b> 
	* – выставленная оценка</li>     <li> <b>USER_ID</b> – идентификатор
	* пользователя</li>     <li> <b>USER_IP</b> – IP-адрес пользователя</li> </ul> Все
	* поля являются обязательными.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // добавляем теме отрицательную оценку.
	* $arAddVote = array(
	* 	"ENTITY_TYPE_ID"  =&gt;  "FORUM_TOPIC",
	* 	"ENTITY_ID"       =&gt;  38,
	* 	"VALUE"           =&gt;  -1,
	* 	"USER_ID"         =&gt;  1, 
	*  	"USER_IP"         =&gt;  '127.0.0.1',
	* );
	* CRatings::AddRatingVote($arAddVote);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/addratingvote.php
	* @author Bitrix
	*/
	public static function AddRatingVote($arParam)
	{
		global $DB, $CACHE_MANAGER;

		if (isset($_SESSION['RATING_VOTE_COUNT']) && $arParam['ENTITY_TYPE_ID'] == 'USER')
		{
			if ($_SESSION['RATING_VOTE_COUNT'] >= $_SESSION['RATING_USER_VOTE_COUNT'])
				return false;
			else
				$_SESSION['RATING_VOTE_COUNT']++;
		}

		$arParam['ENTITY_TYPE_ID'] = substr($arParam['ENTITY_TYPE_ID'], 0, 50);

		CRatings::CancelRatingVote($arParam);

		$err_mess = (CRatings::err_mess())."<br>Function: AddRatingVote<br>Line: ";
		$votePlus = $arParam['VALUE'] >= 0 ? true : false;

		$ratingId = CRatings::GetAuthorityRating();

		$arRatingUserProp = CRatings::GetRatingUserProp($ratingId, $arParam['USER_ID']);
		$voteUserWeight = $arRatingUserProp['VOTE_WEIGHT'];

		$sRatingWeightType = COption::GetOptionString("main", "rating_weight_type", "auto");
		if ($sRatingWeightType == 'auto')
		{
			if ($arParam['ENTITY_TYPE_ID'] == 'USER')
			{
				$sRatingAuthrorityWeight = COption::GetOptionString("main", "rating_authority_weight_formula", 'Y');
				if ($sRatingAuthrorityWeight == 'Y')
				{
					$communitySize = COption::GetOptionString("main", "rating_community_size", 1);
					$communityAuthority = COption::GetOptionString("main", "rating_community_authority", 1);
					$voteWeight = COption::GetOptionString("main", "rating_vote_weight", 1);
					$arParam['VALUE'] = $arParam['VALUE']*($communitySize*($voteUserWeight/$voteWeight)/$communityAuthority);
				}
			}
			else
			{
				$arParam['VALUE'] = $arParam['VALUE']*$voteUserWeight;
			}
		}
		else
		{
			$arParam['VALUE'] = $arParam['VALUE']*$voteUserWeight;
		}
		$arFields = array(
			'ACTIVE' => "'Y'",
			'TOTAL_VOTES' => "TOTAL_VOTES+1",
			'TOTAL_VALUE' => "TOTAL_VALUE".($votePlus ? '+' : '').floatval($arParam['VALUE']),
			'LAST_CALCULATED' => $DB->GetNowFunction(),
		);
		$arFields[($votePlus ? 'TOTAL_POSITIVE_VOTES' : 'TOTAL_NEGATIVE_VOTES')] = ($votePlus ? 'TOTAL_POSITIVE_VOTES+1' : 'TOTAL_NEGATIVE_VOTES+1');

		// GetOwnerDocument
		$arParam['OWNER_ID'] = 0;
		foreach(GetModuleEvents("main", "OnGetRatingContentOwner", true) as $arEvent)
		{
			$result = ExecuteModuleEventEx($arEvent, array($arParam));
			if ($result !== false)
				$arParam['OWNER_ID'] = IntVal($result);
		}

		$rowAffected = $DB->Update("b_rating_voting", $arFields, "WHERE ENTITY_TYPE_ID='".$DB->ForSql($arParam['ENTITY_TYPE_ID'])."' AND ENTITY_ID='".intval($arParam['ENTITY_ID'])."'" , $err_mess.__LINE__);
		if ($rowAffected > 0)
		{
			$rsRV = $DB->Query("SELECT ID, TOTAL_POSITIVE_VOTES FROM b_rating_voting WHERE ENTITY_TYPE_ID='".$DB->ForSql($arParam['ENTITY_TYPE_ID'])."' AND ENTITY_ID='".intval($arParam['ENTITY_ID'])."'", false, $err_mess.__LINE__);
			$arRV = $rsRV->Fetch();
			$arParam['RATING_VOTING_ID'] = $arRV['ID'];
			$arParam['TOTAL_POSITIVE_VOTES'] = $arRV['TOTAL_POSITIVE_VOTES'];
		}
		else
		{
			$arFields = array(
				"ENTITY_TYPE_ID"		=> "'".$DB->ForSql($arParam["ENTITY_TYPE_ID"])."'",
				"ENTITY_ID"				=> intval($arParam['ENTITY_ID']),
				"OWNER_ID"				=> intval($arParam['OWNER_ID']),
				"ACTIVE"					=> "'Y'",
				"CREATED"				=> $DB->GetNowFunction(),
				"LAST_CALCULATED"		=> $DB->GetNowFunction(),
				"TOTAL_VOTES"			=> 1,
				"TOTAL_VALUE"			=> floatval($arParam['VALUE']),
				"TOTAL_POSITIVE_VOTES"	=> ($votePlus ? 1 : 0),
				"TOTAL_NEGATIVE_VOTES"	=> ($votePlus ? 0 : 1)
			);
			$arParam['RATING_VOTING_ID'] = $DB->Insert("b_rating_voting", $arFields, $err_mess.__LINE__);
			$arParam['TOTAL_POSITIVE_VOTES'] = ($votePlus ? 1 : 0);
		}

		$arFields = array(
			"RATING_VOTING_ID"	=> intval($arParam['RATING_VOTING_ID']),
			"ENTITY_TYPE_ID"		=> "'".$DB->ForSql($arParam["ENTITY_TYPE_ID"])."'",
			"ENTITY_ID"				=> intval($arParam['ENTITY_ID']),
			"VALUE"				=> floatval($arParam['VALUE']),
			"ACTIVE"				=> "'Y'",
			"CREATED"			=> $DB->GetNowFunction(),
			"USER_ID"			=> intval($arParam['USER_ID']),
			"USER_IP"			=> "'".$DB->ForSql($arParam["USER_IP"])."'",
			"OWNER_ID"			=> intval($arParam['OWNER_ID']),
		);
		$ID = $DB->Insert("b_rating_vote", $arFields, $err_mess.__LINE__);

		foreach(GetModuleEvents("main", "OnAddRatingVote", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(intval($ID), $arParam));

		if (CModule::IncludeModule('pull'))
		{
			CPullStack::AddShared(Array(
				'module_id' => 'main',
				'command' => 'rating_vote',
				'params' => Array(
					"TYPE" => "ADD",
					"USER_ID" => intval($arParam['USER_ID']),
					"ENTITY_TYPE_ID" => $arParam["ENTITY_TYPE_ID"],
					"ENTITY_ID" => intval($arParam['ENTITY_ID']),
					"TOTAL_POSITIVE_VOTES" => $arParam['TOTAL_POSITIVE_VOTES'],
					"RESULT" => $votePlus? 'PLUS': 'MINUS',
				)
			));
		}

		if (CACHED_b_rating_vote!==false)
		{
			$bucket_size = intval(CACHED_b_rating_bucket_size);
			if($bucket_size <= 0)
				$bucket_size = 100;
			$bucket = intval(intval($arParam['ENTITY_ID'])/$bucket_size);
			$CACHE_MANAGER->Clean("b_rvg_".$DB->ForSql($arParam["ENTITY_TYPE_ID"]).$bucket, "b_rating_voting");
		}

		return true;
	}

	public static function CancelRatingVote($arParam)
	{
		global $DB, $CACHE_MANAGER;

		$err_mess = (CRatings::err_mess())."<br>Function: CancelRatingVote<br>Line: ";

		$sqlStr = "
			SELECT
				RVG.ID,
				RV.ID AS VOTE_ID,
				RV.VALUE AS VOTE_VALUE,
				RVG.TOTAL_POSITIVE_VOTES
			FROM
				b_rating_voting RVG,
				b_rating_vote RV
			WHERE
				RVG.ENTITY_TYPE_ID = '".$DB->ForSql($arParam['ENTITY_TYPE_ID'])."'
			and RVG.ENTITY_ID = ".intval($arParam['ENTITY_ID'])."
			and RVG.ID = RV.RATING_VOTING_ID
			and RV.USER_ID = ".intval($arParam['USER_ID']);

		$res = $DB->Query($sqlStr, false, $err_mess.__LINE__);
		if ($arVote = $res->Fetch())
		{
			$votePlus = $arVote['VOTE_VALUE'] >= 0 ? true : false;
			$arFields = array(
				'TOTAL_VOTES' => "TOTAL_VOTES-1",
				'TOTAL_VALUE' => "TOTAL_VALUE".($votePlus ? '-'.floatval($arVote['VOTE_VALUE']) : '+'.floatval(-1*$arVote['VOTE_VALUE'])),
				'LAST_CALCULATED' => $DB->GetNowFunction(),
			);
			$arFields[($votePlus ? 'TOTAL_POSITIVE_VOTES' : 'TOTAL_NEGATIVE_VOTES')] = ($votePlus ? 'TOTAL_POSITIVE_VOTES-1' : 'TOTAL_NEGATIVE_VOTES-1');
			$DB->Update("b_rating_voting", $arFields, "WHERE ID=".intval($arVote['ID']), $err_mess.__LINE__);
			$DB->Query("DELETE FROM b_rating_vote WHERE ID=".intval($arVote['VOTE_ID']), false, $err_mess.__LINE__);

			foreach(GetModuleEvents("main", "OnCancelRatingVote", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(intval($arVote['VOTE_ID']), $arParam));

			if (CModule::IncludeModule('pull'))
			{
				CPullStack::AddShared(Array(
					'module_id' => 'main',
					'command' => 'rating_vote',
					'params' => Array(
						"TYPE" => "CANCEL",
						"USER_ID" => intval($arParam['USER_ID']),
						"ENTITY_TYPE_ID" => $arParam["ENTITY_TYPE_ID"],
						"ENTITY_ID" => intval($arParam['ENTITY_ID']),
						"TOTAL_POSITIVE_VOTES" => intval($arVote['TOTAL_POSITIVE_VOTES']+($votePlus? -1: 1)),
						"RESULT" => $votePlus? 'PLUS': 'MINUS',
					)
				));
			}

			if (CACHED_b_rating_vote!==false)
			{
				$bucket_size = intval(CACHED_b_rating_bucket_size);
				if($bucket_size <= 0)
					$bucket_size = 100;
				$bucket = intval(intval($arParam['ENTITY_ID'])/$bucket_size);
				$CACHE_MANAGER->Clean("b_rvg_".$DB->ForSql($arParam["ENTITY_TYPE_ID"]).$bucket, "b_rating_voting");
			}

			return true;
		}

		return false;
	}

	public static function UpdateRatingUserBonus($arParam)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: AddRatingBonus<br>Line: ";

		$arParam['RATING_ID'] = intval($arParam['RATING_ID']);
		$arParam['ENTITY_ID'] = intval($arParam['ENTITY_ID']);
		$arParam['BONUS'] = floatval($arParam['BONUS']);

		$arFields = array(
			'RATING_ID'	=> $arParam['RATING_ID'],
			'ENTITY_ID'	=> $arParam['ENTITY_ID'],
			'BONUS'		=> $arParam['BONUS'],
		);

		if (isset($arParam['VOTE_WEIGHT']))
			$arFields['VOTE_WEIGHT'] = floatval($arParam['VOTE_WEIGHT']);

		if (isset($arParam['VOTE_COUNT']))
			$arFields['VOTE_COUNT'] = intval($arParam['VOTE_COUNT']);

		$rows = $DB->Update("b_rating_user", $arFields, "WHERE RATING_ID = ".$arParam['RATING_ID']." AND ENTITY_ID = ".$arParam['ENTITY_ID']);
		if ($rows == 0)
		{
			$rsRB = $DB->Query("SELECT * FROM b_rating_user WHERE RATING_ID = ".$arParam['RATING_ID']." AND ENTITY_ID = ".$arParam['ENTITY_ID'], false, $err_mess.__LINE__);
			if (!$rsRB->SelectedRowsCount())
				$DB->Insert("b_rating_user", $arFields, $err_mess.__LINE__);
		}
		if (CACHED_b_rating_vote!==false)
		{
			global $CACHE_MANAGER;
			$bucket_size = intval(CACHED_b_rating_bucket_size);
			if($bucket_size <= 0)
				$bucket_size = 100;

			$CACHE_MANAGER->Clean("b_rvu_".$arParam['RATING_ID'].intval($arParam['ENTITY_ID']/$bucket_size), "b_rating_user");
		}
		return true;
	}

	public static function GetRatingUserProp($ratingId, $entityId)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: GetRatingUserProp<br>Line: ";
		$ratingId = IntVal($ratingId);

		static $cache = array();
		if(!array_key_exists($ratingId, $cache))
			$cache[$ratingId] = array();

		$arResult = array();
		$arToSelect = array();
		if(is_array($entityId))
		{
			foreach($entityId as $value)
			{
				$value = intval($value);
				if($value > 0)
				{
					if(array_key_exists($value, $cache[$ratingId]))
						$arResult[$value] = $cache[$ratingId][$value];
					else
					{
						$arResult[$value] = $cache[$ratingId][$value] = array();
						$arToSelect[$value] = $value;
					}
				}
			}
		}
		else
		{
			$value = intval($entityId);
			if($value > 0)
			{
				if(isset($cache[$ratingId][$value]))
					$arResult[$value] = $cache[$ratingId][$value];
				else
				{
					$arResult[$value] = $cache[$ratingId][$value] = array();
					$arToSelect[$value] = $value;
				}
			}
		}

		if(!empty($arToSelect))
		{
			$strSql  = "
				SELECT RATING_ID, ENTITY_ID, BONUS, VOTE_WEIGHT, VOTE_COUNT
				FROM b_rating_user
				WHERE RATING_ID = '".$ratingId."' AND ENTITY_ID IN (".implode(',', $arToSelect).")
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			while($arRes = $res->Fetch())
				$arResult[$arRes["ENTITY_ID"]] = $cache[$ratingId][$arRes["ENTITY_ID"]] = $arRes;
		}

		if(!is_array($entityId) && !empty($arResult))
			$arResult = array_pop($arResult);

		return $arResult;
	}

	public static function GetRatingUserPropEx($ratingId, $entityId)
	{
		global $DB, $CACHE_MANAGER;
		$err_mess = (CRatings::err_mess())."<br>Function: GetRatingUserPropEx<br>Line: ";

		$ratingId = IntVal($ratingId);
		$entityId = IntVal($entityId);

		$arDefaultResult = array(
			"RATING_ID" => $ratingId,
			"ENTITY_ID" => $entityId,
			"BONUS" => 0,
			"VOTE_WEIGHT" => 0,
			"VOTE_COUNT" => 0
		);
		if ($ratingId <= 0 || $entityId <= 0)
			return $arDefaultResult;

		$bucket_size = intval(CACHED_b_rating_bucket_size);
		if($bucket_size <= 0)
			$bucket_size = 100;

		$bucket = intval($entityId/$bucket_size);
		$arResult = $CACHE_MANAGER->Read(CACHED_b_rating, $cache_id="b_rvu_".$ratingId.$bucket, "b_rating_user");
		if($arResult)
		{
			$arResult = $CACHE_MANAGER->Get($cache_id);
		}
		if (!$arResult)
		{
			$sql_str = "
				SELECT RATING_ID, ENTITY_ID, BONUS, VOTE_WEIGHT, VOTE_COUNT
				FROM b_rating_user
				WHERE RATING_ID = '".$ratingId."'
				and ENTITY_ID between ".($bucket*$bucket_size)." AND ".(($bucket+1)*$bucket_size-1)."
			";
			$res = $DB->Query($sql_str, false, $err_mess.__LINE__);
			while($arRes = $res->Fetch())
				$arResult[$arRes["ENTITY_ID"]] = $arRes;

			$CACHE_MANAGER->Set($cache_id, $arResult);
		}

		return isset($arResult[$entityId])? $arResult[$entityId]: $arDefaultResult;
	}

	public static function GetAuthorityRating()
	{
		global $DB;

		$authorityRatingId = COption::GetOptionString("main", "rating_authority_rating", null);
		if(is_null($authorityRatingId))
		{
			$db_res = CRatings::GetList(array("ID" => "ASC"), array( "ENTITY_ID" => "USER", "AUTHORITY" => "Y"));
			$res = $db_res->Fetch();

			$authorityRatingId = intval($res['ID']);
			COption::SetOptionString("main", "rating_authority_rating", $authorityRatingId);
		}

		return $authorityRatingId;
	}

	public static function GetWeightList($arSort=array(), $arFilter=Array())
	{
		global $DB;

		$arSqlSearch = Array();
		$strSqlSearch = "";
		$err_mess = (CRatings::err_mess())."<br>Function: GetWeightList<br>Line: ";

		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val)<=0 || $val=="NOT_REF")
					continue;
				switch(strtoupper($key))
				{
					case "ID":
						$arSqlSearch[] = GetFilterQuery("RW.ID",$val,"N");
					break;
					case "RATING_FROM":
						$arSqlSearch[] = GetFilterQuery("RW.RATING_FROM",$val,"N");
					break;
					case "RATING_TO":
						$arSqlSearch[] = GetFilterQuery("RW.RATING_TO",$val,"N");
					break;
					case "WEIGHT":
						$arSqlSearch[] = GetFilterQuery("RW.WEIGHT",$val,"N");
					break;
					case "COUNT":
						$arSqlSearch[] = GetFilterQuery("RW.COUNT",$val,"N");
					break;
					case "MAX":
						if (in_array($val, Array('Y','N')))
							$arSqlSearch[] = "R.MAX = '".$val."'";
					break;
				}
			}
		}

		$sOrder = "";
		foreach($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"? "DESC":"ASC");
			switch (strtoupper($key))
			{
				case "ID":		$sOrder .= ", RW.ID ".$ord; break;
				case "RATING_FROM":	$sOrder .= ", RW.RATING_FROM ".$ord; break;
				case "RATING_TO":		$sOrder .= ", RW.RATING_TO ".$ord; break;
				case "WEIGHT":	$sOrder .= ", RW.WEIGHT ".$ord; break;
				case "COUNT":	$sOrder .= ", RW.COUNT ".$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = "RW.ID DESC";

		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				RW.ID, RW.RATING_FROM, RW.RATING_TO, RW.WEIGHT, RW.COUNT
			FROM
				b_rating_weight RW
			WHERE
			".$strSqlSearch."
			".$strSqlOrder;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	public static function SetWeight($arConfigs)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: SetWeight<br>Line: ";

		usort($arConfigs, array('CRatings', '__SortWeight'));
		// prepare insert
		$arAdd = array();
		foreach($arConfigs as $key => $arConfig)
		{
			//If the first condition is restricted to the bottom, otherwise we take the previous high value
			if ($key == 0)
				$arConfig['RATING_FROM'] = -1000000;
			else
				$arConfig['RATING_FROM'] = floatval($arConfigs[$key-1]['RATING_TO'])+0.0001;
			// If this last condition is restricted to the top
			if (!array_key_exists('RATING_TO', $arConfig))
				$arConfig['RATING_TO'] = 1000000;
			elseif ($arConfig['RATING_TO'] > 1000000)
				$arConfig['RATING_TO'] = 1000000;

			$arAdd[$key]['RATING_FROM']   = floatval($arConfig['RATING_FROM']);
			$arAdd[$key]['RATING_TO']     = floatval($arConfig['RATING_TO']);
			$arAdd[$key]['WEIGHT'] = floatval($arConfig['WEIGHT']);
			$arAdd[$key]['COUNT']  = intval($arConfig['COUNT']);
			$arConfigs[$key] = $arAdd[$key];
		}
		// insert
		$DB->Query("DELETE FROM b_rating_weight", false, $err_mess.__LINE__);
		foreach($arAdd as $key => $arFields)
			$DB->Insert("b_rating_weight", $arFields, $err_mess.__LINE__);

		return true;
	}

	public static function SetVoteGroup($arGroupId, $type)
	{
		global $DB, $CACHE_MANAGER;
		$err_mess = (CRatings::err_mess())."<br>Function: SetVoteGroup<br>Line: ";

		if (!in_array($type, array('R', 'A')))
			return false;

		if (!is_array($arGroupId))
			return false;

		$arFields = array();

		foreach ($arGroupId as $key => $value)
		{
			$arField = array();
			$arField['GROUP_ID'] = intval($value);
			$arField['TYPE'] = "'".$type."'";
			$arFields[$key] = $arField;
		}

		$DB->Query("DELETE FROM b_rating_vote_group WHERE TYPE = '".$type."'", false, $err_mess.__LINE__);
		foreach($arFields as $key => $arField)
			$DB->Insert("b_rating_vote_group", $arField, $err_mess.__LINE__);

		$CACHE_MANAGER->Clean("ratings_vg");

		return true;
	}

	public static function GetVoteGroup($type = '')
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: GetVoteGroup<br>Line: ";

		$bAllType = false;
		if (!in_array($type, array('R', 'A')))
			$bAllType = true;

		$strSql = "SELECT ID, GROUP_ID, TYPE FROM b_rating_vote_group RVG";

		if (!$bAllType)
			$strSql .= " WHERE TYPE = '".$type."'";

		return $DB->Query($strSql, false, $err_mess.__LINE__);
	}

	public static function GetVoteGroupEx($type = '')
	{
		global $DB, $CACHE_MANAGER;
		$err_mess = (CRatings::err_mess())."<br>Function: GetVoteGroupEx<br>Line: ";

		$res = $CACHE_MANAGER->Read(2592000, "ratings_vg");
		if ($res)
		{
			$arResult = $CACHE_MANAGER->Get("ratings_vg");
		}
		else
		{
			$strSql = "SELECT GROUP_ID, TYPE FROM b_rating_vote_group RVG";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			while($arRes = $res->Fetch($res))
			{
				$arResult[] = $arRes;
			}
			$CACHE_MANAGER->Set("ratings_vg", $arResult);
		}
		if ($type != '')
		{
			foreach ($arResult as $key => $value)
			{
				if ($value['TYPE'] != $type)
					unset($arResult[$key]);
			}
		}
		return $arResult;
	}

	public static function ClearData()
	{
		global $DB, $CACHE_MANAGER;
		$err_mess = (CRatings::err_mess())."<br>Function: ClearData<br>Line: ";

		$DB->Query("TRUNCATE TABLE b_rating_prepare", false, $err_mess.__LINE__);
		$DB->Query("TRUNCATE TABLE b_rating_voting_prepare", false, $err_mess.__LINE__);

		$DB->Query("TRUNCATE TABLE b_rating_results", false, $err_mess.__LINE__);
		$DB->Query("TRUNCATE TABLE b_rating_component_results", false, $err_mess.__LINE__);

		$DB->Query("TRUNCATE TABLE b_rating_vote", false, $err_mess.__LINE__);
		$DB->Query("TRUNCATE TABLE b_rating_voting", false, $err_mess.__LINE__);

		$DB->Query("UPDATE b_rating_user SET VOTE_WEIGHT = 0, VOTE_COUNT = 0", false, $err_mess.__LINE__);

		$CACHE_MANAGER->CleanDir("b_rating_voting");
		$CACHE_MANAGER->CleanDir("b_rating_user");

		return true;
	}

	public static function OnUserDelete($ID)
	{
		CRatings::DeleteByUser($ID);
		return true;
	}

	public static function OnAfterUserRegister($arFields)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: OnAfterUserRegister<br>Line: ";

		$userId = isset($arFields["USER_ID"]) ? intval($arFields["USER_ID"]): (isset($arFields["ID"]) ? intval($arFields["ID"]): 0);
		if($userId>0)
		{
			$authorityRatingId = CRatings::GetAuthorityRating();
			$ratingStartValue = COption::GetOptionString("main", "rating_start_authority", 3);
			$ratingCountVote = COption::GetOptionString("main", "rating_count_vote", 10);

			$arParam = array(
				'RATING_ID' => $authorityRatingId,
				'ENTITY_ID' => $userId,
				'BONUS' => intval($ratingStartValue),
				'VOTE_WEIGHT' => intval($ratingStartValue)*COption::GetOptionString("main", "rating_vote_weight", 1),
				'VOTE_COUNT' => intval($ratingCountVote)+intval($ratingStartValue),
			);
			CRatings::UpdateRatingUserBonus($arParam);

			if (IsModuleInstalled("intranet"))
			{
				$strSql = "INSERT INTO b_rating_subordinate (RATING_ID, ENTITY_ID, VOTES) VALUES ('".$authorityRatingId."', '".$userId."', '".(intval($ratingCountVote)+intval($ratingStartValue))."')";
				$DB->Query($strSql, false, $err_mess.__LINE__);
			}

			$sRatingAssignType = COption::GetOptionString("main", "rating_assign_type", 'manual');
			if ($sRatingAssignType == 'auto')
			{
				$assignRatingGroup = COption::GetOptionString("main", "rating_assign_rating_group", 0);
				$assignAuthorityGroup = COption::GetOptionString("main", "rating_assign_authority_group", 0);
				if ($assignRatingGroup == 0 && $assignAuthorityGroup == 0)
					return false;

				$arGroups = array();
				$res = CUser::GetUserGroupList($userId);
				while($res_arr = $res->Fetch())
					$arGroups[] = array("GROUP_ID"=>$res_arr["GROUP_ID"], "DATE_ACTIVE_FROM"=>$res_arr["DATE_ACTIVE_FROM"], "DATE_ACTIVE_TO"=>$res_arr["DATE_ACTIVE_TO"]);

				if ($assignRatingGroup > 0)
					$arGroups[] = array("GROUP_ID"=>intval($assignRatingGroup));
				if ($assignAuthorityGroup > 0 && $assignRatingGroup != $assignAuthorityGroup)
					$arGroups[] = array("GROUP_ID"=>intval($assignAuthorityGroup));

				CUser::SetUserGroup($userId, $arGroups);
			}
			if (CACHED_b_rating_vote!==false)
			{
				global $CACHE_MANAGER;
				$bucket_size = intval(CACHED_b_rating_bucket_size);
				if($bucket_size <= 0)
					$bucket_size = 100;

				$bucket = intval($userId/$bucket_size);
				$CACHE_MANAGER->Clean("b_rvu_".$authorityRatingId.$bucket, "b_rating_user");
			}
		}
	}

	public static function __SortWeight($a, $b)
	{
		if (isset($a['RATING_FROM']) || isset($b['RATING_FROM']))
			return 1;

		return floatval($a['RATING_TO']) < floatval($b['RATING_TO']) ? -1 : 1;
	}

	// check only general field
	public static function __CheckFields($arFields)
	{
		$aMsg = array();

		if(is_set($arFields, "NAME") && trim($arFields["NAME"])=="")
			$aMsg[] = array("id"=>"NAME", "text"=>GetMessage("RATING_GENERAL_ERR_NAME"));
		if(is_set($arFields, "ACTIVE") && !($arFields["ACTIVE"] == 'Y' || $arFields["ACTIVE"] == 'N'))
			$aMsg[] = array("id"=>"ACTIVE", "text"=>GetMessage("RATING_GENERAL_ERR_ACTIVE"));
		if(is_set($arFields, "ENTITY_ID"))
		{
			$arObjects = CRatings::GetRatingObjects();
			if(!in_array($arFields['ENTITY_ID'], $arObjects))
				$aMsg[] = array("id"=>"ENTITY_ID", "text"=>GetMessage("RATING_GENERAL_ERR_ENTITY_ID"));
		}
		if(is_set($arFields, "CALCULATION_METHOD") && trim($arFields["CALCULATION_METHOD"])=="")
			$aMsg[] = array("id"=>"CALCULATION_METHOD", "text"=>GetMessage("RATING_GENERAL_ERR_CAL_METHOD"));

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	// creates a configuration record for each item rating
	public static function __AddComponents($ID, $arFields)
	{
		global $DB;

		$arRatingConfigs = CRatings::GetRatingConfigs($arFields["ENTITY_ID"], false);

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: __AddComponents<br>Line: ";
		foreach ($arFields['CONFIGS'] as $MODULE_ID => $RAT_ARRAY)
		{
			if (!is_array($RAT_ARRAY))
				continue;

			foreach ($RAT_ARRAY as $RAT_TYPE => $COMPONENT)
			{
				if (!is_array($COMPONENT))
					continue;

				foreach ($COMPONENT as $COMPONENT_NAME => $COMPONENT_VALUE)
				{
					if (!isset($arRatingConfigs[$MODULE_ID][$MODULE_ID."_".$RAT_TYPE."_".$COMPONENT_NAME]))
						continue;

					$arFields_i = Array(
						"RATING_ID"			=> $ID,
						"ACTIVE"			=> isset($COMPONENT_VALUE["ACTIVE"]) && $COMPONENT_VALUE["ACTIVE"] == 'Y' ? 'Y' : 'N',
						"ENTITY_ID"			=> $arFields["ENTITY_ID"],
						"MODULE_ID"			=> $MODULE_ID,
						"RATING_TYPE"		=> $RAT_TYPE,
						"NAME"				=> $COMPONENT_NAME,
						"COMPLEX_NAME"		=> $arFields["ENTITY_ID"].'_'.$MODULE_ID.'_'.$RAT_TYPE.'_'.$COMPONENT_NAME,
						"CLASS"				=> $arRatingConfigs[$MODULE_ID][$MODULE_ID."_".$RAT_TYPE."_".$COMPONENT_NAME]["CLASS"],
						"CALC_METHOD"		=> $arRatingConfigs[$MODULE_ID][$MODULE_ID."_".$RAT_TYPE."_".$COMPONENT_NAME]["CALC_METHOD"],
						"EXCEPTION_METHOD"	=> $arRatingConfigs[$MODULE_ID][$MODULE_ID."_".$RAT_TYPE."_".$COMPONENT_NAME]["EXCEPTION_METHOD"],
						"REFRESH_INTERVAL"	=> $arRatingConfigs[$MODULE_ID][$MODULE_ID."_".$RAT_TYPE."_".$COMPONENT_NAME]["REFRESH_TIME"],
						"~LAST_MODIFIED"	=> $DB->GetNowFunction(),
						"~NEXT_CALCULATION" => $DB->GetNowFunction(),
						"IS_CALCULATED"		=> "N",
						"~CONFIG"			=> "'".serialize($COMPONENT_VALUE)."'",
					);

					$DB->Add("b_rating_component", $arFields_i, array(), "", false, $err_mess.__LINE__);
				}
			}
		}


		return true;
	}

	public static function __UpdateComponents($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: __UpdateComponents<br>Line: ";

		$DB->Query("DELETE FROM b_rating_component WHERE RATING_ID=$ID", false, $err_mess.__LINE__);

		CRatings::__AddComponents($ID, $arFields);

		return true;
	}

	public static function err_mess()
	{
		return "<br>Class: CRatings<br>File: ".__FILE__;
	}

	public static function GetRatingVoteList($arParam)
	{
		global $DB, $USER;

		$bplus = true;
		if (strtoupper($arParam['LIST_TYPE']) == 'MINUS')
			$bplus = false;

		$sqlStr = "
			SELECT
				COUNT(RV.ID) as CNT
			FROM
				b_rating_vote RV
			WHERE
				RV.ENTITY_TYPE_ID = '".$DB->ForSql($arParam['ENTITY_TYPE_ID'])."'
			and RV.ENTITY_ID = ".intval($arParam['ENTITY_ID'])."
			".($bplus? " and RV.VALUE > 0 ": " and RV.VALUE < 0 ");
		$res_cnt = $DB->Query($sqlStr);
		$res_cnt = $res_cnt->Fetch();
		$cnt = $res_cnt["CNT"];

		$bIntranetInstalled = IsModuleInstalled("intranet");

		$bExtended = false;
		$arUserID = array();

		if (
			(
				array_key_exists("USER_FIELDS", $arParam)
				&& is_array($arParam["USER_FIELDS"])
			)
			|| (
				array_key_exists("USER_SELECT", $arParam)
				&& is_array($arParam["USER_SELECT"])
			)
		)
		{
			$bExtended = true;
			$sqlStr = CRatings::GetRatingVoteListSQLExtended($arParam, $bplus, $bIntranetInstalled);
		}
		else
		{
			$sqlStr = CRatings::GetRatingVoteListSQL($arParam, $bplus, $bIntranetInstalled);
		}

		$arList = Array();
		$arVoteList = Array();
		if ($arParam['LIST_LIMIT'] != 0 && ceil($cnt/intval($arParam['LIST_LIMIT'])) >= intval($arParam['LIST_PAGE']))
		{
			$res = new CDBResult();
			$res->NavQuery($sqlStr, $cnt, Array('iNumPage' => intval($arParam['LIST_PAGE']), 'nPageSize' => intval($arParam['LIST_LIMIT'])));

			while ($row = $res->Fetch())
			{
				$ar = $row;

				if (!$bExtended)
				{
					$ar["PHOTO"] = $ar["PHOTO_SRC"] = '';
					if (!empty($ar["PERSONAL_PHOTO"]))
					{
						$arFileTmp = CFile::ResizeImageGet(
							$row["PERSONAL_PHOTO"],
							array('width' => 58, 'height' => 58),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
						$ar['PHOTO'] = CFile::ShowImage($arFileTmp['src'], 21, 21, 'border=0');
						$ar['PHOTO_SRC'] = $arFileTmp['src'];
					}
					$ar['FULL_NAME'] = CUser::FormatName(CSite::GetNameFormat(false), $row, $bIntranetInstalled);
				}
				else
					$arUserID[] = $row["ID"];

				if ($ar['ID'] != $USER->GetId())
					$arList[$ar['ID']] = $ar;
				else
					$arVoteList[$ar['ID']] = $ar;
			}
			foreach ($arList as $ar)
				$arVoteList[$ar['ID']] = $ar;

			if (
				$bExtended
				&& count($arUserID) > 0
			)
			{
				$arUserListParams = array();
				$arUsers = array();

				if (
					array_key_exists("USER_FIELDS", $arParam)
					&& is_array($arParam["USER_FIELDS"])
				)
					$arUserListParams["FIELDS"] = $arParam["USER_FIELDS"];
				else
					$arUserListParams["FIELDS"] = array("NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO");

				$arUserListParams["FIELDS"] = array_unique(array_merge(array("ID"), $arUserListParams["FIELDS"]));

				if (
					array_key_exists("USER_SELECT", $arParam)
					&& is_array($arParam["USER_SELECT"])
				)
				{
					$arUserListParams["SELECT"] = $arParam["USER_SELECT"];
				}

				$rsUser = CUser::GetList(
					($by = "ID"),
					($order = "ASC"),
					array("ID" => implode("|", $arUserID)),
					$arUserListParams
				);

				while ($arUser = $rsUser->Fetch())
				{
					$arUser["PHOTO"] = $arUser["PHOTO_SRC"] = '';
					if (array_key_exists("PERSONAL_PHOTO", $arUser))
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arUser["PERSONAL_PHOTO"],
							array("width" => 58, "height" => 58),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
						$arUser["PHOTO_SRC"] = $arFileTmp["src"];
						$arUser["PHOTO"] = CFile::ShowImage($arFileTmp["src"], 21, 21, "border=0");
					}
					$arUser["FULL_NAME"] = CUser::FormatName(CSite::GetNameFormat(false), $arUser, $bIntranetInstalled);
					$arUsers[$arUser["ID"]] = $arUser;
				}

				foreach($arVoteList as $i => $arVoteUser)
					if (array_key_exists($arVoteUser["ID"], $arUsers))
						foreach($arUsers[$arVoteUser["ID"]] as $key => $value)
							$arVoteList[$i][$key] = $value;

			}
		}

		return Array(
			'items_all' => $cnt,
			'items_page' => count($arVoteList),
			'items' => $arVoteList
		);
	}
}
