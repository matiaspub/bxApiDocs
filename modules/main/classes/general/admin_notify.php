<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CAdminNotify</b> - класс для работы с системными уведомлениями.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminnotify/index.php
 * @author Bitrix
 */
class CAdminNotify
{
	const TYPE_NORMAL = 'NORMAL';
	const TYPE_ERROR = 'ERROR';

	protected static function CleanCache()
	{
		global $CACHE_MANAGER;

		$by = 'lid';
		$order = 'asc';
		$rsLangs = CLanguage::GetList($by, $order);
		while ($arLang = $rsLangs->Fetch())
		{
			$CACHE_MANAGER->Clean("admin_notify_list_".$arLang['LANGUAGE_ID']);
		}
		$CACHE_MANAGER->Clean("admin_notify_list");
	}

	
	/**
	* <p>Метод служит для добавления уведомления. Нестатический метод.</p>
	*
	*
	* @param mixed $MESSAGE  Произвольный текст, поддерживает работу со следующим тэгами
	* <b>BR</b>, <b>B</b>, <b>U</b>, <b>I</b>, <b>SPAN</b> <b>A</b>. (В последних двух можно
	* использовать <b>style</b>).
	*
	* @param MESSAG $TAG  Метка уведомления, для его легкого удаления (не обязательное
	* поле).  <p></p> <div class="note"> <b>Примечание</b>: Если добавить два
	* уведомления с одинаковым тэгом (пустой тэг не считается)
	* останется только последнее уведомление.</div>
	*
	* @param TA $MODULE_ID  Модуль отправивший уведомление (не обязательное поле).
	*
	* @param MODULE_I $ENABLE_CLOSE  Может ли администратор закрыть уведомление сам из интерфейса,
	* или будет требоваться удаление через API (не обязательное, по
	* умолчанию может).
	*
	* @return mixed <p>Взвращается ID созданного уведомления.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $ar = Array(
	*    "MESSAGE" =&gt; 'Вы обновили модуль "Веб-мессенджер", для работы с историей сообщений вам необходимо произвести &lt;a href="#"&gt;конвертацию данных&lt;/a&gt;.',
	*    "TAG" =&gt; "IM_CONVERT",
	*    "MODULE_ID" =&gt; "IM",
	*    "ENABLE_CLOSE" =&gt; "N"
	* );
	* $ID = CAdminNotify::Add($ar);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminnotify/add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;
		$err_mess = (self::err_mess()).'<br />Function: Add<br />Line: ';

		if (!self::CheckFields($arFields))
			return false;

		if (!is_set($arFields['ENABLE_CLOSE']))
			$arFields['ENABLE_CLOSE'] = 'Y';

		if (is_set($arFields['TAG']) && strlen(trim($arFields['TAG']))>0)
		{
			$arFields['TAG'] = trim($arFields['TAG']);
			self::DeleteByTag($arFields['TAG']);
		}
		else
		{
			$arFields['TAG'] = "";
		}

		if (!isset($arFields['TYPE']) || !in_array($arFields['TYPE'], Array(self::TYPE_NORMAL, self::TYPE_ERROR)))
			$arFields['TYPE'] = self::TYPE_NORMAL;

		$arFields_i = Array(
			'MODULE_ID'	=> is_set($arFields['MODULE_ID'])? trim($arFields['MODULE_ID']): "",
			'TAG'	=> $arFields['TAG'],
			'MESSAGE'	=> trim($arFields['MESSAGE']),
			'ENABLE_CLOSE'	=> $arFields['ENABLE_CLOSE'],
			'PUBLIC_SECTION' => $arFields['PUBLIC_SECTION']
		);
		$ID = $DB->Add('b_admin_notify', $arFields_i, Array('MESSAGE'));

		if ($ID)
		{
			if (isset($arFields['LANG']) && !empty($arFields['LANG']) && is_array($arFields['LANG']))
			{
				foreach ($arFields['LANG'] as $strLang => $strMess)
				{
					$arFields_l = array(
						'NOTIFY_ID' => $ID,
						'LID' => $strLang,
						'MESSAGE' => trim($strMess)
					);
					$intLangID = $DB->Add('b_admin_notify_lang', $arFields_l, array('MESSAGE'));
				}
			}
		}

		self::CleanCache();
		return $ID;
	}

	private static function CheckFields($arFields)
	{
		$aMsg = array();

		if(is_set($arFields, 'MODULE_ID') && trim($arFields['MODULE_ID'])=='')
			$aMsg[] = array('id'=>'MODULE_ID', 'text'=>GetMessage('MAIN_AN_ERROR_MODULE_ID'));
		if(is_set($arFields, 'TAG') && trim($arFields['TAG'])=='')
			$aMsg[] = array('id'=>'TAG', 'text'=>GetMessage('MAIN_AN_ERROR_TAG'));
		if(!is_set($arFields, 'MESSAGE') || trim($arFields['MESSAGE'])=='')
			$aMsg[] = array('id'=>'MESSAGE', 'text'=>GetMessage('MAIN_AN_ERROR_MESSAGE'));
		if(is_set($arFields, 'ENABLE_CLOSE') && !($arFields['ENABLE_CLOSE'] == 'Y' || $arFields['ENABLE_CLOSE'] == 'N'))
			$aMsg[] = array('id'=>'ENABLE_CLOSE', 'text'=>GetMessage('MAIN_AN_ERROR_ENABLE_CLOSE'));
		$arFields['PUBLIC_SECTION'] = (isset($arFields['PUBLIC_SECTION']) && $arFields['PUBLIC_SECTION'] == 'Y' ? 'Y' : 'N');

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}

	
	/**
	* <p>Метод удаляет уведомление по идентификатору. Статический метод.</p>
	*
	*
	* @param mixed $intID  Идентификатор уведомления
	*
	* @return mixed <p>Возвращает <i>true</i>, если удаление совершено, в противном случае -
	* <i>false</i>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* CAdminNotify::Delete(1)
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminnotify/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$err_mess = (self::err_mess()).'<br />Function: Delete<br />Line: ';
		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		$strSql = "DELETE FROM b_admin_notify_lang WHERE NOTIFY_ID = ".$ID;
		$DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "DELETE FROM b_admin_notify WHERE ID = ".$ID;
		$DB->Query($strSql, false, $err_mess.__LINE__);

		self::CleanCache();
		return true;
	}

	
	/**
	* <p>Метод удаляет уведомление по идентификатору модуля. Статический метод.</p>
	*
	*
	* @param mixed $moduleId  Идентификатор модуля
	*
	* @return mixed <p>Возвращает <i>true</i>, если удаление совершено, в противном случае -
	* <i>false</i>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* CAdminNotify::DeleteByModule("xmpp")
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminnotify/deletebymodule.php
	* @author Bitrix
	*/
	public static function DeleteByModule($moduleId)
	{
		global $DB;
		$err_mess = (self::err_mess()).'<br />Function: DeleteByModule<br />Line: ';

		$strSql = "DELETE FROM b_admin_notify_lang WHERE NOTIFY_ID IN (SELECT ID FROM b_admin_notify WHERE MODULE_ID = '".$DB->ForSQL($moduleId)."')";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "DELETE FROM b_admin_notify WHERE MODULE_ID = '".$DB->ForSQL($moduleId)."'";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		self::CleanCache();
		return true;
	}

	
	/**
	* <p>Метод удаляет уведомление по тегу. Статический метод.</p>
	*
	*
	* @param mixed $tag  Идентификатор тега
	*
	* @return mixed <p>Возвращает <i>true</i>, если удаление совершено, в противном случае -
	* <i>false</i>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* CAdminNotify::DeleteByTag("IM_CONVERT")
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminnotify/deletebytag.php
	* @author Bitrix
	*/
	public static function DeleteByTag($tagId)
	{
		global $DB;
		$err_mess = (self::err_mess()).'<br />Function: DeleteByTag<br />Line: ';

		$tagId = (string)$tagId;
		if ($tagId == '')
			return false;

		$strSql = "DELETE FROM b_admin_notify_lang WHERE NOTIFY_ID IN (SELECT ID FROM b_admin_notify WHERE TAG like '%".$DB->ForSQL($tagId)."%')";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "DELETE FROM b_admin_notify WHERE TAG like '%".$DB->ForSQL($tagId)."%'";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		self::CleanCache();
		return true;
	}

