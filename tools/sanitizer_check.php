<?php
// Developer tool: feeds known XSS payloads through the rich-text sanitizer and reports any that survive.
// Run inside the installed app: php tools/sanitizer_check.php
require __DIR__ . '/../app/bootstrap.php';
use App\Core\Sanitizer;

$bad = [
    '<script>alert(1)</script>', '<img src=x onerror=alert(1)>', '<a href="javascript:alert(1)">x</a>', '<a href="  JaVaScRiPt:alert(1)">x</a>',
    '<a href="java&#x09;script:alert(1)">x</a>', '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>', '<svg onload=alert(1)>', '<svg><script>alert(1)</script></svg>',
    '<iframe src="javascript:alert(1)"></iframe>', '<body onload=alert(1)>', '<div style="background:url(javascript:alert(1))">x</div>', '<style>@import "x";</style>',
    '<math><mi xlink:href="javascript:alert(1)">x</mi></math>', '<form action="javascript:alert(1)"><input type=submit></form>', '<object data="javascript:alert(1)"></object>',
    '<embed src="javascript:alert(1)">', '<img src="//evil.example/x.png" onerror="alert(1)">', '<p onclick="alert(1)">x</p>', '<a href="#" onmouseover="alert(1)">x</a>',
    '<!--<script>alert(1)</script>-->', '<scr<script>ipt>alert(1)</scr</script>ipt>', '<img src="x" srcset="javascript:alert(1)">',
    '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">', '<link rel=stylesheet href="javascript:alert(1)">', '<base href="javascript:alert(1)//">',
    '<a href="vbscript:msgbox(1)">x</a>', '<img src="data:image/svg+xml;base64,PHN2Zz4=">', '<table background="javascript:alert(1)"><tr><td>x</td></tr></table>',
    '<a href="&#106;avascript:alert(1)">x</a>', '<details open ontoggle=alert(1)>x</details>', '<a href="javascript&colon;alert(1)">x</a>', '<img src=x onerror=alert`1`>',
    "<a href=\"jav\tascript:alert(1)\">x</a>", '<input autofocus onfocus=alert(1)>', '<textarea><script>alert(1)</script></textarea>', '<noscript><p title="</noscript><img src=x onerror=alert(1)>">',
];
$pattern = '/<script|<iframe|<svg|<style|<object|<embed|<form|<meta|<link|<base|<input|<textarea|<math|javascript:|vbscript:|data:|\son\w+\s*=|expression\(|srcset|&colon;/i';
$fail = 0;
foreach ($bad as $i => $p) {
    $out = Sanitizer::clean($p);
    if (preg_match($pattern, $out, $m)) { $fail++; echo "SURVIVED #$i: " . $p . "  =>  " . $out . "\n"; }
}
$keep = Sanitizer::clean('<p>Hello <strong>bold</strong> <a href="https://example.org/x" target="_blank">link</a></p>');
if (strpos($keep, '<strong>bold</strong>') === false || strpos($keep, 'href="https://example.org/x"') === false || strpos($keep, 'noopener') === false) { $fail++; echo "LEGIT CONTENT DAMAGED: $keep\n"; }
echo ($fail ? "FAILED: $fail" : 'ALL ' . count($bad) . ' PAYLOADS NEUTRALISED, legitimate markup kept') . "\n";
exit($fail ? 1 : 0);
