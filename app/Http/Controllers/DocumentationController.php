<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(): Response
    {
        // Read the user manual markdown file
        $manualPath = base_path('docs/user-manual.md');

        if (! File::exists($manualPath)) {
            abort(404, 'User manual not found');
        }

        $markdownContent = File::get($manualPath);

        // Extract table of contents from markdown headers
        $tableOfContents = $this->extractTableOfContents($markdownContent);

        return Inertia::render('Documentation', [
            'markdownContent' => $markdownContent,
            'tableOfContents' => $tableOfContents,
            'lastModified' => File::lastModified($manualPath),
        ]);
    }

    /**
     * Extract table of contents from markdown headers
     */
    private function extractTableOfContents(string $markdown): array
    {
        $toc = [];
        $lines = explode("\n", $markdown);

        foreach ($lines as $line) {
            if (preg_match('/^(#{2,4})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]) - 1; // Subtract 1 to make h2 = level 1
                $title = trim($matches[2]);
                $anchor = strtolower(str_replace([' ', '/', '&', '?'], ['-', '', '', ''], $title));

                $toc[] = [
                    'level' => $level,
                    'title' => $title,
                    'anchor' => $anchor,
                ];
            }
        }

        return $toc;
    }
}
