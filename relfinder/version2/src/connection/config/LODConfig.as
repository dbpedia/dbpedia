package connection.config 
{
	import mx.collections.ArrayCollection;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class LODConfig extends DBpediaConfig
	{
		override public function get endpointURI():String {
			return "http://lod.openlinksw.com/sparql";
		}
		
		override public function get defaultGraphURI():String {
			return "";
		}
		
		override public function get isVirtuoso():Boolean {
			return true;
		}
		
		override public function get description():String {
			return "lod";
		}
		
		override public function get name():String {
			return "Linking Open Data (LOD)"; 
		}
		
		override public function get ignoredProperties():ArrayCollection {
			var ignoredProperties:ArrayCollection = super.ignoredProperties;
			ignoredProperties.addItem("http://dbpedia.org/property/wikilink");
			return ignoredProperties;
		}
		
	}
	
}