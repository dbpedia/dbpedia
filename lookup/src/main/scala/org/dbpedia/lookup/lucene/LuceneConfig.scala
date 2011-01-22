package org.dbpedia.lookup.lucene

import org.apache.lucene.index.IndexWriter
import org.apache.lucene.util.Version
import io.Source
import java.io.File
import org.apache.lucene.analysis._

/**
 * Created by IntelliJ IDEA.
 * User: Max
 * Date: 14.01.11
 * Time: 15:10
 * Lucene configuration data.
 */

object LuceneConfig {

    val INDEX_CONFIG_FILE = "default_index_path"

    // Default index directory is read from the configuration file
    private val defaultIndexDir = new File(Source.fromFile(INDEX_CONFIG_FILE).getLines.next)
    def defaultIndex: File = {
        System.err.println("INFO: using default index specified in '"+INDEX_CONFIG_FILE+"': "+defaultIndexDir)
        if(!defaultIndexDir.isDirectory) {
            System.err.println("WARNING: "+defaultIndexDir+" is not a valid directory.")
        }
        defaultIndexDir
    }

    // Overwrite existing directories when indexing (must be true if target directory does not exist)
    val overwriteExisting = true

    val commitAfterNTriples = 2000000

    // Optimize index after indexing
    val optimize = true

    // Maximum field length for the fields defined below
    val maxFieldLen = new IndexWriter.MaxFieldLength(2000)

    // Lucene Version
    val version = Version.LUCENE_30

//    // Autocomplete-Analyzer
//    class AutocompleteAnalyzer extends Analyzer {
//        override def tokenStream(fieldName: String, reader: Reader): TokenStream = {
//            var result: TokenStream = new StandardTokenizer(version, reader)
//            result = new LowerCaseFilter(result)
//            result = new ISOLatin1AccentFilter(result)
//            result = new EdgeNGramTokenFilter(result, Side.FRONT, 1, 20)
//            System.out.println(result)
//            result
//        }
//    }
    val analyzer = new KeywordAnalyzer

    object Fields {
        val URI = "URI"
        val SURFACE_FORM = "SURFACE_FORM"
        val REFCOUNT = "REFCOUNT"

        val DESCRIPTION = "DESCRIPTION"
        val CLASS = "CLASS"
        val CATEGORY = "CATEGORY"
        val TEMPLATE = "TEMPLATE"
        val REDIRECT = "REDIRECT"
    }

}