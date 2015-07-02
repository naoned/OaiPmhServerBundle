<?php

namespace Naoned\OaiPmhServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Naoned\OaiPmhServerBundle\Exception\UndefinedVerbException;

class MainController extends Controller
{
    public function indexAction()
    {
        $request = $this->get('request');
        $verb    = $request->get('verb');

        $methodName = $verb.'Verb';
        if (!method_exists($this, $methodName)) {
	        return $this->Error();
        }

        $this->viewVars = array(
        	'verb' => $verb,
        );
        return $this->$methodName();
    }

    private function Error()
    {
        return $this->render('NaonedOaiPmhServerBundle:Errors:IllegalVerb.xml.twig');
    }

    private function IdentifyVerb()
    {
        return $this->render(
        	'NaonedOaiPmhServerBundle::Identify.xml.twig',
        	$this->viewVars
        );
    }
}



