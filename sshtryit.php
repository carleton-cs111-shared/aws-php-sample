
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

# Lots of great help here on handling streamed output, which I've used below:
# https://secure.php.net/manual/en/function.ssh2-exec.php

$stream = ssh2_exec($connection, "python3 {$pyfile}");

$done = false;

while (!$done) {
    $line = fgets($stream);
    if (preg_match('/\[end\]/',$line)) {
        $done = true;
    } else {
        echo $line;
    }
}












?>


