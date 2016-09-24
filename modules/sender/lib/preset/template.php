<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender\Preset;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Fileman\Block\Editor;
use Bitrix\Sender\TemplateTable;

Loc::loadMessages(__FILE__);

class Template
{
	/**
	 * @return array
	 */
	public static function getListByType()
	{
		$resultTemplateList = array();
		$templateList = static::getList();
		foreach($templateList as $template)
			$resultTemplateList[$template['TYPE']][] = $template;

		return $resultTemplateList;
	}

	/**
	 * @return array
	 */
	public static function getTypeList()
	{
		return array(
			'BASE' => Loc::getMessage('TYPE_PRESET_TEMPLATE_BASE'),
			//'MARKETPLACE' => Loc::getMessage('TYPE_PRESET_TEMPLATE_MARKETPLACE'),
			'USER' => Loc::getMessage('TYPE_PRESET_TEMPLATE_USER'),
			'ADDITIONAL' => Loc::getMessage('TYPE_PRESET_TEMPLATE_ADDITIONAL'),
			//'MAILING' => Loc::getMessage('TYPE_PRESET_TEMPLATE_MAILING'),
			'SITE_TMPL' => Loc::getMessage('TYPE_PRESET_TEMPLATE_SITE_TMPL'),
		);
	}

	/**
	 * @return array|null
	 */
	public static function getById($type, $id)
	{
		$result = null;
		if($type && $id)
		{
			$templateList = static::getList(array($type, $id));
			foreach($templateList as $template)
			{
				if($template['ID'] != $id || $template['TYPE'] != $type)
				{
					continue;
				}

				$result = $template;
				break;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getList(array $parameters = array())
	{
		$resultList = array();
		$event = new Event('sender', 'OnPresetTemplateList', $parameters);
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() == EventResult::ERROR)
			{
				continue;
			}

			$eventResultParameters = $eventResult->getParameters();

			if (!empty($eventResultParameters))
			{
				$resultList = array_merge($resultList, $eventResultParameters);
			}
		}

		return $resultList;
	}

	/**
	 * @return string
	 */
	public static function getTemplateListHtml($containerId = 'TEMPLATE_CONTAINER')
	{
		static $templateListByType;

		if(!$templateListByType)
			$templateListByType = \Bitrix\Sender\Preset\Template::getListByType();

		$templateTypeList = \Bitrix\Sender\Preset\Template::getTypeList();

		ob_start();
		?>
		<script>
			BX.ready(function(){
				letterManager = new SenderLetterManager;
				if(!letterManager.get('<?=$containerId?>'))
				{
					letterManager.add('<?=$containerId?>', {'container': BX('<?=$containerId?>')});
				}
			});
		</script>
		<div class="sender-template-cont">
			<div>
				<table>
					<tr>
					<td style="vertical-align: top;">
						<div class="sender-template-type-selector">
							<?
							$firstTemplateType = null;
							foreach($templateTypeList as $templateType => $templateTypeName):
								if(!$firstTemplateType) $firstTemplateType = $templateType;
								?>
								<div class="sender-template-type-selector-button sender-template-type-selector-button-type-<?=$templateType?>"
										data-bx-sender-tmpl-type="<?=htmlspecialcharsbx($templateType)?>">
									<?=$templateTypeName?>
								</div>
							<?endforeach;?>
						</div>
					</td>
					<td style="vertical-align: top;">
						<div class="sender-template-list-container">
							<?foreach($templateTypeList as $templateType => $templateTypeName):?>
								<div id="sender-template-list-type-container-<?=$templateType?>" class="sender-template-list-type-container sender-template-list-type-container-<?=$templateType?>" style="display: none;">
									<?
									/*
									if($templateType == 'MARKETPLACE')
									{
										\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/panel/main/marketplace.css');
										?>
										<script>
											function mp_hl(el, show)
											{
												if(show)
													BX.addClass(el, 'mp-over-over');
												else
													BX.removeClass(el, 'mp-over-over');

											}
											BX.ready(function(){
												BX.ajax.insertToNode(
													'/bitrix/admin/update_system_market.php?category=55&lang=ru&mode=list',
													BX('sender-template-list-type-container-<?=$templateType?>')
												);
											});
										</script>
										<?
									}
									else
									*/
									if(isset($templateListByType[$templateType]))
										foreach($templateListByType[$templateType] as $templateNum => $template):
											$isContentForBlockEditor = TemplateTable::isContentForBlockEditor($template['HTML']);
									?>
										<div class="sender-template-list-type-block">
											<div class="sender-template-list-type-block-caption sender-template-list-block-selector"
											     data-bx-sender-tmpl-version="<?=($isContentForBlockEditor?'block':'visual')?>"
											     data-bx-sender-tmpl-name="<?=htmlspecialcharsbx($template['NAME'])?>"
											     data-bx-sender-tmpl-type="<?=htmlspecialcharsbx($template['TYPE'])?>"
											     data-bx-sender-tmpl-code="<?=htmlspecialcharsbx($template['ID'])?>"
											     data-bx-sender-tmpl-lang="<?=LANGUAGE_ID?>">
												<a class="sender-link-email" href="javascript: void(0);">
													<?=htmlspecialcharsbx($template['NAME'])?>
												</a>
												<?if(!$isContentForBlockEditor):?>
													<br>
													<span style="font-size: 10px;"><?=Loc::getMessage('SENDER_PRESET_TEMPLATE_OLD_EDITOR')?></span>
												<?endif;?>
											</div>
											<div class="sender-template-list-type-block-img sender-template-list-block-selector"
													data-bx-sender-tmpl-version="<?=($isContentForBlockEditor?'block':'visual')?>"
													data-bx-sender-tmpl-name="<?=htmlspecialcharsbx($template['NAME'])?>"
													data-bx-sender-tmpl-type="<?=htmlspecialcharsbx($template['TYPE'])?>"
													data-bx-sender-tmpl-code="<?=htmlspecialcharsbx($template['ID'])?>"
													data-bx-sender-tmpl-lang="<?=LANGUAGE_ID?>">
												<?if(!empty($template['ICON'])):?>
													<img src="<?=$template['ICON']?>">
												<?endif;?>
											</div>
											<?if(!empty($template['HTML'])):?>
												<div class="sender-template-message-preview-btn"
													data-bx-sender-tmpl-name="<?=htmlspecialcharsbx($template['NAME'])?>"
													data-bx-sender-tmpl-type="<?=htmlspecialcharsbx($template['TYPE'])?>"
													data-bx-sender-tmpl-code="<?=htmlspecialcharsbx($template['ID'])?>"
													data-bx-sender-tmpl-lang="<?=LANGUAGE_ID?>">
													<a class="sender-link-email " href="javascript: void(0);"><?=Loc::getMessage('SENDER_PRESET_TEMPLATE_BTN_PREVIEW')?></a>
												</div>
											<?endif;?>
										</div>
									<?endforeach;?>
									<?if(/*$templateType != 'MARKETPLACE' && */empty($templateListByType[$templateType])):?>
										<div class="sender-template-list-type-blockempty">
											<?=Loc::getMessage('SENDER_PRESET_TEMPLATE_NO_TMPL')?>
										</div>
									<?endif;?>
								</div>
							<?endforeach;?>
						</div>
					</td>
					<td style="vertical-align: top;">
						<span class="sender-template-btn-close" title="<?=Loc::getMessage('SENDER_PRESET_TEMPLATE_BTN_CLOSE')?>"></span>
					</td>
					</tr>
				</table>
			</div>
		</div>
		<?
		return ob_get_clean();
	}
}


class TemplateBase
{
	const LOCAL_DIR_TMPL = '/modules/sender/preset/template/';
	const LOCAL_DIR_IMG = '/images/sender/preset/template/';

