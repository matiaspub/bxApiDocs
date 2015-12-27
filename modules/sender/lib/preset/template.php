<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender\Preset;

use Bitrix\Main\Entity;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;

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
			'USER' => Loc::getMessage('TYPE_PRESET_TEMPLATE_USER'),
			'ADDITIONAL' => Loc::getMessage('TYPE_PRESET_TEMPLATE_ADDITIONAL'),
		);
	}

	/**
	 * @return array
	 */
	public static function getList()
	{
		$resultList = array();
		$event = new Event('sender', 'OnPresetTemplateList');
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
				letterManager.setTemplateListByType(<?=\CUtil::PhpToJSObject($templateListByType);?>);
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
									 bxsendertype="<?=htmlspecialcharsbx($templateType)?>">
									<?=$templateTypeName?>
								</div>
							<?endforeach;?>
						</div>
					</td>
					<td style="vertical-align: top;">
						<div class="sender-template-list-container">
							<?foreach($templateTypeList as $templateType => $templateTypeName):?>
								<div class="sender-template-list-type-container sender-template-list-type-container-<?=$templateType?>" style="display: none;">
									<?if(isset($templateListByType[$templateType])) foreach($templateListByType[$templateType] as $templateNum => $template):?>
										<div class="sender-template-list-type-block">
											<div class="sender-template-list-type-block-caption sender-template-list-block-selector">
												<a class="sender-link-email" href="javascript: void(0);" bxsendertype="<?=htmlspecialcharsbx($template['TYPE'])?>" bxsendernum="<?=intval($templateNum)?>">
													<?=htmlspecialcharsbx($template['NAME'])?>
												</a>
											</div>
											<div class="sender-template-list-type-block-img sender-template-list-block-selector"
												 bxsendertype="<?=htmlspecialcharsbx($template['TYPE'])?>" bxsendernum="<?=intval($templateNum)?>">
												<?if(!empty($template['ICON'])):?>
													<img src="<?=$template['ICON']?>">
												<?endif;?>
											</div>
										</div>
									<?endforeach;?>
									<?if(empty($templateListByType[$templateType])):?>
										<div class="sender-template-list-type-blockempty">
											<?=Loc::getMessage('SENDER_PRESET_TEMPLATE_NO_TMPL')?>
										</div>
									<?endif;?>
								</div>
							<?endforeach;?>
						</div>
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
	public static function onPresetTemplateList()
	{
		$resultList = array();

		$templateList = static::getListName();


		foreach ($templateList as $templateName)
		{
			$template = static::getById($templateName);
			if($template)
				$resultList[] = $template;
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
			'3column1',
			'3column2',
			'3column3',
		);

		return $templateNameList;
	}

	/**
	 * @param $templateName
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
				'NAME' => Loc::getMessage('PRESET_TEMPLATE_' . $templateName),
				'ICON' => (!empty($fullPathOfIcon) ? '/bitrix'.$localPathOfIcon : ''),
				'HTML' => $fileContent
			);
		}

		return $result;
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