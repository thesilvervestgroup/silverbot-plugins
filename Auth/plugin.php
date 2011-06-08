<?php

class Auth extends SilverBotPlugin {
	public $trigger = '~';
	public $accounts = array();
	private $db = '';
	private $dbh; // for the PDO connection
	
	// init the db and load users
	public function __construct() {
		$this->db = "sqlite:".$this->getDataDirectory()."auth.db"; // create the auth.db file inside the plugin dir
		$this->dbh = new PDO($this->db, '', '');

		$result = $this->dbh->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
		if ($result->fetchColumn() === false) { // db not initialised
			$this->dbh->query("CREATE TABLE users (name TEXT PRIMARY KEY, hostmask TEXT);");
		} else {
			$result = $this->dbh->query('SELECT * FROM users');
			if (!empty($result)) foreach ($result as $row) {
				$this->accounts[$row['name']] = $row['hostmask'];
			}
		}
	}
	
	public function hasAccess($mask) {
		foreach ($this->accounts as $user=>$test)
			if (preg_match('/'.$test.'/', $mask) == 1) return true;
		return false;
	}
	
	public function addUser($user, $mask) {
		return ($this->dbh->prepare('INSERT INTO users (name, hostmask) VALUES (?, ?)')->execute(array($user, $mask)));
	}
	
	public function delUser($user) {
		return ($this->dbh->prepare('DELETE FROM users WHERE name = ?')->execute(array($user)));
	}
	
	public function editUser($user, $newmask) {
		return ($this->dbh->prepare('UPDATE users SET hostmask = ? WHERE name = ?')->execute(array($newmask, $user)));
	}
		
	private function refreshUsers() {
		$result = $this->dbh->query('SELECT * FROM users');
		if (!empty($result)) foreach ($result as $row) {
			$this->accounts[$row['name']] = $row['hostmask'];
		}
	}

	private function countUsers() {
		return count($this->accounts);
	}
	
	public function prv_adduser($data) {
		if ($this->hasAccess($data['user_host'])) {
			$params = explode(' ', $data['data']);
			if (count($params) != 2) {
				$this->bot->reply('Usage: ~adduser <nickname> <hostmask (regexp)>');
				return;
			}
			$this->addUser($params[0], $params[1]);
			$this->bot->reply("User '{$params[0]}' added!");
			
			$this->refreshUsers();
		}
	}
	
	public function prv_deluser($data) {
		if ($this->hasAccess($data['user_host'])) {
			$params = explode(' ', $data['data']);
			if (count($params) != 1) {
				$this->bot->reply('Usage: ~deluser <nickname>');
				return;
			}
			$this->delUser($params[0]);
			$this->bot->reply("Access for '{$params[0]}' revoked!");

			$this->refreshUsers();
		}
	}
	
	public function prv_edituser($data) {
		if ($this->hasAccess($data['user_host'])) {
			$params = explode(' ', $data['data']);
			if (count($params) != 2) {
				$this->bot->reply('Usage: ~edituser <nickname> <new hostmask (regexp)>');
				return;
			}
			$this->editUser($params[0], $params[1]);
			$this->bot->reply("Hostmask for '{$params[0]}' updated to '{$params[1]}!");

			$this->refreshUsers();
		}
	}

	// this function takes 'ownership' of the bot
	// you need to have set your nickname in the config.ini file
	// and trigger it as soon as the bot comes online the first time
	// with ~hello your!user@mask
	public function prv_hello($data) {
		if (count($this->accounts) == 0 && $data['source'] == $this->config['owner'] && !empty($data['data'])) {
			$this->addUser($data['source'], $data['data']);
			$this->refreshUsers();
			$this->bot->reply('MASTER, I AM HERE TO SERVE YOU.');
		}
	}
	
}
