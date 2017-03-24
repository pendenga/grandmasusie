<?php

include_once 'module.php';
include_once 'blog.php';
include_once '../helpers/formatlongtext.php';

class BlogForm extends Module {
	public $blog=false;

	function __singleton($initialize=true) {
		if ($this->blog===false) {
			$this->blog = new Blog($this->db, $this->famu, $initialize);
		}
	}
	
	function blogCreate($title) {
		$this->__singleton(false);
		$this->blog->newBlog($title);	
	}

	function blogSave($title) {
		$this->__singleton();
		$this->blog->saveBlog($title);
	}

	function blogCreateForm() {
		$this->__singleton();
		$btnLabel = ($this->blog->info['blog_name']=='') ? 'Create Blog' : 'Edit Blog Name';
		return <<<EOD
			<form method="post">
				<div width="100%"><label>Title: <input type="text" name="blog[name]" size="50" maxlength="255" value="{$this->blog->info['blog_name']}"/></label><input type="submit" class="buttons" name="Submit" value="{$btnLabel}"/></div>
			</form>
EOD;
	}

	function entryCreate($title, $content, $active) {
		$this->__singleton();
		return $this->blog->newEntry($title, $content, $active);
	}

	function entrySave($entry_id, $title, $content, $active) {
		$this->__singleton();
		return $this->blog->saveEntry($entry_id, $title, $content, $active);
	}

	function entryForm($entry_id='') {
		$this->__singleton();
		// create new blog
		if ($this->blog->info === false) {
			return $this->tmpl->formWithInstructions("Name Your Blog", $this->blogCreateForm(), "I don't know if I even want to have them called \"Blogs\". In the modern vernacular that seems to mean \"spout off\". Could we have a name that's got more of the meaning, \"Whassup?\" or \"What's Happenin' Man\" or something else.  - Grandma Susie");

		// create new entry
		} else {
			$form = <<<EOD
			<form id="write_form" name="write_form" method="post" action="">
				<div width="100%"><label>Title <input type="text" name="new[title]" size="60" value=""/></label></div>
				<textarea name="new[content]" id="markdown_example" cols="75" rows="8" class="blogAuthoring"></textarea><br/>
				<div width="50%" style="float: right">
					<label><input type="radio" name="new[publish]" value="1" checked="checked"/> Publish</label>
					<label><input type="radio" name="new[publish]" value="0" /> Draft</label></div>
				<div width="40%"><input type="submit" class="buttons" name="Submit" value="Save" /></div>
			</form>
			<script>
				markdown_toolbar = new Control.TextArea.ToolBar.Markdown('markdown_example');
				markdown_toolbar.toolbar.toolbar.id = 'markdown_toolbar';
			</script>

EOD;
			return $this->tmpl->formWithInstructions("{$this->blog->info['blog_name']} - (New Entry)", $form, "One idea might be to have some space for something like a blog or a diary so that everyone can post a BRIEF message about what is happening in their life, maybe once a week or once a month??? I'd almost like to see it arranged by date instead of arranged by family -- so that I can read it more quickly instead of paging through everyone's home page to find them all. Maybe it could be arranged by BOTH??  I'd love to hear more about what everyone is doing! - Grandma Susie");
		}
	}

	protected function blogEntryAbstract($entry, $type='blog', $trunk=250) {
		$abstract = FormatLongText::for_print(GTools::truncateParagraph($entry['content'], $trunk));
		$title = strip_tags($entry['title'],'<a><p><img>');
		$comments = ($entry['comments']==1) ? '1 comment' : "{$entry['comments']} comments";
		$posted = ($type=='blog') ? "also in <a href=\"/read/fam/{$entry['household_id']}/\">{$entry['fam_blog_name']}</a>" : "from <a href=\"/read/blog/{$entry['blog_id']}/\">{$entry['blog_name']}</a>";
		$draft = ($entry['active']==1) ? '' : "<img src=\"{$this->tmpl->icon['blog_draft_sm.png']}\" border=\"0\" title=\"This draft is not published.\" />";
		$date = GTools::postTime($entry['updated_dt']);
		$avtr = $this->famu->getFullAvatarLink($entry['avatar_id'], $entry['user_id'], "{$entry['first_name']} {$entry['last_name']}");
		$fam_entry = <<<EOD
			{$avtr}
			<h4><a href="/read/blog/{$entry['blog_id']}/{$entry['entry_id']}/">{$title}{$draft}</a></h4>
				<span class="author">by {$entry['first_name']} {$entry['last_name']}</span>
				<span class="famblog">{$posted}</span>
				<div class="posted">Posted {$date} - {$comments}</div>
			<div style="clear: both" class="description">{$abstract}</div>
EOD;
		return $fam_entry;
	}

