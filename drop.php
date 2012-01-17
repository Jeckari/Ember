<?php  
        session_start();
        include_once ("./cgi-bin/notedb.php");  
        
        if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
            die();
        }
      
        // CLIENT INFORMATION  
        $id        = htmlentities(trim($_POST['id']),ENT_QUOTES, 'UTF-8');  
        $cat        = htmlentities(trim($_POST['cat']),ENT_QUOTES, 'UTF-8');  
        $cat = ucwords(trim(strtolower($cat)));
        
        if(empty($id) || empty($cat))
            die();
            
        if($cat == "Trash") {
            $addNote  = "DELETE from notes WHERE id='$id'";  
        } else {
            $addNote  = "UPDATE notes SET category='$cat' WHERE id='$id'";  
        }
        mysql_query($addNote) or die(mysql_error());  
      
?>