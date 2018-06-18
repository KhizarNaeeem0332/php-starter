<?php

define('BinDev_START', microtime(true));

use Bindeveloperz\Core\Database\DB;
use Bindeveloperz\Core\Config\Config;
use Bindeveloperz\Core\Database\Pagination;
use Bindeveloperz\Core\Faker\Faker;
use Bindeveloperz\Core\Log\ErrorHandler;

require_once  'vendor/autoload.php';

//Setting Configuration
$configPath = __DIR__ . '/config/';
$_config = Config::getInstance($configPath);

//Database and Schema
$_db = DB::getInstance($_config->get("database.mysql"));
$_schema = \Bindeveloperz\Core\Database\Schema::getInstance();

//Setting Pagination Instance
$_page = Pagination::getInstance();

//FileSystem instance
$_file = Bindeveloperz\Core\Filesystem\File::getInstance();

//Setting Faker libray

$_faker = Faker::getInstance();

if($_config->get("app.debug"))
{
    $error  = new ErrorHandler();
    $error->run();
}

if($_config->get("app.logQuery"))
{
    $_db::enableQueryLog();
}
