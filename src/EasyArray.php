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

        $this->_values = $this->_recursiveValueUnpacker($values);

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

        if(!$this->_append)
            throw new \BadMethodCallException("Data append has been disabled");

        $values = $this->_recursiveValueUnpacker([$offset => $value]);

        $this->_values = array_merge_recursive($this->_values,$values);

        $default = $this->_executeEvents;
        $this->_executeEvents = FALSE;

        $obj = $this->get($offset);

        if($obj instanceof IEasyArray){
            $tmpArrayHolder = $obj->asArray();
            $returnValue = ($value === array_pop($tmpArrayHolder));
        }else{
            $returnValue = ($obj === $value);
        }

        $this->_executeEvents = $default;
        return $returnValue;

    }

    public function asArray(): array{
        return $this->_values;
    }

    public function merge(array $array, bool $overwrite = TRUE){

        if($overwrite && !$this->_change)
            throw new \BadMethodCallException("Data change has been disabled");

        if(!$overwrite && !$this->_append)
            throw new \BadMethodCallException("Data append has been disabled");

        $values = $this->_recursiveValueUnpacker($array);

        if($overwrite){
            $this->_values = array_replace_recursive($this->_values,$values);
        }else{
            $this->_values = array_merge_recursive($this->_values,$values);
        }

    }

    public function getSerializer(): ISerializer{
        return $this->__serializerInstance;
    }

    public function sameAs(string $offset, $value):bool{

        $default = $this->_executeEvents;//store start state

        $this->_executeEvents = FALSE;//disable events

        $returnValue = FALSE;
        $type = gettype($value);
        $obj = $this->get($offset);

        if(($type !== 'array' && $type !== 'object') && ($type !== gettype($obj))) return false;

        switch ($type){
            default:
                if($obj !== $value)
                    break;
                $returnValue = TRUE;
            break;
            case 'array':

                if(!is_object($obj))
                    break;

                if(!is_subclass_of($obj,IEasyArray::class))
                    break;

                $match = $obj->asArray();

                if(count($match) !== count($value))
                    break;
                $tmpValue = $value;

                ksort($match);
                ksort($tmpValue);

                if($match !== $tmpValue)
                    break;

                $returnValue = TRUE;

            break;
            case 'object':

                 if(!is_object($obj))
                     break;

                 if(get_class($obj) !== get_class($value))
                     break;

                 $nameAsKey = function(array $value,$obj = NULL){
                     $ar = [];
                     foreach($value as $v){

                         if($v instanceof \ReflectionMethod)
                             $ar[$v->getName()] = $v->getClosure();

                         if($v instanceof \ReflectionProperty)
                             $ar[$v->getName()] = $v->getValue($obj);

                     }
                     return $ar;
                 };

                 $match = new \ReflectionObject($obj);
                 $reflectValue = new \ReflectionObject($value);

                 if($this->_ksort($match->getConstants()) !== $this->_ksort($reflectValue->getConstants()))
                     break;

                 if($this->_ksort($nameAsKey($match->getProperties(),$obj)) !== $this->_ksort($nameAsKey($reflectValue->getProperties(),$value)))
                     break;

                 if($this->_ksort($nameAsKey($match->getMethods())) !== $this->_ksort($nameAsKey($reflectValue->getMethods())))
                     break;

                 $returnValue = TRUE;

            break;
        }

        $this->_executeEvents = $default;
        return $returnValue;

    }

    public function count(string $offset = NULL):int{

        if(is_null($offset)){
            return count($this->_values);
        }

        $default = $this->_executeEvents;
        $this->_executeEvents = FALSE;
        $obj = $this->get($offset);
        $returnValue = count(($obj instanceof IEasyArray) ? $obj->asArray() : $obj);
        $this->_executeEvents = $default;
        return $returnValue;

    }

    #endregion

    #region IteratorAggregate

    public function getIterator():ArrayIterator{
        return new ArrayIterator($this->_values);
    }

    #endregion

    #region Protected Methods

    //TODO : optimize with reference instead

    protected function _recursiveValueUnpacker(array $array):array{

        $a = [];

        foreach($array as $k => $v){

            if(strpos($k,'.') !== FALSE){
                $keys = explode('.',$k);
                $keyCount = count($keys);

                foreach($keys as $index => $keyValue){

                    if(!isset($ref))
                        $ref = &$a;

                    if($keyCount === $index+1){
                        $ref[$keyValue] = (is_array($v) ? $this->_recursiveValueUnpacker($v) : $v);
                        unset($ref);
                    }else{
                        $check = $this->_check($ref,$keyValue);
                        if(!$check || ($check && !is_array($ref[$keyValue])))
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

    protected function _ksort(array $a):array{

        ksort($a);
        return $a;

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