<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

use Bitrix\Main\Text\BinaryString;
use Bitrix\Main\IO;
use Bitrix\Main\Config\Configuration;

class HttpClient
{
	const HTTP_1_0 = "1.0";
	const HTTP_1_1 = "1.1";
	const HTTP_GET = "GET";
	const HTTP_POST = "POST";
	const HTTP_PUT = "PUT";
	const HTTP_HEAD = "HEAD";
	const HTTP_PATCH = "PATCH";

	const BUF_READ_LEN = 16384;
	const BUF_POST_LEN = 131072;

	protected $proxyHost;
	protected $proxyPort;
	protected $proxyUser;
	protected $proxyPassword;

	protected $resource;
	protected $socketTimeout = 30;
	protected $streamTimeout = 60;
	protected $error = array();

	/** @var HttpHeaders */
	protected $requestHeaders;
	/** @var HttpCookies  */
	protected $requestCookies;
	protected $waitResponse = true;
	protected $redirect = true;
	protected $redirectMax = 5;
	protected $redirectCount = 0;
	protected $compress = false;
	protected $version = self::HTTP_1_0;
	protected $requestCharset = '';
	protected $sslVerify = true;

	protected $status = 0;
	/** @var HttpHeaders */
	protected $responseHeaders;
	/** @var HttpCookies  */
	protected $responseCookies;
	protected $result = '';
	protected $outputStream;

	protected $effectiveUrl;

