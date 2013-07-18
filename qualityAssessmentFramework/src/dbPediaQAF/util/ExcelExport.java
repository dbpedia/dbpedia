/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package dbPediaQAF.util;

import dbPediaQAF.Config;
import dbPediaQAF.resultManaging.*;
import dbPediaQAF.resultManaging.CompletenessHandswitch;
import dbPediaQAF.resultManaging.ResultSet;
import dbPediaQAF.resultManaging.TripleObjectResult;
import dbPediaQAF.util.PatternCategory;
import dbPediaQAF.xmlQuery.Snippet;
import java.io.*;
import java.lang.reflect.Field;
import java.util.*;
import org.apache.poi.ss.usermodel.*;

/**
 *
 * @author Paul
 */
public class ExcelExport
{

    public ExcelExport(ResultSet resultSet)
    {
        //this.path = path;
        this.resultSet = resultSet;
    }
    //private String path;
    private ResultSet resultSet;
    private Workbook wb;
    private Sheet currentSheet;

    public void updateTripleSheet()
    {
        /** Set the row numbers of the master.xls for the specific comparison results. */
        Map<String, Integer> startRows = new HashMap<String, Integer>();
        startRows.put("goldStandard", 6);
        startRows.put("tripleTEC", 70);
        startRows.put("triplePNC", 102);
        startRows.put("tripleOSC", 134);
        startRows.put("tripleOSCwrong", 166);
        Integer startCellNumber = 12;
        try
        {
//            System.out.println("tripleTEC.missing.plainProperty.withMapping.entityTriples: " + resultSet.tripleTEC.missing.plainProperty.withMapping.triples.entityTriples.getTriples().size());
//            System.out.println("tripleTEC.missing.plainProperty.withMapping.intermediateTriples: " + resultSet.tripleTEC.missing.plainProperty.withMapping.triples.intermediateTriples.getTriples().size());
//            System.out.println("tripleTEC.missing.lists.withMapping.entityTriples: " + resultSet.tripleTEC.missing.lists.withMapping.triples.entityTriples.getTriples().size());
//            System.out.println("tripleTEC.missing.lists.withMapping.intermediateTriples: " + resultSet.tripleTEC.missing.lists.withMapping.triples.intermediateTriples.getTriples().size());
            /** Load master.xls and select the triple result sheet. */
            InputStream inp = new FileInputStream(Config.getExcelFileOutputPath());
            wb = WorkbookFactory.create(inp);
            currentSheet = wb.getSheetAt(0);
            /** Load fields of the resultSet and iterate over them. */
            Field[] resultSetFields = resultSet.getClass().getDeclaredFields();
            for (Field resultSetField : resultSetFields)
            {
//                System.out.println(resultSetField.getName());
                CompletenessHandswitch actualResult = (CompletenessHandswitch) resultSetField.get(resultSet);
                /** Select the right row number for the specific comparison result. */
                if (startRows.containsKey(resultSetField.getName()))
                {
                    writeCategory(PatternCategory.PlainProperty, actualResult, startRows.get(resultSetField.getName()), startCellNumber);
                    writeCategory(PatternCategory.NumberUnit, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 7);
                    writeCategory(PatternCategory.Coordinates, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 14);
                    writeCategory(PatternCategory.Interval, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 21);
                    writeCategory(PatternCategory.List, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 28);
                    writeCategory(PatternCategory.OnePropertyTable, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 35);
                    writeCategory(PatternCategory.MultiPropertyTable, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 42);
                    writeCategory(PatternCategory.OpenProperty, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 49);
                    writeCategory(PatternCategory.OpenPropertyTable, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 56);
                    writeCategory(PatternCategory.InternalTemplate, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 63);
                    writeCategory(PatternCategory.MergedProperties, actualResult, startRows.get(resultSetField.getName()), startCellNumber + 70);
                }
            }
            // Write the output to a file
            FileOutputStream fileOut = new FileOutputStream(Config.getExcelFileOutputPath());
            wb.write(fileOut);
            fileOut.close();
        }
        catch (Exception ex)
        {
            System.out.println("Failed to update excel triple sheet.");
            System.out.println("Error Msg: " + ex.getMessage());
        }

    }

