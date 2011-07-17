<?php
/*
 * This file is part of the WebServiceBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Bundle\WebServiceBundle\Soap;

/**
 *
 * @author Christian Kerl <christian-kerl@web.de>
 */
use Bundle\WebServiceBundle\ServiceDefinition\Type;

use Bundle\WebServiceBundle\ServiceDefinition\Dumper\FileDumper;

use Bundle\WebServiceBundle\Converter\ConverterRepository;

use Bundle\WebServiceBundle\SoapKernel;

use Bundle\WebServiceBundle\Util\QName;

use Bundle\WebServiceBundle\ServiceDefinition\ServiceDefinition;

class SoapServerFactory
{
    private $wsdlFile;
    private $classmap;
    private $converters;

    public function __construct($wsdlFile, array $classmap, ConverterRepository $converters)
    {
        $this->wsdlFile = $wsdlFile;
        $this->classmap = $classmap;
        $this->converters = $converters;
    }

    public function create(&$request, &$response)
    {
        $server = new \SoapServer(
            $this->wsdlFile,
            array(
                'classmap' => $this->classmap,
            	'typemap'  => $this->createSoapServerTypemap($request, $response),
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            )
        );

        return $server;
    }

    private function createSoapServerTypemap(&$request, &$response)
    {
        $result = array();

        foreach($this->converters->getTypeConverters() as $typeConverter)
        {
            $result[] = array(
                'type_name' => $typeConverter->getTypeName(),
                'type_ns' => $typeConverter->getTypeNamespace(),
                'from_xml' => function($input) use (&$request, $typeConverter) {
                    return $typeConverter->convertXmlToPhp($request, $input);
                },
                'to_xml' => function($input) use (&$response, $typeConverter) {
                    return $typeConverter->convertPhpToXml($response, $input);
                }
            );
        }

        return $result;
    }
}
