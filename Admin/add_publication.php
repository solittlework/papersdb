<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<?php

 // $Id: add_publication.php,v 1.3 2006/05/11 22:32:31 aicmltec Exp $

 /**
  * \file
  *
  * \brief This page is the form for adding/editing a publication.
  *
  * It has many side functions that are needed for the form to work
  * smoothly. It takes the input from the user, and then sends that input to
  * add_publication_db.php.
  */

?>

<html>
<head>
<title>Add or Edit Publication</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
     <link rel="stylesheet" type="text/css" href="../style.css"/>
     </head>

<?

include("header.php");
require('../functions.php');
$link = connect_db();

//User's 10 most popular Authors
function popularauthors(){
    $userauthorcount = 0;
    $user_query
        = "SELECT pub_author.author_id "
        . "FROM pub_author, publication, user "
		. "WHERE publication.submit = user.name "
        . "AND publication.pub_id = pub_author.pub_id "
        . "AND user.login=\"" .$_SERVER['PHP_AUTH_USER'] . "\"";

    $user_result  = mysql_query($user_query)
        or die("Query failed: " . mysql_error());
    while($user_array = mysql_fetch_array($user_result, MYSQL_ASSOC)){
		$popular_users[$user_array['author_id']]++;
		$listofauthors[$userauthorcount++] = $user_array['author_id'];
    }
    if($userauthorcount < 10) $length = $userauthorcount; else $length = 10;
    for($count = 0; $count < $length; $count++){
        $largest = "";
        $largestvalue = 0;
        for($index = 0; $index< $userauthorcount; $index++)
            if($popular_users[$listofauthors[$index]] > $largestvalue){
                $largestvalue = $popular_users[$listofauthors[$index]];
                $largest = $listofauthors[$index];
            }
        $finallist[$count] = $largest;
        $popular_users[$largest] = 0;
    }
    return $finallist;

}

// Global variable to keep track of what we're doing - can change this
// to not be a boolean if we want to deal with more than 2 different
// operations (save, new) on this page
$edit = FALSE;
//////////////////////EDIT START/////////////////////////////////
// Check to see if we've been passed a publication ID
if ((isset($_GET['pub_id']) && $_GET['pub_id'] != "") && ($new != "false")) {

	// Set "edit mode" to true - we could just check for the existence
	// of pub_id in the GET variables, but this is more clear.
	$edit = TRUE;
	// Get publication info
	$pubInfo = get_publication_info($_GET['pub_id']);

	// Check if the publication actually exists
	if ($pubInfo == NULL) {
        "Error: Publication with ID " . $_GET['pub_id'] . " doesn't exist.";
        disconnect_db($link);
		exit;
	}
    if(($intpoint == "")&&($ext == "")){
        $point_query = "SELECT type, name, value FROM pointer WHERE pub_id="
            . $_GET['pub_id'];
        $point_result = query_db($point_query);
        $intpoint = 0;
        $ext = 0;
        while($point_line = mysql_fetch_array($point_result, MYSQL_ASSOC)){
            if($point_line[type] == "int"){
                $internal = "intpointer".($intpoint++);
                $$internal = $point_line[value];
            }
            else if($point_line[type] == "ext"){
                $externalname = "extname".$ext;
                $$externalname = $point_line[name];

                $temparray1 = split("<a href=\"",$point_line[value]);
                $temparray2 = split("\" target=\"_blank\">",$temparray1[1]);
                $temparray3 = split("</a>",$temparray2[1]);

                $externalvalue = "extvalue".$ext;
                $$externalvalue = $temparray3[0];
                $externallink = "extlink".($ext++);
                $$externallink = $temparray2[0];
            }
        }
    }

	// Set the variables to be set in the page as initial values.  We have to
	// check and see if there's a value that's already been posted back to us,
	// and use that instead, in case it changes between page updates.

	$catvals = get_category($_GET['pub_id']);
	$category_id = $catvals['cat_id'];

	if ($_GET['category'] == "") {
		$category = $catvals['category'];
	}

	if ($_GET['title'] == "") {
		$title = $pubInfo['title'];
	}

	if ($_GET['abstract'] == "") {
		$abstract = $pubInfo['abstract'];
	}

	if ($_GET['venue'] == "") {
		$venue = $pubInfo['venue'];
	}

	if ($_GET['extra_info'] == "") {
		$extra_info = $pubInfo['extra_info'];
	}

	if ($_GET['keywords'] == "") {
		$keywords = $pubInfo['keywords'];
	}

	// Deal with the publication date.
	// variables we care about are $month, $day, $year.
	$published = $pubInfo['published'];

	$myYear = strtok($published,"-");
	$myMonth = strtok("-");
	$myDay = strtok("-");

	if ($_GET['month'] == "") {
		$month = $myMonth;
	}

	if ($_GET['day'] == "") {
		$day = $myDay;
	}

	if ($_GET['year'] == "") {
		$year = $myYear;
	}


	// Check the number of materials
	// Don't allow the user to set the number of materials less
	// than what currently exist in the DB.
	$dbMaterials = get_num_db_materials ($pub_id);

	if ($_GET['numMaterials'] != "") {
		if ($_GET['numMaterials'] < $dbMaterials) {
			$numMaterials = $dbMaterials;
		}
	}
	else {
		$numMaterials = $dbMaterials;
	}


	// andy_note: Paper is a special case! For now we'll use strtok to
	// get only the name of the file and discard the rest.
	$paper = $pubInfo['paper'];
	$paperTmp = strtok($paper,"/");

	// Since strtok will return a "false" as the last element, the
	// item we're actually interested in is the item that appears
	// *second to last*.  So we set $paper = $paperTmp and then get
	// the right thing.
	while ($paperTmp) {
		$paper = $paperTmp;
		$paperTmp = strtok("/");
	}

	$authors_from_db = get_authors($_GET['pub_id']);
}
/////////////////////EDIT END///////////////////////////////////////

