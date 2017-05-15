<?php
namespace Sitegeist\CriticalMass\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("session")
 * @Flow\Aspect
 */
class RememberJustModifiedNodeAspect
{
    /**
     * Identifier of the latest modified node
     *
     * @var string
     */
    protected $justModifiedNodeIdentifier = '';

    /**
     * @Flow\After("method(Sitegeist\CriticalMass\Hooks\ContentRepositoryHooks->node(Created|Updated)())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     */
    public function rememberJustModifiedNode(JoinPointInterface $joinPoint)
    {
        $node = $joinPoint->getMethodArgument('node');
        $this->justModifiedNodeIdentifier = $node->getIdentifier();
    }

    /**
     * Get the identifier of the latest modified node and remove it from the session
     *
     * @return string
     */
    public function getAndFlushJustModifiedNodeIdentifier()
    {
        $result = $this->justModifiedNodeIdentifier;
        $this->justModifiedNodeIdentifier = '';

        return $result;
    }
}
