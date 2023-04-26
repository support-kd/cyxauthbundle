<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SupportKd\CyxAuthBundle\Form;

use FOS\UserBundle\Util\LegacyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Captcha\Bundle\CaptchaBundle\Form\Type\CaptchaType;

class ResettingType extends AbstractType
{
   
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('captchaCode', CaptchaType::class, array(
            'captchaConfig' => 'RegisterCaptcha',
            'label' => 'Retype the characters from the picture'
        ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ResettingFormType';

        // Or for Symfony < 2.8
        // return 'fos_user_registration';
    }

    // BC for SF < 3.0
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

}
