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
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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

		//Add extra donate field
		if (!empty($options['donate'])) {
			$form->add('donate', UrlType::class, ['attr' => ['placeholder' => 'Your donate'], 'required' => false]);
		}

		//Add extra link field
		if (!empty($options['link'])) {
			$form->add('link', UrlType::class, ['attr' => ['placeholder' => 'Your link'], 'required' => false]);
		}

		//Add extra phone field
		if (!empty($options['phone'])) {
			$form->add('phone', TelType::class, ['attr' => ['placeholder' => 'Your phone'], 'required' => false]);
		}

		//Add extra profile field
		if (!empty($options['profile'])) {
			$form->add('profile', UrlType::class, ['attr' => ['placeholder' => 'Your profile'], 'required' => false]);
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
		$resolver->setDefaults(['donate' => true, 'link' => true, 'phone' => true, 'profile' => true]);

		//Add extra donate option
		$resolver->setAllowedTypes('donate', 'boolean');

		//Add extra link option
		$resolver->setAllowedTypes('link', 'boolean');

		//Add extra phone option
		$resolver->setAllowedTypes('phone', 'boolean');

		//Add extra profile option
		$resolver->setAllowedTypes('profile', 'boolean');
	}


	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsys_air_register';
	}
}
