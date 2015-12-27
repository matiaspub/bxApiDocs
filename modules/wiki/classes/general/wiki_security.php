<?

IncludeModuleLangFile(__FILE__);


/**
 * <b>CWikiSecurity</b> - Класс защищающий вики-страницу от потенциально опасных элементов. Динамичный метод. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisecurity/index.php
 * @author Bitrix
 */
class CWikiSecurity
{
	var $_filters = false;

	public function __construct($char = false)
	{
		if($char === false)
			$char = " ";

		$_M='(?:[\x09\x0a\x0d\\\\]*)';
		$_M3='(?:[\x09\x0a\x0d\\\\\s]*)';
		$_M2='(?:(?:[\x09\x0a\x0d\\\\\s]|(?:\/\*.*?\*\/))*)';

		$_Jj ="(?:j|(?:\\\\0*[64]a))";
		$_Ja ="(?:a|(?:\\\\0*[64]1))";
		$_Jb ="(?:b|(?:\\\\0*[64]2))";

		$_Jv ="(?:v|(?:\\\\0*[75]6))";
		$_Js ="(?:s|(?:\\\\0*[75]3))";
		$_Jc ="(?:c|(?:\\\\0*[64]3))";
		$_Jr ="(?:r|(?:\\\\0*[75]2))";
		$_Ji ="(?:i|(?:\\\\0*[64]9))";
		$_Jp ="(?:p|(?:\\\\0*[75]0))";
		$_Jt ="(?:t|(?:\\\\0*[75]4))";

		$_Je ="(?:e|(?:\\\\0*[64]5))";
		$_Jx ="(?:x|(?:\\\\0*[75]8))";
		$_Jo ="(?:o|(?:\\\\0*[64]f))";
		$_Jn ="(?:n|(?:\\\\0*[64]e))";

		$_Jm ="(?:m|(?:\\\\0*[64]d))";

		$_Jh ="(?:h|(?:\\\\0*[64]8))";

		$_Jgav ="(?:@|(?:\\\\0*40))";

		$_Jdd="(?:\\:|=|(?:\\\\0*3a)|(?:\\\\0*3d))";
		$_Jss="(?:\\(|(?:\\\\0*28))";

		$_Jvopr="(?:\\?|(?:\\\\0*3f))";
		$_Jgalka="(?:\\<|(?:\\\\0*3c))";

		$_WS_OPT = "[\\x00\\x09\\x0A\\x0B\\x0C\\x0D\\s\\\\]*";

		if(!$this->_filters)
		{
			$this->_filters = array(
				"" => array("\\1 * \\2" /*space is not enought*/, array(
				"/({$_Jb}{$_M}{$_Je}{$_M}{$_Jh}{$_M})({$_Ja}{$_M}{$_Jv}{$_M}{$_Ji}{$_M}{$_Jo}{$_M}{$_Jr}{$_WS_OPT}{$_Jdd})/is",
				"/({$_Jgav}{$_M}{$_Ji}{$_M}{$_Jm})({$_M}{$_Jp}{$_M}{$_Jo}{$_M}{$_Jr}{$_M}{$_Jt})/",
				"/({$_Jgalka}{$_Jvopr}{$_M}{$_Ji}{$_M})({$_Jm}{$_M}{$_Jp}{$_M}{$_Jo}{$_M}{$_Jr}{$_M}{$_Jt})/is",
				"/({$_Jj}{$_M3}{$_Ja}{$_M3}{$_Jv}{$_M3})({$_Ja}{$_M3}{$_Js}{$_M3}{$_Jc}{$_M3}{$_Jr}{$_M3}{$_Ji}{$_M3}{$_Jp}{$_M3}{$_Jt}{$_M3}{$_Jdd})/is",
				"/({$_Jv}{$_M3}{$_Jb}{$_M3})({$_Js}{$_M3}{$_Jc}{$_M3}{$_Jr}{$_M3}{$_Ji}{$_M3}{$_Jp}{$_M3}{$_Jt}{$_M3}{$_Jdd})/is",
				"/({$_Je}{$_M2}{$_Jx}{$_M2})({$_Jp}{$_M2}{$_Jr}{$_M2}{$_Je}{$_M2}{$_Js}{$_M2}{$_Js}{$_M2}{$_Ji}{$_M2}{$_Jo}{$_M2}{$_Jn}{$_M2}{$_Jss})/is",
				)),

				"<" => array("\\1{$char}\\2", array(
				"/(\<{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
				"/(\<{$_M}x{$_M}:{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t)/is",
				"/(\<{$_M}a{$_M}p{$_M}p{$_M})(l{$_M}e{$_M}t)/is",
				"/(\<{$_M}e{$_M}m{$_M}b)(e{$_M}d)/is",
				"/(\<{$_M}s{$_M}t{$_M})(y{$_M}l{$_M}e)/is",
				"/(\<{$_M}f{$_M}r{$_M}a{$_M})(m{$_M}e)/is",
				"/(\<{$_M}i{$_M}f{$_M}r{$_M})(a{$_M}m{$_M}e)/is",
				"/(\<{$_M}f{$_M}o{$_M})(r{$_M}m)/is",
				"/(\.{$_M}c{$_M}o{$_M})(o{$_M}k{$_M}i{$_M}e)/is",
				"/(\<{$_M}o{$_M}b{$_M})(j{$_M}e{$_M}c{$_M}t)/is",
				"/(\<{$_M}l{$_M}i{$_M})(n{$_M}k)/is",
				"/(\<{$_M}m{$_M}e{$_M}t)({$_M}a)/is",
				"/(\<{$_M}L{$_M}A{$_M}Y{$_M})(E{$_M}R)/is",
				"/(\<{$_M}h{$_M}t{$_M})(m{$_M}l)/is",
				"/(\<{$_M}x{$_M}m{$_M})(l)/is",
				"/(\<{$_M}b{$_M}a{$_M})(s{$_M}e)/is",
				)),

				"=" => array("\\1{$char}\\2", array(
				"/([\W]s{$_M}t{$_M})(y{$_M}l{$_M}e{$_WS_OPT}\=)(?!\\s*\"(\\s*[a-z-]+\\s*:\\s*([0-9a-z\\s%,.#-]+|rgb\\s*\\([0-9,\\s]+\\))\\s*;{0,1}){0,}\\s*\")(?!\\s*&quot;(\\s*[a-z-]+\\s*:\\s*([0-9a-z\\s%,.#-]+|rgb\\s*\\([0-9,\\s]+\\))\\s*;{0,1}){0,}\\s*&quot;)/is",
				"/(f{$_M}o{$_M}r{$_M})(m{$_M}a{$_M}c{$_M}t{$_M}i{$_M}o{$_M}n{$_WS_OPT}\=)/is",

				"/(o{$_M}n{$_M}A{$_M})(b{$_M}o{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}B{$_M})(l{$_M}u{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}C{$_M})(h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}C{$_M})(l{$_M}i{$_M}c{$_M}k{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}D{$_M})(b{$_M}l{$_M}C{$_M}l{$_M}i{$_M}c{$_M}k{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}E{$_M})(r{$_M}r{$_M}o{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}F{$_M})(o{$_M}c{$_M}u{$_M}s{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}K{$_M})(e{$_M}y{$_M}D{$_M}o{$_M}w{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}K{$_M})(e{$_M}y{$_M}P{$_M}r{$_M}e{$_M}s{$_M}s{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}K{$_M})(e{$_M}y{$_M}U{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}L{$_M})(o{$_M}a{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}M{$_M})(o{$_M}u{$_M}s{$_M}e{$_M}D{$_M}o{$_M}w{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}M{$_M})(o{$_M}u{$_M}s{$_M}e{$_M}M{$_M}o{$_M}v{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}M{$_M})(o{$_M}u{$_M}s{$_M}e{$_M}O{$_M}u{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}M{$_M})(o{$_M}u{$_M}s{$_M}e{$_M}O{$_M}v{$_M}e{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}M{$_M})(o{$_M}u{$_M}s{$_M}e{$_M}U{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}M{$_M})(o{$_M}v{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}R{$_M})(e{$_M}s{$_M}e{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}R{$_M})(e{$_M}s{$_M}i{$_M}z{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}S{$_M})(e{$_M}l{$_M}e{$_M}c{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}S{$_M})(u{$_M}b{$_M}m{$_M}i{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}U{$_M})(n{$_M}l{$_M}o{$_M}a{$_M}d{$_WS_OPT}\=)/is",

				"/(o{$_M}n{$_M}m{$_M}o{$_M})(u{$_M}s{$_M}e{$_M}l{$_M}e{$_M}a{$_M}v{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}o{$_M}u{$_M})(s{$_M}e{$_M}e{$_M}n{$_M}t{$_M}e{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}e{$_M}l{$_M})(e{$_M}c{$_M}t{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}e{$_M}l{$_M})(e{$_M}c{$_M}t{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",

				"/(o{$_M}n{$_M}a{$_M}f{$_M})(t{$_M}e{$_M}r{$_M}p{$_M}r{$_M}i{$_M}n{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}p{$_M}r{$_M}i{$_M}n{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}o{$_M}n{$_M}l{$_M}o{$_M}a{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}h{$_M}a{$_M})(s{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}e{$_M})(s{$_M}s{$_M}a{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}o{$_M}f{$_M})(f{$_M}l{$_M}i{$_M}n{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}o{$_M}n{$_M})(l{$_M}i{$_M}n{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}a{$_M})(g{$_M}e{$_M}h{$_M}i{$_M}d{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}a{$_M})(g{$_M}e{$_M}s{$_M}h{$_M}o{$_M}w{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}o{$_M})(p{$_M}s{$_M}t{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(d{$_M}o{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}t{$_M})(o{$_M}r{$_M}a{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}u{$_M}n{$_M})(d{$_M}o{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}o{$_M})(n{$_M}t{$_M}e{$_M}x{$_M}t{$_M}m{$_M}e{$_M}n{$_M}u{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}f{$_M}o{$_M})(r{$_M}m{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}f{$_M}o{$_M})(r{$_M}m{$_M}i{$_M}n{$_M}p{$_M}u{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}i{$_M}n{$_M})(p{$_M}u{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}i{$_M}n{$_M})(v{$_M}a{$_M}l{$_M}i{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(a{$_M}g{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(a{$_M}g{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(a{$_M}g{$_M}e{$_M}n{$_M}t{$_M}e{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(a{$_M}g{$_M}l{$_M}e{$_M}a{$_M}v{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(a{$_M}g{$_M}o{$_M}v{$_M}e{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(a{$_M}g{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}r{$_M})(o{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}o{$_M})(u{$_M}s{$_M}e{$_M}w{$_M}h{$_M}e{$_M}e{$_M}l{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}c{$_M})(r{$_M}o{$_M}l{$_M}l{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}a{$_M})(n{$_M}p{$_M}l{$_M}a{$_M}y{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}a{$_M})(n{$_M}p{$_M}l{$_M}a{$_M}y{$_M}t{$_M}h{$_M}r{$_M}o{$_M}u{$_M}g{$_M}h{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}u{$_M})(r{$_M}a{$_M}t{$_M}i{$_M}o{$_M}n{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}e{$_M}m{$_M})(p{$_M}t{$_M}i{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}e{$_M}n{$_M})(d{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}l{$_M}o{$_M})(a{$_M}d{$_M}e{$_M}d{$_M}d{$_M}a{$_M}t{$_M}a{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}l{$_M}o{$_M})(a{$_M}d{$_M}e{$_M}d{$_M}m{$_M}e{$_M}t{$_M}a{$_M}d{$_M}a{$_M}t{$_M}a{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}l{$_M}o{$_M})(a{$_M}d{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}a{$_M})(u{$_M}s{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}l{$_M})(a{$_M}y{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}l{$_M})(a{$_M}y{$_M}i{$_M}n{$_M}g{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}r{$_M})(o{$_M}g{$_M}r{$_M}e{$_M}s{$_M}s{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}a{$_M})(t{$_M}e{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(a{$_M}d{$_M}y{$_M}s{$_M}t{$_M}a{$_M}t{$_M}e{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}e{$_M})(e{$_M}k{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}e{$_M})(e{$_M}k{$_M}i{$_M}n{$_M}g{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}t{$_M})(a{$_M}l{$_M}l{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}u{$_M})(s{$_M}p{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}i{$_M})(m{$_M}e{$_M}u{$_M}p{$_M}d{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}v{$_M}o{$_M})(l{$_M}u{$_M}m{$_M}e{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}a{$_M})(i{$_M}t{$_M}i{$_M}n{$_M}g{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}i{$_M})(m{$_M}e{$_M}e{$_M}r{$_M}r{$_M}o{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}e{$_M}n{$_M})(d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(g{$_M}i{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}e{$_M})(d{$_M}i{$_M}a{$_M}c{$_M}o{$_M}m{$_M}p{$_M}l{$_M}e{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}e{$_M})(d{$_M}i{$_M}a{$_M}l{$_M}o{$_M}a{$_M}d{$_M}f{$_M}a{$_M}i{$_M}l{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}e{$_M})(d{$_M}i{$_M}a{$_M}s{$_M}l{$_M}i{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(p{$_M}e{$_M}a{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(s{$_M}u{$_M}m{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(s{$_M}y{$_M}n{$_M}c{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(v{$_M}e{$_M}r{$_M}s{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}c{$_M})(r{$_M}i{$_M}p{$_M}t{$_M}c{$_M}o{$_M}m{$_M}m{$_M}a{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}e{$_M})(d{$_M}i{$_M}a{$_M}e{$_M}r{$_M}r{$_M}o{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}o{$_M}u{$_M})(t{$_M}o{$_M}f{$_M}s{$_M}y{$_M}n{$_M}c{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}e{$_M})(e{$_M}k{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}y{$_M})(n{$_M}c{$_M}r{$_M}e{$_M}s{$_M}t{$_M}o{$_M}r{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}r{$_M})(a{$_M}c{$_M}k{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}u{$_M}r{$_M})(l{$_M}f{$_M}l{$_M}i{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}t{$_M})(a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}a{$_M}c{$_M})(t{$_M}i{$_M}v{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}a{$_M}f{$_M})(t{$_M}e{$_M}r{$_M}u{$_M}p{$_M}d{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}a{$_M}c{$_M}t{$_M}i{$_M}v{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}c{$_M}o{$_M}p{$_M}y{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}c{$_M}u{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}d{$_M}e{$_M}a{$_M}c{$_M}t{$_M}i{$_M}v{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}e{$_M}d{$_M}i{$_M}t{$_M}f{$_M}o{$_M}c{$_M}u{$_M}s{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}p{$_M}a{$_M}s{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}u{$_M}n{$_M}l{$_M}o{$_M}a{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}e{$_M})(f{$_M}o{$_M}r{$_M}e{$_M}u{$_M}p{$_M}d{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}b{$_M}o{$_M})(u{$_M}n{$_M}c{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}e{$_M})(l{$_M}l{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}o{$_M})(n{$_M}t{$_M}r{$_M}o{$_M}l{$_M}s{$_M}e{$_M}l{$_M}e{$_M}c{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}o{$_M})(p{$_M}y{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}c{$_M}u{$_M})(t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}a{$_M})(t{$_M}a{$_M}a{$_M}v{$_M}a{$_M}i{$_M}l{$_M}a{$_M}b{$_M}l{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}a{$_M})(t{$_M}a{$_M}s{$_M}e{$_M}t{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}a{$_M})(t{$_M}a{$_M}s{$_M}e{$_M}t{$_M}c{$_M}o{$_M}m{$_M}p{$_M}l{$_M}e{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}e{$_M})(a{$_M}c{$_M}t{$_M}i{$_M}v{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}e{$_M}r{$_M})(r{$_M}o{$_M}r{$_M}u{$_M}p{$_M}d{$_M}a{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}f{$_M}i{$_M})(l{$_M}t{$_M}e{$_M}r{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}f{$_M}i{$_M})(n{$_M}i{$_M}s{$_M}h{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}f{$_M}o{$_M})(c{$_M}u{$_M}s{$_M}i{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}f{$_M}o{$_M})(c{$_M}u{$_M}s{$_M}o{$_M}u{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}h{$_M}a{$_M})(s{$_M}h{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}h{$_M}e{$_M})(l{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}l{$_M}a{$_M})(y{$_M}o{$_M}u{$_M}t{$_M}c{$_M}o{$_M}m{$_M}p{$_M}l{$_M}e{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}l{$_M}o{$_M})(s{$_M}e{$_M}c{$_M}a{$_M}p{$_M}t{$_M}u{$_M}r{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}o{$_M})(v{$_M}e{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}o{$_M})(v{$_M}e{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}s{$_M})(s{$_M}i{$_M}t{$_M}e{$_M}m{$_M}o{$_M}d{$_M}e{$_M}j{$_M}u{$_M}m{$_M}p{$_M}l{$_M}i{$_M}s{$_M}t{$_M}i{$_M}t{$_M}e{$_M}m{$_M}r{$_M}e{$_M}m{$_M}o{$_M}v{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}m{$_M}s{$_M})(t{$_M}h{$_M}u{$_M}m{$_M}b{$_M}n{$_M}a{$_M}i{$_M}l{$_M}c{$_M}l{$_M}i{$_M}c{$_M}k{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}a{$_M})(g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}a{$_M})(s{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}p{$_M}r{$_M})(o{$_M}p{$_M}e{$_M}r{$_M}t{$_M}y{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(s{$_M}i{$_M}z{$_M}e{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(s{$_M}i{$_M}z{$_M}e{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}o{$_M})(w{$_M}e{$_M}n{$_M}t{$_M}e{$_M}r{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}o{$_M})(w{$_M}e{$_M}x{$_M}i{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}o{$_M})(w{$_M}s{$_M}d{$_M}e{$_M}l{$_M}e{$_M}t{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}o{$_M})(w{$_M}s{$_M}i{$_M}n{$_M}s{$_M}e{$_M}r{$_M}t{$_M}e{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}e{$_M})(l{$_M}e{$_M}c{$_M}t{$_M}i{$_M}o{$_M}n{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}t{$_M})(o{$_M}p{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}s{$_M}t{$_M})(o{$_M}r{$_M}a{$_M}g{$_M}e{$_M}c{$_M}o{$_M}m{$_M}m{$_M}i{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}i{$_M})(m{$_M}e{$_M}o{$_M}u{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}r{$_M}e{$_M})(a{$_M}d{$_M}y{$_M}s{$_M}t{$_M}a{$_M}t{$_M}e{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_M}d{$_WS_OPT}\=)/is",

				"/(o{$_M}n{$_M}s{$_M}e{$_M})(a{$_M}r{$_M}c{$_M}h{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}f{$_M}u{$_M}l{$_M}l{$_M}s{$_M}c{$_M}r{$_M}e{$_M}e{$_M}n{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}z{$_M}o{$_M})(o{$_M}m{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}o{$_M})(u{$_M}c{$_M}h{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}o{$_M})(u{$_M}c{$_M}h{$_M}m{$_M}o{$_M}v{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}o{$_M})(u{$_M}c{$_M}h{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}t{$_M}o{$_M})(u{$_M}c{$_M}h{$_M}c{$_M}a{$_M}n{$_M}c{$_M}e{$_M}l{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}g{$_M}e{$_M})(s{$_M}t{$_M}u{$_M}r{$_M}e{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}g{$_M}e{$_M})(s{$_M}t{$_M}u{$_M}r{$_M}e{$_M}c{$_M}h{$_M}a{$_M}n{$_M}g{$_M}e{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}g{$_M}e{$_M})(s{$_M}t{$_M}u{$_M}r{$_M}e{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}a{$_M}n{$_M}i{$_M}m{$_M}a{$_M}t{$_M}i{$_M}o{$_M}n{$_M}s{$_M}t{$_M}a{$_M}r{$_M}t{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}a{$_M}n{$_M}i{$_M}m{$_M}a{$_M}t{$_M}i{$_M}o{$_M}n{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}a{$_M}n{$_M}i{$_M}m{$_M}a{$_M}t{$_M}i{$_M}o{$_M}n{$_M}i{$_M}t{$_M}e{$_M}r{$_M}a{$_M}t{$_M}i{$_M}o{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}d{$_M}e{$_M})(v{$_M}i{$_M}c{$_M}e{$_M}o{$_M}r{$_M}i{$_M}e{$_M}n{$_M}t{$_M}a{$_M}t{$_M}i{$_M}o{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}t{$_M}r{$_M}a{$_M}n{$_M}s{$_M}i{$_M}t{$_M}i{$_M}o{$_M}n{$_M}e{$_M}n{$_M}d{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}b{$_M}e{$_M}g{$_M}i{$_M}n{$_M}f{$_M}u{$_M}l{$_M}l{$_M}s{$_M}c{$_M}r{$_M}e{$_M}e{$_M}n{$_WS_OPT}\=)/is",
				"/(o{$_M}n{$_M}w{$_M}e{$_M})(b{$_M}k{$_M}i{$_M}t{$_M}e{$_M}n{$_M}d{$_M}f{$_M}u{$_M}l{$_M}l{$_M}s{$_M}c{$_M}r{$_M}e{$_M}e{$_M}n{$_WS_OPT}\=)/is",
				)),

				":" => array("\\1{$char}\\2", array(
				"/(u{$_M}r{$_M}n{$_M2}\:{$_M2}s{$_M})(c{$_M}h{$_M}e{$_M}m{$_M}a{$_M}s{$_M}\-{$_M}m{$_M}i{$_M}c{$_M}r{$_M}o{$_M}s{$_M}o{$_M}f{$_M}t{$_M}\-{$_M}c{$_M}o{$_M}m{$_M2}\:)/",
				"/(d{$_M}a{$_M}t{$_M})(a{$_M}\:)/is",
				)),

				"-" => array("\\1{$char}\\2", array(
				"/(\-{$_M}m{$_M}o{$_M}z{$_M}\-{$_M}b{$_M}i{$_M})(n{$_M}d{$_M}i{$_M}n{$_M}g{$_M}{$_WS_OPT}\:{$_WS_OPT}{$_M}u{$_M}r{$_M}l)/is",
				)),

				"(" => array("\\1{$char}\\2", array(
				"/(f{$_M}r{$_M}o{$_M}m)({$_M}c{$_M}h{$_M}a{$_M}r{$_M}c{$_M}o{$_M}d{$_M}e{$_M3}\()/",
				"/(u{$_M}n{$_M}e{$_M})(s{$_M}c{$_M}a{$_M}p{$_M}e{$_M3}\()/",
				)),
			);
		}
	}

	
	/**
	* <p>Метод обрабатывает содержимое Wiki-страницы.</p>
	*
	*
	* @param string &$str  Содержимое Wiki-страницы </ht
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* $IBLOCK_ID = 2;
	* $NAME = 'Тестовая страница';
	* $arFilter = array(
	* 	'ACTIVE' =&gt; 'Y',
	* 	'CHECK_PERMISSIONS' =&gt; 'N',
	* 	'IBLOCK_ID' =&gt; $IBLOCK_ID
	* );
	* $arElement = CWiki::GetElementByName($NAME, $arFilter);
	* 
	* $CWikiSecurity = new CWikiSecurity ();
	* $CWikiSecurity-&gt;clear($arElement['~DETAIL_TEXT']);
	* echo $arElement['~DETAIL_TEXT'];<br>?&gt;
	* </htm
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiparser/parse.php">CWikiParser::Parse</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisecurity/clear.php
	* @author Bitrix
	*/
	public function clear(&$str)
	{
	    return $this->_dostr($str);
	}

