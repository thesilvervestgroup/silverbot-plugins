<?php

class YouTube extends SilverBotPlugin {

	public function chn_youtube($data) {
		$vids = $this->searchVideo($data['data']);
		
		if (count($vids)) {
			foreach ($vids as $vid) {
				$this->bot->reply($vid['title'] . ' - http://www.youtube.com/watch?v=' . $vid['id']);
			}
		} else {
			$this->bot->reply('No Youtube results found for \''.$data['data'].'\'');
		}
	}
	
	public function chn_yt($data) {
		$this->chn_youtube($data);
	}
	
	public function chn($data) {
		$regexp = '~https?://(?:[0-9A-Z-]+\.)?(?:youtu\.be/|youtube\.com\S*[^\w\-\s])([\w\-]{11})(?=[^\w\-]|$)(?![?=&+%\w]*(?:[\'"][^<>]*>| </a>))[?=&+%\w-]*~ix';
		if (preg_match($regexp, $data['text'], $matches) != false) {
			$url = "http://gdata.youtube.com/feeds/api/videos/". $matches[1] . "?alt=json";
			$data = json_decode($this->curlGet($url), true);

			$this->bot->reply("YouTube - {$data['entry']['title']['$t']} ({$matches[1]})");
		}
	}
	
	private function searchVideo($term) {
		$url = 'http://gdata.youtube.com/feeds/api/videos?alt=json&q=' . urlencode($term);
		$data = json_decode($this->curlGet($url));

		$videos = array(); $i = 0;
		if ($data) foreach ($data->feed->entry as $entry) {
			$videos[$i]['id'] = substr($entry->id->{'$t'}, strrpos($entry->id->{'$t'}, '/')+1);
			$videos[$i]['title'] = $entry->title->{'$t'};
			if (++$i == 3) break;
		}
		
		return $videos;
	}
	
}

