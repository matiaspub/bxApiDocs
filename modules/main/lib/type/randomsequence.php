<?php
namespace Bitrix\Main\Type;
/**
 * Class for generating pseudo random sequences.
 * Never use it for any security or cryptographic purposes.
 *
 * <code>
 * use \Bitrix\Main\Type\RandomSequence;
 * $rs = new RandomSequence("A");
 * echo $rs->randString();
 * </code>
 */
class RandomSequence
{
	private $mz = 0;
	private $mw = 0;

	/**
	 * Starts new sequence of pseudo random values.
	 *
	 * @param string $seed
	 * @return void
	 */
	public function __construct($seed = "")
	{
		$md = md5($seed);
		$this->mz = crc32(substr($md, 0, 16));
		$this->mw = crc32(substr($md, -16));
	}

	/**
	 * Returns next pseudo random value from the sequence.
	 * The result is signed 32 bit integer.
	 *
	 * @return int
	 */
	public function getNext()
	{
		$this->mz = 36969 * ($this->mz & 65535) + ($this->mz >> 16);
		if($this->mz > 0x7FFFFFFF)
			$this->mz = -(0xFFFFFFFF - $this->mz + 1);

		$this->mw = 18000 * ($this->mw & 65535) + ($this->mw >> 16);
		if($this->mw > 0x7FFFFFFF)
			$this->mw = -(0xFFFFFFFF - $this->mw + 1);

		//return ($this->mz << 16) + $this->mw;

		$r = ($this->mz << 16) & 0xFFFF0000;
		if($r > 0x7FFFFFFF)
			$r = -(0xFFFFFFFF - $r + 1);

		$r += $this->mw;
		if($r > 0x7FFFFFFF)
			$r = -(0xFFFFFFFF - $r + 1);

		return ($r & 0xFFFFFFFF);
	}

	/**
	 * Returns next pseudo random number value from the sequence.
	 * between $min and $max including borders.
	 *
	 * @param int $min
	 * @param int $max
	 * @return int
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function rand($min, $max)
	{
		if ($min >= $max)
			throw new \Bitrix\Main\NotSupportedException("max parameter must be greater than min.");

		$r = sprintf("%u", $this->getNext()) / sprintf("%u", 0xffffffff);
		return intval($min + $r * ($max - $min + 1));
	}

	/**
	 * Returns next pseudo random string value from the sequence.
	 *
	 * @param int $length
	 * @return string
	 */
	public function randString($length = 10)
	{
		static $allChars = "abcdefghijklnmopqrstuvwxyzABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789";
		$result = "";
		for ($i = 0; $i < $length; $i++)
		{
			$result .= $allChars[$this->rand(0, 61)];
		}
		return $result;
	}
}
