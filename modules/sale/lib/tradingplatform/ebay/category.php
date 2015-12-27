<?php
	namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\Entity;
use \Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class CategoryTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME int mandatory
 * <li> CATEGORY_ID int mandatory
 * <li> PARENT_ID int mandatory
 * <li> LEVEL int mandatory
 * <li> CONDITION_ID_VALUES string(255) optional
 * <li> CONDITION_ID_DEFINITION_URL string(255) optional
 * <li> ITEM_SPECIFIC_ENABLED string(1) optional
 * <li> VARIATIONS_ENABLED string(1) optional
 * <li> PRODUCT_CREATION_ENABLED string(1) optional
 * <li> LAST_UPDATE string(1) optional
 * </ul>
 *
 * @package Bitrix\Sale\TradingPlatform\Ebay
 **/

class CategoryTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_ebay_cat';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateName'),
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_NAME_FIELD'),
			),
			'CATEGORY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_CATEGORY_ID_FIELD'),
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_PARENT_ID_FIELD'),
			),
			'LEVEL' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_LEVEL_FIELD'),
			),
			'CONDITION_ID_VALUES' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateConditionIdValues'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_CONDITION_ID_VALUES_FIELD'),
			),
			'CONDITION_ID_DEFINITION_URL' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateConditionIdDefinitionUrl'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_CONDITION_ID_DEFINITION_URL_FIELD'),
			),
			'ITEM_SPECIFIC_ENABLED' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateItemSpecificEnabled'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_ITEM_SPECIFIC_ENABLED_FIELD'),
			),
			'VARIATIONS_ENABLED' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateVariationsEnabled'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_VARIATIONS_ENABLED_FIELD'),
			),
			'PRODUCT_CREATION_ENABLED' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateProductCreationEnabled'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_PRODUCT_CREATION_ENABLED_FIELD'),
			),
			'LAST_UPDATE' => array(
				'data_type' => 'datetime',
				'required' => false,
				'default' => DateTime::createFromTimestamp(time()),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_GENERAL_METADATA_ENTITY_LAST_UPDATE_FIELD'),
			),
		);
	}

	/**
	 * @param string $ebayCategoryId Ebay category Id.
	 * @return array Ebay category parents chain till top level.
	 */
	public static function getCategoryParents($ebayCategoryId)
	{
		$result = array();

		do
		{
			$categoryRes = self::getList(array(
				'select' =>array('CATEGORY_ID', 'NAME', 'PARENT_ID', 'LEVEL'),
				'filter'=> array(
					'CATEGORY_ID' => $ebayCategoryId
				),
			));

			if($category = $categoryRes->fetch())
			{
				$result[$category["LEVEL"]] = $category;
				$ebayCategoryId = $category["PARENT_ID"];
			}
			else
			{
				break;
			}
		}
		while($category["LEVEL"] > 1 || $category["PARENT_ID"] != $category["CATEGORY_ID"]);

		return array_reverse($result, true);
	}

	/**
	 * Overrides parent update  to sate update date to current.
	 * @param mixed $primary Primary key.
	 * @param array $data Data fields.
	 * @return Entity\UpdateResult
	 */
	public static function update($primary, array $data)
	{
		$data["LAST_UPDATE"] = DateTime::createFromTimestamp(time());
		return  parent::update($primary, $data);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateConditionIdValues()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateConditionIdDefinitionUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateItemSpecificEnabled()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	public static function validateVariationsEnabled()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	public static function validateProductCreationEnabled()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
}