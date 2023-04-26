<?php

namespace SupportKd\CyxAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('CyxAuthBundle:Default:index.html.twig');
    }
}
