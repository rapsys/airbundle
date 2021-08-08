<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends \Rapsys\UserBundle\Form\RegisterType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		//Call parent build form
		$form = parent::buildForm($builder, $options);

		//Add extra phone field
		if (!empty($options['phone'])) {
			$form->add('phone', TelType::class, ['attr' => ['placeholder' => 'Your phone'], 'required' => false]);
		}

		//Return form
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		//Call parent configure options
		parent::configureOptions($resolver);

		//Set defaults
		$resolver->setDefaults(['phone' => true]);

		//Add extra mail option
		$resolver->setAllowedTypes('phone', 'boolean');
	}


	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_register';
	}
}
