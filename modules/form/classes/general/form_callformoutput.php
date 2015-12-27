<?
/**
 * Form output class - templates management & final output
 *
 */

/**
 * <p>Если не предполагается использование страниц редактирования результата формы или просмотра списка результатов, то имеет смысл вставить в шаблон ответ веб-формы в обход основного шаблона:</p> <pre class="syntax" id="xmp4A9A280C"><buttononclick> &lt;!-- Если есть ответ формы - выведем его в обход шаблона --&gt; &lt;?if($FORM-&gt;isFormNote()):?&gt; &lt;?=$FORM-&gt;ShowFormNote()?&gt; &lt;?else:?&gt; &lt;!-- здесь остальной шаблон веб-формы --&gt; &lt;?endif?&gt; </buttononclick></pre> <br><br>
 *
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre>
 * <code><b>$FORM</b></code> - экземпляр класса <code>CFormOutput</code> - 
 * создаётся и инициализируется автоматически вне шаблона. Вызов методов 
 * <code>CFormOutput::ShowFormHeader()</code> и 
 * <code>CFormOutput::ShowFormFooter()</code> также добавляется к шаблону 
 * автоматически.
 * 
 * <buttononclick>
 * &lt;!-- Выведем описание формы --&gt;
 * &lt;table width="100%" cellpadding="2" cellspacing="0" border="0"&gt;
 *     &lt;tr&gt;
 *         &lt;td align="center"&gt;&lt;?=$FORM-&gt;ShowFormDescription()?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 * &lt;/table&gt;
 * 
 * &lt;!-- Если есть ошибки валидатора - выведем их --&gt;
 * &lt;?if($FORM-&gt;isFormErrors()):?&gt;
 * &lt;table width="100%" cellpadding="2" cellspacing="0" border="0"&gt;
 *     &lt;tr&gt;
 *         &lt;td&gt;&lt;?=$FORM-&gt;ShowFormErrors()?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 * &lt;/table&gt;
 * &lt;?endif?&gt;
 * 
 * &lt;!-- Выведем поля формы --&gt;
 * &lt;table width="100%" cellpadding="2" cellspacing="0" border="0"&gt;
 *     &lt;tr&gt;
 *         &lt;td width="40%" valign="top" align="right"&gt;&lt;?=$FORM-&gt;ShowInputCaption('test_q')?&gt;: &lt;/td&gt;
 *         &lt;td width="60%" valign="top"&gt;&lt;?=$FORM-&gt;ShowInput('test_q')?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 *     &lt;tr&gt;
 *         &lt;td valign="top" align="right"&gt;&lt;?=$FORM-&gt;ShowInputCaption('test_q_text')?&gt;: &lt;/td&gt;
 *         &lt;td valign="top"&gt;&lt;?=$FORM-&gt;ShowInput('test_q_text')?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 *     &lt;tr&gt;
 *         &lt;td valign="top" align="right"&gt;&lt;?=$FORM-&gt;ShowInputCaption('test_q_textarea')?&gt;: &lt;/td&gt;
 *         &lt;td valign="top"&gt;&lt;?=$FORM-&gt;ShowInput('test_q_textarea')?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 * &lt;/table&gt;
 * 
 * &lt;!-- Если используется CAPTCHA - выведем картинку и поле для ввода --&gt;
 * &lt;?if($FORM-&gt;isUseCaptcha()):?&gt;
 * &lt;table width="100%" cellpadding="2" cellspacing="0" border="0"&gt;
 *     &lt;tr&gt;
 *         &lt;td colspan="2" height="8"&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 *     &lt;tr&gt;
 *         &lt;td width="40%" valign="top" align="right" class="text"&gt;Защита от автоматической регистрации: &lt;/td&gt;
 *         &lt;td width="60%" valign="top"&gt;&lt;?=$FORM-&gt;ShowCaptchaImage()?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 *     &lt;tr&gt;
 *         &lt;td valign="top" align="right" class="text"&gt;Введите слово с картинки&lt;?=$FORM-&gt;ShowRequired()?&gt;: &lt;/td&gt;
 *         &lt;td valign="top"&gt;&lt;?=$FORM-&gt;ShowCaptchaField()?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 * &lt;/table&gt;
 * &lt;?endif?&gt;
 * 
 * &lt;!-- Выведем кнопки формы --&gt;
 * &lt;table width="100%" cellpadding="2" cellspacing="0" border="0"&gt;
 *     &lt;tr&gt;
 *         &lt;td width="40%"&gt;&amp;nbsp;&lt;/td&gt;
 *         &lt;td width="60%"&gt;
 *             &lt;?=$FORM-&gt;ShowSubmitButton()?&gt;&amp;nbsp;
 *             &lt;?=$FORM-&gt;ShowApplyButton()?&gt;&amp;nbsp;
 *             &lt;?=$FORM-&gt;ShowResetButton()?&gt;
 *         &lt;/td&gt;
 *     &lt;/tr&gt;
 * &lt;/table&gt;
 * </buttononclick>
 * 
 * Если не предполагается использование страниц редактирования результата формы 
 * или просмотра списка результатов, то имеет смысл вставить в шаблон ответ 
 * веб-формы в обход основного шаблона:
 * 
 * <buttononclick>
 * &lt;!-- Если есть ответ формы - выведем его в обход шаблона --&gt;
 * &lt;?if($FORM-&gt;isFormNote()):?&gt;
 * &lt;?=$FORM-&gt;ShowFormNote()?&gt;
 * &lt;?else:?&gt;
 * &lt;!-- здесь остальной шаблон веб-формы --&gt;
 * &lt;?endif?&gt;
 * </buttononclick>
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php
 * @author Bitrix
 */
class CAllFormOutput extends CFormOutput_old
{
	var $__cache_path = "";
	var $__cache_file_header = "<?if(!defined(\"B_PROLOG_INCLUDED\") || B_PROLOG_INCLUDED!==true)die();?><?=\$FORM->ShowFormHeader();?>";
	var $__cache_file_footer = "<?=\$FORM->ShowFormFooter();?>";

	var $__admin;

	var $WEB_FORM_ID;
	var $WEB_FORM_NAME;

	var $arParams;
	var $arForm;
	var $arQuestions;
	var $arAnswers;
	var $arDropDown;
	var $arMultiSelect;

	var $arrRESULT_PERMISSION = array();

	var $arrVALUES;

	var $RESULT_ID;
	var $arResult;

	var $strFormNote;

	var $F_RIGHT;
	var $CAPTCHACode;

	var $bSimple;

	var $__error_msg = "";
	var $__form_validate_errors = "";
	var $__cache_file_name;

	var $__form_image_cache = "";
	var $__form_image_path_cache = "";
	var $__form_input_caption_image_cache = array();
	var $__form_input_caption_image_path_cache = array();

	var $comp2 = false;

	var $bIsFormValidateErrors = false;

	public function CAllFormOutput()
	{
		$this->__cache_path = BX_PERSONAL_ROOT."/tmp/form";
	}

	public function InitializeTemplate($arParams, $arResult)
	{
		$this->WEB_FORM_ID = $arParams["WEB_FORM_ID"];
		$this->RESULT_ID = $arParams["RESULT_ID"];

		$this->arParams 	= $arParams;
		$this->arForm 		= $arResult["arForm"];
		$this->arQuestions 	= $arResult["arQuestions"];
		$this->arAnswers 	= $arResult["arAnswers"];
		$this->arDropDown 	= $arResult["arDropDown"];
		$this->arMultiSelect = $arResult["arMultiSelect"];

		$this->arrVALUES = $arResult["arrVALUES"];

		$this->F_RIGHT = $arResult["F_RIGHT"];
		if ($this->RESULT_ID)
		{
			if ($this->isAccessFormResult($arResult['arResultData']))
			{
				$this->arrRESULT_PERMISSION = CFormResult::GetPermissions($this->RESULT_ID, $v);
				$this->arResult = $arResult['arResultData'];
			}
		}

		$this->strFormNote = $arResult["FORM_NOTE"];
		$this->__form_validate_errors = $arResult["FORM_ERRORS"];
		$this->bIsFormValidateErrors = $arResult['isFormErrors'] == 'Y';

		$this->bSimple = (COption::GetOptionString("form", "SIMPLE", "Y") == "Y") ? true : false;

		$this->WEB_FORM_NAME = $arResult["arForm"]["SID"];

		if ($this->arForm["USE_CAPTCHA"] == "Y")
		{
			$this->CAPTCHACode = $arResult["CAPTCHACode"];
		}
	}

	public function IncludeFormCustomTemplate()
	{
		if ($this->__check_form_cache())
		{
			$FORM =& $this; // create interface for template
			ob_start();
			eval('?>'.$this->__cache_tpl.'<?');
			$strReturn = ob_get_contents();
			ob_end_clean();

			return $strReturn;
		}
		else
		{
			return false;
		}
	}

