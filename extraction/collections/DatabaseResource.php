<?php

abstract class DatabaseResource extends AbstractResource {
    protected $connection;

    public static function getDatabase($requestingClass, $type) {
        if ($type == 'mysql') {
            // Assuming there is a logger class with a static method log()
            Logger::log('debug', $requestingClass. ' requested mysql database ');
            return new MySQLDatabaseResource($requestingClass.'::'.$type);
        } else {
            // Handle unsupported database type
            throw new Exception("Unsupported database type: $type");
        }
    }

    public abstract function query($query);
}
?>
