#!/usr/bin/env php
<?php

/*
 * This script will get full details of all followers for a given
 * screen name and write to a JSON file.
 *
 * See extract-followers-stats.php for a tool that can process
 * the JSON into CSV ready for graphing or other reporting.
 * 
 * NOTE - this doesn't work with accounts with large numbers of 
 * followers, due to memory issues. Such accounts will probably 
 * need to be written to a database table, batch by batch.
 */

if (!isset($argv[1])) {
  echo "Usage: get-followers.php SCREENNAME\n";
  exit;
}

/* Include common functions/objects. */
require_once(__DIR__.'/twitlib.php');

echo "Getting followers for " . $argv[1] . ", in batches of 200. This may take a little while.\n";

$screen_name = $argv[1];

$fname = $screen_name . '-followers-' . tstamp() . '.json';

/* Retrieve follower data, write to file. */
$t = get_loop('followers/list', array('screen_name' => $screen_name, 'count' => 200, 'skip_status' => 'true'), 'users', true);
file_put_contents($fname, json_encode($t));

echo "Followers file written to " . $fname . "\n";

?>
