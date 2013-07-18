/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Statement;
import dbPediaQAF.util.PatternCategory;
import dbPediaQAF.xmlQuery.Snippet;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

/**
 *
 * @author Paul
 */
public class CategorySwitch implements Switch
{

    public MappingSwitch plainProperty = new MappingSwitch();
    public MappingSwitch numberUnits = new MappingSwitch();
    public MappingSwitch coordinates = new MappingSwitch();
    public MappingSwitch lists = new MappingSwitch();
    public MappingSwitch intervals = new MappingSwitch();
    public MappingSwitch onePropertyTables = new MappingSwitch();
    public MappingSwitch multiPropertyTables = new MappingSwitch();
    public MappingSwitch openProperties = new MappingSwitch();
    public MappingSwitch openPropertyTables = new MappingSwitch();
    public MappingSwitch internalTemplates = new MappingSwitch();
    public MappingSwitch mergedProperties = new MappingSwitch();
    public MappingSwitch toDos = new MappingSwitch();

    public Map getResult() {
        HashMap<PatternCategory, HashMap> resultMap = new HashMap<PatternCategory, HashMap>();



        return resultMap;
    }


    public void add(Statement stmt, DataSet datastore)
    {
        if (datastore.getMapStatementToPatternCategory().containsKey(stmt.hashCode()))
        {
            String pc = datastore.getMapStatementToPatternCategory().get(stmt.hashCode()).toString();
            switch (PatternCategory.toPatternCategory(pc))
            {
                case PlainProperty:
                    plainProperty.add(stmt, datastore);
                    break;
                case NumberUnit:
                    numberUnits.add(stmt, datastore);
                    break;
                case Coordinates:
                    coordinates.add(stmt, datastore);
                    break;
                case List:
                    lists.add(stmt, datastore);
                    break;
                case Interval:
                    intervals.add(stmt, datastore);
                    break;
                case OnePropertyTable:
                    onePropertyTables.add(stmt, datastore);
                    break;
                case MultiPropertyTable:
                    multiPropertyTables.add(stmt, datastore);
                    break;
                case OpenProperty:
                    openProperties.add(stmt, datastore);
                    break;
                case OpenPropertyTable:
                    openPropertyTables.add(stmt, datastore);
                    break;
                case InternalTemplate:
                    internalTemplates.add(stmt, datastore);
                    break;
                case MergedProperties:
                    mergedProperties.add(stmt, datastore);
                    break;
                case ToDo:
                    toDos.add(stmt, datastore);
                    break;
            }
        }
    }

    public void add(Snippet snpt, DataSet datastore)
    {
        String patternCategory = snpt.getPatternClass();
        switch (PatternCategory.toPatternCategory(patternCategory))
        {
            case PlainProperty:
                plainProperty.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                //System.out.println("Triples:");
                //System.out.println(snippet.getTriple());
                break;
            case NumberUnit:
                numberUnits.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                //System.out.println("Triples:");
                //System.out.println(snippet.getTriple());
                break;
            case Coordinates:
                coordinates.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                //System.out.println("Triples:");
                //System.out.println(snippet.getTriple());
                break;
            case Interval:
                intervals.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                //System.out.println("Triples:");
                //System.out.println(snippet.getTriple());
                break;
            case List:
                lists.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                //System.out.println("Triples:");
                //System.out.println(snippet.getTriple());
                break;
            case OnePropertyTable:
                onePropertyTables.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                break;
            case MultiPropertyTable:
                multiPropertyTables.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                break;
            case OpenProperty:
                openProperties.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                break;
            case OpenPropertyTable:
                openPropertyTables.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                break;
            case InternalTemplate:
                internalTemplates.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                break;
            case MergedProperties:
                mergedProperties.add(snpt, datastore);
                //System.out.println("PatternCategory:" + snippet.getPatternClass());
                break;
            case ToDo:
                toDos.add(snpt, datastore);
                //System.out.println("ToDo:" + snippet.getPatternClass());
                break;
            default:
                //toDos.add(snpt, datastore);
                //System.out.println("NO VALUE");
                break;
        }
    }

