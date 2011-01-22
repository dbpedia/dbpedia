/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

import com.adobe.flex.extras.controls.springgraph.Graph;
import com.dynamicflash.util.Base64;
import com.hillelcoren.components.AutoComplete;
import connection.config.Config;
import connection.config.IConfig;
import connection.model.LookUpCache;
import mx.core.Repeater;
import mx.rpc.events.FaultEvent;
import mx.rpc.http.HTTPService;
import mx.utils.ObjectUtil;
import mx.utils.StringUtil;

import connection.ILookUp;
import connection.ISPARQLResultParser;
import connection.LookUpKeywordSearch;
import connection.SPARQLConnection;
import connection.SPARQLResultParser;
import connection.config.DBpediaConfig;
import connection.config.LODConfig;
import connection.model.ConnectionModel;

import de.polygonal.ds.ArrayedQueue;
import de.polygonal.ds.HashMap;
import de.polygonal.ds.Iterator;

import flash.desktop.Clipboard;
import flash.desktop.ClipboardFormats;
import flash.events.Event;
import flash.events.MouseEvent;
import flash.events.TextEvent;
import flash.events.TimerEvent;
import flash.utils.Dictionary;
import flash.utils.Timer;

import global.Languages;
import global.StatusModel;

import graphElements.*;

import mx.collections.ArrayCollection;
import mx.controls.Alert;
import mx.core.Application;
import mx.managers.PopUpManager;
import mx.rpc.events.ResultEvent;

import popup.ExpertSettings;
import popup.Infos;
import popup.InputDisambiguation;
import popup.InputSelection;
import popup.InputSelectionEvent;

[Bindable]
private var graph:Graph = new Graph();
private var foundNodes:HashMap = new HashMap();
private var givenNodes:HashMap = new HashMap();
private var givenNodesInsertionTime:HashMap = new HashMap();
private var _relationNodes:HashMap = new HashMap();
private var relations:HashMap = new HashMap();
private var elements:HashMap = new HashMap();
private var toDrawPaths:ArrayedQueue = new ArrayedQueue(1000);
//private var iter:Iterator;
//[Bindable]
//public var currentNode:MyNode = null;	//the currently selected node in the graph
private var _selectedElement:Element = null;	//so ist es besser!

private var myConnection:SPARQLConnection = null;
private var sparqlEndpoint:String = "";
private var basicGraph:String = "";
private var resultParser:ISPARQLResultParser = new SPARQLResultParser();

private var lastInputs:Array = new Array();

[Bindable]
private var inputFields:ArrayCollection = new ArrayCollection(new Array(new String("input0"), new String("input1")));
[Bindable]
private var autoCompleteList:ArrayCollection = new ArrayCollection();

[Bindable]
private var _concepts:ArrayCollection = new ArrayCollection();
private var _selectedConcept:Concept = null;

[Bindable]
private var _relTypes:ArrayCollection = new ArrayCollection();
private var _selectedRelType:RelType = null;

[Bindable]
private var _pathLengths:ArrayCollection = new ArrayCollection();
private var _selectedPathLength:PathLength = null;	//??? braucht man ??

private var _paths:HashMap = new HashMap();
//[Bindable(event = "maxPathLengthChange")]
//private var _maxPathLength:int = 0;
//private var _selectedMaxPathLength:int = 0;	
//private var _selectedMinPathLength:int = 0;

[Bindable(event = "eventLangsChanged")]
private var languageDP:Array = Languages.getInstance().asDataProvider;

public var PLRCHANGE:String = "selectedPathLengthRangeChange";

private var _graphIsFull:Boolean = false;	//whether the graph is overcluttered already!
private var _delayedDrawing:Boolean = true;

private var inputCache:ArrayCollection = new ArrayCollection();

[Bindable]
private var _showOptions:Boolean = false;	//flag to set filters and infos visible or invisible

private function setup(): void {
	
	myConnection = new SPARQLConnection();
	
	StatusModel.getInstance().addEventListener("eventMessageChanged", statusChangedHandler);
	
	callLater(setupParams);
	
	fillExamples();
}

private function setupParams():void {
	var hasObjectParameters:Boolean = false;
	var param:Dictionary = getUrlParamateres();
	
	var conf:Config = new Config();
	
	for (var key:String in param) {
		
		if (key.substring(0, 3) == "obj") {
			hasObjectParameters = validateParamters(key, param[key].toString()) || hasObjectParameters;
		}
		
		//if (key == "obj2") {
			//hasObjectParameters = validateParamters(key, param[key].toString()) || hasObjectParameters;
		//}
		
		if (key == "name") {
			conf.name = Base64.decode(param[key]);
		}
		
		if (key == "description") {
			conf.description = Base64.decode(param[key]);
		}
		
		if (key == "endpointURI") {
			conf.endpointURI = Base64.decode(param[key]);
		}
		
		if (key == "defaultGraphURI") {
			conf.defaultGraphURI = Base64.decode(param[key]);
		}
		
		if (key == "isVirtuoso") {
			conf.isVirtuoso = (Base64.decode(param[key]) == "true") ? true : false;
		}
		
		if (key == "useProxy") {
			conf.useProxy = (Base64.decode(param[key]) == "true") ? true : false;
		}
		
		if (key == "autocompleteURIs") {
			conf.autocompleteURIs = new ArrayCollection(Base64.decode(param[key]).split(","));
		}
		
		if (key == "ignoredProperties") {
			conf.ignoredProperties = new ArrayCollection(Base64.decode(param[key]).split(","));
		}
		
	}
	
	if (hasObjectParameters) {
		
		if (conf.endpointURI != null && conf.endpointURI != "") {
			var found:Boolean = false;
			
			for (var i:int = 0; i < ConnectionModel.getInstance().sparqlConfigs.length; i++) {
				if (!found && conf.equals(ConnectionModel.getInstance().sparqlConfigs.getItemAt(i) as IConfig)) {
					found = true;
					
					ConnectionModel.getInstance().sparqlConfig = ConnectionModel.getInstance().sparqlConfigs.getItemAt(i) as IConfig;
					
				}
			}
			
			if (!found) {
				ConnectionModel.getInstance().sparqlConfigs.addItem(conf);
				ConnectionModel.getInstance().sparqlConfig = conf;
			}
		}
		
		callLater(findRelations);
	}
}

