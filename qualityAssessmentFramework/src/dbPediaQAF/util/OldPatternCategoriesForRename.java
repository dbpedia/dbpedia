/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package dbPediaQAF.util;

/**
 *
 * @author Paul
 */
public enum OldPatternCategoriesForRename {

        NoClass, Range, Coordinates, NumberUnit, List, OnePropertyTable,
        MultiPropertyTable, PredicateObjectRelation, PredicateObjectRelationTable,
        InternalTemplate, MergedProperties,
        ToDo, NOVALUE;

        public static OldPatternCategoriesForRename toOldPatternClass(String str) {
            try {
                return valueOf(str);
            } catch (Exception ex) {
                return NOVALUE;
            }
        }
    }
