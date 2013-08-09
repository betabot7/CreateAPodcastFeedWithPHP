<?php
class SimpleFileConfiguration 
{
    const DATA_FILE = 'configuration.txt';
    public $dataFile;

    public function __construct(Pimple $c) {
        $this->dataFile = $c['config']['path.data'] . $this::DATA_FILE;
    }
 
    public function load() {
        $contents = file_get_contents($this->dataFile);
        return unserialize($contents);
    }

    public function save($configuration) {
        $contents = serialize($configuration);
        file_put_contents($this->dataFile, $contents);
    }
}
