<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

use Bitrix\Main\Text\String;
use Bitrix\Main\IO;

class HttpClient
{
	const HTTP_1_0 = "1.0";
	const HTTP_1_1 = "1.1";
	const HTTP_GET = "GET";
	const HTTP_POST = "POST";
	const HTTP_PUT = "PUT";
	const BUF_READ_LEN = 8192;
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

	protected $status = 0;
	/** @var HttpHeaders */
	protected $responseHeaders;
	/** @var HttpCookies  */
	protected $responseCookies;
	protected $result = '';
	protected $outputStream;
	protected $contentType = '';
	protected $responseCharset = '';

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
	 * 	All the options can be set separately with setters.
	 */
	public function __construct(array $options = null)
	{
		$this->requestHeaders = new HttpHeaders();
		$this->responseHeaders = new HttpHeaders();
		$this->requestCookies = new HttpCookies();
		$this->responseCookies = new HttpCookies();

		if($options !== null)
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
		}
	}

	/**
	 * Perfoms GET request.
	 *
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query"
	 * @return string|bool Response entity string or false on error. Note, it's empty string if outputStream is set
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
	 * Perfoms POST request.
	 *
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query"
	 * @param array|string|resource $postData Entity of POST/PUT request. If it's resource handler then data will be read directly from the stream
	 * @return string|bool Response entity string or false on error. Note, it's empty string if outputStream is set
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
	 * @param string $method HTTP method (GET, POST, etc.). Note, it must be in UPPERCASE
	 * @param string $url Absolute URI eg. "http://user:pass @ host:port/path/?query"
	 * @param array|string|resource $postData Entity of POST/PUT request. If it's resource handler then data will be read directly from the stream
	 * @return bool Query result (true or false). Response entity string can be get via getResult() method. Note, it's empty string if outputStream is set
	 */
	public function query($method, $url, $postData = null)
	{
		$queryMethod = $method;
		$queryUrl = $url;

		if(is_array($postData))
		{
			$postData = http_build_query($postData, "", "&");
		}

		while(true)
		{
			//Only absoluteURI is accepted
			//Location response-header field must be absoluteURI either
			$parsedUrl = new Uri($queryUrl);
			if($parsedUrl->getHost() == '')
			{
				$this->error["URI"] = "Incorrect URI: ".$queryUrl;
				return false;
			}

			if($this->connect($parsedUrl) === false)
			{
				return false;
			}

			$this->sendRequest($queryMethod, $parsedUrl, $postData);

			if(!$this->waitResponse)
			{
				$this->disconnect();
				return true;
			}

			$this->readResponse();

			$this->disconnect();

			if($this->redirect && ($location = $this->responseHeaders->get("Location")) !== null && $location <> '')
			{
				if($this->redirectCount < $this->redirectMax)
				{
					$queryUrl = $location;
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
				break;
			}
		}
		return true;
	}

	/**
	 * Sets an HTTP request header field
	 *
	 * @param string $name Name of the header field
	 * @param string $value Value of the field
	 * @param bool $replace Replace existing header field with the same name or add one more
	 */
	public function setHeader($name, $value, $replace = true)
	{
		if($replace == true || $this->requestHeaders->get($name) === null)
		{
			$this->requestHeaders->set($name, $value);
		}
	}

	/**
	 * Sets an array of cookies for HTTP request
	 *
	 * @param array $cookies Array of cookie_name => value pairs.
	 */
	public function setCookies(array $cookies)
	{
		$this->requestCookies->set($cookies);
	}

	/**
	 * Sets Basic Authorization request header field
	 *
	 * @param string $user Username
	 * @param string $pass Password
	 */
	public function setAuthorization($user, $pass)
	{
		$this->setHeader("Authorization", "Basic ".base64_encode($user.":".$pass));
	}

	/**
	 * Sets redirect options
	 *
	 * @param bool $value If true, do redirect (default true)
	 * @param null|int $max Maximum allowed redirect count
	 */
	public function setRedirect($value, $max = null)
	{
		$this->redirect = ($value? true : false);
		if($max !== null)
		{
			$this->redirectMax = intval($value);
		}
	}

	/**
	 * Sets response waiting option
	 *
	 * @param bool $value If true, wait for response. If false, return just after request (default true)
	 */
	public function waitResponse($value)
	{
		$this->waitResponse = ($value? true : false);
	}

	/**
	 * Sets connection timeout
	 *
	 * @param int $value Connection timeout in seconds (default 30)
	 */
	public function setTimeout($value)
	{
		$this->socketTimeout = intval($value);
	}

	/**
	 * Sets socket stream reading timeout
	 *
	 * @param int $value Stream reading timeout in seconds; "0" means no timeout (default 60)
	 */
	public function setStreamTimeout($value)
	{
		$this->streamTimeout = intval($value);
	}

	/**
	 * Sets HTTP protocol version. In version 1.1 chunked response is possible.
	 *
	 * @param string $value Version "1.0" or "1.1" (default "1.0").
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
	 */
	public function setCompress($value)
	{
		$this->compress = ($value? true : false);
	}

	/**
	 * Sets charset for entity-body (used in the Content-Type request header field for POST and PUT)
	 *
	 * @param string $value
	 */
	public function setCharset($value)
	{
		$this->requestCharset = $value;
	}

	/**
	 * Sets HTTP proxy for request
	 *
	 * @param string $proxyHost Proxy host name or address (without "http://")
	 * @param null|int $proxyPort Proxy port number
	 * @param null|string $proxyUser Proxy username
	 * @param null|string $proxyPassword Proxy password
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
	 */
	public function setOutputStream($handler)
	{
		$this->outputStream = $handler;
	}

	/**
	 * Downloads and saves a file.
	 *
	 * @param string $url URI to download
	 * @param string $filePath Absolute file path
	 * @return bool
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

			fclose($handler);
			return $res;
		}

		return false;
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
			$port = $url->getPort();
		}

		$res = stream_socket_client($proto.$host.":".$port, $errno, $errstr, $this->socketTimeout);

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

		$buf = fread($this->resource, $bufLength);
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

	protected function sendRequest($method, Uri $url, $postData = null)
	{
		$this->status = 0;
		$this->result = '';
		$this->responseHeaders->clear();
		$this->responseCookies->clear();

		if($this->proxyHost <> '')
		{
			$path = $url->getUrl();
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

		if(!is_resource($postData) && ($method == self::HTTP_POST || $method == self::HTTP_PUT))
		{
			if($this->requestHeaders->get("Content-Type") === null)
			{
				$contentType = "application/x-www-form-urlencoded";
				if($this->requestCharset <> '')
				{
					$contentType .= "; charset=".$this->requestCharset;
				}
				$this->setHeader("Content-Type", $contentType);
			}
			if($this->requestHeaders->get("Content-Length") === null)
			{
				$this->setHeader("Content-Length", String::getBinaryLength($postData));
			}
		}

		$request .= $this->requestHeaders->toString();
		$request .= "\r\n";

		$this->send($request);

		if($method == self::HTTP_POST || $method == self::HTTP_PUT)
		{
			if(is_resource($postData))
			{
				//PUT data can be file resource
				while(!feof($postData))
				{
					$this->send(fread($postData, self::BUF_POST_LEN));
				}
			}
			else
			{
				$this->send($postData);
			}
			$this->send("\r\n");
		}
	}

	protected function readResponse()
	{
		$headers = "";
		while(!feof($this->resource))
		{
			$line = fgets($this->resource, self::BUF_READ_LEN);
			if($line == "\r\n" || $line === false)
			{
				break;
			}
			$headers .= $line;

			if($this->streamTimeout > 0)
			{
				$info = stream_get_meta_data($this->resource);
				if($info['timed_out'])
				{
					break;
				}
			}
		}

		$this->parseHeaders($headers);

		if($this->redirect && ($location = $this->responseHeaders->get("Location")) !== null && $location <> '')
		{
			//do we need entity body on redirect?
			return;
		}

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
					if($buf === false)
					{
						break 2;
					}
					$length -= String::getBinaryLength($buf);

					if($this->streamTimeout > 0)
					{
						$info = stream_get_meta_data($this->resource);
						if($info['timed_out'])
						{
							break 2;
						}
					}
				}
			}
		}
		else
		{
			while(!feof($this->resource))
			{
				$buf = $this->receive();
				if($buf === false)
				{
					break;
				}

				if($this->streamTimeout > 0)
				{
					$info = stream_get_meta_data($this->resource);
					if($info['timed_out'])
					{
						break;
					}
				}
			}
		}

		if($this->responseHeaders->get("Content-Encoding") == "gzip")
		{
			$this->decompress();
		}
	}

	protected function decompress()
	{
		if(is_resource($this->outputStream))
		{
			$compressed = stream_get_contents($this->outputStream, -1, 10);
			$compressed = String::getBinarySubstring($compressed, 0, -8);
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
			$compressed = String::getBinarySubstring($this->result, 10, -8);
			if($compressed <> '')
			{
				$this->result = gzinflate($compressed);
			}
		}
	}

	protected function parseHeaders($headers)
	{
		$arHeaders = explode("\n", $headers);
		foreach ($arHeaders as $k => $header)
		{
			if($k == 0)
			{
				if(preg_match('#HTTP\S+ (\d+)#', $header, $arFind))
				{
					$this->status = intval($arFind[1]);
				}
			}
			elseif(strpos($header, ':') !== false)
			{
				$arHeader = explode(':', $header, 2);
				if(strtolower($arHeader[0]) == 'set-cookie')
				{
					$this->responseCookies->addFromString($arHeader[1]);
				}
				$this->responseHeaders->add($arHeader[0], trim($arHeader[1]));
			}
		}

		if(($contentType = $this->responseHeaders->get("Content-Type")) !== null)
		{
			$parts = explode(";", $contentType);
			$this->contentType = trim($parts[0]);
			foreach($parts as $part)
			{
				$values = explode("=", $part);
				if(strtolower(trim($values[0])) == "charset")
				{
					$this->responseCharset = trim($values[1]);
					break;
				}
			}
		}
	}

	/**
	 * Returns parsed HTTP response headers
	 *
	 * @return HttpHeaders
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
	public function getCookies()
	{
		return $this->responseCookies;
	}

	/**
	 * Returns HTTP response status code
	 *
	 * @return int
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
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * Returns array of errors on failure
	 *
	 * @return array Array with "error_code" => "error_message" pair
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
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Returns response content encoding
	 *
	 * @return string
	 */
	public function getCharset()
	{
		return $this->responseCharset;
	}
}
