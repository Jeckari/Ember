<?php 
include('config.php');

$DBH = 0;


 class PasswordResults {
    const Failed = 0;
    const NoUser = 1;
    const Locked = 2;
    const Success = 3;
    const UserExists = 4;
 }
 
 class CreateNoteResults {
    const Failed = 0;
    const Success = 1;
 }
 
 
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


    try {
        $stmt = $DBH->prepare("SELECT 1 from users");
    } catch (PDOException $e) {
        //Failed to prepare, so users doesn't exist
        //TODO: Code checking
        try {
                $stmt = $DBH->prepare("CREATE TABLE users (username VARCHAR(16), PRIMARY KEY(username), password VARCHAR(128), attempts TINYINT, attemptTime DATETIME)");
                $stmt->execute();
            } catch (PDOException $e) {
                echo $e->getMessage();  
            }
            
            create_user($default_user,$default_pass);    
    }
    
    try {
        $stmt = $DBH->prepare("SELECT 1 from notes");
    } catch (PDOException $e) {
        //Failed to prepare, so notes doesn't exist
        try {
                $stmt = $DBH->prepare("CREATE TABLE notes (id INT NOT NULL AUTO_INCREMENT,  PRIMARY KEY(id), head VARCHAR(128), body TEXT, category VARCHAR(128), userID INT NOT NULL)");
                $stmt->execute();
            } catch (PDOException $e) {
                echo $e->getMessage();
            }
    
    }    
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
    try {
        $stmt = $DBH->prepare('SELECT password, attempts, UNIX_TIMESTAMP(attemptTime), username FROM users WHERE username = :username');
        $stmt->execute($data);
        
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $row = $stmt->fetch();
        
    } catch (PDOException $e) {
        echo $e->getMessage();
        return PasswordResults::NoUser;    
    }
    
    $attempts = $row['attempts'];
    
    if($r[2] < strtotime("-1 hour")){ //More than an hour ago.
        try {
            $stmt = $DBH->prepare('UPDATE users SET attempts = 0 WHERE username = :username');
            $stmt->execute($data);
        
        } catch (PDOException $e) {
            echo $e->getMessage();
            return PasswordResults::NoUser;    
        }
        $attempts = 0;
    }
    
    if($attempts > max_attempts) {
        return PasswordResults::Locked;
    }
    
    $salt = substr($row['password'], 0, 64);
    $hash = $saltypassword;
    for($i=0;$i<256;$i++){
        $hash = hash('sha256',$hash);    
    }
    $hash = $salt.$hash;
    
    if($hash == $row['password']) {
        $_SESSION['SESS_MEMBER_ID'] = $row['username'];
        return PasswordResults::Success;
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
            echo $e->getMessage();
            return PasswordResults::Failed;    
        }
    }
    return PasswordResults::Failed;
 }
 
function print_notes(){
        global $DBH;
        try {
            $stmt = $DBH->prepare('SELECT head, body, category, id FROM notes ORDER BY Category ASC');
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC); 
            
            $lastcat = "";
        
            while($row = $stmt->fetch()) {
                $catfile = str_replace (" ", "", $row['category']);
                
                if($row['category'] != $lastcat) {//New category
                    if($lastcat != "")
                            echo '</div>';
                    echo '<div class="'.$catfile.' core">';
                    $backimg = 'backs/'. urlencode($catfile) .'.png';
                    if(!file_exists($backimg))
                        $backimg = 'backs/Unfiled.png';
                    echo '<div class = "'.$catfile.' sectionHead droptarget" id="'.$row['category'].'" >';
                    echo '<h1>';
                    echo $row['category'];
                    echo '</h1></div>';
                }
                
                $embimg = 'tabs/'. urlencode($catfile) .'.png';
                if(!file_exists($backimg)) {
                    $embimg = 'tabs/Unfiled.png';
                    }
                        
                echo '<div class = "ember" id="note'.$row['id'].'" >';
                echo '<small>' . html_entity_decode($row['head'],ENT_QUOTES, 'UTF-8') . '</small>';
                echo '<h1>' . html_entity_decode($row['head'],ENT_QUOTES, 'UTF-8') . '</h1>';
                echo format_html(html_entity_decode($row['body'],ENT_QUOTES, 'UTF-8'));
                echo '</div>';
                
                $lastcat = $row['category'];
            }
            if($lastcat != "")
                echo '</div>';
        } catch (PDOException $e) {
            echo $e->getMessage();
            return;    
        }
        
        
 }
 
    setup_db();
    mb_language('uni');
    mb_internal_encoding('UTF-8');    
    
 ?>