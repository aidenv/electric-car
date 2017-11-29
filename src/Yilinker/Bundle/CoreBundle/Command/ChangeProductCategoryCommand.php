<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\systemcategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategoryTranslation;
use Yilinker\Bundle\CoreBundle\Entity\Language;

class ChangeProductCategoryCommand extends Command
{
    const ORDERPRODUCT_PER_ITERATION = 100;
    
    protected function configure()
    {
        $this
            ->setName('yilinker:category:initCategory')
            ->setDescription('更新分类信息');
        ;
    }

    /**
     * 1,读取出来分类的信息
     * 2.分类的信息对比现在当前分类的信息,没有数据的就新入库数据
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();
        $output->writeln("Start ... [OK]");

        $systemcategoryRep = $em->getRepository('YilinkerCoreBundle:systemcategory');
        $productCategoryReps = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        
        foreach($systemcategoryRep->findAll() as $v){
            if(null == $productCategoryReps->findOneBy(['referenceNumber'=>$v->getSystemCategoryId()])) {
                $productCategory = new ProductCategory();
                $categoryParentId = ($v->getSystemCategoryParentId()==0) ? 'parent': $v->getSystemCategoryParentId();
                $parent = $productCategoryReps->findOneBy(['referenceNumber'=>$categoryParentId]);
                $productCategory->setParent($parent);
                $productCategory->setName($v->getSystemCategoryNameUS());
                $slug = $v->getSystemCategoryNameUS();
                $slug = trim($slug);
                $slug = strtolower($slug);
                $slug = str_replace('&', '-', $slug);
                $productCategory->setSlug($slug);
                $productCategory->setDescription($v->getSystemCategoryDescriptionUS());
                $productCategory->setReferenceNumber($v->getSystemCategoryId());
                $productCategory->setIsDelete(false);
                $productCategory->setDateAdded(new \DateTime());
                $productCategory->setDateLastModified(new \DateTime());

//                dump($v, $productCategory);exit;

                $pct = new ProductCategoryTranslation();
                $pct->setProductCategory($productCategory);
                $pct->setDescription($v->getSystemCategoryDescriptionCN());
                $language = $em->getRepository('YilinkerCoreBundle:Language')->findOneBy(['languageId'=>2]);
                $pct->setLanguage($language);
                $pct->setName($v->getSystemCategoryNameCN());

                $em->persist($productCategory);
                $em->persist($pct);
                $em->flush();
                echo $productCategory->getProductCategoryId() . "\n\r";
            }
        }
        $output->writeln("End ... [OK]");


    }

}