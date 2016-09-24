<?php

namespace Bitrix\Main\UrlPreview\Parser;
use Bitrix\Main\UrlPreview\HtmlDocument;
use Bitrix\Main\UrlPreview\Parser;
use Bitrix\Main\Web\HttpHeaders;

class Common extends Parser
{
	const MIN_IMAGE_HEIGHT = 100;
	const MIN_IMAGE_WIDTH = 100;

	/** @var array img elements, discovered in the document */
	protected $imgElements = array();

	/**
	 * Parses HTML document's meta tags, and fills document's metadata.
	 *
	 * @param HtmlDocument $document HTML document to scan for metadata.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод парсит метатеги HTML документа и заполняет поля метаданных документа.</p>
	*
	*
	* @param mixed $Bitrix  HTML документ для сканирования метаданных
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parser/common/handle.php
	* @author Bitrix
	*/
	public function handle(HtmlDocument $document)
	{
		if(strlen($document->getTitle()) == 0)
		{
			$document->setTitle($this->getTitle($document));
		}

		if(strlen($document->getDescription()) == 0)
		{
			$document->setDescription($document->getMetaContent('description'));
		}

		$this->imgElements = $document->extractElementAttributes('img');
		if(strlen($document->getImage()) == 0)
		{
			$image = $this->getImage($document);
			if(strlen($image) > 0)
			{
				$document->setImage($image);
			}
			else
			{
				$imageCandidates = $this->getImageCandidates();
				if(count($imageCandidates) === 1)
				{
					$document->setImage($imageCandidates[0]);
				}
				else if(count($imageCandidates) > 1)
				{
					$document->setExtraField('IMAGES', $imageCandidates);
				}
			}
		}
	}

	/**
	 * @param HtmlDocument $document HTML document to scan for title.
	 * @return string
	 */
	protected function getTitle(HtmlDocument $document)
	{
		$title = $document->getMetaContent('title');
		if(strlen($title) > 0)
		{
			return $title;
		}

		preg_match('/<title>(.+?)<\/title>/mis', $document->getHtml(), $matches);
		return (isset($matches[1]) ? $matches[1] : null);
	}

	/**
	 * @param HtmlDocument $document
	 * @return string
	 */
	protected function getImage(HtmlDocument $document)
	{
		$result = $document->getLinkHref('image_src');
		if(strlen($result) > 0)
		{
			return $result;
		}

		foreach($this->imgElements as $imgElement)
		{
			if(isset($imgElement['rel']) && $imgElement['rel'] == 'image_src')
			{
				$result = $imgElement['src'];
				return $result;
			}
		}

		return null;
	}

	/**
	 * Iterates through img elements, and return array of urls of images, which size is greater then 100pxx100px
	 * @return array
	 */
	protected function getImageCandidates()
	{
		$result = array();
		foreach ($this->imgElements as $imgElement)
		{
			$imageDimensions = $this->getImageDimensions($imgElement);
			if($imageDimensions['width'] >= self::MIN_IMAGE_WIDTH && $imageDimensions['height'] >= self::MIN_IMAGE_HEIGHT)
			{
				$result[] = $imgElement['src'];
			}
		}
		return $result;
	}

	/**
	 * Returns size of the img element
	 * @param array $imageAttributes Array of the attributes of the img tag.
	 * @return array Returns array with keys width and height.
	 */
	protected function getImageDimensions(array $imageAttributes)
	{
		$result = array(
			'width' => null,
			'height' => null
		);

		foreach(array_keys($result) as $imageDimension)
		{
			if(isset($imageAttributes[$imageDimension]))
			{
				$result[$imageDimension] = $imageAttributes[$imageDimension];
			}
			else if(isset($imageAttributes['style']) && preg_match('/'.$imageDimension.':\s*(\d+?)px/', $imageAttributes['style'], $matches))
			{
				$result[$imageDimension] = $matches[1];
			}
		}
		return $result;
	}
}