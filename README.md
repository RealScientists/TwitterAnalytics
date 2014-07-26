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

##Data Retrieval/Storage Rationale##
`get-follower-ids.php` is intended to be run periodically, to allow comparisons to be made of follower numbers over time. This would allow analysis of whether followers were lost/gained when a certain conversation was going on, compared with the same timeframe on other days. Current testing has sampling happen at noon and midnight UTC, although more frequent sampling may allow for better analysis, since timezones would not muddy the waters so much.

Disc is cheap; why not pull entire follower records, rather than just IDs? The reason for pulling only IDs is rate limiting. The API call that retrieves IDs allows 5,000 records to be pulled per call, so @realscientists, currenly with a little over 12,000 followers, can be done in just three API calls. Retrieving entire follower records is limited to 200 records per call, so over sixty calls would need to be made to retrieve data for just one account. Rate limiting appears to allow fifteen calls per fifteen minute block, so the retrieval loop would need to include one-minute delays - so only 1,000 follower records could be pulled in five minutes. Retrieving just IDs means that full follower records could be pulled just ONCE, then only missing records (for new followers) would need to be retrieved. (Note that follower records would become stale with time, and would need to be refreshed if analysis were being performed on followers/following/listed/tweets.)

Why not use MySQL or MongoDB for storage? The type of analysis I have envisaged so far only requires sequential access to data - not random access. As things stand, only a PHP interpreter is required, so no need to install database engines which might be considered over-the-top for this application. Follower ID records are stored as JSON, as this can be translated into an array easily, and standard PHP array operations used for analysis. Index records are store as CSV, as this is a format that lends itself readily to file appends (as opposed to reading a JSON document in, updating it, writing it back out again.) Index files, which contain follower/following/listed/tweet numbers can also be read directly into a spreadsheet for analysis/graphing.

Note that any analysis where full follower records are used *would* be better suited to a database; since the format supplied by Twitter is JSON, it would be logical to use MongoDB for this purpose.
