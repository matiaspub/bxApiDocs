<?php
namespace Bitrix\Crm\Recovery;
use Bitrix\Main;
class EntityRecoveryData
{
	protected $ID = 0;
	protected $registrationTime = null;
	protected $entityID = 0;
	protected $entityTypeID = 0;
	protected $contextID = 0;
	protected $userID = 0;
	protected $responsibleID = 0;
	protected $title = '';
	protected $data = null;

	protected static $ENABLE_COMPRESSION = null;

	const CONTEXT_UNDEFINED = 0;
	const CONTEXT_DEDUPLICATION = 1;
	const CONTEXT_DELETION = 2;

	public function __construct()
	{
		$this->registrationTime = new Main\Type\DateTime();
	}

	public function getID()
	{
		return $this->ID;
	}
	public function getRegistrationTime()
	{
		return $this->registrationTime;
	}
	public function setRegistrationTime(Main\Type\DateTime $time)
	{
		$this->registrationTime = $time;
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function setEntityID($entityID)
	{
		return $this->entityID = $entityID;
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function setEntityTypeID($entityTypeID)
	{
		return $this->entityTypeID = $entityTypeID;
	}
	public function getContextID()
	{
		return $this->contextID;
	}
	public function setContextID($contextID)
	{
		return $this->contextID = $contextID;
	}
	public function getUserID()
	{
		return $this->userID;
	}
	public function setUserID($userID)
	{
		return $this->userID = $userID;
	}
	public function getResponsibleID()
	{
		return $this->responsibleID;
	}
	public function setResponsibleID($responsibleID)
	{
		return $this->responsibleID = $responsibleID;
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function setTitle($title)
	{
		return $this->title = $title;
	}
	public function getData()
	{
		return $this->data;
	}
	public function setData(array $data)
	{
		return $this->data = $data;
	}
	public function setDataItem($name, $value)
	{
		if($this->data === null)
		{
			$this->data = array();
		}
		$this->data[$name] = $value;
	}
	public static function getByID($ID)
	{
		$dbResult = EntityRecoveryTable::getList(array('filter' => array('=ID' => $ID)));
		$fields = $dbResult->fetch();
		if(!is_array($fields))
		{
			return null;
		}

		$self = new EntityRecoveryData();
		$self->initializeFromFields($fields);
		return $self;
	}
	public static function deleteByID($ID)
	{
		/** @var Main\Entity\DeleteResult $result **/
		$result = EntityRecoveryTable::delete($ID);
		if(!$result->isSuccess())
		{
			throw new Main\SystemException("Could not delete EntityRecoveryData.\n".implode("\n", $result->getErrorMessages()));
		}
	}
	public function save()
	{
		$data = serialize($this->data);
		$isCompressed = self::isCompressionEnabled();
		if($isCompressed)
		{
			$data = gzcompress($data);
		}

		$fields = array(
			'REGISTRATION_TIME' => $this->registrationTime,
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => $this->entityTypeID,
			'CONTEXT_ID' => $this->contextID,
			'USER_ID' => $this->userID,
			'RESPONSIBLE_ID' => $this->responsibleID,
			'TITLE' => $this->title,
			'IS_COMPRESSED' => $isCompressed ? 'Y' : 'N',
			'DATA' => $data
		);

		if($this->ID > 0)
		{
			/** @var Main\Entity\UpdateResult $result **/
			$result = EntityRecoveryTable::update($this->ID, $fields);
			if(!$result->isSuccess())
			{
				throw new Main\SystemException("Could not update EntityRecoveryData.\n".implode("\n", $result->getErrorMessages()));
			}
		}
		else
		{
			/** @var Main\Entity\AddResult $result **/
			$result = EntityRecoveryTable::add($fields);
			if(!$result->isSuccess())
			{
				throw new Main\SystemException("Could not create EntityRecoveryData.\n".implode("\n", $result->getErrorMessages()));
			}

			$this->ID = $result->getId();
		}
	}
	public function delete()
	{
		if($this->ID <= 0)
		{
			throw new Main\InvalidOperationException("Could not delete EntityRecoveryData. The entity ID is not fond.");
		}
		self::deleteByID($this->ID);
	}
	protected function initializeFromFields(array $fields)
	{
		$this->ID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		$this->entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		$this->entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : 0;
		$this->contextID = isset($fields['CONTEXT_ID']) ? intval($fields['CONTEXT_ID']) : 0;
		$this->userID = isset($fields['USER_ID']) ? intval($fields['USER_ID']) : 0;
		$this->responsibleID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
		$this->title = isset($fields['TITLE']) ? $fields['TITLE'] : '';

		if(isset($fields['REGISTRATION_TIME']))
		{
			$this->registrationTime = $fields['REGISTRATION_TIME'];
		}

		$data = isset($fields['DATA']) ? $fields['DATA'] : '';
		if($data !== '')
		{
			$isCompressed = isset($fields['IS_COMPRESSED']) ? $fields['IS_COMPRESSED'] : '';
			if($isCompressed === 'Y')
			{
				if(!self::isCompressionEnabled())
				{
					throw new Main\NotSupportedException("Could not prepare recovery date. Compression is disabled in current environment.");
				}
				$data = gzuncompress($data);
				if($data === false)
				{
					$data = '';
				}
			}
		}
		$this->data = $data !== '' ? unserialize($data) : array();
	}
	protected static function isCompressionEnabled()
	{
		if(self::$ENABLE_COMPRESSION === null)
		{
			self::$ENABLE_COMPRESSION = function_exists('gzcompress') && function_exists('gzuncompress');
		}

		return self::$ENABLE_COMPRESSION;
	}
}