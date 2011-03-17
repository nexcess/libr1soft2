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

class R1soft_TaskRun
  extends R1soft_Object {

  /*
   * Log levels.
   */
  const LOG_INFO    = 20000;
  const LOG_WARN    = 30000;
  const LOG_ERROR   = 40000;
  const LOG_FATAL   = 50000;

  /*
   * Task states.
   */
  const STATE_ERROR       = -1;
  const STATE_STARTED     = 0;
  const STATE_FINISHED    = 1;
  const STATE_QUEUED      = 2;
  const STATE_CANCELLED   = 4;
  const STATE_INTERRUPTED = 8;

  protected $_ns        = 'taskRun';

  /**
   * Placeholder method, taskRun objects cannot be created manually.
   *
   * @param R1soft_Remote $connection
   * @param array $options 
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    throw new R1soft_Object_Exception( 'Cannot create new objects of type: ' . __CLASS__ );
  }

  /**
   * Overridden to make properties read-only.
   */
  public function __set( $name, $value ) {
    $this->_checkValid();
    $this->_magicError( debug_backtrace(), 'Attempt to set read-only property: ' . $name );
    return null;
  }

  /**
   * Get the logs for this TaskRun object. The returned array is rows of these
   * fields:
   *    string : taskLogMsgID
   *    string : taskRunID (this object)
   *    int : logLevel (one of LOG_*)
   *    string : time
   *    string : msg
   *
   * @return array
   */
  public function getLogs() {
    $this->_checkValid();
    return $this->_apiConnection->taskRun->getTaskLogs( $this->getID() );
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      'taskRunID'       => $fields[0],
      'taskName'        => $fields[1],
      'runState'        => $fields[2],
      'statusMessage'   => $fields[3],
      'progress'        => $fields[4],
      'startTime'       => $fields[5],
      'endTime'         => $fields[6],
      'hostID'          => $fields[7],
      'scheduledTaskID' => $fields[8],
      'runBy'           => $fields[9] ));
  }
}