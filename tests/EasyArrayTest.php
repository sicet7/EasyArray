<?php
/**
 * Created by PhpStorm.
 * User: sicet7
 * Date: 3/21/17
 * Time: 12:40 PM
 */

namespace Sicet7\Tests\EasyArray;


use Sicet7\EasyArray\EasyArray as EA;
use PHPUnit\Framework\TestCase;
use Sicet7\EasyArray\Interfaces\IEasyArray;
use Sicet7\EasyArray\JsonSerializer;
use SuperClosure\Analyzer\TokenAnalyzer;
use SuperClosure\Serializer;


class EasyArrayTest extends TestCase{

    use PHPUnit_TestCase_Docs;

    protected
        $easyarray = null,
        $conf = array();

    protected function setUp(){

        $obj = new \stdClass();

        $obj->dummy = 'hello';

        $this->conf = [
            EA::ALLOWCHANGE => TRUE,
            EA::ALLOWAPPEND => TRUE,
            EA::SERIALIZER => new JsonSerializer(
                (new Serializer(
                    new TokenAnalyzer()
                ))
            ),
            EA::EVENTSENABLE => TRUE
        ];

        $this->easyarray = new EA([
            'testNumber' => 134,
            'test' => [
                'data' => [
                    'numbers' => [
                        'one' => 1,
                        'two' => 2,
                        'three' => 3,
                        'four' => 4,
                        'five' => 5,
                        'six' => 6,
                        'seven' => 7,
                        'eight' => 8,
                        'nine' => 9,
                        'zero' => 0
                    ],
                    'functions' => [
                        'hello' => function($string){
                            return 'hello '.$string;
                        },
                        'it' => function($string){
                            return $string.' it!';
                        },
                        'isInt' => function($var){
                            return is_integer($var);
                        }
                    ]
                ]
            ],
        'test.data.numbers.two' => 1,// This will be unpacked and overwrite. The latest definition of a value will be the saved one
        'test.object.one' => new \stdClass(),
        'test.object.two' => $obj
        ],$this->conf);
    }


    /** @test */
    public function normal_get(){

        $this->assertEquals(134,$this->easyarray->get('testNumber'));

        $this->assertInstanceOf(IEasyArray::class,$this->easyarray->get('test'));

    }

    /**
     * @test
     * @depends normal_get
     */
    public function nested_get(){

        $this->assertInstanceOf(IEasyArray::class,$this->easyarray->get('test.data'));

        $this->assertEquals(1,$this->easyarray->get('test.data.numbers.one'));

        $this->assertEquals(1,$this->easyarray->get('test.data.numbers.two'));

        $this->assertEquals('hello',$this->easyarray->get('test.object.two.dummy'));

    }

    /**
     * @test
     * @depends nested_get
     */
    public function same_as(){

        $this->assertTrue($this->easyarray->sameAs('test.data.numbers', ['one' => 1,
            'two' => 1,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'zero' => 0]));

        $this->assertTrue($this->easyarray->sameAs('test.data.numbers.one',1));

        $this->assertFalse($this->easyarray->sameAs('test.object.two',new \stdClass()));

        $this->assertTrue($this->easyarray->sameAs('test.object.one',new \stdClass()));

    }

    /**
     * @test
     * @depends same_as
     */
    public function normal_set(){

        $this->assertTrue($this->easyarray->set('hi',1337));

        $this->assertTrue($this->easyarray->set('test',new \stdClass()));

    }
    /**
     * @test
     * @depends normal_set
     */
    public function nested_set(){

        $this->assertTrue($this->easyarray->set('how.are.you.doing','good'));

        $this->assertTrue($this->easyarray->set('i.want.to.set.a.object',new \stdClass()));

    }

    /** @test */
    public function normal_check(){

        $this->assertTrue($this->easyarray->exists('test'));

        $this->assertFalse($this->easyarray->exists('test2'));

    }
    /** @test */
    public function nested_check(){

        $this->assertTrue($this->easyarray->exists('test.data.numbers.one'));

        $this->assertFalse($this->easyarray->exists('test.data.numbers.eleven'));

    }

    /**
     * @test
     * @depends normal_check
     */
    public function normal_delete(){

        $this->assertTrue($this->easyarray->exists('test'));

        $this->easyarray->remove('test');

        $this->assertFalse($this->easyarray->exists('test'));

    }

    /**
     * @test
     * @depends nested_check
     */
    public function nested_delete(){

        $this->assertTrue($this->easyarray->exists('test.data.numbers.one'));

        $this->easyarray->remove('test.data.numbers.one');

        $this->assertFalse($this->easyarray->exists('test.data.numbers.one'));

    }

    /** @test */
    public function get_iterator(){

        $this->assertInstanceOf(\ArrayIterator::class,$this->easyarray->getIterator());

    }

    /** @test */
    public function get_serializer(){

        $this->assertInstanceOf(JsonSerializer::class,$this->easyarray->getSerializer());

    }

    /**
     * @test
     * @depends same_as
     */
    public function merge(){

        $this->easyarray->merge([
            'test.data.numbers.one' => 5,
            'test.data.numbers.eleven' => 11,
        ]);

        $this->assertTrue($this->easyarray->sameAs('test.data.numbers',['one' => 5,
            'two' => 1,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'zero' => 0,
            'eleven' => 11]));

        $this->easyarray->merge([
            'test.data.numbers.one' => 1
        ],false);

        $this->assertInstanceOf(IEasyArray::class,$this->easyarray->get('test.data.numbers.one'));

        $this->assertEquals(5,$this->easyarray->get('test.data.numbers.one.0'));

        $this->assertEquals(1,$this->easyarray->get('test.data.numbers.one.1'));

    }

    /** @test */
    public function add(){

        $this->easyarray->add('test.data.numbers.one',12);

        $this->assertInstanceOf(IEasyArray::class,$this->easyarray->get('test.data.numbers.one'));

    }

    /** @test */
    public function is_serializable(){

        $this->assertInternalType('string',$this->easyarray->serialize());

    }

    /**
     * @test
     * @depends is_serializable
     */
    public function is_unserializable(){

        $ser = $this->easyarray->serialize();
        $obj = (new EA())->unserialize($ser);

        $this->assertEquals(1,$obj->get('test.data.numbers.two'));

        $this->assertEquals('hello world!',$obj->get('test.data.functions.hello')('world!'));

    }


}
