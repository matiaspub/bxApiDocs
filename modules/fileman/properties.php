<?
IncludeModuleLangFile(__FILE__);

$GLOBALS['YANDEX_MAP_PROPERTY'] = array();

class CIBlockPropertyMapInterface
{
	public static function GetUserTypeDescription()
	{
		return array();
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		return '';
	}

	public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		return $value['VALUE'];
	}

	public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		return '';
	}

	public static function ConvertFromDB($arProperty, $value)
	{
		$arResult = array('VALUE' => '');

		if (strlen($value['VALUE']) > 0)
		{
			$arCoords = explode(',', $value['VALUE'], 2);

			$lat = doubleval($arCoords[0]);
			$lng = doubleval($arCoords[1]);

			if ($lat && $lng)
				$arResult['VALUE'] = $lat.','.$lng;
		}

		return $arResult;
	}

	public static function ConvertToDB($arProperty, $value)
	{
		$arResult = array('VALUE' => '');

		if (strlen($value['VALUE']) > 0)
		{
			$arCoords = explode(',', $value['VALUE'], 2);

			$lat = doubleval($arCoords[0]);
			$lng = doubleval($arCoords[1]);

			if ($lat && $lng)
				$arResult['VALUE'] = $lat.','.$lng;
		}

		return $arResult;
	}

	public static function _GetMapKey($map_type, &$strDomain)
	{
		$MAP_KEY = '';
		$strMapKeys = COPtion::GetOptionString('fileman', 'map_'.$map_type.'_keys');

		$strDomain = $_SERVER['HTTP_HOST'];
		$wwwPos = strpos($strDomain, 'www.');
		if ($wwwPos === 0)
			$strDomain = substr($strDomain, 4);

		if ($strMapKeys)
		{
			$arMapKeys = unserialize($strMapKeys);

			if (array_key_exists($strDomain, $arMapKeys))
				$MAP_KEY = $arMapKeys[$strDomain];
		}

		return $MAP_KEY;
	}
}

class CIBlockPropertyMapGoogle extends CIBlockPropertyMapInterface
{
	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "map_google",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_MAP_GOOGLE"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyMapGoogle","GetPropertyFieldHtml"),
			"GetPublicViewHTML" => array("CIBlockPropertyMapGoogle","GetPublicViewHTML"),
			"ConvertToDB" => array("CIBlockPropertyMapGoogle","ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyMapGoogle","ConvertFromDB"),
		);
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;

		static $googleMapLastNumber = 0;
		static $googleMapID = '';

		if (($arProperty['MULTIPLE'] == 'Y' && $googleMapID !== $arProperty['ID']) || $arProperty['MULTIPLE'] == 'N')
			$googleMapLastNumber = 0;

		if ($arProperty['MULTIPLE'] == 'Y')
			$googleMapID = $arProperty['ID'];

		if ($strHTMLControlName["MODE"] != "FORM_FILL")
			return '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName['VALUE']).'" value="'.htmlspecialcharsbx($value['VALUE']).'" />';

		if (strlen($value['VALUE']) > 0)
		{
			list($POINT_LAT, $POINT_LON) = explode(',', $value['VALUE'], 2);
			$bHasValue = true;
		}
		else
		{
			$POINT_LAT = doubleval(GetMessage('IBLOCK_PROP_MAP_GOOGLE_INIT_LAT'));
			$POINT_LON = doubleval(GetMessage('IBLOCK_PROP_MAP_GOOGLE_INIT_LON'));
			$bHasValue = false;
		}

		ob_start();

		if ($arProperty['MULTIPLE'] == 'Y' && isset($GLOBALS['GOOGLE_MAP_PROPERTY'][$arProperty['ID']]))
		{
			// property is multimple and map is already showed

			$MAP_ID = $GLOBALS['GOOGLE_MAP_PROPERTY'][$arProperty['ID']];
		}
		else
		{
			$MAP_ID = 'map_google_'.$arProperty['CODE'].$arProperty['ID'];
			$GLOBALS['GOOGLE_MAP_PROPERTY'][$arProperty['ID']] = $MAP_ID;

?>
<div id="bx_map_hint_<?echo $MAP_ID?>" style="display: none;">
	<div id="bx_map_hint_value_<?echo $MAP_ID?>" style="display: <?echo $bHasValue ? 'block' : 'none'?>;">
<?
		echo GetMessage('IBLOCK_PROP_MAP_GOOGLE_INSTR_VALUE').'<br /><br />';
?>
	</div>
	<div id="bx_map_hint_novalue_<?echo $MAP_ID?>" style="display: <?echo $bHasValue ? 'none' : 'block'?>;">
<?
		echo GetMessage('IBLOCK_PROP_MAP_GOOGLE_INSTR').'<br /><br />';
?>
	</div>
</div>
<?
		$APPLICATION->IncludeComponent(
			'bitrix:map.google.system',
			'',
			array(
				'INIT_MAP_TYPE' => 'NORMAL',
				'INIT_MAP_LON' => $POINT_LON ? $POINT_LON : 37.64,
				'INIT_MAP_LAT' => $POINT_LAT ? $POINT_LAT : 55.76,
				'INIT_MAP_SCALE' => 10,
				'OPTIONS' => array('ENABLE_SCROLL_ZOOM', 'ENABLE_DRAGGING'),
				'CONTROLS' => array('LARGE_MAP_CONTROL', 'HTYPECONTROL', 'MINIMAP', 'SCALELINE', 'SMALL_ZOOM_CONTROL'),
				'MAP_WIDTH' => '95%',
				'MAP_HEIGHT' => 400,
				'MAP_ID' => $MAP_ID,
				'DEV_MODE' => 'Y',
			),
			false, array('HIDE_ICONS' => 'Y')
		);

//http://jabber.bx/view.php?id=17908
?>
<script type="text/javascript">
	BX.ready(function(){
		var tabArea = BX.findParent(BX("BX_GMAP_<?=$MAP_ID?>"),{className:"adm-detail-content"});
		var tabButton = BX("tab_cont_"+tabArea.id);
		BX.bind(tabButton,"click", function() { BXMapGoogleAfterShow("<?=$MAP_ID?>"); });
	});

	<?if($arProperty['MULTIPLE'] == 'N'):?>
		function setPointValue_<?echo $MAP_ID?>(obPoint)
		{
			if (null == window.obPoint_<?echo $MAP_ID?>__n0_)
			{
				window.obPoint_<?echo $MAP_ID?>__n0_ = new google.maps.Marker({
					position: obPoint.latLng,
					map: window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'],
					draggable:true
				});

				google.maps.event.addListener(window.obPoint_<?echo $MAP_ID?>__n0_, "dragend", updatePointPosition_<?echo $MAP_ID?>__n0_);
			}
			else
			{
				window.obPoint_<?echo $MAP_ID?>__n0_.setPosition(obPoint.latLng);
			}

			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';
			BX('point_control_<?echo $MAP_ID?>__n0_').style.display = 'inline-block';

			updatePointPosition_<?echo $MAP_ID?>__n0_(obPoint);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(obPoint_<?echo $MAP_ID?>__n0_.getPosition());
		}
	<?else:?>
		function setPointValue_<?echo $MAP_ID?>(obPoint)
		{
			var i = 0, point = [], k = [];
			while (BX('point_<?echo $MAP_ID?>__n' + i + '_lat'))
			{
				if(BX('point_<?echo $MAP_ID?>__n' + i + '_lat').value == ''
					&& BX('point_<?echo $MAP_ID?>__n' + i + '_lon')
					&& BX('point_<?echo $MAP_ID?>__n' + i + '_lon').value == '')
				{
					k.push(i);
				}
				i++;
			}
			if (k.length <= 1)
			{
				window.addNewRow(BX('point_<?echo $MAP_ID?>__n0_lat').parentNode.parentNode.parentNode.parentNode.id);
			}
			k = (k.length) ? Math.min.apply(null, k) : i;
			var obPnt = 'obPoint_<?echo $MAP_ID?>__n'+k+'_',
				updPP = 'updatePointPosition_<?echo $MAP_ID?>__n'+k+'_';
			if(window[updPP])
			{
				window[obPnt] = null;

				window[obPnt] = new google.maps.Marker({
					position: obPoint.latLng,
					map: window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'],
					draggable:true
				});
				google.maps.event.addListener(window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_, "dragend", updatePointPosition_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_);
				window[updPP](obPoint);
			}

			BX('point_control_<?echo $MAP_ID?>__n'+k+'_').style.display = 'inline-block';

			updateMapHint_<?echo $MAP_ID?>();
		}
	<?endif;?>

	function updateMapHint_<?echo $MAP_ID?>()
	{
		var noValue = true,
			i = 0;
		while (BX('point_<?echo $MAP_ID?>__n' + i + '_lat'))
		{
			if (BX('point_<?echo $MAP_ID?>__n' + i + '_lat').value !== '' || !BX('point_<?echo $MAP_ID?>__n' + i + '_lon') || BX('point_<?echo $MAP_ID?>__n' + i + '_lon').value !=='')
				noValue = false;
			i++;
		}
		if (noValue)
		{
			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'block';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'none';
		}
		else
		{
			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';
		}
	}
</script>

<div id="bx_address_search_control_<?echo $MAP_ID?>" style="display: none;margin-top:15px;"><?echo GetMessage('IBLOCK_PROP_MAP_GOOGLE_SEARCH')?><input type="text" name="bx_address_<?echo $MAP_ID?>" id="bx_address_<?echo $MAP_ID?>" value="" style="width: 300px;" autocomplete="off" /></div>
<br />
<?
		}
?>
<input type="text" style="width:125px;margin:0 0 4px" name="point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lat" id="point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lat" onchange="setInputPointValue_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()" />, <input type="text" style="width:125px;margin:0 15px 4px 0;" name="point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lon" id="point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lon" onchange="setInputPointValue_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()" />
<div id="point_control_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_" style="display:none;margin:0 0 4px"><a href="javascript:void(0);" onclick="findPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()"><?echo GetMessage('IBLOCK_PROP_MAP_GOOGLE_GOTO_POINT')?></a> | <a href="javascript:void(0);" onclick="if (confirm('<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_GOOGLE_REMOVE_POINT_CONFIRM'))?>')) removePoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()"><?echo GetMessage('IBLOCK_PROP_MAP_GOOGLE_REMOVE_POINT')?></a></div><br />
<input type="text" style="display:none;" id="value_<?echo $MAP_ID;?>__n<?=$googleMapLastNumber?>_" name="<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" value="<?=htmlspecialcharsEx($value["VALUE"])?>" />
<script>
	window.jsAdminGoogleMess = {
		nothing_found: '<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_GOOGLE_NOTHING_FOUND'))?>'
	}
	BX.loadCSS('/bitrix/components/bitrix/map.google.view/settings/settings.css');

	function BXWaitForMap_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()
	{
		if (!window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'])
			setTimeout(BXWaitForMap_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_, 300);
		else
		{
			if(!window.markersBounds_<?echo $MAP_ID?>)
				window.markersBounds_<?echo $MAP_ID?> = new google.maps.LatLngBounds();
			window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_ = null;

			google.maps.event.clearListeners(window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'], 'dblclick');
			google.maps.event.addListener(window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'], 'dblclick', window.setPointValue_<?echo $MAP_ID?>);

			var searchInput = BX('bx_address_<?echo $MAP_ID?>');
			BX.bind(searchInput,"keydown", jsGoogleCESearch_<?echo $MAP_ID;?>.setTypingStarted);
			BX.bind(searchInput,"contextmenu", jsGoogleCESearch_<?echo $MAP_ID;?>.setTypingStarted);
			BX('point_control_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_').style.display = 'none';

			<?if ($bHasValue):?>
				setPointValue_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_({latLng: new google.maps.LatLng(<?echo $POINT_LAT?>, <?echo $POINT_LON?>)});
				window.markersBounds_<?echo $MAP_ID?>.extend(new google.maps.LatLng(<?echo $POINT_LAT?>, <?echo $POINT_LON?>));
				if (<?=$googleMapLastNumber?> > 0)
					window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].setCenter(window.markersBounds_<?echo $MAP_ID?>.getCenter(), window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].fitBounds(window.markersBounds_<?echo $MAP_ID?>));
			<?endif;?>

			BX('bx_address_search_control_<?echo $MAP_ID?>').style.display = 'block';
			BX('bx_map_hint_<?echo $MAP_ID?>').style.display = 'block';
		}
	}


function findPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()
{
	if (null != window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_)
		window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_.getPosition());
}

function removePoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()
{
	window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_.setMap(null);
	window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_ = null;

	BX('point_control_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_').style.display = 'none';

	updatePointPosition_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_();

	updateMapHint_<?echo $MAP_ID?>();
}

function setPointValue_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_(obEvent)
{
	var obPoint = BX.type.isArray(obEvent) ? {latLng: new google.maps.LatLng(obEvent[0], obEvent[1])} : {latLng: new google.maps.LatLng(obEvent.latLng.lat(), obEvent.latLng.lng())};

	if (null == window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_)
	{
		window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_ = new google.maps.Marker({
			position: obPoint.latLng,
			map: window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'],
			draggable:true
		});
		google.maps.event.addListener(window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_, "dragend", updatePointPosition_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_);
	}
	else
	{
		window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_.setPosition(obPoint.latLng);
	}

	BX('point_control_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_').style.display = 'inline-block';
	BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
	BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';

	updatePointPosition_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_(obPoint);
	window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_.getPosition());
}

