package de.mannheim.uni.convertors;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.ObjectInputStream;
import java.io.ObjectOutputStream;
import java.io.OutputStreamWriter;
import java.io.Writer;
import java.util.ArrayList;
import java.util.LinkedList;
import java.util.List;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;
import java.util.logging.FileHandler;
import java.util.logging.Logger;
import java.util.logging.SimpleFormatter;
import java.util.zip.GZIPOutputStream;

import org.json.simple.JSONArray;
import org.json.simple.JSONObject;

import com.hp.hpl.jena.query.ParameterizedSparqlString;
import com.hp.hpl.jena.query.Query;
import com.hp.hpl.jena.query.QueryFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.rdf.model.Literal;

import de.mannheim.uni.model.DBpediaInstance;
import de.mannheim.uni.model.DBpediaInstanceProperty;
import de.mannheim.uni.model.DBpediaProperty;
import de.mannheim.uni.sparql.SPARQLEndpointQueryRunner;

public class ClassToJson {

	public static final String GET_INSTANCES_OF_CLASS = "select distinct ?Concept where {?Concept a ?type}";
	public static final String GET_PROPERTIES_OF_INSTANCE = "PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#> select distinct * where {?instance ?prop ?object .FILTER(?prop!=<http://dbpedia.org/ontology/wikiPageWikiLink> && ?prop!=<http://dbpedia.org/property/wikiPageUsesTemplate> && ?prop!=<http://purl.org/dc/terms/subject> && ?prop!=<http://dbpedia.org/ontology/wikiPageExternalLink> && ?prop!=<http://www.w3.org/ns/prov#wasDerivedFrom> && ?prop!=<http://www.w3.org/ns/prov#wasDerivedFrom> && ?prop!=<http://dbpedia.org/ontology/abstract>). FILTER(!regex(str(?prop), \"^http://xmlns.com\")).  optional {?prop rdfs:range ?range} .optional {?object rdfs:label ?label FILTER(LANGMATCHES(LANG(?label), \"en\")) } }";
	public static final String GET_LEAF_CLASSES = "PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#> PREFIX owl:<http://www.w3.org/2002/07/owl#> select distinct ?type FROM <http://dbpedia.org>  {?type a owl:Class . FILTER NOT EXISTS{?subclass rdfs:subClassOf ?type}}";
	public static final String GET_ALL_CLASSES = "PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#> PREFIX owl:<http://www.w3.org/2002/07/owl#> select distinct ?type FROM <http://dbpedia.org> {?type a owl:Class}";

	SPARQLEndpointQueryRunner queryRunner;

	Logger logger;

	public ClassToJson(Logger logger, String endpoint) {
		// TODO Auto-generated constructor stub
		// initialize
		queryRunner = new SPARQLEndpointQueryRunner(endpoint);// SPARQLEndpointQueryRunner.getLocalDBpeidaRunner();//
		this.logger = logger;
	}

	private class ClassToCSVThread implements Runnable {
		private String classURI;
		SPARQLEndpointQueryRunner queryRunner;

		List<DBpediaProperty> properties;
		List<DBpediaInstance> instancesWithProperties;

		public ClassToCSVThread(String classURI, String endpoint) {
			queryRunner = new SPARQLEndpointQueryRunner(endpoint);
			this.classURI = classURI;
			properties = new ArrayList<DBpediaProperty>();
			instancesWithProperties = new ArrayList<DBpediaInstance>();

		}

		public void run() {
			convertClass(classURI);
		}

