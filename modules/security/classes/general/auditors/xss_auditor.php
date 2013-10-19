<?php

class CSecurityFilterXssAuditor extends CSecurityFilterBaseAuditor
{
	private $whiteList = array();

	protected $name = "XSS";

	/**
	 * @param string $pString
	 * @return bool
	 */
	public function process($pString)
	{
		if(!preg_match("/[(){}\\[\\]=+&%<>]?/", $pString))
			return false;

		$this->lazyLoadFilters();
		$this->lazyLoadWhitelist();
		$this->setValidString("");
		$found = false;

		$str1 = $this->processWhiteList($pString, "store");

		$str2 = "";
		$strX = $str1;
		while($str2 <> $strX)
		{
			foreach($this->filters as $searchChar => $filters)
			{
				if($searchChar === '' || $searchChar === 0 || strpos($strX, $searchChar) !== false)
				{
					$str2 = $strX;
					$strX = preg_replace($filters[1], $filters[0], $str2);
				}
			}
		}

		if($str2 <> $str1)
		{
			$str2 = $this->processWhiteList($str2, "restore");
			$this->setValidString($str2);
			$found = true;
		} 

		return $found;
	}


	/**
	 *
	 */
	protected function lazyLoadWhitelist()
	{
		if(!$this->whiteList)
		{
			$this->whiteList = $this->getWhiteList();
		}
	}


