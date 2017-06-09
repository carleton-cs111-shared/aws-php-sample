<?php

// Trying to set alarms for the VM created in vmsample.php to see if can make those work.

require 'vendor/autoload.php';
use Aws\CloudWatch\CloudWatchClient;

// Instantiate Ec2Client to do all of the work
$client = CloudWatchClient::factory(array(
    'profile' => 'default',
    'region' => 'us-east-1'
));

$result = $client->describeAlarms([]);
print_r($result);
