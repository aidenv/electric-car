<?php

namespace Yilinker\Bundle\CoreBundle\Services\Cms;


/**
 * Class XMLParserService
 * @package Yilinker\Bundle\FrontendBundle\Services\Pages
 */
class XMLParserService
{
    /**
     * Search and return values of the given node(regardless of node deepness).
     *
     * @param $xmlObj
     * @param $node
     * @return array
     */
    public function getAllNodeValues($xmlObj, $node)
    {
        //find all nodes with the node name given by the parameter
        $nodes = $xmlObj->xpath('.//'.$node);

        $nodeValues = array();

        //push node values to the array
        foreach($nodes as $nodeValue){
            array_push($nodeValues, (string) $nodeValue);
        }

        return array_unique($nodeValues);
    }
    /**
     * Search and return attribute values of the given node(regardless of node deepness).
     *
     * @param $xmlObj
     * @param $node
     * @return array
     */
    public function getAllNodeAttributeValues($xmlObj, $node, $attribute)
    {
        //find all nodes with the node name given by the parameter
        $nodes = $xmlObj->xpath('.//'.$node);

        $nodeAttributeValues = array();

        //push node values to the array
        foreach($nodes as $node){
            array_push($nodeAttributeValues, (string) $node[$attribute]);
        }

        return array_unique($nodeAttributeValues);
    }

    public function getNodeValues($xmlObj, $xpath)
    {
        $nodes = $xmlObj->xpath($xpath);
        $nodeValues = array();
        foreach($nodes as $nodeValue){
            array_push($nodeValues, (string) $nodeValue);
        }

        return array_unique($nodeValues);
    }
    
    /**
     * Retrieve an XML node with the given ID attribute
     *
     * @param XML $xmlObject
     * @param string $node
     * @param string $id
     */
    public function getNodeWithId($xmlObject, $node, $id)
    {
        $nodes = $xmlObject->xpath($node.'[@id="'.$id.'"]');

        return reset($nodes);
    }


}