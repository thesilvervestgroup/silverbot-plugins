<?php

class Robot extends SilverBotPlugin {

	private $data = array();
	private $dataFile = '';
	private $muted = array();

	public function __construct() {
		$this->dataFile = $this->getDataDirectory() . 'savedata.db';
		$this->load();
		$stat = stat($this->dataFile);
		$this->savemtime = $stat['mtime'];
	}

	public function chn($data) {
		$regex = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
		if (preg_match($regex, $data['text'], $matches)) {
			$url = $matches[1];
			// url was found
			if (@isset($this->data['urls'][$url])) {
				$info = $this->data['urls'][$url];
				$diff = time() - $info['w'];
				$str = "ROBOT (".$this->ago($diff)." by " . $info['u'] . ")";
				// roboto
				if (!isset($this->data['muted'][$data['source']]) || $this->data['muted'][$data['source']] != true)
					$this->bot->reply($str);
			} else {
				$this->data['urls'][$url]['w'] = time();
				$this->data['urls'][$url]['u'] = $data['username'];
			}
		}

		// auto-save after five minutes worth of chat (minimum, might be more depending on time between messages)
		if ((time() - $this->savemtime) > 300)
			$this->save();
	}
	
	public function chn_robot($data) {
		switch (trim(strtolower($data['data']))) {
			case 'off':
				$this->data['muted'][$data['source']] = true;
				$this->save();
				$this->bot->reply("Muted ROBOT for {$data['source']}");
				break;
				
			case 'on':
				$this->data['muted'][$data['source']] = false;
				$this->save();
				$this->bot->reply("Now ROBOT'ing for {$data['source']}");
				break;
				
			case 'stat':
			case 'stats':
			case 'status':
				$users = array();
				$links = $oldest = 0;
			
				foreach ($this->data['urls'] as $url) {	
					if ($oldest == 0) $oldest = $url['w'];
					$users[$url['u']]++;
					$links++;
					if ($url['w'] < $oldest) $oldest = $url['w'];
				}
				
				arsort($users);
				$most_user = current(array_flip($users));
				$most_links = current($users);
				
				$this->bot->reply("Number of links in ROBOT cache: $links");
				$this->bot->reply("$most_user has submitted the most links with $most_links");
				break;
		}
	}
	
	public function pub_link($data) {
		$s = $data['data'];

		// make it regex'd
		$from = array('0','1','2','3','4','5','6','7','8','9',' ');
		$to = array('(0|zero)','(1|one)','(2|two)','(3|three)','(4|four)','(5|five)','(6|six)','(7|seven)','(8|eight)','(9|nine)','.*');
		$s = str_replace($from, $to, $s);
		$s = '/.*' . $s . '.*/i';
		
		$links = array_keys($this->data['urls']);
		$matched = array();
		foreach ($links as $link) {
			if (preg_match($s, $link, $matches) == true) {
				$matched[] = $link;
			}
		}
		
		if (count($matched)) {
			$this->bot->reply("Most recent links matching '{$data['data']}'");
			foreach ($matched as $url) {
				$times[$url] = $this->data['urls'][$url]['w'];
			}
			
			arsort($times);
			$count = 1;
			foreach ($times as $url=>$time) {
				if ($count == 4) break;
				$this->bot->reply("$count: $url");
				$count++;
			}
			
		} else {
			$this->bot->reply("No links found matching '{$data['data']}'");
		}
	}	

	private function reset() {
		unset($this->data);
		$this->data['since'] = time();
		$this->save();
	}

	private function save() {
		file_put_contents($this->dataFile, serialize($this->data)); // but data is serialized, since it's an array
		$this->savemtime = time();
	}

	private function load() {
		if (file_exists($this->dataFile))
			$this->data = unserialize(file_get_contents($this->dataFile));
		else
			$this->reset(); // we wanna init the data file if we can't find it
	}

	private function ago($timestamp)
	{
		$difference = $timestamp;
		
		$periods = array("s", "m", "h", "d", "w", "m", "y", "d");
		$lengths = array("60","60","24","7","4.35","12","10");
		for($j = 0; $difference >= $lengths[$j]; $j++)
		{
			$difference /= $lengths[$j];
		}
		
		$difference = round($difference);		
		$text = "$difference $periods[$j] ago";
		return $text;
	}
}