	public function IncludeFormTemplate()
	{
		global $APPLICATION;
		if ($this->__check_form_cache())
		{
			$APPLICATION->SetTemplateCSS("form/form.css");
			$FORM =& $this;
			eval($this->__cache_tpl);

			return true;
		}
		else
		{
			return false;
		}
	}

	public static function isStatisticIncluded()
	{
		return CModule::IncludeModule("statistic");
	}

	/**
	 * Private method used to check out for template and template cache file
	 * Returns true whether tpl file exists and puts its path to private
	 * property __cache_file_name. Otherwise returns false
	 *
	 * @return bool
	 */
	public function __check_form_cache()
	{
		global $CACHE_MANAGER;

		// if no tpl at all - return false
		if (strlen($this->arForm["FORM_TEMPLATE"]) <= 0 || $this->arForm["USE_DEFAULT_TEMPLATE"] != "N")
		{
			$this->arForm["USE_DEFAULT_TEMPLATE"] = "Y";
			return false;
		}

		$this->__cache_tpl = '';

		$cache_dir = '/form/templates/'.$this->arForm['ID'];
		$cache_id = 'form|template|'.$this->arForm['ID'];

		$obCache = new CPHPCache();

		if ($obCache->InitCache(30*86400, $cache_id, $cache_dir))
		{
			$res = $obCache->GetVars();
			$this->__cache_tpl = $res['FORM_TEMPLATE'];
		}
		else
		{
			$obCache->StartDataCache();

			$CACHE_MANAGER->StartTagCache($cache_dir);

			$CACHE_MANAGER->RegisterTag('forms');
			$CACHE_MANAGER->RegisterTag('form_'.$this->arForm['ID']);

			$this->__cache_tpl = $res['FORM_TEMPLATE'] = $this->__cache_file_header.$this->arForm['FORM_TEMPLATE'].$this->__cache_file_footer;

			$CACHE_MANAGER->EndTagCache();
			$obCache->EndDataCache(array('FORM_TEMPLATE' => $this->__cache_tpl));
		}

		return true;
	}

	/*
	public function __clear_form_cache_files()
	{
		$path = $_SERVER['DOCUMENT_ROOT'].$this->__cache_path;
		$fname_mask = "form_".$this->WEB_FORM_ID;

		if ($dh = @opendir($path))
		{
			while (($fname = @readdir($dh)) !== false)
			{
				if (substr($fname, 0, strlen($fname_mask)) == $fname_mask) @unlink($path."/".$fname);
			}
			closedir($dh);
		}
	}
	*/

	/**
	 * Public method used to check whether there were some form validation errors
	 * Use: <?if($FORM->isFormErrors()):?>There're some errors!<?endif?>
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "есть ли ошибки валидатора формы".</p>
	*
	*
	* @return bool <p><i>true</i>, если в процессе обработки результата формы обнаружены
	* ошибки. <i>false</i> в противном случае.</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?if($FORM-&gt;isFormErrors()):?&gt;Ошибки: &lt;?=$FORM-&gt;ShowFormErrors()?&gt;&lt;?endif?&gt; </pre></bod
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrors.php">CFormOutput::ShowFormErrors</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrorstext.php">CFormOutput::ShowFormErrorsText</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformerrors.php
	* @author Bitrix
	*/
	public function isFormErrors()
	{
		if (is_array($this->__form_validate_errors))
			return count($this->__form_validate_errors) > 0;
		else
			return strlen($this->__form_validate_errors) > 0;
	}

	/**
	 * Public method used to show formatted form errors
	 * Use: <?=$FORM->ShowFormErrors()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод отформатированных ошибок валидатора формы</p>
	*
	*
	* @return string <p>Возвращает отфоматированный список ошибок валидатора формы.
	* Если ошибок нет, возвращается пустая строка.</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax"> &lt;?=$FORM-&gt;ShowFormErrors()?&gt;
	* </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrorstext.php">CFormOutput::ShowFormErrorsText</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformerrors.php">CFormOutput::isFormErrors</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrors.php
	* @author Bitrix
	*/
	public function ShowFormErrors()
	{
		ob_start();

		if ($this->arParams['USE_EXTENDED_ERRORS'] == 'N')
			ShowError($this->__form_validate_errors);
		elseif (is_array($this->__form_validate_errors))
			ShowError(implode('<br />', $this->__form_validate_errors));

		$ret = ob_get_contents();
		ob_end_clean();

		return $ret;
	}

	/**
	 * Public method used to show unformatted form errors
	 * Use: <font color="red"><?=$FORM->ShowFormErrorsText()?></font>
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод неотформатированных ошибок валидатора формы</p>
	*
	*
	* @return string <p>Возвращает неотфоматированный список ошибок валидатора формы.
	* Если ошибок нет, возвращается пустая строка.</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">&lt;font
	* color="#FF0000"&gt;&lt;?=$FORM-&gt;ShowFormErrorsText()?&gt;&lt;/font&gt;</pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrors.php">CFormOutput::ShowFormErrors</a>
	* </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformerrors.php">CFormOutput::isFormErrors</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrorstext.php
	* @author Bitrix
	*/
	public function ShowFormErrorsText()
	{
		if ($this->arParams['USE_EXTENDED_ERRORS'] == 'N')
			return $this->__form_validate_errors;
		else
			return implode('<br />', $this->__form_validate_errors);
	}

	/**
	 * Public: shows form note formatted string if any (like 'Changes saved')
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод отформатированных информационных сообщений формы</p>
	*
	*
	* @return string <p>Возвращает отфоматированные информационные сообщения формы
	* (напр. "Ваша заявка принята"). Если сообщений нет, возвращается
	* пустая строка.</p> <a name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowFormNote()?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformnotetext.php">CFormOutput::ShowFormNoteText</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformnote.php">CFormOutput::isFormNote</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformnote.php
	* @author Bitrix
	*/
	public function ShowFormNote()
	{
		ob_start();
		ShowNote($this->strFormNote);
		$ob = ob_get_contents();
		ob_end_clean();
		return $ob;
	}

	/**
	 * Public: shows form note unformatted string if any (like 'Changes saved')
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод неотформатированных информационных сообщений формы</p>
	*
	*
	* @return string <p>Возвращает неотфоматированные информационные сообщения формы
	* (напр. "Ваша заявка принята"). Если сообщений нет, возвращается
	* пустая строка.</p> <a name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowFormNoteText()?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformnote.php">CFormOutput::ShowFormNoteText</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformnote.php">CFormOutput::isFormNote</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformnotetext.php
	* @author Bitrix
	*/
	public function ShowFormNoteText()
	{
		return $this->strFormNote;
	}

	/**
	 * Public: check whether form has note string (like 'Changes saved')
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "есть ли текстовые заметки".</p>
	*
	*
	* @return bool <p><i>true</i>, если у есть текстовые заметки. <i>false</i> в противном
	* случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?if($FORM-&gt;isFormNote()):?&gt;Ответ: &lt;?=$FORM-&gt;ShowFormNote()?&gt;&lt;?endif?&gt;
	* </b
	* \\ способ проверить, отправлена ли форма, а затем вывести сообщение об успешной отправке
	* &lt;? if($FORM-&gt;isFormNote()) //т.е. если сообщение есть, значит нужно его показать, т.е. форма отправлена
	* {
	* echo $FORM-&gt;ShowFormNote();?&gt; //выводим сообщение "Ваша заявка успешно отправлена"
	*  }
	* else //в противном случает выводим саму форму для заполнения
	* {
	* 
	* шаблон формы
	* ;}
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <p><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a><br><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformnote.php">CFormOutput::ShowFormNote</a></p></b
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformnote.php
	* @author Bitrix
	*/
	public function isFormNote()
	{
		return strlen($this->strFormNote) > 0;
	}

	/**
	 * Get current form runtime error code string
	 * use $MESS from lang file to customize error messages
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает код ошибки инициализации формы</p>
	*
	*
	* @return string <p>Метод возвращает одну из следующих строк, либо пустую строку,
	* если ошибок нет</p> <table class="tnormal" width="100%"> <tr> <th width="18%">Строка</th> <th
	* width="82%">Описание</th> </tr> <tr> <td>FORM_NOT_FOUND</td> <td>Формы с переданным WEB_FORM_ID
	* не существует</td> </tr> <tr> <td>FORM_ACCESS_DENIED</td> <td>Не хватает прав доступа к
	* форме</td> </tr> </table> <p>Проверка наличия ошибки и вывод
	* соответствующего ей языкового сообщения производится
	* автоматически при инициализации формы</p>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrors.php">CFormOutput::ShowFormErrors</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformerrorstext.php">CFormOutput::ShowFormErrorsText</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showerrormsg.php
	* @author Bitrix
	*/
	public function ShowErrorMsg()
	{
		return $this->__error_msg;
	}

