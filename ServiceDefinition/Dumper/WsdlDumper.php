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

use Bundle\WebServiceBundle\Util\QName;

use Bundle\WebServiceBundle\ServiceDefinition\Type;

use Bundle\WebServiceBundle\ServiceDefinition\Method;
use Bundle\WebServiceBundle\ServiceDefinition\ServiceDefinition;

use Bundle\WebServiceBundle\Util\Assert;

use Zend\Soap\Wsdl;


/**
 *
 * @author Christian Kerl <christian-kerl@web.de>
 */
class WsdlDumper implements DumperInterface
{
    private $wsdl;
    private $definition;
    
    public function dumpServiceDefinition(ServiceDefinition $definition, array $options = array())
    {
        Assert::thatArgumentNotNull('definition', $definition);

        $options = array_merge(array('endpoint' => '', 'stylesheet' => null), $options);
        
        $this->definition = $definition;

        $wsdl = $this->wsdl = new Wsdl($definition->getName(), $definition->getNamespace(), new WsdlTypeStrategy());
        
        $port = $wsdl->addPortType($this->getPortTypeName());
        $binding = $wsdl->addBinding($this->getBindingName(), $this->qualify($this->getPortTypeName()));
        
        $wsdl->addSoapBinding($binding, 'rpc');
        $wsdl->addService($this->getServiceName(), $this->getPortName(), $this->qualify($this->getBindingName()), $options['endpoint']);

        foreach($definition->getMethods() as $method)
        {
            $requestParts = array();
            $responseParts = array();

            foreach($method->getArguments() as $argument)
            {
                $requestParts[$argument->getName()] = $wsdl->getType($argument->getType()->getPhpType());
            }

            if($method->getReturn() !== null)
            {
                $responseParts['return'] = $wsdl->getType($method->getReturn()->getPhpType());
            }

            $wsdl->addMessage($this->getRequestMessageName($method), $requestParts);
            $wsdl->addMessage($this->getResponseMessageName($method), $responseParts);

            $portOperation = $wsdl->addPortOperation($port, $method->getName(), $this->qualify($this->getRequestMessageName($method)), $this->qualify($this->getResponseMessageName($method)));
            $portOperation->setAttribute('parameterOrder', implode(' ', array_keys($requestParts)));

            $bindingInput = array(
                'parts' => implode(' ', array_keys($requestParts)),
                'use' => 'literal',
                'namespace' => $definition->getNamespace(),
                'encodingStyle' => 'http://schemas.xmlsoap.org/soap/encoding/',
            );
            $bindingOutput = array(
                'parts' => implode(' ', array_keys($responseParts)),
                'use' => 'literal',
                'namespace' => $definition->getNamespace(),
                'encodingStyle' => 'http://schemas.xmlsoap.org/soap/encoding/',
            );

            $bindingOperation = $wsdl->addBindingOperation($binding, $method->getName(), $bindingInput, $bindingOutput);
            $wsdl->addSoapOperation($bindingOperation, $this->getSoapOperationName($method));
        }

        $this->definition = null;

        $dom = $wsdl->toDomDocument();
        $dom->formatOutput = true;
        
        if($options['stylesheet'] !== null)
        {
            $stylesheet = $dom->createProcessingInstruction('xml-stylesheet', sprintf('type="text/xsl" href="%s"', $options['stylesheet']));
            
            $dom->insertBefore($stylesheet, $dom->documentElement);
        }
        
        return $wsdl->toXml();
    }
    
    protected function qualify($name, $namespace = null)
    {
        if($namespace === null)
        {
            $namespace = $this->definition->getNamespace();
        }
        
        return $this->wsdl->toDomDocument()->lookupPrefix($namespace) . ':' . $name;
    }
    
    protected function getPortName()
    {
        return $this->definition->getName() . 'Port';
    }
    
    protected function getPortTypeName()
    {
        return $this->definition->getName() . 'PortType';
    }

    protected function getBindingName()
    {
        return $this->definition->getName() . 'Binding';
    }

    protected function getServiceName()
    {
        return $this->definition->getName() . 'Service';
    }

    protected function getRequestMessageName(Method $method)
    {
        return $method->getName() . 'Request';
    }

    protected function getResponseMessageName(Method $method)
    {
        return $method->getName() . 'Response';
    }

    protected function getSoapOperationName(Method $method)
    {
        return $this->definition->getNamespace() . $method->getName();
    }
}
