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


class EasyArrayTest extends TestCase{

    use PHPUnit_TestCase_Docs;

    protected $easyarray = null;

    protected function setUp(){
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
        'test.data.numbers.two' => 1// This will be unpacked and overwrite. The latest definition of a value will be the saved one
        ],true);
    }

    /** @test */
    public function normal_get(){

        $this->assertEquals(134,$this->easyarray->get('testNumber'));

        $this->assertInstanceOf(EA::class,$this->easyarray->get('test'));

    }

    /** @test */
    public function nested_get(){

        $this->assertInstanceOf(EA::class,$this->easyarray->get('test.data'));

        $this->assertEquals(1,$this->easyarray->get('test.data.numbers.one'));

        $this->assertEquals(1,$this->easyarray->get('test.data.numbers.two'));

    }

}