function setInputPointValue_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_()
{
	var vv = [BX('point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lat').value, BX('point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lon').value];
	if (vv[0] == '' && vv[1] == '')
	{
		removePoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_();
	}
	var v = [parseFloat(BX('point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lat').value), parseFloat(BX('point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lon').value)];
	if (!isNaN(v[0]) && !isNaN(v[1]))
	{
		setPointValue_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_(v);
	}
}

function updatePointPosition_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_(obPoint)
{
	var obP = [];
	if (!!obPoint && !!obPoint.latLng)
		obP.push(obPoint.latLng.lat(), obPoint.latLng.lng());
	else if (!!window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_)
		obP.push(window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_.latLng.lat(), window.obPoint_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_.latLng.lng());
	else
		obP = null;
	var obInput = BX('value_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_');
	obInput.value = null == obP ? '' : obP[0] + ',' + obP[1];
	BX('point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lat').value = obP ? obP[0] : '';
	BX('point_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_lon').value = obP ? obP[1] : '';
}

	BX.ready(setTimeout(BXWaitForMap_<?echo $MAP_ID?>__n<?=$googleMapLastNumber?>_, 1));

var jsGoogleCESearch_<?echo $MAP_ID;?> = {
	bInited: false,

	map: null,
	geocoder: null,
	obInput: null,
	timerID: null,
	timerDelay: 1000,

	arSearchResults: [],

	obOut: null,

	__init: function(input)
	{
		if (jsGoogleCESearch_<?echo $MAP_ID;?>.bInited) return;

		jsGoogleCESearch_<?echo $MAP_ID;?>.map = window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'];
		jsGoogleCESearch_<?echo $MAP_ID;?>.obInput = input;

		//input.form.onsubmit = function() {jsGoogleCESearch_<?echo $MAP_ID;?>.doSearch(); return false;}

		input.onfocus = jsGoogleCESearch_<?echo $MAP_ID;?>.showResults;
		input.onblur = jsGoogleCESearch_<?echo $MAP_ID;?>.hideResults;

		jsGoogleCESearch_<?echo $MAP_ID;?>.bInited = true;
	},

	setTypingStarted: function(e)
	{
		if (null == e)
			e = window.event;

		if (e.keyCode == 13)
		{
			jsGoogleCESearch_<?echo $MAP_ID;?>.doSearch();
			return false;
		}
		else
		{

			if (!jsGoogleCESearch_<?echo $MAP_ID;?>.bInited)
				jsGoogleCESearch_<?echo $MAP_ID;?>.__init(this);

			if (e.type=="contextmenu")
					jsGoogleCESearch_<?echo $MAP_ID;?>.timerDelay=3000;
			else
					jsGoogleCESearch_<?echo $MAP_ID;?>.timerDelay=1000;

			jsGoogleCESearch_<?echo $MAP_ID;?>.hideResults();

			if (null != jsGoogleCESearch_<?echo $MAP_ID;?>.timerID)
				clearTimeout(jsGoogleCESearch_<?echo $MAP_ID;?>.timerID);

			jsGoogleCESearch_<?echo $MAP_ID;?>.timerID = setTimeout(jsGoogleCESearch_<?echo $MAP_ID;?>.doSearch, jsGoogleCESearch_<?echo $MAP_ID;?>.timerDelay);
		}
	},

	doSearch: function()
	{
		var value = jsUtils.trim(jsGoogleCESearch_<?echo $MAP_ID;?>.obInput.value);
		if (value.length > 1)
		{
			if (null == jsGoogleCESearch_<?echo $MAP_ID;?>.geocoder)
				jsGoogleCESearch_<?echo $MAP_ID;?>.geocoder = new google.maps.Geocoder();

			jsGoogleCESearch_<?echo $MAP_ID;?>.geocoder.geocode({
				address: value,
				language: '<?=LANGUAGE_ID?>'
			}, jsGoogleCESearch_<?echo $MAP_ID;?>.__searchResultsLoad);
		}
	},

	handleError: function()
	{
		alert(jsGoogleCE.jsMess.mess_error);
	},

	setResultsCoordinates: function()
	{
		var obPos = jsUtils.GetRealPos(jsGoogleCESearch_<?echo $MAP_ID;?>.obInput);
		jsGoogleCESearch_<?echo $MAP_ID;?>.obOut.style.top = (obPos.bottom + 2) + 'px';
		jsGoogleCESearch_<?echo $MAP_ID;?>.obOut.style.left = obPos.left + 'px';
	},

	__generateOutput: function()
	{
		jsGoogleCESearch_<?echo $MAP_ID;?>.obOut = document.body.appendChild(document.createElement('UL'));
		jsGoogleCESearch_<?echo $MAP_ID;?>.obOut.className = 'bx-google-address-search-results';
		jsGoogleCESearch_<?echo $MAP_ID;?>.setResultsCoordinates();
	},

	__searchResultsLoad: function(obResult, status)
	{
		var _this = jsGoogleCESearch_<?echo $MAP_ID;?>;

		if (status != google.maps.GeocoderStatus.OK && status != google.maps.GeocoderStatus.ZERO_RESULTS)
		{
			_this.handleError({message: status});
			return;
		}

		if (!obResult)
		{
			_this.handleError();
		}
		else
		{
			if (null == _this.obOut)
				_this.__generateOutput();

			_this.obOut.innerHTML = '';
			_this.clearSearchResults();

			var len = 0;
			if (status == google.maps.GeocoderStatus.OK)
			{
				len = obResult.length;
				var obList = null;
			}

			if (len > 0)
			{
				for (var i = 0; i < len; i++)
				{
					_this.arSearchResults[i] = obResult[i].geometry;

					var lnk_text = '';
					for (var j = 0; j < obResult[i].address_components.length; j++)
					{
						lnk_text += (lnk_text.length > 0 ? ', ' : '') + obResult[i].address_components[j].long_name;
					}

					_this.obOut.appendChild(BX.create('LI', {
						attrs: {className: i == 0 ? 'bx-google-first' : ''},
						children: [
							BX.create('A', {
								attrs: {href: "javascript:void(0)"},
								props: {BXSearchIndex: i},
								events: {click: _this.__showSearchResult},
								children: [
									BX.create('SPAN', {
										text: lnk_text
									})
								]
							})
						]
					}));
				}
			}
			else
			{
				_this.obOut.innerHTML = '<li class="bx-google-notfound">' + window.jsAdminGoogleMess.nothing_found + '</li>';
			}

			_this.showResults();
		}
	},

	__showSearchResult: function()
	{
		if (null !== this.BXSearchIndex)
		{
			jsGoogleCESearch_<?echo $MAP_ID;?>.map.setCenter(jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults[this.BXSearchIndex].location);
			if (jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults[this.BXSearchIndex].viewport)
				jsGoogleCESearch_<?echo $MAP_ID;?>.map.fitBounds(jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults[this.BXSearchIndex].viewport);
		}
	},

	showResults: function()
	{
		if (null != jsGoogleCESearch_<?echo $MAP_ID;?>.obOut)
		{
			jsGoogleCESearch_<?echo $MAP_ID;?>.setResultsCoordinates();
			jsGoogleCESearch_<?echo $MAP_ID;?>.obOut.style.display = 'block';
		}
	},

	hideResults: function()
	{
		if (null != jsGoogleCESearch_<?echo $MAP_ID;?>.obOut)
		{
			setTimeout("jsGoogleCESearch_<?echo $MAP_ID;?>.obOut.style.display = 'none'", 300);
		}
	},

	clearSearchResults: function()
	{
		for (var i = 0; i < jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults.length; i++)
		{
			delete jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults[i];
		}

		jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults = [];
	},

	clear: function()
	{
		if (!jsGoogleCESearch_<?echo $MAP_ID;?>.bInited)
			return;

		jsGoogleCESearch_<?echo $MAP_ID;?>.bInited = false;
		if (null != jsGoogleCESearch_<?echo $MAP_ID;?>.obOut)
		{
			jsGoogleCESearch_<?echo $MAP_ID;?>.obOut.parentNode.removeChild(jsGoogleCESearch_<?echo $MAP_ID;?>.obOut);
			jsGoogleCESearch_<?echo $MAP_ID;?>.obOut = null;
		}

		jsGoogleCESearch_<?echo $MAP_ID;?>.arSearchResults = [];
		jsGoogleCESearch_<?echo $MAP_ID;?>.map = null;
		jsGoogleCESearch_<?echo $MAP_ID;?>.geocoder = null;
		jsGoogleCESearch_<?echo $MAP_ID;?>.obInput = null;
		jsGoogleCESearch_<?echo $MAP_ID;?>.timerID = null;
	}
}
</script>
<?
	$out = ob_get_contents();
	ob_end_clean();

	if ($arProperty['MULTIPLE'] == 'Y')
		$googleMapLastNumber++;
		
	return $out;
	}

	public static function GetPublicViewHTML($arProperty, $value, $arParams)
	{
		$s = '';
		if(strlen($value["VALUE"])>0)
		{
			$value = parent::ConvertFromDB($arProperty, $value);
			if ($arParams['MODE'] == 'CSV_EXPORT')
			{
				$s = $value;
			}
			else
			{
				$googleMapLastNumber = 0;

				$arCoords = explode(',', $value['VALUE']);
				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent(
					'bitrix:map.google.view',
					'',
					array(
						'MAP_DATA' => serialize(array(
							'google_lat' => $arCoords[0],
							'google_lon' => $arCoords[1],
							'PLACEMARKS' => array(
								array(
									'LON' => $arCoords[1],
									'LAT' => $arCoords[0],
								),
							),
						)),
						'MAP_ID' => 'MAP_GOOGLE_VIEW_'.$arProperty['IBLOCK_ID'].'_'.$arProperty['ID'].'__n'.$googleMapLastNumber.'_',
						'DEV_MODE' => 'Y',
					),
					false, array('HIDE_ICONS' => 'Y')
				);

				$s .= ob_get_contents();
				ob_end_clean();
			}
		}

		return $s;
	}
}

