<?php

/**
* A wrapper class for the socket functionalities in PHP
*
* DISCLAIMER
*
* Do not edit or add to this file as it is an abstract class. If you wish to
* customize the Socket class for your needs please extend it in your custom 
* class to make modificatons.
*
* @author Jayanka Ghosh < https://github.com/jayankaghosh >
* 
*/
abstract class Socket
{
	protected $_host, $_port, $_null, $_socket, $_socketAllowed, $_clients;

	/*
	 * when new client joins the session
	 * @param (Socket) clientSocket
	 */
	abstract protected function onNewClient($socket);

	/*
	 * when client sends data
	 * @param (String) data, (Socket) client sending the data
	 */
	abstract protected function onIncomingData($incomingData, $socket);

	/*
	 * when a client is removed from the session
	 * @param (Socket) clientSocket
	 */
	abstract protected function onClientRemoved($socket);

	/*
	 * before the socket session is closed
	 */
	abstract protected function beforeSocketClose();

	/*
	 * when the socket session closes
	 */
	abstract protected function onSocketClose();

	/*
	 * This method is called before every iteration of the socket loop
	 * The logic to stop the iteration and end the socket session may be implemented here
	 */
	abstract protected function onNextIteration();


	/*
	 * @params
	 * (String) $host where the socket is running
	 * (String) $port on which to run the socket 
	 */
	public function __construct($host = "localhost", $port = "9000"){
		$this->_host = $host;
		$this->_port = $port;
		$this->_null = NULL;
		$this->_socketAllowed = false;
		$this->_clients = array();
	}

	/*
	 * Start the socket loop
	 */
	public function startSocket(){
		$this->_socketAllowed = true;
		$this->createSocket();
		$this->bindSocket();
		$this->startListeningOnSocket();
	}


	/*
	 * Stop the socket loop
	 */
	public function stopSocket(){
		$this->_socketAllowed = false;
	}



	protected function createSocket(){
		//Create TCP/IP sream socket
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		//reuseable port
		socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1);
	}

	protected function bindSocket(){
		//bind socket to specified host
		socket_bind($this->_socket, 0, $this->_port);
	}

	protected function startListeningOnSocket(){
		//listen to port
		socket_listen($this->_socket);
		$this->_clients[] = $this->_socket;
		$this->_startListeningOnSocket();
	}

	protected function getClients(){
		return $this->_clients;
	}

	private function _startListeningOnSocket(){
		while ($this->_socketAllowed) {
			//manage multipal connections
			$changed = $this->_clients;
			//returns the socket resources in $changed array
			socket_select($changed, $null, $null, 0, 10);
			//check for new socket
			if (in_array($this->_socket, $changed)) {
				$socket_new = socket_accept($this->_socket); //accpet new socket
				$this->_clients[] = $socket_new; //add socket to client array
				$header = socket_read($socket_new, 1024); //read data sent by the socket
				$this->_performHandshaking($header, $socket_new, $this->_host, $this->_port); //perform websocket handshake
				//new socket found callback
				$this->onNewClient($socket_new);
				//make room for new socket
				$found_socket = array_search($this->_socket, $changed);
				unset($changed[$found_socket]);
			}
			
			//loop through all connected sockets
			foreach ($changed as $changed_socket){
				
				//check for any incoming data
				while(@socket_recv($changed_socket, $buf, 1024, 0) >= 1)
				{
					$received_text = $this->unmask($buf); //unmask data
					//incoming data callback
					$this->onIncomingData($received_text, $changed_socket);
					break 2; //exist this loop
				}
				
				$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
				// check disconnected client
				if ($buf === false) {
					// remove client for $clients array
					$found_socket = array_search($changed_socket, $this->_clients);
					//socket removed callback
					$this->onClientRemoved($changed_socket);
					unset($this->_clients[$found_socket]);
				}
			}
			$this->onNextIteration();
		}
		// close the listening socket
		$this->beforeSocketClose();
		socket_close($this->_socket);
		//socket closed callback
		$this->onSocketClose();
	}


	//handshake new client.
	private function _performHandshaking($receved_header,$client_conn, $host, $port){
		$headers = array();
		$lines = preg_split("/\r\n/", $receved_header);
		foreach($lines as $line)
		{
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
			{
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		//hand shaking header
		$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"WebSocket-Origin: $host\r\n" .
		"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
		"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($client_conn,$upgrade,strlen($upgrade));
	}


	public function getIPFromSocket($socket){
		@socket_getpeername($socket, $ip);
		return $ip;
	}

	public function sendMessageToAll($msg){
		foreach($this->_clients as $socket)
		{
			$this->sendMessage($socket, $msg);
		}
		return true;
	}

	public function sendMessage($socket = null, $message = ""){
		$message = $this->mask($message);
		@socket_write($socket,$message,strlen($message));
	}


	//Unmask incoming framed message
	private function unmask($text) {
		$length = ord($text[1]) & 127;
		if($length == 126) {
			$masks = substr($text, 4, 4);
			$data = substr($text, 8);
		}
		elseif($length == 127) {
			$masks = substr($text, 10, 4);
			$data = substr($text, 14);
		}
		else {
			$masks = substr($text, 2, 4);
			$data = substr($text, 6);
		}
		$text = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$text .= $data[$i] ^ $masks[$i%4];
		}
		return $text;
	}

	//Encode message for transfer to client.
	private function mask($text)
	{
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($text);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$text;
	}

}