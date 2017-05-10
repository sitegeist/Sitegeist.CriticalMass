<?php
namespace Sitegeist\CriticalMass\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\Utility as EelUtility;
use Neos\Eel\CompilingEvaluator;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * @Flow\Scope("singleton")
 */
class NodeSortingService
{
	/**
     * @Flow\Inject(lazy=FALSE)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

	/**
     * @Flow\InjectConfiguration(path="defaultContext", package="TYPO3.TypoScript")
     * @var array
     */
    protected $defaultTypoScriptContextConfiguration;

	/**
	 * @param NodeInterface $node
	 * @param string $eelExpression
	 * @param string $nodeTypeFilter
	 * @return void
	 */
	public function sortChildNodesByEelExpression(
		NodeInterface $node,
		$eelExpression,
		$nodeTypeFilter = 'Neos.Neos:Document'
	) {
		$nodes = $node->getChildNodes($nodeTypeFilter);

		foreach ($nodes as $subject) {

			$object = null;
			foreach ($nodes as $node) {
				if ($this->sortingConditionApplies($subject, $node, $eelExpression)) {
					$object = $node;
					break;
				}
			}

			if ($object) {
				$subject->moveBefore($object);
			}
		}
	}

	/**
	 * @param NodeInterface $a
	 * @param NodeInterface $b
	 * @param string $eelExpression
	 * @return boolean
	 */
	protected function sortingConditionApplies(NodeInterface $a, NodeInterface $b, $eelExpression)
	{
		return EelUtility::evaluateEelExpression(
			$eelExpression,
			$this->eelEvaluator,
			['a' => $a, 'b' => $b],
			$this->defaultTypoScriptContextConfiguration
		);
	}
}
