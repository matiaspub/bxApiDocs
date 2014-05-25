<?php
namespace Bitrix\Main\System;

interface IApplicationStrategy
{
	public function preInitialize();
	static public function createDatabaseConnection();
	static public function initializeContext();
	public function initializeBasicKernel();
	static public function initializeExtendedKernel();
	static public function authenticateUser();
	public function authorizeUser();
	static public function postInitialize();
	public function initializeDispatcher();
	static public function runInitScripts();
}
