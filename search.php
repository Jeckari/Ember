<?php  
        session_start();
        include_once ("./cgi-bin/notedb.php");  
        
        if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
            die();
        }
      
        // CLIENT INFORMATION  
        $text        = htmlentities(trim($_POST['text']),ENT_QUOTES, 'UTF-8');  
        
        if(empty($text))
            die();
            
        $sql  = "SELECT id from notes WHERE UPPER(body) LIKE UPPER('%$text%') or UPPER(head) like ('%$text%') ";  
        $result = mysql_query($sql);
        if(!$result){
            mysql_error();//Empty table
        }
        else while($row = mysql_fetch_array($result)){
            
            echo ' '.$row[0];
        }
?>