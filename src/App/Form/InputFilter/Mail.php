<?php

namespace App\Form\InputFilter;

use Zend\InputFilter\InputFilter;

class Mail extends InputFilter
{

    public function __construct()
    {
        //username
        $this->add(array(
            'name' => 'mailAddress',
            'required' => true,
            'validators' => array(array(
                'name' => 'Zend\Validator\EmailAddress',
                )
            )
        ));

        //Password
        $this->add(array(
            'name' => 'mailSubject',
            'required' => true,
            'validators' => array(array(
                    'name' => 'Zend\Validator\StringLength',
                    'options' => array(
                        'min' => 3,
                        'max' => 50,
                    ),
                ),
            )
        ));
        
        //Password
        $this->add(array(
            'name' => 'mailContent',
            'required' => true,
            'validators' => array(array(
                    'name' => 'Zend\Validator\StringLength',
                    'options' => array(
                        'min' => 15,
                        'max' => 2000,
                    ),
                ),
            )
        ));
    }

}
