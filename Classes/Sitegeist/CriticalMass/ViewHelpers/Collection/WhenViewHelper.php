<?php
namespace Sitegeist\CriticalMass\ViewHelpers\Collection;

/**
 * Conditional ViewHelper that helps operating within a
 * (possibly non-existent) application conext
 *
 * === Examples ===
 *
 * Given we're not in the Neos backend.
 *
 * <code title="Basic usage">
 * <sgcm:list.app>
 * Hello {sgcm:list.when(then: 'Foo', else: 'Bar')}!
 * </sgcm:list.app>
 * </code>
 * <output>
 * Hello Bar
 * </output>
 * @return void
 */
class WhenViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

  public function render($then = NULL, $else = NULL) {
    if ($this->templateVariableContainer->exists('@ApplicationContext')) {
      $applicationContext = $this->templateVariableContainer->get('@ApplicationContext');
      return $this->resolveConditionalParameter(AppViewHelper::LIST_APPLICATION_NAME === $applicationContext,
        $then, $else);
    }

    return $this->resolveConditionalParameter(FALSE, NULL, $else);
  }

  protected function resolveConditionalParameter($condition, $then = NULL, $else = NULL) {
    if ($condition === TRUE) {
      return (NULL !== $then) ? $then : TRUE;
    }

    (NULL !== $else) ? $else : FALSE;
  }
}
