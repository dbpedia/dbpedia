package connection.config
{
	import mx.collections.ArrayCollection;
	import connection.ILookUp;


	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class DBTuneConfig extends Config
	{
		
		override public function get endpointURI():String {
			return "http://api.talis.com/stores/bbc-backstage/services"
			return "http://dbtune.org/bbc/playcount/sparql";
		}
		
		override public function get defaultGraphURI():String {
			return "";
		}
		
		override public function get isVirtuoso():Boolean {
			return false;
		}
		
		override public function get abbreviation():String {
			return "dbtune";
		}
		
		override public function get name():String {
			return "DBTune"; 
		}
		
	}
}