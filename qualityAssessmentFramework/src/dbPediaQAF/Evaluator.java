/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF;

import dbPediaQAF.util.LevenshteinDistance;
import dbPediaQAF.xmlQuery.Snippet;
import com.hp.hpl.jena.rdf.model.*;
import com.hp.hpl.jena.util.FileManager;
import dbPediaQAF.resultManaging.ResultSet;
import dbPediaQAF.resultManaging.DataSet;
import java.io.*;
import java.util.*;

/**
 *
 * @author Paul
 */
public class Evaluator
{

    private DataSet datastore = new DataSet();
    private Model actualDBpediaModel;
    private Model actualDBpediaSubModel;
    private Model actualDBpediaSubSubModel;
    private ResultSet resultSet = new ResultSet();
    private boolean dataLoaded = false;

    /**
     * @return true if loadData() is caled before.
     */
    private boolean isDataLoaded()
    {
        return dataLoaded;
    }

    public void loadData()
    {
        InputStream inputStreamDBpediaRelease = FileManager.get().open(Config.getRelevantDBpediaTriplesPath());
        if (inputStreamDBpediaRelease == null)
        {
            throw new IllegalArgumentException("File: " + inputStreamDBpediaRelease + " not found");
        }
        actualDBpediaModel = ModelFactory.createDefaultModel();
        //actualDBpediaModel.read(inputStreamDBpediaRelease, null);
        actualDBpediaModel.read(inputStreamDBpediaRelease, null, "N-TRIPLE");
        datastore.loadTestdata();
        dataLoaded = true;
    }

    public void createGoldStandard()
    {
        System.out.println("============================================================");
        System.out.println("Creating Goldstandard Statistics");
        System.out.println("============================================================");
        if (isDataLoaded())
        {

            for (StmtIterator iterator = datastore.getGoldModel().listStatements(); iterator.hasNext();)
            {
                Statement statement = iterator.nextStatement();
                resultSet.goldStandard.present.add(statement, datastore);
            }
        }
        System.out.println("done !");
        System.out.println();
    }

    /**
     * - Iterate over all gold triples
     * -- put triple in startResultSetOptimal
     * -- check if triple is in actualDBpediaModel, put into firstReseultSet
     */
    public void tripleEqualityComparison()
    {
        System.out.println("============================================================");
        System.out.println("Triple Equality Comparison");
        System.out.println("============================================================");
        if (isDataLoaded())
        {

            for (StmtIterator iterator = datastore.getGoldModel().listStatements(); iterator.hasNext();)
            {
                Statement statement = iterator.nextStatement();


                if (actualDBpediaModel.contains(statement))
                {
                    if (Config.isRelativeMappings())
                    {
                        datastore.getMapStatementHasMapping().put(statement.hashCode(), true);
                        resultSet.tripleTEC.present.add(statement, datastore);
                    }
                    else
                    {
                        if (datastore.hasMapping(statement))
                        {
                            resultSet.tripleTEC.present.add(statement, datastore);
                        }
                        else
                        {
                            resultSet.tripleTEC.missing.add(statement, datastore);
                        }
                    }
                }
                else
                {
                    resultSet.tripleTEC.missing.add(statement, datastore);
                }
            }
        }
        //startResultSetOptimal.resourceNodeStmts.resources.urls.printStmts();
        System.out.println("done !");
        System.out.println();
    }