private function preInitHandler(event:Event):void {
	// load config
	var root:String = Application.application.url;
	var configLoader:HTTPService = new HTTPService(root);
	
	configLoader.addEventListener(ResultEvent.RESULT, xmlCompleteHandler);
	configLoader.addEventListener(FaultEvent.FAULT, xmlCompleteHandler);
	configLoader.url = "config/Config.xml";
	configLoader.send();
   
}

private function xmlCompleteHandler(event:Event):void {
	if (event is ResultEvent) {
		
		var result:Object = (event as ResultEvent).result.data;
		
		// set proxy
		ConnectionModel.getInstance().proxy = result.proxy.url;
		ConnectionModel.getInstance().defaultProxy = result.proxy.url;
		
		// set default endpoint
		var defaultConfig:Config = getConfig(result.endpoints.defaultEndpoint);
		ConnectionModel.getInstance().sparqlConfigs.addItem(defaultConfig);
		ConnectionModel.getInstance().sparqlConfig = defaultConfig;
		
		for each (var obj:Object in result.endpoints.endpoint) {
			ConnectionModel.getInstance().sparqlConfigs.addItem(getConfig(obj));
		}
		
	}else {
		Alert.show((event as FaultEvent).fault.toString(), "Config file not found");
	}
	
	callLater(setInitialized);
}

private function setInitialized():void {
	super.initialized = true
}

public function getConfig(conf:Object):Config {
	
	var config:Config = new Config();
	
	config.name = conf.name;
	config.description = conf.description;
	config.endpointURI = conf.endpointURI;
	config.defaultGraphURI = conf.defaultGraphURI;
	config.isVirtuoso = conf.isVirtuoso;
	config.useProxy = conf.useProxy;
	if (conf.autocompleteURIs != null) {
		for each (var autocomplete:Object in conf.autocompleteURIs) {
			if (autocomplete is ArrayCollection) {
				config.autocompleteURIs = autocomplete as ArrayCollection;
			}else {
				config.autocompleteURIs = new ArrayCollection([autocomplete]);
			}
		}
	}
	if (conf.ignoredProperties != null) {
		for each (var ignoredProperty:Object in conf.ignoredProperties) {
			if (ignoredProperty is ArrayCollection) {
				config.ignoredProperties = ignoredProperty as ArrayCollection;
			}else {
				config.ignoredProperties = new ArrayCollection([ignoredProperty]);
			}
		}
	}
	//config.autocompleteURIs = (conf.autocompleteURIs != null) ? conf.autocompleteURIs.autocompleteURI : null;
	//config.ignoredProperties = (conf.ignoredProperties != null) ? conf.ignoredProperties.ignoredProperty : null;
	
	return config;
}

override public function set initialized(value:Boolean):void{
	// don't do anything, so we wait until the xml loads
}

private function statusChangedHandler(event:Event):void {
	statusLabel.text = "Status: " + StatusModel.getInstance().message;
	
	if (StatusModel.getInstance().isSearching){
		la.startRotation();
	}else{
		la.stopRotation();
		delayedDrawing = false;
	}
}

private function validateParamters(key:String, value:String):Boolean {
	if (key.indexOf("obj") == 0) {
		var index:int = new int(key.charAt(3));
		
		while (index > inputFieldRepeater.dataProvider.length) {
			addNewInputField();
		}
	
		if (index != 0) {
			var  obj:Object = decodeObjectParameter(value);
			(inputField[index - 1] as AutoComplete).selectedItem = obj;
			(inputField[index - 1] as AutoComplete).validateNow();
		}
		return true;
	}
	return false;
}

private function inputToURL():String {
	var str:String = "";
	
	str += Application.application.url.substring(0, Application.application.url.lastIndexOf(".swf") + 4) + "?";
	
	var i:int;
	
	for (i = 0; i < lastInputs.length; i++) {
		str += "obj" + (i + 1) + "=" + encodeObjectParameter(lastInputs[i].label, lastInputs[i].uri);
		if (i + 1 < lastInputs.length) {
			str += "&";
		}
	}
	
	str += ConnectionModel.getInstance().sparqlConfig.toURLParameters();
	
	return str;
}

private function encodeObjectParameter(label:String, url:String):String {
	return Base64.encode(label + "|" + url);
}

private function decodeObjectParameter(value:String):Object {
	var obj:Object = new Object();
	var str:String = Base64.decode(value);
	var arr:Array = str.split("|");
	obj.label = arr[0].toString();
	obj.uris = new Array();
	for (var i:int = 1; i <= arr.length - 1; i++){
		if (arr[i] && arr[i].toString() != ""){
			(obj.uris as Array).push(arr[i].toString());
		}
	}
	
	return obj;
}

private function getUrlParamateres():Dictionary {
	var urlParams:Dictionary = new Dictionary();
	var param:Object = Application.application.parameters;
	for (var key:String in param) {
		urlParams[key] = param[key];
	}
	return urlParams;
}

public function pathLengthRangeChanged(limit1:int, limit2:int):void {
	/*if (_delayedDrawing) {
		//emptyToDrawPaths();	//empties the queue
		//delayedDrawing = false;
	}*/
	
	//trace("new lengthRange: " + limit1 + ", " + limit2);
	if (limit1 < limit2) {
		//_selectedMaxPathLength = limit2;
		//_selectedMinPathLength = limit1;
	}else {
		//_selectedMaxPathLength = limit1;
		//_selectedMinPathLength = limit2;
	}
	//pathLengthRange.values = new Array(_selectedMinPathLength, _selectedMaxPathLength);
	//dispatchEvent(new Event(PLRCHANGE));
}

/*[Bindable(event=PLRCHANGE)]
public function get selectedMaxPathLength():int {
	return _selectedMaxPathLength;
}*/

/*[Bindable(event=RLRPLCHANGE)]
public function get selectedMinPathLength():int {
	return _selectedMinPathLength;
}*/

