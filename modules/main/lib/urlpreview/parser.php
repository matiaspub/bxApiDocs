<?

namespace Bitrix\Main\UrlPreview;

abstract class Parser
{
	/**
	 * Method should parse document and fill document's metadata properties that were left unfilled by
	 * previous parsers in chain.
	 *
	 * @param HtmlDocument $document
	 */
	
	/**
	* <p>Абстрактный нестатический метод парсит документ и заполняет свойства метаданных документа, которые не были заполнены предыдущими парсерами в цепочке.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Mai $UrlPreview  
	*
	* @param HtmlDocument $document  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parser/handle.php
	* @author Bitrix
	*/
	abstract public function handle(HtmlDocument $document);
}