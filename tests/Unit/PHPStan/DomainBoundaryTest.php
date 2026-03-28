<?php

describe('Domain Boundary Enforcement', function () {
    it('domain layer files contain zero Illuminate or Laravel imports (TECH-01)', function () {
        $domainPath = app_path('Domain');

        if (!is_dir($domainPath)) {
            $this->markTestSkipped('Domain directory does not exist yet');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($domainPath)
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        expect($phpFiles)->not->toBeEmpty('Domain directory should contain at least one PHP file');

        foreach ($phpFiles as $filePath) {
            $contents = file_get_contents($filePath);
            expect($contents)
                ->not->toContain('use Illuminate\\', "File {$filePath} imports from Illuminate")
                ->not->toContain('use Laravel\\', "File {$filePath} imports from Laravel");
        }
    });
});
