#!/usr/bin/env php
<?php
/**
 * Takes a Twitter screen name (handle,) and attempts to 
 * get the user record, list of follower IDs, list of
 * friends IDs (following,) and last 500 tweets (or as 
 * many as there are, if less.)
 *
 * Each of these is written as a JSON file, where the name
 * is structured thus:
 *
 * SCREEN_NAME-TIMESTAMP-DATASET.json
 * 
 * Where values for DATASET are:
 *
 * - user
 * - followers
 * - friends
 * - tweets 
 *
 * @package slurp.php
 * @author Matthew Smith <matt@smiffytech.com>
 * @param string screen name (by command line argument.)
 */
ini_set('memory_limit', '-1');
date_default_timezone_set('UTC');

/* Check screenname is specified. */
if ( !isset($argv[1]) ) {
  echo "Usage: slurp.php SCREENNAME\n";
  exit;
}

$screen_name = $argv[1];

require_once(__DIR__.'/twitlib.php');

$namebase = $screen_name . '-' . tstamp() . '-';

echo "Attempting to get user " . $screen_name . "\n";
$user = twitter_get('users/show', array('screen_name' => $screen_name));
file_put_contents($namebase . 'user.json', json_encode($user, JSON_PRETTY_PRINT));

echo "Attempting to retrieve follower IDs.\n";

/* Retrieve follower ids, write to file. */
$t = get_loop('followers/ids', array('screen_name' => $screen_name), 'ids', true, 5);
file_put_contents($namebase . 'followers.json', json_encode($t, JSON_PRETTY_PRINT));

echo "Attempting to retrieve friends IDs.\n";
/* Retrieve friends ids, write to file. */
$t = get_loop('friends/ids', array('screen_name' => $screen_name), 'ids', true, 5);
file_put_contents($namebase . 'friends.json', json_encode($t, JSON_PRETTY_PRINT));

$data = array();
$tmpto = 0;
$maxtweets = 500;
$maxcount = 50;
$max_retries = 20;
$progress = true;
$tweetcount = 0;

while(true) {

  echo "Get tweets older or equal to " . $tmpto . "\n";

  $retries = 0;
  while (true) {
    if ($tweetcount ==0) {
      $ret = twitter_get('statuses/user_timeline', 
        array('screen_name' => $screen_name, 'count' => $maxcount, 'include_rts' => 1)); 
    } else {
      $ret = twitter_get('statuses/user_timeline', 
        array('screen_name' => $screen_name, 'count' => $maxcount, 'max_id' => $tmpto, 'include_rts' => 1)); 
    }

    /* If we got the data, leave the loop. */
    if ( $ret['status'] === true ) {
        break;
    }

    if ($progress) {
      echo "Unsuccessful API call on attempt " . $retries . ". " . $ret['errmsg'];
    }

    $retries++;

    if ($retries > $max_retries) {
      echo "Giving up.\n";
      exit;
    }

    //    sleep(120);
  }


  $numtweets = count($ret['data']);

  echo "Got ". $numtweets . " tweets\n";

  /*
   * Iterate through array - break if we hit the last required tweet.
   */
  $breaknow = false;
  $tmpary = array();
  foreach ($ret['data'] as $tweet) {
    $tmpary[] = $tweet;
    $tweetcount++;
    if ($tweetcount == $maxtweets) {
      echo "Tweet " . $tweet['id'] . " is tweet #" . $maxtweets . " - breaking.\n";
      $breaknow = true;
      break;
    }
  }

  $tmpdata = $data;
  $data = array_merge($tmpdata, $tmpary);

  $lasttweet = array_pop($ret['data']);

  //var_dump($lasttweet); exit;
  $tmpto = $lasttweet['id'] - 1;

  if ($breaknow || $tmpto == -1) {
    break;
  }
}

file_put_contents($namebase . 'tweets.json', json_encode($data, JSON_PRETTY_PRINT));


?>
