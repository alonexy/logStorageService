<?php

declare(strict_types=1);
/**
 * This file is part of log_store.
 *
 * @author     alonexy@qq.com
 */

namespace App\Tool;

use Monolog\Handler\StreamHandler;

class TcpStreamHandler extends StreamHandler
{
    /** @var null|resource */
    protected $stream;

    protected $url;

    protected $filePermission;

    protected $useLocking;

    /** @var null|string */
    private $errorMessage;

    private $dirCreated;

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
        $this->dirCreated = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        if (! is_resource($this->stream)) {
            if ($this->url === null || $this->url === '') {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->createDir();
            $this->errorMessage = null;
            set_error_handler([$this, 'customErrorHandler']);
            $this->stream = fopen($this->url, 'a');
            if ($this->filePermission !== null) {
                @chmod($this->url, $this->filePermission);
            }
            restore_error_handler();
            if (! is_resource($this->stream)) {
                $this->stream = null;

                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $this->url));
            }
        }

        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            flock($this->stream, LOCK_EX);
        }

        $this->streamWrite($this->stream, $record);

        if ($this->useLocking) {
            flock($this->stream, LOCK_UN);
        }
    }

    /**
     * Write to stream.
     * @param resource $stream
     */
    protected function streamWrite($stream, array $record): void
    {
        $data = $record['formatted'];
        $type = pack('N', 1001);
        $length = pack('N', strlen($data));
        //length+type+body
        $packge = $length . $type . $data;
        socket_write($stream, $packge, strlen($packge));
    }
}
