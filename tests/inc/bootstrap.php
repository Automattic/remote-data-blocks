<?php

declare(strict_types = 1);

define( 'ABSPATH', __FILE__ );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY', __DIR__ . '/../../' );
define( 'REMOTE_DATA_BLOCKS__UNIT_TEST', true );
define( 'REMOTE_DATA_BLOCKS_ENCRYPTION_KEY', 'test_encryption_key' );
define( 'REMOTE_DATA_BLOCKS_ENCRYPTION_SALT', 'test_encryption_salt' );

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/Integrations/constants.php';
require_once __DIR__ . '/../../functions.php';
require_once __DIR__ . '/stubs.php';
