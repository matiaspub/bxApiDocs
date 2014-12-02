<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

namespace Bitrix\Security;

use Bitrix\Main\Config;
use Bitrix\Main\Context;
use Bitrix\Main\Data;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;

/**
 * Class HostRestriction
 * @since 14.0.6
 * @example tests/security/hosts/basic.php
 * @package Bitrix\Security
 */
class HostRestriction
{
	const ACTION_REDIRECT = 'redirect';
	const ACTION_STOP = 'stop';

	private $optionPrefix = 'restriction_hosts_';
	private $cacheInitPath = 'security';
	private $cacheId = 'restriction_hosts';
	private $cacheTtl = 31536000; //one year
	private $action = 'stop';
	private $actionOptions = array();
	private $isLogNeeded = true;
	private $hosts = null;
	private $validActions = array(
		self::ACTION_REDIRECT,
		self::ACTION_STOP
	);
	private $validationRegExp = null;
	private $isActive = null;

	/**
	 * Handler for system event "OnPageStart", does nothing in CLI mode because it does not make sense
	 */
	public static function onPageStart()
	{
		if (\CSecuritySystemInformation::isCliMode())
			return;

		/** @var HostRestriction $instance */
		$instance = new static;
		$instance->process();
	}

	public function __construct()
	{
		$this->hosts = Config\Option::get('security', $this->optionPrefix.'hosts', '');
		$this->action = Config\Option::get('security', $this->optionPrefix.'action', '');
		$this->actionOptions = unserialize(Config\Option::get('security', $this->optionPrefix.'action_options', '{}'));
		$this->isLogNeeded = Config\Option::get('security', $this->optionPrefix.'logging', false);
	}

	/**
	 * The main method that checks the current host, logging and starting action
	 *
	 * @param string $host Requested host for checking.
	 * @return $this
	 */
	public function process($host = null)
	{
		if (is_null($host))
			$host = $this->getTargetHost();

		if ($this->isValidHost($host))
			return $this;

		if ($this->isLogNeeded)
			$this->log($host);

		$this->doActions();

		return $this;
	}

