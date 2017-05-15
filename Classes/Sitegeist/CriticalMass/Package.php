<?php

namespace Sitegeist\CriticalMass;

use Neos\Flow\Package\Package as BasePackage;

/**
 * The Sitegeist\CriticalMass
 */
class Package extends BasePackage
{
    public function boot(\Neos\Flow\Core\Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(
            'Neos\ContentRepository\Domain\Model\Node',
            'nodeCreated',
            'Sitegeist\CriticalMass\Hooks\ContentRepositoryHooks',
            'nodeCreated'
        );
        $dispatcher->connect(
            'Neos\ContentRepository\Domain\Model\Node',
            'nodeUpdated',
            'Sitegeist\CriticalMass\Hooks\ContentRepositoryHooks',
            'nodeUpdated'
        );
    }
}
