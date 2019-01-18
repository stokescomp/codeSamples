<?php
/*
	Testlink importer
	This script was made by Michael Stokes in the missionary department June 2013.
	contact me for questions: stokescomp@gmail.com or mstokes@ldschurch.org
	This script needs an xml from testLink and a csv with 2 tab delimited columns of id and number from Enterprise Tester.
	It makes the csv file to import back into Enterprise Tester. 
	It puts the id and number columns from the csv into an array and then loops through the xml from 
	testLink and only adds the testcases that are found by number in the csv file.

	//The csv file from Enterprise Tester has lines like this: 
	first column is the guid in Enterprise tester, the second column is the test number
	{002d1b36-6568-471e-b119-a14300c95cf0}	900
	{6a3abf8e-30b3-4543-9d13-a14300c95cf0}	901

	The TestLink file looks like this:
	<testcase internalid="3874" name="The test description">
		<node_order><![CDATA[100]]></node_order>
		<externalid><![CDATA[1124]]></externalid>
		<summary><![CDATA[<p>The summary of the test</p>]]></summary>
		<steps><![CDATA[<p>1. Login to website.<br />
	2. Click on admin menu.<br />
	3. Click person.<br />
	4. Make sure the person's name exists at the top of the page.</p>]]></steps>
		<expectedresults><![CDATA[<p>The person's name is at the top of the page.</p>]]></expectedresults>
	<keywords>	
		<keyword name="L3"><notes><![CDATA[Negative tests, performance, scalability, integration, security, link validation.]]></notes></keyword>
		<keyword name="MANUAL"><notes><![CDATA[This test case should not or cannot be automated.]]></notes></keyword>
		<keyword name="CAT:PersonCategory"><notes><![CDATA[]]></notes></keyword>
		<keyword name="MINUTES:30"><notes><![CDATA[]]></notes>
		</keyword>
	</keywords>
	<custom_fields>	
		<custom_field>
			<name><![CDATA[Time to Test (in minutes)]]></name>
			<value><![CDATA[]]></value>
		</custom_field>
	</custom_fields>
	</testcase>
*/
$todays_date = date('m-d-Y');

$projectname = "PROJECT";
$xml_filename = "PROJECT_all_testsuites";
$csv_filename = "PROJECT_cleanIds_numbers";

$filename = "PHPScript{$projectname}-{$todays_date}";
$save_path = "save/".$filename.".csv";
$xml = new SimpleXMLElement(file_get_contents($xml_filename.'.xml'));
$id_list = file_get_contents("{$csv_filename}.csv");
$id_list = explode("\r\n",$id_list);
$id_list_array = array();
foreach($id_list as $each_line){
	$column = explode("\t",$each_line);
	$id_list_array[$column[1]] = $column[0];
}

//process the whole xml to get all the folder structures.
//make the array like this.
//external id is the key
$testcase['1234']['project_scructure'] = "folder level 1|folder level 2|folder level 3|folder level 4";
$testcase['2345']['project_scructure'] = "folder level 1|folder level 2|folder level 3";
$testcase['3456']['project_scructure'] = "folder level 1|folder level 2";
$testcase['4567']['project_scructure'] = "folder level 1";

$xml = $xml->xpath('//testcase');
$pass = false;
$error_over_1024_chars_array = array();
$error_no_newline_array = array();
$delimiters = array("<p>", "<tr>");
$table_delimiters = array('<table>','</table>','<br />');
$list_delimiters = array('<p>');
echo "<h1>There are ".count($xml)." testcases</h1>";

//write the new file
file_put_contents($save_path, '"Id","Name","Description","Step Number","Step Description","Step Expected Result"'."\r\n");

