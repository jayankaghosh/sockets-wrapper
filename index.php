<!DOCTYPE html>
<html>
<head>
	<title>Chatbox</title>
	<script type="text/javascript" src="lib/js/Socket.js"></script>
	<style type="text/css">
		#chat-container{
			width:40%;
			border:1px solid silver;
		}
		#messages{
			height: 200px;
			overflow-y: auto;
			margin-bottom: 5px;
		}
		#new-message{
			width: 80%;
			padding: 5px 10px;
		}
	</style>
</head>
<body>
	<div id="chat-container">
		<div id="messages"></div>
		<input type="text" id="new-message">
	</div>	

	<script type="text/javascript">
		
		/*
		 * Capturing the user's nickname
		 */
		var user = {};
		while(!user.nickname){
			user.nickname = prompt("Enter a nickname");
		}

		/*
		 * Assigning a random color to the user
		 */
		var colors = ["red", "green", "blue", "black", "grey"];
		user.color = 	colors[(Math.floor(Math.random() * (colors.length-1 - 0 + 1)) + 0)];

		user = JSON.stringify(user);


		/*
		 * Registering a new socket
		 */
		var socket = new Socket({
			'socketUrl': 'ws://127.0.0.1:8080/socketphp/Server.php'
		});
		
		
		/*
		 * Binding callbacks to the various socket events 
		 */
		socket.onopen = function(ev){
			this.sendMessageToSocket({
				'event' 	: 'info',
				'message'	: ' has joined the chatroom',
				'sender'	: user
			})
		}
		socket.onmessage = function(ev){
			var data = JSON.parse(ev.data);
			addMessage(data.sender, data.message, data.event);
		}
		socket.onerror = function(ev){
			alert("error");
		}
		socket.onclose = function(ev){
			alert("connection closed");
		}

		/*
		 * Method to display a message in the chatroom
		 */
		function addMessage(sender, message, type){
			sender = JSON.parse(sender);
			var messageBox = document.getElementById("messages");
			var newMessage = document.createElement("div");
			newMessage.innerHTML = "<b style='color:"+sender.color+"'>"+sender.nickname+":</b> "+message;
			newMessage.className += type;
			messageBox.appendChild(newMessage);
		}

		document.getElementById('new-message').addEventListener('keyup', function(e){
			if(e.keyCode === 13){
				var message = this.value;
				socket.sendMessageToSocket({
					'event'		: 	'message',
					'message'	: 	message,
					'sender'	: 	user
				});
				this.value = "";
			}
		});

		socket.start();
	</script>
</body>
</html>