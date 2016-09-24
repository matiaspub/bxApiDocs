<?
namespace Bitrix\Wiki;

/**
 * This Class implements the Difference Algorithm published in
 * "An O(ND) Difference Algorithm and its Variations" by Eugene Myers
 *  Algorithmica Vol. 1 No. 2, 1986, p 251.
 */

class Diff
{
	protected $upVector = array();
	protected $downVector = array();
	protected $modifiedA = array();
	protected $modifiedB = array();

	/**
	 * Function finds the difference between two versions of text and creates html output with highlighted edits to
	 * transform text from first to second version.
	 *
	 * @param string $a First version of text to be compared.
	 * @param string $b Second version of text to be compared.
	 * @return string
	 */
	public function getDiffHtml($a, $b)
	{
		preg_match_all("/(<.*?>\s*|\s+)([^\s<]*)/", " ".$a, $matchA);
		preg_match_all("/(<.*?>\s*|\s+)([^\s<]*)/", " ".$b, $matchB);

		$diffScript = $this->getDiffScript($matchA[2], $matchB[2]);
		if(count($diffScript) == 0)
		{
			// no difference found
			return $a;
		}

		$positionA = 0;

		$result = '';
		foreach($diffScript as $diffItem)
		{
			while($positionA < $diffItem['startA'])
			{
				$result .= $matchA[0][$positionA];
				$positionA++;
			}

			//deleted items
			if($diffItem['deletedA'] > 0)
			{
				$result .= $matchA[1][$positionA] . '<s style="color:red">' . $matchA[2][$positionA];
				for($i = 1; $i < $diffItem['deletedA']; $i++)
					$result .= $matchA[0][$positionA + $i];

				$result .= '</s>';
				$positionA = $positionA + $diffItem['deletedA'];
			}

			if($diffItem['insertedB'] > 0)
			{
				$result .= $matchB[1][$diffItem['startB']] . '<b style="color:green">' . $matchB[2][$diffItem['startB']];
				for($i = 1; $i < $diffItem['insertedB']; $i++)
					$result .= $matchB[0][$diffItem['startB'] + $i];

				$result .= '</b>';
			}
		}

		while($positionA < count($matchA[0]))
		{
			$result .= $matchA[0][$positionA];
			$positionA++;
		}

		return $result;
	}

	/**
	 * Function compares two arrays and creates edit script, that is required to transform array $a to array $b
	 *
	 * @param array $a First array to be compared.
	 * @param array $b Second array to be compared.
	 * @return array Array of edit steps to transform array $a to array $b. Each step is an array with keys:
	 * <li>startA - position in array $a
	 * <li>startB - position in array $b
	 * <li>deletedA - count of elements deleted from array $a
	 * <li>insertedB - count of elements inserted from array $b.
	 */
	public function getDiffScript(array $a, array $b)
	{
		$this->init();
		$this->longestCommonSubsequence($a, 0, count($a), $b, 0, count($b));
		return $this->createDiff($a, $b);
	}

	/**
	 * Function initializes object's fields for usage in difference algorithm.
	 *
	 * @return void
	 */
	protected function init()
	{
		$this->upVector = array();
		$this->downVector = array();
		$this->modifiedA = array();
		$this->modifiedB = array();
	}

	/**
	 * Function looks for longest common subsequence between two array
	 *
	 * @param array $a First array to be compared.
	 * @param int $lowerA Lower bound of the first array.
	 * @param int $upperA Upper bound of the first array.
	 * @param array $b Second array to be compared.
	 * @param int $lowerB Lower bound of the second array.
	 * @param int $upperB Upper bound of the second array.
	 * @return void
	 */
	protected function longestCommonSubsequence(array $a, $lowerA, $upperA, array $b, $lowerB, $upperB)
	{
		// Skipping equal lines at the start
		while ($lowerA < $upperA && $lowerB < $upperB && $a[$lowerA] == $b[$lowerB])
		{
			$lowerA++;
			$lowerB++;
		}

		// Skipping equal lines at the end
		while ($lowerA < $upperA && $lowerB < $upperB && $a[$upperA - 1] == $b[$upperB - 1])
		{
			$upperA--;
			$upperB--;
		}

		if ($lowerA === $upperA)
		{
			// mark as inserted lines.
			while ($lowerB < $upperB)
			{
				$this->modifiedB[$lowerB++] = true;
			}
		}
		else
		{
			if ($lowerB === $upperB)
			{
				// mark as deleted lines.
				while ($lowerA < $upperA)
				{
					$this->modifiedA[$lowerA++] = true;
				}
			}
			else
			{
				// Find the middle snake and length of an optimal path for A and B
				$sms = $this->shortestMiddleSnake($a, $lowerA, $upperA, $b, $lowerB, $upperB);

				// The path is from LowerX to (x,y) and (x,y) to UpperX
				$this->longestCommonSubsequence($a, $lowerA, $sms['x'], $b, $lowerB, $sms['y']);
				$this->longestCommonSubsequence($a, $sms['x'], $upperA, $b, $sms['y'], $upperB);
			}
		}
	}


