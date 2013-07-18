<?php

/**
 * The CategoriesSqlIterator cycles over all Categories
 * in the DBpedia MySQL database that are not redirects.
 * Database settings have to be specified in databaseconfig.php.
 */
class LanguageCategoriesSqlIterator extends AbstractMySqlIterator
{
	private $language;

	protected function catalog($dbprefix, $language) {
		$this->language = $language;
		return $dbprefix.$language;
	}

	protected function template($language) {
		return "select page_title from page where page_namespace = 14 and page_is_redirect = 0 LIMIT %d, %d";
	}

	protected function value($row) {
		if (!isset($this->language)) {
			$this->language = Options::getOption('language');
		}
		$category = Util::getMediaWikiNamespace($this->language, MW_CATEGORY_NAMESPACE);
		return $category.":" . $row['page_title'];
	}

}

