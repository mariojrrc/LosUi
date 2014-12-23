<?php
/**
 * Form row styled for Bootstrap 3
 *
 * Long description for file (if any)...
 *
 * @author     Leandro Silva <leandro@leandrosilva.info>
 * @category   LosUi
 * @license    http://opensource.org/licenses/MIT   MIT License
 * @link       http://github.com/LansoWeb/LosUi
 * @see        http://getbootstrap.com/css/#forms
 */
namespace LosUi\Form\View\Helper;

use Zend\Form\View\Helper\FormRow as ZfFormRow;
use Zend\Form\ElementInterface;
use Zend\Form\Element\Button;
use Zend\Form\Element\MonthSelect;
use Zend\Form\LabelAwareInterface;

/**
 * Form row styled for Bootstrap 3
 *
 * @author     Leandro Silva <leandro@leandrosilva.info>
 * @category   LosUi
 * @license    http://opensource.org/licenses/MIT   MIT License
 * @link       http://github.com/LansoWeb/LosUi
 * @see        http://getbootstrap.com/css/#forms
 */
class FormRow extends ZfFormRow
{

    protected $rowWrapper = '<div class="form-group%s">%s%s</div>';

    protected function getElementErrorsHelper()
    {
        if ($this->elementErrorsHelper) {
            return $this->elementErrorsHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementErrorsHelper = $this->view->plugin('los_form_element_errors');
        }

        if (! $this->elementErrorsHelper instanceof FormElementErrors) {
            $this->elementErrorsHelper = new FormElementErrors();
        }

        return $this->elementErrorsHelper;
    }

    public function render(ElementInterface $element)
    {
        $escapeHtmlHelper = $this->getEscapeHtmlHelper();
        $labelHelper = $this->getLabelHelper();
        $elementHelper = $this->getElementHelper();
        $elementErrorsHelper = $this->getElementErrorsHelper();

        $label = $element->getLabel();
        $inputErrorClass = $this->getInputErrorClass();

        if (isset($label) && '' !== $label) {
            // Translate the label
            if (null !== ($translator = $this->getTranslator())) {
                $label = $translator->translate($label, $this->getTranslatorTextDomain());
            }
        }

        $classAttributes = ($element->hasAttribute('class') ? $element->getAttribute('class') . ' ' : '');
        $classAttributes = $classAttributes . 'form-control';
        $element->setAttribute('class', $classAttributes);

        // Does this element have errors ?
        if (count($element->getMessages()) > 0 && ! empty($inputErrorClass)) {
            $classAttributes = ($element->hasAttribute('class') ? $element->getAttribute('class') . ' ' : '');
            $classAttributes = $classAttributes . $inputErrorClass;

            $element->setAttribute('class', $classAttributes);
        }

        if ($this->partial) {
            $vars = array(
                'element' => $element,
                'label' => $label,
                'labelAttributes' => $this->labelAttributes,
                'labelPosition' => $this->labelPosition,
                'renderErrors' => $this->renderErrors
            );

            return $this->view->render($this->partial, $vars);
        }

        if ($this->renderErrors) {
            $elementErrors = $elementErrorsHelper->render($element, [
                'class' => 'text-danger'
            ]);
        }

        $elementString = $elementHelper->render($element);

        // hidden elements do not need a <label> -https://github.com/zendframework/zf2/issues/5607
        $type = $element->getAttribute('type');
        if (isset($label) && '' !== $label && $type !== 'hidden') {

            $labelAttributes = array();

            if ($element instanceof LabelAwareInterface) {
                $labelAttributes = $element->getLabelAttributes();
            }

            if (! $element instanceof LabelAwareInterface || ! $element->getLabelOption('disable_html_escape')) {
                $label = $escapeHtmlHelper($label);
            }

            if (empty($labelAttributes)) {
                $labelAttributes = $this->labelAttributes;
            }

            if (! $element->getAttribute('id') && $element->getName()) {
                $element->setAttribute('id', $element->getName());
            }
            if ($element->getAttribute('id')) {
                $labelAttributes['for'] = $element->getAttribute('id');
            }

            // Multicheckbox elements have to be handled differently as the HTML standard does not allow nested
            // labels. The semantic way is to group them inside a fieldset
            if ($type === 'multi_checkbox' || $type === 'radio' || $element instanceof MonthSelect) {
                $markup = sprintf('<fieldset><legend>%s</legend>%s</fieldset>', $label, $elementString);
            } else {
                // Ensure element and label will be separated if element has an `id`-attribute.
                // If element has label option `always_wrap` it will be nested in any case.
                if ($element->hasAttribute('id') && ($element instanceof LabelAwareInterface && ! $element->getLabelOption('always_wrap'))) {
                    $labelOpen = '';
                    $labelClose = '';
                    $label = $labelHelper($element);
                } else {
                    $labelOpen = $labelHelper->openTag($labelAttributes);
                    $labelClose = $labelHelper->closeTag();
                }

                if ($label !== '' && (! $element->hasAttribute('id')) || ($element instanceof LabelAwareInterface && $element->getLabelOption('always_wrap'))) {
                    $label = '<span>' . $label . '</span>';
                }

                // Button element is a special case, because label is always rendered inside it
                if ($element instanceof Button) {
                    $labelOpen = $labelClose = $label = '';
                }

                switch ($this->labelPosition) {
                    case self::LABEL_PREPEND:
                        $markup = sprintf($this->rowWrapper, ! empty($elementErrors) ? ' has-error' : '', $labelOpen . $label . $labelClose, $elementString);
                        break;
                    case self::LABEL_APPEND:
                    default:
                        $markup = sprintf($this->rowWrapper, ! empty($elementErrors) ? ' has-error' : '', $elementString, $labelOpen . $label . $labelClose);
                        break;
                }
            }

            if ($this->renderErrors) {
                $markup .= $elementErrors;
            }
        } else {
            if ($this->renderErrors) {
                $markup = $elementString . $elementErrors;
            } else {
                $markup = $elementString;
            }
        }

        return $markup;
    }
}
