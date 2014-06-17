<?php

namespace Gears\Form\Element;

class Checkbox extends ElementAbstract
{
    public function render()
    {
        $attrs = (object)$this->attributes;
        $html = $this->renderLabel();
        $html .= sprintf(
            '<input type="checkbox" %s value="%s" />',
            $this->getAttributesString(),
            !!$this->form->getData($attrs->name)
        );
        return $html;
    }
}