class CIBlockPropertyMapYandex extends CIBlockPropertyMapInterface
{
	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "map_yandex",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_MAP_YANDEX"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyMapYandex", "GetPropertyFieldHtml"),
			"GetPublicViewHTML"	=> array("CIBlockPropertyMapYandex", "GetPublicViewHTML"),
			"GetPublicEditHTML"	=> array("CIBlockPropertyMapYandex", "GetPublicEditHTML"),
			"ConvertToDB" => array("CIBlockPropertyMapYandex", "ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyMapYandex", "ConvertFromDB"),
		);
	}

	public static function _DrawKeyInputControl($MAP_ID, $strDomain)
	{
		echo BeginNote();
?>
<div id="key_input_control_<?echo $MAP_ID?>">
		<?echo str_replace('#DOMAIN#', $strDomain, GetMessage('IBLOCK_PROP_MAP_YANDEX_NO_KEY_MESSAGE'))?><br /><br />
		<?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_NO_KEY')?><input type="text" name="map_yandex_key_<?echo $MAP_ID?>" id="map_yandex_key_<?echo $MAP_ID?>" /> <input type="button" value="<?echo htmlspecialcharsbx(GetMessage('IBLOCK_PROP_MAP_YANDEX_NO_KEY_BUTTON'))?>" onclick="setYandexKey('<?echo $strDomain?>', 'map_yandex_key_<?echo $MAP_ID?>')" /> <input type="button" value="<?echo htmlspecialcharsbx(GetMessage('IBLOCK_PROP_MAP_YANDEX_SAVE_KEY_BUTTON'))?>" onclick="saveYandexKey('<?echo $strDomain?>', 'map_yandex_key_<?echo $MAP_ID?>')" />
</div>
<div id="key_input_message_<?echo $MAP_ID?>" style="display: none;"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_NO_KEY_OKMESSAGE')?></div>
<?
		echo EndNote();
?>
<script type="text/javascript">
function setYandexKey(domain, input)
{
	LoadMap_<?echo $MAP_ID?>(document.getElementById(input).value);
}

function saveYandexKey(domain, input)
{
	var value = document.getElementById(input).value;

	CHttpRequest.Action = function(result)
	{
		CloseWaitWindow();
		if (result == 'OK')
		{
			document.getElementById('key_input_control_<?echo $MAP_ID?>').style.display = 'none';
			document.getElementById('key_input_message_<?echo $MAP_ID?>').style.display = 'block';
			if (!window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'])
				setYandexKey(domain, input);
		}
		else
			alert('<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_YANDEX_NO_KEY_ERRORMESSAGE'))?>');
	}

	var data = 'key_type=yandex&domain=' + domain + '&key=' + value + '&<?echo bitrix_sessid_get()?>';
	ShowWaitWindow();
	CHttpRequest.Post('/bitrix/admin/settings.php?lang=<?echo LANGUAGE_ID?>&mid=fileman&save_map_key=Y', data);
}
</script>
<?
	} // _DrawKeyInputControl()

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;

		static $yandexMapLastNumber = 0;
		static $yandexMapID = '';

		if (($arProperty['MULTIPLE'] == 'Y' && $yandexMapID !== $arProperty['ID']) || $arProperty['MULTIPLE'] == 'N')
			$yandexMapLastNumber = 0;

		if ($arProperty['MULTIPLE'] == 'Y')
			$yandexMapID = $arProperty['ID'];

		// TODO: remove this later to use in property default value setting
		if ($strHTMLControlName["MODE"] != "FORM_FILL")
			return '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName['VALUE']).'" value="'.htmlspecialcharsbx($value['VALUE']).'" />';

		if (strlen($value['VALUE']) > 0)
		{
			list($POINT_LAT, $POINT_LON) = explode(',', $value['VALUE'], 2);
			$bHasValue = true;
		}
		else
		{
			$POINT_LAT = doubleval(GetMessage('IBLOCK_PROP_MAP_YANDEX_INIT_LAT'));
			$POINT_LON = doubleval(GetMessage('IBLOCK_PROP_MAP_YANDEX_INIT_LON'));
			$bHasValue = false;
		}
		ob_start();

		if ($arProperty['MULTIPLE'] == 'Y' && isset($GLOBALS['YANDEX_MAP_PROPERTY'][$arProperty['ID']]))
		{
			// property is multimple and map is already showed

			$MAP_ID = $GLOBALS['YANDEX_MAP_PROPERTY'][$arProperty['ID']];
		}
		else
		{
			$MAP_ID = 'map_yandex_'.$arProperty['CODE'].'_'.$arProperty['ID'];
			$GLOBALS['YANDEX_MAP_PROPERTY'][$arProperty['ID']] = $MAP_ID;


?>
<div id="bx_map_hint_<?echo $MAP_ID?>" style="display: none;">
	<div id="bx_map_hint_value_<?echo $MAP_ID?>" style="display: <?echo $bHasValue ? 'block' : 'none'?>;">
<?
			echo GetMessage('IBLOCK_PROP_MAP_YANDEX_INSTR_VALUE').'<br /><br />';
?>
	</div>
	<div id="bx_map_hint_novalue_<?echo $MAP_ID?>" style="display: <?echo $bHasValue ? 'none' : 'block'?>;">
<?
			echo GetMessage('IBLOCK_PROP_MAP_YANDEX_INSTR').'<br /><br />';
?>
	</div>
</div>
<?
			$APPLICATION->IncludeComponent(
				'bitrix:map.yandex.system',
				'',
				array(
					'INIT_MAP_TYPE' => 'MAP',
					'INIT_MAP_LON' => $POINT_LON ? $POINT_LON : 37.64,
					'INIT_MAP_LAT' => $POINT_LAT ? $POINT_LAT : 55.76,
					'INIT_MAP_SCALE' => 10,
					'OPTIONS' => array('ENABLE_SCROLL_ZOOM', 'ENABLE_DRAGGING'),
					'CONTROLS' => array('ZOOM', 'MINIMAP', 'TYPECONTROL', 'SCALELINE'),
					'MAP_WIDTH' => '95%',
					'MAP_HEIGHT' => 400,
					'MAP_ID' => $MAP_ID,
					'DEV_MODE' => 'Y',
					//'ONMAPREADY' => 'BXWaitForMap_'.$MAP_ID
				),
				false, array('HIDE_ICONS' => 'Y')
			);

//http://jabber.bx/view.php?id=17908
?>
<script type="text/javascript">
	BX.ready(function(){
		var tabArea = BX.findParent(BX("BX_YMAP_<?=$MAP_ID?>"),{className:"adm-detail-content"});
		var tabButton = BX("tab_cont_"+tabArea.id);
		BX.bind(tabButton,"click", function() { BXMapYandexAfterShow("<?=$MAP_ID?>"); });
	});

	<?if($arProperty['MULTIPLE'] == 'N'):?>
		function setPointValue_<?echo $MAP_ID?>(obEvent)
		{
			var obPoint = BX.type.isArray(obEvent) ? obEvent : obEvent.get("coordPosition");

			if (null == window.obPoint_<?echo $MAP_ID?>__n0_)
			{
				window.obPoint_<?echo $MAP_ID?>__n0_ = new ymaps.Placemark(obPoint, {}, {draggable:true});
				window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.add(window.obPoint_<?echo $MAP_ID?>__n0_);
				window.obPoint_<?echo $MAP_ID?>__n0_.events.add('dragend', updatePointPosition_<?echo $MAP_ID?>__n0_);
			}
			else
			{
				window.obPoint_<?echo $MAP_ID?>__n0_.geometry.setCoordinates(obPoint);
			}

			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';
			BX('point_control_<?echo $MAP_ID?>__n0_').style.display = 'inline-block';

			updatePointPosition_<?echo $MAP_ID?>__n0_(obPoint);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(obPoint_<?echo $MAP_ID?>__n0_.geometry.getCoordinates(), {delay:0});
		}
	<?else:?>
		function setPointValue_<?echo $MAP_ID?>(obEvent)
		{
			var obPoint = BX.type.isArray(obEvent) ? obEvent : obEvent.get("coordPosition");
			var i = 0, point = [], k = [];
			while (BX('point_<?echo $MAP_ID?>__n' + i + '_lat'))
			{
				if(BX('point_<?echo $MAP_ID?>__n' + i + '_lat').value == ''
					&& BX('point_<?echo $MAP_ID?>__n' + i + '_lon')
					&& BX('point_<?echo $MAP_ID?>__n' + i + '_lon').value == '')
				{
					k.push(i);
				}
				i++;
			}
			if (k.length <= 1)
			{
				window.addNewRow(BX('point_<?echo $MAP_ID?>__n0_lat').parentNode.parentNode.parentNode.parentNode.id);
			}
			k = (k.length) ? Math.min.apply(null, k) : i;
			var obPnt = 'obPoint_<?echo $MAP_ID?>__n'+k+'_',
				updPP = 'updatePointPosition_<?echo $MAP_ID?>__n'+k+'_';
			if(window[updPP])
			{
				window[obPnt] = null;
				window[obPnt] = new ymaps.Placemark(obPoint, {}, {draggable:true});
				window.GLOBAL_arMapObjects["<?echo $MAP_ID?>"].geoObjects.add(window[obPnt]);
				window[obPnt].events.add("dragend", window[updPP]);
				window[updPP](obPoint);
			}

			BX('point_control_<?echo $MAP_ID?>__n'+k+'_').style.display = 'inline-block';

			updateMapHint_<?echo $MAP_ID?>();
		}
	<?endif;?>

	function setDefaultPreset_<?echo $MAP_ID?>()
	{
		if(window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects)
		{
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.each(function (geoObject) {
				geoObject.options.set({preset: 'twirl#blueIcon'});
			});
		}
	}

	function updateMapHint_<?echo $MAP_ID?>()
	{
		var noValue = true,
			i = 0;
		while (BX('point_<?echo $MAP_ID?>__n' + i + '_lat'))
		{
			if (BX('point_<?echo $MAP_ID?>__n' + i + '_lat').value !== '' || !BX('point_<?echo $MAP_ID?>__n' + i + '_lon') || BX('point_<?echo $MAP_ID?>__n' + i + '_lon').value !=='')
				noValue = false;
			i++;
		}
		if (noValue)
		{
			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'block';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'none';
		}
		else
		{
			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';
		}
	}
</script>

<div id="bx_address_search_control_<?echo $MAP_ID?>" style="display: none;margin-top:15px;"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_SEARCH')?><input type="text" name="bx_address_<?echo $MAP_ID?>" id="bx_address_<?echo $MAP_ID?>" value="" style="width: 300px;" autocomplete="off" /></div>
<br />
<?
		}
?>
<input type="text" style="width:125px;margin:0 0 4px" name="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat" id="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat" onchange="setInputPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()" />, <input type="text" style="width:125px;margin:0 15px 4px 0;" name="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon" id="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon" onchange="setInputPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()" />
<div id="point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_" style="display:none;margin:0 0 4px"><a href="javascript:void(0);" onclick="findPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_GOTO_POINT')?></a> | <a href="javascript:void(0);" onclick="if (confirm('<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_YANDEX_REMOVE_POINT_CONFIRM'))?>')) removePoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_REMOVE_POINT')?></a></div><br />
<input type="text" style="display:none;" id="value_<?echo $MAP_ID;?>__n<?=$yandexMapLastNumber?>_" name="<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" value="<?=htmlspecialcharsEx($value["VALUE"])?>" />
<script>
	window.jsAdminYandexMess = {
		nothing_found: '<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_YANDEX_NOTHING_FOUND'))?>'
	}
	jsUtils.loadCSSFile('/bitrix/components/bitrix/map.yandex.view/settings/settings.css');

	function BXWaitForMap_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		if (!window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'])
			setTimeout(BXWaitForMap_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_, 300);
		else
		{
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_ = null;

			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].events.remove('dblclick', window.setPointValue_<?echo $MAP_ID?>);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].events.add('dblclick', window.setPointValue_<?echo $MAP_ID?>);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].events.add('click', window.setDefaultPreset_<?echo $MAP_ID?>);
			var searchInput = BX('bx_address_<?echo $MAP_ID?>');
			BX.bind(searchInput, "keydown", jsYandexCESearch_<?echo $MAP_ID;?>.setTypingStarted);
			BX.bind(searchInput, "contextmenu", jsYandexCESearch_<?echo $MAP_ID;?>.setTypingStarted);
			BX('point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_').style.display = 'none';

			<?if ($bHasValue):?>
				setPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_([<?echo $POINT_LAT?>, <?echo $POINT_LON?>]);
				if (<?=$yandexMapLastNumber?> > 0)
					window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].setBounds(window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.getBounds(), {checkZoomRange: true});
			<?endif;?>

			BX('bx_address_search_control_<?echo $MAP_ID?>').style.display = 'block';
			BX('bx_map_hint_<?echo $MAP_ID?>').style.display = 'block';

		}
	}

	function findPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		if (null != window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_)
		{
			window.setDefaultPreset_<?echo $MAP_ID?>();
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.getCoordinates(),{delay:0});
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.options.set({preset: 'twirl#redIcon'});

		}
	}

	function removePoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.remove(window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_);
		window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_ = null;

		BX('point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_').style.display = 'none';

		updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_();

		updateMapHint_<?echo $MAP_ID?>();
	}

	function setPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(obEvent)
	{
		var obPoint = BX.type.isArray(obEvent) ? obEvent : obEvent.get("coordPosition");

		if (null == window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_)
		{
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_ = new ymaps.Placemark(obPoint, {}, {draggable:true});
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.add(window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_);
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.events.add('dragend', updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_);
		}
		else
		{
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.setCoordinates(obPoint);
		}

		BX('point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_').style.display = 'inline-block';
		BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
		BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';

		updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(obPoint);
		window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.getCoordinates(),{delay:0});
	}

	function setInputPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		var vv = [BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat').value, BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon').value];
		if (vv[0] == '' && vv[1] == '')
		{
			removePoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_();
		}
		var v = [parseFloat(BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat').value), parseFloat(BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon').value)];
		if (!isNaN(v[0]) && !isNaN(v[1]))
		{
			setPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(v);
		}
	}

	function updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(obPoint)
	{
		//var obPosition = obPoint.getGeoPoint();
		if (!!obPoint && !!obPoint.geometry)
			obPoint = obPoint.geometry.getCoordinates();
		else if (!!window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_)
			obPoint = window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.getCoordinates();
		else
			obPoint = null;

		var obInput = BX('value_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_');
		obInput.value = null == obPoint ? '' : obPoint[0] + ',' + obPoint[1];

		BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat').value = obPoint ? obPoint[0] : '';
		BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon').value = obPoint ? obPoint[1] : '';
	}

	BX.ready(setTimeout(BXWaitForMap_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_, 1));
	var jsYandexCESearch_<?echo $MAP_ID;?> = {

		bInited: false,

		map: null,
		geocoder: null,
		obInput: null,
		timerID: null,
		timerDelay: 1000,

		arSearchResults: [],
		strLastSearch: null,

		obOut: null,

		__init: function(input)
		{
			if (jsYandexCESearch_<?echo $MAP_ID;?>.bInited) return;

			jsYandexCESearch_<?echo $MAP_ID;?>.map = window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'];
			jsYandexCESearch_<?echo $MAP_ID;?>.obInput = input;

			input.onfocus = jsYandexCESearch_<?echo $MAP_ID;?>.showResults;
			input.onblur = jsYandexCESearch_<?echo $MAP_ID;?>.hideResults;

			jsYandexCESearch_<?echo $MAP_ID;?>.bInited = true;
		},

		setTypingStarted: function(e)
		{
			if (null == e)
				e = window.event;

			jsYandexCESearch_<?echo $MAP_ID;?>.hideResults();

			if (e.keyCode == 13 )
			{
				jsYandexCESearch_<?echo $MAP_ID;?>.doSearch();
				return false;
			}
			else
			{
				if (!jsYandexCESearch_<?echo $MAP_ID;?>.bInited)
					jsYandexCESearch_<?echo $MAP_ID;?>.__init(this);

				if (e.type=="contextmenu")
					jsYandexCESearch_<?echo $MAP_ID;?>.timerDelay=3000;
				else
					jsYandexCESearch_<?echo $MAP_ID;?>.timerDelay=1000;

				if (null != jsYandexCESearch_<?echo $MAP_ID;?>.timerID)
					clearTimeout(jsYandexCESearch_<?echo $MAP_ID;?>.timerID);

				jsYandexCESearch_<?echo $MAP_ID;?>.timerID = setTimeout(jsYandexCESearch_<?echo $MAP_ID;?>.doSearch, jsYandexCESearch_<?echo $MAP_ID;?>.timerDelay);
			}
		},

		doSearch: function()
		{
			this.strLastSearch = jsUtils.trim(jsYandexCESearch_<?echo $MAP_ID;?>.obInput.value);

			if (this.strLastSearch.length > 1)
			{
				ymaps.geocode(this.strLastSearch).then(
					jsYandexCESearch_<?echo $MAP_ID;?>.__searchResultsLoad,
					jsYandexCESearch_<?echo $MAP_ID;?>.handleError
				);
			}
		},

		handleError: function(error)
		{
			alert(this.jsMess.mess_error + ': ' + error.message);
		},

		setResultsCoordinates: function()
		{
			var obPos = jsUtils.GetRealPos(jsYandexCESearch_<?echo $MAP_ID;?>.obInput);
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.top = (obPos.bottom + 2) + 'px';
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.left = obPos.left + 'px';
		},

		__generateOutput: function()
		{
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut = document.body.appendChild(document.createElement('UL'));
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut.className = 'bx-yandex-address-search-results';
		},

		__searchResultsLoad: function(res)
		{
			var _this = jsYandexCESearch_<?echo $MAP_ID;?>;

			if (null == _this.obOut)
				_this.__generateOutput();

			_this.obOut.innerHTML = '';
			_this.clearSearchResults();

			var len = res.geoObjects.getLength();
			if (len > 0)
			{
				for (var i = 0; i < len; i++)
				{
					_this.arSearchResults[i] = res.geoObjects.get(i);

					var obListElement = document.createElement('LI');

					if (i == 0)
						obListElement.className = 'bx-yandex-first';

					var obLink = document.createElement('A');
					obLink.href = "javascript:void(0)";
					var obText = obLink.appendChild(document.createElement('SPAN'));
					obText.appendChild(document.createTextNode(
						jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults[i].properties.get('metaDataProperty').GeocoderMetaData.text
					));

					obLink.BXSearchIndex = i;
					obLink.onclick = _this.__showSearchResult;

					obListElement.appendChild(obLink);
					_this.obOut.appendChild(obListElement);
				}
			}
			else
			{
				//var str = _this.jsMess.mess_search_empty;
				_this.obOut.innerHTML = '<li class="bx-yandex-notfound">' + window.jsAdminYandexMess.nothing_found + '</li>';
			}

			_this.showResults();
		},

		__showSearchResult: function()
		{
			if (null !== this.BXSearchIndex)
			{
				var bounds =  jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults[this.BXSearchIndex].properties.get('boundedBy');
				jsYandexCESearch_<?echo $MAP_ID;?>.map.setBounds(bounds, { checkZoomRange: true });
			}
		},

		showResults: function()
		{
			if(this.strLastSearch!=jsUtils.trim(jsYandexCESearch_<?echo $MAP_ID;?>.obInput.value))
				jsYandexCESearch_<?echo $MAP_ID;?>.doSearch();

			if (null != jsYandexCESearch_<?echo $MAP_ID;?>.obOut)
			{
				jsYandexCESearch_<?echo $MAP_ID;?>.setResultsCoordinates();
				jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.display = 'block';
			}
		},

		hideResults: function()
		{
			if (null != jsYandexCESearch_<?echo $MAP_ID;?>.obOut)
			{
				setTimeout("jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.display = 'none'", 300);
			}
		},

		clearSearchResults: function()
		{
			for (var i = 0; i < jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults.length; i++)
			{
				delete jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults[i];
			}

			jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults = [];
		},

		clear: function()
		{
			if (!jsYandexCESearch_<?echo $MAP_ID;?>.bInited)
				return;

			jsYandexCESearch_<?echo $MAP_ID;?>.bInited = false;
			if (null != jsYandexCESearch_<?echo $MAP_ID;?>.obOut)
			{
				jsYandexCESearch_<?echo $MAP_ID;?>.obOut.parentNode.removeChild(jsYandexCESearch_<?echo $MAP_ID;?>.obOut);
				jsYandexCESearch_<?echo $MAP_ID;?>.obOut = null;
			}

			jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults = [];
			jsYandexCESearch_<?echo $MAP_ID;?>.map = null;
			jsYandexCESearch_<?echo $MAP_ID;?>.geocoder = null;
			jsYandexCESearch_<?echo $MAP_ID;?>.obInput = null;
			jsYandexCESearch_<?echo $MAP_ID;?>.timerID = null;
		}
	}

