<?php

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set ('max_execution_time', 0); 

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Hug\Tasks\TaskStore as TaskStore;
use Hug\Tasks\TaskManager as TaskManager;
use Hug\Tasks\Task as Task;

# task_name => max threads
$tasks_threads = [
    'test_task_1' => 1,
    'test_task_2' => 5
];


# Create Task Store (see config.ini for params)
$task_store = new TaskStore();
# Create a Task
$task = new Task(1, null, 'test_task_1', 'ls -lsa', ['project_id' => 34]);
# Save Task
$task_store->save($task);

# Start Task Manager
$TaskManager = new TaskManager($tasks_threads, $task_store);

# Rien ne se passe après cette ligne utiliser un autre script

/*$server = stream_socket_server('tcp://127.0.0.1:8080');
stream_set_blocking($server, 0);
$loop->addReadStream($server, function ($server) use ($loop) 
{
    $conn = stream_socket_accept($server);
    $data = "HTTP/1.1 200 OK\r\nContent-Length: 3\r\n\r\nHi\n";
    $loop->addWriteStream($conn, function ($conn) use (&$data, $loop) 
    {
        $written = fwrite($conn, $data);
        if ($written === strlen($data)) 
        {
            fclose($conn);
            $loop->removeStream($conn);
        }
        else
        {
            $data = substr($data, 0, $written);
        }
    });
});*/