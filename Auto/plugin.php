<?php

class Auto extends SilverBotPlugin
{
    private $options = array('o', 'h', 'v');
    private $data = array();
    private $file = '';

    public function __construct() {
        $this->file = $this->getDataDirectory() . 'auto.json';
        $this->load();
    }

    public function onJoin($data) {
        $channel = $data['source'];
        $who = $data['username'];

        if (empty($this->data[$channel])) {
            $this->data[$channel] = array('type' => '', 'ignores' => array());
            $this->save();

            return;
        }

        if (!in_array($who, $this->data[$channel]['ignores'])) {
            $option = $this->data[$channel]['type'];
            $this->bot->send("MODE {$channel} +{$option} {$who}");
        }
    }

    public function pub_auto($data) {
        $channel = $data['source'];
        $params = explode(' ', $data['data']);

        if (empty($this->data[$channel])) {
            $this->data[$channel] = array('type' => '', 'ignores' => array());
        }

        if (!$this->bot->Auth->hasAccess($data['user_host'])) {
            $this->bot->pm($data['username'], "You do not have access to change the !auto mode for {$channel}");
            return;
        }

        $param = array_shift($params);
        if (!in_array($param, $this->options)) {
            $this->data[$channel]['type'] = '';
            $this->bot->pm($channel, "Disable auto in {$channel}");
        } else {
            $this->data[$channel]['type'] = $param;
            $this->bot->pm($channel, "Set {$channel} to auto +{$param}");
        }

        $this->save();
    }

    public function pub_autouser($data) {
        if (empty($this->data[$data['source']])) {
            $this->data[$channel] = array('type' => '', 'ignores' => array());
        }

        $channel = $data['source'];
        $params = explode(' ', $data['data']);

        if (!$this->bot->Auth->hasAccess($data['user_host'])) {
            $this->bot->pm($data['username'], "You do not have access to change the !autouser mode for {$channel}");
            return;
        }

        if (count($params) !== 1) {
            $this->bot->pm($data['username'], "Usage in your required channel: !autouser {user}");
            return;
        }

        if (isset($this->data[$channel]['ignores'][$params[0]])) {
            unset($this->data[$channel]['ignores'][$params[0]]);
            $this->bot->pm($channel, "User '{$params[0]}' removed from ignore list");
        } else {
            $this->data[$channel]['ignores'][] = $params[0];
            $this->bot->pm($channel, "User '{$params[0]}' added to ignore list");
        }
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