	protected function _dostr(&$str)
	{
		if(preg_match("/^[A-Za-z0-9_.,-]*$/", $str))
			return false;

		$str1="";
		$strY=$str;
		while($str1 <> $strY)
		{
			$str1 = $strY;
			$strY = $this->_decode($strY);
			$strY = str_replace("\x00", "", $strY);
			$strY = preg_replace("/\&\#0+(;|([^\d;]))/is", "\\2", $strY);
			$strY = preg_replace("/\&\#x0+(;|([^\da-f;]))/is", "\\2", $strY);
		}

		$bResult = false;

		$str2 = "";
		$strX = $str1;
		while($str2 <> $strX)
		{
			foreach($this->_filters as $ch => $filters)
			{
				if($ch == '' || strpos($str2, $ch) !== false)
				{
					$str2 = $strX;
					$strX = preg_replace($filters[1], $filters[0], $str2);
					$bResult =  true;
				}
			}
		}

		if($str2 <> $str1)
			$str = $str2;
		else
			$str = $str1;

		return $bResult;
	}

	/*
	Function is used in regular expressions in order to decode characters presented as &#123;
	*/
	public static function _decode_cb($in)
	{
		$ad = $in[2];
		if($ad == ';')
			$ad="";
		$num = intval($in[1]);
		return chr($num).$ad;
	}

	/*
	Function is used in regular expressions in order to decode characters presented as  &#xAB;
	*/
	public static function _decode_cb_hex($in)
	{
		$ad = $in[2];
		if($ad==';')
			$ad="";
		$num = intval(hexdec($in[1]));
		return chr($num).$ad;
	}

	/*
	Decodes string from html codes &#***;
	One pass!
	-- Decode only a-zA-Z:().=, because only theese are used in filters
	*/
	public static function _decode($str)
	{
		$str = preg_replace_callback("/\&\#(\d+)([^\d])/is", array("CWikiSecurity", "_decode_cb"), $str);
		$str = preg_replace_callback("/\&\#x([\da-f]+)([^\da-f])/is", array("CWikiSecurity", "_decode_cb_hex"), $str);
		return str_replace('&colon;', ':', $str);
	}

}

?>
