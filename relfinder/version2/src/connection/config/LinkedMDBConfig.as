package connection.config 
{
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class LinkedMDBConfig extends Config
	{
		override public function get endpointURI():String {
			return "http://data.linkedmdb.org";
		}
		
		override public function get defaultGraphURI():String {
			return "";
		}
		
		override public function get isVirtuoso():Boolean {
			return false;
		}
		
		override public function get abbreviation():String {
			return "lmdb";
		}
		
		override public function get name():String {
			return "Linked Movie Data Base"; 
		}
	}
	
}