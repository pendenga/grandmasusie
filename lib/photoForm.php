<?php

include_once 'module.php';
include_once 'photo.php';
include_once '../lib/extlib/markdown.php';
include_once '../helpers/formattime.php';

class PhotoForm extends Module {
	protected $photo_array;
	protected $last;
	protected $set_key;
	protected $set_id;
	protected $set_offset;
	public $photo;
	public $photo_count;

	function captionEdit($edit_id) {
		return <<<EOD
		<form id="caption_edit_form"><input type="text" id="{$edit_id}" name="caption" value="{$this->photo->img['caption']}" size="70" maxlength="55" onblur="new Ajax.Updater('caption_{$this->photo->img['photo_id']}', '/photo_server/caption/{$this->photo->img['photo_id']}/', {method: 'post', parameters: $('caption_edit_form').serialize(true)});"/></form>
EOD;
	}

	function captionSave($caption) {
		return $this->photo->saveCaption($caption);
	}

	function descriptionEdit($edit_id) {
		return <<<EOD
		<form id="description_edit_form"><textarea cols="59" rows="4" name="description" id="{$edit_id}" onblur="new Ajax.Updater('description_{$this->photo->img['photo_id']}', '/photo_server/description/{$this->photo->img['photo_id']}/', {method: 'post', parameters: $('description_edit_form').serialize(true)});">{$this->photo->img['description']}</textarea></form>
EOD;
	}

	function descriptionSave($description) {
		$this->photo->saveDescription($description);
	}

	function takenEdit($edit_id) {
		if ($this->photo->img['take_dt'] != '') {
			$taken = date('n/j/y', strtotime($this->photo->img['take_dt']));
		}
		return <<<EOD
		<form id="taken_edit_form">Taken: 
			<input type="text" name="taken" class="smallform" id="{$edit_id}" value="{$taken}" onblur="new Ajax.Updater('taken_{$this->photo->img['photo_id']}', '/photo_server/taken/{$this->photo->img['photo_id']}/', {method: 'post', parameters: $('taken_edit_form').serialize(true), onComplete: function(transport, json) { if (json.error_msg) { alert(json.error_msg);}}});"/>
		</form>
EOD;
	}

	function takenSave($taken) {
		$this->photo->saveTaken($taken);
	}

	function favoriteIcon() {
		$link = ($this->photo->img['favorite']) ? '<a href="#" class="active">A Favorite</a>' : '<a href="#">Add to Favorites</a>';
		return "<div id=\"favorite_{$this->photo->img['photo_id']}\" class=\"favorite\">{$link}</div>";
	}

	function favoriteToggle() {	
		$this->photo->saveFavorite(!$this->photo->img['favorite']);
	}

	function flaggedIcon() {
		$link = ($this->photo->img['flagged']) ? '<a href="#" class="active">Flagged</a>' : '<a href="#">Flag This</a>';
		return "<div id=\"flagged_{$this->photo->img['photo_id']}\" class=\"flagged\">{$link}</div>";
	}

	function flaggedToggle() {	
		$this->photo->saveFlagged(!$this->photo->img['flagged']);
	}

	function originalIcon() {
		$orig = $this->photo->getUrl_original();
		return "<a href=\"{$orig}\" target=\"_new\" title=\"View Original Size Photo ({$this->photo->img['orig_width']}x{$this->photo->img['orig_height']})\" class=\"help\">Original Photo</a>";
	}

	function alertIcon() {
		$link = ($this->photo->img['alert_me']) ? '<a href="#" class="active">Comment Alert</a>' : '<a href="#">Alert Me</a>';
		return "<div id=\"commentAlert_{$this->photo->img['photo_id']}\" class=\"commentAlert\">{$link}</div>";
	}

	function avatarIcon() {
		return "<a href=\"/useredit/avatar/{$this->photo->img['photo_id']}/\" target=\"avatar\" title=\"Create Your Avatar From This Photo\" class=\"help\">Change Avatar</a>";
	}

