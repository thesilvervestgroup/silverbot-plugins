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
	
}

