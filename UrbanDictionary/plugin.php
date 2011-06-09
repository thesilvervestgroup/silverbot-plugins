<?php

class UrbanDictionary extends SilverBotPlugin {

	public function chn_ud($data) {
		if (empty($data['data']))
			return;

		$showExample = false;
		
		// split off any flags
		$flags = explode(' ', $data['data']);
		foreach ($flags as $key=>$flag) {
			if (substr($flag, 0, 1) == '-') {
				unset($flags[$key]);
				
				$bit = substr($flag, 1);
				if ($bit == 'e') $showExample = true;
			}
		}
		$term = join(' ', $flags);
		$defs = $this->getDefinitions($term);

		if (count($defs) > 1) {
			if ($defs['exact'] === true) {
				$this->bot->reply("UD for '$term':");
				$i = 1;
				foreach ($defs['terms'] as $def) {
					$this->bot->reply($i++ . ". " . $def[0]);
					if ($showExample === true) {
						$this->bot->reply("Example: " . $def[1]);
					}
				}
			} else {
				$this->bot->reply("UD for '$term' not found, closest matches:");
				foreach ($defs['terms'] as $def) {
					$this->bot->reply($def['term'] . ": " . $def[0]);
					if ($showExample === true) {
						$this->bot->reply("Example: " . $def[1]);
					}
				}
			}
		} else {
			$this->bot->reply("No UD definition for '$term'");
		}
	}
	
	public function chn_urbandictionary($data) {
		$this->chn_ud($data);
	}
	
    /**
     * Gets term definition(s) from Urban Dictionary
     * string $term - Term to define
     * int $count - How many definitions to get maximum (may return less if there aren't this many)
     * int $maxlen - Length to shorten each definition to if exceeds this length
     */
	private function getDefinitions($term, $count = 3, $maxLen = 280) {
		$url = "http://www.urbandictionary.com/define.php?term=" . urlencode($term);
		
		$data = $this->curlGet($url);
		$defs = array('exact' => true); // true until proven otherwise

		// gonna be breaking out the xPath here
		$doc = new DOMDocument;
		$doc->loadHTML($data);
		$xpath = new DOMXPath($doc);

		// get the term names for 'exact match'
		$rows = $xpath->query('//td[@class="word"]');
		$i = 0;
		foreach ($rows as $node) {
			if ($i == 0 && strtolower(trim($node->nodeValue)) != strtolower($term)) $defs['exact'] = false; // just confirm for the first result
			$defs['terms'][$i++]['term'] = str_replace("\r", " ", trim($node->nodeValue));
			if ($i == $count) break;
		}
		
		// get definitions
		$rows = $xpath->query('//div[@class="definition"]');
		$i = 0;
		foreach ($rows as $node) {
			list($text) = explode("\n", wordwrap(strip_tags(str_replace("\r", " ", trim($node->nodeValue))), $maxLen, "\n", true), 1);
			$defs['terms'][$i++][0] = $text;
			if ($i == $count) break;
		}

		// get examples as well (we will filter later)
		$rows = $xpath->query('//div[@class="example"]');
		$i = 0;
		foreach ($rows as $node) {
			list($text) = explode("\n", wordwrap(strip_tags(str_replace("\r", " ", trim($node->nodeValue))), $maxLen, "\n", true), 1);
			$defs['terms'][$i++][1] = $text;
			if ($i == $count) break;
		}
		
		return $defs;
	}
	
}

