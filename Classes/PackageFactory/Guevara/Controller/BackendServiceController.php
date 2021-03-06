<?php
namespace PackageFactory\Guevara\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "PackageFactory.Guevara".*
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use PackageFactory\Guevara\Domain\Model\ChangeCollection;
use PackageFactory\Guevara\Domain\Model\FeedbackCollection;
use PackageFactory\Guevara\Domain\Model\Feedback\Messages\Error;
use PackageFactory\Guevara\Domain\Model\Feedback\Messages\Info;
use PackageFactory\Guevara\Domain\Model\Feedback\Messages\Success;
use PackageFactory\Guevara\Domain\Model\Feedback\Operations\ReloadDocument;
use PackageFactory\Guevara\Domain\Service\NodeTreeBuilder;
use PackageFactory\Guevara\TYPO3CR\Service\NodeService;
use TYPO3\Eel\FlowQuery\FlowQuery;
use PackageFactory\Guevara\View\BackendTypoScriptView;

class BackendServiceController extends ActionController
{

    /**
     * @var array
     */
    protected $supportedMediaTypes = ['application/json'];

    /**
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\Flow\Mvc\View\JsonView::class;

    /**
     * @Flow\Inject
     * @var FeedbackCollection
     */
    protected $feedbackCollection;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PublishingService
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * Apply a set of changes to the system
     *
     * @param ChangeCollection $changes
     * @return void
     */
    public function changeAction(ChangeCollection $changes)
    {
        try {
            $count = $changes->count();
            $changes->compress()->apply();

            $success = new Info();
            $success->setMessage(sprintf('%d change(s) successfully applied.', $count));

            $this->feedbackCollection->add($success);
            $this->persistenceManager->persistAll();
        } catch(\Exception $e) {
            $error = new Error();
            $error->setMessage($e->getMessage());

            $this->feedbackCollection->add($error);
        }

        $this->view->assign('value', $this->feedbackCollection);
    }

    /**
     * Publish nodes
     *
     * @param array $nodeContextPaths
     * @param string $targetWorkspaceName
     * @return void
     */
    public function publishAction(array $nodeContextPaths, $targetWorkspaceName) {
        try {
            $targetWorkspace = $this->workspaceRepository->findOneByName($targetWorkspaceName);

            foreach ($nodeContextPaths as $contextPath) {
                $node = $this->nodeService->getNodeFromContextPath($contextPath);
                $this->publishingService->publishNode($node, $targetWorkspace);

                $reloadDocument = new ReloadDocument();
                $reloadDocument->setDocument($this->nodeService->getClosestDocument($node));

                $this->feedbackCollection->add($reloadDocument);
            }

            $success = new Success();
            $success->setMessage(sprintf('Published %d change(s) to %s.', count($nodeContextPaths), $targetWorkspaceName));

            $this->feedbackCollection->add($success);

            $this->persistenceManager->persistAll();
        } catch(\Exception $e) {
            $error = new Error();
            $error->setMessage($e->getMessage());

            $this->feedbackCollection->add($error);
        }

        $this->view->assign('value', $this->feedbackCollection);
    }

    /**
     * Discard nodes
     *
     * @param array $nodeContextPaths
     * @return void
     */
    public function discardAction(array $nodeContextPaths) {
        try {
            foreach ($nodeContextPaths as $contextPath) {
                $node = $this->nodeService->getNodeFromContextPath($contextPath);
                $this->publishingService->discardNode($node);

                $reloadDocument = new ReloadDocument();
                $reloadDocument->setDocument($this->nodeService->getClosestDocument($node));

                $this->feedbackCollection->add($reloadDocument);
            }

            $success = new Success();
            $success->setMessage(sprintf('Discarded %d node(s).', count($nodeContextPaths)));

            $this->feedbackCollection->add($success);

            $this->persistenceManager->persistAll();
        } catch(\Exception $e) {
            $error = new Error();
            $error->setMessage($e->getMessage());

            $this->feedbackCollection->add($error);
        }

        $this->view->assign('value', $this->feedbackCollection);
    }

    public function initializeLoadTreeAction()
    {
        $this->arguments['nodeTreeArguments']->getPropertyMappingConfiguration()->allowAllProperties();
    }

    /**
     * Load the nodetree
     *
     * @param NodeTreeBuilder $nodeTreeArguments
     * @param boolean $includeRoot
     * @return void
     */
    public function loadTreeAction(NodeTreeBuilder $nodeTreeArguments, $includeRoot = false)
    {
        $nodeTreeArguments->setControllerContext($this->controllerContext);
        $this->view->assign('value', $nodeTreeArguments->build($includeRoot));
    }

    /**
     * Build and execute a flow query chain
     *
     * @param array $chain
     * @return void
     */
    public function flowQueryAction(array $chain)
    {
        $createContext = array_shift($chain);
        $finisher = array_pop($chain);

        $flowQuery = new FlowQuery(array_map(
            function ($envelope) {
                return $this->nodeService->getNodeFromContextPath($envelope['$node']);
            },
            $createContext['payload']
        ));

        foreach ($chain as $operation) {
            $flowQuery = call_user_func_array([$flowQuery, strtolower($operation['type'])], $operation['payload']);
        }

        if ('GET' === $finisher['type']) {
            $result = $flowQuery->get();
        }

        $typoScriptView = new BackendTypoScriptView();
        $typoScriptView->setControllerContext($this->controllerContext);
        $typoScriptView->setTypoScriptPath('nodes');
        $typoScriptView->assign('nodes', $result);

        $this->view->assign('value', $typoScriptView->render());
    }
}
