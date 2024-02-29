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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\NotBlank;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Slot;

/**
 * {@inheritdoc}
 */
class ApplicationType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		//Create form
		$form = $builder;

		//Add dance field
		$form->add('dance', EntityType::class, ['class' => 'Rapsys\AirBundle\Entity\Dance', 'choices' => $options['dance_choices'], 'preferred_choices' => $options['dance_favorites'], 'attr' => ['placeholder' => 'Your dance'], 'choice_translation_domain' => true, 'constraints' => [new NotBlank(['message' => 'Please provide your dance'])], 'data' => $options['dance_default']]);

		//Add date field
		$form->add('date', DateType::class, ['attr' => ['placeholder' => 'Your date', 'class' => 'date'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'format' => 'yyyy-MM-dd', 'data' => new \DateTime('+7 day'), 'constraints' => [new NotBlank(['message' => 'Please provide your date']), new Type(['type' => \DateTime::class, 'message' => 'Your date doesn\'t seems to be valid'])]]);

		//Add location field
		$form->add('location', EntityType::class, ['class' => 'Rapsys\AirBundle\Entity\Location', 'choices' => $options['location_choices'], 'preferred_choices' => $options['location_favorites'], 'attr' => ['placeholder' => 'Your location'], 'choice_translation_domain' => true, 'constraints' => [new NotBlank(['message' => 'Please provide your location'])], 'data' => $options['location_default']]);

		//Add slot field
		$form->add('slot', EntityType::class, ['class' => 'Rapsys\AirBundle\Entity\Slot', 'attr' => ['placeholder' => 'Your slot'], 'constraints' => [new NotBlank(['message' => 'Please provide your slot'])], 'choice_translation_domain' => true, 'data' => $options['slot_default']]);

		//Add extra user field
		if (!empty($options['user'])) {
			//XXX: choicetype used here to use our own custom translated string
			$form->add('user', ChoiceType::class, ['attr' => ['placeholder' => 'Your user'], 'choice_translation_domain' => false, 'constraints' => [new NotBlank(['message' => 'Please provide your user'])], 'choices' => $options['user_choices'], 'data' => $options['user_default']]);
		}

		//Add submit
		$form->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);

		//Return form
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		//Set defaults
		$resolver->setDefaults(['error_bubbling' => true, 'dance_choices' => [], 'dance_default' => null, 'dance_favorites' => [], 'location_choices' => [], 'location_default' => null, 'location_favorites' => [], 'slot_default' => null, 'user' => true, 'user_choices' => [], 'user_default' => 1]);

		//Add dance choices
		$resolver->setAllowedTypes('dance_choices', 'array');

		//Add dance default
		$resolver->setAllowedTypes('dance_default', [Dance::class, 'null']);

		//Add dance favorites
		$resolver->setAllowedTypes('dance_favorites', 'array');

		//Add location choices
		$resolver->setAllowedTypes('location_choices', 'array');

		//Add location default
		$resolver->setAllowedTypes('location_default', [Location::class, 'null']);

		//Add location favorites
		$resolver->setAllowedTypes('location_favorites', 'array');

		//Add slot default
		$resolver->setAllowedTypes('slot_default', [Slot::class, 'null']);

		//Add user field
		$resolver->setAllowedTypes('user', 'boolean');

		//Add user choices
		$resolver->setAllowedTypes('user_choices', 'array');

		//Add user default
		$resolver->setAllowedTypes('user_default', 'integer');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_application';
	}
}
