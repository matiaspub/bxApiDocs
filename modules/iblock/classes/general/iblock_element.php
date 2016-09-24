<?

/**
 * <b>_CIBElement</b> - вспомогательный класс для работы с объектами, которые возвращает <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>::<a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnextelement.php">GetNextElement</a>. Для получения различных характеристик элемента рекомендуется использовать именно этот класс, т.к. использование методов этого объекта позволяет более гибко и эффективно работать с элементами информационных блоков.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/index.php
 * @author Bitrix
 */
class _CIBElement
{
	var $fields;
	var $props=false;

	
	/**
	* <p>Возвращает массив значений полей приведенный в HTML безопасный вид. Также в полях <i>DETAIL_PAGE_URL</i> и <i>LIST_PAGE_URL</i> заменяются шаблоны вида #IBLOCK_ID# и т.п. на их реальные значения, в результате чего в этих полях будут ссылки на страницу детального просмотра и страницу списка элементов. Нестатический метод.</p>
	*
	*
	* @return array <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">полями элемента
	* информационного блока</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $res = CIBlockElement::GetByID($_GET["PID"]);
	* if($obRes = $res-&gt;GetNextElement())
	* {
	*   $ar_res = $obRes-&gt;GetFields();
	*   echo '&lt;a href="'.$ar_res['detail_page_url'].'"&gt;'.$ar_res['name'].'&lt;/a&gt;';
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/index.php">CIBlockResult</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockresult/getnext.php">GetNext()</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">Поля элемента
	* информационного блока </a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">GetList()</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/getfields.php
	* @author Bitrix
	*/
	public function GetFields()
	{
		return $this->fields;
	}

