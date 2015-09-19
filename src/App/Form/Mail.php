<?php

namespace App\Form;

use Zend\Form\Form;

class Mail extends Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name' => 'mailAddress',
            'type' => 'email',
            'options' => array(
                'label' => 'Email address:',
            ),
        ));

        $this->add(array(
            'name' => 'mailSubject',
            'type' => 'text',
            'options' => array(
                'label' => 'Mail Subject:',
            ),
        ));
        
        $this->add(array(
            'name' => 'mailContent',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Mail content:',
            ),
            'attributes' => array(
                'rows' => 7,
                'class' => 'html-text',
            ),
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Send',
                'id' => 'submitbutton',
                'class' => 'btn btn-primary btn-lg'
            ),
        ));
    }
}