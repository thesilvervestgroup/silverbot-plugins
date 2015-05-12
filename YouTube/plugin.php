<?php
/**
 * Requires API key in the config file, as api_key
 */
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
			$url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id=" . urlencode($matches[1]) . "&key=" . $this->config['api_key'];
			$data = json_decode($this->curlGet($url), true);

			if (is_array($data) && !empty($data['items']))
				$this->bot->reply("YouTube - {$data['items'][0]['snippet']['title']} ({$data['items'][0]['id']})");
		}
	}
	
	private function searchVideo($term) {
		$url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . urlencode($term) . "&key=" . $this->config['api_key'];
		$data = json_decode($this->curlGet($url));

		$videos = array(); $i = 0;
		if ($data) foreach ($data->items as $entry) {
			if ($entry->id->kind != 'youtube#video') continue;
			$videos[$i]['id'] = $entry->id->videoId;
			$videos[$i]['title'] = $entry->snippet->title;
			if (++$i == 3) break;
		}
		
		return $videos;
	}
	
}

