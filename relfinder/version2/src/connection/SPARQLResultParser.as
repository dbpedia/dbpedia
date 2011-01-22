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
	import connection.model.ConnectionModel;
	import de.polygonal.ds.HashMap;
	import flash.utils.Dictionary;
	import global.StatusModel;
	import graphElements.Concept;
	import graphElements.Path;
	import graphElements.Relation;
	
	import flash.utils.unescapeMultiByte;
	
	import graphElements.Element;
	
	import mx.collections.ArrayCollection;
	import mx.core.Application;
	import mx.controls.Alert;
	
	
	public class SPARQLResultParser implements ISPARQLResultParser
	{
		private var arrVariables:Array = new Array();
		private var arrNamespaces:Array = new Array();
		private var arrResult:Array = new Array();
		private var arrLinks:Array = new Array();
		private var strBooleanResult:String;
		
		private var resultNS:Namespace = new Namespace("http://www.w3.org/2005/sparql-results#");
		private var result:XML;
		
		public static var idCounter:int = 0;
		
		public function SPARQLResultParser() 
		{
			
		}
		
		public function handleSPARQLResultEvent(event:SPARQLResultEvent):void {
			StatusModel.getInstance().addFound();
			
			var time:Date = event.executenTime;
			var lastClear:Date = ConnectionModel.getInstance().lastClear;
			
			if (time.time > lastClear.time) {
				
				result = new XML(event.result);
				var between:ArrayCollection = event.sources;
				
				if (result..resultNS::results !== "") {
					var parsingInformations:int = new int(event.parsingInformations);
					
					if (parsingInformations == SPARQLQueryBuilder.connectedDirectlyInverted) {
						between = new ArrayCollection(between.toArray().reverse());
					}
					
					parse(result, between, parsingInformations);
				}else {
					trace("empty Result");
				}
			}
		}
		
		/**
		 * @param	_xmlInput
		 * @param	_between
		 */
		public function parse(_xmlInput:XML, _between:ArrayCollection, parsingInformations:int):void{
			
			setVariables(_xmlInput);
			
			var invertDirection:Boolean = false;
			
			var results:Array = new Array();
			for each (var result:XML in _xmlInput..resultNS::results.resultNS::result) {
				var resultArr:Array = new Array();
				for each (var bind:String in getVariables()) {
					var elem:Helper = new Helper;
					elem.binding = bind;
					elem.uri = result.resultNS::binding.(@name == bind).resultNS::uri;
					resultArr.push(elem);
				}
				results.push(resultArr);
				//if (!collectionContainsEntry(results, resultArr)) {
					//results.push(resultArr);
				//}
			}
			
			if (results.length == 0) {
				StatusModel.getInstance().addWasRelationFound(false);
			}else {
				StatusModel.getInstance().addWasRelationFound(true);
			}
			
			for (var i:int = 0; i < results.length; i++) {
				
				var subject:Element;
				var predicate:Element;
				var object:Element;
				
				var subjectBinding:String = "of0";
				var objectBinding:String = "";
				
				var subURI:String = _between.getItemAt(0).toString();
				var subLabel:String = getLabelFromURI(subURI);
				subLabel = unescapeMultiByte(subLabel).split("_").join(" ");
				subject = app().getElement(subURI, subURI, subLabel);
				
				app().getGivenNode(subject.id, subject);	// addGivenElement(subject);
				
				var pathRelations:Array = new Array();	//contains all the relations in one path
				var pathId:String = "";
				
				for (var j:int = 0; j < (results[i] as Array).length; j++) {
					if (((results[i] as Array)[j].binding as String).charAt(0) == "p") {
						
						var predURI:String = ((results[i] as Array)[j].uri).toString();
						var predLabel:String = getLabelFromURI(predURI);
						predLabel = trimHashSign(unescapeMultiByte(predLabel).split("_").join(" "));
						predicate = app().getElement(predURI, predURI, predLabel, true);
						
					}else {
						var objURI:String = ((results[i] as Array)[j].uri).toString();
						var objLabel:String = getLabelFromURI(objURI);
						objLabel = unescapeMultiByte(objLabel).split("_").join(" ");
						
						object = app().getElement(objURI, objURI, objLabel);
						
						//only for testing
						//setConceptOfElement(object, "testCid", "testC");
						
						
						objectBinding = ((results[i] as Array)[j].binding).toString();
						
						
						invertDirection = false;
						if (parsingInformations == SPARQLQueryBuilder.connectedDirectlyInverted || parsingInformations == SPARQLQueryBuilder.connectedViaMiddleInverted) {
							invertDirection = true;
						}
						
						var r1:Relation = getRelation(subject, subjectBinding, predicate, object, objectBinding, invertDirection);
						
						pathRelations.push(r1);
						pathId += r1.id;
						
						
						//app().addRelation(subject, predicate, object);	// getRelation(subURI, subLabel, predURI, predLabel, objURI, objLabel);
						
						subject = app().getElement(objURI, objURI, objLabel);
						subjectBinding = objectBinding;
					}
				}
				var endURI:String = _between.getItemAt(1).toString();
				var endLabel:String = getLabelFromURI(endURI);
				
				endLabel = unescapeMultiByte(endLabel).split("_").join(" ");
				
				object = app().getElement(endURI, endURI, endLabel);
				objectBinding = "os0";
				
				app().getGivenNode(object.id, object);	// addGivenElement(object);
				
				invertDirection = false;
				if (parsingInformations == SPARQLQueryBuilder.connectedDirectlyInverted || parsingInformations == SPARQLQueryBuilder.connectedViaMiddleInverted) {
					invertDirection = true;
				}
				var r2:Relation = getRelation(subject, subjectBinding, predicate, object, objectBinding, invertDirection);
				
				pathRelations.push(r2);
				pathId += r2.id;
				
				app().getPath(pathId, pathRelations);
				
				//app().addRelation(subject, predicate, object);	// .getRelation(subURI, subLabel, predURI, predLabel, endNode.id, endNode.eLabel);
				
			}
			
			app().startDrawing();
		}
		
		private function trimURI(uri:String):String {
			if (uri.charAt(uri.length - 1) == '/') {
				uri = (uri.substr(0, uri.length - 1));
			}
			return uri;
		}
		
		private function trimHashSign(uri:String):String {
			return uri.substr(uri.lastIndexOf("#") + 1)
		}
		
		private function getLabelFromURI(uri:String):String {
			var trimmedUri:String = trimURI(uri);
			return trimmedUri.substr(trimmedUri.lastIndexOf("/") + 1);
		}
		
		private function getRelation(subject:Element, subjectBinding:String, predicate:Element, object:Element, objectBinding:String, invertDirection:Boolean = false):Relation {
			var invert:Boolean = false;
			
			var sub:int;
			var obj:int;
			
			if (subjectBinding.charAt(1) == "f") {
				sub = int.MIN_VALUE + new int(subjectBinding.charAt(2));
			}else if (subjectBinding.charAt(0) == "m") {
				sub = -1;
			}else if (subjectBinding.charAt(1) == "s") {
				sub = new int(subjectBinding.charAt(2));
			}
			
			if (objectBinding.charAt(1) == "f") {
				obj = int.MIN_VALUE + new int(objectBinding.charAt(2));
			}else if (objectBinding.charAt(0) == "m") {
				obj = -1;
			}else if (objectBinding.charAt(1) == "s") {
				obj = new int(objectBinding.charAt(2));
			}
			
			
			
			//if (obj < sub) {
				//invert = true;
			//}
			
			if (invertDirection) {
				invert = !invert;
			}
			
			if (obj >= 0) {
				invert = !invert;
			}
			
			if (invert) {
				return app().getRelation(object, predicate, subject);
			}else {
				return app().getRelation(subject, predicate, object);
			}
			
		}
		
		private function collectionContainsEntry(collection:Array, entry:Array):Boolean {
			if (collection.length != entry.length) {
				return false;
			}
			
			var entryToString:String = "";
			for each(var e:Helper in entry) {
				entryToString += e.binding + e.uri;
			}
			
			var bool:Boolean = false;
			for (var i:int = 0; i < collection.length; i++) {
				var colToString:String = "";
				var col:Array = collection[i] as Array;
				for each(var c:Helper in col) {
					colToString += c.binding + c.uri;
				}
				if (entryToString == colToString) {
					bool = true;
				}
				if (bool) {
					return true;
				}
			}
			return false;
		}
		
		protected function sortVariables():void {
			
			var vars1:Array = new Array();
			var vars2:Array = new Array();
			var varMiddle:Array = new Array();
			
			for each(var mid:String in arrVariables) {
				if (mid == "middle") {
					varMiddle.push("middle");
				}
			}
			
			var i:int = 1;
			var loop:Boolean = true;
			while (loop) {
				loop = false;
				for each(var pf:String in arrVariables) {
					if (pf == "pf" + i) {
						vars1.push("pf" + i);
						loop = true;
					}
				}
				for each(var of:String in arrVariables) {
					if (of == "of" + i) {
						vars1.push("of" + i);
						loop = true;
					}
				}
				i++;
			}
			
			i = 1;
			loop = true;
			while (loop) {
				loop = false;
				for each(var ps:String in arrVariables) {
					if (ps == "ps" + i) {
						vars2.push("ps" + i);
						loop = true;
					}
				}
				for each(var os:String in arrVariables) {
					if (os == "os" + i) {
						vars2.push("os" + i);
						loop = true;
					}
				}
				i++;
			}
			
			arrVariables = vars1.concat(varMiddle.concat(vars2.reverse()));
		}
		
		protected function setVariables(xmlInput:XML):void
		{
			arrVariables = (xmlInput..resultNS::head.resultNS::variable.attributes() as XMLList).toXMLString().split("\n");
			sortVariables();
		}
		
		public function getVariables():Array
		{
			return arrVariables;
		}
		
		protected function setNamespaceDeclarations(_xmlInput:XML):void
		{
			for (var i:uint = 0; i < _xmlInput.namespaceDeclarations().length; i++) 
			{
				var ns:Namespace = _xmlInput.namespaceDeclarations()[i]; 
				var prefix:String = ns.prefix;
				
				if (prefix == "") 
				{
					
					arrNamespaces.unshift(ns);
					
				}
				else
				{
					arrNamespaces.push(ns);
				}
			}
		}
		
		public function getNamespaceDeclarations():Array
		{
			return arrNamespaces;
		}
		
		public function get results():Array
		{
			return arrResult;
		}
		
		public function get links():Array
		{
			return arrLinks;
		}
		
		public function get booleanResult():String		
		{
			return strBooleanResult;
		}
		
		
		
		protected function app():Main {
			return Application.application as Main;
		}
		
		/**
		 * Dummy function!!! Nur zum Zeigen wie Concept gesetzt wird für Element!!
		 * @param	e
		 * @param	cURI
		 * @param	cLabel
		 */
		//do not delete I might need it later to look at it again , Sebastian
		public function setConceptOfElement(e:Element, cURI:String, cLabel:String):void {
			var c:Concept = app().getConcept(cURI, cLabel);
			//c.addElement(e);
			e.concept = c;
		}
		
		public function clear():void {
			result = new XML();
			//TODO mehr!
		}
		
	}
}
class Helper {
	public var uri:String = "";
	public var binding:String = "";
	public function toString():String 
	{
		return uri + " - " + binding;
	}
}