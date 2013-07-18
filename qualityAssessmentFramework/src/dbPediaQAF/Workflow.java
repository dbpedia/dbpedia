/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF;

import dbPediaQAF.util.*;
import dbPediaQAF.xmlQuery.*;
import java.io.*;
import java.net.URL;
import java.net.URLEncoder;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 *
 * @author Paul
 */
public class Workflow
{

    public static void main(String args[])
    {
        Workflow workflow = new Workflow();
        try
        {
            //workflow.preprocessing();
            workflow.evaluateDataset();
            //workflow.renamePatternCategories();
            //workflow.searchForSnippetsWithoutTriples();
        }
        catch (Exception ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            ex.printStackTrace();
            System.exit(1);
        }
    }

    private void evaluateDataset()
    {
        try
        {
            Evaluator evaluator = new Evaluator();
            System.out.println("Load data ... ");
            evaluator.loadData();
            System.out.println("Done.");
            evaluator.tripleEqualityComparison();
            evaluator.predicateNeutralityComparison();
            evaluator.objectSimilarityComparison();
            evaluator.SnippetCompletenessCheck();
            evaluator.createGoldStandard();
            evaluator.resultHandler();

        }
        catch (Exception ex)
        {
            System.out.println("Msg: " + ex.getMessage());
        }
    }

    private void preprocessing()
    {
        try
        {
            System.out.println("Start preprocessing ... ");
            //Preprocessor.createMappingFiles();
            //Preprocessor.createRelevantTriplesFileFromDBpediaRelease();
            //Preprocessor.createRelevantTriplesFileFromLocalWikiInstance();
            System.out.println("Done.");
        }
        catch (Exception ex)
        {
            System.out.println("Msg: " + ex.getMessage());
        }
    }

