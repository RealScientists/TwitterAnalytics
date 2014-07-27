#!/usr/bin/env php
<?php
/* 
 * Uncomment to work on large datasets if 
 * a) you get PHP Fatal error: allowed memory size... warning and
 * b) you think your machine has adequate RAM.
 */
//ini_set('memory_limit', '-1');

/*
 * This script will get full details of all followers for a given
 * screen name and write to a JSON file.
 *
 * See extract-followers-stats.php for a tool that can process
 * the JSON into CSV ready for graphing or other reporting.
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
