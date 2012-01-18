jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", (($(window).height() - this.outerHeight()) / 2) + $(window).scrollTop() + "px");
    this.css("left", (($(window).width() - this.outerWidth()) / 2) + $(window).scrollLeft() + "px");
    return this;
}

function touchHandler(event)
{
    var touches = event.changedTouches,
    first = touches[0],
    type = "";

     switch(event.type)
    {
        case "touchstart": type = "mousedown"; break;
        case "touchmove":  type="mousemove"; break;        
        case "touchend":   type="mouseup"; break;      
        case "touchcancel":   type="mouseup"; break;
        default: return;
    }

    var simulatedEvent = document.createEvent("MouseEvent");
    simulatedEvent.initMouseEvent( {
      type: mouseEvents[type],
      which: 1,
      pageX: first.pageX,
      pageY: first.pageY,
      screenX: first.screenX,
      screenY: first.screenY,
      clientX: first.clientX,
      clientY: first.clientY
    });

    document.dispatchEvent(simulatedEvent);
    if(type.length > 0)
        event.preventDefault();
}

function init_touch()
{
  $.support.touch = typeof Touch === 'object';

  if (!$.support.touch) {
    return;
  }
  
   document.addEventListener('touchstart.' + self.widgetName, touchHandler, true);
   document.addEventListener('touchmove.' + self.widgetName, touchHandler, true);
   document.addEventListener("touchend." + self.widgetName, touchHandler, true);
   document.addEventListener("touchcancel." + self.widgetName, touchHandler, true);    
}

 function make_droppable(target) {
    target.droppable({
                hoverClass: 'hover',
                over: function() {
                       $(this).addClass('hover');
                },
                out: function() {
                        $(this).removeClass('hover');
                },
                drop: function(event, ui) {
                    ui.helper.remove();
                    var itemID = ui.draggable.attr("id").substr(4);
                    var myClass = $(this).attr("class");
                    var myID = $(this).attr("id");
                    var matches = myClass.split(/\b/);
                    if(myID != "Trash"){
                        //Decrease old count
                        var hits = ui.draggable.siblings().find("h1 .count");
                        var num = 0;
                        if(hits.html().length && hits.html()[0] == '(')
                            num = parseInt(hits.html().substr(1,hits.html().length-1));
                        hits.html('('+(num-1)+')');
                        
                        ui.draggable.insertAfter(this);
                        
                        //Increase new count
                        num = 0;
                        hits = ui.draggable.siblings().find("h1 .count");
                        if(hits.html().length && hits.html()[0] == '(')
                            num = parseInt(hits.html().substr(1,hits.html().length-1));
                        hits.html('('+(num+1)+')');
                        $(this).siblings().show();
                        
                    } else {
                        hits = ui.draggable.siblings().find("h1 .count");
                        hits.html(parseInt(hits.html())-1);                        
                        ui.draggable.remove();
                    }     
                      
                    $.ajax({  
                            type: "POST",  
                            url: "ajax.php",  
                            data: "act=move&id="+ itemID+"&cat="+myID,
                            beforeSend: function() {
                                $('div.working').fadeIn(200);
                            },
                            success: function(data){  
                                $('div.working').fadeOut(600);
                                if(data == ""){ //Error: No data
                                    $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                                    return;
                                }
                                
                                var datacode = data[0];
                                data = data.substr(1);
                                if(datacode == '0') { //Success!
                                    //TODO We should replace the moved/deleted note here.
                                }  else {
                                    $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                                    if(datacode == '2') {//SQL Error
                                        if(data != "")
                                        alert(data);
                                    }
                                }
                            },
                    });
                }
    }); 
 }
 
 function make_draggable(target){
    target.draggable({
                revert: true,
                cursorAt: { top: 0, left: 0 },
                helper: function(event) {
                   return $('<div class="helper">' + $(this).children("small").text() + '</div>');
                },

             });
 }
 
 function make_expander(target){
    target.toggle(
                function() { 
                    $(this).siblings('div.ember').slideToggle(200); 
                }, function() { 
                    $(this).siblings('div.ember').slideToggle(200); return false; 
                }
            ); 
 }
 
 function new_note(head,body,cat,id){
            if(!head) head = "Note";
            if(!body) body = "No text.";
            if(!cat) cat = "Unfiled";
            
            if ($('.'+cat.replace(/\s/g,'')).length == 0){
                var newdiv = $('<div class="'+cat.replace(/\s/g,'')+' core"><div class="'+cat.replace(/\s/g,'')+' sectionHead droptarget" id="'+cat+'"><h1>'+cat+'<span class="count">(1)</span></h1></div></div>');
                $('._newNote').after(newdiv);
                make_expander($('div.sectionHead'));
                make_droppable($('.droptarget'));
            }            
            
            var ember = $('<div class="ember" id="note'+id+'"><small>'+head+'</small><h1>'+head+'</h1><p>'+body+'</p></div>');
            $('div.'+cat.replace(/\s/g,'')+'.core').append(ember);
            make_draggable(ember);
            
            
            return ember;
 }
 
  function activate_searchform() {
 
    //New Note Form
            $("form#searchform").submit(function() {  
            // we want to store the values from the form input box, then send via ajax below  
            var text     = $('#searchtext').attr('value');      
            if(text == ""){
                $('div.ember').hide();
                return;
            }
                $.ajax({  
                    type: "POST",  
                    url: "ajax.php",  
                    data: "act=search&text="+ text,  
                    beforeSend: function() {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        $('div.working').fadeOut(600);
                        if(data == ""){ //Error: No data
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                            return;
                        }
                        
                        var datacode = data[0];
                        data = data.substr(1);
                        if(datacode == '0'){ //Success!
                            $('div.ember').hide();
                            var rows = data.split(' ');
                            for(var i in rows) {
                                 if(rows[i].trim().length > 0)
                                    $('#note'+rows[i].trim()).show();
                            }
                            if(rows.length > 0)
                                $('body, html').animate({ scrollTop: $('#note'+rows[0].trim()).offset().top }, 1000);
                        } else {
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});
                        }
                        
                    },
                });  
                
            return false;  
            });  
 }
 
 function activate_noteform() {
            $("form#noteform").submit(function() {  
            // we want to store the values from the form input box, then send via ajax below  
            var head     = $('#head').attr('value');  
            var body     = $('#body').attr('value');  
            var cat     = $('#cat').attr('value');              
            
                $.ajax({  
                    type: "POST",  
                    url: "ajax.php",  
                    data: "act=create&head="+ head +"&body="+ body + "&cat="+ cat,  
                    beforeSend: function() {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        $('div.working').fadeOut(200);
                        if(data == ""){ //Error: No data
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                            return;
                        }

                        var datacode = data[0];
                        data = data.substr(1);
                        if(datacode == '0'){ //Success!
                            var note = new_note(head,body,cat,data);
                            $('body, html').animate({ scrollTop: note.offset.top }, 1000);
                            $('div.'+cat.replace(/\s/g,'')+'.core div.ember').show(); 
                            $("form#noteform #head").val('');
                            $("form#noteform #body").val('');                        
                            $('div.success').fadeIn(600,function(){$('div.success').fadeOut(1200);});                                    
                            return;
                        } else {
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                            if(datacode == '2') {//SQL Error
                                if(data != "")
                                alert(data);
                            }
                        }
                        
                    },
                });  
                
            return false;  
            });  
 }
 
 function do_logout() {
                $.ajax({  
                    type: "POST",  
                    url: "ajax.php",  
                    data: "act=logout",
                    beforeSend: function() {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        $('div.working').fadeOut(200);
                        if(data == ""){ //Error: No data
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                            return;
                        }
                        var datacode = data[0];
                        data = data.substr(1);
                        if(datacode == '0'){ //Success!
                            window.location.reload(true);
                        } else {
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});
                        }
                    },
                });  
 }
 