</script>
<?
		$out = ob_get_contents();
		ob_end_clean();

		if ($arProperty['MULTIPLE'] == 'Y')
			$yandexMapLastNumber++;

		return $out;
	}

	public static function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;

		static $yandexMapLastNumber = 0;
		static $yandexMapID = '';

		if (($arProperty['MULTIPLE'] == 'Y' && $yandexMapID !== $arProperty['ID']) || $arProperty['MULTIPLE'] == 'N')
			$yandexMapLastNumber = 0;

		if ($arProperty['MULTIPLE'] == 'Y')
			$yandexMapID = $arProperty['ID'];

		if (strlen($value['VALUE']) > 0)
		{
			list($POINT_LAT, $POINT_LON) = explode(',', $value['VALUE'], 2);
			$bHasValue = true;
		}
		else
		{
			$POINT_LAT = doubleval(GetMessage('IBLOCK_PROP_MAP_YANDEX_INIT_LAT'));
			$POINT_LON = doubleval(GetMessage('IBLOCK_PROP_MAP_YANDEX_INIT_LON'));
			$bHasValue = false;
		}
		ob_start();?>
		<div>
				<?
		if ($arProperty['MULTIPLE'] == 'Y' && isset($GLOBALS['YANDEX_MAP_PROPERTY'][$arProperty['ID']]))
		{
			$MAP_ID = $GLOBALS['YANDEX_MAP_PROPERTY'][$arProperty['ID']];
		}
		else
		{
			$MAP_ID = 'map_yandex_'.$arProperty['CODE'].'_'.$arProperty['ID'];
			$GLOBALS['YANDEX_MAP_PROPERTY'][$arProperty['ID']] = $MAP_ID;


?>
<div id="bx_map_hint_<?echo $MAP_ID?>" style="display: none;">
	<div id="bx_map_hint_value_<?echo $MAP_ID?>" style="display: <?echo $bHasValue ? 'block' : 'none'?>;">
<?
			echo GetMessage('IBLOCK_PROP_MAP_YANDEX_INSTR_VALUE').'<br /><br />';
?>
	</div>
	<div id="bx_map_hint_novalue_<?echo $MAP_ID?>" style="display: <?echo $bHasValue ? 'none' : 'block'?>;">
<?
			echo GetMessage('IBLOCK_PROP_MAP_YANDEX_INSTR').'<br /><br />';
?>
	</div>
</div>
<?
			$APPLICATION->IncludeComponent(
				'bitrix:map.yandex.system',
				'',
				array(
					'INIT_MAP_TYPE' => 'MAP',
					'INIT_MAP_LON' => $POINT_LON ? $POINT_LON : 37.64,
					'INIT_MAP_LAT' => $POINT_LAT ? $POINT_LAT : 55.76,
					'INIT_MAP_SCALE' => 10,
					'OPTIONS' => array('ENABLE_SCROLL_ZOOM', 'ENABLE_DRAGGING'),
					'CONTROLS' => array('ZOOM', 'MINIMAP', 'TYPECONTROL', 'SCALELINE'),
					'MAP_WIDTH' => 450,
					'MAP_HEIGHT' => 400,
					'MAP_ID' => $MAP_ID,
					'DEV_MODE' => 'Y'
				),
				false, array('HIDE_ICONS' => 'Y')
			);
?>
<script type="text/javascript">
	BX.ready(function(){
		var tabArea = BX.findParent(BX("BX_YMAP_<?=$MAP_ID?>"),{className:"adm-detail-content"});
		var tabButton = BX("tab_cont_"+tabArea.id);
		BX.bind(tabButton,"click", function() { BXMapYandexAfterShow("<?=$MAP_ID?>"); });
	});

	<?if($arProperty['MULTIPLE'] == 'N'):?>
		function setPointValue_<?echo $MAP_ID?>(obEvent)
		{
			var obPoint = BX.type.isArray(obEvent) ? obEvent : obEvent.get("coordPosition");

			if (null == window.obPoint_<?echo $MAP_ID?>__n0_)
			{
				window.obPoint_<?echo $MAP_ID?>__n0_ = new ymaps.Placemark(obPoint, {}, {draggable:true});
				window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.add(window.obPoint_<?echo $MAP_ID?>__n0_);
				window.obPoint_<?echo $MAP_ID?>__n0_.events.add('dragend', updatePointPosition_<?echo $MAP_ID?>__n0_);
			}
			else
			{
				window.obPoint_<?echo $MAP_ID?>__n0_.geometry.setCoordinates(obPoint);
			}

			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';
			BX('point_control_<?echo $MAP_ID?>__n0_').style.display = 'inline-block';

			updatePointPosition_<?echo $MAP_ID?>__n0_(obPoint);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(obPoint_<?echo $MAP_ID?>__n0_.geometry.getCoordinates(), {delay:0});
		}
	<?else:?>
		function setPointValue_<?echo $MAP_ID?>(obEvent)
		{
			var obPoint = BX.type.isArray(obEvent) ? obEvent : obEvent.get("coordPosition");
			var i = 0, point = [], k = [];
			while (BX('point_<?echo $MAP_ID?>__n' + i + '_lat'))
			{
				if(BX('point_<?echo $MAP_ID?>__n' + i + '_lat').value == ''
					&& BX('point_<?echo $MAP_ID?>__n' + i + '_lon')
					&& BX('point_<?echo $MAP_ID?>__n' + i + '_lon').value == '')
				{
					k.push(i);
				}
				i++;
			}
			if (k.length <= 1)
			{
				BX('point_<?echo $MAP_ID?>__n0_lat').parentNode.parentNode.id = '<?echo $MAP_ID?>';
				addNewRow_<?echo $MAP_ID?>(BX('point_<?echo $MAP_ID?>__n0_lat').parentNode.parentNode.id);
			}
			k = (k.length) ? Math.min.apply(null, k) : i;
			var obPnt = 'obPoint_<?echo $MAP_ID?>__n'+k+'_',
				updPP = 'updatePointPosition_<?echo $MAP_ID?>__n'+k+'_';
			if(window[updPP])
			{
				window[obPnt] = null;
				window[obPnt] = new ymaps.Placemark(obPoint, {}, {draggable:true});
				window.GLOBAL_arMapObjects["<?echo $MAP_ID?>"].geoObjects.add(window[obPnt]);
				window[obPnt].events.add("dragend", window[updPP]);
				window[updPP](obPoint);
			}

			BX('point_control_<?echo $MAP_ID?>__n'+k+'_').style.display = 'inline-block';

			updateMapHint_<?echo $MAP_ID?>();
		}
	<?endif;?>

	function addNewRow_<?echo $MAP_ID?>(tdID, row_to_clone)
	{
		var TD = document.getElementById(tdID);
		var cnt = BX.findChildren(TD, {tag : 'DIV'}, false).length;
		var sHTML = BX.findChildren(TD, {tag : 'DIV'}, false)[cnt-1].innerHTML;
		var oDiv = TD.appendChild(document.createElement('div'));
		oDiv.parentNode.appendChild(document.createElement('br'));

		var s, e, n, p;
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('[n',p);
			if(s<0)break;
			e = sHTML.indexOf(']',s);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+2,e-s));
			sHTML = sHTML.substr(0, s)+'[n'+(++n)+']'+sHTML.substr(e+1);
			p=s+1;
		}
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('__n',p);
			if(s<0)break;
			e = sHTML.indexOf('_',s+2);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+3,e-s));
			sHTML = sHTML.substr(0, s)+'__n'+(++n)+'_'+sHTML.substr(e+1);
			p=e+1;
		}
		p = 0;
		while(true)
		{
			s = sHTML.indexOf('__N',p);
			if(s<0)break;
			e = sHTML.indexOf('__',s+2);
			if(e<0)break;
			n = parseInt(sHTML.substr(s+3,e-s));
			sHTML = sHTML.substr(0, s)+'__N'+(++n)+'__'+sHTML.substr(e+2);
			p=e+2;
		}
		oDiv.innerHTML = sHTML;

		var inputName = document.getElementById('value_<?echo $MAP_ID;?>__n<?=$yandexMapLastNumber?>_').name;
		p = 0;
		s = inputName.indexOf('][',p);
		e = inputName.indexOf(']',s+1);
		inputName = inputName.substr(0, s)+']['+(++n)+']'+inputName.substr(e+1);
		document.getElementById('value_<?echo $MAP_ID;?>__n' + --n + '_').name = inputName;

		var patt = new RegExp ("<"+"script"+">[^\000]*?<"+"\/"+"script"+">", "ig");
		var code = sHTML.match(patt);
		if(code)
		{
			for(var i = 0; i < code.length; i++)
			{
				if(code[i] != '')
				{
					s = code[i].substring(8, code[i].length-9);
					jsUtils.EvalGlobal(s);
				}
			}
		}

		if (BX && BX.adminPanel)
		{
			BX.adminPanel.modifyFormElements(oDiv);
			BX.onCustomEvent('onAdminTabsChange');
		}

		setTimeout(function() {
			var r = BX.findChildren(oDiv, {tag: /^(input|select|textarea)$/i});
			if (r && r.length > 0)
			{
				for (var i=0,l=r.length;i<l;i++)
				{
					if (r[i].form && r[i].form.BXAUTOSAVE)
						r[i].form.BXAUTOSAVE.RegisterInput(r[i]);
					else
						break;
				}
			}
		}, 10);
	}

	function setDefaultPreset_<?echo $MAP_ID?>()
	{
		if(window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects)
		{
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.each(function (geoObject) {
				geoObject.options.set({preset: 'twirl#blueIcon'});
			});
		}
	}

	function updateMapHint_<?echo $MAP_ID?>()
	{
		var noValue = true,
			i = 0;
		while (BX('point_<?echo $MAP_ID?>__n' + i + '_lat'))
		{
			if (BX('point_<?echo $MAP_ID?>__n' + i + '_lat').value !== '' || !BX('point_<?echo $MAP_ID?>__n' + i + '_lon') || BX('point_<?echo $MAP_ID?>__n' + i + '_lon').value !=='')
				noValue = false;
			i++;
		}
		if (noValue)
		{
			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'block';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'none';
		}
		else
		{
			BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
			BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';
		}
	}
