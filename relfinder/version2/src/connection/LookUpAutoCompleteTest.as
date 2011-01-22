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
	import mx.rpc.http.HTTPService;
	import mx.rpc.events.ResultEvent;
	import mx.rpc.events.FaultEvent;
	import mx.core.Application;
	
	public class LookUpAutoCompleteTest implements ILookUp
	{
		private var host:String = "http://lookup.dbpedia.org/api/autocompletetest.asmx/GetCompletionList";
		
		private var target:Object;
		
		public function run(_input:String, target:Object, limit:int = 20):void {
			
			this.target = target;
			
			var httpService:HTTPService = new HTTPService(this.host);
			
			httpService.addEventListener(ResultEvent.RESULT, lookUp_Result);
			httpService.addEventListener(FaultEvent.FAULT, lookUp_Fault);
			
			httpService.url = this.host;
			httpService.method = "GET";
			
			
			var params:Object = new Object();
			params["count"] = limit;
			params["prefixText"] = _input;
			
			trace("lookup (autocompletetest): " + _input);
			httpService.send(params);
		}
		
		public function lookUp_Result(e:ResultEvent):void {
			var results:ArrayCollection = new ArrayCollection();
			
			trace("LookUpAutoCompleteTest results:");
			
			var result:XML = new XML(e.message.body);
			var ns:Namespace = new Namespace("http://tempuri.org/");
			for each (var str:String in result..ns::string) {
				trace(str);
				results.addItem(str);
			}
			
			target.dataProvider = results;
			//app().setAutoCompleteList(results);
		}
		
		public function lookUp_Fault(e:FaultEvent):void {
			trace("lookUp_Fault: " + e.message.toString());
		}
		
		private function app():Main {
			return Application.application as Main;
		}
		
	}
	
}