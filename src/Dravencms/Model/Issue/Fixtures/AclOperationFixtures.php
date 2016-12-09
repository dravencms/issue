<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\Issue\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class AclOperationFixtures extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $operations = [
            'issue' => [
                'edit' => 'Umožnuje editaci issue',
                'delete' => 'Umožnuje smazání issue',
            ]
        ];
        foreach ($operations AS $resourceName => $operationList)
        {
            /** @var AclResource $aclResource */
            $aclResource = $this->getReference('user-acl-resource-'.$resourceName);
            foreach ($operationList AS $operationName => $operationDescription)
            {
                $aclOperation = new AclOperation($aclResource, $operationName, $operationDescription);
                //Allow all operations to administrator group
                $aclOperation->addGroup($this->getReference('user-group-administrator'));
                $manager->persist($aclOperation);
                $this->addReference('user-acl-operation-'.$resourceName.'-'.$operationName, $aclOperation);
            }
        }
        $manager->flush();
    }
    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getDependencies()
    {
        return ['Dravencms\Model\Issue\Fixtures\AclResourceFixtures', 'Dravencms\Model\User\Fixtures\GroupFixtures'];
    }
}