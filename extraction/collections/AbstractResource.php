<?php

abstract class AbstractResource {
	
		protected $wasInititialized = false;
		public $id;
		
		public function __construct($id){
			$this->id = $id;
			}
		
		/*
		 * Connections should be bound to the lifetime of the object
		 * */
		public function __destruct(){
				$this->close();
			}
		
		public abstract function close();
			
		public function log ($lvl, $message){
		
			Logger::logComponent("resource",get_class($this)."", $lvl ,$message);
		
		}
	
	}
