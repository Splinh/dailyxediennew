<?php
$html = file_get_contents('https://dailyxedien.vn/?s=zoomer');
if ($html) {
    preg_match_all('/href="([^"]*\/san-pham\/[^"]*zoomer[^"]*)"/i', $html, $m);
    print_r(array_unique($m[1]));
}
