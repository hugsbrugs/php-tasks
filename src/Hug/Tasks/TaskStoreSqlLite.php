<?php

namespace Hug\Tasks;

use Hug\Database\SqlLiteDB as SqlLiteDB;

use DateTime;
use PDOException;

/**
 *
 */
class TaskStoreSqlLite implements TaskStoreInterface
{
	# Singleton
	private static $_instance = null;

	public $tasks = null; 
	public $tasks_db = null;
	
	/**
	 *
	 */
	private function __construct()
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
		if($this->tasks_db->table_exists(TASK_STORE_SQLLITE_TABLE)==false)
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
	            $this->tasks[$row['id']] = Task::create(
					$row['id'],
					$row['pid'],
					$row['name'],
					$row['command'],
					$row['status'],
					$row['success'],
					$row['start_date']!==null ? DateTime::createFromFormat('Y-m-d H:i:s', $row['start_date']) : null,
					$row['end_date']!==null ? DateTime::createFromFormat('Y-m-d H:i:s', $row['end_date']) : null,
					$row['do_not_launch_until']!==null ? DateTime::createFromFormat('Y-m-d H:i:s', $row['do_not_launch_until']) : null,
					$row['log_file'],
					$row['relaunched'],
	            	json_decode($row['params'])
				);
	        }
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
	public function save($task)
	{
		$saved = false;

		$id = null;
        $insert = $this->tasks_db->dbh->prepare('INSERT INTO '.TASK_STORE_SQLLITE_TABLE.' (id, pid, name, command, status, success, start_date, end_date, do_not_launch_until, log_file, relaunched, params) VALUES (NULL, :pid, :name, :command, :status, :success, :start_date, :end_date, :do_not_launch_until, :log_file, :relaunched, :params)');

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
			':params' => json_encode($task->params),
        ]);
        $id = $this->tasks_db->dbh->lastInsertId();
        $insert->closeCursor();
        
        if($id!==null && $id > 0)
        {
            $saved = true;
            $task->id = $id;
            # Add task in list
            $this->tasks[$task->id] = $task;
        }

        return $saved;
	}
	
	/**
	 *
	 */
	public function update($task)
	{
		$updated = false;
        
        $update = $this->tasks_db->dbh->prepare('UPDATE '.TASK_STORE_SQLLITE_TABLE.' SET pid=:pid, name=:name, command=:command, status=:status, success=:success, start_date=:start_date, end_date=:end_date, do_not_launch_until=:do_not_launch_until,
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
		$this->load();

		$updated = false;

		$to_update = count($pids);
		$is_updated = 0;

		foreach ($this->tasks as $id => $task)
		{
			if($task->status==='running')
			{
				if(in_array($task->pid, $pids))
				{
					# Update Task status & end_date
					$this->tasks[$id]->status = 'closed';
					$this->tasks[$id]->pid = null;
					$this->tasks[$id]->end_date = new DateTime('now');
					if($this->update($this->tasks[$id]))
					{
						$is_updated++;
					}
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
		$this->load();

		$deleted = false;

        $delete = $this->tasks_db->dbh->prepare('DELETE FROM '.TASK_STORE_SQLLITE_TABLE.' WHERE id=:id');
        
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
		$delete = $this->tasks_db->truncate_table(TASK_STORE_SQLLITE_TABLE);
	}

	/**
	 * Initialize Task Database Table
	 */
	private function initialize()
	{
		$initialized = false;

		try
		{
			$table = TASK_STORE_SQLLITE_TABLE;
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

	/**
     * Singleton creation
     *
     * @param void
     * @return TaskStoreMysql
     */
    public static function getInstance()
    {
    	if(is_null(self::$_instance)) 
    	{
    		self::$_instance = new TaskStoreSqlLite();  
    	}
 
    	return self::$_instance;
    }

}