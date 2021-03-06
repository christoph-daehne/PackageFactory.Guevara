<?php
namespace PackageFactory\Guevara\Domain\Service;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\Neos\Service\LinkingService;
use PackageFactory\Guevara\TYPO3CR\Service\NodeService;

class NodeTreeBuilder
{
    /**
     * The site node
     *
     * @var NodeInterface
     */
    protected $root;

    /**
     * The currently active node in the tree
     *
     * @var NodeInterface
     */
    protected $active;

    /**
     * An (optional) node type filter
     *
     * @var string|null
     */
    protected $nodeTypeFilter = null;

    /**
     * An (optional) search term filter
     *
     * @var string
     */
    protected $searchTermFilter = '';

    /**
     * Determines how many levels of the tree should be loaded
     *
     * @var integer
     */
    protected $depth = 1;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * Set the root node
     *
     * @param string $rootContextPath
     * @return void
     */
    public function setRoot($rootContextPath)
    {
        $this->root = $this->nodeService->getNodeFromContextPath($rootContextPath);
    }

    /**
     * Get the root node
     *
     * @return NodeInterface
     */
    public function getRoot()
    {
        if (!$this->root) {
            $this->root = $this->active->getContext()->getCurrentSiteNode();
        }

        return $this->root;
    }

    /**
     * Set the active node
     *
     * @param string $activeContextPath
     * @return void
     */
    public function setActive($activeContextPath)
    {
        $this->active = $this->nodeService->getNodeFromContextPath($activeContextPath);
    }

    /**
     * Set the node type filter
     *
     * @param string $nodeTypeFilter
     * @return void
     */
    public function setNodeTypeFilter($nodeTypeFilter)
    {
        $this->nodeTypeFilter = $nodeTypeFilter;
    }

    /**
     * Set the search term filter
     *
     * @param string $searchTermFilter
     * @return void
     */
    public function setSearchTermFilter($searchTermFilter)
    {
        $this->searchTermFilter = $searchTermFilter;
    }

    /**
     * Set the depth
     *
     * @param integer $depth
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
    }

    /**
     * Set the controller context
     * @param ControllerContext $controllerContext
     * @return void
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Build a json serializable tree structure containing node information
     *
     * @return array
     */
    public function build($includeRoot = false, $root = null, $depth = null)
    {
        $root = $root === null ? $this->getRoot() : $root;
        $depth = $depth === null ? $this->depth : $depth;

        $result = [];

        foreach ($root->getChildNodes($this->nodeTypeFilter) as $childNode) {
            $hasChildNodes = $childNode->hasChildNodes($this->nodeTypeFilter);
            $shouldLoadChildNodes = $hasChildNodes && ($depth > 1 || $this->isInRootLine($this->active, $childNode));

            $result[$childNode->getName()] = [
                'label' => $childNode->getNodeType()->isOfType('TYPO3.Neos:Document') ?
                    $childNode->getProperty('title') : $childNode->getLabel(),
                'contextPath' => $childNode->getContextPath(),
                'nodeType' => $childNode->getNodeType()->getName(),
                'hasChildren' => $hasChildNodes,
                'isActive' => $this->active && ($childNode->getPath() === $this->active->getPath()),
                'isFocused' => $this->active && ($childNode->getPath() === $this->active->getPath()),
                'isCollapsed' => !$shouldLoadChildNodes,
                'isCollapsable' => $hasChildNodes
            ];

            if ($shouldLoadChildNodes) {
                $result[$childNode->getName()]['children'] =
                    $this->build(false, $childNode, $depth - 1);
            }

            if ($childNode->getNodeType()->isOfType('TYPO3.Neos:Document')) {
                $result[$childNode->getName()]['href'] = $this->linkingService->createNodeUri(
                    /* $controllerContext */ $this->controllerContext,
                    /* $node */ $childNode,
                    /* $baseNode */ null,
                    /* $format */ null,
                    /* $absolute */ true,
                    /* $arguments */ [],
                    /* $section */ '',
                    /* $addQueryString */ false,
                    /* $argumentsToBeExcludedFromQueryString */ [],
                    /* $resolveShortcuts */ false
                );
            }
        }

        if ($includeRoot) {
          return [
              $root->getName() =>[
                  'label' => $root->getNodeType()->isOfType('TYPO3.Neos:Document') ?
                      $root->getProperty('title') : $root->getLabel(),
                  'icon' => 'globe',
                  'contextPath' => $root->getContextPath(),
                  'nodeType' => $root->getNodeType()->getName(),
                  'hasChildren' => count($result),
                  'isCollapsed' => false,
                  'isActive' => $this->active && ($root->getPath() === $this->active->getPath()),
                  'isFocused' => $this->active && ($root->getPath() === $this->active->getPath()),
                  'children' => $result
              ]
          ];
        }

        return $result;
    }

    protected function isInRootLine(NodeInterface $haystack = null, NodeInterface $needle)
    {
        if ($haystack === null) {
            return false;
        }

        return mb_strrpos($haystack->getPath(), $needle->getPath(), null, 'UTF-8') === 0;
    }
}