	/**
	 * @param array $options Optional array with options:
	 *		"redirect" bool Follow redirects (default true)
	 *		"redirectMax" int Maximum number of redirects (default 5)
	 *		"waitResponse" bool Wait for response or disconnect just after request (default true)
	 *		"socketTimeout" int Connection timeout in seconds (default 30)
	 *		"streamTimeout" int Stream reading timeout in seconds (default 60)
	 *		"version" string HTTP version (HttpClient::HTTP_1_0, HttpClient::HTTP_1_1) (default "1.0")
	 *		"proxyHost" string Proxy host name/address
	 *		"proxyPort" int Proxy port number
	 *		"proxyUser" string Proxy username
	 *		"proxyPassword" string Proxy password
	 *		"compress" bool Accept gzip encoding (default false)
	 *		"charset" string Charset for body in POST and PUT
	 *		"disableSslVerification" bool Pass true to disable ssl check.
	 * 	All the options can be set separately with setters.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия при создании объекта.</p>
	*
	*
	* @param array $options = null Массив параметров: <ul> <li>bool <b>redirect</b> Следовать переадресации (по
	* умолчанию <i>true</i> - редирект).</li> <li>int <b>redirectMax</b> Максимальное
	* количество редиректов (по умолчанию  5).</li>  <li>bool <b>waitResponse</b> 
	* Дождаться ответа или отключиться сразу после запроса (по
	* умолчанию <i>true</i> - ожидание ответа)</li>  <li>int <b>socketTimeout</b> Таймаут
	* соединения в секундах (по умолчанию  30).</li> <li>int <b>streamTimeout</b> 
	* Таймаут потока в секундах (по умолчанию  60).</li>  <li>string <b>version</b>
	* Версия HTTP (HttpClient::HTTP_1_0, HttpClient::HTTP_1_1) (по умолчанию  "1.0").</li>  <li>string
	* <b>proxyHost</b> Имя\адрес прокси сервера.</li> <li>int <b>proxyPort</b> Порт прокси
	* сервера.</li>  <li>string <b>proxyUser</b> Имя пользователя прокси сервера.</li> 
	* <li>string <b>proxyPassword</b> Пароль прокси.</li>  <li>bool <b>compress</b> Использование
	* сжатия zip (по умолчанию <i>false</i>). Если <i>true</i>, будет послан
	* <code>Accept-Encoding: gzip</code>.</li> <li>string <b>charset</b> Кодировка для содержимого POST
	* и PUT запросов. (Используется в поле заголовка запроса Content-Type.)</li> 
	* <li>bool <b>disableSslVerification</b> Если установлено <i>true</i>, то верификация
	* ssl-сертификатов производиться не будет.</li>  </ul> Все эти опции не
	* обязательно указывать при создании экземпляра класса, их можно
	* установить в дальнейшем.
	*
	* @return public 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Создать экземпляр класса:$http=new \Bitrix\Main\Web\HttpClient(array $options = null);)Выполнение <a href="/api_d7/bitrix/main/web/httpclient/get.php">GET</a> запроса. В параметр необходимо передать абсолютный путь. Возвращается строка ответа или <i>false</i>, если произошла ошибка. Обратите внимание, что пустая строка не является ошибкой.$http-&gt;get($url)Выполнение <a href="/api_d7/bitrix/main/web/httpclient/post.php">POST</a> запроса. Первый параметр - абсолютный путь. Второй параметр может быть массивом, строкой, объектом. Аналогично предыдущему методу возвращает строку ответа или <i>false</i>, если произошла ошибка.$http-&gt;post($url, $postData = null)Установка <a href="/api_d7/bitrix/main/web/httpclient/setheader.php">заголовока http</a> в запросе. Первый параметр устанавливает имя поля заголовка, второй параметр значение этого поля. (Обратите внимание, что в обоих случаях передается строка. То есть если нужно сразу установить несколько заголовков, то метод нужно будет вызвать несколько раз.) Третий параметр отвечает за то, будет ли перезаписываться заголовок, если уже установлен параметр с таким же именем или нет.$http-&gt;setHeader($name, $value, $replace = true)Установка <a href="%5CBitrix%5CMain%5CWeb%5CHttpClient::setCookies" target="_blank">cookies</a> для запроса. Единственный параметр принимает массив, где ключ - это имя cookies, а значение - значение cookies.$http-&gt;setCookies(array $cookies)Установка поля заголовка запроса базовой http <a href="/api_d7/bitrix/main/web/httpclient/setauthorization.php">авторизации</a>. В параметрах передается имя пользователя и пароль.$http-&gt;setAuthorization($user, $pass)Установка <a href="/api_d7/bitrix/main/web/httpclient/setredirect.php">опции перенаправления</a>. Первый параметр устанавливает делать ли перенаправления или нет, а второй задаёт максимальное количество перенаправлений.$http-&gt;setRedirect($value, $max = null)Установка <a href="/api_d7/bitrix/main/web/httpclient/waitresponse.php">параметра</a>, который задаёт ожидание отклика от сервера или закрытие соединения сразу после запроса. По умолчанию - ожидание.$http-&gt;waitResponse($value)Установка максимального <a href="/api_d7/bitrix/main/web/httpclient/settimeout.php">времени ожидания</a> ответа в секундах.$http-&gt;setTimeout($value)Установка <a href="/api_d7/bitrix/main/web/httpclient/setversion.php">версии HTTP</a> протокола. По умолчанию 1.0. Можно установить 1.1$http-&gt;setVersion($value)Указание, использовать ли <a href="/api_d7/bitrix/main/web/httpclient/setcompress.php">сжатие</a> или нет. Обратите внимание, что сжатый ответ в любом случае обрабатывается, если верно переданы заголовки.$http-&gt;setCompress($value)Установка <a href="/api_d7/bitrix/main/web/httpclient/setcharset.php">кодировки</a> для тела объектов запросов POST и PUT$http-&gt;setCharset($value)Установка параметров для использования <a href="/api_d7/bitrix/main/web/httpclient/setproxy.php">прокси сервера</a>. Указывается первым параметром хост или адрес, вторым - порт, третий параметр - это имя пользователя и четвертый - пароль. Все параметры кроме имени хоста не обязательны.$http-&gt;setProxy($proxyHost, $proxyPort = null, $proxyUser = null, $proxyPassword = null)Указание, что результатом будет <a href="/api_d7/bitrix/main/web/httpclient/setoutputstream.php">поток</a>. Параметром передается реcурс на файл или на поток.$http-&gt;setOutputStream($handler)
	* <a href="/api_d7/bitrix/main/web/httpclient/download.php">Загрузка</a> и сохранение файла. Первый параметр - это адрес откуда нужно скачать файл. Второй параметр - это абсолютный путь для сохранения файла.$http-&gt;download($url, $filePath)
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/__construct.php
	* @author Bitrix
	*/
	public function __construct(array $options = null)
	{
		$this->requestHeaders = new HttpHeaders();
		$this->responseHeaders = new HttpHeaders();
		$this->requestCookies = new HttpCookies();
		$this->responseCookies = new HttpCookies();

		if($options === null)
		{
			$options = array();
		}

		$defaultOptions = Configuration::getValue("http_client_options");
		if($defaultOptions !== null)
		{
			$options += $defaultOptions;
		}

		if(!empty($options))
		{
			if(isset($options["redirect"]))
			{
				$this->setRedirect($options["redirect"], $options["redirectMax"]);
			}
			if(isset($options["waitResponse"]))
			{
				$this->waitResponse($options["waitResponse"]);
			}
			if(isset($options["socketTimeout"]))
			{
				$this->setTimeout($options["socketTimeout"]);
			}
			if(isset($options["streamTimeout"]))
			{
				$this->setStreamTimeout($options["streamTimeout"]);
			}
			if(isset($options["version"]))
			{
				$this->setVersion($options["version"]);
			}
			if(isset($options["proxyHost"]))
			{
				$this->setProxy($options["proxyHost"], $options["proxyPort"], $options["proxyUser"], $options["proxyPassword"]);
			}
			if(isset($options["compress"]))
			{
				$this->setCompress($options["compress"]);
			}
			if(isset($options["charset"]))
			{
				$this->setCharset($options["charset"]);
			}
			if(isset($options["disableSslVerification"]) && $options["disableSslVerification"] === true)
			{
				$this->disableSslVerification();
			}
		}
	}

