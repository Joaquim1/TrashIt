<?php

include dirname(__DIR__) . '/composer/vendor/autoload.php';

$mgClient = new Mailgun\Mailgun('key-a25f196a81a299a9b9b5e9d1633ddf42');
$domain = "mg.trashit.us";