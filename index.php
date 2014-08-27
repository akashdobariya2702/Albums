<?php
require_once("config.php");
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

    <script src="lib/fileDownload/jquery.fileDownload.js"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>

    <div id="fb-root"></div>
    <script>
    window.fbAsyncInit = function() {
        FB.init({
          appId      : "<?php echo $APP_ID;?>",
          xfbml      : true,
          version    : 'v2.0',
          status     : true,
          cookie     : true
        });

        FB.getLoginStatus(function(response) {
          if (response.status === 'connected') {
            /*var uid = response.authResponse.userID;*/
            var accessToken = response.authResponse.accessToken;
            showData(accessToken);
            
          }
          else {
            $(".on_login").show();

          }
        });
        
    };

    function login()
    {
      FB.login(function(response) {
        if (response.authResponse) 
        {
          var accessToken = response.authResponse.accessToken;
          showData(accessToken);
        } 
        else 
        {
           $(".on_login").show();
        }
      },{scope: 'user_about_me,user_photos'});
    }

    function showData(token)
    {
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                facebook_token: token
              },
              success:function(result)
              {

              }
      });

      $(".on_login").hide();
      $(".after_login").show();

      FB.api('/me', function(response) {
        $("#profile_name").text(response.name);
        
      });

      FB.api('/me/picture?type=large', function(response) {
        $("#profile_pic").attr("src",response.data.url);
      });

      FB.api('/me/albums', function(response) {
        var list="";
        $.each(response.data, function(index, element) {
          list+="<div class='img-container'><span>"+element.name+"</span><a href='slides.php?id="+element.id+"'><img src='https://graph.facebook.com/"+element.id+"/picture?type=album&access_token="+FB.getAccessToken()+"' onclick='fetchPhotos("+element.id+")'/></a><input type='checkbox' name='check' value='"+element.id+"'><br><button type='button' onclick=downloadThis("+element.id+") class='btn btn-default'>Download</button><button type='button' onclick=moveThis("+element.id+") class='btn btn-default'>Move</button></div>";
        });
        $(".albums").append(list);
      });
    }

    function download(AlbumIds)
    {
      $('#mydiv').show();
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                AlbumId: AlbumIds
              },
              success:function(result)
              {
                $('#mydiv').hide();
                $(".start_download").attr("href",result);
                $("#myModal").modal();
              }
      });
    }

    $(document).on("click", "start_download", function () {
        $.fileDownload($(this).prop('href'), {
            preparingMessageHtml: "We are preparing your report, please wait...",
            failMessageHtml: "There was a problem generating your report, please try again."
        });
        return false;
    });

    function downloadThis(AlbumId)
    {
      download(AlbumId);
    }

    function downloadAll()
    {
      var idList = "";
      $("input[name='check']").each(function() {
        idList+=$(this).val()+",";
      });
      idList = idList.substring(0, idList.length - 1);
      download(idList);
    }

    function downloadSelected()
    {
      var idList = "";
      $("input[name='check']:checked").each(function() {
        idList+=$(this).val()+",";
      });
      idList = idList.substring(0, idList.length - 1);
      if(idList == "")
        alert("Select at least one album");
      else
      {
        download(idList);
      }
    }

    function move(AlbumIds)
    {
      $('#mydiv').show();
      $.ajax({
              type: "POST",
              url: "facebook.php",
              cache: false,
              data: {
                AlbumId: AlbumIds,
                Move: true
              },
              success:function(result)
              {
                location.href="picasa.php?album="+result;
              }
      });
    }

    function moveAll()
    {
      var idList = "";
      $("input[name='check']").each(function() {
        idList+=$(this).val()+",";
      });
      idList = idList.substring(0, idList.length - 1);
      move(idList);
    }

    function moveSelected()
    {
      var idList = "";
      $("input[name='check']:checked").each(function() {
        idList+=$(this).val()+",";
      });
      idList = idList.substring(0, idList.length - 1);
      if(idList == "")
        alert("Select at least one album");
      else
      {
        move(idList);
      }
    }

    function moveThis(AlbumId)
    {
      move(AlbumId);
    }

    function logout()
    {
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
      FB.logout(function(){document.location.reload();});
    }

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

    

    <div class="on_login" align="center">
      <h3>Get Your</h3>
      <h1><span style="color:#3b5998">FACEBOOK</span> ALBUM</h1>
      <img src="lib/images/login.PNG" onclick="login();"/>
    </div>
    <!-- <center><img src="lib/images/loader.gif"/></center> -->
    <div id="mydiv">
        <img src="lib/images/loader.gif" class="ajax-loader"/>
    </div>
    <div class="after_login container">
      <div class="row">
        <div class="col-md-12 bg-primary"><h3>Welcome <span id="profile_name"></span> !</h3><img src="lib/images/logout.PNG" class="logout" onclick="logout();"/></div>
      </div>
      <div class="row content">
        <div class="col-md-3">
          <img id="profile_pic" src="">
          <button type="button" class="btn btn-primary" onclick="downloadAll()">Download All Albums</button>
          <button type="button" class="download_selected btn btn-primary" onclick="downloadSelected()">Download Selected Albums</button>
          <button type="button" class="btn btn-primary" onclick="moveAll()">Move All Albums To Picasa</button>
          <button type="button" class="btn btn-primary" onclick="moveSelected()">Move Selected Albums To Picasa</button>
        </div>
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