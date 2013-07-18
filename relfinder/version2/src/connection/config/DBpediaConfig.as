package connection.config 
{
	import connection.ILookUp;
	import connection.LookUpKeywordSearch;
	import connection.LookUpSPARQL;
	
	import mx.collections.ArrayCollection;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class DBpediaConfig extends Config
	{
		override public function get endpointURI():String {
			return "http://dbpedia.org/sparql";
		}
		
		override public function get defaultGraphURI():String {
			return "http://dbpedia.org";
		}
		
		override public function get isVirtuoso():Boolean {
			return true;
		}
		
		override public function get description():String {
			return "dbp";
		}
		
		override public function get name():String {
			return "DBpedia"; 
		}
		
		override public function get ignoredProperties():ArrayCollection {
			var ignoredProperties:ArrayCollection = new ArrayCollection();
			ignoredProperties.addItem("http://www.w3.org/1999/02/22-rdf-syntax-ns#type");
			ignoredProperties.addItem("http://www.w3.org/2004/02/skos/core#subject");
			ignoredProperties.addItem("http://dbpedia.org/property/wikiPageUsesTemplate");
			ignoredProperties.addItem("http://dbpedia.org/property/wordnet_type");
			return ignoredProperties;
		}
		
		//protected var _lookUp:ILookUp = new LookUpKeywordSearch();
		protected var _lookUp:ILookUp = new LookUpSPARQL();
		
		override public function get lookUp():ILookUp{
			if (_lookUp == null){
				_lookUp = new LookUpSPARQL();
			}
			return _lookUp;
		}
		
		override public function set lookUp(lookUp:ILookUp):void{
			_lookUp = lookUp;
		}
	}
}
	