	function prevNext() {
		GTools::logOutput(" Setting Previous and Next photo");
		// define the limit and offset for set queries
		if ($this->set_offset > 0) {
			$offset = $this->set_offset - 1;
			$limit = 3;
		} else {
			$offset = 0;
			$limit = 2;
		}

		GTools::logOutput("set key: {$this->set_key}");

		// choose a set query or default to all photos
		switch ($this->set_key) {
		case 'in_user':
			$count = $this->db->do_sql("call photoSetUserCount(1, {$this->set_id})");
			$info = $this->db->do_sql("call photoSetUser(1, {$this->set_id}, {$limit}, {$offset})");
			$user = $this->db->do_sql("select * from user_name where user_id={$this->set_id}");
			$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
			$set_name = "<a href=\"/user/{$this->set_id}/photos/\">{$user[0]['common_name']}'s Photos</a>";
			break;

		case 'in_sameage':
			$range = intval(sqrt(floatval($this->set_id)));
			$low = $this->set_id-$range;
			$high = $this->set_id+$range;
			$info = $this->db->do_sql("SELECT SQL_CALC_FOUND_ROWS p.photo_id, u.user_id, p.take_dt, p.server_id, p.photo_uid, p.caption, p.description, p.orig_size, p.orig_height, p.orig_width, p.mime, p.ext, p.private, p.complete, p.uploaded_dt, u.full_name, u.common_name, u.avatar_id, concat('featuring ', u.full_name) title FROM user_name u INNER JOIN photo_featuring pf ON u.user_id=pf.user_id INNER JOIN photo p ON pf.photo_id=p.photo_id WHERE u.birth_date IS NOT NULL AND u.birth_year_unknown=0 AND datediff(p.take_dt, u.birth_date) BETWEEN {$low} AND {$high} ORDER BY p.take_dt DESC LIMIT {$limit} OFFSET {$offset}");
			$count = $this->db->do_sql("SELECT FOUND_ROWS() AS photoCount");
			$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
			$fmt_age = FormatTime::ageInDays($this->set_id);
			$set_name = "<a href=\"/sameage/{$this->set_id}\">{$fmt_age}</a>";
			break;

		case 'in_faves':
			$count = $this->db->do_sql("call photoSetFavoriteCount(1, {$this->set_id})");
			$info = $this->db->do_sql("call photoSetFavorite(1, {$this->set_id}, {$limit}, {$offset})");
			$user = $this->db->do_sql("select * from user_name where user_id={$this->set_id}");
			$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
			$set_name = "<a href=\"/user/{$this->set_id}/favorites/\">{$user[0]['common_name']}'s Favorites</a>";
			break;

		case 'in_feat':
			$count = $this->db->do_sql("call photoSetFeaturedCount(1, {$this->set_id})");
			$info = $this->db->do_sql("call photoSetFeatured(1, {$this->set_id}, {$limit}, {$offset})");
			$user = $this->db->do_sql("select * from user_name where user_id={$this->set_id}");
			$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
			$set_name = "<a href=\"/user/{$this->set_id}/featured/\">{$user[0]['common_name']} Featured</a>";
			break;
	
		case 'in_gallery':
			switch ($this->set_id) {
			case 'favorite':
				$count = $this->db->do_sql("call photoSetAllFavoritesCount(1)");
				$info = $this->db->do_sql("call photoSetAllFavorites(1, {$limit}, {$offset})");
				$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
				$set_name = "<a href=\"/gallery/favorite/\">Favorite Photos</a>";
				break;
			case 'group':
				$count = $this->db->do_sql("call photoSetGroupCount(1, 4)");
				$info = $this->db->do_sql("call photoSetGroup(1, 4, {$limit}, {$offset})");
				$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
				$set_name = "<a href=\"/gallery/group/\">Group Photos</a>";
				break;
			case 'unseen':
				$count = $this->db->do_sql("call photoUnseenCount({$this->famu->active_id}, 30)");
				$offset = max(0, min($offset, $count[0]['photoCount']-2));
				$info = $this->db->do_sql("call photoUnseenComment({$this->famu->active_id}, 30, {$limit}, {$offset})");
				$set_count = "{$count[0]['photoCount']}";
				$set_name = "<a href=\"/gallery/newcomment/\">Unseen Photos/Comments</a>";		
				break;
			default:
			}
			break;

		case 'tagged':
			$rs = $this->db->do_sql("SELECT * FROM tag WHERE tag='{$this->set_id}'");
			$tag_id = (count($rs) > 0) ? $rs[0]['tag_id'] : false;
			if ($tag_id) {
				$count = $this->db->do_sql("call photoSetTagCount(1, {$tag_id})");
				$info = $this->db->do_sql("call photoSetTags(1, {$tag_id}, {$limit}, {$offset})");
				$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
			}
			$set_name = "<a href=\"/tags/{$this->set_id}/\">'{$this->set_id}'</a>";
			break;

		case 'gallery':
			$count = $this->db->do_sql("call photoSetRecentCount(1)");
			$info = $this->db->do_sql("call photoSetRecent(1, {$this->famu->active_id}, {$limit}, {$offset})");
			$set_count = ($this->set_offset+1)." of {$count[0]['photoCount']}";
			$set_name = "<a href=\"/gallery/\">All Photos</a>";
			break;

		default:
			$this->setPhotoSet();
			$set_name = "<a href=\"/gallery/\">All Photos</a>";
			$set_count = 'Place';
			// next & prev
			$prev = $this->nextPhoto();
			$next = $this->prevPhoto();
		}

		
		if ($this->set_id=='unseen') {
			// for unseen photos/comments the set will diminish to zero
			if (count($info)>0) {
				$prev = new Photo($this->db, $this->famu, $info[0]);
			}
			if (count($info)>1) {
				$next = new Photo($this->db, $this->famu, $info[1]);
			}
		} else {
			// format for two or three results
			if (count($info)==3) {
				$prev = new Photo($this->db, $this->famu, $info[0]);
				$next = new Photo($this->db, $this->famu, $info[2]);
			} elseif (count($info)==2 && $this->set_offset==0) {
				$next = new Photo($this->db, $this->famu, $info[1]);
			} elseif (count($info)==2) {
				$prev = new Photo($this->db, $this->famu, $info[0]);
			}
		}

		// format prev/next links
		if ($prev || $next) {
			if (isset($prev->img['title'])) {
				$prevLink = $this->getSquareLink($prev, $prev->img['title'], -1);
			} else {
				$prevName = ($prev->img['full_name']=='') ? "{$prev->img['first_name']} {$prev->img['last_name']}" : $prev->img['full_name'];
				$prevLink = $this->getSquareLink($prev, "{$prev->img['caption']} - by {$prevName}", -1);
			}
			if (isset($next->img['title'])) {
				$nextLink = $this->getSquareLink($next, $next->img['title'], 1);
			} else {
				$nextName = ($next->img['full_name']=='') ? "{$next->img['first_name']} {$next->img['last_name']}" : $next->img['full_name'];
				$nextLink = $this->getSquareLink($next, "{$next->img['caption']} - by {$nextName}", 1);	
			}
		}

		// link back to main group
		if ($this->set_key != '') {
			$homeLink = "<div class=\"detail\"><a href=\"/viewphoto/{$this->photo->img['photo_id']}/\">View in ALL PHOTOS</a></div>";
		}
		return <<<EOD
			<h4>{$set_count} in <em>{$set_name}</em></h4>
			{$prevLink}{$nextLink}{$homeLink}
EOD;
	}

