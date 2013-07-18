/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package graphElements {
	
	import connection.SPARQLConnection;
	import connection.SPARQLResultEvent;
	import de.polygonal.ds.HashMap;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.utils.Dictionary;
	import global.Languages;
	import graphElements.events.PropertyChangedEvent;
	import mx.events.CollectionEvent;
	import mx.rpc.events.FaultEvent;
	import mx.rpc.events.ResultEvent;
	import mx.rpc.http.HTTPService;
	import mx.core.Application;
	import mx.controls.Alert;
	import mx.collections.ArrayCollection;
	import mx.utils.URLUtil;
	
	public class Element extends EventDispatcher{
		
		private var _id:String;
		private var _resourceURI:String;
		private var _isPredicate:Boolean = false;
		
		private var _lang:String = "en";
		private var _defaultLang:String = "en";
		private var _resource:String = "res";
		
		private var _label:String = "";
		private var _rdfLabel:Dictionary;
		
		private var _abstract:Dictionary;
		private var _loadAbstact:Boolean = true;
		
		private var _comment:Dictionary;
		
		private var _imageURL:String = "";
		private var _loadImageURL:Boolean = true;
		
		private var _pages:ArrayCollection;
		private var _linkToWikipedia:String = "";
		private var _loadLinkToWikipedia:Boolean = true;
		
		private var _loading:Boolean = false;
		
		private var rdfURL:String = "";
		private var rdf:XML;
		
		private var dbppropNS:Namespace = new Namespace("http://dbpedia.org/property/");
		private var xmlns:Namespace = new Namespace("http://www.w3.org/XML/1998/namespace");
		private var rdfNS:Namespace = new Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#");
		private var rdfsNS:Namespace = new Namespace("http://www.w3.org/2000/01/rdf-schema#");
		private var foafNS:Namespace = new Namespace("http://xmlns.com/foaf/0.1/");
		
		private var _relations:Array = new Array();
		
		private var _isVisible:Boolean = false;
		
		
		//private var _concepts:ArrayCollection = new ArrayCollection();
			
		private var _concept:Concept = null;
		//private var _conceptURI:String = "";
		//private var _conceptLabel:String = "";
		
		public static var VCHANGE:String = "isVisibleChange";
		public static var CONCEPTCHANGE:String = "conceptChange";
		//public static var NEWRCHANGE:String = "newRestrictionChange";
		private var _isGiven:Boolean = false;	//whether this is given by the user or found via dbpedia
		
		//private var _newRestriction:PropertyChangedEvent = null;
		
		/**
		 * 
		 * @param	_id
		 * @param	_label
		 * @param	_abstract	short text to describe the information that is represented by the element (dbpprop:abstract in English)
		 * @param	_imageURL	one URL to an image that illustrates the information that is represented by the element (foaf:img)
		 * @param	_linkToWikipedia	the link to the corresponding articel on wikipedia.org
		 */
		public function Element(_id:String, _resourceURI:String, _label:String, isPredicate:Boolean = false, abstract:Dictionary = null, _imageURL:String = "", _linkToWikipedia:String = "", comment:Dictionary = null, pages:ArrayCollection = null/*, concept:Concept = null*/) {
			
			this._abstract = new Dictionary();
			this._comment = new Dictionary();
			this._rdfLabel = new Dictionary();
			
			this.pages = new ArrayCollection();
			
			this._id = _id;
			
			this._resourceURI = _resourceURI;
			dispatchEvent(new Event("resourceURIChange"));
			
			this._label = _label;
			this._isPredicate = isPredicate;
			
			addRDFLabel(_label, _defaultLang);
			addRDFLabel(_resourceURI, _resource);
			
			if (abstract != null) {
				this._abstract = abstract;
				this._loadAbstact = false;
			}
			
			if (comment != null) {
				this._comment = comment;
			}
			
			if (pages != null) {
				this._pages = pages;
			}
			this._pages.addEventListener(CollectionEvent.COLLECTION_CHANGE, dispatchPagesChange);
			
			if (_imageURL != "") {
				this.imageURL = _imageURL;
				this._loadImageURL = false;
			}else {
				this.imageURL = "loading image for " + this._label;
			}
			
			if (_linkToWikipedia != "") {
				this.linkToWikipedia = _linkToWikipedia;
				this._loadLinkToWikipedia = false;
			}else {
				this.linkToWikipedia = "loading link for " + this._label;
			}
			
			Languages.getInstance().addEventListener("eventSelectedLanguageChanged", selectedLanguageChangedHandler);
			
			loadInfos();
			//this._concept = concept;
		}
		
		public function removeListener():void {
			for each(var r:Relation in _relations) {
				r.removeEventListener(Relation.VCHANGE, relationVChangeHandler);
			}
			//TODO more
		}
		
		private function dispatchPagesChange(e:Event):void {
			dispatchEvent(new Event("pagesChange"));
		}

		private function selectedLanguageChangedHandler(event:Event):void {
			_lang = Languages.getInstance().selectedLanguage;
			
			dispatchEvent(new Event("abstractChange"));
			dispatchEvent(new Event("linkToWikipediaChange"));
			dispatchEvent(new Event("htmlLinkToWikipediaChange"));
			dispatchEvent(new Event("rdfLabelChange"));
		}
		
		[Bindable(event="isLoadingChange")]
		public function get isLoading():Boolean {
			return _loading;
		}
		
		public function set isLoading(value:Boolean):void {
			_loading = value;
			dispatchEvent(new Event("isLoadingChange"));
		}
		
		public function get id():String {
			return _id;
		}
		
		[Bindable(event="labelChange")]
		public function get label():String {
			return _label;
		}
		
		public function get isPredicate():Boolean {
			return _isPredicate;
		}
		
		[Bindable(event=Element.VCHANGE)]
		public function get isVisible():Boolean {
			return _isVisible;
		}
		
		public function set isVisible(b:Boolean):void {
			//trace("element.isVisible = " + b);
			if (_isVisible != b) {
				_isVisible = b;
				//dispatchEvent(new Event(Element.VCHANGE));
				dispatchEvent(new PropertyChangedEvent(Element.VCHANGE, this, "isVisible"));
				
				if (!_isPredicate) {
					//trace("is not a predicate: " + id);
					if (_isVisible) {
						//wird über path gesteuert!
					}else {
						//trace("hide elementNode: " + id);
						app().hideNode(app().getInstanceNode(id, this));
					}
				}
			}
		}
		
		/*public function set newRestriction(e:PropertyChangedEvent):void {
			if (_newRestriction != e) {
				_newRestriction = e;
				dispatchEvent(new PropertyChangedEvent(Element.NEWRCHANGE, this, "newRrestrictionChange"));
			}
		}*/
		
		public function set isGiven(b:Boolean):void {
			_isGiven = b;
		}
		
		public function addRDFLabel(value:String, languageCode:String = "en"):void {
			Languages.getInstance().addLanguageCode(languageCode);
			if (value == null || value == "") {
				_rdfLabel[languageCode] = "no link available for " + this._label;
			}else {
				_rdfLabel[languageCode] = value;
			}
			
			dispatchEvent(new Event("rdfLabelChange", true));
			dispatchEvent(new Event("htmlLinkToWikipediaChange"));
		}
		
		[Bindable(event="rdfLabelChange")]
		public function get rdfLabel():String {
			if (_loadLinkToWikipedia) {
				loadInfos();
			}
			
			if (_rdfLabel.hasOwnProperty(_lang)) {
				return _rdfLabel[_lang];
			}else if (_rdfLabel.hasOwnProperty(_defaultLang)) {
				return _rdfLabel[_defaultLang];
			}
			
			return "no link available";
		}
		
		[Bindable(event="resourceURIChange")]
		public function get uriLink():String {
			return "<font color='#0000FF'><u><a href='event:" + _resourceURI + "'>" + URLUtil.getServerName(_resourceURI) + "</a></u></font>";
		}
		
		[Bindable(event="resourceURIChange")]
		public function get resourceURI():String {
			return _resourceURI;
		}
		
		
		[Bindable(event="htmlLinkToWikipediaChange")]
		public function get htmlLinkToWikipedia():String {
			
			if (_lang != _defaultLang && _abstract.hasOwnProperty(_lang) && linkToWikipedia.toLowerCase().search("wikipedia.org") > 0) {
				var link:String = "http://" + _lang + ".wikipedia.org/wiki/" + encodeURI(rdfLabel);
				return "<font color='#0000FF'><u><a href='event:" + link + "'>" + URLUtil.getServerName(link) + "</a></u></font> (<font color='#0000FF'><u><a href='event:" + linkToWikipedia + "'>" + _defaultLang + "</a></u></font>)";
			}
			
			if (linkToWikipedia.toLowerCase().search("http") != 0) {
				return null;
			}
			
			return "<font color='#0000FF'><u><a href='event:" + linkToWikipedia + "'>" + URLUtil.getServerName(linkToWikipedia) + "</a></u></font>";
		}
		
		[Bindable(event="pagesChanged")]
		public function get pages():ArrayCollection {
			return _pages;
		}
		
		public function set pages(value:ArrayCollection):void {
			_pages = value;
			dispatchEvent(new Event("pagesChanged"));
		}
		
		[Bindable(event="abstractChange")]
		public function get abstract():String {
			if (_loadAbstact) {
				loadInfos();
			}
			
			if (_abstract.hasOwnProperty(_lang)) {
				return _abstract[_lang];
			}else if (_abstract.hasOwnProperty(_defaultLang)) {
				return _abstract[_defaultLang];
			}
			// if no abstract available, try to find a comment
			else if (_comment.hasOwnProperty(_lang)) {
				return _comment[_lang];
			}else if (_comment.hasOwnProperty(_defaultLang)) {
				return _comment[_defaultLang];
			}
			
			return "no abstract available";
		}
		
		public function addAbstract(value:String, languageCode:String = "en"):void {
			Languages.getInstance().addLanguageCode(languageCode);
			
			if (value != null || value != "") {
				
				if (languageCode == "") {
					languageCode = _defaultLang;
				}
				
				_abstract[languageCode] = value;
			}
			
			
			dispatchEvent(new Event("abstractChange"));
		}
		
		public function addComment(value:String, languageCode:String = "en"):void {
			Languages.getInstance().addLanguageCode(languageCode);
			
			if (value != null || value != "") {
				if (languageCode == "") {
					languageCode = _defaultLang;
				}
				
				_comment[languageCode] = value;
			}
			
			dispatchEvent(new Event("abstractChange"));
			dispatchEvent(new Event("commentChange"));
		}
		
		public function set imageURL(value:String):void {
			if (_imageURL != value) {
				if (value == null || value == "") {
					_imageURL = "no image available for " + this._label;
				}else {
					_imageURL = value;
				}
				dispatchEvent(new Event("imageURLChange"));
			}
		}
		
		[Bindable(event="imageURLChange")]
		public function get imageURL():String {
			if (_loadImageURL) {
				loadInfos();
			}
			return _imageURL;
		}
		
		public function set linkToWikipedia(value:String):void {
			if (_linkToWikipedia != value) {
				if (value == null || value == "") {
					_linkToWikipedia = "no link available for " + this._label;
				}else {
					_linkToWikipedia = value;
				}
				dispatchEvent(new Event("linkToWikipediaChange"));
				dispatchEvent(new Event("htmlLinkToWikipediaChange"));
			}
		}
		
		[Bindable(event="linkToWikipediaChange")]
		public function get linkToWikipedia():String {
			if (_loadLinkToWikipedia) {
				loadInfos();
			}
			return _linkToWikipedia;
		}
		
		public function loadInfos():void {
			if (!isLoading && !_isPredicate) {
				isLoading = true;
				//trace("test")
				var sparql:SPARQLConnection = new SPARQLConnection();
				var query:String = "SELECT ?property ?hasValue WHERE { <" +
									_resourceURI + 
									"> ?property ?hasValue }";
				
				sparql.executeSparqlQuery(null, query, sparqlResultHandler, "XML", true, faultHandler);
				
				//new
				/*if (!this._isGiven) {	//given nodes cannot be filtered at all!
					loadClass();
				}*/ // PH: wird jetzt erst in FoundNode.as gemacht! Damit nur für FoundNode Konzepte zugewiesen werden!
			}
		}
		
		private function sparqlResultHandler(e:SPARQLResultEvent):void {
			var resultNS:Namespace = new Namespace("http://www.w3.org/2005/sparql-results#");
			var xmlNS:Namespace = new Namespace("http://www.w3.org/XML/1998/namespace");
			var result:XML = new XML(e.result);
			
			var i:int = 0;
			
			imageURL = "no image available";
			linkToWikipedia = "";
			
			if (result..resultNS::results !== "") {
				for each (var res:XML in result..resultNS::results.resultNS::result) {
					
					// wikipedia link or a similar link
					if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://purl.org/ontology/mo/wikipedia") {
						linkToWikipedia = res.resultNS::binding.(@name == "hasValue").resultNS::uri;
						pages.addItem(res.resultNS::binding.(@name == "hasValue").resultNS::uri);
					}else if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://xmlns.com/foaf/0.1/page") {
						linkToWikipedia = res.resultNS::binding.(@name == "hasValue").resultNS::uri;
						pages.addItem(res.resultNS::binding.(@name == "hasValue").resultNS::uri);
					}
					
					// label or name
					if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://www.w3.org/2000/01/rdf-schema#label") {
						
						var rdfLang:String = res.resultNS::binding.(@name == "hasValue").resultNS::literal.@xmlNS::lang;
						if (rdfLang == null || rdfLang == "") {
							rdfLang = _defaultLang;
						}
						addRDFLabel(res.resultNS::binding.(@name == "hasValue").resultNS::literal, rdfLang);
					}else if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://xmlns.com/foaf/0.1/name") {
						var rdfLang2:String = res.resultNS::binding.(@name == "hasValue").resultNS::literal.@xmlNS::lang;
						if (rdfLang2 == null || rdfLang2 == "") {
							rdfLang2 = _defaultLang;
						}
						addRDFLabel(res.resultNS::binding.(@name == "hasValue").resultNS::literal, rdfLang2);
					}
					
					// abstarct or comment
					if ((res.resultNS::binding.(@name == "property").resultNS::uri).search("abstract") > -1) {
						addAbstract(res.resultNS::binding.(@name == "hasValue").resultNS::literal, res.resultNS::binding.(@name == "hasValue").resultNS::literal.@xmlNS::lang);
					}
					if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://www.w3.org/2000/01/rdf-schema#comment") {
						addComment(res.resultNS::binding.(@name == "hasValue").resultNS::literal, res.resultNS::binding.(@name == "hasValue").resultNS::literal.@xmlNS::lang);
					}
					
					// depiction (image)
					//TODO: extract all image urls (Timo)
					if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://xmlns.com/foaf/0.1/depiction") {
						imageURL = res.resultNS::binding.(@name == "hasValue").resultNS::uri;
					}
					
					/*if ((res.resultNS::binding.(@name == "property").resultNS::uri) == "http://www.w3.org/1999/02/22-rdf-syntax-ns#type") {
						 var conceptURI:String = res.resultNS::binding.(@name == "hasValue").resultNS::uri;
						 //Alert.show(conceptURI);
						 if ((conceptURI != "http://dbpedia.org/ontology/Resource") && (!this._isGiven)){	//given nodes cannot be filtered at all!
							 var cLabel:String =
								conceptURI.replace("http://dbpedia.org/ontology/", "db:")
								.replace("http://dbpedia.org/class/yago/", "yago:")
								.replace("http://sw.opencyc.org/2008/06/10/concept/", "cyc:")
								.replace("http://xmlns.com/foaf/0.1/", "foaf:")
								.replace("http://umbel.org/umbel/sc/", "umb:");
							 
							//temp, please change (Sebastian, Jens):
							if (this.concept == null) {
								var c:Concept = app().getConcept(conceptURI, cLabel);
								//this.addConcept(c);
								//bitte nur ein Konzept!!
								this.concept = c;
							}
							
						}
					}*/
				}
			}
			
			_loadLinkToWikipedia = false;
			_loadAbstact = false;
			_loadImageURL = false;
			
			isLoading = false;
		}
		
		public function loadClass():void {
			if (!_isPredicate) {
				var sparql:SPARQLConnection = new SPARQLConnection();
				var query:String = "SELECT ?class WHERE {\n" + 
									"<" + _resourceURI + "> a ?class . \n"+
									"OPTIONAL { ?subClass <http://www.w3.org/2000/01/rdf-schema#subClassOf> ?class } . \n" + 
									"FILTER (!bound(?subClass)) . \n" +
									//Removed filter, so it will work with other endpoints then dbpedia or lod
									//"FILTER (?class LIKE <http://dbpedia.org/ontology/%>) . \n" +
									"FILTER (?class != <http://dbpedia.org/ontology/Resource>) . \n" +
									"}";
				sparql.executeSparqlQuery(null, query, loadClassResultHandler, "XML", true, faultHandler);
				
			}
		}
		
		private function loadClassResultHandler(e:SPARQLResultEvent):void {
			var resultNS:Namespace = new Namespace("http://www.w3.org/2005/sparql-results#");
			var xmlNS:Namespace = new Namespace("http://www.w3.org/XML/1998/namespace");
			var result:XML = new XML(e.result);
			
			if (result..resultNS::results !== "") {
				for each (var res:XML in result..resultNS::results.resultNS::result) {
					var conceptURI:String = res.resultNS::binding.(@name == "class").resultNS::uri;
					var cLabel:String =
								conceptURI.replace("http://dbpedia.org/ontology/", "db:")
								.replace("http://dbpedia.org/class/yago/", "yago:")
								.replace("http://sw.opencyc.org/2008/06/10/concept/", "cyc:")
								.replace("http://xmlns.com/foaf/0.1/", "foaf:")
								.replace("http://umbel.org/umbel/sc/", "umb:");
					if (this.concept == null) {
						var c:Concept = app().getConcept(conceptURI, cLabel);
						//this.addConcept(c);
						//bitte nur ein Konzept!!
						this.concept = c;
					}
				}
			}
			
			
		}
		
		
		
		private function faultHandler(e:FaultEvent):void {
			isLoading = false;
			//trace((e);
		}
		
		public function getCopy():Element {
			return new Element(this._id, this._resourceURI, this._label, this._isPredicate, this._abstract, this._imageURL, this._linkToWikipedia, this._comment);
		}
		
		public function addRelation(rel:Relation):void {
			this._relations.push(rel);
			//rel.addEventListener(Relation.VCHANGE, relationVChangeHandler);
			rel.addEventListener(Relation.VCHANGE, relationVChangeHandler);
			//rel.addEventListener(Relation.NEWRCHANGE, relationNewRestrictionHandler);
		}
		
		/*private function propertyChangedHandler(event:PropertyChangedEvent):void {
			if (event.origin is Relation) {
				if (event.propery == "isVisible") {
					var r:Relation = event.origin as Relation;
					if (this.isVisible) {
						if (!r.isVisible) {
							if (!oneRelationIsVisible()) {	//if no relation is visible!
								var i:MyNode = app().getInstanceNode(id, this);
								if (i is FoundNode) {	//only if foundNode!!
									isVisible = false;
								}
							}
						}
					}else {
						if (r.isVisible) {
							if ((concept == null) || (concept.isVisible)) {
								isVisible = true;	
							}
						}
					}
				}
			}
		}*/
		
		private function relationVChangeHandler(event:PropertyChangedEvent):void {
			var r:Relation = event.target as Relation;
			if (this.isVisible) {
				if (!r.isVisible) {
					if (!oneRelationIsVisible()) {	//if no relation is visible!
						var i:MyNode = app().getInstanceNode(id, this);
						if (i is FoundNode) {	//only if foundNode!!
							isVisible = false;
						}
					}
				}
			}else {
				if (r.isVisible) {
					if ((concept == null) || (concept.isVisible)) {
						isVisible = true;	
					}
				}else {
					
					//TEST
					//isVisible = false;
				}
			}
		}
		
		/*private function relationNewRestrictionHandler(event:PropertyChangedEvent):void {
			trace("newRestriction in " + this.id);
			var r:Relation = event.target as Relation;
			newRestriction = event;
			if (r.newRestriction.origin is Path) {	//only
				//TODO: Testen ob eigenständige Restriction!! Heißt, bleibt invisible bis diese Restriction wieder aufgehoben!
				
			}
		}*/
		
		public function get relations():Array {
			return this._relations;
		}
		
		public function set concept(c:Concept):void {
			//schwachsinn bei mehreren concepts!!! -> remove former event listener
			//if(_concept != null) _concept.removeEventListener(Concept.VCHANGE, conceptVChangeHandler);
			
			_concept = c;
			_concept.addElement(this);
			dispatchEvent(new PropertyChangedEvent(Element.CONCEPTCHANGE, this, "concept"));
			//dispatchEvent(new Event("conceptChange"));
			//_concept.addEventListener(Concept.VCHANGE, conceptVChangeHandler);
			/*for each(var r:Relation in _relations) {
				_concept.addEventListener(PropertyChangedEvent.PROPERTY_CHANGED, r);
				//_concept.addEventListener(Concept.VCHANGE, r.conceptVChangeHandler);
			}*/
			//_concept.dispatchEvent(new Event(Concept.VCHANGE));	//to inform about its visibility
			_concept.dispatchEvent(new PropertyChangedEvent(Concept.VCHANGE, _concept, "isVisible"));
		}
		
		[Bindable(event=Element.CONCEPTCHANGE)]
		public function get concept():Concept {
			return _concept;
		}
		
		/*[Bindable(event="conceptsChange")]
		public function get concepts():ArrayCollection {
			return _concepts;
		}
		
		public function addConcept(c:Concept):void {
			if (!_concepts.contains(c)) {
				c.addElement(this);
				_concepts.addItem(c);
				dispatchEvent(new Event("conceptsChange"));
				//c.addEventListener(Concept.VCHANGE, conceptVChangeHandler);
				
				for each(var r:Relation in _relations) {
					c.addEventListener(Concept.VCHANGE, r.elementVConceptChangeHandler);
				}
			}
		}
		
		public function oneConceptIsVisible():Boolean {
			for each(var c:Concept in _concepts) {
				if (c.isVisible) {
					return true;
				}
			}
			return false;
		}*/
		
		private function conceptVChangeHandler(event:Event):void {
			//trace("conceptVChange: " + _concept.id + ", v: " + _concept.isVisible);
			if (!_concept.isVisible) {
				if (isVisible) {
					isVisible = false;
				}else {
					//newRestriction = event;
				}
			}else {
				isVisible = true;
				//TODO: wenn aber über die maxLength eigentlich nicht visible!! Backpropagation über path!!
			}
		}
		
		override public function toString():String 
		{
			return "Element " + id;
		}
		
		private function oneRelationIsVisible():Boolean {
			for each(var r:Relation in relations) {
				//trace("path: " + p.id + " v: " + p.isVisible);
				if (r.isVisible) {
					return true;
				}
			}
			//trace(("all relations are invisible!");
			return false;
		}
		
		private function app(): Main {
			return Application.application as Main;
		}
	}
	
}