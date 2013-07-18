/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package global 
{
	
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class Languages implements IEventDispatcher
	{
		//*** Singleton **********************************************************
		private static var instance:Languages;
		
		private var eventDispatcher:EventDispatcher;
		
		public function Languages(singleton:SingletonEnforcer) 
		{
			eventDispatcher = new EventDispatcher();
			langs.push("en");
		}
		
		public static function getInstance():Languages{
			if (Languages.instance == null){
				Languages.instance = new Languages(new SingletonEnforcer());
				
			}
			return Languages.instance;
		}
		//************************************************************************
		
		private var langs:Array = new Array();
		
		public function addLanguageCode(code:String):void {
			code = code.toLowerCase();
			
			if (code != null && code != "" && !containsLanguageCode(code)) {
				langs.push(new String(code));
				langs.sort();
				dispatchEvent(new Event("eventLangsChanged"));
			}
		}
		
		public function containsLanguageCode(code:String):Boolean {
			code = code.toLowerCase();
			for each (var lang:String in langs) {
				if (code == lang.toLowerCase()) {
					return true;
				}
			}
			
			return false;
		}
		
		[Bindable(event = "eventLangsChanged")]
		public function get asDataProvider():Array {
			return langs;
		}
		
		private var _selectedLanguage:String = "en";
		
		[Bindable(event = "eventSelectedLanguageChanged")]
		public function get selectedLanguage():String {
			return _selectedLanguage;
		}
		
		public function set selectedLanguage(code:String):void {
			if (containsLanguageCode(code)) {
				_selectedLanguage = code.toLowerCase();
				dispatchEvent(new Event("eventSelectedLanguageChanged"));
			}
		}
		
		[Bindable(event = "eventLangsChanged")]
		public function get selectedLanguageIndex():int{
			for (var i:int = 0; i < langs.length; i++){
				if (langs[i].toString() == _selectedLanguage.toString()){
					return i;
				}
			}
			return -1;
		}
		
		public function clear():void {
			langs = new Array();
			langs.push("en");
			
			_selectedLanguage = "en";
			dispatchEvent(new Event("eventLangsChanged"));
			dispatchEvent(new Event("eventSelectedLanguageChanged"));
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