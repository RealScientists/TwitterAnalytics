<?php

/**
 * @package twitlib.php 
 * @author Matthew Smith <matt@smiffytech.com>
 */

date_default_timezone_set('UTC');

/**
 * You need to grab the Twitter client code from github
 * and put it in this directory:
 * https://github.com/timwhitlock/php-twitter-api.git
 */
require_once(__DIR__.'/php-twitter-api/twitter-client.php');


/**
 * config.json needs values set for your Twitter developer API access details.
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

/**
 * Loop calling twitter_get() to handle paged responses.
 * 
 * @param string $endpoint relative URI of API endpoint.
 * @param array $args parameters for request per API documentation.
 * @param string $key_field name of array field in returned data to retrieve.
 * @param boolean $progress show progress through echo to STDOUT.
 * @param unsigned integer maximum API call retries after initial attempt.
 * @return array concatenated dataset from multiple API calls.
 */
function get_loop($endpoint, $args, $key_field, $progress = false, $max_retries = 5) {

  $count = 0;
  
  $args['cursor'] = -1;

  $data = array();

  while (true) {
    $count++;

    if ($progress) {
      echo "Getting batch " . $count . " from " . $endpoint . "\n";
    }


    $ret = null;

    /**
     * Make up to $max_retries attempts to do the API call.
     *
     * @var integer number of attempts after the initial one that have been made on this call.
     * @var integer max_retries number of times after first attempt to try making this call.
     */
    $retries = 0;
    while (true) {
      $ret = twitter_get($endpoint, $args);

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

      sleep(120);
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
