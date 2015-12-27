<?

use	Bitrix\Sale\Compatible,
	Bitrix\Sale\Internals,
	Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/** @deprecated */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleOrderPropsValue
{
	
	/**
	* <p>Метод возвращает результат выборки записей из заказов в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле местоположения, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи значений
	* свойств. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр массиве со списком значений;</li> <li> <b>~</b> - значение поля
	* проверяется на соответствие передаваемому в фильтр шаблону;</li>
	* <li> <b>%</b> - значение поля проверяется на соответствие передаваемой
	* в фильтр строке в соответствии с языком запросов.</li> </ul> В
	* качестве "название_поляX" может стоять любое поле заказов.<br><br>
	* Пример фильтра: <pre class="syntax">array("~CODE" =&gt; "SH*")</pre> Этот фильтр означает
	* "выбрать все записи, в которых значение в поле CODE (символьный код
	* свойства) начинается с SH".<br><br> Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи значений свойств.
	* Массив имеет вид: <pre class="syntax"> array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле значений свойств. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру.<br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров значений свойств с ключами:</p>
	* <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код значения свойства заказа.</td> </tr> <tr> <td>ORDER_ID</td> <td>Код
	* заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td> <td>Код свойства.</td> </tr> <tr> <td>NAME</td>
	* <td>Название свойства.</td> </tr> <tr> <td>VALUE</td> <td>Значение свойства.</td> </tr>
	* <tr> <td>CODE</td> <td>Символьный код свойства.</td> </tr> </table> <p>Если в качестве
	* параметра arGroupBy передается пустой массив, то метод вернет число
	* записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Узнаем имя заказчика (т.е. значение, которое было введено в поле свойства 
	* // заказа $ORDER_ID с установленным флагом IS_PAYER)
	* $PAYER_NAME = "";
	* $db_order = CSaleOrder::GetList(
	*         array("DATE_UPDATE" =&gt; "DESC"),
	*         array("ID" =&gt; $ORDER_ID)
	*     );
	* if ($arOrder = $db_order-&gt;Fetch())
	* {
	*    $db_props = CSaleOrderProps::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array(
	*                 "PERSON_TYPE_ID" =&gt; $arOrder["PERSON_TYPE_ID"],
	*                 "IS_PAYER" =&gt; "Y"
	*             )
	*     );
	*    if ($arProps = $db_props-&gt;Fetch())
	*    {
	*       $db_vals = CSaleOrderPropsValue::GetList(
	*             array("SORT" =&gt; "ASC"),
	*             array(
	*                     "ORDER_ID" =&gt; $ORDER_ID,
	*                     "ORDER_PROPS_ID" =&gt; $arProps["ID"]
	*                 )
	*         );
	*       if ($arVals = $db_vals-&gt;Fetch())
	*         $PAYER_NAME = $arVals["VALUE"];
	*    }
	* }
	* echo $PAYER_NAME;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getlist.52da0d54.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if (strlen($arOrder) > 0 && strlen($arFilter) > 0)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;

			$arSelectFields = array("ID", "ORDER_ID", "ORDER_PROPS_ID", "NAME", "VALUE", "VALUE_ORIG", "CODE");
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "ORDER_ID", "ORDER_PROPS_ID", "NAME", "VALUE", "VALUE_ORIG", "CODE");

		// add aliases

		$query = new Compatible\OrderQueryLocation(Internals\OrderPropsValueTable::getEntity());
		$query->addLocationRuntimeField('VALUE', 'PROPERTY');
		$query->addAliases(array(
			// for GetList
			'PROP_ID'              => 'PROPERTY.ID',
			'PROP_PERSON_TYPE_ID'  => 'PROPERTY.PERSON_TYPE_ID',
			'PROP_NAME'            => 'PROPERTY.NAME',
			'PROP_TYPE'            => 'PROPERTY.TYPE',
			'PROP_REQUIED'         => 'PROPERTY.REQUIRED',
			'PROP_DEFAULT_VALUE'   => 'PROPERTY.DEFAULT_VALUE',
			'PROP_SORT'            => 'PROPERTY.SORT',
			'PROP_USER_PROPS'      => 'PROPERTY.USER_PROPS',
			'PROP_IS_LOCATION'     => 'PROPERTY.IS_LOCATION',
			'PROP_PROPS_GROUP_ID'  => 'PROPERTY.PROPS_GROUP_ID',
			'PROP_DESCRIPTION'     => 'PROPERTY.DESCRIPTION',
			'PROP_IS_EMAIL'        => 'PROPERTY.IS_EMAIL',
			'PROP_IS_PROFILE_NAME' => 'PROPERTY.IS_PROFILE_NAME',
			'PROP_IS_PAYER'        => 'PROPERTY.IS_PAYER',
			'PROP_IS_LOCATION4TAX' => 'PROPERTY.IS_LOCATION4TAX',
			'PROP_IS_ZIP'          => 'PROPERTY.IS_ZIP',
			'PROP_CODE'            => 'PROPERTY.CODE',
			'PROP_ACTIVE'          => 'PROPERTY.ACTIVE',
			'PROP_UTIL'            => 'PROPERTY.UTIL',
			// for converter
			'TYPE'     => 'PROPERTY.TYPE',
			'SETTINGS' => 'PROPERTY.SETTINGS',
			'MULTIPLE' => 'PROPERTY.MULTIPLE',
			// for GetOrderProps
			'PROPERTY_NAME'        => 'PROPERTY.NAME',
			'PROPS_GROUP_ID'       => 'PROPERTY.PROPS_GROUP_ID',
			'INPUT_FIELD_LOCATION' => 'PROPERTY.INPUT_FIELD_LOCATION',
			'IS_LOCATION'          => 'PROPERTY.IS_LOCATION',
			'IS_EMAIL'             => 'PROPERTY.IS_EMAIL',
			'IS_PROFILE_NAME'      => 'PROPERTY.IS_PROFILE_NAME',
			'IS_PAYER'             => 'PROPERTY.IS_PAYER',
			'ACTIVE'               => 'PROPERTY.ACTIVE',
			'UTIL'                 => 'PROPERTY.UTIL',
			'GROUP_SORT'           => 'PROPERTY.GROUP.SORT',
			'GROUP_NAME'           => 'PROPERTY.GROUP.NAME',
		));

		// relations for GetOrderRelatedProps

		$relationFilter = array();

		if ($arFilter['PAYSYSTEM_ID'])
		{
			$relationFilter []= array(
				'=PROPERTY.Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_TYPE' => 'P',
				'=PROPERTY.Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_ID' => $arFilter['PAYSYSTEM_ID'],
			);
		}

		if ($arFilter['DELIVERY_ID'])
		{
			$relationFilter['LOGIC'] = 'OR';
			$relationFilter []= array(
				'=PROPERTY.Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_TYPE' => 'D',
				'=PROPERTY.Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_ID' => $arFilter['DELIVERY_ID'],
			);
		}

		if ($relationFilter)
			$query->addFilter(null, $relationFilter);

		// execute

		$query->prepare($arOrder, $arFilter, $arGroupBy, $arSelectFields);

		if ($query->counted())
		{
			return $query->exec()->getSelectedRowsCount();
		}
		else
		{
			$result = new Compatible\CDBResult;
			$adapter = new CSaleOrderPropsValueAdapter($query->getSelectNamesAssoc() + array_flip($arSelectFields));
			$adapter->addFieldProxy('VALUE');
			$result->addFetchAdapter($adapter);

			if (! $query->aggregated())
			{
				$query->addAliasSelect('TYPE');
				$query->addAliasSelect('SETTINGS');
				$query->addAliasSelect('MULTIPLE');

				if ($relationFilter)
				{
					$query->registerRuntimeField('PROPERTY_ID', new Entity\ExpressionField('PROPERTY_ID', 'DISTINCT(%s)', 'ID'));
					$sel = $query->getSelect();
					array_unshift($sel, 'PROPERTY_ID');
					$query->setSelect($sel);
				}
			}

			return $query->compatibleExec($result, $arNavStartParams);
		}
	}

	
	/**
	* <p>Метод возвращает параметры значения с кодом ID свойства заказа. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код значения свойства заказа.
	*
	* @return array <p>Возвращается ассоциативный массив параметров значения
	* свойства с ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>
	* <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код значения свойства заказа.</td> </tr>
	* <tr> <td>ORDER_ID</td> <td>Код заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td> <td>Код
	* свойства.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr> <td>VALUE</td>
	* <td>Значение свойства.</td> </tr> <tr> <td>CODE</td> <td>Символьный код
	* свойства.</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getbyid.54043fd5.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		return $ID
			? self::GetList(array(), array('ID' => $ID))->Fetch()
			: false;
	}

	
	/**
	* <p>Метод возвращает набор значений свойств для заказа с кодом ORDER_ID. Кроме параметров значений свойств возвращаются также некоторые связанные значения. Метод динамичный.</p>
	*
	*
	* @param int $ORDER_ID  Код заказа.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров значений свойств заказа (и сопутствующие
	* параметры других объектов) с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код значения свойства
	* заказа.</td> </tr> <tr> <td>ORDER_ID</td> <td>Код заказа.</td> </tr> <tr> <td>ORDER_PROPS_ID</td>
	* <td>Код свойства заказа.</td> </tr> <tr> <td>NAME</td> <td>Название свойства
	* заказа (привязанное к значению) </td> </tr> <tr> <td>VALUE</td> <td>Значение
	* свойства заказа.</td> </tr> <tr> <td>CODE</td> <td>Символьный код свойства
	* заказа.</td> </tr> <tr> <td>PROPERTY_NAME</td> <td>Название свойства заказа.</td> </tr> <tr>
	* <td>TYPE</td> <td>Тип свойства заказа.</td> </tr> <tr> <td>PROPS_GROUP_ID</td> <td>Код группы
	* свойств заказа.</td> </tr> <tr> <td>GROUP_NAME</td> <td>Название группы свойств
	* заказа.</td> </tr> <tr> <td>IS_LOCATION</td> <td>Флаг (Y/N) использовать ли это
	* значение в качестве кода местоположения для получения стоимости
	* доставки.</td> </tr> <tr> <td>IS_EMAIL</td> <td>Флаг (Y/N) использовать ли это
	* значение в качестве email адреса покупателя.</td> </tr> <tr> <td>IS_PROFILE_NAME</td>
	* <td>Флаг (Y/N) использовать ли это значение в качестве названия
	* профиля покупателя.</td> </tr> <tr> <td>IS_PAYER</td> <td>Флаг (Y/N) использовать ли
	* это значение в качестве имени покупателя.</td> </tr> </table>
	* <p>Результирующий набор отсортирован последовательно по индексу
	* сортировки группы свойств заказа, названию группы свойств
	* заказа, индексу сортировки свойства заказа, названию свойства
	* заказа.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем все свойства заказа с кодом $ID, сгруппированые по группам свойств
	* $db_props = CSaleOrderPropsValue::GetOrderProps($ID);
	* $iGroup = -1;
	* while ($arProps = $db_props-&gt;Fetch())
	* {
	*    if ($iGroup!=IntVal($arProps["PROPS_GROUP_ID"]))
	*    {
	*       echo "&lt;b&gt;".$arProps["GROUP_NAME"]."&lt;/b&gt;&lt;br&gt;";
	*       $iGroup = IntVal($arProps["PROPS_GROUP_ID"]);
	*    }
	* 
	*    echo $arProps["NAME"].": ";
	* 
	*    if ($arProps["TYPE"]=="CHECKBOX")
	*    {
	*       if ($arProps["VALUE"]=="Y")
	*          echo "Да";
	*       else
	*          echo "Нет";
	*    }
	*    elseif ($arProps["TYPE"]=="TEXT" || $arProps["TYPE"]=="TEXTAREA")
	*    {
	*       echo htmlspecialchars($arProps["VALUE"]);
	*    }
	*    elseif ($arProps["TYPE"]=="SELECT" || $arProps["TYPE"]=="RADIO")
	*    {
	*       $arVal = CSaleOrderPropsVariant::GetByValue($arProps["ORDER_PROPS_ID"], $arProps["VALUE"]);
	*       echo htmlspecialchars($arVal["NAME"]);
	*    }
	*    elseif ($arProps["TYPE"]=="MULTISELECT")
	*    {
	*       $curVal = split(",", $arProps["VALUE"]);
	*       for ($i = 0; $i&lt;count($curVal); $i++)
	*       {
	*          $arVal = CSaleOrderPropsVariant::GetByValue($arProps["ORDER_PROPS_ID"], $curVal[$i]);
	*          if ($i&gt;0) echo ", ";
	*          echo htmlspecialchars($arVal["NAME"]);
	*       }
	*    }
	*    elseif ($arProps["TYPE"]=="LOCATION")
	*    {
	*       $arVal = CSaleLocation::GetByID($arProps["VALUE"], LANGUAGE_ID);
	*       echo htmlspecialchars($arVal["COUNTRY_NAME"]." - ".$arVal["CITY_NAME"]);
	*    }
	* 
	*    echo "&lt;br&gt;";
	* }
	* ?&gt;
	* 
	* 
	* //выборка по нескольким свойствам (например, по LOCATION и ADDRESS):
	* $dbOrderProps = CSaleOrderPropsValue::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array("ORDER_ID" =&gt; $intOrderID, "CODE"=&gt;array("LOCATION", "ADDRESS"))
	*     );
	*     while ($arOrderProps = $dbOrderProps-&gt;GetNext()):
	*             echo "&lt;pre&gt;"; print_r($arOrderProps); echo "&lt;/pre&gt;";
	*     endwhile;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getorderprops.af7c248d.php
	* @author Bitrix
	*/
	public static function GetOrderProps($ORDER_ID)
	{
		return self::GetList(
			array('GROUP_SORT', 'GROUP_NAME', 'PROP_SORT', 'PROPERTY_NAME', 'PROP_ID'),
			array('ORDER_ID' => $ORDER_ID),
			false, false,
			array(
				'ID', 'ORDER_ID', 'ORDER_PROPS_ID', 'NAME', 'VALUE', 'CODE',
				'PROPERTY_NAME', 'TYPE', 'PROPS_GROUP_ID', 'INPUT_FIELD_LOCATION', 'IS_LOCATION', 'IS_EMAIL', 'IS_PROFILE_NAME', 'IS_PAYER', 'ACTIVE', 'UTIL',
				'GROUP_NAME', 'GROUP_SORT',
			)
		);
	}

	public static function GetOrderRelatedProps($ORDER_ID, $arFilter = array())
	{
		if (! is_array($arFilter))
			$arFilter = array();

		return self::GetList(
			array('GROUP_SORT', 'GROUP_NAME', 'PROP_SORT', 'PROPERTY_NAME', 'PROP_ID'),
			array('ORDER_ID' => $ORDER_ID, 'PAYSYSTEM_ID' => $arFilter['PAYSYSTEM_ID'], 'DELIVERY_ID' => $arFilter['DELIVERY_ID']),
			false, false,
			array(
				'ID', 'ORDER_ID', 'ORDER_PROPS_ID', 'NAME', 'VALUE', 'CODE',
				'PROPERTY_NAME', 'TYPE', 'PROPS_GROUP_ID', 'INPUT_FIELD_LOCATION', 'IS_LOCATION', 'IS_EMAIL', 'IS_PROFILE_NAME', 'IS_PAYER', 'ACTIVE', 'UTIL',
				'GROUP_NAME', 'GROUP_SORT',
			)
		);
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "ORDER_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_ORDER_ID"), "EMPTY_ORDER_ID");
			return false;
		}
		
		if ((is_set($arFields, "ORDER_PROPS_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_PROPS_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_PROP_ID"), "EMPTY_ORDER_PROPS_ID");
			return false;
		}

		if (is_set($arFields, "ORDER_ID"))
		{
			if (!($arOrder = CSaleOrder::GetByID($arFields["ORDER_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_ID"], GetMessage("SKGOPV_NO_ORDER_ID")), "ERROR_NO_ORDER");
				return false;
			}
		}

		if (is_set($arFields, "ORDER_PROPS_ID"))
		{
			if (!($arOrder = CSaleOrderProps::GetByID($arFields["ORDER_PROPS_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_PROPS_ID"], GetMessage("SKGOPV_NO_PROP_ID")), "ERROR_NO_PROPERY");
				return false;
			}

			if (is_set($arFields, "ORDER_ID"))
			{
				$arFilter = Array(
						"ORDER_ID" => $arFields["ORDER_ID"],
						"ORDER_PROPS_ID" => $arFields["ORDER_PROPS_ID"],
					);
				if(IntVal($ID) > 0)
					$arFilter["!ID"] = $ID;
				$dbP = CSaleOrderPropsValue::GetList(Array(), $arFilter);
				if($arP = $dbP->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_DUPLICATE_PROP_ID", Array("#ID#" => $arFields["ORDER_PROPS_ID"], "#ORDER_ID#" => $arFields["ORDER_ID"])), "ERROR_DUPLICATE_PROP_ID");
					return false;
				}
			}
		}

		return true;
	}

	
	/**
	* <p>Метод добавляет новое значение свойства к заказу на основании параметров arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров значения свойства, ключами в
	* котором являются названия параметров значения свойства, а
	* значениями - соответствующие значения.<br><br> Допустимые ключи:<ul>
	* <li> <b>ORDER_ID</b> - код заказа (обязательное);</li> <li> <b>ORDER_PROPS_ID</b> - код
	* свойства (обязательное);</li> <li> <b>NAME</b> - название свойства
	* (обязательное);</li> <li> <b>VALUE</b> - значение свойства;</li> <li> <b>CODE</b> -
	* символьный код свойства.</li> </ul>
	*
	* @return int <p>Метод возвращает код добавленного значения свойства или <i>false</i>
	* в случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "ORDER_ID" =&gt; 124859,
	*    "ORDER_PROPS_ID" =&gt; 15,
	*    "NAME" =&gt; "Адрес доставки",
	*    "CODE" =&gt; "ADDRESS",
	*    "VALUE" =&gt; "ул. Строителей, дом 88, кв. 15"
	* );
	* 
	* CSaleOrderPropsValue::Add($arFields);
	* ?&gt;
	* 
	* 
	* //метод класса, который добавляет свойство (код/значение) к заказу, динамически узнавая идентификатор свойства: 
	*  public static function AddOrderProperty($code, $value, $order) {
	*       if (!strlen($code)) {
	*          return false;
	*       }
	*       if (CModule::IncludeModule('sale')) {
	*          if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' =&gt; $code))-&gt;Fetch()) {
	*             return CSaleOrderPropsValue::Add(array(
	*                'NAME' =&gt; $arProp['NAME'],
	*                'CODE' =&gt; $arProp['CODE'],
	*                'ORDER_PROPS_ID' =&gt; $arProp['ID'],
	*                'ORDER_ID' =&gt; $order,
	*                'VALUE' =&gt; $value,
	*             ));
	*          }
	*       }
	*    }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__add.af505780.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		if (! self::CheckFields('ADD', $arFields, 0))
			return false;

//		if ($arFields['VALUE'] && ($oldProperty = CSaleOrderProps::GetById($arFields['ORDER_PROPS_ID'])))
//		{
//			$oldProperty['VALUE'] = $arFields['VALUE'];
//			$arFields['VALUE'] = CSaleOrderPropsAdapter::convertOldToNew($oldProperty, 'VALUE', true);
//		}

		// location ID to CODE, VALUE is always present
		if((string) $arFields['VALUE'] != '')
			$arFields['VALUE'] = self::translateLocationIDToCode($arFields['VALUE'], $arFields['ORDER_PROPS_ID']);

		return Internals\OrderPropsValueTable::add(array_intersect_key($arFields, CSaleOrderPropsValueAdapter::$allFields))->getId();
	}

	
	/**
	* <p>Метод обновляет параметры значения с кодом ID свойства заказа на параметры из массива arFields. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание:</b> если при оформлении заказа не были заполнены какие-то свойства, то эти свойства обновить потом не получится, т.к. их нет в базе данных (незаполненные свойства не имеют пустых значений в базе). Поэтому, если нужно заполнить такое свойство, то сперва нужно создать его через <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__add.af505780.php">CSaleOrderPropsValue::Add</a> и только затем значение этого свойства будет доступно для <b>CSaleOrderPropsValue::Update</b> и <a href="http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__getlist.52da0d54.php">CSaleOrderPropsValue::Getlist</a>.</div>
	*
	*
	* @param int $ID  Код значения свойства заказа.
	*
	* @param array $arFields  Ассоциативный массив параметров значения свойства, ключами в
	* котором являются названия параметров значения свойства, а
	* значениями - соответствующие новые значения. <br><br> Допустимые
	* ключи: <ul> <li> <b>ORDER_ID</b> - код заказа;</li> <li> <b>ORDER_PROPS_ID</b> - код
	* свойства;</li> <li> <b>NAME</b> - название свойства;</li> <li> <b>VALUE</b> - значение
	* свойства;</li> <li> <b>CODE</b> - символьный код свойства.</li> </ul>
	*
	* @return int <p>Метод возвращает код обновленного значения свойства или <i>false</i>
	* в случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>CSaleOrderPropsValue::Update(8, array("CODE"=&gt;"ADDRESS"));<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderpropsvalue/csaleorderpropsvalue__update.4d3a46b6.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		if (! self::CheckFields('UPDATE', $arFields, $ID))
			return false;

//		if ($arFields['VALUE'])
//		{
//			if (!  ($propertyId = $arFields['ORDER_PROPS_ID'])
//				&& ($propertyValue = Internals\OrderPropsValueTable::getById($ID)->fetch()))
//			{
//				$propertyId = $propertyValue['ORDER_PROPS_ID'];
//			}
//
//			if ($propertyId && ($oldProperty = CSaleOrderProps::GetById($propertyId)))
//			{
//				$oldProperty['VALUE'] = $arFields['VALUE'];
//				$arFields['VALUE'] = CSaleOrderPropsAdapter::convertOldToNew($oldProperty, 'VALUE', true);
//			}
//		}

		// location ID to CODE
		if((string) $arFields['VALUE'] != '')
		{
			if((string) $arFields['ORDER_PROPS_ID'] != '')
				$propId = intval($arFields['ORDER_PROPS_ID']);
			else
			{
				$propValue = self::GetByID($ID);
				$propId = $propValue['ORDER_PROPS_ID'];
			}

			$arFields['VALUE'] = self::translateLocationIDToCode($arFields['VALUE'], $propId);
		}

		return Internals\OrderPropsValueTable::update($ID, array_intersect_key($arFields, CSaleOrderPropsValueAdapter::$allFields))->getId();
	}

	public static function translateLocationIDToCode($id, $orderPropId)
	{
		$prop = CSaleOrderProps::GetByID($orderPropId);
		if(isset($prop['TYPE']) && $prop['TYPE'] == 'LOCATION')
			return CSaleLocation::tryTranslateIDToCode($id);

		return $id;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$strSql = "DELETE FROM b_sale_order_props_value WHERE ID = ".$ID." ";
		return $DB->Query($strSql, True);
	}

	public static function DeleteByOrder($orderID)
	{
		global $DB;
		$orderID = IntVal($orderID);

		$strSql = "DELETE FROM b_sale_order_props_value WHERE ORDER_ID = ".$orderID." ";
		return $DB->Query($strSql, True);
	}
}

/** @deprecated */
final class CSaleOrderPropsValueAdapter implements Compatible\FetchAdapter
{
	private $fieldProxy = array();

