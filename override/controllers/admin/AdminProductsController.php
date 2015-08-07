<?php
class AdminProductsController extends AdminProductsControllerCore
{    
    public function __construct()
    {
        parent::__construct();
        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
        $module = new SupplierBackOffice();
        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);                      	
        if(!$row)
            return false;
        $idProfile = $row['id_profile'];
        if($this->context->employee->id_category)
            $this->_list = null;
        if($this->context->employee->id_profile == $idProfile ) {
            $this->_where = " AND a.`id_category_default` = ".$this->context->employee->id_category;
            unset($this->fields_list['name_category']);
        }
    }
        
    protected function processBulkDelete()
    {
        if ($this->tabAccess['delete'] === '1')
            {
                if (is_array($this->boxes) && !empty($this->boxes))
                    {
                        $object = new $this->className();
                        if (isset($object->noZeroObject) &&
                        (count(call_user_func(array($this->className, $object->noZeroObject))) <= 1 || count($_POST[$this->table.'Box']) == count(call_user_func(array($this->className, $object->noZeroObject)))))
                            $this->errors[] = Tools::displayError('You need at least one object.').' <b>'.$this->table.'</b><br />'.Tools::displayError('You cannot delete all of the items.');
                        else
                            {
                                $success = 1;
                                $products = Tools::getValue($this->table.'Box');
                                if (is_array($products) && ($count = count($products)))
                                    {
                                        if (intval(ini_get('max_execution_time')) < round($count * 1.5))
                                            ini_set('max_execution_time', round($count * 1.5));
                                        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
                                            $stock_manager = StockManagerFactory::getManager();
                                        foreach ($products as $id_product)
                                            {
                                                $product = new Product((int)$id_product);
                                                require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
                                                $module = new SupplierBackOffice();
                                                $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module->getSupplierTranslation()."'";
                                                $row = Db::getInstance()->getRow($sql);                      	
                                                if(!$row)
                                                    return false;
                                                $idProfile = $row['id_profile'];
                                                if($this->context->employee->id_profile == $idProfile && $product->id_category_default != $this->context->employee->id_category)
                                                    $this->errors[] = Tools::displayError("You cannot delete a product of a category that is not yours.");               
                                                /*
                                                 * @since 1.5.0
                                                 * It is NOT possible to delete a product if there are currently:
                                                 * - physical stock for this product
                                                 * - supply order(s) for this product
                                                 */
                                                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management)
                                                    {
                                                        $physical_quantity = $stock_manager->getProductPhysicalQuantities($product->id, 0);
                                                        $real_quantity = $stock_manager->getProductRealQuantities($product->id, 0);
                                                        if ($physical_quantity > 0 || $real_quantity > $physical_quantity)
                                                            $this->errors[] = sprintf(Tools::displayError('You cannot delete the product #%d because there is physical stock left.'), $product->id);
                                                    }
                                                if (!count($this->errors))
                                                    {
                                                        if ($product->delete())
                                                            PrestaShopLogger::addLog(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int)$product->id, true, (int)$this->context->employee->id);
                                                        else
                                                            $success = false;
                                                    }
                                                else
                                                    $success = 0;
                                            }
                                    }
                                if ($success)
                                    {
                                        $id_category = (int)Tools::getValue('id_category');
                                        $category_url = empty($id_category) ? '' : '&id_category='.(int)$id_category;
                                        $this->redirect_after = self::$currentIndex.'&conf=2&token='.$this->token.$category_url;
                                    }
                                else
                                    $this->errors[] = Tools::displayError('An error occurred while deleting this selection.');
                            }
                    }
                else
                    $this->errors[] = Tools::displayError('You must select at least one element to delete.');
            }
        else
            $this->errors[] = Tools::displayError('You do not have permission to delete this.');
    }

    public function renderForm()
    {           
            
        // This nice code (irony) is here to store the product name, because the row after will erase product name in multishop context
        $this->product_name = $this->object->name[$this->context->language->id];

        if (!method_exists($this, 'initForm'.$this->tab_display))
            return;

        $product = $this->object;
                
        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
        $module_supplier = new SupplierBackOffice();
        $sql_supplier = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module_supplier->getSupplierTranslation()."'";
        $row_supplier = Db::getInstance()->getRow($sql_supplier);
        if(!$row_supplier)
            return false;
        $idProfile = $row_supplier['id_profile'];

        if($this->context->employee->id_profile == $idProfile)
            $this->context->smarty->assign('supplier_profile', true);
        else
            $this->context->smarty->assign('supplier_profile', false);

        // Product for multishop
        $this->context->smarty->assign('bullet_common_field', '');
        if (Shop::isFeatureActive() && $this->display == 'edit')
            {
                if (Shop::getContext() != Shop::CONTEXT_SHOP)
                    {
                        $this->context->smarty->assign(array(
                            'display_multishop_checkboxes' => true,
                            'multishop_check' => Tools::getValue('multishop_check'),
                        ));
                    }

                if (Shop::getContext() != Shop::CONTEXT_ALL)
                    {
                        $this->context->smarty->assign('bullet_common_field', '<i class="icon-circle text-orange"></i>');
                        $this->context->smarty->assign('display_common_field', true);
                    }
            }

        $this->tpl_form_vars['tabs_preloaded'] = $this->available_tabs;
                
        $this->tpl_form_vars['product_type'] = (int)Tools::getValue('type_product', $product->getType());

        $this->getLanguages();

        $this->tpl_form_vars['id_lang_default'] = Configuration::get('PS_LANG_DEFAULT');

        $this->tpl_form_vars['currentIndex'] = self::$currentIndex;
        $this->tpl_form_vars['display_multishop_checkboxes'] = (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP && $this->display == 'edit');
        $this->fields_form = array('');

        $this->tpl_form_vars['token'] = $this->token;
        $this->tpl_form_vars['combinationImagesJs'] = $this->getCombinationImagesJs();
        $this->tpl_form_vars['PS_ALLOW_ACCENTED_CHARS_URL'] = (int)Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        $this->tpl_form_vars['post_data'] = Tools::jsonEncode($_POST);
        $this->tpl_form_vars['save_error'] = !empty($this->errors);
        $this->tpl_form_vars['mod_evasive'] = Tools::apacheModExists('evasive');
        $this->tpl_form_vars['mod_security'] = Tools::apacheModExists('security');
        $this->tpl_form_vars['ps_force_friendly_product'] = Configuration::get('PS_FORCE_FRIENDLY_PRODUCT');

        // autoload rich text editor (tiny mce)
        $this->tpl_form_vars['tinymce'] = true;
        $iso = $this->context->language->iso_code;
        $this->tpl_form_vars['iso'] = file_exists(_PS_CORE_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en';
        $this->tpl_form_vars['path_css'] = _THEME_CSS_DIR_;
        $this->tpl_form_vars['ad'] = __PS_BASE_URI__.basename(_PS_ADMIN_DIR_);

        if (Validate::isLoadedObject(($this->object)))
            $id_product = (int)$this->object->id;
        else
            $id_product = (int)Tools::getvalue('id_product');

        $page = (int)Tools::getValue('page');

        $this->tpl_form_vars['form_action'] = $this->context->link->getAdminLink('AdminProducts').'&'.($id_product ? 'id_product='.(int)$id_product : 'addproduct').($page > 1 ? '&page='.(int)$page : '');
        $this->tpl_form_vars['id_product'] = $id_product;

        // Transform configuration option 'upload_max_filesize' in octets
        $upload_max_filesize = Tools::getOctets(ini_get('upload_max_filesize'));

        // Transform configuration option 'upload_max_filesize' in MegaOctets
        $upload_max_filesize = ($upload_max_filesize / 1024) / 1024;

        $this->tpl_form_vars['upload_max_filesize'] = $upload_max_filesize;
        $this->tpl_form_vars['country_display_tax_label'] = $this->context->country->display_tax_label;
        $this->tpl_form_vars['has_combinations'] = $this->object->hasAttributes();
        $this->product_exists_in_shop = true;

        if ($this->display == 'edit' && Validate::isLoadedObject($product) && Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP && !$product->isAssociatedToShop($this->context->shop->id))
            {
                $this->product_exists_in_shop = false;
                if ($this->tab_display == 'Informations')
                    $this->displayWarning($this->l('Warning: The product does not exist in this shop'));

                $default_product = new Product();
                $definition = ObjectModel::getDefinition($product);
                foreach ($definition['fields'] as $field_name => $field)
                    if (isset($field['shop']) && $field['shop'])
                        $product->$field_name = ObjectModel::formatValue($default_product->$field_name, $field['type']);
            }

        // let's calculate this once for all
        if (!Validate::isLoadedObject($this->object) && Tools::getValue('id_product'))
            $this->errors[] = 'Unable to load object';
        else
            {
                $this->_displayDraftWarning($this->object->active);

                // if there was an error while saving, we don't want to lose posted data
                if (!empty($this->errors))
                    $this->copyFromPost($this->object, $this->table);

                $this->initPack($this->object);
                $this->{'initForm'.$this->tab_display}($this->object);
                $this->tpl_form_vars['product'] = $this->object;

                if ($this->ajax)
                    if (!isset($this->tpl_form_vars['custom_form']))
                        throw new PrestaShopException('custom_form empty for action '.$this->tab_display);
                    else
                        return $this->tpl_form_vars['custom_form'];
            }
                
        $parent = AdminController::renderForm();
                
        $this->addJqueryPlugin(array('autocomplete', 'fancybox', 'typewatch'));
        return $parent;
    }
        
    public function renderKpis()
    {
        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
        $module = new SupplierBackOffice();
        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);                      	
        if(!$row)
            return false;
        $idProfile = $row['id_profile'];        
        if($this->context->employee->id_profile == $idProfile )
            return;
                
        return parent::renderKpis();
    }

    public function initFormAssociations($obj)
    {
        $product = $obj;
        $data = $this->createTemplate($this->tpl_form);
        // Prepare Categories tree for display in Associations tab
        $root = Category::getRootCategory();

        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
        $module_supplier = new SupplierBackOffice();
        $sql_supplier = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module_supplier->getSupplierTranslation()."'";
        $row_supplier = Db::getInstance()->getRow($sql_supplier);
        if(!$row_supplier)
            return false;
        $idProfile = $row_supplier['id_profile'];
        if ($this->context->employee->id_profile == $idProfile)
            $default_category = $this->context->cookie->id_category_products_filter ? $this->context->cookie->id_category_products_filter : $this->context->employee->id_category;
        else
            $default_category = $this->context->cookie->id_category_products_filter ? $this->context->cookie->id_category_products_filter : Context::getContext()->shop->id_category;
        if (!$product->id || !$product->isAssociatedToShop())
            $selected_cat = Category::getCategoryInformations(Tools::getValue('categoryBox', array($default_category)), $this->default_form_language);
        else
            {
                if (Tools::isSubmit('categoryBox'))
                    $selected_cat = Category::getCategoryInformations(Tools::getValue('categoryBox', array($default_category)), $this->default_form_language);
                else
                    $selected_cat = Product::getProductCategoriesFull($product->id, $this->default_form_language);
            }

        // Multishop block
        $data->assign('feature_shop_active', Shop::isFeatureActive());
        $helper = new HelperForm();
        if ($this->object && $this->object->id)
            $helper->id = $this->object->id;
        else
            $helper->id = null;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;

        // Accessories block
        $accessories = Product::getAccessoriesLight($this->context->language->id, $product->id);

        if ($post_accessories = Tools::getValue('inputAccessories'))
            {
                $post_accessories_tab = explode('-', $post_accessories);
                foreach ($post_accessories_tab as $accessory_id)
                    if (!$this->haveThisAccessory($accessory_id, $accessories) && $accessory = Product::getAccessoryById($accessory_id))
                        $accessories[] = $accessory;
            }
        $data->assign('accessories', $accessories);

        $product->manufacturer_name = Manufacturer::getNameById($product->id_manufacturer);

        $categories = array();
        foreach ($selected_cat as $key => $category)
            $categories[] = $key;

        $tree = new HelperTreeCategories('associated-categories-tree', 'Associated categories');
        $tree->setTemplate('tree_associated_categories.tpl')
             ->setHeaderTemplate('tree_associated_header.tpl')
             ->setRootCategory($root->id)
             ->setUseCheckBox(true)
             ->setUseSearch(true)
             ->setSelectedCategories($categories);

        $data->assign(array('default_category' => $default_category,
        'selected_cat_ids' => implode(',', array_keys($selected_cat)),
        'selected_cat' => $selected_cat,
        'id_category_default' => $product->getDefaultCategory(),
        'category_tree' => $tree->render(),
        'product' => $product,
        'link' => $this->context->link,
        'is_shop_context' => Shop::getContext() == Shop::CONTEXT_SHOP
        ));

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }

    public function processUpdate()
    {
        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
        $module = new SupplierBackOffice();
        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);                      	
        if(!$row)
            return false;
        $idProfile = $row['id_profile'];
        if($this->context->employee->id_profile == $idProfile && ((int)Tools::getValue('active') == 1 || !Tools::isSubmit('active'))) 
            $this->errors[] = Tools::displayError('You can\'t create a product directly with an active status.');

        $existing_product = $this->object;
                
        $this->checkProduct();

        if (!empty($this->errors))
            {
                $this->display = 'edit';
                return false;
            }

        $id = (int)Tools::getValue('id_'.$this->table);
        /* Update an existing product */
        if (isset($id) && !empty($id))
            {
                /** @var Product $object */
                $object = new $this->className((int)$id);
                $this->object = $object;

                if (Validate::isLoadedObject($object))
                    {
                        $this->_removeTaxFromEcotax();
                        $product_type_before = $object->getType();
                        $this->copyFromPost($object, $this->table);
                        $object->indexed = 0;

                        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP)
                            $object->setFieldsToUpdate((array)Tools::getValue('multishop_check', array()));

                        // Duplicate combinations if not associated to shop
                        if ($this->context->shop->getContext() == Shop::CONTEXT_SHOP && !$object->isAssociatedToShop())
                            {
                                $is_associated_to_shop = false;
                                $combinations = Product::getProductAttributesIds($object->id);
                                if ($combinations)
                                    {
                                        foreach ($combinations as $id_combination)
                                            {
                                                $combination = new Combination((int)$id_combination['id_product_attribute']);
                                                $default_combination = new Combination((int)$id_combination['id_product_attribute'], null, (int)$this->object->id_shop_default);

                                                $def = ObjectModel::getDefinition($default_combination);
                                                foreach ($def['fields'] as $field_name => $row)
                                                    $combination->$field_name = ObjectModel::formatValue($default_combination->$field_name, $def['fields'][$field_name]['type']);

                                                $combination->save();
                                            }
                                    }
                            }
                        else
                            $is_associated_to_shop = true;

                        if ($object->update())
                            {
 
                                $module->sendEmailNotification($existing_product);
                                // If the product doesn't exist in the current shop but exists in another shop
                                if (Shop::getContext() == Shop::CONTEXT_SHOP && !$existing_product->isAssociatedToShop($this->context->shop->id))
                                    {
                                        $out_of_stock = StockAvailable::outOfStock($existing_product->id, $existing_product->id_shop_default);
                                        $depends_on_stock = StockAvailable::dependsOnStock($existing_product->id, $existing_product->id_shop_default);
                                        StockAvailable::setProductOutOfStock((int)$this->object->id, $out_of_stock, $this->context->shop->id);
                                        StockAvailable::setProductDependsOnStock((int)$this->object->id, $depends_on_stock, $this->context->shop->id);
                                    }

                                PrestaShopLogger::addLog(sprintf($this->l('%s modification', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int)$this->object->id, true, (int)$this->context->employee->id);
                                if (in_array($this->context->shop->getContext(), array(Shop::CONTEXT_SHOP, Shop::CONTEXT_ALL)))
                                    {
                                        if ($this->isTabSubmitted('Shipping'))
                                            $this->addCarriers();
                                        if ($this->isTabSubmitted('Associations'))
                                            $this->updateAccessories($object);
                                        if ($this->isTabSubmitted('Suppliers'))
                                            $this->processSuppliers();
                                        if ($this->isTabSubmitted('Features'))
                                            $this->processFeatures();
                                        if ($this->isTabSubmitted('Combinations'))
                                            $this->processProductAttribute();
                                        if ($this->isTabSubmitted('Prices'))
                                            {
                                                $this->processPriceAddition();
                                                $this->processSpecificPricePriorities();
                                            }
                                        if ($this->isTabSubmitted('Customization'))
                                            $this->processCustomizationConfiguration();
                                        if ($this->isTabSubmitted('Attachments'))
                                            $this->processAttachments();
                                        if ($this->isTabSubmitted('Images'))
                                            $this->processImageLegends();

                                        $this->updatePackItems($object);
                                        // Disallow avanced stock management if the product become a pack
                                        if ($product_type_before == Product::PTYPE_SIMPLE && $object->getType() == Product::PTYPE_PACK)
                                            StockAvailable::setProductDependsOnStock((int)$object->id, false);
                                        $this->updateDownloadProduct($object, 1);
                                        $this->updateTags(Language::getLanguages(false), $object);

                                        if ($this->isProductFieldUpdated('category_box') && !$object->updateCategories(Tools::getValue('categoryBox')))
                                            $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
                                    }

                                if ($this->isTabSubmitted('Warehouses'))
                                    $this->processWarehouses();
                                if (empty($this->errors))
                                    {
                                        if (in_array($object->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION'))
                                            Search::indexation(false, $object->id);

                                        // Save and preview
                                        if (Tools::isSubmit('submitAddProductAndPreview'))
                                            $this->redirect_after = $this->getPreviewUrl($object);
                                        else
                                            {
                                                $page = (int)Tools::getValue('page');
                                                // Save and stay on same form
                                                if ($this->display == 'edit')
                                                    {
                                                        $this->confirmations[] = $this->l('Update successful');
                                                        $this->redirect_after = self::$currentIndex.'&id_product='.(int)$this->object->id
                                                            .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                                                            .'&updateproduct&conf=4&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).($page > 1 ? '&page='.(int)$page : '').'&token='.$this->token;
                                                    }
                                                else
                                                    // Default behavior (save and back)
                                                    $this->redirect_after = self::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '').'&conf=4'.($page > 1 ? '&submitFilterproduct='.(int)$page : '').'&token='.$this->token;
                                            }
                                    }
                                // if errors : stay on edit page
                                else
                                    $this->display = 'edit';
                            }
                        else
                            {
                                if (!$is_associated_to_shop && $combinations)
                                    foreach ($combinations as $id_combination)
                                        {
                                            $combination = new Combination((int)$id_combination['id_product_attribute']);
                                            $combination->delete();
                                        }
                                $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Db::getInstance()->getMsgError().')';
                            }
                    }
                else
                    $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Tools::displayError('The object cannot be loaded. ').')';
                return $object;
            }
    }

    public function processAdd()
    {
        $this->checkProduct();

        if (!empty($this->errors))
            {
                $this->display = 'add';
                return false;
            }

        $this->object = new $this->className();
        $this->_removeTaxFromEcotax();
        $this->copyFromPost($this->object, $this->table);
        if ($this->object->add())
            {
                PrestaShopLogger::addLog(sprintf($this->l('%s addition', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int)$this->object->id, true, (int)$this->context->employee->id);
                $this->addCarriers($this->object);
                $this->updateAccessories($this->object);
                $this->updatePackItems($this->object);
                $this->updateDownloadProduct($this->object);

                if (Configuration::get('PS_FORCE_ASM_NEW_PRODUCT') && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $this->object->getType() != Product::PTYPE_VIRTUAL)
                    {
                        $this->object->advanced_stock_management = 1;
                        StockAvailable::setProductDependsOnStock($this->object->id, true, (int)$this->context->shop->id, 0);
                        $this->object->save();
                    }

                if (empty($this->errors))
                    {

                        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
                        $module_supplier = new SupplierBackOffice();
                        $sql_supplier = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module_supplier->getSupplierTranslation()."'";
                        $row_supplier = Db::getInstance()->getRow($sql_supplier);
                        if(!$row_supplier)
                            return false;
                        $idProfile = $row_supplier['id_profile'];
                        if ($this->context->employee->id_profile == $idProfile)
                            $module_supplier->sendEmailNotification($this->object);
                                    
                        $languages = Language::getLanguages(false);
                        if ($this->isProductFieldUpdated('category_box') && !$this->object->updateCategories(Tools::getValue('categoryBox')))
                            $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
                        elseif (!$this->updateTags($languages, $this->object))
                            $this->errors[] = Tools::displayError('An error occurred while adding tags.');
                        else
                            {
                                Hook::exec('actionProductAdd', array('id_product' => (int)$this->object->id, 'product' => $this->object));
                                if (in_array($this->object->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION'))
                                    Search::indexation(false, $this->object->id);
                            }

                        if (Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT') != 0 && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
                            {
                                $warehouse_location_entity = new WarehouseProductLocation();
                                $warehouse_location_entity->id_product = $this->object->id;
                                $warehouse_location_entity->id_product_attribute = 0;
                                $warehouse_location_entity->id_warehouse = Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT');
                                $warehouse_location_entity->location = pSQL('');
                                $warehouse_location_entity->save();
                            }

                        // Apply groups reductions
                        $this->object->setGroupReduction();

                        // Save and preview
                        if (Tools::isSubmit('submitAddProductAndPreview'))
                            $this->redirect_after = $this->getPreviewUrl($this->object);

                        // Save and stay on same form
                        if ($this->display == 'edit')
                            $this->redirect_after = self::$currentIndex.'&id_product='.(int)$this->object->id
                                .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                                .'&updateproduct&conf=3&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).'&token='.$this->token;
                        else
                            // Default behavior (save and back)
                            $this->redirect_after = self::$currentIndex
                                .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                                .'&conf=3&token='.$this->token;
                    }
                else
                    {
                        $this->object->delete();
                        // if errors : stay on edit page
                        $this->display = 'edit';
                    }
            }
        else
            $this->errors[] = Tools::displayError('An error occurred while creating an object.').' <b>'.$this->table.'</b>';

        return $this->object;
    }
    
    public function processStatus()
    {
        $this->loadObject(true);
        if (!Validate::isLoadedObject($this->object))
            return false;
        if (($error = $this->object->validateFields(false, true)) !== true)
            $this->errors[] = $error;
        if (($error = $this->object->validateFieldsLang(false, true)) !== true)
            $this->errors[] = $error;
        require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
        $module = new SupplierBackOffice();
        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);                      	
        if(!$row)
            return false;
        $idProfile = $row['id_profile'];
        if($this->context->employee->id_profile == $idProfile) 
            $this->errors[] = Tools::displayError('You can\'t change the status of the product.');
        
                
        if (count($this->errors))
            return false;

        $res = AdminController::processStatus();

        return $res;
    }
}
