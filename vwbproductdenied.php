<?php
class VwbProductDenied extends Module
{
    public function __construct()
    {
        $this->name = 'vwbproductdenied';
        $this->version = '1.0.0';
        $this->author = 'ViWeb';
        $this->tab = 'front_office_features';
        parent::__construct();

        $this->displayName = 'Vwb Product Denied';
        $this->description = $this->l('Module for making grid of inactive or denied products');
    }

    public function install()
    {
        if (!parent::install())
            return false;

        if (!$this->installTab(0, 'AdminDeniedProduct', 'Denied Products'))
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall())
            return false;

        if (!$this->uninstallTab('AdminDeniedProduct'))
            return false;

        return true;
    }

    public function installTab($parent, $class_name, $name)
    {
        if (is_int($parent)) {
            $id_parent = $parent;
        } else {
            $id_parent = Tab::getIdFromClassName($class_name);
        }

        $tab = new Tab;
        $tab->id_parent = $id_parent;
        $tab->class_name = $class_name;
        $tab->module = $this->name;
        foreach(Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }

        return $tab->add();
    }

    public function uninstallTab($class_name)
    {
        $id_tab = Tab::getIdFromClassName($class_name);
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    public function getModuleUri()
    {
        return $this->_path;
    }
}