</script>

<div id="bx_address_search_control_<?echo $MAP_ID?>" style="display: none;margin-top:15px;"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_SEARCH')?><input type="text" name="bx_address_<?echo $MAP_ID?>" id="bx_address_<?echo $MAP_ID?>" value="" style="width: 300px;" autocomplete="off" /></div>
<br />
<?
		}
?>
<input type="text" style="width:125px;margin:0 0 4px" name="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat" id="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat" onchange="setInputPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()" />, <input type="text" style="width:125px;margin:0 15px 4px 0;" name="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon" id="point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon" onchange="setInputPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()" />
<div id="point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_" style="display:none;margin:0 0 4px;font-size: 12px"><a href="javascript:void(0);" onclick="findPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_GOTO_POINT')?></a> | <a href="javascript:void(0);" onclick="if (confirm('<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_YANDEX_REMOVE_POINT_CONFIRM'))?>')) removePoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()"><?echo GetMessage('IBLOCK_PROP_MAP_YANDEX_REMOVE_POINT')?></a></div><br />
<input type="text" style="display:none;" id="value_<?echo $MAP_ID;?>__n<?=$yandexMapLastNumber?>_" name="<?=htmlspecialcharsbx($strHTMLControlName["VALUE"])?>" value="<?=htmlspecialcharsEx($value["VALUE"])?>" />
<script>
	window.jsAdminYandexMess = {
		nothing_found: '<?echo CUtil::JSEscape(GetMessage('IBLOCK_PROP_MAP_YANDEX_NOTHING_FOUND'))?>'
	}
	jsUtils.loadCSSFile('/bitrix/components/bitrix/map.yandex.view/settings/settings.css');

	function BXWaitForMap_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		if (!window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'])
			setTimeout(BXWaitForMap_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_, 300);
		else
		{
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_ = null;

			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].events.remove('dblclick', window.setPointValue_<?echo $MAP_ID?>);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].events.add('dblclick', window.setPointValue_<?echo $MAP_ID?>);
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].events.add('click', window.setDefaultPreset_<?echo $MAP_ID?>);
			var searchInput = BX('bx_address_<?echo $MAP_ID?>');
			BX.bind(searchInput, "keydown", jsYandexCESearch_<?echo $MAP_ID;?>.setTypingStarted);
			BX.bind(searchInput, "contextmenu", jsYandexCESearch_<?echo $MAP_ID;?>.setTypingStarted);
			BX('point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_').style.display = 'none';

			<?if ($bHasValue):?>
				setPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_([<?echo $POINT_LAT?>, <?echo $POINT_LON?>]);
				if (<?=$yandexMapLastNumber?> > 0)
					window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].setBounds(window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.getBounds(), {checkZoomRange: true});
			<?endif;?>

			BX('bx_address_search_control_<?echo $MAP_ID?>').style.display = 'block';
			BX('bx_map_hint_<?echo $MAP_ID?>').style.display = 'block';

		}
	}

	function findPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		if (null != window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_)
		{
			window.setDefaultPreset_<?echo $MAP_ID?>();
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.getCoordinates(),{delay:0});
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.options.set({preset: 'twirl#redIcon'});

		}
	}

	function removePoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.remove(window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_);
		window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_ = null;

		BX('point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_').style.display = 'none';

		updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_();

		updateMapHint_<?echo $MAP_ID?>();
	}

	function setPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(obEvent)
	{
		var obPoint = BX.type.isArray(obEvent) ? obEvent : obEvent.get("coordPosition");

		if (null == window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_)
		{
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_ = new ymaps.Placemark(obPoint, {}, {draggable:true});
			window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].geoObjects.add(window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_);
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.events.add('dragend', updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_);
		}
		else
		{
			window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.setCoordinates(obPoint);
		}

		BX('point_control_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_').style.display = 'inline-block';
		BX('bx_map_hint_novalue_<?echo $MAP_ID?>').style.display = 'none';
		BX('bx_map_hint_value_<?echo $MAP_ID?>').style.display = 'block';

		updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(obPoint);
		window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'].panTo(obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.getCoordinates(),{delay:0});
	}

	function setInputPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_()
	{
		var vv = [BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat').value, BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon').value];
		if (vv[0] == '' && vv[1] == '')
		{
			removePoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_();
		}
		var v = [parseFloat(BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat').value), parseFloat(BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon').value)];
		if (!isNaN(v[0]) && !isNaN(v[1]))
		{
			setPointValue_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(v);
		}
	}

	function updatePointPosition_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_(obPoint)
	{
		if (!!obPoint && !!obPoint.geometry)
			obPoint = obPoint.geometry.getCoordinates();
		else if (!!window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_)
			obPoint = window.obPoint_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_.geometry.getCoordinates();
		else
			obPoint = null;

		var obInput = BX('value_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_');
		obInput.value = null == obPoint ? '' : obPoint[0] + ',' + obPoint[1];

		BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lat').value = obPoint ? obPoint[0] : '';
		BX('point_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_lon').value = obPoint ? obPoint[1] : '';
	}

	BX.ready(setTimeout(BXWaitForMap_<?echo $MAP_ID?>__n<?=$yandexMapLastNumber?>_, 1));
	var jsYandexCESearch_<?echo $MAP_ID;?> = {

		bInited: false,

		map: null,
		geocoder: null,
		obInput: null,
		timerID: null,
		timerDelay: 1000,

		arSearchResults: [],
		strLastSearch: null,

		obOut: null,

		__init: function(input)
		{
			if (jsYandexCESearch_<?echo $MAP_ID;?>.bInited) return;

			jsYandexCESearch_<?echo $MAP_ID;?>.map = window.GLOBAL_arMapObjects['<?echo $MAP_ID?>'];
			jsYandexCESearch_<?echo $MAP_ID;?>.obInput = input;

			input.onfocus = jsYandexCESearch_<?echo $MAP_ID;?>.showResults;
			input.onblur = jsYandexCESearch_<?echo $MAP_ID;?>.hideResults;

			jsYandexCESearch_<?echo $MAP_ID;?>.bInited = true;
		},

		setTypingStarted: function(e)
		{
			if (null == e)
				e = window.event;

			jsYandexCESearch_<?echo $MAP_ID;?>.hideResults();

			if (e.keyCode == 13 )
			{
				jsYandexCESearch_<?echo $MAP_ID;?>.doSearch();
				return false;
			}
			else
			{
				if (!jsYandexCESearch_<?echo $MAP_ID;?>.bInited)
					jsYandexCESearch_<?echo $MAP_ID;?>.__init(this);

				if (e.type=="contextmenu")
					jsYandexCESearch_<?echo $MAP_ID;?>.timerDelay=3000;
				else
					jsYandexCESearch_<?echo $MAP_ID;?>.timerDelay=1000;

				if (null != jsYandexCESearch_<?echo $MAP_ID;?>.timerID)
					clearTimeout(jsYandexCESearch_<?echo $MAP_ID;?>.timerID);

				jsYandexCESearch_<?echo $MAP_ID;?>.timerID = setTimeout(jsYandexCESearch_<?echo $MAP_ID;?>.doSearch, jsYandexCESearch_<?echo $MAP_ID;?>.timerDelay);
			}
		},

		doSearch: function()
		{
			this.strLastSearch = jsUtils.trim(jsYandexCESearch_<?echo $MAP_ID;?>.obInput.value);

			if (this.strLastSearch.length > 1)
			{
				ymaps.geocode(this.strLastSearch).then(
					jsYandexCESearch_<?echo $MAP_ID;?>.__searchResultsLoad,
					jsYandexCESearch_<?echo $MAP_ID;?>.handleError
				);
			}
		},

		handleError: function(error)
		{
			alert(this.jsMess.mess_error + ': ' + error.message);
		},

		setResultsCoordinates: function()
		{
			var obPos = jsUtils.GetRealPos(jsYandexCESearch_<?echo $MAP_ID;?>.obInput);
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.top = (obPos.bottom + 2) + 'px';
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.left = obPos.left + 'px';
		},

		__generateOutput: function()
		{
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut = document.body.appendChild(document.createElement('UL'));
			jsYandexCESearch_<?echo $MAP_ID;?>.obOut.className = 'bx-yandex-address-search-results';
		},

		__searchResultsLoad: function(res)
		{
			var _this = jsYandexCESearch_<?echo $MAP_ID;?>;

			if (null == _this.obOut)
				_this.__generateOutput();

			_this.obOut.innerHTML = '';
			_this.clearSearchResults();

			var len = res.geoObjects.getLength();
			if (len > 0)
			{
				for (var i = 0; i < len; i++)
				{
					_this.arSearchResults[i] = res.geoObjects.get(i);

					var obListElement = document.createElement('LI');

					if (i == 0)
						obListElement.className = 'bx-yandex-first';

					var obLink = document.createElement('A');
					obLink.href = "javascript:void(0)";
					var obText = obLink.appendChild(document.createElement('SPAN'));
					obText.appendChild(document.createTextNode(
						jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults[i].properties.get('metaDataProperty').GeocoderMetaData.text
					));

					obLink.BXSearchIndex = i;
					obLink.onclick = _this.__showSearchResult;

					obListElement.appendChild(obLink);
					_this.obOut.appendChild(obListElement);
				}
			}
			else
			{
				_this.obOut.innerHTML = '<li class="bx-yandex-notfound">' + window.jsAdminYandexMess.nothing_found + '</li>';
			}

			_this.showResults();
		},

		__showSearchResult: function()
		{
			if (null !== this.BXSearchIndex)
			{
				var bounds =  jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults[this.BXSearchIndex].properties.get('boundedBy');
				jsYandexCESearch_<?echo $MAP_ID;?>.map.setBounds(bounds, { checkZoomRange: true });
			}
		},

		showResults: function()
		{
			if(this.strLastSearch!=jsUtils.trim(jsYandexCESearch_<?echo $MAP_ID;?>.obInput.value))
				jsYandexCESearch_<?echo $MAP_ID;?>.doSearch();

			if (null != jsYandexCESearch_<?echo $MAP_ID;?>.obOut)
			{
				jsYandexCESearch_<?echo $MAP_ID;?>.setResultsCoordinates();
				jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.display = 'block';
			}
		},

		hideResults: function()
		{
			if (null != jsYandexCESearch_<?echo $MAP_ID;?>.obOut)
			{
				setTimeout("jsYandexCESearch_<?echo $MAP_ID;?>.obOut.style.display = 'none'", 300);
			}
		},

		clearSearchResults: function()
		{
			for (var i = 0; i < jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults.length; i++)
			{
				delete jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults[i];
			}

			jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults = [];
		},

		clear: function()
		{
			if (!jsYandexCESearch_<?echo $MAP_ID;?>.bInited)
				return;

			jsYandexCESearch_<?echo $MAP_ID;?>.bInited = false;
			if (null != jsYandexCESearch_<?echo $MAP_ID;?>.obOut)
			{
				jsYandexCESearch_<?echo $MAP_ID;?>.obOut.parentNode.removeChild(jsYandexCESearch_<?echo $MAP_ID;?>.obOut);
				jsYandexCESearch_<?echo $MAP_ID;?>.obOut = null;
			}

			jsYandexCESearch_<?echo $MAP_ID;?>.arSearchResults = [];
			jsYandexCESearch_<?echo $MAP_ID;?>.map = null;
			jsYandexCESearch_<?echo $MAP_ID;?>.geocoder = null;
			jsYandexCESearch_<?echo $MAP_ID;?>.obInput = null;
			jsYandexCESearch_<?echo $MAP_ID;?>.timerID = null;
		}
	}

