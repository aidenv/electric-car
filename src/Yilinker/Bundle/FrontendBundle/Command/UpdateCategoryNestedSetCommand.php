<?php

namespace Yilinker\Bundle\FrontendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\CategoryNestedSet;

/**
 * Populate the CategoryNestedSet table based on the ProductCategory Table
 *
 * Class UpdateCategoryNestedSetCommand
 * @package Yilinker\Bundle\FrontendBundle\Command
 */
class UpdateCategoryNestedSetCommand extends ContainerAwareCommand
{

    /**
     * Configure the command     
     *
     */
    protected function configure()
    {        
        $this->setName('category:create_nested_set')
             ->setDescription('Generate populate the CategoryNestedSet table');
    }
    
    /**
     * Excecute the command
     *
     * @param Symfony\Component\Console\Input\InputInterface $input
     * @param Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $allCategories = $this->getAllCategory();
        /**
         * Translate the categories to address the problem with spaces in between the category ids
         * For the nested set to work, the ids must appear sequentially without breaks.
         */
        $translatedCategories = [];
        $categoryOneToOneMap = [];
        $count = 1;
        foreach ($allCategories as $category) {
            $categoryId = $category->getProductCategoryId();
            $parentId = $category->getParent()->getProductCategoryId();
            $translatedId = $count;
            $translatedCategories[$categoryId] = array(
                'translated_id' => $translatedId,
                'original_id' => $categoryId,
                'original_parent_id' => $parentId,
            );
            $categoryOneToOneMap[$translatedId] =  $category;
            $count++;
        }

        /**
         * Insert translated parent Id into $translatedCategories array.
         * This done only after generating the translated table
         * in order to take into account cases where a category appears in the
         * result before it's parent
         */
        foreach ($translatedCategories as $index => $translatedCategory) {
            $translatedParentId = 0;
            if (isset($translatedCategories[$translatedCategory['original_parent_id']])) {
                $translatedParentId = $translatedCategories[$translatedCategory['original_parent_id']]['translated_id'];
            }
            $translatedCategories[$index]['translated_parent_id'] = $translatedParentId;
        }

        /**
         * Group categories by translated parent id
         */
        $categoriesGroupedByParent = [];
        $count = 0;
        foreach ($translatedCategories as $category) {
            $parentId = $category['translated_parent_id'];
            $categoryId = $category['translated_id'];
            if (!array_key_exists($parentId, $categoriesGroupedByParent)) {
                $categoriesGroupedByParent[$parentId] = array();
            }
            $categoriesGroupedByParent[$parentId][] = $categoryId;
        }

        /**
         * Unset the root category from the children of root
         */
        $position = array_search(ProductCategory::ROOT_CATEGORY_ID, $categoriesGroupedByParent[ProductCategory::ROOT_CATEGORY_ID]);
        unset($categoriesGroupedByParent[ProductCategory::ROOT_CATEGORY_ID][$position]);

        /**
         * Generate the Nested Set insert statements
         */
        $nestedSetTranformer = new TreeTransformer($categoriesGroupedByParent);
        $nestedSetTranformer->traverse(ProductCategory::ROOT_CATEGORY_ID);
        $nestedSetData = $nestedSetTranformer->getNestedSetData();

        $this->emptyCategoryNestedSet();

        /**
         * Persist data into the CategoryNestedSet table
         * Execute raw PDO query so that the primary key can be set
         */
        $insertToNestedTableQuery = "
            INSERT INTO CategoryNestedSet (`category_nested_set_id`, `left`, `right`, `product_category_id`) VALUES 
        ";
        $bindParamaters = array();
        foreach($nestedSetData as $nestedSetRow){
            $insertToNestedTableQuery .= '(?,?,?,?),';
            $bindParamaters[] = $nestedSetRow['categoryNestedSetId'];
            $bindParamaters[] = $nestedSetRow['left'];
            $bindParamaters[] = $nestedSetRow['right'];
            $bindParamaters[] = $categoryOneToOneMap[$nestedSetRow['categoryNestedSetId']]->getProductCategoryId();
        }
        $insertToNestedTableQuery = rtrim($insertToNestedTableQuery, ',');
        $finalInsertStatement = $doctrine->getEntityManager()->getConnection()->prepare($insertToNestedTableQuery);

        for ($count = 0; $count < count($bindParamaters); $count++) {
            $parameter = $bindParamaters[$count];
            $index = $count + 1;
            $finalInsertStatement->bindValue($index, $parameter, \PDO::PARAM_INT);
        }

        $finalInsertStatement->execute();
        echo "\nNested set table successfully generated.\n\n";
    }

     /**
      * Get all category in ProductCategory table
      * @return array
      */
     private function getAllCategory()
     {
         $entityManager = $this->getContainer()->get('doctrine')
                               ->getEntityManager();
         $productCategoryRepository = $entityManager->getRepository('YilinkerCoreBundle:ProductCategory');

         return $productCategoryRepository->findAll();
    }


    /**
     * Empty CategoryNestedSet table
     */
    private function emptyCategoryNestedSet()
    {
        $sql = "DELETE FROM CategoryNestedSet WHERE 1;";
        $this->getContainer()->get('doctrine')
             ->getEntityManager()
             ->getConnection()->query( $sql );
    }

}

/**
 * @class   TreeTransformer
 * @author  Original Paul Houle
 *          Matthew Toledo
 * @created 2008-11-04
 * @url     http://gen5.info/q/2008/11/04/nested-sets-php-verb-objects-and-noun-objects/
 *
 * Refactored to use PDO and adhere to coding standards
 *
 */
class TreeTransformer
{
    /**
     * Index counter
     *
     * @var integer
     */
    private $count;
        
    /**
     * The nested set list array
     *
     * @var integer[]
     */
    private $list;

    /**
     * Array of binded parameters
     *
     * @var mixed
     */
    private $nestedSetData;

    /**
     * Initialize the class
     *
     * @param array $list
     */
    public function __construct($list)
    {
        if (!is_array($list)) {
            throw new Exception("First parameter should be an array. Instead, it was of type '".gettype($list)."'");
        }
        $this->count = 1;
        $this->list = $list;
        $this->nestedSetData = array();
    }
    /**
     * Traverses the list begining with $startId and
     * stores it into the nested set table
     *
     * @param integer $startingId
     */
    public function traverse($startingId)
    {
        $left = $this->count;
        $this->count++;
        $children = $this->getChildren($startingId);
        if ($children) {
            foreach ($children as $child) {
                $this->traverse($child);
            }
        }
        $right = $this->count;
        $this->count++;
        $this->write($left, $right, $startingId);
    }
    
    /**
     * Returns children of a certain category
     *
     * @param integer[]
     */
    private function getChildren($categoryId)
    {
        return isset($this->list[$categoryId]) ? $this->list[$categoryId] : false;
    }

    /**
     * Inserts a node into the nested set table
     *
     * @param integer $left
     * @param integer $right
     * @param integer $categoryId
     */
    private function write($left, $right, $categoryId)
    {
        $left = (int) $left;
        $right = (int) $right;
        
        $this->nestedSetData[] = array(
            'categoryNestedSetId' => $categoryId,
            'left' => $left,
            'right' => $right,
        );
    }

    public function getNestedSetData()
    {
        return $this->nestedSetData;
    }

}

