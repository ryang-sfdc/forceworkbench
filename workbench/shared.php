<?php
$version = "1.1.12";

function show_error($errors){
	print "<div class='show_errors'>\n";
	print "<img src='images/warning.gif' width='30' height='30' align='middle' border='0' alt='ERROR:' /> ";
	print htmlentities($errors);
	print "</div>\n";
}

function show_info($info){
	print "<div class='show_info'>\n";
	print "<img src='images/info.gif' width='30' height='30' align='middle' border='0' alt='Info:' /> ";
	print htmlentities($info);
	print "</div>\n";
}

function myGlobalSelect($default_object){
	print "<select id='myGlobalSelect' name='default_object' style='width: 20em;'>\n";
	print "<option value=''></option>";
	if (!$_SESSION[myGlobal]){
		try{
		global $mySforceConnection;
		$_SESSION[myGlobal] = $mySforceConnection->describeGlobal();
		} catch (Exception $e) {
	      	$errors = null;
			$errors = $e->getMessage();
			show_error($errors);
			exit;
	    }
	}
	//Print the global object types in a dropdown select box
	foreach($_SESSION[myGlobal]->types as $type){
		print "	<option value='$type'";
		if ($default_object == $type){
			print " selected='true'";
			}
		print " />$type</option> \n";
	}
	print "</select>\n";
}

function field_mapping_set($action,$csv_array){
	if ($action == 'insert' || $action == 'upsert' || $action == 'update'){
		if (isset($_SESSION[default_object])){
			try{
				global $mySforceConnection;
				$describeSObject_result = $mySforceConnection->describeSObjects(array ($_SESSION[default_object]));
			} catch (Exception $e) {
			      	$errors = null;
					$errors = $e->getMessage();
					show_error($errors);
					exit;
		    }
		} else {
		show_error("A default object is required to $action. Go to the Select page to choose a default object and try again.");
	}
	}

	print "<form method='POST' action='$_SERVER[PHP_SELF]'>";

	if ($action == 'upsert'){
		print "<p><strong>Map the Salesforce fields to the columns from the uploaded CSV:</strong></p>\n";
		print "<table class='description'><tr>\n";
		print "<td style='color: red;'>External Id</stong>";
		print "<td><select name='_ext_id' style='width: 100%;'>\n";
		print "	<option value=''></option>\n";
		foreach($describeSObject_result->fields as $fields => $field){
			print   " <option value='$field->name'>$field->name</option>\n";
		}
		print "</select></td></tr></table>\n";
	}

	print "<p><strong>Map the Salesforce fields to the columns from the uploaded CSV:</strong></p>\n";
	print "<table class='description'>\n";
	print "<tr><th>Salesforce Field</th><th>CSV Field</th></tr>\n";

	if ($action == 'insert'){
		foreach($describeSObject_result->fields as $fields => $field){
			if ($field->createable){
				print "<tr";
				if (!$field->nillable && !$field->defaultedOnCreate) print " style='color: red;'";
				print "><td>$field->name</td>";
				print "<td><select name='$field->name' style='width: 100%;'>";
				print "	<option value=''></option>\n";
				foreach($csv_array[0] as $col){
					print   "<option value='$col'";
					if (strtolower($col) == strtolower($field->name)) print " selected='true' ";
					print ">$col</option>\n";
				}
				print "</select></td></tr>\n";
			}
		}
	}

	if ($action == 'update'){
		field_mapping_idOnly_set($csv_array);
		foreach($describeSObject_result->fields as $fields => $field){
			if ($field->updateable){
				print "<tr";
				if (!$field->nillable && !$field->defaultedOnCreate) print " style='color: red;'";
				print "><td>$field->name</td>";
				print "<td><select name='$field->name' style='width: 100%;'>";
				print "	<option value=''></option>\n";
				foreach($csv_array[0] as $col){
					print   "<option value='$col'";
					if (strtolower($col) == strtolower($field->name)) print " selected='true' ";
					print ">$col</option>\n";
				}
				print "</select></td></tr>\n";
			}
		}
	}

	if ($action == 'upsert'){
		field_mapping_idOnly_set($csv_array);
		foreach($describeSObject_result->fields as $fields => $field){
			if ($field->updateable && $field->createable){
				print "<tr";
				if (!$field->nillable && !$field->defaultedOnCreate) print " style='color: red;'";
				print "><td>$field->name</td>";
				print "<td><select name='$field->name' style='width: 100%;'>";
				print "	<option value=''></option>\n";
				foreach($csv_array[0] as $col){
					print   "<option value='$col'";
					if (strtolower($col) == strtolower($field->name)) print " selected='true' ";
					print ">$col</option>\n";
				}
				print "</select></td></tr>\n";
			}
		}
	}


	if ($action == 'delete' || $action == 'undelete' || $action == 'purge'){
		field_mapping_idOnly_set($csv_array);
	}


	print "</table>\n";
	print "<p><input type='submit' name='action' value='Map Fields' />\n";
	print "<input type='button' value='Preview CSV' onClick='window.open(" . '"csv_preview.php"' . ")'></p>\n";
	print "</form>\n";
}


