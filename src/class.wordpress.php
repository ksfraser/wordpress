<?php

/*
 *	This idea is when a different part of the app inserts a record
 *	into the Master table, we would then post the data to Wordpress
 *	Watches for:
 *		NOTIFY_MASTER_INSERT
 *		NOTIFY_MASTER_UPDATE
 *		NOTIFY_WORDPRESS_INSERT	//NEW 201704 - send to remote WORDPRESS when local is updated
 *
 *	Should also figure out how to update a wordpress page so that
 *	udpates to the Master table are also updated to Wordpress
 *
 *	Triggers:
 *		NOTIFY_WORDPRESS_IMAGE_UPLOADED
 *		NOTIFY_WORDPRESS_POST
 */

include_once("class-IXR.php");
require_once( dirname( __FILE__ ) . '/../../class.base.php' );

//class post2wordpress extends controller {
class post2wordpress extends base {
	var $existing_categories;
	var $xmlurl;
	var $user;
	var $pass;
	var $imgdir;
	var $imgtype;
	var $imgurl;			//URL on wordpress of image we uploaded
	var $master_class;		//The class holding the data that was inserted, ready to be posted
	var $data;			//data passed in by notifyer
	var $title;
	var $body;
	var $xmlclient;			//IXR client
	var $imgname;			//The name of the image file proper (no path info)
	var $filename;			//Image filename
	var $author;			//Userid of author
	var $categories;
	var $already_posted;		//Notify is looping.
	var $upc;			//Should we update Wordpress if MASTER is updated?
	var $postedtowordpress;
	var $wp_post_object = array();

