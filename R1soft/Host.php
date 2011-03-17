<?php
/**
 * Nexcess.net libr1soft2   
 *
 * <pre>
 * +----------------------------------------------------------------------+
 * | Nexcess.net libr1soft2                                               |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 2006-2011 Nexcess.net L.L.C., All Rights Reserved.     |
 * +----------------------------------------------------------------------+
 * | Redistribution and use in source form, with or without modification  |
 * | is NOT permitted without consent from the copyright holder.          |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND |
 * | ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,    |
 * | THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A          |
 * | PARTICULAR PURPOSE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,    |
 * | EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,  |
 * | PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR   |
 * | PROFITS; OF BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY  |
 * | OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT         |
 * | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE    |
 * | USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH     |
 * | DAMAGE.                                                              |
 * +----------------------------------------------------------------------+
 * </pre>
 */

class R1soft_Host
  extends R1soft_Object {

  /*
   * Host types.
   */
  const TYPE_UNKNOWN    = -1;
  const TYPE_LINUX      = 0;
  const TYPE_WINDOWS    = 1;

  protected $_ns        = 'host';
  protected $_nameToken = 'Hostname';

  /**
   * Create a new host on the R1soft server, note that this is not the same as
   * creating a new host *object*, although it does that as well.
   *
   * @param R1soft_Remote $connection
   * @param array $options list of parameters to create object with
   *
   * @return R1soft_Host the newly created object
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    return self::_create( $connection, __CLASS__,
      array( $connection->host, 'addHost' ), $options );
  }

  /**
   * Create a new disksafe for this host
   *
   * @param int $compressionLevel
   * @param double $maxFreeSpace
   * @return R1soft_Disksafe the new disksafe object
   */
  public function addDisksafe( $compressionLevel, $maxFreeSpace ) {
    $this->_checkValid();
    return R1soft_Disksafe::create( $this->_apiConnection,
      array( $this->getID(), $compressionLevel, $maxFreeSpace ) );
  }

  /**
   * Create a new local (unix socket-based) mysql instance for this host
   *
   * @param string $mysqlUser
   * @param string $mysqlPass
   * @param string $socket full path to the mysql socket
   * @param string $description
   * @param string $dataDir full path to mysql data dir (see /etc/my.cnf)
   * @return R1soft_MysqlInstance
   */
  public function addLocalMySQLInstance( $mysqlUser, $mysqlPass,
    $socket = '/var/lib/mysql/mysql.sock', $description = 'mysqld',
    $dataDir = '/var/lib/mysql' ) {

    $this->_checkValid();
    try {
      return new R1soft_MysqlInstance( $this->_apiConnection,
        $this->_apiConnection->mySQL->addLocalMySQLInstance( $this->_fields['hostID'],
          $socket, $mysqlUser, $mysqlPass, $description ) );
    } catch( Zend_XmlRpc_Client_FaultException $e ) {
      if( $e->getCode() == R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
        //work around for undocumented method signature in v2.28
        try {
          return new R1soft_MysqlInstance( $this->_apiConnection,
            $this->_apiConnection->mySQL->addLocalMySQLInstance( $this->_fields['hostID'],
              $socket, $mysqlUser, $mysqlPass, $description, $dataDir, $dataDir,
              $dataDir ) );
        } catch( Zend_XmlRpc_Client_FaultException $f ) {
          throw $e;
        }
      } else {
        throw $e;
      }
    }
  }

  /**
   * Delete the corresponding object on the R1soft server, this object is no
   * longer valid after deletion (method calls with throw
   * R1soft_Object_Exceptions)
   */
  public function delete() {
    $this->_checkValid();
    $this->_apiConnection->host->deleteHost( $this->_fields['hostID'] );
    $this->_setInvalid();
  }

  /**
   * Get the active disksafe for this host.
   *
   * @return R1soft_Disksafe
   */
  public function getActiveDiskSafe() {
    $fields = $this->_apiConnection->diskSafe->getActiveDiskSafe( $this->getID() );
    return new R1soft_Disksafe( $this, $fields[0] );
  }

  /**
   * Get the last finished backup taskrun for this host, or null if there haven't
   * been any backups finished
   *
   * @return R1soft_TaskRun|null
   */
  public function getLastFinishedBackupTask() {
    $this->_checkValid();
    $result = $this->_apiConnection->host->getLastFinishedBackupTaskInfo( $this->_fields['hostID'] );
    if( implode( '', $result ) ) {
      //getLastFinishedBackupTaskInfo() returns taskRunID as string, need it as a double
      return new R1soft_TaskRun( $this->_apiConnection, (double)$result[2] );
    } else { //all fields are empty, no backup tasks have been completed
      return null;
    }
  }

  /**
   * Get a list of all the scheduled backup tasks for this host
   *
   * @return array<R1soft_BackupTask>
   */
  public function getScheduledTasks() {
    $this->_checkValid();
    $taskIDs = $this->_apiConnection->backupTask->getScheduledTaskIdsByHost( $this->_fields['hostID'] );
    $tasks = array();
    foreach( $taskIDs as $taskID ) {
      $tasks[] = new R1soft_BackupTask( $this->_apiConnection, $taskID );
    }
    return $tasks;
  }

  /**
   * Add a new scheduled backup task for this host
   *
   * @param string $description
   * @param int $recoveryPointsToKeep backup rotation policy
   * @param bool $runVerifyTask
   * @param bool $runDefragmentTask
   * @param array<array> $devices list of partitions (under 'partition' key) and
   *                               disks (under 'disk' key) to backup
   * @param int $frequency should be one of FREQ_*
   * @param int $minutes
   * @param array<int> $hours
   * @param array<int> $days
   * 
   * @return R1soft_BackupTask
   */
  public function scheduleBackupTask( $description = '', $recoveryPointsToKeep = 1,
                                        $runVerifyTask = false, $runDefragmentTask = false,
                                        array $devices = null,
                                        $frequency = R1soft_BackupTask::FREQ_ONCE,
                                        $minutes = 0, array $hours = array(),
                                        array $days = array() ) {
    $this->_checkValid();
    if( is_null( $devices ) ) {
      $backupAllActiveDevices = true;
      $diskDevicePaths = array();
      $partitionDevicePaths = array();
    } else {
      $backupAllActiveDevices = false;
      $diskDevicePaths = isset( $devices['disk'] ) ? $devices['disk'] : array();
      $partitionDevicePaths = isset( $devices['partition'] ) ? $devices['partition'] : array();
    }
    return new R1soft_BackupTask( $this->_apiConnection,
      $this->_apiConnection->backupTask->scheduleBackupTask( true, $description,
      $this->_fields['hostID'], $recoveryPointsToKeep, $runVerifyTask,
      $runDefragmentTask, $backupAllActiveDevices, $diskDevicePaths,
      $partitionDevicePaths, $frequency, $minutes, $hours, $days ) );
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      'hostID'      => $fields[0],
      'hostname'    => $fields[1],
      'hostType'    => $fields[2],
      'volumeID'    => $fields[3],
      'isEnabled'   => $fields[4],
      'controlPanelModuleEnabled'
                    => $fields[5],
      'quota'       => $fields[6],
      'diskUsage'   => $fields[7] ));
  }
}