	function nextPhoto($date=false) {
		if ($date===false) { 
			$date = $this->photo->img['uploaded_dt'];
		}

		switch ($this->set_key) {
		case 'in_user':
			$this->set_key = $key;
			$this->set_id = $id;
		default:
			$info = $this->db->do_sql("SELECT p.photo_id, p.user_id, p.take_dt, p.take_exif, p.filename, p.server_id, p.photo_uid, p.caption, p.description, p.orig_size, p.orig_height, p.orig_width, p.mime, p.ext, p.private, p.complete, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id FROM photo p INNER JOIN users u ON u.user_id=p.user_id WHERE p.complete=1 AND p.uploaded_dt > '{$date}' ORDER BY uploaded_dt ASC LIMIT 1");
		}

		if (count($info)>0) {
			return new Photo($this->db, $this->famu, $info[0]);
		} else {
			return false;
		}
	}

	function prevPhoto($date=false) {
		if ($date===false) { 
			$date = $this->photo->img['uploaded_dt'];
		}
		$info = $this->db->do_sql("SELECT p.photo_id, p.user_id, p.take_dt, p.take_exif, p.filename, p.server_id, p.photo_uid, p.caption, p.description, p.orig_size, p.orig_height, p.orig_width, p.mime, p.ext, p.private, p.complete, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id FROM photo p INNER JOIN users u ON u.user_id=p.user_id WHERE p.complete=1 AND p.uploaded_dt < '{$date}' ORDER BY uploaded_dt DESC LIMIT 1");
		return new Photo($this->db, $this->famu, $info[0]);
		if (count($info)>0) {
			return new Photo($this->db, $this->famu, $info[0]);
		} else {
			return false;
		}
	}

	function getSquareLink($phto=false, $title='', $rel_offset=0) {
		if ($phto != false) {
			$title = str_replace('"', "'", $title);
			$imgsrc = $phto->getUrl_square();
			$class = ($rel_offset > 0) ? 'next' : 'prev';
			$link = $this->getSetLink($rel_offset);
			$imhref = "/viewphoto/{$phto->img['photo_id']}/{$link}";
			return "<a href=\"{$imhref}\" class=\"{$class}\"><img src=\"{$imgsrc}\" border=\"0\" title=\"{$title}\" class=\"help\"/></a>";
		}
	}

	function addFeatured($user_id) {
		return $this->photo->addUserFeatured($user_id);
	}

	function addTags($tag_text) {
		return $this->photo->assignTag($tag_text);
	}

	function removeFeatured($user_id) {
		return $this->photo->removeUserFeatured($user_id);
	}
	
