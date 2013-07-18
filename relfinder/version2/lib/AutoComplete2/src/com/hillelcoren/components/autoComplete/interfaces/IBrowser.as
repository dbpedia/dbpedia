package com.hillelcoren.components.autoComplete.interfaces
{
	import flash.events.IEventDispatcher;
	
	import mx.collections.ArrayCollection;
	
	public interface IBrowser extends IEventDispatcher
	{
		function init():void
		function get selectedItems():Array;
		function set filterFunction( value:Function ):void
		function set title( value:String ):void;
		function set dataProvider( value:ArrayCollection ):void
		function set labelFunction( value:Function ):void
		function set originalSelectedItems( value:ArrayCollection ):void
	}
}
