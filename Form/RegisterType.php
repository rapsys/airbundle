<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Rapsys\AirBundle\Entity\Country;

use Rapsys\UserBundle\Form\RegisterType as BaseRegisterType;

class RegisterType extends BaseRegisterType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): FormBuilderInterface {
		//Call parent build form
		$form = parent::buildForm($builder, $options);

		//Add extra city field
		if (!empty($options['city'])) {
			$form->add('city', TextType::class, ['attr' => ['placeholder' => 'Your city'], 'required' => false]);
		}

		//Add extra country field
		if (!empty($options['country'])) {
			//Add country field
			$form->add('country', EntityType::class, ['class' => $options['country_class'], 'choice_label' => 'title'/*, 'choices' => $options['location_choices']*/, 'preferred_choices' => $options['country_favorites'], 'attr' => ['placeholder' => 'Your country'], 'choice_translation_domain' => false, 'required' => true, 'data' => $options['country_default']]);
		}

		//Add extra phone field
		if (!empty($options['phone'])) {
			$form->add('phone', TelType::class, ['attr' => ['placeholder' => 'Your phone'], 'required' => false]);
		}

		//Add extra pseudonym field
		if (!empty($options['pseudonym'])) {
			$form->add('pseudonym', TextType::class, ['attr' => ['placeholder' => 'Your pseudonym'], 'required' => false]);
		}

		//Add extra zipcode field
		if (!empty($options['zipcode'])) {
			$form->add('zipcode', TextType::class, ['attr' => ['placeholder' => 'Your zipcode'], 'required' => false]);
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
		$resolver->setDefaults(['city' => true, 'country' => true, 'country_class' => 'RapsysAirBundle:Country', 'country_default' => null, 'country_favorites' => [], 'phone' => true, 'pseudonym' => true, 'zipcode' => true]);

		//Add extra city option
		$resolver->setAllowedTypes('city', 'boolean');

		//Add extra country option
		$resolver->setAllowedTypes('country', 'boolean');

		//Add country class
		$resolver->setAllowedTypes('country_class', 'string');

		//Add country default
		$resolver->setAllowedTypes('country_default', [Country::class, 'null']);

		//Add country favorites
		$resolver->setAllowedTypes('country_favorites', 'array');

		//Add extra phone option
		$resolver->setAllowedTypes('phone', 'boolean');

		//Add extra pseudonym option
		$resolver->setAllowedTypes('pseudonym', 'boolean');

		//Add extra zipcode option
		$resolver->setAllowedTypes('zipcode', 'boolean');
	}


	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return 'rapsys_air_register';
	}
}