function field_mapping_idOnly_set($csv_array){
	print "<tr style='color: red;'><td>Id</td>";
	print "<td><select name='Id' style='width: 100%;'>";
	print "	<option value=''></option>\n";
	foreach($csv_array[0] as $col){
		print   "<option value='$col'";
		if (strtolower($col) == 'id') print " selected='true' ";
		print ">$col</option>\n";
	}
	print "</select></td></tr>\n";
}


function field_mapping_show($field_map,$ext_id){
	if ($ext_id){
		print "<table class='description'>\n";
		print "<tr><td>External Id</td> <td>$ext_id</td></tr>\n";
		print "</table><p/>\n";
	}

	print "<table class='description'>\n";
	print "<tr><th>Salesforce Field</th><th>CSV Field</th></tr>\n";
	foreach($field_map as $salesforce_field=>$csv_field){
		if($salesforce_field && $csv_field){
			print "<tr><td>$salesforce_field</td> <td>$csv_field</td></tr>\n";
		}
	}
	print "</table>\n";
}


function field_mapping_confirm($action,$field_map,$csv_array,$ext_id){
	if (!($field_map && $csv_array)){
		show_error("CSV file and field mapping not initialized successfully. Upload a new file and map fields.");
	} else {

	if (($action == 'Confirm Update') || ($action == 'Confirm Delete') || ($action == 'Confirm Undelete') || ($action == 'Confirm Purge')){
		if (!$field_map[Id]){
			show_error("Salesforce ID not selected. Please try again.");
			include_once('footer.php');
			exit();
		} else {
		ob_start();
		field_mapping_show($field_map,null);
		$id_col = array_search($field_map[Id],$csv_array[0]);
		for($row=1,$id_count = 0; $row < count($csv_array); $row++){
			if ($csv_array[$row][$id_col]){
				$id_count++;
			}
		}
		$field_mapping_table = ob_get_clean();
		show_info ("The file uploaded contains $id_count records with Salesforce IDs with the field mapping below.");
		print "<p><strong>Confirm the mappings below:</strong></p>";
		print "<p>$field_mapping_table</p>";
		}
	} else {
		$record_count = count($csv_array) - 1;
		show_info ("The file uploaded contains $record_count records to be added to $_SESSION[default_object].");
		print "<p><strong>Confirm the mappings below:</strong></p>";
		field_mapping_show($field_map,$ext_id);
	}

	print "<form method='POST' action='$_SERVER[PHP_SELF]'>";
	print "<p><input type='submit' name='action' value='$action' /></p>\n";
	print "</form>\n";
	}
}

