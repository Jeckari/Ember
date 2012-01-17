<?php  
        session_start();
        
        include_once ("./cgi-bin/notedb.php");  
        if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
            die();
        }

        
        // CLIENT INFORMATION  
        $head        = htmlentities(trim($_POST['head']),ENT_QUOTES, 'UTF-8');  
        $body        = htmlentities(trim($_POST['body']),ENT_QUOTES, 'UTF-8');  
        $cat        = htmlentities(trim($_POST['cat']),ENT_QUOTES, 'UTF-8');  

        
        $cat = ucwords(trim(strtolower($cat)));
        $head = ucwords(trim(strtolower($head)));
        
        
        $body = mysql_real_escape_string($body);
        $head = mysql_real_escape_string($head);
        $cat = mysql_real_escape_string($cat);
        
        if($cat == "Trash") //No sense creating something for the trash.
            die();
        
        if(empty($body))
            die();
        
        if(empty($head))
            $head = "Note";
        
        if(empty($cat))
            $cat = "Unfiled";
            
        
        $addNote  = "INSERT INTO notes (head,body,category) VALUES ('$head','$body','$cat')";  
        mysql_query($addNote) or die(mysql_error());  
        $result = mysql_query("SELECT MAX(ID) FROM notes where head = '$head'");
        if($result){
            $row = mysql_fetch_array($result);
            echo $row[0];
        }
?>