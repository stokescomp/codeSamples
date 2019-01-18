<?php

/*
	This file was made my Michael Stokes Oct 2017.
	This makes a selenium IDE script that firefox can use to get the creation date from TestLink website.
	gets the internal id numbers from TestLink using the guids from EnterpriseTester so you can get only the numbers that match the numbers that are in ET.
*/
$html_head = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head profile="http://selenium-ide.openqa.org/profiles/test-case">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="selenium.base" href="http://10.118.208.101/testlink/lib/testcases/" />
<title>seleniumtestLink</title>
</head>
<body>
<table cellpadding="1" cellspacing="1" border="1">
<thead>
<tr><td rowspan="1" colspan="3">seleniumtestLink</td></tr>
</thead><tbody>';

$html_foot = '
</tbody></table>
</body>
</html>';

//make a list of the guid and numbers (internalid)
//this file looks like this: It is the guid and number from Enterprise Tester
// {bda25e01-4a81-460d-bb56-a1ee01115048}	1229
// {bf68fb4f-7caa-4ea0-a3d1-a1ee0111504c}	1120
$id_list = file_get_contents("PROJECT guid to id.txt");
$id_list = explode("\r\n",$id_list);
$id_list_array = array();
foreach($id_list as $each_line){
	$column = explode("\t",$each_line);
	$id_list_array[$column[1]] = $column[0];
}

// $path = 'http://10.123.34.12/testlink/archiveData.php?edit=testcase&id=';
$xml_path = 'PROJECT.xml';
$xml = new SimpleXMLElement(file_get_contents($xml_path));
$xml = $xml->xpath('//testcase');

$array_internal_id = array();
//make the selenium file
file_put_contents('selenium_testcase.html',$html_head);
	
foreach($xml as $countTestCases=>$each){
	//if($countTestCases <= 5001)continue;
	$externalid = $each->externalid;
	if(isset($id_list_array[$externalid+0])) {
		$id = $id_list_array[$externalid+0];
	}
	$internalid = $each['internalid'];
// echo $internalid;
// 	exit;
	file_put_contents('PROJECT_internal_ids.txt', $internalid."\n", FILE_APPEND);
	echo $internalid."<br />";
	
	$html_part = '<tr>
	<td>open</td>
	<td>/testlink/lib/testcases/archiveData.php?edit=testcase&amp;id='.$internalid.'</td>
	<td></td>
</tr>
<tr>
	<td>storeText</td>
	<td>css=tr.time_stamp_creation td:eq(0)</td>
	<td>creation</td>
</tr>
<tr>
	<td>storeElementPresent</td>
	<td>css=tr.time_stamp_creation td:eq(1)</td>
	<td>modified_present</td>
</tr>
<tr>
	<td>gotoIf</td>
	<td>${modified_present} == false</td>
	<td>continue'.$countTestCases.'</td>
</tr>
<tr>
	<td>storeText</td>
	<td>css=tr.time_stamp_creation td:eq(1)</td>
	<td>modified</td>
</tr>
<tr>
	<td>echo</td>
	<td>,'.$id.','.$internalid.',${creation},${modified}</td>
	<td></td>
</tr>
<tr>
	<td>gotolabel</td>
	<td>end'.$countTestCases.'</td>
	<td></td>
</tr>
<tr>
	<td>label</td>
	<td>continue'.$countTestCases.'</td>
	<td></td>
</tr>
<tr>
	<td>storeText</td>
	<td>css=tr.time_stamp_creation td:eq(0)</td>
	<td>creation</td>
</tr>
<tr>
	<td>echo</td>
	<td>,'.$id.','.$internalid.',${creation},</td>
	<td></td>
</tr>
<tr>
	<td>label</td>
	<td>end'.$countTestCases.'</td>
	<td></td>
</tr>
';
	file_put_contents('selenium_testcase.html', $html_part."\n", FILE_APPEND);
	// if($countTestCases > 10) break;
}

file_put_contents('selenium_testcase.html', $html_foot."\n", FILE_APPEND);

?>