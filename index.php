<?php
    // using sessions to store token info
    session_start();

    // require config and twitter helper
    require 'config.php';
    require 'twitter-login-php/autoload.php';

    // use our twitter helper
    use Abraham\TwitterOAuth\TwitterOAuth;

    if ( isset( $_SESSION['twitter_access_token'] ) && $_SESSION['twitter_access_token'] ) { // we have an access token
        $isLoggedIn = true;	
    } elseif ( isset( $_GET['oauth_verifier'] ) && isset( $_GET['oauth_token'] ) && isset( $_SESSION['oauth_token'] ) && $_GET['oauth_token'] == $_SESSION['oauth_token'] ) { // coming from twitter callback url
        // setup connection to twitter with request token
        $connection = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] );
		
        // get an access token
        $access_token = $connection->oauth( "oauth/access_token", array( "oauth_verifier" => $_GET['oauth_verifier'] ) );

        // save access token to the session
        $_SESSION['twitter_access_token'] = $access_token;

        // user is logged in
        $isLoggedIn = true;
    } else { // not authorized with our app, show login button
        // connect to twitter with our app creds
        $connection = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET );

        // get a request token from twitter
        $request_token = $connection->oauth( 'oauth/request_token', array( 'oauth_callback' => OAUTH_CALLBACK ) );

        // save twitter token info to the session
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

        // user is logged in
        $isLoggedIn = false;
    }
    
    if ( $isLoggedIn ) { // logged in
        // get token info from session
        $oauthToken = $_SESSION['twitter_access_token']['oauth_token'];
        $oauthTokenSecret = $_SESSION['twitter_access_token']['oauth_token_secret'];

        // setup connection
        $connection = new TwitterOAuth( CONSUMER_KEY, CONSUMER_SECRET, $oauthToken, $oauthTokenSecret );

        // user twitter connection to get user info
        $user = $connection->get( "account/verify_credentials", ['include_email' => 'true'] );

        if ( property_exists( $user, 'errors' ) ) { // errors, clear session so user has to re-authorize with our app
	        $_SESSION = array();
	        header( 'Refresh:0' );
        } else { // display user info in browser
	        ?>
	        <img src="<?php echo $user->profile_image_url; ?>" />
	        <br />
	        <b>User:</b> <?php echo $user->name; ?>
	        <br />
	        <b>Location:</b> <?php echo $user->location; ?>
	        <br />
	        <b>Twitter Handle:</b> <?php echo $user->screen_name; ?>
            <br />
            <b>Description:</b> <?php echo $user->description; ?>
	        <br />
	        <b>User Created:</b> <?php echo $user->created_at; ?>
	        <br />
	        <hr />
	        <br />
	        <a href="logout.php">Logout</a>

            <?php
        }
    } else {  
        $url = $connection->url( 'oauth/authorize', array( 'oauth_token' => $request_token['oauth_token'] ) );
        ?>
<!DOCTYPE html >
<html lang="en" itemscope itemtype="http://schema.org/Article">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LOGIN</title>
<script src="https://apis.google.com/js/client:platform.js?onload=renderButton" async defer></script>
<meta name="google-signin-client_id" content="390553438912-upsil31oeko7edibicf9uoos1rkissvg.apps.googleusercontent.com">
<link href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round" rel="stylesheet">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> 	

<script>
		// Render Google Sign-in button
		function renderButton() {
			gapi.signin2.render('gSignIn', {
				'scope': 'profile email',
				'display':'none',
				'width': 240,
				'height': 50,
				'longtitle': true,
				'theme': 'dark',
				'onsuccess': onSuccess,
				'onfailure': onFailure
			});
		}
		
		// Sign-in success callback
		function onSuccess(googleUser) {
			// Get the Google profile data (basic)
			//var profile = googleUser.getBasicProfile();
			
			// Retrieve the Google account data
			gapi.client.load('oauth2', 'v2', function () {
				var request = gapi.client.oauth2.userinfo.get({
					'userId': 'me'
				});
				request.execute(function (resp) {
					// Display the user details
					var profileHTML = '<h3>Welcome '+resp.given_name+'! <a href="javascript:void(0);" onclick="signOut();">Sign out</a></h3>';
					profileHTML += '<img src="'+resp.picture+'"/><p><b>Google ID: </b>'+resp.id+'</p><p><b>Name: </b>'+resp.name+'</p><p><b>Email: </b>'+resp.email+'</p><p><b>Gender: </b>'+resp.gender+'</p><p><b>Locale: </b>'+resp.locale+'</p><p><b>Google Profile:</b> <a target="_blank" href="'+resp.link+'">click to view profile</a></p>';
					document.getElementsByClassName("userContent")[0].innerHTML = profileHTML;
					
					document.getElementById("gSignIn").style.display = "none";
					document.getElementsByClassName("userContent")[0].style.display = "block";
				});
			});
		}
		
		// Sign-in failure callback
		function onFailure(error) {
			alert(error);
		}
		
		// Sign out the user
		function signOut() {
			var auth2 = gapi.auth2.getAuthInstance();
			auth2.signOut().then(function () {
				document.getElementsByClassName("userContent")[0].innerHTML = '';
				document.getElementsByClassName("userContent")[0].style.display = "none";
				document.getElementById("gSignIn").style.display = "block";
			});
			
			auth2.disconnect();
		}
		</script>
		<style type="text/css">
		    .continer{padding: 20px;}
			.userContent{
				padding: 10px 20px;
				margin: auto;
				width: 350px;
				background-color: #f7f7f7;
				box-shadow: 0 2px 5px 0 rgba(0,0,0,0.16),
				0 2px 10px 0 rgba(0, 0, 0, 0.12);
			}
			.userContent  h3{font-size: 17px;}
			.userContent  p{font-size: 15px;}
			.userContent  img{max-width: 100%;margin-bottom: 5px;}
			.login-form {
				width: 340px;
				margin: 30px auto;
			}
			.login-form form {
				margin-bottom: 15px;
				background: #f7f7f7;
				box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
				padding: 30px;
			}
			.login-form h2 {
				margin: 0 0 15px;
			}
			.login-form .hint-text {
				color: #777;
				padding-bottom: 15px;
				text-align: center;
			}
			.form-control, .btn {
				min-height: 38px;
				border-radius: 2px;
			}
			.login-btn {        
				font-size: 15px;
				font-weight: bold;
			}
			.or-seperator {
				margin: 20px 0 10px;
				text-align: center;
				border-top: 1px solid #ccc;
			}
			.or-seperator i {
				padding: 0 10px;
				background: #f7f7f7;
				position: relative;
				top: -11px;
				z-index: 1;
			}
			.social-btn .btn {
				margin: 10px 0;
				font-size: 15px;
				text-align: left; 
				line-height: 24px;       
			}
			.social-btn .btn i {
				float: left;
				margin: 4px 15px  0 5px;
				min-width: 15px;
			}
			.input-group-addon .fa{
				font-size: 18px;
			}
		</style>
	
</head>
<body style="background-color:black">
		<div class="login-form">
			<form method="post">
				<h2 class="text-center">Sign in</h2>		
				<div class="text-center social-btn">
					<a href="javascript:void(0);" onclick="fbLogin()" id="fbLink" class="btn btn-primary btn-block"><i class="fa fa-facebook"></i> Sign in with <b>Facebook</b></a>
					<a href="<?php echo $url; ?>" class="btn btn-info btn-block"><i class="fa fa-twitter"></i> Sign in with <b>Twitter</b></a>
					<div id="gSignIn"></div>
		
				</div>
				<div class="or-seperator"><i>or</i></div>
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-user"></i></span>
						<input type="email" class="form-control" name="email" placeholder="Email" required="required">
					</div>
				</div>
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon"><i class="fa fa-lock"></i></span>
						<input type="password" class="form-control" name="password" placeholder="Password" required="required">
					</div>
				</div>        
				<div class="form-group">
					<button type="submit" class="btn btn-success btn-block login-btn">Sign in</button>
				</div>
				<div class="clearfix">
					<label class="pull-left checkbox-inline"><input type="checkbox"> Remember me</label>
					<a href="#" class="pull-right text-success">Forgot Password?</a>
				</div>  
				
			</form>
			<div class="hint-text small">Don't have an account? <a href="#" class="text-success">Register Now!</a></div>
		</div>
		
		<div class="userContent" style="display: none;"></div>
		<div id="status"></div>
<div id="userData"></div>

		</body>
		<script>
				window.fbAsyncInit = function() {
					// FB JavaScript SDK configuration and setup
					FB.init({
					  appId      : '2282546262028402', // FB App ID
					  cookie     : true,  // enable cookies to allow the server to access the session
					  xfbml      : true,  // parse social plugins on this page
					  version    : 'v2.8' // use graph api version 2.8
					});
					
					// Check whether the user already logged in
					FB.getLoginStatus(function(response) {
						if (response.status === 'connected') {
							//display user data
							getFbUserData();
						}
					});
				};
				
				// Load the JavaScript SDK asynchronously
				(function(d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) return;
					js = d.createElement(s); js.id = id;
					js.src = "//connect.facebook.net/en_US/sdk.js";
					fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));
				
				// Facebook login with JavaScript SDK
				function fbLogin() {
					FB.login(function (response) {
						if (response.authResponse) {
							// Get and display the user profile data
							getFbUserData();
						} else {
							document.getElementById('status').innerHTML = 'User cancelled login or did not fully authorize.';
						}
					}, {scope: 'email'});
				}
				
				// Fetch the user profile data from facebook
				function getFbUserData(){
					FB.api('/me', {locale: 'en_US', fields: 'id,first_name,last_name,email,link,gender,locale,picture'},
					function (response) {
						document.getElementById('fbLink').setAttribute("onclick","fbLogout()");
						document.getElementById('fbLink').innerHTML = 'Logout from Facebook';
						document.getElementById('status').innerHTML = 'Thanks for logging in, ' + response.first_name + '!';
						document.getElementById('userData').innerHTML = '<p><b>FB ID:</b> '+response.id+'</p><p><b>Name:</b> '+response.first_name+' '+response.last_name+'</p><p><b>Email:</b> '+response.email+'</p><p><b>Gender:</b> '+response.gender+'</p><p><b>Locale:</b> '+response.locale+'</p><p><b>Picture:</b> <img src="'+response.picture.data.url+'"/></p><p><b>FB Profile:</b> <a target="_blank" href="'+response.link+'">click to view profile</a></p>';
					});
				}
				
				// Logout from facebook
				function fbLogout() {
					FB.logout(function() {
						document.getElementById('fbLink').setAttribute("onclick","fbLogin()");
						document.getElementById('fbLink').innerHTML = 'sign in with Facebook';
						document.getElementById('userData').innerHTML = '';
						document.getElementById('status').innerHTML = 'You have successfully logout from Facebook.';
					});
				}
				</script>

				
</html>                            
        <?php
    }?>
    
