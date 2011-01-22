package connection.model 
{
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;
	import flash.utils.Dictionary;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class LookUpCache implements IEventDispatcher
	{
		//*** Singleton **********************************************************
		private static var instance:LookUpCache;
		
		private var eventDispatcher:EventDispatcher;
		
		public function LookUpCache(singleton:SingletonEnforcer) 
		{
			eventDispatcher = new EventDispatcher();
		}
		
		public static function getInstance():LookUpCache{
			if (LookUpCache.instance == null){
				LookUpCache.instance = new LookUpCache(new SingletonEnforcer());
				
			}
			return LookUpCache.instance;
		}
		//************************************************************************
		
		private var _lastSend:Dictionary = new Dictionary();
		
		public function setLastSend(target:Object, lastSend:Date):void {
			_lastSend[target] = lastSend;
		}
		
		public function getLastSend(target:Object):Date {
			return _lastSend[target];
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