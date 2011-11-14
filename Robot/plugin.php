<?php

class Robot extends SilverBotPlugin {

	private $data = array();
	private $dataFile = '';

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