	/**
	 * @return array
	 */
	public static function onPresetTemplateList($templateType = null, $templateId = null)
	{
		$resultList = array();

		$templateList = static::getListName();


		foreach ($templateList as $templateName)
		{
			if($templateName !== $templateId && $templateId)
			{
				continue;
			}

			$template = static::getById($templateName);
			if($template)
			{
				if($template['TYPE'] === $templateType || !$templateType)
				{
					$resultList[] = $template;
				}
			}
		}

		return $resultList;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function onPresetTemplateListSite($templateType = null, $templateId = null)
	{
		$resultList = array();

		if($templateType && $templateType !== 'SITE_TMPL')
		{
			return $resultList;
		}

		$by = 'SORT';
		$order = 'ASC';
		$filter = array('TYPE' => 'mail');
		if($templateId)
		{
			$filter['ID'] = $templateId;
		}

		$templateDb = \CSiteTemplate::GetList(array($by => $order), $filter, array("ID", "NAME", "CONTENT", "SCREENSHOT"));
		\Bitrix\Main\Loader::includeModule('fileman');
		$replaceAttr = Editor::BLOCK_PLACE_ATTR . '="' . Editor::BLOCK_PLACE_ATTR_DEF_VALUE . '"';
		$replaceText = '<div style="padding: 20px; border: 2px dashed #868686;"><span style="color: #868686; font-size: 20px;">' . Loc::getMessage('PRESET_TEMPLATE_LIST_SITE_DEF_TEXT') . '</span></div>';
		while($template = $templateDb->Fetch())
		{
			if($template['ID'] == 'mail_user')
			{
				continue;
			}

			$replaceTo = $replaceText;
			$html = $template['CONTENT'];

			$html = preg_replace('/<\?[\w\w].*?B_PROLOG_INCLUDED[^>].*?\?>/is', '', $html);
			if(stripos($html, $replaceAttr) === false)
			{
				$replaceTo = '<div id="bxStylistBody" ' . $replaceAttr . '>' . $replaceText . '</div>';
			}

			$html = str_replace(
				'#WORK_AREA#',
				$replaceTo,
				$html
			);

			$resultList[] = array(
				'TYPE' => 'SITE_TMPL',
				'ID' => $template['ID'],
				'NAME' => $template['NAME'],
				'ICON' => $template['SCREENSHOT'],
				'HTML' => $html
			);
		}

		return $resultList;
	}

	/**
	 * @return array
	 */
	public static function getListName()
	{
		$templateNameList = array(
			'empty',
			'1column1',
			'1column2',
			'2column1',
			'2column2',
			'2column3',
			'2column4',
			'2column5',
			'2column6',
			'2column7',
			'dynamic1',
			'dynamic2',
		);

		return $templateNameList;
	}

	/**
	 * @param string $templateName
	 * @return array|null
	 */
	public static function getById($templateName)
	{
		$result = null;

		$localPathOfIcon = static::LOCAL_DIR_IMG . bx_basename($templateName) . '.png';
		$fullPathOfIcon = \Bitrix\Main\Loader::getLocal($localPathOfIcon);

		$fullPathOfFile = \Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_TMPL . bx_basename($templateName) . '.php');
		if ($fullPathOfFile)
			$fileContent = File::getFileContents($fullPathOfFile);
		else
			$fileContent = '';

		if (!empty($fileContent) || $templateName == 'empty')
		{
			\Bitrix\Main\Loader::includeModule('fileman');
			if(\Bitrix\Fileman\Block\Editor::isContentSupported($fileContent))
			{
				$fileContent = static::replaceTemplateByDefaultData($fileContent);
			}

			$fileContent = str_replace(
				array('%TEXT_UNSUB_TEXT%', '%TEXT_UNSUB_LINK%'),
				array(
					Loc::getMessage('PRESET_MAILBLOCK_unsub_TEXT_UNSUB_TEXT'),
					Loc::getMessage('PRESET_MAILBLOCK_unsub_TEXT_UNSUB_LINK')
				),
				$fileContent
			);

			$result = array(
				'TYPE' => 'BASE',
				'ID' => $templateName,
				'NAME' => Loc::getMessage('PRESET_TEMPLATE_' . $templateName),
				'ICON' => (!empty($fullPathOfIcon) ? '/bitrix'.$localPathOfIcon : ''),
				'HTML' => $fileContent
			);
		}

		return $result;
	}

