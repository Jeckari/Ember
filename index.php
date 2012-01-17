<?php 
    session_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"	"http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
        <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
		<title>Ember</title>
        <LINK href="ember.css" rel="stylesheet" type="text/css">
        <LINK href="colors.css" rel="stylesheet" type="text/css">
	</head>
<script type="text/javascript" src="./jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="./jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="./ember.js"></script>
<script type="text/javascript" src="http://crypto-js.googlecode.com/files/2.5.3-crypto-sha256.js"></script>
	<body>
    <div class="_logo" ></div>
    
    <?php
    include_once ("./cgi-bin/notedb.php");   
        
    //Check whether the session variable SESS_MEMBER_ID is present or not
    if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
    ?>

    <div class="_login">  
    <h1>Login</h1>
    <form id="loginform" method="post">  
        <label for="username">Username:</label><input id="username" type="text" name="username" /><br/>
        <label for="password">Password:</label><input id="password" type="text" name="password" /><br/>
        <button> Submit </button>  
    </form>  
    <div class="success" style="display: none;">Successfully logged in.</div>  
    <div class="error" style="display: none;">Incorrect username or password.</div>  
    <div class="locked" style="display: none;">Account locked. Please wait an hour.</div>  
    </div>
            
    <?php
    } //End insecure
    else {
        
        include ("./noteload.php");
    }
    ?>        
	</body>
</html>
