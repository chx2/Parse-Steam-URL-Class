# Parse-Steam-URL-Class
A PHP class that can be used to parse Steam urls, whether dealing with ID64 or a vanity URL.

## Why use this?
If you're simply wanting to parse a steam URL 
without wanting to import an entire library to 
do so, this is a simple snippet that will return
CommunityID, SteamID, and UserID as an easy to 
reference array of values.

### Requirements 
If you wish to use the resolveVanity() function, you will need to input a Steam WebAPI Key.
Learn more @ https://steamcommunity.com/dev

#### Usage 
To use this, you will need to provide an input to convert
as well your SteamWebAPI Key. For example:

```php
$id = new SteamID($input,$api_key);
```

So if I were to pass my own custom steam url, https://steamcommunity.com/id/xthenew
through the function, if I want to convert this custom URL to a SteamID, I would do the following:

```php
if ($id->resolveVanity()) {
  $communityid = $id->toCommunityID();
  $steamid = $id->toSteamID();
  $userid = '[U:1:'.$id->toUserID().']';
}
```

In order to use it, simply save a copy of the steamid.class.php then in your project:
```php
require_once 'steamid.class.php';
```

#### Functions List
| Function Name | Return Value |
| --- | ---|
| isID32() | True if input ID follows ID32 format |
| isID64() | True if input ID follows ID64 format |
| resolveVanity() | True if input ID is a Vanity URL, converts class id to ID64, returns id |
| toCommunityID() | Convert class id to ID64, returns id |
| toSteamID() | Convert class id to ID32, returns id |
| toUserID() | Conver class id to UserID, returns id |

##### Final note:
This file uses id conversion functions by https://gist.github.com/rannmann