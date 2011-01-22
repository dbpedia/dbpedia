package connection.config 
{
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class CIAWorldFactBookConfig extends Config
	{
		override public function get endpointURI():String {
			return "http://www4.wiwiss.fu-berlin.de/factbook";
		}
		
		override public function get defaultGraphURI():String {
			return "";
		}
		
		override public function get isVirtuoso():Boolean {
			return false;
		}
		
		override public function get abbreviation():String {
			return "ciawfb";
		}
		
		override public function get name():String {
			return "CIA World Fact Book"; 
		}
	}
	
}