	/**
	 * Function looks for shortest middle snake between two arrays (see Meyer's work
	 * "An O(ND) Difference Algorithm and its Variations")
	 *
	 * @param array $a First array to be compared.
	 * @param int $lowerA Lower bound of the first array.
	 * @param int $upperA Upper bound of the first array.
	 * @param array $b Second array to be compared.
	 * @param int $lowerB Lower bound of the second array.
	 * @param int $upperB Upper bound of the second array.
	 * @return array Array with keys 'x' and 'y', describing found shortest middle snake
	 */
	protected function shortestMiddleSnake(array $a, $lowerA, $upperA, array $b, $lowerB, $upperB)
	{
		$result = array();

		$downK = $lowerA - $lowerB; // the k-line to start the forward search
		$upK = $upperA - $upperB; // the k-line to start the reverse search

		$delta = ($upperA - $lowerA) - ($upperB - $lowerB);
		$oddDelta = ($delta & 1) != 0;

		$maxD = (($upperA - $lowerA + $upperB - $lowerB) / 2) + 1;

		// init vectors
		$this->downVector[$downK + 1] = $lowerA;
		$this->upVector[$upK - 1] = $upperA;

		for ($d = 0; $d <= $maxD; $d++)
		{

			// Extend the forward path.
			for ($k = $downK - $d; $k <= $downK + $d; $k += 2)
			{
				// find the only or better starting point
				$x = 0;
				$y = 0;
				if ($k == $downK - $d)
				{
					$x = $this->downVector[$k + 1]; // down
				}
				else
				{
					$x = $this->downVector[$k - 1] + 1; // a step to the right
					if (($k < $downK + $d) && ($this->downVector[$k + 1] >= $x))
					{
						$x = $this->downVector[$k + 1];
					} // down
				}
				$y = $x - $k;

				// find the end of the furthest reaching forward D-path in diagonal k.
				while (($x < $upperA) && ($y < $upperB) && ($a[$x] == $b[$y]))
				{
					$x++;
					$y++;
				}
				$this->downVector[$k] = $x;

				// overlap ?
				if ($oddDelta && ($upK - $d < $k) && ($k < $upK + $d))
				{
					if ($this->upVector[$k] <= $this->downVector[$k])
					{
						$result['x'] = $this->downVector[$k];
						$result['y'] = $this->downVector[$k] - $k;
						return $result;
					}
				}
			}

			// Extend the reverse path.
			for ($k = $upK - $d; $k <= $upK + $d; $k += 2)
			{

				// find the only or better starting point
				$x = 0;
				$y = 0;
				if ($k == $upK + $d)
				{
					$x = $this->upVector[$k - 1]; // up
				}
				else
				{
					$x = $this->upVector[$k + 1] - 1; // left
					if (($k > $upK - $d) && ($this->upVector[$k - 1] < $x))
					{
						$x = $this->upVector[$k - 1];
					} // up
				}
				$y = $x - $k;

				while (($x > $lowerA) && ($y > $lowerB) && ($a[$x - 1] == $b[$y - 1]))
				{
					// diagonal
					$x--;
					$y--;
				}
				$this->upVector[$k] = $x;

				// overlap ?
				if (!$oddDelta && ($downK - $d <= $k) && ($k <= $downK + $d))
				{
					if ($this->upVector[$k] <= $this->downVector[$k])
					{
						$result['x'] = $this->downVector[$k];
						$result['y'] = $this->downVector[$k] - $k;
						return $result;
					}
				}
			}
		}
	}

	/**
	 * Function creates diff script. Should be called after longestCommonSubsequence().
	 * @param array $a First array to be compared.
	 * @param array $b Second array to be compared.
	 * @return array Array of edit steps to transform array $a to array $b.
	 */
	protected function createDiff(array $a, array $b)
	{
		$indexA = 0;
		$indexB = 0;
		$result = array();
		while ($indexA < count($a) || $indexB < count($b))
		{
			if (($indexA < count($a)) && (!$this->modifiedA[$indexA]) && ($indexB < count($b)) && (!$this->modifiedB[$indexB]))
			{
				// equal lines
				$indexA++;
				$indexB++;
			}
			else
			{
				// maybe deleted and/or inserted lines
				$startA = $indexA;
				$startB = $indexB;

				while ($indexA < count($a) && ($indexB >= count($b) || $this->modifiedA[$indexA]))
				{
					$indexA++;
				}

				while ($indexB < count($b) && ($indexA >= count($a) || $this->modifiedB[$indexB]))
				{
					$indexB++;
				}

				if (($startA < $indexA) || ($startB < $indexB))
				{
					// store a new difference-item
					$result[] = array("startA" => $startA, "startB" => $startB, "deletedA" => $indexA - $startA, "insertedB" => $indexB - $startB);
				}
			}
		}
		return $result;
	}
}