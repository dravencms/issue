<?php

namespace Dravencms\Issue\Script;

use Dravencms\Model\Admin\Entities\Menu;
use Dravencms\Model\Admin\Repository\MenuRepository;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Model\User\Repository\AclOperationRepository;
use Dravencms\Model\User\Repository\AclResourceRepository;
use Dravencms\Packager\IPackage;
use Dravencms\Packager\IScript;
use Kdyby\Doctrine\EntityManager;

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
class PostInstall implements IScript
{
    /** @var MenuRepository */
    private $menuRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var AclResourceRepository */
    private $aclResourceRepository;

    /** @var AclOperationRepository */
    private $aclOperationRepository;

    /**
     * PostInstall constructor.
     * @param MenuRepository $menuRepository
     * @param EntityManager $entityManager
     * @param AclResourceRepository $aclResourceRepository
     * @param AclOperationRepository $aclOperationRepository
     */
    public function __construct(MenuRepository $menuRepository, EntityManager $entityManager, AclResourceRepository $aclResourceRepository, AclOperationRepository $aclOperationRepository)
    {
        $this->menuRepository = $menuRepository;
        $this->entityManager = $entityManager;
        $this->aclResourceRepository = $aclResourceRepository;
        $this->aclOperationRepository = $aclOperationRepository;
    }

    /**
     * @param IPackage $package
     */
    public function run(IPackage $package)
    {
        if (!$aclResource = $this->aclResourceRepository->getOneByName('issue')) {
            $aclResource = new AclResource('issue', 'Issue');

            $this->entityManager->persist($aclResource);
        }

        if (!$aclOperationEdit = $this->aclOperationRepository->getOneByName('edit')) {
            $aclOperationEdit = new AclOperation($aclResource, 'edit', 'Allows editation of Issue');
            $this->entityManager->persist($aclOperationEdit);
        }

        if (!$aclOperationDelete = $this->aclOperationRepository->getOneByName('delete')) {
            $aclOperationDelete = new AclOperation($aclResource, 'delete', 'Allows deletion of Issue');
            $this->entityManager->persist($aclOperationDelete);
        }

        if (!$this->menuRepository->getOneByPresenter(':Admin:Issue:Issue')) {
            $adminMenuRoot = new Menu('Issues', ':Admin:Issue:Issue', 'fa-bug', $aclOperationEdit);
            $this->entityManager->persist($adminMenuRoot);
        }

    }
}