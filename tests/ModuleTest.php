<?php
declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use LotGD\Core\Configuration;
use LotGD\Core\Game;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Tests\ModelTestCase;

use LotGD\Module\SceneBundle\Module;

class ModuleTest extends ModelTestCase
{
    const Library = 'lotgd/module-scene-bundle';

    private $g;
    private $moduleModel;

    protected function getDataSet(): \PHPUnit_Extensions_Database_DataSet_YamlDataSet
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', 'module.yml']));
    }

    public function setUp()
    {
        parent::setUp();

        // Make an empty logger for these tests. Feel free to change this
        // to place log messages somewhere you can easily find them.
        $logger  = new Logger('test');
        $logger->pushHandler(new NullHandler());

        // Create a Game object for use in these tests.
        $this->g = new Game(new Configuration(getenv('LOTGD_TESTS_CONFIG_PATH')), $logger, $this->getEntityManager(), implode(DIRECTORY_SEPARATOR, [__DIR__, '..']));

        // Register and unregister before/after each test, since
        // handleEvent() calls may expect the module be registered (for example,
        // if they read properties from the model).
        $this->moduleModel = new ModuleModel(self::Library);
        $this->moduleModel->save($this->getEntityManager());
        Module::onRegister($this->g, $this->moduleModel);
    }

    public function tearDown()
    {
        parent::tearDown();

        Module::onUnregister($this->g, $this->moduleModel);
        $m = $this->getEntityManager()->getRepository(ModuleModel::class)->find(self::Library);
        if ($m) {
            $m->delete($this->getEntityManager());
        }
    }

    // TODO for LotGD staff: this test assumes the schema in their yaml file
    // reflects all columns in the core's models of characters, scenes and modules.
    // This is pretty fragile since every time we add a column, everyone's tests
    // will break.
    public function testUnregister()
    {
        Module::onUnregister($this->g, $this->moduleModel);
        $m = $this->getEntityManager()->getRepository(ModuleModel::class)->find(self::Library);
        $m->delete($this->getEntityManager());

        // Assert that databases are the same before and after.
        // TODO for module author: update list of tables below to include the
        // tables you modify during registration/unregistration.
        $after = $this->getConnection()->createDataSet(['characters', 'scenes', 'modules']);
        $before = $this->getDataSet();

        $this->assertDataSetsEqual($before, $after);

        // Since tearDown() contains an onUnregister() call, this also tests
        // double-unregistering, which should be properly supported by modules.
    }

    public function testHandleUnknownEvent()
    {
        // Always good to test a non-existing event just to make sure nothing happens :).
        $context = [];
        Module::handleEvent($this->g, 'e/lotgd/tests/unknown-event', $context);
    }
}
