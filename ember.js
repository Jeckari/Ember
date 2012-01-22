var myUsername;

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
                    var section = $(this);
                    var myID = section.attr("id");
                    if(myID.substr(0,3) == "cat")
                        myID = myID.substr(3);
                    if(myID != "Trash"){
                        increment_count(ui.draggable,-1);                    
                        ui.draggable.insertAfter(section);
                        increment_count(ui.draggable,+1);                    
                    } else {
                        increment_count(ui.draggable,-1);                                         
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
                    $(this).siblings('div.ember').slideDown(200); 
                }, function() { 
                    $(this).siblings('div.ember').slideUp(200); return false; 
                }
            ); 
 }
 
 function increment_count(ember, amount){
    if(!ember)
        return;
    var num = 0;
    var hits = ember.parent(".core").find("h1 .count");
    if(hits && hits.html()) {
            if(hits.html().length && hits.html()[0] == '(')
                            num = parseInt(hits.html().substr(1,hits.html().length-1));
            hits.html('('+(num+amount)+')');
    }        
 }
 
 function update_note(head,body,cat,id,username){
    var ember = $('#note'+id.toString());
    if(ember) { //Note already exists, so move it.
        increment_count(ember,-1);
        ember.empty().remove();
    }
    
    if(!head) head = "Note";
    if(!body) body = "No text.";
    if(!cat) cat = "Unfiled";
    
    if (!$('div.'+cat.replace(/\s/g,'.core')).length){
        var newdiv = $('<div class="'+cat.replace(/\s/g,'')+' core droptarget" id="'+cat+'"><div class="'+cat.replace(/\s/g,'')+' sectionHead"><h1>'+cat+'<span class="count">(1)</span></h1></div></div>');
        $('div._newNote').after(newdiv);
        make_expander(newdiv);
        make_droppable(newdiv);
    }            
    
    var ember = $('<div class="ember" id="note'+id.toString()+'"><small>'+head+'</small><h1>'+head+'</h1><h2>'+username+'</h2><p>'+body+'</p></div>');
    var section = $('div.'+cat.replace(/\s/g,'')+'.core');
    
    section.append(ember);
    increment_count(ember,+1);                    
    
    make_draggable(ember);
    ember.show();
    
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
                            update_note(head,body,cat,parseInt(data),window.myUsername);
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
                            if(results) {
                                for(var i=0;i<results.length;i++){
                                    var row = jQuery.parseJSON(results[i]);
                                    update_note(row[0],row[1],row[2],parseInt(row[3]),row[4]);
                                }
                            }
                            } else {
                                if(datacode != '1') //If we're not a security failure...
                                    $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});
                            }
                    },
                }); 
        }
    , 2500 );

$(document).ready(
    function() { 
    
    myUsername = "me";
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
                                        window.myUsername = data;
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
        