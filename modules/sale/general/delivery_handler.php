<?
IncludeModuleLangFile(__FILE__);

Bitrix\Main\Loader::registerAutoLoadClasses('sale', array(
	"\\Bitrix\\Sale\\Delivery\\Services\\Automatic" => "lib/delivery/services/automatic.php"
));

/** @deprecated */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/index.php
 * @author Bitrix
 * @deprecated
 */
class CAllSaleDeliveryHandler
{
	public static $actions = array(
		"REQUEST_SELF" => 0, // Request to delivery company to wait a cargo
		"REQUEST_TAKE" => 1  // Request to delivery company to take a cargo
	);

	/** public: Initialize
	 * includes all delivery_*.php files in /php_interface/include/sale_delivery/ and /modules/sale/delivery/
	 * double files with the same name are ignored
	 * @deprecated
	 */
	public static function Initialize()
	{
		\Bitrix\Sale\Delivery\Services\Automatic::initHandlers();
	}

	/**
	 * private: get all handlers
	 * @deprecated
	 */
	protected static function __getRegisteredHandlers()
	{
		return \Bitrix\Sale\Delivery\Services\Automatic::getRegisteredHandlers("SID");
	}

	/**
	 * get full list based on FS
	 * @deprecated
	 */
	
	/**
	* <p>Метод возвращает список всех имеющихся обработчиков. Список обработчиков строится на основе события onSaleDeliveryHandlersBuildList. Метод статический.</p>
	*
	*
	* @param array $arrayarSort = array("SORT" => "ASC") Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array("<i>параметр_сортировки</i>"
	* =&gt; "<i>направление_сортировки</i>" [, ...])</pre> <p>В качестве параметра
	* сортировки может выступать одно из следующих значений:</p> <ul> <li>
	* <b>SORT</b> - параметр "сортировка"; </li> <li> <b>NAME</b> - наименования службы
	* доставки; </li> <li> <b>SID</b> - строковой идентификатор службы доставки;
	* </li> <li> <b>HANDLER</b> - путь к обработчику службы доставки; </li> <li> <b>ACTIVE</b> -
	* флаг активности службы доставки. </li> </ul> <p>В качестве
	* "направление_сортировки" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).</p> Значение по умолчанию -
	* массив array("SORT" =&gt; "ASC") - означает, что результат будет отсортирован
	* по возрастанию.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий записи со
	* структурой, аналогичной <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getlist.php">CSaleDeliveryHandlers::GetList()</a>
	* с единственным изменением:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>INSTALLED</td> <td>Флаг,
	* показывающий, есть ли в БД конфигурация для данного обработчика
	* (Y|N).</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* CModule::IncludeModule('sale');
	* 
	* $dbResult = CSaleDeliveryHandler::GetAdminList(
	*   array(
	*     'SORT' =&gt; 'ASC', 
	*     'NAME' =&gt; 'ASC'
	*   ) 
	* );
	* echo '&lt;ul&gt;';
	* while ($arResult = $dbResult-&gt;GetNext())
	* {
	*   echo '&lt;li&gt;('.$arResult['SID'].') &lt;b&gt;'.$arResult['NAME'].'&lt;/b&gt;&lt;br /&gt;'; 
	*   echo '&lt;small&gt;'.$arResult['DESCRIPTION'].'&lt;/small&gt;&lt;/li&gt;';
	* }
	* echo '&lt;/ul&gt;';
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getadminlist.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetAdminList($arSort = array("SORT" => "ASC"))
	{
		return  self::GetList($arSort, array("SITE_ID" => "ALL"));
	}

	protected static function isFieldInFilter($fieldName, $filter)
	{
		foreach($filter as $key => $value)
			if(preg_replace('/[^A-Z_]/', '', $key) == $fieldName)
				return true;

		return false;
	}

	protected static function getFilterValue($fieldName, $filter)
	{
		foreach($filter as $fName => $fValue)
			if(preg_replace('/[^A-Z_]/', '', $fName) == $fieldName)
				return $fValue;

		return false;
	}

	protected static function convertFilterOldToNew(array $oldFilter)
	{
		$result = array_intersect_key($oldFilter, Bitrix\Sale\Delivery\Services\Table::getMap());

		$result[] = array(
			'LOGIC' => 'OR',
			'=CLASS_NAME' => array(
				'\\Bitrix\\Sale\\Delivery\\Services\\Automatic',
				'\\Bitrix\\Sale\\Delivery\\Services\\AutomaticProfile'
			)
		);

		//$result['=PARENT_ID'] = '0';

		if(empty($oldFilter))
			return $result;

		if(self::isFieldInFilter("SID", $oldFilter))
		{
			$result["=CODE"] = self::getFilterValue("SID", $oldFilter);
			unset($oldFilter["SID"]);
		}

		if(self::isFieldInFilter("ID", $oldFilter))
		{
			$result["=CODE"] = self::getFilterValue("ID", $oldFilter);
			unset($oldFilter["ID"]);
		}

		if(self::isFieldInFilter("ACTIVE", $oldFilter))
		{
			$result["=ACTIVE"] = self::getFilterValue("ACTIVE", $oldFilter);

			if($result["=ACTIVE"] == "ALL")
				unset($result["=ACTIVE"]);

			unset($oldFilter["ACTIVE"]);
		}

		if(self::isFieldInFilter("HANDLER", $oldFilter))
		{
			$result["=HANDLER"] = self::getFilterValue("HANDLER", $oldFilter);
			unset($oldFilter["HANDLER"]);
		}

		if(self::isFieldInFilter("PATH", $oldFilter))
		{
			$result["=HANDLER"] = self::getFilterValue("PATH", $oldFilter);
			unset($oldFilter["PATH"]);
		}

		return $result;
	}


	protected static function isFieldInFilter2($fieldName, $filter)
	{
		$result = false;

		foreach($filter as $key => $value)
			if(preg_replace('/[^A-Z_]/', '', $key) == $fieldName)
				return true;

		return $result;
	}

	protected static function checkRestrictionFilter(array $restriction, array $filter)
	{
		$result = true;
		switch($restriction["CLASS_NAME"])
		{
			case '\Bitrix\Sale\Delivery\Restrictions\BySite':
				$result = !(self::isFieldInFilter2("SITE_ID", $filter) && strlen(self::getFilterValue("SITE_ID", $filter)) > 0 && self::getFilterValue("SITE_ID", $filter)!= $restriction["PARAMS"]["SITE_ID"]);
				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByWeight':
				$result = !(isset($filter["COMPABILITY"]["WEIGHT"])
					&& (
						floatval($filter["COMPABILITY"]["WEIGHT"]) < floatval($restriction["PARAMS"]["MIN_WEIGHT"])
						||
						floatval($filter["COMPABILITY"]["WEIGHT"]) > floatval($restriction["PARAMS"]["MAX_WEIGHT"])
					)
				);
				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByPrice':
				$result = !(isset($filter["COMPABILITY"]["PRICE"])
					&& (
						floatval($filter["COMPABILITY"]["PRICE"]) < floatval($restriction["PARAMS"]["MIN_WEIGHT"])
						||
						floatval($filter["COMPABILITY"]["PRICE"]) > floatval($restriction["PARAMS"]["MAX_WEIGHT"])
					)
				);
				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByLocation':
			case '\Bitrix\Sale\Delivery\Restrictions\ByPaySystem':
			default:
				break;
		}

		return $result;
	}

	/**
	 * get handlers list based on DB data
	 * @deprecated
	 */
	
