<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/29/17
 * Time: 11:00 AM
 */

namespace Sicet7\EasyArray;


use Sicet7\EasyArray\Interfaces\ISerializer;

class JsonSerializer extends \Zumba\JsonSerializer\JsonSerializer implements ISerializer{// This class is made cuz i want to use the "ISerializer" to check on

    public function serialize($value){
        return parent::serialize($value);
    }

    public function unserialize($value){
        return parent::unserialize($value);
    }

}