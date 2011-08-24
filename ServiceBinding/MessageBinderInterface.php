<?php
/*
 * This file is part of the BeSimpleSoapBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapBundle\ServiceBinding;

use BeSimple\SoapBundle\ServiceDefinition\Method;

/**
 * @author Christian Kerl <christian-kerl@web.de>
 */
interface MessageBinderInterface
{
    /**
     * @param Method $messageDefinition
     * @param mixed $message
     *
     * @return mixed
     */
    function processMessage(Method $messageDefinition, $message, array $definitionComplexTypes = array());
}