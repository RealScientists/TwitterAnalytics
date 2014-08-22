#!/usr/bin/env php
<?php

require_once(__DIR__.'/twitlib.php');

/** @var boolean $verbose run in verbose mode. */
$verbose = true;


/** @var string path for storing files. */
$datadir = __DIR__.'/data/';

/**
 * @global string UTC timestamp YYYYMMDD-HHmm
 *
 * Used to identify the batch in the CSV index file, 
 * allowing datasets for multiple accounts to be compared.
 */
$batch_id = tstamp();


/* Pull in $screen_names from watchlist. */
require_once(__DIR__.'/watchlist.php');

/**
 * Loop to generate and index datasets for given screennames.
 */
foreach ($screen_names as $screen_name) {
  get_ids($screen_name);
}

/**
 * Loop through provided screen names, get dataset
 * write dataset to file, record file name in list.
 *
 * @param array list of screen names.
 */
function get_ids($screen_name) {
  global $batch_id, $datadir, $verbose;

  $fname = $datadir . $screen_name . '-followers_ids-' . tstamp() . '.json';
  $dsname = $datadir . $screen_name . '-followers_ids-datasets.csv';

  /* Retrieve follower ids, write to file. */
  $t = get_loop('followers/ids', array('screen_name' => $screen_name), 'ids', $verbose, 5);
  file_put_contents($fname, json_encode($t));

  /* Get data for current user to append to CSV */
  $user = twitter_get('users/show', array('screen_name' => $screen_name));

  /* 
   * filename
   * batch id (batch timestamp)
   * followers count
   * following count
   * tweets count
   * listed count
   * display name
   */
  $csv_row = '"' . $fname . '","' 
    . $batch_id . '",'
    . sizeof($t) . ',' 
    . $user['data']['friends_count'] . ','
    . $user['data']['statuses_count'] . ',' 
    . $user['data']['listed_count'] . ',' 
    . '"' . $user['data']['name'] . '"'
    . "\n";
  file_put_contents($dsname, $csv_row, FILE_APPEND);
}

?>
