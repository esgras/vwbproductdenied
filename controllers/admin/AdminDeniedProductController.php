<?php

class AdminDeniedProductController extends ModuleAdminController
{

    protected $id_current_category;


    public function __construct()
    {
        $this->table = 'product';
        $this->className = 'Product';
        $this->bootstrap = true;
        $this->lang = true;
        $this->explicitSelect = true;

        if (!Tools::getValue('id_product')) {
            $this->multishop_context_group = false;
        }
        
        $this->content_only = false;

        parent::__construct();

        $this->bulk_actions = array();
        $this->toolbar_btn = array();

        $this->imageType = 'jpg';
        $this->_defaultOrderBy = 'position';
        $this->max_file_size = (int)(Configuration::get('PS_LIMIT_UPLOAD_FILE_VALUE') * 1000000);
        $this->max_image_size = (int)Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE');


        if ($id_category = (int)Tools::getValue('productFilter_cl!name')) {
            $this->_category = new Category((int)$id_category);
            $_POST['productFilter_cl!name'] = $this->_category->name[$this->context->language->id];
        } else {
            if ($id_category = (int)Tools::getValue('id_category')) {
                $this->id_current_category = $id_category;
                $this->context->cookie->id_category_products_filter = $id_category;
            } elseif ($id_category = $this->context->cookie->id_category_products_filter) {
                $this->id_current_category = $id_category;
            }
            if ($this->id_current_category) {
                $this->_category = new Category((int)$this->id_current_category);
            } else {
                $this->_category = new Category();
            }
        }

        $join_category = false;
        if (Validate::isLoadedObject($this->_category) && empty($this->_filter)) {
            $join_category = true;
        }

        $this->_join .= '
		LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.`id_product` = a.`id_product` AND sav.`id_product_attribute` = 0
		'.StockAvailable::addSqlShopRestriction(null, null, 'sav').') ';

        $alias = 'sa';
        $alias_image = 'image_shop';

        $id_shop = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP? (int)$this->context->shop->id : 'a.id_shop_default';
        $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` AND sa.id_shop = '.$id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON ('.$alias.'.`id_category_default` = cl.`id_category` AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = '.$id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = '.$id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_product` = a.`id_product` AND image_shop.`cover` = 1 AND image_shop.id_shop = '.$id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = image_shop.`id_image`)
				LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (pd.`id_product` = a.`id_product` AND pd.`active` = 1)';

        $this->_select .= 'shop.`name` AS `shopname`, a.`id_shop_default`, ';
        $this->_select .= $alias_image.'.`id_image` AS `id_image`, cl.`name` AS `name_category`, '.$alias.'.`price`, 0 AS `price_final`, a.`is_virtual`, pd.`nb_downloadable`, sav.`quantity` AS `sav_quantity`, '.$alias.'.`active`, IF(sav.`quantity`<=0, 1, 0) AS `badge_danger`';

        if ($join_category) {
            $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = a.`id_product` AND cp.`id_category` = '.(int)$this->_category->id.') ';
            $this->_select .= ' , cp.`position`, ';
        }
        $this->_use_found_rows = false;
        $this->_group = '';

        $this->fields_list = array();
        $this->fields_list['id_product'] = array(
            'title' => $this->l('ID'),
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'type' => 'int'
        );
        $this->fields_list['image'] = array(
            'title' => $this->l('Image'),
            'align' => 'center',
            'image' => 'p',
            'orderby' => false,
            'filter' => false,
            'search' => false
        );
        $this->fields_list['name'] = array(
            'title' => $this->l('Name'),
            'filter_key' => 'b!name'
        );
        $this->fields_list['reference'] = array(
            'title' => $this->l('Reference'),
            'align' => 'left',
        );

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->fields_list['shopname'] = array(
                'title' => $this->l('Default shop'),
                'filter_key' => 'shop!name',
            );
        } else {
            $this->fields_list['name_category'] = array(
                'title' => $this->l('Category'),
                'filter_key' => 'cl!name',
            );
        }
        $this->fields_list['price'] = array(
            'title' => $this->l('Base price'),
            'type' => 'price',
            'align' => 'text-right',
            'filter_key' => 'a!price'
        );
        $this->fields_list['price_final'] = array(
            'title' => $this->l('Final price'),
            'type' => 'price',
            'align' => 'text-right',
            'havingFilter' => true,
            'orderby' => false,
            'search' => false
        );

        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $this->fields_list['sav_quantity'] = array(
                'title' => $this->l('Quantity'),
                'type' => 'int',
                'align' => 'text-right',
                'filter_key' => 'sav!quantity',
                'orderby' => true,
                'badge_danger' => true,
                //'hint' => $this->l('This is the quantity available in the current shop/group.'),
            );
        }

        $this->fields_list['active'] = array(
            'title' => $this->l('Status'),
            'active' => 'status',
            'filter_key' => $alias.'!active',
            'align' => 'text-center',
            'type' => 'bool',
            'class' => 'fixed-width-sm',
            'orderby' => false
        );

        if ($join_category && (int)$this->id_current_category) {
            $this->fields_list['position'] = array(
                'title' => $this->l('Position'),
                'filter_key' => 'cp!position',
                'align' => 'center',
                'position' => 'position'
            );
        }
    }

    public function renderList()
    {
        if (!($this->fields_list && is_array($this->fields_list))) {
            return false;
        }

        $this->_where .= ' AND a.active=1 AND a.quantity=0 AND a.out_of_stock=2 ';

        $this->getList($this->context->language->id);
        $this->actions_available = array();




        // If list has 'active' field, we automatically create bulk action
        if (isset($this->fields_list) && is_array($this->fields_list) && array_key_exists('active', $this->fields_list)
            && !empty($this->fields_list['active'])) {
            if (!is_array($this->bulk_actions)) {
                $this->bulk_actions = array();
            }

            $this->bulk_actions = array_merge(array(
                'enableSelection' => array(
                    'text' => $this->l('Enable selection'),
                    'icon' => 'icon-power-off text-success'
                ),
                'disableSelection' => array(
                    'text' => $this->l('Disable selection'),
                    'icon' => 'icon-power-off text-danger'
                ),
                'divider' => array(
                    'text' => 'divider'
                )
            ), $this->bulk_actions);
        }

        $helper = new HelperList();

        // Empty list is ok
        if (!is_array($this->_list)) {
            $this->displayWarning($this->l('Bad SQL query', 'Helper').'<br />'.htmlspecialchars($this->_list_error));
            return false;
        }

        $this->setHelperDisplay($helper);

        $helper->_default_pagination = $this->_default_pagination;
        $helper->_pagination = $this->_pagination;
        $helper->tpl_vars = $this->getTemplateListVars();
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;

        // For compatibility reasons, we have to check standard actions in class attributes
        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }

        $helper->toolbar_btn = array();
        $helper->bulk_actions = array();

        $helper->is_cms = $this->is_cms;
        $helper->sql = $this->_listsql;
        $list = $helper->generateList($this->_list, $this->fields_list);
        

        return $list;
    }

    public function getProductsToken()
    {
        return Tools::getAdminToken('AdminProducts'.intval(Tab::getIdFromClassName('AdminProducts')).intval($this->context->cookie->id_employee));
    }

    public function initFooter()
    {
        $productsToken = $this->getProductsToken();

        Media::addJsDef(array(
                'productsToken' => $productsToken,
                'searchController' => 'AdminDeniedProduct',
                'replaceController' => 'AdminProducts'
            )
        );

        $this->addJs($this->module->getModuleUri() . 'views/js/my.js');

        parent::initFooter();

    }
}
