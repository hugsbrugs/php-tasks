<?php

namespace Hug\Tasks;

use DateTime;
use SplPriorityQueue;

use React\EventLoop\Factory as Factory;
use Hug\Tasks\Tasks as Tasks;
use Hug\Tasks\TaskStoreFile as TSF;
use Hug\Scripts\Scripts as Scripts;
// use Hug\Tasks\TaskQuery;

/**
 *
 */
class TaskManager
{
	public $tmp = null;

	// timer loop 
	public $loop = null;

	public $task_queue = [];

	public $is_tasking = false;
	
	public $max_threads = [];

	public $scan_timer = null;

	public $running_threads = [];

	public $running_pids = [];

	public $task_store = null;


	/**
	 *
	 */
	function __construct($tasks_threads, $task_store)
	{
		if(defined('TASK_TMP_FILE'))
		{
			$this->tmp = TASK_TMP_FILE;
		}
		else
		{
			$this->tmp = sys_get_temp_dir();
		}
		
		$this->max_threads = $tasks_threads;
		$this->task_store = $task_store;

		# Create empty array of tasks for all queues
		$this->task_queue = array_fill_keys(array_keys($tasks_threads), null);
		# Create empty array of running threads for all queues
		$this->running_threads = array_fill_keys(array_keys($tasks_threads), 0);
		# Create empty array of running PID for all queues
		$this->running_pids = array_fill_keys(array_keys($tasks_threads), []);

		#
		$this->init_task_queue();

		#
		$this->loop = Factory::create();

		// $this->loop->addPeriodicTimer(5, function () {
		//     $memory = memory_get_usage() / 1024;
		//     $formatted = number_format($memory, 3).'K';
		//     echo "Current memory usage: {$formatted}\n";
		// });

		// initialiaze tasks to perform
		$this->load_pending_tasks();

		# Load Tasks to perform every x seconds
		$this->loop->addPeriodicTimer(LOAD_PENDING_TASKS_DELAY, [$this, 'load_pending_tasks']);

		# Check Running processes every x seconds
		$this->loop->addPeriodicTimer(CHECK_RUNNING_PIDS_DELAY, [$this, 'check_running_pids']);

		# Launch Waiting Tasks in queue every x seconds
		$this->scan_timer = $this->loop->addPeriodicTimer(PROCESS_TASKS_DELAY, [$this, 'process']);

		# Launch Loop
		$this->loop->run();
	}

	/**
	 *
	 */
	function init_task_queue()
	{
		foreach ($this->task_queue as $task_name => $queue)
		{
			$this->task_queue[$task_name] = new SplPriorityQueue();
			$this->task_queue[$task_name]->setExtractFlags(SplPriorityQueue::EXTR_DATA);
		}
	}

	/**
	 * Load tasks from database
	 * with status 'pending' 
	 */
	function load_pending_tasks()
	{
		# Don't Load if already loading
		if($this->is_tasking===false)
	    {
	        $this->is_tasking = true;

	        # Reset current pending tasks
	        $this->init_task_queue();

			$tasks = $this->task_store->get_by_status('pending');
			foreach ($tasks as $task)
			{
				if(isset($this->task_queue[$task->name]))
				{
					$this->task_queue[$task->name]->insert($task, 1);
				}
				else
				{
					error_log('Unknown Task Name : ' . $task->name);
				}
			}

			$this->is_tasking = false;
		}
	}

	/**
	 * Checks for all running tasks if their pid
	 * is still running or not to free queue
	 */
	public function check_running_pids()
	{
		$closed_pids = [];
		# Check running pids
	    foreach ($this->running_pids as $task_name => $task_pids)
	    {
	    	foreach ($task_pids as $key => $task_pid)
	    	{
	    		if(!Scripts::is_running($task_pid))
		        {
		        	$closed_pids[] = $task_pid;

		            unset($this->running_pids[$task_name][$key]);

		            $this->running_threads[$task_name]--;
		        }
	    	}
	    }

	    # Update Database
	    if(count($closed_pids) > 0)
	    {
		    $this->task_store->update_closed($closed_pids);
	    }

	    # RE INDEX ARRAY
	    foreach ($this->running_pids as $task_name => $task_pids)
	    {
	    	$this->running_pids[$task_name] = array_values($task_pids);
	    }
	}

	/**
	 * Launches new tasks and save start date and pid in database
	 */
	public function process()
	{
		// error_log('process');
	    if($this->is_tasking===false)
	    {
	        $this->is_tasking = true;
	        
	        foreach ($this->task_queue as $task_name => $queue)
	        {
		        while($this->running_threads[$task_name] < $this->max_threads[$task_name] && $queue->isEmpty()===false)
		        {
		            $task = $queue->extract();
		            // error_log('process task ' . print_r($task, true));

		            # Check for task timeout
		            $task_timeout = $task->do_not_launch_until;

		            if($task_timeout==null || ($task_timeout!==null && $task_timeout < (new DateTime('now'))) )
		            {						
						if($task->command!==null)
						{
							$log_file = tempnam($this->tmp, $task->name);

							$res = Scripts::run($task->command, $log_file);

							if($res['status']==='success')
			                {
			                	if($res['data']['pid'])
			                	{
			                		// error_log('pid : ' . $res['data']['pid']);

				                    $this->running_threads[$task->name]++;
				                    
				                    $this->running_pids[$task->name][] = (int)$res['data']['pid'];
				                    
				                    # Set Task status as running
				                    $task->status = 'running';
				                    $task->pid = (int)$res['data']['pid'];
				                    $task->start_date = new DateTime('now');
				                    $task->log_file = $res['data']['log'];
				                	$this->task_store->update($task);
				                }
				                else
				                {
				                    # Set Task status as closed or buggy
				                    $task->status = 'closed';
				                    $task->start_date = new DateTime('now');
				                    $task->end_date = new DateTime('now');
				                    $task->log_file = $res['data']['log'];
				                	$this->task_store->update($task);
				                }
			                }
			                else
			                {
			                	error_log('Error running script : ' . $res['message']);
			                	
			                	# Set Task status as failing
			                	$task->status = 'failed';
			                	$this->task_store->update($task);
			                }
						}
						else
						{
							error_log('Task Missing Command');
							// throw new Exception("Task Missing Command !", 1);
						}
			        }
			        else
			        {
			        	# task will be launched later
			        	error_log('Task will be launched later');
			        }

		            $queue->current();
		        }
		    }
	        
	        //
	        $this->is_tasking = false;
	    }
	}

	/**
	 *
	 */
	public function add_task($task)
	{
		return $this->task_store->save($task);
	}
}