	public function __construct(array $select)
	{
		$this->select = $select;
	}

	public function addFieldProxy($field)
	{
		if((string) $field == '')
			return false;

		$this->fieldProxy['PROXY_'.$field] = $field;

		return true;
	}

	public function adapt(array $newProperty)
	{
		if (! isset($newProperty['TYPE']))
			return $newProperty;

		if(is_array($newProperty))
		{
			foreach($newProperty as $k => $v)
			{
				if(isset($this->fieldProxy[$k]))
				{
					unset($newProperty[$k]);
					$newProperty[$this->fieldProxy[$k]] = $v;
				}
			}
		}

		$oldProperty = CSaleOrderPropsAdapter::convertNewToOld($newProperty);

		$oldProperty['VALUE'     ] = CSaleOrderPropsAdapter::getOldValue($newProperty['VALUE'], $newProperty['TYPE']);
		$oldProperty['PROP_TYPE' ] = $oldProperty['TYPE' ];
		$oldProperty['PROP_SIZE1'] = $oldProperty['SIZE1'];
		$oldProperty['PROP_SIZE2'] = $oldProperty['SIZE2'];

		return array_intersect_key($oldProperty, $this->select);
	}

	public static $allFields = array('ORDER_ID'=>1, 'ORDER_PROPS_ID'=>1, 'NAME'=>1, 'VALUE'=>1, 'CODE'=>1);
}