</script>

		</div>

		<?$out = ob_get_contents();
		ob_end_clean();

		if ($arProperty['MULTIPLE'] == 'Y')
			$yandexMapLastNumber++;

		return $out;
	}

	public static function GetPublicViewHTML($arProperty, $value, $arParams)
	{
		$s = '';
		if ($arParams['MODE'] == 'CSV_EXPORT')
		{
			if (strlen($value["VALUE"])>0)
			{
				$s = parent::ConvertFromDB($arProperty, $value);
			}
		}
		else
		{
			if(strlen($value["VALUE"])>0)
			{
				$value = parent::ConvertFromDB($arProperty, $value);
				$arCoords = explode(',', $value['VALUE']);
				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent(
					'bitrix:map.yandex.view',
					'',
					array(
						'MAP_DATA' => serialize(array(
							'yandex_lat' => $arCoords[0],
							'yandex_lon' => $arCoords[1],
							'PLACEMARKS' => array(
								array(
									'LON' => $arCoords[1],
									'LAT' => $arCoords[0],
								),
							),
						)),
						'MAP_ID' => 'MAP_YANDEX_VIEW_'.$arProperty['IBLOCK_ID'].'_'.$arProperty['ID'].'_'.rand(),
						'DEV_MODE' => 'Y',
					),
					false, array('HIDE_ICONS' => 'Y')
				);

				$s = ob_get_contents();
				ob_end_clean();
			}
		}

		return $s;
	}
}

//AddEventHandler("iblock", "OnIBlockPropertyBuildList", array("CIBlockPropertyFileMan", "GetUserTypeDescription"));
//RegisterModuleDependences('iblock', 'OnIBlockPropertyBuildList', 'fileman', 'CIBlockPropertyMapGoogle', 'GetUserTypeDescription');
//RegisterModuleDependences('iblock', 'OnIBlockPropertyBuildList', 'fileman', 'CIBlockPropertyMapYandex', 'GetUserTypeDescription');

// ##########################
// #######   VIDEO PROPERTIES   ######
// ##########################

// ### Base class ###
class CVideoProperty
{
	public static function BasePrepareSettings($arProperty, $key = "SETTINGS")
	{
		$arSet = array(
			"BUFFER_LENGTH" => "10",
			"CONTROLBAR" => "bottom",
			"AUTOSTART" => "N",
			"VOLUME" => "90",
			"SKIN" => "",
			"FLASHVARS" => "",
			"WMODE_FLV" => "transparent",
			"BGCOLOR" => "FFFFFF",
			"COLOR" => "000000",
			"OVER_COLOR" => "000000",
			"SCREEN_COLOR" => "000000",
			"SILVERVARS" => "",
			"WMODE_WMV" => "windowless",
			"WIDTH" => "400",
			"HEIGHT" => "300",
		);

		if(is_array($arProperty[$key]))
		{
			if (isset($arProperty[$key]["BUFFER_LENGTH"]))
				$arSet["BUFFER_LENGTH"] = intVal($arProperty[$key]["BUFFER_LENGTH"]);

			if (isset($arProperty[$key]["CONTROLBAR"]))
				$arSet["CONTROLBAR"] = $arProperty[$key]["CONTROLBAR"];

			if (isset($arProperty[$key]["AUTOSTART"]))
				$arSet["AUTOSTART"] = $arProperty[$key]["AUTOSTART"] == "Y" ? "Y" : "N";

			if (isset($arProperty[$key]["VOLUME"]))
				$arSet["VOLUME"] = intVal($arProperty[$key]["VOLUME"]);

			if (isset($arProperty[$key]["SKIN"]))
				$arSet["SKIN"] = $arProperty[$key]["SKIN"];

			if (isset($arProperty[$key]["FLASHVARS"]))
				$arSet["FLASHVARS"] = $arProperty[$key]["FLASHVARS"];

			if (isset($arProperty[$key]["WMODE_FLV"]))
				$arSet["WMODE_FLV"] = $arProperty[$key]["WMODE_FLV"];

			if (isset($arProperty[$key]["BGCOLOR"]))
				$arSet["BGCOLOR"] = $arProperty[$key]["BGCOLOR"];
			if (isset($arProperty[$key]["COLOR"]))
				$arSet["COLOR"] = $arProperty[$key]["COLOR"];
			if (isset($arProperty[$key]["OVER_COLOR"]))
				$arSet["OVER_COLOR"] = $arProperty[$key]["OVER_COLOR"];
			if (isset($arProperty[$key]["SCREEN_COLOR"]))
				$arSet["SCREEN_COLOR"] = $arProperty[$key]["SCREEN_COLOR"];

			if (isset($arProperty[$key]["SILVERVARS"]))
				$arSet["SILVERVARS"] = $arProperty[$key]["SILVERVARS"];

			if (isset($arProperty[$key]["WMODE_WMV"]))
				$arSet["WMODE_WMV"] = $arProperty[$key]["WMODE_WMV"];

			if (isset($arProperty[$key]["WIDTH"]))
				$arSet["WIDTH"] = $arProperty[$key]["WIDTH"];
			if (isset($arProperty[$key]["HEIGHT"]))
				$arSet["HEIGHT"] = $arProperty[$key]["HEIGHT"];
		}
		return $arSet;
	}

	public static function BaseGetSettingsHTML($name, $val)
	{
		$arSkins = GetSkinsEx(CUserTypeVideo::GetSkinPath());
		ob_start();
?>
<tr><td colSpan="2">
<style>
tr.bx-prop-sub-title td{background: #E2E1E0! important; color: #525355! important; font-weight: bold! important; text-align: left! important; padding-left: 10px;}
</style>
</td></tr>
<tr class="heading"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_BOTH_SET')?></td></tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_BUFFER')?>:</td>
	<td>
		<input type="text" name="<?= $name?>[BUFFER_LENGTH]" size="10" value="<?= $val["BUFFER_LENGTH"]?>"/>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_CONTROLBAR')?>:</td>
	<td>
		<select  name="<?= $name?>[CONTROLBAR]">
			<option value="bottom" <? if($val["CONTROLBAR"] == 'bottom') echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_CONTROLBAR_DOWN')?></option>
			<option value="none" <? if($val["CONTROLBAR"] == 'none') echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_CONTROLBAR_NONE')?></option>
		</select>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_AUTOSTART')?>:</td>
	<td>
		<input value="Y" type="checkbox" name="<?= $name?>[AUTOSTART]" <? if($val["AUTOSTART"] == 'Y') echo 'checked="checked"';?>/>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_VOLUME')?>:</td>
	<td>
		<input type="text" name="<?= $name?>[VOLUME]" size="10" value="<?= $val["VOLUME"]?>"/>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SIZE')?></td>
	<td>
		<input type="text" name="<?= $name?>[WIDTH]" style="width: 70px;" size="10" value="<?= $val["WIDTH"]?>"/>
		x
		<input type="text" name="<?= $name?>[HEIGHT]" style="width: 70px;" size="10" value="<?= $val["HEIGHT"]?>"/>
	</td>
</tr>
<tr class="heading"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_FLV_SET')?></td></tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_SKIN')?>:</td>
	<td id="bx_player_skin_cell">
		<input id="bx_player_skin_input" type="hidden" name="<?= $name?>[SKIN]" value="<?= $val["SKIN"]?>" />
<script>
jsUtils.loadCSSFile("/bitrix/components/bitrix/player/js/skin_selector.css");
jsUtils.loadJSFile("/bitrix/components/bitrix/player/js/prop_skin_selector.js", function()
{
	if (!window.ComponentPropsSkinSelector || !window.BXSkinSelector)
		return;

	// Try to imitate CUSTOM-parameter in component params dialog
	ComponentPropsSkinSelector({
		propertyID : "SKIN",
		getElements : function(){return {SKIN_PATH: {value: "<?= CVideoProperty::GetSkinPath()?>"}};},
		oInput : document.getElementById("bx_player_skin_input"),
		oCont : document.getElementById("bx_player_skin_cell"),
		data : '<?= CUtil::JSEscape(CUtil::PhpToJSObject(array($arSkins, array('NoPreview' => GetMessage("IBLOCK_PROP_VIDEO_NO_PREVIEW"))))) ?>'
	});
});
</script>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_FLASHVARS')?>:</td>
	<td>
		<textarea cols="25"  name="<?= $name?>[FLASHVARS]"><?= $val["FLASHVARS"]?></textarea>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_FLV')?>:</td>
	<td>
		<select  name="<?= $name?>[WMODE_FLV]">
			<option value="window" <? if($val["WMODE_FLV"] == 'window' || !$val["WMODE_FLV"]) echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_WINDOW')?></option>
			<option value="opaque" <? if($val["WMODE_FLV"] == 'opaque') echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_OPAQUE')?></option>
			<option value="transparent" <? if($val["WMODE_FLV"] == 'transparent') echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_TRANSPARENT')?></option>
		</select>
	</td>
</tr>
<tr class="heading"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_WMV_SET')?></td></tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_BGCOLOR')?>:</td>
	<td><input type="text" name="<?= $name?>[BGCOLOR]" size="10" value="<?= $val["BGCOLOR"]?>"/></td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_COLOR')?>:</td>
	<td><input type="text" name="<?= $name?>[COLOR]" size="10" value="<?= $val["COLOR"]?>"/></td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_OVER_COLOR')?>:</td>
	<td><input type="text" name="<?= $name?>[OVER_COLOR]" size="10" value="<?= $val["OVER_COLOR"]?>"/></td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_SCREEN_COLOR')?>:</td>
	<td><input type="text" name="<?= $name?>[SCREEN_COLOR]" size="10" value="<?= $val["SCREEN_COLOR"]?>"/ ></td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_SILVERVARS')?>:</td>
	<td>
		<textarea cols="25"  name="<?= $name?>[SILVERVARS]"><?= $val["SILVERVARS"]?></textarea>
	</td>
</tr>
<tr>
	<td><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_WMV')?>:</td>
	<td>
		<select  name="<?= $name?>[WMODE_WMV]">
			<option value="window" <? if($val["WMODE_WMV"] == 'window') echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_WINDOW')?></option>
			<option value="windowless" <? if($val["WMODE_WMV"] == 'windowless') echo 'selected';?>><?= GetMessage('IBLOCK_PROP_VIDEO_SET_WMODE_TRANSPARENT')?></option>
		</select>
	</td>
