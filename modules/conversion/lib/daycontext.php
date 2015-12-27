<?php

namespace Bitrix\Conversion;

use Bitrix\Main\SiteTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Type\Date;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;

final class DayContext extends Internals\BaseContext
{
	/** Add value to counter. If counter not exists set counter to value.
	 * @param string    $name  - counter name
	 * @param int|float $value - number to add
	 */
	public function addCounter($name, $value)
	{
		if (($id = $this->id) === null)
		{
			$value = (float) $value;

			if ($v =& self::$session['PENDING_COUNTERS'][$name])
			{
				$v += $value;
			}
			else
			{
				$v = $value;
			}
		}
		else
		{
			parent::addCounter(new Date(), $name, $value);
		}
	}

	/** Add value to counter (once a day per person). If counter not exists set counter to value.
	 * @param string    $name  - counter name
	 * @param int|float $value - number to add
	 */
	public function addDayCounter($name, $value)
	{
		$session =& self::$session;
		$unique =& $session['UNIQUE'];

		if (! in_array($name, $unique, true))
		{
			$unique []= $name;

			if (($id = $this->id) === null)
			{
				$session['PENDING_DAY_COUNTERS'][$name] = $value;
			}
			else
			{
				$this->addCounter($name, $value);
				$this->setCookie(); // TODO HACK save to database into session
			}
		}
	}

	/** Add currency value to counter. If counter not exists set counter to value.
	 * @param string           $name     - counter name
	 * @param int|float|string $value    - numeric value
	 * @param string           $currency - currency code (eg: RUB)
	 */
	public function addCurrencyCounter($name, $value, $currency)
	{
		$this->addCounter($name, Utils::convertToBaseCurrency($value, $currency));
	}

	/** Attach entity item to context.
	 * @param string     $entity
	 * @param string|int $item
	 * @throws ArgumentTypeException
	 */
	public function attachEntityItem($entity, $item)
	{
		if (! is_string($entity))
			throw new ArgumentTypeException('entity', 'string');

		if (! is_scalar($item))
			throw new ArgumentTypeException('item', 'scalar');

		if (($id = $this->id) === null)
		{
			self::$session['PENDING_ENTITY_ITEMS'][$entity.':'.$item] = array('ENTITY' => $entity, 'ITEM' => $item);
		}
		else
		{
			try
			{
				$result = Internals\ContextEntityItemTable::add(array(
					'CONTEXT_ID' => $id,
					'ENTITY'     => $entity,
					'ITEM'       => $item,
				));

				$result->isSuccess(); // TODO
			}
			catch (\Bitrix\Main\DB\SqlQueryException $e)
			{
				// TODO log??
			}
		}
	}

	/** Get context of attached entity item.
	 * @param $entity
	 * @param $item
	 * @return self
	 */
	static public function getEntityItemInstance($entity, $item)
	{
		$instance = new self;

		if ($row = Internals\ContextEntityItemTable::getRow(array(
			'select' => array('CONTEXT_ID'),
			'filter' => array('=ENTITY' => $entity, '=ITEM' => $item),
		)))
		{
			$instance->id = $row['CONTEXT_ID'];
		}
		else
		{
			$instance->id = self::EMPTY_CONTEXT_ID;
		}

		self::getInstance(); // load cookie unique counters

		return $instance;
	}

	/** @var self $instance */
	static private $instance;
	static private $session;

	/** Get day context singleton instance.
	 * @return self
	 */
	static public function getInstance()
	{
		if (! self::$instance)
		{
			$instance = new self;
			$varName  = self::getVarName();
			$session  =& $_SESSION[$varName];
			$expire   = strtotime('today 23:59');

			if (! (is_array($session) && is_int($session['ID']) && $session['EXPIRE'] === $expire))
			{
				$session = array('ID' => null, 'EXPIRE' => $expire, 'UNIQUE' => array());

				//global $APPLICATION; $cookie = $APPLICATION->get_cookie($varname);
				if ($cookie = $_COOKIE[$varName])
				{
					try
					{
						$cookie = Json::decode($cookie);
					}
					catch (ArgumentException $e)
					{
					}
				}

				// check if cookie is valid
				if (   is_array($cookie)
					&& is_array($cookie['UNIQUE'])
					&& $cookie['EXPIRE'] === $expire
					&& ($id = $cookie['ID']) !== null
					&& is_int($id)
					&& ($id === self::EMPTY_CONTEXT_ID || Internals\ContextTable::getByPrimary($id)->fetch())
				)
				{
					$session['ID'    ] = $id;
					$session['UNIQUE'] = $cookie['UNIQUE'];
				}
			}

			$instance->id = $session['ID'];
			self::$session =& $session;
			self::$instance = $instance;
		}

		return self::$instance;
	}

	private function setCookie()
	{
		$session = self::$session;

		//$APPLICATION->set_cookie($varname, $id, strtotime('today 23:59'));
		setcookie(self::getVarName(), Json::encode(array(
			'ID'     => $session['ID'    ],
			'EXPIRE' => $session['EXPIRE'],
			'UNIQUE' => $session['UNIQUE'],
		)), strtotime('+1 year'), '/');
	}

	/** @internal */
	static public function saveInstance()
	{
		$instance = self::getInstance();
		$session =& self::$session;

		// save day context

		if ($instance->id === null)
		{
			foreach (EventManager::getInstance()->findEventHandlers('conversion', 'OnSetDayContextAttributes') as $handler)
			{
				ExecuteModuleEventEx($handler, array($instance));
			}

			$instance->save();
			$session['ID'] = $instance->id;
			$instance->setCookie();
		}

		// save pending counters

		if ($pending =& $session['PENDING_COUNTERS'])
		{
			foreach($pending as $name => $value)
			{
				$instance->addCounter($name, $value);
			}

			$pending = array();
		}

		if ($pending =& $session['PENDING_DAY_COUNTERS'])
		{
			foreach($pending as $name => $value)
			{
				$instance->addDayCounter($name, $value);
			}

			$pending = array();
		}

		if ($pending =& $session['PENDING_ENTITY_ITEMS'])
		{
			foreach($pending as $i)
			{
				$instance->attachEntityItem($i['ENTITY'], $i['ITEM']);
			}

			$pending = array();
		}
	}

	/** @internal */
	static public function getVarName()
	{
		static $name;

		if (! $name)
		{
			$name = 'BITRIX_CONVERSION_CONTEXT_'.self::getSiteId();
		}

		return $name;
	}

	/** @internal */
	static public function getSiteId()
	{
		static $siteId = null;

		if ($siteId === null)
		{
			$siteId = '';

			if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
			{
				// In admin section SITE_ID = "ru" !!!

				if ($row = SiteTable::getList(array(
					'select' => array('LID'),
					'order'  => array('DEF' => 'DESC', 'SORT' => 'ASC'),
					'limit'  => 1,
				))->fetch())
				{
					$siteId = $row['LID'];
				}
			}
			else
			{
				$siteId = SITE_ID;
			}
		}

		return $siteId;
	}
}
