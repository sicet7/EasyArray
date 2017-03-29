<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/21/17
 * Time: 8:48 AM
 */

namespace Sicet7\EasyArray\Interfaces;


interface IEventValues{

    public function __construct(array $eventValues,int $event);

    public function getType():int;

    public function get(string $name);

    public function blockAction();

}