	/**
	 * Public method used to put form header (<form> tag and hidden fields)
	 * Added to form template automatically
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод HTML-заголовка формы</p>
	*
	*
	* @return string <p>Возвращает HTML-код заголовка формы. В том числе, тэг &lt;form&gt;,
	* скрытые поля.</p> <a name="examples"></a><h4>Использование</h4> <p>При создании
	* шаблона формы редактором, добавляется в начало шаблона
	* автоматически.</p> <pre class="syntax"> &lt;?=$FORM-&gt;ShowFormHeader()?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformfooter.php">CFormOutput::ShowFormFooter</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformheader.php
	* @author Bitrix
	*/
	public function ShowFormHeader()
	{
		global $APPLICATION;

		$res = sprintf(
			"<form name=\"%s\" action=\"%s\" method=\"%s\" enctype=\"multipart/form-data\">",
			$this->arForm["SID"],
			//$APPLICATION->GetCurPage(),
			POST_FORM_ACTION_URI,
			"POST"
		);

		$res .= bitrix_sessid_post();

		$arHiddenInputs["WEB_FORM_ID"] = $this->WEB_FORM_ID;
		if (!empty($this->RESULT_ID)) $arHiddenInputs["RESULT_ID"] = $this->RESULT_ID;
		$arHiddenInputs["lang"] = LANGUAGE_ID;

		foreach ($arHiddenInputs as $name => $value)
		{
			$res .= sprintf(
			"<input type=\"hidden\" name=\"%s\" value=\"%s\" />",
			$name, $value
			);
		}

		return $res;
	}

	/**
	 * Public method used to put form footer (end <form> tag)
	 * Added to form template automatically
	 *
	 * @return string
	 */
	
	/**
	* <p>Завершение вывода формы</p>
	*
	*
	* @return string <p>Возвращает завершающий HTML-код формы. В том числе, тэг &lt;/form&gt;.</p>
	* <a name="examples"></a><h4>Использование</h4> <p>При создании шаблона формы
	* редактором, добавляется в конец шаблона автоматически.</p> <pre
	* class="syntax">&lt;?=$FORM-&gt;ShowFormFooter()?&gt;</pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a>
	* </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformheader.php">CFormOutput::ShowFormHeader</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformfooter.php
	* @author Bitrix
	*/
	public static function ShowFormFooter()
	{
		return "</form>";
	}

	public function __admin_GetInputType($FIELD_SID)
	{
		if (is_array($this->arAnswers[$FIELD_SID]))
		{
			$type = "";
			foreach ($this->arAnswers[$FIELD_SID] as $key=>$arAnswer)
			{
				if ($type == "")
				{
					$type = $arAnswer["FIELD_TYPE"];
				}
				elseif ($type != $arAnswer["FIELD_TYPE"])
					return "multiple";
			}

			return $type;
		}
		else return "none";
	}

	public function __admin_GetInputAnswersStructure($FIELD_SID)
	{
		if (is_array($this->arAnswers[$FIELD_SID]))
		{
			$out = array();
			$csort_max = 0;
			foreach ($this->arAnswers[$FIELD_SID] as $key => $arAnswer)
			{
				$last = $arAnswer;
				if ($csort_max < $arAnswer["C_SORT"]) $csort_max = $arAnswer["C_SORT"];
				$ans = array();
				foreach ($arAnswer as $key=>$value)
				{
					$ans[] = $key.":'".CUtil::JSEscape($value)."'";
				}

				$ans[] = "ANS_NEW:false";

				$out[] = "{".implode(",", $ans)."}";
			}

			$imax = 0;
			if (in_array($last['FIELD_TYPE'], array('checkbox', 'dropdown', 'multiselect', 'radio'))) $imax = 5;
			for ($i=0; $i<$imax; $i++)
			{
				$ans = array();
				$csort_max += 100;

				foreach ($last as $key=>$value)
				{
					if ($key == "ACTIVE")
						$ans[] = $key.":'Y'";
					elseif ($key == "C_SORT")
						$ans[] = $key.":'".$csort_max."'";
					else
						$ans[] = $key.":'".(in_array($key, array('FIELD_TYPE', 'FIELD_ID', 'QUESTION_ID')) ? CUtil::JSEscape($value) : "")."'";
				}

				$ans[] = "ANS_NEW:true";

				$out[] = "{".implode(",", $ans)."}";
			}

			return "[".implode(",", $out)."]";
		}
		else
			return "[]";
	}

	/**
	 * Public method used to put input field title to template
	 * Use: <?=$FORM->ShowInputCaption('MYFIELD_5')?>
	 *
	 * @param string $FIELD_SID
	 * @param string $caption_css_class
	 * @return string
	 */
	
	/**
	* <p>Вставка в шаблон подписи поля ответа на вопрос.</p>
	*
	*
	* @param string $FIELD_SID  Строковой идентификатор поля вопроса. Обязательный параметр.
	*
	* @param  $string  CSS-класс подписи. Необязательный параметр. Если для выставлено
	* значение "Текст вопроса - HTML", то параметр игнорируется. До версии
	* 5.1.2 значение по умолчанию - "tablebodytext".
	*
	* @param mixed $CSSClass = ""] 
	*
	* @return string <p>Возвращается обработанная подпись поля формы.</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowInputCaption('MYFIELD_5')?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinput.php">CFormOutput::ShowInput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaptionimage.php">CFormOutput::ShowInputCaptionImage</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaption.php
	* @author Bitrix
	*/
	public function ShowInputCaption($FIELD_SID, $css_style = "")
	{
		$ret = "";
		if (empty($this->arQuestions[$FIELD_SID])) $ret = "";
		else
		{
			if ($this->arQuestions[$FIELD_SID]["TITLE_TYPE"]=="html")
			{
				$ret = $this->arQuestions[$FIELD_SID]["TITLE"].CForm::ShowRequired($this->arQuestions[$FIELD_SID]["REQUIRED"]);
			}
			else
			{
				if ($this->arQuestions[$FIELD_SID]["ADDITIONAL"]=="Y")
				{
					$ret = "<b>".$this->arQuestions[$FIELD_SID]["TITLE"]."</b>".CForm::ShowRequired($this->arQuestions[$FIELD_SID]["REQUIRED"]);
				}
				else
				{
					$ret = htmlspecialcharsbx($this->arQuestions[$FIELD_SID]["TITLE"]).CForm::ShowRequired($this->arQuestions[$FIELD_SID]["REQUIRED"]);
				}
			}
		}

		if (strlen($css_style) > 0) $ret = "<span class=\"".$css_style."\">".$ret."</span>";

		if (is_array($this->__form_validate_errors) && array_key_exists($FIELD_SID, $this->__form_validate_errors))
			$ret = '<span class="form-error-fld" title="'.htmlspecialcharsbx($this->__form_validate_errors[$FIELD_SID]).'"></span>'."\r\n".$ret;

		return $ret;
	}


	public function __admin_ShowInputCaption($FIELD_SID, $caption_css_class = "", $unform = false)
	{
		if (empty($this->arQuestions[$FIELD_SID])) return "";
		if ($unform) return $this->arQuestions[$FIELD_SID]["TITLE"];
		if ($this->arQuestions[$FIELD_SID]["TITLE_TYPE"]=="html")
		{
			return $this->arQuestions[$FIELD_SID]["TITLE"]. CForm::ShowRequired($this->arQuestions[$FIELD_SID]["REQUIRED"]);
		}
		else
		{
			if ($this->arQuestions[$FIELD_SID]["ADDITIONAL"]=="Y")
			{
				return "<span class=\"".$caption_css_class."\"><b>".$this->arQuestions[$FIELD_SID]["TITLE"]."</b></span>".CForm::ShowRequired($this->arQuestions[$FIELD_SID]["REQUIRED"]);
			}
			else
			{
				return "<span class=\"".$caption_css_class."\">".$this->arQuestions[$FIELD_SID]["TITLE"]."</span>". CForm::ShowRequired($this->arQuestions[$FIELD_SID]["REQUIRED"]);
			}
		}
	}


	/**
	 * Public method used to put question image if exists onto form
	 * Use: <?=$FORM->ShowInputCaptionImage('MYFIELD_5', 50, 50, "hspace=\"0\" vspace=\"0\" align=\"left\" border=\"0\"", "", true, GetMessage("FORM_ENLARGE"))?>
	 * params like CFile::ShowImage()
	 * Returns image code if image exists and empty string otherwise
	 *
	 * @param string $FIELD_SID
	 * @param int $iMaxW
	 * @param int $iMaxH
	 * @param string $sParams
	 * @param string $strImageUrl
	 * @param bool $bPopup
	 * @param string $strPopupTitle

	 * @return string
	 */
	
