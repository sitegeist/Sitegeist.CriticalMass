<?php
namespace Sitegeist\CriticalMass\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

use Neos\Flow\Cli\CommandController;

use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\SiteService;

use Sitegeist\CriticalMass\Service\ExpressionService;

class CsvCommandController extends CommandController
{

    /**
     * @var array
     * @Flow\InjectConfiguration("import")
     */
    protected $importConfigurations;

    /**
     * @var array
     * @Flow\InjectConfiguration("export")
     */
    protected $exportConfigurations;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var SiteService
     */
    protected $siteService;


    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contentContextFactory;

    /**
     * @Flow\Inject
     * @var ExpressionService
     */
    protected $expressionService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * List the defined import and export presets
     *
     */
    public function showPresetsCommand()
    {
        $this->outputLine('Import presets:');
        if ($this->importConfigurations && is_array($this->importConfigurations)) {
            foreach ($this->importConfigurations as $presetName => $preset) {
                $description = array_key_exists('description', $preset) ? $preset['description'] : '';
                $this->outputLine(sprintf(' - %s : %s', $presetName, $description));
            }
        }
        $this->outputLine('Export presets:');
        if ($this->exportConfigurations && is_array($this->exportConfigurations)) {
            foreach ($this->exportConfigurations as $presetName => $preset) {
                $description = array_key_exists('description', $preset) ? $preset['description'] : '';
                $this->outputLine(sprintf(' - %s : %s', $presetName, $description));
            }
        }
    }

    /**
     * Import or update nodes from csv-file
     *
     * @param string $preset Preset-name
     * @param string $file File-name
     * @param string $siteNode SiteNode-name
     * @param boolean $verbose Show informations during process
     */
    public function importCommand($preset, $file, $siteNode = null, $verbose = false)
    {

        if ($this->importConfigurations
            && is_array($this->importConfigurations)
            && array_key_exists($preset, $this->importConfigurations) === false
        ) {
            $this->outputLine(sprintf('The import-preset %s was not found', $preset));
            $this->quit(1);
        } else {
            $configuration = $this->importConfigurations[$preset];
        }

        $site = null;
        if ($siteNode) {
            $site = $this->siteRepository->findOneByNodeName($siteNode);
        } else {
            $site = $this->siteRepository->findDefault();
        }

        if (!$site) {
            $this->outputLine('Site could not be determined. Please specify a valid siteNode.');
            $this->quit(1);
        }
        $fileHandle = fopen($file, "r");

        $contentContext = $this->contentContextFactory->create(['currentSite' => $site]);
        $siteNode = $contentContext->getCurrentSiteNode();

        $handle =  fopen($file, "r");
        $fieldNames = fgetcsv($fileHandle);

        $baseContext = ['site' => $siteNode];
        $contextProperties = Arrays::getValueByPath($configuration, 'context');
        if ($contextProperties && is_array($contextProperties)) {
            foreach ($contextProperties as $propertyName => $expression) {
                $baseContext[$propertyName] = $this->expressionService->evaluateExpression($expression, $baseContext);
            }
        }

        while (($data = fgetcsv($fileHandle)) !== false) {
            // map data to keys
            $row = array_combine($fieldNames, $data);
            $context =  array_merge($baseContext, ['row' => $row]);
            $this->import($context, $configuration, $verbose);
        }
        fclose($handle);
    }


