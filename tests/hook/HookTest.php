<?php
namespace Jepsonwu\Hook;

use PHPUnit\Framework\TestCase;

require_once "MyCollection.php";

/**
 * Created by PhpStorm.
 * User: jepsonwu
 * Date: 2017/2/14
 * Time: 11:04
 */
class HookTest extends TestCase
{
    public function testRun()
    {
        $this->registerPersonInfoHook();

        $result = "man";
        $result = Scheduler::run(MyCollection::$hook['person']['info'], $result);
        $this->assertTrue($result == "this is person infomation:name:jepson,age:26,sex:man", "hook run failed,result:man!");

        $result = false;
        $result = Scheduler::run(MyCollection::$hook['person']['info'], $result);
        $this->assertTrue($result === false, "hook run failed,result:false");

        $result = null;
        $result = Scheduler::run(MyCollection::$hook['person']['info'], $result);
        $this->assertTrue($result == "this is person infomation:name:jepson,age:26", "hook run failed,result:null");
    }

    public function testExec()
    {
        $this->registerPersonSexHook();
        $this->registerPersonInfoHook();

        Scheduler::registerCollection(new MyCollection());
        $result = "man";
        $result = Scheduler::exec($result);
        $this->assertTrue($result == "this is person infomation:name:jepson,age:26,sex:1", "hook exec failed,result:man");
    }

    public function registerPersonSexHook()
    {
        $hook = new Hook(function ($sex) {
            return $sex == "man" ? 1 : 0;
        });
        Scheduler::register(MyCollection::$hook['person']['sex'], $hook);
    }

    public function registerPersonInfoHook()
    {
        $hook = new Hook(function ($name, $age, $sex = null) {
            return "this is person infomation:name:{$name},age:{$age}" . (is_null($sex) ? "" : ",sex:{$sex}");
        });
        $hook['name'] = "jepson";
        $hook["age"] = 26;

        Scheduler::register(MyCollection::$hook['person']['info'], $hook);
    }
}