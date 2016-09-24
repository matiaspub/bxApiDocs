<?php
IncludeModuleLangFile(__FILE__);

class CVariableDeclare
{
	public $line = 0;
	public $start = 0;
	public $end = 0;
	public $comment = '';
	public $tokens = array();
	public $dependencies = array();
	public $tainted_vars = array();
	public $id = 0;

	public function __construct($id, $line, $start, $end, $tokens, $comment, $dependencies, $tainted_vars)
	{
		$this->line = $line;
		$this->start = $start;
		$this->end = $end;
		$this->comment = $comment;
		$this->tokens = $tokens;
		$this->dependencies = $dependencies;
		$this->tainted_vars = $tainted_vars;
		$this->id = $id;
	}
}

class CVariable
{
	public $declares = array();
	public $have_user_input = false;
	public $secure = false;
	public $name = '';
	public $requestInitialization = true;

	public function __construct($name)
	{
		$this->name = $name;
		$this->have_user_input = false;
		$this->declares = array();
		$this->secure = false;
		$this->requestInitialization = true;
	}
	
	public function newDeclare($id, $line, $start, $end, $tokens, $comment, $dependencies, $tainted_vars)
	{
		$this->declares[] = new CVariableDeclare($id, $line, $start, $end, $tokens, $comment, $dependencies, $tainted_vars);
	}
}

class CVuln
{
	public $comment = '';
	public $tokens = array();
	public $dependencies = array();
	public $tainted_vars = array();
	public $name = '';
	public $line = 0;
	public $filename = '';
	public $traverse = '';
	public $additional_text = '';

	public function __construct($filename, $line, $name, $tokens, $dependencies, $tainted_vars, $comment)
	{
		$this->tokens = $tokens;
		$this->filename = $filename;
		$this->line = $line;
		$this->name = $name;
		$this->dependencies = $dependencies;
		$this->tainted_vars = $tainted_vars;
		$this->comment = $comment;
		$this->traverse = '';
		$this->additional_text = '';
	}

}

class CVulnScanner
{

	public $vuln_count = 0;
	public $arResult = array();

	private $tokens = array();
	private $variables = array();
	private $template = '';
	private $file_history = array();
	private $current_file = '';
	private $scan_functions = array();
	private $tokens_type = array();
	private $vuln_func = array();
	private $sec_func = array();
	private $v_userinput = array();
	private $mp_mode = false;
	private $color = array();
	private $securing_list = array();
	private $tainted_vars = array();
	private $comment = '';
	private $braces = 0;
	private $dependency = array();
	private $last_dependency = array();
	private $dependencies = array();
	private $dependency_line = 0;
	private $scanning_file = '';
	private $init_functions = array();
	private $search_xss = true;
	private $global_xss_ignore = false;

	public function __construct($file_name, $arParams, $template = '.default', $component_template = '')
	{
		$this->scanning_file = $file_name;
		$this->source_functions = array();
		$this->tokens_type = $arParams['TOKENS_TYPES'];
		$this->vuln_func = $arParams['VULN_FUNCTIONS'];
		$this->sec_func = $arParams['SECURING_FUNCTIONS'];
		$this->v_userinput = $arParams['USER_INPUTS'];
		$this->init_functions = $arParams['INIT_FUNCTIONS'];

		$this->securing_list = array();
		$this->variables = array();

		$this->mp_mode = $arParams['MP_mode'];
		$this->arParams = $arParams;
		$this->arResult = array();
		$this->tainted_vars = array();

		$this->template = $template;
		$this->comment = '';
		$this->dependency = array();
		$this->last_dependency = array();
		$this->dependencies = array();
		$this->dependency_line = 'syntax_error';
		$this->braces = 0;

		$this->scan_functions = array_merge(
			$this->vuln_func['XSS'],
			$this->vuln_func['CODE'],
			$this->vuln_func['FILE_INCLUDE'],
			$this->vuln_func['EXEC'],
			$this->vuln_func['DATABASE'],
			$this->vuln_func['OTHER']
		);

		$this->color = $arParams['COLORS'];
		$this->file_history[] = $file_name;
		$this->current_file = end($this->file_history);
		$this->tokens = $this->tokenize(file_get_contents($file_name), $component_template);
		$this->vuln_count = 0;
		$this->search_xss = true;
		$this->global_xss_ignore = false;
	}

	private function getTokensInfo($tokens, $var_declare = true, $function = '')
	{
		$arResult = array();

		$this->securing_list = array();

		$braces = 0;
		$c_params = 1;
		$skip = false;
		$unsecure = false;
		$secure = false;
		$cur_brace = -1;

		for ($i = 0, $count = count($tokens); $i < $count; $i++) 
		{
			if(is_array($tokens[$i])) 
			{
				$token = $tokens[$i][0];
				$token_value = $tokens[$i][1];
				if($token === T_DOUBLE_COLON || $token === T_OBJECT_OPERATOR)
					return false;

				elseif($token === T_VARIABLE)
				{
					if($var_declare || $this->scan_functions[$function][0] === 0 || in_array($c_params, $this->scan_functions[$function][0]))
					{

						if((is_array($tokens[$i - 1])
							&& in_array($tokens[$i - 1][0], $this->tokens_type['CASTS']))
							|| in_array($tokens[$i + 1], $this->tokens_type['ARITHMETIC_STR'])
							|| in_array($tokens[$i - 1], $this->tokens_type['ARITHMETIC_STR'])
							|| (is_array($tokens[$i + 1])
								&& (in_array($tokens[$i + 1][0], $this->tokens_type['ARITHMETIC'])
									|| in_array($tokens[$i + 1][0], $this->tokens_type['OPERATOR'])
									|| in_array($tokens[$i + 1][0], $this->tokens_type['LOGICAL'])
								))
							|| (is_array($tokens[$i - 1])
								&& (in_array($tokens[$i - 1][0], $this->tokens_type['ARITHMETIC'])
									|| in_array($tokens[$i - 1][0], $this->tokens_type['OPERATOR'])
									|| in_array($tokens[$i - 1][0], $this->tokens_type['LOGICAL'])
								))
						)
						{
							$skip = true;
						}
						else
						{
							if(!in_array($token_value, array_keys($arResult)))
							{
								/*if($var_declare)
								{*/
								if(in_array($token_value, $this->v_userinput) && ($var_declare || !$secure || $unsecure))
								{

									$arResult[$token_value]['have_user_input'] = true;
									$arResult[$token_value]['secure'] = $secure;
									$arResult[$token_value]['var_name'] = $token_value;
									$arResult[$token_value]['requestInitialization'] = true;
								}
								elseif(isset($this->variables[$val = $this->getVarName($tokens[$i])]))
								{
									if($this->variables[$val]->have_user_input && ($var_declare || !$this->variables[$val]->secure || $unsecure))
									{
										$arResult[$token_value]['have_user_input'] = true;
										$arResult[$token_value]['secure'] = ($this->variables[$val]->secure && !$unsecure) ? true : $secure;
										$arResult[$token_value]['var_name'] = $val;
										$arResult[$token_value]['requestInitialization'] = $this->variables[$val]->requestInitialization;
									}
									//break;
								}
								elseif((isset($this->variables[$token_value]) && $this->variables[$token_value]->have_user_input)
									&& ($var_declare || !$this->variables[$token_value]->secure || $unsecure))
								{
									$arResult[$token_value]['have_user_input'] = true;
									$arResult[$token_value]['secure'] = ($this->variables[$token_value]->secure && !$unsecure) ? true : $secure;
									$arResult[$token_value]['var_name'] = $token_value;
									$arResult[$token_value]['requestInitialization'] = $this->variables[$token_value]->requestInitialization;
									//break;
								}
								/*}
								else
								{
									if(!$secure && (in_array($token_value, $this->v_userinput) || (isset($this->variables[$token_value]) && $this->variables[$token_value]->have_user_input && (!$this->variables[$token_value]->secure || $unsecure))))
									{
										$arResult[$token_value]['have_user_input'] = true;
										//$arResult[]['secure'] = $secure;
										$arResult[$token_value]['var_name']=$token_value;
									}
								}*/
							}
						}
					}
				}
				elseif($cur_brace === -1 && $token === T_STRING
					&& in_array($token_value, $this->sec_func['INSTRING'])
				)
				{
					$unsecure = true;
					$secure = false;
					$cur_brace = $braces;
				}
				elseif(!$unsecure && ($token === T_STRING
					&& (
						in_array($token_value, $this->sec_func['SECURES_ALL'])
							|| in_array($token_value, $this->sec_func['STRING'])
							|| (is_array($this->scan_functions[$function][1])
							&& in_array($token_value, $this->scan_functions[$function][1]))
					))
					|| (in_array($token, $this->tokens_type['CASTS']) && $tokens[$i + 1] === '(')
				)
				{
					$this->securing_list[] = $token_value;
					$secure = true;

					$cur_brace = $braces;
					$braces++;
					$i++;
				}
				elseif($token === T_ISSET || ($token === T_STRING && substr($token_value, 0, 3) === 'is_'))
				{
					$skip = true;
				}
			}
			elseif($braces === 1 && $tokens[$i] === ', ')
			{
				$c_params++;
				$skip = false;
			}
			elseif($tokens[$i] === '(')
			{
				$braces++;
			}
			elseif($tokens[$i] === ')')
			{
				$braces--;
				if($cur_brace === $braces)
				{
					$cur_brace = -1;
					$unsecure = false;
					$secure = false;
				}
			}

			if($skip)
			{
				while (!($tokens[$i + 1] === ', ') && $i + 1 < $count) 
				{
					if($tokens[$i + 1] === ')')
						$braces--;
					$i++;
				}
				$skip = false;
			}
		}

		if(!empty($arResult))
		{
			$secure = true;
			foreach ($arResult as $res)
			{
				if($res['secure'] === false)
				{
					$secure = false;
					break;
				}
			}

			$requestInitialization = false;
			foreach ($arResult as $res)
			{
				if($res['requestInitialization'] === true)
				{
					$requestInitialization = true;
					break;
				}
			}

			return array($secure, $arResult, $requestInitialization);
		}

		return false;
	}