	/**
	 * @param bool|array $arOrder
	 * @param array $arFilter
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает значения свойств текущего элемента информационного блока. Нестатический метод.</p> <p></p> <div class="note"> <b>Примечание:</b> данный метод не работает, если в <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">CIBlockElement::GetList</a> в <b>arSelectFields</b> не указаны <i>ID</i> и <i>IBLOCK_ID</i>, а в <b>arFilter</b> не задан <i>IBLOCK_ID</i>. Должно быть, например, так: <pre class="syntax"> $dbEl = CIBlockElement::GetList( 		Array(),  		Array("IBLOCK_TYPE"=&gt;"catalog", "IBLOCK_ID"=&gt;11),  		false,  		false,  		array("ID" , "IBLOCK_ID", ......) ); </pre> </div>
	*
	*
	* @param array $arOrder = false Массив вида Array(<i>by1</i>=&gt;<i>order1</i>[, 			<i>by2</i>=&gt;<i>order2</i> [, ..]]), где <i>by</i> -
	* поле для сортировки, может принимать значения: 			          <ul> <li> <b>id</b> -
	* код свойства; 				</li>                     <li> <b>sort</b> - индекс сортировки; 				</li>  
	*                   <li> <b>name</b> - имя свойства; 				</li>                     <li> <span
	* style="font-weight: bold;">active</span> - активность свойства;</li>                     <li> <span
	* style="font-weight: bold;">value_id</span> - код значения свойства;</li>                     <li>
	* <span style="font-weight: bold;">enum_sort</span> - индекс сортировки варианта
	* списочного свойства; </li>          </ul> <i>order</i> - порядок сортировки,
	* может принимать значения: 				          <ul> <li> <b>asc</b> - по возрастанию;
	* 					</li>                     <li> <b>desc</b> - по убыванию; </li>          </ul>
	*
	* @param array $arFilter = Array() Массив вида array("фильтруемое поле"=&gt;"значения фильтра" [, ...])         
	* <br>        "фильтруемое поле" может принимать значения:          <br>       
	*     <i>NAME</i> - название свойства;          <br>            <i>ID</i> - код
	* свойства;          <br>            <i>ACTIVE</i> - активность свойства (Y|N), по
	* умолчанию выводятся только активные свойства, если необходимо
	* вывести все значения, то установите <i>ACTIVE</i> в пустое значение;       
	*   <br>            <i>SEARCHABLE</i> - участвует в поиске или нет (Y|N);          <br>       
	*     <i>PROPERTY_TYPE</i> - тип свойства;          <br>            <i>CODE</i> - символьный
	* код свойства;          <br>            <i>EMPTY</i> - пустота значения свойства
	* (Y|N). По умолчанию выводятся все свойства и имеющие непустые
	* значения и без значений.          <br>        Не обязательный параметр, по
	* умолчанию равен array().
	*
	* @return array <li>в качестве индексов массива "Символьный код свойства" (задается
	* в настройках информационного блока) или, если символьный код
	* свойства не указан, то уникальный числовой ID свойства. </li>
	*
	* <h4>See Also</h4> 
	* <br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/getproperties.php
	* @author Bitrix
	*/
	public function GetProperties($arOrder = false, $arFilter = array())
	{
		if($arOrder === false)
			$arOrder = array("sort"=>"asc","id"=>"asc","enum_sort"=>"asc","value_id"=>"asc");
		if (count($arFilter)==0 && is_array($this->props))
		{
			$arAllProps = array();
			/** @noinspection PhpWrongForeachArgumentTypeInspection */
			foreach($this->props as $arProp)
			{
				if(strlen(trim($arProp["CODE"]))>0)
					$PIND = $arProp["CODE"];
				else
					$PIND = $arProp["ID"];

				$arProp["VALUE"] = $this->fields["PROPERTY_".$arProp["ID"]];
				$arProp["DESCRIPTION"] = $this->fields["DESCRIPTION_".$arProp["ID"]];
				if($arProp["MULTIPLE"]=="N")
				{
					if($arProp["PROPERTY_TYPE"]=="L")
					{
						$arProp["VALUE_ENUM_ID"] = $val = $arProp["VALUE"];
						$arEnum = CIBlockPropertyEnum::GetByID($val);
						if($arEnum!==false)
						{
							$arProp["~VALUE"] = $arEnum["VALUE"];
							if(is_array($arProp["VALUE"]) || preg_match("/[;&<>\"]/", $arProp["VALUE"]))
								$arProp["VALUE"]  = htmlspecialcharsEx($arEnum["VALUE"]);
							else
								$arProp["VALUE"]  = $arEnum["VALUE"];
							$arProp["VALUE_ENUM"] = $arProp["VALUE"];
							$arProp["VALUE_XML_ID"]  = htmlspecialcharsEx($arEnum["XML_ID"]);
							$arProp["VALUE_SORT"] = $arEnum["SORT"];
						}
						else
						{
							$arProp["~VALUE"] = "";
							$arProp["VALUE"]  = "";
						}
					}
					elseif(is_array($arProp["VALUE"]) || strlen($arProp["VALUE"]))
					{
						if($arProp["PROPERTY_TYPE"]=="N")
							$arProp["VALUE"] = htmlspecialcharsEx(CIBlock::NumberFormat($arProp["VALUE"]));
						$arProp["~VALUE"] = $this->fields["~PROPERTY_".$arProp["ID"]];
						$arProp["~DESCRIPTION"] = $this->fields["~DESCRIPTION_".$arProp["ID"]];
					}
					else
					{
						$arProp["VALUE"] = $arProp["~VALUE"] = "";
						$arProp["DESCRIPTION"] = $arProp["~DESCRIPTION"] = "";
					}
				}
				else
				{
					$arList = $arProp["VALUE"];
					$arListTilda = $this->fields["~PROPERTY_".$arProp["ID"]];
					if($arProp["PROPERTY_TYPE"]=="L")
					{
						$arProp["~VALUE"] = $arProp["VALUE"] = $arProp["VALUE_ENUM_ID"] = false;
						$arProp["VALUE_XML_ID"] = false;
						foreach($arList as $key=>$val)
						{
							if(strlen($val)>0)
							{
								$arEnum = CIBlockPropertyEnum::GetByID($key);
								if($arEnum!==false)
								{
									$xml_id = htmlspecialcharsEx($arEnum["XML_ID"]);
									$sort = $arEnum["SORT"];
								}
								else
								{
									$xml_id = false;
									$sort = false;
								}

								if(is_array($arProp["VALUE"]))
								{

									$arProp["VALUE_ENUM_ID"][] = $key;
									$arProp["~VALUE"][] = $val;
									if(is_array($val) || preg_match("/[;&<>\"]/", $val))
										$arProp["VALUE"][] = htmlspecialcharsEx($val);
									else
										$arProp["VALUE"][] = $val;
									$arProp["VALUE_XML_ID"][] = $xml_id;
									$arProp["VALUE_SORT"][] = $sort;
								}
								else
								{
									$arProp["VALUE_ENUM_ID"] = array($key);
									$arProp["~VALUE"] = array($val);
									if(is_array($val) || preg_match("/[;&<>\"]/", $val))
										$arProp["VALUE"] = array(htmlspecialcharsEx($val));
									else
										$arProp["VALUE"] = array($val);
									$arProp["VALUE_XML_ID"] = array($xml_id);
									$arProp["VALUE_SORT"] = array($sort);
								}
							}
						}
						$arProp["VALUE_ENUM"] = $arProp["VALUE"];
					}
					else
					{
						$arDesc = $arProp["DESCRIPTION"];
						$arDescTilda = $this->fields["~DESCRIPTION_".$arProp["ID"]];

						$arProp["~VALUE"] = $arProp["VALUE"] = false;
						$arProp["~DESCRIPTION"] = $arProp["DESCRIPTION"] = false;
						foreach($arList as $key=>$val)
						{
							if(is_array($val) || strlen($val)>0)
							{
								if(is_array($arProp["VALUE"]))
								{
									$arProp["~VALUE"][] = $arListTilda[$key];
									if($arProp["PROPERTY_TYPE"]=="N")
										$val = htmlspecialcharsEx(CIBlock::NumberFormat($val));
									$arProp["VALUE"][] = $val;
									$arProp["~DESCRIPTION"][] = $arDescTilda[$key];
									$arProp["DESCRIPTION"][] = $arDesc[$key];
								}
								else
								{
									$arProp["~VALUE"] = array($arListTilda[$key]);
									if($arProp["PROPERTY_TYPE"]=="N")
										$val = htmlspecialcharsEx(CIBlock::NumberFormat($val));
									$arProp["VALUE"] = array($val);
									$arProp["~DESCRIPTION"] = array($arDescTilda[$key]);
									$arProp["DESCRIPTION"] = array($arDesc[$key]);
								}
							}
						}
					}
				}
				$arAllProps[$PIND]=$arProp;
			}
			return $arAllProps;
		}

		if(array_key_exists("ID", $arFilter) && is_string($arFilter['ID']))
		{
			if (!is_numeric(substr($arFilter["ID"], 0, 1)))
			{
				$arFilter["CODE"] = $arFilter["ID"];
				unset($arFilter["ID"]);
			}
		}

		if(!array_key_exists("ACTIVE", $arFilter))
			$arFilter["ACTIVE"]="Y";

		$props = CIBlockElement::GetProperty($this->fields["IBLOCK_ID"], $this->fields["ID"], $arOrder, $arFilter);

		$arAllProps = Array();
		while($arProp = $props->Fetch())
		{
			if(strlen(trim($arProp["CODE"]))>0)
				$PIND = $arProp["CODE"];
			else
				$PIND = $arProp["ID"];

			if($arProp["PROPERTY_TYPE"]=="L")
			{
				$arProp["VALUE_ENUM_ID"] = $arProp["VALUE"];
				$arProp["VALUE"] = $arProp["VALUE_ENUM"];
			}

			if(is_array($arProp["VALUE"]) || (strlen($arProp["VALUE"]) > 0))
			{
				$arProp["~VALUE"] = $arProp["VALUE"];
				if(is_array($arProp["VALUE"]) || preg_match("/[;&<>\"]/", $arProp["VALUE"]))
					$arProp["VALUE"] = htmlspecialcharsEx($arProp["VALUE"]);
				$arProp["~DESCRIPTION"] = $arProp["DESCRIPTION"];
				if(preg_match("/[;&<>\"]/", $arProp["DESCRIPTION"]))
					$arProp["DESCRIPTION"] = htmlspecialcharsEx($arProp["DESCRIPTION"]);
			}
			else
			{
				$arProp["VALUE"] = $arProp["~VALUE"] = "";
				$arProp["DESCRIPTION"] = $arProp["~DESCRIPTION"] = "";
			}

			if($arProp["MULTIPLE"]=="Y")
			{
				if(array_key_exists($PIND, $arAllProps))
				{
					$arTemp = &$arAllProps[$PIND];
					if($arProp["VALUE"]!=="")
					{
						if(is_array($arTemp["VALUE"]))
						{
							$arTemp["VALUE"][] = $arProp["VALUE"];
							$arTemp["~VALUE"][] = $arProp["~VALUE"];
							$arTemp["DESCRIPTION"][] = $arProp["DESCRIPTION"];
							$arTemp["~DESCRIPTION"][] = $arProp["~DESCRIPTION"];
							$arTemp["PROPERTY_VALUE_ID"][] = $arProp["PROPERTY_VALUE_ID"];
							if($arProp["PROPERTY_TYPE"]=="L")
							{
								$arTemp["VALUE_ENUM_ID"][] = $arProp["VALUE_ENUM_ID"];
								$arTemp["VALUE_ENUM"][] = $arProp["VALUE_ENUM"];
								$arTemp["VALUE_XML_ID"][] = $arProp["VALUE_XML_ID"];
								//$arTemp["VALUE_SORT"][] = $arProp["VALUE_SORT"];
							}
						}
						else
						{
							$arTemp["VALUE"] = array($arProp["VALUE"]);
							$arTemp["~VALUE"] = array($arProp["~VALUE"]);
							$arTemp["DESCRIPTION"] = array($arProp["DESCRIPTION"]);
							$arTemp["~DESCRIPTION"] = array($arProp["~DESCRIPTION"]);
							$arTemp["PROPERTY_VALUE_ID"] = array($arProp["PROPERTY_VALUE_ID"]);
							if($arProp["PROPERTY_TYPE"]=="L")
							{
								$arTemp["VALUE_ENUM_ID"] = array($arProp["VALUE_ENUM_ID"]);
								$arTemp["VALUE_ENUM"] = array($arProp["VALUE_ENUM"]);
								$arTemp["VALUE_XML_ID"] = array($arProp["VALUE_XML_ID"]);
								$arTemp["VALUE_SORT"] = array($arProp["VALUE_SORT"]);
							}
						}
					}
				}
				else
				{
					$arProp["~NAME"] = $arProp["NAME"];
					if(preg_match("/[;&<>\"]/", $arProp["NAME"]))
						$arProp["NAME"] = htmlspecialcharsEx($arProp["NAME"]);
					$arProp["~DEFAULT_VALUE"] = $arProp["DEFAULT_VALUE"];
					if(is_array($arProp["DEFAULT_VALUE"]) || preg_match("/[;&<>\"]/", $arProp["DEFAULT_VALUE"]))
						$arProp["DEFAULT_VALUE"] = htmlspecialcharsEx($arProp["DEFAULT_VALUE"]);
					if($arProp["VALUE"]!=="")
					{
						$arProp["VALUE"] = array($arProp["VALUE"]);
						$arProp["~VALUE"] = array($arProp["~VALUE"]);
						$arProp["DESCRIPTION"] = array($arProp["DESCRIPTION"]);
						$arProp["~DESCRIPTION"] = array($arProp["~DESCRIPTION"]);
						$arProp["PROPERTY_VALUE_ID"] = array($arProp["PROPERTY_VALUE_ID"]);
						if($arProp["PROPERTY_TYPE"]=="L")
						{
							$arProp["VALUE_ENUM_ID"] = array($arProp["VALUE_ENUM_ID"]);
							$arProp["VALUE_ENUM"] = array($arProp["VALUE_ENUM"]);
							$arProp["VALUE_XML_ID"] = array($arProp["VALUE_XML_ID"]);
							$arProp["VALUE_SORT"] = array($arProp["VALUE_SORT"]);
						}
					}
					else
					{
						$arProp["VALUE"] = false;
						$arProp["~VALUE"] = false;
						$arProp["DESCRIPTION"] = false;
						$arProp["~DESCRIPTION"] = false;
						$arProp["PROPERTY_VALUE_ID"] = false;
						if($arProp["PROPERTY_TYPE"]=="L")
						{
							$arProp["VALUE_ENUM_ID"] = false;
							$arProp["VALUE_ENUM"] = false;
							$arProp["VALUE_XML_ID"] = false;
							$arProp["VALUE_SORT"] = false;
						}
					}
					$arAllProps[$PIND] = $arProp;
				}
			}
			else
			{
				$arProp["~NAME"] = $arProp["NAME"];
				if(preg_match("/[;&<>\"]/", $arProp["NAME"]))
					$arProp["NAME"] = htmlspecialcharsEx($arProp["NAME"]);
				$arProp["~DEFAULT_VALUE"] = $arProp["DEFAULT_VALUE"];
				if(is_array($arProp["DEFAULT_VALUE"]) || preg_match("/[;&<>\"]/", $arProp["DEFAULT_VALUE"]))
					$arProp["DEFAULT_VALUE"] = htmlspecialcharsEx($arProp["DEFAULT_VALUE"]);
				$arAllProps[$PIND] = $arProp;
			}
		}

		return $arAllProps;
	}

	
	/**
	* <p>Метод возвращает параметры свойства <i>ID</i> и его значения для текущего элемента информационного блока. Нестатический метод.</p>
	*
	*
	* @param mixed $mixedID  Числовой или символьный код свойства.
	*
	* @return array <a href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#fproperty">полей
	* свойства</a><br><i>VALUE</i><br><i>VALUE_ENUM_ID</i><br><i>DESCRIPTION</i><br><i>PROPERTY_VALUE_ID</i><br><p></p><div
	* class="note"> <b>Примечание:</b> если <b>GetProperty</b> применяется к результату
	* работы <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">CIBlockElement::GetList</a>, то в
	* <b>arSelectFields</b> необходимо <b>обязательно</b> указать <i>IBLOCK_ID</i>, иначе
	* результат будет пустым.</div>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* $res = CIBlockElement::GetByID($_GET["PID"]);
	* if($obRes = $res-&gt;GetNextElement())
	* {
	*   $ar_res = $obRes-&gt;GetProperty("PHOTOS");
	*   print_r($ar_res);
	* }
	* ?&gt;
	* <b>Вывод примера:
	* </b>
	* Array
	* (
	*     [ID] =&gt; 388
	*     [TIMESTAMP_X] =&gt; 20050119162457
	*     [IBLOCK_ID] =&gt; 11
	*     [NAME] =&gt; Photos with description
	*     [ACTIVE] =&gt; Y
	*     [SORT] =&gt; 500
	*     [CODE] =&gt; 
	*     [DEFAULT_VALUE] =&gt; 
	*     [PROPERTY_TYPE] =&gt; F
	*     [ROW_COUNT] =&gt; 1
	*     [COL_COUNT] =&gt; 30
	*     [LIST_TYPE] =&gt; L
	*     [MULTIPLE] =&gt; Y
	*     [XML_ID] =&gt; 
	*     [FILE_TYPE] =&gt; 
	*     [MULTIPLE_CNT] =&gt; 5
	*     [TMP_ID] =&gt; 
	*     [WITH_DESCRIPTION] =&gt; Y
	*     [LINK_IBLOCK_ID] =&gt; 0
	*     [VALUE_TYPE] =&gt; text
	*     [VALUE_ENUM] =&gt; 
	*     [VALUE] =&gt; Array
	*         (
	*             [0] =&gt; 2311
	*         )
	* 
	*     [~VALUE] =&gt; Array
	*         (
	*             [0] =&gt; 2311
	*         )
	* 
	*     [DESCRIPTION] =&gt; Array
	*         (
	*             [0] =&gt; Descr
	*         )
	* 
	*     [~DESCRIPTION] =&gt; Array
	*         (
	*             [0] =&gt; Descr
	*         )
	* 
	*     [PROPERTY_VALUE_ID] =&gt; Array
	*         (
	*             [0] =&gt; 53865
	*         )
	* 
	*     [~NAME] =&gt; Photos with description
	*     [~DEFAULT_VALUE] =&gt; 
	* )
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/index.php">CIBlockElement</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getproperty.php">GetProperty()</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/index.php">_CIBElement</a>::<a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/getproperties.php">GetProperties()</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/getproperty.php
	* @author Bitrix
	*/
	public function GetProperty($ID)
	{
		$res = $this->GetProperties(array(), array("ID"=>$ID));
		list(, $res) = each($res);
		return $res;
	}

	
	/**
	* <p>Возвращает группы, которым принадлежит текущий элемент и значения свойств типа "привязка к разделам" заданные для данного элемента. Нестатический метод.   <br></p>
	*
	*
	* @return CDBResult <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?<br>$res = CIBlockElement::GetByID($_GET["PID"]);<br>if($obRes = $res-&gt;GetNextElement())<br>{<br>  $ar_res = $obRes-&gt;GetGroups();<br>  print_r($ar_res);<br>}<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li>    <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getelementgroups.php">CIBlockElement::GetElementGroups</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/_cibelement/getgroups.php
	* @author Bitrix
	*/
	public function GetGroups()
	{
		return CIBlockElement::GetElementGroups($this->fields["ID"]);
	}
}