</tr>
<?
		$result .= ob_get_contents();
		ob_end_clean();
		return $result;
	}

	public static function BaseGetEditFormHTML($set, $val, $name, $controlMode=false)
	{
		global $APPLICATION;
		$id = str_replace(array("[","]",":"), "_", $name);
		$path = $val["path"];

		if (intVal($val['width']) <= 0)
			$val['width'] = $set['WIDTH'];
		if (intVal($val['height']) <= 0)
			$val['height'] = $set['HEIGHT'];

		ob_start();
?>
<div style="padding: 5px;">
<style>
table.bx-video-prop-tbl{border-collapse: collapse! important; border: 1px solid #E0E4F1;}
table.bx-video-prop-tbl td{padding: 3px 5px! important; border-bottom: 1px dotted #BABABA !important;}
table.bx-video-prop-tbl tr.bx-prop-main-title td{background: #E0E4F1! important; color: #525355! important; font-weight: bold! important; text-align: center! important; border-bottom-width: 0px !important; padding: 5px! important;}
table.bx-video-prop-tbl tr.bx-prop-sub-title td{background: #E2E1E0! important; color: #525355! important; font-weight: bold! important; text-align: left! important; border-bottom-width: 0px !important;}
table.bx-video-prop-tbl td.bx-pr-title{text-align: right! important; vertical-align: top! important; padding-top: 8px !important;}
div.bx-path-div a{float: right !important;}
div.bx-path-div a.bx-leave{display: none;}
div.bx-path-div-changed a.bx-leave{display: block !important;}
div.bx-path-div-changed a.bx-change{display: none !important;}
div.bx-path-div input.bx-path{color: #525355 !important;}
div.bx-path-div-changed input.bx-path{text-decoration: line-through !important;}
table.bx-video-prop-tbl img.spacer{display:block;float:left;height:1px;margin-top:-2px;width:220px;}
</style>

<table class="bx-video-prop-tbl">
	<tr class="bx-prop-main-title"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_PARAMS_TITLE')?></td></tr>
	<? if ($controlMode != "iblock_element_admin"): ?>
		<? if(strlen($path) > 0):?>
			<tr class="heading"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_PARAMS_TITLE_VIEW')?></td></tr>
			<tr>
				<td colSpan="2" style="text-align: center;">
		<?$APPLICATION->IncludeComponent(
				"bitrix:player",
				"",
				array(
					"PLAYER_TYPE" => "auto",
					"PATH" => $path,
					"WIDTH" => $val['width'],
					"HEIGHT" => $val['height'],
					"FILE_TITLE" => $val['title'],
					"FILE_DURATION" => intVal($val['duration']),
					"FILE_AUTHOR" => $val['author'],
					"FILE_DATE" => $val['date'],
					"FILE_DESCRIPTION" => $val['desc'],
					"SKIN_PATH" => CVideoProperty::GetSkinPath(),
					"SKIN" => $set["SKIN"],
					"CONTROLBAR" => $set["CONTROLBAR"],
					"WMODE" => $set["WMODE_FLV"],
					"WMODE_WMV" => $set["WMODE_WMV"],
					"SHOW_CONTROLS" => $set["CONTROLBAR"] != 'none' ? "Y" : "N",
					"CONTROLS_BGCOLOR" => $set["CONTROLS_BGCOLOR"],
					"CONTROLS_COLOR" => $set["CONTROLS_COLOR"],
					"CONTROLS_OVER_COLOR" => $set["CONTROLS_OVER_COLOR"],
					"SCREEN_COLOR" => $set["SCREEN_COLOR"],
					"AUTOSTART" => $set["AUTOSTART"],
					"VOLUME" => $set["VOLUME"],
					"ADDITIONAL_FLASHVARS" => $set["FLASHVARS"],
					"BUFFER_LENGTH" => $set["BUFFER_LENGTH"],
					"ADDITIONAL_WMVVARS" => $set["SILVERVARS"],
					"ALLOW_SWF" => "N",
					"LOGO_POSITION" => "none"
					),
					false,
					array('HIDE_ICONS' => 'Y')
		); ?>
				</td>
			</tr>
		<?endif;?>

		<tr><td class="bx-pr-title" style="width: 300px;"></td><td style="width: 240px;"></td></tr>

		<tr class="heading"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_PARAMS_TITLE_MAIN')?></td></tr>

		<? if(strlen($path) > 0):?>
			<tr>
				<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_FILE')?>:</td>
				<td>
					<div id="bx_video_path_div_<?= $id?>" class="bx-path-div">
					<input type="hidden" value="<?= $path?>" name= "<?= $name?>[CUR_PATH]" />
					<input id="bx_video_b_new_file_<?= $id?>" type="hidden" value="N" name= "<?= $name?>[B_NEW_FILE]" />
					<input class="bx-path" readonly="readonly" value="<?= htmlspecialcharsex($path)?>" size="30" />
					<br />
					<a href="javascript: void(0)" onclick="return ChangeOrLeaveFile<?=$id?>(true);" class="bx-change" id="bx-change"><?= GetMessage('IBLOCK_PROP_VIDEO_FILE_CHANGE')?></a>
					<a href="javascript: void(0)" onclick="return ChangeOrLeaveFile<?=$id?>(false);"  class="bx-leave"><?= GetMessage('IBLOCK_PROP_VIDEO_FILE_LEAVE')?></a>
					</div>
				</td>
			</tr>
			<? if(CVideoProperty::CheckFileInUploadDir($path)):?>
				<tr id="bx_video_del_row_<?= $id?>" style="display: none;">
					<td class="bx-pr-title"></td>
					<td>
						<input type="checkbox" value="Y" id="bx_video_del_<?= $id?>" checked="checked" name= "<?= $name?>[DEL_CUR_FILE]" /><label for="bx_video_del_<?= $id?>"><?= GetMessage('IBLOCK_PROP_VIDEO_DEL_FILE')?></label>
					</td>
				</tr>
			<?endif;?>
		<?endif;?>

		<tr id="bx_video_new_path_row_<?= $id?>" <?if (strlen($path) > 0){ echo 'style="display: none;"'; }?>>
			<td class="bx-pr-title" style="width: 300px;"><?= GetMessage(strlen($path) > 0 ? 'IBLOCK_PROP_VIDEO_PATH_NEW' : 'IBLOCK_PROP_VIDEO_PATH')?>:</td>
			<td style="width: 240px;">
			<img src="/bitrix/images/1.gif" class="spacer" />
			<div id="bx_video_path_cont1_<?= $id?>" style="display: none;">
			<input type="text" size="30" value="" id="bx_video_path_<?= $id?>"  style="float:left;" name= "<?= $name?>[PATH]" />
			<?
			CAdminFileDialog::ShowScript(Array
				(
					"event" => "OpenFileBrowser_".$id,
					"arResultDest" => Array("FUNCTION_NAME" => "SetVideoPath".$id),
					"arPath" => Array(),
					"select" => 'F',// F - file only, D - folder only
					"operation" => 'O',// O - open, S - save
					"showUploadTab" => false,
					"showAddToMenuTab" => false,
					"fileFilter" => 'flv,mp4,mp3,wmv',
					"allowAllFiles" => true,
					"SaveConfig" => true
				)
			);

			CMedialib::ShowBrowseButton(
				array(
					"id" => 'OpenFileBrowser_but_'.$id,
					"event" => "OpenFileBrowser_".$id,
					'MedialibConfig' => array(
						"event" => "OpenFileBrowser_ml_".$id,
						"arResultDest" => array("FUNCTION_NAME" => "SetVideoPath".$id)
					)
				));?>
			<br />
			<a href="javascript: void(0)" onclick="return DisplayCont('bx_video_path_cont2_<?= $id?>', 'bx_video_path_cont1_<?= $id?>');" style="float: right;"><?= GetMessage('IBLOCK_PROP_VIDEO_PATH_FROM_PC')?></a>
			</div>
			<div id="bx_video_path_cont2_<?= $id?>">
				<input type="file" value="" id="bx_video_file_<?= $id?>" name= "<?= $name?>[FILE]" />
				<br />
				<a href="javascript: void(0)" onclick="return DisplayCont('bx_video_path_cont1_<?= $id?>', 'bx_video_path_cont2_<?= $id?>');" style="float: right;"><?= GetMessage('IBLOCK_PROP_VIDEO_PATH_FROM_FD')?></a>
			</div>
			</td>
		</tr>
	<?else:?>
		<tr>
			<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_FILE')?>:</td>
			<td>
				<div id="bx_video_path_div_<?= $id?>" class="bx-path-div">
					<input type="text" size="25" value="<?= htmlspecialcharsex($path)?>" size="30" name="<?= $name?>[PATH]"/>
				</div>
			</td>
		</tr>
	<?endif;?>

	<tr>
		<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_SIZE')?>:</td>
		<td>
			<input  id="bx_video_width_<?= $id?>" type="text" size="10" style="width: 70px;" value="<?= $val['width']?>" name= "<?= $name?>[WIDTH]" />
			x
			<input id="bx_video_height_<?= $id?>" type="text" size="10" style="width: 70px;" value="<?= $val['height']?>" name= "<?= $name?>[HEIGHT]" />
		</td>
	</tr>
	<tr class="heading"><td colSpan="2"><?= GetMessage('IBLOCK_PROP_VIDEO_PARAMS_TITLE_INFO')?></td></tr>
	<tr>
		<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_TITLE')?>:</td>
		<td><input id="bx_video_title_<?= $id?>" type="text" size="30" value="<?= htmlspecialcharsbx($val['title'])?>" name="<?= $name?>[TITLE]" /></td>
	</tr>
	<tr>
		<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_DURATION')?>:</td>
		<td><input id="bx_video_duration_<?= $id?>" type="text" size="30" value="<?= $val['duration']?>" name="<?= $name?>[DURATION]"/></td>
	</tr>
	<tr>
		<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_AUTHOR')?>:</td>
		<td><input id="bx_video_author_<?= $id?>" type="text" size="30" value="<?= htmlspecialcharsbx($val['author'])?>" name="<?= $name?>[AUTHOR]"/></td>
	</tr>
	<tr>
		<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_DATE')?>:</td>
		<td><input id="bx_video_date_<?= $id?>" type="text" size="30" value="<?= $val['date']?>" name="<?= $name?>[DATE]" /></td>
	</tr>
	<tr>
		<td class="bx-pr-title"><?= GetMessage('IBLOCK_PROP_VIDEO_DESC')?>:</td>
		<td><input id="bx_video_desc_<?= $id?>" type="text" size="30" value="<?= htmlspecialcharsbx($val['desc'])?>" name="<?= $name?>[DESC]"/></td>
	</tr>
</table>
<script>
function DisplayCont(id1, id2)
{
	var
		el1 = document.getElementById(id1),
		el2 = document.getElementById(id2);

	if (el1 && el2)
	{
		el1.style.display = "block";
		el2.style.display = "none";
	}
	return false;
}

function SetVideoPath<?= $id?>(filename, path, site)
{
	var
		url,
		srcInput = document.getElementById("bx_video_path_<?= $id?>");

	if (typeof filename == 'object') // Using medialibrary
	{
		url = filename.src;
		document.getElementById("bx_video_title_<?= $id?>").value = filename.name || '';
		document.getElementById("bx_video_desc_<?= $id?>").value = filename.description || '';
	}
	else // Using file dialog
	{
		url = (path == '/' ? '' : path) + '/'+filename;
	}

	srcInput.value = url;
	if(srcInput.onchange)
		srcInput.onchange();
	srcInput.focus();
	srcInput.select();
}

function ChangeOrLeaveFile<?= $id?>(bChange)
{
	var
		pDiv = document.getElementById("bx_video_path_div_<?= $id?>"),
		pDelRow = document.getElementById("bx_video_del_row_<?= $id?>"),
		pBNewFile = document.getElementById("bx_video_b_new_file_<?= $id?>"),
		pNewFileRow = document.getElementById("bx_video_new_path_row_<?= $id?>"),
		_display = jsUtils.IsIE() ? "inline" : "table-row";

	if (pBNewFile)
		pBNewFile.value = bChange ? "Y" : "N";

	if (pDelRow)
		pDelRow.style.display = bChange ? _display : 'none';

	if (pNewFileRow)
		pNewFileRow.style.display = bChange ? _display : 'none';

	pDiv.className = bChange ? "bx-path-div bx-path-div-changed" : "bx-path-div";

	return false;
}
</script>
</div>
<?
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}

	public static function BaseConvertToDB($value)
	{
		$io = CBXVirtualIo::GetInstance();

		$arRes = array("path" => "");
		if (!is_array($value))
			$value = array();

		//In case of DB value just serialize it
		if (implode("|", array_keys($value)) === 'path|width|height|title|duration|author|date|desc')
			return serialize($value);

		if ($value["B_NEW_FILE"] != "N") // New video or replacing old
		{
			if (strlen($value["CUR_PATH"]) > 0 && $value["DEL_CUR_FILE"] == "Y" && CIBlockPropertyVideo::CheckFileInUploadDir($value["CUR_PATH"]))
			{
				// del current file
				$cur_path_ = $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $value["CUR_PATH"]);
				$flTmp = $io->GetFile($cur_path_);
				$flSzTmp = $flTmp->GetFileSize();
				if($io->Delete($cur_path_))
				{
					// Quota
					if(COption::GetOptionInt("main", "disk_space") > 0)
						CDiskQuota::updateDiskQuota("file", $flSzTmp, "delete");
				}
			}

			// Get video
			if (strlen($value["PATH"]) > 0 )
			{
				$arRes["path"] = $value["PATH"];
			}
			else if (isset($value["FILE"]) && strlen($value["FILE"]["tmp_name"]) > 0)
			{
				$pathToDir = CIBlockPropertyVideo::GetUploadDirPath();
				if (!$io->DirectoryExists($_SERVER["DOCUMENT_ROOT"].$pathToDir))
					CFileMan::CreateDir($pathToDir);

				// 1. Convert name
				$name = preg_replace("/[^a-zA-Z0-9_:\.]/is", "_", $value["FILE"]["name"]);
				$baseNamePart = substr($name, 0, strrpos($name, '.'));
				$ext = GetFileExtension($name);

				if(strlen($ext) > 0 && !HasScriptExtension($name) && substr($name, 0, 1) != ".")
				{
					$ind = 0;
					// 2. Check if file already exists
					while($io->FileExists($_SERVER["DOCUMENT_ROOT"].Rel2Abs($pathToDir, $name)))
						$name = $baseNamePart."_(".++$ind.").".$ext; // 3. Rename

					$pathto = Rel2Abs($pathToDir, $name);
					if (is_uploaded_file($value["FILE"]["tmp_name"])
						&& $io->Copy($value["FILE"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"].$pathto))
					{
						$arRes["path"] = Rel2Abs("/", $pathto);
						// Quota
						if(COption::GetOptionInt("main", "disk_space") > 0)
							CDiskQuota::updateDiskQuota("file", $value["FILE"]["size"], "add");
					}
				}
			}
		}
		elseif (strlen($value["CUR_PATH"]) > 0) // save current file
		{
			if(preg_match("/^(http|https):\\/\\//", $value["CUR_PATH"]))
				$arRes["path"] = $value["CUR_PATH"];
			else
				$arRes["path"] = Rel2Abs("/", $value["CUR_PATH"]);
		}

		// Width  & height
		$arRes["width"] = intVal($value["WIDTH"]);
		$arRes["height"] = intVal($value["HEIGHT"]);
		if ($arRes["width"] < 0)
			$arRes["width"] = 400;
		if ($arRes["height"] < 0)
			$arRes["height"] = 300;

		// Video info
		$arRes["title"] = $value["TITLE"];
		$arRes["duration"] = $value["DURATION"];
		$arRes["author"] = $value["AUTHOR"];
		$arRes["date"] = $value["DATE"];
		$arRes["desc"] = $value["DESC"];

		$strRes = serialize($arRes);
		if ($arRes["path"] == "" && $arRes["title"] == "" && $arRes["author"] == "")
			return "";

		return $strRes;
	}

	public static function BaseConvertFromDB($val = "")
	{
		if (!is_array($val) && strlen($val) > 0)
			$val = unserialize($val);
		return $val ? $val : array();
	}

	public static function BaseCheckFields($val)
	{
		$arErrors = array();

		if (!is_array($val))
			$val = array();

		// Check uploaded file
		if ($val["B_NEW_FILE"] != "N" && isset($val["FILE"])) //
		{
			if($val["FILE"]["error"] == 1 || $val["FILE"]["error"] == 2)
				$arErrors[] = GetMessage("IBLOCK_PROP_VIDEO_SIZE_ERROR", Array('#FILE_NAME#' => $pathto))."\n";

			if(strlen($val["FILE"]["tmp_name"]) > 0)
			{
				$name = $val["FILE"]["name"];
				$name = preg_replace("/[^a-zA-Z0-9_:\.]/is", "_", $name);
				$ext = GetFileExtension($name);

				if(strlen($ext) == 0 || HasScriptExtension($name) || substr($name, 0, 1) == ".")
					$arErrors[] = GetMessage("IBLOCK_PROP_VIDEO_INCORRECT_EXT", array("#EXT#" => strtoupper($ext)));
				elseif (!is_uploaded_file($val["FILE"]["tmp_name"]))
					$arErrors[] = GetMessage("IBLOCK_PROP_VIDEO_UPLOAD_ERROR");
				else
				{
					$quota = new CDiskQuota();
					if (!$quota->checkDiskQuota(array("FILE_SIZE" => $val["FILE"]["size"])))
						$arErrors[] = GetMessage("IBLOCK_PROP_VIDEO_QUOTE_ERROR")."\n";
				}
			}
		}
		return $arErrors;
	}

	public static function BaseGetAdminListViewHTML($val)
	{
		if (!is_array($val) || strlen($val["path"]) == 0)
			return '';
		return "<span style='white-space: nowrap;' title='".$val["path"]."'>".GetMessage("IBLOCK_PROP_VIDEO")." [".htmlspecialcharsex($val["path"])."]</span>";
	}

	public static function BaseGetPublicHTML($set, $val)
	{
		if (strlen($val["path"]) <= 0)
			return '';

		global $APPLICATION;
		ob_start();
		$title = strlen($val['title']) > 0 ? $val['title'] : "";
?>
<div title="<?= addslashes($title)?>">
<?$APPLICATION->IncludeComponent(
	"bitrix:player",
	"",
	array(
		"PLAYER_TYPE" => "auto",
		"PATH" => $val["path"],
		"WIDTH" => $val['width'],
		"HEIGHT" => $val['height'],
		"FILE_TITLE" => $val['title'],
		"FILE_DURATION" => intVal($val['duration']),
		"FILE_AUTHOR" => $val['author'],
		"FILE_DATE" => $val['date'],
		"FILE_DESCRIPTION" => $val['desc'],
		"SKIN_PATH" => CIBlockPropertyVideo::GetSkinPath(),
		"SKIN" => $set["SKIN"],
		"CONTROLBAR" => $set["CONTROLBAR"],
		"WMODE" => $set["WMODE_FLV"],
		"WMODE_WMV" => $set["WMODE_WMV"],
		"SHOW_CONTROLS" => $set["CONTROLBAR"] != 'none' ? "Y" : "N",
		"CONTROLS_BGCOLOR" => $set["CONTROLS_BGCOLOR"],
		"CONTROLS_COLOR" => $set["CONTROLS_COLOR"],
		"CONTROLS_OVER_COLOR" => $set["CONTROLS_OVER_COLOR"],
		"SCREEN_COLOR" => $set["SCREEN_COLOR"],
		"AUTOSTART" => $set["AUTOSTART"],
		"VOLUME" => $set["VOLUME"],
		"ADDITIONAL_FLASHVARS" => $set["FLASHVARS"],
		"BUFFER_LENGTH" => $set["BUFFER_LENGTH"],
		"ADDITIONAL_WMVVARS" => $set["SILVERVARS"],
		"ALLOW_SWF" => "N",
		"LOGO_POSITION" => "none"
		),
		$APPLICATION->getCurrentIncludedComponent(),
		array('HIDE_ICONS' => 'Y')
); ?>
</div>
<?
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}

	public static function BaseOnSearchContent($val)
	{
		$str = "";
		if (strlen($val['path']) > 0)
		{
			if (strlen($val['title']) > 0)
				$str .= $val['title']." \n";

			if (strlen($val['author']) > 0)
				$str .= $val['author']." \n";

			if (strlen($val['desc']) > 0)
				$str .= $val['desc']." \n";
		}

		return $str;
	}

	public static function CheckFileInUploadDir($path = '')
	{
		$pathToDir = CVideoProperty::GetUploadDirPath();
		return substr($path, 0, strlen($pathToDir)) == $pathToDir;
	}

	public static function GetUploadDirPath()
	{
		return "/upload/video/";
	}

	public static function GetSkinPath()
	{
		return "/bitrix/components/bitrix/player/mediaplayer/skins";
	}

}

if (!function_exists('getSkinsEx'))
{
	function getSkinsEx($path)
	{
		$basePath = $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $path);
		$arSkins = Array();

		if (!is_dir($basePath)) // Not valid folder
			return $arSkins;

		$arSkins = getSkinsFromDir($path);

		$handle  = @opendir($basePath);

		while(false !== ($skinDir = @readdir($handle)))
		{

			if(!is_dir($basePath.'/'.$skinDir) || $skinDir == "." || $skinDir == ".." )
				continue;

			$arSkins = array_merge($arSkins,getSkinsFromDir($path.'/'.$skinDir));
		}
		return $arSkins;
	}

	function getSkinsFromDir($path) //http://jabber.bx/view.php?id=28856
	{
		$basePath = $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $path);
		$arSkinExt = array('swf', 'zip');
		$arPreviewExt = array('png', 'gif', 'jpg', 'jpeg');
		$prExtCnt = count($arPreviewExt);
		$arSkins = Array();
		$handle  = @opendir($basePath);

		while(false !== ($f = @readdir($handle)))
		{
			if($f == "." || $f == ".." || $f == ".htaccess" || !is_file($basePath.'/'.$f))
				continue;

			$ext = strtolower(GetFileExtension($f));
			if (in_array($ext, $arSkinExt)) // We find skin
			{
				$name = substr($f, 0, - strlen($ext) - 1); // name of the skin
				if (strlen($name) <= 0)
					continue;

				$Skin = array('filename' => $f);
				$Skin['name'] = strtoupper(substr($name, 0, 1)).strtolower(substr($name, 1));
				$Skin['the_path'] = $path;

				// Try to find preview
				for ($i = 0; $i < $prExtCnt; $i++)
				{
					if (file_exists($basePath.'/'.$name.'.'.$arPreviewExt[$i]))
					{
						$Skin['preview'] = $name.'.'.$arPreviewExt[$i];
						break;
					}
				}
				$arSkins[] = $Skin;
			}
		}

		return $arSkins;
	}
}

// ### Iblock property ###
class CIBlockPropertyVideo extends CVideoProperty
{
	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "video",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_VIDEO"),
			"GetPropertyFieldHtml" => array("CIBlockPropertyVideo", "GetPropertyFieldHtml"),
			"GetPublicViewHTML" => array("CIBlockPropertyVideo", "GetPublicViewHTML"),
			"ConvertToDB" => array("CIBlockPropertyVideo", "ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyVideo", "ConvertFromDB"),
			"CheckFields" => array("CIBlockPropertyVideo", "CheckFields"),
			"GetSearchContent" => array("CIBlockPropertyVideo", "GetSearchContent"),
			"GetSettingsHTML" => array("CIBlockPropertyVideo", "GetSettingsHTML"),
			"PrepareSettings" => array("CIBlockPropertyVideo", "PrepareSettings"),
			"GetAdminListViewHTML" => array("CIBlockPropertyVideo", "GetAdminListViewHTML"),
			"GetLength" => array("CIBlockPropertyVideo", "GetLength"),
		);
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$dbVal = CUserTypeVideo::BaseConvertToDB($value["VALUE"]);
		$val = CUserTypeVideo::BaseConvertFromDB($dbVal);
		return CIBlockPropertyVideo::BaseGetEditFormHTML($arProperty["USER_TYPE_SETTINGS"], $val, $strHTMLControlName["VALUE"], $strHTMLControlName["MODE"]);
	}

	public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		return CIBlockPropertyVideo::BaseGetAdminListViewHTML($value["VALUE"]);
	}

	public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		return CIBlockPropertyVideo::BaseGetPublicHTML($arProperty["USER_TYPE_SETTINGS"], $value["VALUE"]);
	}

	public static function ConvertFromDB($arProperty, $value)
	{
		$value['VALUE'] = CIBlockPropertyVideo::BaseConvertFromDB($value['VALUE']);
		return $value;
	}

	public static function ConvertToDB($arProperty, $value)
	{
		return CIBlockPropertyVideo::BaseConvertToDB($value["VALUE"]);
	}

	public static function CheckFields($arProperty, $value)
	{
		return CIBlockPropertyVideo::BaseCheckFields($value["VALUE"]);
	}

	public static function GetLength($arProperty, $value)
	{
		if(
			is_array($value)
			&& array_key_exists("VALUE", $value)
			&& is_array($value["VALUE"])
		)
		{
			if(
				array_key_exists("PATH", $value["VALUE"])
				&& strlen(trim($value["VALUE"]["PATH"])) > 0
			)
				return 1;

			if(
				array_key_exists("FILE", $value["VALUE"])
				&& is_array($value["VALUE"]["FILE"])
				&& $value["VALUE"]["FILE"]["error"] === 0
			)
				return 1;

			if(
				array_key_exists("CUR_PATH", $value["VALUE"])
				&& strlen(trim($value["VALUE"]["CUR_PATH"]))
				&& !($value["VALUE"]["B_NEW_FILE"] === "Y" && $value["VALUE"]["DEL_CUR_FILE"] === "Y")
			)
				return 1;
		}

		return 0;
	}

	public static function PrepareSettings($arProperty)
	{
		$arResult = CUserTypeVideo::BasePrepareSettings($arProperty, "USER_TYPE_SETTINGS");
		$arResult['SMART_FILTER'] = 'N';
		$arFields['USER_TYPE_SETTINGS'] = $arResult;

		return $arFields;
	}

	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("FILTRABLE", "ROW_COUNT", "COL_COUNT", "DEFAULT_VALUE", "SMART_FILTER"), //will hide the field
			"SET" => array("FILTRABLE" => "N", "SMART_FILTER" => "N"), //if set then hidden field will get this value
			"USER_TYPE_SETTINGS_TITLE" => GetMessage("IBLOCK_PROP_VIDEO_SET_NAME")
		);

		$arSettings = CIBlockPropertyVideo::PrepareSettings($arProperty);
		if (isset($arSettings['USER_TYPE_SETTINGS']))
			$arSettings = $arSettings['USER_TYPE_SETTINGS'];

		return CIBlockPropertyVideo::BaseGetSettingsHTML($strHTMLControlName["NAME"], $arSettings);
	}

	public static function GetSearchContent($arProperty, $value, $strHTMLControlName)
	{
		return CIBlockPropertyVideo::BaseOnSearchContent($value["VALUE"]);
	}
}

