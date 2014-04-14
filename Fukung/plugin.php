<?php

class Fukung extends SilverBotPlugin {

	public function chn_fukung($data) {
		if (empty($data['data'])) {
			$this->random();
		} else {
			$this->search($data['data']);
		}
	}

	private function random() {
		$url = 'http://fukung.net/random';
		$data = $this->curlGet($url);
		
		if (preg_match('/link\s+rel=\"image_src\"\s+href=\"(.+)\"/', $data, $matches) !== false) {
			$this->bot->reply(strip_tags(trim($matches[1])));
		}
	}
		
	private function search($term) {
		$url = 'http://fukung.net/rss/tag/200/' . urlencode($term);
		$data = $this->curlGet($url);
		
		$lines = explode("\n", $data);
		$images = array();
		foreach ($lines as $line) {
			if (preg_match('/<img src="(.+?)"/', $line, $matches) != false) {
				$images[] = $matches[1];
			}
		}
		
		$count = count($images);
		if ($count > 0) {
			$rand = mt_rand(0, $count);
			$image = $images[$rand];
			$this->bot->reply("$term: $image ($rand of $count)");
		} else {
			$this->bot->reply("$term: no images found");
		}
	}

}

