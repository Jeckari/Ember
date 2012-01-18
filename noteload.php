<?php  
    @session_start();
    include_once ("./cgi-bin/notedb.php");   
        
    //Check whether the session variable SESS_MEMBER_ID is present or not
    if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) { //Begin insecure
    echo "You must be logged in to see notes.";
    } //End insecure
    else { //Begin secure    
?>
    <div class="_toolbar">
        <div class="_toggleAll">
            <h1>Toggle All</h1>
        </div>
        <div class="_logout">
            <h1>Logout</h1>
        </div>
        <div class="_search">  
            <form id="searchform" method="post">  
                <label for="searchtext">Search:</label><input id="searchtext" type="text" name="searchtext" />
                <button> Search </button>  
            </form>  
        </div>
    </div>
        <div class="_clearer">
        </div>

<div class="_newNote">  
        <div class = "sectionHead" style="background: #808080;">
            <h1>New Note</h1>
        </div>
        <div class = "ember">
            <form id="noteform" method="post">  
                <TEXTAREA id="cat" NAME="cat" ROWS=1 COLS=12 placeholder="Unfiled"></TEXTAREA> <br/>
                <TEXTAREA id="head" NAME="head" ROWS=1 COLS=48 placeholder="Note Header"></TEXTAREA> 
                <TEXTAREA id="body" NAME="body" ROWS=3 COLS=64 placeholder="Note Body"></TEXTAREA> <br/>
                <button> Add Note </button>  
            </form>  
        </div>

        <div class="success" style="display: none;">Database updated.</div>  
        <div class="error" style="display: none;">An error has occured.</div>  
        <div class="working" style="display: none;">Working. Please wait...</div>  
</div>
<?php 
        print_notes();
?>
<div class="_trash">  
        <div class = "sectionHead droptarget" id="Trash" style="background: #808080;">
            <h1>Trash</h1>
        </div>
</div>
<?php 
        } //End secure
?>