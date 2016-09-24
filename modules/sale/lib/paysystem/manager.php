<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Mail;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Internals\PaymentTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Internals\ServiceRestrictionTable;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Services\PaySystem\Restrictions;

Loc::loadMessages(__FILE__);

/**
 * Class PaySystemManager
 * @package Bitrix\Sale\Payment
 */
final class Manager
{
	const CACHE_ID = "BITRIX_SALE_INNER_PS_ID";
	const TTL = 31536000;
	/**
	 * @var array
	 */
	private static $handlerDirectories = array(
		'CUSTOM' => '',
		'LOCAL' => '/local/php_interface/include/sale_payment/',
		'SYSTEM' => '/bitrix/modules/sale/handlers/paysystem/',
		'SYSTEM_OLD' => '/bitrix/modules/sale/payment/'
	);

	/**
	 * @return array
	 */
	public static function getHandlerDirectories()
	{
		$handlerDirectories = self::$handlerDirectories;
		$handlerDirectories['CUSTOM'] = Option::get("sale", "path2user_ps_files", BX_PERSONAL_ROOT."/php_interface/include/sale_payment/");

		return $handlerDirectories;
	}

	/**
	 * @param array $params
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getList(array $params = array())
	{
		return PaySystemActionTable::getList($params);
	}

	/**
	 * @param $id
	 * @return array|false
	 */
	public static function getById($id)
	{
		if ($id <= 0)
			return false;

		$params = array(
			'select' => array('*'),
			'filter' => array('ID' => $id)
		);

		$dbRes = self::getList($params);
		return $dbRes->fetch();
	}

