<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/17/17
 * Time: 1:23 PM
 */

namespace Sicet7\EasyArray\Interfaces;

use \ArrayIterator;
use \Closure;
use \ArrayAccess;
use \IteratorAggregate;
use \Serializable;
use Zumba\JsonSerializer\JsonSerializer;


interface EasyArrayInterface extends ArrayAccess, IteratorAggregate, Serializable {

    #region Constants

    const GET_EVENT = 0;
    const SET_EVENT = 1;
    const ISSET_EVENT = 2;
    const UNSET_EVENT = 3;
    const CALL_EVENT = 4;

    #endregion

    #region Public

    #region Magic Methods

    public function __construct(array $values = [],bool $change = false);

    public function __set(string $name,$value):bool;

    public function __get(string $name);

    public function __unset(string $name);

    public function __isset(string $name):bool;

    #endregion

    #region Normal Methods

    public function get(string $offset);

    public function set(string $offset,$value):bool;

    public function add(string $offset,$value):bool;

    public function remove(string $offset);

    public function exists(string $offset):bool;

    public function asArray():array;

    public function toJson(bool $onlyValues = true):string;

    public function addEvent(Closure $callback,int $type = EasyArrayInterface::GET_EVENT, int $position = null);

    public function merge(array $array,bool $overwrite = false);

    public function getSerializer():JsonSerializer;

    #endregion

    #region Array Access

    public function offsetGet($offset);

    public function offsetExists($offset);

    public function offsetSet($offset, $value);

    public function offsetUnset($offset);

    #endregion

    #region IteratorAggregate

    public function getIterator():ArrayIterator;

    #endregion

    #region Serializable

    public function serialize():string;

    public function unserialize($serialized);

    #endregion

    #endregion

}