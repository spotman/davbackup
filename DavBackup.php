<?php
/**
 * DavBackup
 *
 * Copyright (c) 2015, Dmitry Mamontov <d.slonyara@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Dmitry Mamontov nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   davbackup
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.1.0
 */

/**
 * DavBackup main class implements backup and sending it to the cloud.
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.1.0
 * @link      https://github.com/dmamontov/davbackup
 * @since     Class available since Release 1.1.0
 * @abstract
 */
abstract class DavBackup
{
    /**
     * Directory for the temporary storage of backups
     */
    const TEMPORARY_DIRECTORY_NAME = 'tmp';

    /**
     * Archive Type TAR
     */
    const TAR = 0;

    /**
     * Archive Type ZIP
     */
    const ZIP = 1;

    /**
     * Archive Type RAR
     */
    const RAR = 2;

    /**
     * URL to the cloud
     *
     * @var string
     * @access private
     */
    private $url;

    /**
     * Authorization data
     *
     * @var array
     * @access private
     */
    private $credentials;

    /**
     * The real path to the temporary directory.
     *
     * @var string
     * @access private
     */
    private $realTemporaryDirectory;

    /**
     * The name of the directory on the server.
     *
     * @var string
     * @access protected
     */
    protected $remoteDirectoryName;

    /**
     * Path to the directory you want to backup
     *
     * @var string
     * @access protected
     */
    protected $path;

    /**
     * The connection to the database
     *
     * @var PDO
     * @access protected
     */
    protected $connection;

    /**
     * The prefix name of the archive
     *
     * @var string
     * @access protected
     */
    protected $prefix;

    /**
     * Archive Type
     *
     * @var integer
     * @access protected
     */
    protected $type = 0;

    /**
     * Compression
     *
     * @var bool
     * @access protected
     */
    protected $compression = false;

    /**
     * Remove backup file
     *
     * @var bool
     * @access protected
     */
    protected $removeFile = true;

    /**
     * Type of authorization
     *
     * @var string
     * @access protected
     */
    protected $authtype = '';

    /**
     * Sets variables and creates the required directory
     *
     * @param string $url
     * @param string $login
     * @param string $password
     *
     * @access protected
     */
    protected function __construct($url, $login, $password)
    {
        ini_set('memory_limit', '-1');

        $this->checkType($this->type);
        $this->checkUrl($url);
        $this->url = $url;

        $this->checkCredentials($login, $password);
        $this->credentials = [$login, $password];

        $this->prefix = (string)time();

        $this->realTemporaryDirectory = sprintf('%s/%s/', __DIR__, self::TEMPORARY_DIRECTORY_NAME);

        if (!file_exists($this->realTemporaryDirectory)) {
            mkdir($this->realTemporaryDirectory, 0755);
        }
    }

    /**
     * Validates the server address.
     *
     * @param string $url
     *
     * @throws InvalidArgumentException
     */
    final private function checkUrl($url): void
    {
        if (!is_string($url) || stripos($url, 'http') === false) {
            throw new InvalidArgumentException('Invalid value for the server address.');
        }
    }

    /**
     * Validates the data for authorization.
     *
     * @param string $login
     * @param string $password
     *
     * @throws InvalidArgumentException
     */
    private function checkCredentials(string $login, string $password): void
    {
        if (empty($login)) {
            throw new InvalidArgumentException('The `login` can not be empty');
        }
        if (empty($password)) {
            throw new InvalidArgumentException('The `password` can not be empty');
        }
    }

    /**
     * Set the compression of the archive
     *
     * @param bool $compression
     *
     * @return DavBackup
     * @access public
     * @final
     */
    final public function setCompression(bool $compression = null): self
    {
        $this->compression = $compression ?? true;

        return $this;
    }

    /**
     * Gets a compressed archive
     *
     * @return bool
     * @access public
     * @final
     */
    final public function isCompressed(): bool
    {
        return $this->compression;
    }

    /**
     * Set remove backup file
     *
     * @param bool $removeFile
     *
     * @return DavBackup
     * @access public
     * @final
     */
    final public function setRemoveFile($removeFile = true): self
    {
        $this->removeFile = (bool)$removeFile;

        return $this;
    }

    /**
     * Gets a remove backup file
     *
     * @return bool
     * @access public
     * @final
     */
    final public function isRemoveFile(): bool
    {
        return $this->removeFile;
    }

