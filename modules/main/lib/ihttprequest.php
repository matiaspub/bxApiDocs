<?php
namespace Bitrix\Main;

interface IHttpRequest
{
	public function getQueryString($name);
	static public function getPostData($name);
	public function getFile($name);
	static public function getCookie($name);
	public function getRequestUri();
	static public function getRequestMethod();
	public function getUserAgent();
	static public function getAcceptedLanguages();
	public function getHttpHost();
	static public function isHttps();
}