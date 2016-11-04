<?php

namespace Sitegeist\CriticalMass\Hooks;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\PublishingServiceInterface;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

use TYPO3\TYPO3CR\Utility as CrUtility;
use TYPO3\Eel\Utility as EelUtility;
use TYPO3\Eel\CompilingEvaluator;

use TYPO3\Eel\FlowQuery\FlowQuery as FlowQuery;

use Sitegeist\CriticalMass\Service\NodeSortingService;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryHooks
{

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var PublishingServiceInterface
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

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
     * @var array
     * @Flow\InjectConfiguration("automaticNodeHierarchy")
     */
    protected $automaticNodeHierarchyConfigurations;

    /**
     * @Flow\Inject
     * @var NodeSortingService
     */
    protected $nodeSortingService;

    /**
     * Signal that is triggered on node create
     *
     * @param NodeInterface $node
     */
    public function nodeCreated(NodeInterface $node)
    {
        $this->handleAutomaticHirarchiesForNodes($node);
    }

    /**
     * Signal that is triggered on node create
     *
     * @param NodeInterface $node
     */
    public function nodeUpdated(NodeInterface $node)
    {
        $this->handleAutomaticHirarchiesForNodes($node);
    }

    /**
     * Check wether the given node is affected by automatic hierarchy generation
     * and apply the configured hierarchie if needed
     *
     * @param NodeInterface $node
     */
    protected function handleAutomaticHirarchiesForNodes(NodeInterface $node)
    {
        if (is_array($this->automaticNodeHierarchyConfigurations)) {
            foreach ($this->automaticNodeHierarchyConfigurations as $nodeType => $hierarchyConfiguration) {
                if ($node->getNodeType()->isOfType($nodeType)) {
                    $this->handleAutomaticHierarchyForNodeType($node, $hierarchyConfiguration);
                }
            }
        }
    }

    /**
     * Apply the given hierarchie configuration to the given node
     *
     * @param NodeInterface $nodeType
     * @param array $configuration
     * @param NodeInterface $node
     */
    protected function handleAutomaticHierarchyForNodeType(NodeInterface $node, $configuration)
    {
        $documentNode = (new FlowQuery(array($node)))->closest('[instanceof TYPO3.Neos:Document]')->get(0);
        $site = (new FlowQuery(array($node)))->parents('[instanceof TYPO3.Neos:Document]')->slice(-1, 1)->get(0);

        // find collection root
        $collectionRoot = $this->evaluateExpression($configuration['root'], $node, $documentNode, $site,
            $this->defaultTypoScriptContextConfiguration);
        if ($collectionRoot && $collectionRoot instanceof NodeInterface) {

            $targetCollectionNode = $collectionRoot;
            $collectionPath = array($targetCollectionNode);

            // traverse path and move node
            foreach ($configuration['path'] as $pathItemConfiguration) {
                $pathItemTypeConfiguration = $this->evaluateExpression($pathItemConfiguration['type'], $node,
                    $documentNode, $site, $this->defaultTypoScriptContextConfiguration);
                $pathItemNameConfiguration = $this->evaluateExpression($pathItemConfiguration['name'], $node,
                    $documentNode, $site, $this->defaultTypoScriptContextConfiguration);

                $pathItemName = CrUtility::renderValidNodeName($pathItemNameConfiguration);
                $pathItemNodeType = $this->nodeTypeManager->getNodeType($pathItemTypeConfiguration);

                if (!$pathItemNodeType || !$pathItemName) {
                    continue;
                }

                // find next path collectionNodes
                $childNodes = $targetCollectionNode->getChildNodes();
                $nextCollectionNode = null;
                foreach ($childNodes as $childNode) {
                    if ($childNode->getNodeType() == $pathItemNodeType && $childNode->getNodeData()->getName() == $pathItemName) {
                        $nextCollectionNode = $childNode;
                    }
                }

                // create missing collectionNodes
                if (!$nextCollectionNode) {
                    $nextCollectionNode = $targetCollectionNode->createNode($pathItemName, $pathItemNodeType);
                    if ($pathItemConfiguration['properties']) {
                        foreach ($pathItemConfiguration['properties'] as $propertyName => $propertyEelExpression) {
                            $propertyValue = $this->evaluateExpression($propertyEelExpression, $node, $documentNode,
                                $site, $this->defaultTypoScriptContextConfiguration);
                            $nextCollectionNode->setProperty($propertyName, $propertyValue);
                        }
                    }
                }

                if (array_key_exists('sortBy', $pathItemConfiguration) && $pathItemConfiguration['sortBy']) {
                    $this->nodeSortingService->sortChildNodesByEelExpression(
                        $targetCollectionNode,
                        $pathItemConfiguration['sortBy']
                    );
                }

                $targetCollectionNode = $nextCollectionNode;
                $collectionPath[] = $targetCollectionNode;
            }

            if ($targetCollectionNode != $node->getParent()) {
                // move node into to the target
                $node->moveInto($targetCollectionNode);
            }

            if (
                array_key_exists('autoPublishPath', $configuration) &&
                $configuration['autoPublishPath'] === true
            ) {
                foreach ($collectionPath as $nodeOnPath) {
                    if (!$nodeOnPath->getWorkspace()->isPublicWorkspace()) {
                        $this->publishContentsRecursively($nodeOnPath);
                    }
                }
            }
        }
    }

    protected function publishContentsRecursively(NodeInterface $node)
    {
        $contents = $node->getChildNodes('TYPO3.Neos:Content');
        foreach ($contents as $contentNode) {
            if (!$contentNode->getWorkspace()->isPublicWorkspace()) {
                $this->publishContentsRecursively($contentNode);
            }
        }

        $this->publishingService->publishNode($node);
    }

    /**
     * Evaluate the given expression as eel if an eel pattern is detected
     * otherwise return the expression as value
     *
     * @param string $expression
     * @param NodeInterface $node
     * @param NodeInterface $documentNode
     * @param NodeInterface $site
     * @param array $defaultContextConfiguration
     * @return mixed
     * @throws \TYPO3\Eel\Exception
     */
    protected function evaluateExpression(
        $expression,
        NodeInterface $node,
        NodeInterface $documentNode,
        NodeInterface $site,
        $defaultContextConfiguration
    ) {
        if (is_string($expression) && substr($expression, 0, 2) == '${') {
            return EelUtility::evaluateEelExpression($expression, $this->eelEvaluator,
                array('node' => $node, 'documentNode' => $documentNode, 'site' => $site), $defaultContextConfiguration);
        } else {
            return $expression;
        }
    }

}
