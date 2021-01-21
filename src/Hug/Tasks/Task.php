<?php

namespace Hug\Tasks;

use JsonSerializable;
use DateTime;

/**
 * Task Class
 *
 * Contains all informations about a task
 */
class Task implements JsonSerializable
{
	public $id;

	public $pid;
	public $name;

	public $command;

	public $status;
	public $success;

	public $start_date;
	public $end_date;
	public $do_not_launch_until;
	
	public $log_file;
	public $relaunched;

	# Extra params Array for app specific needs
	public $params;
	
	/**
	 * Create task with main params
	 */
	function __construct($id, $pid, $name, $command, $params = null)
	{
		$this->id = $id;
		$this->pid = $pid;
		$this->name = $name;
		$this->command = $command;
		$this->status = 'pending';
		$this->params = $params;
	}

	/**
	 * Create task with all params
	 */
	public static function create($id, $pid, $name, $command, $status, $success, $start_date, $end_date, $do_not_launch_until, $log_file, $relaunched, $params)
	{
		$Task = new Task($id, $pid, $name, $command, $params);
		$Task->status = $status;
		$Task->success = $success;
		$Task->start_date = $start_date;
		$Task->end_date = $end_date;
		$Task->do_not_launch_until = $do_not_launch_until;
		$Task->log_file = $log_file;
		$Task->relaunched = $relaunched;
		return $Task;
	}

	/**
	 * For Testing
	 */
	public static function get_random_task()
	{
		$id = mt_rand(1, 10000);
		$name = 'random task ' . rand(1, 10000);
		$status = array_rand(['pending', 'running', 'closed', 'failed']);
		$params = [
			'project-id' => mt_rand(1, 10000)
		];
		return new Task($id, null, $name, $status, $params);
	}

	/**
	 *
	 */
	public function jsonSerialize()
	{
		# Convert DateTime to ISO8601 format
		$start_date = $this->start_date;
		if(is_object($this->start_date) && get_class($this->start_date)==='DateTime')
			$start_date = $this->start_date->format('Y-m-d H:i:s');

		$end_date = $this->end_date;
		if(is_object($this->end_date) && get_class($this->end_date)==='DateTime')
			$end_date = $this->end_date->format('Y-m-d H:i:s');

		$do_not_launch_until = $this->do_not_launch_until;
		if(is_object($this->do_not_launch_until) && get_class($this->do_not_launch_until)==='DateTime')
			$do_not_launch_until = $this->do_not_launch_until->format('Y-m-d H:i:s');

		return  [
			'id' => $this->id,
			'pid' => $this->pid,
			'name' => $this->name,
			'command' => $this->command,
			'status' => $this->status,
			'success' => $this->success,
			'start_date' => $start_date,
			'end_date' => $end_date,
			'do_not_launch_until' => $do_not_launch_until,
			'log_file' => $this->log_file,
			'relaunched' => $this->relaunched,
			'params' => $this->params,
		];
	}

}