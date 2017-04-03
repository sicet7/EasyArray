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

    const
        ALLOWCHANGE   = 'CHANGE',
        ALLOWAPPEND   = 'APPEND',
        EVENTSENABLE  = 'EVENTS',
        SERIALIZER    = 'SERIALIZER';

    #endregion

    #region Properties



    protected
        $_values  = array(),
        $_change  = FALSE,
        $_append  = FALSE,
        $_options = array();



    private
        $__serializerInstance = NULL;


    #endregion

    #region Init

    public function __construct(array $values = [],array $options = []){

        //validates options
        $ope = $this->_validateOptions($options);

        //sets options
        $this->__serializerInstance     = $this->_options[EasyArray::SERIALIZER]    = $ope[EasyArray::SERIALIZER];
        $this->_change                  = $this->_options[EasyArray::ALLOWCHANGE]   = $ope[EasyArray::ALLOWCHANGE];
        $this->_append                  = $this->_options[EasyArray::ALLOWAPPEND]   = $ope[EasyArray::ALLOWAPPEND];

        //unpacks values
        $this->_recursiveValueUnpacker($values);

        //sets main values
        $this->_values = $values;

    }

    #endregion

    #region Serializable

    public function serialize(): string{
        //returns the object serialized
        return $this->getSerializer()->serialize(['values' => $this->_values, 'change' => $this->_change]);
    }

    public function unserialize($serialized):IEasyArray{

        //universalizes passed data
        $un = $this->getSerializer()->unserialize($serialized);

        if(!array_key_exists('values',$un) || !array_key_exists('change',$un))
            throw new \InvalidArgumentException('Data invalid');

        //binds the data to this object and then returns this object
        $this->_values = $un['values'];
        $this->_change = $un['change'];
        return $this;

    }

    #endregion

    #region Getters, Setters, Unset and Isset

    #region Normal Methods

    public function get(string $offset){

        //determines if the offset/key we are looking for might be nested based on dots
        if(strpos($offset,'.') !== FALSE){

            //copies main values array
            $copy = $this->_values;

            //splits keys into array based on dots
            $keys = explode('.',$offset);

            //loops keys
            foreach($keys as $key){

                //if key is a empty string return NULL
                if($key == ''){
                    $copy = NULL;
                    break;
                }

                //if key pointed to a object
                if(is_object($copy)){

                    //reflects on object
                    $reflect = new \ReflectionObject($copy);

                    //checks if has constant or property with the name of the key
                    if($reflect->hasConstant($key)){
                        $copy = $reflect->getConstant($key);
                    }elseif($reflect->hasProperty($key)){
                        $copy = $reflect->getProperty($key)->getValue($copy);
                    }else{
                        //returns null if object does not have constant or property with the same name as the key we are looking for
                        $copy = NULL;
                        break;
                    }

                }elseif(isset($copy[$key]) || @array_key_exists($key,$copy)){

                    //changes array.
                    $copy = $copy[$key];

                }else{
                    //if key is not found return null
                    $copy = NULL;
                    break;
                }
            }

            // return what ever is in the copy variable unless it is an array then it is converted to a new EastArray object
            return (is_array($copy) && !($copy instanceof EasyArray) ? new EasyArray($copy,$this->_options) : $copy);
        }else{
            return (is_array($this->_values[$offset]) && !($this->_values[$offset] instanceof EasyArray) ? new EasyArray($this->_values[$offset],$this->_options) : $this->_values[$offset]);
        }

    }

    public function set(string $offset, $value): bool{

        //determines if change is allowed
        if(!$this->_change)
            throw new \BadMethodCallException('Data change has been disabled');

        // TODO: Integrate Event Activation

        //determines if the offset/key we are looking for might be nested based on dots
        if(strpos($offset,'.') !== FALSE){

            //separates keys on dots
            $keys = explode('.', $offset);

            //counts the amount of keys
            $keyCount = count($keys);

            //sets reference to main value array
            $ref = &$this->_values;

            //loops keys
            foreach($keys as $key => $keyValue){

                //if key is nothing, happens when there is a double dot or trailing dot, break;
                if($keyValue == '')
                    break;

                //determines if we are on the last key
                if($keyCount === $key+1){
                    $ref[$keyValue] = $value;
                }else{

                    //checks if key is in array
                    $check = $this->_check($ref,$keyValue);

                    //if key is not found or if the keys value isn't an array, sets the value to an new array
                    if(!$check || ($check && !is_array($ref[$keyValue]))) $ref[$keyValue] = array();

                    //changes array into that array
                    $ref = &$ref[$keyValue];
                }

            }

        }else{

            //sets value
            $this->_values[$offset] = $value;

        }

        return $this->sameAs($offset,$value);

    }

    public function exists(string $offset): bool{

        // TODO: Integrate Event Activation

        return $this->_check($this->_values,$offset);
    }

    public function remove(string $offset){

        //determines if change is allowed
        if(!$this->_change)
            throw new \BadMethodCallException('Data change has been disabled');

        // TODO: Integrate Event Activation

        //returns if key isn't found
        if(!$this->_check($this->_values,$offset)) return;

        //determines if the offset/key we are looking for might be nested based on dots
        if(strpos($offset,'.') !== FALSE){

            //creates reference to values array
            $ref = &$this->_values;

            //splits keys into chucks
            $keys = explode('.',$offset);

            //get the last key
            $unsetElementKey = array_pop($keys);

            //counts the remaining keys
            $keyCount = count($keys);

            //function main loop
            foreach($keys as $index => $keyValue){

                //makes sure the there is no key with no name
                if($keyValue == '')
                    break;

                //checks to see if key exists
                $check = $this->_check($ref,$keyValue);

                //checks if is last element (minus the element we popped off in the start)
                if($keyCount === $index+1){

                    //exit function if doesn't key exists
                    if(!$check) break;

                    //changes array into the last array
                    $ref = &$ref[$keyValue];

                    //removes value from array
                    unset($ref[$unsetElementKey]);
                    break;
                }else{

                    //exit function if doesn't key exists
                    if(!$check) break;

                    $ref = &$ref[$keyValue];
                }

            }

        }else{
            //unsets value
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

    public function add(string $offset, $value): bool{

        //check if append is enabled
        if(!$this->_append)
            throw new \BadMethodCallException('Data append has been disabled');

        //unpack the array
        $a = [$offset => $value];
        $this->_recursiveValueUnpacker($a);


        //marge unacpked array with current values in $this->_values
        $this->_values = array_merge_recursive($this->_values,$a);

        //get the offset from the array
        $obj = $this->get($offset);

        //check if the offset is returned as an array and then convert it, if not array just match the added value with retrieved value
        if($obj instanceof IEasyArray){
            $tmpArrayHolder = $obj->asArray();
            $returnValue = ($value === array_pop($tmpArrayHolder));
        }else{
            $returnValue = ($obj === $value);
        }

        //return boolean that tells if it was added successfully
        return $returnValue;

    }

    public function asArray(): array{
        return $this->_values;
    }

    public function merge(array $array, bool $overwrite = TRUE){

        //check if change is allowed
        if($overwrite && !$this->_change)
            throw new \BadMethodCallException('Data change has been disabled');

        //check if append is allowed
        if(!$overwrite && !$this->_append)
            throw new \BadMethodCallException('Data append has been disabled');

        //unpack the arrays values
        $this->_recursiveValueUnpacker($array);

        //determines if it should overwrite or not
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

        //get the information
        $obj = $this->get($offset);

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

        //get the value that is being counted
        $obj = $this->get($offset);

        //count the value and if EasyArray is returned convert it to an array. Potential Exception thrown here if a not countable is returned by the get operation.
        $returnValue = count(($obj instanceof IEasyArray) ? $obj->asArray() : $obj);

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

        //determines if the offset/key we are looking for might be nested based on dots
        if(strpos($key,'.') !== FALSE){

            //splits the keys into an array by the dots
            $keys = explode('.',$key);

            //copies array to search through it
            $copy = $ar;

            //sets default return value
            $re = TRUE;

            foreach($keys as $tKey){

                //if empty key then set return value to FALSE and break loop
                if($tKey == ''){
                    $re = FALSE;
                    break;
                }

                //determines if key exists, if so then replace copied variable with the value else set return value to FALSE and break loop
                if(isset($copy[$tKey]) || @array_key_exists($tKey,$copy)){
                    $copy = $copy[$tKey];
                }else{
                    $re = FALSE;
                    break;
                }

            }

            //return, return value
            return $re;

        }else{
            //return TRUE or FALSE depending on if the key exists in the array
            return (isset($ar[$key]) || @array_key_exists($key,$ar));
        }
    }

    protected function _runEvents(int $type):bool{


    }

    protected function _validateOptions(array $options):array{

        //checks if the SERIALIZER value is set in the options array, if not sets a default SERIALIZER
        if(array_key_exists(EasyArray::SERIALIZER,$options)){

            //checks if the set SERIALIZER value is valid
            if(!($options[EasyArray::SERIALIZER] instanceof ISerializer)) throw new \InvalidArgumentException('Serializer must implement "Sicet7\\EasyArray\\Interfaces\\ISerializer"');

        }else{
            $options[EasyArray::SERIALIZER] = new JsonS(new Serializer(new TokenAnalyzer()));
        }

        //checks if the ALLOWCHANGE value is set in the options array, if not sets a default BOOLEAN
        if(array_key_exists(EasyArray::ALLOWCHANGE,$options)){

            //checks if the set ALLOWCHANGE value is valid
            if(!is_bool($options[EasyArray::ALLOWCHANGE])) throw new \InvalidArgumentException('Change must be a boolean value!');

        }else{
            $options[EasyArray::ALLOWCHANGE] = FALSE;
        }

        //checks if the ALLOWAPPEND value is set in the options array, if not sets a default BOOLEAN
        if(array_key_exists(EasyArray::ALLOWAPPEND,$options)){

            //checks if the set ALLOWAPPEND value is valid
            if(!is_bool($options[EasyArray::ALLOWAPPEND])) throw new \InvalidArgumentException('Append must be a boolean value!');

        }else{
            $options[EasyArray::ALLOWAPPEND] = $options[EasyArray::ALLOWCHANGE];
        }

        //checks if the EVENTSENABLE value is set in the options array, if not sets a default BOOLEAN
        if(array_key_exists(EasyArray::EVENTSENABLE,$options)){

            //checks if the set EVENTSENABLE value is valid
            if(!is_bool($options[EasyArray::EVENTSENABLE])) throw new \InvalidArgumentException('Events must be a boolean value!');

        }else{
            $options[EasyArray::EVENTSENABLE] = FALSE;
        }

        return $options;

    }

    #endregion

}