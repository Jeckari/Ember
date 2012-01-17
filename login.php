<?php        
        session_start();
        
        include_once ("./cgi-bin/notedb.php");  
      
        // CLIENT INFORMATION  
        $username   = htmlentities(trim($_POST['username']),ENT_QUOTES, 'UTF-8');  
        $password   = htmlentities($_POST['password'],ENT_QUOTES, 'UTF-8');  
        
        $sql = 'SELECT * FROM users';
        $q = mysql_query($sql);
        if(!$q || mysql_num_rows($q) == 0) {
            echo PasswordResults::Failed;
            die();
        }
        
        if(empty($username)) {
            echo PasswordResults::NoUser;
            die();
        }
        if(empty($password) || $password == "0"){
            //No password, so gimme some salt
            
            $sql = 'SELECT password FROM users WHERE username = "' . mysql_real_escape_string($username) . '"';
            $q = mysql_query($sql) or die(mysql_error());
            if(!$q) {
                echo PasswordResults::NoUser;    
                die();
            }
            
            $r = mysql_fetch_assoc($q);
            $salt = substr($r['password'], 0, 64);
            echo $salt;
            die();
        }
        
        echo attempt_login($username,$password);      
?>