function form_upload_objectSelect_show($file_input_name,$showObjectSelect = FALSE){
	print "<form enctype='multipart/form-data' method='post' action='$_SERVER[PHP_SELF]'>\n";
	print "<input type='hidden' name='MAX_FILE_SIZE' value='512000' />\n"; //max size = 512KB
	print "<p><input type='file' name='$file_input_name' size=44 /></p>\n";
	if ($showObjectSelect){
		 myGlobalSelect($_SESSION[default_object]);
		 $submitLabel = 'Upload & Select Object';
	} else {
		$submitLabel = 'Upload';
	}
	print "<p><input type='submit' name='action' value='$submitLabel' /></p>\n";
	print "</form>\n";
}

function csv_upload_valid_check($file){
	//print "Initializing upload...<br/>";
	//print "Name: $file[name]<br/>";
	//print "Type: $file[type]<br/>";
	//print "Size: $file[size] bytes<br/>";
	//print "Temp Name: $file[tmp_name]<br/>";
	//print "Error: $file[error]<br/>";

	if($file[error] !== 0){
		$upload_error_codes = array(
		       0=>"There is no error, the file uploaded with success",
		       1=>"The file uploaded is too large. Please try again. (Error 1)", //as per PHP config
		       2=>"The file uploaded is too large. Please try again. (Error 2)", //as per form config
		       3=>"The file uploaded was only partially uploaded.  Please try again. (error 3)",
		       4=>"No file was uploaded.  Please try again. (Error 4)",
		       6=>"Missing a temporary folder.  Please try again. (Error 6)",
		       7=>"Failed to write file to disk.  Please try again. (Error 7)",
		       8=>"File upload stopped by extension.  Please try again. (Error 8)"
			);
		return($upload_error_codes[$file[error]]);
	}

	elseif(!is_uploaded_file($file[tmp_name])){
		return("The file was not uploaded from your computer. Please try again.");
	}

	elseif((!stristr($file[type],'csv') || $file[type] !== "application//vnd.ms-excel") && !stristr($file[name],'.csv')){
		return("The file uploaded is not a valid CSV file. Please try again.");
	}

	elseif($file[size] == 0){
		return("The file uploaded contains no data. Please try again.");
	}

	else{
		return(0);
	}
}

function csv_file_to_array($file){
	$csv_array = array();
	$handle = fopen($file, "r");
	for ($row=0; ($data = fgetcsv($handle)) !== FALSE; $row++) {
	   for ($col=0; $col < count($data); $col++) {
	       $csv_array[$row][$col] = $data[$col];
	   }
	}
	fclose($handle);

	if ($csv_array !== NULL){
		return($csv_array);
	} else {
		echo("There were errors parsing your CSV file. Please try again.");
		exit;
	}

}


function csv_array_show($csv_array){
	print "<table class='data_table'>\n";
	print "<tr>";
		for($col=0; $col < count($csv_array[0]); $col++){
			print "<th>";
			print htmlentities($csv_array[0][$col],ENT_QUOTES,'UTF-8');
			print "</th>";
		}
	print "</tr>\n";
	for($row=1; $row < count($csv_array); $row++){
		print "<tr>";
		for($col=0; $col < count($csv_array[0]); $col++){
			print "<td>";
			if ($csv_array[$row][$col]){
				print htmlentities($csv_array[$row][$col],ENT_QUOTES,'UTF-8');
			} else {
				print "&nbsp;";
			}
			print "</td>";
		}
		print "</tr>\n";
	}
	print "</table>\n";
}

function idOnlyCallIds($api_call,$field_map,$csv_array,$show_results){
	if (!($field_map && $csv_array)){
		show_error("CSV file and field mapping not initialized successfully. Upload a new file and map fields.");
	} else {

	$id_array =  array();
	$id_col = array_search($field_map[Id],$csv_array[0]);

	for($row=1; $row < count($csv_array); $row++){
		if ($csv_array[$row][$id_col]){
			$id_array[] = $csv_array[$row][$id_col];
		}
	}

	$results = array();
	$id_array_all = $id_array;

	while($id_array){
		$id_array200 = array_splice($id_array,0,200);
		try{
			global $mySforceConnection;
			if($api_call == 'purge') $api_call = 'emptyRecycleBin';
			$results_more = $mySforceConnection->$api_call($id_array200);

		    if(!$results){
		    	$results = $results_more;
		    } else {
		    	$results = array_merge($results,$results_more);
		    }

		} catch (Exception $e) {
	      	$errors = null;
			$errors = $e->getMessage();
			show_error($errors);
			exit;
	    }
	}
	if($show_results) show_idOnlyCall_results($results,$id_array_all);
	}
}

