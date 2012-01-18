<?php
//Set up your database connection here.
$db_type        = "mysql";
$db_host        = "localhost";
$db_user        = "yourUser";
$db_pass        = "yourPass";
$db_database    = "yourDatabaseName";


//The following configure the user automatically created if the user table doesn't exist. 
$default_user   = "Admin";
$default_pass   = "Test";

//This setting controls the maximum number of login attempts before an account is locked.
$max_attempts   = 5;
?>