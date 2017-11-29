<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Yilinker\Bundle\CoreBundle\Entity\Voucher;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Yilinker\Bundle\CoreBundle\Entity\VoucherCode;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThan;

use Yilinker\Bundle\CoreBundle\Entity\VoucherStore;
use Yilinker\Bundle\CoreBundle\Entity\VoucherProduct;
use Yilinker\Bundle\CoreBundle\Entity\VoucherProductCategory;

use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Traits\SlugHandler;

class VoucherFormType extends AbstractType
{
    use SlugHandler;

    private $em;
    private $frontendHostname;
    private $router;

    public function init($container)
    {
        $this->em = $container->get("doctrine.orm.entity_manager");
        $this->frontendHostname = $container->getParameter("frontend_hostname");
        $this->router = $container->get("router");
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'attr' => array(
                    'placeholder' => 'Voucher Code Name',
                    'maxlength' => '255',
                    'class' => 'ui fluid input field'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'Voucher code name is required'
                        )
                    )
                )
            ))
            ->add('usageType', 'choice', array(
                'choices' => Voucher::usageTypes(),
                'attr' => array(
                    'class' => 'form-ui ui search single selection dropdown'
                )
            ))
            ->add('quantity', 'number', array(
                'attr' => array(
                    'class' => 'ui fluid input field',
                    'placeholder' => '100'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'Quantity is required'
                        )
                    )
                )
            ))
            ->add('code', 'text', array(
                'mapped' => false,
                'attr' => array(
                    'maxlength' => '30',
                    'class' => 'ui fluid input field',
                    'readonly' => 'readonly'
                )
            ))
            ->add('batchUpload', 'checkbox', array(
                'mapped' => false
            ))
            ->add('discountType', 'choice', array(
                'choices' => Voucher::discountTypes(),
                'attr' => array(
                    'class' => 'form-ui ui search single selection dropdown'
                )
            ))
            ->add('value', 'number', array(
                'attr' => array(
                    'class' => 'ui fluid input field',
                    'placeholder' => '100%'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'Voucher\'s value is required'
                        )
                    )
                )
            ))
            ->add('minimumPurchase', 'number', array(
                'required' => false,
                'attr' => array(
                    'class' => 'ui fluid input field'
                )
            ))
            ->add('productVouchers', 'textarea', array(
                'mapped' => false,
            ))
            ->add('productCategories', 'entity', array(
                'mapped' => false,
                'class' => 'YilinkerCoreBundle:ProductCategory',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pc')
                              ->where('pc.parent = :rootCategory')
                              ->andWhere('pc.productCategoryId <> :rootCategory')
                              ->andWhere('pc.isDelete = false')
                              ->setParameter(':rootCategory', ProductCategory::ROOT_CATEGORY_ID);
                },
                'multiple' => true
            ))
            ->add('stores', 'textarea', array(
                'mapped' => false,
            ))
            ->add('isActive', 'checkbox')
            ->add('includeAffiliates', 'checkbox')
            ->add('startDate', 'datetime', array(
                'widget' => 'single_text',
                'attr' => array(
                    'data-voucher-start-date' => '',
                    'class' => 'ui fluid input field start-date',
                    'placeholder' => 'Start Date'
                )
            ))
            ->add('endDate', 'datetime', array(
                'widget' => 'single_text',
                'attr' => array(
                    'data-voucher-end-date' => '',
                    'class' => 'ui input field end-date',
                    'placeholder' => 'End Date'
                ),
                'constraints' => array(
                    new GreaterThan(array(
                        'value'     => 'today',
                        'message'   => 'End Date must be greater than or equal today'
                    ))
                )
            ))
        ;

        $this->addEventListeners($builder);
    }

    public function addEventListeners($builder)
    {
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function(FormEvent $event) {
                $form = $event->getForm();
                $voucher = $event->getData();

                $code = $form->get('code')->getData();
                $batchUpload = $form->get('batchUpload')->getData();

                if ($code) {
                    $codes = $batchUpload ? explode(',', $code): array($code);

                    foreach ($codes as $code) {
                        $voucherCode = new VoucherCode;
                        $voucherCode->setCode($code);
                        $voucherCode->setVoucher($voucher);

                        $voucher->addVoucherCode($voucherCode);
                    }
                }

                $productVouchers = $form->get('productVouchers')->getData();
                $productVouchers = trim($productVouchers);
                $productVouchers = explode("\n", $productVouchers);

                if($productVouchers){
                    $productSlugs = array();
                    foreach ($productVouchers as $productVoucher) {
                        $slug = trim($this->getLastSegment($productVoucher), "\r");
                        array_push($productSlugs, $slug);
                    }

                    $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
                    $products = $productRepository->getProductsBySlug($productSlugs);

                    foreach ($products as $product) {
                        $voucherProduct = new VoucherProduct();
                        $voucherProduct->setProduct($product);
                        $voucherProduct->setVoucher($voucher);

                        $voucher->addVoucherProduct($voucherProduct);
                    }
                }

                $storeVouchers = $form->get('stores')->getData();
                $storeVouchers = trim($storeVouchers);
                $storeVouchers = explode("\n", $storeVouchers);

                if($storeVouchers){
                    $storeSlugs = array();
                    foreach ($storeVouchers as $storeVoucher) {
                        $slug = trim($this->getLastSegment($storeVoucher), "\r");
                        array_push($storeSlugs, $slug);
                    }

                    $storeRepository = $this->em->getRepository("YilinkerCoreBundle:Store");
                    $stores = $storeRepository->getStoreByStoreSlugIn($storeSlugs);

                    foreach ($stores as $store) {
                        $voucherStore = new VoucherStore();
                        $voucherStore->setStore($store);
                        $voucherStore->setVoucher($voucher);

                        $voucher->addVoucherStore($voucherStore);
                    }
                }

                $productCategories = $form->get("productCategories")->getData();

                foreach($productCategories as $productCategory){
                    $voucherProductCategory = new VoucherProductCategory();
                    $voucherProductCategory->setProductCategory($productCategory);
                    $voucherProductCategory->setVoucher($voucher);

                    $voucher->addVoucherProductCategory($voucherProductCategory);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function(FormEvent $event){
                $form = $event->getForm();
                $voucher = $event->getData();

                if(!is_null($voucher)){

                    $products = array();
                    foreach($voucher->getVoucherProducts() as $voucherProduct){
                        array_push(
                            $products, 
                            $this->frontendHostname.$this->router->generate(
                                "product_details", array(
                                    "slug" => $voucherProduct->getProduct()->getSlug()
                                )
                            )
                        );
                    }

                    $productVouchers = implode("\n", $products);

                    $productVouchersField = $form->get('productVouchers');
                    $productVouchersField->setData($productVouchers);

                    $stores = array();
                    foreach($voucher->getVoucherStores() as $voucherStore){
                        array_push(
                            $stores, 
                            $this->frontendHostname.$this->router->generate(
                                "store_page_products", array(
                                    "slug" => $voucherStore->getStore()->getStoreSlug()
                                )
                            )
                        );
                    }

                    $storeVouchers = implode("\n", $stores);

                    $storesField = $form->get('stores');
                    $storesField->setData($storeVouchers);

                    $productCategories = array();
                    foreach($voucher->getVoucherProductCategories() as $voucherProductCategories){
                        array_push(
                            $productCategories, 
                            $voucherProductCategories->getProductCategory()
                        );
                    }

                    $productCategoriesField = $form->get('productCategories');
                    $productCategoriesField->setData($productCategories);
                }
            }
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\Voucher'
        ));
    }

    public function getName()
    {
        return 'voucher';
    }
}