<?php
// Standard GPL and phpdocs

namespace local_expnotas\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    public function render_index_page($page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_expnotas/index_page', $data);
    }
}
