<?php
declare(strict_types=1);

/**
 * OneDriveBackup - backup in OneDrive.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.1.0
 * @link      https://github.com/dmamontov/davbackup
 * @since     Class available since Release 1.1.0
 * @todo      microsoft temporarily disable access to WebDAV
 */
class OneDriveBackup extends DavBackup
{
    /**
     * URL to the cloud
     */
    const URL = 'https://d.docs.live.net/';

    /**
     * Sets variables
     *
     * @param string $url
     * @param string $login
     * @param string $cid
     *
     * @return void
     * @access public
     */
    public function __construct($login, $password, $cid)
    {
        throw new RuntimeException('Microsoft temporarily disable access to WebDAV');
        //parent::__construct(self::URL . $cid . '/', (string) $login, (string) $password);
    }
}
