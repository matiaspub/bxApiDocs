<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
class DuplicateControl
{
	private static $CURRENT_SETTINGS = null;
	protected $settings = array();

	protected function __construct(array $settings = null)
	{
		if($settings !== null)
		{
			$this->settings = $settings;
		}
	}
	public static function isControlEnabledFor($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);

		//By default control is enabled
		$settings = self::loadCurrentSettings();
		return !isset($settings['enableFor'][$entityTypeName]) || $settings['enableFor'][$entityTypeName] === 'Y';
	}
	public function isEnabledFor($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		//By default control is enabled
		return !isset($this->settings['enableFor'][$entityTypeName]) || $this->settings['enableFor'][$entityTypeName] === 'Y';
	}
	public function enabledFor($entityTypeID, $enable)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}

		if(!is_bool($enable))
		{
			if(is_numeric($enable))
			{
				$enable = $enable > 0;
			}
			elseif(is_string($enable))
			{
				$enable = strtoupper($enable) === 'Y';
			}
			else
			{
				$enable = false;
			}
		}
		$this->settings['enableFor'][\CCrmOwnerType::ResolveName($entityTypeID)] = $enable ? 'Y' : 'N';
	}
	public static function getCurrent()
	{
		return new DuplicateControl(self::loadCurrentSettings());
	}
	public function save()
	{
		self::$CURRENT_SETTINGS = $this->settings;
		\Bitrix\Main\Config\Option::set('crm', 'dup_ctrl', serialize(self::$CURRENT_SETTINGS));
	}
	private static function loadCurrentSettings()
	{
		if(self::$CURRENT_SETTINGS === null)
		{
			$s = \Bitrix\Main\Config\Option::getRealValue('crm', 'dup_ctrl');
			if(is_string($s) && $s !== '')
			{
				$ary = unserialize($s);
				if(is_array($ary))
				{
					self::$CURRENT_SETTINGS = &$ary;
					unset($ary);
				}
			}
			if(!is_array(self::$CURRENT_SETTINGS))
			{
				self::$CURRENT_SETTINGS = array();
			}
			if(!isset(self::$CURRENT_SETTINGS['enableFor']))
			{
				self::$CURRENT_SETTINGS['enableFor'] = array();
			}
		}
		return self::$CURRENT_SETTINGS;
	}
}