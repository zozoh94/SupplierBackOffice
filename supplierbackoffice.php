<?php
if (!defined('_PS_VERSION_'))
    exit;

class SupplierBackOffice extends Module
{
    public function __construct()
    {
        $this->name = 'supplierbackoffice';
        $this->tab = 'bakc_office_features';
        $this->version = '1.0';
        $this->author = 'Enzo Hamelin';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6'); 

        parent::__construct();

        $this->displayName = $this->l('Supplier Backoffice');
        $this->description = $this->l('Module to allow suppliers modify their products.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Supplier Backoffice ?');
    }

    public function install()
    {
        if(Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if(!parent::install())
            return false;
        
        $langId = (int) (Configuration::get('PS_LANG_DEFAULT'));
        $supplierProfile = new Profile();
        $supplierProfile->name = array($langId => $this->l('Supplier'));
        if(!$supplierProfile->save())
            return false;

        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$this->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);
        if(!$row)
            return false;
        $idProfile = $row['id_profile'];

        $sql = "SELECT id_tab FROM "._DB_PREFIX_."tab WHERE class_name = 'AdminCatalog'";
        $row = Db::getInstance()->getRow($sql);
        if(!$row)
            return false;
        $idTabProduct = $row['id_tab'];
        
        Db::getInstance()->update('access', array('view' => true), 'id_tab = '.$idTabProduct.' AND id_profile = '.$idProfile);

        
        $sql = "SELECT id_tab FROM "._DB_PREFIX_."tab WHERE class_name = 'AdminProducts'";
        $row = Db::getInstance()->getRow($sql);
        if(!$row)
            return false;
        $idTabProduct = $row['id_tab'];
        
        Db::getInstance()->update('access', array('view' => true, 'add' => true, 'edit' => true), 'id_tab = '.$idTabProduct.' AND id_profile = '.$idProfile);

        $sql = 'ALTER TABLE '._DB_PREFIX_.'employee ADD id_category INT(10) NULL';
        if(!Db::getInstance()->Execute($sql))
            return false;
        
        return true;
    }

    public function uninstall()
    {
        if(!parent::uninstall())
            return false;
        
        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$this->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);
        if(!$row)
            return false;

        $supplierProfile = new Profile($row['id_profile']);
        if(!$supplierProfile)
            return false;

        if(!$supplierProfile->delete())
            return false;

        $sql = 'ALTER TABLE '._DB_PREFIX_.'employee DROP id_category';
        if(!Db::getInstance()->Execute($sql))
            return false;
 
        return true;
    }

    public function getSupplierTranslation()
    {
        return $this->l('Supplier');
    }

    public function getCategoryTranslation()
    {
        return $this->l('Category');
    }

    public function hookDisplayBackOfficeHeader($params){
        $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$this->getSupplierTranslation()."'";
        $row = Db::getInstance()->getRow($sql);                      	
        if(!$row)
            return false;
        $idProfile = $row['id_profile'];
        if($this->context->employee->id_profile == $idProfile) {
            $link = new LinkCore();
            $html = "<script type=\"text/javascript\">
                     $(document).ready(function(){
                         $('#header_foaccess .string-long').text('".$this->l('Shop')."');
                         $('#header_employee_box').prepend(\"<li><a href='".$link->getAdminLink('AdminProducts')."'>".$this->l('My products')."</a></li>\");
                     });
                     </script>";
            $this->context->controller->addCss(_PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.css");
            return $html;
        }
    }

    public function sendEmailNotification($product)
    {     
       	$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $subject = $this->l('Product created/edited');
        $data = array('{id}'  => $product->id );
                    
        Mail::Send($id_lang, 'product', $subject , $data, Configuration::get('PS_SHOP_EMAIL'), NULL, NULL, NULL, NULL, NULL, dirname(__FILE__).'/mails/');
    }
}
?>