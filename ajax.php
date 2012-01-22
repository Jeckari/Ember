<?php  
        session_start();        
        include_once ("./cgi-bin/notedb.php");  
        

        if(isset($_POST['act'])) {
            $act = $_POST['act'];
        } else {
            echo AjaxReturn::UnknownAction;
            die();            
        }
        
        //Actions without security:
        
        if($act == "login") { //Login
            if(isset($_POST['user']))
                $username   = htmlentities(trim($_POST['user']),ENT_QUOTES, 'UTF-8');  
            if(isset($_POST['pass']))
                $password   = htmlentities($_POST['pass'],ENT_QUOTES, 'UTF-8');  
            
            if(empty($username)) {
                echo AjaxReturn::MalformedAction;
                die();
            }
            if(empty($password)){
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
                        echo AjaxReturn::Success . $salt;
                        die();
                    }         
                    echo AjaxReturn::BadLogin;
                    die();
                } catch (PDOException $e) {
                    echo AjaxReturn::SQLFail . $e->getMessage();
                    die();
                }
            }
            
            echo attempt_login($username,$password);  
            die();
        }
        
        //Actions with security:
        
        
        if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
            echo AjaxReturn::SecurityFail;
            die();
        }
        
        if($act == "poll") { //Poll for new data
            if(!isset($_SESSION['SESS_LAST_POLL']))
                $_SESSION['SESS_LAST_POLL'] = strtotime("now");
            
            $data = array( 
                'modified' => $_SESSION['SESS_LAST_POLL'],
            );
            
            try {
                $stmt = $DBH->prepare('SELECT head, body, bookID, notes.id, users.username, books.name as bookname FROM notes INNER JOIN users ON notes.userID = users.id JOIN books ON notes.bookID = books.ID WHERE modified > FROM_UNIXTIME(:modified)');
                $stmt->execute($data);
                $stmt->setFetchMode(PDO::FETCH_ASSOC); 
                
                echo AjaxReturn::Success;
                $results = array();
                
                $count = 0;
                while($row = $stmt->fetch()) {
                        $count++;
                        $ar = array($row['head'], $row['body'], $row['bookname'], $row['id'], $row['username'], $row['bookID']);
                        array_push($results,json_encode($ar));
                }
                if($count > 0) {
                    echo json_encode($results);
                    $_SESSION['SESS_LAST_POLL'] = strtotime("now");
                }
                die();
            }  catch (PDOException $e) {
                echo AjaxReturn::SQLFail . $e->getMessage();
                die();
            }
            
            
        }
        else if($act == "create") { //Create a new note
            if(isset($_POST['head']))
                $head = htmlentities(trim($_POST['head']),ENT_QUOTES, 'UTF-8');  
            if(isset($_POST['body']))
                $body = htmlentities(trim($_POST['body']),ENT_QUOTES, 'UTF-8');  
            if(isset($_POST['cat']))
                $cat = htmlentities(trim($_POST['cat']),ENT_QUOTES, 'UTF-8');  

            if($cat == "Trash" ) { //No sense creating something for the trash.
                echo AjaxReturn::MalformedAction;
                die();
            }
            $cat = create_book(ucwords(trim(strtolower($cat))));
            $head = ucwords(trim(strtolower($head)));
            
            
            if(empty($body) || strlen(trim($body)) == 0)
                $body = "No text.";
                
            if(empty($head) || strlen(trim($head)) == 0)
                $head = "Note";
            
            if(empty($cat))
                $cat = 0;
            
            $data = array( 
                'head' => $head,
                'bookID' => $cat,
                'body' => $body,
                'created' => strtotime("now"),
                'modified' => strtotime("now"),
                'userID' => $_SESSION['SESS_MEMBER_ID'],
            );
            try {
                $stmt = $DBH->prepare('INSERT INTO notes (head,body,bookID,created,modified,userID) VALUES (:head,:body,:bookID,FROM_UNIXTIME(:created),FROM_UNIXTIME(:modified),:userID)');
                $stmt->execute($data);
                $stmt = $DBH->prepare('SELECT MAX(id) as maxid FROM notes');
                $stmt->execute($data);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);    
                echo AjaxReturn::Success;
                if($row = $stmt->fetch()) {
                    echo $row['maxid'];
                } 
                die();
            } catch (PDOException $e) {
                echo AjaxReturn::SQLFail . $e->getMessage();
                die();
            }
        } else if($act == "move") { //Move or delete a note
        
            if(isset($_POST['id']))
                $id  = htmlentities(trim($_POST['id']),ENT_QUOTES, 'UTF-8');  
                
            if(isset($_POST['cat'])) {
                $cat = htmlentities(trim($_POST['cat']),ENT_QUOTES, 'UTF-8');  
                $cat = ucwords(trim(strtolower($cat)));
            }

            if(empty($id)){
                echo AjaxReturn::MalformedAction;
                die();     
            }                

            try {
                if($cat == "Trash") {
                
                    $data = array( 
                        'id' => $id,
                    );
                    $stmt = $DBH->prepare('DELETE from notes WHERE id=:id');
                } else {
                    if(empty($cat)){
                        echo AjaxReturn::MalformedAction;
                        die();
                    }
                    $data = array( 
                        'id' => $id,
                        'bookID' => $cat,
                        'modified' => strtotime("now")
                    );
                    $stmt = $DBH->prepare('UPDATE notes SET bookID=:bookID, modified=FROM_UNIXTIME(:modified) WHERE id=:id');
                }
                $stmt->execute($data);
                echo AjaxReturn::Success;
            } catch (PDOException $e) {
                echo AjaxReturn::SQLFail . $e->getMessage();
            }   
            die();
        }
        else if($act == "logout") { //Logout
            session_start();
            session_destroy();
            echo AjaxReturn::Success;
            die();
        }
        else if($act == "search") { //Search
            if(isset($_POST['text']))
                $text = htmlentities(trim($_POST['text']),ENT_QUOTES, 'UTF-8');  
            
            if(empty($text)){
                echo AjaxReturn::MalformedAction;
                die();
            }
                
            $data = array( 
                'text' => '%'.$text.'%'
            );
            try {
                $stmt = $DBH->prepare('SELECT id from notes WHERE UPPER(body) LIKE UPPER(:text) or UPPER(head) like UPPER(:text)');
                $stmt->execute($data);
            
                $stmt->setFetchMode(PDO::FETCH_ASSOC);    
                echo AjaxReturn::Success;
                
                while($row = $stmt->fetch()) {  
                    echo ' '.$row['id'];  
                }  
            } catch (PDOException $e) {
                echo AjaxReturn::SQLFail . $e->getMessage();
                die();
            }   
        
        }
        
        
    echo AjaxReturn::MalformedAction;
?>