<?php

/**
 * The anguageAllArticlesSqlIterator cycles over all articles
 * in the DBpedia MySQL database, including redirect pages.
 * Database settings have to be specified in databaseconfig.inc.php.
 */
class LanguageAllArticlesSqlIterator extends AbstractMySqlIterator
{
	protected function catalog($dbprefix, $language) {
		return $dbprefix.$language;
	}

	protected function template($language) {
		return "select page_title from page where page_namespace = 0 LIMIT %d, %d";
	}

	protected function value($row) {
		return $row['page_title'];
	}
}

