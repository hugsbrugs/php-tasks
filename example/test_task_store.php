<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set ('max_execution_time', 0); 

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// use DateTime;

use Hug\Tasks\TaskStore as TaskStore;
use Hug\Tasks\Task as Task;

$TaskStore = new TaskStore();

$task = Task::get_random_task();
$saved = $TaskStore->save($task);
error_log('saved : ' . $saved);
error_log('saved : ' . print_r($TaskStore->tasks(), true));

$task->end_date = new DateTime('now');
$updated = $TaskStore->update($task);
error_log('updated : ' . $updated);
error_log('updated : ' . print_r($TaskStore->tasks(), true));

$deleted = $TaskStore->delete($task);
error_log('deleted : ' . $deleted);
error_log('deleted : ' . print_r($TaskStore->tasks(), true));

$reseted = $TaskStore->reset();
error_log('reseted : ' . $reseted);

