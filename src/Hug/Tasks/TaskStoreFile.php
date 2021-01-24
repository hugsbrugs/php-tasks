<?php

namespace Hug\Tasks;

use Exception;
use DateTime;
use StdClass;

/**
 * Saves Task List in JSON file with Task PID as object key
 */
class TaskStoreFile implements TaskStoreInterface
{
	# Singleton
	private static $_instance = null;

	private $tasks = null; 
	private $tasks_file = null;

	/**
	 *
	 */
	private function __construct()
	{
		$this->tasks_file = TASK_STORE_FILE;
		$this->load();
	}

	/**
	 *
	 */
	private function load()
	{
		if(is_file($this->tasks_file) && is_readable($this->tasks_file))
		{
			# Load Tasks
			$this->tasks = [];

			$file = file_get_contents($this->tasks_file);
			$tasks = json_decode($file);
			if($tasks!==false)
			{
				foreach ($tasks as $key => $task)
				{
					$this->tasks[$task->id] = Task::create(
						$task->id,
						$task->pid,
						$task->name,
						$task->command,
						$task->status,
						$task->success,
						$task->start_date!==null ? DateTime::createFromFormat('Y-m-d H:i:s', $task->start_date) : null,
						$task->end_date!==null ? DateTime::createFromFormat('Y-m-d H:i:s', $task->end_date) : null,
						$task->do_not_launch_until!==null ? DateTime::createFromFormat('Y-m-d H:i:s', $task->do_not_launch_until) : null,
						$task->log_file,
						$task->relaunched,
		            	$task->params
					);
		        }
		    }
		}
		else
		{
			$this->initialize();
			$this->load();
			// throw new Exception("Error No Task File", 1);
		}
	}

	/**
	 * Get tasks
	 */
	public function tasks()
	{
		$this->load();
		return $this->tasks;
	}

	/**
	 *
	 */
	/*private function write_lock()
	{
		$f = fopen($this->tasks_file, 'a+');
		if (flock($f, LOCK_EX))
		{
		    // sleep(10);
		    fseek($f, 0);
		    // var_dump(fgets($f, 4048));
		    fwrite($f, json_encode($this->tasks, JSON_PRETTY_PRINT));
		    flock($f, LOCK_UN);
		}
	}*/

	/**
	 *
	 */
	private function write()
	{
		$writed = false;

		if(false !== $bytes = file_put_contents($this->tasks_file, json_encode($this->tasks, JSON_PRETTY_PRINT | LOCK_EX)))
		{
			$writed = true;
		}

		return $writed;
	}

	/**
	 *
	 */
	public function save($task)
	{
		$this->load();

		$saved = false;

		$id = $task->id;
		# Check Entry Does Not Exist
		if(!isset($this->tasks[$id]))
		{
			# Add Task
			$this->tasks[$id] = $task;
			# Save Tasks File
			$saved = $this->write();
		}
		else
		{
			error_log('Task Already Saved');
		}

		return $saved;
	}

	/**
	 *
	 */
	public function update($task)
	{
		$this->load();

		$updated = false;

		$id = $task->id;

		# Check Entry Exist
		if(isset($this->tasks[$id]))
		{
			# Replace Task
			$this->tasks[$id] = $task;
			# Save Tasks File
			$updated = $this->write();
		}
		else
		{
			throw new Exception("Task Not Found", 1);
			
		}

		return $updated;
	}

	/**
	 *
	 */
	public function update_closed($pids)
	{
		$this->load();

		$updated = false;

		$to_update = count($pids);
		$is_update = 0;

		foreach ($this->tasks as $id => $task)
		{
			if($task->status==='running')
			{
				$pid = $task->pid;
				if(in_array($pid, $pids))
				{
					error_log('close task : ' . $task->id);
					# Update Task status & end_date
					$this->tasks[$id]->status = 'closed';
					$this->tasks[$id]->pid = null;
					$this->tasks[$id]->end_date = new DateTime('now');
					$is_update++;
				}
			}
		}

		# Save Tasks File
		$updated_file = false;
		if($is_update > 0)
		{
			$updated_file = $this->write();
		}

		if($to_update===$is_update && $updated_file)
		{
			$updated = true;
		}

		return $updated;
	}


	/**
	 *
	 */
	public function delete($task)
	{
		$this->load();

		$deleted = false;

		$id = $task->id;
		# Check Entry Exist
		if(isset($this->tasks[$id]))
		{
			# Replace Task
			unset($this->tasks[$id]);
			# Save Tasks File
			$deleted = $this->write();
		}
		else
		{
			throw new Exception("Task Not Found", 1);
			
		}

		return $deleted;
	}

	/**
	 * Get all Tasks with given status
	 */
	public function get_by_status($status)
	{
		$this->load();

		$tasks = [];
		
		foreach ($this->tasks as $id => $task)
		{
			if($task->status===$status)
			{
				$tasks[$id] = $task;
			}
		}

		return $tasks;
	}

	/**
	 * Reset Task List
	 */
	public function reset()
	{
		$reseted = false;

		# Save Tasks File
		$this->tasks = [];
		$reseted = $this->write();

		return $reseted;
	}

	/**
	 * Initialize Task File with empty object
	 */
	private function initialize()
	{
		$initialized = false;

		try
		{
			# Save Tasks File
			if(file_put_contents(TASK_STORE_FILE, json_encode([], JSON_PRETTY_PRINT | LOCK_EX))!==false)
			{
				$initialized = true;
			}
		}
		catch(Exception $e)
		{
			error_log('Initialize : ' . $e->getMessage());
		}

		return $initialized;
	}

	/**
     * Singleton creation
     *
     * @param void
     * @return TaskStoreFile
     */
    public static function getInstance()
    {
    	if(is_null(self::$_instance)) 
    	{
    		self::$_instance = new TaskStoreFile();  
    	}
 
    	return self::$_instance;
    }

}