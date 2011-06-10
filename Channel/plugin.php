<?php

class Channel extends SilverBotPlugin {
	public $trigger = '.';
	public $channels = array();
	
	public function onConnect() {
		foreach ($this->config['autojoin'] as $channel)
			$this->join($channel);
	}
	
	public function onDisconnect() {
		$this->channels = array(); // clear the array so we can connect on reconnect
	}
	
	public function onKick($data) {
		if ($data['kicked'] == $this->bot->nickname) { // bot was kicked
			unset($this->channels[$data['source']]);
			if ($this->config['rejoin_on_kick'] == true)
				$this->join($data['source']);
		}
	}
	
	public function pub_join($data) {
		if ($this->bot->Auth->hasAccess($data['user_host']) && substr($data['data'], 0, 1) == '#') {
			$this->join($data['data']);
		}
	}

	public function pub_part($data) {
		if ($this->bot->Auth->hasAccess($data['user_host'])) {
			if (empty($data['data'])) {
				$this->part($data['source']);
			} else if (substr($data['data'], 0, 1) == '#') {
				$this->part($data['data']);
			}
		}
	}
	
	// joins a channel
	// prefixing $channel w/ # is optional
	private function join($channel) {
		if (substr($channel, 0, 1) == '#') $chan = $channel;
		else $chan = '#' . $channel;
		
		if (!empty($this->channels[$chan])) return; // already in the channel
		$this->channels[$chan] = 1; // this will be extended later
		$string = 'JOIN :' . $chan;
		$this->bot->send($string);
	}

	// leaves a channel
	// prefixing $channel w/ # is optional
	public function part($channel) {
		if (substr($channel, 0, 1) == '#') $chan = $channel;
		else $chan = '#' . $channel;
		
		if (empty($this->channels[$chan])) return; // not in the channel
		unset($this->channels[$chan]);
		$string = 'PART :' . $chan;
		$this->bot->send($string);
	}
	
}

