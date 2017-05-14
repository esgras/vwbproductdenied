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

        if (!$this->loadSqlFromFile(__DIR__ . '/install/install.sql'))
            return false;


        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall())
            return false;

        if (!$this->uninstallTab('AdminDeniedProduct'))
            return false;

        if (!$this->loadSqlFromFile(__DIR__ . '/install/uninstall.sql'))
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

    public function loadSqlFromFile($file)
    {
        $sqlContent = file_get_contents($file);
        $sqlContent = str_replace('PREFIX_', _DB_PREFIX_, $sqlContent);
        $sqlContent = str_replace('FIELD_NAME', $this->fieldName, $sqlContent);
        $queries = preg_split('#;\s*[\r\n]*#', $sqlContent, -1, PREG_SPLIT_NO_EMPTY);

        $res = true;
        foreach ($queries as $query) {
              $res &= DB::getInstance()->execute(trim($query));
        }

        return $res;
    }
}