	/**
	* <p>Метод возвращает список обработчиков автоматизированных служб доставки, для которых установлены настройки в БД. Метод статический.</p>
	*
	*
	* @param array $arrayarSort = array("SORT" => "ASC") Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array("<i>параметр_сортировки</i>"
	* =&gt; "<i>направление_сортировки</i>" [, ...])</pre> <p>В качестве параметра
	* сортировки может выступать одно из следующих значений:</p> <ul> <li>
	* <b>SORT</b> - параметр "сортировка"; </li> <li> <b>NAME</b> - наименования службы
	* доставки; </li> <li> <b>SID</b> - строковой идентификатор службы доставки;
	* </li> <li> <b>HANDLER</b> - путь к обработчику службы доставки; </li> <li> <b>ACTIVE</b> -
	* флаг активности службы доставки. </li> </ul> <p>В качестве
	* "направление_сортировки" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).</p> Значение по умолчанию -
	* массив array("SORT" =&gt; "ASC") - означает, что результат будет отсортирован
	* по возрастанию.
	*
	* @param array $arrayarFilter = array() Массив, в соответствии с которым фильтруются записи службы
	* доставки.<br>Массив имеет вид: <pre
	* class="syntax">array("<i>фильтруемое_поле</i>"=&gt;"<i>значения_фильтра</i>" [,
	* ...])</pre>"<i>фильтруемое_поле</i>" может принимать значения: <ul> <li>
	* <b>ACTIVE</b> - фильтр по активности (Y|N); передача значения
	* <code>"ACTIVE"=&gt;"ALL"</code> выводит все элементы без учета их состояния;
	* <br>по умолчанию выводятся только активные элементы; </li> <li> <b>SITE_ID</b>
	* - по сайту; ; передача значения <code>"SITE_ID"=&gt;"ALL"</code> выводит
	* настройки для всех сайтов; <br>по умолчанию получаются настройки
	* службы доставки только для текущего сайта; </li> <li> <b>SID</b> - по
	* строковому идентификатору обработчика; </li> <li> <b>HANDLER</b> - фильтр по
	* части пути к файлу обработчика. </li> <li> <b>COMPABILITY</b> - проверка
	* совместимости обработчика с параметрами заказа; <br>значение
	* должно быть массивом данных по заказу следующей структуры: <ul> <li>
	* <b>WEIGHT</b> - суммарный вес заказа; </li> <li> <b>PRICE</b> - суммарная стоимость
	* заказа; </li> <li> <b>LOCATION_FROM</b> - ID местоположения магазина
	* (устанавливается в настройках модуля); </li> <li> <b>LOCATION_TO</b> - ID
	* местоположения, указанному при оформлении заказа. </li> </ul> </li>
	* </ul>Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий записи следующей
	* структуры:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>SID</td> <td>Строковой идентификатор
	* обработчика доставки.</td> </tr> <tr> <td>NAME</td> <td>Наименование службы
	* доставки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание службы доставки.</td> </tr>
	* <tr> <td>DESCRIPTION_INNER</td> <td>"Внутреннее" описание функционала
	* обработчика службы доставки.</td> </tr> <tr> <td>LID</td> <td>Идентификатор
	* сайта, для которого установлены настройки.</td> </tr> <tr> <td>ACTIVE</td>
	* <td>Флаг активности службы доставки.</td> </tr> <tr> <td>SORT</td> <td>Значение
	* параметра сортировки для данной службы доставки.</td> </tr> <tr>
	* <td>BASE_CURRENCY</td> <td>Идентификатор валюты, в которой работает
	* обработчик службы доставки.</td> </tr> <tr> <td>TAX_RATE</td> <td>Значение
	* наценки, автоматически добавляемой к стоимости доставки (%).</td> </tr>
	* <tr> <td>HANDLER</td> <td>Путь к файлу обработчика доставки.</td> </tr> <tr>
	* <td>DBSETSETTINGS</td> <td>callback к методу обработчика, обеспечивающему
	* сохранение массива настроек в БД.</td> </tr> <tr> <td>DBGETSETTINGS</td> <td>callback к
	* методу обработчика, обеспечивающему получение массива настроек
	* из БД.</td> </tr> <tr> <td>GETCONFIG</td> <td>callback к методу обработчика,
	* возвращающему список настроек обработчика.</td> </tr> <tr> <td>COMPATIBILITY</td>
	* <td>callback к методу обработчика, осуществляющему проверку
	* применимости обработчика к заказу.</td> </tr> <tr> <td>CALCULATE</td> <td>callback к
	* методу обработчика, осуществляющему расчёт стоимости
	* доставки.</td> </tr> <tr> <td>PROFILES</td> <td>Массив профилей обработки
	* доставки. Представляет собой ассоциативный массив вида: <pre
	* class="syntax">Array ( "<i>строковый_идентификатор_профиля</i>" =&gt; Array ( "TITLE" =&gt;
	* "<i>название_профиля</i>", "DESCRIPTION" =&gt; "<i>описание_профиля</i>",
	* "RESTRICTIONS_WEIGHT" =&gt; Array ( //< ограничения обработчика по весу >// ),
	* "RESTRICTIONS_SUM" =&gt; Array ( //< ограничения обработчика по стоимости >// ),
	* "ACTIVE" =&gt; "<i>флаг_активности_профиля</i>", ), //< ................... >// ) </pre> </td>
	* </tr> <tr> <td>CONFIG</td> <td>Массив настроек обработчика доставки со
	* значениями. Подробнее см. <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/delivery.php">Руководство по созданию
	* автоматизированных обработчиков доставки</a>.</td> </tr> <tr>
	* <td>PROFILE_USE_DEFAULT</td> <td>Значение флага "используются параметры
	* профилей по умолчанию" (Y|N).</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* CModule::IncludeModule('sale');
	* 
	* $dbResult = CSaleDeliveryHandler::GetList(
	*   array(
	*     'SORT' =&gt; 'ASC', 
	*     'NAME' =&gt; 'ASC'
	*   ), 
	*   array(
	*     'ACTIVE' =&gt; 'Y'
	*   )
	* );
	* echo '&lt;ul&gt;';
	* while ($arResult = $dbResult-&gt;GetNext())
	* {
	*   echo '&lt;li&gt;('.$arResult['SID'].') &lt;b&gt;'.$arResult['NAME'].'&lt;/b&gt;&lt;br /&gt;'; 
	*   echo '&lt;small&gt;'.$arResult['DESCRIPTION'].'&lt;/small&gt;&lt;/li&gt;';
	* }
	* echo '&lt;/ul&gt;';
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getlist.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetList($arSort = array("SORT" => "ASC"), $arFilter = array())
	{
		if(self::isFieldInFilter2("SITE", $arFilter))
		{
			$arFilter["SITE_ID"] = self::getFilterValue("SITE", $arFilter);
			unset($arFilter["SITE"]);
		}

		if(!isset($arFilter["SITE_ID"]))
			$arFilter["SITE_ID"] = SITE_ID;
		elseif($arFilter["SITE_ID"] == "ALL")
			unset($arFilter["SITE_ID"]);

		if(!isset($arFilter["ACTIVE"]))
			$arFilter["ACTIVE"] = "Y";
		elseif($arFilter["ACTIVE"] == "ALL")
			unset($arFilter["ACTIVE"]);

		$params = array(
			'order' => array_intersect_key($arSort, Bitrix\Sale\Delivery\Services\Table::getMap()),
			'filter' => self::convertFilterOldToNew($arFilter));

		$services = array();

		$dbRes = \Bitrix\Sale\Delivery\Services\Table::getList($params);

		while($service = $dbRes->fetch())
		{
			$dbRstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' => array(
					"=DELIVERY_ID" => $service["ID"],
				)
			));

			while($restr = $dbRstrRes->fetch())
			{
				if(!self::checkRestrictionFilter($restr, $arFilter))
					continue 2;

				if($restr["CLASS_NAME"] == '\Bitrix\Sale\Delivery\Restrictions\BySite')
					$service["LID"] = $restr["PARAMS"]["SITE_ID"];
			}

			$srv = \Bitrix\Sale\Delivery\Services\Automatic::convertNewServiceToOld($service);

			if (is_array($arFilter["COMPABILITY"]))
			{
				$arProfiles = CSaleDeliveryHandler::GetHandlerCompability($arFilter["COMPABILITY"], $srv);

				if (!is_array($arProfiles) || count($arProfiles) <= 0)
					continue;
				else
					$srv = $arProfiles;
			}

			if($srv)
				$services[] = $srv;
		}

		$result = new \CDBResult;
		$result->InitFromArray($services);

		return $result;
	}

	/**
	 * get services compability. result - list of delivery profiles;
	 * @deprecated
	 */
	
