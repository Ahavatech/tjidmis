<?php
namespace App\Core;
class View {
    public static function render(string $tpl, array $vars = [], ?string $layout = 'public'): void {
        $content = self::partial($tpl, $vars);
        if ($layout === null) { echo $content; return; }
        $vars['content'] = $content;
        echo self::partial('layouts/' . $layout, $vars);
    }
    public static function partial(string $tpl, array $vars = []): string {
        extract($vars, EXTR_SKIP);
        ob_start();
        require APP . '/Views/' . $tpl . '.php';
        return (string)ob_get_clean();
    }
}
