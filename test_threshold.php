<?php
require_once "vendor/autoload.php";

function getPerformanceThreshold(): int
{
    if (getenv("CI") \!== false || getenv("GITHUB_ACTIONS") \!== false) {
        return 25;
    }
    if (file_exists("/.dockerenv") || getenv("DOCKER") \!== false) {
        return 50;
    }
    return 100;
}

echo "Threshold atual: " . getPerformanceThreshold() . " req/s
";
echo "CI: " . (getenv("CI") \!== false ? "true" : "false") . "
";
echo "GitHub Actions: " . (getenv("GITHUB_ACTIONS") \!== false ? "true" : "false") . "
";
echo "Docker: " . (file_exists("/.dockerenv") ? "true" : "false") . "
";
