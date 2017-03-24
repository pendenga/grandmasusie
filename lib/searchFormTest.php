<html>
<head>
<script type="text/javascript" src="/js/prototype.js"> </script>
<script type="text/javascript" src="/js/behaviour.js"> </script>
<script type="text/javascript" src="/js/scriptaculous.js"> </script>
<link href="/tmpl/green/green.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php


include_once '../lib/dbobject.php';
include_once '../lib/familize.php';
include_once '../lib/searchForm.php';
include_once '../lib/template.php';
include_once '../lib/extlib/Services_JSON.php';

$db = new DBObject();
$famu = new Familize($db);
$tmpl = new $famu->template($famu);
$srch = new SearchForm($db, $famu, $tmpl);
$json = new Services_JSON();

//header('content-type: text/plain');

print_r($srch->searchUsersForList('grant'));



?>
<input autocomplete="off" id="user" name="searchString" size="30" type="text" value="" />
<div class="auto_complete" id="user_auto_complete"></div>
<script type="text/javascript">
new Ajax.Autocompleter('user', 'user_auto_complete', '/search_server/users/', {})
</script>
</body>
</html>