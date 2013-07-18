package connection
{
	import connection.model.ConnectionModel;
	import connection.model.LookUpCache;
	
	import mx.collections.ArrayCollection;
	import mx.rpc.events.FaultEvent;
	import mx.utils.StringUtil;
	
	import utils.SimilaritySort;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class LookUpSPARQL implements ILookUp
	{
		private var sparqlConnection:SPARQLConnection = new SPARQLConnection();
		
		private var target:Object;
		
		private var currentInput:String = "";
		
		public function run(_input:String, target:Object, limit:int = 0):void {
			
			currentInput = _input;
			
			this.target = target;
			
			var inputArrayCollection:ArrayCollection = new ArrayCollection();
			inputArrayCollection.addItem(_input);
			
			var query:String = "";
			
			if (ConnectionModel.getInstance().sparqlConfig.isVirtuoso) {
				//query = createStandardBIFContainsQuery(_input, limit);
				//sparqlConnection.executeSparqlQuery(inputArrayCollection, query, lookUp_Result);
				
				
				//query = createCompleteOutdegQuery(_input, 20);
				query = createCompleteIndegQuery(_input, 20);
				sparqlConnection.executeSparqlQuery(inputArrayCollection, query, lookUp_Count_Result);
				
				
			}else {
				query = createStandardREGEXQuery(_input, 20);
				//query = createCompleteREGEXIndegQuery(_input, 20);
				sparqlConnection.executeSparqlQuery(inputArrayCollection, query, lookUp_Result);
			}
			
		}
		
		public function createStandardBIFContainsQuery(input:String, limit:int = 0):String {
			input = StringUtil.trim(input);
			var query:String = "";
			query = "SELECT ?s ?l WHERE { ";
			if (ConnectionModel.getInstance().sparqlConfig.autocompleteURIs != null && ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length > 0) {
				query += "{ ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(0) + "> ?l } ";
				for (var i:int = 1; i < ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length; i++) {
					query += "UNION { ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(i) + "> ?l } ";
				}
				query += ". ";
			}else {
				query += "?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . "
			}
			query += " ?l bif:contains \"'" + input + "'\" . } "; 
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		
//		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
//		PREFIX foaf: <http://xmlns.com/foaf/0.1/>
//		PREFIX dcterms:  <http://purl.org/dc/terms/>
//		SELECT ?s  ?l  count(?s)  WHERE {
//		{?s ?p ?o.
//		{?s rdfs:label ?l }
//		UNION {?s foaf:name ?l}
//		UNION {?s dcterms:title ?l}
//		Filter regex(?l, 'Bruce', 'i') }
//		 }
//		GROUP BY ?s ?l 
		public function createStandardREGEXQuery(input:String, limit:int = 20):String {
			input = StringUtil.trim(input);
			var query:String = "";
			query = "SELECT ?s ?l WHERE { ";
			if (ConnectionModel.getInstance().sparqlConfig.autocompleteURIs != null && ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length > 0) {
				query += "{ ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(0) + "> ?l } ";
				for (var i:int = 1; i < ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length; i++) {
					query += "UNION { ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(i) + "> ?l } ";
				}
				query += ". ";
			}else {
				query += "?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . "
			}
			query += "FILTER regex(?l, '" + input + "', 'i')  . } "; 
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		public function createCompleteOutdegQuery(input:String, limit:int = 0):String {
			input = StringUtil.trim(input);
			if (input.search(" ") < 0) {
				return createSingleWordCompleteCountOutdegQuery("'" + input + "'", limit);
			}else {
				var newInput:String = input.split(" ").join("' and '");
				return createMultipleWordsCompleteCountOutdegQuery("'" + newInput + "'", limit);
			}
		}
		
		private function createMultipleWordsCompleteCountOutdegQuery(input:String, limit:int = 0):String {
			var query:String = "";
			query = "SELECT DISTINCT ?s ?l count(?s) as ?count WHERE { ?s ?p ?someobj . ";
			if (ConnectionModel.getInstance().sparqlConfig.autocompleteURIs != null && ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length > 0) {
				query += "{ ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(0) + "> ?l } ";
				for (var i:int = 1; i < ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length; i++) {
					query += "UNION { ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(i) + "> ?l } ";
				}
				query += ". ";
			}else {
				query += "?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . "
			}
			query += "?l bif:contains \"" + input + "\" . FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')). FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). FILTER (lang(?l) = 'en'). FILTER (!isLiteral(?someobj)). } ORDER BY DESC(?count) "; 
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		private function createSingleWordCompleteCountOutdegQuery(input:String, limit:int = 0):String {
			var query:String = "";
			query = "SELECT ?s ?l count(?s) as ?count WHERE { ?s ?p ?someobj . ";
			if (ConnectionModel.getInstance().sparqlConfig.autocompleteURIs != null && ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length > 0) {
				query += "{ ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(0) + "> ?l } ";
				for (var i:int = 1; i < ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length; i++) {
					query += "UNION { ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(i) + "> ?l } ";
				}
				query += ". ";
			}else {
				query += "?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . "
			}
			query += "?l bif:contains \"" + input + "\" . FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')). FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). FILTER (lang(?l) = 'en'). FILTER (!isLiteral(?someobj)). } ORDER BY DESC(?count) "
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		public function createCompleteIndegQuery(input:String, limit:int = 0):String {
			input = StringUtil.trim(input);
			if (input.search(" ") < 0) {
				return createSingleWordCompleteCountIndegQuery("'" + input + "'", limit);
			}else {
				var newInput:String = input.split(" ").join("' and '");
				return createMultipleWordsCompleteCountIndegQuery("'" + newInput + "'", limit);
			}
		}
		
		private function createMultipleWordsCompleteCountIndegQuery(input:String, limit:int = 0):String {
			var query:String = "";
			query = "SELECT DISTINCT ?s ?l count(?s) as ?count WHERE { ?someobj ?p ?s . ";
			if (ConnectionModel.getInstance().sparqlConfig.autocompleteURIs != null && ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length > 0) {
				query += " ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(0) + "> ?l ";
				for (var i:int = 1; i < ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length; i++) {
					query += "UNION { ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(i) + "> ?l }";
				}
				query += ". ";
			}else {
				query += "?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . "
			}
			query += "?l bif:contains \"" + input + "\" . FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')). FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). FILTER (lang(?l) = 'en'). FILTER (!isLiteral(?someobj)). } ORDER BY DESC(?count) "; 
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		private function createSingleWordCompleteCountIndegQuery(input:String, limit:int = 0):String {
			var query:String = "";
			query = "SELECT ?s ?l count(?s) as ?count WHERE { ?someobj ?p ?s . ";
			if (ConnectionModel.getInstance().sparqlConfig.autocompleteURIs != null && ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length > 0) {
				query += "?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(0) + "> ?l ";
				for (var i:int = 1; i < ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.length; i++) {
					query += "UNION { ?s <" + ConnectionModel.getInstance().sparqlConfig.autocompleteURIs.getItemAt(i) + "> ?l } ";
				}
				query += ". ";
			}else {
				query += "?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . "
			}
			query += "?l bif:contains \"" + input + "\" . FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')). FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). FILTER (lang(?l) = 'en'). FILTER (!isLiteral(?someobj)). } ORDER BY DESC(?count) "
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		public function createCompleteREGEXIndegQuery(input:String, limit:int = 0):String {
			input = StringUtil.trim(input);
			if (input.search(" ") < 0) {
				return createSingleWordREGEXCompleteCountIndegQuery("\"" + input + "\"", limit);
			}else {
				var newInput:String = input.split(" ").join("\" and \"");
				return createMultipleWordsREGEXCompleteCountIndegQuery("\"" + newInput + "\"", limit);
			}
		}
		
		private function createMultipleWordsREGEXCompleteCountIndegQuery(input:String, limit:int = 0):String {
			var query:String = "";
			query = "SELECT DISTINCT ?s ?l count(?s) as ?count WHERE { ?someobj ?p ?s . ?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . filter regex(?l, '" + input + "', 'i')  . FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')). FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). FILTER (lang(?l) = 'en'). FILTER (!isLiteral(?someobj)). } ORDER BY DESC(?count) "; 
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		private function createSingleWordREGEXCompleteCountIndegQuery(input:String, limit:int = 0):String {
			var query:String = "";
			query = "SELECT ?s ?l COUNT(?s) WHERE { ?someobj ?p ?s . ?s <http://www.w3.org/2000/01/rdf-schema#label> ?l . FILTER regex(?l, '" + input + "', 'i')  . FILTER (!regex(str(?s), '^http://dbpedia.org/resource/Category:')). FILTER (!regex(str(?s), '^http://dbpedia.org/resource/List')). FILTER (!regex(str(?s), '^http://sw.opencyc.org/')). FILTER (lang(?l) = 'en'). FILTER (!isLiteral(?someobj)). } ORDER BY DESC(COUNT(?s)) "
			if (limit != 0) {
				query += "LIMIT " + limit.toString();
			}
			return query;
		}
		
		private function createInputStringWithWildcards(input:String):String {
			var output:String = "";
			
			var stringArray:Array = input.split(" ");
			for (var i:int; i < stringArray.length; i++) {
				if (stringArray[i].toString().length >= 3 && i == stringArray.length - 1) {
					stringArray[i] = stringArray[i].toString() + "*";
				}
				output += stringArray[i];
				if (!(i == stringArray.length - 1)) {
					output += " ";
				}
			}
			
			trace(output);
			
			return output;
		}
		
		public function traceResults(e:SPARQLResultEvent):void {
			trace(e.result);
		}
		
		public function lookUp_Count_Result(e:SPARQLResultEvent):void {
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
				var result:XML = new XML(e.result);
				var resultNS:Namespace = new Namespace("http://www.w3.org/2005/sparql-results#");
				
				var contains:Boolean = false;
				var containsLabel:Boolean = false;
				
				if (result..resultNS::results != "") {
					for each (var res:XML in result..resultNS::results.resultNS::result) {
						
						var newLabel:String = res.resultNS::binding.(@name == 'l').resultNS::literal;
						var newUri:String = res.resultNS::binding.(@name == 's').resultNS::uri;
						var newCount:int = new int(res.resultNS::binding.(@name == 'count').resultNS::literal);
						
						contains = false;
						containsLabel = false;
						
						var oldObject:Object;
						
						for each (var entry:Object in results) {
							if (entry.label.toString() == newLabel.toString()) {
								containsLabel = true;
								for each (var uri:String in (entry.uris as Array)) {
									if (uri == newUri) {
										contains = true;
									}
								}
								if (!contains){
									(entry.uris as Array).push(newUri);
								}
								continue;
							}
						}
						
						if (!contains) {
							
							if (!containsLabel){
								var ob:Object;
								ob = new Object();
								ob.label = newLabel;
								ob.count = newCount;
								ob.uris = new Array(newUri);
								results.addItem(ob);
							}
						}
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
		
		public function lookUp_Result(e:SPARQLResultEvent):void {
			var lastSend:Date = LookUpCache.getInstance().getLastSend(target);
			var resultSend:Date = e.executenTime;
			
			if (lastSend == null) {
				lastSend = resultSend;
			}
			
			LookUpCache.getInstance().setLastSend(target, resultSend);
			
			var lastInput:String = e.sources.getItemAt(0).toString();
			
			trace("lastinput", lastInput, "currentinput", currentInput);
			
			if (resultSend.time >= lastSend.time && lastInput && currentInput && lastInput == currentInput) {
				
				var results:ArrayCollection = new ArrayCollection();
				var result:XML;
				try {
					result = new XML(e.result);
				} catch (error:TypeError){
					result = new XML();
					trace(error);
					trace(e.result);
				}
				var resultNS:Namespace = new Namespace("http://www.w3.org/2005/sparql-results#");
				
				var contains:Boolean = false;
				var containsLabel:Boolean = false;
				
				if (result..resultNS::results != "") {
					for each (var res:XML in result..resultNS::results.resultNS::result) {
						
						var newLabel:String = res..resultNS::literal;
						var newUri:String = res..resultNS::uri;
						
						contains = false;
						containsLabel = false;
						
						var oldObject:Object;
						
						for each (var entry:Object in results) {
							if (entry.label.toString() == newLabel.toString()) {
								containsLabel = true;
								for each (var uri:String in (entry.uris as Array)) {
									if (uri == newUri) {
										contains = true;
									}
								}
								if (!contains){
									(entry.uris as Array).push(newUri);
								}
								continue;
							}
						}
						
						if (!contains) {
							
							if (!containsLabel){
								var ob:Object;
								ob = new Object();
								ob.label = newLabel;
								ob.uris = new Array(newUri);
								results.addItem(ob);
							}
						}
					}
				}
				
				if (results.length == 0) {
					var empty:Object = new Object();
					empty.label = "No results found";
					results.addItem(empty);
				}
				
				SimilaritySort.sort(results, lastInput);
				
				target.dataProvider = results;
			}
			
		}
		
		public function lookUp_Fault(e:FaultEvent):void {
			trace("lookUp_Fault: " + e.message.toString());
		}
		
	}
	
}