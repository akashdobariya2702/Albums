<?php
// for store the tocken value in session.
session_start();
//print_r($_SESSION);
require_once("config.php");
 
require_once( 'lib/Facebook/HttpClients/FacebookHttpable.php' );
require_once( 'lib/Facebook/HttpClients/FacebookCurl.php' );
require_once( 'lib/Facebook/HttpClients/FacebookCurlHttpClient.php' );
 
require_once( 'lib/Facebook/Entities/AccessToken.php' );
require_once( 'lib/Facebook/Entities/SignedRequest.php' );
 
// other files remain the same
require_once( 'lib/Facebook/FacebookSession.php' );
require_once( 'lib/Facebook/FacebookRedirectLoginHelper.php' );
require_once( 'lib/Facebook/FacebookRequest.php' );
require_once( 'lib/Facebook/FacebookResponse.php' );
require_once( 'lib/Facebook/FacebookSDKException.php' );
require_once( 'lib/Facebook/FacebookRequestException.php' );
require_once( 'lib/Facebook/FacebookOtherException.php' );
require_once( 'lib/Facebook/FacebookAuthorizationException.php' );
require_once( 'lib/Facebook/GraphObject.php' );
require_once( 'lib/Facebook/GraphSessionInfo.php' );
require_once( 'lib/Facebook/FacebookSignedRequestFromInputHelper.php' );
require_once( 'lib/Facebook/FacebookJavaScriptLoginHelper.php' );
require_once( 'lib/Facebook/GraphUser.php' );

 
// path of these files have changes
use Facebook\HttpClients\FacebookHttpable;
use Facebook\HttpClients\FacebookCurl;
use Facebook\HttpClients\FacebookCurlHttpClient;
 
use Facebook\Entities\AccessToken;
use Facebook\Entities\SignedRequest;
 
// other files remain the same
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\FacebookOtherException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphSessionInfo;
use Facebook\FacebookSignedRequestFromInputHelper;
use Facebook\FacebookJavaScriptLoginHelper;
use Facebook\GraphUser;

// set facebook app id and app secret key.
FacebookSession::setDefaultApplication($APP_ID, $APP_SECRET);

// if POST with facebook_token then we need to store in the session for use it in other events.
if(isset($_POST['facebook_token']) && $_POST['facebook_token'] != "")
{
  // storing the token value in session
  $_SESSION['facebook_token'] = $_POST['facebook_token'];
  return true;
}

// destroying the session when user logout.
if(isset($_POST['facebook_token_destroy']) && $_POST['facebook_token_destroy'] != "")
{
  session_destroy();
  return true;
} 

// if facebook_token is not set then we have to stop the script. otherwise script will not work.
if(!isset($_SESSION['facebook_token']))
{
  die();
}

// get token for make some event.
$session = new FacebookSession($_SESSION['facebook_token']);

// remove all-sub directories and files.
function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
}

// make the .ZIP folder with sub files and folders
function Zip($source, $destination)
{    
    if (!extension_loaded('zip') || !file_exists($source)) {     
        return false;     
    }       

    $zip = new ZipArchive();    
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {    
        return false;    
    }    

    if (is_dir($source) === true)      
    {      
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);        

        foreach ($files as $file)     
        {      
            $file = str_replace('\\', '/', $file);     

            // Ignore "." and ".." folders        
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )        
                continue;    

            if (is_dir($file) === true)    
            {    
                $zip->addEmptyDir(str_replace($source . '/', '', $file));    
            }    
            else if (is_file($file) === true)    
            {    
                $zip->addFile($file, str_replace($source . '/', '', $file));      
            }       
        }      
    }
    else if (is_file($source) === true)    
    {    
        $zip->addFromString(basename($source), file_get_contents($source));    
    }    

    return $zip->close();    
}

// download the album using album id and it will copy image into folder(path) 
function downloadAlbum($id, $path)
{
  global $session;
  $data = "";
  try {
    ini_set('max_execution_time', 300);
    $request = new FacebookRequest($session, "GET", "/".$id."/photos?fields=source");
    $response = $request->execute();
    $graphObject = $response->getGraphObject();

    $album =  $graphObject->getProperty('data');

    $album_data = $album->asArray();
    $fileName = 1;
    foreach($album_data as $row){
        $file = (array)$row;
        $ext = pathinfo($file['source'], PATHINFO_EXTENSION);
        if(strpos($ext,'?') !== false)
          $ext = substr($ext, 0, strpos($ext, "?"));
        copy($file['source'], $path."/".$fileName.".".$ext);
        $data.=$fileName.".".$ext."\n";
        $fileName++;
    }
    return true;

  } catch (FacebookRequestException $ex) {
    echo $ex->getMessage();
  } catch (\Exception $ex) {
    echo $ex->getMessage();
  }
}

// if AlbumId set then it will allow to enter.
if(isset($_POST['AlbumId']) && $_POST['AlbumId'] != "")
{
  //ini_set('max_execution_time', 1000);
  $albumList = explode(",", $_POST['AlbumId']); // make the array of AlbumId.
  $sizeArray = count($albumList); // album length.
  if($sizeArray>1)
  { 
    // if morethan 1 album. creating main folder for all albums
    $user_profile = (new FacebookRequest(
      $session, 'GET', '/me'
    ))->execute()->getGraphObject(GraphUser::className());

    $folder = $user_profile->getName(); // getting user name
    $path = "download/".$folder;
    $zipName = $folder;

    if(is_dir($path))
      rrmdir($path);
    //$old_umask = umask(0);
    mkdir($path, 0777); // make directory
    //umask($old_umask);
  }
  else
    $path = "download"; // if only one album

  // loop for all albums
  foreach ($albumList as $key => $value) 
  {
    $id = $value;

    $request = new FacebookRequest($session, "GET", "/".$id."/");
    $response = $request->execute();
    $graphObject = $response->getGraphObject();
    $album_name = $graphObject->getProperty('name'); // getting specific album name using album id.

    if(isset($_POST['Move']) && $_POST['Move'] != "")
    {
      $album_name = str_replace(" ","",$album_name); // for picasa move.
    }
    $curunt_path =  $path."/".$album_name; // download path for curunt album.

    if($sizeArray == 1) //if only 1 album to download then set the main $path.
    {
      $path = $curunt_path; 
      $zipName = $id;
    }

    if(is_dir($curunt_path))
      rrmdir($curunt_path);
    //$old_umask = umask(0);
    mkdir($curunt_path, 0777);
    //umask($old_umask);

    downloadAlbum($id, $curunt_path);
  }

  if(isset($_POST['Move']) && $_POST['Move'] != "")
  {
    echo $path;
  }
  else
  {
    Zip($path, $path.".zip");
    echo $path.".zip"; 
  }
  
}

?>