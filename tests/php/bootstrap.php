<?php
/**
 * Bootstraps the app for CLI test execution.
 * Deliberately mirrors what index.php does (require autoload.php) rather than
 * reimplementing routing/session logic.
 */
error_reporting(E_ALL);

$projectRoot = dirname(__DIR__, 2);
chdir($projectRoot); // class/, inc/ etc. are resolved relative to CWD by Autoloader/Config

require_once $projectRoot . '/autoload.php';
require_once __DIR__ . '/framework.php';