public function getConcept(uri:String, label:String):Concept {
	//trace("getConcept : " + uri);
	for each(var c:Concept in _concepts) {
		if (c.id == uri) {
			
			return c;
		}
	}
	//trace("build new concpet " + uri);
	var newC:Concept = new Concept(uri, label);
	_concepts.addItem(newC);
	newC.addEventListener(Concept.NUMVECHANGE, conceptChangeListener);
	newC.addEventListener(Concept.VCHANGE, conceptChangeListener);
	return newC;
}

private function conceptChangeListener(event:Event):void {
	var c:Concept = event.target as Concept;
	_concepts.itemUpdated(c);
}

[Bindable]
public function get selectedConcept():Concept {
	return _selectedConcept;
}

public function set selectedConcept(c:Concept):void {
	if (_selectedConcept != c) {
		//trace("selectedConcept change "+c.id);
		
		//deselect all other selections
		selectedRelType = null;
		selectedPathLength = null;
		
		_selectedConcept = c;
		//dispatchEvent(new Event("selectedConceptChange"));
	}
}


/** RelTypes **/

public function getRelType(uri:String, label:String):RelType {
	//trace("getConcept : " + uri);
	for each(var r:RelType in _relTypes) {
		if (r.id == uri) {
			
			return r;
		}
	}
	trace("build new reltype " + uri);
	var newR:RelType = new RelType(uri, label);
	_relTypes.addItem(newR);
	newR.addEventListener(RelType.NUMVRCHANGE, relTypeChangeListener);
	newR.addEventListener(RelType.VCHANGE, relTypeChangeListener);
	
	if (_graphIsFull) {
		//newR.isVisible = false;
	}
	return newR;
}

private function relTypeChangeListener(event:Event):void {
	
	var rT:RelType = event.target as RelType;
	//trace("relTypes update : " +rT.numVisibleRelations);
	_relTypes.itemUpdated(rT);
}

[Bindable]
public function get selectedRelType():RelType {
	return _selectedRelType;
}

public function set selectedRelType(r:RelType):void {
	if (_selectedRelType != r) {
		//trace("selectedConcept change "+c.id);
		
		//deselect all other selections
		selectedConcept = null;
		selectedPathLength = null;
		
		_selectedRelType = r;
		//dispatchEvent(new Event("selectedConceptChange"));
	}
}


/** PathLenghts **/

public function getPathLength(uri:String, length:int):PathLength {
	for each(var pL:PathLength in _pathLengths) {
		if (pL.id == uri) {
			
			return pL;
		}
	}
	//trace("build new concpet " + uri);
	var newPL:PathLength = new PathLength(uri, length);
	_pathLengths.addItem(newPL);
	newPL.addEventListener(PathLength.NUMVPCHANGE, pathLengthChangeListener);
	newPL.addEventListener(PathLength.VCHANGE, pathLengthChangeListener);
	
	if (_graphIsFull) {
		//set new pathLength invisible
		newPL.isVisible = false;
	}
	return newPL;
}

private function pathLengthChangeListener(event:Event):void {
	var pL:PathLength = event.target as PathLength;
	_pathLengths.itemUpdated(pL);
}

[Bindable]
public function get selectedPathLength():PathLength {
	return _selectedPathLength;
}

public function set selectedPathLength(p:PathLength):void {
	if (_selectedPathLength != p) {
		//trace("selectedConcept change "+c.id);
		
		//deselect all other selections
		selectedRelType = null;
		selectedConcept = null;
		
		_selectedPathLength = p;
		//dispatchEvent(new Event("selectedConceptChange"));
	}
}

public function getGivenNode(_uri:String, _element:Element):GivenNode {
	if (!givenNodes.containsKey(_uri)) {
		var newGivenNode:GivenNode = new GivenNode(_uri, _element);
		givenNodes.insert(_uri, newGivenNode);
		givenNodesInsertionTime.insert(_uri, new Date());
		
		var givenNodesArray:Array = new Array();
		
		//var itr:Iterator = 
		var keys:Array = givenNodesInsertionTime.getKeySet();
		
		//var uri:String = "";
		
		for each(var uri:String in keys) {
			if (givenNodes.containsKey(uri)) {
				givenNodesArray.push({time:(givenNodesInsertionTime.find(uri) as Date).time, node:givenNodes.find(uri)});
			}
		}
		/*while (itr.hasNext()) {
			uri = itr.next() as String;
			if (givenNodes.containsKey(uri)) {
				givenNodesArray.push({time:(givenNodesInsertionTime.find(uri) as Date).time, node:givenNodes.find(uri)});
			}
		}*/
		
		givenNodesArray.sortOn("time", Array.NUMERIC);
		
		addNodeToGraph(newGivenNode);
		
		var angle:Number = 360 / givenNodesArray.length;
		var centerX:Number = this.sGraph.width / 2;
		var centerY:Number = this.sGraph.height / 2
		var radius:Number = Math.min(centerX - 120, centerY - 80);
		
		for (var i:int = 0; i < givenNodesArray.length; i++) {
			if ((givenNodesArray[i].node as GivenNode).getX() == 0 && (givenNodesArray[i].node as GivenNode).getY() == 0) {
				(givenNodesArray[i].node as GivenNode).setPosition( (radius) * Math.sin((i * angle - 90) * (Math.PI / 180)) + centerX, (-radius) * Math.cos((i * angle - 90) * (Math.PI / 180)) + centerY);
			}else {
				moveNodeToPosition((givenNodesArray[i].node as GivenNode), (radius) * Math.sin((i * angle - 90) * (Math.PI / 180)) + centerX, ( -radius) * Math.cos((i * angle - 90) * (Math.PI / 180)) + centerY);
			}
		}
		
		//trace("add givenNode: " + newGivenNode.id);
		
		
		//switch(givenNodes.size) {
			//case 1:
				//newGivenNode.setPosition(120, (this.sGraph.height / 2));
				//break;
			//case 2: 
				//newGivenNode.setPosition((this.sGraph.width - 120), (this.sGraph.height / 2));
				//break;
			//default:
				//newGivenNode.setPosition(Math.random() * this.sGraph.width, Math.random() * this.sGraph.height);
				//break;
		//}
	}
	return givenNodes.find(_uri);
}

public function moveNodeToPosition(node:GivenNode, x:Number, y:Number):void {
	(node as GivenNode).moveToPosition(x, y);
}

