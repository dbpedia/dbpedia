package connection.model 
{
	import connection.config.CIAWorldFactBookConfig;
	import connection.config.Config;
	import connection.config.DBLPConfig;
	import connection.config.DBpediaConfig;
	import connection.config.IConfig;
	import connection.config.LinkedMDBConfig;
	import connection.config.LODConfig;
	
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;
	
	import mx.collections.ArrayCollection;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class ConnectionModel implements IEventDispatcher
	{
		//*** Singleton **********************************************************
		private static var instance:ConnectionModel;
		
		private var eventDispatcher:EventDispatcher;
		
		public function ConnectionModel(singleton:SingletonEnforcer) 
		{
			eventDispatcher = new EventDispatcher();
		}
		
		public static function getInstance():ConnectionModel{
			if (ConnectionModel.instance == null){
				ConnectionModel.instance = new ConnectionModel(new SingletonEnforcer());
				
			}
			return ConnectionModel.instance;
		}
		//************************************************************************	
		
		private var _proxy:String = "";
		
		[Bindable(event="proxyChange")]
		public function get proxy():String {
			return _proxy;
		}
		
		public function set proxy(value:String):void {
			_proxy = value;
			dispatchEvent(new Event("proxyChange"));
		}
		
		private var _defaultProxy:String = "";
		
		[Bindable(event="defaultProxyChange")]
		public function get defaultProxy():String {
			return _defaultProxy;
		}
		
		public function set defaultProxy(value:String):void {
			_defaultProxy = value;
			dispatchEvent(new Event("defaultProxyChange"));
		}
		
		public var sparqlConfigs:ArrayCollection = new ArrayCollection();
		
		public function getSPARQLByName(name:String):IConfig {
			for each (var config:IConfig in sparqlConfigs) {
				if (config.name.toLowerCase() == name.toLowerCase()) {
					return config;
				}
			}
			return null;
		}
		
		public function getSPARQLByEndpointURI(endpointURI:String):IConfig {
			for each (var config:IConfig in sparqlConfigs) {
				if (config.endpointURI.toLowerCase() == endpointURI.toLowerCase()) {
					return config;
				}
			}
			return null;
		}
		
		private var _sparqlConfig:IConfig = new DBpediaConfig(); // Standard
		//private var _sparqlConfig:IConfig = new LODConfig(); // funtioniert
		//private var _sparqlConfig:IConfig = new LinkedMDBConfig(); // funktioniert noch nicht (Label)
		//private var _sparqlConfig:IConfig = new CIAWorldFactBookConfig(); // funktioniert noch nicht (Server)
		//private var _sparqlConfig:IConfig = new DBLPConfig(); // funktioniert noch nicht (Server)
		
		[Bindable(event = "sparqlConfigChanged")]
		public function get sparqlConfig():IConfig {
			if (_sparqlConfig == null) {
				_sparqlConfig = new Config();
			}
			return _sparqlConfig;
		}
		
		public function set sparqlConfig(sparqlConfig:IConfig):void {
			_sparqlConfig = sparqlConfig;
			dispatchEvent(new Event("sparqlConfigChanged"));
		}
		
		private var _lastClear:Date = new Date();
		
		public function get lastClear():Date {
			return _lastClear;
		}
		
		public function set lastClear(date:Date):void {
			_lastClear = date;
		}
		
		//*** IEventDispatcher ***************************************************
		public function addEventListener(type:String, listener:Function,
			useCapture:Boolean = false, priority:int = 0, weakRef:Boolean = false):void{
			eventDispatcher.addEventListener(type, listener, useCapture, priority, weakRef);
		}
		
		public function dispatchEvent(event:Event):Boolean{
			return eventDispatcher.dispatchEvent(event);
		}
		
		public function hasEventListener(type:String):Boolean{
			return eventDispatcher.hasEventListener(type);
		}
		
		public function removeEventListener(type:String, listener:Function,
			useCapture:Boolean = false):void{
			eventDispatcher.removeEventListener(type, listener, useCapture);
		}
		
		public function willTrigger(type:String):Boolean {
			return eventDispatcher.willTrigger(type);
		}
		//************************************************************************
	}
}
class SingletonEnforcer{}