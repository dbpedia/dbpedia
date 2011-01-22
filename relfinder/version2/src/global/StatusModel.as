package global 
{
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class StatusModel implements IEventDispatcher
	{
		//*** Singleton **********************************************************
		private static var instance:StatusModel;
		
		private var eventDispatcher:EventDispatcher;
		
		public function StatusModel(singleton:SingletonEnforcer) 
		{
			eventDispatcher = new EventDispatcher();
		}
		
		public static function getInstance():StatusModel{
			if (StatusModel.instance == null){
				StatusModel.instance = new StatusModel(new SingletonEnforcer());
				
			}
			return StatusModel.instance;
		}
		//************************************************************************	
		
		
		private var _message:String = "Idle";
		
		[Bindable(event = "eventMessageChanged")]
		public function get message():String {
			
			if ((_searchCount == 0) && (_queueIsEmpty)) {
				return "idle";
			}
			
			if (_searchCount == _errorCount) {
				
				_message = "Database not available. Check network connection.";
				
			}else {
				
				if ((_searchCount > _foundCount) || (!_queueIsEmpty)) {
					_message = "Searching for relations";
				}else {
					
					if (_noRelationFound) {
						_message = "No Relation found";
					}else {
						_message = "Idle";
					}
				}
				
			}
			
			if (_errorsOccured) {
				_message += " / some Errors occured";
			}
			
			return _message;
		}
		
		public function set message(message:String):void {
			_message = message;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		private var _searchCount:int = 0;
		private var _foundCount:int = 0;
		private var _errorCount:int = 0;
		
		private var _queueIsEmpty:Boolean = false;
		
		public function addSearch():void {
			_searchCount++;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		public function addFound():void {
			_foundCount++;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		public function addError():void {
			_errorCount++;
			_errorsOccured = true;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		public function set queueIsEmpty(b:Boolean):void {
			if (_queueIsEmpty != b) {
				_queueIsEmpty = b;
				dispatchEvent(new Event("eventMessageChanged"));
			}
		}
		
		private var _noRelationFound:Boolean = false;
		
		public function resetNoRelationFound():void {
			_noRelationFound = true;
			_errorsOccured = false;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		public function addWasRelationFound(wasRelationFound:Boolean):void {
			_noRelationFound = _noRelationFound && !wasRelationFound;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		private var _errorsOccured:Boolean = false;
		
		public function clear():void {
			_searchCount = 0;
			_foundCount = 0;
			_errorCount = 0;
			_message = "Idle";
			_noRelationFound = false;
			_errorsOccured = false;
			dispatchEvent(new Event("eventMessageChanged"));
		}
		
		[Bindable(event = "eventMessageChanged")]
		public function get isSearching():Boolean{
			if ((_searchCount == 0) && (_queueIsEmpty)) {
				return false;
			}
			if (_searchCount == _errorCount) {
				
				return false;
				
			}else {
				
				if ((_searchCount > _foundCount) || (!_queueIsEmpty)) {
					return true;
				}else {
					return false;
				}
			}
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