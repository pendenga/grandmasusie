var time_start;
var global_search_seq = 0;
var global_search_ack = 0;
function g_ajaxInit() {
	// initialize page timers
	var start = new Date();
	time_start = start.getTime(); 

	// get unseen list
	/*
	new Ajax.PeriodicalUpdater('unseen', '/login_server/unseen/', {
		method: 'get',
		frequency: 15,
		decay: 2
	});
	*/

	// General ajax responders, will respond to every ajax request
	Ajax.Responders.register({
		onCreate: function(){
			//$('progress').style.top = scrollTop()+'px';
			$('progress').show();
		}, 
		onComplete: function(){
			//$('progress').hide();
			new Effect.Fade('progress');
		},
		onSuccess: function(){
			//$('progress').hide();
			new Effect.Fade('progress');
		},
		onFailure: function() { 
			alert('Something went wrong...')
		}
	});


	// Startup Click Heat
	/*
	clickHeatSite = ''; 
	if (clickHeatPage == '') {
		clickHeatPage = 'index'; 
	}
	initClickHeat();
	*/
}
Behaviour.addLoadEvent(g_ajaxInit);

function check_unseen() {
	new Ajax.Updater('unseen', '/login_server/unseen/', { onSuccess: unseen_notify });
}
function unseen_notify() {
	new Effect.Highlight('unseen', {startcolor:'#ffff99', endcolor:'#9ab43f'});
}

/**
 * ScrollTop function from from Rick Strahl's Web Log: http://www.west-wind.com/WebLog/posts/4607.aspx
 */
function scrollTop() {
	var ScrollTop = document.body.scrollTop;
	if (ScrollTop == 0)	{
		if (window.pageYOffset) {
			ScrollTop = window.pageYOffset;
		} else {
			ScrollTop = (document.body.parentElement) ? document.body.parentElement.scrollTop : 0;
		}
	}
	return ScrollTop;
}

var templateRules = {
	'#login_switcher' : function(el) { el.onchange = function() {
		new Ajax.Updater('active_user', '/login_server/switchto/'+$F('login_switcher'), {
			onSuccess: function(transport, json) {
				if (json.passive == 1) {
					new Effect.Fade('unseen');
					new Effect.Fade('check_unseen');
					//$('unseen').className = "hiddenDiv";
					//$('check_unseen').className = "hiddenDiv";
				} else {
					new Ajax.Updater('unseen', '/login_server/unseen/'+$F('login_switcher'), { onSuccess: unseen_notify });
					new Effect.Appear('unseen');
					new Effect.Appear('check_unseen');
					//$('unseen').className = "";
					//$('check_unseen').className = "smallbuttons";
				}
			}
		});
	}},
	'#check_unseen' : function(el) { el.onclick = check_unseen },
	'.modalbox' : function(el) { el.onclick = function() {
		Modalbox.show(this.title, this.href, {
			width: 500, 
			height: 220, 
			afterLoad: function() { $('progress').className = 'hiddenDiv'; }
		});
		return false;
	}},
	/*
	'.help' : function(el) {
		new Tooltip(el, {backgroundColor: "#333", borderColor: "#333", textColor: "#FFF", textShadowColor: "#000"});
	},
	*/
	'.required' : function(el) { 
		el.onblur = function() {

			if (el.hasClassName('email') && /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(el.value)==false) {
				$(el.id+"_label").addClassName('error');
				if (Element.visible($(el.id+"_ok"))) {new Effect.Fade($(el.id+"_ok"))}
				new Effect.Appear($(el.id+"_warning"), {queue: 'end'});

			} else if (el.value=='' || el.selectedIndex==0) {
				$(el.id+"_label").addClassName('error');
				if (Element.visible($(el.id+"_ok"))) {new Effect.Fade($(el.id+"_ok"))}
				new Effect.Appear($(el.id+"_warning"), {queue: 'end'});

			} else {
				$(el.id+"_label").removeClassName('error');
				if (Element.visible($(el.id+"_warning"))) {new Effect.Fade($(el.id+"_warning"))}
				new Effect.Appear($(el.id+"_ok"), {queue: 'end'});
			}
			Element.hide(el.id+"_hint");
		},
		el.onfocus = function() { 
			new Effect.Appear(el.id+"_hint", { duration: .5 });
			new Effect.Highlight(el.id+"_label");
		} 
	}
};
Behaviour.register(templateRules);

function loginSwitcher(switcher) {
	
}