// echo "<pre>",print_r($each,1),"</pre>";
foreach($xml as $countTestCases=>$each){
	$internalid = $each['internalid'];
echo $internalid."<br />";
	continue;

	$externalid = $each->externalid;
	// use this to ignore all test cases that are not this id.
	// if($externalid != 2581) continue; //971//1308 //2581
	$name = $each['name'];
	$summary = $each->summary;
	if(isset($each->expectedresults)) {
		$expected_results = $each->expectedresults;
		if($expected_results == "Enter Result")
			$expected_results = "-";
	} else
		$expected_results = '';
	$steps = $each->steps;
	// if(strpos($steps,'<tr')) $table = true; else $table = false;
	// if(strpos($summary,'<tr')) $table = true; else $table = false;
	if(strpos($expected_results,'<tr')) $table = true; else $table = false;
	
	$steps = strip_tags($steps,'<p><li><ol><ul><br><tr><td><th><table>');
	
	//clean the tags and other unwanted text
	$steps = cleanTags($steps);
	$name = str_replace("&nbsp;"," ",$name);
	$summary = cleanTags($summary);
	$summary = cleanTables($summary);
	$summary = str_replace("&nbsp;"," ",$summary);
	$summary = str_replace("<br />","\n",$summary);
	$summary = strip_tags($summary);
	$expected_results = cleanTags($expected_results);
	$expected_results = cleanTables($expected_results);
	$expected_results = str_replace("&nbsp;"," ",$expected_results);
	$expected_results = str_replace("<br />","\n",$expected_results);
	$expected_results = strip_tags($expected_results);
	
	//uncomment these two lines to see which testcases use SQL and fix them if they are on more than one line by removing the <br /> at the end of each line
	/*if(strpos($steps,'SELECT') === false)
		continue;
	*/

	if($table){
		//nested tables
		// echo "number of tables: ".substr_count($steps, "<table>").": $externalid<br />";
		$steps = cleanTables($steps);
		$steps = explode($table_delimiters[0], str_replace($table_delimiters, $table_delimiters[0], $steps) );
	} else if(strpos($steps, '<ol>') !== false || strpos($steps, '<ul>') !== false){
		// echo $steps;
		//split by the list items
		$steps = explode($list_delimiters[0], str_replace($list_delimiters, $list_delimiters[0], $steps) );
	} else {
		// continue;
		// This splits the steps into peices by any of the delimiters. It replaces all the delimiters with the first delimiter before doing the explode function.
		$steps = explode($delimiters[0], str_replace($delimiters, $delimiters[0], $steps) );
	}
	// uncomment to see number of tables in steps.
	// continue;

	//use this to see what steps are blank
	// if(count($steps) > 1){
	// 	continue;
	// } else {
	// 	if(strlen(trim($steps[0])) > 0) continue;

	// 	$error_no_newline_array[] = $externalid+0;
	// }

	// echo "<pre>",print_r($each),"</pre>";
	if($table)
		echo "<h2>TABLE_FOUND</h2>";
	if(strlen($expected_results) > 1024){
		//save a list of the testcases that are too long 
		$error_over_1024_chars_array[] = $externalid+0;
		echo "<h2>EXPECTED_RESULT_EXCEEDS_1024_CHARS</h2>";
	}
	echo "<b>Name:</b> ".$name."<br />";
	echo "<b>External id:</b> ".$externalid." count steps:".count($steps)."<br />";
	echo "<b>ID:</b> ";
	//I only will write to the csv file if the externalid in the xml is in the csv file that 
	//we exported from ET using the existing testcases. This way we can only update the testcases 
	//that we have not updated our selves.
	if(isset($id_list_array[$externalid+0])) {
		$id = $id_list_array[$externalid+0];
		echo $id_list_array[$externalid+0]."<br />";
		$pass = true;
	} else {
		echo "<b>NO_ID_SKIP_IT</b><br />";
		$pass = false;
	}
	echo "<b>Summary:</b> <pre>".$summary."</pre><br />";
	echo "<b>Expected Results:</b><pre> ".$expected_results."</pre><br />";
	
	$step_num = 1;
	echo "<pre>";
	foreach($steps as $key=>&$eachstep){
		$eachstep = str_replace("<br />","\n",$eachstep);
		$eachstep = str_replace("<p>","\n",$eachstep);
		$eachstep = str_replace("</p>","",$eachstep);
		$eachstep = str_replace("</li>","",$eachstep);
		$eachstep = str_replace("<ul>","",$eachstep);
		$eachstep = str_replace("</ul>","",$eachstep);
		$eachstep = str_replace("<ol>","",$eachstep);
		$eachstep = str_replace("</ol>","",$eachstep);
		$eachstep = str_replace("<th>","",$eachstep);
		$eachstep = str_replace("</tr>","",$eachstep);
		$eachstep = str_replace("<td>","",$eachstep);
		$eachstep = str_replace("<table>","",$eachstep);
		$eachstep = str_replace("</table>","",$eachstep);
		$eachstep = str_replace("&nbsp;"," ",$eachstep);

		$eachstep = preg_replace("/\n(\s)+/", "\n", $eachstep);

		//fix the expected results so there it is a dash for all steps except the last step
		if((count($steps)-1) == $key){
			$temp_expected_results = $expected_results;
		} else
			$temp_expected_results = '-';

		$eachstep = trim(strip_tags($eachstep));
		$current_position = 0;
		$count = 0;
		$no_newline = false;
		if(strlen($eachstep) > 0){
			if(strlen($eachstep) > 1024){
				echo "<h2>ERROR:1024_CHARACTERS";
				if($table) 
					echo "_TABLE";
				echo "</h2>";
				while(strlen($eachstep) > 1024 && $no_newline == false){
					$count++;
					if($count > 20) exit;
					//fix the expected results so there it is a dash for all steps except the last step
					if((count($steps)-1) == $key){
						$temp_expected_results = $expected_results;
					} else
						$temp_expected_results = '-';
					
					//find the previous new line before 1024 characters
					//set the newline to the place of the last newline before 1024 characters
					$newline_pos = strripos($eachstep, "\n", -(strlen($eachstep)-1024));
					if($newline_pos === false){
						$no_newline = true;
						echo htmlspecialchars($eachstep);
					}
					//echo "total length:".strlen($eachstep)." newline: $newline_pos - currentpos:".$current_position."<br />";
					
					//This is the part from the current position to the last newline before 1024 characters
					$current_step_first_part = substr($eachstep, 0, $newline_pos);
					//save the current step from the current position to the end of the step
					$eachstep = substr($eachstep, $newline_pos);
					echo "<b>PART_OF_1024_STEP{$step_num}:</b>$current_step_first_part<br />\n";
					$current_position = $newline_pos;
					$step_num++;
					if($pass){
				 		file_put_contents($save_path, '"'.$id.'","'.cleanQuotes($name).'","'.cleanQuotes($summary).'","'.($step_num-1).'","'.cleanQuotes($current_step_first_part).'","'.cleanQuotes($temp_expected_results).'"'."\n", FILE_APPEND);
					}
				}
			}
			if($no_newline){
				echo('<h2>NO_NEWLINE</h2>Fix this error and retry.');
				$error_no_newline_array[] = $externalid+0;
				continue;
			}
			
			//for fixing tables to remove the last pipe
			if(substr($eachstep, strlen($eachstep)-1) == '|') 
				$eachstep = substr($eachstep, 0, strlen($eachstep)-1);
			echo "<b>STEP{$step_num}:</b>$eachstep<br />\n";
			//Length:".strlen($eachstep);

			$step_num++;

			//save the current step
			if($pass)
				file_put_contents($save_path, '"'.$id.'","'.cleanQuotes($name).'","'.cleanQuotes($summary).'","'.($step_num-1).'","'.cleanQuotes($eachstep).'","'.cleanQuotes($temp_expected_results).'"'."\n", FILE_APPEND);
			// echo "total step count:".count($steps)." current key: $key<br />";
			// echo '"'.$id.'","'.$name.'","'.$summary.'","'.($step_num-1).'","'.$eachstep.'","'.$temp_expected_results.'"'."\r\n<br />";
		}
	}
	echo "</pre><br />";
	// if($countTestCases > 2000) break;
}

