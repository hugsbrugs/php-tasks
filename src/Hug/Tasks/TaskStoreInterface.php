<?php

namespace Hug\Tasks;

/**
 *
 */
interface TaskStoreInterface
{
	/**
	 * Saves a task
	 */
	public function save($task);

	/**
	 * Updates a Task
	 */
	public function update($task);

	/**
	 *
	 */
	public function update_closed($pids);

	/**
	 * Delete a Task
	 */
	public function delete($task);

	/**
	 * Get all Tasks with given status
	 */
	public function get_by_status($status);

	/**
	 * Reset Task List
	 */
	public function reset();

	/**
	 * Initialize Task Database Table
	 */
	// private function initialize();
	
}