<?php

/**
 * The AllArticlesSqlIterator cycles over all articles
 * in the DBpedia MySQL database, including redirect pages. 
 * Database settings have to be specified in databaseconfig.inc.php.
 */
class AllArticlesSqlIterator extends AbstractMySqlIterator
{
	protected function catalog($dbprefix, $language) {
		return $dbprefix.'en'; // always use en.wiki
	}
	
	protected function template($language) {
		if($language == 'en') {
			return "select page_title from page where page_namespace = 0 LIMIT %d, %d";
		} else {
			// language code in column ll_lang uses '-', not '_'
			$language = str_replace('_', '-', $language);
			// find links from en.wiki to $language.wiki
			return "select replace(trim(ll_title), ' ', '_') as page_title from page p inner join langlinks ll on p.page_id = ll.ll_from where p.page_namespace = 0 and ll.ll_lang = '$language' LIMIT %d, %d";
		}
	}
	
	protected function value($row) {
		return $row['page_title'];
	}
}