function putSObjects($api_call,$ext_id,$field_map,$csv_array,$show_results){
	if (!($field_map && $csv_array && $_SESSION[default_object])){
		show_error("CSV file and field mapping not initialized. Upload a new file and map fields.");
	} else {
		$csv_header = array_shift($csv_array);
		$results = array();

		while($csv_array){
			$sObjects = array();
			$csv_array200 = array_splice($csv_array,0,200);

			for($row=0; $row < count($csv_array200); $row++){
			    $sObject = new SObject;
		    	$sObject->type = $_SESSION[default_object];
		    	$fields = array();

		    	foreach($field_map as $salesforce_field => $csv_field){

					if($salesforce_field && $csv_field){
						$col = array_search($field_map[$salesforce_field],$csv_header);
						$field = array($salesforce_field => htmlentities($csv_array200[$row][$col],ENT_QUOTES,'UTF-8'));
					}
					if (!$fields){
						$fields = $field;
					} else {
						$fields = array_merge($fields,$field);
					}
				}
			    $sObject->fields = $fields;
			    array_push($sObjects, $sObject);
			    unset($sObject);
			}

			try{
				global $mySforceConnection;
				if ($api_call == 'upsert'){
					$results_more = $mySforceConnection->$api_call($ext_id,$sObjects);
					unset($sObjects);
				} else {
					$results_more = $mySforceConnection->$api_call($sObjects);
					unset($sObjects);
				}
			} catch (Exception $e) {
		      	$errors = null;
				$errors = $e->getMessage();
				show_error($errors);
				exit;
		    }
		    if(!$results){
		    	$results = $results_more;
		    } else {
		    	$results = array_merge($results,$results_more);
		    }
		}
		if($show_results) show_put_results($results,$api_call);
		}
}

function show_put_results($results,$api_call){
	//check if only result is returned
	if(!is_array($results)) $results = array($results);

	$success_count = 0;
	$error_count = 0;
	ob_start();
	for($row=0; $row < count($results); $row++){
		$excel_row = $row + 2;
		if ($results[$row]->success){
			$success_count++;
			print "<tr>";
			print "<td>" . $excel_row . "</td>";
			print "<td>" . $results[$row]->id . "</td>";
			print "<td>Success</td>";
			if (($api_call == 'upsert' && $results[$row]->created) || $api_call == 'create'){
				print "<td>Created</td>";
			} else {
				print "<td>Updated</td>";
			}
			print "</tr>\n";
		} else {
			$error_count++;
			print "<tr style='color: red;'>";
			print "<td>" . $excel_row . "</td>";
			print "<td>" . $results[$row]->id . "</td>";
			print "<td>" . ucwords($results[$row]->errors->message) . "</td>";
			print "<td>" . $results[$row]->errors->statusCode . "</td>";
			//print "<td>" . $results[$row]->errors->fields . "</td>"; //APIDOC: Reserved for future use. Array of one or more field names. Identifies which fields in the object, if any, affected the error condition.
			print "</tr>\n";
		}
	}
	print "</table><br/>";
	$results_table = ob_get_clean();
	show_info("There were $success_count successes and $error_count errors.");
	print "<br/>\n<table class='data_table'>\n";
	print "<td>1</td> <th>ID</th> <th>Result</th> <th>Status</th>\n";
	print "<p>$results_table</p>";
}