	/**
	 * Checking host by host restriction policy
	 *
	 * @param string $host Host for checking.
	 * @return bool Return true for valid (allowed) host.
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function isValidHost($host)
	{
		return (bool) (
			is_string($host)
			&& $host !== ''
			&& preg_match($this->getValidationRegExp(), $host) > 0
		);
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return array(
			'hosts' => $this->hosts,
			'current_host' => $this->getTargetHost(),
			'action' => $this->action,
			'action_options' => $this->actionOptions,
			'logging' => $this->isLogNeeded,
			'active' => is_null($this->isActive)? $this->getActive(): $this->isActive
		);
	}

	/**
	 * Set various properties for host checking, now support:
	 *  - hosts: a string with allowed hosts (wild card supported, e.g.: *.example.com) {@see setHosts}
	 *  - action: a string with action for unallowed host {@see validActions}
	 *  - action_options: array with some options for action {@see setAction}
	 *  - logging: bool, set true if need logging unallowed host {@see setLogging}
	 *  - active: bool, set true if automatic checking on every request needed
	 *
	 * @param array $properties See above.
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws LogicException
	 * @return $this
	 */
	public function setProperties(array $properties)
	{
		if (isset($properties['hosts']))
		{
			$this->setHosts($properties['hosts']);
		}

		if (isset($properties['action']))
		{
			if (isset($properties['action_options']))
			{
				$this->setAction($properties['action'], $properties['action_options']);
			}
			else
			{
				$this->setAction($properties['action']);
			}
		}

		if (isset($properties['logging']))
		{
			$this->setLogging($properties['logging']);
		}

		if (isset($properties['active']))
		{
			$this->setActive($properties['active']);
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return array
	 */
	public function getActionOptions()
	{
		return $this->actionOptions;
	}

	/**
	 * Set action performed while checking
	 *
	 * @param string $action Some action, now supported: redirect and stop.
	 * @param array $options Some options for action, so far supported only host for redirect in redirect action.
	 * @return $this
	 * @throws \Bitrix\Security\LogicException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function setAction($action, array $options = array())
	{
		if (!$action)
			throw new ArgumentNullException('action');

		if (!is_string($action))
			throw new ArgumentTypeException('action', 'string');

		if (!in_array($action, $this->validActions))
			throw new ArgumentOutOfRangeException('action', $this->validActions);

		if ($action === self::ACTION_REDIRECT)
		{
			if (!isset($options['host']) || !$options['host'])
				throw new LogicException('options[host] not present', 'SECURITY_HOSTS_EMPTY_HOST_ACTION');

			if (!preg_match('#^https?://#', $options['host']))
				throw new LogicException('invalid redirecting host present in options[host]', 'SECURITY_HOSTS_INVALID_HOST_ACTION');
		}


		$this->action = $action;
		$this->actionOptions = $options;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getLogging()
	{
		return $this->isLogNeeded;
	}

	/**
	 * Activate or deactivate logging on unallowed host requested
	 *
	 * @param bool $isLogNeeded Set true if need logging unallowed host.
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function setLogging($isLogNeeded = true)
	{
		if (!is_bool($isLogNeeded))
			throw new ArgumentTypeException('isLogNeeded', 'bool');

		$this->isLogNeeded = $isLogNeeded;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getActive()
	{
		if (is_null($this->isActive))
			$this->isActive = $this->isBound();

		return $this->isActive;
	}

	/**
	 * Activate or deactivate automatic checking
	 *
	 * @param bool $isActive Set true for enable checking on every request.
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @return $this
	 */
	public function setActive($isActive = false)
	{
		if (!is_bool($isActive))
			throw new ArgumentTypeException('isActive', 'bool');

		$this->isActive = $isActive;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHosts()
	{
		return $this->hosts;
	}

	/**
	 * Set allowed hosts
	 *
	 * @param string $hosts Allowed hosts (wild card supported, e.g.: *.example.com).
	 * @param bool $ignoreChecking Set false for disable host validating before set.
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws LogicException
	 * @return $this
	 */
	public function setHosts($hosts, $ignoreChecking = false)
	{
		if (!is_string($hosts))
			throw new ArgumentTypeException('host', 'string');

		if (!$ignoreChecking)
			$this->checkNewHosts($hosts);

		$this->hosts = $hosts;

		return $this;
	}

	/**
	 * Return regular expressions (based on hosts) for checking.
	 * Note: regular expression is cached for performance improvement and auto cleared after saving {@see save}
	 *
	 * @return string
	 */
	public function getValidationRegExp()
	{
		if ($this->validationRegExp)
			return $this->validationRegExp;

		$cache = Data\Cache::createInstance();
		if($cache->initCache($this->cacheTtl, $this->cacheId, $this->cacheInitPath) )
		{
			$this->validationRegExp = $cache->getVars();
		}
		else
		{
			$this->validationRegExp = $this->genValidationRegExp($this->hosts);
			$cache->startDataCache();
			$cache->endDataCache($this->validationRegExp);
		}

		return $this->validationRegExp;
	}

	/**
	 * Save all properties, enable automatic checking and clear cache if needed
	 *
	 * @return $this
	 */
	public function save()
	{
		Config\Option::set('security', $this->optionPrefix.'hosts', $this->hosts, '');
		Config\Option::set('security', $this->optionPrefix.'action', $this->action, '');
		Config\Option::set('security', $this->optionPrefix.'action_options', serialize($this->actionOptions), '');
		Config\Option::set('security', $this->optionPrefix.'logging', $this->isLogNeeded, '');
		if (!is_null($this->isActive))
		{

			if ($this->isActive)
			{
				EventManager::getInstance()
					->registerEventHandler('main', 'OnPageStart', 'security', get_class($this), 'onPageStart');
			}
			else
			{
				EventManager::getInstance()
					->unRegisterEventHandler('main', 'OnPageStart', 'security', get_class($this), 'onPageStart');
			}
		}
		Data\Cache::createInstance()->clean($this->cacheId, $this->cacheInitPath);

		return $this;
	}

	/**
	 * Return true if HostRestriction already handled on system event "OnPageStart"
	 *
	 * @return bool
	 */
	protected function isBound()
	{
		$handlers = EventManager::getInstance()->findEventHandlers('main', 'OnPageStart', array('security'));

		foreach($handlers as $handler)
		{
			if ($handler['TO_CLASS'] === get_class($this))
				return true;
		}

		return false;
	}

	/**
	 * Return requested host for checking
	 *
	 * @return string
	 */
	protected function getTargetHost()
	{
		static $host = null;
		if (is_null($host))
			$host = Context::getCurrent()->getServer()->getHttpHost();

		return $host;
	}

	/**
	 * Logging current host by event manager
	 *
	 * @param string $host Requested host.
	 * @return bool
	 */
	protected function log($host)
	{
		return \CSecurityEvent::getInstance()->doLog('SECURITY', 'SECURITY_HOST_RESTRICTION', 'HTTP_HOST', $host);
	}

	/**
	 * Perform some actions when requested host is not allowed by host restriction policy
	 *
	 * @return $this
	 */
	protected function doActions()
	{
		switch($this->action)
		{
			case self::ACTION_STOP:
				/** @noinspection PhpIncludeInspection */
				include Loader::getLocal('/admin/security_403.php');
				die();
				break;
			case self::ACTION_REDIRECT:
				localRedirect($this->actionOptions['host'], true);
				break;
			default:
				trigger_error('Unknown action', E_USER_WARNING);
		}

		return $this;
	}

	/**
	 * Generates regular expression obtained from hosts
	 *
	 * @param string $hosts Allowed hosts (wild card supported, e.g.: *.example.com).
	 * @return string
	 */
	protected function genValidationRegExp($hosts)
	{
		$hosts = trim($hosts);
		$hosts = preg_quote($hosts);
		$hosts = preg_replace(
			array('~\#.*~', '~\\\\\*~', '~\s+~s'),
			array('',       '.*',       '|'),
			$hosts
		);

		return "#^\s*($hosts)(:\d+)?\s*$#iD";
	}

	/**
	 * Checks the host to detect logical errors (eg blocking the current host)
	 *
	 * @param string $hosts
	 * @return $this
	 * @throws \Bitrix\Security\LogicException
	 */
	protected function checkNewHosts($hosts)
	{
		$this->validationRegExp = $this->genValidationRegExp($hosts);

		if (!preg_match($this->validationRegExp, $this->getTargetHost()))
			throw new LogicException('Current host blocked', 'SECURITY_HOSTS_SELF_BLOCK');

		if (preg_match($this->validationRegExp, 'some-invalid-host.com'))
			throw new LogicException('Any host passed restrictions', 'SECURITY_HOSTS_ANY_HOST');

		return $this;
	}
}