	private function dependencyHave($tokens, $type)
	{

		for ($i = 1, $tokens_count = count($tokens) - 1; $i < $tokens_count; $i++)
		{
			if(is_array($tokens[$i]))
			{
				switch ($type)
				{
					case 'XSS':
						if($tokens[$i][0] === T_STRING && $tokens[$i - 1] !== '!' && $tokens[$i][1] === 'check_bitrix_sessid')
							return true;
						break;
					case '!XSS':
						if($tokens[$i][0] === T_STRING && $tokens[$i - 1] === '!' && $tokens[$i][1] === 'check_bitrix_sessid')
							return true;
						break;
				}
			}
		}
		return false;
	}

	public function process()
	{
		for ($i = 0, $tokens_count = count($this->tokens); $i < $tokens_count; $i++)
		{
		
			if((time() - $this->arParams['time_start']) >= $this->arParams['time_out'])
				return false;

			if(is_array($this->tokens[$i]))
			{
				$token = $this->tokens[$i][0];
				$token_value = $this->tokens[$i][1];
				$cur_line = $this->tokens[$i][2];

				if($token === T_VARIABLE)
				{
					if($this->tokens[$i + 1] === '=' || (is_array($this->tokens[$i + 1]) && in_array($this->tokens[$i + 1][0], $this->tokens_type['ASSIGNMENT'])))
					{
						if(!(is_array($this->tokens[$i + 2]) && $this->tokens[$i + 2][0] === T_ARRAY))
							$this->addVariable($this->tokens[$i], $i, $cur_line, $i, $this->getBraceEnd($this->tokens, $i), '');
						else
							$i += $this->getBraceEnd($this->tokens, $i);
					}
				}
				elseif($token === T_STRING && in_array($token_value, $this->init_functions))
				{
					if($this->tokens[$i + 1] === '(' && is_array($this->tokens[$i + 2]) && $this->tokens[$i + 3] === ')')
						if(isset($this->variables[$val = $this->getVarName($this->tokens[$i + 2])]))
							unset($this->variables[$val]);
				}
				elseif(
					in_array($token, $this->tokens_type['FUNCTIONS']) 
					|| ($this->search_xss && !$this->global_xss_ignore && in_array($token, $this->tokens_type['XSS']))
					|| in_array($token, $this->tokens_type['INCLUDES'])
				) 
				{
					if(in_array($token, $this->tokens_type['INCLUDES']))
					{
						$component_template = '';
						//$additional_tokens=array();
						if($token == T_INCLUDE_COMPONENT)
						{
							if(!$this->mp_mode) //not done yet
							{
								$component_name = '';
								if($this->tokens[$i + 1] === '(' && $this->tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING)
								{
									//if(!empty(substr($this->tokens[$i+2][1], 1, -1)))
									$component_name = substr($this->tokens[$i + 2][1], 1, -1);
								}
								$component_name = self::strtolower($component_name);
								if(empty($component_name)) // || strpos($component_name, 'bitrix:') === 0)
									continue;

								$component_name = "/".str_replace(":", "/", $component_name);

								if(is_file($this->arParams['doc_root_path'].'/bitrix/components'.$component_name.'/component.php'))
									$inc_file = $this->arParams['doc_root_path'].'/bitrix/components'.$component_name.'/component.php';
								else
									continue;

								if(
									$this->current_file === $inc_file
									|| in_array($inc_file, $this->file_history)
								)
								{
									// Recursion detected
									continue;
								}

								if($this->tokens[$i + 1] === '(' && $this->tokens[$i + 4][0] === T_CONSTANT_ENCAPSED_STRING)
									$component_template = substr($this->tokens[$i + 4][1], 1, -1);
								
								//$additional_tokens=array(array(T_VARIABLE, '$arParams', 0), '=', array(T_CONSTANT_ENCAPSED_STRING, ' ', 0), ';', array(T_VARIABLE, '$arResult', 0), '=', array(T_CONSTANT_ENCAPSED_STRING, ' ', 0), ';');
								$scanner = new CVulnScanner($inc_file, $this->arParams, $this->template, $component_template);
								$result = $scanner->process();
								if($result !== false)
								{
									if(count($scanner->arResult) > 0)
										$this->arResult = array_merge($this->arResult, $scanner->arResult);
									$this->vuln_count += $scanner->vuln_count;
								}
								else
								{
									return false;
								}

								unset($scanner);
								continue;
							}
							else
							{
								continue;
							}
						}
						//including result_modifier.php
						elseif($token == T_INCLUDE_RESULT_MODIFIER)
						{
							if(!$this->mp_mode)
							{
								$skip = 1;
								$component_path = array_pop(explode('bitrix/components', dirname($this->current_file)));
								$component_template = (!empty($this->tokens[$i][3]) ? $this->tokens[$i][3] : '.default');
								if(is_file($this->arParams['doc_root_path'].'/bitrix/templates/'.$this->template.'/components'.$component_path.'/'.$component_template.'/result_modifier.php')) 
									$inc_file = $this->arParams['doc_root_path'].'/bitrix/templates/'.$this->template.'/components'.$component_path.'/'.$component_template.'/result_modifier.php';
								elseif(is_file($this->arParams['doc_root_path'].'/bitrix/templates/'.$component_template.'/components'.$component_path.'/.default/result_modifier.php'))
									$inc_file = $this->arParams['doc_root_path'].'/bitrix/templates/'.$component_template.'/components'.$component_path.'/.default/result_modifier.php';
								else
									$inc_file = dirname($this->current_file).'/templates/'.$component_template.'/result_modifier.php';

								unset($component_path);
							}
							else
							{
								continue;
							}
						}
						//component template including
						elseif($token == T_INCLUDE_COMPONENTTEMPLATE)
						{
							if(!$this->mp_mode)
							{
								$template_name = 'template';
								$component_template = (!empty($this->tokens[$i][3]) ? $this->tokens[$i][3] : '.default');
								$skip = 3;
								if($this->tokens[$i + 1] === '(' && $this->tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING)
								{
									$tmp = substr($this->tokens[$i + 2][1], 1, -1);
									if(!empty($tmp))
										$template_name = $tmp;
									unset($tmp);
								}
								elseif($this->tokens[$i + 1] === '(' && $this->tokens[$i + 2] === ')')
								{
									$skip = 2;
								}
								$component_path = array_pop(explode('bitrix/components', dirname($this->current_file)));
								if(is_file($this->arParams['doc_root_path'].'/bitrix/templates/'.$this->template.'/components'.$component_path.'/'.$component_template.'/'.$template_name.'.php'))
									$inc_file = $this->arParams['doc_root_path'].'/bitrix/templates/'.$this->template.'/components'.$component_path.'/'.$component_template.'/'.$template_name.'.php';
								elseif(is_file($this->arParams['doc_root_path'].'/bitrix/templates/.default/components'.$component_path.'/'.$component_template.'/'.$template_name.'.php'))
									$inc_file = $this->arParams['doc_root_path'].'/bitrix/templates/.default/components'.$component_path.'/'.$component_template.'/'.$template_name.'.php';
								else
									$inc_file = dirname($this->current_file).'/templates/'.$component_template.'/'.$template_name.'.php';
								

								unset($component_path);
							}
							else
							{
								continue;
							}
						}
						// include('xxx')
						elseif((($this->tokens[$i + 1] === '('
							&& $this->tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING
							&& $this->tokens[$i + 3] === ')')
							// include 'xxx'
							|| (is_array($this->tokens[$i + 1])
								&& $this->tokens[$i + 1][0] === T_CONSTANT_ENCAPSED_STRING
								&& $this->tokens[$i + 2] === ';'))
						)
						{
							if($this->tokens[$i + 1] === '(')
							{
								$inc_file = substr($this->tokens[$i + 2][1], 1, -1);
								$skip = 5;
							}
							else
							{
								$inc_file = substr($this->tokens[$i + 1][1], 1, -1);
								$skip = 3;
							}
						}
						else
						{
							$inc_file = $this->getTokensValue(
								$this->current_file, array_slice($this->tokens, $i + 1, $c = $this->getBraceEnd($this->tokens, $i + 1) + 1));
							$skip = $c + 1;
						}


						$try_file = $inc_file;
						if(!is_file($try_file)) // && strpos($try_file, $this->arParams['path'] !== 0))
						{
							$try_file = dirname($this->current_file).'/'.$inc_file;

						}

						//not including bitrix core, too hard:(
						if(is_file($try_file) && !preg_match($this->arParams['PREG_FOR_SKIP_INCLUDE'], $inc_file))
						{
							if(
								$this->current_file !== realpath($try_file) // Circle including
								&& !in_array(realpath($try_file), $this->file_history)
							)
							{
								if($file = file_get_contents($try_file))
								{
									$inc_tokens = $this->tokenize($file, $component_template);
									$this->tokens = array_merge(array_slice($this->tokens, 0, $i + $skip), $inc_tokens, array(array(T_INCLUDE_END, 0, $inc_file)), array_slice($this->tokens, $i + $skip));

									$tokens_count = count($this->tokens);
									$this->current_file = & realpath($try_file);

									$this->comment = str_replace(realpath(trim($this->arParams['path'])), '', realpath(trim($try_file))).' ';
								}
							}
						}
						unset($try_file);
					}
					//////TAINT ANALYSIS//////////
					if(isset($this->scan_functions[$token_value])
						&& !(($this->tokens[$i + 1] === '(' && $this->tokens[$i + 2] === ')') || $this->tokens[$i + 1] === ';') //skip function with empty parameter list
					)
					{

						if($this->tokens[$i + 1] === '(')
							$result = $this->getTokensInfo(array_slice($this->tokens, $i + 2, $this->getBraceEnd($this->tokens, $i + 2) - 1), false, $token_value);
						else
							$result = $this->getTokensInfo(array_slice($this->tokens, $i + 1, $this->getBraceEnd($this->tokens, $i + 1)), false, $token_value);

						if($result !== false)
						{
							if($this->tokens[$i + 1] === '(')
								$result = $this->getTokensInfo(array_slice($this->tokens, $i + 2, $this->getBraceEnd($this->tokens, $i + 2) - 1), false, $token_value);
							else
								$result = $this->getTokensInfo(array_slice($this->tokens, $i + 1, $this->getBraceEnd($this->tokens, $i + 1)), false, $token_value);


							$tainted_vars = array();
							foreach ($result[1] as $res)
							{
								if(!$res['secure'] && !isset($tainted_vars[$res['var_name']]))
								{
									if($res['requestInitialization'] || !array_key_exists($token_value, $this->vuln_func['XSS']))
									{
										$tainted_vars[$res['var_name']] = '<div class="checklist-vulnscan-code">';
										$tainted_vars[$res['var_name']].= $this->traverseVar($res['var_name'], $i);
										$tainted_vars[$res['var_name']].= '</div>';
										$this->vuln_count++;
									}
								}
							}

							if(!empty($tainted_vars))
							{
								//little hack for $DB->Query, TODO: Fix this later!
								if($token_value === 'query')
									$pos = $i - 2;
								else $pos = $i;

								$vuln = new CVuln(
									$this->scanning_file,
									$cur_line,
									$token_value,
									array_slice($this->tokens, $pos, $this->getBraceEnd($this->tokens, $pos)),
									$this->dependencies,
									$tainted_vars,
									$this->comment
								);

								$this->arResult[] = $vuln;
							}

						}
					}
				}
				elseif(in_array($token, $this->tokens_type['FLOW_CONTROL']))
				{
					$c = 1;
					while ($this->tokens[$i + $c] !== '{' && ($i + $c) < count($this->tokens))
						$c++;

					$this->dependency = array_slice($this->tokens, $i, $c);
					$this->dependency_line = $cur_line;
				}
				elseif($token === T_INCLUDE_END)
				{
					array_pop($this->file_history);
					$this->current_file = & end($this->file_history);
					$this->comment = basename($this->current_file) == basename($this->scanning_file) ? '' : basename($this->current_file);
				}

				//dependency check
				if($token === T_EXIT && count($this->dependencies) === 1 && $this->dependencyHave(end($this->dependencies), '!XSS'))
					$this->global_xss_ignore = true;

				//end of dependency check
			}
			else
			{
				if($this->tokens[$i] === '{'
					&& ($this->tokens[$i - 1] === ')' || $this->tokens[$i - 1] === ':'
					|| (is_array($this->tokens[$i - 1]) && $this->tokens[$i - 1][0] === T_ELSE))
				)
				{
					if(count($this->dependency) > 2
						&& $this->dependency[0][0] === T_ELSE && $this->dependency[1][0] !== T_IF
					)
					{
						$this->dependency = $this->last_dependency;
						$this->dependency[] = array(T_ELSE, 'else', $this->dependency[0][2]);
					}
					$this->dependencies[$this->dependency_line] = $this->dependency;

					if($this->dependencyHave($this->dependency, 'XSS'))
						$this->search_xss = false;

					$this->dependency = array();
					$this->dependency_line = 'syntax_error';
					$this->braces++;
				}
				elseif($this->tokens[$i] === '}'
					&& ($this->tokens[$i - 1] === ';' || $this->tokens[$i - 1] === '}' || $this->tokens[$i - 1] === '{')
				)
				{
					$this->braces--;

					$this->last_dependency = array_pop($this->dependencies);

					if($this->dependencyHave($this->last_dependency, 'XSS'))
						$this->search_xss = true;

					$this->dependency = array();
				}
			}
		}
		return true;
	}

