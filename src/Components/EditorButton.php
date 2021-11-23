<?php

namespace Netflex\Pages\Components;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class EditorButton extends Component
{
    public $area;
    public $page;
    public $class;
    public $name;
    public $label;
    public $description;
    public $items;
    public $style;
    public $icon;
    public $field;
    public $type;
    public $config;
    public $relationId;

    /** Determines if this button should use the Netflex editor styles */
    protected $styles = true;

    /**
     * Create a new component instance.
     *
     * @param string $area
     * @param null $type
     * @param int $items
     * @param string|null $name
     * @param string|null $label
     * @param string|null $description
     * @param string $style
     * @param string|null $icon
     * @param string|null $position
     * @param string|null $field
     * @param string|array|null $model
     * @param array|null $options
     * @throws Exception
     */
    public function __construct(
        $area = '',
        $type = null,
        $items = 99999,
        $name = null,
        $label = null,
        $description = null,
        $style = null,
        $icon = null,
        $position = null,
        $field = null,
        $model = null,
        $options = null,
        $class = null
    ) {
        $this->styles = (bool) (variable('netflexEditorStyles') ?? true);
        $this->page = current_page();

        if ($this->styles) {
            $style = $style ?? 'position: initial;';
            $class = array_values(array_filter(['netflex-content-settings-btn', $class]));
            if ($position) {
                $class[] = "netflex-content-btn-pos-$position";
            }
        } else {
            $style = $style ?? '';
            $class = $class ?? 'btn btn-primary';
            $class .= ' netflex-content-settings-btn netflex-styles-disabled';
            $class = explode(' ', $class);
        }

        $this->class = implode(' ', $class);
        $this->name = $name ?? $area;
        $this->label = $label ?? $name ?? $area;
        $this->name = $this->name ?? $this->label ?? $this->area;
        $this->description = $description ?? null;
        $this->area = blockhash_append($area);
        $this->items = (int) ($items ?? 99999);
        $this->style = $style ?? null;
        $this->icon = $icon ?? null;
        $this->field = $field ?? null;
        $this->type = $type ?? null;

        switch ($this->type) {
            case 'editor_large':
                $this->type = 'editor-large';
                break;
            case 'editor_small':
                $this->type = 'editor-small';
                break;
            default:
                break;
        }

        if (!$this->field) {
          switch ($this->type) {
            case 'checkbox-group':
            case 'checkbox':
            case 'entries':
            case 'color':
            case 'select':
            case 'multiselect':
            case 'text':
                $this->field = 'text';
                break;
            case 'editor-large':
            case 'editor-small':
                $this->field = 'html';
                break;
            case 'image':
              $this->field = 'image';
              break;
            default:
              break;
          }
        }

        $this->relationId = null;

        if ($model) {
            $this->relationId = Collection::make(is_array($model) ? $model : [$model])
                ->map(function ($model) {
                    if (class_exists($model)) {
                        return (new $model)->getRelationId();
                    }
                })
                ->filter()
                ->implode(',');
        }

        $this->icon = $this->icon ? "<span class=\"{$this->icon}\"></span>" : null;
        $this->config = $options ? base64_encode(serialize($options)) : null;

        page_editable_push($area, $this->type, [
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'type' => $this->type,
            'options' => $options,
            'model' => $model,
            'relationId' => $this->relationId,
            'field' => $this->field,
            'alias' => $this->area,
            'items' => $this->items
        ]);
    }

    public function shouldRender()
    {
        return current_mode() === 'edit';
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('netflex-pages::editor-button');
    }
}
