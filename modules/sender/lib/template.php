<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TemplateTable extends Entity\DataManager
{
	const LOCAL_DIR_IMG = '/images/sender/preset/template/';

	/**
	 * Handler of event that return array of templates
	 *
	 * @param string|null $templateType
	 * @param string|null $templateId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function onPresetTemplateList($templateType = null, $templateId = null)
	{
		$resultList = array();
		if($templateType && $templateType !== 'USER')
		{
			return $resultList;
		}

		$localPathOfIcon = static::LOCAL_DIR_IMG . 'my.png';
		$fullPathOfIcon = \Bitrix\Main\Loader::getLocal($localPathOfIcon);

		// return only active templates, but if requested template by id return any
		$filter = array();
		if($templateId)
		{
			$filter['ID'] = $templateId;
		}
		else
		{
			$filter['ACTIVE'] = 'Y';
		}

		$templateDb = static::getList(array('filter' => $filter, 'order' => array('ID' => 'DESC')));
		while($template = $templateDb->fetch())
		{
			$resultList[] = array(
				'TYPE' => 'USER',
				'ID' => $template['ID'],
				'NAME' => $template['NAME'],
				'ICON' => (!empty($fullPathOfIcon) ? '/bitrix'.$localPathOfIcon : ''),
				'HTML' => $template['CONTENT']
			);
		}

		return $resultList;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_preset_template';
	}

	/**
	 * Return the map
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'autocomplete' => true,
				'primary' => true,
			),
			'ACTIVE' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => 'Y',
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_TEMPLATE_FIELD_TITLE_NAME')
			),
			'CONTENT' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_TEMPLATE_FIELD_TITLE_CONTENT')
			),
		);
	}

	/**
	 * Handler of before delete event
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	
	/**
	* <p>Обработчик события <code>onBeforeDelete</code>. Метод статический. </p>
	*
	*
	* @param mixed $Bitrix  Данные для удаления.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Event $event  
	*
	* @return \Bitrix\Main\Entity\EventResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sender/templatetable/onbeforedelete.php
	* @author Bitrix
	*/
	public static function onBeforeDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();
		$chainListDb = MailingChainTable::getList(array(
			'select' => array('ID', 'SUBJECT', 'MAILING_ID', 'MAILING_NAME' => 'MAILING.NAME'),
			'filter' => array('TEMPLATE_TYPE' => 'USER', 'TEMPLATE_ID' => $data['primary']['ID']),
			'order' => array('MAILING_NAME' => 'ASC', 'ID')
		));

		if($chainListDb->getSelectedRowsCount() > 0)
		{
			$template = static::getRowById($data['primary']['ID']);
			$messageList = array();
			while($chain = $chainListDb->fetch())
			{
				$messageList[$chain['MAILING_NAME']] = '[' . $chain['ID'] . '] ' . htmlspecialcharsbx($chain['SUBJECT']) . "\n";
			}

			$message = Loc::getMessage('SENDER_ENTITY_TEMPLATE_DELETE_ERROR_TEMPLATE', array('#NAME#' => $template['NAME'])) . "\n";
			foreach($messageList as $mailingName => $messageItem)
			{
				$message .= Loc::getMessage('SENDER_ENTITY_TEMPLATE_DELETE_ERROR_MAILING', array('#NAME#' => $mailingName)) . "\n" . $messageItem . "\n";
			}

			$result->addError(new Entity\EntityError($message));
		}

		return $result;
	}

	/**
	 * Function return true if html in $content is supported by Block Editor
	 *
	 * @param string $content
	 * @return boolean
	 */
	public static function isContentForBlockEditor($content)
	{
		return \Bitrix\Fileman\Block\Editor::isContentSupported($content);
	}

	/**
	 * Init editor
	 *
	 * @param array $params
	 * @return string
	 */
	public static function initEditor(array $params)
	{
		$fieldName = $params['FIELD_NAME'];
		$fieldValue = $params['FIELD_VALUE'];
		$isUserHavePhpAccess = $params['HAVE_USER_ACCESS'];
		$showSaveTemplate = isset($params['SHOW_SAVE_TEMPLATE']) ? $params['SHOW_SAVE_TEMPLATE'] : true;
		$site = isset($params['SITE']) ? $params['SITE'] : '';
		$charset = isset($params['CHARSET']) ? $params['CHARSET'] : '';
		$contentUrl = isset($params['CONTENT_URL']) ? $params['CONTENT_URL'] : '';
		$templateTypeInput = isset($params['TEMPLATE_TYPE_INPUT']) ? $params['TEMPLATE_TYPE_INPUT'] : 'TEMPLATE_TYPE';
		$templateIdInput = isset($params['TEMPLATE_ID_INPUT']) ? $params['TEMPLATE_ID_INPUT'] : 'TEMPLATE_ID';
		$templateType = isset($params['TEMPLATE_TYPE']) ? $params['TEMPLATE_TYPE'] : '';
		$templateId = isset($params['TEMPLATE_ID']) ? $params['TEMPLATE_ID'] : '';
		$isTemplateMode = isset($params['IS_TEMPLATE_MODE']) ? (bool) $params['IS_TEMPLATE_MODE'] : true;
		if(!empty($params['PERSONALIZE_LIST']) && is_array($params['PERSONALIZE_LIST']))
		{
			PostingRecipientTable::setPersonalizeList($params['PERSONALIZE_LIST']);
		}


		\CJSCore::RegisterExt("editor_mailblock", Array(
			"js" => array(
				"/bitrix/js/sender/editor_mailblock.js",
			),
			"rel" => array()
		));
		\CJSCore::Init(array("editor_mailblock"));

		static $isInit;

		$isDisplayBlockEditor = ($templateType && $templateId) || static::isContentForBlockEditor($fieldValue);

		$editorHeight = 650;
		$editorWidth = '100%';

		ob_start();
		?>
		<div id="bx-sender-visual-editor-<?=$fieldName?>" style="<?if($isDisplayBlockEditor):?>display: none;<?endif;?>">
		<?
		if(\Bitrix\Main\Config\Option::get('fileman', 'use_editor_3') == 'Y'):
			\Bitrix\Main\Loader::includeModule('fileman');
		?>
		<script>
			BX.ready(function(){
				<?if(!$isInit): $isInit = true;?>
					var letterManager = new SenderLetterManager;
					letterManager.setMailBlockList(<?=\CUtil::PhpToJSObject(\Bitrix\Sender\Preset\MailBlock::getBlockForVisualEditor());?>);
					letterManager.setPlaceHolderList(<?=\CUtil::PhpToJSObject(\Bitrix\Sender\PostingRecipientTable::getPersonalizeList());?>);
				<?endif;?>
			});

			BX.message({
				"BXEdMailBlocksTitle" : "<?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_MAILBLOCK')?>",
				"BXEdMailBlocksSearchPlaceHolder" : "<?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_MAILBLOCK_SEARCH')?>",
				"BXEdPlaceHolderSelectorTitle" : "<?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_PLACEHOLDER')?>"
			});
		</script>
		<?\CFileMan::AddHTMLEditorFrame(
			$fieldName,
			$fieldValue,
			false,
			"text",
			array(
				'height' => $editorHeight,
				'width' => $editorWidth
			),
			"N",
			0,
			"",
			"onfocus=\"t=this\"",
			false,
			!$isUserHavePhpAccess,
			false,
			array(
				//'templateID' => $str_SITE_TEMPLATE_ID,
				'componentFilter' => array('TYPE' => 'mail'),
				'limit_php_access' => !$isUserHavePhpAccess
			)
		);?>
		<?
		else:
			$fieldValue = htmlspecialcharsback($fieldValue);
			?>
			<br>
			<?=Loc::getMessage("SENDER_ENTITY_TEMPLATE_NOTE_OLD_EDITOR", array("%LINK_START%" => '<a href="/bitrix/admin/settings.php?mid=fileman&lang=' . LANGUAGE_ID . '">',	"%LINK_END%" => '</a>'))?>
			<br>
			<br>
			<textarea class="typearea" style="width:<?=$editorWidth?>;height:<?=$editorHeight?>px;" name="<?=$fieldName?>" id="bxed_<?=$fieldName?>" wrap="virtual"><?= htmlspecialcharsbx($fieldValue)?></textarea>
			<?
		endif;
		?>
		</div>

		<div id="bx-sender-block-editor-<?=$fieldName?>" style="<?if(!$isDisplayBlockEditor):?>display: none;<?endif;?>">
			<br/>
			<input type="hidden" name="<?=htmlspecialcharsbx($templateTypeInput)?>" value="<?=htmlspecialcharsbx($templateType)?>" />
			<input type="hidden" name="<?=htmlspecialcharsbx($templateIdInput)?>" value="<?=htmlspecialcharsbx($templateId)?>" />
			<?
			$url = '';
			if($isDisplayBlockEditor)
			{
				if($templateType && $templateId)
				{
					$url = '/bitrix/admin/sender_template_admin.php?';
					$url .= 'action=get_template&template_type=' . $templateType . '&template_id=' . $templateId;
					$url .= '&lang=' . LANGUAGE_ID . '&' . bitrix_sessid_get();
				}
				else
				{
					$url = $contentUrl;
				}
			}
			echo \Bitrix\Fileman\Block\EditorMail::show(array(
				'id' => $fieldName,
				'charset' => $charset,
				'site' => $site,
				'own_result_id' => 'bxed_' . $fieldName,
				'url' => $url,
				'templateType' => $templateType,
				'templateId' => $templateId,
				'isTemplateMode' => $isTemplateMode,
				'isUserHavePhpAccess' => $isUserHavePhpAccess,
			));
			?>
		</div>

		<?
		if($showSaveTemplate):
		?>
		<script>
			function ToggleTemplateSaveDialog()
			{
				BX('TEMPLATE_ACTION_SAVE_NAME_CONT').value = '';

				var currentDisplay =  BX('TEMPLATE_ACTION_SAVE_NAME_CONT').style.display;
				BX('TEMPLATE_ACTION_SAVE_NAME_CONT').style.display = BX.toggle(currentDisplay, ['inline', 'none']);
			}
		</script>
		<div class="adm-detail-content-item-block-save">
			<span>
				<input type="checkbox" value="Y" name="TEMPLATE_ACTION_SAVE" id="TEMPLATE_ACTION_SAVE" onclick="ToggleTemplateSaveDialog();">
				<label for="TEMPLATE_ACTION_SAVE"><?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_SAVE')?></label>
			</span>
			<span id="TEMPLATE_ACTION_SAVE_NAME_CONT" style="display: none;"> <?=Loc::getMessage('SENDER_TEMPLATE_EDITOR_SAVE_NAME')?> <input type="text" name="TEMPLATE_ACTION_SAVE_NAME"></span>
		</div>
		<?
		endif;

		return ob_get_clean();
	}
}