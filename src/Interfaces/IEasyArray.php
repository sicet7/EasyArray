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