<?php

namespace Rapsys\AirBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;

class DisputeType extends AbstractType {
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		return $builder
			->add('offense', ChoiceType::class, ['choices' => ['Forbidden gathering' => 'gathering', 'Traffic at prohibited time' => 'traffic'/*, 'Traffic at prohibited location' => 'location'*/], 'attr' => ['placeholder' => 'Your offense'], 'constraints' => [new NotBlank(['message' => 'Please provide your offense'])]])
			->add('court', TextType::class, ['label' => 'Court city', 'attr' => ['placeholder' => 'Your court city'], 'constraints' => [new NotBlank(['message' => 'Please provide your court city'])]])
			->add('notice', TextType::class, ['label' => 'Notice number', 'attr' => ['placeholder' => 'Your notice number'], 'constraints' => [new NotBlank(['message' => 'Please provide your notice'])]])
			->add('agent', TextType::class, ['label' => 'Agent number', 'attr' => ['placeholder' => 'Your agent number'], 'constraints' => [new NotBlank(['message' => 'Please provide your agent'])]])
			->add('service', TextType::class, ['label' => 'Service code', 'attr' => ['placeholder' => 'Your service code'], 'constraints' => [new NotBlank(['message' => 'Please provide your service'])]])
			->add('abstract', TextareaType::class, ['attr' => ['placeholder' => 'Your abstract', 'cols' => 50, 'rows' => 15], 'constraints' => []])
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
		return 'dispute_form';
	}
}