	function __construct( $dispatcher )
	{
		parent::__construct( $dispatcher );
		$this->ObserverRegister( $this, "NOTIFY_MASTER_INSERT", 1 );
		$this->ObserverRegister( $this, "NOTIFY_MASTER_UPDATE", 1 );
		$this->ObserverRegister( $this, "NOTIFY_WORDPRESS_INSERT", 1 );
		$this->ObserverRegister( $this, "NOTIFY_INSERT_UPC", 1 );
		//$this->ObserverRegister( $this, "NOTIFY_MASTER_UPDATE", 1 );

		//We need an admin screen for setting URL, user, pass, etc
		//In the mean time
		$this->xmlurl = 'http://andyapp.ksfraser.com/wordpress/xmlrpc.php';
		$this->user = "admin";
		$this->pass = "m1l1ce";
		$this->imgdir = "../../../../../../images";
		$this->imgtype="jpg";
		$this->already_posted = FALSE;
	}
 	function notified( $class, $event, $msg )
        {
		//echo __FILE__ . ":" . __LINE__ . "<br />\n";
		//var_dump( $class );
                if( "NOTIFY_MASTER_INSERT" == $event OR "NOTIFY_INSERT_UPC" == $event OR "NOTIFY_WORDPRESS_INSERT" == $event )
                //if( "NOTIFY_MASTER_INSERT" == $event )
                {
			//echo "post2wordpress notified " . $event . "<br />";
			if( !isset( $this->xmlclient ) )
			{
				$this->xmlclient = new IXR_Client( $this->xmlurl );
			}
			$this->master_class = $class;
			$this->data = $msg;
			if( $this->already_posted == FALSE )
			{
				$ret = $this->createPageData();
				if( $ret )
					$this->postPageData();
			}
		}
                if( "NOTIFY_MASTER_UPDATE" == $event )
                {
			echo "post2wordpress notified " . $event . "<br />";
			if( !isset( $this->xmlclient ) )
			{
				$this->xmlclient = new IXR_Client( $this->xmlurl );
			}
			$this->master_class = $class;
			$this->data = $msg;
			if( !isset( $this->upc ) )
			{
				$this->createPageData();
				$this->updatePageData();
			}
		}
	}
	function updatePageData()
	{
	}
	function updateMaster( $p_id )
	{
		$this->master_class->postedtowordpress = $p_id;
		$this->postedtowordpress = $p_id;
		$_SESSION["postedtowordpress"] = $p_id;
		$this->already_posted = TRUE;
		$this->ObserverNotify( 'NOTIFY_WORDPRESS_POST', $this->data, $this );
		$this->master_class->UpdateVAR();
	}
	function postPageData()
	{
		//echo "Posting to Wordpress\n";
		$p_id = $this->createPost( $this->title, $this->body, $this->categories, 1, $this->existing_categories);
		if( $p_id == "573" )
		{
			//Title already exists
			$this->ObserverNotify( 'NOTIFY_WORDPRESS_ALREADY_EXISTS', $this->data, $this );
			$this->already_posted = TRUE;
		        echo "Wordpress says title " . $this->title . "already exists<br />\n";
				//Should get post's id and update master
		}
		else
		{
		        echo "Post ID " . $p_id . " for title " . $this->title . "<br />\n";
		        if( is_array( $p_id ) )
		        {
		                //Posting failed - returned an array (fault codes)
		        }
		        else
		        {
	/*
		                $M = new master();
		                $M->fieldspec['Title']['prikey'] = 'N';
		                $M->postedtowordpress = $p_id;
		                $M->upc = $this->data['upc'];
		                //$M->Title = $this->data['Title']; //' in the title seems to blow this up :(
		                $M->UpdateVAR();
				$this->already_posted = TRUE;
				$this->upc = $M->upc;
				$this->ObserverNotify( 'NOTIFY_WORDPRESS_POST', $this->data, $this );
		        	unset( $M );
	*/
				$this->updateMaster( $p_id );
		        }
		}
	}
	function createPageData()
	{
		//echo __FILE__ . ":" . __LINE__ . "<br />\n";
		if( isset( $this->master_class->Title ) )
		{
			$this->wp_post_object['post_title'] = $this->master_class->Title;
			//echo __FILE__ . ":" . __LINE__ . "<br />\n";
			$this->title = $this->master_class->Title;
			$this->categories = $this->master_class->Genre . "," . $this->master_class->Media . "," . $this->master_class->year . "," . $this->master_class->mpaarating . ", " . $this->master_class->keywords . ", " . $this->master_class->author  . ", " . $this->master_class->publisher;
			//Need to look up category IDs...
			//$this->wp_post_object['post_category'] = array( 1,2 );  //wp_set_post_categories()

		        $body = "<h1>" . $this->master_class->Title . "</h1>";
			if( isset( $this->master_class->upc ) )
			{
			        $body .= "UPC: " . $this->master_class->upc . "<br />";
			}
			if( isset( $this->master_class->isbn ) )
			{
		        $body .= "ISBN: " . $this->master_class->isbn  . "<br />";
			}
			if( isset( $this->master_class->Length ) OR  isset( $this->master_class->pages )  OR isset( $this->master_class->numberofdisks ) )
			{
		        	$body .= "<h2>";
		        	$body .= "Movie Runtime or Book Page Count";
		        	$body .= "</h2>";
				if( isset( $this->master_class->Length ) )
				{
		        		if( strlen( $this->master_class->Length ) > 2 )
		        		        $body .= "Length: " . $this->master_class->Length . "<br />";
				}
				if(  isset( $this->master_class->pages )  )
				{
		        		if( strlen( $this->master_class->pages ) > 2 )
		        		        $body .= "Pages: " . $this->master_class->pages . "<br />";
				}
				if( isset( $this->master_class->numberofdisks ) )
				{
		        		if( strlen( $this->master_class->numberofdisks ) > 2 )
		        		        $body .= "Number of disks: " . $this->master_class->numberofdisks . "<br />";
				}
			}
			if( isset( $this->master_class->imdbnumber ) OR  isset( $this->master_class->azDetailPageURL )  OR isset( $this->master_class->chaptersURL ) )
			{
		        	$body .= "<h2>";
		        	$body .= "IMDB details, URL and Chapters URL";
		        	$body .= "</h2>";
				if( isset( $this->master_class->imdbnumber ) )
				{
		        		if( strlen( $this->master_class->imdbnumber ) > 2 )
		        		        $body .= "IMDB Number: " . $this->master_class->imdbnumber . "<br />";
				}
				if( isset( $this->master_class->azDetailPageURL ) )
				{
		        		if( strlen( $this->master_class->azDetailPageURL ) > 2 )
		        		        $body .= '<a href="' . $this->master_class->azDetailPageURL . '">Amazon URL</a>' . "<br />";
				}
				if( isset( $this->master_class->chaptersURL ) )
				{
		        		if( strlen( $this->master_class->chaptersURL ) > 2 )
		        		        $body .= '<a href="' . $this->master_class->chaptersURL . '">Chaptors URL</a>' . "<br />";
				}
			}
			if( isset( $this->master_class->author ) OR  isset( $this->master_class->publisher )  OR isset( $this->master_class->releasedate ) )
			{
		        	$body .= "<h2>";
		        	$body .= "Author, Publisher and Release Date";
		        	$body .= "</h2>";
				if( isset( $this->master_class->author ) )
				{
		        		if( strlen( $this->master_class->author ) > 2 )
		        		        $body .= "Author: " . $this->master_class->author . "<br />";
				}
				if( isset( $this->master_class->publisher ) )
				{
		        		if( strlen( $this->master_class->publisher ) > 2 )
		        		        $body .= "Publisher: " . $this->master_class->publisher . "<br />";
				}
				if( isset( $this->master_classR->eleasedate ) )
				{
		        		if( strlen( $this->master_class->releasedate ) > 2 )
		        		        $body .= "Release Date: " . $this->master_class->releasedate . "<br />";
				}
			}
			if( isset( $this->master_class->comments ) OR  isset( $this->master_class->summary )  OR isset( $this->master_class->userrating ) OR isset( $this->master_class->keywords ) )
			{
		        	$body .= "<h2>";
		        	$body .= "Other Details";
		        	$body .= "</h2>";
				if( isset( $this->master_class->comments ) )
				{
		        		if( strlen( $this->master_class->comments ) > 2 )
		        		        $body .= "Comments: " . $this->master_class->comments . "<br />";
				}
				if( isset( $this->master_class->summary ) )
				{
		        		if( strlen( $this->master_class->summary ) > 2 )
		        		        $body .= "Summary: " . $this->master_class->summary . "<br />";
				}
				if( isset( $this->master_class->format ) )
				{
					if( strlen( $this->master_class->format ) > 2 )
		        	 	$body .= "Format: " . $this->master_class->format . "<br />";
				}
				if( isset( $this->master_class->userrating ) )
				{
		        		if( strlen( $this->master_class->userrating ) > 2 )
		        		        $body .= "User's Rating: " . $this->master_class->userrating . "<br />";
				}
				if( isset( $this->master_class->keywords ) )
				{
		        		if( strlen( $this->master_class->keywords ) > 2 )
		        		        $body .= "Keywords: " . $this->master_class->keywords . "<br />";
				}
			}
			if( isset( $this->master_class->location ) OR  isset( $this->master_class->inventorydate ) )
			{
		        	$body .= "<h2>";
		        	$body .= "Inventory Location and Last Date";
		        	$body .= "</h2>";
		        	$body .= "Location: " . $this->master_class->location;
		        	$body .= "As of " .$this->master_class->inventorydate . "<br />";
			}
			if( isset( $this->master_class->coverimage ) )
			{
		        	$body .= "<h2>";
		        	$body .= "Cover Image";
		        	$body .= "</h2>";
		        	$this->imgname = substr( $this->master_class->coverimage, 7 ) ;
		        	$this->filename = $this->imgdir . "/" . $this->imgname;
			        $attachImage = $this->uploadImage();
			        if( !strcmp( "", $attachImage ) )
			        {
			                $body .= "<img src='$attachImage' width='256' height='256' /></a>";
			        }
			}
			$this->body = $body;
			$this->wp_post_object['post_content'] = $this->body;
			$this->wp_post_object['post_type'] = "post";
			$this->wp_post_object['post_status'] = "publish";
			$this->wp_post_object['post_author'] = 1;
			$this->wp_post_object['comment_status'] = "closed";
			$this->wp_post_object['ping_status'] = "closed";
			//$this->wp_post_object['to_ping'] = ""; //space seperated URL list
			//$this->wp_post_object['ID'] = 1; //If set, update this post
			//$this->wp_post_object['post_date'] = "20170201";
			//$this->wp_post_object['post_date_gmt'] = "20170201";
			//$this->wp_post_object['post_content_filtered'] = "";
			//$this->wp_post_object['post_excerpt'] = "";
			//$this->wp_post_object['post_password'] = ""; //string
			//$this->wp_post_object['post_parent'] = 0; //int
			//$this->wp_post_object['menu_order'] = 0; //int
			$this->wp_post_object['guid'] = $this->master_class->upc; //int  
			//$this->wp_post_object['tax_input'] = ""; //taxonomy by terms  wp_set_post_terms(int $post_id, string|array $tags = '', string $taxonomy = 'post_tag', bool $append = false)
			//$this->wp_post_object['tags_input'] = ""; //wp_set_post_tags()
			//$this->wp_post_object['meta_input'] = ""; //
			//		string post_type
			//		string post_format
			//		string post_name: Encoded URL (slug)
			//		int sticky
			//		int post_thumbnail
			//		array custom_fields
			//		    struct
			//		            string key
			//		            string value
			//		    struct terms: Taxonomy names as keys, array of term IDs as values.
			//		    struct terms_names: Taxonomy names as keys, array of term names as values.
			//		    struct enclosure
			//		        string url
			//		        	int length
			//		        	string type

			$included = include_once( 'wp-includes/post.php' );
			if( $included )
				wp_insert_post( $this->wp_post_object );
			return TRUE;
		}
		else
		{
			echo __FILE__ . ":" . __LINE__ . "<br />\n";
			var_dump( $this->master_class );
			return FALSE;
		}

	}

	
	function createPost( $title, $body, $categories, $publishDraft = 0, $existing_categories = array() )
	{
	    /*$categories is a list seperated by ,*/
	    $cats = preg_split('/,/', $categories, -1, PREG_SPLIT_NO_EMPTY);
/*
	    foreach ($cats as $key => $data){
		if( !isset( $existing_categories[$data] ) )
		{
	        	$this->createCategory($data,"","");
			$this->existing_categories[$data] = $data;
		}
		else
		{
			//category already exists
		}
	    }
*/
	    $data = array(
	        'title' => $title,
	        'description' => $body,
	        'dateCreated' => (new IXR_Date(time())),
	        //'dateCreated' => (new IXR_Date($time)),  //publish in the future
	        'mt_allow_comments' => 0, // 1 to allow comments
	        'mt_allow_pings' => 0,// 1 to allow trackbacks
	        'categories' => $cats,
	    );
/*
	'wp.newPost'
	int blog_id
	string username
	string password
	struct content
		string post_type
		string post_status
		string post_title
		int post_author
		string post_excerpt
		string post_content
		datetime post_date_gmt | post_date
		string post_format
		string post_name: Encoded URL (slug)
		string post_password
		string comment_status
		string ping_status
		int sticky
		int post_thumbnail
		int post_parent
		array custom_fields
		    struct
		        string key
		        string value
		struct terms: Taxonomy names as keys, array of term IDs as values.
		struct terms_names: Taxonomy names as keys, array of term names as values.
		struct enclosure
		    string url
		    int length
		    string type
*/
	    $published = $publishDraft; // 0 - draft, 1 - published
	    $res = $this->xmlclient->query('metaWeblog.newPost', '', $this->user, $this->pass, $data, $published);
	     $returnInfo = $this->xmlclient->getResponse();
		//post_id always coming back as 3
		//var_dump( $returnInfo );
	     return $returnInfo;     //return the url of the posted Image
	}
	
