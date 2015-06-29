<?php
namespace Sitegeist\CriticalMass\ViewHelpers;

class AbstractApplicationContextViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

  const APPLICATION_WRAPPER = '<div data-sgcmapp="%s">%s</div>';

  protected $escapeChildren = FALSE;

  protected $escapeOutput = FALSE;

  /**
   * Wrap any string in an application container
   *
   * @return string
   */
  protected function wrapApplicationUserInterface($applicationName, $content) {
    return sprintf(self::APPLICATION_WRAPPER, $applicationName, $content);
  }

  /**
   * Get the current node context
   *
   * @return \TYPO3\Neos\Domain\Service\ContentContext
   */
  protected function getCurrentNodeContext() {
    $view = $this->viewHelperVariableContainer->getView();

    if ($view instanceof \TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface) {
			$currentTypoScriptContext = $view->getTypoScriptObject()->getTsRuntime()->getCurrentContext();
			if (isset($currentTypoScriptContext['node'])) {
				$context = $currentTypoScriptContext['node']->getContext();

        if ($context instanceof \TYPO3\Neos\Domain\Service\ContentContext) {
          return $context;
        }
			}
    }
  }
}
