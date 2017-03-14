<?php
declare(strict_types=1);

namespace LotGD\Module\SceneBundle;

use Doctrine\Common\Collections\ArrayCollection;

use LotGD\Core\Game;
use LotGD\Core\Models\SceneConnectionGroup;
use LotGD\Core\Models\SceneConnectable;
use LotGD\Core\Module as ModuleInterface;
use LotGD\Core\Models\Module as ModuleModel;
use LotGD\Core\Models\Scene;
use LotGD\Module\Village\Module as VillageModule;

class Module implements ModuleInterface {
    const ModuleIdentifier = "lotgd/module-scene-bundle";
    const SceneTemplates = [
        "lotgd/module-scene-bundle/pond",
        "lotgd/module-scene-bundle/pond/oak",
    ];

    const Groups = [
        "pond" => [
            "lotgd/module-scene-bundle/pond/the-pond",
            "lotgd/module-scene-bundle/pond/back"
        ],
        "oak" => [
            "lotgd/module-scene-bundle/oak/back",
        ]
    ];

    public static function handleEvent(Game $g, string $event, array &$context)
    {

    }

    private static function getBaseScene(): array
    {
        $pondScene = Scene::create([
            "template" => self::SceneTemplates[0],
            "title" => "The Pond",
            "description" => "A bit outside of the village, near the border to the dark deep forest, "
                . "there is a place simply called «The Pond». It's name-giver, a small pond with a beautiful, "
                . "blue-green colour, is in it's centre, enclosed by meadow. After seeing this place, everyone "
                . "nows immediatly why this place is liked among lovers. A bit more separate, an old oak tree"
                . "stands mighty.",
        ]);

        $pondScene->addConnectionGroup(new SceneConnectionGroup(self::Groups["pond"][0], "The Pond"));
        $pondScene->addConnectionGroup(new SceneConnectionGroup(self::Groups["pond"][1], "Back"));

        $oakScene = Scene::create([
            "template" => self::SceneTemplates[1],
            "title" => "The old oak",
            "description" => "The old oaken tree, the oldest tree inside of the village and one of the few that "
                . "didn't fell victim to the woodcutter, has a lot of old and fresh hearts cut inside it's bork, "
            . "witness to the numerous lover's that wanted their love to last as long as this tree does.",
        ]);

        $oakScene->addConnectionGroup(new SceneConnectionGroup(self::Groups["oak"][0], "Back"));

        $pondScene
            ->getConnectionGroup(self::Groups["pond"][0])
            ->connect(
                $oakScene->getConnectionGroup(self::Groups["oak"][0])
            );

        return [$pondScene, $oakScene];
    }

    public static function onRegister(Game $g, ModuleModel $module)
    {
        $villageScenes = $g->getEntityManager()->getRepository(Scene::class)
            ->findBy(["template" => VillageModule::VillageScene]);

        foreach ($villageScenes as $villageScene) {
            [$pondScene, $oakScene] = self::getBaseScene();

            // Connect the pond to the village
            if ($villageScene->hasConnectionGroup(VillageModule::Groups[1])) {
                $villageScene
                    ->getConnectionGroup(VillageModule::Groups[1])
                    ->connect($pondScene->getConnectionGroup(self::Groups["pond"][1]));
            } else {
                $villageScene->connect($pondScene->getConnectionGroup(self::Groups["pond"][1]));
            }

            // connect the oak to the village in one direction only.
            $oakScene
                ->getConnectionGroup(self::Groups["oak"][0])
                ->connect($villageScene, SceneConnectable::Unidirectional);

            // mark for saving, but don't save.
            $g->getEntityManager()->persist($pondScene);
            $g->getEntityManager()->persist($oakScene);
        }

        $g->getEntityManager()->flush();
    }

    public static function onUnregister(Game $g, ModuleModel $module)
    {
        foreach (self::SceneTemplates as $template) {
            $scenes = $g->getEntityManager()->getRepository(Scene::class)
                ->findBy(["template" => $template]);

            foreach($scenes as $scene) {
                $g->getEntityManager()->remove($scene);
            }
        }

        $g->getEntityManager()->flush();
    }
}
