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

class R1soft_Volume
  extends R1soft_Object {

  protected $_ns        = 'volume';
  protected $_nameToken = 'Name';

  /**
   * Create a new volume on the R1soft server.
   *
   * @param R1soft_Remote $connection
   * @param array $options
   * @return R1soft_Volume
   */
  static public function create( R1soft_Remote $connection, array $options ) {
    return self::_create( $connection, __CLASS__,
      array( $connection->volume, 'addVolume' ), $options );
  }

  /**
   * Overridden to handle 'allowedScheduleFrequencies' array.
   */
  public function __set( $name, $value ) {
    if( $name == 'allowedScheduleFrequencies' && is_array( $value ) ) {
      $this->_checkValid();
      $this->_apiConnection->volume->setAllowedScheduleFrequencies( $this->_fields['volumeID'],
        $value[0], $value[1], $value[2], $value[3], $value[4] );
      $this->_fields[$name] = $value;
    } else {
      parent::__set( $name, $value );
    }
  }

  /**
   * Delete the corresponding object on the R1soft server, this object is no
   * longer valid after deletion (method calls with throw
   * R1soft_Object_Exceptions)
   */
  public function delete() {
    $this->_checkValid();
    $this->_apiConnection->volume->deleteVolume( $this->_fields['volumeID'] );
    $this->_setInvalid();
  }

  protected function _parseFields( array $fields ) {
    $this->_fields = array_merge( $this->_fields, array(
      'volumeID'        => $fields[0],
      'volumeName'      => $fields[1],
      'storagePoolID'   => $fields[2],
      'maxLinuxHosts'   => $fields[3],
      'maxWindowsHosts' => $fields[4],
      'isControlPanelModuleEnabled'
                        => $fields[5],
      'quota'           => $fields[6],
      'diskUsage'       => $fields[7] ));
  }
}