    /**
     * - remove firstResultSetPresent triples from actualDBpediaModel to avoid that they reappear in the SO comparison
     * - iterate over firstResultSetMissing
     * -- if statements in the actualDBpediaSubModel match S?O gold triple
     * --- iterate over actualDBpediaSubModel triples which match gold S?O triple
     * ---- put actual S?O triples to secondResultSetPresentActual (to remove them for smiliarity comparison step)
     * --- ERROR SOURCE put S?O gold triple to secondResultSetPresent
     * -- else: no triple in actual that matches S?O triple
     * --- put S?O gold triple to secondResultSetMissing
     *
     * ERROR SOURCE: There could be more than one gold triple that have same subject and object.
     * For instance a film wich has the same person as director, auhtor and actor. If only the director is
     * extracted, the director triple is taken from the DBpedia model. SORRY ITS OK: no S?O triple is left
     * in the DBpedia model. NO ITS NOT OK: True the triples is taken frmo the dbpedia model. But sometimes
     * there are predicates that doesen match the gold standard, so they contain in the dbpedia model.
     * If now two triples with same s. and o. are in the missing triples model, both would be marked as found.
     * POSSIBLE SOLUTIONS: Take the S?O machting triple in the dbpedia model from it. DONE
     *
     */
    public void predicateNeutralityComparison()
    {
        System.out.println("============================================================");
        System.out.println("Predicate Neutrality comparison");
        System.out.println("============================================================");
        // remove triples that are found in the actualDBpediaModel from it,
        // to avoid that they reappear in the PN comparison
        actualDBpediaSubModel = actualDBpediaModel.remove(resultSet.tripleTEC.getPresentTriples());

        // S-?-O comparison
        for (StmtIterator missingStmtIterator = resultSet.tripleTEC.getMissingTriples().listStatements(); missingStmtIterator.hasNext();)
        {
            Statement missingStmt = (Statement) missingStmtIterator.next();
            if (datastore.hasMapping(missingStmt))
            {
                // exist S?O statements in the actualDBpediaSubModel?
                if (actualDBpediaSubModel.contains(missingStmt.getSubject(), null, missingStmt.getObject()))
                {
//                printStatement(missingStmt, "The missing optimal statement: ");
//                System.out.println("is present with the following predicates:");
                    boolean present = false;

                    // iterate over present S?O triples
                    for (StmtIterator soPresentStmtItr = actualDBpediaSubModel.listStatements(
                            missingStmt.getSubject(), null, missingStmt.getObject());
                         soPresentStmtItr.hasNext();)
                    {
                        Statement soPresentStmt = soPresentStmtItr.nextStatement();
                        // only if the soPresentStmt dosen't counted before.
                        if (!resultSet.triplePNCactual.getTriples().contains(soPresentStmt))
                        {
                            resultSet.triplePNCactual.present.add(soPresentStmt, datastore);
                            present = true;
                        }
                        //System.out.println(soPresentStmt.getPredicate().getURI());
                    }
                    if (present)
                    {
                        // no need for a additional handling of false relativeMappings, because only mapped statements ar considered here: see first IF in method
                        if (Config.isRelativeMappings())
                        {
                            datastore.getMapStatementHasMapping().put(missingStmt.hashCode(), true);
                        }
                        resultSet.triplePNC.present.add(missingStmt, datastore);
                    }
                    else
                    {
                        resultSet.triplePNC.missing.add(missingStmt, datastore);
                    }
                }
                else
                {
                    resultSet.triplePNC.missing.add(missingStmt, datastore);
                }
            }
            else
            {
                resultSet.triplePNC.missing.add(missingStmt, datastore);
            }

        }
        System.out.println("done !");
        System.out.println();
    }

