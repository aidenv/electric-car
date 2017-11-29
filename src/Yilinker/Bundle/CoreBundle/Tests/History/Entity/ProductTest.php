<?php
namespace Yilinker\Bundle\CoreBundle\Tests\Entity;

use PHPUnit_Framework_TestCase;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

class ProductTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->loadFixtures(array(
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\LoadUserData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\LoadBrandData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\LoadProductCategoryData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\LoadProductConditionData',
        ), null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE);
    }

    /**
     * Tests if the product slug is generated correctly
     * This test has to connect to a real DBAL to be able to
     * test the slug generation (mocks won't do)
     */
    public function testSlugGeneration()
    {
        $entityManager =  $this->getContainer()
                               ->get('doctrine')
                               ->getManager();
       
        $user = $entityManager->getRepository("Yilinker:User")
                              ->findOneBy(['username' => 'sample_user']);
        $brand = $entityManager->getRepository("Yilinker:Brand")
                               ->findOneBy(['name' => 'Sample Brand']);
        $category = $entityManager->getRepository("Yilinker:ProductCategory")
                                  ->findOneBy(['name' => 'Top Level Category']);
        $condition =  $entityManager->getRepository("Yilinker:ProductCondition")
                                    ->findOneBy(['name' => 'New']);
        //Test simple slug
        $productOne = new Product();
        $productOne->setName('Symfony is awesome');
        $productOne->setDateCreated(new \DateTime("now"));
        $productOne->setDateLastModified(new \DateTime("now"));
        $productOne->setUser($user);
        $productOne->setBrand($brand);
        $productOne->setBasePrice(0);
        $productOne->setDescription("");
        $productOne->setCondition($condition);
        $productOne->setProductCategory($category);

        //Test slug with product multiple spaces
        $productTwo = new Product();
        $productTwo->setName('Double  spaces');
        $productTwo->setDateCreated(new \DateTime("now"));
        $productTwo->setDateLastModified(new \DateTime("now"));
        $productTwo->setUser($user);
        $productTwo->setBrand($brand);
        $productTwo->setBasePrice(0);
        $productTwo->setDescription("");
        $productTwo->setCondition($condition);
        $productTwo->setProductCategory($category);

        //Test slug number increment for the same product name
        $productThree = new Product();
        $productThree->setName('Symfony is awesome');
        $productThree->setDateCreated(new \DateTime("now"));
        $productThree->setDateLastModified(new \DateTime("now"));
        $productThree->setUser($user);
        $productThree->setBrand($brand);
        $productThree->setBasePrice(0);
        $productThree->setDescription("");
        $productThree->setCondition($condition);
        $productThree->setProductCategory($category);

        $entityManager->persist($productOne);
        $entityManager->persist($productTwo);
        $entityManager->persist($productThree);
        $entityManager->flush();

        $this->assertEquals($productOne->getSlug(), 'symfony-is-awesome');
        $this->assertEquals($productTwo->getSlug(), 'double-spaces');
        $this->assertEquals($productThree->getSlug(), 'symfony-is-awesome-1');
    }

}
