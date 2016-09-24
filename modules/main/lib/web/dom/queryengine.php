<?php
namespace Bitrix\Main\Web\DOM;

abstract class QueryEngine
{
	const DIR_DOWN = 0;
	const DIR_UP = 1;
	const FILTER_NODE_TYPE = 'nodeType';
	const FILTER_NODE_NAME = 'nodeName';
	const FILTER_ATTR = 'attr';
	const FILTER_ATTR_VALUE = 'attrValue';
	const FILTER_ATTR_CLASS_NAME = 'attrClassName';

	const FILTER_OPERATION_EQUAL = '=';
	const FILTER_OPERATION_NOT_EQUAL = '!';
	const FILTER_OPERATION_START = '^';
	const FILTER_OPERATION_END = '$';
	const FILTER_OPERATION_CONTAIN = '*';
	const FILTER_OPERATION_CONTAIN_WORD = '|';

	protected $limit = null;
	protected $deep = true;
	protected $direction = self::DIR_DOWN;

	private static $querySelectorEngine;

	public static function getQuerySelectorEngine()
	{
		if (self::$querySelectorEngine == null)
		{
			self::$querySelectorEngine = new QuerySelectorEngine();
		}

		return self::$querySelectorEngine;
	}

	protected function isNodeFiltered(Node $node, array $filter)
	{
		$isFiltered = false;
		foreach($filter as $filterItem)
		{
			foreach($filterItem as $type => $value)
			{
				switch($type)
				{
					case self::FILTER_NODE_TYPE:

						if (!is_array($value))
						{
							$value = array($value);
						}

						foreach($value as $nodeType)
						{
							if(!$node->getNodeType() === $nodeType)
							{
								return false;
							}
							else
							{
								$isFiltered = true;
							}
						}
						break;

					case self::FILTER_NODE_NAME:
						if(strtoupper($value) === $node->getNodeName())
						{
							$isFiltered = true;
						}
						else
						{
							return false;
						}
						break;

					case self::FILTER_ATTR:
						if(!$node->hasAttributes())
						{
							$isFiltered = false;
						}
						else
						{
							if (!is_array($value))
							{
								$value = array($value);
							}

							foreach($value as $attrName)
							{
								/* @var $node Element*/
								if($node->getAttribute($attrName) === null)
								{
									return false;
								}
								else
								{
									$isFiltered = true;
								}
							}
						}
						break;

					case self::FILTER_ATTR_VALUE:
						if(!$node->hasAttributes())
						{
							$isFiltered = false;
						}
						else
						{
							foreach($value as $attr)
							{
								$attrValue = $node->getAttribute($attr['name']);
								if(!$attrValue)
								{
									continue;
								}

								$operationValue = $attr['value'];
								switch($attr['operation'])
								{
									case self::FILTER_OPERATION_NOT_EQUAL:

										if($attrValue === $operationValue)
										{
											return false;
										}
										break;

									case self::FILTER_OPERATION_CONTAIN:

										if(strpos($attrValue, $operationValue) === false)
										{
											return false;
										}
										break;

									case self::FILTER_OPERATION_END:

										if(substr($attrValue, -strlen($operationValue)) !== $operationValue)
										{
											return false;
										}
										break;

									case self::FILTER_OPERATION_START:

										if(strpos($attrValue, $operationValue) !== 0)
										{
											return false;
										}
										break;

									case self::FILTER_OPERATION_CONTAIN_WORD:

										throw new DomException('Not supported query filter: FILTER_OPERATION_CONTAIN_WORD');
										break;

									case self::FILTER_OPERATION_EQUAL:
									default:
										if($attrValue !== $operationValue)
										{
											return false;
										}

								}

								$isFiltered = true;
							}
						}
						break;

					case self::FILTER_ATTR_CLASS_NAME:
						if(!$node->hasAttributes())
						{
							$isFiltered = false;
						}
						else
						{
							if (!is_array($value))
							{
								$value = array($value);
							}

							foreach($value as $className)
							{
								if(!in_array($className, $node->getClassList()))
								{
									return false;
								}
								else
								{
									$isFiltered = true;
								}
							}
						}
						break;
				}
			}
		}

		return $isFiltered;
	}

	public function walk(array $filter = null, callable $callback = null, Node $node, $limit = 0, $direction = self::DIR_DOWN)
	{
		if($limit > 0)
		{
			$this->limit = $limit;
		}
		else
		{
			$this->limit = null;
		}

		$this->deep = true;
		$this->direction = $direction;

		return $this->walkInternal($filter, $callback, $node, $callback, $direction);
	}


	protected function walkInternal(array $filter = null, callable $callback = null, Node $node)
	{
		$resultList = array();
		if($node->hasChildNodes())
		{
			foreach($node->getChildNodesArray() as $childNode)
			{
				if($callback)
				{
					$flag = call_user_func_array($callback, array($childNode, $filter));
				}
				elseif($filter)
				{
					$flag = $this->isNodeFiltered($childNode, $filter);
				}
				else
				{
					break;
				}

				if($flag === true)
				{
					// save node to result list
					$resultList[] = $childNode;

					// check limit of results
					if($this->limit !== null)
					{
						--$this->limit;
						if($this->limit <= 0)
						{
							break;
						}
					}

					// check search only in child list
					if(!$this->deep)
					{
						continue;
					}
				}

				$resultList = array_merge(
					$resultList,
					$this->walkInternal($filter, $callback, $childNode, $callback)
				);
			}
		}

		return $resultList;
	}

	abstract public function query($queryString = "", Node $node, $limit = 0, $direction = self::DIR_DOWN);
}