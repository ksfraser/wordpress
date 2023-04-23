<?php

/**********************************************************************
 *
 *	This file is a script that checks in the MASTER table for
 *	titles that don't have a article id (postedtowordpress) set.
 *	It then sends each of those to andyapp and then updates MASTER
 *	with the article id.
 *
 * ********************************************************************/

//$localuse = $_SERVER['SERVER_NAME'] == "127.0.0.1" || $_SERVER['SERVER_NAME'] == "localhost" || substr($_SERVER['SERVER_NAME'],0,3) == "192";
//ini_set('display_errors', $localuse);
ini_set('display_errors', 'On');
error_reporting(E_ALL|E_STRICT);

require_once( 'post2wordpress.php' );
/*
	function createPost( $client, $username, $password, $title, $body, $categories, $publishDraft = 0)
	function uploadImage($client, $username, $password, $filename, $name, $type)
	function createCategory( $client, $username, $password, $catName, $catSlug, $catDescription )
	function getCategories( $client, $username, $password)
*/

echo "1\n";

define( "XMLURL", 'http://andyapp.ksfraser.com/wordpress/xmlrpc.php' );
//define( "XMLURL", 'http://127.0.0.1/kallimachos/wordpress/xmlrpc.php' );
define( "USER", "admin" );
define( "PASS", "m1l1ce" );
require_once("wordpress/wp-includes/class-IXR.php");
$client = new IXR_Client( XMLURL );
echo "2\n";

//if (!$client->query('wp.getCategories','', USER,PASS ) ) {
//	die('An error occurred - '.$client->getErrorCode().":".$client->getErrorMessage());
//}
//$response = $client->getResponse();

$username = "admin";
$password = "m1l1ce";
//$username = USER;
//$password = PASS;
$existing_categories = array();
//$existing_categories = getCategories( $client, $username, $password); //array of the existing categories
//echo "Tried getting categories\n";
//var_dump( $existing_categories );

/*******************************************
*
*	Build the list of media and
*	create posts
*
*******************************************/
//require_once( 'include_all.php' );
echo "require Local\n";
	require_once( 'local.php' );
echo "INITed\n";
	Local_Init();

echo "require generictable\n";
require_once( 'data/generictable.php');
echo "require MASTER class\n";
require_once( 'model/master.class.php' );
$master = new master();
$master->where = "postedtowordpress = '0'";
$master->limit = 500;
$master->Select();
echo "3\n";
$dir = "/mnt/2/development/var/www/html/kallimachos/images";
$type="jpg";
echo "Rows returned: " . $master->rowcount . "\n";
$published = 0;
foreach( $master->resultarray as $row )
{
	echo "LOOP\n";
	$title = $row['Title'];
	$categories = $row['Genre'] . "," . $row['Media'] . "," . $row['year'] . "," . $row['mpaarating'] . ", " . $row['keywords'];
	$body = "<h1>" . $row['Title'] . "</h1>";
	$body .= "UPC: " . $row['upc'] . "<br />";
	$body .= "ISBN: " . $row['isbn']  . "<br />";
	$body .= "<h2>";
	$body .= "Movie Runtime or Book Page Count";
	$body .= "</h2>";
	if( strlen( $row['Length'] ) > 2 )
		$body .= "Length: " . $row['Length'] . "<br />";
	if( strlen( $row['pages'] ) > 2 )
		$body .= "Pages: " . $row['pages'] . "<br />";
	if( strlen( $row['numberofdisks'] ) > 2 )
		$body .= "Number of disks: " . $row['numberofdisks'] . "<br />";
	$body .= "<h2>";
	$body .= "IMDB details, URL and Chapters URL";
	$body .= "</h2>";
	if( strlen( $row['imdbnumber'] ) > 2 )
		$body .= "IMDB Number: " . $row['imdbnumber'] . "<br />";
	if( strlen( $row['azDetailPageURL'] ) > 2 )
		$body .= '<a href="' . $row['azDetailPageURL'] . '">Amazon URL</a>' . "<br />";
	if( strlen( $row['chaptersURL'] ) > 2 )
		$body .= '<a href="' . $row['chaptersURL'] . '">Chaptors URL</a>' . "<br />";
	$body .= "<h2>";
	$body .= "Author, Publisher and Release Date";
	$body .= "</h2>";
	if( strlen( $row['author'] ) > 2 )
		$body .= "Author: " . $row['author'] . "<br />";
	if( strlen( $row['publisher'] ) > 2 )
		$body .= "Publisher: " . $row['publisher'] . "<br />";
	if( strlen( $row['releasedate'] ) > 2 )
		$body .= "Release Date: " . $row['releasedate'] . "<br />";
	$body .= "<h2>";
	$body .= "Other Details";
	$body .= "</h2>";
	if( strlen( $row['comments'] ) > 2 )
		$body .= "Comments: " . $row['comments'] . "<br />";
	if( strlen( $row['summary'] ) > 2 )
		$body .= "Summary: " . $row['summary'] . "<br />";
	if( strlen( $row['format'] ) > 2 )
		$body .= "Format: " . $row['format'] . "<br />";
	if( strlen( $row['userrating'] ) > 2 )
		$body .= "User's Rating: " . $row['userrating'] . "<br />";
	if( strlen( $row['keywords'] ) > 2 )
		$body .= "Keywords: " . $row['keywords'] . "<br />";
	$body .= "<h2>";
	$body .= "Inventory Location and Last Date";
	$body .= "</h2>";
	$body .= "Location: " . $row['location'];
	$body .= "As of " .$row['inventorydate'] . "<br />";
	$body .= "<h2>";
	$body .= "Cover Image";
	$body .= "</h2>";
	//$body .= $row['coverimage'] . "<br />";	//images/12345.jpg
	$name = substr( $row['coverimage'], 7 ) ;
	//$name = strstr( $row['coverimage'], "/" ) ;
	$filename = $dir . "/" . $name;

	$attachImage = uploadImage($client, $username, $password, $filename, $name, $type);
	if( !strcmp( "", $attachImage ) )
	{
		$body .= "<img src='$attachImage' width='256' height='256' /></a>";
	}
echo "Posting to Wordpress\n";
	$p_id = createPost( $client, $username, $password, $title, $body, $categories, 1, $existing_categories);
	echo "Post ID " . $p_id . " for title " . $title . "\n";
	if( is_array( $p_id ) )
	{
		//Posting failed - returned an array (fault codes)
	}
	else
	{
		$M = new master();
		$M->fieldspec['Title']['prikey'] = 'N';
		$M->postedtowordpress = $p_id; 
		$M->upc = $row['upc'];
		//$M->Title = $row['Title']; //' in the title seems to blow this up :(
		$M->UpdateVAR();
	}
	unset( $M );
	sleep( 3 ); //Help lower the TOP load numbers
	//sleep( 30 ); //Help lower the TOP load numbers
	$published++;
	//var_dump( $body );
	//exit();
}

echo "Published: " . $published . "\n";






?>
