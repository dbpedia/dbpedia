package  
{
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.utils.Timer;
	
	import mx.core.IInvalidating;
	import mx.core.UIComponent;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class LoadingAnimation extends UIComponent
	{
		private var timer:Timer;
		
		private var _timerDelay:Number = 100;
		
		private var _rotationAngle:Number = 0;
		
		private var _rotationAngleStep:Number = 45;
		
		private var _dotSize:Number = 3;
		
		private var _circleRadius:Number = 10;
		
		private var _numberOfDots:int = 4;
		
		private var _radial:Number = Math.PI / 180;
		
		private var _width:Number = 22;
		
		private var _height:Number = 22;
		
		public function LoadingAnimation() 
		{
			timer = new Timer(_timerDelay);
			timer.addEventListener(TimerEvent.TIMER, rotate);
		}
		
		public function startRotation():void{
			if (!timer.running){
				
				timer.start();
				
				setSizeByCircleRadius();
				
				dispatchEvent(new Event("rotationStatusChanged"));
			}
			
		}
		
		public function stopRotation():void{
			if(timer.running){
				timer.stop();
				
				setSizeByCircleRadius();
				
				graphics.clear();
				dispatchEvent(new Event("rotationStatusChanged"));
			}
		}
		
		[Bindable(event="rotationStatusChanged")]
		public function isRotating():Boolean{
			return timer.running;
		}
		
		[Bindable(event="widthChanged")]
		override public function get width():Number
	    {
	        return _width;
	    }
	
	    override public function set width(value:Number):void
	    {
	        if (explicitWidth != value)
	        {
	            explicitWidth = value;
	            
	            invalidateSize();
	        }
	
	        if (_width != value)
	        {
	            invalidateProperties();
	            invalidateDisplayList();
	
	            var p:IInvalidating = parent as IInvalidating;
	            if (p && includeInLayout)
	            {
	                p.invalidateSize();
	                p.invalidateDisplayList();
	            }
	
	            _width = value;
	
	            dispatchEvent(new Event("widthChanged"));
	        }
	    }
		
		[Bindable(event="heightChanged")]
		override public function get height():Number
	    {
	        return _height;
	    }
		
	    override public function set height(value:Number):void
	    {
	        if (explicitHeight != value)
	        {
	            explicitHeight = value;
	            
	            invalidateSize();
	        }
	
	        if (_height != value)
	        {
	            invalidateProperties();
	            invalidateDisplayList();
	
	            var p:IInvalidating = parent as IInvalidating;
	            if (p && includeInLayout)
	            {
	                p.invalidateSize();
	                p.invalidateDisplayList();
	            }
	
	            _height = value;
	
	            dispatchEvent(new Event("heightChanged"));
	        }
	    }
		
		[Bindable(event="delayChanged")]
		public function get animationDelay():Number{
			return _timerDelay;
		}
		
		public function set animationDelay(value:Number):void{
			_timerDelay = value;
			timer.delay = value;
			dispatchEvent(new Event("delayChanged"));
		}
		
		[Bindable(event="rotationAngleChanged")]
		public function get rotationAngle():Number{
			return _rotationAngle;
		}
		
		public function set rotationAngle(value:Number):void{
			_rotationAngle = value;
			dispatchEvent(new Event("rotationAngleChanged"));
		}
		
		[Bindable(event="rotationAngleStepChanged")]
		public function get rotationAngleStep():Number{
			return _rotationAngleStep;
		}
		
		public function set rotationAngleStep(value:Number):void{
			_rotationAngleStep = value;
			dispatchEvent(new Event("rotationAngleStepChanged"));
		}
		
		[Bindable(event="dotSizeChanged")]
		public function get dotSize():Number{
			return _dotSize;
		}
		
		public function set dotSize(value:Number):void{
			_dotSize = value;
			setSizeByCircleRadius();
			dispatchEvent(new Event("dotSizeChanged"));
		}
		
		[Bindable(event="circleRadiusChanged")]
		public function get circleRadius():Number{
			return _circleRadius;
		}
		
		public function set circleRadius(value:Number):void{
			_circleRadius = value;
			
			setSizeByCircleRadius();
			
			dispatchEvent(new Event("circleRadiusChanged"));
		}
		
		[Bindable(event="numberOfDotsChanged")]
		public function get numberOfDots():Number{
			return _numberOfDots;
		}
		
		public function set numberOfDots(value:Number):void{
			_numberOfDots = value;
			dispatchEvent(new Event("numberOfDotsChanged"));
		}
		
		private function setSizeByCircleRadius():void{
			if (isRotating()){
				width = _circleRadius * 2 + 4 + _dotSize;
				height = _circleRadius * 2 + 4 + _dotSize;
			}else{
				width = 0;
				height = 0;
			}
		}
		
		private function rotate(event:TimerEvent):void {
			
			var centerX:int = (width / 2) - (_dotSize / 2) + 1;
			var centerY:int = (height / 2) - (_dotSize / 2) + 1;
			
			graphics.clear();
			
			var alphaCircle:Number = 1.0;
			var decreaseAlpha:Number = 1.0 / _numberOfDots;
			
			for (var i:Number = 0; i < _numberOfDots; i++){
				
				var posX:Number = centerX + (Math.cos((_rotationAngle - (_rotationAngleStep * i)) * _radial) * _circleRadius);
				var posY:Number = centerY + (Math.sin((_rotationAngle - (_rotationAngleStep * i)) * _radial) * _circleRadius);
				
				graphics.beginFill(0x000000, alphaCircle);
				graphics.drawCircle(posX, posY, _dotSize);
				graphics.endFill();
				
				alphaCircle -= decreaseAlpha;
			}
			
			_rotationAngle += _rotationAngleStep;
			_rotationAngle %= 360;
		}
		
	}
	
}