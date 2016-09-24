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

class MailBlock
{
	/**
	 * @return array
	 */
	public static function getListByType()
	{
		$resultTemplateList = array();
		$arTemplateList = static::getList();
		foreach($arTemplateList as $template)
			$resultTemplateList[$template['TYPE']][] = $template;

		return $resultTemplateList;
	}

	/**
	 * @return array
	 */
	public static function getList()
	{
		$resultList = array();
		$event = new Event('sender', 'OnPresetMailBlockList');
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
	 * @return array
	 */
	public static function getBlockForVisualEditor()
	{
		$arResult = array(
			'items' => array(),
			'groups' => array(),
			'rootDefaultFilename' => ''
		);

		$arGroupExists = array();
		$arBlocksByType = static::getListByType();
		foreach($arBlocksByType as $type => $arBlockList)
		{
			foreach($arBlockList as $blockNum => $arBlock)
			{
				$name = 'mailblock'.str_pad($blockNum+1, 4, '0', STR_PAD_LEFT);
				$key = $arBlock['TYPE'].'/'.$name;
				$arResult['items'][$key] = array(
					'name' => $name,
					'path' => $arBlock['TYPE'],
					'title' => $arBlock['NAME'],
					'thumb' => '',
					'code' => $arBlock['HTML'],
					'description' => empty($arBlock['DESC']) ? '' : $arBlock['DESC'],
					'template' => '',
					'level' => '',
					'parent' => $arBlock['TYPE'],
				);

				if(!in_array($arBlock['TYPE'], $arGroupExists))
				{
					$arResult['groups'][] = array(
						'path' => '',
						'name' => $arBlock['TYPE'],
						'level' => '0',
						'default_name' => 'mailblockgroup' . (count($arGroupExists) + 1)
					);
					$arGroupExists[] = $arBlock['TYPE'];
				}

			} // foreach $arBlockList

		} // foreach $arBlocksByType

		if(isset($arResult['groups'][0]))
			$arResult['rootDefaultFilename'] = $arResult['groups'][0]['default_name'];

		return $arResult;
	}
}

class MailBlockBase
{
	/**
	 *
	 */
	const LOCAL_DIR_BLOCK = '/modules/sender/preset/mailblock/';

	/**
	 * @return array
	 */
	public static function onPresetMailBlockList()
	{
		return static::getList();
	}

	/**
	 * @return array
	 */
	public static function getList()
	{
		$resultList = array();

		$arBlockByType = static::getBlockListByType();

		foreach($arBlockByType as $type => $arBlock)
		{
			foreach ($arBlock as $blockName)
			{
				$result = static::getById($blockName);
				if (!empty($result))
				{
					$resultList[] = $result;
				}
			}
		}

		$resultListPersonal = array();
		foreach(\Bitrix\Sender\PostingRecipientTable::getPersonalizeList() as $arPersonalizeBlock)
		{
			$resultListPersonal[] = array(
				'TYPE' => Loc::getMessage('TYPE_PRESET_MAILBLOCK_PERSONALISE'),
				'CODE' => $arPersonalizeBlock['CODE'],
				'NAME' => $arPersonalizeBlock['NAME'],
				'DESC' => $arPersonalizeBlock['DESC'],
				'ICON' => '',
				'HTML' => '#' . $arPersonalizeBlock['CODE'] . '#'
			);
		}

		$resultList = array_merge($resultListPersonal, $resultList);

		return $resultList;
	}

	/**
	 * @return array
	 */
	public static function getBlockListByType()
	{
		$arBlockByType = array(
			'BASE' => array(
				'unsub',
				'image',
				'text',
				'line',
				'image_text',
				'text_image',
				'image2',
				'image3',
				'text2',
				'text3',
			)
		);

		return $arBlockByType;
	}

	/**
	 * @param $blockName
	 * @return array|null
	 */
	public static function getById($blockName)
	{
		$result = null;
		$type = null;

		$arBlockByType = static::getBlockListByType();
		foreach($arBlockByType as $searchType => $arBlock)
		{
			foreach ($arBlock as $searchBlockName)
			{
				if($blockName == $searchBlockName)
				{
					$type = $searchType;
					break;
				}
			}
		}

		$fullPathOfFile = \Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_BLOCK . bx_basename($blockName) . '.html');
		if ($fullPathOfFile)
		{
			$fileContent = File::getFileContents($fullPathOfFile);

			if($blockName == 'unsub')
			{
				$fileContent = str_replace(
					array('%TEXT_UNSUB_TEXT%', '%TEXT_UNSUB_LINK%'),
					array(
						Loc::getMessage('PRESET_MAILBLOCK_' . $blockName.'_TEXT_UNSUB_TEXT'),
						Loc::getMessage('PRESET_MAILBLOCK_' . $blockName.'_TEXT_UNSUB_LINK')
					),
					$fileContent
				);
			}

			$result = array(
				'TYPE' => Loc::getMessage('TYPE_PRESET_MAILBLOCK_'.$type),
				'CODE' => $blockName,
				'NAME' => Loc::getMessage('PRESET_MAILBLOCK_' . $blockName),
				'ICON' => '',
				'HTML' => $fileContent
			);
		}


		return $result;
	}

	/**
	 * @param $blockName
	 * @param $html
	 * @return bool|int
	 */
	public static function update($blockName, $html)
	{
		$result = false;
		$fullPathOfFile = \Bitrix\Main\Loader::getLocal(static::LOCAL_DIR_BLOCK . bx_basename($blockName) . '.html');
		if ($fullPathOfFile)
			$result = File::putFileContents($fullPathOfFile, $html);

		return $result;
	}
}