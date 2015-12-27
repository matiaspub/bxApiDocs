<?php

IncludeModuleLangFile(__FILE__);


/**
 * <b>CWiki</b> - Класс для работы c Wiki. </h
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/index.php
 * @author Bitrix
 */
class CWiki
{
	/**
	 *
	 *
	 * @var CIBlockElement
	 */
	var $cIB_E = null;
	const PAGE_UPDATED_CACHE_ID = "WIKI_PAGE_UPDATED_";
	const GET_BY_NAME_CACHE_ID = "WIKI_BY_NAME_CACHE_ID_";
	const CWIKI_CACHE_TTL = 36000000;

	public function __construct()
	{
		$this->cIB_E = new CIBlockElement();
	}

	
	/**
	* <p>Метод добавляет новую Wiki-страницу. Динамичный метод.</p>
	*
	*
	* @param array $arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/add.php">CIBlock::Add</a>
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Добавим Wiki-страницу в инфо.блок с идентификатором 2
	* 
	* $arFields = array(
	* 	'ACTIVE' =&gt; 'Y',
	* 	'IBLOCK_ID' =&gt; 2,
	* 	'DETAIL_TEXT_TYPE' =&gt; 'html',
	* 	'DETAIL_TEXT' =&gt; '&lt;br/&gt;&lt;h2&gt;Тестовая страница&lt;/h2&gt;&lt;br/&gt;',
	* 	'NAME' =&gt; 'Тестовая страница'
	* );
	* $CWiki = new CWiki();
	* if (!($ID = $CWiki-&gt;Add($arFields)))
	* 	echo 'Ошибка. Страница не создана.';
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Update.php">CWiki::Update</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Add.php
	* @author Bitrix
	*/
	public function Add($arFields)
	{
		$arFields['XML_ID'] = $arFields['NAME'];

		$arCats = array();
		$CWikiParser = new CWikiParser();
		$arFields['DETAIL_TEXT'] = $CWikiParser->parseBeforeSave($arFields['DETAIL_TEXT'], $arCats, $arFields['NAME_TEMPLATE']);
		if (CWikiSocnet::IsSocNet())
			$arFields['IBLOCK_SECTION_ID'] = CWikiSocnet::$iCatId;

		//add item
		$ID = $this->cIB_E->Add($arFields);

		$this->CleanCache($ID, $arFields['NAME'],$arFields['IBLOCK_ID']);

		//serve category / bindings
		$this->UpdateCategory($ID, $arFields['IBLOCK_ID'], $arCats);

		//$this->UpdateHistory($ID, $arFields['IBLOCK_ID']);

		return $ID;
	}

	
	/**
	* <p>Метод изменяет Wiki-страницу, добавляет запись в историю и обслуживает привязки к категориям. Динамичный метод.</p>
	*
	*
	* @param int $ID  Идентификатор Wiki-страницы
	*
	* @param array $arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a>
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Обновим Wiki-страницу с идентификатором 13 в инфо.блоке с идентификатором 2
	* $ID = 13;
	* $arFields = array(
	* 	'IBLOCK_ID' =&gt; 2,
	* 	'DETAIL_TEXT_TYPE' =&gt; 'html',
	* 	'DETAIL_TEXT' =&gt; '&lt;br/&gt;&lt;h2&gt;Измененная тестовая страница&lt;/h2&gt;&lt;br/&gt;'	
	* );
	* $CWiki = new CWiki();
	* if (!$CWiki-&gt;Update($ID, $arFields))
	* 	echo 'Ошибка. Страница не изменена.';<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Add.php">CWiki::Add</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/UpdateCategory.php">CWiki::UpdateCategory</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/UpdateHistory.php">CWiki::UpdateHistory</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Update.php
	* @author Bitrix
	*/
	public function Update($ID, $arFields)
	{
		$arCats = array();
		$CWikiParser = new CWikiParser();
		$arFields['DETAIL_TEXT'] = $CWikiParser->parseBeforeSave($arFields['DETAIL_TEXT'], $arCats, $arFields['NAME_TEMPLATE']);

		$this->CleanCache($ID, $arFields['NAME'], $arFields['IBLOCK_ID']);
		//save item
		$this->cIB_E->Update($ID, $arFields);

		//serve category / bindings
		$arCats = str_replace("/", "-", $arCats);	//http://jabber.bx/view.php?id=28447
		$this->UpdateCategory($ID, $arFields['IBLOCK_ID'], $arCats);

		$modifyComment = isset($arFields["MODIFY_COMMENT"]) ? $arFields["MODIFY_COMMENT"] : "";

		$this->UpdateHistory($ID, $arFields['IBLOCK_ID'], $modifyComment);

		return true;
	}

	
	/**
	* <p>Метод восстанавливает Wiki-страницу из истории. Динамичный метод.</p>
	*
	*
	* @param int $HISTORY_ID  Идентификатор записи истории
	*
	* @param int $ID  Идентификатор Wiki-страницы
	*
	* @param int $IBLOCK_ID  Идентификатор Инфо.блока
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Восстановим страницу с идентификатором 13 из инфо.блока с идентификатором 2 по записи в истории 26
	* $HISTORY_ID = 26;
	* $ID = 13;
	* $IBLOCK_ID = 2;
	* 
	* $CWiki = new CWiki();
	* if (!$CWiki-&gt;Recover($HISTORY_ID, $ID, $IBLOCK_ID))
	* 	echo 'Ошибка. Страница не восстановлена.';<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/UpdateHistory.php">CWiki::UpdateHistory</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Recover.php
	* @author Bitrix
	*/
	public function Recover($HISTORY_ID, $ID, $IBLOCK_ID)
	{
		$this->CleanCacheById($ID, $IBLOCK_ID);

		$rIBlock = CIBlock::getList(Array(), array('ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N'));
		$arIBlock = $rIBlock->GetNext();
		if ($arIBlock['BIZPROC'] == 'Y' && CModule::IncludeModule('bizproc'))
		{
			$arErrorsTmp = array();
			$arHistoryResult = CBPDocument::GetDocumentFromHistory($HISTORY_ID, $arErrorsTmp);
			$modifyComment = GetMessage('WIKI_RECOVER_COMMENT')." ".$arHistoryResult["MODIFIED"];
			if (CBPHistoryService::RecoverDocumentFromHistory($HISTORY_ID))
			{
				if ($this->UpdateHistory($ID, $IBLOCK_ID, $modifyComment))
					return true;
				else
					return false;
			}
			else
				return false;
		}
		else
			return false;
	}

	
	/**
	* <p>Метод создает запись в истории на основе Wiki-страницы. Динамичный метод.</p>
	*
	*
	* @param mixed $ID  Идентификатор Wiki-страницы
	*
	* @param mixed $IBLOCK_ID  Идентификатор Инфоблока
	*
	* @param mixed $modifyComment = false Необязательный.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Создадим новую запись в истории страницы с идентификатором 13 инфо.блока с идентификатором 2
	* $ID = 13;
	* $IBLOCK_ID = 2;
	* 
	* $CWiki = new CWiki();
	* if (!$CWiki-&gt;UpdateHistory($ID, $IBLOCK_ID))
	* 	echo 'Ошибка. Запись истории не создана.';<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Recover.php">CWiki::Recover</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/UpdateHistory.php
	* @author Bitrix
	*/
	public static function UpdateHistory($ID, $IBLOCK_ID, $modifyComment=false)
	{
		global $USER;

		$rIBlock = CIBlock::getList(Array(), array('ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N'));
		$arIBlock = $rIBlock->GetNext();

		// add changes history
		if ($arIBlock['BIZPROC'] == 'Y' && CModule::IncludeModule('bizproc'))
		{
			$cRuntime = CBPRuntime::GetRuntime();
			$cRuntime->StartRuntime();
			$documentService = $cRuntime->GetService('DocumentService');

			$historyIndex = CBPHistoryService::Add(
				array(
					'DOCUMENT_ID' => array('iblock', 'CWikiDocument', $ID),
					'NAME' => 'New',
					'DOCUMENT' => null,
					'USER_ID' => $USER->GetID()
				)
			);

			$arDocument = $documentService->GetDocumentForHistory(array('iblock', 'CWikiDocument', $ID), $historyIndex);
			$arDocument["MODIFY_COMMENT"] = $modifyComment ? $modifyComment : '';

			if (is_array($arDocument))
			{
				CBPHistoryService::Update(
					$historyIndex,
					array(
						'NAME' => $arDocument['NAME'],
						'DOCUMENT' => $arDocument,
					)
				);
			}
			return true;
		}
		return false;
	}

	
	/**
	* <p>Метод обновляет привязки Wiki-страницы к категориям. Динамичный метод.</p>
	*
	*
	* @param int $ID  Идентификатор Wiki-страницы
	*
	* @param int $IBLOCK_ID  Идентификатор Инфо.блока
	*
	* @param array $arCats  Массив наименований категорий страницы
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Обновим категории страницы с идентификатором 13 из инфо.блока с идентификатором 2
	* $arCats = array('Категория 1', 'Категория 2');
	* $ID = 13;
	* $IBLOCK_ID = 2;
	* 
	* $CWiki = new CWiki();
	* $CWiki-&gt;UpdateCategory($ID, $IBLOCK_ID, $arCats);<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetCategory.php">CWiki::GetCategory</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/UpdateCategory.php
	* @author Bitrix
	*/
	public function UpdateCategory($ID, $IBLOCK_ID, $arCats)
	{

		$this->CleanCacheById($ID, $IBLOCK_ID);

		$arFilter = array(
			'IBLOCK_ID' => $IBLOCK_ID,
			'CHECK_PERMISSIONS' => 'N'
		);
		$arElement = self::GetElementById($ID, $arFilter);
		$bCategoryPage = false;
		$sCatName = '';
		$arCatsID = array();
		if (CWikiUtils::IsCategoryPage($arElement['~NAME'], $sCatName))
			$bCategoryPage = true;

		if ($bCategoryPage)
		{
			// get current category
			$arFilter =  array('NAME' => $sCatName, 'IBLOCK_ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N');
			if (CWikiSocnet::IsSocNet())
			{
				$arFilter['>LEFT_BORDER'] = CWikiSocnet::$iCatLeftBorder;
				$arFilter['<RIGHT_BORDER'] = CWikiSocnet::$iCatRightBorder;
			}
			$rsCurCats = CIBlockSection::GetList(array(), $arFilter);
			$arCurCat = $rsCurCats->GetNext();

			if (empty($arCurCat))
			{
				$CIB_S = new CIBlockSection();
				$_arFields = array();
				$_arFields['IBLOCK_ID'] = $IBLOCK_ID;
				$_arFields['ACTIVE'] = 'Y';
				$_arFields['NAME'] = $sCatName;
				$_arFields['XML_ID'] = $sCatName;
				if (CWikiSocnet::IsSocNet())
					$_arFields['IBLOCK_SECTION_ID'] = CWikiSocnet::$iCatId;
				$iCurCatID = $CIB_S->Add($_arFields);
				if ($iCurCatID != false)
					$arCatsID[] = $iCurCatID;
			}
			else
			{
				$iCurCatID = $arCurCat['ID'];
				$arCatsID[] = $arCurCat['ID'];
			}

			// Page bind only to this category
			CIBlockElement::SetElementSection($ID, $arCatsID);

			$CIB_S = new CIBlockSection();
			if (!empty($arCats))
			{
				// Nova create a category if it still has no
				$arFilter =  array('NAME' => $arCats[0], 'IBLOCK_ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N');
				if (CWikiSocnet::IsSocNet())
				{
					$arFilter['>LEFT_BORDER'] = CWikiSocnet::$iCatLeftBorder;
					$arFilter['<RIGHT_BORDER'] = CWikiSocnet::$iCatRightBorder;
				}
				$rsCats = CIBlockSection::GetList(array(), $arFilter);
				$arCat = $rsCats->GetNext();

				if (empty($arCat))
				{
					$_arFields = array();
					$_arFields['IBLOCK_ID'] = $IBLOCK_ID;
					$_arFields['ACTIVE'] = 'Y';
					$_arFields['NAME'] = CWikiUtils::htmlspecialcharsback($arCats[0]);
					$_arFields['XML_ID'] = CWikiUtils::htmlspecialcharsback($arCats[0]);
					$_arFields['CHECK_PERMISSIONS'] = 'N';
					if (CWikiSocnet::IsSocNet())
						$_arFields['IBLOCK_SECTION_ID'] = CWikiSocnet::$iCatId;

					$iCatID = $CIB_S->Add($_arFields);
				}
				else
					$iCatID = $arCat['ID'];

				$_arFields = array();
				$_arFields['IBLOCK_ID'] = $IBLOCK_ID;
				$_arFields['ACTIVE'] = 'Y';
				$_arFields['IBLOCK_SECTION_ID'] = $iCatID;
				// current category doing this subcategory
				$CIB_S->Update($iCurCatID, $_arFields);
			}
			else
			{
				$_arFields = array();
				$_arFields['IBLOCK_ID'] = $IBLOCK_ID;
				$_arFields['ACTIVE'] = 'Y';
				$_arFields['IBLOCK_SECTION_ID'] = 0;
				if (CWikiSocnet::IsSocNet())
					$_arFields['IBLOCK_SECTION_ID'] = CWikiSocnet::$iCatId;
				// bind to the root category
				$CIB_S->Update($iCurCatID, $_arFields);
			}
		}
		else  //not category
		{
			$arExistsCatsId = array();
			$arDelCatId = array();
			$rsSect = CIBlockElement::GetElementGroups($ID, false);
			//$arResult['SECTIONS'] = array(); //erase candidat
			while($arSect = $rsSect->GetNext())
				$arExistsCatsId[] = $arSect['ID'];

			if (!empty($arCats))
			{
				$arFilter =  array('NAME' => $arCats, 'IBLOCK_ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N');
				if (CWikiSocnet::IsSocNet())
				{
					$arFilter['>LEFT_BORDER'] = CWikiSocnet::$iCatLeftBorder;
					$arFilter['<RIGHT_BORDER'] = CWikiSocnet::$iCatRightBorder;
				}
				$rsCats = CIBlockSection::GetList(array(), $arFilter);
				while($arCat = $rsCats->GetNext())
				{
					$arExiststInBlockCats[] = $arCat['~NAME'];
					$arCatsID[] = $arCat['ID'];
				}

				$CIB_S = new CIBlockSection();
				foreach ($arCats as $sCatName)
				{
					if (!in_array($sCatName, $arExiststInBlockCats))
					{
						$_arFields = array();
						$_arFields['IBLOCK_ID'] = $IBLOCK_ID;
						$_arFields['ACTIVE'] = 'Y';
						$_arFields['NAME'] = CWikiUtils::htmlspecialcharsback($sCatName, false);
						$_arFields['XML_ID'] = CWikiUtils::htmlspecialcharsback($sCatName, false);
						$_arFields['CHECK_PERMISSIONS'] = 'N';
						if (CWikiSocnet::IsSocNet())
							$_arFields['IBLOCK_SECTION_ID'] = CWikiSocnet::$iCatId;
						$iCatID = $CIB_S->Add($_arFields);
						if ($iCatID != false)
							$arCatsID[] = $iCatID;
					}
				}

				//bind to the item
				if (!empty($arCatsID))
				{
					//if (CWikiSocnet::IsSocNet())
					//	$arCatsID[] = CWikiSocnet::$iCatId;
					CIBlockElement::SetElementSection($ID, $arCatsID);
				}
			}
			else
			{
				$arCatsID = array();
				if (CWikiSocnet::IsSocNet())
					$arCatsID = CWikiSocnet::$iCatId;
				CIBlockElement::SetElementSection($ID, $arCatsID);
			}

			if (is_array($arCatsID))
				$arDelCatId = array_diff($arExistsCatsId, $arCatsID);
			if (!empty($arDelCatId))
			{
				foreach ($arDelCatId as $_iCatId)
				{
					$obRes = CIBlockSection::GetList(array(), array('ID' => $_iCatId, 'IBLOCK_ID' => $IBLOCK_ID), true);
					$arCatProp = $obRes->Fetch();
					if ($arCatProp['ELEMENT_CNT'] == 0)
						CIBlockSection::Delete($_iCatId);
				}
			}
		}
	}