	protected function blogEntryCompressed($entry, $type='blog', $trunk=250, $showAvatar=true, $noheader=false) {
		$date = GTools::postTime($entry['updated_dt']);
		$entry['comments'] = intval($entry['comments']);
		$title = strip_tags($entry['title'], '<a><p><img>');
		$comments = ($entry['comments']==1) ? '1 comment' : "{$entry['comments']} comments";
		$abstract = str_replace('"', "'", strip_tags(GTools::truncateParagraph(FormatLongText::for_print($entry['content']), $trunk)));
		$draft = ($entry['active']==1) ? '' : "<img src=\"{$this->tmpl->icon['blog_draft_sm.png']}\" border=\"0\" title=\"This draft is not published.\" />";
		$avtr = ($showAvatar) ? $this->famu->getFullAvatarLink($entry['avatar_id'], $entry['user_id'], "{$entry['first_name']} {$entry['last_name']}") : '';
		$titletag = ($noheader) ? 'strong' : 'h4';
		$blogname = str_replace('"', "'", $entry['blog_name']);
		$author = ($type=='blog') ? '' : "<a href=\"/read/blog/{$entry['blog_id']}/\" title=\"Read entries from '{$blogname}'\" class=\"help\">by {$entry['first_name']} {$entry['last_name']}</a>";
		$fam_entry = <<<EOD
			{$avtr}
			<{$titletag}><a href="/read/blog/{$entry['blog_id']}/{$entry['entry_id']}/" title="{$abstract}" class="help">{$title}{$draft}</a></{$titletag}>
			<div class="footnote">Posted {$date} {$author} - $comments</div>
EOD;
		return $fam_entry;
	}

	/**
	 * The most recent post from each blog, ordered with most recent first
	 */
	function latestByBlog() {
		$blogs = $this->db->do_sql("CALL blogLatestByBlog(1)");
		foreach ($blogs as $blog) {
			$blog_entry = ($blog['entry_id']=='') ? "<li><em>This blog has no entries.</em></li>" : '<li>'.$this->blogEntryCompressed($blog, 'blog').'</li>';

			// append blog name to output
			$output .= <<<EOD
			<li><div class="famblog"><a href="/read/blog/{$blog['blog_id']}/" title="View all blog entries from {$blog['blog_name']}" class="help">{$blog['blog_name']}</a>: latest entry</div>
				{$blog_entry}
			</li>
EOD;
		}
		return "<div class=\"blogFrame\"><ul>{$output}</ul></div>";
	}

	function searchBlogs($searchString) {
		$this->__singleton();
		$searchString = str_replace("'", "\\\'", $searchString);
		$entries = $this->db->do_sql("CALL blogSearch(\"$searchString\")");
		foreach ($entries as $famblog) {
			$fam_entry = '<li>'.$this->blogEntryCompressed($famblog, 'fam').'</li>';

			// append family blog name to output
			$output .= <<<EOD
			<div class="famblogFrame" style="width: 375px">
				<h3><a href="/read/fam/{$famblog['household_id']}/">{$famblog['fam_blog_name']}</a></h3>
				<ul class="blogList">{$fam_entry}<ul>
			</div>
EOD;
		}
		return $output;
	}

	/**
	 * The most recent post from each family ordered by family sequence
	 */
	function latestByFamily() {
		$this->__singleton();
		$entries = $this->db->do_sql("CALL blogLatestByFamily({$this->famu->site_id})");
		foreach ($entries as $famblog) {
			$fam_entry = ($famblog['entry_id']=='') ? "<em>This family has not written anything yet.</em>" : $this->blogEntryCompressed($famblog, 'fam');

			// append family blog name to output
			$output .= <<<EOD
			<li><div class="famblog"><a href="/read/fam/{$famblog['household_id']}/" title="View all blog entries from {$famblog['fam_blog_name']}" class="help">{$famblog['fam_blog_name']}</a></div>
				{$fam_entry}
			</li>
EOD;
		}
		return "<div class=\"blogFrame\"><ul>{$output}</ul></div>";
	}

