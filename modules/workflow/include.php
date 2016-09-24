<?
global $DBType;

CModule::AddAutoloadClasses(
	"workflow",
	array(
		"CAllWorkflow" => "classes/general/workflow.php",
		"CWorkflow" => "classes/".$DBType."/workflow.php",
		"CWorkflowStatus" => "classes/general/status.php",
	)
);

function GetDefaultProlog($title)
{
	return "<"."?\n".
		"require(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/modules/main/include/prolog_before.php\");\n".
		"\$APPLICATION->SetTitle(\"".EscapePHPString($title)."\");\n".
		"require(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/modules/main/include/prolog_after.php\");\n".
		"?".">\n";
}

function GetDefaultEpilog()
{
	return "\n<"."?require(\$_SERVER[\"DOCUMENT_ROOT\"].\"/bitrix/modules/main/include/epilog.php\");?".">";
}

function PathToWF($text, $DOCUMENT_ID)
{
	return preg_replace("'(<img[^>]+?src\\s*=\\s*\")(\\S+)(\"[^>]*>)'i", "\\1/bitrix/admin/workflow_get_file.php?did=".$DOCUMENT_ID."&fname=\\2\\3", $text);
}

function convert_image($img="",$query="",$param="")
{
	if (is_array($img))
	{
		$param = $img[3];
		$query = $img[2];
		$img   = $img[1];
	}
	else
	{
		$param = stripslashes($param);
		$query = stripslashes($query);
		$img   = stripslashes($img);
	}
	$params = array();
	parse_str(htmlspecialcharsback($query), $params);
	return $img.$params['fname'].$param;
}

function WFToPath($text)
{
	return preg_replace_callback("'(<img[^>]+?src\\s*=\\s*[\"\'])/bitrix/admin/workflow_get_file.php\\?([^>]+)([\"\'][^>]*>)'i", "convert_image", $text);
}

function SavePreviewContent($abs_path, $strContent)
{
	CheckDirPath($abs_path);
	$fd = fopen($abs_path, "wb");
	if(is_resource($fd))
	{
		$result = fwrite($fd, $strContent);
		fclose($fd);
		chmod($abs_path, BX_FILE_PERMISSIONS);

		return $result > 0;
	}
	else
	{
		return false;
	}
}

//http://en.wikipedia.org/wiki/Longest_common_subsequence_problem
//function  LCS(X[1..m], Y[1..n])
function LongestCommonSubsequence($X, $Y)
{
//	m_start := 1
	$m_start = 0;
//	m_end := m
	$m_end = count($X)-1;
//	n_start := 1
	$n_start = 0;
//	n_end := n
	$n_end = count($Y)-1;
//	C = array(m_start-1..m_end, n_start-1..n_end)
	$C = array();
//	for($i = $m_start-1; $i <= $m_end; $i++)
//	{
//		$C[$i] = array();
//		for($j = $n_start-1; $j <= $n_end; $j++)
//		{
//			$C[$i][$j] = 0;
//		}
//	}
//	for i := m_start..m_end
	for($i = $m_start; $i <= $m_end; $i++)
	{
//		for j := n_start..n_end
		for($j = $n_start; $j <= $n_end; $j++)
		{
//			if X[i] = Y[j]
			if($X[$i] == $Y[$j])
			{
//				C[i,j] := C[i-1,j-1] + 1
				$C[$i][$j] = $C[($i-1)][($j-1)] + 1;
			}
//			else:
			else
			{
				$k = max($C[$i][($j-1)], $C[($i-1)][$j]);
//				C[i,j] := max(C[i,j-1], C[i-1,j])
				if($k != 0)
				{
					$C[$i][$j] = $k;
					//Clean up to the left
					if($C[$i][$j-1] < $k)
						for($jj = $j-1;$jj >= $n_start;$jj--)
							if(is_array($C[$i]) && array_key_exists($jj, $C[$i]))
								unset($C[$i][$jj]);
							else
								break;
				}
			}
		}
		//Clean up to the up
		if($i > $m_start)
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
		}
	}
//	return C[m,n]
	return $C;
}

//function printDiff(C[0..m,0..n], X[1..m], Y[1..n], i, j)
//	if i > 0 and j > 0 and X[i] = Y[j]
//		printDiff(C, X, Y, i-1, j-1)
//		print "  " + X[i]
//	else
//		if j > 0 and (i = 0 or C[i,j-1] >= C[i-1,j])
//			printDiff(C, X, Y, i, j-1)
//			print "+ " + Y[j]
//		else if i > 0 and (j = 0 or C[i,j-1] < C[i-1,j])
//			printDiff(C, X, Y, i-1, j)
//			print "- " + X[i]

function printDiff($C, $X, $Y, $Xt, $Yt, $i, $j)
{
	$a = array();
	while($i >= 0 || $j >= 0)
	{
		if( ($i >= 0) && ($j >= 0) && ($Xt[$i] == $Yt[$j]) )
		{
			array_unshift($a, $X[1][$i].$X[2][$i]);
			$i--; $j--;
		}
		elseif( ($j >= 0) && ($i <= 0 || ($C[$i][($j-1)] >= $C[($i-1)][$j])) )
		{
			array_unshift($a, $Y[1][$j].'<b style="color:green">',$Y[2][$j],"</b >");
			$j--;
		}
		elseif( ($i >= 0) && ($j <= 0 || ($C[$i][($j-1)] < $C[($i-1)][$j])) )
		{
			array_unshift($a, $X[1][$i].'<s style="color:red">',$X[2][$i],"</s >");
			$i--;
		}
	}
	echo implode("", $a);
}

function getDiff($X, $Y)
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
	{
		$Xmatch_trimmed[] = trim($match);
	}

	$Ymatch_trimmed = array();
	foreach($Ymatch[2] as $i => $match)
	{
		$Ymatch_trimmed[] = trim($match);
	}

	ob_start();
	printDiff(
		LongestCommonSubsequence($Xmatch_trimmed, $Ymatch_trimmed),
		$Xmatch,
		$Ymatch,
		$Xmatch_trimmed,
		$Ymatch_trimmed,
		count($Xmatch_trimmed)-1,
		count($Ymatch_trimmed)-1
	);
	$sHTML = ob_get_contents();
	ob_end_clean();

	$sHTML = preg_replace('#</b >(\s*)<b style="color:green">#','\\1',$sHTML);
	$sHTML = preg_replace('#<b style="color:green">(\s*)</b >#','\\1',$sHTML);
	$sHTML = preg_replace('#</s >(\s*)<s style="color:red">#','\\1',$sHTML);
	$sHTML = preg_replace('#<s style="color:red">(\s*)</s >#','\\1',$sHTML);

	return $sHTMLStart.$sHTML.$sHTMLEnd;
}

?>