	function uploadImage()
	{
	        $fs = filesize($this->filename);   
	        $file = fopen($this->filename, 'rb');  //Getting errors about invalid stream.  Should put in a check
	        $filedata = fread($file, $fs);    
	        fclose($file); 
	
	        $data = array(
	            'name'  => $this->imgname, 
	            'type'  => 'image/' . $this->imgtype,  
	            'bits'  => new IXR_Base64($filedata), 
	            false //overwrite
	        );
	        $res = $this->xmlclient->query('wp.uploadFile',1, $this->user, $this->pass, $data);
	
	     $returnInfo = $this->xmlclient->getResponse();
		if( isset( $returnInfo['url'] ) )
		{
			$this->imgurl = $returnInfo['url'];
			$this->ObserverNotify( 'NOTIFY_WORDPRESS_IMAGE_UPLOADED', $this->imgurl , $this);
	     		return $returnInfo['url'];     //return the url of the posted Image
		}
		else
		{
			$this->imgurl = "";
			return "";
		}
	}
	
	function createCategory( $catName, $catSlug, $catDescription )
	{
	    $res = $this->xmlclient->query('wp.newCategory', '', $this->user, $this->pass, 
	        array(
	            'name' => $catName,
	            'slug' => $catSlug,
	            'parent_id' => 0,
	            'description' => $catDescription
	        )
	    );
		return $res;
	}
	
