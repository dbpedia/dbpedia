<?php
namespace dbpedia\core
{
class DBpediaLogger
{
    public static function getLogger( $name )
    {
        return \Logger::getLogger(str_replace("\\", '.', $name));
    }
}
}
