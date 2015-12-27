<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Security\Filter\Auditor;

/**
 * Xss security auditor
 * Searching XSS like strings, for example: <img src=x onerror=prompt(/Hi_big_guy/)>
 *
 * @package Bitrix\Security\Filter\Auditor
 * @since 14.0.0
 */
class Xss
	extends Base
{
	protected $name = 'XSS';

	private $whiteList = array();

	/**
	 * Process and save filtered value
	 * Return true if value triggered auditor filtration
	 * For get filtered value use Xss::getFilteredValue
	 *
	 * Simple example:
	 * <code>
	 * $ob = new Xss();
	 * if ($ob->process($someValue))
	 *     $someValue = $ob->getFilteredValue();
	 * </code>
	 * @param string $value
	 * @return bool
	 */
	public function process($value)
	{
		if (!preg_match('#[(){}\[\]=+&%<>]?#', $value))
			return false;

		$this->initializeFilters();
		$this->setFilteredValue('');
		$found = false;

		$targetValue = $this->processWhiteList($value, 'store');

		$last = '';
		$current = $targetValue;
		while ($last != $current)
		{
			$last = $current;
			foreach ($this->filters as $searchChar => $filters)
			{
				if ($searchChar && strpos($current, $searchChar) === false)
					continue;

				$current = preg_replace($filters['search'], $filters['replace'], $current);
			}
		}

		if ($last != $targetValue)
		{
			$last = $this->processWhiteList($last, 'restore');
			$this->setFilteredValue($last);
			$found = true;
		} 

		return $found;
	}

	protected function initializeWhiteList()
	{
		if (!$this->whiteList)
		{
			$this->whiteList = $this->getWhiteList();
		}
	}

	protected function getFilters()
	{
		$_M = '(?:[\x09\x0a\x0d\\\\]*)';
		$_M3 = '(?:[\x09\x0a\x0d\\\\\s]*)';
		$_M2 = '(?:(?:[\x09\x0a\x0d\\\\\s]|(?:\/\*.*?\*\/))*)';

		$_Al = '(?<![a-z0-9&_?])';

		$_Jj = '(?:j|(?:\\\\0*[64]a))';
		$_Ja = '(?:a|(?:\\\\0*[64]1))';
		$_Jb = '(?:b|(?:\\\\0*[64]2))';

		$_Jv = '(?:v|(?:\\\\0*[75]6))';
		$_Js = '(?:s|(?:\\\\0*[75]3))';
		$_Jc = '(?:c|(?:\\\\0*[64]3))';
		$_Jr = '(?:r|(?:\\\\0*[75]2))';
		$_Ji = '(?:i|(?:\\\\0*[64]9))';
		$_Jp = '(?:p|(?:\\\\0*[75]0))';
		$_Jt = '(?:t|(?:\\\\0*[75]4))';

		$_Je = '(?:e|(?:\\\\0*[64]5))';
		$_Jx = '(?:x|(?:\\\\0*[75]8))';
		$_Jo = '(?:o|(?:\\\\0*[64]f))';
		$_Jn = '(?:n|(?:\\\\0*[64]e))';

		$_Jh = '(?:h|(?:\\\\0*[64]8))';

		$_Jdd = '(?:\\:|=|(?:\\\\0*3a)|(?:\\\\0*3d))';
		$_Jss = '(?:\\(|(?:\\\\0*28))';

		$_WS_OPT = '[\\x00\\x09\\x0A\\x0B\\x0C\\x0D\\s\\\\]*'; //not modified
		$replacePattern = $this->getSplittingString(2);
		$filters = array(
			0 => array(
				'search' => array(
					"/({$_Jb}{$_M}{$_Je}{$_M}{$_Jh}{$_M})({$_Ja}{$_M}{$_Jv}{$_M}{$_Ji}{$_M}{$_Jo}{$_M}{$_Jr}{$_WS_OPT}{$_Jdd})/is",
					"/({$_Jj}{$_M3}{$_Ja}{$_M3}{$_Jv}{$_M3})({$_Ja}{$_M3}{$_Js}{$_M3}{$_Jc}{$_M3}{$_Jr}{$_M3}{$_Ji}{$_M3}{$_Jp}{$_M3}{$_Jt}{$_M3}{$_Jdd})/is",
					"/({$_Jv}{$_M3}{$_Jb}{$_M3})({$_Js}{$_M3}{$_Jc}{$_M3}{$_Jr}{$_M3}{$_Ji}{$_M3}{$_Jp}{$_M3}{$_Jt}{$_M3}{$_Jdd})/is",
					"/({$_Je}{$_M2}{$_Jx}{$_M2})({$_Jp}{$_M2}{$_Jr}{$_M2}{$_Je}{$_M2}{$_Js}{$_M2}{$_Js}{$_M2}{$_Ji}{$_M2}{$_Jo}{$_M2}{$_Jn}{$_M2}{$_Jss})/is",
				),
				'replace' => $this->getSplittingString(2, " * ")
			),

			"<" => array(
				'search' => array(
					"/(<{$_M}!{$_M}D{$_M}O{$_M})(C{$_M}T{$_M}Y{$_M}P{$_M}E)/is",
					"/(<{$_M}!{$_M}E{$_M}N{$_M})(T{$_M}I{$_M}T{$_M}Y)/is",
					"/(<{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
					"/(<{$_M}\\/{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
					"/(<{$_M}x{$_M}:{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
					"/(<{$_M}a{$_M}p{$_M}p{$_M})(l{$_M}e{$_M}t)/is",
					"/(<{$_M}e{$_M}m{$_M}b)(e{$_M}d)/is",
					"/(<{$_M}s{$_M}t{$_M})(y{$_M}l{$_M}e)/is",
					"/(<{$_M}f{$_M}r{$_M}a{$_M})(m{$_M}e)/is",
					"/(<{$_M}i{$_M}f{$_M}r{$_M})(a{$_M}m{$_M}e)/is",
					"/(<{$_M}f{$_M}o{$_M})(r{$_M}m)/is",
					//"/(\.{$_M}c{$_M}o{$_M})(o{$_M}k{$_M}i{$_M}e)/is",
					"/(<{$_M}o{$_M}b{$_M})(j{$_M}e{$_M}c{$_M}t)/is",
					"/(<{$_M}l{$_M}i{$_M})(n{$_M}k)/is",
					"/(<{$_M}m{$_M}e{$_M}t)({$_M}a)/is",
					"/(<{$_M}L{$_M}A{$_M}Y{$_M})(E{$_M}R)/is",
					"/(<{$_M}h{$_M}t{$_M})(m{$_M}l)/is",
					"/(<{$_M}x{$_M}m{$_M})(l)/is",
					"/(<{$_M}b{$_M}a{$_M})(s{$_M}e)/is",
					"/(<{$_M}s{$_M}v{$_M})(g)/is",
					"/(<!--'start_frame)(_cache_)/is",
					"/(<!--'end_frame)(_cache_)/is"
				),
				'replace' => $replacePattern
			),

			"=" => array(
				'search' => array(
					"/{$_Al}(s{$_M}t{$_M})(y{$_M}l{$_M}e{$_WS_OPT}\=)
						(?!\\s*
							(?P<quot>\"|&quot;|')
							(\\s*[a-z-]+\\s*:\\s*(([0-9a-z\\s%,.#!\-'\"\\/]+|&quot;)*|rgb\\s*\\([0-9,\\s]+\\))\\s*(?:!important)?;?)*
							\\s*
							(?P=quot)
						)
					/xis",
					"/{$_Al}(f{$_M}o{$_M}r{$_M})(m{$_M}a{$_M}c{$_M}t{$_M}i{$_M}o{$_M}n{$_WS_OPT}=)/is",
					"/{$_Al}(o{$_M}n{$_M}(?:[a-z]{$_M})*?)(([a-z]{$_M}){3}{$_WS_OPT}=)/is"
				),
				'replace' => $replacePattern
			),

			":" => array(
				'search' => array(
					"/(u{$_M}r{$_M}n{$_M2}:{$_M2}s{$_M})(c{$_M}h{$_M}e{$_M}m{$_M}a{$_M}s{$_M}-{$_M}m{$_M}i{$_M}c{$_M}r{$_M}o{$_M}s{$_M}o{$_M}f{$_M}t{$_M}-{$_M}c{$_M}o{$_M}m{$_M2}:)/",
					"/((?:'|\"|=|:){$_M3}d{$_M}a{$_M}t{$_M})(a{$_M}:)(?![0-9]|image)/is",
				),
				'replace' => $replacePattern
			),

			"-" => array(
				'search' => array(
					"/(-{$_M}m{$_M}o{$_M}z{$_M}-{$_M}b{$_M}i{$_M})(n{$_M}d{$_M}i{$_M}n{$_M}g{$_M}{$_WS_OPT}:{$_WS_OPT}{$_M}u{$_M}r{$_M}l)/is",
				),
				'replace' => $replacePattern
			),

		);

		return $filters;
	}


	/**
	 * @return array
	 */
	protected function getWhiteList()
	{
		$safeReplacement = md5(mt_rand());
		return array(
			//video player insertion
			array(
				'store_match' => '#(<script)(\\s+type="text/javascript"\\s+src="/bitrix/components/bitrix/player/wmvplayer/(silverlight|wmvplayer).js"[\\s/]*></script>)#s',
				'store_replacement' => '<'.$safeReplacement.'\\2',
				'restore_match' => '#<'.$safeReplacement.'#',
				'restore_replacement' => '<script',
			),
			array(
				'store_match' => '#(<script)(\\s+type\\s*=\\s*"text/javascript"\\s*>\\s*new\\s+jeroenwijering\\.Player\\(\\s*document\\.getElementById\\(\\s*"[a-zA-Z0-9_]+"\\s*\\)\\s*,\\s*"/bitrix/components/bitrix/player/wmvplayer/wmvplayer.xaml"\\s*,\\s*{\\s*(?:[a-zA-Z0-9_]+:\\s+"[a-zA-Z0-9/.]*?"[,\\s]*)*}\\);</script>)#s',
				'store_replacement' => '<'.$safeReplacement.'\\2',
				'restore_match' => '#<'.$safeReplacement.'#',
				'restore_replacement' => '<script',
			)
		);
	}

	/**
	 * @param string $value
	 * @param string $action - only 'store' or 'replace'
	 * @return string
	 */
	protected function processWhiteList($value, $action = 'store')
	{
		if (!is_string($value) || !$value)
			return '';

		if (!in_array($action, array('store', 'replace')))
			return $value;

		$this->initializeWhiteList();
		$result = '';
		while ($result != $value)
		{
			$result = $value;
			foreach ($this->whiteList as $whiteListElement)
			{
				$result = preg_replace($whiteListElement[$action.'_match'], $whiteListElement[$action.'_replacement'], $value);
			}
		}
		return $result;
	}

}
