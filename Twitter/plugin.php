<?php

class Twitter extends SilverBotPlugin {

	public function chn_twitter($data) {
		if (empty($data['data']))
			return;
			
		list($username) = explode(' ', $data['data'], 1);
		$tweet = $this->getLastTweet($username);
		$tweet = str_replace("\n", " ", $tweet); // because charliesheen is annoying
		
		if (empty($tweet))
			$this->bot->reply("No recent tweets found for '$username'");
		else
			$this->bot->reply("@$username: $tweet");
	}

	private function getLastTweet($username) {
		$url = 'http://api.twitter.com/1/statuses/user_timeline.json?screen_name='.$username.'&trim_user=true&count=1&include_rts=true';

		$data = json_decode($this->curlGet($url));
		if (!is_array($data)) return false;
		
		$tweet = $data[0];
		return $tweet->text;
	}
	
}


