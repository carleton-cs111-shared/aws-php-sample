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

// Create security group, and configure. This will go up on AWS.
$securityGroupName = 'placement-vm-security-group';
//$result = $ec2->createSecurityGroup(['GroupName' => $securityGroupName,
//	                             'Description' => 'Basic ssh security']);
//$ec2->authorizeSecurityGroupIngress([
//	'GroupName' => $securityGroupName,
//	'IpPermissions' => [['IpProtocol' => 'tcp',
//			     'FromPort' => 22,
//			     'ToPort' => 22,
//			     'IpRanges' => [['CidrIp' => '0.0.0.0/0']]]]]);

// Start up a VM.
// t2 instance types, which is what I'm running, must run on a VPC: a virtual private cloud.
// In order to do that, you need to specify a subnet. The subnet read in the file below is one
// of my own personal subnets that I've created.

// Parse without sections
$ini_array = parse_ini_file("privatedata.ini");
$subnetId = $ini_array['subnetid'];
echo "$subnetId\n";

$result = $ec2->runInstances([
	'ImageId' => 'ami-c58c1dd3',
	'MinCount' => 1,
	'MaxCount' => 1,
	'InstanceType' => 't2.micro',
	'KeyName' => $keyPairName,
	'SubnetId' => $subnetId,
	//'SecurityGroups' => [$securityGroupName]
	]);


$instanceIds = $result->getPath('Instances/*/InstanceID');

$ec2->waitUntilInstanceRunning(['InstanceIds' => $instanceIds]);

$result = $ec2->describeInstances(['InstanceIds' => $instanceIds]);

echo current($result->getPath('Reservations/*/Instances/*/PublicDnsName'));
