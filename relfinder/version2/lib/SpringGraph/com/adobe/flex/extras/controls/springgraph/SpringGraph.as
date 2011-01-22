////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.springgraph {
	import mx.core.IFactory;
	import mx.core.ClassFactory;
	import mx.core.UIComponent;
	import mx.core.IDataRenderer;
	import flash.events.MouseEvent;
	import flash.events.TimerEvent;
	import flash.utils.Timer;
	import flash.utils.getTimer;
	import mx.containers.Canvas;
	import mx.core.Container;
	import com.adobe.flex.extras.controls.forcelayout.ForceDirectedLayout;
	import flash.events.Event;
	import mx.effects.Fade;
	import mx.events.EffectEvent;
	import mx.effects.Effect;
	import flash.geom.Rectangle;

	//[Event(name="doubleClick", type="flash.events.Event")]

/**
 *  The SpringGraph component displays a set of objects, using 
 *  a force-directed layout algorithm to position the objects.
 *  Behind the objects, the component draws lines connecting
 *  items that are linked.
 * 
 *  <p>The set of objects, and the links between them, is defined
 *  by this component's dataProvider property. For each Item in the dataProvider, 
 *  there is a corresonding itemRenderer, which is any UIComponent that implements
 *  the IDataRenderer interface. You define these via the itemRenderer or viewFactory
 *  properties. Each itemRenderer's 'data' property is a reference to its corresponding Item.</p>
 * 
 *  <p>SpringGraph does its drawing of lines and items inside the
 *  area that you define as the height and width
 *  of this component.</p>
 * 
 * <p>You can control what links look like, in 4 ways:
 * <br>1. do nothing. The edges will draw in a default width and color
 * <br>2. set 'lineColor'. The edges will draw with that color, in a default width.
 * <br>3. use Graph.link() to add a data object to any particular link. If that
 * data object contains a field called 'settings', then the
 * value of 'settings' should be an object with fields 'color', 'thickness', and 'alpha'. For 
 * example:<br><br>
 *     var data: Object = {settings: {alpha: 0.5, color: 0, thickness: 2}};<br>
 *     g.link(fromItem, toItem, data);<br>
 * <br>4. define an EdgeRenderer (see 'edgeRenderer' below)
 * </p>
 *  <p>This component allows the user to click on items and drag them around.
 * </p>
 * 
 *  <p>This component was written by Mark Shepherd of Adobe Flex Builder Engineering.
 *  The force-directed layout algorithm was translated and adapted to ActionScript 3 from 
 *  Java code written by Alexander Shapiro of TouchGraph, Inc. (http://www.touchgraph.com).
 * </p>
 *
 *  @mxml
 *
 *  <p>The <code>&lt;SpringGraph&gt;</code> tag inherits all the tag attributes
 *  of its superclass, and adds the following tag attributes:</p>
 *
 *  <pre>
 *  &lt;mx:SpringGraph
 *    <b>Properties</b>
 *    dataProvider="null"
 *    itemRenderer="null"
 *    lineColor="0xcccccc"
 *    replusionFactor="0.75"
 *  /&gt;
 *  </pre>
 *
 * @author   Mark Shepherd
 */	
 public class SpringGraph extends Canvas {
		
		public function SpringGraph(): void {
			drawingSurface = new UIComponent();
            this.addEventListener("mouseDown", backgroundMouseDownEvent);

			this.addChild(drawingSurface);
			this.verticalScrollPolicy = "off";
			this.horizontalScrollPolicy = "off";

  			//this.addEventListener("mouseDown", backgroundMouseDownEvent);
			this.addEventListener("mouseUp", dragEnd);
			this.addEventListener("mouseMove", dragContinue);
			this.addEventListener("preinitialize", myPreinitialize);
			this.addEventListener("creationComplete", myCreationComplete);
		}

	    /** @private */
		override protected function updateDisplayList(unscaledWidth:Number, unscaledHeight:Number):void {
   			super.updateDisplayList(unscaledWidth, unscaledHeight);
			
			if((_dataProvider != null) && _dataProvider.layoutChanged) {
				drawEdges();
				_dataProvider.layoutChanged = false;
			}
		}
		
	    /** @private */
		private function drawEdges(): void {
			drawingSurface.graphics.clear();
			var edges: Array = _dataProvider.getEdges();
			for each (var edge: GraphEdge in edges) {
				var fromNode: GraphNode = GraphNode(edge.getFrom());
				var toNode: GraphNode = GraphNode(edge.getTo());
				var color: int = ((fromNode.item == distinguishedItem) || (toNode.item == distinguishedItem))
					? distinguishedLineColor : _lineColor;
				drawEdge(fromNode.view, toNode.view, color);
			}
		}

		private function drawEdge(f: UIComponent, t: UIComponent, color: int): void {
			var fromX: int = f.x + (f.width / 2);
			var fromY: int = f.y + (f.height / 2);
			var toX: int = t.x + (t.width / 2);
			var toY: int = t.y + (t.height / 2);
			if((_edgeRenderer != null) && _edgeRenderer.draw(drawingSurface.graphics, f, t, fromX, fromY, toX, toY, _graph))
				return;
			var fromItem: Item = (f as IDataRenderer).data as Item;
			var toItem: Item = (t as IDataRenderer).data as Item;
			var linkData: Object = _graph.getLinkData(fromItem, toItem);
			var alpha: Number = 1.0;
			var thickness: int = 1;
			if ((linkData != null) && (linkData.hasOwnProperty("settings"))) {
				var settings: Object = linkData.settings;
				alpha = settings.alpha;
				thickness = settings.thickness;
				color = settings.color;
			}

			drawingSurface.graphics.lineStyle(thickness,color,alpha);
			drawingSurface.graphics.beginFill(0);
			drawingSurface.graphics.moveTo(fromX, fromY);
			drawingSurface.graphics.lineTo(toX, toY);
			drawingSurface.graphics.endFill();
		}
 		
 		private function myPreinitialize(event: Object): void {
			var dp: GraphDataProvider = new GraphDataProvider(this);
			_dataProvider = dp;
			forceDirectedLayout = new ForceDirectedLayout(dp);
			refresh();
 		}
 		
 		private function myCreationComplete(event: Object): void {
 			creationIsComplete = true;
 			if(pendingDataProvider != null) {
 				doSetDataProvider(pendingDataProvider);
 				pendingDataProvider = null;
 			}
 			rebuild();
 		}
 		
	    /** @private */
 		public function removeComponent(component: UIComponent): void {
 			//Object(component).removeYourself();
 			if(removeItemEffect != null) {
 				removeItemEffect.addEventListener(EffectEvent.EFFECT_END, removeEffectDone);
	 			removeItemEffect.createInstance(component).startEffect();
	 		} else {
	 			component.parent.removeChild(component);
	 		}
  		}
  		
  		private function removeEffectDone(event: EffectEvent): void {
  			var component: UIComponent = event.effectInstance.target as UIComponent;
  			if(component.parent != null)
				component.parent.removeChild(component);
  		}
		
		/** An effect that is applied to all itemRenderer instances when they
		 * are removed from the spring graph. */ 
		public var removeItemEffect: Effect;
		
		/** An effect that applied to all itemRenderer instances when they
		 * are add to the spring graph. */ 
		public var addItemEffect: Effect;

		/**The XML element and attribute names to use when parsing an XML dataProvider.
		   The array must have 4 elements:
		   <ul>
		   <li> the element name that defines nodes
		   <li> the element name that defines edges
		   <li> the edge attribute name that defines the 'from' node
		   <li> the edge attribute name that defines the 'to' node
		   </ul>
		 */
		public function set xmlNames(array: Array): void {
			_xmlNames = array;
		}
		
	    /** @private */
 		public function addComponent(component: UIComponent): void {
 			//bject(component).addYourself(this);
 			//this.addChild(component);
			//trace("addcomponent: "+ component);
 			this.addChild(component);
 			if(addItemEffect != null) {
 				//addItemEffect.addEventListener(EffectEvent.EFFECT_END, addEffectDone);
	 			addItemEffect.createInstance(component).startEffect();
	 		} else {
	 			//this.addChild(component);
	 		}
			//trace("end");
 		}
 		
 		/** [for experimental use]. The layout computations are stopped when the amount of motion
 		 * falls below this threshold. I don't know what the units are,
 		 * the range of meaningful values is from 0.001 to 2.0 or so. Low 
 		 * numbers mean that the layout takes longer to settle down, but gives
 		 * a better result. High numbers means that the layout will stop
 		 * sooner, but perhaps with not as nice a layout. 
 		 */
 		public function set motionThreshold(t: Number): void {
 			ForceDirectedLayout.motionLimit = t;
 			dispatchEvent(new Event("motionThresholdChange"));
 		}
 		
 		/** The layout computations are stopped when the amount of motion
 		 * falls below this threshold. */
		[Bindable("motionThresholdChange")]
 		public function get motionThreshold(): Number {
 			return ForceDirectedLayout.motionLimit;
 		}
 		
 		/*
  		private function addEffectDone(event: EffectEvent): void {
  			var component: UIComponent = event.effectInstance.target as UIComponent;
  			this.addChild(component);
  		}
  		*/
  		
	    /** @private */
 		public function newComponent(item: Item): UIComponent {
 			var component: UIComponent = createComponent(item);
            component.x = this.width / 2;
            component.y = this.height / 2;
            component.addEventListener("mouseDown", mouseDownEvent);
            //item.addEventListener("doubleClick", doubleClick);
            // double-click event doesn't happen if we are also listening for mouseDown
       	    addComponent(component);
 			return component;
 		}
   	
		/**
		 * ADDED BY PHILIPP HEIM, 20.3.2008
		 * @return _dataProvider
		 */
		public function getDataProvider():Object {
			return _dataProvider;
		}
		
		
   		private function mouseDownEvent(event: MouseEvent):void  {
			//trace("mouseDownEvent");
   			var now: int = getTimer();
   			if((now - lastMouseDownTime) < 300) {
   				// it's a double-click
   				var node: GraphNode = _dataProvider.findNode(UIComponent(event.currentTarget));
   				if(node != null) {
   					dragEnd(event);
   					if(Object(node.view).hasOwnProperty("doubleClick"))
   						Object(node.view).doubleClick(event);	   	
   				}
   				return;
   			}
   			lastMouseDownTime = now;
   			dragBegin(event);
   			event.stopImmediatePropagation();
   		}
 
	    /** @private */
   		protected function dragBegin(event: MouseEvent):void  {
   			dragComponent = UIComponent(event.currentTarget);
   			dragStartX = dragComponent.x;
   			dragStartY = dragComponent.y;
   			dragCursorStartX = event.stageX;
   			dragCursorStartY = event.stageY;
   			forceDirectedLayout.setDragNode(_dataProvider.findNode(dragComponent));
   		}
   	
   		private function dragContinue(event: MouseEvent):void  {
   			if(backgroundDragInProgress) {
   				backgroundDragContinue(event);
   				return;
   			}
   			if(dragComponent == null) return;
   			
   			var deltaX: int = event.stageX - dragCursorStartX;
   			var deltaY: int = event.stageY - dragCursorStartY;
   			dragComponent.x = dragStartX + deltaX;
   			dragComponent.y = dragStartY + deltaY;
			refresh();
   		}
   		
   		private function dragEnd(event: MouseEvent):void  {
   			if(backgroundDragInProgress) {
   				backgroundDragEnd(event);
   				return;
   			}
   			dragComponent = null;
   			forceDirectedLayout.setDragNode(null);
   		}

   		private function backgroundMouseDownEvent(event: MouseEvent):void  {
   			var now: int = getTimer();
   			if((now - lastMouseDownTime) < 300) {
   				// it's a double-click
   				//var node: GraphNode = _dataProvider.findNode(UIComponent(event.currentTarget));
   				//if(node != null) {
   				//	dragEnd(event);
   				//	Object(node.view).doubleClick();	   	
   				//}
   				return;
   			}
   			lastMouseDownTime = now;
   			backgroundDragBegin(event);
   			event.stopImmediatePropagation();
   		}

   		private function backgroundDragBegin(event: MouseEvent):void  {
   			//trace("backgroundDragBegin");
   			backgroundDragInProgress = true;
   			/*
   			dragComponent = UIComponent(event.currentTarget);
   			dragStartX = dragComponent.x;
   			dragStartY = dragComponent.y;
   			*/
   			dragCursorStartX = event.stageX;
   			dragCursorStartY = event.stageY;
   			//forceDirectedLayout.setDragNode(_dataProvider.findNode(dragComponent));
   		}
   	
   		private function backgroundDragContinue(event: MouseEvent):void  {
   			//trace("backgroundDragContinue");
   			/*
   			if(dragComponent == null) return;
   			*/
   			var deltaX: int = event.stageX - dragCursorStartX;
   			var deltaY: int = event.stageY - dragCursorStartY;
  			dragCursorStartX = event.stageX;
   			dragCursorStartY = event.stageY;
   			
   			// apply the delta to all components
   			scroll(deltaX, deltaY);
   	        drawingSurface.invalidateDisplayList();

    		//dragComponent.x = dragStartX + deltaX;
   			//dragComponent.y = dragStartY + deltaY;
			refresh();
   		}
  		
 		/** @private */
  		protected function scroll(deltaX: int, deltaY: int): void {
   			var c: Array = this.getChildren();
   			for (var i: int = 1; i < c.length; i++) {
   				var itemView: Object = c[i];
   				if(itemView != drawingSurface) {
   					itemView.x = itemView.x + deltaX;
   					itemView.y = itemView.y + deltaY;
   				}
   			}
  		}
  		
   		private function backgroundDragEnd(event: MouseEvent):void  {
   			//trace("backgroundDragEnd");
   			backgroundDragInProgress = false;
   			/*
   			dragComponent = null;
   			forceDirectedLayout.setDragNode(null);
   			*/
   		}
 
 		/** @private */
        protected function startTimer():void {
            timer = new Timer(10, 2);
            timer.addEventListener(TimerEvent.TIMER_COMPLETE, tick);
            timer.start();
        }
		
		/** @private */
        protected function tick(event:TimerEvent = null):void {
        	if(_autoFit) {
        		autoFitTick();
        	} else {
				forceDirectedLayout.tick();
        	}
			this.invalidateDisplayList();
			startTimer();
       }

	    /** @private */
        public function get draggedComponent(): UIComponent {
        	var node: GraphNode = GraphNode(forceDirectedLayout.dragNode);
        	if(node == null)
        		return null;
        	return node.view;
        }
		
	    /**
	     *  Redraw everything. Call this when you changed something that
	     * could affect the size of any of the active itemRenderers. There is
	     * no need to call this when the graph data is changed, we update
	     * automatically in that case.
	     */
        public function refresh(): void {
        	if(_dataProvider != null) {
	        	_dataProvider.layoutChanged = true;
	        	if((forceDirectedLayout != null) && _dataProvider.hasNodes/*graph.hasNodes*/) {
		        	forceDirectedLayout.resetDamper();
		        	if(timer == null)
			        	tick();
		        }
	        }
        }

	    /**
	     *  Throw away the dataProvider, leaving an empty graph.
	     */
		public function empty(): void {
        	setDataProvider(new Graph());
        }

	    /**
	     *  Defines the UIComponent class for rendering an item. One instance
	     *  of this class will be created for each item contained in the "dataProvider" property.
	     *  You should specify an itemRenderer if you want every type of Item to have the same kind of view.
	     *  If you want different types of Items to have different views,
	     *  use viewFactory instead.
	     *  
	     *  @default null 
	     */
		public function set itemRenderer(factory: IFactory): void {
			itemRendererFactory = factory;
		}

	    /** @private
	     *  
	     */
 		public function createComponent(item: Item): UIComponent {
 			var result: UIComponent = null;
			

			if(item is HistorySeed) {
				result = new HistorySeedView();
 			} else {
 				if(_viewFactory != null)
	 					result =_viewFactory.getView(item) as UIComponent;
	 			if(result == null) {
	 				if(itemRendererFactory != null)
	 					result = itemRendererFactory.newInstance();
	 				else
	 					result = new DefaultItemView();
	 			}
	 		}
 			if(result is IDataRenderer)
 				(result as IDataRenderer).data = item;
 			return result;
 		}
		
		/*
		private function set distance(d: int): void {
			_dataProvider.distance = d;
			refresh();
		}
		
		private function get distance(): int {
			return _dataProvider.distance;
		}
		*/
		
	    /**
	     *  Defines the data model for this springgraph. The data is 
	     *  a set of items which can be  linked to each other. You can provide the
	     *  data as XML, or as a Graph object. 
	     *  <p>
	     * To use XML, provide an object of type XML with the following format:
	     * <ul>
	     * <li>root element can have any name; attributes are ignored</li>
	     * <li>items are defined by elements whose name is 'Node', which must have a unique 'id' attribute.</li>
	     * <li>links are defined by elements whose name is 'Edge', which must have attributes 'fromID' and 'toID', which
	     * reference the id of the 2 items connect by a link.</li>
	     * <li>you can have any nesting structure you like, we ignore it.</li>
	     * <li>namespaces are not currently supported</li>
	     * </ul>
	     * <p>When the dataProvider is set to XML, we automatically create a Graph that repesents the items and links
	     * in the XML data. Each itemRenderer's 'data' property is set to the Item object whose 'id' 
	     * is the id of an XML Node, and whose 'data' property is the XML object representing the Node.
	     * You can use the xmlNames property to define the names that you have used in your XML data.
	     * The default XML names 'Node', 'Edge', 'fromID', and 'toID'.</p>
	     *  @default null 
	     */
		public function set dataProvider(obj: Object): void {
			setDataProvider(obj);
		}
		
		public function get dataProvider(): Object {
			return _graph;
		}
		
		private function setDataProvider(obj: Object): void {
			if(creationIsComplete) {
				doSetDataProvider(obj);
			} else {
				pendingDataProvider = obj;
			}
		}
		
		private var creationIsComplete: Boolean = false;
		private var pendingDataProvider: Object = null;
		
		private function doSetDataProvider(obj: Object): void {
			if(obj is XML)
				obj = Graph.fromXML(obj as XML, _xmlNames);
			_graph = obj as Graph;
			rebuild();
			_graph.addEventListener(Graph.CHANGE, graphChangeHandler);
		}
		
	    /**
	     *  The color we use to draw the lines that represent links between items.
	     *  
	     *  @default 0xcccccc 
	     */
		public function set lineColor(color: int): void {
			_lineColor = color;
			refresh();
		}
		
	    /**
	     *  How strongly do items push each other away.
	     *  
	     *  @default 0.75 
	     */
		public function set repulsionFactor(factor: Number): void {
			_repulsionFactor = factor;
			refresh();
		}
		
		[Bindable(event="repulsionFactorChanged")]
		public function get repulsionFactor(): Number {
			return _repulsionFactor;
		}
		
		/** @private */
		public function graphChangeHandler(event: Event): void {
			rebuild();
		}
		
		/** A factory that can create views for specific Items. This is an instance of
		 * a class (or component) that implements the IViewFactory interface.
 	     *  You should specify only one of itemRenderer or viewFactory.
		 */
		public function set viewFactory(factory: IViewFactory): void {
			_viewFactory = factory;
		}
		
		/** Defines an Edge Renderer object that we will use to render edges.
		 * If this is null, we use our built-in edge renderer.
		 */
		public function set edgeRenderer(renderer: IEdgeRenderer): void {
			_edgeRenderer = renderer;
		}
		
		/** Enable/disable the auto-fit feature. When enabled, we automatically
		 * and continuously adjust the 'repulsionFactor' property, as well as scroll the
		 * viewing area of the roamer, so that the graph
		 * items are entirely contained within, and nicely spread out over
		 * the entire rectangle of this component. When disabled, we obey whatever
		 * value you set into the 'repulsionFactor' property, and scrolling
		 * must be done manually. When autoFit is enabled, you may still
		 * set repulsionFactor and scroll - the component will smoothly continue
		 * from wherever you left it. */
		public function set autoFit(value: Boolean): void {
			_autoFit = value;
		}
		
		private function rebuild(): void {
			if((_graph != null) && (_dataProvider != null)) {
	   	        _dataProvider.graph = _graph;
				refresh();
			}
		}
		
		/** the implemenation of auto-separation, which runs on every drawing cycle.
		 * The algorithm continuously adjusts repulsionFactor to try and keep the
		 * available screen space filled to about 90%. (FYI, all of the numbers and
		 * coefficients have been hand-tuned for the RoamerDemo sample on my laptop
		 * screen. I can't guarantee they work well in all situations, let me know
		 * if there are problems). (mark s. nov 2006)
		 * @private
		 */
		private function autoFitTick():void {
 			// do a layout pass
			forceDirectedLayout.tick();
			
			// find out the current rect occupied by all items
			var itemBounds: Rectangle = calcItemsBoundingRect();
			//trace("top: " + itemBounds.top + "left, : " + itemBounds.left + "bottom, : " + itemBounds.bottom + "right, : " + itemBounds.right);
			if(itemBounds != null) {
				// find out how much of the available space is currently in use
				var vCoverage: Number = (itemBounds.bottom - itemBounds.top) / this.height;
				var hCoverage: Number = (itemBounds.right - itemBounds.left) / this.width;
				var coverage: Number = Math.max(hCoverage, vCoverage);
				
				if((prevCoverage > 0) && (coverage > 0)) {
					// our ideal coverage is 90%. Find out how close we are to that.
					var distance: Number = 0.9 - coverage;
					if (Math.abs(distance) > 0.03) {
						// We are more than 3% away from the ideal coverage
						
						// Find out how much the coverage has changed in the last tick
						// A positive delta means the space occupied by our items
						// is expanding, negative means it's contracting
						var deltaCoverage: Number = coverage - prevCoverage;
						
						// Figure out how quickly we want to expand or contract.
						// The further away we are from the target coverage, the more quickly
						// we want the coverage to change. But we don't want to change it
						// too quickly, because we don't to overshoot, we don't want to
						// accelerate or decelerate too fast.
						var targetDelta: Number = distance * 0.2;
						if(targetDelta < -0.01) targetDelta = -0.01;
						if(targetDelta > 0.01) targetDelta = 0.01;
						
						if(deltaCoverage < targetDelta) {
							// we're not expanding fast enough. crank up the repulsion,
							_repulsionFactor = _repulsionFactor + 0.01;
							// (but not too much!)
							if(_repulsionFactor > 0.7)
								_repulsionFactor = 0.7;
						} else {
							// we're not contracting fast enough. crank down the repulsion. 
							_repulsionFactor = _repulsionFactor - 0.01;
							// (but not too much!)
							if(_repulsionFactor < 0.05)
								_repulsionFactor = 0.05;
						}
						//trace("rep " + this._repulsionFactor + ", coverage " + coverage 
						//	+ ", delta " + deltaCoverage + ", target " + targetDelta);
					}
				}
				prevCoverage = coverage;

				if((itemBounds.left < 0) || (itemBounds.top < 0) || (itemBounds.bottom > this.height) || (itemBounds.right > this.width)) {
					// some items are off the screen. Let's auto-scroll the display.
					
					// calculate how far we have to center all the items on screen in the X direction
					var scrollX: int = (this.width / 2) - (itemBounds.x + (itemBounds.width / 2));
					// limit it to a few pixels at a time, I think this looks nicer
					if(scrollX < -1) scrollX = -1;
					if(scrollX > 1) scrollX = 1;
					
					// do the same for the Y direction
					var scrollY: int = (this.height / 2) - (itemBounds.y + (itemBounds.height / 2));
					if(scrollY < -1) scrollY = -1;
					if(scrollY > 1) scrollY = 1;
					
					// do the scrolling
					if((scrollX != 0) || (scrollY != 0))
						scroll(scrollX, scrollY);
				}
 			}
			if(prevRepulsionFactor != _repulsionFactor) {
				prevRepulsionFactor = _repulsionFactor;
				dispatchEvent(new Event("repulsionFactorChanged"));
			}
        }
		
  		private function calcItemsBoundingRect(): Rectangle {
   			var c: Array = this.getChildren();
   			if(c.length == 0) return null;

			var result: Rectangle = new Rectangle(9999999, 9999999, -9999999, -9999999);
   			for (var i: int = 1; i < c.length; i++) {
   				var itemView: Object = c[i];
   				if(itemView != drawingSurface) {
		    		if(itemView.x < result.left) result.left = itemView.x;
		    		if((itemView.x + itemView.width) > result.right) result.right = itemView.x + itemView.width;
		    		if(itemView.y < result.top) result.top = itemView.y;
		    		if((itemView.y + itemView.height) > result.bottom) result.bottom = itemView.y + itemView.height;
   				}
   			}
   			return result;
  		}

	    /** @private */
		protected var _dataProvider:GraphDataProvider = null;
	    /** @private */
		public var distinguishedItem: Item;
	    /** @private */
		protected var _lineColor: int = 0xcccccc;
	    /** @private */
		public var distinguishedLineColor: int = 0xff0000;
	    /** @private */
		public var _repulsionFactor: Number = 0.75;
	    /** @private */
		public var defaultRepulsion: Number = 100;
	    /** @private */
		protected var forceDirectedLayout: ForceDirectedLayout = null;
		/** @private */
		protected var drawingSurface: UIComponent; // we can't use our own background for drawing, because it doesn't scroll
		/** @private */
		protected var _graph: Graph;
		/** @private */
		protected var _xmlNames: Array;

        private var timer:Timer;      
		private var itemRendererFactory: IFactory = null;
        private var dragComponent: UIComponent;
        private var dragStartX: int;
        private var dragStartY: int;
        private var dragCursorStartX: int;
        private var dragCursorStartY: int;
        private var lastMouseDownTime: int = -999999;
        private var paused: Boolean = false;
        private var backgroundDragInProgress: Boolean = false;
        private var _viewFactory: IViewFactory = null;
        private var _edgeRenderer: IEdgeRenderer = null;
		private var _autoFit: Boolean = false;
		private var prevCoverage: Number = 0;
		private var prevRepulsionFactor: Number = 0;
	}
}
