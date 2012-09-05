<?php

class FMyLife extends SilverBotPlugin {

	private $cache = array();

	public function chn_fml($data) {
		if (count($this->cache) < 3) $this->refillCache();
		$fml = array_shift($this->cache);
		
		$this->bot->reply($fml);
	}
	
	private function refillCache() {
		$url = 'http://www.fmylife.com/random';
		$data = $this->curlGet($url);
		
		$lines = explode("\n", $data);
		
		foreach ($lines as $line) {
			// ugly, but ensures that only 'your life sucks' entries get through
			if (preg_match('/class="fmllink">(.*?)<\/a>.+? FML<\/a>.+?life sucks<\/a> \(<span.+?>([0-9]+?)<\/span>\).+?deserved it<\/a> \(<span.+?>([0-9]+?)<\/span>\)/', $line, $matches) !== false) {
				if (count($matches) == 4 && $matches[2] > $matches[3]) {
					$this->cache[] = strip_tags(trim($matches[1]));
				}
			}
		}
	}
	
}

