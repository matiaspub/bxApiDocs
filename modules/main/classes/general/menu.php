<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);


/**
 * <b>CMenu</b> - класс для работы с меню.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/index.php
 * @author Bitrix
 */
class CMenu
{
	var $type = "left";
	var $arMenu = array();
	var $bMenuCalc = false;
	var $MenuDir = "";
	var $MenuExtDir = "";
	var $MenuTemplate = "";
	var $template = "";
	var $LAST_ERROR = "";
	/** @var CDebugInfo */
	var $debug = null;

	public function __construct($type="left")
	{
		$this->type = $type;
	}

	public function disableDebug()
	{
		$this->debug = false;
	}

	
	/**
	* <p>Инициализирует (заполняет пунктами) объект класса CMenu. Возвращает "true" если в каталоге сайта найден файл меню <nobr><b>.</b><i>тип меню</i><b>.menu.php</b></nobr> (поиск идет вверх по иерархии начиная с каталога <i>dir</i>), и "false" в противном случае. Нестатический метод.</p>
	*
	*
	* @param string $InitDir  Папка, начиная с которой, объект будет искать файл <nobr><b>.</b><i>тип
	* меню</i><b>.menu.php</b></nobr> (файл с параметрами и пунктами меню).
	*
	* @param bool $MenuExt = false Если значение - "true", то для формирования массива меню, помимо
	* файлов <nobr><b>.</b><i>тип меню</i><b>.menu.php</b></nobr> будут также подключаться
	* файлы с именами вида <nobr><b>.</b><i>тип меню</i><b>.menu_ext.php</b></nobr>. В которых
	* вы можете манипулировать массивом меню <b>$aMenuLinks</b> произвольно, по
	* вашему усмотрению (например, дополнять пункты меню значениями из
	* инфо-блоков).<br>Необязателен. По умолчанию - "false".
	*
	* @param string $template = false Шаблон для вывода меню. <br>Необязателен. По умолчанию - "false", что
	* означает искать шаблон меню сначала в файле <nobr><b>/bitrix/templates/</b><i>ID
	* текущего шаблона сайта</i><b>/</b><i>тип меню</i><b>.menu_template.php</b></nobr>, затем
	* если шаблон не будет найден, то искать в файле
	* <nobr><b>/bitrix/templates/.default/</b><i>тип меню</i><b>.menu_template.php</b></nobr>. В самом
	* шаблоне меню вам будут доступны следующие предустановленные
	* переменные: 	<ul> <li> <b>$arMENU</b> - копия массива меню 		</li> <li> <b>$arMENU_LINK</b> -
	* ссылка на текущий массив меню 		</li> <li> <b>$TEXT</b> - текст текущего
	* пункта меню 		</li> <li> <b>$LINK</b> - ссылка текущего пункта меню 		</li> <li>
	* <b>$SELECTED</b> - выбран ли пункт меню в данный момент 		</li> <li> <b>$PERMISSION</b> -
	* доступ на страницу указанную в $LINK, возможны следующие значения:
	* 			<ul> <li> <b>D</b> - доступ запрещён 				</li> <li> <b>R</b> - чтение (право
	* просмотра содержимого файла) 				</li> <li> <b>U</b> - документооборот
	* (право на редактирование файла в режиме документооборота) 				</li>
	* <li> <b>W</b> - запись (право на прямое редактирование) 				</li> <li> <b>X</b> -
	* полный доступ (право на прямое редактирование файла и право на
	* изменение прав доступа на данных файл) 			</li> </ul> </li> <li>
	* <b>$ADDITIONAL_LINKS</b> - дополнительные ссылки для подсветки меню 		</li> <li>
	* <b>$ITEM_TYPE</b> - "D" - директория (если $LINK заканчивается на "/"), иначе "P" -
	* страница 		</li> <li> <b>$ITEM_INDEX</b> - порядковый номер пункта меню 		</li> <li>
	* <b>$PARAMS</b> - параметры пунктов меню 	</li> </ul> 	При этом в шаблоне для
	* построения меню необходимо будет инициализировать следующие
	* перменные: 	<ul> <li> <b>$sMenuProlog</b> - HTML который будет добавлен перед
	* пунктами меню 		</li> <li> <b>$sMenuEpilog</b> - HTML который будет добавлен после
	* пунктов меню 		</li> <li> <b>$sMenuBody</b> - HTML представляющий из себя один
	* пункт меню 		</li> <li> <b>$sMenu</b> - HTML представляющий из себя все меню
	* целиком (только для метода GetMenuHtmlEx) 	</li> </ul>
	*
	* @param mixed $onlyCurrentDir = false Если значение - "true", то отключается поиск файла меню в
	* родительских каталогах.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $lm = new CMenu("left");
	* <b>$lm-&gt;Init</b>($APPLICATION-&gt;GetCurDir(), true);
	* $lm-&gt;template = "/bitrix/templates/demo/left.menu_template.php";
	* echo $lm-&gt;GetMenuHtml();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04708"
	* >Меню</a></li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenu.php">CMain::GetMenu</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/init.php
	* @author Bitrix
	*/
	public function Init($InitDir, $bMenuExt=false, $template=false, $onlyCurrentDir=false)
	{
		global $USER;
		if(
			$this->debug !== false
			&& $_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"] == "Y"
			&& (
				$USER->IsAdmin()
				|| $_SESSION["SHOW_SQL_STAT"]=="Y"
			)
		)
		{
			$this->debug = new CDebugInfo(false);
			$this->debug->Start();
		}

		$io = CBXVirtualIo::GetInstance();

		$aMenuLinks = array();
		$bFounded = false;
		if($template === false)
			$sMenuTemplate = '';
		else
			$sMenuTemplate = $template;

		$InitDir = str_replace("\\", "/", $InitDir);
		$Dir = $InitDir;

		$site_dir = false;
		if(defined("SITE_DIR") && SITE_DIR <> '')
		{
			$site_dir = SITE_DIR;
		}
		elseif(array_key_exists("site", $_REQUEST) && $_REQUEST["site"] <> '')
		{
			$rsSites = CSite::GetByID($_REQUEST["site"]);
			if($arSite = $rsSites->Fetch())
				$site_dir = $arSite["DIR"];
		}

		while($Dir <> '')
		{
			if($site_dir !== false && (strlen(trim($Dir, "/")) < strlen(trim($site_dir, "/"))))
				break;

			$Dir = rtrim($Dir, "/");
			$menu_file_name = $io->CombinePath($_SERVER["DOCUMENT_ROOT"], $Dir, ".".$this->type.".menu.php");

			if($io->FileExists($menu_file_name))
			{
				include($io->GetPhysicalName($menu_file_name));
				$this->MenuDir = $Dir."/";
				$this->arMenu = $aMenuLinks;
				$this->template = $sMenuTemplate;
				$bFounded = true;
				break;
			}

			if($Dir == "")
				break;

			$pos = strrpos($Dir, "/");
			if($pos===false || $onlyCurrentDir == true)
				break;

			$Dir = substr($Dir, 0, $pos+1);
		}

		if($bMenuExt)
		{
			$Dir = $InitDir;
			while($Dir <> '')
			{
				if($site_dir !== false && (strlen(trim($Dir, "/")) < strlen(trim($site_dir, "/"))))
					break;

				$Dir = rtrim($Dir, "/");
				$menu_file_name = $io->CombinePath($_SERVER["DOCUMENT_ROOT"], $Dir, ".".$this->type.".menu_ext.php");

				if($io->FileExists($menu_file_name))
				{
					include($io->GetPhysicalName($menu_file_name));
					if(!$bFounded)
						$this->MenuDir = $Dir."/";

					$this->MenuExtDir = $Dir."/";
					$this->arMenu = $aMenuLinks;
					$this->template = $sMenuTemplate;
					$bFounded = true;
					break;
				}

				if($Dir == "")
					break;

				$pos = strrpos($Dir, "/");
				if($pos===false || $onlyCurrentDir == true)
					break;

				$Dir = substr($Dir, 0, $pos+1);
			}
		}

		return $bFounded;
	}