// ### UserType for main module ###
class CUserTypeVideo extends CVideoProperty
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "video",
			"CLASS_NAME" => "CUserTypeVideo",
			"DESCRIPTION" => GetMessage("IBLOCK_PROP_VIDEO"),
			"BASE_TYPE" => "string"
		);
	}

	public static function GetDBColumnType($arUserField)
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "text";
			case "oracle":
				return "varchar2(2000 char)";
			case "mssql":
				return "varchar(2000)";
		}
	}

	public static function PrepareSettings($arProperty)
	{
		return CUserTypeVideo::BasePrepareSettings($arProperty, "SETTINGS");
	}

	public static function GetSettingsHTML($arUserField = array(), $arHtmlControl, $bVarsFromForm)
	{
		if(!is_array($arUserField))
			$arUserField = array();

		$arUserField["SETTINGS"] = $bVarsFromForm ? $GLOBALS[$arHtmlControl["NAME"]] : CUserTypeVideo::PrepareSettings($arUserField);
		return CUserTypeVideo::BaseGetSettingsHTML($arHtmlControl["NAME"], $arUserField["SETTINGS"]);
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$val = CUserTypeVideo::BaseConvertFromDB(htmlspecialcharsback($arHtmlControl["VALUE"])); // Unserialize array
		return CUserTypeVideo::BaseGetEditFormHTML($arUserField["SETTINGS"], $val, $arHtmlControl["NAME"]);
	}

	public static function OnBeforeSave($arUserField, $value)
	{
		return CUserTypeVideo::BaseConvertToDB($value);
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$val = CUserTypeVideo::BaseConvertFromDB(htmlspecialcharsback($arHtmlControl["VALUE"])); // Unserialize array
		return CUserTypeVideo::BaseGetAdminListViewHTML($val);
	}

	public static function CheckFields($arUserField, $value)
	{
		return CUserTypeVideo::BaseCheckFields($value);
	}

	public static function OnSearchIndex($arUserField)
	{
		return CIBlockPropertyVideo::BaseOnSearchContent($arUserField["VALUE"]);
	}

	public static function GetPublicViewHTML($arUserField, $arHtmlControl)
	{
		$val = CUserTypeVideo::BaseConvertFromDB(htmlspecialcharsback($arHtmlControl["VALUE"])); // Unserialize array
		return CUserTypeVideo::BaseGetPublicHTML($arUserField["SETTINGS"], $val);
	}
}
?>