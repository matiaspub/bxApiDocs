<?
namespace Bitrix\Bizproc;

use \Bitrix\Main\Loader;
use \Bitrix\Rest\RestException;
use \Bitrix\Rest\AccessException;
use \Bitrix\OAuth\ClientTable;

Loader::includeModule('rest');

class RestService extends \IRestService
{
	const SCOPE = 'bizproc';
	protected static $app;

	const ERROR_UNSUPPORTED_PROTOCOL = 'ERROR_UNSUPPORTED_PROTOCOL';
	const ERROR_WRONG_HANDLER_URL = 'ERROR_WRONG_HANDLER_URL';
	const ERROR_HANDLER_URL_MATCH = 'ERROR_HANDLER_URL_MATCH';

	const ERROR_ACTIVITY_ALREADY_INSTALLED = 'ERROR_ACTIVITY_ALREADY_INSTALLED';
	const ERROR_ACTIVITY_ADD_FAILURE = 'ERROR_ACTIVITY_ADD_FAILURE';
	const ERROR_ACTIVITY_VALIDATION_FAILURE = 'ERROR_ACTIVITY_VALIDATION_FAILURE';
	const ERROR_ACTIVITY_NOT_FOUND = 'ERROR_ACTIVITY_NOT_FOUND';
	const ERROR_EMPTY_LOG_MESSAGE = 'ERROR_EMPTY_LOG_MESSAGE';
	const ERROR_WRONG_WORKFLOW_ID = 'ERROR_WRONG_WORKFLOW_ID';
	const ERROR_WRONG_ACTIVITY_NAME = 'ERROR_WRONG_ACTIVITY_NAME';

	public static function onRestServiceBuildDescription()
	{
		return array(
			static::SCOPE => array(
				'bizproc.activity.add' => array(__CLASS__, 'addActivity'),
				'bizproc.activity.delete' => array(__CLASS__, 'deleteActivity'),
				'bizproc.activity.log' => array(__CLASS__, 'writeActivityLog'),
				'bizproc.activity.list' => array(__CLASS__, 'getActivityList'),
				'bizproc.event.send' => array(__CLASS__, 'sendEvent'),
			),
		);
	}