    /**
     * Sets prefix of the archive
     *
     * @param string $prefix
     * @param bool   $prependTimestamp
     *
     * @return DavBackup
     * @access public
     * @final
     */
    final public function setPrefix(string $prefix, bool $prependTimestamp = null): self
    {
        if (!empty($prefix)) {
            $this->prefix = $prependTimestamp
                ? sprintf('%s-%s', time(), $this->clearPrefix($prefix))
                : sprintf('%s', $this->clearPrefix($prefix));
        }

        return $this;
    }

    /**
     * Drops a prefix of the archive
     *
     * @return $this
     * @access public
     * @final
     */
    final public function resetPrefix(): self
    {
        $this->prefix = null;

        return $this;
    }

    /**
     * It gets prefix of the archive
     *
     * @return string
     * @access public
     * @final
     */
    final public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Replace invalid characters in the prefix.
     *
     * @param string $prefix
     *
     * @return string
     */
    private function clearPrefix(string $prefix): string
    {
        return str_replace([' ', "\n", "\t", '_'], '-', strtolower($prefix));
    }

    /**
     * Sets type of archive
     *
     * @param integer $type
     *
     * @return DavBackup
     * @access public
     * @final
     */
    final public function setType(int $type = null)
    {
        $type = $type ?? 0;

        if (in_array($type, [self::TAR, self::ZIP, self::RAR], true) && $this->checkType($type)) {
            $this->type = (int)$type;
        }

        return $this;
    }

    /**
     * Gets type of archive
     *
     * @return string
     * @access public
     * @final
     */
    final public function getType(): string
    {
        switch ($this->type) {
            default:
            case self::TAR:
                return 'tar';
            case self::ZIP:
                return 'zip';
            case self::RAR:
                return 'rar';
        }
    }

    /**
     * Checks classes for archive types.
     *
     * @param integer $type
     *
     * @throws RuntimeException
     * @return boolean
     */
    private function checkType($type): bool
    {
        switch ($type) {
            case self::TAR:
                if (!class_exists('PharData')) {
                    throw new RuntimeException('PharData class is not in the system, try to select another file type.');
                }

                return true;

            case self::ZIP:
                if (!class_exists('ZipArchive')) {
                    throw new RuntimeException('ZipArchive class is not in the system, try to select another file type.');
                }

                return true;

            case self::RAR:
                if (!class_exists('RarArchiver')) {
                    throw new RuntimeException(
                        'RarArchiver class is not in the system, try to select another file type. It can be downloaded via the link https://github.com/dmamontov/rararchiver.'
                    );
                }

                return true;

            default:
                return false;
        }
    }

    /**
     * Sets the directory you want to backup
     *
     * @param string $path
     *
     * @throws InvalidArgumentException
     * @return DavBackup
     * @access public
     * @final
     */
    final public function setPath($path): self
    {
        if (file_exists($path) && is_dir($path)) {
            $this->path = realpath($path);
        } else {
            throw new InvalidArgumentException('The specified path does not exist or is not a directory.');
        }

        return $this;
    }

    /**
     * @param string $user
     * @param string $pass
     * @param string $database
     * @param string $host
     * @param string $driver
     *
     * @throws InvalidArgumentException
     * @return DavBackup
     * @access public
     * @final
     */
    final public function setDbConnection(
        string $user,
        string $pass,
        string $database,
        string $host = null,
        string $driver = null
    ): self {
        $host   = $host ?? 'localhost';
        $driver = $driver ?? 'mysql';

        try {
            $this->connection = new PDO(sprintf('%s:host=%s;dbname=%s', $driver, $host, $database), $user, $pass);
        } catch (PDOException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $this->connection->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);

        return $this;
    }

    /**
     * @param string $dir
     *
     * @return \DavBackup
     */
    public function setRemoteDirectoryName(string $dir): self
    {
        $this->remoteDirectoryName = $dir;

        return $this;
    }

