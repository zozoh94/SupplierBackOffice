<?php
class AdminDashboardController extends AdminDashboardControllerCore
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
        $link = new LinkCore();
        if($this->context->employee->id_profile == $idProfile )
            Tools::redirectAdmin($link->getAdminLink('AdminProducts'));

    }
}
