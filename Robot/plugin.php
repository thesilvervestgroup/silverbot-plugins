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
		$channel = $data['source'];
		if (preg_match($regex, $data['text'], $matches)) {
			$url = $matches[1];
			// url was found
			if (@isset($this->data['urls'][$channel][$url])) {
				$info = $this->data['urls'][$channel][$url];
				$diff = time() - $info['w'];
				$str = "ROBOT (".$this->ago($diff)." by " . $info['u'] . ")";
				// roboto
				if (!isset($this->data['muted'][$channel]) || $this->data['muted'][$channel] != true)
					$this->bot->reply($str);
			} else {
				$this->data['urls'][$channel][$url]['w'] = time();
				$this->data['urls'][$channel][$url]['u'] = $data['username'];
			}
		}

		// auto-save after five minutes worth of chat (minimum, might be more depending on time between messages)
		if ((time() - $this->savemtime) > 300)
			$this->save();
	}
	
	public function chn_robot($data) {
		list($cmd, $info) = explode(' ', trim($data['data']), 2);
		$cmd = strtolower($cmd);
		$channel = $data['source'];
		switch ($cmd) {
			case 'domstat':
			case 'domstats':
				$domains = array();
				foreach ($this->data['urls'][$channel] as $url=>$urldata) {
					$url = parse_url($url);
					$domains[$url['host']]++;
				}
				
				arsort($domains);
				$this->bot->reply('Number of unique domains in ROBOT cache: ' . count($domains));
				$this->bot->reply("Top three domains:");
				$count = 0;
				foreach($domains as $domain=>$hits) {
					$this->bot->reply("'$domain' with $hits");
					if (++$count == 3) break;
				}
				break;
				
			case 'off':
				$this->data['muted'][$channel] = true;
				$this->save();
				$this->bot->reply("Muted ROBOT for $channel");
				break;
				
			case 'on':
				$this->data['muted'][$channel] = false;
				$this->save();
				$this->bot->reply("Now ROBOT'ing for $channel");
				break;
			
			case 'find':
			case 'link':
			case 'search':
				$this->pub_link(array('data' => $info));
				break;
				
			case 'stat':
			case 'stats':
			case 'status':
				$users = $domains = array();
				$links = $oldest = 0;
			
				if (isset($this->data['urls'][$channel]) foreach ($this->data['urls'][$channel] as $u=>$url) {	
					if ($oldest == 0) $oldest = $url['w'];
					$users[$url['u']]++;
					$u = parse_url($u);
					$domains[$url['u']][$u['host']]++;
					$links++;
					if ($url['w'] < $oldest) $oldest = $url['w'];
				}
				
				if (strlen($info)) { // stats for a user
					$user = trim($info);
					if (!empty($users[$user])) {
						arsort($domains[$user]);
						$this->bot->reply("$user has submitted {$users[$user]} links spread across " . count($domains[$user]) . " domains");
						$this->bot->reply("Top three domains:");
						foreach($domains[$user] as $domain=>$hits) {
							$this->bot->reply("'$domain' with $hits");
							if (++$count == 3) break;
						}
					} else {
						$this->bot->reply("Can't find $user in ROBOT cache");
					}
				} else {
					arsort($users);
					$most_user = current(array_flip($users));
					$most_links = current($users);
				
					$this->bot->reply("Number of links in ROBOT cache: $links");
					$this->bot->reply("$most_user has submitted the most links with $most_links");
				}
				break;
		}
	}
	
	public function pub_link($data) {
		$s = $data['data'];
		$channel = $data['source'];

		// make it regex'd
		$from = array('0','1','2','3','4','5','6','7','8','9','/','+','*','.','$','^',' ');
		$to = array('(0|zero)','(1|one)','(2|two)','(3|three)','(4|four)','(5|five)','(6|six)','(7|seven)','(8|eight)','(9|nine)','\/','\+','\*','\.','\$','\^','.*');
		$s = str_replace($from, $to, $s);
		$s = '/.*' . $s . '.*/i';
		
		$links = array_keys($this->data['urls'][$channel]);
		$matched = array();
		foreach ($links as $link) {
			if (preg_match($s, $link, $matches) == true) {
				$matched[] = $link;
			}
		}
		
		if (count($matched)) {
			$this->bot->reply("Most recent links matching '{$data['data']}'");
			foreach ($matched as $url) {
				$times[$url] = $this->data['urls'][$channel][$url]['w'];
			}
			
			arsort($times);
			$count = 1;
			foreach ($times as $url=>$time) {
				$who = $this->data['urls'][$channel][$url]['u'];
				$when = $this->ago(time() - $time);
				$this->bot->reply("$who, $when: $url");
				if (++$count == 4) break;
			}
			
		} else {
			$this->bot->reply("No links found matching '{$data['data']}'");
		}
	}
	
	public function pub_links($data) {
		$links = array();
		$channel = $data['source'];
		$oldest = 0;
		$from = time() - 86400; // rolling 24 hour window
		
		if (isset($this->data['urls'][$channel]) foreach ($this->data['urls'][$channel] as $url=>$urldata) {
			if ($urldata['w'] < $from) continue;
			if (isset($links[$url])) continue;
			if ($oldest == 0) $oldest = $urldata['w'];
			$links[$url] = $urldata;
			if ($urldata['w'] < $oldest) $oldest = $urldata['w'];
		}
		
		foreach ($links as $url=>$link) {
			$this->bot->pm($data['username'], date('[H:i:s] ', $link['w']) . "<{$link['u']}> $url");
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
		
		$periods = array("s", "m", "h", "d", "w", "M", "Y", "D");
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