	/**
	 * Closes the connection on the object destruction.
	 */
	
	/**
	* <p>Нестатический метод вызывается при уничтожении последней ссылки на экземпляр объекта, но не уничтожает объект класса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/__destruct.php
	* @author Bitrix
	*/
	public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Performs GET request.
	 *
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query".
	 * @return string|bool Response entity string or false on error. Note, it's empty string if outputStream is set.
	 */
	
	/**
	* <p>Нестатический метод выполняет GET запрос.</p>
	*
	*
	* @param string $url  абсолютный URI, например: <code>&lt;a href="http://user:pass"&gt;http://user:pass&lt;/a&gt; @
	* host:port/path/?query</code>
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/get.php
	* @author Bitrix
	*/
	public function get($url)
	{
		if($this->query(self::HTTP_GET, $url))
		{
			return $this->getResult();
		}
		return false;
	}

	/**
	 * Performs HEAD request.
	 *
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query"
	 * @return HttpHeaders|bool Response headers or false on error.
	 */
	
	/**
	* <p>Нестатический метод выполняет HEAD запрос.</p>
	*
	*
	* @param string $url  абсолютный URI, например: <code>&lt;a href="http://user:pass"&gt;http://user:pass&lt;/a&gt; @
	* host:port/path/?query</code>
	*
	* @return \Bitrix\Main\Web\HttpHeaders|boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/head.php
	* @author Bitrix
	*/
	public function head($url)
	{
		if($this->query(self::HTTP_HEAD, $url))
		{
			return $this->getHeaders();
		}
		return false;
	}

	/**
	 * Performs POST request.
	 *
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query".
	 * @param array|string|resource $postData Entity of POST/PUT request. If it's resource handler then data will be read directly from the stream.
	 * @return string|bool Response entity string or false on error. Note, it's empty string if outputStream is set.
	 */
	
	/**
	* <p>Нестатический метод выполняет POST запрос.</p>
	*
	*
	* @param string $url  Абсолютный URI, например: <code>&lt;a href="http://user:pass"&gt;http://user:pass&lt;/a&gt; @
	* host:port/path/?query</code>.
	*
	* @param string $array  Сущность POST/PUT запроса. Если это - обработчик ресурсов, то чтение
	* данных осуществляется непосредственно из потока.
	*
	* @param arra $string  
	*
	* @param resource $postData = null 
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/post.php
	* @author Bitrix
	*/
	public function post($url, $postData = null)
	{
		if($this->query(self::HTTP_POST, $url, $postData))
		{
			return $this->getResult();
		}
		return false;
	}

	/**
	 * Perfoms HTTP request.
	 *
	 * @param string $method HTTP method (GET, POST, etc.). Note, it must be in UPPERCASE.
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query".
	 * @param array|string|resource $entityBody Entity body of the request. If it's resource handler then data will be read directly from the stream.
	 * @return bool Query result (true or false). Response entity string can be get via getResult() method. Note, it's empty string if outputStream is set.
	 */
	
