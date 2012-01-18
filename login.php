<?php        
        session_start();
        
        include_once ("./cgi-bin/notedb.php");  
      
        // CLIENT INFORMATION  
        $username   = htmlentities(trim($_POST['username']),ENT_QUOTES, 'UTF-8');  
        $password   = htmlentities($_POST['password'],ENT_QUOTES, 'UTF-8');  
        
        if(empty($username)) {
            echo PasswordResults::NoUser;
            die();
        }
        if(empty($password) || $password == "0"){
            //No password, so gimme some salt
            $data = array( 
                'username' => $username,
            );
            try {
                $stmt = $DBH->prepare('SELECT password FROM users WHERE username = :username');
                $stmt->execute($data);
                
                $stmt->setFetchMode(PDO::FETCH_ASSOC);    
                if($row = $stmt->fetch()) {
                    $salt = substr($row['password'], 0, 64);
                    echo $salt;
                    die();
                }         
                echo PasswordResults::NoUser;
                die();
            } catch (PDOException $e) {
                echo $e->getMessage();
                echo PasswordResults::NoUser;
                die();
            }
        }
        
        echo attempt_login($username,$password);      
?>