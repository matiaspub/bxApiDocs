function SectionSH(id, action, prefix)
{
	var section = document.getElementById(id);
	if ((action != 'show') && (action != 'hide'))
		action = section.style.display != 'none' ? 'hide' : 'show';
	var arCookie = new Array();
	var arCookie_ = new Array();
	var strCookie = "";
	var tmp = "";
	var all_checkbox = document.getElementById(prefix+'_all_checkbox');
	var c_name = '<?=COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_FORUM_FILTER"?>';
	if (document.cookie.length>0)
	{
		c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1)
		{ 
			c_start=c_start + c_name.length+1 
			c_end=document.cookie.indexOf(";",c_start)
			if (c_end==-1) 
				c_end=document.cookie.length
			strCookie = unescape(document.cookie.substring(c_start,c_end))
		}
	}
	if (strCookie.length > 0)
	{
		arCookie_ = strCookie.split('/');
		for (var ii=0; ii<arCookie_.length; ii++)
		{
			tmp = arCookie_[ii].split('-');
			if (tmp.length > 0)
				arCookie[tmp[0]] = tmp[1];
		}
	}

	arCookie[id] = (action != 'show' ? 'N':'Y');

	strCookie = "";
	arCookie_ = Array();
	for (var key in arCookie)
	{
		if (arCookie[key] == 'Y')
			arCookie_.push(key+'-'+arCookie[key]);
	}
	
	if (arCookie_.length > 0)
		document.cookie = c_name+'='+arCookie_.join('/')+'; expires=Thu, 31 Dec 2030 23:59:59 GMT; path=/;';
	else
		document.cookie = c_name+"=Y; expires=Sun, 31 Dec 2000 23:59:59 GMT; path=/;";
		
	if (action != 'show')
	{
		section.style.display = 'none';
		document.getElementById(id+'_checkbox').checked = false;
		all_checkbox.checked = false;
	}
	else
	{
		try{
			section.style.display = 'table-row';
		}
		catch(e){
			section.style.display = 'block';
		}
		document.getElementById(id+'_checkbox').checked = true;
	}
}

function SectionGA(id, checkbox_ga)
{
	var action = 'show';
	if (checkbox_ga.checked != true)
		action = 'hide';
	table = document.getElementById(id+'_table');
	
	for(var i=1; i<table.rows.length; i++)
	{
		if (table.rows[i].id.substring(0, id.length) == id)
		{
			SectionSH(table.rows[i].id, action, id);
		}
	}
}