	// all the most recent entries for a family's set of blogs
	function recentByFamily($household_id) {
		$this->__singleton();
		$entries = $this->db->do_sql("CALL blogRecentFamblog(1, {$household_id}, {$this->famu->active_id})");
		
		// loop over entries
		if (count($entries)>0) {
			foreach ($entries as $entry) {
				$fam_entry .= '<li>'.$this->blogEntryCompressed($entry, 'fam', 300).'</li>';
			}
		} else {
			$fam_entry = "<li><em>This family has not written anything yet.</em></li>";
		}

		// blog header
		$output .= <<<EOD
		<div class="famblogFrame">
			<h3>{$entries[0]['fam_blog_name']}</h3>
			<ul class="blogList">{$fam_entry}<ul>
		</div>
EOD;
		return $output;
	}

	// all the most recent entries for a single blog
	function recentByBlog($blog_id) {
		$this->__singleton();
		
		// loop over entries
		$entries = $this->db->do_sql("CALL blogRecentBlog(1, {$blog_id}, {$this->famu->active_id})");
		if (count($entries) > 0) {
			$username = "{$entries[0]['first_name']} {$entries[0]['last_name']}";
			$blogname = $entries[0]['blog_name'];
			$famblink = "<a href=\"/read/fam/{$entries[0]['household_id']}/\">{$entries[0]['fam_blog_name']}</a>";
			$avtr = $this->famu->getFullAvatarLink($entries[0]['avatar_id'], $entries[0]['user_id'], $username);
			foreach ($entries as $entry) {
				$blog_entry .= '<li>'.$this->blogEntryCompressed($entry, 'blog', 300, false).'</li>';
			}
		} else {
			$blog_entry = "<em>This blog has no posts so far.</em>";
		}

		// format blog list
		$output .= <<<EOD
		<div class="famblogFrame">
			<div class="clearfix">
				{$avtr}<h3>{$blogname}</h3>
				by {$username}
				<div class="footnote">part of {$famblink}</div>
			</div>
			<ul class="blogList">{$blog_entry}</ul>
		</div>
EOD;
		return $output;
	}

	function flaggedList() {
		$this->__singleton();
		$entries = $this->blog->getFlaggedEntries();
		
		if (count($entries)<1) {
			$output = "<h3>There are no flagged blog entries.</h3>";
		} else {
			// loop over entries
			foreach ($entries as $entry) {
				$fam_entry .= "<li>".$this->blogEntryCompressed($entry, 'fam', 300)."</li>";
			}
			$output = "<div class=\"blogFrame\" style=\"margin-left: 20px\"><ul>{$fam_entry}</ul></div>";
		}
		return $output;
	}

	function taggedList($tag_id) {
		$this->__singleton();
		$entries = $this->blog->getTaggedEntries($tag_id);
		
		if (count($entries)>1) {
			$output = "<h3>There are no tagged blog entries.</h3>";
		} else {
			// loop over entries
			foreach ($entries as $entry) {
				$fam_entry .= "<li>".$this->blogEntryCompressed($entry, 'fam', 300)."</li>";
			}
			$output = "<div class=\"blogFrame\" style=\"margin-left: 20px\"><ul>{$fam_entry}</ul></div>";
		}
		return $output;
	}

	function unreadComments() {
		$this->__singleton();
		$entries = $this->blog->getUnreadComments();
		
		if (count($entries)<1) {
			$output = "<h3>There are no unread blog comments.</h3>";
		} else {
			// loop over entries
			foreach ($entries as $entry) {
				$fam_entry .= "<li>".$this->blogEntryCompressed($entry, 'fam', 300)."</li>";
			}
			$output = "<div class=\"blogFrame\" style=\"margin-left: 20px\"><ul>{$fam_entry}</ul></div>";
		}
		return $output;
	}

	function unreadCommentsSide($limit=5) {
		$this->__singleton();
		$entries = $this->blog->getUnreadComments($limit);
		
		if (count($entries)<1) {
			return '';
		} else {
			// loop over entries
			foreach ($entries as $entry) {
				$fam_entry .= "<li>".$this->blogEntryCompressed($entry, 'fam', 300, false, true)."</li>";
			}
			return "<div class=\"blogFrame\" style=\"margin-left: 0px\"><h4>Unread Blogs</h4><ul>{$fam_entry}</ul></div>";
		}
	}

	function draftIcon() {
		return (in_array($this->blog->info['user_id'], $this->famu->switch_list)) ? ($this->blog->info['active']==1) ? "<img src=\"{$this->tmpl->icon['blog_pub.png']}\" border=\"0\" title=\"Click to un-publish this blog.\" />" : "<img src=\"{$this->tmpl->icon['blog_draft.png']}\" border=\"0\" title=\"Click to publish this blog entry\" />" : '';
	}

