<?php
namespace Sitegeist\CriticalMass\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Sitegeist\CriticalMass\Aspects\RememberJustModifiedNodeAspect;

/**
 * Eel helper for node handling
 */
class NodeHelper implements ProtectedContextAwareInterface
{
	/**
	 * @Flow\Inject
	 * @var RememberJustModifiedNodeAspect
	 */
	protected $rememberJustModifiedNodeAspect;

	/**
	 * Identifier of the latest modified node
	 *
	 * @var string
	 */
	protected $justModifiedNodeIdentifier = null;

	/**
     * @var array
     * @Flow\InjectConfiguration("automaticNodeHierarchy")
     */
    protected $automaticNodeHierarchyConfigurations;

	/**
	 * Check if there is a configuration for automatic hierarchy generation for the node type
	 * of the given node
	 *
	 * @param NodeInterface $node
	 * @return boolean
	 */
	public function isHierarchyGenerationConfiguredForNode(NodeInterface $node)
	{
		foreach (array_keys($this->automaticNodeHierarchyConfigurations) as $configuredNodeType) {
			if ($node->getNodeType()->isOfType($configuredNodeType)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether the given node has just been modified
	 *
	 * @param NodeInterface $node
	 * @return boolean
	 */
	public function hasNodeJustBeenModified(NodeInterface $node)
	{
		if ($this->justModifiedNodeIdentifier === null) {
			$this->justModifiedNodeIdentifier = $this->rememberJustModifiedNodeAspect
				->getAndFlushJustModifiedNodeIdentifier();
		}

		return $node->getIdentifier() === $this->justModifiedNodeIdentifier;
	}

	/**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
	{
        return true;
    }
}
