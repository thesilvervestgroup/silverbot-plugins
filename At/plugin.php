<?php

class At extends SilverBotPlugin {

	private $atTimers = array();
	private $allowedCommands = array('say');

	public function __construct() {
		$this->addTimer('checkTimers', '1 second', array($this, 'checkTimers'));
	}
	
	public function chn_at($data) {
		$bits = explode(' ', $data['data'], 3);
		if (count($bits) != 3) {
			$this->bot->pm($data['username'], 'Invalid usage: !at <time> <command> [addt\'l data]');
			$this->bot->pm($data['username'], 'Example: "!at 15:00 say hello, world!" will cause the bot to PRIVMSG the current channel with "hello world" at 3pm');
			return;
		}
		
		list ($time, $command, $commdata) = $bits;
		
		$ts = strtotime(date('Y-m-d ') . $time);
		if ($ts < time()) {
			$this->bot->pm($data['username'], 'Invalid usage: time must be in the future, in 24-hour format (eg 15:00 for 3pm), and only for the current day');
			return;
		}
		
		if (!in_array(strtolower($command), $this->allowedCommands)) {
			$this->bot->pm($data['username'], "Invalid usage: '$command' is not a valid command");
			return;
		}
		
		switch (strtolower($command)) {
			case 'say':
				if (empty($commdata)) {
					$this->bot->pm($data['username'], 'Invalid usage: \'say\' command needs something to say!');
					return;
				}
				break;
		}
		
		$this->atTimers[] = array('due' => $ts, 'method' => strtolower($command), 'source' => $data['source'], 'data' => $commdata);
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
				$this->bot->pm($timer['source'], $timer['data']);
				return;
				
		}
	}
	
}