    private void countPatternCategories()
    {

        RegexPatternList regexPL = new RegexPatternList();
        List<RegexPattern> regexPatternList = regexPL.getRegexPatternList();
        ArticleItemCol root = null;
        Article article = null;
        File file = null;
        File articleListfile = new File(Config.getArticleListPath());
        try
        {
            root = ArticleItemCol.load(articleListfile);
        }
        catch (IOException ex)
        {
            System.out.println("Msg: " + ex.getMessage());
            ex.printStackTrace();
            //System.exit(1);
        }
        List<ArticleItem> articleItemList = root.getArticleItem();
        for (ArticleItem articleItem : articleItemList)
        {
            if (articleItem.isDone())
            {
                System.out.println();
                System.out.println("################ " + articleItem.getUri() + " ################");
                try
                {
                    file = new File(Config.getDataPath() + URLEncoder.encode(articleItem.getUri(), "UTF-8") + ".xml");
                }
                catch (UnsupportedEncodingException ex)
                {
                    System.out.println("Msg: " + ex.getMessage());
                    ex.printStackTrace();
                    //System.exit(1);
                }
                try
                {
                    article = Article.load(file);
                    List<Snippet> snippetList = article.getSnippet();
                    for (Snippet snippet : snippetList)
                    {
                        // Triples
                        //**
                        String patternTriples = snippet.getTriple();
                        int x = 1;
                        //System.out.println("test1");
                        if (patternTriples != null)
                        {
                            for (String patternTriple : patternTriples.split("\n"))
                            {
                                Pattern p = Pattern.compile("<[^>]+>\\s<[^>]+>");
                                Matcher m = p.matcher(patternTriple);
                                String str = m.replaceFirst("");
                                //System.out.println("test2");
                                //boolean b = m.find();
                                //System.out.println("found: " + b);
                                //System.out.println("Tripelnummer: " + x);
//                                System.out.println(patternTriple);
//                                if (patternTriple.equals("<http://dbpedia.org/resource/Cape_Town> <http://www.w3.org/2003/01/geo/wgs84_pos#long> \"18.42388888888889\"^^<http://www.w3.org/2001/XMLSchema#float> ."))
//                                {
//                                    System.out.println();
//                                }
                                //System.out.println("Rest: " + str);
                                x++;
                                boolean baseCategoryFound = false;
                                for (RegexPattern regexPattern : regexPatternList)
                                {
                                    Matcher patternMatcher = regexPattern.getPattern().matcher(str);
                                    if (patternMatcher.find() && baseCategoryFound == false)
                                    {
                                        baseCategoryFound = true;
                                        switch (regexPattern.getBaseCategory())
                                        {
                                            case Integer:
                                                BaseCategory.Integer.count();
                                                //System.out.println("integer found");
                                                break;
                                            case Double:
                                                BaseCategory.Double.count();
                                                //System.out.println("double found");
                                                break;
                                            case Float:
                                                BaseCategory.Float.count();
                                                //System.out.println("float found");
                                                break;
                                            case Date:
                                                BaseCategory.Date.count();
                                                //System.out.println("date found");
                                                break;
                                            case GYear:
                                                BaseCategory.GYear.count();
                                                //System.out.println("gYear found");
                                                break;
                                            case GYearMonth:
                                                BaseCategory.GYearMonth.count();
                                                //System.out.println("gYearMonth found");
                                                break;
                                            case GMonthDay:
                                                BaseCategory.GMonthDay.count();
                                                //System.out.println("gMonthDay found");
                                                break;
                                            case Time:
                                                BaseCategory.Time.count();
                                                //System.out.println("time found");
                                                break;
                                            case String:
                                                BaseCategory.String.count();
                                                //System.out.println("string found");
                                                break;
                                            case Resource:
                                                BaseCategory.Resource.count();
                                                //System.out.println("resource found");
                                                break;
                                            case Url:
                                                BaseCategory.Url.count();
                                                //System.out.println("url found");
                                                break;
                                            default:
                                                BaseCategory.NOVALUE.count();
                                                //System.out.println("case dosen't exist!");
                                                break;
                                        }
                                        //break;
                                    }
                                }
                                if (baseCategoryFound == false)
                                {
                                    BaseCategory.NOVALUE.count();
                                    System.out.println("NO VALUE found");
                                }
                                //int start = patternTriple.
                                //String thirdNode = patternTriple.substring(bla);
                            }
                        }
                        //**/
                        //System.out.println("###### SNIPPET END ######");
                        // PatternCategories
                        String patternCategory = snippet.getPatternClass();
                        switch (PatternCategory.toPatternCategory(patternCategory))
                        {
                            case PlainProperty:
                                PatternCategory.PlainProperty.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                //System.out.println("Triples:");
                                //System.out.println(snippet.getTriple());
                                break;
                            case NumberUnit:
                                PatternCategory.NumberUnit.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                //System.out.println("Triples:");
                                //System.out.println(snippet.getTriple());
                                break;
                            case Coordinates:
                                PatternCategory.Coordinates.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                //System.out.println("Triples:");
                                //System.out.println(snippet.getTriple());
                                break;
                            case Interval:
                                PatternCategory.Interval.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                //System.out.println("Triples:");
                                //System.out.println(snippet.getTriple());
                                break;
                            case List:
                                PatternCategory.List.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                //System.out.println("Triples:");
                                //System.out.println(snippet.getTriple());
                                break;
                            case OnePropertyTable:
                                PatternCategory.OnePropertyTable.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                break;
                            case MultiPropertyTable:
                                PatternCategory.MultiPropertyTable.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                break;
                            case OpenProperty:
                                PatternCategory.OpenProperty.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                break;
                            case OpenPropertyTable:
                                PatternCategory.OpenPropertyTable.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                break;
                            case InternalTemplate:
                                PatternCategory.InternalTemplate.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                break;
                            case MergedProperties:
                                PatternCategory.MergedProperties.count();
                                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                                break;
                            case ToDo:
                                PatternCategory.ToDo.count();
                                //System.out.println("ToDo:" + snippet.getPatternClass());
                                break;
                            default:
                                PatternCategory.NOVALUE.count();
                                System.out.println("NO VALUE");
                                System.out.println("PatternCategory:" + snippet.getPatternClass());
                                System.out.println("Triples:");
                                System.out.println(snippet.getTriple());
                                break;
                        }
                        //System.out.println("#### SNIPPET END ####");
                    }
                }
                catch (IOException ex)
                {
                    System.out.println("Msg: " + ex.getMessage());
                    ex.printStackTrace();
                    //System.exit(1);
                }
            }
        }

        int sum = 0;
        sum = sum
                + PatternCategory.PlainProperty.getCounter()
                + PatternCategory.Interval.getCounter()
                + PatternCategory.Coordinates.getCounter()
                + PatternCategory.NumberUnit.getCounter()
                + PatternCategory.List.getCounter()
                + PatternCategory.OnePropertyTable.getCounter()
                + PatternCategory.MultiPropertyTable.getCounter()
                + PatternCategory.OpenProperty.getCounter()
                + PatternCategory.OpenPropertyTable.getCounter()
                + PatternCategory.InternalTemplate.getCounter()
                + PatternCategory.MergedProperties.getCounter()
                + PatternCategory.ToDo.getCounter()
                + PatternCategory.NOVALUE.getCounter();

        System.out.println();
        //**
        System.out.println("########### Pattern Class Statistics ###########");
        System.out.println("sum:" + sum);
        System.out.println("NoClass counter:" + PatternCategory.PlainProperty.getCounter());
        System.out.println("NumberUnit counter:" + PatternCategory.NumberUnit.getCounter());
        System.out.println("Coordinates counter:" + PatternCategory.Coordinates.getCounter());
        System.out.println("Range counter:" + PatternCategory.Interval.getCounter());
        System.out.println("List counter:" + PatternCategory.List.getCounter());
        System.out.println("OnePropertyTable counter:" + PatternCategory.OnePropertyTable.getCounter());
        System.out.println("MultiPropertyTable counter:" + PatternCategory.MultiPropertyTable.getCounter());
        System.out.println("PredicateObjectRelation counter:" + PatternCategory.OpenProperty.getCounter());
        System.out.println("PredicateObjectRelationTable counter:" + PatternCategory.OpenPropertyTable.getCounter());
        System.out.println("InternalTemplate counter:" + PatternCategory.InternalTemplate.getCounter());
        System.out.println("MergedProperties counter:" + PatternCategory.MergedProperties.getCounter());
        System.out.println("ToDo counter:" + PatternCategory.ToDo.getCounter());
        System.out.println("NOVALUE counter:" + PatternCategory.NOVALUE.getCounter());
        System.out.println();
        //**/
        System.out.println("########### Base Class Statistics ###########");
        System.out.println("integer counter:" + BaseCategory.Integer.getCounter());
        System.out.println("double counter:" + BaseCategory.Double.getCounter());
        System.out.println("float counter:" + BaseCategory.Float.getCounter());
        System.out.println("date counter:" + BaseCategory.Date.getCounter());
        System.out.println("gYear counter:" + BaseCategory.GYear.getCounter());
        System.out.println("gYearMonth counter:" + BaseCategory.GYearMonth.getCounter());
        System.out.println("gMonthDay counter:" + BaseCategory.GMonthDay.getCounter());
        System.out.println("time counter:" + BaseCategory.Time.getCounter());
        System.out.println("string counter:" + BaseCategory.String.getCounter());
        System.out.println("resource counter:" + BaseCategory.Resource.getCounter());
        System.out.println("url counter:" + BaseCategory.Url.getCounter());
        System.out.println("NO VALUE counter:" + BaseCategory.NOVALUE.getCounter());
    }

