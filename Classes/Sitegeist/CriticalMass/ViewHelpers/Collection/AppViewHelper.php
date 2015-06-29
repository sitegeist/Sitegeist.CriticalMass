<?php
namespace Sitegeist\CriticalMass\ViewHelpers\Collection;

use TYPO3\Flow\Annotations as Flow;

/**
 * ViewHelper to print a wrapper indicating the load of the
 * Sitegeist.CriticalMass:List javascript application, when the Neos
 * backend is rendered.
 *
 * = Examples =
 *
 * Given we are currently seeing the Neos backend:
 *
 * <code title="Basic usage">
 * <sgcm:list.app>
 * Hello World
 * </sgcm:list.app>
 * </code>
 * <output>
 * <div data-sgcm-app="Sitegeist.CriticalMass:List">
 * Hello World
 * </div>
 * </output>
 */
class AppViewHelper extends \Sitegeist\CriticalMass\ViewHelpers\AbstractApplicationContextViewHelper {



  const LIST_APPLICATION_NAME = 'Sitegeist.CriticalMass:Collection';

	/**
   * If in backend, render a javascript applciation wrapper around the contents of
   * this view helper
   *
   * @param string $nodeType
   * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $referenceNode
	 * @return string
	 */
	public function render($nodeType = '', $referenceNode = NULL) {
    if ($referenceNode !== NULL && !$referenceNode instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
      throw new \IllegalArgumentException('referenceNode must be of Type \TYPO3\TYPO3CR\Domain\Model\NodeInterface',
        1435571225);
    }

    if ($this->getCurrentNodeContext()->isInBackend()) {
      if ($this->templateVariableContainer->exists('@ApplicationContext')) {
        throw new \RuntimeException('It is not allowed to nest Application Contexts',
          1435574783);
      }

      $this->templateVariableContainer->add('@ApplicationContext', self::LIST_APPLICATION_NAME);

      $contents = ('' !== $nodeType) ?
        sprintf('<input type="hidden" data-namespace="%s" data-key="nodeType" value="%s">',
          self::LIST_APPLICATION_NAME,
          $nodeType
      ) : '';

      $contents.= (NULL !== $referenceNode) ?
        sprintf('<input type="hidden" data-namespace="%s" data-key="referenceNode" value="%s">',
          self::LIST_APPLICATION_NAME,
          $referenceNode->getPath()
      ) : '';

      $contents.= $this->renderChildren();
      return $this->wrapApplicationUserInterface(self::LIST_APPLICATION_NAME, $contents);
    }

    return $this->renderChildren();
	}
}
