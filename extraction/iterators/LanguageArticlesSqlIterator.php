<?php

/**
 * The LanguageArticlesSqlIterator cycles over all articles
 * in the specified language that are not redirects. Unlike
 * other iterators, it also includes articles that are not
 * linked from an equivalent article in the English Wikipedia.
 */
class LanguageArticlesSqlIterator extends AbstractMySqlIterator
{
	protected function catalog($dbprefix, $language) {
		return $dbprefix.$language;
	}
	
	protected function template($language) {
		return "select page_title from page where (page_namespace = 0) and page_is_redirect = 0 LIMIT %d, %d";
	}
	
	protected function value($row) {
		return $row['page_title'];
	}
	
}