public function addNewInputField():void {
	inputCache = new ArrayCollection();
	
	for (var i:int = 0; i < inputFieldRepeater.dataProvider.length; i++) {
		if (inputField[i] is AutoComplete) {
			inputCache.addItem((inputField[i] as AutoComplete).selectedItem);
		}
	}
	
	inputFields.addItem(new String('input'));
	
	(inputFieldRepeater as Repeater).validateNow();
	
	for (var j:int = 0; j < inputCache.length; j++) {
		(inputField[j] as AutoComplete).selectedItem = inputCache.getItemAt(j);
	}
	
	(inputFieldRepeater as Repeater).validateNow();
	
	
}

public function removeInputField(index:int):void {
	
	if (inputFieldRepeater.dataProvider.length <= 2) {
		return;
	}
	
	inputCache = new ArrayCollection();
	
	for (var i:int = 0; i < inputFieldRepeater.dataProvider.length; i++) {
		if (i != index && inputField[i] is AutoComplete) {
			inputCache.addItem((inputField[i] as AutoComplete).selectedItem);
		}
	}
	
	inputFields.removeItemAt(index);
	
	(inputFieldRepeater as Repeater).validateNow();
	
	for (var j:int = 0; j < inputCache.length; j++) {
		(inputField[j] as AutoComplete).selectedItem = inputCache.getItemAt(j);
	}
	
	(inputFieldRepeater as Repeater).validateNow();
}

public function getInstanceNode(_id:String, _element:Element):MyNode {
	if (givenNodes.containsKey(_id)) {	//if the node is a given node!
		
		return givenNodes.find(_id) as MyNode;
	}
	if (!foundNodes.containsKey(_id)) {
		var newFoundNode:FoundNode = new FoundNode(_id, _element);
		//trace("new FoundNode: " + newFoundNode.id);
		foundNodes.insert(_id, newFoundNode);
		addNodeToGraph(newFoundNode);
	}
	return foundNodes.find(_id) as MyNode;
}

public function getRelationNode(id:String, relation:Relation):RelationNode {
	if (!_relationNodes.containsKey(id)) {
		//trace("<<<< do not exist yet: " + id);
		var newRelationNode:RelationNode = new RelationNode(id, relation);
		_relationNodes.insert(id, newRelationNode);
		addNodeToGraph(newRelationNode);
	}
	return _relationNodes.find(id);
}

public function drawPath(p:Path, immediatly:Boolean = false):void {
	
	if (delayedDrawing && !immediatly) {
		//trace("want to draw path: " + p.id);
		toDrawPaths.enqueue(p);
		startDrawing();
	}else {
		//trace("draw path: " + p.id);
		for each(var r:Relation in p.relations) {
			drawRelation(r, p.layout);
		}
	}
	
}

private function drawRelation(_r:Relation, layout:Object = null):void {
	
	var subject:Element = _r.subject;
	var object:Element = _r.object;
	var predicate:Element = _r.predicate;
	
	//trace("draw relation: " + subject.id + ", " + predicate.id + ", " + object.id);
	var subjectNode:MyNode = getInstanceNode(subject.id, subject);
	if (!graph.hasNode(subjectNode.id)) {
		showNode(subjectNode);
	}
	
	var predicateNode:RelationNode = getRelationNode(_r.id, _r); // new RelationNode(_r.id, _r);	//important: _r.id and not _r.predicate.id!!
	if (!graph.hasNode(predicateNode.id)) {
		showNode(predicateNode);
	}
	
	var objectNode:MyNode = getInstanceNode(object.id, object);
	if (!graph.hasNode(objectNode.id)) {
		showNode(objectNode);
	}
	
	addRelationToGraph(subjectNode, predicateNode, objectNode, layout);
}

private function addNodeToGraph(node:MyNode):void {	//TODO: relations need to be added too!
	//trace(">>> add node to graph: " + node.id);
	graph.add(node);
	node.element.isVisible = true;
	//setCurrentItem(node);
}

public function hideNode(node:MyNode):void {
	//trace("hideNode " + node.id);
	if (graph.hasNode(node.id)) {	//if part of the graph
		removeNodeFromGraph(node);
	}
}

public function showNode(node:MyNode):void {
	trace("---- showNode: " + node.id);
	//TODO: Relationen wieder aufbauen!
	addNodeToGraph(node);
}

private function removeNodeFromGraph(node:MyNode):void {	//TODO: the whole connection must be removed too! And the relation!
	trace("Remove node from graph: " + node.id);
	node.element.isVisible = false;
	graph.remove(node);
	//sGraph.removeFromHistory(node);
	
	//setCurrentItem(null);
}

private function addRelationToGraph(subjectNode:MyNode, predicateNode:MyNode, objectNode:MyNode, layout:Object = null):void {
	
	var object1:Object = new Object();
	object1.startId = subjectNode.id;	//defines the direction of the link!
	if (layout != null) object1.settings = layout.settings;
	graph.link(subjectNode, predicateNode, object1);
	
	var object2:Object = new Object();
	object2.startId = predicateNode.id;
	if (layout != null) object2.settings = layout.settings;
	graph.link(predicateNode, objectNode, object2);
	
	//setCurrentItem(objectNode);
	//setCurrentItem(predicateNode);
	//setCurrentItem(subjectNode);
}

public function getRelation(_subject:Element, _predicate:Element, _object:Element):Relation {
	var relId:String = _subject.id + _predicate.id + _object.id; //_subject.label.toLowerCase() + _predicate.label.toLowerCase() + _object.label.toLowerCase();
	if (!relations.containsKey(relId)) {
		var rT:RelType = getRelType(_predicate.id, _predicate.label);
		var newRel:Relation = new Relation(relId, _subject, _predicate, _object, rT);
		
		relations.insert(relId, newRel);
		
		//toDrawRelations.enqueue(newRel);
	}
	return relations.find(relId);
}