while (!(strpos($category, "\\") === FALSE)) {
    $category = stripslashes($category);
}
while (!(strpos($title, "\\") === FALSE)) {
    $title = stripslashes($title);
}


/* Adding a new author

This takes input from add_author.php and then adds it to the
database. This code is on this page because it allows the author
to be instantly added to the list to choose from.

*/
if ($newAuthorSubmitted == "true") {
    $authorname = trim($lastname) . ", " .trim($firstname);
    $check_query = "SELECT author_id FROM author WHERE name=\"$authorname\"";
    $check_result = mysql_query($check_query);
    $check_array =  mysql_fetch_array($check_result, MYSQL_ASSOC);
    if ($check_array[author_id] != "") {
        print "<script language=\"Javascript\">"
            . "alert (\"Author already exists.\")"
            . "</script>";
    }
    else {
	    //add http:// to webpage address if needed
	    if(strpos($webpage, "http") === FALSE)
        {
		    $webpage = "http://".$webpage;
        }

		/* Performing SQL query */
		$author_query = "INSERT INTO author "
            . "(author_id, name, title, email, organization, webpage) "
            . "VALUES (NULL, \"$authorname\", \"$auth_title\", \"$email\", "
            . "\"$organization\", \"$webpage\")";
		$author_result = mysql_query($author_query)
            or die("Query failed : " . mysql_error());

		$unique_interest_id_counter = 0;

		for ($i = 0; $i < count($newInterest); $i++) {
			if ($newInterest[$i] != "") {
				$interest_query = "INSERT INTO interest "
                    . "(interest_id, interest) "
                    . "VALUES (NULL, \"$newInterest[$i]\")";
				$interest_result = mysql_query($interest_query)
                    or die("Query failed : " . mysql_error());

				$interest_id_query = "SELECT interest_id FROM interest "
                    . "WHERE interest=\"$newInterest[$i]\"";
				$interest_id_result = mysql_query($interest_id_query)
                    or die("Query failed: " . mysql_error());
				$interest_id_temp_array
                    =  mysql_fetch_array($interest_id_result, MYSQL_ASSOC);

				$interest_id_array[$unique_interest_id_counter]
                    = $interest_id_temp_array[interest_id];
				$unique_interest_id_counter++;

				mysql_free_result($interest_id_result);
			}
		}

		$author_id_query
            = "SELECT author_id FROM author WHERE name=\"$authorname\"";
		$author_id_result = mysql_query($author_id_query)
            or die("Query failed: " . mysql_error());

		$author_id_array = mysql_fetch_array($author_id_result, MYSQL_ASSOC);
		$author_id = $author_id_array['author_id'];

		$temp = "";

		for ($i = 0; $i < $numInterests; $i++) {
			if ($interests[$i] != null) {
				$temp .= " (" . $author_id . "," . $interests[$i] . "),";
			}
		}

		for ($i = 0; $i < $unique_interest_id_counter; $i++) {
			$temp .= " (" . $author_id . "," . $interest_id_array[$i] . "),";
		}

		$temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));

		if ($temp != "") {
			$author_interest_query
                = "INSERT INTO author_interest (author_id, interest_id) VALUES $temp";
			$author_interest_result = mysql_query($author_interest_query) or die("Query failed: " . mysql_error());
		}

		$newAuthorSubmitted == "false";

		// This is to preserve the selections the user has already made
		$all_author_query = "SELECT name FROM author";
		$all_author_result = mysql_query($all_author_query) or die("Query failed: " . mysql_error());
		$position = -1;
		$author_counter = 0;

		while ($all_author_line = mysql_fetch_array($all_author_result, MYSQL_ASSOC)) {
			if (strcmp($all_author_line['name'], $authorname) == 0) {
				$position = $author_counter;
			}
			$author_counter++;
		}

		$push_counter = 0;

		for ($i = 0; $i < $author_counter; $i++) {
			if ($i >= $position) {
				if ($authors[$i] != "") {
					$push_array[$push_counter] = $i + 1;
					$push_counter++;
				}
			}
		}

		for ($i = 0; $i < ($author_counter + 1); $i++) {
			if ($i > $position) {
				$authors[$i] = "";
			}
			if (in_array($i, (array)$push_array)) {
				$authors[$i] = $i . "selected";
			}
		}

		$authors[$position] = $position . "selected";

		mysql_free_result($author_id_result);
		mysql_free_result($all_author_result);

	}

	if($fromauthorspage == "true")
	{
		print "<h3>Author added.</h3>";
		print "<a href=\"../list_author.php?admin=true\">Back to Authors</a>";
		print "<br><a href=\"./\">Administrator Page</a>";
		exit;

	}
}

/* Adding a new category
 This code takes input from add_category.php and
 adds the category to the database. Like the authors,
 this is here so that the newly added category can be
 instantly selected.
*/
if ($newCatSubmitted == "true") {
    /* Connecting, selecting database */

    /* Performing SQL query */
    $cat_query = "INSERT INTO category (cat_id, category) VALUES (NULL, \"$catname\")";
    $cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());

    $unique_info_id_counter = 0;

    for ($i = 0; $i < count($newField); $i++) {
        if ($newField[$i] != "") {
            $info_query = "INSERT INTO info (info_id, name) VALUES (NULL, \"$newField[$i]\")";
            $info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());

            $info_id_query = "SELECT info_id FROM info WHERE name=\"$newField[$i]\"";
            $info_id_result = mysql_query($info_id_query) or die("Query failed: " . mysql_error());
            $info_id_temp_array =  mysql_fetch_array($info_id_result, MYSQL_ASSOC);

            $info_id_array[$unique_info_id_counter] = $info_id_temp_array[info_id];
            $unique_info_id_counter++;

            mysql_free_result($info_id_result);
        }
    }

    // update our information to sync with what we added to the db
    $cat_id_query = "SELECT cat_id FROM category WHERE category=\"$catname\"";
    $cat_id_result = mysql_query($cat_id_query) or die("Query failed: " . mysql_error());

    $cat_id_array = mysql_fetch_array($cat_id_result, MYSQL_ASSOC);
    $cat_id = $cat_id_array[cat_id];

    $temp = "";

    //if there were additional fields associated with the category then add them to cat_info
    if ($unique_info_id_counter!=0){

        for ($i = 0; $i < $numInfo; $i++) {
            if ($related[$i] != null) {
                $temp .= " (" . $cat_id . "," . $related[$i] . "),";
            }
        }

        for ($i = 0; $i < $unique_info_id_counter; $i++) {
            $temp .= " (" . $cat_id . "," . $info_id_array[$i] . "),";
        }

        $temp = substr_replace($temp, "", (strlen($temp) - 1), strlen($temp));
        $cat_info_query = "INSERT INTO cat_info (cat_id, info_id) VALUES $temp";
        $cat_info_result = mysql_query($cat_info_query) or die("Query failed: " . mysql_error());
    }
    $newCatSubmitted = "false";
    $category = $catname;

    mysql_free_result($cat_id_result);

}

