<?php

/**
 * This class can be used by other modules in bitrix kernel.
 * BEFORE using this class, this comment must be updated to
 * maintain external class dependencies.
 * 
 * Currently depended modules:
 * learning
 * tasks
 * ------
 * 
 * @access protected
 */
class CLearnSharedArgManager
{
	protected $parsedOptions = NULL;

	/**
	 * @param array of options to be checked and processed (by setting default values and type casting)
	 * @param array of parameters, describes options checking and processing rules
	 * 
	 * @example of $arParseParams
	 *		$arArgManagerParams = array(
	 *		'includeInactiveEdges' => array('type'          => 'boolean',	// allowed are: boolean, integer, strictly_castable_to_integer, string
	 *                                      'mandatory'     => true/false,	// is element mandatory
	 *										'default_value' => true),
	 *		'includeActiveEdges'   => array('type'          => 'boolean',
	 *                                      'mandatory'     => true/false,
	 *										'default_value' => true),
	 *		);
	 * 
	 */
	public function __construct ($arOptions, $arParseParams)
	{
		$this->CheckParseParams ($arParseParams);

		$this->parsedOptions = $this->ParseOptions ($arOptions, $arParseParams);
	}

	public function GetParsedOptions()
	{
		if ($this->parsedOptions === NULL)
			throw new Exception();

		return ($this->parsedOptions);
	}


	public static function StaticParser ($arOptions, $arParseParams)
	{
		try
		{
			$oArgManager = new CLearnSharedArgManager ($arOptions, $arParseParams);
			$rc = $oArgManager->GetParsedOptions();
			unset ($oArgManager);
		}
		catch (Exception $e)
		{
			throw new LearnException (
				'EA_PARAMS: ArgManager at line: ' . $e->GetLine(), 
				LearnException::EXC_ERR_ALL_PARAMS);
		}
		return ($rc);
	}


	protected function ParseOptions ($arOptions, $arParseParams)
	{
		if ( ! is_array($arOptions) )
			throw new Exception();

		$arParsedOptions = array();

		foreach ($arParseParams as $paramName => $arParamData)
		{
			// If option cannot be omitted - check it
			if ($arParamData['mandatory'])
			{
				if ( ! array_key_exists($paramName, $arOptions) )
					throw new Exception();
			}
			else	// option can be omitted, so can be default value
			{
				if ( ! array_key_exists($paramName, $arOptions) )
				{
					if (array_key_exists('default_value', $arParamData))
						$arOptions[$paramName] = $arParamData['default_value'];
				}
			}

			// now, check and cast (if should) type of value in $arOptions[$paramName]
			switch ($arParamData['type'])
			{
				case 'boolean':
					if ( ! in_array($arOptions[$paramName], array(true, false), true) )
						throw new Exception();
				break;

				case 'string':
					if ( ! is_string($arOptions[$paramName]) )
						throw new Exception();
				break;

				case 'integer':
					if ( ! is_int($arOptions[$paramName]) )
						throw new Exception();
				break;

				case 'strictly_castable_to_integer':
					if ( ! is_numeric($arOptions[$paramName]) )
						throw new Exception();

					if ( ! is_int($arOptions[$paramName] + 0) )
						throw new Exception();

					$arOptions[$paramName] = (int) ($arOptions[$paramName] + 0);
				break;

				default:
					throw new Exception();
				break;
			}

			$arParsedOptions[$paramName] = $arOptions[$paramName];
			unset ($arOptions[$paramName]);
		}

		// Ensure that there is no more options
		if (count($arOptions) > 0)
			throw new Exception('there is unprocessed options');

		return ($arParsedOptions);
	}

	protected function CheckParseParams ($arParseParams)
	{
		if ( ! is_array($arParseParams) )
			throw new Exception();

		$arMandatoryFields = array (
			'type'      => array('boolean', 'integer', 'strictly_castable_to_integer', 'string'), 
			'mandatory' => array(true, false)
		);

		foreach ($arParseParams as $key => $element)
		{
			if ( ! is_array($element) )
				throw new Exception();

			// check mandatory fields
			foreach ($arMandatoryFields as $mandatoryField => $mandatoryFieldAllowedValues)
			{
				if ( ! array_key_exists($mandatoryField, $element) )
					throw new Exception();

				// check allowed values
				if (is_array($mandatoryFieldAllowedValues))
					if ( ! in_array($element[$mandatoryField], $mandatoryFieldAllowedValues, true) )
						throw new Exception();
			}

			// if exists $element['default_value'] => $element['mandatory'] must be FALSE
			// because if mandatory is TRUE => value must be set and no default value available
			if (array_key_exists('default_value', $element) && ($element['mandatory'] !== false))
				throw new Exception('"default_value" incompatibily with enabled "mandatory" flag');
		}
	}
}