function init_notes(){
    make_draggable($('div.ember'));
    make_droppable($('.droptarget'));
    make_expander($('div.sectionHead'));
    
    $('div._toggleAll').toggle(
        function() { 
            $('div.ember').toggle(true); 
        }, function() { 
            $('div.ember').toggle(false); 
            return false; 
        }
    );
    $('div._logout').click(
        function() { 
            do_logout(); 
        }
    );
    activate_noteform();
    activate_searchform();
    $('div.ember').hide(); 
}


 function update_note(head,body,cat,id){
            if($('#note'+id.toString())) //Note already exists, so move it.
                $('#note'+id.toString()).remove();
            new_note(head,body,cat,id);

 }

 setInterval ( 
        function() {
                 $.ajax({  
                    type: "POST",  
                    url: "ajax.php",  
                    data: "act=poll",
                    success: function(data){  
                        if(data == ""){ //Error: No data
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                            return;
                        }
                        var datacode = data[0];
                        data = data.substr(1);
                        if(datacode == '0'){ //Success!
                        var results = jQuery.parseJSON(data);
                        for(var i=0;i<results.length;i++){
                            var row = jQuery.parseJSON(results[i]);
                            update_note(row[0],row[1],row[2],row[3]);
                        }
                        } else {
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});
                        }
                    },
                }); 
        }
    , 2500 );

