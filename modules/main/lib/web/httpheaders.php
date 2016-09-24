<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

class HttpHeaders
{
	protected $headers = array();

	static public function __construct()
	{
	}

	/**
	 * Adds a header.
	 * @param string $name
	 * @param string $value
	 */
	
	/**
	* <p>Нестатический метод добавляет заголовок.</p>
	*
	*
	* @param string $name  
	*
	* @param string $value  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/add.php
	* @author Bitrix
	*/
	public function add($name, $value)
	{
		$name = str_replace(array("\r", "\n"), "", $name);
		$value = str_replace(array("\r", "\n"), "", $value);
		$nameLower = strtolower($name);

		if(!isset($this->headers[$nameLower]))
		{
			$this->headers[$nameLower] = array(
				"name" => $name,
				"values" => array(),
			);
		}
		$this->headers[$nameLower]["values"][] = $value;
	}

	/**
	 * Sets a header value.
	 * @param string $name
	 * @param string $value
	 */
	
	/**
	* <p>Нестатический метод устанавливает значение заголовка.</p>
	*
	*
	* @param string $name  
	*
	* @param string $value  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/set.php
	* @author Bitrix
	*/
	public function set($name, $value)
	{
		$name = str_replace(array("\r", "\n"), "", $name);
		$value = str_replace(array("\r", "\n"), "", $value);
		$nameLower = strtolower($name);

		$this->headers[$nameLower] = array(
			"name" => $name,
			"values" => array($value),
		);
	}

	/**
	 * Returns a header value by its name. If $returnArray is true then an array with multiple values is returned.
	 * @param string $name
	 * @param bool $returnArray
	 * @return null|string|array
	 */
	
	/**
	* <p>Нестатический метод возвращает заголовок по его имени.</p>
	*
	*
	* @param string $name  Имя заголовка.
	*
	* @param boolean $returnArray = false Если <i>true</i>, то возвращает массив с несколькими значениями.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/get.php
	* @author Bitrix
	*/
	public function get($name, $returnArray = false)
	{
		$nameLower = strtolower($name);

		if(isset($this->headers[$nameLower]))
		{
			if($returnArray)
			{
				return $this->headers[$nameLower]["values"];
			}
			return $this->headers[$nameLower]["values"][0];
		}
		return null;
	}

	/**
	 * Clears all headers.
	 */
	
	/**
	* <p>Нестатический метод очищает все заголовки.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/clear.php
	* @author Bitrix
	*/
	public function clear()
	{
		$this->headers = array();
	}

	/**
	 * Returns the string representation for a HTTP request.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает строковое представление запроса HTTP.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/tostring.php
	* @author Bitrix
	*/
	public function toString()
	{
		$str = "";
		foreach($this->headers as $header)
		{
			foreach($header["values"] as $value)
			{
				$str .= $header["name"].": ".$value."\r\n";
			}
		}
		return $str;
	}

	/**
	 * Returns headers as a raw array.
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает заголовки в виде исходного массива.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/toarray.php
	* @author Bitrix
	*/
	public function toArray()
	{
		return $this->headers;
	}

	/**
	 * Returns the content type part of the Content-Type header.
	 * @return null|string
	 */
	
	/**
	* <p>Нестатический метод возвращает тип контента из <b>Content-Type</b> заголовка.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/getcontenttype.php
	* @author Bitrix
	*/
	public function getContentType()
	{
		$contentType = $this->get("Content-Type");
		if($contentType !== null)
		{
			$parts = explode(";", $contentType);
			return trim($parts[0]);
		}
		return null;
	}

	/**
	 * Returns the charset part of the Content-Type header.
	 * @return null|string
	 */
	
	/**
	* <p>Нестатический метод возвращает кодировку из <b>Content-Type</b> заголовка.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/getcharset.php
	* @author Bitrix
	*/
	public function getCharset()
	{
		$contentType = $this->get("Content-Type");
		if($contentType !== null)
		{
			$parts = explode(";", $contentType);
			foreach($parts as $part)
			{
				$values = explode("=", $part);
				if(strtolower(trim($values[0])) == "charset")
				{
					return trim($values[1]);
				}
			}
		}
		return null;
	}

	/**
	 * Returns disposition-type part of the Content-Disposition header
	 * @return null|string Disposition-type part of the Content-Disposition header if found or null otherwise.
	 */
	
	/**
	* <p>Нестатический метод возвращает <b>disposition-type</b> из <b>Content-Disposition</b> заголовка.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/getcontentdisposition.php
	* @author Bitrix
	*/
	public function getContentDisposition()
	{
		$contentDisposition = $this->get("Content-Disposition");
		if($contentDisposition !== null)
		{
			$parts = explode(";", $contentDisposition);
			return trim($parts[0]);
		}
		return null;
	}

	/**
	 * Returns a filename from the Content-disposition header.
	 *
	 * @return string|null Filename if it was found in the Content-disposition header or null otherwise.
	 */
	
	/**
	* <p>Нестатический метод возвращает имя файла из <b>Content-disposition</b> заголовка.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpheaders/getfilename.php
	* @author Bitrix
	*/
	public function getFilename()
	{
		$contentDisposition = $this->get('Content-disposition');
		if($contentDisposition !== null)
		{
			$filename = null;
			$encoding = null;

			$contentElements = explode(';', $contentDisposition);
			foreach($contentElements as $contentElement)
			{
				$contentElement = trim($contentElement);
				if(preg_match('/^filename\*=(.+)\'(.+)?\'(.+)$/', $contentElement, $matches))
				{
					$filename = $matches[3];
					$encoding = $matches[1];
					break;
				}
				elseif(preg_match('/^filename="(.+)"$/', $contentElement, $matches))
				{
					$filename = $matches[3];
				}
			}

			if($filename <> '')
			{
				$filename = urldecode($filename);

				if($encoding <> '')
				{
					$charset = \Bitrix\Main\Context::getCurrent()->getCulture()->getCharset();
					$filename = \Bitrix\Main\Text\Encoding::convertEncoding($filename, $encoding, $charset);
				}
			}

			return $filename;
		}
		return null;
	}
}
