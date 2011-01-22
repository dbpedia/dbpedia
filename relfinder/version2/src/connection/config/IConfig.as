package connection.config 
{
	import connection.ILookUp;
	
	import mx.collections.ArrayCollection;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public interface IConfig 
	{
		[Bindable(event="endpointURIChange")]
		function get endpointURI():String;
		
		function set endpointURI(value:String):void;
		
		[Bindable(event="defaultGraphURIChange")]
		function get defaultGraphURI():String;
		
		function set defaultGraphURI(value:String):void;
		
		[Bindable(event="isVirtuosoChange")]
		function get isVirtuoso():Boolean;
		
		function set isVirtuoso(value:Boolean):void;
		
		[Bindable(event="nameChange")]
		function get name():String;
		
		function set name(value:String):void;
		
		[Bindable(event="descriptionChange")]
		function get description():String;
		
		function set description(value:String):void;
		
		[Bindable(event="autocompleteURIsChange")]
		function get autocompleteURIs():ArrayCollection;
		
		function set autocompleteURIs(value:ArrayCollection):void;
		
		[Bindable(event="ignoredPropertiesChange")]
		function get ignoredProperties():ArrayCollection;
		
		function set ignoredProperties(value:ArrayCollection):void;
		
		[Bindable(event="lookUpChange")]
		function get lookUp():ILookUp;
		
		function set lookUp(value:ILookUp):void;
		
		[Bindable(event="useProxyChange")]
		function get useProxy():Boolean;
		
		function set useProxy(value:Boolean):void;
		
		function toURLParameters():String;
		
		function equals(value:IConfig):Boolean
	}
	
}