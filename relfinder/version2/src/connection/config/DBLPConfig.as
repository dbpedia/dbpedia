package connection.config 
{
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class DBLPConfig extends Config
	{
		override public function get endpointURI():String {
			return "http://www4.wiwiss.fu-berlin.de/dblp";
		}
		
		override public function get defaultGraphURI():String {
			return "";
		}
		
		override public function get isVirtuoso():Boolean {
			return false;
		}
		
		override public function get abbreviation():String {
			return "dblp";
		}
		
		override public function get name():String {
			return "DBLP Bibliography Database (Berlin)"; 
		}
	}
	
}