	public static function GetHtml()
	{
		global $CACHE_MANAGER;
		$arNotify = false;

		if($CACHE_MANAGER->Read(86400, "admin_notify_list_".LANGUAGE_ID))
			$arNotify = $CACHE_MANAGER->Get("admin_notify_list_".LANGUAGE_ID);

		if($arNotify === false)
		{
			$arNotify = Array();
			$CBXSanitizer = new CBXSanitizer;
			$CBXSanitizer->AddTags(array(
				'a' => array('href','style'),
				'b' => array(), 'u' => array(),
				'i' => array(), 'br' => array(),
				'span' => array('style'),
			));
			$dbRes = self::GetList();
			while ($ar = $dbRes->Fetch())
			{
				$ar["MESSAGE"] = $CBXSanitizer->SanitizeHtml((''!= $ar['MESSAGE_LANG'] ? $ar['MESSAGE_LANG'] : $ar['MESSAGE']));
				$arNotify[] = $ar;
			}
			$CACHE_MANAGER->Set("admin_notify_list_".LANGUAGE_ID, $arNotify);
		}

		$html = "";
		foreach ($arNotify as $value)
		{
			$html .= '<div class="adm-warning-block" data-id="'.intval($value['ID']).'" data-ajax="Y"><span class="adm-warning-text">'.$value['MESSAGE'].'</span><span class="adm-warning-icon"></span>'.($value['ENABLE_CLOSE'] == 'Y' ? '<span onclick="BX.adminPanel ? BX.adminPanel.hideNotify(this.parentNode) : BX.admin.panel.hideNotify(this.parentNode);" class="adm-warning-close"></span>' : '').'</div>';
		}

		return $html;
	}

	
	/**
	* <p>Метод производит выборку уведомлений с сортировкой и фильтрацией. Статический метод.</p>
	*
	*
	* @param array $arSort = array() Сортировка осуществляется по: <ul> <li> <b>ID</b> - идентификатору
	* сообщения;</li> <li> <b>MODULE_ID</b> - идентификатору модуля, к которому
	* относится сообщение.</li>   </ul>
	*
	* @param array $arFilter = array() Фильтрация осуществляется по: <ul> <li> <b>ID</b> - идентификатору
	* сообщения;</li> <li> <b>MODULE_ID</b> - идентификатору модуля, к которому
	* относится сообщение;</li>  <li> <b>TAG</b> - тегу;</li>  <li> <b>ENABLE_CLOSE</b>-
	* разрешению на ручное закрытие.</li>  </ul>
	*
	* @return mixed <p>Возвращается экземляр класса <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> для дальней
	* обработки.</p>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* CAdminNotify::GetList(array('ID' =&gt; 'DESC'), array('MODULE_ID'=&gt;'main'));
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminnotify/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arSort=array(), $arFilter=array())
	{
		global $DB;

		$arSqlSearch = Array();
		$strSqlSearch = '';
		$err_mess = (self::err_mess()).'<br />Function: GetList<br />Line: ';

		if (!is_array($arFilter))
			$arFilter = array();
		if (!isset($arFilter['LID']))
			$arFilter['LID'] = LANGUAGE_ID;
		if (!isset($arFilter['PUBLIC_SECTION']))
			$arFilter['PUBLIC_SECTION'] = 'N';

		$strFrom = '';
		$strSelect = "AN.ID, AN.MODULE_ID, AN.TAG, AN.MESSAGE, AN.ENABLE_CLOSE, AN.PUBLIC_SECTION";

		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0, $ic=count($filter_keys); $i<$ic; $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				if (strlen($val)<=0 || $val=='NOT_REF') continue;
				switch(strtoupper($filter_keys[$i]))
				{
					case 'ID':
						$arSqlSearch[] = GetFilterQuery('AN.ID', $val, 'N');
					break;
					case 'MODULE_ID':
						$arSqlSearch[] = GetFilterQuery('AN.MODULE_ID', $val);
					break;
					case 'TAG':
						$arSqlSearch[] = GetFilterQuery('AN.TAG', $val);
					break;
					case 'MESSAGE':
						$arSqlSearch[] = GetFilterQuery('AN.MESSAGE', $val);
					break;
					case 'ENABLE_CLOSE':
						$arSqlSearch[] = ($val=='Y') ? "AN.ENABLE_CLOSE='Y'" : "AN.ENABLE_CLOSE='N'";
					break;
					case 'LID':
						$strSelect .= ", ANL.MESSAGE as MESSAGE_LANG";
						$strFrom = 'LEFT JOIN b_admin_notify_lang ANL ON (AN.ID = ANL.NOTIFY_ID AND ANL.LID = \''.$DB->ForSQL($val).'\')';
						break;
					case 'PUBLIC_SECTION':
						$arSqlSearch[] = ($val=='Y') ? "AN.PUBLIC_SECTION='Y'" : "AN.PUBLIC_SECTION='N'";
				}
			}
		}

		$sOrder = '';
		foreach($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> 'ASC'? 'DESC':'ASC');
			switch (strtoupper($key))
			{
				case 'ID':		$sOrder .= ', AN.ID '.$ord; break;
				case 'MODULE_ID':	$sOrder .= ', AN.MODULE_ID '.$ord; break;
				case 'ENABLE_CLOSE':	$sOrder .= ', AN.ENABLE_CLOSE '.$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = 'AN.ID DESC';

		$strSqlOrder = ' ORDER BY '.TrimEx($sOrder,',');
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$strSql = "SELECT ".$strSelect." FROM b_admin_notify AN ".$strFrom." WHERE ".$strSqlSearch." ".$strSqlOrder;
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	private static function err_mess()
	{
		return '<br />Class: CAdminNotify<br />File: '.__FILE__;
	}
}