	public function RecalcMenu($bMultiSelect = false, $bCheckSelected = true)
	{
		if($this->bMenuCalc !== false)
			return true;

		/**
		 * @global CAllMain $APPLICATION
		 * @global CCacheManager $CACHE_MANAGER
		 * @noinspection PhpUnusedLocalVariableInspection
		 */
		global $USER, $DB, $APPLICATION, $CACHE_MANAGER;

		$result = array();

		$cur_page = $APPLICATION->GetCurPage(true);
		$cur_page_no_index = $APPLICATION->GetCurPage(false);

		$APPLICATION->_menu_recalc_counter++;

		$this->bMenuCalc = true;

		if(strlen($this->template)>0 && file_exists($_SERVER["DOCUMENT_ROOT"].$this->template))
		{
			$this->MenuTemplate = $_SERVER["DOCUMENT_ROOT"].$this->template;
		}
		else
		{
			if(defined("SITE_TEMPLATE_PATH") && file_exists($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/".$this->type.".menu_template.php"))
			{
				$this->template = SITE_TEMPLATE_PATH."/".$this->type.".menu_template.php";
				$this->MenuTemplate = $_SERVER["DOCUMENT_ROOT"].$this->template;
			}
			elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".LANG."/".$this->type.".menu_template.php"))
			{
				$this->template = BX_PERSONAL_ROOT."/php_interface/".LANG."/".$this->type.".menu_template.php";
				$this->MenuTemplate = $_SERVER["DOCUMENT_ROOT"].$this->template;
			}
			else
			{
				$this->template = BX_PERSONAL_ROOT."/templates/.default/".$this->type.".menu_template.php";
				$this->MenuTemplate = $_SERVER["DOCUMENT_ROOT"].$this->template;
			}
		}

		if(!file_exists($this->MenuTemplate))
		{
			$this->LAST_ERROR = "Template ".$this->MenuTemplate." is not found.";
			return false;
		}

		$arMenuCache = false;
		$bCached = false;
		$bCacheIsAllowed = CACHED_menu!==false && !$USER->IsAuthorized() && $this->MenuExtDir == '';
		if($bCacheIsAllowed)
		{
			$cache_id = $_SERVER["DOCUMENT_ROOT"].",".$this->MenuDir.",,".$this->type;
			if($CACHE_MANAGER->Read(CACHED_menu, $cache_id, "menu"))
			{
				$arMenuCache = $CACHE_MANAGER->Get($cache_id);
				$bCached = true;
			}
		}

		$arUserRights = $USER->GetAccessCodes();
		$ITEM_INDEX = -1;

		$cur_selected = -1;
		$cur_selected_len = -1;
		$previousDepthLevel = -1;
		$arParents = array(); //Stack of menu items

		foreach($this->arMenu as $iMenuItem=>$MenuItem)
		{
			$TEXT = $MenuItem[0];

			if($bCached)
			{
				$LINK = $arMenuCache[$iMenuItem]["LINK"];
			}
			else
			{
				//if the link is relative let's transform it to absolute
				if(!preg_match("'^(([A-Za-z]+://)|mailto:|javascript:|#)'i", $MenuItem[1]))
				{
					$LINK = Rel2Abs($this->MenuDir, $MenuItem[1]);
				}
				else
				{
					$LINK = $MenuItem[1];
				}
				$arMenuCache[$iMenuItem]["LINK"] = $LINK;
			}

			$bSkipMenuItem = false;
			$ADDITIONAL_LINKS = $MenuItem[2];
			$PARAMS = $MenuItem[3];

			//Calculate menu items stack for iblock items only
			if($this->MenuExtDir <> '' && is_array($PARAMS) && isset($PARAMS["FROM_IBLOCK"]))
			{
				if($previousDepthLevel == -1)
					$previousDepthLevel = $PARAMS["DEPTH_LEVEL"];

				if($PARAMS["DEPTH_LEVEL"] > $previousDepthLevel)
				{
					//Deeper into sections tree
					if($iMenuItem > 0)
						$arParents[] = array("INDEX" => $iMenuItem-1, "DEPTH_LEVEL" => $PARAMS["DEPTH_LEVEL"]);
				}
				else
				{
					//Unwind parents stack
					while(
						count($arParents) > 0
						&& $arParents[count($arParents)-1]["DEPTH_LEVEL"] > $PARAMS["DEPTH_LEVEL"]
					)
					{
						array_pop($arParents);
					}
				}
				$previousDepthLevel = $PARAMS["DEPTH_LEVEL"];
			}
			elseif($previousDepthLevel != -1)
			{
				//End of tree, so reset the stack
				$previousDepthLevel = -1;
				$arParents = array();
			}


			if(count($MenuItem)>4)
			{
				$CONDITION = $MenuItem[4];
				if($CONDITION <> '' && (!eval("return ".$CONDITION.";")))
					$bSkipMenuItem = true;
			}

			if(!$bSkipMenuItem)
				$ITEM_INDEX++;

			if(($pos = strpos($LINK, "?"))!==false)
				$ITEM_TYPE = "U";
			elseif(substr($LINK, -1)=="/")
				$ITEM_TYPE = "D";
			else
				$ITEM_TYPE = "P";

			$SELECTED = false;

			if($bCached)
			{
				$all_links = $arMenuCache[$iMenuItem]["LINKS"];
				if(!is_array($all_links))
					$all_links = array();
			}
			else
			{
				$all_links = array();
				if(is_array($ADDITIONAL_LINKS))
				{
					foreach($ADDITIONAL_LINKS as $link)
					{
						$tested_link = trim(Rel2Abs($this->MenuDir, $link));
						if(strlen($tested_link)>0)
							$all_links[] = $tested_link;
					}
				}
				$all_links[] = $LINK;
				$arMenuCache[$iMenuItem]["LINKS"] = $all_links;
			}

			if(preg_match("'^(([A-Za-z]+://)|mailto:|javascript:|#)'i", $MenuItem[1]))
			{
				$PERMISSION = "Z";
			}
			else
			{
				if(!$bSkipMenuItem && $bCheckSelected)
				{
					foreach($all_links as $tested_link)
					{
						if($tested_link == '')
							continue;

						$SELECTED = self::IsItemSelected($tested_link, $cur_page, $cur_page_no_index);
						if($SELECTED)
							break;
					}
				}

				if($bCached)
					$PERMISSION = $arMenuCache[$iMenuItem]["PERM"];
				else
					$arMenuCache[$iMenuItem]["PERM"] = $PERMISSION = $APPLICATION->GetFileAccessPermission(GetFileFromURL($LINK), $arUserRights);
			}

			if($SELECTED && !$bMultiSelect)
			{
				/** @noinspection PhpUndefinedVariableInspection */
				$new_len = strlen($tested_link);
				if($new_len > $cur_selected_len)
				{
					if($cur_selected !== -1)
						$result[$cur_selected]['SELECTED'] = false;

					$cur_selected = count($result);
					$cur_selected_len = $new_len;
				}
				else
				{
					$SELECTED = false;
				}
			}

			//Adjust selection for iblock sections tree
			if(
				$SELECTED
				&& $this->MenuExtDir <> ''
				&& is_array($PARAMS)
				&& isset($PARAMS["FROM_IBLOCK"])
			)
			{
				foreach($arParents as $parentMenuItem)
				{
					$parentIndex = $parentMenuItem["INDEX"];
					if(
						is_array($result[$parentIndex]["PARAMS"])
						&& isset($result[$parentIndex]["PARAMS"]["FROM_IBLOCK"])
					)
						$result[$parentIndex]["SELECTED"] = true;
				}
			}

			if(!$bSkipMenuItem)
			{
				$r = array(
					"TEXT" => $TEXT,
					"LINK" => $LINK,
					"SELECTED" => $SELECTED,
					"PERMISSION" => $PERMISSION,
					"ADDITIONAL_LINKS" => $ADDITIONAL_LINKS,
					"ITEM_TYPE" => $ITEM_TYPE,
					"ITEM_INDEX" => $ITEM_INDEX,
					"PARAMS" => $PARAMS
				);

				$result[] = $r;
			}
		}

		$this->arMenu = $result;

		if($bCacheIsAllowed && !$bCached)
		{
			/** @noinspection PhpUndefinedVariableInspection */
			$CACHE_MANAGER->Set($cache_id, $arMenuCache);
		}

		return true;
	}

	public static function IsItemSelected($tested_link, $cur_page, $cur_page_no_index)
	{
		//"/admin/"
		//"/admin/index.php"
		//"/admin/index.php?module=mail"
		if(strpos($cur_page, $tested_link) === 0 || strpos($cur_page_no_index, $tested_link) === 0)
			return true;

		if(($pos = strpos($tested_link, "?")) !== false)
		{
			if(($s = substr($tested_link, 0, $pos)) == $cur_page || $s == $cur_page_no_index)
			{
				$params = explode("&", substr($tested_link, $pos+1));
				$bOK = true;
				foreach($params as $param)
				{
					$eqpos = strpos($param, "=");
					$varvalue = "";
					if($eqpos === false)
					{
						$varname = $param;
					}
					elseif($eqpos == 0)
					{
						continue;
					}
					else
					{
						$varname = substr($param, 0, $eqpos);
						$varvalue = urldecode(substr($param, $eqpos+1));
					}

					$globvarvalue = (isset($GLOBALS[$varname])? $GLOBALS[$varname] : "");
					if($globvarvalue != $varvalue)
					{
						$bOK = false;
						break;
					}
				}

				if($bOK)
					return true;
			}
		}
		return false;
	}

	
	/**
	* <p>Возвращает HTML представляющий из себя меню. В отличие от метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/getmenuhtml.php">CMenu::GetMenuHtml</a> шаблон меню будет подключаться только один раз. Нестатический метод.</p> <p class="note"><b>Примечание</b>. В шаблоне меню, используемом методом, в обязательном порядке необходимо инициализировать переменную <b>$sMenu</b>, в которой должен храниться HTML представляющий из себя все меню целиком.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $lm = new CMenu("left");
	* $lm-&gt;Init($APPLICATION-&gt;GetCurDir(), true);
	* $lm-&gt;template = "/bitrix/templates/demo/left.menu_template.php";
	* echo <b>$lm-&gt;GetMenuHtmlEx</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04708" >Меню</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/getmenuhtml.php">CMenu::GetMenuHtml</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenuhtml.php">CMain::GetMenuHtml</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenuhtmlex.php">CMain::GetMenuHtmlEx</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/getmenuhtmlex.php
	* @author Bitrix
	*/
	public function GetMenuHtmlEx()
	{
		/**
		 * @global CAllMain $APPLICATION
		 * @noinspection PhpUnusedLocalVariableInspection
		 */
		global $USER, $DB, $APPLICATION; // must be!

		if(!$this->RecalcMenu())
			return false;

		// $arMENU - menu array copy
		// $arMENU_LINK - reference to menu array
		/** @noinspection PhpUnusedLocalVariableInspection */
		$arMENU_LINK = $MENU_ITEMS = &$this->arMenu;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$arMENU = $this->arMenu;
		$sMenu = "";

		include($this->MenuTemplate);

		$result = $sMenu;

		$arIcons = array();
		$bShowButtons = false;
		$sMenuFile = $this->MenuDir.".".$this->type.".menu.php";
		if($APPLICATION->GetShowIncludeAreas())
		{
			$menu_perm = $APPLICATION->GetFileAccessPermission($sMenuFile);
			$templ_perm = $APPLICATION->GetFileAccessPermission($this->template);
			if($menu_perm >= "W")
			{
				$arIcons[] = array(
					"URL"=>"/bitrix/admin/fileman_menu_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&path=".urlencode($this->MenuDir)."&name=".$this->type,
					"ICON"=>"menu-edit",
					"TITLE"=>GetMessage("MAIN_MENU_EDIT")
				);
			}
			if($templ_perm>="W" && $USER->IsAdmin())
			{
				$arIcons[] = array(
					"URL"=>"/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&full_src=Y&path=".urlencode($this->template),
					"ICON"=>"menu-template",
					"TITLE"=>GetMessage("MAIN_MENU_TEMPLATE_EDIT")
				);
			}
			if(count($arIcons) > 0)
			{
				$result = $APPLICATION->IncludeStringBefore().$result;
				$bShowButtons = true;
			}
		}

		if($this->debug)
			$result .= $this->debug->Output($sMenuFile, $sMenuFile);

		if($bShowButtons)
			$result .= $APPLICATION->IncludeStringAfter($arIcons);

		return $result;
	}


	
	/**
	* <p>Возвращает HTML представляющий из себя меню. Нестатический метод.</p>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $lm = new CMenu("left");
	* $lm-&gt;Init($APPLICATION-&gt;GetCurDir(), true);
	* $lm-&gt;template = "/bitrix/templates/demo/left.menu_template.php";
	* echo <b>$lm-&gt;GetMenuHtml</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;CHAPTER_ID=04708"
	* >Меню</a></li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/getmenuhtmlex.php">CMenu::GetMenuHtmlEx</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenuhtml.php">CMain::GetMenuHtml</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/getmenuhtmlex.php">CMain::GetMenuHtmlEx</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cmenu/getmenuhtml.php
	* @author Bitrix
	*/
	public function GetMenuHtml()
	{
		/**
		 * @global CAllMain $APPLICATION
		 * @noinspection PhpUnusedLocalVariableInspection
		 */
		global $USER, $DB, $APPLICATION; // must be!

		if(!$this->RecalcMenu())
			return false;

		// $arMENU - menu array copy
		// $arMENU_LINK - reference to menu array
		/** @noinspection PhpUnusedLocalVariableInspection */
		$arMENU_LINK = $MENU_ITEMS = &$this->arMenu;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$arMENU = $this->arMenu;

		$result = "";
		$sMenuPrologTmp = "";
		$sMenuEpilog = "";
		$n = count($this->arMenu);
		for($i = 0; $i < $n; $i++)
		{
			$m = $this->arMenu[$i];
			$sMenuBody = "";
			$sMenuProlog = "";
			$sMenuEpilog = "";
			$ITEM_INDEX = 0;
			extract($m, EXTR_OVERWRITE);

			// $TEXT - item text
			// $LINK - item link
			// $SELECTED - is item highlighed
			// $PERMISSION - linked page permission
			// $ADDITIONAL_LINKS - additional links for highlighting
			// $ITEM_TYPE - "D" - directory, "P" - page
			// $ITEM_INDEX - item number
			// $PARAMS - additional parameters

			include($this->MenuTemplate);

			if($ITEM_INDEX == 0)
				$sMenuPrologTmp = $sMenuProlog;
			$result .= $sMenuBody;
		}

		$result = $sMenuPrologTmp.$result.$sMenuEpilog;

		$arIcons = array();
		$bShowButtons = false;
		$sMenuFile = $this->MenuDir.".".$this->type.".menu.php";
		if($APPLICATION->GetShowIncludeAreas())
		{
			$menu_perm = $APPLICATION->GetFileAccessPermission($sMenuFile);
			$templ_perm = $APPLICATION->GetFileAccessPermission($this->template);
			if($menu_perm >= "W")
			{
				$arIcons[] = array(
					"URL"=>"/bitrix/admin/fileman_menu_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&path=".urlencode($this->MenuDir)."&name=".$this->type,
					"ICON"=>"menu-edit",
					"TITLE"=>GetMessage("MAIN_MENU_EDIT")
				);
			}

			if($templ_perm >= "W" && $USER->IsAdmin())
			{
				$arIcons[] = array(
					"URL"=>"/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&back_url=".urlencode($_SERVER["REQUEST_URI"])."&full_src=Y&path=".urlencode($this->template),
					"ICON"=>"menu-template",
					"TITLE"=>GetMessage("MAIN_MENU_TEMPLATE_EDIT")
				);
			}

			if(count($arIcons) > 0)
			{
				$result = $APPLICATION->IncludeStringBefore().$result;
				$bShowButtons = true;
			}
		}

		if($this->debug)
			$result .= $this->debug->Output($sMenuFile, $sMenuFile);

		if($bShowButtons)
			$result .= $APPLICATION->IncludeStringAfter($arIcons);

		return $result;
	}
}

class CMenuCustom
{
	var $arItems = array();

	public function AddItem($type="left", $arItem=array())
	{
		if (count($arItem) <= 0)
			return;

		if (!array_key_exists("TEXT", $arItem) || strlen(trim($arItem["TEXT"])) <= 0)
			return;

		if (!array_key_exists("LINK", $arItem) || strlen(trim($arItem["LINK"])) <= 0)
			$arItem["LINK"] = "";

		if (!array_key_exists("SELECTED", $arItem))
			$arItem["SELECTED"] = false;

		if (!array_key_exists("PERMISSION", $arItem))
			$arItem["PERMISSION"] = "R";

		if (!array_key_exists("DEPTH_LEVEL", $arItem))
			$arItem["DEPTH_LEVEL"] = 1;

		if (!array_key_exists("IS_PARENT", $arItem))
			$arItem["IS_PARENT"] = false;

		$this->arItems[$type][] = array(
						"TEXT" => $arItem["TEXT"],
						"LINK" => $arItem["LINK"],
						"SELECTED" => $arItem["SELECTED"],
						"PERMISSION" => $arItem["PERMISSION"],
						"DEPTH_LEVEL" => $arItem["DEPTH_LEVEL"],
						"IS_PARENT" => $arItem["IS_PARENT"],
					);
	}


	public function GetItems($type="left")
	{
		if (array_key_exists($type, $this->arItems))
			return $this->arItems[$type];
		else
			return false;
	}

}

global $BX_MENU_CUSTOM;
$BX_MENU_CUSTOM = new CMenuCustom;
