<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CPostingGeneral</b> - класс для работы с выпусками новостей подписки.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/index.php
 * @author Bitrix
 */
class CPostingGeneral
{
	var $LAST_ERROR="";
	//email count for one hit
	static $current_emails_per_hit = 0;

	//get by ID
	
	/**
	* <p>Метод возвращает выпуск по его идентификатору. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingfields.php">поля объекта
	* "Выпуск"</a>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $rsPosting = <b>CPosting::GetByID</b>($ID);
	* $arPosting = $rsPosting-&gt;Fetch();
	* if($arPosting)
	*     echo htmlspecialchars(print_r($arPosting, true));
	* else
	*     echo "Not found";
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostinggetbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT
				P.*
				,".$DB->DateToCharFunction("P.TIMESTAMP_X", "FULL")." AS TIMESTAMP_X
				,".$DB->DateToCharFunction("P.DATE_SENT", "FULL")." AS DATE_SENT
				,".$DB->DateToCharFunction("P.AUTO_SEND_TIME", "FULL")." AS AUTO_SEND_TIME
			FROM b_posting P
			WHERE P.ID=".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	//list of categories linked with message
	
	/**
	* <p>Метод возвращает выборку рассылок, на которые будет отправлен выпуск. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">поля объекта
	* "Рассылка"</a>: ID, NAME, SORT, LID, ACTIVE.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //в какие рубрики отправлять
	* $aPostRub = array();
	* $post_rub = <b>CPosting::GetRubricList</b>($post_arr["ID"]);
	* while($post_rub_arr = $post_rub-&gt;Fetch())
	*     $aPostRub[] = $post_rub_arr["ID"];
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostinggetrubriclist.php
	* @author Bitrix
	*/
	public static function GetRubricList($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT
				R.ID
				,R.NAME
				,R.SORT
				,R.LID
				,R.ACTIVE
			FROM
				b_list_rubric R
				,b_posting_rubric PR
			WHERE
				R.ID=PR.LIST_RUBRIC_ID
				AND PR.POSTING_ID=".$ID."
			ORDER BY
				R.LID, R.SORT, R.NAME
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	//list of user group linked with message
	
	/**
	* <p>Метод возвращает выборку групп пользователей, на которые будет отправлен выпуск. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны поля
	* объекта "Группа": ID, NAME.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* //show user groups and check selected for the issue
	* $aPostGrp = array();
	* if($ID&gt;0)
	* {
	*     $post_grp = <b>CPosting::GetGroupList</b>($ID);
	*     while($post_grp_arr = $post_grp-&gt;Fetch())
	*         $aPostGrp[] = $post_grp_arr["ID"];
	* }
	* $group = CGroup::GetList(($by="name"), ($order="asc"));
	* while($group-&gt;ExtractFields("g_")):
	* ?&gt;
	* &lt;input type="checkbox" name="GROUP_ID[]" value="&lt;?echo $g_ID?&gt;"&lt;?if(in_array($g_ID, $aPostGrp)) echo " checked"?&gt;&gt;
	* &lt;?echo $g_NAME?&gt;&lt;br&gt;
	* &lt;?
	* endwhile;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostinggetgrouplist.php
	* @author Bitrix
	*/
	public static function GetGroupList($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT
				G.ID
				,G.NAME
			FROM
				b_group G
				,b_posting_group PG
			WHERE
				G.ID=PG.GROUP_ID
				AND PG.POSTING_ID=".$ID."
			ORDER BY
				G.C_SORT, G.ID
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	// delete by ID
	
	/**
	* <p>Метод удаляет выпуск по его идентификатору. Метод статический.</p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и  ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @return mixed <p>В случае успешного удаления возвращается результат типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. В противном
	* случает возвращается false.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $res = <b>CPosting::Delete</b>($ID);
	* if(!$res)
	*     echo "Cannot delete the issue.";
	* elseif($res-&gt;AffectedRowsCount() &lt; 1)
	*     echo "Already deleted.";
	* else
	*     echo "Deleted successfylly.";
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingdelete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);

		$DB->StartTransaction();

		CPosting::DeleteFile($ID);

		$res = $DB->Query("DELETE FROM b_posting_rubric WHERE POSTING_ID='".$ID."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res)
			$res = $DB->Query("DELETE FROM b_posting_group WHERE POSTING_ID='".$ID."' ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res)
			$res = $DB->Query("DELETE FROM b_posting_email WHERE POSTING_ID='".$ID."' ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if($res)
			$res = $DB->Query("DELETE FROM b_posting WHERE ID='".$ID."' ", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if($res)
			$DB->Commit();
		else
			$DB->Rollback();

		return $res;
	}

	public static function OnGroupDelete($group_id)
	{
		global $DB;
		$group_id = intval($group_id);

		return $DB->Query("DELETE FROM b_posting_group WHERE GROUP_ID=".$group_id, true);
	}

	
	/**
	* <p>Метод удаляет одно или все вложения выпуска. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param int $file_id = false Идентификатор вложения. Если параметр не указан или равен false, то
	* удаляются все вложения выпуска.
	*
	* @return mixed <p>Нет.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* if(is_array($FILE_ID))
	*         foreach($FILE_ID as $file_id)
	*             <b>CPosting::DeleteFile</b>($ID, $file_id);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingdeletefile.php
	* @author Bitrix
	*/
	public static function DeleteFile($ID, $file_id=false)
	{
		global $DB;

		$rsFile = CPosting::GetFileList($ID, $file_id);
		while($arFile = $rsFile->Fetch())
		{
			$DB->Query("DELETE FROM b_posting_file where POSTING_ID=".intval($ID)." AND FILE_ID=".intval($arFile["ID"]), false, "File: ".__FILE__."<br>Line: ".__LINE__);
			CFile::Delete(intval($arFile["ID"]));
		}
	}

	public static function SplitFileName($file_name)
	{
		$found = array();
		// exapmle(2).txt
		if(preg_match("/^(.*)\\((\\d+?)\\)(\\..+?)$/", $file_name, $found))
		{
			$fname = $found[1];
			$fext = $found[3];
			$index = $found[2];
		}
		// example(2)
		elseif(preg_match("/^(.*)\\((\\d+?)\\)$/", $file_name, $found))
		{
			$fname = $found[1];
			$fext = "";
			$index = $found[2];
		}
		// example.txt
		elseif(preg_match("/^(.*)(\\..+?)$/", $file_name, $found))
		{
			$fname = $found[1];
			$fext = $found[2];
			$index = 0;
		}
		// example
		else
		{
			$fname = $file_name;
			$fext = "";
			$index = 0;
		}
		return array($fname, $fext, $index);
	}

	
	/**
	* <p>Метод добавляет вложение к выпуску сохраняя и регистрируя его в таблице файлов (b_file). Метод нестатический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param array $file  Массив с данными файла формата:<br><pre bgcolor="#323232" style="padding:5px;">Array(     "name" =&gt; "название файла",
	*     "size" =&gt; "размер",     "tmp_name" =&gt; "временный путь на сервере",     "type"
	* =&gt; "тип загружаемого файла");</pre> Массив такого вида может быть
	* взят прямо из $_FILES[имя поля]
	*
	* @return bool <p>В случае успешного сохранения вложения возвращается ID
	* зарегистрированного файла. В противном случает возвращается false,
	* и переменная класса LAST_ERROR содержит сообщение об ошибке.</p><a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* if (strlen($save)&gt;0 &amp;&amp; $REQUEST_METHOD=="POST")
	* {
	*     $cPosting=new CPosting;
	* $file_id = $cPosting-&gt;SaveFile($ID, $_FILES["FILE_TO_ATTACH"]);
	* if($file_id===false)
	*     strError .= "Ошибка при сохранении вложения."."<br>";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingsavefile.php
	* @author Bitrix
	*/
	public function SaveFile($ID, $file)
	{
		global $DB, $APPLICATION;
		$ID = intval($ID);
		$filesSize = 0;

		$arFileName = CPosting::SplitFileName($file["name"]);
		//Check if file with this name already exists
		$arSameNames = array();
		$rsFile = CPosting::GetFileList($ID);
		while($arFile = $rsFile->Fetch())
		{
			$filesSize += $arFile["FILE_SIZE"];
			$arSavedName = CPosting::SplitFileName($arFile["ORIGINAL_NAME"]);
			if($arFileName[0] == $arSavedName[0] && $arFileName[1] == $arSavedName[1])
				$arSameNames[$arSavedName[2]] = true;
		}

		$max_files_size = COption::GetOptionString("subscribe", "max_files_size") * 1024 *1024;
		if ($max_files_size > 0)
		{
			$filesSize += $file["size"];
			if ($filesSize > $max_files_size)
			{
				$this->LAST_ERROR = GetMessage("class_post_err_files_size", array(
					"#MAX_FILES_SIZE#" => CFile::FormatSize($max_files_size),
				));
				$APPLICATION->ThrowException($this->LAST_ERROR);
				return false;
			}
		}

		while(array_key_exists($arFileName[2], $arSameNames))
		{
			$arFileName[2]++;
		}

		if($arFileName[2] > 0)
		{
			$file["name"] = $arFileName[0]."(".($arFileName[2]).")".$arFileName[1];
		}

		//save file
		$file["MODULE_ID"] = "subscribe";
		$fid = intval(CFile::SaveFile($file, "subscribe", true, true));
		if(($fid > 0) && $DB->Query("INSERT INTO b_posting_file (POSTING_ID, FILE_ID) VALUES (".$ID." ,".$fid.")", false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			return true;
		}
		else
		{
			$this->LAST_ERROR = GetMessage("class_post_err_att");
			$APPLICATION->ThrowException($this->LAST_ERROR);
			return false;
		}
	}

	
	/**
	* <p>Метод возвращает выборку вложений выпуска. Метод статический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param int $file_id = false Идентификатор вложения. Если параметр не указан или равен false, то
	* выбираются все вложения выпуска.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/index.php">поля объекта "Файл"</a>: ID,
	* FILE_SIZE, ORIGINAL_NAME, SUBDIR, FILE_NAME, CONTENT_TYPE.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //размер всех вложений
	* $attach_size = 0;
	* $rsFile = <b>CPosting::GetFileList</b>($ID);
	* while($arFile = $rsFile-&gt;Fetch())
	*     $attach_size += $arFile["FILE_SIZE"];
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostinggetfilelist.php
	* @author Bitrix
	*/
	public static function GetFileList($ID, $file_id=false)
	{
		global $DB;
		$ID = intval($ID);
		$file_id = intval($file_id);

		$strSql = "
			SELECT
				F.ID
				,F.FILE_SIZE
				,F.ORIGINAL_NAME
				,F.SUBDIR
				,F.FILE_NAME
				,F.CONTENT_TYPE
				,F.HANDLER_ID
			FROM
				b_file F
				,b_posting_file PF
			WHERE
				F.ID=PF.FILE_ID
				AND PF.POSTING_ID=".$ID."
			".($file_id>0?"AND PF.FILE_ID = ".$file_id:"")."
			ORDER BY F.ID
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	//check fields before writing
	public function CheckFields($arFields, $ID)
	{
		/** @global CDatabase $DB */
		global $DB;
		/** @global  CMain $APPLICATION */
		global $APPLICATION;

		$this->LAST_ERROR = "";
		$aMsg = array();

		if(array_key_exists("FROM_FIELD", $arFields))
		{
			if(strlen($arFields["FROM_FIELD"])<3 || !check_email($arFields["FROM_FIELD"]))
				$aMsg[] = array("id"=>"FROM_FIELD", "text"=>GetMessage("class_post_err_email"));
		}

		if(!array_key_exists("DIRECT_SEND", $arFields) || $arFields["DIRECT_SEND"]=="N")
		{
			if(array_key_exists("TO_FIELD", $arFields) && strlen($arFields["TO_FIELD"])<=0)
				$aMsg[] = array("id"=>"TO_FIELD", "text"=>GetMessage("class_post_err_to"));
		}

		if(array_key_exists("SUBJECT", $arFields))
		{
			if(strlen($arFields["SUBJECT"])<=0)
				$aMsg[] = array("id"=>"SUBJECT", "text"=>GetMessage("class_post_err_subj"));
		}

		if(array_key_exists("BODY", $arFields))
		{
			if(strlen($arFields["BODY"])<=0)
				$aMsg[] = array("id"=>"BODY", "text"=>GetMessage("class_post_err_text"));
		}

		if(array_key_exists("AUTO_SEND_TIME", $arFields) && $arFields["AUTO_SEND_TIME"]!==false)
		{
			if($DB->IsDate($arFields["AUTO_SEND_TIME"], false, false, "FULL")!==true)
				$aMsg[] = array("id"=>"AUTO_SEND_TIME", "text"=>GetMessage("class_post_err_auto_time"));
		}

		if(array_key_exists("CHARSET", $arFields))
		{
			$sCharset = COption::GetOptionString("subscribe", "posting_charset");
			$aCharset = explode(",", ToLower($sCharset));
			if (!in_array(ToLower($arFields["CHARSET"]), $aCharset))
			{
				$aMsg[] = array("id"=>"CHARSET", "text"=>GetMessage("class_post_err_charset"));
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}

		return true;
	}

	//relation with categories
	
	/**
	* <p>Метод изменяет информацию о привязке выпуска к рубрикам подписки. Метод нестатический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param array $aRubric  Массив идентификаторов рассылок.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $posting = new CPosting;
	* $posting-&gt;<b>UpdateRubrics</b>($ID, array(1, 2, 3));
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingupdaterubrics.php
	* @author Bitrix
	*/
	public static function UpdateRubrics($ID, $aRubric)
	{
		global $DB;
		$ID = intval($ID);

		$DB->Query("DELETE FROM b_posting_rubric WHERE POSTING_ID=".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arID = array();
		if(is_array($aRubric))
			foreach($aRubric as $i)
				$arID[] = intval($i);
		if(count($arID)>0)
			$DB->Query("
				INSERT INTO b_posting_rubric (POSTING_ID, LIST_RUBRIC_ID)
				SELECT ".$ID.", ID
				FROM b_list_rubric
				WHERE ID IN (".implode(", ",$arID).")
				", false, "File: ".__FILE__."<br>Line: ".__LINE__
			);
	}

	//relation with user groups
	
	/**
	* <p>Метод изменяет информацию о привязке выпуска к группам пользователей. Метод нестатический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param array $aGroup  Массив идентификаторов групп пользователей.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $posting = new CPosting;
	* $posting-&gt;<b>UpdateGroups</b>($ID, array(1));
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingupdategroups.php
	* @author Bitrix
	*/
	public static function UpdateGroups($ID, $aGroup)
	{
		global $DB;
		$ID = intval($ID);

		$DB->Query("DELETE FROM b_posting_group WHERE POSTING_ID='".$ID."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arID = array();
		if(is_array($aGroup))
			foreach($aGroup as $i)
				$arID[] = intval($i);
		if(count($arID)>0)
			$DB->Query("
				INSERT INTO b_posting_group (POSTING_ID, GROUP_ID)
				SELECT ".$ID.", ID
				FROM b_group
				WHERE ID IN (".implode(", ",$arID).")
				", false, "File: ".__FILE__."<br>Line: ".__LINE__
			);
	}

	//Addition
	
	/**
	* <p>Метод добавляет выпуск. Метод нестатический.</p>
	*
	*
	* @param array $arFields  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingfields.php">полей объекта
	* "Выпуск"</a><br> 	Дополнительно могут быть указаны поля:<br> 	RUB_ID -
	* массив идентификаторов рассылок;<br> 	GROUP_ID - массив
	* идентификаторов групп пользователей.
	*
	* @return int <p>В случае успешного добавления возвращается ID выпуска. В
	* противном случает возвращается false, и переменная класса LAST_ERROR
	* содержит сообщение об ошибке (так же возбуждается исключение <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a>).</p><p>При
	* указании статуса в поле <b>STATUS</b> следует учитывать, что при
	* переводе из одного статуса в другой могут выполняться
	* определенные действия. Так, например, при переводе из статуса
	* "Черновик" ("D") в статус "В процессе" ("P") формируется список адресов,
	* по которым будет происходить отправка. А именно, адреса, на
	* которые требуется отправить выпуск, попадают в таблицу
	* <b>b_posting_email</b> со статусом "Y". Если при добавлении выпуска сразу
	* указать статус "В процессе" ("Р"), то процесс добавления адресов в
	* таблицу <b>b_posting_email</b> не произойдет, и выпуск никому не отправится.
	* При этом статус выпуска сменится на "S" (отправлен успешно).
	* Выпуски хранятся в таблице <b>b_posting</b>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $posting = new CPosting;
	* $arFields = Array(
	*     "FROM_FIELD" =&gt; $FROM_FIELD,
	*     "TO_FIELD" =&gt; $TO_FIELD,
	*     "BCC_FIELD" =&gt; $BCC_FIELD,
	*     "EMAIL_FILTER" =&gt; $EMAIL_FILTER,
	*     "SUBJECT" =&gt; $SUBJECT,
	*     "BODY_TYPE" =&gt; ($BODY_TYPE &lt;&gt; "html"? "text":"html"),
	*     "BODY" =&gt; $BODY,
	*     "DIRECT_SEND" =&gt; ($DIRECT_SEND &lt;&gt; "Y"? "N":"Y"),
	*     "CHARSET" =&gt; $CHARSET,
	*     "SUBSCR_FORMAT" =&gt; ($SUBSCR_FORMAT&lt;&gt;"html" &amp;&amp; $SUBSCR_FORMAT&lt;&gt;"text"?
	*         false:$SUBSCR_FORMAT),
	*     "RUB_ID" =&gt; $RUB_ID,
	*     "GROUP_ID" =&gt; $GROUP_ID
	* );
	* if($STATUS &lt;&gt; "")
	* {
	*     if($STATUS&lt;&gt;"S" &amp;&amp; $STATUS&lt;&gt;"E" &amp;&amp; $STATUS&lt;&gt;"P")
	*         $STATUS = "D";
	*     $arFields["STATUS"] = $STATUS;
	*     if($STATUS == "D")
	*     {
	*         $arFields["DATE_SENT"] = false;
	*         $arFields["SENT_BCC"] = "";
	*         $arFields["ERROR_EMAIL"] = "";
	*     }
	* }
	* $ID = <b>$posting-&gt;Add</b>($arFields);
	* if($ID == false)
	*     echo $posting-&gt;LAST_ERROR;
	* 
	* // Полностью схема генерации выпуска из скрипта выглядит так:
	*     $cPosting = new CPosting;
	*     $ID = $cPosting-&gt;Add($arFields);
	*     if($ID)
	*     {
	*     $cPosting-&gt;ChangeStatus($ID, "P");
	*     $cPosting-&gt;AutoSend($ID);
	*     }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingadd.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields($arFields, 0))
			return false;

		if(!array_key_exists("MSG_CHARSET", $arFields))
			$arFields["MSG_CHARSET"] = LANG_CHARSET;
		$arFields["VERSION"] = '2';

		$ID = $DB->Add("b_posting", $arFields, Array("BCC_FIELD","BODY"));
		if($ID > 0)
		{
			$this->UpdateRubrics($ID, $arFields["RUB_ID"]);
			$this->UpdateGroups($ID, $arFields["GROUP_ID"]);
		}
		return $ID;
	}

	//Update
	
	/**
	* <p>Метод изменяет информацию о выпуске по его идентификатору. Метод нестатический.</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param array $arFields  Массив со значениями <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingfields.php">полей объекта
	* "Выпуск"</a>. 	Дополнительно могут быть указаны поля:<br> 	RUB_ID - массив
	* идентификаторов рассылок;<br> 	GROUP_ID - массив идентификаторов групп
	* пользователей.
	*
	* @return bool <p>В случае успешного изменения возвращается true. В противном
	* случает возвращается false, и переменная класса LAST_ERROR содержит
	* сообщение об ошибке.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $posting = new CPosting;
	* $arFields = Array(
	*     "FROM_FIELD" =&gt; $FROM_FIELD,
	*     "TO_FIELD" =&gt; $TO_FIELD,
	*     "BCC_FIELD" =&gt; $BCC_FIELD,
	*     "EMAIL_FILTER" =&gt; $EMAIL_FILTER,
	*     "SUBJECT" =&gt; $SUBJECT,
	*     "BODY_TYPE" =&gt; ($BODY_TYPE &lt;&gt; "html"? "text":"html"),
	*     "BODY" =&gt; $BODY,
	*     "DIRECT_SEND" =&gt; ($DIRECT_SEND &lt;&gt; "Y"? "N":"Y"),
	*     "CHARSET" =&gt; $CHARSET,
	*     "SUBSCR_FORMAT" =&gt; ($SUBSCR_FORMAT&lt;&gt;"html" &amp;&amp; $SUBSCR_FORMAT&lt;&gt;"text"? false:$SUBSCR_FORMAT),
	*     "RUB_ID" =&gt; $RUB_ID,
	*     "GROUP_ID" =&gt; $GROUP_ID
	* );
	* if($STATUS &lt;&gt; "")
	* {
	*     if($STATUS&lt;&gt;"S" &amp;&amp; $STATUS&lt;&gt;"E" &amp;&amp; $STATUS&lt;&gt;"P")
	*         $STATUS = "D";
	*     $arFields["STATUS"] = $STATUS;
	*     if($STATUS == "D")
	*     {
	*         $arFields["DATE_SENT"] = false;
	*         $arFields["SENT_BCC"] = "";
	*         $arFields["ERROR_EMAIL"] = "";
	*     }
	* }
	* if(!<b>$posting-&gt;Update</b>($ID, $arFields))
	*     $strError = $posting-&gt;LAST_ERROR;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingupdate.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if(!$this->CheckFields($arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_posting", $arFields);
		if($strUpdate!="")
		{
			$strSql = "UPDATE b_posting SET ".$strUpdate." WHERE ID=".$ID;
			$arBinds = array(
				"BCC_FIELD" => $arFields["BCC_FIELD"],
				//"SENT_BCC" => $arFields["SENT_BCC"],
				"BODY" => $arFields["BODY"],
				//"ERROR_EMAIL" => $arFields["ERROR_EMAIL"],
				//"BCC_TO_SEND" => $arFields["BCC_TO_SEND"],
			);
			if(!$DB->QueryBind($strSql, $arBinds))
				return false;
		}
		if(is_set($arFields, "RUB_ID"))
			$this->UpdateRubrics($ID, $arFields["RUB_ID"]);
		if(is_set($arFields, "GROUP_ID"))
			$this->UpdateGroups($ID, $arFields["GROUP_ID"]);

		return true;
	}

	
	/**
	* <p>Метод возвращает массив адресов, по которым выпуск будет отправлен. Метод нестатический.</p>
	*
	*
	* @param array $post_arr  Массив всех <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingfields.php">полей объекта
	* "Выпуск"</a> в виде наборов "название поля" =&gt; "значение".
	*
	* @return array <p>Возвращает массив уникальных e-mail адресов. В который входят</p><ul>
	* <li>Адреса подписчиков из рубрик выпуска, подписка которых активна
	* и подтверждена с учетом формата подписки и фильтра адресов.</li>
	* <li>Адреса зарегистрированных и активных пользователей
	* принадлежащих тем группам к которым привязан выпуск.</li> <li>Адреса
	* перечисленные в поле BCC_FIELD.</li> </ul><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $post = CPosting::GetByID($ID);
	* if(($post_arr = $post-&gt;Fetch()))
	*     $aEmail = <b>CPosting::GetEmails</b>($post_arr);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostinggetemails.php
	* @author Bitrix
	*/
	public static function GetEmails($post_arr)
	{
		$aEmail = array();

		//send to categories
		$aPostRub = array();
		$post_rub = CPostingGeneral::GetRubricList($post_arr["ID"]);
		while($post_rub_arr = $post_rub->Fetch())
			$aPostRub[] = $post_rub_arr["ID"];

		$subscr = CSubscription::GetList(
			array("ID"=>"ASC"),
			array("RUBRIC_MULTI"=>$aPostRub, "CONFIRMED"=>"Y", "ACTIVE"=>"Y",
				"FORMAT"=>$post_arr["SUBSCR_FORMAT"], "EMAIL"=>$post_arr["EMAIL_FILTER"])
		);
		while(($subscr_arr = $subscr->Fetch()))
			$aEmail[] = $subscr_arr["EMAIL"];

		//send to user groups
		$aPostGrp = array();
		$post_grp = CPostingGeneral::GetGroupList($post_arr["ID"]);
		while($post_grp_arr = $post_grp->Fetch())
			$aPostGrp[] = $post_grp_arr["ID"];

		if(count($aPostGrp)>0)
		{
			$user = CUser::GetList(
				($b="id"), ($o="asc"),
				array("GROUP_MULTI"=>$aPostGrp, "ACTIVE"=>"Y", "EMAIL"=>$post_arr["EMAIL_FILTER"])
			);
			while(($user_arr = $user->Fetch()))
				$aEmail[] = $user_arr["EMAIL"];
		}

		//from additional emails
		$BCC = $post_arr["BCC_FIELD"];
		if($post_arr["DIRECT_SEND"] == "Y")
			$BCC .= ($BCC <> ""? ",":"").$post_arr["TO_FIELD"];
		if($BCC <> "")
		{
			$BCC = str_replace("\r\n", "\n", $BCC);
			$BCC = str_replace("\n", ",", $BCC);
			$aBcc = explode(",", $BCC);
			foreach ($aBcc as $bccEmail)
			{
				$bccEmail = trim($bccEmail);
				if($bccEmail <> "")
					$aEmail[] = $bccEmail;
			}
		}

		$aEmail = array_unique($aEmail);

		return $aEmail;
	}

	
	/**
	* <p>Метод для отправки выпуска с помощью cron'а или агента. Метод статический.</p>
	*
	*
	* @param mixed $intID = false Идентификатор выпуска. Если параметр не указан или равен false, то
	* будут отсылаться все выпуски в статусе "В процессе" время
	* отправки которых меньше или равно текущему. В порядке
	* возрастания времени отправки.
	*
	* @param bool $limit = false Флажок ограничения количества отправляемых писем за один вызов.
	* Если этот параметр указан и равен true количество писем
	* отправляемых за один вызов данной функции ограничивается
	* параметром "Количество писем для автоматической рассылки
	* агентом за один запуск" в настройках модуля. При отправке выпуска
	* с помощью агента (задан параметр ID) дополнительно ограничивается
	* продолжительность отправки, которая определяется параметром в
	* настройках модуля.
	*
	* @param string $site_id = false Идентификатор сайта. Используется при отправке автоматически
	* сгенерированных выпусков с помощью агентов. Если этот параметр
	* указан, то его значение сравнивается с текущим значением
	* константы SITE_ID. Таким образом агент по отправке выполняется
	* только в контексте сайта к которому привязана рубрика породившая
	* выпуск. Это позволяет избежать проблемы пропуска картинок в html
	* выпусках в случае многосайтовости организованной по второму
	* варианту.
	*
	* @return mixed <p>Нет.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* #!/usr/bin/php
	* &lt;?php
	* //Здесь необходимо указать ваш DOCUMENT_ROOT!
	* $_SERVER["DOCUMENT_ROOT"] = "/opt/www/html";
	* $DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
	* define("NO_KEEP_STATISTIC", true);
	* define("NOT_CHECK_PERMISSIONS", true);
	* set_time_limit(0);
	* require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	* if (CModule::IncludeModule("subscribe"))
	* {
	*     $cPosting = new CPosting;
	*     $cPosting-&gt;<b>AutoSend</b>();
	* }
	* require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	* ?&gt;
	* 
	* &lt;?
	*     CAgent::AddAgent("CPosting::<b>AutoSend</b>(".$ID.",true);", "subscribe", "N", 0, $post_arr["AUTO_SEND_TIME"], "Y", $post_arr["AUTO_SEND_TIME"]);
	* ?&gt;
	* &lt;p class="notetext"&gt;Для отправки выпуска был создан агент.&lt;/p&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingautosend.php
	* @author Bitrix
	*/
	public static function AutoSend($ID=false, $limit=false, $site_id=false)
	{
		if($ID===false)
		{
			//Here is cron job entry
			$cPosting = new CPosting;
			$rsPosts = $cPosting->GetList(
				array("AUTO_SEND_TIME"=>"ASC", "ID"=>"ASC"),
				array("STATUS_ID"=>"P", "AUTO_SEND_TIME_2"=>ConvertTimeStamp(false, "FULL"))
			);
			while($arPosts=$rsPosts->Fetch())
			{
				if($limit===true)
				{
					$maxcount = COption::GetOptionInt("subscribe", "subscribe_max_emails_per_hit") - self::$current_emails_per_hit;
					if($maxcount <= 0)
						break;
				}
				else
				{
					$maxcount = 0;
				}
				$cPosting->SendMessage($arPosts["ID"], 0, $maxcount);
			}
		}
		else
		{
			if($site_id && $site_id != SITE_ID)
			{
				return "CPosting::AutoSend(".$ID.($limit? ",true": ",false").",\"".$site_id."\");";
			}

			//Here is agent entry
			if($limit===true)
			{
				$maxcount = COption::GetOptionInt("subscribe", "subscribe_max_emails_per_hit") - self::$current_emails_per_hit;
				if($maxcount <= 0)
					return "CPosting::AutoSend(".$ID.",true".($site_id? ",\"".$site_id."\"": "").");";
			}
			else
			{
				$maxcount = 0;
			}

			$cPosting = new CPosting;
			$res = $cPosting->SendMessage($ID, COption::GetOptionString("subscribe", "posting_interval"), $maxcount, true);
			if($res == "CONTINUE")
				return "CPosting::AutoSend(".$ID.($limit? ",true": ",false").($site_id?",\"".$site_id."\"":"").");";
		}
		return "";
	}

	//Send message
	
	/**
	* <p>Нестатический метод отправляет выпуск в почтовую рассылку по адресам, указанным в таблице <b>b_posting_email</b> с соответствующим идентификатором выпуска. При этом обновляя статусы:  </p> <ul> <li>Y - еще не отправлялось </li> <li>N - отправлено </li> <li>Е - с ошибками</li> </ul>    Выпуски со статусом "Успешно отправлен" повторно не отправляются. Если у выпуска установлен статус "Частично отправлен", то выпуск отправляется по оставшимся адресам.   <p>Сначала выполняется попытка получения блокировки выпуска (см. <a href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostinglock.php">CPosting::Lock</a>). Если блокировку получить не удалось, то отправка считается частичной и возвращается "CONTINUE". Затем формируется тело письма для отправки и в цикле по адресам подписчиков осуществляется отправка выпуска с использованием функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxmail.php">bxmail</a>.</p>   <p>В режиме отправки "Персонально каждому получателю" перед вызовом <a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxmail.php">bxmail</a> вызываются обработчики события BeforePostingSendMail. </p>   <p>В цикле отправки в очереди адресов делаются отметки об успешной отправке или ошибке.</p>   <p>С выпуска снимается блокировка(см. <a href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostingunlock.php">CPosting::UnLock</a>).</p>
	*
	*
	* @param mixed $intID  Идентификатор выпуска.
	*
	* @param int $timeout = 0 Максимальное время отправки в секундах. При превышении этого
	* времени прерывается работа 	и устанавливается статус выпуска
	* "Частично отправлен". Параметр имеет значение только при методе
	* 	отправки "Персонально каждому получателю". 	Если timeout=0, то
	* отправка производится за один шаг.
	*
	* @param int $maxcount = 0 Максимальное количество писем для отправки. При превышении этого
	* количества прерывается работа 	и устанавливается статус выпуска
	* "Частично отправлен". Параметр имеет значение только при методе
	* 	отправки "Персонально каждому получателю". 	Если maxcount=0, то
	* отправка производится за один шаг.
	*
	* @return mixed <p>Функция возвращает true при успешной отправке, false при неуспешной,
	* "CONTINUE" при частичной отправке. При неуспешной отправке переменная
	* LAST_ERROR класса содержит сообщение об ошибке.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>if($action=="send" &amp;&amp; $ID&gt;0):<br>    if(($res = <b>$cPosting-&gt;SendMessage</b>($ID, COption::GetOptionString("subscribe", "posting_interval"))) !== false):<br>        if($res === "CONTINUE"):<br>?&gt;<br>&lt;script language="JavaScript" type="text/javascript"&gt;<br>&lt;!--<br>function DoNext(){window.location="&lt;?echo $APPLICATION-&gt;GetCurPage()."?ID=".$ID."&amp;action=send&amp;lang=".LANG."&amp;rnd=".rand();?&gt;";}<br>setTimeout('DoNext()', 2500);<br>//--&gt;<br>&lt;/script&gt;<br>&lt;?<br>        else:<br>            $strOk = "Sent successfully.";<br>        endif; //$res === "CONTINUE"<br>    endif; //$cPosting-&gt;SendMessage<br>    $strError .= $cPosting-&gt;LAST_ERROR;<br>endif; //$action=="send"<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxmail.php">bxmail</a></li><br><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingsendmessage.php
	* @author Bitrix
	*/
	public function SendMessage($ID, $timeout=0, $maxcount=0, $check_charset=false)
	{
		global $DB, $APPLICATION;

		$eol = \Bitrix\Main\Mail\Mail::getMailEol();
		$ID = intval($ID);
		$timeout = intval($timeout);
		$start_time = getmicrotime();

		@set_time_limit(0);
		$this->LAST_ERROR = "";

		$post = $this->GetByID($ID);
		if(!($post_arr = $post->Fetch()))
		{
			$this->LAST_ERROR .= GetMessage("class_post_err_notfound");
			return false;
		}

		if($post_arr["STATUS"] != "P")
		{
			$this->LAST_ERROR .= GetMessage("class_post_err_status")."<br>";
			return false;
		}

		if(
			$check_charset
			&& (strlen($post_arr["MSG_CHARSET"]) > 0)
			&& (strtoupper($post_arr["MSG_CHARSET"]) != strtoupper(LANG_CHARSET))
		)
		{
			return "CONTINUE";
		}

		if(CPosting::Lock($ID)===false)
		{
			if($e = $APPLICATION->GetException())
			{
				$this->LAST_ERROR .= GetMessage("class_post_err_lock")."<br>".$e->GetString();
				if(strpos($this->LAST_ERROR, "PLS-00201") !== false && strpos($this->LAST_ERROR, "'DBMS_LOCK'") !== false)
					$this->LAST_ERROR .= "<br>".GetMessage("class_post_err_lock_advice");
				$APPLICATION->ResetException();
				return false;
			}
			else
			{
				return "CONTINUE";
			}
		}

		if($post_arr["VERSION"] <> '2')
		{
			if(is_string($post_arr["BCC_TO_SEND"]) && strlen($post_arr["BCC_TO_SEND"])>0)
			{
				$a =  explode(",", $post_arr["BCC_TO_SEND"]);
				foreach($a as $e)
					$DB->Query("INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL) VALUES (".$ID.", 'Y', '".$DB->ForSQL($e)."')");
			}

			if(is_string($post_arr["ERROR_EMAIL"]) && strlen($post_arr["ERROR_EMAIL"])>0)
			{
				$a =  explode(",", $post_arr["ERROR_EMAIL"]);
				foreach($a as $e)
					$DB->Query("INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL) VALUES (".$ID.", 'E', '".$DB->ForSQL($e)."')");
			}

			if(is_string($post_arr["SENT_BCC"]) && strlen($post_arr["SENT_BCC"])>0)
			{
				$a =  explode(",", $post_arr["SENT_BCC"]);
				foreach($a as $e)
					$DB->Query("INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL) VALUES (".$ID.", 'N', '".$DB->ForSQL($e)."')");
			}

			$DB->Query("UPDATE b_posting SET VERSION='2', BCC_TO_SEND=null, ERROR_EMAIL=null, SENT_BCC=null WHERE ID=".$ID);
		}

		$tools = new CMailTools;
		//MIME with attachments
		if($post_arr["BODY_TYPE"]=="html" && COption::GetOptionString("subscribe", "attach_images")=="Y")
		{
			$post_arr["BODY"] = $tools->ReplaceImages($post_arr["BODY"]);
		}

		if(strlen($post_arr["CHARSET"]) > 0)
		{
			$from_charset = $post_arr["MSG_CHARSET"]? $post_arr["MSG_CHARSET"]: SITE_CHARSET;
			$post_arr["BODY"] = $APPLICATION->ConvertCharset($post_arr["BODY"], $from_charset, $post_arr["CHARSET"]);
			$post_arr["SUBJECT"] = $APPLICATION->ConvertCharset($post_arr["SUBJECT"], $from_charset, $post_arr["CHARSET"]);
			$post_arr["FROM_FIELD"] = $APPLICATION->ConvertCharset($post_arr["FROM_FIELD"], $from_charset, $post_arr["CHARSET"]);
		}

		//Preparing message header, text, subject
		$sBody = str_replace("\r\n", "\n", $post_arr["BODY"]);
		$sBody = implode(
			"\n",
			array_filter(
				preg_split("/(.{512}[^ ]*[ ])/", $sBody." ", -1, PREG_SPLIT_DELIM_CAPTURE)
			)
		); //Some MTA has 4K limit for fgets function. So we have to split the message body.
		if(COption::GetOptionString("main", "CONVERT_UNIX_NEWLINE_2_WINDOWS", "N") == "Y")
			$sBody = str_replace("\n", "\r\n", $sBody);

		if(COption::GetOptionString("subscribe", "allow_8bit_chars") <> "Y")
		{
			$sSubject = CMailTools::EncodeSubject($post_arr["SUBJECT"], $post_arr["CHARSET"]);
			$sFrom = CMailTools::EncodeHeaderFrom($post_arr["FROM_FIELD"], $post_arr["CHARSET"]);
		}
		else
		{
			$sSubject = $post_arr["SUBJECT"];
			$sFrom = $post_arr["FROM_FIELD"];
		}

		if($post_arr["BODY_TYPE"] == "html")
		{
			//URN2URI
			$tmpTools = new CMailTools;
			$sBody = $tmpTools->ReplaceHrefs($sBody);
		}

		$bHasAttachments = false;
		$sHeader = "";
		$sBoundary = "";

		if(count($tools->aMatches) > 0)
		{
			$bHasAttachments = true;

			$sBoundary = "----------".uniqid("");
			$sHeader =
				'From: '.$sFrom.$eol.
				'X-Bitrix-Posting: '.$post_arr["ID"].$eol.
				'MIME-Version: 1.0'.$eol.
				'Content-Type: multipart/mixed; boundary="'.$sBoundary.'"'.$eol.
				'Content-Transfer-Encoding: 8bit';

			$sBody =
				"--".$sBoundary.$eol.
				"Content-Type: ".($post_arr["BODY_TYPE"]=="html"? "text/html":"text/plain").($post_arr["CHARSET"]<>""? "; charset=".$post_arr["CHARSET"]:"").$eol.
				"Content-Transfer-Encoding: 8bit".$eol.$eol.
				$sBody.$eol;

			foreach($tools->aMatches as $attachment)
			{
				if(strlen($post_arr["CHARSET"]) > 0)
				{
					$from_charset = $post_arr["MSG_CHARSET"]? $post_arr["MSG_CHARSET"]: SITE_CHARSET;
					$attachment["DEST"] = $APPLICATION->ConvertCharset($attachment["DEST"], $from_charset, $post_arr["CHARSET"]);
				}

				if(COption::GetOptionString("subscribe", "allow_8bit_chars") <> "Y")
					$name = CMailTools::EncodeSubject($attachment["DEST"], $post_arr["CHARSET"]);
				else
					$name = $attachment["DEST"];

				$sBody .=
					$eol."--".$sBoundary.$eol.
					"Content-Type: ".$attachment["CONTENT_TYPE"]."; name=\"".$name."\"".$eol.
					"Content-Transfer-Encoding: base64".$eol.
					"Content-ID: <".$attachment["ID"].">".$eol.$eol.
					chunk_split(
						base64_encode(
							file_get_contents($attachment["PATH"])
						), 72, $eol
					);
			}
		}

		$arFiles = array();
		$maxFileSize = intval(COption::GetOptionInt("subscribe", "max_file_size"));
		$rsFile = CPosting::GetFileList($ID);
		while($arFile = $rsFile->Fetch())
		{
			if (
				$maxFileSize == 0
				|| $arFile["FILE_SIZE"] <= $maxFileSize
			)
				$arFiles[] = $arFile;
		}

		if(!empty($arFiles))
		{
			if(!$bHasAttachments)
			{
				$bHasAttachments = true;
				$sBoundary = "----------".uniqid("");
				$sHeader =
					"From: ".$sFrom.$eol.
					'X-Bitrix-Posting: '.$post_arr["ID"].$eol.
					"MIME-Version: 1.0".$eol.
					"Content-Type: multipart/mixed; boundary=\"".$sBoundary."\"".$eol.
					"Content-Transfer-Encoding: 8bit";

				$sBody =
					"--".$sBoundary.$eol.
					"Content-Type: ".($post_arr["BODY_TYPE"]=="html"? "text/html":"text/plain").($post_arr["CHARSET"]<>""? "; charset=".$post_arr["CHARSET"]:"").$eol.
					"Content-Transfer-Encoding: 8bit".$eol.$eol.
					$sBody.$eol;
			}

			foreach ($arFiles as $arFile)
			{
				if(strlen($post_arr["CHARSET"]) > 0)
				{
					$from_charset = $post_arr["MSG_CHARSET"]? $post_arr["MSG_CHARSET"]: SITE_CHARSET;
					$file_name = $APPLICATION->ConvertCharset($arFile["ORIGINAL_NAME"], $from_charset, $post_arr["CHARSET"]);
				}
				else
				{
					$file_name = $arFile["ORIGINAL_NAME"];
				}

				$sBody .=
					$eol."--".$sBoundary.$eol.
					"Content-Type: ".$arFile["CONTENT_TYPE"]."; name=\"".$file_name."\"".$eol.
					"Content-Transfer-Encoding: base64".$eol.
					"Content-Disposition: attachment; filename=\"".CMailTools::EncodeHeaderFrom($file_name, $post_arr["CHARSET"])."\"".$eol.$eol;

				$arTempFile = CFile::MakeFileArray($arFile["ID"]);
				$sBody .= chunk_split(
					base64_encode(
						file_get_contents($arTempFile["tmp_name"])
					),
					72,
					$eol
				);
			}
		}

		if($bHasAttachments)
		{
			$sBody .= $eol."--".$sBoundary."--".$eol;
		}
		else
		{
			//plain message without MIME
			$sHeader =
				"From: ".$sFrom.$eol.
				'X-Bitrix-Posting: '.$post_arr["ID"].$eol.
				"MIME-Version: 1.0".$eol.
				"Content-Type: ".($post_arr["BODY_TYPE"]=="html"? "text/html":"text/plain").($post_arr["CHARSET"]<>""? "; charset=".$post_arr["CHARSET"]:"").$eol.
				"Content-Transfer-Encoding: 8bit";
		}

		$mail_additional_parameters = trim(COption::GetOptionString("subscribe", "mail_additional_parameters"));
		if($post_arr["DIRECT_SEND"] == "Y")
		{
			//personal delivery
			$arEvents = GetModuleEvents("subscribe", "BeforePostingSendMail", true);

			$rsEmails = $DB->Query($DB->TopSql("
				SELECT *
				FROM b_posting_email
				WHERE POSTING_ID = ".$ID." AND STATUS='Y'
			", $maxcount));

			while($arEmail = $rsEmails->Fetch())
			{
				//Event part
				$arFields = array(
					"POSTING_ID" => $ID,
					"EMAIL" => $arEmail["EMAIL"],
					"SUBJECT" => $sSubject,
					"BODY" => $sBody,
					"HEADER" => $sHeader,
					"EMAIL_EX" => $arEmail,
				);
				foreach($arEvents as $arEvent)
					$arFields = ExecuteModuleEventEx($arEvent, array($arFields));
				//Sending

				if(is_array($arFields))
				{
					$to = CMailTools::EncodeHeaderFrom($arFields["EMAIL"], $post_arr["CHARSET"]);
					$result = bxmail($to, $arFields["SUBJECT"], $arFields["BODY"], $arFields["HEADER"], $mail_additional_parameters);
				}
				else
				{
					$result = $arFields !== false;
				}

				//Result check and iteration
				if($result)
					$DB->Query("UPDATE b_posting_email SET STATUS='N' WHERE ID = ".$arEmail["ID"]);
				else
					$DB->Query("UPDATE b_posting_email SET STATUS='E' WHERE ID = ".$arEmail["ID"]);

				if($timeout > 0 && getmicrotime()-$start_time >= $timeout)
					break;

				self::$current_emails_per_hit++;
			}
		}
		else
		{
			//BCC delivery
			$rsEmails = $DB->Query($DB->TopSql("
				SELECT *
				FROM b_posting_email
				WHERE POSTING_ID = ".$ID." AND STATUS='Y'
			", COption::GetOptionString("subscribe", "max_bcc_count")));

			$aStep = array();
			while($arEmail = $rsEmails->Fetch())
				$aStep[$arEmail["ID"]] = $arEmail["EMAIL"];

			if(count($aStep) > 0)
			{
				$BCC = implode(",", $aStep);
				$sHeaderStep = $sHeader.$eol."Bcc: ".$BCC;
				$result = bxmail($post_arr["TO_FIELD"], $sSubject, $sBody, $sHeaderStep, $mail_additional_parameters);
				if($result)
				{
					$DB->Query("UPDATE b_posting_email SET STATUS='N' WHERE ID in (".implode(", ", array_keys($aStep)).")");
				}
				else
				{
					$DB->Query("UPDATE b_posting_email SET STATUS='E' WHERE ID in (".implode(", ", array_keys($aStep)).")");
					$this->LAST_ERROR .= GetMessage("class_post_err_mail")."<br>";
				}
			}
		}

		//set status and delivered and error emails
		$arStatuses = $this->GetEmailStatuses($ID);
		if(!array_key_exists("Y", $arStatuses))
		{
			$STATUS = array_key_exists("E", $arStatuses)? "E": "S";
			$DATE = $DB->GetNowFunction();
		}
		else
		{
			$STATUS = "P";
			$DATE = "null";
		}

		CPosting::UnLock($ID);

		$DB->Query("UPDATE b_posting SET STATUS='".$STATUS."', DATE_SENT=".$DATE." WHERE ID=".$ID);

		return ($STATUS=="P"? "CONTINUE": true);
	}

	
	/**
	* <p>Метод возвращает массив статусов в очереди выпуска на отправку. Ключами массива являются статусы, а значениями количество подписчиков в соответствующих статусах. Метод статический.</p>
	*
	*
	* @param int $intID  Идентификатор выпуска
	*
	* @return array <p>Массив распределения получателей выпуска по статусам. Если
	* получатели в каком-то из статусов отсутствуют, то и
	* соответствующий элемента массива будет отсутствовать. 
	* </p><p>Допустимыми значениями ключей являются:</p><ul> <li>"N" - письмо
	* отправлено успешно;</li>     <li>"E" - отправлено с ошибкой;</li>     <li>"Y" -
	* ожидает отправки.</li>  </ul>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$arStatuses = CPosting::GetEmailStatuses($ID);<br>if(!isset($arStatuses["Y"]))<br>  echo "Выпуск отправлен.";<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingfields.php">Поля
	* CPosting</a> </li>  </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/GetEmailStatuses.php
	* @author Bitrix
	*/
	public static function GetEmailStatuses($ID)
	{
		global $DB;
		$arStatuses = array();
		$rs = $DB->Query("
			SELECT STATUS, COUNT(*) CNT
			FROM b_posting_email
			WHERE POSTING_ID = ".intval($ID)."
			GROUP BY STATUS
		");
		while($ar = $rs->Fetch())
			$arStatuses[$ar["STATUS"]] = $ar["CNT"];
		return $arStatuses;
	}

	
	/**
	* <p>Метод возвращает выборку из очереди на отправку. Метод статический.</p>
	*
	*
	* @param int $intID  Идентификатор выпуска
	*
	* @param char $STATUS  Статус получателя в очереди.          <p>Допустимыми значениями
	* являются:</p>                 <ul> <li>"N" письмо отправлено успешно;</li>               
	*      <li>"E" - отправлено с ошибкой;</li>                     <li>"Y" - ожидает
	* отправки.</li>          </ul>
	*
	* @return array <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a><a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/crubric/crubric.fields.php">поля объекта
	* "Очередь отправки"</a>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingfields.php">Поля
	* CPosting</a> </li>  </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/GetEmailsByStatus.php
	* @author Bitrix
	*/
	public static function GetEmailsByStatus($ID, $STATUS)
	{
		global $DB;

		return $DB->Query("
			SELECT *
			FROM b_posting_email
			WHERE POSTING_ID = ".intval($ID)."
			AND STATUS = '".$DB->ForSQL($STATUS)."'
			ORDER BY EMAIL
		");
	}

	
	/**
	* <p>Метод изменяет статус выпуска и производит действия в соответствии с приведенной ниже таблицей. Метод нестатический.</p>   <table width="100%" class="tnormal"><tbody> <tr> <th width="15%">Текущий статус</th> <th>Новый статус</th> <th>Действия</th> </tr> <tr> <td>Черновик</td> 	<td>В процессе</td> 	<td>Формируется список адресов по которым будет происходить отправка.</td> </tr> <tr> <td>В процессе</td> 	<td>Остановлен</td> 	<td>Нет.</td> </tr> <tr> <td>Остановлен</td> 	<td>В процессе</td> 	<td>Нет.</td> </tr> <tr> <td>В процессе</td> 	<td>Отправлен с ошибками</td> 	<td>Нет.</td> </tr> <tr> <td>В процессе</td> 	<td>Отправлен</td> 	<td>Нет.</td> </tr> <tr> <td>Отправлен с ошибками</td> 	<td>В процессе</td> 	<td>Адреса в очереди отправки помеченные как ошибочные помечаются на отправку.</td> </tr> <tr> <td>Отправлен с ошибками,         <br>       Отправлен,         <br>       Остановлен</td> 	<td>Черновик</td> 	<td>Очередь отправки очищается.</td> </tr> </tbody></table>
	*
	*
	* @param mixed $intID  В процессе
	*
	* @param string $status  Остановлен
	*
	* @return bool <p>true при успешной смене статуса и false при неуспешной. При
	* неуспешной смене статуса переменная LAST_ERROR класса содержит
	* сообщение об ошибке.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* ///<*****************************<br>// Stop sending the message<br>///<*****************************<br>if($action=="stop" &amp;&amp; $ID&gt;0 &amp;&amp; $POST_RIGHT=="W")<br>{<br>	$cPosting-&gt;<b>ChangeStatus</b>($ID, "W");<br>}<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cpostinggeneral/cpostingchangestatus.php
	* @author Bitrix
	*/
	public function ChangeStatus($ID, $status)
	{
		global $DB;

		$ID = intval($ID);
		$this->LAST_ERROR = "";

		$strSql = "SELECT STATUS, VERSION FROM b_posting WHERE ID=".$ID;
		$db_result = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arResult = $db_result->Fetch();
		if(!$arResult)
		{
			$this->LAST_ERROR = GetMessage("class_post_err_notfound")."<br>";
			return false;
		}

		if($arResult["STATUS"]==$status)
			return true;

		switch($arResult["STATUS"].$status)
		{
			case "DP":
				//BCC_TO_SEND fill
				$post = $this->GetByID($ID);
				if(!($post_arr = $post->Fetch()))
				{
					$this->LAST_ERROR .= GetMessage("class_post_err_notfound")."<br>";
					return false;
				}

				$DB->Query("DELETE from b_posting_email WHERE POSTING_ID = ".$ID);

				$DB->Query("
					INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL, SUBSCRIPTION_ID, USER_ID)
					SELECT DISTINCT
						PR.POSTING_ID, 'Y', S.EMAIL, S.ID, S.USER_ID
					FROM
						b_posting_rubric PR
						INNER JOIN b_subscription_rubric SR ON SR.LIST_RUBRIC_ID = PR.LIST_RUBRIC_ID
						INNER JOIN b_subscription S ON S.ID = SR.SUBSCRIPTION_ID
						LEFT JOIN b_user U ON U.ID = S.USER_ID
					WHERE
						PR.POSTING_ID = ".$ID."
						AND S.CONFIRMED = 'Y'
						AND S.ACTIVE = 'Y'
						AND (U.ID IS NULL OR U.ACTIVE = 'Y')
						".(strlen($post_arr["SUBSCR_FORMAT"]) <= 0 || $post_arr["SUBSCR_FORMAT"]==="NOT_REF" ? "": "AND S.FORMAT='".($post_arr["SUBSCR_FORMAT"]=="text"? "text": "html")."'")."
						".(strlen($post_arr["EMAIL_FILTER"]) <= 0 || $post_arr["EMAIL_FILTER"]==="NOT_REF" ? "": "AND ".GetFilterQuery("S.EMAIL", $post_arr["EMAIL_FILTER"], "Y", array("@", ".", "_")))."
				");

				//send to user groups
				$res = $DB->Query("SELECT * FROM b_posting_group WHERE POSTING_ID = ".$ID." AND GROUP_ID = 2");
				if($res->Fetch())
				{
					$DB->Query("
						INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL, SUBSCRIPTION_ID, USER_ID)
						SELECT
							".$ID.", 'Y', U.EMAIL, NULL, MIN(U.ID)
						FROM
							b_user U
						WHERE
							U.ACTIVE = 'Y'
							".(strlen($post_arr["EMAIL_FILTER"]) <= 0 || $post_arr["EMAIL_FILTER"]==="NOT_REF" ? "": "AND ".GetFilterQuery("U.EMAIL", $post_arr["EMAIL_FILTER"], "Y", array("@", ".", "_")))."
							and U.EMAIL not in (SELECT EMAIL FROM b_posting_email WHERE POSTING_ID = ".$ID.")
						GROUP BY U.EMAIL
					");
				}
				else
				{
					$DB->Query("
						INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL, SUBSCRIPTION_ID, USER_ID)
						SELECT
							PG.POSTING_ID, 'Y', U.EMAIL, NULL, MIN(U.ID)
						FROM
							b_posting_group PG
							INNER JOIN b_user_group UG ON UG.GROUP_ID = PG.GROUP_ID
							INNER JOIN b_user U ON U.ID = UG.USER_ID
						WHERE
							PG.POSTING_ID = ".$ID."
							and (UG.DATE_ACTIVE_FROM is null or UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")
							and (UG.DATE_ACTIVE_TO is null or UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().")
							and U.ACTIVE = 'Y'
							".(strlen($post_arr["EMAIL_FILTER"]) <= 0 || $post_arr["EMAIL_FILTER"]==="NOT_REF" ? "": "AND ".GetFilterQuery("U.EMAIL", $post_arr["EMAIL_FILTER"], "Y", array("@", ".", "_")))."
							and U.EMAIL not in (SELECT EMAIL FROM b_posting_email WHERE POSTING_ID = ".$ID.")
						GROUP BY PG.POSTING_ID, U.EMAIL
					");
				}

				//from additional emails
				$BCC = $post_arr["BCC_FIELD"];
				if($post_arr["DIRECT_SEND"] == "Y")
					$BCC .= ($BCC <> ""? ",":"").$post_arr["TO_FIELD"];
				$BCC = str_replace("\r\n", "\n", $BCC);
				$BCC = str_replace("\n", ",", $BCC);
				$aBcc = explode(",", $BCC);
				foreach($aBcc as $email)
				{
					$email = trim($email);
					if($email <> "")
					{
						$DB->Query("
							INSERT INTO b_posting_email (POSTING_ID, STATUS, EMAIL, SUBSCRIPTION_ID, USER_ID)
							SELECT
								P.ID, 'Y', '".($DB->ForSQL($email))."', NULL, NULL
							FROM
								b_posting P
							WHERE
								P.ID = ".$ID."
								and '".($DB->ForSQL($email))."' not in (SELECT EMAIL FROM b_posting_email WHERE POSTING_ID = ".$ID.")
						");
					}
				}

				$res = $DB->Query("SELECT count(*) CNT from b_posting_email WHERE POSTING_ID = ".$ID);
				$ar = $res->Fetch();

				if($ar["CNT"] > 0)
				{
					$DB->Query("UPDATE b_posting SET STATUS='".$status."', VERSION='2', BCC_TO_SEND=null, ERROR_EMAIL=null, SENT_BCC=null WHERE ID=".$ID);
				}
				else
				{
					$this->LAST_ERROR .= GetMessage("class_post_err_status4");
					return false;
				}
				break;
			case "PW":
			case "WP":
			case "PE":
			case "PS":
				$DB->Query("UPDATE b_posting SET STATUS='".$status."' WHERE ID=".$ID);
				break;
			case "EW"://This is the way to resend error e-mails
			case "EP":
				if($arResult["VERSION"] == "2")
				{
					$DB->Query("UPDATE b_posting_email SET STATUS='Y' WHERE POSTING_ID=".$ID." AND STATUS='E'");
					$DB->Query("UPDATE b_posting SET STATUS='".$status."' WHERE ID=".$ID);
				}
				else
				{
					//Send it in old fashion way
					$DB->Query("UPDATE b_posting SET STATUS='".$status."', BCC_TO_SEND=ERROR_EMAIL, ERROR_EMAIL=null WHERE ID=".$ID);
				}
				break;
			case "ED":
			case "SD":
			case "WD":
				$DB->Query("UPDATE b_posting SET STATUS='".$status."', VERSION='2', SENT_BCC=null, ERROR_EMAIL=null, BCC_TO_SEND=null, DATE_SENT=null WHERE ID=".$ID);
				break;
			default:
				$this->LAST_ERROR = GetMessage("class_post_err_status2");
				return false;
		}

		return true;
	}
}

class CMailTools
{
	var $aMatches = array();
	var $pcre_backtrack_limit = false;
	var $server_name = null;
	var $maxFileSize = 0;

	public static function IsEightBit($str)
	{
		$len = strlen($str);
		for($i=0; $i<$len; $i++)
			if(ord(substr($str, $i, 1))>>7)
				return true;
		return false;
	}

	public static function EncodeMimeString($text, $charset)
	{
		if(!CMailTools::IsEightBit($text))
			return $text;

		$maxl = intval((76 - strlen($charset) + 7)*0.4);

		$res = "";
		$eol = \Bitrix\Main\Mail\Mail::getMailEol();
		$len = strlen($text);
		for($i=0; $i<$len; $i=$i+$maxl)
		{
			if($i>0)
				$res .= $eol."\t";
			$res .= "=?".$charset."?B?".base64_encode(substr($text, $i, $maxl))."?=";
		}
		return $res;
	}

	public static function EncodeSubject($text, $charset)
	{
		return "=?".$charset."?B?".base64_encode($text)."?=";
	}

	public static function EncodeHeaderFrom($text, $charset)
	{
		$i = CUtil::BinStrlen($text);
		while($i > 0)
		{
			if(ord(CUtil::BinSubstr($text, $i-1, 1))>>7)
				break;
			$i--;
		}
		if($i==0)
			return $text;
		else
			return "=?".$charset."?B?".base64_encode(CUtil::BinSubstr($text, 0, $i))."?=".CUtil::BinSubstr($text, $i);
	}

	public function __replace_img($matches)
	{
		$io = CBXVirtualIo::GetInstance();
		$src = $matches[3];

		if($src == "")
			return $matches[0];

		if(array_key_exists($src, $this->aMatches))
		{
			$uid = $this->aMatches[$src]["ID"];
			return $matches[1].$matches[2]."cid:".$uid.$matches[4].$matches[5];
		}

		$filePath = $io->GetPhysicalName($_SERVER["DOCUMENT_ROOT"].$src);
		if(!file_exists($filePath))
			return $matches[0];

		if (
			$this->maxFileSize > 0
			&& filesize($filePath) > $this->maxFileSize
		)
			return $matches[0];

		$aImage = CFile::GetImageSize($filePath, true);
		if (!is_array($aImage))
			return $matches[0];

		if (function_exists("image_type_to_mime_type"))
			$contentType = image_type_to_mime_type($aImage[2]);
		else
			$contentType = CMailTools::ImageTypeToMimeType($aImage[2]);

		$uid = uniqid(md5($src));

		$this->aMatches[$src] = array(
			"SRC" => $src,
			"PATH" => $filePath,
			"CONTENT_TYPE" => $contentType,
			"DEST" => bx_basename($src),
			"ID" => $uid,
		);

		return $matches[1].$matches[2]."cid:".$uid.$matches[4].$matches[5];
	}

	public function ReplaceHrefs($text)
	{
		if($this->pcre_backtrack_limit === false)
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		$text_len = defined("BX_UTF")? mb_strlen($text, 'latin1'): strlen($text);
		$text_len++;
		if($this->pcre_backtrack_limit < $text_len)
		{
			@ini_set("pcre.backtrack_limit", $text_len);
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		}

		if(!isset($this->server_name))
			$this->server_name = COption::GetOptionString("main", "server_name", "");

		if($this->server_name != '')
			$text = preg_replace(
				"/(<a\\s[^>]*?(?<=\\s)href\\s*=\\s*)([\"'])(\\/.*?)(\\2)(\\s.+?>|\\s*>)/is",
				"\\1\\2http://".$this->server_name."\\3\\4\\5",
				$text
			);

		return $text;
	}

	public function ReplaceImages($text)
	{
		if($this->pcre_backtrack_limit === false)
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		$text_len = defined("BX_UTF")? mb_strlen($text, 'latin1'): strlen($text);
		$text_len++;
		if($this->pcre_backtrack_limit < $text_len)
		{
			@ini_set("pcre.backtrack_limit", $text_len);
			$this->pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
		}
		$this->maxFileSize = intval(COption::GetOptionInt("subscribe", "max_file_size"));
		$this->aMatches = array();
		$text = preg_replace_callback(
			"/(<img\\s[^>]*?(?<=\\s)src\\s*=\\s*)([\"']?)(.*?)(\\2)(\\s.+?>|\\s*>)/is",
			array(&$this, "__replace_img"),
			$text
		);
		$text = preg_replace_callback(
			"/(background-image\\s*:\\s*url\\s*\\()([\"']?)(.*?)(\\2)(\\s*\\);)/is",
			array(&$this, "__replace_img"),
			$text
		);
		$text = preg_replace_callback(
			"/(<td\\s[^>]*?(?<=\\s)background\\s*=\\s*)([\"']?)(.*?)(\\2)(\\s.+?>|\\s*>)/is",
			array(&$this, "__replace_img"),
			$text
		);
		$text = preg_replace_callback(
			"/(<table\\s[^>]*?(?<=\\s)background\\s*=\\s*)([\"']?)(.*?)(\\2)(\\s.+?>|\\s*>)/is",
			array(&$this, "__replace_img"),
			$text
		);
		return $text;
	}

	public static function ImageTypeToMimeType($type)
	{
		static $aTypes = array(
			1 => "image/gif",
			2 => "image/jpeg",
			3 => "image/png",
			4 => "application/x-shockwave-flash",
			5 => "image/psd",
			6 => "image/bmp",
			7 => "image/tiff",
			8 => "image/tiff",
			9 => "application/octet-stream",
			10 => "image/jp2",
			11 => "application/octet-stream",
			12 => "application/octet-stream",
			13 => "application/x-shockwave-flash",
			14 => "image/iff",
			15 => "image/vnd.wap.wbmp",
			16 => "image/xbm",
		);
		if (isset($aTypes[$type]))
			return $aTypes[$type];
		else
			return "application/octet-stream";
	}
}
?>