#!/usr/bin/env php
<?php

date_default_timezone_set('UTC');

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

$screen_names = array( 'realscientists', 'wethehumanities', 'smiffy' );

/**
 * @global string UTC timestamp YYYYMMDD-HHmm
 *
 * Used to identify the batch in the CSV index file, 
 * allowing datasets for multiple accounts to be compared.
 */
$batch_id = tstamp();

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
  global $batch_id;

  $fname = $screen_name . '-followers_ids-' . tstamp() . '.json';
  $dsname = $screen_name . '-followers_ids-datasets.csv';

  /* Retrieve follower ids, write to file. */
  $t = get_loop('followers/ids', array('screen_name' => $screen_name), 'ids');
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


/**
 * Loop calling twitter_get() to handle paged responses.
 * 
 * @param string $endpoint relative URI of API endpoint.
 * @param array $args parameters for request per API documentation.
 * @param string name of array field in returned data to retrieve.
 * @return array concatenated dataset from multiple API calls.
 */
function get_loop($endpoint, $args, $key_field) {
  
  $args['cursor'] = -1;

  $data = array();

  while (true) {
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
    //sleep(60);
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
