/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import dbPediaQAF.util.ExcelExport;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;
import dbPediaQAF.Config;
import dbPediaQAF.Evaluator;
import dbPediaQAF.resultManaging.TripleObjectResult;
import dbPediaQAF.util.BaseCategory;
import dbPediaQAF.util.PatternCategory;
import dbPediaQAF.xmlQuery.Snippet;
import java.io.BufferedWriter;
import java.io.FileWriter;
import java.io.IOException;
import java.util.Calendar;
import java.util.Iterator;
import java.util.List;
import java.util.Locale;

/**
 *
 * @author Paul
 */
public class ResultSet
{

    public CompletenessHandswitch goldStandard = new CompletenessHandswitch();
    public CompletenessHandswitch triplePNCactual = new CompletenessHandswitch();
    public CompletenessHandswitch tripleTEC = new CompletenessHandswitch();
    public CompletenessHandswitch triplePNC = new CompletenessHandswitch();
    public CompletenessHandswitch tripleOSC = new CompletenessHandswitch();
    public CompletenessHandswitch tripleOSCwrong = new CompletenessHandswitch();
    public CompletenessHandswitch snippetGoldStandard = new CompletenessHandswitch();
    public CompletenessHandswitch snippetTEC = new CompletenessHandswitch();
    public CompletenessHandswitch snippetPNC = new CompletenessHandswitch();
    public CompletenessHandswitch snippetOSC = new CompletenessHandswitch();

    public void cleanResults(DataSet datastore)
    {
        System.out.println("============================================================");
        System.out.println("Result Cleaning");
        System.out.println("============================================================");
        if (tripleTEC.getMissingTriples().containsAny(tripleTEC.getPresentTriples()))
        {
            System.out.println("DOUBLE ENTRYS in the Triple Equality Comparison!");
            System.out.println("MUST BE FIXED !!");
        }
        if (triplePNC.getMissingTriples().containsAny(triplePNC.getPresentTriples()))
        {
            System.out.println("DOUBLE ENTRYS in the Predicate Neutrality Comparison!");
            System.out.println("MUST BE FIXED !!");
        }

        if (tripleOSC.getMissingTriples().containsAny(tripleOSC.getPresentTriples()))
        {
            System.out.println("DOUBLE ENTRYS in the Object Similarity Comparison:");
            Model all = ModelFactory.createDefaultModel();
            all = tripleOSC.getTriples();

            Model onlyMissing = ModelFactory.createDefaultModel();
            onlyMissing = tripleOSC.getMissingTriples().difference(tripleOSC.getPresentTriples());

            Model onlyPresent = ModelFactory.createDefaultModel();
            onlyPresent = tripleOSC.getPresentTriples().difference(tripleOSC.getMissingTriples());

            Model onlyMissPres = ModelFactory.createDefaultModel();
            onlyMissPres = onlyMissing.union(onlyPresent);

            Model dif = ModelFactory.createDefaultModel();
            dif = all.difference(onlyMissPres);

            System.out.println("The following triples are marked as present now:");
            for (Iterator it = dif.listStatements(); it.hasNext();)
            {
                Statement stmt = (Statement) it.next();
                tripleOSC.missing.remove(stmt, datastore);
                Evaluator.printStatement(stmt, null);
                System.out.println();
            }
            if (tripleOSC.getPresentTriples().containsAll(dif) && !tripleOSC.getMissingTriples().containsAll(dif))
            {
                System.out.println("Fixed.");
            }
            else
            {
                System.out.println("Some double entries left.");
            }
//            System.out.println("thirdResultSetPresent: " + thirdResultSetPresent.getTriples().size());
//            System.out.println("thirdResultSetMissing: " + thirdResultSetMissing.getTriples().size());
//            System.out.println("all: " + all.size());
//            System.out.println("onlyMissing: " + onlyMissing.size());
//            System.out.println("onlyPresent: " + onlyPresent.size());
//            System.out.println("onlyMissPres: " + onlyMissPres.size());
//            System.out.println("dif: " + dif.size());
        }
    }

    public void printResults(DataSet datastore)
    {
        cleanResults(datastore);
        System.out.println("============================================================");
        System.out.println("RESULTS");
        System.out.println("============================================================");
        System.out.println("Gold Standard:");
        goldStandard.printStats();
        System.out.println();
        System.out.println("============================================================");
        System.out.println("Result of the Triple Equality Compariosn: ");
        tripleTEC.printStats();
        System.out.println();
        System.out.println("============================================================");
        System.out.println("Result of the Predicate Neutrality Comparison: ");
        triplePNC.printStats();
        System.out.println();
        System.out.println("============================================================");
        System.out.println("Result of the Object Similarity Comparison: ");
        tripleOSC.printStats();
        System.out.println();
        System.out.println("------------------------------");
        System.out.println("Wrong extracted triples: ");
        tripleOSCwrong.printStats();
        System.out.println();
        System.out.println("============================================================");
        System.out.println("Snippet results:");
        System.out.println("============================================================");
        System.out.println("Result of the Snippet Triple Equality Comparison: ");
        snippetTEC.printStats();
        System.out.println("============================================================");
        System.out.println("Result of the Snippet Predicate Neutrality Comparison: ");
        snippetPNC.printStats();
        System.out.println("============================================================");
        System.out.println("Result of the Snippet Object Similarity comparison: ");
        snippetOSC.printStats();

//        List<Snippet> snippets = snippetTEC.getSnippets(PatternCategory.PlainProperty);
//        System.out.println("snippetTEC.noClass: " + snippets.size());
//        for (Snippet snippet : snippets)
//        {
//            //System.out.println(snippet.getSource());
//            System.out.println("TRIPLES:" + snippet.getTriple());
//        }


        Model triples = tripleOSCwrong.present.internalTemplates.getTriples();
        System.out.println("tripleOSCwrong.present.internalTemplates: " + triples.size());

        for (StmtIterator iterator = triples.listStatements(); iterator.hasNext();)
            {
                Statement statement = iterator.nextStatement();
                Evaluator.printStatement(statement, null);
                System.out.println();
        }

        //System.out.println(tripleTEC.getTripleCSV());
    }

    public void createCSV()
    {
        Calendar cal = Calendar.getInstance();
        String date = cal.get(5) + " " + cal.getDisplayName(2, Calendar.LONG, Locale.ENGLISH) + " " + cal.get(1) + " - " + cal.get(11) + ":" + cal.get(12) + "\n";
        String source = "Results produced at " + date;
        source = source + "Gold Standard \n" + goldStandard.getTripleCSV();
        source = source + "tripleTEC \n" + tripleTEC.getTripleCSV();
        source = source + "triplePNC \n" + triplePNC.getTripleCSV();
        source = source + "tripleOSC \n" + tripleOSC.getTripleCSV();
        source = source + "tripleOSCwrong \n" + tripleOSCwrong.getTripleCSV();
        source = source + "snippetTEC \n" + snippetTEC.getSnippetCSV();
        source = source + "snippetPNC \n" + snippetPNC.getSnippetCSV();
        source = source + "snippetOSC \n" + snippetOSC.getSnippetCSV();
        String csvFile = Config.getCsvOutputPath();
        try
        {
            BufferedWriter out = new BufferedWriter(new FileWriter(csvFile));
            out.write(source);
            out.close();
        }
        catch (IOException exWriter)
        {
            System.out.println("Msg: " + exWriter.getMessage());
        }
    }

    public void exportToExcel(DataSet datastore)
    {

        cleanResults(datastore);
        ExcelExport export = new ExcelExport(this);
        export.updateTripleSheet();
        export.updateSnippetSheet();
    }
}
