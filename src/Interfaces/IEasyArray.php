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


interface IEasyArray extends ArrayAccess, IteratorAggregate, Serializable {

    #region Constants

    const BEFORE_GET_EVENT = 0;
    const AFTER_GET_EVENT = 1;
    const BEFORE_SET_EVENT = 2;
    const AFTER_SET_EVENT = 3;
    const BEFORE_ISSET_EVENT = 4;
    const AFTER_ISSET_EVENT = 5;
    const BEFORE_UNSET_EVENT = 6;
    const AFTER_UNSET_EVENT = 7;
    const BEFORE_CALL_EVENT = 8;
    const AFTER_CALL_EVENT = 9;

    #endregion

    #region Public

    #region Magic Methods

    public function __construct(array $values = [],array $options = []);

    #endregion

    #region Normal Methods

    public function get(string $offset);

    public function set(string $offset,$value):bool;

    public function add(string $offset,$value):bool;

    public function remove(string $offset);

    public function exists(string $offset):bool;

    public function asArray():array;

    public function addEvent(Closure $callback,int $type = IEasyArray::BEFORE_GET_EVENT, int $position = NULL);

    public function merge(array $array,bool $overwrite = FALSE);

    public function getSerializer():ISerializer;

    public function sameAs(string $offset,$value):bool;
    
    public function count(string $offset = NULL):int;

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