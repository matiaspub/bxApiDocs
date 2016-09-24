<?php
namespace Bitrix\Main\Web\DOM;

class StyleInliner
{
	/**
	 * @param Document $document
	 * @param array $styleList
	 * @return array
	 */
	public static function inlineDocument(Document $document, array $styleList = null)
	{
		if(!$styleList)
		{
			$styleList = CssParser::parseDocument($document, true);
		}

		foreach($styleList as $rule)
		{
			$nodeList = $document->querySelectorAll($rule['SELECTOR']);
			foreach($nodeList as $node)
			{
				static::setStyle($node, $rule['STYLE'], true);
			}
		}
	}

	public static function inlineHtml($html, array $styleList = null)
	{
		$document = new Document;
		$document->loadHTML($html);

		static::inlineDocument($document, $styleList);

		return $document->saveHTML();
	}

	/*
	 * @param Element $node
	 * @return array
	*/
	public static function getStyle(Element $node)
	{
		$styleList = array();
		$style = $node->getAttribute("style");
		if($style)
		{
			$styleList = CssParser::getDeclarationArray($style);
		}

		return $styleList;
	}

	/*
	 * @param Element $node
	 * @param array $styleList
	 * @param bool $append
	 * @return void
	*/
	public static function setStyle(Element $node, $styleList, $append = false)
	{
		if($append)
		{
			if(count($styleList) <= 0)
			{
				return;
			}

			$result = static::getStyle($node);
			foreach($styleList as $k => $v)
			{
				if("!important" !== substr(strtolower($styleList[$k]), -10))
				{
					if(array_key_exists($k, $result))
					{
						continue;
					}
				}

				$result[$k] = $v;
			}
		}
		else
		{
			$result = $styleList;
		}

		$node->setAttribute("style", CssParser::getDeclarationString($result));
	}
}