	/**
	* <p>Вывод изображения, прикрепленного к вопросу формы. Если изображение есть, возвращается HTML-код вставки. В противном случае - пустая строка.</p>
	*
	*
	* @param string $FIELD_SID  Строковой идентификатор поля вопроса. Обязательный параметр.
	*
	* @param  $string  Расположение изображения относительно текста. Может принимать
	* одно из четырех значений - <code>LEFT</code>, <code>CENTER</code>, <code>RIGHT</code>
	* (регистр не имеет значения) или пустое. Необязательный параметр.
	*
	* @param mixed $sAlign = "" Максимальная ширина изображения. Если ширина картинки больше iMaxW,
	* то она будет пропорционально смаштабирована.<br> Необязательный.
	* До версии 5.1.2 значение по умолчанию - "0" - без ограничений.
	*
	* @param int $iMaxW = 0 Максимальная высота изображения. Если высота картинки больше iMaxH,
	* то она будет пропорционально смаштабирована.<br> Необязательный.
	* До версии 5.1.2 значение по умолчанию - "0" - без ограничений.
	*
	* @param int $iMaxH = 0 Открывать ли при клике на изображении дополнительное popup окно с
	* увеличенным изображением.<br> Необязательный. Должен приниметь
	* одно из двух значений - "Y" или "N" (с учётом регистра). По умолчанию -
	* "N" (до версии 5.1.2 - "false").
	*
	* @param string $bPopup = "N" Текст всплывающей подсказки на изображении (только если <i>bPopup</i> =
	* "Y")<br> Необязательный. По умолчанию выводится фраза "Нажмите чтобы
	* увеличить" на языке страницы (до версии 5.1.2 - "false").
	*
	* @param string $strPopupTitle = "" Устанавливает вертикальный отступ картинки от окружающего
	* текста в пикселях.<br> Необязательный. По умолчанию - "0" - без
	* отступа.
	*
	* @param string $sHSpace = "" Устанавливает горизонтальный отступ картинки от окружающего
	* текста в пикселях.<br> Необязательный. По умолчанию - "0" - без
	* отступа.
	*
	* @param string $sVSpace = "" Устанавливает толщину рамки вокруг изображения. Необязательный.
	* По умолчанию равен "0" - без рамки:
	*
	* @param string $sBorder = "" 
	*
	* @return string <p>Возвращает HTML-код для вставки изображения в форму</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowInputCaptionImage('MYFIELD_5', 'LEFT', 50, 50, "N", "", 5, 5)?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/showimage.php">CFile::ShowImage</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinput.php">CFormOutput::ShowInput</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaption.php">CFormOutput::ShowInputCaption</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isinputcaptionimage.php">CFormOutput::isInputCaptionImage</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaptionimage.php
	* @author Bitrix
	*/
	public function ShowInputCaptionImage($FIELD_SID, $sAlign = "", $iMaxW="", $iMaxH="", $bPopup="N", $strPopupTitle="", $sHSpace = "", $sVSpace = "", $sBorder = "")
	{
		if ($this->isInputCaptionImage($FIELD_SID))
		{
			$arImageParams = array();

			if (strlen($sAlign) > 0) $arImageParams[] = sprintf("align=\"%s\"", $sAlign);
			if (strlen($sHSpace) > 0) $arImageParams[] = sprintf("hspace=\"%s\"", $sHSpace);
			if (strlen($sVSpace) > 0) $arImageParams[] = sprintf("vspace=\"%s\"", $sVSpace);
			if (strlen($sBorder) > 0) $arImageParams[] = sprintf("border=\"%s\"", $sBorder);
			else $arImageParams[] = "border=\"0\"";

			if (strlen($strPopupTitle) <= 0) $strPopupTitle = false;

			if (empty($this->__form_input_caption_image_cache[$FIELD_SID]))
			{
				$this->__form_input_caption_image_cache[$FIELD_SID] = CFile::ShowImage($this->arQuestions[$FIELD_SID]["IMAGE_ID"], $iMaxW, $iMaxH, implode(" ", $arImageParams), $strImageUrl, $bPopup == "Y", $strPopupTitle);
			}

			$ret = $this->__form_input_caption_image_cache[$FIELD_SID];

			if (strtoupper($sAlign) == "CENTER") $ret = "<div align=\"center\">".$ret."</div>";

			return $ret;
		}
		else
		{
			return "";
		}
	}

	/**
	 * Public method used to check wheter current question has image
	 * Use: <?=($FORM->isInputCaptionImage('MYFIELD_5') ? "image: ".$FORM->ShowInputCaptionImage('MYFIELD_5') : "no image")?>
	 *
	 * @param string $FIELD_SID
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "прикреплена ли к вопросу с данным идентификатором картинка".</p>
	*
	*
	* @param string $FIELD_SID  Строковой идентификатор поля вопроса. Обязательный параметр.
	*
	* @return bool <p><i>true</i>, если к вопросу прикреплена картинка. <i>false</i> в противном
	* случае.</p> <a name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowInputCaption('MYFIELD_5')?&gt;: &lt;?=$FORM-&gt;ShowInput('MYFIELD_5')?&gt;&lt;br /&gt;
	* &lt;?if($FORM-&gt;isInputCaptionImage('MYFIELD_5')):?&gt; &lt;?=$FORM-&gt;ShowInputCaptionImage('MYFIELD_5')?&gt;
	* &lt;?else:?&gt; &lt;?=CFile::ShowImage("/myimages/form_field_default.jpg")?&gt; &lt;?endif?&gt;: </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/showimage.php">CFile::ShowImage</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinput.php">CFormOutput::ShowInput</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaption.php">CFormOutput::ShowInputCaption</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaptionimage.php">CFormOutput::ShowInputCaptionImage</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isinputcaptionimage.php
	* @author Bitrix
	*/
	public function isInputCaptionImage($FIELD_SID)
	{
		return intval($this->arQuestions[$FIELD_SID]["IMAGE_ID"])>0;
	}

	/**
	 * Public method used to put input fields to template
	 * Use: <?=$FORM->ShowInput('MYFIELD_5')?>
	 *
	 * @param string $FIELD_SID
	 * @param string $caption_css_class
	 * @return string
	 */
	
