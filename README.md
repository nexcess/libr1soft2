libr1soft2
==========
About
-----
libr1soft2 is a PHP toolkit for working with the R1soft CDP v2.0 XMLRPC API,
intended for use in a larger project or just in a small script to automate
some of the common tasks R1soft users do.

---

Requirements
------------
* PHP >=5.2 (PHP 5.1 is untested but may work)
* ZendFramework
* A R1soft CDP v2.0 server

---

Install/Config
--------------
Since this is intended to be used in other projects, rather than as a stand-alone
tool, there is no real installation. However, due to a bug in either ZendFramework
or the R1soft XMLRPC API implementation, ZendFramework will need to be patched
with the Zend\_XmlRpc\_Request.php.patch file.  
The bug is that the original Zend\_XmlRpc\_Request class will not add a <params/>
tag if the method being called has no parameters, which causes the R1soft XMLRPC
implementation to not handle the request correctly (read: at all). Note that since
there are very few methods in the API that have no parameters so you could
technically get away without using the patch if you stayed away from those
methods, but libr1soft2 has not been written with this workaround in mind so the
results are undefined.

---

Usage
-----
There are two basic ways to use libr1soft2:  
1) Using the stand-alone R1soft\_Remote object.  
2) Using the full R1soft\_Object object model.  

For the first, usage is very simple since the R1soft\_Remote is just a simple
wrapper around the Zend\_XmlRpc_Client\_ServerProxy object. Here is an example
(note that this assumes that there is an automatic class loader in use):

    $server = new R1soft_Remote( 'r1soft.example.com', 'user', '12345', true );
    //you can now use $server like a normal proxy object
    $host = $server->host->getHostByHostname( 'host.example.com' );
    //check the R1soft API documentation for the meaning of the returned array:
    //  http://wiki.r1soft.com/display/R1D/CDP+Server+API+Guide
    $hostID = $host[0];
    if( $server->host->isHostDiscoveryFinished( $hostID ) ) {
        $server->backupTask->scheduleBackupTask(
            true, 'backup test', $hostID, 1, true, true, true, array(), array(),
            7, 0, array(), array() );
    }

The object model is more complex:

    $api = new R1soft_Remote( 'r1soft.example.com', 'user', '12345', true );
    //get a host object
    $host = new R1soft_Host( $api, 'host.example.com' );
    //get it's active disk safe
    $disksafe = $host->getActiveDiskSafe();
    //add a new disk safe with compression level 1 and allow 5% free space before defrag
    $newDisksafe = $host->addDiskSafe( 1, 5.0 );
    //delete the old disk safe, any further calls on the $disksafe object will throw
    // a R1soft_Object_Exception
    $disksafe->delete();
    //all the objects automatically inherit the methods in their namespace so
    //  just because a method isn't implemented directly in libr1soft2 doesn't
    //  mean it's not available. You can use most set/get property like methods
    //  as actual properties.
    $user = new R1soft_User( $api );
    $user->emailAddress = 'email@example.com';
    $host->addUser( $user->getID() );
