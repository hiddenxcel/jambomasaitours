<?php
/**
 * Vifaa vya pamoja vya muundo wa admin (v2).
 *
 *   adm_pagehead($title, $subtitle, [
 *       'icon'    => 'fa-newspaper',
 *       'actions' => [['label'=>'New post','href'=>'blog.php?new=1','icon'=>'fa-plus','kind'=>'primary']],
 *       'help'    => ['key'=>'blog','title'=>'How this page works','steps'=>[...],'tips'=>[...]],
 *   ]);
 *   adm_tip('Maelezo mafupi', 'brand|warn|danger|ok|info');
 *
 * Maandishi ya steps/tips yanaweza kuwa na <strong>, <em>, <code>, <a> (yanaandikwa na msanidi, si na mtumiaji).
 */
if (!function_exists('adm_pagehead')) {

    function adm_pagehead(string $title, string $subtitle = '', array $opt = []): void
    {
        $icon    = $opt['icon'] ?? '';
        $actions = $opt['actions'] ?? [];
        echo '<header class="adm-pagehead"><div>';
        echo '<h1 class="adm-pagehead__title">';
        if ($icon) echo '<span class="adm-pagehead__icon"><i class="fas ' . htmlspecialchars($icon) . '"></i></span>';
        echo htmlspecialchars($title) . '</h1>';
        if ($subtitle !== '') echo '<p class="adm-pagehead__sub">' . $subtitle . '</p>';
        echo '</div>';
        if ($actions) {
            echo '<div class="adm-pagehead__actions">';
            foreach ($actions as $a) {
                $kind = $a['kind'] ?? 'outline';
                $cls  = $kind === 'primary' ? 'btn-adm btn-adm-primary' : ($kind === 'danger' ? 'btn-adm btn-adm-danger' : 'btn-adm btn-adm-outline');
                echo '<a class="' . $cls . '" href="' . htmlspecialchars($a['href'] ?? '#') . '" ' . ($a['attrs'] ?? '') . '>';
                if (!empty($a['icon'])) echo '<i class="fas ' . htmlspecialchars($a['icon']) . '"></i> ';
                echo htmlspecialchars($a['label'] ?? '') . '</a>';
            }
            echo '</div>';
        }
        echo '</header>';
        if (!empty($opt['help'])) adm_help($opt['help']);
    }

    function adm_help(array $h): void
    {
        static $js = false;
        $key   = preg_replace('/[^a-z0-9_-]/i', '', $h['key'] ?? basename($_SERVER['PHP_SELF'], '.php'));
        $title = $h['title'] ?? 'How this page works';
        echo '<details class="adm-help" data-help="' . htmlspecialchars($key) . '" open>';
        echo '<summary><span class="adm-help__ico"><i class="fas fa-lightbulb"></i></span><span>' . htmlspecialchars($title) . '</span>';
        echo '<span class="adm-help__hint"><span class="adm-help__hint-t">Hide</span> <i class="fas fa-chevron-down"></i></span></summary>';
        echo '<div class="adm-help__body"><div>';
        if (!empty($h['steps'])) {
            echo '<h3 class="adm-help__h">' . htmlspecialchars($h['steps_title'] ?? 'Step by step') . '</h3><ol class="adm-steps">';
            foreach ($h['steps'] as $s) echo '<li>' . $s . '</li>';
            echo '</ol>';
        }
        echo '</div><div>';
        if (!empty($h['tips'])) {
            echo '<h3 class="adm-help__h">' . htmlspecialchars($h['tips_title'] ?? 'Good to know') . '</h3><ul class="adm-tips">';
            foreach ($h['tips'] as $t) echo '<li>' . $t . '</li>';
            echo '</ul>';
        }
        echo '</div></div></details>';
        if (!$js) {
            $js = true;
            echo '<script>(function(){document.querySelectorAll("details.adm-help").forEach(function(d){var k="adm_help_"+d.dataset.help,t=d.querySelector(".adm-help__hint-t");'
               . 'try{if(localStorage.getItem(k)==="closed")d.removeAttribute("open");}catch(e){}'
               . 'function lbl(){if(t)t.textContent=d.open?"Hide":"Show";}lbl();'
               . 'd.addEventListener("toggle",function(){lbl();try{localStorage.setItem(k,d.open?"open":"closed");}catch(e){}});});})();</script>';
        }
    }

    function adm_tip(string $html, string $kind = 'info', ?string $icon = null): void
    {
        $icons = ['info' => 'fa-circle-info', 'brand' => 'fa-lightbulb', 'warn' => 'fa-triangle-exclamation', 'danger' => 'fa-circle-exclamation', 'ok' => 'fa-circle-check'];
        $ic = $icon ?? ($icons[$kind] ?? 'fa-circle-info');
        $cls = $kind === 'info' ? 'adm-tip' : 'adm-tip adm-tip--' . $kind;
        echo '<div class="' . $cls . '"><i class="fas ' . htmlspecialchars($ic) . '"></i><div>' . $html . '</div></div>';
    }
}
