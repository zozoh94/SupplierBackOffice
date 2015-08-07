<?php

class Employee extends EmployeeCore
{
    /** @var id of the category associated to the supplier */
    public $id_category;

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        self::$definition['fields']['id_category'] = array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false);
        parent::__construct($id, $id_lang, $id_shop);
    }
}