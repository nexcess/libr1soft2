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

class R1soft_MysqlInstance
  extends R1soft_Object {

  /*
   * Mysql instance types
   */
  const TYPE_LOCAL      = 1;
  const TYPE_REMOTE     = 2;

  protected $_ns        = 'mySQL';

  /**
   * Create a new mysql instance for a host on the r1soft server
   *
   * @param R1soft_Remote $connection
   * @param array $options
   * @return R1soft_MysqlInstance
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    if( is_int( $options[2] ) ) {
      return self::_create( $connection, __CLASS__,
        array( $connection->mySQL, 'addRemoteMySQLInstance' ), $options );
    } else {
      return self::_create( $connection, __CLASS__,
        array( $connection->mySQL, 'addLocalMySQLInstance' ), $options );
    }
  }

  /**
   * Delete the corresponding object on the R1soft server, this object is no
   * longer valid after deletion (method calls with throw
   * R1soft_Object_Exceptions)
   */
  public function delete() {
    $this->_checkValid();
    $this->_apiConnection->mySQL->deleteMySQLInstance( $this->_fields['hostID'], $this->_fields['instanceID'] );
    $this->_setInvalid();
  }

  protected function _getIDName() {
    return 'instanceID';
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array(
      'instanceID'                  => $fields[0],
      'hostID'                      => $fields[1],
      'description'                 => $fields[2],
      'connectionType'              => $fields[3],
      'mySQLUser'                   => $fields[6],
      'customMySQLDataDirectory'    => $fields[7], //always blank (bug in api?)
      'customInnoDBDataDirectory'   => $fields[8],
      'customInnoDBLogDirectory'    => $fields[9] );
    if( $this->_fields['connectionType'] == self::TYPE_LOCAL ) {
      $this->_fields['mySQLSocketPath'] = $fields[4];
    } elseif( $this->_fields['connectionType'] == self::TYPE_REMOTE ) {
      $this->_fields['mySQLHost'] = $fields[4];
      $this->_fields['mySQLPort'] = $fields[5];
    } else {
      throw new R1soft_Object_Exception( 'Unrecognized connection type: ' .
        $this->_fields['connectionType'] );
    }
  }

  protected function  _populateFields( $id ) {
    if( !preg_match( self::PATTERN_ID, $id ) ) {
      throw new R1soft_Object_Exception( 'Object does not accept a name: ' . $id );
    } else {
      try {
        $this->_parseFields( $this->_apiConnection->mySQL->getMySQLInstance( $id ) );
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