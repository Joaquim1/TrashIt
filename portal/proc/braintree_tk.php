<?php
include(dirname(dirname(__DIR__)) . "/includes/braintree.php");

echo Braintree_ClientToken::generate();