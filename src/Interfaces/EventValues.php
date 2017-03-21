<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/21/17
 * Time: 8:48 AM
 */

namespace Sicet7\EasyArray\Interfaces;


interface EventValues{

    public function __construct(array $values,int $event);

    public function getType():int;

    public function set(string $name,$value);

    public function get(string $name);

}