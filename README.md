TwitterAnalytics
================

A collection of scripts for running twitter analytics with the twitter API

##Prerequisites##

To use the code presented here, you will need:

* The Twitter client library from here: https://github.com/timwhitlock/php-twitter-api.git clone into this directory.
* Twitter API credentials. See next section.

###Twitter API Access###
* Go to https://apps.twitter.com, sign in with your regular Twitter account.
* Create Application.
* Fill in mandatory fields, agree to rules of the road. 
* Next screen, manage API keys. 
* Create my access token.

You will need to create a file config.json like this:
<pre><code>
{
  "consumer_key" : "asdfasfasdf",
  "consumer_secret" : "asdfsadfasfasdfasdf",
  "access_token" : "asfasdfasdf",
  "access_token_secret" : "asdfasdfasfasd"
}
</code></pre>
...where consumer_key is identified on the Twitter page is identified as API key, and consumer_secret as API secret.

##Gathering Follower IDs##
`get-followers-ids.php` will retrieve arrays of follower IDs for all screen names in the `$screen_names` array,
  and write them to `[screen name]-followers-ids-[timestamp].json`. For every screen name, an index file will (CSV) will be created and appended to every time `get-followers-ids.php` is run. Index files are named `[screen name]-followers_ids-datasets.csv. CSV columns are thus:

* filename
* batch id (batch timestamp)
* followers count
* following count
* tweets count
* listed count
* display name

The batch id is provided so that datasets for multiple accounts taken at the same time can be compared.

##Analysing The Data##
`intersects.php` provides a simple example that reads the first line of the index files for the given screen names, reads the corresponding datasets into an array, then shows the follower count for each screen name. The final lines perform an array_intersect to find common follower IDs between accounts.

In the same way, an array_diff() of dataset X and dataset Y can show followers GAINED between samples X and Y, and array_diff() of dataset Y and dataset X can show followers LOST between samples X and Y.