$(document).ready(

   
    

    function() { 
    init_touch();
    $('div._login').center();
    init_notes();
  
    
    //Login Form
            $("form#loginform").submit(function() {  
            // we want to store the values from the form input box, then send via ajax below  
            var username     = $('#username').attr('value');  
            var password     = $('#password').attr('value');    
            
                
            
            //First get salt
                $.ajax({  
                    type: "POST",  
                    url: "ajax.php",  
                    data: "act=login&user="+ username,  
                    beforeSend: function(data) {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        $('div.working').fadeOut(200);
                        if(data == ""){ //Error: No data
                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);}); 
                            return;
                        }
                        var datacode = data[0];
                        data = data.substr(1);
                        
                        if(datacode == '0') {
                            var saltypassword = Crypto.SHA256(data.trim()+password, { asBits: true });
                            $.ajax({  
                                type: "POST",  
                                url: "ajax.php",  
                                data: "act=login&user="+ username +"&pass="+ saltypassword,  
                                beforeSend: function() {
                                    $('div.working').fadeIn(200);
                                },
                                success: function(data){                             
                                    $('div.working').fadeOut(200);
                                    if(data == ""){ //Error: No data
                                        $('div.error').fadeIn(600,function(){$('div.success').fadeOut(1200);}); 
                                        return;
                                    }
                                    var datacode = data[0];
                                    data = data.substr(1);
                                    if(datacode == '0'){ //Success!
                                        $('div.success').fadeIn(600,function(){$('div.success').fadeOut(1200);});     
                                        $('div._login').fadeOut(1200);
                                        //AJAX grabbing of notes
                                        $.ajax({  
                                            type: "POST",  
                                            url: "noteload.php",  
                                            success: function(data){ 
                                                $('._login').after(data); 
                                                init_notes();
                                            },
                                        });  
                                    } else {
                                        if(datacode == '4') 
                                            $('div.malformed').fadeIn(600,function(){$('div.malformed').fadeOut(1200);});
                                        else if(datacode == '5') //Bad Login
                                            $('div.badlogin').fadeIn(600,function(){$('div.badlogin').fadeOut(1200);});
                                        else if(datacode == '6')
                                            $('div.locked').fadeIn(600,function(){$('div.locked').fadeOut(1200);});
                                        else
                                            $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});
                                    }
                            
                                },
                            });  
                        } else {
                            if(datacode == '4') 
                                $('div.malformed').fadeIn(600,function(){$('div.malformed').fadeOut(1200);});
                            else if(datacode == '5') //Bad Login
                                $('div.badlogin').fadeIn(600,function(){$('div.badlogin').fadeOut(1200);});
                            else
                                $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});
                                        
                        }
                    },
                });  
                
            return false;  
            });  
    
    }
); 
        