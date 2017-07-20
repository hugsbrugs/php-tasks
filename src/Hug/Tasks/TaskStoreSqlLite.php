<?php

namespace Hug\Tasks;

use Hug\Database\SqlLiteDB as SqlLiteDB;

/**
 *
 */
class TaskStoreSqlLite implements TaskStoreInterface
{
	public $tasks = null; 
	public $tasks_db = null;
	
	/**
	 *
	 */
	function __construct()
	{
		$this->tasks_db = SqlLiteDB::getInstance(
			TASK_STORE_SQLLITE_FILE,
			TASK_STORE_SQLLITE_USER,
			TASK_STORE_SQLLITE_PASS,
			TASK_STORE_SQLLITE_DB,
			TASK_STORE_MYSQL_ENV
		);
		$this->load();
	}

	/**
	 *
	 */
	private function load()
	{
		# Check table exist
		if($this->tasks_db->table_exists(TASK_STORE_SQLLITE_TABLE))
		{
			# Create Table
			$this->initialize();
		}
		else
		{
			# Load Tasks
			$this->tasks = [];
			// $result = $pdo->query('SELECT * FROM '.TASK_STORE_SQLLITE_TABLE);
			$query = $this->tasks_db->dbh->prepare('SELECT * FROM '.TASK_STORE_SQLLITE_TABLE);
	        $query->execute();
	        while($row = $query->fetch())
	        {
	            //var_dump($row);
	            $this->tasks[] = new Task(
					$row['id'],
					$row['pid'],
					$row['name'],
					$row['command'],
					$row['status'],
					$row['success'],
					$row['start_date'],
					$row['end_date'],
					$row['do_not_launch_until'],
					$row['log_file'],
					$row['relaunched'],
					json_decode($row['params'])
					// unserialize($row['params']),
	            );
	        }
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
	public function save($task)
	{
		$saved = false;

		$id = null;
        $insert = $this->tasks_db->dbh->prepare('INSERT INTO '.TASK_STORE_MYSQL_TABLE.' (id, pid, name, command, status, success, start_date, end_date, do_not_launch_until, log_file, relaunched, params) VALUES (NULL, :pid, :name, :command, :status, :success, :start_date, :end_date, :do_not_launch_until, :log_file, :relaunched, :params)');

        $insert->execute([
			':pid' => $task->pid,
			':name' => $task->name,
			':command' => $task->command,
			':status' => $task->status,
			':success' => $task->success,
			':start_date' => get_class($task->start_date)=='DateTime' ? $task->start_date->format('Y-m-d H:i:s') : $task->start_date,
			':end_date' => get_class($task->end_date)=='DateTime' ? $task->end_date->format('Y-m-d H:i:s') : $task->end_date,
			':do_not_launch_until' => get_class($task->do_not_launch_until)=='DateTime' ? $task->do_not_launch_until->format('Y-m-d H:i:s') : $task->do_not_launch_until,
			':log_file' => $task->log_file,
			':relaunched' => $task->relaunched,
			// ':params' => serialize($task->params),
			':params' => json_encode($task->params),
        ]);
        $id = $this->tasks_db->dbh->lastInsertId();
        $insert->closeCursor();
        
        if($id!==null && $id > 0)
        {
            $saved = true;
            $task->id = $id;
            # Add task in list
            $this->tasks[] = $task;
        }

        return $saved;
	}
	
	/**
	 *
	 */
	public function update($task)
	{
		$updated = false;
        
        $update = $this->tasks_db->dbh->prepare('UPDATE '.TASK_STORE_MYSQL_TABLE.' SET pid=:pid, name=:name, command=:command, status=:status, success=:success, start_date=:start_date, end_date=:end_date, do_not_launch_until=:do_not_launch_until,
			log_file=:log_file, relaunched=:relaunched, params=:params WHERE id=:id');

        $update->execute([
            ':id' => $task->id,
			':pid' => $task->pid,
			':name' => $task->name,
			':command' => $task->command,
			':status' => $task->status,
			':success' => $task->success,
			':start_date' => get_class($task->start_date)=='DateTime' ? $task->start_date->format('Y-m-d H:i:s') : $task->start_date,
			':end_date' => get_class($task->end_date)=='DateTime' ? $task->end_date->format('Y-m-d H:i:s') : $task->end_date,
			':do_not_launch_until' => get_class($task->do_not_launch_until)=='DateTime' ? $task->do_not_launch_until->format('Y-m-d H:i:s') : $task->do_not_launch_until,
			':log_file' => $task->log_file,
			':relaunched' => $task->relaunched,
			':params' => json_encode($task->params)
        ]);           
        
        if($update->rowCount()===1)
        {
            $updated = true;

            # Update task in list
            foreach ($this->tasks as $key => $atask)
            {
            	if($atask->id===$task->id)
            	{
            		$this->tasks[$key] = $task;
            		break;
            	}
            }
        }
        
        $update->closeCursor();
        
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
		$is_updated = 0;

		foreach ($pids as $id)
		{
			if(isset($this->tasks->$id))
			{
				# Update Task status & end_date
				$this->tasks->$id->status = 'closed';
				$this->tasks->$id->pid = null;
				$this->tasks->$id->end_date = new DateTime('now');
				if($this->save($this->tasks->$id))
				{
					$is_updated++;
				}
			}
		}

		if($to_update===$is_updated)
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

        $delete = $this->tasks_db->dbh->prepare('DELETE FROM '.TASK_STORE_MYSQL_TABLE.' WHERE id=:id');
        
        $delete->execute([":id" => $task->id]);
        
        if($delete->rowCount()===1)
        {
            $deleted = true;

            # Delete task in list
            foreach ($this->tasks as $key => $atask)
            {
            	if($atask->id===$task->id)
            	{
            		unset($this->tasks[$key]);
            		break;
            	}
            }
        }
        
        $delete->closeCursor();

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
	 * Empty database
	 */
	public function reset()
	{
		$delete = $this->tasks_db->truncate_table(TASK_STORE_MYSQL_TABLE);
	}

	/**
	 * Initialize Task Database Table
	 */
	public function initialize()
	{
		$initialized = false;

		try
		{
			$table = TASK_STORE_MYSQL_TABLE;
			$command = <<<LABEL
CREATE TABLE IF NOT EXISTS `$table` ( 
id INTEGER PRIMARY KEY AUTOINCREMENT,
pid INTEGER,
name VARCHAR(100),
command VARCHAR(255),
status VARCHAR(20),
success INTEGER,
start_date DATETIME,
end_date DATETIME,
do_not_launch_until DATETIME,
log_file VARCHAR(255),
relaunched INTEGER,
params VARCHAR(255)
);
LABEL;
			$result = $this->tasks_db->dbh->query($command);
			// error_log('result : ' . $result);

			$initialized = true;
		}
		catch(PDOException $e)
		{
			error_log('initialize : ' . $e->getMessage());
		}

		return $initialized;
	}

}