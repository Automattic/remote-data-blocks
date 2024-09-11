<?php
define( 'ABSPATH', __FILE__ );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY', __DIR__ . '/../' );
define( 'REMOTE_DATA_BLOCKS__UNIT_TEST', true );

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/autoloader.php';
require_once __DIR__ . '/mocks/mock-data-source.php';
require_once __DIR__ . '/mocks/mock-query-runner.php';
require_once __DIR__ . '/stubs.php';
