<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/template.php';
include_once '../lib/familize.php';
include_once '../lib/photoForm.php';
include_once '../lib/searchForm.php';

$photo_id = $_REQUEST['url'][0];
list($photo_id, $set, $set_id, $set_offset) = $_REQUEST['url'];

GTools::logOutput("--- Loading View Photo ---");

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$phto = new PhotoForm($db, $famu, $tmpl);
$phto->setPhoto($photo_id);
$phto->setPhotoSet($set, $set_id, $set_offset);
//print_r($_POST);

// process posted comments (normal comments are posted to comment_server.php
if (isset($_POST['new']['comment'])) {
	include_once '../lib/commentForm.php';
	$como = new CommentForm($db, $famu, $tmpl);
	$como->newPhotoComment($photo_id, $_POST['new']['comment'], $_POST['new']['user_id']);
}

// graceful degredation of search users form - allow normal $_POST submission
if (isset($_POST['new_feat_id'])) {
	// add user by id
	if ($_POST['new_feat_id'] != '') {
		$addMessage = ($phto->addFeatured($_POST['new_feat_id'])) ? "{$_POST['searchString']} added" : "Action Failed!";

	// add user by name
	} else {
		$srch = new SearchForm($db, $famu, $tmpl);
		list($users, $unique) = $srch->searchUsersForFullName($_POST['searchString']);
		if ($unique) {
			$addMessage = ($phto->addFeatured($users[0]['user_id'])) ? "{$_POST['searchString']} added" : "Action Failed!";
		} elseif (count($users) == 0) {
			$addMessage = "No Users Found";
		} else {
			$addMessage = "Ambiguous Name";
		}
	}
	$js .= "alert('{$addMessage}');";

// graceful degredation of tags search form - allow normal $_POST submission
} elseif (isset($_POST['new_tags_id']) && $_POST['searchString']!='') {
	$addMessage = ($phto->addTags($_POST['searchString'])) ? "{$_POST['searchString']} added" : "Action Failed!";
	$js .= "alert('{$addMessage}');";
}

$js .= <<<EOD
	var ajaxOptions;
	var generalRules = {
		'.commentAlert' : function(el) {
			el.onclick = function() {
				alert('This feature is in testing.  As in ..."coming soon".  \\nYou can prepare for this feature by verifying your contact info.');
			}
		},
		'.favorite' : function(el) {
			el.onclick = function() {
				aId = el.id.split('_');
				new Ajax.Updater(el.id, '/photo_server/favorite/'+aId[1]);
			}
		},
		'.flagged' : function(el) {
			el.onclick = function() {
				aId = el.id.split('_');
				new Ajax.Updater(el.id, '/photo_server/flagged/'+aId[1], {onSuccess: check_unseen });
			}
		},
	};
	Behaviour.register(generalRules);
EOD;

if ($phto->photo->editable) {
	$js .= <<<EOD

	var photoRules = {
		'.caption' : function(el) {
			el.onclick = function(){
				aId = el.id.split('_');
				new Ajax.Updater(el.id, '/photo_server/caption/'+aId[1],{
					onComplete: function(transport, json) {
						$(json.edit_id).focus();
					}
				});
			},
			el.onmouseover = function(){
				el.className = "caption editable";
			},
			el.onmouseout = function(){
				el.className = "caption";
			}
		},
		'.description' : function(el) {
			el.onclick = function(){
				aId = el.id.split('_');
				new Ajax.Updater(el.id, '/photo_server/description/'+aId[1],{
					onComplete: function(transport, json) {
						$(json.edit_id).focus();
					}
				});
			},
			el.onmouseover = function(){
				el.className = "description editable";
			},
			el.onmouseout = function(){
				el.className = "description";
			}
		},
		'.taken' : function(el) {
			el.onclick = function(){
				aId = el.id.split('_');
				new Ajax.Updater(el.id, '/photo_server/taken/'+aId[1], {
					onComplete: function(transport, json) {
						$(json.edit_id).focus();
					}
				});
			},
			el.onmouseover = function(){
				el.className = "taken editable";
			},
			el.onmouseout = function(){
				el.className = "taken";
			}
		}
	};
	Behaviour.register(photoRules);

EOD;
}

