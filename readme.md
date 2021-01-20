# php-tasks

This librairy provides utilities function to manage tasks processing

[![Build Status](https://travis-ci.org/hugsbrugs/php-tasks.svg?branch=master)](https://travis-ci.org/hugsbrugs/php-tasks)
[![Coverage Status](https://coveralls.io/repos/github/hugsbrugs/php-tasks/badge.svg?branch=master)](https://coveralls.io/github/hugsbrugs/php-tasks?branch=master)

## Install

Install package with composer
```
composer require hugsbrugs/php-tasks
```

In your PHP code, load library
```php
require_once __DIR__ . '/../vendor/autoload.php';
use Hug\Tasks\Tasks as Tasks;
```

### Edit config.ini

Tasks can be stored in 3 differents ways : in file, in MySql or SqlLite database. Fill in appropriate section in config file. You can tweak [task_manager] sections (in seconds) depending on your tasks manager behavior and your server capacities

```
[task_store]
TASK_STORE_METHOD = 'file';'file'|'mysql'|'sqllite'

[task_store_file]
TASK_STORE_FILE = '/var/www/my-project/tasks.json'

[task_tmp_file]
TASK_TMP_FILE = '/tmp'

[task_store_mysql]
TASK_STORE_MYSQL_USER = 'username'
TASK_STORE_MYSQL_PASS = 'password'
TASK_STORE_MYSQL_HOST = 'localhost'
TASK_STORE_MYSQL_PORT = 3306
TASK_STORE_MYSQL_DB = 'database_name'
TASK_STORE_MYSQL_TABLE = 'tasks_table_name'
TASK_STORE_MYSQL_ENV = 'dev';'dev'|'prod'

[task_store_sqllite]
TASK_STORE_SQLLITE_FILE = '/var/www/my-project/tasks.sqllite.db'
TASK_STORE_SQLLITE_USER = ''
TASK_STORE_SQLLITE_PASS = ''
TASK_STORE_SQLLITE_DB = 'database_name'
TASK_STORE_SQLLITE_TABLE = 'tasks_table_name'
TASK_STORE_MYSQL_ENV = 'dev';'dev'|'prod'

[task_manager]
LOAD_PENDING_TASKS_DELAY = 15
CHECK_RUNNING_PIDS_DELAY = 4
PROCESS_TASKS_DELAY = 4
```


### Edit process_tasks.php
In this file, you will define your different tasks names and how many threads can run concurrently for each task.

```php
# Load config.ini file
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Hug\Tasks\TaskStore as TaskStore;
use Hug\Tasks\TaskManager as TaskManager;
use Hug\Tasks\Task as Task;

# Define task_name => max threads
$tasks_threads = [
    'export' => 1,
    'download' => 1,
    'delete' => 1,
    'extract' => 5,
    'check_availability' => 5,
    'check_metrics' => 5
];


# Create Task Store (see config.ini for params)
$task_store = new TaskStore();

# Start Task Manager
$TaskManager = new TaskManager($tasks_threads, $task_store);
```

Run The task manager on command line for testing purposes
```php
php process_tasks.php
```

In production mode, use a tool like supervisord to always run your task manager
```
[program:phptasks]
command=bash -c "ulimit -n 10000; exec /usr/bin/php /var/www/my-project/process_tasks.php"
process_name=phptasks
numprocs=1
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/supervisor/supervisor-phptasks-info.log
stdout_logfile_maxbytes=1MB
stderr_logfile=/var/log/supervisor/supervisor-phptasks-error.log
stderr_logfile_maxbytes=1MB
```

### Add Tasks add_task.php
In another php file, create a task and add it to the task store.
```php
# Create Task Store (see config.ini for params)
$task_store = new TaskStore();

# Create a Task
$task = new Task(1, null, 'export', 'ls -lsa', ['project_id' => 34]);

# Save Task
$task_store->save($task);
```

Task status can be one of following : 'pending', 'running', 'closed', 'failed'


## Author

Hugo Maugey [Webmaster](https://hugo.maugey.fr/webmaster) | [Consultant SEO](https://hugo.maugey.fr/consultant-seo) | [Fullstack developer](https://hugo.maugey.fr/developpeur-web)