	/**
	* <p>Нестатический метод выполняет HTTP запрос.</p>
	*
	*
	* @param string $method  HTTP метод (GET, POST и так далее). <b>Важно</b>: должно быть набрано в
	* верхнем регистре.
	*
	* @param string $url  Абсолютный URI, например: <code>&lt;a href="http://user:pass"&gt;http://user:pass&lt;/a&gt; @
	* host:port/path/?query</code>.
	*
	* @param string $array  Сущность POST/PUT запроса. Если это - обработчик ресурсов, то данные
	* будут читаться непосредственно из потока.
	*
	* @param arra $string  
	*
	* @param resource $postData = null 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/query.php
	* @author Bitrix
	*/
	public function query($method, $url, $entityBody = null)
	{
		$queryMethod = $method;
		$this->effectiveUrl = $url;

		if(is_array($entityBody))
		{
			$entityBody = http_build_query($entityBody, "", "&");
		}

		$this->redirectCount = 0;

		while(true)
		{
			//Only absoluteURI is accepted
			//Location response-header field must be absoluteURI either
			$parsedUrl = new Uri($this->effectiveUrl);
			if($parsedUrl->getHost() == '')
			{
				$this->error["URI"] = "Incorrect URI: ".$this->effectiveUrl;
				return false;
			}

			//just in case of serial queries
			$this->disconnect();

			if($this->connect($parsedUrl) === false)
			{
				return false;
			}

			$this->sendRequest($queryMethod, $parsedUrl, $entityBody);

			if(!$this->waitResponse)
			{
				$this->disconnect();
				return true;
			}

			if(!$this->readHeaders())
			{
				$this->disconnect();
				return false;
			}

			if($this->redirect && ($location = $this->responseHeaders->get("Location")) !== null && $location <> '')
			{
				//we don't need a body on redirect
				$this->disconnect();

				if($this->redirectCount < $this->redirectMax)
				{
					$this->effectiveUrl = $location;
					if($this->status == 302 || $this->status == 303)
					{
						$queryMethod = self::HTTP_GET;
					}
					$this->redirectCount++;
				}
				else
				{
					$this->error["REDIRECT"] = "Maximum number of redirects (".$this->redirectMax.") has been reached at URL ".$url;
					trigger_error($this->error["REDIRECT"], E_USER_WARNING);
					return false;
				}
			}
			else
			{
				//the connection is still active to read the response body
				break;
			}
		}
		return true;
	}

	/**
	 * Sets an HTTP request header field.
	 *
	 * @param string $name Name of the header field.
	 * @param string $value Value of the field.
	 * @param bool $replace Replace existing header field with the same name or add one more.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает поле заголовка HTTP запроса.</p>
	*
	*
	* @param string $name  Имя поля заголовка.
	*
	* @param string $value  Значение поля.
	*
	* @param boolean $replace = true Заменить существующий заголовок с текщим именем или добавить ещё
	* один.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setheader.php
	* @author Bitrix
	*/
	public function setHeader($name, $value, $replace = true)
	{
		if($replace == true || $this->requestHeaders->get($name) === null)
		{
			$this->requestHeaders->set($name, $value);
		}
	}

	/**
	 * Sets an array of cookies for HTTP request.
	 *
	 * @param array $cookies Array of cookie_name => value pairs.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает массив cookies для HTTP запроса.</p>
	*
	*
	* @param array $cookies  Массив пар <code>cookie_name =&gt; value</code>.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setcookies.php
	* @author Bitrix
	*/
	public function setCookies(array $cookies)
	{
		$this->requestCookies->set($cookies);
	}

	/**
	 * Sets Basic Authorization request header field.
	 *
	 * @param string $user Username.
	 * @param string $pass Password.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает поле заголовка запроса аутентификации (Basic Authorization).</p>
	*
	*
	* @param string $user  Логин.
	*
	* @param string $pass  Пароль.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setauthorization.php
	* @author Bitrix
	*/
	public function setAuthorization($user, $pass)
	{
		$this->setHeader("Authorization", "Basic ".base64_encode($user.":".$pass));
	}

	/**
	 * Sets redirect options.
	 *
	 * @param bool $value If true, do redirect (default true).
	 * @param null|int $max Maximum allowed redirect count.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает опции редиректа.</p>
	*
	*
	* @param boolean $value  Если <i>true</i>, выполняется редирект (по умолчанию <i>true</i>).
	*
	* @param boolean $null  Максимально возможное число редиректов.
	*
	* @param integer $max = null 
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setredirect.php
	* @author Bitrix
	*/
	public function setRedirect($value, $max = null)
	{
		$this->redirect = ($value? true : false);
		if($max !== null)
		{
			$this->redirectMax = intval($max);
		}
	}