	private static function tokenToString($token)
	{
		$result = '';
		for ($i = 0, $count = count($token); $i < $count; $i++)
		{
			if(is_array($token[$i]))
				$result .= self::tokenToString($token[$i]);
			else
				$result .= $token[$i][1];
		}
		return $result;
	}

	private function getVarName($token, $level = -1)
	{
		$var_name = $token[1];
		if(is_array($token[3]))
		{
			for ($i = 0, $count = count($token[3]); $i < $count && ($i < $level || $level === -1); $i++)
			{
				if(is_array($token[3][$i]))
				{
					/*
									// TODO: to fix with foreach
									$res=$this->getTokensInfo($token[3][$i], true);
									if($res === false)
										$var_name .='['.self::tokenToString($token[3][$i]).']';
					*/
				}
				else
				{
					$var_name .= '['.$token[3][$i].']';
				}
			}
		}
		return $var_name;
	}

	private function clearVariables($var)
	{
		$this->variables;
		if(!empty($this->variables))
		{
			foreach (array_keys($this->variables) as $key)
			{
				if(preg_match('/'.preg_quote($var).'\[/is', $key))
					unset($this->variables[$key]);
			}
		}

	}

	private function addVariable($var, $id, $line, $start, $end, $comment = '', $customTokens = array())
	{

		$tokens = !empty($customTokens) ? $customTokens : array_slice($this->tokens, $start, $end);
		$tokensForScan = !empty($customTokens) ? $customTokens : array_slice($this->tokens, $start + 2, $end - 2);
		$tokensInfo = $this->getTokensInfo($tokensForScan);

		$dependencies = array();
		/*TODO: Use dependency to detect overwritten variable!
		* foreach ($this->dependencies as $dep_line => $dependency)
		{
			if(!empty($dependency))
				$dependencies[$dep_line] = $dependency;
		}*/

		$varName = self::getVarName($var);
		$this->clearVariables($varName);
		if($tokensInfo !== false)
		{
			$taintedVars = array();
			foreach ($tokensInfo[1] as $res)
				$taintedVars[] = $res['varName'];
			
			if(!isset($this->variables[$varName]))
				$var = new CVariable($varName);
			else
				$var = $this->variables[$varName];

			$var->have_user_input = true;
			$var->secure = $tokensInfo[0];
			$var->requestInitialization = $tokensInfo[2];
			if (!$this->search_xss)
				$var->requestInitialization = false;
			$var->newDeclare($id, $line, $start, $end, $tokens, $this->comment.$comment, $dependencies, $taintedVars);
			$this->variables[$varName] = $var;
		}
		elseif(isset($this->variables[$varName]))
		{
			unset($this->variables[$varName]); //TODO: Fix this, with dependency overwritten!
		}
	}

	public function tokenize($code, $component_template = '')
	{
		if(preg_match_all('/\$GLOBALS\[\'[_0-9]*\'\]/', $code, $mat) > 20)
			return array();
		$tokens = token_get_all($code);
		$tokens = $this->prepareTokens($tokens);
		$tokens = $this->reconstructArray($tokens);
		$tokens = $this->fixTokens($tokens, $component_template);
		$tokens = $this->removeTernary($tokens);


		return $tokens;
	}

	private function prepareTokens($tokens)
	{
		for ($i = 0, $c = count($tokens); $i < $c; $i++)
		{
			if(is_array($tokens[$i]))
			{
				if(in_array($tokens[$i][0], $this->tokens_type['IGNORE']))
					unset($tokens[$i]);
				elseif($tokens[$i][0] === T_CLOSE_TAG)
					if($tokens[$i - 1] !== '{' && $tokens[$i - 1] !== ';')
						$tokens[$i] = ';'; else
						unset($tokens[$i]);
				elseif($tokens[$i][0] === T_OPEN_TAG_WITH_ECHO)
					$tokens[$i][1] = 'echo';
				elseif($tokens[$i][0] == T_CLASS)
				{
					unset($tokens[$i]);
					$braces = 1;
					$f = 1;
					while ($tokens[$i + $f] !== '{' && ($i + $f) < $c)
					{
						unset($tokens[$i + $f]);
						$f++;
					}
				
					$f++;
					while ($braces !== 0 && ($i + $f) < $c)
					{
						if($tokens[$i + $f] === '{')
							$braces++;
						elseif($tokens[$i + $f] === '}')
							$braces--;
						unset($tokens[$i + $f]);
						$f++;
					}
				
					for ($j = $i; $j < $i + $f + 1; $j++)
						unset($tokens[$j]);
					$i += $f;
				}
				elseif($tokens[$i][0] == T_FUNCTION)
				{
					unset($tokens[$i]);

					if(is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_STRING)
					{
						$this->sec_func['STRING'][] = self::strtolower($tokens[$i + 1][1]);
					}
					else
					{
						$f = 1;
						while ($tokens[$i + $f] !== '(' && ($i + $f) < $c)
						{
							$f++;
						}
						if(is_array($tokens[$i + $f - 1]) && $tokens[$i + $f - 1][0] === T_STRING)
						{
							$this->sec_func['STRING'][] = self::strtolower($tokens[$i + $f - 1][1]);
						}
					}

					$f = 1;
					$braces = 1;
					while ($tokens[$i + $f] !== '{' && ($i + $f) < $c)
					{
						unset($tokens[$i + $f]);
						$f++;
					}
					$f++;
					while ($braces !== 0 && ($i + $f) < $c)
					{
						if($tokens[$i + $f] === '{')
							$braces++;
						elseif($tokens[$i + $f] === '}')
							$braces--;
						unset($tokens[$i + $f]);
						$f++;
					}
					for ($j = $i; $j < $i + $f + 1; $j++)
						unset($tokens[$j]);
					$i += $f;
				}
			}
			elseif($tokens[$i] === '@')
			{
				unset($tokens[$i]);
			}
		}

		return array_values($tokens);
	}

