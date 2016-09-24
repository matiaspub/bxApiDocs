<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Main\Text;

class HtmlFilter
{
	
	/**
	* <p>Статический метод конвертирует специальные символы в HTML-сущности для безопасного вывода.</p>
	*
	*
	* @param string $string  Конвертируемая строка.
	*
	* @param string $flags = "ENT_COMPAT" Флаг, определяющий режим обработки кавычек, некорректных кодовых
	* последовательностей и используемый тип документа. По умолчанию
	* используется - <code>ENT_COMPAT</code>.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/htmlfilter/encode.php
	* @author Bitrix
	*/
	public static function encode($string, $flags = ENT_COMPAT)
	{
		return htmlspecialchars($string, $flags, (defined("BX_UTF") ? "UTF-8" : "ISO-8859-1"));
	}
}