	//TODO: Delete (check) all comments
	
	/**
	* <p>Метод удаляет Wiki-страницу. Динамичный метод.</p>
	*
	*
	* @param int $ID  Идентификатор Wiki-страницы
	*
	* @param int $IBLOCK_ID  Идентификатор Инфо.блока. <br> До версии 10.0.0 назывался <b>BLOCK_ID</b>.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Удалим Wiki-страницу с идентификатором 13 в инфо.блоке с идентификатором 2<br>$ID = 13;<br>$IBLOCK_ID = 2;<br><br>$CWiki = new CWiki();<br>if (!$CWiki-&gt;Delete($ID, $IBLOCK_ID))<br>	echo 'Ошибка. Страница не удалена.';<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Delete.php
	* @author Bitrix
	*/
	public function Delete($ID, $IBLOCK_ID)
	{
		$rIBlock = CIBlock::getList(Array(), array('ID' => $IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N'));
		$arIBlock = $rIBlock->GetNext();

		// erase the history of changes
		if ($arIBlock['BIZPROC'] == 'Y' && CModule::IncludeModule('bizproc'))
		{
			$historyService = new CBPHistoryService();
			$historyService->DeleteHistoryByDocument(array('iblock', 'CWikiDocument', $ID));
		}

		$this->CleanCacheById($ID,$IBLOCK_ID);

		// delete item
		$bResult = $this->cIB_E->Delete($ID);

		return $bResult;
	}

	
	/**
	* <p>Метод привязывает изображение к Wiki-странице. Динамичный метод.</p>
	*
	*
	* @param int $ID  Идентификатор Wiki-страницы. До версии 10.0.0 назывался <b>ELEMENT_ID</b>.
	*
	* @param int $IBLOCK_ID  Идентификатор Инфо.блока
	*
	* @param array $arImage  Массив свойств изображения
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // привяжем изображение к странице с идентификатором 13 из инфо.блока с идентификатором 2
	* $ID = 13;
	* $IBLOCK_ID = 2;
	* 
	* if (CFile::IsImage($_FILES['FILE_ID']['name']))
	* {
	*     $CWiki = new CWiki();
	*     $CWiki-&gt;AddImage($ID, $IBLOCK_ID, $_FILES['FILE_ID']);
	* }<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/DeleteImage.php">CWiki::DeleteImage</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/AddImage.php
	* @author Bitrix
	*/
	public function AddImage($ID, $IBLOCK_ID, $arImage)
	{
		$arProperties = array();
		$arCurImages = array();
		$arCurImagesNew = array();
		$arAddImage = array();

		$rsProperties = CIBlockElement::GetProperty($IBLOCK_ID, $ID, 'value_id', 'asc', array('ACTIVE' => 'Y', 'CODE' => 'IMAGES'));
		while($arProperty = $rsProperties->Fetch())
		{
			if($arProperty['CODE'] == 'IMAGES')
			{
				$arProperties['IMAGES'] = $arProperty;
				$arCurImages[] = $arProperty['VALUE'];
			}
		}

		$obProperty = new CIBlockProperty();
		$res = true;
		if(!array_key_exists('IMAGES', $arProperties))
		{
			$res = $obProperty->Add(array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'ACTIVE' => 'Y',
				'PROPERTY_TYPE' => 'F',
				'MULTIPLE' => 'Y',
				'NAME' => 'Images',
				'CODE' => 'IMAGES'
			));
		}

		$arFields = array();

		CFile::ResizeImage($arImage, array(
			'width' => COption::GetOptionString('wiki', 'image_max_width', 600),
			'height' => COption::GetOptionString('wiki', 'image_max_height', 600)
		));

		$arFields['PROPERTY_VALUES'] = array('IMAGES' => $arImage);
		$arFields['BLOCK_ID'] = $IBLOCK_ID;
		$arFields['ELEMENT_ID'] = $ID;

		$this->cIB_E->Update($ID, $arFields);

		$rsProperties = CIBlockElement::GetProperty($IBLOCK_ID, $ID, 'value_id', 'asc', array('ACTIVE' => 'Y', 'CODE' => 'IMAGES', 'EMPTY' => 'N'));
		while($arProperty = $rsProperties->Fetch())
		{
			if($arProperty['CODE'] == 'IMAGES')
				$arCurImagesNew[] = $arProperty['VALUE'];
		}

		$arAddImage = array_diff($arCurImagesNew, $arCurImages);
		list(, $imgId) = each($arAddImage);
		return $imgId;
	}

	
	/**
	* <p>Удаляет изображение из Wiki-страницы. Динамичный метод.</p>
	*
	*
	* @param int $IMAGE_ID  Идентификатор изображения.
	*
	* @param int $ID  Идентификатор Wiki-страницы. До версии 10.0.0 назывался <b>ELEMENT_ID</b>
	*
	* @param int $IBLOCK_ID  Идентификатор Инфоблока.
	*
	* @return void 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Удалим изображение с идентификатором 5 из страницы с идентификатором 13 из инфо.блока с идентификатором 2
	* $IMAGE_ID = 5;
	* $ID = 13;
	* $IBLOCK_ID = 2;
	* 
	* $CWiki = new CWiki();
	* $CWiki-&gt;DeleteImage($IMAGE_ID, $ID, $IBLOCK_ID);<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/AddImage.php">CWiki::AddImage</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/DeleteImage.php
	* @author Bitrix
	*/
	public function DeleteImage($IMAGE_ID, $ID, $IBLOCK_ID)
	{
		$rsProperties = CIBlockElement::GetProperty($IBLOCK_ID, $ID, 'value_id', 'asc', array('ACTIVE' => 'Y', 'CODE' => 'IMAGES'));
		$_iPropertyId = 0;
		while($arProperty = $rsProperties->Fetch())
		{
			if($arProperty['CODE'] == 'IMAGES' && $arProperty['VALUE'] == $IMAGE_ID)
			{
				$_iPropertyId = $arProperty['PROPERTY_VALUE_ID'];
				break;
			}
		}

		if (!empty($_iPropertyId))
		{
			$arPropertyValues = array();
			$arPropertyValues[$_iPropertyId] = array('VALUE' => array('del' => 'Y'), 'DESCRIPTION' => '');
			$this->cIB_E->SetPropertyValues($ID, $IBLOCK_ID, $arPropertyValues, 'IMAGES');
		}
	}

	
	/**
	* <p>Метод изменяет Wiki-страницу, добавляет запись в историю и обслуживает привязки к категориям. Динамичный метод.</p>
	*
	*
	* @param int $ID  Идентификатор Wiki-страницы
	*
	* @param array $arFields  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/update.php">CIBlock::Update</a>
	*
	* @param bUpdateSearc $h = true Необязательный.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br><br>// <span style='font-size: 10pt; line-height: 115%; font-family: "Courier New";'>Переименуем </span>Wiki-страницу с идентификатором 13 в инфо.блоке с идентификатором 2<br>$ID = 13;<br>$arFields = array(<br>	'IBLOCK_ID' =&gt; 2,<br>	'NAME' =&gt; 'Измененная тестовая страница'	<br>);<br>$CWiki = new CWiki();<br>if (!$CWiki-&gt;Rename($ID, $arFields))<br>	echo 'Ошибка. Страница не переименована.';<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Update.php">CWiki::Update</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/Rename.php
	* @author Bitrix
	*/
	public function Rename($ID, $arFields, $bUpdateSearch=true)
	{
		$arFilter = array('IBLOCK_ID' => $arFields['IBLOCK_ID'], 'CHECK_PERMISSIONS' => 'N');

		// checking for the existence of a page with this name
		$arElement = self::GetElementByName($arFields['NAME'], $arFilter);
		$arOldElement = self::GetElementById($ID, $arFilter);

		$bRename = false;
		if ($arOldElement != false)
		{
			if ($arElement == false)
				$bRename = true;
			else if($arElement['ID'] == $ID)
				$bRename = true;
		}

		if ($bRename)
		{
			$this->CleanCacheById($ID, $arFields['IBLOCK_ID']);

			$arFields['XML_ID'] = $arFields['NAME'];
			$this->cIB_E->Update($ID, $arFields, false, $bUpdateSearch);

			$sCatName = '';
			if(CWikiUtils::IsCategoryPage($arFields['NAME'], $sCatName))
			{
				$sCatNameOld = '';
				if (CWikiUtils::IsCategoryPage($arOldElement['NAME'], $sCatNameOld))
				{
					// rename a category
					$arFilter =  array('NAME' => $sCatNameOld, 'IBLOCK_ID' => $arFields['IBLOCK_ID'], 'CHECK_PERMISSIONS' => 'N');
					if (CWikiSocnet::IsSocNet())
					{
						$arFilter['>LEFT_BORDER'] = CWikiSocnet::$iCatLeftBorder;
						$arFilter['<RIGHT_BORDER'] = CWikiSocnet::$iCatRightBorder;
					}
					$rsCats = CIBlockSection::GetList(array(), $arFilter);
					$arCat = $rsCats->GetNext();

					if ($arCat != false)
					{
						$CIB_S = new CIBlockSection();

						$_arFields = array();
						$_arFields['IBLOCK_ID'] = $arFields['IBLOCK_ID'];
						$_arFields['NAME'] = $sCatName;
						$_arFields['XML_ID'] = $sCatName;
						$_arFields['CHECK_PERMISSIONS'] = 'N';

						$CIB_S->Update($arCat['ID'], $_arFields);
					}
				}
			}

			$arOldElement['NAME'] = CWikiUtils::htmlspecialcharsback($arOldElement['NAME']);

			if (self::GetDefaultPage($arFields['IBLOCK_ID']) == false
				|| (self::GetDefaultPage($arFields['IBLOCK_ID']) == $arOldElement['NAME']
					&& $arOldElement['NAME'] != $arFields['NAME']))
				self::SetDefaultPage($arFields['IBLOCK_ID'], $arFields['NAME']);

			return true;
		}

		return false;
	}


	/**
	 * Renames inner links, and categories on wiki pages
	 * alternatively you must do that manualy, after page, or catgory was renamed.
	 * @param int $iBlockId (mandatory)- id of iblock witch contain page, or category.
	 * @param str $oldName (mandatory)- old page or category name.
	 * @param str $newName (mandatory)- new page or category name.
	 * @param int $iBlockSectId (optional) - id of iBlock section witch contain page, or category.
	 *			if wiki used with socnet groups, this param must be setted, otherwise all pages of all soc. groups
	 *			will be changed.
	 * @return int the amount of changed pages.
	 */
	public function RenameLinkOnPages($iBlockId, $oldName, $newName, $iBlockSectId = false)
	{
		if(!$iBlockId || !$oldName || !$newName)
			return false;

		$arFilter["IBLOCK_ID"] = $iBlockId;
		$arFilter["CHECK_PERMISSIONS"]="N";

		if($iBlockSectId)
		{
			$arFilter["SECTION_ID"] = $iBlockSectId;
			$arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		}

		$count = 0;
		$sCatName = '';
		$isCategory = CWikiUtils::IsCategoryPage($oldName , $sCatName);

		$catSearch = "[[".GetMessage('CATEGORY_NAME').":".$sCatName."]]";

		$arPatterns = array(
			//link and link_name are equal
			array(
			"search" => "[[".$oldName."|".$oldName."]]",
			"pattern" => "/\[\[(".preg_quote($oldName).")\|(".preg_quote($oldName).")\]\]/isU".BX_UTF_PCRE_MODIFIER,
			"replacement" => "[[".$newName."|".$newName."]]"
			),

			//link and link_name are different
			array(
			"search" => "[[".$oldName."|",
			"pattern" => "/\[\[(".preg_quote($oldName).")\|(.*)\]\]/isU".BX_UTF_PCRE_MODIFIER,
			"replacement" => "[[".$newName."|$2]]"
			),

			//exist only link
			array(
			"search" => "[[".$oldName."]]",
			"pattern" => "/\[\[".preg_quote($oldName)."\]\]/isU".BX_UTF_PCRE_MODIFIER,
			"replacement" => "[[".$newName."]]"
			)
		);



		$dbRes = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "NAME", "DETAIL_TEXT"));

