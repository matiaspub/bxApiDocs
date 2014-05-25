<?php

class CLearnRelationHelper
{
	public static function RenderForm ($oAccess, $LESSON_ID, $arOPathes)
	{
		global $APPLICATION;

		$curDir = $APPLICATION->GetCurDir();
		if (substr($curDir, -1) !== '/')
			$curDir .= '/';

		?>
		<script type="text/javascript">
		function module_learning_js_admin_function_add_parent(lesson_id, name)
		{
			module_learning_js_admin_function_add_child_or_parent('LEARNING_LIST_OF_PARENTS', 'RELATION_PARENT[]', lesson_id, name);
			//alert ('called: module_learning_js_admin_function_add_parent(' + lesson_id + name + ')');
		}
		function module_learning_js_admin_function_add_child(lesson_id, name)
		{
			module_learning_js_admin_function_add_child_or_parent('LEARNING_LIST_OF_DESCENDANTS', 'RELATION_CHILD[]', lesson_id, name);
			//alert ('called: module_learning_js_admin_function_add_child(' + lesson_id + ')');
		}
		function module_learning_js_admin_function_add_child_or_parent(targetNode, fieldName, lesson_id, name)
		{
			var elemId = 'RELATION_PARENT_LID_' + lesson_id;

			var oDIV = BX.create('DIV', {'props': {'id': elemId}});
			var oA = BX.create(
				'SPAN', 
				{
					'props':
					{
						'className': 'access-delete',
					},
					'style':
					{
						position: 'relative',
						top: '3px',
						marginRight: '1px'
					},
					'events':
					{
						'click': function() {
							document.getElementById(elemId).parentNode.removeChild(document.getElementById(elemId));
						}
					}
				}
			);
			var oINPUT = BX.create(
				'INPUT', 
				{
					'props': 
					{
						'type':  'hidden', 
						'name':  fieldName,
						'value': lesson_id
					}
				}
			);
			var oSPAN = BX.create(
				'SPAN', 
				{
					'text': '[' + lesson_id + '] ' + name
				}
			);
			var oSPAN_space = BX.create(
				'SPAN', 
				{
					'text': ' '
				}
			);

			oDIV.appendChild(oA);
			oDIV.appendChild(oSPAN_space);
			oDIV.appendChild(oINPUT);
			oDIV.appendChild(oSPAN);
			BX(targetNode).appendChild(oDIV);
		}
		</script>
		<?php

		$arChilds  = array();
		$key = 0;

		$isChapter = $isCourse = false;
		$resChilds = CLearnLesson::GetListOfImmediateChilds($LESSON_ID);
		while ($arChild = $resChilds->Fetch())
		{
			$isChapter = true;		// this lesson is chapter, because there is descendants
			$arChilds['RELATION_CHILD_' . $key++] = array(
				'elemName' => 'RELATION_CHILD[]',
				'lessonId' => $arChild['LESSON_ID'],
				'Name'     => '[<a href="' . htmlspecialcharsbx($curDir) . 'learn_unilesson_edit.php?lang=' . LANG 
					. '&LESSON_ID=' . ($arChild['LESSON_ID'] + 0) . '&LESSON_PATH=' . (int) $arChild['LESSON_ID'] . '" target=_blank>' 
					. (int) $arChild['LESSON_ID'] 
					. '</a>] ' 
					. htmlspecialcharsbx($arChild['NAME'])
				);
		}

		// Is course?
		$isCourse = (CLearnLesson::GetLinkedCourse ($LESSON_ID) !== false);

		?>
		<div style="padding:10px;">
			<div id="LEARNING_LIST_OF_PARENTS" style="padding:10px 0;">
				<h3><?php echo GetMessage('LEARNING_LIST_OF_PARENTS') . ':'; ?></h3>
			<?php
			$arParents = array();
			$resParents = CLearnLesson::GetListOfImmediateParents($LESSON_ID);
			while ($arParent = $resParents->Fetch())
			{
				$arParents['RELATION_PARENT_' . $key++] = array(
					'elemName' => 'RELATION_PARENT[]',
					'lessonId' => $arParent['LESSON_ID'],
					'Name'     => '[<a href="' . htmlspecialcharsbx($curDir) . 'learn_unilesson_edit.php?lang=' . LANG 
						. '&LESSON_ID=' . ($arParent['LESSON_ID'] + 0) . '&LESSON_PATH=' . (int) $arParent['LESSON_ID'] . '" target=_blank>' 
						. (int) $arParent['LESSON_ID'] . '</a>] ' 
						. htmlspecialcharsbx($arParent['NAME'])
					);
			}

			foreach ($arParents as $elemId => $arElem)
			{
				?>
				<div id="<?php echo $elemId; ?>">
					<?php
					if (
						(
							$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS)
							|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS)
						)
						&&
						(
							$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS)
							|| $oAccess->IsLessonAccessible ($arElem['lessonId'], CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS)
						)
					)
					{
						?>
						<span
							onclick="document.getElementById('<?php echo $elemId; ?>').parentNode.removeChild(document.getElementById('<?php echo $elemId; ?>'))" 
							class="access-delete"
							style="position:relative; top:3px; margin-right:1px;"
							>&nbsp;</span>
						<?php
					}
					?>
					<input type="hidden" name="<?php echo ($arElem['elemName']); ?>" value="<?php echo (int) $arElem['lessonId']; ?>">
					<span style="font-style:italic;"><?php echo ($arElem['Name']); ?></span>
				</div>
				<?
			}
			?>
			</div>

			<?php
			if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_TO_PARENTS)
				|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_LINK_TO_PARENTS)
			)
			{
				?>
				<div style="padding:0px;">
					<a href="javascript:void(0);" class="bx-action-href"
						onclick="window.open('<?php echo addslashes(htmlspecialcharsbx($curDir)); ?>learn_unilesson_admin.php?lang=<?php echo LANGUAGE_ID; 
							?>&amp;search_retpoint=module_learning_js_admin_function_add_parent&amp;search_mode_type=parents_candidates', 
							'module_learning_js_admin_window_select_lessons_for_relations', 
							'scrollbars=yes,resizable=yes,width=960,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 960)/2-5));" 
						><?php echo GetMessage('LEARNING_ADD_ELEMENT'); ?></a>
				</div>
				<?php
			}
			?>
			<div style="height:25px;">&nbsp;</div>
			<div id="LEARNING_LIST_OF_DESCENDANTS" style="padding:10px 0;">
				<h3><?php echo GetMessage('LEARNING_LIST_OF_DESCENDANTS') . ':'; ?></h3>
			<?php
			foreach ($arChilds as $elemId => $arElem)
			{
				?>
				<div id="<?php echo $elemId; ?>">
					<?php
					if (
						(
							$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS)
							|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS)
						)
						&&
						(
							$oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS)
							|| $oAccess->IsLessonAccessible ($arElem['lessonId'], CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS)
						)
					)
					{
						?>
						<a href="javascript:void(0);" 
							onclick="document.getElementById('<?php echo $elemId; ?>').parentNode.removeChild(document.getElementById('<?php echo $elemId; ?>'))" 
							class="access-delete"></a>
						<?php
					}
					?>
					<input type="hidden" name="<?php echo ($arElem['elemName']); ?>" value="<?php echo (int) $arElem['lessonId']; ?>">
					<span style="font-style:italic;"><?php echo ($arElem['Name']); ?></span>
				</div>
				<?
			}
			?>
			</div>
			<?php
			if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_DESCENDANTS)
				|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_LINK_DESCENDANTS)
			)
			{
				?>
				<div style="padding:0px;">
					<a href="javascript:void(0);" class="bx-action-href"
						onclick="window.open('<?php echo addslashes(htmlspecialcharsbx($curDir)); ?>learn_unilesson_admin.php?lang=<?php echo LANGUAGE_ID; 
							?>&amp;search_retpoint=module_learning_js_admin_function_add_child', 
							'module_learning_js_admin_window_select_lessons_for_relations', 
							'scrollbars=yes,resizable=yes,width=960,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 960)/2-5));" 
						><?php echo GetMessage('LEARNING_ADD_ELEMENT'); ?></a>
				</div>
				<?php
			}
			?>
			<div style="height:25px;">&nbsp;</div>
			<hr>
			<div id="LEARNING_LIST_OF_ALL_PARENT_PATHES" style="padding:10px 0;">
				<?php

				$cntParentPathes = count($arOPathes);

				$langPhraseBase = 'LEARNING_LIST_OF_ALL_PARENT_PATHES_FOR_';

				$lessonType = 'LESSON';
				if ($isCourse)
					$lessonType = 'COURSE';
				elseif ($isChapter)
					$lessonType = 'CHAPTER';

				$isEmpty = '';
				if ($cntParentPathes === 0)
					$isEmpty = '_IS_EMPTY';

				echo '<h3>' . GetMessage($langPhraseBase . $lessonType . $isEmpty) . '</h3>';

				if ($cntParentPathes > 0)
				{
					$pattern = '[<a href="' . addslashes(htmlspecialcharsbx($curDir)) . 'learn_unilesson_edit.php?lang=' . LANG 
						. '&LESSON_ID=#LESSON_ID#&LESSON_PATH=#LESSON_ID#" target="_blank">#LESSON_ID#</a>] #NAME#';

					foreach ($arOPathes as $oPath)
					{
						echo $oPath->GetPathAsHumanReadableString(' / ', $pattern);

						if ($oPath->Count() >= 1)
						{
							if (CLearnLesson::IsPublishProhibited ($LESSON_ID, $oPath->GetTop()))
								echo ' <span style="color:grey;">(' . GetMessage('LEARNING_LESSON_IS_PUBLISH_PROHIBITED') . ')</span>';
						}

						echo '<br>';
					}
				}
				?>
			</div>
		</div>
		<?php
	}


	public static function ProccessPOST($oAccess, $LESSON_ID, $sort = false)
	{
		$isAccessUseCache = true;

		if ($sort === false)
			$sort = 500;

		// Remove/add relations from/to parent
		if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_TO_PARENTS, $isAccessUseCache)
			|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_LINK_TO_PARENTS, $isAccessUseCache)
			|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS, $isAccessUseCache)
			|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS, $isAccessUseCache)
		)
		{
			$arCurParentsIds = array();
			$resParents = CLearnLesson::GetListOfImmediateParents($LESSON_ID);
			while ($arParent = $resParents->Fetch())
				$arCurParentsIds[] = (int) $arParent['LESSON_ID'];

			$arDestParentsIds = array();

			if (isset($_POST['RELATION_PARENT']) && is_array($_POST['RELATION_PARENT']))
				foreach ($_POST['RELATION_PARENT'] as $key => $relatedLessonId)
					$arDestParentsIds[] = (int) $relatedLessonId;

			// remove relations
			if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS, $isAccessUseCache)
				|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS, $isAccessUseCache)
			)
			{
				$arRemoveIds = array_diff ($arCurParentsIds, $arDestParentsIds);
				foreach ($arRemoveIds as $relatedLessonId)
				{
					if ( $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS, $isAccessUseCache)
						|| $oAccess->IsLessonAccessible ($relatedLessonId, CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS, $isAccessUseCache) 
					)
					{
						CLearnLesson::RelationRemove ($relatedLessonId, $LESSON_ID);
					}
				}
			}

			// add relations
			if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_TO_PARENTS, $isAccessUseCache)
				|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_LINK_TO_PARENTS, $isAccessUseCache)
			)
			{
				$arAddIds = array_diff ($arDestParentsIds, $arCurParentsIds);
				foreach ($arAddIds as $relatedLessonId)
				{
					if ( $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_DESCENDANTS, $isAccessUseCache)
						|| $oAccess->IsLessonAccessible ($relatedLessonId, CLearnAccess::OP_LESSON_LINK_DESCENDANTS, $isAccessUseCache) 
					)
					{
						CLearnLesson::RelationAdd ($relatedLessonId, $LESSON_ID, array('SORT' => $sort));
					}
				}
			}
		}

		// Remove/add relations from/to childs
		if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_DESCENDANTS, $isAccessUseCache)
			|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_LINK_DESCENDANTS, $isAccessUseCache)
			|| $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS, $isAccessUseCache)
			|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS, $isAccessUseCache)
		)
		{
			$arCurChildsIds = array();
			$resChilds = CLearnLesson::GetListOfImmediateChilds($LESSON_ID);
			while ($arChild = $resChilds->Fetch())
				$arCurChildsIds[] = (int) $arChild['LESSON_ID'];

			$arDestChildsIds  = array();

			if (isset($_POST['RELATION_CHILD']) && is_array($_POST['RELATION_CHILD']))
				foreach ($_POST['RELATION_CHILD'] as $key => $relatedLessonId)
					$arDestChildsIds[] = (int) $relatedLessonId;

			// remove relations
			if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS, $isAccessUseCache)
				|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_UNLINK_DESCENDANTS, $isAccessUseCache)
			)
			{
				$arRemoveIds = array_diff ($arCurChildsIds, $arDestChildsIds);
				foreach ($arRemoveIds as $relatedLessonId)
				{
					if ( $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS, $isAccessUseCache)
						|| $oAccess->IsLessonAccessible ($relatedLessonId, CLearnAccess::OP_LESSON_UNLINK_FROM_PARENTS, $isAccessUseCache) 
					)
					{
						CLearnLesson::RelationRemove ($LESSON_ID, $relatedLessonId);
					}
				}
			}

			// add relations
			if ($oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_DESCENDANTS, $isAccessUseCache)
				|| $oAccess->IsLessonAccessible ($LESSON_ID, CLearnAccess::OP_LESSON_LINK_DESCENDANTS, $isAccessUseCache)
			)
			{
				$arAddIds = array_diff ($arDestChildsIds, $arCurChildsIds);
				foreach ($arAddIds as $relatedLessonId)
				{
					if ( $oAccess->IsBaseAccess(CLearnAccess::OP_LESSON_LINK_TO_PARENTS, $isAccessUseCache)
						|| $oAccess->IsLessonAccessible ($relatedLessonId, CLearnAccess::OP_LESSON_LINK_TO_PARENTS, $isAccessUseCache) 
					)
					{
						CLearnLesson::RelationAdd ($LESSON_ID, $relatedLessonId, array('SORT' => $sort));
					}
				}
			}
		}
	}
}
