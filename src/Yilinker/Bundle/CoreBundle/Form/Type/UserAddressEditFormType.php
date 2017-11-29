<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Yilinker\Bundle\CoreBundle\Form\Type\UserAddressFormType;

class UserAddressEditFormType extends UserAddressFormType
{
    public function getName()
    {
        return 'user_address_edit';
    }
}