	/**
	 * Sets response waiting option.
	 *
	 * @param bool $value If true, wait for response. If false, return just after request (default true).
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает опцию ожидания ответа.</p>
	*
	*
	* @param boolean $value  Если <i>true</i>, ожидает ответ. Если <i>false</i>, возвращает сразу после
	* запроса. (По умолчанию <i>true</i>).
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/waitresponse.php
	* @author Bitrix
	*/
	public function waitResponse($value)
	{
		$this->waitResponse = ($value? true : false);
	}

	/**
	 * Sets connection timeout.
	 *
	 * @param int $value Connection timeout in seconds (default 30).
	 * @return void
	 */
	
	/**
	* Нестатический метод устанавливает таймаут соединения.
	*
	*
	* @param integer $value  Таймаут соединения в секундах (по умолчанию 30).
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/settimeout.php
	* @author Bitrix
	*/
	public function setTimeout($value)
	{
		$this->socketTimeout = intval($value);
	}

	/**
	 * Sets socket stream reading timeout.
	 *
	 * @param int $value Stream reading timeout in seconds; "0" means no timeout (default 60).
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает таймаут чтения потока сокетов.</p>
	*
	*
	* @param integer $value  Таймаут чтения потока  в секундах; "0" - нет ограничений (по
	* умолчанию 60).
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setstreamtimeout.php
	* @author Bitrix
	*/
	public function setStreamTimeout($value)
	{
		$this->streamTimeout = intval($value);
	}

	/**
	 * Sets HTTP protocol version. In version 1.1 chunked response is possible.
	 *
	 * @param string $value Version "1.0" or "1.1" (default "1.0").
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает версию HTTP протокола. В версии 1.1 возможен фрагментированный ответ.</p>
	*
	*
	* @param string $value  Версия "1.0" или "1.1" (По умолчанию "1.0").
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setversion.php
	* @author Bitrix
	*/
	public function setVersion($value)
	{
		$this->version = $value;
	}

	/**
	 * Sets compression option.
	 * Consider not to use the "compress" option with the output stream if a content can be large.
	 * Note, that compressed response is processed anyway if Content-Encoding response header field is set
	 *
	 * @param bool $value If true, "Accept-Encoding: gzip" will be sent.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает опции компрессии.</p> <p class="note">Примите во внимание, что не нужно использовать опции сжатия с выходным потоком, если контент может быть большим. Учтите что сжатый ответ обрабатывается в любом случае, если установлено поле заголовка Content-Encoding.</p>
	*
	*
	* @param boolean $value  Если <i>true</i>, будет послан <b>Accept-Encoding: gzip</b>.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setcompress.php
	* @author Bitrix
	*/
	public function setCompress($value)
	{
		$this->compress = ($value? true : false);
	}

	/**
	 * Sets charset for entity-body (used in the Content-Type request header field for POST and PUT)
	 *
	 * @param string $value Charset.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает кодировку для тела объекта (используется в поле заголовка запроса Content-Type для POST и PUT).</p>
	*
	*
	* @param string $value  Кодировка.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setcharset.php
	* @author Bitrix
	*/
	public function setCharset($value)
	{
		$this->requestCharset = $value;
	}

	/**
	 * Disables ssl certificate verification.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод запрещает верификацию ssl сертификата.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/disablesslverification.php
	* @author Bitrix
	*/
	public function disableSslVerification()
	{
		$this->sslVerify = false;
	}

	/**
	 * Sets HTTP proxy for request.
	 *
	 * @param string $proxyHost Proxy host name or address (without "http://").
	 * @param null|int $proxyPort Proxy port number.
	 * @param null|string $proxyUser Proxy username.
	 * @param null|string $proxyPassword Proxy password.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает HTTP прокси для запроса.</p>
	*
	*
	* @param string $proxyHost  Имя или адрес хоста (без <i>http://</i>).
	*
	* @param string $null  Номер порта.
	*
	* @param integer $proxyPort = null Имя пользователя.
	*
	* @param mixed $null  Пароль пользователя.
	*
	* @param string $proxyUser = null 
	*
	* @param mixed $null  
	*
	* @param string $proxyPassword = null 
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setproxy.php
	* @author Bitrix
	*/
	public function setProxy($proxyHost, $proxyPort = null, $proxyUser = null, $proxyPassword = null)
	{
		$this->proxyHost = $proxyHost;
		$this->proxyPort = intval($proxyPort);
		if($this->proxyPort <= 0)
		{
			$this->proxyPort = 80;
		}
		$this->proxyUser = $proxyUser;
		$this->proxyPassword = $proxyPassword;
	}

