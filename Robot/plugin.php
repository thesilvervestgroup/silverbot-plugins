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

	public function chan($data) {
		$regex = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
		if (preg_match($regex, $data['text'], $matches) !== false) {
			$url = $matches[1];
			// url was found
			if (@isset($this->data['urls'][$url])) {
				// roboto
				$this->bot->reply('ROBOT');
			} else {
				$this->data['urls'][$url] = time();
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

}

