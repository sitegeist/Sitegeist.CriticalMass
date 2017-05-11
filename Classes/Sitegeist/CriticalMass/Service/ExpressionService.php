<?php
/**
 * Created by PhpStorm.
 * User: MFI
 * Date: 11.05.17
 * Time: 08:11
 */

namespace Sitegeist\CriticalMass\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\Utility as EelUtility;
use Neos\Eel\CompilingEvaluator;

class ExpressionService
{
    /**
     * @Flow\InjectConfiguration(path="defaultContext", package="Neos.Fusion")
     * @var array
     */
    protected $defaultTypoScriptContextConfiguration;

    /**
     * @Flow\Inject(lazy=FALSE)
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * Evaluate the given expression as eel if an eel pattern is detected
     * otherwise return the expression as value
     *
     * @param string $expression
     * @param array $context
     * @return mixed
     * @throws \Neos\Eel\Exception
     */
    public function evaluateExpression(
        $expression,
        $context
    ) {
        if (is_string($expression) && substr($expression, 0, 2) == '${') {
            $expression = str_replace(chr(10), '', $expression);
            return EelUtility::evaluateEelExpression($expression, $this->eelEvaluator,
                $context, $this->defaultTypoScriptContextConfiguration);
        } else {
            return $expression;
        }
    }

}
