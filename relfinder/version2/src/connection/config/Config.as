package connection.config 
{
	import com.dynamicflash.util.Base64;
	import connection.ILookUp;
	import connection.LookUpSPARQL;
	import flash.events.Event;
	
	import flash.events.EventDispatcher;
	
	import mx.collections.ArrayCollection;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class Config extends EventDispatcher implements IConfig
	{
		
		private var _endpointURI:String;
		
		private var _defaultGraphURI:String;
		
		private var _isVirtuoso:Boolean;
		
		private var _name:String;
		
		private var _description:String;
		
		private var _autocompleteURIs:ArrayCollection;
		
		private var _ignoredProperties:ArrayCollection;
		
		private var _lookUp:ILookUp;
		
		private var _useProxy:Boolean = true;
		
		public function Config(name:String = "", description:String = "",
					endpointURI:String = "", defaultGraphURI:String = "", isVirtuoso:Boolean = false,
					ignoredProperties:ArrayCollection = null, useProxy:Boolean = true,
					autocompleteURIs:ArrayCollection = null,
					lookUp:ILookUp = null) {
			
			this.name = (name == null || name == "") ? "New Config" : name;
			this.description = description;
			this.endpointURI = endpointURI;
			this.defaultGraphURI = defaultGraphURI;
			this.isVirtuoso = isVirtuoso;
			this.ignoredProperties = ignoredProperties;
			this.useProxy = useProxy;
			
			this.lookUp = lookUp;
		}
		
		[Bindable(event="endpointURIChange")]
		public function get endpointURI():String {
			return _endpointURI;
		}
		
		public function set endpointURI(value:String):void {
			_endpointURI = value;
			dispatchEvent(new Event("endpointURIChange"));
		}
		
		[Bindable(event="defaultGraphURIChange")]
		public function get defaultGraphURI():String {
			return _defaultGraphURI;
		}
		
		public function set defaultGraphURI(value:String):void {
			_defaultGraphURI = value;
			dispatchEvent(new Event("defaultGraphURIChange"));
		}
		
		[Bindable(event="isVirtuosoChange")]
		public function get isVirtuoso():Boolean {
			return _isVirtuoso;
		}
		
		public function set isVirtuoso(value:Boolean):void {
			_isVirtuoso = value;
			dispatchEvent(new Event("isVirtuosoChange"));
		}
		
		[Bindable(event="nameChange")]
		public function get name():String {
			return _name;
		}
		
		public function set name(value:String):void {
			_name = value;
			dispatchEvent(new Event("nameChange"));
		}
		
		[Bindable(event="descriptionChange")]
		public function get description():String {
			return _description;
		}
		
		public function set description(value:String):void {
			_description = value;
			dispatchEvent(new Event("descriptionChange"));
		}
		
		[Bindable(event="ignoredPropertiesChange")]
		public function get ignoredProperties():ArrayCollection {
			return _ignoredProperties;
		}
		
		public function set ignoredProperties(value:ArrayCollection):void {
			_ignoredProperties = value;
			dispatchEvent(new Event("ignoredPropertiesChange"));
		}
		
		[Bindable(event="autocompleteURIsChange")]
		public function get autocompleteURIs():ArrayCollection {
			return _autocompleteURIs;
		}
		
		public function set autocompleteURIs(value:ArrayCollection):void {
			_autocompleteURIs = value;
			dispatchEvent(new Event("autocompleteURIsChange"));
		}
		
		[Bindable(event="lookUpChange")]
		public function get lookUp():ILookUp{
			if (_lookUp == null){
				_lookUp = new LookUpSPARQL();
			}
			return _lookUp;
		}
		
		public function set lookUp(value:ILookUp):void {
			_lookUp = value;
			dispatchEvent(new Event("lookUpChange"));
		}
		
		[Bindable(event="useProxyChange")]
		public function get useProxy():Boolean {
			return _useProxy;
		}
		
		public function set useProxy(value:Boolean):void {
			_useProxy = value;
			dispatchEvent(new Event("useProxyChange"));
		}
		
		public function toURLParameters():String {
			var data:String = "";
			if (name != null && name != "") {
				data += "&name=" + Base64.encode(name);
			}
			if (description != null && description != "") {
				data += "&description=" + Base64.encode(description);
			}
			if (endpointURI != null && endpointURI != "") {
				data += "&endpointURI=" + Base64.encode(endpointURI);
			}
			if (defaultGraphURI != null && defaultGraphURI != "") {
				data += "&defaultGraphURI=" + Base64.encode(defaultGraphURI);
			}
			data += "&isVirtuoso=" + Base64.encode(isVirtuoso.toString()) +
				"&useProxy=" + Base64.encode(useProxy.toString());

			if (autocompleteURIs != null && autocompleteURIs.length > 0) {
				
				var acuri:String = "";
				
				for (var i:int = 0; i < autocompleteURIs.length; i++) {
					acuri += autocompleteURIs.getItemAt(i);
					if (i < autocompleteURIs.length - 1) {
						acuri += ",";
					}
				}
				data += "&autocompleteURIs=" + Base64.encode(acuri);
			}
			
			if (ignoredProperties != null && ignoredProperties.length > 0) {
				
				var ipuri:String = "";
				
				for (var j:int = 0; j < ignoredProperties.length; j++) {
					ipuri += ignoredProperties.getItemAt(j);
					if (j < ignoredProperties.length - 1) {
						ipuri += ",";
					}
				}
				data += "&ignoredProperties=" + Base64.encode(ipuri);
			}
			
			return data;
		}
		
		public function equals(config:IConfig):Boolean {
			return (name == config.name) && (endpointURI == config.endpointURI) &&
						(defaultGraphURI == config.defaultGraphURI) && (isVirtuoso == config.isVirtuoso) &&
						(useProxy == config.useProxy) && arrayCollectionEquals(autocompleteURIs, config.autocompleteURIs) &&
						arrayCollectionEquals(ignoredProperties, config.ignoredProperties);
		}
		
		private function arrayCollectionEquals(ac1:ArrayCollection, ac2:ArrayCollection):Boolean {
			if (ac1.length != ac2.length) {
				return false;
			}
			
			var a1:Array = ac1.toArray();
			var a2:Array = ac2.toArray();
			
			a1.sort();
			a2.sort();
			
			for (var i:int = 0; i < a1.length; i++) {
				if ((a1[i] as String) != (a2[i] as String)) {
					return false;
				}
			}
			
			return true;
		}
		
		override public function toString():String {
			return "Name: " + name + "\n" +
					"Description: " + description  + "\n" +
					"EndpointURI: " + endpointURI  + "\n" +
					"DefaultGraphURI: " + defaultGraphURI  + "\n" +
					"IsVirtuoso: " + isVirtuoso + "\n" +
					"UseProxy: " + useProxy + "\n" +
					"AutocompleteURIs: " + ((autocompleteURIs == null) ? "null" : autocompleteURIs.toArray() + " #" + autocompleteURIs.length) + "\n" +
					"IgnoredProperties: " + ((ignoredProperties == null) ? "null" : ignoredProperties.toArray() + " #" + ignoredProperties.length);
		}
	}
	
}