    /**
     * It creates and sends the file to a remote server.
     *
     * @return DavBackup
     * @throws RuntimeException
     * @access public
     * @final
     */
    final public function execute(): self
    {
        // Cleanup temp directory
        $this->deleteDirectory($this->realTemporaryDirectory, true);

        $archive = $this->getArchive();

        if ($this->connection instanceof PDO) {
            $this->createDbBackup();
            if (file_exists(sprintf('%s%s.sql', $this->realTemporaryDirectory, $this->prefix))) {
                $archive->addFile(
                    sprintf('%s%s.sql', $this->realTemporaryDirectory, $this->prefix),
                    sprintf('sql/%s.sql', $this->prefix)
                );
            }
        }

        $this->checkDavConnection();

        $this->createRemoteFolder();

        if ($this->path !== null) {
            $archive = $this->addFiles($archive);
        }

        if ($this->type === self::ZIP) {
            $archive->close();
        } elseif ($this->compression && $this->type === self::TAR) {
            $archive->compress(Phar::GZ);
            @unlink(sprintf('%s%s.tar', $this->realTemporaryDirectory, $this->prefix));
        }

        $realName = $this->getRealName();
        $realPath = sprintf('%s%s', $this->realTemporaryDirectory, $realName);

        if (file_exists($realPath)) {
            $fullUrl = $this->remoteDirectoryName
                ? sprintf('%s%s/%s', $this->url, $this->remoteDirectoryName, $realName)
                : sprintf('%s%s', $this->url, $realName);

            $send = $this->request($fullUrl, ['Content-type: application/octet-stream'], 'PUT', $realPath);

            if (file_exists(sprintf('%s%s.sql', $this->realTemporaryDirectory, $this->prefix))) {
                @unlink(sprintf('%s%s.sql', $this->realTemporaryDirectory, $this->prefix));
            }

            if (!in_array($send->code, [201, 204], true)) {
                throw new RuntimeException('There was an error sending the archive', $send->code);
            }

            if ($this->isRemoveFile()) {
                @unlink(sprintf('%s%s', $this->realTemporaryDirectory, $realName));
            }
        }

        return $this;
    }

    protected function deleteDirectory(string $dir, bool $keepSelf = null): bool
    {
        $files = array_diff(scandir($dir, null), ['.', '..']);

        foreach ($files as $file) {
            is_dir("$dir/$file")
                ? $this->deleteDirectory("$dir/$file")
                : unlink("$dir/$file");
        }

        return $keepSelf ? true : rmdir($dir);
    }

    /**
     * Get the real name of the archive.
     *
     * @return string
     * @access private
     * @final
     */
    final public function getRealName(): string
    {
        $realName = '';

        switch ($this->type) {
            case self::TAR:
                $realName = sprintf('%s.tar%s', $this->prefix, $this->compression === true ? '.gz' : '');
                break;
            case self::ZIP:
                $realName = sprintf('%s.zip', $this->prefix);
                break;
            case self::RAR:
                $realName = sprintf('%s.rar', $this->prefix);
                break;
        }

        return $realName;
    }

    private function checkDavConnection(): self
    {
        $result = $this->request($this->url, ['Depth: 0'], 'PROPFIND');

        if (!in_array($result->code, [201, 204, 207], true)) {
            throw new RuntimeException(
                sprintf('Failed to connect to %s (got %s response)', $this->url, $result->code),
                $result->code
            );
        }

        return $this;
    }

    /**
     * Create a directory on a remote server.
     *
     * @throws RuntimeException
     * @return DavBackup
     * @access private
     * @final
     */
    private function createRemoteFolder(): self
    {
        $folder = sprintf('%s%s', $this->url, $this->remoteDirectoryName);

        $result = $this->request($folder, ['Depth: 0'], 'PROPFIND');
        if ($result->code === 404) {
            $result = $this->request($folder, [], 'MKCOL');
        }

        if (!in_array($result->code, [201, 204, 207], true)) {
            throw new RuntimeException(
                sprintf('Failed to create remote directory %s (got %s response), ', $folder, $result->code),
                $result->code
            );
        }

        return $this;
    }