$info[0] = "";

/* Performing SQL query */
$cat_query = "SELECT category FROM category";
$cat_result = mysql_query($cat_query) or die("Query failed : " . mysql_error());

$venue_query = "SELECT venue_id, title FROM venue ORDER BY title";
$venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());

if($category != NULL){
    $catid_query = "SELECT cat_id FROM category WHERE category = \"$category\"";
    $catid_result = mysql_query($catid_query) or die("Query failed : " . mysql_error());
    $catid_line = mysql_fetch_array($catid_result, MYSQL_ASSOC);
    $category_id = $catid_line['cat_id'];
}
$info_query = "SELECT info.name FROM info, category, cat_info WHERE "
    . "category.cat_id       = cat_info.cat_id "
    . "AND info.info_id      = cat_info.info_id "
    . "AND category.category = \"$category\"";
$info_result = mysql_query($info_query) or die("Query failed : " . mysql_error());

$info_counter = 0;
while ($info_line = mysql_fetch_array($info_result, MYSQL_ASSOC)) {
    $info[$info_counter] = $info_line[name];
    $info_counter++;
}
if($pub_id == "")
	$author_query = "SELECT * FROM author ORDER BY name ASC";
else
	$author_query = "SELECT author.name, author.author_id FROM author, pub_author where".
        " author.author_id=pub_author.author_id AND pub_author.pub_id=$pub_id ORDER BY pub_author.rank";
$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());

// Optiontransfer is the author selection windows.
?>
<SCRIPT LANGUAGE="JavaScript" SRC="OptionTransfer.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript">
	var opt = new OptionTransfer("authors[]","authorslist[]");
//opt.setAutoSort(true);
opt.saveRemovedLeftOptions("removedLeft");
opt.saveAddedLeftOptions("addedLeft");
opt.saveNewLeftOptions("selected_authors");
</SCRIPT>
<script language="JavaScript" type="text/JavaScript">

    window.name="add_publication.php";
function dataKeep(tab) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
		if ((document.forms["pubForm"].elements[i].value != "") &&
            (document.forms["pubForm"].elements[i].value != null)) {
			if (info_counter > 0) {
                temp_qs = temp_qs + "&";
			}

			if (document.forms["pubForm"].elements[i].name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;

				for (j = 0; j < author_array.length; j++) {

                    if (author_count > 0) {
                        author_list = author_list + "&";
                    }
                    author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
                    author_count++;

				}

				temp_qs = temp_qs + author_list;
			}
			else if(document.forms["pubForm"].elements[i].name == "comments")
				temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value.replace("\"","'");

			else if(document.forms["pubForm"].elements[i].name == "nopaper"){
                if(document.forms["pubForm"].elements[i].checked)
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;
			}
			else if(document.forms["pubForm"].elements[i].name == "ext"){
                if(tab == "addext")
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print ($ext+1); ?>";
                else if(tab == "remext")
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print ($ext-1); ?>";
                else
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print $ext; ?>";
			}
			else if(document.forms["pubForm"].elements[i].name == "intpoint"){
                if(tab == "addint")
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print ($intpoint+1); ?>";
                else if(tab == "remint")
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print ($intpoint-1); ?>";
                else
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print $intpoint; ?>";
			}
			else if(document.forms["pubForm"].elements[i].name == "numMaterials"){
                if(tab == "addnum")
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print ($numMaterials+1); ?>";
                else if(tab == "remnum")
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + "<? print ($numMaterials-1); ?>";
            }
			else
				temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;

			info_counter++;
		}
	}
	if((tab == "addnum")||(tab == "remnum"))
		temp_qs = temp_qs + "&#" + "STEP2";
	if(((tab == "addext")||(tab == "remext"))||((tab == "addint")||(tab == "remint")))
		temp_qs = temp_qs + "&#" + "pointers";
	else if(tab != "none")
		temp_qs = temp_qs + "&#" + tab;
	temp_qs = temp_qs.replace("\"", "?");
	temp_qs = temp_qs.replace(" ", "%20");
	location.href = "http://" + "<? print $_SERVER["HTTP_HOST"]; print $PHP_SELF; ?>?" + temp_qs;
}

function dataKeepPopup(page) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
		if ((document.forms["pubForm"].elements[i].value != "") &&
            (document.forms["pubForm"].elements[i].value != null)) {
			if (info_counter > 0) {
                temp_qs = temp_qs + "&";
			}
			if (document.forms["pubForm"].elements[i].name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;

				for (j = 0; j < author_array.length; j++) {
					if (author_array[j].selected == 1) {
						if (author_count > 0) {
							author_list = author_list + "&";
						}
						author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
						author_count++;
					}
				}

				temp_qs = temp_qs + author_list;
			}
			else {
				if(document.forms["pubForm"].elements[i].name == "comments"){
					temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value.replace("\"","'");
				}
				else
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;
			}

			info_counter++;
		}
	}

	if (page == "add_category.php") {
		temp_qs = temp_qs + "&newFields=0";
	}
    temp_qs = temp_qs + "&new=false";

	//var temp_url = "http://" + "<? print $_SERVER["HTTP_HOST"]; ?>/~loh/" + page + "?" + temp_qs;
	var temp_url = "./" + page + "?" + temp_qs;
	temp_url = temp_url.replace(" ", "%20");
	temp_url = temp_url.replace("\"", "'");
	if(page == "keywords.php")
		window.open(temp_url, 'Add', 'width=860,height=600,scrollbars=yes,resizable=yes');
	else
		window.open(temp_url, 'Add', 'width=700,height=405,scrollbars=yes,resizable=yes');
}
function help(q) {
    temp_url = "./help.php?q=" + q;
    window.open(temp_url, 'Add', 'width=700,height=405,scrollbars=yes,resizable=no');

}

