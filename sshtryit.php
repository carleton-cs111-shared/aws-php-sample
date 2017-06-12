
<?php
$connection = ssh2_connect('shell.example.com', 22);

if (ssh2_auth_agent($connection, 'username')) {
    echo "Authentication Successful!\n";
} else {
    die('Authentication Failed...');
}
?>



<!--<?php
require 'Net/SSH2.php';
require 'Crypt/RSA.php';

// Upload public key for user "student"
$key = new Crypt_RSA();

$key->loadKey(file_get_contents('/pathtokey.pem'));


$ssh = new Net_SSH2('ec2-user@52.91.44.49');
if (!$ssh->login('user', $key)) {
    exit('Login Failed');
}

?>
-->