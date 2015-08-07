<?php
class AdminEmployeesController extends AdminEmployeesControllerCore
{
	public function renderForm()
	{
		/** @var Employee $obj */
		if (!($obj = $this->loadObject(true)))
			return;

		$available_profiles = Profile::getProfiles($this->context->language->id);

		if ($obj->id_profile == _PS_ADMIN_PROFILE_ && $this->context->employee->id_profile != _PS_ADMIN_PROFILE_)
		{
			$this->errors[] = Tools::displayError('You cannot edit the SuperAdmin profile.');
			return parent::renderForm();
		}

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->l('Employees'),
				'icon' => 'icon-user'
			),
			'input' => array(
				array(
					'type' => 'text',
					'class' => 'fixed-width-xl',
					'label' => $this->l('First Name'),
					'name' => 'firstname',
					'required' => true
				),
				array(
					'type' => 'text',
					'class' => 'fixed-width-xl',
					'label' => $this->l('Last Name'),
					'name' => 'lastname',
					'required' => true
				),
				array(
					'type' => 'html',
					'name' => 'employee_avatar',
					'html_content' => '<div id="employee-thumbnail"><a href="http://www.prestashop.com/forums/index.php?app=core&amp;module=usercp" target="_blank" style="background-image:url('.$obj->getImage().')"></a></div>
					<div class="alert alert-info">'.sprintf($this->l('Your avatar in PrestaShop 1.6.x is your profile picture on %1$s. To change your avatar, log in to PrestaShop.com with your email %2$s and follow the on-screen instructions.'), '<a href="http://www.prestashop.com/forums/index.php?app=core&amp;module=usercp" class="alert-link" target="_blank">PrestaShop.com</a>', $obj->email).'</div>',
				),
				array(
					'type' => 'text',
					'class'=> 'fixed-width-xxl',
					'prefix' => '<i class="icon-envelope-o"></i>',
					'label' => $this->l('Email address'),
					'name' => 'email',
					'required' => true,
					'autocomplete' => false
				),
			),
		);

		if ($this->restrict_edition)
		{
			$this->fields_form['input'][] = array(
				'type' => 'change-password',
				'label' => $this->l('Password'),
				'name' => 'passwd'
				);

			if (Tab::checkTabRights(Tab::getIdFromClassName('AdminModulesController')))
				$this->fields_form['input'][] = array(
					'type' => 'prestashop_addons',
					'label' => 'PrestaShop Addons',
					'name' => 'prestashop_addons',
				);
		}
		else
			$this->fields_form['input'][] = array(
				'type' => 'password',
				'label' => $this->l('Password'),
				'hint' => sprintf($this->l('Password should be at least %s characters long.'), Validate::ADMIN_PASSWORD_LENGTH),
				'name' => 'passwd'
				);

		$this->fields_form['input'] = array_merge($this->fields_form['input'], array(
			array(
				'type' => 'switch',
				'label' => $this->l('Connect to PrestaShop'),
				'name' => 'optin',
				'required' => false,
				'is_bool' => true,
				'values' => array(
					array(
						'id' => 'optin_on',
						'value' => 1,
						'label' => $this->l('Yes')
					),
					array(
						'id' => 'optin_off',
						'value' => 0,
						'label' => $this->l('No')
					)
				),
				'hint' => $this->l('PrestaShop can provide you with guidance on a regular basis by sending you tips on how to optimize the management of your store which will help you grow your business. If you do not wish to receive these tips, please uncheck this box.')
			),
			array(
				'type' => 'default_tab',
				'label' => $this->l('Default page'),
				'name' => 'default_tab',
				'hint' => $this->l('This page will be displayed just after login.'),
				'options' => $this->tabs_list
			),
			array(
				'type' => 'select',
				'label' => $this->l('Language'),
				'name' => 'id_lang',
				//'required' => true,
				'options' => array(
					'query' => Language::getLanguages(false),
					'id' => 'id_lang',
					'name' => 'name'
				)
			),
			array(
				'type' => 'select',
				'label' => $this->l('Theme'),
				'name' => 'bo_theme_css',
				'options' => array(
					'query' => $this->themes,
					'id' => 'id',
					'name' => 'name'
				),
				'onchange' => 'var value_array = $(this).val().split("|"); $("link").first().attr("href", "themes/" + value_array[0] + "/css/" + value_array[1]);',
				'hint' => $this->l('Back office theme.')
			),
			array(
				'type' => 'radio',
				'label' => $this->l('Admin menu orientation'),
				'name' => 'bo_menu',
				'required' => false,
				'is_bool' => true,
				'values' => array(
					array(
						'id' => 'bo_menu_on',
						'value' => 0,
						'label' => $this->l('Top')
					),
					array(
						'id' => 'bo_menu_off',
						'value' => 1,
						'label' => $this->l('Left')
					)
				)
			)
		));

                require_once _PS_MODULE_DIR_."supplierbackoffice/supplierbackoffice.php";
                $module = new SupplierBackOffice();
                $sql = "SELECT id_profile FROM "._DB_PREFIX_."profile_lang WHERE name = '".$module->getSupplierTranslation()."'";
                $row = Db::getInstance()->getRow($sql);                      	
                if(!$row)
                  return false;

                $idProfile = $row['id_profile'];

                $allCategories = Category::getCategories();
                foreach($allCategories as $childs)
                    foreach($childs as $category)
                        $categories[] = $category['infos'];
                $categories = array_merge(array(
                    array(
                        'id_category' => null,
                        'name' => ''
                    )
                ), $categories);
                if ($obj->id_profile == $idProfile && $this->context->employee->id_profile != $idProfile)
                {
                    $this->fields_form['input'] = array_merge($this->fields_form['input'], array(                       
                        array(
                            'type' => 'select',
                            'label' => $module->getCategoryTranslation(),
                            'name' => 'id_category',
                            'required' => false,
                            'options' => array(
                                'query' => $categories,
                                'id' => 'id_category',
                                'name' => 'name',
                                'lang' => true,
                            )
			)
                    ));
                }
             
		if ((int)$this->tabAccess['edit'] && !$this->restrict_edition)
		{
			$this->fields_form['input'][] = array(
				'type' => 'switch',
				'label' => $this->l('Active'),
				'name' => 'active',
				'required' => false,
				'is_bool' => true,
				'values' => array(
					array(
						'id' => 'active_on',
						'value' => 1,
						'label' => $this->l('Enabled')
					),
					array(
						'id' => 'active_off',
						'value' => 0,
						'label' => $this->l('Disabled')
					)
				),
				'hint' => $this->l('Allow or disallow this employee to log into the Admin panel.')
			);

			// if employee is not SuperAdmin (id_profile = 1), don't make it possible to select the admin profile
			if ($this->context->employee->id_profile != _PS_ADMIN_PROFILE_)
				foreach ($available_profiles as $i => $profile)
					if ($available_profiles[$i]['id_profile'] == _PS_ADMIN_PROFILE_)
					{
						unset($available_profiles[$i]);
						break;
					}
			$this->fields_form['input'][] = array(
				'type' => 'select',
				'label' => $this->l('Permission profile'),
				'name' => 'id_profile',
				'required' => true,
				'options' => array(
					'query' => $available_profiles,
					'id' => 'id_profile',
					'name' => 'name',
					'default' => array(
						'value' => '',
						'label' => $this->l('-- Choose --')
					)
				)
			);

			if (Shop::isFeatureActive())
			{
				$this->context->smarty->assign('_PS_ADMIN_PROFILE_', (int)_PS_ADMIN_PROFILE_);
				$this->fields_form['input'][] = array(
					'type' => 'shop',
					'label' => $this->l('Shop association'),
					'hint' => $this->l('Select the shops the employee is allowed to access.'),
					'name' => 'checkBoxShopAsso',
				);
			}
		}

		$this->fields_form['submit'] = array(
			'title' => $this->l('Save'),
		);

		$this->fields_value['passwd'] = false;
		$this->fields_value['bo_theme_css'] = $obj->bo_theme.'|'.$obj->bo_css;

		if (empty($obj->id))
			$this->fields_value['id_lang'] = $this->context->language->id;

		return AdminController::renderForm();
	}
}
