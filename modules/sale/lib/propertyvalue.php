<?php
/**
 * Created by PhpStorm.
 * User: Alexey
 * Date: 09.01.2015
 * Time: 17:41
 */

namespace Bitrix\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use	Bitrix\Sale\Internals\Input,
	Bitrix\Sale\Internals\OrderPropsTable,
	Bitrix\Sale\Internals\OrderPropsValueTable,
	Bitrix\Sale\Internals\OrderPropsVariantTable,
	Bitrix\Main\Entity,
	Bitrix\Main\SystemException,
	Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\OrderPropsRelationTable;

class PropertyValue
	extends Internals\CollectableEntity
{
	protected $property = array();
	protected $savedValue;
	protected $deletedValue;

	protected static $mapFields;

	public static function create(PropertyValueCollection $collection, array $property = array())
	{
		$propertyValue = new static($property);
		$propertyValue->setCollection($collection);
		return $propertyValue;
	}

	protected function __construct(array $property = null, array $value = null, array $relation = null)
	{
		if (! $property && ! $value)
			throw new SystemException('invalid arguments', 0, __FILE__, __LINE__);

		if ($property)
		{
			if (is_array($property['SETTINGS']))
			{
				$property += $property['SETTINGS'];
				unset ($property['SETTINGS']);
			}
		}
		else
		{
			$property = array(
				'TYPE' => 'STRING',
				'PROPS_GROUP_ID' => 0,
				'NAME' => $value['NAME'],
				'CODE' => $value['CODE'],
			);
		}

		if (! $value)
		{
			$value = array(
				'ORDER_PROPS_ID' => $property['ID'],
				'NAME' => $property['NAME'],
				'CODE' => $property['CODE']
			);
		}

		if (!empty($relation))
			$property['RELATION'] = $relation;

		$this->savedValue = $value['VALUE']; //Input\File::getValue($property, $value['VALUE']);

		switch($property['TYPE'])
		{
			case 'ENUM':

				if ($propertyId = $property['ID'])
					$property['OPTIONS'] = static::loadOptions($propertyId);

				break;

			case 'FILE':

				if ($defaultValue = &$property['DEFAULT_VALUE'])
					$defaultValue = Input\File::loadInfo($defaultValue);

				if ($orderValue = &$value['VALUE'])
					$orderValue = Input\File::loadInfo($orderValue);

				break;
		}

		$this->property = $property;

		parent::__construct($value); //TODO field
	}

	public function setValue($value)
	{
		if ($value && $this->property['TYPE'] == 'FILE')
			$value = Input\File::loadInfo($value);

		if ($this->property['TYPE'] == "STRING")
		{
			if (Input\StringInput::isMultiple($value))
			{
				$fields = $this->getFields();
				$baseValuesData = $fields->getValues();
				$baseValues = null;
				if (!empty($baseValuesData['VALUE']) && is_array($baseValuesData['VALUE']))
				{
					$baseValues = array_values($baseValuesData['VALUE']);
				}
				foreach ($value as $key => $data)
				{
					if (Input\StringInput::isDeletedSingle($data))
					{
						$this->deletedValue[] = $key;
						if (is_array($baseValues) && array_key_exists($key, $baseValues))
						{
							$value[$key] = $baseValues[$key];
						}
						else
						{
							$value[$key] = '';
						}

					}
				}
			}
		}

		$this->setField('VALUE', $value);
	}

	private function getValueForDB($value)
	{
		$property = $this->property;

		if ($property['TYPE'] == 'FILE')
		{
			$value = Input\File::asMultiple($value);

			foreach ($value as $i => $file)
			{
				if (Input\File::isDeletedSingle($file))
				{
					unset($value[$i]);
				}
				else
				{
					if (Input\File::isUploadedSingle($file)
						&& ($fileId = \CFile::SaveFile(array('MODULE_ID' => 'sale') + $file, 'sale/order/properties'))
						&& is_numeric($fileId))
					{
						$file = $fileId;
					}

					$value[$i] = Input\File::loadInfoSingle($file);
				}
			}

			$this->fields->set('VALUE', $value);
			$value = Input\File::getValue($property, $value);

			foreach (
				array_diff(
					Input\File::asMultiple(Input\File::getValue($property, $this->savedValue         )),
					Input\File::asMultiple(                                $value                     ),
					Input\File::asMultiple(Input\File::getValue($property, $property['DEFAULT_VALUE']))
				)
				as $fileId)
			{
				\CFile::Delete($fileId);
			}
		}
		elseif($property['TYPE'] == 'STRING')
		{
			if (!empty($this->deletedValue) && is_array($this->deletedValue))
			{
				if (!empty($value) && is_array($value))
				{
					foreach ($value as $i => $string)
					{
						if (in_array($i, $this->deletedValue))
						{
							unset($value[$i]);
							unset($this->deletedValue[$i]);
						}
					}
				}
			}
		}

		return $value;
	}

	/** @return Entity\Result */
	public function save()
	{
		$result = new Result();
		$value = self::getValueForDB($this->fields->get('VALUE'));

		if ($valueId = $this->getId())
		{
			if ($value != $this->savedValue)
			{
				$r = Internals\OrderPropsValueTable::update($valueId, array('VALUE' => $value));

				if ($r->isSuccess())
					$this->savedValue = $value;
				else
					$result->addErrors($r->getErrors());
			}
		}
		else
		{
			if ($value !== null)
			{
				$property = $this->property;
				$r = Internals\OrderPropsValueTable::add(array(
					'ORDER_ID' => $this->getParentOrderId(),
					'ORDER_PROPS_ID' => $property['ID'],
					'NAME' => $property['NAME'],
					'VALUE' => $value,
					'CODE' => $property['CODE'],
				));
				if ($r->isSuccess())
				{
					$this->savedValue = $value;
					$this->setFieldNoDemand('ID', $r->getId());
				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}

	public function setValueFromPost(array $post)
	{
		$result = new Result();
		$property = $this->property;

		$key = isset($property["ID"]) ? $property["ID"] : "n".$this->getId();
		$value = isset($post['PROPERTIES'][$key]) ? $post['PROPERTIES'][$key] : null;

		if (isset($post['PROPERTIES'][$key]))
			$this->setValue($value);

		return $result;
	}

	public function checkValue($key, $value)
	{
		static $errorsList = array();
		$result = new Result();
		$property = $this->getProperty();

		$error = Input\Manager::getError($property, $value);

		if (!is_array($value) && strval(trim($value)) != "" && $property['IS_EMAIL'] == 'Y' && !check_email($value, true)) // TODO EMAIL TYPE
		{
			$error['EMAIL'] = str_replace(
				array("#EMAIL#", "#NAME#"),
				array(htmlspecialcharsbx($value), htmlspecialcharsbx($property['NAME'])),
				Loc::getMessage("SALE_GOPE_WRONG_EMAIL")
			);
		}

		foreach ($error as $e)
		{
			if (!empty($e) && is_array($e))
			{
				foreach ($e as $errorMsg)
				{
					if (isset($errorsList[$property['ID']]) && in_array($errorMsg, $errorsList[$property['ID']]))
						continue;

					$result->addError(new ResultError($property['NAME'].' '.$errorMsg, "PROPERTIES[".$key."]"));

					$errorsList[$property['ID']][] = $errorMsg;
				}
			}
			else
			{
				if (isset($errorsList[$property['ID']]) && in_array($e, $errorsList[$property['ID']]))
					continue;

				$result->addError(new ResultError($property['NAME'].' '.$e, "PROPERTIES[$key]"));

				$errorsList[$property['ID']][] = $e;
			}
		}

		return $result;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return Result
	 * @throws SystemException
	 */
	public function checkRequiredValue($key, $value)
	{
		static $errorsList = array();
		$result = new Result();
		$property = $this->getProperty();

		$error = Input\Manager::getRequiredError($property, $value);

		foreach ($error as $e)
		{
			if (!empty($e) && is_array($e))
			{
				foreach ($e as $errorMsg)
				{
					if (isset($errorsList[$property['ID']]) && in_array($errorMsg, $errorsList[$property['ID']]))
						continue;

					$result->addError(new ResultError($property['NAME'].' '.$errorMsg, "PROPERTIES[".$key."]"));

					$errorsList[$property['ID']][] = $errorMsg;
				}
			}
			else
			{
				if (isset($errorsList[$property['ID']]) && in_array($e, $errorsList[$property['ID']]))
					continue;

				$result->addError(new ResultError($property['NAME'].' '.$e, "PROPERTIES[$key]"));

				$errorsList[$property['ID']][] = $e;
			}
		}
		return $result;
	}

	private function getParentOrderId()
	{
		/** @var PaymentCollection $collection */
		$collection = $this->getCollection();
		$order = $collection->getOrder();
		return $order->getId();
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array('VALUE');
	}

	/**
	 * @return array
	 */
	public static function getMeaningfulFields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public static function getAllFields()
	{
		if (empty(static::$mapFields))
		{
			static::$mapFields = parent::getAllFieldsByMap(Internals\OrderPropsValueTable::getMap());
		}
		return static::$mapFields;
	}

	public function getProperty()
	{
		return $this->property;
	}

	public function getViewHtml()
	{
		return Input\Manager::getViewHtml($this->property, $this->getValue());
	}

	public function getEditHtml()
	{
		$key = isset($this->property["ID"]) ? $this->property["ID"] : "n".$this->getId();
		return Input\Manager::getEditHtml("PROPERTIES[".$key."]", $this->property, $this->getValue());
	}

	public function getValue()
	{
		return $this->getField("VALUE");
	}

	public function getValueId()
	{
		return $this->getField('ID');
	}

	public function getPropertyId()
	{
		return $this->property['ID'];
	}

	public function getPersonTypeId()
	{
		return $this->property['PERSON_TYPE_ID'];
	}

	public function getGroupId()
	{
		return $this->property['PROPS_GROUP_ID'];
	}

	public function getName()
	{
		return $this->property['NAME'];
	}

	public function getRelations()
	{
		return $this->property['RELATION'];
	}

	public function getDescription()
	{
		return $this->property['DESCRIPTION'];
	}

	public function getType()
	{
		return $this->property['TYPE'];
	}

	public function isRequired()
	{
		return $this->property['REQUIRED'] == 'Y';
	}

	public function isUtil()
	{
		return $this->property['UTIL'] == 'Y';
	}

	public static function getMeaningfulValues($personTypeId, $request)
	{
		$personTypeId = intval($personTypeId);
		if ($personTypeId <= 0)
			throw new ArgumentNullException("personTypeId");

		if (!is_array($request))
			throw new ArgumentNullException("request");

		$result = array();

		$db = OrderPropsTable::getList(array(
			'select' => array('ID', 'IS_LOCATION', 'IS_EMAIL', 'IS_PROFILE_NAME',
				'IS_PAYER', 'IS_LOCATION4TAX', 'CODE', 'IS_ZIP', 'IS_PHONE', 'IS_ADDRESS',
			),
			'filter' => array(
				'ACTIVE' => 'Y',
				'UTIL' => 'N',
				'PERSON_TYPE_ID' => $personTypeId
			)
		));
		while ($row = $db->fetch())
		{
			if (array_key_exists($row["ID"], $request))
			{
				foreach ($row as $key => $value)
				{
					if (($value === "Y") && (substr($key, 0, 3) === "IS_"))
					{
						$result[substr($key, 3)] = $request[$row["ID"]];
					}
				}
			}
		}

		return $result;
	}

	public static function loadForOrder(Order $order)
	{
		$objects = array();

		$propertyValues = array();
		$propertyValuesMap = array();
		$properties = array();

		if ($order->getId() > 0)
		{
			$result = OrderPropsValueTable::getList(array(
				'select' => array('ID', 'NAME', 'VALUE', 'CODE', 'ORDER_PROPS_ID'),
				'filter' => array('ORDER_ID' => $order->getId())
			));
			while ($row = $result->fetch())
			{
				$propertyValues[$row['ID']] = $row;
				$propertyValuesMap[$row['ORDER_PROPS_ID']] = $row['ID'];
			}
		}

		$filter = array(
//			'=ACTIVE' => 'Y',
//			'=UTIL' => 'N',
		);

		if ($order->getPersonTypeId() > 0)
			$filter[] = array('=PERSON_TYPE_ID' => $order->getPersonTypeId());

		$result = OrderPropsTable::getList(array(
			'select' => array('ID', 'PERSON_TYPE_ID', 'NAME', 'TYPE', 'REQUIRED', 'DEFAULT_VALUE', 'SORT',
				'USER_PROPS', 'IS_LOCATION', 'PROPS_GROUP_ID', 'DESCRIPTION', 'IS_EMAIL', 'IS_PROFILE_NAME',
				'IS_PAYER', 'IS_LOCATION4TAX', 'IS_FILTERED', 'CODE', 'IS_ZIP', 'IS_PHONE', 'IS_ADDRESS',
				'ACTIVE', 'UTIL', 'INPUT_FIELD_LOCATION', 'MULTIPLE', 'SETTINGS'
			),
			'filter' => $filter,
			'order' => array('SORT' => 'ASC')
		));

		while ($row = $result->fetch())
			$properties[$row['ID']] = $row;

		$result = OrderPropsRelationTable::getList(array(
			'select' => array(
				'PROPERTY_ID', 'ENTITY_ID', 'ENTITY_TYPE'
			),
			'filter' => array(
				'PROPERTY_ID' => array_keys($properties)
			)
		));

		$propRelation = array();
		while ($row = $result->fetch())
		{
			if (empty($row))
				continue;

			if (!isset($propRelation[$row['PROPERTY_ID']]))
				$propRelation[$row['PROPERTY_ID']] = array();

			$propRelation[$row['PROPERTY_ID']][] = $row;
		}

		foreach ($properties as $property)
		{
			$id = $property['ID'];

			if (isset($propertyValuesMap[$id]))
			{
				$fields = $propertyValues[$propertyValuesMap[$id]];
				unset($propertyValues[$propertyValuesMap[$id]]);
				unset($propertyValuesMap[$id]);
			}
			else
			{
				if ($property['ACTIVE'] == 'N') // || $property['UTIL'] == 'Y')
					continue;

				$fields = null;
			}
			if (isset($propRelation[$id]))
				$objects[] = new static($property, $fields, $propRelation[$id]);
			else
				$objects[] = new static($property, $fields);
		}

		foreach ($propertyValues as $propertyValue)
		{
			$objects[] = new static(null, $propertyValue);
		}

		return $objects;
	}

	static public function loadOptions($propertyId)
	{
		$options = array();

		$result = OrderPropsVariantTable::getList(array(
			'select' => array('VALUE', 'NAME'),
			'filter' => array('ORDER_PROPS_ID' => $propertyId),
			'order' => array('SORT' => 'ASC')
		));

		while ($row = $result->fetch())
			$options[$row['VALUE']] = $row['NAME'];

		return $options;
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return PropertyValue
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$propertyValueClone = clone $this;
		$propertyValueClone->isClone = true;

		/** @var Internals\Fields $fields */
		if ($fields = $this->fields)
		{
			$propertyValueClone->fields = $fields->createClone($cloneEntity);
		}

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $propertyValueClone;
		}

		if ($collection = $this->getCollection())
		{
			if (!$cloneEntity->contains($collection))
			{
				$cloneEntity[$collection] = $collection->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($collection))
			{
				$propertyValueClone->collection = $cloneEntity[$collection];
			}
		}

		return $propertyValueClone;
	}

}
