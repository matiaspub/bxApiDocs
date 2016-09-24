<?

namespace Bitrix\Main\UrlPreview\Parser;

use Bitrix\Main\UrlPreview\HtmlDocument;
use Bitrix\Main\UrlPreview\Parser;

class OpenGraph extends Parser
{
	/**
	 * Parses HTML documents OpenGraph metadata
	 *
	 * @param HtmlDocument $document HTML document to be parsed.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод парсит HTML документ по разметке OpenGraph.</p>
	*
	*
	* @param mixed $Bitrix  HTML документ для парсинга.
	*
	* @param Bitri $Main  
	*
	* @param Mai $UrlPreview  
	*
	* @param HtmlDocument $document  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parser/opengraph/handle.php
	* @author Bitrix
	*/
	static public function handle(HtmlDocument $document)
	{
		if(strlen($document->getTitle()) == 0)
		{
			$ogTitle = $document->getMetaContent('og:title');
			if(strlen($ogTitle) > 0)
			{
				$document->setTitle($ogTitle);
			}
		}

		if(strlen($document->getDescription()) == 0)
		{
			$ogDescription = $document->getMetaContent('og:description');
			if(strlen($ogDescription) > 0)
			{
				$document->setDescription($ogDescription);
			}
		}

		if(strlen($document->getImage()) == 0)
		{
			$ogImage = $document->getMetaContent('og:image:secure_url') ?: $document->getMetaContent('og:image');
			if(strlen($ogImage) > 0)
			{
				$document->setImage($ogImage);
			}
		}

		if(!$document->getExtraField('SITE_NAME'))
		{
			$ogSiteName = $document->getMetaContent('og:site_name');
			if(strlen($ogSiteName) > 0)
			{
				$document->setExtraField('SITE_NAME', $ogSiteName);
			}
		}

		/*	Not really opengraph property :), but it's placed in opengraph parser to prevent executing full parser chain
			just to get favicon */
		if(!$document->getExtraField('FAVICON'))
		{
			if($favicon = $document->getLinkHref('icon'))
			{
				$document->setExtraField('FAVICON', $favicon);
			}
		}
	}
}