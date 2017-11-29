<?php

namespace Yilinker\Bundle\CoreBundle\Command\Translation;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductCategoryTranslateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('yilinker:translation:productcategory')
             ->setDescription('Migrate ProductCategoryTranslation to ext_translation_product_category')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //ini_set('memory_limit', '50M');
        ini_set('memory_limit','-1');

        $this->migrate();
        $output->writeln("<info> migrated </info>");
    }

    private function migrate()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();
        $conn = $em->getConnection();
        
        $productCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $productCategoryTranslation = $conn->fetchAll("select product_category_id, name from ProductCategoryTranslation");

        foreach ($productCategoryTranslation as $key => $trans) {
            
            if ($category = $productCategory->find($trans['product_category_id'])) {
                $category->setName($trans['name']);
                $category->setLocale('cn');

                $em->persist($category);
                $em->flush();
                $em->clear();
                
                unset($category);
                gc_collect_cycles();
        
                echo "real: ".(memory_get_usage()/1024/1024)." MiB\n\n";
            }
        }
    }
}
