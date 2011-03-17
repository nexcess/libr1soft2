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

class R1soft_User
  extends R1soft_Object {

  protected $_ns        = 'user';
  protected $_nameToken = 'Username';

  /**
   * Create a new user on the r1soft server
   *
   * @param R1soft_Remote $connection
   * @param array $options
   * @return R1soft_User
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    return self::_create( $connection, __CLASS__,
      array( $connection->user, 'addUser' ), $options );
  }

  /**
   * Overridden to handle special getMyId method for convenience.
   */
  public function __construct( R1soft_Remote $connection, $user = null ) {
    if( is_null( $user ) ) {
      parent::__construct( $connection, $connection->user->getMyId() );
    } else {
      parent::__construct( $connection, $user );
    }
  }

  /**
   * Delete the corresponding object on the R1soft server, this object is no
   * longer valid after deletion (method calls with throw
   * R1soft_Object_Exceptions)
   */
  public function delete() {
    $this->_checkValid();
    $this->_apiConnection->user->deleteUser( $this->getID() );
    $this->_setInvalid();
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      'userID'              => $fields[0],
      'userName'            => $fields[1],
      'emailAddress'        => $fields[2],
      'isEnabled'           => $fields[3],
      'isSuperUser'         => $fields[4],
      'canChangePassword'   => $fields[5],
      'mustChangePassword'  => $fields[6],
      'canAddUser'          => $fields[7],
      'canAddHost'          => $fields[8] ) );
  }
}