	/**
	* <p>Метод возвращает список профилей обработчика, подходящих данному заказу. Осуществляется проверка по весу и стоимости, а также вызывается метод COMPABILITY обработчика. Метод статический.</p>
	*
	*
	* @param array $arOrder  Массив заказа. Представляет собой ассоциативный массив с
	* ключами: <ul> <li> <b>WEIGHT</b> - суммарный вес заказа в граммах; </li> <li>
	* <b>PRICE</b> - суммарная стоимость заказа в базовой валюте магазина; </li>
	* <li> <b>LOCATION_FROM</b> - ID местоположения магазина, настраиваемого в
	* настройках модуля "Интернет-магазин"; </li> <li> <b>LOCATION_TO</b> - ID
	* местоположения, указываемого клиентом при оформлении заказа. </li>
	* </ul>
	*
	* @param array $arHandler  Описательный массив обработчика, возвращаемый методами <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getbysid.php">CSaleDeliveryHandler::GetBySID()</a>,
	* <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getlist.php">CSaleDeliveryHandler::GetList()</a>,
	* <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getadminlist.php">CSaleDeliveryHandler::GetAdminList()</a>,
	*
	* @param mixed $SITE_ID = SITE_ID Идентификатор сайта. По умолчанию используется текущий.
	*
	* @return mixed <p>Метод возвращает массив профилей доставки, подходящих для
	* данного заказа, либо false в случае, если ни один из профилей не
	* подходит. Массив возвращается в том формате, в котором он указан в
	* элементе "PROFILES" описательного массива обработчика, т.е.</p> <pre
	* class="syntax">Array ( "<i>строковый_идентификатор_профиля</i>" =&gt; Array ( "TITLE" =&gt;
	* "<i>название_профиля</i>", "DESCRIPTION" =&gt; "<i>описание_профиля</i>",
	* "RESTRICTIONS_WEIGHT" =&gt; Array ( //< ограничения обработчика по весу >// ),
	* "RESTRICTIONS_SUM" =&gt; Array ( //< ограничения обработчика по стоимости >// ),
	* "ACTIVE" =&gt; "<i>флаг_активности_профиля</i>", ), //< ................... >// ) </pre> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_gethandlercompability.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetHandlerCompability($arOrder, $arHandler, $SITE_ID = SITE_ID)
	{
		if (!defined('SALE_DH_INITIALIZED'))
			CSaleDeliveryHandler::Initialize();

		$currency = CSaleLang::GetLangCurrency($SITE_ID);

		if ($currency != $arHandler["BASE_CURRENCY"])
			$arOrder["PRICE"] = CCurrencyRates::ConvertCurrency($arOrder["PRICE"], $currency, $arHandler["BASE_CURRENCY"]);

		if (is_array($arHandler["PROFILES"]))
		{
			$arProfilesList = $arHandler["PROFILES"];
			foreach ($arProfilesList as $profile_id => $arProfile)
			{
				if (is_array($arProfile["RESTRICTIONS_WEIGHT"]) && count($arProfile["RESTRICTIONS_WEIGHT"]) > 0)
				{

					$arOrder["WEIGHT"] = doubleval($arOrder["WEIGHT"]);

					if ($arOrder["WEIGHT"] < $arProfile["RESTRICTIONS_WEIGHT"][0])
					{
						unset($arProfilesList[$profile_id]);
						continue;
					}
					else
					{
						if (
							is_set($arProfile["RESTRICTIONS_WEIGHT"], 1)
							&&
							Doubleval($arProfile["RESTRICTIONS_WEIGHT"][1]) > 0
							&&
							$arOrder["WEIGHT"] > $arProfile["RESTRICTIONS_WEIGHT"][1]
						)
						{
							unset($arProfilesList[$profile_id]);
							continue;
						}

					}
				}


				if (is_array($arProfile["RESTRICTIONS_SUM"]) && count($arProfile["RESTRICTIONS_SUM"]) > 0)
				{
					if (
						$arOrder["PRICE"] < $arProfile["RESTRICTIONS_SUM"][0]
						||
						(
							is_set($arProfile["RESTRICTIONS_SUM"], 1)
							&&
							Doubleval($arProfile["RESTRICTIONS_SUM"][1]) > 0
							&&
							$arOrder["PRICE"] > $arProfile["RESTRICTIONS_SUM"][1]
						)
					)
					{
						unset($arProfilesList[$profile_id]);
						continue;
					}
				}

				if (is_array($arProfile["RESTRICTIONS_DIMENSIONS"]) && count($arProfile["RESTRICTIONS_DIMENSIONS"]) > 0)
				{
					if (!self::checkDimensions($arOrder["MAX_DIMENSIONS"], $arProfile["RESTRICTIONS_DIMENSIONS"]))
					{

						unset($arProfilesList[$profile_id]);
						continue;
					}
				}

				if (intval($arProfile["RESTRICTIONS_DIMENSIONS_SUM"]) > 0)
				{
					if (!self::checkDimensionsSum($arOrder["ITEMS"], intval($arProfile["RESTRICTIONS_DIMENSIONS_SUM"])))
					{
						unset($arProfilesList[$profile_id]);
						continue;
					}
				}

				if (intval($arProfile["RESTRICTIONS_MAX_SIZE"]) > 0)
				{
					if (!self::checkMaxSize($arOrder["ITEMS"], intval($arProfile["RESTRICTIONS_MAX_SIZE"])))
					{
						unset($arProfilesList[$profile_id]);
						continue;
					}
				}
			}

			if (is_callable($arHandler["COMPABILITY"]))
			{
				$arHandlerProfilesList = call_user_func($arHandler["COMPABILITY"], $arOrder, $arHandler["CONFIG"]["CONFIG"]);

				if (is_array($arHandlerProfilesList))
				{
					foreach ($arProfilesList as $profile_id => $arHandler)
					{
						if (!in_array($profile_id, $arHandlerProfilesList))
							unset($arProfilesList[$profile_id]);
					}
				}
				else
					return array();
			}
			return $arProfilesList;
		}
		else
			return false;
	}

	/**
	 * @param $SID
	 * @param $profileId
	 * @param $arOrder
	 * @param bool $siteId
	 * @return array|mixed
	 * @deprecated
	 */
	public static function GetHandlerExtraParams($SID, $profileId, $arOrder, $siteId = false)
	{
		$result = array();
		if (!defined('SALE_DH_INITIALIZED'))
			CSaleDeliveryHandler::Initialize();

		if (!$siteId)
			$siteId = SITE_ID;

		$rsDeliveryHandler = CSaleDeliveryHandler::GetBySID($SID, $siteId);
		if ($arHandler = $rsDeliveryHandler->Fetch())
		{
			if (isset($arHandler["GETEXTRAINFOPARAMS"]) && is_callable($arHandler["GETEXTRAINFOPARAMS"]))
			{
				$result = call_user_func($arHandler["GETEXTRAINFOPARAMS"], $arOrder, $arHandler["CONFIG"]["CONFIG"], $profileId, $siteId);
			}
		}

		return $result;
	}

	/**
	 * @param $deliveryId
	 * @return array|mixed
	 * @deprecated
	 */
	public static function getActionsList($deliveryId)
	{
		$result = array();

		if (!defined('SALE_DH_INITIALIZED'))
			CSaleDeliveryHandler::Initialize();

		$arDId = CSaleDeliveryHelper::getDeliverySIDAndProfile($deliveryId);
		$rsDeliveryHandler = CSaleDeliveryHandler::GetBySID($arDId["SID"]);

		if ($arHandler = $rsDeliveryHandler->Fetch())
		{
			if (isset($arHandler["GETORDERSACTIONSLIST"]) && is_callable($arHandler["GETORDERSACTIONSLIST"]))
			{
				$result = call_user_func($arHandler["GETORDERSACTIONSLIST"]);
			}
		}

		return $result;
	}

	/**
	 * @param $deliveryId
	 * @param $actionId
	 * @param $arOrder
	 * @return array|mixed
	 * @deprecated
	 */
	public static function executeAction($deliveryId, $actionId, $arOrder)
	{
		$result = array();
		$arDId = CSaleDeliveryHelper::getDeliverySIDAndProfile($deliveryId);

		if (!defined('SALE_DH_INITIALIZED'))
			CSaleDeliveryHandler::Initialize();

		$rsDeliveryHandler = CSaleDeliveryHandler::GetBySID($arDId["SID"]);

		if ($arHandler = $rsDeliveryHandler->Fetch())
		{
			if (isset($arHandler["EXECUTEACTION"]) && is_callable($arHandler["EXECUTEACTION"]))
			{
				$result = call_user_func($arHandler["EXECUTEACTION"], $actionId, $arDId["PROFILE"], $arOrder, $arHandler["CONFIG"]["CONFIG"]);
			}
		}

		return $result;
	}

	/**
	 * get services data by DB sID
	 * @deprecated
	 */
	