		/**
		 * Converts all instances from the given class to CSV file (avoiding out
		 * of memory exception by first serializing all data in several small
		 * files)
		 * 
		 * @param classURI
		 */
		public void convertClass(String classURI) {

			logger.info("Converting class " + classURI + " ...");

			long start = System.currentTimeMillis();
			List<String> instancesURIs = getInstancesFromClass(classURI);
			logger.info("Retrieved instances of class: " + classURI + ": "
					+ instancesURIs.size());

			logger.info("Time to retrieve instances from this class in miliseconds:"
					+ (System.currentTimeMillis() - start));

			int instanceCounter = 0;
			for (String instanceURI : instancesURIs) {
				logger.info("Converting instance " + instanceCounter + "/"
						+ instancesURIs.size());
				DBpediaInstance ins = null;
				try {
					ins = processInstance(instanceURI);
				} catch (Exception e) {

				}
				if (ins != null)
					instancesWithProperties.add(ins);

				// write instances to file, to avoid out of memory exception
				if (instancesWithProperties.size() > 30000) {
					try {
						FileOutputStream fos = new FileOutputStream(
								"tmpFiles/"
										+ classURI.substring(classURI
												.lastIndexOf("/") + 1)
										+ instanceCounter + ".ser");
						ObjectOutputStream oos = new ObjectOutputStream(fos);
						oos.writeObject(instancesWithProperties);
						oos.close();
					} catch (Exception e1) {
						// TODO Auto-generated catch block
						e1.printStackTrace();
					}
					instancesWithProperties = new ArrayList<DBpediaInstance>();
				}
				instanceCounter++;
			}
			logger.info("Write to CSV");
			// serialize the objects for further use
			try {
				FileOutputStream fos = new FileOutputStream("tmpFiles/"
						+ classURI.substring(classURI.lastIndexOf("/") + 1)
						+ instanceCounter + ".ser");
				ObjectOutputStream oos = new ObjectOutputStream(fos);
				oos.writeObject(instancesWithProperties);
				oos.close();
				instancesWithProperties = new ArrayList<DBpediaInstance>();
			} catch (Exception e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
			// serialize the properties also
			try {
				FileOutputStream fos = new FileOutputStream("tmpProps/"
						+ classURI.substring(classURI.lastIndexOf("/") + 1)
						+ "Properties.ser");
				ObjectOutputStream oos = new ObjectOutputStream(fos);
				oos.writeObject(properties);
				oos.close();

			} catch (Exception e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
			convertTmpFilesToCSV(classURI);
			logger.info("Total time to convert class in miliseconds: "
					+ (System.currentTimeMillis() - start));
		}

		/**
		 * converts all temporary files to CSV
		 * 
		 * @param classURI
		 */
		public void convertTmpFilesToCSV(String classURI) {
			// create the CSV File
			Writer writer = null;
			try {

				writer = new BufferedWriter(new OutputStreamWriter(
						new GZIPOutputStream(new FileOutputStream(
								"json/"
										+ classURI.substring(classURI
												.lastIndexOf("/") + 1)
										+ ".json.gz")), "UTF-8"));
				// writer = new CSVWriter(new FileWriter("Output/"
				// + classURI.substring(classURI.lastIndexOf("/") + 1)
				// + ".csv"), ';');
			} catch (Exception e) {
				e.printStackTrace();
			}
			// write the headers

			// decide how many properties and double properties are there
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
			JSONArray list = new JSONArray();
			// write the first one
			JSONObject obj = new JSONObject();
			obj.put("propertyLabel", "URI");
			obj.put("propertyURI", "URI");
			obj.put("propertyTypeLabel", "URI");
			obj.put("propertyType", "http://www.w3.org/2002/07/owl#Thing");
			list.add(obj);
			for (String propURI : finalOrderProperties) {

				DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(
						propURI, properties);
				// add one more column
				if (prop.isObjectProperty()) {
					entries[i] = prop.getLabel() + "_label";
					i++;
					obj = new JSONObject();
					obj.put("propertyLabel", prop.getLabel() + "_label");
					obj.put("propertyURI", prop.getUri());
					obj.put("propertyTypeLabel", "XMLSchema#string");
					obj.put("propertyType", DBpediaProperty.STRING_SCHEMA_TYPE);
					list.add(obj);
				}
				obj = new JSONObject();
				obj.put("propertyLabel", prop.getLabel());
				obj.put("propertyURI", prop.getUri());
				obj.put("propertyTypeLabel", prop.getFinalRangeLabel());
				obj.put("propertyType", prop.getFinalRange());
				list.add(obj);
			}
			JSONObject props = new JSONObject();
			props.put("properties", list);
			try {
				String jsonStr = props.toJSONString();
				writer.write(jsonStr.substring(0, jsonStr.length() - 1) + ",\n");
				writer.write("\"instances\":[");
			} catch (IOException e2) {
				// TODO Auto-generated catch block
				e2.printStackTrace();
			}

			// write instances from the files
			boolean isFirst = true;
			File folder = new File("tmpFiles");
			for (File fileEntry : folder.listFiles()) {
				if (!fileEntry.getName().startsWith(
						classURI.substring(classURI.lastIndexOf("/") + 1)))
					continue;
				try {
					FileInputStream fis = new FileInputStream(
							fileEntry.getPath());
					ObjectInputStream ois = new ObjectInputStream(fis);
					List<DBpediaInstance> instancesWithPropertiesTmp = (List) ois
							.readObject();
					ois.close();

					writeToCSV(writer, properties, instancesWithPropertiesTmp,
							classURI, totalPropSize, finalOrderProperties,
							isFirst);
					isFirst = false;
					fileEntry.delete();
				} catch (Exception e1) {
					// TODO Auto-generated catch block
					e1.printStackTrace();
				}

			}
			try {
				writer.write("]}");
				writer.close();
			} catch (IOException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}

		}

		/**
		 * Writes the sublist of instances to CSV
		 * 
		 * @param writer
		 * @param properties
		 * @param instancesWithProperties
		 * @param classURI
		 * @param totalPropSize
		 * @param finalOrderProperties
		 */
		private void writeToCSV(Writer writer,
				List<DBpediaProperty> properties,
				List<DBpediaInstance> instancesWithProperties, String classURI,
				int totalPropSize, List<String> finalOrderProperties,
				boolean isFirst) {

			// write the instances

			for (DBpediaInstance instanceProp : instancesWithProperties) {
				JSONObject objMain = new JSONObject();
				String[] entries = new String[totalPropSize];
				entries[0] = instanceProp.getUri();
				int i = 1;
				JSONObject obj = new JSONObject();
				for (String propURI : finalOrderProperties) {
					DBpediaProperty prop = DBpediaProperty.gerPropertyByURI(
							propURI, properties);
					if (prop.isObjectProperty()) {
						List<String> values = instanceProp.gerPropertyByURI(
								prop.getUri()).getValuesLabels();
						// compute the value
						String value = "NULL";
						boolean isAdded = false;
						if (values.size() > 0) {
							value = values.get(0).substring(
									values.get(0).lastIndexOf("/") + 1);
							if (!value.startsWith("http://")) {
								value = cleanString(value);
							}
							// if there are more values concatenate them into
							// one
							// value

							if (values.size() > 1) {
								JSONArray list = new JSONArray();

								for (String valueFromList : values) {
									if (!valueFromList.startsWith("http://")) {
										valueFromList = cleanString(valueFromList);
									}
									list.add(valueFromList);
								}
								obj.put(prop.getUri() + "_label", list);
								isAdded = true;
							}

						}
						if (!isAdded)
							obj.put(prop.getUri() + "_label", value);
					}
					List<String> values = instanceProp.gerPropertyByURI(
							prop.getUri()).getValues();
					// compute the value
					String value = "NULL";
					boolean isAdded = false;
					if (values.size() > 0) {
						value = values.get(0);
						if (!value.startsWith("http://")) {
							value = cleanString(value);
						}
						// if there are more values concatenate them into one
						// value
						if (values.size() > 1) {
							JSONArray list = new JSONArray();
							value = "";
							for (String valueFromList : values) {
								if (!valueFromList.startsWith("http://")) {
									valueFromList = cleanString(valueFromList);
								}

								list.add(valueFromList);
							}
							obj.put(prop.getUri(), list);
							isAdded = true;
						}
					}
					if (!isAdded)
						obj.put(prop.getUri(), value);
				}
				objMain.put(instanceProp.getUri(), obj);
				try {
					if (isFirst) {
						writer.write(objMain.toJSONString());
						isFirst = false;
					} else {
						writer.write(",\n" + objMain.toJSONString());
					}
				} catch (IOException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				}
			}

		}

		/**
		 * retrieves all instances for a given class, from the given SPARQL
		 * endpoint
		 * 
		 * @param classURI
		 * @return
		 */
		private List<String> getInstancesFromClass(String classURI) {
			List<String> instancesURIs = new ArrayList<String>();
			int offset = 0;
			// create the query
			ParameterizedSparqlString queryStringGetInstances = new ParameterizedSparqlString(
					GET_INSTANCES_OF_CLASS);
			queryStringGetInstances.setIri("?type", classURI);

			Query queryQGetInstances = QueryFactory
					.create(queryStringGetInstances.toString());
			queryQGetInstances = SPARQLEndpointQueryRunner
					.addOrderByToQuery(queryQGetInstances.toString());
			queryQGetInstances.setLimit(queryRunner.getPageSize());

			ResultSet RS = queryRunner.runSelectQuery(queryQGetInstances
					.toString());
			if (RS == null)
				return instancesURIs;
			while (true) {
				logger.info("Retrieving instances: " + offset + " - "
						+ (offset + queryRunner.getPageSize()));
				while (RS.hasNext()) {
					QuerySolution sol = RS.next();
					String instanceName = sol.get("Concept").toString();
					instancesURIs.add(instanceName);
				}
				offset += queryRunner.getPageSize();
				queryQGetInstances.setOffset(offset);
				queryQGetInstances.setLimit(queryRunner.getPageSize());
				RS = queryRunner.runSelectQuery(queryQGetInstances.toString());
				if (RS == null || !RS.hasNext())
					break;
			}

			return instancesURIs;
		}

		/**
		 * Extracts all properties and values for a given instance
		 * 
		 * @param instanceURI
		 * @return
		 */
		private DBpediaInstance processInstance(String instanceURI) {
			// create the query
			int offset = 0;
			ParameterizedSparqlString queryStringGetInstances = new ParameterizedSparqlString(
					GET_PROPERTIES_OF_INSTANCE);
			queryStringGetInstances.setIri("?instance", instanceURI);

			Query queryQGetInstances = QueryFactory
					.create(queryStringGetInstances.toString());
			// queryQGetInstances = SPARQLEndpointQueryRunner
			// .addOrderByToQuery(queryQGetInstances.toString());
			queryQGetInstances.setLimit(queryRunner.getPageSize());

			// initialize the instance
			DBpediaInstance instance = getInstanceFromFile(instanceURI);
			if (instance == null) {
				instance = new DBpediaInstance();

				instance.setUri(instanceURI);

				ResultSet RS = queryRunner.runSelectQuery(queryQGetInstances
						.toString());
				while (true) {
					while (RS.hasNext()) {
						QuerySolution sol = RS.next();
						String propUri = sol.get("prop").toString();
						String value = sol.get("object").toString();

						DBpediaInstanceProperty propertyOfInstance = instance
								.gerPropertyByURI(propUri);

						String range = "";
						String domainClass = "";
						String label = "";
						if (sol.contains("range")) {
							range = sol.get("range").toString();
						}
						if (sol.contains("DomainClass")) {
							domainClass = sol.get("DomainClass").toString();
						}
						if (sol.get("object").isLiteral()) {
							Literal valueLitteral = sol.getLiteral("object");
							// remove non english literals
							if (valueLitteral.toString().contains("@"))
								if (!valueLitteral.toString().endsWith("@en"))
									continue;
							value = valueLitteral.getString();
							if (range.equals(""))
								range = DBpediaProperty
										.guessAttributeType(valueLitteral);
						} else {
							label = value.substring(value.lastIndexOf("/") + 1);
							if (sol.contains("label"))
								label = sol.getLiteral("label").getString();
						}
						if (!propertyOfInstance.isFinalRange()) {
							if (range.equals("") && domainClass.equals(""))
								range = domainClass = DBpediaProperty.UNKNOWN_URI_SCHEMA_TYPE;
							if (range.equals("")) {
								if (domainClass
										.contains("http://www.w3.org/2002/07/owl#Class")) {
									range = domainClass;
								} else if (domainClass
										.contains("http://dbpedia.org/ontology/")
										|| domainClass
												.equals("http://www.w3.org/2002/07/owl#Thing")) {
									if (!propertyOfInstance.getTypes()
											.contains(domainClass)) {
										propertyOfInstance.getTypes().add(
												domainClass);
										propertyOfInstance
												.setFinalRange(domainClass);
									}
								}
							}
							if (!range.equals("")) {
								propertyOfInstance.setFinalRange(true);
								propertyOfInstance.setFinalRange(range);
							}

						}
						if (!propertyOfInstance.getValues().contains(value))
							propertyOfInstance.getValues().add(value);
						if (!propertyOfInstance.getValuesLabels().contains(
								label))
							propertyOfInstance.getValuesLabels().add(label);
					}
					queryRunner.closeConnection();
					offset += queryRunner.getPageSize();
					queryQGetInstances.setOffset(offset);
					queryQGetInstances.setLimit(queryRunner.getPageSize());
					RS = queryRunner.runSelectQuery(queryQGetInstances
							.toString());
					if (!RS.hasNext()) {
						queryRunner.closeConnection();
						break;
					}
				}
				saveInstanceToFile(instance);
			}
			for (DBpediaInstanceProperty propOfInstace : instance
					.getProperties()) {
				boolean shouldAdd = true;
				DBpediaProperty propFromClass = new DBpediaProperty();
				for (DBpediaProperty propOFClass : properties) {
					if (propOFClass.getUri().equals(propOfInstace.getUri())) {
						propFromClass = propOFClass;
						shouldAdd = false;
						break;
					}
				}
				if (shouldAdd) {
					propFromClass.setUri(propOfInstace.getUri());
					// the labels are ambiguous
					propFromClass.setLabel(propOfInstace.getUri().substring(
							propOfInstace.getUri().lastIndexOf("/") + 1));
				}
				if (propFromClass.getRange().containsKey(
						propOfInstace.getFinalRange())) {
					propFromClass.getRange().put(
							propOfInstace.getFinalRange(),
							propFromClass.getRange().get(
									propOfInstace.getFinalRange() + 1));
				} else {
					propFromClass.getRange().put(propOfInstace.getFinalRange(),
							1);
				}
				if (propFromClass.getFinalRange().equals("")) {
					propFromClass
							.setFinalRange("http://www.w3.org/2002/07/owl#Thing");
					propFromClass.setFinalRangeLabel("owl#Thing");
					if (propOfInstace.isFinalRange()) {
						propFromClass.setFinalRange(propOfInstace
								.getFinalRange());
						propFromClass.setFinalRangeLabel(propOfInstace
								.getFinalRange().substring(
										propOfInstace.getFinalRange()
												.lastIndexOf("/") + 1));
					}
				}
				if (shouldAdd) {
					if (propFromClass.getFinalRange().equals(
							"http://www.w3.org/2000/01/rdf-schema#Class")
							|| propFromClass.getFinalRange().equals(
									"http://www.w3.org/2002/07/owl#Thing")
							|| propFromClass.getFinalRange().contains(
									"http://dbpedia.org/ontology/")
							|| propFromClass.getFinalRange().equals(
									"http://www.w3.org/2002/07/owl#Class")) {
						propFromClass.setObjectProperty(true);
					}
					properties.add(propFromClass);
				}
			}
			return instance;
		}

		private DBpediaInstance getInstanceFromFile(String uri) {
			DBpediaInstance instance = new DBpediaInstance();
			try {
				FileInputStream fis = new FileInputStream("InstanceCash/"
						+ uri.substring(uri.lastIndexOf("/") + 1) + ".ser");
				ObjectInputStream ois = new ObjectInputStream(fis);
				instance = (DBpediaInstance) ois.readObject();
				ois.close();
				fis.close();

			} catch (Exception e1) {
				// TODO Auto-generated catch block
				return null;
			}
			System.out.println("skipping querys");
			return instance;
		}

		private void saveInstanceToFile(DBpediaInstance instance) {
			try {
				FileOutputStream fos = new FileOutputStream("InstanceCash/"
						+ instance.getUri().substring(
								instance.getUri().lastIndexOf("/") + 1)
						+ ".ser");
				ObjectOutputStream oos = new ObjectOutputStream(fos);
				oos.writeObject(instance);
				oos.close();
				fos.close();
			} catch (Exception e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
		}
	}

	/**
	 * retrieves all classes from the given SPARQL endpoint
	 * 
	 * @param query
	 * @return
	 */
	public List<String> getCLasses(String query) {
		List<String> classes = new ArrayList<String>();
		Query queryQGetInstances = QueryFactory.create(query);
		// queryQGetInstances = SPARQLEndpointQueryRunner
		// .addOrderByToQuery(queryQGetInstances.toString());
		ResultSet RS = queryRunner
				.runSelectQuery(queryQGetInstances.toString());
		int offset = 0;
		while (true) {
			while (RS.hasNext()) {
				QuerySolution sol = RS.next();
				String clazz = sol.get("type").toString();
				if (clazz.contains("http://dbpedia.org/ontology/"))
					classes.add(clazz);
			}
			offset += queryRunner.getPageSize();
			queryQGetInstances.setOffset(offset);
			queryQGetInstances.setLimit(queryRunner.getPageSize());

			break;
		}
		if (query.equals(GET_ALL_CLASSES))
			classes.add("http://www.w3.org/2002/07/owl#Thing");
		return classes;
	}

	/**
	 * removes all classes that are already processed
	 * 
	 * @param allClasses
	 */
	public static void removeDoneCLasses(List<String> allClasses) {

		File folder = new File("json");
		for (File fileEntry : folder.listFiles()) {
			if (allClasses.contains("http://dbpedia.org/ontology/"
					+ fileEntry.getName().replace(".json", "")))
				allClasses.remove("http://dbpedia.org/ontology/"
						+ fileEntry.getName().replace(".json", ""));
			if (fileEntry.getName().replace(".json", "").equals("Thing")
					&& allClasses
							.contains("http://www.w3.org/2002/07/owl#Thing"))
				allClasses.remove("http://www.w3.org/2002/07/owl#Thing");

			// too big to be represented as json
			allClasses.remove("http://www.w3.org/2002/07/owl#Thing");
			allClasses.remove("http://dbpedia.org/ontology/Agent");
			allClasses.remove("	http://dbpedia.org/ontology/Place");
			allClasses.remove("	http://dbpedia.org/ontology/Person");
		}

	}

	/**
	 * returns new logger
	 * 
	 * @return
	 */
	private static Logger getLogger() {
		Logger logger = Logger.getLogger("logger");
		FileHandler fh;

		try {

			// This block configure the logger with handler and formatter
			fh = new FileHandler("logger.log");
			logger.addHandler(fh);
			// logger.setLevel(Level.ALL);
			SimpleFormatter formatter = new SimpleFormatter();
			fh.setFormatter(formatter);

		} catch (SecurityException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
		return logger;
	}

	public static void generateFolders() {
		try {
			File folder = new File("json");
			if (folder.canRead() == false) {
				folder.mkdir();
			}
			folder = new File("tmpFiles");
			if (folder.canRead() == false) {
				folder.mkdir();
			}
			folder = new File("tmpProps");
			if (folder.canRead() == false) {
				folder.mkdir();
			}
			folder = new File("InstanceCash");
			if (folder.canRead() == false) {
				folder.mkdir();
			}
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	/**
	 * cleans the string from unwanted special characters
	 * 
	 * @param value
	 * @return
	 */
	public String cleanString(String value) {
		value = value.replace("\"", "");
		value = value.replace("|", "");
		value = value.replace(",", "");
		value = value.replace("{", "");
		value = value.replace("}", "");
		value = value.replaceAll("\n", "");
		return value;
	}

	/**
	 * invokes the process the first argument should be the SPARQL endpoint,
	 * otherwise the official DBpeidia endpoint will be used
	 * 
	 * @param args
	 */
	public static void main(String[] args) {

		String endpoint = SPARQLEndpointQueryRunner.DBPEDIA_ENDPOINT;
		if (args != null && args.length > 0 && args[0] != null)
			endpoint = args[0];
		Logger logger = getLogger();
		// generate folders if they are missing
		generateFolders();
		ClassToJson convetor = new ClassToJson(logger, endpoint);
		convetor.startExtraction();

		// for (String clazz : allClasses) {
		// convetor = new ClassToCSV(logger, endpoint);
		// convetor.convertClass(clazz);
		// }
	}

	private void startExtraction() {
		List<String> allClasses = getCLasses(GET_ALL_CLASSES);
		removeDoneCLasses(allClasses);
		// List<String> allClasses = new ArrayList<String>();
		// allClasses.add("http://dbpedia.org/ontology/Brain");
		int cores = Runtime.getRuntime().availableProcessors();
		ThreadPoolExecutor pool = new ThreadPoolExecutor(cores, cores, 0,
				TimeUnit.SECONDS,
				new java.util.concurrent.ArrayBlockingQueue<Runnable>(
						allClasses.size()));

		for (String clazz : allClasses) {
			ClassToCSVThread tc = new ClassToCSVThread(clazz,
					queryRunner.getEndpoint());

			pool.execute(tc);
		}
		pool.shutdown();
		try {
			pool.awaitTermination(5, TimeUnit.DAYS);
		} catch (InterruptedException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

	}

}
