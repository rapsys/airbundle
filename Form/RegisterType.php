<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends \Rapsys\UserBundle\Form\RegisterType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return parent::buildForm($builder, $options)
			->add('phone', TelType::class, array('attr' => array('placeholder' => 'Your phone'), 'constraints' => array(new NotBlank(array('message' => 'Please provide your phone')))));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_register';
	}
}