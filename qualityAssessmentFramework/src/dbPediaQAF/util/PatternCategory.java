/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package dbPediaQAF.util;

/**
 *
 * @author Paul
 */
public enum PatternCategory {

        PlainProperty, Interval, Coordinates, NumberUnit, List, OnePropertyTable,
        MultiPropertyTable, OpenProperty, OpenPropertyTable,
        InternalTemplate, MergedProperties,
        ToDo, NOVALUE;

        private int counter = 0;

        public void count() {
            counter = counter + 1;
        }

        public int getCounter() {
            return counter;
        }

        public int getSum() {
            int sum = 0;
            sum = sum
                + this.PlainProperty.getCounter()
                + this.Interval.getCounter()
                + this.Coordinates.getCounter()
                + this.NumberUnit.getCounter()
                + this.List.getCounter()
                + this.OnePropertyTable.getCounter()
                + this.MultiPropertyTable.getCounter()
                + this.OpenProperty.getCounter()
                + this.OpenPropertyTable.getCounter()
                + this.InternalTemplate.getCounter()
                + this.MergedProperties.getCounter()
                + this.ToDo.getCounter()
                + this.NOVALUE.getCounter();
            return sum;
        }

        public static PatternCategory toPatternCategory(String str) {
            try {
                return valueOf(str);
            } catch (Exception ex) {
                return NOVALUE;
            }
        }
    }