public function getElement(_id:String, _resourceURI:String, _label:String, isPredicate:Boolean = false, _abstract:Dictionary = null, _imageURL:String = "", _linkToWikipedia:String = ""):Element {
	
	//WARNING: This is just a workaround!! It should get index by its id instead of by its label!!
	if (!elements.containsKey(_label.toLowerCase())) {	//_id
		var e:Element = new Element(_label.toLowerCase()/*_id*/, _resourceURI, _label, isPredicate, _abstract, _imageURL, _linkToWikipedia);
		
		elements.insert(_label.toLowerCase()/*_id*/, e);
	}
	return elements.find(_label.toLowerCase()/*_id*/);
}

public function getPath(pathId:String, pathRelations:Array):Path {
	if (!_paths.containsKey(pathId)) {
		var pL:PathLength = getPathLength(pathRelations.length.toString(), pathRelations.length - 1);
		var newPath:Path = new Path(pathId, pathRelations, pL);
		_paths.insert(pathId, newPath);
		/*if (_maxPathLength < newPath.pathLength.num) {
			maxPathLength = newPath.pathLength.num;
		}*/
		
		if (!_graphIsFull) {
			//if (selectedMaxPathLength < newPath.pathLength.num) {
				if (_paths.size > 7) {
					trace("graph is full!!!");
					_graphIsFull = true;
				}else {
					//pathLengthRangeChanged(_selectedMinPathLength, newPath.pathLength.num);	//update slider
					//selectedMaxPathLength = newPath.pathLength;	
				}
			//}
		}
		
		
	}
	return _paths.find(pathId);
}

/*public function set maxPathLength(m:int):void {
	_maxPathLength =m;
	//pathLengthRange.maximum = _maxPathLength;
	//var a:Array = new Array();
	//for (var i:int = 0; i <= _maxPathLength; i++) {
	//	a.push(i);
	//}
	//pathLengthRange.labels = a;
	//dispatchEvent(new Event("maxPathLengthChange"));
}*/

/*public function setCurrentItem(_i:Item):void {
	//trace("set current item " + _i.id);
	//sGraph.currentItem = _i;
}*/

[Bindable]
public function get selectedElement():Element {
	return _selectedElement;
}

public function set selectedElement(e:Element):void {
	//trace("setSelectedE");
	//delayedDrawing = false;	//because user interaction!
	
	if (e == null) {
		_selectedElement = null;
		selectedConcept = null;
	}else if ((_selectedElement == null) || (e != null && _selectedElement != null && _selectedElement.id != null && e.id != null && _selectedElement.id != e.id)) {
		_selectedElement = e;
		selectedConcept = _selectedElement.concept;
		var iter:Iterator = _paths.getIterator();
		while (iter.hasNext()) {
			var p1:Path = iter.next();
			p1.isHighlighted = false;
		}
		if (foundNodes.containsKey(e.id)) {	//only for found nodes
			
			for each(var r:Relation in _selectedElement.relations) {
				for each(var p:Path in r.paths) {
					if (p.isVisible) {
						p.isHighlighted = true;
					}
				}
			}
		}
	}else {
		//trace("else");
		for each(var r2:Relation in _selectedElement.relations) {
			for each(var p2:Path in r2.paths) {
				p2.isHighlighted = false;
			}
		}
	}
}

private function clear():void {
	trace("clear");
	
	ConnectionModel.getInstance().lastClear = new Date();
	
	//TODO: clear slider, clear input fields
	
	//TODO: Stop SPARQL queries, clear all the connection stuff! 
	//(resultParser as SPARQLResultParser).clear();
	
	/**
	 * REMOVE ALL LISTENER ----------------
	 */
	var iter:Iterator = _paths.getIterator();
	while (iter.hasNext()) {
		var p:Path = iter.next();
		p.removeListener();
	}
	
	var iter2:Iterator = relations.getIterator();
	while (iter2.hasNext()) {
		var r:Relation = iter2.next();
		r.removeListener();
	}
	
	var iter4:Iterator = elements.getIterator();
	while (iter4.hasNext()) {
		var e:Element = iter4.next();
		e.removeListener();
	}
	
	for each(var c:Concept in _concepts) {
		c.removeListener();
	}
	
	for each(var pL:PathLength in _pathLengths) {
		pL.removeListener();
	}
	
	for each(var rT:RelType in _relTypes) {
		rT.removeListener();
	}
	
	/**
	 * RESET VARIABLES -----------------------
	 */
	graph = new Graph();
	selectedElement = null;
	_selectedConcept = null;
	_selectedPathLength = null;
	_selectedRelType = null;
	_graphIsFull = false;	//whether the graph is overcluttered already!
	_delayedDrawing = true;
	
	_relationNodes = new HashMap();
	foundNodes = new HashMap();
	givenNodes = new HashMap();

	toDrawPaths = new ArrayedQueue(1000);
	timer.stop();
	timer.delay = 2000;
	StatusModel.getInstance().queueIsEmpty = true;
	
	//trace("before",_paths.size);
	_pathLengths = new ArrayCollection();
	_paths = new HashMap();
	//trace("after", _paths.size);
	_relTypes = new ArrayCollection();
	relations = new HashMap();
	_concepts = new ArrayCollection();
	elements = new HashMap();
	
	//_maxPathLength = 0;
	//_selectedMinPathLength = 0;
	//_selectedMaxPathLength = 0;
	
	
	
	myConnection = new SPARQLConnection();
	
	StatusModel.getInstance().clear();
	
	Languages.getInstance().clear();
	
	_selectedElement = null;	//so ist es besser!
	
	sparqlEndpoint = "";
	basicGraph = "";
	resultParser = new SPARQLResultParser();
//	myLookUp = new LookUpSPARQL();

	inputFields = new ArrayCollection(new Array(new String("input0"), new String("input1")));
	autoCompleteList = new ArrayCollection();
	
	//pathLengthRange.thumbCount = 2;
	//pathLengthRange.values = [0, 0];
	//pathLengthRange.minimum = 0;
	//pathLengthRange.maximum = 1;
	
	
	trace("check clear!!");
	trace("graph: " + graph.nodeCount);
	trace("paths: " + _paths.size);
}

//--Expert-Settings + Info-------------------------------------

private var _settingsButton:Object;

[Embed(source="../assets/img/16-tool.png")]
private var _settingsButtonIcon:Class;

private var _infosButton:Object;

[Embed(source="../assets/img/16-info.png")]
private var _infosButtonIcon:Class;