	/**
	 * @return array
	 */
	protected function getFilters()
	{
		$_M = '(?:[\x09\x0a\x0d\\\\]*)';
		$_M3 = '(?:[\x09\x0a\x0d\\\\\s]*)';
		$_M2 = '(?:(?:[\x09\x0a\x0d\\\\\s]|(?:\/\*.*?\*\/))*)';

		$_Al = '(?<![a-z0-9&_?])';

		$_Jj = "(?:j|(?:\\\\0*[64]a))";
		$_Ja = "(?:a|(?:\\\\0*[64]1))";
		$_Jb = "(?:b|(?:\\\\0*[64]2))";

		$_Jv = "(?:v|(?:\\\\0*[75]6))";
		$_Js = "(?:s|(?:\\\\0*[75]3))";
		$_Jc = "(?:c|(?:\\\\0*[64]3))";
		$_Jr = "(?:r|(?:\\\\0*[75]2))";
		$_Ji = "(?:i|(?:\\\\0*[64]9))";
		$_Jp = "(?:p|(?:\\\\0*[75]0))";
		$_Jt = "(?:t|(?:\\\\0*[75]4))";

		$_Je = "(?:e|(?:\\\\0*[64]5))";
		$_Jx = "(?:x|(?:\\\\0*[75]8))";
		$_Jo = "(?:o|(?:\\\\0*[64]f))";
		$_Jn = "(?:n|(?:\\\\0*[64]e))";

		$_Jm = "(?:m|(?:\\\\0*[64]d))";

		$_Jh = "(?:h|(?:\\\\0*[64]8))";

		$_Jgav = "(?:@|(?:\\\\0*40))";

		$_Jdd = "(?:\\:|=|(?:\\\\0*3a)|(?:\\\\0*3d))";
		$_Jss = "(?:\\(|(?:\\\\0*28))";

		$_Jvopr = "(?:\\?|(?:\\\\0*3f))";
		$_Jgalka = "(?:\\<|(?:\\\\0*3c))";

		$_WS_OPT = "[\\x00\\x09\\x0A\\x0B\\x0C\\x0D\\s\\\\]*"; //not modified

		$filters = array(
			0 => array($this->getSplittingString(2, " * "), array(//space is not enought
				"/({$_Jb}{$_M}{$_Je}{$_M}{$_Jh}{$_M})({$_Ja}{$_M}{$_Jv}{$_M}{$_Ji}{$_M}{$_Jo}{$_M}{$_Jr}{$_WS_OPT}{$_Jdd})/is",
				"/({$_Jgav}{$_M}{$_Ji}{$_M}{$_Jm})({$_M}{$_Jp}{$_M}{$_Jo}{$_M}{$_Jr}{$_M}{$_Jt})/",
				"/({$_Jgalka}{$_Jvopr}{$_M}{$_Ji}{$_M})({$_Jm}{$_M}{$_Jp}{$_M}{$_Jo}{$_M}{$_Jr}{$_M}{$_Jt})/is",
				"/({$_Jj}{$_M3}{$_Ja}{$_M3}{$_Jv}{$_M3})({$_Ja}{$_M3}{$_Js}{$_M3}{$_Jc}{$_M3}{$_Jr}{$_M3}{$_Ji}{$_M3}{$_Jp}{$_M3}{$_Jt}{$_M3}{$_Jdd})/is",
				"/({$_Jv}{$_M3}{$_Jb}{$_M3})({$_Js}{$_M3}{$_Jc}{$_M3}{$_Jr}{$_M3}{$_Ji}{$_M3}{$_Jp}{$_M3}{$_Jt}{$_M3}{$_Jdd})/is",
				"/({$_Je}{$_M2}{$_Jx}{$_M2})({$_Jp}{$_M2}{$_Jr}{$_M2}{$_Je}{$_M2}{$_Js}{$_M2}{$_Js}{$_M2}{$_Ji}{$_M2}{$_Jo}{$_M2}{$_Jn}{$_M2}{$_Jss})/is",
			)),

			"<" => array($this->getSplittingString(2), array(
				"/(\<{$_M}\!{$_M}D{$_M}O{$_M})(C{$_M}T{$_M}Y{$_M}P{$_M}E)/is",
				"/(\<{$_M}\!{$_M}E{$_M}N{$_M})(T{$_M}I{$_M}T{$_M}Y)/is",
				"/(\<{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
				"/(\<{$_M}\\/{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
				"/(\<{$_M}x{$_M}:{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
				"/(\<{$_M}a{$_M}p{$_M}p{$_M})(l{$_M}e{$_M}t)/is",
				"/(\<{$_M}e{$_M}m{$_M}b)(e{$_M}d)/is",
				"/(\<{$_M}s{$_M}t{$_M})(y{$_M}l{$_M}e)/is",
				"/(\<{$_M}f{$_M}r{$_M}a{$_M})(m{$_M}e)/is",
				"/(\<{$_M}i{$_M}f{$_M}r{$_M})(a{$_M}m{$_M}e)/is",
				"/(\<{$_M}f{$_M}o{$_M})(r{$_M}m)/is",
				//"/(\.{$_M}c{$_M}o{$_M})(o{$_M}k{$_M}i{$_M}e)/is",
				"/(\<{$_M}o{$_M}b{$_M})(j{$_M}e{$_M}c{$_M}t)/is",
				"/(\<{$_M}l{$_M}i{$_M})(n{$_M}k)/is",
				"/(\<{$_M}m{$_M}e{$_M}t)({$_M}a)/is",
				"/(\<{$_M}L{$_M}A{$_M}Y{$_M})(E{$_M}R)/is",
				"/(\<{$_M}h{$_M}t{$_M})(m{$_M}l)/is",
				"/(\<{$_M}x{$_M}m{$_M})(l)/is",
				"/(\<{$_M}b{$_M}a{$_M})(s{$_M}e)/is",
				"/(\<{$_M}s{$_M}v{$_M})(g)/is",
			)),

			"=" => array($this->getSplittingString(2), array(
				"/([\W]s{$_M}t{$_M})(y{$_M}l{$_M}e{$_WS_OPT}\=)
					(?!\\s*
						(?P<quot>\"|&quot;|')
						(\\s*[a-z-]+\\s*:\\s*([0-9a-z\\s%,.#!\-'\"]+|rgb\\s*\\([0-9,\\s]+\\))\\s*;?)*
						\\s*
						(?P=quot)
					)
				/xis",
				"/(f{$_M}o{$_M}r{$_M})(m{$_M}a{$_M}c{$_M}t{$_M}i{$_M}o{$_M}n{$_WS_OPT}\=)/is",
				"/{$_Al}(o{$_M}n{$_M})(([a-z]{$_M}){3,}{$_WS_OPT}\=)/is"
			)),

			":" => array($this->getSplittingString(2), array(
				"/(u{$_M}r{$_M}n{$_M2}\:{$_M2}s{$_M})(c{$_M}h{$_M}e{$_M}m{$_M}a{$_M}s{$_M}\-{$_M}m{$_M}i{$_M}c{$_M}r{$_M}o{$_M}s{$_M}o{$_M}f{$_M}t{$_M}\-{$_M}c{$_M}o{$_M}m{$_M2}\:)/",
				"/{$_Al}(d{$_M}a{$_M}t{$_M})(a{$_M}\:)(?![0-9]|image)/is",
			)),

			"-" => array($this->getSplittingString(2), array(
				"/(\-{$_M}m{$_M}o{$_M}z{$_M}\-{$_M}b{$_M}i{$_M})(n{$_M}d{$_M}i{$_M}n{$_M}g{$_M}{$_WS_OPT}\:{$_WS_OPT}{$_M}u{$_M}r{$_M}l)/is",
			)),

		);

		return $filters;
	}


	/**
	 * @return array
	 */
	protected function getWhiteList()
	{
		$safe_replacement = md5(mt_rand());
		$whitelist = array(
			//video player insertion
			array(
				'store_match' => '#(<script)(\\s+type="text/javascript"\\s+src="/bitrix/components/bitrix/player/wmvplayer/(silverlight|wmvplayer).js"[\\s/]*></script>)#s',
				'store_replacement' => '<'.$safe_replacement.'\\2',
				'restore_match' => '#<'.$safe_replacement.'#',
				'restore_replacement' => '<script',
			),
			array(
				'store_match' => '#(<script)(\\s+type\\s*=\\s*"text/javascript"\\s*>\\s*new\\s+jeroenwijering\\.Player\\(\\s*document\\.getElementById\\(\\s*"[a-zA-Z0-9_]+"\\s*\\)\\s*,\\s*"/bitrix/components/bitrix/player/wmvplayer/wmvplayer.xaml"\\s*,\\s*{\\s*(?:[a-zA-Z0-9_]+:\\s+"[a-zA-Z0-9/.]*?"[,\\s]*)*}\\);</script>)#s',
				'store_replacement' => '<'.$safe_replacement.'\\2',
				'restore_match' => '#<'.$safe_replacement.'#',
				'restore_replacement' => '<script',
			),
			array(
				'store_match' => '#(BX\\.WindowManager\\.)(\\d+\\.\\d+)#s',
				'store_replacement' => '_b_x_'.$safe_replacement.'\\2',
				'restore_match' => '#_b_x_'.$safe_replacement.'#',
				'restore_replacement' => 'BX.WindowManager.',
			),
			//AJAX part of the component
			array(
				'store_match' => '#sale\.location\.suggest#s',
				'store_replacement' => '_b_x2_'.$safe_replacement,
				'restore_match' => '#_b_x2_'.$safe_replacement.'#',
				'restore_replacement' => 'sale.location.suggest',
			),
			//more will come
		);

		return $whitelist;
	}

	/**
	 * @param string $pString
	 * @param string $pAction - only "store" or "replace"
	 * @return string
	 */
	protected function processWhiteList($pString, $pAction = "store")
	{
		if( !(is_string($pString) && $pString != "") )
			return "";

		if(!in_array($pAction, array("store", "replace")))
			return $pString;

		$str1="";
		$strY=$pString;
		while($str1 <> $strY)
		{
			$str1 = $strY;
			foreach($this->whiteList as $arWhiteListElement)
			{
				$strY = preg_replace($arWhiteListElement[$pAction."_match"], $arWhiteListElement[$pAction."_replacement"], $strY);
			}
		}
		return $str1;
	}

}
