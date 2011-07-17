<?php
/*
 * This file is part of the WebServiceBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Bundle\WebServiceBundle\ServiceDefinition\Dumper;

use Bundle\WebServiceBundle\Util\String;

use Zend\Soap\Exception,
    Zend\Soap\Wsdl,
    Zend\Soap\Wsdl\Strategy,
    Zend\Soap\Wsdl\Strategy\DefaultComplexType,
    Zend\Soap\Wsdl\Strategy\ArrayOfTypeSequence;
    
class WsdlTypeStrategy implements Strategy
{
    /**
     * Context WSDL file
     *
     * @var \Zend\Soap\Wsdl|null
     */
    private $_context;

    private $typeStrategy;
    private $arrayStrategy;
    
    public function __construct()
    {
        $this->typeStrategy = new DefaultComplexType();
        $this->arrayStrategy = new ArrayOfTypeSequence();
    }
    
    /**
     * Method accepts the current WSDL context file.
     *
     * @param \Zend\Soap\Wsdl $context
     */
    public function setContext(Wsdl $context)
    {
        $this->_context = $context;
        return $this;
    }
    
    /**
     * Create a complex type based on a strategy
     *
     * @throws \Zend\Soap\WsdlException
     * @param  string $type
     * @return string XSD type
     */
    public function addComplexType($type)
    {
        if(!($this->_context instanceof Wsdl) ) {
            throw new Exception\InvalidArgumentException(
                "Cannot add complex type '$type', no context is set for this composite strategy."
            );
        }

        $strategy = String::endsWith($type, '[]') ? $this->arrayStrategy : $this->typeStrategy;
        $strategy->setContext($this->_context);
        return $strategy->addComplexType($type);
    }
}