<?php
/*
 * This file is part of the WebServiceBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Bundle\WebServiceBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

use Bundle\WebServiceBundle\Soap\SoapRequest;
use Bundle\WebServiceBundle\Soap\SoapResponse;
use Bundle\WebServiceBundle\Soap\SoapHeader;
use Bundle\WebServiceBundle\Soap\SoapServerFactory;

use Bundle\WebServiceBundle\ServiceBinding\ServiceBinder;

use Bundle\WebServiceBundle\Converter\ConverterRepository;

use Bundle\WebServiceBundle\Util\String;

/**
 * 
 *
 * @author Christian Kerl <christian-kerl@web.de>
 */
class SoapWebServiceController extends ContainerAware
{
    /**
     * @var \SoapServer
     */
    protected $soapServer;

    /**
     * @var \Bundle\WebServiceBundle\Soap\SoapRequest
     */
    protected $soapRequest;

    /**
     * @var \Bundle\WebServiceBundle\Soap\SoapResponse
     */
    protected $soapResponse;

    /**
     * @var \Bundle\WebServiceBundle\ServiceBinding\ServiceBinder
     */
    protected $serviceBinder;

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    public function getRequest()
    {
        return $this->soapRequest;
    }

    public function getResponse()
    {
        return $this->soapResponse;
    }

    private function getHttpRequest()
    {
        return $this->container->get('request');
    }
    
    private function getHttpKernel()
    {
        return $this->container->get('http_kernel');
    }
    
    public function call($webservice)
    {
        $webServiceContext = $this->getWebServiceContext($webservice);

        $this->soapRequest = SoapRequest::createFromHttpRequest($this->getHttpRequest());

        $this->serviceBinder = $webServiceContext->getServiceBinder();
        
        $this->soapServer = $webServiceContext->getServerFactory()->create($this->soapRequest, $this->soapResponse);
        $this->soapServer->setObject($this);

        ob_start();
        {
            $this->soapServer->handle($this->soapRequest->getSoapMessage());
        }
        $soapResponseContent = ob_get_clean();
        
        $this->soapResponse->setContent($soapResponseContent);

        return $this->soapResponse;
    }

    public function definition($webservice)
    {
        $webServiceContext = $this->getWebServiceContext($webservice);
        $request = $this->container->get('request');
        
        if($request->query->has('WSDL'))
        {
            $endpoint = $this->container->get('router')->generate('_webservice_call', array('webservice' => $webservice), true);
            
            $response = new Response($webServiceContext->getWsdlFileContent($endpoint));
            $response->headers->set('Content-Type', 'application/wsdl+xml');
        }
        else
        {
            // TODO: replace with better representation
            $response = new Response($webServiceContext->getWsdlFileContent());
            $response->headers->set('Content-Type', 'text/xml');
        }
        
        return $response;
    }

    private function getWebServiceContext($webservice)
    {
        if(!$this->container->has('webservice.context.' . $webservice))
        {
            throw new NotFoundHttpException(sprintf("No webservice with name '%s' found!", $webservice));
        }
        return $this->container->get('webservice.context.' . $webservice);
    }
    
    /**
     * This method gets called once for every SOAP header the \SoapServer received
     * and afterwards once for the called SOAP operation.
     *
     * @param string $method The SOAP header or SOAP operation name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if($this->serviceBinder->isServiceHeader($method))
        {
            // collect request soap headers
            $this->soapRequest->getSoapHeaders()->add(
                $this->serviceBinder->processServiceHeader($method, $arguments[0])
            );

            return;
        }

        if($this->serviceBinder->isServiceMethod($method))
        {
            $this->soapRequest->attributes->add(
                $this->serviceBinder->processServiceMethodArguments($method, $arguments)
            );

            // forward to controller
            $response = $this->getHttpKernel()->handle($this->soapRequest, HttpKernelInterface::SUB_REQUEST, false);

            $this->soapResponse = $this->checkResponse($response);

            // add response soap headers to soap server
            foreach($this->soapResponse->getSoapHeaders() as $header)
            {
                $this->soapServer->addSoapHeader($header->toNativeSoapHeader());
            }

            // return operation return value to soap server
            return $this->serviceBinder->processServiceMethodReturnValue(
                $method,
                $this->soapResponse->getReturnValue()
            );
        }
    }

    /**
     * Checks that the given Response is a SoapResponse.
     *
     * @param Response $response A response to check
     *
     * @return SoapResponse A valid SoapResponse
     *
     * @throws InvalidArgumentException if the given Response is null or not a SoapResponse
     */
    protected function checkResponse(Response $response)
    {
        if($response == null || !$response instanceof SoapResponse)
        {
            throw new \InvalidArgumentException();
        }

        return $response;
    }
}
