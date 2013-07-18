package com
{
	[Bindable]
	public class Color
	{
		private var _name:String;
		private var _hex:uint;
		
		public function Color( name:String, color:uint )
		{
			_name = name;
			_hex = color;
		}
		
		public function set name( value:String ):void
		{
			_name = value;
		}
		
		public function get name():String
		{
			return _name;
		}
		
		public function set hex( value:uint ):void
		{
			_hex = value;
		}
		
		public function get hex():uint		
		{
			return _hex;
		}
	}
}