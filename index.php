<?php
require_once("config.php"); // Inforamation of Facebook App
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

    <!-- jQuery Downloader -->
    <script src="lib/fileDownload/jquery.fileDownload.js"></script>

    <!-- Bootstrap Files -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

    <div id="fb-root"></div>
    <script>
    // FB Initialization
    window.fbAsyncInit = function() {
        FB.init({
          appId      : "<?php echo $APP_ID;?>", // Facebook App ID
          xfbml      : true,
          version    : 'v2.0', // Facebook Auth Version 
          status     : true,
          cookie     : true
        });

        //check user login status
        FB.getLoginStatus(function(response) {
          if (response.status === 'connected') {
            // if user allowed to access his FB inforamtion.
            /*var uid = response.authResponse.userID;*/
            var accessToken = response.authResponse.accessToken; // Facebook AccessToken
            showData(accessToken);
            
          }
          else {
            // show login button
            $(".on_login").show();

          }
        });
        
    };

    function login()
    {
      FB.login(function(response) {
        if (response.authResponse) 
        {
          var accessToken = response.authResponse.accessToken; // Facebook AccessToken
          showData(accessToken);
        } 
        else 
        {
          // show login button
          $(".on_login").show(); // If auth doen't response.
        }
      },{scope: 'user_about_me,user_photos'});
    }

    // display the page content. ex. display profile picture, user name and list all albums. 
    function showData(token)
    {
      //set the facebook token in session by ajax (facebook.php page). 
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                facebook_token: token //token
              },
              success:function(result)
              {

              }
      });

      $(".on_login").hide(); // hide login button
      $(".after_login").show(); // show the div which contains user information.

      // FB.api is for call the graph api.
      FB.api('/me', function(response) {
        $("#profile_name").text(response.name); // getting user name.
        
      });

      FB.api('/me/picture?type=large', function(response) {
        $("#profile_pic").attr("src",response.data.url); // User profile photo url.
      });

      // list of all user's albums and make frame(img-container) of each album with doenload and move button.
      FB.api('/me/albums', function(response) {
        var list="";
        $.each(response.data, function(index, element) {
          list+="<div class='img-container'><span>"+element.name+"</span><a href='slides.php?id="+element.id+"'><img src='https://graph.facebook.com/"+element.id+"/picture?type=album&access_token="+FB.getAccessToken()+"'/></a><input type='checkbox' name='check' value='"+element.id+"'><br><button type='button' onclick=downloadThis("+element.id+") class='btn btn-default'>Download</button><button type='button' onclick=moveThis("+element.id+") class='btn btn-default'>Move</button></div>";
        });
        $(".albums").append(list);
      });
    }

    //only one main function for all type of album download
    function download(AlbumIds)
    {
      $('#mydiv').show(); // loading image show and it will block the page content.
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                AlbumId: AlbumIds // albums ids
              },
              success:function(result)
              {
                $('#mydiv').hide(); // if success then loading image will hide.
                $(".start_download").attr("href",result); // set the link of download
                $("#myModal").modal(); // show the download button model.
              }
      });
    }

    //on click on Click Here to Download button it will start the download
    $(document).on("click", "start_download", function () {
        $.fileDownload($(this).prop('href'), {
            preparingMessageHtml: "We are preparing your report, please wait...",
            failMessageHtml: "There was a problem generating your report, please try again."
        });
        return false;
    });

    // download button on album container
    function downloadThis(AlbumId)
    {
      download(AlbumId);
    }

    // for download all album button.
    function downloadAll()
    {
      var idList = "";
      $("input[name='check']").each(function() {
        idList+=$(this).val()+","; //getting each albums ids.
      });
      idList = idList.substring(0, idList.length - 1); //for remove last comas.
      download(idList); //call download function with album ID's list
    }

    // for download selected album button.
    function downloadSelected()
    {
      var idList = "";
      $("input[name='check']:checked").each(function() {
        idList+=$(this).val()+","; //getting selected albums ids.
      });
      idList = idList.substring(0, idList.length - 1); //for remove last comas.
      if(idList == "")
        alert("Select at least one album");
      else
      {
        download(idList); //call download function with album ID's list
      }
    }

    // for move photos to google plus (picasa)
    function move(AlbumIds)
    {
      $('#mydiv').show(); //loading image show and block page content
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                AlbumId: AlbumIds, // Albums IDs.
                Move: true // for picasa move and we don't need to create .zip file.
              },
              success:function(result)
              {
                location.href="picasa.php?album="+result; // rerdirect for upload images into picasa with album location.
              }
      });
    }

    // for move all albums button
    function moveAll()
    {
      var idList = "";
      $("input[name='check']").each(function() {
        idList+=$(this).val()+","; // getting all albums Ids
      });
      idList = idList.substring(0, idList.length - 1); //for remove last comas.
      move(idList); // call main move() funation with album id list.
    }

    // for move selected album button
    function moveSelected()
    {
      var idList = "";
      $("input[name='check']:checked").each(function() {
        idList+=$(this).val()+","; // getting all albums Ids
      });
      idList = idList.substring(0, idList.length - 1);  //for remove last comas.
      if(idList == "")
        alert("Select at least one album");
      else
      {
        move(idList); // call main move() funation with album id list.
      }
    }

    // for move button on Album container
    function moveThis(AlbumId)
    {
      move(AlbumId);
    }

    // onclick on Logout button user will logout from facebook.
    function logout()
    {
      // destroy the session which we uses in php.
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                facebook_token_destroy: true
              },
              success:function(result)
              {
                
              }
      });
      FB.logout(function(){document.location.reload();}); //reload the page
    }

    //basic setup Initialization.
    (function(d, s, id){
         var js, fjs = d.getElementsByTagName(s)[0];
         if (d.getElementById(id)) {return;}
         js = d.createElement(s); js.id = id;
         js.src = "//connect.facebook.net/en_US/sdk.js";
         fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    
    </script>

    <!-- Modal Start -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <center><h4 class="modal-title" id="myModalLabel">Album Download</h4></center>
          </div>
          <div class="modal-body">
            <table style="width: 100%; text-align: left;">
              <tbody class="table_data">
                <!-- Download Button -->
                <a href="download/133988719992004.zip" class="btn btn-primary start_download">Click Here to Download</a>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal End -->

    
    <!-- only Login Button show with some message -->
    <div class="on_login" align="center">
      <h3>Get Your</h3>
      <h1><span style="color:#3b5998">FACEBOOK</span> ALBUM</h1>
      <img src="lib/images/login.PNG" onclick="login();"/>
    </div>

    <!-- <center><img src="lib/images/loader.gif"/></center> -->
    <!-- Loader image and its block the page(can not click on any events) -->
    <div id="mydiv">
        <img src="lib/images/loader.gif" class="ajax-loader"/>
    </div>

    <!-- If login & authentication is success then below content show-->
    <div class="after_login container">
      <div class="row">
        <div class="col-md-12 bg-primary"><h3>Welcome <span id="profile_name"></span> !</h3><img src="lib/images/logout.PNG" class="logout" onclick="logout();"/></div>
      </div>
      <div class="row content">
        <!-- below div for profile image and button for download and move -->
        <div class="col-md-3">
          <img id="profile_pic" src="">
          <button type="button" class="btn btn-primary" onclick="downloadAll()">Download All Albums</button>
          <button type="button" class="download_selected btn btn-primary" onclick="downloadSelected()">Download Selected Albums</button>
          <button type="button" class="btn btn-primary" onclick="moveAll()">Move All Albums To Picasa</button>
          <button type="button" class="btn btn-primary" onclick="moveSelected()">Move Selected Albums To Picasa</button>
        </div>

        <!-- below div for show all albums -->
        <div class="col-md-9 albums">
          <!-- <div class="img-container">
             <img src="lib/images/background.jpg" alt="" />
             <span>kkkk</span>
          </div> -->
        </div>
      </div>
    </div>
    
    <!-- Bootstrap File -->
    <script src="lib/bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>