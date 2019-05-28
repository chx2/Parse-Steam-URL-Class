<?php
class SteamID {

  protected $id;
  protected $key;

  public function __construct($id,$api_key = '') {
    $this->id = $id;
    $this->key = $api_key;
    return $this;
  }

  public function isID32() {
    if(preg_match('/^STEAM_0:[01]:[0-9]{8,9}$/', $this->id)) {
    	return true;
    }
    return false;
  }

  public function isID64() {
    if (strpos($this->id, '7656119') !== false) {
      $this->id = cleanOutput(str_replace('https://steamcommunity.com/profiles/', '', $this->id));
      return true;
    }
    return false;
  }

  public function resolveVanity() {
    $search = cleanOutput(str_replace('https://steamcommunity.com/id/', '', $this->id));
    $api = 'http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key='.$this->key.'&vanityurl='.$search;
    $vanity = getCURL($api);
    if ($vanity['response']['success'] === 1) {
      $this->id = $vanity['response']['steamid'];
      return true;
    }
    else {
      return false;
    }
  }

  public function toCommunityID() {
      if (preg_match('/^STEAM_/', $this->id)) {
          $parts = explode(':', $this->id);
          return bcadd(bcadd(bcmul($parts[2], '2'), '76561197960265728'), $parts[1]);
      } elseif (is_numeric($this->id) && strlen($this->id) < 16) {
          return bcadd($this->id, '76561197960265728');
      } else {
          return $this->id;
      }
  }

  public function toSteamID() {
      if (is_numeric($this->id) && strlen($this->id) >= 16) {
  				$this->id = bcsub($this->id, '76561197960265728');
  				//If subtraction goes negative, shift value up
  				if ($this->id < 0) {
  					$this->id += 4294967296;
  				}
          $z = bcdiv($this->id, '2');
      } elseif (is_numeric($this->id)) {
          $z = bcdiv($this->id, '2');
      } else {
          return $this->id;
      }
      $y = bcmod($this->id, '2');
      return 'STEAM_0:' . $y . ':' . floor($z);
  }

  public function toUserID() {
      if (preg_match('/^STEAM_/', $this->id)) {
          $split = explode(':', $this->id);
          return $split[2] * 2 + $split[1];
      } elseif (preg_match('/^765/', $this->id) && strlen($this->id) > 15) {
  				$uid = bcsub($this->id, '76561197960265728');
  				if ($uid < 0) {
  					$uid += 4294967296;
  				}
  				return  $uid;
      } else {
          return $this->id;
      }
  }

}
