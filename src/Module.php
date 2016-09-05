<?php
declare(strict_types=1);

namespace LotGD\Module\SceneBundle;

use LotGD\Core\Game;
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

    public static function handleEvent(Game $g, string $event, array &$context)
    {

    }

    private static function getBaseScene($template)
    {
        switch($template) {
            case self::SceneTemplates[0]:
                $pondScene = Scene::create([
                    "template" => self::SceneTemplates[0],
                    "title" => "The Pond",
                    "description" => "A bit outside of the village, near the border to the dark deep forest, "
                        . "there is a place simply called «The Pond». It's name-giver, a small pond with a beautiful, "
                        . "blue-green colour, is in it's centre, enclosed by meadow. After seeing this place, everyone "
                        . "nows immediatly why this place is liked among lovers. A bit more separate, an old oak tree"
                        . "stands mighty.",
                ]);

                $oakScene = Scene::create([
                    "template" => self::SceneTemplates[1],
                    "title" => "The old oak",
                    "description" => "The old oaken tree, the oldest tree inside of the village and one of the few that "
                        . "didn't fell victim to the woodcutter, has a lot of old and fresh hearts cut inside it's bork, "
                    . "witness to the numerous lover's that wanted their love to last as long as this tree does.",
                ]);

                $oakScene->setParent($pondScene);

                return $pondScene;
        }

        return null;
    }

    public static function onRegister(Game $g, ModuleModel $module)
    {
        $villageScenes = $g->getEntityManager()->getRepository(Scene::class)
            ->findBy(["template" => VillageModule::VillageScene]);

        foreach ($villageScenes as $villageScene) {
            foreach (self::SceneTemplates as $template) {
                $scene = self::getBaseScene($template);
                if ($scene !== null) {
                    $scene->setParent($villageScene);
                }
            }
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
