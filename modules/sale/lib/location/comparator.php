<?
namespace Bitrix\Sale\Location;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\Comparator\Replacement;

Loader::registerAutoLoadClasses(
	'sale',
	array(
		'Bitrix\Sale\Location\Comparator\Replacement' => 'lib/location/comparator/ru/replacement.php',
	)
);

class Comparator
{
	const LOCALITY = 0;
	const DISTRICT = 1;
	const REGION = 2;
	const COUNTRY = 3;

	public static $variants = null;

	public static function isLocationsEqual($location1, $location2)
	{
		foreach($location1 as $type => $name)
		{
			if(empty($location2[$type]))
				continue;

			if(strlen($location1[$type]) > 0 && strlen($location2[$type]) > 0)
			{
				/** @var Comparator  $comparator */
				$comparator = self::getConcreteComparatorClassaName($type);

				if(!$comparator::isEntityEqual($location1[$type], $location2[$type]))
					return false;
			}
		}

		return true;
	}

	public static function isCountryRussia($countryName)
	{
		return Replacement::isCountryRussia($countryName);
	}

	/**
	 * @param int|string $type.
	 * @return string Comparator class name.
	 * @throws ArgumentOutOfRangeException
	 */
	private static function getConcreteComparatorClassaName($type)
	{
		if($type === self::LOCALITY || $type === 'LOCALITY' || $type == 'CITY')
			$result = 'ComparatorLocality';
		elseif($type === self::DISTRICT || $type === 'SUBREGION')
			$result = 'ComparatorDistrict';
		elseif($type === self::REGION || $type === 'REGION')
			$result = 'ComparatorRegion';
		elseif($type === self::COUNTRY || $type === 'COUNTRY')
			$result = 'ComparatorCountry';
		else
			throw new ArgumentOutOfRangeException('type');

		return '\Bitrix\Sale\Location\\'.$result;
	}

	public static function isEntityEqual($entity1, $entity2, $type = '')
	{
		if(strlen($type) > 0)
		{
			/** @var Comparator  $comparator */
			$comparator = self::getConcreteComparatorClassaName($type);
			return 	$comparator::isEntityEqual($entity1, $entity2);
		}

		if(is_array($entity1) && !empty($entity1['NAME']))
		{
			$entity1N = array('NAME' => $entity1['NAME']);
			$entity1N['TYPE'] = !empty($entity1['TYPE']) ? $entity1['TYPE'] : '';
		}
		else
		{
			$entity1N = static::normalize($entity1);
		}

		if(is_array($entity2) && !empty($entity2['NAME']))
		{
			$entity2N = array('NAME' => $entity2['NAME']);
			$entity2N['TYPE'] = !empty($entity2['TYPE']) ? $entity2['TYPE'] : '';
		}
		else
		{
			$entity2N = static::normalize($entity2);
		}

		if(strlen($entity1N['NAME']) > 0 && strlen($entity2N['NAME']) > 0)
			if($entity1N['NAME'] != $entity2N['NAME'])
				return false;

		if(strlen($entity1N['TYPE']) > 0 && strlen($entity2N['TYPE']) > 0)
			if($entity1N['TYPE'] != $entity2N['TYPE'])
				return false;

		return true;
	}

	protected static function getTypes()
	{
		return array();
	}

	protected static function getVariantsValues()
	{
		if(static::$variants === null)
		{
			static::setVariantsValues(array());
		}

		return static::$variants;
	}

	public static function setVariantsValues(array $variants = array())
	{
		static::$variants = $variants;
	}

	public static function setVariants(array $variants = array())
	{
		foreach($variants as $type => $v)
		{
			/** @var Comparator  $comparator */
			$comparator = self::getConcreteComparatorClassaName($type);
			$comparator::setVariantsValues(
				self::normalizeVariants($v)
			);
		}
	}

