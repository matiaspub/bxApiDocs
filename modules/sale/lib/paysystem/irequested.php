<?php

namespace Bitrix\Sale\PaySystem;

interface IRequested
{
	/**
	 * @return bool
	 */
	static public function createMovementListRequest();

	/**
	 * @param $requestId
	 * @return array
	 */
	static public function getMovementListStatus($requestId = null);

	/**
	 * @param $requestId
	 * @return mixed
	 */
	static public function getMovementList($requestId = null);
}