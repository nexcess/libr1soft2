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

abstract class R1soft_Object {

  /**
   * UUID pattern used by R1soft server for (most) objects.
   */
  const PATTERN_ID          = '~^[A-Za-z0-9]{8}-(?:[A-Za-z0-9]{4}-){3}[A-Za-z0-9]{12}$~';

  /**
   * Internal API connection used for method calls.
   *
   * @var R1soft_Remote
   */
  protected $_apiConnection = null;

  /**
   * Cache for remote object properties.
   *
   * @var array
   */
  protected $_fields        = array();

  /**
   * If the object type has a name R1soft knows about, this will be set to it.
   * Most object types do not have names.
   *
   * @var string
   */
  protected $_nameToken     = null;

  /**
   * XMLRPC API namespace for this object type.
   *
   * @var string
   */
  protected $_ns            = null;

  /**
   * Set to false when the object gets delete()'d
   * 
   * @var bool
   */
  private   $_valid         = true;

  abstract static public function create( R1soft_Remote $connection, array $options );

  /**
   * Generic method to create an new object on the R1soft server.
   *
   * @param R1soft_Remote $connection
   * @param string $class name of the new object's class
   * @param array $createMethod array of object and method name to pass to call_user_func_array
   * @param array $args args for new object creation
   * @return class instance of the newly created $class object
   */
  static protected function _create( R1soft_Remote $connection, $class,
                                      array $createMethod, array $args = array() ) {
    return new $class( $connection, call_user_func_array( $createMethod , $args ) );
  }

  /**
   *
   * @param R1soft_Remote $connection A valid API connection to an R1soft server.
   * @param mixed $id The unique ID used by the R1soft server to identify an
   *                   object, usually a string matching PATTERN_ID.
   */
  public function __construct( R1soft_Remote $connection, $id ) {
    $this->_apiConnection = $connection;
    try {
      $this->_populateFields( $id );
    } catch( Exception $e ) {
      $this->_setInvalid();
      throw $e;
    }
  }

  /**
   * Get string representation of object. Contains the objects class name and ID
   * (if known).
   *
   * @return string
   */
  public function __toString() {
    if( !$this->_valid ) {
      $id = '*deleted*';
    } elseif( isset( $this->_fields[$this->_getIDName()] ) ) {
      $id = $this->_fields[$this->_getIDName()];
    } else {
      $id = '*new*';
    }
    return sprintf( '%s(%s)', get_class( $this ), $id );
  }

  /**
   * Try to call a method from this object's namespace, with the object's ID as
   * the first argument.
   *
   * @param string $name name of the method we'll try to call
   * @param mixed $arguments method arguments
   * @return mixed method return value
   */
  public function __call( $name, $arguments ) {
    $this->_checkValid();
    //array_unshift( $arguments, $this->getID() );
    try {
      if( isset( $this->_fields[$this->_getIDName()] ) ) {
        array_unshift( $arguments, $this->_fields[$this->_getIDName()] );
      }
      return call_user_func_array(
        array( $this->_apiConnection->{$this->_ns}, $name ), $arguments );
    } catch( Zend_XmlRpc_Client_FaultException $e ) {
      if( $e->getCode() == R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
        $this->_magicError( debug_backtrace(), sprintf( 'Call to undefined method %s::%s()', get_class( $this ), $name ) );
      } else {
        throw $e;
      }
    }
  }

  /**
   * Access a property of this object. First looks in _fields, then tries remote
   * <namespace>.get<PropertyName>(). If both lookups fail, an error is printed
   * and null is returned.
   *
   * @param string $name
   * @return mixed
   */
  public function __get( $name ) {
    $this->_checkValid();
    if( isset( $this->_fields[$name] ) ) {
      return $this->_fields[$name];
    } else {
      try {
        $value = $this->_apiConnection->{$this->_ns}
                      ->{'get' . ucfirst( $name )}( $this->_fields[$this->_getIDName()] );
        $this->_fields[$name] = $value;
        return $value;
      } catch( Zend_XmlRpc_Client_FaultException $e ) {
        $this->_magicError( debug_backtrace(), 'Undefined property: ' . $name );
        return null;
      }
    }
  }

