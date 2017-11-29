<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Transformers;

use DateTime;
use Doctrine\ORM\EntityManager;
use FOS\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Elastica\Document;


class ProductToElasticaTransformer implements ModelToElasticaTransformerInterface
{
    /**
     * Symfony service container
     */
    private $serviceContainer;

    /**
     * Set the entityManager
     *
     */
    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * Transforms a product entity into an elastica object having the required keys
     *
     * @param Product $oject the object to convert
     * @param array $fields
     *
     * @return Document
     */
    public function transform($object, array $fields)
    {
        $originalLocale = $object->getLocale();
        $em = $this->serviceContainer->get('doctrine.orm.entity_manager');
        $translatableService = $this->serviceContainer->get('yilinker_core.service.search.translatable');

        $defaultLanguage = $translatableService->getDefaultLanguage();
        $languageCountryMaping = $translatableService->getLanguageCountryMapping(true);

        /**
         * Refresh Product locale
         */
        $object->setLocale($defaultLanguage);
        $em->refresh($object);

        $cartItemRepository = $em->getRepository("YilinkerCoreBundle:CartItem");
        $wishlistInstances = $cartItemRepository->getWishlistInstances($object);

        $promoInstanceRepository = $em->getRepository("YilinkerCoreBundle:PromoInstance");
        $promoInstances = $this->constructPromoInstances($promoInstanceRepository->getCurrentProductPromoInstances($object, true));
        $productGlobalName = $object->getName();
        $product = array(
            "productId"             => $object->getProductId(),
            "dateCreated"           => $object->getDateCreated()->format(DateTime::ISO8601),
            "dateLastModified"      => $object->getDateLastModified()->format(DateTime::ISO8601),
            "clickCount"            => $object->getClickCount(),
            "wishlistCount"         => count($wishlistInstances),
            "name"                  => $productGlobalName,
            "slug"                  => $object->getSlug(),
            "attributeValues"       => $object->getCapitalizedAttributeValues(),
//            "description"           => $object->getDescription(),
            "description"           => '',
            "keywords"              => $object->getKeywords(),
            "shortDescription"      => $object->getShortDescription(),
            "categoryKeyword"       => $object->getCategoryKeyword(),
            "customCategoryIds"     => $object->getCustomCategoryIds(),
            "customCategories"      => $object->getCustomCategories(),
            "promoInstances"        => $promoInstances,
            "flattenedCategory"     => $object->getFlattenedCategory(),
            "flattenedSeller"       => $object->getFlattenedSeller(),
            "flattenedBrand"        => $object->getFlattenedBrand(),
            "status"                => $object->getStatus(),
            "isManufacturerProduct" => (boolean) $object->getManufacturerProductMap(),
            "isInhouseProduct"      => $object->isInhouseProduct(),
            "countries"             => $object->getIsInhouse() ? $object->getProductCountryCodes() :$object->getAllCountryCodes(),
            "warehouses"            => $this->constructWarehouses($object),
        );

        /**
         * Add searchable translatable fields here
         */
        $translatablelanguages = $translatableService->getTranslatableLanguages();
        foreach($translatablelanguages as $language){
            $object->setLocale($language);
            $em->refresh($object);

            $defaultProductUnit = $object->getDefaultUnit();
            $country = $languageCountryMaping[$language];
            if($defaultProductUnit && $country){
                $defaultProductUnit->setLocale($country->getCode());

                $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
                $translations = $repository->findTranslations($defaultProductUnit);

                $loweredCountryCode = strtolower($country->getCode());
                if(
                    $translations &&
                    array_key_exists($loweredCountryCode, $translations)
                ){
                    $translationValues = $translations[$loweredCountryCode];

                    if(array_key_exists("name", $translationValues)){
                        $defaultProductUnit->setName($translationValues["name"]);
                    }

                    if(array_key_exists("discountedPrice", $translationValues)){
                        $defaultProductUnit->setDiscountedPrice($translationValues["discountedPrice"]);
                    }

                    if(array_key_exists("price", $translationValues)){
                        $defaultProductUnit->setPrice($translationValues["price"]);
                    }

                    if(array_key_exists("status", $translationValues)){
                        $defaultProductUnit->setPrice($translationValues["status"]);
                    }


                    $object->setDefaultUnit($defaultProductUnit);

                    $product[$language.'_name'] = strlen($object->getName()) > 0 ? $object->getName() : $productGlobalName;
                    $product[$language.'_defaultPrice'] = $object->getDefaultPrice();
                    $product[$language.'_originalPrice'] = $object->getOriginalPrice();
                    $product[$language.'_discount'] = $object->getDiscount();
                    $product[$language.'_status'] = $object->getProductCountryStatus($country);
                    $product[$language.'_status'] = 2;
                }
            }
        }

        $document = new Document($object->getProductId(), $product);

        $object->setLocale($originalLocale);
        $em->refresh($object);

        return $document;
    }

    private function constructWarehouses($product)
    {
        $em = $this->serviceContainer->get('doctrine.orm.entity_manager');

        $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');

        $productWarehouses = array();
        foreach ($product->getProductWarehouses(true) as $warehouse) {
            $countryCode = $warehouse->getCountryCode();
            $userWarehouse = $warehouse->getUserWarehouse();
            if (!$userWarehouse) {
                continue;
            }
            $warehouseCountry = $userWarehouse->getCountry();

            if ($country = $countryRepository->findOneByName((string) $warehouseCountry)) {
                $warehouseCountryCode = strtolower($country->getCode());

                if (!isset($productWarehouses[$countryCode."-".$warehouseCountryCode])) {
                    $productWarehouses[] = $countryCode."-".$warehouseCountryCode;
                }
            }
        }

        sort($productWarehouses);

        return $productWarehouses;
    }

    private function constructPromoInstances($intances)
    {
        $promoInstances = array();

        foreach($intances as $intance){
            array_push($promoInstances, array(
                "promoInstanceId" => $intance->getPromoInstanceId(),
                "promoTitle"      => $intance->getTitle(),
                "promoIsEnabled"  => $intance->getIsEnabled(),
                "promoDateStart"  => $intance->getDateStart()->format(DateTime::ISO8601),
                "promoDateEnd"    => $intance->getDateEnd()->format(DateTime::ISO8601)
            ));
        }

        return $promoInstances;
    }
}
