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

class R1soft_Storagepool
  extends R1soft_Object {

  protected $_ns        = 'storagepool';
  protected $_nameToken = 'Name';

  /**
   * Placeholder method, Storagepool objects cannot be created manually
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

  protected function _getIDName() {
    return 'storagePoolID';
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      'storagePoolID'   => $fields[0],
      'storagePoolName' => $fields[1] ));
  }

  /**
   * Overridden to handle unique get<object> method names.
   */
  protected function _populateFields( $id ) {
    if( is_string( $id ) && !preg_match( self::PATTERN_ID, $id ) ) {
      if( is_null( $this->_nameToken ) ) {
        throw new R1soft_Object_Exception( 'Object does not accept a name: ' . $id );
      } else {
        try {
          $byName = $this->_apiConnection->storagepool->getStoragePoolByName( $id );
        } catch( Zend_XmlRpc_Client_FaultException $e ) {
          if( $e->getCode() === R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
            throw new R1soft_Object_Exception( 'Invalid object id: ' . $id );
          } elseif( $e->getCode() === R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
            throw new R1soft_Exception( 'Object not found: ' . $id );
          } else {
            throw $e;
          }
        }
        $id = $byName[0];
      }
    }
    try {
      $this->_parseFields( $this->_apiConnection->storagepool->getStoragePool( $id ) );
    } catch( Zend_XmlRpc_Client_FaultException $e ) {
      if( $e->getCode() === R1soft_Remote::ERROR_OM_NOT_FOUND_EXCEPTION ) {
        throw new R1soft_Exception( 'Object not found: ' . $id );
      } else {
        throw $e;
      }
    }
  }
}