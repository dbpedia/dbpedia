<?php

/**
 * The TemplatesSqlIterator cycles over Templates
 * in the DBpedia MySQL database that are not redirects.
 * Database settings have to be specified in databaseconfig.php.
 */
class TemplatesSqlIterator extends AbstractMySqlIterator
{
	protected function catalog($dbprefix, $language) {
		// always use en.wiki database
		return $dbprefix.'en';
	}
	
	protected function template($language) {
		if($language == 'en') {
			return "select page_title from page where (page_namespace = 10) and page_is_redirect = 0 LIMIT %d, %d";
		} else {
			// language code in column ll_lang uses '-', not '_'
			$language = str_replace('_', '-', $language);
			// find links from en.wiki to $language.wiki
			return "select replace(trim(ll_title), ' ', '_') as page_title from page p inner join langlinks ll on p.page_id = ll.ll_from where (p.page_namespace = 10) and p.page_is_redirect = 0 and ll.ll_lang = '$language' LIMIT %d, %d";
		}
	}
	
	protected function value($row) {
		return 'Template:' . $row['page_title'];
	}
	
}

