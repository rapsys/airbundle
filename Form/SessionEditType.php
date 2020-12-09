<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Time;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Location;

class SessionEditType extends AbstractType {
	//Doctrine instance
	private $doctrine;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(RegistryInterface $doctrine) {
		$this->doctrine = $doctrine;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		//Is admin or rainfall >= 2
		if (!empty($options['raincancel'])) {
			//Add raincancel item
			$builder->add('raincancel', SubmitType::class, ['label' => 'Rain cancel', 'attr' => ['class' => 'submit']]);
		}

		//Is admin or owner
		if (!empty($options['modify'])) {
			$builder
				//TODO: avertissement + minimum et maximum ???
				->add('begin', TimeType::class, ['attr' => ['placeholder' => 'Your begin', 'class' => 'time'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'data' => $options['begin'], 'constraints' => [new NotBlank(['message' => 'Please provide your begin']), new Time(['message' => 'Your begin doesn\'t seems to be valid'])]])
				//TODO: avertissement + minimum et maximum ???
				->add('length', TimeType::class, ['attr' => ['placeholder' => 'Your length', 'class' => 'time'], 'html5' => true, 'input' => 'datetime', 'widget' => 'single_text', 'data' => $options['length'], 'constraints' => [new NotBlank(['message' => 'Please provide your length']), new Time(['message' => 'Your length doesn\'t seems to be valid'])]])
				->add('modify', SubmitType::class, ['label' => 'Modify', 'attr' => ['class' => 'submit']]);
		}

		//Is admin or applicant
		if (!empty($options['cancel'])) {
			$builder->add('cancel', SubmitType::class, ['label' => 'Cancel', 'attr' => ['class' => 'submit']]);
		}

		//Is admin or session not finished
		#if (!empty($options['admin']) || $options['stop'] > new \DateTime('now')) {

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
			$users = $this->doctrine->getRepository(User::class)->findAllApplicantBySession($options['session']);
			//Add admin fields
			$builder
				//TODO: class admin en rouge ???
				->add('user', ChoiceType::class, ['attr' => ['placeholder' => 'Your user'], 'constraints' => [new NotBlank(['message' => 'Please provide your user'])], 'choices' => $users, 'data' => $options['user'], 'choice_translation_domain' => false])
				->add('attribute', SubmitType::class, ['label' => 'Attribute', 'attr' => ['class' => 'submit']])
				->add('autoattribute', SubmitType::class, ['label' => 'Auto attribute', 'attr' => ['class' => 'submit']])
				->add('lock', SubmitType::class, ['label' => 'Lock', 'attr' => ['class' => 'submit']]);
		}

		//Return form
		return $builder;
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['error_bubbling' => true, 'admin' => false, 'begin' => null, 'length' => null, 'cancel' => false, 'raincancel' => false, 'modify' => false, 'move' => false, 'user' => null, 'session' => null]);
		$resolver->setAllowedTypes('admin', 'boolean');
		#TODO: voir si c'est le bon type
		$resolver->setAllowedTypes('begin', 'datetime');
		$resolver->setAllowedTypes('length', 'datetime');
		$resolver->setAllowedTypes('cancel', 'boolean');
		$resolver->setAllowedTypes('raincancel', 'boolean');
		$resolver->setAllowedTypes('modify', 'boolean');
		$resolver->setAllowedTypes('move', 'boolean');
		$resolver->setAllowedTypes('user', 'integer');
		$resolver->setAllowedTypes('session', 'integer');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'rapsys_air_session_edit';
	}
}
