/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

package connection 
{
	import flash.events.Event;
	import mx.collections.ArrayCollection;
	import mx.core.mx_internal;
	import mx.messaging.events.MessageEvent;
	import mx.rpc.AsyncToken;
	import mx.rpc.http.HTTPService;
	
	use namespace mx_internal;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class SPARQLService extends HTTPService
	{
		private var _sources:ArrayCollection = null;
		
		private var _executenTime:Date;
		
		private var _parsingInformations:Object = null;
		
		public function SPARQLService(rootURL:String = null, destination:String = null) 
		{
			super(rootURL, destination);
		}
		
		public function get sources():ArrayCollection {
			return _sources;
		}
		
		public function set sources(sources:ArrayCollection):void {
			_sources = sources;
		}
		
		public function get parsingInformations():Object {
			return _parsingInformations;
		}
		
		public function set parsingInformations(value:Object):void {
			_parsingInformations = value;
		}
		
		override public function send(parameters:Object = null):AsyncToken 
		{
			_executenTime = new Date();
			return super.send(parameters);
		}
		
		public function get executenTime():Date {
			if (_executenTime == null) {
				_executenTime = new Date();
			}
			return _executenTime;
		}
		
		/**
		 *  Take the MessageAckEvent and take the result, store it, and broadcast out
		 *  appropriately.
		 *
		 *  @private
		 */
		mx_internal override function resultHandler(event:MessageEvent):void
		{
			var token:AsyncToken = preHandle(event);
			
			//if the handler didn't give us something just bail
			if (token == null)
				return;
				
			if (processResult(event.message, token))
			{
				dispatchEvent(new Event(BINDING_RESULT));
				var resultEvent:SPARQLResultEvent = SPARQLResultEvent.createEvent(_result, _sources, executenTime, _parsingInformations, token, event.message);
				resultEvent.headers = _responseHeaders;
				dispatchRpcEvent(resultEvent);
			}
			//no else, we assume process would have dispatched the faults if necessary
		}
	}
	
}