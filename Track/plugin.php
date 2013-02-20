<?php

class Track extends SilverBotPlugin {
    private $data = array();
    private $file = '';
    private $allowed_trackers = array('fedex');

    public function __construct() {
        $this->file = $this->getDataDirectory() . 'tracking.json';
        $this->load();
		$this->addTimer('checkPackages', '1 minutes', array($this, 'checkPackages'));
    }

	public function chn_track($data) {
		$bits = explode(' ', $data['data']);
		if (empty($bits) || !in_array($bits[0], $this->allowed_trackers) || empty($bits[1]) || strlen($bits[1]) < 8) {
			$this->bot->pm($data['username'], 'Usage: !track <shipping company> <tracking number>');
			$this->bot->pm($data['username'], 'Tracking companies currently supported: ' . join(', ', $this->allowed_trackers));
		}

		if (isset($this->data[$bits[1]])) {
			$this->bot->reply('Already tracking that package');
		} else {
			$this->data[$bits[1]] = array('type' => $bits[0], 'who' => $data['username'], 'chan' => $data['source']);
			$this->checkPackage($bits[1]);
		}
	}

	private function fedex($tracking) {
		// setup the query
		$req = '{"TrackPackagesRequest":{"appType":"wtrk","processingParameters":{"anonymousTransaction":true},"trackingInfoList":[{"trackNumberInfo":{"trackingNumber":"'.$tracking.'"}}]}}';
		$postarray = array('data' => $req, 'action' => 'trackpackages');
		$post = http_build_query($postarray);

		// curl that bitch
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.fedex.com/trackingCal/track");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Silverbot/1.0 (+https://github.com/thesilvervestgroup/silverbot)');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		curl_close($ch);

		// FedEX JSON uses hex char codes (\xFF), but json_decode() barfs.. this fixes them
		$pattern = '/\\\\x([0-9a-f]{2})/';
		$ret = preg_replace_callback($pattern, array($this, "chrhexdec"), $ret);

		// process the json
		$data = json_decode($ret);
		$package = $data->TrackPackagesResponse->packageList[0];
		$status = $package->statusWithDetails;
		foreach ($package->scanEventList as $scan) {
			$time = strtotime($scan->date . ' ' . $scan->time . ' ' . $scan->gmtOffset);
			$scans[$time] = $scan->status . ' (' . $scan->scanLocation . ')';
		}

		return array('number' => $tracking, 'status' => $status, 'scans' => $scans);
	}

	private function chrhexdec($matches) {
	    return chr(hexdec($matches[1]));
	}

	protected function checkPackages($who = null) {
		if (count($this->data)) foreach ($this->data as $number=>$package) {
			$this->checkPackage($number);
		}
	}

	protected function checkPackage($number) {
		$package = $this->data[$number];
		switch ($package['type']) {
		case 'fedex':
			$result = $this->fedex($number);
			if ($result['status'] != $package['status']) {
				$this->bot->pm($package['chan'], $package['who'] . ': ' . ucfirst($package['type']) . ' package ' . $number . ' - ' . $result['status']);
				$latestscan = current($result['scans']);
				$this->bot->pm($package['chan'], $package['who'] . ': Latest scan: ' . $latestscan);
				$result['who'] = $package['who'];
				$result['type'] = $package['type'];
				$result['chan'] = $package['chan'];
				$this->data[$number] = $result;
			}
			break;
		}

		$this->save();
	}

    private function save() {
        $data = json_encode($this->data);
        file_put_contents($this->file, $data);
    }

    private function load() {
        if (!file_exists($this->file)) {
            $this->save();
        } else {
            $this->data = json_decode(file_get_contents($this->file), true);
        }
    }
}
