<?php

class Timer extends SilverBotPlugin {

	private $atTimers = array();
	private $allowedCommands = array('say','pm');

	public function __construct() {
		$this->addTimer('checkTimers', '1 second', array($this, 'checkTimers'));
	}
	
	private function usage($data) {
		$this->bot->pm($data['username'], 'Invalid usage: !timer <period> <say|pm> [username|message,...]');
		$this->bot->pm($data['username'], 'Example: "!timer 1 hour say hello, world!" will cause the bot to PRIVMSG the current channel with "hello world" in one hour');
		$this->bot->pm($data['username'], 'Also: "!timer 1 hour pm me hello, world!" will cause the bot to PRIVMSG your current username with "hello world" in one hour');
		return;
	}
	
	public function chn_timer($data) {		
		$str = $data['data'];
		$pieces = explode(' ', $str);
		foreach ($pieces as $i=>$piece) {
			if (($pos = array_search(strtolower($piece), $this->allowedCommands)) !== false) {
				// get the command
				$cmd = $this->allowedCommands[$pos];
				$cmdpos = strpos($str, $cmd);
				
				// get the time period
				$time = substr($str, 0, $cmdpos - 1);
				$time = strtotime($time);
				if (!$time) { $this->usage($data); continue; } // invalid time
				
				// get the extra params
				switch ($cmd) {
				    case 'pm':
				        $who = $pieces[$i+1];
				        if (empty($who)) { $this->usage($data); break(2); } // might be a user, but no string found so fail
				        $string = substr($str, strpos($str, $who) + strlen($who) + 1);
				        if ($who == 'me') $who = $data['username'];
				        if (empty($string))  { $this->usage($data); break(2); } // user found, but no string to send
				        break;
				    default:
				        $who = $data['source'];
				        $string = substr($str, $cmdpos + strlen($cmd) + 1);
				}
				
				$this->atTimers[] = array('due' => $time, 'method' => $cmd, 'who' => $who, 'data' => $string);
				return;
			}
		}
		
		// no command found
		$this->usage($data);
	}
	
	protected function checkTimers() {
		if (count($this->atTimers)) foreach ($this->atTimers as $key=>$timer) {
			if ($timer['due'] < time()) {
				$this->execTimer($timer);
				unset($this->atTimers[$key]);
			}
		}
	}
	
	protected function execTimer($timer) {
		switch ($timer['method']) {
			case 'say':
			case 'pm':
				$this->bot->pm($timer['who'], $timer['data']);
				return;
				
		}
	}
	
}

