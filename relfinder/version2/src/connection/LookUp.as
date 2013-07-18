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
	import mx.rpc.http.HTTPService;
	import mx.rpc.events.ResultEvent;
	import mx.rpc.events.FaultEvent;
	import mx.core.Application;
	import flash.system.Security;


	public class LookUp 
	{
		private var host:String;
		public function LookUp(_host:String) {
			Security.allowDomain("http://lookup.dbpedia.org");
			//http://lookup.dbpedia.org/api/search.asmx?op=PrefixSearch
			this.host = _host;
		}
		
		public function run(_input:String):void {
			var httpService:HTTPService = new HTTPService(this.host);
			
			httpService.addEventListener(ResultEvent.RESULT, lookUp_Result);
			httpService.addEventListener(FaultEvent.FAULT, lookUp_Fault);
			
			httpService.url = this.host;
			httpService.method = "GET";
			
			var params:Object = new Object();
			params["q"] = _input;
			httpService.send(params);
		}
		
		public function lookUp_Result(e:ResultEvent):void {
			//TODO: parse xml
			app().setAutoCompleteList(null);
		}
		
		public function lookUp_Fault(e:FaultEvent):void {
			trace("lookUp_Fault: " + e.message.toString());
		}
		
		private function app():Main {
			return Application.application as Main;
		}
		
	}
	
}