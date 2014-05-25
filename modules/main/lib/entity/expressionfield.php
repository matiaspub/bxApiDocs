<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Expr field is to describe dynamic fields by expression, e.g. we have PRICE_USD field and need to count price in EUR
 * then we define expression field PRICE_EUR with expression = array('%s * 1.25', 'PRICE_USD')
 * @package bitrix
 * @subpackage main
 */
class ExpressionField extends Field
{
	/**
	 * @var string
	 */
	protected $expression;

	/**
	 * Full expression, recursively includes expressions from buildFrom fields
	 * @var string
	 */
	protected $fullExpression;

	/**
	 * @var ScalarField
	 */
	protected $valueField;

	/**
	 * @var array
	 */
	protected $buildFrom;

	/**
	 * @var QueryChain[]
	 */
	protected $buildFromChains;

	protected $isAggregated;

	protected $hasSubquery;

	protected $options;


	protected static
		$aggrFunctionsMYSQL = array('AVG', 'BIT_AND', 'BIT_OR', 'BIT_XOR', 'COUNT',
			'GROUP_CONCAT', 'MAX', 'MIN', 'STD', 'STDDEV_POP', 'STDDEV_SAMP',
			'STDDEV', 'SUM', 'VAR_POP', 'VAR_SAMP', 'VARIANCE'
		),
		$aggrFunctionsMSSQL = array('AVG', 'MIN', 'CHECKSUM_AGG', 'OVER', 'COUNT',
			'ROWCOUNT_BIG', 'COUNT_BIG', 'STDEV', 'GROUPING', 'STDEVP',
			'GROUPING_ID', 'SUM', 'MAX', 'VAR', 'VARP'
		),
		$aggrFunctionsORACLE = array('AVG', 'COLLECT', 'CORR', 'CORR_S', 'CORR_K',
			'COUNT', 'COVAR_POP', 'COVAR_SAMP', 'CUME_DIST', 'DENSE_RANK', 'FIRST',
			'GROUP_ID', 'GROUPING', 'GROUPING_ID', 'LAST', 'MAX', 'MEDIAN', 'MIN',
			'PERCENTILE_CONT', 'PERCENTILE_DISC', 'PERCENT_RANK', 'RANK',
			'REGR_SLOPE', 'REGR_INTERCEPT', 'REGR_COUNT', 'REGR_R2', 'REGR_AVGX',
			'REGR_AVGY', 'REGR_SXX', 'REGR_SYY', 'REGR_SXY', 'STATS_BINOMIAL_TEST',
			'STATS_CROSSTAB', 'STATS_F_TEST', 'STATS_KS_TEST', 'STATS_MODE',
			'STATS_MW_TEST', 'STATS_ONE_WAY_ANOVA', 'STATS_T_TEST_ONE',
			'STATS_T_TEST_PAIRED', 'STATS_T_TEST_INDEP', 'STATS_T_TEST_INDEPU',
			'STATS_WSR_TEST', 'STDDEV', 'STDDEV_POP', 'STDDEV_SAMP', 'SUM',
			'VAR_POP', 'VAR_SAMP', 'VARIANCE'
		),
		$aggrFunctions;



	public function __construct($name, $dataType, Base $entity, $expression, $parameters = array())
	{
		parent::__construct($name, $dataType, $entity, $parameters);

		$this->buildFrom = $expression;
		$this->expression = array_shift($this->buildFrom);

		unset($parameters['expression']);
		$this->valueField = $this->entity->initializeField($name, $parameters);

		if (!($this->valueField instanceof ScalarField))
		{
			throw new \Exception('expression field can only be a scalar type.');
		}

		if (isset($parameters['options']))
		{
			$this->options = $parameters['options'];
		}
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->valueField, $name), $arguments);
	}

	public function validateValue($value, $row, Result $result)
	{
		return $this->valueField->validateValue($value, $row, $result);
	}

	public function getExpression()
	{
		return $this->expression;
	}

	public function getFullExpression()
	{
		if (!isset($this->fullExpression))
		{
			$SQLBuildFrom = array();

			foreach ($this->getBuildFromChains() as $chain)
			{
				if ($chain->getLastElement()->getValue() instanceof ExpressionField)
				{
					$SQLBuildFrom[] = $chain->getLastElement()->getValue()->getFullExpression();
				}
				else
				{
					$SQLBuildFrom[] = '%s';
				}
			}

			$this->fullExpression = call_user_func_array('sprintf', array_merge(array($this->expression), $SQLBuildFrom));
		}

		return $this->fullExpression;
	}

	public function isAggregated()
	{
		if (!isset($this->isAggregated))
		{
			$this->isAggregated = (bool) self::checkAggregation($this->getFullExpression());
		}

		return $this->isAggregated;
	}

	public function hasSubquery()
	{
		if (!isset($this->hasSubquery))
		{
			$this->hasSubquery = (bool) self::checkSubquery($this->getFullExpression());
		}

		return $this->hasSubquery;
	}

	public function isConstant()
	{
		return empty($this->buildFrom);
	}

	public function getBuildFromChains()
	{
		if (is_null($this->buildFromChains))
		{
			$this->buildFromChains = array();

			foreach ($this->buildFrom as $elem)
			{
				$this->buildFromChains[] = QueryChain::getChainByDefinition($this->entity, $elem);
			}
		}

		return $this->buildFromChains;
	}

	/**
	 * @return array|null
	 */
	public function getOptions()
	{
		return $this->options;
	}

	public static function checkAggregation($expression)
	{
		if (empty(self::$aggrFunctions))
		{
			self::$aggrFunctions = array_unique(array_merge(
				self::$aggrFunctionsMYSQL, self::$aggrFunctionsMSSQL, self::$aggrFunctionsORACLE
			));
		}

		// should remove subqueries from expression here: EXISTS(..(..)..), (SELECT ..(..)..)

		if (preg_match('/(?:^|[^a-z0-9_])EXISTS\s*\(/', $expression))
		{
			return false;
		}
		else
		{
			preg_match_all('/(?:^|[^a-z0-9_])(?<!SELECT\s\s)('.join('|', self::$aggrFunctions).')[\s\(]+/i', $expression, $matches);

			return $matches[1];
		}
	}

	public static function checkSubquery($expression)
	{
		return (preg_match('/(?:^|[^a-z0-9_])EXISTS\s*\(/', $expression) || preg_match('/(?:^|[^a-z0-9_])\(\s*SELECT/', $expression));
	}

	public function __clone()
	{
		$this->buildFromChains = null;
	}
}


