<?php class OrganicInternet_SimpleConfigurableProducts_Catalog_Model_Product_Type_Configurable
    extends Mage_Catalog_Model_Product_Type_Configurable
{
    #Copied from Magento v1.3.1 code.
    #Only need to comment out addFilterByRequiredOptions but there's no
    #nice way of doing that without cutting and pasting the method into my own
    #derived class. Boo.
    public function getUsedProducts($requiredAttributeIds = null, $product = null)
    {
        Varien_Profiler::start('CONFIGURABLE:'.__METHOD__);
        if (!$this->getProduct($product)->hasData($this->_usedProducts)) {
            if (is_null($requiredAttributeIds)
                and is_null($this->getProduct($product)->getData($this->_configurableAttributes))) {
                // If used products load before attributes, we will load attributes.
                $this->getConfigurableAttributes($product);
                // After attributes loading products loaded too.
                Varien_Profiler::stop('CONFIGURABLE:'.__METHOD__);
                return $this->getProduct($product)->getData($this->_usedProducts);
            }

            $usedProducts = array();
            $collection = $this->getUsedProductCollection($product)
                ->addAttributeToSelect('*');
            $x = $collection->getSize();
            // ->addFilterByRequiredOptions();

            if (is_array($requiredAttributeIds)) {
                foreach ($requiredAttributeIds as $attributeId) {
                    $attribute = $this->getAttributeById($attributeId, $product);
                    if (!is_null($attribute))
                        $collection->addAttributeToFilter($attribute->getAttributeCode(), array('notnull'=>1));
                }
            }

            foreach ($collection as $item) {
                $usedProducts[] = $item;
            }

            $this->getProduct($product)->setData($this->_usedProducts, $usedProducts);
        }
        Varien_Profiler::stop('CONFIGURABLE:'.__METHOD__);
        return $this->getProduct($product)->getData($this->_usedProducts);
    }

    #This is modified to completely ignore the isSalable status of the configurable product itself
    #It only uses the isSalable sattus of the children to determine the isSalable status of the configurable product

    #Not needed since I've competely rewritten the indexing for configurable products now
/*
    public function isSalable($product = null)
    {
        $salable = false;
        foreach ($this->getUsedProducts(null, $product) as $child) {
            $salable = $salable || $child->isSalable();
        }
        return $salable;
    }
*/

    /**
     * NOTE: Copied from Magento 1.9.2.0 and modified to include additional attributes to select
     *
     * Retrieve used product by attribute values
     *  $attrbutesInfo = array(
     *      $attributeId => $attributeValue
     *  )
     *
     * @param  array $attributesInfo
     * @param  Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Product|null
     */
    public function getProductByAttributes($attributesInfo, $product = null)
    {
        if (is_array($attributesInfo) && !empty($attributesInfo)) {
            $productCollection = $this->getUsedProductCollection($product)->addAttributeToSelect(array('name', 'price'));
            foreach ($attributesInfo as $attributeId => $attributeValue) {
                $productCollection->addAttributeToFilter($attributeId, $attributeValue);
            }
            $productObject = $productCollection->getFirstItem();
            if ($productObject->getId()) {
                return $productObject;
            }

            foreach ($this->getUsedProducts(null, $product) as $productObject) {
                $checkRes = true;
                foreach ($attributesInfo as $attributeId => $attributeValue) {
                    $code = $this->getAttributeById($attributeId, $product)->getAttributeCode();
                    if ($productObject->getData($code) != $attributeValue) {
                        $checkRes = false;
                    }
                }
                if ($checkRes) {
                    return $productObject;
                }
            }
        }
        return null;
    }
}