echo "<h1>The number of extended steps that are greater than 1024 chars is: ".count($error_over_1024_chars_array)."<br />To find the steps that were greater than 1024 characters look for these errors:</h1>";


echo "<h2>ERROR:1024_CHARACTERS</h2>";
echo "<h2>EXPECTED_RESULT_EXCEEDS_1024_CHARS</h2>";
echo "<h2>NO_NEWLINE</h2>";

echo "<h1>The number of steps that don't have a new line is: ".count($error_no_newline_array)."<br />To see these errors look for these ids:</h1>";

sort($error_over_1024_chars_array);
sort($error_no_newline_array);

echo "List of testcases over 1024 characters:<br />";
foreach($error_over_1024_chars_array as $each){
	echo $each."<br />";
}
echo "List of testcases that have more than no newline and needs to be fixed in the xml file:<br />";
foreach($error_no_newline_array as $each){
	echo $each."<br />";
}

function cleanTables($input){
	//replace the td with |
	//replace <tr> with <tr>Row # - 
	$input = str_replace("<table>", "<table></tr>Table:\n", $input);
	$input = str_replace("<tr>", "Row# - ", $input);
	$input = str_replace("</tr>", "\n", $input);
	$input = str_replace(array("</td>","</th>"), " | ", $input);
	$input = str_replace("<th>", "\n", $input);
	$input = str_replace("<td>", "\n", $input);
	//remove extra spaces and then remove the last |
	$input = preg_replace("/\s+/", " ", $input);
	$input = str_replace(" | </tbody>", "</tbody>", $input);
	$input = str_replace("| Row#", "\nRow#", $input);

	//replace each Row# with it's number.
	$input = explode("Row#", $input);
	foreach($input as $key=>&$each){
		if($key == 0) continue;
		$each = "Row $key".$each;
	}
	return implode($input);
}

