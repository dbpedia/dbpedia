<?php

abstract class AbstractResource {
    protected $wasInitialized = false;
    protected $id;

    public function __construct($id) {
        if (!empty($id)) {
            $this->id = $id;
        } else {
            throw new InvalidArgumentException('ID cannot be empty');
        }
    }

    public function __destruct() {
        $this->close();
    }

    public abstract function close();

    protected function log($lvl, $message) {
        Logger::logComponent("resource", get_class($this), $lvl, $message);
    }
}
?>
