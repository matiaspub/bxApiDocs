<?
use \Bitrix\Sale\Internals\PaySystemActionTable;

IncludeModuleLangFile(__FILE__);

/** @deprecated */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/index.php
 * @author Bitrix
 * @deprecated
 */
class CAllSalePaySystem
{
	static function DoProcessOrder(&$arOrder, $paySystemId, &$arErrors)
	{
		if (intval($paySystemId) > 0)
		{
			$arPaySystem = array();

			$dbPaySystem = CSalePaySystem::GetList(
				array("SORT" => "ASC", "PSA_NAME" => "ASC"),
				array(
					"ACTIVE" => "Y",
					"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
					"PSA_HAVE_PAYMENT" => "Y"
				)
			);

			while ($arPaySystem = $dbPaySystem->Fetch())
			{
				if ($arPaySystem["ID"] == $paySystemId)
				{
					$arOrder["PAY_SYSTEM_ID"] = $paySystemId;

					$arOrder["PAY_SYSTEM_PRICE"] = CSalePaySystemsHelper::getPSPrice(
						$arPaySystem,
						$arOrder["ORDER_PRICE"],
						$arOrder["PRICE_DELIVERY"],
						$arOrder["DELIVERY_LOCATION"]
					);
					break;
				}
			}

			if (empty($arPaySystem))
			{
				$arErrors[] = array("CODE" => "CALCULATE", "TEXT" => GetMessage('SKGPS_PS_NOT_FOUND'));
			}
		}
	}

	public static function DoLoadPaySystems($personType, $deliveryId = 0, $arDeliveryMap = null)
	{
		$arResult = array();

		$arFilter = array(
			"ACTIVE" => "Y",
			"PERSON_TYPE_ID" => $personType,
			"PSA_HAVE_PAYMENT" => "Y"
		);

		// $arDeliveryMap = array(array($deliveryId => 8), array($deliveryId => array(34, 22)), ...)
		if (is_array($arDeliveryMap) && (count($arDeliveryMap) > 0))
		{
			foreach ($arDeliveryMap as $val)
			{
				if (is_array($val[$deliveryId]))
				{
					foreach ($val[$deliveryId] as $v)
						$arFilter["ID"][] = $v;
				}
				elseif (IntVal($val[$deliveryId]) > 0)
					$arFilter["ID"][] = $val[$deliveryId];
			}
		}
		$dbPaySystem = CSalePaySystem::GetList(
			array("SORT" => "ASC", "PSA_NAME" => "ASC"),
			$arFilter
		);
		while ($arPaySystem = $dbPaySystem->GetNext())
			$arResult[$arPaySystem["ID"]] = $arPaySystem;

		return $arResult;
	}

	
	/**
	* <p>Метод возвращает параметры платежной системы с кодом ID. Если установлен параметр PERSON_TYPE_ID, то возвращаются так же параметры соответствующего обработчика платежной системы. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код платежной системы.
	*
	* @param int $PERSON_TYPE_ID = 0 Код типа плательщика. Если установлен, то дополнительно
	* возвращаются параметры соответствующего обработчика платежной
	* системы. Иначе - только параметры самой платежной системы.
	*
	* @return array <p>Возвращается ассоциативный массив параметров с ключами:</p><table
	* class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>     <th>Описание</th>   </tr> <tr> <td>ID</td> 
	*    <td>Код платежной системы.</td> </tr> <tr> <td>LID</td>     <td>Сайт, на котором
	* работает эта система.</td> </tr> <tr> <td>CURRENCY</td>     <td>Валюта, с которой
	* работает эта система.</td> </tr> <tr> <td>NAME</td>     <td>Название платежной
	* системы.</td> </tr> <tr> <td>ACTIVE</td>     <td>Флаг (Y/N) активности системы.</td> </tr>
	* <tr> <td>SORT</td>     <td>Индекс сортировки.</td> </tr> <tr> <td>DESCRIPTION</td>    
	* <td>Описание платежной системы.</td> </tr> <tr> <td>PSA_ID</td>     <td>Код
	* обработчика платежной системы (возвращается, если в метод
	* передается тип плательщика) </td>   </tr> <tr> <td>PSA_NAME</td>     <td>Название
	* обработчика (возвращается, если в метод передается тип
	* плательщика)</td>   </tr> <tr> <td>PSA_ACTION_FILE</td>     <td>Скрипт обработчика
	* (возвращается, если в метод передается тип плательщика)</td>   </tr> <tr>
	* <td>PSA_RESULT_FILE</td>     <td>Скрипт запроса результатов (возвращается, если
	* в метод передается тип плательщика)</td>   </tr> <tr> <td>PSA_NEW_WINDOW</td>    
	* <td>Флаг (Y/N) открывать ли скрипт обработчика в новом окне
	* (возвращается, если в метод передается тип плательщика)</td>   </tr>
	* </table><p>  </p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // Выведем название обработчика платежной системы с кодом $PAY_SYSTEM_ID
	* // для типа плательщика с кодом $PERSON_TYPE
	* if ($arPaySys = CSalePaySystem::GetByID($PAY_SYSTEM_ID, $PERSON_TYPE))
	* {
	*    echo $arPaySys["PSA_NAME"];
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__getbyid.c3560000.php
	* @author Bitrix
	*/
	public static function GetByID($id, $personTypeId = 0)
	{
		$id = (int)$id;
		$personTypeId = (int)$personTypeId;

		if ($personTypeId > 0)
		{
			$select = array_merge(array('ID', 'NAME', 'DESCRIPTION', 'ACTIVE', 'SORT'), self::getAliases());

			$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getList(array(
				'select' => $select,
				'filter' => array('ID' => $id)
			));
		}
		else
		{
			$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getById($id);
		}

		if ($result = $dbRes->fetch())
		{
			$map = CSalePaySystemAction::getOldToNewHandlersMap();
			$key = array_search($result['ACTION_FILE'], $map);

			if ($key !== false)
				$result['ACTION_FILE'] = $key;

			return $result;
		}

		return false;
	}

