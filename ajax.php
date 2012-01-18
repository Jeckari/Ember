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
        
        if($cat == "Trash" ) { //No sense creating something for the trash.
            die();
        }
        
        if(empty($body) || strlen(trim($body)) == 0)
            $body = "No text.";
            
        if(empty($head) || strlen(trim($head)) == 0)
            $head = "Note";
        
        if(empty($cat) || strlen(trim($cat)) == 0)
            $cat = "Unfiled";
            
        $data = array( 
            'head' => $head,
            'cat' => $cat,
            'body' => $body,
        );
        try {
            $stmt = $DBH->prepare('INSERT INTO notes (head,body,category) VALUES (:head,:body,:cat)');
            $stmt->execute($data);
            $stmt = $DBH->prepare('SELECT MAX(ID) FROM notes where head = :head');
            $stmt->execute($data);
            $stmt->setFetchMode(PDO::FETCH_BOTH);    
            if($row = $stmt->fetch()) {
                echo $row[0];
            } 
        } catch (PDOException $e) {
            echo $e->getMessage();
            die();
        }
?>