	function removeTags($tag_id) {
		return $this->photo->unassignTag($tag_id);
	}

	function getFeatured() {
		if ($this->photo != false) {
			$feats = $this->photo->getUsersFeatured();
			foreach ($feats as $feat) {
				$ageStr = ($feat['age_in_photo']=='') ? '' : " was <a href=\"/sameage/{$feat['age_in_photo']}/\">".FormatTime::ageInDays($feat['age_in_photo']).'</a>';
				$avtr = $this->famu->getCustomAvatarLink($feat['avatar_id'], "/user/{$feat['user_id']}/featured/", "View photos featuring {$name}", $feat['full_name'], 32);
				$remover = ($this->famu->moderator) ? " <a href=\"#\" onclick=\"removeFeat({$this->photo->img['photo_id']}, {$feat['user_id']})\" class=\"remove\">(remove)</a>" : '';
				$featStr .= "<li id=\"feat_list_{$feat['user_id']}\">{$avtr}{$ageStr}{$remover}</li>";
			}
			return $featStr;
		}
	}
	
	function getTags() {
		if ($this->photo != false) {
			$tags = $this->photo->getTagsAssigned();
			foreach ($tags as $tag) {
				$linker = "<a href=\"/tags/{$tag['tag']}\">{$tag['tag']}</a>";
				$remover = ($this->famu->moderator) ? " <a href=\"#\" onclick=\"removeTags({$this->photo->img['photo_id']}, {$tag['tag_id']})\" class=\"remove\">(remove)</a>" : '';
				$tagsStr .= "<li id=\"tags_list_{$tag['tag_id']}\">{$linker}{$remover}</li>";
			}
			return $tagsStr;
		}
	}
	

