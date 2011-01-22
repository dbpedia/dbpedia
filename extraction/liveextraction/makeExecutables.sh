#!/bin/bash
JARPATH=""; 
for jar in `find lib -name "*.jar"`
do JARPATH=$JARPATH:$jar 
done

echo "#!/bin/bash
java -Xms128m -Xmx1024m -cp ."$JARPATH":Extraction.jar oaiReader.OAIReaderMain \$@" > run_DefaultExtraction.sh
chmod 744 run_DefaultExtraction.sh

echo "#!/bin/bash
java -Xms128m -Xmx1024m  -cp ."$JARPATH":Extraction.jar oaiReader.MetaExtractionMain \$@" > run_MetaExtraction.sh
chmod 744 run_MetaExtraction.sh

echo "#!/bin/bash
java  -Xms128m -Xmx1024m -cp ."$JARPATH":Extraction.jar oaiReader.OaiMappingExtractionMain \$@" > run_OaiMappingExtraction.sh
chmod 744 run_OaiMappingExtraction.sh

echo "#!/bin/bash
java  -Xms128m -Xmx1024m -cp ."$JARPATH":Extraction.jar oaiReader.TestEncoding \$@" > run_TestEncoding.sh
chmod 744 run_TestEncoding.sh


echo "#!/bin/bash
java  -Xms128m -Xmx1024m -cp ."$JARPATH":Extraction.jar main.TaskProcessor \$@" > run_TaskProcessor.sh
chmod 744 run_TaskProcessor.sh

