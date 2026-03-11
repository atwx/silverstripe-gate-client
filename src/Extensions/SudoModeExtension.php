<?php

namespace Atwx\SilverGateClient\Extensions;

use SilverStripe\Control\Session;
use SilverStripe\Core\Extension;

class SudoModeExtension extends Extension
{
    public function updateCheck(bool &$active, Session $session): void
    {
        if ($session->get('jwt-authenticated')) {
            $active = true;
        }
    }
}
