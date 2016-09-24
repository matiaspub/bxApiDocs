<?php

namespace Bitrix\Main\UrlPreview;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Uri;

class ParserChain
{
	/** @var array */
	protected static $metadataParsers =  array(
		'Bitrix\Main\UrlPreview\Parser\OpenGraph',
		'Bitrix\Main\UrlPreview\Parser\SchemaOrg',
		'Bitrix\Main\UrlPreview\Parser\Oembed',
		'Bitrix\Main\UrlPreview\Parser\Common'
	);

	/**
	 * @var array Key is host, value - parser class name
	 */
	protected  static $metadataParsersByHost = array(

	);

	/**
	 * @param Uri $uri
	 * @return array
	 */
	protected static function getParserChain(Uri $uri)
	{
		$result = array();
		if(isset(static::$metadataParsersByHost[$uri->getHost()]))
		{
			$result[] = static::$metadataParsersByHost[$uri->getHost()];
		}

		$result = array_merge($result, static::$metadataParsers);

		return $result;
	}

	/**
	 * Executes chain of parsers, passing them $document
	 *
	 * @param HtmlDocument $document
	 */
	
	/**
	* <p>Статический метод выполняет последовательность парсеров для приходящего <code>$document</code>.</p>
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parserchain/extractmetadata.php
	* @author Bitrix
	*/
	public static function extractMetadata(HtmlDocument $document)
	{
		foreach(static::getParserChain($document->getUri()) as $parserClassName)
		{
			/** @var \Bitrix\Main\UrlPreview\Parser $parser */
			if(class_exists($parserClassName))
			{
				$parser = new $parserClassName();
				if(is_a($parser, '\Bitrix\Main\UrlPreview\Parser'))
				{
					$parser->handle($document);
				}
			}
			if($document->checkMetadata())
			{
				break;
			}
		}
	}

	/**
	 * Registers special parser for host
	 *
	 * @param string $host
	 * @param string $parserClassName Parser class must extend \Bitrix\Main\UrlPreview\Parser
	 * @throws ArgumentException
	 */
	
	/**
	* <p>Статический метод регистрирует специальный парсер для хоста.</p>
	*
	*
	* @param string $host  Хост
	*
	* @param string $parserClassName  Класс парсера - расширение <a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parser/index.php">\Bitrix\Main\UrlPreview\Parser</a>
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parserchain/registermetadataparser.php
	* @author Bitrix
	*/
	public static function registerMetadataParser($host, $parserClassName)
	{
		if(!class_exists($parserClassName) || !is_subclass_of($parserClassName, '\Bitrix\Main\UrlPreview\Parser'))
		{
			throw new ArgumentException('Parser class must extend \Bitrix\Main\UrlPreview\Parser', 'parserClassName');
		}

		static::$metadataParsersByHost[$host] = $parserClassName;
	}
}