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
	import connection.model.LookUpCache;
	
	import mx.collections.ArrayCollection;
	import mx.rpc.events.FaultEvent;
	
	public class LookUpKeywordSearch implements ILookUp
	{
		
		private var host:String = "http://lookup.dbpedia.org/api/search.asmx/KeywordSearch";
		
		private var target:Object;
		
		
		
		private var currentInput:String = "";
		
		public function LookUpKeywordSearch(){
			
		}
		
		public function run(_input:String, target:Object, limit:int = 40):void {
			var httpService:SPARQLService = new SPARQLService(host);
			httpService.addEventListener(SPARQLResultEvent.SPARQL_RESULT, lookUp_Result);
			httpService.addEventListener(FaultEvent.FAULT, lookUp_Fault);
			
			httpService.url = this.host;
			httpService.method = "POST";
			
			currentInput = _input;
			
			this.target = target;
			
			var params:Object = new Object();
			params["MaxHits"] = limit;
			params["QueryClass"] = "http://dbpedia.org/ontology/Resource";
			params["QueryString"] = _input;
			
			var inputArrayCollection:ArrayCollection = new ArrayCollection();
			inputArrayCollection.addItem(_input);
			
			httpService.sources = inputArrayCollection;
			
			httpService.send(params);
			
		}
		
		public function lookUp_Result(e:SPARQLResultEvent):void {
			var lastSend:Date = LookUpCache.getInstance().getLastSend(target);
			var resultSend:Date = e.executenTime;
			
			if (lastSend == null) {
				lastSend = resultSend;
			}
			
			LookUpCache.getInstance().setLastSend(target, resultSend);
			
			var lastInput:String = e.sources.getItemAt(0).toString();
			
			trace(lastInput, currentInput);
			
			if (resultSend.time >= lastSend.time && lastInput && currentInput && lastInput == currentInput) {
			
				var results:ArrayCollection = new ArrayCollection();
				var result:XML = new XML(e.message.body);
				var ns:Namespace = new Namespace("http://lookup.dbpedia.org/");
				for each (var res:XML in result..ns::Result) {
					var labelStr:String = res.ns::Label;
					var uriStr:String = res.ns::URI;
					if (labelStr.toLowerCase().search(currentInput.toLowerCase()) >= 0){
						results.addItem( { label:labelStr, uris: new Array(uriStr) } );
					}
				}
				
				if (results.length == 0) {
					var empty:Object = new Object();
					empty.label = "No results found";
					results.addItem(empty);
				}
				
				target.dataProvider = results;
			}
		}
		
		public function lookUp_Fault(e:FaultEvent):void {
			trace("lookUp_Fault: " + e.message.toString());
		}
	}
	
}