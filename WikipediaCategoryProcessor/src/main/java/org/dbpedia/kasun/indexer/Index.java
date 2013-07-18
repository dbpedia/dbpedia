/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/** 
 * 
 *      Date             Author          Changes 
 *      Jul 17, 2013     Kasun Perera    Created   
 * 
 */ 

package org.dbpedia.kasun.indexer;


import java.io.File;
import java.io.IOException;
import java.io.StringReader;
import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.core.WhitespaceAnalyzer;
//import org.apache.lucene.analysis.;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.index.CorruptIndexException;
import org.apache.lucene.index.IndexWriter;
import org.apache.lucene.index.IndexWriterConfig;
import org.apache.lucene.store.NIOFSDirectory;
import org.apache.lucene.util.Version;



/**
 * TODO- describe the  purpose  of  the  class
 * 
 */
public class Index {
     public void index() throws IOException {

       // int noOfDocs = docNames.length;
        //String content = convertPDFToText(docNo);
        //String content = ReadTextFile(fileNames[docNo]);
//String b = new DefaultTokenizer().processText(content);
        // this.noOfWordsOfDOc[curDocNo] = wordCount(content);
        //StringReader strRdElt = new StringReader(content);

        // StringReader strRdElt = new StringReader(new DefaultTokenizer().processText(filesInText[docNo]));



String pathToIndex="";
int noOfDocs = 0;




        //  doc.add(new Field(docNames ;
        //this.ArrLstSentencesOfDoc[curDocNo] = sentenceCount(content);
        //this.noOfSentencesOfDoc[curDocNo] = this.ArrLstSentencesOfDoc[curDocNo].size() ;
        IndexWriter iW;
        try {
            NIOFSDirectory dir = new NIOFSDirectory(new File(pathToIndex)) ;
            //dir = new RAMDirectory() ;
           iW = new IndexWriter(dir, new IndexWriterConfig( Version.LUCENE_43, new WhitespaceAnalyzer( Version.LUCENE_43 ) ));
           

            for (int i = 0; i < noOfDocs; i++) {
                StringReader strRdElt = new StringReader("");
                StringReader docId = new StringReader(Integer.toString(i));

                Document doc = new Document();

                doc.add(new Field("doccontent", strRdElt, Field.TermVector.YES));
                doc.add(new Field("docid", docId, Field.TermVector.YES));
              //  iW.addDocument(doc);
            }


            iW.close();
            dir.close() ;
        } catch (CorruptIndexException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }


}
