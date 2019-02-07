<?php
declare(strict_types=1);

/**
 * DropBoxBackup - backup in DropBox.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.1.0
 * @link      https://github.com/dmamontov/davbackup
 * @since     Class available since Release 1.1.0
 * @todo      working through service dropdav.com
 */
class DropBoxBackup extends DavBackup
{
    /**
     * URL to the cloud
     */
    public const URL = 'https://dav.dropdav.com/';

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
        parent::__construct(self::URL, $login, $password);
    }
}