private var _clearButton:Object;

[Embed(source="../assets/img/Clear.png")]
private var _clearButtonIcon:Class;

private var _urlButton:Object;

[Embed(source="../assets/img/16-url.png")]
private var _urlButtonIcon:Class;

private function getButtons():ArrayCollection {
	
	var btns:ArrayCollection = new ArrayCollection();
	
	if (_settingsButton == null) {
		_settingsButton = new Object();
		_settingsButton.toolTip = "Settings";
		_settingsButton.name = "settings";
		_settingsButton.icon = _settingsButtonIcon;
		_settingsButton.clickHandler = settingsClickHandler;
	}
	btns.addItem(_settingsButton);
	if (_infosButton == null) {
		_infosButton = new Object();
		_infosButton.toolTip = "Infos";
		_infosButton.name = "infos";
		_infosButton.icon = _infosButtonIcon;
		_infosButton.clickHandler = infosClickHandler;
	}
	btns.addItem(_infosButton);
	
	if (_clearButton == null) {
		_clearButton = new Object();
		_clearButton.toolTip = "Clear";
		_clearButton.name = "clear";
		_clearButton.icon = _clearButtonIcon;
		_clearButton.clickHandler = clearClickHandler;
	}
	btns.addItem(_clearButton);
	
	if (_urlButton == null) {
		_urlButton = new Object();
		_urlButton.toolTip = "Get URL for current search";
		_urlButton.name = "url";
		_urlButton.icon = _urlButtonIcon;
		_urlButton.clickHandler = urlClickHandler;
	}
	btns.addItem(_urlButton);

	return btns;
}

private function urlClickHandler(event:MouseEvent):void{
	var url:String = inputToURL();
	Clipboard.generalClipboard.clear();
	Clipboard.generalClipboard.setData(ClipboardFormats.TEXT_FORMAT, url);
	Alert.show(url, "This URL has been saved to your clipboard");
}

private function clearClickHandler(event:MouseEvent):void {
	clear();
}

private function settingsClickHandler(event:MouseEvent):void {
	var pop:ExpertSettings = PopUpManager.createPopUp(this, ExpertSettings) as ExpertSettings;
}

private function infosClickHandler(event:MouseEvent):void {
	var pop:Infos = PopUpManager.createPopUp(this, Infos) as Infos;
}

//--AutoComplete----------------------------------------
/*private function showExamples():void {
	//var pop:Examples = PopUpManager.createPopUp(this, Examples) as Examples;
	
	
}*/

[Bindable]
private var _examples:ArrayCollection = new ArrayCollection();

private function fillExamples():void {
	//example1
	var ex1:Object = new Object();
	
	var o11:Object = new Object();
	o11.label = "Albert Einstein";
	ex1.o1Lab = "Albert Einstein";
	o11.uris = new Array("http://dbpedia.org/resource/Albert_Einstein");
	var o21:Object = new Object();
	o21.label = "Kurt Gödel";
	ex1.o2Lab = "Kurt Gödel";
	o21.uris = new Array("http://dbpedia.org/resource/Kurt_G%C3%B6del");
	var ep1:Object = new Object();
	ep1.label = "DBpedia";
	ex1.epLab = "DBpedia";
	ep1.uri = "http://dbpedia.org/sparql";
	
	ex1.o1 = o11;
	ex1.o2 = o21;
	ex1.ep = ep1;
	
	_examples.addItem(ex1);
	
	//example2
	var ex2:Object = new Object();
	
	var o12:Object = new Object();
	o12.label = "Albert Einstein";
	ex2.o1Lab = "Albert Einstein";
	o12.uris = new Array("http://dbpedia.org/resource/Albert_Einstein");
	var o22:Object = new Object();
	o22.label = "Stuttgart";
	ex2.o2Lab = "Stuttgart";
	o22.uris = new Array("http://dbpedia.org/resource/Stuttgart");
	var ep2:Object = new Object();
	ep2.label = "DBpedia";
	ex2.epLab = "DBpedia";
	ep2.uri = "http://dbpedia.org/sparql";
	
	ex2.o1 = o12;
	ex2.o2 = o22;
	ex2.ep = ep2;
	
	_examples.addItem(ex2);
	
	//example3
	var ex3:Object = new Object();
	
	var o13:Object = new Object();
	o13.label = "Leipzig";
	ex3.o1Lab = "Leipzig";
	o13.uris = new Array("http://dbpedia.org/resource/Leipzig");
	var o23:Object = new Object();
	o23.label = "Berlin";
	ex3.o2Lab = "Berlin";
	o23.uris = new Array("http://dbpedia.org/resource/Berlin");
	var ep3:Object = new Object();
	ep3.label = "DBpedia";
	ex3.epLab = "DBpedia";
	ep3.uri = "http://dbpedia.org/sparql";
	
	ex3.o1 = o13;
	ex3.o2 = o23;
	ex3.ep = ep3;
	
	_examples.addItem(ex3);
	
	//example4
	var ex4:Object = new Object();
	
	var o14:Object = new Object();
	o14.label = "Duisburg";
	ex4.o1Lab = "Duisburg";
	o14.uris = new Array("http://dbpedia.org/resource/Duisburg");
	var o24:Object = new Object();
	o24.label = "Essen";
	ex4.o2Lab = "Essen";
	o24.uris = new Array("http://dbpedia.org/resource/Essen");
	var ep4:Object = new Object();
	ep4.label = "DBpedia";
	ex4.epLab = "DBpedia";
	ep4.uri = "http://dbpedia.org/sparql";
	
	ex4.o1 = o14;
	ex4.o2 = o24;
	ex4.ep = ep4;
	
	_examples.addItem(ex4);
	
	//example5
	var ex5:Object = new Object();
	
	var o15:Object = new Object();
	o15.label = "Kill Bill";
	ex5.o1Lab = "Kill Bill";
	o15.uris = new Array("http://data.linkedmdb.org/resource/film/716");
	var o25:Object = new Object();
	o25.label = "Pulp Fiction";
	ex5.o2Lab = "Pulp Fiction";
	o25.uris = new Array("http://data.linkedmdb.org/resource/film/77");
	var ep5:Object = new Object();
	ep5.label = "LinkedMDB";
	ex5.epLab = "LinkedMDB";
	ep5.uri = "http://data.linkedmdb.org";
	
	ex5.o1 = o15;
	ex5.o2 = o25;
	ex5.ep = ep5;
	
	_examples.addItem(ex5);
}

