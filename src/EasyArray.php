<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/17/17
 * Time: 11:55 AM
 */

namespace Sicet7\EasyArray;

use \Closure;
use \ArrayIterator;
use Zumba\JsonSerializer\JsonSerializer;
use SuperClosure\Serializer;
use Sicet7\EasyArray\Interfaces\EasyArrayInterface;

class EasyArray implements EasyArrayInterface{

    #region Constants

    const SERIALIZE = 0;
    const UNSERIALIZE = 1;
    const DEFINE = 2;
    const DELETE = 3;
    const UNDO = 4;

    #endregion

    #region Properties

    #region Protected

    protected $_values = array();
    protected $_change = false;
    protected $_events = array(
        EasyArrayInterface::GET_EVENT => array(),
        EasyArrayInterface::SET_EVENT => array(),
        EasyArrayInterface::ISSET_EVENT => array(),
        EasyArrayInterface::UNSET_EVENT => array(),
        EasyArrayInterface::CALL_EVENT => array()
    );
    protected $_executeEvents = true;

    #endregion

    #region Private

    private $__serializerInstance = null;

    private static $__serializer = null;

    #endregion

    #endregion

    #region Init

    public function __construct(array $values = [],bool $change = false){
        if(!isset(self::$__serializer)){
            self::$__serializer = new JsonSerializer(new Serializer);
        }
        $this->__serializerInstance = self::$__serializer;
        $this->_values = $this->_recursiveValueUnpacker($values);
        $this->_change = $change;
    }

    #endregion

    #region Serializable

    public function serialize(): string{
        // TODO: Implement serialize() method.
    }

    public function unserialize($serialized){
        // TODO: Implement unserialize() method.
    }

    #endregion

    #region Getters, Setters, Unset and Isset

    #region Normal Methods

    public function get(string $offset){

        // TODO: Integrate Event Activation

        if(strpos($offset,'.') !== false){
            $copy = $this->_values;//values stored in the array is copied
            $keys = explode('.',$offset);

            foreach($keys as $key){

                if($key == ''){
                    $copy = null;
                    break;
                }

                if(isset($copy[$key]) || @array_key_exists($key,$copy)){
                    $copy = $copy[$key];
                }else{
                    $copy = null;
                    break;
                }
            }

            // TODO : Implement a instance of the Child Class instead.
            return (is_array($copy) && !($copy instanceof EasyArray) ? new EasyArray($copy,$this->_change) : $copy);
        }else{
            return (is_array($this->_values[$offset]) && !($this->_values[$offset] instanceof EasyArray) ? new EasyArray($this->_values[$offset],$this->_change) : $this->_values[$offset]);
        }

    }

    public function set(string $offset, $value): bool{

        // TODO: Implement set() method.

    }

    public function exists(string $offset): bool{
        // TODO: Implement exists() method.
    }

    public function remove(string $offset){
        // TODO: Implement remove() method.
    }

    #endregion

    #region Magic Methods

    public function __get(string $name){
        // TODO: Implement __get() method.
    }

    public function __set(string $name,$value): bool{
        // TODO: Implement __set() method.
    }

    public function __isset(string $name): bool{
        // TODO: Implement __isset() method.
    }

    public function __unset(string $name){
        // TODO: Implement __unset() method.
    }

    #endregion

    #region EasyArray

    public function offsetGet($offset){
        return $this->get((string) $offset);
    }

    public function offsetSet($offset, $value){
        return $this->set((string) $offset,$value);
    }

    public function offsetExists($offset){
        return $this->exists((string) $offset);
    }

    public function offsetUnset($offset){
        return $this->remove((string) $offset);
    }

    #endregion

    #endregion

    #region Methods

    public function addEvent(Closure $callback, int $type = EasyArrayInterface::GET_EVENT,int $position = null){
        // TODO: Implement addEvent() method.
    }

    public function add(string $offset, $value): bool{
        // TODO: Implement add() method.
    }

    public function asArray(): array{
        // TODO: Implement asArray() method.
    }

    public function toJson(bool $onlyValues = true): string{
        // TODO: Implement toJson() method.
    }

    public function merge(array $array, bool $overwrite = false){
        // TODO: Implement merge() method.
    }

    public function getSerializer(): JsonSerializer{
        return $this->__serializerInstance;
    }

    #endregion

    #region IteratorAggregate

    public function getIterator():ArrayIterator{
        return new ArrayIterator($this->_values);
    }

    #endregion

    #region Protected Methods

    protected function _recursiveValueUnpacker(array $array):array{

        $a = [];

        foreach($array as $k => $v){

            if(strpos($k,'.') !== false){
                $keys = explode('.',$k);

                foreach($keys as $index => $keyValue){

                    $count = $index+1;

                    if(!isset($ref))
                        $ref = &$a;

                    if(count($keys) == $count){
                        $ref[$keyValue] = (is_array($v) ? $this->_recursiveValueUnpacker($v) : $v);
                    }else{
                        if(!$this->_check($ref,$keyValue) || ($this->_check($ref,$keyValue) && !is_array($ref[$keyValue])))
                            $ref[$keyValue] = array();
                        $ref = &$ref[$keyValue];
                    }

                }

            }else{
                if(is_array($v)){
                    $a[$k] = $this->_recursiveValueUnpacker($v);
                }else{
                    $a[$k] = $v;
                }
            }


        }

        return $a;

    }

    protected function _check(array $ar,$key):bool{
        if(strpos($key,'.') !== false){
            $keys = explode('.',$key);
            $ref = $ar;
            $re = true;

            foreach($keys as $tKey){

                if($tKey == ''){
                    $re = false;
                    break;
                }

                if(!isset($ref[$tKey]) && !@array_key_exists($tKey,$ref)){
                    $re = false;
                    break;
                }else{
                    $ref = $ref[$tKey];
                }

            }

            return $re;

        }else{
            return (isset($ar[$key]) || @array_key_exists($key,$ar));
        }
    }

    protected function _runEvents(int $type, $values):bool{
        // TODO: Implementation method.
        // TODO: Implement _runEvents();
    }

    #endregion

    #region Public Static

    public static function comCreate(){
        // TODO: Implement comCreate() method.
    }

    #endregion

}