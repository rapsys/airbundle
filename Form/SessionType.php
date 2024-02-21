<?php

namespace Rapsys\AirBundle\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\NotBlank;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;

class SessionType extends AbstractType {
	//Doctrine instance
	private $doctrine;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(ManagerRegistry $doctrine) {
		$this->doctrine = $doctrine;
	}

	/**
	 * @todo: clean that shit
	 * @todo: mapped => false for each button not related with session !!!!
	 * @todo: set stuff in the SessionController, no loggic here please !!!
	 * @todo: add the dance link stuff
	 *
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		//Is admin or user with rainfall >= 2
		if (!empty($options['raincancel'])) {
			//Add raincancel item
			$builder->add('raincancel', SubmitType::class, ['label' => 'Rain cancel', 'attr' => ['class' => 'submit']]);
		//Is admin
		} elseif (!empty($options['admin'])) {
			//Add forcecancel item
			$builder->add('forcecancel', SubmitType::class, ['label' => 'Force cancel', 'attr' => ['class' => 'submit']]);
		}

		//Is admin or owner
		if (!empty($options['modify'])) {
			if (!empty($options['admin'])) {
				$builder
					//Add dance field
					->add('dance', EntityType::class, ['class' => 'RapsysAirBundle:Dance', 'choices' => $options['dance_choices'], 'preferred_choices' => $options['dance_favorites'], 'attr' => ['placeholder' => 'Your dance'], 'choice_translation_domain' => true, 'constraints' => [new NotBlank(['message' => 'Please provide your dance'])], 'data' => $options['dance_default']])

					//Add slot field
					->add('slot', EntityType::class, ['class' => 'RapsysAirBundle:Slot', 'attr' => ['placeholder' => 'Your slot'], 'constraints' => [new NotBlank(['message' => 'Please provide your slot'])], 'choice_translation_domain' => true, 'data' => $options['slot_default']])
					//Add date field
					->add('date', DateType::class, ['attr' => ['placeholder' => 'Your date', 'class' => 'date'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'format' => 'yyyy-MM-dd', 'data' => $options['date'], 'constraints' => [new NotBlank(['message' => 'Please provide your date']), new Type(['type' => \DateTime::class, 'message' => 'Your date doesn\'t seems to be valid'])]]);
			}

			$builder
				//TODO: avertissement + minimum et maximum ???
				->add('begin', TimeType::class, ['attr' => ['placeholder' => 'Your begin', 'class' => 'time'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'data' => $options['begin'], 'constraints' => [new NotBlank(['message' => 'Please provide your begin']), new Type(['type' => \DateTime::class, 'message' => 'Your begin doesn\'t seems to be valid'])]])
				//TODO: avertissement + minimum et maximum ???
				->add('length', TimeType::class, ['attr' => ['placeholder' => 'Your length', 'class' => 'time'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'data' => $options['length'], 'constraints' => [new NotBlank(['message' => 'Please provide your length']), new Type(['type' => \DateTime::class, 'message' => 'Your length doesn\'t seems to be valid'])]])
				->add('modify', SubmitType::class, ['label' => 'Modify', 'attr' => ['class' => 'submit']]);
		}

		//Is admin or applicant
		if (!empty($options['cancel'])) {
			$builder->add('cancel', SubmitType::class, ['label' => 'Cancel', 'attr' => ['class' => 'submit']]);
		}

		//Is admin or senior owner
		if (!empty($options['move'])) {
			//Load locations
			$locations = $this->doctrine->getRepository(Location::class)->findComplementBySessionId($options['session']);
			$builder
				//TODO: class senior en orange ???
				->add('location', ChoiceType::class, ['attr' => ['placeholder' => 'Your location'], 'constraints' => [new NotBlank(['message' => 'Please provide your location'])], 'choices' => $locations, 'choice_translation_domain' => true])
				->add('move', SubmitType::class, ['label' => 'Move', 'attr' => ['class' => 'submit']]);
		}

		//Add extra user field
		if (!empty($options['admin'])) {
			//Load users
			$users = $this->doctrine->getRepository(User::class)->findBySessionId($options['session']);
			//Add admin fields
			$builder
				//TODO: class admin en rouge ???
				->add('user', ChoiceType::class, ['attr' => ['placeholder' => 'Your user'], 'constraints' => [new NotBlank(['message' => 'Please provide your user'])], 'choices' => $users, 'data' => $options['user'], 'choice_translation_domain' => false])
				->add('lock', SubmitType::class, ['label' => 'Lock', 'attr' => ['class' => 'submit']]);

			//Is admin and locked === null
			if (!empty($options['attribute'])) {
				//Add attribute fields
				$builder
					->add('attribute', SubmitType::class, ['label' => 'Attribute', 'attr' => ['class' => 'submit']])
					->add('autoattribute', SubmitType::class, ['label' => 'Auto attribute', 'attr' => ['class' => 'submit']]);
			}
		}

		//Return form
		return $builder;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['error_bubbling' => true, 'admin' => false, 'dance_choices' => [], 'dance_default' => null, 'dance_favorites' => [], 'date' => null, 'begin' => null, 'length' => null, 'cancel' => false, 'raincancel' => false, 'modify' => false, 'move' => false, 'attribute' => false, 'user' => null, 'session' => null, 'slot_default' => null]);

		//Add admin
		$resolver->setAllowedTypes('admin', 'boolean');

		//Add dance choices
		$resolver->setAllowedTypes('dance_choices', 'array');

		//Add dance default
		$resolver->setAllowedTypes('dance_default', [Dance::class, 'null']);

		//Add dance favorites
		$resolver->setAllowedTypes('dance_favorites', 'array');

		//Add date
		$resolver->setAllowedTypes('date', 'datetime');

		//Add begin
		$resolver->setAllowedTypes('begin', 'datetime');

		//Add length
		$resolver->setAllowedTypes('length', 'datetime');

		//Add cancel
		$resolver->setAllowedTypes('cancel', 'boolean');

		//Add raincancel
		$resolver->setAllowedTypes('raincancel', 'boolean');

		//Add modify
		$resolver->setAllowedTypes('modify', 'boolean');

		//Add move
		$resolver->setAllowedTypes('move', 'boolean');

		//Add attribute
		$resolver->setAllowedTypes('attribute', 'boolean');

		//Add user
		$resolver->setAllowedTypes('user', 'integer');

		//Add session
		$resolver->setAllowedTypes('session', 'integer');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_session_edit';
	}
}
