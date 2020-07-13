<?php
/**
 * Created by PhpStorm.
 * User: alonexy
 * Date: 20/7/9
 * Time: 15:43
 */

namespace App\Tool;


use Monolog\Handler\StreamHandler;

class PipeStreamHandler extends StreamHandler
{
    /** @var resource|null */
    protected $stream;
    protected $url;
    /** @var string|null */
    private $errorMessage;
    protected $filePermission;
    protected $useLocking;
    private $dirCreated;

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream     = null;
        $this->dirCreated = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        if (!is_resource($this->stream)) {
            if (null === $this->url || '' === $this->url) {
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
            if (!is_resource($this->stream)) {
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
     * Write to stream
     * @param resource $stream
     * @param array $record
     */
    protected function streamWrite($stream, array $record): void
    {
        $data   = mb_convert_encoding($record['formatted'], "UTF-8");
        $type   = pack('N', (int)888);
        $length = pack('N', (int)strlen($data));
        //type+length++body
        $packge = $type . $length . $data;
        fwrite($stream, $packge, strlen($packge));
    }

}