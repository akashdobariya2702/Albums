<?php
// we need session for stor the picasa token value.
session_start();

// include zend framework library to use picasa api
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Facebook Album</title>

    <!-- Bootstrap File -->
    <link href="lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- main css file -->
    <link href="lib/main.css" rel="stylesheet">
  
    <!-- jQuery File File -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <?php

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

    if (!isset($_SESSION['sessionToken']) && !isset($_GET['token'])) 
    {
        $url = getAuthSubUrl(); //getting url.
        echo "<center><a href=\"{$url}\"><img src='lib/images/google-login.png' style='width:75mm;margin-top:20%;'/></a></center>";
    }
    else if (!isset($_SESSION['sessionToken']) && isset($_GET['token'])) {
        $_SESSION['sessionToken'] =
            Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
        header("Location:index.php");
    }

    ?>
    <!-- Bootstrap File -->
    <script src="lib/bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>