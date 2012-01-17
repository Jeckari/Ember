<?php 
include('config.php');

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
 
  
 class PasswordResults {
    const Failed = 0;
    const NoUser = 1;
    const Locked = 2;
    const Success = 3;
    const UserExists = 4;
 }
 
 function create_user($username, $password){
    $username = ucwords(trim(strtolower($username)));
    $saltypassword = hash_password($username,$password);
    $username        = mysql_real_escape_string(htmlentities($username,ENT_QUOTES, 'UTF-8'));
    
    $sql = 'INSERT INTO users (username, password) VALUES ("'.$username.'", "'.$saltypassword.'")';
    $q = mysql_query($sql);
 }
 
 function attempt_login($username, $saltypassword) {
    $sql = 'SELECT password, attempts, UNIX_TIMESTAMP(attemptTime), username FROM users WHERE username = "' . mysql_real_escape_string($username) . '"';
    $q = mysql_query($sql);

    if(!$q)
        return PasswordResults::NoUser;    
    
    $r = mysql_fetch_assoc($q);
    
    $attempts = $r[1];
    
    if($r[2] < strtotime("-1 hour")){ //More than an hour ago.
        $sql = 'UPDATE users SET attempts = 0 WHERE username = "' . mysql_real_escape_string($username) . '"';
        mysql_query($sql);
        $attempts = 0;
    }
    
    if($attempts > 5)
        return PasswordResults::Locked;
    
    $salt = substr($r['password'], 0, 64);
    $hash = $saltypassword;
    for($i=0;$i<256;$i++){
        $hash = hash('sha256',$hash);    
    }
    $hash = $salt.$hash;
    
    if($hash == $r['password']) {
        $_SESSION['SESS_MEMBER_ID'] = $r['username'];
        return PasswordResults::Success;
    }
    else {
        $sql = 'UPDATE users SET attempts = attempts + 1, attemptTime = FROM_UNIXTIME(' . strtotime("now") . ') WHERE username = "' . mysql_real_escape_string($username) . '"';
        mysql_query($sql);
    }
    return PasswordResults::Failed;
 }
 
 function print_notes(){
  $sql = "SELECT head, body, category, id FROM notes ORDER BY Category ASC";
        $result = mysql_query($sql);
        $lastcat = "";
        
        if(!$result){
            mysql_error();//Empty table
        }
        else
        while($row = mysql_fetch_array($result)){
            $catfile = str_replace (" ", "", $row[2]);
            if($row[2] != $lastcat) {//New category
                if($lastcat != "")
                        echo '</div>';
                echo '<div class="'.$catfile.' core">';
                $backimg = 'backs/'. urlencode($catfile) .'.png';
                if(!file_exists($backimg))
                    $backimg = 'backs/Unfiled.png';
                echo '<div class = "'.$catfile.' sectionHead droptarget" id="'.$row[2].'" >';
                echo '<h1>';
                echo $row[2];
                echo '</h1></div>';
            }
            
            $embimg = 'tabs/'. urlencode($catfile) .'.png';
            if(!file_exists($backimg)) {
                $embimg = 'tabs/Unfiled.png';
                }
                    
            echo '<div class = "ember" id="note'.$row[3].'" >';
            echo '<small>' . html_entity_decode($row[0],ENT_QUOTES, 'UTF-8') . '</small>';
            echo '<h1>' . html_entity_decode($row[0],ENT_QUOTES, 'UTF-8') . '</h1>';
            echo format_html(html_entity_decode($row[1],ENT_QUOTES, 'UTF-8'));
            echo '</div>';
            
            $lastcat = $row[2];
        }
        if($lastcat != "")
            echo '</div>';
 }
 
 // Connects to your Database 
    mysql_connect($db_host, $db_user, $db_pass) or die(mysql_error());     
    mysql_select_db($db_database) or die(mysql_error());
    
    //Comment this out after your table has been created.
    $sql="CREATE TABLE users (username VARCHAR(16), PRIMARY KEY(username), password VARCHAR(128), attempts TINYINT, attemptTime DATETIME)";
    @mysql_query($sql);
    $sql="CREATE TABLE notes (id INT NOT NULL AUTO_INCREMENT,  PRIMARY KEY(id), head VARCHAR(128), body TEXT, category VARCHAR(128), userID INT NOT NULL)";
    @mysql_query($sql);
    //Comment this out after your user has been created.
    create_user($default_user,$default_pass);
    
    mb_language('uni');
    mb_internal_encoding('UTF-8');
    
 ?>