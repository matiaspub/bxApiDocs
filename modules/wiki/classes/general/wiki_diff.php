<?

IncludeModuleLangFile(__FILE__);


/**
 * <b>CWikiDiff</b> - Класс сравнения вики-страниц.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikidiff/index.php
 * @author Bitrix
 */
class CWikiDiff
{
	/**
	 * @deprecated Use Bitrix\Wiki\Diff::getDiffHtml() instead.
	 * @param string $a First version of text to be compared.
	 * @param string $b Second version of text to be compared.
	 * @return string Formatted result of comparison.
	 */
	
	/**
	* <p>Метод сравнивает две страницы. Статический метод.</p>
	*
	*
	* @param string $stringX  Страница исходная.
	*
	* @param string $stringY  Страница, с которой сравниваем.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikidiff/getDiff.php
	* @author Bitrix
	* @deprecated Use Bitrix\Wiki\Diff::getDiffHtml() instead.
	*/
	public static function getDiff($a, $b)
	{
		$diff = new Bitrix\Wiki\Diff();
		return $diff->getDiffHtml($a, $b);
	}
}
