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
use Sicet7\EasyArray\Interfaces\ISerializer;
use SuperClosure\Analyzer\TokenAnalyzer;
use Sicet7\EasyArray\Interfaces\IEasyArray;
use Sicet7\EasyArray\JsonSerializer as JsonS;
use SuperClosure\Serializer;

class EasyArray implements IEasyArray{

    #region Constants

    const ALLOWCHANGE = 'CHANGE';
    const ALLOWAPPEND = 'APPEND';
    const EVENTSENABLE = 'EVENTS';
    const SERIALIZER = 'SERIALIZER';

    #endregion

    #region Properties

    #region Protected

    protected $_values = array();
    protected $_change = FALSE;
    protected $_append = FALSE;
    protected $_options = array();
    protected $_events = array(
        IEasyArray::BEFORE_GET_EVENT => array(),
        IEasyArray::AFTER_GET_EVENT => array(),
        IEasyArray::BEFORE_SET_EVENT => array(),
        IEasyArray::AFTER_SET_EVENT => array(),
        IEasyArray::BEFORE_ISSET_EVENT => array(),
        IEasyArray::AFTER_ISSET_EVENT => array(),
        IEasyArray::BEFORE_UNSET_EVENT => array(),
        IEasyArray::AFTER_UNSET_EVENT => array(),
        IEasyArray::BEFORE_CALL_EVENT => array(),
        IEasyArray::AFTER_CALL_EVENT => array()
    );
    protected $_executeEvents = TRUE;

    #endregion

    #region Private

    private $__serializerInstance = NULL;

    #endregion

    #endregion

    #region Init

    public function __construct(array $values = [],array $options = []){

        $ope = $this->_validateOptions($options);

        $this->_executeEvents           = $this->_options[EasyArray::EVENTSENABLE]  = $ope[EasyArray::EVENTSENABLE];
        $this->__serializerInstance     = $this->_options[EasyArray::SERIALIZER]    = $ope[EasyArray::SERIALIZER];
        $this->_change                  = $this->_options[EasyArray::ALLOWCHANGE]   = $ope[EasyArray::ALLOWCHANGE];
        $this->_append                  = $this->_options[EasyArray::ALLOWAPPEND]   = $ope[EasyArray::ALLOWAPPEND];


        $this->_recursiveValueUnpacker($values);
        $this->_values = $values;

    }

    #endregion

    #region Serializable

    public function serialize(): string{
        return $this->getSerializer()->serialize(['values' => $this->_values, 'change' => $this->_change, 'events' => $this->_events]);
    }

    public function unserialize($serialized):IEasyArray{
        $un = $this->getSerializer()->unserialize($serialized);
        $this->_values = $un['values'];
        $this->_change = $un['change'];
        $this->_events = $un['events'];
        return $this;
    }

    #endregion

    #region Getters, Setters, Unset and Isset

    #region Normal Methods

    public function get(string $offset){

        // TODO: Integrate Event Activation

        if(strpos($offset,'.') !== FALSE){
            $copy = $this->_values;//values stored in the array is copied
            $keys = explode('.',$offset);

            foreach($keys as $key){

                if($key == ''){
                    $copy = NULL;
                    break;
                }

                if(is_object($copy)){

                    $reflect = new \ReflectionObject($copy);

                    if($reflect->hasConstant($key)){
                        $copy = $reflect->getConstant($key);
                    }elseif($reflect->hasProperty($key)){
                        $copy = $reflect->getProperty($key)->getValue($copy);
                    }else{
                        $copy = NULL;
                        break;
                    }

                }elseif(isset($copy[$key]) || @array_key_exists($key,$copy)){

                    $copy = $copy[$key];

                }else{
                    $copy = NULL;
                    break;
                }
            }

            // TODO : Implement a instance of the Child Class instead.
            return (is_array($copy) && !($copy instanceof EasyArray) ? new EasyArray($copy,$this->_options) : $copy);
        }else{
            return (is_array($this->_values[$offset]) && !($this->_values[$offset] instanceof EasyArray) ? new EasyArray($this->_values[$offset],$this->_options) : $this->_values[$offset]);
        }

    }

    public function set(string $offset, $value): bool{

        if(!$this->_change)
            throw new \BadMethodCallException("Data change has been disabled");

        // TODO: Integrate Event Activation

        if(strpos($offset,'.') !== FALSE){

            $keys = explode('.', $offset);
            $ref = &$this->_values;

            foreach($keys as $key => $keyValue){

                $k = $key+1;

                if($keyValue == '')
                    break;

                if(count($keys) == $k){
                    $ref[$keyValue] = $value;
                }else{
                    if(!$this->_check($ref,$keyValue) || ($this->_check($ref,$keyValue) && !is_array($ref[$keyValue])))
                        $ref[$keyValue] = array();
                    $ref = &$ref[$keyValue];
                }

            }

        }else{

            $this->_values[$offset] = $value;

        }

        return $this->sameAs($offset,$value);

    }

    public function exists(string $offset): bool{

        // TODO: Integrate Event Activation

        return $this->_check($this->_values,$offset);
    }