private function loadExample(o1:Object, o2:Object, ep:Object):void {
	
	var searchPossible:Boolean = true;
	
	if (ConnectionModel.getInstance().sparqlConfig.endpointURI.toString() != ep.uri.toString()) {
		var conf:IConfig = ConnectionModel.getInstance().getSPARQLByEndpointURI(ep.uri.toString());
		if (conf != null) {
			Alert.show("Your selected Endpoint was set to \"" + conf.name + "\".\nYou can change back the endpoint to \"" + ConnectionModel.getInstance().sparqlConfig.name + "\" in the settings menu.", "Endpoint changed", Alert.OK + Alert.NONMODAL);
			ConnectionModel.getInstance().sparqlConfig = conf;
		}else {
			searchPossible = false;
			Alert.show("The desired endpoint \"" + ep.uri + "\" was not specified in the configuration file.", "Endpoint not specified", Alert.OK);
		}
	}
	
	if (searchPossible) {
		clear();
		tn.selectedChild = tab1;	//set current tab
		(inputField[0] as AutoComplete).selectedItem = o1;
		(inputField[1] as AutoComplete).selectedItem = o2;
		
		(inputField[0] as AutoComplete).validateNow();
		(inputField[1] as AutoComplete).validateNow();
		
		findRelations();
	}

}

private function autoDisambiguate(ac:AutoComplete):Boolean {
	var input:String = ac.searchText;
	var dp:ArrayCollection = ac.dataProvider;
	
	trace("auto disambiguate: " + input);
	trace("searching for direct match");
	//for each (var obj:Object in dp) {
		//if ((StringUtil.trim(obj.label)).toLowerCase() == (StringUtil.trim(input)).toLowerCase()) {
			//ac.selectedItem = obj;
			//ac.validateNow();
			//trace("direct match found");
			//return true;
		//}
	//}
	// directly match only the first element
	if (dp.length > 0) {
		if ((StringUtil.trim(dp.getItemAt(0).label)).toLowerCase() == (StringUtil.trim(input)).toLowerCase()) {
			ac.selectedItem = dp.getItemAt(0);
			ac.validateNow();
			trace("direct match found");
			return true;
		}
	}
	trace("no direct match found");
	
	// results of this method weren't really satisfying, so it was disabled
	// enabled again with a higher ratio
	trace("checking count");
	if (dp.length >= 2) {
		var o1:Object = dp.getItemAt(0);
		var o2:Object = dp.getItemAt(1);
		
		if (o1 != null && o1.hasOwnProperty("count") && o2 != null && o2.hasOwnProperty("count")) {
			 //if count of o1 is much higher than count of o2, take o1 as selected item
			if (o1.count / o2.count > 20) {
				ac.selectedItem = o1;
				ac.validateNow();
				trace("disambiguated by count. relation between 1st and 2nd item = " + o1.count / o2.count + " 1st item will be taken as selected object");
				return true;
			}else {
				trace("no disambiguation by count. relation between 1st and 2nd item to low = " + o1.count / o2.count);
				return false;
			}
		}
	}
	trace("no auto disambiguation possible");
	
	return false;
}

private function findRelations():void {
	
	if (!isInputValid()) {
		for (var j:int = 0; j < inputFieldRepeater.dataProvider.length; j++) {
			if (!((inputField[j] as AutoComplete).selectedItem && (inputField[j] as AutoComplete).selectedItem.hasOwnProperty('uris'))) {
				
				var select:Object = getInputFromAC(j);
				
				if (select != null) {
					(inputField[j] as AutoComplete).selectedItem = select;
					(inputField[j] as AutoComplete).validateNow();
				}else {
					
					var success:Boolean = autoDisambiguate(inputField[j] as AutoComplete);
					
					if (!success) {
						var pop:InputSelection = PopUpManager.createPopUp(findRelationButton, InputSelection) as InputSelection;
						pop.inputIndex = j;
						pop.dataProvider = (inputField[j] as AutoComplete).dataProvider;
						pop.inputText = (inputField[j] as AutoComplete).searchText;
						pop.addEventListener(InputSelectionEvent.INPUTSELECTION, inputSelectionWindowHandler);
						break;
					}
				}
			}
		}
	}
	
	if (isInputValid()) {
		
		_showOptions = true; 	//sets the filters visible
		
		if (isInputUnique()) {
			var betArr:Array = new Array();
			
			lastInputs = new Array();
			
			for (var i:int = 0; i < inputFieldRepeater.dataProvider.length; i++) {
				if ((inputField[i] as AutoComplete).selectedItem.hasOwnProperty("tempUri") && (inputField[i] as AutoComplete).selectedItem.tempUri != null) {
					
					var o1:Object = new Object();
					o1.label = (inputField[i] as AutoComplete).selectedItem.label;
					o1.uri = (inputField[i] as AutoComplete).selectedItem.tempUri;
					lastInputs.push(o1);
					
					betArr.push((inputField[i] as AutoComplete).selectedItem.tempUri);
					(inputField[i] as AutoComplete).selectedItem.tempUri = null;
				}else {
					
					var o2:Object = new Object();
					o2.label = (inputField[i] as AutoComplete).selectedItem.label;
					o2.uri = ((inputField[i] as AutoComplete).selectedItem.uris as Array)[0];
					lastInputs.push(o2);
					
					betArr.push(((inputField[i] as AutoComplete).selectedItem.uris as Array)[0]);
				}
			}
			
			var between:ArrayCollection = new ArrayCollection(betArr);
			
			myConnection.findRelations(between, 10, 3, resultParser);
			
			delayedDrawing = true;
			
		}else {
			// disambiguate
			for (var k:int = 0; k < inputFieldRepeater.dataProvider.length; k++) {
				// no tempURI
				if (!((inputField[k] as AutoComplete).selectedItem.hasOwnProperty("tempUri") && (inputField[i] as AutoComplete).selectedItem.tempUri != null)) {
					// several URIs
					if (!((inputField[k] as AutoComplete).selectedItem && (inputField[k] as AutoComplete).selectedItem.hasOwnProperty('uris') && ((inputField[k] as AutoComplete).selectedItem.uris as Array).length == 1)) {
						var disambiguation:InputDisambiguation = PopUpManager.createPopUp(findRelationButton, InputDisambiguation) as InputDisambiguation;
						disambiguation.inputIndex = k;
						disambiguation.inputItem = (inputField[k] as AutoComplete).selectedItem;
						disambiguation.addEventListener("Disambiguation", inputDisambiguationWindowHandler);
						break;
					}
				}
			}
		}
	}
}

