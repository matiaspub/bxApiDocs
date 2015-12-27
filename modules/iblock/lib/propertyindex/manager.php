<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Manager
{
	protected static $catalog = null;
	/**
	 * For offers iblock identifier returns it's products iblock.
	 * Otherwise $iblockId returned.
	 *
	 * @param integer $iblockId Information block identifier.
	 *
	 * @return integer
	 */
	public static function resolveIblock($iblockId)
	{
		if (self::$catalog === null)
		{
			self::$catalog = \Bitrix\Main\Loader::includeModule("catalog");
		}

		if (self::$catalog)
		{
			$catalog = \CCatalogSKU::getInfoByOfferIBlock($iblockId);
			if (!empty($catalog) && is_array($catalog))
			{
				return $catalog["PRODUCT_IBLOCK_ID"];
			}
		}

		return $iblockId;
	}

	/**
	 * If elementId is an offer, then it's product identifier returned
	 * Otherwise $elementId returned.
	 *
	 * @param integer $iblockId Information block identifier.
	 * @param integer $elementId Element identifier.
	 *
	 * @return integer
	 */
	public static function resolveElement($iblockId, $elementId)
	{
		if (self::$catalog === null)
		{
			self::$catalog = \Bitrix\Main\Loader::includeModule("catalog");
		}

		if (self::$catalog)
		{
			$catalog = \CCatalogSKU::getProductInfo($elementId, $iblockId);
			if (!empty($catalog) && is_array($catalog))
			{
				return $catalog["ID"];
			}
		}

		return $elementId;
	}

	/**
	 * Drops all related to index database structures.
	 *
	 * @param integer $iblockId Information block identifier.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function dropIfExists($iblockId)
	{
		$storage = new Storage($iblockId);
		if ($storage->isExists())
			$storage->drop();

		$dictionary = new Dictionary($iblockId);
		if ($dictionary->isExists())
			$dictionary->drop();
	}

	/**
	 * Creates and initializes new indexer instance.
	 *
	 * @param integer $iblockId Information block identifier.
	 *
	 * @return \Bitrix\Iblock\PropertyIndex\Indexer
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function createIndexer($iblockId)
	{
		$indexer = new Indexer($iblockId);
		$indexer->init();
		return $indexer;
	}

	/**
	 * Marks iblock as one who needs index rebuild.
	 *
	 * @param integer $iblockId Information block identifier.
	 *
	 * @return void
	 */
	public static function markAsInvalid($iblockId)
	{
		\Bitrix\Iblock\IblockTable::update($iblockId, array(
			"PROPERTY_INDEX" => "I",
		));
		self::checkAdminNotification(true);
	}

	/**
	 * Adds admin users notification about index rebuild.
	 *
	 * @param boolean $force Whenever skip iblock check.
	 *
	 * @return void
	 */
	public static function checkAdminNotification($force = false)
	{
		if ($force)
		{
			$add = true;
		}
		else
		{
			$iblockList = \Bitrix\Iblock\IblockTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=PROPERTY_INDEX' => 'I'),
			));
			$add = ($iblockList->fetch()? true: false);
		}

		if ($add)
		{
			$notifyList = \CAdminNotify::getList(array(), array(
				"TAG" => "iblock_property_reindex",
			));
			if (!$notifyList->fetch())
			{
				\CAdminNotify::add(array(
					"MESSAGE" => Loc::getMessage("IBLOCK_NOTIFY_PROPERTY_REINDEX", array(
						"#LINK#" => "/bitrix/admin/iblock_reindex.php?lang=".\Bitrix\Main\Application::getInstance()->getContext()->getLanguage(),
					)),
					"TAG" => "iblock_property_reindex",
					"MODULE_ID" => "iblock",
					"ENABLE_CLOSE" => "Y",
					"PUBLIC_SECTION" => "N",
				));
			}
		}
		else
		{
			\CAdminNotify::deleteByTag("iblock_property_reindex");
		}
	}
	/**
	 * Deletes index and mark iblock as having none.
	 *
	 * @param integer $iblockId Information block identifier.
	 *
	 * @return void
	 */
	public static function deleteIndex($iblockId)
	{
		self::dropIfExists($iblockId);
		\Bitrix\Iblock\IblockTable::update($iblockId, array(
			"PROPERTY_INDEX" => "N",
		));
	}

	/**
	 * Deletes all related to element information if index exists.
	 *
	 * @param integer $iblockId Information block identifier.
	 * @param integer $elementId Identifier of the element.
	 *
	 * @return void
	 */
	public static function deleteElementIndex($iblockId, $elementId)
	{
		$elementId = intval($elementId);
		$productIblock = self::resolveIblock($iblockId);
		$indexer = self::createIndexer($productIblock);

		if ($indexer->isExists())
		{
			if ($iblockId != $productIblock)
			{
				self::updateElementIndex($iblockId, $elementId);
			}
			else
			{
				$indexer->deleteElement($elementId);
			}
		}
	}

	/**
	 * Updates all related to element information if index exists.
	 *
	 * @param integer $iblockId Information block identifier.
	 * @param integer $elementId Identifier of the element.
	 *
	 * @return void
	 */
	public static function updateElementIndex($iblockId, $elementId)
	{
		$elementId = intval($elementId);
		$productIblock = self::resolveIblock($iblockId);
		$indexer = self::createIndexer($productIblock);
		if ($indexer->isExists())
		{
			if ($iblockId != $productIblock)
			{
				$elementId = self::resolveElement($iblockId, $elementId);
			}

			$indexer->deleteElement($elementId);
			$connection = \Bitrix\Main\Application::getConnection();
			$elementCheck = $connection->query("
				SELECT BE.ID
				FROM b_iblock_element BE
				WHERE BE.ACTIVE = 'Y'
				".\CIBlockElement::wf_getSqlLimit("BE.", "N")."
				AND BE.ID = ".intval($elementId)
			);
			if ($elementCheck->fetch())
			{
				$indexer->indexElement($elementId);
			}
		}
	}
}
