<?php
Product::$definition['fields']['date_inactive'] = array(
    'type' => ObjectModel::TYPE_INT,
);

class Product extends ProductCore
{
   public $date_inactive;

}
