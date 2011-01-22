<?php
namespace dbpedia\util
{

class PhpUtil
{
    /**
     * @param $name name of variable to check, for error message
     * @throws \InvalidArgumentException if $dir is not a string pointing to an existing directory
     * @return absolute path for given string, using forward slashes, ending with a slash.
     */
    public static function assertDir( $var, $name )
    {
        self::assertString($var, $name);
        $dir = str_replace('\\', '/', realpath($var));
        if (! is_dir($dir)) throw new \InvalidArgumentException($name . ' must be an existing directory, but is ' . $var);
        if (! StringUtil::endsWith($dir, '/')) $dir .= '/';
        return $dir;
    }
    
    /**
     * @param $var variable to check
     * @param $name name of variable to check, for error message
     * @throws \InvalidArgumentException if $var is not a string
     */
    public static function assertString( $var, $name )
    {
        if (! is_string($var)) throw new \InvalidArgumentException($name . ' must be a string, but has type ' . self::typeNameOf($var));
    }
    
    /**
     * @param $var variable to check
     * @param $name name of variable to check, for error message
     * @throws \InvalidArgumentException if $var is not an integer
     */
    public static function assertInteger( $var, $name )
    {
        if (! is_integer($var)) throw new \InvalidArgumentException($name . ' must be an integer, but has type ' . self::typeNameOf($var));
    }
    
    /**
     * @param $var variable to check
     * @param $name name of variable to check, for error message
     * @throws \InvalidArgumentException if $var is not a boolean
     */
    public static function assertBoolean( $var, $name )
    {
        if (! is_bool($var)) throw new \InvalidArgumentException($name . ' must be a boolean, but has type ' . self::typeNameOf($var));
    }
    
    /**
     * @param $var variable to check
     * @param $name name of variable to check, for error message
     * @throws \InvalidArgumentException if $var is not an array
     */
    public static function assertArray( $var, $name )
    {
        if (! is_array($var)) throw new \InvalidArgumentException($name . ' must be an array, but has type ' . self::typeNameOf($var));
    }
    
    /**
     * @param $var variable to check
     * @param $type string containing fully qualified class name, with or without leading backslash. 
     * @param $name name of variable to check, for error message
     * @throws \InvalidArgumentException if $var is not of given type
     */
    public static function assertType( $var, $type, $name )
    {
        if (! ($var instanceof $type)) throw new \InvalidArgumentException($name . ' must be a ' . $type . ', but has type ' . self::typeNameOf($var));
    }
    
    /**
     * return type name of a primitive value, an array, an object, a resource or null.
     * @param $var may be a primitive value, an object, a resource, or null.
     * @return type name of given value: "boolean", "integer", "double" (not "float"), "string",
     * "array", class name, resource type name, "NULL", or "unknown type"
     */
    public static function typeNameOf( $var )
    {
        // Note: we could convert this to nested conditional expressions, but associativity 
        // seems to be strange (right to left?). Using 'if' feels safer.
        if (is_object($var)) return get_class($var);
        if (is_resource($var)) return get_resource_type($var);
        return gettype($var);
    }
}

}
