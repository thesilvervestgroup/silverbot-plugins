<?php

class Utility extends SilverBotPlugin {

	/**
	 * Convert a timestamp into a human-readable date
	 * @int timestamp
	 */
	public function pub_timestamp($data) {
		$bits = explode(' ', $data['data']);
		if (empty($bits[0]))
			return;

		// make sure the timestamp is a inside a valid 32bit integer range
		if ($bits[0] > 0 && $bits[1] < 2147483647) {
			// default to current timezone
			$offset = date(Z) / 3600;
			if (isset($bits[1]) && $bits[1] >= -12 && $bits[1] <= 14)
				$offset = (int)$bits[1];
			
			// make a nicer string
			$offset_str = '';
			if ($offset > 0)
				$offset_str = '+' . $offset;
			else if ($offset < 0)
				$offset_str = (string)$offset;
			
			$this->bot->reply($data['username'] . ', ' . $data['data'] . ' is ' . gmdate('Y-m-d H:i:s', $bits[0] + ($offset * 3600)) . ' UTC' . $offset_str);
		}
	}
	
	// alias to pub_timestamp()
	public function pub_ts($data) {
		$this->pub_timestamp($data);
	}
	
	/**
	 * Generates a "random" password
	 * @int minimum length - default 8
	 * @int maximum length - default 8
	 */
	public function pub_randpw($data) {
		$bits = explode(' ', $data['data']);
		$min = $max = 8;
		if (!empty($bits[0]) && is_numeric($bits[0]) && $bits[0] > 0 && $bits[0] < 1025) $min = $bits[0];
		if (!empty($bits[1]) && is_numeric($bits[0]) && $bits[1] > 0 && $bits[1] < 1025 && $bits[1] >= $min) $max = $bits[1];
		if ($min > $max) $max = $min;
		
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-=_+<>?@#^&';
		$length = mt_rand($min, $max);
		$output = '';
		for ($i = 0; $i < $length; $i++) {
			$output .= $chars[(mt_rand() % strlen($chars))];
		}
		
		$this->bot->reply($output);
	}
	
	/**
	 * Performs a DNS lookup, either addr to name or name to addr
	 * @string lookup
	 */
	public function pub_dns($data) {
		if (empty($data['data'])) return;
		if (ip2long($data['data']) === false) { // not an IP
			$list = gethostbynamel($data['data'])
			$this->bot->reply($data['username'] . ': ' . $data['data'] . ' resolves to \'' . join(', ', $list) . '\'');
		} else {
			$this->bot->reply($data['username'] . ': ' . $data['data'] . ' resolves to \'' . gethostbyaddr($data['data']) . '\'');
		}
	}
	
	/**
	 * Perform a Base64 encode
	 * @string data
	 */
	public function pub_base64($data) {
		$this->bot->reply($data['username'] . ': ' . base64_encode($data['data']));
	}
	
	/**
	 * Perform a Base64 decode
	 * @string data
	 */
	public function pub_unbase64($data) {
		$this->bot->reply($data['username'] . ': ' . base64_decode($data['data']));
	}
	
}

