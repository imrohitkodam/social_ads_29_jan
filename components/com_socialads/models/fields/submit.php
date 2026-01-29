<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormField;

use Joomla\CMS\Language\Text;

class JFormFieldSubmit extends JFormField {

	protected $type = 'submit';
	protected $value;
	protected $for;

	public function getInput() {
		
		$this->value = $this->getAttribute('value');
		
		return '<button id="' . $this->id . '"' 
				. ' name="submit_' . $this->for . '"'
				. ' value="'. $this->value . '"' 
				. ' title="' . Text::_('JSEARCH_FILTER_SUBMIT') . '"'
				. ' class="btn" style="margin-top: -10px;">' 
				. Text::_('JSEARCH_FILTER_SUBMIT') 
				. ' </button>';
	}

}
