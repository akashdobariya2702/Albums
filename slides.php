<!-- Show the album images in slides -->
<!DOCTYPE html>
<html>
	<head>
		<title>Facebook Album</title>

		<!-- main css file -->
    	<link href="lib/main.css" rel="stylesheet">

		<!-- jQuery File File -->
    	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

		<!-- FlexSlider File for image slider-->
	    <link rel="stylesheet" href="lib/FlexSlider-master/flexslider.css" type="text/css" media="screen" />
	    <script defer src="lib/FlexSlider-master/jquery.flexslider.js"></script>
	</head>
	<body>
		<div id="fb-root"></div>
	    <script>
	    window.fbAsyncInit = function() {
	    	// FB Initialization
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
	          	// using album id get the albums photos sourse, width, height.
            	FB.api("/<?=$_GET['id']?>/photos?fields=source,width,height", function(response) {
			        var list=""; // list for album images
			        $.each(response.data, function(index, element) {
			          list+="<li><img src='"+element.source+"' width='"+element.width+"' height='"+element.height+"'/></li>";
			        });
			        $(".slides").html(list); // add code into slides jquery plugins

			        // start slider plugins
			        $('.flexslider').flexslider({
			        	animation: "slide"
			      	});
		      	});
		      		
	          }
	          else {
	          	// if user not login or authenticate the it will redirect to index page.
	            location.href="index.php";
	          }
	        });
	    };

	    (function(d, s, id){
	         var js, fjs = d.getElementsByTagName(s)[0];
	         if (d.getElementById(id)) {return;}
	         js = d.createElement(s); js.id = id;
	         js.src = "//connect.facebook.net/en_US/sdk.js";
	         fjs.parentNode.insertBefore(js, fjs);
	    }(document, 'script', 'facebook-jssdk'));
	    </script>
		
		<!-- Go Back to index page -->
		<a href="index.php"><img src="lib/images/button_home.png" height="15%" width="15%"/></a>

		<!-- Slider -->
	    <div class="flexslider">
	      <ul class="slides">
	    	
	      </ul>
	    </div>
	    
	</body>
</html>