	/**
	 * @param $code
	 * @return array|bool
	 */
	public static function getByCode($code)
	{
		$params = array(
			'select' => array('*'),
			'filter' => array('CODE' => $code)
		);

		$dbRes = self::getList($params);
		return $dbRes->fetch();
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return \Bitrix\Main\Entity\UpdateResult
	 * @throws \Exception
	 */
	public static function update($primary, array $data)
	{
		return PaySystemActionTable::update($primary, $data);
	}

	/**
	 * @param Request $request
	 * @return array|false
	 */
	public static function searchByRequest(Request $request)
	{
		$documentRoot = Application::getDocumentRoot();

		$items = self::getList(array('select' => array('*')));

		while ($item = $items->fetch())
		{
			$name = $item['ACTION_FILE'];

			foreach (self::getHandlerDirectories() as $type => $path)
			{
				if (File::isFileExists($documentRoot.$path.$name.'/handler.php'))
				{
					require_once($documentRoot.$path.$name.'/handler.php');

					$className = static::getClassNameFromPath($item['ACTION_FILE']);
					if (class_exists($className) && is_callable(array($className, 'isMyResponse')))
					{
						if ($className::isMyResponse($request, $item['ID']))
							return $item;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param string $className
	 * @return mixed|string
	 */
	public static function getFolderFromClassName($className)
	{
		$pos = strrpos($className, '\\');
		if ($pos !== false)
			$className = substr($className, $pos + 1);

		$folder = str_replace('Handler', '', $className);
		$folder = self::sanitize($folder);
		$folder = ToLower($folder);

		return $folder;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public static function sanitize($name)
	{
		return preg_replace("/[^a-z0-9._]/i", "", $name);
	}

	/**
	 * @param $paymentId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getIdsByPayment($paymentId)
	{
		$params = array(
			'select' => array('ID', 'ORDER_ID'),
		);

		if (intval($paymentId).'|' == $paymentId.'|')
		{
			$params['filter']['ID'] = $paymentId;
		}
		else
		{
			$params['filter']['ACCOUNT_NUMBER'] = $paymentId;
		}

		$data = PaymentTable::getRow($params);

		return array((int)$data['ORDER_ID'], (int)$data['ID']);
	}

	/**
	 * @return array
	 */
	public static function getConsumersList()
	{
		$result = array();

		$items = self::getList();

		while ($item = $items->fetch())
		{
			$data = self::getHandlerDescription($item['ACTION_FILE']);
			$data['NAME'] = $item['NAME'];
			$data['GROUP'] = 'PAYSYSTEM';
			$data['PROVIDERS'] = array('VALUE', 'COMPANY', 'ORDER', 'USER', 'PROPERTY', 'PAYMENT');

			$result['PAYSYSTEM_'.$item['ID']] = $data;
		}

		return $result;
	}

	/**
	 * @param array $arPSCorrespondence
	 * @return array
	 */
	private static function convertCodesToNewFormat(array $arPSCorrespondence)
	{
		if ($arPSCorrespondence)
		{
			foreach ($arPSCorrespondence as $i => $property)
			{
				if ($property['TYPE'] == 'SELECT')
				{
					$options = array();
					foreach ($property['VALUE'] as $code => $value)
						$options[$code] = $value['NAME'];

					$arPSCorrespondence[$i] = array(
						'NAME' => $property['NAME'],
						'INPUT' => array(
							'TYPE' => 'ENUM',
							'OPTIONS' => $options
						)
					);
				}
				else if ($property['TYPE'] == 'FILE')
				{
					$arPSCorrespondence[$i] = array(
						'NAME' => $property['NAME'],
						'INPUT' => array(
							'TYPE' => 'FILE'
						)
					);
				}

				if (array_key_exists('DESCR', $property))
					$arPSCorrespondence[$i]['DESCRIPTION'] = $property['DESCR'];

				$arPSCorrespondence[$i]['GROUP'] = 'PS_OTHER';
			}

			return $arPSCorrespondence;
		}

		return array();
	}

	/**
	 * @param $paySystemId
	 * @return string
	 */
	public static function getPsType($paySystemId)
	{
		$params = array(
			'select' => array('IS_CASH'),
			'filter' => array('ID' => $paySystemId)
		);

		$dbRes = self::getList($params);
		$data = $dbRes->fetch();

		return $data['IS_CASH'];
	}

	/**
	 * @param Payment $payment
	 * @param int $mode
	 * @return array
	 */
	public static function getListWithRestrictions(Payment $payment, $mode = Restrictions\Manager::MODE_CLIENT)
	{
		$result = array();

		$dbRes = self::getList(array(
			'filter' => array('ACTIVE' => 'Y'),
			'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
		));

		while ($paySystem = $dbRes->fetch())
		{
			if ($mode == Restrictions\Manager::MODE_MANAGER)
			{
				$checkServiceResult = Restrictions\Manager::checkService($paySystem['ID'], $payment, $mode);
				if ($checkServiceResult != Restrictions\Manager::SEVERITY_STRICT)
				{
					if ($checkServiceResult == Restrictions\Manager::SEVERITY_SOFT)
						$paySystem['RESTRICTED'] = $checkServiceResult;
					$result[$paySystem['ID']] = $paySystem;
				}
			}
			else if ($mode == Restrictions\Manager::MODE_CLIENT)
			{
				if (Restrictions\Manager::checkService($paySystem['ID'], $payment, $mode) === Restrictions\Manager::SEVERITY_NONE)
					$result[$paySystem['ID']] = $paySystem;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getHandlerList()
	{
		$documentRoot = Application::getDocumentRoot();
		$result = array(
			'SYSTEM' => array(),
			'USER' => array()
		);

		$oldHandlerList = array('yandex_3x', 'bill', 'bill_de', 'bill_ua', 'bill_en', 'bill_la', 'paymaster', 'assist', 'liqpay', 'qiwi', 'sberbank_new', 'webmoney_web', 'money.mail', 'payment_forward_calc', 'payment_forward', 'roboxchange', 'cash');

		foreach (self::getHandlerDirectories() as $type => $dir)
		{
			if (!Directory::isDirectoryExists($documentRoot.$dir))
				continue;

			$directory = new Directory($documentRoot.$dir);
			foreach ($directory->getChildren() as $handler)
			{
				if (!$handler->isDirectory() || (in_array($handler->getName(), $oldHandlerList) && $type == 'SYSTEM_OLD'))
					continue;

				$isDescriptionExist = false;
				/** @var Directory $handler */
				foreach ($handler->getChildren() as $item)
				{
					if ($item->isFile())
					{
						$data = array();
						$psTitle = '';

						if (strpos($item->getName(), '.description') !== false)
						{
							$handlerName = $handler->getName();

							include $item->getPath();

							if (array_key_exists('NAME', $data))
							{
								$psTitle = $data['NAME'].' ('.$handlerName.')';
							}
							else
							{
								if ($psTitle == '')
									$psTitle = $handlerName;
								else
									$psTitle .= ' ('.$handlerName.')';

								$handlerName = str_replace($documentRoot, '', $handler->getPath());
							}
							$group = (strpos($type, 'SYSTEM') !== false) ? 'SYSTEM' : 'USER';

							if (!isset($result[$group][$handlerName]))
								$result[$group][$handlerName] = $psTitle;
							$isDescriptionExist = true;
							continue(2);
						}
					}
				}

				if (!$isDescriptionExist)
				{
					$group = (strpos($type, 'SYSTEM') !== false) ? 'SYSTEM' : 'USER';
					$handlerName = str_replace($documentRoot, '', $handler->getPath());
					$result[$group][$handlerName] = $handler->getName();
				}
			}
		}
		return $result;
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function getClassNameFromPath($path)
	{
		$pos = strrpos($path, '/');

		if ($pos == strlen($path))
		{
			$path = substr($path, 0, $pos - 1);
			$pos = strrpos($path, '/');
		}

		if ($pos !== false)
			$path = substr($path, $pos+1);

		return "Sale\\Handlers\\PaySystem\\".$path.'Handler';
	}

	/**
	 * @param $handler
	 * @return array
	 */
	public static function getHandlerDescription($handler)
	{
		$path = null;
		$data = array();
		$documentRoot = Application::getDocumentRoot();

		if (strpos($handler, '/') !== false)
		{
			$psTitle = '';
			$arPSCorrespondence = array();

			$actionFile = $documentRoot.$handler.'/.description.php';
			if (File::isFileExists($actionFile))
			{
				require $actionFile;

				if ($arPSCorrespondence)
				{
					$codes = self::convertCodesToNewFormat($arPSCorrespondence);

					if ($codes)
						return array('NAME' => $psTitle, 'SORT' => 100, 'CODES' => $codes);
				}
				elseif ($data)
				{
					return $data;
				}
			}
		}
		else
		{
			$path = self::getPathToHandlerFolder($handler);
			if ($path !== null)
			{
				$path = $documentRoot.$path.'/.description.php';
				if (File::isFileExists($path))
				{
					require $path;

					return $data;
				}
			}
		}

		return $data;
	}

	/**
	 * @param $folder
	 * @return null|string
	 */
	public static function getPathToHandlerFolder($folder)
	{
		$documentRoot = Application::getDocumentRoot();

		if (strpos($folder, '/') !== false)
		{
			return $folder;
		}
		else
		{
			$dirs = self::getHandlerDirectories();

			foreach ($dirs as $dir)
			{
				$path = $dir.$folder;
				if (!Directory::isDirectoryExists($documentRoot.$path))
					continue;

				return $path;
			}
		}

		return null;
	}

	/**
	 * @return int
	 */
	public static function getInnerPaySystemId()
	{
		$id = 0;
		$cacheManager = Application::getInstance()->getManagedCache();

		if($cacheManager->read(self::TTL, self::CACHE_ID))
			$id = $cacheManager->get(self::CACHE_ID);

		if ($id <= 0)
		{
			$data = PaySystemActionTable::getRow(
				array(
					'select' => array('ID'),
					'filter' => array('ACTION_FILE' => 'inner')
				)
			);
			if ($data === null)
				$id = self::createInnerPaySystem();
			else
				$id = $data['ID'];

			$cacheManager->set(self::CACHE_ID, $id);
		}

		return $id;
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	private static function createInnerPaySystem()
	{
		$paySystemSettings = array(
			'NAME' => Loc::getMessage('SALE_PS_MANAGER_INNER_NAME'),
			'PSA_NAME' => Loc::getMessage('SALE_PS_MANAGER_INNER_NAME'),
			'ACTION_FILE' => 'inner',
			'ACTIVE' => 'Y',
			'NEW_WINDOW' => 'N'
		);

		$imagePath = Application::getDocumentRoot().'/bitrix/images/sale/sale_payments/inner.png';
		if (File::isFileExists($imagePath))
		{
			$paySystemSettings['LOGOTIP'] = \CFile::MakeFileArray($imagePath);
			$paySystemSettings['LOGOTIP']['MODULE_ID'] = "sale";
			\CFile::SaveForDB($paySystemSettings, 'LOGOTIP', 'sale/paysystem/logotip');
		}

		$result = PaySystemActionTable::add($paySystemSettings);

		if ($result->isSuccess())
			return $result->getId();

		return 0;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function isExist($id)
	{
		return (bool)self::getById($id);
	}

	/**
	 * @param $id
	 * @return Service|null
	 */
	public static function getObjectById($id)
	{
		if ($id <= 0)
			return null;

		$data = Manager::getById($id);

		if (is_array($data) && $data)
			return new Service($data);

		return null;
	}

	/**
	 * @param $folder
	 * @param int $paySystemId
	 * @return array|mixed
	 */
	public static function getTariff($folder, $paySystemId = 0)
	{
		$documentRoot = Application::getDocumentRoot();
		$result = array();

		$path = self::getPathToHandlerFolder($folder);
		if ($path !== null)
		{
			if (File::isFileExists($documentRoot.$path.'/handler.php'))
			{
				require_once $documentRoot.$path.'/handler.php';

				$className = self::getClassNameFromPath($folder);
				if (class_exists($className))
				{
					$interfaces = class_implements($className);
					if (array_key_exists('Bitrix\Sale\PaySystem\IPayable', $interfaces))
						$result = $className::getStructure($paySystemId);
				}
			}
		}
		else
		{
			$result = \CSalePaySystemsHelper::getPaySystemTarif($folder, $paySystemId);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getBusValueGroups()
	{
		return array(
			'CONNECT_SETTINGS_ALFABANK' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_ALFABANK'), 'SORT' => 100),
			'CONNECT_SETTINGS_AUTHORIZE' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_AUTHORIZE'), 'SORT' => 100),
			'CONNECT_SETTINGS_YANDEX' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_YANDEX'), 'SORT' => 100),
			'CONNECT_SETTINGS_YANDEX_INVOICE' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_YANDEX_INVOICE'), 'SORT' => 100),
			'CONNECT_SETTINGS_WEBMONEY' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_WEBMONEY'), 'SORT' => 100),
			'CONNECT_SETTINGS_ASSIST' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_ASSIST'), 'SORT' => 100),
			'CONNECT_SETTINGS_ROBOXCHANGE' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_ROBOXCHANGE'), 'SORT' => 100),
			'CONNECT_SETTINGS_QIWI' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_QIWI'), 'SORT' => 100),
			'CONNECT_SETTINGS_PAYPAL' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_PAYPAL'), 'SORT' => 100),
			'CONNECT_SETTINGS_PAYMASTER' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_PAYMASTER'), 'SORT' => 100),
			'CONNECT_SETTINGS_LIQPAY' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_LIQPAY'), 'SORT' => 100),
			'CONNECT_SETTINGS_BILL' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_BILL'), 'SORT' => 100),
			'CONNECT_SETTINGS_BILLDE' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_BILLDE'), 'SORT' => 100),
			'CONNECT_SETTINGS_BILLEN' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_BILLEN'), 'SORT' => 100),
			'CONNECT_SETTINGS_BILLUA' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_BILLUA'), 'SORT' => 100),
			'CONNECT_SETTINGS_BILLLA' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_CONNECT_SETTINGS_BILLLA'), 'SORT' => 100),
			'GENERAL_SETTINGS' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_GENERAL_SETTINGS'), 'SORT' => 100),
			'COLUMN_SETTINGS' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_COLUMN_SETTINGS'), 'SORT' => 100),
			'VISUAL_SETTINGS' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_VISUAL_SETTINGS'), 'SORT' => 100),
			'HEADER_SETTINGS' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_HEADER_SETTINGS'), 'SORT' => 100),
			'FOOTER_SETTINGS' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_FOOTER_SETTINGS'), 'SORT' => 100),
			'PAYMENT' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_PAYMENT'), 'SORT' => 200),
			'PAYSYSTEM' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_PAYSYSTEM'), 'SORT' => 500),
			'PS_OTHER' => array('NAME' => Loc::getMessage('SALE_PS_MANAGER_GROUP_PS_OTHER'), 'SORT' => 10000)
		);
	}

	/**
	 * @param $primary
	 * @return \Bitrix\Main\Entity\DeleteResult
	 */
	public static function delete($primary)
	{
		$result = PaySystemActionTable::delete($primary);
		if ($result->isSuccess())
		{
			$restrictionList =  Restrictions\Manager::getRestrictionsList($primary);
			if ($restrictionList)
			{
				Restrictions\Manager::getClassesList();

				foreach ($restrictionList as $restriction)
				{
					/** @var \Bitrix\Sale\Services\Base\Restriction $className */
					$className = $restriction["CLASS_NAME"];
					if (is_subclass_of($className, '\Bitrix\Sale\Services\Base\Restriction'))
					{
						$className::delete($restriction['ID'], $primary);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $paySystemId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getPersonTypeIdList($paySystemId)
	{
		$data = array();

		$dbRestriction = ServiceRestrictionTable::getList(array(
			'filter' => array(
				'SERVICE_ID' => $paySystemId,
				'SERVICE_TYPE' => Restrictions\Manager::SERVICE_TYPE_PAYMENT,
				'=CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType'
			)
		));

		while ($restriction = $dbRestriction->fetch())
			$data = array_merge($data, $restriction['PARAMS']['PERSON_TYPE_ID']);

		return $data;
	}

	/**
	 * @param int $paySystemId
	 * @param null $requestId
	 * @return string
	 */
	public static function checkMovementListStatus($paySystemId, $requestId = null)
	{
		$service = self::getObjectById($paySystemId);

		if ($service && $service->isRequested())
		{
			$status = $service->checkMovementListStatus($requestId);
			if ($status == true)
			{
				$movementList = $service->getMovementList($requestId);
				$service->applyAccountMovementList($movementList);

				return '';
			}
		}

		return '\Bitrix\Sale\PaySystem\Manager::getMovementListStatus('.$paySystemId.',\''.$requestId.'\');';
	}
}