	/**
	 * Sets the response output to the stream instead of the string result. Useful for large responses.
	 * Note, the stream must be readable/writable to support a compressed response.
	 * Note, in this mode the result string is empty.
	 *
	 * @param resource $handler File or stream handler.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает вывод ответа в поток вместо строкового результата. Используется для больших ответов.</p> <p class="note"><b>Внимание!</b> Поток должен иметь возможность записи/чтения, чтобы поддерживать сжатый ответ. В этом режиме строчный ответ - пустой.</p>
	*
	*
	* @param resource $handler  Файл либо обработчик потока.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setoutputstream.php
	* @author Bitrix
	*/
	public function setOutputStream($handler)
	{
		$this->outputStream = $handler;
	}

	/**
	 * Downloads and saves a file.
	 *
	 * @param string $url URI to download.
	 * @param string $filePath Absolute file path.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод скачивает и сохраняет файлы.</p>
	*
	*
	* @param string $url  URI для скачивания.
	*
	* @param string $filePath  Абсолютный путь.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/download.php
	* @author Bitrix
	*/
	public function download($url, $filePath)
	{
		$dir = IO\Path::getDirectory($filePath);
		IO\Directory::createDirectory($dir);

		$file = new IO\File($filePath);
		$handler = $file->open("w+");
		if($handler !== false)
		{
			$this->setOutputStream($handler);
			$res = $this->query(self::HTTP_GET, $url);
			if($res)
			{
				$res = $this->readBody();
			}
			$this->disconnect();

			fclose($handler);
			return $res;
		}

		return false;
	}

	/**
	 * Returns URL of the last redirect if request was redirected, or initial URL if request was not redirected.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает URL последнего редиректа, если запрос был перенаправлен, или первоначалный URL если перенаправления не было.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/geteffectiveurl.php
	* @author Bitrix
	*/
	public function getEffectiveUrl()
	{
		return $this->effectiveUrl;
	}

	protected function connect(Uri $url)
	{
		if($this->proxyHost <> '')
		{
			$proto = "";
			$host = $this->proxyHost;
			$port = $this->proxyPort;
		}
		else
		{
			$proto = ($url->getScheme() == "https"? "ssl://" : "");
			$host = $url->getHost();
			$host = \CBXPunycode::ToASCII($host, $encodingErrors);
			if(is_array($encodingErrors) && count($encodingErrors) > 0)
			{
				$this->error["URI"] = "Error converting hostname to punycode: ".implode("\n", $encodingErrors);
				return false;
			}
			$url->setHost($host);

			$port = $url->getPort();
		}

		$context = $this->createContext();
		if ($context)
		{
			$res = stream_socket_client($proto.$host.":".$port, $errno, $errstr, $this->socketTimeout, STREAM_CLIENT_CONNECT, $context);
		}
		else
		{
			$res = stream_socket_client($proto.$host.":".$port, $errno, $errstr, $this->socketTimeout);
		}

		if(is_resource($res))
		{
			$this->resource = $res;

			if($this->streamTimeout > 0)
			{
				stream_set_timeout($this->resource, $this->streamTimeout);
			}

			return true;
		}

		if(intval($errno) > 0)
		{
			$this->error["CONNECTION"] = "[".$errno."] ".$errstr;
		}
		else
		{
			$this->error["SOCKET"] = "Socket connection error.";
		}

		return false;
	}

	protected function createContext()
	{
		$contextOptions = array();
		if ($this->sslVerify === false)
		{
			$contextOptions["ssl"]["verify_peer_name"] = false;
			$contextOptions["ssl"]["verify_peer"] = false;
			$contextOptions["ssl"]["allow_self_signed"] = true;
		}
		$context = stream_context_create($contextOptions);
		return $context;
	}

	protected function disconnect()
	{
		if($this->resource)
		{
			fclose($this->resource);
			$this->resource = null;
		}
	}