	function getPage() {
		$mag = $this->originalIcon();
		$fav = $this->favoriteIcon();
		$flg = $this->flaggedIcon();
		$avt = $this->avatarIcon();
		$alr = $this->alertIcon();
		$src = $this->photo->getUrl_medium();
		$cap = strip_tags($this->photo->img['caption'], '<a><p><img>');
		$cap = (!$cap) ? ($this->photo->editable) ? '<em>click here to add a caption</em>' : '' : $cap;
		$des = $this->photo->img['description'];
		$des = (!$des) ? ($this->photo->editable) ? '<em>click here to add a description</em>' : '' : Markdown($des);

		// author information
		$date = GTools::postTime($this->photo->img['uploaded_dt']);
		$post = "Uploaded {$date}";
		$name = ($this->photo->img['full_name']=='') ? "{$this->photo->img['first_name']} {$this->photo->img['last_name']}" : $this->photo->img['full_name'];
		$avtr = $this->famu->getFullAvatarLink($this->photo->img['avatar_id'], $this->photo->img['user_id'], $name, "by {$name}");
				
		// detailed information
		if ($this->photo->img['take_dt']!='') {
			$take = GTools::takeTime($this->photo->img['take_dt'], $this->photo->img['take_exif']);
		} 			
		if ($this->photo->editable || $this->famu->moderator) {
			$take = ($take) ? "Taken <em>{$take}</em>" : "<em>When was this photo taken?</em>";
			$details .= "<li class=\"taken\" id=\"taken_{$this->photo->img['photo_id']}\">{$take}</li>";
		} elseif ($take) {
			$details .= "<li>Taken {$take}</li>";			
		}
		if ($this->photo->img['ext']=='jpg' || $this->photo->img['mime']=='image/jpeg') {
			$old_err = error_reporting(0);
			$exif = exif_read_data($this->photo->getFile_original());
			if ($exif['Model']!='') {
				$details .= (stripos($exif['Model'], $exif['Make'])===false) ? "<li>Taken with {$exif['Make']} {$exif['Model']}</li>" : "<li>Taken with {$exif['Model']}</li>";
			}
			error_reporting($old_err);
		}
		if ($this->photo->img['orig_size']!='') {
			$size = GTools::convertBytes($this->photo->img['orig_size']);
			$details .= "<li>Original is {$size} ({$this->photo->img['orig_width']}x{$this->photo->img['orig_height']}px)</li>";
		}
		list($views, $faves, $lastView) = $this->photo->getViewsAndFaves();
		$info = getimagesize($this->photo->getFile_medium());
		$peeps = ($faves>0) ? ($faves==1) ? "<a href=\"/favorites/{$this->photo->img['photo_id']}/\">{$faves} person</a> calls" : "<a href=\"/favorites/{$this->photo->img['photo_id']}/\">{$faves} people</a> call" : "{$faves} people call";
		$details .= "<li>{$peeps} this a favorite</li>";
		$peeps = ($views==1) ? "{$views} other person" : "{$views} other people";
		$details .= "<li>Viewed by {$peeps}</li>";
		$last = ($lastView=='') ? "You are the first to view" : "Last Viewed ".GTools::postTime($lastView);
		$details .= "<li>{$last}</li>";

		// navigation to previous and next
		$prevnext = $this->prevNext();

		// get all comments together
		include_once '../lib/commentForm.php';
		$como = new CommentForm($this->db, $this->famu, $this->tmpl);
		$como->setPhotoComments($this->photo->img['photo_id'], $this->last);
		$commentForm = $como->getPage($this->photo->img['photo_id']);

		// LEFT COLUMN //
		// LEFT COLUMN //
		// LEFT COLUMN //

		// get search users form
		if ($this->famu->moderator) {
			$featuredForm = <<<EOD
			<form id="addFeaturedUser" class="sideSearch" method="POST">
				<input type="text" autocomplete="off" id="feat" name="searchString" size="25" value="" class="smallinput"/>
				<input type="hidden" id="new_feat_id" name="new_feat_id" value=""/>Search Users: 
				<input type="button" id="featsubmit_{$this->photo->img['photo_id']}" class="feat_submit smallbuttons" value="add user">
				<div class="auto_complete" id="feat_auto_complete" style="display: none"></div>
			</form>

EOD;
		}
		// get featured users
		$featuredList = $this->getFeatured();
		if ($featuredList != '' || $this->famu->moderator) {
			$featuredInfo = <<<EOD
			<fieldset class="photoDetail">
				<legend>In This Photo<span id="feat_list_message" style="display: none"></span></legend>
				<ul id="feat_list" class="userList">{$featuredList}</ul>
				{$featuredForm}	
			</fieldset>

EOD;
		}

		// get search tags form
		if ($this->famu->moderator) {
			$tagsForm = <<<EOD
			<form id="addTags" class="sideSearch" method="POST">
				<input type="text" autocomplete="off" id="tags" name="searchString" size="25" value="" class="smallinput"/>
				<input type="hidden" id="new_tags_id" name="new_tags_id" value=""/>Search Tags: 
				<input type="button" id="tagssubmit_{$this->photo->img['photo_id']}" class="tags_submit smallbuttons" value="add tag">
				<div class="auto_complete" id="tags_auto_complete" style="display: none"></div>
			</form>

EOD;
		}
		// get assigned tags
		$tagsList = $this->getTags();
		if ($tagsList != '' || $this->famu->moderator) {
			$tagsInfo = <<<EOD
			<fieldset class="photoDetail">
				<legend>Tags<span id="tags_list_message" style="display: none"></span></legend>
				<ul id="tags_list">{$tagsList}</ul>
				{$tagsForm}	
			</fieldset>

EOD;
		}

		$leftColumn = <<<EOD
		<div class="photoAuthor clearfix">
			{$avtr}<div class="detail">{$post}</div>
		</div>
		{$featuredInfo}
		{$tagsInfo}
		<fieldset class="photoDetail">
			<legend>Additional Information</legend>
			<ul>{$details}</ul>
		</fieldset>

EOD;

		// CENTER COLUMN //
		// CENTER COLUMN //
		// CENTER COLUMN //

		$centerColumn = <<<EOD
		<div class="photoFrame">
            <h2 id="caption_{$this->photo->img['photo_id']}" class="caption">{$cap}</h2>
			<ul id="photoOptions">
				<li id="photo-favorite">{$fav}</li>
				<li id="photo-flagged">{$flg}</li>
				<li id="photo-alert">{$alr}</li>
				<li id="photo-avatar">{$avt}</li>
				<li id="photo-original">{$mag}</li>
			</ul>
            <img src="{$src}" {$info[3]}/>
			<div id="description_{$this->photo->img['photo_id']}" class="description">{$des}</div>
		</div>
		{$commentForm}

EOD;
		
		// RIGHT COLUMN //
		// RIGHT COLUMN //
		// RIGHT COLUMN //

		$rightColumn = <<<EOD
		<div class="thirdColumnBox" id="photoNav">
			{$prevnext}
		</div>
EOD;
/*	
		<div class="thirdColumnBox">
			<h4>Map Location</h4>
			<div id="map"></div>
		</div>

		<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAANFxSgEaS-BpcPCXR6EqfYBTbaNkkd-V23Jyo1ZOghgMUiJitrxQKpOUNa5ZADr8XXGFQBJ95T5b4ZQ"></script>
		<script type="text/javascript" src="/js/gmapper.js"></script>
		<script>
		function mapInit() {
			application = new GMapperApplication($('map'));
			application.setCenter(37.4419, -122.1419, 13);
		}
		Behaviour.addLoadEvent(mapInit);
		</script>

EOD;
*/

		//return $this->tmpl->basicPage($output);
		return $this->tmpl->threeColumnLayout($leftColumn, $centerColumn, $rightColumn, 'page');
}

