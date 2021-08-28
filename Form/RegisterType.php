<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys PackBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends \Rapsys\UserBundle\Form\RegisterType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): FormBuilderInterface {
		//Call parent build form
		$form = parent::buildForm($builder, $options);

		//Add extra phone field
		if (!empty($options['phone'])) {
			$form->add('phone', TelType::class, ['attr' => ['placeholder' => 'Your phone'], 'required' => false]);
		}

		//Add extra pseudonym field
		if (!empty($options['pseudonym'])) {
			$form->add('pseudonym', TextType::class, ['attr' => ['placeholder' => 'Your pseudonym'], 'constraints' => [new NotBlank(['message' => 'Please provide your pseudonym'])]]);
		}

		//Add extra slug field
		if (!empty($options['slug'])) {
			$form->add('slug', TextType::class, ['attr' => ['placeholder' => 'Your slug'], 'required' => false]);
		}

		//Return form
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		//Call parent configure options
		parent::configureOptions($resolver);

		//Set defaults
		$resolver->setDefaults(['phone' => true, 'pseudonym' => true, 'slug' => true]);

		//Add extra phone option
		$resolver->setAllowedTypes('phone', 'boolean');

		//Add extra pseudonym option
		$resolver->setAllowedTypes('pseudonym', 'boolean');

		//Add extra slug option
		$resolver->setAllowedTypes('slug', 'boolean');
	}


	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsys_air_register';
	}
}
