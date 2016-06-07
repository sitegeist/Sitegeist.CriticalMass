<?php

namespace Sitegeist\CriticalMass;

use TYPO3\Flow\Package\Package as BasePackage;


/**
 * The Sitegeist\CriticalMass
 */
class Package extends BasePackage {
    public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeCreated', 'Sitegeist\CriticalMass\Hooks\ContentRepositoryHooks', 'nodeCreated');
        $dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodeUpdated', 'Sitegeist\CriticalMass\Hooks\ContentRepositoryHooks', 'nodeUpdated');
    }
}