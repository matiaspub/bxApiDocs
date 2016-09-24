<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock;

Loc::loadMessages(__FILE__);

class CIBlockPropertyTools
{
	const CODE_MORE_PHOTO = 'MORE_PHOTO';
	const CODE_SKU_LINK = 'CML2_LINK';
	const CODE_BLOG_POST = 'BLOG_POST_ID';
	const CODE_BLOG_COMMENTS_COUNT = 'BLOG_COMMENTS_CNT';
	const CODE_FORUM_TOPIC = 'FORUM_TOPIC_ID';
	const CODE_FORUM_MESSAGES_COUNT = 'FORUM_MESSAGE_CNT';
	const CODE_VOTE_COUNT = 'VOTE_COUNT';
	const CODE_VOTE_COUNT_OLD = 'vote_count';
	const CODE_VOTE_SUMM = 'VOTE_SUM';
	const CODE_VOTE_SUMM_OLD = 'vote_sum';
	const CODE_VOTE_RATING = 'RATING';
	const CODE_VOTE_RATING_OLD = 'rating';

	const XML_SKU_LINK = 'CML2_LINK';

	const USER_TYPE_SKU_LINK = 'SKU';

	protected static $errors = array();

	/**
	 * Return error list.
	 *
	 * @return array
	 */
	public static function getErrors()
	{
		return self::$errors;
	}

	/**
	 * Clear error list
	 *
	 * @return void
	 */
	public static function clearErrors()
	{
		self::$errors = array();
	}