	protected function send($data)
	{
		return fwrite($this->resource, $data);
	}

	protected function receive($bufLength = null)
	{
		if($bufLength === null)
		{
			$bufLength = self::BUF_READ_LEN;
		}

		$buf = stream_get_contents($this->resource, $bufLength);
		if($buf !== false)
		{
			if(is_resource($this->outputStream))
			{
				//we can write response directly to stream (file, etc.) to minimize memory usage
				fwrite($this->outputStream, $buf);
				fflush($this->outputStream);
			}
			else
			{
				$this->result .= $buf;
			}
		}

		return $buf;
	}

	protected function sendRequest($method, Uri $url, $entityBody = null)
	{
		$this->status = 0;
		$this->result = '';
		$this->responseHeaders->clear();
		$this->responseCookies->clear();

		if($this->proxyHost <> '')
		{
			$path = $url->getLocator();
			if($this->proxyUser <> '')
			{
				$this->setHeader("Proxy-Authorization", "Basic ".base64_encode($this->proxyUser.":".$this->proxyPassword));
			}
		}
		else
		{
			$path = $url->getPathQuery();
		}

		$request = $method." ".$path." HTTP/".$this->version."\r\n";

		$this->setHeader("Host", $url->getHost());
		$this->setHeader("Connection", "close", false);
		$this->setHeader("Accept", "*/*", false);
		$this->setHeader("Accept-Language", "en", false);

		if(($user = $url->getUser()) <> '')
		{
			$this->setAuthorization($user, $url->getPass());
		}

		$cookies = $this->requestCookies->toString();
		if($cookies <> '')
		{
			$this->setHeader("Cookie", $cookies);
		}

		if($this->compress)
		{
			$this->setHeader("Accept-Encoding", "gzip");
		}

		if(!is_resource($entityBody))
		{
			if($method == self::HTTP_POST)
			{
				//special processing for POST requests
				if($this->requestHeaders->get("Content-Type") === null)
				{
					$contentType = "application/x-www-form-urlencoded";
					if($this->requestCharset <> '')
					{
						$contentType .= "; charset=".$this->requestCharset;
					}
					$this->setHeader("Content-Type", $contentType);
				}
			}
			if($entityBody <> '' || $method == self::HTTP_POST)
			{
				//HTTP/1.0 requires Content-Length for POST
				if($this->requestHeaders->get("Content-Length") === null)
				{
					$this->setHeader("Content-Length", BinaryString::getLength($entityBody));
				}
			}
		}

		$request .= $this->requestHeaders->toString();
		$request .= "\r\n";

		$this->send($request);

		if(is_resource($entityBody))
		{
			//PUT data can be a file resource
			while(!feof($entityBody))
			{
				$this->send(fread($entityBody, self::BUF_POST_LEN));
			}
		}
		elseif($entityBody <> '')
		{
			$this->send($entityBody);
		}
	}

	protected function readHeaders()
	{
		$headers = "";
		while(!feof($this->resource))
		{
			$line = fgets($this->resource, self::BUF_READ_LEN);
			if($line == "\r\n")
			{
				break;
			}
			if($this->streamTimeout > 0)
			{
				$info = stream_get_meta_data($this->resource);
				if($info['timed_out'])
				{
					$this->error['STREAM_TIMEOUT'] = "Stream reading timeout of ".$this->streamTimeout." second(s) has been reached";
					return false;
				}
			}
			if($line === false)
			{
				$this->error['STREAM_READING'] = "Stream reading error";
				return false;
			}
			$headers .= $line;
		}

		$this->parseHeaders($headers);

		return true;
	}

