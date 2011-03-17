<?php
/**
 * Nexcess.net php-libr1soft
 *
 * <pre>
 * +----------------------------------------------------------------------+
 * | Nexcess.net php-libr1soft                                            |
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

class R1soft_BackupTask
  extends R1soft_Object {

  /*
   * Backup task recurrence values.
   */
  const FREQ_MINUTELY   = 1;
  const FREQ_HOURLY     = 2;
  const FREQ_DAILY      = 3;
  const FREQ_WEEKLY     = 4;
  const FREQ_MONTHLY    = 5;
  const FREQ_ONCE       = 7;

  protected $_ns        = 'backupTask';

  /**
   * Create a new backup task on the R1soft server
   *
   * @param R1soft_Remote $connection
   * @param array $options list of parameters to create object with
   *
   * @return R1soft_BackupTask
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    return self::_create( $connection, __CLASS__,
      array( $connection->backupTask, 'scheduleBackupTask' ), $options );
  }

  /**
   * Delete the corresponding object on the R1soft server, this object is no
   * longer valid after deletion (method calls with throw
   * R1soft_Object_Exceptions)
   */
  public function delete() {
    $this->_checkValid();
    $this->_apiConnection->backupTask->deleteScheduleTask( $this->getID() );
    $this->_setInvalid();
  }

  /**
   * Run this backup task (asynchronous)
   *
   * @return R1soft_TaskRun
   */
  public function run() {
    $this->_checkValid();
    return new R1soft_TaskRun( $this->_apiConnection,
      $this->_apiConnection->backupTask->runNow( $this->getID() ) );
  }

  protected function _getIDName() {
    return 'scheduledTaskID';
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      $this->_getIDName() => $fields['scheduledTaskId'],
      'hostID'            => $fields['hostId'],
      'enabled'           => $fields['enabled'],
      'description'       => $fields['description'],
      'taskType'          => $fields['taskType'],
      'recurrenceType'    => $fields['recurrenceType'] ),
      $this->_apiConnection->backupTask->getTask( $fields['scheduledTaskId'] ) );
  }

  /**
   * Overridden because the backupTask methods have an different naming scheme.
   */
  protected function  _populateFields( $id ) {
    if( !preg_match( self::PATTERN_ID, $id ) ) {
      throw new R1soft_Object_Exception( 'Object does not accept a name: ' . $id );
    } else {
      try {
        $this->_parseFields( $this->_apiConnection->backupTask->getScheduledTaskSummary( $id ) );
      } catch( Zend_XmlRpc_Client_FaultException $e ) {
        if( $e->getCode() === R1soft_Remote::ERROR_OM_NOT_FOUND_EXCEPTION ) {
          throw new R1soft_Exception( 'Object not found: ' . $id );
        } else {
          throw $e;
        }
      }
    }
  }
}