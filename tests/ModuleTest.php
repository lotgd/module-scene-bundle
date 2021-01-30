<?php
declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use LotGD\Core\Configuration;
use LotGD\Core\Game;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Tests\ModelTestCase;
use Symfony\Component\Yaml\Yaml;

use LotGD\Module\SceneBundle\Module;

class ModuleTest extends ModelTestCase
{
    const Library = 'lotgd/module-scene-bundle';

    public $g;
    private $moduleModel;

    public function getDataSet(): array
    {
        return Yaml::parseFile(implode(DIRECTORY_SEPARATOR, [__DIR__, 'datasets', 'module.yml']));
    }

    public function setUp(): void
    {
        parent::setUp();

        // Register and unregister before/after each test, since
        // handleEvent() calls may expect the module be registered (for example,
        // if they read properties from the model).
        $this->moduleModel = new ModuleModel(self::Library);
        $this->moduleModel->save($this->getEntityManager());
        Module::onRegister($this->g, $this->moduleModel);

        $this->g->getEntityManager()->flush();
        $this->g->getEntityManager()->clear();
    }

    public function tearDown(): void
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        Module::onUnregister($this->g, $this->moduleModel);

        $m = $this->getEntityManager()->getRepository(ModuleModel::class)->find(self::Library);
        if ($m) {
            $m->delete($this->getEntityManager());
        }

        $this->getEntityManager()->clear();

        parent::tearDown();
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

        $this->assertDataWasKeptIntact($this->getDataSet(), $this->getConnection()[0], ['characters', 'scenes', 'modules']);

        // Since tearDown() contains an onUnregister() call, this also tests
        // double-unregistering, which should be properly supported by modules.
    }

    public function testHandleUnknownEvent()
    {
        // Always good to test a non-existing event just to make sure nothing happens :).
        $context = new \LotGD\Core\Events\EventContext(
            "e/lotgd/tests/unknown-event",
            "none",
            \LotGD\Core\Events\EventContextData::create([])
        );

        $newContext = Module::handleEvent($this->g, $context);

        $this->assertSame($context, $newContext);
    }
}