	/**
	* <p>Вставка полей ответа на вопрос в шаблон. Параметры поля ввода задаются в настройках вопроса.</p>
	*
	*
	* @param string $FIELD_SID  Строковой идентификатор поля вопроса. Обязательный параметр.
	*
	* @param  $string  CSS-класс для подписи к полю ввода. Необязательный параметр.
	*
	* @param mixed $CSSClass = ""] 
	*
	* @return string <p>Возвращается HTML-код для вставки полей формы</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax"> &lt;?=$FORM-&gt;ShowInput('MYFIELD_5')?&gt;
	* </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaption.php">CFormOutput::ShowInputCaption</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaptionimage.php">CFormOutput::ShowInputCaptionImage</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinput.php
	* @author Bitrix
	*/
	public function ShowInput($FIELD_SID, $caption_css_class = '')
	{
		$arrVALUES = $this->arrVALUES;

		if (is_array($this->arAnswers[$FIELD_SID]))
		{
			$res = "";

			reset($this->arAnswers[$FIELD_SID]);
			if (is_array($this->arDropDown[$FIELD_SID])) reset($this->arDropDown[$FIELD_SID]);
			if (is_array($this->arMutiselect[$FIELD_SID])) reset($this->arMutiselect[$FIELD_SID]);

			while (list($key, $arAnswer) = each($this->arAnswers[$FIELD_SID]))
			{
				if ($arAnswer["FIELD_TYPE"]=="dropdown" && $show_dropdown=="Y") continue;
				if ($arAnswer["FIELD_TYPE"]=="multiselect" && $show_multiselect=="Y") continue;

				if ($key > 0) $res .= "<br />";

				switch ($arAnswer["FIELD_TYPE"])
				{
					case "radio":
						$ans_id = "form_checkbox_".$FIELD_SID."_".$arAnswer['ID'];
						$arAnswer['FIELD_PARAM'] .= ' id="'.$ans_id.'"';

						$value = CForm::GetRadioValue($FIELD_SID, $arAnswer, $arrVALUES);
						$input = CForm::GetRadioField(
							$FIELD_SID,
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_PARAM"]
						);

						if (strlen($ans_id) > 0)
						{
							$res .= $input;
							$res .= "<label for=\"".$ans_id."\">";
							$res .= "<span class=\"".$caption_css_class."\">&nbsp;".$arAnswer["MESSAGE"]."</span></label>";
						}
						else
						{
							$res .= "<label>";
							$res .= $input;
							$res .= "<span class=\"".$caption_css_class."\">&nbsp;".$arAnswer["MESSAGE"]."</span></label>";
						}

						break;
					case "checkbox":

						$ans_id = "form_checkbox_".$FIELD_SID."_".$arAnswer['ID'];
						$arAnswer['FIELD_PARAM'] .= ' id="'.$ans_id.'"';

						$value = CForm::GetCheckBoxValue($FIELD_SID, $arAnswer, $arrVALUES);
						$input = CForm::GetCheckBoxField(
							$FIELD_SID,
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_PARAM"]
						);

						if (strlen($ans_id) > 0)
						{
							$res .= $input;
							$res .= "<label for=\"".$ans_id."\">";
							$res .= "<span class=\"".$caption_css_class."\">&nbsp;".$arAnswer["MESSAGE"]."</span></label>";
						}
						else
						{
							$res .= "<label>";
							$res .= $input;
							$res .= "<span class=\"".$caption_css_class."\">&nbsp;".$arAnswer["MESSAGE"]."</span></label>";
						}

						break;
					case "dropdown":
						if ($show_dropdown!="Y")
						{
							$value = CForm::GetDropDownValue($FIELD_SID, $this->arDropDown, $arrVALUES);
							$res .= CForm::GetDropDownField(
								$FIELD_SID,
								$this->arDropDown[$FIELD_SID],
								$value,
								$arAnswer["FIELD_PARAM"]);
							$show_dropdown = "Y";
						}
						break;
					case "multiselect":
						if ($show_multiselect!="Y")
						{
							$value = CForm::GetMultiSelectValue($FIELD_SID, $this->arMultiSelect, $arrVALUES);
							$res .= CForm::GetMultiSelectField(
								$FIELD_SID,
								$this->arMultiSelect[$FIELD_SID],
								$value,
								$arAnswer["FIELD_HEIGHT"],
								$arAnswer["FIELD_PARAM"]);
							$show_multiselect = "Y";
						}
						break;
					case "text":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}

						$value = CForm::GetTextValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetTextField(
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_WIDTH"],
							$arAnswer["FIELD_PARAM"]);
						break;

					case "hidden":
						/*
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}
						*/

						$value = CForm::GetHiddenValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetHiddenField(
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_PARAM"]);
						break;

					case "password":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}
						$value = CForm::GetPasswordValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetPasswordField(
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_WIDTH"],
							$arAnswer["FIELD_PARAM"]);
						break;
					case "email":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}
						$value = CForm::GetEmailValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetEmailField(
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_WIDTH"],
							$arAnswer["FIELD_PARAM"]);
						break;
					case "url":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}
						$value = CForm::GetUrlValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetUrlField(
							$arAnswer["ID"],
							$value,
							$arAnswer["FIELD_WIDTH"],
							$arAnswer["FIELD_PARAM"]);
						break;
					case "textarea":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}
						$value = CForm::GetTextAreaValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetTextAreaField(
							$arAnswer["ID"],
							$arAnswer["FIELD_WIDTH"],
							$arAnswer["FIELD_HEIGHT"],
							$arAnswer["FIELD_PARAM"],
							$value
							);
						break;
					case "date":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]." (".CSite::GetDateFormat("SHORT").")</span><br />";
						}
						$value = CForm::GetDateValue($arAnswer["ID"], $arAnswer, $arrVALUES);
						$res .= CForm::GetDateField(
							$arAnswer["ID"],
							$this->arForm["SID"],
							$value,
							$arAnswer["FIELD_WIDTH"],
							$arAnswer["FIELD_PARAM"]);
						break;
					case "image":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}

						if ($this->RESULT_ID)
						{
							if ($arFile = CFormResult::GetFileByAnswerID($this->RESULT_ID, $arAnswer["ID"]))
							{
								if (intval($arFile["USER_FILE_ID"])>0)
								{
									if ($arFile["USER_FILE_IS_IMAGE"]=="Y")
									{
										$res .= CFile::ShowImage($arFile["USER_FILE_ID"], 0, 0, "border=0", "", true);
										$res .= "<br />";
									} //endif;
								} //endif;
							} // endif
						} // endif

						$res .= CForm::GetFileField(
							$arAnswer["ID"],
							$arAnswer["FIELD_WIDTH"],
							"IMAGE",
							0,
							"",
							$arAnswer["FIELD_PARAM"]);
						break;
					case "file":
						if (strlen(trim($arAnswer["MESSAGE"]))>0)
						{
							$res .= "<span class=\"".$caption_css_class."\">".$arAnswer["MESSAGE"]."</span><br />";
						}

						if ($this->RESULT_ID)
						{
							if ($arFile = CFormResult::GetFileByAnswerID($this->RESULT_ID, $arAnswer["ID"]))
							{
								if (intval($arFile["USER_FILE_ID"])>0)
								{
									$res .= "<a title=\"".GetMessage("FORM_VIEW_FILE")."\" target=\"_blank\" class=\"tablebodylink\" href=\"/bitrix/tools/form_show_file.php?rid=".$this->RESULT_ID."&hash=".$arFile["USER_FILE_HASH"]."&lang=".LANGUAGE_ID."\">".htmlspecialcharsbx($arFile["USER_FILE_NAME"])."</a>&nbsp;(";
									$res .= CFile::FormatSize($arFile["USER_FILE_SIZE"]);
									$res .= ")&nbsp;&nbsp;[&nbsp;<a title=\"".str_replace("#FILE_NAME#", $arFile["USER_FILE_NAME"], GetMessage("FORM_DOWNLOAD_FILE"))."\" class=\"tablebodylink\" href=\"/bitrix/tools/form_show_file.php?rid=".$this->RESULT_ID."&hash=".$arFile["USER_FILE_HASH"]."&lang=".LANGUAGE_ID."&action=download\">".GetMessage("FORM_DOWNLOAD")."</a>&nbsp;]";
									$res .= "<br /><br />";
								} //endif;
							} //endif;
						}

						$res .= CForm::GetFileField(
							$arAnswer["ID"],
							$arAnswer["FIELD_WIDTH"],
							"FILE",
							0,
							"",
							$arAnswer["FIELD_PARAM"]);
						break;
				} //endswitch;
			} //endwhile;

			return $res;
		} //endif(is_array($arAnswers[$FIELD_SID]));
		elseif (is_array($this->arQuestions[$FIELD_SID]) && $this->arQuestions[$FIELD_SID]["ADDITIONAL"] == "Y")
		{
			$res = "";
			switch ($this->arQuestions[$FIELD_SID]["FIELD_TYPE"])
			{
				case "text":
					$value = CForm::GetTextAreaValue("ADDITIONAL_".$this->arQuestions[$FIELD_SID]["ID"], array(), $this->arrVALUES);
					$res .= CForm::GetTextAreaField(
						"ADDITIONAL_".$this->arQuestions[$FIELD_SID]["ID"],
						"60",
						"5",
						"",
						$value
						);
					break;
				case "integer":
					$value = CForm::GetTextValue("ADDITIONAL_".$this->arQuestions[$FIELD_SID]["ID"], array(), $this->arrVALUES);
					$res .= CForm::GetTextField(
						"ADDITIONAL_".$this->arQuestions[$FIELD_SID]["ID"],
						$value);
					break;
				case "date":
					$value = CForm::GetDateValue("ADDITIONAL_".$this->arQuestions[$FIELD_SID]["ID"], array(), $this->arrVALUES);
					$res .= CForm::GetDateField(
						"ADDITIONAL_".$this->arQuestions[$FIELD_SID]["ID"],
						$arForm["SID"],
						$value);
					break;
			} //endswitch;

			return $res;
		}
		else return "";
	}

	/**
	 * Public method used to check whether current form uses captcha.
	 * Use: <?if($FORM->isUseCaptcha()):?>form uses CAPTCHA<?else:?>form doesnt use CAPTCHA<?endif;?>
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "форма использует CAPTCHA".</p>
	*
	*
	* @return bool <p><i>true</i>, если форма использует CAPTCHA. <i>false</i> в противном случае.</p>
	* <a name="examples"></a><h4>Использование</h4> <pre class="syntax">&lt;?if($FORM-&gt;isUseCaptcha()):?&gt;
	* &lt;tr&gt; &lt;td colspan="2" height="8"&gt;&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td valign="top" align="right"
	* class="text"&gt;Защита от автоматической регистрации:&lt;/td&gt; &lt;td
	* valign="top"&gt;&lt;?=$FORM-&gt;ShowCaptchaImage()?&gt;&lt;/td&gt; &lt;/tr&gt; &lt;tr&gt; &lt;td valign="top"
	* align="right" class="text"&gt;Введите слово с
	* картинки&lt;?=$FORM-&gt;ShowRequired()?&gt;:&lt;/td&gt; &lt;td
	* valign="top"&gt;&lt;?=$FORM-&gt;ShowCaptchaField()?&gt;&lt;/td&gt; &lt;/tr&gt; &lt;?endif?&gt;</pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li> <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptcha.php">CFormOutput::ShowCaptcha</a> </li> <li>
	* <a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchafield.php">CFormOutput::ShowCaptchaField</a>
	* </li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchaimage.php">CFormOutput::ShowCaptchaImage</a></li>
	* </menu></b<br><br><h4>Смотрите также</h4> <ul><li><a
	* href="http://dev.1c-bitrix.ru/community/webdev/user/61475/blog/updated-without-a-page-reload-captcha/">Обновление
	* капчи без перезагрузки страницы</a></li></ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isusecaptcha.php
	* @author Bitrix
	*/
	public function isUseCaptcha()
	{
		return $this->arForm["USE_CAPTCHA"] == "Y" && strlen($this->CAPTCHACode) > 0;
	}

	/**
	 * Public method used to put CAPTCHA image onto form.
	 * Use: <?=$FORM->ShowCaptchaImage()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает HTML-код для вставки изображения CAPTCHA</p>
	*
	*
	* @return string <p>Возвращается HTML-код для вставки изображения CAPTCHA</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax"> &lt;?=$FORM-&gt;ShowCaptchaImage()?&gt;
	* </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptcha.php">CFormOutput::ShowCaptcha</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchafield.php">CFormOutput::ShowCaptchaField</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isusecaptcha.php">CFormOutput::isUseCaptcha</a></li>
	* </menu></b<br><br><h4>Смотрите также</h4> <ul><li><a
	* href="http://dev.1c-bitrix.ru/community/webdev/user/61475/blog/updated-without-a-page-reload-captcha/">Обновление
	* капчи без перезагрузки страницы</a></li></ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchaimage.php
	* @author Bitrix
	*/
	public function ShowCaptchaImage()
	{

		if ($this->isUseCaptcha())
			return "<input type=\"hidden\" name=\"captcha_sid\" value=\"".htmlspecialcharsbx($this->CAPTCHACode)."\" /><img src=\"/bitrix/tools/captcha.php?captcha_sid=".htmlspecialcharsbx($this->CAPTCHACode)."\" width=\"180\" height=\"40\" />";
		else return "";
	}

	/**
	 * Public method used to put CAPTCHA input field onto form.
	 * Use: <?=$FORM->ShowCaptchaField()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает код поля для ввода CAPTCHA</p>
	*
	*
	* @return string <p>Возвращается HTML-код поля для ввода CAPTCHA</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax"> &lt;?=$FORM-&gt;ShowCaptchaField()?&gt;
	* </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptcha.php">CFormOutput::ShowCaptcha</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchaimage.php">CFormOutput::ShowCaptchaImage</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isusecaptcha.php">CFormOutput::isUseCaptcha</a></li>
	* </menu></b<br><br><h4>Смотрите также</h4> <ul><li><a
	* href="http://dev.1c-bitrix.ru/community/webdev/user/61475/blog/updated-without-a-page-reload-captcha/">Обновление
	* капчи без перезагрузки страницы</a></li></ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchafield.php
	* @author Bitrix
	*/
	public function ShowCaptchaField()
	{
		if ($this->isUseCaptcha())
			return "<input type=\"text\" name=\"captcha_word\" size=\"30\" maxlength=\"50\" value=\"\" class=\"inputtext\" />";
		else return "";
	}

	/**
	 * Public: show both CAPTCHA fields with default formating
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает комбинацию изображения CAPTCHA и поля для ввода</p>
	*
	*
	* @return string <p>Возвращается HTML-код обоих элементов</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax"> &lt;?=$FORM-&gt;ShowCaptcha()?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchafield.php">CFormOutput::ShowCaptchaField</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptchaimage.php">CFormOutput::ShowCaptchaImage</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isusecaptcha.php">CFormOutput::isUseCaptcha</a></li>
	* </menu></b<br><br><h4>Смотрите также</h4> <ul><li><a
	* href="http://dev.1c-bitrix.ru/community/webdev/user/61475/blog/updated-without-a-page-reload-captcha/">Обновление
	* капчи без перезагрузки страницы</a></li></ul> <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showcaptcha.php
	* @author Bitrix
	*/
	public function ShowCaptcha()
	{
		return $this->ShowCaptchaImage()."<br />".$this->ShowCaptchaField();
	}

	/**
	 * Public method used to put submit button onto form.
	 * Use: <?=$FORM->ShowSubmitButton()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает HTML-код кнопки отправки формы создания/редактирования записи</p>
	*
	*
	* @param  $string  Текст, расположенный на кнопке. Если параметр пуст или не указан,
	* то будет использовано значение параметра "Подпись на кнопке"
	* вкладки "Свойства" страницы редактирования параметров веб-формы,
	* либо значение по умолчанию. Необязательный параметр.
	*
	* @param CAPTIO $N = "" CSS-класс кнопки. Необязательный параметр.
	*
	* @param string $CSSClass = "" 
	*
	* @return string <p>Возвращается HTML-код кнопки отправки формы</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowSubmitButton("Отправить заявку", "form-button-submit")?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showapplybutton.php">CFormOutput::ShowApplyButton</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showresetbutton.php">CFormOutput::ShowResetButton</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showsubmitbutton.php
	* @author Bitrix
	*/
	public function ShowSubmitButton($caption = "", $css_style = "")
	{
		$button_value = strlen(trim($caption)) > 0 ? trim($caption) : (strlen(trim($this->arForm["BUTTON"]))<=0 ? GetMessage("FORM_ADD") : $this->arForm["BUTTON"]);

		return "<input ".(intval($this->F_RIGHT)<10 ? "disabled" : "")." type=\"submit\" name=\"web_form_submit\" value=\"".htmlspecialcharsbx($button_value)."\"".(!empty($css_style) ? " class=\"".$css_style."\"" : "")." />";
	}

	/**
	 * Public method used to put apply button onto form.
	 * Use: <?=$FORM->ShowApplyButton()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает HTML-код кнопки "Применить" формы создания/редактирования записи</p>
	*
	*
	* @param  $string  Текст, расположенный на кнопке. Необязательный параметр.
	* Параметр необязательный, и если он пуст или не указан, то будет
	* использовано значение по умолчанию.
	*
	* @param CAPTIO $N = "" CSS-класс кнопки. Необязательный параметр.
	*
	* @param string $CSSClass = "" 
	*
	* @return string <p>Возвращается HTML-код кнопки "Применить"</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowApplyButton("Применить", "form-button-apply")?&gt; </pre></bod
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showresetbutton.php">CFormOutput::ShowResetButton</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showsubmitbutton.php">CFormOutput::ShowSubmitButton</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showapplybutton.php
	* @author Bitrix
	*/
	public function ShowApplyButton($caption = "", $css_style = "")
	{
		$button_value = strlen(trim($caption)) > 0 ? trim($caption) : GetMessage("FORM_APPLY");

		return "<input type=\"hidden\" name=\"web_form_apply\" value=\"Y\" /><input ".((intval($this->F_RIGHT)<10) ? "disabled" : "")." type=\"submit\" name=\"web_form_apply\" value=\"".htmlspecialcharsbx($button_value)."\"".(!empty($css_style) ? " class=\"".$css_style."\"" : "")." />";
	}

	/**
	 * Public method used to put reset button onto form.
	 * Use: <?=$FORM->ShowResetButton()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Возвращает HTML-код кнопки "Сбросить" формы создания/редактирования записи.</p>
	*
	*
	* @param  $string  Текст, расположенный на кнопке. Если параметр пуст или не указан,
	* то будет использовано значение по умолчанию. Необязательный
	* параметр.
	*
	* @param CAPTIO $N = "" CSS-класс кнопки. Необязательный параметр.
	*
	* @param string $CSSClass = "" 
	*
	* @return string <p>Возвращается HTML-код кнопки "сбросить".</p> <a
	* name="examples"></a><h4>Использование</h4> <pre
	* class="syntax">&lt;?=$FORM-&gt;ShowResetButton("Отменить изменения", "form-button-reset")?&gt; </pre>
	* </h
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showapplybutton.php">CFormOutput::ShowApplyButton</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showsubmitbutton.php">CFormOutput::ShowSubmitButton</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showresetbutton.php
	* @author Bitrix
	*/
	public static function ShowResetButton($caption = "", $css_style = "")
	{
		$button_value = strlen(trim($caption)) > 0 ? trim($caption) : GetMessage("FORM_RESET");

		return "<input type=\"reset\" value=\"".htmlspecialcharsbx($button_value)."\"".(!empty($css_style) ? " class=\"".$css_style."\"" : "")." />";
	}

	/**
	 * Public method used to put form description onto form page
	 * Use: <?=$FORM->ShowFormDescription()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод описательного текста формы</p>
	*
	*
	* @param  $string  CSS-класс который нужно применить к выводимому тексту.
	* Необязательный параметр. Если для описания формы выставлено
	* значение "HTML", то параметр игнорируется.
	*
	* @param CSSClas $s = "" 
	*
	* @return string <p>Возвращает описательный текст формы</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowFormDescription("form-description-text")?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformtitle.php">CFormOutput::ShowFormTitle</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformimage.php">CFormOutput::ShowFormImage</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformdescription.php">CFormOutput::isFormDescription</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformdescription.php
	* @author Bitrix
	*/
	public function ShowFormDescription($css_style = "")
	{
		$ret = $this->arForm["DESCRIPTION_TYPE"] == "html" ? trim($this->arForm["DESCRIPTION"]) : nl2br(htmlspecialcharsbx(trim($this->arForm["DESCRIPTION"])));

		if (strlen($css_style) > 0) $ret = "<div class=\"".$css_style."\">".$ret."</div>";

		return $ret;
	}

	/**
	 * Public: check whether form has description
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "есть ли у формы текстовое описание".</p>
	*
	*
	* @return bool <p><i>true</i>, если у формы есть текстовое описание. <i>false</i> в противном
	* случае.</p> <a name="examples"></a><h4>Использование</h4> <pre
	* class="syntax">&lt;?if($FORM-&gt;isFormDescription()):?&gt;Описание:
	* &lt;?=$FORM-&gt;ShowFormDescription()?&gt;&lt;?endif?&gt;</pre></bod
	*
	* <h4>See Also</h4> 
	* <p><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a><br><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformdescription.php">CFormOutput::ShowFormDescription</a></p></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformdescription.php
	* @author Bitrix
	*/
	public function isFormDescription()
	{
		return strlen(trim($this->arForm["DESCRIPTION"])) > 0;
	}

	/**
	 * Public: shows form image; params like CFile::ShowImage()
	 * Use: <?=$FORM->ShowFormImage(250, 250, "hspace=\"0\" vspace=\"0\" align=\"left\" border=\"0\"", "", true, GetMessage("FORM_ENLARGE"))?>
	 * Returns image code if image exists and empty string otherwise
	 *
	 * @param int $iMaxW
	 * @param int $iMaxH
	 * @param string $sParams
	 * @param string $strImageUrl
	 * @param bool $bPopup
	 * @param mixed $strPopupTitle
	 * @return string
	 */
	//function ShowFormImage($iMaxW=0, $iMaxH=0, $sParams="border=\"0\"", $strImageUrl="", $bPopup=false, $strPopupTitle=false)
	
	/**
	* <p>Вывод изображения, прикрепленного к описанию формы</p>
	*
	*
	* @param  $string  Необязательный параметр. Значение по умолчанию - "border=\"0\"".
	*
	* @param mixed $sAlign = "" Необязательный параметр.
	*
	* @param int $iMaxW = 0 Расположение изображения относительно текста. Может принимать
	* одно из четырех значений - <code>LEFT</code>, <code>CENTER</code>, <code>RIGHT</code>
	* (регистр не имеет значения) или пустое. Необязательный параметр.
	*
	* @param int $iMaxH = 0 Максимальная ширина изображения. Если ширина картинки больше iMaxW,
	* то она будет пропорционально смаштабирована.<br> Необязательный.
	* По умолчанию - "0" - без ограничений.
	*
	* @param string $bPopup = "N" Максимальная высота изображения. Если высота картинки больше iMaxH,
	* то она будет пропорционально смаштабирована.<br> Необязательный.
	* По умолчанию - "0" - без ограничений.
	*
	* @param string $strPopupTitle = "" Открывать ли при клике на изображении дополнительное popup окно с
	* увеличенным изображением.<br> Необязательный. Должен приниметь
	* одно из двух значений - "Y" или "N" (с учётом регистра). По умолчанию -
	* "N" (до версии 5.1.2 - "false").
	*
	* @param string $sHSpace = "" Текст всплывающей подсказки на изображении (только если <i>bPopup</i> =
	* "Y")<br> Необязательный. По умолчанию выводится фраза "Нажмите чтобы
	* увеличить" на языке страницы (до версии 5.1.2 значение по умолчанию -
	* "false").
	*
	* @param string $sVSpace = "" Устанавливает вертикальный отступ картинки от окружающего
	* текста в пикселях.<br> Необязательный. По умолчанию - "0" - без
	* отступа.
	*
	* @param string $sBorder = "" Устанавливает горизонтальный отступ картинки от окружающего
	* текста в пикселях.<br> Необязательный. По умолчанию - "0" - без
	* отступа.
	*
	* @return string <p>Возвращает HTML-код для вставки изображения в формы</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax"> &lt;?=$FORM-&gt;ShowFormImage("CENTER",
	* 250, 250, "Y", GetMessage("FORM_ENLARGE"), 0, 0)?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/showimage.php">CFile::ShowImage</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformdescription.php">CFormOutput::ShowFormDescription</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformtitle.php">CFormOutput::ShowFormTitle</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformimage.php">CFormOutput::isFormImage</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformimage.php
	* @author Bitrix
	*/
	public function ShowFormImage($sAlign = "", $iMaxW="", $iMaxH="", $bPopup="N", $strPopupTitle="", $sHSpace = "", $sVSpace = "", $sBorder = "")
	{
		if ($this->isFormImage())
		{
			$arImageParams = array();

			if (strlen($sAlign) > 0) $arImageParams[] = sprintf("align=\"%s\"", $sAlign);
			if (strlen($sHSpace) > 0) $arImageParams[] = sprintf("hspace=\"%s\"", $sHSpace);
			if (strlen($sVSpace) > 0) $arImageParams[] = sprintf("vspace=\"%s\"", $sVSpace);
			if (strlen($sBorder) > 0) $arImageParams[] = sprintf("border=\"%s\"", $sBorder);
			else $arImageParams[] = "border=\"0\"";

			if (strlen($strPopupTitle) <= 0) $strPopupTitle = false;

			if (strlen($this->__form_image_cache) <= 0)
			{
				$this->__form_image_cache = CFile::ShowImage($this->arForm["IMAGE_ID"], $iMaxW, $iMaxH, implode(" ", $arImageParams), $strImageUrl, $bPopup == "Y", $strPopupTitle);
			}

			$ret = $this->__form_image_cache;

			if (strtoupper($sAlign) == "CENTER") $ret = "<div align=\"center\">".$ret."</div>";

			$this->__form_image_cache = $ret;

			return $ret;
		}
	}

	/**
	 * Public: check if form has image
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "прикреплена ли к форме картинка".</p>
	*
	*
	* @return bool <p><i>true</i>, если к форме прикреплена картинка. <i>false</i> в противном
	* случае.</p> <a name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?if($FORM-&gt;isFormImage()):?&gt; &lt;?=$FORM-&gt;ShowFormImage()?&gt; &lt;?else:?&gt;
	* &lt;?=CFile::ShowImage("/myimages/form_default.jpg")?&gt; &lt;?endif?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cfile/showimage.php">CFile::ShowImage</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformimage.php">CFormOutput::ShowFormImage</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformimage.php
	* @author Bitrix
	*/
	public function isFormImage()
	{
		return intval($this->arForm["IMAGE_ID"])>0;
	}

	/**
	 * Public: shows current form title
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод текстового заголовка формы</p>
	*
	*
	* @param  $string  CSS-класс который нужно применить к выводимому тексту.
	* Необязательный параметр.
	*
	* @param CSSClas $s = "" 
	*
	* @return string <p>Возвращает текстовый заголовок (название) формы</p> <a
	* name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?=$FORM-&gt;ShowFormTitle("form-title")?&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformdescription.php">CFormOutput::ShowFormDescription</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformimage.php">CFormOutput::ShowFormImage</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformtitle.php">CFormOutput::isFormTitle</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformtitle.php
	* @author Bitrix
	*/
	public function ShowFormTitle($css_style = "")
	{
		$ret = trim(htmlspecialcharsbx($this->arForm["NAME"]));

		if (strlen($css_style) > 0) $ret = "<div class=\"".$css_style."\">".$ret."</div>";

		return $ret;
	}

	/**
	 * Public: check whether current form has title string
	 *
	 * @return bool
	 */
	
	/**
	* <p>Проверка условия "есть ли у формы текстовый заголовок (название)".</p>
	*
	*
	* @return bool <p><i>true</i>, если у формы есть текстовый заголово (название). <i>false</i> в
	* противном случае.</p> <a name="examples"></a><h4>Использование</h4> <pre class="syntax">
	* &lt;?if($FORM-&gt;isFormTitle()):?&gt;Описание: &lt;?=$FORM-&gt;ShowFormTitle()?&gt;&lt;?endif?&gt; </pre></bod
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showformtitle.php">CFormOutput::ShowFormTitle</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/isformtitle.php
	* @author Bitrix
	*/
	public function isFormTitle()
	{
		return strlen(trim($this->arForm["NAME"])) > 0;
	}

	public function ShowResultStatusForm()
	{
		if ($this->isResultStatusChangeAccess())
		{
			return SelectBox("status_".$this->arForm["SID"], CFormStatus::GetDropdown($this->WEB_FORM_ID, array("MOVE"), $this->arResult["USER_ID"]), " ", "", "");
		}
		else
			return "";
	}

	public function ShowResultStatus($bNotShowCSS = "N")
	{
		if (intval($this->RESULT_ID) <= 0) return "";
		if ($bNotShowCSS != "N")
		{
			return "<span class='".$this->arResult["STATUS_CSS"]."'>".$this->arResult["STATUS_TITLE"]."</span>";
		}
		else
		{
			return $this->arResult["STATUS_TITLE"];
		}
	}

	public function ShowResultStatusText()
	{
		return $this->arResult["STATUS_TITLE"];
	}

	public function GetResultStatusCSSClass()
	{
		return $this->arResult["STATUS_CSS"];
	}

	public function isResultStatusChangeAccess()
	{
		return (!empty($this->RESULT_ID) && in_array("EDIT", $this->arrRESULT_PERMISSION));
	}

	public static function ShowDateFormat($css_style = "")
	{
		$format = CLang::GetDateFormat("SHORT");

		if (strlen($css_style) > 0) return '<span class="'.$css_style.'">'.$format.'</span>';
		else return $format;
	}

	/**
	 * Public method used to show "required" label (red '*')
	 * Use: <?=$FORM->ShowRequired()?>
	 *
	 * @return string
	 */
	
	/**
	* <p>Вывод пометки "обязательное поле" - <span style="font-family: Verdana, Arial, Helvetica, sans-serif; color:red; font-size:12px; ">*</span>. При выводе подписи к полю посредством <a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaption.php">CFormOutput::ShowInputCaption</a> пометка вставаляется автоматически.</p>
	*
	*
	* @return string <p>Возвращается HTML-код пометки</p> <a name="examples"></a><h4>Использование</h4>
	* <pre class="syntax"> Первый обязательный вопрос
	* &lt;?=$FORM-&gt;ShowRequired()?&gt;:&lt;?=$FORM-&gt;ShowInput('REQ_FIELD_1')?&gt;&lt;br /&gt;
	* &lt;?=$FORM-&gt;ShowInputCaption('REQ_FIELD_2'):&lt;?=$FORM-&gt;ShowInput('REQ_FIELD_2')?&gt;&lt;br /&gt; </pre>
	*
	* <h4>See Also</h4> 
	* <menu> <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/index.php">Класс CFormOutput</a></li>
	* <li><a href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinput.php">CFormOutput::ShowInput</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showinputcaption.php">CFormOutput::ShowInputCaption</a></li>
	* </menu></b<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/form/classes/cformoutput/showrequired.php
	* @author Bitrix
	*/
	public static function ShowRequired()
	{
		return CForm::ShowRequired("Y");
	}

	public static function CheckTemplate($FORM_TEMPLATE, &$arrFS)
	{
		if (count($arrFS) > 0)
		{
			$arFldSIDs = array();
			$arInactiveFldSIDs = array();
			$str = "";
			foreach ($arrFS as $key => $arField)
			{
				$cur_str = "";
				if (strlen(trim($arField["FIELD_SID"]))<=0) $cur_str .= GetMessage("FORM_ERROR_FORGOT_SID")."<br>";
				elseif (preg_match("/[^A-Za-z_01-9]/",$arField["FIELD_SID"])) $cur_str .= GetMessage("FORM_ERROR_INCORRECT_SID")."<br>";
				elseif (in_array($arField['FIELD_SID'], $arFldSIDs))
				{
					$key = array_search($arField['FIELD_SID'], $arInactiveFldSIDs);
					if ($key)
					{
						unset($arrFS[$key]);
						unset($arInactiveFldSIDs[$key]);
						unset($arFldSIDs[$key]);
					}
					else
					{
						$s = str_replace("#TYPE#", GetMessage("FORM_TYPE_FIELD"), GetMessage("FORM_ERROR_WRONG_SID"));
						$s = str_replace("#ID#",$zr["ID"],$s);
						$cur_str .= $s."<br>";
					}
				}
				else
				{
					$arFldSIDs[$key] = $arField["FIELD_SID"];
					if (!CForm::isFieldInTemplate($arField["FIELD_SID"], $FORM_TEMPLATE))
						$arInactiveFldSIDs[$key] = $arField["FIELD_SID"];
				}

				if (!empty($cur_str))
				{
					$str .= $cur_str;
				}
			}

			if (!empty($str))
			{
				$_GLOBALS["strError"] .= $str;
				return false;
			}
			else return true;
		}
		return true;
	}

	public static function PrepareFormData($arrFS)
	{
		$out = "";
		$i = 0;
		if (is_array($arrFS))
		{
			foreach($arrFS as $key=>$arField)
			{
				if ($arField['isNew'] == "Y") $arField["CAPTION"] = $arField["isHTMLCaption"] == "Y" ? $arField["CAPTION_UNFORM"] : "<span class=\"tablebodytext\">".$arField["CAPTION_UNFORM"]."</span>".($arField["isRequired"] ? CFormOutput::ShowRequired() : "");
?>
arrInputObjects[<?=$i++?>] = new CFormAnswer(
	'<?=$arField["FIELD_SID"]?>',
	'<?=CUtil::JSEscape($arField["CAPTION"])?>',
	'<?=$arField["isHTMLCaption"]?>',
	'<?=CUtil::JSEscape("'", "\\'", $arField["CAPTION_UNFORM"])?>',
	'<?=$arField["isRequired"]?>',
	'<?=$arField["type"]?>',
	[<?
				foreach ($arField["structure"] as $key=>$arQuestion)
				{
					$arr = array();
					$cnt = 0;
					foreach ($arQuestion as $q_key=>$value)
					{
						$arr[] = $q_key.":'".($q_key == "ANS_NEW" ? ($value == "Y" ? 'true' : 'false') : str_replace("'", "\\'", $value))."'";
						if ($q_key == "ANS_NEW" && $value) $cnt++;
					}

					if ($key != 0) echo ",";
					echo "{";
					echo implode(",", $arr);
					echo "}";
				}
	?>],
	<?=$arField["isNew"] == "Y" ? 'true' : 'false'?>,
	<?=$arField["ID"] ? $arField["ID"] : '_global_newinput_counter++'?>,
	'<?=$arField["inResultsTable"]?>',
	'<?=$arField["inExcelTable"]?>'
);

<?
				if ($cnt > 0) echo "_global_newanswer_counter += ".$cnt.";\n";
			}
		}
	}

	public function setError($error)
	{
		$this->__error_msg = $error;
	}

	public function isAccessFormParams()
	{
		return $this->F_RIGHT >= 25;
	}

	public function isAccessForm()
	{
		return $this->F_RIGHT >= 10;
	}

	public function isAccessFormResult($arrResult)
	{
		global $USER;

		return $this->F_RIGHT>=20 || ($this->F_RIGHT>=15 && $USER->GetID()==$arrResult["USER_ID"]);
	}

	public function isAccessFormResultEdit()
	{
		return in_array("EDIT",$this->arrRESULT_PERMISSION);
	}

	public function isAccessFormResultView()
	{
		return in_array("VIEW",$this->arrRESULT_PERMISSION);
	}

	public function isAccessFormResultList()
	{
		return $this->F_RIGHT >= 15;
	}

	public function getFormImagePath()
	{
		if (!$this->isFormImage()) return false;
		if (empty($this->__form_image_path_cache))
			$this->__form_image_path_cache = CFile::GetPath($this->arForm["IMAGE_ID"]);

		return $this->__form_image_path_cache;
	}

	public function getInputCaptionImagePath($FIELD_SID)
	{
		if (!$this->isInputCaptionImage($FIELD_SID)) return false;
		if (empty($this->__form_input_caption_image_path_cache[$FIELD_SID]))
			$this->__form_input_caption_image_path_cache[$FIELD_SID] = CFile::GetPath($this->arQuestions[$FIELD_SID]["IMAGE_ID"]);

		return $this->__form_input_caption_image_path_cache[$FIELD_SID];
	}

	public function setInputDefaultValue($FIELD_SID, $value, $ANSWER_ID = false)
	{
		if (is_array($this->arAnswers) && is_array($this->arAnswers[$FIELD_SID]))
		{
			$type = $this->__admin_GetInputType($FIELD_SID);
			if ($type == "multiple" || $type == "file" || $type == "image")
			{
				return;
			}

			if (intval($ANSWER_ID) == 0)
			{
				if ($type == "checkbox" || $type == "multiselect")
				{
					if (is_array($value)) $this->arrVALUES["form_".$type."_".$FIELD_SID] = $value;
				}
				elseif ($type == "radio" || $type == "dropdown")
				{
					if (!is_array($value)) $this->arrVALUES["form_".$type."_".$FIELD_SID] = $value;
				}
				else
				{
					$ANSWER_ID = $this->arAnswers[$FIELD_SID][0]["ID"];
					$this->arrVALUES["form_".$type."_".$ANSWER_ID] = $value;
				}
			}
			elseif (is_array($ANSWER_ID))
			{
				if ($type == "checkbox" || $type == "multiselect")
					$this->arrVALUES["form_".$type."_".$FIELD_SID] = $value == "N" ? array() : $ANSWER_ID;
			}
			else
			{
				if ($type == "radio" || $type == "dropdown")
					$this->arrVALUES["form_".$type."_".$FIELD_SID] = $value == "N" ? "" : $ANSWER_ID;
				else
					$this->arrVALUES["form_".$type."_".$ANSWER_ID] = $value;
			}
		}
	}
}
?>