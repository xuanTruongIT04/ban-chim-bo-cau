<?php

declare(strict_types=1);

namespace App\Rules\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Use_>
 */
final class NoLaravelImportInDomainRule implements Rule
{
    private const FORBIDDEN_PREFIXES = [
        'Illuminate\\',
        'Laravel\\',
    ];

    private const DOMAIN_PATH_SEGMENT = '/Domain/';

    public function getNodeType(): string
    {
        return Use_::class;
    }

    /**
     * @param Use_ $node
     * @return list<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $filePath = $scope->getFile();

        if (!str_contains($filePath, self::DOMAIN_PATH_SEGMENT)) {
            return [];
        }

        $errors = [];
        foreach ($node->uses as $use) {
            $importedName = $use->name->toString();
            foreach (self::FORBIDDEN_PREFIXES as $prefix) {
                if (str_starts_with($importedName, $prefix)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            'Domain layer must not import from "%s". Found: "%s". Move this to Application or Infrastructure layer.',
                            rtrim($prefix, '\\'),
                            $importedName
                        )
                    )->build();
                    break;
                }
            }
        }

        return $errors;
    }
}