  /**
   * Set a property of this object. First tries to set the property on the
   * remote object (via <namespace>.set<PropertyName>(<objectID>, $value). If that
   * fails, it checks that field already exists locally and sets it if so,
   * otherwise, an error is printed. The method does not allow the object's ID to
   * be changed.
   *
   * @param string $name
   * @param mixed $value
   */
  public function __set( $name, $value ) {
    $this->_checkValid();
    if( $name == $this->_getIDName() ) {
      $this->_magicError( debug_backtrace(), 'Attempt to set read-only property: ' . $name );
    } else {
      try {
        $this->_apiConnection->{$this->_ns}
             ->{'set' . ucfirst( $name )}( $this->getID(), $value );
        $this->_fields[$name] = $value;
      } catch( Zend_XmlRpc_Client_FaultException $e ) {
        if( $e->getCode() == R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
          if( isset( $this->_fields[$name] ) ) {
            $this->_fields[$name] = $value;
          } else {
            $this->_magicError( debug_backtrace(), 'Undefined property: ' . $name );
          }
        } else {
          throw $e;
        }
      }
    }
  }

  /**
   * Get the object's internal API connection
   *
   * @return R1soft_Remote
   */
  public function getConnection() {
    $this->_checkValid();
    return $this->_apiConnection;
  }

  /**
   * Get the unique ID that the R1soft server uses to identify the object.
   *
   * @return mixed
   */
  public function getID() {
    $this->_checkValid();
    if( isset( $this->_fields[$this->_getIDName()] ) ) {
      return $this->_fields[$this->_getIDName()];
    } else {
      return null;
    }
  }

  /**
   * Get the name of the unique ID that the R1soft server uses to identify an object.
   *
   * @return string
   */
  protected function _getIDName() {
    return $this->_ns . 'ID';
  }

  /**
   * This method should handle parsing of the data pulled by _populateFields(),
   * generally just assigning keys to the lists the R1soft server will return.
   */
  abstract protected function _parseFields( array $fields );

  /**
   * Get the objects properties from the R1soft server. First tries to get the
   * data by object name if the $id doesn't look like a UUID (PATTERN_ID) since
   * it sometimes provides more (or better) data. If that fails, we try to get
   * the object's data by ID. If that fails, we don't know what this object is
   * so an exception will be thrown. This should really only be called in the
   * constructor.
   *
   * @param mixed $id
   */
  protected function _populateFields( $id ) {
    if( is_string( $id ) && !preg_match( self::PATTERN_ID, $id ) ) {
      if( is_null( $this->_nameToken ) ) {
        throw new R1soft_Object_Exception( 'Object does not accept a name: ' . $id );
      } else {
        try {
          $byName = $this->_apiConnection->{$this->_ns}
                         ->{'get' . ucfirst( $this->_ns ) . 'By' . $this->_nameToken}( $id );
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
      $this->_fields = $this->_apiConnection->{$this->_ns}
                            ->{'get' . ucfirst( $this->_ns ) . 'AsMap'}( $id );
    } catch( Zend_XmlRpc_Client_FaultException $e ) {
      if( $e->getCode() === R1soft_Remote::ERROR_OM_NOT_FOUND_EXCEPTION ) {
        throw new R1soft_Exception( 'Object not found: ' . $id );
      } elseif( $e->getCode() === R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
        try {
          $this->_parseFields( $this->_apiConnection->{$this->_ns}
               ->{'get' . ucfirst( $this->_ns )}( $id ) );
        } catch( Zend_XmlRpc_Client_FaultException $e ) {
          if( $e->getCode() === R1soft_Remote::ERROR_OM_NOT_FOUND_EXCEPTION ) {
            throw new R1soft_Exception( 'Object not found: ' . $id );
          } else {
            throw $e;
          }
        }
      } else {
        throw $e;
      }
    }
  }

  /**
   * Simple convenience method for making sure the object hasn't been
   * delete()'d. Should be called at the beginning of every public method.
   */
  final protected function _checkValid() {
    if( !$this->_valid ) {
      throw new R1soft_Object_Exception( 'Object is invalid (deleted)' );
    }
  }

  /**
   * Mark this object as invalid/deleted on the remote server.
   */
  protected function _setInvalid() {
    $this->_apiConnection = null;
    $this->_fields = null;
    $this->_valid = false;
  }

  /**
   * Convenience method for printing an error.
   *
   * @param array $backtrace Should be the result of debug_backtrace()
   * @param string $message The error to print.
   */
  final protected function _magicError( array $backtrace, $message ) {
    trigger_error( sprintf( '%s in %s on line %d', $message, $backtrace[0]['file'],
                            $backtrace[0]['line'] ), E_USER_NOTICE );
  }
}