    public void remove(Statement stmt, DataSet datastore)
    {
        if (datastore.getMapStatementToPatternCategory().containsKey(stmt.hashCode()))
        {
            String pc = datastore.getMapStatementToPatternCategory().get(stmt.hashCode()).toString();
            switch (PatternCategory.toPatternCategory(pc))
            {
                case PlainProperty:
                    plainProperty.remove(stmt, datastore);
                    break;
                case NumberUnit:
                    numberUnits.remove(stmt, datastore);
                    break;
                case Coordinates:
                    coordinates.remove(stmt, datastore);
                    break;
                case List:
                    lists.remove(stmt, datastore);
                    break;
                case Interval:
                    intervals.remove(stmt, datastore);
                    break;
                case OnePropertyTable:
                    onePropertyTables.remove(stmt, datastore);
                    break;
                case MultiPropertyTable:
                    multiPropertyTables.remove(stmt, datastore);
                    break;
                case OpenProperty:
                    openProperties.remove(stmt, datastore);
                    break;
                case OpenPropertyTable:
                    openPropertyTables.remove(stmt, datastore);
                    break;
                case InternalTemplate:
                    internalTemplates.remove(stmt, datastore);
                    break;
                case MergedProperties:
                    mergedProperties.remove(stmt, datastore);
                    break;
                case ToDo:
                    toDos.remove(stmt, datastore);
                    break;
            }
        }
    }

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(plainProperty.getTriples());
        m.add(numberUnits.getTriples());
        m.add(coordinates.getTriples());
        m.add(lists.getTriples());
        m.add(intervals.getTriples());
        m.add(onePropertyTables.getTriples());
        m.add(multiPropertyTables.getTriples());
        m.add(openProperties.getTriples());
        m.add(openPropertyTables.getTriples());
        m.add(internalTemplates.getTriples());
        m.add(mergedProperties.getTriples());
        m.add(toDos.getTriples());
        return m;
    }

    public Model getMappedTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(plainProperty.getMappedTriples());
        m.add(numberUnits.getMappedTriples());
        m.add(coordinates.getMappedTriples());
        m.add(lists.getMappedTriples());
        m.add(intervals.getMappedTriples());
        m.add(onePropertyTables.getMappedTriples());
        m.add(multiPropertyTables.getMappedTriples());
        m.add(openProperties.getMappedTriples());
        m.add(openPropertyTables.getMappedTriples());
        m.add(internalTemplates.getMappedTriples());
        m.add(mergedProperties.getMappedTriples());
        m.add(toDos.getMappedTriples());
        return m;
    }

    public Model getNotMappedTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(plainProperty.getNotMappedTriples());
        m.add(numberUnits.getNotMappedTriples());
        m.add(coordinates.getNotMappedTriples());
        m.add(lists.getNotMappedTriples());
        m.add(intervals.getNotMappedTriples());
        m.add(onePropertyTables.getNotMappedTriples());
        m.add(multiPropertyTables.getNotMappedTriples());
        m.add(openProperties.getNotMappedTriples());
        m.add(openPropertyTables.getNotMappedTriples());
        m.add(internalTemplates.getNotMappedTriples());
        m.add(mergedProperties.getNotMappedTriples());
        m.add(toDos.getNotMappedTriples());
        return m;
    }

    public List<Snippet> getSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(plainProperty.getSnippets());
        list.addAll(numberUnits.getSnippets());
        list.addAll(coordinates.getSnippets());
        list.addAll(lists.getSnippets());
        list.addAll(intervals.getSnippets());
        list.addAll(onePropertyTables.getSnippets());
        list.addAll(multiPropertyTables.getSnippets());
        list.addAll(openProperties.getSnippets());
        list.addAll(openPropertyTables.getSnippets());
        list.addAll(internalTemplates.getSnippets());
        list.addAll(mergedProperties.getSnippets());
        list.addAll(toDos.getSnippets());
        return list;
    }

    public List<Snippet> getMappedSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(plainProperty.getMappedSnippets());
        list.addAll(numberUnits.getMappedSnippets());
        list.addAll(coordinates.getMappedSnippets());
        list.addAll(lists.getMappedSnippets());
        list.addAll(intervals.getMappedSnippets());
        list.addAll(onePropertyTables.getMappedSnippets());
        list.addAll(multiPropertyTables.getMappedSnippets());
        list.addAll(openProperties.getMappedSnippets());
        list.addAll(openPropertyTables.getMappedSnippets());
        list.addAll(internalTemplates.getMappedSnippets());
        list.addAll(mergedProperties.getMappedSnippets());
        list.addAll(toDos.getMappedSnippets());
        return list;
    }

    public List<Snippet> getNotMappedSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(plainProperty.getNotMappedSnippets());
        list.addAll(numberUnits.getNotMappedSnippets());
        list.addAll(coordinates.getNotMappedSnippets());
        list.addAll(lists.getNotMappedSnippets());
        list.addAll(intervals.getNotMappedSnippets());
        list.addAll(onePropertyTables.getNotMappedSnippets());
        list.addAll(multiPropertyTables.getNotMappedSnippets());
        list.addAll(openProperties.getNotMappedSnippets());
        list.addAll(openPropertyTables.getNotMappedSnippets());
        list.addAll(internalTemplates.getNotMappedSnippets());
        list.addAll(mergedProperties.getNotMappedSnippets());
        list.addAll(toDos.getNotMappedSnippets());
        return list;
    }

    public int size()
    {
        int num = (int) plainProperty.getTriples().size()
                + (int) numberUnits.getTriples().size()
                + (int) coordinates.getTriples().size()
                + (int) lists.getTriples().size()
                + (int) intervals.getTriples().size()
                + (int) onePropertyTables.getTriples().size()
                + (int) multiPropertyTables.getTriples().size()
                + (int) openProperties.getTriples().size()
                + (int) openPropertyTables.getTriples().size()
                + (int) internalTemplates.getTriples().size()
                + (int) mergedProperties.getTriples().size()
                + (int) toDos.getTriples().size();
        return num;
    }

    public void printStats()
    {
        System.out.println("Comparison Result:");
        System.out.println("Overall triples: " + size());
        System.out.println("NoClass: " + plainProperty.getTriples().size() + ":" + plainProperty.withMapping.getTriples().size());
        System.out.println("NumberUnit: " + numberUnits.getTriples().size() + ":" + numberUnits.withMapping.getTriples().size());
        System.out.println("Coordinate: " + coordinates.getTriples().size() + ":" + coordinates.withMapping.getTriples().size());
        System.out.println("Range: " + intervals.getTriples().size() + ":" + intervals.withMapping.getTriples().size());
        System.out.println("List: " + lists.getTriples().size() + ":" + lists.withMapping.getTriples().size());
        System.out.println("OnePropertyTable: " + onePropertyTables.getTriples().size());
        System.out.println("MultiPropertyTable: " + multiPropertyTables.getTriples().size());
        System.out.println("PredicateObjectRelation: " + openProperties.getTriples().size());
        System.out.println("PredicateObjectRelationTable: " + openPropertyTables.getTriples().size());
        System.out.println("InternalTemplate: " + internalTemplates.getTriples().size());
        System.out.println("MergedProperties: " + mergedProperties.getTriples().size());
        System.out.println("ToDo: " + toDos.getTriples().size());

    }

    public String getTripleCSV()
    {
        String csvStats = "";
        csvStats = csvStats
                + "Category"
                + ",absolut"
                + ",present"
                + ",missing"
                + ",present with Mapping"
                + ",missing with Mapping"
                + ",present without Mapping"
                + ",missing without Mapping"
                + "\n";
        csvStats = csvStats
                + "overall"
                + "," + getTriples().size()
                + ",na"
                + ",na"
                + "," + getMappedTriples().size()
                + ",na"
                + "," + getNotMappedTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "NoClass"
                + "," + plainProperty.getTriples().size()
                + ",na"
                + ",na"
                + "," + plainProperty.withMapping.getTriples().size()
                + ",na"
                + "," + plainProperty.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "NumberUnit"
                + "," + numberUnits.getTriples().size()
                + ",na"
                + ",na"
                + "," + numberUnits.withMapping.getTriples().size()
                + ",na"
                + "," + numberUnits.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "Coordinate"
                + "," + coordinates.getTriples().size()
                + ",na"
                + ",na"
                + "," + coordinates.withMapping.getTriples().size()
                + ",na"
                + "," + coordinates.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "Range"
                + "," + intervals.getTriples().size()
                + ",na"
                + ",na"
                + "," + intervals.withMapping.getTriples().size()
                + ",na"
                + "," + intervals.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "List"
                + "," + lists.getTriples().size()
                + ",na"
                + ",na"
                + "," + lists.withMapping.getTriples().size()
                + ",na"
                + "," + lists.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "OnePropertyTable"
                + "," + onePropertyTables.getTriples().size()
                + ",na"
                + ",na"
                + "," + onePropertyTables.withMapping.getTriples().size()
                + ",na"
                + "," + onePropertyTables.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "MultiPropertyTable"
                + "," + multiPropertyTables.getTriples().size()
                + ",na"
                + ",na"
                + "," + multiPropertyTables.withMapping.getTriples().size()
                + ",na"
                + "," + multiPropertyTables.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "PredicateObjectRelation"
                + "," + openProperties.getTriples().size()
                + ",na"
                + ",na"
                + "," + openProperties.withMapping.getTriples().size()
                + ",na"
                + "," + openProperties.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "PredicateObjectRelationTable"
                + "," + openPropertyTables.getTriples().size()
                + ",na"
                + ",na"
                + "," + openPropertyTables.withMapping.getTriples().size()
                + ",na"
                + "," + openPropertyTables.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "InternalTemplate"
                + "," + internalTemplates.getTriples().size()
                + ",na"
                + ",na"
                + "," + internalTemplates.withMapping.getTriples().size()
                + ",na"
                + "," + internalTemplates.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "MergedProperties"
                + "," + mergedProperties.getTriples().size()
                + ",na"
                + ",na"
                + "," + mergedProperties.withMapping.getTriples().size()
                + ",na"
                + "," + mergedProperties.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        csvStats = csvStats
                + "ToDo"
                + "," + toDos.getTriples().size()
                + ",na"
                + ",na"
                + "," + toDos.withMapping.getTriples().size()
                + ",na"
                + "," + toDos.withoutMapping.getTriples().size()
                + ",na"
                + "\n";
        return csvStats;
    }
}