    public function remove(string $offset){


        if(!$this->_change)
            throw new \BadMethodCallException("Data change has been disabled");

        // TODO: Integrate Event Activation

        if(strpos($offset,'.') !== FALSE){
            $ref = &$this->_values;
            $keys = explode('.',$offset);
            $unsetElementKey = array_pop($keys);

            foreach($keys as $index => $keyValue){

                $k = $index+1;

                if($keyValue == '')
                    break;

                if(isset($ref[$keyValue]) || @array_key_exists($keyValue,$ref)){
                    if(count($keys) == $k){
                        $ref = &$ref[$keyValue];
                        unset($ref[$unsetElementKey]);
                        break;
                    }else{
                        if(!$this->_check($ref,$keyValue) || ($this->_check($ref,$keyValue) && !is_array($ref[$keyValue])))
                            $ref[$keyValue] = array();
                        $ref = &$ref[$keyValue];
                    }
                }else{
                    break;
                }

            }

        }else{
            unset($this->_values[$offset]);
        }

    }

    #endregion

    #region ArrayAccess

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

    public function addEvent(Closure $callback, int $type = IEasyArray::BEFORE_GET_EVENT,int $position = NULL){
        // TODO: Implement addEvent() method.
    }

    public function add(string $offset, $value): bool{

        //check if append is enabled
        if(!$this->_append)
            throw new \BadMethodCallException("Data append has been disabled");

        //unpack the array
        $a = [$offset => $value];
        $this->_recursiveValueUnpacker($a);


        //marge unacpked array with current values in $this->_values
        $this->_values = array_merge_recursive($this->_values,$a);

        //store event execution state
        $default = $this->_executeEvents;

        //disable events
        $this->_executeEvents = FALSE;

        //get the offset from the array
        $obj = $this->get($offset);

        //check if the offset is returned as an array and then convert it, if not array just match the added value with retrieved value
        if($obj instanceof IEasyArray){
            $tmpArrayHolder = $obj->asArray();
            $returnValue = ($value === array_pop($tmpArrayHolder));
        }else{
            $returnValue = ($obj === $value);
        }

        //revert event execution state
        $this->_executeEvents = $default;

        //return boolean that tells if it was added successfully
        return $returnValue;

    }

    public function asArray(): array{
        return $this->_values;
    }

    public function merge(array $array, bool $overwrite = TRUE){

        //check if change is allowed
        if($overwrite && !$this->_change)
            throw new \BadMethodCallException("Data change has been disabled");

        //check if append is allowed
        if(!$overwrite && !$this->_append)
            throw new \BadMethodCallException("Data append has been disabled");

        $this->_recursiveValueUnpacker($array);

        if($overwrite){
            $this->_values = array_replace_recursive($this->_values,$array);
        }else{
            $this->_values = array_merge_recursive($this->_values,$array);
        }

    }

    public function getSerializer(): ISerializer{
        return $this->__serializerInstance;
    }

    public function sameAs(string $offset, $value):bool{

        //store event execution state
        $default = $this->_executeEvents;

        //disable events
        $this->_executeEvents = FALSE;

        //get the information
        $obj = $this->get($offset);

        //restore event state to default after we got out information
        $this->_executeEvents = $default;

        //convert EasyArray's to array if both variables are not EasyArrays
        if(($obj instanceof IEasyArray) && !($value instanceof IEasyArray)) $obj = $obj->asArray();

        //getting the types of the variables
        $valueType = gettype($value);
        $objType = gettype($obj);


        //return if types are not the same
        if($valueType !== $objType) return FALSE;

        if($valueType === 'object'){

            //if variables types are objects
            //make sure both objects are the same class.
            if(get_class($value) !== get_class($obj)) return FALSE;

            //instantiate reflections
            $match = new \ReflectionObject($obj);
            $reflect = new \ReflectionObject($value);

            //get object constants
            $matchConstants = $match->getConstants();
            $reflectConstants = $reflect->getConstants();

            //sort arrays of object constants by key
            ksort($matchConstants);
            ksort($reflectConstants);

            //make sure the two arrays of constants match
            if($matchConstants !== $reflectConstants) return FALSE;

            //cleanup
            unset($matchConstants,$reflectConstants);

            //define anon function set names as keys and a variable value based on input.
            $nameAsKey = function(array &$value,$obj = NULL){
                foreach($value as $index => $v){

                    if($v instanceof \ReflectionMethod){
                        $value[$v->getName()] = $v->getClosure();
                        unset($value[$index]);
                    }

                    if($v instanceof \ReflectionProperty){
                        $value[$v->getName()] = $v->getValue($obj);
                        unset($value[$index]);
                    }

                }
            };

            //get object properties
            $matchProperties = $match->getProperties();
            $reflectProperties = $reflect->getProperties();

            //Set the name of the property to the key and the value to the value.
            $nameAsKey($matchProperties,$obj);
            $nameAsKey($reflectProperties,$value);

            //sort the property arrays by key
            ksort($matchProperties);
            ksort($reflectProperties);

            //make sure the two arrays of properties match
            if($matchProperties !== $reflectProperties) return FALSE;

            //cleanup
            unset($matchProperties,$reflectProperties);

            //get object methods
            $matchMethods = $match->getMethods();
            $reflectMethods = $reflect->getMethods();

            //set the name as the key and the closure as the value
            $nameAsKey($matchMethods);
            $nameAsKey($reflectMethods);

            //sort the method arrays by key
            ksort($matchMethods);
            ksort($reflectMethods);

            // make sure the arrays match
            if($matchMethods !== $reflectMethods) return FALSE;

            //last cleanup
            unset($matchMethods,$reflectMethods,$match,$reflect,$nameAsKey);

        }else{

            //if the type of the values are arrays sort them by key
            if($valueType === 'array'){
                ksort($value);
                ksort($obj);
            }

            //check if the value match
            if($value !== $obj) return FALSE;

        }

        return TRUE;

    }

