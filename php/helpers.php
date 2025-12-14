<?php
function renderMarkdown(string $text): string {
    // 1️⃣ Escape HTML content first to prevent XSS, but allow < and > for markdown processing.
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // Temporarily revert < and > back so regex can match block elements like code and quotes
    $text = str_replace(['&lt;', '&gt;'], ['<', '>'], $text);

    $blocks = [];
    $inlines = [];

    // 2️⃣ Extract fenced code blocks (```) FIRST
    $text = preg_replace_callback(
        '/```([\s\S]*?)```/',
        function ($m) use (&$blocks) {
            $key = "\x00BLOCK" . count($blocks) . "\x00";
            // Re-escape the content inside the code block to display <, > literally
            $blocks[$key] =
                '<pre class="md-code"><code>' . htmlspecialchars($m[1]) . '</code></pre>';
            return $key;
        },
        $text
    );

    // 3️⃣ Extract inline code (`)
    $text = preg_replace_callback(
        '/`([^`\n]+)`/',
        function ($m) use (&$inlines) {
            $key = "\x00INLINE" . count($inlines) . "\x00";
            // Re-escape the content inside the inline code
            $inlines[$key] =
                '<code class="md-inline">' . htmlspecialchars($m[1]) . '</code>';
            return $key;
        },
        $text
    );

    // 4️⃣ Quotes: Match one or more lines starting with >
    $text = preg_replace_callback(
        '/^((?:>.*(?:\n|$))+)/m',
        function ($m) {
            // Remove the leading '> ' (and optional space) from all lines in the captured block
            $content = preg_replace('/^> ?/m', '', $m[1]);
            // Trim the content and wrap it in a single blockquote tag
            return '<blockquote>' . trim($content) . '</blockquote>';
        },
        $text
    );

    // 5️⃣ Headings
    $text = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $text);

    // 6️⃣ Formatting (NOW safe — code is gone)
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text); // Bold
    $text = preg_replace('/__(.*?)__/s', '<u>$1</u>', $text);            // Underline
    $text = preg_replace('/~~(.*?)~~/s', '<s>$1</s>', $text);            // Strikethrough
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);          // Italic

    // 7️⃣ Line breaks
    $text = nl2br($text);

    // 8️⃣ Restore inline code
    $text = strtr($text, $inlines);

    // 9️⃣ Restore fenced code blocks
    $text = strtr($text, $blocks);

    return $text;
}
