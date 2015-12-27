<?

use
	Bitrix\Sale\Internals\OrderPropsTable,
	Bitrix\Sale\Compatible\OrderQuery,
	Bitrix\Sale\Compatible\FetchAdapter,
	Bitrix\Main\Entity,
	Bitrix\Main\Application,
	Bitrix\Main\SystemException,
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
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleOrderProps
{
	/*
	 * Checks order properties' values on the basis of order properties' restrictions
	 *
	 * @param array $arOrder - order data
	 * @param array $arOrderPropsValues - array of order properties values to be checked
	 * @param array $arErrors
	 * @param array $arWarnings
	 * @param int $paysystemId - id of the paysystem, will be used to get order properties related to this paysystem
	 * @param int $deliveryId - id of the delivery sysetm, will be used to get order properties related to this delivery system
	 */
	static function DoProcessOrder(&$arOrder, $arOrderPropsValues, &$arErrors, &$arWarnings, $paysystemId = 0, $deliveryId = "", $arOptions = array())
	{
		if (!is_array($arOrderPropsValues))
			$arOrderPropsValues = array();

		$arUser = null;

		$arFilter = array(
			"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
			"ACTIVE" => "Y"
		);

		if ($paysystemId != 0)
		{
			$arFilter["RELATED"]["PAYSYSTEM_ID"] = $paysystemId;
			$arFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		if (strlen($deliveryId) > 0)
		{
			$arFilter["RELATED"]["DELIVERY_ID"] = $deliveryId;
			$arFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		$dbOrderProps = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			$arFilter,
			false,
			false,
			array("ID", "NAME", "TYPE", "IS_LOCATION", "IS_LOCATION4TAX", "IS_PROFILE_NAME", "IS_PAYER", "IS_EMAIL",
				"REQUIED", "SORT", "IS_ZIP", "CODE", "DEFAULT_VALUE")
		);
		while ($arOrderProp = $dbOrderProps->Fetch())
		{
			if (!array_key_exists($arOrderProp["ID"], $arOrderPropsValues))
			{
				$curVal = $arOrderProp["DEFAULT_VALUE"];

				if (strlen($curVal) <= 0)
				{
					if ($arOrderProp["IS_EMAIL"] == "Y" || $arOrderProp["IS_PAYER"] == "Y")
					{
						if ($arUser == null)
						{
							$dbUser = CUser::GetList($by = "ID", $order = "desc", array("ID_EQUAL_EXACT" => $arOrder["USER_ID"]));
							$arUser = $dbUser->Fetch();
						}
						if ($arOrderProp["IS_EMAIL"] == "Y")
							$curVal = is_array($arUser) ? $arUser["EMAIL"] : "";
						elseif ($arOrderProp["IS_PAYER"] == "Y")
							$curVal = is_array($arUser) ? $arUser["NAME"].(strlen($arUser["NAME"]) <= 0 || strlen($arUser["LAST_NAME"]) <= 0 ? "" : " ").$arUser["LAST_NAME"] : "";
					}
				}
			}
			else
			{
				$curVal = $arOrderPropsValues[$arOrderProp["ID"]];
			}

			if ((!is_array($curVal) && strlen($curVal) > 0) || (is_array($curVal) && count($curVal) > 0))
			{
				//if ($arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "MULTISELECT" || $arOrderProp["TYPE"] == "RADIO")
				if ($arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "RADIO")
				{
					$arVariants = array();
					$dbVariants = CSaleOrderPropsVariant::GetList(
						array("SORT" => "ASC", "NAME" => "ASC"),
						array("ORDER_PROPS_ID" => $arOrderProp["ID"]),
						false,
						false,
						array("*")
					);
					while ($arVariant = $dbVariants->Fetch())
						$arVariants[] = $arVariant["VALUE"];

					if (!is_array($curVal))
						$curVal = array($curVal);

					$arKeys = array_keys($curVal);
					foreach ($arKeys as $k)
					{
						if (!in_array($curVal[$k], $arVariants))
							unset($curVal[$k]);
					}

					if ($arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "RADIO")
						$curVal = array_shift($curVal);
				}
				elseif ($arOrderProp["TYPE"] == "LOCATION")
				{
					if (is_array($curVal))
						$curVal = array_shift($curVal);

					if(CSaleLocation::isLocationProMigrated())
					{
						// if we came from places like CRM, we got location in CODEs, because CRM knows nothing about location IDs.
						// so, CRM sends LOCATION_IN_CODES in options array. In the other case, we assume we got locations as IDs
						$res = CSaleLocation::GetById($curVal);
						if(intval($res['ID']))
						{
							$curVal = $res['ID'];
							$locId = $res['ID'];
						}
						else
						{
							$curVal = null;
							$locId = false;
						}
					}
					else // dead branch in 15.5.x
					{
						$dbVariants = CSaleLocation::GetList(
							array(),
							array("ID" => $curVal),
							false,
							false,
							array("ID")
						);
						if ($arVariant = $dbVariants->Fetch())
							$curVal = intval($arVariant["ID"]);
						else
							$curVal = null;
					}
				}
			}

			if ($arOrderProp["TYPE"] == "LOCATION" && ($arOrderProp["IS_LOCATION"] == "Y" || $arOrderProp["IS_LOCATION4TAX"] == "Y"))
			{
				if ($arOrderProp["IS_LOCATION"] == "Y")
					$arOrder["DELIVERY_LOCATION"] = $locId;
				if ($arOrderProp["IS_LOCATION4TAX"] == "Y")
					$arOrder["TAX_LOCATION"] = $locId;

				if (!$locId)
					$bErrorField = true;
			}
			elseif ($arOrderProp["IS_PROFILE_NAME"] == "Y" || $arOrderProp["IS_PAYER"] == "Y" || $arOrderProp["IS_EMAIL"] == "Y" || $arOrderProp["IS_ZIP"] == "Y")
			{
				$curVal = trim($curVal);
				if ($arOrderProp["IS_PROFILE_NAME"] == "Y")
					$arOrder["PROFILE_NAME"] = $curVal;
				if ($arOrderProp["IS_PAYER"] == "Y")
					$arOrder["PAYER_NAME"] = $curVal;
				if ($arOrderProp["IS_ZIP"] == "Y")
					$arOrder["DELIVERY_LOCATION_ZIP"] = $curVal;
				if ($arOrderProp["IS_EMAIL"] == "Y")
				{
					$arOrder["USER_EMAIL"] = $curVal;
					if (!check_email($curVal))
						$arWarnings[] = array("CODE" => "PARAM", "TEXT" => str_replace(array("#EMAIL#", "#NAME#"), array(htmlspecialcharsbx($curVal), htmlspecialcharsbx($arOrderProp["NAME"])), GetMessage("SALE_GOPE_WRONG_EMAIL")));
				}

				if (strlen($curVal) <= 0)
					$bErrorField = true;
			}
			elseif ($arOrderProp["REQUIED"] == "Y")
			{
				if ($arOrderProp["TYPE"] == "TEXT" || $arOrderProp["TYPE"] == "TEXTAREA" || $arOrderProp["TYPE"] == "RADIO" || $arOrderProp["TYPE"] == "SELECT" || $arOrderProp["TYPE"] == "CHECKBOX")
				{
					if (strlen($curVal) <= 0)
						$bErrorField = true;
				}
				elseif ($arOrderProp["TYPE"] == "LOCATION")
				{
					if (intval($curVal) <= 0)
						$bErrorField = true;
				}
				elseif ($arOrderProp["TYPE"] == "MULTISELECT")
				{
					//if (!is_array($curVal) || count($curVal) <= 0)
					if (strlen($curVal) <= 0)
						$bErrorField = true;
				}
				elseif ($arOrderProp["TYPE"] == "FILE")
				{
					if (is_array($curVal))
					{
						foreach ($curVal as $index => $arFileData)
						{
							if (!array_key_exists("name", $arFileData) && !array_key_exists("file_id", $arFileData))
								$bErrorField = true;
						}
					}
					else
					{
						$bErrorField = true;
					}
				}
			}

			if ($bErrorField)
			{
				$arWarnings[] = array("CODE" => "PARAM", "TEXT" => str_replace("#NAME#", htmlspecialcharsbx($arOrderProp["NAME"]), GetMessage("SALE_GOPE_FIELD_EMPTY")));
				$bErrorField = false;
			}

			$arOrder["ORDER_PROP"][$arOrderProp["ID"]] = $curVal;
		}
	}

	/*
	 * Updates/adds order properties' values
	 *
	 * @param array $orderId
	 * @param array $personTypeId
	 * @param array $arOrderProps - array of order properties values
	 * @param array $arErrors
	 */
	static function DoSaveOrderProps($orderId, $personTypeId, $arOrderProps, &$arErrors, $paysystemId = 0, $deliveryId = "")
	{
		$arIDs = array();
		$dbResult = CSaleOrderPropsValue::GetList(
			array(),
			//array("ORDER_ID" => $orderId, "PROP_UTIL" => "N"),
			array("ORDER_ID" => $orderId),
			false,
			false,
			array("ID", "ORDER_PROPS_ID")
		);
		while ($arResult = $dbResult->Fetch())
			$arIDs[$arResult["ORDER_PROPS_ID"]] = $arResult["ID"];

		$arFilter = array(
			"PERSON_TYPE_ID" => $personTypeId,
			"ACTIVE" => "Y"
		);

		if ($paysystemId != 0)
		{
			$arFilter["RELATED"]["PAYSYSTEM_ID"] = $paysystemId;
			$arFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		if (strlen($deliveryId) > 0)
		{
			$arFilter["RELATED"]["DELIVERY_ID"] = $deliveryId;
			$arFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		$dbOrderProperties = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			$arFilter,
			false,
			false,
			array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT")
		);
		while ($arOrderProperty = $dbOrderProperties->Fetch())
		{
			$curVal = $arOrderProps[$arOrderProperty["ID"]];

			if (($arOrderProperty["TYPE"] == "MULTISELECT") && is_array($curVal))
				$curVal = implode(",", $curVal);

			if ($arOrderProperty["TYPE"] == "FILE" && is_array($curVal))
			{
				$tmpVal = "";
				foreach ($curVal as $index => $fileData)
				{
					$bModify = true;
					if (isset($fileData["file_id"])) // existing file
					{
						if (isset($fileData["del"]))
						{
							$arFile = CFile::MakeFileArray($fileData["file_id"]);
							$arFile["del"] = $fileData["del"];
							$arFile["old_file"] = $fileData["file_id"];
						}
						else
						{
							$bModify = false;
							if (strlen($tmpVal) > 0)
								$tmpVal .= ", ".$fileData["file_id"];
							else
								$tmpVal = $fileData["file_id"];
						}
					}
					else // new file array
						$arFile = $fileData;

					if (isset($arFile["name"]) && strlen($arFile["name"]) > 0 && $bModify)
					{
						$arFile["MODULE_ID"] = "sale";
						$fid = CFile::SaveFile($arFile, "sale");
						if (intval($fid) > 0)
						{
							if (strlen($tmpVal) > 0)
								$tmpVal .= ", ".$fid;
							else
								$tmpVal = $fid;
						}
					}
				}

				$curVal = $tmpVal;
			}

			if (strlen($curVal) > 0)
			{
				$arFields = array(
					"ORDER_ID" => $orderId,
					"ORDER_PROPS_ID" => $arOrderProperty["ID"],
					"NAME" => $arOrderProperty["NAME"],
					"CODE" => $arOrderProperty["CODE"],
					"VALUE" => $curVal
				);

				if (array_key_exists($arOrderProperty["ID"], $arIDs))
				{
					CSaleOrderPropsValue::Update($arIDs[$arOrderProperty["ID"]], $arFields);
					unset($arIDs[$arOrderProperty["ID"]]);
				}
				else
				{
					CSaleOrderPropsValue::Add($arFields);
				}
			}
		}

		foreach ($arIDs as $id)
			CSaleOrderPropsValue::Delete($id);
	}

	
	/**
	* <p>Метод возвращает результат выборки из свойств заказов в соответствии со своими параметрами. Метод динамичный.</p>
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
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи свойств
	* заказа. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b>!</b> - отрицание;</li> <li> <b>+</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр разделенном запятой списке значений. Можно передавать и
	* фильтр. Для ключа <b>CODE</b> - корректно формирует фильтр только для
	* массива, а не для перечисление через запятые.;</li> <li> <b>~</b> -
	* значение поля проверяется на соответствие передаваемому в
	* фильтр шаблону;</li> <li> <b>%</b> - значение поля проверяется на
	* соответствие передаваемой в фильтр строке в соответствии с
	* языком запросов.</li> </ul> В качестве "название_поляX" может стоять
	* любое поле заказов.<br><br> Пример фильтра: <pre class="syntax">array("REQUIED" =&gt;
	* "Y")</pre> Этот фильтр означает "выбрать все записи, в которых
	* значение в поле REQUIED (обязательно для заполнения) равно Y".<br><br>
	* Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи свойств заказа.
	* Массив имеет вид: <pre class="syntax"> array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле свойств заказа. В
	* качестве группирующей функции могут стоять: <ul> <li> <b>COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b>MAX</b> -
	* вычисление максимального значения;</li> <li> <b>UTIL</b> - флаг Y/N,
	* служебное;</li> <li> <b>SUM</b> - вычисление суммы.</li> </ul> Если массив
	* пустой, то метод вернет число записей, удовлетворяющих
	* фильтру.<br><br> Значение по умолчанию - <i>false</i> - означает, что
	* результат группироваться не будет.
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
	* ассоциативных массивов параметров свойств с ключами:</p> <table
	* class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код свойства заказа.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	* плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr>
	* <td>TYPE</td> <td>Тип свойства. Допустимые значения: <ul> <li>CHECKBOX - флаг,</li>
	* <li>TEXT - строка текста,</li> <li>SELECT - выпадающий список значений, </li>
	* <li>MULTISELECT - список со множественным выбором,</li> <li>TEXTAREA -
	* многострочный текст,</li> <li>LOCATION - местоположение,</li> <li>RADIO -
	* переключатель.</li> </ul> </td> </tr> <tr> <td>REQUIED</td> <td>Флаг (Y/N) обязательное
	* ли поле.</td> </tr> <tr> <td>DEFAULT_VALUE</td> <td>Значение по умолчанию.</td> </tr> <tr>
	* <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr> <td>USER_PROPS</td> <td>Флаг (Y/N) входит
	* ли это свойство в профиль покупателя.</td> </tr> <tr> <td>IS_LOCATION</td> <td>Флаг
	* (Y/N) использовать ли значение свойства как местоположение
	* покупателя для расчёта стоимости доставки (только для свойств
	* типа LOCATION)</td> </tr> <tr> <td>PROPS_GROUP_ID</td> <td>Код группы свойств.</td> </tr> <tr>
	* <td>SIZE1</td> <td>Ширина поля (размер по горизонтали).</td> </tr> <tr> <td>SIZE2</td>
	* <td>Высота поля (размер по вертикали).</td> </tr> <tr> <td>DESCRIPTION</td>
	* <td>Описание свойства.</td> </tr> <tr> <td>IS_EMAIL</td> <td>Флаг (Y/N) использовать
	* ли значение свойства как E-Mail покупателя.</td> </tr> <tr> <td>IS_PROFILE_NAME</td>
	* <td>Флаг (Y/N) использовать ли значение свойства как название
	* профиля покупателя.</td> </tr> <tr> <td>IS_PAYER</td> <td>Флаг (Y/N) использовать ли
	* значение свойства как имя плательщика.</td> </tr> <tr> <td>IS_LOCATION4TAX</td>
	* <td>Флаг (Y/N) использовать ли значение свойства как местоположение
	* покупателя для расчёта налогов (только для свойств типа LOCATION)</td>
	* </tr> <tr> <td>CODE</td> <td>Символьный код свойства.</td> </tr> </table> <p>Если в
	* качестве параметра arGroupBy передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем форму для ввода свойств заказа для группы свойств с кодом 5, которые входят в профиль покупателя, для типа плательщика с кодом 2
	* $db_props = CSaleOrderProps::GetList(
	*         array("SORT" =&gt; "ASC"),
	*         array(
	*                 "PERSON_TYPE_ID" =&gt; 2,
	*                 "PROPS_GROUP_ID" =&gt; 5,
	*                 "USER_PROPS" =&gt; "Y"
	*             ),
	*         false,
	*         false,
	*         array()
	*     );
	* 
	* if ($props = $db_props-&gt;Fetch())
	* {
	*    echo "Заполните параметры заказа:&lt;br&gt;";
	*    do
	*    {
	*       echo $props["NAME"];
	*       if ($props["REQUIED"]=="Y" || 
	*           $props["IS_EMAIL"]=="Y" || 
	*           $props["IS_PROFILE_NAME"]=="Y" || 
	*           $props["IS_LOCATION"]=="Y" || 
	*           $props["IS_LOCATION4TAX"]=="Y" || 
	*           $props["IS_PAYER"]=="Y")
	*       {
	*          echo "*";
	*       }
	*       echo ": ";
	* 
	*       if ($props["TYPE"]=="CHECKBOX")
	*       {
	*          echo '&lt;input type="checkbox" class="inputcheckbox" name="ORDER_PROP_'.$props["ID"].'" value="Y"'.(($props["DEFAULT_VALUE"]=="Y")?" checked":"").'&gt;';
	*       }
	*       elseif ($props["TYPE"]=="TEXT")
	*       {
	*          echo '&lt;input type="text" class="inputtext" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:30).'" maxlength="250" value="'.htmlspecialchars($props["DEFAULT_VALUE"]).'" name="ORDER_PROP_'.$props["ID"].'"&gt;';
	*       }
	*       elseif ($props["TYPE"]=="SELECT")
	*       {
	*          echo '&lt;select name="ORDER_PROP_'.$props["ID"].'" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:1).'"&gt;';
	*          $db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=&gt;$props["ID"]));
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["VALUE"].'"'.(($vars["VALUE"]==$props["DEFAULT_VALUE"])?" selected":"").'&gt;'.htmlspecialchars($vars["NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="MULTISELECT")
	*       {
	*          echo '&lt;select multiple name="ORDER_PROP_'.$props["ID"].'[]" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:5).'"&gt;';
	*          $arDefVal = Split(",", $props["DEFAULT_VALUE"]);
	*          for ($i = 0; $i&lt;count($arDefVal); $i++)
	*             $arDefVal[$i] = Trim($arDefVal[$i]);
	* 
	*          $db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=&gt;$props["ID"]));
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["VALUE"].'"'.(in_array($vars["VALUE"], $arDefVal)?" selected":"").'&gt;'.htmlspecialchars($vars["NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="TEXTAREA")
	*       {
	*          echo '&lt;textarea rows="'.((IntVal($props["SIZE2"])&gt;0)?$props["SIZE2"]:4).'" cols="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:40).'" name="ORDER_PROP_'.$props["ID"].'"&gt;'.htmlspecialchars($props["DEFAULT_VALUE"]).'&lt;/textarea&gt;';
	*       }
	*       elseif ($props["TYPE"]=="LOCATION")
	*       {
	*          echo '&lt;select name="ORDER_PROP_'.$props["ID"].'" size="'.((IntVal($props["SIZE1"])&gt;0)?$props["SIZE1"]:1).'"&gt;';
	*          $db_vars = CSaleLocation::GetList(Array("SORT"=&gt;"ASC", "COUNTRY_NAME_LANG"=&gt;"ASC", "CITY_NAME_LANG"=&gt;"ASC"), array(), LANGUAGE_ID);
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;option value="'.$vars["ID"].'"'.((IntVal($vars["ID"])==IntVal($props["DEFAULT_VALUE"]))?" selected":"").'&gt;'.htmlspecialchars($vars["COUNTRY_NAME"]." - ".$vars["CITY_NAME"]).'&lt;/option&gt;';
	*          }
	*          echo '&lt;/select&gt;';
	*       }
	*       elseif ($props["TYPE"]=="RADIO")
	*       {
	*          $db_vars = CSaleOrderPropsVariant::GetList(($by="SORT"), ($order="ASC"), Array("ORDER_PROPS_ID"=&gt;$props["ID"]));
	*          while ($vars = $db_vars-&gt;Fetch())
	*          {
	*             echo '&lt;input type="radio" name="ORDER_PROP_'.$props["ID"].'" value="'.$vars["VALUE"].'"'.(($vars["VALUE"]==$props["DEFAULT_VALUE"])?" checked":"").'&gt;'.htmlspecialchars($vars["NAME"]).'&lt;br&gt;';
	*          }
	*       }
	* 
	*       if (strlen($props["DESCRIPTION"])&gt;0)
	*       {
	*          echo "&lt;br&gt;&lt;small&gt;".$props["DESCRIPTION"]."&lt;/small&gt;";
	*       }
	* 
	*       echo "&lt;br&gt;";
	*    }
	*    while ($props = $db_props-&gt;Fetch());
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__getlist.d76e30a4.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
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

			$arSelectFields = array();
		}

		if (! $arSelectFields)
			$arSelectFields = array(
				"ID", "PERSON_TYPE_ID", "NAME", "TYPE", "REQUIED", "DEFAULT_VALUE", "DEFAULT_VALUE_ORIG", "SORT", "USER_PROPS",
				"IS_LOCATION", "PROPS_GROUP_ID", "SIZE1", "SIZE2", "DESCRIPTION", "IS_EMAIL", "IS_PROFILE_NAME",
				"IS_PAYER", "IS_LOCATION4TAX", "IS_ZIP", "CODE", "IS_FILTERED", "ACTIVE", "UTIL",
				"INPUT_FIELD_LOCATION", "MULTIPLE", "PAYSYSTEM_ID", "DELIVERY_ID"
			);

		// add aliases

		$query = new \Bitrix\Sale\Compatible\OrderQueryLocation(OrderPropsTable::getEntity());
		$query->addLocationRuntimeField('DEFAULT_VALUE');
		$query->addAliases(array(
			'REQUIED'              => 'REQUIRED',
			'GROUP_ID'             => 'GROUP.ID',
			'GROUP_PERSON_TYPE_ID' => 'GROUP.PERSON_TYPE_ID',
			'GROUP_NAME'           => 'GROUP.NAME',
			'GROUP_SORT'           => 'GROUP.SORT',
			'PERSON_TYPE_LID'      => 'PERSON_TYPE.LID',
			'PERSON_TYPE_NAME'     => 'PERSON_TYPE.NAME',
			'PERSON_TYPE_SORT'     => 'PERSON_TYPE.SORT',
			'PERSON_TYPE_ACTIVE'   => 'PERSON_TYPE.ACTIVE',
			'PAYSYSTEM_ID'         => 'Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.PROPERTY_ID',
			'DELIVERY_ID'          => 'Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.PROPERTY_ID',
		));

		// relations

		if (isset($arFilter['RELATED']))
		{
			// 1. filter related to something
			if (is_array($arFilter['RELATED']))
			{
				$relationFilter = array();

				if ($arFilter['RELATED']['PAYSYSTEM_ID'])
					$relationFilter []= array(
						'=Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_TYPE' => 'P',
						'=Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_ID' => $arFilter['RELATED']['PAYSYSTEM_ID'],
					);

				if ($arFilter['RELATED']['DELIVERY_ID'])
				{
					if ($relationFilter)
						$relationFilter['LOGIC'] = $arFilter['RELATED']['LOGIC'] == 'AND' ? 'AND' : 'OR';

					$relationFilter []= array(
						'=Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_TYPE' => 'D',
						'=Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.ENTITY_ID' => $arFilter['RELATED']['DELIVERY_ID'],
					);
				}

				// all other
				if ($arFilter['RELATED']['TYPE'] == 'WITH_NOT_RELATED' && $relationFilter)
				{
					$relationFilter = array(
						'LOGIC' => 'OR',
						$relationFilter,
						array('=Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.PROPERTY_ID' => null),
					);
				}

				if ($relationFilter)
					$query->addFilter(null, $relationFilter);
			}
			// 2. filter all not related to anything
			else
			{
				$query->addFilter('=Bitrix\Sale\Internals\OrderPropsRelationTable:lPROPERTY.PROPERTY_ID', null);

				if (($key = array_search('PAYSYSTEM_ID', $arSelectFields)) !== false)
					unset($arSelectFields[$key]);

				if (($key = array_search('DELIVERY_ID', $arSelectFields)) !== false)
					unset($arSelectFields[$key]);
			}

			unset($arFilter['RELATED']);
		}

		if (isset($arFilter['PERSON_TYPE_ID']) && is_array($arFilter['PERSON_TYPE_ID']))
		{
			foreach ($arFilter['PERSON_TYPE_ID'] as $personTypeKey => $personTypeValue)
			{
				if (!is_array($personTypeValue) && !empty($personTypeValue) && intval($personTypeValue) > 0)
				{
					unset($arFilter['PERSON_TYPE_ID'][$personTypeKey]);
					$arFilter['PERSON_TYPE_ID'][] = $personTypeValue;
				}
			}
		}

		// execute

		$query->prepare($arOrder, $arFilter, $arGroupBy, $arSelectFields);

		if ($query->counted())
		{
			return $query->exec()->getSelectedRowsCount();
		}
		else
		{
			$result = new \Bitrix\Sale\Compatible\CDBResult;
			$adapter = new CSaleOrderPropsAdapter($query, $arSelectFields);
			$adapter->addFieldProxy('DEFAULT_VALUE');
			$result->addFetchAdapter($adapter);
			return $query->compatibleExec($result, $arNavStartParams);
		}
	}

	
	/**
	* <p>Метод возвращает параметры свойства с кодом ID заказа. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код свойства заказа. </ht
	*
	* @return array <p>Возвращается ассоциативный массив параметров свойства с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код свойства заказа.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип
	* плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название свойства.</td> </tr> <tr>
	* <td>TYPE</td> <td>Тип свойства. Допустимые значения:<ul> <li> <b>CHECKBOX</b> -
	* флаг,</li> <li> <b>TEXT</b> - строка текста,</li> <li> <b>SELECT</b> - выпадающий список
	* значений, </li> <li> <b>MULTISELECT</b> - список со множественным выбором,</li> <li>
	* <b>TEXTAREA</b> - многострочный текст,</li> <li> <b>LOCATION</b> - местоположение,</li>
	* <li> <b>RADIO</b> - переключатель.</li> </ul> </td> </tr> <tr> <td>REQUIED</td> <td>Флаг (Y/N)
	* обязательное ли поле.</td> </tr> <tr> <td>DEFAULT_VALUE</td> <td>Значение по
	* умолчанию.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr>
	* <td>USER_PROPS</td> <td>Флаг (Y/N) входит ли это свойство в профиль
	* покупателя.</td> </tr> <tr> <td>IS_LOCATION</td> <td>Флаг (Y/N) использовать ли
	* значение свойства как местоположение покупателя для расчёта
	* стоимости доставки (только для свойств типа LOCATION)</td> </tr> <tr>
	* <td>PROPS_GROUP_ID</td> <td>Код группы свойств.</td> </tr> <tr> <td>SIZE1</td> <td>Ширина поля
	* (размер по горизонтали).</td> </tr> <tr> <td>SIZE2</td> <td>Высота поля (размер по
	* вертикали).</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание свойства.</td> </tr> <tr>
	* <td>IS_EMAIL</td> <td>Флаг (Y/N) использовать ли значение свойства как E-Mail
	* покупателя.</td> </tr> <tr> <td>IS_PROFILE_NAME</td> <td>Флаг (Y/N) использовать ли
	* значение свойства как название профиля покупателя.</td> </tr> <tr>
	* <td>IS_PAYER</td> <td>Флаг (Y/N) использовать ли значение свойства как имя
	* плательщика.</td> </tr> <tr> <td>IS_LOCATION4TAX</td> <td>Флаг (Y/N) использовать ли
	* значение свойства как местоположение покупателя для расчёта
	* налогов (только для свойств типа LOCATION)</td> </tr> <tr> <td>CODE</td>
	* <td>Символьный код свойства.</td> </tr> </table> <p>  </p<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if ($arOrderProps = CSaleOrderProps::GetByID($ID))
	* {
	*    echo "&lt;pre&gt;";
	*    print_r($arOrderProps);
	*    echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__getbyid.39564dea.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$id = (int) $ID;
		return $id > 0 && $id == $ID
			? self::GetList(array(), array('ID' => $ID))->Fetch()
			: false;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		if (is_set($arFields, "PERSON_TYPE_ID") && $ACTION != "ADD")
			UnSet($arFields["PERSON_TYPE_ID"]);

		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"]) <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGOP_EMPTY_PERS_TYPE"), "ERROR_NO_PERSON_TYPE");
			return false;
		}
		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGOP_EMPTY_PROP_NAME"), "ERROR_NO_NAME");
			return false;
		}
		if ((is_set($arFields, "TYPE") || $ACTION=="ADD") && strlen($arFields["TYPE"]) <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGOP_EMPTY_PROP_TYPE"), "ERROR_NO_TYPE");
			return false;
		}

		if (is_set($arFields, "REQUIED") && $arFields["REQUIED"]!="Y")
			$arFields["REQUIED"]="N";
		if (is_set($arFields, "USER_PROPS") && $arFields["USER_PROPS"]!="Y")
			$arFields["USER_PROPS"]="N";
		if (is_set($arFields, "IS_LOCATION") && $arFields["IS_LOCATION"]!="Y")
			$arFields["IS_LOCATION"]="N";
		if (is_set($arFields, "IS_LOCATION4TAX") && $arFields["IS_LOCATION4TAX"]!="Y")
			$arFields["IS_LOCATION4TAX"]="N";
		if (is_set($arFields, "IS_EMAIL") && $arFields["IS_EMAIL"]!="Y")
			$arFields["IS_EMAIL"]="N";
		if (is_set($arFields, "IS_PROFILE_NAME") && $arFields["IS_PROFILE_NAME"]!="Y")
			$arFields["IS_PROFILE_NAME"]="N";
		if (is_set($arFields, "IS_PAYER") && $arFields["IS_PAYER"]!="Y")
			$arFields["IS_PAYER"]="N";
		if (is_set($arFields, "IS_FILTERED") && $arFields["IS_FILTERED"]!="Y")
			$arFields["IS_FILTERED"]="N";
		if (is_set($arFields, "IS_ZIP") && $arFields["IS_ZIP"]!="Y")
			$arFields["IS_ZIP"]="N";

		if (is_set($arFields, "IS_LOCATION") && is_set($arFields, "TYPE") && $arFields["IS_LOCATION"]=="Y" && $arFields["TYPE"]!="LOCATION")
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGOP_WRONG_PROP_TYPE"), "ERROR_WRONG_TYPE1");
			return false;
		}
		if (is_set($arFields, "IS_LOCATION4TAX") && is_set($arFields, "TYPE") && $arFields["IS_LOCATION4TAX"]=="Y" && $arFields["TYPE"]!="LOCATION")
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGOP_WRONG_PROP_TYPE"), "ERROR_WRONG_TYPE2");
			return false;
		}

		if ((is_set($arFields, "PROPS_GROUP_ID") || $ACTION=="ADD") && IntVal($arFields["PROPS_GROUP_ID"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGOP_EMPTY_PROP_GROUP"), "ERROR_NO_GROUP");
			return false;
		}

		if (is_set($arFields, "PERSON_TYPE_ID"))
		{
			if (!($arPersonType = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"])))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], Loc::getMessage("SKGOP_NO_PERS_TYPE")), "ERROR_NO_PERSON_TYPE");
				return false;
			}
		}

		return true;
	}

	
	/**
	* <p>Метод добавляет новое свойство заказа с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив, в котором ключами являются названия
	* параметров свойства, а значениями - значения этих параметров.<br><br>
	* Допустимые ключи: <ul> <li> <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li> <b>NAME</b>
	* - название свойства (тип плательщика зависит от сайта, а сайт - от
	* языка; название должно быть на соответствующем языке);</li> <li>
	* <b>TYPE</b> - тип свойства. Допустимые значения: <ul> <li> <b>CHECKBOX</b> - флаг;</li>
	* <li> <b>TEXT</b> - строка текста;</li> <li> <b>SELECT</b> - выпадающий список
	* значений;</li> <li> <b>MULTISELECT</b> - список со множественным выбором;</li> <li>
	* <b>TEXTAREA</b> - многострочный текст;</li> <li> <b>LOCATION</b> - местоположение;</li>
	* <li> <b>RADIO</b> - переключатель.</li> </ul> </li> <li> <b>REQUIED</b> - флаг (Y/N)
	* обязательное ли поле;</li> <li> <b>DEFAULT_VALUE</b> - значение по умолчанию;</li>
	* <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>USER_PROPS</b> - флаг (Y/N) входит ли
	* это свойство в профиль покупателя;</li> <li> <b>IS_LOCATION</b> - флаг (Y/N)
	* использовать ли значение свойства как местоположение покупателя
	* для расчёта стоимости доставки (только для свойств типа LOCATION); </li>
	* <li> <b>PROPS_GROUP_ID</b> - код группы свойств;</li> <li> <b>SIZE1</b> - ширина поля
	* (размер по горизонтали);</li> <li> <b>SIZE2</b> - высота поля (размер по
	* вертикали);</li> <li> <b>DESCRIPTION</b> - описание свойства;</li> <li> <b>IS_EMAIL</b> -
	* флаг (Y/N) использовать ли значение свойства как E-Mail покупателя;</li>
	* <li> <b>IS_PROFILE_NAME</b> - флаг (Y/N) использовать ли значение свойства как
	* название профиля покупателя; </li> <li> <b>IS_PAYER</b> - флаг (Y/N)
	* использовать ли значение свойства как имя плательщика;</li> <li>
	* <b>IS_LOCATION4TAX</b> - флаг (Y/N) использовать ли значение свойства как
	* местоположение покупателя для расчёта налогов (только для
	* свойств типа <b>LOCATION</b>);</li> <li> <b>CODE</b> - символьный код свойства.</li>
	* <li> <b>IS_FILTERED</b> - свойство доступно в фильтре по заказам. С версии
	* 10.0.</li> <li> <b>IS_ZIP</b> - использовать как почтовый индекс. С версии
	* 10.0.</li> <li> <b>UTIL</b> - позволяет использовать свойство только в
	* административной части. С версии 11.0.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного свойства заказа.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "PERSON_TYPE_ID" =&gt; 2,
	*    "NAME" =&gt; "Комплектация",
	*    "TYPE" =&gt; "RADIO",
	*    "REQUIED" =&gt; "Y",
	*    "DEFAULT_VALUE" =&gt; "F",
	*    "SORT" =&gt; 100,
	*    "CODE" =&gt; "COMPLECT",
	*    "USER_PROPS" =&gt; "N",
	*    "IS_LOCATION" =&gt; "N",
	*    "IS_LOCATION4TAX" =&gt; "N",
	*    "PROPS_GROUP_ID" =&gt; 1,
	*    "SIZE1" =&gt; 0,
	*    "SIZE2" =&gt; 0,
	*    "DESCRIPTION" =&gt; "",
	*    "IS_EMAIL" =&gt; "N",
	*    "IS_PROFILE_NAME" =&gt; "N",
	*    "IS_PAYER" =&gt; "N"
	* );
	* 
	* // Если установлен код свойства, то изменяем свойство с этим кодом,
	* // иначе добавляем новой свойство
	* if ($ID&gt;0)
	* {
	*    if (!CSaleOrderProps::Update($ID, $arFields))
	*    {
	*       echo "Ошибка изменения параметров свойства";
	*    }
	*    else
	*    {
	*       // Обновим символьный код у значений свойства
	*       // (хранение избыточных данных для оптимизации работы)
	*       $db_order_props_tmp =
	*           CSaleOrderPropsValue::GetList(($b="NAME"),
	*                                         ($o="ASC"),
	*                                         Array("ORDER_PROPS_ID"=&gt;$ID));
	*       while ($ar_order_props_tmp = $db_order_props_tmp-&gt;Fetch())
	*       {
	*          CSaleOrderPropsValue::Update($ar_order_props_tmp["ID"],
	*                                       array("CODE" =&gt; "COMPLECT"));
	*       }
	*    }
	* }
	* else
	* {
	*    $ID = CSaleOrderProps::Add($arFields);
	*    if ($ID&lt;=0)
	*       echo "Ошибка добавления свойства";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__add.b64a5ac9.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		foreach (GetModuleEvents('sale', 'OnBeforeOrderPropsAdd', true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;

		if (! self::CheckFields('ADD', $arFields))
			return false;

		$newProperty = CSaleOrderPropsAdapter::convertOldToNew($arFields);
		$ID = OrderPropsTable::add(array_intersect_key($newProperty, CSaleOrderPropsAdapter::$allFields))->getId();

		foreach(GetModuleEvents('sale', 'OnOrderPropsAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры свойства с кодом ID заказа на значения из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код свойства заказа. </ht
	*
	* @param array $arFields  Ассоциативный массив новых значений параметров свойства заказа,
	* в котором ключами являются названия параметров свойства, а
	* значениями - значения этих параметров.<br> Допустимые ключи: <ul> <li>
	* <b>PERSON_TYPE_ID</b> - тип плательщика;</li> <li> <b>NAME</b> - название свойства (тип
	* плательщика зависит от сайта, а сайт - от языка; название должно
	* быть на соответствующем языке);</li> <li> <b>TYPE</b> - тип свойства.
	* Допустимые значения: <ul> <li> <b>CHECKBOX</b> - флаг;</li> <li> <b>TEXT</b> - строка
	* текста;</li> <li> <b>SELECT</b> - выпадающий список значений;</li> <li> <b>MULTISELECT</b>
	* - список со множественным выбором;</li> <li> <b>TEXTAREA</b> - многострочный
	* текст;</li> <li> <b>LOCATION</b> - местоположение;</li> <li> <b>RADIO</b> -
	* переключатель.</li> </ul> </li> <li> <b>REQUIED</b> - (Y/N) флаг обязательности;</li>
	* <li> <b>DEFAULT_VALUE</b> - значение по умолчанию;</li> <li> <b>SORT</b> - индекс
	* сортировки;</li> <li> <b>USER_PROPS</b> - флаг (Y/N) входит ли это свойство в
	* профиль покупателя;</li> <li> <b>IS_LOCATION</b> - флаг (Y/N) использовать ли
	* значение свойства как местоположение покупателя для расчёта
	* стоимости доставки (только для свойств типа LOCATION); </li> <li>
	* <b>PROPS_GROUP_ID</b> - код группы свойств;</li> <li> <b>SIZE1</b> - ширина поля (размер
	* по горизонтали);</li> <li> <b>SIZE2</b> - высота поля (размер по
	* вертикали);</li> <li> <b>DESCRIPTION</b> - описание свойства;</li> <li> <b>IS_EMAIL</b> -
	* флаг (Y/N) использовать ли значение свойства как E-Mail покупателя;</li>
	* <li> <b>IS_PROFILE_NAME</b> - флаг (Y/N) использовать ли значение свойства как
	* название профиля покупателя; </li> <li> <b>IS_PAYER</b> - флаг (Y/N)
	* использовать ли значение свойства как имя плательщика;</li> <li>
	* <b>IS_LOCATION4TAX</b> - флаг (Y/N) использовать ли значение свойства как
	* местоположение покупателя для расчёта налогов (только для
	* свойств типа <b>LOCATION</b>);</li> <li> <b>CODE</b> - символьный код свойства.</li>
	* </ul>
	*
	* @return int <p>Возвращается код измененного свойства заказа.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arFields = array(
	*    "PERSON_TYPE_ID" =&gt; 2,
	*    "NAME" =&gt; "Комплектация",
	*    "TYPE" =&gt; "RADIO",
	*    "REQUIED" =&gt; "Y",
	*    "DEFAULT_VALUE" =&gt; "F",
	*    "SORT" =&gt; 100,
	*    "CODE" =&gt; "COMPLECT",
	*    "USER_PROPS" =&gt; "N",
	*    "IS_LOCATION" =&gt; "N",
	*    "IS_LOCATION4TAX" =&gt; "N",
	*    "PROPS_GROUP_ID" =&gt; 1,
	*    "SIZE1" =&gt; 0,
	*    "SIZE2" =&gt; 0,
	*    "DESCRIPTION" =&gt; "",
	*    "IS_EMAIL" =&gt; "N",
	*    "IS_PROFILE_NAME" =&gt; "N",
	*    "IS_PAYER" =&gt; "N"
	* );
	*  
	* // Если установлен код свойства, то изменяем свойство с этим кодом,
	* // иначе добавляем новой свойство
	* if ($ID&gt;0)
	* {
	*    if (!CSaleOrderProps::Update($ID, $arFields))
	*    {
	*       echo "Ошибка изменения параметров свойства";
	*    }
	*    else
	*    {
	*       // Обновим символьный код у значений свойства
	*       // (хранение избыточных данных для оптимизации работы)
	*       $db_order_props_tmp = 
	*           CSaleOrderPropsValue::GetList(($b="NAME"), 
	*                                         ($o="ASC"),
	*                                         Array("ORDER_PROPS_ID"=&gt;$ID));
	*       while ($ar_order_props_tmp = $db_order_props_tmp-&gt;Fetch())
	*       {
	*          CSaleOrderPropsValue::Update($ar_order_props_tmp["ID"],
	*                                       array("CODE" =&gt; "COMPLECT"));
	*       }
	*    }
	* }
	* else
	* {
	*    $ID = CSaleOrderProps::Add($arFields);
	*    if ($ID&lt;=0)
	*       echo "Ошибка добавления свойства";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__update.6e284623.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		if (! $ID)
			return false;

		foreach (GetModuleEvents('sale', 'OnBeforeOrderPropsUpdate', true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields)) === false)
				return false;

		if (! self::CheckFields('UPDATE', $arFields, $ID))
			return false;

		$newProperty = CSaleOrderPropsAdapter::convertOldToNew($arFields + self::GetByID($ID));
		OrderPropsTable::update($ID, array_intersect_key($newProperty, CSaleOrderPropsAdapter::$allFields));

		foreach(GetModuleEvents('sale', 'OnOrderPropsUpdate', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	
	/**
	* <p>Метод удаляет свойство с кодом ID заказа. Существующие в базе значения этого свойства отвязываются от удаляемого свойства. Удаляются связанные значения из профиля покупателя. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код удаляемого свойства.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!CSaleOrderProps::Delete(12))
	*    echo "Ошибка удаления свойства";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorderprops/csaleorderprops__delete.75442e5e.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		if (! $ID)
			return false;

		foreach (GetModuleEvents('sale', 'OnBeforeOrderPropsDelete', true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
				return false;

		global $DB;

		$DB->Query("DELETE FROM b_sale_order_props_variant WHERE ORDER_PROPS_ID = ".$ID, true);
		$DB->Query("UPDATE b_sale_order_props_value SET ORDER_PROPS_ID = NULL WHERE ORDER_PROPS_ID = ".$ID, true);
		$DB->Query("DELETE FROM b_sale_user_props_value WHERE ORDER_PROPS_ID = ".$ID, true);
		$DB->Query("DELETE FROM b_sale_order_props_relation WHERE PROPERTY_ID = ".$ID, true);
		CSaleOrderUserProps::ClearEmpty();

		foreach(GetModuleEvents('sale', 'OnOrderPropsDelete', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		return $DB->Query("DELETE FROM b_sale_order_props WHERE ID = ".$ID, true);
	}

	public static function GetRealValue($propertyID, $propertyCode, $propertyType, $value, $lang = false)
	{
		$propertyID = IntVal($propertyID);
		$propertyCode = Trim($propertyCode);
		$propertyType = Trim($propertyType);

		if ($lang === false)
			$lang = LANGUAGE_ID;

		$arResult = array();

		$curKey = ((strlen($propertyCode) > 0) ? $propertyCode : $propertyID);

		if ($propertyType == "SELECT" || $propertyType == "RADIO")
		{
			$arValue = CSaleOrderPropsVariant::GetByValue($propertyID, $value);
			$arResult[$curKey] = $arValue["NAME"];
		}
		elseif ($propertyType == "MULTISELECT")
		{
			$curValue = "";

			if (!is_array($value))
				$value = explode(",", $value);

			for ($i = 0, $max = count($value); $i < $max; $i++)
			{
				if ($arValue1 = CSaleOrderPropsVariant::GetByValue($propertyID, $value[$i]))
				{
					if ($i > 0)
						$curValue .= ",";
					$curValue .= $arValue1["NAME"];
				}
			}

			$arResult[$curKey] = $curValue;
		}
		elseif ($propertyType == "LOCATION")
		{
			if(CSaleLocation::isLocationProMigrated())
			{
				$curValue = '';
				if(strlen($value))
				{
					$arValue = array();

					if(intval($value))
					{
						try
						{
							$locationStreetPropertyValue = '';
							$res = \Bitrix\Sale\Location\LocationTable::getPathToNode($value, array('select' => array('LNAME' => 'NAME.NAME', 'TYPE_ID'), 'filter' => array('=NAME.LANGUAGE_ID' => LANGUAGE_ID)));
							$types = \Bitrix\Sale\Location\Admin\TypeHelper::getTypeCodeIdMapCached();
							$path = array();
							while($item = $res->fetch())
							{
								// copy street to STREET property
								if($types['ID2CODE'][$item['TYPE_ID']] == 'STREET')
									$arResult[$curKey."_STREET"] = $item['LNAME'];

								if($types['ID2CODE'][$item['TYPE_ID']] == 'COUNTRY')
									$arValue["COUNTRY_NAME"] = $item['LNAME'];

								if($types['ID2CODE'][$item['TYPE_ID']] == 'REGION')
									$arValue["REGION_NAME"] = $item['LNAME'];

								if($types['ID2CODE'][$item['TYPE_ID']] == 'CITY')
									$arValue["CITY_NAME"] = $item['LNAME'];

								if($types['ID2CODE'][$item['TYPE_ID']] == 'VILLAGE')
									$arResult[$curKey."_VILLAGE"] = $item['LNAME'];

								$path[] = $item['LNAME'];
							}

							$curValue = implode(' - ', $path);
						}
						catch(\Bitrix\Main\SystemException $e)
						{
						}
					}
				}
			}
			else
			{
				$arValue = CSaleLocation::GetByID($value, $lang);
				$curValue = $arValue["COUNTRY_NAME"].((strlen($arValue["COUNTRY_NAME"])<=0 || strlen($arValue["REGION_NAME"])<=0) ? "" : " - ").$arValue["REGION_NAME"].((strlen($arValue["COUNTRY_NAME"])<=0 || strlen($arValue["CITY_NAME"])<=0) ? "" : " - ").$arValue["CITY_NAME"];
			}

			$arResult[$curKey] = $curValue;
			$arResult[$curKey."_COUNTRY"] = $arValue["COUNTRY_NAME"];
			$arResult[$curKey."_REGION"] = $arValue["REGION_NAME"];
			$arResult[$curKey."_CITY"] = $arValue["CITY_NAME"];
		}
		else
		{
			$arResult[$curKey] = $value;
		}

		return $arResult;
	}

	/*
	 * Get order property relations
	 *
	 * @param array $arFilter with keys: PROPERTY_ID, ENTITY_ID, ENTITY_TYPE
	 * @return dbResult
	 */
	public static function GetOrderPropsRelations($arFilter = array())
	{
		global $DB;

		$strSqlSearch = "";

		foreach ($arFilter as $key => $val)
		{
			$val = $DB->ForSql($val);

			switch(ToUpper($key))
			{
				case "PROPERTY_ID":
					$strSqlSearch .= " AND PROPERTY_ID = '".trim($val)."' ";
					break;
				case "ENTITY_ID":
					$strSqlSearch .= " AND ENTITY_ID = '".trim($val)."' ";
					break;
				case "ENTITY_TYPE":
					$strSqlSearch .= " AND ENTITY_TYPE = '".trim($val)."' ";
					break;
			}
		}

		$strSql =
			"SELECT * ".
			"FROM b_sale_order_props_relation ".
			"WHERE 1 = 1";

		if (strlen($strSqlSearch) > 0)
			$strSql .= " ".$strSqlSearch;

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	/*
	 * Update order property relations
	 *
	 * @param int $ID - property id
	 * @param array $arEntityIDs - array of IDs entities (payment or delivery systems)
	 * @param string $entityType - P/D (payment or delivery systems)
	 * @return dbResult
	 */
	public static function UpdateOrderPropsRelations($ID, $arEntityIDs, $entityType)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$strUpdate = "";
		$arFields = array();

		foreach ($arEntityIDs as &$id)
		{
			$id = $DB->ForSql($id);
		}
		unset($id);

		$entityType = $DB->ForSql($entityType, 1);

		$DB->Query("DELETE FROM b_sale_order_props_relation WHERE PROPERTY_ID = '".$DB->ForSql($ID)."' AND ENTITY_TYPE = '".$entityType."'");

		foreach ($arEntityIDs as $val)
		{
			$arTmp = array("ENTITY_ID" => $val, "ENTITY_TYPE" => $entityType);
			$arInsert = $DB->PrepareInsert("b_sale_order_props_relation", $arTmp);

			$strSql =
				"INSERT INTO b_sale_order_props_relation (PROPERTY_ID, ".$arInsert[0].") ".
				"VALUES('".$ID."', ".$arInsert[1].")";

			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return true;
	}

	public static function PrepareRelation4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		return false;
	}
}

/** @deprecated */
final class CSaleOrderPropsAdapter implements FetchAdapter
{
	private $select;

	private $fieldProxy = array();

	public function __construct(OrderQuery $query, array $select)
	{
		$this->select = $query->getSelectNamesAssoc() + array_flip($select);

		if (! $query->aggregated())
		{
			$query->addAliasSelect('TYPE');
			$query->addAliasSelect('SETTINGS');
			$query->addAliasSelect('MULTIPLE');
			$query->registerRuntimeField('PROPERTY_ID', new Entity\ExpressionField('PROPERTY_ID', 'DISTINCT(%s)', 'ID'));
			$sel = $query->getSelect();
			array_unshift($sel, 'PROPERTY_ID');
			$query->setSelect($sel);
		}
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

		$oldProperty = self::convertNewToOld($newProperty);
		$oldProperty['VALUE'] = self::getOldValue($newProperty['VALUE'], $newProperty['TYPE']);

		return array_intersect_key($oldProperty, $this->select);
	}

	static function getOldValue($value, $type)
	{
		if (is_array($value))
		{
			switch ($type)
			{
				case 'ENUM': $value = implode(',', $value); break;
				case 'FILE': $value = implode(', ', $value); break;
				default    : $value = reset($value);
			}
		}

		return $value;
	}

	static public function convertNewToOld(array $property)
	{
		if (isset($property['REQUIRED']) && !empty($property['REQUIRED']))
			$property['REQUIED'] = $property['REQUIRED'];

		$settings = $property['SETTINGS'];

		switch ($property['TYPE'])
		{
			case 'STRING':

				if ($settings['MULTILINE'] == 'Y')
				{
					$property['TYPE'] = 'TEXTAREA';
					$property['SIZE1'] = $settings['COLS'];
					$property['SIZE2'] = $settings['ROWS'];
				}
				else
				{
					$property['TYPE'] = 'TEXT';
					$property['SIZE1'] = $settings['SIZE'];
				}

				break;

			case 'Y/N':

				$property['TYPE'] = 'CHECKBOX';

				break;

			case 'DATE':

				$property['TYPE'] = 'DATE';

				break;

			case 'FILE':

				$property['TYPE'] = 'FILE';

				break;

			case 'ENUM':

				if ($property['MULTIPLE'] == 'Y')
				{
					$property['TYPE'] = 'MULTISELECT';
					$property['SIZE1'] = $settings['SIZE'];
				}
				elseif ($settings['MULTIELEMENT'] == 'Y')
				{
					$property['TYPE'] = 'RADIO';
				}
				else
				{
					$property['TYPE'] = 'SELECT';
					$property['SIZE1'] = $settings['SIZE'];
				}

				break;

			case 'LOCATION':

				$property['SIZE1'] = $settings['SIZE'];

				break;

			default: $property['TYPE'] = 'TEXT';
		}

		return $property;
	}

	// M I G R A T I O N

	static function convertOldToNew(array $property)
	{
		if (isset($property['REQUIED']) && !empty($property['REQUIED']))
			$property['REQUIRED'] = $property['REQUIED'];

		$size1 = intval($property['SIZE1']);
		$size2 = intval($property['SIZE2']);

		$settings = array();

		// TODO remove sale/include.php - $GLOBALS["SALE_FIELD_TYPES"]
		switch ($property['TYPE'])
		{
			case 'TEXT':

				$property['TYPE'] = 'STRING';

				if ($size1 > 0)
					$settings['SIZE'] = $size1;

				break;

			case 'TEXTAREA':

				$property['TYPE'] = 'STRING';

				$settings['MULTILINE'] = 'Y';

				if ($size1 > 0)
					$settings['COLS'] = $size1;

				if ($size2 > 0)
					$settings['ROWS'] = $size2;

				break;

			case 'CHECKBOX':

				$property['TYPE'] = 'Y/N';

				break;

			case 'RADIO':

				$property['TYPE'] = 'ENUM';

				$settings['MULTIELEMENT'] = 'Y';

				break;

			case 'SELECT':

				$property['TYPE'] = 'ENUM';

				if ($size1 > 0)
					$settings['SIZE'] = $size1;

				break;

			case 'MULTISELECT':

				$property['TYPE'] = 'ENUM';

				$property['MULTIPLE'] = 'Y';

				if ($size1 > 0)
					$settings['SIZE'] = $size1;

				break;

			case 'LOCATION':

				// ID came, should store CODE
				if (intval($property['DEFAULT_VALUE']))
				{
					$res = \Bitrix\Sale\Location\LocationTable::getList(array('filter' => array('=ID' => intval($property['DEFAULT_VALUE'])), 'select' => array('CODE')))->fetch();
					if(is_array($res) && (string) $res['CODE'] != '')
					{
						$property['DEFAULT_VALUE'] = $res['CODE'];
					}
				}

				if ($size1 > 0)
					$settings['SIZE'] = $size1;

				break;
		}

		$property['SETTINGS'] = $settings;

		return $property;
	}

	static $allFields = array(
		'PERSON_TYPE_ID'=>1, 'NAME'=>1, 'TYPE'=>1, 'REQUIRED'=>1, 'DEFAULT_VALUE'=>1, 'SORT'=>1, 'USER_PROPS'=>1,
		'IS_LOCATION'=>1, 'PROPS_GROUP_ID'=>1, 'DESCRIPTION'=>1, 'IS_EMAIL'=>1, 'IS_PROFILE_NAME'=>1, 'IS_PAYER'=>1,
		'IS_LOCATION4TAX'=>1, 'IS_FILTERED'=>1, 'CODE'=>1, 'IS_ZIP'=>1, 'IS_PHONE'=>1, 'ACTIVE'=>1, 'UTIL'=>1,
		'INPUT_FIELD_LOCATION'=>1, 'MULTIPLE'=>1, 'IS_ADDRESS'=>1, 'SETTINGS'=>1,
	);

	static function migrate()
	{
		$errors = '';
		$result = Application::getConnection()->query('SELECT * FROM b_sale_order_props');

		while ($oldProperty = $result->fetch())
		{
			$newProperty = self::convertOldToNew($oldProperty);
			$newProperty['IS_ADDRESS'] = 'N'; // fix oracle's mb default

			$update = OrderPropsTable::update($newProperty['ID'], array_intersect_key($newProperty, self::$allFields));

			if ($update->isSuccess())
			{
				//////CSaleOrderPropsValueAdapter::migrate($oldProperty);
			}
			else
			{
				$errors .= 'cannot update property: '.$oldProperty['ID']."\n".implode("\n", $update->getErrorMessages())."\n\n";
			}
		}

		if ($errors)
			throw new SystemException($errors, 0, __FILE__, __LINE__);
	}
}