	protected function readBody()
	{
		if($this->responseHeaders->get("Transfer-Encoding") == "chunked")
		{
			while(!feof($this->resource))
			{
				/*
				chunk = chunk-size [ chunk-extension ] CRLF
						chunk-data CRLF
				chunk-size = 1*HEX
				chunk-extension = *( ";" chunk-ext-name [ "=" chunk-ext-val ] )
				*/
				$line = fgets($this->resource, self::BUF_READ_LEN);
				if($line == "\r\n")
				{
					continue;
				}
				if(($pos = strpos($line, ";")) !== false)
				{
					$line = substr($line, 0, $pos);
				}

				$length = hexdec($line);
				while($length > 0)
				{
					$buf = $this->receive($length);
					if($this->streamTimeout > 0)
					{
						$info = stream_get_meta_data($this->resource);
						if($info['timed_out'])
						{
							$this->error['STREAM_TIMEOUT'] = "Stream reading timeout of ".$this->streamTimeout." second(s) has been reached";
							return false;
						}
					}
					if($buf === false)
					{
						$this->error['STREAM_READING'] = "Stream reading error";
						return false;
					}
					$length -= BinaryString::getLength($buf);
				}
			}
		}
		else
		{
			while(!feof($this->resource))
			{
				$buf = $this->receive();
				if($this->streamTimeout > 0)
				{
					$info = stream_get_meta_data($this->resource);
					if($info['timed_out'])
					{
						$this->error['STREAM_TIMEOUT'] = "Stream reading timeout of ".$this->streamTimeout." second(s) has been reached";
						return false;
					}
				}
				if($buf === false)
				{
					$this->error['STREAM_READING'] = "Stream reading error";
					return false;
				}
			}
		}

		if($this->responseHeaders->get("Content-Encoding") == "gzip")
		{
			$this->decompress();
		}

		return true;
	}

	protected function decompress()
	{
		if(is_resource($this->outputStream))
		{
			$compressed = stream_get_contents($this->outputStream, -1, 10);
			$compressed = BinaryString::getSubstring($compressed, 0, -8);
			if($compressed <> '')
			{
				$uncompressed = gzinflate($compressed);

				rewind($this->outputStream);
				$len = fwrite($this->outputStream, $uncompressed);
				ftruncate($this->outputStream, $len);
			}
		}
		else
		{
			$compressed = BinaryString::getSubstring($this->result, 10, -8);
			if($compressed <> '')
			{
				$this->result = gzinflate($compressed);
			}
		}
	}

	protected function parseHeaders($headers)
	{
		foreach (explode("\n", $headers) as $k => $header)
		{
			if($k == 0)
			{
				if(preg_match('#HTTP\S+ (\d+)#', $header, $find))
				{
					$this->status = intval($find[1]);
				}
			}
			elseif(strpos($header, ':') !== false)
			{
				list($headerName, $headerValue) = explode(':', $header, 2);
				if(strtolower($headerName) == 'set-cookie')
				{
					$this->responseCookies->addFromString($headerValue);
				}
				$this->responseHeaders->add($headerName, trim($headerValue));
			}
		}
	}

	/**
	 * Returns parsed HTTP response headers
	 *
	 * @return HttpHeaders
	 */
	
	/**
	* <p>Нестатический метод возвращает отпарсенные заголовки HTTP ответов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Web\HttpHeaders 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/getheaders.php
	* @author Bitrix
	*/
	public function getHeaders()
	{
		return $this->responseHeaders;
	}

	/**
	 * Returns parsed HTTP response cookies
	 *
	 * @return HttpCookies
	 */
	
	/**
	* <p>Нестатический метод возвращает отпарсенный HTTP ответ cookies.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Web\HttpCookies 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/getcookies.php
	* @author Bitrix
	*/
	public function getCookies()
	{
		return $this->responseCookies;
	}

	/**
	 * Returns HTTP response status code
	 *
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает код статуса HTTP ответа.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/getstatus.php
	* @author Bitrix
	*/
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Returns HTTP response entity string. Note, if outputStream is set, the result will be empty string.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает строку сущности HTTP ответа. </p> <p class="note"> <b>Важно</b>! если установлен <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/setoutputstream.php">OutputStream</a>, то результатом будет пустая строка.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/getresult.php
	* @author Bitrix
	*/
	public function getResult()
	{
		if($this->waitResponse && $this->resource)
		{
			$this->readBody();
			$this->disconnect();
		}
		return $this->result;
	}

	/**
	 * Returns array of errors on failure
	 *
	 * @return array Array with "error_code" => "error_message" pair
	 */
	
	/**
	* <p>Нестатический метод возвращает массив ошибок при неудаче.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/geterror.php
	* @author Bitrix
	*/
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Returns response content type
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает тип контента ответа.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/getcontenttype.php
	* @author Bitrix
	*/
	public function getContentType()
	{
		return $this->responseHeaders->getContentType();
	}

	/**
	 * Returns response content encoding
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает кодировку контента ответа.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/getcharset.php
	* @author Bitrix
	*/
	public function getCharset()
	{
		return $this->responseHeaders->getCharset();
	}
}
