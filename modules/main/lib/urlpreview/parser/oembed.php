<?php

namespace Bitrix\Main\UrlPreview\Parser;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UrlPreview\HtmlDocument;
use Bitrix\Main\UrlPreview\Parser;


class Oembed extends Parser
{
	const OEMBED_TYPE_XML = "text/xml+oembed";
	const OEMBED_TYPE_JSON ="application/json+oembed";

	/** @var string Possible values: (json|xml) */
	protected $metadataType;

	/** @var string */
	protected $metadataUrl;

	/** @var  string */
	protected $metadataEncoding;

	/**
	 * Downloads and parses HTML's document metadata, formatted with oEmbed standard.
	 *
	 * @param HtmlDocument $document HTML document.
	 */
	
	/**
	* <p>Нестатический метод загружает и парсит метаданные HTML документа, форматируя их по стандарту <b>oEmbed</b>.</p>
	*
	*
	* @param mixed $Bitrix  HTML документ.
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parser/oembed/handle.php
	* @author Bitrix
	*/
	public function handle(HtmlDocument $document)
	{
		if(!$this->detectOembedLink($document) || strlen($this->metadataUrl) == 0)
		{
			return;
		}

		$httpClient = new HttpClient();
		$rawMetadata = $httpClient->get($this->metadataUrl);

		if($rawMetadata === false)
		{
			return;
		}

		$parsedMetadata = $this->parseMetadata($rawMetadata);
		if($parsedMetadata !== false)
		{
			if(strlen($this->metadataEncoding) > 0 && $document->getEncoding() !== $this->metadataEncoding)
			{
				$parsedMetadata = Encoding::convertEncoding($parsedMetadata, $this->metadataEncoding, $document->getEncoding());
			}

			if($document->getTitle() == '' && $parsedMetadata['title'] != '')
			{
				$document->setTitle($parsedMetadata['title']);
			}

			if($document->getImage() == '' && $parsedMetadata['thumbnail_url'] != '')
			{
				$document->setImage($parsedMetadata['thumbnail_url']);
			}

			if($document->getEmdbed() == '' && $parsedMetadata['html'] != '')
			{
				$document->setEmbed($parsedMetadata['html']);
			}
		}
	}

	/**
	 * @param HtmlDocument $document
	 * @return bool
	 */
	protected function detectOembedLink(HtmlDocument $document)
	{
		preg_match_all('/<link.+?alternate.+?>/', $document->getHtml(), $linkElements);

		foreach($linkElements[0] as $linkElement)
		{
			$typeJson = (strpos($linkElement, $this::OEMBED_TYPE_JSON) !== false);
			$typeXml = (strpos($linkElement, $this::OEMBED_TYPE_XML) !== false);
			if($typeJson || $typeXml)
			{
				if(preg_match('/href=[\'"](.+?)[\'"]/', $linkElement, $attributes))
				{
					$this->metadataType = ($typeJson ? 'json' : 'xml');
					$this->metadataUrl = $attributes[1];
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $rawMetadata
	 * @return array|false
	 */
	protected function parseMetadata($rawMetadata)
	{
		switch($this->metadataType)
		{
			case 'json':
				return $this->parseJsonMetadata($rawMetadata);
				break;
			case 'xml':
				return $this->parseXmlMetadata($rawMetadata);
				break;
		}

		return false;
	}

	protected function parseJsonMetadata($rawMetadata)
	{
		$parsedMetadata = json_decode($rawMetadata, true);
		$this->metadataEncoding = 'UTF-8';

		return $parsedMetadata;
	}

	/**
	 * @param string $rawMetadata
	 * @return array|false
	 */
	protected function parseXmlMetadata($rawMetadata)
	{
		$xml = new \CDataXML();
		if($xml->LoadString($rawMetadata))
		{
			//detect xml encoding
			if(preg_match('/<\?xml[^>]+?encoding=[\'"](.+?)[\'"]\?>/', $rawMetadata, $matches))
				$this->metadataEncoding = $matches[1];
			else
				$this->metadataEncoding = 'UTF-8';

			$result = array();
			$dom = $xml->GetTree();
			$mainNode = $dom->elementsByName('oembed');
			foreach($mainNode[0]->children as $node)
			{
				$result[$node->name] = $node->content;
			}
			return $result;
		}

		return false;
	}
}