<?
namespace Bitrix\Bizproc;

use \Bitrix\Main\Loader;
use \Bitrix\Rest\AppLangTable;
use \Bitrix\Rest\AppTable;
use \Bitrix\Rest\RestException;
use \Bitrix\Rest\AccessException;

Loader::includeModule('rest');

class RestService extends \IRestService
{
	const SCOPE = 'bizproc';
	protected static $app;
	private static $allowedOperations = array('', '!', '<', '<=', '>', '>=');//, '><', '!><', '?', '=', '!=', '%', '!%', ''); May be later?

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
				
				'bizproc.task.list' =>  array(__CLASS__, 'getTaskList'),

				'bizproc.workflow.instances' => array(__CLASS__, 'getWorkflowInstances')
			),
		);
	}

	/**
	 * Deletes application activities.
	 * @param array $fields Fields describes application.
	 * @return void
	 */
	
	/**
	* <p>Обработчик, вызываемый при удалении приложения. Удаляет связанные с приложением действия. Метод статический.</p>
	*
	*
	* @param array $fields  Поля, описывающие приложение.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/restservice/onrestappdelete.php
	* @author Bitrix
	*/
	public static function onRestAppDelete(array $fields)
	{
		$fields = array_change_key_case($fields, CASE_UPPER);
		if (empty($fields['APP_ID']))
			return;

		if (!Loader::includeModule('rest'))
			return;

		$dbRes = AppTable::getById($fields['APP_ID']);
		$app = $dbRes->fetch();

		if(!$app)
			return;

		$iterator = RestActivityTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=APP_ID' => $app['CLIENT_ID'])
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
	
	/**
	* <p>Обработчик, вызываемый при обновлении приложения. Удаляет связанные с приложением действия. Метод статический.</p>
	*
	*
	* @param array $fields  Поля, описывающие приложение.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/restservice/onrestappupdate.php
	* @author Bitrix
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

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getWorkflowInstances($params, $n, $server)
	{
		self::checkAdminPermissions();
		$params = array_change_key_case($params, CASE_UPPER);

		$fields = array(
			'ID' => 'ID',
			'MODIFIED' => 'MODIFIED',
			'OWNED_UNTIL' => 'OWNED_UNTIL',
			'MODULE_ID' => 'STATE.MODULE_ID',
			'ENTITY' => 'STATE.ENTITY',
			'DOCUMENT_ID' => 'STATE.DOCUMENT_ID',
			'STARTED' => 'STATE.STARTED',
			'STARTED_BY' => 'STATE.STARTED_BY',
			'TEMPLATE_ID' => 'STATE.WORKFLOW_TEMPLATE_ID',
		);

		$select = static::getSelect($params['SELECT'], $fields, array('ID', 'MODIFIED', 'OWNED_UNTIL'));
		$filter = static::getFilter($params['FILTER'], $fields, array('MODIFIED', 'OWNED_UNTIL'));
		$order = static::getOrder($params['ORDER'], $fields, array('MODIFIED' => 'DESC'));

		$iterator = WorkflowInstanceTable::getList(array(
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'limit' => static::LIST_LIMIT,
			'offset' => (int) $n
		));

		$result = array();
		while ($row = $iterator->fetch())
		{
			if (isset($row['MODIFIED']))
				$row['MODIFIED'] = \CRestUtil::convertDateTime($row['MODIFIED']);
			if (isset($row['OWNED_UNTIL']))
				$row['OWNED_UNTIL'] = \CRestUtil::convertDateTime($row['OWNED_UNTIL']);
			$result[] = $row;
		}

		return $result;
	}

	/**
	 * @param array $params Input params.
	 * @param int $n Offset.
	 * @param \CRestServer $server Rest server instance.
	 * @return array
	 * @throws AccessException
	 */
	public static function getTaskList($params, $n, $server)
	{
		global $USER;
		self::checkAdminPermissions();
		$params = array_change_key_case($params, CASE_UPPER);

		$fields = array(
			'ID' => 'ID',
			'WORKFLOW_ID' => 'WORKFLOW_ID',
			'DOCUMENT_NAME' => 'DOCUMENT_NAME',
			'DESCRIPTION' => 'DESCRIPTION',
			'NAME' => 'NAME',
			'MODIFIED' => 'MODIFIED',
			'WORKFLOW_STARTED' => 'WORKFLOW_STARTED',
			'WORKFLOW_STARTED_BY' => 'WORKFLOW_STARTED_BY',
			'OVERDUE_DATE' => 'OVERDUE_DATE',
			'WORKFLOW_TEMPLATE_ID' => 'WORKFLOW_TEMPLATE_ID',
			'WORKFLOW_TEMPLATE_NAME' => 'WORKFLOW_TEMPLATE_NAME',
			'WORKFLOW_STATE' => 'WORKFLOW_STATE',
			'STATUS' => 'STATUS',
			'USER_ID' => 'USER_ID',
			'USER_STATUS' => 'USER_STATUS',
			'MODULE_ID' => 'MODULE_ID',
			'ENTITY' => 'ENTITY',
			'DOCUMENT_ID' => 'DOCUMENT_ID',
		);

		$select = static::getSelect($params['SELECT'], $fields, array('ID', 'WORKFLOW_ID', 'DOCUMENT_NAME', 'NAME'));
		$select = array_merge(array('MODULE', 'ENTITY', 'DOCUMENT_ID'), $select);
		$filter = static::getFilter($params['FILTER'], $fields, array('MODIFIED', 'WORKFLOW_STARTED', 'OVERDUE_DATE'));
		$order = static::getOrder($params['ORDER'], $fields, array('ID' => 'DESC'));

		$currentUserId = (int) $USER->getId();
		$targetUserId = isset($filter['USER_ID'])? (int)$filter['USER_ID'] : 0;

		if ($targetUserId !== $currentUserId && !\CBPHelper::checkUserSubordination($currentUserId, $targetUserId))
		{
			self::checkAdminPermissions();
		}

		$iterator = \CBPTaskService::getList(
			$order,
			$filter,
			false,
			static::getNavData($n),
			$select
		);

		$result = array();
		while ($row = $iterator->fetch())
		{
			if (isset($row['MODIFIED']))
				$row['MODIFIED'] = \CRestUtil::convertDateTime($row['MODIFIED']);
			if (isset($row['WORKFLOW_STARTED']))
				$row['WORKFLOW_STARTED'] = \CRestUtil::convertDateTime($row['WORKFLOW_STARTED']);
			if (isset($row['OVERDUE_DATE']))
				$row['OVERDUE_DATE'] = \CRestUtil::convertDateTime($row['OVERDUE_DATE']);
			$row['DOCUMENT_URL'] = \CBPDocument::getDocumentAdminPage(array(
				$row['MODULE_ID'], $row['ENTITY'], $row['DOCUMENT_ID']
			));

			$result[] = $row;
		}

		return $result;
	}

	private static function getSelect($rules, $fields, $default = array())
	{
		$select = array();
		if (!empty($rules) && is_array($rules))
		{
			foreach ($rules as $field)
			{
				$field = strtoupper($field);
				if (isset($fields[$field]) && !in_array($field, $select))
					$select[$field] = $fields[$field];
			}
		}

		return $select ? $select : $default;
	}

	private static function getOrder($rules, $fields, array $default = array())
	{
		$order = array();
		if (!empty($rules) && is_array($rules))
		{
			foreach ($rules as $field => $ordering)
			{
				$field = strtoupper($field);
				$ordering = strtoupper($ordering);
				if (isset($fields[$field]))
					$order[$fields[$field]] = $ordering == 'DESC' ? 'DESC' : 'ASC';
			}
		}

		return $order ? $order : $default;
	}

	private static function getFilter($rules, $fields, array $datetimeFieldsList = array())
	{
		$filter = array();
		if (!empty($rules) && is_array($rules))
		{
			foreach ($rules as $key => $value)
			{
				if (preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
				{
					$operation = $matches[1];
					$field = $matches[2];

					if (in_array($operation, static::$allowedOperations, true) && isset($fields[$field]))
					{
						if (in_array($field, $datetimeFieldsList))
							$value = \CRestUtil::unConvertDateTime($value);

						$filter[$operation.$fields[$field]] = $value;
					}
				}
			}
		}

		return $filter;
	}

	private static function checkAdminPermissions()
	{
		global $USER;
		if (!isset($USER)
			|| !is_object($USER)
			|| (!$USER->isAdmin() && !(Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($USER->getID())))
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
		if (!Loader::includeModule('rest'))
			return array('*' => 'No app');

		$iterator = AppTable::getList(
			array(
				'filter' => array(
					'=CLIENT_ID' => $appId
				),
				'select' => array('ID', 'APP_NAME', 'CODE'),
			)
		);
		$app = $iterator->fetch();
		$result = array('*' => $app['APP_NAME'] ? $app['APP_NAME'] : $app['CODE']);

		$iterator = AppLangTable::getList(array(
			'filter' => array(
				'=APP_ID' => $app['ID'],
			),
			'select' => array('LANGUAGE_ID', 'MENU_NAME')
		));
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
			$runtime = \CBPRuntime::getRuntime();
			$runtime->startRuntime();
			/** @var \CBPDocumentService $documentService */
			$documentService = $runtime->getService('DocumentService');
			$documentService->getDocumentFieldTypes($documentType);
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
	 * @param \CRestServer $server
	 * @return array|bool|false|mixed|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private static function getApp($server)
	{
		if(self::$app == null)
		{
			if (Loader::includeModule('rest'))
			{
				$result = AppTable::getList(
					array(
						'filter' => array(
							'=CLIENT_ID' => $server->getAppId()
						)
					)
				);
				self::$app = $result->fetch();
			}
		}

		return self::$app;
	}
}