	function flaggedIcon() {
		$link = ($this->blog->info['flagged']) ? '<a href="#" class="active">Flagged</a>' : '<a href="#">Flag This</a>';
		return "<div id=\"flagged_{$this->blog->info['entry_id']}\" class=\"flagged\">{$link}</div>";
	}

	function getCategories($entry_id) {
		$cats = $this->blog->getCategories($entry_id);
		foreach ($cats as $cat) {
			$remover .= ($this->famu->moderator) ? " <a href=\"#\" onclick=\"new Ajax.Updater('feat_list', '/photo_server/remfeatured/{$this->photo->img['photo_id']}/{$feat['user_id']}'); return false;\" class=\"remove\">(remove)</a>" : '';
			$output .= "<li>{$cat['category']}{$remover}</li>";
		}
		return $output;
	}

	function addTags($tag_text) {
		return $this->blog->assignTag($tag_text);
	}

	function removeTags($tag_id) {
		return $this->blog->unassignTag($tag_id);
	}

	function getTags() {
		if ($this->blog != false) {
			$tags = $this->blog->getTagsAssigned();
			foreach ($tags as $tag) {
				$linker = "<a href=\"/tags/{$tag['tag']}\">{$tag['tag']}</a>";
				$remover = ($this->famu->moderator) ? " <a href=\"#\" onclick=\"removeTags({$this->blog->info['entry_id']}, {$tag['tag_id']})\" class=\"remove\">(remove)</a>" : '';
				$tagsStr .= "<li id=\"tags_list_{$tag['tag_id']}\">{$linker}{$remover}</li>";
			}
			return $tagsStr;
		}
	}

