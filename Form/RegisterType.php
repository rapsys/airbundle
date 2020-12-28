<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends \Rapsys\UserBundle\Form\RegisterType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return parent::buildForm($builder, $options)
			#TODO: add url ? add text ?
			->add('phone', TelType::class, ['attr' => ['placeholder' => 'Your phone'], 'constraints' => [new NotBlank(['message' => 'Please provide your phone'])]])
			->add('donation', UrlType::class, ['attr' => ['placeholder' => 'Your donation link']])
			->add('site', UrlType::class, ['attr' => ['placeholder' => 'Your website']]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_register';
	}
}