	function findAuthor(){
		$this->xmlclient->query('wp.getAuthors ', 0, $this->user, $this->pass);
		$authors = $this->xmlclient->getResponse();
	    	foreach ($authors as $key => $data){
			//  echo $authors[$key]['user_login'] . $authors[$key]['user_id'] ."</br>";
	        	if($authors[$key]['user_login'] == $this->author){
	            		return $authors[$key]['user_id'];
	        	}
	    	}
	   	return "not found";
	}
	
	function findCategories(){
		$this->xmlclient->query('wp.getCategories', 0, $this->user, $this->pass);
		$categories = $this->xmlclient->getResponse();
	    	foreach ($categories as $key => $data){
			//  echo $categories[$key]['user_login'] . $categories[$key]['user_id'] ."</br>";
	        	if($categories[$key]['user_login'] == $this->categories){
	            		return $categories[$key]['user_id'];
	        	}
	    	}
	   	return "not found";
	}
	
	function getCategories(){
		$this->xmlclient->query('wp.getCategories', 0, $this->user, $this->pass);
		$categories = $this->xmlclient->getResponse();
		$cats = array();
	    	foreach ($categories as $key => $data){
			//  echo $categories[$key]['user_login'] . $categories[$key]['user_id'] ."</br>";
			//$cats[$data] = $data;
	    	}
	   	return $cats;
	}
	
}

?>