function dataKeepPopupWithID(page, id) {
	var temp_qs = "";
	var info_counter = 0;

	for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
		if ((document.forms["pubForm"].elements[i].value != "") &&
            (document.forms["pubForm"].elements[i].value != null)) {
			if (info_counter > 0) {
                temp_qs = temp_qs + "&";
			}
			if (document.forms["pubForm"].elements[i].name == "authors[]") {
				author_array = document.forms["pubForm"].elements['authors[]'];
				var author_list = "";
				var author_count = 0;

				for (j = 0; j < author_array.length; j++) {

                    if (author_count > 0) {
                        author_list = author_list + "&";
                    }
                    author_list = author_list + "authors[" + j + "]=" + author_array[j].value;
                    author_count++;

				}

				temp_qs = temp_qs + author_list;
			}
			else {
				if(document.forms["pubForm"].elements[i].name == "comments"){
					temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value.replace("\"","'");
				}
				else
                    temp_qs = temp_qs + document.forms["pubForm"].elements[i].name + "=" + document.forms["pubForm"].elements[i].value;
			}

			info_counter++;
		}
	}

	if (page == "add_category.php") {
		temp_qs = temp_qs + "&newFields=0";
	}
	temp_qs = temp_qs.replace("\"", "?");

	//var temp_url = "http://" + "<? print $_SERVER["HTTP_HOST"]; ?>/~loh/" + page + "?" + temp_qs + "&pub_id=" + id;
	var temp_url = "./" + page + "?" + temp_qs + "&pub_id=" + id;
	temp_url = temp_url.replace(" ", "%20");
	window.open(temp_url, 'Add');//, 'width=600,height=350,scrollbars=yes,resizable=yes');
}

function verify(num) {

	if (document.forms["pubForm"].elements["category"].value == "") {
        alert("Please select a category for the publication.");
        return false;
	}
	else if (document.forms["pubForm"].elements["title"].value == "") {
        alert("Please enter a title for the publication.");
        return false;
	}
	else if(document.forms["pubForm"].elements["nopaper"].value == "false"){
        if (document.forms["pubForm"].elements["uploadpaper"].value == "") {
            alert("Please choose a paper to upload or select \"No Paper\".");
            return false;
        }
	}
	else if (document.forms["pubForm"].elements["selected_authors"].value == "") {
        alert("Please select the author(s) of this publication.");
        return false;
	}
	else if (document.forms["pubForm"].elements["abstract"].value == "") {
        alert("Please enter the abstract for this publication.");
        return false;
	}
	else if (document.forms["pubForm"].elements["keywords"].value == "") {
        alert("Please enter the keywords for this publication.");
        return false;
	}
	else
        return true;

	alert("Error: Verifying");
	return false;

}

function resetAll() {
	location.href="./add_publication.php";
}
function refresher() { window.location.reload(true);}

</script>

<body  onLoad="opt.init(document.forms[0])">
    <a name="Start"></a>
    <h3><? if ($edit)print "Edit"; else print "Add"; ?> Publication</h3>
    <?
    if(!$edit) {
        print "Adding a publication takes two steps:<br>"
            . "1. Fill in the appropriate fields<br>"
            . "2. Upload the paper and any additional materials<br><br>"
            . "<div id=\"highlight\">For help on any field just click the "
            . "field name.</div>";
    }
?>

<form name="pubForm" action="add_publication_db.php" method="POST"
    enctype="multipart/form-data">

    <?
if ($edit) {
    print "<input type=\"hidden\" name=\"pub_id\" value=\"". $pub_id
    . "\"> \n";
}
?>

<table width="790" border="0" cellspacing="0" cellpadding="6">
    <tr>
<td colspan="2"><hr></td>
</tr>
<tr>
<td colspan="2"><a name="STEP1"></a><div id="emph">Step 1:</div></td>
</tr>
<!-- Publication Venue -->
<?
if ($edit == true) {
    if (strstr($venue, "venue_id:<")) {
        $tokens = split('venue_id:<|>', $venue);
        //print "<br> $venue <br>";
       //print_r($tokens);
        for ($i=0; $i<count($tokens); $i++) {
            if (strlen($tokens[$i]) > 0) {
                $venue_id = $tokens[$i];
                break;
            }
        }
    }
    else if (strlen($venue) > 0) {
        $venue_id = -2;
    }
    else {
        $venue_id = -1;
    }
}
?>
<tr>
<td width="25%" valign="top">
    <a href="javascript:help('publication_venue');">
    <div id="field">Publication Venue:</div></a>
    </td>
    <td width="75%">
    <select name="venue_id" onChange="javascript:dataKeep('Start');">
    <option value="-1">--- Select a Venue ---</option>
    <option value="-1" <? if($venue_id == -1) print "SELECTED"; ?>>No Venue</option>
    <option value="-2" <? if($venue_id == -2) print "SELECTED"; ?>>Unique Venue</option>
    <option value="-1">----------------------------</option>
    <?
    while ($venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC)) {
        print "<option value=\"" . $venue_line['venue_id'] . "\"";

        if ($venue_id == $venue_line['venue_id'])
            print " SELECTED ";

        print ">" . $venue_line['title'] . "</option> \n";
    }
