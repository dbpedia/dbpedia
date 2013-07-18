// Reflector, by Narciso Jaramillo, nj_flex@rictus.com
// Copyright 2006 Narciso Jaramillo

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Partly based on ReflectFilter.as by Trey Long, trey@humanwasteland.com.

package com.rictus.reflector
{
	import flash.display.BitmapData;
	import flash.display.GradientType;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.geom.Matrix;
	import flash.geom.Point;
	import flash.geom.Rectangle;
	
	import mx.core.UIComponent;
	import mx.events.FlexEvent;
	import mx.events.MoveEvent;
	import mx.events.ResizeEvent;
	import flash.filters.BlurFilter;
	import flash.filters.BitmapFilter;
	import flash.filters.BitmapFilterQuality;

	/**
	 * A component that displays a reflection below another component. 
	 * The reflection is "live"--as the other component's display updates,
	 * the reflection updates as well.  The reflection automatically positions
	 * itself below the target component (so it only works if the target
	 * component's container is absolutely positioned, like a Canvas or a
	 * Panel with layout="absolute").
	 * 
	 * Typically, you'll want to set a low alpha on the Reflector component (0.3
	 * would be a good default).
	 * 
	 * Author: Narciso Jaramillo, nj_flex@rictus.com
	 */
	public class Reflector extends UIComponent
	{
		// The component we're reflecting.
		private var _target: UIComponent;
		
		// Cached bitmap data objects.  We store these to avoid reallocating
		// bitmap data every time the target redraws.
		private var _alphaGradientBitmap: BitmapData;
		private var _targetBitmap: BitmapData;
		private var _resultBitmap: BitmapData;
		
		// The current falloff value (see the description of the falloff property).
		private var _falloff: Number = 0.6;
		
        // the current blur value
        private var _blurAmount:Number = 0.5;
        
		/**
		 * The UIComponent that you want to reflect.  Should be in an absolutely-
		 * positioned container.  The reflector will automatically position itself
		 * beneath the target.
		 */		 
		[Bindable]
		public function get target(): UIComponent {
			return _target;
		}
		
		public function set target(value: UIComponent): void {
			if (_target != null) {
				// Remove our listeners from the previous target.
				_target.removeEventListener(FlexEvent.UPDATE_COMPLETE, handleTargetUpdate, true);
				_target.removeEventListener(MoveEvent.MOVE, handleTargetMove);
				_target.removeEventListener(ResizeEvent.RESIZE, handleTargetResize);
				
				// Clear our bitmaps, so we regenerate them next time a component is targeted.
				clearCachedBitmaps();
			}
			
			_target = value;
			
			if (_target != null) {
				// Register to get notified whenever the target is redrawn.  We pass "true" 
				// for useCapture here so we can detect when any descendants of the target are
				// redrawn as well.
				_target.addEventListener(FlexEvent.UPDATE_COMPLETE, handleTargetUpdate, true);
				
				// Register to get notified whenever the target moves or resizes.
				_target.addEventListener(MoveEvent.MOVE, handleTargetMove);
				_target.addEventListener(ResizeEvent.RESIZE, handleTargetResize);
				
				// Position ourselves correctly.
				handleMove();
				handleResize();
				
				// Mark ourselves dirty so we get redrawn at the next opportunity.
				invalidateDisplayList();
			}
		}
		
		/**
		 * How much of the component to reflect, between 0 and 1; 0 means not to
		 * reflect any of the component, while 1 means to reflect the entire
		 * component.  The default is 0.6.
		 */
		[Bindable]
		public function get falloff(): Number {
			return _falloff;
		}
		
		public function set falloff(value: Number): void {
			_falloff = value;
			handleResize();
		}
        
        [Bindable]
        public function get blurAmount(): Number {
            return _blurAmount;
        }

        public function set blurAmount(value: Number): void {
            _blurAmount = value;
            handleResize();
        }

		private function handleTargetUpdate(event: FlexEvent): void {
			// The target has been redrawn, so mark ourselves for redraw.
			invalidateDisplayList();
		}
		
		private function handleTargetMove(event: MoveEvent): void {
			handleMove();
		}
		
		private function handleTargetResize(event: ResizeEvent): void {
			handleResize();
		}
		
		private function handleResize(): void {
			// Since the target is resizing or the falloff is changing, we have 
			// to recreate our bitmaps in addition to redrawing and resizing ourselves.
			clearCachedBitmaps();
			if (_target != null) {
				width = _target.width;
				height = _target.height * _falloff;
				handleMove();
				invalidateDisplayList();
			}
		}
		
		private function handleMove(): void {
			// Move to be immediately below the target.  We don't need to
			// redraw ourselves in this case.
			move(_target.x, _target.y + _target.height);
		}
		
		override protected function updateDisplayList(unscaledWidth: Number, unscaledHeight: Number): void {
			// This function is called by the framework at some point after invalidateDisplayList() is called.
			if (_target != null && _target.height * _falloff >= 1) {
				// Create our cached bitmap data objects if they haven't been created already.
				createBitmaps(_target);
				
				var rect: Rectangle = new Rectangle(0, 0, _target.width, _target.height * _falloff);
				
				// Draw the bottom part of the target component into the target bitmap, flipped upside down.
				var flipTransform: Matrix = new Matrix();
				flipTransform.scale(1, -1);
				flipTransform.translate(0, _target.height);
				_targetBitmap.fillRect(rect, 0x00000000);
				_targetBitmap.draw(_target, flipTransform);
				
				// Combine the target image with the alpha gradient to produce the reflection image.
				_resultBitmap.fillRect(rect, 0x00000000);
				_resultBitmap.copyPixels(_targetBitmap, rect, new Point(), _alphaGradientBitmap);
				
                // And blur it
                graphics.beginFill(0xFFCC00);
                graphics.drawRect(0, 0, _target.width, _target.height);
                graphics.endFill();
                var filter:BitmapFilter = new BlurFilter(_blurAmount*5, _blurAmount*10, BitmapFilterQuality.HIGH);
                var myFilters:Array = new Array();
                myFilters.push(filter);
                filters = myFilters;            
                
				// Finally, copy the resulting bitmap into our own graphic context.
				graphics.clear();
				graphics.beginBitmapFill(_resultBitmap, null, false);
				graphics.drawRect(0, 0, _target.width, _target.height * _falloff);
			}
		}
		
		private function clearCachedBitmaps(): void {
			_alphaGradientBitmap = null;
			_targetBitmap = null;
			_resultBitmap = null;
		}
		
		private function createBitmaps(target: UIComponent): void {
			if (target != null && target.height * _falloff >= 1) {
				if (_alphaGradientBitmap == null) {
					// Create and store an alpha gradient.  Whenever we redraw, this will be combined
					// with an image of the target component to create the "fadeout" effect.
					_alphaGradientBitmap = new BitmapData(target.width, target.height * _falloff, true, 0x00000000);
					var gradientMatrix: Matrix = new Matrix();
					var gradientSprite: Sprite = new Sprite();
					gradientMatrix.createGradientBox(target.width, target.height * _falloff, Math.PI/2, 
						0, 0);
					gradientSprite.graphics.beginGradientFill(GradientType.LINEAR, [0xFFFFFF, 0xFFFFFF], 
						[1, 0], [0, 255], gradientMatrix);
					gradientSprite.graphics.drawRect(0, 0, target.width, target.height * _falloff);
					gradientSprite.graphics.endFill();
					_alphaGradientBitmap.draw(gradientSprite, new Matrix());
				}
				if (_targetBitmap == null) {
					// Create a bitmap to hold the target's image.  This is updated every time
					// we're redrawn in updateDisplayList().
					_targetBitmap = new BitmapData(target.width, target.height * _falloff, true, 0x00000000);
				}
				if (_resultBitmap == null) {
					// Create a bitmap to hold the reflected image.  This is updated every time
					// we're redrawn in updateDisplayList().
					_resultBitmap = new BitmapData(target.width, target.height * _falloff, true, 0x00000000);
				}
			}
		}
	}
}