function show_idOnlyCall_results($results,$id_array){
	//check if only result is returned
	if(!is_array($results)) $results = array($results);

	$success_count = 0;
	$error_count = 0;
	ob_start();
	for($row=0; $row < count($id_array); $row++){
		$excel_row = $row + 2;
		if ($results[$row]->success){
			$success_count++;
			print "<tr>";
			print "<td>" . $excel_row . "</td>";
			print "<td>" . $id_array[$row] . "</td>";
			print "<td>Success</td><td></td>";
			print "</tr>";
		} else {
			$error_count++;
			print "<tr style='color: red;'>";
			print "<td>" . $excel_row . "</td>";
			print "<td>" . $id_array[$row] . "</td>";
			print "<td>" . ucwords($results[$row]->errors->message) . "</td>";
			print "<td>" . $results[$row]->errors->statusCode . "</td>";
			//print "<td>" . $results[$row]->errors->fields . "</td>"; //APIDOC: Reserved for future use. Array of one or more field names. Identifies which fields in the object, if any, affected the error condition.
			print "</tr>";
		}
	}
	print "</table><br/>";
	$results_table = ob_get_clean();
	show_info("There were $success_count successes and $error_count errors.");
	print "<p></p><table class='data_table'>\n";
	print "<td>1</td><th>ID</th><th>Result</th><th>Error Code</th>\n";
	print "<p>$results_table</p>";
}


function put($action){
	$confirm_action = 'Confirm ' . ucwords($action);

	if($_POST[action] == $confirm_action){
		require_once('header.php');
		print "<h1>" . ucwords($action) . " Results</h1>";
		if ($action == 'insert') $api_call = 'create'; else $api_call = $action;
		if ($action == 'upsert') $ext_id = $_SESSION[_ext_id]; else $ext_id = NULL;
		putSObjects($api_call,$ext_id,$_SESSION[field_map],$_SESSION[csv_array],true);
		include_once('footer.php');
		unset($_SESSION[field_map],$_SESSION[csv_array],$_SESSION[_ext_id],$_SESSION['file_tmp_name']);
	}

	elseif($_POST[action] == 'Map Fields'){
		require_once('header.php');
		array_pop($_POST); //remove header row
		if ($_POST[_ext_id]){
			$_SESSION[_ext_id] = $_POST[_ext_id];
			$_POST[_ext_id] = NULL;
		}
		$_SESSION[field_map] = $_POST;
		field_mapping_confirm($confirm_action,$_SESSION[field_map],$_SESSION[csv_array],$_SESSION[_ext_id]);
		include_once('footer.php');
	}

	elseif ($_FILES['file'] && $_POST[default_object]){
		require_once('header.php');
		if (csv_upload_valid_check($_FILES['file'])){
			form_upload_objectSelect_show('file',TRUE);
			show_error(csv_upload_valid_check($_FILES['file']));
		} else {
			$csv_file_name = basename($_FILES['file'][name]);
			$_SESSION['file_tmp_name'] = $_FILES['file'][tmp_name];
			$_SESSION[csv_array] = csv_file_to_array($_SESSION['file_tmp_name']);
			$csv_array_count = count($_SESSION[csv_array]) - 1;
			if (!$csv_array_count) {
				show_error("The file uploaded contains no records. Please try again.");
				include_once('footer.php');
				exit();
			} elseif($csv_array_count > 2000){
				show_error ("The file uploaded contains more than 2000 records. The size of the dataset is limited for performance reasons. Please try again.");
				include_once('footer.php');
				exit();
			}
			$info = "The file $csv_file_name was uploaded successfully and contains $csv_array_count row";
			if ($csv_array_count !== 1) $info .= 's';
			show_info($info);
			print "<br/>";
			field_mapping_set($action,$_SESSION[csv_array]);
		}
		include_once('footer.php');
	}

	else {
		require_once ('header.php');
		print "<p><strong>Select an object and upload a CSV file with Salesforce IDs to $action:</strong></p>\n";
		form_upload_objectSelect_show('file',TRUE);
		include_once('footer.php');
	}
}

