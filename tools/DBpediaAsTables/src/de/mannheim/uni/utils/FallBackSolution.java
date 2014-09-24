package de.mannheim.uni.utils;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.ObjectInputStream;
import java.io.ObjectOutputStream;
import java.util.ArrayList;
import java.util.LinkedList;
import java.util.List;
import java.util.Properties;
import java.util.logging.Logger;

import au.com.bytecode.opencsv.CSVWriter;
import de.mannheim.uni.model.DBpediaInstance;
import de.mannheim.uni.model.DBpediaProperty;
import de.mannheim.uni.sparql.SPARQLEndpointQueryRunner;

public class FallBackSolution {
	public static void main(String[] args) {
		try {

			FileInputStream fis = new FileInputStream("tmpProps/" + args[0]
					+ "Properties.ser");
			ObjectInputStream ois = new ObjectInputStream(fis);
			List<DBpediaProperty> properties = (List) ois.readObject();
			ois.close();
			convertTmpFilesToCSV(args[0], properties);
		} catch (Exception e1) {
			// TODO Auto-generated catch block
			e1.printStackTrace();
		}

	}

	public static void convertTmpFilesToCSV(String classURI,
			List<DBpediaProperty> properties1) {
		List<DBpediaProperty> properties = new ArrayList<DBpediaProperty>();
		for (DBpediaProperty prop : properties1) {
			if (prop.getUri().startsWith("http://dbpedia.org/property/"))
				continue;
			properties.add(prop);
		}

		// create the CSV File
		CSVWriter writer = null;
		try {
			writer = new CSVWriter(
					new FileWriter("Output/" + classURI + ".csv"), ';');
		} catch (Exception e) {
			e.printStackTrace();
		}
		// write the headers

		// decide how many properties and duble properties are there
		int totalPropSize = 1; // starting with the URI
		for (DBpediaProperty prop : properties) {
			totalPropSize++;
			if (prop.isObjectProperty())
				totalPropSize++;
		}

		List<String> alphabeticallyOrderProperties = new ArrayList<String>();
		for (DBpediaProperty prop : properties) {
			alphabeticallyOrderProperties.add(prop.getUri());
		}
		java.util.Collections.sort(alphabeticallyOrderProperties);

		// create the final order
		List<String> finalOrderProperties = new LinkedList<String>();
		// add the label
		if (alphabeticallyOrderProperties
				.contains("http://www.w3.org/2000/01/rdf-schema#label")) {
			finalOrderProperties
					.add("http://www.w3.org/2000/01/rdf-schema#label");
			alphabeticallyOrderProperties
					.remove("http://www.w3.org/2000/01/rdf-schema#label");
		}
		// add the short abstract
		if (alphabeticallyOrderProperties
				.contains("http://www.w3.org/2000/01/rdf-schema#comment")) {
			finalOrderProperties
					.add("http://www.w3.org/2000/01/rdf-schema#comment");
			alphabeticallyOrderProperties
					.remove("http://www.w3.org/2000/01/rdf-schema#comment");
		}
		for (String str : alphabeticallyOrderProperties) {
			finalOrderProperties.add(str);
		}

		// write the properties labels in the first row
		String[] entries = new String[totalPropSize];
		// this is the URI
		entries[0] = "URI";
		int i = 1;
		for (String propURI : finalOrderProperties) {
			DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(propURI,
					properties);
			// add one more column
			if (prop.isObjectProperty()) {
				entries[i] = prop.getLabel() + "_label";
				i++;
			}
			entries[i] = prop.getLabel();
			i++;
		}
		writer.writeNext(entries);

		// write the properties uris in the second row
		entries = new String[totalPropSize];
		// this is the URI
		entries[0] = "URI";
		i = 1;
		for (String propURI : finalOrderProperties) {
			DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(propURI,
					properties);
			// add one more column
			if (prop.isObjectProperty()) {
				entries[i] = prop.getUri();
				i++;
			}
			entries[i] = prop.getUri();
			i++;
		}
		writer.writeNext(entries);

		// write the properties types label in the third row
		entries = new String[totalPropSize];
		// this is the URI
		entries[0] = "URI";
		i = 1;
		for (String propURI : finalOrderProperties) {
			DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(propURI,
					properties);
			// add one more column
			if (prop.isObjectProperty()) {
				entries[i] = "XMLSchema#string";
				i++;
			}
			entries[i] = prop.getFinalRangeLabel();
			i++;
		}
		writer.writeNext(entries);
		// write the properties types uri in the fourth row
		entries = new String[totalPropSize];
		// this is the URI
		entries[0] = "http://www.w3.org/2002/07/owl#Thing";
		i = 1;
		for (String propURI : finalOrderProperties) {
			DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(propURI,
					properties);
			// add one more column
			if (prop.isObjectProperty()) {
				entries[i] = DBpediaProperty.STRING_SCHEMA_TYPE;
				i++;
			}
			entries[i] = prop.getFinalRange();
			i++;
		}
		writer.writeNext(entries);

		// write instances from the files
		File folder = new File("tmpFiles");
		for (File fileEntry : folder.listFiles()) {
			if (!fileEntry.getName().startsWith(classURI))
				continue;
			try {
				FileInputStream fis = new FileInputStream(fileEntry.getPath());
				ObjectInputStream ois = new ObjectInputStream(fis);
				List<DBpediaInstance> instancesWithPropertiesTmp = (List) ois
						.readObject();
				ois.close();

				int start = 0;
				int part = 500;
				while (part < instancesWithPropertiesTmp.size()) {
					writeToCSV(writer, properties,
							instancesWithPropertiesTmp.subList(start, part),
							classURI, totalPropSize, finalOrderProperties);
					start += 500;
					part += 500;
				}

				fileEntry.delete();
			} catch (Exception e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}

		}
		try {
			writer.close();
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

	}

	private static void writeToCSV(CSVWriter writer,
			List<DBpediaProperty> properties,
			List<DBpediaInstance> instancesWithProperties, String classURI,
			int totalPropSize, List<String> finalOrderProperties) {

		// write the instances
		String[] entries = new String[totalPropSize];
		for (DBpediaInstance instanceProp : instancesWithProperties) {

			entries[0] = instanceProp.getUri();
			int i = 1;
			for (String propURI : finalOrderProperties) {
				DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(
						propURI, properties);
				if (prop.isObjectProperty()) {
					List<String> values = instanceProp.gerPropertyByURI(
							prop.getUri()).getValues();
					// compute the value
					String value = "NULL";
					if (values.size() > 0) {
						value = values.get(0).substring(
								values.get(0).lastIndexOf("/") + 1);
						// if there are more values concatenate them into one
						// value
						if (values.size() > 1) {
							value = "";
							for (String valueFromList : values) {
								value += "\",\""
										+ valueFromList.substring(valueFromList
												.lastIndexOf("/") + 1);
							}
							value += "\"}";
							value = value.replaceFirst("\",\"", "{\"");
						}
					}
					value = value.replaceAll("\n", "");
					entries[i] = value;
					i++;
				}
				List<String> values = instanceProp.gerPropertyByURI(
						prop.getUri()).getValues();
				// compute the value
				String value = "NULL";
				if (values.size() > 0) {
					value = values.get(0);
					// if there are more values concatenate them into one value
					if (values.size() > 1) {
						value = "";
						for (String valueFromList : values) {
							value += "\",\"" + valueFromList;
						}
						value += "\"}";
						value = value.replaceFirst("\",\"", "{\"");
					}
				}
				value = value.replaceAll("\n", "");
				entries[i] = value;
				i++;
			}
			writer.writeNext(entries);

		}

	}
}
