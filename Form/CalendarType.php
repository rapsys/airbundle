<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;

class CalendarType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder
			->add('calendar', TextType::class, ['label' => 'Calendar id', 'attr' => ['placeholder' => 'Your calendar id'], 'constraints' => [new NotBlank(['message' => 'Please provide your calendar id'])]])
			//TODO: validate prefix against [a-v0-9]{5,}
			//XXX: see https://developers.google.com/calendar/api/v3/reference/events/insert#id
			->add('prefix', TextType::class, ['label' => 'Prefix', 'attr' => ['placeholder' => 'Your prefix'], 'constraints' => [new NotBlank(['message' => 'Please provide your prefix'])]])
			->add('project', TextType::class, ['label' => 'Project id', 'attr' => ['placeholder' => 'Your project id'], 'required' => false])
			->add('client', TextType::class, ['label' => 'Client id', 'attr' => ['placeholder' => 'Your client id'], 'required' => false])
			->add('secret', TextType::class, ['label' => 'Client secret', 'attr' => ['placeholder' => 'Your client secret'], 'required' => false])
			->add('submit', SubmitType::class, ['label' => 'Send', 'attr' => ['class' => 'submit']]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(['error_bubbling' => true]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'calendar_form';
	}
}
