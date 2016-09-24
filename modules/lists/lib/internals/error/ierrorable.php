<?php

namespace Bitrix\Lists\Internals\Error;

interface IErrorable 
{
	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	
	/**
	* <p>Нестатический метод возвращает массив ошибок.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/lists/ierrorable/geterrors.php
	* @author Bitrix
	*/
	static public function getErrors();

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	
	/**
	* <p>Нестатический метод возвращает массив ошибок с необходимым кодом.</p>
	*
	*
	* @param string $code  Код ошибки.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/lists/ierrorable/geterrorsbycode.php
	* @author Bitrix
	*/
	static public function getErrorsByCode($code);

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	
	/**
	* <p>Нестатический метод получает ошибку с необходимым кодом.</p>
	*
	*
	* @param string $code  Код ошибки.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/lists/ierrorable/geterrorbycode.php
	* @author Bitrix
	*/
	static public function getErrorByCode($code);
}