    public function count(string $offset = NULL):int{

        //returns entire values array if no offset is passed
        if(is_null($offset)){
            return count($this->_values);
        }

        //store event execution state
        $default = $this->_executeEvents;

        //disable event execution
        $this->_executeEvents = FALSE;

        //get the value that is being counted
        $obj = $this->get($offset);

        //count the value and if EasyArray is returned convert it to an array. Potential Exception thrown here if a not countable is returned by the get operation.
        $returnValue = count(($obj instanceof IEasyArray) ? $obj->asArray() : $obj);

        //set event execution state back
        $this->_executeEvents = $default;

        //return count
        return $returnValue;

    }

    #endregion

    #region IteratorAggregate

    public function getIterator():ArrayIterator{
        return new ArrayIterator($this->_values);
    }

    #endregion

    #region Protected Methods

    protected function _recursiveValueUnpacker(array &$array){

        foreach($array as $k => $v){
            if(strpos($k,'.') !== FALSE){

                //separates the keys
                $keys = explode('.',$k);

                //counts the amount of keys
                $keyCount = count($keys);

                foreach($keys as $index => $keyValue){

                    //resetter, will reset back to root array.
                    if(!isset($ref)) $ref = &$array;

                    //checks if we are at last key
                    if($keyCount === $index+1){

                        //gives sub-arrays same procedure
                        if(is_array($v)) $this->_recursiveValueUnpacker($v);

                        //defines
                        $ref[$keyValue] = $v;

                        //removes reference to sub-array
                        unset($ref);
                    }else{
                        //check if the reference contains the key
                        $check = $this->_check($ref,$keyValue);

                        //if key is not set or is not an array will set it to an array
                        if(!$check || ($check && !is_array($ref[$keyValue]))) $ref[$keyValue] = array();

                        //redefines reference to the newly created array
                        $ref = &$ref[$keyValue];
                    }

                }
                //removes not unpacked value from main array.
                unset($array[$k]);
            }else{
                if(is_array($array[$k])) $this->_recursiveValueUnpacker($array[$k]);
            }
        }
    }

    protected function _check(array $ar,$key):bool{
        if(strpos($key,'.') !== FALSE){
            $keys = explode('.',$key);
            $ref = $ar;
            $re = TRUE;

            foreach($keys as $tKey){

                if($tKey == ''){
                    $re = FALSE;
                    break;
                }

                if(isset($ref[$tKey]) || @array_key_exists($tKey,$ref)){
                    $ref = $ref[$tKey];
                }else{
                    $re = FALSE;
                    break;
                }

            }

            return $re;

        }else{
            return (isset($ar[$key]) || @array_key_exists($key,$ar));
        }
    }

    protected function _runEvents(int $type):bool{
        // TODO: Implementation method.
        // TODO: Implement _runEvents();
    }

    protected function _validateOptions(array $options):array{

        if(array_key_exists(EasyArray::SERIALIZER,$options)){
            if(!($options[EasyArray::SERIALIZER] instanceof ISerializer)) throw new \InvalidArgumentException('Serializer must implement "Sicet7\\EasyArray\\Interfaces\\ISerializer"');
        }else{
            $options[EasyArray::SERIALIZER] = new JsonS(new Serializer(new TokenAnalyzer()));
        }

        if(array_key_exists(EasyArray::ALLOWCHANGE,$options)){
            if(!is_bool($options[EasyArray::ALLOWCHANGE])) throw new \InvalidArgumentException('Change must be a boolean value!');
        }else{
            $options[EasyArray::ALLOWCHANGE] = FALSE;
        }

        if(array_key_exists(EasyArray::ALLOWAPPEND,$options)){
            if(!is_bool($options[EasyArray::ALLOWAPPEND])) throw new \InvalidArgumentException('Append must be a boolean value!');
        }else{
            $options[EasyArray::ALLOWAPPEND] = $options[EasyArray::ALLOWCHANGE];
        }

        if(array_key_exists(EasyArray::EVENTSENABLE,$options)){
            if(!is_bool($options[EasyArray::EVENTSENABLE])) throw new \InvalidArgumentException('Events must be a boolean value!');
        }else{
            $options[EasyArray::EVENTSENABLE] = FALSE;
        }

        return $options;

    }

    #endregion

}