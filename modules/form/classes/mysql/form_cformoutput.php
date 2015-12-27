<?
/**
 * Form output class
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
class CFormOutput extends CAllFormOutput 
{
	public static function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CFormOutput<br>File: ".__FILE__;
	}	
	
	public function CFormOutput()
	{
		$this->CAllFormOutput();
	}
}
?>