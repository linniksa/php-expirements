<?php

use Evenement\EventEmitterTrait;

/**
 * Class CSVReader
 */
class CSVReader implements \Iterator
{
    use EventEmitterTrait;

    const ROW_SIZE = 4096;

    private $filePointer;

    private $delimiter;
    private $enclosure;
    private $escape;

    private $row;
    private $index;

    private $keys;

    public function __construct($file)
    {
        $this->delimiter   = ',';
        $this->enclosure   = '"';
        $this->escape      = '\\';

        if (!is_resource($file)) {
            $file = fopen($file, 'r');
        }

        $this->filePointer = $file;

        if (!$this->filePointer) {
            throw new \Exception('Could not open csv file');
        }
    }

    public final function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if (is_resource($this->filePointer)) {
            fclose($this->filePointer);
            $this->emit('close');
        };
    }

    public function useFistLineAsKeys()
    {
        if ($this->index > 0) {
            throw new \LogicException('First line is skipped');
        }

        $this->rewind();
        $this->keys = array_values($this->row);
        $this->next();

        return $this;
    }

    public function rewind()
    {
        if (null === $this->index) {
            $this->next();
            $this->index = 0;
        }
    }

    public function current()
    {
        return $this->row;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        ++$this->index;
        $row = fgetcsv($this->filePointer, self::ROW_SIZE, $this->delimiter, $this->enclosure, $this->escape);

        if ($row && $this->keys) {
            $row = array_combine($this->keys, $row);
        }

        $this->row = $row;
    }

    public function valid()
    {
        return (bool) $this->row;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * @param string $escape
     */
    public function setEscape($escape)
    {
        $this->escape = $escape;
    }

    /**
     * @return string
     */
    public function getEscape()
    {
        return $this->escape;
    }

}