if ($phto->photo->editable || $famu->moderator) {
	$js .= <<<EOD
	var moderatorRules = {
		'.feat_submit' : function(el) {
			el.onclick = function() {
				aId = el.id.split('_');
				new Ajax.Updater('feat_list', '/photo_server/addfeatured/'+aId[1]+'/'+$('feat').value+'/'+$('new_feat_id').value+'/', { 
					onSuccess: function(transport, json) { 
						$('feat_list_message').innerHTML = ' ('+json.feat_message+')';
						new Effect.Appear('feat_list_message', {scope: 'feat_message'});
						new Effect.Fade('feat_list_message', {scope: 'feat_message', queue: 'end', duration: 2});
						new Effect.Highlight('feat_list', {startcolor: '#ffffdd', endcolor: '#ffffff'});
						$('feat').value=''; 
						$('feat').focus(); 
					}
				});
				return false;
			}
		},
		'.tags_submit' : function(el) {
			el.onclick = function() {
				aId = el.id.split('_');
				new Ajax.Updater('tags_list', '/photo_server/addtags/'+aId[1]+'/'+$('tags').value+'/'+$('new_tags_id').value+'/', { 
					onSuccess: function(transport, json) { 
						$('tags_list_message').innerHTML = ' ('+json.tags_message+')';
						new Effect.Appear('tags_list_message', {scope: 'tags_message'});
						new Effect.Fade('tags_list_message', {scope: 'tags_message', queue: 'end', duration: 2});
						new Effect.Highlight('tags_list', {startcolor: '#ffffdd', endcolor: '#ffffff'});
						$('tags').value=''; 
						$('tags').focus(); 
					}
				});
				return false;
			}
		}
	}
	Behaviour.register(moderatorRules);

	function removeFeat(photo_id, user_id) {
		new Effect.Squish('feat_list_'+user_id);
		new Ajax.Request('/photo_server/remfeatured/'+photo_id+'/'+user_id+'/', {
			onSuccess: function(transport, json) {
				$('feat_list_message').innerHTML = ' ('+json.feat_message+')';
				new Effect.Appear('feat_list_message', {scope: 'feat_message'});
				new Effect.Fade('feat_list_message', {scope: 'feat_message', queue: 'end', duration: 2});
				$('feat').value=''; 
				$('feat').focus(); 
			}
		}); 
		return false;		
	}

	function removeTags(photo_id, tags_id) {
		new Effect.Squish('tags_list_'+tags_id);
		new Ajax.Request('/photo_server/remtags/'+photo_id+'/'+tags_id+'/', {
			onSuccess: function(transport, json) {
				$('tags_list_message').innerHTML = ' ('+json.tags_message+')';
				new Effect.Appear('tags_list_message', {scope: 'tags_message'});
				new Effect.Fade('tags_list_message', {scope: 'tags_message', queue: 'end', duration: 2});
				$('tags').value=''; 
				$('tags').focus(); 
			}
		});
		return false;		
	}

	function photoInit() {
		new Ajax.Autocompleter('feat', 'feat_auto_complete', '/search_server/users/', {
			onSuccess: function() { $('new_feat_id').value = ''; }
		});
		new Ajax.Autocompleter('tags', 'tags_auto_complete', '/search_server/tags/', {
			onSuccess: function() { $('new_tags_id').value = ''; }
		});
	}
	Behaviour.addLoadEvent(photoInit);

EOD;
}


$tmpl->addJS($js);
//$tmpl->title = "Photos";
$tmpl->title = $phto->photo->img['caption'];
$tmpl->pageHeader();
print $phto->getPage();
$tmpl->pageFooter();

//print $_SERVER['HTTP_REFERER'];

?>
