<?
IncludeModuleLangFile(__FILE__);

class CClock
{
	public static function Init(&$arParams)
	{
		if (!isset($arParams['inputId']))
			$arParams['inputId'] = 'bxclock_'.rand();
		if (!isset($arParams['inputName']))
			$arParams['inputName'] = $arParams['inputId'];
		if (!isset($arParams['step']))
			$arParams['step'] = 5;
		if ($arParams['view'] == 'select' && $arParams['step'] < 30)
			$arParams['step'] = 30;

		if ($arParams['view'] != 'inline')
			$arParams['view'] = 'input';
	}

	public static function Show($arParams)
	{

		CClock::Init($arParams);
		$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');

		// Show input
		switch ($arParams['view'])
		{
			case 'label':
				?>
				<input type="hidden" id="<?=$arParams['inputId']?>" name="<?=$arParams['inputName']?>"  value="<?=$arParams['initTime']?>">
				<div id=class="bx-clock-label" onmouseover="this.className='bx-clock-label-over';" onmouseout="this.className='bx-clock-label';" onclick=""><? echo $arParams['initTime'] ? $arParams['initTime'] : 'Time'; ?></div><?
				break;
			case 'select':
				?>
				<select id="<?=$arParams['inputId']?>" name="<?=$arParams['inputName']?>">
					<?
					for ($i = 0; $i < 24; $i++)
					{
						$h = ($i < 10) ? '0'.$i : $i;
						?><option value="<?=$h?>:00"><?=$h?>:00</option><?
						if ($arParams['step']) {?><option value="<?=$h?>:30"><?=$h?>:30</option><?}
					}
					?>
				</select>
				<?
				break;
			case 'inline':
				?>
				<input type="hidden" id="<?=$arParams['inputId']?>" name="<?=$arParams['inputName']?>"  value="<?=$arParams['initTime']?>" />
				<div id="<?=$arParams['inputId']?>_clock"></div>
				<script type="text/javascript">
					if (!window.bxClockLoaders)
					{
						window.bxClockLoaders = [];
						window.onload = function() {
							for (var i=0; i<window.bxClockLoaders.length; i++)
								setTimeout(window.bxClockLoaders[i], 20*i + 20);
							window.bxClockLoaders = null;
						}
					}

					window.bxClockLoaders.push("bxShowClock_<?=$arParams['inputId']?>('<?=$arParams['inputId']?>_clock');");
				</script>
				<?
				break;
			default: //input
				?><input id="<?=$arParams['inputId']?>" name="<?=$arParams['inputName']?>" type="text" value="<?=$arParams['initTime']?>" size="<?=IsAmPmMode() ? 6 : 4?>" title="<?=$arParams['inputTitle']?>" /><?
				break;
		}
		// Show icon
		if ($arParams['showIcon'] !== false)
		{
			?><a href="javascript:void(0);" onclick="bxShowClock_<?=$arParams['inputId']?>()" title="<?=GetMessage('BX_CLOCK_TITLE')?>" onmouseover="this.className='bxc-icon-hover';" onmouseout="this.className='';"><img id="<?=$arParams['inputId']?>_icon" src="/bitrix/images/1.gif" class="bx-clock-icon bxc-iconkit-c"></a><?
		}

		//Init JS and append CSS
		?><script>
		BX.loadCSS("/bitrix/themes/.default/clock.css");

		function bxLoadClock_<?=$arParams['inputId']?>(callback)
		{
			<?if($arParams['view'] != 'inline'):?>
			if (!window.JCClock && !window.jsUtils)
			{
				return setTimeout(function(){bxLoadClock_<?=$arParams['inputId']?>(callback);}, 50);
			}
			<?endif;?>

			if (!window.JCClock)
			{
				if(!!window.bClockLoading)
				{
					return setTimeout(function(){bxLoadClock_<?=$arParams['inputId']?>(callback);}, 50);
				}
				else
				{
					window.bClockLoading = true;
					return BX.loadScript(['<?=CUtil::GetAdditionalFileURL("/bitrix/js/main/clock.js")?>'], function() {bxLoadClock_<?=$arParams['inputId']?>(callback)});
				}
			}

			window.bClockLoading = false;

			var obId = 'bxClock_<?=$arParams['inputId']?>';

			window[obId] = new JCClock({
				step: <?=$arParams['step']?>,
				initTime: '<?=$arParams['initTime']?>',
				showIcon: <? echo $arParams['showIcon'] ? 'true' : 'false';?>,
				inputId: '<?=$arParams['inputId']?>',
				iconId: '<?=$arParams['inputId'].'_icon'?>',
				zIndex: <?= isset($arParams['zIndex']) ? intval($arParams['zIndex']) : 0 ?>,
				AmPmMode: <? echo $arParams['am_pm_mode'] ? 'true' : 'false';?>,
				MESS: {
					Insert: '<?=GetMessageJS('BX_CLOCK_INSERT')?>',
					Close: '<?=GetMessageJS('BX_CLOCK_CLOSE')?>',
					Hours: '<?=GetMessageJS('BX_CLOCK_HOURS')?>',
					Minutes: '<?=GetMessageJS('BX_CLOCK_MINUTES')?>',
					Up: '<?=GetMessageJS('BX_CLOCK_UP')?>',
					Down: '<?=GetMessageJS('BX_CLOCK_DOWN')?>'
				}
				});

			return callback.apply(window, [window[obId]]);
		}

		function bxShowClock_<?=$arParams['inputId']?>(id)
		{
			bxLoadClock_<?=$arParams['inputId']?>(function(obClock)
			{
				obClock.Show(id);
			});
		}
	</script><?
	}
}
?>