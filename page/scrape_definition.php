<?php
include '../lib/dbobject.php';
$db = new DBObject();

print <<<EOD
<form method="POST">
<table>
<tr><td>URL:</td>
	<td><input type="text" name="uri" value="{$_POST['uri']}"/></td></tr>
<tr><td>Num:</td>
	<td><input type="text" name="num" value="{$_POST['num']}"/></td></tr>
<tr><td colspan="2"><input type="submit" value="scrape"></td></tr>
</table>
</form>
EOD;

if ($_POST['uri']) {
	print_r($_POST);
	print "<hr/>";

	$file = GTools::curl_get_file($_POST['uri']);
	$file = preg_replace('/\n/', '', $file);
	$file = preg_replace('/\r/', '', $file);
	$file = preg_replace('/ +/', ' ', $file);
	preg_match('/def_word">([^<]+)<\/td>/', $file, $ud_word);
	preg_match('/def_p">(.+)<\/div>/', $file, $ud_part);
	//<p>(.+)<\/p> <p style=.font-style: italic.>(.+)<\/p>/', $file, $ud_defn);
	
	print "Word: {$ud_word[1]}<hr/>\n";
	print "PART: {$ud_part[1]}<hr/>\n";
	//print "Defn: {$ud_defn[1]}<hr/>\n";
	//print "Use: {$ud_defn[2]}<hr/>\n";
	//print_r($ud_defn);

	print $file;
	
	print "<hr/>\n";
}

print '<pre style="font-size: .7em">';
print_r($db->do_sql("select * from word"));
print '</pre>';
?>