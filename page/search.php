<?php
$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/familize.php';
include_once '../lib/searchForm.php';
include_once '../lib/photoForm.php';
include_once '../lib/template.php';

$db = new DBObject();
$famu = new Familize($db);
$famu->loginCheck();
$tmpl = new $famu->template($famu);
$srch = new SearchForm($db, $famu, $tmpl);
// capture post'ed search
if ($_REQUEST['searchString']) {
	$searchString = $_REQUEST['searchString'];

// get url variables
} else {
	list($searchType, $searchString, $offset) = $_REQUEST['url'];
	$offset = ($offset=='')  ? 0 : max(0, $offset-1);
}
$resultLimit = 16;

// load page
$tmpl->title = "Search Results";
$tmpl->pageHeader();

switch ($searchType) {
case 'blogs':
	include_once '../lib/blogForm.php';
	$blog = new BlogForm($db, $famu, $tmpl);
	$form = $blog->searchBlogs($searchString);
	break;

case 'photo':
	// search photo captions and descriptions
	$limit = 500;
	$phto = new PhotoForm($db, $famu, $tmpl);
	$offs = $phto->searchPhotos($searchString, $limit, 0);
	$form = $phto->getThumbGallery(8);
	break;

case 'photo_comment':
	// search photo captions and descriptions
	$limit = 500;
	$phto = new PhotoForm($db, $famu, $tmpl);
	$offs = $phto->searchPhotoComments($searchString, $limit, 0);
	$form = $phto->getThumbGallery(8);
	break;

default:
	$users .= $srch->searchUsersForAvatar($searchString);
	if ($users != false) {
		$rows .= "<tr><th valign=\"top\">User Profiles</th><td>{$users}</td></tr>";
	}

	// search photo captions and descriptions
	$phto = new PhotoForm($db, $famu, $tmpl);
	$offset = $phto->searchPhotos($searchString, $resultLimit, 0);
	if ($phto->photo_count > 0) {
		$photos = $phto->getThumbGallery(8);
		$more = ($phto->photo_count > $resultLimit) ? "Showing {$resultLimit} of {$phto->photo_count} results in photos, <a href=\"/search/photo/{$searchString}/\">see all...</a>" : '';
		$rows .= <<<EOD
		<tr><td valign="top" width="150"><strong>Photos</strong><div class="detail">{$more}</div></th>
			<td>{$photos}</td></tr>
EOD;
	}

	// search photo comments
	$pht2 = new PhotoForm($db, $famu, $tmpl);
	$offset = $pht2->searchPhotoComments($searchString, $resultLimit, 0);
	if ($pht2->photo_count > 0) {
		$photos = $pht2->getThumbGallery(8);
		$more = ($phto->photo_count > $resultLimit) ? "Showing {$resultLimit} of {$pht2->photo_count} results in photos, <a href=\"/search/photo_comment/{$searchString}/\">see all...</a>" : '';
		$rows .= <<<EOD
		<tr><td valign="top" width="150"><strong>Photo Comments</strong><div class="detail">{$more}</div></th>
			<td>{$photos}</td></tr>
EOD;
	}

	// search blog entries
	$ent_cnt = $srch->countBlogEntries($searchString);
	if ($ent_cnt > 0) {
		$entries = $srch->searchBlogEntries($searchString, $resultLimit, 0);
		$more = ($ent_cnt > $resultLimit) ? "Showing {$resultLimit} of {$ent_cnt} results in blog entries, see more..." : '';
		$rows .= <<<EOD
		<tr><td valign="top" width="150"><strong>Blog Entries</strong><div class="detail">{$more}</div></th>
			<td>{$entries}</td></tr>
EOD;
	}

	// search blog entries
	$ent_cnt = $srch->countBlogComments($searchString);
	if ($ent_cnt > 0) {
		$entries = $srch->searchBlogComments($searchString, $resultLimit, 0);
		$more = ($ent_cnt > $resultLimit) ? "Showing {$resultLimit} of {$ent_cnt} results in blog comments, see more..." : '';
		$rows .= <<<EOD
		<tr><td valign="top" width="150"><strong>Blog Comments</strong><div class="detail">{$more}</div></th>
			<td>{$entries}</td></tr>
EOD;
	}

	$form = "<table width=\"100%\">{$rows}</table>";
}

print $tmpl->threeColumnLayout('', $tmpl->titledPage("Search Results for '".htmlentities($searchString)."'", $form), '', 'comic');
$tmpl->pageFooter();
//print_r($_POST);

/*
print "<pre>";
print_r($famu);
print "</pre>";
*/
?>