<?php

namespace Sitegeist\CriticalMass\Hooks;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

use TYPO3\Eel\Utility;
use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Eel\FlowQuery\FlowQuery;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryHooks {

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject(lazy=FALSE)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @var array
     * @Flow\InjectConfiguration("automaticNodeHirarchy")
     */
    protected $automaticNodeHirarchyConfigurations;

    /**
     * @param NodeInterface $node
     */
    public function nodeCreated (NodeInterface $node) {
        $this->handleAutomaticHirarchiesForNewNodes($node);
    }

    /**
     * @param NodeInterface $node
     */
    public function nodeUpdated (NodeInterface $node) {
        $this->handleAutomaticHirarchiesForNewNodes($node);
    }


    /**
     * @param NodeInterface $node
     */
    protected function handleAutomaticHirarchiesForNewNodes (NodeInterface $node) {

        if (is_array($this->automaticNodeHirarchyConfigurations)) {
            foreach ($this->automaticNodeHirarchyConfigurations as $nodeType => $hirarchyConfiguration) {
                $this->handleAutomaticHirarchyForNodeType($nodeType, $hirarchyConfiguration, $node);
            }
        }
    }

    /**
     * @param $nodeType
     * @param $hirarchyConfiguration
     * @param NodeInterface $node
     */
    protected function handleAutomaticHirarchyForNodeType($nodeType, $hirarchyConfiguration, NodeInterface $node) {
        if ($node->getNodeType()->getName() === $nodeType) {
            // find collection root
            $collectionRoot = Utility::evaluateEelExpression($hirarchyConfiguration['root'], $this->eelEvaluator, array('node' => $node));
            if ($collectionRoot && $collectionRoot instanceof NodeInterface) {
                $collectionNode = $collectionRoot;

                // traverse path and move node
                foreach ($hirarchyConfiguration['path'] as $pathItem) {
                    $expectedNodeProperties = array();
                    foreach ($pathItem['properties'] as $propertyName => $propertyEelExpression) {
                        $expectedNodeProperties[$propertyName] = Utility::evaluateEelExpression($propertyEelExpression, $this->eelEvaluator, array('node' => $node));
                    }

                    xdebug_break();

                    if (!$expectedNodeProperties['title'] && !$expectedNodeProperties['uriPathSegment']) {
                        continue;
                    }

                    // find next path collectionNodes

                    $flowQuery = new FlowQuery(array($collectionNode));
                    $flowQuery = $flowQuery->children('[instanceof ' . $pathItem['type'] . ']');
                    foreach ($expectedNodeProperties as $expectedPropertyName => $expectedPropertyValue) {
                        $flowQuery = $flowQuery->filter('[' . $expectedPropertyName . '="' . $expectedPropertyValue . '"]');
                    }
                    $nextCollectionNode = $flowQuery->get(0);

                    // create missing collectionNodes

                    if (!$nextCollectionNode) {
                        $nextCollectionNodeType = $this->nodeTypeManager->getNodeType($pathItem['type']);
                        $nextCollectionNode = $collectionNode->createNode(strtolower($expectedNodeProperties['title']), $nextCollectionNodeType);
                        foreach ($expectedNodeProperties as $expectedPropertyName => $expectedPropertyValue) {
                            $nextCollectionNode->setProperty($expectedPropertyName, $expectedPropertyValue);
                        }
                    }

                    $collectionNode = $nextCollectionNode;
                }

                // move node into the last collection node
                $node->moveInto($collectionNode);
            }
        }
    }
}