	/**
	* <p>Данный метод служит для получения информации по конкретному обработчику по его строковому идентификатору. Метод статический.</p>
	*
	*
	* @param string $SID  Строковый идентификатор обработчика.
	*
	* @param mixed $SITE_ID = false Идентификатор сайта. По умолчанию используется текущий.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий запись со
	* структурой, аналогичной <a
	* href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getlist.php">CSaleDeliveryHandler::GetList()</a>.</p>
	* <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* CModule::IncludeModule('sale');
	* 
	* $dbResult = CSaleDeliveryHandler::GetBySID('cpcr');
	* 
	* if ($arResult = $dbResult-&gt;GetNext())
	* {
	*   echo '('.$arResult['SID'].') &lt;b&gt;'.$arResult['NAME'].'&lt;/b&gt;&lt;br /&gt;'; 
	*   echo '&lt;small&gt;'.$arResult['DESCRIPTION'].'&lt;/small&gt;&lt;ul&gt;';
	*   foreach ($arResult['PROFILES'] as $profile_id =&gt; $arProfile)
	*   {
	*     echo '&lt;li&gt;('.$profile_id.') '.$arProfile['TITLE'].'&lt;/li&gt;';
	*   }
	*   echo '&lt;/ul&gt;';
	* }
	* else
	* {
	*   echo 'Обработчик не найден';
	* }
	* 
	* </htm
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_getbysid.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetBySID($SID, $SITE_ID = false)
	{
		static $cache = array();

		if (!isset($cache[$SITE_ID]))
			$cache[$SITE_ID] = array();

		if (!isset($cache[$SITE_ID][$SID]))
		{
			$dbRes = self::GetList(array(),array("SID" => $SID, "SITE_ID" => $SITE_ID));

			while($handler = $dbRes->Fetch())
				$cache[$SITE_ID][$SID][] = $handler;
		}

		$dbResult = new CDBResult();
		$dbResult->InitFromArray($cache[$SITE_ID][$SID]);

		return $dbResult;
	}

	/** @deprecated */
	public static function CheckFields($arData)
	{
		global $APPLICATION;

		$numberFieldsProf = array("RESTRICTIONS_WEIGHT", "RESTRICTIONS_SUM", "TAX_RATE", "RESTRICTIONS_MAX_SIZE", "RESTRICTIONS_DIMENSIONS_SUM");

		if(isset($arData["PROFILES"]) && is_array($arData["PROFILES"]))
		{
			foreach ($arData["PROFILES"] as $profileId => $arProfile)
			{
				foreach ($numberFieldsProf as $fName)
				{
					if (isset($arProfile[$fName]))
					{
						if(!is_array($arProfile[$fName]))
							$arProfile[$fName] = array($arProfile[$fName]);

						foreach ($arProfile[$fName] as $fValue)
						{
							if($result = CSaleDeliveryHelper::getFormatError($fValue, 'NUMBER', GetMessage("SALE_DH_CF_ERROR_P_".$fName)))
							{
								$APPLICATION->ThrowException($result, $fName);
								return false;
							}
						}
					}
				}
			}
		}

		if(isset($arData['TAX_RATE']) && $result = CSaleDeliveryHelper::getFormatError($arData['TAX_RATE'], 'NUMBER', GetMessage('SALE_DH_CF_ERROR_TAX_RATE')))
		{
			$APPLICATION->ThrowException($result, 'TAX_RATE');
			return false;
		}

		if(isset($arData['SORT']) && $result = CSaleDeliveryHelper::getFormatError($arData['SORT'], 'NUMBER', GetMessage('SALE_DH_CF_ERROR_SORT')))
		{
			$APPLICATION->ThrowException($result, 'SORT');
			return false;
		}

		return true;
	}

