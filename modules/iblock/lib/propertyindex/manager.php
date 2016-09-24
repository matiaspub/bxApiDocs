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
	
	/**
	* <p>Если передается идентификатор инфоблока торговых предложений, то метод вернет идентификатор соответствующего ему инфоблока товаров. В противном случае метод вернет искомый идентификатор <code>$iblockId</code>. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/resolveiblock.php
	* @author Bitrix
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
	
	/**
	* <p>Если передается идентификатор торгового предложения, то метод вернет идентификатор соответствующего ему товара. В противном случае метод вернет искомый идентификатор <code>$elementId</code>. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @param integer $elementId  Идентификатор элемента.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/resolveelement.php
	* @author Bitrix
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
	
	/**
	* <p>Метод удаляет из базы данных все таблицы, связанные с индексом заданного инфоблока. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/dropifexists.php
	* @author Bitrix
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
	
	/**
	* <p>Метод создает и инициализирует новый экземпляр класса <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/indexer/index.php">PropertyIndex\Indexer</a>. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @return \Bitrix\Iblock\PropertyIndex\Indexer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/createindexer.php
	* @author Bitrix
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
	
	/**
	* <p>Метод проставляет отметку для инфоблока, что ему необходима переиндексация. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/markasinvalid.php
	* @author Bitrix
	*/
	public static function markAsInvalid($iblockId)
	{
		\Bitrix\Iblock\IblockTable::update($iblockId, array(
			"PROPERTY_INDEX" => "I",
		));

		$productIblock = self::resolveIblock($iblockId);
		if ($iblockId != $productIblock)
		{
			\Bitrix\Iblock\IblockTable::update($productIblock, array(
				"PROPERTY_INDEX" => "I",
			));
		}

		self::checkAdminNotification(true);
	}

	/**
	 * Adds admin users notification about index rebuild.
	 *
	 * @param boolean $force Whenever skip iblock check.
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод добавляет уведомление пользователям группы <b>Администраторы</b> о необходимости пересоздания индекса. Метод статический.</p>
	*
	*
	* @param boolean $force = false Параметр принимает значение <i>false</i>, если проверка на
	* необходимость переиндексации не выполнялась.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/checkadminnotification.php
	* @author Bitrix
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
	
	/**
	* <p>Метод удаляет индекс и проставляет отметку для инфоблока, что у него нет индекса. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/deleteindex.php
	* @author Bitrix
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
	
	/**
	* <p>Метод удаляет всю связанную с элементом информацию, если индекс существует. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @param integer $elementId  Идентификатор элемента.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/deleteelementindex.php
	* @author Bitrix
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
	
	/**
	* <p>Метод обновляет всю связанную с элементом информацию, если индекс существует. Метод статический.</p>
	*
	*
	* @param integer $iblockId  Идентификатор инфоблока.
	*
	* @param integer $elementId  Идентификатор элемента.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/manager/updateelementindex.php
	* @author Bitrix
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
