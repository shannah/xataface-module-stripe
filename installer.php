<?php
class modules_stripe_installer {
    function update_1() {
        $sql[] = "CREATE TABLE `stripe_customers` ( `username` VARCHAR(100) NOT NULL , `customer_id` VARCHAR(64) NOT NULL , PRIMARY KEY (`username`), UNIQUE (`customer_id`)) ENGINE = InnoDB;";
        $sql[] = "CREATE TABLE `stripe_transactions` ( `transaction_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT , `customer_id` VARCHAR(64) NOT NULL , `type` VARCHAR(64) NOT NULL , `data` TEXT NOT NULL , `date_created` DATETIME NULL , PRIMARY KEY (`transaction_id`), INDEX (`customer_id`), INDEX (`type`)) ENGINE = InnoDB;";
        
        df_q($sql);
    }
    
    function update_2() {
        $sql[] = "ALTER TABLE `stripe_customers` ADD `currency` VARCHAR(3) NULL AFTER `customer_id`;";
        df_q($sql);
    }
    
    function update_3() {
        $sql[] = "CREATE TABLE `stripe_exchange_rates` ( `base_currency` VARCHAR(3) NOT NULL , `target_currency` VARCHAR(3) NOT NULL , `conversion` FLOAT(11) NOT NULL , `last_updated` DATETIME NOT NULL , PRIMARY KEY (`base_currency`, `target_currency`)) ENGINE = MyISAM;";
        $sql[] = "CREATE TABLE `stripe_products` ( `id` VARCHAR(64) NOT NULL , `active` TINYINT(1) NOT NULL , `name` VARCHAR(200) NOT NULL , `data` TEXT NOT NULL , `updated` BIGINT(20) NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;";
        
        $sql[] = "CREATE TABLE `stripe_prices` ( `id` VARCHAR(64) NOT NULL , `product` VARCHAR(64) NOT NULL , `currency` VARCHAR(3) NOT NULL , `active` TINYINT(1) NOT NULL , `unit_amount` INT(11) NOT NULL , `unit_amount_decimal` VARCHAR(20) NOT NULL , `data` TEXT NOT NULL , PRIMARY KEY (`id`), INDEX (`product`)) ENGINE = MyISAM;";
        df_q($sql);
    }
    
    function update_4() {
        $sql[] = "ALTER TABLE `stripe_customers` ADD `data` TEXT NULL AFTER `currency`;";
        df_q($sql);
    }
}
?>