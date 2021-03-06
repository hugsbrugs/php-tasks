<?php

namespace Hug\Tasks;

use Exception;

/**
 *
 */
class TaskStore
{
	# Singleton
	private static $_instance = null;

	private $store = null;

	/**
	 *
	 */
	private function __construct()
	{
		$task_store_methods = ['file', 'mysql', 'sqllite'];
		if(defined('TASK_STORE_METHOD') && in_array(TASK_STORE_METHOD, $task_store_methods))
		{
			$this->load();
		}
		else
		{
			throw new Exception("INVAID OR MISSING TASK STORE METHOD", 1);
		}
	}

	/**
	 * Load Task Store
	 */
	public function load()
	{
		switch (TASK_STORE_METHOD)
		{
			case 'file':
				$this->store = TaskStoreFile::getInstance();
				break;
			case 'mysql':
				$this->store = TaskStoreMysql::getInstance();
				break;
			case 'sqllite':
				$this->store = TaskStoreSqlLite::getInstance();
				break;			
			default:
				throw new Exception("INVAID OR MISSING TASK STORE METHOD", 1);
				break;
		}
	}

	/**
	 * Get tasks
	 */
	public function tasks()
	{
		return $this->store->tasks();
	}

	/**
	 * Saves a task
	 */
	public function save($task)
	{
		return $this->store->save($task);
	}

	/**
	 * Updates a Task
	 */
	public function update($task)
	{
		return $this->store->update($task);
	}

	/**
	 *
	 */
	public function update_closed($pids)
	{
		return $this->store->update_closed($pids);
	}

	/**
	 * Delete a Task
	 */
	public function delete($task)
	{
		return $this->store->delete($task);
	}

	/**
	 * Get all Tasks with given status
	 */
	public function get_by_status($status)
	{
		return $this->store->get_by_status($status);
	}

	/**
	 * Reset Task List
	 */
	public function reset()
	{
		return $this->store->reset();
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
    		self::$_instance = new TaskStore();  
    	}
 
    	return self::$_instance;
    }
}