	/**
	 * @param string $template
	 * @return string
	 */
	protected static function replaceTemplateByDefaultData($template)
	{
		$phone = '8 495 212-85-06';
		$phonePath = Application::getDocumentRoot() . '/include/telephone.php';
		$logoHeader = '/include/logo.png';
		$logoFooter = '/include/logo_mobile.png';
		if(!File::isFileExists(Application::getDocumentRoot() . $logoHeader))
		{
			$logoHeader = '/bitrix/images/sender/preset/blocked1/logo.png';
		}
		if(!File::isFileExists(Application::getDocumentRoot() . $logoFooter))
		{
			$logoFooter = '/bitrix/images/sender/preset/blocked1/logo_m.png';;
		}

		if(File::isFileExists($phonePath))
		{
			$phone = File::getFileContents($phonePath);
		}

		$themeContent = File::getFileContents(\Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_TMPL . 'theme.php'));
		return str_replace(
			array(
				'%TEMPLATE_CONTENT%', '%LOGO_PATH_HEADER%', '%LOGO_PATH_FOOTER%', '%PHONE%',
				'%UNSUB_LINK%', '%MENU_CONTACTS%',
				'%MENU_HOWTO%', '%MENU_DELIVERY%',
				'%MENU_ABOUT%', '%MENU_GUARANTEE%',
				'%SCHEDULE_NAME%', '%SCHEDULE_DETAIL%',

				'%BUTTON%', '%HEADER%',
				'%TEXT1%', '%TEXT2%',
				'%TEXT3%', '%TEXT4%',
				'%TEXT5%', '%TEXT6%',
			),
			array(
				$template, $logoHeader, $logoFooter, $phone,
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_UNSUB_LINK'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_MENU_CONTACTS'),
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_MENU_HOWTO'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_MENU_DELIVERY'),
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_MENU_ABOUT'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_MENU_GUARANTEE'),
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_SCHEDULE_NAME'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_SCHEDULE_DETAIL'),

				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_BUTTON'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_HEADER'),
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_TEXT1'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_TEXT2'),
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_TEXT3'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_TEXT4'),
				Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_TEXT5'), Loc::getMessage('PRESET_TEMPLATE_LIST_BLANK_TEXT6'),
			),
			$themeContent
		);
	}

	/**
	 * @param $templateName
	 * @param $html
	 * @return bool|int
	 */
	public static function update($templateName, $html)
	{
		$result = false;
		$fullPathOfFile = \Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_TMPL . bx_basename($templateName) . '.php');
		if ($fullPathOfFile)
			$result = File::putFileContents($fullPathOfFile, $html);

		return $result;
	}
}