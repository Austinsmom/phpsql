<?php
/**
 * @author Gerrit Addiks <gerrit.addiks@brille24.de>
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Addiks\PHPSQL\PDO;
use Addiks\PHPSQL\Entity\Exception\MalformedSql;
use Addiks\PHPSQL\ResultWriter;

define("BASEDIR", realpath(__DIR__."/.."));

function __autoload($className)
{
    $filePath = str_replace("\\", "/", $className);
    $filePath = BASEDIR."/src/{$filePath}.php";
    if (file_exists($filePath)) {
        require_once($filePath);
    }
}

$pdo = new PDO("inmemory:some_example_database");

try{

    // create a few tables inside that database
    // (the ENGINE definition will be ignored)
    $pdo->query("

        CREATE TABLE `product` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(64) DEFAULT NULL,
          `price` float DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

        CREATE TABLE `customer` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(64) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

        CREATE TABLE `cart_item` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `customer_id` int(11) DEFAULT NULL,
          `product_id` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `fk_cusotmer_idx` (`customer_id`),
          KEY `fk_product_idx` (`product_id`),
          CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
          CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

    ");

    // Oops, forgot the quantity on that cart-item table, let's add it
    $pdo->query("ALTER TABLE `cart_item` ADD COLUMN `quantity` FLOAT(3,2) NOT NULL DEFAULT 1.0");

    // Let's have a look on the table 'cart_item'
    echo new ResultWriter($pdo->query("DESCRIBE `cart_item`")->getResult());

    $pdo->query("
        INSERT INTO `product`
            (id, name, price)
        VALUES
            (1, 'Socks', 12.34),
            (2, 'Pants', 56.78),
            (3, 'T-Shirt', 31.41);

        INSERT INTO `customer`
            (id, name)
        VALUES
            (12, 'John Smith'),
            (34, 'Ka Ching'),
            (56, 'Hans Müller');

        INSERT INTO `cart_item`
            (customer_id, product_id, quantity)
        VALUES
            (12, 1, 4),
            (12, 2, 2),
            (34, 3, 3),
            (56, 1, 2),
            (56, 3, 1);
    ");

    // just dump all data
    echo new ResultWriter($pdo->query("
        SELECT *
        FROM
            cart_item
        LEFT JOIN
            product ON(product.id = product_id)
        LEFT JOIN
            customer ON(customer.id = customer_id)
    "));
    
    // how often were our products ordered?
    echo new ResultWriter($pdo->query("
        SELECT
            product.name,
            SUM(cart_item.quantity)
        FROM
            product
        LEFT JOIN
            cart_item ON(id = product_id)
        GROUP BY
            product.name
    "));

} catch (MalformedSql $exception) {
    echo $exception;
}
