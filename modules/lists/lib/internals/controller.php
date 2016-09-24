<?php

namespace Bitrix\Lists\Internals;

use Bitrix\Lists\Internals\Error\Error;
use Bitrix\Lists\Internals\Error\ErrorCollection;
use Bitrix\Lists\Internals\Error\IErrorable;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\PostDecodeFilter;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

abstract class Controller implements IErrorable
{
	const ERROR_REQUIRED_PARAMETER = 'LISTS_CONTROLLER_22001';
	const ERROR_UNKNOWN_ACTION     = 'LISTS_CONTROLLER_22002';

	const STATUS_SUCCESS      = 'success';
	const STATUS_DENIED       = 'denied';
	const STATUS_ERROR        = 'error';
	const STATUS_NEED_AUTH    = 'need_auth';
	const STATUS_INVALID_SIGN = 'invalid_sign';

	/** @var string */
	protected $action;
	/** @var  array */
	protected $actionDescription;
	/** @var  string */
	protected $realActionName;
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var  \Bitrix\Main\HttpRequest */
	protected $request;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
		$this->request = Context::getCurrent()->getRequest();
	}

	protected function end()
	{
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		die;
	}

	public function exec()
	{
		try
		{
			if($this->request->isPost())
			{
				\CUtil::jSPostUnescape();
				$this->request->addFilter(new PostDecodeFilter);
			}

			$this->resolveAction();			
			$this->checkAction();

			$this->checkRequiredModules();

			if(!$this->prepareParams())
			{
				$this->sendJsonErrorResponse();
			}

			//todo create Event!
			if($this->processBeforeAction($this->getAction()) !== false)
			{
				$this->runAction();
			}
		}
		catch(\Exception $e)
		{
			$this->runProcessingException($e);
		}
	}

	/**
	 * @return array|bool|\CAllUser|\CUser
	 */
	protected function getUser()
	{
		global $USER;
		return $USER;
	}

	protected function sendJsonResponse($response, $params = null)
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			// define('PUBLIC_AJAX_MODE', true);
		}

		global $APPLICATION;
		$APPLICATION->restartBuffer();

		if(!empty($params['http_status']) && $params['http_status'] == 403)
		{
			header('HTTP/1.0 403 Forbidden', true, 403);
		}
		if(!empty($params['http_status']) && $params['http_status'] == 500)
		{
			header('HTTP/1.0 500 Internal Server Error', true, 500);
		}

		header('Content-Type:application/json; charset=UTF-8');
		echo Json::encode($response);

		$this->end();
	}

	protected function sendJsonErrorResponse()
	{
		$errors = array();
		foreach($this->getErrors() as $error)
		{
			/** @var Error $error */
			$errors[] = array(
				'message' => $error->getMessage(),
				'code' => $error->getCode(),
			);
		}
		unset($error);
		$this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
			'errors' => $errors,
		));
	}

	protected function sendJsonAccessDeniedResponse($message = '')
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_DENIED,
			'message' => $message,
		));
	}

	protected function sendJsonInvalidSignResponse($message = '')
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_INVALID_SIGN,
			'message' => $message,
		));
	}

	protected function sendJsonSuccessResponse(array $response = array())
	{
		$response['status'] = self::STATUS_SUCCESS;
		$this->sendJsonResponse($response);
	}

	protected function sendResponse($response)
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();

		echo $response;

		$this->end();
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function resolveAction()
	{
		$listOfActions = $this->normalizeListOfAction($this->listOfActions());
		$action = strtolower($this->action);

		if(!isset($listOfActions[$action]))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_CONTROLLER_ERROR_UNKNOWN_ACTION', array('#ACTION#' => $action)), self::ERROR_UNKNOWN_ACTION)));
			return $this;
		}

		$this->realActionName = $action;
		$description = $listOfActions[$this->realActionName];
		$this->setAction($description['name'], $description);

		return $this;
	}

	//todo refactor BaseComponent + Controller normalizeListOfAction, resolveAction.
	//you can use composition in BaseComponent
	protected function normalizeListOfAction(array $listOfActions)
	{
		$normalized = array();
		foreach($listOfActions as $action => $description)
		{
			if(!is_string($action))
			{
				$normalized[$description] = $this->normalizeActionDescription($description, $description);
			}
			else
			{
				$normalized[$action] = $this->normalizeActionDescription($action, $description);
			}
		}
		unset($action, $description);

		return array_change_key_case($normalized, CASE_LOWER);
	}

	protected function normalizeActionDescription($action, $description)
	{
		if(!is_array($description))
		{
			$description = array(
				'method' => array('GET'),
				'name' => $description,
				'check_csrf_token' => false,
				'redirect_on_auth' => true,
				'close_session' => false,
			);
		}
		if(empty($description['name']))
		{
			$description['name'] = $action;
		}
		if(!isset($description['redirect_on_auth']))
		{
			$description['redirect_on_auth'] = false;
		}
		if(!isset($description['close_session']))
		{
			$description['close_session'] = false;
		}

		return $description;
	}

	protected function checkAction()
	{
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$description = $this->getActionDescription();

		if(!$this->getUser() || !$this->getUser()->getId())
		{
			if($description['redirect_on_auth'])
			{
				LocalRedirect(SITE_DIR . 'auth/?backurl=' . urlencode(Application::getInstance()->getContext()->getRequest()->getRequestUri()));
			}
			else
			{
				$this->runProcessingIfUserNotAuthorized();
			}
		}

		//if does not exist check_csrf_token we have to check csrf for only POST method.
		if($description['check_csrf_token'] === true || ($this->request->isPost() && !isset($description['check_csrf_token'])))
		{
			//in BDisk we have token_sid
			if(!check_bitrix_sessid() && !check_bitrix_sessid('token_sid'))
			{
				$this->runProcessingIfInvalidCsrfToken();
			}
		}

		if(!in_array($this->request->getRequestMethod(), $description['method']))
		{
			$this->sendJsonAccessDeniedResponse('Wrong method for current action');
		}
	}

	protected function listOfActions()
	{
		return array();
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
	public function getActionDescription()
	{
		return $this->actionDescription;
	}

	/**
	 * @param string $action
	 * @param array  $description
	 * @return $this
	 */
	public function setAction($action, array $description)
	{
		$this->action = $action;
		$this->actionDescription = $description;

		return $this;
	}

	/**
	 * @param string $action
	 * @return $this
	 */
	public function setActionName($action)
	{
		$this->action = $action;
		return $this;
	}

	protected function checkRequiredModules()
	{}

	protected function prepareParams()
	{
		return true;
	}

	/**
	 * Common operations before run action.
	 * @param string $actionName Action name which will be run.
	 * @return bool If method will return false, then action will not execute.
	 */
	protected function processBeforeAction($actionName)
	{
		return true;
	}

	protected function runAction()
	{
		$description = $this->getActionDescription();
		if($description['close_session'] === true)
		{
			//todo be careful by using this features.
			session_write_close();
		}
		$actionMethod = 'processAction' . $this->getAction();

		return $this->$actionMethod();
	}

	protected function runProcessingException(\Exception $e)
	{
//		throw $e;
		$this->errorCollection->add(array(new Error($e->getMessage())));
		$this->sendJsonErrorResponse();
	}

	protected function runProcessingIfUserNotAuthorized()
	{
		$this->sendJsonAccessDeniedResponse();
	}

	protected function runProcessingIfInvalidCsrfToken()
	{
		$this->sendJsonAccessDeniedResponse('Wrong csrf token');
	}

	/**
	 * @return Application|\Bitrix\Main\HttpApplication|\CAllMain|\CMain
	 */
	protected function getApplication()
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * @param array $inputParams
	 * @param array $required
	 * @return bool
	 */
	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && strlen($inputParams[$item]))))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_CONTROLLER_ERROR_REQUIRED_PARAMETER', array('#PARAM#' => $item)), self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}

	protected function checkRequiredPostParams(array $required)
	{
		$params = array();
		foreach($required as $item)
		{
			$params[$item] = $this->request->getPost($item);
		}
		unset($item);

		return $this->checkRequiredInputParams($params, $required);
	}

	protected function checkRequiredGetParams(array $required)
	{
		$params = array();
		foreach($required as $item)
		{
			$params[$item] = $this->request->getQuery($item);
		}
		unset($item);

		return $this->checkRequiredInputParams($params, $required);
	}

	protected function checkRequiredFilesParams(array $required)
	{
		$params = array();
		foreach($required as $item)
		{
			$params[$item] = $this->request->getFile($item);
		}
		unset($item);

		return $this->checkRequiredInputParams($params, $required);
	}

	/**
	 * Returns whether this is an AJAX (XMLHttpRequest) request.
	 * @return boolean
	 */
	protected function isAjaxRequest()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}
}