    private void renamePatternCategories() throws IOException
    {
        File articleListfile = new File(Config.getArticleListPath());
        File file;
        ArticleItemCol root = ArticleItemCol.load(articleListfile);
        List<ArticleItem> articleItemList = root.getArticleItem();
        for (ArticleItem articleItem : articleItemList)
        {
            //if (articleItem.getUri().equals("Methanol")) {
            if (articleItem.isDone())
            {
                System.out.println(articleItem.getUri());

                file = new File(Config.getDataPath() + URLEncoder.encode(articleItem.getUri(), "UTF-8") + ".xml");
                Article article = Article.load(file);
                List<Snippet> snippetList = article.getSnippet();
                for (Snippet snippet : snippetList)
                {
                    String patternClass = snippet.getPatternClass();
                    switch (OldPatternCategoriesForRename.toOldPatternClass(patternClass))
                    {
                        case NoClass:
                            snippet.setPatternClass(PatternCategory.PlainProperty.toString());
                            System.out.println("Date to new class:" + snippet.getPatternClass());
                            break;
                        case NumberUnit:
                            snippet.setPatternClass(PatternCategory.NumberUnit.toString());
                            System.out.println("NumberUnit to new class:" + snippet.getPatternClass());
                            break;
                        case Coordinates:
                            snippet.setPatternClass(PatternCategory.Coordinates.toString());
                            System.out.println("Coordinate to new class:" + snippet.getPatternClass());
                            break;
                        case Range:
                            snippet.setPatternClass(PatternCategory.Interval.toString());
                            System.out.println("Range to new class:" + snippet.getPatternClass());
                            break;
                        case List:
                            snippet.setPatternClass(PatternCategory.List.toString());
                            System.out.println("List to new class:" + snippet.getPatternClass());
                            break;
                        case OnePropertyTable:
                            snippet.setPatternClass(PatternCategory.OnePropertyTable.toString());
                            System.out.println("OnePropTable to new class:" + snippet.getPatternClass());
                            break;
                        case MultiPropertyTable:
                            snippet.setPatternClass(PatternCategory.MultiPropertyTable.toString());
                            System.out.println("ManyPropTable to new class:" + snippet.getPatternClass());
                            break;
                        case PredicateObjectRelation:
                            snippet.setPatternClass(PatternCategory.OpenProperty.toString());
                            System.out.println("PredObjRel to new class:" + snippet.getPatternClass());
                            break;
                        case PredicateObjectRelationTable:
                            snippet.setPatternClass(PatternCategory.OpenPropertyTable.toString());
                            System.out.println("PredObjRelTable to new class:" + snippet.getPatternClass());
                            break;
                        case InternalTemplate:
                            snippet.setPatternClass(PatternCategory.InternalTemplate.toString());
                            System.out.println("InternTemplate to new class:" + snippet.getPatternClass());
                            break;
                        case MergedProperties:
                            snippet.setPatternClass(PatternCategory.MergedProperties.toString());
                            System.out.println("mergedProperties to new class:" + snippet.getPatternClass());
                            break;
                        default:
                            System.out.println("NO VALUE");
                            break;
                    }
                }
                article.save(file);
            }
        }
    }