	function individualBlogEntry($entry_id, $type='blog') {
		$this->__singleton();
		$this->blog->getBlogEntry($entry_id);
		if ($this->blog->info['entry_id'] == '') {
			return '<div class="photoFrame"><h3>This entry is no longer active</h3></div>';
		}

		// different title for blog or famblog
		$titleLink = ($type=='blog') ? "<a href=\"/read/blog/{$this->blog->info['blog_id']}/\">{$this->blog->info['blog_name']}</a>" : "<a href=\"/read/fam/{$this->blog->info['household_id']}/\">{$this->blog->info['fam_blog_name']}</a>";
		$viewLbl = ($this->blog->info['viewCount']==1) ? "user" : "users";
		
		// get all comments together
		include_once '../lib/commentForm.php';
		$como = new CommentForm($this->db, $this->famu, $this->tmpl);
		$como->setBlogComments($this->blog->info['entry_id'], $this->blog->last);
		$commentForm = $como->getPage($this->blog->info['entry_id']);

		// content
		$content = FormatLongText::for_print($this->blog->info['content']);
		$date = GTools::postTime($this->blog->info['updated_dt']);
		$auth = "{$this->blog->info['first_name']} {$this->blog->info['last_name']}";
		$avtr = $this->famu->getFullAvatarLink($this->blog->info['avatar_id'], $this->blog->info['user_id'], $auth, "by {$auth}");
		$draf = $this->draftIcon();
		$flag = $this->flaggedIcon();


		// LEFT COLUMN //
		// LEFT COLUMN //
		// LEFT COLUMN //
		// get search tags form
		if ($this->famu->moderator) {
			$tagsForm = <<<EOD
			<form id="addTags" class="sideSearch" method="POST">
				<input type="text" autocomplete="off" id="tags" name="searchString" size="25" value="" class="smallinput"/>
				<input type="hidden" id="new_tags_id" name="new_tags_id" value=""/>Search Tags: 
				<input type="button" id="tagssubmit_{$this->blog->info['entry_id']}" class="tags_submit smallbuttons" value="add tag">
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
		$author = <<<EOD
		<div class="photoAuthor clearfix">
			<h4>{$titleLink}</h4>
			{$avtr}
			<div class="detail">Posted {$date}</div>
			<div class="detail">Viewed by {$this->blog->info['viewCount']} other {$viewLbl}</div>
		</div>
		{$tagsInfo}

EOD;
		
		// CENTER COLUMN //
		// CENTER COLUMN //
		// CENTER COLUMN //
		$output = <<<EOD

		<div class="photoFrame">
            <h2 class="title" id="title_{$this->blog->info['entry_id']}">{$this->blog->info['title']}</h2>
			<ul id="photoOptions">
				<li id="photo-draft">{$draf}</li>
				<li id="photo-flagged">{$flag}</li>
			</ul>
			<div class="clearfix" id="content_{$this->blog->info['entry_id']}">
				<div onclick="editContent(this)" class="blogContent">{$content}</div>
			</div>
		</div>
		{$commentForm}
	
EOD;

		if (in_array($this->blog->info['user_id'], $this->famu->switch_list)) {
			$output .= <<<EOD
			<script>
			function editContent(el) {
				aId = el.parentNode.id.split('_');
				new Ajax.Updater(el.parentNode.id, '/write_server/content/'+aId[1],{
					onComplete: function(transport, json) {
						$(json.edit_id).focus();
						markdown_toolbar = new Control.TextArea.ToolBar.Markdown(json.edit_id);
						markdown_toolbar.toolbar.toolbar.id = 'markdown_toolbar';
					}
				});
			}
			var ajaxOptions;
			var blogRules = {
				'.draft' : function(el) {
					el.onclick = function(){
						aId = el.id.split('_');
						new Ajax.Updater(el.id, '/write_server/draft/'+aId[1]);
					}
				},
				'.flagged' : function(el) {
					el.onclick = function(){
						aId = el.id.split('_');
						new Ajax.Updater(el.id, '/write_server/flagged/'+aId[1], { onSuccess: check_unseen });
					}
				},
				'.title' : function(el) {
					el.onclick = function(){
						aId = el.id.split('_');
						new Ajax.Updater(el.id, '/write_server/title/'+aId[1],{
							onComplete: function(transport, json) {
								$(json.edit_id).focus();
							}
						});
					},
					el.onmouseover = function(){
						el.className = "title editable";
					},
					el.onmouseout = function(){
						el.className = "title";
					}
				},
				'.content' : function(el) {
					el.onmouseover = function(){
						el.className = "content editable";
					}
					el.onmouseout = function(){
						el.className = "content";
					}
				}
			};
			Behaviour.register(blogRules);
			</script>
EOD;
		} else {
			$output .= <<<EOD
			<script>
			function editContent(el) {}
			var blogRules = {
				'.flagged' : function(el) {
					el.onclick = function(){
						aId = el.id.split('_');
						new Ajax.Updater(el.id, '/write_server/flagged/'+aId[1], { onSuccess: check_unseen });
					}
				}
			};
			Behaviour.register(blogRules);
			</script>
EOD;
		}

		// Moderator Functions for setting tags //
		if ($this->famu->moderator) {
			$output .= <<<EOD
			<script>

			var moderatorRules = {
				'.tags_submit' : function(el) {
					el.onclick = function() {
						aId = el.id.split('_');
						new Ajax.Updater('tags_list', '/write_server/addtags/'+aId[1]+'/'+$('tags').value+'/'+$('new_tags_id').value+'/', { 
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

			function removeTags(photo_id, tags_id) {
				new Effect.Squish('tags_list_'+tags_id);
				new Ajax.Request('/write_server/remtags/'+photo_id+'/'+tags_id+'/', {
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
				new Ajax.Autocompleter('tags', 'tags_auto_complete', '/search_server/tags/', {
					onSuccess: function() { $('new_tags_id').value = ''; }
				});
			}
			Behaviour.addLoadEvent(photoInit);
			</script>

EOD;
		}
		$this->tmpl->setAuthorBlock($author);
		return $output;
	}

	function titleEdit($edit_id) {
		return <<<EOD
		<form id="title_edit_form"><input type="text" id="{$edit_id}" name="title" value="{$this->blog->info['title']}" size="70" maxlength="55" onblur="new Ajax.Updater('title_{$this->blog->info['entry_id']}', '/write_server/title/{$this->blog->info['entry_id']}/', {method: 'post', parameters: $('title_edit_form').serialize(true)});"/></form>
EOD;
	}

	function contentEdit($edit_id) {
		return <<<EOD
		<br style="clear: both"/>
		<form id="content_edit_form">
		<table border="0">
		<tr><td><textarea name="content" id="{$edit_id}" cols="60" rows="20" class="blogAuthoring">{$this->blog->info['content']}</textarea></td></tr>
		<tr><td align="right">
			<input type="button" class="buttons" value="Cancel" onclick="new Ajax.Updater('content_{$this->blog->info['entry_id']}', '/write_server/resetcontent/{$this->blog->info['entry_id']}/');"/> or <input type="button" class="buttons" value="Save Changes" onclick="new Ajax.Updater('content_{$this->blog->info['entry_id']}', '/write_server/content/{$this->blog->info['entry_id']}/', {	method: 'post', parameters: $('content_edit_form').serialize(true)});"/>
		</td></tr>
		</table>
		</form>
EOD;

	}
}

?>