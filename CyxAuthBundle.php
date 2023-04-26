<?php

namespace SupportKd\CyxAuthBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CyxAuthBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
