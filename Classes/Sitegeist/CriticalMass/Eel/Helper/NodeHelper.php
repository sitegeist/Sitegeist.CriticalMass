<?php
namespace Sitegeist\CriticalMass\Eel\Helper;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use Sitegeist\CriticalMass\Aspects\RememberJustCreatedNodeAspect;

/**
 * Eel helper for node handling
 */
class NodeHelper implements ProtectedContextAwareInterface
{
	/**
	 * @Flow\Inject
	 * @var RememberJustCreatedNodeAspect
	 */
	protected $rememberJustCreatedNodeAspect;

	/**
	 * Identifier of the latest created node
	 *
	 * @var string
	 */
	protected $justCreatedNodeIdentifier = null;

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
	 * Check whether the given node has just been created
	 *
	 * @param NodeInterface $node
	 * @return boolean
	 */
	public function hasNodeJustBeenCreated(NodeInterface $node)
	{
		if ($this->justCreatedNodeIdentifier === null) {
			$this->justCreatedNodeIdentifier = $this->rememberJustCreatedNodeAspect
				->getAndFlushJustCreatedNodeIdentifier();
		}

		return $node->getIdentifier() === $this->justCreatedNodeIdentifier;
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