function idOnlyCall($action){
	if($_POST['action'] == 'Confirm ' . ucfirst($action)){
		require_once('header.php');
		print "<h1>" . ucfirst($action) . " Results</h1>";
		idOnlyCallIds($action,$_SESSION[field_map],$_SESSION[csv_array],true);
		unset($_SESSION[field_map],$_SESSION[csv_array],$_SESSION[update_file_tmp_name]);
		include_once('footer.php');
	}

	elseif($_POST['action'] == 'Map Fields'){
		require_once('header.php');
		array_pop($_POST); //remove header row
		$_SESSION[field_map] = $_POST;
		field_mapping_confirm('Confirm ' . ucfirst($action),$_SESSION[field_map],$_SESSION[csv_array],null);
		include_once('footer.php');
	}

	elseif ($_FILES[file]){
		require_once('header.php');
		if (csv_upload_valid_check($_FILES[file])){
			form_upload_objectSelect_show('file',FALSE);
			show_error(csv_upload_valid_check($_FILES[file]));
			include_once('footer.php');
		} else {
			$csv_file_name = basename($_FILES[file][name]);
			$_SESSION[file_tmp_name] = $_FILES[file][tmp_name];
			$_SESSION[csv_array] = csv_file_to_array($_SESSION[file_tmp_name]);
			$csv_array_count = count($_SESSION[csv_array]) - 1;
			if (!$csv_array_count) {
				show_error("The file uploaded contains no records. Please try again.");
				include_once('footer.php');
				exit();
			}
			elseif ($csv_array_count > 5000) {
				show_error("The file uploaded contains more than 5000 records. The size of the dataset is limited for performance reasons. Please try again.");
				include_once('footer.php');
				exit();
			}
			$info = "The file $csv_file_name was uploaded successfully and contains $csv_array_count row";
			if ($csv_array_count !== 1) $info .= 's';
				show_info($info);
				print "<br/>";
				field_mapping_set($action,$_SESSION[csv_array]);
			}
		}
		else {
			require_once ('header.php');
			print "<p><strong>Upload a CSV file with Salesforce IDs to $action:</strong></p>\n";
			form_upload_objectSelect_show('file',FALSE);
			include_once('footer.php');
		}
}


function debug(){
	print "<pre style='font-family: monospace; text-align: left;'>";
	try{
	global $mySforceConnection;

	print "<h1>GLOBALS</h1>\n";

	print "<strong>COOKIE SUPERGLOBAL VARIABLE</strong>\n";
	var_dump ($_COOKIE);
	print "<hr/>";

	print "<strong>SESSION SUPERGLOBAL VARIABLE</strong>\n";
	var_dump ($_SESSION);
	print "<hr/>";

	print "<strong>POST SUPERGLOBAL VARIABLE</strong>\n";
	var_dump ($_POST);
	print "<hr/>";

	print "<strong>GET SUPERGLOBAL VARIABLE</strong>\n";
	var_dump ($_GET);
	print "<hr/>";

	print "<strong>FILES SUPERGLOBAL VARIABLE</strong>\n";
	var_dump ($_FILES);
	print "<hr/>";

	print "<strong>ENVIRONMENT SUPERGLOBAL VARIABLE</strong>\n";
	var_dump ($_ENV);
	print "<hr/>";

	print "<h1>SOAP MESSAGES</h1>\n";

	print "<strong>LAST REQUEST HEADER</strong>\n";
	print htmlentities($mySforceConnection->getLastRequestHeaders(),ENT_QUOTES,'UTF-8');
	print "<hr/>";

	print "<strong>LAST REQUEST</strong>\n";
	print htmlentities($mySforceConnection->getLastRequest(),ENT_QUOTES,'UTF-8');
	print "<hr/>";

	print "<strong>LAST RESPONSE HEADER</strong>\n";
	print htmlentities($mySforceConnection->getLastResponseHeaders(),ENT_QUOTES,'UTF-8');
	print "<hr/>";

	print "<strong>LAST RESPONSE</strong>\n";
	print htmlentities($mySforceConnection->getLastResponse(),ENT_QUOTES,'UTF-8');
//	print $mySforceConnection->getLastResponse();
	print "<hr/>";
	}
	catch (Exception $e) {
		print "<strong>SOAP Error</strong>\n";
		print_r ($e);
	}

	print "</pre>";
}


?>