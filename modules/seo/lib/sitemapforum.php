<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\Entity;
use Bitrix\Seo\SitemapEntityTable;

class SitemapForumTable
	extends SitemapEntityTable
{
	const ENTITY_TYPE = 'FORUM';
}

class SitemapForum
{
	public static function __callStatic($name, $arguments)
	{
		$name = ToUpper($name);
		switch($name)
		{
			case "ADDMESSAGE":
				if ($arguments[1]["APPROVED"] == "Y")
				{
					self::actionUpdate($arguments[2], $arguments[2]);
				}
				break;
			case 'ADDTOPIC':
				if ($arguments[1]["APPROVED"] == "Y")
				{
					self::actionAdd(array(), $arguments[1]);
				}
				break;
			case 'UPDATETOPIC':
				if ($arguments[1]["APPROVED"] == "N")
				{
					self::actionDelete($arguments[1]);
				}
				else if (empty($arguments[2]) || $arguments[1]["FORUM_ID"] == $arguments[2]["FORUM_ID"])
				{
					self::actionUpdate((empty($arguments[2]) ? $arguments[1] : $arguments[2]), $arguments[1]);
				}
				else
				{
					self::actionDelete($arguments[2]);
					self::actionAdd(array(), $arguments[1]);
				}
				break;
			case 'DELETETOPIC':
				if ($arguments[1]["APPROVED"] == "Y")
				{
					self::actionDelete($arguments[1]);
				}
			break;
		}
	}

	protected static function checkParams($arMessage = array(), &$arTopic, &$arForum)
	{
		if (\Bitrix\Main\Loader::includeModule('forum'))
		{
			$arTopic = (!empty($arTopic) ? $arTopic : \CForumTopic::GetByID($arMessage["TOPIC_ID"]));
			if (empty($arTopic))
				return false;
			$arSitemaps = SitemapForumTable::getSitemapsByEntityId($arTopic["FORUM_ID"]);
			if (!empty($arSitemaps) && ($arForum = \CForumNew::GetByIDEx($arTopic["FORUM_ID"])) && $arForum)
			{
				$arForum["PATH2FORUM_MESSAGE"] = \CForumNew::GetSites($arTopic["FORUM_ID"]);
				$date = MakeTimeStamp($arTopic['LAST_POST_DATE']);
				$result = array();
				foreach($arSitemaps as $arSitemap)
				{
					$path = $arForum["PATH2FORUM_MESSAGE"][$arSitemap["SITE_ID"]];
					if (!empty($path))
					{
						$arSitemap["fileName"] = str_replace("#FORUM_ID#", $arForum["ID"], $arSitemap['SITEMAP_FILE_FORUM']);
						$arSitemap["url"] = \CForumNew::PreparePath2Message(
							$path,
							array(
								"FORUM_ID" => $arForum["ID"],
								"TOPIC_ID" => $arTopic["ID"],
								"TITLE_SEO" => $arTopic["TITLE_SEO"],
								"MESSAGE_ID" => "s",
								"SOCNET_GROUP_ID" => $arTopic["SOCNET_GROUP_ID"],
								"OWNER_ID" => $arTopic["OWNER_ID"],
								"PARAM1" => $arTopic["PARAM1"],
								"PARAM2" => $arTopic["PARAM2"]
							)
						);
						$arSitemap["date"] = $date;
						$result[] = $arSitemap;
					}
				}
				return (empty($result) ? false : $result);
			}
		}
		return false;
	}

	protected static function actionUpdate($arOldTopic, $arTopic, $arForum = array())
	{
		if (($arSitemaps = self::checkParams(array(), $arTopic, $arForum)) && $arSitemaps)
		{
			$arSitemapsOld = self::checkParams(array(), $arOldTopic, $arForum);
			foreach($arSitemaps as $key => $arSitemap)
			{
				$sitemapFile = new SitemapFile($arSitemap["fileName"], $arSitemap);
				$sitemapFile->removeEntry($arSitemapsOld[$key]['url']);

				$sitemapFile->appendIblockEntry($arSitemap["url"], $arSitemap['date']);

				$sitemapIndex = new SitemapIndex($arSitemap['SITEMAP_FILE'], $arSitemap);
				$sitemapIndex->appendIndexEntry($sitemapFile);
				if($arSitemap['ROBOTS'] == 'Y')
				{
					$robotsFile = new RobotsFile($arSitemap['SITE_ID']);
					$robotsFile->addRule(
						array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
					);
				}
			}
		}
	}

	protected static function actionDelete($arTopic, $arForum = array())
	{
		if (($arSitemaps = self::checkParams(array(), $arTopic, $arForum)) && $arSitemaps)
		{
			foreach($arSitemaps as $arSitemap)
			{
				$sitemapFile = new SitemapFile($arSitemap["fileName"], $arSitemap);
				$sitemapFile->removeEntry($arSitemap['url']);
				$informRobots = false;
				if (!$sitemapFile->isNotEmpty())
				{
					$rule = array(
						'url' => \CForumNew::PreparePath2Message(
							$arForum["PATH2FORUM_MESSAGE"][$arSitemap["SITE_ID"]],
							array(
								"FORUM_ID" => $arForum["ID"],
								"TOPIC_ID" => $arForum["TID"],
								"TITLE_SEO" => $arForum["TITLE_SEO"],
								"MESSAGE_ID" => "s",
								"SOCNET_GROUP_ID" => $arForum["SOCNET_GROUP_ID"],
								"OWNER_ID" => $arForum["OWNER_ID"],
								"PARAM1" => $arForum["PARAM1"],
								"PARAM2" => $arForum["PARAM2"]
							)
						),
						'date' => MakeTimeStamp($arForum['LAST_POST_DATE'])
					);
					$sitemapFile->appendIblockEntry($rule['url'], $rule['date']);
					$informRobots = true;
				}

				$sitemapIndex = new SitemapIndex($arSitemap['SITEMAP_FILE'], $arSitemap);
				$sitemapIndex->appendIndexEntry($sitemapFile);
				if ($informRobots && $arSitemap['ROBOTS'] == 'Y')
				{
					$robotsFile = new RobotsFile($arSitemap['SITE_ID']);
					$robotsFile->addRule(
						array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
					);
				}
			}
		}
	}

	protected static function actionAdd($arMessage, $arTopic, $arForum = array())
	{
		if (($arSitemaps = self::checkParams($arMessage, $arTopic, $arForum)) && $arSitemaps)
		{
			foreach($arSitemaps as $arSitemap)
			{
				$sitemapFile = new SitemapFile($arSitemap["fileName"], $arSitemap);
				$sitemapFile->appendIblockEntry($arSitemap['url'], $arSitemap['date']);

				$sitemapIndex = new SitemapIndex($arSitemap['SITEMAP_FILE'], $arSitemap);
				$sitemapIndex->appendIndexEntry($sitemapFile);

				if($arSitemap['ROBOTS'] == 'Y')
				{
					$robotsFile = new RobotsFile($arSitemap['SITE_ID']);
					$robotsFile->addRule(
						array(RobotsFile::SITEMAP_RULE, $sitemapIndex->getUrl())
					);
				}
			}
		}
	}
}