		while($arElement = $dbRes->GetNext())
		{
			$bChanged = false;

			$newText = $arElement["~DETAIL_TEXT"];

			foreach ($arPatterns as $arPattern)
			{
				if(strpos($newText, $arPattern["search"]) !== false)
				{
					$newText = preg_replace($arPattern["pattern"], $arPattern["replacement"], $newText);
					$bChanged = true;
				}
			}


			if ($isCategory)
				if(strpos($newText, $catSearch) !== false)
				{
					$newText = $this->RenameCategoryOnPage($newText, $sCatName, $newName);
					$bChanged = true;
				}

			if($bChanged)
			{
				$this->CleanCache($arElement["ID"], $arElement["NAME"], $iBlockId);
				$this->cIB_E->Update($arElement["ID"], array("DETAIL_TEXT" => $newText), false, true);
				self::MarkPageAsUpdated($iBlockId, $iBlockSectId, $arElement["NAME"]);
				$count++;
			}
		}

		return $count;
	}

	public static function RenameCategoryOnPage($pageText, $oldCategoryName, $newCategoryName)
	{
		$newCategoryName = preg_replace("/category:/isU", "", $newCategoryName);
		return preg_replace("/\[\[".GetMessage('CATEGORY_NAME').":".$oldCategoryName."\]\]/isU", "[[".GetMessage('CATEGORY_NAME').":".$newCategoryName."]]", $pageText);
	}

	
	/**
	* <p>Метод устанавливает Wiki-страницу по-умолчанию. Динамичный метод.</p>
	*
	*
	* @param int $IBLOCK_ID  Идентификатор Инфоблока. <br>До версии 10.0.0 назывался <b>BLOCK_ID</b>.
	*
	* @param string $NAME  Наименование страницы
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Установим страницу "Тестовая страница" страницей по-умолчанию инфо.блока с идентификатором 2
	* $IBLOCK_ID = 2;
	* $NAME = 'Тестовая страница';
	* 
	* if (!CWiki::SetDefaultPage($IBLOCK_ID, $NAME))
	* 	echo 'Ошибка. Страница по-умолчанию не установлена.';<br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetDefaultPage.php">CWiki::GetDefaultPage</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/SetDefaultPage.php
	* @author Bitrix
	*/
	static function SetDefaultPage($IBLOCK_ID, $NAME)
	{
		if (CWikiSocnet::IsSocNet())
		{
			$ENTITY_ID = 'IBLOCK_'.$IBLOCK_ID.'_SECTION';
			$ELEMENT_ID = CWikiSocnet::$iCatId;
		}
		else
		{
			$ENTITY_ID = 'IBLOCK_'.$IBLOCK_ID;
			$ELEMENT_ID = $IBLOCK_ID;
		}

		AddEventHandler("main", "OnUserTypeBuildList", array("CUserTypeWiki", "GetUserTypeDescription"));
		$GLOBALS['USER_FIELD_MANAGER']->CleanCache();
		$GLOBALS['USER_FIELD_MANAGER']->arUserTypes = '';

		$arElement = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($ENTITY_ID, $ELEMENT_ID);

		if ($arElement !== false)
		{
			if (!isset($arElement['UF_WIKI_INDEX']))
			{
				$arFields = array();
				$arFields['ENTITY_ID'] = $ENTITY_ID;
				$arFields['FIELD_NAME'] = 'UF_WIKI_INDEX';
				$arFields['USER_TYPE_ID'] = 'wiki';
				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID=$CAllUserTypeEntity->Add($arFields);
				if (false == $intID)
				{
					$e = $GLOBALS['APPLICATION']->GetException();
					if ($e)
						ShowError(GetMessage("WIKI_USER_T_ADD_ERR").$e->GetString());
				}
			}

			if (empty($arElement['UF_WIKI_INDEX']['VALUE']) || $arElement['UF_WIKI_INDEX']['VALUE'] != $NAME)
			{
				$arFields = array();
				$arFields['UF_WIKI_INDEX'] = $NAME;
				$GLOBALS['USER_FIELD_MANAGER']->Update($ENTITY_ID, $ELEMENT_ID, $arFields);
			}
			return true;
		}
		return false;
	}

	
	/**
	* <p>Метод возвращает Wiki-страницу по-умолчанию. Динамичный метод.</p>
	*
	*
	* @param int $IBLOCK_ID  Идентификатор Инфоблока. <br> До версии 10.0.0 назвался <b>BLOCK_ID</b>.
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Получим страницу по-умолчанию инфо.блока с идентификатором 2
	* $IBLOCK_ID = 2;
	* 
	* $NAME = CWiki::GetDefaultPage($IBLOCK_ID);
	* if (strlen($NAME) &lt;= 0)
	* 	echo 'Ошибка. Страница по-умолчанию не установлена.';
	* <br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/SetDefaultPage.php">CWiki::SetDefaultPage</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetDefaultPage.php
	* @author Bitrix
	*/
	static function GetDefaultPage($IBLOCK_ID)
	{
		if (CWikiSocnet::IsSocNet())
		{
			$ENTITY_ID = 'IBLOCK_'.$IBLOCK_ID.'_SECTION';
			$ELEMENT_ID = CWikiSocnet::$iCatId;
		}
		else
		{
			$ENTITY_ID = 'IBLOCK_'.$IBLOCK_ID;
			$ELEMENT_ID = $IBLOCK_ID;
		}

		AddEventHandler("main", "OnUserTypeBuildList", array("CUserTypeWiki", "GetUserTypeDescription"));
		$GLOBALS['USER_FIELD_MANAGER']->CleanCache();
		$GLOBALS['USER_FIELD_MANAGER']->arUserTypes = '';

		$arElement = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(
			$ENTITY_ID,
			$ELEMENT_ID
		);

		return isset($arElement['UF_WIKI_INDEX']['VALUE']) ? $arElement['UF_WIKI_INDEX']['VALUE'] : '';
	}

	
	/**
	* <p>Метод возвращает массив категорий Wiki-страницы. Динамичный метод.</p>
	*
	*
	* @param string $NAME  Наименование Wiki-страницы. До версии 9.5.3 назывался <b>ID</b>.
	*
	* @param int $IBLOCK_ID  Идентификатор Инфоблока. До версии 10.0.0 назывался <b>BLOCK_ID</b>.
	*
	* @return array <p> Возвращается массив категорий, содержащих поля со значениями:
	* </p> <table width="100%" class="tnormal"><tbody> <tr> <th width="15%">Параметр</th> <th>Описание</th>
	* </tr> <tr> <td>NAME</td> <td>наименование категории </td> </tr> <tr> <td>TITLE</td>
	* <td>наименование категории для подсказки</td> </tr> <tr> <td>LINK</td> <td>ссылка
	* на категорию</td> </tr> <tr> <td>IS_RED</td> <td>является ли ссылка красной (т.е.
	* страница категории не создана)</td> </tr> </tbody></table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Получим категории для страницы "Тестовая страница" инфо.блока с идентификатором 2
	* $NAME = 'Тестовая страница';
	* $IBLOCK_ID = 2;
	* 
	* $arCategory = CWiki::GetCategory($NAME, $IBLOCK_ID);<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetCategory.php
	* @author Bitrix
	*/
	public static function GetCategory($NAME, $IBLOCK_ID)
	{
		global $arParams;

		$arResult = array();
		$arResult[] = array(
			'TITLE' => GetMessage('Service:Categories_TITLE'),
			'NAME' => GetMessage('Service:Categories'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CATEGORIES'],
					array(
						'wiki_name' => 'Service:Categories',
						'group_id' => CWikiSocnet::$iSocNetId
					)
				),
				array()
			),
			'IS_RED' => 'N',
			'IS_SERVICE' => 'Y'
		);

		$arFilter['=XML_ID'] = $NAME;
		$arFilter['IBLOCK_ID'] = $IBLOCK_ID;
		$arFilter['CHECK_PERMISSIONS'] = 'N';

		if (CWikiSocnet::IsSocNet())
			$arFilter['SUBSECTION'] = CWikiSocnet::$iCatId;

		$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, Array());
		$arElement = $rsElement->GetNext();

		$sCatName = '';
		if (CWikiUtils::IsCategoryPage($NAME, $sCatName))
			return 	$arResult;

		$arLink = array();
		$arLinkExists = array();
		$arCat = array();
		$rsSect = CIBlockElement::GetElementGroups($arElement['ID'], false);
		while($arSect = $rsSect->GetNext())
		{
			$arCat[$arSect['ID']] = $arSect;
			$arLink[] = 'category:'.CWikiUtils::htmlspecialcharsback($arSect['NAME']);
		}

		if(empty($arLink))
			return array();

		if (CWikiSocnet::IsSocNet() && isset($arCat[CWikiSocnet::$iCatId]))
			unset($arCat[CWikiSocnet::$iCatId]);

		$arFilter = array();
		$arFilter['=NAME'] = $arLink;
		$arFilter['IBLOCK_ID'] = $IBLOCK_ID;
		$arFilter['ACTIVE'] = 'Y';
		$arFilter['CHECK_PERMISSIONS'] = 'N';
		if (CWikiSocnet::IsSocNet())
			$arFilter['SUBSECTION'] = CWikiSocnet::$iCatId;

		$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, Array());

		while($obElement = $rsElement->GetNextElement())
		{
			$arFields = $obElement->GetFields();
			$arLinkExists[] = preg_replace('/^(category|'.GetMessage('CATEGORY_NAME').'):/i'.BX_UTF_PCRE_MODIFIER, '', $arFields['NAME']);
		}

		if (!empty($arCat))
		{
			foreach ($arCat as $_arCat)
			{
				$_arCat['NAME'] = CWikiUtils::htmlspecialcharsback($_arCat['NAME'], false);
				$_arResult = array();
				$_arResult['ID'] = $_arCat['ID'];
				$_arResult['IS_RED'] = 'N';
				$_arResult['LINK'] = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CATEGORY'],
						array(
							'wiki_name' => 'Category:'.$_arCat['NAME'],
							'group_id' => CWikiSocnet::$iSocNetId
						)
					),
					array()
				);

				$_arResult['TITLE'] = $_arCat['NAME'];
				$_arResult['NAME'] = $_arCat['NAME'];
				$_arResult['IS_SERVICE'] = 'N';
				if (!in_array($_arCat['NAME'], $arLinkExists))
					$_arResult['IS_RED'] = 'Y';
				$arResult[] = $_arResult;
			}
		}
		return $arResult;
	}

	/**
	 *
	 *
	 *
	 * @param int $ID
	 * @return array
	 */
	
	/**
	* <p>Возвращает Wiki-страницу по фильтру arFilter. Статичный метод.</p>
	*
	*
	* @param int $ID  Идентификатор Wiki-страницы
	*
	* @param array $arFilter  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/getlist.php">GetList</a>
	*
	* @return array <p>Возвращается массив, содержащий поля со значениями: </p> <table
	* width="100%" class="tnormal"><tbody> <tr> <th width="15%">Параметр</th> <th>Описание</th> </tr> <tr>
	* <td>NAME</td> <td>наименование страницы</td> </tr> <tr> <td>DETAIL_TEXT_TYPE</td> <td>тип
	* содержимого страницы</td> </tr> <tr> <td>DETAIL_TEXT</td> <td>текст содержимого
	* страницы</td> </tr> <tr> <td>IMAGES</td> <td>массив изображений страницы</td> </tr>
	* <tr> <td>SECTIONS</td> <td>массив категорий страницы</td> </tr> <tr> <td>TAGS</td>
	* <td>массив тэгов страницы</td> </tr> </tbody></table>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Получим Wiki-страницу с идентификатором 13 инфо.блока с идентификатором 2
	* $ID = 13;
	* $arFilter = array(
	* 	'ACTIVE' =&gt; 'Y',
	* 	'CHECK_PERMISSIONS' =&gt; 'N',
	* 	'IBLOCK_ID' =&gt; 2
	* );
	* $arElement = CWiki::GetElementById($ID, $arFilter);
	* if ($arElement == false)
	* 	echo 'Страница не найдена.';
	* <br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetElementByName.php">CWiki::GetElementByName</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetCategory.php">CWiki::GetCategory</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiparser/parse.php">CWikiParser::Parse</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisecurity/clear.php">CWikiSecurity::clear</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetElementById.php
	* @author Bitrix
	*/
	public static function GetElementById($ID, $arFilter)
	{
		global $arParams;
		$arFilter['ID'] = $ID;
		if (CWikiSocnet::IsSocNet())
			$arFilter['SUBSECTION'] = CWikiSocnet::$iCatId;
		$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, Array());
		$obElement = $rsElement->GetNextElement();
		$arResult = false;
		if ($obElement !== false)
		{
			$arResult = $obElement->GetFields();

			if (isset($arResult['NAME']))
				$arResult['NAME'] = htmlspecialcharsbx($arResult['NAME']);
			$rsProperties = $obElement->GetProperties(array(), array('CODE' => 'IMAGES'));

			foreach ($rsProperties as $arProperty)
				$arResult[$arProperty['CODE']] = $arProperty['VALUE'];

			$arResult['SECTIONS'] = self::GetCategory($arResult['XML_ID'], $arFilter['IBLOCK_ID']);
			if (!empty($arResult['TAGS']))
			{
				$_arTAGS = explode(',', $arResult['TAGS']);
				$arResult['_TAGS'] = array();
				foreach ($_arTAGS as $sTag)
				{
					$arTag = array('NAME' => $sTag);
					if (!empty($arParams['PATH_TO_SEARCH']))
					{
						$arP = $arParams['IN_COMPLEX'] == 'Y' && $arParams['SEF_MODE'] == 'N' ? array($arParams['OPER_VAR'] => 'search') : array();
						$arP['tags'] = urlencode($sTag);
						$arTag['LINK'] = CHTTP::urlAddParams(
									CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SEARCH'],
										array(
											'wiki_name' => $arParams['ELEMENT_NAME'],
											'group_id' => CWikiSocnet::$iSocNetId)
										),
										$arP
									);
					}
					$arResult['_TAGS'][] = $arTag;
				}
			}
		}
		return $arResult;
	}

	/**
	 * @param string $NAME (mandatory) - the name of page
	 * @param array $arFilter (mandatory) - the filter for CIBlockElement::GetList
	 * @param array $arComponentParams (optional) - params of the calling wiki component
	 *		  using indexes: CACHE_TIME, PATH_TO_SEARCH, IN_COMPLEX, SEF_MODE, OPER_VAR, ELEMENT_NAME
	 *		  necessary for: building search tag links
	 * @return array
	 */
	
	/**
	* <p>Возвращает Wiki-страницу по фильтру arFilter. Статичный метод.</p>
	*
	*
	* @param string $NAME  Название Wiki-страницы
	*
	* @param array $arFilter  <a href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblock/getlist.php">GetList</a>
	*
	* @param arComponentParam $s = array() Необязательный.
	*
	* @return result_type <p>Возвращается массив, содержащий поля со значениями: </p> <table
	* width="100%" class="tnormal"><tbody> <tr> <th width="15%">Параметр</th> <th>Описание</th> </tr> <tr>
	* <td>NAME</td> <td>наименование страницы</td> </tr> <tr> <td>DETAIL_TEXT_TYPE</td> <td>тип
	* содержимого страницы</td> </tr> <tr> <td>DETAIL_TEXT</td> <td>текст содержимого
	* страницыы</td> </tr> <tr> <td>IMAGES</td> <td>массив изображений страницы</td> </tr>
	* <tr> <td>SECTIONS</td> <td>массив категорий страницы</td> </tr> <tr> <td>TAGS</td>
	* <td>массив тэгов страницы</td> </tr> </tbody></table>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>
	* // Получим Wiki-страницу с названием "Тестовая страница" инфо.блока с идентификатором 2
	* 
	* $NAME = 'Тестовая страница';
	* $arFilter = array(
	* 	'ACTIVE' =&gt; 'Y',
	* 	'CHECK_PERMISSIONS' =&gt; 'N',
	* 	'IBLOCK_ID' =&gt; 2
	* );
	* $arElement = CWiki::GetElementByName($NAME, $arFilter);
	* if ($arElement == false)
	* 	echo 'Страница не найдена.';
	* <br>?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetElementById.php">CWiki::GetElementById</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetCategory.php">CWiki::GetCategory</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikiparser/parse.php">CWikiParser::Parse</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/wiki/classes/cwikisecurity/clear.php">CWikiSecurity::clear</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/wiki/classes/cwiki/GetElementByName.php
	* @author Bitrix
	*/
	public static function GetElementByName($NAME, $arFilter, $arComponentParams = array())
	{
		global $CACHE_MANAGER;

		$iCatId = "";

		if (CWikiSocnet::IsSocNet())
		{
			$arFilter['SUBSECTION'] = CWikiSocnet::$iCatId;
			$iCatId = $arFilter['SUBSECTION'];
		}

		$cacheByNameID = self::GetIdForCacheByName($arFilter['IBLOCK_ID'], $iCatId, $NAME);

		$cacheTime = isset($arComponentParams['CACHE_TIME']) ? intval($arComponentParams['CACHE_TIME']) : CWiki::CWIKI_CACHE_TTL;

		if($CACHE_MANAGER->Read($cacheTime, $cacheByNameID))
		{
			$cachedElement = $CACHE_MANAGER->Get($cacheByNameID);

			if($cachedElement)
			{
				//if cached element satisfied to filter's conditions
				$sameFilter = true;
				foreach ($arFilter as $key => $value)
				{
					if(isset($cachedElement[$key]) && $value != "" && $cachedElement[$key] != $value)
					{
						$sameFilter = false;
						break;
					}
				}

				if($sameFilter)
					return $cachedElement;
			}
		}

		$NAME = CWikiUtils::UnlocalizeCategoryName($NAME);
		$NAME = CWikiUtils::htmlspecialcharsback($NAME);
		$arFilter['=XML_ID'] = $NAME;

		$rsElement = CIBlockElement::GetList(array(), $arFilter);
		$obElement = $rsElement->GetNextElement();
		$arResult = false;
		if ($obElement !== false)
		{
			$arResult = $obElement->GetFields();
			if (isset($arResult['NAME']))
				$arResult['NAME'] = htmlspecialcharsbx($arResult['NAME']);
			$rsProperties = $obElement->GetProperties(array(), array('CODE' => 'IMAGES'));

			foreach ($rsProperties as $arProperty)
				$arResult[$arProperty['CODE']] = $arProperty['VALUE'];

			$rsProperties = $obElement->GetProperties(array(), array('CODE' => 'FORUM_TOPIC_ID'));

			foreach ($rsProperties as $arProperty)
				$arResult[$arProperty['CODE']] = $arProperty['VALUE'];

			$arResult['SECTIONS'] = self::GetCategory($arResult['XML_ID'], $arFilter['IBLOCK_ID']);
			if (!empty($arResult['TAGS']))
			{
				$_arTAGS = explode(',', $arResult['TAGS']);
				$arResult['_TAGS'] = array();
				foreach ($_arTAGS as $sTag)
				{
					$sTag = trim($sTag);
					$arTag = array('NAME' => $sTag);
					if (!empty($arComponentParams) && isset($arComponentParams['PATH_TO_SEARCH']))
					{
						$arP = $arComponentParams['IN_COMPLEX'] == 'Y' && $arComponentParams['SEF_MODE'] == 'N' ? array($arComponentParams['OPER_VAR'] => 'search') : array();
						$arP['tags'] = urlencode($sTag);
						$arTag['LINK'] = CHTTP::urlAddParams(
									CComponentEngine::MakePathFromTemplate($arComponentParams['PATH_TO_SEARCH'],
										array(
											'wiki_name' => $arComponentParams['ELEMENT_NAME'],
											'group_id' => CWikiSocnet::$iSocNetId
										)
									),
									$arP
								);
					}
					$arResult['_TAGS'][] = $arTag;
				}
			}
		}

		if(!empty($arComponentParams)) //Let's store only full page data with tag links
			$CACHE_MANAGER->Set($cacheByNameID, $arResult);

		return $arResult;
	}

	private function CleanCacheById($ID, $iBlockId = false)
	{
		return $this->CleanCache($ID, false, $iBlockId);
	}

	private function CleanCache($ID = false, $Name = false, $iBlockId = false)
	{
		if($ID === false && !$Name)
			return false;

		global $CACHE_MANAGER;

		if($ID !== false)
			$CACHE_MANAGER->ClearByTag('wiki_'.$ID);

		if(!$iBlockId)
			return true;

		$iCatId = CWikiSocnet::IsSocNet() ? CWikiSocnet::$iCatId : "";

		if($ID !== false )
		{
			$cacheByNameID = self::GetIdForCacheByName($iBlockId, $iCatId, $ID);
			$CACHE_MANAGER->Clean($cacheByNameID);

			if(!$Name)
			{
				$arFilter = array(
					'IBLOCK_ID' => $iBlockId,
					'CHECK_PERMISSIONS' => 'N'
					);

				$arElement = self::GetElementById($ID, $arFilter);
				if($arElement != false)
					$elName = $arElement['NAME'];
			}
			else
			{
				$elName = $Name;
			}
		}

		$cacheByNameID = self::GetIdForCacheByName($iBlockId, $iCatId, $elName);
		$CACHE_MANAGER->Clean($cacheByNameID);

		return true;
	}

	private static function GetIdForCacheByName($iBlockId, $iSocCatId, $elementName)
	{
		return self::GET_BY_NAME_CACHE_ID.$iBlockId.$iSocCatId.$elementName;
	}

	public static function UnMarkPageAsUpdated($iBlockId, $iSocCatId, $name)
	{
		global $CACHE_MANAGER;

		$cacheId = self::GetCacheIdForPageUpdated($iBlockId, $iSocCatId, $name);

		$CACHE_MANAGER->Clean($cacheId);

		return true;
	}

	public static function IsPageUpdated($iBlockId, $iSocCatId, $name, $cacheTime = self::CWIKI_CACHE_TTL)
	{
		global $CACHE_MANAGER;

		$cacheId = self::GetCacheIdForPageUpdated($iBlockId, $iSocCatId, $name);

		if($CACHE_MANAGER->Read($cacheTime, $cacheID))
			return ($CACHE_MANAGER->Get($cacheId) == "Y");

		return false;
	}

	private static function MarkPageAsUpdated($iBlockId, $iSocCatId, $name)
	{
		global $CACHE_MANAGER;

		$cacheId = self::GetCacheIdForPageUpdated($iBlockId, $iSocCatId, $name);

		$CACHE_MANAGER->Set($cacheId, "Y");

		return true;
	}

	private static function GetCacheIdForPageUpdated($iBlockId, $iSocCatId, $name)
	{
		return self::PAGE_UPDATED_CACHE_ID.$iBlockId.$iSocCatId.$name;
	}

}

?>