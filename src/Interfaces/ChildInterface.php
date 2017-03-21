<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/17/17
 * Time: 2:19 PM
 */

namespace Sicet7\EasyArray\Interfaces;

use \ArrayAccess;
use \IteratorAggregate;
use \ArrayIterator;

interface ChildInterface extends ArrayAccess, IteratorAggregate{

    #region Public

    #region Magic Methods

    public function __construct(EasyArrayInterface &$easyArray,string $path);

    public function __get($name);

    public function __set($name, $value):bool;

    public function __isset($name):bool;

    public function __unset($name);

    public function __call($name, $arguments);

    #endregion

    #region ArrayAccess

    public function offsetExists($offset):bool;

    public function offsetSet($offset, $value):bool;

    public function offsetGet($offset);

    public function offsetUnset($offset);

    #endregion

    #region IteratorAggregate

    public function getIterator():ArrayIterator;

    #endregion

    #endregion

}