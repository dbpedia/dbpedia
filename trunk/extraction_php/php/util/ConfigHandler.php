<?php

namespace dbpedia\util
{

use dbpedia\Redirect;
use dbpedia\util\PhpUtil;
use dbpedia\util\LockedFile;
use dbpedia\sources\FileSource;
use dbpedia\ontology\OntologyReader;
use dbpedia\mapping\ExtractionContext;
use dbpedia\mapping\ExtractionManager;
use dbpedia\mapping\MappingExtractor;
use dbpedia\destinations\StringQuadDestination;
use dbpedia\destinations\SingletonQuadDestinations;

/**
 * ConfigSerializer main object
 */
class ConfigHandler
{
    private $log;

    /**
     * Absolute path to ontology dir, using forward slashes.  Never null.
     * @var string
     */
    private $ontologyDir;
    
    /**
     * Absolute path to mapping dir, using forward slashes.  Never null.
     * @var string
     */
    private $mappingDir;
    
    /**
     * @var LockedFile
     */
    private $configFile;
    
    /**
     * @param $ontologyDir ontology dir path, string
     * @param $mappingDir mapping dir path, string
     * @param $configFile config file path, string
     */
    public function __construct( $ontologyDir, $mappingDir, $configFile )
    {
        $this->log = \dbpedia\core\DBpediaLogger::getLogger(__CLASS__);
        $this->ontologyDir = PhpUtil::assertDir($ontologyDir, 'ontology dir');
        $this->mappingDir = PhpUtil::assertDir($mappingDir, 'mapping dir');
        PhpUtil::assertString($configFile, 'config file');
        $this->configFile = new LockedFile($configFile);
    }
    
    public function updateOntology( $path, $source )
    {
        return $this->updateConfig($this->ontologyDir, $path, $source);
    }
    
    public function updateMapping( $path, $source )
    {
        return $this->updateConfig($this->mappingDir, $path, $source);
    }
    
    private function updateConfig( $dir, $path, $source )
    {
        PhpUtil::assertString($path, 'path');
        PhpUtil::assertString($source, 'source');
        
        // TODO: add '#1' etc. if necessary on Windows for file names that only differ in case
        // TODO: make suffix configurable
        $file = $dir . $path . '.txt';
        
        return $this->buildConfigFile($file, $source);
    }
    
    /**
     * @param $file 
     * @param $source 
     */
    private function buildConfigFile( $file = null, $source = null )
    {
        $secs = microtime(true);
        
        $this->configFile->lockForWrite();
        
        if ($file != null) file_put_contents($file, $source);
        
        $configHolder = $this->loadConfigDirs();
        
        $this->configFile->write(serialize($configHolder));
         
        $this->configFile->unlock();
        
        $this->log->info('successfully re-loaded config (' . (microtime(true) - $secs) * 1000 . ' millis)');
        
        return $configHolder;
    }
    
    private function loadConfigDirs()
    {
        $context = new ExtractionContext();
        
        $destination = new StringQuadDestination();
        $destinations = new SingletonQuadDestinations($destination);
        $context->setDestinations($destinations);
        
         // TODO: load redirects
        $redirects = new Redirect();
        $context->setRedirects($redirects);
        
        $ontology = $this->loadOntology();
        $extractor = $this->loadMappings($ontology, $context);
        
        $configHolder = new ConfigHolder($ontology, $extractor, $destinations);
        
        return $configHolder;
    }

    public function loadConfigFile()
    {
        $secs = microtime(true);
        
        $content = '';
        try
        {
            $content = $this->configFile->lockAndRead();
        }
        catch (\Exception $e)
        {
            // fall through
        }
        
        // failed to load file or file was empty
        if ($content === '') return $this->buildConfigFile();
        
        $configHolder = unserialize($content);
        
        $this->log->info('successfully loaded config file (' . (microtime(true) - $secs) * 1000 . ' millis)');
        
        return $configHolder;
    }
    
    /**
     * @param $abstracts should properties for AbstractExtractor be added to ontology?
     * TODO: Create an interface ExtractorBuilder with methods addProperties() and createExtractor().
     * Give this method an array of ExtractorBuilders.
     */
    public function loadOntology( $abstracts = true )
    {
        $ontologyPages = $this->loadAllPages($this->ontologyDir);

        $ontologyReader = new OntologyReader();
        $ontology = $ontologyReader->read($ontologyPages);
        
        \dbpedia\mapping\LabelExtractor::addProperties($ontology);
        if ($abstracts) \dbpedia\mapping\AbstractExtractor::addProperties($ontology);
        
        $this->destroyAllPages($ontologyPages);
        
        return $ontology;
    }

    /**
     *
     * @param $abstracts should AbstractExtractor be added? 
     * TODO: Create an interface ExtractorBuilder with methods addProperties() and createExtractor().
     * Give this method an array of ExtractorBuilders.
     */
    public function loadMappings( $ontology, $context, $abstracts = true )
    {
        $mappingPages = $this->loadAllPages($this->mappingDir);

        $extractor = new ExtractionManager();
        $extractor->addExtractor(MappingExtractor::load($mappingPages, $ontology, $context));
        
        $extractor->addExtractor(\dbpedia\mapping\LabelExtractor::load($ontology, $context));
        if ($abstracts) $extractor->addExtractor(\dbpedia\mapping\AbstractExtractor::load($ontology, $context));
        
        $this->destroyAllPages($mappingPages);
        
        return $extractor;
    }

    /**
     * @param $dir
     */
    private function loadAllPages( $dir )
    {
        $source = new FileSource($dir, array('.svn'));
        return $source->loadPages();
    }
    
    private function destroyAllPages( $pages )
    {
        foreach ($pages as $page)
        {
            $page->destroy();
        }
    }
    
}

}
