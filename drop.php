<?php  
        session_start();
        include_once ("./cgi-bin/notedb.php");  
        
        if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
            die();
        }
      
        // CLIENT INFORMATION  
        $id  = htmlentities(trim($_POST['id']),ENT_QUOTES, 'UTF-8');  
        $cat = htmlentities(trim($_POST['cat']),ENT_QUOTES, 'UTF-8');  
        $cat = ucwords(trim(strtolower($cat)));

        if(empty($id))
            die();
        

        try {
            if($cat == "Trash") {
            
                $data = array( 
                    'id' => $id,
                );
                $stmt = $DBH->prepare('DELETE from notes WHERE id=:id');
            } else {
                if(empty($cat))
                    die();
                $data = array( 
                    'id' => $id,
                    'cat' => $cat,
                );
                $stmt = $DBH->prepare('UPDATE notes SET category=:cat WHERE id=:id');
            }
            $stmt->execute($data);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }   
?>