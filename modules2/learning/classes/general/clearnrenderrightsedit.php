<?php

class CLearnRenderRightsEdit
{
	public static function RenderBaseRightsTab ($userId, $POSTName = 'BASE_RIGHTS')
	{
		self::RenderLessonRightsTab ($userId, $POSTName, false, false);
	}


	public static function RenderLessonRightsTab ($userId, $POSTName = 'LESSON_RIGHTS', $lessonId, $readOnly)
	{
		$oAccess = CLearnAccess::GetInstance ($userId);
		$arPossibleRights = $oAccess->ListAllPossibleRights();

		$arBaseRights   = $oAccess->GetBasePermissions();

		// is it base permissions request?
		if ($lessonId === false)
			$arActualRights = $arBaseRights;
		elseif ($lessonId == 0)	// is new lesson?
			$arActualRights = array();
		else
			$arActualRights = $oAccess->GetLessonPermissions($lessonId);

		CLearnRenderRightsEdit::LearningShowRights(
			$lessonId,		// expected (bool)false for base rights
			$POSTName,
			$arBaseRights,
			$arPossibleRights,
			$arActualRights,
			array(),
			array(),
			$readOnly
		);
	}


	protected static function LearningShowRights($lessonId, $variable_name, $arBaseRights, $arPossibleRights, $arActualRights, $arSelected = array(), $arHighLight = array(), $readOnly)
	{
		$js_var_name = preg_replace("/[^a-zA-Z0-9_]/", "_", $variable_name);
		$html_var_name = htmlspecialcharsbx($variable_name);

		$sSelect = '<select name="'.$html_var_name.'[][TASK_ID]" style="vertical-align:middle">';
		foreach($arPossibleRights as $taskId => $arRightsData)
		{
			$selected = '';
			if (strtoupper($arRightsData['name']) === 'LEARNING_LESSON_ACCESS_DENIED')
				$selected = ' selected="selected" ';
			$sSelect .= '<option value="' . (int) $taskId . '" ' . $selected . '>' . htmlspecialcharsex($arRightsData['name_human']) . '</option>';
		}
		$sSelect .= '</select>';

		$table_id = $variable_name."_table";
		$href_id = $variable_name."_href";

		CJSCore::Init(array('access'));
		?>
		<tr>
			<td colspan="2" align="center">
				<input type="hidden" name="<?php echo $variable_name . '_marker' ?>" value='yeah!'>
				<script type="text/javascript">
					var obLearningJSRightsAccess_<?=$js_var_name?> = new LearningJSRightsAccess(
						<?=intval($lessonId)?>,
						<?=CUtil::PhpToJsObject($arSelected)?>,
						'<?=CUtil::JSEscape($variable_name)?>',
						'<?=CUtil::JSEscape($table_id)?>',
						'<?=CUtil::JSEscape($href_id)?>',
						'<?=CUtil::JSEscape($sSelect)?>',
						<?=CUtil::PhpToJsObject($arHighLight)?>
					);
				</script>
				<h3><?php echo GetMessage('LEARNING_RIGHTS_FOR_ADMINISTRATION'); ?></h3>
				<table width="100%" cellpadding="0" cellspacing="10" border="0" id="<?echo htmlspecialcharsbx($table_id)?>" align="center">
				<?php

				$access = new CAccess();

				// If rights are for lesson => show base rights
				if ($lessonId !== false)
				{
					$arBaseNames = $access->GetNames(array_keys($arBaseRights));

					foreach ($arBaseRights as $symbol => $taskId)
					{
						if ($taskId <= 0)
							continue;
					?>
					<tr valign="top">
						<td align="right"><?echo htmlspecialcharsex($arBaseNames[$symbol]['provider'] . ' ' . $arBaseNames[$symbol]['name'])?>:&nbsp;</td>
						<td align="left">
							<?php echo htmlspecialcharsex(CLearnAccess::GetNameForTask ($taskId)); ?>
						</td>
					</tr>
					<?
					}
				}

				$arNames = $access->GetNames(array_keys($arActualRights));
				foreach($arActualRights as $symbol => $taskId)
				{
					if ($taskId <= 0)
						continue;
				?>
				<tr valign="top">
					<td align="right">
						<div style="padding-top:8px;">
						<span href="javascript:void(0);" 
							onclick="LearningJSRightsAccess.DeleteRow(
								this, 
								'<?=htmlspecialcharsbx(CUtil::addslashes($symbol))?>', 
								'<?=CUtil::JSEscape($html_var_name)?>')" 
							class="access-delete"
							style="position:relative; top:1px; margin-right:3px;"
						></span><?php
						if (strlen($arNames[$symbol]['provider']))
							echo htmlspecialcharsex($arNames[$symbol]['provider'] . ' ' . $arNames[$symbol]['name']);
						else
							echo htmlspecialcharsex($arNames[$symbol]['name']);
						?>:&nbsp;
					</div>
					</td>
					<td align="left">
						<?php
						if ( $readOnly )
						{
							echo htmlspecialcharsex(CLearnAccess::GetNameForTask ($taskId));
						}
						else
						{
						?>
						<input type="hidden" name="<?php echo $html_var_name; ?>[][GROUP_CODE]" value="<?php echo htmlspecialcharsbx($symbol); ?>">
						<div style="min-width:720px;">
							<select name="<?php echo $html_var_name; ?>[][TASK_ID]" style="vertical-align:middle">
						<?php
						foreach($arPossibleRights as $id => $arRightsData)
						{
							?>
							<option value="<?php echo (int) $id; ?>" <?php if($id == $taskId) echo "selected"; ?>><?php echo htmlspecialcharsex(CLearnAccess::GetNameForTask ($id)); ?></option>
							<?php
						}
						?>
						</select>
						</div>
						<?php
						}
						?>
					</td>
				</tr>
				<?
				}
			
				if ( ! $readOnly )
				{
				?>
					<tr>
						<td width="40%" align="right">&nbsp;</td>
						<td width="60%" align="left">
							<a href="javascript:void(0)"  id="<?echo htmlspecialcharsbx($href_id)?>" class="bx-action-href"><?echo GetMessage("LEARNING_RIGHTS_ADD")?></a>
						</td>
					</tr>
				<?php
				}
				?>
				</table>
				<br>
				<strong><?php echo GetMessage('LEARNING_RIGHTS_NOTE'); ?></strong>
			</td>
		</tr>
		<?
	}
}