	/**
	 * Deletes application activities.
	 * @param array $fields Fields describes application.
	 * @return void
	 */
	public static function onRestAppDelete(array $fields)
	{
		$fields = array_change_key_case($fields, CASE_UPPER);
		if (empty($fields['APP_ID']))
			return;

		$app = \CBitrix24App::getByID($fields['APP_ID']);
		if(!$app)
			return;

		$iterator = RestActivityTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=APP_ID' => $app['APP_ID'])
		));

		while ($activity = $iterator->fetch())
		{
			RestActivityTable::delete($activity['ID']);
		}
	}

	/**
	 * Deletes application activities.
	 * @param array $fields Fields describes application.
	 * @return void
	 */
	public static function onRestAppUpdate(array $fields)
	{
		static::onRestAppDelete($fields);
	}

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws \Exception
	 */
	public static function addActivity($params, $n, $server)
	{
		self::checkAdminPermissions();
		$params = self::prepareActivityData($params);
		self::validateActivity($params, $server);

		$params['APP_ID'] = $server->getAppId();
		$params['INTERNAL_CODE'] = self::generateInternalCode($params);
		$params['APP_NAME'] = self::getAppName($params['APP_ID']);

		$iterator = RestActivityTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=INTERNAL_CODE' => $params['INTERNAL_CODE'])
		));
		$result = $iterator->fetch();
		if ($result)
		{
			throw new RestException('Activity already installed!', self::ERROR_ACTIVITY_ALREADY_INSTALLED);
		}

		$params['AUTH_USER_ID'] = isset($params['AUTH_USER_ID'])? (int) $params['AUTH_USER_ID'] : 0;

		$result = RestActivityTable::add($params);

		if ($result->getErrors())
			throw new RestException('Activity save error!', self::ERROR_ACTIVITY_ADD_FAILURE);

		return true;
	}

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteActivity($params, $n, $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		self::checkAdminPermissions();
		self::validateActivityCode($params['CODE']);
		$params['APP_ID'] = $server->getAppId();
		$internalCode = self::generateInternalCode($params);

		$iterator = RestActivityTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=INTERNAL_CODE' => $internalCode)
		));
		$result = $iterator->fetch();
		if (!$result)
		{
			throw new RestException('Activity not found!', self::ERROR_ACTIVITY_NOT_FOUND);
		}
		RestActivityTable::delete($result['ID']);
		return true;
	}

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function sendEvent($params, $n, $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		list($workflowId, $activityName, $eventId) = self::extractEventToken($params['EVENT_TOKEN']);

		\CBPRuntime::sendExternalEvent(
			$workflowId,
			$activityName,
			array(
				'EVENT_ID' => $eventId,
				'RETURN_VALUES' => isset($params['RETURN_VALUES']) ? $params['RETURN_VALUES'] : array(),
				'LOG_MESSAGE' => isset($params['LOG_MESSAGE']) ? $params['LOG_MESSAGE'] : '',
			)
		);

		return true;
	}

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function writeActivityLog($params, $n, $server)
	{
		$params = array_change_key_case($params, CASE_UPPER);
		list($workflowId, $activityName, $eventId) = self::extractEventToken($params['EVENT_TOKEN']);

		$logMessage = isset($params['LOG_MESSAGE']) ? $params['LOG_MESSAGE'] : '';

		if (empty($logMessage))
			throw new RestException('Empty log message!', self::ERROR_EMPTY_LOG_MESSAGE);

		\CBPRuntime::sendExternalEvent(
			$workflowId,
			$activityName,
			array(
				'EVENT_ID' => $eventId,
				'LOG_ACTION' => true,
				'LOG_MESSAGE' => $logMessage
			)
		);

		return true;
	}

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getActivityList($params, $n, $server)
	{
		self::checkAdminPermissions();
		$iterator = RestActivityTable::getList(array(
			'select' => array('CODE'),
			'filter' => array('=APP_ID' => $server->getAppId())
		));

		$result = array();
		while ($row = $iterator->fetch())
		{
			$result[] = $row['CODE'];
		}
		return $result;
	}

	private static function checkAdminPermissions()
	{
		global $USER;
		if (!isset($USER)
			|| !is_object($USER)
			|| (!$USER->isAdmin() && !(Loader::includeModule("bitrix24") && \CBitrix24::isPortalAdmin($USER->getID())))
		)
		{
			throw new AccessException();
		}
	}

	private static function generateInternalCode($data)
	{
		return md5($data['APP_ID'].'@'.$data['CODE']);
	}

	private static function getAppName($appId)
	{
		$iterator = \CBitrix24App::getList(array(), array('APP_ID' => $appId));
		$app = $iterator->fetch();
		$result = array('*' => $app['APP_NAME']);

		$iterator = \CBitrix24AppLang::getList($appId);
		while($lang = $iterator->fetch())
		{
			$result[strtoupper($lang['LANGUAGE_ID'])] = $lang['MENU_NAME'];
		}

		return $result;
	}

	private static function prepareActivityData(array $data, $ignore = false)
	{
		if (!$ignore)
			$data = array_change_key_case($data, CASE_UPPER);
		foreach ($data as $key => &$field)
		{
			if (is_array($field))
				$field = self::prepareActivityData($field, $key == 'PROPERTIES' || $key == 'RETURN_PROPERTIES' || $key == 'OPTIONS');
		}
		return $data;
	}

	private static function validateActivity($data, $server)
	{
		if (!is_array($data) || empty($data))
			throw new RestException('Empty data!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);

		static::validateActivityCode($data['CODE']);
		static::validateActivityHandler($data['HANDLER'], $server);
		if (empty($data['NAME']))
			throw new RestException('Empty activity NAME!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);

		if (isset($data['PROPERTIES']))
			static::validateActivityProperties($data['PROPERTIES']);

		if (isset($data['RETURN_PROPERTIES']))
			static::validateActivityProperties($data['RETURN_PROPERTIES']);
		if (isset($data['DOCUMENT_TYPE']))
			static::validateActivityDocumentType($data['DOCUMENT_TYPE']);
		if (isset($data['FILTER']) && !is_array($data['FILTER']))
			throw new RestException('Wrong activity FILTER!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
	}

	private static function validateActivityCode($code)
	{
		if (empty($code))
			throw new RestException('Empty activity code!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
		if (!preg_match('#^[a-z0-9\.\-_]+$#i', $code))
			throw new RestException('Wrong activity code!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
	}

	private static function validateActivityHandler($handler, $server)
	{
		$handlerData = parse_url($handler);

		if (is_array($handlerData)
			&& strlen($handlerData['host']) > 0
			&& strpos($handlerData['host'], '.') > 0
		)
		{
			if ($handlerData['scheme'] == 'http' || $handlerData['scheme'] == 'https')
			{
				$host = $handlerData['host'];
				$app = self::getApp($server);
				if (strlen($app['URL']) > 0)
				{
					$urls = array($app['URL']);

					if (strlen($app['URL_DEMO']) > 0)
					{
						$urls[] = $app['URL_DEMO'];
					}
					if (strlen($app['URL_INSTALL']) > 0)
					{
						$urls[] = $app['URL_INSTALL'];
					}

					$found = false;
					foreach($urls as $url)
					{
						$a = parse_url($url);
						if ($host == $a['host'] || $a['host'] == 'localhost')
						{
							$found = true;
							break;
						}
					}

					if(!$found)
					{
						throw new RestException('Handler URL host doesn\'t match application url', self::ERROR_HANDLER_URL_MATCH);
					}
				}
			}
			else
			{
				throw new RestException('Unsupported event handler protocol', self::ERROR_UNSUPPORTED_PROTOCOL);
			}
		}
		else
		{
			throw new RestException('Wrong handler URL', self::ERROR_WRONG_HANDLER_URL);
		}
	}

	private static function validateActivityProperties($properties)
	{
		if (!is_array($properties))
			throw new RestException('Wrong properties array!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
		foreach ($properties as $key => $property)
		{
			if (!preg_match('#^[a-z][a-z0-9_]*$#i', $key))
				throw new RestException('Wrong property key ('.$key.')!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
			if (empty($property['NAME']))
				throw new RestException('Empty property NAME ('.$key.')!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
		}
	}

	private static function validateActivityDocumentType($documentType)
	{
		try
		{
			\CBPHelper::parseDocumentId($documentType);
		}
		catch (\CBPArgumentNullException $e)
		{
			throw new RestException('Wrong activity DOCUMENT_TYPE!', self::ERROR_ACTIVITY_VALIDATION_FAILURE);
		}
	}

	private static function extractEventToken($token)
	{
		$data = \CBPRestActivity::extractToken($token);
		if (!$data)
			throw new AccessException();
		return $data;
	}

	/**
	 * @param $server
	 * @return array|bool|false|mixed|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function getApp($server)
	{
		if (self::$app == null)
		{
			if (Loader::includeModule('bitrix24'))
			{
				$result = \CBitrix24App::getList(array(), array('APP_ID' => $server->getAppId()));
				self::$app = $result->fetch();
			}
			elseif (Loader::includeModule('oauth'))
			{
				$result = ClientTable::getList(array('filter' => array('=CLIENT_ID' => $server->getAppId())));
				self::$app = $result->fetch();
				if (is_array(self::$app) && is_array(self::$app['SCOPE']))
				{
					self::$app['SCOPE'] = implode(',', self::$app['SCOPE']);
				}
			}
		}

		return self::$app;
	}
}