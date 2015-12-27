<?

IncludeModuleLangFile(__FILE__);


/**
 * <b>CWikiDiff</b> - Класс сравнения вики-страниц. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikidiff/index.php
 * @author Bitrix
 */
class CWikiDiff
{
	//http://en.wikipedia.org/wiki/Longest_common_subsequence_problem
	static function LongestCommonSubsequence($X, $Y)
	{
		$m_start = 0;
		$m_end = count($X)-1;
		$n_start = 0;
		$n_end = count($Y)-1;
		$C = array();

		for($i = $m_start; $i <= $m_end; $i++)
		{
			for($j = $n_start; $j <= $n_end; $j++)
			{
				if($X[$i] == $Y[$j])
					$C[$i][$j] = $C[($i-1)][($j-1)] + 1;
				else
				{
					$k = max($C[$i][($j-1)], $C[($i-1)][$j]);

					if($k != 0)
					{
						$C[$i][$j] = $k;
						//Clean up to the left (buggy)
						/*if($C[$i][$j-1] < $k)
							for($jj = $j-1;$jj >= $n_start;$jj--)
								if(is_array($C[$i]) && array_key_exists($jj, $C[$i]))
									unset($C[$i][$jj]);
								else
									break;*/
					}
				}
			}
			//Clean up to the up
			/*if($i > $m_start)
			{
				$ii = $i - 1;
				if(is_array($C[$ii]))
				{
					for($j = $n_end; $j > $n_start && array_key_exists($j, $C[$ii]); $j--)
					{
						if($C[$i][$j] > $C[$ii][$j])
							unset($C[$ii][$j]);
					}
				}
			}*/
		}
		return $C;
	}

	static function printDiff($C, $X, $Y, $Xt, $Yt, $i, $j)
	{
		while ($i>=0 || $j>=0)
		{
			if( ($i >= 0) && ($j >= 0) && ($Xt[$i] == $Yt[$j]) )
			{
				$arOut[] = $X[1][$i].$X[2][$i];
				$i=$i-1;
				$j=$j-1;
			}

			else
			{
				if( ($j >= 0) && (($i < 0) || ($C[$i][($j-1)] >= $C[($i-1)][$j])) )
				{
					$arOut[] = $Y[1][$j].'<b style="color:green">'.$Y[2][$j]."</b >";
					$j=$j-1;
				}

				elseif( ($i >= 0) && (($j < 0) || ($C[$i][($j-1)] < $C[($i-1)][$j])) )
				{
					$arOut[] = $X[1][$i].'<s style="color:red">'.$X[2][$i]."</s >";
					$i=$i-1;
				}
			}
		}

		if (!is_array($arOut))
			return false;

		$arOut = array_reverse($arOut);
		$strOut = "";

		foreach ($arOut as $str)
			$strOut.=$str;

		return $strOut;
	}

	
	/**
	* <p>Метод сравнивает две страницы. Статичный метод.</p>
	*
	*
	* @param string $X  Страница исходная. </h
	*
	* @param string $Y  Страница, с которой сравниваем.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikidiff/getDiff.php
	* @author Bitrix
	*/
	static function getDiff($X, $Y)
	{

		preg_match_all("/(<.*?>\s*|\s+)([^\s<]*)/", " ".$X, $Xmatch);

		preg_match_all("/(<.*?>\s*|\s+)([^\s<]*)/", " ".$Y, $Ymatch);

		//Determine common beginning
		$sHTMLStart = "";
		while( count($Xmatch[0]) && count($Ymatch[0]) && (trim($Xmatch[2][0]) == trim($Ymatch[2][0])) )
		{
			$sHTMLStart .= $Xmatch[0][0];
			array_shift($Xmatch[0]);array_shift($Xmatch[1]);array_shift($Xmatch[2]);
			array_shift($Ymatch[0]);array_shift($Ymatch[1]);array_shift($Ymatch[2]);
		}

		//Find common ending
		$X_end = count($Xmatch[0])-1;
		$Y_end = count($Ymatch[0])-1;
		$sHTMLEnd = "";

		while( ($X_end >= 0) && ($Y_end >= 0) && (trim($Xmatch[2][$X_end]) == trim($Ymatch[2][$Y_end])) )
		{
			$sHTMLEnd = $Xmatch[0][$X_end].$sHTMLEnd;
			unset($Xmatch[0][$X_end]);unset($Xmatch[1][$X_end]);unset($Xmatch[2][$X_end]);
			unset($Ymatch[0][$Y_end]);unset($Ymatch[1][$Y_end]);unset($Ymatch[2][$Y_end]);
			$X_end--;
			$Y_end--;
		}

		//What will actually diff
		$Xmatch_trimmed = array();
		foreach($Xmatch[2] as $i => $match)
			$Xmatch_trimmed[] = trim($match);

		$Ymatch_trimmed = array();
		foreach($Ymatch[2] as $i => $match)
			$Ymatch_trimmed[] = trim($match);

		//$C = self::LongestCommonSubsequence($Xmatch_trimmed, $Ymatch_trimmed); //Debug
		//self::PrintView($C,$Xmatch_trimmed,$Ymatch_trimmed,0,625);

		$sHTML =self::printDiff(
				self::LongestCommonSubsequence($Xmatch_trimmed, $Ymatch_trimmed),
				$Xmatch,
				$Ymatch,
				$Xmatch_trimmed,
				$Ymatch_trimmed,
				count($Xmatch_trimmed)-1,
				count($Ymatch_trimmed)-1
			);

		$sHTML = preg_replace('#</b >(\s*)<b style="color:green">#','\\1',$sHTML);
		$sHTML = preg_replace('#<b style="color:green">(\s*)</b >#','\\1',$sHTML);
		$sHTML = preg_replace('#</s >(\s*)<s style="color:red">#','\\1',$sHTML);
		$sHTML = preg_replace('#<s style="color:red">(\s*)</s >#','\\1',$sHTML);

		return $sHTMLStart.$sHTML.$sHTMLEnd;
	}

	static function PrintView($C,$X,$Y,$start=0,$end=1000) //Debug
	{
		echo "<table border='1'><tr><td>&nbsp;</td>";

		for($i=$start;$i<=min(count($X),$end);$i++)
				echo "<td><b>".$X[$i]."</b>&nbsp;</td>";

		echo "</tr>";

		for($i = $start;$i<=min(count($Y),$end);$i++)
		{
				echo "<tr><td><b>".$Y[$i]."</b>&nbsp;</td>";

				for($j=$start;$j<=min(count($X),$end);$j++)
						echo "<td>".$C[$j][$i]."&nbsp;</td>";

				echo "</tr>";
		}

		echo "</table><table border='1'>";

		for($i=$start;$i<=min(max(count($X),count($Y)),$end);$i++)
				echo "<tr><td>$i</td><td>".$X[$i]."&nbsp;</td><td>".$Y[$i]."&nbsp;</td</tr>";

		echo "</table>maxX: ".count($X)." maxY: ".count($Y)."<br>";
	}
}
