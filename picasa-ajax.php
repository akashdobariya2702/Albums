<?php
// we need session for stor the picasa token value.
session_start();

require 'vendor/autoload.php';
require_once("config.php");

if(isset($_POST['check_picasa_token'])){
    if(isset($_SESSION['sessionToken']))
        echo "true";
    else
        echo "false";
}

// include zend framework library to use picasa api
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

/**
* Returns the full URL of the current page, based upon env variables
*
* Env variables used:
* $_SERVER['HTTPS'] = (on|off|)
* $_SERVER['HTTP_HOST'] = value of the Host: header
* $_SERVER['SERVER_PORT'] = port number (only used if not http/80,https/443)
* $_SERVER['REQUEST_URI'] = the URI after the method of the HTTP request
*
* @return string Current URL
*/
function getCurrentUrl()
{
    global $_SERVER;
 
    /**
     * Filter php_self to avoid a security vulnerability.
     */
    $php_request_uri = htmlentities(substr($_SERVER['REQUEST_URI'], 0,
    strcspn($_SERVER['REQUEST_URI'], "\n\r")), ENT_QUOTES);
 
    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    $host = $_SERVER['HTTP_HOST'];
    if ($_SERVER['SERVER_PORT'] != '' &&
        (($protocol == 'http://' && $_SERVER['SERVER_PORT'] != '80') ||
        ($protocol == 'https://' && $_SERVER['SERVER_PORT'] != '443'))) {
            $port = ':' . $_SERVER['SERVER_PORT'];
    } else {
        $port = '';
    }
    return $protocol . $host . $port . $php_request_uri;
}
 
/**
* Returns the AuthSub URL which the user must visit to authenticate requests
* from this application.
*
* Uses getCurrentUrl() to get the next URL which the user will be redirected
* to after successfully authenticating with the Google service.
*
* @return string AuthSub URL
*/
function getAuthSubUrl()
{
    $next = getCurrentUrl();
    $scope = 'http://picasaweb.google.com/data';
    $secure = false;
    $session = true;
    return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure,
        $session);
}
 
/**
* Returns a HTTP client object with the appropriate headers for communicating
* with Google using AuthSub authentication.
*
* Uses the $_SESSION['sessionToken'] to store the AuthSub session token after
* it is obtained. The single use token supplied in the URL when redirected
* after the user succesfully authenticated to Google is retrieved from the
* $_GET['token'] variable.
*
* @return Zend_Http_Client
*/
function getAuthSubHttpClient()
{
    global $_SESSION, $_GET;
    if (!isset($_SESSION['sessionToken']) && isset($_GET['token'])) {
        $_SESSION['sessionToken'] =
            Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
    }
    $client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['sessionToken']);
    return $client;
}
 
/**
* Create a new instance of the service, redirecting the user
* to the AuthSub server if necessary.
*/
//$service = new Zend_Gdata_Photos(getAuthSubHttpClient());

// uploading photo into album.
function uploadPhotoIntoAlbum($service, $albumName, $fileName, $albumID) {
    $gp = $service;
    $username = "default";
    $filename = $fileName;//"download/2.jpg";
    $photoName = $albumName." Moved from Facebook";
    $photoCaption = "Picasa Album";
    $photoTags = "beach, sunshine";

    // We use the albumId of 'default' to indicate that we'd like to upload
    // this photo into the 'drop box'.  This drop box album is automatically 
    // created if it does not already exist.
    $albumId = $albumID;//"default";

    $fd = $gp->newMediaFileSource($filename);
    $fd->setContentType("image/jpeg");

    // Create a PhotoEntry
    $photoEntry = $gp->newPhotoEntry();

    $photoEntry->setMediaSource($fd);
    $photoEntry->setTitle($gp->newTitle($photoName));
    $photoEntry->setSummary($gp->newSummary($photoCaption));

    // add some tags
    $keywords = new Zend_Gdata_Media_Extension_MediaKeywords();
    $keywords->setText($photoTags);
    $photoEntry->mediaGroup = new Zend_Gdata_Media_Extension_MediaGroup();
    $photoEntry->mediaGroup->keywords = $keywords;

    // We use the AlbumQuery class to generate the URL for the album
    $albumQuery = $gp->newAlbumQuery();

    $albumQuery->setUser($username);
    $albumQuery->setAlbumId($albumId);
    
    // We insert the photo, and the server returns the entry representing
    // that photo after it is uploaded
    $insertedEntry = $gp->insertPhotoEntry($photoEntry, $albumQuery->getQueryUrl());
}

// each file will upload
function uploadingToPicasa($service, $album_name, $path, $albumID)
{
    //ini_set('max_execution_time', 500); // set intialization time

    $objects = scandir($path);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") 
       {
            $ext = pathinfo($object, PATHINFO_EXTENSION);
            $validExtension = array("jpg", "jpeg", "png", "gif");

            if(in_array($ext, $validExtension))
            {
               uploadPhotoIntoAlbum($service, $album_name, $path."/".$object, $albumID); 
            }
       }
     }
     reset($objects);
}

//when the page call or load this function we need to load first.
if(isset($_POST['album']) && $_POST['album'] != "")
{
    $client = getAuthSubHttpClient();

    $directories = glob($_POST['album'] . '/*' , GLOB_ONLYDIR); // scan only directories.

    //ini_set('max_execution_time', 500); // set intialization time

    $urls = "";
        if(count($directories) == 0)
        {
            //no sub-directorie
            $album_name = explode("/", $_POST['album']);
            $album_name = end($album_name);

            //create album in picasa.
            $service = new Zend_Gdata_Photos($client);
            $entry = new Zend_Gdata_Photos_AlbumEntry();
            $entry->setTitle($service->newTitle($album_name));
            $value = $service->insertAlbumEntry($entry);
            $albumID = $value->getGphotoId(); // Album ID
            
            $links = $value->getLink(); // list of links
            $href = $links[1]->getHref();
            $urls.="<br><a href=\"".$href."\" target=\"_blank\">".$album_name."</a>";

            //process for upload photos on albums.
            uploadingToPicasa($service, $album_name, $_POST['album'], $albumID);


        }
        else
        {
            //have sub-directories
            for($i=0; $i<count($directories); $i++)
            {
                $path = $directories[$i];
                $album_name = explode("/", $path);
                $album_name = end($album_name);

                //create album in picasa.
                $service = new Zend_Gdata_Photos($client);
                $entry = new Zend_Gdata_Photos_AlbumEntry();
                $entry->setTitle($service->newTitle($album_name));
                $value = $service->insertAlbumEntry($entry);
                $albumID = $value->getGphotoId(); // Album ID

                $links = $value->getLink(); // list of links
                $href = $links[1]->getHref();
                $urls.="<br><a href=\"".$href."\" target=\"_blank\">".$album_name."</a>";

                //process for upload photos on albums.
                uploadingToPicasa($service, $album_name, $path, $albumID);
                
            }
            
        }

        $sendgrid = new SendGrid($SEND_GRID_ID, $SEND_GRID_PASSWORD);
        $message = new SendGrid\Email();
        $message->addTo($_SESSION['email'])->
                  setFrom("album@album123.com")->
                  setSubject("Your Facebook Album Moved Successfully")->
                  setText("Facebook Album")->
                  setHtml("<p>Your Facebook Album Moved Successfully.</p><p>Albums Moved Links</p><p>".$urls."</p>");
        $response = $sendgrid->send($message);

        echo "Album Moved Successfully<br>".$urls;
}

?>