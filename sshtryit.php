
<?php
$path = getenv('HOME') . '/aws-php/';
# IP address in local environmental variable AWSIP
$connection = ssh2_connect(getenv('AWSIP'), 22);

if (ssh2_auth_pubkey_file($connection, 'student', '~/.ssh/vmkeypair.pub',
                          '~/.ssh/vmkeypair.pem')) {
    echo "Authentication Successful!\n";
} else {
    die('Authentication Failed...');
}

$pyfile = 'count.py';
ssh2_scp_send($connection, "{$path}/{$pyfile}", "/home/student/{$pyfile}");

# Lots of great help here on handling streamed output, which I've used below:
# https://secure.php.net/manual/en/function.ssh2-exec.php

$stream = ssh2_exec($connection, "python3 {$pyfile}");
$errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

$done = false;

$max_time = 1; // in seconds
$start_time = time();
echo $start_time . "\n";

while (!$done) {
    $line = fgets($stream);
    $errorLine = fgets($errorStream);
    if ((time() - $start_time > $max_time) or preg_match('/\[end\]/',$line)) {
        $done = true;
    } else {
        echo $line;
        echo $errorLine;
    }
}




?>


