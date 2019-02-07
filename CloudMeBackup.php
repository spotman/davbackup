<?php
declare(strict_types=1);

/**
 * CloudMeBackup - backup in CloudMe.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.1.0
 * @link      https://github.com/dmamontov/davbackup
 * @since     Class available since Release 1.1.0
 */
class CloudMeBackup extends DavBackup
{
    /**
     * URL to the cloud
     */
    public const URL = 'http://webdav.cloudme.com/';

    /**
     * Sets variables
     *
     * @param string $login
     * @param string $password
     *
     * @access public
     */
    public function __construct(string $login, string $password)
    {
        $this->authtype = CURLAUTH_ANY;
        parent::__construct(self::URL."$login/CloudDrive/Documents/", $login, $password);
    }
}
