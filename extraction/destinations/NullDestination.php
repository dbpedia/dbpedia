<?php
/**
*/
class NullDestination implements Destination{
	public function start() {}
	public function accept($extractionResult) {}
	public function finish() {}
}