	private function wrapBraces($tokens, $start, $between, $end)
	{

		$tokens = array_merge(
			array_slice($tokens, 0, $start),
			array('{'),
			array_slice($tokens, $start, $between),
			array('}'),
			array_slice($tokens, $end)
		);

		return $tokens;
	}

	private function fixTokens($tokens, $component_template = '')
	{
		for ($i = 0, $max = count($tokens); $i < $max; $i++)
		{
			if($tokens[$i] === '`')
			{
				$f = 1;
				while ($tokens[$i + $f] !== '`' && $i < $max)
				{
					if(is_array($tokens[$i + $f]))
						$line = $tokens[$i + $f][2];

					$f++;
					if(!isset($tokens[$i + $f]) || $tokens[$i + $f] === ';')
					{
						break;
					}
				}
				if(!empty($line))
				{
					$tokens[$i + $f] = ')';
					$tokens[$i] = array(T_STRING, 'backticks', $line);

					$tokens = array_merge(
						array_slice($tokens, 0, $i + 1), array('('), array_slice($tokens, $i + 1)
					);
				}
			}
			elseif(is_array($tokens[$i]))
			{
				if(is_array($tokens[$i]) && (self::strtolower($tokens[$i][1]) === 'includecomponenttemplate' || self::strtolower($tokens[$i][1]) === 'initcomponenttemplate'))
				{
					$tokens[$i][3] = $component_template;
					$tokens[$i][1] = self::strtolower($tokens[$i][1]);
					$tokens[$i][0] = T_INCLUDE_COMPONENTTEMPLATE;
					$tmp = array(array(T_INCLUDE_RESULT_MODIFIER, 'include_result_modifier', $tokens[$i][2], $component_template));
					array_splice($tokens, $i - 1, 0, $tmp);
					$i++;
				}
				elseif(is_array($tokens[$i]) && (self::strtolower($tokens[$i][1]) == 'includecomponent'))
				{
					$tokens[$i][1] = self::strtolower($tokens[$i][1]);
					$tokens[$i][0] = T_INCLUDE_COMPONENT;
				}
				elseif(($tokens[$i][0] === T_IF || $tokens[$i][0] === T_ELSEIF || $tokens[$i][0] === T_FOR
					|| $tokens[$i][0] === T_FOREACH || $tokens[$i][0] === T_WHILE) && $tokens[$i + 1] === '('
				)
				{
					$f = 2;
					$braceopen = 1;
					while ($braceopen !== 0 && ($i + $f) < $max)
					{
						if($tokens[$i + $f] === '(')
							$braceopen++;
						elseif($tokens[$i + $f] === ')')
							$braceopen--;
						$f++;

						if(!isset($tokens[$i + $f]))
						{
							break;
						}
					}

					if($tokens[$i + $f] === ':')
					{
						switch ($tokens[$i][0])
						{
							case T_IF:
							case T_ELSEIF:
								$endtoken = T_ENDIF;
								break;
							case T_FOR:
								$endtoken = T_ENDFOR;
								break;
							case T_FOREACH:
								$endtoken = T_ENDFOREACH;
								break;
							case T_WHILE:
								$endtoken = T_ENDWHILE;
								break;
							default:
								$endtoken = ';';
						}

						$c = 1;
						while ($tokens[$i + $f + $c][0] !== $endtoken)
						{
							$c++;
							if(!isset($tokens[$i + $f + $c]))
							{
								break;
							}
						}
						$tokens = $this->wrapBraces($tokens, $i + $f + 1, $c + 1, $i + $f + $c + 2);
					}
					elseif($tokens[$i + $f] !== '{' && $tokens[$i + $f] !== ';')
					{
						$c = 1;
						while ($tokens[$i + $f + $c] !== ';' && $c < $max)
						{
							$c++;
						}
						$tokens = $this->wrapBraces($tokens, $i + $f, $c + 1, $i + $f + $c + 1);
					}
				}
				elseif($tokens[$i][0] === T_ELSE
					&& $tokens[$i + 1][0] !== T_IF
					&& $tokens[$i + 1] !== '{'
				)
				{
					$f = 2;
					while ($tokens[$i + $f] !== ';' && $f < $max)
					{
						$f++;
					}
					$tokens = $this->wrapBraces($tokens, $i + 1, $f, $i + $f + 1);
				}
				elseif($tokens[$i][0] === T_SWITCH && $tokens[$i + 1] === '(')
				{
					$braces = 1;
					$c = 2;
					while ($braces !== 0)
					{
						if($tokens[$i + $c] === '(')
						{
							$braces++;
						}
						elseif($tokens[$i + $c] === ')')
						{
							$braces--;
						}
						elseif(!isset($tokens[$i + $c]) || $tokens[$i + $c] === ';')
						{
							break;
						}
						$c++;
					}
					if($tokens[$i + $c] === ':')
					{
						$f = 1;
						while ($tokens[$i + $c + $f][0] !== T_ENDSWITCH)
						{
							$f++;
							if(!isset($tokens[$i + $c + $f]))
								break;
						}
						$tokens = $this->wrapBraces($tokens, $i + $c + 1, $f + 1, $i + $c + $f + 2);
					}
				}
				elseif($tokens[$i][0] === T_CASE)
				{
					$e = 1;
					while ($tokens[$i + $e] !== ':' && $tokens[$i + $e] !== ';')
					{
						$e++;

						if(!isset($tokens[$i + $e]))
							break;
					}
					$f = $e + 1;
					if(($tokens[$i + $e] === ':' || $tokens[$i + $e] === ';')
						&& $tokens[$i + $f] !== '{'
						&& $tokens[$i + $f][0] !== T_CASE && $tokens[$i + $f][0] !== T_DEFAULT
					)
					{
						$braces = 0;
						while ($braces || (isset($tokens[$i + $f]) && $tokens[$i + $f] !== '}'
							&& !(is_array($tokens[$i + $f])
								&& ($tokens[$i + $f][0] === T_BREAK || $tokens[$i + $f][0] === T_CASE
									|| $tokens[$i + $f][0] === T_DEFAULT || $tokens[$i + $f][0] === T_ENDSWITCH
								)
							)
						))
						{
							if($tokens[$i + $f] === '{')
								$braces++;
							elseif($tokens[$i + $f] === '}')
								$braces--;
							$f++;

							if(!isset($tokens[$i + $f]))
								break;
						}
						if($tokens[$i + $f][0] === T_BREAK)
						{
							if($tokens[$i + $f + 1] === ';')
								$tokens = $this->wrapBraces($tokens, $i + $e + 1, $f - $e + 1, $i + $f + 2);
							else
								$tokens = $this->wrapBraces($tokens, $i + $e + 1, $f - $e + 2, $i + $f + 3);
						}
						else
						{
							$tokens = $this->wrapBraces($tokens, $i + $e + 1, $f - $e - 1, $i + $f);
						}
						$i++;
					}
				}
				elseif($tokens[$i][0] === T_DEFAULT
					&& $tokens[$i + 2] !== '{'
				)
				{
					$f = 2;
					$braces = 0;
					while ($tokens[$i + $f] !== ';' && $tokens[$i + $f] !== '}' || $braces)
					{
						if($tokens[$i + $f] === '{')
							$braces++;
						elseif($tokens[$i + $f] === '}')
							$braces--;
						$f++;

						if(!isset($tokens[$i + $f]))
							break;
					}
					$tokens = $this->wrapBraces($tokens, $i + 2, $f - 1, $i + $f + 1);
				}
				elseif($tokens[$i][0] === T_FUNCTION)
				{
					$tokens[$i + 1][1] = self::strtolower($tokens[$i + 1][1]);
				}
				elseif($tokens[$i][0] === T_STRING && $tokens[$i + 1] === '(')
				{
					$tokens[$i][1] = self::strtolower($tokens[$i][1]);
				}
				elseif($tokens[$i][0] === T_DO)
				{
					$f = 2;
					$otherDOs = 0;
					while ($tokens[$i + $f][0] !== T_WHILE || $otherDOs)
					{
						if($tokens[$i + $f][0] === T_DO)
							$otherDOs++;
						elseif($tokens[$i + $f][0] === T_WHILE)
							$otherDOs--;
						$f++;

						if(!isset($tokens[$i + $f]))
							break;
					}

					if($tokens[$i + 1] !== '{')
					{
						$tokens = $this->wrapBraces($tokens, $i + 1, $f - 1, $i + $f);
						$f += 2;
					}

					$d = 1;
					while ($tokens[$i + $f + $d] !== ';' && $d < $max)
					{
						$d++;
					}

					$tokens = array_merge(
						array_slice($tokens, 0, $i),
						array_slice($tokens, $i + $f, $d),
						array_slice($tokens, $i + 1, $f - 1),
						array_slice($tokens, $i + $f + $d + 1, count($tokens))
					);
				}
			}
		}
		return array_values($tokens);
	}

