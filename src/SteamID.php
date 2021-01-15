<?php
/**
 * Parse-Steam-URL-Class
 *
 * A single PHP class meant to handle the utility of dealing with the conversion between SteamID formats.
 *
 * If you're simply wanting to parse a steam URL without wanting to import an entire library to do so,
 * this is a simple snippet that will return CommunityID, SteamID, and UserID as an easy to reference array of values.
 *
 * If you wish to use the resolveVanity() or toAvatar() functions, you will need to input a Steam WebAPI Key.
 * Learn more @ https://steamcommunity.com/dev
 *
 * This file uses id conversion functions by https://gist.github.com/rannmann
 */
namespace chx2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SteamID {

    protected $id;
    protected $key;
    protected $client;

    public function __construct($id,$api_key = '') {
        $this->id = $id;
        $this->key = $api_key;
        $this->client = new Client([
            'base_uri' => 'http://api.steampowered.com/ISteamUser',
            'timeout'  => 5
        ]);
        return $this;
    }

    /**
     * isID32
     *
     * Determine if an input ID is in ID32 format.
     * @return bool
     */
    public function isID32() {
        return boolval(preg_match('/^STEAM_0:[01]:[0-9]{8,9}$/', $this->id));
    }

    /**
     * isID64
     *
     * Determine if an input ID is in ID64 format.
     *
     * @return bool
     */
    public function isID64() {
        return (preg_match('/7656119[0-9]{10}/i', $this->id));
    }

    /**
     * resolveVanity
     *
     * Determine if a vanity url is valid.
     * @return bool
     */
    public function resolveVanity() {
        $search = $this->cleanOutput($this->id);
        try {
            $data = $this->client->request('GET', '/ResolveVanityURL/v0001/', [
              'query' => [
                  'key' => $this->key,
                  'vanityurl' => $search
              ]
            ]);
            if ($data['response']['success'] === 1) {
                $this->id = $data['response']['steamid'];
                return true;
            }
            else {
                return false;
            }
        } catch (GuzzleException $e) {
          return false;
        }
    }

    /**
     * toAvatar
     *
     * Return a profile image for the given SteamID
     * @return mixed
     */
    public function toAvatar() {
        if ($this->isID32() || $this->isID64() || $this->resolveVanity()) {
            $key = $this->toCommunityID();
            try {
                $data = $this->client->request('GET', '/GetPlayerSummaries/v0002/', [
                    'query' => [
                        'key' => $this->key,
                        'steamids' => $key
                    ]
                ]);
            } catch (GuzzleException $e) {
                return false;
            }
            return $data['response']['players'][0]['avatarfull'];
        }
        else {
            return false;
        }
    }

    /**
     * toCommunityID
     *
     * Convert the current ID instance to ID64 format.
     * @return int|string
     */
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

    /**
     * toSteamID
     *
     * Convert the current ID instance to ID32 format.
     * @return int|string
     */
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

    /**
     * toUserID
     *
     * Convert the current ID instance to UserID format.
     * @return mixed
     */
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

    /**
     * toArray
     *
     * Convert the current ID instance to an array of all class returned formats.
     * @return array
     */
    public function toArray() {
        return [
            'avatar'    => $this->toAvatar(),
            'id32'      => $this->toSteamID(),
            'id64'      => $this->toCommunityID(),
            'uid'       => $this->toUserID()
        ];
    }

    /**
     * cleanOutput
     *
     * Remove trailing slashes, whitespace, and profile url segments from input.
     * @param $input
     * @return string|string[]|null
     */
    private function cleanOutput($input) {
        $inputNoProfileURL = str_replace('https://steamcommunity.com/id/', '', $input);
        $inputNoTrailingSlash = htmlspecialchars(str_replace('/', '', $inputNoProfileURL));
        $inputNoWhitespace = preg_replace('/\s/', '', $inputNoTrailingSlash);
        return $inputNoWhitespace;
    }

}
