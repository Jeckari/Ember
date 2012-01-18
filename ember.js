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
                      
                        //var item_id = $(ui.draggable).attr('id');
                      ui.helper.remove();
                      var itemID = ui.draggable.attr("id").substr(4);
                      var myClass = $(this).attr("class");
                      var myID = $(this).attr("id");
                      var matches = myClass.split(/\b/);

                      
                      if(myID != "Trash"){
                        ui.draggable.insertAfter(this);
                        $(this).siblings().show(); 
                      } else {
                        ui.draggable.remove();
                      }
                      
                      //alert("id="+ itemID+"&cat="+myID);
                      
                      $.ajax({  
                            type: "POST",  
                            url: "drop.php",  
                            data: "id="+ itemID+"&cat="+myID,
                            success: function(){  
                                $('div.success').fadeIn(600,function(){$('div.success').fadeOut(1200);});     
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
            if ($('.'+cat.replace(/\s/g,'')).length == 0){
                var newdiv = $('<div class="'+cat.replace(/\s/g,'')+' core"><div class="'+cat.replace(/\s/g,'')+' sectionHead droptarget" id="'+cat+'"><h1>'+cat+'</h1></div></div>');
                $('._newNote').after(newdiv);
                make_expander($('div.sectionHead'));
                make_droppable($('.droptarget'));
            }            
            
            var ember = $('<div class="ember" id="'+id+'"><small>'+head+'</small><h1>'+head+'</h1><p>'+body+'</p></div>');
            $('div.'+cat.replace(/\s/g,'')+'.core').append(ember);
            make_draggable(ember);
            $('div.'+cat.replace(/\s/g,'')+'.core div.ember').show(); 
            
            $('body, html').animate({ scrollTop: ember.offset().top }, 1000);

 }
 
  function activate_searchform() {
 
    //New Note Form
            $("form#searchform").submit(function() {  
            // we want to store the values from the form input box, then send via ajax below  
            var text     = $('#searchtext').attr('value');      
            
                $.ajax({  
                    type: "POST",  
                    url: "search.php",  
                    data: "text="+ text,  
                    beforeSend: function() {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        $('div.working').fadeOut(600);
                        $('div.ember').hide();
                        var rows = data.split(' ');
                        for(var i in rows) {
                             if(rows[i].trim().length > 0)
                                $('#note'+rows[i].trim()).show();
                        }
                        if(rows.length > 0)
                            $('body, html').animate({ scrollTop: $('#note'+rows[0].trim()).offset().top }, 1000);
                        
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
                    data: "head="+ head +"&body="+ body + "&cat="+ cat,  
                    beforeSend: function() {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        if(!cat)
                            cat="Unfiled";
                        if(!body)
                            body="No text.";
                        if(!head)
                            head="Note.";
                        
                        new_note(head,body,cat,data);
                        $("form#noteform #head").val('');
                        $("form#noteform #body").val('');                        
                        $('div.working').fadeOut(200);
                        $('div.success').fadeIn(600,function(){$('div.success').fadeOut(1200);});                                    
                    },
                });  
                
            return false;  
            });  
 }
 
 function do_logout() {
                $.ajax({  
                    type: "POST",  
                    url: "logout.php",  
                    success: function(data){  
                        window.location.reload(true);
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
                    url: "login.php",  
                    data: "username="+ username +"&password=0",  
                    beforeSend: function(data) {
                        $('div.working').fadeIn(200);
                    },
                    success: function(data){  
                        
                        
                        var saltypassword = Crypto.SHA256(data.trim()+password, { asBits: true });
                        $.ajax({  
                            type: "POST",  
                            url: "login.php",  
                            data: "username="+ username +"&password="+ saltypassword,  
                            beforeSend: function() {
                                $('div.working').fadeIn(200);
                            },
                            success: function(data){                             
                                
                                $('div.working').fadeOut(200);
                                if(data == "3") {
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
                                }
                                else if(data == "2")
                                    $('div.locked').fadeIn(600,function(){$('div.locked').fadeOut(1200);});                                    
                                else if(data == "1")
                                    $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});                                     
                                else 
                                    $('div.error').fadeIn(600,function(){$('div.error').fadeOut(1200);});                                    
                            },
                        });  
                    },
                });  
                
            return false;  
            });  
    
    }
); 
        