	private function reconstructArray($tokens)
	{
		for ($i = 0, $max = count($tokens); $i < $max; $i++)
		{
			if(is_array($tokens[$i]) && $tokens[$i][0] === T_VARIABLE && $tokens[$i + 1] === '[')
			{
				$tokens[$i][3] = array();
				$has_more_keys = true;
				$index = -1;
				$c = 2;

				while ($has_more_keys && $index < 20)
				{
					$index++;
					if(($tokens[$i + $c][0] === T_CONSTANT_ENCAPSED_STRING || $tokens[$i + $c][0] === T_LNUMBER || $tokens[$i + $c][0] === T_NUM_STRING) && $tokens[$i + $c + 1] === ']')
					{
						unset($tokens[$i + $c - 1]);
						$tokens[$i][3][$index] = str_replace(array('"', "'"), '', $tokens[$i + $c][1]);
						unset($tokens[$i + $c]);
						unset($tokens[$i + $c + 1]);
						$c += 2;
					}
					else
					{
						$tokens[$i][3][$index] = array();
						$braces = 1;
						unset($tokens[$i + $c - 1]);
						while ($braces !== 0 && $c < 100)
						{
							if($tokens[$i + $c] === '[')
							{
								$braces++;
							}
							elseif($tokens[$i + $c] === ']')
							{
								$braces--;
							}
							else
							{
								$tokens[$i][3][$index][] = $tokens[$i + $c];
							}
							unset($tokens[$i + $c]);
							$c++;
						}
						unset($tokens[$i + $c - 1]);
					}
					if($tokens[$i + $c] !== '[')
						$has_more_keys = false;
					$c++;
				}

				$i += $c - 1;
			}
		}

		return array_values($tokens);
	}

	private function removeTernary($tokens)
	{
		$max = count($tokens);
		$i = 0;

		while ($i < $max)
		{
			if($tokens[$i] === '?')
			{
				unset($tokens[$i]);

				$k = 1;
				$braces = 0;
				while (!(($tokens[$i - $k] === ';' || $tokens[$i - $k] === ', ' || $tokens[$i - $k] === '.' || $tokens[$i - $k] === '{' || $tokens[$i - $k] === '}') && $braces <= 0) && ($i - $k) > 0)
				{
					if($tokens[$i - $k] === ')')
						$braces++;
					elseif($tokens[$i - $k] === '(')
						$braces--;
					unset($tokens[$i - $k]);
					$k++;
				}

				$k = 1;
				$braces = 0;
				while (!(($tokens[$i + $k] === ';' || $tokens[$i + $k] === ', ' || $tokens[$i + $k] === '.' || $tokens[$i + $k] === '}') && $braces <= 0) && ($i + $k) < $max)
				{
					if($tokens[$i + $k] === '(')
						$braces++;
					elseif($tokens[$i + $k] === ')')
						$braces--;
					unset($tokens[$i + $k]);
					$k++;
				}
				$i += $k;
			}
			$i++;
		}

		return array_values($tokens);
	}

	private function getTokensValue($file_name, $tokens, $start = 0, $stop = 0)
	{
		$value = '';
		if(!$stop)
			$stop = count($tokens);
		for ($i = $start; $i < $stop; $i++)
		{
			if(is_array($tokens[$i]))
			{
				if($tokens[$i][0] === T_VARIABLE
					|| ($tokens[$i][0] === T_STRING
						&& ($i + 1) < $stop && $tokens[$i + 1] !== '(')
				)
				{
					if(!in_array($tokens[$i][1], $this->v_userinput))
					{
						if($tokens[$i][1] === 'DIRECTORY_SEPARATOR' || $tokens[$i][1] === 'PATH_SEPARATOR')
							$value .= '/';
						elseif(self::strtolower($tokens[$i][1]) === '$componentpath')
							$value = dirname($file_name);
						elseif($tokens[$i][1] === '$_SERVER' && $tokens[$i][3][0] === 'DOCUMENT_ROOT')
							$value .= $this->arParams['doc_root_path'];
					}
				}
				elseif($tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING
				)
				{

					$value .= substr($tokens[$i][1], 1, -1);
				}
				elseif($tokens[$i][0] === T_ENCAPSED_AND_WHITESPACE)
				{
					$value .= $tokens[$i][1];
				}
				elseif($tokens[$i][0] === T_FILE
					&& ($i > 2 && $tokens[$i - 2][0] === T_STRING && $tokens[$i - 2][1] === 'dirname')
				)
				{
					$value = dirname($file_name).'/';
				}
				elseif($tokens[$i][0] === T_LNUMBER || $tokens[$i][0] === T_DNUMBER || $tokens[$i][0] === T_NUM_STRING)
				{
					$value .= round($tokens[$i][1]);
				}
				elseif($tokens[$i][0] === T_AS)
				{
					break;
				}
			}
		}

		return $value;
	}

	private function getBraceEnd($tokens, $i)
	{
		$c = 1;
		$braces = 1;
		$max=count($tokens);
		while (!($braces === 0 || $tokens[$i + $c] === ';'))
		{
			if($tokens[$i + $c] === '(')
			{
				$braces++;
			}
			elseif($tokens[$i + $c] === ')')
			{
				$braces--;
			}
			if($c > 50 || $c > $max)
				break;
			$c++;
		}
		return $c;
	}

	private function getColor($token)
	{
		if(array_key_exists($token, $this->color))
			return $this->color[$token];
		else
			return '#007700';
	}

	private function highlightArray($token)
	{
		$result = '';
		foreach ($token as $key)
		{
			if($key != '*')
			{
				$result .= '<span style="color: #007700;">[</span>';
				if(!is_array($key))
				{
					if(is_numeric($key))
						$result .= '<span style="color: #0000BB;">'.$key.'</span>';
					else
						$result .= '<span style="color: #DD0000;">\''.htmlentities($key, ENT_QUOTES, 'utf-8').'\'</span>';
				}
				else
				{
					foreach ($key as $token)
					{
						if(is_array($token))
						{
							if(isset($token[3]))
								$result .= $this->highlightArray($token[3]);
							else
								$result .= '<span style="color: '.$this->getColor($token[0]).';">'.htmlentities($token[1], ENT_QUOTES, 'utf-8').'</span>';
						}
						else
							$result .= "<span style=\"color: #007700;\">{$token}</span>";
					}
				}
				$result .= '<span style="color: #007700;">]</span>';
			}
		}
		return $result;
	}

	private function highlightLine($line, $tokens = array(), $tainted_vars = array(), $comment = '')
	{
		$output = '';
		for ($i = 0, $count = count($tainted_vars); $i < $count; $i++)
		{
			if($pos = strpos($tainted_vars[$i], '['))
				$tainted_vars[$i] = substr($tainted_vars[$i], 0, $pos);
		}
		if(isset($line))
			$output .= "<span>$line:</span>&nbsp;";

		for ($i = 0, $count = count($tokens); $i < $count; $i++)
		{
			$token = $tokens[$i];
			if(is_string($token))
			{
				if($token === ', ' || $token === ';')
					$output .= "<span style=\"color: #007700;\">$token&nbsp;</span>";
				elseif(in_array($token, $this->tokens_type['SPACE_WRAP_STR']) || in_array($token, $this->tokens_type['ARITHMETIC_STR']))
					$output .= '<span style="color: #007700;">&nbsp;'.$token.'&nbsp;</span>';
				else
					$output .= '<span style="color: #007700;">'.htmlentities($token, ENT_QUOTES, 'utf-8').'</span>';
			}
			elseif(is_array($token)
				&& $token[0] !== T_OPEN_TAG
				&& $token[0] !== T_CLOSE_TAG
			)
			{

				if(in_array($token[0], $this->tokens_type['SPACE_WRAP']) || in_array($token[0], $this->tokens_type['OPERATOR']) || in_array($token[0], $this->tokens_type['ASSIGNMENT']))
				{
					$output .= '&nbsp;<span style="color: '.$this->getColor($token[0]).";\">{$token[1]}</span>&nbsp;";
				}
				else
				{
					$text = htmlentities($token[1], ENT_QUOTES, 'utf-8');
					$text = str_replace(array(' ', "\n"), '&nbsp;', $text);

					if($token[0] === T_FUNCTION)
						$text .= '&nbsp;';

						$span = "<span ";

						if($token[0] === T_VARIABLE && is_array($tainted_vars) && in_array($token[1], $tainted_vars))
							$span .= "style=\"color: #0000BB;\"><b>".$text."</b></span>";
						else
							$span .= "style=\"color: ".$this->getColor($token[0]).";\">$text</span>";

						$text = $span;

						if(isset($token[3]))
						{
							$text .= $this->highlightArray($token[3]);
						}

					$output .= $text;
					if(is_array($token) && (in_array($token[0], $this->tokens_type['INCLUDES']) || in_array($token[0], $this->tokens_type['XSS']) || $token[0] === 'T_EVAL'))
						$output .= '&nbsp;';
				}
			}
		}

		if(!empty($comment))
			$output .= '&nbsp;<span style="color: #808080;">// '.htmlentities($comment, ENT_QUOTES, 'utf-8').'</span>';

		return '<div style="clear:both;">'.$output.'</div>';
	}

