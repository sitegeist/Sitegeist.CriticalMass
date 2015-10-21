<?php

namespace Sitegeist\CriticalMass\Hooks;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

use TYPO3\Eel\Utility as EelUtility;
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
     * @Flow\Inject
     * @var \TYPO3\Flow\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     * @Flow\InjectConfiguration("automaticNodeHirarchy")
     */
    protected $automaticNodeHirarchyConfigurations;

    /**
     * @param NodeInterface $node
     */
    public function nodeCreated (NodeInterface $node) {
        $this->handleAutomaticHirarchiesForNodes($node);
    }

    /**
     * @param NodeInterface $node
     */
    public function nodeUpdated (NodeInterface $node) {
        $this->handleAutomaticHirarchiesForNodes($node);
    }


    /**
     * @param NodeInterface $node
     */
    protected function handleAutomaticHirarchiesForNodes (NodeInterface $node) {
        if (is_array($this->automaticNodeHirarchyConfigurations)) {
            foreach ($this->automaticNodeHirarchyConfigurations as $nodeType => $hirarchyConfiguration) {
                if ($node->getNodeType()->getName() === $nodeType) {
                    $this->handleAutomaticHirarchyForNodeType($node, $hirarchyConfiguration);
                }
            }
        }
    }

    /**
     * @param $nodeType
     * @param $configuration
     * @param NodeInterface $node
     */
    protected function handleAutomaticHirarchyForNodeType(NodeInterface $node, $configuration) {

        $defaultTypoScriptContextConfiguration = $this->configurationManager->getConfiguration('Settings', 'Sitegeist.CriticalMass.eelContext');
        $defaultTypoScriptContext = EelUtility::getDefaultContextVariables($defaultTypoScriptContextConfiguration);

        // find collection root
        $collectionRoot = EelUtility::evaluateEelExpression($configuration['root'], $this->eelEvaluator, array('node' => $node), $defaultTypoScriptContext);
        if ($collectionRoot && $collectionRoot instanceof NodeInterface) {

            $targetCollectionNode = $collectionRoot;
            $collectionPath = array($targetCollectionNode);

            // traverse path and move node
            foreach ($configuration['path'] as $pathItem) {
                $expectedNodeProperties = array();
                foreach ($pathItem['properties'] as $propertyName => $propertyEelExpression) {
                    $expectedNodeProperties[$propertyName] = EelUtility::evaluateEelExpression($propertyEelExpression, $this->eelEvaluator, array('node' => $node), $defaultTypoScriptContext);
                }

                if (!$expectedNodeProperties['title'] && !$expectedNodeProperties['uriPathSegment']) {
                    continue;
                }

                // find next path collectionNodes
                $flowQuery = new FlowQuery(array($targetCollectionNode));
                $flowQuery = $flowQuery->children('[instanceof ' . $pathItem['type'] . ']');
                foreach ($expectedNodeProperties as $expectedPropertyName => $expectedPropertyValue) {
                    $flowQuery = $flowQuery->filter('[' . $expectedPropertyName . '="' . $expectedPropertyValue . '"]');
                }
                $nextCollectionNode = $flowQuery->get(0);

                // create missing collectionNodes
                if (!$nextCollectionNode) {
                    $nextCollectionNodeType = $this->nodeTypeManager->getNodeType($pathItem['type']);
                    $nextCollectionNode = $targetCollectionNode->createNode(strtolower($expectedNodeProperties['title']), $nextCollectionNodeType);
                    foreach ($expectedNodeProperties as $expectedPropertyName => $expectedPropertyValue) {
                        $nextCollectionNode->setProperty($expectedPropertyName, $expectedPropertyValue);
                    }
                }

                $targetCollectionNode = $nextCollectionNode;
                $collectionPath[] = $targetCollectionNode;
            }

            if ($targetCollectionNode != $node->getParent()) {
                // move node into to the target
                $node->moveInto($targetCollectionNode);
            }
        }

    }
}