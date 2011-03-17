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

class R1soft_Disksafe
  extends R1soft_ChildObject {

  protected $_ns        = 'diskSafe';

  /**
   * Create a new disksafe on the R1soft server, see notes from createHost()
   *
   * @param R1soft_Remote $connection a valid api connection
   * @param array $options list of parameters to create object with
   *
   * @return R1soft_Disksafe the newly create object
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    return self::_create( $connection, __CLASS__,
      array( $connection->diskSafe, 'addDiskSafe' ), $options );
  }

  /**
   * Delete the corresponding object on the R1soft server, this object is no
   * longer valid after deletion (method calls with throw
   * R1soft_Object_Exceptions)
   */
  public function delete() {
    $this->_checkValid();
    $this->_apiConnection->diskSafe->deleteDiskSafe( $this->_fields['hostID'],
                                                     $this->_fields['diskSafeID'] );
    $this->_setInvalid();
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      'diskSafeID'        => $field[0],
      'hostID'            => $field[1],
      'compressionLevel'  => $field[2],
      'encryptionType'    => $field[3],
      'diskUsage'         => $field[4],
      'timeCreated'       => $field[5] ));
  }
}