?>
</select>
&nbsp;&nbsp;<a href="javascript:dataKeepPopup('add_venue.php');"><font face="Arial, Helvetica, sans-serif" size="1">Add a New Venue</font></a>
<BR>
<?
if(($venue_id != "")&&($venue_id != -1)&&($venue_id != -2)) {

    $venue_query = "SELECT * FROM venue WHERE venue_id=$venue_id";
    $venue_result = mysql_query($venue_query) or die("Query failed : " . mysql_error());
    $venue_line = mysql_fetch_array($venue_result, MYSQL_ASSOC);
    $venue_name = $venue_line['name'];
    $venue_url = $venue_line['url'];
    $venue_type = $venue_line['type'];
    $venue_data = $venue_line['data'];

    //if($pub_id == ""){
    if((($category == "")||($category == "In Conference"))||(($category == "In Workshop")||($category == "In Journal"))){

        if($venue_type == "Conference")
            $category = "In Conference";
        else if($venue_type == "Workshop")
            $category = "In Workshop";
        else if($venue_type == "Journal")
            $category = "In Journal";

    }

    if(($venue_line[date] != NULL)&&($venue_line[date] != ""))
    {
        $date = split("-", $venue_line[date]);
        $year = $date[0];
        $month = $date[1];
        $day = $date[2];
    }
    //}
}


?>
</td>
</tr>

<!-- Category -->
<tr>
<td width="25%" valign="top">
          <a href="javascript:help('category');">
          <div id="field">
          Category: </a></td>
<td width="75%">
          <select name="category" onChange="javascript:dataKeep('Start');">
          <option value=" ">--- Please Select a Category ---</option>
<?
while ($cat_line = mysql_fetch_array($cat_result, MYSQL_ASSOC)) {
    print "<option value=\"" . $cat_line['category'] . "\"";

    if ($category == $cat_line['category'])
        print " SELECTED ";

    print ">" . $cat_line['category'] . "</option> \n";
}
?>
</select>
&nbsp;&nbsp;<a href="javascript:dataKeepPopup('add_category.php');"><font face="Arial, Helvetica, sans-serif" size="1">Add Category</font></a>
</td>
</tr>


<!-- Title of the paper -->
<tr>

<td width="25%" valign="top"><A href="javascript:help('title');"><font color="#000000" size="2" face="Arial, Helvetica, sans-serif"><b>Title: </b></font></a></td>
<td width="75%"><input type="text" name="title" size="93" maxlength="250" value="<? print stripslashes($title); ?>"></td>
</tr>


<!-- Authors  -->
<tr>
<td width="25%"><A href="javascript:help('authors');"><font color="#000000" size="2" face="Arial, Helvetica, sans-serif"><b>Authors: </b></font></a></td>
<td width="75%">
          <TABLE>
<tr>
<td>
<a href="javascript:opt.moveOptionUp()"><FONT COLOR="#FFFFFF"><img src="../up_arrow.jpg"></FONT></a><BR><BR>
<a href="javascript:opt.moveOptionDown()"><FONT COLOR="#FFFFFF"><img src="../down_arrow.jpg"></FONT></a><BR><BR>
</td>
<td>
<SELECT NAME="authors[]" MULTIPLE SIZE=14 onDblClick="opt.transferRight()">
          <?
                                                                                                                                       // Jeff: the below code doesn't appear to do fuck-all
                                                                                                                                       /*$counter1 = 0;
                                                                                                                                        if($selected_authors == "")
                                                                                                                                        if($pub_id != "")
                                                                                                                                        while ($author_line1 = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
                                                                                                                                        if ($authors[$counter1] != "" ||
                                                                                                                                        $authors_from_db[$author_line1[name]] != ""){
                                                                                                                                        print "<option value=\"" . $author_line1[author_id] . "\"" . "";
                                                                                                                                        print ">" . $author_line1[name] . "</option>";}
                                                                                                                                        $counter1++;
                                                                                                                                        }

                                                                                                                                        if($selected_authors != ""){

                                                                                                                                        $temparray = split(",",$selected_authors);
                                                                                                                                        for($a = 0; $a < count($temparray); $a++){
                                                                                                                                        $authorkeep_query = "SELECT * FROM author WHERE author_id=\"".$temparray[$a]."\"";
                                                                                                                                        $authorkeep_result = mysql_query($authorkeep_query) or die("Query failed : " . mysql_error());
                                                                                                                                        $authorkeep_line = mysql_fetch_array($authorkeep_result, MYSQL_ASSOC);
                                                                                                                                        print "<option value=\"" . $temparray[$a] . "\"" . "";
                                                                                                                                        print ">" . $authorkeep_line[name] . "</option>";
                                                                                                                                        }
                                                                                                                                        }*/
if ($edit == TRUE) {
    $author_query = "SELECT author.name, author.author_id FROM author, pub_author WHERE author.author_id=pub_author.author_id AND pub_author.pub_id=" . quote_smart($_GET['pub_id']) . " ORDER BY author.name ASC";
    $author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
    $counter = 0;
    while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
        if (!($authors[$counter] != "" || $authors_from_db[$author_line['name']] != "")){
            $found = false;
            for($a = 0; $a < count($temparray); $a++)
                if($author_line[author_id] == $temparray[$a])
                    $found = true;
            if(!$found){
                print "<option value=\"" . $author_line['author_id'] . "\"" . "";
                print ">" . $author_line['name'] . "</option>\n";
            }
        }
        $counter++;
    }
}
?>

</SELECT>
</td>
<td><center>

<INPUT TYPE="button" NAME="right" VALUE="&gt;&gt;" ONCLICK="opt.transferRight()"><BR><BR>
<INPUT TYPE="button" NAME="left" VALUE="&lt;&lt;" ONCLICK="opt.transferLeft()"><BR><BR>
<a href="javascript:dataKeepPopup('add_author.php?popup=true');"><font face="Arial, Helvetica, sans-serif" size="1">Add New<BR>Author To<BR>Database</font></a><BR><BR>
</center>
</td>
<td>
<SELECT NAME="authorslist[]" MULTIPLE SIZE=14 onDblClick="opt.transferLeft()">
          <?
