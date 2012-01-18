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
            
        $data = array( 
            'text' => '%'.$text.'%'
        );
        try {
            $stmt = $DBH->prepare('SELECT id from notes WHERE UPPER(body) LIKE UPPER(:text) or UPPER(head) like UPPER(:text) or UPPER(category) like UPPER(:text)');
            $stmt->execute($data);
        
            $stmt->setFetchMode(PDO::FETCH_ASSOC);    
            while($row = $stmt->fetch()) {  
                echo ' '.$row['id'];  
            }  
        } catch (PDOException $e) {
            file_put_contents('./PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }   
?>