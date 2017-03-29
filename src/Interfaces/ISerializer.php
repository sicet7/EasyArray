<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/29/17
 * Time: 10:53 AM
 */

namespace Sicet7\EasyArray\Interfaces;


interface ISerializer{

    public function serialize($data);

    public function unserialize($data);

}