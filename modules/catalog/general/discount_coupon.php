<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CCatalogDiscountCoupon</b> - класс для работы с купонами скидок.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/index.php
 * @author Bitrix
 */
class CAllCatalogDiscountCoupon
{
	const TYPE_ONE_TIME = 'Y';
	const TYPE_ONE_ORDER = 'O';
	const TYPE_NO_LIMIT = 'N';

	protected static $arOneOrderCoupons = array();

	
	/**
	* <p>Возвращает массив имеющихся на сайте типов купонов.</p>
	*
	*
	*
	*
	* @param boolean $boolFull = false Параметр определяет в каком виде возвращать массив типов
	* купонов: в кратком (<i>false</i>) или в развернутом (<i>true</i>).
	*
	*
	*
	* @return array <p>Возвращает массив типов купонов:</p> <ul> <li>если <b>$boolFull == false</b>, то
	* вернется массив, содержащий типы купонов в качестве элементов
	* массива;<br><br> </li> <li>если <b>$boolFull == true</b>, типы купонов будут ключами
	* массива, а значениями - названия типов: <ul> [Y] =&gt; Купон на одну
	* позицию заказа <br> [O] =&gt; Купон на один заказ <br> [N] =&gt; Многоразовый
	* купон</ul> </li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getcouponttypes.php
	* @author Bitrix
	*/
	static public function GetCoupontTypes($boolFull = false)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				self::TYPE_ONE_TIME => GetMessage('BT_CAT_COUPON_TYPE_ONE_TIME'),
				self::TYPE_ONE_ORDER => GetMessage('BT_CAT_COUPON_TYPE_ONE_ORDER'),
				self::TYPE_NO_LIMIT => GetMessage('BT_CAT_COUPON_TYPE_NO_LIMIT'),
			);
		}
		return array(
			self::TYPE_ONE_TIME,
			self::TYPE_ONE_ORDER,
			self::TYPE_NO_LIMIT,
		);
	}

	
	/**
	* <p>Метод служит для проверки (и корректировки, если это возможно) параметров, переданных в методы <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a> и <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a>.</p>
	*
	*
	*
	*
	* @param string $ACTION  указывает, для какого метода идет проверка. Возможные значения:
	* <br><ul> <li> <b>ADD</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a>;</li>
	* <li> <b>UPDATE</b> - для метода <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a>.</li>
	* </ul>
	*
	*
	*
	* @param array &$arFields  Ассоциативный массив параметров купона. Массив передается по
	* ссылке и его значения могут быть изменены функцией. <br> Допустимые
	* ключи: <ul> <li> <b>DISCOUNT_ID</b> - код (ID) скидки;</li> <li> <b>ACTIVE</b> - флаг
	* активности купона (Y/N);</li> <li> <b>ONE_TIME</b> - флаг однократного
	* использования купона (Y|N);</li> <li> <b>COUPON</b> - код купона;</li> <li>
	* <b>DATE_APPLY</b> - дата применения купона;</li> <li> <b>DESCRIPTION</b> -
	* комментарий.</li> </ul>
	*
	*
	*
	* @param int $ID = 0 код (ID) купона (только для CCatalogDiscountCoupon::Update). </htm
	*
	*
	*
	* @return boolean <p> В случае корректности переданных параметров возвращает true,
	* иначе - false. Если функция вернула false, с помощью $APPLICATION-&gt;GetException()
	* можно получить текст ошибок.</p> <p><b>Обязательные проверки</b></p>
	* </htm<ul> <li>для <b>CCatalogDiscountCoupon::Add</b> <ul> <li>поле DISCOUNT_ID присутствует и
	* содержит код (ID) существующей скидки;</li> <li>если поле ACTIVE не
	* существует или не равно N, ему присваивается значение Y;</li> <li>если
	* поле ONE_TIME не существует или не равно N, ему присваивается значение
	* Y;</li> <li>поле COUPON существует и содержит уникальный код,
	* отсутствующий в списке купонов;</li> <li>если поле DATE_APPLY не
	* существует или не содержит корректную дату, ему присваивается
	* значение false.</li> </ul> <br> </li> <li>для <b>CCatalogDiscountCoupon::Update</b> <ul> <li>если
	* поле DISCOUNT_ID присутствует, оно должно содержать код (ID)
	* существующей скидки;</li> <li>если поле ACTIVE существует и не равно N,
	* ему присваивается значение Y;</li> <li>если поле ONE_TIME существует и не
	* равно N, ему присваивается значение Y;</li> <li>если поле COUPON
	* существует, оно содержит уникальный код, заданный только для
	* этого купона (с таким ID);</li> <li>если поле DATE_APPLY существует и
	* содержит некорректную дату, ему присваивается значение false.</li> </ul>
	* </li> </ul>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/catalog/fields.php">Структура таблицы</a></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/add.php">CCatalogDiscountCoupon::Add</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/update.php">CCatalogDiscountCoupon::Update</a></li>
	* </ul> </ht<br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/checkfields.php
	* @author Bitrix
	*/
	static public function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB;
		global $APPLICATION;
		global $USER;

		$ACTION = strtoupper($ACTION);
		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;

		if ((is_set($arFields, "DISCOUNT_ID") || $ACTION=="ADD") && intval($arFields["DISCOUNT_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("KGDC_EMPTY_DISCOUNT"), "EMPTY_DISCOUNT_ID");
			return false;
		}

		if ((is_set($arFields, "COUPON") || $ACTION=="ADD") && strlen($arFields["COUPON"]) <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("KGDC_EMPTY_COUPON"), "EMPTY_COUPON");
			return false;
		}
		elseif(is_set($arFields, "COUPON"))
		{
			$arFilter = array("COUPON" => substr($arFields["COUPON"], 0, 32));
			if ($ID > 0)
				$arFilter["!ID"] = $ID;

			$rsCoupon = CCatalogDiscountCoupon::GetList(array(),$arFilter);

			if ($arCoupon = $rsCoupon->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage("KGDC_DUPLICATE_COUPON"), "DUPLICATE_COUPON");
				return false;
			}
		}

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";
		if ((is_set($arFields, "ONE_TIME") || $ACTION=="ADD") && !in_array($arFields["ONE_TIME"], self::GetCoupontTypes()))
			$arFields["ONE_TIME"] = self::TYPE_ONE_TIME;

		if ((is_set($arFields, "DATE_APPLY") || $ACTION=="ADD") && (!$DB->IsDate($arFields["DATE_APPLY"], false, SITE_ID, "FULL")))
			$arFields["DATE_APPLY"] = false;

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = intval($USER->GetID());
		$strDateFunction = $DB->GetNowFunction();
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ('ADD' == $ACTION)
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}
		if ('UPDATE' == $ACTION)
		{
			if (array_key_exists('CREATED_BY', $arFields))
				unset($arFields['CREATED_BY']);
		}

		return true;
	}

	
	/**
	* <p>Метод добавляет код купона <i> coupon</i> в массив доступных для получения скидки купонов текущего покупателя. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @param string $coupon  Код купона.
	*
	*
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного добавления кода
	* купона и <i>false</i> в случае ошибки.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/setcoupon.php
	* @author Bitrix
	*/
	static public function SetCoupon($coupon)
	{
		$coupon = trim($coupon);
		if (empty($coupon))
			return false;

		if (!isset($_SESSION["CATALOG_USER_COUPONS"]) || !is_array($_SESSION["CATALOG_USER_COUPONS"]))
			$_SESSION["CATALOG_USER_COUPONS"] = array();

		$dbCoupon = CCatalogDiscountCoupon::GetList(
			array(),
			array("COUPON" => $coupon, "ACTIVE" => "Y"),
			false,
			false,
			array("ID")
		);
		if ($arCoupon = $dbCoupon->Fetch())
		{
			if (!in_array($coupon, $_SESSION["CATALOG_USER_COUPONS"]))
				$_SESSION["CATALOG_USER_COUPONS"][] = $coupon;

			return true;
		}

		return false;
	}

	
	/**
	* <p>Метод возвращает массив доступных для получения скидки купонов текущего покупателя. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @return array <p>Метод возвращает массив купонов текущего пользователя.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getcoupons.php
	* @author Bitrix
	*/
	static public function GetCoupons()
	{
		if (!isset($_SESSION["CATALOG_USER_COUPONS"]) || !is_array($_SESSION["CATALOG_USER_COUPONS"]))
			$_SESSION["CATALOG_USER_COUPONS"] = array();

		return $_SESSION["CATALOG_USER_COUPONS"];
	}

	static public function EraseCoupon($strCoupon)
	{
		$strCoupon = trim($strCoupon);
		if (empty($strCoupon))
			return false;

		if (!isset($_SESSION["CATALOG_USER_COUPONS"]) || !is_array($_SESSION["CATALOG_USER_COUPONS"]))
		{
			$_SESSION["CATALOG_USER_COUPONS"] = array();
			return false;
		}
		$key = array_search($strCoupon,$_SESSION["CATALOG_USER_COUPONS"]);
		if (false !== $key)
		{
			unset($_SESSION["CATALOG_USER_COUPONS"][$key]);
			$_SESSION["CATALOG_USER_COUPONS"][$key] == ''; // for compatibility with old style code
			return true;
		}
		return false;
	}

	
	/**
	* <p>Метод очищает массив купонов, введенных текущим покупателем. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @return void <p>Метод не возвращает значений.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/clearcoupon.php
	* @author Bitrix
	*/
	static public function ClearCoupon()
	{
		$_SESSION["CATALOG_USER_COUPONS"] = array();
	}

	static public function SetCouponByManage($intUserID,$strCoupon)
	{
		$intUserID = intval($intUserID);
		if (0 < $intUserID)
		{
			$strCoupon = trim($strCoupon);
			if (empty($strCoupon))
				return false;

			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"]))
				$_SESSION["CATALOG_MANAGE_COUPONS"] = array();
			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]))
				$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID] = array();

			$rsCoupons = CCatalogDiscountCoupon::GetList(
				array(),
				array("COUPON" => $strCoupon, "ACTIVE" => "Y"),
				false,
				false,
				array('ID')
			);
			if ($arCoupon = $rsCoupons->Fetch())
			{
				if (!in_array($strCoupon, $_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]))
				$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID][] = $strCoupon;

				return true;
			}
		}
		return false;
	}

	static public function GetCouponsByManage($intUserID)
	{
		$intUserID = intval($intUserID);
		if (0 < $intUserID)
		{
			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"]))
				$_SESSION["CATALOG_MANAGE_COUPONS"] = array();
			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]))
				$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID] = array();

			return $_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID];
		}
		return false;
	}

	static public function EraseCouponByManage($intUserID,$strCoupon)
	{
		$intUserID = intval($intUserID);
		if (0 < $intUserID)
		{
			$strCoupon = trim($strCoupon);
			if (empty($strCoupon))
				return false;
			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"]))
				return false;
			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]))
				return false;
			$key = array_search($strCoupon,$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]);
			if (false !== $key)
			{
				unset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID][$key]);
				return true;
			}
		}
		return false;
	}

	static public function ClearCouponsByManage($intUserID)
	{
		$intUserID = intval($intUserID);
		if (0 < $intUserID)
		{
			if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"]))
				$_SESSION["CATALOG_MANAGE_COUPONS"] = array();
			$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID] = array();
			return true;
		}
		return false;
	}

	static public function OnSetCouponList($intUserID, $arCoupons, $arModules)
	{
		global $USER;
		$boolResult = false;
		if (
			empty($arModules)
			|| (is_array($arModules) && in_array('catalog', $arModules))
		)
		{
			if (!empty($arCoupons))
			{
				if (!is_array($arCoupons))
					$arCoupons = array($arCoupons);

				$intUserID = intval($intUserID);
				if (0 < $intUserID)
				{
					$boolCurrentUser = ($USER->IsAuthorized() && $intUserID == $USER->GetID());
					if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"]))
						$_SESSION["CATALOG_MANAGE_COUPONS"] = array();
					if (!isset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]) || !is_array($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]))
						$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID] = array();

					if ($boolCurrentUser)
					{
						if (!isset($_SESSION["CATALOG_USER_COUPONS"]) || !is_array($_SESSION["CATALOG_USER_COUPONS"]))
							$_SESSION["CATALOG_USER_COUPONS"] = array();
					}

					foreach ($arCoupons as &$strOneCoupon)
					{
						$strOneCoupon = trim($strOneCoupon);
						if (empty($strOneCoupon))
							continue;
						if (
							in_array($strOneCoupon, $_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID])
							&& (!($boolCurrentUser && !in_array($strOneCoupon,$_SESSION["CATALOG_USER_COUPONS"])))
						)
							continue;

						$rsCoupons = CCatalogDiscountCoupon::GetList(
							array(),
							array("COUPON" => $strOneCoupon, "ACTIVE" => "Y"),
							false,
							false,
							array('ID')
						);

						if ($arCoupon = $rsCoupons->Fetch())
						{
							if (!in_array($strOneCoupon, $_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]))
								$_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID][] = $strOneCoupon;
							$boolResult = true;
							if ($boolCurrentUser)
							{
								if (!in_array($strOneCoupon, $_SESSION["CATALOG_USER_COUPONS"]))
									$_SESSION["CATALOG_USER_COUPONS"][] = $strOneCoupon;
							}
						}
					}
					if (isset($strOneCoupon))
						unset($strOneCoupon);
				}
				elseif (0 == $intUserID && !$USER->IsAuthorized())
				{
					if (!isset($_SESSION["CATALOG_USER_COUPONS"]) || !is_array($_SESSION["CATALOG_USER_COUPONS"]))
						$_SESSION["CATALOG_USER_COUPONS"] = array();
					foreach ($arCoupons as &$strOneCoupon)
					{
						$strOneCoupon = trim($strOneCoupon);
						if (empty($strOneCoupon) || in_array($strOneCoupon, $_SESSION["CATALOG_USER_COUPONS"]))
							continue;

						$rsCoupons = CCatalogDiscountCoupon::GetList(
							array(),
							array("COUPON" => $strOneCoupon, "ACTIVE" => "Y"),
							false,
							false,
							array('ID')
						);

						if ($arCoupon = $rsCoupons->Fetch())
						{
							if (!in_array($strOneCoupon, $_SESSION["CATALOG_USER_COUPONS"]))
								$_SESSION["CATALOG_USER_COUPONS"][] = $strOneCoupon;
							$boolResult = true;
						}
					}
					if (isset($strOneCoupon))
						unset($strOneCoupon);
				}
			}
		}

		return $boolResult;
	}

	static public function OnClearCouponList($intUserID, $arCoupons, $arModules)
	{
		global $USER;

		$boolResult = false;
		if (
			empty($arModules)
			|| (is_array($arModules) && in_array('catalog', $arModules))
		)
		{
			if (!empty($arCoupons))
			{
				if (!is_array($arCoupons))
					$arCoupons = array($arCoupons);

				$intUserID = intval($intUserID);
				if (0 < $intUserID)
				{
					$boolCurrentUser = ($USER->IsAuthorized() && $intUserID == $USER->GetID());
					if (
						isset($_SESSION["CATALOG_MANAGE_COUPONS"]) && is_array($_SESSION["CATALOG_MANAGE_COUPONS"])
						&& isset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]) && is_array($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID])
					)
					{
						foreach ($arCoupons as &$strOneCoupon)
						{
							$strOneCoupon = trim($strOneCoupon);
							if (empty($strOneCoupon))
								continue;
							$key = array_search($strOneCoupon, $_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]);
							if (false !== $key)
							{
								unset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID][$key]);
								$boolResult = true;
							}
						}
						if (isset($strOneCoupon))
							unset($strOneCoupon);
					}
					if ($boolCurrentUser
						&& isset($_SESSION["CATALOG_USER_COUPONS"]) && is_array($_SESSION["CATALOG_USER_COUPONS"])
					)
					{
						foreach ($arCoupons as &$strOneCoupon)
						{
							$strOneCoupon = trim($strOneCoupon);
							if (empty($strOneCoupon))
								continue;
							$key = array_search($strOneCoupon, $_SESSION["CATALOG_USER_COUPONS"]);
							if (false !== $key)
							{
								unset($_SESSION["CATALOG_USER_COUPONS"][$key]);
								$boolResult = true;
							}
						}
						if (isset($strOneCoupon))
							unset($strOneCoupon);
					}
				}
				elseif (0 == $intUserID && !$USER->IsAuthorized())
				{
					if (
						isset($_SESSION["CATALOG_USER_COUPONS"]) && is_array($_SESSION["CATALOG_USER_COUPONS"])
					)
					{
						foreach ($arCoupons as &$strOneCoupon)
						{
							$strOneCoupon = trim($strOneCoupon);
							if (empty($strOneCoupon))
								continue;
							$key = array_search($strOneCoupon, $_SESSION["CATALOG_USER_COUPONS"]);
							if (false !== $key)
							{
								unset($_SESSION["CATALOG_USER_COUPONS"][$key]);
								$boolResult = true;
							}
						}
						if (isset($strOneCoupon))
							unset($strOneCoupon);
					}
				}
			}
		}
		return $boolResult;
	}

	static public function OnDeleteCouponList($intUserID, $arModules)
	{
		global $USER;

		$boolResult = false;
		if (
			empty($arModules)
			|| (is_array($arModules) && in_array('catalog', $arModules))
		)
		{
			$intUserID = intval($intUserID);
			if (0 < $intUserID)
			{
				$boolCurrentUser = ($USER->IsAuthorized() && $intUserID == $USER->GetID());
				if (
					isset($_SESSION["CATALOG_MANAGE_COUPONS"]) && is_array($_SESSION["CATALOG_MANAGE_COUPONS"])
					&& isset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]) && is_array($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID])
				)
				{
					unset($_SESSION["CATALOG_MANAGE_COUPONS"][$intUserID]);
					$boolResult = true;
				}
				if ($boolCurrentUser
					&& isset($_SESSION["CATALOG_USER_COUPONS"]) && is_array($_SESSION["CATALOG_USER_COUPONS"])
				)
				{
					unset($_SESSION["CATALOG_USER_COUPONS"]);
					$boolResult = true;
				}
			}
			elseif (0 == $intUserID && !$USER->IsAuthorized())
			{
				if (
					isset($_SESSION["CATALOG_USER_COUPONS"]) && is_array($_SESSION["CATALOG_USER_COUPONS"])
				)
				{
					unset($_SESSION["CATALOG_USER_COUPONS"]);
					$boolResult = true;
				}
			}
		}
		return $boolResult;
	}

	
	/**
	* <p>Метод проверяет существование купона.</p>
	*
	*
	*
	*
	* @param string $strCoupon  Код купона.
	*
	*
	*
	* @return bool <p> В случае наличия купона возвращает true, иначе - false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/isexistcoupon.php
	* @author Bitrix
	*/
	static public function IsExistCoupon($strCoupon)
	{
		return false;
	}
}
?>