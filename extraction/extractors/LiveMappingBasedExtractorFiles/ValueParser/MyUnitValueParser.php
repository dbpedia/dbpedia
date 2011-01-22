<?php


class MyUnitValueParser
    implements IValueParser
{
    private $language;
    private $unit;

    private static $unitToQuantity = array(
        "km"      => "Length",              // kilometers
        "m³/s"    => "FlowRate",            // Volume/Time (m^3/s)
        "cuft"    => "Volume",             // cubic foot
        "cuft/s"  => "FlowRate",            // cubic foot per time
        "km2"     => "Area",                // square kilometers
        "sqmi"    => "Area",                // square miles
        "K"       => "Temperature",         // degree kelvin
        "C"       => "Temperature",         // degree celsius
        "F"       => "Temperature",         // degree fahrenheit
        "m"       => "Length",              // meters
        "mi"      => "Length",              // miles
    	"ft"      => "Length",              // foot
        "in"      => "Length",              // inch
        "PD/sqkm" => "PopulationDensity",  // pop-density/square miles
        "PD/sqmi" => "PopulationDensity",  // pop-density/square kilometers
        "lb"      => "Mass",                // pound
        "kg"      => "Mass",                // kilogram
        "st"      => "Mass",              //? What weight unit is that?
        "minute"  => "Time",                // minutes
    );

    public static function getUnitToQuantityMap()
    {
    	return self::$unitToQuantity;
    }

    
    private function getQuantityByUnit($unit)
    {
        if(!array_key_exists($unit, self::$unitToQuantity))
            return "";

        return self::$unitToQuantity[$unit];
    }

    public function __construct($language, $unit)
    {
        $this->language = $language;
        $this->unit     = $unit;
    }

    
    /**
     * Returns: (0 => value, 1 => unit (datatype))
     *
     * @param unknown_type $value
     * @return unknown
     */
    public function parse($value)
    {
        $quantity = $this->getQuantityByUnit($this->unit);

        /*
        echo "$quantity\n";
        echo $this->unit."\n";
        echo $GLOBALS[$quantity][$this->unit]."\n";
		*/

		// 	$GLOBALS['Length']['m']
		/*
		echo "Calling with settings:
					PAGEID => 'http://dummy.org',
					PROPERTYNAME => 'http://dummy.org',
					UNITTYPE=>{$quantity},
					UNITEXACTTYPE=>{$this->unit},
					TARGETUNIT=>{$GLOBALS[$quantity][$this->unit]},
					IGNOREUNIT => false)
		";*/
        $er = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE) ;
        try {
		$result =
			UnitValueParser::parseValue(
				$value,
				$this->language,
				array(
					PAGEID => "http://dummy.org",
					PROPERTYNAME => "http://dummy.org",
					UNITTYPE=>$quantity,
					UNITEXACTTYPE=>$this->unit,
					TARGETUNIT=>$GLOBALS[$quantity][$this->unit],
					IGNOREUNIT => false));
        }
        catch(Exception $e) {
        }
        
        error_reporting($er);
        
        return $result;
	/*
        return UnitValueParser::parseValue(
            $value,
            $this->language,
            array($quantity, $this->unit, "dummy key"));

            // Note on Dummy key:
            // This argument is used solely for logging purposes
	*/
    }

}