/************************************************/
var jsUtils =
{
	arEvents: Array(),

	addEvent: function(el, evname, func, capture)
	{
		if(el.attachEvent) // IE
			el.attachEvent("on" + evname, func);
		else if(el.addEventListener) // Gecko / W3C
			el.addEventListener(evname, func, false);
		else
			el["on" + evname] = func;
		this.arEvents[this.arEvents.length] = {'element': el, 'event': evname, 'fn': func};
	},

	removeEvent: function(el, evname, func)
	{
		if(el.detachEvent) // IE
			el.detachEvent("on" + evname, func);
		else if(el.removeEventListener) // Gecko / W3C
			el.removeEventListener(evname, func, false);
		else
			el["on" + evname] = null;
	},

	removeAllEvents: function(el)
	{
		for(var i in this.arEvents)
		{
			if(this.arEvents[i] && (el==false || el==this.arEvents[i].element))
			{
				jsUtils.removeEvent(this.arEvents[i].element, this.arEvents[i].event, this.arEvents[i].fn);
				this.arEvents[i] = null;
			}
		}
		if(el==false)
			this.arEvents.length = 0;
	},

	GetRealPos: function(el)
	{
		if(!el || !el.offsetParent)
			return false;
		var res=Array();
		res["left"] = el.offsetLeft;
		res["top"] = el.offsetTop;
		var objParent = el.offsetParent;
		while(objParent && objParent.tagName != "BODY")
		{
			res["left"] += objParent.offsetLeft;
			res["top"] += objParent.offsetTop;
			objParent = objParent.offsetParent;
		}
		res["right"]=res["left"] + el.offsetWidth;
		res["bottom"]=res["top"] + el.offsetHeight;
		
		return res;
	},

	FindChildObject: function(obj, tag_name, class_name)
	{
		if(!obj)
			return null;
		var tag = tag_name.toUpperCase();
		var cl = (class_name? class_name.toLowerCase() : null);
		var n = obj.childNodes.length;
		for(var j=0; j<n; j++)
		{
			var child = obj.childNodes[j];
			if(child.tagName && child.tagName.toUpperCase() == tag)
				if(!class_name || child.className.toLowerCase() == cl)
					return child;
		}
		return null;
	},

	FindNextSibling: function(obj, tag_name)
	{
		if(!obj)
			return null;
		var o = obj;
		var tag = tag_name.toUpperCase();
		while(o.nextSibling)
		{
			var sibling = o.nextSibling;
			if(sibling.tagName && sibling.tagName.toUpperCase() == tag)
				return sibling;
			o = sibling;
		}
		return null;
	},

	FindPreviousSibling: function(obj, tag_name)
	{
		if(!obj)
			return null;
		var o = obj;
		var tag = tag_name.toUpperCase();
		while(o.previousSibling)
		{
			var sibling = o.previousSibling;
			if(sibling.tagName && sibling.tagName.toUpperCase() == tag)
				return sibling;
			o = sibling;
		}
		return null;
	},

	FindParentObject: function(obj, tag_name)
	{
		if(!obj)
			return null;
		var o = obj;
		var tag = tag_name.toUpperCase();
		while(o.parentNode)
		{
			var parent = o.parentNode;
			if(parent.tagName && parent.tagName.toUpperCase() == tag)
				return parent;
			o = parent;
		}
		return null;
	},

	IsIE: function()
	{
		return (document.attachEvent && !this.IsOpera());
	},

	IsOpera: function()
	{
		return (navigator.userAgent.toLowerCase().indexOf('opera') != -1);
	},

	ToggleDiv: function(div)
	{
		var style = document.getElementById(div).style;
		if(style.display!="none")
			style.display = "none";
		else
			style.display = "block";
		return (style.display != "none");
	},

	urlencode: function(s)
	{
		return escape(s).replace(new RegExp('\\+','g'), '%2B');
	},

	OpenWindow: function(url, width, height)
	{
		var w = screen.width, h = screen.height;
		if(this.IsOpera())
		{
			w = document.body.offsetWidth;
			h = document.body.offsetHeight;
		}
		window.open(url, '', 'status=no,scrollbars=yes,resizable=yes,width='+width+',height='+height+',top='+Math.floor((h - height)/2-14)+',left='+Math.floor((w - width)/2-5));
	},
	
	SetPageTitle: function(s)
	{
		document.title = phpVars.titlePrefix+s;
		var h1 = document.getElementsByTagName("H1");
		if(h1)
			h1[0].innerHTML = s;
	},

	LoadPageToDiv: function(url, div_id)
	{
		var div = document.getElementById(div_id);
		if(!div)
			return;
		CHttpRequest.Action = function(result)
		{
			CloseWaitWindow();
			document.getElementById(div_id).innerHTML = result;
		}
		ShowWaitWindow();
		CHttpRequest.Send(url);
	},

	trim: function(s)
	{
		var r, re;
		re = /^[ \r\n]+/g;
		r = s.replace(re, "");
		re = /[ \r\n]+$/g;
		r = r.replace(re, "");
		return r;
	},
	
	Redirect: function(args, url)
	{
		var e = null, bShift = false;
		if(args.length > 0)
			e = args[0];
		if(!e)
			e = window.event;
		if(e) 
			bShift = e.shiftKey;

		if(bShift) 
			window.open(url); 
		else 
		{
			ShowWaitWindow();
			window.location=url;
		}
	},

	False: function(){return false;},

	AlignToPos: function(pos, w, h)
	{
		var x = pos["left"], y = pos["bottom"];

		var body = document.body;
		if((body.clientWidth + body.scrollLeft) - (pos["left"] + w) < 0)
		{
			if(pos["right"] - w >= 0 )
				x = pos["right"] - w;
			else
				x = body.scrollLeft;
		}

		if((body.clientHeight + body.scrollTop) - (pos["bottom"] + h) < 0)
		{
			if(pos["top"] - h >= 0)
				y = pos["top"] - h;
			else
				y = body.scrollTop;
		}
		
		return {'left':x, 'top':y};
	}
}

/************************************************/






















