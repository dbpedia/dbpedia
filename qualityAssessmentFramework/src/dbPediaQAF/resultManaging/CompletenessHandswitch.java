/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.resultManaging;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import dbPediaQAF.util.PatternCategory;
import dbPediaQAF.xmlQuery.Snippet;
import java.util.LinkedList;
import java.util.List;

/**
 *
 * @author Paul
 */
public class CompletenessHandswitch
{

    public CategorySwitch present = new CategorySwitch();
    public CategorySwitch missing = new CategorySwitch();

    public Model getTriples()
    {
        Model m = ModelFactory.createDefaultModel();
        m.add(present.getTriples());
        m.add(missing.getTriples());
        return m;
    }

    public Model getTriples(PatternCategory patternCategory)
    {
        Model m = ModelFactory.createDefaultModel();
        switch (patternCategory)
        {
            case PlainProperty:
                m.add(present.plainProperty.getTriples());
                m.add(missing.plainProperty.getTriples());
                break;
            case NumberUnit:
                m.add(present.numberUnits.getTriples());
                m.add(missing.numberUnits.getTriples());
                break;
            case Coordinates:
                m.add(present.coordinates.getTriples());
                m.add(missing.coordinates.getTriples());
                break;
            case List:
                m.add(present.lists.getTriples());
                m.add(missing.lists.getTriples());
                break;
            case Interval:
                m.add(present.intervals.getTriples());
                m.add(missing.intervals.getTriples());
                break;
            case OnePropertyTable:
                m.add(present.onePropertyTables.getTriples());
                m.add(missing.onePropertyTables.getTriples());
                break;
            case MultiPropertyTable:
                m.add(present.multiPropertyTables.getTriples());
                m.add(missing.multiPropertyTables.getTriples());
                break;
            case OpenProperty:
                m.add(present.openProperties.getTriples());
                m.add(missing.openProperties.getTriples());
                break;
            case OpenPropertyTable:
                m.add(present.openPropertyTables.getTriples());
                m.add(missing.openPropertyTables.getTriples());
                break;
            case InternalTemplate:
                m.add(present.internalTemplates.getTriples());
                m.add(missing.internalTemplates.getTriples());
                break;
            case MergedProperties:
                m.add(present.mergedProperties.getTriples());
                m.add(missing.mergedProperties.getTriples());
                break;
            case ToDo:
                m.add(present.toDos.getTriples());
                m.add(missing.toDos.getTriples());
                break;
        }
        return m;
    }

    public Model getPresentTriples()
    {
        return present.getTriples();
    }

    public Model getMissingTriples()
    {
        return missing.getTriples();
    }

