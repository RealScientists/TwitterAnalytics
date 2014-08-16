#!/usr/bin/env php
<?php

/* Include common functions/objects. */
require_once(__DIR__.'/twitlib.php');

/* Check filename specified. */
if ( !isset($argv[1]) ) {
  echo "Usage: tweets2csv.php FILENAME\n";
  exit;
}

/* Read file. */
$json = file_get_contents($argv[1]);

/* Could not read file. */
if ( $json === false ) {
  echo "Could not read data from " . $argv[1] . ", aborting.\n";
  exit;
}

/* 
 * Decode JSON. 
 * 
 * Note that tweets JSON file is in reverse chronological order,
 * so array needs to be reversed to read in natural order.
 *
 */
$jdata = array_reverse(json_decode($json, true));

echo "Generating CSV...\n";

/*
 * Use same file name, with different extension, for output.
 */
$outfile = rtrim($argv[1], 'json') . 'csv';

$fp = fopen($outfile, 'w');
fputcsv($fp, array('uct_timestamp','tweet','retweets','favourites','tweet_id'));

foreach ($jdata as $tweet) {
  $row = array( 
    twittime2iso($tweet['created_at']), 
    $tweet['text'], 
    $tweet['retweet_count'],
    $tweet['favorite_count'],
    $tweet['id']
  );
  fputcsv($fp, $row);
}

fclose($fp);

echo "Tweets written to " . $outfile . "\n";


?>
