var Socket = function(socketInfo){
	this.socketUrl = socketInfo.socketUrl;
	if(!socketInfo.timer){
		this._timer = false;
	}
	else{
		this._timer = socketInfo.timer;
	}
}

Socket.prototype = {
	start: function(){
		this.registerSocket(this.socketUrl);
	},
	registerSocket: function(socketUrl){
		var self = this;
		self.websocket = new WebSocket(socketUrl);
		self.websocket.onopen = self.onopen.bind(self);
		self.websocket.onmessage = self.onmessage.bind(self);
		self.websocket.onerror = self.onerror.bind(self);
		self.websocket.onclose = self.onclose.bind(self);
		if(self._timer){
			setTimeout(function(){
				self.websocket.close();
			}, self._timer*1000);
		}
	},
	sendMessageToSocket: function(msg){
		if(typeof msg === "object") msg = JSON.stringify(msg);
		this.websocket.send(msg);
	},
	onopen: function(ev) {},
	onmessage: function(ev) {},
	onerror: function(ev){},
	onclose: function(ev){},
	notify: function(message){
		Notification.requestPermission().then(function(result) {
		  	var notification = new Notification(message);
		});
	},
	warnBeforeClose: function(warn){
		if(warn)	window.addEventListener("beforeunload", this.registerCloseBeforeListener);
		else window.removeEventListener("beforeunload", this.registerCloseBeforeListener, false);
	},
	registerCloseBeforeListener: function (e) {
		var confirmationMessage = 'It looks like you have been editing something. '
	                            + 'If you leave before saving, your changes will be lost.';

	    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
	    return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
	}
}