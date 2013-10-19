<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/index.php
 * @author Bitrix
 */
class CAllCatalog
{
	protected static $arCatalogCache = array();

	
	/**
	 * <p>Метод служит для проверки (и корректировки, если это возможно) параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__add.cee81079.php">CCatalog::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__update.c1202733.php">CCatalog::Update</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $ACTION  Указывает, для какого метода идет проверка. Возможные значения:
	 * <br><ul> <li> <b>ADD</b> - для метода <a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__add.cee81079.php">CCatalog::Add</a>;</li> <li>
	 * <b>UPDATE</b> - для метода <a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__update.c1202733.php">CCatalog::Update</a>.</li>
	 * </ul>
	 *
	 *
	 *
	 * @param array &$arFields  Ассоциативный массив параметров привязки инфоблока к модулю
	 * Торгового каталога. Массив передается по ссылке и его значения
	 * могут быть изменены функцией. <br> Допустимые ключи: <ul> <li> <b>IBLOCK_ID</b>
	 * - код (ID) инфоблока;</li> <li> <b>SUBSCRIPTION</b> - флаг "Продажа контента" (Y/N);</li>
	 * <li> <b>YANDEX_EXPORT</b> - флаг "Экспортировать в Яндекс.Товары" (Y/N);</li> <li>
	 * <b>VAT_ID</b> - код (ID) типа НДС;</li> <li> <b>PRODUCT_IBLOCK_ID</b> - код (ID) инфоблока
	 * товаров (для инфоблока торговых предложений);</li> <li> <b>SKU_PROPERTY_ID</b> -
	 * код (ID) свойства привязки к инфоблоку товаров (для инфоблока
	 * торговых предложений);</li> </ul>
	 *
	 *
	 *
	 * @param int $ID = 0] Код (ID) инфоблока.
	 *
	 *
	 *
	 * @return bool <p> В случае корректности переданных параметров возвращает true,
	 * иначе - false. Если функция вернула false, с помощью $APPLICATION-&gt;GetException()
	 * можно получить текст ошибок.</p><p><b>Обязательные проверки</b></p><ul>
	 * <li>для <b>CCatalog::Add</b> <ul> <li>ключ IBLOCK_ID присутствует и содержит код (ID)
	 * существующего инфоблока;</li> <li>если ключ SUBSCRIPTION не существует или
	 * не равен Y, ему присваивается значение N;</li> <li>если ключ YANDEX_EXPORT не
	 * существует или не равен Y, ему присваивается значение N;</li> <li>если
	 * ключ VAT_ID не существует или меньше 0, ему присваивается значение
	 * 0;</li> <li>PRODUCT_IBLOCK_ID и SKU_PROPERTY_ID оба отсутствуют, оба равны нулю, либо
	 * отвечают правилу: <ul> <li>PRODUCT_IBLOCK_ID - код (ID) существующего
	 * инфоблока;</li> <li>SKU_PROPERTY_ID - код (ID) существующего свойства
	 * инфоблока IBLOCK_ID. Тип свойства - "SKU", свойство одиночное, поле
	 * LINK_IBLOCK_ID свойства = PRODUCT_IBLOCK_ID.</li> </ul> </li> </ul> <br> </li> <li>для
	 * <b>CCatalog::Update</b> <ul> <li>инфоблок с кодом ID должен являться торговым
	 * каталогом;</li> <li>если ключ SUBSCRIPTION существует и не равен Y, ему
	 * присваивается значение N;</li> <li>если ключ YANDEX_EXPORT существует и не
	 * равен Y, ему присваивается значение N;</li> <li>если ключ VAT_ID
	 * существует и меньше 0, ему присваивается значение 0;</li> <li>PRODUCT_IBLOCK_ID
	 * и SKU_PROPERTY_ID оба отсутствуют либо оба заданы;</li> </ul> </li> </ul>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = array(
	 *    'IBLOCK_ID' =&gt; 2,
	 *    'YANDEX_EXPORT' =&gt; 'Y'
	 * );
	 * $boolResult = CCatalog::CheckFields('ADD',$arFields);
	 * if ($boolResult == false)
	 * {
	 * 	if ($ex = $APPLICATION-&gt;GetException())
	 * 	{
	 * 		$strError = $ex-&gt;GetString();
	 * 		ShowError($strError);
	 * 	}
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__add.cee81079.php">CCatalog::Add</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__update.c1202733.php">CCatalog::Update</a></li>
	 * </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/checkfields.php
	 * @author Bitrix
	 */
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$ID = intval($ID);
		$arCatalog = false;
		if (0 < $ID)
			$arCatalog = CCatalog::GetByID($ID);
		if ($boolResult)
		{
			if (('UPDATE' == $ACTION) && (false == $arCatalog))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'ID','text' => GetMessage('BT_MOD_CATALOG_ERR_UPDATE_BAD_ID'));
			}
		}

		if ($boolResult)
		{
			if ('ADD' == $ACTION || is_set($arFields,'IBLOCK_ID'))
			{
				if (!is_set($arFields,'IBLOCK_ID'))
				{
					$arMsg[] = array('id' => 'IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_IBLOCK_ID_FIELD_ABSENT'));
					$boolResult = false;
				}
				elseif(0 >= intval($arFields['IBLOCK_ID']))
				{
					$arMsg[] = array('id' => 'IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_IBLOCK_ID_INVALID'));
					$boolResult = false;
				}
				else
				{
					$arFields['IBLOCK_ID'] = intval($arFields['IBLOCK_ID']);
					$rsIBlocks = CIBlock::GetByID($arFields['IBLOCK_ID']);
					if (!($arIBlock = $rsIBlocks->Fetch()))
					{
						$arMsg[] = array('id' => 'IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_IBLOCK_ID_ABSENT'));
						$boolResult = false;
					}
				}
			}
			if ((is_set($arFields, "SUBSCRIPTION") || $ACTION=="ADD") && $arFields["SUBSCRIPTION"] != "Y")
				$arFields["SUBSCRIPTION"] = "N";
			if ((is_set($arFields, "YANDEX_EXPORT") || $ACTION=="ADD") && $arFields["YANDEX_EXPORT"] != "Y")
				$arFields["YANDEX_EXPORT"] = "N";

			if ((is_set($arFields,'VAT_ID') || ('ADD' == $ACTION)))
			{
				$arFields['VAT_ID'] = intval($arFields['VAT_ID']);
				if (0 > $arFields['VAT_ID'])
				{
					$arFields['VAT_ID'] = 0;
				}
			}
		}

		if ($boolResult)
		{
			if ('ADD' == $ACTION)
			{
				if (!is_set($arFields, "PRODUCT_IBLOCK_ID"))
				{
					$arFields["PRODUCT_IBLOCK_ID"] = 0;
				}
				elseif (0 > intval($arFields["PRODUCT_IBLOCK_ID"]))
				{
					$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_INVALID'));
					$arFields["PRODUCT_IBLOCK_ID"] = 0;
					$boolResult = false;
				}
				elseif (0 < intval($arFields["PRODUCT_IBLOCK_ID"]))
				{
					$arFields["PRODUCT_IBLOCK_ID"] = intval($arFields["PRODUCT_IBLOCK_ID"]);
					$rsIBlocks = CIBlock::GetByID($arFields['PRODUCT_IBLOCK_ID']);
					if (!($arIBlock = $rsIBlocks->Fetch()))
					{
						$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_ABSENT'));
						$arFields["PRODUCT_IBLOCK_ID"] = 0;
						$boolResult = false;
					}
					else
					{
						if ($arFields["PRODUCT_IBLOCK_ID"] == $arFields['IBLOCK_ID'])
						{
							$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_SELF'));
							$arFields["PRODUCT_IBLOCK_ID"] = 0;
							$boolResult = false;
						}
					}
				}
				else
				{
					$arFields["PRODUCT_IBLOCK_ID"] = 0;
				}

				if (!is_set($arFields, "SKU_PROPERTY_ID"))
				{
					$arFields["SKU_PROPERTY_ID"] = 0;
				}
				elseif (0 > intval($arFields["SKU_PROPERTY_ID"]))
				{
					$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_ID_INVALID'));
					$arFields["SKU_PROPERTY_ID"] = 0;
					$boolResult = false;
				}
				else
				{
					$arFields["SKU_PROPERTY_ID"] = intval($arFields["SKU_PROPERTY_ID"]);
				}

				if ((0 < $arFields["PRODUCT_IBLOCK_ID"]) && (0 == $arFields['SKU_PROPERTY_ID']))
				{
					$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_WITHOUT_SKU_PROP'));
					$boolResult = false;
				}
				elseif ((0 == $arFields["PRODUCT_IBLOCK_ID"]) && (0 < $arFields['SKU_PROPERTY_ID']))
				{
					$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_WITHOUT_PRODUCT'));
					$boolResult = false;
				}
				elseif ((0 < $arFields["PRODUCT_IBLOCK_ID"]) && (0 < $arFields['SKU_PROPERTY_ID']))
				{
					$rsProps = CIBlockProperty::GetList(array(),array('IBLOCK_ID' => $arFields['IBLOCK_ID'],'ID' => $arFields['SKU_PROPERTY_ID'],'ACTIVE' => 'Y'));
					if ($arProp = $rsProps->Fetch())
					{
						if (('E' != $arProp['PROPERTY_TYPE']) || ($arFields["PRODUCT_IBLOCK_ID"] != $arProp['LINK_IBLOCK_ID']))
						{
							$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_WITHOUT_PRODUCT'));
							$boolResult = false;
						}
					}
					else
					{
						$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_NOT_FOUND'));
						$boolResult = false;
					}
				}
			}
			elseif ('UPDATE' == $ACTION)
			{
				$boolLocalFlag = (is_set($arFields,'PRODUCT_IBLOCK_ID') == is_set($arFields,'SKU_PROPERTY_ID'));
				if (!$boolLocalFlag)
				{
					$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_AND_SKU_PROPERTY_ID_NEED'));
					$boolResult = false;
				}
				else
				{
					if (is_set($arFields, 'PRODUCT_IBLOCK_ID'))
					{
						if (0 > intval($arFields["PRODUCT_IBLOCK_ID"]))
						{
							$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_INVALID'));
							$arFields["PRODUCT_IBLOCK_ID"] = 0;
							$boolResult = false;
						}
						elseif (0 < intval($arFields["PRODUCT_IBLOCK_ID"]))
						{
							$arFields["PRODUCT_IBLOCK_ID"] = intval($arFields["PRODUCT_IBLOCK_ID"]);
							$rsIBlocks = CIBlock::GetByID($arFields['PRODUCT_IBLOCK_ID']);
							if (!($arIBlock = $rsIBlocks->Fetch()))
							{
								$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_ABSENT'));
								$arFields["PRODUCT_IBLOCK_ID"] = 0;
								$boolResult = false;
							}
							else
							{
								if (0 < $ID && $arFields["PRODUCT_IBLOCK_ID"] == $ID)
								{
									$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_SELF'));
									$arFields["PRODUCT_IBLOCK_ID"] = 0;
									$boolResult = false;
								}
								else
								{
									if (is_set($arFields, 'IBLOCK_ID') && $arFields["PRODUCT_IBLOCK_ID"] == $arFields['IBLOCK_ID'])
									{
										$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_SELF'));
										$arFields["PRODUCT_IBLOCK_ID"] = 0;
										$boolResult = false;
									}
								}
							}
						}
					}

					if (is_set($arFields, 'SKU_PROPERTY_ID'))
					{
						if (0 > intval($arFields["SKU_PROPERTY_ID"]))
						{
							$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_ID_INVALID'));
							$arFields["SKU_PROPERTY_ID"] = 0;
							$boolResult = false;
						}
						else
						{
							$arFields["SKU_PROPERTY_ID"] = intval($arFields["SKU_PROPERTY_ID"]);
						}
					}
					if (is_set($arFields, 'PRODUCT_IBLOCK_ID') && is_set($arFields, 'SKU_PROPERTY_ID'))
					{
						if ((0 < $arFields["PRODUCT_IBLOCK_ID"]) && (0 == $arFields['SKU_PROPERTY_ID']))
						{
							$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_WITHOUT_SKU_PROP'));
							$boolResult = false;
						}
						elseif ((0 == $arFields["PRODUCT_IBLOCK_ID"]) && (0 < $arFields['SKU_PROPERTY_ID']))
						{
							$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_WITHOUT_PRODUCT'));
							$boolResult = false;
						}
						elseif ((0 < $arFields["PRODUCT_IBLOCK_ID"]) && (0 < $arFields['SKU_PROPERTY_ID']))
						{
							$rsProps = CIBlockProperty::GetList(array(),array('IBLOCK_ID' => $ID, 'ID' => $arFields['SKU_PROPERTY_ID'],'ACTIVE' => 'Y'));
							if ($arProp = $rsProps->Fetch())
							{
								if (('E' != $arProp['PROPERTY_TYPE']) || ($arFields["PRODUCT_IBLOCK_ID"] != $arProp['LINK_IBLOCK_ID']))
								{
									$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_WITHOUT_PRODUCT'));
									$boolResult = false;
								}
							}
							else
							{
								$arMsg[] = array('id' => 'SKU_PROPERTY_ID', "text" => GetMessage('BT_MOD_CATALOG_ERR_SKU_PROP_NOT_FOUND'));
								$boolResult = false;
							}
						}
					}
				}
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	
	/**
	 * <p>Возвращает массив параметров каталога, включая некоторые параметры, относящиеся к информационному блоку. Если инфоблок с кодом $ID не существует или не является торговым каталогом, вернет false.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код каталога - инфоблока.
	 *
	 *
	 *
	 * @return array <p>Если инфоблок с кодом $ID не существует или не является торговым
	 * каталогом, вернет false. Иначе возвращает ассоциативный массив с
	 * ключами:</p><table class="tnormal" width="100%"> <thead><tr> <th width="15%">Ключ</th>
	 * <th>Описание</th> </tr></thead> <tbody> <tr> <td>IBLOCK_ID</td> <td>Код (ID) информационного
	 * блока.</td> </tr> <tr> <td>ID</td> <td>Код (ID) информационного блока.</td> </tr> <tr>
	 * <td>IBLOCK_TYPE_ID</td> <td>Тип информационного блока.</td> </tr> <tr> <td>NAME</td>
	 * <td>Название информационного блока.</td> </tr> <tr> <td>SUBSCRIPTION</td> <td>Флаг
	 * "Продажа контента" (Y/N).</td> </tr> <tr> <td>YANDEX_EXPORT</td> <td>Флаг
	 * "экспортировать в Яндекс.Товары" (Y/N).</td> </tr> <tr> <td>VAT_ID</td> <td>Код (ID)
	 * типа НДС.</td> </tr> <tr> <td>PRODUCT_ID</td> <td>Код (ID) инфоблока товаров (для
	 * инфоблока торговых предложений). Для обычного каталога содержит
	 * 0.</td> </tr> <tr> <td>SKU_PROPERTY_ID</td> <td>Код (ID) свойства привязки к инфоблоку
	 * товаров (для инфоблока торговых предложений). Для обычного
	 * каталога содержит 0.</td> </tr> <tr> <td>OFFERS_IBLOCK_ID</td> <td>Код (ID) инфоблока
	 * торговых предложений (для ситуации, когда торговым каталогом
	 * являются как инфоблок товаров, так и инфоблок торговых
	 * предложений). Во всех остальных случаях содержит NULL. Ключ
	 * используется для совместимости, для получения полной информации
	 * о связке "инфоблок товаров - инфоблок торговых предложений"
	 * рекомендуется использовать метод <b>CCatalog::GetByIDExt()</b>.</td> </tr> <tr>
	 * <td>OFFERS_PROPERTY_ID</td> <td>код (ID) свойства привязки торговых предложений к
	 * товарам для ситуации, когда торговым каталогом являются как
	 * инфоблок товаров, так и инфоблок торговых предложений). Во всех
	 * остальных случаях содержит NULL. Ключ используется для
	 * совместимости, для получения полной информации о связке
	 * "инфоблок товаров - инфоблок торговых предложений" рекомендуется
	 * использовать метод <b>Catalog::GetByIDExt()</b>.</td> </tr> <tr> <td>OFFERS</td> <td>Флаг
	 * наличия инфоблока торговых предложений (Y/N).</td> </tr> </tbody> </table>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li>
	 * </ul><br><br>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__getbyid.d6f66bc1.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		if (array_key_exists($ID, self::$arCatalogCache))
		{
			return self::$arCatalogCache[$ID];
		}
		else
		{
			$strSql = "SELECT CI.*, I.ID as ID, I.IBLOCK_TYPE_ID, I.LID, I.NAME,
					OFFERS.IBLOCK_ID OFFERS_IBLOCK_ID, OFFERS.SKU_PROPERTY_ID OFFERS_PROPERTY_ID
				FROM
					b_catalog_iblock CI INNER JOIN b_iblock I ON CI.IBLOCK_ID = I.ID
					LEFT JOIN b_catalog_iblock OFFERS ON CI.IBLOCK_ID = OFFERS.PRODUCT_IBLOCK_ID
				WHERE
					CI.IBLOCK_ID = ".$ID;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($res = $db_res->Fetch())
			{
				$res["OFFERS"] = $res["PRODUCT_IBLOCK_ID"] ? "Y": "N";
				self::$arCatalogCache[$ID] = $res;
				if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
				{
					global $CATALOG_CATALOG_CACHE;
					$CATALOG_CATALOG_CACHE = self::$arCatalogCache;
				}
				return $res;
			}
		}
		return false;
	}

	public static function GetFilterOperation($key)
	{
		$arResult = array(
			'FIELD' => '',
			'NEGATIVE' => 'N',
			'OPERATION' => '',
			'OR_NULL' => 'N'
		);

		static $arDoubleModify = array(
			'>=' => '>=',
			'<=' => '<='
		);

		static $arOneModify = array(
			'>' => '>',
			'<' => '<',
			'@' => 'IN',
			'~' => 'LIKE',
			'%' => 'QUERY',
			'=' => '='
		);

		if (0 == strncmp($key, '!', 1))
		{
			$arResult['NEGATIVE'] = 'Y';
			$key = substr($key, 1);
			if (0 == strncmp($key, '+', 1))
			{
				$arResult['OR_NULL'] = 'Y';
				$key = substr($key, 1);
			}
		}
		elseif (0 == strncmp($key, '+', 1))
		{
			$arResult['OR_NULL'] = 'Y';
			$key = substr($key, 1);
			if (0 == strncmp($key, '!', 1))
			{
				$arResult['NEGATIVE'] = 'Y';
				$key = substr($key, 1);
			}
		}

		$strKeyOp = substr($key, 0, 2);
		if ('' != $strKeyOp && array_key_exists($strKeyOp, $arDoubleModify))
		{
			$arResult['OPERATION'] = $arDoubleModify[$strKeyOp];
			$arResult['FIELD'] = substr($key, 2);
			return $arResult;
		}
		$strKeyOp = substr($key, 0, 1);
		if ('' != $strKeyOp && array_key_exists($strKeyOp, $arOneModify))
		{
			$arResult['OPERATION'] = $arOneModify[$strKeyOp];
			$arResult['FIELD'] = substr($key, 1);
			return $arResult;
		}
		$arResult['OPERATION'] = '=';
		$arResult['FIELD'] = $key;
		return $arResult;
	}

	public static function PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$strDBType = strtoupper($DB->type);

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (!empty($arGroupBy) && is_array($arGroupBy))
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| empty($arSelectFields)
				|| !is_array($arSelectFields)
				|| in_array("*", $arSelectFields))
			{
				for ($i = 0, $intCount = count($arFieldsKeys); $i < $intCount; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if ('' != $strSqlSelect)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
					{
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];
					}

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if ('' != $strSqlFrom)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if ('' != $strSqlSelect)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if ('' != $strSqlFrom)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if ('' != $strSqlGroupBy)
			{
				if ('' != $strSqlSelect)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
			{
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
			}
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		$filter_keys = (!is_array($arFilter) ? array() : array_keys($arFilter));

		for ($i = 0, $intCount = count($filter_keys); $i < $intCount; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			$vals = (!is_array($vals) ? array($vals) : array_values($vals));

			$key = $filter_keys[$i];
			$key_res = CCatalog::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if ('' != $key && array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();

				if (!empty($vals))
				{
					if ($strOperation == "IN")
					{
						if (isset($arFields[$key]["WHERE"]))
						{
							$arSqlSearch_tmp1 = call_user_func_array(
									$arFields[$key]["WHERE"],
									array($vals, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
								);
							if ($arSqlSearch_tmp1 !== false)
								$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
						else
						{
							if ($arFields[$key]["TYPE"] == "int")
							{
								array_walk($vals, create_function("&\$item", "\$item=intval(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (empty($vals))
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." IN (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "double")
							{
								array_walk($vals, create_function("&\$item", "\$item=DoubleVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (empty($vals))
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->ForSql(\$item).\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (empty($vals))
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "datetime")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"FULL\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (empty($vals))
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "date")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"SHORT\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (empty($vals))
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
						}
					}
					else
					{
						for ($j = 0, $intCountVals = count($vals); $j < $intCountVals; $j++)
						{
							$val = $vals[$j];

							if (isset($arFields[$key]["WHERE"]))
							{
								$arSqlSearch_tmp1 = call_user_func_array(
										$arFields[$key]["WHERE"],
										array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
									);
								if ($arSqlSearch_tmp1 !== false)
									$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
							}
							else
							{
								if ($arFields[$key]["TYPE"] == "int")
								{
									if ((intval($val) == 0) && (strpos($strOperation, "=") !== false))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".intval($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "double")
								{
									$val = str_replace(",", ".", $val);

									if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== false))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
								{
									if ($strOperation == "QUERY")
									{
										$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
									}
									else
									{
										if ((strlen($val) == 0) && (strpos($strOperation, "=") !== false))
											$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
										else
											$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
									}
								}
								elseif ($arFields[$key]["TYPE"] == "datetime")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
								}
								elseif ($arFields[$key]["TYPE"] == "date")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
								}
							}
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				for ($j = 0, $intCountSearch = count($arSqlSearch_tmp); $j < $intCountSearch; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					}
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					}
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		for ($i = 0, $intCount = count($arSqlSearch); $i < $intCount; $i++)
		{
			if ('' != $strSqlWhere)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $intCount = count($arSqlOrder); $i < $intCount; $i++)
		{
			if ('' != $strSqlOrderBy)
				$strSqlOrderBy .= ", ";
			if ($strDBType == "ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}

	public static function _PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$strDBType = strtoupper($DB->type);

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (!empty($arGroupBy) && is_array($arGroupBy))
		{
			//$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields)
					&& !in_array($key, $arGroupByFunct)
					)
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| empty($arSelectFields)
				|| !is_array($arSelectFields)
				|| in_array("*", $arSelectFields))
			{
				for ($i = 0, $intCount = count($arFieldsKeys); $i < $intCount; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if ('' != $strSqlSelect)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if ('' != $strSqlSelect)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (($strDBType == "ORACLE" || $strDBType == "MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if ('' != $strSqlFrom)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if ('' != $strSqlGroupBy)
			{
				if ('' != $strSqlSelect)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
			{
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
			}
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();
		$arSqlHaving = Array();

		$filter_keys = (!is_array($arFilter) ? array() : array_keys($arFilter));

		for ($i = 0, $intCount = count($filter_keys); $i < $intCount; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			$vals = (!is_array($vals) ? array($vals) : array_values($vals));

			$key = $filter_keys[$i];
			$key_res = CCatalog::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if ('' != $key && array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				$arSqlHaving_tmp = array();
				for ($j = 0, $intCountVals = count($vals); $j < $intCountVals; $j++)
				{
					$val = $vals[$j];

					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
								$arFields[$key]["WHERE"],
								array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], &$arFields, &$arFilter)
							);
						if ($arSqlSearch_tmp1 !== false)
						{
							if (isset($arFields[$key]["GROUPED"]) && $arFields[$key]["GROUPED"] == "Y")
								$arSqlHaving_tmp[] = $arSqlSearch_tmp1;
							else
								$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
					}
					else
					{
						$arSqlSearch_tmp1 = "";

						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((intval($val) == 0) && (strpos($strOperation, "=") !== false))
								$arSqlSearch_tmp1 = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp1 = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".intval($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== false))
								$arSqlSearch_tmp1 = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp1 = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						{
							if ($strOperation == "QUERY")
							{
								$arSqlSearch_tmp1 = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
							}
							else
							{
								if ((strlen($val) == 0) && (strpos($strOperation, "=") !== false))
									$arSqlSearch_tmp1 = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
								else
									$arSqlSearch_tmp1 = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp1 = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp1 = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp1 = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp1 = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
						}

						if (isset($arFields[$key]["GROUPED"]) && $arFields[$key]["GROUPED"] == "Y")
							$arSqlHaving_tmp[] = $arSqlSearch_tmp1;
						else
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				for ($j = 0, $intCountSearchTmp = count($arSqlSearch_tmp); $j < $intCountSearchTmp; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					}
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					}
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";

				$strSqlHaving_tmp = "";
				for ($j = 0, $intCountHavingTmp = count($arSqlHaving_tmp); $j < $intCountHavingTmp; $j++)
				{
					if ($j > 0)
						$strSqlHaving_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlHaving_tmp .= "(".$arSqlHaving_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlHaving_tmp) > 0)
						$strSqlHaving_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlHaving_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if (strlen($strSqlHaving_tmp) > 0)
						$strSqlHaving_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlHaving_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlHaving_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
				}

				if ($strSqlHaving_tmp != "")
					$arSqlHaving[] = "(".$strSqlHaving_tmp.")";
			}
		}

		$strSqlWhere = "";
		for ($i = 0, $intCount = count($arSqlSearch); $i < $intCount; $i++)
		{
			if ('' != $strSqlWhere)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}

		$strSqlHaving = "";
		for ($i = 0, $intCount = count($arSqlHaving); $i < $intCount; $i++)
		{
			if ('' != $strSqlHaving)
				$strSqlHaving .= " AND ";
			$strSqlHaving .= "(".$arSqlHaving[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC".($strDBType == "ORACLE" ? " NULLS LAST" : "");
			else
				$order = "ASC".($strDBType == "ORACLE" ? " NULLS FIRST" : "");

			if (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $intCount = count($arSqlOrder); $i < $intCount; $i++)
		{
			if ('' != $strSqlOrder)
				$strSqlOrder .= ", ";
			$strSqlOrder .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
				"SELECT" => $strSqlSelect,
				"FROM" => $strSqlFrom,
				"WHERE" => $strSqlWhere,
				"GROUPBY" => $strSqlGroupBy,
				"ORDERBY" => $strSqlOrder,
				"HAVING" => $strSqlHaving
			);
	}

	
	/**
	 * <p>Метод служит для добавления новой записи в таблицу привязывания информационного блока к модулю торгового каталога. </p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив параметров привязки, который может содержать следующие
	 * ключи: <ul> <li> <b>IBLOCK_ID</b> - код (ID) инфоблока (обязательный);</li> <li>
	 * <b>SUBSCRIPTION</b> - флаг "Продажа контента" (Y/N) (необязательный), по
	 * умолчанию - N;</li> <li> <b>YANDEX_EXPORT</b> - флаг "Экспортировать в
	 * Яндекс.Товары" (Y/N) (необязательный), по умолчанию - N;</li> <li> <b>VAT_ID</b> -
	 * код (ID) типа НДС (необязательный), по умолчанию - 0;</li> <li>
	 * <b>PRODUCT_IBLOCK_ID</b> - код (ID) инфоблока товаров (для инфоблока торговых
	 * предложений) (необязательный, только вместе с SKU_PROPERTY_ID), по
	 * умолчанию - 0;</li> <li> <b>SKU_PROPERTY_ID</b> - код (ID) свойства привязки к
	 * инфоблоку товаров (для инфоблока торговых предложений),
	 * (необязательный, только вместе с PRODUCT_IBLOCK_ID), по умолчанию - 0;</li> </ul>
	 * Необязательные ключи, отсутствующие в массиве, получат значения
	 * по умолчанию.
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i>, если запись успешно добавлена и <i>false</i> - если
	 * произошла ошибка. Текстовое сообщение об ошибках можно получить
	 * через $APPLICATION-&gt;GetException().</p><p>Перед добавлением записи в таблицу
	 * осуществляется проверка параметров привязки методом <a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/checkfields.php">CCatalog::CheckFields</a>
	 * (условия корректности параметров изложены в нем). Если проверка
	 * прошла успешно, производится запись в базу. Попытка добавить
	 * больше одной записи с одинаковым IBLOCK_ID вызовет ошибку базы
	 * данных.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * Привязка инфоблока к модулю Торгового каталога
	 * $arFields = array(
	 * 	'IBLOCK_ID' =&gt; 2,			// код (ID) инфоблока товаров
	 * 	'YANDEX_EXPORT' =&gt; 'Y',		// экспортировать в Яндекс.Товары с помощью агента
	 * );
	 * $boolResult = CCatalog::Add($arFields);
	 * if ($boolResult == false)
	 * {
	 * 	if ($ex = $APPLICATION-&gt;GetException())
	 * 	{
	 * 		$strError = $ex-&gt;GetString();
	 * 		ShowError($strError);
	 * 	}
	 * }
	 * Привязка инфоблока к модулю Торговых предложений как инфоблока торговых предложений
	 * $arFields = array(
	 * 	'IBLOCK_ID' =&gt; 2,			// код (ID) инфоблока торговых предложений
	 * 	'VAT_ID' =&gt; 2,				// код (ID) типа НДС 
	 * 	'PRODUCT_IBLOCK_ID' =&gt; 10,	// код (ID) инфоблока товаров (может быть привязан или не привязан к модулю торгового каталога)
	 * 	'SKU_PROPERTY_ID' =&gt; 14		// код (ID) свойства привязки инфоблока с ID=2 к инфоблоку с ID=10 (тип свойства - SKU)
	 * );
	 * $boolResult = CCatalog::Add($arFields);
	 * if ($boolResult == false)
	 * {
	 * 	if ($ex = $APPLICATION-&gt;GetException())
	 * 	{
	 * 		$strError = $ex-&gt;GetString();
	 * 		ShowError($strError);
	 * 	}
	 * }
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/checkfields.php">CCatalog::CheckFields</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__update.c1202733.php">CCatalog::Update</a></li>
	 * </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__add.cee81079.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (array_key_exists('OFFERS', $arFields))
			unset($arFields['OFFERS']);
		if (!CCatalog::CheckFields("ADD", $arFields, 0))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_iblock", $arFields);

		$strSql = "INSERT INTO b_catalog_iblock(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		CCatalogSKU::ClearCache();

		return true;
	}

	
	/**
	 * <p>Функция изменяет параметры записи с кодом ID в таблице привязывания информационного блока к модулю Торгового Каталога. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код записи для изменения.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив новых параметров записи, ключами с котором
	 * являются названия параметров, а значениями - новые
	 * значения.<br>Допустимые ключи: <ul> <li> <b>SUBSCRIPTION</b> - флаг "Продажа
	 * контента" (Y/N);</li> <li> <b>YANDEX_EXPORT</b> - флаг "Экспортировать в
	 * Яндекс.Товары" (Y/N);</li> <li> <b>VAT_ID</b> - код (ID) типа НДС;</li> <li>
	 * <b>PRODUCT_IBLOCK_ID</b> - код (ID) инфоблока товаров (для инфоблока торговых
	 * предложений, только вместе с SKU_PROPERTY_ID);</li> <li> <b>SKU_PROPERTY_ID</b> - код (ID)
	 * свойства привязки к инфоблоку товаров (для инфоблока торговых
	 * предложений, только вместе с PRODUCT_IBLOCK_ID);</li> </ul>
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i> в случае успешного изменения записи и <i>false</i> -
	 * в противном случае. </p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/checkfields.php">CCatalog::CheckFields</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__add.cee81079.php">CCatalog::Add</a></li>
	 * </ul><br><br>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__update.c1202733.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);
		if (array_key_exists('OFFERS', $arFields))
			unset($arFields['OFFERS']);

		if (!CCatalog::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_iblock", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_iblock SET ".$strUpdate." WHERE IBLOCK_ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if (array_key_exists($ID, self::$arCatalogCache))
			{
				unset($arCatalogCache[$ID]);
				if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
				{
					global $CATALOG_CATALOG_CACHE;
					$CATALOG_CATALOG_CACHE = self::$arCatalogCache;
				}
			}
		}
		CCatalogSKU::ClearCache();
		return true;
	}

	
	/**
	 * <p>Метод удаляет привязку информационного блока с кодом ID к торговому каталогу. При этом удаляются так же параметры товаров и ценовые предложения, относящиеся к этому каталогу. Описания товаров, относящиеся к элементу информационного блока, остаются неизменными.</p> <p>Перед удалением происходит вызов обработчиков события OnBeforeCatalogDelete.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код информационного блока - каталога.
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i> в случае успешного удаления записи и <i>false</i> -
	 * в противном случае. </p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalog/ccatalog__delete.b8b22efb.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);

		$bCanDelete = true;
		foreach(GetModuleEvents("catalog", "OnBeforeCatalogDelete", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;
		}

		foreach(GetModuleEvents("catalog", "OnCatalogDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		$bSuccess = true;

		$dbRes = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $ID));
		while ($arRes = $dbRes->Fetch())
		{
			if (!CCatalogProduct::Delete($arRes["ID"]))
				$bSuccess = false;
		}

		if ($bSuccess)
		{
			if (array_key_exists($ID, self::$arCatalogCache))
			{
				unset(self::$arCatalogCache[$ID]);
				if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
				{
					global $CATALOG_CATALOG_CACHE;
					$CATALOG_CATALOG_CACHE = self::$arCatalogCache;
				}
			}
			CCatalogSKU::ClearCache();
			CCatalogProduct::ClearCache();
			return $DB->Query("DELETE FROM b_catalog_iblock WHERE IBLOCK_ID = ".$ID, true);
		}
		return false;

	}

	public static function OnIBlockDelete($ID)
	{
		return CCatalog::Delete($ID);
	}

	public static function PreGenerateXML($xml_type = "yandex")
	{
		if ($xml_type=="yandex")
		{
			$strYandexAgent = COption::GetOptionString('catalog','yandex_agent_file','');
			if (strlen($strYandexAgent) > 0)
			{
				if (file_exists($_SERVER['DOCUMENT_ROOT'].$strYandexAgent) && is_file($_SERVER['DOCUMENT_ROOT'].$strYandexAgent))
				{
					include_once($_SERVER['DOCUMENT_ROOT'].$strYandexAgent);
				}
				else
				{
					CEventLog::Log('WARNING','CAT_YAND_FILE','catalog','YandexAgent',$strYandexAgent);
					include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/load/yandex.php");
				}
			}
			else
			{
				include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/load/yandex.php");
			}
		}

		global $pPERIOD;
		$pPERIOD = intval(COption::GetOptionString("catalog", "yandex_xml_period", "24"))*60*60;
		return "CCatalog::PreGenerateXML(\"".$xml_type."\");";
	}

/*
* @deprecated deprecated since catalog 11.0.2
* @see CCatalogSKU::GetInfoByProductIBlock()
*/
	public static function GetSkuInfoByProductID($ID)
	{
		return CCatalogSKU::GetInfoByProductIBlock($ID);
	}

/*
* @deprecated deprecated since catalog 11.0.2
* @see CCatalogSKU::GetInfoByLinkProperty()
*/
	public static function GetSkuInfoByPropID($ID)
	{
		return CCatalogSKU::GetInfoByLinkProperty($ID);
	}

	public static function OnBeforeIBlockElementDelete($ID)
	{
		global $APPLICATION;
		global $DB;

		$ID = intval($ID);
		if (0 < $ID)
		{
			$intIBlockID = intval(CIBlockElement::GetIBlockByID($ID));
			if (0 < $intIBlockID)
			{
				$arCatalog = CCatalogSKU::GetInfoByProductIBlock($intIBlockID);
				if (!empty($arCatalog) && is_array($arCatalog) && 0 < $arCatalog['IBLOCK_ID'] && 0 < $arCatalog['SKU_PROPERTY_ID'])
				{
					$arFilter = array('IBLOCK_ID' => $arCatalog['IBLOCK_ID'],'=PROPERTY_'.$arCatalog['SKU_PROPERTY_ID'] => $ID,'ACTIVE' => '');
					$rsOffers = CIBlockElement::GetList(array(),$arFilter,false,false,array('ID','IBLOCK_ID'));
					while($arOffer = $rsOffers->Fetch())
					{
						foreach(GetModuleEvents("iblock", "OnBeforeIBlockElementDelete", true) as $arEvent)
						{
							if(ExecuteModuleEventEx($arEvent, array($arOffer['ID']))===false)
							{
								$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
								$err_id = false;
								if ($ex = $APPLICATION->GetException())
								{
									$err .= ': '.$ex->GetString();
									$err_id = $ex->GetID();
								}
								$APPLICATION->ThrowException($err, $err_id);
								return false;
							}
						}
						if (false == CIBlockElement::Delete($arOffer['ID']))
						{
							$APPLICATION->ThrowException(GetMessage('BT_MOD_CATALOG_ERR_CANNOT_DELETE_OFFERS'));
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	public static function OnBeforeCatalogDelete($ID)
	{
		global $APPLICATION;

		$arMsg = array();

		$ID = intval($ID);
		if (0 >= $ID) return true;
		$arCatalog = CCatalog::GetByIDExt($ID);
		if (false == $arCatalog) return true;
		if (0 < intval($arCatalog['PRODUCT_IBLOCK_ID']))
		{
			$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', 'text' => GetMessage('BT_MOD_CATALOG_ERR_CANNOT_DELETE_SKU_IBLOCK'));
			$obError = new CAdminException($arMsg);
			$APPLICATION->ThrowException($obError);
			return false;
		}
		if (0 < intval($arCatalog['OFFERS_IBLOCK_ID']))
		{
			$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', 'text' => GetMessage('BT_MOD_CATALOG_ERR_CANNOT_DELETE_PRODUCT_IBLOCK'));
			$obError = new CAdminException($arMsg);
			$APPLICATION->ThrowException($obError);
			return false;
		}
		foreach(GetModuleEvents("catalog", "OnBeforeCatalogDelete", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array($ID)))
			{
				$strError = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if ($ex = $APPLICATION->GetException())
				{
					$strError .= ': '.$ex->GetString();
				}
				$APPLICATION->ThrowException($strError);
				return false;
			}
		}

		return true;
	}

	public static function OnBeforeIBlockPropertyDelete($intPropertyID)
	{
		global $APPLICATION;

		$intPropertyID = intval($intPropertyID);
		if (0 >= $intPropertyID)
			return true;
		$arSkuInfo = CCatalogSKU::GetInfoByLinkProperty($intPropertyID);
		if (!empty($arSkuInfo))
		{
			$APPLICATION->ThrowException(GetMessage(
				'BT_MOD_CATALOG_ERR_CANNOT_DELETE_SKU_PROPERTY',
				array(
					'#SKU_PROPERTY_ID#' => $arSkuInfo['SKU_PROPERTY_ID'],
					'#PRODUCT_IBLOCK_ID#' => $arSkuInfo['PRODUCT_IBLOCK_ID'],
					'#IBLOCK_ID#' => $arSkuInfo['IBLOCK_ID'],
				)
			));
			return false;
		}
		return true;
	}

	public static function GetByIDExt($ID)
	{
		$arResult = false;
		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		$mxPRResult = CCatalog::GetByID($ID);
		if (false != $mxPRResult)
		{
			$arResult = $mxPRResult;
			if (0 < $mxPRResult['PRODUCT_IBLOCK_ID'])
			{
				$arResult['CATALOG_TYPE'] = 'O';
				$arResult['OFFERS_IBLOCK_ID'] = 0;
				$arResult['OFFERS_PROPERTY_ID'] = 0;
			}
			else
			{
				$mxSKU = CCatalogSKU::GetInfoByProductIBlock($ID);
				if (false == $mxSKU)
				{
					$arResult['CATALOG_TYPE'] = 'D';
					$arResult['OFFERS_IBLOCK_ID'] = 0;
					$arResult['OFFERS_PROPERTY_ID'] = 0;
				}
				else
				{
					$arResult['CATALOG_TYPE'] = 'X';
					$arResult['OFFERS_IBLOCK_ID'] = $mxSKU['IBLOCK_ID'];
					$arResult['OFFERS_PROPERTY_ID'] = $mxSKU['SKU_PROPERTY_ID'];
				}
			}
			$arResult['CATALOG'] = 'Y';
		}
		else
		{
			$mxSKU = CCatalogSKU::GetInfoByProductIBlock($ID);
			if (false != $mxSKU)
			{
				$arResult['CATALOG'] = 'N';
				$arResult['CATALOG_TYPE'] = 'P';
				$arResult['OFFERS_IBLOCK_ID'] = $mxSKU['IBLOCK_ID'];
				$arResult['OFFERS_PROPERTY_ID'] = $mxSKU['SKU_PROPERTY_ID'];
			}
		}
		return $arResult;
	}

	public static function UnLinkSKUIBlock($ID)
	{
		global $APPLICATION;
		global $DB;

		$arMsg = array();
		$boolResult = true;

		$ID = intval($ID);
		if (0 >= $ID)
		{
			$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID','text' => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_INVALID'));
			$boolResult = false;
		}

		if ($boolResult)
		{
			$rsCatalog = CCatalog::GetList(
				array(),
				array('PRODUCT_IBLOCK_ID' => $ID),
				false,
				false,
				array('IBLOCK_ID')
			);
			if ($arCatalog = $rsCatalog->Fetch())
			{
				$arCatalog['IBLOCK_ID'] = intval($arCatalog['IBLOCK_ID']);
				$arFields = array(
					'PRODUCT_IBLOCK_ID' => 0,
					'SKU_PROPERTY_ID' => 0,
				);
				if (CCatalog::Update($arCatalog['IBLOCK_ID'], $arFields))
				{
					if (array_key_exists($arCatalog['IBLOCK_ID'], self::$arCatalogCache))
					{
						unset(self::$arCatalogCache[$arCatalog['IBLOCK_ID']]);
						if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
						{
							global $CATALOG_CATALOG_CACHE;
							$CATALOG_CATALOG_CACHE = self::$arCatalogCache;
						}
					}
				}
				else
				{
					return false;
				}
			}
		}
		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		else
		{
			CCatalogSKU::ClearCache();
		}
		return $boolResult;
	}

	public static function LinkSKUIBlock($ID,$SKUID)
	{
		global $APPLICATION;
		global $DB;

		$arMsg = array();
		$boolResult = true;

		$intSKUPropID = 0;
		$ibp = new CIBlockProperty();
		$ID = intval($ID);
		if (0 >= $ID)
		{
			$arMsg[] = array('id' => 'PRODUCT_IBLOCK_ID', 'text' => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_INVALID'));
			$boolResult = false;
		}
		$SKUID = intval($SKUID);
		if (0 >= $SKUID)
		{
			$arMsg[] = array('id' => 'OFFERS_IBLOCK_ID', 'text' => GetMessage('BT_MOD_CATALOG_ERR_OFFERS_ID_INVALID'));
			$boolResult = false;
		}
		if ($ID == $SKUID)
		{
			$arMsg[] = array('id' => 'OFFERS_IBLOCK_ID', 'text' => GetMessage('BT_MOD_CATALOG_ERR_PRODUCT_ID_SELF'));
			$boolResult = false;
		}

		if ($boolResult)
		{
			$arSKUProp = false;
			$rsProps = CIBlockProperty::GetList(array(),array('IBLOCK_ID' => $SKUID,'PROPERTY_TYPE' => 'E','LINK_IBLOCK_ID' => $ID,'ACTIVE' => 'Y'));
			while ($arProp = $rsProps->Fetch())
			{
				if (is_array($arProp) && 'N' == $arProp['MULTIPLE'])
				{
					$arSKUProp = $arProp;
					break;
				}
			}
			if ((false === $arSKUProp) || (is_array($arSKUProp) && 'N' != $arSKUProp['MULTIPLE']))
			{
				$arOFProperty = array(
					'NAME' => GetMessage('BT_MOD_CATALOG_MESS_SKU_PROP_NAME'),
					'IBLOCK_ID' => $SKUID,
					'PROPERTY_TYPE' => 'E',
					'USER_TYPE' =>'SKU',
					'LINK_IBLOCK_ID' => $ID,
					'ACTIVE' => 'Y',
					'SORT' => '5',
					'MULTIPLE' => 'N',
					'CODE' => 'CML2_LINK',
					'XML_ID' => 'CML2_LINK',
					"FILTRABLE" => "Y",
					"SEARCHABLE" => "N",
				);
				$intSKUPropID = $ibp->Add($arOFProperty);
				if (!$intSKUPropID)
				{
					$arMsg[] = array('id' => 'SKU_PROPERTY_ID','text' => str_replace('#ERROR#',$ibp->LAST_ERROR,GetMessage('BT_MOD_CATALOG_ERR_CREATE_SKU_PROPERTY')));
					$boolResult = false;
				}
			}
			elseif (('SKU' != $arSKUProp['USER_TYPE']) || ('CML2_LINK' != $arProp['XML_ID']))
			{
				$arFields = array(
					'USER_TYPE' => 'SKU',
					'XML_ID' => 'CML2_LINK',
				);
				$boolFlag = $ibp->Update($arSKUProp['ID'],$arFields);
				if (false === $boolFlag)
				{
					$arMsg[] = array('id' => 'SKU_PROPERTY_ID','text' => str_replace('#ERROR#',$ibp->LAST_ERROR,GetMessage('BT_MOD_CATALOG_ERR_UPDATE_SKU_PROPERTY')));
					$boolResult = false;
				}
				else
					$intSKUPropID = $arSKUProp['ID'];
			}
			else
			{
				$intSKUPropID = $arSKUProp['ID'];
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return $boolResult;
		}
		else
		{
			CCatalogSKU::ClearCache();
			return $intSKUPropID;
		}
	}

	public static function GetCatalogFieldsList()
	{
		global $DB;
		$arFieldsList = $DB->GetTableFieldsList('b_catalog_iblock');
		$arFieldsList[] = 'CATALOG';
		$arFieldsList[] = 'CATALOG_TYPE';
		$arFieldsList[] = 'OFFERS_IBLOCK_ID';
		$arFieldsList[] = 'OFFERS_PROPERTY_ID';
		$arFieldsList = array_unique($arFieldsList);
		return $arFieldsList;
	}

	public static function IsUserExists()
	{
		global $USER;

		return (isset($USER) && $USER instanceof CUser);
	}
}
?>