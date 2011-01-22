/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package connection {
	import connection.config.DBpediaConfig;
	import connection.config.IConfig;
	import connection.model.ConnectionModel;
	import flash.events.EventDispatcher;
	import flash.utils.Dictionary;
	import global.StatusModel;
	import mx.collections.ArrayCollection;
	import mx.controls.TextArea;
	import mx.rpc.events.ResultEvent;
	import mx.rpc.events.FaultEvent;
	import mx.core.Application;
	import flash.system.Security;
	import mx.controls.Alert;

	
	public class SPARQLConnection extends EventDispatcher {
		private var host:String;
		public var basicGraph:String;
		public var resultFormat:String = "XML";
		public var prefixes:String = "";
		
		//the almighty lod cloud as SPARQL endpoint
		//private var endpointURI:String = "http://lod.openlinksw.com/sparql";
		
		//our own server, might be faster for developing
		//private var endpointURI:String = "http://139.18.2.37:8890/sparql";
		
		//the standard, including community traffic
		//private var endpointURI:String = "http://dbpedia.org/sparql";
		//private var defaultGraphURI:String = "http://dbpedia.org";
		
		
		private var contentType:String = "application/sparql-results+json";
		
		public function get config():IConfig {
			return ConnectionModel.getInstance().sparqlConfig;
		}
		
		public function SPARQLConnection(_host:String = "", _basicGraph:String = "") {
			Security.allowDomain("http://dbpedia.org");
			this.host = _host;
			this.basicGraph = _basicGraph;
		}
		
		public function close():void{
			
		}
		
		public function findRelations(between:ArrayCollection, maxNum:int = 10, maxDist:int = 3, resultHandlerClass:ISPARQLResultParser = null):void {
			
			var onResult:Function = (resultHandlerClass != null) ? resultHandlerClass.handleSPARQLResultEvent : findRelations_Result;
			
			var ignoredObjects:ArrayCollection = new ArrayCollection();
			var ignoredProperties:ArrayCollection = ConnectionModel.getInstance().sparqlConfig.ignoredProperties;
			var avoidCycles:int = 2;
			
			var builder:SPARQLQueryBuilder = new SPARQLQueryBuilder();
			
			for (var i:int = 0; i < between.length; i++) {
				var obj1:String = between.getItemAt(i).toString();
				for (var j:int = i+1; j < between.length; j++) {
					var obj2:String = between.getItemAt(j).toString();
					
					var queries:ArrayCollection = builder.buildQueries(obj1, obj2, maxDist, maxNum, ignoredObjects, ignoredProperties, avoidCycles);
					
					StatusModel.getInstance().resetNoRelationFound();
					
					for each (var query:Array in queries) {
						StatusModel.getInstance().addSearch();
						
						executeSparqlQuery(new ArrayCollection(new Array(obj1, obj2)), query[0], onResult, resultFormat, true, null, query[1]);
						
					}
				}
			}
			
			/*var obj1:String = "";
			var obj2:String = "";
			
			//var index:int = 0;
			
			if (between.length >= 2) {
				//obj1 = decodeURI(between.getItemAt(0).toString());
				obj1 = between.getItemAt(0).toString();
				//obj2 = decodeURI(between.getItemAt(1).toString());
				obj2 = between.getItemAt(1).toString();
				
				var queries:ArrayCollection = builder.buildQueries(obj1, obj2, maxDist, maxNum, ignoredObjects, ignoredProperties, avoidCycles);
				
				StatusModel.getInstance().resetNoRelationFound();
				
				for each (var query:Array in queries) {
					StatusModel.getInstance().addSearch();
					
					executeSparqlQuery(new ArrayCollection(new Array(obj1, obj2)), query[0], onResult, resultFormat, true, null, query[1]);
					
				}
				
			}*/
			
		}
		
		public function executeSparqlQuery(sources:ArrayCollection, sparqlQueryString:String, resultHandler:Function, format:String = "XML", useDefaultGraphURI:Boolean = true, errorHandler:Function = null, parsingInformations:Object = null):SPARQLService {
			//Alert.show(sparqlQueryString);
			
			if (resultHandler == null) {
				resultHandler = findRelations_Result;
			}
			
			var url:String = config.endpointURI + "/sparql?query=";
			
			var sparqlService:SPARQLService = new SPARQLService(config.endpointURI);
			sparqlService.sources = sources;
			sparqlService.parsingInformations = parsingInformations;
			
			if (config.useProxy) {
				sparqlService.url = ConnectionModel.getInstance().proxy + "?" + config.endpointURI + "/sparql?";
			}else {
				sparqlService.url = config.endpointURI + "/sparql?";
			}
			sparqlService.useProxy = false;
			sparqlService.method = "GET";
			sparqlService.contentType = contentType;
			sparqlService.resultFormat = "text";
			sparqlService.addEventListener(SPARQLResultEvent.SPARQL_RESULT, resultHandler);
			
			if (errorHandler != null) {
				sparqlService.addEventListener(FaultEvent.FAULT, errorHandler);
			}else {
				sparqlService.addEventListener(FaultEvent.FAULT, findRelations_Fault);
			}
			
			var params:Dictionary = new Dictionary();
			if (useDefaultGraphURI && config.defaultGraphURI != null && config.defaultGraphURI != "") {
				params["default-graph-uri"] = config.defaultGraphURI;
			}
			params["format"] = format;
			params["query"] = sparqlQueryString;
			
			sparqlService.send(params);
			return sparqlService;
		}
		
		private function findRelations_Result(e:SPARQLResultEvent):void {
			StatusModel.getInstance().addFound();
			var resultNS:Namespace = new Namespace("http://www.w3.org/2005/sparql-results#");
			var result:XML = new XML(e.result);
			var out:String;
			
			if (result..resultNS::results == "") {
				out = "No Relation found" + "\n\n";

			}else{
				out = result.toString() + "\n\n";
			}
			trace("No ResultParser defined:\n" + out);
		}
		
		private function findRelations_Fault(e:FaultEvent):void {
			StatusModel.getInstance().addFound();
			StatusModel.getInstance().addError();
			trace("SPARQLConnection Fault");
			trace(e);
		}
	}
}