function cleanTags($input){
	//remove attributes from some html tags
	$input = preg_replace("/<p[^>]+>/i", "<p> ", $input);
	$input = preg_replace("/<li[^>]+>/i", "<li> ", $input);
	$input = preg_replace("/<ol[^>]+>/i", "<ol> ", $input);
	$input = preg_replace("/<ul[^>]+>/i", "<ul> ", $input);
	$input = preg_replace("/<table[^>]+>/i", "<table> ", $input);
	$input = preg_replace("/<th[^>]+>/i", "<th> ", $input);
	$input = preg_replace("/<tr[^>]+>/i", "<tr> ", $input);
	$input = preg_replace( '/&lt;select (.+?)&gt;/i', '', $input);
	//get rid of <input */>
	$input = preg_replace("/&lt;input[^>]+&gt;/i", " ", $input);
	//textarea's should be replaced with some text like: Find the textarea with this name: TheNameAttribute
	$input = preg_replace( '#&lt;textarea name=&quot;([^;]+)&quot;(.+?)&gt;&lt;/textarea&gt;#', 
	    'Find the textarea with this name:$1', 
	    $input
	);
	$input = htmlspecialchars_decode($input);
	$input = cleanSpecialcharacters($input);
	return $input;
}
function cleanQuotes($input){
	return str_replace('"', '""', $input);
}

function cleanSpecialcharacters($input){
	//list of characters to change
	$char_list = array(
		'&eacute;'=>'é',
		'&reg;'=>'®',
		'&trade;'=>'™',
		'&copy;'=>'©',
		'&#95;'=>'_',
		'&raquo;'=>'»',
		'&bull;'=>'•',
		'&rsquo;'=>'"',
		'&AElig;'=>"Æ",
		'&ordm;'=>"º",
		'&rdquo;'=>'"',
		'&ldquo;'=>'"',
		'&yuml;'=>'ÿ',
		'&auml;'=>'ä',
		'&Egrave;'=>'È',
		'&lsquo;'=>'‘',
		'&agrave;'=>'à',
		'&otilde;'=>'õ',
		'&icirc;'=>'î',
		'&uuml;'=>'ü',
		'&Aring;'=>'Å',
		'&OElig;'=>'Œ',
		'&ccedil;'=>'ç',
		'&Ccedil;'=>'Ç',
		'&eth;'=>'ð',
		'&Oslash;'=>'Ø',
		'&iquest;'=>'¿',
		'&iexcl;'=>'¡',
		'&euro;'=>'€',
		'&Ntilde;'=>'Ñ',
		'&deg;'=>'°',
		'&Acirc;'=>'Â',
		'&pound;'=>'£',
		'&yen;'=>'¥',
		'&circ;'=>'ˆ',
		'&rsaquo;'=>'›',
		'&cent;'=>'¢',
		'&uacute;'=>'ú',
		'&quot;'=>'"',
		'&oacute;'=>'ó',
		'&ndash;'=>'–',
		'&hellip;'=>'…',
		'&aacute;'=>'á',
		'&ntilde;'=>'ñ',
		'&atilde;'=>'ã',
		'&middot;'=>'·',
		'&acirc;'=>'â',
	);

	foreach($char_list as $key=>$each){
		$input = str_replace($key, $each, $input);
	}
	return $input;
}

/*
QUESTIONS

Things to do when processing this script.
Look for description steps that are over 1024 characters
Look for expected results that are over 1024 characters
Look for tables in summary, step description, expected results and you can move them into an attached file or remove them or fix them
Look for empty table rows
fix instances where it says: ER: and move it to expected results
fix where there are more than one expected result since the script just picks the first one. We don't need to worry about this because we only have testcases with one step.
Look for SELECT in the results and remove the <br /> so all the SQL statments are on their own lines.
fix any testcases with no newline. look for this error in the html output: NO_NEWLINE
fix steps with this error: ERROR:1024_CHARACTERS

*/