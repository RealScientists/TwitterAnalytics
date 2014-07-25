#!/usr/bin/env php
<?php

date_default_timezone_set('UTC');

$csvfilebase = '-followers_ids-datasets.csv';

$screen_names = array( 'realscientists', 'wethehumanities', 'smiffy' );

$datasets = array();

foreach ($screen_names as $screen_name) {
  $file = $screen_name . $csvfilebase;
  $f = fopen($file, 'r');
  $line = fgetcsv($f);
  fclose($f);

  $datasets[$screen_name] = json_decode(file_get_contents($line[0]), true);

  echo $screen_name . ": " . sizeof($datasets[$screen_name]) . "\n";
}

echo "RS/WtH " . sizeof(array_intersect($datasets['realscientists'], $datasets['wethehumanities'])) . "\n";
echo "RS/smiffy " . sizeof(array_intersect($datasets['realscientists'], $datasets['smiffy'])) . "\n";
echo "WtH/smiffy " . sizeof(array_intersect($datasets['wethehumanities'], $datasets['smiffy'])) . "\n";
echo "RS/WtH/smiffy " . sizeof(array_intersect($datasets['realscientists'], $datasets['wethehumanities'], $datasets['smiffy'])) . "\n";