	function getGallery() {
		if (count($this->photo_array)<1) {
			return "<h3>There are no photos in this set</h3>";
		}
		foreach ($this->photo_array as $this->photo) {
			$src = $this->photo->getUrl_small();
			$fav = ($this->photo->img['favorite']) ? "A Favorite" : "Click to see this photo, comments, and details";
			$date = GTools::postTime($this->photo->img['uploaded_dt']);
			$name = ($this->photo->img['full_name']=='') ? "{$this->photo->img['first_name']} {$this->photo->img['last_name']}" : $this->photo->img['full_name'];
			$comments = ($this->photo->img['comments']==1) ? '1 comment' : "{$this->photo->img['comments']} comments";
			$desc = GTools::truncateParagraph($this->photo->img['description'], 150);
			$avtr = $this->famu->getFullAvatar($this->photo->img['avatar_id']);
			$output .= <<<EOD

			<div class="galleryFrame">
				<a href="/viewphoto/{$this->photo->img['photo_id']}/"><img src="{$src}" border="0" title="{$fav}" style="float: left"/></a>
				<div class="caption">{$this->photo->img['caption']}</div>
				<a href="/user/{$this->photo->img['user_id']}/"><img src="{$avtr}" style="float: left" border="0"/></a>
					<div class="posted">Uploaded {$date}</div>
					<div class="author">by {$name}</div>
					<div class="options">{$comments}</div>
					<div class="description">{$desc}</div>
				<br style="clear: both"/>
			</div>
				
EOD;
		}
		return $output;
	}

	function getSetLink($off_off) {
		if ($this->set_key!='' && $this->set_id != '') {
			$offset = max(0, $this->set_offset + $off_off);
			return "{$this->set_key}/{$this->set_id}/{$offset}/";
		}
	}

	function getThumbGallery() {
		if (count($this->photo_array)>0) {
			foreach ($this->photo_array as $this->photo) {
				$src = $this->photo->getUrl_square();
				$link = $this->getSetLink($i++);
				$name = ($this->photo->img['full_name']=='') ? "{$this->photo->img['first_name']} {$this->photo->img['last_name']}" : $this->photo->img['full_name'];
				if ($this->photo->img['title']) {
					$title = $this->photo->img['title'];
				} elseif (isset($this->photo->img['tip'])) {
					$title = $this->photo->img['tip']." - ";
					$title .= str_replace('"', "'", $this->photo->img['caption']);
					$title .= " by {$name}";
				} elseif (isset($this->photo->img['comments'])) {
					$title = str_replace('"', "'", $this->photo->img['caption']);
					$title .= " by {$name} - ";
					$title .= ($this->photo->img['comments']==1) ? '1 comment' : "{$this->photo->img['comments']} comments";
				}
				$output .= "<a href=\"/viewphoto/{$this->photo->img['photo_id']}/{$link}\"><img src=\"{$src}\" title=\"{$title}\" class=\"help\"/></a>";
			}
		}
		return "<div class=\"galleryFrame\">{$output}</div>";
	}

