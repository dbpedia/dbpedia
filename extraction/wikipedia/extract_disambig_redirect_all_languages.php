<?php
	
	// path to  <MediaWiki>/languages/messages
	$path = ''; 
	// the Util.php will use this files for isDisambiguation and isRedirect
	$disambig_path = '../core/disambigs.php';
	$redirect_path = '../core/redirects.php';
	
	if ($dir=opendir($path)) {
		while(false !== ($file=readdir($dir))) {
			if (!is_dir($file) && (preg_match("~Messages(.*)\.php~",$file,$matches))) {
				$files_with_languages[] = $matches;
			}
		}
		closedir($dir);
	} else {
		die ("Please set path for <MediaWiki>/languages/messages directory first!");
	}
	
	$languages = array();
	// get all disambiguation & redirect arrays
	foreach ($files_with_languages as $file_and_language){
		$language = strtolower($file_and_language[1]);
		
		// in other places, wikipedia uses '-' instead of '_'
		$language = str_replace('_','-',$language);
		
		// qqq is the documentation
		if ($language == "qqq") continue;
		
		$languages[] = $language;

		$file = $file_and_language[0];
		$file_with_path = $path.'/'.$file_and_language[0];
		
		
		unset($magicWords, $messages, $fallback);
		// "execute" the language.php file will set $magicWords, $messages, $fallback
		require $file_with_path;

		if (!$magicWords['redirect']) {
			//echo "       " . $file . " has no magicWords or no redirect \n";
		} else {
			$redirect_synonym_list = null;
			foreach ($magicWords['redirect'] as $id => $redirect_synonym) {			
				if (($id == 0) && (!is_numeric($redirect_synonym))) {
					echo "        " . $file . "has a nonNumeric value at redirect[0]: ", $redirect_synonym;
				}
				if ($id > 0) {
					$redirect_synonym_list[] = $redirect_synonym;
				}
			}
			$redirect_languages[$language] = $redirect_synonym_list;
		} // end 'if' for '$magicWords['redirect']' check

		
		if (!$messages['disambiguationspage']) {
			// echo "       " . $file . " has no DisambiguationPage \n";
		} else {
			$messages['disambiguationspage'] = str_replace("Template:", "", $messages['disambiguationspage']);
			$messages['disambiguationspage'] = str_replace("{{ns:template}}:", "", $messages['disambiguationspage']);
			$messages['disambiguationspage'] = str_replace("\r\n", "\n", $messages['disambiguationspage']);
			$messages['disambiguationspage'] = str_replace("\r", "\n", $messages['disambiguationspage']);
			$messages_array = explode("\n", $messages['disambiguationspage']);
			foreach ($messages_array as $disambig_message){
				$disambig_languages[$language][] = trim($disambig_message);
			}
		}

		if ($fallback) {
			$fallback_languages[$language] = $fallback;
		}	
	} // foreach language-file
	
//var_dump($redirect_languages);
//var_dump($disambig_languages);
//var_dump($fallback_languages);

	foreach ($languages as $language) {
		if (!$redirect_languages[$language]) {
			$language_temp = $language;
			do {
				if ($redirect_languages[$fallback_languages[$language_temp]]) {
					$redirect_languages[$language] = $redirect_languages[$fallback_languages[$language_temp]];
					break;
				} else {
					$language_temp = $fallback_languages[$language_temp];
				}
			} while ($fallback_languages[$language_temp]);
		}
		
		if (!$disambig_languages[$language]) {
			$language_temp = $language;
			do {
				if ($disambig_languages[$fallback_languages[$language_temp]]) {
					$disambig_languages[$language] = $disambig_languages[$fallback_languages[$language_temp]];
					break;
				} else {
					$language_temp = $fallback_languages[$language_temp];
				}
			} while ($fallback_languages[$language_temp]);
		}
	} // end foreach 

	// echo if there is no redirect or disambig-language set
	$no_redirect_found_counter = 0;
	$no_disambig_found_counter = 0;
	foreach ($languages as $language) {
		if (!$redirect_languages[$language]) {
			echo "no rediret for $language \n";
			$no_redirect_found_counter++;
		}
		if (!$disambig_languages[$language]) {
			echo "no disambig for $language \n";
			$no_disambig_found_counter++;
		}
	}
	echo "number of languages with no redirect found ".$no_redirect_found_counter."\n";
	echo "number of languages with no disambig found ".$no_disambig_found_counter."\n";
	
	
	// generate disambigs file
	$path = $disambig_path;
	if (!$file_handle = fopen($path, "wb")) {
		die ("File not found " . $path);
	}
	$first = true;
	fwrite($file_handle, "<?php\n");
	fwrite($file_handle, '$GLOBALS[\'MEDIAWIKI_DISAMBIGUATIONS\'] = array(');
	foreach ($disambig_languages as $language => $disambiguationspage) {
		if ($first) $first = false; else fwrite($file_handle, ',');
		fwrite($file_handle, "\n" . "    '" . $language . "' => array(");
		$innerFirst = true;
		foreach ($disambiguationspage as $disambig_synonym) {
			if ($innerFirst) $innerFirst = false; else fwrite($file_handle, ',');
			// encoding arrangments
			$disambig_synonym = str_replace('\\', '\\\\', $disambig_synonym);
			$disambig_synonym = str_replace('\'', '\\\'', $disambig_synonym);
			fwrite($file_handle, "'" . $disambig_synonym . "'");
		}
		fwrite($file_handle, ')');
	}
	fwrite($file_handle, "\n);\n");
	if(!fclose($file_handle)) {
		echo "error closing " . $path;
	}
		
	// generate redirects file
	$path = $redirect_path;
	if (!$file_handle = fopen($path, "wb")) {
		die ("File not found " . $path);
	}
	$first = true;
	fwrite($file_handle, "<?php\n");
	fwrite($file_handle, '$GLOBALS[\'MEDIAWIKI_REDIRECTS\'] = array(');
	foreach ($redirect_languages as $language => $redirect_synonym_list) {
		if ($first) $first = false; else fwrite($file_handle, ',');
		fwrite($file_handle, "\n" . "    '" . $language . "' => array(");
		$innerFirst = true;
		foreach ($redirect_synonym_list as $redirect_synonym) {
			if ($innerFirst) $innerFirst = false; else fwrite($file_handle, ',');
			// encoding arrangments
			$redirect_synonym = str_replace('\\', '\\\\', $redirect_synonym);
			$redirect_synonym = str_replace('\'', '\\\'', $redirect_synonym);
			fwrite($file_handle, "'" . $redirect_synonym . "'");
		}
		fwrite($file_handle, ')');
	}
	fwrite($file_handle, "\n);\n");
	if(!fclose($file_handle)) {
		echo "error closing " . $path;
	}
		
	