	protected static function getAliases()
	{
		$aliases = array(
			"PSA_ID" => 'ID',
			"PSA_ACTION_FILE" => 'ACTION_FILE',
			"PSA_RESULT_FILE" => 'RESULT_FILE',
			"PSA_NEW_WINDOW" => 'NEW_WINDOW',
			"PSA_PERSON_TYPE_ID" => 'PERSON_TYPE_ID',
			"PSA_PARAMS" => 'PARAMS',
			"PSA_TARIF" => 'TARIF',
			"PSA_HAVE_PAYMENT" => 'HAVE_PAYMENT',
			"PSA_HAVE_ACTION" => 'HAVE_ACTION',
			"PSA_HAVE_RESULT" => 'HAVE_RESULT',
			"PSA_HAVE_PREPAY" => 'HAVE_PREPAY',
			"PSA_HAVE_RESULT_RECEIVE" => 'HAVE_RESULT_RECEIVE',
			"PSA_ENCODING" => 'ENCODING',
			"PSA_LOGOTIP" => 'LOGOTIP'
		);
		return $aliases;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB, $USER;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"])<=0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPS_EMPTY_NAME"), "ERROR_NO_NAME");
			return false;
		}

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"] = "N";
		if (is_set($arFields, "SORT") && IntVal($arFields["SORT"])<=0)
			$arFields["SORT"] = 100;

		return True;
	}

	
	/**
	* <p>Метод обновляет параметры платежной системы с кодом ID в соответствии со значениями из массива arFields. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код платежной системы.
	*
	* @param array $arFields  Ассоциативный массив новых параметров платежной системы, в
	* котором ключами являются названия параметров, а значениями -
	* соответствующие значения.<br>  	  Допустимые ключи: 		<ul> <li> <b>LID</b> -
	* сайт платежной системы;</li>  			<li> <b>CURRENCY</b> - валюта платежной
	* системы;</li>  			<li> <b>NAME</b> - название платежной системы;</li>  			<li>
	* <b>ACTIVE</b> - флаг (Y/N) активности 				платежной системы;</li>  			<li> <b>SORT</b> -
	* индекс сортировки;</li>  			<li> <b>DESCRIPTION</b> - описание.</li>  		</ul>
	*
	* @return int <p>Возвращается код измененной записи или <i>false</i> в случае
	* ошибки.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__update.cba446b8.php
	* @author Bitrix
	*/
	public static function Update($id, $arFields)
	{
		if (isset($arFields['LID']))
			unset($arFields['LID']);

		if (isset($arFields['CURRENCY']))
			unset($arFields['CURRENCY']);

		$id = (int)$id;

		if (!CSalePaySystem::CheckFields("UPDATE", $arFields))
			return false;

		return CSalePaySystemAction::Update($id, $arFields);
	}

	
	/**
	* <p>Метод удаляет платежную систему с кодом ID. Если к платежной системе с кодом ID привязаны заказы, то эта платежная система удалена не будет. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код платежной системы.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* CSalePaySystem::Delete(25);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__delete.c8c60e70.php
	* @author Bitrix
	*/
	public static function Delete($id)
	{
		$id = (int)$id;

		$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getById($id);
		if (!$dbRes->fetch())
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPS_ORDERS_TO_PAYSYSTEM"), "ERROR_ORDERS_TO_PAYSYSTEM");
			return false;
		}

		$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::delete($id);

		return $dbRes->isSuccess();
	}

	public static function getNewIdsFromOld($ids, $personTypeId = null)
	{
		$dbRes = PaySystemActionTable::getList(array(
			'select' => array('ID'),
			'filter' => array('PAY_SYSTEM_ID' => $ids)
		));

		$data = array();
		while ($ps = $dbRes->fetch())
		{
			if (!is_null($personTypeId))
			{
				$dbRestriction = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
					'filter' => array(
						'SERVICE_ID' => $ps['ID'],
						'SERVICE_TYPE' => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
						'=CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType'
					)
				));

				while ($restriction = $dbRestriction->fetch())
				{
					if (!in_array($personTypeId, $restriction['PARAMS']['PERSON_TYPE_ID']))
						continue(2);
				}
			}

			$data[] = $ps['ID'];
		}

		return $data;
	}

	public static function getPaySystemPersonTypeIds($paySystemId)
	{
		$data = array();

		$dbRestriction = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
			'filter' => array(
				'SERVICE_ID' => $paySystemId,
				'SERVICE_TYPE' => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
				'=CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType'
			)
		));
		while ($restriction = $dbRestriction->fetch())
			$data = array_merge($data, $restriction['PARAMS']['PERSON_TYPE_ID']);

		return $data;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из платежных систем в соответствии со своими параметрами. Нестатический метод.</p>
	*
	*
	* @param array $arOrder = array(("SORT"=>"ASC" Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: 		         <pre class="syntax">array(<br>"название_поля1"
	* =&gt; "направление_сортировки1",<br>"название_поля2" =&gt;
	* "направление_сортировки2",<br>. . .<br>)</pre>        		В качестве
	* "название_поля<i>N</i>" может стоять любое поле 		платежных систем, а в
	* качестве "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>"
	* (по возрастанию) и "<i>DESC</i>" (по убыванию).         <br><br>        		Если массив
	* сортировки имеет несколько элементов, то 		результирующий набор
	* сортируется последовательно по каждому элементу (т.е. сначала
	* сортируется по первому элементу, потом результат сортируется по
	* второму и т.д.). 
	*
	* @param mixed $NAME  Массив, в соответствии с которым фильтруются 		записи платежных
	* систем. Массив имеет вид: 		         <pre
	* class="syntax">array(<br>"[модификатор1][оператор1]название_поля1" =&gt;
	* "значение1",<br>"[модификатор2][оператор2]название_поля2" =&gt;
	* "значение2",<br>. . .<br>)</pre>        Удовлетворяющие фильтру записи
	* возвращаются в результате, а записи, которые не удовлетворяют
	* условиям фильтра, отбрасываются.         <br><br>        	Допустимыми
	* являются следующие модификаторы: 		         <ul> <li> <b> 	!</b> - отрицание;</li>
	*          			           <li> <b> 	+</b> - значения null, 0 и пустая строка так же
	* удовлетворяют условиям фильтра.</li>          		</ul>        	Допустимыми
	* являются следующие операторы: 	         <ul> <li> <b>&gt;=</b> - значение поля
	* больше или равно передаваемой в фильтр величины;</li>          			          
	* <li> <b>&gt;</b> - значение поля строго больше передаваемой в фильтр
	* величины;</li>          			           <li> <b>&lt;=</b> - значение поля меньше или
	* равно передаваемой в фильтр величины;</li>          			           <li> <b>&lt;</b> -
	* значение поля строго меньше передаваемой в фильтр величины;</li>     
	*     			           <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр разделенном запятой списке значений;</li>          			           <li>
	* <b>~</b> - значение поля проверяется на соответствие передаваемому в
	* фильтр шаблону;</li>          			           <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li>          	</ul>        В качестве
	* "название_поляX" может стоять любое поле 		заказов.         <br><br>       
	* 	Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param NAM $ASC  Массив полей, по которым группируются записи 		платежных систем.
	* Массив имеет вид: 		         <pre class="syntax">array("название_поля1",<br>     
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre>        	В качестве
	* "название_поля<i>N</i>" может стоять любое поле 		платежных систем. В
	* качестве группирующей функции могут стоять: 		         <ul> <li> 	<b> 	COUNT</b> -
	* подсчет количества;</li>          			           <li> <b>AVG</b> - вычисление среднего
	* значения;</li>          			           <li> <b>MIN</b> - вычисление минимального
	* значения;</li>          			           <li> 	<b> 	MAX</b> - вычисление максимального
	* значения;</li>          			           <li> <b>SUM</b> - вычисление суммы.</li>          		</ul>
	* <br>        		Значение по умолчанию - <i>false</i> - означает, что результат
	* группироваться не будет.
	*
	* @param array $arFilter = array() Массив параметров выборки. Может содержать следующие ключи: 		       
	*  <ul> <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li>          			           <li> 	любой
	* ключ, принимаемый методом <b> CDBResult::NavQuery</b> 				в качестве третьего
	* параметра.</li>          		</ul>        Значение по умолчанию - <i>false</i> -
	* означает, что параметров выборки нет.
	*
	* @param array $arGroupBy = false Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение 		"*", то будут возвращены все доступные
	* поля.         <br><br>        		Значение по умолчанию - пустой массив 		array() -
	* означает, что будут возвращены все поля основной таблицы запроса.
	*
	* @param array $arNavStartParams = false 
	*
	* @param array $arSelectFields = array() Код платежной системы.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров платежных систем с
	* ключами:</p><table width="100%" class="tnormal"><tbody> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код платежной системы.</td> </tr> <tr>
	* <td>NAME</td> <td>Название платежной системы.</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N)
	* активности системы.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr>
	* <tr> <td>DESCRIPTION</td> <td>Описание платежной системы.</td> </tr> <tr> <td>PSA_ID</td>
	* <td>Код обработчика платежной системы (возвращается, если в метод
	* передается тип плательщика) </td> </tr> <tr> <td>PSA_NAME</td> <td>Название
	* обработчика (возвращается, если в метод передается тип
	* плательщика)</td> </tr> <tr> <td>PSA_ACTION_FILE</td> <td>Скрипт обработчика
	* (возвращается, если в метод передается тип плательщика)</td> </tr> <tr>
	* <td>PSA_RESULT_FILE</td> <td>Скрипт запроса результатов (возвращается, если в
	* метод передается тип плательщика)</td> </tr> <tr> <td>PSA_NEW_WINDOW</td> <td>Флаг
	* (Y/N) открывать ли скрипт обработчика в новом окне (возвращается,
	* если в метод передается тип плательщика)</td> 	</tr> <tr> <td>PSA_PERSON_TYPE_ID</td>
	* <td>Код типа плательщика.</td> </tr> <tr> <td>PSA_PARAMS</td> <td>Параметры вызова
	* обработчика.</td> </tr> <tr> <td>PSA_HAVE_PAYMENT</td> <td>Есть вариант обработчика
	* для работы после оформления заказа.</td> </tr> <tr> <td>PSA_HAVE_ACTION</td> <td>Есть
	* вариант обработчика для мгновенного списания денег.</td> </tr> <tr>
	* <td>PSA_HAVE_RESULT</td> <td>Есть скрипт запроса результатов.</td> </tr> <tr>
	* <td>PSA_HAVE_PREPAY</td> <td>Есть вариант обработчика для работы во время
	* оформления заказа.</td> </tr> </tbody></table><p>Если в качестве параметра
	* arGroupBy передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>// Выведем все активные платежные системы для текущего сайта, для типа плательщика с кодом 2, работающие с валютой RUR<br>$db_ptype = CSalePaySystem::GetList($arOrder = Array("SORT"=&gt;"ASC", "PSA_NAME"=&gt;"ASC"), Array("LID"=&gt;SITE_ID, "CURRENCY"=&gt;"RUB", "ACTIVE"=&gt;"Y", "PERSON_TYPE_ID"=&gt;2));<br>$bFirst = True;<br>while ($ptype = $db_ptype-&gt;Fetch())<br>{<br>   ?&gt;&lt;input type="radio" name="PAY_SYSTEM_ID" value="&lt;?echo $ptype["ID"] ?&gt;"&lt;?if ($bFirst) echo " checked";?&gt;&gt;&lt;b&gt;&lt;?echo $ptype["PSA_NAME"] ?&gt;&lt;/b&gt;&lt;br&gt;&lt;?<br>   $bFirst = <i>false</i>;<br>   if (strlen($ptype["DESCRIPTION"])&gt;0)<br>      echo $ptype["DESCRIPTION"]."&lt;br&gt;";<br>   ?&gt;&lt;hr size="1" width="90%"&gt;&lt;?<br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__getlist.b3a25180.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array("SORT" => "ASC", "NAME" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		if (array_key_exists("PSA_PERSON_TYPE_ID", $arFilter))
		{
			$arFilter['PERSON_TYPE_ID'] = $arFilter['PSA_PERSON_TYPE_ID'];
			unset($arFilter["PSA_PERSON_TYPE_ID"]);
		}

		$salePaySystemFields = array('ID', 'NAME', 'ACTIVE', 'SORT', 'DESCRIPTION');
		$ignoredFields = array('LID', 'CURRENCY', 'PERSON_TYPE_ID');

		if (!$arSelectFields)
		{
			$select = array('ID', 'NAME', 'ACTIVE', 'SORT', 'DESCRIPTION');
		}
		else
		{
			$select = array();
			foreach ($arSelectFields as $key => $field)
			{
				if (in_array($field, $ignoredFields))
					continue;

				$select[$key] = self::getAlias($field);
			}
		}

		$filter = array();
		foreach ($arFilter as $key => $value)
		{
			if (in_array($key, $ignoredFields))
				continue;

			$filter[self::getAlias($key)] = $value;
		}

		if (isset($arFilter['PERSON_TYPE_ID']))
			$select = array_merge($select, array('PSA_ID' => 'ID', 'PSA_NAME', 'ACTION_FILE', 'RESULT_FILE', 'NEW_WINDOW', 'PERSON_TYPE_ID', 'PARAMS', 'TARIF', 'HAVE_PAYMENT', 'HAVE_ACTION', 'HAVE_RESULT', 'HAVE_PREPAY', 'HAVE_RESULT_RECEIVE', 'ENCODING', 'LOGOTIP'));

		if (in_array('PARAMS', $select) && !array_key_exists('PSA_ID', $select))
			$select['PSA_ID'] = 'ID';

		if (in_array('PARAMS', $select) && !in_array('PERSON_TYPE_ID', $select))
			$select[] = 'PERSON_TYPE_ID';

		$order = array();
		foreach ($arOrder as $key => $value)
			$order[self::getAlias($key)] = $value;

		$groupBy = array();
		if ($arGroupBy !== false)
		{
			$arGroupBy = !is_array($arGroupBy) ? array($arGroupBy) : $arGroupBy;

			foreach ($arGroupBy as $key => $value)
				$groupBy[$key] = self::getAlias($value);
		}
		$dbRes = PaySystemActionTable::getList(
			array(
				'select' => $select,
				'filter' => $filter,
				'order' => $order,
				'group' => $groupBy,
			)
		);

		$limit = null;
		if (is_array($arNavStartParams) && isset($arNavStartParams['nTopCount']))
		{
			if ($arNavStartParams['nTopCount'] > 0)
				$limit = $arNavStartParams['nTopCount'];
		}

		$result = array();

		while ($data = $dbRes->fetch())
		{
			if ($limit !== null && !$limit)
				break;

			$dbRestriction = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
				'filter' => array(
					'SERVICE_ID' => $data['ID'],
					'SERVICE_TYPE' => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT
				)
			));

			while ($restriction = $dbRestriction->fetch())
			{
				if (!CSalePaySystemAction::checkRestriction($restriction, $arFilter))
					continue(2);
			}

			if (isset($data['ACTION_FILE']))
			{
				$oldHandler = array_search($data['ACTION_FILE'], CSalePaySystemAction::getOldToNewHandlersMap());
				if ($oldHandler !== false)
					$data['ACTION_FILE'] = $oldHandler;
			}

			if (array_key_exists('PARAMS', $data))
			{
				$params = CSalePaySystemAction::getParamsByConsumer('PAYSYSTEM_'.$data['PSA_ID'], $data['PERSON_TYPE_ID']);
				$params['BX_PAY_SYSTEM_ID'] = array('TYPE' => '', 'VALUE' => $data['PSA_ID']);
				$data['PARAMS'] = serialize($params);
			}

			foreach ($data as $key => $value)
			{
				if (!in_array($key, $salePaySystemFields))
				{
					$newKey = self::getAliasBack($key);
					if ($newKey != $key)
					{
						$data[$newKey] = $value;
						unset($data[$key]);
					}
				}
			}

			$result[] = $data;
			$limit--;
		}

		$dbRes = new \CDBResult();
		$dbRes->InitFromArray($result);

		return $dbRes;
	}

	private static function getAlias($key)
	{
		$prefix = '';
		$pos = strpos($key, 'PSA_');
		if ($pos > 0)
		{
			$prefix = substr($key, 0, $pos);
			$key = substr($key, $pos);
		}

		$aliases = self::getAliases();

		if (isset($aliases[$key]))
			$key = $aliases[$key];

		return $prefix.$key;
	}

	private static function getAliasBack($value)
	{
		$aliases = self::getAliases();
		$result = array_search($value, $aliases);

		return $result !== false ?  $result : $value;
	}

	/**
	 * @param $arFields
	 * @return bool|int
	 * @throws Exception
	 */
	
	/**
	* <p>Метод добавляет новую платежную систему на основании параметров из массива arFields. Нестатический метод.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой платежной системы, в
	* котором ключами являются названия параметров, а значениями -
	* соответствующие значения.<br>  		Допустимые ключи: 		<ul> <li> <b>  	 
	* CURRENCY</b> - валюта платежной системы;</li>  			<li> <b>  	  NAME</b> - название
	* платежной системы;</li>  			<li> <b>ACTIVE</b> - флаг (Y/N) активности платежной
	* системы;</li>  			<li> <b>SORT</b> - индекс сортировки;</li>  			<li> <b>DESCRIPTION</b> -
	* описание.</li>  		</ul>
	*
	* @return int <p>Возвращается код измененной записи или <i>false</i> в случае
	* ошибки.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystem/csalepaysystem__add.eba446b8.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		if (isset($arFields['LID']))
			unset($arFields['LID']);

		if (isset($arFields['CURRENCY']))
			unset($arFields['CURRENCY']);

		if (!CSalePaySystem::CheckFields("ADD", $arFields))
			return false;

		return CSalePaySystemAction::add($arFields);
	}
}
?>