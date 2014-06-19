<?php

namespace Gears\Form\Element;

class Checkbox extends ElementAbstract
{
    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $html = $this->renderLabel();
        $html .= sprintf(
            '<input type="checkbox" %s%s value="1" />',
            $this->getAttributesString(),
            !!$this->getValue() ? ' checked="checked"' : ''
        );
        return $html;
    }
}
