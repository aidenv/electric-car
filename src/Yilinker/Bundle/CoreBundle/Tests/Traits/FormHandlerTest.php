<?php

namespace Yilinker\Bundle\CoreBundle\Tests\Traits;

use Symfony\Component\Form\Form;
use Yilinker\Bundle\CoreBundle\Tests\YilinkerCoreWebTestCase;
use Yilinker\Bundle\CoreBundle\Traits\ContainerHandler;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;

class FormHandlerTest extends YilinkerCoreWebTestCase
{
    use ContainerHandler;
	use FormHandler;

    public function testTransactForm()
    {
        $form = $this->getForm();

        $this->assertSame(true, $form instanceof Form);
        $this->assertNotNull($form);
        $this->assertTrue($form->isSubmitted());
    }

    public function testGetErrorsArray()
    {
        $form = $this->getForm();
        $errors = $this->getErrors($form, true);

        $this->assertSame(true, is_array($errors));
        $this->assertNotEmpty($errors);
    }

    public function testGetErrorsString()
    {
        $form = $this->getForm();
        $errors = $this->getErrors($form, false);

        $this->assertSame(false, is_array($errors));
        $this->assertNotEmpty($errors);
    }

    private function getForm()
    {
        $this->setMainContainer($this->client->getContainer());

        return $this->transactForm("core_user_add");
    }
}