if ($edit == TRUE)
    $author_query = "SELECT author.name, author.author_id FROM author LEFT JOIN pub_author ON (author.author_id=pub_author.author_id AND pub_author.pub_id=" . quote_smart($_GET['pub_id']) . ") WHERE pub_author.pub_id IS NULL ORDER BY author.name ASC";
else
    $author_query = "SELECT author.name, author.author_id FROM author ORDER BY author.name ASC";
$author_result = mysql_query($author_query) or die("Query failed : " . mysql_error());
$counter = 0;
while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
    if (!($authors[$counter] != "" || $authors_from_db[$author_line['name']] != "")){
        $found = false;
        for($a = 0; $a < count($temparray); $a++)
            if($author_line[author_id] == $temparray[$a])
                $found = true;
        if(!$found){
            print "<option value=\"" . $author_line['author_id'] . "\"" . "";
            print ">" . $author_line['name'] . "</option>\n";
        }
    }
    $counter++;
}
?>
</SELECT>
<input type="hidden" name="selected_authors">

    </td>
<td valign="top">
    <table width="150"><tr><td>
<?
  // User selected author list
print "<b>Favorite Collaborators:</b><br>";
$user_query = "SELECT author.author_id, author.name FROM user_author, author "
    . "WHERE user_author.author_id=author.author_id "
    . "AND user_author.login=\"" . $_SERVER['PHP_AUTH_USER']
    . "\" ORDER BY author.name";

$user_result = mysql_query($user_query)
    or die("Query failed: " . mysql_error());

while($user_array = mysql_fetch_array($user_result, MYSQL_ASSOC))
{
    print "<li><a href=\"javascript:opt.moveToLeft(". $user_array['author_id']
        . ");\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">"
        . $user_array['name'] . "</font></a><br>";
}

?>
&nbsp;&nbsp;
<font align=bottom face="Arial, Helvetica, sans-serif" size="1">
          <a href ="login.php?status=edit">Add/Change collaborators</a></font>
</td></tr></table></td>
<td valign="top"> <table width="150"><tr><td>
<?
 // Most used authors by the user list
print "<b>Your Most Used Authors:</b><br>";
$thelist = popularauthors();
for($a = 0; $a < count($thelist); $a++)
    if($thelist[$a] != ""){
        $user_query = "SELECT name FROM author WHERE author_id = ".$thelist[$a];
        $user_result = mysql_query($user_query) or die("Query failed: " . mysql_error());
        $user_array = mysql_fetch_array($user_result, MYSQL_ASSOC);
        print "<li><a href=\"javascript:opt.moveToLeft(".$thelist[$a].");\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".$user_array['name']."</font></a><br>";
    }
?>
</td></tr></table></td>
</tr>
</TABLE>
</td>
</TR>


<!-- Abstract -->
<tr>
<td width="25%" valign="top"><A href="javascript:help('abstract');"><font color="#000000" size="2" face="Arial, Helvetica, sans-serif"><b>Abstract:</b></font></a><BR>
<font face="Arial, Helvetica, sans-serif" size="1" color="red">HTML enabled</font></td>
<td width="75%"><textarea name="abstract" cols="70" rows="10"><? print stripslashes($abstract); ?></textarea></td>
</tr>
<!-- Venue Show  -->
<?
if($venue_id >= 0){
    print "<tr>";
    if($venue_type != "") {
        print "<td width=\"25%\" valign=\"top\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">"
            . "<b>" . $venue_type . ":</b></font></td>";

        }
    print "<td>";
    if($venue_url != "")
        print " <a href=\"".$venue_url."\" target=\"_blank\">";
    if($venue_name != "")
        print $venue_name;
    if($venue_url != "")
        print "</a>";
    print "</td></tr>";
    if($venue_data != ""){
        print "<tr>";
        print "<td width=\"25%\" valign=\"top\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>";

        if($venue_type == "Conference")
            print "Location:";
        else if($venue_type == "Journal")
            print "Publisher:";
        else if($venue_type == "Workshop")
            print "Associated Conference:";

        print "</b></font></td>";
        print "<td>" . $venue_data ."</td></tr>";
    }

}

if($venue_id == -2) {
    print "<tr>"
        . "<td width=\"25%\" valign=\"top\">"
        . "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">"
        . "<b>Unique Venue:</b></font><br/>"
        . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\" color=\"red\">"
        . "HTML enabled</font></td>"
        . "<td width=\"75%\">"
        . "<textarea name=\"venue\" cols=\"70\" rows=\"5\">"
        . stripslashes($venue) . "</textarea></td>"
        . "</tr>";
}
?>

<!-- Extra Information -->
<? if($extrainfoSubmitted){
    $extra_info = trim($extra_info);
    $count = count($extra);
    for($q=0; $q < $count; $q++){
        if($extra_info != "")
            $extra_info .= ", ";
        $extra_info .= array_shift($extra);
    }
    $extra_info = trim($extra_info);
}?>
<tr>
<td width="25%" valign="top">
    <a href="javascript:help('extra_information');">
    <font color="#000000" size="2" face="Arial, Helvetica, sans-serif">
    <b>Extra Information:</b></a><a NAME="extra"></a>
    </font><br/>
    <font size="1">Optional</font>
    </td>
    <td width="75%">
    <textarea name="extra_info" cols="70" rows="5">
    <? print stripslashes($extra_info); ?></textarea>
    <br.>&nbsp;&nbsp;
<a href="javascript:dataKeepPopup('extra_info.php');">
                   <font face="Arial, Helvetica, sans-serif" size="1">
                   Select from a list of previously used information options
                   </font></a>
</td>
</tr>

<!-- Pointers -->
<? if($ext == "")
     $ext = 0;
if($intpoint == "")
    $intpoint = 0;
