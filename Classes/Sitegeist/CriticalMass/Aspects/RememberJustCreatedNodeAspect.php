<?php
namespace Sitegeist\CriticalMass\Aspects;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("session")
 * @Flow\Aspect
 */
class RememberJustCreatedNodeAspect
{
	/**
	 * Identifier of the latest created node
	 *
	 * @var string
	 */
	protected $justCreatedNodeIdentifier = '';

	/**
     * @Flow\After("method(TYPO3\Neos\Service\NodeOperations->create())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return string The result of the target method if it has not been intercepted
     */
    public function rememberJustCreatedNode(JoinPointInterface $joinPoint)
	{
		$node = $joinPoint->getResult();
		$this->justCreatedNodeIdentifier = $node->getIdentifier();
	}

	/**
	 * Get the identifier of the latest created node and remove it from the session
	 *
	 * @return string
	 */
	public function getAndFlushJustCreatedNodeIdentifier()
	{
		$result = $this->justCreatedNodeIdentifier;
		$this->justCreatedNodeIdentifier = '';

		return $result;
	}
}
