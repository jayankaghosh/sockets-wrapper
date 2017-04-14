<?php
/**
* A simple class to implement a timer in PHP
*
* @author Jayanka Ghosh < https://github.com/jayankaghosh >
* 
*/
class Timer{
	
	private $_time, $_starttime;

	/*
	 * The Timer constructor
	 * @param (int) $time in seconds
	 */
	public function __construct($time){
		$this->_time = $time;
	}

	/*
	 * To start the timer
	 */
	public function start(){
		$this->_starttime = time();
	}

	/*
	 * To get the time remaining
	 * @return (int) $time left in seconds
	 */
	public function remaining(){
		$now = time()-$this->_starttime;
		return $this->_time - $now;
	}

	/*
	 * To check if time is up
	 * @return (boolean) Time up or not
	 */
	public function over(){
		$now = time()-$this->_starttime;
		if($now > $this->_time){
			return true;
		}
		return false;
	}
}