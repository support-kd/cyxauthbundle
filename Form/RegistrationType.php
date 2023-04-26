<?php
// src/AppBundle/Form/RegistrationType.php

namespace SupportKd\CyxAuthBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Captcha\Bundle\CaptchaBundle\Form\Type\CaptchaType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName');
        $builder->add('LastName');
        $builder->add('phoneNo');
        $builder->add('captchaCode', CaptchaType::class, array(
            'captchaConfig' => 'RegisterCaptcha',
            'label' => 'Retype the characters from the picture'
        ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';

        // Or for Symfony < 2.8
        // return 'fos_user_registration';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }

    // For Symfony 2.x
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}