	public static function flatten($value)
	{
		$result = preg_replace('/\s*(\(.*\))/i'.BX_UTF_PCRE_MODIFIER, ' ', $value);
		$result = preg_replace('/[~\'\"\`\!\@\#\$\%\^\&\*\+\=\\\.\,\?\:\;\{\}\[\]\-]/i'.BX_UTF_PCRE_MODIFIER, ' ', $result);
		$result = preg_replace('/\s{2,}/i'.BX_UTF_PCRE_MODIFIER, ' ', $result);
		$result = str_replace('Ё', 'Е', $result);
		$result = ToUpper($result);
		$result = trim($result);

		return $result;
	}

	protected static function normalizeVariants(array $variants)
	{
		$result = array();

		foreach($variants as $k => $v)
			$result[self::flatten($k)] = self::flatten($v);

		return $result;
	}

	public static function normalizeEntity($name, $type)
	{
		/** @var Comparator $comparator */
		$comparator = self::getConcreteComparatorClassaName($type);
		return $comparator::normalize($name);
	}

	// Gadyukino d. | Derevnya Gadyukino  => array( 'NAME' => 'Gadykino', 'TYPE' => 'DEREVNYA'
	protected static function normalize($name)
	{
		$name = self::flatten($name);

		if(strlen($name) <= 0)
			return array('NAME' => '', 'TYPE' => '');

		$matches = array();
		$types = static::getTypes();
		$resultType = '';
		$variants = static::getVariantsValues();

		foreach($variants as $wrong => $correct)
		{
			if($name == self::flatten($wrong))
			{
				$name = $correct;
				break;
			}
		}

		foreach($types as $type => $search)
		{
			if(!is_array($search))
				continue;

			$search[] = $type;

			foreach($search as $s)
			{
				$regexp = '';
				$s = self::flatten($s);

				if(strpos($name, $s.' ') !== false)
					$regexp = '/^'.$s.'\s+(.*)$/i'.BX_UTF_PCRE_MODIFIER;
				elseif(strpos($name, ' '.$s) !== false)
					$regexp = '/^(.*)\s+'.$s.'$/i'.BX_UTF_PCRE_MODIFIER;

				if(strlen($regexp) > 0 && preg_match($regexp, $name, $matches))
				{
					$name = $matches[1];
					$resultType = $type;
					break 2;
				}
			}
		}

		return array(
			'NAME' => $name,
			'TYPE' => $resultType
		);
	}

	public static function getLocalityNamesArray($name, $type)
	{
		if(strlen($name) <= 0)
			return array();

		$result = array();
		$types = Replacement::getLocalityTypes();

		if(strlen($type) > 0)
		{
			$result[] = ToUpper($type.' '.$name);
			$result[] = ToUpper($name.' '.$type);

			if(is_array($types[$type]) && !empty($types[$type]))
			{
				foreach($types[$type] as $t)
				{
					$result[] = ToUpper($t.' '.$name);
					$result[] = ToUpper($name.' '.$t);
				}
			}
		}
		else
		{
			foreach($types as $k => $v)
			{
				$result[] = ToUpper($k.' '.$name);
				$result[] = ToUpper($name.' '.$k);

				if(is_array($v) && !empty($v))
				{
					foreach($v as $vv)
					{
						$result[] = ToUpper($vv.' '.$name);
						$result[] = ToUpper($name.' '.$vv);
					}
				}
			}
		}

		return $result;
	}
}

class ComparatorLocality extends Comparator
{
	public static $variants = null;

	protected static function getTypes()
	{
		return Replacement::getLocalityTypes();
	}
}

class ComparatorDistrict extends Comparator
{
	public static $variants = null;

	protected static function getTypes()
	{
		return Replacement::getDistrictTypes();
	}
}

class ComparatorRegion extends Comparator
{
	public static $variants = null;

	protected static function getTypes()
	{
		return Replacement::getRegionTypes();
	}

	public static function setVariantsValues(array $variants = array())
	{
		static::$variants = static::normalizeVariants(
			array_merge(
				Replacement::getRegionVariants(),
				$variants
			)
		);
	}
}

class ComparatorCountry extends Comparator
{
	public static $variants = null;

	public static function setVariantsValues(array $variants = array())
	{
		static::$variants = static::normalizeVariants(
			array_merge(
				Replacement::getCountryVariants(),
				$variants
			)
		);
	}
}