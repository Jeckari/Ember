<?php 
include('config.php');

$DBH = 0;

//Return codes:
class AjaxReturn {
    const Success = 0;
    const SecurityFail = 1;
    const SQLFail = 2;
    const UnknownAction = 3;
    const MalformedAction = 4;
    const BadLogin = 5;
    const LockedLogin = 6;
};
 
function setup_db() {
    global $DBH;
    global $db_type, $db_database, $db_host, $db_database, $db_user, $db_pass;
    global $default_user, $default_pass;
     try {  
     if($db_type == "sqlite")
        $DBH = new PDO("sqlite:$db_database");  
     else
        $DBH = new PDO("$db_type:host=$db_host;dbname=$db_database", $db_user, $db_pass);  
    } catch(PDOException $e) {  
        echo $e->getMessage();  
        die();
    }  


    
    //Check for table existence.
    $hasUsers = false;
    $hasNotes = false;
    $hasBooks = false;
    
    try {
        $stmt = $DBH->prepare("SHOW TABLES");
        $stmt->execute();
        
        $stmt->setFetchMode(PDO::FETCH_BOTH);
        while($row = $stmt->fetch()){
            $tabname = strtolower($row[0]);
            switch($tabname){
                case "users": $hasUsers = true; break;
                case "notes": $hasNotes = true; break;
                case "books": $hasBooks = true; break;
            }
        }
        
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    
    if(!$hasUsers){
        try {
            $stmt = $DBH->prepare("CREATE TABLE users (id INT NOT NULL AUTO_INCREMENT,  PRIMARY KEY(id), username VARCHAR(16), password VARCHAR(128), attempts TINYINT, attemptTime DATETIME)");
            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();  
        }
        
        create_user($default_user,$default_pass);    
    }
    if(!$hasNotes) {
        try {
                $stmt = $DBH->prepare("CREATE TABLE notes (id INT NOT NULL AUTO_INCREMENT,  PRIMARY KEY(id), head VARCHAR(128), body TEXT, userID INT NOT NULL, bookID INT NOT NULL, created DATETIME, modified DATETIME)");
                $stmt->execute();
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
    }
    if(!$hasBooks) {
        try {
                $stmt = $DBH->prepare("CREATE TABLE books (id INT NOT NULL AUTO_INCREMENT,  PRIMARY KEY(id), name VARCHAR(128))");
                $stmt->execute();
                $stmt = $DBH->prepare("INSERT INTO books (id, name) values (1,'Unfiled')");
                $stmt->execute();
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
    }
}

function create_book($bookname){
    global $DBH;
    try {
                $data = array(
                    'name' => $bookname
                );
                $stmt = $DBH->prepare("SELECT id FROM books where name = :name");
                $stmt->execute($data);
                if($stmt->rowCount() == 0){
                    $stmt = $DBH->prepare("INSERT INTO books (name) values (:name)");
                    $stmt->execute($data);
                    $stmt = $DBH->prepare("SELECT id FROM books where name = :name");
                    $stmt->execute($data);
                }
                if($stmt->rowCount() == 0)
                    return 1;
                
                $stmt->setFetchMode(PDO::FETCH_BOTH);
                $row = $stmt->fetch();
                return $row['id'];
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    return 1;
}

function format_html($content)
 {
  $content = "<p>" . str_replace("\n", "<br/>", $content) . "";
  $content = "" . str_replace("<br/><br/>", "</p><p>", $content) . "";
  return "" . str_replace("<br/><li>", "<li>", $content) . "";
 }
 
 function hash_password($username, $password) {
    $salt = hash('sha256', uniqid(mt_rand(), true) . strtolower($username));
    $hash = hash('sha256',$salt.$password);
    for($i=0;$i<256;$i++){
        $hash = hash('sha256',$hash);    
    }
    
    return $salt . $hash;    
 }
 
  
 function create_user($username, $password){
    global $DBH;
    $username = ucwords(trim(strtolower($username)));
    $saltypassword = hash_password($username,$password);
    $username        = htmlentities($username,ENT_QUOTES, 'UTF-8');
    
    $data = array( 
        'username' => $username,
        'password' => $saltypassword,
    );
    try {
        $stmt = $DBH->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
        $stmt->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }   
 }
 
 function attempt_login($username, $saltypassword) {
    global $DBH;
    global $max_attempts;
    
    $data = array( 
        'username' => $username,
    );
    
    //Check how many attempts they've made
    try {
        $stmt = $DBH->prepare('SELECT password, attempts, UNIX_TIMESTAMP(attemptTime), username, id FROM users WHERE username = :username');
        $stmt->execute($data);
        
        $stmt->setFetchMode(PDO::FETCH_BOTH);
        $row = $stmt->fetch();
        
    } catch (PDOException $e) {
        return AjaxReturn::SQLFail . $e->getMessage();   
    }
    
    $attempts = $row['attempts'];
    
    if($r[2] > strtotime("-1 hour")){ //More than an hour ago.
        try {
            $stmt = $DBH->prepare('UPDATE users SET attempts = 0 WHERE username = :username');
            $stmt->execute($data);
        
        } catch (PDOException $e) {
            return AjaxReturn::SQLFail . $e->getMessage();    
        }
        $attempts = 0;
    }
    
    if($attempts > $max_attempts) {
        return AjaxReturn::LockedLogin;
    }
    
    //Check password
    $salt = substr($row['password'], 0, 64);
    $hash = $saltypassword;
    for($i=0;$i<256;$i++){
        $hash = hash('sha256',$hash);    
    }
    $hash = $salt.$hash;
    
    if($hash == $row['password']) {
        $_SESSION['SESS_MEMBER_ID'] = $row['id'];
        $_SESSION['SESS_MEMBER_NAME'] = $row['username'];
        $_SESSION['SESS_LAST_POLL'] = strtotime("now");
         try {
            $stmt = $DBH->prepare('UPDATE users SET attempts = 0 WHERE username = :username');
            $stmt->execute($data);
        
        } catch (PDOException $e) {
            //Do nothing here, because we logged in. So we can't reset the attempt count. So what.
        }
        $attempts = 0;
        return AjaxReturn::Success . $row['username'];
    }
    else { 
         $data = array( 
            'username' => $username,
            'time' => strtotime("now"),
        );
        try {
            $stmt = $DBH->prepare('UPDATE users SET attempts = attempts + 1, attemptTime = FROM_UNIXTIME(:time) WHERE username = :username');
            $stmt->execute($data);
        
        } catch (PDOException $e) {
            return AjaxReturn::SQLFail . $e->getMessage();    
        }
    }
    return AjaxReturn::BadLogin;
 }
 
 function print_category($id, $count, $data) {
    global $DBH;
    try {
        $mydata = array(
            'id' => $id
        );
        $stmt = $DBH->prepare('SELECT name FROM books WHERE id = :id');
        $stmt->execute($mydata);
        $stmt->setFetchMode(PDO::FETCH_ASSOC); 
        $row = $stmt->fetch();
        if($row) {
            $name = $row['name'];
        }
    } catch (PDOException $e) {
            return AjaxReturn::SQLFail . $e->getMessage();    
    }
    
    $catfile = str_replace (" ", "", $name);    
    echo '<div class="'.$catfile.' core droptarget" id="cat'.$id.'" >';

    echo '<div class = "'.$catfile.' sectionHead"  >';
    echo '<h1>';
    echo $name;
    echo ' <span class = "count">(' . $count . ')</span>';
    echo '</h1></div>';
    echo $data;
    echo '</div>';
 }
 
function print_notes(){
        global $DBH;
        try {
            $stmt = $DBH->prepare('SELECT head, body, bookID, notes.id, users.username FROM notes INNER JOIN users on notes.userID = users.id ORDER BY bookID ASC, modified DESC');
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC); 
            
            $lastcat = "";
            $catdata = "";
            $catcount = 0;
            
            while($row = $stmt->fetch()) {
                
                if($row['bookID'] != $lastcat && $lastcat != "") {//New category
                    print_category($lastcat,$catcount,$catdata);
                    $catdata = "";
                    $catcount = 0;
                }
                
                $catcount += 1;
                $lastcat = $row['bookID'];                        
                $catdata = $catdata . '<div class = "ember" id="note'.$row['id'].'" >';
                $catdata = $catdata . '<small>' . html_entity_decode($row['head'],ENT_QUOTES, 'UTF-8') . '</small>';
                $catdata = $catdata . '<h1>' . html_entity_decode($row['head'],ENT_QUOTES, 'UTF-8') . '</h1>';
                $catdata = $catdata . '<h2>' . html_entity_decode($row['username'],ENT_QUOTES, 'UTF-8') . '</h2>';
                $catdata = $catdata . format_html(html_entity_decode($row['body'],ENT_QUOTES, 'UTF-8'));
                $catdata = $catdata . '</div>';
            }
            if($lastcat != "")
                print_category($lastcat, $catcount, $catdata);
        } catch (PDOException $e) {
            echo $e->getMessage();
            return;    
        }
        
        
 }
 
    setup_db();
    mb_language('uni');
    mb_internal_encoding('UTF-8');    
    
 ?>