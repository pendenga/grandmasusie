// position of the tooltip relative to the mouse in pixel //
var offsetx = 12;
var offsety =  8;

function newelement(newid) { 
    if(document.createElement)     { 
        var el = document.createElement('div'); 
        el.id = newid;     
        with(el.style) { 
            display = 'none';
            position = 'absolute';
        } 
        el.innerHTML = '&nbsp;'; 
        document.body.appendChild(el); 
    } 
} 

var ie5 = (document.getElementById && document.all); 
var ns6 = (document.getElementById && !document.all); 

function getmouseposition(e) {
    if(document.getElementById)     {
		var iebody=(document.compatMode && document.compatMode != "BackCompat")? document.documentElement : document.body;
		pagex = (ie5)?iebody.scrollLeft:window.pageXOffset;
		pagey = (ie5)?iebody.scrollTop:window.pageYOffset;
        mousex = (ie5)?event.x:(ns6)?clientX = e.clientX:false;
        mousey = (ie5)?event.y:(ns6)?clientY = e.clientY:false;

		var lixlpixel_tooltip = document.getElementById('tooltip');
		var lixlpixel_toolmenu = document.getElementById('toolmenu');

		if (lixlpixel_tooltip && lixlpixel_tooltip.style.display != 'none') {
			if (lixlpixel_toolmenu) lixlpixel_toolmenu.style.display = 'none';
			lixlpixel_tooltip.style.left = (mousex+pagex+offsetx) + 'px';
			lixlpixel_tooltip.style.top = (mousey+pagey+offsety) + 'px';
		}
		if (lixlpixel_toolmenu && lixlpixel_toolmenu.style.display != 'none') {
			if (lixlpixel_tooltip) lixlpixel_tooltip.style.display = 'none';
	        lixlpixel_toolmenu.style.left = (mousex+pagex+offsetx) + 'px';
		    lixlpixel_toolmenu.style.top = (mousey+pagey+offsety) + 'px';	
		}
	}
}

function tooltip(tip) {
    if(!document.getElementById('tooltip')) newelement('tooltip');
    var lixlpixel_tooltip = document.getElementById('tooltip');
    lixlpixel_tooltip.innerHTML = tip;
    lixlpixel_tooltip.style.display = 'block';
    document.onmousemove = getmouseposition;
}

function tooltip_exit() {
    document.getElementById('tooltip').style.display = 'none';
	document.onmousemove = '';
}

function toolmenu(e) {
    if(!document.getElementById('toolmenu')) newelement('toolmenu');
    var lixlpixel_toolmenu = document.getElementById('toolmenu');
	var lixlpixel_tools = document.getElementById(e.target.id+'_tools');

	lixlpixel_toolmenu.innerHTML = lixlpixel_tools.innerHTML + "<div class=\"exit\"><a href=# onclick=toolmenu_exit()>[close]</a></div>";
    lixlpixel_toolmenu.style.display = 'block';
	getmouseposition(e);
}

function toolmenu_exit() {
    document.getElementById('toolmenu').style.display = 'none';
}
