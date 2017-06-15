
<?php
$path = getenv('HOME') . '/aws-php/';
# IP address in local environmental variable AWSIP
$connection = ssh2_connect(getenv('AWSIP'), 22);

if (ssh2_auth_pubkey_file($connection, 'ec2-user', '~/.ssh/vmkeypair.pub',
                          '~/.ssh/vmkeypair.pem')) {
    echo "Authentication Successful!\n";
} else {
    die('Authentication Failed...');
}

$pyfile = 'count.py';
ssh2_scp_send($connection, "{$path}/{$pyfile}", "/home/ec2-user/{$pyfile}");
$stream = ssh2_exec($connection, "python3 {$pyfile}");

stream_set_blocking($stream, true);
for ($i = 1; $i <= 10; $i++) {
    $line = fgets($stream);
    echo $line;
}












?>