    /**
     * @param array $context
     * @param array $configuration
     * @param bool $verbose
     */
    protected function import($context, $configuration, $verbose = false)
    {
        $updateNodeQuery = Arrays::getValueByPath($configuration, 'update.node');

        $createCondition = Arrays::getValueByPath($configuration, 'create.condition');
        $createParentQuery = Arrays::getValueByPath($configuration, 'create.parentNode');
        $createNodeType = Arrays::getValueByPath($configuration, 'create.type');
        $createPropertyMap = Arrays::getValueByPath($configuration, 'create.properties');

        $propertyMap = Arrays::getValueByPath($configuration, 'properties');

        /**
         * @var NodeInterface
         */
        $node = null;
        if ($updateNodeQuery) {
            $node = $this->expressionService->evaluateExpression($updateNodeQuery, $context);
        }

        $nodeType = null;
        if ($createParentQuery && $createNodeType) {
            $nodeType = $this->nodeTypeManager->getNodeType($createNodeType);
        }

        if ($node instanceof NodeInterface) {
            if ($verbose) {
                $this->outputLine(sprintf('- update node of type %s at path %s ', $node->getNodeType()->getName(), $node->getPath()));
            }
            $persistenceNeeded = $this->update($node, $context, $propertyMap);
            if ($persistenceNeeded) {
                $this->persistenceManager->persistAll();
            }
        } elseif ($createNodeType) {
            if ($createCondition) {
                $conditionResult = $this->expressionService->evaluateExpression($createCondition, $context);
                if (!$conditionResult) {
                    if ($verbose) {
                        $this->outputLine(sprintf('- skip creation nodetype %s from row %s because of condition %s', $createNodeType, implode(',', $context['row']), $createCondition));
                    }
                    return;
                }
            }

            $parent = $this->expressionService->evaluateExpression($createParentQuery, $context);

            if ($parent && $parent instanceof NodeInterface) {
                if ($verbose) {
                    $this->outputLine(sprintf('- create node of type %s below node %s', $createNodeType, $parent->getPath()));
                }
                $nodeTemplate = new NodeTemplate();
                $nodeTemplate->setNodeType($nodeType);
                if ($createPropertyMap) {
                    $mergedPropertyMap = Arrays::arrayMergeRecursiveOverrule($propertyMap, $createPropertyMap);
                    $this->update($nodeTemplate, $context, $mergedPropertyMap);
                } else {
                    $this->update($nodeTemplate, $context, $propertyMap);
                }
                $node = $parent->createNodeFromTemplate($nodeTemplate);
                $this->persistenceManager->persistAll();
            } else {
                if ($verbose) {
                    $this->outputLine(sprintf('- cannot create nodetype %s from row %s because of missing parent node', $createNodeType, implode(',', $context['row'])));
                }
            }
        }

        if ($node && $node instanceof NodeInterface) {
            $descendantImportConfigurations = Arrays::getValueByPath($configuration, 'descendants');
            if ($descendantImportConfigurations) {
                foreach ($descendantImportConfigurations as $key => $descendentImportConfiguration) {
                    $this->import(
                        Arrays::arrayMergeRecursiveOverrule($context, ['ancestor' => $node]),
                        $descendentImportConfiguration,
                        $verbose
                    );
                }
            }
        }
    }

    /**
     * Evaluate property map and set the properties and return wether anything has changed
     *
     * @param NodeInterface|NodeTemplate $node
     * @param $context
     * @param $propertyMap
     * @return boolean return wether a property was changed
     */
    protected function update($node, $context, $propertyMap)
    {
        $persistenceNeeded = false;
        foreach ($propertyMap as $propertyName => $expression) {
            $value = $this->expressionService->evaluateExpression($expression, $context);
            if ($node->getProperty($propertyName) !== $value) {
                $node->setProperty($propertyName, $value);
                $persistenceNeeded = true;
            }
        }
        return $persistenceNeeded;
    }

    /**
     * Export nodes to csv
     *
     * @param string $preset Preset-name
     * @param string $file File-name
     * @param string $siteNode SiteNode-name
     */
    public function exportCommand($preset, $file, $siteNode = null)
    {
        if ($this->exportConfigurations
            && is_array($this->exportConfigurations)
            && array_key_exists($preset, $this->exportConfigurations) === false
        ) {
            $this->outputLine(sprintf('The export-preset %s was not found', $preset));
            $this->quit(1);
        } else {
            $configuration = $this->exportConfigurations[$preset];
        }

        $site = null;
        if ($siteNode) {
            $site = $this->siteRepository->findOneByNodeName($siteNode);
        } else {
            $site = $this->siteRepository->findDefault();
        }

        if (!$site) {
            $this->outputLine('Site could not be determined. Please specify a valid siteNode.');
            $this->quit(1);
        }

        $contentContext = $this->contentContextFactory->create(['currentSite' => $site]);
        $siteNode = $contentContext->getCurrentSiteNode();

        $nodes = $this->expressionService->evaluateExpression($configuration['nodes'], ['site'=> $siteNode]);

        $fileHandle = fopen($file, 'w');
        fputcsv($fileHandle, array_keys($configuration['properties']));
        foreach ($nodes as $node) {
            $row = [];
            foreach ($configuration['properties'] as $expression) {
                $row[] = $this->expressionService->evaluateExpression(
                    $expression,
                    ['site'=> $siteNode, 'node' => $node]
                );
            }
            fputcsv($fileHandle, $row);
        }
        fclose($fileHandle);

        $this->outputLine(sprintf('Exported %s nodes to file %s', count($nodes), $file));
    }
}