	/**
	 * @param $sid
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	public static function getServiceParams($sid, $siteId = false)
	{
		$res = \Bitrix\Sale\Delivery\Services\Table::getList(array(
			'filter' => array(
				'CODE' => $sid,
				'=CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Automatic'
			)
		));

		while($handler = $res->fetch())
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"=DELIVERY_ID" => $handler["ID"],
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite'
				)
			));

			$restrict = $rstrRes->fetch();

			if(!is_array($restrict) && !$siteId)
				return $handler;

			if($restrict["PARAMS"]["SITE_ID"] == $siteId)
				return $handler;
		}

		return array();
	}

	/**
	 * @param $deliveryId
	 * @param $siteId
	 * @param $update
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	protected static function saveRestrictionBySiteId($deliveryId, $siteId, $update)
	{
		$rfields = array(
			"DELIVERY_ID" => $deliveryId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite',
			"PARAMS" => array(
				"SITE_ID" => $siteId
			)
		);

		if($update)
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}
		else
		{
			$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		return $rres->isSuccess();
	}

	/**
	 * @param $deliveryId
	 * @param array $weightParams
	 * @param $update
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	protected static function saveRestrictionByWeight($deliveryId, array $weightParams, $update)
	{
		$rfields = array(
			"DELIVERY_ID" => $deliveryId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByWeight',
			"PARAMS" => array(
				"MIN_WEIGHT" => $weightParams[0],
				"MAX_WEIGHT" => $weightParams[1]
			)
		);

		if($update)
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByWeight'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}
		else
		{
			$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		return $rres->isSuccess();
	}

	protected static function saveRestrictionByPublicShow($deliveryId, $publicShow, $update)
	{
		$rfields = array(
			"DELIVERY_ID" => $deliveryId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPublicMode',
			"PARAMS" => array(
				"PUBLIC_SHOW" => ($publicShow) ? 'Y' : 'N'
			)
		);

		if($update)
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPublicMode'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}
		else
		{
			$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		return $rres->isSuccess();
	}

	/**
	 * @param $deliveryId
	 * @param array $priceParams
	 * @param $currency
	 * @param $update
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	protected static function saveRestrictionByPrice($deliveryId, array $priceParams, $currency, $update)
	{
		$rfields = array(
			"DELIVERY_ID" => $deliveryId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPrice',
			"PARAMS" => array(
				"MIN_PRICE" => $priceParams[0],
				"MAX_PRICE" => $priceParams[1],
				"CURRENCY" => $currency
			)
		);

		if($update)
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPrice'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}
		else
		{
			$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		return $rres->isSuccess();
	}

	/**
	 * @param $deliveryId
	 * @param array $params
	 * @param $update
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	protected static function saveRestrictionByDimensions($deliveryId, array $params, $update)
	{
		$rfields = array(
			"DELIVERY_ID" => $deliveryId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByDimensions',
			"PARAMS" => array(
				"LENGTH" => $params["LENGTH"],
				"WIDTH" => $params["WIDTH"],
				"HEIGHT" => $params["HEIGHT"],
			)
		);

		if($update)
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByDimensions'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}
		else
		{
			$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		return $rres->isSuccess();
	}

	/**
	 * @param $deliveryId
	 * @param array $params
	 * @param $update
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	protected static function saveRestrictionByMaxSize($deliveryId, $maxSize, $update)
	{
		$rfields = array(
			"DELIVERY_ID" => $deliveryId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByMaxSize',
			"PARAMS" => array(
				"MAX_SIZE" => $maxSize,
			)
		);

		if($update)
		{
			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $deliveryId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByMaxSize'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}
		else
		{
			$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		return $rres->isSuccess();
	}

	/** @deprecated */
	public static function Set($code, $arData, $siteId = false)
	{
		global $APPLICATION;

		$serviceParams = self::getServiceParams($code, $siteId);
		$id = isset($serviceParams["ID"]) ? $serviceParams["ID"] : false;

		$update = intval($id) > 0;
		$fields = array_intersect_key($arData, Bitrix\Sale\Delivery\Services\Table::getMap());

		if(!$update) //add new
		{
			$fields["CODE"] = $code;

			if(!isset($arData["CLASS_NAME"]))
				$fields["CLASS_NAME"] = '\Bitrix\Sale\Delivery\Services\Automatic';
			else
				$fields["CLASS_NAME"] = $arData["CLASS_NAME"];
		}

		if(isset($arData["PARENT_ID"]))
			$fields["PARENT_ID"] = $arData["PARENT_ID"];
		elseif(!$update)
			$fields["PARENT_ID"] = 0;

		if (!defined('SALE_DH_INITIALIZED'))
			CSaleDeliveryHandler::Initialize();

		$handlers = self::__getRegisteredHandlers();

		if(isset($serviceParams["CONFIG"]))
			$fields["CONFIG"] = $serviceParams["CONFIG"];
		else
			$fields["CONFIG"] = array();

		if (isset($arData["CONFIG"]))
		{
			if (isset($handlers[$code]["DBSETSETTINGS"]) && is_callable($handlers[$code]["DBSETSETTINGS"]))
			{
				if (!$strOldSettings = call_user_func($handlers[$code]["DBSETSETTINGS"], $arData["CONFIG"]))
				{
					$APPLICATION->ThrowException("Can't save delivery services's old settings");
					return  false;
				}
			}
			else
			{
				$strOldSettings = $arData["CONFIG"];
			}

			$strOldSettings = serialize($strOldSettings);
			$fields["CONFIG"]["MAIN"]["OLD_SETTINGS"] = $strOldSettings;
		}

		if(!empty($arData["BASE_CURRENCY"]))
			$fields["CURRENCY"] = $arData["BASE_CURRENCY"];
		elseif(!empty($serviceParams["CURRENCY"]))
			$fields["CURRENCY"] = $serviceParams["CURRENCY"];
		elseif(!empty($handlers[$code]["BASE_CURRENCY"]))
			$fields["CURRENCY"] = $handlers[$code]["BASE_CURRENCY"];
		else
			$fields["CURRENCY"] = COption::GetOptionString('sale', 'default_currency', 'RUB');

		if (isset($arData["SID"]))
			$fields["CONFIG"]["MAIN"]["SID"] = $arData["SID"];
		else
			$fields["CONFIG"]["MAIN"]["SID"] = "";

		if(isset($arData["TAX_RATE"]) && floatval($arData["TAX_RATE"]) > 0)
		{
			$fields["CONFIG"]["MAIN"]["MARGIN_VALUE"] = $arData["TAX_RATE"];
			$fields["CONFIG"]["MAIN"]["MARGIN_TYPE"] = "%";
		}

		elseif(!$update)
			$fields["CONFIG"]["MAIN"]["MARGIN"] = 0;

		if (isset($arData["PROFILE_ID"]))
			$fields["CONFIG"]["MAIN"]["PROFILE_ID"] = $arData["PROFILE_ID"];
		else
			$fields["CONFIG"]["MAIN"]["PROFILE_ID"] = "";

		if (isset($arData["LOGOTIP"]) && is_array($arData["LOGOTIP"]))
		{
			$fields["LOGOTIP"] = $arData["LOGOTIP"];
			$fields["LOGOTIP"]["MODULE_ID"] = "sale";
			CFile::SaveForDB($fields, "LOGOTIP", "sale/delivery/logotip");
		}

		if($update)
			$res = \Bitrix\Sale\Delivery\Services\Table::update($id, $fields);
		else
			$res = \Bitrix\Sale\Delivery\Services\Table::add($fields);

		if(!$res->isSuccess())
		{
			throw new \Bitrix\Main\SystemException(implode("\n", $res->getErrorMessages()));
		}

		if(!$update)
			$id = $res->getId();

		if (is_array($arData["PROFILES"]))
		{
			foreach($arData["PROFILES"] as $profileCode => $profileData)
			{
				self::Set($code.":".$profileCode,
					array(
						"NAME" => strlen($profileData["TITLE"]) > 0 ? $profileData["TITLE"] : $handlers[$code]['PROFILES'][$profileCode]['TITLE'],
						"DESCRIPTION" => isset($profileData["DESCRIPTION"]) ? $profileData["DESCRIPTION"] : '',
						"ACTIVE" => isset($profileData["ACTIVE"]) ?  $profileData["ACTIVE"] : "N",
						"TAX_RATE" => isset($profileData["TAX_RATE"]) ?  $profileData["TAX_RATE"] : 0,
						"PARENT_ID" => isset($profileData["PARENT_ID"]) ?  $profileData["PARENT_ID"] : $id,
						"SORT" => isset($arData["SORT"]) ?  $arData["SORT"] : 100,
						"RESTRICTIONS_WEIGHT" => isset($profileData["RESTRICTIONS_WEIGHT"]) ? $profileData["RESTRICTIONS_WEIGHT"] : false,
						"RESTRICTIONS_SUM" => isset($profileData["RESTRICTIONS_SUM"]) ? $profileData["RESTRICTIONS_SUM"] : false,
						"RESTRICTIONS_DIMENSIONS" => isset($profileData["RESTRICTIONS_DIMENSIONS"]) ? $profileData["RESTRICTIONS_DIMENSIONS"] : false,
						"RESTRICTIONS_MAX_SIZE" => isset($profileData["RESTRICTIONS_MAX_SIZE"]) ? $profileData["RESTRICTIONS_MAX_SIZE"] : 0,
						"RESTRICTIONS_DIMENSIONS_SUM" => isset($profileData["RESTRICTIONS_DIMENSIONS_SUM"]) ? $profileData["RESTRICTIONS_DIMENSIONS_SUM"] : 0,
						"CLASS_NAME" => '\Bitrix\Sale\Delivery\Services\AutomaticProfile',
						"PROFILE_ID" => $profileCode
					),
					strlen($siteId) > 0 ? $siteId : ""
				);
			}
		}

		if(strlen($siteId) > 0)
		{
			if(!self::saveRestrictionBySiteId($id, $siteId, $update))
			{
				$APPLICATION->ThrowException("Can't save delivery restriction by site", "SITE_ID");
				return false;
			}
		}
		elseif($update)
		{
			\Bitrix\Sale\Delivery\Restrictions\Table::deleteByDeliveryIdClassName($id, '\Bitrix\Sale\Delivery\Restrictions\BySite');
		}

		if(is_array($arData["RESTRICTIONS_WEIGHT"]) && (floatval($arData["RESTRICTIONS_WEIGHT"][0]) > 0 || floatval($arData["RESTRICTIONS_WEIGHT"][1]) > 0))
		{
			if(!self::saveRestrictionByWeight($id, $arData["RESTRICTIONS_WEIGHT"], $update))
			{
				$APPLICATION->ThrowException("Can't save delivery restriction by weight", "RESTRICTIONS_WEIGHT");
				return false;
			}
		}
		elseif($update)
		{
			\Bitrix\Sale\Delivery\Restrictions\Table::deleteByDeliveryIdClassName($id, '\Bitrix\Sale\Delivery\Restrictions\ByWeight');
		}

		if(is_array($arData["RESTRICTIONS_SUM"]) && (floatval($arData["RESTRICTIONS_SUM"][0]) > 0 || floatval($arData["RESTRICTIONS_SUM"][1]) > 0))
		{
			if(!self::saveRestrictionByPrice($id, $arData["RESTRICTIONS_SUM"], $fields["CURRENCY"], $update))
			{
				$APPLICATION->ThrowException("Can't save delivery restriction by sum", "RESTRICTIONS_SUM");
				return false;
			}
		}
		elseif($update)
		{
			\Bitrix\Sale\Delivery\Restrictions\Table::deleteByDeliveryIdClassName($id, '\Bitrix\Sale\Delivery\Restrictions\ByPrice');
		}

		if(
		(is_array($arData["RESTRICTIONS_DIMENSIONS"])
			&& (floatval($arData["RESTRICTIONS_DIMENSIONS"][0]) > 0
				|| floatval($arData["RESTRICTIONS_DIMENSIONS"][1]) > 0
				|| floatval($arData["RESTRICTIONS_DIMENSIONS"][3]) > 0
			)
		)
		)
		{
			if(!self::saveRestrictionByDimensions(
				$id,
				array(
					"LENGTH" => count($arData["RESTRICTIONS_DIMENSIONS"][0]) > 0  ? $arData["RESTRICTIONS_DIMENSIONS"][0] : 0,
					"WIDTH" => isset($arData["RESTRICTIONS_DIMENSIONS"][1]) ? $arData["RESTRICTIONS_DIMENSIONS"][1] : 0,
					"HEIGHT" => isset($arData["RESTRICTIONS_DIMENSIONS"][2]) ? $arData["RESTRICTIONS_DIMENSIONS"][2] : 0
				),
				$update
			)
			)
			{
				$APPLICATION->ThrowException("Can't save delivery restriction by dimensions");
				return false;
			}
		}
		elseif($update)
		{
			\Bitrix\Sale\Delivery\Restrictions\Table::deleteByDeliveryIdClassName($id, '\Bitrix\Sale\Delivery\Restrictions\ByDimensions');
		}

		if(floatval($arData["RESTRICTIONS_MAX_SIZE"]) > 0)
		{
			if(!self::saveRestrictionByMaxSize($id, $arData["RESTRICTIONS_MAX_SIZE"], $update))
			{
				$APPLICATION->ThrowException("Can't save delivery restriction by maxx size", "RESTRICTIONS_MAX_SIZE");
				return false;
			}
		}
		elseif($update)
		{
			\Bitrix\Sale\Delivery\Restrictions\Table::deleteByDeliveryIdClassName($id, '\Bitrix\Sale\Delivery\Restrictions\ByMaxSize');
		}

		return $id;
	}

	/** @deprecated */
	public static function Reset($sid)
	{
		$dbRes =  \Bitrix\Sale\Delivery\Services\Table::getList(array(
			"filter" => array(
				"LOGIC" => "OR",
				"=CODE" => $sid,
				"CODE" => $sid.":%"
			),
			"select" => array(
				"ID"
			)
		));

		try
		{
			while($service = $dbRes->fetch())
				\Bitrix\Sale\Delivery\Services\Table::delete($service["ID"]);
		}
		catch(\Bitrix\Main\SystemException $e)
		{
			$GLOBALS["APPLICATION"]->ThrowException($e->getMessage());
			return false;
		}

		return true;
	}

	/** @deprecated */
	public static function ResetAll()
	{
		$serviceRes = \Bitrix\Sale\Delivery\Services\Table::getList(
			array(
				'filter' => array("=CLASS_NAME" => '\Bitrix\Sale\Delivery\Services\Automatic'),
				'select' => array("CODE")
			));

		while($service = $serviceRes->fetch())
			self::Reset($service["CODE"]);

		return;
	}

