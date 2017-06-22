<?php
/*
 * Copyright 2013. Amazon Web Services, Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

// Most based on this blog entry:
// https://aws.amazon.com/blogs/developer/provision-an-amazon-ec2-instance-with-php/

require 'vendor/autoload.php';
//require 'Net/SSH2.php';
//require 'Crypt/RSA.php';

use Aws\Ec2\Ec2Client;

// Instantiate Ec2Client to do all of the work
$ec2 = Ec2Client::factory(array(
    'profile' => 'default',
    'region' => 'us-east-1'
));

// Create a key pair. This creates a local copy, but it also goes up on AWS.
$keyPairName = 'vmkeypair';
//$result = $ec2->createKeyPair(['KeyName' => $keyPairName]);
//$saveKeyLocation = getenv('HOME') . "/.ssh/{$keyPairName}.pem";
//file_put_contents($saveKeyLocation, $result['KeyMaterial']);
// Permissions important for ssh
//chmod($saveKeyLocation, 0600);

// Parse without sections
$ini_array = parse_ini_file("privatedata.ini");
$subnetId = $ini_array['subnetid'];
$vpcId = $ini_array['vpcid'];
$securityId = $ini_array['securityid'];

// Create security group, and configure. This will go up on AWS.
$securityGroupName = 'placement-vm-security-group';
//$result = $ec2->createSecurityGroup(['GroupName' => $securityGroupName,
//	                             'Description' => 'Basic ssh security',
//				     'VpcId' => $vpcId]);
//$ec2->authorizeSecurityGroupIngress([
//	'GroupId' => $securityId,
//	'IpPermissions' => [['IpProtocol' => 'tcp',
//			     'FromPort' => 22,
//			     'ToPort' => 22,
//			     'IpRanges' => [['CidrIp' => '0.0.0.0/0']]]]]);


// Observe all the instances already out there
//$result = $ec2->describeInstances();
//$instanceIds = $result->getPath('Reservations/*/Instances/*/InstanceId');
//print_r($instanceIds);

// t2 instance types, which is what I'm running, must run on a VPC: a virtual private cloud.
// In order to do that, you need to specify a subnet. The subnet read in the file below is one
// of my own personal subnets that I've created.

// chunk_split/base64_encode is the canonincal way to base64encode a string
// https://stackoverflow.com/questions/29477997/what-is-the-use-of-the-combination-of-chunk-split-and-base64-encode
$startupScript = chunk_split(base64_encode(file_get_contents("startupscript.txt")));

$result = $ec2->runInstances([
	'ImageId' => 'ami-c58c1dd3',
	'MinCount' => 1,
	'MaxCount' => 1,
	'InstanceType' => 't2.micro',
	'KeyName' => $keyPairName,
	'SubnetId' => $subnetId,
	'SecurityGroupIds' => [$securityId],
	'UserData' => $startupScript,
    'InstanceInitiatedShutdownBehavior' => 'terminate'
]);


$instanceIds = $result->getPath('Instances/*/InstanceId');

$ec2->waitUntilInstanceRunning(['InstanceIds' => $instanceIds]);

echo "Instance running... now waiting for startup script to finish.";

// Frustratingly, AWS doesn't seem to provide an easy way to do this.
// https://stackoverflow.com/questions/11245356/how-to-check-user-data-status-while-launching-the-instance-in-aws
// I'm going to work around this by trying to ssh in via the student account, and verifying that the startup
// script is done by seeing if a certain file exists.



$result = $ec2->describeInstances(['InstanceIds' => $instanceIds]);
$ip_address = $result->getPath('Reservations/*/Instances/*/PublicIpAddress')[0];

echo "$ip_address\n";

$startup_done = false;

while (!$startup_done) {
   echo "Sleeping...\n";
   sleep(60);
   echo "Trying to connect...\n";

   $connection = ssh2_connect($ip_address, 22);
   if (!$connection) {
      echo "Unable to form ssh2 connection, will try again.\n";
      continue;
   }

   if (ssh2_auth_pubkey_file($connection, 'ec2-user', '~/.ssh/vmkeypair.pub',
                             '~/.ssh/vmkeypair.pem')) {
      echo "Authentication successful.\n";
   } else {
      echo "Authentication Failed, will try again.\n";
      echo "Done.\n";
      $startup_done = true;
      break;
   }

   if (ssh2_scp_recv($connection, "/home/ec2-user/startupdone.txt", "/tmp/startupdone.txt")) {
      $startup_done = true;
      echo "Startup complete.";
   } else {
      echo "Startup not complete, will try again.";
   }
}
