package components
{
	import mx.containers.Panel;
	import mx.controls.Button;
	import flash.display.DisplayObject;
	import mx.controls.Alert;
	import mx.controls.LinkButton;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import mx.events.CloseEvent;
	import mx.containers.ControlBar;
	import mx.binding.utils.BindingUtils;
	import mx.core.UIComponent;
	import mx.core.Container;
	import mx.managers.SystemManager;
	import flash.system.System;
	import mx.collections.ArrayCollection;
	
	public class ButtonBarPanel extends Panel
	{
		private static const cMargin:int = 6;
		private static const cPixelsFromTop:int = 5;
		private static const cPixelsFromRight:int = 15;
		private static const cIconSize : int = 16;
		public var _buttons : ArrayCollection;
		public var btnAtBottom:Boolean = false;
		
		public function get buttons():ArrayCollection
		{
			return _buttons;
		}
		
		public function set buttons(btns:ArrayCollection): void
		{
			_buttons = btns;
			createChildren();
		}
		
		protected override function createChildren():void
		{
			super.createChildren();
			if(buttons == null)
				return;
				
			for(var i:int = 0; i<_buttons.length; i++)
			{
				var btnObj : Object = buttons.getItemAt(i);
				var btn : LinkButton = new LinkButton();
				btn.toolTip = btnObj.toolTip;
				var btnIcon : Object = btnObj.icon;	
				btn.setStyle("overIcon",btnIcon);
	     		btn.setStyle("downIcon",btnIcon);
	     		btn.setStyle("upIcon",btnIcon);
	     		btn.name = btnObj.name;
	     		
	     		btn.addEventListener(MouseEvent.MOUSE_OVER,onMouseEvent);
	     		btn.addEventListener(MouseEvent.MOUSE_OUT,onMouseEvent);
	     		if(btnObj.clickHandler != null)
	     			btn.addEventListener(MouseEvent.CLICK,btnObj.clickHandler);
				btn.visible=true;
				btn.includeInLayout = true;
				rawChildren.addChild(btn);
			}
			if(btnAtBottom)
			{
				this.setStyle("borderThicknessBottom",25);
			}
		}
		
		protected override function updateDisplayList(unscaledWidth: Number, unscaledHeight:Number):void  
		{
		    super.updateDisplayList(unscaledWidth, unscaledHeight);
		    
		    if(unscaledWidth > 0)
		    {
		    	var y:Number = 0;
			    if(btnAtBottom)
			    	y = unscaledHeight - 20;
			    else	
			    	y = cPixelsFromTop;
		    	for(var i:int = 0; i<buttons.length; i++)
		    	{
		    		var btnObj : Object = buttons.getItemAt(i);
		    		var btn:Button = rawChildren.getChildByName(btnObj.name) as Button;
		    		if(btn == null)
		    			continue;
		    		var eIcon:DisplayObject = btn.getChildByName("upIcon");
		    		btn.setActualSize(eIcon.width+cMargin, eIcon.height+cMargin);
			    	var buttonWidth:int=btn.width;
			    	var x:Number = unscaledWidth - buttonWidth - cPixelsFromRight;
			    	 btn.move(x-(i*(cIconSize+cMargin)), y);
		    	}
			    
		    }
		
		 }
		 
		private function onMouseEvent(event:Event) : void
		{
			var btn : Button = Button(event.target);
			if(event.type == MouseEvent.MOUSE_OVER)
				btn.alpha = 0.6;
			else	
				btn.alpha = 1.0;
		}
		
		
		
	}
}