print "<input type=\"hidden\" name=\"ext\" value=\"$ext\">";
print "<input type=\"hidden\" name=\"intpoint\" value=\"$intpoint\">";
$e = 0;
do{
    print "<tr>"
        . "<td width=\"25%\" valign=\"top\">";
    if($e == 0) {
        print "<a name=\"pointers\"></a>"
            . "<a href=\"javascript:help('pointers');\">"
            . "<font color=\"#000000\" size=\"2\" "
            . "face=\"Arial, Helvetica, sans-serif\">"
            . "<b>External Pointers:</b></font></a><br/>"
            . "<font size=\"1\">Optional</font>";
        }
    print "</td>"
        . "<td width=\"75%\">";
    if($ext != 0) {
        $tempname = "extname".$e;
        $tempvalue = "extvalue".$e;
        $templink = "extlink".$e;
        if($$tempname == "") $$tempname = "Pointer Type";
        if($$templink == "") $$templink = "http://";
        if($$tempvalue == "") $$tempvalue = "Title of link";

        print "<table><tr>"
            . "<td><input type=\"text\" name=\"extname" . $e . "\""
            . " size=\"17\" maxlength=\"250\" value=\"" . $$tempname
            . "\"><b> :</b></td>"
            . "<td><input type=\"text\" name=\"extvalue" . $e . "\""
            . " size=\"20\" maxlength=\"250\" value=\"" . $$tempvalue . "\">"
            . "</td>"
            . "<td><input type=\"text\" name=\"extlink" . $e . "\""
            . " size=\"30\" maxlength=\"250\" value=\"" . $$templink . "\">"
            . "</td>"
            . "</tr></table>";
    }
    else {
        print "<a href=\"javascript:dataKeep('addext');\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"3\">"
            . "Add an external pointer</a>";
    }
    print "</td></tr>";

    if($e == ($ext-1)) {
        print "<tr><td></td><td valign=\"top\">&nbsp;&nbsp;"
            . "<a href=\"javascript:dataKeep('addext');\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
            . "Add another external pointer</a>";
        if ($ext > 0) {
            print "&nbsp;&nbsp;<a href=\"javascript:dataKeep('remext');\">"
                . "Remove the above pointer</a>";

        }
        print "</font></td></tr>";
    }
    $e++;
} while($e < $ext);


$e = 0;
do{
    print "<tr>"
        . "<td width=\"25%\" valign=\"top\">";
    if($e == 0) {
        print "<a href=\"javascript:help('pointers');\">"
            . "<font color=\"#000000\" size=\"2\" "
            . " face=\"Arial, Helvetica, sans-serif\">"
            . "<b>Internal Pointers:</b></font></A><br/>"
            . "<font size=\"1\">Optional</font>";
    }
    print "</td>"
        . "<td width=\"75%\">";
    if($intpoint != 0) {
        print "<select name=\"intpointer" . $e . "\">"
            . "<option value=\"\">--- Link to a publication ---</option>";
        $pubs_query = "SELECT title, pub_id FROM publication";
        $pubs_result = mysql_query($pubs_query) or die("Query failed : " . mysql_error());
        while ($pubs_line = mysql_fetch_array($pubs_result, MYSQL_ASSOC)) {
            print "<option value=\"" . $pubs_line[pub_id] . "\"";
            $pointer = "intpointer".$e;
            if (stripslashes($$pointer) == $pubs_line[pub_id])
                print " selected";
            $tempstring = stripslashes($pubs_line[title]);
            if(strlen($tempstring) > 70) {
                $tempstring = substr($tempstring,0,67)."...";
            }
            print ">" . $tempstring . "</option>";
        }
        print "</select>";
    }
    else {
        print "<a href=\"javascript:dataKeep('addint');\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"3\">"
            . "Add an internal pointer</font></a>";
    }
    print "</td></tr>";

    if($e == ($intpoint-1)) {
        print "<tr><td></td><td valign=\"top\">&nbsp;&nbsp;"
            . "<a href=\"javascript:dataKeep('addint');\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
            . "Add another internal pointer</a>";
        if ($intpoint > 0) {
            print "&nbsp;&nbsp;"
                . "<a href=\"javascript:dataKeep('remint');\">"
                . "Remove the above pointer</a>";
        }
        print "</font></td></tr>";
    }
    $e++;
}
while($e < $intpoint);
?>

<!-- Keywords -->
<? if($keywordsSubmitted){
    for($q=0; $q < $keywordcount; $q++)
        if($keyword[$q] != "")
            $keywords .= $keyword[$q]."; ";
    $keywords = trim($keywords);
}?>
<tr>
<td width="25%" valign="top"><A NAME=keywords></a><A href="javascript:help('keywords');"><font color="#000000" size="2" face="Arial, Helvetica, sans-serif"><b>Keywords: </b></font></A></td>
<td width="75%"><input type="text" name="keywords" size="60" maxlength="250" value="<? print stripslashes($keywords); ?>">&nbsp;&nbsp;<font face="Arial, Helvetica, sans-serif" size="1">seperate by semi-colon (;)</font>
<BR>&nbsp;&nbsp;<a href="javascript:dataKeepPopup('keywords.php');"><font face="Arial, Helvetica, sans-serif" size="1">Select from a list of previously used keywords</font></a>
</td>
</tr>

<!-- Additional info fields  -->
<? for ($i = 0; $i < count($info); $i++) {
    $varname = strtolower($info[$i]);
    if (($$varname == "")&&($pub_id != NULL)) {
        $infoID = get_info_id($category_id, $varname);
        if ($varname != "") {
            $varname = str_replace(" ", "", $varname);

            // If the user didn't enter anything into the form,
            // use the value we pulled from the databasefo_id($category_id, $info[$i]);
		    $$varname = get_info_field_value($pub_id, $category_id, $infoID);
		}
        ?>
            <tr>
                 <td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b><? print $info[$i] ?>: </b></font><a href="../help.php" target="_blank" onClick="window.open('../help.php?helpcat=Additional Fields', 'Help', 'width=400,height=400'); return false"><img src="./question_mark_sm.JPG" border="0" alt="help"></a></td>
                 <td width="75%"><input type="text" name="<? print $varname ?>" size="50" maxlength="250" value="<? print stripslashes($$varname); ?>"></td>
                 </tr>
                 <? 	  }
}
?>


