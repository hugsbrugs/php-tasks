<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set ('max_execution_time', 0); 

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Hug\Tasks\TaskStore as TaskStore;
use Hug\Tasks\Task as Task;

$task_store = new TaskStore();

$task = new Task(2, null, 'test_task_2', 'ls -lsa', ['project_id' => 34]);
$task_store->save($task);

