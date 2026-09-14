<?php

declare(strict_types=1);

// Autoload Composer dependencies (installed in the module folder).
require_once __DIR__ . '/../vendor/autoload.php';

// Define PrestaShop constants the module files check for, so they can be
// require()'d outside of a real shop installation.
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '9.1.0'); // dummy version for testing purposes
}

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', sys_get_temp_dir() . '/orderrevocator-tests-modules/');
}

// Minimal stand-ins for the PrestaShop core classes this module depends on.
require_once __DIR__ . '/Mocks/PrestaShopMock.php';

// Load the module's own front controller, which composer's classmap
// autoloader would otherwise only resolve lazily on first use.
require_once __DIR__ . '/../controllers/front/form.php';
