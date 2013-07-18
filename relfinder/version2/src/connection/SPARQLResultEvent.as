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
	import mx.collections.ArrayCollection;
	import mx.messaging.messages.IMessage;
	import mx.rpc.AsyncToken;
	import mx.rpc.events.ResultEvent;

	public class SPARQLResultEvent extends ResultEvent
	{
		public static const SPARQL_RESULT:String = "SPARQLResult";
		
		private var _sources:ArrayCollection;
		
		private var _executenTime:Date;
		
		private var _parsingInformations:Object;
		
		public function SPARQLResultEvent(type:String, sources:ArrayCollection, executenTime:Date, parsingInformations:Object = null, bubbles:Boolean = false, cancelable:Boolean = true,
											result:Object = null, token:AsyncToken = null, message:IMessage = null)
		{
			super(type, bubbles, cancelable, result, token, message);
			_sources = sources;
			_executenTime = executenTime;
			_parsingInformations = parsingInformations;
		}
		
		public function get executenTime():Date {
			return _executenTime;
		}
		
		public function get sources():ArrayCollection {
			return _sources;
		}
		
		public function get parsingInformations():Object {
			return _parsingInformations;
		}
		
		public static function createEvent(result:Object = null, sources:ArrayCollection = null, executenTime:Date = null, parsingInformations:Object = null, token:AsyncToken = null, message:IMessage = null):SPARQLResultEvent
		{
			return new SPARQLResultEvent(SPARQLResultEvent.SPARQL_RESULT, sources, executenTime, parsingInformations, false, true, result, token, message);
		}
		
	}
	
}