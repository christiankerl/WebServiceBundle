<?php
/*
 * This file is part of the BeSimpleSoapBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapBundle\ServiceDefinition;

use BeSimple\SoapBundle\Util\Collection;

class ServiceDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var \BeSimple\SoapBundle\Util\Collection
     */
    private $methods;

    private $complexTypes = array();

    public function __construct($name = null, $namespace = null, array $methods = array())
    {
        $this->setName($name);
        $this->setNamespace($namespace);

        $this->methods = new Collection('getName', 'BeSimple\SoapBundle\ServiceDefinition\Method');

        $this->setMethods($methods);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @return \BeSimple\SoapBundle\Util\Collection
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param array $methods
     */
    public function setMethods(array $methods)
    {
        $this->methods->addAll($methods);
    }

    /**
     * @return array
     */
    public function getAllTypes()
    {
        $types = array();

        foreach ($this->methods as $method) {
            foreach ($method->getArguments() as $argument) {
                $types[] = $argument->getType();
            }

            foreach ($method->getHeaders() as $header) {
                $types[] = $header->getType();
            }

            $types[] = $method->getReturn();
        }

        return $types;
    }

    public function addDefinitionComplexType($type, Collection $complexType)
    {
        $this->complexTypes[$type] = $complexType;
    }

    public function getDefinitionComplexTypes()
    {
        return $this->complexTypes;
    }
    
    public function getAllTypes()
    {
        $result = array();
        
        foreach($this->methods as $method)
        {
            foreach($method->getArguments() as $argument)
            {
                $result[] = $argument->getType();
            }
            
            $result[] = $method->getReturn();
        }
        
        return $result;
    }
}