<!-- Date Published -->
<tr>
<td width="25%" valign="top"><A href="javascript:help('date_published');"><font color="#000000" size="2" face="Arial, Helvetica, sans-serif"><b>Date Published: </b></font></a></td>

<td width="75%">
              <?  if ($month == "")
     generate_select_month("month", 1, 12);
else
    generate_select_month("month", 1, 12, $month);
?>&nbsp;&nbsp;<?
if ($day == "")
    generate_select_date("day", 1, 31);
else
    generate_select_date("day", 1, 31, $day);
?>&nbsp;&nbsp;<?
$today = getdate();
if ($year == "")
    generate_select_date("year", 1960, $today[year]);
else 	{
    generate_select_date("year", 1960, $today[year], $year);
}
?>
</td>
</tr>
<!-- STEP 2 -->
<tr>
<td colspan="2"><hr></td>
</tr>
<tr>
<td colpsan="2"><a name="STEP2"><div id="emph">Step 2:</div></a></td>

</tr>

<!-- The Paper -->
<tr>
<td width="25%" valign="top"><A href="javascript:help('paper');">
    <font color="#000000" size="2" face="Arial, Helvetica, sans-serif">
    <b>Paper: </b></a></font></td>
<?
if ($edit) {
    print "<td width=\"75%\">" . $paper . "&nbsp; &nbsp; &nbsp;"
    . "<a href=\"javascript:dataKeepPopupWithID('change_paper.php',"
    . $pub_id . ");\">"
    . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
    . "Change Paper</font></a>"
    . "</td>";
}
else {
    print "<td width=\"75%\">"
        . "<input type=\"radio\" name=\"nopaper\" value=\"false\" ";
    if(($nopaper == "false")||($nopaper == ""))
        print "checked";
    print "><input type=\"file\" name=\"uploadpaper\" size=\"60\" "
        . " maxlength=\"250\"><br/>"
        . "<input type=\"radio\" name=\"nopaper\" value=\"true\" ";
    if($nopaper == "true")
        print "checked";
    print "> No paper at this time."
        . "</td>";
}

print "</tr>";
?>

<!-- Additional Materials -->
<?
if($numMaterials > 0) {
    print "<tr><td width=\"25%\" valign=\"top\">"
        . "<a href=\"javascript:help('additional_materials');\">"
        . "<font color=\"#000000\" size=\"2\""
        . " face=\"Arial, Helvetica, sans-serif\">"
        . "<b>Additional Materials: </b></a></font></td>"
        . "</tr>\n";
}

for ($i = 0; $i < $numMaterials; $i++) {
    print "<tr>";

    if ($i < $dbMaterials) {
        $add_info_array = get_additional_material($pub_id, $i);
        print "<td width=\"25%\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\" "
            . "color=\"#990000\"><b>";
        if($add_info_array[1] != "")
            print $add_info_array[1];
        else
            print "Additional Material " . ($i+1);
        print ": </b></font></td>"
            . "<td width=\"75%\">";
        print $add_info_array[0];
        print "&nbsp; &nbsp; &nbsp; "
            . "<a href=\"javascript:\" "
            . " onClick=\"javascript:window.open('";
        print "delete.php?info=" . $pub_id . "/" . $i . "&confirm=false"
            ."','deleteadd','width=200,height=200,directories=no,location=no,"
            . "menubar=no,scrollbars=no,status=no,toolbar=no,"
            . "resizable=no')\">"
            . "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
            . "Delete</font></a> </td>";
    }
    else {
        print "<td width=\"25%\"><input type=\"text\" name=\"type" . $i
            . "\" size=\"17\" maxlength=\"250\" "
            . "value=\"Additional Material ";
        print $i+1;
        print "\"><b>:</b></td>"
            . "<td width=\"75%\">"
            . "<input type=\"file\" name=\"uploadadditional" . $i
            . "\" size=\"50\" maxlength=\"250\"></td>"
            . "</tr>\n";
    }

}
?>
<tr><td></td>
<td>
<?
if($numMaterials == "") {
    $numMaterials = 0;
}

print "<input type=\"hidden\" name=\"numMaterials\" value=\"" . $numMaterials
. "\">"
. "&nbsp;&nbsp;"
. "<a href=\"javascript:dataKeep('addnum');\">"
. "<font face=\"Arial, Helvetica, sans-serif\" size=\"1\">"
. "Add other material</a>";

if ($numMaterials > 0) {
    print "&nbsp;&nbsp;<a href=\"javascript:dataKeep('remnum');\">"
        . "Remove this material</a>";
}
print "</font></td></tr>"
. "<tr><td colspan=\"2\"><hr></td></tr>";
?>

<!-- Buttons to control what we do with the data -->
     <tr>
     <td width="25%">&nbsp;</td>
     <td width="75%" align="left">

    <? if ($edit) { ?>
                    <input type="SUBMIT" name="Save" value="<? if ($edit) print "Accept Modifications"; else print "Accept New Publication"; ?>" class="text" onClick="return verify(1);">
                    <input type="RESET" name="Clear" value="Reset" class="text" onClick="refresher();">
                    <? } else { ?>
    <input type="SUBMIT" name="Submit" value="Add Publication" class="text" onClick="return verify(0);">
        <input type="RESET" name="Clear" value="Clear" class="text" onClick="resetAll();">
		<? }  ?>

		&nbsp;&nbsp;

<!-- This will clear out all the values in all fields -->

<!-- Reset will set everything back to what they are in the DB (not implemented) -->
<!-- <input type="RESET" name="Reset" value="Reset" class="text"></td> -->
</tr>
</table>
</form>
<? back_button(); ?>
</body>
</html>

<?
  /* Free resultset */
mysql_free_result($cat_result);
mysql_free_result($info_result);
mysql_free_result($author_result);

/* Closing connection */
disconnect_db($link);

?>