    /**
     * - remove secondResultSetPresentActual from actualDBpediaSubModel to avoid that they reappear in the similarity comparison
     * - iterate over triples from secondResultSetMissing
     * - if actualDBpediaSubSubModel matches a SP? gold triple
     * -- compare similarity of objects and in case of similar mark as present
     * - else mark as missing
     * - if missing triple is intermediate node
     * -- iterate over all actualDBpediaSubSub triples with the same predicate as the missing gold triple
     * -- compare the subject, the same entity prefix is needed in missing gold triple and interm. node,#
     *    so that one can say the intm. node belongs to the missing gold triple.
     * -- if it does: subject fits and predicate is the same, so compare the object
     *    and mark as present or missing.
     *
     */
    public void objectSimilarityComparison() throws Exception
    {
        System.out.println("============================================================");
        System.out.println("Object Similarity Comparison");
        System.out.println("============================================================");

//        int x = 0;
//        int y = 0;
        // remove triples that are found in the actualDBpediaModel from the actualDBpediaSubModel,
        // to avoid that they reappear in the similarity comparison
        actualDBpediaSubSubModel = ModelFactory.createDefaultModel();
        actualDBpediaSubSubModel = actualDBpediaSubModel.remove(resultSet.triplePNCactual.getTriples());
        //System.out.println("secondResultSetPresentActual.size: " + secondResultSetPresentActual.getNumberOfStatements());
        //System.out.println("optimalStatementsModel.size: " + optimalStatementsModel.size());
        // TODO: why identical?
        //System.out.println("actualDBpediaSubModel.size: " + actualDBpediaSubModel.size());
        //System.out.println("actualDBpediaSubSubModel.size: " + actualDBpediaSubSubModel.size());
        //System.out.println("secondResultSetMissing.size: " + secondResultSetMissing.getNumberOfStatements());

        for (StmtIterator missingSOStmtItr = resultSet.triplePNC.getMissingTriples().listStatements(); missingSOStmtItr.hasNext();)
        {
            Statement missingSOStmt = (Statement) missingSOStmtItr.nextStatement();

            if (datastore.hasMapping(missingSOStmt) == false && Config.isRelativeMappings() == false)
            {
                resultSet.tripleOSC.missing.add(missingSOStmt, datastore);
            }
            else
            {
                //RDFNode missingObject = missingSOStmt.getObject();

                // TODO: DONE! similarity comparison - collect the wrong extracted data
                // TODO: DONE! solve problem with multiple added statements.
                // ex. two properties like : <name> <award> <?>
                // for each property, two statements would added, so four all in all
                if (actualDBpediaSubSubModel.contains(missingSOStmt.getSubject(), missingSOStmt.getPredicate()))
                {
                    SimpleSelector selector = new SimpleSelector(missingSOStmt.getSubject(), missingSOStmt.getPredicate(), (Object) null);
                    for (StmtIterator actualStmtItr = actualDBpediaSubSubModel.listStatements(selector); actualStmtItr.hasNext();)
                    {
                        Statement actualStmt = (Statement) actualStmtItr.nextStatement();
                        sortStatementsByObjectSimilarity(missingSOStmt, actualStmt, Config.isPrintDeviations());
                    }
                }
                else
                {
                    if (!resultSet.tripleOSC.getMissingTriples().contains(missingSOStmt))
                    {
                        resultSet.tripleOSC.missing.add(missingSOStmt, datastore);
                    }
                }

                /**
                 * TODO: DONE! check all missing stmts for intermediate nodes with other uris as i prefer.
                 * the uri creation of my gold standard differs from that used in the framework.
                 * So, they woulden't match against each other.
                 *
                 * if missing triple is intermediate node
                 */
                if (datastore.getListOfIntermediateNodeSubjects().contains(missingSOStmt.getSubject()))
                {
                    //get all triples with the same predicate as the missing gold triple
                    SimpleSelector selector = new SimpleSelector(null, missingSOStmt.getPredicate(), (Object) null);
                    for (StmtIterator actualStmtItr = actualDBpediaSubSubModel.listStatements(selector); actualStmtItr.hasNext();)
                    {
                        Statement actualStmt = (Statement) actualStmtItr.nextStatement();

                        String dbPediaNameSpace = "http://dbpedia.org/resource/";
                        String missingSubjectName = missingSOStmt.getSubject().getURI().replace(dbPediaNameSpace, "");
                        String actualSubjectName = actualStmt.getSubject().getURI().replace(dbPediaNameSpace, "");

                        if (actualSubjectName.contains("__"))
                        {
                            String[] split = missingSubjectName.split("__");
                            if (split[0].length() > 1)
                            {
                                // if intermediate node belongs the the missing gold subject
                                if (actualSubjectName.startsWith(split[0]))
                                {
    //                                System.out.println("############################");
    //                                System.out.println("gold subject start:" + missingSubjectName);
    //                                System.out.println("actual Subject start:" + actualSubjectName);
    //                                printStatement(actualStmt, "actual statement");
    //                                printStatement(missingSOStmt, "gold statement");
    //                                System.out.println("############################");


                                    // subject fits and predicate is the same, so compare the object:
                                    sortStatementsByObjectSimilarity(missingSOStmt, actualStmt, Config.isPrintDeviations());
                                }
                            }
                        }
                    }
                }
            }
        }
        //System.out.println("optimalStatementsModel.size: " + optimalStatementsModel.size());
        //System.out.println("actualDBpediaSubModel.size: " + actualDBpediaSubModel.size());
        //System.out.println("actualDBpediaSubSubModel.size: " + actualDBpediaSubSubModel.size());

        // TODO: why 0?
//        System.out.println("x.size: " + x);
//        System.out.println("y.size: " + y);
        System.out.println("done !");
        System.out.println();
    }

