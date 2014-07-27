#!/usr/bin/env php
<?php
/* 
 * Uncomment to work on large datasets if 
 * a) you get PHP Fatal error: allowed memory size... warning and
 * b) you think your machine has adequate RAM.
 */
//ini_set('memory_limit', '-1');

/* Include common functions/objects. */
require_once(__DIR__.'/twitlib.php');

/*
 * Processor the JSON output of get-followers.php.
 *
 * Creates a summary CSV file of all followers with their
 * basic stats, and also a set of CSV files for the frequencies
 * of those stats defined in $fields_of_interest.
 */

/* Check filename specified. */
if ( !isset($argv[1]) ) {
  echo "Usage: extract-followers-stats.php FILENAME\n";
  exit;
}

/* Read file. */
$json = file_get_contents($argv[1]);

/* Could not read file. */
if ( $json === false ) {
  echo "Could not read data from " . $argv[1] . ", aborting.\n";
  exit;
}

/* Decode JSON. */
$jdata = json_decode($json, true);

/* JSON invalid. */
if ( $jdata === null ) {
  echo "Could not parse JSON from " . $argv[1] . ", aborting.\n";
  exit;
}

/* 
 * Get frequencies for the values of these fields. 
 *
 * See definition of user object here: https://dev.twitter.com/docs/platform-objects/users
 */
$fields_of_interest = array( 'followers_count', 'friends_count', 'favourites_count', 'listed_count', 'statuses_count' );


/* 
 * Create frequencies array, and start
 * a CSV file with headings.
 */
$frequencies = array();
$sscsv = 'screen_name,name,id,created_at,';
foreach ( $fields_of_interest as $field ) {
  $frequencies[$field] = array();
  $sscsv .= $field . ',';
}
$sscsv = rtrim($sscsv, ',');
$sscsv .= "\n";

$screen_name = explode('-', $argv[1])[0];

$ssfname = $screen_name . '-summary-stats.csv';

/* Process users. */
foreach ( $jdata as $user ) {
  /*
   * Summary data for each user.
   */
  $csvrow = '"' . $user['screen_name'] . '",';
  $csvrow .= '"' . $user['name'] . '",';
  $csvrow .= '"' . $user['id_str'] . '",';
  $csvrow .= '"' . twittime2iso($user['created_at']) . '",';

  /* Populate the frequencies arrays. */
  foreach( $fields_of_interest as $field ) {
    /* 
     * PHP is loosely typed, so we need to un-integer
     * the number to use it as an associative array key.
     */
    $key = 'k' . $user[$field];

    if ( array_key_exists($key, $frequencies[$field]) ) {
      $frequencies[$field][$key]++;
    } else {
      $frequencies[$field][$key] = 1;
    }

    $csvrow .= $user[$field] . ',';   
  }
  
  $sscsv .= rtrim($csvrow, ',') . "\n";
}

file_put_contents($ssfname, $sscsv);

foreach ( $fields_of_interest as $field ) {
  $fname = $screen_name . '-' . $field . '-frequencies.csv';  

  $csv = null;
  foreach ( array_keys($frequencies[$field]) as $key ) {
    $n = ltrim($key, 'k');
    $v = $frequencies[$field][$key];

    $csv .= $n . ',' . $v . "\n";
  }

  file_put_contents($fname, $csv);
}
