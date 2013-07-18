<?php
namespace dbpedia\dataparser
{
use dbpedia\wikiparser\Node;

//recognized Month for Date extraction
//month must be lowercase !!
$GLOBALS['month']=array('en' => array('january'=>'01','february'=>'02','march'=>'03','april'=>'04','may'=>'05','june'=>'06','july'=>'07','august'=>'08','september'=>'09','october'=>'10','november'=>'11','december'=>'12'),
                        'de' => array('januar'=>'01','februar'=>'02','m�rz'=>'03','maerz'=>'03','april'=>'04','mai'=>'05','juni'=>'06','juli'=>'07','august'=>'08','september'=>'09','oktober'=>'10','november'=>'11','dezember'=>'12'),
                        'fr' => array('janvier'=>'01','f�vrier'=>'02','mars'=>'03','avril'=>'04','mai'=>'05','juin'=>'06','juillet'=>'07','ao�t'=>'08','septembre'=>'09','octobre'=>'10','novembre'=>'11','d�cembre'=>'12'),
                        'it' => array('gennaio'=>'01','febbraio'=>'02','marzo'=>'03','aprile'=>'04','maggio'=>'05','giugno'=>'06','luglio'=>'07','agosto'=>'08','settembre'=>'09','ottobre'=>'10','novembre'=>'11','dicembre'=>'12'));

//recognized scales
$GLOBALS['scale']=array('thousand'=>'1000','million'=>'1000000','mio'=>'1000000','billion'=>'1000000000','mrd'=>'1000000000','trillion'=>'1000000000000',
'quadrillion'=>'1000000000000000');

/**
 *
 * @author Paul Kreis
 */
interface DataParser {

    /**
     * Parses a WikiText section.
     *
     * @param Node $node The node representing the WikiText to be parsed
     * @throws DataParserException If an parsing error occurs
     */
    public function parse(Node $node);
}
}