    /**
     *
     */
    public void SnippetCompletenessCheck()
    {
        System.out.println("============================================================");
        System.out.println("Snippet Completeness Check");
        System.out.println("============================================================");
        //int numSnippets = datastore.getMapSnippetToGoldModel().size();
        //System.out.println("number of snippets: " + numSnippets);
        Iterator hmItr = datastore.getMapSnippetToGoldModel().keySet().iterator();
        while (hmItr.hasNext())
        {
            Snippet snippet = (Snippet) hmItr.next();
            Model snippetModel = (Model) datastore.getMapSnippetToGoldModel().get(snippet);
            if (snippet.getTriple() != null && !snippet.getTriple().trim().equals(""))
            {
                resultSet.snippetGoldStandard.present.add(snippet, datastore);
                if (resultSet.tripleTEC.getPresentTriples().containsAll(snippetModel))
                {
                    resultSet.snippetTEC.present.add(snippet, datastore);
                }
                else
                {
                    resultSet.snippetTEC.missing.add(snippet, datastore);

                    if (resultSet.tripleTEC.getPresentTriples().add(resultSet.triplePNC.getPresentTriples()).containsAll(snippetModel))
                    {
                        resultSet.snippetPNC.present.add(snippet, datastore);
                    }
                    else
                    {
                        resultSet.snippetPNC.missing.add(snippet, datastore);

                        if (resultSet.tripleTEC.getPresentTriples().add(
                                resultSet.triplePNC.getPresentTriples()).add(
                                resultSet.tripleOSC.getPresentTriples()).containsAll(snippetModel))
                        {
                            resultSet.snippetOSC.present.add(snippet, datastore);
                        }
                        else
                        {
                            resultSet.snippetOSC.missing.add(snippet, datastore);
                        }
                    }
                }
            }
        }
        System.out.println("done !");
        System.out.println();
    }

    public static void printStatement(Statement stmt, String title)
    {
        if (title != null)
        {
            System.out.println(title);
        }
        System.out.println("S: " + stmt.getSubject().getURI());
        System.out.println("P: " + stmt.getPredicate().getURI());
        System.out.print("O: ");
        if (stmt.getObject().isLiteral())
        {
            System.out.print("\"" + stmt.getObject().asLiteral().getString() + "\"");
            if (stmt.getObject().asLiteral().getDatatypeURI() != null)
            {
                System.out.println("^^" + stmt.getObject().asLiteral().getDatatypeURI());
            }
            else if (stmt.getObject().asLiteral().getLanguage() != null)
            {
                System.out.println("@" + stmt.getObject().asLiteral().getLanguage());
            }
            else
            {
                System.out.println();
            }
        }
        else if (stmt.getObject().isResource())
        {
            System.out.println(stmt.getObject().asResource().getURI());
        }
    }

    private boolean isLiteralAnInt(Literal lit)
    {
        if (!isLiteralAnDbl(lit))
        {
            try
            {
                int intValue = lit.getInt();
            }
            catch (Exception numEx)
            {
                return false;
            }
            return true;
        }
        return false;
    }

    private boolean isLiteralAnDbl(Literal lit)
    {
        try
        {
            double dblValue = lit.getDouble();

        }
        catch (Exception numEx)
        {
            return false;
        }
        return true;
    }