    private void searchForSnippetsWithoutTriples()
    {
        System.out.println("start searching");
        File articleListfile = new File(Config.getArticleListPath());
        ArticleItemCol root = null;
        try
        {
            root = ArticleItemCol.load(articleListfile);
        }
        catch (IOException ex)
        {
            Logger.getLogger(Workflow.class.getName()).log(Level.SEVERE, null, ex);
        }
        List<ArticleItem> articleItemList = root.getArticleItem();
        for (ArticleItem articleItem : articleItemList)
        {
//            System.out.println(articleItem.getUri());
            if (articleItem.isDone())
            {
                try
                {
                    File articleFile = new File(Config.getDataPath() + URLEncoder.encode(articleItem.getUri(), "UTF-8") + ".xml");
                    Article article = Article.load(articleFile);
                    System.out.println(article.getName());
                    for (Snippet snippet : article.getSnippet())
                    {
                        System.out.println(snippet.getTriple());
                        if (snippet.getTriple().trim().equals(""))
                        {
                            System.out.println("EMPTY");
                            System.out.println(snippet.getSource());
                        }
                    }
                }
                catch (IOException ex)
                {
                    Logger.getLogger(Workflow.class.getName()).log(Level.SEVERE, null, ex);
                }
                System.out.println("##########################################");
            }
        }
    }
}