	/**
	 * Create property.
	 *
	 * @param int $iblockID					Iblock id.
	 * @param string $propertyCode			Property code.
	 * @param array $propertyParams			Property params.
	 * @return bool|int
	 */
	public static function createProperty($iblockID, $propertyCode, $propertyParams = array())
	{
		self::$errors = array();
		$iblockID = (int)$iblockID;
		$propertyCode = (string)$propertyCode;
		if ($iblockID <= 0 || $propertyCode === '')
			return false;

		$iblockIterator = Iblock\IblockTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=ID' => $iblockID)
		));
		if (!($iblock = $iblockIterator->fetch()))
			return false;

		unset($iblock, $iblockIterator);
		$propertyDescription = static::getPropertyDescription($propertyCode, $propertyParams);
		if ($propertyDescription === false)
			return false;

		$propertyDescription['IBLOCK_ID'] = $iblockID;
		if (!static::validatePropertyDescription($propertyDescription))
			return false;

		$propertyId = 0;
		$getListParams = array(
			'select' => array('ID'),
			'filter' => array('=IBLOCK_ID' => $iblockID, '=CODE' => $propertyCode, '=ACTIVE' => 'Y')
		);
		static::modifyGetListParams($getListParams, $propertyCode, $propertyDescription);
		$property = Iblock\PropertyTable::getList($getListParams)->fetch();
		if (!empty($property))
		{
			if (static::validateExistProperty($propertyCode, $property))
				$propertyId = (int)$property['ID'];
		}
		unset($property);
		if (!empty(self::$errors))
			return false;
		if ($propertyId > 0)
			return $propertyId;
		unset($propertyId);
		$propertyResult = Iblock\PropertyTable::add($propertyDescription);
		if ($propertyResult->isSuccess())
		{
			return $propertyResult->getId();
		}
		else
		{
			self::$errors = $propertyResult->getErrorMessages();
			return false;
		}
	}

	/**
	 * Return filled property description.
	 *
	 * @param string $propertyCode			Property code.
	 * @param array $propertyParams			Property params.
	 * @return array|bool
	 */
	public static function getPropertyDescription($propertyCode, $propertyParams = array())
	{
		$propertyCode = (string)$propertyCode;
		if ($propertyCode === '')
			return false;
		if (!is_array($propertyParams))
			$propertyParams = array();
		switch($propertyCode)
		{
			case self::CODE_MORE_PHOTO:
				$propertyDescription = array(
					'PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_FILE,
					'USER_TYPE' => null,
					'NAME' => Loc::getMessage('IBPT_PROP_TITLE_MORE_PHOTO'),
					'CODE' => self::CODE_MORE_PHOTO,
					'MULTIPLE' => 'Y',
					'FILE_TYPE' => 'jpg, gif, bmp, png, jpeg',
					'ACTIVE' => 'Y',
				);
				break;
			case self::CODE_SKU_LINK:
				$propertyDescription = array(
					'PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_ELEMENT,
					'USER_TYPE' => self::USER_TYPE_SKU_LINK,
					'NAME' => Loc::getMessage('IBPT_PROP_TITLE_SKU_LINK'),
					'CODE' => self::CODE_SKU_LINK,
					'XML_ID' => self::XML_SKU_LINK,
					'MULTIPLE' => 'N',
					'ACTIVE' => 'Y',
				);
				if (isset($propertyParams['LINK_IBLOCK_ID']))
					$propertyDescription['LINK_IBLOCK_ID'] = (int)$propertyParams['LINK_IBLOCK_ID'];
				if (isset($propertyParams['USER_TYPE_SETTINGS']))
					$propertyDescription['USER_TYPE_SETTINGS'] = $propertyParams['USER_TYPE_SETTINGS'];
				break;
			case self::CODE_BLOG_POST:
				$propertyDescription = array(
					'PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_NUMBER,
					'USER_TYPE' => null,
					'NAME' => Loc::getMessage('IBPT_PROP_TITLE_BLOG_POST'),
					'CODE' => self::CODE_BLOG_POST,
					'MULTIPLE' => 'N',
					'ACTIVE' => 'Y',
				);
				break;
			case self::CODE_BLOG_COMMENTS_COUNT:
				$propertyDescription = array(
					'PROPERTY_TYPE' => Iblock\PropertyTable::TYPE_NUMBER,
					'USER_TYPE' => null,
					'NAME' => Loc::getMessage('IBPT_PROP_TITLE_BLOG_COMMENTS_COUNT'),
					'CODE' => self::CODE_BLOG_COMMENTS_COUNT,
					'MULTIPLE' => 'N',
					'ACTIVE' => 'Y',
				);
				break;
			default:
				$propertyDescription = false;
				break;
		}
		if ($propertyDescription !== false)
		{
			if (isset($propertyParams['NAME']))
				$propertyDescription['NAME'] = $propertyParams['NAME'];
			if (isset($propertyParams['SORT']))
				$propertyDescription['SORT'] = $propertyParams['SORT'];
			if (isset($propertyParams['XML_ID']) && !isset($propertyDescription['XML_ID']))
				$propertyDescription['XML_ID'] = $propertyParams['XML_ID'];
		}
		return $propertyDescription;
	}

	/**
	 * Check property description before create.
	 *
	 * @param array $propertyDescription		Property description.
	 * @return bool
	 */
	public static function validatePropertyDescription($propertyDescription)
	{
		if (empty($propertyDescription) || !isset($propertyDescription['CODE']))
			return false;
		$checkResult = true;

		switch ($propertyDescription['CODE'])
		{
			case self::CODE_SKU_LINK:
				if (
					!isset($propertyDescription['LINK_IBLOCK_ID'])
					|| $propertyDescription['LINK_IBLOCK_ID'] <= 0
					|| $propertyDescription['LINK_IBLOCK_ID'] == $propertyDescription['IBLOCK_ID']
				)
				{
					$checkResult = false;
				}
				if ($checkResult)
				{
					$iblockIterator = Iblock\IblockTable::getList(array(
						'select' => array('ID'),
						'filter' => array('=ID' => $propertyDescription['LINK_IBLOCK_ID'])
					));
					if (!($iblock = $iblockIterator->fetch()))
						$checkResult = false;
				}
				break;
			case self::CODE_MORE_PHOTO:
			case self::CODE_BLOG_POST:
			case self::CODE_BLOG_COMMENTS_COUNT:
				$checkResult = true;
				break;
			default:
				$checkResult = false;
				break;
		}
		return $checkResult;
	}

	/**
	 * Returns the list of infoblock properties, values for which need to be emptied when copying infoblock element.
	 *
	 * @param int $iblockID						Iblock id.
	 * @param array $propertyCodes			Property codes.
	 * @return array
	 */
	public static function getClearedPropertiesID($iblockID, $propertyCodes = array())
	{
		$iblockID = (int)$iblockID;
		if ($iblockID <= 0)
			return array();
		if (empty($propertyCodes) || !is_array($propertyCodes))
			$propertyCodes = array(
				self::CODE_BLOG_POST,
				self::CODE_BLOG_COMMENTS_COUNT,
				self::CODE_FORUM_TOPIC,
				self::CODE_FORUM_MESSAGES_COUNT,
				self::CODE_VOTE_COUNT,
				self::CODE_VOTE_COUNT_OLD,
				self::CODE_VOTE_SUMM,
				self::CODE_VOTE_SUMM_OLD,
				self::CODE_VOTE_RATING,
				self::CODE_VOTE_RATING_OLD
			);
		$result = array();
		$propertyIterator = Iblock\PropertyTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=IBLOCK_ID' => $iblockID, '@CODE' => $propertyCodes)
		));
		while ($property = $propertyIterator->fetch())
		{
			$result[] = (int)$property['ID'];
		}
		return $result;
	}

	/**
	 * Return exist property list.
	 *
	 * @param int $iblockID							Iblock id.
	 * @param array|string $propertyCodes			Property codes.
	 * @param bool $indexCode						Return codes as key.
	 * @return array|bool
	 */
	public static function getExistProperty($iblockID, $propertyCodes, $indexCode = true)
	{
		$indexCode = ($indexCode === true);
		$iblockID = (int)$iblockID;
		if ($iblockID <= 0)
			return false;
		$propertyCodes = static::clearPropertyList($propertyCodes);
		if (empty($propertyCodes))
			return false;

		$result = array();
		$propertyIterator = Iblock\PropertyTable::getList(array(
			'select' => array('ID', 'CODE'),
			'filter' => array('=IBLOCK_ID' => $iblockID, '@CODE' => $propertyCodes)
		));
		if ($indexCode)
		{
			while ($property = $propertyIterator->fetch())
			{
				$property['ID'] = (int)$property['ID'];
				if (!isset($result[$property['CODE']]))
				{
					$result[$property['CODE']] = $property['ID'];
				}
				else
				{
					if (!is_array($result[$property['CODE']]))
						$result[$property['CODE']] = array($result[$property['CODE']]);
					$result[$property['CODE']][] = $property['ID'];
				}
			}
			unset($property, $propertyIterator);
		}
		else
		{
			while ($property = $propertyIterator->fetch())
			{
				$property['ID'] = (int)$property['ID'];
				$result[$property['ID']] = $property['CODE'];
			}
			unset($property, $propertyIterator);
		}
		return $result;
	}

	/**
	 * Return property symbolic codes.
	 *
	 * @param bool $extendedMode		Get codes as keys.
	 * @return array
	 */
	public static function getPropertyCodes($extendedMode = false)
	{
		$extendedMode = ($extendedMode === true);
		$result = array(
			self::CODE_MORE_PHOTO,
			self::CODE_SKU_LINK,
			self::CODE_BLOG_POST,
			self::CODE_BLOG_COMMENTS_COUNT,
			self::CODE_FORUM_TOPIC,
			self::CODE_FORUM_MESSAGES_COUNT,
			self::CODE_VOTE_COUNT,
			self::CODE_VOTE_COUNT_OLD,
			self::CODE_VOTE_SUMM,
			self::CODE_VOTE_SUMM_OLD,
			self::CODE_VOTE_RATING,
			self::CODE_VOTE_RATING_OLD
		);
		return (
			$extendedMode
			? array_fill_keys($result, true)
			: $result
		);
	}

	/**
	 * Clear property symbolic codes.
	 *
	 * @param array|string $propertyCodes
	 * @return array|string
	 */
	public static function clearPropertyList($propertyCodes)
	{
		$result = array();
		if (!is_array($propertyCodes))
			$propertyCodes = array((string)$propertyCodes);
		if (empty($propertyCodes))
			return $result;

		$currentList = static::getPropertyCodes(true);
		foreach ($propertyCodes as &$code)
		{
			$code = (string)$code;
			if (isset($currentList[$code]))
				$result = $code;
		}
		unset($code);

		return $result;
	}

	/**
	 * Modify getList params for property search.
	 *
	 * @param array &$getListParams			\Bitrix\Main\Entity\DataManager::getList params.
	 * @param string $propertyCode			Property code.
	 * @param array $propertyDescription	Property description.
	 * @return void
	 */
	protected static function modifyGetListParams(&$getListParams, $propertyCode, $propertyDescription)
	{
		switch ($propertyCode)
		{
			case self::CODE_SKU_LINK:
				$getListParams['select'][] = 'XML_ID';
				$getListParams['select'][] = 'USER_TYPE';

				$getListParams['filter']['=LINK_IBLOCK_ID'] = $propertyDescription['LINK_IBLOCK_ID'];
				$getListParams['filter']['=PROPERTY_TYPE'] = Iblock\PropertyTable::TYPE_ELEMENT;
				$getListParams['filter']['=ACTIVE'] = 'Y';
				$getListParams['filter']['=MULTIPLE'] = 'N';
				break;
		}
	}

	/**
	 * Validate and modify exist property.
	 *
	 * @param string $propertyCode			Property code.
	 * @param array $property				Current property data.
	 * @return bool
	 * @throws Exception
	 */
	protected static function validateExistProperty($propertyCode, $property)
	{
		$result = true;
		switch ($propertyCode)
		{
			case self::CODE_SKU_LINK:
				$fields = array();
				if ($property['USER_TYPE'] != self::USER_TYPE_SKU_LINK)
					$fields['USER_TYPE'] = self::USER_TYPE_SKU_LINK;
				if ($property['XML_ID'] != self::XML_SKU_LINK)
					$fields['XML_ID'] = self::XML_SKU_LINK;
				if (!empty($fields))
				{
					$propertyResult = Iblock\PropertyTable::update($property['ID'], $fields);
					if (!$propertyResult->isSuccess())
					{
						self::$errors = $propertyResult->getErrorMessages();
						$result = false;
					}
					unset($propertyResult);
				}
				unset($fields);
				break;
		}

		return $result;
	}
}