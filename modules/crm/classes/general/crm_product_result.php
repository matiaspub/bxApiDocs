<?php
if (!CModule::IncludeModule('catalog'))
{
	return false;
}

class CCrmProductResult extends CDBResult
{
	private $arFieldAssoc;
	private $arAdditionalFilter;
	private $arAdditionalSelect;
	private $bRealPrice;
	private static $bInit = false;
	private static $bVatMode = false;
	private static $arVatRates = array();
	private static $catalogIncluded = false;

	function CCrmProductResult($res, $arFields = array(), $arAdditionalFilter = array(), $arAdditionalSelect = array(), $arOptions = array())
	{
		parent::CDBResult($res);
		$fields = $arFields;
		foreach ($fields as $k => $v)
		{
			$str = strval($v);
			if (trim($str) === '') unset($fields[$k]);
		}
		$this->arFieldAssoc = array_flip($fields);
		$this->arAdditionalFilter = $arAdditionalFilter;
		$this->arAdditionalSelect = $arAdditionalSelect;
		$this->bRealPrice = false;
		if (is_array($arOptions) && count($arOptions) > 0)
		{
			if (isset($arOptions['REAL_PRICE']) && $arOptions['REAL_PRICE'] === true)
			{
				$this->bRealPrice = true;
			}
		}

		self::$bVatMode = CCrmTax::isVatMode();
		if (self::$bVatMode)
			self::$arVatRates = CCrmVat::GetAll();
	}

	function Fetch()
	{
		if (self::$catalogIncluded === false)
		{
			if (!CModule::IncludeModule('catalog'))
				return false;
			else
				self::$catalogIncluded = true;
		}

		if ($res = parent::Fetch())
		{
			foreach ($this->arFieldAssoc as $k => $v)
			{
				if ($k !== $v && (isset($res[$k]) || array_key_exists($k, $res)))
				{
					$res[$v] = $res[$k];
					unset($res[$k]);
				}
			}

			if (is_array($this->arAdditionalSelect) && count($this->arAdditionalSelect) > 0)
			{
				$priceInfo = null;
				$CCatalogProduct = new CCatalogProduct();

				$catalogValues = false;
				if (in_array('PRICE', $this->arAdditionalSelect, true) ||
					in_array('VAT_ID', $this->arAdditionalSelect, true) ||
					in_array('VAT_INCLUDED', $this->arAdditionalSelect, true) ||
					in_array('MEASURE', $this->arAdditionalSelect, true)
				)
				{
					$catalogValues = $CCatalogProduct->GetByID($res['ID']);
				}

				$bRequirePrice = in_array('PRICE', $this->arAdditionalSelect, true);
				$bRequireCurrency = in_array('CURRENCY_ID', $this->arAdditionalSelect, true);
				if ($bRequirePrice || $bRequireCurrency)
				{
					$arPrice = CCrmProduct::getPrice($res['ID']);
					$priceInfo = array(
						'PRICE' => isset($arPrice['PRICE']) ? $arPrice['PRICE'] : null,
						'CURRENCY' => isset($arPrice['CURRENCY']) ? $arPrice['CURRENCY'] : null
					);
					if ($bRequirePrice)
						$res['PRICE'] = $priceInfo['PRICE'];
					if ($bRequireCurrency)
						$res['CURRENCY_ID'] = $priceInfo['CURRENCY'];
					unset($arPrice);

					// recalculate price
					if (!$this->bRealPrice && self::$bVatMode && $catalogValues !== false)
					{
						if (isset($res['PRICE']) && isset($catalogValues['VAT_ID']) && isset($catalogValues['VAT_INCLUDED']))
						{
							if($catalogValues['VAT_INCLUDED'] !== 'Y')
							{
								if (isset(self::$arVatRates[$catalogValues['VAT_ID']]))
								{
									$vatRate = self::$arVatRates[$catalogValues['VAT_ID']]['RATE'];
									$res['PRICE'] = (doubleval($vatRate)/100 + 1) * doubleval($res['PRICE']);
								}
							}
						}
					}
				}
				unset($bRequirePrice, $bRequireCurrency);

				foreach ($this->arAdditionalSelect as $field)
				{
					if ($field === 'ORIGINATOR_ID')
					{
						if (isset($res['XML_ID']) && !empty($res['XML_ID']) && $res['XML_ID'] !== '#' &&
							isset($res['IBLOCK_ID']) && $res['IBLOCK_ID'] != CCrmProduct::getDefaultCatalogId())
						{
							$delimiterPos = strpos($res['XML_ID'], '#');
							if ($delimiterPos !== false)
							{
								$res['ORIGINATOR_ID'] = substr($res['XML_ID'], 0, $delimiterPos);
							}
							else $res['ORIGINATOR_ID'] = $res['XML_ID'];
						}
						else $res['ORIGINATOR_ID'] = '';
					}
					elseif ($field === 'ORIGIN_ID')
					{
						if (isset($res['XML_ID']) && !empty($res['XML_ID']) && $res['XML_ID'] !== '#' &&
							isset($res['IBLOCK_ID']) && $res['IBLOCK_ID'] != CCrmProduct::getDefaultCatalogId())
						{
							$delimiterPos = strpos($res['XML_ID'], '#');
							if ($delimiterPos !== false)
							{
								$res['ORIGIN_ID'] = substr($res['XML_ID'], $delimiterPos + 1);
								if ($res['ORIGIN_ID'] === false) $res['ORIGIN_ID'] = '';
							}
							else $res['ORIGIN_ID'] = '';
						}
						else $res['ORIGIN_ID'] = '';
					}
					elseif ($field === 'VAT_ID' || $field === 'VAT_INCLUDED' || $field === 'MEASURE')
					{
						if ($field === 'VAT_ID')
							$res['VAT_ID'] =
								($catalogValues !== false && isset($catalogValues['VAT_ID'])) ?
									$catalogValues['VAT_ID'] : null;
						if ($field === 'VAT_INCLUDED')
							$res['VAT_INCLUDED'] =
								($catalogValues !== false && isset($catalogValues['VAT_INCLUDED'])) ?
									$catalogValues['VAT_INCLUDED'] : null;
						if ($field === 'MEASURE')
							$res['MEASURE'] =
								($catalogValues !== false && isset($catalogValues['MEASURE'])) ?
									$catalogValues['MEASURE'] : null;
					}
				}
			}
			if (in_array('XML_ID', $this->arAdditionalSelect, true) && isset($res['XML_ID'])) unset($res['XML_ID']);
		}

		return $res;
	}
}
