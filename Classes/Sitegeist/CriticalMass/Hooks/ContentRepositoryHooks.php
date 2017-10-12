<?php
namespace Sitegeist\CriticalMass\Hooks;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\PublishingServiceInterface;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeServiceInterface;

use Neos\Eel\FlowQuery\FlowQuery as FlowQuery;
use Neos\Utility\Arrays;

use Sitegeist\CriticalMass\Service\ExpressionService;
use Sitegeist\CriticalMass\Service\NodeSortingService;

/**
 * @Flow\Scope("singleton")
 */
class ContentRepositoryHooks
{

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var PublishingServiceInterface
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var NodeServiceInterface
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ExpressionService
     */
    protected $expressionService;

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
     * @flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
        // Make sure, we get consistent data from our queries
        $this->persistenceManager->persistAll();


        $documentNode = (new FlowQuery(array($node)))->closest('[instanceof Neos.Neos:Document]')->get(0);
        $site = (new FlowQuery(array($node)))->parents('[instanceof Neos.Neos:Document]')->last()->get(0);

        $baseContext = ['node' => $node, 'documentNode' => $documentNode, 'site' => $site];
        $expressionContext = ['node' => $node, 'documentNode' => $documentNode, 'site' => $site];

        // evaluate context first
        $contextProperties = Arrays::getValueByPath($configuration, 'context');
        if ($contextProperties && is_array($contextProperties)) {
            foreach ($contextProperties as $propertyName => $expression) {
                $expressionContext[$propertyName] = $this->expressionService->evaluateExpression($expression, $baseContext);
            }
        }

        // check the condition first if one is defined
        if (array_key_exists('condition', $configuration) && $configuration['condition']) {
            $evaluatedCondition = $this->expressionService->evaluateExpression(
                $configuration['condition'],
                $expressionContext
            );
            if (!$evaluatedCondition) {
                return;
            }
        }

        // find collection root
        $collectionRoot = $this->expressionService->evaluateExpression(
            $configuration['root'],
            $expressionContext
        );

        // handle path rules
        if ($collectionRoot && $collectionRoot instanceof NodeInterface) {
            $targetCollectionNode = $collectionRoot;
            $collectionPath = array($targetCollectionNode);

            // traverse path and move node
            foreach ($configuration['path'] as $pathItemConfiguration) {
                $pathItemTypeConfiguration = $this->expressionService->evaluateExpression(
                    $pathItemConfiguration['type'],
                    $expressionContext
                );

                $pathItemNodeType = $this->nodeTypeManager->getNodeType($pathItemTypeConfiguration);
                if (!$pathItemNodeType) {
                    continue;
                }

                $nextCollectionNode = $this->expressionService->evaluateExpression(
                    $pathItemConfiguration['node'],
                    array_merge($expressionContext, ['parent' => $targetCollectionNode])
                );

                // create missing collectionNodes
                if (!$nextCollectionNode) {
                    $nextCollectionNodeName = $this->nodeService->generateUniqueNodeName($targetCollectionNode->getPath());
                    $nextCollectionNode = $targetCollectionNode->createNode($nextCollectionNodeName, $pathItemNodeType);
                    if ($pathItemConfiguration['properties']) {
                        foreach ($pathItemConfiguration['properties'] as $propertyName => $propertyEelExpression) {
                            $propertyValue = $this->expressionService->evaluateExpression(
                                $propertyEelExpression,
                                $expressionContext
                            );
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

            if (array_key_exists('sortBy', $configuration) && $configuration['sortBy']) {
                $this->nodeSortingService->sortChildNodesByEelExpression(
                    $targetCollectionNode,
                    $configuration['sortBy']
                );
            }

            if (array_key_exists('autoPublishPath', $configuration) &&
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
        $contents = $node->getChildNodes('Neos.Neos:Content');
        foreach ($contents as $contentNode) {
            if (!$contentNode->getWorkspace()->isPublicWorkspace()) {
                $this->publishContentsRecursively($contentNode);
            }
        }

        $this->publishingService->publishNode($node);
    }
}
