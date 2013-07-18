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
	import flash.utils.Dictionary;
	import mx.collections.ArrayCollection;
	import mx.collections.XMLListCollection;
	
	public class SPARQLQueryBuilder 
	{
		
		public static const db:String = "http://dbpedia.org/resource/";
		public static const rdf:String = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
		public static const skos:String = "http://www.w3.org/2004/02/skos/core#";
		
		public static const connectedDirectly:int = 0;
		public static const connectedDirectlyInverted:int = 1;
		public static const connectedViaMiddle:int = 2;
		public static const connectedViaMiddleInverted:int = 3;
		
		private var prefixes:Dictionary = new Dictionary();
		
		public function SPARQLQueryBuilder() {
			prefixes["db"] = db;
			prefixes["rdf"] = rdf;
			prefixes["skos"] = skos;
		}
		
		
		/**
		 * Builds and returns a set of queries to find relations between two object1 and object2.
		 */
		public function buildQueries(object1:String, object2:String, maxDistance:int, limit:int, ignoredObjects:ArrayCollection = null, ignoredProperties:ArrayCollection = null, avoidCycles:int = 0):ArrayCollection {
			var queries:ArrayCollection = new ArrayCollection();
				
			var queries2:Dictionary = getQueries(object1, object2, maxDistance, limit, ignoredObjects, ignoredProperties, avoidCycles);
			for (var key:String in queries2) {
				var arr:ArrayCollection = (queries2[key] as ArrayCollection);
				if (arr){
					for (var i:int = 0; i < arr.length; i++) {
						queries.addItem(arr.getItemAt(i));
					}
				}
			}
			
			return queries;
			
		}
		
		/**
		 * Takes the core of a SPARQL query and completes it (e.g. adds prefixes).
		 * 
		 */
		private function completeQuery(coreQuery:String, options:Dictionary, vars:Dictionary):String {
			var completeQuery:String = '';
//			for (var key:String in prefixes) {
//				completeQuery += 'PREFIX ' + key + ': <' + prefixes[key] + ">\n";
//			}
			
			
			// TODO: we have to ask for an abstract, an imageURL and a link to wikipedia for each information too!
			
			completeQuery += 'SELECT * WHERE {' + "\n";
			completeQuery += coreQuery + "\n";
			completeQuery += generateFilter(options, vars) + "\n";
			var limit:String = "";
			if (options.hasOwnProperty('limit')) {
				limit = 'LIMIT ' + options['limit'];
			}	
			completeQuery += '} ' + limit;
			return completeQuery;
		}
		
		/**
		 * Return a set of queries to find relations between two objects.
		 * 
		 * @param object1 First object.
		 * @param object2 Second object.
		 * @param maxDistance The maximum distance up to which we want to search.
		 * @param limit The maximum number of results per SPARQL query (=LIMIT).
		 * @param ignoredObjects Objects which should not be part of the returned connections between the first and second object.
		 * @param ignoredProperties Properties which should not be part of the returned connections between the first and second object.
		 * @param avoidCycles Integer value which indicates whether we want to suppress cycles, 
		 * 			0 = no cycle avoidance
		 * 			1 = no intermediate object can be object1 or object2
		 *  		2 = like 1 + an object can not occur more than once in a connection.
		 * @return A two dimensional array of the form $array[$distance][$queries].
		 */
		private function getQueries(object1:String, object2:String, maxDistance:int, limit:int, ignoredObjects:ArrayCollection = null, ignoredProperties:ArrayCollection = null, avoidCycles:int = 0):Dictionary {
			var queries:Dictionary = new Dictionary();
			var options:Dictionary = new Dictionary();
			options['object1'] = object1;
			options['object2'] = object2;
			options['limit'] = limit;
			options['ignoredObjects'] = ignoredObjects;
			options['ignoredProperties'] = ignoredProperties;
			options['avoidCycles'] = avoidCycles;
			
			for(var distance:int = 1; distance <= maxDistance; distance++) {
				// get direct connection in both directions
				queries[distance] = new ArrayCollection();
				(queries[distance] as ArrayCollection).addItem(new Array(direct(object1, object2, distance, options), connectedDirectly));
				(queries[distance] as ArrayCollection).addItem(new Array(direct(object2, object1, distance, options), connectedDirectlyInverted));
				
				/*
				 * generates all possibilities for the distances
				 * 
				 * current
				 * distance 	a 	b
				 * 2			1	1
				 * 3			2	1
				 * 				1	2
				 * 4			3	1
				 * 				1	3
				 * 				2	2
				 * */
				
				for(var a:int = 1; a <= distance; a++) {
					for(var b:int = 1; b <= distance; b++) {
						if ((a + b) == distance) {
							(queries[distance] as ArrayCollection).addItem(new Array(connectedViaAMiddleObject(object1, object2, a, b, true,  options), connectedViaMiddle));
							(queries[distance] as ArrayCollection).addItem(new Array(connectedViaAMiddleObject(object1, object2, a, b, false, options), connectedViaMiddleInverted));
						}
					}
				}
			}
			return queries;
		}
		
		/**
		 * Return a set of queries to find relations between two objects, 
		 * which are connected via a middle objects.
		 * $dist1 and $dist2 give the distance between the first and second object to the middle
		 * they have ti be greater that 1
		 * 
		 * Patterns:
		 * if $toObject is true then:
		 * PATTERN												DIST1	DIST2
		 * first-->?middle<--second 						  	1		1
		 * first-->?of1-->?middle<--second						2		1
		 * first-->?middle<--?os1<--second 						1		2
		 * first-->?of1-->middle<--?os1<--second				2		2
		 * first-->?of1-->?of2-->middle<--second				3		1
		 * 
		 * if $toObject is false then (reverse arrows)
		 * first<--?middle-->second 
		 * 
		 * the naming of the variables is "pf" and "of" because predicate from "f"irst object
		 * and "ps" and "os" from "s"econd object
		 * 
		 * @param first First object.
		 * @param second Second object.
		 * @param dist1 Distance of first object from middle
		 * @param dist2 Distance of second object from middle
		 * @param toObject Boolean reverses the direction of arrows.
		 * @param options All options like ignoredProperties, etc. are passed via this array (needed for filters)
		 * @return the SPARQL Query as a String
		 */
		private function connectedViaAMiddleObject(first:String, second:String, dist1:int, dist2:int, toObject:Boolean, options:Dictionary):String{
			var properties:ArrayCollection = new ArrayCollection();
			var vars:Dictionary = new Dictionary();
			vars['pred'] = new ArrayCollection();
			vars['obj'] = new ArrayCollection();
			
			(vars['obj'] as ArrayCollection).addItem('?middle');
			
			var fs:String = 'f';
			var tmpdist:int = dist1;
			var twice:int = 0;
			var coreQuery:String = "";
			var object:String = first;
			
			// to keep the code compact I used a loop
			// subfunctions were not appropiate since information for filters is collected
			// basically the first loop generates $first-pf1->of1-pf2->middle
			// while the second generates $second -ps1->os1-pf2->middle
			while(twice < 2){
				
				if(tmpdist == 1) {
					coreQuery += toPattern(uri(object), '?p' + fs + '1', '?middle', toObject);
					(vars['pred'] as ArrayCollection).addItem('?p' + fs + '1');
				}else {
					coreQuery += toPattern(uri(object), '?p' + fs + '1', '?o' + fs + '1', toObject);
					(vars['pred'] as ArrayCollection).addItem('?p' + fs + '1');
					
					for(var x:int = 1; x < tmpdist; x++){
						var s:String = '?o' + fs + '' + x;
						var p:String = '?p' + fs + '' + (x + 1); 
						(vars['obj'] as ArrayCollection).addItem(s);
						(vars['pred'] as ArrayCollection).addItem(p);
						if((x+1) == tmpdist){
							coreQuery += toPattern(s , p , '?middle', toObject);
						}else{
							coreQuery += toPattern(s , p , '?o' + fs + '' + (x+1), toObject);
						}
					}
				}
				twice++;
				fs = 's';
				tmpdist = dist2;
				object = second;
				
			}//end while
			
			return  completeQuery(coreQuery, options, vars);
		}  
		
		/**
		 * Helper function to reverse the order 
		 */
		private function toPattern(s:String, p:String, o:String, toObject:Boolean):String{
			if(toObject){
				return s + ' ' + p + ' ' + o + " . \n";
			}else {
				return o + ' ' + p + ' ' + s + " . \n";
			}
			
		}
		
		/**
		 * Returns a query for getting a direct connection from $object1 to $object2.
		 */
		private function direct(object1:String, object2:String, distance:int, options:Dictionary):String {
			var vars:Dictionary = new Dictionary();
			vars['obj'] = new ArrayCollection();
			vars['pred'] = new ArrayCollection();
			if(distance == 1) {
				var retval:String = uri(object1) + ' ?pf1 ' + uri(object2);
				(vars['pred'] as ArrayCollection).addItem('?pf1');
				return completeQuery(retval,  options, vars);
				
			} else {
				var query:String = uri(object1) + ' ?pf1 ?of1 ' + ".\n";
				(vars['pred'] as ArrayCollection).addItem('?pf1');
				(vars['obj'] as ArrayCollection).addItem('?of1');
				for(var i:int = 1; i < distance-1; i++) {
					query += '?of' + i + ' ?pf' + (i+1) + ' ?of' + (i+1) + ".\n";
					(vars['pred'] as ArrayCollection).addItem('?pf' + (i+1));
					(vars['obj'] as ArrayCollection).addItem('?of' + (i+1));
				}
				query  += '?of' + (distance-1) + ' ?pf' + distance + ' ' + uri(object2);
				(vars['pred'] as ArrayCollection).addItem('?pf' + distance);
				//$vars['obj'][] = '?of'.($distance-1);
				return completeQuery(query, options, vars);
			}
		}
		
		private function generateFilter(options:Dictionary, vars:Dictionary):String{
			var filterterms:ArrayCollection = new ArrayCollection();
			for each(var pred:String in (vars['pred'] as ArrayCollection)) {
				// ignore properties
				if(options && options.hasOwnProperty('ignoredProperties') && options['ignoredProperties'] && (options['ignoredProperties'] is ArrayCollection) && (options['ignoredProperties'] as ArrayCollection).length > 0){
					for each(var ignored:String in (options['ignoredProperties'] as ArrayCollection)) {
						filterterms.addItem(pred + ' != ' + uri(ignored) + ' ');
					}
				}
				
			}
			for each(var obj:String in (vars['obj'] as ArrayCollection)) {
				// ignore literals
				filterterms.addItem('!isLiteral(' + obj + ')');
				// ignore objects
				if(options && options.hasOwnProperty('ignoredObjects') && options['ignoredProperties'] && (options['ignoredProperties'] is ArrayCollection) && (options['ignoredObjects'] as ArrayCollection).length > 0){
					for each(var ignored2:String in (options['ignoredObjects'] as ArrayCollection)){
						filterterms.addItem(obj + ' != ' + uri(ignored2) + ' ');
					}
				}
				
				if(options && options.hasOwnProperty('avoidCycles') && options['avoidCycles']){
					// object variables should not be the same as object1 or object2
					if( options['avoidCycles'] > 0){
						filterterms.addItem(obj + ' != ' + uri(options['object1']) + ' ');
						filterterms.addItem(obj + ' != ' + uri(options['object2']) + ' ');
					}
					// object variables should not be the same as any other objectvariables
					if( options['avoidCycles'] > 1){
						for each(var otherObj:String in (vars['obj'] as ArrayCollection)) {
							if(obj != otherObj){
								filterterms.addItem(obj + ' != ' + otherObj + ' ');
							}
						}
					}
				}
			}
			
			if (filterterms.length == 0) {
				return "";
			}
			
			return 'FILTER ' + expandTerms(filterterms, '&&') + '. ';
		}
		
		/**
		 * simple startsWith function 
		 */
		private function startsWith(haystack:String, needle:String):Boolean{
		  // Recommended version, using strpos
		   return haystack.indexOf(needle) == 0;
		}
		
		/**
		 * Takes a URI and formats it according to the prefix map.
		 * This basically is a fire and forget function, punch in 
		 * full uris, prefixed uris or anything and it will be fine
		 * 
		 * 1. if uri can be prefixed, prefixes it and returns
		 * 2. checks whether uri is already prefixed and returns
		 * 3. else it puts brackets around the <uri>
		 */
		private function uri(uri:String):String {
			
			//Prefixe und Sonderzeichen funktionieren nicht zusammen!!!
			
			//for (var key:String in prefixes) {
				//if (startsWith(uri, prefixes[key] )) {
					//uri = uri.replace(prefixes[key], key + ":");
					//return uri;
				//}
			//}
			//
			//for (var key2:String in prefixes) {
				//if (startsWith(uri,  (key2 + ":") )) {
					//return uri;
				//}
			//}
			return "<" + uri + ">";
		}
		
		/*
		 * puts bracket around the (filterterms) and concatenates them with &&
		 * 
		 */
		private function expandTerms (terms:ArrayCollection, operator:String = "&&"):String{
			var result:String = "";
			for (var x:int = 0; x < terms.length; x++){
				result += "(" + terms.getItemAt(x) + ")";
				result += (x + 1 == terms.length) ? "" : " " + operator +" ";
				result += "\n";
			}
			return "(" + result + ")";
		}
	}
	
}