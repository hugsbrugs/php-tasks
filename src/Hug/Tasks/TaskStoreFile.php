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
	public $tasks = null; 
	public $tasks_file = null;

	/**
	 *
	 */
	function __construct()
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
			$file = file_get_contents($this->tasks_file);
			$this->tasks = json_decode($file);
			// if($this->tasks!==false)
			// {
				
			// }
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
		return $this->tasks;
	}

	/**
	 *
	 */
	private function write_lock()
	{
		$f = fopen($this->tasks_file, 'a+');
		if (flock($f, LOCK_EX))
		{
		    // sleep(10);
		    fseek($f, 0);
		    // var_dump(fgets($f, 4048));
		    fwrite($f, json_encode($this->tasks));
		    flock($f, LOCK_UN);
		}
	}

	/**
	 *
	 */
	private function write()
	{
		$writed = false;

		if(file_put_contents($this->tasks_file, json_encode($this->tasks, JSON_PRETTY_PRINT | LOCK_EX))!==false)
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
		$saved = false;

		$id = $task->id;
		# Check Entry Does Not Exist
		if(!isset($this->tasks->$id))
		{
			# Add Task
			$this->tasks->$id = $task;
			# Save Tasks File
			$saved = $this->write();
		}
		else
		{
			error_log('Task Already Saved');
			// throw new Exception("Task Already Saved", 1);
			
		}

		return $saved;
	}

	/**
	 *
	 */
	public function update($task)
	{
		$updated = false;
		// error_log('TSF update task : ' . print_r($task, true));
		$this->load();

		$id = $task->id;
		// error_log('TSF update id : ' . $id);
		# Check Entry Exist
		if(isset($this->tasks->$id))
		{
			# Replace Task
			$this->tasks->$id = $task;
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
		$updated = false;

		$this->load();

		$to_update = count($pids);
		$is_update = 0;

		foreach ($this->tasks as $id => $task)
		{
			if($task->status==='running')
			{
				$pid = $task->pid;
				if(in_array($pid, $pids))
				{
					# Update Task status & end_date
					$this->tasks->$id->status = 'closed';
					$this->tasks->$id->pid = null;
					$this->tasks->$id->end_date = new DateTime('now');
					$is_update++;
				}
			}
		}

		# Save Tasks File
		$updated_file = $this->write();

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
		$deleted = false;

		$id = $task->id;
		# Check Entry Exist
		if(isset($this->tasks->$id))
		{
			# Replace Task
			unset($this->tasks->$id);
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
		$this->tasks = new StdClass();
		$reseted = $this->write();

		return $reseted;
	}

	/**
	 * Initialize Task File with empty object
	 */
	public function initialize()
	{
		$initialized = false;

		try
		{
			# Save Tasks File
			if(file_put_contents(TASK_STORE_FILE, json_encode(new StdClass(), JSON_PRETTY_PRINT | LOCK_EX))!==false)
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

}