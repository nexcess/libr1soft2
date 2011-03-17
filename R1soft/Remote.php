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

class R1soft_Remote {

  /*
   * XMLRPC fault codes returned by the R1soft API.
   */
  const ERROR_UNKNOWN                           = 0;
  const ERROR_KEY_EXCEPTION                     = 1;
  const ERROR_OM_NOT_FOUND_EXCEPTION            = 11;
  const ERROR_OM_EXCEPTION                      = 12;
  const ERROR_CRON_EXCEPTION                    = 13;
  const ERROR_SERVER_EXCEPTION                  = 14;
  const ERROR_ACL_EXCEPTION                     = 16;
  const ERROR_NATIVE_EXCEPTION                  = 17;
  const ERROR_VOLUME_NOT_FOUND_EXCEPTION        = 18;
  const ERROR_METHOD_NOT_FOUND_EXCEPTION        = 404;

  /**
   * The default API request pacing delay in seconds.
   */
  const DEFAULT_REQUEST_DELAY                   = 0.3;

  /**
   * The internal xmlrpc client, really only used to get the proxy object.
   *
   * @var Zend_XmlRpc_Client
   */
  private $_client  = null;

  /**
   * This objects request pacing delay in seconds.
   *
   * @var double
   */
  private $_delay   = self::DEFAULT_REQUEST_DELAY;

  /**
   * The full URL for the R1soft API. Includes the username and password.
   *
   * @var string
   */
  private $_fullUrl = null;

  /**
   * The internal proxy to the R1soft server. All API requests go through this.
   *
   * @var Zend_XmlRpc_Client_ServerProxy
   */
  private $_proxy   = null;

  /**
   * Create a new connection to a R1soft server.
   *
   * @param string $host Hostname or IP address of the R1soft server.
   * @param string $username
   * @param string $password
   * @param bool $secure Use HTTPS
   */
  public function __construct( $host, $username, $password, $secure = true ) {
    $scheme = $secure ? 'https' : 'http';
    $port = $secure ? '8085' : '8084';
    $this->_fullUrl = sprintf( '%s://%s:%s@%s:%s/xmlrpc', $scheme, $username,
                               $password, $host, $port );
    $this->_client = new Zend_XmlRpc_Client( $this->_fullUrl );
    $this->_proxy = $this->_client->getProxy();
    $this->_testConnection();
  }

  /**
   * Pass through to the Zend_XmlRpc_Client_ServerProxy object
   *
   * @param string $ns proxy namespace to get
   * @return Zend_XmlRpc_Client_ServerProxy
   */
  public function __get( $ns ) {
    $this->_wait();
    return $this->_proxy->$ns;
  }

  /**
   * Create a new backup task on the R1soft server
   *
   * @param bool $enabled
   * @param string $description
   * @param string $hostID
   * @param int $recoveryPointsToKeep rotation policy
   * @param bool $runVerifyTask
   * @param bool $runDefragmentTask
   * @param bool $backupAllActiveDevices overrides diskDevicePaths/partitionDevicePaths
   * @param array<string> $diskDevicePaths ex: array('/dev/sda', '/dev/sdb')
   * @param array<string> $partitionDevicePaths ex: array('/dev/sda1', '/dev/sdb3')
   * @param int $frequency should be one of R1soft_BackupTask::FREQ_*
   * @param int $minutes
   * @param array<int> $hours
   * @param array<int> $days
   *
   * @return R1soft_BackupTask
   */
  public function createBackupTask() {
    return R1soft_BackupTask::create( $this, func_get_args() );
  }

  /**
   * Create a new disksafe on the R1soft server, see notes from createHost()
   *
   * @param string $hostID
   * @param int $compressionLevel should be 0 <= x <= 9
   * @param double $maxFreeSpace amount of free space allowed before defragmentation
   *
   * @return R1soft_Disksafe the newly create object
   */
  public function createDisksafe() {
    return R1soft_Disksafe::create( $this, func_get_args() );
  }

  /**
   * Create a new host on the R1soft server, note that this is not the same as
   * creating a new host *object*, although it does that as well.
   *
   * @param string $hostName server's (resolvable) hostname or IP address
   * @param int $hostType    should be one of R1soft_Host::TYPE_*
   * @param string $volumeID
   * @param bool $isEnabled
   * @param double $quota    set to -1.0 for unlimited
   *
   * @return R1soft_Host the newly created object
   */
  public function createHost() {
    return R1soft_Host::create( $this, func_get_args() );
  }

  /**
   * Create a new mysql instance on the R1soft server
   *
   * @param string $hostID
   * @param string $connectionString this should either be the unix socket path
   *                                 or hostname:port for the mysql connection
   * @param string $mysqlUser
   * @param string $mysqlPass
   * @param string $description
   *
   * @return R1soft_MysqlInstance
   */
  public function createMysqlInstance() {
    return R1soft_MysqlInstance::create( $this, func_get_args() );
  }

  /**
   * Create a new user on the R1soft server.
   *
   * @param string $username
   * @param string $emailAddress
   * @param string $password
   * @param bool $isEnabled
   * @param bool $isSuperUser
   * @param bool $canChangePassword
   * @param bool $mustChangePassword
   * @param bool $canAddUser
   * @param bool $canAddHost
   * 
   * @return R1soft_User the newly created object
   */
  public function createUser() {
    return R1soft_User::create( $this, func_get_args() );
  }

  /**
   * Create a new storage volume on the R1soft server
   *
   * @param string $volumeName
   * @param string $storagePoolID
   * @param int $maxLinuxHosts
   * @param int $maxWindowsHosts
   * @param double $quota set to -1.0 to ignore
   *
   * @return R1soft_Volume
   */
  public function createVolume() {
    return R1soft_Volume::create( $this, func_get_args() );
  }

  /**
   * Get the internal xmlrpc client object
   *
   * @return Zend_XmlRpc_Client
   */
  public function getClient() {
    return $this->_client;
  }

  /**
   * Get the current request delay amount in seconds.
   *
   * @return double
   */
  public function getDelay() {
    return $this->_delay;
  }

  /**
   * Get the internal server proxy object
   *
   * @return Zend_XmlRpc_Client_ServerProxy
   */
  public function getProxy() {
    return $this->_proxy;
  }

  /**
   * Set the request delay amount in seconds. Set to null to reset to default
   * value.
   *
   * @param double $amount seconds to delay
   */
  public function setDelay( $amount = null ) {
    if( is_null( $amount ) ) {
      $this->_delay = self::DEFAULT_REQUEST_DELAY;
    } elseif( $amount < 0 ) {
      $this->_delay = 0;
    } else {
      $this->_delay = (double)$amount;
    }
  }

  /**
   * Test the connection to the R1soft server to make sure we have valid login
   * info and that the server exists and is responding to requests. Called only
   * at object construction.
   */
  private function _testConnection() {
    if( false ) {
      throw new R1soft_Exception( 'Error setting up connection to R1soft API' );
    }
  }

  /**
   * Delay execution for $this->_delay seconds
   *
   * The r1soft API seems to not handle high request volume very well, which this
   * library ends up supplying. This method is used to pace the requests a small
   * amount to limit the errors from the transport (Zend_Http)
   */
  private function _wait() {
    if( $this->_delay ) {
      usleep( int( $this->_delay * 1000000 ) );
    }
  }
}