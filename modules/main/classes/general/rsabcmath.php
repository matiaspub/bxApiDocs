<?
class CRsaBcmathProvider extends CRsaProvider
{
	static public function LoadKeys()
	{
		$arKeys = unserialize(COption::GetOptionString("main", "~rsa_keys_bcmath", ""));
		if(!is_array($arKeys))
			return false;
		return $arKeys;
	}

	static public function SaveKeys($arKeys)
	{
		COption::SetOptionString("main", "~rsa_keys_bcmath", serialize($arKeys));
	}
	
	public function Decrypt($data) 
	{
		$d = self::raw2int(base64_decode($this->_D));
		$n = self::raw2int(base64_decode($this->_M));

		$out = '';
		$blocks = explode(' ', $data);
		foreach($blocks as $block)
		{
			$block = self::powmod(self::raw2int(base64_decode($block)), $d, $n);
			while(bccomp($block, '0') != 0)
			{
				$x = bcmod($block, '256');
				$block = bcdiv($block, '256', 0);
				$out .= chr($x);
			}
		}
		return $out;
	}
	    
	static public function Keygen($keylen=false)
	{
		if($keylen === false)
			$keylen = 512;
		else
			$keylen = intval($keylen);
	
		$pl = intval(($keylen + 1) / 2);
		$ql = $keylen - $pl;
	
		$p = self::getRndPrime($pl);
		$q = self::getRndPrime($ql);
	
		$x = self::getkeysfrompq($p, $q, 1) ;
	
		return array(
			"M" => base64_encode(self::int2raw($x[0])),
			"E" => base64_encode(self::int2raw($x[1])),
			"D" => base64_encode(self::int2raw($x[2])),
			"chunk" => $keylen/8,
		);
	}

	private static function getRndPrime($cnt)
	{
		$btn = intval($cnt / 8);
		$bn = $cnt % 8;
		$ret = '0';
	
		while(self::bitlenght($ret) != $cnt)
		{
			$str = '';
			for($i = 0; $i < $btn; $i++) 
				$str .= chr(rand() & 0xff);
	
			$n = rand() & 0xff;
			$n |= 0x80;
			$n >>= 8 - $bn;
			$str .= chr($n);
			$ret = self::raw2int($str);
	
			if(!bccomp(bcmod($ret, '2'), '0')) 
				$ret = bcadd($ret, '1');
	
			while(!self::is_prime($ret)) 
				$ret = bcadd($ret, '2');
		}
		return $ret;
	}

	private static function bitlenght($in)
	{
		$t = self::int2raw($in);
		$out = strlen($t) * 8;
	
		$t = ord($t[strlen($t)-1]);
	
		if(!$t) 
		{
			$out -= 8;
		}
		else 
		{
			while(!($t & 0x80)) 
			{
				$out--;
				$t <<= 1;
			}
		}
		return $out;
	}

	private static function is_prime($in)
	{
		static $ps = null;
		static $psc = 0;
		if(is_null($ps)) 
		{
			$ps = array();
			for($i = 0; $i < 10000; $i++) 
				$ps[] = $i;
			$ps[0] = $ps[1] = 0;
			for($i = 2; $i < 100; $i++) 
			{
				while(!$ps[$i]) 
					$i++;
				$j = $i;
				for($j += $i; $j < 10000; $j += $i) 
					$ps[$j] = 0;
			}
			$j = 0;
			for($i = 0; $i < 10000; $i++) 
			{
				if($ps[$i]) $ps[$j++] = $ps[$i];
			}
			$psc = $j;
		}
	
		for($i = 0; $i < $psc; $i++) 
		{
			if(bccomp($in, $ps[$i]) <= 0) 
				return true;
			if(!bccomp(bcmod($in, $ps[$i]), '0')) 
				return false;
		}
	
		for($i = 0; $i < 7; $i++) 
		{
			if(!self::miller($in, $ps[$i])) 
				return false;
		}
		return true;
	}

	private static function getkeysfrompq($p, $q)
	{
		$n = bcmul($p, $q);
		$m = bcmul(bcsub($p, 1), bcsub($q, 1));
		$e = self::get_e($m);
		$d = self::ext($e, $m);
		return array($n, $e, $d);
	}

	private static function ext($e1, $em) 
	{
		$u1 = '1';
		$u2 = '0';
		$u3 = $em;
		$v1 = '0';
		$v2 = '1';
		$v3 = $e1;
	
		while(bccomp($v3, 0) != 0) 
		{
			$qt = bcdiv($u3, $v3, 0);
			$t1 = bcsub($u1, bcmul($qt, $v1));
			$t2 = bcsub($u2, bcmul($qt, $v2));
			$t3 = bcsub($u3, bcmul($qt, $v3));
			$u1 = $v1;
			$u2 = $v2;
			$u3 = $v3;
			$v1 = $t1;
			$v2 = $t2;
			$v3 = $t3;
			$z  = '1';
		}
	
		$uu = $u1;
		$vv = $u2;
	
		if(bccomp($vv, 0) == -1) 
			$ret = bcadd($vv, $em);
		else 
			$ret = $vv;
	
		return $ret;
	}
	
	private static function get_e($m)
	{
		$et = '257';
		if(bccomp(self::GCD($et, $m), '1') != 0)
		{
			$et = '5';
			$c = '2';
	
			while(bccomp(self::GCD($et, $m), '1') != 0)
			{
				$et = bcadd($et, $c);
				if($c == '2')
						$c = '4';
				else
					$c = '2';
			}
		}
	
		return $et;
	}

	private static function GCD($e, $m) 
	{
		$e1 = $e;
		$m1 = $m;
		while(bccomp($e1, 0) != 0) 
		{
			$w = bcsub($m1, bcmul($e1, bcdiv($m1, $e1, 0)));;
			$m1 = $e1;
			$e1 = $w;
		}
	
		return $m1;
	}
	
	private static function int2raw($in)
	{
		$out = '';
	
		if($in=='0') 
			return chr(0);
	
		while(bccomp($in, '0'))
		{
			$out .= chr(bcmod($in, '256'));
			$in = bcdiv($in, '256');
		}
		return $out;
	}
	
	private static function raw2int($in)
	{
		$out = '0';
		$n = strlen($in);
		while($n > 0)
		{
			$out = bcadd(bcmul($out, '256'), ord($in[--$n]));
		}
		return $out;
	}

	private static function powmod($n, $p, $m)
	{
		if(function_exists('bcpowmod')) 
			return bcpowmod($n, $p, $m);
	
		$out = '1';
		do
		{
			if(!bccomp(bcmod($p, '2'), '1'))
			{
				$out = bcmod(bcmul($out, $n), $m);
			}
			$n = bcmod(bcpow($n, '2'), $m);
			$p = bcdiv($p, '2');
		}
		while(bccomp($p, '0'));
	
		return $out;
	}
	
	private static function miller($in, $b)
	{
		if(!bccomp($in, '1'))
			return false;
	
		$t = bcsub($in, '1');
	
		$nulcnt = 0;
		while(!bccomp(bcmod($t, '2'), '0')) 
		{
			$nulcnt++;
			$t = bcdiv($t, '2');
		}
	
		$t = self::powmod($b, $t, $in);
		if(!bccomp($t, '1')) 
			return true;
	
		while($nulcnt--) 
		{
			if(!bccomp(bcadd($t, '1'), $in)) 
				return true;

			$t = self::powmod($t, '2', $in);
		}
		return false;
	}
}
?>