    public List<Snippet> getSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(present.getSnippets());
        list.addAll(missing.getSnippets());
        return list;
    }

    public List<Snippet> getSnippets(PatternCategory patternCategory)
    {
        List<Snippet> list = new LinkedList<Snippet>();
        switch (patternCategory)
        {
            case PlainProperty:
                list.addAll(present.plainProperty.getSnippets());
                list.addAll(missing.plainProperty.getSnippets());
                break;
            case NumberUnit:
                list.addAll(present.numberUnits.getSnippets());
                list.addAll(missing.numberUnits.getSnippets());
                break;
            case Coordinates:
                list.addAll(present.coordinates.getSnippets());
                list.addAll(missing.coordinates.getSnippets());
                break;
            case List:
                list.addAll(present.lists.getSnippets());
                list.addAll(missing.lists.getSnippets());
                break;
            case Interval:
                list.addAll(present.intervals.getSnippets());
                list.addAll(missing.intervals.getSnippets());
                break;
            case OnePropertyTable:
                list.addAll(present.onePropertyTables.getSnippets());
                list.addAll(missing.onePropertyTables.getSnippets());
                break;
            case MultiPropertyTable:
                list.addAll(present.multiPropertyTables.getSnippets());
                list.addAll(missing.multiPropertyTables.getSnippets());
                break;
            case OpenProperty:
                list.addAll(present.openProperties.getSnippets());
                list.addAll(missing.openProperties.getSnippets());
                break;
            case OpenPropertyTable:
                list.addAll(present.openPropertyTables.getSnippets());
                list.addAll(missing.openPropertyTables.getSnippets());
                break;
            case InternalTemplate:
                list.addAll(present.internalTemplates.getSnippets());
                list.addAll(missing.internalTemplates.getSnippets());
                break;
            case MergedProperties:
                list.addAll(present.mergedProperties.getSnippets());
                list.addAll(missing.mergedProperties.getSnippets());
                break;
            case ToDo:
                list.addAll(present.toDos.getSnippets());
                list.addAll(missing.toDos.getSnippets());
                break;
        }
        return list;
    }

    public List<Snippet> getMappedSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(present.getMappedSnippets());
        list.addAll(missing.getMappedSnippets());
        return list;
    }

    public List<Snippet> getNotMappedSnippets()
    {
        List<Snippet> list = new LinkedList<Snippet>();
        list.addAll(present.getNotMappedSnippets());
        list.addAll(missing.getNotMappedSnippets());
        return list;
    }

    public void printStats()
    {
        System.out.println("Comparison Result:");
        System.out.println("Overall triples: " + getTriples().size());
        System.out.println("NoClass: " + present.plainProperty.getTriples().size() + ":" + present.plainProperty.withMapping.getTriples().size());
        System.out.println("NumberUnit: " + present.numberUnits.getTriples().size() + ":" + present.numberUnits.withMapping.getTriples().size());
        System.out.println("Coordinate: " + present.coordinates.getTriples().size() + ":" + present.coordinates.withMapping.getTriples().size());
        System.out.println("Range: " + present.intervals.getTriples().size() + ":" + present.intervals.withMapping.getTriples().size());
        System.out.println("List: " + present.lists.getTriples().size() + ":" + present.lists.withMapping.getTriples().size());
        System.out.println("OnePropertyTable: " + present.onePropertyTables.getTriples().size());
        System.out.println("MultiPropertyTable: " + present.multiPropertyTables.getTriples().size());
        System.out.println("PredicateObjectRelation: " + present.openProperties.getTriples().size());
        System.out.println("PredicateObjectRelationTable: " + present.openPropertyTables.getTriples().size());
        System.out.println("InternalTemplate: " + present.internalTemplates.getTriples().size());
        System.out.println("MergedProperties: " + present.mergedProperties.getTriples().size());
        System.out.println("ToDo: " + present.toDos.getTriples().size());

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
                + "," + present.getTriples().size()
                + "," + missing.getTriples().size()
                + "," + present.getMappedTriples().size()
                + "," + missing.getMappedTriples().size()
                + "," + present.getNotMappedTriples().size()
                + "," + missing.getNotMappedTriples().size()
                + "\n";
        csvStats = csvStats
                + "NoClass"
                + "," + getTriples(PatternCategory.PlainProperty).size()
                + "," + present.plainProperty.getTriples().size()
                + "," + missing.plainProperty.getTriples().size()
                + "," + present.plainProperty.withMapping.getTriples().size()
                + "," + missing.plainProperty.withMapping.getTriples().size()
                + "," + present.plainProperty.withoutMapping.getTriples().size()
                + "," + missing.plainProperty.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "NumberUnit"
                + "," + getTriples(PatternCategory.NumberUnit).size()
                + "," + present.numberUnits.getTriples().size()
                + "," + missing.numberUnits.getTriples().size()
                + "," + present.numberUnits.withMapping.getTriples().size()
                + "," + missing.numberUnits.withMapping.getTriples().size()
                + "," + present.numberUnits.withoutMapping.getTriples().size()
                + "," + missing.numberUnits.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "Coordinate"
                + "," + getTriples(PatternCategory.Coordinates).size()
                + "," + present.coordinates.getTriples().size()
                + "," + missing.coordinates.getTriples().size()
                + "," + present.coordinates.withMapping.getTriples().size()
                + "," + missing.coordinates.withMapping.getTriples().size()
                + "," + present.coordinates.withoutMapping.getTriples().size()
                + "," + missing.coordinates.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "Range"
                + "," + getTriples(PatternCategory.Interval).size()
                + "," + present.intervals.getTriples().size()
                + "," + missing.intervals.getTriples().size()
                + "," + present.intervals.withMapping.getTriples().size()
                + "," + missing.intervals.withMapping.getTriples().size()
                + "," + present.intervals.withoutMapping.getTriples().size()
                + "," + missing.intervals.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "List"
                + "," + getTriples(PatternCategory.List).size()
                + "," + present.lists.getTriples().size()
                + "," + missing.lists.getTriples().size()
                + "," + present.lists.withMapping.getTriples().size()
                + "," + missing.lists.withMapping.getTriples().size()
                + "," + present.lists.withoutMapping.getTriples().size()
                + "," + missing.lists.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "OnePropertyTable"
                + "," + getTriples(PatternCategory.OnePropertyTable).size()
                + "," + present.onePropertyTables.getTriples().size()
                + "," + missing.onePropertyTables.getTriples().size()
                + "," + present.onePropertyTables.withMapping.getTriples().size()
                + "," + missing.onePropertyTables.withMapping.getTriples().size()
                + "," + present.onePropertyTables.withoutMapping.getTriples().size()
                + "," + missing.onePropertyTables.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "MultiPropertyTable"
                + "," + getTriples(PatternCategory.MultiPropertyTable).size()
                + "," + present.multiPropertyTables.getTriples().size()
                + "," + missing.multiPropertyTables.getTriples().size()
                + "," + present.multiPropertyTables.withMapping.getTriples().size()
                + "," + missing.multiPropertyTables.withMapping.getTriples().size()
                + "," + present.multiPropertyTables.withoutMapping.getTriples().size()
                + "," + missing.multiPropertyTables.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "PredicateObjectRelation"
                + "," + getTriples(PatternCategory.OpenProperty).size()
                + "," + present.openProperties.getTriples().size()
                + "," + missing.openProperties.getTriples().size()
                + "," + present.openProperties.withMapping.getTriples().size()
                + "," + missing.openProperties.withMapping.getTriples().size()
                + "," + present.openProperties.withoutMapping.getTriples().size()
                + "," + missing.openProperties.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "PredicateObjectRelationTable"
                + "," + getTriples(PatternCategory.OpenPropertyTable).size()
                + "," + present.openPropertyTables.getTriples().size()
                + "," + missing.openPropertyTables.getTriples().size()
                + "," + present.openPropertyTables.withMapping.getTriples().size()
                + "," + missing.openPropertyTables.withMapping.getTriples().size()
                + "," + present.openPropertyTables.withoutMapping.getTriples().size()
                + "," + missing.openPropertyTables.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "InternalTemplate"
                + "," + getTriples(PatternCategory.InternalTemplate).size()
                + "," + present.internalTemplates.getTriples().size()
                + "," + missing.internalTemplates.getTriples().size()
                + "," + present.internalTemplates.withMapping.getTriples().size()
                + "," + missing.internalTemplates.withMapping.getTriples().size()
                + "," + present.internalTemplates.withoutMapping.getTriples().size()
                + "," + missing.internalTemplates.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "MergedProperties"
                + "," + getTriples(PatternCategory.MergedProperties).size()
                + "," + present.mergedProperties.getTriples().size()
                + "," + missing.mergedProperties.getTriples().size()
                + "," + present.mergedProperties.withMapping.getTriples().size()
                + "," + missing.mergedProperties.withMapping.getTriples().size()
                + "," + present.mergedProperties.withoutMapping.getTriples().size()
                + "," + missing.mergedProperties.withoutMapping.getTriples().size()
                + "\n";
        csvStats = csvStats
                + "ToDo"
                + "," + getTriples(PatternCategory.ToDo).size()
                + "," + present.toDos.getTriples().size()
                + "," + missing.toDos.getTriples().size()
                + "," + present.toDos.withMapping.getTriples().size()
                + "," + missing.toDos.withMapping.getTriples().size()
                + "," + present.toDos.withoutMapping.getTriples().size()
                + "," + missing.toDos.withoutMapping.getTriples().size()
                + "\n";
        return csvStats;
    }

    public String getSnippetCSV()
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
                + "," + getSnippets().size()
                + "," + present.getSnippets().size()
                + "," + missing.getSnippets().size()
                + "," + present.getMappedSnippets().size()
                + "," + missing.getMappedSnippets().size()
                + "," + present.getNotMappedSnippets().size()
                + "," + missing.getNotMappedSnippets().size()
                + "\n";
        csvStats = csvStats
                + "NoClass"
                + "," + getSnippets(PatternCategory.PlainProperty).size()
                + "," + present.plainProperty.getSnippets().size()
                + "," + missing.plainProperty.getSnippets().size()
                + "," + present.plainProperty.withMapping.getSnippets().size()
                + "," + missing.plainProperty.withMapping.getSnippets().size()
                + "," + present.plainProperty.withoutMapping.getSnippets().size()
                + "," + missing.plainProperty.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "NumberUnit"
                + "," + getSnippets(PatternCategory.NumberUnit).size()
                + "," + present.numberUnits.getSnippets().size()
                + "," + missing.numberUnits.getSnippets().size()
                + "," + present.numberUnits.withMapping.getSnippets().size()
                + "," + missing.numberUnits.withMapping.getSnippets().size()
                + "," + present.numberUnits.withoutMapping.getSnippets().size()
                + "," + missing.numberUnits.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "Coordinate"
                + "," + getSnippets(PatternCategory.Coordinates).size()
                + "," + present.coordinates.getSnippets().size()
                + "," + missing.coordinates.getSnippets().size()
                + "," + present.coordinates.withMapping.getSnippets().size()
                + "," + missing.coordinates.withMapping.getSnippets().size()
                + "," + present.coordinates.withoutMapping.getSnippets().size()
                + "," + missing.coordinates.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "Range"
                + "," + getSnippets(PatternCategory.Interval).size()
                + "," + present.intervals.getSnippets().size()
                + "," + missing.intervals.getSnippets().size()
                + "," + present.intervals.withMapping.getSnippets().size()
                + "," + missing.intervals.withMapping.getSnippets().size()
                + "," + present.intervals.withoutMapping.getSnippets().size()
                + "," + missing.intervals.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "List"
                + "," + getSnippets(PatternCategory.List).size()
                + "," + present.lists.getSnippets().size()
                + "," + missing.lists.getSnippets().size()
                + "," + present.lists.withMapping.getSnippets().size()
                + "," + missing.lists.withMapping.getSnippets().size()
                + "," + present.lists.withoutMapping.getSnippets().size()
                + "," + missing.lists.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "OnePropertyTable"
                + "," + getSnippets(PatternCategory.OnePropertyTable).size()
                + "," + present.onePropertyTables.getSnippets().size()
                + "," + missing.onePropertyTables.getSnippets().size()
                + "," + present.onePropertyTables.withMapping.getSnippets().size()
                + "," + missing.onePropertyTables.withMapping.getSnippets().size()
                + "," + present.onePropertyTables.withoutMapping.getSnippets().size()
                + "," + missing.onePropertyTables.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "MultiPropertyTable"
                + "," + getSnippets(PatternCategory.MultiPropertyTable).size()
                + "," + present.multiPropertyTables.getSnippets().size()
                + "," + missing.multiPropertyTables.getSnippets().size()
                + "," + present.multiPropertyTables.withMapping.getSnippets().size()
                + "," + missing.multiPropertyTables.withMapping.getSnippets().size()
                + "," + present.multiPropertyTables.withoutMapping.getSnippets().size()
                + "," + missing.multiPropertyTables.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "PredicateObjectRelation"
                + "," + getSnippets(PatternCategory.OpenProperty).size()
                + "," + present.openProperties.getSnippets().size()
                + "," + missing.openProperties.getSnippets().size()
                + "," + present.openProperties.withMapping.getSnippets().size()
                + "," + missing.openProperties.withMapping.getSnippets().size()
                + "," + present.openProperties.withoutMapping.getSnippets().size()
                + "," + missing.openProperties.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "PredicateObjectRelationTable"
                + "," + getSnippets(PatternCategory.OpenPropertyTable).size()
                + "," + present.openPropertyTables.getSnippets().size()
                + "," + missing.openPropertyTables.getSnippets().size()
                + "," + present.openPropertyTables.withMapping.getSnippets().size()
                + "," + missing.openPropertyTables.withMapping.getSnippets().size()
                + "," + present.openPropertyTables.withoutMapping.getSnippets().size()
                + "," + missing.openPropertyTables.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "InternalTemplate"
                + "," + getSnippets(PatternCategory.InternalTemplate).size()
                + "," + present.internalTemplates.getSnippets().size()
                + "," + missing.internalTemplates.getSnippets().size()
                + "," + present.internalTemplates.withMapping.getSnippets().size()
                + "," + missing.internalTemplates.withMapping.getSnippets().size()
                + "," + present.internalTemplates.withoutMapping.getSnippets().size()
                + "," + missing.internalTemplates.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "MergedProperties"
                + "," + getSnippets(PatternCategory.MergedProperties).size()
                + "," + present.mergedProperties.getSnippets().size()
                + "," + missing.mergedProperties.getSnippets().size()
                + "," + present.mergedProperties.withMapping.getSnippets().size()
                + "," + missing.mergedProperties.withMapping.getSnippets().size()
                + "," + present.mergedProperties.withoutMapping.getSnippets().size()
                + "," + missing.mergedProperties.withoutMapping.getSnippets().size()
                + "\n";
        csvStats = csvStats
                + "ToDo"
                + "," + getSnippets(PatternCategory.ToDo).size()
                + "," + present.toDos.getSnippets().size()
                + "," + missing.toDos.getSnippets().size()
                + "," + present.toDos.withMapping.getSnippets().size()
                + "," + missing.toDos.withMapping.getSnippets().size()
                + "," + present.toDos.withoutMapping.getSnippets().size()
                + "," + missing.toDos.withoutMapping.getSnippets().size()
                + "\n";
        return csvStats;
    }
}
