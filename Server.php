<?php

//path to the php libraries
$path = 'lib/php/';

//to check when to stop the socket
require_once $path.'Timer.php';

//the main socket class
require_once $path.'Socket.php';


class Server extends Socket{

	protected $timer;

	private $_users = array();

	public function __construct(){
		$this->timer = new Timer(2000);
		parent::__construct("127.0.0.1", "8080");
		$this->timer->start();
		$this->startSocket();
	}

	protected function onNextIteration(){
		if($this->timer->over()){
			$this->stopSocket();
		}
	}

	protected function onNewClient($socket){

	}

	protected function onIncomingData($incomingData, $socket){
		$this->sendMessageToAll($incomingData);
	}

	protected function onClientRemoved($socket){

	}

	protected function beforeSocketClose(){

	}

	protected function onSocketClose(){

	}

}

$t = new Server();
