<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class DiscountEntitiesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DISCOUNT_ID int mandatory
 * <li> MODULE_ID string(50) mandatory
 * <li> ENTITY string(255) mandatory
 * <li> FIELD_ENTITY string(255) mandatory
 * <li> FIELD_TABLE string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Sale\Internals
 **/

class DiscountEntitiesTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_discount_entities';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('DISCOUNT_ENTITIES_ENTITY_ID_FIELD')
			)),
			'DISCOUNT_ID' => new Main\Entity\IntegerField('DISCOUNT_ID', array(
				'required' => true,
				'title' => Loc::getMessage('DISCOUNT_ENTITIES_ENTITY_DISCOUNT_ID_FIELD')
			)),
			'MODULE_ID' => new Main\Entity\StringField('MODULE_ID', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
				'title' => Loc::getMessage('DISCOUNT_ENTITIES_ENTITY_MODULE_ID_FIELD')
			)),
			'ENTITY' => new Main\Entity\StringField('ENTITY', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntity'),
				'title' => Loc::getMessage('DISCOUNT_ENTITIES_ENTITY_ENTITY_FIELD')
			)),
			'FIELD_ENTITY' => new Main\Entity\StringField('FIELD_ENTITY', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateFieldEntity'),
				'title' => Loc::getMessage('DISCOUNT_ENTITIES_ENTITY_FIELD_ENTITY_FIELD')
			)),
			'FIELD_TABLE' => new Main\Entity\StringField('FIELD_TABLE', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateFieldTable'),
				'title' => Loc::getMessage('DISCOUNT_ENTITIES_ENTITY_FIELD_TABLE_FIELD'),
			))
		);
	}
	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for ENTITY field.
	 *
	 * @return array
	 */
	public static function validateEntity()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for FIELD_ENTITY field.
	 *
	 * @return array
	 */
	public static function validateFieldEntity()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for FIELD_TABLE field.
	 *
	 * @return array
	 */
	public static function validateFieldTable()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Delete entity list by discount.
	 *
	 * @param int $discount			Discount id.
	 * @return void
	 */
	public static function deleteByDiscount($discount)
	{
		$discount = (int)$discount;
		if ($discount <= 0)
			return;
		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$conn->queryExecute(
			'delete from '.$helper->quote(self::getTableName()).' where '.$helper->quote('DISCOUNT_ID').' = '.$discount
		);
	}

	/**
	 * Update entity list by discount.
	 *
	 * @param int $discount				Discount id.
	 * @param array $entityList			Discount entity list.
	 * @param bool $clear				Clear old values.
	 * @return bool
	 */
	public static function updateByDiscount($discount, $entityList, $clear)
	{
		$discount = (int)$discount;
		if ($discount <= 0)
			return false;
		$clear = ($clear === true);
		if ($clear)
		{
			self::deleteByDiscount($discount);
		}
		if (!empty($entityList) && is_array($entityList))
		{
			foreach ($entityList as &$entity)
			{
				$fields = array(
					'DISCOUNT_ID' => $discount,
					'MODULE_ID' => $entity['MODULE'],
					'ENTITY' => $entity['ENTITY'],
					'FIELD_ENTITY' => $entity['FIELD_ENTITY'],
				);
				if (is_array($fields['FIELD_ENTITY']))
					$fields['FIELD_ENTITY'] = implode('-', $fields['FIELD_ENTITY']);
				if (isset($entity['FIELD_TABLE']) && is_array($entity['FIELD_TABLE']))
				{
					foreach ($entity['FIELD_TABLE'] as $oneField)
					{
						if (empty($oneField))
							continue;
						$fields['FIELD_TABLE'] = $oneField;
						$result = self::add($fields);
					}
					unset($oneField);
				}
				else
				{
					$fields['FIELD_TABLE'] = (isset($entity['FIELD_TABLE']) ? $entity['FIELD_TABLE'] : $entity['FIELD_ENTITY']);
					$result = self::add($fields);
				}
			}
			unset($entity);
		}
		return true;
	}

	/**
	 * Return entity by discount list.
	 *
	 * @param array $discountList			Discount id list.
	 * @param array $filter				Additional filter.
	 * @param bool $groupModule			Group by modules.
	 * @return array
	 */
	public static function getByDiscount($discountList, $filter = array(), $groupModule = true)
	{
		$groupModule = ($groupModule === true);
		$result = array();
		if (!empty($discountList) && is_array($discountList))
		{
			Main\Type\Collection::normalizeArrayValuesByInt($discountList);
			if (!empty($discountList))
			{
				if (!is_array($filter))
					$filter = array();

				$discountRows = array_chunk($discountList, 500);
				foreach ($discountRows as &$row)
				{
					$filter['@DISCOUNT_ID'] = $row;

					$entityIterator = self::getList(array(
						'select' => array('DISCOUNT_ID', 'MODULE_ID', 'ENTITY', 'FIELD_ENTITY', 'FIELD_TABLE'),
						'filter' => $filter
					));
					if ($groupModule)
					{
						while ($entity = $entityIterator->fetch())
						{
							unset($entity['DISCOUNT_ID']);
							if (!isset($result[$entity['MODULE_ID']]))
								$result[$entity['MODULE_ID']] = array();
							if (!isset($result[$entity['MODULE_ID']][$entity['ENTITY']]))
								$result[$entity['MODULE_ID']][$entity['ENTITY']] = array();
							$result[$entity['MODULE_ID']][$entity['ENTITY']][$entity['FIELD_ENTITY']] = $entity;
						}
					}
					else
					{
						while ($entity = $entityIterator->fetch())
						{
							$entity['DISCOUNT_ID'] = (int)$entity['DISCOUNT_ID'];
							if (!isset($result[$entity['DISCOUNT_ID']]))
								$result[$entity['DISCOUNT_ID']] = array();
							$result[$entity['DISCOUNT_ID']][] = $entity;
						}
					}
					unset($entity, $entityIterator);
				}
				unset($row, $discountRows);
			}
		}
		return $result;
	}
}