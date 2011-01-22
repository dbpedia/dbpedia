<?php
/**
 * @author	Paul Kreis <mail@paulkreis.de>
 * 
 */

class FileManager {

	/**
	 * Returns a single line from a file
	 *
	 * @param string $filename
	 * @param int $line 
	 * @return string
	 */
	function getFileLine($filename, $line) {
		$data = file($filename);
		return $data[$line];
	}
	
	/**
	 *
	 * @param resource $handle
	 * @return string
	 */
	function getNextFileLine($handle) {
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			return $buffer;
		}
	}
}
