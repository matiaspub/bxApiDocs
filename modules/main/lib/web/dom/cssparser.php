<?php
namespace Bitrix\Main\Web\DOM;


class CssParser
{
	public static function parseDocument(Document $document, $sort = false)
	{
		$css = static::findDocumentCss($document);

		return static::parse($css, $sort);
	}

	public static function parse($css, $sort = false)
	{
		$result = static::parseCss($css);
		if($sort)
		{
			return static::sortSelectors($result);
		}
		else
		{
			return $result;
		}
	}

	public static function parseCss($css)
	{
		$result = array();

		// remove comments
		$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','', $css);
		// remove keyframes rules
		$css = preg_replace('/@[-|keyframes].*?\{.*?\}[ \r\n]*?/s', '', $css);
		$css = trim($css);
		foreach(explode("}", $css) as $declarationBlock)
		{
			$declarationBlock = trim($declarationBlock);
			if(!$declarationBlock)
			{
				continue;
			}

			$declarationBlockExploded = explode("{", $declarationBlock);
			$selectorList = $declarationBlockExploded[0];
			$declaration = $declarationBlockExploded[1];
			$declaration = trim(trim($declaration), ";");

			foreach(explode(',', $selectorList) as $selector)
			{
				$selector = trim($selector);
				$result[] = array(
					'SELECTOR' => $selector,
					'STYLE' => static::getDeclarationArray($declaration),
				);
			}
		}

		return $result;
	}

	/**
	 * @param Document $document
	 * @return string
	 */
	public static function findDocumentCss(Document $document)
	{
		if(!$document->getHead())
		{
			return '';
		}

		if(!$document->getHead()->hasChildNodes())
		{
			return '';
		}

		$cssList = array();
		foreach($document->getHead()->getChildNodes() as $child)
		{
			/** @var $child Element */
			if($child->getNodeName() === "STYLE" && $child->getAttribute('media') !== 'print')
			{
				$cssList[] = $child->getTextContent();
				//$child->getParentNode()->removeChild($child);
			}
		}

		return implode("\n", $cssList);
	}

	public static function getDeclarationArray($declarationBlock)
	{
		$styleList = array();
		$declarationBlock = trim($declarationBlock);
		if($declarationBlock)
		{
			foreach(explode(";", $declarationBlock) as $declaration)
			{
				$declaration = trim($declaration);
				if(!$declaration)
				{
					continue;
				}

				// check declaration
				if (!preg_match('#^([-a-z0-9\*]+):(.*)$#i', $declaration, $matches))
				{
					continue;
				}

				if(!isset($matches[0], $matches[1], $matches[2]))
				{
					continue;
				}

				$styleList[trim($matches[1])] = trim($matches[2]);
			}
		}

		return $styleList;
	}

	public static function getDeclarationString($declarationList)
	{
		$result = '';
		foreach($declarationList as $property => $value)
		{
			$result .= trim($property) . ': ' . trim($value) . ';';
		}

		return $result;
	}


	public static function sortSelectors($styleList)
	{
		foreach($styleList as $k => $v)
		{
			$styleList[$k]['SORT'] = static::getSelectorSort($v['SELECTOR']);
		}

		usort($styleList, function ($a, $b)	{
			$a = $a['SORT'];
			$b = $b['SORT'];
			for($i = 0; $i<3; $i++)
			{
				if($a[$i] !== $b[$i])
				{
					return $a[$i] < $b[$i] ? -1 : 1;
				}
			}

			return 1; // last class have more priority
		});

		foreach($styleList as $k => $v)
		{
			unset($styleList[$k]['SORT']);
		}

		return array_reverse($styleList);
	}

	public static function getSelectorSort($selector)
	{
		return array(
			preg_match_all('/#\w/i', $selector, $result),
			preg_match_all('/\.\w/i', $selector, $result),
			preg_match_all('/^\w|\ \w|\(\w|\:[^not]/i', $selector, $result)
		);
	}
}