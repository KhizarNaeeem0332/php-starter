<?php


include_once  'controllers/core/Database.php';
include_once  'controllers/core/Logger.php';
include_once  'controllers/core/Stringer.php';


spl_autoload_register(function ($class){
    include_once  "controllers/$class.php" ;
});



//include all files
foreach (glob('helpers/*.php') as $filename)
{
    include_once $filename;
}




use Bindeveloperz\Database as KDB;


const CONFIG = [

    "database" => [
        'driver' => 'sqlite',
        'host' =>  '',
        'database' => 'storage/database/database.db',
        'username' => '',
        'password' => '',
        'port' => '3306',
        "option" => true
    ]
];




$db = KDB::getInstance(CONFIG['database']);


// global variables

$pageTitle  = "Homepage";
$notify = [];
$errors = 0;





