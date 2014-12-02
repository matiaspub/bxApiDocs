<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;

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

	public static function getErrors()
	{
		return self::$errors;
	}

	public static function clearErrors()
	{
		self::$errors = array();
	}

	public static function createProperty($iblockID, $propertyCode, $propertyParams = array())
	{
		self::$errors = array();
		$iblockID = (int)$iblockID;
		$propertyCode = (string)$propertyCode;
		if ($iblockID <= 0 || $propertyCode === '')
			return false;
		$iblockIterator = IblockTable::getList(array(
			'select' => array('ID'),
			'filter' => array('ID' => $iblockID)
		));
		if (!($iblock = $iblockIterator->fetch()))
			return false;
		unset($iblock, $iblockIterator);
		$propertyIterator = PropertyTable::getList(array(
			'select' => array('ID'),
			'filter' => array('IBLOCK_ID' => $iblockID, '=CODE' => $propertyCode)
		));
		if ($property = $propertyIterator->fetch())
			return (int)$property['ID'];
		unset($propertyIterator);
		$propertyDescription = self::getPropertyDescription($propertyCode, $propertyParams);
		if ($propertyDescription === false)
			return false;
		$propertyDescription['IBLOCK_ID'] = $iblockID;
		if (!self::validatePropertyDescription($propertyDescription))
			return false;
		$propertyResult = PropertyTable::add($propertyDescription);
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
					'PROPERTY_TYPE' => PropertyTable::TYPE_FILE,
					'USER_TYPE' => null,
					'NAME' => loc::getMessage('IBPT_PROP_TITLE_MORE_PHOTO'),
					'CODE' => self::CODE_MORE_PHOTO,
					'MULTIPLE' => 'Y',
					'FILE_TYPE' => 'jpg, gif, bmp, png, jpeg',
					'ACTIVE' => 'Y',
				);
				break;
			case self::CODE_SKU_LINK:
				$propertyDescription = array(
					'PROPERTY_TYPE' => PropertyTable::TYPE_ELEMENT,
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
					'PROPERTY_TYPE' => PropertyTable::TYPE_NUMBER,
					'USER_TYPE' => null,
					'NAME' => Loc::getMessage('IBPT_PROP_TITLE_BLOG_POST'),
					'CODE' => self::CODE_BLOG_POST,
					'MULTIPLE' => 'N',
					'ACTIVE' => 'Y',
				);
				break;
			case self::CODE_BLOG_COMMENTS_COUNT:
				$propertyDescription = array(
					'PROPERTY_TYPE' => PropertyTable::TYPE_NUMBER,
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
				$propertyDescription['SORT'];
			if (isset($propertyParams['XML_ID']) && !isset($propertyDescription['XML_ID']))
				$propertyDescription['XML_ID'] = $propertyParams['XML_ID'];
		}
		return $propertyDescription;
	}

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
					$iblockIterator = IblockTable::getList(array(
						'select' => array('ID'),
						'filter' => array('ID' => $propertyDescription['LINK_IBLOCK_ID'])
					));
					if (!($iblock = $iblockIterator->fetch()))
						$checkResult = false;
				}
				break;
			default:
				$checkResult = false;
				break;
		}
		return $checkResult;
	}

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
		$propertyIterator = PropertyTable::getList(array(
			'select' => array('ID'),
			'filter' => array('IBLOCK_ID' => $iblockID, '=CODE' => $propertyCodes)
		));
		while ($property = $propertyIterator->fetch())
		{
			$result[] = (int)$property['ID'];
		}
		return $result;
	}
}
?>