    public void updateSnippetSheet()
    {
        /** Set the row numbers of the master.xls for the specific comparison results. */
        Map<String, Integer> startRows = new HashMap<String, Integer>();
        startRows.put("snippetGoldStandard", 3);
        startRows.put("snippetTEC", 27);
        startRows.put("snippetPNC", 39);
        startRows.put("snippetOSC", 51);
        Integer startCellNumber = 3;

//        System.out.println("snippetTEC: " + resultSet.snippetTEC.getSnippets().size());
//        System.out.println("snippetTEC.present.plainProperty: " + resultSet.snippetTEC.present.plainProperty.getSnippets().size());
//        System.out.println("snippetTEC.missing.plainProperty: " + resultSet.snippetTEC.missing.plainProperty.getSnippets().size());
//        System.out.println("snippetTEC.missing.lists: " + resultSet.snippetTEC.missing.lists.getSnippets().size());

        List<Snippet> snippets = resultSet.snippetPNC.present.plainProperty.withoutMapping.getSnippets();
        System.out.println("present.withoutMapping: " + snippets.size());
        for (Snippet snippet : snippets)
        {
            System.out.println(snippet.getTriple());
        }

        try
        {
            /** Load master.xls and select the snippet result sheet. */
            InputStream inp = new FileInputStream(Config.getExcelFileOutputPath());
            wb = WorkbookFactory.create(inp);
            currentSheet = wb.getSheetAt(1);
            /** Load fields of the resultSet and iterate over them. */
            Field[] resultSetFields = resultSet.getClass().getDeclaredFields();
            for (Field resultSetField : resultSetFields)
            {
//                System.out.println(resultSetField.getName());
                CompletenessHandswitch actualResult = (CompletenessHandswitch) resultSetField.get(resultSet);
                /** Select the right row number for the specific comparison result. */
                if (startRows.containsKey(resultSetField.getName()))
                {
                    int startRowNumber = startRows.get(resultSetField.getName());
                    writeSnippetLine(actualResult, startRowNumber, startCellNumber);
                }
            }
            // Write the output to a file
            FileOutputStream fileOut = new FileOutputStream(Config.getExcelFileOutputPath());
            wb.write(fileOut);
            fileOut.close();
        }
        catch (Exception ex)
        {
            System.out.println("Failed to update excel snippet sheet.");
            System.out.println("Error Msg: " + ex.getMessage());
        }
    }

    private void writeCategory(PatternCategory pc, CompletenessHandswitch cs, Integer startRowNumber, Integer startCellNumber)
    {
        TripleObjectResult lor = new TripleObjectResult(cs, pc);
        //lor.print();
        /** Write entity triples */
        writeDatatypeLine(startRowNumber, startCellNumber, lor.stringResults);
        writeDatatypeLine(startRowNumber + 1, startCellNumber, lor.integerResults);
        writeDatatypeLine(startRowNumber + 2, startCellNumber, lor.doubleResults);
        writeDatatypeLine(startRowNumber + 3, startCellNumber, lor.floatResults);
        writeDatatypeLine(startRowNumber + 4, startCellNumber, lor.dateResults);
        writeDatatypeLine(startRowNumber + 5, startCellNumber, lor.gYearResults);
        writeDatatypeLine(startRowNumber + 6, startCellNumber, lor.gYearMonthResults);
        writeDatatypeLine(startRowNumber + 7, startCellNumber, lor.gMonthDayResults);
        writeDatatypeLine(startRowNumber + 8, startCellNumber, lor.timeResults);
        writeDatatypeLine(startRowNumber + 10, startCellNumber, lor.entityResults);
        writeDatatypeLine(startRowNumber + 11, startCellNumber, lor.intermediateResults);
        writeDatatypeLine(startRowNumber + 12, startCellNumber, lor.urlResults);

        /** Write intermediate triples */
        writeDatatypeLine(startRowNumber + 15, startCellNumber, lor.stringIntermResults);
        writeDatatypeLine(startRowNumber + 16, startCellNumber, lor.integerIntermResults);
        writeDatatypeLine(startRowNumber + 17, startCellNumber, lor.doubleIntermResults);
        writeDatatypeLine(startRowNumber + 18, startCellNumber, lor.floatIntermResults);
        writeDatatypeLine(startRowNumber + 19, startCellNumber, lor.dateIntermResults);
        writeDatatypeLine(startRowNumber + 20, startCellNumber, lor.gYearIntermResults);
        writeDatatypeLine(startRowNumber + 21, startCellNumber, lor.gYearMonthIntermResults);
        writeDatatypeLine(startRowNumber + 22, startCellNumber, lor.gMonthDayIntermResults);
        writeDatatypeLine(startRowNumber + 23, startCellNumber, lor.timeIntermResults);
        writeDatatypeLine(startRowNumber + 25, startCellNumber, lor.entityIntermResults);
        writeDatatypeLine(startRowNumber + 26, startCellNumber, lor.intermediateIntermResults);
        writeDatatypeLine(startRowNumber + 27, startCellNumber, lor.urlIntermResults);
    }

