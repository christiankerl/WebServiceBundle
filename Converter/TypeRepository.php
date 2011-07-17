<?php
/*
 * This file is part of the WebServiceBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Bundle\WebServiceBundle\Converter;

use Bundle\WebServiceBundle\Util\String;

use Bundle\WebServiceBundle\Util\Assert;

use Bundle\WebServiceBundle\ServiceDefinition\ServiceDefinition;
use Bundle\WebServiceBundle\Util\QName;

/**
 *
 * @author Christian Kerl <christian-kerl@web.de>
 */
class TypeRepository
{
    const ARRAY_SUFFIX = '[]';
    
    private $xmlNamespaces = array();
    private $defaultTypeMap = array();
    
    public function addXmlNamespace($prefix, $url)
    {
        $this->xmlNamespaces[$prefix] = $url;
    }
    
    public function getXmlNamespace($prefix)
    {
        return $this->xmlNamespaces[$prefix];
    }
    
    public function addDefaultTypeMapping($phpType, $xmlType)
    {
        Assert::thatArgumentNotNull('phpType', $phpType);
        Assert::thatArgumentNotNull('xmlType', $xmlType);
        
        $this->defaultTypeMap[$phpType] = $this->getQName($xmlType);
    }
    
    public function fixTypeInformation(ServiceDefinition $definition)
    {
        $typeMap = $this->defaultTypeMap;
        
        foreach($definition->getAllTypes() as $type)
        {
            $phpType = $type->getPhpType();
            $xmlType = $type->getXmlType();
            
            if($phpType === null)
            {
                throw new \InvalidArgumentException();
            }
            
            if($xmlType === null)
            {
                if(!isset($typeMap[$phpType]))
                {
                    $parts = explode('\\', $phpType);
                    $xmlTypeName = ucfirst(end($parts));
                    
                    if(String::endsWith($phpType, self::ARRAY_SUFFIX))
                    {
                        $xmlTypeName = str_replace(self::ARRAY_SUFFIX, 'Array', $xmlTypeName);
                    }
                    
                    $typeMap[$phpType] = new QName($definition->getNamespace(), $xmlTypeName);
                }
                
                $xmlType = $typeMap[$phpType];
            }
            else
            {
                $xmlType = $this->getQName($xmlType);
            }
            
            $type->setXmlType((string) $xmlType);
        }
    }
    
    private function getQName($xmlType)
    {
        if(QName::isPrefixedQName($xmlType))
        {   
            return QName::fromPrefixedQName($xmlType, array($this, 'getXmlNamespace'));
        }
        else
        {
            return QName::fromPackedQName($xmlType);
        }
    }
    
    public function createComplexTypeMap(ServiceDefinition $definition)
    {
        
    }
}