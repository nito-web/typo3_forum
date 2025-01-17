<?php
namespace Mittwald\Typo3Forum\Domain\Model\Forum;

use Mittwald\Typo3Forum\Domain\Model\User\FrontendUser;
use Mittwald\Typo3Forum\Domain\Repository\Forum\ForumRepository;
/*                                                                      *
 *  COPYRIGHT NOTICE                                                    *
 *                                                                      *
 *  (c) 2015 Mittwald CM Service GmbH & Co KG                           *
 *           All rights reserved                                        *
 *                                                                      *
 *  This script is part of the TYPO3 project. The TYPO3 project is      *
 *  free software; you can redistribute it and/or modify                *
 *  it under the terms of the GNU General public License as published   *
 *  by the Free Software Foundation; either version 2 of the License,   *
 *  or (at your option) any later version.                              *
 *                                                                      *
 *  The GNU General public License can be found at                      *
 *  http://www.gnu.org/copyleft/gpl.html.                               *
 *                                                                      *
 *  This script is distributed in the hope that it will be useful,      *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of      *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       *
 *  GNU General public License for more details.                        *
 *                                                                      *
 *  This copyright notice MUST APPEAR in all copies of the script!      *
 *                                                                      */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A virtual root forum.
 * This class models a virtual root forum that is the parent forum of all
 * other forums.
 */
class RootForum extends Forum implements SingletonInterface
{
    protected ForumRepository $forumRepository;

    public function __construct(
        ForumRepository $forumRepository
    ) {
        $this->uid = 0;

        $this->forumRepository = $forumRepository;
    }

    public function getChildren(): ObjectStorage
    {
        return $this->forumRepository->findRootForums();
    }

    public function checkAccess(FrontendUser $user = null, $accessType = Access::TYPE_READ)
    {
        return $accessType === Access::TYPE_READ;
    }
}
