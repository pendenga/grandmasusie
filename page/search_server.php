<?php

$microtimer_start = microtime(true);
session_start();

include_once '../lib/dbobject.php';
include_once '../lib/familize.php';
include_once '../lib/searchForm.php';
include_once '../lib/template.php';
include_once '../lib/extlib/Services_JSON.php';

$db = new DBObject();
$famu = new Familize($db, false);
$tmpl = new $famu->template($famu);
$srch = new SearchForm($db, $famu, $tmpl);
$json = new Services_JSON();

list($searchType, $postId) = $_REQUEST['url'];
GTools::logOutput(" type:$searchType, id:$postId");

if (isset($_REQUEST['searchString'])) {
	$searchString = $_REQUEST['searchString'];
}


try {
	switch ($searchType) {
	case 'profile':
	case 'users':
		$ajaxResponse .= $srch->searchUsersForList(trim($searchString), true);
		break;
	case 'tags':
		$ajaxResponse .= $srch->searchTagsForList(trim($searchString), true);
		break;
	default:
		$ajaxResponse = 'unknown action';
	}

	//print_r($famu);

} catch (Database_QueryException $e) {
	mail('pendenga@gmail.com', "Database Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
} catch (Exception $e) {
	mail('pendenga@gmail.com', "Unknown Error ({$_SERVER['SCRIPT_URI']}): {$reg->result[0]['site_name']}", $e->getMessage());
}

header('X-JSON: ('.$json->encode(array(
	'timer2' => sprintf("%0.3f",microtime(true)-$microtimer_start), 
	'searchSequence' => $searchSequence)
	).')');
print $ajaxResponse;

?>