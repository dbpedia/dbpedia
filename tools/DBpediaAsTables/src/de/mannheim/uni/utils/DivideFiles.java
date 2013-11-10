package de.mannheim.uni.utils;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.ObjectInputStream;
import java.io.ObjectOutputStream;
import java.util.ArrayList;
import java.util.List;

import de.mannheim.uni.model.DBpediaInstance;

public class DivideFiles {
	public static void main(String[] args) {
		DivideFiles();
	}

	public static void DivideFiles() {
		File folder = new File("tmpFiles");
		for (File fileEntry : folder.listFiles()) {
			try {
				FileInputStream fis = new FileInputStream(fileEntry.getPath());
				ObjectInputStream ois = new ObjectInputStream(fis);
				List<DBpediaInstance> instancesWithPropertiesTmp = (List) ois
						.readObject();
				ois.close();

				List<DBpediaInstance> instancesFirstPart = new ArrayList<DBpediaInstance>();
				instancesFirstPart.addAll(instancesWithPropertiesTmp.subList(0,
						instancesWithPropertiesTmp.size() / 2));
				List<DBpediaInstance> instancesSecondtPart = new ArrayList<DBpediaInstance>();
				instancesSecondtPart.addAll(instancesWithPropertiesTmp.subList(
						instancesWithPropertiesTmp.size() / 2,
						instancesWithPropertiesTmp.size()));

				try {
					FileOutputStream fos = new FileOutputStream("tmpParts\\"
							+ fileEntry.getName().replace(".ser", "First")
							+ instancesFirstPart.size() + ".ser");
					ObjectOutputStream oos = new ObjectOutputStream(fos);
					oos.writeObject(instancesFirstPart);
					oos.close();
				} catch (Exception e1) {
					// TODO Auto-generated catch block
					e1.printStackTrace();
				}
				try {
					FileOutputStream fos = new FileOutputStream("tmpParts\\"
							+ fileEntry.getName().replace(".ser", "Second")
							+ ".ser");
					ObjectOutputStream oos = new ObjectOutputStream(fos);
					oos.writeObject(instancesSecondtPart);
					oos.close();
				} catch (Exception e1) {
					// TODO Auto-generated catch block
					e1.printStackTrace();
				}

			} catch (Exception e1) {
				// TODO Auto-generated catch block
				e1.printStackTrace();
			}
		}
	}
}