	private function getVulnNodeTitle($func_name)
	{
		if(isset($this->vuln_func['XSS'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_XSS_NAME');
		elseif(isset($this->vuln_func['HTTP_HEADER'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_HEADER_NAME');
		elseif(isset($this->vuln_func['DATABASE'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_DATABASE_NAME');
		elseif(isset($this->vuln_func['FILE_INCLUDE'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_INCLUDE_NAME');
		elseif(isset($this->vuln_func['EXEC'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_EXEC_NAME');
		elseif(isset($this->vuln_func['CODE'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_CODE_NAME');
		elseif(isset($this->vuln_func['POP'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_POP_NAME');
		elseif(isset($this->vuln_func['OTHER'][$func_name]))
			$vulnname = GetMessage('VULNSCAN_OTHER_NAME');
		else
			$vulnname = GetMessage('VULNSCAN_UNKNOWN');

		return $vulnname;
	}

	private function getVulnNodeDescription($func_name)
	{
		if(isset($this->vuln_func['XSS'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_XSS_HELP');
		elseif(isset($this->vuln_func['HTTP_HEADER'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_HEADER_HELP');
		elseif(isset($this->vuln_func['DATABASE'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_DATABASE_HELP');
		elseif(isset($this->vuln_func['FILE_INCLUDE'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_INCLUDE_HELP');
		elseif(isset($this->vuln_func['EXEC'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_EXEC_HELP');
		elseif(isset($this->vuln_func['CODE'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_CODE_HELP');
		elseif(isset($this->vuln_func['POP'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_POP_HELP');
		elseif(isset($this->vuln_func['OTHER'][$func_name]))
			$vulnhelp = GetMessage('VULNSCAN_OTHER_HELP');
		else
			$vulnhelp = GetMessage('VULNSCAN_UNKNOWN_HELP');

		return $vulnhelp;
	}

	private function getVulnName($func_name)
	{
		if (isset($this->vuln_func['XSS'][$func_name]))
			return 'XSS';
		elseif (isset($this->vuln_func['HTTP_HEADER'][$func_name]))
			return 'HEADER';
		elseif (isset($this->vuln_func['DATABASE'][$func_name]))
			return 'DATABASE';
		elseif (isset($this->vuln_func['FILE_INCLUDE'][$func_name]))
			return 'INCLUDE';
		elseif (isset($this->vuln_func['EXEC'][$func_name]))
			return 'EXEC';
		elseif (isset($this->vuln_func['CODE'][$func_name]))
			return 'CODE';
		elseif (isset($this->vuln_func['POP'][$func_name]))
			return 'POP';
		elseif (isset($this->vuln_func['OTHER'][$func_name]))
			return 'OTHER';
		else
			return 'UNKNOWN';
	}

	private function traverseVar($var, $id = -1)
	{
		$result = '';
		if(isset($this->variables[$var]))
		{
			$cur_var = $this->variables[$var];
			foreach ($cur_var->declares as $var_declare)
			{
				if($var_declare->id < $id || $id === -1)
				{
					foreach ($var_declare->tainted_vars as $taint_var)
					{
						$res = $this->traverseVar($taint_var, $var_declare->id);
						if($res && strpos($result, $res) === false)
							$result .= $res;
					}

					$result .= '<div class="checklist-vulnscan-code-line">';
					$result .= $this->highlightLine($var_declare->line, $var_declare->tokens, $var_declare->tainted_vars, $var_declare->comment);
					$result .= '</div>';
				}
			}
		}
		return $result;
	}

	private function dependenciesTraverse($dependencies = array())
	{
		$result = '';
		if(!empty($dependencies))
		{
			$result .= GetMessage('VULNSCAN_REQUIRE').':';

			foreach ($dependencies as $line => $dependency)
			{
				if(!empty($dependency))
				{
					$result .= $this->highlightLine($line, $dependency);
				}
			}
		}
		return $result;
	}

	private static function searchSimilarVuln($output, $max)
	{
		for ($i = 0; $i < $max; $i++)
		{
			if(($output[$i]->name === $output[$i]->name) && ($output[$i]->filename === $output[$i]->filename) && $output[$i]->tainted_vars === $output[$max]->tainted_vars)
				return $i;
		}
		return false;
	}

	private function prepareOutput($output)
	{

		for ($i = 0, $count = count($output); $i < $count; $i++)
		{
			if(($find = self::searchSimilarVuln($output, $i)) !== false)
			{
				$output[$find]->additional_text .= '<div class="checklist-vulnscan-dangerous-is-here">';
				$output[$find]->additional_text .= $this->highlightLine($output[$i]->line, $output[$i]->tokens, key($output[$i]->tainted_vars), $output[$i]->comment);
				$output[$find]->additional_text .= '</div>';
				unset($output[$i]);
			}
		}
		return $output;
	}

	private function getHelp($category)
	{
		$result = '';
		/*
		$result = '<div class="checklist-vulnscan-helpbox-sheme">';
		$result .= '<table>';
		$result .= '<tr><td>'.GetMessage('VULNSCAN_HELP_INPUT').'</td><td>'.GetMessage('VULNSCAN_HELP_FUNCTION').'</td><td>'.GetMessage('VULNSCAN_HELP_VULNTYPE').'</td></tr>';
		$result .= '<tr><td>';

		if (!empty($tree->source))
			$result .= $tree->source;
		else
			$result .= '$_GET';

		$result .= '</td><td>';
		$result .= $tree->name.'()';
		$result .= '</td><td>';
		$result .= $this->getVulnNodeTitle($category);
		$result .= '</td></tr>';
		$result .= '</table></div>';*/
		$result .= '<div class="checklist-vulnscan-helpbox-description">';
		$result .= GetMessage('VULNSCAN_'.$this->getVulnName($category).'_HELP');
		$result .= '</div>';
		$result .= '<div class="checklist-vulnscan-helpbox-safe-title">';
		$result .= GetMessage('VULNSCAN_HELP_SAFE');
		$result .= '</div>';
		$result .= '<div class="checklist-vulnscan-helpbox-safe-description">';
		$result .= GetMessage('VULNSCAN_'.$this->getVulnName($category).'_HELP_SAFE');
		$result .= '</div>';
		return $result;
	}


	public function getOutput()
	{
		$output = $this->prepareOutput($this->arResult);
		$result = '';
		if(!empty($output))
		{
			foreach ($output as $vuln)
			{

				$filename = htmlspecialcharsbx(str_replace(realpath(trim($this->arParams['doc_root_path'])), '',str_replace(realpath(trim($this->arParams['path'])), '', realpath(trim($vuln->filename)))));

				foreach ($vuln->tainted_vars as $tainted_var_name => $tainted_var)
				{
					$result .= '<div class="checklist-dot-line"></div><div class="checklist-vulnscan-files">'.'<span class="checklist-vulnscan-filename">'.GetMessage('VULNSCAN_FILE').': '.$filename.'</span>'.'<div id="'.$filename.'">';
					$result .= '<div class="checklist-vulnscan-vulnblock">'.'<div class="checklist-vulnscan-vulnscan-blocktitle">'.GetMessage('VULNSCAN_'.$this->getVulnName($vuln->name).'_NAME').'</div>';
					$result .= '<div style="visibility: hidden; display:none;" class="checklist-vulnscan-helpbox" data-help="'.$filename.'">'.$this->getHelp($vuln->name).'</div>';
					$result .= $tainted_var;

					$result .= '<div class="checklist-vulnscan-dangerous-is-here">';
					$result .= $this->highlightLine($vuln->line, $vuln->tokens, array($tainted_var_name), $vuln->comment);
					$result .= '</div>';

					$result .= '<div class="checklist-vulnscan-dependecies">';
					$result .= $this->dependenciesTraverse($vuln->dependencies);
					$result .= '</div>';

					if(!empty($vuln->additional_text))
						$result .= "\n".'<div><div class="checklist-vulnscan-vulnblocktitle">'.GetMessage('VULNSCAN_SIMILAR').':</div><div class="checklist-vulnscan-codebox"><div class="checklist-vulnscan-code">'.$vuln->additional_text.'</div></div></div>';


					$result .= '</div></div></div>';
				}
			}
		}
		return $result;
	}

	protected static function strtolower($pString)
	{
		if(function_exists("mb_orig_strtolower"))
		{
			return mb_orig_strtolower($pString);
		}
		else
		{
			return strtolower($pString);
		}
	}

}

class CQAACheckListTests
{
	static private function getFiles($path, $skip_preg, $file_types, $doc_root, &$files, &$dirs)
	{
		$handle = opendir($path);
		if ($handle)
		{
			while (($file = readdir($handle)) !== false)
			{
				if($file === '.' || $file === '..')
					continue;

				$name = $path.'/'.str_replace("\\", "/", $file);
				if (preg_match($skip_preg, str_replace($doc_root, "", $name)))
				{
					continue;
				}

				if (is_dir($name))
				{
					$dirs[] = $name;
				}
				elseif(in_array(substr($name, -4), $file_types))
				{
					$files[] = $name;
				}
			}
		}
		closedir($handle);
	}

	static private function defineScanParams()
	{
		if(!defined('T_INCLUDE_RESULT_MODIFIER'))
			// define('T_INCLUDE_RESULT_MODIFIER', 10001);
		if(!defined('T_INCLUDE_COMPONENTTEMPLATE'))
			// define('T_INCLUDE_COMPONENTTEMPLATE', 10002);
		if(!defined('T_INCLUDE_COMPONENT'))
			// define('T_INCLUDE_COMPONENT', 10003);
		if(!defined('T_INCLUDE_END'))
			// define('T_INCLUDE_END', 10004);
		$SKIPDIR = array(// skipping directories
			'lang',
			'help',
			'images',
			'upload',
			'uploads',
			'jquery',
			'js',
			'css',
			'\/bitrix\/[^t].*',
		);

		$SKIPFILE = array(// skipping files
			'\.access\.php',
			'\.description\.php',
			'\.parameters\.php',
			'install\/[\w]*\.php',
		);

		$arResult = Array(
			'FILE_TYPES' => Array(
				'.php',
				'.inc'
			),
			'PREG_FOR_SKIP_INCLUDE' => '/\/modules\/(bitrix\.)?[^\W\.]+\//is',
			'PREG_FOR_SKIP_SCAN' => '/(\/bitrix\/modules\/)|(\/bitrix\/components\/bitrix\/)|('.implode('$)|(', $SKIPDIR).'$)|('.implode('$)|(', $SKIPFILE).'$)/is',
			//	'VERBOSITY' => 1,
			'MAX_TRACE' => 30,
			'MAX_ARRAY_ELEMENTS' => 50,
			'MP_mode' => false,
			'production_mode' => false,
			'path' => $_SERVER['DOCUMENT_ROOT'],
			'doc_root_path' => $_SERVER['DOCUMENT_ROOT']
		);

		$arResult['TOKENS_TYPES'] = Array(
			'IGNORE' => Array(
				T_BAD_CHARACTER,
				T_DOC_COMMENT,
				T_COMMENT,
				T_INLINE_HTML,
				T_WHITESPACE,
				T_OPEN_TAG
			),
			'LOOP_CONTROL' => Array(
				T_WHILE,
				T_FOR,
				T_FOREACH
			),
			'FLOW_CONTROL' => Array(
				T_IF,
				T_SWITCH,
				T_CASE,
				T_ELSE,
				T_ELSEIF
			),
			'ASSIGNMENT' => Array(
				T_AND_EQUAL,
				T_CONCAT_EQUAL,
				T_DIV_EQUAL,
				T_MINUS_EQUAL,
				T_MOD_EQUAL,
				T_MUL_EQUAL,
				T_OR_EQUAL,
				T_PLUS_EQUAL,
				T_SL_EQUAL,
				T_SR_EQUAL,
				T_XOR_EQUAL
			),
			'ASSIGNMENT_SECURE' => Array(
				T_DIV_EQUAL,
				T_MINUS_EQUAL,
				T_MOD_EQUAL,
				T_MUL_EQUAL,
				T_OR_EQUAL,
				T_PLUS_EQUAL,
				T_SL_EQUAL,
				T_SR_EQUAL,
				T_XOR_EQUAL
			),
			'OPERATOR' => Array(
				T_IS_EQUAL,
				T_IS_GREATER_OR_EQUAL,
				T_IS_IDENTICAL,
				T_IS_NOT_EQUAL,
				T_IS_NOT_IDENTICAL,
				T_IS_SMALLER_OR_EQUAL
			),
			'FUNCTIONS' => Array(
				T_STRING, // all functions
				T_EVAL
			),
			'INCLUDES' => Array(
				T_INCLUDE,
				T_INCLUDE_ONCE,
				T_REQUIRE,
				T_REQUIRE_ONCE,
				T_INCLUDE_COMPONENT,
				T_INCLUDE_COMPONENTTEMPLATE,
				T_INCLUDE_RESULT_MODIFIER
			),
			'XSS' => Array(
				T_PRINT,
				T_ECHO,
				T_OPEN_TAG_WITH_ECHO,
				T_EXIT
			),
			'CASTS' => Array(
				T_BOOL_CAST,
				T_DOUBLE_CAST,
				T_INT_CAST,
				T_UNSET_CAST,
				T_UNSET
			),
			'LOGICAL' => Array(
				T_BOOLEAN_AND,
				T_BOOLEAN_OR,
				T_LOGICAL_AND,
				T_LOGICAL_OR,
				T_LOGICAL_XOR
			),
			'SPACE_WRAP' => Array(
				T_AS,
				T_BOOLEAN_AND,
				T_BOOLEAN_OR,
				T_LOGICAL_AND,
				T_LOGICAL_OR,
				T_LOGICAL_XOR,
				T_SL,
				T_SR,
				T_CASE,
				T_ELSE,
				T_GLOBAL,
				T_NEW
			),
			'ARITHMETIC' => Array(
				T_INC,
				T_DEC
			),
			'ARITHMETIC_STR' => Array(
				'+',
				'-',
				'*',
				'/',
				'%'
			),
			'SPACE_WRAP_STR' => Array(
				'.',
				'=',
				'>',
				'<',
				':',
				'?'
			)
		);
		$arResult['SECURING_FUNCTIONS'] = Array(
			'BOOL' => Array(
				'is_double',
				'is_float',
				'is_real',
				'is_long',
				'is_int',
				'is_integer',
				'ctype_alnum',
				'ctype_alpha',
				'ctype_cntrl',
				'ctype_digit',
				'ctype_xdigit',
				'ctype_upper',
				'ctype_lower',
				'ctype_space',
				'in_array',
				'preg_match',
				'preg_match_all',
				'fnmatch',
				'ereg',
				'eregi',
			),
			'STRING' => Array(
				'intval',
				'floatval',
				'doubleval',
				'filter_input',
				'urlencode',
				'rawurlencode',
				'round',
				'floor',
				'strlen',
				'hexdec',
				'strrpos',
				'strpos',
				'md5',
				'sha1',
				'crypt',
				'crc32',
				'base64_encode',
				'ord',
				'sizeof',
				'count',
				'bin2hex',
				'levenshtein',
				'abs',
				'bindec',
				'decbin',
				'hexdec',
				'rand',
				'max',
				'min',
				'preg_replace', //Fix this later
				'getimagesize',
				'phpformatdatetime',
				'mkdatetime',
				'formatdateex',
			),
			'INSTRING' => Array(
				'rawurldecode',
				'urldecode',
				'base64_decode',
				'html_entity_decode',
				'str_rot13',
				'chr',
				'htmlspecialcharsback',
			),
			'XSS' => Array(
				'htmlentities',
				'htmlspecialchars',
				'htmlspecialcharsex',
				'htmlspecialcharsbx',
				'jsescape',
				'jsurlescape',
				'phptojsobject',
				'showerror',
				'showmessage',
				'showimage',
				'shownote',
				'getcurpageparam',
				'selectbox',
				'selectboxm',
				'selectboxfromarray',
				'getmessage',
				'getvars',
				'highlight_string',
				'inputtype',
				'inputtags',
			),
			'SQL' => Array(
				'addslashes',
				'mysql_real_escape_string',
				'forsql',
			),
			'PREG' => Array(
				'preg_quote'
			),
			'FILE' => Array(
				'rel2abs'
			),
			'SYSTEM' => Array(
				'escapeshellarg',
				'escapeshellcmd',
			),
			'XPATH' => Array(
				'addslashes'
			)
		);

		$arResult['SECURING_FUNCTIONS']['QUOTE_ANALYSIS'] = Array($arResult['SECURING_FUNCTIONS']['SQL']);
		$arResult['SECURING_FUNCTIONS']['SECURES_ALL'] = array_merge(
			$arResult['SECURING_FUNCTIONS']['XSS'],
			$arResult['SECURING_FUNCTIONS']['SQL'],
			$arResult['SECURING_FUNCTIONS']['PREG'],
			$arResult['SECURING_FUNCTIONS']['FILE'],
			$arResult['SECURING_FUNCTIONS']['SYSTEM'],
			$arResult['SECURING_FUNCTIONS']['XPATH']
		);

		$arResult['VULN_FUNCTIONS'] = Array(
			'XSS' => Array(
				'echo' => array(0, $arResult['SECURING_FUNCTIONS']['XSS']),
				'print' => array(array(1), $arResult['SECURING_FUNCTIONS']['XSS']),
				'exit' => array(array(1), $arResult['SECURING_FUNCTIONS']['XSS']),
				'die' => array(array(1), $arResult['SECURING_FUNCTIONS']['XSS']),
			),
			'HTTP_HEADER' => Array(
				'header' => array(array(1), array())
			),
			'CODE' => Array(
				'assert' => Array(Array(1), Array()),
				'call_user_func' => Array(Array(1), Array()),
				'call_user_func_Array' => Array(Array(1), Array()),
				'create_function' => Array(Array(1, 2), Array()),
				'eval' => Array(Array(1), Array()),
				'mb_ereg_replace' => Array(Array(1, 2), $arResult['SECURING_FUNCTIONS']['PREG']),
				'mb_eregi_replace' => Array(Array(1, 2), $arResult['SECURING_FUNCTIONS']['PREG']),
				'ob_start' => Array(Array(1), Array()),
				//'preg_replace' => Array(Array(1, 2), $arResult['SECURING_FUNCTIONS']['PREG']),
				//'preg_replace_callback' => Array(Array(1, 2), $arResult['SECURING_FUNCTIONS']['PREG']),
			),
			'FILE_INCLUDE' => Array(
				'include' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['FILE']),
				'include_once' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['FILE']),
				'require' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['FILE']),
				'require_once' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['FILE']),
				'set_include_path' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['FILE']),
			),
			'EXEC' => Array(
				'backticks' => Array(Array(1), Array()),
				'exec' => Array(Array(1), Array()),
				'passthru' => Array(Array(1), Array()),
				'pcntl_exec' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['SYSTEM']),
				'popen' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['SYSTEM']),
				'proc_open' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['SYSTEM']),
				'shell_exec' => Array(Array(1), Array()),
				'system' => Array(Array(1), Array()),
				'mail' => Array(Array(5), Array()),
				'bxmail' => Array(Array(5), Array())
			),
			'DATABASE' => Array(
				'query' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['SQL']),
				'mysql_query' => Array(Array(1), $arResult['SECURING_FUNCTIONS']['SQL'])
			),
			'OTHER' => Array(
				'dl' => Array(Array(1), Array()),
				'ereg' => Array(Array(2), Array()),
				'eregi' => Array(Array(2), Array()),
				'sleep' => Array(Array(1), Array()),
				// It's too difficult to validate, maybe in future versions
				//'unserialize' => Array(Array(1), Array()),
				//'extract' => Array(Array(1), Array()),
				//'mb_parse_str' => Array(Array(1), Array()),
				//'parse_str' => Array(Array(1), Array()),
				//'define' => Array(Array(1), Array())
			),
			'POP' => Array(
				'unserialize' => Array(Array(1), Array()),
				'is_a' => Array(Array(1), Array())
			)
		);
		$arResult['USER_INPUTS'] = Array(
			'$_GET',
			'$_POST',
			'$_COOKIE',
			'$_REQUEST',
			//'$_FILES', // Maybe later
			//'$_SERVER', // Maybe later
			'$_ENV',
			'$HTTP_GET_VARS',
			'$HTTP_POST_VARS',
			'$HTTP_COOKIE_VARS',
			'$HTTP_REQUEST_VARS',
			'$HTTP_POST_FILES',
			'$HTTP_SERVER_VARS',
			'$HTTP_ENV_VARS',
			'$HTTP_RAW_POST_DATA',
			'$argc',
			'$argv'
		);

		$arResult['INIT_FUNCTIONS'] = Array(
			'initbvar',
			'initbvarfromarr'
		);

		$arResult['COLORS']=array(
			T_DOLLAR_OPEN_CURLY_BRACES => '#007700',
			T_CURLY_OPEN => '#007700',
			T_OPEN_TAG => '#007700',
			T_CLOSE_TAG => '#007700',
			T_AND_EQUAL => '#007700',
			T_CONCAT_EQUAL => '#007700',
			T_DIV_EQUAL => '#007700',
			T_MINUS_EQUAL => '#007700',
			T_MOD_EQUAL => '#007700',
			T_MUL_EQUAL => '#007700',
			T_OR_EQUAL => '#007700',
			T_PLUS_EQUAL => '#007700',
			T_SL_EQUAL => '#007700',
			T_SR_EQUAL => '#007700',
			T_XOR_EQUAL => '#007700',
			T_IS_EQUAL => '#007700',
			T_IS_GREATER_OR_EQUAL => '#007700',
			T_IS_IDENTICAL => '#007700',
			T_IS_NOT_EQUAL => '#007700',
			T_IS_NOT_IDENTICAL => '#007700',
			T_INC => '#007700',
			T_DEC => '#007700',
			T_OBJECT_OPERATOR => '#007700',
			T_IF => '#007700',
			T_SWITCH => '#007700',
			T_WHILE => '#007700',
			T_DO => '#007700',
			T_EXIT => '#007700',
			T_TRY => '#007700',
			T_CATCH => '#007700',
			T_ISSET => '#007700',
			T_FOR => '#007700',
			T_FOREACH => '#007700',
			T_RETURN => '#007700',
			T_DOUBLE_ARROW => '#007700',
			T_AS => '#007700',
			T_CASE => '#007700',
			T_DEFAULT => '#007700',
			T_BREAK => '#007700',
			T_CONTINUE => '#007700',
			T_GOTO => '#007700',
			T_GLOBAL => '#007700',
			T_LOGICAL_AND => '#007700',
			T_LOGICAL_OR => '#007700',
			T_EMPTY => '#007700',
			T_UNSET => '#007700',
			T_ELSE => '#007700',
			T_ELSEIF => '#007700',
			T_LIST => '#007700',
			T_ARRAY => '#007700',
			T_ECHO => '#007700',
			T_START_HEREDOC => '#007700',
			T_END_HEREDOC => '#007700',
			T_FUNCTION => '#007700',
			T_PUBLIC => '#007700',
			T_PRIVATE => '#007700',
			T_PROTECTED => '#007700',
			T_STATIC => '#007700',
			T_CLASS => '#007700',
			T_NEW => '#007700',
			T_PRINT => '#007700',
			T_INCLUDE => '#007700',
			T_INCLUDE_ONCE => '#007700',
			T_REQUIRE => '#007700',
			T_REQUIRE_ONCE => '#007700',
			T_USE => '#007700',
			T_VAR => '#007700',
			T_BOOL_CAST => '#007700',
			T_DOUBLE_CAST => '#007700',
			T_INT_CAST => '#007700',
			T_UNSET_CAST => '#007700',
			T_BOOLEAN_OR => '#007700',
			T_BOOLEAN_AND => '#007700',
			T_FILE => '#007700',
			T_LINE => '#007700',
			T_DIR => '#007700',
			T_FUNC_C => '#007700',
			T_CLASS_C => '#007700',
			T_METHOD_C => '#007700',
			T_NS_C => '#007700',
			T_CONST => '#0000BB',
			T_VARIABLE => '#0000BB',
			T_STRING_VARNAME => '#0000BB',
			T_STRING => '#0000BB',
			T_EVAL => '#0000BB',
			T_LNUMBER => '#0000BB',
			T_ENCAPSED_AND_WHITESPACE => '#DD0000;',
			T_CONSTANT_ENCAPSED_STRING => '#DD0000;',
			T_INLINE_HTML => '#000000;',
			T_COMMENT => '#FF8000;',
			T_DOC_COMMENT => '#FF8000;'
		);
		ini_set('auto_detect_line_endings', 1);
		ini_set('short_open_tag', 1);

		return $arResult;
	}

	static private function getCurTemplate($path, $mp_mode=false)
	{
		if(!$mp_mode)
		{
			$dbSiteRes=CSite::GetTemplateList(CSite::GetSiteByFullPath($path, true));
			if(($arSiteRes = $dbSiteRes->Fetch()) !== false)
				return $arSiteRes['TEMPLATE'];
		}
		return '.default';
	}
	static public function checkVulnerabilities($arParams)
	{
		if(extension_loaded('tokenizer') === true)
		{
		if(!$_SESSION['BX_CHECKLIST'][$arParams['TEST_ID']])
			$_SESSION['BX_CHECKLIST'][$arParams['TEST_ID']] = Array();
		$NS = &$_SESSION['BX_CHECKLIST'][$arParams['TEST_ID']];

		$arScanParams = self::defineScanParams();
		$phpMaxExecutionTime = ini_get("max_execution_time");
		$arScanParams['time_out'] = $phpMaxExecutionTime > 0 ? $phpMaxExecutionTime - 2: 30;
		$arScanParams['time_start'] = time();
		$arScanParams['MP_mode'] = false;

		if($arParams['STEP'] === 0)
		{
			$NS = Array();

			$NS['CUR_FILE_ID'] = 0;
			$NS['FILE_LIST'] = array();
			$NS['DIR_LIST'] = array();
			self::getFiles(
				$arScanParams['path'],
				$arScanParams['PREG_FOR_SKIP_SCAN'],
				$arScanParams['FILE_TYPES'],
				$arScanParams['path'],
				$NS['FILE_LIST'],
				$NS['DIR_LIST']
			);
			$NS['VULN_COUNT'] = 0;
			$NS['STUCK_FILE'] = -1;
			$NS['MESSAGE'] = Array();
		}

		$time_end = $arScanParams['time_start'] + $arScanParams['time_out'];
		while ($NS['DIR_LIST'] && $time_end > time())
		{
			$dir = array_shift($NS['DIR_LIST']);
			self::getFiles(
				$dir,
				$arScanParams['PREG_FOR_SKIP_SCAN'],
				$arScanParams['FILE_TYPES'],
				$arScanParams['path'],
				$NS['FILE_LIST'],
				$NS['DIR_LIST']
			);
		}

		if ($NS['DIR_LIST'])
		{
			return Array(
				'IN_PROGRESS' => 'Y',
				'PERCENT' => 0,
			);
		}

		$result=true;
		do
		{
			if(is_file($file = $NS['FILE_LIST'][$NS['CUR_FILE_ID']]))
			{
				if(isset($output))
					unset($output);
				if(isset($scan))
					unset($scan);

				$scan = new CVulnScanner($file, $arScanParams, self::getCurTemplate($file, $arScanParams['MP_mode']));
				$result = $scan->process();
				if($result !== false)
				{
					if($scan->vuln_count > 0)
					{
						$NS['MESSAGE'][$NS['CUR_FILE_ID']]['VULN_COUNT'] = $scan->vuln_count;
						$NS['MESSAGE'][$NS['CUR_FILE_ID']]['OUTPUT'] = $scan->getOutput();
					}
					$NS['CUR_FILE_ID']++;
				}
				else
				{
					if($NS['STUCK_FILE'] === $NS['CUR_FILE_ID'])
					{
						$NS['CUR_FILE_ID']++;
						$NS['STUCK_FILE'] = -1;
					}
					else
						$NS['STUCK_FILE'] = $NS['CUR_FILE_ID'];
				}
			}
			else
			{
				$NS['CUR_FILE_ID']++;
			}

		} while ($NS['CUR_FILE_ID'] < count($NS['FILE_LIST']) && $result !== false);

		if(!($NS['CUR_FILE_ID'] < count($NS['FILE_LIST'])))
		{
			$arDetailReport = '';
			$vulnCount=0;
			foreach ($NS['MESSAGE'] as $file_output)
				if (!empty($file_output))
					if (strpos($arDetailReport, $file_output['OUTPUT']) === false)
					{
						$arDetailReport .= $file_output['OUTPUT'];
						$vulnCount += $file_output['VULN_COUNT'];
					}

			unset($_SESSION['BX_CHECKLIST'][$arParams['TEST_ID']]);

			$arResult = Array(
				'MESSAGE' => Array(
					'PREVIEW' => GetMessage('VULNSCAN_FIULECHECKED').count($NS['FILE_LIST']).GetMessage('VULNSCAN_VULNCOUNTS').$vulnCount,
					'PROBLEM_COUNT' => $vulnCount,
					'DETAIL' => $arDetailReport
				),
				'STATUS' => ($vulnCount > 0 ? false : true)
			);
		}
		else
		{
			$percent = round(($NS['CUR_FILE_ID']) / (count($NS['FILE_LIST']) * 0.01), 0);
			$arResult = Array(
				'IN_PROGRESS' => 'Y',
				'PERCENT' => number_format($percent, 2),
			);
		}
		}
		else
		{
			$arResult = Array(
				'MESSAGE' => Array(
					'PREVIEW' => GetMessage('VULNSCAN_TOKENIZER_NOT_INSTALLED'),
				),
				'STATUS' => false
			);
		}
		return $arResult;
	}

}


?>