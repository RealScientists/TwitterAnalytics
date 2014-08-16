#!/usr/bin/env php
<?php
ini_set('memory_limit', '-1');

/* Include common functions/objects. */
require_once(__DIR__.'/twitlib.php');

$argc = count($argv);

if ($argc != 5) { 
  echo "Usage: get-timeline SCREENNAME TWEETER FROM_TWEET_ID TO_TWEET_ID\n";
  exit;
}

$screen_name = $argv[1];
$curator = $argv[2];
$from = $argv[3];
$to = $argv[4];

$fname = $screen_name . '-' . $curator . '-tweets.json';

$data = array();
$tmpto = $to;
$maxcount = 50;
$max_retries = 5;
$progress = true;
$tweetcount = 0;

while(true) {

  echo "Older or equal to " . $tmpto . "\n";

  $retries = 0;
  while (true) {
    $ret = twitter_get('statuses/user_timeline', 
      array('screen_name' => $screen_name, 'count' => $maxcount, 'max_id' => $tmpto, 'include_rts' => 1)); 

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
  }


  $numtweets = count($ret['data']);

  echo "Got ". $numtweets . " tweets\n";

  /*
   * Iterate through array - break if we hit the last required tweet.
   */
  $breaknow = false;
  $tmpary = array();
  foreach ($ret['data'] as $tweet) {
    if ($tweet['id'] < $from) {
      echo "Tweet " . $tweet['id'] . " is older than we want - breaking.\n";
      $breaknow = true;
      break;
    }
    $tmpary[] = $tweet;
    $tweetcount++;
  }

  $tmpdata = $data;
  $data = array_merge($tmpdata, $tmpary);

  $lasttweet = array_pop($ret['data']);

  $tmpto = $lasttweet['id'] - 1;

  if ($breaknow) {
    break;
  }
}

file_put_contents($fname, json_encode($data, JSON_PRETTY_PRINT));

echo $tweetcount . " tweets file written to " . $fname . "\n";

/*
 * Invoke tweets2csv.php to convert JSON to CSV summary.
 */
$csvcmd = './tweets2csv.php ' . $fname;
system($csvcmd, $retval);
echo $retval;

?>