function ForumPopupMenu(id)
{
	var _this = this;
	this.menu_id = id;
	this.controlDiv = null;
	this.dxShadow = 5
	this.OnClose = null;
	
	this.Show = function(div, left, top)
	{
		var zIndex = parseInt(div.style.zIndex);
		if(zIndex <= 0 || isNaN(zIndex))
			zIndex = 100;
		div.style.zIndex = zIndex;
		div.style.left = left + "px";
		div.style.top = top + "px";

		if(jsUtils.IsIE())
		{
			var frame = document.getElementById(div.id+"_frame");
			if(!frame)
			{
				frame = document.createElement("IFRAME");
				frame.src = "javascript:''";
				frame.id = div.id+"_frame";
				frame.style.position = 'absolute';
				frame.style.zIndex = zIndex-1;
				document.body.appendChild(frame);
			}
			frame.style.width = div.offsetWidth + "px";
			frame.style.height = div.offsetHeight + "px";
			frame.style.left = div.style.left;
			frame.style.top = div.style.top;
			frame.style.visibility = 'visible';
		}
	}

	this.PopupShow = function(pos)
	{
		var div = document.getElementById(this.menu_id);
		if(!div)
			return;

		setTimeout(function(){jsUtils.addEvent(document, "click", _this.CheckClick)}, 10);
		jsUtils.addEvent(document, "keypress", _this.OnKeyPress);

		var w = div.offsetWidth;
		var h = div.offsetHeight;
		pos = jsUtils.AlignToPos(pos, w, h);

		div.style.width = w + 'px';
		div.style.visibility = 'visible';
		
		this.Show(div, pos["left"], pos["top"]);

		div.ondrag = jsUtils.False;
		div.onselectstart = jsUtils.False;
		div.style.MozUserSelect = 'none';
	}

	this.PopupHide = function()
	{
		var div = document.getElementById(this.menu_id);
		if(div)
		{
			var frame = document.getElementById(div.id+"_frame");
			if(frame)
				frame.style.visibility = 'hidden';

			div.style.visibility = 'hidden';
		}

		if(this.OnClose)
			this.OnClose();

		this.controlDiv = null;
		jsUtils.removeEvent(document, "click", _this.CheckClick);
		jsUtils.removeEvent(document, "keypress", _this.OnKeyPress);
	}

	this.CheckClick = function(e)
	{
		var div = document.getElementById(_this.menu_id);
		if(!div)
			return;

		if (div.style.visibility != 'visible')
			return;

		var x = e.clientX + document.body.scrollLeft;
		var y = e.clientY + document.body.scrollTop;

		/*menu region*/
		var posLeft = parseInt(div.style.left);
		var posTop = parseInt(div.style.top);
		var posRight = posLeft + div.offsetWidth;
		var posBottom = posTop + div.offsetHeight;
		if(x >= posLeft && x <= posRight && y >= posTop && y <= posBottom)
			return;

		if(_this.controlDiv)
		{
			var pos = jsUtils.GetRealPos(_this.controlDiv);
			if(x >= pos['left'] && x <= pos['right'] && y >= pos['top'] && y <= pos['bottom'])
				return;
		}
		_this.PopupHide();
	}

	this.OnKeyPress = function(e)
	{
		if(!e) e = window.event
		if(!e) return;
		if(e.keyCode == 27)
			_this.PopupHide();
	},

	this.IsVisible = function()
	{
		return (document.getElementById(this.menu_id).style.visibility != 'hidden');
	}

	this.ShowMenu = function(control)
	{
		if(this.controlDiv == control)
			this.PopupHide();
		else
		{
			this.PopupHide();
			
			control.className += '-hover';
			
			var pos = jsUtils.GetRealPos(control);
			
			pos["bottom"]+=2;

/*			if(!jsUtils.IsIE())
			{
				pos["top"] += document.body.scrollTop;
				alert('document.body.scrollTop = '+document.body.scrollTop);
				
				pos["bottom"] += document.body.scrollTop;
				pos["left"] += document.body.scrollLeft;
				pos["right"] += document.body.scrollLeft;
			}
*/
			this.controlDiv = control;
			this.OnClose = function()
			{
				control.className = control.className.replace(/\-hover/ig, "");
			}
			this.PopupShow(pos);
		}
	}
}