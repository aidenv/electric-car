<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Custom;

interface UserVerifiedController
{
    public function allowUnverifiedActions();
    public function unverifiedAction();
}