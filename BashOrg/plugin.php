<?php

class BashOrg extends SilverBotPlugin {

	public function chn_bash($data) {
		if (empty($data['data']))
			return;
			
		$quote = $this->searchQuote($data['data']);
		
		if ($quote == false) {
			$this->bot->reply("No quotes found matching '{$data['data']}'");
		} else {
			$lines = explode("\n", $quote);
			foreach ($lines as $line) 
				$this->bot->reply($line);
		}
	}
			
	private function searchQuote($search) {
		$url = "http://bash.org/?search=" . urlencode($search);
		$data = $this->curlGet($url);
		if (preg_match('/p\s*class="qt"\>(.+?)\<\/p\>/sm', $data, $matches) !== false) {
			return str_replace("<br />", '', html_entity_decode($matches[1]));
		}
		
		return false;
	}

}