private function getInputFromAC(acIndex:int):Object {
	for each (var o:Object in (inputField[acIndex] as AutoComplete).dataProvider) {
		
		if (o.hasOwnProperty("label") && o.hasOwnProperty("uri") && (inputField[acIndex] as AutoComplete) != null &&
				o.label.toString().toLowerCase() == (inputField[acIndex] as AutoComplete).searchText.toString().toLowerCase()) {
			return o;
		}
	}
	return null;
}

private function inputDisambiguationWindowHandler(event:Event):void {
	findRelations();
}

private function inputSelectionWindowHandler(event:InputSelectionEvent):void {
	(inputField[event.autoCompleteIndex] as AutoComplete).selectedItem = event.selectedItem;
	(inputField[event.autoCompleteIndex] as AutoComplete).validateNow();
	findRelations();
}

private function isInputUnique():Boolean {
	var unique:Boolean = true;
	
	for (var i:int = 0; i < inputFieldRepeater.dataProvider.length; i++) {
		unique = (unique && (inputField[i] as AutoComplete).selectedItem && (inputField[i] as AutoComplete).selectedItem.hasOwnProperty('uris') && ((inputField[i] as AutoComplete).selectedItem.uris as Array).length == 1)
			|| (unique && (inputField[i] as AutoComplete).selectedItem && (inputField[i] as AutoComplete).selectedItem.hasOwnProperty('tempUri') && (inputField[i] as AutoComplete).selectedItem.tempUri != null);
	}
	
	return unique;
}

private function isInputValid():Boolean {
	var valid:Boolean = true;
	
	for (var i:int = 0; i < inputFieldRepeater.dataProvider.length; i++) {
		valid = valid && (inputField[i] as AutoComplete).selectedItem && (inputField[i] as AutoComplete).selectedItem.hasOwnProperty('uris');
	}
	
	return valid;
}

private function findRelationXMLResultHandler(event:ResultEvent, resources:ArrayCollection):void {
	var result:XML = new XML(event.result);
	//trace(result);
}

private function replaceWhitspaces(str:String):String {
	return str.split(" ").join("_");
}

private function findAutoComplete(_typedText:String, target:AutoComplete):void {
	ConnectionModel.getInstance().sparqlConfig.lookUp.run(_typedText, target);
}

public function setAutoCompleteList(_list:ArrayCollection):void {
	autoCompleteList = _list;
}

// when an item is selected or de-selelcted
private function handleAutoCompleteChange(_selectedItem:Object):void {
//	//trace("handleAutoCompleteChange");
	if (_selectedItem != null && _selectedItem.hasOwnProperty( "label" )){
		//trace(_selectedItem.label);
	}
}

// when the text in the search field is changed
private function handleAutoCompleteSearchChange(_selectedItem:Object):void {
	//trace("handleAutoCompleteSearchChange");
	if (_selectedItem != null && _selectedItem.hasOwnProperty( "searchText" )){
		var input:String = _selectedItem.searchText;
		trace(input);
		//Workaround Case-Sensitivity
		if (input.length == 1 && input.charAt() == input.charAt().toLowerCase()) {
			input = input.toUpperCase();
			if (input != _selectedItem.searchText) {
				_selectedItem.searchText = input;
			}
		}
		
		if (input != null && input.length >= 2) {
			var results:ArrayCollection = new ArrayCollection();
			var searching:Object = new Object();
			searching.label = "Searching...";
			results.addItem(searching);
			_selectedItem.dataProvider = results;
			_selectedItem.validateNow();
			findAutoComplete(input, _selectedItem as AutoComplete);
		}
	}
}


//--Delayed Drawing----------------------
private var timer:Timer = new Timer(2000);
public function startDrawing():void {
	//timer = new Timer(2000, results.length);
	if (!timer.running) {
		timer.addEventListener(TimerEvent.TIMER, drawNextPath);
		//trace("start timer");
		timer.start();
		StatusModel.getInstance().queueIsEmpty = false;
		//trace("timer start");
	}
}

/**
 * Only called by timer
 * @param	event
 */
private function drawNextPath(event:Event):void {
	if (toDrawPaths.isEmpty()) {
		timer.stop();
		StatusModel.getInstance().queueIsEmpty = true;	//TODO: direkt an toDrawPaths.isEmpty mit EventListener binden!
		//trace("timer stop");
	}else {
		
		var p:Path = toDrawPaths.dequeue();
		if (!p.isVisible) {	//if it is not visible, try the next one
			drawNextPath(null);
		}else {
			for each(var r:Relation in p.relations) {
				drawRelation(r, p.layout);
			}
		}
		
	}
}

[Bindable(event="delayedDrawingChanged")]
public function get delayedDrawing():Boolean {
	return _delayedDrawing;
}

public function set delayedDrawing(b:Boolean):void {
	if (_delayedDrawing != b) {
		_delayedDrawing = b;
		
		if (_delayedDrawing) {
			timer.delay = 2000;
		}else {
			timer.delay = 100;	//make the drawing fast!
		}
		
		dispatchEvent(new Event("delayedDrawingChanged"));
		
		/*timer.stop();
		StatusModel.getInstance().queueIsEmpty = true;
		while (!toDrawPaths.isEmpty()) {	//dump all!!
			var p:Path = toDrawPaths.dequeue();
			for each(var r:Relation in p.relations) {
				drawRelation(r, p.layout);
			}
		}
		toDrawPaths.clear();*/
	}
}

public function emptyToDrawPaths():void {
	 //toDrawPaths.clear();
}
