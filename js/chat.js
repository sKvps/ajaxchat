/*
   File: chat.js
   Caller: index.html (via <script> tag)
   Purpose: This is the AJAX chat JavaScript file
   Author: Yaakov Relkin
   Version: 0.8.7
*/

$(document).ready(ready);
var user = {}, conversations=[];
var buddiesPoller = {
	bud:null,
	poll:function() {
		this.bud = setInterval(pollBuddies, 3000);
	},
	stopPolling:function() {
		this.bud = clearInterval(this.bud);
	}
}
function ready() {
	$("#loader").css({left:screen.availWidth/2-110});
	$("input [type='button']").hover(
		function() { $(this).addClass('ui-hover-state'); }, 
		function() { $(this).removeClass('ui-hover-state'); }
	);
	$('#buddyList').dialog(
						   {
							   resizable:false,
							   draggable:false,
							   position:'left', 
							   width:'200px', 
							   close: function(event, ui) {
								   if($("#login").css("display")=="block") return;
								   setTimeout(function() {
									   $("#buddyList").dialog('open');
													   }, 50);
							   },
							   height:'300px', 
							   buttons: {
								   'Logout':logout
								}
						   }
						   );
	$("#buddyList").dialog('close');
	$("#login_submit").click(login);
	checkLogin();
}
function logged_in() {
	$("#login").slideUp();
	buddiesPoller.poll();
	$("#mainWindow").slideDown();
	$("#buddyList").dialog('open');
	$("#chatStatus").slideDown();
	$("#loader").slideUp();
}
function login() {
	$("#loader").slideDown();
    $.ajax({
		   type:"POST",
		   url: "app/chat.php?action=login",
		   data: "username="+$("#username").val()+"&password="+$("#password").val(),
		   dataType:"json",
		   success:function(json) {
			   if(json.login_status != undefined) {
			        if(json.login_status == true) {
						user = json.userData;
						logged_in();
						$("#chatStatus").html(json.status);
					}
					else {
						 $("#login").effect("shake", { times: 3 }, 100);
					     $("#loader").slideUp();
					}
			   } else {
					 $("#login").effect("shake", { times: 3 }, 100);
					 $("#loader").slideUp();
			   }
		   }
		   });
}
function checkLogin() {
	$("#loader").slideDown();
    $.ajax({
		   url:"app/chat.php?action=init",
		   type:"POST",
		   dataType:"json",
		   success:function(json) {
		       if(json.login_status==true) {
				     user = json.userData;
			         logged_in();
					 $("#chatStatus").html(json.status);
			   } else {
			   	$("#loader").slideUp();
			   }
		   }
		   });
}
function newConversation(to) {
	$("#loader").slideDown();
	$.ajax({
		   url:"app/chat.php?action=new_conv",
		   type:"POST",
		   data:"from="+user.user_id+"&to="+to,
		   dataType:"json",
		   success:function(json) {
			   if(json.error != undefined) {
			   		alert(json.error);
					return;
			   }
			   if(json.Conversation != undefined) {
			   		var convObj = document.createElement('div');
					var chatArea = document.createElement('div');
					var chatTextArea = document.createElement('div');
					$(chatArea).css({fontSize:"12px", minHeight:"150px", maxHeight:"175px", width:"95%", overflow:"auto", marginLeft:"-15px"});
					$(chatArea).attr("id", "chatArea_"+json.Conversation.conv_id);
					$(chatTextArea).html("<input type='text' size='100' id='chatText_"+json.Conversation.conv_id+"' value='' style='margin-top:10px; font-size:14pt; font-family:Verdana, Helvetica, sans-serif; height:50px; width:350px;'></textarea>");
					$(convObj).append(chatArea);
					$(convObj).append(chatTextArea);
					$(convObj).css({height:"400px", width:"450px"});
					json.Conversation.conv_id = json.Conversation.conv_id.toString();
					var conv_obj = {
						obj:"#conv_"+json.Conversation.to.user_id,
						conv_id: json.Conversation.conv_id,
						messages:json.Conversation.messages,
						lastID:0,
						getLastID:function() { conv_obj.lastID = getHighestMsgId(conv_obj.messages); },
						firstFlag:true,
						poller:setInterval(function() {
													pollMessagesFor(conv_obj, conv_obj.firstFlag);
										   }, 2000)
					};
					conversations[json.Conversation.conv_id] = conv_obj;
					if(json.Conversation.messages.length > 0) {
						$("#chatArea_"+json.Conversation.conv_id).html(parseMessages(json.Conversation.messages));
					} else {
						$("#chatArea_"+json.Conversation.conv_id).html("Please wait...");
					}
					var id = 'conv_'+json.Conversation.to.user_id;
					$(convObj).attr({'title':'Chat with '+ucfirst((json.Conversation.to.username == user.username && json.Conversation.from != undefined) ? json.Conversation.from.username : json.Conversation.to.username), 'id':id});
					if(document.getElementById(id) == undefined) {
						$(document.body).append(convObj);
						$("#chatText_"+json.Conversation.conv_id).keydown(function(event) {
																				   if(event.keyCode==13) {
																						sMsg(json.Conversation.conv_id, json.Conversation.to);
																						return;
																				   }
																				   });
						$("#"+id).dialog({
									  autoOpen:false,
									  width:'475px',
									  height:'400px',
									  close: function(event, ui) {
										  	clearInterval(conversations[json.Conversation.conv_id].poller);
									  },
									  buttons: {
										  'Send':function() {
											  sMsg(json.Conversation.conv_id, json.Conversation.to);
										  },
										  'Close':function(){
										  	$(this).dialog('close');
										  }
										  
									  }
									  });
					}
					else {
						$("#"+id).parent().parent().css({display:"block"});
					}
					$("#"+id).dialog('open');
					$("#loader").slideUp();
			   }
		   }
		   });
}
function getHighestMsgId(messages) {
	var h = 0;
	for(var i in messages) {
		var msg_id = messages[i].msg_id;
		if(msg_id > h) h = msg_id;
	}
	return h;
}
function sMsg(id, to) {
	sendMessage(id, to);
}
function parseMessages(messages) {
	var str = "";
	if(typeof messages != "object") return false;
	for(var i=0; i < messages.length; i++) {
		var msg=messages[i];
		str += "<span class='message'><span class='nameString'>"+ucfirst(msg.from)+"</span> said at : <b style='font-size:10pt;'>"+msg.tme+"</b>&nbsp;&nbsp<span class='chatString'>"+unescape(msg.message)+"</span></span><br/>";
	}
	return str;
}
function sendMessage(conv, to) {
	if($("#chatText_"+conv).val() == "") {
		alert("Sorry, you must enter something in the chat box before sending your message!");
		return;
	}
	$("#loader").slideDown();
	$.ajax({
		   	url:"app/chat.php?action=send_message",
			type:"POST",
			data:"message="+escape($("#chatText_"+conv).val())+"&conv_id="+conv,
			success: function(r) { 
				$("#chatText_"+conv).val("");
				$("#loader").slideUp(); 
			}
		   });
}