	function setCommentsUnseen($limit=12, $offset=0) {
		// set count
		GTools::logOutput(" familize:setCommentsUnseen()", true);
		$count = $this->db->do_sql("call photoUnseenCount({$this->famu->active_id}, 30)");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("call photoUnseenComment({$this->famu->active_id}, 30, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setAllFavorites($limit=12, $offset=0) {
		$count = $this->db->do_sql("call photoSetAllFavoritesCount(1)");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		$info = $this->db->do_sql("call photoSetAllFavorites(1, $limit, $offset)");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setFavorites($limit=12, $offset=0, $user_id=false) {
		// set user_id
		$user_id = ($user_id==false) ? $this->famu->active_id : $user_id;

		// set count
		$count = $this->db->do_sql("call photoSetFavoriteCount(1, {$user_id})");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("call photoSetFavorite(1, {$user_id}, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setFeatured($limit=12, $offset=0, $user_id=false) {
		// set user_id
		$user_id = ($user_id==false) ? $this->famu->active_id : $user_id;

		// set count
		$count = $this->db->do_sql("call photoSetFeaturedCount(1, {$user_id})");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("call photoSetFeatured(1, {$user_id}, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setFlagged($limit=12, $offset=0, $user_id=false) {
		// set user_id
		$user_id = ($user_id==false) ? $this->famu->active_id : $user_id;

		// set count
		$count = $this->db->do_sql("SELECT count(*) photoCount FROM photo p INNER JOIN photo_flag pf ON pf.user_id={$user_id} AND p.photo_id=pf.photo_id WHERE p.complete=1");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("SELECT p.photo_id, p.user_id, p.take_dt, p.server_id, p.photo_uid, p.caption, p.description, p.orig_size, p.orig_height, p.orig_width, p.mime, p.ext, p.private, p.complete, p.uploaded_dt, pf.updated_dt, u.first_name, u.last_name, u.avatar_id, COUNT(c.comment_id) AS comments, NOT ISNULL(pf.photo_id) AS flagged FROM photo p INNER JOIN users u ON u.user_id=p.user_id LEFT OUTER JOIN photo_comment c ON p.photo_id=c.photo_id AND c.site_id=1 INNER JOIN photo_flag pf ON pf.user_id={$user_id} AND p.photo_id=pf.photo_id WHERE p.complete=1 GROUP BY p.photo_id, p.user_id, p.take_dt, p.photo_uid, p.caption, p.description, p.orig_size, p.mime, p.ext, p.private, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id ORDER BY pf.updated_dt DESC LIMIT {$limit} OFFSET {$offset}");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setPhotoSearch($searchString, $limit=12, $offset=0) {
		$searchString = str_replace("'", "\\\'", $searchString);
		$count = $this->db->do_sql("call photoSearchCount(\"{$searchString}\")");
		$this->photo_count = $count[0]['result_count'];

		$info = $this->db->do_sql("call photoSearch(\"{$searchString}\", {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}


	/**
	 * Query a single photo for display on a page
	 */
	function setPhoto($photo_id) {
		// set view
		$last = $this->db->do_sql("CALL log_photo_view ({$this->famu->active_id}, {$photo_id}, 1)");
		$this->last = $last[0]['last_seen'];

		$info = $this->db->do_sql("CALL photoLookup({$this->famu->site_id}, {$this->famu->active_id}, {$photo_id})");
		$this->photo = new Photo($this->db, $this->famu, $info[0]);
	}

	/**
	 * set photo set for referencing an images position in a gallery or other photo set
	 */
	function setPhotoSet($key='', $id='', $offset=0) {
		$this->set_key = $key;
		$this->set_id = $id;
		$this->set_offset = $offset;
	}

	function setGroupPhotos($min_size=10, $limit=12, $offset=0) {
		$count = $this->db->do_sql("call photoSetGroupCount(1, {$min_size})");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}
		
		// set photos
		$info = $this->db->do_sql("call photoSetGroup(1, {$min_size}, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setSameAge($age_days, $range_days=false, $limit=12, $offset=0) {
		if ($range_days===false) {
			$range_days = intval(sqrt(floatval($age_days)));
		}
		$low = intval($age_days)-intval($range_days);
		$high = intval($age_days)+intval($range_days);
		$query = <<<EOD
			SELECT SQL_CALC_FOUND_ROWS p.photo_id, u.user_id, p.take_dt, p.server_id, p.photo_uid, p.caption, p.description, 
			p.orig_size, p.orig_height, p.orig_width, p.mime, p.ext, p.private, p.complete, p.uploaded_dt,
			u.full_name, u.common_name, u.avatar_id, concat('featuring ', u.full_name) title
			FROM user_name u
			INNER JOIN photo_featuring pf ON u.user_id=pf.user_id
			INNER JOIN photo p ON pf.photo_id=p.photo_id
			WHERE u.birth_date IS NOT NULL AND u.birth_year_unknown=0
				AND datediff(p.take_dt, u.birth_date) BETWEEN {$low} AND {$high}
			ORDER BY p.take_dt DESC
			LIMIT {$limit} OFFSET {$offset}
EOD;
		$query = preg_replace('/[\n\t\r ]+/', ' ', $query);
		$info = $this->db->do_sql($query);
		$count = $this->db->do_sql("SELECT FOUND_ROWS() AS row_count");
		$this->photo_count = $count[0]['row_count'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setTaggedPhotos($tag_id, $limit=12, $offset=0) {
		$count = $this->db->do_sql("call photoSetTagCount(1, {$tag_id})");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}
		
		// set photos
		$info = $this->db->do_sql("call photoSetTags(1, {$tag_id}, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setTopComments($minimum, $limit=12, $offset=0) {
		$count = $this->db->do_sql("call photoTopCommentCount($minimum)");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		$info = $this->db->do_sql("call photoTopComments($minimum, $limit, $offset)");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function setUserPhotos($limit=12, $offset=0) {
		// set count
		$count = $this->db->do_sql("call photoSetUserCount(1, {$this->famu->active_id})");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("call photoSetUser(1, {$this->famu->active_id}, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function searchPhotos($phrase, $limit=12, $offset=0) {
		// set count
		$phrase = str_replace("'", '', $phrase);
		$count = $this->db->do_sql("SELECT COUNT(p.photo_id) AS photoCount FROM photo p WHERE p.complete=1 AND MATCH(p.caption, p.description) AGAINST ('{$phrase}' IN BOOLEAN MODE)");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("SELECT p.photo_id, p.user_id, p.take_dt, p.server_id, p.photo_uid, p.caption, p.description, p.ext, p.private, p.complete, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id, COUNT(c.comment_id) AS comments FROM photo p INNER JOIN users u ON u.user_id=p.user_id LEFT OUTER JOIN photo_comment c ON p.photo_id=c.photo_id AND c.site_id=1 WHERE p.complete=1 AND MATCH(p.caption, p.description) AGAINST ('{$phrase}' IN BOOLEAN MODE) GROUP BY p.photo_id, p.user_id, p.take_dt, p.photo_uid, p.caption, p.description, p.ext, p.private, p.complete, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id LIMIT {$limit} OFFSET {$offset}");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function searchPhotoComments($phrase, $limit=12, $offset=0) {
		// set count
		$phrase = str_replace("'", '', $phrase);
		$count = $this->db->do_sql("SELECT COUNT(p.photo_id) AS photoCount FROM photo p INNER JOIN photo_comment c ON p.photo_id=c.photo_id AND c.site_id=1 WHERE p.complete=1 AND MATCH(c.comment) AGAINST ('{$phrase}' IN BOOLEAN MODE)");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("SELECT p.photo_id, p.user_id, p.take_dt, p.server_id, p.photo_uid, p.caption, p.description, p.ext, p.private, p.complete, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id, COUNT(c.comment_id) AS comments FROM photo p INNER JOIN users u ON u.user_id=p.user_id INNER JOIN photo_comment c ON p.photo_id=c.photo_id AND c.site_id=1 WHERE p.complete=1 AND MATCH(c.comment) AGAINST ('{$phrase}' IN BOOLEAN MODE) GROUP BY p.photo_id, p.user_id, p.take_dt, p.photo_uid, p.caption, p.description, p.ext, p.private, p.complete, p.uploaded_dt, u.first_name, u.last_name, u.avatar_id LIMIT {$limit} OFFSET {$offset}");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	/**
	 * Query a set of the most recent photos for display on a page
	 */
	function setRecent($limit=12, $offset=0) {
		// set count
		$count = $this->db->do_sql("SELECT count(*) photoCount FROM photo WHERE complete=1");
		$this->photo_count = $count[0]['photoCount'];

		// double check offset
		if ($offset > $this->photo_count) {
			$offset = floor($this->photo_count/$limit)*$limit;
		}

		// set photos
		$info = $this->db->do_sql("call photoSetRecent(1, {$this->famu->active_id}, {$limit}, {$offset})");
		foreach ($info as $photo) {
			$this->photo_array[] = new Photo($this->db, $this->famu, $photo);
		}
		$this->photo = $this->photo_array[0];
		return $offset;
	}

	function getPageList($count, $limit, $offset, $linkPrefix) {
		$curr = intval($offset/$limit);
		$numPages = ceil($count/$limit);

		// set first and last
		$first = max(0, $curr-7);
		$last = min($numPages, $first+15);
		if ($first > 1) {
			$dot1 = "<li><a href=\"{$linkPrefix}1/\">1</a></li>\n<li>...</li>";
		} elseif ($first == 1) {
			$dot1 = "<li><a href=\"{$linkPrefix}1/\">1</a></li>\n";
		}
		$dot2 = ($last==$numPages) ? '' : '<li>...</li>';

		// paginate
		if ($last-$first > 1) {
			for ($i=$first; $i<$last; $i++) {
				$n = $i+1;
				$pages .= ($i==$curr) ? "<li>{$n}</li>\n" : "<li><a href=\"{$linkPrefix}{$n}/\">{$n}</a></li>\n";
			}

			// prev, next links
			$prev = ($curr<=0) ? '<li>&lt;Prev</li>' : "<li><a href=\"{$linkPrefix}{$curr}/\">&lt;Prev</a></li><li>{$dot1}</li>";
			$next = ($curr>=($numPages-1)) ? '<li>Next&gt;</li>' : "<li>{$dot2}</li><li><a href=\"{$linkPrefix}".($curr+2).'">Next&gt;</a></li>';
			$linkList = "<ul class=\"pagination\">{$prev}{$pages}{$next}</ul>";
		}

		// display what's showing
		$from = $offset+1;
		$show = min($count, $offset+$limit);
		return array($linkList, "{$from}-{$show} of {$count}");
	}
}

?>