    /**
     * It creates a database dump
     *
     * @return DavBackup
     * @access private
     * @final
     */
    final private function createDbBackup()
    {
        $types = [
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'bigint',
            'float',
            'double',
            'decimal',
            'real',
        ];

        $file = new SplFileObject(sprintf('%s%s.sql', $this->realTemporaryDirectory, $this->prefix), 'a+');

        $tables = $this->getTables();

        foreach ($tables as $table) {
            $sql    = $this->connection->query(sprintf('SELECT * FROM %s', $table));
            $column = $sql->columnCount();
            $rows   = $sql->rowCount();

            $result = sprintf('DROP TABLE IF EXISTS %s;', $table);

            $structure = $this->connection->query(sprintf('SHOW CREATE TABLE %s', $table));
            $row       = $structure->fetch(PDO::FETCH_NUM);
            $result    .= sprintf("\n\n%s;\n\n", str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $row[1]));
            unset($structure);

            $file->fwrite($result);
            $result = '';
            $type   = [];

            if ($rows) {
                $result  = sprintf('INSERT INTO %s (', $table);
                $columns = $this->connection->query(sprintf('SHOW COLUMNS FROM %s', $table));

                $count = 0;

                while ($row = $columns->fetch(PDO::FETCH_NUM)) {
                    $type[$table][] = stripos($row[1], '(') ? stristr($row[1], '(', true) : $row[1];
                    $result         .= $row[0];

                    $count++;
                    if ($count < $columns->rowCount()) {
                        $result .= ', ';
                    }
                }
                unset($columns);

                $result .= ') VALUES';

                $file->fwrite($result);

                $result = '';
            }

            $count = 0;
            while ($row = $sql->fetch(PDO::FETCH_NUM)) {
                $result .= "\n\t(";

                for ($i = 0; $i < $column; $i++) {
                    if (isset($row[$i]) && in_array($type[$table][$i], $types) && !empty($row[$i])) {
                        $result .= $row[$i];
                    } elseif (isset($row[$i])) {
                        $result .= $this->connection->quote($row[$i]);
                    } else {
                        $result .= 'NULL';
                    }

                    if ($i < $column - 1) {
                        $result .= ',';
                    }
                }

                $count++;
                $result .= $count < $rows ? '),' : ');';

                $file->fwrite($result);

                $result = '';
            }

            $file->fwrite("\n\n\n\n");
        }

        unset($file, $result, $tables, $sql, $column, $rows, $row);

        return $this;
    }

    /**
     * It gets the name of the table from the database
     *
     * @return array
     * @access private
     * @final
     */
    private function getTables(): array
    {
        $tables = [];

        $sql = $this->connection->query('SHOW TABLES');
        while ($row = $sql->fetch(PDO::FETCH_NUM)) {
            array_push($tables, $row[0]);
        }

        return $tables;
    }

    /**
     * Creates an archive on a specified format.
     *
     * @return PharData|ZipArchive
     * @throws RuntimeException
     * @access private
     * @final
     */
    private function getArchive()
    {
        $archive = null;

        try {
            switch ($this->type) {
                case self::TAR:
                    $archive = new PharData(sprintf('%s%s.tar', $this->realTemporaryDirectory, $this->prefix));
                    break;
                case self::ZIP:
                    $archive = new ZipArchive();
                    $archive->open(sprintf('%s%s.zip', $this->realTemporaryDirectory, $this->prefix),
                        ZipArchive::CREATE);
                    break;
                case self::RAR:
                    $archive = new RarArchiver(sprintf('%s%s.rar', $this->realTemporaryDirectory, $this->prefix),
                        RarArchiver::CREATE);
                    break;
            }
        } catch (Exception $e) {
            throw new RuntimeException(sprintf('Failed to create the archive: %s', $e->getMessage()));
        }

        return $archive;
    }

    /**
     * Adding files to the archive.
     *
     * @param PharData|ZipArchive $archive
     *
     * @return PharData|ZipArchive
     * @access private
     * @final
     */
    private function addFiles($archive)
    {
        $flags = FilesystemIterator::SKIP_DOTS
            | FilesystemIterator::CURRENT_AS_PATHNAME
            | FilesystemIterator::KEY_AS_PATHNAME
            | FilesystemIterator::FOLLOW_SYMLINKS;

        $filesIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, $flags),
            RecursiveIteratorIterator::SELF_FIRST
        );

        switch ($this->type) {
            case self::RAR:
            case self::TAR:
            case self::ZIP:
                foreach ($filesIterator as $file) {
                    $realFile = realpath($file);
                    if (is_file($realFile)) {
                        $archive->addFile($realFile, trim(str_replace($this->path, '', $file), '/'));
                    }
                }
                break;
        }

        return $archive;
    }

    /**
     * Executes queries to the cloud
     *
     * @param string $url
     * @param array  $headers
     * @param string $method
     * @param string $file
     *
     * @return stdClass
     * @access private
     * @final
     */
    private function request(string $url, array $headers = [], string $method = '', string $file = null): \stdClass
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', $this->credentials));

        if (!empty($this->authtype)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, $this->authtype);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($method)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($file !== null) {
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, fopen($file, 'rb'));
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
        }

        $response   = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $result           = new stdClass();
        $result->response = $response;
        $result->code     = (int)$statusCode;

        return $result;
    }
}
