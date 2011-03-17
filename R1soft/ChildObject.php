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

abstract class R1soft_ChildObject
  extends R1soft_Object {

  /**
   * The logical parent of this object. This object is not valid without a parent.
   *
   * @var R1soft_Object
   */
  private $_parent = null;

  /**
   *
   * @param R1soft_Object $parent
   * @param mixed $id
   */
  public function __construct( R1soft_Object $parent, $id ) {
    if( is_null( $parent ) ) {
      $this->_setInvalid();
      throw new R1soft_Object_Exception( 'ChildObject must have a valid parent' );
    } else {
      $this->_parent = $parent;
      parent::__construct( $parent->getConnection(), $id );
    }
  }

  /**
   * Get's the parent's API connection.
   *
   * @return R1soft_Remote
   */
  public function getConnection() {
    return $this->getParent()->getConnection();
  }

  /**
   * Get this object's parent.
   *
   * @return R1soft_Object
   */
  public function getParent() {
    $this->_checkValid();
    return $this->_parent;
  }

  /*
   * Overriden to handle using the parent's object ID.
   */
  protected function _populateFields( $id ) {
    if( is_string( $id ) && !preg_match( self::PATTERN_ID, $id ) ) {
      if( is_null( $this->_nameToken ) ) {
        throw new R1soft_Object_Exception( 'Object does not accept a name: ' . $id );
      } else {
        try {
          $byName = $this->_apiConnection->{$this->_ns}
                         ->{'get' . ucfirst( $this->_ns ) . 'By' . $this->_nameToken}(
                           $this->_parent->getID(), $id );
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
                            ->{'get' . ucfirst( $this->_ns ) . 'AsMap'}(
                              $this->_parent->getID(), $id );
    } catch( Zend_XmlRpc_Client_FaultException $e ) {
      if( $e->getCode() === R1soft_Remote::ERROR_OM_NOT_FOUND_EXCEPTION ) {
        throw new R1soft_Exception( 'Object not found: ' . $id );
      } elseif( $e->getCode() === R1soft_Remote::ERROR_METHOD_NOT_FOUND_EXCEPTION ) {
        try {
          var_dump( $this->_parent->getID(), $id);
          $this->_parseFields( $this->_apiConnection->{$this->_ns}
               ->{'get' . ucfirst( $this->_ns )}(
                 $this->_parent->getID(), $id ) );
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

  /*
   * Overridden to also invalidate this object connection to it's parent. Does
   * not effect the parent.
   */
  protected function _setInvalid() {
    $this->_parent = null;
    parent::_setInvalid();
  }
}