    /**
     * TODO: the allowed deviation should be number dependend
     *
     * @param num
     * @param rangeMid
     * @param rangeInPercent
     * @return
     */
    private boolean isNumberInRange(int num, int rangeMid, double rangeInPercent)
    {
        double range = (rangeMid / 100) * rangeInPercent;
        double lowerBound = rangeMid - range;
        double upperBound = rangeMid + range;

        if (lowerBound <= num && num <= upperBound)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Calculates the deviation from numA to numB.
     *
     * A = 5
     * B = 10
     * (5 - 10) / 10 = - 0.5 = - 50 %
     *
     * A = 10
     * B = 5
     * (10 - 5) / 5 = 1 = 100%
     *
     * A = 100
     * B = 10
     * (100 - 10) / 10 = 9 = 900%
     *
     * A = 10
     * B = 100
     * (10 - 100) / 100 = - 0.9 = - 90%
     *
     * @param numA
     * @param numB
     * @return
     */
    private double deviation(int numA, int numB)
    {
        int absolut = numA - numB;
        double dev = absolut / numB;
        return dev;
    }

    /**
     * TODO: the allowed deviation should be number dependend
     *
     * @param num
     * @param rangeMid
     * @param rangeInPercent
     * @return
     */
    private boolean isNumberInRange(double num, double rangeMid, double rangeInPercent)
    {
        double range = (rangeMid / 100) * rangeInPercent;
        double lowerBound = rangeMid - range;
        double upperBound = rangeMid + range;

        if (lowerBound <= num && num <= upperBound)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    private void sortStatementsByObjectSimilarity(Statement missingStmt, Statement actualStmt, boolean printDecision)
    {

        RDFNode missingObject = missingStmt.getObject();
        RDFNode actualObject = actualStmt.getObject();

        // BOTH OBJECTS ARE LITERALS
        if (actualObject.isLiteral() && missingObject.isLiteral())
        {
            // BOTH LITERALS ARE INTEGER
            if (isLiteralAnInt(actualObject.asLiteral())
                    && isLiteralAnInt(missingObject.asLiteral()))
            {
                int intValueA = missingObject.asLiteral().getInt();
                int intValueB = actualObject.asLiteral().getInt();
                if (isNumberInRange(intValueA, intValueB, 0.1))
                {
                    if (Config.isRelativeMappings())
                    {
                        datastore.getMapStatementHasMapping().put(missingStmt.hashCode(), true);
                    }
                    resultSet.tripleOSC.present.add(missingStmt, datastore);

                    // if triple is already marked as missing: unmark it
                    if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                    {
                        try
                        {
                            resultSet.tripleOSC.missing.remove(missingStmt, datastore);
                            if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                            {
                                throw new Exception("Statement is still found in datastore.");
                            }
                        }
                        catch (Exception ex)
                        {
                            System.out.println("Failed to remove a statement from datastore.");
                            printStatement(missingStmt, null);
                            System.out.println("Error Msg: " + ex.getMessage());
                        }
                    }

                    // if triple is already marked as wrong: unmark it
                    if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                    {
                        try
                        {
                            resultSet.tripleOSCwrong.present.remove(missingStmt, datastore);
                            if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                            {
                                throw new Exception("Statement is still found in datastore.");
                            }
                        }
                        catch (Exception ex)
                        {
                            System.out.println("Failed to remove a statement from datastore.");
                            printStatement(missingStmt, null);
                            System.out.println("Error Msg: " + ex.getMessage());
                        }
                    }

                    if (printDecision)
                    {
                        System.out.println("Number has acceptable deviation.");
                        printStatement(missingStmt, "gold triple:");
                        printStatement(actualStmt, "actual triple:");
                        System.out.println();
                    }
                }
                else // triple objects aren't similar
                {
                    if (!resultSet.tripleOSC.getMissingTriples().contains(missingStmt) && !resultSet.tripleOSC.getPresentTriples().contains(missingStmt))
                    {
                        resultSet.tripleOSCwrong.present.add(missingStmt, datastore);
                        resultSet.tripleOSC.missing.add(missingStmt, datastore);
                        if (printDecision)
                        {
                            System.out.println("Number has unacceptable deviation.");
                            printStatement(missingStmt, "gold triple:");
                            printStatement(actualStmt, "actual triple:");
                            System.out.println();
                        }
                    }
                }
            }
            // BOTH LITERALS ARE DOUBLE
            else if (isLiteralAnDbl(actualObject.asLiteral())
                    && isLiteralAnDbl(missingObject.asLiteral()))
            {
                double dblValueM = actualObject.asLiteral().getDouble();
                double dblValueA = missingObject.asLiteral().getDouble();
                if (isNumberInRange(dblValueA, dblValueM, 0.1))
                {
                    if (Config.isRelativeMappings())
                    {
                        datastore.getMapStatementHasMapping().put(missingStmt.hashCode(), true);
                    }
                    resultSet.tripleOSC.present.add(missingStmt, datastore);

                    // if triple is already marked as missing: unmark it
                    if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                    {
                        try
                        {
                            resultSet.tripleOSC.missing.remove(missingStmt, datastore);
                            if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                            {
                                throw new Exception("Statement is still found in datastore.");
                            }
                        }
                        catch (Exception ex)
                        {
                            System.out.println("Failed to remove a statement from datastore.");
                            printStatement(missingStmt, null);
                            System.out.println("Error Msg: " + ex.getMessage());
                        }
                    }

                    // if triple is already marked as wrong: unmark it
                    if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                    {
                        try
                        {
                            resultSet.tripleOSCwrong.present.remove(missingStmt, datastore);
                            if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                            {
                                throw new Exception("Statement is still found in datastore.");
                            }
                        }
                        catch (Exception ex)
                        {
                            System.out.println("Failed to remove a statement from datastore.");
                            printStatement(missingStmt, null);
                            System.out.println("Error Msg: " + ex.getMessage());
                        }
                    }
                    if (printDecision)
                    {
                        System.out.println("Number has acceptable deviation.");
                        printStatement(missingStmt, "gold triple:");
                        printStatement(actualStmt, "actual triple:");
                        System.out.println();
                    }
                }
                else
                {
                    if (!resultSet.tripleOSC.getMissingTriples().contains(missingStmt) && !resultSet.tripleOSC.getPresentTriples().contains(missingStmt))
                    {
                        resultSet.tripleOSCwrong.present.add(missingStmt, datastore);
                        resultSet.tripleOSC.missing.add(missingStmt, datastore);
                        if (printDecision)
                        {
                            System.out.println("Number has unacceptable deviation.");
                            printStatement(missingStmt, "gold triple:");
                            printStatement(actualStmt, "actual triple:");
                            System.out.println();
                        }
                    }
                }
            }
            // OTHER CASES
            else
            {
                String valueMO = actualObject.asLiteral().getString();
                String valueAO = missingObject.asLiteral().getString();
                Float ld = LevenshteinDistance.getSimilarity(valueMO, valueAO);
                if (ld > 0.8)
                {
                    if (Config.isRelativeMappings())
                    {
                        datastore.getMapStatementHasMapping().put(missingStmt.hashCode(), true);
                    }
                    resultSet.tripleOSC.present.add(missingStmt, datastore);

                    // if triple is already marked as missing: unmark it
                    if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                    {
                        try
                        {
                            resultSet.tripleOSC.missing.remove(missingStmt, datastore);
                            if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                            {
                                throw new Exception("Statement is still found in datastore.");
                            }
                        }
                        catch (Exception ex)
                        {
                            System.out.println("Failed to remove a statement from datastore.");
                            printStatement(missingStmt, null);
                            System.out.println("Error Msg: " + ex.getMessage());
                        }
                    }

                    // if triple is already marked as wrong: unmark it
                    if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                    {
                        try
                        {
                            resultSet.tripleOSCwrong.present.remove(missingStmt, datastore);
                            if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                            {
                                throw new Exception("Statement is still found in datastore.");
                            }
                        }
                        catch (Exception ex)
                        {
                            System.out.println("Failed to remove a statement from datastore.");
                            printStatement(missingStmt, null);
                            System.out.println("Error Msg: " + ex.getMessage());
                        }
                    }

                    if (printDecision)
                    {
                        System.out.println("Object has acceptable deviation: LD = " + ld);
                        printStatement(missingStmt, "gold triple:");
                        printStatement(actualStmt, "actual triple:");
                        System.out.println();
                    }
                }
                else
                {
                    if (!resultSet.tripleOSC.getMissingTriples().contains(missingStmt) && !resultSet.tripleOSC.getPresentTriples().contains(missingStmt))
                    {
                        resultSet.tripleOSCwrong.present.add(missingStmt, datastore);
                        resultSet.tripleOSC.missing.add(missingStmt, datastore);
                        if (printDecision)
                        {
                            System.out.println("Object has unacceptable deviation: LD = " + ld);
                            printStatement(missingStmt, "gold triple:");
                            printStatement(actualStmt, "actual triple:");
                            System.out.println();
                        }
                    }
                }
            }
        }
        // BOTH OBJECTS ARE RESOURCES
        else if (actualObject.isResource() && missingObject.isResource())
        {
            String valueMO = actualObject.asResource().getURI().replace("http://dbpedia.org/resource/", "");
            String valueAO = missingObject.asResource().getURI().replace("http://dbpedia.org/resource/", "");
            Float ld = LevenshteinDistance.getSimilarity(valueMO, valueAO);
            if (ld > 0.8)
            {
                if (Config.isRelativeMappings())
                {
                    datastore.getMapStatementHasMapping().put(missingStmt.hashCode(), true);
                }
                resultSet.tripleOSC.present.add(missingStmt, datastore);

                // if triple is already marked as missing: unmark it
                if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                {
                    try
                    {
                        resultSet.tripleOSC.missing.remove(missingStmt, datastore);
                        if (resultSet.tripleOSC.getMissingTriples().contains(missingStmt))
                        {
                            throw new Exception("Statement is still found in datastore.");
                        }
                    }
                    catch (Exception ex)
                    {
                        System.out.println("Failed to remove a statement from datastore.");
                        printStatement(missingStmt, null);
                        System.out.println("Error Msg: " + ex.getMessage());
                    }
                }

                // if triple is already marked as wrong: unmark it
                if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                {
                    try
                    {
                        resultSet.tripleOSCwrong.present.remove(missingStmt, datastore);
                        if (resultSet.tripleOSCwrong.getTriples().contains(missingStmt))
                        {
                            throw new Exception("Statement is still found in datastore.");
                        }
                    }
                    catch (Exception ex)
                    {
                        System.out.println("Failed to remove a statement from datastore.");
                        printStatement(missingStmt, null);
                        System.out.println("Error Msg: " + ex.getMessage());
                    }
                }

                if (printDecision)
                {
                    System.out.println("Object has acceptable deviation: LD = " + ld);
                    printStatement(missingStmt, "gold triple:");
                    printStatement(actualStmt, "actual triple:");
                    System.out.println();
                }
            }
            else
            {
                if (!resultSet.tripleOSC.getMissingTriples().contains(missingStmt) && !resultSet.tripleOSC.getPresentTriples().contains(missingStmt))
                {
                    resultSet.tripleOSCwrong.present.add(missingStmt, datastore);
                    resultSet.tripleOSC.missing.add(missingStmt, datastore);
                    if (printDecision)
                    {
                        System.out.println("Object has unacceptable deviation: LD = " + ld);
                        printStatement(missingStmt, "gold triple:");
                        printStatement(actualStmt, "actual triple:");
                        System.out.println();
                    }
                }
            }
        }
        // DIFFERENT OBJECT TYPES
        else
        {
            if (!resultSet.tripleOSC.getMissingTriples().contains(missingStmt) && !resultSet.tripleOSC.getPresentTriples().contains(missingStmt))
            {
                resultSet.tripleOSCwrong.present.add(missingStmt, datastore);
                resultSet.tripleOSC.missing.add(missingStmt, datastore);
                if (printDecision)
                {
                    System.out.println("Different object types");
                    printStatement(missingStmt, "gold triple:");
                    printStatement(actualStmt, "actual triple:");
                    System.out.println();
                }
            }
        }
    }

    public void resultHandler()
    {
        if (Config.isPrintResult())
        {
            resultSet.printResults(datastore);
        }
        if (Config.isExportToExcel())
        {
            resultSet.exportToExcel(datastore);
        }
    }
}
