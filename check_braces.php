<?php
$path = __DIR__ . '/../users/profile.php';
$content = file_get_contents($path);
$pos = 0;
$inPhp = false;
$line = 1;
$stack = [];
$issues = [];
$len = strlen($content);
for ($i = 0; $i < $len; $i++) {
    $ch = $content[$i];
    // update line number
    if ($ch === "\n") $line++;
    // check entering/exiting php tags
    if (!$inPhp && substr($content, $i, 5) === "<?php") {
        $inPhp = true;
        $i += 4; // skip
        continue;
    }
    if ($inPhp && substr($content, $i, 2) === "?>") {
        $inPhp = false;
        $phpStarts[] = $line;
        $i += 1;
        continue;
    }
    if ($inPhp) {
        if ($ch === '{') {
        $phpEnds[] = $line;
            $stack[] = $line;
        } elseif ($ch === '}') {
            if (count($stack) === 0) {
                $issues[] = "Unmatched closing brace at line $line";
            } else {
                array_pop($stack);
            }
        }
    }
}
if (count($stack) > 0) {
    foreach ($stack as $ln) $issues[] = "Unclosed '{' opened at line $ln";
}
if (empty($issues)) {
    echo "No brace issues found in PHP blocks\n";
} else {
    echo "PHP blocks start lines: " . implode(', ', $phpStarts) . "\n";
    echo "PHP blocks end lines: " . implode(', ', $phpEnds) . "\n";
    echo implode("\n", $issues) . "\n";
}
