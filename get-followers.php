#!/usr/bin/env php
<?php

/*
 * This script will get full details of all followers for a given
 * screen name and write to a JSON file.
 *
 * See extract-followers-stats.php for a tool that can process
 * the JSON into CSV ready for graphing or other reporting.
 */

date_default_timezone_set('UTC');


if (!isset($argv[1])) {
  echo "Usage: get-followers.php SCREENNAME\n";
  exit;
}


/**
 * You need to grab the Twitter client code from github
 * and put it in this directory:
 * https://github.com/timwhitlock/php-twitter-api.git
 */
require_once(__DIR__.'/php-twitter-api/twitter-client.php');


/**
 * config.json needs values set for your Twitter developer
 * API access details:
 *
 * consumer_key
 * consumer_secret
 * access_token
 * access_token_secret
 *
 * If you don't have this, go to https://apps.twitter.com to
 * create the keys for a new app.
 */
$jconfig = file_get_contents('config.json');
$config = json_decode($jconfig, true);

$client = new TwitterApiClient;
$client->set_oauth ( 
  $config['consumer_key'],
  $config['consumer_secret'],
  $config['access_token'], 
  $config['access_token_secret'] 
);

echo "Getting followers for " . $argv[1] . ", in batches of 200. This may take a little while.\n";

get_followers($argv[1]);

/************************** END *********************************/

/**
 * Loop through provided screen, get dataset
 * write dataset to file.
 *
 * @param array list of screen names.
 */
function get_followers($screen_name) {
  global $batch_id;

  $fname = $screen_name . '-followers-' . tstamp() . '.json';

  /* Retrieve follower data, write to file. */
  $t = get_loop('followers/list', array('screen_name' => $screen_name, 'count' => 200, 'skip_status' => 'true'), 'users', true);
  file_put_contents($fname, json_encode($t));

  echo "Followers file written to " . $fname . "\n";
}


/**
 * Loop calling twitter_get() to handle paged responses.
 * 
 * @param string $endpoint relative URI of API endpoint.
 * @param array $args parameters for request per API documentation.
 * @param string name of array field in returned data to retrieve.
 * @return array concatenated dataset from multiple API calls.
 */
function get_loop($endpoint, $args, $key_field, $progress = false) {

  $count = 0;
  
  $args['cursor'] = -1;

  $data = array();

  while (true) {
    $count++;

    if ($progress) {
      echo "Getting batch " . $count . " from " . $endpoint . "\n";
    }

    $ret = twitter_get($endpoint, $args);

    if ( $ret['status'] === false ) {
      echo $ret['errmsg'];
      exit;
    }

    $tmpdata = $data;
    $data = array_merge($tmpdata, $ret['data'][$key_field]);

    if ( $ret['data']['next_cursor'] == 0 ) {
      break;
    }

    $args['cursor'] = $ret['data']['next_cursor'];
    sleep(60);
  }

  return($data);
}

/**
 * Make a GET request to the Twitter API.
 *
 * @param string $endpoint relative URI of API endpoint.
 * @param array $args parameters for request per API documentation.
 * @return array [ array data from API, boolean status, string error message ].
 */
function twitter_get($endpoint, $args) {
  global $client;
  $status = true;
  $errmsg = null;
  $data = null;

  try {
    $data = $client->call( $endpoint, $args, 'GET' );
  }
  catch( TwitterApiException $err ){
    $status = false;
    $errmsg = 'Status ' . $err->getStatus() . '. Error '.$err->getCode() . ' - ' . $err->getMessage() . "\n";
  }
  
  return(array('data' => $data, 'status' => $status, 'errmsg' => $errmsg));
}

/**
 * Return timestamp.
 * 
 * @return string timestamp in format YYYYMMDD-HHmm.
 */
function tstamp() {
  return(date("omd-Hi"));
}

?>
