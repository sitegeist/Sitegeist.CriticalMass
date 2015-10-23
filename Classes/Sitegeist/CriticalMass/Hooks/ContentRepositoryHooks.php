<?php

namespace Sitegeist\CriticalMass\Hooks;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

use TYPO3\TYPO3CR\Utility as CrUtility;
use TYPO3\Eel\Utility as EelUtility;
use TYPO3\Eel\CompilingEvaluator;

use TYPO3\Eel\FlowQuery\FlowQuery as FlowQuery;

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
     * @Flow\InjectConfiguration("automaticNodeHierarchy")
     */
    protected $automaticNodeHierarchyConfigurations;

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
        if (is_array($this->automaticNodeHierarchyConfigurations)) {
            foreach ($this->automaticNodeHierarchyConfigurations as $nodeType => $hierarchyConfiguration) {
                if ($node->getNodeType()->isOfType($nodeType)) {
                    $this->handleAutomaticHierarchyForNodeType($node, $hierarchyConfiguration);
                }
            }
        }
    }

    /**
     * @param $nodeType
     * @param $configuration
     * @param NodeInterface $node
     */
    protected function handleAutomaticHierarchyForNodeType(NodeInterface $node, $configuration) {
        $documentNode = (new FlowQuery(array($node)))->closest('[instanceof TYPO3.Neos:Document]')->get(0);
        $site = (new FlowQuery(array($node)))->parents('[instanceof TYPO3.Neos:Document]')->slice(-1,1)->get(0);

        $defaultTypoScriptContextConfiguration = $this->configurationManager->getConfiguration('Settings', 'Sitegeist.CriticalMass.eelContext');
        $defaultTypoScriptContext = EelUtility::getDefaultContextVariables($defaultTypoScriptContextConfiguration);

        // find collection root
        $collectionRoot = $this->evaluateExpression($configuration['root'], $node, $documentNode, $site, $defaultTypoScriptContext);
        if ($collectionRoot && $collectionRoot instanceof NodeInterface) {

            $targetCollectionNode = $collectionRoot;
            $collectionPath = array($targetCollectionNode);

            // traverse path and move node
            foreach ($configuration['path'] as $pathItemConfiguration) {
                $pathItemTypeConfiguration = $this->evaluateExpression($pathItemConfiguration['type'], $node, $documentNode, $site, $defaultTypoScriptContext);
                $pathItemNameConfiguration = $this->evaluateExpression($pathItemConfiguration['name'], $node, $documentNode, $site, $defaultTypoScriptContext);

                $pathItemName = CrUtility::renderValidNodeName($pathItemNameConfiguration);
                $pathItemNodeType = $this->nodeTypeManager->getNodeType($pathItemTypeConfiguration);

                if (!$pathItemNodeType || !$pathItemName){
                    continue;
                }

                // find next path collectionNodes
                $childNodes = $targetCollectionNode->getChildNodes();
                $nextCollectionNode = NULL;
                foreach($childNodes as $childNode) {
                   if ($childNode->getNodeType() == $pathItemNodeType && $childNode->getNodeData()->getName() == $pathItemName) {
                       $nextCollectionNode = $childNode;
                   }
                }

                // create missing collectionNodes
                if (!$nextCollectionNode) {
                    $nextCollectionNode = $targetCollectionNode->createNode($pathItemName, $pathItemNodeType);
                    if ($pathItemConfiguration['properties']) {
                        foreach ($pathItemConfiguration['properties'] as $propertyName => $propertyEelExpression) {
                            $propertyValue = $this->evaluateExpression($propertyEelExpression, $node, $documentNode, $site, $defaultTypoScriptContext);
                            $nextCollectionNode->setProperty($propertyName, $propertyValue);
                        }
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

    protected function evaluateExpression ($expression, $node, $documentNode, $site, $defaultTypoScriptContext) {
        if (is_string($expression) && substr($expression, 0,2) == '${') {
            return EelUtility::evaluateEelExpression($expression,  $this->eelEvaluator, array('node' => $node, 'documentNode' => $documentNode, 'site' => $site), $defaultTypoScriptContext);
        } else {
            return $expression;
        }
    }

}