    private void writeDatatypeLine(Integer startRowNumber, Integer cellStartNum, Map<String, Long> resultMap)
    {
        /** Write present triples */
        Row row = currentSheet.getRow(startRowNumber);
        Cell cellPresent = row.getCell(cellStartNum);
        if (cellPresent == null)
        {
            cellPresent = row.createCell(cellStartNum);
        }
        cellPresent.setCellType(Cell.CELL_TYPE_NUMERIC);
        cellPresent.setCellValue(resultMap.get("present"));

        /** Write present triples with mapping */
        int cellNumberPM = cellStartNum + 1;
        Cell cellPresentM = row.getCell(cellNumberPM);
        if (cellPresentM == null)
        {
            cellPresentM = row.createCell(cellNumberPM);
        }
        cellPresentM.setCellType(Cell.CELL_TYPE_NUMERIC);
        cellPresentM.setCellValue(resultMap.get("presentMapping"));

        /** Write missing triples */
        int cellNumberM = cellStartNum + 3;
        Cell cellMissing = row.getCell(cellNumberM);
        if (cellMissing == null)
        {
            cellMissing = row.createCell(cellNumberM);
        }
        cellMissing.setCellType(Cell.CELL_TYPE_NUMERIC);
        cellMissing.setCellValue(resultMap.get("missing"));

        /** Write missing triples with mapping */
        int cellNumberMM = cellStartNum + 4;
        Cell cellMissingM = row.getCell(cellNumberMM);
        if (cellMissingM == null)
        {
            cellMissingM = row.createCell(cellNumberMM);
        }
        cellMissingM.setCellType(Cell.CELL_TYPE_NUMERIC);
        cellMissingM.setCellValue(resultMap.get("missingMapping"));
    }

    private void writeSnippetLine(CompletenessHandswitch actualResult, int startRowNumber, int startCellNumber)
    {

        /** plainProperty */
        Row row = currentSheet.getRow(startRowNumber);

        Cell cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.plainProperty.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.plainProperty.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.plainProperty.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.plainProperty.withMapping.snippets.getSnippets().size());

        /** numberUnits */
        row = currentSheet.getRow(startRowNumber + 1);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.numberUnits.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.numberUnits.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.numberUnits.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.numberUnits.withMapping.snippets.getSnippets().size());

        /** coordinates */
        row = currentSheet.getRow(startRowNumber + 2);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.coordinates.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.coordinates.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.coordinates.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.coordinates.withMapping.snippets.getSnippets().size());

        /** lists */
        row = currentSheet.getRow(startRowNumber + 3);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.lists.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.lists.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.lists.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.lists.withMapping.snippets.getSnippets().size());

        /** intervals */
        row = currentSheet.getRow(startRowNumber + 4);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.intervals.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.intervals.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.intervals.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.intervals.withMapping.snippets.getSnippets().size());

        /** onePropertyTables */
        row = currentSheet.getRow(startRowNumber + 5);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.onePropertyTables.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.onePropertyTables.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.onePropertyTables.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.onePropertyTables.withMapping.snippets.getSnippets().size());

        /** multiPropertyTables */
        row = currentSheet.getRow(startRowNumber + 6);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.multiPropertyTables.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.multiPropertyTables.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.multiPropertyTables.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.multiPropertyTables.withMapping.snippets.getSnippets().size());


        /** openProperties */
        row = currentSheet.getRow(startRowNumber + 7);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.openProperties.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.openProperties.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.openProperties.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.openProperties.withMapping.snippets.getSnippets().size());


        /** openPropertyTables */
        row = currentSheet.getRow(startRowNumber + 8);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.openPropertyTables.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.openPropertyTables.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.openPropertyTables.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.openPropertyTables.withMapping.snippets.getSnippets().size());


        /** internalTemplates */
        row = currentSheet.getRow(startRowNumber + 9);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.internalTemplates.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.internalTemplates.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.internalTemplates.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.internalTemplates.withMapping.snippets.getSnippets().size());


        /** mergedProperties */
        row = currentSheet.getRow(startRowNumber + 10);

        cell = row.getCell(startCellNumber);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.mergedProperties.getSnippets().size());

        cell = row.getCell(startCellNumber + 1);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 1);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.present.mergedProperties.withMapping.snippets.getSnippets().size());

        cell = row.getCell(startCellNumber + 3);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 3);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.mergedProperties.getSnippets().size());

        cell = row.getCell(startCellNumber + 4);
        if (cell == null)
        {
            cell = row.createCell(startCellNumber + 4);
        }
        cell.setCellType(Cell.CELL_TYPE_NUMERIC);
        cell.setCellValue(actualResult.missing.mergedProperties.withMapping.snippets.getSnippets().size());

    }
}
