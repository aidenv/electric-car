<?php
namespace Yilinker\Bundle\CoreBundle\Command;

use RecursiveDirectoryIterator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Yilinker\Bundle\CoreBundle\Entity\Product;


/**
 * Generate Sitemap
 *
 * @package Yilinker\Bundle\FrontendBundle\Command
 */
class GenerateSitemapCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker:generate:sitemap')
            ->setDescription('Generate Sitemap')
            
            ->addOption('section',null,InputOption::VALUE_OPTIONAL,'Section of page','items')
            ->setHelp(<<<EOF
<info>--section=[items,categories,stores]</info>
EOF
            );

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->resourceDir = $this->getContainer()->get('kernel')->locateResource("@YilinkerCoreBundle/Resources");
        $this->router = $this->getContainer()->get('router');
        $this->templating = $this->getContainer()->get('templating');
        $this->filesystem = $this->getContainer()->get('filesystem');

        $sitemaps = array();
        $section = $input->getOption("section");

        if ($section == 'items') {
            $sitemaps[] = $this->generateItems();
        } else if ($section == 'categories') {
            $sitemaps[] = $this->generateCategories();
        } else if ($section == 'stores') {
            $sitemaps[] = $this->generateStores();
        }

        $this->generateIndexSitemaps($sitemaps);

        $output->writeln("<info> Succesfully generated!.</info>");
        $output->writeln("<info> Chect at web/assets/sitemap/ </info> ");
        
    }

    protected function generateItems($page = 1)
    {
        $productSearchService = $this->getContainer()->get('yilinker_core.service.search.product');
        
        $productSearch = $productSearchService->searchProductsWithElastic(
            '',null,null,null,null,null,null,null,null,null,$page,200 
        );

        $html = $this->templating->render('YilinkerCoreBundle:Sitemap:_product_item_sitemap.xml.twig', array('products' => $productSearch['products']));
        $sitemapDir = $this->resourceDir. DIRECTORY_SEPARATOR. 'sitemap';
        $tmpFilename = $sitemapDir.DIRECTORY_SEPARATOR. 'product-item-sitemap.tmp.xml';
        $filename = $sitemapDir.DIRECTORY_SEPARATOR. 'product-item-sitemap.xml';

        $this->filesystem->mkdir($sitemapDir);
        file_put_contents($tmpFilename, $html, FILE_APPEND);
        
        if ($page == $productSearch['totalPage']) {
            $content = file_get_contents($tmpFilename);

            $data = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    $content    
</urlset>
EOF;
        
            $this->save($sitemapDir,$filename,$data); 
            $this->filesystem->remove($tmpFilename);

        }

        //recursion
        if ($page < $productSearch['totalPage']) {
            unset($productSearch);
            $this->generateItems(++$page);
        }

        return 'product-item-sitemap.xml.gz';        
    }
    
    protected function generateCategories()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $categoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        
        $categories = $categoryRepository->getMainCategories("ASC", "name");

        $html = $this->templating->render('YilinkerCoreBundle:Sitemap:_all_categories_sitemap.xml.twig', array('categories' => $categories));
        
        $sitemapDir = $this->resourceDir. DIRECTORY_SEPARATOR. 'sitemap';
        $filename = $sitemapDir.DIRECTORY_SEPARATOR. 'categories-sitemap.xml';

        $this->save($sitemapDir,$filename,$html);       

        return 'categories-sitemap.xml.gz';
    }

    protected function generateStores()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $stores = $em->getRepository('YilinkerCoreBundle:Store')->getActiveStoreList();
        
        $html = $this->templating->render('YilinkerCoreBundle:Sitemap:_stores_sitemap.xml.twig', array('stores' => $stores));

        $sitemapDir = $this->resourceDir. DIRECTORY_SEPARATOR. 'sitemap';
        $filename = $sitemapDir.DIRECTORY_SEPARATOR. 'stores-sitemap.xml';

        $this->save($sitemapDir,$filename,$html); 

        return 'stores-sitemap.xml.gz';
    }


    protected function generateIndexSitemaps($sitemaps)
    {

        $html = $this->templating->render('YilinkerCoreBundle:Sitemap:_sitemap-index.xml.twig', array(
            'sitemaps' => $sitemaps,
            'sitemap_hostname' => $this->getContainer()->getParameter('sitemap_hostname') 
        ));

        $sitemapDir = $this->resourceDir. DIRECTORY_SEPARATOR. 'sitemap';
        $filename = $sitemapDir.DIRECTORY_SEPARATOR. 'index-sitemap.xml';

        $this->save($sitemapDir,$filename,$html);

        return 'index-sitemap.xml.gz';      
    }

    private function save($sitemapDir,$filename,$html)
    {
        $this->filesystem->mkdir($sitemapDir);

        $this->filesystem->dumpFile($filename,$html);

        $this->gzipfile($filename);

    }

    private function gzipfile($filename)
    {
        $fp = fopen($filename, "r");

        $filenameGz = $filename . '.gz';
        fseek($fp, 0);
        $sitemapFileGz = gzopen($filenameGz, 'wb9');
        while (!feof($fp)) {
            gzwrite($sitemapFileGz, fread($fp, filesize($filename)));
        }
        fclose($fp);
        gzclose($sitemapFileGz);
    }
}
