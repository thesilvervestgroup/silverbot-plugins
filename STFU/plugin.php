<?php

class STFU extends SilverBotPlugin {

	private $data = array();
	private $expletives = array();
	private $savemtime = 0;
	private $dataFile = '';
	private $swearFile = '';

	public function __construct() {
		$this->dataFile = $this->getDataDirectory() . 'savedata.db';
		$this->swearFile = $this->getDataDirectory() . 'expletives.db';
		$this->load();
		$stat = stat($this->dataFile);
		$this->savemtime = $stat['mtime'];
	}

	public function pub($data) {
		if (empty($this->data[$data['source']]['user'][$data['username']]))
			$this->data[$data['source']]['user'][$data['username']] = array('c' => 0, 'l' => 0, 'w' => 0, 'e' => 0, 'swears' => array());
		if (empty($this->data[$data['source']]['glob']))
			$this->data[$data['source']]['glob'] = array('c' => 0, 'l' => 0, 'w' => 0, 'e' => 0, 'swears' => array());
		
		$len = strlen($data['text']);
		$words = count(explode(' ', $data['text']));
		$this->data[$data['source']]['user'][$data['username']]['c'] += $len; // add string length to char count
		$this->data[$data['source']]['glob']['c'] += $len;
		$this->data[$data['source']]['user'][$data['username']]['w'] += $words; // increase word count
		$this->data[$data['source']]['glob']['w'] += $words;
		$this->data[$data['source']]['user'][$data['username']]['l']++; // increase line count
		$this->data[$data['source']]['glob']['l']++;

		// this is gonna suck when we're tracking lots of swears
		foreach ($this->expletives as $word) {
			if (preg_match_all("/\b$word\b/i", $data['text'], $null) == true) {
				$count = count($null[0]);
				$this->data[$data['source']]['user'][$data['username']]['e'] += $count; // increase expletive count
				$this->data[$data['source']]['user'][$data['username']]['swears'][$word] += $count; // track words per user
				$this->data[$data['source']]['glob']['e'] += $count; // and words per channel
				$this->data[$data['source']]['glob']['swears'][$word] += $count;
			}
		}

		// auto-save after five minutes worth of chat (minimum, might be more depending on time between messages)
		if ((time() - $this->savemtime) > 300)
			$this->save();
	}

	public function pub_stfu($data) {
		if (empty($this->data[$data['source']])) {
			$this->bot->reply("I haven't tracked any data for this channel yet");
			return;
		}
		$dataset = $this->data[$data['source']];
		
		foreach ($dataset['user'] as $username=>$userdata) {
			$lines[$username] = $userdata['l'];
			$chars[$username] = $userdata['c'];
			$words[$username] = $userdata['w'];
		}

		foreach ($lines as $username=>$count) {
			$wplavg[$username] = number_format($words[$username] / $count, 2); // words per line avg
			$cpwavg[$username] = number_format($chars[$username] / $words[$username], 2); // chars per word average
		}

		arsort($lines); reset($lines);
		arsort($chars); reset($chars);
		arsort($words); reset($words);
		arsort($wplavg); reset($wplavg);
		arsort($cpwavg); reset($cpwavg);

		$who = key($lines);
		$this->bot->reply("Most lines: " . $who . " with " . $lines[$who] . " lines");
		$who = key($cpwavg);
		$this->bot->reply("Biggest words: " . $who . " with " . $cpwavg[$who] . " characters per word average");
	}

	public function pub_stfuswear($data) {
		$params = explode(' ', $data['data']);

		if (empty($this->data[$data['source']])) {
			$this->bot->reply("I haven't tracked any data for this channel yet");
			return;
		}

		// get channel statistics
		if (empty($params[0])) {
			$dataset = $this->data[$data['source']];
			foreach ($dataset['user'] as $username=>$userdata) {
				foreach ($userdata['swears'] as $word=>$count) $swearers[$username] += $count;
			}
			arsort($swearers);

			$totswears = $dataset['glob']['e'];

			$this->bot->reply("Total channel expletives uttered: " . $totswears);
			$this->bot->reply("Top 3 swearers:");
			reset($swearers);
			for ($i = 1; $i < 4; $i++) {
				$username = key($swearers);
				$this->bot->reply("$i: '$username' with " . $swearers[$username] . " expletives counted");
				if (next($swearers) === false)
					break;
			}
		} else { // get for a specific user
			$username = $params[0];
			if (empty($this->data[$data['source']]['user'][$username])) {
				$this->bot->reply("I haven't tracked any data for that user yet");
				return;
			}

			$dataset = $this->data[$data['source']]['user'][$username];
			$swears = $dataset['swears'];
			arsort($swears); reset($swears);

			$this->bot->reply("{$username}'s most commonly used expletives:");
			$out = array();
			for ($i = 1; $i < 6; $i++) {
				$word = key($swears);
				$out[] = "'$word' with " . $swears[$word] . " uses";
				if (next($swears) === false)
					break;
			}
			$this->bot->reply(join(", ", $out));
		}

	}

	private function reset() {
		unset($this->data);
		$this->data['since'] = time();
		$this->save();
	}

	private function addSwear($word) {
		if (in_array($word, $this->expletives))
			return false;

		$this->expletives[] = $word;
		$this->save();
		return true;
	}

	private function delSwear($word) {
		if ($key = array_search($word, $this->expletives)) {
			unset($this->expletives[$key]);
			$this->save();
			return true;
		}
		return false;
	}

	private function save() {
		file_put_contents($this->swearFile, join("\n", $this->expletives)); // expletives are \n delimited
		file_put_contents($this->dataFile, serialize($this->data)); // but data is serialized, since it's an array
		$this->savemtime = time();
	}

	private function load() {
		if (file_exists($this->swearFile))
			$this->expletives = explode("\n", file_get_contents($this->swearFile));
		if (file_exists($this->dataFile))
			$this->data = unserialize(file_get_contents($this->dataFile));
		else
			$this->reset(); // we wanna init the data file if we can't find it
	}

}