	/** @deprecated */
	protected static function __executeCalculateEvents($SID, $profile, $arOrder, $arReturn)
	{
		$arEventsList = array(
			"onSaleDeliveryHandlerCalculate",
			"onSaleDeliveryHandlerCalculate_".$SID,
		);

		foreach ($arEventsList as $event)
		{
			foreach(GetModuleEvents("sale", $event, true) as $arEventHandler)
			{
				$arReturnTmp = ExecuteModuleEventEx($arEventHandler, array($SID, $profile, $arOrder, $arReturn));

				if (is_array($arReturnTmp))
					$arReturn = $arReturnTmp;
			}
		}

		return $arReturn;
	}

	public static function execOldEventWithNewParams(Bitrix\Main\Event $params)
	{
		/** @var \Bitrix\Sale\Shipment $shipment*/
		if(!$shipment = $params->getParameter("SHIPMENT"))
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, 'sale');

		/** @var \Bitrix\Sale\Delivery\Services\Base $deliverySrv */
		if(!$deliverySrv = $shipment->getDelivery())
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, 'sale');

		if(get_class($deliverySrv) != 'Bitrix\Sale\Delivery\Services\AutomaticProfile')
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, 'sale');

		if(!$code = $deliverySrv->getCode())
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, 'sale');

		$sidAndProfile = \CSaleDeliveryHelper::getDeliverySIDAndProfile($code);

		/** @var \Bitrix\Sale\Delivery\CalculationResult $result*/
		if(!$result = $params->getParameter("RESULT"))
			throw new \Bitrix\Main\ArgumentNullException("params[RESULT]");

		if(!$collection = $shipment->getCollection())
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, 'sale');

		/** @var \Bitrix\Sale\Order $order */
		if(!$order = $collection->getOrder())
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, 'sale');

		$oldOrder = \Bitrix\Sale\Compatible\OrderCompatibility::convertOrderToArray($order);

		$oldResult = array(
			"VALUE" => $result->getPrice(),
			"TRANSIT" => $result->getPeriodDescription(),
			"TEXT" => $result->isSuccess() ? $result->getDescription() : implode("<br>\n", $result->getErrorMessages()),
			"RESULT" => $result->isSuccess() ? "OK" : "ERROR"
		);

		if($result->isNextStep())
			$oldResult["RESULT"] = "NEXT_STEP";

		if($result->isSuccess() && strlen($result->getDescription()) > 0)
			$oldResult["RESULT"] = "NOTE";

		if(intval($result->getPacksCount()) > 0)
			$oldResult["PACKS_COUNT"] = $result->getPacksCount();

		if($result->isNextStep()  && strlen($result->getTmpData()) > 0)
			$oldResult["TEMP"] = CUtil::JSEscape($result->getTmpData());

		$oldResult = self::__executeCalculateEvents($sidAndProfile["SID"], $sidAndProfile["PROFILE"], $oldOrder, $oldResult);

		$result->setDeliveryPrice($oldResult["VALUE"]);

		if($oldResult["RESULT"] == "ERROR")
			$result->addError(new \Bitrix\Main\Entity\EntityError($oldResult["TEXT"]));
		elseif($oldResult["RESULT"] == "NEXT_STEP")
			$result->setAsNextStep();

		if(isset($oldResult["TRANSIT"])) $result->setPeriodDescription($oldResult["TRANSIT"]);
		if(isset($oldResult["TEXT"])) $result->setDescription($oldResult["TEXT"]);
		if(isset($oldResult["PACKS_COUNT"])) $result->setPacksCount($oldResult["PACKS_COUNT"]);
		if(isset($oldResult["TEMP"])) $result->setTmpData($oldResult["TEMP"]);

		return $result;
	}

	/** deprecated */
	
	/**
	* <p>Вызов полного цикла расчёта. В случае, если обработчик службы доставки осуществляет расчёт за один шаг, метод аналогичен <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_calculate.php">CSaleDeliveryHandler::Calculate()</a>. В противном случае метод автоматически выполнит переход на следующий шаг расчёта. Метод статический.</p>
	*
	*
	* @param string $SID  Строковый идентификатор обработчика.
	*
	* @param string $profile  Идентификатор профиля обработчика.
	*
	* @param array $arOrder  Массив заказа: <ul> <li> <b>WEIGHT</b> - суммарный вес заказа в граммах; </li>
	* <li> <b>PRICE</b> - суммарная стоимость заказа в базовой валюте магазина;
	* </li> <li> <b>LOCATION_FROM</b> - ID местоположения магазина, настраиваемого в
	* настройках модуля "Интернет-магазин"; </li> <li> <b>LOCATION_TO</b> - ID
	* местоположения, указываемого клиентом при оформлении заказа; </li>
	* <li> <b>ITEMS</b> - массив позиций корзины, причем каждая позиция
	* обладает набором свойств. Одно из них - <b>DIMENSIONS</b> - массив,
	* содержащий длину, высоту и ширину. Служба доставки может
	* проверить подходят ли все товары для доставки данной службой
	* (размеры / вес / цена) или сколько коробок необходимо для того,
	* чтобы отправить весь заказ.</li> </ul>
	*
	* @param string $currency  Идентификатор валюты.
	*
	* @param mixed $SITE_ID = false Идентификатор сайта. По умолчанию используется текущий.
	*
	* @return array <p>Возвращается ассоциативный массив следующей структуры:</p> <table
	* class="tnormal" width="100%"><tbody> <tr> <th width="15%">Ключ</th> <th width="85%">Описание</th> </tr> <tr>
	* <td><b>RESULT</b></td> <td>Идентификатор ответа. Возможные значения: <ul>
	* <li>"<b>OK</b>" - стоимость доставки успешно рассчитана; </li> <li>"<b>ERROR</b>" - в
	* процессе расчёта произошла ошибка.</li> </ul> </td> </tr> <tr> <td><b>VALUE</b></td>
	* <td>Значение стоимости доставки в валюте, задаваемой в параметрах
	* метода - currency. (<code>RESULT = 'OK'</code>).</td> </tr> <tr> <td><b>TRANSIT</b></td>
	* <td>Длительность доставки в днях (<code>RESULT = 'OK'</code>). Если обработчик
	* доставки не возвращает длительность, то этот параметр
	* отсутствует.</td> </tr> <tr> <td><b>TEXT</b></td> <td>Текст ошибки (<code>RESULT =
	* 'ERROR'</code>).</td> </tr> </tbody></table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $arOrder = array(
	*   "WEIGHT" =&gt; "10", // вес заказа в граммах
	*   "PRICE" =&gt; "100", // стоимость заказа в базовой валюте магазина
	*   "LOCATION_FROM" =&gt; COption::GetOptionInt('sale', 'location'), // местоположение магазина
	*   "LOCATION_TO" =&gt; 55892, // местоположение доставки
	* );
	* 
	* $currency = CSaleLang::GetLangCurrency(SITE_ID);
	* 
	* $dbHandler = CSaleDeliveryHandler::GetBySID('simple');
	* if ($arHandler = $dbHandler-&gt;Fetch())
	* {
	*   $arProfiles = CSaleDeliveryHandler::GetHandlerCompability($arOrder, $arHandler);
	*   if (is_array($arProfiles) &amp;&amp; count($arProfiles) &gt; 0)
	*   {
	*     $arProfiles = array_keys($arProfiles);
	*     $arReturn = CSaleDeliveryHandler::CalculateFull(
	*       'simple', // идентификатор службы доставки
	*       $arProfiles[0], // идентификатор профиля доставки
	*       $arOrder, // заказ
	*       $currency // валюта, в которой требуется вернуть стоимость
	*     );
	* 
	*     if ($arReturn["RESULT"] == "OK")
	*     {
	*       ShowNote('Стоимость доставки успешно рассчитана!');
	*       echo 'Стоимость доставки: '.CurrencyFormat($arReturn["VALUE"], $currency).'&lt;br /&gt;';
	*       if (is_set($arReturn['TRANSIT']) &amp;&amp; $arReturn['TRANSIT'] &gt; 0)
	*       {
	*         echo 'Длительность доставки: '.$arReturn['TRANSIT'].' дней.&lt;br /&gt;';
	*       }
	*     }
	*     else
	*     {
	*       ShowError('Не удалось рассчитать стоимость доставки! '.$arResult['ERROR']);
	*     }
	*   }
	*   else
	*   {
	*     ShowError('Невозможно доставить заказ!');
	*   }
	* }
	* else
	* {
	*   ShowError('Обработчик не найден!');
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_calculatefull.php
	* @author Bitrix
	*/
	public static function CalculateFull($SID, $profile, $arOrder, $currency, $SITE_ID = false)
	{
		$bFinish = false;
		$STEP = 0;
		$TMP = false;

		while (!$bFinish)
		{
			$arResult = CSaleDeliveryHandler::Calculate(++$STEP, $SID, $profile, $arOrder, $currency, $TMP, $SITE_ID);

			if ($arResult["RESULT"] == "NEXT_STEP" && strlen($arResult["TEMP"]) > 0)
				$TMP = $arResult["TEMP"];

			$bFinish = $arResult["RESULT"] == "OK" || $arResult["RESULT"] == "ERROR";
		}

		return $arResult;
	}

	/**
	 * @param $STEP
	 * @param $SID
	 * @param $profile
	 * @param $arOrder
	 * @param $currency
	 * @param bool $TMP
	 * @param bool $SITE_ID
	 * @return array
	 * @deprecated
	 */
	
	/**
	* <p>Вызов одного шага расчёта стоимости доставки. Метод статический.</p>
	*
	*
	* @param int $STEP  Текущий шаг расчёта. </ht
	*
	* @param string $SID  Строковый идентификатор обработчика.
	*
	* @param string $profile  Идентификатор профиля обработчика.
	*
	* @param array $arOrder  Массив заказа: <ul> <li> <b>WEIGHT</b> - суммарный вес заказа в граммах; </li>
	* <li> <b>PRICE</b> - суммарная стоимость заказа в базовой валюте магазина;
	* </li> <li> <b>LOCATION_FROM</b> - ID местоположения магазина, настраиваемого в
	* настройках модуля "Интернет-магазин";</li> <li> <b>LOCATION_TO</b> - ID
	* местоположения, указываемого клиентом при оформлении заказа;</li>
	* <li> <b>ITEMS</b> - массив позиций корзины, причем каждая позиция
	* обладает набором свойств. Одно из них - <b>DIMENSIONS</b> - массив,
	* содержащий длину, высоту и ширину. Служба доставки может
	* проверить подходят ли все товары для доставки данной службой
	* (размеры / вес / цена) или сколько коробок необходимо для того,
	* чтобы отправить весь заказ.</li> </ul>
	*
	* @param string $currency  Идентификатор валюты.
	*
	* @param mixed $TMP = false Временные данные с предыдущего шага.
	*
	* @param mixed $SITE_ID = false Идентификатор сайта. По умолчанию используется текущий.
	*
	* @return array <p>Возвращается ассоциативный массив следующей структуры:</p> <table
	* class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th width="85%">Описание</th> </tr> <tr>
	* <td><b>RESULT</b></td> <td>Идентификатор ответа. Возможные значения: <ul>
	* <li>"<b>OK</b>" - стоимость доставки успешно рассчитана; </li> <li>"<b>ERROR</b>" - в
	* процессе расчёта произошла ошибка; </li> <li>"<b>NEXT_STEP</b>" - необходимо
	* перейти на следующий шаг для продолжения расчёта. </li> </ul> </td> </tr> <tr>
	* <td><b>VALUE</b></td> <td>Значение стоимости доставки в валюте, задаваемой в
	* параметрах метода - currency. (<code>RESULT = 'OK'</code>)</td> </tr> <tr> <td><b>TRANSIT</b></td>
	* <td>Длительность доставки в днях (<code>RESULT = 'OK'</code>). Если обработчик
	* доставки не возвращает длительность, то этот параметр
	* отсутствует.</td> </tr> <tr> <td><b>TEXT</b></td> <td>Текст ошибки или текст,
	* сопровождающий переход на следующий шаг (<code>RESULT =
	* {'ERROR'|'NEXT_STEP'}</code>).</td> </tr> <tr> <td><b>TEMP</b></td> <td>Строка, содержащая
	* промежуточные данные, которые нужно передать следующему шагу
	* (<code>RESULT = 'NEXT_STEP'</code>).</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledeliveryhandler/csaledeliveryh_calculate.php
	* @author Bitrix
	* @deprecated
	*/
	public static function Calculate($STEP, $SID, $profile, $arOrder, $currency, $TMP = false, $SITE_ID = false)
	{
		global $APPLICATION;

		if (!defined('SALE_DH_INITIALIZED'))
			CSaleDeliveryHandler::Initialize();

		if (!$SITE_ID) $SITE_ID = SITE_ID;

		$rsDeliveryHandler = CSaleDeliveryHandler::GetBySID($SID, $SITE_ID);
		if (!$arHandler = $rsDeliveryHandler->Fetch())
		{
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage("SALE_DH_ERROR_HANDLER_NOT_INSTALLED")
			);
		}

		if (is_callable($arHandler["CALCULATOR"]))
		{
			$arConfig = $arHandler["CONFIG"]["CONFIG"];

			$arOrder["PRICE"] = CCurrencyRates::ConvertCurrency(
				$arOrder["PRICE"],
				$currency,
				$arHandler["BASE_CURRENCY"]
			);

			if ($res = call_user_func($arHandler["CALCULATOR"], $profile, $arConfig, $arOrder, $STEP, $TMP))
			{
				if (is_array($res))
					$arReturn = $res;
				elseif (is_numeric($res))
					$arReturn = array(
						"RESULT" => "OK",
						"VALUE" => doubleval($res)
					);
			}
			else
			{
				if ($ex = $APPLICATION->GetException())
					return array(
						"RESULT" => "ERROR",
						"TEXT" => $ex->GetString(),
					);
				else
					return array(
						"RESULT" => "OK",
						"VALUE" => 0
					);
			}

			if (
				is_array($arReturn)
				&&
				$arReturn["RESULT"] == "OK"
				&&
				$currency != $arHandler["BASE_CURRENCY"]
				&&
				CModule::IncludeModule('currency')
			)
			{
				$arReturn["VALUE"] = CCurrencyRates::ConvertCurrency(
					$arReturn["VALUE"],
					$arHandler["BASE_CURRENCY"],
					$currency
				);
			}

			$arReturn["VALUE"] *= 1 + ($arHandler["TAX_RATE"]/100);

			if(isset($arHandler['PROFILES'][$profile]['TAX_RATE']))
				$arReturn["VALUE"] *= 1 + (floatval($arHandler['PROFILES'][$profile]['TAX_RATE'])/100);

			$arReturn = CSaleDeliveryHandler::__executeCalculateEvents($SID, $profile, $arOrder, $arReturn);

			return $arReturn;
		}
		else
		{
			return array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage("SALE_DH_ERROR_WRONG_HANDLER_FILE")
			);
		}
	}

	/**
	 * @param $arOrderDimensions
	 * @param $arRestrictDimensions
	 * @return bool
	 * @deprecated
	 */
	public static function checkDimensions($arOrderDimensions, $arRestrictDimensions)
	{
		$dimCount = 3;
		if(
			!is_array($arOrderDimensions)
			||
			!is_array($arRestrictDimensions)
			||
			empty($arOrderDimensions)
			||
			empty($arRestrictDimensions)
			||
			count($arOrderDimensions) != $dimCount
			||
			count($arRestrictDimensions) != $dimCount
		)
			return true;

		$result = true;

		rsort($arOrderDimensions, SORT_NUMERIC);
		rsort($arRestrictDimensions, SORT_NUMERIC);

		for ($i=0; $i < $dimCount; $i++)
		{
			if(
				floatval($arRestrictDimensions[$i]) <= 0
				||
				$arOrderDimensions[$i] <=0
			)
			{
				break;
			}

			if($arOrderDimensions[$i] > $arRestrictDimensions[$i])
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * @param $arItems
	 * @param $maxDimensionSum
	 * @return bool
	 * @deprecated
	 */
	public static function checkDimensionsSum($arItems, $maxDimensionSum)
	{
		$result = true;
		$maxDimensionSum = floatval($maxDimensionSum);

		if(is_array($arItems) && $maxDimensionSum > 0)
		{
			foreach ($arItems as $arItem)
			{
				if(!self::isDimensionsExist($arItem))
					continue;

				$itemDimSumm = floatval($arItem["WIDTH"])+floatval($arItem["HEIGHT"])+floatval($arItem["LENGTH"]);

				if($itemDimSumm > $maxDimensionSum)
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $arItems
	 * @param $maxSize
	 * @return bool
	 * @deprecated
	 */
	public static function checkMaxSize($arItems, $maxSize)
	{
		$result = true;
		$maxSize = floatval($maxSize);

		if(is_array($arItems) && $maxSize > 0)
		{

			foreach ($arItems as $arItem)
			{
				if(!self::isDimensionsExist($arItem))
					continue;

				if(
					floatval($arItem["WIDTH"]) > $maxSize
					||
					floatval($arItem["HEIGHT"]) > $maxSize
					||
					floatval($arItem["LENGTH"]) > $maxSize
				)
				{
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $arItem
	 * @return bool
	 * @deprecated
	 */
	private static function isDimensionsExist($arItem)
	{
		return (
			isset($arItem["WIDTH"]) && floatval($arItem["WIDTH"]) > 0
			&&
			isset($arItem["HEIGHT"]) && floatval($arItem["HEIGHT"]) > 0
			&&
			isset($arItem["LENGTH"]) && floatval($arItem["LENGTH"]) > 0
		);
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public static function getActionsNames()
	{
		return array(
			"REQUEST_SELF" => GetMessage("SALE_DH_ACTION_REQUEST_SELF"),
			"REQUEST_TAKE" => GetMessage("SALE_DH_ACTION_REQUEST_TAKE")
		);
	}

	/**
	 * @return \Bitrix\Sale\Result|bool
	 * @throws Exception
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function convertToNew($renameTable = false)
	{
		$result = new \Bitrix\Sale\Result();
		$con = \Bitrix\Main\Application::getConnection();

		if(!$con->isTableExists("b_sale_delivery_handler"))
			return true;

		$sqlHelper = $con->getSqlHelper();
		$deliveryRes = $con->query('SELECT * FROM b_sale_delivery_handler WHERE CONVERTED != \'Y\'');
		$tablesToUpdate = array(
			'b_sale_order',
			'b_sale_order_history',
		);

		\CSaleDeliveryHandler::Initialize();
		$handlers = \CSaleDeliveryHandler::__getRegisteredHandlers();

		while($delivery = $deliveryRes->fetch())
		{
			if(strlen($delivery["HID"]) <= 0)
			{
				$result->addError( new \Bitrix\Main\Entity\EntityError("Can't delivery HID. ID: \"".$delivery["ID"]."\""));
				continue;
			}

			if(!isset($handlers[$delivery["HID"]]))
			{
				$result->addError( new \Bitrix\Main\Entity\EntityError("Can't find delivery handler in registered HID: \"".$delivery["HID"]."\""));
				continue;
			}

			if(strlen($delivery["PROFILES"]) > 0) //get from base
				$delivery["PROFILES"] = unserialize($delivery["PROFILES"]);
			else //or default.
				$delivery["PROFILES"] = $handlers[$delivery["HID"]]["PROFILES"];

			// Something strange it probably not used
			if($delivery["PROFILES"] == false || !is_array($delivery["PROFILES"]) || empty($delivery["PROFILES"] ))
			{
				$result->addError( new \Bitrix\Main\Entity\EntityError("Can't receive info about profiles. Delivery HID: \"".$delivery["HID"]."\""));
				continue;
			}

			//Set profiles activity
			foreach($delivery["PROFILES"] as $id => $params)
				if(!isset($delivery["PROFILES"][$id]["ACTIVE"]) || $delivery["ACTIVE"] == "N")
					$delivery["PROFILES"][$id]["ACTIVE"] = $delivery["ACTIVE"];

			unset($delivery["ID"]);
			$delivery["CONFIG"] = array();

			if (strlen($delivery["SETTINGS"]) > 0)
			{
				if(isset($handlers[$delivery["HID"]]["DBGETSETTINGS"]) && is_callable($handlers[$delivery["HID"]]["DBGETSETTINGS"]))
					$delivery["CONFIG"] = call_user_func($handlers[$delivery["HID"]]["DBGETSETTINGS"], $delivery["SETTINGS"]);
				else
					$delivery["CONFIG"] = $delivery["SETTINGS"];
			}
			elseif(isset($handlers[$delivery["HID"]]["GETCONFIG"]) && is_callable($handlers[$delivery["HID"]]["GETCONFIG"]))
			{
				$config = call_user_func(
					$handlers[$delivery["HID"]]["GETCONFIG"],
					strlen($delivery["LID"]) > 0 ? $delivery["LID"] : false
				);

				foreach($config["CONFIG"] as $key => $arConfig)
				{
					if(!empty($arConfig["DEFAULT"]))
					{
						$delivery["CONFIG"][$key] = $arConfig["DEFAULT"];
					}
				}
			}

			$delivery["SID"] = $handlers[$delivery["HID"]]["SID"];

			$id = \CSaleDeliveryHandler::Set(
				$delivery["HID"],
				$delivery,
				strlen($delivery["LID"]) > 0 ? $delivery["LID"] : false
			);

			if(intval($id) <= 0)
			{
				$result->addError(
					new \Bitrix\Main\Entity\EntityError(
						"Can't convert delivery handler with hid: ".
						$delivery["HID"].
						(strlen($delivery["LID"]) > 0 ? " for site: ".$delivery["LID"] : "")
					)
				);

				continue;
			}

			$con->queryExecute("UPDATE b_sale_delivery_handler SET CONVERTED='Y' WHERE HID LIKE '".$sqlHelper->forSql($delivery["HID"])."'");
			$ids = array($id);

			foreach($delivery["PROFILES"] as $profileName => $profileData)
			{
				$fullSid = $delivery["HID"].":".$profileName;
				$profileId = \Bitrix\Sale\Delivery\Services\Table::getIdByCode($fullSid);
				$ids[] = $profileId;

				if(intval($profileId) > 0)
				{
					foreach($tablesToUpdate as $table)
						$con->queryExecute("UPDATE ".$table." SET DELIVERY_ID=".$sqlHelper->forSql($profileId)." WHERE DELIVERY_ID = '".$sqlHelper->forSql($fullSid)."'");

					$con->queryExecute("UPDATE b_sale_delivery2paysystem SET DELIVERY_ID=".$sqlHelper->forSql($profileId).", DELIVERY_PROFILE_ID='' WHERE DELIVERY_ID = '".$sqlHelper->forSql($delivery["HID"])."' AND DELIVERY_PROFILE_ID='".$profileName."'");
				}
				else
				{
					$result->addError( new \Bitrix\Main\Entity\EntityError("Cant determine id for profile code: ".$fullSid));
				}
			}

			$con->queryExecute("UPDATE b_sale_delivery2paysystem SET DELIVERY_ID=".$sqlHelper->forSql($id).", DELIVERY_PROFILE_ID='' WHERE DELIVERY_ID = '".$sqlHelper->forSql($delivery["HID"])."' AND (DELIVERY_PROFILE_ID='' OR DELIVERY_PROFILE_ID IS NULL)");

			$d2pRes = \Bitrix\Sale\Internals\DeliveryPaySystemTable::getList(array(
				'filter' => array(
					'DELIVERY_ID' => $ids
				),
				'select' => array("DELIVERY_ID"),
				'group' => array("DELIVERY_ID")
			));

			while($d2p = $d2pRes->fetch())
			{
				$res = \Bitrix\Sale\Delivery\Restrictions\Table::add(array(
					"DELIVERY_ID" => $d2p["DELIVERY_ID"],
					"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPaySystem',
					"SORT" => 100
				));

				if(!$res->isSuccess())
					$result->addErrors($res->getErrors());
			}
		}

		if($renameTable && $result->isSuccess())
			$con->renameTable('b_sale_delivery_handler','b_sale_delivery_handler_old');

		return $result;
	}

	public static function convertToNewAgent($renameTable = false)
	{
		self::convertToNew($renameTable);
		return "";
	}

	public static function convertConfigHandlerToSidAgent()
	{
		\Bitrix\Sale\Delivery\Services\Manager::getHandlersClassNames();
		$initedHandlersH = \Bitrix\Sale\Delivery\Services\Automatic::getRegisteredHandlers("HANDLER");
		$initedHandlersS = \Bitrix\Sale\Delivery\Services\Automatic::getRegisteredHandlers("SID");
		$filter = array('=CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Automatic');

		$res = Bitrix\Sale\Delivery\Services\Table::getList(array(
			'filter' => $filter,
			'select' => array("ID", "CODE", "CONFIG")
		));

		while($params = $res->fetch())
		{
			if(!empty($params["CONFIG"]["MAIN"]["SID"]))
				continue;

			$config = $params["CONFIG"];

			if(!empty($initedHandlersH[$config["MAIN"]["HANDLER"]]["SID"]))
				$config["MAIN"]["SID"] = $initedHandlersH[$config["MAIN"]["HANDLER"]]["SID"];
			elseif(!empty($params["CODE"]) && !empty($initedHandlersS[$params["CODE"]]))
				$config["MAIN"]["SID"] = $params["CODE"];
			else
				$config["MAIN"]["SID"] = "";

			unset($config["MAIN"]["HANDLER"]);
			Bitrix\Sale\Delivery\Services\Table::update($params["ID"], array("CONFIG" => $config));
		}

		return "";
	}
}
?>