<?php
include dirname(__DIR__) . '/composer/vendor/autoload.php';

Braintree_Configuration::environment('sandbox');
Braintree_Configuration::merchantId('');
Braintree_Configuration::publicKey('');
Braintree_Configuration::privateKey('');