function pollMessagesFor(conv_obj, first) {
	if(first) conv_obj.firstFlag = false;
	conv_obj.getLastID();
	var lastID = conv_obj.lastID;
	$.ajax({
		   url:"app/chat.php?action=get_messages",
		   type:"POST",
		   data:"conv="+conv_obj.conv_id+"&lastID="+lastID+"&first="+toBoolean(first),
		   dataType:"json",
		   success:function(json) {
			   if(json.error!=undefined) {
				   if(json.error=="Table was deleted!") {
				   		for(var i in conversations) {
							 var conv = conversations[i];
							 conv.poller = clearInterval(conv.poller);
							 $(conv.obj).slideUp();
						}
				   }
			   }
			   var objStr = "#chatArea_"+conv_obj.conv_id;
			   if(json.messages==undefined) return;
			   if(!first) {
				   $(objStr).append(parseMessages(json.messages));
				   conversations[conv_obj.conv_id].messages = add_to_array(conversations[conv_obj.conv_id].messages, json.messages);
			   } else {
				   conversations[conv_obj.conv_id].messages = json.messages;
			   	   $(objStr).html(parseMessages(json.messages));
			   }
			   $(objStr).scrollTo(10000);
		   }
		   });
}
function add_to_array(array_target, array_source) {
	var newTarget = array_target;
	for(var s in array_source) {
		newTarget.push(array_source[s]);
	}
	return newTarget;
}
function logout() {
	$("#loader").slideDown();
	$.ajax({
		   url:"app/chat.php?action=logout",
		   dataType:"json",
		   success:function(json) {
				for(var i in conversations) {
					var conv = conversations[i];
					conv.poller = clearInterval(conv.poller);
					$(conv.obj).dialog('close');
				}
			    if(json.login_status === true) {
					$("#login").slideDown()
					buddiesPoller.stopPolling();
					$("#mainWindow").slideUp();
					$("#buddyList").dialog('close');
					$("#chatStatus").slideUp();
					setTimeout(function(){alert("You have been successfully logged out!\nThanks for using Yaakov's Chat System!");}, 500);
				} else {
					alert("Technical issues occurred while logging you out.");
					window.location.reload();
				}
				$("#loader").slideUp();
		   }
		   });
}
function pollBuddies() {
	$.ajax({
		   url:"app/chat.php?action=poll",
		   dataType:"json",
		   success:function(json) {
			   if(json.status != undefined) {
					   alert(json.status);
					   logout();
					   return;
			   }
			   $("#status").html("<b>Status</b><span id='statSpan' style='margin:10px; border:thin solid #fff;padding:5px;'> "+ucfirst(user.username)+" "+json.my_status.me.status+" ("+json.my_status.me.stat_time+")</span>");
			   $("#statSpan").css("cursor", "pointer");
			   $("#statSpan").click(function() {editStatus(this);});
			   if(json.buddies==undefined || json.buddies.length <= 0) {
				    if(document.getElementById("blStatus") != null) {
						$("#blStatus").slideDown();
						return;
					}
				  	var nBLdiv = document.createElement('div');
					$(nBLdiv).html("You have no buddies online.");
					$(nBLdiv).css({marginLeft:'-20px'});
					$(nBLdiv).attr("id", "blStatus");
	   			    $("#buddyList").empty();
			   		$("#buddyList").dialog('option', 'title', "Buddy List (0)");
					$("#buddyList").append(nBLdiv);
					for(var i in conversations) {
						$(conversations[i].obj).parent().parent().slideUp();
					}
					return;
			   }
			   if(json.my_status.error!=undefined) {
				   	var er = json.my_status.error;
					if(typeof console.log == 'function') {
						console.log(er);
					} else {
						alert(er);
					}
					return;
			   }
			   if(document.getElementById("blStatus")) {
			   		$("#blStatus").slideUp();
			   }
  			   $("#buddyList").parent().children(0).html("Buddy List ("+json.buddies.length+")");
			   $("#buddyList").empty();
			   for(var i in json.buddies) {
			   		$("#buddyList").append("<li style='margin-left:-10px'><a href=\"#\" onclick=\"newConversation('"+json.buddies[i].user_id+"')\">"+ucfirst(json.buddies[i].username)+"</a><br/><div class='buddyStatus'>"+json.buddies[i].status+"</div></li>");
			   }
			   $("#buddyList").append("</ul>");
		   }
		   });
}
function editStatus(obj) {
	 buddiesPoller.stopPolling();
	 var tb = document.createElement('input');
	 $(tb).attr({type:"text", value:"", id:"statUpdate"});
	 $(tb).css({backgroundColor:"#111", border:"thin solid #ccc", padding:"2px", color:"#fff", fontSize:"10pt", marginLeft:"8px"});
	 $(tb).keydown(function(event) {
							if(event.keyCode==13) {
								  $(obj).attr("disabled", "disabled");
								  $(obj).parent().html("Please wait...");
								  var val = $(this).val();
 								  $.ajax({
											url:"app/chat.php?action=set_user_status",
											type:"POST", 
											data:"status="+val,
											dataType:"json",
											success:function(json) {
												if(json.result == true) alert("Your status has been updated!");
												else alert(json.result);
												buddiesPoller.poll();
												$("#statUpdate").replaceWith(obj);
											}
										 });
							}
							});
	$(obj).replaceWith(tb);
}
function print_r(obj) {
	var s=[];
	for(var i in obj) {
		s.push((in_array(ucfirst(typeof obj[i]), ['Object', 'String', 'Number', 'Boolean']) ? ucfirst(typeof(obj[i]))+" Property \""+i+"\": (\n"+obj[i]+"\n)" : "Method \""+i+"\": (\n"+((typeof obj[i] == "object") ? print_r(obj[i]) : obj[i]) +"\n)") );
	}
	s=s.join(", ");
	return "{\n"+s+"\n}";
}
function toBoolean(obj) {
	if(typeof obj == "object" && obj.length >= 1) return true;
	else if(typeof obj == "boolean") return obj;
	else if(typeof obj == "string" && obj.length >=1) return true;
	return false;
}
function in_array(needle, array) {for(var i in array) {if(array[i]==needle) return true; } return false;}
function ucfirst(str) {
	if(str.length < 1) return str;
        return str.substring(0, 1).toUpperCase()+str.substring(1, str.length());
}